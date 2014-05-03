<?php
namespace TYPO3\CMS\Install\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Christian Kuhn <lolli@schwarzbu.ch>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Basic service to clear caches within the install tool.
 * This is NOT an API class, it is for internal use in the install tool only.
 */
class ClearCacheService {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 * @inject
	 */
	protected $objectManager = NULL;

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
	public function clearAll() {
		// Delete typo3temp/Cache
		GeneralUtility::rmdir(PATH_site . 'typo3temp/Cache', TRUE);

		$bootstrap = \TYPO3\CMS\Core\Core\Bootstrap::getInstance();
		$bootstrap->unregisterClassLoader();

		\TYPO3\CMS\Core\Cache\Cache::flagCachingFrameworkForReinitialization();

		$bootstrap
			->initializeClassLoader()
			->initializeCachingFramework()
			->initializeClassLoaderCaches()
			->initializePackageManagement('TYPO3\\CMS\\Core\\Package\\PackageManager');

		// Get all table names starting with 'cf_' and truncate them
		$database = $this->getDatabaseConnection();
		$tables = $database->admin_get_tables();
		foreach ($tables as $table) {
			$tableName = $table['Name'];
			if (substr($tableName, 0, 3) === 'cf_') {
				$database->exec_TRUNCATEquery($tableName);
			}
		}

		// From this point on, the code may fatal, if some broken extension is loaded.

		// Use bootstrap to load all ext_localconf and ext_tables
		$bootstrap
			->loadTypo3LoadedExtAndExtLocalconf(FALSE)
			->applyAdditionalConfigurationSettings()
			->initializeTypo3DbGlobal()
			->loadExtensionTables(FALSE);

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
	protected function getDatabaseConnection() {
		static $database;
		if (!is_object($database)) {
			/** @var \TYPO3\CMS\Core\Database\DatabaseConnection $database */
			$database = $this->objectManager->get('TYPO3\\CMS\\Core\\Database\\DatabaseConnection');
			$database->setDatabaseUsername($GLOBALS['TYPO3_CONF_VARS']['DB']['username']);
			$database->setDatabasePassword($GLOBALS['TYPO3_CONF_VARS']['DB']['password']);
			$database->setDatabaseHost($GLOBALS['TYPO3_CONF_VARS']['DB']['host']);
			$database->setDatabasePort($GLOBALS['TYPO3_CONF_VARS']['DB']['port']);
			$database->setDatabaseSocket($GLOBALS['TYPO3_CONF_VARS']['DB']['socket']);
			$database->setDatabaseName($GLOBALS['TYPO3_CONF_VARS']['DB']['database']);
			$database->connectDB();
		}
		return $database;
	}
}
