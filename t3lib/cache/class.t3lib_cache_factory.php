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
 * @scope singleton
 * @api
 */
class t3lib_cache_Factory implements t3lib_Singleton {

	/**
	 * The current FLOW3 context ("production", "development" etc.)
	 *
	 * TYPO3 v4 note: This variable is always set to "production"
	 * in TYPO3 v4 and only kept in v4 to keep v4 and FLOW3 in sync.
	 *
	 * @var string
	 */
	protected $context;

	/**
	 * A reference to the cache manager
	 *
	 * @var t3lib_cache_Manager
	 */
	protected $cacheManager;

	/**
	 * Constructs this cache factory
	 *
	 * @param string $context The current FLOW3 context
	 * @param t3lib_cache_Manager $cacheManager The cache manager
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($context, t3lib_cache_Manager $cacheManager) {
		$this->context = $context;
		$this->cacheManager = $cacheManager;
		$this->cacheManager->injectCacheFactory($this);
	}

	/**
	 * Factory method which creates the specified cache along with the specified kind of backend.
	 * After creating the cache, it will be registered at the cache manager.
	 *
	 * @param string $cacheIdentifier The name / identifier of the cache to create
	 * @param string $cacheObjectName Object name of the cache frontend
	 * @param string $backendObjectName Object name of the cache backend
	 * @param array $backendOptions (optional) Array of backend options
	 * @return t3lib_cache_frontend_Frontend The created cache frontend
	 * @throws t3lib_cache_exception_InvalidBackend if the cache backend is not valid
	 * @throws t3lib_cache_exception_InvalidCache if the cache frontend is not valid
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function create($cacheIdentifier, $cacheObjectName, $backendObjectName, array $backendOptions = array()) {

		$backendReference = $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheBackends'][$backendObjectName];

		if (strpos($backendReference, ':') === FALSE) {
			$backendClassReference = $backendReference;
		} else {
			t3lib_div::deprecationLog("Configuring cacheBackend with filename is deprecated since TYPO3 4.5. Use the autoloader instead.");
				// Loading the cache backend file and class
			list($backendFile, $backendClassReference) = explode(
				':',
				$backendReference
			);

			$backendRequireFile = t3lib_div::getFileAbsFileName($backendFile);
			if ($backendRequireFile) {
				t3lib_div::requireOnce($backendRequireFile);
			}
		}

		$backend = t3lib_div::makeInstance($backendClassReference, $this->context, $backendOptions);

		if (!$backend instanceof t3lib_cache_backend_Backend) {
			throw new t3lib_cache_exception_InvalidBackend(
				'"' . $backendObjectName . '" is not a valid cache backend.',
				1216304301
			);
		}

		if (is_callable(array($backend, 'initializeObject'))) {
			$backend->initializeObject();
		}


		$cacheReference = $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheFrontends'][$cacheObjectName];
		$cacheReference = $cacheReference ? $cacheReference : 't3lib_cache_frontend_VariableFrontend';

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
				'"' . $cacheObjectName . '" is not a valid cache.',
				1216304300
			);
		}

		if (is_callable(array($cache, 'initializeObject'))) {
			$cache->initializeObject();
		}

		$this->cacheManager->registerCache($cache);

		return $cache;
	}

}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/cache/class.t3lib_cache_factory.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/cache/class.t3lib_cache_factory.php']);
}

?>