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

namespace TYPO3\CMS\Core\Category\Collection;

use TYPO3\CMS\Core\Collection\AbstractRecordCollection;
use TYPO3\CMS\Core\Collection\CollectionInterface;
use TYPO3\CMS\Core\Collection\EditableCollectionInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Category Collection to handle records attached to a category
 */
class CategoryCollection extends AbstractRecordCollection implements EditableCollectionInterface
{
    /**
     * The table name collections are stored to
     *
     * @var string
     */
    protected static $storageTableName = 'sys_category';

    /**
     * Name of the categories-relation field (used in the MM_match_fields/fieldname property of the TCA)
     *
     * @var string
     */
    protected $relationFieldName = 'categories';

    /**
     * Creates this object.
     *
     * @param string $tableName Name of the table to be working on
     * @param string $fieldName Name of the field where the categories relations are defined
     * @throws \RuntimeException
     */
    public function __construct($tableName = null, $fieldName = null)
    {
        parent::__construct();
        if (!empty($tableName)) {
            $this->setItemTableName($tableName);
        } elseif (empty($this->itemTableName)) {
            throw new \RuntimeException(self::class . ' needs a valid itemTableName.', 1341826168);
        }
        if (!empty($fieldName)) {
            $this->setRelationFieldName($fieldName);
        }
    }

    /**
     * Creates a new collection objects and reconstitutes the
     * given database record to the new object.
     *
     * @param array $collectionRecord Database record
     * @param bool $fillItems Populates the entries directly on load, might be bad for memory on large collections
     * @return CategoryCollection
     */
    public static function create(array $collectionRecord, $fillItems = false)
    {
        $collection = GeneralUtility::makeInstance(
            self::class,
            $collectionRecord['table_name'],
            $collectionRecord['field_name']
        );
        $collection->fromArray($collectionRecord);
        if ($fillItems) {
            $collection->loadContents();
        }
        return $collection;
    }

    /**
     * Loads the collections with the given id from persistence
     * For memory reasons, per default only f.e. title, database-table,
     * identifier (what ever static data is defined) is loaded.
     * Entries can be load on first access.
     *
     * @param int $id Id of database record to be loaded
     * @param bool $fillItems Populates the entries directly on load, might be bad for memory on large collections
     * @param string $tableName Name of table from which entries should be loaded
     * @param string $fieldName Name of the categories relation field
     * @return CollectionInterface
     */
    public static function load($id, $fillItems = false, $tableName = '', $fieldName = '')
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(static::$storageTableName);

        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $collectionRecord = $queryBuilder->select('*')
            ->from(static::$storageTableName)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT))
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        $collectionRecord['table_name'] = $tableName;
        $collectionRecord['field_name'] = $fieldName;

        return self::create($collectionRecord, $fillItems);
    }

    /**
     * Selects the collected records in this collection, by
     * looking up the MM relations of this record to the
     * table name defined in the local field 'table_name'.
     *
     * @return QueryBuilder
     */
    protected function getCollectedRecordsQueryBuilder()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(static::$storageTableName);
        $queryBuilder->getRestrictions()->removeAll();

        $queryBuilder->select($this->getItemTableName() . '.*')
            ->from(static::$storageTableName)
            ->join(
                static::$storageTableName,
                'sys_category_record_mm',
                'sys_category_record_mm',
                $queryBuilder->expr()->eq(
                    'sys_category_record_mm.uid_local',
                    $queryBuilder->quoteIdentifier(static::$storageTableName . '.uid')
                )
            )
            ->join(
                'sys_category_record_mm',
                $this->getItemTableName(),
                $this->getItemTableName(),
                $queryBuilder->expr()->eq(
                    'sys_category_record_mm.uid_foreign',
                    $queryBuilder->quoteIdentifier($this->getItemTableName() . '.uid')
                )
            )
            ->where(
                $queryBuilder->expr()->eq(
                    static::$storageTableName . '.uid',
                    $queryBuilder->createNamedParameter($this->getIdentifier(), \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'sys_category_record_mm.tablenames',
                    $queryBuilder->createNamedParameter($this->getItemTableName(), \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'sys_category_record_mm.fieldname',
                    $queryBuilder->createNamedParameter($this->getRelationFieldName(), \PDO::PARAM_STR)
                )
            )
            // Add required sorting field.
            ->orderBy('sys_category_record_mm.sorting', 'ASC')
            // Add foreign uid field to ensure determistic sorting across dbms and dbms versions
            ->addOrderBy('sys_category_record_mm.uid_foreign', 'ASC')
        ;

        return $queryBuilder;
    }

    /**
     * Gets the collected records in this collection, by
     * using <getCollectedRecordsQueryBuilder>.
     *
     * @return array
     */
    protected function getCollectedRecords()
    {
        $relatedRecords = [];

        $queryBuilder = $this->getCollectedRecordsQueryBuilder();
        $result = $queryBuilder->executeQuery();

        while ($record = $result->fetchAssociative()) {
            $relatedRecords[] = $record;
        }

        return $relatedRecords;
    }

    /**
     * Populates the content-entries of the storage
     * Queries the underlying storage for entries of the collection
     * and adds them to the collection data.
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
     * collection will be empty afterwards
     */
    public function removeAll()
    {
        $this->storage = new \SplDoublyLinkedList();
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
     * Sets the name of the categories relation field
     *
     * @param string $field
     */
    public function setRelationFieldName($field)
    {
        $this->relationFieldName = $field;
    }

    /**
     * Gets the name of the categories relation field
     *
     * @return string
     */
    public function getRelationFieldName()
    {
        return $this->relationFieldName;
    }

    /**
     * Getter for the storage table name
     *
     * @return string
     */
    public static function getStorageTableName()
    {
        return self::$storageTableName;
    }

    /**
     * Getter for the storage items field
     *
     * @return string
     */
    public static function getStorageItemsField()
    {
        return self::$storageItemsField;
    }
}
