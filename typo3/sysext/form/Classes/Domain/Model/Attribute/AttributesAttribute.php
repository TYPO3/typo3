<?php
namespace TYPO3\CMS\Form\Domain\Model\Attribute;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;

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
	 * @var int
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
	 * @param int $elementId The ID of the element
	 * @return AttributesAttribute
	 */
	public function __construct($elementId) {
		$this->elementId = (int)$elementId;
		$this->localCobj = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);
		$this->localizationHandler = GeneralUtility::makeInstance(\TYPO3\CMS\Form\Localization::class);
		$this->requestHandler = GeneralUtility::makeInstance(\TYPO3\CMS\Form\Request::class);
	}

	/**
	 * Add an attribute object to the attribute array
	 *
	 * @param string $class Name of the attribute
	 * @param mixed $value Typoscript configuration to construct value
	 * @return \TYPO3\CMS\Form\Domain\Model\Attribute\AbstractAttribute
	 */
	public function addAttribute($class, $value) {
		$class = strtolower((string)$class);
		$className = 'TYPO3\\CMS\\Form\\Domain\\Model\\Attribute\\' . ucfirst($class) . 'Attribute';
		$this->attributes[$class] = GeneralUtility::makeInstance($className, $value, $this->elementId);
		return $this;
	}

	/**
	 * Remove an attribute object from the attribute array
	 *
	 * @param string $class Name of the attribute
	 * @return \TYPO3\CMS\Form\Domain\Model\Attribute\AbstractAttribute
	 */
	public function removeAttribute($class) {
		unset($this->attributes[$class]);
		return $this;
	}

	/**
	 * Get the attributes of the object
	 *
	 * @return \TYPO3\CMS\Form\Domain\Model\Attribute\AbstractAttribute[] Attributes objects
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
	 * @param \TYPO3\CMS\Form\Domain\Model\Attribute\AbstractAttribute $attributeObject The attribute object
	 * @return void
	 */
	public function setAttribute($key, $attributeObject) {
		$this->attributes[$key] = (object) $attributeObject;
	}

	/**
	 * Returns TRUE if attribute is set
	 *
	 * @param string $key The name of the attribute
	 * @return bool
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
