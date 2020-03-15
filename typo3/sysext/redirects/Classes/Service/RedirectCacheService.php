<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Redirects\Service;

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

use TYPO3\CMS\Core\Cache\CacheManager;
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
     * @var \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface
     */
    protected $cache;

    /**
     * Constructor setting up the cache
     * @param CacheManager|null $cacheManager
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
     */
    public function __construct(CacheManager $cacheManager = null)
    {
        $cacheManager = $cacheManager ?? GeneralUtility::makeInstance(CacheManager::class);
        $this->cache = $cacheManager->getCache('cache_pages');
    }

    /**
     * Fetches all redirects available to the system, grouped by domain and regexp/nonregexp
     *
     * @return array
     */
    public function getRedirects(): array
    {
        $redirects = $this->cache->get('redirects');
        if (!is_array($redirects)) {
            $redirects = $this->rebuild();
        }
        return $redirects;
    }

    /**
     * Rebuilds the cache for all redirects, grouped by host as well as by regular expressions and respect_query_parameters.
     * Does not include deleted redirects, but includes the ones with dynamic starttime/endtime.
     */
    public function rebuild(): array
    {
        $redirects = [];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_redirect');
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(HiddenRestriction::class))
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $statement = $queryBuilder
            ->select('*')
            ->from('sys_redirect')
            ->execute();
        while ($row = $statement->fetch()) {
            $host = $row['source_host'] ?: '*';
            if ($row['is_regexp']) {
                $redirects[$host]['regexp'][$row['source_path']][$row['uid']] = $row;
            } elseif ($row['respect_query_parameters']) {
                $redirects[$host]['respect_query_parameters'][$row['source_path']][$row['uid']] = $row;
            } else {
                $redirects[$host]['flat'][rtrim($row['source_path'], '/') . '/'][$row['uid']] = $row;
            }
        }
        $this->cache->set('redirects', $redirects);
        return $redirects;
    }
}
