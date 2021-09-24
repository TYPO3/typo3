<?php

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

namespace TYPO3\CMS\Core\Cache;

use TYPO3\CMS\Core\Cache\Backend\BackendInterface;
use TYPO3\CMS\Core\Cache\Backend\NullBackend;
use TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\Event\CacheFlushEvent;
use TYPO3\CMS\Core\Cache\Exception\DuplicateIdentifierException;
use TYPO3\CMS\Core\Cache\Exception\InvalidBackendException;
use TYPO3\CMS\Core\Cache\Exception\InvalidCacheException;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * The Cache Manager
 */
class CacheManager implements SingletonInterface
{
    /**
     * @var FrontendInterface[]
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
        'frontend' => VariableFrontend::class,
        'backend' => Typo3DatabaseBackend::class,
        'options' => [],
        'groups' => ['all'],
    ];

    /**
     * @var bool
     */
    protected $disableCaching = false;

    /**
     * @param bool $disableCaching
     */
    public function __construct(bool $disableCaching = false)
    {
        $this->disableCaching = $disableCaching;
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
     * @param array<string, array> $cacheConfigurations The cache configurations to set
     * @throws \InvalidArgumentException If $cacheConfigurations is not an array
     */
    public function setCacheConfigurations(array $cacheConfigurations)
    {
        $newConfiguration = [];
        $migratedConfiguration = [];
        foreach ($cacheConfigurations as $identifier => $configuration) {
            if (empty($identifier)) {
                throw new \InvalidArgumentException('A cache identifier was not set.', 1596980032);
            }
            if (!is_array($configuration)) {
                throw new \InvalidArgumentException('The cache configuration for cache "' . $identifier . '" was not an array as expected.', 1231259656);
            }
            // Fallback layer, will be removed in TYPO3 v12.0.
            if (strpos($identifier, 'cache_') === 0) {
                $identifier = substr($identifier, 6);
                if (empty($identifier)) {
                    throw new \InvalidArgumentException('A cache identifier was not set.', 1596980033);
                }
                trigger_error('Accessing a cache with the "cache_" prefix as in "' . $identifier . '" is not necessary anymore, and should be called without the cache prefix.', E_USER_DEPRECATED);
                $migratedConfiguration[$identifier] = $configuration;
            } else {
                $newConfiguration[$identifier] = $configuration;
            }
        }
        $this->cacheConfigurations = array_replace_recursive($newConfiguration, $migratedConfiguration);
    }

    /**
     * Registers a cache so it can be retrieved at a later point.
     *
     * @param FrontendInterface $cache The cache frontend to be registered
     * @param array $groups Cache groups to be associated to the cache
     * @throws DuplicateIdentifierException if a cache with the given identifier has already been registered.
     */
    public function registerCache(FrontendInterface $cache, array $groups = [])
    {
        $identifier = $cache->getIdentifier();
        if (isset($this->caches[$identifier])) {
            throw new DuplicateIdentifierException('A cache with identifier "' . $identifier . '" has already been registered.', 1203698223);
        }
        $this->caches[$identifier] = $cache;
        foreach ($groups as $groupIdentifier) {
            $this->cacheGroups[$groupIdentifier][] = $identifier;
        }
    }

    /**
     * Returns the cache specified by $identifier
     *
     * @param string $identifier Identifies which cache to return
     * @return FrontendInterface The specified cache frontend
     * @throws NoSuchCacheException
     */
    public function getCache($identifier)
    {
        // Fallback layer, will be removed in TYPO3 v12.0.
        if (strpos($identifier, 'cache_') === 0) {
            trigger_error('Accessing a cache with the "cache_" prefix as in "' . $identifier . '" is not necessary anymore, and should be called without the cache prefix.', E_USER_DEPRECATED);
            $identifier = substr($identifier, 6);
        }
        if ($this->hasCache($identifier) === false) {
            throw new NoSuchCacheException('A cache with identifier "' . $identifier . '" does not exist.', 1203699034);
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
     */
    public function hasCache($identifier)
    {
        // Fallback layer, will be removed in TYPO3 v12.0.
        if (strpos($identifier, 'cache_') === 0) {
            trigger_error('Accessing a cache with the "cache_" prefix as in "' . $identifier . '" is not necessary anymore, and should be called without the cache prefix.', E_USER_DEPRECATED);
            $identifier = substr($identifier, 6);
        }
        return isset($this->caches[$identifier]) || isset($this->cacheConfigurations[$identifier]);
    }

    /**
     * Flushes all registered caches
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
     * @throws NoSuchCacheGroupException
     */
    public function flushCachesInGroup($groupIdentifier)
    {
        $this->createAllCaches();
        if (!isset($this->cacheGroups[$groupIdentifier])) {
            throw new NoSuchCacheGroupException('No cache in the specified group \'' . $groupIdentifier . '\'', 1390334120);
        }
        foreach ($this->cacheGroups[$groupIdentifier] as $cacheIdentifier) {
            if (isset($this->caches[$cacheIdentifier])) {
                $this->caches[$cacheIdentifier]->flush();
            }
        }
    }

    /**
     * Flushes entries tagged by the specified tag of all registered
     * caches of a specific group.
     *
     * @param string $groupIdentifier
     * @param string|array $tag Tag to search for
     * @throws NoSuchCacheGroupException
     */
    public function flushCachesInGroupByTag($groupIdentifier, $tag)
    {
        if (empty($tag)) {
            return;
        }
        $this->createAllCaches();
        if (!isset($this->cacheGroups[$groupIdentifier])) {
            throw new NoSuchCacheGroupException('No cache in the specified group \'' . $groupIdentifier . '\'', 1390337129);
        }
        foreach ($this->cacheGroups[$groupIdentifier] as $cacheIdentifier) {
            if (isset($this->caches[$cacheIdentifier])) {
                $this->caches[$cacheIdentifier]->flushByTag($tag);
            }
        }
    }

    /**
     * Flushes entries tagged by any of the specified tags in all registered
     * caches of a specific group.
     *
     * @param string $groupIdentifier
     * @param string[] $tags Tags to search for
     * @throws NoSuchCacheGroupException
     */
    public function flushCachesInGroupByTags($groupIdentifier, array $tags)
    {
        if (empty($tags)) {
            return;
        }
        $this->createAllCaches();
        if (!isset($this->cacheGroups[$groupIdentifier])) {
            throw new NoSuchCacheGroupException('No cache in the specified group \'' . $groupIdentifier . '\'', 1390337130);
        }
        foreach ($this->cacheGroups[$groupIdentifier] as $cacheIdentifier) {
            if (isset($this->caches[$cacheIdentifier])) {
                $this->caches[$cacheIdentifier]->flushByTags($tags);
            }
        }
    }

    /**
     * Flushes entries tagged by the specified tag of all registered
     * caches.
     *
     * @param string $tag Tag to search for
     */
    public function flushCachesByTag($tag)
    {
        $this->createAllCaches();
        foreach ($this->caches as $cache) {
            $cache->flushByTag($tag);
        }
    }

    /**
     * Flushes entries tagged by any of the specified tags in all registered caches.
     *
     * @param string[] $tags Tags to search for
     */
    public function flushCachesByTags(array $tags)
    {
        $this->createAllCaches();
        foreach ($this->caches as $cache) {
            $cache->flushByTags($tags);
        }
    }

    /**
     * @return string[]
     * @internal
     */
    public function getCacheGroups(): array
    {
        $groups = array_keys($this->cacheGroups);

        foreach ($this->cacheConfigurations as $config) {
            foreach ($config['groups'] ?? [] as $group) {
                if (!in_array($group, $groups, true)) {
                    $groups[] = $group;
                }
            }
        }

        return $groups;
    }

    public function handleCacheFlushEvent(CacheFlushEvent $event): void
    {
        foreach ($event->getGroups() as $group) {
            $this->flushCachesInGroup($group);
        }
    }

    /**
     * Instantiates all registered caches.
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
     * @throws DuplicateIdentifierException
     * @throws InvalidBackendException
     * @throws InvalidCacheException
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

        if ($this->disableCaching && $backend !== TransientMemoryBackend::class) {
            $backend = NullBackend::class;
            $backendOptions = [];
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

        // New operator used on purpose: This class is required early during
        // bootstrap before makeInstance() is properly set up
        $backend = '\\' . ltrim($backend, '\\');
        $backendInstance = new $backend('production', $backendOptions);
        if (!$backendInstance instanceof BackendInterface) {
            throw new InvalidBackendException('"' . $backend . '" is not a valid cache backend object.', 1464550977);
        }
        if (is_callable([$backendInstance, 'initializeObject'])) {
            $backendInstance->initializeObject();
        }

        // New used on purpose, see comment above
        $frontendInstance = new $frontend($identifier, $backendInstance);
        if (!$frontendInstance instanceof FrontendInterface) {
            throw new InvalidCacheException('"' . $frontend . '" is not a valid cache frontend object.', 1464550984);
        }
        if (is_callable([$frontendInstance, 'initializeObject'])) {
            $frontendInstance->initializeObject();
        }

        $this->registerCache($frontendInstance);
    }
}
