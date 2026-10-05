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

namespace TYPO3\CMS\Backend\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\RouteRedirect;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Security\MfaChallengeResponseFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\Pipeline\Authenticated;
use TYPO3\CMS\Core\Authentication\Pipeline\AuthenticatorManager;
use TYPO3\CMS\Core\Authentication\Pipeline\Challenge;
use TYPO3\CMS\Core\Authentication\Pipeline\Realm;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Controller\ErrorPageController;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\SetCookieService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Session\UserSessionManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Authenticates the request against the backend realm of the authentication
 * pipeline, initializes $GLOBALS['BE_USER'] and the global LANG object, and
 * provides the established principal as "backend.principal" request attribute.
 *
 * @internal
 */
class BackendUserAuthenticator extends \TYPO3\CMS\Core\Middleware\BackendUserAuthenticator
{
    public function __construct(
        Context $context,
        private readonly LanguageServiceFactory $languageServiceFactory,
        private readonly UriBuilder $uriBuilder,
        private readonly AuthenticatorManager $authenticatorManager,
        private readonly MfaChallengeResponseFactory $mfaChallengeResponseFactory,
    ) {
        parent::__construct($context);
    }

    /**
     * Resolves the request to an authentication outcome and sets up
     * $GLOBALS['BE_USER'] and $GLOBALS['LANG']
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var Route $route */
        $route = $request->getAttribute('route');
        $isAjaxCall = (bool)($route->getOption('ajax') ?? false);
        // Whether multi-factor authentication is requested
        $mfaRequested = $route->getOption('_identifier') === 'auth_mfa';
        $realm = Realm::backend();

        $result = $this->authenticatorManager->authenticate($request, $realm);
        if ($result instanceof Challenge) {
            // An interactive authentication step is required (e.g. multi-factor
            // authentication) - the challenge response initiates it.
            return $result->response;
        }
        if ($result instanceof Authenticated && $result->hasAttribute('legacyUser')) {
            /** @var BackendUserAuthentication $backendUser */
            $backendUser = $result->getAttribute('legacyUser');
            $GLOBALS['BE_USER'] = $backendUser;
        } else {
            // During the migration period the legacy user object is guaranteed to
            // be established by LegacyBackendAuthenticator, even for anonymous
            // requests and failed authentication attempts.
            /** @var BackendUserAuthentication $backendUser */
            $backendUser = $GLOBALS['BE_USER'];
        }

        // Register the backend user as aspect and initializing workspace once for TSconfig conditions
        $this->setBackendUserAspect($backendUser, (int)($backendUser->user['workspace_id'] ?? 0));
        if ($this->isLoggedInBackendUserRequired($route)) {
            if (!$this->context->getAspect('backend.user')->isLoggedIn()) {
                if ($isAjaxCall) {
                    return new Response(statusCode: 401);
                }
                $uri = $this->uriBuilder->buildUriWithRedirect(
                    'login',
                    [],
                    RouteRedirect::createFromRoute($route, $request->getQueryParams())
                );
                $response = new RedirectResponse($uri);
                return $this->enrichResponseWithHeadersAndCookieInformation($request, $response, $backendUser);
            }
            if (!$backendUser->isUserAllowedToLogin()) {
                $content = GeneralUtility::makeInstance(ErrorPageController::class)->errorAction(
                    'Login Error',
                    'TYPO3 is in maintenance mode at the moment. Only administrators are allowed access.',
                    1294585860,
                    503
                );
                $response = new HtmlResponse($content, 503);
                return $this->enrichResponseWithHeadersAndCookieInformation($request, $response, $backendUser);
            }
        }
        if ($this->context->getAspect('backend.user')->isLoggedIn()) {
            $backendUser->initializeBackendLogin($request);
            // In case the current request is not targeted to authenticate against MFA, the "mfa"
            // key is not yet set in session (indicating that MFA has already been passed) and it's
            // no "switch-user" mode, check whether the user is required to set up MFA and redirect
            // to the corresponding setup endpoint if not already on it.
            if (!$mfaRequested
                && !(bool)($backendUser->getSessionData('mfa') ?? false)
                && !$backendUser->getOriginalUserIdWhenInSwitchUserMode()
                && $backendUser->isMfaSetupRequired()
                && $route->getOption('_identifier') !== 'setup_mfa'
            ) {
                if ($isAjaxCall) {
                    return new Response(statusCode: 401);
                }
                return $this->mfaChallengeResponseFactory->createRedirect('setup_mfa', $backendUser, $request);
            }
        }
        if ($result instanceof Authenticated) {
            $request = $request->withAttribute('backend.principal', $result->principal);
        }
        $GLOBALS['LANG'] = $this->languageServiceFactory->createFromUserPreferences($backendUser);
        // Re-setting the user and take the workspace from the user object now
        $this->setBackendUserAspect($backendUser);
        $response = $handler->handle($request);
        $this->sessionGarbageCollection();
        return $this->enrichResponseWithHeadersAndCookieInformation($request, $response, $backendUser);
    }

    /**
     * Backend requests should almost always apply Set-Cookie information and never be cacheable.
     * This is also needed if there is a redirect from somewhere in the code.
     *
     * The cookie is omitted from the response, if no cookie is set and
     * the user has not just been logged in, because that means we
     * a) do not delete a cookie and b) must not delete a cookie
     * that was omitted by the browser due to SameSite=strict handling
     * in cross-site requests.
     *
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     */
    protected function enrichResponseWithHeadersAndCookieInformation(
        ServerRequestInterface $request,
        ResponseInterface $response,
        ?BackendUserAuthentication $userAuthentication
    ): ResponseInterface {
        $setCookie = true;
        if ($userAuthentication) {
            $isCookieSet = isset($request->getCookieParams()[$userAuthentication->name]);
            // If no backend user is logged-in, the cookie should be removed
            if (!$this->context->getAspect('backend.user')->isLoggedIn()) {
                if ($isCookieSet) {
                    $userAuthentication->removeCookie();
                } else {
                    $setCookie = false;
                }
            }
            if ($setCookie) {
                $response = $this->applyCookieToResponse($response, $request, $userAuthentication);
            }
        }
        // Additional headers to never cache any PHP request should be sent at any time when
        // accessing the TYPO3 Backend
        $response = $this->applyHeadersToResponse($response);
        return $response;
    }

    /**
     * Garbage collection for be_sessions (with a probability)
     */
    protected function sessionGarbageCollection(): void
    {
        UserSessionManager::create('BE')->collectGarbage();
    }

    /**
     * Applies the session cookie as evaluated by the authentication process
     * to the response.
     */
    protected function applyCookieToResponse(
        ResponseInterface $response,
        ServerRequestInterface $request,
        BackendUserAuthentication $userAuthentication
    ): ResponseInterface {
        return SetCookieService::create($userAuthentication->name, $userAuthentication->loginType)->applyCookieToResponse(
            $response,
            $userAuthentication->getSession(),
            $userAuthentication->getCookieBehavior(),
            $request->getAttribute('normalizedParams')
        );
    }

    /**
     * Check if the user is required for the request.
     * Routes declared with the access "anonymous" (login, password reset, ...) don't require a user.
     *
     * @param Route $route the Route to check against
     * @return bool true when the Route requires an authenticated backend user
     */
    protected function isLoggedInBackendUserRequired(Route $route): bool
    {
        return $route->getAccess()->requiresAuthentication();
    }
}
