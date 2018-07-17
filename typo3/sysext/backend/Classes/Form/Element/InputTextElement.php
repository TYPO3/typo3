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

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * General type=input element.
 *
 * This one kicks in if no specific renderType like "inputDateTime"
 * or "inputColorPicker" is set.
 */
class InputTextElement extends AbstractFormElement
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
     * This will render a single-line input form field, possibly with various control/validation features
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $languageService = $this->getLanguageService();

        $table = $this->data['tableName'];
        $fieldName = $this->data['fieldName'];
        $row = $this->data['databaseRow'];
        $parameterArray = $this->data['parameterArray'];
        $resultArray = $this->initializeResultArray();

        $itemValue = $parameterArray['itemFormElValue'];
        $config = $parameterArray['fieldConf']['config'];
        $evalList = GeneralUtility::trimExplode(',', $config['eval'], true);
        $size = MathUtility::forceIntegerInRange($config['size'] ?? $this->defaultInputWidth, $this->minimumInputWidth, $this->maxInputWidth);
        $width = (int)$this->formMaxWidth($size);
        $nullControlNameEscaped = htmlspecialchars('control[active][' . $table . '][' . $row['uid'] . '][' . $fieldName . ']');

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        if ($config['readOnly']) {
            // Early return for read only fields
            if (in_array('password', $evalList, true)) {
                $itemValue = $itemValue ? '*********' : '';
            }
            $html = [];
            $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
            $html[] =   $fieldInformationHtml;
            $html[] =   '<div class="form-wizards-wrap">';
            $html[] =       '<div class="form-wizards-element">';
            $html[] =           '<div class="form-control-wrap" style="max-width: ' . $width . 'px">';
            $html[] =               '<input class="form-control" value="' . htmlspecialchars($itemValue) . '" type="text" disabled>';
            $html[] =           '</div>';
            $html[] =       '</div>';
            $html[] =   '</div>';
            $html[] = '</div>';
            $resultArray['html'] = implode(LF, $html);
            return $resultArray;
        }

        // @todo: The whole eval handling is a mess and needs refactoring
        foreach ($evalList as $func) {
            // @todo: This is ugly: The code should find out on it's own whether a eval definition is a
            // @todo: keyword like "date", or a class reference. The global registration could be dropped then
            // Pair hook to the one in \TYPO3\CMS\Core\DataHandling\DataHandler::checkValue_input_Eval()
            if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][$func])) {
                if (class_exists($func)) {
                    $evalObj = GeneralUtility::makeInstance($func);
                    if (method_exists($evalObj, 'deevaluateFieldValue')) {
                        $_params = [
                            'value' => $itemValue
                        ];
                        $itemValue = $evalObj->deevaluateFieldValue($_params);
                    }
                    if (method_exists($evalObj, 'returnFieldJS')) {
                        $resultArray['additionalJavaScriptPost'][] = 'TBE_EDITOR.customEvalFunctions[' . GeneralUtility::quoteJSvalue($func) . ']'
                            . ' = function(value) {' . $evalObj->returnFieldJS() . '};';
                    }
                }
            }
        }

        $attributes = [
            'value' => '',
            'id' => StringUtility::getUniqueId('formengine-input-'),
            'class' => implode(' ', [
                'form-control',
                't3js-clearable',
                'hasDefaultValue',
            ]),
            'data-formengine-validation-rules' => $this->getValidationDataAsJsonString($config),
            'data-formengine-input-params' => json_encode([
                'field' => $parameterArray['itemFormElName'],
                'evalList' => implode(',', $evalList),
                'is_in' => trim($config['is_in'])
            ]),
            'data-formengine-input-name' => $parameterArray['itemFormElName'],
        ];

        $maxLength = $config['max'] ?? 0;
        if ((int)$maxLength > 0) {
            $attributes['maxlength'] = (int)$maxLength;
        }
        if (!empty($config['placeholder'])) {
            $attributes['placeholder'] = trim($config['placeholder']);
        }
        if (isset($config['autocomplete'])) {
            $attributes['autocomplete'] = empty($config['autocomplete']) ? 'new-' . $fieldName : 'on';
        }

        $valuePickerHtml = [];
        if (isset($config['valuePicker']['items']) && is_array($config['valuePicker']['items'])) {
            $mode = $config['valuePicker']['mode'] ?? '';
            $itemName = $parameterArray['itemFormElName'];
            $fieldChangeFunc = $parameterArray['fieldChangeFunc'];
            if ($mode === 'append') {
                $assignValue = 'document.querySelectorAll(' . GeneralUtility::quoteJSvalue('[data-formengine-input-name="' . $itemName . '"]') . ')[0]'
                    . '.value+=\'\'+this.options[this.selectedIndex].value';
            } elseif ($mode === 'prepend') {
                $assignValue = 'document.querySelectorAll(' . GeneralUtility::quoteJSvalue('[data-formengine-input-name="' . $itemName . '"]') . ')[0]'
                    . '.value=\'\'+this.options[this.selectedIndex].value+document.editform[' . GeneralUtility::quoteJSvalue($itemName) . '].value';
            } else {
                $assignValue = 'document.querySelectorAll(' . GeneralUtility::quoteJSvalue('[data-formengine-input-name="' . $itemName . '"]') . ')[0]'
                    . '.value=this.options[this.selectedIndex].value';
            }
            $valuePickerHtml[] = '<select';
            $valuePickerHtml[] =  ' class="form-control tceforms-select tceforms-wizardselect"';
            $valuePickerHtml[] =  ' onchange="' . htmlspecialchars($assignValue . ';this.blur();this.selectedIndex=0;' . implode('', $fieldChangeFunc)) . '"';
            $valuePickerHtml[] = '>';
            $valuePickerHtml[] = '<option></option>';
            foreach ($config['valuePicker']['items'] as $item) {
                $valuePickerHtml[] = '<option value="' . htmlspecialchars($item[1]) . '">' . htmlspecialchars($languageService->sL($item[0])) . '</option>';
            }
            $valuePickerHtml[] = '</select>';
        }

        $valueSliderHtml = [];
        if (isset($config['slider']) && is_array($config['slider'])) {
            $resultArray['requireJsModules'][] = 'TYPO3/CMS/Backend/ValueSlider';
            $min = $config['range']['lower'] ?? 0;
            $max = $config['range']['upper'] ?? 10000;
            $step = $config['slider']['step'] ?? 1;
            $width = $config['slider']['width'] ?? 400;
            $valueType = 'null';
            if (in_array('int', $evalList, true)) {
                $valueType = 'int';
                $itemValue = (int)$itemValue;
            } elseif (in_array('double2', $evalList, true)) {
                $valueType = 'double';
                $itemValue = (double)$itemValue;
            }
            $callbackParams = [ $table, $row['uid'], $fieldName, $parameterArray['itemFormElName'] ];
            $id = 'slider-' . md5($parameterArray['itemFormElName']);
            $valueSliderHtml[] = '<div';
            $valueSliderHtml[] =    ' id="' . $id . '"';
            $valueSliderHtml[] =    ' data-slider-id="' . $id . '"';
            $valueSliderHtml[] =    ' data-slider-min="' . (int)$min . '"';
            $valueSliderHtml[] =    ' data-slider-max="' . (int)$max . '"';
            $valueSliderHtml[] =    ' data-slider-step="' . htmlspecialchars($step) . '"';
            $valueSliderHtml[] =    ' data-slider-value="' . htmlspecialchars($itemValue) . '"';
            $valueSliderHtml[] =    ' data-slider-value-type="' . htmlspecialchars($valueType) . '"';
            $valueSliderHtml[] =    ' data-slider-item-name="' . htmlspecialchars($parameterArray['itemFormElName']) . '"';
            $valueSliderHtml[] =    ' data-slider-callback-params="' . htmlspecialchars(json_encode($callbackParams)) . '"';
            $valueSliderHtml[] =    ' style="width: ' . $width . 'px;"';
            $valueSliderHtml[] = '>';
            $valueSliderHtml[] = '</div>';
        }

        $fieldControlResult = $this->renderFieldControl();
        $fieldControlHtml = $fieldControlResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldControlResult, false);

        $fieldWizardResult = $this->renderFieldWizard();
        $fieldWizardHtml = $fieldWizardResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldWizardResult, false);
        $inputType = 'text';

        if (in_array('email', $evalList, true)) {
            $inputType = 'email';
        } elseif (!empty(array_intersect($evalList, ['int', 'num']))) {
            $inputType = 'number';

            if (isset($config['range']['lower'])) {
                $attributes['min'] = (int)$config['range']['lower'];
            }
            if (isset($config['range']['upper'])) {
                $attributes['max'] = (int)$config['range']['upper'];
            }
        }

        $mainFieldHtml = [];
        $mainFieldHtml[] = '<div class="form-control-wrap" style="max-width: ' . $width . 'px">';
        $mainFieldHtml[] =  '<div class="form-wizards-wrap">';
        $mainFieldHtml[] =      '<div class="form-wizards-element">';
        $mainFieldHtml[] =          '<input type="' . $inputType . '"' . GeneralUtility::implodeAttributes($attributes, true) . ' />';
        $mainFieldHtml[] =          '<input type="hidden" name="' . $parameterArray['itemFormElName'] . '" value="' . htmlspecialchars($itemValue) . '" />';
        $mainFieldHtml[] =      '</div>';
        if (!empty($valuePickerHtml) || !empty($valueSliderHtml) || !empty($fieldControlHtml)) {
            $mainFieldHtml[] =      '<div class="form-wizards-items-aside">';
            $mainFieldHtml[] =          '<div class="btn-group">';
            $mainFieldHtml[] =              implode(LF, $valuePickerHtml);
            $mainFieldHtml[] =              implode(LF, $valueSliderHtml);
            $mainFieldHtml[] =              $fieldControlHtml;
            $mainFieldHtml[] =          '</div>';
            $mainFieldHtml[] =      '</div>';
        }
        if (!empty($fieldWizardHtml)) {
            $mainFieldHtml[] = '<div class="form-wizards-items-bottom">';
            $mainFieldHtml[] = $fieldWizardHtml;
            $mainFieldHtml[] = '</div>';
        }
        $mainFieldHtml[] =  '</div>';
        $mainFieldHtml[] = '</div>';
        $mainFieldHtml = implode(LF, $mainFieldHtml);

        $fullElement = $mainFieldHtml;
        if ($this->hasNullCheckboxButNoPlaceholder()) {
            $checked = $itemValue !== null ? ' checked="checked"' : '';
            $fullElement = [];
            $fullElement[] = '<div class="t3-form-field-disable"></div>';
            $fullElement[] = '<div class="checkbox t3-form-field-eval-null-checkbox">';
            $fullElement[] =     '<label for="' . $nullControlNameEscaped . '">';
            $fullElement[] =         '<input type="hidden" name="' . $nullControlNameEscaped . '" value="0" />';
            $fullElement[] =         '<input type="checkbox" name="' . $nullControlNameEscaped . '" id="' . $nullControlNameEscaped . '" value="1"' . $checked . ' />';
            $fullElement[] =         $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.nullCheckbox');
            $fullElement[] =     '</label>';
            $fullElement[] = '</div>';
            $fullElement[] = $mainFieldHtml;
            $fullElement = implode(LF, $fullElement);
        } elseif ($this->hasNullCheckboxWithPlaceholder()) {
            $checked = $itemValue !== null ? ' checked="checked"' : '';
            $placeholder = $shortenedPlaceholder = $config['placeholder'] ?? '';
            $disabled = '';
            $fallbackValue = 0;
            if (strlen($placeholder) > 0) {
                $shortenedPlaceholder = GeneralUtility::fixed_lgd_cs($placeholder, 20);
                if ($placeholder !== $shortenedPlaceholder) {
                    $overrideLabel = sprintf(
                        $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.placeholder.override'),
                        '<span title="' . htmlspecialchars($placeholder) . '">' . htmlspecialchars($shortenedPlaceholder) . '</span>'
                    );
                } else {
                    $overrideLabel = sprintf(
                        $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.placeholder.override'),
                        htmlspecialchars($placeholder)
                    );
                }
            } else {
                $overrideLabel = $languageService->sL(
                    'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.placeholder.override_not_available'
                );
            }
            $fullElement = [];
            $fullElement[] = '<div class="checkbox t3js-form-field-eval-null-placeholder-checkbox">';
            $fullElement[] =     '<label for="' . $nullControlNameEscaped . '">';
            $fullElement[] =         '<input type="hidden" name="' . $nullControlNameEscaped . '" value="' . $fallbackValue . '" />';
            $fullElement[] =         '<input type="checkbox" name="' . $nullControlNameEscaped . '" id="' . $nullControlNameEscaped . '" value="1"' . $checked . $disabled . ' />';
            $fullElement[] =         $overrideLabel;
            $fullElement[] =     '</label>';
            $fullElement[] = '</div>';
            $fullElement[] = '<div class="t3js-formengine-placeholder-placeholder">';
            $fullElement[] =    '<div class="form-control-wrap" style="max-width:' . $width . 'px">';
            $fullElement[] =        '<input type="text" class="form-control" disabled="disabled" value="' . $shortenedPlaceholder . '" />';
            $fullElement[] =    '</div>';
            $fullElement[] = '</div>';
            $fullElement[] = '<div class="t3js-formengine-placeholder-formfield">';
            $fullElement[] =    $mainFieldHtml;
            $fullElement[] = '</div>';
            $fullElement = implode(LF, $fullElement);
        }

        $resultArray['html'] = '<div class="formengine-field-item t3js-formengine-field-item">' . $fieldInformationHtml . $fullElement . '</div>';
        return $resultArray;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
