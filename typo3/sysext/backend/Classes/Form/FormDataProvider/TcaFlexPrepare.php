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
use TYPO3\CMS\Core\Migrations\TcaMigration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Prepare flex data structure and data values.
 *
 * This data provider is typically executed directly after TcaFlexFetch
 */
class TcaFlexPrepare implements FormDataProviderInterface
{
    /**
     * Resolve flex data structures and prepare flex data values.
     *
     * Normalize some details to have aligned array nesting for the rest of the
     * processing method and the render engine.
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
            if (empty($fieldConfig['config']['type']) || $fieldConfig['config']['type'] !== 'flex') {
                continue;
            }
            $result = $this->createDefaultSheetInDataStructureIfNotGiven($result, $fieldName);
            $result = $this->removeTceFormsArrayKeyFromDataStructureElements($result, $fieldName);
            $result = $this->migrateFlexformTcaDataStructureElements($result, $fieldName);
        }

        return $result;
    }

    /**
     * Add a sheet structure if data structure has none yet to simplify further handling.
     *
     * Example TCA field config:
     * ['config']['ds']['ROOT'] becomes
     * ['config']['ds']['sheets']['sDEF']['ROOT']
     *
     * @param array $result Result array
     * @param string $fieldName Currently handled field name
     * @return array Modified result
     * @throws \UnexpectedValueException
     */
    protected function createDefaultSheetInDataStructureIfNotGiven(array $result, $fieldName)
    {
        $modifiedDataStructure = $result['processedTca']['columns'][$fieldName]['config']['ds'];
        if (isset($modifiedDataStructure['ROOT']) && isset($modifiedDataStructure['sheets'])) {
            throw new \UnexpectedValueException(
                'Parsed data structure has both ROOT and sheets on top level',
                1440676540
            );
        }
        if (isset($modifiedDataStructure['ROOT']) && is_array($modifiedDataStructure['ROOT'])) {
            $modifiedDataStructure['sheets']['sDEF']['ROOT'] = $modifiedDataStructure['ROOT'];
            unset($modifiedDataStructure['ROOT']);
        }
        $result['processedTca']['columns'][$fieldName]['config']['ds'] = $modifiedDataStructure;
        return $result;
    }

    /**
     * Remove "TCEforms" key from all elements in data structure to simplify further parsing.
     *
     * Example config:
     * ['config']['ds']['sheets']['sDEF']['ROOT']['el']['anElement']['TCEforms']['label'] becomes
     * ['config']['ds']['sheets']['sDEF']['ROOT']['el']['anElement']['label']
     *
     * @param array $result Result array
     * @param string $fieldName Currently handled field name
     * @return array Modified result
     */
    protected function removeTceFormsArrayKeyFromDataStructureElements(array $result, $fieldName)
    {
        $modifiedDataStructure = $result['processedTca']['columns'][$fieldName]['config']['ds'];
        $modifiedDataStructure = $this->removeElementTceFormsRecursive($modifiedDataStructure);
        $result['processedTca']['columns'][$fieldName]['config']['ds'] = $modifiedDataStructure;
        return $result;
    }

    /**
     * Moves ['el']['something']['TCEforms'] to ['el']['something'] and ['ROOT']['TCEforms'] to ['ROOT'] recursive
     *
     * @param array $structure Given hierarchy
     * @return array Modified hierarchy
     */
    protected function removeElementTceFormsRecursive(array $structure)
    {
        $newStructure = [];
        foreach ($structure as $key => $value) {
            if ($key === 'ROOT' && is_array($value) && isset($value['TCEforms'])) {
                $value = array_merge($value, $value['TCEforms']);
                unset($value['TCEforms']);
            }
            if ($key === 'el' && is_array($value)) {
                $newSubStructure = [];
                foreach ($value as $subKey => $subValue) {
                    if (is_array($subValue) && count($subValue) === 1 && isset($subValue['TCEforms'])) {
                        $newSubStructure[$subKey] = $subValue['TCEforms'];
                    } else {
                        $newSubStructure[$subKey] = $subValue;
                    }
                }
                $value = $newSubStructure;
            }
            if (is_array($value)) {
                $value = $this->removeElementTceFormsRecursive($value);
            }
            $newStructure[$key] = $value;
        }
        return $newStructure;
    }

    /**
     * On-the-fly migration for flex form "TCA"
     *
     * @param array $result Result array
     * @param string $fieldName Currently handled field name
     * @return array Modified result
     */
    protected function migrateFlexformTcaDataStructureElements(array $result, $fieldName)
    {
        $modifiedDataStructure = $result['processedTca']['columns'][$fieldName]['config']['ds'];
        $modifiedDataStructure = $this->migrateFlexformTcaRecursive($modifiedDataStructure, $result['tableName'], $fieldName);
        $result['processedTca']['columns'][$fieldName]['config']['ds'] = $modifiedDataStructure;
        return $result;
    }

    /**
     * Recursively migrate flex form TCA
     *
     * @param array $structure Given hierarchy
     * @param string $table
     * @param string $fieldName
     * @return array Modified hierarchy
     */
    protected function migrateFlexformTcaRecursive($structure, $table, $fieldName)
    {
        $newStructure = [];
        foreach ($structure as $key => $value) {
            if ($key === 'el' && is_array($value)) {
                $newSubStructure = [];
                $tcaMigration = GeneralUtility::makeInstance(TcaMigration::class);
                foreach ($value as $subKey => $subValue) {
                    // On-the-fly migration for flex form "TCA"
                    // @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8. This can be removed *if* no additional TCA migration is added with CMS 8, see class TcaMigration
                    $dummyTca = [
                        'dummyTable' => [
                            'columns' => [
                                'dummyField' => $subValue,
                            ],
                        ],
                    ];
                    $migratedTca = $tcaMigration->migrate($dummyTca);
                    $messages = $tcaMigration->getMessages();
                    if (!empty($messages)) {
                        $context = 'FormEngine did an on-the-fly migration of a flex form data structure. This is deprecated and will be removed'
                            . ' with TYPO3 CMS 8. Merge the following changes into the flex form definition of table "' . $table . '"" in field "' . $fieldName . '"":';
                        array_unshift($messages, $context);
                        GeneralUtility::deprecationLog(implode(LF, $messages));
                    }
                    $newSubStructure[$subKey] = $migratedTca['dummyTable']['columns']['dummyField'];
                }
                $value = $newSubStructure;
            }
            if (is_array($value)) {
                $value = $this->migrateFlexformTcaRecursive($value, $table, $fieldName);
            }
            $newStructure[$key] = $value;
        }
        return $newStructure;
    }
}
