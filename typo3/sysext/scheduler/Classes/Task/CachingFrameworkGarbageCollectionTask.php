<?php
namespace TYPO3\CMS\Scheduler\Task;

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

/**
 * Garbage collection of caching framework cache backends.
 *
 * This task finds all configured caching framework caches and
 * calls the garbage collection of a cache if the cache backend
 * is configured to be cleaned.
 * @internal This class is a specific scheduler task implementation is not considered part of the Public TYPO3 API.
 */
class CachingFrameworkGarbageCollectionTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask
{
    /**
     * Backend types that should be cleaned up,
     * set by additional field provider.
     *
     * @var array Selected backends to do garbage collection for
     */
    public $selectedBackends = [];

    /**
     * Execute garbage collection, called by scheduler.
     *
     * @return bool
     */
    public function execute()
    {
        // Global sub-array with all configured caches
        $cacheConfigurations = $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'];
        if (is_array($cacheConfigurations)) {
            // Iterate through configured caches and call garbage collection if
            // backend is within selected backends in additional field of task
            foreach ($cacheConfigurations as $cacheName => $cacheConfiguration) {
                // The cache backend used for this cache
                $usedCacheBackend = $cacheConfiguration['backend'];
                if (in_array($usedCacheBackend, $this->selectedBackends)) {
                    \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->getCache($cacheName)->collectGarbage();
                }
            }
        }
        return true;
    }
}
