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

namespace TYPO3\CMS\Belog\EventListener;

use TYPO3\CMS\Backend\Backend\Event\SystemInformationToolbarCollectorEvent;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Toolbar\InformationStatus;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageService;

/**
 * Count latest exceptions for the system information menu.
 *
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
final readonly class SystemInformationEventListener
{
    public function __construct(
        private ConnectionPool $connectionPool,
        private UriBuilder $uriBuilder
    ) {}

    /**
     * Modifies the SystemInformation toolbar to inject a new message
     * @throws RouteNotFoundException
     */
    #[AsEventListener('belog/show-latest-errors')]
    public function appendMessage(SystemInformationToolbarCollectorEvent $event): void
    {
        // we can't use the extbase repository here as the required TypoScript may not be parsed yet
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_log');
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
                ),
                $queryBuilder->expr()->eq(
                    'channel',
                    $queryBuilder->createNamedParameter('php', Connection::PARAM_STR)
                )
            )
            ->executeQuery()
            ->fetchOne();

        if ($count > 0) {
            $moduleIdentifier = 'system_log';
            $moduleParams = ['constraint' => ['channel' => 'php']];
            $text = $this->getLanguageService()->translate(
                'systemmessage.errorsInPeriod',
                'belog.messages',
                [
                    $count,
                    (string)$this->uriBuilder->buildUriFromRoute($moduleIdentifier, $moduleParams),
                ]
            );
            $systemInformationToolbarItem = $event->getToolbarItem();
            $systemInformationToolbarItem->addSystemMessage(
                $text,
                InformationStatus::ERROR,
                $count,
                $moduleIdentifier,
                http_build_query($moduleParams)
            );
        }
    }

    private function fetchLastAccessTimestamp(): int
    {
        if (!isset($this->getBackendUser()->uc['systeminformation'])) {
            return 0;
        }
        $systemInformationUc = json_decode($this->getBackendUser()->uc['systeminformation'], true, 512, JSON_THROW_ON_ERROR);
        return (int)($systemInformationUc['system_log']['lastAccess'] ?? 0);
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
