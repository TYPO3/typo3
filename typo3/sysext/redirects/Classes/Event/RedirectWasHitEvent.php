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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * This event is fired in the \TYPO3\CMS\Redirects\Http\Middleware\RedirectHandler
 * middleware when a request matches a configured redirect.
 *
 * It can be used to further process the matched redirect and
 * to adjust the PSR-7 Response. It furthermore allows to influence Core
 * functionality, for example the hit count increment.
 */
final class RedirectWasHitEvent
{
    public function __construct(
        private readonly ServerRequestInterface $request,
        private ResponseInterface $response,
        private array $matchedRedirect,
        private readonly UriInterface $targetUrl
    ) {}

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function getTargetUrl(): UriInterface
    {
        return $this->targetUrl;
    }

    public function setMatchedRedirect(array $matchedRedirect): void
    {
        $this->matchedRedirect = $matchedRedirect;
    }

    public function getMatchedRedirect(): array
    {
        return $this->matchedRedirect;
    }

    public function setResponse(ResponseInterface $response): void
    {
        $this->response = $response;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
