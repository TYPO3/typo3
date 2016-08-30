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

use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Implements the repository for record collections.
 */
class RecordCollectionRepository
{
    /**
     * @var string
     */
    const TYPE_Static = 'static';

    /**
     * Name of the table the collection records are stored to
     *
     * @var string
     */
    protected $table = 'sys_collection';

    /**
     * @var string
     */
    protected $typeField = 'type';

    /**
     * @var string
     */
    protected $tableField = 'table_name';

    /**
     * Finds a record collection by uid.
     *
     * @param int $uid The uid to be looked up
     * @return NULL|\TYPO3\CMS\Core\Collection\AbstractRecordCollection
     */
    public function findByUid($uid)
    {
        $result = null;
        $data = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            '*',
            $this->table,
            'uid=' . (int)$uid . BackendUtility::deleteClause($this->table)
        );
        if (is_array($data)) {
            $result = $this->createDomainObject($data);
        }
        return $result;
    }

    /**
     * Finds all record collections.
     *
     * @return NULL|\TYPO3\CMS\Core\Collection\AbstractRecordCollection[]
     */
    public function findAll()
    {
        return $this->queryMultipleRecords();
    }

    /**
     * Finds record collections by table name.
     *
     * @param string $tableName Name of the table to be looked up
     * @return \TYPO3\CMS\Core\Collection\AbstractRecordCollection[]
     */
    public function findByTableName($tableName)
    {
        $conditions = [
            $this->tableField . '=' . $this->getDatabaseConnection()->fullQuoteStr($tableName, $this->table)
        ];
        return $this->queryMultipleRecords($conditions);
    }

    /**
     * Finds record collection by type.
     *
     * @param string $type Type to be looked up
     * @return NULL|\TYPO3\CMS\Core\Collection\AbstractRecordCollection[]
     */
    public function findByType($type)
    {
        $conditions = [
            $this->typeField . '=' . $this->getDatabaseConnection()->fullQuoteStr($type, $this->table)
        ];
        return $this->queryMultipleRecords($conditions);
    }

    /**
     * Finds record collections by type and table name.
     *
     * @param string $type Type to be looked up
     * @param string $tableName Name of the table to be looked up
     * @return NULL|\TYPO3\CMS\Core\Collection\AbstractRecordCollection[]
     */
    public function findByTypeAndTableName($type, $tableName)
    {
        $conditions = [
            $this->typeField . '=' . $this->getDatabaseConnection()->fullQuoteStr($type, $this->table),
            $this->tableField . '=' . $this->getDatabaseConnection()->fullQuoteStr($tableName, $this->table)
        ];
        return $this->queryMultipleRecords($conditions);
    }

    /**
     * Deletes a record collection by uid.
     *
     * @param int $uid uid to be deleted
     * @return void
     */
    public function deleteByUid($uid)
    {
        $this->getDatabaseConnection()->exec_UPDATEquery($this->table, 'uid=' . (int)$uid, ['deleted' => 1, 'tstamp' => $GLOBALS['EXEC_TIME']]);
    }

    /**
     * Queries for multiple records for the given conditions.
     *
     * @param array $conditions Conditions concatenated with AND for query
     * @return NULL|\TYPO3\CMS\Core\Collection\AbstractRecordCollection[]
     */
    protected function queryMultipleRecords(array $conditions = [])
    {
        $result = null;
        if (!empty($conditions)) {
            $conditionsWhereClause = implode(' AND ', $conditions);
        } else {
            $conditionsWhereClause = '1=1';
        }
        $data = $this->getDatabaseConnection()->exec_SELECTgetRows(
            '*',
            $this->table,
            $conditionsWhereClause . BackendUtility::deleteClause($this->table)
        );
        if ($data !== null) {
            $result = $this->createMultipleDomainObjects($data);
        }
        return $result;
    }

    /**
     * Creates a record collection domain object.
     *
     * @param array $record Database record to be reconstituted
     * @return \TYPO3\CMS\Core\Collection\AbstractRecordCollection
     * @throws \RuntimeException
     */
    protected function createDomainObject(array $record)
    {
        switch ($record['type']) {
            case self::TYPE_Static:
                $collection = StaticRecordCollection::create($record);
                break;
            default:
                throw new \RuntimeException('Unknown record collection type "' . $record['type'], 1328646798);
        }
        return $collection;
    }

    /**
     * Creates multiple record collection domain objects.
     *
     * @param array $data Array of multiple database records to be reconstituted
     * @return \TYPO3\CMS\Core\Collection\AbstractRecordCollection[]
     */
    protected function createMultipleDomainObjects(array $data)
    {
        $collections = [];
        foreach ($data as $collection) {
            $collections[] = $this->createDomainObject($collection);
        }
        return $collections;
    }

    /**
     * Gets the database object.
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
