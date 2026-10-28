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

use TYPO3\CMS\Core\Cache\Flush\CacheFlushPlan;

/**
 * Dispatched once per cache flush with the complete plan of cache tags and
 * cache groups, which listeners may change before anything is flushed.
 *
 * The event is also dispatched by CacheFlusher::resolve(), which flushes
 * nothing. Listeners must only change the plan, follow-up work belongs into
 * a listener of AfterCacheFlushedEvent.
 */
final readonly class BeforeCacheFlushEvent
{
    public function __construct(
        public CacheFlushPlan $plan,
    ) {}
}
