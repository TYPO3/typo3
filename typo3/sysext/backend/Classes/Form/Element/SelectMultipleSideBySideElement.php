<?php
namespace TYPO3\CMS\Backend\Form\Element;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Render a widget with two boxes side by side.
 *
 * This is rendered for config type=select, maxitems > 1, renderType=selectMultipleSideBySide set
 */
class SelectMultipleSideBySideElement extends AbstractFormElement
{
    /**
     * Render side by side element.
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $table = $this->data['tableName'];
        $field = $this->data['fieldName'];
        $parameterArray = $this->data['parameterArray'];
        // Field configuration from TCA:
        $config = $parameterArray['fieldConf']['config'];

        // Creating the label for the "No Matching Value" entry.
        $noMatchingLabel = isset($parameterArray['fieldTSConfig']['noMatchingValue_label'])
            ? $this->getLanguageService()->sL(trim($parameterArray['fieldTSConfig']['noMatchingValue_label']))
            : '[ ' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.noMatchingValue') . ' ]';

        $selItems = $config['items'];
        $html = '';
        $disabled = '';
        if ($config['readOnly']) {
            $disabled = ' disabled="disabled"';
        }
        // Setting this hidden field (as a flag that JavaScript can read out)
        if (!$disabled) {
            $html .= '<input type="hidden" data-formengine-input-name="' . htmlspecialchars($parameterArray['itemFormElName']) . '" value="' . ($config['multiple'] ? 1 : 0) . '" />';
        }
        // Set max and min items:
        $maxitems = MathUtility::forceIntegerInRange($config['maxitems'], 0);
        if (!$maxitems) {
            $maxitems = 100000;
        }
        // Get the array with selected items:
        $itemsArray = $parameterArray['itemFormElValue'] ?: [];

        // Perform modification of the selected items array:
        foreach ($itemsArray as $itemNumber => $itemValue) {
            $itemArray = [
                0 => $itemValue,
                1 => '',
            ];

            if (isset($parameterArray['fieldTSConfig']['altIcons.'][$itemValue])) {
                $itemArray[2] = $parameterArray['fieldTSConfig']['altIcons.'][$itemValue];
            }

            foreach ($selItems as $selItem) {
                if ($selItem[1] == $itemValue) {
                    $itemArray[1] = $selItem[0];
                    break;
                }
            }
            $itemsArray[$itemNumber] = implode('|', $itemArray);
        }

        // size must be at least two, as there are always maxitems > 1 (see parent function)
        if (isset($config['size'])) {
            $size = (int)$config['size'];
        } else {
            $size = 2;
        }
        $size = $config['autoSizeMax'] ? MathUtility::forceIntegerInRange(count($itemsArray) + 1, MathUtility::forceIntegerInRange($size, 1), $config['autoSizeMax']) : $size;
        $allowMultiple = !empty($config['multiple']);

        $itemsToSelect = [];
        $filterTextfield = [];
        $filterSelectbox = '';
        if (!$disabled) {
            // Create option tags:
            $opt = [];
            foreach ($selItems as $p) {
                $disabledAttr = '';
                $classAttr = '';
                if (!$allowMultiple && in_array((string)$p[1], $parameterArray['itemFormElValue'], true)) {
                    $disabledAttr = ' disabled="disabled"';
                    $classAttr = ' class="hidden"';
                }
                $opt[] = '<option value="' . htmlspecialchars($p[1]) . '" title="' . htmlspecialchars($p[0]) . '"' . $classAttr . $disabledAttr . '>' . htmlspecialchars($p[0]) . '</option>';
            }
            // Put together the selector box:
            $selector_itemListStyle = isset($config['itemListStyle'])
                ? ' style="' . htmlspecialchars($config['itemListStyle']) . '"'
                : '';
            $sOnChange = implode('', $parameterArray['fieldChangeFunc']);

            $multiSelectId = StringUtility::getUniqueId('tceforms-multiselect-');
            $itemsToSelect[] = '<select data-relatedfieldname="' . htmlspecialchars($parameterArray['itemFormElName']) . '" '
                . 'data-exclusivevalues="' . htmlspecialchars($config['exclusiveKeys']) . '" '
                . 'id="' . $multiSelectId . '" '
                . 'data-formengine-input-name="' . htmlspecialchars($parameterArray['itemFormElName']) . '" '
                . 'class="form-control t3js-formengine-select-itemstoselect" '
                . ($size ? ' size="' . $size . '" ' : '')
                . 'onchange="' . htmlspecialchars($sOnChange) . '" '
                . $parameterArray['onFocus']
                . $this->getValidationDataAsDataAttribute($config)
                . $selector_itemListStyle
                . '>';
            $itemsToSelect[] = implode(LF, $opt);
            $itemsToSelect[] = '</select>';

            // enable filter functionality via a text field
            if ($config['enableMultiSelectFilterTextfield']) {
                $filterTextfield[] = '<span class="input-group input-group-sm">';
                $filterTextfield[] =    '<span class="input-group-addon">';
                $filterTextfield[] =        '<span class="fa fa-filter"></span>';
                $filterTextfield[] =    '</span>';
                $filterTextfield[] =    '<input class="t3js-formengine-multiselect-filter-textfield form-control" value="">';
                $filterTextfield[] = '</span>';
            }

            // enable filter functionality via a select
            if (isset($config['multiSelectFilterItems']) && is_array($config['multiSelectFilterItems']) && count($config['multiSelectFilterItems']) > 1) {
                $filterDropDownOptions = [];
                foreach ($config['multiSelectFilterItems'] as $optionElement) {
                    $optionValue = $this->getLanguageService()->sL(isset($optionElement[1]) && trim($optionElement[1]) !== '' ? trim($optionElement[1])
                        : trim($optionElement[0]));
                    $filterDropDownOptions[] = '<option value="' . htmlspecialchars($this->getLanguageService()->sL(trim($optionElement[0]))) . '">'
                        . htmlspecialchars($optionValue) . '</option>';
                }
                $filterSelectbox = '<select class="form-control input-sm t3js-formengine-multiselect-filter-dropdown">'
                    . implode(LF, $filterDropDownOptions) . '</select>';
            }
        }

        if (!empty(trim($filterSelectbox)) && !empty($filterTextfield)) {
            $filterSelectbox = '<div class="form-multigroup-item form-multigroup-element">' . $filterSelectbox . '</div>';
            $filterTextfield = '<div class="form-multigroup-item form-multigroup-element">' . implode(LF, $filterTextfield) . '</div>';
            $selectBoxFilterContents = '<div class="t3js-formengine-multiselect-filter-container form-multigroup-wrap">' . $filterSelectbox . $filterTextfield . '</div>';
        } else {
            $selectBoxFilterContents = trim($filterSelectbox . ' ' . implode(LF, $filterTextfield));
        }

        // Pass to "dbFileIcons" function:
        $params = [
            'size' => $size,
            'autoSizeMax' => MathUtility::forceIntegerInRange($config['autoSizeMax'], 0),
            'style' => isset($config['selectedListStyle'])
                ? ' style="' . htmlspecialchars($config['selectedListStyle']) . '"'
                : '',
            'dontShowMoveIcons' => $maxitems <= 1,
            'maxitems' => $maxitems,
            'info' => '',
            'headers' => [
                'selector' => $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.selected'),
                'items' => $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.items'),
                'selectorbox' => $selectBoxFilterContents,
            ],
            'noBrowser' => 1,
            'rightbox' => implode(LF, $itemsToSelect),
            'readOnly' => $disabled
        ];
        $html .= $this->dbFileIcons($parameterArray['itemFormElName'], '', '', $itemsArray, '', $params, $parameterArray['onFocus']);

        // Wizards:
        if (!$disabled) {
            $html = $this->renderWizards(
                [$html],
                $config['wizards'],
                $table,
                $this->data['databaseRow'],
                $field,
                $parameterArray,
                $parameterArray['itemFormElName'],
                BackendUtility::getSpecConfParts($parameterArray['fieldConf']['defaultExtras'])
            );
        }

        $resultArray = $this->initializeResultArray();
        $resultArray['html'] = $html;
        return $resultArray;
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }
}
