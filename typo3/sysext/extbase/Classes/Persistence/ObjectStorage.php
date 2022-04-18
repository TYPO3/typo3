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

namespace TYPO3\CMS\Extbase\Persistence;

use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;

/**
 * The storage for objects. It ensures the uniqueness of an object in the storage. It's a remake of the
 * SplObjectStorage introduced in PHP 5.3.
 *
 * Opposed to the SplObjectStorage the ObjectStorage does not implement the Serializable interface.
 *
 * @template TEntity
 * @implements \ArrayAccess<string, TEntity>
 * @implements \Iterator<string, TEntity>
 */
class ObjectStorage implements \Countable, \Iterator, \ArrayAccess, ObjectMonitoringInterface
{
    /**
     * This field is only needed to make debugging easier:
     * If you call current() on a class that implements Iterator, PHP will return the first field of the object
     * instead of calling the current() method of the interface.
     * We use this unusual behavior of PHP to return the warning below in this case.
     *
     * @var string
     */
    private $warning = 'You should never see this warning. If you do, you probably used PHP array functions like current() on the TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage. To retrieve the first result, you can use the rewind() and current() methods.';

    /**
     * An array holding the objects and the stored information. The key of the array items ist the
     * spl_object_hash of the given object.
     *
     * array(
     * spl_object_hash =>
     * array(
     * 'obj' => $object,
     * 'inf' => $information
     * )
     * )
     *
     * @var array
     */
    protected $storage = [];

    /**
     * A flag indication if the object storage was modified after reconstitution (eg. by adding a new object)
     *
     * @var bool
     */
    protected $isModified = false;

    /**
     * An array holding the internal position the object was added.
     * The object entry is unset when the object gets removed from the objectstorage
     *
     * @var array
     */
    protected $addedObjectsPositions = [];

    /**
     * An array holding the internal position the object was added before, when it would
     * be removed from the objectstorage
     *
     * @var array
     */
    protected $removedObjectsPositions = [];

    /**
     * An internal var holding the count of added objects to be stored as position.
     * It would be reset, when all objects will be removed from the objectstorage
     *
     * @var int
     */
    protected $positionCounter = 0;

    /**
     * Rewinds the iterator to the first storage element.
     */
    #[\ReturnTypeWillChange]
    public function rewind()
    {
        reset($this->storage);
    }

    /**
     * Checks if the array pointer of the storage points to a valid position.
     *
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function valid()
    {
        return current($this->storage) !== false;
    }

    /**
     * Returns the index at which the iterator currently is.
     *
     * This is different from the SplObjectStorage as the key in this implementation is the object hash (string).
     *
     * @return string The index corresponding to the position of the iterator.
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return key($this->storage);
    }

    /**
     * Returns the current storage entry.
     *
     * @return TEntity|null The object at the current iterator position.
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        $item = current($this->storage);

        return $item['obj'] ?? null;
    }

    /**
     * Moves to the next entry.
     */
    #[\ReturnTypeWillChange]
    public function next()
    {
        next($this->storage);
    }

    /**
     * Returns the number of objects in the storage.
     *
     * @return int The number of objects in the storage.
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->storage);
    }

    /**
     * Associates data to an object in the storage. offsetSet() is an alias of attach().
     *
     * @param TEntity $object The object to add.
     * @param mixed $information The data to associate with the object.
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($object, $information)
    {
        $this->isModified = true;
        $this->storage[spl_object_hash($object)] = ['obj' => $object, 'inf' => $information];

        $this->positionCounter++;
        $this->addedObjectsPositions[spl_object_hash($object)] = $this->positionCounter;
    }

    /**
     * Checks whether an object exists in the storage.
     *
     * @param TEntity|int $value The object to look for, or the key in the storage.
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($value)
    {
        return (is_object($value) && isset($this->storage[spl_object_hash($value)]))
            || (MathUtility::canBeInterpretedAsInteger($value) && isset(array_values($this->storage)[$value]));
    }

    /**
     * Removes an object from the storage. offsetUnset() is an alias of detach().
     *
     * @param TEntity|int $value The object to remove, or its key in the storage.
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($value)
    {
        $this->isModified = true;

        $object = $value;

        if (MathUtility::canBeInterpretedAsInteger($value)) {
            $object = $this->offsetGet($value);
        }

        unset($this->storage[spl_object_hash($object)]);

        if (empty($this->storage)) {
            $this->positionCounter = 0;
        }

        $this->removedObjectsPositions[spl_object_hash($object)] = $this->addedObjectsPositions[spl_object_hash($object)] ?? null;
        unset($this->addedObjectsPositions[spl_object_hash($object)]);
    }

    /**
     * Returns the data associated with an object, or the object itself if an
     * integer is passed.
     *
     * @param TEntity|int $value The object to look for, or its key in the storage.
     * @return mixed The data associated with an object in the storage, or the object itself if an integer is passed.
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($value)
    {
        if (MathUtility::canBeInterpretedAsInteger($value)) {
            return array_values($this->storage)[$value]['obj'];
        }

        /** @var DomainObjectInterface $value */
        return $this->storage[spl_object_hash($value)]['inf'];
    }

    /**
     * Checks if the storage contains a specific object.
     *
     * @param TEntity $object The object to look for.
     * @return bool
     */
    public function contains($object)
    {
        return $this->offsetExists($object);
    }

    /**
     * Adds an object in the storage, and optionally associate it to some data.
     *
     * @param TEntity $object The object to add.
     * @param mixed $information The data to associate with the object.
     */
    public function attach($object, $information = null)
    {
        $this->offsetSet($object, $information);
    }

    /**
     * Removes an object from the storage.
     *
     * @param TEntity $object The object to remove.
     */
    public function detach($object)
    {
        $this->offsetUnset($object);
    }

    /**
     * Returns the data, or info, associated with the object pointed by the current iterator position.
     *
     * @return mixed The data associated with the current iterator position.
     */
    public function getInfo()
    {
        $item = current($this->storage);

        return $item['inf'] ?? null;
    }

    /**
     * Associates data, or info, with the object currently pointed to by the iterator.
     *
     * @param mixed $data
     */
    public function setInfo($data)
    {
        $this->isModified = true;
        $key = key($this->storage);
        $this->storage[$key]['inf'] = $data;
    }

    /**
     * Adds all objects-data pairs from a different storage in the current storage.
     *
     * @param ObjectStorage<TEntity> $objectStorage
     */
    public function addAll(ObjectStorage $objectStorage)
    {
        foreach ($objectStorage as $object) {
            $this->attach($object, $objectStorage->getInfo());
        }
    }

    /**
     * Removes objects contained in another storage from the current storage.
     *
     * @param ObjectStorage<TEntity> $objectStorage The storage containing the elements to remove.
     */
    public function removeAll(ObjectStorage $objectStorage)
    {
        foreach ($objectStorage as $object) {
            $this->detach($object);
        }
    }

    /**
     * Returns this object storage as an array
     *
     * @return array The object storage
     */
    public function toArray()
    {
        $array = [];
        $storage = array_values($this->storage);
        foreach ($storage as $item) {
            $array[] = $item['obj'];
        }
        return $array;
    }

    /**
     * Alias of toArray which allows that method to be used from contexts which support
     * for example dotted paths, e.g. ObjectAccess::getPropertyPath($object, 'children.array.123')
     * to get exactly the 123rd item in the "children" property which is an ObjectStorage.
     *
     * @return array
     */
    public function getArray()
    {
        return $this->toArray();
    }

    /**
     * Dummy method to avoid serialization.
     *
     * @throws \RuntimeException
     * @todo: serialize() is not called when \Serializable interface is not implemented. Obsolete method. Drop in v12.
     */
    public function serialize()
    {
        throw new \RuntimeException('An ObjectStorage instance cannot be serialized.', 1267700868);
    }

    /**
     * Dummy method to avoid unserialization.
     *
     * @throws \RuntimeException
     * @todo: unserialize() is not called when \Serializable interface is not implemented. Obsolete method. Drop in v12.
     */
    public function unserialize()
    {
        throw new \RuntimeException('A ObjectStorage instance cannot be unserialized.', 1267700870);
    }

    /**
     * Register the storage's clean state, e.g. after it has been reconstituted from the database.
     */
    public function _memorizeCleanState()
    {
        $this->isModified = false;
    }

    /**
     * Returns TRUE if the storage was modified after reconstitution.
     *
     * @return bool
     */
    public function _isDirty()
    {
        return $this->isModified;
    }

    /**
     * Returns TRUE if an object is added, then removed and added at a different position
     *
     * @param mixed $object
     * @return bool
     */
    public function isRelationDirty($object)
    {
        return isset($this->addedObjectsPositions[spl_object_hash($object)])
                && isset($this->removedObjectsPositions[spl_object_hash($object)])
                && ($this->addedObjectsPositions[spl_object_hash($object)] !== $this->removedObjectsPositions[spl_object_hash($object)]);
    }

    /**
     * @param mixed $object
     * @return int|null
     */
    public function getPosition($object)
    {
        if (!isset($this->addedObjectsPositions[spl_object_hash($object)])) {
            return null;
        }

        return $this->addedObjectsPositions[spl_object_hash($object)];
    }
}
