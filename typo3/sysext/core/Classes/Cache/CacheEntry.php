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

namespace TYPO3\CMS\Core\Cache;

use Psr\Http\Message\ServerRequestInterface;

/**
 * A closure for lazy cache entry persistence, allowing cache lifetime
 * to be altered in the CacheDataCollector.
 *
 * Also allows a custom middleware to intercept a cache entry
 * from within a middleware.
 *
 * @internal
 */
final readonly class CacheEntry
{
    public function __construct(
        public string $identifier,
        public mixed $content,
        private \Closure $persist,
    ) {}

    public function __invoke(ServerRequestInterface $request): void
    {
        ($this->persist)($request, $this->identifier, $this->content);
    }
}
