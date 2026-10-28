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

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Redirects\Service\RedirectCacheService;

/**
 * Rebuilds the redirect cache of the source hosts of changed sys_redirect
 * records, including the previous source host of a record, once per host.
 *
 * @internal This class is a specific TYPO3 hook implementation and is not part of the Public TYPO3 API.
 */
#[Autoconfigure(public: true)]
final readonly class DataHandlerRedirectCacheHook
{
    public function __construct(
        private RedirectCacheService $redirectCacheService,
        private ConnectionPool $connectionPool,
    ) {}

    public function processDatamap_afterAllOperations(DataHandler $dataHandler): void
    {
        $uids = [];
        foreach (array_keys($dataHandler->datamap['sys_redirect'] ?? []) as $id) {
            $uid = MathUtility::canBeInterpretedAsInteger($id) ? (int)$id : (int)($dataHandler->substNEWwithIDs[$id] ?? 0);
            if ($uid > 0) {
                $uids[] = $uid;
            }
        }
        $this->rebuildRedirectCache($uids, $dataHandler);
    }

    public function processCmdmap_afterFinish(DataHandler $dataHandler): void
    {
        $this->rebuildRedirectCache(array_map(intval(...), array_keys($dataHandler->cmdmap['sys_redirect'] ?? [])), $dataHandler);
    }

    /**
     * @param int[] $uids
     */
    private function rebuildRedirectCache(array $uids, DataHandler $dataHandler): void
    {
        if ($uids === []) {
            return;
        }
        $sourceHosts = $this->getSourceHosts($uids);
        foreach ($uids as $uid) {
            $previousSourceHost = $dataHandler->getHistoryRecords()['sys_redirect:' . $uid]['oldRecord']['source_host'] ?? null;
            if ($previousSourceHost !== null) {
                $sourceHosts[] = (string)$previousSourceHost;
            }
        }
        $sourceHosts = array_map(static fn(string $sourceHost): string => $sourceHost === '' ? '*' : $sourceHost, $sourceHosts);
        foreach (array_unique($sourceHosts) as $sourceHost) {
            $this->redirectCacheService->rebuildForHost($sourceHost);
        }
    }

    /**
     * @param int[] $uids
     * @return string[]
     */
    private function getSourceHosts(array $uids): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_redirect');
        $queryBuilder->getRestrictions()->removeAll();
        return array_map(strval(...), $queryBuilder
            ->select('source_host')
            ->from('sys_redirect')
            ->where(
                $queryBuilder->expr()->in('uid', $queryBuilder->createNamedParameter($uids, Connection::PARAM_INT_ARRAY))
            )
            ->executeQuery()
            ->fetchFirstColumn());
    }
}
