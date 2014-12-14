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
			$disabled = ' disabled';
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
			$radioId = htmlspecialchars($additionalInformation['itemFormElID'] . '_' . $checkbox);
			$radioOnClick = implode('', $additionalInformation['fieldChangeFunc']);
			$radioChecked = (string)$selectedItem[1] === (string)$additionalInformation['itemFormElValue'] ? ' checked="checked"' : '';
			$item .= '<div class="radio' . $disabled . '">'
				. '<label for="' . $radioId . '">'
				. '<input '
				. 'type="radio" '
				. $this->formEngine->insertDefStyle('radio') . ' '
				. 'name="' . htmlspecialchars($additionalInformation['itemFormElName']) . '" '
				. 'id="' . $radioId . '" '
				. 'value="' . htmlspecialchars($selectedItem[1]) . '" '
				. $radioChecked . ' '
				. $additionalInformation['onFocus'] . ' '
				. $disabled . ' '
				. 'onclick="' . htmlspecialchars($radioOnClick) . '" '
				. '/>'
				. htmlspecialchars($selectedItem[0])
				. '</label>'
			. '</div>';
		}
		return $item;
	}
}
