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

use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\TypoScript\AST\AstBuilder;
use TYPO3\CMS\Core\TypoScript\TypoScriptStringFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Backend layout element. This is used when editing backend_layout records.
 * It renders the layout wizard to manage rows and columns and shows the pseudo TypoScript result.
 *
 * Note this element does not support fancy TypoScript features like @import
 * lines and special ":=" value manipulation functions. When backend_layouts want to use
 * these, they shouldn't use table record based backend_layouts, but register backend layouts
 * using the BackendLayout/DataProviderInterface to store them in files, which obsoletes
 * table record based backend_layouts and with it this FormEngine element class.
 *
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

    protected array $rows = [];
    protected int $colCount = 0;
    protected int $rowCount = 0;

    public function render(): array
    {
        $lang = $this->getLanguageService();
        $resultArray = $this->initializeResultArray();
        // @deprecated since v12, will be removed with v13 when all elements handle label/legend on their own
        $resultArray['labelHasBeenHandled'] = true;
        $this->initializeWizard();

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
                'label' => $lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_alt_doc.xlf:buttons.pageTsConfig'),
                'panel' => 'top',
                'mode' => GeneralUtility::jsonEncodeForHtmlAttribute(JavaScriptModuleInstruction::create('@typo3/t3editor/language/typoscript.js', 'typoscript')->invoke(), false),
                'nolazyload' => 'true',
                'readonly' => 'true',
            ];
            $editor = '
                <typo3-t3editor-codemirror class="t3js-grideditor-preview-config grideditor-preview" ' . GeneralUtility::implodeAttributes($codeMirrorConfig, true) . '>
                    <textarea class="t3js-tsconfig-preview-area form-control"></textarea>
                </typo3-t3editor-codemirror>';

            $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create('@typo3/t3editor/element/code-mirror-element.js');
        } else {
            $editor = '
                <label>' . htmlspecialchars($lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_alt_doc.xlf:buttons.pageTsConfig')) . '</label>
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
        $html[] =               '<div class="grideditor' . ($readOnly ? ' grideditor-readonly' : '') . '">';
        if (!$readOnly) {
            $html[] =               '<div class="grideditor-control grideditor-control-top">';
            $html[] =                   '<div class="btn-group">';
            $html[] =                       '<a class="btn btn-default btn-sm t3js-grideditor-addrow-top" href="#"';
            $html[] =                           ' title="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:grid_addRow')) . '">';
            $html[] =                           $this->iconFactory->getIcon('actions-plus', Icon::SIZE_SMALL)->render();
            $html[] =                       '</a>';
            $html[] =                       '<a class="btn btn-default btn-sm t3js-grideditor-removerow-top" href="#"';
            $html[] =                           ' title="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:grid_removeRow')) . '">';
            $html[] =                           $this->iconFactory->getIcon('actions-minus', Icon::SIZE_SMALL)->render();
            $html[] =                       '</a>';
            $html[] =                   '</div>';
            $html[] =               '</div>';
        }
        $html[] =                   '<div class="grideditor-editor">';
        $html[] =                       '<div';
        $html[] =                           ' id="editor"';
        $html[] =                           ' class="t3js-grideditor"';
        $html[] =                           ' data-data="' . htmlspecialchars($json) . '"';
        $html[] =                           ' data-rowcount="' . (int)$this->rowCount . '"';
        $html[] =                           ' data-colcount="' . (int)$this->colCount . '"';
        $html[] =                           ' data-readonly="' . ($readOnly ? '1' : '0') . '"';
        $html[] =                           ' data-field="' . htmlspecialchars($this->data['parameterArray']['itemFormElName']) . '"';
        $html[] =                       '></div>';
        $html[] =                   '</div>';
        if (!$readOnly) {
            $html[] =               '<div class="grideditor-control grideditor-control-right">';
            $html[] =                   '<div class="btn-group-vertical">';
            $html[] =                       '<a class="btn btn-default btn-sm t3js-grideditor-addcolumn" href="#"';
            $html[] =                           ' title="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:grid_addColumn')) . '">';
            $html[] =                           $this->iconFactory->getIcon('actions-plus', Icon::SIZE_SMALL)->render();
            $html[] =                       '</a>';
            $html[] =                       '<a class="btn btn-default btn-sm t3js-grideditor-removecolumn" href="#"';
            $html[] =                           ' title="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:grid_removeColumn')) . '">';
            $html[] =                           $this->iconFactory->getIcon('actions-minus', Icon::SIZE_SMALL)->render();
            $html[] =                       '</a>';
            $html[] =                   '</div>';
            $html[] =               '</div>';
            $html[] =               '<div class="grideditor-control grideditor-control-bottom">';
            $html[] =                   '<div class="btn-group">';
            $html[] =                       '<a class="btn btn-default btn-sm t3js-grideditor-addrow-bottom" href="#"';
            $html[] =                           ' title="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:grid_addRow')) . '">';
            $html[] =                           $this->iconFactory->getIcon('actions-plus', Icon::SIZE_SMALL)->render();
            $html[] =                       '</a>';
            $html[] =                       '<a class="btn btn-default btn-sm t3js-grideditor-removerow-bottom" href="#"';
            $html[] =                           ' title="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:grid_removeRow')) . '">';
            $html[] =                           $this->iconFactory->getIcon('actions-minus', Icon::SIZE_SMALL)->render();
            $html[] =                       '</a>';
            $html[] =                   '</div>';
            $html[] =               '</div>';
        }
        $html[] =                   '<div class="grideditor-preview">' . $editor . '</div>';
        $html[] =                '</div>';
        if (!$readOnly && !empty($fieldWizardHtml)) {
            $html[] =           '<div class="form-wizards-items-bottom">' . $fieldWizardHtml . '</div>';
        }
        $html[] =           '</div>';
        $html[] =       '</div>';
        $html[] =   '</div>';
        $html[] = '</div>';

        $html = implode(LF, $html);
        $resultArray['html'] = $this->wrapWithFieldsetAndLegend($html);
        $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create(
            '@typo3/backend/grid-editor.js',
            'GridEditor'
        )->instance();
        $resultArray['additionalInlineLanguageLabelFiles'][] = 'EXT:core/Resources/Private/Language/locallang_wizards.xlf';
        $resultArray['additionalInlineLanguageLabelFiles'][] = 'EXT:backend/Resources/Private/Language/locallang.xlf';

        return $resultArray;
    }

    protected function initializeWizard(): void
    {
        // Initialize default values
        $rows = [[['colspan' => 1, 'rowspan' => 1, 'spanned' => 0, 'name' => '0x0']]];
        $colCount = 1;
        $rowCount = 1;

        if (!empty($this->data['parameterArray']['itemFormElValue'])) {
            // Parse the TypoScript a-like syntax in case we already have a config (e.g. database value or default from TCA)
            $typoScriptStringFactory = GeneralUtility::makeInstance(TypoScriptStringFactory::class);
            $typoScriptTree = $typoScriptStringFactory->parseFromString($this->data['parameterArray']['itemFormElValue'], new AstBuilder(new NoopEventDispatcher()));
            $typoScriptArray = $typoScriptTree->toArray();
            if (is_array($typoScriptArray['backend_layout.'] ?? false)) {
                // Only evaluate, in case the "backend_layout." array exists on root level
                $data = $typoScriptArray['backend_layout.'];
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
