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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap\Relation;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * A proxy that can replace any object and replaces itself in its parent on the first access
 * (`call`, `get`, `set`, `isset`, `unset`).
 *
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 *
 * @template TEntity
 * @extends ObjectStorage<TEntity>
 */
class LazyObjectStorage extends ObjectStorage implements LoadingStrategyInterface
{
    /**
     * This field is only needed to make debugging easier:
     *
     * If you call current() on a class that implements Iterator, PHP will return the first field of the object
     * instead of calling the current() method of the interface.
     *
     * We use this unusual behavior of PHP to return the warning below in this case.
     *
     * @var string
     */
    private $warning = 'You should never see this warning. If you do, you probably used PHP array functions like current() on the TYPO3\\CMS\\Extbase\\Persistence\\Generic\\LazyObjectStorage. To retrieve the first result, you can use the rewind() and current() methods.';

    protected DataMapper $dataMapper;

    /**
     * The object this property is contained in.
     *
     * @var \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface
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
     * @var bool
     */
    protected $isInitialized = false;

    /**
     * @return bool
     */
    public function isInitialized()
    {
        return $this->isInitialized;
    }

    /**
     * @param TEntity $parentObject The object instance this proxy is part of
     * @param string $propertyName The name of the proxied property in its parent
     * @param mixed $fieldValue The raw field value.
     */
    public function __construct($parentObject, $propertyName, $fieldValue, ?DataMapper $dataMapper = null)
    {
        $this->parentObject = $parentObject;
        $this->propertyName = $propertyName;
        $this->fieldValue = $fieldValue;
        reset($this->storage);
        if ($dataMapper === null) {
            $dataMapper = GeneralUtility::makeInstance(DataMapper::class);
        }
        $this->dataMapper = $dataMapper;
    }

    /**
     * Lazily initializes the object storage.
     */
    protected function initialize()
    {
        if ($this->isInitialized) {
            return;
        }

        $this->isInitialized = true;
        $objects = $this->dataMapper->fetchRelated($this->parentObject, $this->propertyName, $this->fieldValue, false);
        foreach ($objects as $object) {
            parent::attach($object);
        }
        $this->_memorizeCleanState();
        if (!$this->isStorageAlreadyMemorizedInParentCleanState()) {
            $this->parentObject->_memorizeCleanState($this->propertyName);
        }
    }

    protected function isStorageAlreadyMemorizedInParentCleanState(): bool
    {
        return $this->parentObject->_getCleanProperty($this->propertyName) === $this;
    }

    // Delegation to the ObjectStorage methods below

    /**
     * @see `ObjectStorage::addAll`
     */
    public function addAll(ObjectStorage $storage): void
    {
        $this->initialize();
        parent::addAll($storage);
    }

    /**
     * @param TEntity $object The object to add.
     * @param mixed $information The information to associate with the object.
     *
     * @see `ObjectStorage::attach`
     */
    public function attach($object, $information = null): void
    {
        $this->initialize();
        parent::attach($object, $information);
    }

    /**
     * @param TEntity $object The object to look for.
     *
     * @see `ObjectStorage::contains`
     */
    public function contains($object): bool
    {
        $this->initialize();
        return parent::contains($object);
    }

    /**
     * Counts the elements in the storage array
     *
     * @throws Exception
     * @return 0|positive-int The number of objects in the storage.
     */
    public function count(): int
    {
        $columnMap = $this->dataMapper->getDataMap(get_class($this->parentObject))->getColumnMap($this->propertyName);
        if (!$this->isInitialized && $columnMap->getTypeOfRelation() === Relation::HAS_MANY) {
            $numberOfElements = $this->dataMapper->countRelated($this->parentObject, $this->propertyName, $this->fieldValue);
        } else {
            $this->initialize();
            $numberOfElements = count($this->storage);
        }
        return $numberOfElements;
    }

    /**
     * @return TEntity|null The object at the current iterator position.
     *
     * @see `ObjectStorage::current`
     *
     * @todo: Set return type to mixed in v13
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        $this->initialize();
        return parent::current();
    }

    /**
     * @param TEntity $object The object to remove.
     *
     * @see `ObjectStorage::detach`
     */
    public function detach($object): void
    {
        $this->initialize();
        parent::detach($object);
    }

    /**
     * @return string The index corresponding to the position of the iterator.
     *
     * @see `ObjectStorage::key`
     */
    public function key(): string
    {
        $this->initialize();
        return parent::key();
    }

    /**
     * @see `ObjectStorage::next`
     */
    public function next(): void
    {
        $this->initialize();
        parent::next();
    }

    /**
     * @param TEntity $value The object to look for, or the key in the storage.
     *
     * @see `ObjectStorage::offsetExists`
     */
    public function offsetExists($value): bool
    {
        $this->initialize();
        return parent::offsetExists($value);
    }

    /**
     * @param TEntity $value The object to look for, or its key in the storage.
     *
     * @see `ObjectStorage::offsetGet`
     *
     * @todo: Set return type to mixed in v13
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($value)
    {
        $this->initialize();
        return parent::offsetGet($value);
    }

    /**
     * @param TEntity $object The object to add.
     * @param mixed $information The information to associate with the object.
     *
     * @see `ObjectStorage::offsetSet`
     */
    public function offsetSet($object, $information): void
    {
        $this->initialize();
        parent::offsetSet($object, $information);
    }

    /**
     * @param TEntity $value The object to remove, or its key in the storage.
     *
     * @see `ObjectStorage::offsetUnset`
     */
    public function offsetUnset($value): void
    {
        $this->initialize();
        parent::offsetUnset($value);
    }

    /**
     * @param ObjectStorage $storage The storage containing the elements to remove.
     *
     * @see `ObjectStorage::removeAll`
     */
    public function removeAll(ObjectStorage $storage): void
    {
        $this->initialize();
        parent::removeAll($storage);
    }

    /**
     * @see `ObjectStorage::rewind`
     */
    public function rewind(): void
    {
        $this->initialize();
        parent::rewind();
    }

    /**
     * @see `ObjectStorage::valid`
     */
    public function valid(): bool
    {
        $this->initialize();
        return parent::valid();
    }

    /**
     * @see `ObjectStorage::toArray`
     */
    public function toArray(): array
    {
        $this->initialize();
        return parent::toArray();
    }

    /**
     * @param mixed $object
     */
    public function getPosition($object): ?int
    {
        $this->initialize();
        return parent::getPosition($object);
    }

    public function __serialize(): array
    {
        $properties = get_object_vars($this);
        unset(
            $properties['warning'],
            $properties['dataMapper']
        );
        return $properties;
    }

    public function __unserialize(array $data): void
    {
        foreach ($data as $propertyName => $propertyValue) {
            $this->{$propertyName} = $propertyValue;
        }

        $this->dataMapper = GeneralUtility::getContainer()->get(DataMapper::class);
    }
}
