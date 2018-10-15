<?php
namespace TYPO3\CMS\Extbase\Persistence\Generic;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\ObjectMonitoringInterface;

/**
 * A persistence backend. This backend maps objects to the relational model of the storage backend.
 * It persists all added, removed and changed objects.
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class Backend implements \TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface, \TYPO3\CMS\Core\SingletonInterface
{
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
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory
     */
    protected $dataMapFactory;

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
     * @param \TYPO3\CMS\Extbase\Persistence\Generic\Session $session
     */
    public function injectSession(\TYPO3\CMS\Extbase\Persistence\Generic\Session $session)
    {
        $this->session = $session;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService
     */
    public function injectReflectionService(\TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService)
    {
        $this->reflectionService = $reflectionService;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\Generic\Qom\QueryObjectModelFactory $qomFactory
     */
    public function injectQomFactory(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\QueryObjectModelFactory $qomFactory)
    {
        $this->qomFactory = $qomFactory;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\Generic\Storage\BackendInterface $storageBackend
     */
    public function injectStorageBackend(\TYPO3\CMS\Extbase\Persistence\Generic\Storage\BackendInterface $storageBackend)
    {
        $this->storageBackend = $storageBackend;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory $dataMapFactory
     */
    public function injectDataMapFactory(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory $dataMapFactory)
    {
        $this->dataMapFactory = $dataMapFactory;
    }

    /**
     * @param \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher
     */
    public function injectSignalSlotDispatcher(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher)
    {
        $this->signalSlotDispatcher = $signalSlotDispatcher;
    }

    /**
     * Constructs the backend
     *
     * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
     */
    public function __construct(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager)
    {
        $this->configurationManager = $configurationManager;
        $this->referenceIndex = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ReferenceIndex::class);
        $this->referenceIndex->enableRuntimeCache();
        $this->aggregateRootObjects = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->deletedEntities = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->changedEntities = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface $persistenceManager
     */
    public function setPersistenceManager(\TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface $persistenceManager)
    {
        $this->persistenceManager = $persistenceManager;
    }

    /**
     * Returns the repository session
     *
     * @return \TYPO3\CMS\Extbase\Persistence\Generic\Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Returns the current QOM factory
     *
     * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\QueryObjectModelFactory
     */
    public function getQomFactory()
    {
        return $this->qomFactory;
    }

    /**
     * Returns the reflection service
     *
     * @return \TYPO3\CMS\Extbase\Reflection\ReflectionService
     */
    public function getReflectionService()
    {
        return $this->reflectionService;
    }

    /**
     * Returns the number of records matching the query.
     *
     * @param \TYPO3\CMS\Extbase\Persistence\QueryInterface $query
     * @return int
     */
    public function getObjectCountByQuery(\TYPO3\CMS\Extbase\Persistence\QueryInterface $query)
    {
        return $this->storageBackend->getObjectCountByQuery($query);
    }

    /**
     * Returns the object data matching the $query.
     *
     * @param \TYPO3\CMS\Extbase\Persistence\QueryInterface $query
     * @return array
     */
    public function getObjectDataByQuery(\TYPO3\CMS\Extbase\Persistence\QueryInterface $query)
    {
        $query = $this->emitBeforeGettingObjectDataSignal($query);
        $result = $this->storageBackend->getObjectDataByQuery($query);
        $result = $this->emitAfterGettingObjectDataSignal($query, $result);
        return $result;
    }

    /**
     * Emits a signal before object data is fetched
     *
     * @param \TYPO3\CMS\Extbase\Persistence\QueryInterface $query
     * @return \TYPO3\CMS\Extbase\Persistence\QueryInterface Modified query
     */
    protected function emitBeforeGettingObjectDataSignal(\TYPO3\CMS\Extbase\Persistence\QueryInterface $query)
    {
        $signalArguments = $this->signalSlotDispatcher->dispatch(__CLASS__, 'beforeGettingObjectData', [$query]);
        return $signalArguments[0];
    }

    /**
     * Emits a signal after object data is fetched
     *
     * @param \TYPO3\CMS\Extbase\Persistence\QueryInterface $query
     * @param array $result
     * @return array Modified result
     */
    protected function emitAfterGettingObjectDataSignal(\TYPO3\CMS\Extbase\Persistence\QueryInterface $query, array $result)
    {
        $signalArguments = $this->signalSlotDispatcher->dispatch(__CLASS__, 'afterGettingObjectData', [$query, $result]);
        return $signalArguments[1];
    }

    /**
     * Returns the (internal) identifier for the object, if it is known to the
     * backend. Otherwise NULL is returned.
     *
     * @param object $object
     * @return string|null The identifier for the object if it is known, or NULL
     */
    public function getIdentifierByObject($object)
    {
        if ($object instanceof \TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy) {
            $object = $object->_loadRealInstance();
            if (!is_object($object)) {
                return null;
            }
        }
        return $this->session->getIdentifierByObject($object);
    }

    /**
     * Returns the object with the (internal) identifier, if it is known to the
     * backend. Otherwise NULL is returned.
     *
     * @param string $identifier
     * @param string $className
     * @return object|null The object for the identifier if it is known, or NULL
     */
    public function getObjectByIdentifier($identifier, $className)
    {
        if ($this->session->hasIdentifier($identifier, $className)) {
            return $this->session->getObjectByIdentifier($identifier, $className);
        }
        $query = $this->persistenceManager->createQueryForType($className);
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->getQuerySettings()->setRespectSysLanguage(false);
        $query->getQuerySettings()->setLanguageOverlayMode(true);
        return $query->matching($query->equals('uid', $identifier))->execute()->getFirst();
    }

    /**
     * Checks if the given object has ever been persisted.
     *
     * @param object $object The object to check
     * @return bool TRUE if the object is new, FALSE if the object exists in the repository
     */
    public function isNewObject($object)
    {
        return $this->getIdentifierByObject($object) === null;
    }

    /**
     * Sets the aggregate root objects
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $objects
     */
    public function setAggregateRootObjects(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $objects)
    {
        $this->aggregateRootObjects = $objects;
    }

    /**
     * Sets the changed objects
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $entities
     */
    public function setChangedEntities(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $entities)
    {
        $this->changedEntities = $entities;
    }

    /**
     * Sets the deleted objects
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $entities
     */
    public function setDeletedEntities(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $entities)
    {
        $this->deletedEntities = $entities;
    }

    /**
     * Commits the current persistence session.
     */
    public function commit()
    {
        $this->persistObjects();
        $this->processDeletedObjects();
    }

    /**
     * Traverse and persist all aggregate roots and their object graph.
     */
    protected function persistObjects()
    {
        $this->visitedDuringPersistence = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        foreach ($this->aggregateRootObjects as $object) {
            /** @var DomainObjectInterface $object */
            if ($object->_isNew()) {
                $this->insertObject($object);
            }
            $this->persistObject($object);
        }
        foreach ($this->changedEntities as $object) {
            $this->persistObject($object);
        }
    }

    /**
     * Persists the given object.
     *
     * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object The object to be inserted
     */
    protected function persistObject(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object)
    {
        if (isset($this->visitedDuringPersistence[$object])) {
            return;
        }
        $row = [];
        $queue = [];
        $dataMap = $this->dataMapFactory->buildDataMap(get_class($object));
        $properties = $object->_getProperties();
        foreach ($properties as $propertyName => $propertyValue) {
            if (!$dataMap->isPersistableProperty($propertyName) || $this->propertyValueIsLazyLoaded($propertyValue)) {
                continue;
            }
            $columnMap = $dataMap->getColumnMap($propertyName);
            if ($propertyValue instanceof \TYPO3\CMS\Extbase\Persistence\ObjectStorage) {
                $cleanProperty = $object->_getCleanProperty($propertyName);
                // objectstorage needs to be persisted if the object is new, the objectstorge is dirty, meaning it has
                // been changed after initial build, or an empty objectstorge is present and the cleanstate objectstorage
                // has childelements, meaning all elements should been removed from the objectstorage
                if ($object->_isNew() || $propertyValue->_isDirty() || ($propertyValue->count() === 0 && $cleanProperty && $cleanProperty->count() > 0)) {
                    $this->persistObjectStorage($propertyValue, $object, $propertyName, $row);
                    $propertyValue->_memorizeCleanState();
                }
                foreach ($propertyValue as $containedObject) {
                    if ($containedObject instanceof \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface) {
                        $queue[] = $containedObject;
                    }
                }
            } elseif ($propertyValue instanceof \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface
                && $object instanceof ObjectMonitoringInterface) {
                if ($object->_isDirty($propertyName)) {
                    if ($propertyValue->_isNew()) {
                        $this->insertObject($propertyValue, $object, $propertyName);
                    }
                    $row[$columnMap->getColumnName()] = $this->getPlainValue($propertyValue);
                }
                $queue[] = $propertyValue;
            } elseif ($object->_isNew() || $object->_isDirty($propertyName)) {
                $row[$columnMap->getColumnName()] = $this->getPlainValue($propertyValue, $columnMap);
            }
        }
        if (!empty($row)) {
            $this->updateObject($object, $row);
            $object->_memorizeCleanState();
        }
        $this->visitedDuringPersistence[$object] = $object->getUid();
        foreach ($queue as $queuedObject) {
            $this->persistObject($queuedObject);
        }
        $this->emitAfterPersistObjectSignal($object);
    }

    /**
     * Checks, if the property value is lazy loaded and was not initialized
     *
     * @param mixed $propertyValue The property value
     * @return bool
     */
    protected function propertyValueIsLazyLoaded($propertyValue)
    {
        if ($propertyValue instanceof \TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy) {
            return true;
        }
        if ($propertyValue instanceof \TYPO3\CMS\Extbase\Persistence\Generic\LazyObjectStorage) {
            if ($propertyValue->isInitialized() === false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Persists an object storage. Objects of a 1:n or m:n relation are queued and processed with the parent object.
     * A 1:1 relation gets persisted immediately. Objects which were removed from the property were detached from
     * the parent object. They will not be deleted by default. You have to annotate the property
     * with '@TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")' if you want them to be deleted as well.
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $objectStorage The object storage to be persisted.
     * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject The parent object. One of the properties holds the object storage.
     * @param string $propertyName The name of the property holding the object storage.
     * @param array &$row The row array of the parent object to be persisted. It's passed by reference and gets filled with either a comma separated list of uids (csv) or the number of contained objects.
     */
    protected function persistObjectStorage(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $objectStorage, \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject, $propertyName, array &$row)
    {
        $className = get_class($parentObject);
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $dataMapper = $objectManager->get(DataMapper::class);
        $columnMap = $this->dataMapFactory->buildDataMap($className)->getColumnMap($propertyName);
        $propertyMetaData = $this->reflectionService->getClassSchema($className)->getProperty($propertyName);
        foreach ($this->getRemovedChildObjects($parentObject, $propertyName) as $removedObject) {
            $this->detachObjectFromParentObject($removedObject, $parentObject, $propertyName);
            if ($columnMap->getTypeOfRelation() === \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::RELATION_HAS_MANY && $propertyMetaData['annotations']['cascade'] === 'remove') {
                $this->removeEntity($removedObject);
            }
        }

        $currentUids = [];
        $sortingPosition = 1;
        $updateSortingOfFollowing = false;

        foreach ($objectStorage as $object) {
            /** @var DomainObjectInterface $object */
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
                $updateSortingOfFollowing = true;
            } elseif ($cleanProperty === null || $cleanProperty->getPosition($object) === null) {
                // if parent object is new then it doesn't have cleanProperty yet; before attaching object it's clean position is null
                $this->attachObjectToParentObject($object, $parentObject, $propertyName, $sortingPosition);
                // if a relation is dirty (speaking the same object is removed and added again at a different position), all objects after this needs to be updated the sorting
                $updateSortingOfFollowing = true;
            } elseif ($objectStorage->isRelationDirty($object) || $cleanProperty->getPosition($object) !== $objectStorage->getPosition($object)) {
                $this->updateRelationOfObjectToParentObject($object, $parentObject, $propertyName, $sortingPosition);
                $updateSortingOfFollowing = true;
            } elseif ($updateSortingOfFollowing) {
                if ($sortingPosition > $objectStorage->getPosition($object)) {
                    $this->updateRelationOfObjectToParentObject($object, $parentObject, $propertyName, $sortingPosition);
                } else {
                    $sortingPosition = $objectStorage->getPosition($object);
                }
            }
            $currentUids[] = $object->getUid();
        }

        if ($columnMap->getParentKeyFieldName() === null) {
            $row[$columnMap->getColumnName()] = implode(',', $currentUids);
        } else {
            $row[$columnMap->getColumnName()] = $dataMapper->countRelated($parentObject, $propertyName);
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
    protected function getRemovedChildObjects(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object, $propertyName)
    {
        $removedObjects = [];
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
     * @param int $sortingPosition
     */
    protected function attachObjectToParentObject(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object, \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject, $parentPropertyName, $sortingPosition = 0)
    {
        $parentDataMap = $this->dataMapFactory->buildDataMap(get_class($parentObject));

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
     * @param \TYPO3\CMS\Extbase\DomainObject\AbstractEntity $parentObject
     * @param string $parentPropertyName
     * @param int $sortingPosition
     */
    protected function updateRelationOfObjectToParentObject(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object, \TYPO3\CMS\Extbase\DomainObject\AbstractEntity $parentObject, $parentPropertyName, $sortingPosition = 0)
    {
        $parentDataMap = $this->dataMapFactory->buildDataMap(get_class($parentObject));
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
     * @param int $sortingPosition
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalRelationTypeException
     */
    protected function attachObjectToParentObjectRelationHasMany(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object, \TYPO3\CMS\Extbase\DomainObject\AbstractEntity $parentObject, $parentPropertyName, $sortingPosition = 0)
    {
        $parentDataMap = $this->dataMapFactory->buildDataMap(get_class($parentObject));
        $parentColumnMap = $parentDataMap->getColumnMap($parentPropertyName);
        if ($parentColumnMap->getTypeOfRelation() !== \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::RELATION_HAS_MANY) {
            throw new \TYPO3\CMS\Extbase\Persistence\Exception\IllegalRelationTypeException(
                'Parent column relation type is ' . $parentColumnMap->getTypeOfRelation() .
                ' but should be ' . \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::RELATION_HAS_MANY,
                1345368105
            );
        }
        $row = [];
        $parentKeyFieldName = $parentColumnMap->getParentKeyFieldName();
        if ($parentKeyFieldName !== null) {
            $row[$parentKeyFieldName] = $parentObject->_getProperty('_localizedUid') ?: $parentObject->getUid();
            $parentTableFieldName = $parentColumnMap->getParentTableFieldName();
            if ($parentTableFieldName !== null) {
                $row[$parentTableFieldName] = $parentDataMap->getTableName();
            }
            $relationTableMatchFields = $parentColumnMap->getRelationTableMatchFields();
            if (is_array($relationTableMatchFields)) {
                $row = array_merge($relationTableMatchFields, $row);
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
     */
    protected function detachObjectFromParentObject(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object, \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject, $parentPropertyName)
    {
        $parentDataMap = $this->dataMapFactory->buildDataMap(get_class($parentObject));
        $parentColumnMap = $parentDataMap->getColumnMap($parentPropertyName);
        if ($parentColumnMap->getTypeOfRelation() === \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::RELATION_HAS_MANY) {
            $row = [];
            $parentKeyFieldName = $parentColumnMap->getParentKeyFieldName();
            if ($parentKeyFieldName !== null) {
                $row[$parentKeyFieldName] = 0;
                $parentTableFieldName = $parentColumnMap->getParentTableFieldName();
                if ($parentTableFieldName !== null) {
                    $row[$parentTableFieldName] = '';
                }
                $relationTableMatchFields = $parentColumnMap->getRelationTableMatchFields();
                if (is_array($relationTableMatchFields) && !empty($relationTableMatchFields)) {
                    $row = array_merge(array_fill_keys(array_keys($relationTableMatchFields), ''), $row);
                }
            }
            $childSortByFieldName = $parentColumnMap->getChildSortByFieldName();
            if (!empty($childSortByFieldName)) {
                $row[$childSortByFieldName] = 0;
            }
            if (!empty($row)) {
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
     * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject The parentobject.
     * @param string $parentPropertyName
     */
    protected function insertObject(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object, \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject = null, $parentPropertyName = '')
    {
        if ($object instanceof \TYPO3\CMS\Extbase\DomainObject\AbstractValueObject) {
            $result = $this->getUidOfAlreadyPersistedValueObject($object);
            if ($result !== false) {
                $object->_setProperty('uid', (int)$result);
                return;
            }
        }
        $dataMap = $this->dataMapFactory->buildDataMap(get_class($object));
        $row = [];
        $properties = $object->_getProperties();
        foreach ($properties as $propertyName => $propertyValue) {
            if (!$dataMap->isPersistableProperty($propertyName) || $this->propertyValueIsLazyLoaded($propertyValue)) {
                continue;
            }
            $columnMap = $dataMap->getColumnMap($propertyName);
            if ($columnMap->getTypeOfRelation() === ColumnMap::RELATION_HAS_ONE) {
                $row[$columnMap->getColumnName()] = 0;
            } elseif ($columnMap->getTypeOfRelation() !== ColumnMap::RELATION_NONE) {
                if ($columnMap->getParentKeyFieldName() === null) {
                    // CSV type relation
                    $row[$columnMap->getColumnName()] = '';
                } else {
                    // MM type relation
                    $row[$columnMap->getColumnName()] = 0;
                }
            } elseif ($propertyValue !== null) {
                $row[$columnMap->getColumnName()] = $this->getPlainValue($propertyValue, $columnMap);
            }
        }
        $this->addCommonFieldsToRow($object, $row);
        if ($dataMap->getLanguageIdColumnName() !== null && $object->_getProperty('_languageUid') === null) {
            $row[$dataMap->getLanguageIdColumnName()] = 0;
            $object->_setProperty('_languageUid', 0);
        }
        if ($dataMap->getTranslationOriginColumnName() !== null) {
            $row[$dataMap->getTranslationOriginColumnName()] = 0;
        }
        if ($dataMap->getTranslationOriginDiffSourceName() !== null) {
            $row[$dataMap->getTranslationOriginDiffSourceName()] = '';
        }
        if ($parentObject !== null && $parentPropertyName) {
            $parentColumnDataMap = $this->dataMapFactory->buildDataMap(get_class($parentObject))->getColumnMap($parentPropertyName);
            $relationTableMatchFields = $parentColumnDataMap->getRelationTableMatchFields();
            if (is_array($relationTableMatchFields)) {
                $row = array_merge($relationTableMatchFields, $row);
            }
            if ($parentColumnDataMap->getParentKeyFieldName() !== null) {
                $row[$parentColumnDataMap->getParentKeyFieldName()] = (int)$parentObject->getUid();
            }
        }
        $uid = $this->storageBackend->addRow($dataMap->getTableName(), $row);
        $object->_setProperty('uid', (int)$uid);
        $object->setPid((int)$row['pid']);
        if ((int)$uid >= 1) {
            $this->emitAfterInsertObjectSignal($object);
        }
        $frameworkConfiguration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        if ($frameworkConfiguration['persistence']['updateReferenceIndex'] === '1') {
            $this->referenceIndex->updateRefIndexTable($dataMap->getTableName(), $uid);
        }
        $this->session->registerObject($object, $uid);
        if ((int)$uid >= 1) {
            $this->emitEndInsertObjectSignal($object);
        }
    }

    /**
     * Emits a signal after an object was added to the storage
     *
     * @param DomainObjectInterface $object
     */
    protected function emitAfterInsertObjectSignal(DomainObjectInterface $object)
    {
        $this->signalSlotDispatcher->dispatch(__CLASS__, 'afterInsertObject', [$object]);
    }

    /**
     * Emits a signal after an object was registered in persistence session
     * This signal replaces the afterInsertObject signal which is now deprecated
     *
     * @param DomainObjectInterface $object
     */
    protected function emitEndInsertObjectSignal(DomainObjectInterface $object)
    {
        $this->signalSlotDispatcher->dispatch(__CLASS__, 'endInsertObject', [$object]);
    }

    /**
     * Tests, if the given Value Object already exists in the storage backend and if so, it returns the uid.
     *
     * @param \TYPO3\CMS\Extbase\DomainObject\AbstractValueObject $object The object to be tested
     * @return mixed The matching uid if an object was found, else FALSE
     */
    protected function getUidOfAlreadyPersistedValueObject(\TYPO3\CMS\Extbase\DomainObject\AbstractValueObject $object)
    {
        return $this->storageBackend->getUidOfAlreadyPersistedValueObject($object);
    }

    /**
     * Inserts mm-relation into a relation table
     *
     * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object The related object
     * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject The parent object
     * @param string $propertyName The name of the parent object's property where the related objects are stored in
     * @param int $sortingPosition Defaults to NULL
     * @return int The uid of the inserted row
     */
    protected function insertRelationInRelationtable(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object, \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject, $propertyName, $sortingPosition = null)
    {
        $dataMap = $this->dataMapFactory->buildDataMap(get_class($parentObject));
        $columnMap = $dataMap->getColumnMap($propertyName);
        $parentUid = $parentObject->getUid();
        if ($parentObject->_getProperty('_localizedUid') !== null) {
            $parentUid = $parentObject->_getProperty('_localizedUid');
        }
        $row = [
            $columnMap->getParentKeyFieldName() => (int)$parentUid,
            $columnMap->getChildKeyFieldName() => (int)$object->getUid(),
            $columnMap->getChildSortByFieldName() => $sortingPosition !== null ? (int)$sortingPosition : 0
        ];
        $relationTableName = $columnMap->getRelationTableName();
        if ($columnMap->getRelationTablePageIdColumnName() !== null) {
            $row[$columnMap->getRelationTablePageIdColumnName()] = $this->determineStoragePageIdForNewRecord();
        }
        $relationTableMatchFields = $columnMap->getRelationTableMatchFields();
        if (is_array($relationTableMatchFields)) {
            $row = array_merge($relationTableMatchFields, $row);
        }
        $relationTableInsertFields = $columnMap->getRelationTableInsertFields();
        if (is_array($relationTableInsertFields)) {
            $row = array_merge($relationTableInsertFields, $row);
        }
        $res = $this->storageBackend->addRow($relationTableName, $row, true);
        return $res;
    }

    /**
     * Updates mm-relation in a relation table
     *
     * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object The related object
     * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject The parent object
     * @param string $propertyName The name of the parent object's property where the related objects are stored in
     * @param int $sortingPosition Defaults to NULL
     * @return bool TRUE if update was successfully
     */
    protected function updateRelationInRelationTable(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object, \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject, $propertyName, $sortingPosition = 0)
    {
        $dataMap = $this->dataMapFactory->buildDataMap(get_class($parentObject));
        $columnMap = $dataMap->getColumnMap($propertyName);
        $row = [
            $columnMap->getParentKeyFieldName() => (int)$parentObject->getUid(),
            $columnMap->getChildKeyFieldName() => (int)$object->getUid(),
            $columnMap->getChildSortByFieldName() => (int)$sortingPosition
        ];
        $relationTableName = $columnMap->getRelationTableName();
        $relationTableMatchFields = $columnMap->getRelationTableMatchFields();
        if (is_array($relationTableMatchFields)) {
            $row = array_merge($relationTableMatchFields, $row);
        }
        $res = $this->storageBackend->updateRelationTableRow(
            $relationTableName,
            $row
        );
        return $res;
    }

    /**
     * Delete all mm-relations of a parent from a relation table
     *
     * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject The parent object
     * @param string $parentPropertyName The name of the parent object's property where the related objects are stored in
     * @return bool TRUE if delete was successfully
     */
    protected function deleteAllRelationsFromRelationtable(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject, $parentPropertyName)
    {
        $dataMap = $this->dataMapFactory->buildDataMap(get_class($parentObject));
        $columnMap = $dataMap->getColumnMap($parentPropertyName);
        $relationTableName = $columnMap->getRelationTableName();
        $relationMatchFields = [
            $columnMap->getParentKeyFieldName() => (int)$parentObject->getUid()
        ];
        $relationTableMatchFields = $columnMap->getRelationTableMatchFields();
        if (is_array($relationTableMatchFields)) {
            $relationMatchFields = array_merge($relationTableMatchFields, $relationMatchFields);
        }
        $res = $this->storageBackend->removeRow($relationTableName, $relationMatchFields, false);
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
    protected function deleteRelationFromRelationtable(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $relatedObject, \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject, $parentPropertyName)
    {
        $dataMap = $this->dataMapFactory->buildDataMap(get_class($parentObject));
        $columnMap = $dataMap->getColumnMap($parentPropertyName);
        $relationTableName = $columnMap->getRelationTableName();
        $relationMatchFields = [
            $columnMap->getParentKeyFieldName() => (int)$parentObject->getUid(),
            $columnMap->getChildKeyFieldName() => (int)$relatedObject->getUid()
        ];
        $relationTableMatchFields = $columnMap->getRelationTableMatchFields();
        if (is_array($relationTableMatchFields)) {
            $relationMatchFields = array_merge($relationTableMatchFields, $relationMatchFields);
        }
        $res = $this->storageBackend->removeRow($relationTableName, $relationMatchFields, false);
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
    protected function fetchMaxSortingFromParentTable(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject, $parentPropertyName)
    {
        $parentDataMap = $this->dataMapFactory->buildDataMap(get_class($parentObject));
        $parentColumnMap = $parentDataMap->getColumnMap($parentPropertyName);
        if ($parentColumnMap->getTypeOfRelation() === \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::RELATION_HAS_MANY) {
            $tableName = $parentColumnMap->getChildTableName();
            $sortByFieldName = $parentColumnMap->getChildSortByFieldName();

            if (empty($sortByFieldName)) {
                return false;
            }
            $matchFields = [];
            $parentKeyFieldName = $parentColumnMap->getParentKeyFieldName();
            if ($parentKeyFieldName !== null) {
                $matchFields[$parentKeyFieldName] = $parentObject->getUid();
                $parentTableFieldName = $parentColumnMap->getParentTableFieldName();
                if ($parentTableFieldName !== null) {
                    $matchFields[$parentTableFieldName] = $parentDataMap->getTableName();
                }
            }

            if (empty($matchFields)) {
                return false;
            }
        } elseif ($parentColumnMap->getTypeOfRelation() === \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
            $tableName = $parentColumnMap->getRelationTableName();
            $sortByFieldName = $parentColumnMap->getChildSortByFieldName();

            $matchFields = [
                $parentColumnMap->getParentKeyFieldName() => (int)$parentObject->getUid()
            ];

            $relationTableMatchFields = $parentColumnMap->getRelationTableMatchFields();
            if (is_array($relationTableMatchFields)) {
                $matchFields = array_merge($relationTableMatchFields, $matchFields);
            }
        } else {
            throw new \TYPO3\CMS\Extbase\Persistence\Exception\IllegalRelationTypeException('Unexpected parent column relation type: ' . $parentColumnMap->getTypeOfRelation(), 1345368106);
        }

        $result = $this->storageBackend->getMaxValueFromTable(
            $tableName,
            $matchFields,
            $sortByFieldName
        );
        return $result;
    }

    /**
     * Updates a given object in the storage
     *
     * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object The object to be updated
     * @param array $row Row to be stored
     * @return bool
     */
    protected function updateObject(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object, array $row)
    {
        $dataMap = $this->dataMapFactory->buildDataMap(get_class($object));
        $this->addCommonFieldsToRow($object, $row);
        $row['uid'] = $object->getUid();
        if ($dataMap->getLanguageIdColumnName() !== null) {
            $row[$dataMap->getLanguageIdColumnName()] = (int)$object->_getProperty('_languageUid');
            if ($object->_getProperty('_localizedUid') !== null) {
                $row['uid'] = $object->_getProperty('_localizedUid');
            }
        }
        $res = $this->storageBackend->updateRow($dataMap->getTableName(), $row);
        if ($res === true) {
            $this->emitAfterUpdateObjectSignal($object);
        }
        $frameworkConfiguration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        if ($frameworkConfiguration['persistence']['updateReferenceIndex'] === '1') {
            $this->referenceIndex->updateRefIndexTable($dataMap->getTableName(), $row['uid']);
        }
        return $res;
    }

    /**
     * Emits a signal after an object was updated in storage
     *
     * @param DomainObjectInterface $object
     */
    protected function emitAfterUpdateObjectSignal(DomainObjectInterface $object)
    {
        $this->signalSlotDispatcher->dispatch(__CLASS__, 'afterUpdateObject', [$object]);
    }

    /**
     * Emits a signal after an object was persisted
     *
     * @param DomainObjectInterface $object
     */
    protected function emitAfterPersistObjectSignal(DomainObjectInterface $object)
    {
        $this->signalSlotDispatcher->dispatch(__CLASS__, 'afterPersistObject', [$object]);
    }

    /**
     * Adds common databse fields to a row
     *
     * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object
     * @param array &$row
     */
    protected function addCommonFieldsToRow(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object, array &$row)
    {
        $dataMap = $this->dataMapFactory->buildDataMap(get_class($object));
        $this->addCommonDateFieldsToRow($object, $row);
        if ($dataMap->getRecordTypeColumnName() !== null && $dataMap->getRecordType() !== null) {
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
     */
    protected function addCommonDateFieldsToRow(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object, array &$row)
    {
        $dataMap = $this->dataMapFactory->buildDataMap(get_class($object));
        if ($object->_isNew() && $dataMap->getCreationDateColumnName() !== null) {
            $row[$dataMap->getCreationDateColumnName()] = $GLOBALS['EXEC_TIME'];
        }
        if ($dataMap->getModificationDateColumnName() !== null) {
            $row[$dataMap->getModificationDateColumnName()] = $GLOBALS['EXEC_TIME'];
        }
    }

    /**
     * Iterate over deleted aggregate root objects and process them
     */
    protected function processDeletedObjects()
    {
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
     * @param bool $markAsDeleted Whether to just flag the row deleted (default) or really delete it
     */
    protected function removeEntity(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object, $markAsDeleted = true)
    {
        $dataMap = $this->dataMapFactory->buildDataMap(get_class($object));
        $tableName = $dataMap->getTableName();
        if ($markAsDeleted === true && $dataMap->getDeletedFlagColumnName() !== null) {
            $deletedColumnName = $dataMap->getDeletedFlagColumnName();
            $row = [
                'uid' => $object->getUid(),
                $deletedColumnName => 1
            ];
            $this->addCommonDateFieldsToRow($object, $row);
            $res = $this->storageBackend->updateRow($tableName, $row);
        } else {
            $res = $this->storageBackend->removeRow($tableName, ['uid' => $object->getUid()]);
        }
        if ($res === true) {
            $this->emitAfterRemoveObjectSignal($object);
        }
        $this->removeRelatedObjects($object);
        $frameworkConfiguration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        if ($frameworkConfiguration['persistence']['updateReferenceIndex'] === '1') {
            $this->referenceIndex->updateRefIndexTable($tableName, $object->getUid());
        }
    }

    /**
     * Emits a signal after an object was removed from storage
     *
     * @param DomainObjectInterface $object
     */
    protected function emitAfterRemoveObjectSignal(DomainObjectInterface $object)
    {
        $this->signalSlotDispatcher->dispatch(__CLASS__, 'afterRemoveObject', [$object]);
    }

    /**
     * Remove related objects
     *
     * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object The object to scanned for related objects
     */
    protected function removeRelatedObjects(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object)
    {
        $className = get_class($object);
        $dataMap = $this->dataMapFactory->buildDataMap($className);
        $classSchema = $this->reflectionService->getClassSchema($className);
        $properties = $object->_getProperties();
        foreach ($properties as $propertyName => $propertyValue) {
            $columnMap = $dataMap->getColumnMap($propertyName);
            if ($columnMap === null) {
                continue;
            }
            $propertyMetaData = $classSchema->getProperty($propertyName);
            if ($propertyMetaData['annotations']['cascade'] === 'remove') {
                if ($columnMap->getTypeOfRelation() === \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::RELATION_HAS_MANY) {
                    foreach ($propertyValue as $containedObject) {
                        $this->removeEntity($containedObject);
                    }
                } elseif ($propertyValue instanceof \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface) {
                    $this->removeEntity($propertyValue);
                }
            } elseif ($dataMap->getDeletedFlagColumnName() === null
                && $columnMap->getTypeOfRelation() === ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY
            ) {
                $this->deleteAllRelationsFromRelationtable($object, $propertyName);
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
    protected function determineStoragePageIdForNewRecord(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object = null)
    {
        $frameworkConfiguration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        if ($object !== null) {
            if (\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertyGettable($object, 'pid')) {
                $pid = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($object, 'pid');
                if (isset($pid)) {
                    return (int)$pid;
                }
            }
            $className = get_class($object);
            if (isset($frameworkConfiguration['persistence']['classes'][$className]) && !empty($frameworkConfiguration['persistence']['classes'][$className]['newRecordStoragePid'])) {
                return (int)$frameworkConfiguration['persistence']['classes'][$className]['newRecordStoragePid'];
            }
        }
        $storagePidList = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $frameworkConfiguration['persistence']['storagePid']);
        return (int)$storagePidList[0];
    }

    /**
     * Returns a plain value
     *
     * i.e. objects are flattened out if possible.
     * Checks explicitly for null values as DataMapper's getPlainValue would convert this to 'NULL'
     *
     * @param mixed $input The value that will be converted
     * @param ColumnMap $columnMap Optional column map for retrieving the date storage format
     * @return int|string|null
     */
    protected function getPlainValue($input, ColumnMap $columnMap = null)
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $dataMapper = $objectManager->get(DataMapper::class);
        return $input !== null
            ? $dataMapper->getPlainValue($input, $columnMap)
            : null;
    }
}
