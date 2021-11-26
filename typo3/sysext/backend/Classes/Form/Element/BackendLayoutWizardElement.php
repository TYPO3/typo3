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

use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Backend layout element
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class BackendLayoutWizardElement extends AbstractFormElement
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
     * @var array
     */
    protected $rows = [];

    /**
     * @var int
     */
    protected $colCount = 0;

    /**
     * @var int
     */
    protected $rowCount = 0;

    /**
     * @return array
     */
    public function render()
    {
        $lang = $this->getLanguageService();
        $resultArray = $this->initializeResultArray();
        $this->init();

        $row = $this->data['databaseRow'];
        $tca = $this->data['processedTca'];
        $parameterArray = $this->data['parameterArray'];

        // readOnly is not supported as columns config but might be set by SingleFieldContainer in case
        // "l10n_display" is set to "defaultAsReadonly". To prevent misbehaviour for fields, which falsely
        // set this, we also check for "defaultAsReadonly" being set and whether the record is an overlay.
        $readOnly = ($parameterArray['fieldConf']['config']['readOnly'] ?? false)
            && ($tca['ctrl']['transOrigPointerField'] ?? false)
            && ($row[$tca['ctrl']['transOrigPointerField']][0] ?? $row[$tca['ctrl']['transOrigPointerField']] ?? false)
            && GeneralUtility::inList($parameterArray['fieldConf']['l10n_display'] ?? '', 'defaultAsReadonly');

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        $fieldWizardResult = $this->renderFieldWizard();
        $fieldWizardHtml = $fieldWizardResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldWizardResult, false);

        // Use CodeMirror if available
        if (ExtensionManagementUtility::isLoaded('t3editor')) {
            $codeMirrorConfig = [
                'label' => $lang->getLL('buttons.pageTsConfig'),
                'panel' => 'top',
                'mode' => 'TYPO3/CMS/T3editor/Mode/typoscript/typoscript',
                'nolazyload' => 'true',
                'options' => GeneralUtility::jsonEncodeForHtmlAttribute([
                    'readOnly' => 'true',
                ], false),
            ];
            $editor = '
                <typo3-t3editor-codemirror class="t3js-grideditor-preview-config grideditor-preview" ' . GeneralUtility::implodeAttributes($codeMirrorConfig, true) . '>
                    <textarea class="t3js-tsconfig-preview-area"></textarea>
                </typo3-t3editor-codemirror>';

            $resultArray['stylesheetFiles'][] = 'EXT:t3editor/Resources/Public/JavaScript/Contrib/codemirror/lib/codemirror.css';
            $resultArray['stylesheetFiles'][] = 'EXT:t3editor/Resources/Public/Css/t3editor.css';
            $resultArray['requireJsModules'][] = JavaScriptModuleInstruction::forRequireJS('TYPO3/CMS/T3editor/Element/CodeMirrorElement');
        } else {
            $editor = '
                <label>' . htmlspecialchars($lang->getLL('buttons.pageTsConfig')) . '</label>
                <div class="t3js-grideditor-preview-config grideditor-preview">
                    <textarea class="t3js-tsconfig-preview-area form-control" rows="25" readonly></textarea>
                </div>';
        }

        $json = (string)json_encode($this->rows, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
        $html = [];
        $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
        $html[] =   $fieldInformationHtml;
        $html[] =   '<div class="form-control-wrap">';
        $html[] =       '<div class="form-wizards-wrap">';
        $html[] =           '<div class="form-wizards-element">';
        $html[] =               '<input';
        $html[] =                   ' type="hidden"';
        $html[] =                   ' name="' . htmlspecialchars($this->data['parameterArray']['itemFormElName']) . '"';
        $html[] =                   ' value="' . htmlspecialchars($this->data['parameterArray']['itemFormElValue']) . '"';
        $html[] =                   '/>';
        $html[] =               '<table class="grideditor table table-bordered">';
        if (!$readOnly) {
            $html[] =                   '<tr>';
            $html[] =                       '<td colspan="2" align="center">';
            $html[] =                           '<div class="btn-group">';
            $html[] =                               '<a class="btn btn-default btn-sm t3js-grideditor-removerow-top" href="#"';
            $html[] =                                   ' title="' . htmlspecialchars($lang->getLL('grid_removeRow')) . '">';
            $html[] =                                   '<i class="fa fa-fw fa-minus"></i>';
            $html[] =                               '</a>';
            $html[] =                               '<a class="btn btn-default btn-sm t3js-grideditor-addrow-top" href="#"';
            $html[] =                                   ' title="' . htmlspecialchars($lang->getLL('grid_addRow')) . '">';
            $html[] =                                   '<i class="fa fa-fw fa-plus"></i>';
            $html[] =                               '</a>';
            $html[] =                           '</div>';
            $html[] =                       '</td>';
            $html[] =                   '</tr>';
        }
        $html[] =                   '<tr>';
        $html[] =                       '<td class="editor_cell">';
        $html[] =                           '<div';
        $html[] =                               ' id="editor"';
        $html[] =                               ' class="t3js-grideditor"';
        $html[] =                               ' data-data="' . htmlspecialchars($json) . '"';
        $html[] =                               ' data-rowcount="' . (int)$this->rowCount . '"';
        $html[] =                               ' data-colcount="' . (int)$this->colCount . '"';
        $html[] =                               ' data-readonly="' . ($readOnly ? '1' : '0') . '"';
        $html[] =                               ' data-field="' . htmlspecialchars($this->data['parameterArray']['itemFormElName']) . '"';
        $html[] =                           '>';
        $html[] =                           '</div>';
        $html[] =                       '</td>';
        if (!$readOnly) {
            $html[] =                       '<td>';
            $html[] =                           '<div class="btn-group-vertical">';
            $html[] =                               '<a class="btn btn-default btn-sm t3js-grideditor-addcolumn" href="#"';
            $html[] =                                   ' title="' . htmlspecialchars($lang->getLL('grid_addColumn')) . '">';
            $html[] =                                   '<i class="fa fa-fw fa-plus"></i>';
            $html[] =                               '</a>';
            $html[] =                               '<a class="btn btn-default btn-sm t3js-grideditor-removecolumn" href="#"';
            $html[] =                                   ' title="' . htmlspecialchars($lang->getLL('grid_removeColumn')) . '">';
            $html[] =                                   '<i class="fa fa-fw fa-minus"></i>';
            $html[] =                               '</a>';
            $html[] =                           '</div>';
            $html[] =                       '</td>';
            $html[] =                   '</tr>';
            $html[] =                   '<tr>';
            $html[] =                       '<td colspan="2" align="center">';
            $html[] =                           '<div class="btn-group">';
            $html[] =                               '<a class="btn btn-default btn-sm t3js-grideditor-addrow-bottom" href="#"';
            $html[] =                                   ' title="' . htmlspecialchars($lang->getLL('grid_addRow')) . '">';
            $html[] =                                   '<i class="fa fa-fw fa-plus"></i>';
            $html[] =                               '</a>';
            $html[] =                               '<a class="btn btn-default btn-sm t3js-grideditor-removerow-bottom" href="#"';
            $html[] =                                   ' title="' . htmlspecialchars($lang->getLL('grid_removeRow')) . '">';
            $html[] =                                   '<i class="fa fa-fw fa-minus"></i>';
            $html[] =                               '</a>';
            $html[] =                           '</div>';
            $html[] =                       '</td>';
        }
        $html[] =                   '</tr>';
        $html[] =                   '<tr>';
        $html[] =                       '<td colspan="2">';
        $html[] =                           $editor;
        $html[] =                       '</td>';
        $html[] =                   '</tr>';
        $html[] =               '</table>';
        $html[] =           '</div>';
        if (!$readOnly && !empty($fieldWizardHtml)) {
            $html[] = '<div class="form-wizards-items-bottom">';
            $html[] = $fieldWizardHtml;
            $html[] = '</div>';
        }
        $html[] =       '</div>';
        $html[] =   '</div>';
        $html[] = '</div>';

        $html = implode(LF, $html);
        $resultArray['html'] = $html;
        $resultArray['requireJsModules'][] = JavaScriptModuleInstruction::forRequireJS(
            'TYPO3/CMS/Backend/GridEditor',
            'GridEditor'
        )->instance();
        $resultArray['additionalInlineLanguageLabelFiles'][] = 'EXT:core/Resources/Private/Language/locallang_wizards.xlf';
        $resultArray['additionalInlineLanguageLabelFiles'][] = 'EXT:backend/Resources/Private/Language/locallang.xlf';

        return $resultArray;
    }

    /**
     * Initialize wizard
     */
    protected function init()
    {
        // Initialize default values
        $rows = [[['colspan' => 1, 'rowspan' => 1, 'spanned' => 0, 'name' => '0x0']]];
        $colCount = 1;
        $rowCount = 1;

        if (!empty($this->data['parameterArray']['itemFormElValue'])) {
            // load TS parser in case we already have a config (e.g. database value or default from TCA)
            $parser = GeneralUtility::makeInstance(TypoScriptParser::class);
            $parser->parse($this->data['parameterArray']['itemFormElValue']);
            if (is_array($parser->setup['backend_layout.'] ?? false)) {
                // Only evaluate, in case the "backend_layout." array exists on root level
                $data = $parser->setup['backend_layout.'];
                $rows = [];
                $colCount = $data['colCount'];
                $rowCount = $data['rowCount'];
                $dataRows = $data['rows.'];
                $spannedMatrix = [];
                for ($i = 1; $i <= $rowCount; $i++) {
                    $cells = [];
                    $row = array_shift($dataRows);
                    $columns = $row['columns.'];
                    for ($j = 1; $j <= $colCount; $j++) {
                        $cellData = [];
                        if (!($spannedMatrix[$i][$j] ?? false)) {
                            if (is_array($columns) && !empty($columns)) {
                                $column = array_shift($columns);
                                if (isset($column['colspan'])) {
                                    $cellData['colspan'] = (int)$column['colspan'];
                                    $columnColSpan = (int)$column['colspan'];
                                    if (isset($column['rowspan'])) {
                                        $columnRowSpan = (int)$column['rowspan'];
                                        for ($spanRow = 0; $spanRow < $columnRowSpan; $spanRow++) {
                                            for ($spanColumn = 0; $spanColumn < $columnColSpan; $spanColumn++) {
                                                $spannedMatrix[$i + $spanRow][$j + $spanColumn] = 1;
                                            }
                                        }
                                    } else {
                                        for ($spanColumn = 0; $spanColumn < $columnColSpan; $spanColumn++) {
                                            $spannedMatrix[$i][$j + $spanColumn] = 1;
                                        }
                                    }
                                } else {
                                    $cellData['colspan'] = 1;
                                    if (isset($column['rowspan'])) {
                                        $columnRowSpan = (int)$column['rowspan'];
                                        for ($spanRow = 0; $spanRow < $columnRowSpan; $spanRow++) {
                                            $spannedMatrix[$i + $spanRow][$j] = 1;
                                        }
                                    }
                                }
                                if (isset($column['rowspan'])) {
                                    $cellData['rowspan'] = (int)$column['rowspan'];
                                } else {
                                    $cellData['rowspan'] = 1;
                                }
                                if (isset($column['name'])) {
                                    $cellData['name'] = $column['name'];
                                }
                                if (isset($column['colPos'])) {
                                    $cellData['column'] = (int)$column['colPos'];
                                }
                            }
                        } else {
                            $cellData = ['colspan' => 1, 'rowspan' => 1, 'spanned' => 1];
                        }
                        $cells[] = $cellData;
                    }
                    $rows[] = $cells;
                    if (!empty($spannedMatrix[$i]) && is_array($spannedMatrix[$i])) {
                        ksort($spannedMatrix[$i]);
                    }
                }
            }
        }

        $this->rows = $rows;
        $this->colCount = (int)$colCount;
        $this->rowCount = (int)$rowCount;
    }
}
