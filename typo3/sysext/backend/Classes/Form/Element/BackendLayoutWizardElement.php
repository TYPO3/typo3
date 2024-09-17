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

use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\TypoScript\AST\AstBuilder;
use TYPO3\CMS\Core\TypoScript\TypoScriptStringFactory;
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

    public function __construct(
        private readonly TypoScriptStringFactory $typoScriptStringFactory,
    ) {}

    public function render(): array
    {
        $lang = $this->getLanguageService();
        $resultArray = $this->initializeResultArray();
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
        $codeMirrorConfig = [
            'label' => $lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_alt_doc.xlf:buttons.pageTsConfig'),
            'panel' => 'top',
            'mode' => GeneralUtility::jsonEncodeForHtmlAttribute(JavaScriptModuleInstruction::create('@typo3/backend/code-editor/language/typoscript.js', 'typoscript')->invoke(), false),
            'nolazyload' => 'true',
            'readonly' => 'true',
        ];

        $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create('@typo3/backend/code-editor/element/code-mirror-element.js');

        $json = (string)json_encode($this->rows, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
        $codeMirrorConfig = (string)json_encode($codeMirrorConfig, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
        $html = [];
        $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
        $html[] =   $fieldInformationHtml;
        $html[] =   '<div class="form-control-wrap">';
        $html[] =       '<div class="form-wizards-wrap">';
        $html[] =           '<div class="form-wizards-item-element">';
        $html[] =               '<input';
        $html[] =                   ' type="hidden"';
        $html[] =                   ' name="' . htmlspecialchars($this->data['parameterArray']['itemFormElName']) . '"';
        $html[] =                   ' value="' . htmlspecialchars($this->data['parameterArray']['itemFormElValue']) . '"';
        $html[] =                   '/>';
        $html[] =               '<typo3-backend-grid-editor';
        $html[] =                   ' data="' . htmlspecialchars($json) . '"';
        $html[] =                   ' rowCount="' . (int)$this->rowCount . '"';
        $html[] =                   ' colCount="' . (int)$this->colCount . '"';
        $html[] =                   ($readOnly ? 'readonly="true"' : '');
        $html[] =                   ' fieldName="' . htmlspecialchars($this->data['parameterArray']['itemFormElName']) . '"';
        $html[] =                   ' codeMirrorConfig="' . htmlspecialchars($codeMirrorConfig) . '"';
        $html[] =               '></typo3-backend-grid-editor>';
        if (!$readOnly && !empty($fieldWizardHtml)) {
            $html[] =           '<div class="form-wizards-item-bottom">' . $fieldWizardHtml . '</div>';
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
        $resultArray['additionalInlineLanguageLabelFiles'][] = 'EXT:backend/Resources/Private/Language/locallang_alt_doc.xlf';

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
            $typoScriptTree = $this->typoScriptStringFactory->parseFromString($this->data['parameterArray']['itemFormElValue'], new AstBuilder(new NoopEventDispatcher()));
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
                    $columns = $row['columns.'] ?? [];
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
                                if (isset($column['identifier'])) {
                                    $cellData['identifier'] = $column['identifier'];
                                }
                                if (isset($column['slideMode'])) {
                                    $cellData['slideMode'] = $column['slideMode'];
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
