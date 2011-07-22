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
 * Attribute 'name'
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_domain_model_attributes_name extends tx_form_domain_model_attributes_abstract implements tx_form_domain_model_attributes_interface {

	/**
	 * Addition to the name value
	 *
	 * @var string
	 */
	protected $addition;

	/**
	 * TRUE if value is expected without prefix
	 *
	 * @var boolean
	 */
	private $returnValueWithoutPrefix = FALSE;

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
	 * Return the name attribute without the prefix
	 *
	 * @return string
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function getValueWithoutPrefix() {
		$value = (string) $this->value;

			// Change spaces into hyphens
		$value = preg_replace('/\s/' , '-', $value);

			// Remove non-word characters
		$value = preg_replace('/[^a-zA-Z0-9_\-]+/', '', $value);

		if(empty($value)) {
			$value = $this->elementId;
		}

		return $value;
	}

	/**
	 * Sets the attribute 'name'.
	 * Used with all elements
	 * Case Insensitive
	 *
	 * This attribute names the element so that it may be referred to
	 * from style sheets or scripts.
	 *
	 * Note: This attribute has been included for backwards compatibility.
	 * Applications should use the id attribute to identify elements.
	 * This does not apply for form objects, only the form tag
	 *
	 * @return string Attribute value
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function getValue() {
		$value = $this->getValueWithoutPrefix();

		if($this->returnValueWithoutPrefix === FALSE) {
			$requestHandler = t3lib_div::makeInstance('tx_form_system_request');
			$attribute = $requestHandler->getPrefix() . '[' . $value . ']' . $this->addition;
		} else {
			$attribute = $value;
		}

		return $attribute;
	}

	/**
	 * Sets an additional string which will be added to the name
	 * This is necessarry in some cases like a multiple select box
	 *
	 * @param string $addition The additional string
	 * @return tx_form_domain_model_attributes_name
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function setAddition($addition) {
		$this->addition = (string) $addition;

		return $this;
	}

	/**
	 * TRUE if element is not allowed to use a prefix
	 * This is the case with the <form> tag
	 *
	 * @param boolean $parameter
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function setReturnValueWithoutPrefix($parameter) {
		$this->returnValueWithoutPrefix = (boolean) $parameter;
	}
}
?>