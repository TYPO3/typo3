<?php
namespace TYPO3\CMS\Extbase\Persistence\Generic;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
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
 */
class Backend implements \TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface, \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Session
	 */
	protected $session;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage
	 */
	protected $aggregateRootObjects;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage
	 */
	protected $deletedEntities;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage
	 */
	protected $changedEntities;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage
	 */
	protected $visitedDuringPersistence;

	/**
	 * @var \TYPO3\CMS\Extbase\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Qom\QueryObjectModelFactory
	 */
	protected $qomFactory;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Storage\BackendInterface
	 */
	protected $storageBackend;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper
	 */
	protected $dataMapper;

	/**
	 * The TYPO3 reference index object
	 *
	 * @var \TYPO3\CMS\Core\Database\ReferenceIndex
	 */
	protected $referenceIndex;

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
	 */
	protected $signalSlotDispatcher;

	/**
	 * Constructs the backend
	 *
	 * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
	 * @return void
	 */
	public function __construct(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
		$this->referenceIndex = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\ReferenceIndex');
		$this->aggregateRootObjects = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$this->deletedEntities = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$this->changedEntities = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Session $session
	 * @return void
	 */
	public function injectSession(\TYPO3\CMS\Extbase\Persistence\Generic\Session $session) {
		$this->session = $session;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Storage\BackendInterface $storageBackend
	 * @return void
	 */
	public function injectStorageBackend(\TYPO3\CMS\Extbase\Persistence\Generic\Storage\BackendInterface $storageBackend) {
		$this->storageBackend = $storageBackend;
	}

	/**
	 * Injects the DataMapper to map nodes to objects
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper $dataMapper
	 * @return void
	 */
	public function injectDataMapper(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper $dataMapper) {
		$this->dataMapper = $dataMapper;
	}

	/**
	 * Injects the Reflection Service
	 *
	 * @param \TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService
	 * @return void
	 */
	public function injectReflectionService(\TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Injects the QueryObjectModelFactory
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Qom\QueryObjectModelFactory $qomFactory
	 * @return void
	 */
	public function injectQomFactory(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\QueryObjectModelFactory $qomFactory) {
		$this->qomFactory = $qomFactory;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher
	 */
	public function injectSignalSlotDispatcher(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher) {
		$this->signalSlotDispatcher = $signalSlotDispatcher;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface $persistenceManager
	 */
	public function setPersistenceManager(\TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface $persistenceManager) {
		$this->persistenceManager = $persistenceManager;
	}

	/**
	 * Returns the repository session
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Session
	 */
	public function getSession() {
		return $this->session;
	}

	/**
	 * Returns the Data Mapper
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper
	 */
	public function getDataMapper() {
		return $this->dataMapper;
	}

	/**
	 * Returns the current QOM factory
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\QueryObjectModelFactory
	 */
	public function getQomFactory() {
		return $this->qomFactory;
	}

	/**
	 * Returns the reflection service
	 *
	 * @return \TYPO3\CMS\Extbase\Reflection\ReflectionService
	 */
	public function getReflectionService() {
		return $this->reflectionService;
	}

	/**
	 * Returns the number of records matching the query.
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\QueryInterface $query
	 * @return integer
	 * @api
	 */
	public function getObjectCountByQuery(\TYPO3\CMS\Extbase\Persistence\QueryInterface $query) {
		return $this->storageBackend->getObjectCountByQuery($query);
	}

	/**
	 * Returns the object data matching the $query.
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\QueryInterface $query
	 * @return array
	 * @api
	 */
	public function getObjectDataByQuery(\TYPO3\CMS\Extbase\Persistence\QueryInterface $query) {
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
		if ($object instanceof \TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy) {
			$object = $object->_loadRealInstance();
			if (!is_object($object)) {
				return NULL;
			}
		}
		if ($this->session->hasObject($object)) {
			return $this->session->getIdentifierByObject($object);
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
		if ($this->session->hasIdentifier($identifier, $className)) {
			return $this->session->getObjectByIdentifier($identifier, $className);
		} else {
			$query = $this->persistenceManager->createQueryForType($className);
			$query->getQuerySettings()->setRespectStoragePage(FALSE);
			$query->getQuerySettings()->setRespectSysLanguage(FALSE);
			return $query->matching($query->equals('uid', $identifier))->execute()->getFirst();
		}
	}

	/**
	 * Checks if the given object has ever been persisted.
	 *
	 * @param object $object The object to check
	 * @return boolean TRUE if the object is new, FALSE if the object exists in the repository
	 */
	public function isNewObject($object) {
		return $this->getIdentifierByObject($object) === NULL;
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
	 * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
	 * @return void
	 * @deprecated since 6.1, will be removed two versions later
	 */
	public function replaceObject($existingObject, $newObject) {
		$this->session->replaceReconstitutedEntity($existingObject, $newObject);
	}

	/**
	 * Sets the aggregate root objects
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $objects
	 * @return void
	 */
	public function setAggregateRootObjects(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $objects) {
		$this->aggregateRootObjects = $objects;
	}

	/**
	 * Sets the changed objects
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $entities
	 * @return void
	 */
	public function setChangedEntities(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $entities) {
		$this->changedEntities = $entities;
	}

	/**
	 * Sets the deleted objects
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $entities
	 * @return void
	 */
	public function setDeletedEntities(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $entities) {
		$this->deletedEntities = $entities;
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
	 * Sets the deleted objects
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $objects
	 * @return void
	 * @deprecated since 6.1, will be removed two versions later
	 */
	public function setDeletedObjects(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $objects) {
		$this->setDeletedEntities($objects);
	}

	/**
	 * Traverse and persist all aggregate roots and their object graph.
	 *
	 * @return void
	 */
	protected function persistObjects() {
		$this->visitedDuringPersistence = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		foreach ($this->aggregateRootObjects as $object) {
			if ($object->_isNew()) {
				$this->insertObject($object);
			}
			$this->persistObject($object, NULL);
		}
		foreach ($this->changedEntities as $object) {
			$this->persistObject($object, NULL);
		}
	}

	/**
	 * Persists the given object.
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object The object to be inserted
	 * @return void
	 */
	protected function persistObject(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object) {
		if (isset($this->visitedDuringPersistence[$object])) {
			return;
		}
		$row = array();
		$queue = array();
		$dataMap = $this->dataMapper->getDataMap(get_class($object));
		$properties = $object->_getProperties();
		foreach ($properties as $propertyName => $propertyValue) {
			if (!$dataMap->isPersistableProperty($propertyName) || $this->propertyValueIsLazyLoaded($propertyValue)) {
				continue;
			}
			$columnMap = $dataMap->getColumnMap($propertyName);
			if ($propertyValue instanceof \TYPO3\CMS\Extbase\Persistence\ObjectStorage) {
				$cleanProperty = $object->_getCleanProperty($propertyName);
				// objectstorage needs to be persisted if the object is new, the objectstorge is dirty, meaning it has
				// been changed after initial build, or a empty objectstorge is present and the cleanstate objectstorage
				// has childelements, meaning all elements should been removed from the objectstorage
				if ($object->_isNew() || $propertyValue->_isDirty() || ($propertyValue->count() == 0 && $cleanProperty && $cleanProperty->count() > 0)) {
					$this->persistObjectStorage($propertyValue, $object, $propertyName, $row);
					$propertyValue->_memorizeCleanState();
				}
				foreach ($propertyValue as $containedObject) {
					if ($containedObject instanceof \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface) {
						$queue[] = $containedObject;
					}
				}
			} elseif ($propertyValue instanceof \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface) {
				if ($object->_isDirty($propertyName)) {
					if ($propertyValue->_isNew()) {
						$this->insertObject($propertyValue);
					}
					$row[$columnMap->getColumnName()] = $this->getPlainValue($propertyValue);
				}
				$queue[] = $propertyValue;
			} elseif ($object->_isNew() || $object->_isDirty($propertyName)) {
				$row[$columnMap->getColumnName()] = $this->getPlainValue($propertyValue, $columnMap);
			}
		}
		if (count($row) > 0) {
			$this->updateObject($object, $row);
			$object->_memorizeCleanState();
		}
		$this->visitedDuringPersistence[$object] = $object->getUid();
		foreach ($queue as $queuedObject) {
			$this->persistObject($queuedObject);
		}
	}

	/**
	 * Checks, if the property value is lazy loaded and was not initialized
	 *
	 * @param mixed $propertyValue The property value
	 * @return bool
	 */
	protected function propertyValueIsLazyLoaded($propertyValue) {
		if ($propertyValue instanceof \TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy) {
			return TRUE;
		}
		if ($propertyValue instanceof \TYPO3\CMS\Extbase\Persistence\Generic\LazyObjectStorage) {
			if ($propertyValue->isInitialized() === FALSE) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Persists a an object storage. Objects of a 1:n or m:n relation are queued and processed with the parent object. A 1:1 relation
	 * gets persisted immediately. Objects which were removed from the property were detached from the parent object. They will not be
	 * deleted by default. You have to annotate the property with "@cascade remove" if you want them to be deleted as well.
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $objectStorage The object storage to be persisted.
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject The parent object. One of the properties holds the object storage.
	 * @param string $propertyName The name of the property holding the object storage.
	 * @param array &$row The row array of the parent object to be persisted. It's passed by reference and gets filled with either a comma separated list of uids (csv) or the number of contained objects.
	 * @return void
	 */
	protected function persistObjectStorage(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $objectStorage, \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject, $propertyName, array &$row) {
		$className = get_class($parentObject);
		$columnMap = $this->dataMapper->getDataMap($className)->getColumnMap($propertyName);
		$propertyMetaData = $this->reflectionService->getClassSchema($className)->getProperty($propertyName);
		foreach ($this->getRemovedChildObjects($parentObject, $propertyName) as $removedObject) {
			$this->detachObjectFromParentObject($removedObject, $parentObject, $propertyName);
			if ($columnMap->getTypeOfRelation() === \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::RELATION_HAS_MANY && $propertyMetaData['cascade'] === 'remove') {
				$this->removeEntity($removedObject);
			}
		}

		$currentUids = array();
		$sortingPosition = 1;
		$updateSortingOfFollowing = FALSE;

		foreach ($objectStorage as $object) {
			if (empty($currentUids)) {
				$sortingPosition = 1;
			} else {
				$sortingPosition++;
			}
			$cleanProperty = $parentObject->_getCleanProperty($propertyName);
			if ($object->_isNew()) {
				$this->insertObject($object);
				$this->attachObjectToParentObject($object, $parentObject, $propertyName, $sortingPosition);
				// if a new object is inserted, all objects after this need to have their sorting updated
				$updateSortingOfFollowing = TRUE;
			} elseif ($cleanProperty === NULL || $cleanProperty->getPosition($object) === NULL) {
				// if parent object is new then it doesn't have cleanProperty yet; before attaching object it's clean position is null
				$this->attachObjectToParentObject($object, $parentObject, $propertyName, $sortingPosition);
				// if a relation is dirty (speaking the same object is removed and added again at a different position), all objects after this needs to be updated the sorting
				$updateSortingOfFollowing = TRUE;
			} elseif ($objectStorage->isRelationDirty($object) || $cleanProperty->getPosition($object) !== $objectStorage->getPosition($object)) {
				$this->updateRelationOfObjectToParentObject($object, $parentObject, $propertyName, $sortingPosition);
				$updateSortingOfFollowing = TRUE;
			} elseif ($updateSortingOfFollowing) {
				if ($sortingPosition > $objectStorage->getPosition($object)) {
					$this->updateRelationOfObjectToParentObject($object, $parentObject, $propertyName, $sortingPosition);
				} else {
					$sortingPosition = $objectStorage->getPosition($object);
				}
			}
			$currentUids[] = $object->getUid();
		}

		if ($columnMap->getParentKeyFieldName() === NULL) {
			$row[$columnMap->getColumnName()] = implode(',', $currentUids);
		} else {
			$row[$columnMap->getColumnName()] = $this->dataMapper->countRelated($parentObject, $propertyName);
		}
	}

	/**
	 * Returns the removed objects determined by a comparison of the clean property value
	 * with the actual property value.
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object The object
	 * @param string $propertyName
	 * @return array An array of removed objects
	 */
	protected function getRemovedChildObjects(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object, $propertyName) {
		$removedObjects = array();
		$cleanPropertyValue = $object->_getCleanProperty($propertyName);
		if (is_array($cleanPropertyValue) || $cleanPropertyValue instanceof \Iterator) {
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
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject
	 * @param string $parentPropertyName
	 * @param integer $sortingPosition
	 * @return void
	 */
	protected function attachObjectToParentObject(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object, \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject, $parentPropertyName, $sortingPosition = 0) {
		$parentDataMap = $this->dataMapper->getDataMap(get_class($parentObject));
		$parentColumnMap = $parentDataMap->getColumnMap($parentPropertyName);
		if ($parentColumnMap->getTypeOfRelation() === \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::RELATION_HAS_MANY) {
			$this->attachObjectToParentObjectRelationHasMany($object, $parentObject, $parentPropertyName, $sortingPosition);
		} elseif ($parentColumnMap->getTypeOfRelation() === \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
			$this->insertRelationInRelationtable($object, $parentObject, $parentPropertyName, $sortingPosition);
		}
	}

	/**
	 * Updates the fields defining the relation between the object and the parent object.
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject
	 * @param string $parentPropertyName
	 * @param integer $sortingPosition
	 * @return void
	 */
	protected function updateRelationOfObjectToParentObject(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object, \TYPO3\CMS\Extbase\DomainObject\AbstractEntity $parentObject, $parentPropertyName, $sortingPosition = 0) {
		$parentDataMap = $this->dataMapper->getDataMap(get_class($parentObject));
		$parentColumnMap = $parentDataMap->getColumnMap($parentPropertyName);
		if ($parentColumnMap->getTypeOfRelation() === \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::RELATION_HAS_MANY) {
			$this->attachObjectToParentObjectRelationHasMany($object, $parentObject, $parentPropertyName, $sortingPosition);
		} elseif ($parentColumnMap->getTypeOfRelation() === \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
			$this->updateRelationInRelationTable($object, $parentObject, $parentPropertyName, $sortingPosition);
		}
	}

	/**
	 * Updates fields defining the relation between the object and the parent object in relation has-many.
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object
	 * @param \TYPO3\CMS\Extbase\DomainObject\AbstractEntity $parentObject
	 * @param string $parentPropertyName
	 * @param integer $sortingPosition
	 * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalRelationTypeException
	 * @return void
	 */
	protected function attachObjectToParentObjectRelationHasMany(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object, \TYPO3\CMS\Extbase\DomainObject\AbstractEntity $parentObject, $parentPropertyName, $sortingPosition = 0) {
		$parentDataMap = $this->dataMapper->getDataMap(get_class($parentObject));
		$parentColumnMap = $parentDataMap->getColumnMap($parentPropertyName);
		if ($parentColumnMap->getTypeOfRelation() !== \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::RELATION_HAS_MANY) {
			throw new \TYPO3\CMS\Extbase\Persistence\Exception\IllegalRelationTypeException(
				'Parent column relation type is ' . $parentColumnMap->getTypeOfRelation() .
				' but should be ' . \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::RELATION_HAS_MANY,
				1345368105
			);
		}
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
		if (!empty($row)) {
			$this->updateObject($object, $row);
		}
	}

	/**
	 * Updates the fields defining the relation between the object and the parent object.
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject
	 * @param string $parentPropertyName
	 * @return void
	 */
	protected function detachObjectFromParentObject(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object, \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject, $parentPropertyName) {
		$parentDataMap = $this->dataMapper->getDataMap(get_class($parentObject));
		$parentColumnMap = $parentDataMap->getColumnMap($parentPropertyName);
		if ($parentColumnMap->getTypeOfRelation() === \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::RELATION_HAS_MANY) {
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
		} elseif ($parentColumnMap->getTypeOfRelation() === \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
			$this->deleteRelationFromRelationtable($object, $parentObject, $parentPropertyName);
		}
	}

	/**
	 * Inserts an object in the storage backend
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object The object to be insterted in the storage
	 * @return void
	 */
	protected function insertObject(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object) {
		if ($object instanceof \TYPO3\CMS\Extbase\DomainObject\AbstractValueObject) {
			$result = $this->getUidOfAlreadyPersistedValueObject($object);
			if ($result !== FALSE) {
				$object->_setProperty('uid', (integer) $result);
				return;
			}
		}
		$dataMap = $this->dataMapper->getDataMap(get_class($object));
		$row = array();
		$this->addCommonFieldsToRow($object, $row);
		if ($dataMap->getLanguageIdColumnName() !== NULL) {
			$row[$dataMap->getLanguageIdColumnName()] = -1;
		}
		$uid = $this->storageBackend->addRow($dataMap->getTableName(), $row);
		$object->_setProperty('uid', (integer) $uid);
		if ((integer) $uid >= 1) {
			$this->signalSlotDispatcher->dispatch(__CLASS__, 'afterInsertObject', array('object' => $object));
		}
		$frameworkConfiguration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		if ($frameworkConfiguration['persistence']['updateReferenceIndex'] === '1') {
			$this->referenceIndex->updateRefIndexTable($dataMap->getTableName(), $uid);
		}
		$this->session->registerObject($object, $uid);
	}

	/**
	 * Tests, if the given Value Object already exists in the storage backend and if so, it returns the uid.
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\AbstractValueObject $object The object to be tested
	 * @return mixed The matching uid if an object was found, else FALSE
	 */
	protected function getUidOfAlreadyPersistedValueObject(\TYPO3\CMS\Extbase\DomainObject\AbstractValueObject $object) {
		return $this->storageBackend->getUidOfAlreadyPersistedValueObject($object);
	}

	/**
	 * Inserts mm-relation into a relation table
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object The related object
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject The parent object
	 * @param string $propertyName The name of the parent object's property where the related objects are stored in
	 * @param integer $sortingPosition Defaults to NULL
	 * @return int The uid of the inserted row
	 */
	protected function insertRelationInRelationtable(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object, \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject, $propertyName, $sortingPosition = NULL) {
		$dataMap = $this->dataMapper->getDataMap(get_class($parentObject));
		$columnMap = $dataMap->getColumnMap($propertyName);
		$row = array(
			$columnMap->getParentKeyFieldName() => (integer) $parentObject->getUid(),
			$columnMap->getChildKeyFieldName() => (integer) $object->getUid(),
			$columnMap->getChildSortByFieldName() => !is_null($sortingPosition) ? (integer) $sortingPosition : 0
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
		$relationTableMatchFields = $columnMap->getRelationTableMatchFields();
		if (count($relationTableMatchFields)) {
			foreach ($relationTableMatchFields as $matchField => $matchValue) {
				$row[$matchField] = $matchValue;
			}
		}
		$relationTableInsertFields = $columnMap->getRelationTableInsertFields();
		if (count($relationTableInsertFields)) {
			foreach ($relationTableInsertFields as $insertField => $insertValue) {
				$row[$insertField] = $insertValue;
			}
		}
		$res = $this->storageBackend->addRow($relationTableName, $row, TRUE);
		return $res;
	}

	/**
	 * Inserts mm-relation into a relation table
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object The related object
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject The parent object
	 * @param string $propertyName The name of the parent object's property where the related objects are stored in
	 * @param integer $sortingPosition Defaults to NULL
	 * @return integer The uid of the inserted row
	 */
	protected function updateRelationInRelationTable(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object, \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject, $propertyName, $sortingPosition = 0) {
		$dataMap = $this->dataMapper->getDataMap(get_class($parentObject));
		$columnMap = $dataMap->getColumnMap($propertyName);
		$row = array(
			$columnMap->getParentKeyFieldName() => (int)$parentObject->getUid(),
			$columnMap->getChildKeyFieldName() => (int)$object->getUid(),
			$columnMap->getChildSortByFieldName() => (int)$sortingPosition
		);
		$relationTableName = $columnMap->getRelationTableName();
		// FIXME Reenable support for tablenames
		// $childTableName = $columnMap->getChildTableName();
		// if (isset($childTableName)) {
		// 	$row['tablenames'] = $childTableName;
		// }

		$relationTableMatchFields = $columnMap->getRelationTableMatchFields();
		if (is_array($relationTableMatchFields) && count($relationTableMatchFields) > 0) {
			$row = array_merge($relationTableMatchFields, $row);
		}
		$res = $this->storageBackend->updateRelationTableRow(
			$relationTableName,
			$row);
		return $res;
	}

	/**
	 * Delete all mm-relations of a parent from a relation table
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject The parent object
	 * @param string $parentPropertyName The name of the parent object's property where the related objects are stored in
	 * @return bool
	 */
	protected function deleteAllRelationsFromRelationtable(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject, $parentPropertyName) {
		$dataMap = $this->dataMapper->getDataMap(get_class($parentObject));
		$columnMap = $dataMap->getColumnMap($parentPropertyName);
		$relationTableName = $columnMap->getRelationTableName();
		$relationMatchFields = array(
			$columnMap->getParentKeyFieldName() => (integer) $parentObject->getUid()
		);
		$relationTableMatchFields = $columnMap->getRelationTableMatchFields();
		if (is_array($relationTableMatchFields) && count($relationTableMatchFields) > 0) {
			$relationMatchFields = array_merge($relationTableMatchFields, $relationMatchFields);
		}
		$res = $this->storageBackend->removeRow($relationTableName, $relationMatchFields, FALSE);
		return $res;
	}

	/**
	 * Delete an mm-relation from a relation table
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $relatedObject The related object
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject The parent object
	 * @param string $parentPropertyName The name of the parent object's property where the related objects are stored in
	 * @return bool
	 */
	protected function deleteRelationFromRelationtable(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $relatedObject, \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject, $parentPropertyName) {
		$dataMap = $this->dataMapper->getDataMap(get_class($parentObject));
		$columnMap = $dataMap->getColumnMap($parentPropertyName);
		$relationTableName = $columnMap->getRelationTableName();
		$res = $this->storageBackend->removeRow($relationTableName, array(
			$columnMap->getParentKeyFieldName() => (integer) $parentObject->getUid(),
			$columnMap->getChildKeyFieldName() => (integer) $relatedObject->getUid()
		), FALSE);
		return $res;
	}

	/**
	 * Fetches maximal value currently used for sorting field in parent table
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject The parent object
	 * @param string $parentPropertyName The name of the parent object's property where the related objects are stored in
	 * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalRelationTypeException
	 * @return mixed the max value
	 */
	protected function fetchMaxSortingFromParentTable(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject, $parentPropertyName) {
		$parentDataMap = $this->dataMapper->getDataMap(get_class($parentObject));
		$parentColumnMap = $parentDataMap->getColumnMap($parentPropertyName);
		if ($parentColumnMap->getTypeOfRelation() === \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::RELATION_HAS_MANY) {
			$tableName = $parentColumnMap->getChildTableName();
			$sortByFieldName = $parentColumnMap->getChildSortByFieldName();

			if (empty($sortByFieldName)) {
				return FALSE;
			}
			$matchFields = array();
			$parentKeyFieldName = $parentColumnMap->getParentKeyFieldName();
			if ($parentKeyFieldName !== NULL) {
				$matchFields[$parentKeyFieldName] = $parentObject->getUid();
				$parentTableFieldName = $parentColumnMap->getParentTableFieldName();
				if ($parentTableFieldName !== NULL) {
					$matchFields[$parentTableFieldName] = $parentDataMap->getTableName();
				}
			}

			if (empty($matchFields)) {
				return FALSE;
			}
		} elseif ($parentColumnMap->getTypeOfRelation() === \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
			$tableName = $parentColumnMap->getRelationTableName();
			$sortByFieldName = $parentColumnMap->getChildSortByFieldName();

			$matchFields = array(
				$parentColumnMap->getParentKeyFieldName() => (int)$parentObject->getUid()
			);

			$relationTableMatchFields = $parentColumnMap->getRelationTableMatchFields();
			if (is_array($relationTableMatchFields) && count($relationTableMatchFields) > 0) {
				$matchFields = array_merge($relationTableMatchFields, $matchFields);
			}
		} else {
			throw new \TYPO3\CMS\Extbase\Persistence\Exception\IllegalRelationTypeException('Unexpected parent column relation type:' . $parentColumnMap->getTypeOfRelation(), 1345368106);
		}

		$result = $this->storageBackend->getMaxValueFromTable(
			$tableName,
			$matchFields,
			$sortByFieldName);
		return $result;
	}

	/**
	 * Updates a given object in the storage
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object The object to be updated
	 * @param array $row Row to be stored
	 * @return bool
	 */
	protected function updateObject(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object, array $row) {
		$dataMap = $this->dataMapper->getDataMap(get_class($object));
		$this->addCommonFieldsToRow($object, $row);
		$row['uid'] = $object->getUid();
		if ($dataMap->getLanguageIdColumnName() !== NULL) {
			$row[$dataMap->getLanguageIdColumnName()] = $object->_getProperty('_languageUid');
			if ($object->_getProperty('_localizedUid') !== NULL) {
				$row['uid'] = $object->_getProperty('_localizedUid');
			}
		}
		$res = $this->storageBackend->updateRow($dataMap->getTableName(), $row);
		if ($res === TRUE) {
			$this->signalSlotDispatcher->dispatch(__CLASS__, 'afterUpdateObject', array('object' => $object));
		}
		$frameworkConfiguration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		if ($frameworkConfiguration['persistence']['updateReferenceIndex'] === '1') {
			$this->referenceIndex->updateRefIndexTable($dataMap->getTableName(), $row['uid']);
		}
		return $res;
	}

	/**
	 * Adds common databse fields to a row
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object
	 * @param array &$row
	 * @return void
	 */
	protected function addCommonFieldsToRow(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object, array &$row) {
		$dataMap = $this->dataMapper->getDataMap(get_class($object));
		$this->addCommonDateFieldsToRow($object, $row);
		if ($dataMap->getRecordTypeColumnName() !== NULL && $dataMap->getRecordType() !== NULL) {
			$row[$dataMap->getRecordTypeColumnName()] = $dataMap->getRecordType();
		}
		if ($object->_isNew() && !isset($row['pid'])) {
			$row['pid'] = $this->determineStoragePageIdForNewRecord($object);
		}
	}

	/**
	 * Adjustes the common date fields of the given row to the current time
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object
	 * @param array &$row The row to be updated
	 * @return void
	 */
	protected function addCommonDateFieldsToRow(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object, array &$row) {
		$dataMap = $this->dataMapper->getDataMap(get_class($object));
		if ($object->_isNew() && $dataMap->getCreationDateColumnName() !== NULL) {
			$row[$dataMap->getCreationDateColumnName()] = $GLOBALS['EXEC_TIME'];
		}
		if ($dataMap->getModificationDateColumnName() !== NULL) {
			$row[$dataMap->getModificationDateColumnName()] = $GLOBALS['EXEC_TIME'];
		}
	}

	/**
	 * Iterate over deleted aggregate root objects and process them
	 *
	 * @return void
	 */
	protected function processDeletedObjects() {
		foreach ($this->deletedEntities as $entity) {
			if ($this->session->hasObject($entity)) {
				$this->removeEntity($entity);
				$this->session->unregisterReconstitutedEntity($entity);
				$this->session->unregisterObject($entity);
			}
		}
		$this->deletedEntities = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
	}

	/**
	 * Deletes an object
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object The object to be removed from the storage
	 * @param boolean $markAsDeleted Wether to just flag the row deleted (default) or really delete it
	 * @return void
	 */
	protected function removeEntity(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object, $markAsDeleted = TRUE) {
		$dataMap = $this->dataMapper->getDataMap(get_class($object));
		$tableName = $dataMap->getTableName();
		if ($markAsDeleted === TRUE && $dataMap->getDeletedFlagColumnName() !== NULL) {
			$deletedColumnName = $dataMap->getDeletedFlagColumnName();
			$row = array(
				'uid' => $object->getUid(),
				$deletedColumnName => 1
			);
			$this->addCommonDateFieldsToRow($object, $row);
			$res = $this->storageBackend->updateRow($tableName, $row);
		} else {
			$res = $this->storageBackend->removeRow($tableName, array('uid' => $object->getUid()));
		}
		if ($res === TRUE) {
			$this->signalSlotDispatcher->dispatch(__CLASS__, 'afterRemoveObject', array('object' => $object));
		}
		$this->removeRelatedObjects($object);
		$frameworkConfiguration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		if ($frameworkConfiguration['persistence']['updateReferenceIndex'] === '1') {
			$this->referenceIndex->updateRefIndexTable($tableName, $object->getUid());
		}
	}

	/**
	 * Remove related objects
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object The object to scanned for related objects
	 * @return void
	 */
	protected function removeRelatedObjects(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object) {
		$className = get_class($object);
		$dataMap = $this->dataMapper->getDataMap($className);
		$classSchema = $this->reflectionService->getClassSchema($className);
		$properties = $object->_getProperties();
		foreach ($properties as $propertyName => $propertyValue) {
			$columnMap = $dataMap->getColumnMap($propertyName);
			$propertyMetaData = $classSchema->getProperty($propertyName);
			if ($propertyMetaData['cascade'] === 'remove') {
				if ($columnMap->getTypeOfRelation() === \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::RELATION_HAS_MANY) {
					foreach ($propertyValue as $containedObject) {
						$this->removeEntity($containedObject);
					}
				} elseif ($propertyValue instanceof \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface) {
					$this->removeEntity($propertyValue);
				}
			}
		}
	}

	/**
	 * Determine the storage page ID for a given NEW record
	 *
	 * This does the following:
	 * - If the domain object has an accessible property 'pid' (i.e. through a getPid() method), that is used to store the record.
	 * - If there is a TypoScript configuration "classes.CLASSNAME.newRecordStoragePid", that is used to store new records.
	 * - If there is no such TypoScript configuration, it uses the first value of The "storagePid" taken for reading records.
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object
	 * @return int the storage Page ID where the object should be stored
	 */
	protected function determineStoragePageIdForNewRecord(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object = NULL) {
		$frameworkConfiguration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		if ($object !== NULL) {
			if (\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertyGettable($object, 'pid')) {
				$pid = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($object, 'pid');
				if (isset($pid)) {
					return (integer) $pid;
				}
			}
			$className = get_class($object);
			if (isset($frameworkConfiguration['persistence']['classes'][$className]) && !empty($frameworkConfiguration['persistence']['classes'][$className]['newRecordStoragePid'])) {
				return (integer) $frameworkConfiguration['persistence']['classes'][$className]['newRecordStoragePid'];
			}
		}
		$storagePidList = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $frameworkConfiguration['persistence']['storagePid']);
		return (integer) $storagePidList[0];
	}

	/**
	 * Returns a plain value, i.e. objects are flattened out if possible.
	 *
	 * @param mixed $input
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap $columnMap
	 * @return mixed
	 */
	protected function getPlainValue($input, $columnMap = NULL) {
		if ($input instanceof \DateTime) {
			if (!is_null($columnMap) && !is_null($columnMap->getDateTimeStorageFormat())) {
				if ($columnMap->getDateTimeStorageFormat() == 'datetime') {
					return $input->format('Y-m-d H:i:s');
				}
				if ($columnMap->getDateTimeStorageFormat() == 'date') {
					return $input->format('Y-m-d');
				}
			} else {
				return $input->format('U');
			}
		} elseif ($input instanceof \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface) {
			return $input->getUid();
		} elseif (is_bool($input)) {
			return $input === TRUE ? 1 : 0;
		} else {
			return $input;
		}
	}
}

?>
