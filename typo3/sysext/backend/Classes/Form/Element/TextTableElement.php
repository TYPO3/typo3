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
 * Render the table editor
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class TextTableElement extends AbstractFormElement
{
    /**
     * Number of new rows to add in bottom of wizard
     */
    protected int $numNewRows = 1;

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
     * This will render a <textarea> with table wizard
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render(): array
    {
        $parameterArray = $this->data['parameterArray'];
        $resultArray = $this->initializeResultArray();

        $itemValue = $parameterArray['itemFormElValue'];
        $config = $parameterArray['fieldConf']['config'];
        $evalList = GeneralUtility::trimExplode(',', $config['eval'] ?? '', true);

        // Setting number of rows
        $rows = $config['rows'] ?? 0;
        $rows = MathUtility::forceIntegerInRange($rows ?: 5, 1, 20);
        $originalRows = $rows;
        $itemFormElementValueLength = strlen($itemValue);
        if ($itemFormElementValueLength > $this->charactersPerRow * 2) {
            $rows = MathUtility::forceIntegerInRange(
                (int)round($itemFormElementValueLength / $this->charactersPerRow),
                count(explode(LF, $itemValue)),
                20
            );
            if ($rows < $originalRows) {
                $rows = $originalRows;
            }
        }
        $fieldId = StringUtility::getUniqueId('formengine-textarea-');
        $itemName = (string)$parameterArray['itemFormElName'];

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        if ($config['readOnly'] ?? false) {
            $html = [];
            $html[] = $this->renderLabel($fieldId);
            $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
            $html[] =   $fieldInformationHtml;
            $html[] =   '<div class="form-wizards-wrap">';
            $html[] =       '<div class="form-wizards-item-element">';
            $html[] =           '<div class="form-control-wrap" style="overflow: auto;">';
            $html[] =               GeneralUtility::renderTextarea($itemValue, [ 'class' => 'form-control', 'id' => $fieldId, 'name' => $itemName, 'rows' => $rows, 'disabled' => 'disabled']);
            $html[] =           '</div>';
            $html[] =       '</div>';
            $html[] =   '</div>';
            $html[] = '</div>';
            $resultArray['html'] = implode(LF, $html);
            return $resultArray;
        }

        // @todo: The whole eval handling is a mess and needs refactoring
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
                'name' => htmlspecialchars($itemName),
                'data-formengine-validation-rules' => $this->getValidationDataAsJsonString($config),
                'data-formengine-input-name' => htmlspecialchars($itemName),
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

        if (isset($config['max']) && (int)$config['max'] > 0) {
            $attributes['maxlength'] = (string)(int)$config['max'];
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
        $html[] =   '<div class="form-control-wrap" style="overflow: auto">';
        $html[] =       '<div class="form-wizards-wrap">';
        $html[] =           '<div hidden>';
        $html[] =               GeneralUtility::renderTextarea($itemValue, $attributes);
        $html[] =           '</div>';
        $html[] =           $this->getTableWizard($attributes['id']);
        if (!empty($fieldControlHtml)) {
            $html[] =           '<div class="form-wizards-item-aside form-wizards-item-aside--field-control">';
            $html[] =               '<div class="btn-group">';
            $html[] =                   $fieldControlHtml;
            $html[] =               '</div>';
            $html[] =           '</div>';
        }
        if (!empty($fieldWizardHtml)) {
            $html[] = '<div class="form-wizards-item-bottom">';
            $html[] = $fieldWizardHtml;
            $html[] = '</div>';
        }
        $html[] =       '</div>';
        $html[] =   '</div>';
        $html[] = '</div>';

        $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create(
            '@typo3/backend/form-engine/element/text-table-element.js'
        )->instance($fieldId);

        $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create('@typo3/backend/form-engine/element/table-wizard-element.js');
        $resultArray['additionalInlineLanguageLabelFiles'][] = 'EXT:core/Resources/Private/Language/locallang_wizards.xlf';

        $resultArray['html'] = $this->wrapWithFieldsetAndLegend(implode(LF, $html));
        return $resultArray;
    }

    /**
     * Creates the HTML for the Table Wizard:
     *
     * @return string HTML for the table wizard
     */
    protected function getTableWizard(string $dataId): string
    {
        $row = $this->data['databaseRow'];
        $delimiter = ($row['table_delimiter'][0] ?? false) ? chr((int)$row['table_delimiter'][0]) : '|';
        $enclosure = ($row['table_enclosure'][0] ?? false) ? chr((int)$row['table_enclosure'][0]) : '';

        return sprintf(
            '<typo3-formengine-table-wizard %s></typo3-formengine-table-wizard>',
            GeneralUtility::implodeAttributes([
                'type' => 'input',
                'append-rows' => (string)$this->numNewRows,
                'selector' => '#' . $dataId,
                'delimiter' => $delimiter,
                'enclosure' => $enclosure,
            ], true)
        );
    }
}
