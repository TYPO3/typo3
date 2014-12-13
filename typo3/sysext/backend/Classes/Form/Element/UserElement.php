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
 * Generation of TCEform elements of the type "user"
 */
class UserElement extends AbstractFormElement {

	/**
	 * User defined field type
	 *
	 * @param string $table The table name of the record
	 * @param string $field The field name which this element is supposed to edit
	 * @param array $row The record data array where the value(s) for the field can be found
	 * @param array $additionalInformation An array with additional configuration options.
	 * @return string The HTML code for the TCEform field
	 */
	public function render($table, $field, $row, &$additionalInformation) {
		$additionalInformation['table'] = $table;
		$additionalInformation['field'] = $field;
		$additionalInformation['row'] = $row;
		$additionalInformation['parameters'] = isset($additionalInformation['fieldConf']['config']['parameters'])
			? $additionalInformation['fieldConf']['config']['parameters']
			: array();
		$additionalInformation['pObj'] = $this->formEngine;
		return GeneralUtility::callUserFunction(
			$additionalInformation['fieldConf']['config']['userFunc'],
			$additionalInformation,
			$this->formEngine
		);
	}
}
