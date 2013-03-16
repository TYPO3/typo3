<?php
namespace TYPO3\CMS\Form\Domain\Model\Attribute;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2013 Patrick Broens (patrick@patrickbroens.nl)
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
 */
class AttributesAttribute {

	/**
	 * The attributes of the element
	 *
	 * @var array
	 */
	protected $attributes = array();

	/**
	 * Internal Id of the element
	 *
	 * @var integer
	 */
	protected $elementId;

	/**
	 * The content object
	 *
	 * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	protected $localCobj;

	/**
	 * Constructor
	 *
	 * @param integer $elementId The ID of the element
	 * @return void
	 */
	public function __construct($elementId) {
		$this->elementId = (int) $elementId;
		$this->localCobj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
		$this->localizationHandler = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Form\\Localization');
		$this->requestHandler = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Form\\Request');
	}

	/**
	 * Add an attribute object to the attribute array
	 *
	 * @param string $class Name of the attribute
	 * @param mixed $value Typoscript configuration to construct value
	 * @return tx_form_Domain_Model_Attributes
	 */
	public function addAttribute($class, $value) {
		$class = strtolower((string) $class);
		$className = 'TYPO3\\CMS\\Form\\Domain\\Model\\Attribute\\' . ucfirst($class) . 'Attribute';
		$this->attributes[$class] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($className, $value, $this->elementId);
		return $this;
	}

	/**
	 * Remove an attribute object from the attribute array
	 *
	 * @param string $class Name of the attribute
	 * @return tx_form_Domain_Model_Attributes
	 */
	public function removeAttribute($class) {
		unset($this->attributes[$class]);
		return $this;
	}

	/**
	 * Get the attributes of the object
	 *
	 * @return array Attributes objects
	 */
	public function getAttributes() {
		return $this->attributes;
	}

	/**
	 * Get a specific attribute object by using the key
	 *
	 * @param string $key Key of the attribute
	 * @return \TYPO3\CMS\Form\Domain\Model\Attribute\AbstractAttribute The attribute object
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
	 */
	public function setAttribute($key, $attributeObject) {
		$this->attributes[$key] = (object) $attributeObject;
	}

	/**
	 * Returns TRUE if attribute is set
	 *
	 * @param string $key The name of the attribute
	 * @return boolean
	 */
	public function hasAttribute($key) {
		return isset($this->attributes[$key]);
	}

	/**
	 * Set the value of a specific attribute object
	 *
	 * @param $key string Name of the object
	 * @param $value string The value
	 * @return void
	 */
	public function setValue($key, $value) {
		$this->getAttributeObjectByKey($key)->setValue($value);
	}

	/**
	 * Get a specific attribute value by using the key
	 *
	 * @param string $key Key of the attribute
	 * @return string The content of the attribute
	 */
	public function getValue($key) {
		return $this->getAttributeObjectByKey($key)->getValue();
	}

}

?>