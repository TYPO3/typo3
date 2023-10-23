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

namespace TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting;

use Doctrine\DBAL\Exception\TableNotFoundException;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;

/**
 * @internal
 */
class ResolutionRepository
{
    protected const TABLE_NAME = 'sys_csp_resolution';

    public function __construct(protected readonly ConnectionPool $pool) {}

    /**
     * @return list<Resolution>
     */
    public function findAll(): array
    {
        $result = $this->getConnection()->select(
            ['*'],
            self::TABLE_NAME,
            [],
            [],
            ['created' => 'asc']
        );
        return array_map(
            static fn(array $row) => Resolution::fromArray($row),
            $result->fetchAllAssociative()
        );
    }

    /**
     * @return list<Resolution>
     */
    public function findByScope(Scope $scope): array
    {
        try {
            $result = $this->getConnection()->select(
                ['*'],
                self::TABLE_NAME,
                ['scope' => (string)$scope],
                [],
                ['created' => 'asc']
            );
        } catch (TableNotFoundException) {
            // We usually don't take care of non-existing table throughout the system.
            // This one however can happen when major upgrading TYPO3 and calling the
            // backend first time. It is fair to catch this case to prevent forcing admins
            // to unlock standalone install tool or to use cli to fix db schema.
            return [];
        }
        return array_map(
            static fn(array $row) => Resolution::fromArray($row),
            $result->fetchAllAssociative()
        );
    }

    public function findBySummary(string $summary): ?Resolution
    {
        if ($summary === '') {
            return null;
        }
        $result = $this->getConnection()->select(
            ['*'],
            self::TABLE_NAME,
            ['summary' => $summary]
        );
        $row = $result->fetchAssociative();
        if (empty($row)) {
            return null;
        }
        return Resolution::fromArray($row);
    }

    /**
     * @return list<Resolution>
     */
    public function findByIdentifier(string $identifier, bool $prefix = false): array
    {
        if ($identifier === '') {
            return [];
        }
        if ($prefix) {
            $queryBuilder = $this->pool->getQueryBuilderForTable(self::TABLE_NAME);
            $result = $queryBuilder
                ->select('*')
                ->from(self::TABLE_NAME)
                ->where($queryBuilder->expr()->like(
                    'mutation_identifier',
                    $queryBuilder->createNamedParameter($queryBuilder->escapeLikeWildcards($identifier) . '%')
                ))
                ->executeQuery();
        } else {
            $result = $this->getConnection()->select(
                ['*'],
                self::TABLE_NAME,
                ['mutation_identifier' => $identifier]
            );
        }
        return array_map(
            static fn(array $row) => Resolution::fromArray($row),
            $result->fetchAllAssociative()
        );
    }

    public function add(Resolution $resolution): bool
    {
        return $this->getConnection()->insert(
            self::TABLE_NAME,
            $resolution->toArray()
        ) === 1;
    }

    public function remove(string $summary): bool
    {
        return $this->getConnection()->delete(
            self::TABLE_NAME,
            ['summary' => $summary]
        ) === 1;
    }

    protected function getConnection(): Connection
    {
        return $this->pool->getConnectionForTable(self::TABLE_NAME);
    }
}
