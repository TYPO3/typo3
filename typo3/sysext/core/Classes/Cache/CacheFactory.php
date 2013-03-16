<?php
namespace TYPO3\CMS\Core\Cache;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2013 Ingo Renner <ingo@typo3.org>
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
 * @author Robert Lemke <robert@typo3.org>
 * @scope singleton
 * @api
 */
class CacheFactory implements \TYPO3\CMS\Core\SingletonInterface {

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
	 * @var \TYPO3\CMS\Core\Cache\CacheManager
	 */
	protected $cacheManager;

	/**
	 * Constructs this cache factory
	 *
	 * @param string $context The current FLOW3 context
	 * @param \TYPO3\CMS\Core\Cache\CacheManager $cacheManager The cache manager
	 */
	public function __construct($context, \TYPO3\CMS\Core\Cache\CacheManager $cacheManager) {
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
	 * @return \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface The created cache frontend
	 * @throws \TYPO3\CMS\Core\Cache\Exception\InvalidBackendException if the cache backend is not valid
	 * @throws \TYPO3\CMS\Core\Cache\Exception\InvalidCacheException if the cache frontend is not valid
	 * @api
	 */
	public function create($cacheIdentifier, $cacheObjectName, $backendObjectName, array $backendOptions = array()) {
		// New operator used on purpose: This class is required early during
		// bootstrap before makeInstance() is propely set up
		$backendObjectName = '\\' . ltrim($backendObjectName, '\\');
		$backend = new $backendObjectName($this->context, $backendOptions);
		if (!$backend instanceof \TYPO3\CMS\Core\Cache\Backend\BackendInterface) {
			throw new \TYPO3\CMS\Core\Cache\Exception\InvalidBackendException('"' . $backendObjectName . '" is not a valid cache backend object.', 1216304301);
		}
		if (is_callable(array($backend, 'initializeObject'))) {
			$backend->initializeObject();
		}
		// New used on purpose, see comment above
		$cache = new $cacheObjectName($cacheIdentifier, $backend);
		if (!$cache instanceof \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface) {
			throw new \TYPO3\CMS\Core\Cache\Exception\InvalidCacheException('"' . $cacheObjectName . '" is not a valid cache frontend object.', 1216304300);
		}
		if (is_callable(array($cache, 'initializeObject'))) {
			$cache->initializeObject();
		}
		$this->cacheManager->registerCache($cache);
		return $cache;
	}

}


?>