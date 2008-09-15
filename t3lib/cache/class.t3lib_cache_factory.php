<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Ingo Renner <ingo@typo3.org>
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
 * @version $Id$
 */
class t3lib_cache_Factory {

	/**
	 * A reference to the cache manager
	 *
	 * @var t3lib_cache_Manager
	 */
	protected $cacheManager;

	/**
	 * Constructs this cache factory
	 *
	 * @param t3lib_cache_Manager A reference to the cache manager
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function __construct(t3lib_cache_Manager $cacheManager) {
		$this->cacheManager = $cacheManager;
	}

	/**
	 * Factory method which creates the specified cache along with the specified kind of backend.
	 * After creating the cache, it will be registered at the cache manager.
	 *
	 * @param string The name / identifier of the cache to create
	 * @param string Name of the cache frontend
	 * @param string Name of the cache backend
	 * @param array (optional) Array of backend options
	 * @return t3lib_cache_AbstractCache The created cache frontend
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function create($cacheIdentifier, $cacheName, $backendName, array $backendOptions = array()) {

			// loading the cache backend file and class
		list($backendFile, $backendClassReference) = explode(
			':',
			$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheBackends'][$backendName]
		);

		$backendRequireFile = t3lib_div::getFileAbsFileName($backendFile);
		if ($backendRequireFile) {
			t3lib_div::requireOnce($backendRequireFile);
		}

		$backendClassName = t3lib_div::makeInstanceClassName($backendClassReference);
		$backend = new $backendClassName($backendOptions);

		if (!$backend instanceof t3lib_cache_AbstractBackend) {
			throw new t3lib_cache_exception_InvalidCache(
				'"' .$backendName . '" is not a valid cache backend.',
				1216304301
			);
		}


			// loading the cache frontend file and class
		list($cacheFile, $cacheClassReference) = explode(
			':',
			$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['caches'][$cacheName]
		);

		$cacheRequireFile = t3lib_div::getFileAbsFileName($cacheFile);
		if ($cacheRequireFile) {
			t3lib_div::requireOnce($cacheRequireFile);
		}

		$cacheClassName = t3lib_div::makeInstanceClassName($cacheClassReference);
		$cache = new $cacheClassName($cacheIdentifier, $backend);


		if (!$cache instanceof t3lib_cache_AbstractCache) {
			throw new t3lib_cache_exception_InvalidCache(
				'"' .$cacheName . '" is not a valid cache.',
				1216304300
			);
		}

		$this->cacheManager->registerCache($cache);
		return $cache;
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/cache/class.t3lib_cache_factory.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/cache/class.t3lib_cache_factory.php']);
}

?>