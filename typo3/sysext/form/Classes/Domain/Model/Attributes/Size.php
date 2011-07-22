<?php
declare(encoding = 'utf-8');

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008 Patrick Broens (patrick@patrickbroens.nl)
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
 * Attribute 'size'
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_domain_model_attributes_size extends tx_form_domain_model_attributes_abstract implements tx_form_domain_model_attributes_interface {

	/**
	 * Constructor
	 *
	 * @param string $value Attribute value
	 * @param integer $elementId The ID of the element
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function __construct($value, $elementId) {
		parent::__construct($value, $elementId);
	}

	/**
	 * Sets the attribute 'size'.
	 * Used with the element 'input'
	 * Not subject to case changes, because it's an integer
	 *
	 * This attribute tells the user agent the initial width of the control.
	 * The width is given in pixels except when type attribute
	 * has the value "text" or "password".
	 * In that case, its value refers to the (integer) number of characters.
	 *
	 * @return integer Attribute value
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function getValue() {
		$value = (integer) $this->value;

		if($value <= 0) {
			$attribute = 40;
		} else {
			$attribute = $value;
		}

		return $attribute;
	}
}
?>