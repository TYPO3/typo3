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

/**
 * Generation of TCEform elements of the type "check"
 */
class CheckboxElement extends AbstractFormElement {

	/**
	 * This will render a checkbox or an array of checkboxes
	 *
	 * @param string $table The table name of the record
	 * @param string $field The field name which this element is supposed to edit
	 * @param array $row The record data array where the value(s) for the field can be found
	 * @param array $additionalInformation An array with additional configuration options.
	 * @return string The HTML code for the TCEform field
	 */
	public function render($table, $field, $row, &$additionalInformation) {
		$config = $additionalInformation['fieldConf']['config'];
		$item = '';
		$disabled = FALSE;
		if ($this->formEngine->renderReadonly || $config['readOnly']) {
			$disabled = TRUE;
		}
		// Traversing the array of items
		$items = $this->formEngine->initItemArray($additionalInformation['fieldConf']);
		if ($config['itemsProcFunc']) {
			$items = $this->formEngine->procItems(
				$items,
				$additionalInformation['fieldTSConfig']['itemsProcFunc.'],
				$config,
				$table,
				$row,
				$field
			);
		}

		$numberOfItems = count($items);
		if ($numberOfItems === 0) {
			$items[] = array('', '');
			$numberOfItems = 1;
		}
		$formElementValue = (int)$additionalInformation['itemFormElValue'];
		$cols = (int)$config['cols'];
		if ($cols > 1) {
			$colWidth = floor(12 / $cols);
			$colLeftover = 12 - $colWidth * $cols;
			for ($counter = 0; $counter < $numberOfItems; $counter++) {
				if (!($counter % $cols)) {
					$item .= '<div class="row">';
				}
				$item .= '<div class="col-md-' . $colWidth . '">'
					. $this->renderSingleCheckboxElement($items[$counter][0], $counter,  $formElementValue, $numberOfItems, $additionalInformation, $disabled)
					. '</div>';
				if ($counter % $cols + 1 == $cols) {
					$item .= ($colLeftover > 0 ? '<div class="col-md-' . $colLeftover . '"></div>' : '') . '</div>';
				}
			}
			if ($counter % $cols) {
				$rest = $cols - $counter % $cols;
				for ($counter = 0; $counter < $rest; $counter++) {
					$item .= '<div class="col-md-' . $colWidth . '></div>';
				}
				if ($counter > 0) {
					$item .= ($colLeftover > 0 ? '<div class="col-md-' . $colLeftover . '"></div>' : '') . '</div>';
				}
			}
		} else {
			for ($counter = 0; $counter < $numberOfItems; $counter++) {
				$item .=  $this->renderSingleCheckboxElement($items[$counter][0], $counter, $formElementValue, $numberOfItems, $additionalInformation, $disabled);
			}
		}
		if (!$disabled) {
			$item .= '<input type="hidden" name="' . $additionalInformation['itemFormElName'] . '" value="' . htmlspecialchars($formElementValue) . '" />';
		}
		return $item;
	}

	/**
	 * This functions builds the HTML output for the checkbox
	 *
	 * @param string $label Label of this item
	 * @param integer $itemCounter Number of this element in the list of all elements
	 * @param integer $formElementValue Value of this element
	 * @param integer $numberOfItems Full number of items
	 * @param array $additionalInformation Information with additional configuration options.
	 * @param boolean $disabled TRUE if form element is disabled
	 * @return string Single element HTML
	 */
	protected function renderSingleCheckboxElement($label, $itemCounter, $formElementValue, $numberOfItems, $additionalInformation, $disabled) {
		$checkboxParameters = $this->checkBoxParams(
			$additionalInformation['itemFormElName'],
			$formElementValue,
			$itemCounter,
			$numberOfItems,
			implode('', $additionalInformation['fieldChangeFunc'])
		);
		$checkboxName = $additionalInformation['itemFormElName'] . '_' . $itemCounter;
		$checkboxId = $additionalInformation['itemFormElID'] . '_' . $itemCounter;
		return '<div class="checkbox">'
			. '<label for="' . $checkboxId . '">'
			. '<input '
			. 'type="checkbox" '
			. 'value="1" '
			. $this->formEngine->insertDefStyle('check') . ' '
			. 'name="' . $checkboxName . '" '
			. $checkboxParameters . ' '
			. $additionalInformation['onFocus'] . ' '
			. (!$disabled ?: ' disabled="disabled"')
			. ' id="' . $checkboxId . '" '
			. ' />'
			.  htmlspecialchars($label)
			. '</label>'
		. '</div>';
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
		$elementName = $this->formEngine->elName($itemName);
		$checkboxPow = pow(2, $checkbox);
		$onClick = $elementName . '.value=this.checked?(' . $elementName . '.value|' . $checkboxPow . '):('
			. $elementName . '.value&' . (pow(2, $checkboxesCount) - 1 - $checkboxPow) . ');' . $additionalJavaScript;
		return ' onclick="' . htmlspecialchars($onClick) . '"' . ($formElementValue & $checkboxPow ? ' checked="checked"' : '');
	}
}
