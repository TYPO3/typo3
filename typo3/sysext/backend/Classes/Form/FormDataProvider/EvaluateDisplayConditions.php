<?php
declare(strict_types = 1);
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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

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
     * Strategy of the parser is to first find all displayCond in given tca
     * and within all type=flex fields to parse them into an array. This condition
     * array contains all information to evaluate that condition in a second
     * step that - depending on evaluation result - then throws away or keeps the field.
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result): array
    {
        $result = $this->parseDisplayConditions($result);
        $result = $this->evaluateConditions($result);
        return $result;
    }

    /**
     * Find all 'displayCond' in TCA and flex forms and substitute them with an
     * array representation that contains all relevant data to
     * evaluate the condition later. For "FIELD" conditions the helper methods
     * findFieldValue() is used to find the value of the referenced field to put
     * that value into the returned array, too. This is important since the referenced
     * field is "relative" to the position of the field that has the display condition.
     * For instance, "FIELD:aField:=:foo" within a flex form field references a field
     * value from the same sheet, and there are many more complex scenarios to resolve.
     *
     * @param array $result Incoming result array
     * @throws \RuntimeException
     * @return array Modified result array with all displayCond parsed into arrays
     */
    protected function parseDisplayConditions(array $result): array
    {
        $flexColumns = [];
        foreach ($result['processedTca']['columns'] as $columnName => $columnConfiguration) {
            if (isset($columnConfiguration['config']['type']) && $columnConfiguration['config']['type'] === 'flex') {
                $flexColumns[$columnName] = $columnConfiguration;
            }
            if (!isset($columnConfiguration['displayCond'])) {
                continue;
            }
            $result['processedTca']['columns'][$columnName]['displayCond'] = $this->parseConditionRecursive(
                $columnConfiguration['displayCond'],
                $result['databaseRow']
            );
        }

        foreach ($flexColumns as $columnName => $flexColumn) {
            $sheetNameFieldNames = [];
            foreach ($flexColumn['config']['ds']['sheets'] as $sheetName => $sheetConfiguration) {
                // Create a list of all sheet names with field names combinations for later 'sheetName.fieldName' lookups
                // 'one.sheet.one.field' as key, with array of "sheetName" and "fieldName" as value
                if (isset($sheetConfiguration['ROOT']['el']) && is_array($sheetConfiguration['ROOT']['el'])) {
                    foreach ($sheetConfiguration['ROOT']['el'] as $flexElementName => $flexElementConfiguration) {
                        // section container have no value in its own
                        if (isset($flexElementConfiguration['type']) && $flexElementConfiguration['type'] === 'array'
                            && isset($flexElementConfiguration['section']) && $flexElementConfiguration['section'] == 1
                        ) {
                            continue;
                        }
                        $combinedKey = $sheetName . '.' . $flexElementName;
                        if (array_key_exists($combinedKey, $sheetNameFieldNames)) {
                            throw new \RuntimeException(
                                'Ambiguous sheet name and field name combination: Sheet "' . $sheetNameFieldNames[$combinedKey]['sheetName']
                                . '" with field name "' . $sheetNameFieldNames[$combinedKey]['fieldName'] . '" overlaps with sheet "'
                                . $sheetName . '" and field name "' . $flexElementName . '". Do not do that.',
                                1481483061
                            );
                        }
                        $sheetNameFieldNames[$combinedKey] = [
                            'sheetName' => $sheetName,
                            'fieldName' => $flexElementName,
                        ];
                    }
                }
            }
            foreach ($flexColumn['config']['ds']['sheets'] as $sheetName => $sheetConfiguration) {
                if (isset($sheetConfiguration['ROOT']['displayCond'])) {
                    // Condition on a flex sheet
                    $flexContext = [
                        'context' => 'flexSheet',
                        'sheetNameFieldNames' => $sheetNameFieldNames,
                        'currentSheetName' => $sheetName,
                        'flexFormRowData' => $result['databaseRow'][$columnName] ?? null,
                    ];
                    $parsedDisplayCondition = $this->parseConditionRecursive(
                        $sheetConfiguration['ROOT']['displayCond'],
                        $result['databaseRow'],
                        $flexContext
                    );
                    $result['processedTca']['columns'][$columnName]['config']['ds']
                        ['sheets'][$sheetName]['ROOT']['displayCond']
                        = $parsedDisplayCondition;
                }
                if (isset($sheetConfiguration['ROOT']['el']) && is_array($sheetConfiguration['ROOT']['el'])) {
                    foreach ($sheetConfiguration['ROOT']['el'] as $flexElementName => $flexElementConfiguration) {
                        if (isset($flexElementConfiguration['displayCond'])) {
                            // Condition on a flex element
                            $flexContext = [
                                'context' => 'flexField',
                                'sheetNameFieldNames' => $sheetNameFieldNames,
                                'currentSheetName' => $sheetName,
                                'currentFieldName' => $flexElementName,
                                'flexFormDataStructure' => $result['processedTca']['columns'][$columnName]['config']['ds'],
                                'flexFormRowData' => $result['databaseRow'][$columnName] ?? null,
                            ];
                            $parsedDisplayCondition = $this->parseConditionRecursive(
                                $flexElementConfiguration['displayCond'],
                                $result['databaseRow'],
                                $flexContext
                            );
                            $result['processedTca']['columns'][$columnName]['config']['ds']
                                ['sheets'][$sheetName]['ROOT']
                                ['el'][$flexElementName]['displayCond']
                                = $parsedDisplayCondition;
                        }
                        if (isset($flexElementConfiguration['type']) && $flexElementConfiguration['type'] === 'array'
                            && isset($flexElementConfiguration['section']) && $flexElementConfiguration['section'] == 1
                            && isset($flexElementConfiguration['children']) && is_array($flexElementConfiguration['children'])
                        ) {
                            // Conditions on flex container section elements
                            foreach ($flexElementConfiguration['children'] as $containerIdentifier => $containerElements) {
                                if (isset($containerElements['el']) && is_array($containerElements['el'])) {
                                    foreach ($containerElements['el'] as $containerElementName => $containerElementConfiguration) {
                                        if (isset($containerElementConfiguration['displayCond'])) {
                                            $flexContext = [
                                                'context' => 'flexContainerElement',
                                                'sheetNameFieldNames' => $sheetNameFieldNames,
                                                'currentSheetName' => $sheetName,
                                                'currentFieldName' => $flexElementName,
                                                'currentContainerIdentifier' => $containerIdentifier,
                                                'currentContainerElementName' => $containerElementName,
                                                'flexFormDataStructure' => $result['processedTca']['columns'][$columnName]['config']['ds'],
                                                'flexFormRowData' => $result['databaseRow'][$columnName],
                                            ];
                                            $parsedDisplayCondition = $this->parseConditionRecursive(
                                                $containerElementConfiguration['displayCond'],
                                                $result['databaseRow'],
                                                $flexContext
                                            );
                                            $result['processedTca']['columns'][$columnName]['config']['ds']
                                                ['sheets'][$sheetName]['ROOT']
                                                ['el'][$flexElementName]
                                                ['children'][$containerIdentifier]
                                                ['el'][$containerElementName]['displayCond']
                                                = $parsedDisplayCondition;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Parse a condition into an array representation and validate syntax. Handles nested conditions combined with AND and OR.
     * Calls itself recursive for nesting and logically combined conditions.
     *
     * @param mixed $condition Either an array with multiple conditions combined with AND or OR, or a single condition string
     * @param array $databaseRow Incoming full database row
     * @param array $flexContext Detailed flex context if display condition is within a flex field, needed to determine field value for "FIELD" conditions
     * @throws \RuntimeException
     * @return array Array representation of that condition, see unit tests for details on syntax
     */
    protected function parseConditionRecursive($condition, array $databaseRow, array $flexContext = []): array
    {
        $conditionArray = [];
        if (is_string($condition)) {
            $conditionArray = $this->parseSingleConditionString($condition, $databaseRow, $flexContext);
        } elseif (is_array($condition)) {
            foreach ($condition as $logicalOperator => $groupedDisplayConditions) {
                $logicalOperator = strtoupper(is_string($logicalOperator) ? $logicalOperator : '');
                if (($logicalOperator !== 'AND' && $logicalOperator !== 'OR') || !is_array($groupedDisplayConditions)) {
                    throw new \RuntimeException(
                        'Multiple conditions must have boolean operator "OR" or "AND", "' . $logicalOperator . '" given.',
                        1481380393
                    );
                }
                if (count($groupedDisplayConditions) < 2) {
                    throw new \RuntimeException(
                        'With multiple conditions combined by "' . $logicalOperator . '", there must be at least two sub conditions',
                        1481464101
                    );
                }
                $conditionArray = [
                    'type' => $logicalOperator,
                    'subConditions' => [],
                ];
                foreach ($groupedDisplayConditions as $key => $singleDisplayCondition) {
                    $key = strtoupper((string)$key);
                    if (($key === 'AND' || $key === 'OR') && is_array($singleDisplayCondition)) {
                        // Recursion statement: condition is 'AND' or 'OR' and is pointing to an array (should be conditions again)
                        $conditionArray['subConditions'][] = $this->parseConditionRecursive(
                            [$key => $singleDisplayCondition],
                            $databaseRow,
                            $flexContext
                        );
                    } else {
                        $conditionArray['subConditions'][] = $this->parseConditionRecursive(
                            $singleDisplayCondition,
                            $databaseRow,
                            $flexContext
                        );
                    }
                }
            }
        } else {
            throw new \RuntimeException(
                'Condition must be either an array with sub conditions or a single condition string, type ' . gettype($condition) . ' given.',
                1481381058
            );
        }
        return $conditionArray;
    }

    /**
     * Parse a single condition string into pieces, validate them and return
     * an array representation.
     *
     * @param string $conditionString Given condition string like "VERSION:IS:true"
     * @param array $databaseRow Incoming full database row
     * @param array $flexContext Detailed flex context if display condition is within a flex field, needed to determine field value for "FIELD" conditions
     * @return array Validated name array, example: [ type="VERSION", isVersion="true" ]
     * @throws \RuntimeException
     */
    protected function parseSingleConditionString(string $conditionString, array $databaseRow, array $flexContext = []): array
    {
        $conditionArray = GeneralUtility::trimExplode(':', $conditionString, false, 4);
        $namedConditionArray = [
            'type' => $conditionArray[0],
        ];
        switch ($namedConditionArray['type']) {
            case 'FIELD':
                if (empty($conditionArray[1])) {
                    throw new \RuntimeException(
                        'Field condition "' . $conditionString . '" must have a field name as second part, none given.'
                        . 'Example: "FIELD:myField:=:myValue"',
                        1481385695
                    );
                }
                $fieldName = $conditionArray[1];
                $allowedOperators = ['REQ', '>', '<', '>=', '<=', '-', '!-', '=', '!=', 'IN', '!IN', 'BIT', '!BIT'];
                if (empty($conditionArray[2]) || !in_array($conditionArray[2], $allowedOperators)) {
                    throw new \RuntimeException(
                        'Field condition "' . $conditionString . '" must have a valid operator as third part, non or invalid one given.'
                        . ' Valid operators are: "' . implode('", "', $allowedOperators) . '".'
                        . ' Example: "FIELD:myField:=:4"',
                        1481386239
                    );
                }
                $namedConditionArray['operator'] = $conditionArray[2];
                if (!isset($conditionArray[3])) {
                    throw new \RuntimeException(
                        'Field condition "' . $conditionString . '" must have an operand as fourth part, none given.'
                        . ' Example: "FIELD:myField:=:4"',
                        1481401543
                    );
                }
                $operand = $conditionArray[3];
                if ($namedConditionArray['operator'] === 'REQ') {
                    $operand = strtolower($operand);
                    if ($operand === 'true') {
                        $namedConditionArray['operand'] = true;
                    } elseif ($operand === 'false') {
                        $namedConditionArray['operand'] = false;
                    } else {
                        throw new \RuntimeException(
                            'Field condition "' . $conditionString . '" must have "true" or "false" as fourth part.'
                            . ' Example: "FIELD:myField:REQ:true',
                            1481401892
                        );
                    }
                } elseif (in_array($namedConditionArray['operator'], ['>', '<', '>=', '<=', 'BIT', '!BIT'])) {
                    if (!MathUtility::canBeInterpretedAsInteger($operand)) {
                        throw new \RuntimeException(
                            'Field condition "' . $conditionString . '" with comparison operator ' . $namedConditionArray['operator']
                            . ' must have a number as fourth part, ' . $operand . ' given. Example: "FIELD:myField:>:42"',
                            1481456806
                        );
                    }
                    $namedConditionArray['operand'] = (int)$operand;
                } elseif ($namedConditionArray['operator'] === '-' || $namedConditionArray['operator'] === '!-') {
                    list($minimum, $maximum) = GeneralUtility::trimExplode('-', $operand);
                    if (!MathUtility::canBeInterpretedAsInteger($minimum) || !MathUtility::canBeInterpretedAsInteger($maximum)) {
                        throw new \RuntimeException(
                            'Field condition "' . $conditionString . '" with comparison operator ' . $namedConditionArray['operator']
                            . ' must have two numbers as fourth part, separated by dash, ' . $operand . ' given. Example: "FIELD:myField:-:1-3"',
                            1481457277
                        );
                    }
                    $namedConditionArray['operand'] = '';
                    $namedConditionArray['min'] = (int)$minimum;
                    $namedConditionArray['max'] = (int)$maximum;
                } elseif ($namedConditionArray['operator'] === 'IN' || $namedConditionArray['operator'] === '!IN'
                    || $namedConditionArray['operator'] === '=' || $namedConditionArray['operator'] === '!='
                ) {
                    $namedConditionArray['operand'] = $operand;
                }
                $namedConditionArray['fieldValue'] = $this->findFieldValue($fieldName, $databaseRow, $flexContext);
                break;
            case 'HIDE_FOR_NON_ADMINS':
                break;
            case 'REC':
                if (empty($conditionArray[1]) || $conditionArray[1] !== 'NEW') {
                    throw new \RuntimeException(
                        'Record condition "' . $conditionString . '" must contain "NEW" keyword: either "REC:NEW:true" or "REC:NEW:false"',
                        1481384784
                    );
                }
                if (empty($conditionArray[2])) {
                    throw new \RuntimeException(
                        'Record condition "' . $conditionString . '" must have an operand "true" or "false", none given. Example: "REC:NEW:true"',
                        1481384947
                    );
                }
                $operand = strtolower($conditionArray[2]);
                if ($operand === 'true') {
                    $namedConditionArray['isNew'] = true;
                } elseif ($operand === 'false') {
                    $namedConditionArray['isNew'] = false;
                } else {
                    throw new \RuntimeException(
                        'Record condition "' . $conditionString . '" must have an operand "true" or "false, example "REC:NEW:true", given: ' . $operand,
                        1481385173
                    );
                }
                // Programming error: There must be a uid available, other data providers should have taken care of that already
                if (!array_key_exists('uid', $databaseRow)) {
                    throw new \RuntimeException(
                        'Required [\'databaseRow\'][\'uid\'] not found in data array',
                        1481467208
                    );
                }
                // May contain "NEW123..."
                $namedConditionArray['uid'] = $databaseRow['uid'];
                break;
            case 'VERSION':
                if (empty($conditionArray[1]) || $conditionArray[1] !== 'IS') {
                    throw new \RuntimeException(
                        'Version condition "' . $conditionString . '" must contain "IS" keyword: either "VERSION:IS:false" or "VERSION:IS:true"',
                        1481383660
                    );
                }
                if (empty($conditionArray[2])) {
                    throw new \RuntimeException(
                        'Version condition "' . $conditionString . '" must have an operand "true" or "false", none given. Example: "VERSION:IS:true',
                        1481383888
                    );
                }
                $operand = strtolower($conditionArray[2]);
                if ($operand === 'true') {
                    $namedConditionArray['isVersion'] = true;
                } elseif ($operand === 'false') {
                    $namedConditionArray['isVersion'] = false;
                } else {
                    throw new \RuntimeException(
                        'Version condition "' . $conditionString . '" must have a "true" or "false" operand, example "VERSION:IS:true", given: ' . $operand,
                        1481384123
                    );
                }
                // Programming error: There must be a uid available, other data providers should have taken care of that already
                if (!array_key_exists('uid', $databaseRow)) {
                    throw new \RuntimeException(
                        'Required [\'databaseRow\'][\'uid\'] not found in data array',
                        1481469854
                    );
                }
                $namedConditionArray['uid'] = $databaseRow['uid'];
                if (array_key_exists('pid', $databaseRow)) {
                    $namedConditionArray['pid'] = $databaseRow['pid'];
                }
                if (array_key_exists('_ORIG_pid', $databaseRow)) {
                    $namedConditionArray['_ORIG_pid'] = $databaseRow['_ORIG_pid'];
                }
                break;
            case 'USER':
                if (empty($conditionArray[1])) {
                    throw new \RuntimeException(
                        'User function condition "' . $conditionString . '" must have a user function defined a second part, none given.'
                        . ' Correct format is USER:\My\User\Func->match:more:arguments,'
                        . ' given: ' . $conditionString,
                        1481382954
                    );
                }
                $namedConditionArray['function'] = $conditionArray[1];
                array_shift($conditionArray);
                array_shift($conditionArray);
                $parameters = count($conditionArray) < 2
                    ? $conditionArray
                    : array_merge(
                        [$conditionArray[0]],
                        GeneralUtility::trimExplode(':', $conditionArray[1])
                    );
                $namedConditionArray['parameters'] = $parameters;
                $namedConditionArray['record'] = $databaseRow;
                $namedConditionArray['flexContext'] = $flexContext;
                break;
            default:
                throw new \RuntimeException(
                    'Unknown condition rule type "' . $namedConditionArray['type'] . '" with display condition "' . $conditionString . '"".',
                    1481381950
                );
        }
        return $namedConditionArray;
    }

    /**
     * Find field value the condition refers to for "FIELD:" conditions.  For "normal" TCA fields this is the value of
     * a "neighbor" field, but in flex form context it can be prepended with a sheet name. The method sorts out the
     * details and returns the current field value.
     *
     * @param string $givenFieldName The full name used in displayCond. Can have sheet names included in flex context
     * @param array $databaseRow Incoming database row values
     * @param array $flexContext Detailed flex context if display condition is within a flex field, needed to determine field value for "FIELD" conditions
     * @throws \RuntimeException
     * @return mixed The current field value from database row or a deeper flex form structure field.
     */
    protected function findFieldValue(string $givenFieldName, array $databaseRow, array $flexContext = [])
    {
        $fieldValue = null;

        // Early return for "normal" tca fields
        if (empty($flexContext)) {
            if (array_key_exists($givenFieldName, $databaseRow)) {
                $fieldValue = $databaseRow[$givenFieldName];
            }
            return $fieldValue;
        }
        if ($flexContext['context'] === 'flexSheet') {
            // A display condition on a flex form sheet. Relatively simple: fieldName is either
            // "parentRec.fieldName" pointing to a databaseRow field name, or "sheetName.fieldName" pointing
            // to a field value from a neighbor field.
            if (strpos($givenFieldName, 'parentRec.') === 0) {
                $fieldName = substr($givenFieldName, 10);
                if (array_key_exists($fieldName, $databaseRow)) {
                    $fieldValue = $databaseRow[$fieldName];
                }
            } else {
                if (array_key_exists($givenFieldName, $flexContext['sheetNameFieldNames'])) {
                    if ($flexContext['currentSheetName'] === $flexContext['sheetNameFieldNames'][$givenFieldName]['sheetName']) {
                        throw new \RuntimeException(
                            'Configuring displayCond to "' . $givenFieldName . '" on flex form sheet "'
                            . $flexContext['currentSheetName'] . '" referencing a value from the same sheet does not make sense.',
                            1481485705
                        );
                    }
                }
                $sheetName = $flexContext['sheetNameFieldNames'][$givenFieldName]['sheetName'] ?? null;
                $fieldName = $flexContext['sheetNameFieldNames'][$givenFieldName]['fieldName'] ?? null;
                if (!isset($flexContext['flexFormRowData']['data'][$sheetName]['lDEF'][$fieldName]['vDEF'])) {
                    throw new \RuntimeException(
                        'Flex form displayCond on sheet "' . $flexContext['currentSheetName'] . '" references field "' . $fieldName
                        . '" of sheet "' . $sheetName . '", but that field does not exist in current data structure',
                        1481488492
                    );
                }
                $fieldValue = $flexContext['flexFormRowData']['data'][$sheetName]['lDEF'][$fieldName]['vDEF'];
            }
        } elseif ($flexContext['context'] === 'flexField') {
            // A display condition on a flex field. Handle "parentRec." similar to sheet conditions,
            // get a list of "local" field names and see if they are used as reference, else see if a
            // "sheetName.fieldName" field reference is given
            if (strpos($givenFieldName, 'parentRec.') === 0) {
                $fieldName = substr($givenFieldName, 10);
                if (array_key_exists($fieldName, $databaseRow)) {
                    $fieldValue = $databaseRow[$fieldName];
                }
            } else {
                $listOfLocalFlexFieldNames = array_keys(
                    $flexContext['flexFormDataStructure']['sheets'][$flexContext['currentSheetName']]['ROOT']['el']
                );
                if (in_array($givenFieldName, $listOfLocalFlexFieldNames, true)) {
                    // Condition references field name of the same sheet
                    $sheetName = $flexContext['currentSheetName'];
                    if (!isset($flexContext['flexFormRowData']['data'][$sheetName]['lDEF'][$givenFieldName]['vDEF'])) {
                        throw new \RuntimeException(
                            'Flex form displayCond on field "' . $flexContext['currentFieldName'] . '" on flex form sheet "'
                            . $flexContext['currentSheetName'] . '" references field "' . $givenFieldName . '", but a field value'
                            . ' does not exist in this sheet',
                            1481492953
                        );
                    }
                    $fieldValue = $flexContext['flexFormRowData']['data'][$sheetName]['lDEF'][$givenFieldName]['vDEF'];
                } elseif (in_array($givenFieldName, array_keys($flexContext['sheetNameFieldNames'], true))) {
                    // Condition references field name including a sheet name
                    $sheetName = $flexContext['sheetNameFieldNames'][$givenFieldName]['sheetName'];
                    $fieldName = $flexContext['sheetNameFieldNames'][$givenFieldName]['fieldName'];
                    $fieldValue = $flexContext['flexFormRowData']['data'][$sheetName]['lDEF'][$fieldName]['vDEF'];
                } else {
                    throw new \RuntimeException(
                        'Flex form displayCond on field "' . $flexContext['currentFieldName'] . '" on flex form sheet "'
                        . $flexContext['currentSheetName'] . '" references a field or field / sheet combination "'
                        . $givenFieldName . '" that might be defined in given data structure but is not found in data values.',
                        1481496170
                    );
                }
            }
        } elseif ($flexContext['context'] === 'flexContainerElement') {
            // A display condition on a flex form section container element. Handle "parentRec.", compare to a
            // list of local field names, compare to a list of field names from same sheet, compare to a list
            // of sheet fields from other sheets.
            if (strpos($givenFieldName, 'parentRec.') === 0) {
                $fieldName = substr($givenFieldName, 10);
                if (array_key_exists($fieldName, $databaseRow)) {
                    $fieldValue = $databaseRow[$fieldName];
                }
            } else {
                $currentSheetName = $flexContext['currentSheetName'];
                $currentFieldName = $flexContext['currentFieldName'];
                $currentContainerIdentifier = $flexContext['currentContainerIdentifier'];
                $currentContainerElementName = $flexContext['currentContainerElementName'];
                $listOfLocalContainerElementNames = array_keys(
                    $flexContext['flexFormDataStructure']['sheets'][$currentSheetName]['ROOT']
                        ['el'][$currentFieldName]
                        ['children'][$currentContainerIdentifier]
                        ['el']
                );
                $listOfLocalContainerElementNamesWithSheetName = [];
                foreach ($listOfLocalContainerElementNames as $aContainerElementName) {
                    $listOfLocalContainerElementNamesWithSheetName[$currentSheetName . '.' . $aContainerElementName] = [
                        'containerElementName' => $aContainerElementName,
                    ];
                }
                $listOfLocalFlexFieldNames = array_keys(
                    $flexContext['flexFormDataStructure']['sheets'][$currentSheetName]['ROOT']['el']
                );
                if (in_array($givenFieldName, $listOfLocalContainerElementNames, true)) {
                    // Condition references field of same container instance
                    $containerType = current(array_keys(
                        $flexContext['flexFormRowData']['data'][$currentSheetName]
                            ['lDEF'][$currentFieldName]
                            ['el'][$currentContainerIdentifier]
                    ));
                    $fieldValue = $flexContext['flexFormRowData']['data'][$currentSheetName]
                        ['lDEF'][$currentFieldName]
                        ['el'][$currentContainerIdentifier]
                        [$containerType]
                        ['el'][$givenFieldName]['vDEF'];
                } elseif (in_array($givenFieldName, array_keys($listOfLocalContainerElementNamesWithSheetName, true))) {
                    // Condition references field name of same container instance and has sheet name included
                    $containerType = current(array_keys(
                        $flexContext['flexFormRowData']['data'][$currentSheetName]
                        ['lDEF'][$currentFieldName]
                        ['el'][$currentContainerIdentifier]
                    ));
                    $fieldName = $listOfLocalContainerElementNamesWithSheetName[$givenFieldName]['containerElementName'];
                    $fieldValue = $flexContext['flexFormRowData']['data'][$currentSheetName]
                        ['lDEF'][$currentFieldName]
                        ['el'][$currentContainerIdentifier]
                        [$containerType]
                        ['el'][$fieldName]['vDEF'];
                } elseif (in_array($givenFieldName, $listOfLocalFlexFieldNames, true)) {
                    // Condition reference field name of sheet this section container is in
                    $fieldValue = $flexContext['flexFormRowData']['data'][$currentSheetName]
                        ['lDEF'][$givenFieldName]['vDEF'];
                } elseif (in_array($givenFieldName, array_keys($flexContext['sheetNameFieldNames'], true))) {
                    $sheetName = $flexContext['sheetNameFieldNames'][$givenFieldName]['sheetName'];
                    $fieldName = $flexContext['sheetNameFieldNames'][$givenFieldName]['fieldName'];
                    $fieldValue = $flexContext['flexFormRowData']['data'][$sheetName]['lDEF'][$fieldName]['vDEF'];
                } else {
                    $containerType = current(array_keys(
                        $flexContext['flexFormRowData']['data'][$currentSheetName]
                        ['lDEF'][$currentFieldName]
                        ['el'][$currentContainerIdentifier]
                    ));
                    throw new \RuntimeException(
                        'Flex form displayCond on section container field "' . $currentContainerElementName . '" of container type "'
                        . $containerType . '" on flex form sheet "'
                        . $flexContext['currentSheetName'] . '" references a field or field / sheet combination "'
                        . $givenFieldName . '" that might be defined in given data structure but is not found in data values.',
                        1481634649
                    );
                }
            }
        }

        return $fieldValue;
    }

    /**
     * Loop through TCA, find prepared conditions and evaluate them. Delete either the
     * field itself if the condition did not match, or the 'displayCond' in TCA.
     *
     * @param array $result
     * @return array
     */
    protected function evaluateConditions(array $result): array
    {
        // Evaluate normal tca fields first
        $listOfFlexFieldNames = [];
        foreach ($result['processedTca']['columns'] as $columnName => $columnConfiguration) {
            $conditionResult = true;
            if (isset($columnConfiguration['displayCond'])) {
                $conditionResult = $this->evaluateConditionRecursive($columnConfiguration['displayCond']);
                if (!$conditionResult) {
                    unset($result['processedTca']['columns'][$columnName]);
                } else {
                    // Always unset the whole parsed display condition to save some memory, we're done with them
                    unset($result['processedTca']['columns'][$columnName]['displayCond']);
                }
            }
            // If field was not removed and if it is a flex field, add to list of flex fields to scan
            if ($conditionResult && $columnConfiguration['config']['type'] === 'flex') {
                $listOfFlexFieldNames[] = $columnName;
            }
        }

        // Search for flex fields and evaluate sheet conditions throwing them away if needed
        foreach ($listOfFlexFieldNames as $columnName) {
            $columnConfiguration = $result['processedTca']['columns'][$columnName];
            foreach ($columnConfiguration['config']['ds']['sheets'] as $sheetName => $sheetConfiguration) {
                if (isset($sheetConfiguration['ROOT']['displayCond']) && is_array($sheetConfiguration['ROOT']['displayCond'])) {
                    if (!$this->evaluateConditionRecursive($sheetConfiguration['ROOT']['displayCond'])) {
                        unset($result['processedTca']['columns'][$columnName]['config']['ds']['sheets'][$sheetName]);
                    } else {
                        unset($result['processedTca']['columns'][$columnName]['config']['ds']['sheets'][$sheetName]['ROOT']['displayCond']);
                    }
                }
            }
        }

        // With full sheets gone we loop over display conditions of single fields in flex to throw fields away if needed
        $listOfFlexSectionContainers = [];
        foreach ($listOfFlexFieldNames as $columnName) {
            $columnConfiguration = $result['processedTca']['columns'][$columnName];
            if (is_array($columnConfiguration['config']['ds']['sheets'])) {
                foreach ($columnConfiguration['config']['ds']['sheets'] as $sheetName => $sheetConfiguration) {
                    if (isset($sheetConfiguration['ROOT']['el']) && is_array($sheetConfiguration['ROOT']['el'])) {
                        foreach ($sheetConfiguration['ROOT']['el'] as $flexField => $flexConfiguration) {
                            $conditionResult = true;
                            if (isset($flexConfiguration['displayCond']) && is_array($flexConfiguration['displayCond'])) {
                                $conditionResult = $this->evaluateConditionRecursive($flexConfiguration['displayCond']);
                                if (!$conditionResult) {
                                    unset(
                                        $result['processedTca']['columns'][$columnName]['config']['ds']
                                            ['sheets'][$sheetName]['ROOT']
                                            ['el'][$flexField]
                                    );
                                } else {
                                    unset(
                                        $result['processedTca']['columns'][$columnName]['config']['ds']
                                            ['sheets'][$sheetName]['ROOT']
                                            ['el'][$flexField]['displayCond']
                                    );
                                }
                            }
                            // If it was not removed and if the field is a section container, add it to the section container list
                            if ($conditionResult
                                && isset($flexConfiguration['type']) && $flexConfiguration['type'] === 'array'
                                && isset($flexConfiguration['section']) && $flexConfiguration['section'] == 1
                                && isset($flexConfiguration['children']) && is_array($flexConfiguration['children'])
                            ) {
                                $listOfFlexSectionContainers[] = [
                                    'columnName' => $columnName,
                                    'sheetName' => $sheetName,
                                    'flexField' => $flexField,
                                ];
                            }
                        }
                    }
                }
            }
        }

        // Loop over found section container elements and evaluate their conditions
        foreach ($listOfFlexSectionContainers as $flexSectionContainerPosition) {
            $columnName = $flexSectionContainerPosition['columnName'];
            $sheetName = $flexSectionContainerPosition['sheetName'];
            $flexField = $flexSectionContainerPosition['flexField'];
            $sectionElement = $result['processedTca']['columns'][$columnName]['config']['ds']
                ['sheets'][$sheetName]['ROOT']
                ['el'][$flexField];
            foreach ($sectionElement['children'] as $containerInstanceName => $containerDataStructure) {
                if (isset($containerDataStructure['el']) && is_array($containerDataStructure['el'])) {
                    foreach ($containerDataStructure['el'] as $containerElementName => $containerElementConfiguration) {
                        if (isset($containerElementConfiguration['displayCond']) && is_array($containerElementConfiguration['displayCond'])) {
                            if (!$this->evaluateConditionRecursive($containerElementConfiguration['displayCond'])) {
                                unset(
                                    $result['processedTca']['columns'][$columnName]['config']['ds']
                                        ['sheets'][$sheetName]['ROOT']
                                        ['el'][$flexField]
                                        ['children'][$containerInstanceName]
                                        ['el'][$containerElementName]
                                );
                            } else {
                                unset(
                                    $result['processedTca']['columns'][$columnName]['config']['ds']
                                        ['sheets'][$sheetName]['ROOT']
                                        ['el'][$flexField]
                                        ['children'][$containerInstanceName]
                                        ['el'][$containerElementName]['displayCond']
                                );
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Evaluate a condition recursive by evaluating the single condition type
     *
     * @param array $conditionArray The condition to evaluate, possibly with subConditions for AND and OR types
     * @return bool true if the condition matched
     */
    protected function evaluateConditionRecursive(array $conditionArray): bool
    {
        switch ($conditionArray['type']) {
            case 'AND':
                $result = true;
                foreach ($conditionArray['subConditions'] as $subCondition) {
                    $result = $result && $this->evaluateConditionRecursive($subCondition);
                }
                return $result;
            case 'OR':
                $result = false;
                foreach ($conditionArray['subConditions'] as $subCondition) {
                    $result = $result || $this->evaluateConditionRecursive($subCondition);
                }
                return $result;
            case 'FIELD':
                return $this->matchFieldCondition($conditionArray);
            case 'HIDE_FOR_NON_ADMINS':
                return (bool)$this->getBackendUser()->isAdmin();
            case 'REC':
                return $this->matchRecordCondition($conditionArray);
            case 'VERSION':
                return $this->matchVersionCondition($conditionArray);
            case 'USER':
                return $this->matchUserCondition($conditionArray);
        }
        return false;
    }

    /**
     * Evaluates conditions concerning a field of the current record.
     *
     * Example:
     * "FIELD:sys_language_uid:>:0" => TRUE, if the field 'sys_language_uid' is greater than 0
     *
     * @param array $condition Condition array
     * @return bool
     */
    protected function matchFieldCondition(array $condition): bool
    {
        $operator = $condition['operator'];
        $operand = $condition['operand'];
        $fieldValue = $condition['fieldValue'];
        $result = false;
        switch ($operator) {
            case 'REQ':
                if (is_array($fieldValue) && count($fieldValue) <= 1) {
                    $fieldValue = array_shift($fieldValue);
                }
                if ($operand) {
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
                if ($fieldValue === null) {
                    // If field value is null, this is NOT greater than or equal 0
                    // See test set "Field is not greater than or equal to zero if empty array given"
                    $result = false;
                } else {
                    $result = $fieldValue >= $operand;
                }
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
                $min = $condition['min'];
                $max = $condition['max'];
                $result = $fieldValue >= $min && $fieldValue <= $max;
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
                    $result = count(array_intersect($fieldValue, GeneralUtility::trimExplode(',', $operand))) > 0;
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
     * Evaluates conditions concerning the status of the current record.
     *
     * Example:
     * "REC:NEW:FALSE" => TRUE, if the record is already persisted (has a uid > 0)
     *
     * @param array $condition Condition array
     * @return bool
     */
    protected function matchRecordCondition(array $condition): bool
    {
        if ($condition['isNew']) {
            return !((int)$condition['uid'] > 0);
        }
        return (int)$condition['uid'] > 0;
    }

    /**
     * Evaluates whether the current record is versioned.
     *
     * @param array $condition Condition array
     * @return bool
     */
    protected function matchVersionCondition(array $condition): bool
    {
        $isNewRecord = !((int)$condition['uid'] > 0);
        // Detection of version can be done by detecting the workspace of the user
        $isUserInWorkspace = $this->getBackendUser()->workspace > 0;
        if ((array_key_exists('pid', $condition) && (int)$condition['pid'] === -1)
            || (array_key_exists('_ORIG_pid', $condition) && (int)$condition['_ORIG_pid'] === -1)
        ) {
            $isRecordDetectedAsVersion = true;
        } else {
            $isRecordDetectedAsVersion = false;
        }
        // New records in a workspace are not handled as a version record
        // if it's no new version, we detect versions like this:
        // * if user is in workspace: always TRUE
        // * if editor is in live ws: only TRUE if pid == -1
        $result = ($isUserInWorkspace || $isRecordDetectedAsVersion) && !$isNewRecord;
        if (!$condition['isVersion']) {
            $result = !$result;
        }
        return $result;
    }

    /**
     * Evaluates via the referenced user-defined method
     *
     * @param array $condition Condition array
     * @return bool
     */
    protected function matchUserCondition(array $condition): bool
    {
        $parameter = [
            'record' => $condition['record'],
            'flexContext' => $condition['flexContext'],
            'flexformValueKey' => 'vDEF',
            'conditionParameters' => $condition['parameters'],
        ];
        return (bool)GeneralUtility::callUserFunction($condition['function'], $parameter, $this);
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
