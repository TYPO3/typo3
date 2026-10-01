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

namespace TYPO3\CMS\Dashboard\Widgets\Provider;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Pagination\PaginatorInterface;
use TYPO3\CMS\Core\Pagination\QueryBuilderPaginator;
use TYPO3\CMS\Core\SysLog\Action\Login as SystemLogLoginAction;
use TYPO3\CMS\Core\SysLog\Type as SystemLogType;

/**
 * Data provider for "Latest backend logins" widget.
 * Fetches successful backend user login entries from sys_log.
 */
readonly class LatestBeLoginsDataProvider
{
    public function __construct(
        private ConnectionPool $connectionPool
    ) {}

    public function getItems(int $limit = 10): array
    {
        $logEntries = $this->createQueryBuilder()
            ->setMaxResults($limit)
            ->executeQuery()
            ->fetchAllAssociative();

        return $this->enrichLogEntries($logEntries);
    }

    /**
     * Provides the successful backend logins, newest first. The paginated items are raw
     * log entries, which can be turned into widget items via enrichLogEntries().
     */
    public function getPaginator(int $currentPage, int $itemsPerPage): PaginatorInterface
    {
        return new QueryBuilderPaginator($this->createQueryBuilder(), $currentPage, $itemsPerPage);
    }

    /**
     * Enriches log entries with the information of the backend users. Entries of
     * unknown (e.g. deleted) users are omitted.
     */
    public function enrichLogEntries(iterable $logEntries): array
    {
        $items = [];
        $userNames = BackendUtility::getUserNames('username,realName,uid');

        foreach ($logEntries as $entry) {
            $userId = (int)$entry['userid'];
            $userInfo = $userNames[$userId] ?? null;
            if ($userInfo !== null) {
                $items[] = [
                    'timestamp' => (int)$entry['tstamp'],
                    'userId' => $userId,
                    'username' => $userInfo['username'] ?? '',
                    'realName' => $userInfo['realName'] ?? '',
                    'details' => $entry['details'],
                ];
            }
        }

        return $items;
    }

    private function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_log');
        // Only logins of existing users are shown, matching BackendUtility::getUserNames()
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());

        return $queryBuilder
            ->select('sys_log.uid', 'sys_log.tstamp', 'sys_log.userid', 'sys_log.details')
            ->from('sys_log')
            ->join(
                'sys_log',
                'be_users',
                'be_users',
                (string)$queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq('be_users.uid', $queryBuilder->quoteIdentifier('sys_log.userid')),
                    $queryBuilder->expr()->eq('be_users.pid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
                )
            )
            ->where(
                $queryBuilder->expr()->eq(
                    'sys_log.type',
                    $queryBuilder->createNamedParameter(SystemLogType::LOGIN, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'sys_log.action',
                    $queryBuilder->createNamedParameter(SystemLogLoginAction::LOGIN, Connection::PARAM_INT)
                )
            )
            ->orderBy('sys_log.tstamp', 'DESC')
            ->addOrderBy('sys_log.uid', 'DESC');
    }
}
