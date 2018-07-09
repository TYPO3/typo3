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
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
     * @return \TYPO3\CMS\Core\Collection\AbstractRecordCollection|null
     */
    public function findByUid($uid)
    {
        $result = null;
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->table);

        if ($this->getEnvironmentMode() === 'FE') {
            $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
        } else {
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        }

        $data = $queryBuilder->select('*')
            ->from($this->table)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)))
            ->execute()
            ->fetch();
        if (is_array($data)) {
            $result = $this->createDomainObject($data);
        }
        return $result;
    }

    /**
     * Finds all record collections.
     *
     * @return \TYPO3\CMS\Core\Collection\AbstractRecordCollection[]|null
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
        $expressionBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($tableName)
            ->expr();

        return $this->queryMultipleRecords([
            $expressionBuilder->eq($this->tableField, $expressionBuilder->literal($tableName))
        ]);
    }

    /**
     * Finds record collection by type.
     *
     * @param string $type Type to be looked up
     * @return \TYPO3\CMS\Core\Collection\AbstractRecordCollection[]|null
     */
    public function findByType($type)
    {
        $expressionBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->table)
            ->expr();

        return $this->queryMultipleRecords([
            $expressionBuilder->eq($this->typeField, $expressionBuilder->literal($type))
        ]);
    }

    /**
     * Finds record collections by type and table name.
     *
     * @param string $type Type to be looked up
     * @param string $tableName Name of the table to be looked up
     * @return \TYPO3\CMS\Core\Collection\AbstractRecordCollection[]|null
     */
    public function findByTypeAndTableName($type, $tableName)
    {
        $expressionBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->table)
            ->expr();

        return $this->queryMultipleRecords([
            $expressionBuilder->eq($this->typeField, $expressionBuilder->literal($type)),
            $expressionBuilder->eq($this->tableField, $expressionBuilder->literal($tableName))
        ]);
    }

    /**
     * Deletes a record collection by uid.
     *
     * @param int $uid uid to be deleted
     */
    public function deleteByUid($uid)
    {
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($this->table)
            ->update(
                $this->table,
                ['deleted' => 1, 'tstamp' => (int)$GLOBALS['EXEC_TIME']],
                ['uid' => (int)$uid]
            );
    }

    /**
     * Queries for multiple records for the given conditions.
     *
     * @param array $conditions Conditions concatenated with AND for query
     * @return \TYPO3\CMS\Core\Collection\AbstractRecordCollection[]|null
     */
    protected function queryMultipleRecords(array $conditions = [])
    {
        $result = null;

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->table);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $queryBuilder->select('*')
            ->from($this->table);

        if (!empty($conditions)) {
            $queryBuilder->where(...$conditions);
        }

        $data = $queryBuilder->execute()->fetchAll();
        if (!empty($data)) {
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
     * Function to return the current TYPO3_MODE.
     * This function can be mocked in unit tests to be able to test frontend behaviour.
     *
     * @return string
     */
    protected function getEnvironmentMode()
    {
        return TYPO3_MODE;
    }
}
