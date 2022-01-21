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

namespace TYPO3\CMS\Redirects\Hooks;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Redirects\Service\RedirectCacheService;

/**
 * Ensure to clear the cache entry when a sys_redirect record is modified or deleted
 * @internal This class is a specific TYPO3 hook implementation and is not part of the Public TYPO3 API.
 */
class DataHandlerCacheFlushingHook
{
    /**
     * Check if the data handler processed a sys_redirect record, if so, rebuild the redirect index cache
     *
     * @todo This hook is called for each record which needs to clear cache, which means this gets called
     *       for other records than sys_redirects, but also for each sys_redirect record which has been
     *       modified with this DataHandler call. Even if we can narrow down to rebuild only for specific
     *       source_hosts, this still means that we eventually rebuild the "same" cache multiple times.
     *       Find a better way to aggregate them and rebuild only once at the end.
     */
    public function rebuildRedirectCacheIfNecessary(array $parameters, DataHandler $dataHandler): void
    {
        if (
            ($parameters['table'] ?? false) !== 'sys_redirect'
            || !($parameters['uid'] ?? false)
            || (
                !isset($dataHandler->datamap['sys_redirect'][(int)$parameters['uid']])
                && !isset($dataHandler->cmdmap['sys_redirect'][(int)$parameters['uid']])
            )
        ) {
            return;
        }

        $redirectCacheService = GeneralUtility::makeInstance(RedirectCacheService::class);
        $sourceHosts = [];
        if (isset($dataHandler->getHistoryRecords()['sys_redirect:' . (int)$parameters['uid']]['oldRecord']['source_host'])) {
            $sourceHosts[] = $dataHandler->getHistoryRecords()['sys_redirect:' . (int)$parameters['uid']]['oldRecord']['source_host'];
        }
        if (isset($dataHandler->getHistoryRecords()['sys_redirect:' . (int)$parameters['uid']]['newRecord']['source_host'])) {
            $sourceHosts[] = $dataHandler->getHistoryRecords()['sys_redirect:' . (int)$parameters['uid']]['newRecord']['source_host'];
        }
        // only do record lookup for delete cmd, otherwise we cannot get old and new source_host,
        // thus rebuildAll() should be executed as a safety net anyway.
        if ($sourceHosts === [] && isset($dataHandler->cmdmap['sys_redirect'][(int)$parameters['uid']])) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_redirect');
            $queryBuilder->getRestrictions()->removeAll();
            $row = $queryBuilder
                ->select('source_host')
                ->from('sys_redirect')
                ->where(
                    $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($parameters['uid'], Connection::PARAM_INT))
                )
                ->executeQuery()
                ->fetchAssociative();

            if (isset($row['source_host'])) {
                $sourceHosts[] = $row['source_host'] ?: '*';
            }
        }

        // rebuild only specific source_host redirect caches
        if ($sourceHosts !== []) {
            foreach (array_unique($sourceHosts) as $sourceHost) {
                $redirectCacheService->rebuildForHost($sourceHost);
            }
            return;
        }

        // Hopefully we get distinct source_host before. However, rebuild all redirect caches as a safety fallback.
        $redirectCacheService->rebuildAll();
    }
}
