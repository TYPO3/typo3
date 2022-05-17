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
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\RateLimiter\LimiterInterface;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\RouteRedirect;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\Mfa\MfaRequiredException;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Controller\ErrorPageController;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\RateLimiter\RateLimiterFactory;
use TYPO3\CMS\Core\RateLimiter\RequestRateLimitedException;
use TYPO3\CMS\Core\Session\UserSessionManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;

/**
 * Initializes the backend user authentication object (BE_USER) and the global LANG object.
 *
 * @internal
 */
class BackendUserAuthenticator extends \TYPO3\CMS\Core\Middleware\BackendUserAuthenticator implements LoggerAwareInterface
{
    use LoggerAwareTrait;

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
        '/ajax/login/preflight',
        '/ajax/login/refresh',
        '/ajax/login/timedout',
        '/ajax/core/requirejs',
    ];

    private LanguageServiceFactory $languageServiceFactory;
    private RateLimiterFactory $rateLimiterFactory;

    public function __construct(
        Context $context,
        LanguageServiceFactory $languageServiceFactory,
        RateLimiterFactory $rateLimiterFactory
    ) {
        parent::__construct($context);
        $this->languageServiceFactory = $languageServiceFactory;
        $this->rateLimiterFactory = $rateLimiterFactory;
    }

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
        // Rate Limiting
        $rateLimiter = $this->ensureLoginRateLimit($GLOBALS['BE_USER'], $request);
        // Whether multi-factor authentication is requested
        $mfaRequested = $route->getOption('_identifier') === 'auth_mfa';
        try {
            $GLOBALS['BE_USER']->start($request);
        } catch (MfaRequiredException $mfaRequiredException) {
            // If MFA is required and we are not already on the "auth_mfa"
            // route, force the user to it for further authentication.
            if (!$mfaRequested && !$this->isLoggedInBackendUserRequired($route)) {
                return $this->redirectToMfaEndpoint(
                    'auth_mfa',
                    $GLOBALS['BE_USER'],
                    $request,
                    ['identifier' => $mfaRequiredException->getProvider()->getIdentifier()]
                );
            }
        }

        // Register the backend user as aspect and initializing workspace once for TSconfig conditions
        $this->setBackendUserAspect($GLOBALS['BE_USER'], (int)($GLOBALS['BE_USER']->user['workspace_id'] ?? 0));
        if ($this->isLoggedInBackendUserRequired($route)) {
            if (!$this->context->getAspect('backend.user')->isLoggedIn()) {
                $uri = GeneralUtility::makeInstance(UriBuilder::class)->buildUriWithRedirect(
                    'login',
                    [],
                    RouteRedirect::createFromRoute($route, $request->getQueryParams())
                );
                $response = new RedirectResponse($uri);
                return $this->enrichResponseWithHeadersAndCookieInformation($response, $GLOBALS['BE_USER']);
            }
            if (!$GLOBALS['BE_USER']->isUserAllowedToLogin()) {
                $content = GeneralUtility::makeInstance(ErrorPageController::class)->errorAction(
                    'Login Error',
                    'TYPO3 is in maintenance mode at the moment. Only administrators are allowed access.',
                    AbstractMessage::ERROR,
                    1294585860,
                    503
                );
                $response = new HtmlResponse($content, 503);
                return $this->enrichResponseWithHeadersAndCookieInformation($response, $GLOBALS['BE_USER']);
            }
        }
        if ($this->context->getAspect('backend.user')->isLoggedIn()) {
            $GLOBALS['BE_USER']->initializeBackendLogin();
            // Reset the limiter after successful login
            if ($rateLimiter) {
                $rateLimiter->reset();
            }
            // In case the current request is not targeted to authenticate against MFA, the "mfa"
            // key is not yet set in session (indicating that MFA has already been passed) and it's
            // no "switch-user" mode, check whether the user is required to set up MFA and redirect
            // to the corresponding setup endpoint if not already on it.
            if (!$mfaRequested
                && !(bool)($GLOBALS['BE_USER']->getSessionData('mfa') ?? false)
                && !$GLOBALS['BE_USER']->getOriginalUserIdWhenInSwitchUserMode()
                && $GLOBALS['BE_USER']->isMfaSetupRequired()
                && $route->getOption('_identifier') !== 'setup_mfa'
            ) {
                return $this->redirectToMfaEndpoint('setup_mfa', $GLOBALS['BE_USER'], $request);
            }
        }
        $GLOBALS['LANG'] = $this->languageServiceFactory->createFromUserPreferences($GLOBALS['BE_USER']);
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
     * Initiate a redirect to the given MFA endpoint with necessary cookies and headers appended
     */
    protected function redirectToMfaEndpoint(
        string $endpoint,
        BackendUserAuthentication $user,
        ServerRequestInterface $request,
        array $parameters = []
    ): ResponseInterface {
        // GLOBALS[LANG] needs to be set up, because the UriBuilder is generating a token, which in turn
        // needs the FormProtectionFactory, which then builds a Message Closure with GLOBALS[LANG] (hacky, yes!)
        $GLOBALS['LANG'] = $this->languageServiceFactory->createFromUserPreferences($user);
        $response = new RedirectResponse(
            GeneralUtility::makeInstance(UriBuilder::class)->buildUriWithRedirect($endpoint, $parameters, RouteRedirect::createFromRequest($request))
        );
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

    protected function ensureLoginRateLimit(BackendUserAuthentication $user, ServerRequestInterface $request): ?LimiterInterface
    {
        if (!$user->isActiveLogin($request)) {
            return null;
        }
        $loginRateLimiter = $this->rateLimiterFactory->createLoginRateLimiter($user, $request);
        $limit = $loginRateLimiter->consume();
        if (!$limit->isAccepted()) {
            $this->logger->debug('Login request has been rate limited for IP address {ipAddress}', ['ipAddress' => $request->getAttribute('normalizedParams')->getRemoteAddress()]);
            $dateformat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] . ' ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'];
            $lockedUntil = $limit->getRetryAfter()->getTimestamp() > 0 ?
                ' until ' . date($dateformat, $limit->getRetryAfter()->getTimestamp()) : '';
            throw new RequestRateLimitedException(
                HttpUtility::HTTP_STATUS_403,
                'The login is locked' . $lockedUntil . ' due to too many failed login attempts from your IP address.',
                'Login Request Rate Limited',
                1616175867
            );
        }
        return $loginRateLimiter;
    }
}
