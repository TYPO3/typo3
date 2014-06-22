<?php
namespace TYPO3\CMS\Extbase\Reflection;

/**
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
/**
 * Extended version of the ReflectionClass
 */
class ClassReflection extends \ReflectionClass {

	/**
	 * @var \TYPO3\CMS\Extbase\Reflection\DocCommentParser Holds an instance of the doc comment parser for this class
	 */
	protected $docCommentParser;

	/**
	 * The constructor - initializes the class
	 *
	 * @param string $className Name of the class \TYPO3\CMS\Extbase\Reflection to reflect
	 */
	public function __construct($className) {
		parent::__construct($className);
	}

	/**
	 * Replacement for the original getMethods() method which makes sure
	 * that \TYPO3\CMS\Extbase\Reflection\MethodReflection objects are returned instead of the
	 * orginal ReflectionMethod instances.
	 *
	 * @param integer|NULL $filter A filter mask
	 * @return \TYPO3\CMS\Extbase\Reflection\MethodReflection Method reflection objects of the methods in this class
	 */
	public function getMethods($filter = NULL) {
		$extendedMethods = array();
		$methods = $filter === NULL ? parent::getMethods() : parent::getMethods($filter);
		foreach ($methods as $method) {
			$extendedMethods[] = new \TYPO3\CMS\Extbase\Reflection\MethodReflection($this->getName(), $method->getName());
		}
		return $extendedMethods;
	}

	/**
	 * Replacement for the original getMethod() method which makes sure
	 * that \TYPO3\CMS\Extbase\Reflection\MethodReflection objects are returned instead of the
	 * orginal ReflectionMethod instances.
	 *
	 * @param string $name
	 * @return \TYPO3\CMS\Extbase\Reflection\MethodReflection Method reflection object of the named method
	 */
	public function getMethod($name) {
		$parentMethod = parent::getMethod($name);
		if (!is_object($parentMethod)) {
			return $parentMethod;
		}
		return new \TYPO3\CMS\Extbase\Reflection\MethodReflection($this->getName(), $parentMethod->getName());
	}

	/**
	 * Replacement for the original getConstructor() method which makes sure
	 * that \TYPO3\CMS\Extbase\Reflection\MethodReflection objects are returned instead of the
	 * orginal ReflectionMethod instances.
	 *
	 * @return \TYPO3\CMS\Extbase\Reflection\MethodReflection Method reflection object of the constructor method
	 */
	public function getConstructor() {
		$parentConstructor = parent::getConstructor();
		if (!is_object($parentConstructor)) {
			return $parentConstructor;
		}
		return new \TYPO3\CMS\Extbase\Reflection\MethodReflection($this->getName(), $parentConstructor->getName());
	}

	/**
	 * Replacement for the original getProperties() method which makes sure
	 * that \TYPO3\CMS\Extbase\Reflection\PropertyReflection objects are returned instead of the
	 * orginal ReflectionProperty instances.
	 *
	 * @param integer|NULL $filter A filter mask
	 * @return array of \TYPO3\CMS\Extbase\Reflection\PropertyReflection Property reflection objects of the properties in this class
	 */
	public function getProperties($filter = NULL) {
		$extendedProperties = array();
		$properties = $filter === NULL ? parent::getProperties() : parent::getProperties($filter);
		foreach ($properties as $property) {
			$extendedProperties[] = new \TYPO3\CMS\Extbase\Reflection\PropertyReflection($this->getName(), $property->getName());
		}
		return $extendedProperties;
	}

	/**
	 * Replacement for the original getProperty() method which makes sure
	 * that a \TYPO3\CMS\Extbase\Reflection\PropertyReflection object is returned instead of the
	 * orginal ReflectionProperty instance.
	 *
	 * @param string $name Name of the property
	 * @return \TYPO3\CMS\Extbase\Reflection\PropertyReflection Property reflection object of the specified property in this class
	 */
	public function getProperty($name) {
		return new \TYPO3\CMS\Extbase\Reflection\PropertyReflection($this->getName(), $name);
	}

	/**
	 * Replacement for the original getInterfaces() method which makes sure
	 * that \TYPO3\CMS\Extbase\Reflection\ClassReflection objects are returned instead of the
	 * orginal ReflectionClass instances.
	 *
	 * @return array of \TYPO3\CMS\Extbase\Reflection\ClassReflection Class reflection objects of the properties in this class
	 */
	public function getInterfaces() {
		$extendedInterfaces = array();
		$interfaces = parent::getInterfaces();
		foreach ($interfaces as $interface) {
			$extendedInterfaces[] = new \TYPO3\CMS\Extbase\Reflection\ClassReflection($interface->getName());
		}
		return $extendedInterfaces;
	}

	/**
	 * Replacement for the original getParentClass() method which makes sure
	 * that a \TYPO3\CMS\Extbase\Reflection\ClassReflection object is returned instead of the
	 * orginal ReflectionClass instance.
	 *
	 * @return \TYPO3\CMS\Extbase\Reflection\ClassReflection Reflection of the parent class - if any
	 */
	public function getParentClass() {
		$parentClass = parent::getParentClass();
		return $parentClass === FALSE ? FALSE : new \TYPO3\CMS\Extbase\Reflection\ClassReflection($parentClass->getName());
	}

	/**
	 * Checks if the doc comment of this method is tagged with
	 * the specified tag
	 *
	 * @param string $tag Tag name to check for
	 * @return boolean TRUE if such a tag has been defined, otherwise FALSE
	 */
	public function isTaggedWith($tag) {
		$result = $this->getDocCommentParser()->isTaggedWith($tag);
		return $result;
	}

	/**
	 * Returns an array of tags and their values
	 *
	 * @return array Tags and values
	 */
	public function getTagsValues() {
		return $this->getDocCommentParser()->getTagsValues();
	}

	/**
	 * Returns the values of the specified tag
	 *
	 * @param string $tag
	 * @return array Values of the given tag
	 */
	public function getTagValues($tag) {
		return $this->getDocCommentParser()->getTagValues($tag);
	}

	/**
	 * Returns an instance of the doc comment parser and
	 * runs the parse() method.
	 *
	 * @return \TYPO3\CMS\Extbase\Reflection\DocCommentParser
	 */
	protected function getDocCommentParser() {
		if (!is_object($this->docCommentParser)) {
			$this->docCommentParser = new \TYPO3\CMS\Extbase\Reflection\DocCommentParser();
			$this->docCommentParser->parseDocComment($this->getDocComment());
		}
		return $this->docCommentParser;
	}
}
