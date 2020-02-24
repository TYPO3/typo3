<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Dashboard\Widgets;

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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\SysLog\Action\Login as SystemLogLoginAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\SysLog\Type as SystemLogType;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This widget will show the number of failed logins during a given period
 */
class FailedLoginsWidget extends AbstractNumberWithIconWidget
{
    protected $title = 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.failedLogins.title';
    protected $description = 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.failedLogins.description';
    protected $subtitle = 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.failedLogins.subtitle';
    protected $icon = 'content-elements-login';

    protected function initializeView(): void
    {
        $this->number = $this->getNumberOfFailedLogins();
        parent::initializeView();
    }

    public function getNumberOfFailedLogins(int $secondsBack = 86400): int
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('sys_log');

        return (int)$queryBuilder->count('uid')
            ->from('sys_log')
            ->where(
                $queryBuilder->expr()->eq(
                    'type',
                    $queryBuilder->createNamedParameter(SystemLogType::LOGIN, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'action',
                    $queryBuilder->createNamedParameter(SystemLogLoginAction::ATTEMPT, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->neq(
                    'error',
                    $queryBuilder->createNamedParameter(SystemLogErrorClassification::MESSAGE, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->gt(
                    'tstamp',
                    $queryBuilder->createNamedParameter($GLOBALS['EXEC_TIME'] - $secondsBack, Connection::PARAM_INT)
                )
            )
            ->execute()
            ->fetchColumn();
    }
}
