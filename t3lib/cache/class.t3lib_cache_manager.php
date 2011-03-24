<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2011 Ingo Renner <ingo@typo3.org>
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
 * The Cache Manager
 *
 * This file is a backport from FLOW3
 *
 * @package TYPO3
 * @subpackage t3lib_cache
 * @api
 */
class t3lib_cache_Manager implements t3lib_Singleton {
	/**
	 * @var t3lib_cache_Factory
	 */
	protected $cacheFactory;

	/**
	 * @var array
	 */
	protected $caches = array();

	/**
	 * @var array
	 */
	protected $cacheConfigurations = array();

	/**
	 * Sets configurations for caches. The key of each entry specifies the
	 * cache identifier and the value is an array of configuration options.
	 * Possible options are:
	 *
	 *   frontend
	 *   backend
	 *   backendOptions
	 *
	 * If one of the options is not specified, the default value is assumed.
	 * Existing cache configurations are preserved.
	 *
	 * @param	array	The cache configurations to set
	 * @return	void
	 * @author	Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	public function setCacheConfigurations(array $cacheConfigurations) {
		foreach ($cacheConfigurations as $identifier => $configuration) {
			if (!is_array($configuration)) {
				throw new InvalidArgumentException('The cache configuration for cache "' . $identifier . '" was not an array as expected.', 1235838075);
			}
			$this->cacheConfigurations[$identifier] = $configuration;
		}
	}

	/**
	 * Injects the cache factory
	 *
	 * @param	t3lib_cache_Factory	The cache factory
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 * @internal
	 */
	public function setCacheFactory(t3lib_cache_Factory $cacheFactory) {
		$this->cacheFactory = $cacheFactory;
		$this->cacheFactory->setCacheManager($this);
	}

	/**
	 * Initializes the cache manager
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	public function initialize() {
		foreach ($this->cacheConfigurations as $identifier => $configuration) {
			$this->cacheFactory->create(
				$identifier,
				$configuration['frontend'],
				$configuration['backend'],
				$configuration['backendOptions']
			);
		}
	}

	/**
	 * Registers a cache so it can be retrieved at a later point.
	 *
	 * @param t3lib_cache_frontend_Frontend The cache frontend to be registered
	 * @return void
	 * @throws t3lib_cache_exception_DuplicateIdentifier if a cache with the given identifier has already been registered.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function registerCache(t3lib_cache_frontend_Frontend $cache) {
		$identifier = $cache->getIdentifier();

		if (isset($this->caches[$identifier])) {
			throw new t3lib_cache_exception_DuplicateIdentifier(
				'A cache with identifier "' . $identifier . '" has already been registered.',
				1203698223
			);
		}

		$this->caches[$identifier] = $cache;
	}

	/**
	 * Returns the cache specified by $identifier
	 *
	 * @param string Identifies which cache to return
	 * @return t3lib_cache_frontend_Cache The specified cache frontend
	 * @throws t3lib_cache_exception_NoSuchCache
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getCache($identifier) {
		if (!isset($this->caches[$identifier])) {
			throw new t3lib_cache_exception_NoSuchCache(
				'A cache with identifier "' . $identifier . '" does not exist.',
				1203699034
			);
		}

		return $this->caches[$identifier];
	}

	/**
	 * Checks if the specified cache has been registered.
	 *
	 * @param string The identifier of the cache
	 * @return boolean TRUE if a cache with the given identifier exists, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function hasCache($identifier) {
		return isset($this->caches[$identifier]);
	}

	/**
	 * Flushes all registered caches
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function flushCaches() {
		foreach ($this->caches as $cache) {
			$cache->flush();
		}
	}

	/**
	 * Flushes entries tagged by the specified tag of all registered
	 * caches.
	 *
	 * @param string Tag to search for
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function flushCachesByTag($tag) {
		foreach ($this->caches as $cache) {
			$cache->flushByTag($tag);
		}
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/cache/class.t3lib_cache_manager.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/cache/class.t3lib_cache_manager.php']);
}

?>