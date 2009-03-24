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

require_once(PATH_t3lib . 'interfaces/interface.t3lib_singleton.php');

/**
 * The persistence session - acts as a Unit of Work for EXCMVC's persistence framework.
 *
 * @package TYPO3
 * @subpackage extmvc
 * @version $ID:$
 */
class TX_EXTMVC_Persistence_Session implements t3lib_singleton {

	/**
	 * Objects added to the repository but not yet persisted in the persistence backend.
	 * The relevant objects are registered by the TX_EXTMVC_Persistence_Repository.
	 *
	 * @var TX_EXTMVC_Persistence_ObjectStorage
	 */
	protected $addedObjects;

	/**
	 * Objects removed but not yet persisted in the persistence backend.
	 * The relevant objects are registered by the TX_EXTMVC_Persistence_Repository.
	 *
	 * @var TX_EXTMVC_Persistence_ObjectStorage
	 */
	protected $removedObjects;

	/**
	 * Objects which were reconstituted. The relevant objects are registered by 
	 * the TX_EXTMVC_Persistence_Mapper_ObjectRelationalMapper.
	 *
	 * @var TX_EXTMVC_Persistence_ObjectStorage
	 */
	protected $reconstitutedObjects;

	/**
	 * This is an array of aggregate root class names. Aggegate root objects are an entry point to start committing
	 * changes. Aggregate root class names are registered by the TX_EXTMVC_Persistence_Repository.
	 * 
	 * @var array
	 */
	protected $aggregateRootClassNames = array();

	/**
	 * Constructs a new Session
	 *
	 */
	public function __construct() {
		$this->addedObjects = new TX_EXTMVC_Persistence_ObjectStorage();
		$this->removedObjects = new TX_EXTMVC_Persistence_ObjectStorage();
		$this->reconstitutedObjects = new TX_EXTMVC_Persistence_ObjectStorage();
	}

	/**
	 * Registers an added object.
	 *
	 * @param TX_EXTMVC_DomainObject_AbstractDomainObject $object
	 * @return void
	 */
	public function registerAddedObject(TX_EXTMVC_DomainObject_AbstractDomainObject $object) {
		if ($this->reconstitutedObjects->contains($object)) throw new InvalidArgumentException('The object was registered as reconstituted and can therefore not be registered as added.');
		$this->removedObjects->detach($object);
		$this->addedObjects->attach($object);
	}

	/**
	 * Unregisters an added object
	 *
	 * @param TX_EXTMVC_DomainObject_AbstractDomainObject $object
	 * @return void
	 */
	public function unregisterAddedObject(TX_EXTMVC_DomainObject_AbstractDomainObject $object) {
		$this->addedObjects->detach($object);
	}

	/**
	 * Returns all objects which have been registered as added objects
	 *
	 * @param string $objectClassName The class name of objects to be returned
	 * @return array All added objects
	 */
	public function getAddedObjects($objectClassName = NULL) {
		$addedObjects = array();
		foreach ($this->addedObjects as $object) {
			if ($objectClassName != NULL && !($object instanceof $objectClassName)) continue;
			$addedObjects[] = $object;
		}
		return $addedObjects;
	}

	/**
	 * Returns TRUE if the given object is registered as added
	 *
	 * @param TX_EXTMVC_DomainObject_AbstractDomainObject $object
	 * @return bool TRUE if the given object is registered as added; otherwise FALSE
	 */
	public function isAddedObject(TX_EXTMVC_DomainObject_AbstractDomainObject $object) {
		return $this->addedObjects->contains($object);
	}

	/**
	 * Registers a removed object
	 *
	 * @param TX_EXTMVC_DomainObject_AbstractDomainObject $object
	 * @return void
	 */
	public function registerRemovedObject(TX_EXTMVC_DomainObject_AbstractDomainObject $object) {
		if ($this->addedObjects->contains($object)) {
			$this->addedObjects->detach($object);
		} else {
			$this->removedObjects->attach($object);
		}
	}

	/**
	 * Unregisters a removed object
	 *
	 * @param TX_EXTMVC_DomainObject_AbstractDomainObject $object
	 * @return void
	 */
	public function unregisterRemovedObject(TX_EXTMVC_DomainObject_AbstractDomainObject $object) {
		$this->removedObjects->detach($object);
	}

	/**
	 * Returns all objects which have been registered as removed objects
	 *
	 * @param string $objectClassName The class name of objects to be returned
	 * @return array All removed objects
	 */
	public function getRemovedObjects($objectClassName = NULL) {
		$removedObjects = array();
		foreach ($this->removedObjects as $object) {
			if ($objectClassName != NULL && !($object instanceof $objectClassName)) continue;
			$removedObjects[] = $object;
		}
		return $removedObjects;
	}

	/**
	 * Returns TRUE if the given object is registered as removed
	 *
	 * @param TX_EXTMVC_DomainObject_AbstractDomainObject $object
	 * @return bool TRUE if the given object is registered as removed; otherwise FALSE
	 */
	public function isRemovedObject(TX_EXTMVC_DomainObject_AbstractDomainObject $object) {
		return $this->removedObjects->contains($object);
	}

	/**
	 * Registers a reconstituted object
	 *
	 * @param object $object
	 * @return TX_EXTMVC_DomainObject_AbstractDomainObject
	 */
	public function registerReconstitutedObject(TX_EXTMVC_DomainObject_AbstractDomainObject $object) {
		if ($this->addedObjects->contains($object)) throw new InvalidArgumentException('The object was registered as added and can therefore not be registered as reconstituted.');
		$this->reconstitutedObjects->attach($object);
		$object->_memorizeCleanState();
	}

	/**
	 * Unregisters a reconstituted object
	 *
	 * @param TX_EXTMVC_DomainObject_AbstractDomainObject $object
	 * @return void
	 */
	public function unregisterReconstitutedObject(TX_EXTMVC_DomainObject_AbstractDomainObject $object) {
		$this->reconstitutedObjects->detach($object);
	}

	/**
	 * Returns all objects which have been registered as reconstituted objects
	 *
	 * @param string $objectClassName The class name of objects to be returned
	 * @return array All reconstituted objects
	 */
	public function getReconstitutedObjects($objectClassName = NULL) {
		$reconstitutedObjects = array();
		foreach ($this->reconstitutedObjects as $object) {
			if ($objectClassName != NULL && !($object instanceof $objectClassName)) continue;
			$reconstitutedObjects[] = $object;
		}
		return $reconstitutedObjects;
	}

	/**
	 * Returns TRUE if the given object is registered as reconstituted
	 *
	 * @param TX_EXTMVC_DomainObject_AbstractDomainObject $object
	 * @return bool TRUE if the given object is registered as reconstituted; otherwise FALSE
	 */
	public function isReconstitutedObject(TX_EXTMVC_DomainObject_AbstractDomainObject $object) {
		return $this->reconstitutedObjects->contains($object);
	}

	/**
	 * Returns all objects marked as dirty (changed after reconstitution)
	 *
	 * @param string $objectClassName The class name of objects to be returned
	 * @return array An array of dirty objects
	 */
	public function getDirtyObjects($objectClassName = NULL) {
		$dirtyObjects = array();
		foreach ($this->reconstitutedObjects as $object) {
			if ($objectClassName != NULL && !($object instanceof $objectClassName)) continue;
			if ($object->_isDirty()) {
				$dirtyObjects[] = $object;
			}
		}
		return $dirtyObjects;
	}

	/**
	 * Returns TRUE if the given object is dirty
	 *
	 * @param TX_EXTMVC_DomainObject_AbstractDomainObject $object
	 * @return bool TRUE if the given object is dirty; otherwise FALSE
	 */
	public function isDirtyObject(TX_EXTMVC_DomainObject_AbstractDomainObject $object) {
		return $object->_isDirty();
	}

	/**
	 * Unregisters an object from all states
	 *
	 * @param TX_EXTMVC_DomainObject_AbstractDomainObject $object
	 * @return void
	 */
	public function unregisterObject(TX_EXTMVC_DomainObject_AbstractDomainObject $object) {
		$this->unregisterAddedObject($object);
		$this->unregisterRemovedObject($object);
		$this->unregisterReconstitutedObject($object);
	}

	/**
	 * Clears all ObjectStorages
	 *
	 * @return void
	 */
	public function clear() {
		$this->addedObjects = new TX_EXTMVC_Persistence_ObjectStorage();
		$this->removedObjects = new TX_EXTMVC_Persistence_ObjectStorage();
		$this->reconstitutedObjects = new TX_EXTMVC_Persistence_ObjectStorage();
		$this->aggregateRootClassNames = array();
	}

	/**
	 * Registers an aggregate root
	 *
	 * @param string $className The class to be registered
	 * @return void
	 */
	public function registerAggregateRootClassName($className) {
		$this->aggregateRootClassNames[] = $className;
	}

	/**
	 * Returns all aggregate root classes
	 *
	 * @return array An array holding the registered aggregate root classes
	 */
	public function getAggregateRootClassNames() {
		return $this->aggregateRootClassNames;
	}

	/**
	 * Commits the current persistence session.
	 *
	 * @return void
	 */
	public function commit() {
		$dataMapper = t3lib_div::makeInstance('TX_EXTMVC_Persistence_Mapper_ObjectRelationalMapper'); // singleton
		$dataMapper->persistAll();
	}

}
?>