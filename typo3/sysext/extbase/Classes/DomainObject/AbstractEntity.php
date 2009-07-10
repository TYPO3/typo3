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
	private $_cleanProperties = NULL;

	/**
	 * Register an object's clean state, e.g. after it has been reconstituted
	 * from the database
	 *
	 * @param string $propertyName The name of the property to be memorized. If omittet all persistable properties are memorized.
	 * @return void
	 * @internal
	 */
	public function _memorizeCleanState($propertyName = NULL) {
		// TODO Remove dependency to $dataMapper
		if ($propertyName !== NULL) {
		} else {
			$dataMapper = t3lib_div::makeInstance('Tx_Extbase_Persistence_Mapper_DataMapper'); // singleton
			$this->_cleanProperties = array();
			$properties = get_object_vars($this);
			foreach ($properties as $propertyName => $propertyValue) {
				if ($dataMapper->isPersistableProperty(get_class($this), $propertyName)) {
					$this->_memorizePropertyCleanState($propertyName);
				}
			}
		}
	}

	/**
	 * Register an properties's clean state, e.g. after it has been reconstituted
	 * from the database
	 *
	 * @param string $propertyName The name of the property to be memorized. If omittet all persistable properties are memorized.
	 * @return void
	 * @internal
	 */
	public function _memorizePropertyCleanState($propertyName) {
		$propertyValue = $this->$propertyName;
		if (!is_array($this->_cleanProperties)) {
			$this->_cleanProperties = array();
		}
		if (is_object($propertyValue)) {
			$this->_cleanProperties[$propertyName] = clone($propertyValue);
		} else {
			$this->_cleanProperties[$propertyName] = $propertyValue;
		}
	}

	/**
	 * Returns a hash map of dirty properties and $values
	 *
	 * @return array
	 * @internal
	 */
	public function _getDirtyProperties() {
		if (!is_array($this->_cleanProperties)) throw new Tx_Extbase_Persistence_Exception_CleanStateNotMemorized('The clean state of the object "' . get_class($this) . '" has not been memorized before asking _isDirty().', 1233309106);
		if ($this->uid !== NULL && $this->uid != $this->_cleanProperties['uid']) throw new Tx_Extbase_Persistence_Exception_TooDirty('The uid "' . $this->uid . '" has been modified, that is simply too much.', 1222871239);
		$dirtyProperties = array();
		foreach ($this->_cleanProperties as $propertyName => $propertyValue) {
			if ($this->$propertyName !== $propertyValue) {
				$dirtyProperties[$propertyName] = $this->$propertyName;
			}
		}
		return $dirtyProperties;
	}

	/**
	 * Returns TRUE if the properties were modified after reconstitution
	 *
	 * @return boolean
	 * @internal
	 */
	public function _isDirty($propertyName = NULL) {
		if (!is_array($this->_cleanProperties)) throw new Tx_Extbase_Persistence_Exception_CleanStateNotMemorized('The clean state of the object "' . get_class($this) . '" has not been memorized before asking _isDirty().', 1233309106);
		if ($this->uid !== NULL && $this->uid != $this->_cleanProperties['uid']) throw new Tx_Extbase_Persistence_Exception_TooDirty('The uid "' . $this->uid . '" has been modified, that is simply too much.', 1222871239);
		$result = FALSE;
		if ($propertyName !== NULL) {
			$result = $this->_cleanProperties[$propertyName] !== $this->$propertyName;
		} else {
			foreach ($this->_cleanProperties as $propertyName => $propertyValue) {
				if ($this->$propertyName !== $propertyValue) {
					$result = TRUE;
					break;
				}
			}
		}
		return $result;
	}

}
?>