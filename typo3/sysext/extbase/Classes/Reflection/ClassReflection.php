<?php

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Reflection
 * @version $Id: ClassReflection.php 1811 2009-01-28 12:04:49Z robert $
 */

/**
 * Extended version of the ReflectionClass
 *
 * @package FLOW3
 * @subpackage Reflection
 * @version $Id: ClassReflection.php 1811 2009-01-28 12:04:49Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_ExtBase_Reflection_ClassReflection extends ReflectionClass {

	/**
	 * @var Tx_ExtBase_Reflection_DocCommentParser Holds an instance of the doc comment parser for this class
	 */
	protected $docCommentParser;

	/**
	 * The constructor - initializes the class Tx_ExtBase_Reflection_reflector
	 *
	 * @param  string $className: Name of the class Tx_ExtBase_Reflection_to reflect
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($className) {
		parent::__construct($className);
	}

	/**
	 * Replacement for the original getMethods() method which makes sure
	 * that Tx_ExtBase_Reflection_MethodReflection objects are returned instead of the
	 * orginal ReflectionMethod instances.
	 *
	 * @param  long $filter: A filter mask
	 * @return Tx_ExtBase_Reflection_MethodReflection Method reflection objects of the methods in this class
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getMethods($filter = NULL) {
		$extendedMethods = array();

		$methods = ($filter === NULL ? parent::getMethods() : parent::getMethods($filter));
		foreach ($methods as $method) {
			$extendedMethods[] = new Tx_ExtBase_Reflection_MethodReflection($this->getName(), $method->getName());
		}
		return $extendedMethods;
	}

	/**
	 * Replacement for the original getMethod() method which makes sure
	 * that Tx_ExtBase_Reflection_MethodReflection objects are returned instead of the
	 * orginal ReflectionMethod instances.
	 *
	 * @return Tx_ExtBase_Reflection_MethodReflection Method reflection object of the named method
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getMethod($name) {
		$parentMethod = parent::getMethod($name);
		if (!is_object($parentMethod)) return $parentMethod;
		return new Tx_ExtBase_Reflection_MethodReflection($this->getName(), $parentMethod->getName());
	}

	/**
	 * Replacement for the original getConstructor() method which makes sure
	 * that Tx_ExtBase_Reflection_MethodReflection objects are returned instead of the
	 * orginal ReflectionMethod instances.
	 *
	 * @return Tx_ExtBase_Reflection_MethodReflection Method reflection object of the constructor method
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getConstructor() {
		$parentConstructor = parent::getConstructor();
		if (!is_object($parentConstructor)) return $parentConstructor;
		return new Tx_ExtBase_Reflection_MethodReflection($this->getName(), $parentConstructor->getName());
	}

	/**
	 * Replacement for the original getProperties() method which makes sure
	 * that Tx_ExtBase_Reflection_PropertyReflection objects are returned instead of the
	 * orginal ReflectionProperty instances.
	 *
	 * @param  long $filter: A filter mask
	 * @return array of Tx_ExtBase_Reflection_PropertyReflection Property reflection objects of the properties in this class
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getProperties($filter = NULL) {
		$extendedProperties = array();
		$properties = ($filter === NULL ? parent::getProperties() : parent::getProperties($filter));
		foreach ($properties as $property) {
			$extendedProperties[] = new Tx_ExtBase_Reflection_PropertyReflection($this->getName(), $property->getName());
		}
		return $extendedProperties;
	}

	/**
	 * Replacement for the original getProperty() method which makes sure
	 * that a Tx_ExtBase_Reflection_PropertyReflection object is returned instead of the
	 * orginal ReflectionProperty instance.
	 *
	 * @param  string $name: Name of the property
	 * @return Tx_ExtBase_Reflection_PropertyReflection Property reflection object of the specified property in this class
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getProperty($name) {
		return new Tx_ExtBase_Reflection_PropertyReflection($this->getName(), $name);
	}

	/**
	 * Replacement for the original getInterfaces() method which makes sure
	 * that Tx_ExtBase_Reflection_ClassReflection objects are returned instead of the
	 * orginal ReflectionClass instances.
	 *
	 * @return array of Tx_ExtBase_Reflection_ClassReflection Class reflection objects of the properties in this class
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getInterfaces() {
		$extendedInterfaces = array();
		$interfaces = parent::getInterfaces();
		foreach ($interfaces as $interface) {
			$extendedInterfaces[] = new Tx_ExtBase_Reflection_ClassReflection($interface->getName());
		}
		return $extendedInterfaces;
	}

	/**
	 * Replacement for the original getParentClass() method which makes sure
	 * that a Tx_ExtBase_Reflection_ClassReflection object is returned instead of the
	 * orginal ReflectionClass instance.
	 *
	 * @return Tx_ExtBase_Reflection_ClassReflection Reflection of the parent class - if any
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getParentClass() {
		$parentClass = parent::getParentClass();
		return ($parentClass === NULL) ? NULL : new Tx_ExtBase_Reflection_ClassReflection($parentClass->getName());
	}

	/**
	 * Checks if the doc comment of this method is tagged with
	 * the specified tag
	 *
	 * @param  string $tag: Tag name to check for
	 * @return boolean TRUE if such a tag has been defined, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isTaggedWith($tag) {
		$result = $this->getDocCommentParser()->isTaggedWith($tag);
		return $result;
	}

	/**
	 * Returns an array of tags and their values
	 *
	 * @return array Tags and values
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getTagsValues() {
		return $this->getDocCommentParser()->getTagsValues();
	}

	/**
	 * Returns the values of the specified tag
	 * @return array Values of the given tag
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getTagValues($tag) {
		return $this->getDocCommentParser()->getTagValues($tag);
	}

	/**
	 * Returns an instance of the doc comment parser and
	 * runs the parse() method.
	 *
	 * @return Tx_ExtBase_Reflection_DocCommentParser
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function getDocCommentParser() {
		if (!is_object($this->docCommentParser)) {
			$this->docCommentParser = new Tx_ExtBase_Reflection_DocCommentParser;
			$this->docCommentParser->parseDocComment($this->getDocComment());
		}
		return $this->docCommentParser;
	}
}

?>