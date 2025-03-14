<?php

declare(strict_types=1);

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
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\DataHandling\TableColumnType;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Configuration\Exception\NoServerRequestGivenException;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\DomainObject\AbstractValueObject;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Event\Persistence\EntityAddedToPersistenceEvent;
use TYPO3\CMS\Extbase\Event\Persistence\EntityFinalizedAfterPersistenceEvent;
use TYPO3\CMS\Extbase\Event\Persistence\EntityPersistedEvent;
use TYPO3\CMS\Extbase\Event\Persistence\EntityRemovedFromPersistenceEvent;
use TYPO3\CMS\Extbase\Event\Persistence\EntityUpdatedInPersistenceEvent;
use TYPO3\CMS\Extbase\Event\Persistence\ModifyQueryBeforeFetchingObjectCountEvent;
use TYPO3\CMS\Extbase\Event\Persistence\ModifyQueryBeforeFetchingObjectDataEvent;
use TYPO3\CMS\Extbase\Event\Persistence\ModifyResultAfterFetchingObjectCountEvent;
use TYPO3\CMS\Extbase\Event\Persistence\ModifyResultAfterFetchingObjectDataEvent;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalRelationTypeException;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap\Relation;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\Generic\Storage\BackendInterface as StorageBackendInterface;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Reflection\ClassSchema\Property;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;

/**
 * A persistence backend. This backend maps objects to the relational model of the storage backend.
 * It persists all added, removed and changed objects.
 *
 * Warning: This is a stateful-shared service!
 *
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
#[Autoconfigure(public: true)]
class Backend implements BackendInterface
{
    protected PersistenceManagerInterface $persistenceManager;
    protected ObjectStorage $aggregateRootObjects;
    protected ObjectStorage $deletedEntities;
    protected ObjectStorage $changedEntities;
    protected ObjectStorage $visitedDuringPersistence;

    public function __construct(
        protected readonly ConfigurationManagerInterface $configurationManager,
        protected readonly Session $session,
        protected readonly ReflectionService $reflectionService,
        protected readonly StorageBackendInterface $storageBackend,
        protected readonly DataMapFactory $dataMapFactory,
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly ReferenceIndex $referenceIndex,
        protected readonly TcaSchemaFactory $tcaSchemaFactory,
        protected readonly Features $features,
    ) {
        $this->aggregateRootObjects = new ObjectStorage();
        $this->deletedEntities = new ObjectStorage();
        $this->changedEntities = new ObjectStorage();
    }

    public function setPersistenceManager(PersistenceManagerInterface $persistenceManager): void
    {
        $this->persistenceManager = $persistenceManager;
    }

    /**
     * Returns the number of records matching the query.
     *
     * @return int
     */
    public function getObjectCountByQuery(QueryInterface $query)
    {
        $event = new ModifyQueryBeforeFetchingObjectCountEvent($query);
        $this->eventDispatcher->dispatch($event);
        $query = $event->getQuery();
        $result = $this->storageBackend->getObjectCountByQuery($query);
        $event = new ModifyResultAfterFetchingObjectCountEvent($query, $result);
        $this->eventDispatcher->dispatch($event);
        return $event->getResult();
    }

    /**
     * Returns the object data matching the $query.
     *
     * @return list<array<string,mixed>>
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
        }

        return is_object($object) ? $this->session->getIdentifierByObject($object) : null;
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
        // This allows to fetch IDs for languages for default language AND language IDs
        // This is especially important when using the PropertyMapper of the Extbase MVC part to get
        // an object of the translated version of the incoming ID of a record.
        $languageAspect = $query->getQuerySettings()->getLanguageAspect();
        $languageAspect = new LanguageAspect(
            $languageAspect->getId(),
            $languageAspect->getContentId(),
            $languageAspect->getOverlayType() === LanguageAspect::OVERLAYS_OFF ? LanguageAspect::OVERLAYS_ON_WITH_FLOATING : $languageAspect->getOverlayType(),
            $languageAspect->getFallbackChain()
        );
        $query->getQuerySettings()->setLanguageAspect($languageAspect);
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
     */
    public function setAggregateRootObjects(ObjectStorage $objects)
    {
        $this->aggregateRootObjects = $objects;
    }

    /**
     * Sets the changed objects
     */
    public function setChangedEntities(ObjectStorage $entities)
    {
        $this->changedEntities = $entities;
    }

    /**
     * Sets the deleted objects
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
    protected function persistObjects(): void
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
     */
    protected function persistObject(DomainObjectInterface $object): void
    {
        if (isset($this->visitedDuringPersistence[$object])) {
            return;
        }
        $row = [];
        $queue = [];
        $className = get_class($object);
        $dataMap = $this->dataMapFactory->buildDataMap($className);
        $classSchema = $this->reflectionService->getClassSchema($className);
        foreach ($classSchema->getDomainObjectProperties() as $property) {
            $propertyName = $property->getName();
            if (!$dataMap->isPersistableProperty($propertyName)) {
                continue;
            }
            $propertyValue = $object->_getProperty($propertyName);
            if ($this->propertyValueIsLazyLoaded($propertyValue)) {
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
            } elseif ($propertyValue instanceof DomainObjectInterface) {
                if ($object->_isDirty($propertyName)) {
                    if ($propertyValue->_isNew()) {
                        $this->insertObject($propertyValue, $object, $propertyName);
                    }
                    $row[$columnMap->columnName] = $this->getPlainValue($propertyValue, null, $property);
                }
                $queue[] = $propertyValue;
            } elseif ($object->_isNew() || $object->_isDirty($propertyName)) {
                $row[$columnMap->columnName] = $this->getPlainValue($propertyValue, $columnMap, $property);
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
     */
    protected function propertyValueIsLazyLoaded(mixed $propertyValue): bool
    {
        if ($propertyValue instanceof LazyLoadingProxy) {
            return true;
        }
        if (($propertyValue instanceof LazyObjectStorage) && $propertyValue->isInitialized() === false) {
            return true;
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
    protected function persistObjectStorage(
        ObjectStorage $objectStorage,
        DomainObjectInterface $parentObject,
        string $propertyName,
        array &$row
    ): void {
        $className = get_class($parentObject);
        $dataMapper = GeneralUtility::makeInstance(DataMapper::class);
        $columnMap = $this->dataMapFactory->buildDataMap($className)->getColumnMap($propertyName);
        $property = $this->reflectionService->getClassSchema($className)->getProperty($propertyName);
        foreach ($this->getRemovedChildObjects($parentObject, $propertyName) as $removedObject) {
            $this->detachObjectFromParentObject($removedObject, $parentObject, $propertyName);
            if ($columnMap->typeOfRelation === Relation::HAS_MANY && $property->getCascadeValue() === 'remove') {
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
                $this->insertObject($object, $parentObject);
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

        if ($columnMap->parentKeyFieldName === null) {
            $row[$columnMap->columnName] = implode(',', $currentUids);
        } else {
            $row[$columnMap->columnName] = $dataMapper->countRelated($parentObject, $propertyName);
        }
    }

    /**
     * Returns the removed objects determined by a comparison of the clean property value
     * with the actual property value.
     */
    protected function getRemovedChildObjects(DomainObjectInterface $object, string $propertyName): array
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
     */
    protected function attachObjectToParentObject(
        DomainObjectInterface $object,
        DomainObjectInterface $parentObject,
        string $parentPropertyName,
        int $sortingPosition = 0
    ): void {
        $parentDataMap = $this->dataMapFactory->buildDataMap(get_class($parentObject));
        $parentColumnMap = $parentDataMap->getColumnMap($parentPropertyName);
        if ($parentColumnMap->typeOfRelation === Relation::HAS_MANY) {
            $this->attachObjectToParentObjectRelationHasMany($object, $parentObject, $parentPropertyName, $sortingPosition);
        } elseif ($parentColumnMap->typeOfRelation === Relation::HAS_AND_BELONGS_TO_MANY) {
            $this->insertRelationInRelationtable($object, $parentObject, $parentPropertyName, $sortingPosition);
        }
    }

    /**
     * Updates the fields defining the relation between the object and the parent object.
     */
    protected function updateRelationOfObjectToParentObject(
        DomainObjectInterface $object,
        DomainObjectInterface $parentObject,
        string $parentPropertyName,
        int $sortingPosition = 0
    ): void {
        $parentDataMap = $this->dataMapFactory->buildDataMap(get_class($parentObject));
        $parentColumnMap = $parentDataMap->getColumnMap($parentPropertyName);
        if ($parentColumnMap->typeOfRelation === Relation::HAS_MANY) {
            $this->attachObjectToParentObjectRelationHasMany($object, $parentObject, $parentPropertyName, $sortingPosition);
        } elseif ($parentColumnMap->typeOfRelation === Relation::HAS_AND_BELONGS_TO_MANY) {
            $this->updateRelationInRelationTable($object, $parentObject, $parentPropertyName, $sortingPosition);
        }
    }

    /**
     * Updates fields defining the relation between the object and the parent object in relation has-many.
     *
     * @throws IllegalRelationTypeException
     */
    protected function attachObjectToParentObjectRelationHasMany(
        DomainObjectInterface $object,
        DomainObjectInterface $parentObject,
        string $parentPropertyName,
        int $sortingPosition = 0
    ): void {
        $parentDataMap = $this->dataMapFactory->buildDataMap(get_class($parentObject));
        $parentColumnMap = $parentDataMap->getColumnMap($parentPropertyName);
        if ($parentColumnMap->typeOfRelation !== Relation::HAS_MANY) {
            throw new IllegalRelationTypeException(
                'Parent column relation type is ' . Relation::class . '::' . $parentColumnMap->typeOfRelation->name .
                ' but should be ' . Relation::class . '::' . Relation::HAS_MANY->name,
                1345368105
            );
        }
        $row = [];
        if ($parentColumnMap->parentKeyFieldName !== null) {
            $row[$parentColumnMap->parentKeyFieldName] = $parentObject->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID) ?: $parentObject->getUid();
            if ($parentColumnMap->parentTableFieldName !== null) {
                $row[$parentColumnMap->parentTableFieldName] = $parentDataMap->tableName;
            }
            $row = array_merge($parentColumnMap->relationTableMatchFields, $row);
        }
        $childSortByFieldName = $parentColumnMap->childSortByFieldName;
        if (!empty($childSortByFieldName)) {
            $row[$childSortByFieldName] = $sortingPosition;
        }
        if (!empty($row)) {
            $this->updateObject($object, $row);
        }
    }

    /**
     * Updates the fields defining the relation between the object and the parent object.
     */
    protected function detachObjectFromParentObject(
        DomainObjectInterface $object,
        DomainObjectInterface $parentObject,
        string $parentPropertyName
    ): void {
        $parentDataMap = $this->dataMapFactory->buildDataMap(get_class($parentObject));
        $parentColumnMap = $parentDataMap->getColumnMap($parentPropertyName);
        if ($parentColumnMap->typeOfRelation === Relation::HAS_MANY) {
            $row = [];
            if ($parentColumnMap->parentKeyFieldName !== null) {
                $row[$parentColumnMap->parentKeyFieldName] = 0;
                if ($parentColumnMap->parentTableFieldName !== null) {
                    $row[$parentColumnMap->parentTableFieldName] = '';
                }
                if (!empty($parentColumnMap->relationTableMatchFields)) {
                    $row = array_merge(array_fill_keys(array_keys($parentColumnMap->relationTableMatchFields), ''), $row);
                }
            }
            if (!empty($parentColumnMap->childSortByFieldName)) {
                $row[$parentColumnMap->childSortByFieldName] = 0;
            }
            if (!empty($row)) {
                $this->updateObject($object, $row);
            }
        } elseif ($parentColumnMap->typeOfRelation === Relation::HAS_AND_BELONGS_TO_MANY) {
            $this->deleteRelationFromRelationtable($object, $parentObject, $parentPropertyName);
        }
    }

    /**
     * Inserts an object in the storage backend
     */
    protected function insertObject(
        DomainObjectInterface $object,
        ?DomainObjectInterface $parentObject = null,
        string $parentPropertyName = ''
    ): void {
        if ($object instanceof AbstractValueObject) {
            $result = $this->getUidOfAlreadyPersistedValueObject($object);
            if ($result !== null) {
                $object->_setProperty(AbstractDomainObject::PROPERTY_UID, $result);
                return;
            }
        }
        $className = get_class($object);
        $dataMap = $this->dataMapFactory->buildDataMap($className);
        $row = [];
        $classSchema = $this->reflectionService->getClassSchema($className);
        foreach ($classSchema->getDomainObjectProperties() as $property) {
            $propertyName = $property->getName();
            if (!$dataMap->isPersistableProperty($propertyName)) {
                continue;
            }
            $propertyValue = $object->_getProperty($propertyName);
            if ($this->propertyValueIsLazyLoaded($propertyValue)) {
                continue;
            }
            $columnMap = $dataMap->getColumnMap($propertyName);
            if ($columnMap->typeOfRelation === Relation::HAS_ONE) {
                $row[$columnMap->columnName] = 0;
            } elseif ($columnMap->typeOfRelation !== Relation::NONE) {
                if ($columnMap->parentKeyFieldName === null) {
                    // CSV type relation
                    $row[$columnMap->columnName] = '';
                } else {
                    // MM type relation
                    $row[$columnMap->columnName] = 0;
                }
            } elseif ($propertyValue !== null) {
                $row[$columnMap->columnName] = $this->getPlainValue($propertyValue, $columnMap, $property);
            }
        }
        $this->addCommonFieldsToRow($object, $row);
        if ($dataMap->languageIdColumnName !== null && $object->_getProperty(AbstractDomainObject::PROPERTY_LANGUAGE_UID) === null) {
            $row[$dataMap->languageIdColumnName] = 0;
            $object->_setProperty(AbstractDomainObject::PROPERTY_LANGUAGE_UID, 0);
        }
        if ($dataMap->translationOriginColumnName !== null) {
            $row[$dataMap->translationOriginColumnName] = 0;
        }
        if ($dataMap->translationOriginDiffSourceName !== null) {
            $row[$dataMap->translationOriginDiffSourceName] = '';
        }
        if ($parentObject !== null && $parentPropertyName) {
            $parentColumnDataMap = $this->dataMapFactory->buildDataMap(get_class($parentObject))->getColumnMap($parentPropertyName);
            $row = array_merge($parentColumnDataMap->relationTableMatchFields, $row);
            if ($parentColumnDataMap->parentKeyFieldName !== null) {
                $row[$parentColumnDataMap->parentKeyFieldName] = (int)$parentObject->getUid();
            }
        }

        if ($parentObject) {
            // Ensure a nested object respects the storage PID for new records or inherits the storage PID from
            // the parent object.
            $storagePidForObject = $this->determineStoragePageIdForNewRecord($object);
            if ($storagePidForObject === 0) {
                $storagePidForObject = $parentObject->getPid() ?? 0;
            }
            $row['pid'] = $storagePidForObject;
        }

        $uid = $this->storageBackend->addRow($dataMap->tableName, $row);
        $localizedUid = $object->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID);
        $identifier = $uid . ($localizedUid ? '_' . $localizedUid : '');
        $object->_setProperty(AbstractDomainObject::PROPERTY_UID, $uid);
        $object->setPid((int)$row['pid']);
        if ($uid >= 1) {
            $this->eventDispatcher->dispatch(new EntityAddedToPersistenceEvent($object));
        }

        $this->referenceIndex->updateRefIndexTable($dataMap->tableName, $uid);
        $this->session->registerObject($object, $identifier);
        if ($uid >= 1) {
            $this->eventDispatcher->dispatch(new EntityFinalizedAfterPersistenceEvent($object));
        }
    }

    /**
     * Tests, if the given Value Object already exists in the storage backend and if so, it returns the uid.
     *
     * @return int|null The matching uid if an object was found, else null
     */
    protected function getUidOfAlreadyPersistedValueObject(AbstractValueObject $object): ?int
    {
        return $this->storageBackend->getUidOfAlreadyPersistedValueObject($object);
    }

    /**
     * Inserts mm-relation into a relation table
     *
     * @return int The uid of the inserted row
     */
    protected function insertRelationInRelationtable(
        DomainObjectInterface $object,
        DomainObjectInterface $parentObject,
        string $propertyName,
        ?int $sortingPosition = null
    ): int {
        $dataMap = $this->dataMapFactory->buildDataMap(get_class($parentObject));
        $columnMap = $dataMap->getColumnMap($propertyName);
        $parentUid = $parentObject->getUid();
        if ($parentObject->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID) !== null) {
            $parentUid = $parentObject->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID);
        }
        $row = [
            $columnMap->parentKeyFieldName => (int)$parentUid,
            $columnMap->childKeyFieldName => (int)$object->getUid(),
            $columnMap->childSortByFieldName => $sortingPosition ?? 0,
        ];
        $relationTableName = $columnMap->relationTableName;
        if ($this->tcaSchemaFactory->has($relationTableName)) {
            $row[AbstractDomainObject::PROPERTY_PID] = $this->determineStoragePageIdForNewRecord();
        }
        $row = array_merge($columnMap->relationTableMatchFields, $row);
        return $this->storageBackend->addRow($relationTableName, $row, true);
    }

    /**
     * Updates mm-relation in a relation table
     *
     * @return bool TRUE if update was successfully
     */
    protected function updateRelationInRelationTable(
        DomainObjectInterface $object,
        DomainObjectInterface $parentObject,
        string $propertyName,
        int $sortingPosition = 0
    ): bool {
        $dataMap = $this->dataMapFactory->buildDataMap(get_class($parentObject));
        $columnMap = $dataMap->getColumnMap($propertyName);
        $row = [
            $columnMap->parentKeyFieldName => (int)$parentObject->getUid(),
            $columnMap->childKeyFieldName => (int)$object->getUid(),
            $columnMap->childSortByFieldName => $sortingPosition,
        ];
        $relationTableName = $columnMap->relationTableName;
        $row = array_merge($columnMap->relationTableMatchFields, $row);
        $this->storageBackend->updateRelationTableRow($relationTableName, $row);
        return true;
    }

    /**
     * Delete all mm-relations of a parent from a relation table
     *
     * @return bool TRUE if delete was successfully
     */
    protected function deleteAllRelationsFromRelationtable(
        DomainObjectInterface $parentObject,
        string $parentPropertyName
    ): bool {
        $dataMap = $this->dataMapFactory->buildDataMap(get_class($parentObject));
        $columnMap = $dataMap->getColumnMap($parentPropertyName);
        $relationTableName = $columnMap->relationTableName;
        $relationMatchFields = [
            $columnMap->parentKeyFieldName => (int)$parentObject->getUid(),
        ];
        $relationMatchFields = array_merge($columnMap->relationTableMatchFields, $relationMatchFields);
        $this->storageBackend->removeRow($relationTableName, $relationMatchFields);
        return true;
    }

    /**
     * Delete an mm-relation from a relation table
     */
    protected function deleteRelationFromRelationtable(
        DomainObjectInterface $relatedObject,
        DomainObjectInterface $parentObject,
        string $parentPropertyName
    ): bool {
        $dataMap = $this->dataMapFactory->buildDataMap(get_class($parentObject));
        $columnMap = $dataMap->getColumnMap($parentPropertyName);
        $relationTableName = $columnMap->relationTableName;
        $relationMatchFields = [
            $columnMap->parentKeyFieldName => (int)$parentObject->getUid(),
            $columnMap->childKeyFieldName => (int)$relatedObject->getUid(),
        ];
        $relationMatchFields = array_merge($columnMap->relationTableMatchFields, $relationMatchFields);
        $this->storageBackend->removeRow($relationTableName, $relationMatchFields);
        return true;
    }

    /**
     * Updates a given object in the storage
     */
    protected function updateObject(DomainObjectInterface $object, array $row): void
    {
        $dataMap = $this->dataMapFactory->buildDataMap(get_class($object));
        $this->addCommonFieldsToRow($object, $row);
        $row['uid'] = $object->getUid();
        if ($dataMap->languageIdColumnName !== null) {
            $row[$dataMap->languageIdColumnName] = (int)$object->_getProperty(AbstractDomainObject::PROPERTY_LANGUAGE_UID);
            if ($object->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID) !== null) {
                $row['uid'] = $object->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID);
            }
        }
        $this->storageBackend->updateRow($dataMap->tableName, $row);
        $this->eventDispatcher->dispatch(new EntityUpdatedInPersistenceEvent($object));
        $this->referenceIndex->updateRefIndexTable($dataMap->tableName, (int)$row['uid']);
    }

    /**
     * Adds common database fields to a row
     */
    protected function addCommonFieldsToRow(DomainObjectInterface $object, array &$row): void
    {
        $dataMap = $this->dataMapFactory->buildDataMap(get_class($object));
        $this->addCommonDateFieldsToRow($object, $row);
        if ($dataMap->recordTypeColumnName !== null && $dataMap->recordType !== null) {
            $row[$dataMap->recordTypeColumnName] = $dataMap->recordType;
        }
        if ($object->_isNew() && !isset($row['pid'])) {
            $row['pid'] = $this->determineStoragePageIdForNewRecord($object);
        }
    }

    /**
     * Adjusts the common date fields of the given row to the current time
     */
    protected function addCommonDateFieldsToRow(DomainObjectInterface $object, array &$row): void
    {
        $dataMap = $this->dataMapFactory->buildDataMap(get_class($object));
        if ($object->_isNew() && $dataMap->creationDateColumnName !== null) {
            $row[$dataMap->creationDateColumnName] = $GLOBALS['EXEC_TIME'];
        }
        if ($dataMap->modificationDateColumnName !== null) {
            $row[$dataMap->modificationDateColumnName] = $GLOBALS['EXEC_TIME'];
        }
    }

    /**
     * Iterate over deleted aggregate root objects and process them
     */
    protected function processDeletedObjects(): void
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
     */
    protected function removeEntity(DomainObjectInterface $object, bool $markAsDeleted = true): void
    {
        $dataMap = $this->dataMapFactory->buildDataMap(get_class($object));
        if ($markAsDeleted === true && $dataMap->deletedFlagColumnName !== null) {
            $deletedColumnName = $dataMap->deletedFlagColumnName;
            $row = [
                'uid' => $object->getUid(),
                $deletedColumnName => 1,
            ];
            $this->addCommonDateFieldsToRow($object, $row);
            $this->storageBackend->updateRow($dataMap->tableName, $row);
        } else {
            $this->storageBackend->removeRow($dataMap->tableName, ['uid' => $object->getUid()]);
        }
        $this->eventDispatcher->dispatch(new EntityRemovedFromPersistenceEvent($object));

        $this->removeRelatedObjects($object);
        $this->referenceIndex->updateRefIndexTable($dataMap->tableName, $object->getUid());
    }

    /**
     * Remove related objects
     */
    protected function removeRelatedObjects(DomainObjectInterface $object): void
    {
        $className = get_class($object);
        $dataMap = $this->dataMapFactory->buildDataMap($className);
        $classSchema = $this->reflectionService->getClassSchema($className);
        foreach ($classSchema->getDomainObjectProperties() as $property) {
            $propertyName = $property->getName();
            $columnMap = $dataMap->getColumnMap($propertyName);
            if ($columnMap === null) {
                continue;
            }
            $propertyValue = $object->_getProperty($propertyName);
            if ($property->getCascadeValue() === 'remove') {
                if ($columnMap->typeOfRelation === Relation::HAS_MANY) {
                    foreach ($propertyValue as $containedObject) {
                        $this->removeEntity($containedObject);
                    }
                } elseif ($propertyValue instanceof DomainObjectInterface) {
                    $this->removeEntity($propertyValue);
                }
            } elseif ($dataMap->deletedFlagColumnName === null
                && $columnMap->typeOfRelation === Relation::HAS_AND_BELONGS_TO_MANY
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
     * @return int the storage Page ID where the object should be stored
     */
    protected function determineStoragePageIdForNewRecord(?DomainObjectInterface $object = null): int
    {
        $frameworkConfiguration = [];
        try {
            $frameworkConfiguration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        } catch (NoServerRequestGivenException) {
            // Fallback to empty array if ConfigurationManager has not been initialized with a Request.
            // This implies storagePid 0. This is a measure to specifically allow running the extbase
            // persistence layer without a Request, which may be useful in some CLI scenarios (and can
            // be convenient in tests) when no other code branches of extbase that have a hard dependency
            // to the Request (e.g. controllers / view) are used.
        }

        if ($object !== null) {
            if (ObjectAccess::isPropertyGettable($object, AbstractDomainObject::PROPERTY_PID)) {
                $pid = ObjectAccess::getProperty($object, AbstractDomainObject::PROPERTY_PID);
                if (isset($pid)) {
                    return (int)$pid;
                }
            }
            $className = get_class($object);
            if (isset($frameworkConfiguration['persistence']['classes'][$className]) && !empty($frameworkConfiguration['persistence']['classes'][$className]['newRecordStoragePid'])) {
                return (int)$frameworkConfiguration['persistence']['classes'][$className]['newRecordStoragePid'];
            }
        }
        $storagePidList = GeneralUtility::intExplode(',', (string)($frameworkConfiguration['persistence']['storagePid'] ?? '0'));
        return $storagePidList[0];
    }

    /**
     * Returns a plain value, i.e. objects are flattened out if possible.
     * Checks explicitly for null values as DataMapper's getPlainValue would convert this to 'NULL'.
     * For null values, the expected DB null value will be considered.
     *
     * @param mixed $input The value that will be converted
     * @param ColumnMap|null $columnMap Optional column map for retrieving the date storage format
     * @param Property|null $property The current property
     * @return int|string|null
     */
    protected function getPlainValue(mixed $input, ?ColumnMap $columnMap = null, ?Property $property = null)
    {
        if ($input !== null) {
            return GeneralUtility::makeInstance(DataMapper::class)->getPlainValue($input, $columnMap);
        }

        if ($this->features->isFeatureEnabled('extbase.consistentDateTimeHandling') &&
            $columnMap?->type === TableColumnType::DATETIME
        ) {
            return QueryHelper::transformDateTimeToDatabaseValue(
                null,
                $columnMap->isNullable,
                $columnMap->dateTimeFormat ?? 'datetime',
                $columnMap->dateTimeStorageFormat
            );
        }

        if ($property === null) {
            return null;
        }

        $className = $property->getPrimaryType()->getClassName() ?? null;

        if ($className === null) {
            return null;
        }

        // Nullable domain model property
        if (is_subclass_of($className, DomainObjectInterface::class)) {
            return 0;
        }
        // Nullable DateTime property (superseded by extbase.consistentDateTimeHandling above)
        // @todo remove in TYPO3 v15 when extbase.consistentDateTimeHandling will be enforced
        if ($columnMap && is_subclass_of($className, \DateTimeInterface::class)) {
            if ($columnMap->isNullable() && $property->isNullable()) {
                return null;
            }

            $datetimeFormats = QueryHelper::getDateTimeFormats();
            $dateFormat = $columnMap->dateTimeStorageFormat;
            if (!$dateFormat) {
                // Datetime property with no TCA dbType
                return 0;
            }
            if (isset($datetimeFormats[$dateFormat])) {
                // Datetime property with TCA dbType defined. Nullable fields will be saved with the empty value
                // (e.g. "00:00:00" for dbType = time) as well, but DataMapper will correctly map those values to null
                return $datetimeFormats[$dateFormat]['empty'];
            }
        }
        return null;
    }
}
