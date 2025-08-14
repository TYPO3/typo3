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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Collection\AbstractRecordCollection;
use TYPO3\CMS\Core\Collection\CollectionInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Resource\Collection\FileCollectionRegistry;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Repository for accessing file collections stored in the database
 */
readonly class FileCollectionRepository
{
    public function __construct(
        private ConnectionPool $connectionPool,
        private FileCollectionRegistry $fileCollectionRegistry
    ) {}

    /**
     * Finds a record collection by uid.
     *
     * @throws Exception\ResourceDoesNotExistException
     */
    public function findByUid(int $uid): ?CollectionInterface
    {
        $object = null;
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_file_collection');
        if ($this->isFrontendRequest()) {
            $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
        } else {
            $queryBuilder->getRestrictions()->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        }
        $data = $queryBuilder->select('*')
            ->from('sys_file_collection')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchAssociative();
        if (is_array($data)) {
            $object = $this->createDomainObject($data);
        }
        if ($object === null) {
            throw new ResourceDoesNotExistException('Could not find row with uid "' . $uid . '" in table "sys_file_collection"', 1314354066);
        }
        return $object;
    }

    /**
     * Finds record collection by type.
     *
     * @return CollectionInterface[]|null
     */
    public function findByType(string $type): ?array
    {
        $expressionBuilder = $this->connectionPool->getQueryBuilderForTable('sys_file_collection')->expr();
        return $this->queryMultipleRecords([
            $expressionBuilder->eq('type', $expressionBuilder->literal($type)),
        ]);
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

    /**
     * Queries for multiple records for the given conditions.
     *
     * @param array $conditions Conditions concatenated with AND for query
     * @return CollectionInterface[]|null
     */
    protected function queryMultipleRecords(array $conditions = []): ?array
    {
        $result = null;
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_file_collection');
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $queryBuilder->select('*')->from('sys_file_collection');
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

    protected function isFrontendRequest(): bool
    {
        if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend()
        ) {
            return true;
        }
        return false;
    }

    /**
     * Creates a record collection domain object.
     *
     * @param array $record Database record to be reconstituted
     */
    protected function createDomainObject(array $record): CollectionInterface
    {
        /** @var AbstractRecordCollection $className */
        $className = $this->fileCollectionRegistry->getFileCollectionClass($record['type']);
        return $className::create($record);
    }
}
