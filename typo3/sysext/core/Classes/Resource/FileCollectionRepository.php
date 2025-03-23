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

namespace TYPO3\CMS\Core\Resource;

use TYPO3\CMS\Core\Collection\CollectionInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Repository for accessing file collections stored in the database
 */
class FileCollectionRepository
{
    protected string $table = 'sys_file_collection';
    protected string $typeField = 'type';

    /**
     * Finds a record collection by uid.
     *
     * @param int $uid The uid to be looked up
     * @throws Exception\ResourceDoesNotExistException
     */
    public function findByUid($uid): ?CollectionInterface
    {
        $object = null;
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
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchAssociative();
        if (is_array($data)) {
            $object = $this->createDomainObject($data);
        }
        if ($object === null) {
            throw new ResourceDoesNotExistException('Could not find row with uid "' . $uid . '" in table "' . $this->table . '"', 1314354066);
        }
        return $object;
    }

    /**
     * Finds record collection by type.
     *
     * @param string $type Type to be looked up
     * @return CollectionInterface[]|null
     */
    public function findByType($type): ?array
    {
        $expressionBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->table)
            ->expr();

        return $this->queryMultipleRecords([
            $expressionBuilder->eq($this->typeField, $expressionBuilder->literal((string)$type)),
        ]);
    }

    /**
     * Queries for multiple records for the given conditions.
     *
     * @param array $conditions Conditions concatenated with AND for query
     * @return CollectionInterface[]|null
     */
    protected function queryMultipleRecords(array $conditions = []): ?array
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

        $data = $queryBuilder->executeQuery()->fetchAllAssociative();
        if (!empty($data)) {
            $result = $this->createMultipleDomainObjects($data);
        }

        return $result;
    }

    /**
     * Creates multiple record collection domain objects.
     *
     * @param array $data Array of multiple database records to be reconstituted
     * @return CollectionInterface[]
     */
    protected function createMultipleDomainObjects(array $data): array
    {
        $collections = [];
        foreach ($data as $collection) {
            $collections[] = $this->createDomainObject($collection);
        }
        return $collections;
    }

    /**
     * Function to return the current application (FE/BE) based on $GLOBALS[TSFE].
     * This function can be mocked in unit tests to be able to test frontend behaviour.
     */
    protected function getEnvironmentMode(): string
    {
        return ($GLOBALS['TSFE'] ?? null) instanceof TypoScriptFrontendController ? 'FE' : 'BE';
    }

    /**
     * Creates a record collection domain object.
     *
     * @param array $record Database record to be reconstituted
     */
    protected function createDomainObject(array $record): CollectionInterface
    {
        return $this->getFileFactory()->createCollectionObject($record);
    }

    protected function getFileFactory(): ResourceFactory
    {
        return GeneralUtility::makeInstance(ResourceFactory::class);
    }

    /**
     * Finds all record collections.
     *
     * @return CollectionInterface[]|null
     */
    public function findAll(): ?array
    {
        return $this->queryMultipleRecords();
    }
}
