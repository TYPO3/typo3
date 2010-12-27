<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Xavier Perseguers <typo3@perseguers.ch>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
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


/**
 * Cache engine helper for generated queries.
 *
 * $Id$
 *
 * @author Xavier Perseguers <typo3@perseguers.ch>
 * @package TYPO3
 * @subpackage dbal
 */
class tx_dbal_querycache {

	/**
	 * Initializes the caching framework by loading the cache manager and factory
	 * into the global context.
	 *
	 * @return	void
	 */
	public static function initializeCachingFramework() {
		t3lib_cache::initializeCachingFramework();
	}

	/**
	 * Initializes the DBAL cache.
	 *
	 * @return	void
	 */
	public static function initDbalCache() {
		try {
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['dbal'])) {
				$backend = $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['dbal']['backend'];
				$options = $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['dbal']['options'];
			} else {
				// Transient storage, will be better than nothing
				$backend = 't3lib_cache_backend_TransientMemoryBackend';
				$options = array();
			}

			$GLOBALS['typo3CacheFactory']->create(
				'dbal',
				't3lib_cache_frontend_VariableFrontend',
				$backend,
				$options
			);
		} catch (t3lib_cache_exception_DuplicateIdentifier $e) {
			// Do nothing, a DBAL cache already exists
		}
	}

	/**
	 * Returns a proper cache key.
	 *
	 * @param	mixed		$config
	 * @return	void
	 */
	public static function getCacheKey($config) {
		if (is_array($config)) {
			return md5(serialize($config));
		} else {
			return $config;
		}
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/dbal/lib/class.tx_dbal_querycache.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/dbal/lib/class.tx_dbal_querycache.php']);
}

?>