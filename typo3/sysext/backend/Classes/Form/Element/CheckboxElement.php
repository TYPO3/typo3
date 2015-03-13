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

use TYPO3\CMS\Backend\Form\DataPreprocessor;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;

/**
 * Generation of TCEform elements of the type "check"
 */
class CheckboxElement extends AbstractFormElement {

	/**
	 * This will render a checkbox or an array of checkboxes
	 *
	 * @return array As defined in initializeResultArray() of AbstractNode
	 */
	public function render() {
		$config = $this->globalOptions['parameterArray']['fieldConf']['config'];
		$html = '';
		$disabled = FALSE;
		if ($this->isGlobalReadonly() || $config['readOnly']) {
			$disabled = TRUE;
		}
		// Traversing the array of items
		$items = FormEngineUtility::initItemArray($this->globalOptions['parameterArray']['fieldConf']);
		if ($config['itemsProcFunc']) {
			$dataPreprocessor = GeneralUtility::makeInstance(DataPreprocessor::class);
			$items = $dataPreprocessor->procItems(
				$items,
				$this->globalOptions['parameterArray']['fieldTSConfig']['itemsProcFunc.'],
				$config,
				$this->globalOptions['table'],
				$this->globalOptions['databaseRow'],
				$this->globalOptions['fieldName']
			);
		}

		$numberOfItems = count($items);
		if ($numberOfItems === 0) {
			$items[] = array('', '');
			$numberOfItems = 1;
		}
		$formElementValue = (int)$this->globalOptions['parameterArray']['itemFormElValue'];
		$cols = (int)$config['cols'];
		if ($cols > 1) {
			$colWidth = (int)floor(12 / $cols);
			$colClass = "col-md-12";
			$colClear = array();
			if ($colWidth == 6){
				$colClass = "col-sm-6";
				$colClear = array(
					2 => 'visible-sm-block visible-md-block visible-lg-block',
				);
			} elseif ($colWidth === 4) {
				$colClass = "col-sm-4";
				$colClear = array(
					3 => 'visible-sm-block visible-md-block visible-lg-block',
				);
			} elseif ($colWidth === 3) {
				$colClass = "col-sm-6 col-md-3";
				$colClear = array(
					2 => 'visible-sm-block',
					4 => 'visible-md-block visible-lg-block',
				);
			} elseif ($colWidth <= 2) {
				$colClass = "checkbox-column col-sm-6 col-md-3 col-lg-2";
				$colClear = array(
					2 => 'visible-sm-block',
					4 => 'visible-md-block',
					6 => 'visible-lg-block'
				);
			}
			$html .= '<div class="checkbox-row row">';
			for ($counter = 0; $counter < $numberOfItems; $counter++) {
				// use "default" for typical single checkboxes
				$tsConfigKey = ($numberOfItems === 1 ? 'default' : $items[$counter][1]);
				// useful for e.g. pages.l18n_cfg, where there is no value set
				if ($tsConfigKey === '') {
					$tsConfigKey = $counter;
				}
				if (isset($this->globalOptions['parameterArray']['fieldTSConfig']['altLabels.'][$tsConfigKey])) {
					$label = $this->getLanguageService()->sL($this->globalOptions['parameterArray']['fieldTSConfig']['altLabels.'][$tsConfigKey]);
				} else {
					$label = $items[$counter][0];
				}
				$html .=
					'<div class="checkbox-column ' . $colClass . '">'
						. $this->renderSingleCheckboxElement($label, $counter,  $formElementValue, $numberOfItems, $this->globalOptions['parameterArray'], $disabled) .
					'</div>';
				if ($counter + 1 < $numberOfItems && !empty($colClear)) {
					foreach ($colClear as $rowBreakAfter => $clearClass) {
						if (($counter + 1) % $rowBreakAfter === 0) {
							$html .= '<div class="clearfix '. $clearClass . '"></div>';
						}
					}
				}
			}
			$html .= '</div>';
		} else {
			for ($counter = 0; $counter < $numberOfItems; $counter++) {
				// use "default" for typical single checkboxes
				$tsConfigKey = ($numberOfItems === 1 ? 'default' : $items[$counter][1]);
				// useful for e.g. pages.l18n_cfg, where there is no value set
				if ($tsConfigKey === '') {
					$tsConfigKey = $counter;
				}
				if (isset($this->globalOptions['parameterArray']['fieldTSConfig']['altLabels.'][$tsConfigKey])) {
					$label = $this->getLanguageService()->sL($this->globalOptions['parameterArray']['fieldTSConfig']['altLabels.'][$tsConfigKey]);
				} else {
					$label = $items[$counter][0];
				}
				$html .=  $this->renderSingleCheckboxElement($label, $counter, $formElementValue, $numberOfItems, $this->globalOptions['parameterArray'], $disabled);
			}
		}
		if (!$disabled) {
			$html .= '<input type="hidden" name="' . $this->globalOptions['parameterArray']['itemFormElName'] . '" value="' . htmlspecialchars($formElementValue) . '" />';
		}
		$resultArray = $this->initializeResultArray();
		$resultArray['html'] = $html;
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
	protected function renderSingleCheckboxElement($label, $itemCounter, $formElementValue, $numberOfItems, $additionalInformation, $disabled) {
		$config = $additionalInformation['fieldConf']['config'];
		$inline = !empty($config['cols']) && $config['cols'] === "inline";
		$checkboxParameters = $this->checkBoxParams(
			$additionalInformation['itemFormElName'],
			$formElementValue,
			$itemCounter,
			$numberOfItems,
			implode('', $additionalInformation['fieldChangeFunc'])
		);
		$checkboxName = $additionalInformation['itemFormElName'] . '_' . $itemCounter;
		$checkboxId = $additionalInformation['itemFormElID'] . '_' . $itemCounter;
		return '
			<div class="checkbox' . ($inline ? ' checkbox-inline' : '') . (!$disabled ? '' : ' disabled') . '">
				<label>
					<input type="checkbox"
						value="1"
						name="' . $checkboxName . '"
						' . $checkboxParameters . '
						' . $additionalInformation['onFocus'] . '
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
	protected function checkBoxParams($itemName, $formElementValue, $checkbox, $checkboxesCount, $additionalJavaScript = '') {
		$elementName = 'document.editform[' . Generalutility::quoteJSvalue($itemName) . ']';
		$checkboxPow = pow(2, $checkbox);
		$onClick = $elementName . '.value=this.checked?(' . $elementName . '.value|' . $checkboxPow . '):('
			. $elementName . '.value&' . (pow(2, $checkboxesCount) - 1 - $checkboxPow) . ');' . $additionalJavaScript;
		return ' onclick="' . htmlspecialchars($onClick) . '"' . ($formElementValue & $checkboxPow ? ' checked="checked"' : '');
	}

}
