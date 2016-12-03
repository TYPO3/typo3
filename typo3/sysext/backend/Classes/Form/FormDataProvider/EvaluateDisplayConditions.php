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
use TYPO3\CMS\Backend\Form\Utility\DisplayConditionEvaluator;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class EvaluateDisplayConditions implements the TCA 'displayCond' option.
 * The display condition is a colon separated string which describes
 * the condition to decide whether a form field should be displayed.
 */
class EvaluateDisplayConditions implements FormDataProviderInterface
{
    /**
     * Remove fields from processedTca columns that should not be displayed.
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        $result = $this->removeFlexformFields($result);
        $result = $this->removeFlexformSheets($result);
        $result = $this->removeTcaColumns($result);

        return $result;
    }

    /**
     * Evaluate the TCA column display conditions and remove columns that are not displayed
     *
     * @param array $result
     * @return array
     */
    protected function removeTcaColumns($result)
    {
        foreach ($result['processedTca']['columns'] as $columnName => $columnConfiguration) {
            if (!isset($columnConfiguration['displayCond'])) {
                continue;
            }

            $displayConditionValid = $this->getDisplayConditionEvaluator()->evaluateDisplayCondition(
                $columnConfiguration['displayCond'],
                $result['databaseRow']
            );
            if (!$displayConditionValid) {
                unset($result['processedTca']['columns'][$columnName]);
            }
        }

        return $result;
    }

    /**
     * Remove flexform sheets from processed tca if hidden by display conditions
     *
     * @param array $result
     * @return array
     */
    protected function removeFlexformSheets($result)
    {
        foreach ($result['processedTca']['columns'] as $columnName => $columnConfiguration) {
            if (!isset($columnConfiguration['config']['type'])
                || $columnConfiguration['config']['type'] !== 'flex'
                || !isset($result['processedTca']['columns'][$columnName]['config']['ds']['sheets'])
                || !is_array($result['processedTca']['columns'][$columnName]['config']['ds']['sheets'])
            ) {
                continue;
            }

            $flexFormRowData = is_array($result['databaseRow'][$columnName]['data']) ? $result['databaseRow'][$columnName]['data'] : [];
            $flexFormRowData = $this->flattenFlexformRowData($flexFormRowData);
            $flexFormRowData['parentRec'] = $result['databaseRow'];

            $flexFormSheets = $result['processedTca']['columns'][$columnName]['config']['ds']['sheets'];
            foreach ($flexFormSheets as $sheetName => $sheetConfiguration) {
                if (!isset($sheetConfiguration['ROOT']['displayCond'])) {
                    continue;
                }
                $displayConditionValid = $this->getDisplayConditionEvaluator()->evaluateDisplayCondition(
                    $sheetConfiguration['ROOT']['displayCond'],
                    $flexFormRowData,
                    true
                );
                if (!$displayConditionValid) {
                    unset($result['processedTca']['columns'][$columnName]['config']['ds']['sheets'][$sheetName]);
                }
            }
        }

        return $result;
    }

    /**
     * Remove fields from flexform sheets if hidden by display conditions
     *
     * @param array $result
     * @return array
     */
    protected function removeFlexformFields($result)
    {
        foreach ($result['processedTca']['columns'] as $columnName => $columnConfiguration) {
            if (!isset($columnConfiguration['config']['type'])
                || $columnConfiguration['config']['type'] !== 'flex'
                || !isset($result['processedTca']['columns'][$columnName]['config']['ds']['sheets'])
                || !is_array($result['processedTca']['columns'][$columnName]['config']['ds']['sheets'])
            ) {
                continue;
            }

            $flexFormRowData = is_array($result['databaseRow'][$columnName]['data']) ? $result['databaseRow'][$columnName]['data'] : [];
            $flexFormRowData['parentRec'] = $result['databaseRow'];

            foreach ($result['processedTca']['columns'][$columnName]['config']['ds']['sheets'] as $sheetName => $sheetConfiguration) {
                $flexFormSheetRowData = $flexFormRowData[$sheetName]['lDEF'];
                $flexFormSheetRowData['parentRec'] = $result['databaseRow'];
                $result['processedTca']['columns'][$columnName]['config']['ds']['sheets'][$sheetName] = $this->removeFlexformFieldsRecursive(
                    $result['processedTca']['columns'][$columnName]['config']['ds']['sheets'][$sheetName],
                    $flexFormSheetRowData
                );
            }
        }

        return $result;
    }

    /**
     * Remove fields from flexform data structure
     *
     * @param array $structure Given hierarchy
     * @param array $flexFormRowData
     * @return array Modified hierarchy
     */
    protected function removeFlexformFieldsRecursive($structure, $flexFormRowData)
    {
        $newStructure = [];
        foreach ($structure as $key => $value) {
            if ($key === 'el' && is_array($value)) {
                $newSubStructure = [];
                foreach ($value as $subKey => $subValue) {
                    if (!isset($subValue['displayCond']) || $this->getDisplayConditionEvaluator()->evaluateDisplayCondition($subValue['displayCond'], $flexFormRowData, true)) {
                        $newSubStructure[$subKey] = $subValue;
                    }
                }
                $value = $newSubStructure;
            }
            if (is_array($value)) {
                $value = $this->removeFlexformFieldsRecursive($value, $flexFormRowData);
            }
            $newStructure[$key] = $value;
        }

        return $newStructure;
    }

    /**
     * Flatten the Flexform data row for sheet level display conditions that use SheetName.FieldName
     *
     * @param array $flexFormRowData
     * @return array
     */
    protected function flattenFlexformRowData($flexFormRowData)
    {
        $flatFlexFormRowData = [];
        foreach ($flexFormRowData as $sheetName => $sheetConfiguration) {
            foreach ($sheetConfiguration['lDEF'] as $fieldName => $fieldConfiguration) {
                $flatFlexFormRowData[$sheetName . '.' . $fieldName] = $fieldConfiguration;
            }
        }

        return $flatFlexFormRowData;
    }

    /**
     * Returns the DisplayConditionEvaluator utility.
     *
     * @return DisplayConditionEvaluator
     */
    protected function getDisplayConditionEvaluator()
    {
        return GeneralUtility::makeInstance(DisplayConditionEvaluator::class);
    }
}
