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
 * View object for the hidden element
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_Form_View_Mail_Plain_Element_Hidden extends tx_Form_View_Mail_Plain_Element {

	/**
	 * Constructor
	 *
	 * @param tx_Form_Domain_Model_Element_Hidden $model Model for this element
	 * @return void
	 */
	public function __construct(tx_Form_Domain_Model_Element_Hidden $model, $spaces) {
		parent::__construct($model, $spaces);
	}

	public function render() {
		$content = $this->getLabel() . ': ' . $this->getValue();

		return str_repeat(chr(32), $this->spaces) . $content;
	}

	protected function getLabel() {
		$label = $this->model->getName();

		return $label;
	}

	protected function getValue() {
		$value = $this->model->getAttributeValue('value');

		return $value;
	}
}
?>