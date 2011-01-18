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
 * This cache factory takes care of instantiating a cache frontend and injecting
 * a certain cache backend. After creation of the new cache, the cache object
 * is registered at the cache manager.
 *
 * This file is a backport from FLOW3
 *
 * @package TYPO3
 * @subpackage t3lib_cache
 * @api
 * @version $Id$
 */
class t3lib_cache_Factory implements t3lib_Singleton {

	/**
	 * A reference to the cache manager
	 *
	 * @var t3lib_cache_Manager
	 */
	protected $cacheManager;

	/**
	 * Injects the cache manager.
	 *
	 * This is called by the cache manager itself
	 *
	 * @param t3lib_cache_Manager $cacheManager The cache manager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	public function setCacheManager(t3lib_cache_Manager $cacheManager) {
		$this->cacheManager = $cacheManager;
	}

	/**
	 * Factory method which creates the specified cache along with the specified kind of backend.
	 * After creating the cache, it will be registered at the cache manager.
	 *
	 * @param string $cacheIdentifier The name / identifier of the cache to create
	 * @param string $cacheName Name of the cache frontend
	 * @param string $backendName Name of the cache backend
	 * @param array $backendOptions (optional) Array of backend options
	 * @return t3lib_cache_frontend_Frontend The created cache frontend
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function create($cacheIdentifier, $cacheName, $backendName, array $backendOptions = array()) {

		$backendReference = $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheBackends'][$backendName];

		if (strpos($backendReference, ':') === FALSE) {
			$backendClassReference = $backendReference;
		} else {
			t3lib_div::deprecationLog("Configuring cacheBackend with filename is deprecated since TYPO3 4.5. Use the autoloader instead.");
				// loading the cache backend file and class
			list($backendFile, $backendClassReference) = explode(
				':',
				$backendReference
			);

			$backendRequireFile = t3lib_div::getFileAbsFileName($backendFile);
			if ($backendRequireFile) {
				t3lib_div::requireOnce($backendRequireFile);
			}
		}

		$backend = t3lib_div::makeInstance($backendClassReference, $backendOptions);

		if (!$backend instanceof t3lib_cache_backend_Backend) {
			throw new t3lib_cache_exception_InvalidCache(
				'"' . $backendName . '" is not a valid cache backend.',
				1216304301
			);
		}

		$cacheReference = $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheFrontends'][$cacheName];

		if (strpos($cacheReference, ':') === FALSE) {
			$cacheClassReference = $cacheReference;
		} else {
			t3lib_div::deprecationLog("Configuring cacheFrontends with filename is deprecated since TYPO3 4.5. Use the autoloader instead.");
				// loading the cache frontend file and class
			list($cacheFile, $cacheClassReference) = explode(
				':',
				$cacheReference
			);

			$cacheRequireFile = t3lib_div::getFileAbsFileName($cacheFile);
			if ($cacheRequireFile) {
				t3lib_div::requireOnce($cacheRequireFile);
			}
		}
		$cache = t3lib_div::makeInstance($cacheClassReference, $cacheIdentifier, $backend);


		if (!$cache instanceof t3lib_cache_frontend_Frontend) {
			throw new t3lib_cache_exception_InvalidCache(
				'"' . $cacheName . '" is not a valid cache.',
				1216304300
			);
		}

		$this->cacheManager->registerCache($cache);

		return $cache;
	}

}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/cache/class.t3lib_cache_factory.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/cache/class.t3lib_cache_factory.php']);
}

?>