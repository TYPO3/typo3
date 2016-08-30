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
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
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

            if (!$this->evaluateDisplayCondition($columnConfiguration['displayCond'], $result['databaseRow'])) {
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
                if (!$this->evaluateDisplayCondition($sheetConfiguration['ROOT']['displayCond'], $flexFormRowData, true)) {
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
                    if (!isset($subValue['displayCond']) || $this->evaluateDisplayCondition($subValue['displayCond'], $flexFormRowData, true)) {
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
     * Evaluates the provided condition and returns TRUE if the form
     * element should be displayed.
     *
     * The condition string is separated by colons and the first part
     * indicates what type of evaluation should be performed.
     *
     * @param string $displayCondition
     * @param array $record
     * @param bool $flexformContext
     * @param int $recursionLevel Internal level of recursion
     * @return bool TRUE if condition evaluates successfully
     */
    protected function evaluateDisplayCondition($displayCondition, array $record = [], $flexformContext = false, $recursionLevel = 0)
    {
        if ($recursionLevel > 99) {
            // This should not happen, treat as misconfiguration
            return true;
        }
        if (!is_array($displayCondition)) {
            // DisplayCondition is not an array - just get its value
            $result = $this->evaluateSingleDisplayCondition($displayCondition, $record, $flexformContext);
        } else {
            // Multiple conditions given as array ('AND|OR' => condition array)
            $conditionEvaluations = [
                'AND' => [],
                'OR' => [],
            ];
            foreach ($displayCondition as $logicalOperator => $groupedDisplayConditions) {
                $logicalOperator = strtoupper($logicalOperator);
                if (($logicalOperator !== 'AND' && $logicalOperator !== 'OR') || !is_array($groupedDisplayConditions)) {
                    // Invalid line. Skip it.
                    continue;
                } else {
                    foreach ($groupedDisplayConditions as $key => $singleDisplayCondition) {
                        $key = strtoupper($key);
                        if (($key === 'AND' || $key === 'OR') && is_array($singleDisplayCondition)) {
                            // Recursion statement: condition is 'AND' or 'OR' and is pointing to an array (should be conditions again)
                            $conditionEvaluations[$logicalOperator][] = $this->evaluateDisplayCondition(
                                [$key => $singleDisplayCondition],
                                $record,
                                $flexformContext,
                                $recursionLevel + 1
                            );
                        } else {
                            // Condition statement: collect evaluation of this single condition.
                            $conditionEvaluations[$logicalOperator][] = $this->evaluateSingleDisplayCondition(
                                $singleDisplayCondition,
                                $record,
                                $flexformContext
                            );
                        }
                    }
                }
            }
            if (!empty($conditionEvaluations['OR']) && in_array(true, $conditionEvaluations['OR'], true)) {
                // There are OR conditions and at least one of them is TRUE
                $result = true;
            } elseif (!empty($conditionEvaluations['AND']) && !in_array(false, $conditionEvaluations['AND'], true)) {
                // There are AND conditions and none of them is FALSE
                $result = true;
            } elseif (!empty($conditionEvaluations['OR']) || !empty($conditionEvaluations['AND'])) {
                // There are some conditions. But no OR was TRUE and at least one AND was FALSE
                $result = false;
            } else {
                // There are no proper conditions - misconfiguration. Return TRUE.
                $result = true;
            }
        }
        return $result;
    }

    /**
     * Evaluates the provided condition and returns TRUE if the form
     * element should be displayed.
     *
     * The condition string is separated by colons and the first part
     * indicates what type of evaluation should be performed.
     *
     * @param string $displayCondition
     * @param array $record
     * @param bool $flexformContext
     * @return bool
     * @see evaluateDisplayCondition()
     */
    protected function evaluateSingleDisplayCondition($displayCondition, array $record = [], $flexformContext = false)
    {
        $result = false;
        list($matchType, $condition) = explode(':', $displayCondition, 2);
        switch ($matchType) {
            case 'EXT':
                $result = $this->matchExtensionCondition($condition);
                break;
            case 'FIELD':
                $result = $this->matchFieldCondition($condition, $record, $flexformContext);
                break;
            case 'HIDE_FOR_NON_ADMINS':
                $result = $this->matchHideForNonAdminsCondition();
                break;
            case 'HIDE_L10N_SIBLINGS':
                $result = $this->matchHideL10nSiblingsCondition();
                break;
            case 'REC':
                $result = $this->matchRecordCondition($condition, $record);
                break;
            case 'VERSION':
                $result = $this->matchVersionCondition($condition, $record);
                break;
            case 'USER':
                $result = $this->matchUserCondition($condition, $record);
                break;
        }
        return $result;
    }

    /**
     * Evaluates conditions concerning extensions
     *
     * Example:
     * "EXT:saltedpasswords:LOADED:TRUE" => TRUE, if extension saltedpasswords is loaded.
     *
     * @param string $condition
     * @return bool
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8 - Do not use EXT:LOADED display conditions any longer
     */
    protected function matchExtensionCondition($condition)
    {
        GeneralUtility::logDeprecatedFunction();
        $result = false;
        list($extensionKey, $operator, $operand) = explode(':', $condition, 3);
        if ($operator === 'LOADED') {
            if (strtoupper($operand) === 'TRUE') {
                $result = ExtensionManagementUtility::isLoaded($extensionKey);
            } elseif (strtoupper($operand) === 'FALSE') {
                $result = !ExtensionManagementUtility::isLoaded($extensionKey);
            }
        }
        return $result;
    }

    /**
     * Evaluates conditions concerning a field of the current record.
     * Requires a record set via ->setRecord()
     *
     * Example:
     * "FIELD:sys_language_uid:>:0" => TRUE, if the field 'sys_language_uid' is greater than 0
     *
     * @param string $condition
     * @param array $record
     * @param bool $flexformContext
     * @return bool
     */
    protected function matchFieldCondition($condition, $record, $flexformContext = false)
    {
        list($fieldName, $operator, $operand) = explode(':', $condition, 3);
        if ($flexformContext) {
            if (strpos($fieldName, 'parentRec.') !== false) {
                $fieldNameParts = explode('.', $fieldName, 2);
                $fieldValue = $record['parentRec'][$fieldNameParts[1]];
            } else {
                $fieldValue = $record[$fieldName]['vDEF'];
            }
        } else {
            $fieldValue = $record[$fieldName];
        }
        $result = false;
        switch ($operator) {
            case 'REQ':
                if (is_array($fieldValue) && count($fieldValue) <= 1) {
                    $fieldValue = array_shift($fieldValue);
                }
                if (strtoupper($operand) === 'TRUE') {
                    $result = (bool)$fieldValue;
                } else {
                    $result = !$fieldValue;
                }
                break;
            case '>':
                if (is_array($fieldValue) && count($fieldValue) <= 1) {
                    $fieldValue = array_shift($fieldValue);
                }
                $result = $fieldValue > $operand;
                break;
            case '<':
                if (is_array($fieldValue) && count($fieldValue) <= 1) {
                    $fieldValue = array_shift($fieldValue);
                }
                $result = $fieldValue < $operand;
                break;
            case '>=':
                if (is_array($fieldValue) && count($fieldValue) <= 1) {
                    $fieldValue = array_shift($fieldValue);
                }
                $result = $fieldValue >= $operand;
                break;
            case '<=':
                if (is_array($fieldValue) && count($fieldValue) <= 1) {
                    $fieldValue = array_shift($fieldValue);
                }
                $result = $fieldValue <= $operand;
                break;
            case '-':
            case '!-':
                if (is_array($fieldValue) && count($fieldValue) <= 1) {
                    $fieldValue = array_shift($fieldValue);
                }
                list($minimum, $maximum) = explode('-', $operand);
                $result = $fieldValue >= $minimum && $fieldValue <= $maximum;
                if ($operator[0] === '!') {
                    $result = !$result;
                }
                break;
            case '=':
            case '!=':
                if (is_array($fieldValue) && count($fieldValue) <= 1) {
                    $fieldValue = array_shift($fieldValue);
                }
                $result = $fieldValue == $operand;
                if ($operator[0] === '!') {
                    $result = !$result;
                }
                break;
            case 'IN':
            case '!IN':
                if (is_array($fieldValue)) {
                    $result = count(array_intersect($fieldValue, explode(',', $operand))) > 0;
                } else {
                    $result = GeneralUtility::inList($operand, $fieldValue);
                }
                if ($operator[0] === '!') {
                    $result = !$result;
                }
                break;
            case 'BIT':
            case '!BIT':
                $result = (bool)((int)$fieldValue & $operand);
                if ($operator[0] === '!') {
                    $result = !$result;
                }
                break;
        }
        return $result;
    }

    /**
     * Evaluates TRUE if current backend user is an admin.
     *
     * @return bool
     */
    protected function matchHideForNonAdminsCondition()
    {
        return (bool)$this->getBackendUser()->isAdmin();
    }

    /**
     * Evaluates whether the field is a value for the default language.
     * Works only for <langChildren>=1, otherwise it has no effect.
     *
     * @return bool
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    protected function matchHideL10nSiblingsCondition()
    {
        GeneralUtility::deprecationLog('HIDE_L10N_SIBLINGS in Flexform display conditions has been deprecated with TYPO3 CMS 7 and will be removed with TYPO3 CMS 8.');
        return true;
    }

    /**
     * Evaluates conditions concerning the status of the current record.
     * Requires a record set via ->setRecord()
     *
     * Example:
     * "REC:NEW:FALSE" => TRUE, if the record is already persisted (has a uid > 0)
     *
     * @param string $condition
     * @param array $record
     * @return bool
     */
    protected function matchRecordCondition($condition, $record)
    {
        $result = false;
        list($operator, $operand) = explode(':', $condition, 2);
        if ($operator === 'NEW') {
            if (strtoupper($operand) === 'TRUE') {
                $result = !((int)$record['uid'] > 0);
            } elseif (strtoupper($operand) === 'FALSE') {
                $result = ((int)$record['uid'] > 0);
            }
        }
        return $result;
    }

    /**
     * Evaluates whether the current record is versioned.
     * Requires a record set via ->setRecord()
     *
     * @param string $condition
     * @param array $record
     * @return bool
     */
    protected function matchVersionCondition($condition, $record)
    {
        $result = false;
        list($operator, $operand) = explode(':', $condition, 2);
        if ($operator === 'IS') {
            $isNewRecord = !((int)$record['uid'] > 0);
            // Detection of version can be done be detecting the workspace of the user
            $isUserInWorkspace = $this->getBackendUser()->workspace > 0;
            if ((int)$record['pid'] === -1 || (int)$record['_ORIG_pid'] === -1) {
                $isRecordDetectedAsVersion = true;
            } else {
                $isRecordDetectedAsVersion = false;
            }
            // New records in a workspace are not handled as a version record
            // if it's no new version, we detect versions like this:
            // -- if user is in workspace: always TRUE
            // -- if editor is in live ws: only TRUE if pid == -1
            $isVersion = ($isUserInWorkspace || $isRecordDetectedAsVersion) && !$isNewRecord;
            if (strtoupper($operand) === 'TRUE') {
                $result = $isVersion;
            } elseif (strtoupper($operand) === 'FALSE') {
                $result = !$isVersion;
            }
        }
        return $result;
    }

    /**
     * Evaluates via the referenced user-defined method
     *
     * @param string $condition
     * @param array $record
     * @return bool
     */
    protected function matchUserCondition($condition, $record)
    {
        $conditionParameters = explode(':', $condition);
        $userFunction = array_shift($conditionParameters);

        $parameter = [
            'record' => $record,
            'flexformValueKey' => 'vDEF',
            'conditionParameters' => $conditionParameters
        ];

        return (bool)GeneralUtility::callUserFunction($userFunction, $parameter, $this);
    }

    /**
     * Get current backend user
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
