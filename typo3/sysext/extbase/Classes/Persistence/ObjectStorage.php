<?php
namespace TYPO3\CMS\Extbase\Persistence;

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

/**
 * The storage for objects. It ensures the uniqueness of an object in the storage. It's a remake of the
 * SplObjectStorage introduced in PHP 5.3.
 *
 * Opposed to the SplObjectStorage the ObjectStorage does not implement the Serializable interface.
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
     * The object entry is unsetted when the object gets removed from the objectstorage
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
     * It would be resetted, when all objects will be removed from the objectstorage
     *
     * @var int
     */
    protected $positionCounter = 0;

    /**
     * Rewinds the iterator to the first storage element.
     *
     * @return void
     */
    public function rewind()
    {
        reset($this->storage);
    }

    /**
     * Checks if the array pointer of the storage points to a valid position.
     *
     * @return bool
     */
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
    public function key()
    {
        return key($this->storage);
    }

    /**
     * Returns the current storage entry.
     *
     * @return object The object at the current iterator position.
     */
    public function current()
    {
        $item = current($this->storage);
        return $item['obj'];
    }

    /**
     * Moves to the next entry.
     *
     * @return void
     */
    public function next()
    {
        next($this->storage);
    }

    /**
     * Returns the number of objects in the storage.
     *
     * @return int The number of objects in the storage.
     */
    public function count()
    {
        return count($this->storage);
    }

    /**
     * Associates data to an object in the storage. offsetSet() is an alias of attach().
     *
     * @param object $object The object to add.
     * @param mixed $information The data to associate with the object.
     * @return void
     */
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
     * @param object $object The object to look for.
     * @return bool
     */
    public function offsetExists($object)
    {
        return is_object($object) && isset($this->storage[spl_object_hash($object)]);
    }

    /**
     * Removes an object from the storage. offsetUnset() is an alias of detach().
     *
     * @param object $object The object to remove.
     * @return void
     */
    public function offsetUnset($object)
    {
        $this->isModified = true;
        unset($this->storage[spl_object_hash($object)]);

        if (empty($this->storage)) {
            $this->positionCounter = 0;
        }

        $this->removedObjectsPositions[spl_object_hash($object)] = $this->addedObjectsPositions[spl_object_hash($object)];
        unset($this->addedObjectsPositions[spl_object_hash($object)]);
    }

    /**
     * Returns the data associated with an object.
     *
     * @param object $object The object to look for.
     * @return mixed The data associated with an object in the storage.
     */
    public function offsetGet($object)
    {
        return $this->storage[spl_object_hash($object)]['inf'];
    }

    /**
     * Checks if the storage contains a specific object.
     *
     * @param object $object The object to look for.
     * @return bool
     */
    public function contains($object)
    {
        return $this->offsetExists($object);
    }

    /**
     * Adds an object in the storage, and optionaly associate it to some data.
     *
     * @param object $object The object to add.
     * @param mixed $information The data to associate with the object.
     * @return void
     */
    public function attach($object, $information = null)
    {
        $this->offsetSet($object, $information);
    }

    /**
     * Removes an object from the storage.
     *
     * @param object $object The object to remove.
     * @return void
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
        return $item['inf'];
    }

    /**
     * Associates data, or info, with the object currently pointed to by the iterator.
     *
     * @param mixed $data
     * @return void
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
     * @param ObjectStorage $objectStorage
     * @return void
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
     * @param ObjectStorage $objectStorage The storage containing the elements to remove.
     * @return void
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
     * Dummy method to avoid serialization.
     *
     * @throws \RuntimeException
     * @return void
     */
    public function serialize()
    {
        throw new \RuntimeException('An ObjectStorage instance cannot be serialized.', 1267700868);
    }

    /**
     * Dummy method to avoid unserialization.
     *
     * @param string $serialized
     * @throws \RuntimeException
     * @return void
     */
    public function unserialize($serialized)
    {
        throw new \RuntimeException('A ObjectStorage instance cannot be unserialized.', 1267700870);
    }

    /**
     * Register the storage's clean state, e.g. after it has been reconstituted from the database.
     *
     * @return void
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
     * @return int|NULL
     */
    public function getPosition($object)
    {
        if (!isset($this->addedObjectsPositions[spl_object_hash($object)])) {
            return null;
        }

        return $this->addedObjectsPositions[spl_object_hash($object)];
    }
}
