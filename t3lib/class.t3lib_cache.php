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
				// new operator used on purpose, makeInstance() is not ready to be used so early in bootstrap
			$GLOBALS['typo3CacheManager'] = new t3lib_cache_Manager();
			t3lib_div::setSingletonInstance('t3lib_cache_Manager', $GLOBALS['typo3CacheManager']);
			t3lib_div::addClassNameToMakeInstanceCache('t3lib_cache_Manager', 't3lib_cache_Manager');
			$GLOBALS['typo3CacheManager']->setCacheConfigurations($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']);
				// new operator used on purpose, makeInstance() is not ready to be used so early in bootstrap
			$GLOBALS['typo3CacheFactory'] = new t3lib_cache_Factory('production', $GLOBALS['typo3CacheManager']);
			t3lib_div::setSingletonInstance('t3lib_cache_Factory', $GLOBALS['typo3CacheFactory']);
			t3lib_div::addClassNameToMakeInstanceCache('t3lib_cache_Factory', 't3lib_cache_Factory');
			self::$isCachingFrameworkInitialized = TRUE;
		}
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