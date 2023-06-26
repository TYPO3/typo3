<?php

declare(strict_types=1);

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

use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * type=number element.
 *
 * The NumberElement renders a html field with the type "number" attribute.
 */
class NumberElement extends AbstractFormElement
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
     * This will render a single-line input form field, possibly with various control/validation features
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render(): array
    {
        $table = $this->data['tableName'];
        $fieldName = $this->data['fieldName'];
        $parameterArray = $this->data['parameterArray'];
        $resultArray = $this->initializeResultArray();
        // @deprecated since v12, will be removed with v13 when all elements handle label/legend on their own
        $resultArray['labelHasBeenHandled'] = true;
        $config = $parameterArray['fieldConf']['config'];

        $format = $config['format'] ?? 'integer';
        if ($format !== 'integer' && $format !== 'decimal') {
            throw new \UnexpectedValueException(
                'Format "' . $format . '" for field "' . $fieldName . '" in table "' . $table . '" is '
                . 'not valid. Must be either empty or set to one of: "integer", "decimal".',
                1649124682
            );
        }

        // @todo This should be configurable (e.g. [config][precision])
        $precision = 2;

        $itemValue = $parameterArray['itemFormElValue'];
        $width = $this->formMaxWidth(
            MathUtility::forceIntegerInRange($config['size'] ?? $this->defaultInputWidth, $this->minimumInputWidth, $this->maxInputWidth)
        );
        $fieldId = StringUtility::getUniqueId('formengine-input-');
        $renderedLabel = $this->renderLabel($fieldId);

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        if ($config['readOnly'] ?? false) {
            $html = [];
            $html[] = $renderedLabel;
            $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
            $html[] =   $fieldInformationHtml;
            $html[] =   '<div class="form-wizards-wrap">';
            $html[] =       '<div class="form-wizards-element">';
            $html[] =           '<div class="form-control-wrap" style="max-width: ' . $width . 'px">';
            $html[] =               '<input class="form-control" id="' . htmlspecialchars($fieldId) . '" value="' . htmlspecialchars((string)$itemValue) . '" type="text" disabled>';
            $html[] =           '</div>';
            $html[] =       '</div>';
            $html[] =   '</div>';
            $html[] = '</div>';
            $resultArray['html'] = implode(LF, $html);
            return $resultArray;
        }

        $languageService = $this->getLanguageService();
        $itemName = (string)$parameterArray['itemFormElName'];

        // Always add the format.
        $evalList = [$format];
        if ($config['nullable'] ?? false) {
            $evalList[] = 'null';
        }

        $attributes = [
            'value' => '',
            'id' => $fieldId,
            'class' => implode(' ', [
                'form-control',
                'form-control-clearable',
                't3js-clearable',
                'hasDefaultValue',
            ]),
            'data-formengine-validation-rules' => $this->getValidationDataAsJsonString($config),
            'data-formengine-input-params' => (string)json_encode([
                'field' => $itemName,
                'evalList' => implode(',', $evalList),
            ], JSON_THROW_ON_ERROR),
            'data-formengine-input-name' => $itemName,
        ];

        if (!empty($config['placeholder'])) {
            $attributes['placeholder'] = trim($config['placeholder']);
        }
        if (isset($config['autocomplete'])) {
            $attributes['autocomplete'] = empty($config['autocomplete']) ? 'new-' . $fieldName : 'on';
        }

        $valuePickerHtml = [];
        if (is_array($config['valuePicker']['items'] ?? false)) {
            $valuePickerConfiguration = [
                'mode' => $config['valuePicker']['mode'] ?? 'replace',
                'linked-field' => '[data-formengine-input-name="' . $itemName . '"]',
            ];
            $valuePickerAttributes = array_merge(
                [
                    'class' => 'form-select form-control-adapt',
                ],
                $this->getOnFieldChangeAttrs('change', $parameterArray['fieldChangeFunc'] ?? [])
            );

            $valuePickerHtml[] = '<typo3-formengine-valuepicker ' . GeneralUtility::implodeAttributes($valuePickerConfiguration, true) . '>';
            $valuePickerHtml[] = '<select ' . GeneralUtility::implodeAttributes($valuePickerAttributes, true) . '>';
            $valuePickerHtml[] = '<option></option>';
            foreach ($config['valuePicker']['items'] as $item) {
                $valuePickerHtml[] = '<option value="' . htmlspecialchars((string)$item[1]) . '">' . htmlspecialchars($languageService->sL($item[0])) . '</option>';
            }
            $valuePickerHtml[] = '</select>';
            $valuePickerHtml[] = '</typo3-formengine-valuepicker>';

            $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create('@typo3/backend/form-engine/field-wizard/value-picker.js');
        }

        $valueSliderHtml = [];
        if (is_array($config['slider'] ?? false)) {
            if ($format === 'decimal') {
                $itemValue = (float)$itemValue;
            } else {
                $itemValue = (int)$itemValue;
            }
            $valueSliderConfiguration = [
                'precision' => (string)$precision,
                'format' =>  $format,
                'linked-field' => '[data-formengine-input-name="' . $itemName . '"]',
            ];
            $rangeAttributes = [
                'type' => 'range',
                'class' => 'slider',
                'min' => (string)(int)($config['range']['lower'] ?? 0),
                'max' => (string)(int)($config['range']['upper'] ?? 10000),
                'step' => (string)($config['slider']['step'] ?? 1),
                'style' => 'width: ' . (int)($config['slider']['width'] ?? 400) . 'px',
                'title' => (string)$itemValue,
                'value' => (string)$itemValue,
            ];

            $valueSliderHtml[] = '<typo3-formengine-valueslider ' . GeneralUtility::implodeAttributes($valueSliderConfiguration, true) . '>';
            $valueSliderHtml[] = '<div class="slider-wrapper">';
            $valueSliderHtml[] = '<input ' . GeneralUtility::implodeAttributes($rangeAttributes, true) . '>';
            $valueSliderHtml[] = '</div>';
            $valueSliderHtml[] = '</typo3-formengine-valuepicker>';

            $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create('@typo3/backend/form-engine/field-wizard/value-slider.js');
        }

        $fieldControlResult = $this->renderFieldControl();
        $fieldControlHtml = $fieldControlResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldControlResult, false);

        $fieldWizardResult = $this->renderFieldWizard();
        $fieldWizardHtml = $fieldWizardResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldWizardResult, false);

        if (isset($config['range']['lower'])) {
            $attributes['min'] = (string)(int)$config['range']['lower'];
        }
        if (isset($config['range']['upper'])) {
            $attributes['max'] = (string)(int)$config['range']['upper'];
        }

        if ($format === 'decimal') {
            $attributes['step'] = '0.' . str_repeat('0', $precision - 1) . '1';
        }

        $mainFieldHtml = [];
        $mainFieldHtml[] = '<div class="form-control-wrap" style="max-width: ' . $width . 'px">';
        $mainFieldHtml[] =  '<div class="form-wizards-wrap">';
        $mainFieldHtml[] =      '<div class="form-wizards-element">';
        $mainFieldHtml[] =          '<input type="number" ' . GeneralUtility::implodeAttributes($attributes, true) . ' />';
        $mainFieldHtml[] =          '<input type="hidden" name="' . $itemName . '" value="' . htmlspecialchars((string)$itemValue) . '" />';
        $mainFieldHtml[] =      '</div>';
        if (!empty($valuePickerHtml) || !empty($valueSliderHtml) || !empty($fieldControlHtml)) {
            $mainFieldHtml[] =      '<div class="form-wizards-items-aside form-wizards-items-aside--field-control">';
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

        $nullControlNameEscaped = htmlspecialchars('control[active][' . $table . '][' . $this->data['databaseRow']['uid'] . '][' . $fieldName . ']');

        $fullElement = $mainFieldHtml;
        if ($this->hasNullCheckboxButNoPlaceholder()) {
            $checked = $itemValue !== null ? ' checked="checked"' : '';
            $fullElement = [];
            $fullElement[] = '<div class="t3-form-field-disable"></div>';
            $fullElement[] = '<div class="form-check t3-form-field-eval-null-checkbox">';
            $fullElement[] =     '<input type="hidden" name="' . $nullControlNameEscaped . '" value="0" />';
            $fullElement[] =     '<input type="checkbox" class="form-check-input" name="' . $nullControlNameEscaped . '" id="' . $nullControlNameEscaped . '" value="1"' . $checked . ' />';
            $fullElement[] =     '<label class="form-check-label" for="' . $nullControlNameEscaped . '">';
            $fullElement[] =         $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.nullCheckbox');
            $fullElement[] =     '</label>';
            $fullElement[] = '</div>';
            $fullElement[] = $mainFieldHtml;
            $fullElement = implode(LF, $fullElement);
        } elseif ($this->hasNullCheckboxWithPlaceholder()) {
            $checked = $itemValue !== null ? ' checked="checked"' : '';
            $placeholder = $shortenedPlaceholder = trim((string)($config['placeholder'] ?? ''));
            if ($placeholder !== '') {
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
            $fullElement[] = '<div class="form-check t3js-form-field-eval-null-placeholder-checkbox">';
            $fullElement[] =     '<input type="hidden" name="' . $nullControlNameEscaped . '" value="0" />';
            $fullElement[] =     '<input type="checkbox" class="form-check-input" name="' . $nullControlNameEscaped . '" id="' . $nullControlNameEscaped . '" value="1"' . $checked . ' />';
            $fullElement[] =     '<label class="form-check-label" for="' . $nullControlNameEscaped . '">';
            $fullElement[] =         $overrideLabel;
            $fullElement[] =     '</label>';
            $fullElement[] = '</div>';
            $fullElement[] = '<div class="t3js-formengine-placeholder-placeholder">';
            $fullElement[] =    '<div class="form-control-wrap" style="max-width:' . $width . 'px">';
            $fullElement[] =        '<input type="text" class="form-control" disabled="disabled" value="' . htmlspecialchars($shortenedPlaceholder) . '" />';
            $fullElement[] =    '</div>';
            $fullElement[] = '</div>';
            $fullElement[] = '<div class="t3js-formengine-placeholder-formfield">';
            $fullElement[] =    $mainFieldHtml;
            $fullElement[] = '</div>';
            $fullElement = implode(LF, $fullElement);
        }

        $resultArray['html'] = $renderedLabel . '
            <div class="formengine-field-item t3js-formengine-field-item">
                ' . $fieldInformationHtml . $fullElement . '
            </div>';

        return $resultArray;
    }
}
