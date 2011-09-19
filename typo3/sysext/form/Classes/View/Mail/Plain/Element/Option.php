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
 * View object for the option element
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_View_Mail_Plain_Element_Option extends tx_form_View_Mail_Plain_Element_Abstract {

	/**
	 * Constructor
	 *
	 * @param tx_form_Domain_Model_Element_Option $model Model for this element
	 * @param integer $spaces
	 */
	public function __construct(tx_form_Domain_Model_Element_Option $model, $spaces) {
		parent::__construct($model, $spaces);
	}

	/**
	 * @return string
	 */
	public function render() {
		$data = $this->getData();

		if ($data != '') {
			return str_repeat(chr(32), $this->spaces) . $data;
		}
	}

	/**
	 * @return string
	 */
	protected function getData() {
		$value = '';

		if (
			array_key_exists('selected', $this->model->getAllowedAttributes()) &&
			$this->model->hasAttribute('selected')
		) {
			$value = $this->model->getData();
		}

		return $value;
	}
}
?>