<?php
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
 * Abstract for attribute objects
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
abstract class tx_form_Domain_Model_Attributes_Abstract {

	/**
	 * The value of the attribute
	 *
	 * @var array
	 */
	protected $value;

	/**
	 * Internal Id of the element
	 *
	 * @var integer
	 */
	protected $elementId;

	/**
	 * The content object
	 *
	 * @var tslib_cObj
	 */
	protected $localCobj;

	/**
	 * Constructor
	 *
	 * @param string $value Attribute value
	 * @return void
	 */
	public function __construct($value, $elementId) {
		$this->localCobj = t3lib_div::makeInstance('tslib_cObj');
		$this->setValue($value);
		$this->elementId = (integer) $elementId;
	}

	/**
	 * Set the value
	 *
	 * @param string $valueThe value to set
	 * @return void
	 */
	public function setValue($value) {
		if (is_string($value) === FALSE) {
			$value = '';
		}

		$this->value = $value;
	}

	/**
	 * Gets the accordant attribute value.
	 *
	 * @return string
	 */
	abstract public function getValue();
}
?>