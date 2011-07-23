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
 * @scope singleton
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
	 * @var array Default cache configuration as fallback
	 */
	protected $defaultCacheConfiguration = array(
		'frontend' => 't3lib_cache_frontend_VariableFrontend',
		'backend' => 't3lib_cache_backend_DbBackend',
		'options' => array(),
	);

	/**
	 * @param t3lib_cache_Factory $cacheFactory
	 * @return void
	 */
	public function injectCacheFactory(t3lib_cache_Factory $cacheFactory) {
		$this->cacheFactory = $cacheFactory;
	}

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
	 * @param array $cacheConfigurations The cache configurations to set
	 * @return void
	 * @throws \InvalidArgumentException If $cacheConfigurations is not an array
	 */
	public function setCacheConfigurations(array $cacheConfigurations) {
		foreach ($cacheConfigurations as $identifier => $configuration) {
			if (!is_array($configuration)) {
				throw new \InvalidArgumentException(
					'The cache configuration for cache "' . $identifier . '" was not an array as expected.',
					1231259656
				);
			}
			$this->cacheConfigurations[$identifier] = $configuration;
		}
	}

	/**
	 * Registers a cache so it can be retrieved at a later point.
	 *
	 * @param t3lib_cache_frontend_Frontend $cache The cache frontend to be registered
	 * @return void
	 * @throws t3lib_cache_exception_DuplicateIdentifier if a cache with the given identifier has already been registered.
	 * @api
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
	 * @param string $identifier Identifies which cache to return
	 * @return t3lib_cache_frontend_Frontend The specified cache frontend
	 * @throws t3lib_cache_exception_NoSuchCache
	 * @api
	 */
	public function getCache($identifier) {
		if ($this->hasCache($identifier) === FALSE) {
			throw new t3lib_cache_exception_NoSuchCache(
				'A cache with identifier "' . $identifier . '" does not exist.',
				1203699034
			);
		}

		if (!isset($this->caches[$identifier])) {
			$this->createCache($identifier);
		}

		return $this->caches[$identifier];
	}

	/**
	 * Checks if the specified cache has been registered.
	 *
	 * @param string $identifier The identifier of the cache
	 * @return boolean TRUE if a cache with the given identifier exists, otherwise FALSE
	 * @api
	 */
	public function hasCache($identifier) {
		return isset($this->caches[$identifier]) || isset($this->cacheConfigurations[$identifier]);
	}

	/**
	 * Flushes all registered caches
	 *
	 * @return void
	 * @api
	 */
	public function flushCaches() {
		$this->createAllCaches();
		foreach ($this->caches as $cache) {
			$cache->flush();
		}
	}

	/**
	 * Flushes entries tagged by the specified tag of all registered
	 * caches.
	 *
	 * @param string $tag Tag to search for
	 * @return void
	 * @api
	 */
	public function flushCachesByTag($tag) {
		$this->createAllCaches();
		foreach ($this->caches as $cache) {
			$cache->flushByTag($tag);
		}
	}

	/**
	 * TYPO3 v4 note: This method is a direct backport from FLOW3 and currently
	 * unused in TYPO3 v4 context.
	 *
	 * Flushes entries tagged with class names if their class source files have changed.
	 *
	 * This method is used as a slot for a signal sent by the class file monitor defined
	 * in the bootstrap.
	 *
	 * @param string $fileMonitorIdentifier Identifier of the File Monitor (must be "FLOW3_ClassFiles")
	 * @param array $changedFiles A list of full paths to changed files
	 * @return void
	 */
	public function flushClassFileCachesByChangedFiles($fileMonitorIdentifier, array $changedFiles) {
		if ($fileMonitorIdentifier !== 'FLOW3_ClassFiles') {
			return;
		}

		$this->flushCachesByTag(self::getClassTag());
		foreach ($changedFiles as $pathAndFilename => $status) {
			$matches = array();
			if (1 === preg_match('/.+\/(.+)\/Classes\/(.+)\.php/', $pathAndFilename, $matches)) {
				$className = 'F3\\' . $matches[1] . '\\' . str_replace('/', '\\', $matches[2]);
				$this->flushCachesByTag(self::getClassTag($className));
			}
		}
	}

	/**
	 * TYPO3 v4 note: This method is a direct backport from FLOW3 and currently
	 * unused in TYPO3 v4 context.
	 *
	 * Renders a tag which can be used to mark a cache entry as "depends on this class".
	 * Whenever the specified class is modified, all cache entries tagged with the
	 * class are flushed.
	 *
	 * If an empty string is specified as class name, the returned tag means
	 * "this cache entry becomes invalid if any of the known classes changes".
	 *
	 * @param string $className The class name
	 * @return string Class Tag
	 * @api
	 */
	public static function getClassTag($className = '') {
		return ($className === '') ? t3lib_cache_frontend_Frontend::TAG_CLASS : t3lib_cache_frontend_Frontend::TAG_CLASS . str_replace('\\', '_', $className);
	}

	/**
	 * Instantiates all registered caches.
	 *
	 * @return void
	 */
	protected function createAllCaches() {
		foreach (array_keys($this->cacheConfigurations) as $identifier) {
			if (!isset($this->caches[$identifier])) {
				$this->createCache($identifier);
			}
		}
	}

	/**
	 * Instantiates the cache for $identifier.
	 *
	 * @param string $identifier
	 * @return void
	 */
	protected function createCache($identifier) {
		if (isset($this->cacheConfigurations[$identifier]['frontend'])) {
			$frontend = $this->cacheConfigurations[$identifier]['frontend'];
		} else {
			$frontend = $this->defaultCacheConfiguration['frontend'];
		}

		if (isset($this->cacheConfigurations[$identifier]['backend'])) {
			$backend = $this->cacheConfigurations[$identifier]['backend'];
		} else {
			$backend = $this->defaultCacheConfiguration['backend'];
		}

		if (isset($this->cacheConfigurations[$identifier]['options'])) {
			$backendOptions = $this->cacheConfigurations[$identifier]['options'];
		} else {
			$backendOptions = $this->defaultCacheConfiguration['options'];
		}

		$this->cacheFactory->create($identifier, $frontend, $backend, $backendOptions);
	}
}
?>