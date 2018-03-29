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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Generation of TCEform elements of the type "text"
 */
class TextElement extends AbstractFormElement
{
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
    public function render()
    {
        $languageService = $this->getLanguageService();
        $backendUser = $this->getBackendUserAuthentication();

        $table = $this->data['tableName'];
        $fieldName = $this->data['fieldName'];
        $row = $this->data['databaseRow'];
        $parameterArray = $this->data['parameterArray'];
        $resultArray = $this->initializeResultArray();

        $itemValue = $parameterArray['itemFormElValue'];
        $config = $parameterArray['fieldConf']['config'];
        $evalList = GeneralUtility::trimExplode(',', $config['eval'], true);
        $cols = MathUtility::forceIntegerInRange($config['cols'] ?: $this->defaultInputWidth, $this->minimumInputWidth, $this->maxInputWidth);
        $width = $this->formMaxWidth($cols);
        $nullControlNameEscaped = htmlspecialchars('control[active][' . $table . '][' . $row['uid'] . '][' . $fieldName . ']');

        // Setting number of rows
        $rows = MathUtility::forceIntegerInRange($config['rows'] ?: 5, 1, 20);
        $originalRows = $rows;
        $itemFormElementValueLength = strlen($itemValue);
        if ($itemFormElementValueLength > $this->charactersPerRow * 2) {
            $rows = MathUtility::forceIntegerInRange(
                round($itemFormElementValueLength / $this->charactersPerRow),
                count(explode(LF, $itemValue)),
                20
            );
            if ($rows < $originalRows) {
                $rows = $originalRows;
            }
        }

        if ($config['readOnly']) {
            $html = [];
            $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
            $html[] =   '<div class="form-wizards-wrap">';
            $html[] =       '<div class="form-wizards-element">';
            $html[] =           '<div class="form-control-wrap" style="max-width: ' . $width . 'px">';
            $html[] =               '<textarea class="form-control" rows="' . $rows . '" disabled>';
            $html[] =                   htmlspecialchars($itemValue);
            $html[] =               '</textarea>';
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
            // There is a similar hook for "evaluateFieldValue" in DataHandler and InputTextElement
            if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][$func])) {
                if (class_exists($func)) {
                    $evalObj = GeneralUtility::makeInstance($func);
                    if (method_exists($evalObj, 'deevaluateFieldValue')) {
                        $_params = [
                            'value' => $itemValue
                        ];
                        $itemValue = $evalObj->deevaluateFieldValue($_params);
                    }
                }
            }
        }

        $attributes = [
            'id' => StringUtility::getUniqueId('formengine-textarea-'),
            'name' => htmlspecialchars($parameterArray['itemFormElName']),
            'data-formengine-validation-rules' => $this->getValidationDataAsJsonString($config),
            'data-formengine-input-name' => htmlspecialchars($parameterArray['itemFormElName']),
            'rows' => $rows,
            'wrap' => $config['wrap'] ?: 'virtual',
            'onChange' => implode('', $parameterArray['fieldChangeFunc']),
        ];
        $classes = [
            'form-control',
            't3js-formengine-textarea',
            'formengine-textarea',
        ];
        if ($config['fixedFont']) {
            $classes[] = 'text-monospace';
        }
        if ($config['enableTabulator']) {
            $classes[] = 't3js-enable-tab';
        }
        $attributes['class'] = implode(' ', $classes);
        $maximumHeight = (int)$backendUser->uc['resizeTextareas_MaxHeight'];
        if ($maximumHeight > 0) {
            // add the max-height from the users' preference to it
            $attributes['style'] = 'max-height: ' . $maximumHeight . 'px';
        }
        if (isset($config['max']) && (int)$config['max'] > 0) {
            $attributes['maxlength'] = (int)$config['max'];
        }
        if (!empty($config['placeholder'])) {
            $attributes['placeholder'] = htmlspecialchars(trim($config['placeholder']));
        }

        $valuePickerHtml = [];
        if (isset($config['valuePicker']['items']) && is_array($config['valuePicker']['items'])) {
            $mode = $config['valuePicker']['mode'] ?? '';
            $itemName = $parameterArray['itemFormElName'];
            $fieldChangeFunc = $parameterArray['fieldChangeFunc'];
            if ($mode === 'append') {
                $assignValue = 'document.querySelectorAll(' . GeneralUtility::quoteJSvalue('[data-formengine-input-name="' . $itemName . '"]') . ')[0]'
                    . '.value=\'\'+this.options[this.selectedIndex].value+document.editform[' . GeneralUtility::quoteJSvalue($itemName) . '].value';
            } elseif ($mode === 'prepend') {
                $assignValue = 'document.querySelectorAll(' . GeneralUtility::quoteJSvalue('[data-formengine-input-name="' . $itemName . '"]') . ')[0]'
                    . '.value+=\'\'+this.options[this.selectedIndex].value';
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

        $legacyWizards = $this->renderWizards();
        $legacyFieldControlHtml = implode(LF, $legacyWizards['fieldControl']);
        $legacyFieldWizardHtml = implode(LF, $legacyWizards['fieldWizard']);

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        $fieldControlResult = $this->renderFieldControl();
        $fieldControlHtml = $legacyFieldControlHtml . $fieldControlResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldControlResult, false);

        $fieldWizardResult = $this->renderFieldWizard();
        $fieldWizardHtml = $legacyFieldWizardHtml . $fieldWizardResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldWizardResult, false);

        $mainFieldHtml = [];
        $mainFieldHtml[] = '<div class="form-control-wrap" style="max-width: ' . $width . 'px">';
        $mainFieldHtml[] =  '<div class="form-wizards-wrap">';
        $mainFieldHtml[] =      '<div class="form-wizards-element">';
        $mainFieldHtml[] =          '<textarea ' . GeneralUtility::implodeAttributes($attributes, true) . '>' . htmlspecialchars($itemValue) . '</textarea>';
        $mainFieldHtml[] =      '</div>';
        $mainFieldHtml[] =      '<div class="form-wizards-items-aside">';
        $mainFieldHtml[] =          '<div class="btn-group">';
        $mainFieldHtml[] =              implode(LF, $valuePickerHtml);
        $mainFieldHtml[] =              $fieldControlHtml;
        $mainFieldHtml[] =          '</div>';
        $mainFieldHtml[] =      '</div>';
        $mainFieldHtml[] =      '<div class="form-wizards-items-bottom">';
        $mainFieldHtml[] =          $fieldWizardHtml;
        $mainFieldHtml[] =      '</div>';
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
            $fullElement[] =         $languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.nullCheckbox');
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
                        $languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.placeholder.override'),
                        '<span title="' . htmlspecialchars($placeholder) . '">' . htmlspecialchars($shortenedPlaceholder) . '</span>'
                    );
                } else {
                    $overrideLabel = sprintf(
                        $languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.placeholder.override'),
                        htmlspecialchars($placeholder)
                    );
                }
            } else {
                $overrideLabel = $languageService->sL(
                    'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.placeholder.override_not_available'
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
            $fullElement[] =        '<textarea';
            $fullElement[] =            ' class="form-control formengine-textarea' . (isset($config['fixedFont']) ? ' text-monospace'  : '') . '"';
            $fullElement[] =            ' disabled="disabled"';
            $fullElement[] =            ' rows="' . htmlspecialchars($attributes['rows']) . '"';
            $fullElement[] =            ' wrap="' . htmlspecialchars($attributes['wrap']) . '"';
            $fullElement[] =            isset($attributes['style']) ? ' style="' . htmlspecialchars($attributes['style']) . '"' : '';
            $fullElement[] =            isset($attributes['maxlength']) ? ' maxlength="' . htmlspecialchars($attributes['maxlength']) . '"' : '';
            $fullElement[] =        '>';
            $fullElement[] =            htmlspecialchars($shortenedPlaceholder);
            $fullElement[] =        '</textarea>';
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
