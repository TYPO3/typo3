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
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Toolbar\Enumeration\InformationStatus;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Count latest exceptions for the system information menu.
 *
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
final class SystemInformationController
{
    protected array $backendUserConfiguration;

    public function __construct(array $backendUserConfiguration = null)
    {
        $this->backendUserConfiguration = $backendUserConfiguration ?? $GLOBALS['BE_USER']->uc;
    }

    /**
     * Modifies the SystemInformation toolbar to inject a new message
     * @throws RouteNotFoundException
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
                    $queryBuilder->createNamedParameter($this->fetchLastAccessTimestamp(), Connection::PARAM_INT)
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
            $moduleIdentifier = 'system_BelogLog';
            $moduleParams = ['constraint' => ['channel' => 'php']];
            $systemInformationToolbarItem->addSystemMessage(
                sprintf(
                    $this->getLanguageService()->sL('LLL:EXT:belog/Resources/Private/Language/locallang.xlf:systemmessage.errorsInPeriod'),
                    $count,
                    (string)$uriBuilder->buildUriFromRoute($moduleIdentifier, $moduleParams)
                ),
                InformationStatus::STATUS_ERROR,
                $count,
                $moduleIdentifier,
                http_build_query($moduleParams)
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

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
