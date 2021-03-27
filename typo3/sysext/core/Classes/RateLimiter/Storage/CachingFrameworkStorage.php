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

namespace TYPO3\CMS\Core\RateLimiter\Storage;

use Symfony\Component\RateLimiter\LimiterStateInterface;
use Symfony\Component\RateLimiter\Policy\SlidingWindow;
use Symfony\Component\RateLimiter\Policy\TokenBucket;
use Symfony\Component\RateLimiter\Policy\Window;
use Symfony\Component\RateLimiter\Storage\StorageInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;

/**
 * A rate limiter storage utilizing TYPO3's Caching Framework.
 *
 * @internal This is not part of the official TYPO3 Core API due to a limitation of the experimental Symfony Rate Limiter API.
 */
class CachingFrameworkStorage implements StorageInterface
{
    private FrontendInterface $cacheInstance;

    public function __construct(CacheManager $cacheInstance)
    {
        $this->cacheInstance = $cacheInstance->getCache('ratelimiter');
        $this->cacheInstance->collectGarbage();
    }

    public function save(LimiterStateInterface $limiterState): void
    {
        $this->cacheInstance->set(
            sha1($limiterState->getId()),
            serialize($limiterState),
            [],
            $limiterState->getExpirationTime()
        );
    }

    public function fetch(string $limiterStateId): ?LimiterStateInterface
    {
        $cacheItem = $this->cacheInstance->get(sha1($limiterStateId));
        if ($cacheItem) {
            $value = unserialize($cacheItem, ['allowed_classes' => [Window::class, SlidingWindow::class, TokenBucket::class]]);
            if ($value instanceof LimiterStateInterface) {
                return $value;
            }
        }

        return null;
    }

    public function delete(string $limiterStateId): void
    {
        $this->cacheInstance->remove(sha1($limiterStateId));
    }
}
