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

namespace TYPO3\CMS\Frontend\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * This middleware authenticates a Frontend User (fe_users).
 */
class FrontendUserAuthenticator implements MiddlewareInterface
{
    /**
     * @var Context
     */
    protected $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * Creates a frontend user authentication object, tries to authenticate a user and stores
     * it in the current request as attribute.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $frontendUser = GeneralUtility::makeInstance(FrontendUserAuthentication::class);

        // List of page IDs where to look for frontend user records
        $pid = $request->getParsedBody()['pid'] ?? $request->getQueryParams()['pid'] ?? 0;
        if ($pid) {
            $frontendUser->checkPid_value = implode(',', GeneralUtility::intExplode(',', $pid));
        }

        // Check if a session is transferred, and update the cookie parameters
        $frontendSessionKey = $request->getParsedBody()['FE_SESSION_KEY'] ?? $request->getQueryParams()['FE_SESSION_KEY'] ?? '';
        if ($frontendSessionKey) {
            $request = $this->transferFrontendUserSession($frontendUser, $request, $frontendSessionKey);
        }

        // Authenticate now
        $frontendUser->start();
        $frontendUser->unpack_uc();

        // Register the frontend user as aspect and within the session
        $this->setFrontendUserAspect($frontendUser);
        $request = $request->withAttribute('frontend.user', $frontendUser);

        $response = $handler->handle($request);

        // Store session data for fe_users if it still exists
        if ($frontendUser instanceof FrontendUserAuthentication) {
            $frontendUser->storeSessionData();
        }

        return $response;
    }

    /**
     * It's possible to transfer a frontend user session via a GET/POST parameter 'FE_SESSION_KEY'.
     * In the future, this logic should be moved into the FrontendUserAuthentication object directly,
     * but only if FrontendUserAuthentication does not request superglobals (like $_COOKIE) anymore.
     *
     * @param FrontendUserAuthentication $frontendUser
     * @param ServerRequestInterface $request
     * @param string $frontendSessionKey
     * @return ServerRequestInterface
     */
    protected function transferFrontendUserSession(
        FrontendUserAuthentication $frontendUser,
        ServerRequestInterface $request,
        string $frontendSessionKey
    ): ServerRequestInterface {
        [$sessionId, $hash] = explode('-', $frontendSessionKey);
        // If the session key hash check is OK, set the cookie
        if (md5($sessionId . '/' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']) === (string)$hash) {
            $cookieName = FrontendUserAuthentication::getCookieName();

            // keep the global cookie overwriting for now, as long as FrontendUserAuthentication does not
            // use the request object for fetching the cookie information.
            $_COOKIE[$cookieName] = $sessionId;
            if (isset($_SERVER['HTTP_COOKIE'])) {
                // See http://forge.typo3.org/issues/27740
                $_SERVER['HTTP_COOKIE'] .= ';' . $cookieName . '=' . $sessionId;
            }
            // Add the cookie to the Server Request object
            $cookieParams = $request->getCookieParams();
            $cookieParams[$cookieName] = $sessionId;
            $request = $request->withCookieParams($cookieParams);
            $frontendUser->forceSetCookie = true;
            $frontendUser->dontSetCookie = false;
        }
        return $request;
    }

    /**
     * Register the frontend user as aspect
     *
     * @param AbstractUserAuthentication $user
     */
    protected function setFrontendUserAspect(AbstractUserAuthentication $user)
    {
        $this->context->setAspect('frontend.user', GeneralUtility::makeInstance(UserAspect::class, $user));
    }
}
