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
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_log');

        $logEntries = $queryBuilder
            ->select('uid', 'tstamp', 'userid', 'details')
            ->from('sys_log')
            ->where(
                $queryBuilder->expr()->eq(
                    'type',
                    $queryBuilder->createNamedParameter(SystemLogType::LOGIN, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'action',
                    $queryBuilder->createNamedParameter(SystemLogLoginAction::LOGIN, Connection::PARAM_INT)
                )
            )
            ->orderBy('tstamp', 'DESC')
            ->setMaxResults($limit)
            ->executeQuery()
            ->fetchAllAssociative();

        // Enrich with user information
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
}
