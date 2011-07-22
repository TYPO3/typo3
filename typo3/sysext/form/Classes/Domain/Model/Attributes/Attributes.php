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
 * Attribute class for the form elements
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_domain_model_attributes_attributes {

	/**
	 * The attributes of the element
	 *
	 * @var array
	 */
	private $attributes = array();

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
	 * @param integer $elementId The ID of the element
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function __construct($elementId) {
		$this->elementId = (integer) $elementId;
		$this->localCobj = t3lib_div::makeInstance('tslib_cObj');
		$this->localizationHandler = t3lib_div::makeInstance('tx_form_system_localization');
		$this->requestHandler = t3lib_div::makeInstance('tx_form_system_request');
	}

	/**
	 * Add an attribute object to the attribute array
	 *
	 * @param string $class Name of the attribute
	 * @param mixed $value Typoscript configuration to construct value
	 * @return tx_form_domain_model_attributes
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function addAttribute($class, $value) {
		$class = strtolower((string) $class);
		$className = 'tx_form_domain_model_attributes_' . $class;

		$this->attributes[$class] = t3lib_div::makeInstance($className, $value, $this->elementId);

		return $this;
	}

	/**
	 * Remove an attribute object from the attribute array
	 *
	 * @param string $class Name of the attribute
	 * @return tx_form_domain_model_attributes
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function removeAttribute($class) {
		unset($this->attributes[$class]);

		return $this;
	}

	/**
	 * Get the attributes of the object
	 *
	 * @return array Attributes objects
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function getAttributes() {
		return $this->attributes;
	}

	/**
	 * Get a specific attribute object by using the key
	 *
	 * @param string $key Key of the attribute
	 * @return string The attribute object
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function getAttributeObjectByKey($key) {
		return $this->attributes[$key];
	}

	/**
	 * Add an attribute object to the attribute array
	 *
	 * @param string $key The name of the attribute
	 * @param object $attributeObject The attribute object
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function setAttribute($key, $attributeObject) {
		$this->attributes[$key] = (object) $attributeObject;
	}

	/**
	 * Returns TRUE if attribute is set
	 *
	 * @param string $key The name of the attribute
	 * @return boolean
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function hasAttribute($key) {
		if(isset($this->attributes[$key])) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Set the value of a specific attribute object
	 *
	 * @param $key string Name of the object
	 * @param $value string The value
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function setValue($key, $value) {
		$this->attributes[$key]->setValue($value);
	}

	/**
	 * Get a specific attribute value by using the key
	 *
	 * @param string $key Key of the attribute
	 * @return string The content of the attribute
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function getValue($key) {
		if($this->attributes[$key]) {
			return $this->attributes[$key]->getValue();
		}
	}
}
?>