<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2011 Ingo Renner <ingo@typo3.org>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * A cache handling helper class
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_cache {

	/**
	 * @var	boolean
	 */
	protected static $isCachingFrameworkInitialized = FALSE;

	/**
	 * Initializes the caching framework by loading the cache manager and factory
	 * into the global context.
	 *
	 * @return	void
	 */
	public static function initializeCachingFramework() {
		if (!self::isCachingFrameworkInitialized()) {
			$GLOBALS['typo3CacheManager'] = t3lib_div::makeInstance('t3lib_cache_Manager');
			$GLOBALS['typo3CacheManager']->setCacheConfigurations($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']);
			$GLOBALS['typo3CacheFactory'] = t3lib_div::makeInstance('t3lib_cache_Factory', 'production', $GLOBALS['typo3CacheManager']);
			self::$isCachingFrameworkInitialized = TRUE;
		}
	}

	/**
	 * initializes the cache_pages cache
	 *
	 * @return	void
	 * @author	Ingo Renner <ingo@typo3.org>
	 * @deprecated since TYPO3 4.6, will be removed in 6.0 - cacheManager->getCache() now initializes caches automatically
	 */
	public static function initPageCache() {
		t3lib_div::logDeprecatedFunction();
	}

	/**
	 * initializes the cache_pagesection cache
	 *
	 * @return	void
	 * @author	Ingo Renner <ingo@typo3.org>
	 * @deprecated since TYPO3 4.6, will be removed in 6.0 - cacheManager->getCache() now initializes caches automatically
	 */
	public static function initPageSectionCache() {
		t3lib_div::logDeprecatedFunction();
	}

	/**
	 * initializes the cache_hash cache
	 *
	 * @return	void
	 * @author	Ingo Renner <ingo@typo3.org>
	 * @deprecated since TYPO3 4.6, will be removed in 6.0 - cacheManager->getCache() now initializes caches automatically
	 */
	public static function initContentHashCache() {
		t3lib_div::logDeprecatedFunction();
	}

	/**
	 * Determines whether the caching framework is initialized.
	 * The caching framework could be disabled for the core but used by an extension.
	 *
	 * @return boolean True if caching framework is initialized
	 */
	public static function isCachingFrameworkInitialized() {
		if (!self::$isCachingFrameworkInitialized
				&& isset($GLOBALS['typo3CacheManager']) && $GLOBALS['typo3CacheManager'] instanceof t3lib_cache_Manager
				&& isset($GLOBALS['typo3CacheFactory']) && $GLOBALS['typo3CacheFactory'] instanceof t3lib_cache_Factory
		) {
			self::$isCachingFrameworkInitialized = TRUE;
		}

		return self::$isCachingFrameworkInitialized;
	}

	/**
	 * Enables the caching framework for the core caches like cache_pages, cache_pagesection and cache_hash.
	 * This method can be called by extensions in their ext_localconf.php. Calling it later would not work,
	 * since rendering is already started using the defined caches.
	 *
	 * @deprecated since 4.6, will be removed in 6.0: The caching framework is enabled by default
	 * @return void
	 */
	public static function enableCachingFramework() {
		t3lib_div::logDeprecatedFunction();
	}

	/**
	 * Helper method for install tool and extension manager to determine
	 * required table structure of all caches that depend on it
	 *
	 * This is not a public API method!
	 *
	 * @return string Required table structure of all registered caches
	 */
	public static function getDatabaseTableDefinitions() {
		$tableDefinitions = '';
		foreach ($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'] as $cacheName => $_) {
			$backend = $GLOBALS['typo3CacheManager']->getCache($cacheName)->getBackend();
			if (method_exists($backend, 'getTableDefinitions')) {
				$tableDefinitions .= LF . $backend->getTableDefinitions();
			}
		}
		return $tableDefinitions;
	}
}
?>