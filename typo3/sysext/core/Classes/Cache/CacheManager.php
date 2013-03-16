<?php
namespace TYPO3\CMS\Core\Cache;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Ingo Renner <ingo@typo3.org>
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
 * @author Robert Lemke <robert@typo3.org>
 * @scope singleton
 * @api
 */
class CacheManager implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Core\Cache\CacheFactory
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
		'frontend' => 'TYPO3\\CMS\\Core\\Cache\\Frontend\\VariableFrontend',
		'backend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend',
		'options' => array()
	);

	/**
	 * @param \TYPO3\CMS\Core\Cache\CacheFactory $cacheFactory
	 * @return void
	 */
	public function injectCacheFactory(\TYPO3\CMS\Core\Cache\CacheFactory $cacheFactory) {
		$this->cacheFactory = $cacheFactory;
	}

	/**
	 * Sets configurations for caches. The key of each entry specifies the
	 * cache identifier and the value is an array of configuration options.
	 * Possible options are:
	 *
	 * frontend
	 * backend
	 * backendOptions
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
				throw new \InvalidArgumentException('The cache configuration for cache "' . $identifier . '" was not an array as expected.', 1231259656);
			}
			$this->cacheConfigurations[$identifier] = $configuration;
		}
	}

	/**
	 * Registers a cache so it can be retrieved at a later point.
	 *
	 * @param \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface $cache The cache frontend to be registered
	 * @return void
	 * @throws \TYPO3\CMS\Core\Cache\Exception\DuplicateIdentifierException if a cache with the given identifier has already been registered.
	 * @api
	 */
	public function registerCache(\TYPO3\CMS\Core\Cache\Frontend\FrontendInterface $cache) {
		$identifier = $cache->getIdentifier();
		if (isset($this->caches[$identifier])) {
			throw new \TYPO3\CMS\Core\Cache\Exception\DuplicateIdentifierException('A cache with identifier "' . $identifier . '" has already been registered.', 1203698223);
		}
		$this->caches[$identifier] = $cache;
	}

	/**
	 * Returns the cache specified by $identifier
	 *
	 * @param string $identifier Identifies which cache to return
	 * @return \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface The specified cache frontend
	 * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
	 * @api
	 */
	public function getCache($identifier) {
		if ($this->hasCache($identifier) === FALSE) {
			throw new \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException('A cache with identifier "' . $identifier . '" does not exist.', 1203699034);
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
	 * Also flushes AOP proxy caches if a policy was modified.
	 *
	 * This method is used as a slot for a signal sent by the system file monitor
	 * defined in the bootstrap scripts.
	 *
	 * Note: Policy configuration handling is implemented here as well as other parts
	 * of FLOW3 (like the security framework) are not fully initialized at the
	 * time needed.
	 *
	 * @param string $fileMonitorIdentifier Identifier of the File Monitor
	 * @param array $changedFiles A list of full paths to changed files
	 * @return void
	 */
	public function flushClassFileCachesByChangedFiles($fileMonitorIdentifier, array $changedFiles) {
		$modifiedClassNamesWithUnderscores = array();
		$objectClassesCache = $this->getCache('FLOW3_Object_Classes');
		$objectConfigurationCache = $this->getCache('FLOW3_Object_Configuration');
		switch ($fileMonitorIdentifier) {
		case 'FLOW3_ClassFiles':
			$modifiedAspectClassNamesWithUnderscores = array();
			foreach ($changedFiles as $pathAndFilename => $status) {
				$pathAndFilename = str_replace(FLOW3_PATH_PACKAGES, '', $pathAndFilename);
				$matches = array();
				if (preg_match('/[^\\/]+\\/(.+)\\/(Classes|Tests)\\/(.+)\\.php/', $pathAndFilename, $matches) === 1) {
					$classNameWithUnderscores = str_replace('/', '_', $matches[1] . '_' . ($matches[2] === 'Tests' ? 'Tests_' : '') . $matches[3]);
					$classNameWithUnderscores = str_replace('.', '_', $classNameWithUnderscores);
					$modifiedClassNamesWithUnderscores[$classNameWithUnderscores] = TRUE;
					// If an aspect was modified, the whole code cache needs to be flushed, so keep track of them:
					if (substr($classNameWithUnderscores, -6, 6) === 'Aspect') {
						$modifiedAspectClassNamesWithUnderscores[$classNameWithUnderscores] = TRUE;
					}
					// As long as no modified aspect was found, we are optimistic that only part of the cache needs to be flushed:
					if (count($modifiedAspectClassNamesWithUnderscores) === 0) {
						$objectClassesCache->remove($classNameWithUnderscores);
					}
				}
			}
			$flushDoctrineProxyCache = FALSE;
			if (count($modifiedClassNamesWithUnderscores) > 0) {
				$reflectionStatusCache = $this->getCache('FLOW3_Reflection_Status');
				foreach (array_keys($modifiedClassNamesWithUnderscores) as $classNameWithUnderscores) {
					$reflectionStatusCache->remove($classNameWithUnderscores);
					if ($flushDoctrineProxyCache === FALSE && preg_match('/_Domain_Model_(.+)/', $classNameWithUnderscores) === 1) {
						$flushDoctrineProxyCache = TRUE;
					}
				}
				$objectConfigurationCache->remove('allCompiledCodeUpToDate');
			}
			if (count($modifiedAspectClassNamesWithUnderscores) > 0) {
				$this->systemLogger->log('Aspect classes have been modified, flushing the whole proxy classes cache.', LOG_INFO);
				$objectClassesCache->flush();
			}
			if ($flushDoctrineProxyCache === TRUE) {
				$this->systemLogger->log('Domain model changes have been detected, triggering Doctrine 2 proxy rebuilding.', LOG_INFO);
				$objectConfigurationCache->remove('doctrineProxyCodeUpToDate');
			}
			break;
		case 'FLOW3_ConfigurationFiles':
			$policyChangeDetected = FALSE;
			$routesChangeDetected = FALSE;
			foreach (array_keys($changedFiles) as $pathAndFilename) {
				$filename = basename($pathAndFilename);
				if (!in_array($filename, array('Policy.yaml', 'Routes.yaml'))) {
					continue;
				}
				if ($policyChangeDetected === FALSE && basename($pathAndFilename) === 'Policy.yaml') {
					$this->systemLogger->log('The security policies have changed, flushing the policy cache.', LOG_INFO);
					$this->getCache('FLOW3_Security_Policy')->flush();
					$policyChangeDetected = TRUE;
				} elseif ($routesChangeDetected === FALSE && basename($pathAndFilename) === 'Routes.yaml') {
					$this->systemLogger->log('A Routes.yaml file has been changed, flushing the routing cache.', LOG_INFO);
					$this->getCache('FLOW3_Mvc_Routing_FindMatchResults')->flush();
					$this->getCache('FLOW3_Mvc_Routing_Resolve')->flush();
					$routesChangeDetected = TRUE;
				}
			}
			$this->systemLogger->log('The configuration has changed, triggering an AOP proxy class rebuild.', LOG_INFO);
			$objectConfigurationCache->remove('allAspectClassesUpToDate');
			$objectConfigurationCache->remove('allCompiledCodeUpToDate');
			$objectClassesCache->flush();
			break;
		case 'FLOW3_TranslationFiles':
			foreach ($changedFiles as $pathAndFilename => $status) {
				$matches = array();
				if (preg_match('/\\/Translations\\/.+\\.xlf/', $pathAndFilename, $matches) === 1) {
					$this->systemLogger->log('The localization files have changed, thus flushing the I18n XML model cache.', LOG_INFO);
					$this->getCache('FLOW3_I18n_XmlModelCache')->flush();
					break;
				}
			}
			break;
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
	static public function getClassTag($className = '') {
		return $className === '' ? \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface::TAG_CLASS : \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface::TAG_CLASS . str_replace('\\', '_', $className);
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