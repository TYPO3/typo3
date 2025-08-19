<?php

declare(strict_types=1);

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

class CacheManager implements SingletonInterface
{
    /**
     * @var FrontendInterface[]
     */
    protected array $caches = [];

    protected array $cacheConfigurations = [];

    /**
     * Used to flush caches of a specific group
     * is an associative array containing the group identifier as key
     * and the identifier as an array within that group
     * groups are set via the cache configurations of each cache.
     */
    protected array $cacheGroups = [];

    protected array $defaultCacheConfiguration = [
        'frontend' => VariableFrontend::class,
        'backend' => Typo3DatabaseBackend::class,
        'options' => [],
        'groups' => ['all'],
    ];

    public function __construct(
        protected bool $disableCaching = false
    ) {}

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
    public function setCacheConfigurations(array $cacheConfigurations): void
    {
        $newConfiguration = [];
        foreach ($cacheConfigurations as $identifier => $configuration) {
            if (empty($identifier)) {
                throw new \InvalidArgumentException('A cache identifier was not set.', 1596980032);
            }
            if (!is_array($configuration)) {
                throw new \InvalidArgumentException('The cache configuration for cache "' . $identifier . '" was not an array as expected.', 1231259656);
            }
            $newConfiguration[$identifier] = $configuration;
        }
        $this->cacheConfigurations = $newConfiguration;
    }

    /**
     * Registers a cache so it can be retrieved at a later point.
     *
     * @param array $groups Cache groups to be associated to the cache
     * @throws DuplicateIdentifierException if a cache with the given identifier has already been registered.
     */
    public function registerCache(FrontendInterface $cache, array $groups = []): void
    {
        // PHPStan ignore required taking phpdoc-block into account, but it is not ensured and may be also null.
        /** @phpstan-ignore nullCoalesce.expr */
        $identifier = $cache->getIdentifier() ?? '';
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
     * @throws NoSuchCacheException
     */
    public function getCache(string $identifier): FrontendInterface
    {
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
     */
    public function hasCache(string $identifier): bool
    {
        return isset($this->caches[$identifier]) || isset($this->cacheConfigurations[$identifier]);
    }

    /**
     * Flushes all registered caches
     */
    public function flushCaches(): void
    {
        $this->createAllCaches();
        foreach ($this->caches as $cache) {
            $cache->flush();
        }
    }

    /**
     * Flushes all registered caches of a specific group
     *
     * @throws NoSuchCacheGroupException
     */
    public function flushCachesInGroup(string $groupIdentifier): void
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
     * @throws NoSuchCacheGroupException
     */
    public function flushCachesInGroupByTag(string $groupIdentifier, string $tag): void
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
     * @throws NoSuchCacheGroupException
     */
    public function flushCachesInGroupByTags(string $groupIdentifier, array $tags): void
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
     * Flushes entries tagged by the specified tag of all registered caches.
     */
    public function flushCachesByTag(string $tag): void
    {
        $this->createAllCaches();
        foreach ($this->caches as $cache) {
            $cache->flushByTag($tag);
        }
    }

    /**
     * Flushes entries tagged by any of the specified tags in all registered caches.
     */
    public function flushCachesByTags(array $tags): void
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

    protected function createAllCaches(): void
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
     * @throws DuplicateIdentifierException
     * @throws InvalidBackendException
     * @throws InvalidCacheException
     */
    protected function createCache(string $identifier): void
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
        $backendInstance = new $backend($backendOptions);
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
