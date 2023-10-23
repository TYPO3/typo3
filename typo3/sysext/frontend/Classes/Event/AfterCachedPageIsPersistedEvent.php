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
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Event that is used directly after all cached content is stored in
 * the page cache.
 *
 * If a page is called from the cache, this event is NOT fired.
 * This event is also NOT FIRED when $TSFE->no_cache (or manipulated via AfterCacheableContentIsGeneratedEvent)
 * is set.
 */
final class AfterCachedPageIsPersistedEvent
{
    public function __construct(
        private readonly ServerRequestInterface $request,
        private readonly TypoScriptFrontendController $controller,
        private readonly string $cacheIdentifier,
        private readonly array $cacheData,
        private readonly int $cacheLifetime
    ) {}

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function getController(): TypoScriptFrontendController
    {
        return $this->controller;
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
