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

namespace TYPO3\CMS\Core\Authentication;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DefaultRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\PageIdListRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\RootLevelRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Identity provider for local user records (be_users / fe_users): looks
 * them up applying the visibility constraints (deleted, disabled,
 * starttime, endtime) that previously lived as "enablecolumns"
 * configuration on the user authentication objects.
 *
 * Usernames are matched as the database collation compares them, which is
 * case-insensitive on MySQL and MariaDB and case-sensitive on PostgreSQL and
 * SQLite. A username lookup that matches more than one record by that
 * comparison is refused. Usernames are not normalized.
 *
 * @internal not part of TYPO3 Core API yet, still experimental as part of the
 *           authentication modernization groundwork
 */
#[Autoconfigure(public: true)]
final class LocalIdentityProvider
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function findBackendUserByUsername(string $username): ?array
    {
        if ($username === '') {
            return null;
        }
        $queryBuilder = $this->createQueryBuilder('be_users');
        $queryBuilder->where($queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter($username)));
        return $this->fetchUnique($queryBuilder);
    }

    public function findBackendUserByUid(int $uid): ?array
    {
        if ($uid <= 0) {
            return null;
        }
        $queryBuilder = $this->createQueryBuilder('be_users');
        $queryBuilder->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)));
        return $this->fetchUnique($queryBuilder);
    }

    /**
     * @param list<int>|null $storagePageIds the user record must be located in one of these
     *                                       pages, null means unrestricted; callers must state it
     */
    public function findFrontendUserByUsername(string $username, ?array $storagePageIds): ?array
    {
        if ($username === '' || $storagePageIds === []) {
            return null;
        }
        $queryBuilder = $this->createQueryBuilder('fe_users', $storagePageIds);
        $queryBuilder->where($queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter($username)));
        return $this->fetchUnique($queryBuilder);
    }

    public function findFrontendUserByUid(int $uid): ?array
    {
        if ($uid <= 0) {
            return null;
        }
        $queryBuilder = $this->createQueryBuilder('fe_users');
        $queryBuilder->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)));
        return $this->fetchUnique($queryBuilder);
    }

    /**
     * @param list<int>|null $storagePageIds
     */
    private function createQueryBuilder(string $table, ?array $storagePageIds = null): QueryBuilder
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $restrictions = GeneralUtility::makeInstance(DefaultRestrictionContainer::class);
        if ($table === 'be_users') {
            // Backend users always reside on the root level
            $restrictions->add(GeneralUtility::makeInstance(RootLevelRestriction::class, [$table]));
        } elseif ($storagePageIds !== null) {
            $restrictions->add(GeneralUtility::makeInstance(PageIdListRestriction::class, [$table], $storagePageIds));
        }
        $queryBuilder->setRestrictions($restrictions);
        return $queryBuilder->select('*')->from($table);
    }

    /**
     * Ambiguous username matches are refused rather than resolved to an
     * arbitrary record.
     */
    private function fetchUnique(QueryBuilder $queryBuilder): ?array
    {
        $records = $queryBuilder->setMaxResults(2)->executeQuery()->fetchAllAssociative();
        return count($records) === 1 ? $records[0] : null;
    }
}
