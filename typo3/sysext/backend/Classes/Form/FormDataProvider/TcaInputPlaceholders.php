<?php
namespace TYPO3\CMS\Backend\Form\FormDataProvider;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaInputPlaceholderRecord;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Resolve placeholders for fields of type input or text. The placeholder value
 * in the processedTca section of the result will be replaced with the resolved
 * value.
 */
class TcaInputPlaceholders implements FormDataProviderInterface
{
    /**
     * Resolve placeholders for input/text fields. Placeholders that are simple
     * strings will be returned unmodified. Placeholders beginning with __row are
     * being resolved, possibly traversing multiple tables.
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
            // Placeholders are only valid for input and text type fields
            if (
                ($fieldConfig['config']['type'] !== 'input' && $fieldConfig['config']['type'] !== 'text')
                || !isset($fieldConfig['config']['placeholder'])
            ) {
                continue;
            }

            // Resolve __row|field type placeholders
            if (StringUtility::beginsWith($fieldConfig['config']['placeholder'], '__row|')) {
                // split field names into array and remove the __row indicator
                $fieldNameArray = array_slice(
                    GeneralUtility::trimExplode('|', $fieldConfig['config']['placeholder'], true),
                    1
                );
                $result['processedTca']['columns'][$fieldName]['config']['placeholder'] = $this->getPlaceholderValue($fieldNameArray, $result);
            }

            // Resolve placeholders from language files
            if (StringUtility::beginsWith($fieldConfig['config']['placeholder'], 'LLL:')) {
                $result['processedTca']['columns'][$fieldName]['config']['placeholder'] = $this->getLanguageService()->sl($fieldConfig['config']['placeholder']);
            }

            // Remove empty placeholders
            if (empty($result['processedTca']['columns'][$fieldName]['config']['placeholder'])) {
                unset($result['processedTca']['columns'][$fieldName]['config']['placeholder']);
            }
        }

        return $result;
    }

    /**
     * Recursively resolve the placeholder value. A placeholder string with a
     * syntax of __row|field1|field2|field3 will be recursively resolved to a
     * final value.
     *
     * @param array $fieldNameArray
     * @param array $result
     * @param int $recursionLevel
     * @return string
     */
    protected function getPlaceholderValue($fieldNameArray, $result, $recursionLevel = 0)
    {
        if ($recursionLevel > 99) {
            // This should not happen, treat as misconfiguration
            return '';
        }

        $fieldName = array_shift($fieldNameArray);
        $fieldConfig = $result['processedTca']['columns'][$fieldName]['config'];

        // Skip if a defined field was actually not present in the database row
        // Using array_key_exists here, since NULL values are valid as well.
        if (!array_key_exists($fieldName, $result['databaseRow'])) {
            return '';
        }

        $value = $result['databaseRow'][$fieldName];

        switch ($fieldConfig['type']) {
            case 'select':
                // The FormDataProviders already resolved the select items to an array of uids,
                // filter out empty values that occur when no related record has been selected.
                $possibleUids = array_filter($value);
                $foreignTableName = $fieldConfig['foreign_table'];
                break;
            case 'group':
                $possibleUids = $this->getRelatedGroupFieldUids($fieldConfig, $value);
                $foreignTableName = $this->getAllowedTableForGroupField($fieldConfig);
                break;
            case 'inline':
                $possibleUids = array_filter(GeneralUtility::trimExplode(',', $value, true));
                $foreignTableName = $fieldConfig['foreign_table'];
                break;
            default:
                $possibleUids = [];
                $foreignTableName = '';
        }

        if (!empty($possibleUids) && !empty($fieldNameArray)) {
            $relatedFormData = $this->getRelatedFormData($foreignTableName, $possibleUids[0], $fieldNameArray[0]);
            $value = $this->getPlaceholderValue($fieldNameArray, $relatedFormData, $recursionLevel + 1);
        }

        if ($recursionLevel === 0 && is_array($value)) {
            $value = implode(', ', $value);
        }
        return (string)$value;
    }

    /**
     * Compile a formdata result set based on the tablename and record uid.
     *
     * @param string $tableName Name of the table for which to compile formdata
     * @param int $uid UID of the record for which to compile the formdata
     * @param string $columnToProcess The column that is required from the record
     * @return array The compiled formdata
     */
    protected function getRelatedFormData($tableName, $uid, $columnToProcess)
    {
        $fakeDataInput = [
            'command' => 'edit',
            'vanillaUid' => (int)$uid,
            'tableName' => $tableName,
            'inlineCompileExistingChildren' => false,
            'columnsToProcess' => [$columnToProcess],
        ];
        /** @var TcaInputPlaceholderRecord $formDataGroup */
        $formDataGroup = GeneralUtility::makeInstance(TcaInputPlaceholderRecord::class);
        /** @var FormDataCompiler $formDataCompiler */
        $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
        $compilerResult = $formDataCompiler->compile($fakeDataInput);
        return $compilerResult;
    }

    /**
     * Return uids of related records for group type fields. Uids consisting of
     * multiple parts like [table]_[uid]|[title] will be reduced to integers and
     * validated against the allowed table. Uids without a table prefix are
     * accepted in any case.
     *
     * @param array $fieldConfig TCA "config" section for the group type field.
     * @param string $value A comma separated list of records
     * @return array
     */
    protected function getRelatedGroupFieldUids(array $fieldConfig, $value)
    {
        $relatedUids = [];
        $allowedTable = $this->getAllowedTableForGroupField($fieldConfig);

        // Skip if it's not a database relation with a resolvable foreign table
        if (($fieldConfig['internal_type'] !== 'db') || ($allowedTable === false)) {
            return $relatedUids;
        }

        $values = GeneralUtility::trimExplode(',', $value, true);
        foreach ($values as $groupValue) {
            list($foreignIdentifier, $_) = GeneralUtility::trimExplode('|', $groupValue);
            list($recordForeignTable, $foreignUid) = BackendUtility::splitTable_Uid($foreignIdentifier);
            // skip records that do not match the allowed table
            if (!empty($recordForeignTable) && ($recordForeignTable !== $allowedTable)) {
                continue;
            }
            $relatedUids[] = $foreignUid;
        }

        return $relatedUids;
    }

    /**
     * Will read the "allowed" value from the given field configuration
     * and returns FALSE if none or more than one has been defined.
     * Otherwise the name of the allowed table will be returned.
     *
     * @param array $fieldConfig TCA "config" section for the group type field.
     * @return bool|string
     */
    protected function getAllowedTableForGroupField(array $fieldConfig)
    {
        $allowedTable = false;

        $allowedTables = GeneralUtility::trimExplode(',', $fieldConfig['allowed'], true);
        if (count($allowedTables) === 1) {
            $allowedTable = $allowedTables[0];
        }

        return $allowedTable;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
