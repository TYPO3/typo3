<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Patrick Broens (patrick@patrickbroens.nl)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * View object for the textarea element
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_View_Mail_Plain_Element_Textarea extends tx_form_View_Mail_Plain_Element_Abstract {

	/**
	 * Constructor
	 *
	 * @param tx_form_Domain_Model_Element_Textarea $model Model for this element
	 * @param integer $spaces
	 */
	public function __construct(tx_form_Domain_Model_Element_Textarea $model, $spaces) {
		parent::__construct($model, $spaces);
	}

	/**
	 * @return string
	 */
	public function render() {
		$content = $this->getLabel() . ': ' .
			chr(10) .
			str_repeat(chr(32), $this->spaces + 4) .
			$this->getData();

		return str_repeat(chr(32), $this->spaces) . $content;
	}

	/**
	 * @return mixed
	 */
	protected function getLabel() {
		$label = '';

		if ($this->model->additionalIsSet('label')) {
			$label = $this->model->getAdditionalValue('label');
		} else {
			$label = $this->model->getAttributeValue('name');
		}

		return $label;
	}

	protected function getData() {
		$value = str_replace(
			chr(10),
			chr(10) . str_repeat(chr(32), $this->spaces + 4),
			$this->model->getData()
		);

		return $value;
	}
}
?>