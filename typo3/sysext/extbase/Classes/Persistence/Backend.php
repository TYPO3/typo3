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
	 * @internal
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
	 * Returns the current QOM factory
	 *
	 * @return Tx_Extbase_Persistence_QOM_QueryObjectModelFactoryInterface
	 * @internal
	 */
	public function getQOMFactory() {
		return $this->QOMFactory;
	}

	/**
	 * Returns the current value factory
	 *
	 * @return Tx_Extbase_Persistence_ValueFactoryInterface
	 * @internal
	 */
	public function getValueFactory() {
		return $this->valueFactory;
	}

	/**
	 * Returns the current identityMap
	 *
	 * @return Tx_Extbase_Persistence_IdentityMap
	 * @internal
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
	public function getUidByObject($object) {
		if ($this->identityMap->hasObject($object)) {
			return $this->identityMap->getUidByObject($object);
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
		return ($this->getUidByObject($object) === NULL);
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
		$existingUid = $this->getUidByObject($existingObject);
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
	 * Inserts an objects corresponding row into the database. If the object is a value object an
	 * existing instance will be looked up.
	 *
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $object The object to be inserted
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $parentObject The parent object
	 * @param string $parentPropertyName The name of the property the object is stored in
	 * @return void
	 */
	protected function persistObject($object, $parentObject = NULL, $parentPropertyName = NULL, $processQueue = TRUE) {
		$row = array();
		$queue = array();
		$className = get_class($object);
		$dataMap = $this->dataMapper->getDataMap($className);
		$properties = $object->_getProperties();

		if ($object instanceof Tx_Extbase_DomainObject_AbstractValueObject) {
			$this->checkForAlreadyPersistedValueObject($object);
		}


		foreach ($properties as $propertyName => $propertyValue) {
			if ($dataMap->isPersistableProperty($propertyName) && ($propertyValue instanceof Tx_Extbase_Persistence_LazyLoadingProxy)) {
				continue;
			}

			$columnMap = $dataMap->getColumnMap($propertyName);
			$columnName = $columnMap->getColumnName();
			if ($object->_isNew() || $object->_isDirty($propertyName)) {
				if ($columnMap->isRelation()) {
					if (($columnMap->getTypeOfRelation() === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_MANY) || ($columnMap->getTypeOfRelation() === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY)) {
						$row[$columnName] = count($properties[$propertyName]);
						foreach ($propertyValue as $containedObject) {
							$queue[] = array($propertyName => $containedObject);
						}
					} elseif ($propertyValue instanceof Tx_Extbase_DomainObject_DomainObjectInterface) {
						// TODO Handle Value Objects different
						if ($propertyValue->_isNew()) {
							$this->persistObject($propertyValue);
						}
						$row[$columnName] = $propertyValue->getUid();
					}
				} else {
					$row[$columnName] = $dataMap->convertPropertyValueToFieldValue($properties[$propertyName], FALSE);
				}
			}
		}

		if ($object->_isNew()) {
			$this->insertObject($object, $parentObject, $parentPropertyName, $row);
		} elseif ($object->_isDirty()) {
			$this->updateObject($object, $parentObject, $parentPropertyName, $row);
		}

		if ($parentObject instanceof Tx_Extbase_DomainObject_DomainObjectInterface && !empty($parentPropertyName)) {
			$parentClassName = get_class($parentObject);
			$parentDataMap = $this->dataMapper->getDataMap($parentClassName);
			$parentColumnMap = $parentDataMap->getColumnMap($parentPropertyName);

			if (($parentColumnMap->getTypeOfRelation()  === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY)) {
				$this->insertRelation($object, $parentObject, $parentPropertyName);
			}
		}

		if ($object instanceof Tx_Extbase_DomainObject_AbstractEntity) {
			$object->_memorizeCleanState();
		}

		if ($processQueue === TRUE) {
			foreach ($queue as $queuedObjects) {
				foreach($queuedObjects as $propertyName => $queuedObject) {
					$this->persistObject($queuedObject, $object, $propertyName);
				}
			}
		}

	}

	/*
	 * Tests, if the given Domain Object already exists in the storage backend
	 *
	 * @param Tx_Extbase_DomainObject_AbstractValueObject $object The object to be tested
	 */
	protected function checkForAlreadyPersistedValueObject(Tx_Extbase_DomainObject_AbstractValueObject $object) {
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
	protected function insertRelation(Tx_Extbase_DomainObject_DomainObjectInterface $relatedObject, Tx_Extbase_DomainObject_DomainObjectInterface $parentObject, $parentPropertyName) {
		$dataMap = $this->dataMapper->getDataMap(get_class($parentObject));
		$row = array(
			'uid_local' => (int)$parentObject->getUid(), // TODO Aliases for relation field names
			'uid_foreign' => (int)$relatedObject->getUid(),
			'tablenames' => $dataMap->getTableName(),
			'sorting' => 9999 // TODO sorting of mm table items
			);
		$tableName = $dataMap->getColumnMap($parentPropertyName)->getRelationTableName();
		$res = $this->storageBackend->addRow(
			$tableName,
			$row
			);
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
			$row[$dataMap->getCreationDateColumnName()] = time();
		}
		if ($dataMap->hasTimestampColumn()) {
			$row[$dataMap->getTimestampColumnName()] = time();
		}
		if ($dataMap->hasPidColumn()) {
			// FIXME Make the settings from $this->cObj available
			$row['pid'] = !empty($this->cObj->data['pages']) ? $this->cObj->data['pages'] : $GLOBALS['TSFE']->id;
		}
		if ($parentObject instanceof Tx_Extbase_DomainObject_DomainObjectInterface && !empty($parentPropertyName)) {
			$parentDataMap = $this->dataMapper->getDataMap(get_class($parentObject));
			$parentColumnMap = $parentDataMap->getColumnMap($parentPropertyName);
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

	/**
	 * Inserts and updates all relations of an object. It also inserts and updates data in relation tables.
	 *
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $object The object for which the relations should be updated
	 * @param string $propertyName The name of the property holding the related child objects
	 * @param array $relations The queued relations
	 * @return void
	 */
	protected function persistRelations(Tx_Extbase_DomainObject_DomainObjectInterface $object, $propertyName, array $relations) {
		$dataMap = $this->dataMapper->getDataMap(get_class($object));
		foreach ($relations as $propertyName => $relatedObjects) {
			if (!empty($relatedObjects)) {
				$typeOfRelation = $dataMap->getColumnMap($propertyName)->getTypeOfRelation();
				foreach ($relatedObjects as $relatedObject) {
					if ($relatedObject->_isNew()) {
						$this->persistObject($relatedObject, $object, $propertyName);
						if ($typeOfRelation === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
							$this->insertRelationInRelationTable($relatedObject, $object, $propertyName);
						}
					} elseif ($relatedObject->_isDirty()) {
						$this->persistObject($relatedObject, $object, $propertyName);
					}
				}
			}
		}
	}

	/**
	 * Iterate over deleted objects and process them
	 *
	 * @return void
	 */
	protected function processDeletedObjects() {
		foreach ($this->deletedObjects as $object) {
			$this->deleteObject($object);
			if ($this->identityMap->hasObject($object)) {
				$this->session->registerRemovedObject($object);
				$this->identityMap->unregisterObject($object);
			}
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
	 * @param bool $recurseIntoRelations Shold we delete also dependant aggregates (FALSE by default)?
	 * @return void
	 */
	protected function deleteObject(Tx_Extbase_DomainObject_DomainObjectInterface $object, $parentObject = NULL, $parentPropertyName = NULL, $markAsDeleted = TRUE, $recurseIntoRelations = FALSE) {
		// TODO Implement recursive deletions
		$dataMap = $this->dataMapper->getDataMap(get_class($object));
		$tableName = $dataMap->getTableName();
		if ($markAsDeleted === TRUE && $dataMap->hasDeletedColumn()) {
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
	 * Deletes all relations of an object.
	 *
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $object The object for which the relations should be updated
	 * @param string $propertyName The name of the property holding the related child objects
	 * @param array $relations The queued relations
	 * @return void
	 */
	protected function deleteRelatedObjects(Tx_Extbase_DomainObject_DomainObjectInterface $object, array $relations) {
		$dataMap = $this->dataMapper->getDataMap(get_class($object));
		foreach ($relations as $propertyName => $relatedObjects) {
			if (is_array($relatedObjects)) {
				foreach ($relatedObjects as $relatedObject) {
					$this->deleteObject($relatedObject, $object, $propertyName);
					if ($dataMap->getColumnMap($propertyName)->getTypeOfRelation() === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
						$this->deleteRelationInRelationTable($relatedObject, $object, $propertyName);
					}
				}
			}
		}
	}

	/**
	 * Update relations in a relation table
	 *
	 * @param array $relatedObjects An array of related objects
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $parentObject The parent object
	 * @param string $parentPropertyName The name of the parent object's property where the related objects are stored in
	 * @return void
	 */
	protected function deleteRelationInRelationTable($relatedObject, Tx_Extbase_DomainObject_DomainObjectInterface $parentObject, $parentPropertyName) {
		$dataMap = $this->dataMapper->getDataMap(get_class($parentObject));
		$tableName = $dataMap->getColumnMap($parentPropertyName)->getRelationTableName();
		// TODO Remove dependency to the t3lib_db instance
		$res = $this->persistenceBackend->exec_SELECTquery(
			'uid_foreign',
			$tableName,
			'uid_local=' . $parentObject->getUid()
			);
		$existingRelations = array();
		while($row = $this->persistenceBackend->sql_fetch_assoc($res)) {
			$existingRelations[current($row)] = current($row);
		}
		$relationsToDelete = $existingRelations;
		if (is_array($relatedObject)) {
			foreach ($relatedObject as $relatedObject) {
				$relatedObjectUid = $relatedObject->getUid();
				if (array_key_exists($relatedObjectUid, $relationsToDelete)) {
					unset($relationsToDelete[$relatedObjectUid]);
				}
			}
		}
		if (count($relationsToDelete) > 0) {
			$relationsToDeleteList = implode(',', $relationsToDelete);
			$res = $this->persistenceBackend->exec_DELETEquery(
				$tableName,
				'uid_local=' . $parentObject->getUid() . ' AND uid_foreign IN (' . $relationsToDeleteList . ')'
				);
		}
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