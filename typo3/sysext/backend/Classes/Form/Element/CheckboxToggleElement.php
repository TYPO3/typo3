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

use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Generation of TCEform elements of the type "check"
 */
class CheckboxToggleElement extends AbstractFormElement
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
     * This will render a checkbox or an array of checkboxes
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render(): array
    {
        $resultArray = $this->initializeResultArray();

        $elementHtml = '';
        $disabled = false;
        if ($this->data['parameterArray']['fieldConf']['config']['readOnly'] ?? false) {
            $disabled = true;
        }
        // Traversing the array of items
        $items = $this->data['parameterArray']['fieldConf']['config']['items'];

        $numberOfItems = count($items);
        if ($numberOfItems === 0) {
            $items[] = ['', ''];
            $numberOfItems = 1;
        }
        // The values in the array may be numeric strings, but we need real ints.
        $formElementValue = (int)($this->data['parameterArray']['itemFormElValue'] ?? 0);
        $cols = (int)($this->data['parameterArray']['fieldConf']['config']['cols'] ?? 0);
        if ($cols > 1) {
            [$colClass, $colClear] = $this->calculateColumnMarkup($cols);
            $elementHtml .= '<div class="checkbox-row row">';
            $counter = 0;
            // $itemKey is important here, because items could have been removed via TSConfig
            foreach ($items as $itemKey => $itemDefinition) {
                $label = $itemDefinition[0];
                $elementHtml .=
                    '<div class="checkbox-column ' . $colClass . '">'
                    . $this->renderSingleCheckboxElement($label, $itemKey, $formElementValue, $numberOfItems, $this->data['parameterArray'], $disabled) .
                    '</div>';
                ++$counter;
                if ($counter < $numberOfItems && !empty($colClear)) {
                    foreach ($colClear as $rowBreakAfter => $clearClass) {
                        if ($counter % $rowBreakAfter === 0) {
                            $elementHtml .= '<div class="clearfix ' . $clearClass . '"></div>';
                        }
                    }
                }
            }
            $elementHtml .= '</div>';
        } else {
            $counter = 0;
            foreach ($items as $itemKey => $itemDefinition) {
                $label = $itemDefinition[0];
                $elementHtml .= $this->renderSingleCheckboxElement($label, $counter, $formElementValue, $numberOfItems, $this->data['parameterArray'], $disabled);
                ++$counter;
            }
        }
        if (!$disabled) {
            $elementHtml .= '<input type="hidden" name="' . htmlspecialchars($this->data['parameterArray']['itemFormElName']) . '" value="' . htmlspecialchars((string)$formElementValue) . '" />';
        }

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        $fieldWizardResult = $this->renderFieldWizard();
        $fieldWizardHtml = $fieldWizardResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldWizardResult, false);

        $html = [];
        $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
        $html[] = $fieldInformationHtml;
        $html[] =   '<div class="form-wizards-wrap">';
        $html[] =       '<div class="form-wizards-element">';
        $html[] =           $elementHtml;
        $html[] =       '</div>';
        if (!$disabled && !empty($fieldWizardHtml)) {
            $html[] =   '<div class="form-wizards-items-bottom">';
            $html[] =       $fieldWizardHtml;
            $html[] =   '</div>';
        }
        $html[] =   '</div>';
        $html[] = '</div>';

        $resultArray['html'] = implode(LF, $html);
        return $resultArray;
    }

    /**
     * This functions builds the HTML output for the checkbox
     *
     * @param string $label Label of this item
     * @param int $itemCounter Number of this element in the list of all elements
     * @param int $formElementValue Value of this element
     * @param int $numberOfItems Full number of items
     * @param array $additionalInformation Information with additional configuration options.
     * @param bool $disabled TRUE if form element is disabled
     * @return string Single element HTML
     */
    protected function renderSingleCheckboxElement($label, $itemCounter, $formElementValue, $numberOfItems, $additionalInformation, $disabled): string
    {
        $config = $additionalInformation['fieldConf']['config'];
        $inline = !empty($config['cols']) && $config['cols'] === 'inline';
        $invert = isset($config['items'][0]['invertStateDisplay']) && $config['items'][0]['invertStateDisplay'] === true;
        $checkboxParameters = $this->checkBoxParams(
            $additionalInformation['itemFormElName'],
            $formElementValue,
            $itemCounter,
            $numberOfItems,
            $additionalInformation['fieldChangeFunc'] ?? [],
            $invert
        );
        $uniqueId = StringUtility::getUniqueId('_');
        $checkboxId = $additionalInformation['itemFormElID'] . '_' . $itemCounter . $uniqueId;
        return '
            <div class="form-check form-switch' . ($inline ? ' form-check-inline' : '') . (!$disabled ? '' : ' disabled') . '">
                <input type="checkbox"
                    class="form-check-input"
                    value="1"
                    data-formengine-input-name="' . htmlspecialchars($additionalInformation['itemFormElName']) . '"
                    ' . $checkboxParameters . '
                    ' . (!$disabled ? '' : ' disabled="disabled"') . '
                    id="' . $checkboxId . '" />
                <label class="form-check-label" for="' . $checkboxId . '">
                    <span class="form-check-label-text">' . $this->appendValueToLabelInDebugMode(($label ? htmlspecialchars($label) : '&nbsp;'), $formElementValue) . '</span>
                </label>
            </div>';
    }
}
