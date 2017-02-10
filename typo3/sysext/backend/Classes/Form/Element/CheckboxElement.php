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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Generation of TCEform elements of the type "check"
 */
class CheckboxElement extends AbstractFormElement
{
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
     * This will render a checkbox or an array of checkboxes
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $resultArray = $this->initializeResultArray();

        $elementHtml = '';
        $disabled = false;
        if ($this->data['parameterArray']['fieldConf']['config']['readOnly']) {
            $disabled = true;
        }
        // Traversing the array of items
        $items = $this->data['parameterArray']['fieldConf']['config']['items'];

        $numberOfItems = count($items);
        if ($numberOfItems === 0) {
            $items[] = ['', ''];
            $numberOfItems = 1;
        }
        $formElementValue = (int)$this->data['parameterArray']['itemFormElValue'];
        $cols = (int)$this->data['parameterArray']['fieldConf']['config']['cols'];
        if ($cols > 1) {
            $colWidth = (int)floor(12 / $cols);
            $colClass = 'col-md-12';
            $colClear = [];
            if ($colWidth == 6) {
                $colClass = 'col-sm-6';
                $colClear = [
                    2 => 'visible-sm-block visible-md-block visible-lg-block',
                ];
            } elseif ($colWidth === 4) {
                $colClass = 'col-sm-4';
                $colClear = [
                    3 => 'visible-sm-block visible-md-block visible-lg-block',
                ];
            } elseif ($colWidth === 3) {
                $colClass = 'col-sm-6 col-md-3';
                $colClear = [
                    2 => 'visible-sm-block',
                    4 => 'visible-md-block visible-lg-block',
                ];
            } elseif ($colWidth <= 2) {
                $colClass = 'checkbox-column col-sm-6 col-md-3 col-lg-2';
                $colClear = [
                    2 => 'visible-sm-block',
                    4 => 'visible-md-block',
                    6 => 'visible-lg-block'
                ];
            }
            $elementHtml .= '<div class="checkbox-row row">';
            $counter = 0;
            // @todo: figure out in which cases checkbox items to not begin at 0 and why and when this would be useful
            foreach ($items as $itemKey => $itemDefinition) {
                $label = $itemDefinition[0];
                $elementHtml .=
                    '<div class="checkbox-column ' . $colClass . '">'
                        . $this->renderSingleCheckboxElement($label, $itemKey, $formElementValue, $numberOfItems, $this->data['parameterArray'], $disabled) .
                    '</div>';
                $counter = $counter + 1;
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
                $elementHtml .=  $this->renderSingleCheckboxElement($label, $counter, $formElementValue, $numberOfItems, $this->data['parameterArray'], $disabled);
                $counter = $counter + 1;
            }
        }
        if (!$disabled) {
            $elementHtml .= '<input type="hidden" name="' . $this->data['parameterArray']['itemFormElName'] . '" value="' . htmlspecialchars($formElementValue) . '" />';
        }

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        $fieldWizardResult = $this->renderFieldWizard();
        $fieldWizardHtml = $fieldWizardResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldWizardResult, false);

        $html = [];
        $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
        if (!$disabled) {
            $html[] = $fieldInformationHtml;
        }
        $html[] =   '<div class="form-wizards-wrap">';
        $html[] =       '<div class="form-wizards-element">';
        $html[] =           $elementHtml;
        $html[] =       '</div>';
        if (!$disabled) {
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
    protected function renderSingleCheckboxElement($label, $itemCounter, $formElementValue, $numberOfItems, $additionalInformation, $disabled)
    {
        $config = $additionalInformation['fieldConf']['config'];
        $inline = !empty($config['cols']) && $config['cols'] === 'inline';
        $checkboxParameters = $this->checkBoxParams(
            $additionalInformation['itemFormElName'],
            $formElementValue,
            $itemCounter,
            $numberOfItems,
            implode('', $additionalInformation['fieldChangeFunc'])
        );
        $checkboxId = $additionalInformation['itemFormElID'] . '_' . $itemCounter;
        return '
			<div class="checkbox' . ($inline ? ' checkbox-inline' : '') . (!$disabled ? '' : ' disabled') . '">
				<label>
					<input type="checkbox"
						value="1"
						data-formengine-input-name="' . htmlspecialchars($additionalInformation['itemFormElName']) . '"
						' . $checkboxParameters . '
						' . (!$disabled ?: ' disabled="disabled"') . '
						id="' . $checkboxId . '" />
					' . ($label ? htmlspecialchars($label) : '&nbsp;') . '
				</label>
			</div>';
    }

    /**
     * Creates checkbox parameters
     *
     * @param string $itemName Form element name
     * @param int $formElementValue The value of the checkbox (representing checkboxes with the bits)
     * @param int $checkbox Checkbox # (0-9?)
     * @param int $checkboxesCount Total number of checkboxes in the array.
     * @param string $additionalJavaScript Additional JavaScript for the onclick handler.
     * @return string The onclick attribute + possibly the checked-option set.
     */
    protected function checkBoxParams($itemName, $formElementValue, $checkbox, $checkboxesCount, $additionalJavaScript = '')
    {
        $elementName = 'document.editform[' . GeneralUtility::quoteJSvalue($itemName) . ']';
        $checkboxPow = pow(2, $checkbox);
        $onClick = $elementName . '.value=this.checked?(' . $elementName . '.value|' . $checkboxPow . '):('
            . $elementName . '.value&' . (pow(2, $checkboxesCount) - 1 - $checkboxPow) . ');' . $additionalJavaScript;
        return ' onclick="' . htmlspecialchars($onClick) . '"' . ($formElementValue & $checkboxPow ? ' checked="checked"' : '');
    }
}
