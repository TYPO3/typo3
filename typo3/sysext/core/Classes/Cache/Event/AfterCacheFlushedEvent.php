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

use TYPO3\CMS\Core\Cache\Flush\CacheFlushResult;

/**
 * Dispatched once after CacheFlusher flushed caches, e.g. for changed records
 * in DataHandler or via the clear cache actions of the backend. Use it for
 * follow-up work such as purging a reverse proxy or CDN by the flushed cache
 * tags. The "cache:flush" command only dispatches CacheFlushEvent.
 */
final readonly class AfterCacheFlushedEvent
{
    public function __construct(
        public CacheFlushResult $result,
    ) {}
}
