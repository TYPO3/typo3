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

namespace TYPO3\CMS\Frontend\Cache;

/**
 * This class contains cache details and is created or updated in middlewares of the
 * Frontend rendering chain and added as Request attribute "frontend.cache.instruction".
 *
 * Its main goal is to *disable* the Frontend cache mechanisms in various scenarios, for
 * instance when the admin panel is used to simulate access times, or when security
 * mechanisms like cHash evaluation do not match.
 */
final class CacheInstruction
{
    private bool $allowCaching = true;
    private array $disabledCacheReasons = [];

    /**
     * Instruct the core Frontend rendering to disable Frontend caching. Extensions with
     * custom middlewares may set this.
     *
     * Note multiple cache layers are involved during Frontend rendering: For instance multiple
     * TypoScript layers, the page cache and potentially others. Those caches are read from and
     * written to within various middlewares. Depending on the position of a call to this method
     * within the middleware stack, it can happen that some or all caches have already been
     * read of written.
     *
     * Extensions that use this method should keep an eye on their middleware positions in the
     * stack to estimate the performance impact of this call. It's of course best to not use
     * the 'disable cache' mechanic at all, but to handle caching properly in extensions.
     */
    public function disableCache(string $reason): void
    {
        if (empty($reason)) {
            throw new \RuntimeException(
                'A non-empty reason must be given to disable cache. At least mention the extension name that triggers it.',
                1701528694
            );
        }
        $this->allowCaching = false;
        $this->disabledCacheReasons[] = $reason;
    }

    public function isCachingAllowed(): bool
    {
        return $this->allowCaching;
    }

    /**
     * @internal Typically only consumed by extensions like EXT:adminpanel
     */
    public function getDisabledCacheReasons(): array
    {
        return $this->disabledCacheReasons;
    }
}
