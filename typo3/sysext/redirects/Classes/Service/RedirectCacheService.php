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

namespace TYPO3\CMS\Redirects\Service;

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Ensure to clear the cache entry when a sys_redirect record is modified, also the main pool
 * for getting all redirects.
 *
 * @internal
 */
class RedirectCacheService
{
    /**
     * @var FrontendInterface
     */
    protected $cache;

    public function __construct(CacheManager $cacheManager = null)
    {
        $cacheManager = $cacheManager ?? GeneralUtility::makeInstance(CacheManager::class);
        $this->cache = $cacheManager->getCache('pages');
    }

    /**
     * Fetches all redirects available to the system, grouped by domain and regexp/nonregexp
     */
    public function getRedirects(string $sourceHost): array
    {
        $redirects = $this->cache->get($this->buildCacheIdentifier($sourceHost));
        // empty array is considered as valid cache, so we need to check for array type here.
        if (!is_array($redirects)) {
            $redirects = $this->rebuildForHost($sourceHost);
        }
        return $redirects;
    }

    /**
     * Rebuilds the cache for all redirects, grouped by host as well as by regular expressions and respect_query_parameters.
     * Does not include hidden or deleted redirects, but includes the ones with dynamic starttime/endtime.
     */
    public function rebuildForHost(string $sourceHost): array
    {
        $redirects = [];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_redirect');
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(HiddenRestriction::class))
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $queryBuilder
            ->select('*')
            ->from('sys_redirect');

        if ($sourceHost === '' || $sourceHost === '*') {
            $queryBuilder->where(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq('source_host', $queryBuilder->createNamedParameter('')),
                    $queryBuilder->expr()->eq('source_host', $queryBuilder->createNamedParameter('*')),
                )
            );
        } else {
            $queryBuilder->where(
                $queryBuilder->expr()->in('source_host', $queryBuilder->createNamedParameter($sourceHost))
            );
        }
        $statement = $queryBuilder->executeQuery();
        while ($row = $statement->fetchAssociative()) {
            if ($row['is_regexp']) {
                $redirects['regexp'][$row['source_path']][$row['uid']] = $row;
            } elseif ($row['respect_query_parameters']) {
                $redirects['respect_query_parameters'][$row['source_path']][$row['uid']] = $row;
            } else {
                $redirects['flat'][rtrim($row['source_path'], '/') . '/'][$row['uid']] = $row;
            }
        }
        $this->cache->set($this->buildCacheIdentifier($sourceHost), $redirects);
        return $redirects;
    }

    /**
     * Rebuild cache for each distinct redirect source_host.
     */
    public function rebuildAll(): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_redirect');
        // remove all restriction, as we need to retrieve the source host even for hidden or deleted redirects.
        $queryBuilder->getRestrictions()->removeAll();
        $resultSet = $queryBuilder
            ->select('source_host')
            ->distinct()
            ->from('sys_redirect')
            ->executeQuery();
        while ($row = $resultSet->fetchAssociative()) {
            $this->rebuildForHost($row['source_host'] ?? '*');
        }
    }

    private function buildCacheIdentifier(string $sourceHost): string
    {
        return 'redirects_' . sha1($sourceHost);
    }
}
