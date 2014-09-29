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
class RadioElement {

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
		// Get items for the array:
		$selItems = $this->formEngine->initItemArray($additionalInformation['fieldConf']);
		if ($config['itemsProcFunc']) {
			$selItems = $this->formEngine->procItems($selItems, $additionalInformation['fieldTSConfig']['itemsProcFunc.'], $config, $table, $row, $field);
		}
		// Traverse the items, making the form elements:
		$selItemsCount = count($selItems);
		for ($c = 0; $c < $selItemsCount; $c++) {
			$p = $selItems[$c];
			$rID = $additionalInformation['itemFormElID'] . '_' . $c;
			$rOnClick = implode('', $additionalInformation['fieldChangeFunc']);
			$rChecked = (string)$p[1] === (string)$additionalInformation['itemFormElValue'] ? ' checked="checked"' : '';
			$item .= '<input type="radio"' . $this->formEngine->insertDefStyle('radio') . ' name="' . $additionalInformation['itemFormElName']
				. '" value="' . htmlspecialchars($p[1]) . '" onclick="' . htmlspecialchars($rOnClick) . '"' . $rChecked
				. $additionalInformation['onFocus'] . $disabled . ' id="' . $rID . '" />
					<label for="' . $rID . '">' . htmlspecialchars($p[0]) . '</label>
					<br />';
		}
		return $item;
	}
}