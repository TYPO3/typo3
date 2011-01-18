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
 * An abstract Entity. An Entity is an object fundamentally defined not by its attributes,
 * but by a thread of continuity and identity (e.g. a person).
 *
 * @package Extbase
 * @subpackage DomainObject
 * @version $ID:$
 */
abstract class Tx_Extbase_DomainObject_AbstractEntity extends Tx_Extbase_DomainObject_AbstractDomainObject {

	/**
	 * @var An array holding the clean property values. Set right after reconstitution of the object
	 */
	private $_cleanProperties;

	/**
	 * Register an object's clean state, e.g. after it has been reconstituted
	 * from the database.
	 *
	 * @param string $propertyName The name of the property to be memorized. If omitted all persistable properties are memorized.
	 * @return void
	 */
	public function _memorizeCleanState($propertyName = NULL) {
		if ($propertyName !== NULL) {
			$this->_memorizePropertyCleanState($propertyName);
		} else {
			$this->_cleanProperties = array();
			$properties = get_object_vars($this);
			foreach ($properties as $propertyName => $propertyValue) {
				if ($propertyName[0] === '_') continue; // Do not memorize "internal" properties
				$this->_memorizePropertyCleanState($propertyName);
			}
		}
	}

	/**
	 * Register an properties's clean state, e.g. after it has been reconstituted
	 * from the database.
	 *
	 * @param string $propertyName The name of the property to be memorized. If omittet all persistable properties are memorized.
	 * @return void
	 */
	public function _memorizePropertyCleanState($propertyName) {
		$propertyValue = $this->$propertyName;
		if (!is_array($this->_cleanProperties)) {
			$this->_cleanProperties = array();
		}
		if (is_object($propertyValue)) {
			$this->_cleanProperties[$propertyName] = clone($propertyValue);

			// We need to make sure the clone and the original object
			// are identical when compared with == (see _isDirty()).
			// After the cloning, the Domain Object will have the property
			// "isClone" set to TRUE, so we manually have to set it to FALSE
			// again. Possible fix: Somehow get rid of the "isClone" property,
			// which is currently needed in Fluid.
			if ($propertyValue instanceof Tx_Extbase_DomainObject_AbstractDomainObject) {
				$this->_cleanProperties[$propertyName]->_setClone(FALSE);
			}
		} else {
			$this->_cleanProperties[$propertyName] = $propertyValue;
		}
	}

	/**
	 * Returns a hash map of clean properties and $values.
	 *
	 * @return array
	 */
	public function _getCleanProperties() {
		return $this->_cleanProperties;
	}

	/**
	 * Returns the clean value of the given property. The returned value will be NULL if the clean state was not memorized before, or
	 * if the clean value is NULL.
	 *
	 * @param string $propertyName The name of the property to be memorized. If omittet all persistable properties are memorized.
	 * @return mixed The clean property value or NULL
	 */
	public function _getCleanProperty($propertyName) {
		if (is_array($this->_cleanProperties)) {
			return isset($this->_cleanProperties[$propertyName]) ? $this->_cleanProperties[$propertyName] : NULL;
		} else {
			return NULL;
		}
	}
	
	/**
	 * Returns TRUE if the properties were modified after reconstitution
	 *
	 * @param string $propertyName An optional name of a property to be checked if its value is dirty
	 * @return boolean
	 */
	public function _isDirty($propertyName = NULL) {
		if ($this->uid !== NULL && is_array($this->_cleanProperties) && $this->uid != $this->_getCleanProperty('uid')) throw new Tx_Extbase_Persistence_Exception_TooDirty('The uid "' . $this->uid . '" has been modified, that is simply too much.', 1222871239);
		if ($propertyName === NULL) {
			foreach ($this->_getCleanProperties() as $propertyName => $cleanPropertyValue) {
				if ($this->_propertyValueHasChanged($cleanPropertyValue, $this->$propertyName) === TRUE) return TRUE;
			}
		} else {
			if ($this->_propertyValueHasChanged($this->_getCleanProperty($propertyName), $this->$propertyName) === TRUE) return TRUE;
		}
		return FALSE;
	}

	/**
	 * Checks if the current value of a property differs from the previous value.
	 *
	 * @param mixed $previousValue
	 * @param mixed $currentValue
	 * @return boolan
	 */
	protected function _propertyValueHasChanged($previousValue, $currentValue) {
		$result = FALSE;
		if (is_object($currentValue)) {
			if ($currentValue instanceof Tx_Extbase_DomainObject_AbstractDomainObject) {
				if ($previousValue instanceof Tx_Extbase_DomainObject_AbstractDomainObject) {
					$result = $this->_getHash($previousValue) !== $this->_getHash($currentValue);
				} else {
					$result = TRUE;
				}
			} elseif ($currentValue instanceof Tx_Extbase_Persistence_ObjectMonitoringInterface) {
				$result = $currentValue->_isDirty();
			} else {
				$result = $previousValue != $currentValue;
			}
		} else {
			$result = ($previousValue !== $currentValue);
		}
		return $result;
	}

	/**
	 * Generates the value hash for the object
	 *
	 * @var Tx_Extbase_DomainObject_AbstractDomainObject $domainObject The domain object to generate the hash code from
	 * @return string The hash code
	 */
	protected function _getHash(Tx_Extbase_DomainObject_AbstractDomainObject $domainObject) {
		$hashSource = get_class($domainObject);
		$properties = $domainObject->_getProperties();
		if ($domainObject instanceof Tx_Extbase_DomainObject_AbstractValueObject) {
			unset($properties['uid']);
		}
		foreach ($properties as $propertyName => $propertyValue) {
			if (is_array($propertyValue)) {
				$hashSource .= serialize($propertyValue);
			} elseif (!is_object($propertyValue)) {
				$hashSource .= $propertyValue;
			} elseif ($propertyValue instanceof Tx_Extbase_DomainObject_AbstractEntity) {
				$hashSource .= get_class($propertyValue);
				$hashSource .= $propertyValue->getUid();
			} elseif ($propertyValue instanceof Tx_Extbase_DomainObject_AbstractValueObject) {
				$hashSource .= $this->_getHash($propertyValue);
			}
		}
		return sha1($hashSource);
	}

}
?>