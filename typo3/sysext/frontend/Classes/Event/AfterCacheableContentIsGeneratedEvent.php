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
 * Event that allows to enhance or change content (also depending on enabled caching).
 * Depending on disable or enabling caching, the cache is then not stored in the pageCache.
 *
 * Until TYPO3 v13, the flag "isCachingEnabled" was available in $TSFE->no_cache.
 */
final class AfterCacheableContentIsGeneratedEvent
{
    public function __construct(
        private readonly ServerRequestInterface $request,
        private string $content,
        private readonly string $cacheIdentifier,
        private bool $usePageCache
    ) {}

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function isCachingEnabled(): bool
    {
        return $this->usePageCache;
    }

    public function disableCaching(): void
    {
        $this->usePageCache = false;
    }

    public function enableCaching(): void
    {
        $this->usePageCache = true;
    }

    public function getCacheIdentifier(): string
    {
        return $this->cacheIdentifier;
    }
}
