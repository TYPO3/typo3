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
 * Generation of TCEform elements of the type "radio"
 */
class RadioElement extends AbstractFormElement {

	/**
	 * This will render a series of radio buttons.
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

		// Get items for the array
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

		// Traverse the items, making the form elements
		foreach ($selectedItems as $checkbox => $selectedItem) {
			$radioId = $additionalInformation['itemFormElID'] . '_' . $checkbox;
			$radioOnClick = implode('', $additionalInformation['fieldChangeFunc']);
			$radioChecked = (string)$selectedItem[1] === (string)$additionalInformation['itemFormElValue'] ? ' checked="checked"' : '';
			$item .= '<input type="radio"' . $this->formEngine->insertDefStyle('radio') . ' name="' . $additionalInformation['itemFormElName']
				. '" value="' . htmlspecialchars($selectedItem[1]) . '" onclick="' . htmlspecialchars($radioOnClick) . '"' . $radioChecked
				. $additionalInformation['onFocus'] . $disabled . ' id="' . $radioId . '" />
					<label for="' . $radioId . '">' . htmlspecialchars($selectedItem[0]) . '</label>
					<br />';
		}
		return $item;
	}
}
