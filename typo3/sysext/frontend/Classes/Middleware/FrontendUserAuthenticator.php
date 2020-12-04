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
use TYPO3\CMS\Core\Context\Context;
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
        // no matter if we have an active user we try to fetch matching groups which can
        // be set without an user (simulation for instance!)
        $frontendUser->fetchGroupData();

        // Register the frontend user as aspect and within the request
        $userAspect = $frontendUser->createUserAspect();
        $this->context->setAspect('frontend.user', $userAspect);
        $request = $request->withAttribute('frontend.user', $frontendUser);

        $response = $handler->handle($request);

        // Store session data for fe_users if it still exists
        if ($frontendUser instanceof FrontendUserAuthentication) {
            $frontendUser->storeSessionData();
            if ($frontendUser->sendNoCacheHeaders) {
                $response = $this->applyHeadersToResponse($response);
            }
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
        if (hash_equals(md5($sessionId . '/' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']), (string)$hash)) {
            $cookieName = FrontendUserAuthentication::getCookieName();

            // keep the global cookie overwriting for now, as long as FrontendUserAuthentication does not
            // use the request object for fetching the cookie information.
            $_COOKIE[$cookieName] = $sessionId;
            if (isset($_SERVER['HTTP_COOKIE'])) {
                // See https://forge.typo3.org/issues/27740
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
     * Adding headers to the response to avoid caching on the client side.
     * These headers will override any previous headers of these names sent.
     * Get the http headers to be sent if an authenticated user is available,
     * in order to disallow browsers to store the response on the client side.
     *
     * @param ResponseInterface $response
     * @return ResponseInterface the modified response object.
     */
    protected function applyHeadersToResponse(ResponseInterface $response): ResponseInterface
    {
        $headers = [
            'Expires' => 0,
            'Last-Modified' => gmdate('D, d M Y H:i:s') . ' GMT',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Pragma' => 'no-cache'
        ];
        foreach ($headers as $headerName => $headerValue) {
            $response = $response->withHeader($headerName, (string)$headerValue);
        }
        return $response;
    }
}
