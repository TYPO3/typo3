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
	 * This will render a checkbox OR an array of checkboxes
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
		$disabled = '';
		if ($this->formEngine->renderReadonly || $config['readOnly']) {
			$disabled = ' disabled="disabled"';
		}
		// Traversing the array of items
		$selectedItems = $this->formEngine->initItemArray($additionalInformation['fieldConf']);
		if ($config['itemsProcFunc']) {
			$selectedItems = $this->formEngine->procItems(
				$selectedItems,
				$additionalInformation['fieldTSConfig']['itemsProcFunc.'],
				$config,
				$table,
				$row,
				$field
			);
		}

		$selectedItemsCount = count($selectedItems);
		if ($selectedItemsCount === 0) {
			$selectedItems[] = array('', '');
			$selectedItemsCount = 1;
		}

		$formElementValue = (int)$additionalInformation['itemFormElValue'];
		$cols = (int)$config['cols'];
		if ($cols > 1) {
			$item .= '<table border="0" cellspacing="0" cellpadding="0" class="typo3-TCEforms-checkboxArray">';
			for ($c = 0; $c < $selectedItemsCount; $c++) {
				$selectedItem = $selectedItems[$c];
				if (!($c % $cols)) {
					$item .= '<tr>';
				}
				$checkboxParameters = $this->checkBoxParams(
					$additionalInformation['itemFormElName'],
					$formElementValue,
					$c,
					$selectedItemsCount,
					implode('', $additionalInformation['fieldChangeFunc'])
				);
				$checkboxName = $additionalInformation['itemFormElName'] . '_' . $c;
				$checkboxId = $additionalInformation['itemFormElID'] . '_' . $c;
				$item .= '<td nowrap="nowrap"><input type="checkbox" ' . $this->formEngine->insertDefStyle('check')
					. ' value="1" name="' . $checkboxName . '" ' . $checkboxParameters . $disabled . ' id="' . $checkboxId . '" />'
					. '<label for="' . $checkboxId . '">' . htmlspecialchars($selectedItem[0]) . '</label>&nbsp;'
					. '</td>';
				if ($c % $cols + 1 == $cols) {
					$item .= '</tr>';
				}
			}
			if ($c % $cols) {
				$rest = $cols - $c % $cols;
				for ($c = 0; $c < $rest; $c++) {
					$item .= '<td></td>';
				}
				if ($c > 0) {
					$item .= '</tr>';
				}
			}
			$item .= '</table>';
		} else {
			for ($c = 0; $c < $selectedItemsCount; $c++) {
				$selectedItem = $selectedItems[$c];
				$checkboxParameters = $this->checkBoxParams(
					$additionalInformation['itemFormElName'],
					$formElementValue,
					$c,
					$selectedItemsCount,
					implode('', $additionalInformation['fieldChangeFunc'])
				);
				$checkboxName = $additionalInformation['itemFormElName'] . '_' . $c;
				$checkboxId = $additionalInformation['itemFormElID'] . '_' . $c;
				$item .= ($c > 0 ? '<br />' : '') . '<input type="checkbox" ' . $this->formEngine->insertDefStyle('check')
					. ' value="1" name="' . $checkboxName . '"' . $checkboxParameters . $additionalInformation['onFocus'] . $disabled
					. ' id="' . $checkboxId . '" /> '
					. '<label for="' . $checkboxId . '">' . htmlspecialchars($selectedItem[0]) . '</label>';
			}
		}
		if (!$disabled) {
			$item .= '<input type="hidden" name="' . $additionalInformation['itemFormElName'] . '" value="' . htmlspecialchars($formElementValue) . '" />';
		}
		return $item;
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
