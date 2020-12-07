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
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
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
        $route = $request->getAttribute('route');

        // The global must be available very early, because methods below
        // might trigger code which relies on it. See: #45625
        $GLOBALS['BE_USER'] = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        $GLOBALS['BE_USER']->start();
        // Register the backend user as aspect and initializing workspace once for TSconfig conditions
        $this->setBackendUserAspect($GLOBALS['BE_USER'], (int)$GLOBALS['BE_USER']->user['workspace_id']);
        if ($this->isLoggedInBackendUserRequired($route) && !$this->context->getAspect('backend.user')->isLoggedIn()) {
            $uri = GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute('login');
            $response = new RedirectResponse($uri);
            return $this->enrichResponseWithHeadersAndCookieInformation($response, $GLOBALS['BE_USER']);
        }
        try {
            $proceedIfNoUserIsLoggedIn = $this->isLoggedInBackendUserRequired($route) === false;
            // @todo: Ensure that the runtime exceptions are caught
            $GLOBALS['BE_USER']->backendCheckLogin($proceedIfNoUserIsLoggedIn);
            $GLOBALS['LANG'] = LanguageService::createFromUserPreferences($GLOBALS['BE_USER']);
            // Re-setting the user and take the workspace from the user object now
            $this->setBackendUserAspect($GLOBALS['BE_USER']);
            $response = $handler->handle($request);
        } catch (ImmediateResponseException $e) {
            $response = $this->enrichResponseWithHeadersAndCookieInformation(
                $e->getResponse(),
                $GLOBALS['BE_USER']
            );
            // Re-throw this exception
            throw new ImmediateResponseException($response, $e->getCode());
        }
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
