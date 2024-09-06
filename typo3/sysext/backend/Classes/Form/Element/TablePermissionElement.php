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

use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Renders table permission options for each available table.
 *
 * @internal Only used in be_groups for the "combined read & write" table permission list.
 */
final class TablePermissionElement extends AbstractFormElement
{
    private const Permissions = [
        'none' => 'none',
        'select' => 'select',
        'modify' => 'modify',
    ];

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

    public function __construct(
        private readonly IconFactory $iconFactory,
    ) {}

    public function render(): array
    {
        $resultArray = $this->initializeResultArray();

        $parameterArray = $this->data['parameterArray'];
        $config = $parameterArray['fieldConf']['config'];
        $elementFieldName = $parameterArray['itemFormElName'];
        $currentValue = ['modify' => [], 'select' => []];
        if (is_array($parameterArray['itemFormElValue']['modify'] ?? false)
            && is_array($parameterArray['itemFormElValue']['select'] ?? false)
        ) {
            $currentValue = $parameterArray['itemFormElValue'];
        }
        $readOnly = (bool)($config['readOnly'] ?? false);

        $availableTables = $config['items'] ?? [];
        if (empty($availableTables)) {
            // Early return in case the field does not contain any items
            return $resultArray;
        }

        $tablesConfiguration = [];
        $lang = $this->getLanguageService();
        $itemArrayModify = array_flip($currentValue['modify']);
        $itemArraySelect = array_flip($currentValue['select']);
        $elementId = StringUtility::getUniqueId('formengine-table-permission-');

        foreach ($availableTables as $table) {
            $permissions = [];
            foreach (self::Permissions as $permission) {
                $permissions[$permission] = [
                    'label' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.tables_modify.permissions.' . $permission),
                    'attributes' => [
                        'type' => 'radio',
                        'class' => 'form-check-input t3js-table-permissions-item t3js-multi-record-selection-check',
                        'value' => $permission,
                        'name' => $elementId . '[' . $table['value'] . ']',
                        'id' => $elementId . '[' . $table['value'] . '][' . $permission . ']',
                        'data-table' => $table['value'],
                    ],
                ];
            }

            if (isset($itemArrayModify[$table['value']])) {
                $permissions[self::Permissions['modify']]['attributes']['checked'] = 'checked';
            } elseif (isset($itemArraySelect[$table['value']])) {
                $permissions[self::Permissions['select']]['attributes']['checked'] = 'checked';
            } else {
                $permissions[self::Permissions['none']]['attributes']['checked'] = 'checked';
            }

            if ($readOnly) {
                foreach (self::Permissions as $permission) {
                    $permissions[$permission]['attributes']['disabled'] = 'disabled';
                }
            }

            $tablesConfiguration[] = [
                'permissions' => $permissions,
                'label' => [
                    'id' => $elementId . '-' . $table['value'] . '-label',
                    'icon' => $this->getIconForTable(!empty($table['icon']) ? $table['icon'] : 'empty-empty'),
                    'title' => $lang->sL($table['label']),
                    'value' => $table['value'],
                ],
            ];
        }

        $modifyStateFieldName = htmlspecialchars($elementFieldName);
        $selectStateFieldName = htmlspecialchars(str_replace($this->data['fieldName'], $config['selectFieldName'], $elementFieldName));

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        $html[] = '<typo3-formengine-element-tablepermission modifyStateFieldName="' . $modifyStateFieldName . '" selectStateFieldName="' . $selectStateFieldName . '">';
        $html[] = '<div class="formengine-field-item t3js-formengine-field-item" data-formengine-validation-rules="' . htmlspecialchars($this->getValidationDataAsJsonString($config)) . '">';
        $html[] = $fieldInformationHtml;

        if (!$readOnly) {
            $html[] = '<input type="hidden" name="' . $modifyStateFieldName . '" value="' . implode(',', $currentValue['modify']) . '">';
            $html[] = '<input type="hidden" name="' . $selectStateFieldName . '" value="' . implode(',', $currentValue['select']) . '">';
        }

        $tableRows = [];
        foreach ($tablesConfiguration as $tableConfiguration) {
            $tableRows[] = '<tr role="radiogroup" aria-labelledby="' . $tableConfiguration['label']['id'] . '">';
            foreach ($tableConfiguration['permissions'] as $key => $permission) {
                $tableRows[] =    '<td class="col-radiogroup">';
                $tableRows[] =      '<div class="form-check form-check-inline" data-multi-record-selection-identifier="' . $elementId . '-' . $key . '" data-multi-record-selection-element="true">';
                $tableRows[] =          '<input ' . GeneralUtility::implodeAttributes($permission['attributes'], true, true) . '>';
                $tableRows[] =          '<label class="form-check-label" for="' . $permission['attributes']['id'] . '">' . htmlspecialchars($permission['label']) . '</label>';
                $tableRows[] =      '</div>';
                $tableRows[] =    '</td>';
            }
            $tableRows[] =    '<td class="col-title col-border-left">';
            $tableRows[] =        '<label class="label-block nowrap-disabled" id="' . $tableConfiguration['label']['id'] . '">';
            $tableRows[] =            '<span>' . $tableConfiguration['label']['icon'] . '</span>';
            $tableRows[] =            htmlspecialchars($this->appendValueToLabelInDebugMode($tableConfiguration['label']['title'], $tableConfiguration['label']['value']));
            $tableRows[] =        '</label>';
            $tableRows[] =    '</td>';
            $tableRows[] = '</tr>';
        }

        $html[] = '<div id="' . $elementId . '">';
        $html[] = '<div class="table-fit">';
        $html[] =       '<table class="table table-hover">';
        $html[] =           '<thead>';
        $html[] =               '<tr>';
        foreach (self::Permissions as $permission) {
            $html[] =    '<th data-multi-record-selection-identifier="' . $elementId . '-' . $permission . '">';
            $html[] =        $this->getRecordSelectionCheckActions($permission === self::Permissions['none'] ? ['all'] : ['all', 'none', 'toggle'], $readOnly);
            $html[] =    '</th>';
        }
        $html[] =                    '<th class="col-title col-border-left">' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.th.name')) . '</th>';
        $html[] =                '</tr>';
        $html[] =            '</thead>';

        $html[] =        '<tbody>' . implode(LF, $tableRows) . '</tbody>';
        $html[] =        '</table>';
        $html[] =    '</div>';
        $html[] = '</div>';
        $html[] = '</div>';

        if (!$readOnly) {
            $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create('@typo3/backend/form-engine/element/table-permission-element.js');
            $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create('@typo3/backend/multi-record-selection.js');
        }

        $html[] = '</typo3-formengine-element-tablepermission>';

        $resultArray['html'] = $this->wrapWithFieldsetAndLegend(implode(LF, $html));
        return $resultArray;
    }

    protected function wrapWithFieldsetAndLegend(string $innerHTML): string
    {
        $legend = htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.tables_modify'));
        if ($this->getBackendUser()->shallDisplayDebugInformation()) {
            $legend .= ' <code>[' . ($this->data['parameterArray']['fieldConf']['config']['selectFieldName'] ?? '') . ', ' . $this->data['fieldName'] . ']</code>';
        }
        $html = [];
        $html[] = '<fieldset>';
        $html[] =     '<legend class="form-legend t3js-formengine-legend">' . $legend . '</legend>';
        $html[] =     $innerHTML;
        $html[] = '</fieldset>';
        return implode(LF, $html);
    }

    protected function getIconForTable(string $icon): ?string
    {

        return FormEngineUtility::getIconHtml($icon);
    }

    protected function getRecordSelectionCheckActions(array $optionsToShow, bool $readOnly): string
    {
        $checkboxOptions = [
            'all' => '
                <li>
                    <button type="button" class="dropdown-item disabled" data-multi-record-selection-check-action="check-all" title="' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.checkAll') . '">
                        <span class="dropdown-item-columns">
                            <span class="dropdown-item-column dropdown-item-column-icon" aria-hidden="true">
                                ' . $this->iconFactory->getIcon('actions-selection-elements-all', IconSize::SMALL)->render() . '
                            </span>
                            <span class="dropdown-item-column dropdown-item-column-title">
                                ' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.checkAll') . '
                            </span>
                        </span>
                    </button>
                </li>',
            'none' => '
                <li>
                    <button type="button" class="dropdown-item disabled" data-multi-record-selection-check-action="check-none" title="' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.uncheckAll') . '">
                        <span class="dropdown-item-columns">
                            <span class="dropdown-item-column dropdown-item-column-icon" aria-hidden="true">
                                ' . $this->iconFactory->getIcon('actions-selection-elements-none', IconSize::SMALL)->render() . '
                            </span>
                            <span class="dropdown-item-column dropdown-item-column-title">
                                ' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.uncheckAll') . '
                            </span>
                        </span>
                    </button>
                </li>',
            'toggle' => '
                <li>
                    <button type="button" class="dropdown-item" data-multi-record-selection-check-action="toggle" title="' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.toggleSelection') . '">
                        <span class="dropdown-item-columns">
                            <span class="dropdown-item-column dropdown-item-column-icon" aria-hidden="true">
                                ' . $this->iconFactory->getIcon('actions-selection-elements-invert', IconSize::SMALL)->render() . '
                            </span>
                            <span class="dropdown-item-column dropdown-item-column-title">
                                ' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.toggleSelection') . '
                            </span>
                        </span>
                    </button>
                </li>',
        ];

        $checkboxOptions = array_filter($checkboxOptions, static fn($checkboxOption) => in_array($checkboxOption, $optionsToShow, true), ARRAY_FILTER_USE_KEY);
        if ($checkboxOptions === []) {
            return '';
        }

        return '
            <div class="btn-group dropdown">
                <button type="button" class="dropdown-toggle dropdown-toggle-link t3js-multi-record-selection-check-actions-toggle" data-bs-toggle="dropdown" data-bs-boundary="window" aria-expanded="false" ' . ($readOnly ? ' disabled="disabled"' : '') . '>
                    <core:icon identifier="actions-selection" size="small" />
                    ' . $this->iconFactory->getIcon('actions-selection', IconSize::SMALL)->render() . '
                </button>
                <ul class="dropdown-menu t3js-multi-record-selection-check-actions">
                    ' . implode(LF, $checkboxOptions) . '
                </ul>
            </div>';
    }
}
