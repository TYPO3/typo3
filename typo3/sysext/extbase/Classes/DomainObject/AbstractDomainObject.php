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
 * A generic Domain Object.
 *
 * All Model domain objects need to inherit from either AbstractEntity or AbstractValueObject, as this provides important framework information.
 *
 * @package Extbase
 * @subpackage DomainObject
 * @version $ID:$
 */
abstract class Tx_Extbase_DomainObject_AbstractDomainObject implements Tx_Extbase_DomainObject_DomainObjectInterface, Tx_Extbase_Persistence_ObjectMonitoringInterface {

	/**
	 * @var int The uid
	 */
	protected $uid;

	/**
	 * @var int The uid of the localized record. In TYPO3 v4.x the property "uid" holds the uid of the record in default language (the translationOrigin).
	 */
	protected $_localizedUid;

	/**
	 * @var int The uid of the language of the object. In TYPO3 v4.x this is the uid of the language record in the table sys_language.
	 */
	protected $_languageUid;

	/**
	 * TRUE if the object is a clone
	 * @var boolean
	 */
	private $_isClone = FALSE;

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
	 */
	public function __wakeup() {
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
	 * Getter for uid.
	 *
	 * @return int the uid or NULL if none set yet.
	 */
	final public function getUid() {
		if ($this->uid !== NULL) {
			return (int)$this->uid;
		} else {
			return NULL;
		}
	}
	
	/**
	 * Reconstitutes a property. Only for internal use.
	 *
	 * @param string $propertyName
	 * @param string $value
	 * @return void
	 */
	public function _setProperty($propertyName, $propertyValue) {
		if ($this->_hasProperty($propertyName)) {
			$this->$propertyName = $propertyValue;
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Returns the property value of the given property name. Only for internal use.
	 *
	 * @return mixed The propertyValue
	 */
	public function _getProperty($propertyName) {
		return $this->$propertyName;
	}

	/**
	 * Returns a hash map of property names and property values. Only for internal use.
	 *
	 * @return array The properties
	 */
	public function _getProperties() {
		$properties = get_object_vars($this);
		foreach ($properties as $propertyName => $propertyValue) {
			if ($propertyName{0} === '_') {
				unset($properties[$propertyName]);
			}
		}
		return $properties;
	}
	
	/**
	 * Returns the property value of the given property name. Only for internal use.
	 *
	 * @return boolean TRUE bool true if the property exists, FALSE if it doesn't exist or
	 * NULL in case of an error.
	 */
	public function _hasProperty($propertyName) {
		return property_exists($this, $propertyName);
	}

	/**
	 * Returns TRUE if the object is new (the uid was not set, yet). Only for internal use
	 *
	 * @return boolean
	 */
	public function _isNew() {
		return $this->uid === NULL;
	}

	/**
	 * Register an object's clean state, e.g. after it has been reconstituted
	 * from the database
	 *
	 * @return void
	 */
	public function _memorizeCleanState() {
	}
	
	/**
	 * Returns TRUE if the properties were modified after reconstitution. However, value objects can be never updated.
	 *
	 * @return boolean
	 */
	public function _isDirty($propertyName = NULL) {
		return FALSE;
	}

	/**
	 * Returns TRUE if the object has been clonesd, cloned, FALSE otherwise.
	 *
	 * @return boolean TRUE if the object has been cloned
	 */
	public function _isClone() {
		return $this->_isClone;
	}

	/**
	 * Setter whether this Domain Object is a clone of another one.
	 * NEVER SET THIS PROPERTY DIRECTLY. We currently need it to make the
	 * _isDirty check inside AbstractEntity work, but it is just a work-
	 * around right now.
	 *
	 * @param boolean $clone
	 */
	public function _setClone($clone) {
		$this->_isClone = (boolean)$clone;
	}

	/**
	 * Clone method. Sets the _isClone property.
	 *
	 * @return void
	 */
	public function __clone() {
		$this->_isClone = TRUE;
	}
	
}
?>