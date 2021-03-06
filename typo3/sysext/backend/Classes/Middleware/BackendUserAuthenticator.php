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
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderManifestInterface;
use TYPO3\CMS\Core\Authentication\Mfa\MfaRequiredException;
use TYPO3\CMS\Core\Controller\ErrorPageController;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Session\UserSessionManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Initializes the backend user authentication object (BE_USER) and the global LANG object.
 *
 * @internal
 */
class BackendUserAuthenticator extends \TYPO3\CMS\Core\Middleware\BackendUserAuthenticator
{
    /**
     * List of requests that don't need a valid BE user
     *
     * @var array
     */
    protected $publicRoutes = [
        '/login',
        '/login/frame',
        '/login/password-reset/forget',
        '/login/password-reset/initiate-reset',
        '/login/password-reset/validate',
        '/login/password-reset/finish',
        '/ajax/login',
        '/ajax/logout',
        '/ajax/login/refresh',
        '/ajax/login/timedout',
        '/ajax/rsa/publickey',
        '/ajax/core/requirejs',
    ];

    /**
     * Calls the bootstrap process to set up $GLOBALS['BE_USER'] AND $GLOBALS['LANG']
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var Route $route */
        $route = $request->getAttribute('route');

        // The global must be available very early, because methods below
        // might trigger code which relies on it. See: #45625
        $GLOBALS['BE_USER'] = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        try {
            $GLOBALS['BE_USER']->start();
        } catch (MfaRequiredException $mfaRequiredException) {
            // If MFA is required and we are not already on the "auth_mfa"
            // route, force the user to it for further authentication
            if ($route->getOption('_identifier') !== 'auth_mfa') {
                return $this->redirectToMfaAuthProcess($GLOBALS['BE_USER'], $mfaRequiredException->getProvider(), $request);
            }
        }

        // Register the backend user as aspect and initializing workspace once for TSconfig conditions
        $this->setBackendUserAspect($GLOBALS['BE_USER'], (int)$GLOBALS['BE_USER']->user['workspace_id']);
        if ($this->isLoggedInBackendUserRequired($route)) {
            if (!$this->context->getAspect('backend.user')->isLoggedIn()) {
                $uri = GeneralUtility::makeInstance(UriBuilder::class)->buildUriWithRedirect(
                    'login',
                    [],
                    $route->getOption('_identifier'),
                    $request->getQueryParams()
                );
                $response = new RedirectResponse($uri);
                return $this->enrichResponseWithHeadersAndCookieInformation($response, $GLOBALS['BE_USER']);
            }
            if (!$GLOBALS['BE_USER']->isUserAllowedToLogin()) {
                $content = GeneralUtility::makeInstance(ErrorPageController::class)->errorAction(
                    'Login Error',
                    'TYPO3 is in maintenance mode at the moment. Only administrators are allowed access.',
                    AbstractMessage::ERROR,
                    1294585860
                );
                $response = new HtmlResponse($content, 503);
                return $this->enrichResponseWithHeadersAndCookieInformation($response, $GLOBALS['BE_USER']);
            }
        }
        if ($this->context->getAspect('backend.user')->isLoggedIn()) {
            $GLOBALS['BE_USER']->initializeBackendLogin();
        }
        $GLOBALS['LANG'] = LanguageService::createFromUserPreferences($GLOBALS['BE_USER']);
        // Re-setting the user and take the workspace from the user object now
        $this->setBackendUserAspect($GLOBALS['BE_USER']);
        $response = $handler->handle($request);
        $this->sessionGarbageCollection();
        return $this->enrichResponseWithHeadersAndCookieInformation($response, $GLOBALS['BE_USER']);
    }

    /**
     * Backend requests should always apply Set-Cookie information and never be cacheable.
     * This is also needed if there is a redirect from somewhere in the code.
     *
     * @param ResponseInterface $response
     * @param BackendUserAuthentication|null $userAuthentication
     * @return ResponseInterface
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     */
    protected function enrichResponseWithHeadersAndCookieInformation(
        ResponseInterface $response,
        ?BackendUserAuthentication $userAuthentication
    ): ResponseInterface {
        if ($userAuthentication) {
            // If no backend user is logged-in, the cookie should be removed
            if (!$this->context->getAspect('backend.user')->isLoggedIn()) {
                $userAuthentication->removeCookie();
            }
            // Ensure to always apply a cookie
            $response = $userAuthentication->appendCookieToResponse($response);
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
     * Initiate a redirect to the auth_mfa route with the given
     * provider and necessary cookies and headers appended.
     *
     * @param BackendUserAuthentication $user
     * @param MfaProviderManifestInterface $provider
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    protected function redirectToMfaAuthProcess(
        BackendUserAuthentication $user,
        MfaProviderManifestInterface $provider,
        ServerRequestInterface $request
    ): ResponseInterface {
        // GLOBALS[LANG] needs to be set up, because the UriBuilder is generating a token, which in turn
        // needs the FormProtectionFactory, which then builds a Message Closure with GLOBALS[LANG] (hacky, yes!)
        $GLOBALS['LANG'] = LanguageService::createFromUserPreferences($user);
        $uri = GeneralUtility::makeInstance(UriBuilder::class)
            ->buildUriWithRedirectFromRequest(
                'auth_mfa',
                [
                    'identifier' => $provider->getIdentifier()
                ],
                $request
            );
        $response = new RedirectResponse($uri);
        // Add necessary cookies and headers to the response so
        // the already passed authentication step is not lost.
        $response = $user->appendCookieToResponse($response);
        $response = $this->applyHeadersToResponse($response);
        return $response;
    }
    /**
     * Check if the user is required for the request.
     * If we're trying to do a login or an ajax login, don't require a user.
     *
     * @param Route $route the Route path to check against, something like '
     * @return bool true when the Route requires an authenticated backend user
     */
    protected function isLoggedInBackendUserRequired(Route $route): bool
    {
        return in_array($route->getPath(), $this->publicRoutes, true) === false;
    }
}
