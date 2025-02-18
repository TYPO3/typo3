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
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Creates a widget where only one item can be selected.
 * This is either a select drop-down if no size config is given or set to 1, or a select box.
 *
 * This is rendered for type=country
 */
final class SelectCountryElement extends AbstractFormElement
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

    /**
     * Render single element
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render(): array
    {
        $resultArray = $this->initializeResultArray();

        $parameterArray = $this->data['parameterArray'];
        $config = $parameterArray['fieldConf']['config'];

        $selectItems = $parameterArray['fieldConf']['config']['items'] ?? [];
        $classList = ['form-select', 'form-control-adapt'];

        // Initialization:
        $selectId = StringUtility::getUniqueId('tceforms-select-');
        $selectedIcon = '';
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
            $selectedValue = (string)$parameterArray['itemFormElValue'];
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
            } else {
                $icon = !empty($item['icon']) ? FormEngineUtility::getIconHtml($item['icon'], $item['label'], $item['label']) : '';
                if ($selected) {
                    $selectedIcon = $icon;
                }

                $selectItemGroups[$selectItemGroupCount]['items'][] = [
                    'title' => $this->appendValueToLabelInDebugMode($item['label'], $item['value']),
                    'value' => $item['value'],
                    'icon' => $icon,
                    'selected' => $selected,
                ];
                $selectItemCounter++;
            }
        }

        // Fallback icon
        // @todo: assign a special icon for non matching values?
        if (!$selectedIcon && !empty($selectItemGroups[0]['items'][0]['icon'])) {
            $selectedIcon = $selectItemGroups[0]['items'][0]['icon'];
        }

        // Process groups
        foreach ($selectItemGroups as $selectItemGroup) {
            // suppress groups without items
            if (empty($selectItemGroup['items'])) {
                continue;
            }

            $optionGroup = is_array($selectItemGroup['header'] ?? null);
            $options .= ($optionGroup ? '<optgroup label="' . htmlspecialchars($selectItemGroup['header']['title'], ENT_COMPAT, 'UTF-8', false) . '">' : '');

            foreach ($selectItemGroup['items'] as $item) {
                $options .= '<option value="' . htmlspecialchars($item['value']) . '" data-icon="' .
                    htmlspecialchars($item['icon']) . '"'
                    . ($item['selected'] ? ' selected="selected"' : '') . '>' . htmlspecialchars($item['title'], ENT_COMPAT, 'UTF-8', false) . '</option>';
            }
            $hasIcons = !empty($item['icon']);

            $options .= ($optionGroup ? '</optgroup>' : '');
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
        $html[] =           '<div class="form-wizards-element">';
        if ($hasIcons) {
            $html[] =           '<div class="input-group">';
            $html[] =               '<span class="input-group-text input-group-icon">';
            $html[] =                   $selectedIcon;
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
            $html[] =      '<div class="form-wizards-items-aside form-wizards-items-aside--field-control">';
            $html[] =          '<div class="btn-group">';
            $html[] =              $fieldControlHtml;
            $html[] =          '</div>';
            $html[] =      '</div>';
        }
        if (!$disabled && !empty($fieldWizardHtml)) {
            $html[] =       '<div class="form-wizards-items-bottom">';
            $html[] =           $fieldWizardHtml;
            $html[] =       '</div>';
        }
        $html[] =       '</div>';
        $html[] =   '</div>';
        $html[] = '</div>';

        $onFieldChangeItems = $this->getOnFieldChangeItems($parameterArray['fieldChangeFunc'] ?? []);
        $resultArray['javaScriptModules']['selectSingleElement'] = JavaScriptModuleInstruction::create(
            '@typo3/backend/form-engine/element/select-country-element.js'
        )->invoke('initializeOnReady', '#' . $selectId, ['onChange' => $onFieldChangeItems]);

        $resultArray['html'] = implode(LF, $html);
        return $resultArray;
    }
}
