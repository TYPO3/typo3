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
 * @version $Id: Backend.php 2292 2010-05-25 11:11:04Z jocrau $
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
	 * @var Tx_Extbase_Persistence_ObjectStorage
	 */
	protected $visitedDuringPersistence;

	/**
	 * @var Tx_Extbase_Persistence_IdentityMap
	 **/
	protected $identityMap;

	/**
	 * @var Tx_Extbase_Reflection_Service
	 */
	protected $reflectionService;

	/**
	 * @var Tx_Extbase_Persistence_QueryFactoryInterface
	 */
	protected $queryFactory;

	/**
	 * @var Tx_Extbase_Persistence_QOM_QueryObjectModelFactoryInterface
	 */
	protected $qomFactory;

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
	 * @var array
	 **/
	protected $extbaseSettings;

	/**
	 * Constructs the backend
	 *
	 * @param Tx_Extbase_Persistence_Session $session The persistence session used to persist data
	 */
	public function __construct(Tx_Extbase_Persistence_Session $session, Tx_Extbase_Persistence_Storage_BackendInterface $storageBackend) {
		$this->session = $session;
		$this->storageBackend = $storageBackend;
		$this->extbaseSettings = Tx_Extbase_Dispatcher::getExtbaseFrameworkConfiguration();
		if ($this->extbaseSettings['persistence']['updateReferenceIndex'] === '1') {
			$this->referenceIndex = t3lib_div::makeInstance('t3lib_refindex');
		}
		$this->aggregateRootObjects = new Tx_Extbase_Persistence_ObjectStorage();
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
	 * Injects the Reflection Service
	 *
	 * @param Tx_Extbase_Reflection_Service
	 * @return void
	 */
	public function injectReflectionService(Tx_Extbase_Reflection_Service $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Injects the QueryFactory
	 *
	 * @param Tx_Extbase_Persistence_QueryFactoryInterface $queryFactory
	 * @return void
	 */
	public function injectQueryFactory(Tx_Extbase_Persistence_QueryFactoryInterface $queryFactory) {
		$this->queryFactory = $queryFactory;
	}
	
	/**
	 * Injects the QueryObjectModelFactory
	 *
	 * @param Tx_Extbase_Persistence_QOM_QueryObjectModelFactoryInterface $qomFactory
	 * @return void
	 */
	public function injectQomFactory(Tx_Extbase_Persistence_QOM_QueryObjectModelFactoryInterface $qomFactory) {
		$this->qomFactory = $qomFactory;
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
	public function getQomFactory() {
		return $this->qomFactory;
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
	 * Returns the reflection service
	 *
	 * @return Tx_Extbase_Reflection_Service
	 */
	public function getReflectionService() {
		return $this->reflectionService;
	}

	/**
	 * Returns the number of records matching the query.
	 *
	 * @param Tx_Extbase_Persistence_QueryInterface $query
	 * @return integer
	 * @api
	 */
	public function getObjectCountByQuery(Tx_Extbase_Persistence_QueryInterface $query) {
		return $this->storageBackend->getObjectCountByQuery($query);
	}

	/**
	 * Returns the object data matching the $query.
	 *
	 * @param Tx_Extbase_Persistence_QueryInterface $query
	 * @return array
	 * @api
	 */
	public function getObjectDataByQuery(Tx_Extbase_Persistence_QueryInterface $query) {
		return $this->storageBackend->getObjectDataByQuery($query);
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
	 * Returns the object with the (internal) identifier, if it is known to the
	 * backend. Otherwise NULL is returned.
	 *
	 * @param string $identifier
	 * @param string $className
	 * @return object The object for the identifier if it is known, or NULL
	 */
	public function getObjectByIdentifier($identifier, $className) {
		if ($this->identityMap->hasIdentifier($identifier, $className)) {
			return $this->identityMap->getObjectByIdentifier($identifier, $className);
		} else {
			$query = $this->queryFactory->create($className);
			$result = $query->matching($query->withUid($identifier))->execute();
			$object = NULL;
			if (count($result) > 0) {
				$object = current($result);
			}
			return $object;
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
		$this->visitedDuringPersistence = new Tx_Extbase_Persistence_ObjectStorage();
		foreach ($this->aggregateRootObjects as $object) {
			if (!$this->identityMap->hasObject($object)) {
				$this->insertObject($object);
			}
		}
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
	protected function persistObject(Tx_Extbase_DomainObject_DomainObjectInterface $object) {
		if (isset($this->visitedDuringPersistence[$object])) {
			return $this->visitedDuringPersistence[$object];
		} else {
			$this->visitedDuringPersistence[$object] = $object->getUid();
		}

		$row = array();
		$queue = array();
		$className = get_class($object);
		$dataMap = $this->dataMapper->getDataMap($className);
		$classSchema = $this->reflectionService->getClassSchema($className);
		
		$properties = $object->_getProperties();
		foreach ($properties as $propertyName => $propertyValue) {
			if (!$dataMap->isPersistableProperty($propertyName) || $this->propertyValueIsLazyLoaded($propertyValue)) continue;
			
			$columnMap = $dataMap->getColumnMap($propertyName);
			$propertyMetaData = $classSchema->getProperty($propertyName);
			$propertyType = $propertyMetaData['type'];
			// FIXME enable property-type check
			// $this->checkPropertyType($propertyType, $propertyValue);
			if (($propertyValue !== NULL) && ($propertyType === 'SplObjectStorage' || $propertyType === 'Tx_Extbase_Persistence_ObjectStorage')) {
				if ($object->_isNew() || $object->_isDirty($propertyName)) {
					$this->persistObjectStorage($propertyValue, $object, $propertyName, $queue, $row);
					foreach ($propertyValue as $containedObject) {
						if ($containedObject instanceof Tx_Extbase_DomainObject_AbstractDomainObject) {
							$queue[] = $containedObject;
						}
					}
				}
			} elseif ($propertyValue instanceof Tx_Extbase_DomainObject_DomainObjectInterface) {
				if ($object->_isDirty($propertyName)) {
					if ($propertyValue->_isNew()) {
						if ($propertyValue instanceof Tx_Extbase_DomainObject_AbstractEntity) {
							$this->insertObject($propertyValue);
						} else {
							$this->persistValueObject($propertyValue);
						}
					}
					$row[$columnMap->getColumnName()] = $this->getPlainValue($propertyValue);
					$queue[] = $propertyValue;
				}
			} elseif ($object instanceof Tx_Extbase_DomainObject_AbstractValueObject || $object->_isNew() || $object->_isDirty($propertyName)) {
				$row[$columnMap->getColumnName()] = $this->getPlainValue($propertyValue);
			}
		}

		if (count($row) > 0) {
			$this->updateObject($object, $row);
		}
		
		if ($object instanceof Tx_Extbase_DomainObject_AbstractEntity) {
			$object->_memorizeCleanState();
		}

		foreach ($queue as $queuedObject) {
			$this->persistObject($queuedObject);
		}
		
	}

	/**
	 * Checks a value given against the expected type. If not matching, an
	 * UnexpectedTypeException is thrown. NULL is always considered valid.
	 *
	 * @param string $expectedType The expected type
	 * @param mixed $value The value to check
	 * @return void
	 * @throws Tx_Extbase_Persistence_Exception_UnexpectedType
	 */
	protected function checkPropertyType($expectedType, $value) {
		if ($value === NULL) {
			return;
		}

		if (is_object($value)) {
			if (!($value instanceof $expectedType)) {
				throw new Tx_Extbase_Persistence_Exception_UnexpectedTypeException('Expected property of type ' . $expectedType . ', but got ' . get_class($value), 1244465558);				
			}
		} elseif ($expectedType !== gettype($value)) {			
			throw new Tx_Extbase_Persistence_Exception_UnexpectedTypeException('Expected property of type ' . $expectedType . ', but got ' . gettype($value), 1244465558);
		}
	}
	
	/**
	 * Checks, if the property value is lazy loaded and was not initialized
	 *
	 * @param mixed $propertyValue The property value
	 * @return bool
	 */
	public function propertyValueIsLazyLoaded($propertyValue) {
		if ($propertyValue instanceof Tx_Extbase_Persistence_LazyLoadingProxy) return TRUE;
		if (is_object($propertyValue) && get_class($propertyValue) === 'Tx_Extbase_Persistence_LazyObjectStorage') {
			if ($propertyValue->isInitialized() === FALSE) {
				return TRUE;
			}
		}
		return FALSE;
	}
	
	/**
	 * Persists the given value object.
	 *
	 * @return void
	 */
	protected function persistValueObject(Tx_Extbase_DomainObject_AbstractValueObject $object, $sortingPosition = 1) {
		$result = $this->getUidOfAlreadyPersistedValueObject($object);
		if ($result !== FALSE) {
			$object->_setProperty('uid', (int)$result);
		} elseif ($object->_isNew()) {
			$row = array();
			$className = get_class($object);
			$dataMap = $this->dataMapper->getDataMap($className);
			$classSchema = $this->reflectionService->getClassSchema($className);

			$properties = $object->_getProperties();
			foreach ($properties as $propertyName => $propertyValue) {
				if (!$dataMap->isPersistableProperty($propertyName) || $this->propertyValueIsLazyLoaded($propertyValue)) continue;

				$columnMap = $dataMap->getColumnMap($propertyName);
				$propertyMetaData = $classSchema->getProperty($propertyName);
				$propertyType = $propertyMetaData['type'];
				// FIXME enable property-type check
				// $this->checkPropertyType($propertyType, $propertyValue);
				$row[$columnMap->getColumnName()] = $this->getPlainValue($propertyValue);
			}
			$this->insertObject($object, $row);
		}
	}
	
	/**
	 * Tests, if the given Value Object already exists in the storage backend and if so, it returns the uid.
	 *
	 * @param Tx_Extbase_DomainObject_AbstractValueObject $object The object to be tested
	 */
	protected function getUidOfAlreadyPersistedValueObject(Tx_Extbase_DomainObject_AbstractValueObject $object) {
		return $this->storageBackend->getUidOfAlreadyPersistedValueObject($object);
	}
	
	/**
	 * Persists a an object storage. Objects of a 1:n or m:n relation are queued and processed with the parent object. A 1:1 relation
	 * gets persisted immediately. Objects which were removed from the property were detached from the parent object. They will not be
	 * deleted by default. You have to annotate the property with "@cascade remove" if you want them to be deleted as well.
	 *
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $object The object
	 * @param string $propertyName The name of the property the related objects are stored in
	 * @param mixed $propertyValue The property value 
	 * @return void
	 */
	protected function persistObjectStorage(Tx_Extbase_Persistence_ObjectStorage $objectStorage, Tx_Extbase_DomainObject_DomainObjectInterface $parentObject, $propertyName, array &$queue, array &$row) {
		$className = get_class($parentObject);
		$columnMap = $this->dataMapper->getDataMap($className)->getColumnMap($propertyName);
		$columnName = $columnMap->getColumnName();		
		$propertyMetaData = $this->reflectionService->getClassSchema($className)->getProperty($propertyName);

		foreach ($this->getRemovedChildObjects($parentObject, $propertyName) as $removedObject) {
			if ($columnMap->getTypeOfRelation() === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_MANY && $propertyMetaData['cascade'] === 'remove') {
				$this->removeObject($removedObject);
			} else {
				$this->detachObjectFromParentObject($removedObject, $parentObject, $propertyName);
			}
		}

		if ($columnMap->getTypeOfRelation() === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
			$this->deleteAllRelationsFromRelationtable($parentObject, $propertyName);
		}

		$currentUids = array();
		$sortingPosition = 1;
		foreach ($objectStorage as $object) {
			if ($object->_isNew()) {
				if ($object instanceof Tx_Extbase_DomainObject_AbstractEntity) {
					$this->insertObject($object);
				} else {
					$this->persistValueObject($object, $sortingPosition);
				}
			}
			$currentUids[] = $object->getUid();
			$this->attachObjectToParentObject($object, $parentObject, $propertyName, $sortingPosition);
			$sortingPosition++;
		}
		
		if ($columnMap->getParentKeyFieldName() === NULL) {
			$row[$columnMap->getColumnName()] = implode(',', $currentUids);
		} else {
			$row[$columnMap->getColumnName()] = $this->dataMapper->countRelated($parentObject, $propertyName);
		}
	}
	
	/**
	 * Returns the current field value of the given object property from the storage backend.
	 *
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $object The object
	 * @param string $propertyName The property name
	 * @return mixed The field value
	 */
	protected function getCurrentFieldValue(Tx_Extbase_DomainObject_DomainObjectInterface $object, $propertyName) {
		$className = get_class($object);
		$columnMap = $this->dataMapper->getDataMap($className)->getColumnMap($propertyName);
		$query = $this->queryFactory->create($className);
		$query->getQuerySettings()->setReturnRawQueryResult(TRUE);
		$rows = $query->matching($query->withUid($object->getUid()))->execute();
		$currentRow = current($rows);
		$fieldValue = $currentRow[$columnMap->getColumnName()];
		return $fieldValue;
	}
	
	/**
	 * Returns the removed objects determined by a comparison of the clean property value
	 * with the actual property value.
	 *
	 * @param Tx_Extbase_DomainObject_AbstractEntity $object The object
	 * @param string $parentPropertyName The name of the property
	 * @return array An array of removed objects
	 */
	protected function getRemovedChildObjects(Tx_Extbase_DomainObject_AbstractEntity $object, $propertyName) {
		$removedObjects = array();
		$cleanPropertyValue = $object->_getCleanProperty($propertyName);
		if (is_array($cleanPropertyValue) || $cleanPropertyValue instanceof Iterator) {
			$propertyValue = $object->_getProperty($propertyName);
			foreach ($cleanPropertyValue as $containedObject) {
				if (!$propertyValue->contains($containedObject)) {
					$removedObjects[] = $containedObject;
				}
			}
		}
		return $removedObjects;
	}
	
	/**
	 * Updates the fields defining the relation between the object and the parent object.
	 *
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $object 
	 * @param Tx_Extbase_DomainObject_AbstractEntity $parentObject 
	 * @param string $parentPropertyName 
	 * @return void
	 */
	protected function attachObjectToParentObject(Tx_Extbase_DomainObject_DomainObjectInterface $object, Tx_Extbase_DomainObject_AbstractEntity $parentObject, $parentPropertyName, $sortingPosition = 0) {
		$parentDataMap = $this->dataMapper->getDataMap(get_class($parentObject));
		$parentColumnMap = $parentDataMap->getColumnMap($parentPropertyName);
		if ($parentColumnMap->getTypeOfRelation() === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_MANY) {
			$row = array();
			$parentKeyFieldName = $parentColumnMap->getParentKeyFieldName();
			if ($parentKeyFieldName !== NULL) {
				$row[$parentKeyFieldName] = $parentObject->getUid();
				$parentTableFieldName = $parentColumnMap->getParentTableFieldName();
				if ($parentTableFieldName !== NULL) {
					$row[$parentTableFieldName] = $parentDataMap->getTableName();
				}
			}
			$childSortByFieldName = $parentColumnMap->getChildSortByFieldName();
			if (!empty($childSortByFieldName)) {
				$row[$childSortByFieldName] = $sortingPosition;
			}
			if (count($row) > 0) {
				$this->updateObject($object, $row);
			}			
		} elseif ($parentColumnMap->getTypeOfRelation() === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
			$this->insertRelationInRelationtable($object, $parentObject, $parentPropertyName, $sortingPosition);
		}
	}
	
	/**
	 * Updates the fields defining the relation between the object and the parent object.
	 *
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $object 
	 * @param Tx_Extbase_DomainObject_AbstractEntity $parentObject 
	 * @param string $parentPropertyName 
	 * @return void
	 */
	protected function detachObjectFromParentObject(Tx_Extbase_DomainObject_DomainObjectInterface $object, Tx_Extbase_DomainObject_AbstractEntity $parentObject, $parentPropertyName) {
		$parentDataMap = $this->dataMapper->getDataMap(get_class($parentObject));
		$parentColumnMap = $parentDataMap->getColumnMap($parentPropertyName);
		if ($parentColumnMap->getTypeOfRelation() === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_MANY) {
			$row = array();
			$parentKeyFieldName = $parentColumnMap->getParentKeyFieldName();
			if ($parentKeyFieldName !== NULL) {
				$row[$parentKeyFieldName] = '';
				$parentTableFieldName = $parentColumnMap->getParentTableFieldName();
				if ($parentTableFieldName !== NULL) {
					$row[$parentTableFieldName] = '';
				}
			}
			$childSortByFieldName = $parentColumnMap->getChildSortByFieldName();
			if (!empty($childSortByFieldName)) {
				$row[$childSortByFieldName] = 0;
			}
			if (count($row) > 0) {
				$this->updateObject($object, $row);
			}
		} elseif ($parentColumnMap->getTypeOfRelation() === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
			$this->deleteRelationFromRelationtable($object, $parentObject, $parentPropertyName);
		}
	}
	
	/**
	 * Inserts an object in the storage backend
	 *
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $object The object to be insterted in the storage
	 * @return void
	 */
	protected function insertObject(Tx_Extbase_DomainObject_DomainObjectInterface $object, array $row = array()) {
		$dataMap = $this->dataMapper->getDataMap(get_class($object));
		$this->addCommonFieldsToRow($object, $row);
		if($dataMap->getLanguageIdColumnName() !== NULL) {
			$row[$dataMap->getLanguageIdColumnName()] = -1;
		}
		$uid = $this->storageBackend->addRow(
			$dataMap->getTableName(),
			$row
			);
		$object->_setProperty('uid', (int)$uid);
		if ($this->extbaseSettings['persistence']['updateReferenceIndex'] === '1') {
			$this->referenceIndex->updateRefIndexTable($dataMap->getTableName(), $uid);
		}
		$this->identityMap->registerObject($object, $uid);
	}
	
	/**
	 * Inserts mm-relation into a relation table
	 *
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $object The related object
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $parentObject The parent object
	 * @param string $propertyName The name of the parent object's property where the related objects are stored in
	 * @return void
	 */
	protected function insertRelationInRelationtable(Tx_Extbase_DomainObject_DomainObjectInterface $object, Tx_Extbase_DomainObject_DomainObjectInterface $parentObject, $propertyName, $sortingPosition = NULL) {
		$dataMap = $this->dataMapper->getDataMap(get_class($parentObject));
		$columnMap = $dataMap->getColumnMap($propertyName);
		$row = array(
			$columnMap->getParentKeyFieldName() => (int)$parentObject->getUid(),
			$columnMap->getChildKeyFieldName() => (int)$object->getUid(),
			$columnMap->getChildSortByFieldName() => !is_null($sortingPosition) ? (int)$sortingPosition : 0
			);
		$relationTableName = $columnMap->getRelationTableName();
		// FIXME Reenable support for tablenames
		// $childTableName = $columnMap->getChildTableName();
		// if (isset($childTableName)) {
		// 	$row['tablenames'] = $childTableName;
		// }
		if ($columnMap->getRelationTablePageIdColumnName() !== NULL) {
			$row[$columnMap->getRelationTablePageIdColumnName()] = $this->determineStoragePageIdForNewRecord();
		}
		
		$relationTableInsertFields = $columnMap->getRelationTableInsertFields();
		if (count($relationTableInsertFields)) {
			foreach($relationTableInsertFields as $insertField => $insertValue) {
				$row[$insertField] = $insertValue;
			}
		}

		$res = $this->storageBackend->addRow(
			$relationTableName,
			$row,
			TRUE);
		return $res;
	}

	/**
	 * Delete all mm-relations of a parent from a relation table
	 *
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $parentObject The parent object
	 * @param string $parentPropertyName The name of the parent object's property where the related objects are stored in
	 * @return void
	 */
	protected function deleteAllRelationsFromRelationtable(Tx_Extbase_DomainObject_DomainObjectInterface $parentObject, $parentPropertyName) {
		$dataMap = $this->dataMapper->getDataMap(get_class($parentObject));
		$columnMap = $dataMap->getColumnMap($parentPropertyName);
		$relationTableName = $columnMap->getRelationTableName();

		$relationMatchFields = array(
			$columnMap->getParentKeyFieldName() => (int)$parentObject->getUid()
		);

		$relationTableMatchFields = $columnMap->getRelationTableMatchFields();
		if (is_array($relationTableMatchFields) && count($relationTableMatchFields) > 0) {
			$relationMatchFields = array_merge($relationTableMatchFields,$relationMatchFields);
		}

		$res = $this->storageBackend->removeRow(
			$relationTableName,
			$relationMatchFields,
			FALSE);
		return $res;
	}

	/**
	 * Delete an mm-relation from a relation table
	 *
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $relatedObject The related object
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $parentObject The parent object
	 * @param string $parentPropertyName The name of the parent object's property where the related objects are stored in
	 * @return void
	 */
	protected function deleteRelationFromRelationtable(Tx_Extbase_DomainObject_DomainObjectInterface $relatedObject, Tx_Extbase_DomainObject_DomainObjectInterface $parentObject, $parentPropertyName) {
		$dataMap = $this->dataMapper->getDataMap(get_class($parentObject));
		$columnMap = $dataMap->getColumnMap($parentPropertyName);
		$relationTableName = $columnMap->getRelationTableName();
		$res = $this->storageBackend->removeRow(
			$relationTableName,
			array(
				$columnMap->getParentKeyFieldName() => (int)$parentObject->getUid(),
				$columnMap->getChildKeyFieldName() => (int)$relatedObject->getUid(),
				),
			FALSE);
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
	protected function updateObject(Tx_Extbase_DomainObject_DomainObjectInterface $object, array $row) {
		$dataMap = $this->dataMapper->getDataMap(get_class($object));
		$this->addCommonFieldsToRow($object, $row);
		$row['uid'] = $object->getUid();
		if($dataMap->getLanguageIdColumnName() !== NULL) {
			$row[$dataMap->getLanguageIdColumnName()] = $object->_getProperty('_languageUid');
			if ($object->_getProperty('_localizedUid') !== NULL) {
				$row['uid'] = $object->_getProperty('_localizedUid');
			}
		}
		$res = $this->storageBackend->updateRow(
			$dataMap->getTableName(),
			$row
			);
		if ($this->extbaseSettings['persistence']['updateReferenceIndex'] === '1') {
			$this->referenceIndex->updateRefIndexTable($dataMap->getTableName(), $row['uid']);
		}
		return $res;
	}
	
	/**
	 * Returns a table row to be inserted or updated in the database
	 *
	 * @param Tx_Extbase_Persistence_Mapper_DataMap $dataMap The appropriate data map representing a database table
	 * @param array $properties The properties of the object
	 * @return array A single row to be inserted in the database
	 */
	protected function addCommonFieldsToRow(Tx_Extbase_DomainObject_DomainObjectInterface $object, array &$row) {
		$className = get_class($object);
		$dataMap = $this->dataMapper->getDataMap($className);
		if ($object->_isNew() && ($dataMap->getCreationDateColumnName() !== NULL)) {
			$row[$dataMap->getCreationDateColumnName()] = $GLOBALS['EXEC_TIME'];
		}
		if ($dataMap->getModificationDateColumnName() !== NULL) {
			$row[$dataMap->getModificationDateColumnName()] = $GLOBALS['EXEC_TIME'];
		}
		if ($dataMap->getRecordTypeColumnName() !== NULL && $dataMap->getRecordType() !== NULL) {
			$row[$dataMap->getRecordTypeColumnName()] = $dataMap->getRecordType();
		}
		if ($object->_isNew() && !isset($row['pid'])) {
			$row['pid'] = $this->determineStoragePageIdForNewRecord($object);
		}
	}

	/**
	 * Iterate over deleted aggregate root objects and process them
	 *
	 * @return void
	 */
	protected function processDeletedObjects() {
		foreach ($this->deletedObjects as $object) {
			$this->removeObject($object);
			$this->identityMap->unregisterObject($object);
		}
		$this->deletedObjects = new Tx_Extbase_Persistence_ObjectStorage();
	}

	/**
	 * Deletes an object
	 *
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $object The object to be insterted in the storage
	 * @param Tx_Extbase_DomainObject_AbstractEntity|NULL $parentObject The parent object (if any)
	 * @param string|NULL $parentPropertyName The name of the property
	 * @param bool $markAsDeleted Shold we only mark the row as deleted instead of deleting (TRUE by default)?
	 * @return void
	 */
	protected function removeObject(Tx_Extbase_DomainObject_DomainObjectInterface $object, $markAsDeleted = TRUE) {
		$dataMap = $this->dataMapper->getDataMap(get_class($object));
		$tableName = $dataMap->getTableName();
		if (($markAsDeleted === TRUE) && ($dataMap->getDeletedFlagColumnName() !== NULL)) {
			$deletedColumnName = $dataMap->getDeletedFlagColumnName();
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
				array('uid' => $object->getUid())
				);
		}
		$this->removeRelatedObjects($object);
		if ($this->extbaseSettings['persistence']['updateReferenceIndex'] === '1') {
			$this->referenceIndex->updateRefIndexTable($tableName, $object->getUid());
		}		
	}
	
	/**
	 * Remove related objects
	 *
	 * @param Tx_Extbase_DomainObject_DomainObjectInterface $object The object to scanned for related objects
	 * @return void
	 */
	protected function removeRelatedObjects(Tx_Extbase_DomainObject_DomainObjectInterface $object) {
		$className = get_class($object);
		$dataMap = $this->dataMapper->getDataMap($className);
		$classSchema = $this->reflectionService->getClassSchema($className);
				
		$properties = $object->_getProperties();
		foreach ($properties as $propertyName => $propertyValue) {
			$columnMap = $dataMap->getColumnMap($propertyName);
			$propertyMetaData = $classSchema->getProperty($propertyName);
			if ($propertyMetaData['cascade'] === 'remove') {
				if ($columnMap->getTypeOfRelation() === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_MANY) {
					foreach ($propertyValue as $containedObject) {
						$this->removeObject($containedObject);
					}
				} elseif ($propertyValue instanceof Tx_Extbase_DomainObject_DomainObjectInterface) {				
					$this->removeObject($propertyValue);
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
	protected function determineStoragePageIdForNewRecord(Tx_Extbase_DomainObject_DomainObjectInterface $object = NULL) {
		$extbaseSettings = Tx_Extbase_Dispatcher::getExtbaseFrameworkConfiguration();
		if ($object !== NULL) {
			$className = get_class($object);
			if (isset($extbaseSettings['persistence']['classes'][$className]) && !empty($extbaseSettings['persistence']['classes'][$className]['newRecordStoragePid'])) {
				return (int)$extbaseSettings['persistence']['classes'][$className]['newRecordStoragePid'];
			}
		}
		$storagePidList = t3lib_div::intExplode(',', $extbaseSettings['persistence']['storagePid']);
		return (int) $storagePidList[0];
	}
	
	/**
	 * Returns a plain value, i.e. objects are flattened out if possible.
	 *
	 * @param mixed $input
	 * @return mixed
	 */
	protected function getPlainValue($input) {
		if ($input instanceof DateTime) {
			return $input->format('U');
		} elseif ($input instanceof Tx_Extbase_DomainObject_DomainObjectInterface) {
			return $input->getUid();
		} elseif (is_bool($input)) {
			return $input === TRUE ? 1 : 0;
		} else {
			return $input;
		}
	}

}

?>