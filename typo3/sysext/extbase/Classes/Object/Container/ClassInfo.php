<?php
namespace TYPO3\CMS\Extbase\Object\Container;

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
 * Value object containing the relevant informations for a class,
 * this object is build by the classInfoFactory - or could also be restored from a cache
 *
 * @author Daniel Pötzinger
 */
class ClassInfo {

	/**
	 * The classname of the class where the infos belong to
	 *
	 * @var string
	 */
	private $className;

	/**
	 * The constructor Dependencies for the class in the format:
	 * array(
	 * 0 => array( <-- parameters for argument 1
	 * 'name' => <arg name>, <-- name of argument
	 * 'dependency' => <classname>, <-- if the argument is a class, the type of the argument
	 * 'defaultvalue' => <mixed>) <-- if the argument is optional, its default value
	 * ),
	 * 1 => ...
	 * )
	 *
	 * @var array
	 */
	private $constructorArguments;

	/**
	 * All setter injections in the format
	 * array (<nameOfMethod> => <classNameToInject> )
	 *
	 * @var array
	 */
	private $injectMethods;

	/**
	 * All setter injections in the format
	 * array (<nameOfProperty> => <classNameToInject> )
	 *
	 * @var array
	 */
	private $injectProperties;

	/**
	 * Indicates if the class is a singleton or not.
	 *
	 * @var boolean
	 */
	private $isSingleton = FALSE;

	/**
	 * Indicates if the class has the method initializeObject
	 *
	 * @var boolean
	 */
	private $isInitializeable = FALSE;

	/**
	 * @param string $className
	 * @param array $constructorArguments
	 * @param array $injectMethods
	 * @param boolean $isSingleton
	 * @param boolean $isInitializeable
	 * @param array $injectProperties
	 */
	public function __construct($className, array $constructorArguments, array $injectMethods, $isSingleton = FALSE, $isInitializeable = FALSE, array $injectProperties = array()) {
		$this->className = $className;
		$this->constructorArguments = $constructorArguments;
		$this->injectMethods = $injectMethods;
		$this->injectProperties = $injectProperties;
		$this->isSingleton = $isSingleton;
		$this->isInitializeable = $isInitializeable;
	}

	/**
	 * Gets the class name passed to constructor
	 *
	 * @return string
	 */
	public function getClassName() {
		return $this->className;
	}

	/**
	 * Get arguments passed to constructor
	 *
	 * @return array
	 */
	public function getConstructorArguments() {
		return $this->constructorArguments;
	}

	/**
	 * Returns an array with the inject methods.
	 *
	 * @return array
	 */
	public function getInjectMethods() {
		return $this->injectMethods;
	}

	/**
	 * Returns an array with the inject properties
	 *
	 * @return array
	 */
	public function getInjectProperties() {
		return $this->injectProperties;
	}

	/**
	 * Asserts if the class is a singleton or not.
	 *
	 * @return boolean
	 */
	public function getIsSingleton() {
		return $this->isSingleton;
	}

	/**
	 * Asserts if the class is initializeable with initializeObject.
	 *
	 * @return boolean
	 */
	public function getIsInitializeable() {
		return $this->isInitializeable;
	}

	/**
	 * Asserts if the class has Dependency Injection methods
	 *
	 * @return boolean
	 */
	public function hasInjectMethods() {
		return count($this->injectMethods) > 0;
	}

	/**
	 * @return boolean
	 */
	public function hasInjectProperties() {
		return count($this->injectProperties) > 0;
	}
}
