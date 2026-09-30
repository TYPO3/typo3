<?php

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

namespace TYPO3\CMS\Backend\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\RouteAccess;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\Response;

/**
 * Rejects state-changing backend requests which were not initiated by the backend itself.
 *
 * Browsers describe the origin of a request in the `Sec-Fetch-Site` header (or `Origin`
 * for older ones), which cannot be forged by a web page. Requests to anonymous routes,
 * such as webhooks or the login, are not restricted, neither are safe methods like GET.
 *
 * Depends on the NormalizedParams and the backend routing middleware.
 *
 * @internal
 */
readonly class FetchMetadataGuard implements MiddlewareInterface
{
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];
    private const TRUSTED_FETCH_SITES = ['same-origin', 'none'];

    public function __construct(
        private Features $features,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->features->isFeatureEnabled('security.backend.enforceFetchMetadata')) {
            return $handler->handle($request);
        }
        $route = $request->getAttribute('route');
        if (!$route instanceof Route
            || $route->getAccess() === RouteAccess::Anonymous
            || in_array(strtoupper($request->getMethod()), self::SAFE_METHODS, true)
            || $this->isOriginTrusted($request)
        ) {
            return $handler->handle($request);
        }
        return new Response(statusCode: 403, reasonPhrase: 'Cross-origin request denied');
    }

    private function isOriginTrusted(ServerRequestInterface $request): bool
    {
        $fetchSite = strtolower($request->getHeaderLine('Sec-Fetch-Site'));
        if ($fetchSite !== '') {
            return in_array($fetchSite, self::TRUSTED_FETCH_SITES, true);
        }
        $origin = $request->getHeaderLine('Origin');
        if ($origin === '') {
            return true;
        }
        $normalizedParams = $request->getAttribute('normalizedParams');
        return $normalizedParams instanceof NormalizedParams && $origin === $normalizedParams->getRequestHost();
    }
}
