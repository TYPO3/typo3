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

namespace TYPO3\CMS\Core\Cache\Event;

use TYPO3\CMS\Core\Cache\CacheTag;

/**
 * This event should only be used in code that has no access to the request attribute 'frontend.cache.collector'.
 * If you have access to the request, use the $request->getAttribute('frontend.cache.collector')->addCacheTags(...)
 * directly. It's really just there to allow passive cache-data signaling, without exactly knowing the
 * current context.
 *
 * @internal This event is a tribute to core places that need to set cache tags but do not have the
 *           current request yet. The FE CacheDataCollectorAttribute listens on this event. It
 *           may vanish later without further notice.
 */
final readonly class AddCacheTagEvent
{
    public function __construct(
        public CacheTag $cacheTag,
    ) {}
}
