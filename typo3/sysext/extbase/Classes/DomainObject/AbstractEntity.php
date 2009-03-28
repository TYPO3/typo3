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
 * @package TYPO3
 * @subpackage extbase
 * @version $ID:$
 */
abstract class Tx_ExtBase_DomainObject_AbstractEntity extends Tx_ExtBase_DomainObject_AbstractDomainObject {
	/**
	 * @var An array holding the clean property values. Set right after reconstitution of the object
	 */
	private $_cleanProperties = NULL;

	/**
	 * Register an object's clean state, e.g. after it has been reconstituted
	 * from the database
	 *
	 * @return void
	 * @internal
	 */
	public function _memorizeCleanState() {
		$this->initializeCleanProperties();
		$cleanProperties = array();
		foreach ($this->_cleanProperties as $propertyName => $propertyValue) {
			$cleanProperties[$propertyName] = $this->$propertyName;
		}
		$this->_cleanProperties = $cleanProperties;
	}

	/**
	 * Saves a copy of values of the persitable properties inside the object itself. This method is normally
	 * called right after it's reconstitution from a storage.
	 *
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	private	function initializeCleanProperties() {
		$properties = get_object_vars($this);
		$dataMapper = t3lib_div::makeInstance('Tx_ExtBase_Persistence_Mapper_ObjectRelationalMapper'); // singleton
		foreach ($properties as $propertyName => $propertyValue) {
			if ($dataMapper->isPersistableProperty(get_class($this), $propertyName)) {
				$this->_cleanProperties[$propertyName] = NULL;
			}
		}
		$this->_cleanProperties['uid'] = NULL;
	}

	/**
	 * Returns a hash map of dirty properties and $values
	 *
	 * @return array
	 * @internal
	 */
	public function _getDirtyProperties() {
		if (!is_array($this->_cleanProperties)) throw new Tx_ExtBase_Persistence_Exception_CleanStateNotMemorized('The clean state of the object "' . get_class($this) . '" has not been memorized before asking _isDirty().', 1233309106);
		if ($this->uid !== NULL && $this->uid != $this->_cleanProperties['uid']) throw new Tx_ExtBase_Persistence_Exception_TooDirty('The uid "' . $this->uid . '" has been modified, that is simply too much.', 1222871239);
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
	public function _isDirty() {
		if (!is_array($this->_cleanProperties)) throw new Tx_ExtBase_Persistence_Exception_CleanStateNotMemorized('The clean state of the object "' . get_class($this) . '" has not been memorized before asking _isDirty().', 1233309106);
		if ($this->uid !== NULL && $this->uid != $this->_cleanProperties['uid']) throw new Tx_ExtBase_Persistence_Exception_TooDirty('The uid "' . $this->uid . '" has been modified, that is simply too much.', 1222871239);
		foreach ($this->_cleanProperties as $propertyName => $propertyValue) {
			if ($this->$propertyName !== $propertyValue) return TRUE;
		}
		return FALSE;
	}
}
?>