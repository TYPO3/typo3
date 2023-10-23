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

namespace TYPO3\CMS\Redirects\Event;

/**
 * This event is fired in \TYPO3\CMS\Redirects\Service\RedirectService->matchRedirect() for checked host and
 * wildcard host "*".
 *
 * It can be used to implement a custom match method, returning a matchedRedirect record with eventually enriched
 * record data.
 */
final class BeforeRedirectMatchDomainEvent
{
    private ?array $matchedRedirect = null;

    public function __construct(
        private readonly string $domain,
        private readonly string $path,
        private readonly string $query,
        private readonly string $matchDomainName,
    ) {}

    /**
     * @return string Request domain name (host)
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * @return string Request path
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return string Request query parameters
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * @return string Domain name which should be checked, and `getRedirects()` items are provided for
     */
    public function getMatchDomainName(): string
    {
        return $this->matchDomainName;
    }

    /**
     * @return array|null Returns the matched `sys_redirect` record or null
     */
    public function getMatchedRedirect(): ?array
    {
        return $this->matchedRedirect;
    }

    /**
     * @param array|null $matchedRedirect Set matched `sys_redirect` record or null to clear prior set record
     */
    public function setMatchedRedirect(?array $matchedRedirect): void
    {
        $this->matchedRedirect = $matchedRedirect;
    }
}
