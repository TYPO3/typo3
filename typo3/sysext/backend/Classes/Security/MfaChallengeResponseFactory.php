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
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderRegistry;
use TYPO3\CMS\Core\Authentication\Pipeline\Challenge;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\SetCookieService;

/**
 * Decides whether an authenticated backend user still has to pass
 * multi-factor authentication and creates the challenge response initiating
 * it: a redirect to the MFA endpoint with the necessary cookies and headers
 * appended, so the already passed authentication step is not lost.
 *
 * @internal not part of TYPO3 Core API
 */
final class MfaChallengeResponseFactory
{
    public function __construct(
        private readonly UriBuilder $uriBuilder,
        private readonly MfaProviderRegistry $mfaProviderRegistry,
    ) {}

    /**
     * Challenges a user with an active MFA provider that has not passed the
     * multi-factor authentication in this session yet - unless the requested
     * route is the MFA route itself or accessible without an authenticated
     * user, which must stay reachable with the partially authenticated session.
     */
    public function createChallengeIfRequired(
        BackendUserAuthentication $user,
        ServerRequestInterface $request,
    ): ?Challenge {
        $provider = $this->mfaProviderRegistry->getFirstAuthenticationAwareProvider($user);
        if ($provider === null || !$this->isMfaPending($user)) {
            return null;
        }
        /** @var Route|null $route */
        $route = $request->getAttribute('route');
        $mfaRequested = $route?->getOption('_identifier') === 'auth_mfa';
        if ($mfaRequested || ($route !== null && !$route->getAccess()->requiresAuthentication())) {
            return null;
        }
        if ((bool)($route?->getOption('ajax') ?? false)) {
            return new Challenge(new Response(statusCode: 401));
        }
        return new Challenge(
            $this->createRedirect('auth_mfa', $user, $request, ['identifier' => $provider->getIdentifier()])
        );
    }

    /**
     * Whether the user has an active MFA provider and has not passed the
     * multi-factor authentication in this session yet. No principal may be
     * established while it is pending. A session an admin switched to
     * another user with is not challenged, the admin passed the
     * authentication already.
     */
    public function isMfaPending(BackendUserAuthentication $user): bool
    {
        return !$user->getSessionData('mfa')
            && $user->getOriginalUserIdWhenInSwitchUserMode() === null
            && $this->mfaProviderRegistry->getFirstAuthenticationAwareProvider($user) !== null;
    }

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
