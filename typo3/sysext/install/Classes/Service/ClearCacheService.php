<?php

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

namespace TYPO3\CMS\Install\Service;

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DependencyInjection\Cache\ContainerBackend;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Basic service to clear caches within the install tool.
 * @internal This is NOT an API class, it is for internal use in the install tool only.
 */
class ClearCacheService
{
    private const legacyDatabaseCacheTables = [
        'cache_treelist',
    ];

    /**
     * @var LateBootService
     */
    private $lateBootService;

    /**
     * @var FrontendInterface
     */
    private $dependencyInjectionCache;

    public function __construct(
        LateBootService $lateBootService,
        FrontendInterface $dependencyInjectionCache
    ) {
        $this->lateBootService = $lateBootService;
        $this->dependencyInjectionCache = $dependencyInjectionCache;
    }

    /**
     * This clear cache implementation follows a pretty brutal approach.
     * Goal is to reliably get rid of cache entries, even if some broken
     * extension is loaded that would kill the backend 'clear cache' action.
     *
     * Therefore this method "knows" implementation details of the cache
     * framework and uses them to clear all file based cache (typo3temp/Cache)
     * and database caches (tables prefixed with cf_) manually.
     *
     * After that ext_tables and ext_localconf of extensions are loaded, those
     * may register additional caches in the caching framework with different
     * backend, and will then clear them with the usual flush() method.
     */
    public function clearAll()
    {
        // Low level flush of legacy database cache tables
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        foreach (self::legacyDatabaseCacheTables as $tableName) {
            $connection = $connectionPool->getConnectionForTable($tableName);
            $connection->truncate($tableName);
        }

        // Flush all caches defined in TYPO3_CONF_VARS, but not the ones defined by extensions in ext_localconf.php
        $baseCaches = $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'] ?? [];
        $this->flushCaches($baseCaches);

        // Remove DI container cache (this might be removed in preference of functionality to rebuild this cache)
        if ($this->dependencyInjectionCache->getBackend() instanceof ContainerBackend) {
            /** @var ContainerBackend $diCacheBackend */
            $diCacheBackend = $this->dependencyInjectionCache->getBackend();
            // We need to remove using the forceFlush method because the DI cache backend disables the flush method
            $diCacheBackend->forceFlush();
        }

        // From this point on, the code may fatal, if some broken extension is loaded.
        $this->lateBootService->loadExtLocalconfDatabaseAndExtTables();

        $extensionCaches = $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'] ?? [];
        // Loose comparison on purpose to allow changed ordering of the array
        if ($baseCaches != $extensionCaches) {
            // When configuration has changed during loading of extensions (due to ext_localconf.php), flush all caches again
            $this->flushCaches($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']);
        }
    }

    /**
     * The cache manager is already instantiated in the install tool
     * (both in the failsafe and the late boot container), but
     * with settings to disable caching (all caches using NullBackend).
     * We want a "fresh" object here to operate with the really configured cache backends.
     * CacheManager implements SingletonInterface, so the only way to get a "fresh"
     * instance is by circumventing makeInstance and using new directly!
     *
     * @param array $cacheConfiguration
     */
    private function flushCaches(array $cacheConfiguration): void
    {
        $cacheManager = new CacheManager();
        $cacheManager->setCacheConfigurations($cacheConfiguration);
        $cacheManager->flushCaches();
    }
}
