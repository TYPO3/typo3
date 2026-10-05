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

namespace TYPO3\CMS\Backend\Security;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\RouteRedirect;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Http\SetCookieService;

/**
 * Creates the redirect to an MFA endpoint (authentication or setup) with
 * the necessary cookies and headers appended, so the already passed
 * authentication step is not lost.
 *
 * @internal not part of TYPO3 Core API
 */
final class MfaChallengeResponseFactory
{
    public function __construct(
        private readonly UriBuilder $uriBuilder,
    ) {}

    public function createRedirect(
        string $endpoint,
        BackendUserAuthentication $user,
        ServerRequestInterface $request,
        array $parameters = []
    ): ResponseInterface {
        $route = $request->getAttribute('route');
        $routeRedirect
            // when intercepting {entryPoint}/login?redirect=media_management
            = RouteRedirect::createFromRequest($request)
            // when intercepting {entryPoint}/module/file/list
            ?? ($route instanceof Route ? RouteRedirect::createFromRoute($route, $request->getQueryParams()) : null);
        $response = new RedirectResponse(
            $this->uriBuilder->buildUriWithRedirect($endpoint, $parameters, $routeRedirect)
        );
        $response = SetCookieService::create($user->name, $user->loginType)->applyCookieToResponse(
            $response,
            $user->getSession(),
            $user->getCookieBehavior(),
            $request->getAttribute('normalizedParams')
        );
        $headers = [
            'Expires' => 0,
            'Last-Modified' => gmdate('D, d M Y H:i:s') . ' GMT',
            'Cache-Control' => 'no-cache, no-store',
            // HTTP 1.0 compatibility, see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Pragma
            'Pragma' => 'no-cache',
        ];
        foreach ($headers as $headerName => $headerValue) {
            $response = $response->withHeader($headerName, (string)$headerValue);
        }
        return $response;
    }
}
