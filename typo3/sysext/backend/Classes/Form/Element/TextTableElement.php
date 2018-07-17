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

/**
 * Generation of TCEform elements of the type "text"
 */
class TextTableElement extends AbstractFormElement
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
     * This renderType adds the "table wizard icon" by default (on not new records)
     *
     * @var array
     */
    protected $defaultFieldControl = [
        'tableWizard' => [
            'renderType' => 'tableWizard',
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
     * This will render a <textarea> with table wizard
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $backendUser = $this->getBackendUserAuthentication();

        $parameterArray = $this->data['parameterArray'];
        $resultArray = $this->initializeResultArray();

        $itemValue = $parameterArray['itemFormElValue'];
        $config = $parameterArray['fieldConf']['config'];
        $evalList = GeneralUtility::trimExplode(',', $config['eval'], true);
        $cols = MathUtility::forceIntegerInRange($config['cols'] ?: $this->defaultInputWidth, $this->minimumInputWidth, $this->maxInputWidth);
        $width = $this->formMaxWidth($cols);

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

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        if ($config['readOnly']) {
            $html = [];
            $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
            $html[] =   $fieldInformationHtml;
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

        $fieldControlResult = $this->renderFieldControl();
        $fieldControlHtml = $fieldControlResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldControlResult, false);

        $fieldWizardResult = $this->renderFieldWizard();
        $fieldWizardHtml = $fieldWizardResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldWizardResult, false);

        $html = [];
        $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
        $html[] =   $fieldInformationHtml;
        $html[] =   '<div class="form-control-wrap" style="max-width: ' . $width . 'px">';
        $html[] =       '<div class="form-wizards-wrap">';
        $html[] =           '<div class="form-wizards-element">';
        $html[] =               '<textarea ' . GeneralUtility::implodeAttributes($attributes, true) . '>' . htmlspecialchars($itemValue) . '</textarea>';
        $html[] =           '</div>';
        if (!empty($fieldControlHtml)) {
            $html[] =           '<div class="form-wizards-items-aside">';
            $html[] =               '<div class="btn-group">';
            $html[] =                   $fieldControlHtml;
            $html[] =               '</div>';
            $html[] =           '</div>';
        }
        if (!empty($fieldWizardHtml)) {
            $html[] = '<div class="form-wizards-items-bottom">';
            $html[] = $fieldWizardHtml;
            $html[] = '</div>';
        }
        $html[] =       '</div>';
        $html[] =   '</div>';
        $html[] = '</div>';

        $resultArray['html'] = implode(LF, $html);
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
