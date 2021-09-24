<?php

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

namespace TYPO3\CMS\Extbase\Persistence\Generic;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\DomainObject\AbstractValueObject;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Event\Persistence\EntityAddedToPersistenceEvent;
use TYPO3\CMS\Extbase\Event\Persistence\EntityFinalizedAfterPersistenceEvent;
use TYPO3\CMS\Extbase\Event\Persistence\EntityPersistedEvent;
use TYPO3\CMS\Extbase\Event\Persistence\EntityRemovedFromPersistenceEvent;
use TYPO3\CMS\Extbase\Event\Persistence\EntityUpdatedInPersistenceEvent;
use TYPO3\CMS\Extbase\Event\Persistence\ModifyQueryBeforeFetchingObjectDataEvent;
use TYPO3\CMS\Extbase\Event\Persistence\ModifyResultAfterFetchingObjectDataEvent;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalRelationTypeException;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\ObjectMonitoringInterface;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;

/**
 * A persistence backend. This backend maps objects to the relational model of the storage backend.
 * It persists all added, removed and changed objects.
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class Backend implements BackendInterface, SingletonInterface
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
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * Constructs the backend
     *
     * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
     * @param Session $session
     * @param \TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService
     * @param \TYPO3\CMS\Extbase\Persistence\Generic\Storage\BackendInterface $storageBackend
     * @param \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory $dataMapFactory
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        ConfigurationManagerInterface $configurationManager,
        Session $session,
        ReflectionService $reflectionService,
        \TYPO3\CMS\Extbase\Persistence\Generic\Storage\BackendInterface $storageBackend,
        DataMapFactory $dataMapFactory,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->configurationManager = $configurationManager;
        $this->session = $session;
        $this->reflectionService = $reflectionService;
        $this->storageBackend = $storageBackend;
        $this->dataMapFactory = $dataMapFactory;
        $this->eventDispatcher = $eventDispatcher;

        $this->referenceIndex = GeneralUtility::makeInstance(ReferenceIndex::class);
        $this->aggregateRootObjects = new ObjectStorage();
        $this->deletedEntities = new ObjectStorage();
        $this->changedEntities = new ObjectStorage();
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface $persistenceManager
     */
    public function setPersistenceManager(PersistenceManagerInterface $persistenceManager)
    {
        $this->persistenceManager = $persistenceManager;
    }

    /**
     * Returns the number of records matching the query.
     *
     * @param \TYPO3\CMS\Extbase\Persistence\QueryInterface $query
     * @return int
     */
    public function getObjectCountByQuery(QueryInterface $query)
    {
        return $this->storageBackend->getObjectCountByQuery($query);
    }

    /**
     * Returns the object data matching the $query.
     *
     * @param \TYPO3\CMS\Extbase\Persistence\QueryInterface $query
     * @return array
     */
    public function getObjectDataByQuery(QueryInterface $query)
    {
        $event = new ModifyQueryBeforeFetchingObjectDataEvent($query);
        $this->eventDispatcher->dispatch($event);
        $query = $event->getQuery();
        $result = $this->storageBackend->getObjectDataByQuery($query);
        $event = new ModifyResultAfterFetchingObjectDataEvent($query, $result);
        $this->eventDispatcher->dispatch($event);
        return $event->getResult();
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
        if ($object instanceof LazyLoadingProxy) {
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
    public function setAggregateRootObjects(ObjectStorage $objects)
    {
        $this->aggregateRootObjects = $objects;
    }

    /**
     * Sets the changed objects
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $entities
     */
    public function setChangedEntities(ObjectStorage $entities)
    {
        $this->changedEntities = $entities;
    }

    /**
     * Sets the deleted objects
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $entities
     */
    public function setDeletedEntities(ObjectStorage $entities)
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
        $this->visitedDuringPersistence = new ObjectStorage();
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
     * @param DomainObjectInterface $object The object to be inserted
     */
    protected function persistObject(DomainObjectInterface $object)
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
            if ($propertyValue instanceof ObjectStorage) {
                $cleanProperty = $object->_getCleanProperty($propertyName);
                // objectstorage needs to be persisted if the object is new, the objectstorage is dirty, meaning it has
                // been changed after initial build, or an empty objectstorage is present and the cleanstate objectstorage
                // has childelements, meaning all elements should been removed from the objectstorage
                if ($object->_isNew() || $propertyValue->_isDirty() || ($propertyValue->count() === 0 && $cleanProperty && $cleanProperty->count() > 0)) {
                    $this->persistObjectStorage($propertyValue, $object, $propertyName, $row);
                    $propertyValue->_memorizeCleanState();
                }
                foreach ($propertyValue as $containedObject) {
                    if ($containedObject instanceof DomainObjectInterface) {
                        $queue[] = $containedObject;
                    }
                }
            } elseif ($propertyValue instanceof DomainObjectInterface
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
        $this->eventDispatcher->dispatch(new EntityPersistedEvent($object));
    }

    /**
     * Checks, if the property value is lazy loaded and was not initialized
     *
     * @param mixed $propertyValue The property value
     * @return bool
     */
    protected function propertyValueIsLazyLoaded($propertyValue)
    {
        if ($propertyValue instanceof LazyLoadingProxy) {
            return true;
        }
        if ($propertyValue instanceof LazyObjectStorage) {
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
     * @param DomainObjectInterface $parentObject The parent object. One of the properties holds the object storage.
     * @param string $propertyName The name of the property holding the object storage.
     * @param array $row The row array of the parent object to be persisted. It's passed by reference and gets filled with either a comma separated list of uids (csv) or the number of contained objects.
     */
    protected function persistObjectStorage(ObjectStorage $objectStorage, DomainObjectInterface $parentObject, $propertyName, array &$row)
    {
        $className = get_class($parentObject);
        $dataMapper = GeneralUtility::makeInstance(DataMapper::class);
        $columnMap = $this->dataMapFactory->buildDataMap($className)->getColumnMap($propertyName);
        $property = $this->reflectionService->getClassSchema($className)->getProperty($propertyName);
        foreach ($this->getRemovedChildObjects($parentObject, $propertyName) as $removedObject) {
            $this->detachObjectFromParentObject($removedObject, $parentObject, $propertyName);
            if ($columnMap->getTypeOfRelation() === ColumnMap::RELATION_HAS_MANY && $property->getCascadeValue() === 'remove') {
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
     * @param DomainObjectInterface $object The object
     * @param string $propertyName
     * @return array An array of removed objects
     */
    protected function getRemovedChildObjects(DomainObjectInterface $object, $propertyName)
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
     * @param DomainObjectInterface $object
     * @param DomainObjectInterface $parentObject
     * @param string $parentPropertyName
     * @param int $sortingPosition
     */
    protected function attachObjectToParentObject(DomainObjectInterface $object, DomainObjectInterface $parentObject, $parentPropertyName, $sortingPosition = 0)
    {
        $parentDataMap = $this->dataMapFactory->buildDataMap(get_class($parentObject));

        $parentColumnMap = $parentDataMap->getColumnMap($parentPropertyName);
        if ($parentColumnMap->getTypeOfRelation() === ColumnMap::RELATION_HAS_MANY) {
            $this->attachObjectToParentObjectRelationHasMany($object, $parentObject, $parentPropertyName, $sortingPosition);
        } elseif ($parentColumnMap->getTypeOfRelation() === ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
            $this->insertRelationInRelationtable($object, $parentObject, $parentPropertyName, $sortingPosition);
        }
    }

    /**
     * Updates the fields defining the relation between the object and the parent object.
     *
     * @param DomainObjectInterface $object
     * @param DomainObjectInterface $parentObject
     * @param string $parentPropertyName
     * @param int $sortingPosition
     */
    protected function updateRelationOfObjectToParentObject(DomainObjectInterface $object, DomainObjectInterface $parentObject, $parentPropertyName, $sortingPosition = 0)
    {
        $parentDataMap = $this->dataMapFactory->buildDataMap(get_class($parentObject));
        $parentColumnMap = $parentDataMap->getColumnMap($parentPropertyName);
        if ($parentColumnMap->getTypeOfRelation() === ColumnMap::RELATION_HAS_MANY) {
            $this->attachObjectToParentObjectRelationHasMany($object, $parentObject, $parentPropertyName, $sortingPosition);
        } elseif ($parentColumnMap->getTypeOfRelation() === ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
            $this->updateRelationInRelationTable($object, $parentObject, $parentPropertyName, $sortingPosition);
        }
    }

    /**
     * Updates fields defining the relation between the object and the parent object in relation has-many.
     *
     * @param DomainObjectInterface $object
     * @param DomainObjectInterface $parentObject
     * @param string $parentPropertyName
     * @param int $sortingPosition
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalRelationTypeException
     */
    protected function attachObjectToParentObjectRelationHasMany(DomainObjectInterface $object, DomainObjectInterface $parentObject, $parentPropertyName, $sortingPosition = 0)
    {
        $parentDataMap = $this->dataMapFactory->buildDataMap(get_class($parentObject));
        $parentColumnMap = $parentDataMap->getColumnMap($parentPropertyName);
        if ($parentColumnMap->getTypeOfRelation() !== ColumnMap::RELATION_HAS_MANY) {
            throw new IllegalRelationTypeException(
                'Parent column relation type is ' . $parentColumnMap->getTypeOfRelation() .
                ' but should be ' . ColumnMap::RELATION_HAS_MANY,
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
     * @param DomainObjectInterface $object
     * @param DomainObjectInterface $parentObject
     * @param string $parentPropertyName
     */
    protected function detachObjectFromParentObject(DomainObjectInterface $object, DomainObjectInterface $parentObject, $parentPropertyName)
    {
        $parentDataMap = $this->dataMapFactory->buildDataMap(get_class($parentObject));
        $parentColumnMap = $parentDataMap->getColumnMap($parentPropertyName);
        if ($parentColumnMap->getTypeOfRelation() === ColumnMap::RELATION_HAS_MANY) {
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
        } elseif ($parentColumnMap->getTypeOfRelation() === ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
            $this->deleteRelationFromRelationtable($object, $parentObject, $parentPropertyName);
        }
    }

    /**
     * Inserts an object in the storage backend
     *
     * @param DomainObjectInterface $object The object to be inserted in the storage
     * @param DomainObjectInterface $parentObject The parentobject.
     * @param string $parentPropertyName
     */
    protected function insertObject(DomainObjectInterface $object, DomainObjectInterface $parentObject = null, $parentPropertyName = '')
    {
        if ($object instanceof AbstractValueObject) {
            $result = $this->getUidOfAlreadyPersistedValueObject($object);
            if ($result !== null) {
                $object->_setProperty('uid', $result);
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
            $this->eventDispatcher->dispatch(new EntityAddedToPersistenceEvent($object));
        }
        $frameworkConfiguration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        if ($frameworkConfiguration['persistence']['updateReferenceIndex'] === '1') {
            $this->referenceIndex->updateRefIndexTable($dataMap->getTableName(), $uid);
        }
        $this->session->registerObject($object, $uid);
        if ((int)$uid >= 1) {
            $this->eventDispatcher->dispatch(new EntityFinalizedAfterPersistenceEvent($object));
        }
    }

    /**
     * Tests, if the given Value Object already exists in the storage backend and if so, it returns the uid.
     *
     * @param \TYPO3\CMS\Extbase\DomainObject\AbstractValueObject $object The object to be tested
     * @return int|null The matching uid if an object was found, else null
     */
    protected function getUidOfAlreadyPersistedValueObject(AbstractValueObject $object)
    {
        return $this->storageBackend->getUidOfAlreadyPersistedValueObject($object);
    }

    /**
     * Inserts mm-relation into a relation table
     *
     * @param DomainObjectInterface $object The related object
     * @param DomainObjectInterface $parentObject The parent object
     * @param string $propertyName The name of the parent object's property where the related objects are stored in
     * @param int $sortingPosition Defaults to NULL
     * @return int The uid of the inserted row
     */
    protected function insertRelationInRelationtable(DomainObjectInterface $object, DomainObjectInterface $parentObject, $propertyName, $sortingPosition = null)
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
            $columnMap->getChildSortByFieldName() => $sortingPosition !== null ? (int)$sortingPosition : 0,
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
     * @param DomainObjectInterface $object The related object
     * @param DomainObjectInterface $parentObject The parent object
     * @param string $propertyName The name of the parent object's property where the related objects are stored in
     * @param int $sortingPosition Defaults to NULL
     * @return bool TRUE if update was successfully
     */
    protected function updateRelationInRelationTable(DomainObjectInterface $object, DomainObjectInterface $parentObject, $propertyName, $sortingPosition = 0)
    {
        $dataMap = $this->dataMapFactory->buildDataMap(get_class($parentObject));
        $columnMap = $dataMap->getColumnMap($propertyName);
        $row = [
            $columnMap->getParentKeyFieldName() => (int)$parentObject->getUid(),
            $columnMap->getChildKeyFieldName() => (int)$object->getUid(),
            $columnMap->getChildSortByFieldName() => (int)$sortingPosition,
        ];
        $relationTableName = $columnMap->getRelationTableName();
        $relationTableMatchFields = $columnMap->getRelationTableMatchFields();
        if (is_array($relationTableMatchFields)) {
            $row = array_merge($relationTableMatchFields, $row);
        }
        $this->storageBackend->updateRelationTableRow(
            $relationTableName,
            $row
        );
        return true;
    }

    /**
     * Delete all mm-relations of a parent from a relation table
     *
     * @param DomainObjectInterface $parentObject The parent object
     * @param string $parentPropertyName The name of the parent object's property where the related objects are stored in
     * @return bool TRUE if delete was successfully
     */
    protected function deleteAllRelationsFromRelationtable(DomainObjectInterface $parentObject, $parentPropertyName)
    {
        $dataMap = $this->dataMapFactory->buildDataMap(get_class($parentObject));
        $columnMap = $dataMap->getColumnMap($parentPropertyName);
        $relationTableName = $columnMap->getRelationTableName();
        $relationMatchFields = [
            $columnMap->getParentKeyFieldName() => (int)$parentObject->getUid(),
        ];
        $relationTableMatchFields = $columnMap->getRelationTableMatchFields();
        if (is_array($relationTableMatchFields)) {
            $relationMatchFields = array_merge($relationTableMatchFields, $relationMatchFields);
        }
        $this->storageBackend->removeRow($relationTableName, $relationMatchFields, false);
        return true;
    }

    /**
     * Delete an mm-relation from a relation table
     *
     * @param DomainObjectInterface $relatedObject The related object
     * @param DomainObjectInterface $parentObject The parent object
     * @param string $parentPropertyName The name of the parent object's property where the related objects are stored in
     * @return bool
     */
    protected function deleteRelationFromRelationtable(DomainObjectInterface $relatedObject, DomainObjectInterface $parentObject, $parentPropertyName)
    {
        $dataMap = $this->dataMapFactory->buildDataMap(get_class($parentObject));
        $columnMap = $dataMap->getColumnMap($parentPropertyName);
        $relationTableName = $columnMap->getRelationTableName();
        $relationMatchFields = [
            $columnMap->getParentKeyFieldName() => (int)$parentObject->getUid(),
            $columnMap->getChildKeyFieldName() => (int)$relatedObject->getUid(),
        ];
        $relationTableMatchFields = $columnMap->getRelationTableMatchFields();
        if (is_array($relationTableMatchFields)) {
            $relationMatchFields = array_merge($relationTableMatchFields, $relationMatchFields);
        }
        $this->storageBackend->removeRow($relationTableName, $relationMatchFields, false);
        return true;
    }

    /**
     * Updates a given object in the storage
     *
     * @param DomainObjectInterface $object The object to be updated
     * @param array $row Row to be stored
     * @return bool
     */
    protected function updateObject(DomainObjectInterface $object, array $row)
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
        $this->storageBackend->updateRow($dataMap->getTableName(), $row);
        $this->eventDispatcher->dispatch(new EntityUpdatedInPersistenceEvent($object));

        $frameworkConfiguration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        if ($frameworkConfiguration['persistence']['updateReferenceIndex'] === '1') {
            $this->referenceIndex->updateRefIndexTable($dataMap->getTableName(), $row['uid']);
        }
        return true;
    }

    /**
     * Adds common database fields to a row
     *
     * @param DomainObjectInterface $object
     * @param array $row
     */
    protected function addCommonFieldsToRow(DomainObjectInterface $object, array &$row)
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
     * Adjusts the common date fields of the given row to the current time
     *
     * @param DomainObjectInterface $object
     * @param array $row The row to be updated
     */
    protected function addCommonDateFieldsToRow(DomainObjectInterface $object, array &$row)
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
        $this->deletedEntities = new ObjectStorage();
    }

    /**
     * Deletes an object
     *
     * @param DomainObjectInterface $object The object to be removed from the storage
     * @param bool $markAsDeleted Whether to just flag the row deleted (default) or really delete it
     */
    protected function removeEntity(DomainObjectInterface $object, $markAsDeleted = true)
    {
        $dataMap = $this->dataMapFactory->buildDataMap(get_class($object));
        $tableName = $dataMap->getTableName();
        if ($markAsDeleted === true && $dataMap->getDeletedFlagColumnName() !== null) {
            $deletedColumnName = $dataMap->getDeletedFlagColumnName();
            $row = [
                'uid' => $object->getUid(),
                $deletedColumnName => 1,
            ];
            $this->addCommonDateFieldsToRow($object, $row);
            $this->storageBackend->updateRow($tableName, $row);
        } else {
            $this->storageBackend->removeRow($tableName, ['uid' => $object->getUid()]);
        }
        $this->eventDispatcher->dispatch(new EntityRemovedFromPersistenceEvent($object));

        $this->removeRelatedObjects($object);
        $frameworkConfiguration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        if ($frameworkConfiguration['persistence']['updateReferenceIndex'] === '1') {
            $this->referenceIndex->updateRefIndexTable($tableName, $object->getUid());
        }
    }

    /**
     * Remove related objects
     *
     * @param DomainObjectInterface $object The object to scanned for related objects
     */
    protected function removeRelatedObjects(DomainObjectInterface $object)
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
            $property = $classSchema->getProperty($propertyName);
            if ($property->getCascadeValue() === 'remove') {
                if ($columnMap->getTypeOfRelation() === ColumnMap::RELATION_HAS_MANY) {
                    foreach ($propertyValue as $containedObject) {
                        $this->removeEntity($containedObject);
                    }
                } elseif ($propertyValue instanceof DomainObjectInterface) {
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
     * @param DomainObjectInterface|null $object
     * @return int the storage Page ID where the object should be stored
     */
    protected function determineStoragePageIdForNewRecord(DomainObjectInterface $object = null)
    {
        $frameworkConfiguration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        if ($object !== null) {
            if (ObjectAccess::isPropertyGettable($object, 'pid')) {
                $pid = ObjectAccess::getProperty($object, 'pid');
                if (isset($pid)) {
                    return (int)$pid;
                }
            }
            $className = get_class($object);
            // todo: decide what to do with this option.
            if (isset($frameworkConfiguration['persistence']['classes'][$className]) && !empty($frameworkConfiguration['persistence']['classes'][$className]['newRecordStoragePid'])) {
                return (int)$frameworkConfiguration['persistence']['classes'][$className]['newRecordStoragePid'];
            }
        }
        $storagePidList = GeneralUtility::intExplode(',', $frameworkConfiguration['persistence']['storagePid']);
        return (int)$storagePidList[0];
    }

    /**
     * Returns a plain value
     *
     * i.e. objects are flattened out if possible.
     * Checks explicitly for null values as DataMapper's getPlainValue would convert this to 'NULL'
     *
     * @param mixed $input The value that will be converted
     * @param ColumnMap|null $columnMap Optional column map for retrieving the date storage format
     * @return int|string|null
     */
    protected function getPlainValue($input, ColumnMap $columnMap = null)
    {
        return $input !== null
            ? GeneralUtility::makeInstance(DataMapper::class)->getPlainValue($input, $columnMap)
            : null;
    }
}
