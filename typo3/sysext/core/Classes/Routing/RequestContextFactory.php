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

namespace TYPO3\CMS\Core\Routing;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Routing\RequestContext;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

/**
 * @internal this is not part of the TYPO3 Core public API, as it serves as an internal
 * bridge between symfony/routing component and PSR-7 requests
 */
class RequestContextFactory
{
    public function __construct(
        protected readonly BackendEntryPointResolver $backendEntryPointResolver
    ) {
    }

    public function fromBackendRequest(ServerRequestInterface $request): RequestContext
    {
        $scheme = $request->getUri()->getScheme();
        return new RequestContext(
            $this->backendEntryPointResolver->getPathFromRequest($request),
            $request->getMethod(),
            (string)idn_to_ascii($request->getUri()->getHost()),
            $request->getUri()->getScheme(),
            $scheme === 'http' ? $request->getUri()->getPort() ?? 80 : 80,
            $scheme === 'https' ? $request->getUri()->getPort() ?? 443 : 443,
        );
    }

    public function fromUri(UriInterface $uri, string $method = 'GET'): RequestContext
    {
        return new RequestContext(
            '',
            $method,
            (string)idn_to_ascii($uri->getHost()),
            $uri->getScheme(),
            // Ports are only necessary for URL generation in Symfony which is not used by TYPO3
            80,
            443,
            $uri->getPath()
        );
    }

    public function fromSiteLanguage(SiteLanguage $language): RequestContext
    {
        $scheme = $language->getBase()->getScheme();
        return new RequestContext(
            // page segment (slug & enhanced part) is supposed to start with '/'
            rtrim($language->getBase()->getPath(), '/'),
            'GET',
            $language->getBase()->getHost(),
            $scheme ?: 'https',
            $scheme === 'http' ? $language->getBase()->getPort() ?? 80 : 80,
            $scheme === 'https' ? $language->getBase()->getPort() ?? 443 : 443
        );
    }
}
