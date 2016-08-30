<?php
namespace TYPO3\CMS\Core\Cache;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException;

/**
 * The Cache Manager
 *
 * This file is a backport from FLOW3
 * @scope singleton
 * @api
 */
class CacheManager implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * @var \TYPO3\CMS\Core\Cache\CacheFactory
     */
    protected $cacheFactory;

    /**
     * @var \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface[]
     */
    protected $caches = [];

    /**
     * @var array
     */
    protected $cacheConfigurations = [];

    /**
     * Used to flush caches of a specific group
     * is an associative array containing the group identifier as key
     * and the identifier as an array within that group
     * groups are set via the cache configurations of each cache.
     *
     * @var array
     */
    protected $cacheGroups = [];

    /**
     * @var array Default cache configuration as fallback
     */
    protected $defaultCacheConfiguration = [
        'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
        'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
        'options' => [],
        'groups' => ['all']
    ];

    /**
     * @param \TYPO3\CMS\Core\Cache\CacheFactory $cacheFactory
     * @return void
     */
    public function injectCacheFactory(\TYPO3\CMS\Core\Cache\CacheFactory $cacheFactory)
    {
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
    public function setCacheConfigurations(array $cacheConfigurations)
    {
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
    public function registerCache(\TYPO3\CMS\Core\Cache\Frontend\FrontendInterface $cache)
    {
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
    public function getCache($identifier)
    {
        if ($this->hasCache($identifier) === false) {
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
     * @return bool TRUE if a cache with the given identifier exists, otherwise FALSE
     * @api
     */
    public function hasCache($identifier)
    {
        return isset($this->caches[$identifier]) || isset($this->cacheConfigurations[$identifier]);
    }

    /**
     * Flushes all registered caches
     *
     * @return void
     * @api
     */
    public function flushCaches()
    {
        $this->createAllCaches();
        foreach ($this->caches as $cache) {
            $cache->flush();
        }
    }

    /**
     * Flushes all registered caches of a specific group
     *
     * @param string $groupIdentifier
     * @return void
     * @throws NoSuchCacheGroupException
     * @api
     */
    public function flushCachesInGroup($groupIdentifier)
    {
        $this->createAllCaches();
        if (isset($this->cacheGroups[$groupIdentifier])) {
            foreach ($this->cacheGroups[$groupIdentifier] as $cacheIdentifier) {
                if (isset($this->caches[$cacheIdentifier])) {
                    $this->caches[$cacheIdentifier]->flush();
                }
            }
        } else {
            throw new NoSuchCacheGroupException('No cache in the specified group \'' . $groupIdentifier . '\'', 1390334120);
        }
    }

    /**
     * Flushes entries tagged by the specified tag of all registered
     * caches of a specific group.
     *
     * @param string $groupIdentifier
     * @param string $tag Tag to search for
     * @return void
     * @throws NoSuchCacheGroupException
     * @api
     */
    public function flushCachesInGroupByTag($groupIdentifier, $tag)
    {
        $this->createAllCaches();
        if (isset($this->cacheGroups[$groupIdentifier])) {
            foreach ($this->cacheGroups[$groupIdentifier] as $cacheIdentifier) {
                if (isset($this->caches[$cacheIdentifier])) {
                    $this->caches[$cacheIdentifier]->flushByTag($tag);
                }
            }
        } else {
            throw new NoSuchCacheGroupException('No cache in the specified group \'' . $groupIdentifier . '\'', 1390337129);
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
    public function flushCachesByTag($tag)
    {
        $this->createAllCaches();
        foreach ($this->caches as $cache) {
            $cache->flushByTag($tag);
        }
    }

    /**
     * Instantiates all registered caches.
     *
     * @return void
     */
    protected function createAllCaches()
    {
        foreach ($this->cacheConfigurations as $identifier => $_) {
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
    protected function createCache($identifier)
    {
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

        // Add the cache identifier to the groups that it should be attached to, or use the default ones.
        if (isset($this->cacheConfigurations[$identifier]['groups']) && is_array($this->cacheConfigurations[$identifier]['groups'])) {
            $assignedGroups = $this->cacheConfigurations[$identifier]['groups'];
        } else {
            $assignedGroups = $this->defaultCacheConfiguration['groups'];
        }
        foreach ($assignedGroups as $groupIdentifier) {
            if (!isset($this->cacheGroups[$groupIdentifier])) {
                $this->cacheGroups[$groupIdentifier] = [];
            }
            $this->cacheGroups[$groupIdentifier][] = $identifier;
        }

        $this->cacheFactory->create($identifier, $frontend, $backend, $backendOptions);
    }
}
