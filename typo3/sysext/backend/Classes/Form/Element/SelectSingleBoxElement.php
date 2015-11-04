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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Create a widget with a select box where multiple items can be selected
 *
 * This is rendered for config type=select, maxitems > 1, renderType=selectSingleBox
 */
class SelectSingleBoxElement extends AbstractFormElement
{
    /**
     * This will render a selector box element, or possibly a special construction with two selector boxes.
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $parameterArray = $this->data['parameterArray'];
        // Field configuration from TCA:
        $config = $parameterArray['fieldConf']['config'];
        $selectItems = $parameterArray['fieldConf']['config']['items'];

        // Get values in an array (and make unique, which is fine because there can be no duplicates anyway):
        $itemArray = array_flip($parameterArray['itemFormElValue']);
        $optionElements = [];
        $initiallySelectedIndices = [];

        foreach ($selectItems as $i => $item) {
            $value = $item[1];
            $attributes = [];

            // Selected or not by default
            if (isset($itemArray[$value])) {
                $attributes['selected'] = 'selected';
                $initiallySelectedIndices[] = $i;
                unset($itemArray[$value]);
            }

            // Non-selectable element
            if ((string)$value === '--div--') {
                $attributes['disabled'] = 'disabled';
                $attributes['class'] = 'formcontrol-select-divider';
            }

            $optionElements[] = $this->renderOptionElement($value, $item[0], $attributes);
        }

        $selectElement = $this->renderSelectElement($optionElements, $parameterArray, $config);
        $resetButtonElement = $this->renderResetButtonElement($parameterArray['itemFormElName'] . '[]', $initiallySelectedIndices);
        $html = [];

        // Add an empty hidden field which will send a blank value if all items are unselected.
        if (empty($config['readOnly'])) {
            $html[] = '<input type="hidden" name="' . htmlspecialchars($parameterArray['itemFormElName']) . '" value="">';
        }

        // Put it all together
        $width = $this->formMaxWidth($this->defaultInputWidth);
        $html = array_merge($html, [
            '<div class="form-control-wrap" ' . ($width ? ' style="max-width: ' . $width . 'px"' : '') . '>',
                '<div class="form-wizards-wrap form-wizards-aside">',
                    '<div class="form-wizards-element">',
                        $selectElement,
                    '</div>',
                    '<div class="form-wizards-items">',
                        $resetButtonElement,
                    '</div>',
                '</div>',
            '</div>',
            '<p>',
                '<em>' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.holdDownCTRL', true) . '</em>',
            '</p>',
        ]);
        $html = implode(LF, $html);

        // Wizards:
        if (empty($config['readOnly'])) {
            $html = $this->renderWizards(
                [$html],
                $config['wizards'],
                $this->data['tableName'],
                $this->data['databaseRow'],
                $this->data['fieldName'],
                $parameterArray,
                $parameterArray['itemFormElName'],
                BackendUtility::getSpecConfParts($parameterArray['fieldConf']['defaultExtras'])
            );
        }

        $resultArray = $this->initializeResultArray();
        $resultArray['html'] = $html;

        return $resultArray;
    }

    /**
     * Renders a <select> element
     *
     * @param array $optionElements List of rendered <option> elements
     * @param array $parameterArray
     * @param array $config Field configuration
     * @return string
     */
    protected function renderSelectElement(array $optionElements, array $parameterArray, array $config)
    {
        $selectItems = $parameterArray['fieldConf']['config']['items'];
        $size = (int)$config['size'];
        $cssPrefix = $size === 1 ? 'tceforms-select' : 'tceforms-multiselect';

        if ($config['autoSizeMax']) {
            $size = MathUtility::forceIntegerInRange(
                count($selectItems) + 1,
                MathUtility::forceIntegerInRange($size, 1),
                $config['autoSizeMax']
            );
        }

        $attributes = [
            'name' => $parameterArray['itemFormElName'] . '[]',
            'multiple' => 'multiple',
            'onchange' => implode('', $parameterArray['fieldChangeFunc']),
            'id' => StringUtility::getUniqueId($cssPrefix),
            'class' => 'form-control ' . $cssPrefix,
        ];

        if ($size) {
            $attributes['size'] = $size;
        }

        if ($config['readOnly']) {
            $attributes['disabled'] = 'disabled';
        }

        if (isset($config['itemListStyle'])) {
            $attributes['style'] = $config['itemListStyle'];
        }

        $html = [
            '<select ' . $this->implodeAttributes($attributes) . ' ' . $parameterArray['onFocus'] . ' ' . $this->getValidationDataAsDataAttribute($config) . '>',
                implode(LF, $optionElements),
            '</select>',
        ];

        return implode(LF, $html);
    }

    /**
     * Renders a single <option> element
     *
     * @param string $value The option value
     * @param string $label The option label
     * @param array $attributes Map of attribute names and values
     * @return string
     */
    protected function renderOptionElement($value, $label, array $attributes = [])
    {
        $html = [
            '<option value="' . htmlspecialchars($value) . '" ' . $this->implodeAttributes($attributes) . '>',
                htmlspecialchars($label, ENT_COMPAT, 'UTF-8', false),
            '</option>'

        ];

        return implode('', $html);
    }

    /**
     * Renders a button for resetting to the selection on initial load
     *
     * @param string $formElementName Form element name
     * @param array $initiallySelectedIndices List of initially selected option indices
     * @return string
     */
    protected function renderResetButtonElement($formElementName, array $initiallySelectedIndices)
    {
        $formElementName = GeneralUtility::quoteJSvalue($formElementName);
        $resetCode = [
            'document.editform[' . $formElementName . '].selectedIndex=-1'
        ];
        foreach ($initiallySelectedIndices as $index) {
            $resetCode[] = 'document.editform[' . $formElementName . '].options[' . $index . '].selected=1';
        }
        $resetCode[] = 'return false';

        $attributes = [
            'href' => '#',
            'class' => 'btn btn-default',
            'onclick' => htmlspecialchars(implode(';', $resetCode)),
            // htmlspecialchars() is done by $this->implodeAttributes()
            'title' => $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.revertSelection')
        ];

        $html = [
            '<a ' . $this->implodeAttributes($attributes) . '>',
                $this->iconFactory->getIcon('actions-edit-undo', Icon::SIZE_SMALL)->render(),
            '</a>',
        ];

        return implode('', $html);
    }

    /**
     * Build an HTML attributes string from a map of attributes
     *
     * All attribute values are passed through htmlspecialchars()
     *
     * @param array $attributes Map of attribute names and values
     * @return string
     */
    protected function implodeAttributes(array $attributes = [])
    {
        $html = [];
        foreach ($attributes as $name => $value) {
            $html[] = $name . '="' . htmlspecialchars($value, ENT_COMPAT, 'UTF-8', false) . '"';
        }
        return implode(' ', $html);
    }
}
