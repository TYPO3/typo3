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

namespace TYPO3\CMS\Extbase\Service;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/**
 * Cache clearing helper functions
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class CacheService implements SingletonInterface
{
    protected array $clearCacheForTables = [];
    protected \SplStack $cacheTagStack;

    public function __construct(
        private readonly ConfigurationManagerInterface $configurationManager,
        private readonly CacheManager $cacheManager,
        private readonly ConnectionPool $connectionPool,
    ) {
        $this->cacheTagStack = new \SplStack();
    }

    /**
     * @deprecated since TYPO3 v13, will be removed in TYPO3 v14. Use CacheService->getCacheTagStack() instead.
     * Push cache tags directly instead of page IDs.
     */
    public function getPageIdStack(): \SplStack
    {
        return $this->cacheTagStack;
    }

    public function getCacheTagStack(): \SplStack
    {
        return $this->cacheTagStack;
    }

    /**
     * Clears the page cache
     *
     * @param int|int[] $pageIdsToClear single or multiple pageIds to clear the cache for
     */
    public function clearPageCache($pageIdsToClear = null): void
    {
        if ($pageIdsToClear === null) {
            $this->cacheManager->flushCachesInGroup('pages');
        } else {
            if (!is_array($pageIdsToClear)) {
                $pageIdsToClear = [(int)$pageIdsToClear];
            }
            $tags = array_map(static fn(int $item): string => 'pageId_' . $item, $pageIdsToClear);
            $this->cacheManager->flushCachesInGroupByTags('pages', $tags);
        }
    }

    /**
     * First, this method checks, if any records are registered (usually via Database Backend)
     * to be analyzed for a page record, if so, adds additional page IDs to the pageIdStack.
     *
     * Walks through the pageIdStack, collects all pageIds
     * as array and passes them on to clearPageCache.
     */
    public function clearCachesOfRegisteredPageIds(): void
    {
        $frameworkConfiguration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        if (!empty($frameworkConfiguration['persistence']['enableAutomaticCacheClearing'] ?? false)) {
            foreach ($this->clearCacheForTables as $table => $ids) {
                foreach ($ids as $id) {
                    $this->clearPageCacheForGivenRecord($table, $id);
                }
            }
        }
        if (!$this->cacheTagStack->isEmpty()) {
            $cacheTags = [];
            while (!$this->cacheTagStack->isEmpty()) {
                $cacheTagValue = $this->cacheTagStack->pop();
                // Add fallback to old behavior. Pushing pageIds directly to the stack is possible. So we need to handle int values as well.
                $cacheTags[] = is_int($cacheTagValue) ? sprintf('pageId_%s', $cacheTagValue) : (string)$cacheTagValue;
            }
            $cacheTags = array_values(array_unique($cacheTags));
            $this->cacheManager->flushCachesInGroupByTags('pages', $cacheTags);
        }
    }

    /**
     * Stores a record into the stack to resolve the page IDs later-on to clear the caches on these pages
     * then.
     *
     * Make sure to call clearCachesOfRegisteredPageIds() afterwards.
     *
     * @param string $table
     * @param int $uid
     */
    public function clearCacheForRecord(string $table, int $uid): void
    {
        if (!is_array($this->clearCacheForTables[$table] ?? null)) {
            $this->clearCacheForTables[$table] = [];
        }
        $this->clearCacheForTables[$table][] = $uid;
    }

    /**
     * Finds the right PID(s) of a given record and loads the TYPO3 page cache for the given record.
     * If the record lies on a page, then we clear the cache of this page.
     * If the record has no PID column, we clear the cache of the current page as best-effort.
     *
     * Much of this functionality is taken from DataHandler::clear_cache() which unfortunately only works with logged-in BE user.
     *
     * @param string $tableName Table name of the record
     * @param int $uid UID of the record
     */
    protected function clearPageCacheForGivenRecord(string $tableName, int $uid): void
    {
        $pageIdsToClear = [];
        $storagePage = null;

        $this->getCacheTagStack()->push($tableName);
        $this->getCacheTagStack()->push(sprintf('%s_%s', $tableName, $uid));
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll();
        $result = $queryBuilder
            ->select('pid')
            ->from($tableName)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                )
            )
            ->executeQuery();
        if ($row = $result->fetchAssociative()) {
            $storagePage = $row['pid'];
            $pageIdsToClear[] = $storagePage;
        }
        if ($storagePage === null) {
            return;
        }

        $pageTS = BackendUtility::getPagesTSconfig($storagePage);
        if (isset($pageTS['TCEMAIN.']['clearCacheCmd'])) {
            $clearCacheCommands = GeneralUtility::trimExplode(',', strtolower((string)$pageTS['TCEMAIN.']['clearCacheCmd']), true);
            $clearCacheCommands = array_unique($clearCacheCommands);
            foreach ($clearCacheCommands as $clearCacheCommand) {
                if (MathUtility::canBeInterpretedAsInteger($clearCacheCommand)) {
                    $pageIdsToClear[] = $clearCacheCommand;
                }
            }
        }

        foreach ($pageIdsToClear as $pageIdToClear) {
            $this->getCacheTagStack()->push('pageId_' . $pageIdToClear);
            $this->getCacheTagStack()->push(sprintf('%s_pid_%s', $tableName, $pageIdToClear));
        }
    }
}
