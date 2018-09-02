<?php
namespace TYPO3\CMS\Core\Resource\Collection;

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
use TYPO3\CMS\Core\Collection\CollectionInterface;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;

/**
 * Abstract collection.
 */
abstract class AbstractFileCollection extends \TYPO3\CMS\Core\Collection\AbstractRecordCollection
{
    /**
     * The table name collections are stored to
     *
     * @var string
     */
    protected static $storageTableName = 'sys_file_collection';

    /**
     * The type of file collection
     * (see \TYPO3\CMS\Core\Collection\RecordCollectionRepository::TYPE constants)
     *
     * @var string
     */
    protected static $type;

    /**
     * The name of the field items are handled with
     * (usually either criteria, items or folder)
     *
     * @var string
     */
    protected static $itemsCriteriaField;

    /**
     * Field contents of $itemsCriteriaField. Defines which the items or search criteria for the items
     * depending on the type (see self::$type above) of this file collection.
     *
     * @var mixed
     */
    protected $itemsCriteria;

    /**
     * Name of the table records of this collection are stored in
     *
     * @var string
     */
    protected $itemTableName = 'sys_file';

    /**
     * Sets the description.
     *
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Return the key of the current element
     *
     * @return string
     */
    public function key()
    {
        /** @var File $currentRecord */
        $currentRecord = $this->storage->current();
        return $currentRecord->getIdentifier();
    }

    /**
     * Generates comma-separated list of entry uids for usage in DataHandler
     *
     * @param bool $includeTableName
     * @return string
     */
    protected function getItemUidList($includeTableName = false)
    {
        $list = [];
        /** @var File $entry */
        foreach ($this->storage as $entry) {
            $list[] = $this->getItemTableName() . '_' . $entry->getUid();
        }
        return implode(',', $list);
    }

    /**
     * Returns an array of the persistable properties and contents
     * which are processable by DataHandler.
     *
     * @return array
     */
    protected function getPersistableDataArray()
    {
        return [
            'title' => $this->getTitle(),
            'type' => static::$type,
            'description' => $this->getDescription(),
            static::$itemsCriteriaField => $this->getItemsCriteria()
        ];
    }

    /**
     * Similar to method in \TYPO3\CMS\Core\Collection\AbstractRecordCollection,
     * but without 'table_name' => $this->getItemTableName()
     *
     * @return array
     */
    public function toArray()
    {
        $itemArray = [];
        /** @var File $item */
        foreach ($this->storage as $item) {
            $itemArray[] = $item->toArray();
        }
        return [
            'uid' => $this->getIdentifier(),
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'items' => $itemArray
        ];
    }

    /**
     * Gets the current available items.
     *
     * @return array
     */
    public function getItems()
    {
        $itemArray = [];
        /** @var File $item */
        foreach ($this->storage as $item) {
            $itemArray[] = $item;
        }
        return $itemArray;
    }

    /**
     * Similar to method in \TYPO3\CMS\Core\Collection\AbstractRecordCollection,
     * but without $this->itemTableName= $array['table_name'],
     * but with $this->storageItemsFieldContent = $array[self::$storageItemsField];
     *
     * @param array $array
     */
    public function fromArray(array $array)
    {
        $this->uid = $array['uid'];
        $this->title = $array['title'];
        $this->description = $array['description'];
        $this->itemsCriteria = $array[static::$itemsCriteriaField];
    }

    /**
     * Gets ths items criteria.
     *
     * @return mixed
     */
    public function getItemsCriteria()
    {
        return $this->itemsCriteria;
    }

    /**
     * Sets the items criteria.
     *
     * @param mixed $itemsCriteria
     */
    public function setItemsCriteria($itemsCriteria)
    {
        $this->itemsCriteria = $itemsCriteria;
    }

    /**
     * Adds a file to this collection.
     *
     * @param FileInterface $data
     */
    public function add(FileInterface $data)
    {
        $this->storage->push($data);
    }

    /**
     * Adds all files of another collection to the corrent one.
     *
     * @param CollectionInterface $other
     */
    public function addAll(CollectionInterface $other)
    {
        /** @var File $value */
        foreach ($other as $value) {
            $this->add($value);
        }
    }

    /**
     * Removes a file from this collection.
     *
     * @param File $file
     */
    public function remove(File $file)
    {
        $offset = 0;
        /** @var File $value */
        foreach ($this->storage as $value) {
            if ($value === $file) {
                break;
            }
            $offset++;
        }
        $this->storage->offsetUnset($offset);
    }

    /**
     * Removes all elements of the current collection.
     */
    public function removeAll()
    {
        $this->storage = new \SplDoublyLinkedList();
    }
}
