<?php
namespace TYPO3\CMS\Core\Cache;

/**
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
 * A cache handling helper class
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class Cache {

	/**
	 * @var boolean TRUE if caching framework was fully initialized
	 */
	static protected $isCachingFrameworkInitialized = FALSE;

	/**
	 * @var CacheManager
	 */
	static protected $cacheManager;

	/**
	 * @var CacheFactory
	 */
	static protected $cacheFactory;

	/**
	 * Initializes the caching framework by loading the cache manager and factory
	 * into the global context.
	 *
	 * @return CacheManager
	 */
	static public function initializeCachingFramework() {
		if (!self::isCachingFrameworkInitialized()) {
			// New operator used on purpose, makeInstance() is not ready to be used so early in bootstrap
			self::$cacheManager = new CacheManager();
			GeneralUtility::setSingletonInstance('TYPO3\\CMS\\Core\\Cache\\CacheManager', self::$cacheManager);
			self::$cacheManager->setCacheConfigurations($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']);
			// New operator used on purpose, makeInstance() is not ready to be used so early in bootstrap
			self::$cacheFactory = new CacheFactory('production', self::$cacheManager);
			GeneralUtility::setSingletonInstance('TYPO3\\CMS\\Core\\Cache\\CacheFactory', self::$cacheFactory);
			self::$isCachingFrameworkInitialized = TRUE;
		}
		return self::$cacheManager;
	}

	/**
	 * Determines whether the caching framework is initialized.
	 * The caching framework could be disabled for the core but used by an extension.
	 *
	 * @return boolean True if caching framework is initialized
	 */
	static public function isCachingFrameworkInitialized() {
		return self::$isCachingFrameworkInitialized;
	}

	/**
	 * Resets the isCachingFrameworkInitialized state
	 * Beware! This is not public API and necessary for edge cases in the install tool.
	 *
	 * @return void
	 */
	static public function flagCachingFrameworkForReinitialization() {
		self::$isCachingFrameworkInitialized = FALSE;
		GeneralUtility::removeSingletonInstance('TYPO3\\CMS\\Core\\Cache\\CacheManager', self::$cacheManager);
		GeneralUtility::removeSingletonInstance('TYPO3\\CMS\\Core\\Cache\\CacheFactory', self::$cacheFactory);
		self::$cacheManager = NULL;
		self::$cacheFactory = NULL;
	}

	/**
	 * Helper method for install tool and extension manager to determine
	 * required table structure of all caches that depend on it
	 *
	 * This is not a public API method!
	 *
	 * @return string Required table structure of all registered caches
	 */
	static public function getDatabaseTableDefinitions() {
		$tableDefinitions = '';
		foreach ($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'] as $cacheName => $_) {
			$backend = self::$cacheManager->getCache($cacheName)->getBackend();
			if (method_exists($backend, 'getTableDefinitions')) {
				$tableDefinitions .= LF . $backend->getTableDefinitions();
			}
		}
		return $tableDefinitions;
	}

	/**
	 * A slot method to inject the required caching framework database tables to the
	 * tables definitions string
	 *
	 * @param array $sqlString
	 * @param string $extensionKey
	 * @return array
	 */
	public function addCachingFrameworkRequiredDatabaseSchemaToTablesDefinition(array $sqlString, $extensionKey) {
		self::$cacheManager->setCacheConfigurations($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']);
		$sqlString[] = static::getDatabaseTableDefinitions();
		return array('sqlString' => $sqlString, 'extensionKey' => $extensionKey);
	}

}
