<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
 *  All rights reserved
 *
 *  This class is a backport of the corresponding class of FLOW3.
 *  All credits go to the v5 team.
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
 * A proxy that can replace any object and replaces itself in it's parent on
 * first access (call, get, set, isset, unset).
 *
 * @package Extbase
 * @subpackage Persistence
 * @version $Id: LazyObjectStorage.php 2287 2010-05-25 11:09:54Z jocrau $
 */
class Tx_Extbase_Persistence_LazyObjectStorage extends Tx_Extbase_Persistence_ObjectStorage implements Tx_Extbase_Persistence_LoadingStrategyInterface {

	/**
	 * The object this property is contained in.
	 *
	 * @var object
	 */
	protected $parentObject;

	/**
	 * The name of the property represented by this proxy.
	 *
	 * @var string
	 */
	protected $propertyName;

	/**
	 * The raw field value.
	 *
	 * @var mixed
	 */
	protected $fieldValue;

	/**
	 *
	 * @var bool
	 */
	protected $isInitialized = FALSE;

	/**
	 * Returns the state of the initialization
	 *
	 * @return void
	 */
	public function isInitialized() {
		return $this->isInitialized;
	}
	
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
	 * This is a function lazy load implementation. 
	 *
	 * @return void
	 */
	protected function initialize() {
		if (!$this->isInitialized) {
			$this->isInitialized = TRUE;
			$dataMapper = Tx_Extbase_Dispatcher::getPersistenceManager()->getBackend()->getDataMapper();
			$objects = $dataMapper->fetchRelated($this->parentObject, $this->propertyName, $this->fieldValue, FALSE);
			foreach ($objects as $object) {
				parent::attach($object);
			}
			$this->_memorizeCleanState();
			$this->parentObject->_memorizeCleanState($this->propertyName);
		}
	}
		
	// Delegation to the ObjectStorage methods below

	/**
	 * @see Tx_Extbase_Persistence_ObjectStorage::addAll
	 */
	public function addAll($storage) {
		$this->initialize();
		parent::addAll($storage);
	}

	/**
	 * @see Tx_Extbase_Persistence_ObjectStorage::attach
	 */
	public function attach($object, $data = NULL) {
		$this->initialize();
		parent::attach($object, $data);
	}

	/**
	 * @see Tx_Extbase_Persistence_ObjectStorage::contains
	 */
	public function contains($object) {
		$this->initialize();
		return parent::contains($object);
	}

	/**
	 * Counts the elements in the storage array
	 *
	 * @return int The number of elements in the ObjectStorage
	 */
	public function count() {
		$dataMapper = Tx_Extbase_Dispatcher::getPersistenceManager()->getBackend()->getDataMapper();
		$columnMap = $dataMapper->getDataMap(get_class($this->parentObject))->getColumnMap($this->propertyName);
		$numberOfElements = NULL;
		if ($columnMap->getTypeOfRelation() === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_MANY) {
			$numberOfElements = $dataMapper->countRelated($this->parentObject, $this->propertyName, $this->fieldValue);
		} else {
			$this->initialize();
			$numberOfElements = count($this->storage);			
		}
		if (is_null($numberOfElements)) {
			throw new Tx_Extbase_Persistence_Exception('The number of elements could not be determined.', 1252514486);
		}
		return $numberOfElements;
	}

	/**
	 * @see Tx_Extbase_Persistence_ObjectStorage::current
	 */
	public function current() {
		$this->initialize();
		return parent::current();
	}

	/**
	 * @see Tx_Extbase_Persistence_ObjectStorage::detach
	 */
	public function detach($object) {
		$this->initialize();
		parent::detach($object);
	}

	/**
	 * @see Tx_Extbase_Persistence_ObjectStorage::key
	 */
	public function key() {
		$this->initialize();
		return parent::key();
	}

	/**
	 * @see Tx_Extbase_Persistence_ObjectStorage::next
	 */
	public function next() {
		$this->initialize();
		parent::next();
	}

	/**
	 * @see Tx_Extbase_Persistence_ObjectStorage::offsetExists
	 */
	public function offsetExists($object) {
		$this->initialize();
		return parent::offsetExists($object);
	}

	/**
	 * @see Tx_Extbase_Persistence_ObjectStorage::offsetGet
	 */
	public function offsetGet($object) {
		$this->initialize();
		return parent::offsetGet($object);
	}

	/**
	 * @see Tx_Extbase_Persistence_ObjectStorage::offsetSet
	 */
	public function offsetSet($object , $info) {
		$this->initialize();
		parent::offsetSet($object, $info);
	}

	/**
	 * @see Tx_Extbase_Persistence_ObjectStorage::offsetUnset
	 */
	public function offsetUnset($object) {
		$this->initialize();
		parent::offsetUnset($object);
	}

	/**
	 * @see Tx_Extbase_Persistence_ObjectStorage::removeAll
	 */
	public function removeAll($storage) {
		$this->initialize();
		parent::removeAll($storage);
	}

	/**
	 * @see Tx_Extbase_Persistence_ObjectStorage::rewind
	 */
	public function rewind() {
		$this->initialize();
		parent::rewind();
	}

	/**
	 * @see Tx_Extbase_Persistence_ObjectStorage::valid
	 */
	public function valid() {
		$this->initialize();
		return parent::valid();
	}
	
	/**
	 * @see Tx_Extbase_Persistence_ObjectStorage::toArray
	 */
	public function toArray() {
		$this->initialize();
		return parent::toArray();
	}
		
}
?>