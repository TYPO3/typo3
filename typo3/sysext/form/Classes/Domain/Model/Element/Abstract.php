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
 * Abstract for the form elements
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
abstract class tx_form_domain_model_element_abstract {

	/**
	 * Internal Id of the element
	 *
	 * @var integer
	 */
	protected $elementId;

	/**
	 * The name of the element
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * True if it accepts the parent name instead of its own
	 * This is necessary for groups
	 *
	 * @var boolean
	 */
	protected $acceptsParentName = FALSE;

	/**
	 * Attribute object
	 *
	 * @var tx_form_domain_model_attributes
	 */
	protected $attributes;

	/**
	 * Additional object
	 *
	 * @var tx_form_domain_model_additional
	 */
	protected $additional;

	/**
	 * Layout override for the element
	 *
	 * @var string
	 */
	protected $layout;

	/**
	 * Value of the element
	 *
	 * @var mixed
	 */
	protected $value;

	/**
	 * Content of the element when no singleton tag
	 * <option>, <textarea>
	 *
	 * @var mixed
	 */
	protected $data;

	/**
	 * Allowed additionals for elements
	 *
	 * @var array
	 */
	protected $allowedAdditional = array(
		'label',
		'legend'
	);

	/**
	 * Mandatory attributes for elements
	 *
	 * @var array
	 */
	protected $mandatoryAttributes = array();

	/**
	 * The content object
	 *
	 * @var tslib_cObj
	 */
	protected $localCobj;

	/**
	 * Constructor
	 *
	 * @param integer $elementId Internal Id of the element
	 * @param array $arguments Configuration array
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function __construct() {
		$this->localCobj = t3lib_div::makeInstance('tslib_cObj');
		$this->requestHandler = t3lib_div::makeInstance('tx_form_system_request');
		$this->validateClass = t3lib_div::makeInstance('tx_form_system_validate');
		$this->elementCounter = t3lib_div::makeInstance('tx_form_system_elementcounter');
		$this->setElementId();
		$this->createAttributes();
		$this->createAdditional();
		$this->createFilter();
	}

	/**
	 * Set the internal ID of the element
	 *
	 * @param integer $elementId Internal Id of the element
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function setElementId() {
		$this->elementId = $this->elementCounter->getElementId();
	}

	/**
	 * Get the internal ID for the id attribute
	 * of the outer tag of an element like <li>
	 *
	 * @return string
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function getElementId() {
		return $this->elementId;
	}

	/**
	 * Set the name for the element
	 *
	 * @param string $name The name
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function setName($name = '') {
		if ($name != '') {
			$this->name = (string) $name;
		} else {
			$this->name = 'id-' . $this->getElementId();
		}
	}

	/**
	 * Get the name of the element
	 *
	 * @return string
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Check to see if this element accepts the parent name instead of its own
	 *
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function acceptsParentName() {
		return $this->acceptsParentName;
	}

	/**
	 * Set a specific attribute by name and value
	 *
	 * @param string $attribute Name of the attribute
	 * @param mixed $value Value of the attribute
	 * @return object
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function setAttribute($attribute, $value) {
		if(array_key_exists($attribute, $this->allowedAttributes)) {
			$this->attributes->addAttribute($attribute, $value);
		}

		return $this;
	}

	/**
	 * Get the allowed attributes for an element
	 *
	 * @return array The allowed attributes
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function getAllowedAttributes() {
		return $this->allowedAttributes;
	}

	/**
	 * Get the mandatory attributes for an element
	 *
	 * @return array The mandatory attributes
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function getMandatoryAttributes() {
		return $this->mandatoryAttributes;
	}

	/**
	 * Check if element has attributes which are allowed
	 *
	 * @return boolean TRUE if there is a list of allowed attributes
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function hasAllowedAttributes() {
		if(isset($this->allowedAttributes)) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Check if element has additionals which are allowed
	 *
	 * @return boolean TRUE if there is a list of allowed additionals
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function hasAllowedAdditionals() {
		if(isset($this->allowedAdditional)) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Get the allowed additionals for an element
	 *
	 * @return array The allowed additionals
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function getAllowedAdditionals() {
		return $this->allowedAdditional;
	}

	/**
	 * Get the array with all attribute objects for the element
	 *
	 * @return array
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function getAttributes() {
		return $this->attributes->getAttributes();
	}

	/**
	 * Returns TRUE if attribute is set
	 *
	 * @param string $key The name of the attribute
	 * @return boolean
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function hasAttribute($key) {
		return $this->attributes->hasAttribute($key);
	}

	/**
	 * Get the value of a specific attribute by key
	 *
	 * @param string $key Name of the attribute
	 * @return mixed
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function getAttributeValue($key) {
		return $this->attributes->getValue($key);
	}

	/**
	 * Get the array with all additional objects for the element
	 *
	 * @return array
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function getAdditional() {
		return $this->additional->getAdditional();
	}

	/**
	 * Get a specific additional object by using the key
	 *
	 * @param string $key Key of the additional
	 * @return string The additional object
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function getAdditionalObjectByKey($key) {
		return $this->additional->getAdditionalObjectByKey($key);
	}

	/**
	 * Get the value of a specific additional by key
	 *
	 * @param string $key Name of the additional
	 * @return mixed
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function getAdditionalValue($key) {
		return $this->additional->getValue($key);
	}

	/**
	 * Load the attributes object
	 *
	 * @return tx_form_domain_model_attributes
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	protected function createAttributes() {
		$className = 'tx_form_domain_model_attributes_attributes';

		$this->attributes = t3lib_div::makeInstance($className, $this->elementId);
	}

	/**
	 * Set the layout override for the element
	 *
	 * @param string $layout The layout
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function setLayout($layout = '') {
		$this->layout = (string) $layout;
	}

	/**
	 * Get the layout for an element
	 *
	 * @return string XML for layout
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function getLayout() {
		return $this->layout;
	}

	/**
	 * Set the value for the element
	 *
	 * @param string $value The value
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function setValue($value = '') {
		$this->value = (string) $value;
	}

	/**
	 * Get the value for the element
	 *
	 * @return mixed
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * Set the content for the element
	 *
	 * @param string $data The content
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function setData($data = '') {
		$this->data = (string) $data;
	}

	/**
	 * Set the additionals from validation rules
	 *
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function setMessagesFromValidation() {
		if($this->validateClass->hasMessage($this->getName())) {
			$messages = $this->validateClass->getMessagesByName($this->getName());

			try {
				$this->setAdditional('mandatory', 'COA', $messages);
			} catch (Exception $exception) {
				throw new Exception ('Cannot call user function for additional ' . ucfirst($additional));
			}
		}
	}

	/**
	 * Set the additional error from validation rules
	 *
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function setErrorsFromValidation() {
		if($this->validateClass->hasErrors($this->getName())) {
			$errors = $this->validateClass->getErrorsByName($this->getName());

			try {
				$this->setAdditional('error', 'COA', $errors);
			} catch (Exception $exception) {
				throw new Exception ('Cannot call user function for additional ' . ucfirst($additional));
			}
		}
	}

	/**
	 * Set a specific additional by name and value
	 *
	 * @param string $additional Name of the additional
	 * @param mixed $value Value of the additional
	 * @return object
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function setAdditional($additional, $type, $value) {
		$this->additional->addAdditional($additional, $type, $value);

		return $this;
	}

	/**
	 * Check if additional exists
	 *
	 * @param string $key Name of the additional
	 * @return boolean
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function additionalIsSet($key) {
		return $this->additional->additionalIsSet($key);
	}

	/**
	 * Load the additional object
	 *
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	protected function createAdditional() {
		$className = 'tx_form_domain_model_additional_additional';

		$this->additional = t3lib_div::makeInstance($className);
	}

	/**
	 * Set the layout for an additional element
	 *
	 * @param string $key Name of the additional
	 * @param string $layout XML for layout
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function setAdditionalLayout($key, $layout) {
		$this->additional->setLayout($key, $layout);
	}

	/**
	 * Load the filter object
	 *
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	protected function createFilter() {
		$this->filter = t3lib_div::makeInstance('tx_form_system_filter');
	}

	/**
	 * Make a filter object for an element
	 * This is a shortcut to the function in _filter
	 *
	 * @param string $class Name of the filter
	 * @param array $arguments Arguments for the filter
	 * @return object Filter object
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function makeFilter($class, $arguments = array()) {
		$filter = $this->filter->makeFilter($class, $arguments);

		return $filter;
	}

	/**
	 * Add a filter to the filter list
	 * This is a shortcut to the function in _filter
	 *
	 * @param object $filter Filter object
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function addFilter($filter) {
		$this->filter->addFilter($filter);
	}

	/**
	 * Dummy function to check the request handler on input
	 * and set submitted data right for elements
	 *
	 * @return object
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function checkFilterAndSetIncomingDataFromRequest() {
		return $this;
	}
}
?>