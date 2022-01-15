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

namespace TYPO3\CMS\Belog\Controller;

use TYPO3\CMS\Backend\Backend\Event\SystemInformationToolbarCollectorEvent;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Toolbar\Enumeration\InformationStatus;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Count latest exceptions for the system information menu.
 *
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
final class SystemInformationController
{
    /**
     * @var array
     */
    protected $backendUserConfiguration;

    public function __construct(array $backendUserConfiguration = null)
    {
        $this->backendUserConfiguration = $backendUserConfiguration ?? $GLOBALS['BE_USER']->uc;
    }

    /**
     * Modifies the SystemInformation toolbar to inject a new message
     * @param SystemInformationToolbarCollectorEvent $event
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    public function appendMessage(SystemInformationToolbarCollectorEvent $event): void
    {
        $systemInformationToolbarItem = $event->getToolbarItem();
        // we can't use the extbase repository here as the required TypoScript may not be parsed yet
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_log');
        $count = $queryBuilder->count('error')
            ->from('sys_log')
            ->where(
                $queryBuilder->expr()->gte(
                    'tstamp',
                    $queryBuilder->createNamedParameter($this->fetchLastAccessTimestamp(), \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->in(
                    'error',
                    $queryBuilder->createNamedParameter([-1, 1, 2], Connection::PARAM_INT_ARRAY)
                )
            )
            ->executeQuery()
            ->fetchOne();

        if ($count > 0) {
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $systemInformationToolbarItem->addSystemMessage(
                sprintf(
                    LocalizationUtility::translate('systemmessage.errorsInPeriod', 'belog') ?? '',
                    $count,
                    (string)$uriBuilder->buildUriFromRoute(
                        'system_BelogLog',
                        ['tx_belog_system_beloglog' => ['constraint' => ['channel' => 'php']]]
                    )
                ),
                InformationStatus::STATUS_ERROR,
                $count,
                'system_BelogLog',
                http_build_query(['tx_belog_system_beloglog' => ['constraint' => ['channel' => 'php']]])
            );
        }
    }

    private function fetchLastAccessTimestamp(): int
    {
        if (!isset($this->backendUserConfiguration['systeminformation'])) {
            return 0;
        }
        $systemInformationUc = json_decode($this->backendUserConfiguration['systeminformation'], true);
        if (!isset($systemInformationUc['system_BelogLog']['lastAccess'])) {
            return 0;
        }

        return (int)$systemInformationUc['system_BelogLog']['lastAccess'];
    }
}
