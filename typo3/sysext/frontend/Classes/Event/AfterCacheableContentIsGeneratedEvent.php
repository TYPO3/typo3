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
 * Event that allows to enhance or change content (also depending if caching is enabled).
 * Think of $this->isCachingEnabled() as the same as $TSFE->no_cache.
 * Depending on disable or enabling caching, the cache is then not stored in the pageCache.
 */
final class AfterCacheableContentIsGeneratedEvent
{
    public function __construct(
        private readonly ServerRequestInterface $request,
        private readonly TypoScriptFrontendController $controller,
        private readonly string $cacheIdentifier,
        private bool $usePageCache
    ) {}

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function getController(): TypoScriptFrontendController
    {
        return $this->controller;
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
