<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
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
 * A generic Domain Object
 *
 * @package Extbase
 * @subpackage extbase
 * @version $ID:$
 */
abstract class Tx_Extbase_DomainObject_AbstractDomainObject implements Tx_Extbase_DomainObject_DomainObjectInterface {

	/**
	 * @var string The uid
	 */
	protected $uid;

	/**
	 * The generic constructor. If you want to implement your own __constructor() method in your Domain Object you have to call
	 * $this->initializeObject() in the first line of your constructor.
	 *
	 * @var array
	 */
	public function __construct() {
		$this->initializeObject();
	}

	/**
	 * This is the magic __wakeup() method. It's invoked by the unserialize statement in the reconstitution process
	 * of the object. If you want to implement your own __wakeup() method in your Domain Object you have to call
	 * parent::__wakeup() first!
	 *
	 * @return void
	 * @internal
	 */
	public function __wakeup() {
		foreach ($GLOBALS['Extbase']['reconstituteObject']['properties'] as $propertyName => $propertyValue) {
			$this->_reconstituteProperty($propertyName, $propertyValue);
		}
		$this->initializeObject();
	}

	/**
	 * A template method to initialize an object. This can be used to manipulate the object after
	 * reconstitution and before the clean state of it's properties is stored.
	 *
	 * @return void
	 */
	protected function initializeObject() {
	}

	/**
	 * Getter for uid
	 *
	 * @return string
	 */
	final public function getUid() {
		return $this->uid;
	}

	/**
	 * Reconstitutes a property. This method should only be called at reconstitution time by the framework!
	 *
	 * @param string $propertyName
	 * @param string $value
	 * @return void
	 * @internal
	 */
	public function _reconstituteProperty($propertyName, $value) {
		if (property_exists($this, $propertyName)) {
			$this->$propertyName = $value;
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Returns a hash map of property names and property values. Only for internal use.
	 *
	 * @return array The properties
	 * @internal
	 */
	public function _getProperties() {
		$properties = get_object_vars($this);
		// unset($properties['_cleanProperties']); // TODO Check this again
		return $properties;
	}

	/**
	 * Returns the property value of the given property name. Only for internal use.
	 *
	 * @return array The propertyName
	 * @internal
	 */
	public function _getPropertyValue($propertyName) {
		return $this->$propertyName;
	}

	/**
	 * Returns TRUE if the object is new (the uid was not set, yet). Only for internal use
	 *
	 * @return boolean
	 * @internal
	 */
	public function _isNew() {
		return $this->uid === NULL;
	}

	/**
	 * Register an object's clean state, e.g. after it has been reconstituted
	 * from the database
	 *
	 * @return void
	 * @internal
	 */
	public function _memorizeCleanState() {
	}

	/**
	 * Returns a hash map of dirty properties and $values. This is always the empty array for ValueObjects, because ValueObjects never change.
	 *
	 * @return array
	 * @internal
	 */
	public function _getDirtyProperties() {
		return array();
	}

	/**
	 * Returns TRUE if the properties were modified after reconstitution. However, value objects can be never updated.
	 *
	 * @return boolean
	 * @internal
	 */
	public function _isDirty() {
		return FALSE;
	}
}
?>