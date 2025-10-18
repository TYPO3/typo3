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

namespace TYPO3\CMS\Frontend\Event;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Event that is used directly after all cached content is stored in the page cache.
 *
 * NOT fired, if:
 * * A page is called from the cache
 * * Caching is disabled using 'frontend.cache.instruction' request attribute, which can
 *   be set by various middlewares or AfterCacheableContentIsGeneratedEvent
 */
final readonly class AfterCachedPageIsPersistedEvent
{
    public function __construct(
        private ServerRequestInterface $request,
        private string $cacheIdentifier,
        private array $cacheData,
        private int $cacheLifetime
    ) {}

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function getCacheIdentifier(): string
    {
        return $this->cacheIdentifier;
    }

    public function getCacheData(): array
    {
        return $this->cacheData;
    }

    /**
     * The amount of seconds until the cache entry is invalid.
     */
    public function getCacheLifetime(): int
    {
        return $this->cacheLifetime;
    }
}
