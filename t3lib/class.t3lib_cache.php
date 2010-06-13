<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2010 Ingo Renner <ingo@typo3.org>
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
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_cache {
	/**
	 * @var	boolean
	 */
	protected static $isCachingFrameworkInitialized = false;

	/**
	 * Initializes the caching framework by loading the cache manager and factory
	 * into the global context.
	 *
	 * @return	void
	 */
	public static function initializeCachingFramework() {
		if (!self::isCachingFrameworkInitialized()) {
			$GLOBALS['typo3CacheManager'] = t3lib_div::makeInstance('t3lib_cache_Manager');
			$GLOBALS['typo3CacheFactory'] = t3lib_div::makeInstance('t3lib_cache_Factory');
			$GLOBALS['typo3CacheFactory']->setCacheManager($GLOBALS['typo3CacheManager']);
			self::$isCachingFrameworkInitialized = true;
		}
	}

	/**
	 * initializes the cache_pages cache
	 *
	 * @return	void
	 * @author	Ingo Renner <ingo@typo3.org>
	 */
	public static function initPageCache() {
		try {
			$GLOBALS['typo3CacheFactory']->create(
				'cache_pages',
				't3lib_cache_frontend_VariableFrontend',
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['cache_pages']['backend'],
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['cache_pages']['options']
			);
		} catch(t3lib_cache_exception_DuplicateIdentifier $e) {
				// do nothing, a cache_pages cache already exists
		}
	}

	/**
	 * initializes the cache_pagesection cache
	 *
	 * @return	void
	 * @author	Ingo Renner <ingo@typo3.org>
	 */
	public static function initPageSectionCache() {
		try {
			$GLOBALS['typo3CacheFactory']->create(
				'cache_pagesection',
				't3lib_cache_frontend_VariableFrontend',
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['cache_pagesection']['backend'],
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['cache_pagesection']['options']
			);
		} catch(t3lib_cache_exception_DuplicateIdentifier $e) {
				// do nothing, a cache_pagesection cache already exists
		}
	}

	/**
	 * initializes the cache_hash cache
	 *
	 * @return	void
	 * @author	Ingo Renner <ingo@typo3.org>
	 */
	public static function initContentHashCache() {
		try {
			$GLOBALS['typo3CacheFactory']->create(
				'cache_hash',
				't3lib_cache_frontend_VariableFrontend',
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['cache_hash']['backend'],
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['cache_hash']['options']
			);
		} catch(t3lib_cache_exception_DuplicateIdentifier $e) {
				// do nothing, a cache_hash cache already exists
		}
	}

	/**
	 * Determines whether the caching framework is initialized.
	 * The caching framework could be disabled for the core but used by an extension.
	 *
	 * @return	boolean
	 */
	public function isCachingFrameworkInitialized() {
		if (!self::$isCachingFrameworkInitialized
			&& isset($GLOBALS['typo3CacheManager']) && $GLOBALS['typo3CacheManager'] instanceof t3lib_cache_Manager
			&& isset($GLOBALS['typo3CacheFactory']) && $GLOBALS['typo3CacheFactory'] instanceof t3lib_cache_Factory
		) {
			self::$isCachingFrameworkInitialized = true;
		}

		return self::$isCachingFrameworkInitialized;
	}

	/**
	 * Enables the caching framework for the core caches like cache_pages, cache_pagesection and cache_hash.
	 * This method can be called by extensions in their ext_localconf.php. Calling it later would not work,
	 * since rendering is already started using the defined caches.
	 *
	 * @return	void
	 */
	public function enableCachingFramework() {
		if (!defined('TYPO3_UseCachingFramework')) {
			$GLOBALS['TYPO3_CONF_VARS']['SYS']['useCachingFramework'] = 1;
		} elseif (!TYPO3_UseCachingFramework) {
			throw new RuntimeException(
				'The caching framework was already defined to be disabled and cannot be changed. ' .
				'Please put your call to t3lib_cache::enableCachingFramework() into ext_localconf.php.',
				1253273131
			);
		}
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_cache.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_cache.php']);
}

?>