<?php
namespace TYPO3\CMS\Install\Service;

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

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Basic service to clear caches within the install tool.
 * @internal This is NOT an API class, it is for internal use in the install tool only.
 */
class ClearCacheService
{
    /**
     * This clear cache implementation follows a pretty brutal approach.
     * Goal is to reliably get rid of cache entries, even if some broken
     * extension is loaded that would kill the backend 'clear cache' action.
     *
     * Therefor this method "knows" implementation details of the cache
     * framework and uses them to clear all file based cache (typo3temp/Cache)
     * and database caches (tables prefixed with cf_) manually.
     *
     * After that ext_tables and ext_localconf of extensions are loaded, those
     * may register additional caches in the caching framework with different
     * backend, and will then clear them with the usual flush() method.
     */
    public function clearAll()
    {
        // Delete typo3temp/Cache
        GeneralUtility::flushDirectory(Environment::getVarPath() . '/cache', true, true);

        // Get all table names from Default connection starting with 'cf_' and truncate them
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionByName('Default');
        $tableNames = $connection->getSchemaManager()->listTableNames();
        foreach ($tableNames as $tableName) {
            if (strpos($tableName, 'cf_') === 0 || $tableName === 'cache_treelist') {
                $connection->truncate($tableName);
            }
        }

        // check tables on other connections
        $remappedTables = isset($GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping'])
            ? array_keys((array)$GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping'])
            : [];
        foreach ($remappedTables as $tableName) {
            if (strpos((string)$tableName, 'cf_') === 0 || $tableName === 'cache_treelist') {
                $connectionPool->getConnectionForTable($tableName)->truncate($tableName);
            }
        }

        // From this point on, the code may fatal, if some broken extension is loaded.

        // Use bootstrap to load all ext_localconf and ext_tables
        Bootstrap::loadTypo3LoadedExtAndExtLocalconf(false);
        Bootstrap::unsetReservedGlobalVariables();
        Bootstrap::loadBaseTca(false);
        Bootstrap::loadExtTables(false);

        // The cache manager is already instantiated in the install tool
        // with some hacked settings to disable caching of extbase and fluid.
        // We want a "fresh" object here to operate on a different cache setup.
        // cacheManager implements SingletonInterface, so the only way to get a "fresh"
        // instance is by circumventing makeInstance and/or the objectManager and
        // using new directly!
        $cacheManager = new \TYPO3\CMS\Core\Cache\CacheManager();
        $cacheManager->setCacheConfigurations($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']);
        $cacheManager->flushCaches();
    }
}
