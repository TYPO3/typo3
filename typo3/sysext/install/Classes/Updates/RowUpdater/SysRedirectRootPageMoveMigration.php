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

namespace TYPO3\CMS\Install\Updates\RowUpdater;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;

/**
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class SysRedirectRootPageMoveMigration implements RowUpdaterInterface
{
    private const TABLE_NAME = 'sys_redirect';

    private array $rootPageIds;

    public function __construct(
        private ConnectionPool $connectionPool,
        private SiteFinder $siteFinder
    ) {
        $this->rootPageIds = $this->getRootPageIds();
    }

    public function getTitle(): string
    {
        return 'Move "sys_redirect" records to site config root pages.';
    }

    public function hasPotentialUpdateForTable(string $tableName): bool
    {
        if ($tableName !== self::TABLE_NAME || !$this->sysRedirectsTableExists()) {
            return false;
        }
        $rootPageIds = $this->getRootPageIds();
        $queryBuilder = $this->getPreparedQueryBuilder();
        $andWhereConditions = $queryBuilder->expr()->and($queryBuilder->expr()->neq('pid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)));
        if ($rootPageIds !== []) {
            $andWhereConditions = $andWhereConditions->with($queryBuilder->expr()->notIn('pid', $queryBuilder->createNamedParameter($rootPageIds, Connection::PARAM_INT_ARRAY)));
        }
        $numberOfNonRootPageRedirectRecords = (int)$queryBuilder
            ->count('uid')
            ->from(self::TABLE_NAME)
            ->where($andWhereConditions)
            ->executeQuery()
            ->fetchOne();
        return $numberOfNonRootPageRedirectRecords > 0;
    }

    public function updateTableRow(string $tableName, array $row): array
    {
        if ($tableName !== self::TABLE_NAME) {
            return $row;
        }
        $pageId = (int)$row['pid'];
        if ($pageId === 0 || in_array($pageId, $this->rootPageIds, true)) {
            return $row;
        }
        try {
            $rootPageId = $this->siteFinder->getSiteByPageId($pageId)->getRootPageId();
        } catch (SiteNotFoundException) {
            // Move redirects without proper site config root page to top tree page.
            $rootPageId = 0;
        }
        if ($rootPageId !== $pageId) {
            $row['pid'] = $rootPageId;
        }
        return $row;
    }

    private function getRootPageIds(): array
    {
        $rootPageIds = [];
        $sites = $this->siteFinder->getAllSites();
        foreach ($sites as $site) {
            if (!in_array($site->getRootPageId(), $rootPageIds, true)) {
                $rootPageIds[] = $site->getRootPageId();
            }
        }
        return $rootPageIds;
    }

    private function sysRedirectsTableExists(): bool
    {
        $schemaManager = $this->connectionPool->getConnectionForTable(self::TABLE_NAME)->createSchemaManager();
        return $schemaManager->tablesExist(self::TABLE_NAME);
    }

    private function getPreparedQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->getRestrictions()->removeAll();
        return $queryBuilder;
    }
}
