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

namespace TYPO3\CMS\Core\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Cache\CacheDataCollector;
use TYPO3\CMS\Core\Cache\CacheTag;
use TYPO3\CMS\Core\Cache\Event\AddCacheTagEvent;

/**
 * Add CacheTags as 'cacheDataCollector' attribute.
 *
 * @internal
 */
class CacheDataCollectorAttribute implements MiddlewareInterface
{
    /**
     * The maximum length of the X-TYPO3-Cache-Tags header.
     * Prevents exceeding the maximum header size of 8kB.
     * Some web servers (e.g. nginx or apache2) have a default
     * limit of 8kB for the size of a single header.
     */
    private const MAX_CACHE_TAGS_HEADER_LENGTH = 8000;

    private ?CacheDataCollector $cacheDataCollector = null;

    /**
     * Adds an instance of TYPO3\CMS\Core\Cache\CacheDataCollector as
     * attribute to $request object
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Middlewares are shared services. With sub requests in mind, we need to take care
        // existing data of the parent request is not overwritten by a sub request. We thus
        // back up any existing data, create a new CacheDataCollector, dispatch the request,
        // and reset before returning the response.
        // @todo: We could argue sub requests should run their own container instance, but
        //        this has more impact and has not been sorted out, yet.
        $backup = $this->cacheDataCollector;
        $this->cacheDataCollector = new CacheDataCollector();
        $request = $request->withAttribute('frontend.cache.collector', $this->cacheDataCollector);
        $response = $handler->handle($request);
        if ($this->isDebugModeEnabled()) {
            $cacheTags = array_map(fn(CacheTag $cacheTag) => $cacheTag->name, $this->cacheDataCollector->getCacheTags());
            sort($cacheTags);
            foreach (explode("\n", wordwrap(implode(' ', $cacheTags), self::MAX_CACHE_TAGS_HEADER_LENGTH, "\n")) as $delta => $tags) {
                $response = $response->withHeader('X-TYPO3-Cache-Tags' . ($delta > 0 ? '-' . $delta : ''), $tags);
            }
            $response = $response->withHeader('X-TYPO3-Cache-Lifetime', (string)$this->cacheDataCollector->resolveLifetime());
        }
        foreach ($this->cacheDataCollector->getCacheEntries() as $deferredCacheItem) {
            $deferredCacheItem($request);
        }
        $this->cacheDataCollector = $backup;
        return $response;
    }

    #[AsEventListener]
    public function onCacheTagAdded(AddCacheTagEvent $event): void
    {
        // This event listener is a tribute to code places that want to add cache tags,
        // but don't have the request available to set cache tags on the attribute directly.
        // TYPO3 core will try to get rid of these places over time, but needs this event
        // listener for now.
        $this->cacheDataCollector?->addCacheTags($event->cacheTag);
    }

    private function isDebugModeEnabled(): bool
    {
        return !empty($GLOBALS['TYPO3_CONF_VARS']['FE']['debug']);
    }
}
