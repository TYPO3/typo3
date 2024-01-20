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
 * This event is dispatched just before the final page cache identifier is created,
 * that is used to get() - and later set(), if needed and allowed - the page cache row.
 *
 * The event retrieves all current arguments that will be part of the identifier
 * calculation and allows to add further arguments in case page caches need
 * to be more specific.
 *
 * This event can be helpful in various scenarios, for example to implement
 * proper page caching in A/B testing.
 *
 * Note this event is *always* dispatched, even in fully cached page scenarios,
 * if an outer middleware did not return early (for instance due to permission issues).
 */
final class BeforePageCacheIdentifierIsHashedEvent
{
    public function __construct(
        private readonly ServerRequestInterface $request,
        private array $pageCacheIdentifierParameters,
    ) {}

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function getPageCacheIdentifierParameters(): array
    {
        return $this->pageCacheIdentifierParameters;
    }

    public function setPageCacheIdentifierParameters(array $pageCacheIdentifierParameters): void
    {
        $this->pageCacheIdentifierParameters = $pageCacheIdentifierParameters;
    }
}
