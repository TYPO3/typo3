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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Basic service to clear caches within the install tool.
 * This is NOT an API class, it is for internal use in the install tool only.
 */
class ClearCacheService
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager = null;

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

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
     *
     * @return void
     */
    public function clearAll()
    {
        // Delete typo3temp/Cache
        GeneralUtility::flushDirectory(PATH_site . 'typo3temp/Cache', true, true);

        $bootstrap = \TYPO3\CMS\Core\Core\Bootstrap::getInstance();
        $bootstrap
            ->initializeCachingFramework()
            ->initializePackageManagement(\TYPO3\CMS\Core\Package\PackageManager::class);

        // Get all table names starting with 'cf_' and truncate them
        $database = $this->getDatabaseConnection();
        $tables = $database->admin_get_tables();
        foreach ($tables as $table) {
            $tableName = $table['Name'];
            if (substr($tableName, 0, 3) === 'cf_') {
                $database->exec_TRUNCATEquery($tableName);
            } elseif ($tableName === 'cache_treelist') {
                // cache_treelist is not implemented in the caching framework.
                // clear this table manually
                $database->exec_TRUNCATEquery('cache_treelist');
            }
        }

        // From this point on, the code may fatal, if some broken extension is loaded.

        // Use bootstrap to load all ext_localconf and ext_tables
        $bootstrap
            ->loadTypo3LoadedExtAndExtLocalconf(false)
            ->defineLoggingAndExceptionConstants()
            ->unsetReservedGlobalVariables()
            ->initializeTypo3DbGlobal()
            ->loadExtensionTables(false);

        // The cache manager is already instantiated in the install tool
        // with some hacked settings to disable caching of extbase and fluid.
        // We want a "fresh" object here to operate on a different cache setup.
        // cacheManager implements SingletonInterface, so the only way to get a "fresh"
        // instance is by circumventing makeInstance and/or the objectManager and
        // using new directly!
        $cacheManager = new \TYPO3\CMS\Core\Cache\CacheManager();
        $cacheManager->setCacheConfigurations($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']);
        // Cache manager needs cache factory. cache factory injects itself to manager in __construct()
        new \TYPO3\CMS\Core\Cache\CacheFactory('production', $cacheManager);

        $cacheManager->flushCaches();
    }

    /**
     * Get a database instance.
     *
     * @TODO: This method is a copy from AbstractAction. Review them and extract to service
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        static $database;
        if (!is_object($database)) {
            /** @var \TYPO3\CMS\Core\Database\DatabaseConnection $database */
            $database = $this->objectManager->get(\TYPO3\CMS\Core\Database\DatabaseConnection::class);
            $database->setDatabaseUsername($GLOBALS['TYPO3_CONF_VARS']['DB']['username']);
            $database->setDatabasePassword($GLOBALS['TYPO3_CONF_VARS']['DB']['password']);
            $database->setDatabaseHost($GLOBALS['TYPO3_CONF_VARS']['DB']['host']);
            $database->setDatabasePort($GLOBALS['TYPO3_CONF_VARS']['DB']['port']);
            $database->setDatabaseSocket($GLOBALS['TYPO3_CONF_VARS']['DB']['socket']);
            $database->setDatabaseName($GLOBALS['TYPO3_CONF_VARS']['DB']['database']);
            $database->initialize();
            $database->connectDB();
        }
        return $database;
    }
}
