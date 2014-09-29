<?php
namespace TYPO3\CMS\Backend\Form\Element;

/**
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
class CheckboxElement {

	/**
	 * @var \TYPO3\CMS\Backend\Form\FormEngine
	 */
	protected $formEngine;

	/**
	 * Constructor function, setting the FormEngine
	 *
	 * @param \TYPO3\CMS\Backend\Form\FormEngine $formEngine
	 */
	public function __construct(\TYPO3\CMS\Backend\Form\FormEngine $formEngine) {
		$this->formEngine = $formEngine;
	}

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
		// Traversing the array of items:
		$selItems = $this->formEngine->initItemArray($additionalInformation['fieldConf']);
		if ($config['itemsProcFunc']) {
			$selItems = $this->formEngine->procItems($selItems, $additionalInformation['fieldTSConfig']['itemsProcFunc.'], $config, $table, $row, $field);
		}
		if (!count($selItems)) {
			$selItems[] = array('', '');
		}
		$thisValue = (int)$additionalInformation['itemFormElValue'];
		$cols = (int)$config['cols'];
		$selItemsCount = count($selItems);
		if ($cols > 1) {
			$item .= '<table border="0" cellspacing="0" cellpadding="0" class="typo3-TCEforms-checkboxArray">';
			for ($c = 0; $c < $selItemsCount; $c++) {
				$p = $selItems[$c];
				if (!($c % $cols)) {
					$item .= '<tr>';
				}
				$cBP = $this->checkBoxParams($additionalInformation['itemFormElName'], $thisValue, $c, count($selItems), implode('', $additionalInformation['fieldChangeFunc']));
				$cBName = $additionalInformation['itemFormElName'] . '_' . $c;
				$cBID = $additionalInformation['itemFormElID'] . '_' . $c;
				$item .= '<td nowrap="nowrap">' . '<input type="checkbox"' . $this->formEngine->insertDefStyle('check')
					. ' value="1" name="' . $cBName . '"' . $cBP . $disabled . ' id="' . $cBID . '" />'
					. '<label for="' . $cBID . '">' . htmlspecialchars($p[0]) . '</label>&nbsp;'
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
			for ($c = 0; $c < $selItemsCount; $c++) {
				$p = $selItems[$c];
				$cBP = $this->checkBoxParams($additionalInformation['itemFormElName'], $thisValue, $c, count($selItems), implode('', $additionalInformation['fieldChangeFunc']));
				$cBName = $additionalInformation['itemFormElName'] . '_' . $c;
				$cBID = $additionalInformation['itemFormElID'] . '_' . $c;
				$item .= ($c > 0 ? '<br />' : '') . '<input type="checkbox"' . $this->formEngine->insertDefStyle('check')
					. ' value="1" name="' . $cBName . '"' . $cBP . $additionalInformation['onFocus'] . $disabled . ' id="' . $cBID . '" />'
					. '<label for="' . $cBID . '">' . htmlspecialchars($p[0]) . '</label>';
			}
		}
		if (!$disabled) {
			$item .= '<input type="hidden" name="' . $additionalInformation['itemFormElName'] . '" value="' . htmlspecialchars($thisValue) . '" />';
		}
		return $item;
	}

	/**
	 * Creates checkbox parameters
	 *
	 * @param string $itemName Form element name
	 * @param integer $thisValue The value of the checkbox (representing checkboxes with the bits)
	 * @param integer $c Checkbox # (0-9?)
	 * @param integer $iCount Total number of checkboxes in the array.
	 * @param string $addFunc Additional JavaScript for the onclick handler.
	 * @return string The onclick attribute + possibly the checked-option set.
	 */
	protected function checkBoxParams($itemName, $thisValue, $c, $iCount, $addFunc = '') {
		$onClick = $this->formEngine->elName($itemName) . '.value=this.checked?(' . $this->formEngine->elName($itemName) . '.value|' . pow(2, $c)
			. '):(' . $this->formEngine->elName($itemName) . '.value&' . (pow(2, $iCount) - 1 - pow(2, $c)) . ');' . $addFunc;
		$str = ' onclick="' . htmlspecialchars($onClick) . '"' . ($thisValue & pow(2, $c) ? ' checked="checked"' : '');
		return $str;
	}
}