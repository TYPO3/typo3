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

use TYPO3\CMS\Backend\Form\InlineStackProcessor;
use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Creates a widget where only one item can be selected.
 * This is either a select drop-down if no size config is given or set to 1, or a select box.
 *
 * This is rendered for type=select, renderType=selectSingle
 */
class SelectSingleElement extends AbstractFormElement
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
        'selectIcons' => [
            'renderType' => 'selectIcons',
            'disabled' => true,
        ],
        'localizationStateSelector' => [
            'renderType' => 'localizationStateSelector',
            'after' => [
                'selectIcons',
            ],
        ],
        'otherLanguageContent' => [
            'renderType' => 'otherLanguageContent',
            'after' => [ 'localizationStateSelector' ],
        ],
        'defaultLanguageDifferences' => [
            'renderType' => 'defaultLanguageDifferences',
            'after' => [ 'otherLanguageContent' ],
        ],
    ];

    public function __construct(
        private readonly InlineStackProcessor $inlineStackProcessor,
    ) {}

    /**
     * Render single element
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render(): array
    {
        $resultArray = $this->initializeResultArray();

        $table = $this->data['tableName'];
        $field = $this->data['fieldName'];
        $parameterArray = $this->data['parameterArray'];
        $config = $parameterArray['fieldConf']['config'];

        $selectItems = $parameterArray['fieldConf']['config']['items'];
        $classList = ['form-select', 'form-control-adapt'];

        // Check against inline uniqueness
        $this->inlineStackProcessor->initializeByGivenStructure($this->data['inlineStructure']);
        $uniqueIds = [];
        if (($this->data['isInlineChild'] ?? false) && ($this->data['inlineParentUid'] ?? false)) {
            // If config[foreign_unique] is set for the parent inline field, all
            // already used unique ids must be excluded from the select items.
            $inlineObjectName = $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($this->data['inlineFirstPid']);
            if (($this->data['inlineParentConfig']['foreign_table'] ?? false) === $table
                && ($this->data['inlineParentConfig']['foreign_unique'] ?? false) === $field
            ) {
                $classList[] = 't3js-inline-unique';
                $uniqueIds = $this->data['inlineData']['unique'][$inlineObjectName . '-' . $table]['used'] ?? [];
            }
            // hide uid of parent record for symmetric relations
            if (($this->data['inlineParentConfig']['foreign_table'] ?? false) === $table
                && (
                    ($this->data['inlineParentConfig']['foreign_field'] ?? false) === $field
                    || ($this->data['inlineParentConfig']['symmetric_field'] ?? false) === $field
                )
            ) {
                $uniqueIds[] = $this->data['inlineParentUid'];
            }
            $uniqueIds = array_map(intval(...), $uniqueIds);
        }

        // Initialization:
        $selectId = StringUtility::getUniqueId('tceforms-select-');
        $selectedItem = null;
        $size = (int)($config['size'] ?? 0);

        // Style set on <select/>
        $options = '';
        $disabled = false;
        if (!empty($config['readOnly'])) {
            $disabled = true;
        }

        // Prepare groups
        $selectItemCounter = 0;
        $selectItemGroupCount = 0;
        $selectItemGroups = [];
        $selectedValue = '';
        $hasIcons = false;

        // In case e.g. "l10n_display" is set to "defaultAsReadonly" only one value (as string) could be handed in
        if (!empty($parameterArray['itemFormElValue'])) {
            if (is_array($parameterArray['itemFormElValue'])) {
                $selectedValue = (string)$parameterArray['itemFormElValue'][0];
            } else {
                $selectedValue = (string)$parameterArray['itemFormElValue'];
            }
        }

        foreach ($selectItems as $item) {
            $selected = $selectedValue === (string)$item['value'];

            if ($item['value'] === '--div--') {
                // IS OPTGROUP
                if ($selectItemCounter !== 0) {
                    $selectItemGroupCount++;
                }
                $selectItemGroups[$selectItemGroupCount]['header'] = [
                    'title' => $item['label'],
                ];
            } elseif ($selected || !in_array((int)$item['value'], $uniqueIds, true)) {
                $icon = !empty($item['icon']) ? FormEngineUtility::getIconHtml($item['icon'], $item['label'], $item['label']) : '';
                $enhancedItem = [
                    'title' => $this->appendValueToLabelInDebugMode($item['label'], $item['value']),
                    'value' => $item['value'],
                    'icon' => $icon,
                    'selected' => $selected,
                ];
                if ($selected) {
                    $selectedItem = $enhancedItem;
                }
                $selectItemGroups[$selectItemGroupCount]['items'][] = $enhancedItem;
                $selectItemCounter++;
            }
        }

        // Process groups
        foreach ($selectItemGroups as $selectItemGroup) {
            // suppress groups without items
            if (empty($selectItemGroup['items'])) {
                continue;
            }

            $optionGroup = is_array($selectItemGroup['header'] ?? null);
            $options .= ($optionGroup ? '<optgroup label="' . htmlspecialchars($selectItemGroup['header']['title'], ENT_COMPAT, 'UTF-8', false) . '">' : '');

            if (is_array($selectItemGroup['items'])) {
                foreach ($selectItemGroup['items'] as $item) {
                    $options .= '<option value="' . htmlspecialchars($item['value']) . '" data-icon="' .
                        htmlspecialchars($item['icon']) . '"'
                        . ($item['selected'] ? ' selected="selected"' : '') . '>' . htmlspecialchars((string)($item['title'] ?? ''), ENT_COMPAT, 'UTF-8', false) . '</option>';

                    // At least one select item with icon found.
                    if (!empty($item['icon'])) {
                        $hasIcons = true;
                    }
                }
            }
            $options .= ($optionGroup ? '</optgroup>' : '');
        }

        // No item selected. Use first item of first group as selected item, which is display
        // in the form to render icon of that item icon as selected icon when item has one.
        if ($hasIcons
            && $selectedItem === null
            && isset($selectItemGroups[0]['items'][0])
        ) {
            $selectedItem = $selectItemGroups[0]['items'][0];
        }

        $selectAttributes = [
            'id' => $selectId,
            'name' => (string)($parameterArray['itemFormElName'] ?? ''),
            'data-formengine-validation-rules' => $this->getValidationDataAsJsonString($config),
            'class' => implode(' ', $classList),
        ];
        if ($size) {
            $selectAttributes['size'] = (string)$size;
        }
        if ($disabled) {
            $selectAttributes['disabled'] = 'disabled';
        }

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        $fieldControlResult = $this->renderFieldControl();
        $fieldControlHtml = $fieldControlResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldControlResult, false);

        $fieldWizardResult = $this->renderFieldWizard();
        $fieldWizardHtml = $fieldWizardResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldWizardResult, false);

        $html = [];
        $html[] = $this->renderLabel($selectId);
        $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
        $html[] = $fieldInformationHtml;
        $html[] =   '<div class="form-control-wrap">';
        $html[] =       '<div class="form-wizards-wrap">';
        $html[] =           '<div class="form-wizards-item-element">';
        if ($hasIcons) {
            $html[] =           '<div class="input-group">';
            $html[] =               '<span class="input-group-text input-group-icon">';
            if ($selectedItem !== null) {
                $html[] =              $selectedItem['icon'];
            }
            $html[] =               '</span>';
        }
        $html[] =                   '<select ' . GeneralUtility::implodeAttributes($selectAttributes, true) . '>';
        $html[] =                       $options;
        $html[] =                   '</select>';
        if ($hasIcons) {
            $html[] =           '</div>';
        }
        $html[] =           '</div>';
        if (!$disabled && !empty($fieldControlHtml)) {
            $html[] =      '<div class="form-wizards-item-aside form-wizards-item-aside--field-control">';
            $html[] =          '<div class="btn-group">';
            $html[] =              $fieldControlHtml;
            $html[] =          '</div>';
            $html[] =      '</div>';
        }
        if (!$disabled && !empty($fieldWizardHtml)) {
            $html[] =       '<div class="form-wizards-item-bottom">';
            $html[] =           $fieldWizardHtml;
            $html[] =       '</div>';
        }
        $html[] =       '</div>';
        $html[] =   '</div>';
        $html[] = '</div>';

        $onFieldChangeItems = $this->getOnFieldChangeItems($parameterArray['fieldChangeFunc'] ?? []);
        $resultArray['javaScriptModules']['selectSingleElement'] = JavaScriptModuleInstruction::create(
            '@typo3/backend/form-engine/element/select-single-element.js'
        )->invoke('initializeOnReady', '#' . $selectId, ['onChange' => $onFieldChangeItems]);

        $resultArray['html'] = implode(LF, $html);
        return $resultArray;
    }
}
