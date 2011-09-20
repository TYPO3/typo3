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
 * Abstract for the form elements
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
abstract class tx_form_Domain_Model_Element_Abstract {
	const ELEMENT_TYPE_FORM = 'FORM';
	const ELEMENT_TYPE_PLAIN = 'PLAIN';
	const ELEMENT_TYPE_CONTENT = 'CONTENT';

	/**
	 * Internal Id of the element
	 *
	 * @var integer
	 */
	protected $elementId;

	/**
	 * @var string
	 */
	protected $elementType = self::ELEMENT_TYPE_FORM;

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
	 * @var tx_form_Domain_Model_Attributes_Attributes
	 */
	protected $attributes;

	/**
	 * Additional object
	 *
	 * @var tx_form_Domain_Model_Additional_Additional
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
	 * @var array
	 */
	protected $allowedAttributes = array();

	/**
	 * The content object
	 *
	 * @var tslib_cObj
	 */
	protected $localCobj;

	/**
	 * @var tx_form_System_Request
	 */
	protected $requestHandler;

	/**
	 * @var tx_form_System_Elementcounter
	 */
	protected $elementCounter;

	/**
	 * @var tx_form_System_Validate
	 */
	protected $validateClass;

	/**
	 * @var tx_form_System_Filter
	 */
	protected $filter;

	/**
	 * Constructor
	 *
	 * @param integer $elementId Internal Id of the element
	 * @param array $arguments Configuration array
	 * @return void
	 */
	public function __construct() {
		$this->localCobj = t3lib_div::makeInstance('tslib_cObj');
		$this->requestHandler = t3lib_div::makeInstance('tx_form_System_Request');
		$this->validateClass = t3lib_div::makeInstance('tx_form_System_Validate');
		$this->elementCounter = t3lib_div::makeInstance('tx_form_System_Elementcounter');
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
	 */
	public function setElementId() {
		$this->elementId = $this->elementCounter->getElementId();
	}

	/**
	 * Get the internal ID for the id attribute
	 * of the outer tag of an element like <li>
	 *
	 * @return string
	 */
	public function getElementId() {
		return $this->elementId;
	}

	/**
	 * Gets the element type.
	 *
	 * @return string
	 */
	public function getElementType() {
		return $this->elementType;
	}

	/**
	 * Set the name for the element
	 *
	 * @param string $name The name
	 * @return void
	 */
	public function setName($name = '') {
		if (is_string($name) === FALSE) {
			$name = '';
		}

		if ($name !== '') {
			$this->name = $name;
		} else {
			$this->name = 'id-' . $this->getElementId();
		}
	}

	/**
	 * Get the name of the element
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Check to see if this element accepts the parent name instead of its own
	 *
	 * @return boolean
	 */
	public function acceptsParentName() {
		return $this->acceptsParentName;
	}

	/**
	 * Set a specific attribute by name and value
	 *
	 * @param string $attribute Name of the attribute
	 * @param mixed $value Value of the attribute
	 * @return tx_form_Domain_Model_Element_Abstract
	 */
	public function setAttribute($attribute, $value) {
		if (array_key_exists($attribute, $this->allowedAttributes)) {
			$this->attributes->addAttribute($attribute, $value);
		}

		return $this;
	}

	/**
	 * Get the allowed attributes for an element
	 *
	 * @return array The allowed attributes
	 */
	public function getAllowedAttributes() {
		return $this->allowedAttributes;
	}

	/**
	 * Get the mandatory attributes for an element
	 *
	 * @return array The mandatory attributes
	 */
	public function getMandatoryAttributes() {
		return $this->mandatoryAttributes;
	}

	/**
	 * Check if element has attributes which are allowed
	 *
	 * @return boolean TRUE if there is a list of allowed attributes
	 */
	public function hasAllowedAttributes() {
		return (empty($this->allowedAttributes) === FALSE);
	}

	/**
	 * Check if element has additionals which are allowed
	 *
	 * @return boolean TRUE if there is a list of allowed additionals
	 */
	public function hasAllowedAdditionals() {
		return (empty($this->allowedAdditional) === FALSE);
	}

	/**
	 * Get the allowed additionals for an element
	 *
	 * @return array The allowed additionals
	 */
	public function getAllowedAdditionals() {
		return $this->allowedAdditional;
	}

	/**
	 * Get the array with all attribute objects for the element
	 *
	 * @return array
	 */
	public function getAttributes() {
		return $this->attributes->getAttributes();
	}

	/**
	 * Returns TRUE if attribute is set
	 *
	 * @param string $key The name of the attribute
	 * @return boolean
	 */
	public function hasAttribute($key) {
		return $this->attributes->hasAttribute($key);
	}

	/**
	 * Get the value of a specific attribute by key
	 *
	 * @param string $key Name of the attribute
	 * @return mixed
	 */
	public function getAttributeValue($key) {
		return $this->attributes->getValue($key);
	}

	/**
	 * Get the array with all additional objects for the element
	 *
	 * @return array
	 */
	public function getAdditional() {
		return $this->additional->getAdditional();
	}

	/**
	 * Get a specific additional object by using the key
	 *
	 * @param string $key Key of the additional
	 * @return string The additional object
	 */
	public function getAdditionalObjectByKey($key) {
		return $this->additional->getAdditionalObjectByKey($key);
	}

	/**
	 * Get the value of a specific additional by key
	 *
	 * @param string $key Name of the additional
	 * @return mixed
	 */
	public function getAdditionalValue($key) {
		return $this->additional->getValue($key);
	}

	/**
	 * Load the attributes object
	 *
	 * @return void
	 */
	protected function createAttributes() {
		$className = 'tx_form_Domain_Model_Attributes_Attributes';
		$this->attributes = t3lib_div::makeInstance($className, $this->elementId);
	}

	/**
	 * Set the layout override for the element
	 *
	 * @param string $layout The layout
	 * @return void
	 */
	public function setLayout($layout = '') {
		$this->layout = (string) $layout;
	}

	/**
	 * Get the layout for an element
	 *
	 * @return string XML for layout
	 */
	public function getLayout() {
		return $this->layout;
	}

	/**
	 * Set the value for the element
	 *
	 * @param string $value The value
	 * @return void
	 */
	public function setValue($value = '') {
		$this->value = (string) $value;
	}

	/**
	 * Get the value for the element
	 *
	 * @return mixed
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * Set the content for the element
	 *
	 * @param string $data The content
	 * @return void
	 */
	public function setData($data = '') {
		$this->data = (string) $data;
	}

	/**
	 * Set the additionals from validation rules
	 *
	 * @return void
	 */
	public function setMessagesFromValidation() {
		if ($this->validateClass->hasMessage($this->getName())) {
			$messages = $this->validateClass->getMessagesByName($this->getName());

			$this->setAdditional('mandatory', 'COA', $messages);
		}
	}

	/**
	 * Set the additional error from validation rules
	 *
	 * @return void
	 */
	public function setErrorsFromValidation() {
		if ($this->validateClass->hasErrors($this->getName())) {
			$errors = $this->validateClass->getErrorsByName($this->getName());

			$this->setAdditional('error', 'COA', $errors);
		}
	}

	/**
	 * Set a specific additional by name and value
	 *
	 * @param string $additional Name of the additional
	 * @param mixed $value Value of the additional
	 * @return tx_form_Domain_Model_Element_Abstract
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
	 */
	public function additionalIsSet($key) {
		return $this->additional->additionalIsSet($key);
	}

	/**
	 * Load the additional object
	 *
	 * @return void
	 */
	protected function createAdditional() {
		$className = 'tx_form_Domain_Model_Additional_Additional';

		$this->additional = t3lib_div::makeInstance($className);
	}

	/**
	 * Set the layout for an additional element
	 *
	 * @param string $key Name of the additional
	 * @param string $layout XML for layout
	 * @return void
	 */
	public function setAdditionalLayout($key, $layout) {
		$this->additional->setLayout($key, $layout);
	}

	/**
	 * Load the filter object
	 *
	 * @return void
	 */
	protected function createFilter() {
		$this->filter = t3lib_div::makeInstance('tx_form_System_Filter');
	}

	/**
	 * Make a filter object for an element
	 * This is a shortcut to the function in _filter
	 *
	 * @param string $class Name of the filter
	 * @param array $arguments Arguments for the filter
	 * @return object Filter object
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
	 */
	public function addFilter($filter) {
		$this->filter->addFilter($filter);
	}

	/**
	 * Dummy function to check the request handler on input
	 * and set submitted data right for elements
	 *
	 * @return tx_form_Domain_Model_Element_Abstract
	 */
	public function checkFilterAndSetIncomingDataFromRequest() {
		return $this;
	}
}
?>