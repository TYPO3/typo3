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

final class CacheDataCollector implements CacheDataCollectorInterface
{
    /**
     * @var CacheTag[]
     */
    private array $cacheTags = [];

    private int $lifetime = PHP_INT_MAX;

    /**
     * @var CacheEntry[]
     */
    private array $cacheEntries = [];

    /**
     * @return CacheTag[]
     */
    public function getCacheTags(): array
    {
        return array_values($this->cacheTags);
    }

    public function addCacheTags(CacheTag ...$cacheTags): void
    {
        array_walk($cacheTags, fn(CacheTag $cacheTag) => $this->addCacheTag($cacheTag));
    }

    public function removeCacheTags(CacheTag ...$cacheTags): void
    {
        array_walk($cacheTags, fn(CacheTag $cacheTag) => $this->removeCacheTag($cacheTag));
    }

    public function restrictMaximumLifetime(int $lifetime): void
    {
        $this->lifetime = min($lifetime, $this->lifetime);
    }

    public function resolveLifetime(): int
    {
        $lifetimes = array_unique(
            [$this->lifetime, ...array_map(fn(CacheTag $cacheTag) => $cacheTag->lifetime, $this->cacheTags)]
        );
        return min($lifetimes);
    }

    public function enqueueCacheEntry(CacheEntry $deferredCacheItem): void
    {
        $this->cacheEntries[$deferredCacheItem->identifier] = $deferredCacheItem;
    }

    /**
     * @return CacheEntry[]
     */
    public function getCacheEntries(): array
    {
        return array_values($this->cacheEntries);
    }

    private function addCacheTag(CacheTag $cacheTag): void
    {
        $this->cacheTags[$cacheTag->name] = $cacheTag;
    }

    private function removeCacheTag(CacheTag $cacheTag): void
    {
        unset($this->cacheTags[$cacheTag->name]);
    }
}
