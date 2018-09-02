<?php
namespace TYPO3\CMS\Core\Collection;

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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Implementation of a RecordCollection for static TCA-Records
 */
class StaticRecordCollection extends AbstractRecordCollection implements EditableCollectionInterface
{
    /**
     * Creates a new collection objects and reconstitutes the
     * given database record to the new object.
     *
     * @param array $collectionRecord Database record
     * @param bool $fillItems Populates the entries directly on load, might be bad for memory on large collections
     * @return \TYPO3\CMS\Core\Collection\StaticRecordCollection
     */
    public static function create(array $collectionRecord, $fillItems = false)
    {
        /** @var StaticRecordCollection $collection */
        $collection = GeneralUtility::makeInstance(
            self::class,
            $collectionRecord['table_name']
        );
        $collection->fromArray($collectionRecord);
        if ($fillItems) {
            $collection->loadContents();
        }
        return $collection;
    }

    /**
     * Creates this object.
     *
     * @param string $tableName Name of the table to be working on
     * @throws \RuntimeException
     */
    public function __construct($tableName = null)
    {
        parent::__construct();
        if (!empty($tableName)) {
            $this->setItemTableName($tableName);
        } elseif (empty($this->itemTableName)) {
            throw new \RuntimeException(\TYPO3\CMS\Core\Collection\StaticRecordCollection::class . ' needs a valid itemTableName.', 1330293778);
        }
    }

    /**
     * Populates the content-entries of the storage
     *
     * Queries the underlying storage for entries of the collection
     * and adds them to the collection data.
     *
     * If the content entries of the storage had not been loaded on creation
     * ($fillItems = false) this function is to be used for loading the contents
     * afterwards.
     */
    public function loadContents()
    {
        $entries = $this->getCollectedRecords();
        $this->removeAll();
        foreach ($entries as $entry) {
            $this->add($entry);
        }
    }

    /**
     * Returns an array of the persistable properties and contents
     * which are processable by DataHandler.
     *
     * for internal usage in persist only.
     *
     * @return array
     */
    protected function getPersistableDataArray()
    {
        return [
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'items' => $this->getItemUidList(true),
            'type' => 'static',
            'table_name' => $this->getItemTableName()
        ];
    }

    /**
     * Adds on entry to the collection
     *
     * @param mixed $data
     */
    public function add($data)
    {
        $this->storage->push($data);
    }

    /**
     * Adds a set of entries to the collection
     *
     * @param CollectionInterface $other
     */
    public function addAll(CollectionInterface $other)
    {
        foreach ($other as $value) {
            $this->add($value);
        }
    }

    /**
     * Removes the given entry from collection
     *
     * Note: not the given "index"
     *
     * @param mixed $data
     */
    public function remove($data)
    {
        $offset = 0;
        foreach ($this->storage as $value) {
            if ($value == $data) {
                break;
            }
            $offset++;
        }
        $this->storage->offsetUnset($offset);
    }

    /**
     * Removes all entries from the collection
     *
     * collection will be empty afterwards
     */
    public function removeAll()
    {
        $this->storage = new \SplDoublyLinkedList();
    }

    /**
     * Gets the collected records in this collection, by
     * looking up the MM relations of this record to the
     * table name defined in the local field 'table_name'.
     *
     * @return array
     */
    protected function getCollectedRecords()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::$storageTableName);
        $queryBuilder->getRestrictions()->removeAll();
        $statement = $queryBuilder->select($this->getItemTableName() . '.*')
            ->from(self::$storageTableName)
            ->join(
                self::$storageTableName,
                'sys_collection_entries',
                'sys_collection_entries',
                $queryBuilder->expr()->eq(
                    'sys_collection_entries.uid_local',
                    $queryBuilder->quoteIdentifier(self::$storageTableName . '.uid')
                )
            )
            ->join(
                'sys_collection_entries',
                $this->getItemTableName(),
                $this->getItemTableName(),
                $queryBuilder->expr()->eq(
                    'sys_collection_entries.uid_foreign',
                    $queryBuilder->quoteIdentifier($this->getItemTableName() . '.uid')
                )
            )
            ->where(
                $queryBuilder->expr()->eq(
                    self::$storageTableName . '.uid',
                    $queryBuilder->createNamedParameter($this->getIdentifier(), \PDO::PARAM_INT)
                )
            )
            ->execute();
        $relatedRecords = [];
        while ($record = $statement->fetch()) {
            $relatedRecords[] = $record;
        }
        return $relatedRecords;
    }
}
