<?php
namespace TYPO3\CMS\Extbase\Persistence\Generic;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * A proxy that can replace any object and replaces itself in it's parent on
 * first access (call, get, set, isset, unset).
 */
class LazyLoadingProxy implements \Iterator, \TYPO3\CMS\Extbase\Persistence\Generic\LoadingStrategyInterface {

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper
	 */
	protected $dataMapper;

	/**
	 * The object this property is contained in.
	 *
	 * @var \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface
	 */
	private $parentObject;

	/**
	 * The name of the property represented by this proxy.
	 *
	 * @var string
	 */
	private $propertyName;

	/**
	 * The raw field value.
	 *
	 * @var mixed
	 */
	private $fieldValue;

	/**
	 * Constructs this proxy instance.
	 *
	 * @param object $parentObject The object instance this proxy is part of
	 * @param string $propertyName The name of the proxied property in it's parent
	 * @param mixed $fieldValue The raw field value.
	 */
	public function __construct($parentObject, $propertyName, $fieldValue) {
		$this->parentObject = $parentObject;
		$this->propertyName = $propertyName;
		$this->fieldValue = $fieldValue;
	}

	/**
	 * Injects the DataMapper to map nodes to objects
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper $dataMapper
	 * @return void
	 */
	public function injectDataMapper(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper $dataMapper) {
		$this->dataMapper = $dataMapper;
	}

	/**
	 * Populate this proxy by asking the $population closure.
	 *
	 * @return object The instance (hopefully) returned
	 */
	public function _loadRealInstance() {
		// this check safeguards against a proxy being activated multiple times
		// usually that does not happen, but if the proxy is held from outside
		// it's parent... the result would be weird.
		if ($this->parentObject->_getProperty($this->propertyName) instanceof \TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy) {
			$objects = $this->dataMapper->fetchRelated($this->parentObject, $this->propertyName, $this->fieldValue, FALSE, FALSE);
			$propertyValue = $this->dataMapper->mapResultToPropertyValue($this->parentObject, $this->propertyName, $objects);
			$this->parentObject->_setProperty($this->propertyName, $propertyValue);
			$this->parentObject->_memorizeCleanState($this->propertyName);
			return $propertyValue;
		} else {
			return $this->parentObject->_getProperty($this->propertyName);
		}
	}

	/**
	 * Magic method call implementation.
	 *
	 * @param string $methodName The name of the property to get
	 * @param array $arguments The arguments given to the call
	 * @return mixed
	 */
	public function __call($methodName, $arguments) {
		$realInstance = $this->_loadRealInstance();
		if (!is_object($realInstance)) {
			return NULL;
		}
		return call_user_func_array(array($realInstance, $methodName), $arguments);
	}

	/**
	 * Magic get call implementation.
	 *
	 * @param string $propertyName The name of the property to get
	 * @return mixed
	 */
	public function __get($propertyName) {
		$realInstance = $this->_loadRealInstance();
		return $realInstance->{$propertyName};
	}

	/**
	 * Magic set call implementation.
	 *
	 * @param string $propertyName The name of the property to set
	 * @param mixed $value The value for the property to set
	 * @return void
	 */
	public function __set($propertyName, $value) {
		$realInstance = $this->_loadRealInstance();
		$realInstance->{$propertyName} = $value;
	}

	/**
	 * Magic isset call implementation.
	 *
	 * @param string $propertyName The name of the property to check
	 * @return boolean
	 */
	public function __isset($propertyName) {
		$realInstance = $this->_loadRealInstance();
		return isset($realInstance->{$propertyName});
	}

	/**
	 * Magic unset call implementation.
	 *
	 * @param string $propertyName The name of the property to unset
	 * @return void
	 */
	public function __unset($propertyName) {
		$realInstance = $this->_loadRealInstance();
		unset($realInstance->{$propertyName});
	}

	/**
	 * Magic toString call implementation.
	 *
	 * @return string
	 */
	public function __toString() {
		$realInstance = $this->_loadRealInstance();
		return $realInstance->__toString();
	}

	/**
	 * Returns the current value of the storage array
	 *
	 * @return mixed
	 */
	public function current() {
		$realInstance = $this->_loadRealInstance();
		return current($realInstance);
	}

	/**
	 * Returns the current key storage array
	 *
	 * @return integer
	 */
	public function key() {
		$realInstance = $this->_loadRealInstance();
		return key($realInstance);
	}

	/**
	 * Returns the next position of the storage array
	 *
	 * @return void
	 */
	public function next() {
		$realInstance = $this->_loadRealInstance();
		next($realInstance);
	}

	/**
	 * Resets the array pointer of the storage
	 *
	 * @return void
	 */
	public function rewind() {
		$realInstance = $this->_loadRealInstance();
		reset($realInstance);
	}

	/**
	 * Checks if the array pointer of the storage points to a valid position
	 *
	 * @return boolean
	 */
	public function valid() {
		return $this->current() !== FALSE;
	}
}

?>