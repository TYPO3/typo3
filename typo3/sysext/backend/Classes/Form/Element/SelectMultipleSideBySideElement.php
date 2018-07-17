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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Localization\LanguageService;
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
            'after' => [ 'editPopup' ],
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
                'localizationStateSelector'
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
     * Render side by side element.
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $languageService = $this->getLanguageService();
        $resultArray = $this->initializeResultArray();

        $parameterArray = $this->data['parameterArray'];
        $config = $parameterArray['fieldConf']['config'];
        $elementName = $parameterArray['itemFormElName'];

        if ($config['readOnly']) {
            // Early return for the relatively simple read only case
            return $this->renderReadOnly();
        }

        $possibleItems = $config['items'];
        $selectedItems = $parameterArray['itemFormElValue'] ?: [];
        $selectedItemsCount = count($selectedItems);

        $maxItems = $config['maxitems'];
        $autoSizeMax = MathUtility::forceIntegerInRange($config['autoSizeMax'], 0);
        $size = 2;
        if (isset($config['size'])) {
            $size = (int)$config['size'];
        }
        if ($autoSizeMax >= 1) {
            $size = MathUtility::forceIntegerInRange($selectedItemsCount + 1, MathUtility::forceIntegerInRange($size, 1), $autoSizeMax);
        }
        $itemCanBeSelectedMoreThanOnce = !empty($config['multiple']);

        $listOfSelectedValues = [];
        $selectedItemsHtml = [];
        foreach ($selectedItems as $itemValue) {
            foreach ($possibleItems as $possibleItem) {
                if ($possibleItem[1] == $itemValue) {
                    $title = $possibleItem[0];
                    $listOfSelectedValues[] = $itemValue;
                    $selectedItemsHtml[] = '<option value="' . htmlspecialchars($itemValue) . '" title="' . htmlspecialchars($title) . '">' . htmlspecialchars($this->appendValueToLabelInDebugMode($title, $itemValue)) . '</option>';
                    break;
                }
            }
        }

        $selectableItemsHtml = [];
        foreach ($possibleItems as $possibleItem) {
            $disabledAttr = '';
            $classAttr = '';
            if (!$itemCanBeSelectedMoreThanOnce && in_array((string)$possibleItem[1], $selectedItems, true)) {
                $disabledAttr = ' disabled="disabled"';
                $classAttr = ' class="hidden"';
            }
            $selectableItemsHtml[] =
                '<option value="'
                    . htmlspecialchars($possibleItem[1])
                    . '" title="' . htmlspecialchars($possibleItem[0]) . '"'
                    . $classAttr . $disabledAttr
                . '>'
                    . htmlspecialchars($this->appendValueToLabelInDebugMode($possibleItem[0], $possibleItem[1])) .
                '</option>';
        }

        // Html stuff for filter and select filter on top of right side of multi select boxes
        $filterTextfield = [];
        if ($config['enableMultiSelectFilterTextfield']) {
            $filterTextfield[] = '<span class="input-group input-group-sm">';
            $filterTextfield[] =    '<span class="input-group-addon">';
            $filterTextfield[] =        '<span class="fa fa-filter"></span>';
            $filterTextfield[] =    '</span>';
            $filterTextfield[] =    '<input class="t3js-formengine-multiselect-filter-textfield form-control" value="">';
            $filterTextfield[] = '</span>';
        }
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
        if (!empty($filterTextfield) || !empty($filterDropDownOptions)) {
            $filterHtml[] = '<div class="form-multigroup-item-wizard">';
            if (!empty($filterTextfield) && !empty($filterDropDownOptions)) {
                $filterHtml[] = '<div class="t3js-formengine-multiselect-filter-container form-multigroup-wrap">';
                $filterHtml[] =     '<div class="form-multigroup-item form-multigroup-element">';
                $filterHtml[] =         '<select class="form-control input-sm t3js-formengine-multiselect-filter-dropdown">';
                $filterHtml[] =             implode(LF, $filterDropDownOptions);
                $filterHtml[] =         '</select>';
                $filterHtml[] =     '</div>';
                $filterHtml[] =     '<div class="form-multigroup-item form-multigroup-element">';
                $filterHtml[] =         implode(LF, $filterTextfield);
                $filterHtml[] =     '</div>';
                $filterHtml[] = '</div>';
            } elseif (!empty($filterTextfield)) {
                $filterHtml[] = implode(LF, $filterTextfield);
            } else {
                $filterHtml[] = '<select class="form-control input-sm t3js-formengine-multiselect-filter-dropdown">';
                $filterHtml[] =     implode(LF, $filterDropDownOptions);
                $filterHtml[] = '</select>';
            }
            $filterHtml[] = '</div>';
        }

        $classes = [];
        $classes[] = 'form-control';
        $classes[] = 'tceforms-multiselect';
        if ($maxItems === 1) {
            $classes[] = 'form-select-no-siblings';
        }
        $multipleAttribute = '';
        if ($maxItems !== 1 && $size !== 1) {
            $multipleAttribute = ' multiple="multiple"';
        }

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        $fieldControlResult = $this->renderFieldControl();
        $fieldControlHtml = $fieldControlResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldControlResult, false);

        $fieldWizardResult = $this->renderFieldWizard();
        $fieldWizardHtml = $fieldWizardResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldWizardResult, false);

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
        $html[] =                               ' id="' . StringUtility::getUniqueId('tceforms-multiselect-') . '"';
        $html[] =                               ' size="' . $size . '"';
        $html[] =                               ' class="' . implode(' ', $classes) . '"';
        $html[] =                               $multipleAttribute;
        $html[] =                               ' data-formengine-input-name="' . htmlspecialchars($elementName) . '"';
        $html[] =                           '>';
        $html[] =                               implode(LF, $selectedItemsHtml);
        $html[] =                           '</select>';
        $html[] =                       '</div>';
        $html[] =                       '<div class="form-wizards-items-aside">';
        $html[] =                           '<div class="btn-group-vertical">';
        if ($maxItems > 1 && $size >= 5) {
            $html[] =                           '<a href="#"';
            $html[] =                               ' class="btn btn-default t3js-btn-moveoption-top"';
            $html[] =                               ' data-fieldname="' . htmlspecialchars($elementName) . '"';
            $html[] =                               ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.move_to_top')) . '"';
            $html[] =                           '>';
            $html[] =                               $this->iconFactory->getIcon('actions-move-to-top', Icon::SIZE_SMALL)->render();
            $html[] =                           '</a>';
        }
        if ($maxItems > 1) {
            $html[] =                           '<a href="#"';
            $html[] =                               ' class="btn btn-default t3js-btn-moveoption-up"';
            $html[] =                               ' data-fieldname="' . htmlspecialchars($elementName) . '"';
            $html[] =                               ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.move_up')) . '"';
            $html[] =                           '>';
            $html[] =                               $this->iconFactory->getIcon('actions-move-up', Icon::SIZE_SMALL)->render();
            $html[] =                           '</a>';
            $html[] =                           '<a href="#"';
            $html[] =                               ' class="btn btn-default t3js-btn-moveoption-down"';
            $html[] =                               ' data-fieldname="' . htmlspecialchars($elementName) . '"';
            $html[] =                               ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.move_down')) . '"';
            $html[] =                           '>';
            $html[] =                               $this->iconFactory->getIcon('actions-move-down', Icon::SIZE_SMALL)->render();
            $html[] =                           '</a>';
        }
        if ($maxItems > 1 && $size >= 5) {
            $html[] =                           '<a href="#"';
            $html[] =                               ' class="btn btn-default t3js-btn-moveoption-bottom"';
            $html[] =                               ' data-fieldname="' . htmlspecialchars($elementName) . '"';
            $html[] =                               ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.move_to_bottom')) . '"';
            $html[] =                           '>';
            $html[] =                               $this->iconFactory->getIcon('actions-move-to-bottom', Icon::SIZE_SMALL)->render();
            $html[] =                           '</a>';
        }
        $html[] =                               '<a href="#"';
        $html[] =                                   ' class="btn btn-default t3js-btn-removeoption"';
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
        $html[] =                           '<select';
        $html[] =                               ' data-relatedfieldname="' . htmlspecialchars($elementName) . '"';
        $html[] =                               ' data-exclusivevalues="' . htmlspecialchars($config['exclusiveKeys']) . '"';
        $html[] =                               ' id="' . StringUtility::getUniqueId('tceforms-multiselect-') . '"';
        $html[] =                               ' data-formengine-input-name="' . htmlspecialchars($elementName) . '"';
        $html[] =                               ' class="form-control t3js-formengine-select-itemstoselect"';
        $html[] =                               ' size="' . $size . '"';
        $html[] =                               ' onchange="' . htmlspecialchars(implode('', $parameterArray['fieldChangeFunc'])) . '"';
        $html[] =                               ' data-formengine-validation-rules="' . htmlspecialchars($this->getValidationDataAsJsonString($config)) . '"';
        $html[] =                           '>';
        $html[] =                               implode(LF, $selectableItemsHtml);
        $html[] =                           '</select>';
        $html[] =                       '</div>';
        if (!empty($fieldControlHtml)) {
            $html[] =                       '<div class="form-wizards-items-aside">';
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
        $selectedItemsCount = count($selectedItems);

        $autoSizeMax = MathUtility::forceIntegerInRange($config['autoSizeMax'], 0);
        $size = 2;
        if (isset($config['size'])) {
            $size = (int)$config['size'];
        }
        if ($autoSizeMax >= 1) {
            $size = MathUtility::forceIntegerInRange($selectedItemsCount + 1, MathUtility::forceIntegerInRange($size, 1), $autoSizeMax);
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
        $html[] =                       ' class="form-control tceforms-multiselect"';
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
