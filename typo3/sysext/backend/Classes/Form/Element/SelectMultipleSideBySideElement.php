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

namespace TYPO3\CMS\Backend\Form\Element;

use TYPO3\CMS\Backend\Form\Behavior\OnFieldChangeTrait;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Render a widget with two boxes side by side.
 *
 * This is rendered for config type=select, renderType=selectMultipleSideBySide set
 */
class SelectMultipleSideBySideElement extends AbstractFormElement
{
    use OnFieldChangeTrait;

    /**
     * Default field information enabled for this element.
     *
     * @var array
     */
    protected $defaultFieldInformation = [
        'tcaDescription' => [
            'renderType' => 'tcaDescription',
        ],
    ];

    /**
     * Default field controls for this element.
     *
     * @var array
     */
    protected $defaultFieldControl = [
        'editPopup' => [
            'renderType' => 'editPopup',
            'disabled' => true,
        ],
        'addRecord' => [
            'renderType' => 'addRecord',
            'disabled' => true,
        ],
        'listModule' => [
            'renderType' => 'listModule',
            'disabled' => true,
            'after' => [ 'addRecord' ],
        ],
    ];

    /**
     * Default field wizards enabled for this element.
     *
     * @var array
     */
    protected $defaultFieldWizard = [
        'localizationStateSelector' => [
            'renderType' => 'localizationStateSelector',
        ],
        'otherLanguageContent' => [
            'renderType' => 'otherLanguageContent',
            'after' => [
                'localizationStateSelector',
            ],
        ],
        'defaultLanguageDifferences' => [
            'renderType' => 'defaultLanguageDifferences',
            'after' => [
                'otherLanguageContent',
            ],
        ],
    ];

    /**
     * Merge field control configuration with default controls and render them.
     *
     * @return array Result array
     */
    protected function renderFieldControl(): array
    {
        $alternativeResult =  [
            // @todo deprecate inline JavaScript in TYPO3 v12.0
            'additionalJavaScriptPost' => [],
            'additionalHiddenFields' => [],
            'additionalInlineLanguageLabelFiles' => [],
            'stylesheetFiles' => [],
            'requireJsModules' => [],
            'inlineData' => [],
            'html' => '',
        ];
        $options = $this->data;
        $fieldControl = $this->defaultFieldControl;
        $fieldControlFromTca = $options['parameterArray']['fieldConf']['config']['fieldControl'] ?? [];
        ArrayUtility::mergeRecursiveWithOverrule($fieldControl, $fieldControlFromTca);
        $options['renderType'] = 'fieldControl';
        if (isset($fieldControl['editPopup'])) {
            $editPopupControl = $fieldControl['editPopup'];
            unset($fieldControl['editPopup']);
            $alternativeOptions = $options;
            $alternativeOptions['renderData']['fieldControl'] = ['editPopup' => $editPopupControl];
            $alternativeResult = $this->nodeFactory->create($alternativeOptions)->render();
        }
        $options['renderData']['fieldControl'] = $fieldControl;
        return [$this->nodeFactory->create($options)->render(), $alternativeResult];
    }

    /**
     * Render side by side element.
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $filterTextfield = [];
        $languageService = $this->getLanguageService();
        $resultArray = $this->initializeResultArray();

        $parameterArray = $this->data['parameterArray'];
        $config = $parameterArray['fieldConf']['config'];
        $elementName = $parameterArray['itemFormElName'];

        if ($config['readOnly'] ?? false) {
            // Early return for the relatively simple read only case
            return $this->renderReadOnly();
        }

        $possibleItems = $config['items'];
        $selectedItems = $parameterArray['itemFormElValue'] ?: [];
        $maxItems = $config['maxitems'];

        $size = (int)($config['size'] ?? 2);
        $autoSizeMax = (int)($config['autoSizeMax'] ?? 0);
        if ($autoSizeMax > 0) {
            $size = MathUtility::forceIntegerInRange($size, 1);
            $size = MathUtility::forceIntegerInRange(count($selectedItems) + 1, $size, $autoSizeMax);
        }

        $itemCanBeSelectedMoreThanOnce = !empty($config['multiple']);

        $listOfSelectedValues = [];
        $selectedItemsHtml = [];
        foreach ($selectedItems as $itemValue) {
            foreach ($possibleItems as $possibleItem) {
                if ($possibleItem[1] == $itemValue) {
                    $title = $possibleItem[0];
                    $listOfSelectedValues[] = $itemValue;
                    $selectedItemsHtml[] = '<option value="' . htmlspecialchars((string)$itemValue) . '" title="' . htmlspecialchars((string)$title) . '">' . htmlspecialchars($this->appendValueToLabelInDebugMode($title, $itemValue)) . '</option>';
                    break;
                }
            }
        }

        $selectableItemCounter = 0;
        $selectableItemGroupCounter = 0;
        $selectableItemGroups = [];
        $selectableItemsHtml = [];

        // Initialize groups
        foreach ($possibleItems as $possibleItem) {
            $disableAttributes = [];
            if (!$itemCanBeSelectedMoreThanOnce && in_array((string)$possibleItem[1], $selectedItems, true)) {
                $disableAttributes = [
                    'disabled' => 'disabled',
                    'class' => 'hidden',
                ];
            }
            if ($possibleItem[1] === '--div--') {
                if ($selectableItemCounter !== 0) {
                    $selectableItemGroupCounter++;
                }
                $selectableItemGroups[$selectableItemGroupCounter]['header']['title'] = $possibleItem[0];
            } else {
                $selectableItemGroups[$selectableItemGroupCounter]['items'][] = [
                    'label' => $this->appendValueToLabelInDebugMode($possibleItem[0], $possibleItem[1]),
                    'attributes' => array_merge(['title' => $possibleItem[0], 'value' => $possibleItem[1]], $disableAttributes),
                ];
                // In case the item is not disabled, enable the group (if any)
                if ($disableAttributes === [] && isset($selectableItemGroups[$selectableItemGroupCounter]['header'])) {
                    $selectableItemGroups[$selectableItemGroupCounter]['header']['disabled'] = false;
                }
                $selectableItemCounter++;
            }
        }

        // Process groups
        foreach ($selectableItemGroups as $selectableItemGroup) {
            if (!is_array($selectableItemGroup['items'] ?? false) || $selectableItemGroup['items'] === []) {
                continue;
            }

            $optionGroup = isset($selectableItemGroup['header']);
            if ($optionGroup) {
                $selectableItemsHtml[] = '<optgroup label="' . htmlspecialchars($selectableItemGroup['header']['title']) . '"' . (($selectableItemGroup['header']['disabled'] ?? true) ? 'class="hidden" disabled="disabled"' : '') . '>';
            }

            foreach ($selectableItemGroup['items'] as $item) {
                $selectableItemsHtml[] = '
                    <option ' . GeneralUtility::implodeAttributes($item['attributes'], true) . '>
                        ' . htmlspecialchars($item['label']) . '
                    </option>';
            }

            if ($optionGroup) {
                $selectableItemsHtml[] = '</optgroup>';
            }
        }

        // Html stuff for filter and select filter on top of right side of multi select boxes
        $filterTextfield[] = '<span class="input-group input-group-sm">';
        $filterTextfield[] =    '<span class="input-group-text">';
        $filterTextfield[] =        '<span class="fa fa-filter"></span>';
        $filterTextfield[] =    '</span>';
        $filterTextfield[] =    '<input class="t3js-formengine-multiselect-filter-textfield form-control" value="">';
        $filterTextfield[] = '</span>';

        $filterDropDownOptions = [];
        if (isset($config['multiSelectFilterItems']) && is_array($config['multiSelectFilterItems']) && count($config['multiSelectFilterItems']) > 1) {
            foreach ($config['multiSelectFilterItems'] as $optionElement) {
                $value = $languageService->sL($optionElement[0]);
                $label = $value;
                if (isset($optionElement[1]) && trim($optionElement[1]) !== '') {
                    $label = $languageService->sL($optionElement[1]);
                }
                $filterDropDownOptions[] = '<option value="' . htmlspecialchars($value) . '">' . htmlspecialchars($label) . '</option>';
            }
        }
        $filterHtml = [];
        $filterHtml[] = '<div class="form-multigroup-item-wizard">';
        if (!empty($filterDropDownOptions)) {
            $filterHtml[] = '<div class="t3js-formengine-multiselect-filter-container form-multigroup-wrap">';
            $filterHtml[] =     '<div class="form-multigroup-item form-multigroup-element">';
            $filterHtml[] =         '<select class="form-select form-select-sm t3js-formengine-multiselect-filter-dropdown">';
            $filterHtml[] =             implode(LF, $filterDropDownOptions);
            $filterHtml[] =         '</select>';
            $filterHtml[] =     '</div>';
            $filterHtml[] =     '<div class="form-multigroup-item form-multigroup-element">';
            $filterHtml[] =         implode(LF, $filterTextfield);
            $filterHtml[] =     '</div>';
            $filterHtml[] = '</div>';
        } else {
            $filterHtml[] = implode(LF, $filterTextfield);
        }
        $filterHtml[] = '</div>';

        $multipleAttribute = '';
        if ($maxItems !== 1 && $size !== 1) {
            $multipleAttribute = ' multiple="multiple"';
        }

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        [$fieldControlResult, $alternativeControlResult] = $this->renderFieldControl();
        $fieldControlHtml = $fieldControlResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldControlResult, false);
        $alternativeFieldControlHtml = $alternativeControlResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $alternativeControlResult, false);

        $fieldWizardResult = $this->renderFieldWizard();
        $fieldWizardHtml = $fieldWizardResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldWizardResult, false);

        $selectedOptionsFieldId = StringUtility::getUniqueId('tceforms-multiselect-');
        $availableOptionsFieldId = StringUtility::getUniqueId('tceforms-multiselect-');

        $html = [];
        $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
        $html[] =   $fieldInformationHtml;
        $html[] =   '<div class="form-wizards-wrap">';
        $html[] =       '<div class="form-wizards-element">';
        $html[] =           '<input type="hidden" data-formengine-input-name="' . htmlspecialchars($elementName) . '" value="' . (int)$itemCanBeSelectedMoreThanOnce . '" />';
        $html[] =           '<div class="form-multigroup-wrap t3js-formengine-field-group">';
        $html[] =               '<div class="form-multigroup-item form-multigroup-element">';
        $html[] =                   '<label>';
        $html[] =                       htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.selected'));
        $html[] =                   '</label>';
        $html[] =                   '<div class="form-wizards-wrap form-wizards-aside">';
        $html[] =                       '<div class="form-wizards-element">';
        $html[] =                           '<select';
        $html[] =                               ' id="' . $selectedOptionsFieldId . '"';
        $html[] =                               ' size="' . $size . '"';
        $html[] =                               ' class="form-select"';
        $html[] =                               $multipleAttribute;
        $html[] =                               ' data-formengine-input-name="' . htmlspecialchars($elementName) . '"';
        $html[] =                           '>';
        $html[] =                               implode(LF, $selectedItemsHtml);
        $html[] =                           '</select>';
        $html[] =                       '</div>';
        $html[] =                       '<div class="form-wizards-items-aside form-wizards-items-aside--move">';
        $html[] =                           '<div class="btn-group-vertical">';
        if ($maxItems > 1 && $size >= 5) {
            $html[] =                           '<a href="#"';
            $html[] =                               ' class="btn btn-default t3js-btn-option t3js-btn-moveoption-top"';
            $html[] =                               ' data-fieldname="' . htmlspecialchars($elementName) . '"';
            $html[] =                               ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.move_to_top')) . '"';
            $html[] =                           '>';
            $html[] =                               $this->iconFactory->getIcon('actions-move-to-top', Icon::SIZE_SMALL)->render();
            $html[] =                           '</a>';
        }
        if ($maxItems > 1) {
            $html[] =                           '<a href="#"';
            $html[] =                               ' class="btn btn-default t3js-btn-option t3js-btn-moveoption-up"';
            $html[] =                               ' data-fieldname="' . htmlspecialchars($elementName) . '"';
            $html[] =                               ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.move_up')) . '"';
            $html[] =                           '>';
            $html[] =                               $this->iconFactory->getIcon('actions-move-up', Icon::SIZE_SMALL)->render();
            $html[] =                           '</a>';
            $html[] =                           '<a href="#"';
            $html[] =                               ' class="btn btn-default t3js-btn-option t3js-btn-moveoption-down"';
            $html[] =                               ' data-fieldname="' . htmlspecialchars($elementName) . '"';
            $html[] =                               ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.move_down')) . '"';
            $html[] =                           '>';
            $html[] =                               $this->iconFactory->getIcon('actions-move-down', Icon::SIZE_SMALL)->render();
            $html[] =                           '</a>';
        }
        if ($maxItems > 1 && $size >= 5) {
            $html[] =                           '<a href="#"';
            $html[] =                               ' class="btn btn-default t3js-btn-option t3js-btn-moveoption-bottom"';
            $html[] =                               ' data-fieldname="' . htmlspecialchars($elementName) . '"';
            $html[] =                               ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.move_to_bottom')) . '"';
            $html[] =                           '>';
            $html[] =                               $this->iconFactory->getIcon('actions-move-to-bottom', Icon::SIZE_SMALL)->render();
            $html[] =                           '</a>';
        }
        $html[] =                                $alternativeFieldControlHtml;
        $html[] =                               '<a href="#"';
        $html[] =                                   ' class="btn btn-default t3js-btn-option t3js-btn-removeoption"';
        $html[] =                                   ' data-fieldname="' . htmlspecialchars($elementName) . '"';
        $html[] =                                   ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.remove_selected')) . '"';
        $html[] =                               '>';
        $html[] =                                   $this->iconFactory->getIcon('actions-selection-delete', Icon::SIZE_SMALL)->render();
        $html[] =                               '</a>';
        $html[] =                           '</div>';
        $html[] =                       '</div>';
        $html[] =                   '</div>';
        $html[] =               '</div>';
        $html[] =               '<div class="form-multigroup-item form-multigroup-element">';
        $html[] =                   '<label>';
        $html[] =                       htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.items'));
        $html[] =                   '</label>';
        $html[] =                   '<div class="form-wizards-wrap form-wizards-aside">';
        $html[] =                       '<div class="form-wizards-element">';
        $html[] =                           implode(LF, $filterHtml);
        $selectElementAttrs = array_merge(
            [
                'size' => $size,
                'id' => $availableOptionsFieldId,
                'class' => 'form-select t3js-formengine-select-itemstoselect',
                'data-relatedfieldname' => $elementName,
                'data-exclusivevalues' =>  $config['exclusiveKeys'] ?? '',
                'data-formengine-validation-rules' => $this->getValidationDataAsJsonString($config),
            ],
            $this->getOnFieldChangeAttrs('change', $parameterArray['fieldChangeFunc'] ?? [])
        );
        $html[] =                           '<select ' . GeneralUtility::implodeAttributes($selectElementAttrs, true) . '>';
        $html[] =                               implode(LF, $selectableItemsHtml);
        $html[] =                           '</select>';
        $html[] =                       '</div>';
        if (!empty($fieldControlHtml)) {
            $html[] =                       '<div class="form-wizards-items-aside form-wizards-items-aside--field-control">';
            $html[] =                           '<div class="btn-group-vertical">';
            $html[] =                               $fieldControlHtml;
            $html[] =                           '</div>';
            $html[] =                       '</div>';
        }
        $html[] =                   '</div>';
        $html[] =               '</div>';
        $html[] =           '</div>';
        $html[] =           '<input type="hidden" name="' . htmlspecialchars($elementName) . '" value="' . htmlspecialchars(implode(',', $listOfSelectedValues)) . '" />';
        $html[] =       '</div>';
        if (!empty($fieldWizardHtml)) {
            $html[] = '<div class="form-wizards-items-bottom">';
            $html[] = $fieldWizardHtml;
            $html[] = '</div>';
        }
        $html[] =   '</div>';
        $html[] = '</div>';

        $resultArray['requireJsModules'][] = JavaScriptModuleInstruction::forRequireJS(
            'TYPO3/CMS/Backend/FormEngine/Element/SelectMultipleSideBySideElement'
        )->instance($selectedOptionsFieldId, $availableOptionsFieldId);

        $resultArray['html'] = implode(LF, $html);
        return $resultArray;
    }

    /**
     * Create HTML of a read only multi select. Right side is not
     * rendered, but just the left side with the selected items.
     *
     * @return array
     */
    protected function renderReadOnly()
    {
        $languageService = $this->getLanguageService();
        $resultArray = $this->initializeResultArray();

        $parameterArray = $this->data['parameterArray'];
        $config = $parameterArray['fieldConf']['config'];
        $fieldName = $parameterArray['itemFormElName'];

        $possibleItems = $config['items'];
        $selectedItems = $parameterArray['itemFormElValue'] ?: [];
        if (!is_array($selectedItems)) {
            $selectedItems = GeneralUtility::trimExplode(',', $selectedItems, true);
        }
        $size = (int)($config['size'] ?? 2);
        $autoSizeMax = (int)($config['autoSizeMax'] ?? 0);
        if ($autoSizeMax > 0) {
            $size = MathUtility::forceIntegerInRange($size, 1);
            $size = MathUtility::forceIntegerInRange(count($selectedItems) + 1, $size, $autoSizeMax);
        }

        $multiple = '';
        if ($size !== 1) {
            $multiple = ' multiple="multiple"';
        }

        $listOfSelectedValues = [];
        $optionsHtml = [];
        foreach ($selectedItems as $itemValue) {
            foreach ($possibleItems as $possibleItem) {
                if ($possibleItem[1] == $itemValue) {
                    $title = $possibleItem[0];
                    $listOfSelectedValues[] = $itemValue;
                    $optionsHtml[] = '<option value="' . htmlspecialchars($itemValue) . '" title="' . htmlspecialchars($title) . '">' . htmlspecialchars($title) . '</option>';
                    break;
                }
            }
        }

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        $html = [];
        $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
        $html[] =   $fieldInformationHtml;
        $html[] =   '<div class="form-wizards-wrap">';
        $html[] =       '<div class="form-wizards-element">';
        $html[] =           '<label>';
        $html[] =               htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.selected'));
        $html[] =           '</label>';
        $html[] =           '<div class="form-wizards-wrap form-wizards-aside">';
        $html[] =               '<div class="form-wizards-element">';
        $html[] =                   '<select';
        $html[] =                       ' id="' . StringUtility::getUniqueId('tceforms-multiselect-') . '"';
        $html[] =                       ' size="' . $size . '"';
        $html[] =                       ' class="form-select"';
        $html[] =                       $multiple;
        $html[] =                       ' data-formengine-input-name="' . htmlspecialchars($fieldName) . '"';
        $html[] =                       ' disabled="disabled">';
        $html[] =                   '/>';
        $html[] =                       implode(LF, $optionsHtml);
        $html[] =                   '</select>';
        $html[] =               '</div>';
        $html[] =           '</div>';
        $html[] =           '<input type="hidden" name="' . htmlspecialchars($fieldName) . '" value="' . htmlspecialchars(implode(',', $listOfSelectedValues)) . '" />';
        $html[] =       '</div>';
        $html[] =   '</div>';
        $html[] = '</div>';

        $resultArray['html'] = implode(LF, $html);
        return $resultArray;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }
}
