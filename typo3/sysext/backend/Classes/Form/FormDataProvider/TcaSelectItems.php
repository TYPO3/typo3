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

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Resolve select items, set processed item list in processedTca, sanitize and resolve database field
 */
class TcaSelectItems extends AbstractItemProvider implements FormDataProviderInterface
{
    /**
     * Resolve select items
     *
     * @param array $result
     * @return array
     * @throws \UnexpectedValueException
     */
    public function addData(array $result)
    {
        $table = $result['tableName'];

        foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
            if (empty($fieldConfig['config']['type']) || $fieldConfig['config']['type'] !== 'select') {
                continue;
            }

            // Make sure we are only processing supported renderTypes
            if (!$this->isTargetRenderType($fieldConfig)) {
                continue;
            }

            $fieldConfig['config']['items'] = $this->sanitizeItemArray($fieldConfig['config']['items'] ?? [], $table, $fieldName);

            $fieldConfig['config']['maxitems'] = MathUtility::forceIntegerInRange($fieldConfig['config']['maxitems'] ?? 0, 0, 99999);
            if ($fieldConfig['config']['maxitems'] === 0) {
                $fieldConfig['config']['maxitems'] = 99999;
            }

            $fieldConfig['config']['items'] = $this->addItemsFromSpecial($result, $fieldName, $fieldConfig['config']['items']);
            $fieldConfig['config']['items'] = $this->addItemsFromFolder($result, $fieldName, $fieldConfig['config']['items']);

            $fieldConfig['config']['items'] = $this->addItemsFromForeignTable($result, $fieldName, $fieldConfig['config']['items']);

            // Resolve "itemsProcFunc"
            if (!empty($fieldConfig['config']['itemsProcFunc'])) {
                $fieldConfig['config']['items'] = $this->resolveItemProcessorFunction($result, $fieldName, $fieldConfig['config']['items']);
                // itemsProcFunc must not be used anymore
                unset($fieldConfig['config']['itemsProcFunc']);
            }

            // removing items before $dynamicItems and $removedItems have been built results in having them
            // not populated to the dynamic database row and displayed as "invalid value" in the forms view
            $fieldConfig['config']['items'] = $this->removeItemsByUserStorageRestriction($result, $fieldName, $fieldConfig['config']['items']);

            $removedItems = $fieldConfig['config']['items'];

            $fieldConfig['config']['items'] = $this->removeItemsByKeepItemsPageTsConfig($result, $fieldName, $fieldConfig['config']['items']);
            $fieldConfig['config']['items'] = $this->addItemsFromPageTsConfig($result, $fieldName, $fieldConfig['config']['items']);
            $fieldConfig['config']['items'] = $this->removeItemsByRemoveItemsPageTsConfig($result, $fieldName, $fieldConfig['config']['items']);

            $fieldConfig['config']['items'] = $this->removeItemsByUserLanguageFieldRestriction($result, $fieldName, $fieldConfig['config']['items']);
            $fieldConfig['config']['items'] = $this->removeItemsByUserAuthMode($result, $fieldName, $fieldConfig['config']['items']);
            $fieldConfig['config']['items'] = $this->removeItemsByDoktypeUserRestriction($result, $fieldName, $fieldConfig['config']['items']);

            $removedItems = array_diff_key($removedItems, $fieldConfig['config']['items']);

            $currentDatabaseValuesArray = $this->processDatabaseFieldValue($result['databaseRow'], $fieldName);
            // Check if it's a new record to respect TCAdefaults
            if (!empty($fieldConfig['config']['MM']) && $result['command'] !== 'new') {
                // Getting the current database value on a mm relation doesn't make sense since the amount of selected
                // relations is stored in the field and not the uids of the items
                $currentDatabaseValuesArray = [];
            }

            $result['databaseRow'][$fieldName] = $currentDatabaseValuesArray;

            // add item values as keys to determine which items are stored in the database and should be preselected
            $itemArrayValues = array_column($fieldConfig['config']['items'], 1);
            $itemArray = array_fill_keys(
                $itemArrayValues,
                $fieldConfig['config']['items']
            );
            $result['databaseRow'][$fieldName] = $this->processSelectFieldValue($result, $fieldName, $itemArray);

            $fieldConfig['config']['items'] = $this->addInvalidItemsFromDatabase(
                $result,
                $table,
                $fieldName,
                $fieldConfig,
                $currentDatabaseValuesArray,
                $removedItems
            );

            // Translate labels
            // skip file of sys_file_metadata which is not rendered anyway but can use all memory
            if (!($table === 'sys_file_metadata' && $fieldName === 'file')) {
                $fieldConfig['config']['items'] = $this->translateLabels($result, $fieldConfig['config']['items'], $table, $fieldName);
            }

            // Keys may contain table names, so a numeric array is created
            $fieldConfig['config']['items'] = array_values($fieldConfig['config']['items']);

            $result['processedTca']['columns'][$fieldName] = $fieldConfig;
        }

        return $result;
    }

    /**
     * Add values that are currently listed in the database columns but not in the selectable items list
     * back to the list.
     *
     * @param array $result The current result array.
     * @param string $table The current table name
     * @param string $fieldName The current field name
     * @param array $fieldConf The configuration of the current field.
     * @param array $databaseValues The item values from the database, can contain invalid items!
     * @param array $removedItems Items removed by access checks and restrictions, must not be added as invalid values
     * @return array
     */
    public function addInvalidItemsFromDatabase(array $result, $table, $fieldName, array $fieldConf, array $databaseValues, array $removedItems)
    {
        // Early return if there are no items or invalid values should not be displayed
        if (empty($fieldConf['config']['items'])
            || $fieldConf['config']['renderType'] !== 'selectSingle'
            || ($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['disableNoMatchingValueElement'] ?? false)
            || ($fieldConf['config']['disableNoMatchingValueElement'] ?? false)
        ) {
            return $fieldConf['config']['items'];
        }

        $languageService = $this->getLanguageService();
        $noMatchingLabel = isset($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['noMatchingValue_label'])
            ? $languageService->sL(trim($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['noMatchingValue_label']))
            : '[ ' . $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.noMatchingValue') . ' ]';

        $unmatchedValues = array_diff(
            array_values($databaseValues),
            array_column($fieldConf['config']['items'], 1),
            array_column($removedItems, 1)
        );

        foreach ($unmatchedValues as $unmatchedValue) {
            $invalidItem = [
                @sprintf($noMatchingLabel, $unmatchedValue),
                $unmatchedValue
            ];
            array_unshift($fieldConf['config']['items'], $invalidItem);
        }

        return $fieldConf['config']['items'];
    }

    /**
     * Determines whether the current field is a valid target for this DataProvider
     *
     * @param array $fieldConfig
     * @return bool
     */
    protected function isTargetRenderType(array $fieldConfig)
    {
        return $fieldConfig['config']['renderType'] !== 'selectTree';
    }
}
