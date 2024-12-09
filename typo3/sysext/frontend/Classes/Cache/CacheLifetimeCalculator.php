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

namespace TYPO3\CMS\Frontend\Cache;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Event\ModifyCacheLifetimeForPageEvent;
use TYPO3\CMS\Frontend\Event\ModifyCacheLifetimeForRowEvent;

/**
 * Calculates the max lifetime the given page should be stored in TYPO3's page cache.
 *
 * The "lifetime" is the number of seconds from the current time, it is not a full time/timestamp
 * Example: If the lifetime is "3600" (=1h), the page will be cached for 1h.
 *
 * @internal This class is not part of the TYPO3 Core API
 */
#[Autoconfigure(public: true)]
class CacheLifetimeCalculator
{
    protected const defaultCacheTimeout = 86400;

    public function __construct(
        #[Autowire(service: 'cache.runtime')]
        protected readonly FrontendInterface $runtimeCache,
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly ConnectionPool $connectionPool
    ) {}

    /**
     * Get the cache lifetime in seconds for the given record.
     */
    public function calculateLifetimeForRow(string $tableName, array $record, int $defaultCacheTimoutInSeconds = 0): int
    {
        $cachedCacheLifetimeIdentifier = sprintf('calculateLifetimeForRow_%s_%d', $tableName, ($record['uid'] ?? 0));
        $cachedCacheLifetime = $this->runtimeCache->get($cachedCacheLifetimeIdentifier);
        if ($cachedCacheLifetime !== false) {
            return $cachedCacheLifetime;
        }

        $cacheTimeout = $defaultCacheTimoutInSeconds ?: self::defaultCacheTimeout;

        // If the record has a starttime or endtime, we have to adjust the cache timeout
        foreach (['starttime', 'endtime'] as $field) {
            if (isset($GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns'][$field])) {
                $timeField = $GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns'][$field];

                if (array_key_exists($timeField, $record) && $record[$timeField] > 0 && ((int)$record[$timeField] - $GLOBALS['ACCESS_TIME']) > 0) {
                    $cacheTimeout = min($cacheTimeout, (int)$record[$timeField] - $GLOBALS['ACCESS_TIME']);
                }
            }
        }

        // Get the time, rounded to the minute (do not pollute MySQL cache!)
        // It is ok that we do not take seconds into account here because this
        // value will be subtracted later. So we never get the time "before"
        // the cache change.
        $currentTimestamp = (int)$GLOBALS['ACCESS_TIME'];
        $cacheTimeout = min($currentTimestamp, $cacheTimeout);

        $event = new ModifyCacheLifetimeForRowEvent(
            $cacheTimeout,
            $tableName,
            $record
        );
        $event = $this->eventDispatcher->dispatch($event);
        $this->runtimeCache->set($cachedCacheLifetimeIdentifier, $event->cacheLifetime);
        return $cacheTimeout;
    }

    /**
     * Get the cache lifetime in seconds for the given page.
     */
    public function calculateLifetimeForPage(int $pageId, array $pageRecord, array $renderingInstructions, int $defaultCacheTimoutInSeconds, Context $context): int
    {
        $cachedCacheLifetimeIdentifier = 'cacheLifeTimeForPage_' . $pageId;
        $cachedCacheLifetime = $this->runtimeCache->get($cachedCacheLifetimeIdentifier);
        if ($cachedCacheLifetime !== false) {
            return $cachedCacheLifetime;
        }
        if ($pageRecord['cache_timeout'] ?? false) {
            // Cache period was set for the page:
            $cacheTimeout = (int)$pageRecord['cache_timeout'];
        } else {
            // Cache period was set via TypoScript "config.cache_period",
            // otherwise it's the default of 24 hours
            $cacheTimeout = $defaultCacheTimoutInSeconds ?: (int)($renderingInstructions['cache_period'] ?? self::defaultCacheTimeout);
        }

        $cacheTimeout = $this->calculateLifetimeForRow('pages', $pageRecord, $cacheTimeout);

        // Calculate the timeout time for records on the page and adjust cache timeout if necessary
        // Get the configuration
        $tablesToConsider = $this->getCurrentPageCacheConfiguration($pageId, $renderingInstructions);

        // Get the time, rounded to the minute (do not pollute MySQL cache!)
        // It is ok that we do not take seconds into account here because this
        // value will be subtracted later. So we never get the time "before"
        // the cache change.
        $currentTimestamp = (int)$GLOBALS['ACCESS_TIME'];
        $cacheTimeout = min($this->calculatePageCacheLifetime($tablesToConsider, $currentTimestamp), $cacheTimeout);

        $event = new ModifyCacheLifetimeForPageEvent(
            $cacheTimeout,
            $pageId,
            $pageRecord,
            $renderingInstructions,
            $context
        );
        $event = $this->eventDispatcher->dispatch($event);
        $cacheTimeout = $event->getCacheLifetime();
        $this->runtimeCache->set($cachedCacheLifetimeIdentifier, $cacheTimeout);
        return $cacheTimeout;
    }

    /**
     * Calculates page cache timeout according to the records with starttime/endtime on the page.
     *
     * @return int Page cache timeout or PHP_INT_MAX if the timeout cannot be determined
     */
    protected function calculatePageCacheLifetime(array $tablesToConsider, int $currentTimestamp): int
    {
        $result = PHP_INT_MAX;
        // Find timeout by checking every table
        foreach ($tablesToConsider as $tableDef) {
            $result = min($result, $this->getFirstTimeValueForRecord($tableDef, $currentTimestamp));
        }
        // We return + 1 second just to ensure that cache is definitely regenerated
        return $result === PHP_INT_MAX ? PHP_INT_MAX : $result - $currentTimestamp + 1;
    }

    /**
     * Obtains a list of table/pid pairs to consider for page caching.
     *
     * TS configuration looks like this:
     *
     * The cache lifetime of all pages takes starttime and endtime of news records of page 14 into account:
     * config.cache.all = tt_news:14
     *
     * The cache lifetime of the current page allows to take records (e.g. fe_users) into account:
     * config.cache.all = fe_users:current
     *
     * The cache lifetime of page 42 takes starttime and endtime of news records of page 15 and addresses of page 16 into account:
     * config.cache.42 = tt_news:15,tt_address:16
     *
     * @return array Array of 'tablename:pid' pairs. There is at least a current page id in the array
     * @see calculatePageCacheLifetime()
     */
    protected function getCurrentPageCacheConfiguration(int $currentPageId, array $renderingInstructions): array
    {
        $result = ['tt_content:' . $currentPageId];
        if (isset($renderingInstructions['cache.'][$currentPageId])) {
            $result = array_merge($result, GeneralUtility::trimExplode(',', str_replace(':current', ':' . $currentPageId, $renderingInstructions['cache.'][$currentPageId])));
        }
        if (isset($renderingInstructions['cache.']['all'])) {
            $result = array_merge($result, GeneralUtility::trimExplode(',', str_replace(':current', ':' . $currentPageId, $renderingInstructions['cache.']['all'])));
        }
        return array_unique($result);
    }

    /**
     * Find the minimum starttime or endtime value in the table and pid that is greater than the current time.
     *
     * @param string $tableDef Table definition (format tablename:pid)
     * @param int $currentTimestamp the UNIX timestamp of the current time
     * @throws \InvalidArgumentException
     * @return int Value of the next start/stop time or PHP_INT_MAX if not found
     * @see calculatePageCacheLifetime()
     */
    protected function getFirstTimeValueForRecord(string $tableDef, int $currentTimestamp): int
    {
        $result = PHP_INT_MAX;
        [$tableName, $pid] = GeneralUtility::trimExplode(':', $tableDef);
        if (empty($tableName) || empty($pid)) {
            throw new \InvalidArgumentException('Unexpected value for parameter $tableDef. Expected <tablename>:<pid>, got \'' . htmlspecialchars($tableDef) . '\'.', 1307190365);
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()
            ->removeByType(StartTimeRestriction::class)
            ->removeByType(EndTimeRestriction::class);
        $timeFields = [];
        $timeConditions = $queryBuilder->expr()->or();
        foreach (['starttime', 'endtime'] as $field) {
            if (isset($GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns'][$field])) {
                $timeFields[$field] = $GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns'][$field];
                $queryBuilder->addSelectLiteral(
                    'MIN('
                    . 'CASE WHEN '
                    . $queryBuilder->expr()->lte(
                        $timeFields[$field],
                        $queryBuilder->createNamedParameter($currentTimestamp, Connection::PARAM_INT)
                    )
                    . ' THEN NULL ELSE ' . $queryBuilder->quoteIdentifier($timeFields[$field]) . ' END'
                    . ') AS ' . $queryBuilder->quoteIdentifier($timeFields[$field])
                );
                $timeConditions = $timeConditions->with(
                    $queryBuilder->expr()->gt(
                        $timeFields[$field],
                        $queryBuilder->createNamedParameter($currentTimestamp, Connection::PARAM_INT)
                    )
                );
            }
        }

        // if starttime or endtime are defined, evaluate them
        if (!empty($timeFields)) {
            // find the timestamp, when the current page's content changes the next time
            $row = $queryBuilder
                ->from($tableName)
                ->where(
                    $queryBuilder->expr()->eq(
                        'pid',
                        $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT)
                    ),
                    $timeConditions
                )
                ->executeQuery()
                ->fetchAssociative();

            if ($row) {
                foreach ($timeFields as $timeField) {
                    // if a MIN value is found, take it into account for the
                    // cache lifetime we have to filter out start/endtimes < $currentTimestamp,
                    // as the SQL query also returns rows with starttime < $currentTimestamp
                    // and endtime > $currentTimestamp (and using a starttime from the past
                    // would be wrong)
                    if ($row[$timeField] !== null && (int)$row[$timeField] > $currentTimestamp) {
                        $result = min($result, (int)$row[$timeField]);
                    }
                }
            }
        }

        return $result;
    }
}
