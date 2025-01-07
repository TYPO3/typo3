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
 * General type=text element
 *
 * The InputTextElement renders a html textarea field.
 */
class TextElement extends AbstractFormElement
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
     * The number of chars expected per row when the height of a text area field is
     * automatically calculated based on the number of characters found in the field content.
     *
     * @var int
     */
    protected $charactersPerRow = 40;

    /**
     * This will render a <textarea>
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render(): array
    {
        $table = $this->data['tableName'];
        $fieldName = $this->data['fieldName'];
        $parameterArray = $this->data['parameterArray'];
        $resultArray = $this->initializeResultArray();

        $itemValue = $parameterArray['itemFormElValue'];
        $config = $parameterArray['fieldConf']['config'];
        $width = null;
        if ($config['cols'] ?? false) {
            $width = $this->formMaxWidth(MathUtility::forceIntegerInRange($config['cols'], $this->minimumInputWidth, $this->maxInputWidth));
        }
        $fieldId = StringUtility::getUniqueId('formengine-textarea-');
        $itemName = (string)$parameterArray['itemFormElName'];
        $renderedLabel = $this->renderLabel($fieldId);

        // Setting number of rows
        $rows = MathUtility::forceIntegerInRange(($config['rows'] ?? 5) ?: 5, 1, 20);
        $originalRows = $rows;
        $itemFormElementValueLength = strlen((string)$itemValue);
        if ($itemFormElementValueLength > ($this->charactersPerRow * 2)) {
            $rows = MathUtility::forceIntegerInRange(
                (int)round($itemFormElementValueLength / $this->charactersPerRow),
                count(explode(LF, (string)$itemValue)),
                20
            );
            if ($rows < $originalRows) {
                $rows = $originalRows;
            }
        }

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        if ($config['readOnly'] ?? false) {
            $html = [];
            $html[] = $renderedLabel;
            $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
            $html[] =   $fieldInformationHtml;
            $html[] =   '<div class="form-wizards-wrap">';
            $html[] =       '<div class="form-wizards-item-element">';
            $html[] =           '<div class="form-control-wrap"' . ($width ? ' style="max-width: ' . $width . 'px">' : '>');
            $html[] =               GeneralUtility::renderTextarea((string)$itemValue, ['class' => 'form-control', 'id' => $fieldId, 'name' => $itemName, 'rows' => $rows, 'disabled' => 'disabled']);
            $html[] =           '</div>';
            $html[] =       '</div>';
            $html[] =   '</div>';
            $html[] = '</div>';
            $resultArray['html'] = implode(LF, $html);
            return $resultArray;
        }

        $languageService = $this->getLanguageService();

        // @todo: The whole eval handling is a mess and needs refactoring - Especially for this element,
        //        since the resolved $evalList is currently not used at all, because FormEngineValidation
        //        does not support eval for <textarea> elements.
        $evalList = GeneralUtility::trimExplode(',', $config['eval'] ?? '', true);
        foreach ($evalList as $func) {
            // @todo: This is ugly: The code should find out on it's own whether an eval definition is a
            // @todo: keyword like "date", or a class reference. The global registration could be dropped then
            // Pair hook to the one in \TYPO3\CMS\Core\DataHandling\DataHandler::checkValue_input_Eval()
            // There is a similar hook for "evaluateFieldValue" in DataHandler and InputTextElement
            if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][$func])) {
                if (class_exists($func)) {
                    $evalObj = GeneralUtility::makeInstance($func);
                    if (method_exists($evalObj, 'deevaluateFieldValue')) {
                        $_params = [
                            'value' => $itemValue,
                        ];
                        $itemValue = $evalObj->deevaluateFieldValue($_params);
                    }
                }
            }
        }

        $attributes = array_merge(
            [
                'id' => $fieldId,
                'name' => $itemName,
                'data-formengine-validation-rules' => $this->getValidationDataAsJsonString($config),
                'data-formengine-input-name' => $itemName,
                'rows' => (string)$rows,
                'wrap' => (string)(($config['wrap'] ?? 'virtual') ?: 'virtual'),
            ],
            $this->getOnFieldChangeAttrs('change', $parameterArray['fieldChangeFunc'] ?? [])
        );
        $classes = [
            'form-control',
            't3js-formengine-textarea',
            'formengine-textarea',
        ];
        if ($config['fixedFont'] ?? false) {
            $classes[] = 'font-monospace';
        }
        if ($config['enableTabulator'] ?? false) {
            $classes[] = 't3js-enable-tab';
        }
        $attributes['class'] = implode(' ', $classes);

        $maxLength = (int)($config['max'] ?? 0);
        if ($maxLength > 0) {
            $attributes['maxlength'] = (string)$maxLength;
        }
        $minlength = (int)($config['min'] ?? 0);
        if ($minlength > 0 && ($maxLength === 0 || $minlength <= $maxLength)) {
            $attributes['minlength'] = (string)$minlength;
        }
        if (!empty($config['placeholder'])) {
            $attributes['placeholder'] = trim($config['placeholder']);
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
                $valuePickerHtml[] = '<option value="' . htmlspecialchars($item[1]) . '">' . htmlspecialchars($languageService->sL($item[0])) . '</option>';
            }
            $valuePickerHtml[] = '</select>';
            $valuePickerHtml[] = '</typo3-formengine-valuepicker>';

            $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create('@typo3/backend/form-engine/field-wizard/value-picker.js');
        }

        $fieldControlResult = $this->renderFieldControl();
        $fieldControlHtml = $fieldControlResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldControlResult, false);

        $fieldWizardResult = $this->renderFieldWizard();
        $fieldWizardHtml = $fieldWizardResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldWizardResult, false);

        $mainFieldHtml = [];
        $mainFieldHtml[] = '<div class="form-control-wrap"' . ($width ? ' style="max-width: ' . $width . 'px">' : '>');
        $mainFieldHtml[] =  '<div class="form-wizards-wrap">';
        $mainFieldHtml[] =      '<div class="form-wizards-item-element">';
        $mainFieldHtml[] =          GeneralUtility::renderTextarea((string)$itemValue, $attributes);
        $mainFieldHtml[] =      '</div>';
        if (!empty($valuePickerHtml) || !empty($fieldControlHtml)) {
            $mainFieldHtml[] =      '<div class="form-wizards-item-aside form-wizards-item-aside--field-control">';
            $mainFieldHtml[] =          '<div class="btn-group">';
            $mainFieldHtml[] =              implode(LF, $valuePickerHtml);
            $mainFieldHtml[] =              $fieldControlHtml;
            $mainFieldHtml[] =          '</div>';
            $mainFieldHtml[] =      '</div>';
        }
        if (!empty($fieldWizardHtml)) {
            $mainFieldHtml[] = '<div class="form-wizards-item-bottom">';
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
            $placeholder = $shortenedPlaceholder = (string)($config['placeholder'] ?? '');
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
            $textareaAttributes = [
                'class' => 'form-control formengine-textarea' . (isset($config['fixedFont']) ? ' font-monospace' : ''),
                'disabled' => 'disabled',
                'rows' => $attributes['rows'],
                'wrap' => $attributes['wrap'],
                ...(isset($attributes['style']) ? ['style' => $attributes['style']] : []),
                ...(isset($attributes['maxlength']) ? ['maxlength' => $attributes['maxlength']] : []),
            ];
            $fullElement = [];
            $fullElement[] = '<div class="form-check t3js-form-field-eval-null-placeholder-checkbox">';
            $fullElement[] =     '<input type="hidden" name="' . $nullControlNameEscaped . '" value="0" />';
            $fullElement[] =     '<input type="checkbox" class="form-check-input" name="' . $nullControlNameEscaped . '" id="' . $nullControlNameEscaped . '" value="1"' . $checked . ' />';
            $fullElement[] =     '<label class="form-check-label" for="' . $nullControlNameEscaped . '">';
            $fullElement[] =         $overrideLabel;
            $fullElement[] =     '</label>';
            $fullElement[] = '</div>';
            $fullElement[] = '<div class="t3js-formengine-placeholder-placeholder">';
            $fullElement[] =    '<div class="form-control-wrap"' . ($width ? ' style="max-width: ' . $width . 'px">' : '>');
            $fullElement[] =        GeneralUtility::renderTextarea($shortenedPlaceholder, $textareaAttributes);
            $fullElement[] =    '</div>';
            $fullElement[] = '</div>';
            $fullElement[] = '<div class="t3js-formengine-placeholder-formfield">';
            $fullElement[] =    $mainFieldHtml;
            $fullElement[] = '</div>';
            $fullElement = implode(LF, $fullElement);
        }

        $resultArray['html'] = $renderedLabel . '
             <typo3-formengine-element-text class="formengine-field-item t3js-formengine-field-item" recordFieldId="' . htmlspecialchars($fieldId) . '">
                ' . $fieldInformationHtml . '
                ' . $fullElement . '
            </typo3-formengine-element-text>';

        $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create('@typo3/backend/form-engine/element/text-element.js');

        return $resultArray;
    }
}
