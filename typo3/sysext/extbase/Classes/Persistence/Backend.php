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
 * A persistence backend. This backend maps objects to the relational model of the storage backend.
 * It persists all added, removed and changed objects.
 *
 * @package Extbase
 * @subpackage Persistence
 * @version $Id: Backend.php 2183 2009-04-24 14:28:37Z k-fish $
 */
class Tx_Extbase_Persistence_Backend implements Tx_Extbase_Persistence_BackendInterface, t3lib_Singleton {

	/**
	 * @var Tx_Extbase_Persistence_Session
	 */
	protected $session;

	/**
	 * @var Tx_Extbase_Persistence_ObjectStorage
	 */
	protected $aggregateRootObjects;

	/**
	 * @var Tx_Extbase_Persistence_IdentityMap
	 **/
	protected $identityMap;

	/**
	 * @var Tx_Extbase_Persistence_QOM_QueryObjectModelFactoryInterface
	 */
	protected $QOMFactory;

	/**
	 * @var Tx_Extbase_Persistence_ValueFactoryInterface
	 */
	protected $valueFactory;

	/**
	 * @var Tx_Extbase_Persistence_Storage_BackendInterface
	 */
	protected $storageBackend;

	/**
	 * @var Tx_Extbase_Persistence_DataMapper
	 */
	protected $dataMapper;

	/**
	 * The TYPO3 reference index object
	 *
	 * @var t3lib_refindex
	 **/
	protected $referenceIndex;

	/**
	 * Constructs the backend
	 *
	 * @param Tx_Extbase_Persistence_Session $session The persistence session used to persist data
	 */
	public function __construct(Tx_Extbase_Persistence_Session $session, Tx_Extbase_Persistence_Storage_BackendInterface $storageBackend) {
		$this->session = $session;
		$this->storageBackend = $storageBackend;
		$this->referenceIndex = t3lib_div::makeInstance('t3lib_refindex');
		$this->aggregateRootObjects = new Tx_Extbase_Persistence_ObjectStorage();
		$this->persistenceBackend = $GLOBALS['TYPO3_DB']; // FIXME This is just an intermediate solution
	}

	/**
	 * Injects the DataMapper to map nodes to objects
	 *
	 * @param Tx_Extbase_Persistence_Mapper_DataMapper $dataMapper
	 * @return void
	 */
	public function injectDataMapper(Tx_Extbase_Persistence_Mapper_DataMapper $dataMapper) {
		$this->dataMapper = $dataMapper;
	}

	/**
	 * Injects the identity map
	 *
	 * @param Tx_Extbase_Persistence_IdentityMap $identityMap
	 * @return void

	 */
	public function injectIdentityMap(Tx_Extbase_Persistence_IdentityMap $identityMap) {
		$this->identityMap = $identityMap;
	}

	/**
	 * Injects the QueryObjectModelFactory
	 *
	 * @param Tx_Extbase_Persistence_QOM_QueryObjectModelFactoryInterface $dataMapper
	 * @return void
	 */
	public function injectQOMFactory(Tx_Extbase_Persistence_QOM_QueryObjectModelFactoryInterface $QOMFactory) {
		$this->QOMFactory = $QOMFactory;
	}

	/**
	 * Injects the ValueFactory
	 *
	 * @param Tx_Extbase_Persistence_ValueFactoryInterface $valueFactory
	 * @return void
	 */
	public function injectValueFactory(Tx_Extbase_Persistence_ValueFactoryInterface $valueFactory) {
		$this->valueFactory = $valueFactory;
	}

	/**
	 * Returns the repository session
	 *
	 * @return Tx_Extbase_Persistence_Session
	 */
	public function getSession() {
		return $this->session;
	}

	/**
	 * Returns the Data Mapper
	 *
	 * @return Tx_Extbase_Persistence_Mapper_DataMapper
	 */
	public function getDataMapper() {
		return $this->dataMapper;
	}

	/**
	 * Returns the current QOM factory
	 *
	 * @return Tx_Extbase_Persistence_QOM_QueryObjectModelFactoryInterface
	 */
	public function getQOMFactory() {
		return $this->QOMFactory;
	}

	/**
	 * Returns the current value factory
	 *
	 * @return Tx_Extbase_Persistence_ValueFactoryInterface
	 */
	public function getValueFactory() {
		return $this->valueFactory;
	}

	/**
	 * Returns the current identityMap
	 *
	 * @return Tx_Extbase_Persistence_IdentityMap
	 */
	public function getIdentityMap() {
		return $this->identityMap;
	}

	/**
	 * Returns the (internal) identifier for the object, if it is known to the
	 * backend. Otherwise NULL is returned.
	 *
	 * @param object $object
	 * @return string The identifier for the object if it is known, or NULL
	 */
	public function getIdentifierByObject($object) {
		if ($this->identityMap->hasObject($object)) {
			return $this->identityMap->getIdentifierByObject($object);
		} else {
			return NULL;
		}
	}

	/**
	 * Checks if the given object has ever been persisted.
	 *
	 * @param object $object The object to check
	 * @return boolean TRUE if the object is new, FALSE if the object exists in the repository
	 */
	public function isNewObject($object) {
		return ($this->getIdentifierByObject($object) === NULL);
	}

	/**
	 * Replaces the given object by the second object.
	 *
	 * This method will unregister the existing object at the identity map and
	 * register the new object instead. The existing object must therefore
	 * already be registered at the identity map which is the case for all
	 * reconstituted objects.
	 *
	 * The new object will be identified by the uid which formerly belonged
	 * to the existing object. The existing object looses its uid.
	 *
	 * @param object $existingObject The existing object
	 * @param object $newObject The new object
	 * @return void
	 */
	public function replaceObject($existingObject, $newObject) {
		$existingUid = $this->getIdentifierByObject($existingObject);
		if ($existingUid === NULL) throw new Tx_Extbase_Persistence_Exception_UnknownObject('The given object is unknown to this persistence backend.', 1238070163);

		$this->identityMap->unregisterObject($existingObject);
		$this->identityMap->registerObject($newObject, $existingUid);
	}

	/**
	 * Sets the aggregate root objects
	 *
	 * @param Tx_Extbase_Persistence_ObjectStorage $objects
	 * @return void
	 */
	public function setAggregateRootObjects(Tx_Extbase_Persistence_ObjectStorage $objects) {
		$this->aggregateRootObjects = $objects;
	}

	/**
	 * Sets the deleted objects
	 *
	 * @param Tx_Extbase_Persistence_ObjectStorage $objects
	 * @return void
	 */
	public function setDeletedObjects(Tx_Extbase_Persistence_ObjectStorage $objects) {
		$this->deletedObjects = $objects;
	}

	/**
	 * Commits the current persistence session.
	 *
	 * @return void
	 */
	public function commit() {
		$this->persistObjects();
		$this->processDeletedObjects();
	}

	/**
	 * Traverse and persist all aggregate roots and their object graph.
	 *
	 * @return void
	 */
	protected function persistObjects() {
		foreach ($this->aggregateRootObjects as $object) {
			$this->persistObject($object);
		}
	}

	/**
	 * Persists an object (instert, update) and its related objects (instert, update, delete).
	 *
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $object The object to be inserted
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $parentObject The parent object
	 * @param string $parentPropertyName The name of the property the object is stored in
	 * @return void
	 */
	protected function persistObject(Tx_Extbase_DomainObject_DomainObjectInterface $object, $parentObject = NULL, $parentPropertyName = NULL) {
		$row = array();
		$queuedObjects = array();
		$className = get_class($object);
		$dataMap = $this->dataMapper->getDataMap($className);

		if ($object instanceof Tx_Extbase_DomainObject_AbstractValueObject) {
			$this->mapAlreadyPersistedValueObject($object);
		}

		$properties = $object->_getProperties();
		// Fill up $row[$columnName] array with changed values which need to be stored
		foreach ($properties as $propertyName => $propertyValue) {
			if (!$dataMap->isPersistableProperty($propertyName) || ($propertyValue instanceof Tx_Extbase_Persistence_LazyLoadingProxy)) {
				continue;
			}

			$columnMap = $dataMap->getColumnMap($propertyName);
			if ($object->_isNew() || $object->_isDirty($propertyName)) {
				if ($columnMap->isRelation()) {
					$this->persistRelations($object, $propertyName, $propertyValue, $columnMap, $queuedObjects, $row);
				} else {
					// We have not a relation, this means it is a scalar (like a string or interger value) or an object
					$row[$columnMap->getColumnName()] = $dataMap->convertPropertyValueToFieldValue($propertyValue);
				}
			}
		} // end property iteration for loop

		// The state of the Object has to be stored in a local variable because $object->_isNew() will return FALSE after
		// the object was inserted. We need the initial state here.
		$objectIsNew = $object->_isNew();
		if ($objectIsNew === TRUE) {
			$this->insertObject($object, $parentObject, $parentPropertyName, $row);
		} elseif ($object->_isDirty()) {
			$this->updateObject($object, $parentObject, $parentPropertyName, $row);
		}

		// SK: Where does $queueChildObjects come from? Do we need the code below?
		$objectHasToBeUpdated = $this->processQueuedChildObjects($object, $queuedObjects, $row);
		if ($objectHasToBeUpdated === TRUE) {
			// TODO Check if this can be merged with the first update
			$this->updateObject($object, $parentObject, $parentPropertyName, $row);
		}

		// SK: I need to check the code below more thoroughly
		if ($parentObject instanceof Tx_Extbase_DomainObject_DomainObjectInterface && !empty($parentPropertyName)) {
			$parentDataMap = $this->dataMapper->getDataMap(get_class($parentObject));
			$parentColumnMap = $parentDataMap->getColumnMap($parentPropertyName);
			if (($parentColumnMap->getTypeOfRelation()  === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY)) {
				$this->insertRelationInRelationtable($object, $parentObject, $parentPropertyName);
			}
		}

		$this->identityMap->registerObject($object, $object->getUid());
		$object->_memorizeCleanState();
	}

	/**
	 * Persists a relation. Objects of a 1:n or m:n relation are queued and processed with the parent object. A 1:1 relation
	 * gets persisted immediately. Objects which were removed from the property were deleted immediately, too.
	 *
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $object The object to be inserted
	 * @param string $propertyName The name of the property the related objects are stored in
	 * @param mixed $propertyValue The property value (an array of Domain Objects, ObjectStorage holding Domain Objects or a Domain Object itself)
	 * @return void
	 */
	protected function persistRelations(Tx_Extbase_DomainObject_DomainObjectInterface $object, $propertyName, $propertyValue, Tx_Extbase_Persistence_Mapper_ColumnMap $columnMap, &$queuedObjects, &$row) {
			$columnName = $columnMap->getColumnName();
			if (($columnMap->getTypeOfRelation() === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_MANY) || ($columnMap->getTypeOfRelation() === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY)) {
				if (is_array($propertyValue) || $propertyValue instanceof ArrayAccess) {
					foreach ($propertyValue as $relatedObject) {
						$queuedObjects[$propertyName][] = $relatedObject;
					}
					$row[$columnName] = count($propertyValue); // Will be overwritten if the related objects are referenced by a comma separated list
					foreach ($this->getDeletedChildObjects($object, $propertyName) as $deletedObject) {
						$this->deleteObject($deletedObject, $object, $propertyName, TRUE, FALSE);
					}
				}
			} elseif ($propertyValue instanceof Tx_Extbase_DomainObject_DomainObjectInterface) {
				// TODO Handle Value Objects different
				if ($propertyValue->_isNew() || $propertyValue->_isDirty()) {
					$this->persistObject($propertyValue);
				}
				$row[$columnName] = $propertyValue->getUid();
			}
	}

	/**
	 * Returns the deleted objects determined by a comparison of the clean property value
	 * with the actual property value.
	 *
	 * @param Tx_Extbase_DomainObject_AbstractEntity $object The object to be insterted in the storage
	 * @param string $parentPropertyName The name of the property
	 * @return array An array of deleted objects
	 */
	protected function getDeletedChildObjects(Tx_Extbase_DomainObject_AbstractEntity $object, $propertyName) {
		$deletedObjects = array();
		if (!$object->_isNew()) {
			$cleanProperties = $object->_getCleanProperties();
			$cleanPropertyValue = $cleanProperties[$propertyName];
			$propertyValue = $object->_getProperty($propertyName);
			if ($cleanPropertyValue instanceof Tx_Extbase_Persistence_ObjectStorage) {
				$cleanPropertyValue = $cleanPropertyValue->toArray();
			}
			if ($propertyValue instanceof Tx_Extbase_Persistence_ObjectStorage) {
				$propertyValue = $propertyValue->toArray();
			}
			$deletedObjects = array_diff($cleanPropertyValue, $propertyValue);
		}

		return $deletedObjects;
	}

	/**
	 * This function processes the queued child objects to be persisted. The queue is build while looping over the
	 * collection of Domain Objects stored in a object property.
	 *
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $object The object holding the collection
	 * @param array $queuedObjects The queued child objects
	 * @param array $row The row to be inseted or updated in the database. Passed as reference.
	 * @return boolean TRUE if the object holding the collection has to be updated; otherwise FALSE
	 */
	protected function processQueuedChildObjects(Tx_Extbase_DomainObject_DomainObjectInterface $object, array $queuedChildObjects, array &$row) {
		$objectHasToBeUpdated = FALSE;
		$className = get_class($object);
		$dataMap = $this->dataMapper->getDataMap($className);
		foreach ($queuedChildObjects as $propertyName => $childObjects) {
			$childPidArray = array();
			$columnMap = $dataMap->getColumnMap($propertyName);
			foreach($childObjects as $childObject) {
				$this->persistObject($childObject, $object, $propertyName);
				if ($childObject instanceof Tx_Extbase_DomainObject_DomainObjectInterface) {
					$childPidArray[] = (int)$childObject->getUid();
				}
			}
			if ($columnMap->getParentKeyFieldName() === NULL) { // TRUE: We have to generate a comma separated list stored in the field
				$row[$propertyName] = implode(',', $childPidArray);
				$objectHasToBeUpdated = TRUE;
			}
		}
		return $objectHasToBeUpdated;
	}

	/**
	 * Tests, if the given Value Object already exists in the storage backend. If so, it maps the uid
	 * to the given object.
	 *
	 * @param Tx_Extbase_DomainObject_AbstractValueObject $object The object to be tested
	 */
	protected function mapAlreadyPersistedValueObject(Tx_Extbase_DomainObject_AbstractValueObject $object) {
		$dataMap = $this->dataMapper->getDataMap(get_class($object));
		$properties = $object->_getProperties();
		$result = $this->storageBackend->hasValueObject($properties, $dataMap);
		if ($result !== FALSE) {
			$object->_setProperty('uid', $result);
		}
	}

	/**
	 * Inserts an object in the storage
	 *
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $object The object to be insterted in the storage
	 * @param Tx_Extbase_DomainObject_AbstractEntity|NULL $parentObject The parent object (if any)
	 * @param string|NULL $parentPropertyName The name of the property
	 * @param array $row The $row
	 */
	protected function insertObject(Tx_Extbase_DomainObject_DomainObjectInterface $object, Tx_Extbase_DomainObject_AbstractEntity $parentObject = NULL, $parentPropertyName = NULL, array &$row) {
		$className = get_class($object);
		$dataMap = $this->dataMapper->getDataMap($className);
		$tableName = $dataMap->getTableName();
		$this->addCommonFieldsToRow($object, $parentObject, $parentPropertyName, $row);
		$uid = $this->storageBackend->addRow(
			$tableName,
			$row
			);
		$object->_setProperty('uid', $uid);
		$this->referenceIndex->updateRefIndexTable($tableName, $uid);
	}

	/**
	 * Inserts mm-relation into a relation table
	 *
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $relatedObject The related object
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $parentObject The parent object
	 * @param string $parentPropertyName The name of the parent object's property where the related objects are stored in
	 * @return void
	 */
	protected function insertRelationInRelationtable(Tx_Extbase_DomainObject_DomainObjectInterface $relatedObject, Tx_Extbase_DomainObject_DomainObjectInterface $parentObject, $parentPropertyName) {
		$dataMap = $this->dataMapper->getDataMap(get_class($parentObject));
		$columnMap = $dataMap->getColumnMap($parentPropertyName);
		$row = array(
			$columnMap->getParentKeyFieldName() => (int)$parentObject->getUid(),
			$columnMap->getChildKeyFieldName() => (int)$relatedObject->getUid(),
			'tablenames' => $columnMap->getChildTableName(),
			'sorting' => 9999 // TODO sorting of mm table items
			);
		$res = $this->storageBackend->addRow(
			$columnMap->getRelationTableName(),
			$row,
			TRUE);
		return $res;
	}

	/**
	 * Updates a given object in the storage
	 *
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $object The object to be insterted in the storage
	 * @param Tx_Extbase_DomainObject_AbstractEntity|NULL $parentObject The parent object (if any)
	 * @param string|NULL $parentPropertyName The name of the property
	 * @param array $row The $row
	 */
	protected function updateObject(Tx_Extbase_DomainObject_DomainObjectInterface $object, $parentObject = NULL, $parentPropertyName = NULL, array &$row) {
		$className = get_class($object);
		$dataMap = $this->dataMapper->getDataMap($className);
		$tableName = $dataMap->getTableName();
		$this->addCommonFieldsToRow($object, $parentObject, $parentPropertyName, $row);
		$uid = $object->getUid();
		$row['uid'] = $uid;
		$res = $this->storageBackend->updateRow(
			$tableName,
			$row
			);
		$this->referenceIndex->updateRefIndexTable($tableName, $uid);
		return $res;
	}

	/**
	 * Returns a table row to be inserted or updated in the database
	 *
	 * @param Tx_Extbase_Persistence_Mapper_DataMap $dataMap The appropriate data map representing a database table
	 * @param array $properties The properties of the object
	 * @return array A single row to be inserted in the database
	 */
	protected function addCommonFieldsToRow(Tx_Extbase_DomainObject_DomainObjectInterface $object, $parentObject = NULL, $parentPropertyName = NULL, array &$row) {
		$className = get_class($object);
		$dataMap = $this->dataMapper->getDataMap($className);
		if ($dataMap->hasCreationDateColumn() && $object->_isNew()) {
			$row[$dataMap->getCreationDateColumnName()] = $GLOBALS['EXEC_TIME'];
		}
		if ($dataMap->hasTimestampColumn()) {
			$row[$dataMap->getTimestampColumnName()] = $GLOBALS['EXEC_TIME'];
		}

		if ($object->_isNew() && $dataMap->hasPidColumn() && !isset($row['pid'])) {
			$row['pid'] = $this->determineStoragePageIdForNewRecord($object);
		}

		if ($parentObject instanceof Tx_Extbase_DomainObject_DomainObjectInterface && !empty($parentPropertyName)) {
			$parentDataMap = $this->dataMapper->getDataMap(get_class($parentObject));
			$parentColumnMap = $parentDataMap->getColumnMap($parentPropertyName);
			// FIXME This is a hacky solution
			if ($parentColumnMap->getTypeOfRelation() !== Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
				$parentKeyFieldName = $parentColumnMap->getParentKeyFieldName();
				if ($parentKeyFieldName !== NULL) {
					$row[$parentKeyFieldName] = $parentObject->getUid();
				}
				$parentTableFieldName = $parentColumnMap->getParentTableFieldName();
				if ($parentTableFieldName !== NULL) {
					$row[$parentTableFieldName] = $parentDataMap->getTableName();
				}
			}
		}
	}

	/**
	 * Determine the storage page ID for a given NEW record
	 *
	 * This does the following:
	 * - If there is a TypoScript configuration "classes.CLASSNAME.newRecordStoragePid", that is used to store new records.
	 * - If there is no such TypoScript configuration, it uses the first value of The "storagePid" taken for reading records.
	 *
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $object
	 * @return int the storage Page ID where the object should be stored
	 */
	protected function determineStoragePageIdForNewRecord(Tx_Extbase_DomainObject_DomainObjectInterface $object) {
		$className = get_class($object);
		$extbaseSettings = Tx_Extbase_Dispatcher::getSettings();

		if (isset($extbaseSettings['classes'][$className]) && !empty($extbaseSettings['classes'][$className]['newRecordStoragePid'])) {
			return (int)$extbaseSettings['classes'][$className]['newRecordStoragePid'];
		} else {
			$storagePidList = t3lib_div::intExplode(',', $extbaseSettings['storagePid']);
			return (int) $storagePidList[0];
		}
	}

	/**
	 * Iterate over deleted aggregate root objects and process them
	 *
	 * @return void
	 */
	protected function processDeletedObjects() {
		foreach ($this->deletedObjects as $object) {
			$this->deleteObject($object);
			$this->identityMap->unregisterObject($object);
		}
		$this->deletedObjects = new Tx_Extbase_Persistence_ObjectStorage();
	}

	/**
	 * Deletes an object, it's 1:n related objects, and the m:n relations in relation tables (but not the m:n related objects!)
	 *
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $object The object to be insterted in the storage
	 * @param Tx_Extbase_DomainObject_AbstractEntity|NULL $parentObject The parent object (if any)
	 * @param string|NULL $parentPropertyName The name of the property
	 * @param bool $markAsDeleted Shold we only mark the row as deleted instead of deleting (TRUE by default)?
	 * @return void
	 */
	protected function deleteObject(Tx_Extbase_DomainObject_DomainObjectInterface $object, $parentObject = NULL, $parentPropertyName = NULL, $markAsDeleted = TRUE) {
		// TODO Implement recursive deletions
		$dataMap = $this->dataMapper->getDataMap(get_class($object));
		$tableName = $dataMap->getTableName();
		if (($markAsDeleted === TRUE) && $dataMap->hasDeletedColumn()) {
			$deletedColumnName = $dataMap->getDeletedColumnName();
			$res = $this->storageBackend->updateRow(
				$tableName,
				array(
					'uid' => $object->getUid(),
					$deletedColumnName => 1
					)
				);
		} else {
			$res = $this->storageBackend->removeRow(
				$tableName,
				$object->getUid()
				);
		}
		$this->referenceIndex->updateRefIndexTable($tableName, $uid);
	}

	/**
	 * Delegates the call to the Data Map.
	 * Returns TRUE if the property is persistable (configured in $TCA)
	 *
	 * @param string $className The property name
	 * @param string $propertyName The property name
	 * @return boolean TRUE if the property is persistable (configured in $TCA)
	 */
	public function isPersistableProperty($className, $propertyName) {
		$dataMap = $this->dataMapper->getDataMap($className);
		return $dataMap->isPersistableProperty($propertyName);
	}

}

?>