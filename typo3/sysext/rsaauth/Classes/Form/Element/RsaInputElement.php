<?php
namespace TYPO3\CMS\Rsaauth\Form\Element;

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

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Generation of form element of the type rsaInput
 */
class RsaInputElement extends AbstractFormElement
{
    /**
     * Default field wizards enabled for this element.
     *
     * @var array
     */
    protected $defaultFieldWizard = [
        'otherLanguageContent' => [
            'renderType' => 'otherLanguageContent',
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
        $fieldName = $this->data['fieldName'];
        $parameterArray = $this->data['parameterArray'];
        $resultArray = $this->initializeResultArray();
        $resultArray['requireJsModules'] = ['TYPO3/CMS/Rsaauth/RsaEncryptionModule'];

        $itemValue = $parameterArray['itemFormElValue'];
        $config = $parameterArray['fieldConf']['config'];
        $size = MathUtility::forceIntegerInRange($config['size'] ?: $this->defaultInputWidth, $this->minimumInputWidth, $this->maxInputWidth);
        $evalList = GeneralUtility::trimExplode(',', $config['eval'], true);
        $width = (int)$this->formMaxWidth($size);
        $isPasswordField = in_array('password', $evalList, true);

        if ($config['readOnly']) {
            // Early return for read only fields
            if ($isPasswordField) {
                $itemValue = $itemValue ? '*********' : '';
            }
            $html = [];
            $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
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
        $evalList = array_filter($evalList, function ($value) {
            return $value !== 'password';
        });

        $attributes = [
            'type' => 'text',
            'id' => StringUtility::getUniqueId('formengine-input-'),
            'value' => '',
            'class' => implode(' ', [
                'form-control',
                't3js-clearable',
                'hasDefaultValue',
            ]),
            'data-formengine-validation-rules'=> $this->getValidationDataAsJsonString($config),
            'data-formengine-input-params' => json_encode([
                'field' => $parameterArray['itemFormElName'],
                'evalList' => implode(',', $evalList),
                'is_in' => trim($config['is_in']),
            ]),
            'data-formengine-input-name' => htmlspecialchars($parameterArray['itemFormElName']),
        ];

        if (isset($config['max']) && (int)$config['max'] > 0) {
            $attributes['maxlength'] = (int)$config['max'];
        }
        if (!empty($config['placeholder'])) {
            $attributes['placeholder'] = trim($config['placeholder']);
        }
        if (isset($config['autocomplete'])) {
            $attributes['autocomplete'] = empty($config['autocomplete']) ? 'new-' . $fieldName : 'on';
        }
        if ($isPasswordField) {
            $attributes['type'] = 'password';
            $attributes['value'] = $itemValue ? '*********' : '';
            $attributes['autocomplete'] = 'new-' . $fieldName;
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

        $html = [];
        $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
        $html[] =   $fieldInformationHtml;
        $html[] =   '<div class="form-control-wrap" style="max-width: ' . $width . 'px">';
        $html[] =       '<div class="form-wizards-wrap">';
        $html[] =           '<div class="form-wizards-element">';
        $html[] =               '<input ' . GeneralUtility::implodeAttributes($attributes, true) . ' />';
        $html[] =               '<input';
        $html[] =                   ' type="hidden"';
        $html[] =                   ' data-rsa-encryption=""';
        $html[] =                   ' id="' . $parameterArray['itemFormElID'] . '_hidden"';
        $html[] =                   ' name="' . $parameterArray['itemFormElName'] . '"';
        $html[] =                   ' value="' . htmlspecialchars($itemValue) . '"';
        $html[] =               '/>';
        $html[] =           '</div>';
        $html[] =           '<div class="form-wizards-items-aside">';
        $html[] =               '<div class="btn-group">';
        $html[] =                   $fieldControlHtml;
        $html[] =               '</div>';
        $html[] =           '</div>';
        $html[] =           '<div class="form-wizards-items-bottom">';
        $html[] =               $fieldWizardHtml;
        $html[] =           '</div>';
        $html[] =       '</div>';
        $html[] =   '</div>';
        $html[] = '</div>';

        $resultArray['html'] = implode(LF, $html);
        return $resultArray;
    }
}
