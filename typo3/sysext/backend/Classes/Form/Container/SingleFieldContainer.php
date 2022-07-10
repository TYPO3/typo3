<?php

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

namespace TYPO3\CMS\Backend\Form\Container;

use TYPO3\CMS\Backend\Form\Behavior\UpdateValueOnFieldChange;
use TYPO3\CMS\Backend\Form\InlineStackProcessor;
use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\Bitmask\JsConfirmation;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Container around a "single field".
 *
 * This container is the last one in the chain before processing is handed over to single element classes.
 * If a single field is of type flex or inline, it however creates FlexFormEntryContainer or InlineControlContainer.
 *
 * The container does various checks and processing for a given single fields.
 */
class SingleFieldContainer extends AbstractContainer
{
    /**
     * Entry method
     *
     * @throws \InvalidArgumentException
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $backendUser = $this->getBackendUserAuthentication();
        $resultArray = $this->initializeResultArray();

        $table = $this->data['tableName'];
        $row = $this->data['databaseRow'];
        $fieldName = $this->data['fieldName'];

        $parameterArray = [];
        $parameterArray['fieldConf'] = $this->data['processedTca']['columns'][$fieldName];

        $isOverlay = false;

        // This field decides whether the current record is an overlay (as opposed to being a standalone record)
        // Based on this decision we need to trigger field exclusion or special rendering (like readOnly)
        if (isset($this->data['processedTca']['ctrl']['transOrigPointerField'])
            && is_array($this->data['processedTca']['columns'][$this->data['processedTca']['ctrl']['transOrigPointerField']] ?? null)
        ) {
            $parentValue = $row[$this->data['processedTca']['ctrl']['transOrigPointerField']];
            if (MathUtility::canBeInterpretedAsInteger($parentValue)) {
                $isOverlay = (bool)$parentValue;
            } elseif (is_array($parentValue)) {
                // This case may apply if the value has been converted to an array by the select or group data provider
                $isOverlay = !empty($parentValue) ? (bool)$parentValue[0] : false;
            } else {
                throw new \InvalidArgumentException(
                    'The given value "' . $parentValue . '" for the original language field ' . $this->data['processedTca']['ctrl']['transOrigPointerField']
                    . ' of table ' . $table . ' is invalid.',
                    1470742770
                );
            }
        }

        // A couple of early returns in case the field should not be rendered
        $fieldIsExcluded = $parameterArray['fieldConf']['exclude'] ?? false;
        $fieldNotExcludable = $backendUser->check('non_exclude_fields', $table . ':' . $fieldName);
        $fieldExcludedFromTranslatedRecords = empty($parameterArray['fieldConf']['l10n_display']) && ($parameterArray['fieldConf']['l10n_mode'] ?? '') === 'exclude';
        // Return if BE-user has no access rights to this field, @todo: another user access rights check!
        if (($fieldIsExcluded && !$fieldNotExcludable) || ($isOverlay && $fieldExcludedFromTranslatedRecords) || $this->inlineFieldShouldBeSkipped()) {
            return $resultArray;
        }

        $tsConfig = $this->data['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.'] ?? [];
        $parameterArray['fieldTSConfig'] = is_array($tsConfig) ? $tsConfig : [];

        if ($parameterArray['fieldTSConfig']['disabled'] ?? false) {
            return $resultArray;
        }

        // Override fieldConf by fieldTSconfig:
        $parameterArray['fieldConf']['config'] = FormEngineUtility::overrideFieldConf($parameterArray['fieldConf']['config'], $parameterArray['fieldTSConfig']);
        $parameterArray['itemFormElName'] = 'data[' . $table . '][' . $row['uid'] . '][' . $fieldName . ']';
        $parameterArray['itemFormElID'] = 'data_' . $table . '_' . $row['uid'] . '_' . $fieldName;
        $newElementBaseName = isset($this->data['elementBaseName']) ? $this->data['elementBaseName'] . '[' . $table . '][' . $row['uid'] . '][' . $fieldName . ']' : '';

        // The value to show in the form field.
        $parameterArray['itemFormElValue'] = $row[$fieldName];
        // Set field to read-only if configured for translated records to show default language content as readonly
        // Note: In such case, the database value of this field was already overridden by DatabaseRowDefaultAsReadonly.
        if (($parameterArray['fieldConf']['l10n_display'] ?? false)
            && GeneralUtility::inList($parameterArray['fieldConf']['l10n_display'], 'defaultAsReadonly')
            && $isOverlay
        ) {
            $parameterArray['fieldConf']['config']['readOnly'] = true;
        }

        $processedTcaType = $this->data['processedTca']['ctrl']['type'] ?? '';
        $typeField = !str_contains($processedTcaType, ':')
            ? $processedTcaType
            : substr($processedTcaType, 0, (int)strpos($processedTcaType, ':'));

        // JavaScript code for event handlers:
        $parameterArray['fieldChangeFunc'] = [];
        $parameterArray['fieldChangeFunc']['TBE_EDITOR_fieldChanged'] = new UpdateValueOnFieldChange(
            $table,
            (string)$row['uid'],
            $fieldName,
            $parameterArray['itemFormElName']
        );

        // Based on the type of the item, call a render function on a child element
        $options = $this->data;
        $options['parameterArray'] = $parameterArray;
        $options['elementBaseName'] = $newElementBaseName;
        if (!empty($parameterArray['fieldConf']['config']['renderType'])) {
            $options['renderType'] = $parameterArray['fieldConf']['config']['renderType'];
        } else {
            // Fallback to type if no renderType is given
            $options['renderType'] = $parameterArray['fieldConf']['config']['type'];
        }
        $resultArray = $this->nodeFactory->create($options)->render();

        // Render a custom HTML element which will ask the user to save/update the form due to changing the element.
        // This is used for eg. "type" fields and others configured with "onChange"
        // (https://docs.typo3.org/m/typo3/reference-tca/main/en-us/Columns/Properties/OnChange.html)
        $requestFormEngineUpdate =
            (!empty($this->data['processedTca']['ctrl']['type']) && $fieldName === $typeField)
            || (isset($parameterArray['fieldConf']['onChange']) && $parameterArray['fieldConf']['onChange'] === 'reload');
        if ($requestFormEngineUpdate) {
            $askForUpdate = $backendUser->jsConfirmation(JsConfirmation::TYPE_CHANGE);
            $requestMode = $askForUpdate ? 'ask' : 'enforce';
            $fieldSelector = sprintf('[name="%s"]', $parameterArray['itemFormElName']);
            $resultArray['html'] .= '<typo3-formengine-updater mode="' . htmlspecialchars($requestMode) . '" field="' . htmlspecialchars($fieldSelector) . '"></typo3-formengine-updater>';
        }
        return $resultArray;
    }

    /**
     * Checks if the $table is the child of an inline type AND the $field is the label field of this table.
     * This function is used to dynamically update the label while editing. This has no effect on labels,
     * that were processed by a FormEngine-hook on saving.
     *
     * @param string $table The table to check
     * @param string $field The field on this table to check
     * @return bool Is inline child and field is responsible for the label
     */
    protected function isInlineChildAndLabelField($table, $field)
    {
        $inlineStackProcessor = GeneralUtility::makeInstance(InlineStackProcessor::class);
        $inlineStackProcessor->initializeByGivenStructure($this->data['inlineStructure']);
        $level = $inlineStackProcessor->getStructureLevel(-1);
        if ($level['config']['foreign_label']) {
            $label = $level['config']['foreign_label'];
        } else {
            $label = $this->data['processedTca']['ctrl']['label'];
        }
        return $level['config']['foreign_table'] === $table && $label === $field;
    }

    /**
     * Rendering of inline fields should be skipped under certain circumstances
     *
     * @return bool TRUE if field should be skipped based on inline configuration
     */
    protected function inlineFieldShouldBeSkipped()
    {
        $table = $this->data['tableName'];
        $fieldName = $this->data['fieldName'];
        $fieldConfig = $this->data['processedTca']['columns'][$fieldName]['config'];

        $fieldConfig += [
            'MM' => '',
            'foreign_table' => '',
            'foreign_selector' => '',
            'foreign_field' => '',
        ];

        $inlineStackProcessor = GeneralUtility::makeInstance(InlineStackProcessor::class);
        $inlineStackProcessor->initializeByGivenStructure($this->data['inlineStructure']);
        $structureDepth = $inlineStackProcessor->getStructureDepth();

        $skipThisField = false;
        if ($structureDepth > 0) {
            $searchArray = [
                '%OR' => [
                    'config' => [
                        0 => [
                            '%AND' => [
                                'foreign_table' => $table,
                                '%OR' => [
                                    '%AND' => [
                                        'appearance' => ['useCombination' => true],
                                        'foreign_selector' => $fieldName,
                                    ],
                                    'MM' => $fieldConfig['MM'],
                                ],
                            ],
                        ],
                        1 => [
                            '%AND' => [
                                'foreign_table' => $fieldConfig['foreign_table'],
                                'foreign_selector' => $fieldConfig['foreign_field'],
                            ],
                        ],
                    ],
                ],
            ];
            // Get the parent record from structure stack
            $level = $inlineStackProcessor->getStructureLevel(-1) ?: [];
            // If we have symmetric fields, check on which side we are and hide fields, that are set automatically:
            if ($this->data['isOnSymmetricSide']) {
                $searchArray['%OR']['config'][0]['%AND']['%OR']['symmetric_field'] = $fieldName;
                $searchArray['%OR']['config'][0]['%AND']['%OR']['symmetric_sortby'] = $fieldName;
            } else {
                $searchArray['%OR']['config'][0]['%AND']['%OR']['foreign_field'] = $fieldName;
                $searchArray['%OR']['config'][0]['%AND']['%OR']['foreign_sortby'] = $fieldName;
            }
            $skipThisField = $this->arrayCompareComplex($level, $searchArray);
        }
        return $skipThisField;
    }

    /**
     * Handles complex comparison requests on an array.
     * A request could look like the following:
     *
     * $searchArray = array(
     *   '%AND' => array(
     *     'key1' => 'value1',
     *     'key2' => 'value2',
     *     '%OR' => array(
     *       'subarray' => array(
     *         'subkey' => 'subvalue'
     *       ),
     *       'key3' => 'value3',
     *       'key4' => 'value4'
     *     )
     *   )
     * );
     *
     * It is possible to use the array keys '%AND.1', '%AND.2', etc. to prevent
     * overwriting the sub-array. It could be necessary, if you use complex comparisons.
     *
     * The example above means, key1 *AND* key2 (and their values) have to match with
     * the $subjectArray and additional one *OR* key3 or key4 have to meet the same
     * condition.
     * It is also possible to compare parts of a sub-array (e.g. "subarray"), so this
     * function recurses down one level in that sub-array.
     *
     * @param array $subjectArray The array to search in
     * @param array $searchArray The array with keys and values to search for
     * @param string $type Use '%AND' or '%OR' for comparison
     * @return bool The result of the comparison
     */
    protected function arrayCompareComplex($subjectArray, $searchArray, $type = '')
    {
        $localMatches = 0;
        $localEntries = 0;
        if (is_array($searchArray) && !empty($searchArray)) {
            // If no type was passed, try to determine
            if (!$type) {
                reset($searchArray);
                $type = (string)key($searchArray);
                $searchArray = current($searchArray);
            }
            // We use '%AND' and '%OR' in uppercase
            $type = strtoupper($type);
            // Split regular elements from sub elements
            foreach ($searchArray as $key => $value) {
                $localEntries++;
                // Process a sub-group of OR-conditions
                if ($key === '%OR') {
                    $localMatches += $this->arrayCompareComplex($subjectArray, $value, '%OR') ? 1 : 0;
                } elseif ($key === '%AND') {
                    $localMatches += $this->arrayCompareComplex($subjectArray, $value, '%AND') ? 1 : 0;
                } elseif (is_array($value) && $this->isAssociativeArray($searchArray)) {
                    $localMatches += $this->arrayCompareComplex($subjectArray[$key], $value, $type) ? 1 : 0;
                } elseif (is_array($value)) {
                    $localMatches += $this->arrayCompareComplex($subjectArray, $value, $type) ? 1 : 0;
                } else {
                    if (isset($subjectArray[$key]) && isset($value)) {
                        // Boolean match:
                        if (is_bool($value)) {
                            $localMatches += !($subjectArray[$key] xor $value) ? 1 : 0;
                        } elseif (is_numeric($subjectArray[$key]) && is_numeric($value)) {
                            $localMatches += $subjectArray[$key] == $value ? 1 : 0;
                        } else {
                            $localMatches += $subjectArray[$key] === $value ? 1 : 0;
                        }
                    }
                }
                // If one or more matches are required ('OR'), return TRUE after the first successful match
                if ($type === '%OR' && $localMatches > 0) {
                    return true;
                }
                // If all matches are required ('AND') and we have no result after the first run, return FALSE
                if ($type === '%AND' && $localMatches == 0) {
                    return false;
                }
            }
        }
        // Return the result for '%AND' (if nothing was checked, TRUE is returned)
        return $localEntries === $localMatches;
    }

    /**
     * Checks whether an object is an associative array.
     *
     * @param mixed $object The object to be checked
     * @return bool Returns TRUE, if the object is an associative array
     */
    protected function isAssociativeArray($object)
    {
        return is_array($object) && !empty($object) && array_keys($object) !== range(0, count($object) - 1);
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
