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

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception\TooDirtyException;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Keeps the original (clean) property values of Extbase entities and compares them with the current values
 * to find out whether an entity has been changed.
 *
 * The method names follow the concepts of the Doctrine ORM `UnitOfWork`: original entity data and entity
 * change set. The original data is kept per object in a `WeakMap` of this service, not in the entity. An entity
 * that has original data is managed by the persistence layer. An object without original data, for example a
 * clone or an unserialized entity, is not managed: It has no original data, no change set and is never dirty.
 * The original data is not cleared with the persistence state, it is gone when its object is garbage collected.
 *
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
#[Autoconfigure(public: true)]
final class CleanStateTracker
{
    /**
     * @var array<class-string, array{accessible: array<non-empty-string, \ReflectionProperty>, persistable: list<non-empty-string>}>
     */
    private array $propertiesByClass = [];

    /**
     * @var \WeakMap<object, array<non-empty-string, mixed>>
     */
    private \WeakMap $originalData;

    private ?\ReflectionProperty $storageModifiedProperty = null;

    public function __construct()
    {
        $this->originalData = new \WeakMap();
    }

    /**
     * Returns whether original data was registered for the object, which means that it is managed by the
     * persistence layer.
     */
    public function contains(object $entity): bool
    {
        if (!$entity instanceof AbstractDomainObject) {
            return false;
        }
        // An uninitialized lazy proxy can only have been created by the DataMapper, so it is managed
        if (new \ReflectionClass($entity)->isUninitializedLazyObject($entity)) {
            return true;
        }
        return isset($this->originalData[$this->getKey($entity)]);
    }

    /**
     * Registers the current values of all persistable properties of the entity as its original data. For an
     * `ObjectStorage` this resets its modified flag.
     */
    public function setOriginalEntityData(object $entity): void
    {
        if ($entity instanceof ObjectStorage) {
            if (!new \ReflectionClass($entity)->isUninitializedLazyObject($entity)) {
                $this->getStorageModifiedProperty()->setValue($entity, false);
            }
            return;
        }
        if (!$entity instanceof AbstractDomainObject) {
            return;
        }
        $originalData = [];
        foreach ($this->readProperties($entity) as $propertyName => $propertyValue) {
            $this->snapshotValue($originalData, $propertyName, $propertyValue);
        }
        $this->originalData[$this->getKey($entity)] = $originalData;
    }

    /**
     * Registers the current value of one property of the entity as its original value.
     *
     * @param non-empty-string $propertyName
     */
    public function setOriginalEntityProperty(object $entity, string $propertyName): void
    {
        if (!$entity instanceof AbstractDomainObject) {
            return;
        }
        $originalData = $this->getOriginalEntityData($entity);
        $this->snapshotValue($originalData, $propertyName, $this->readProperty($entity, $propertyName));
        $this->originalData[$this->getKey($entity)] = $originalData;
    }

    /**
     * Returns the original values of the entity, or an empty array if none were registered.
     *
     * @return array<non-empty-string, mixed>
     */
    public function getOriginalEntityData(object $entity): array
    {
        return $entity instanceof AbstractDomainObject ? ($this->originalData[$this->getKey($entity)] ?? []) : [];
    }

    /**
     * Returns whether any property of the entity differs from its original value. An `ObjectStorage` is dirty
     * if it was modified since its modified flag was reset.
     *
     * @throws TooDirtyException
     */
    public function isEntityDirty(object $entity): bool
    {
        if ($entity instanceof ObjectStorage) {
            return $this->isStorageModified($entity);
        }
        return $this->computeChangeSet($entity, null) !== [];
    }

    /**
     * Returns whether the given property of the entity differs from its original value.
     *
     * @param non-empty-string $propertyName
     * @throws TooDirtyException
     */
    public function isEntityPropertyDirty(object $entity, string $propertyName): bool
    {
        return $this->computeChangeSet($entity, $propertyName) !== [];
    }

    /**
     * Returns the changed properties of the entity as `property => [original value, current value]`.
     *
     * @return array<non-empty-string, array{mixed, mixed}>
     * @throws TooDirtyException
     */
    public function getEntityChangeSet(object $entity): array
    {
        return $this->computeChangeSet($entity, null);
    }

    /**
     * @param non-empty-string|null $propertyName
     * @return array<non-empty-string, array{mixed, mixed}>
     */
    private function computeChangeSet(object $entity, ?string $propertyName): array
    {
        if (!$entity instanceof AbstractDomainObject) {
            return [];
        }
        $originalData = $this->getOriginalEntityData($entity);
        $uid = $this->readProperty($entity, AbstractDomainObject::PROPERTY_UID);
        $originalUid = $originalData[AbstractDomainObject::PROPERTY_UID] ?? null;
        if ($uid !== null && $originalUid !== null && $uid != $originalUid) {
            throw new TooDirtyException(
                sprintf(
                    'The uid of the persisted object of class "%s" was changed from "%s" to "%s". Changing the uid of a persisted object is not supported.',
                    $entity::class,
                    $originalUid,
                    $uid
                ),
                1222871239
            );
        }

        $propertyNames = $propertyName !== null ? [$propertyName] : array_keys($originalData);
        $changeSet = [];
        foreach ($propertyNames as $name) {
            $originalValue = $originalData[$name] ?? null;
            $currentValue = $this->readProperty($entity, $name);
            if ($this->hasValueChanged($originalValue, $currentValue)) {
                $changeSet[$name] = [$originalValue, $currentValue];
            }
        }
        return $changeSet;
    }

    /**
     * The one place that decides whether a current value differs from the original one.
     */
    private function hasValueChanged(mixed $originalValue, mixed $currentValue): bool
    {
        if (!is_object($currentValue)) {
            return $originalValue !== $currentValue;
        }

        $currentTypeString = null;
        if ($currentValue instanceof LazyLoadingProxy) {
            $currentTypeString = $currentValue->_getTypeAndUidString();
        } elseif ($currentValue instanceof DomainObjectInterface) {
            // For an uninitialized native lazy proxy, getUid() reads the eagerly set uid of the
            // related record without triggering its initialization.
            $currentTypeString = $currentValue::class . ':' . $currentValue->getUid();
        }

        if ($currentTypeString !== null) {
            $originalTypeString = null;
            if ($originalValue instanceof LazyLoadingProxy) {
                $originalTypeString = $originalValue->_getTypeAndUidString();
            } elseif ($originalValue instanceof DomainObjectInterface) {
                $originalTypeString = $originalValue::class . ':' . $originalValue->getUid();
            }
            return $currentTypeString !== $originalTypeString;
        }

        if (new \ReflectionClass($currentValue)->isUninitializedLazyObject($currentValue)) {
            // An uninitialized lazy ObjectStorage cannot have been modified since its creation, as any
            // modifying access would have initialized it. Asking it or comparing it with == would
            // trigger the initialization.
            return false;
        }

        if ($currentValue instanceof ObjectStorage) {
            // The storage owns its modified flag. Comparing it with the original by == could fail
            // for a storage that contains lazy loaded objects, even if it did not change.
            return !is_object($originalValue) || $this->isStorageModified($currentValue) || $originalValue::class !== $currentValue::class;
        }

        // All other objects are compared with == so that a clone of the original is equal to it.
        return $originalValue != $currentValue;
    }

    /**
     * @param array<non-empty-string, mixed> $originalData
     * @param non-empty-string $propertyName
     */
    private function snapshotValue(array &$originalData, string $propertyName, mixed $value): void
    {
        if (is_object($value) && new \ReflectionClass($value)->isUninitializedLazyObject($value)) {
            // Cloning an uninitialized lazy object would trigger its initialization, so it cannot
            // be memorized as clone. A lazy entity proxy is memorized as-is: Its uid is available
            // without initialization, which is all the comparison needs. A lazy ObjectStorage is
            // not memorized at all, its original is registered once it gets initialized. Keeping a
            // reference to the storage here would corrupt serialize(), which initializes the
            // storage mid-traversal and thereby replaces this very entry.
            if ($value instanceof DomainObjectInterface) {
                $originalData[$propertyName] = $value;
            } else {
                unset($originalData[$propertyName]);
            }
        } elseif (is_object($value) && !($value instanceof \UnitEnum) && !($value instanceof DomainObjectInterface)) {
            $originalData[$propertyName] = clone $value;
        } else {
            $originalData[$propertyName] = $value;
        }
    }

    /**
     * The original data of a native lazy proxy is kept under its real instance, as that is the object the
     * persistence layer registers. An uninitialized lazy object is not initialized to find it.
     */
    private function getKey(object $entity): object
    {
        $reflection = new \ReflectionClass($entity);
        return $reflection->isUninitializedLazyObject($entity) ? $entity : $reflection->initializeLazyObject($entity);
    }

    private function getStorageModifiedProperty(): \ReflectionProperty
    {
        return $this->storageModifiedProperty ??= new \ReflectionProperty(ObjectStorage::class, 'isModified');
    }

    private function isStorageModified(ObjectStorage $storage): bool
    {
        // An uninitialized lazy storage cannot have been modified, any modifying access would have initialized it
        return !new \ReflectionClass($storage)->isUninitializedLazyObject($storage)
            && $this->getStorageModifiedProperty()->getValue($storage);
    }

    /**
     * Reads the persistable properties like the entity itself would: public and protected ones, not those starting
     * with an underscore and not uninitialized ones. Properties of parent classes come first.
     *
     * @return array<non-empty-string, mixed>
     */
    private function readProperties(object $entity): array
    {
        $propertyInfo = $this->getPropertyInfo($entity::class);
        $properties = [];
        foreach ($propertyInfo['persistable'] as $propertyName) {
            $property = $propertyInfo['accessible'][$propertyName];
            if ($property->isInitialized($entity)) {
                $properties[$propertyName] = $property->getValue($entity);
            }
        }
        // Dynamic properties are public and not part of the class definition
        foreach (get_object_vars($entity) as $propertyName => $propertyValue) {
            if (!isset($propertyInfo['accessible'][$propertyName]) && !str_starts_with((string)$propertyName, '_')) {
                $properties[$propertyName] = $propertyValue;
            }
        }
        return $properties;
    }

    private function readProperty(object $entity, string $propertyName): mixed
    {
        $property = $this->getPropertyInfo($entity::class)['accessible'][$propertyName] ?? null;
        if ($property === null) {
            return get_object_vars($entity)[$propertyName] ?? null;
        }
        return $property->isInitialized($entity) ? $property->getValue($entity) : null;
    }

    /**
     * @param class-string $className
     * @return array{accessible: array<non-empty-string, \ReflectionProperty>, persistable: list<non-empty-string>}
     */
    private function getPropertyInfo(string $className): array
    {
        if (isset($this->propertiesByClass[$className])) {
            return $this->propertiesByClass[$className];
        }
        $reflection = new \ReflectionClass($className);
        $classes = [];
        for ($class = $reflection; $class !== false; $class = $class->getParentClass()) {
            array_unshift($classes, $class);
        }
        $accessible = [];
        $persistable = [];
        foreach ($classes as $class) {
            foreach ($class->getProperties() as $classProperty) {
                $propertyName = $classProperty->getName();
                if (isset($accessible[$propertyName]) || $classProperty->isStatic() || $classProperty->isVirtual() || $classProperty->isPrivate()) {
                    continue;
                }
                $accessible[$propertyName] = $reflection->getProperty($propertyName);
                if (!str_starts_with($propertyName, '_')) {
                    $persistable[] = $propertyName;
                }
            }
        }
        return $this->propertiesByClass[$className] = ['accessible' => $accessible, 'persistable' => $persistable];
    }
}
