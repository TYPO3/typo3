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

namespace TYPO3\CMS\Form\Domain\Repository;

use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\DTO\FormData;
use TYPO3\CMS\Form\Domain\DTO\SearchCriteria;
use TYPO3\CMS\Form\Storage\JsonObjectKeyOrderPreserver;
use TYPO3\CMS\Form\Storage\Security\FormDefinitionPersistenceCommand;
use TYPO3\CMS\Form\Storage\Security\FormDefinitionPersistenceGuard;

/**
 * Repository class to fetch available form definitions.
 *
 * @internal not part of public TYPO3 Core API
 */
readonly class FormDefinitionRepository
{
    public const TABLE_NAME = 'form_definition';

    public function __construct(
        private ConnectionPool $connectionPool,
        private FormDefinitionPersistenceGuard $persistenceGuard,
        private JsonObjectKeyOrderPreserver $jsonObjectKeyOrderPreserver,
    ) {}

    /**
     * Find a form definition by its uid.
     * Returns the database row as array or null if not found.
     */
    public function findByUid(int $uid): ?array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);

        $row = $queryBuilder
            ->select('*')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, ParameterType::INTEGER))
            )
            ->executeQuery()
            ->fetchAssociative();

        return $row ?: null;
    }

    /**
     * Find all form definitions with optional search criteria.
     * Returns an array of database rows including full configuration.
     */
    public function findAll(SearchCriteria $criteria): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);

        $queryBuilder
            ->select('*')
            ->from(self::TABLE_NAME);

        $this->applySearchCriteria($queryBuilder, $criteria);

        return $queryBuilder
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * Find all form definitions for listing purposes.
     * Returns only metadata columns (uid, pid, identifier, label) without
     * the potentially large configuration JSON column.
     *
     * @return array<int, array{uid: int, pid: int, identifier: string, label: string}>
     */
    public function findAllForListing(SearchCriteria $criteria): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);

        $queryBuilder
            ->select('uid', 'pid', 'identifier', 'label')
            ->from(self::TABLE_NAME);

        $this->applySearchCriteria($queryBuilder, $criteria);

        return $queryBuilder
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * Check if a form definition with the given form identifier exists.
     * Uses a COUNT query on the indexed identifier column for efficiency.
     */
    public function existsByFormIdentifier(string $formIdentifier): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);

        $count = $queryBuilder
            ->count('uid')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq(
                    'identifier',
                    $queryBuilder->createNamedParameter($formIdentifier)
                )
            )
            ->executeQuery()
            ->fetchOne();

        return (int)$count > 0;
    }

    /**
     * Find the UID of a form definition by its form identifier.
     *
     * Returns the UID of the first matching non-deleted record, or null if not found.
     * Used by the upgrade wizard to check for already-migrated forms.
     */
    public function findUidByFormIdentifier(string $formIdentifier): ?int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);

        $uid = $queryBuilder
            ->select('uid')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq(
                    'identifier',
                    $queryBuilder->createNamedParameter($formIdentifier)
                )
            )
            ->executeQuery()
            ->fetchOne();

        return $uid !== false ? (int)$uid : null;
    }

    /**
     * Apply search criteria (search term, limit) to a query builder.
     */
    private function applySearchCriteria(QueryBuilder $queryBuilder, SearchCriteria $criteria): void
    {
        if (!empty($criteria->searchTerm)) {
            $queryBuilder->where(
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->like(
                        'identifier',
                        $queryBuilder->createNamedParameter('%' . $queryBuilder->escapeLikeWildcards($criteria->searchTerm) . '%')
                    ),
                    $queryBuilder->expr()->like(
                        'label',
                        $queryBuilder->createNamedParameter('%' . $queryBuilder->escapeLikeWildcards($criteria->searchTerm) . '%')
                    )
                )
            );
        }

        if ($criteria->hasLimit()) {
            $queryBuilder->setMaxResults($criteria->limit);
        }
    }

    /**
     * Insert a form definition in the database.
     * @return int|null UID of the newly created record or null on failure.
     * @throws \JsonException
     */
    public function add(string $persistenceIdentifier, int $pid, FormData $formDefinition): ?int
    {
        $formDefinitionJson = json_encode($this->jsonObjectKeyOrderPreserver->protect($formDefinition->toArray()), JSON_THROW_ON_ERROR);
        $fields = [
            'pid' => $pid,
            'label' => $formDefinition->name,
            'identifier' => $formDefinition->identifier,
            'configuration' => $formDefinitionJson,
        ];

        $this->persistenceGuard->allowInvocation(FormDefinitionPersistenceCommand::Create, $persistenceIdentifier, $fields);
        /** @var DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([self::TABLE_NAME => [$persistenceIdentifier => $fields]], []);
        try {
            $dataHandler->process_datamap();
        } finally {
            $this->persistenceGuard->consumeInvocation(FormDefinitionPersistenceCommand::Create, $persistenceIdentifier, $fields);
        }

        if ($dataHandler->errorLog !== []) {
            return null;
        }

        return isset($dataHandler->substNEWwithIDs[$persistenceIdentifier])
            ? (int)$dataHandler->substNEWwithIDs[$persistenceIdentifier]
            : null;
    }

    /**
     * Insert a form definition using a raw database insert (no DataHandler).
     *
     * This method is intended for contexts where DataHandler cannot be used,
     * e.g. the Install Tool upgrade wizard where no backend user is available.
     *
     * @return int|null The UID of the newly created record, or null on failure
     */
    public function addRaw(int $pid, FormData $formDefinition): ?int
    {
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE_NAME);
        $connection->insert(self::TABLE_NAME, [
            'pid' => $pid,
            'deleted' => 0,
            'label' => $formDefinition->name,
            'identifier' => $formDefinition->identifier,
            'configuration' => $this->jsonObjectKeyOrderPreserver->protect($formDefinition->toArray()),
            'crdate' => $GLOBALS['EXEC_TIME'] ?? time(),
            'tstamp' => $GLOBALS['EXEC_TIME'] ?? time(),
        ]);
        $uid = (int)$connection->lastInsertId();
        return $uid > 0 ? $uid : null;
    }

    /**
     * Removes a form definition completely from the system using DataHandler.
     *
     * @param int $uid The UID representing the form definition to delete
     * @return bool TRUE if the form definition was successfully deleted, FALSE otherwise
     */
    public function remove(int $uid): bool
    {
        $this->persistenceGuard->allowInvocation(FormDefinitionPersistenceCommand::Delete, $uid);
        /** @var DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], [self::TABLE_NAME => [$uid => ['delete' => 1]]]);
        try {
            $dataHandler->process_cmdmap();
        } finally {
            $this->persistenceGuard->consumeInvocation(FormDefinitionPersistenceCommand::Delete, $uid);
        }
        return $dataHandler->errorLog === [];
    }

    /**
     * Update a form definition in the database using DataHandler.
     *
     * @param int $uid The UID of the form definition to update
     * @param FormData $formDefinition
     * @return bool TRUE if update was successful, FALSE otherwise
     * @throws \JsonException
     */
    public function update(int $uid, FormData $formDefinition): bool
    {
        if (empty($uid)) {
            return false;
        }

        $formDefinitionJson = json_encode($this->jsonObjectKeyOrderPreserver->protect($formDefinition->toArray()), JSON_THROW_ON_ERROR);
        $fields = [
            'label' => $formDefinition->name,
            'identifier' => $formDefinition->identifier,
            'configuration' => $formDefinitionJson,
        ];

        $this->persistenceGuard->allowInvocation(FormDefinitionPersistenceCommand::Update, $uid, $fields);
        /** @var DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([self::TABLE_NAME => [$uid => $fields]], []);
        try {
            $dataHandler->process_datamap();
        } finally {
            $this->persistenceGuard->consumeInvocation(FormDefinitionPersistenceCommand::Update, $uid, $fields);
        }

        return $dataHandler->errorLog === [];
    }
}
