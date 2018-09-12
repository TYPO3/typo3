<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Frontend\Middleware;

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
 * A valid $GLOBALS['TSFE'] object is needed for the time being, being fully backwards-compatible.
 */
class FrontendUserAuthenticator implements MiddlewareInterface
{
    /**
     * Creates a frontend user authentication object, tries to authenticate a user
     * and stores the object in $GLOBALS['TSFE']->fe_user.
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

        // Keep the backwards-compatibility for TYPO3 v9, to have the fe_user within the global TSFE object
        $GLOBALS['TSFE']->fe_user = $frontendUser;

        // Call hook for possible manipulation of frontend user object
        // This hook is kept for compatibility reasons, however, it should be fairly simple to add a custom middleware
        // for this purpose
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['initFEuser'])) {
            trigger_error('The "initFEuser" hook will be removed in TYPO3 v10.0 in favor of PSR-15. Use a middleware instead.', E_USER_DEPRECATED);
            $_params = ['pObj' => &$GLOBALS['TSFE']];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['initFEuser'] as $_funcRef) {
                GeneralUtility::callUserFunction($_funcRef, $_params, $GLOBALS['TSFE']);
            }
        }

        // Register the frontend user as aspect
        $this->setFrontendUserAspect(GeneralUtility::makeInstance(Context::class), $frontendUser);

        return $handler->handle($request);
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
        list($sessionId, $hash) = explode('-', $frontendSessionKey);
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
            // @deprecated: we override the current request because it was enriched by cookie information here.
            $GLOBALS['TYPO3_REQUEST'] = $request;
            $frontendUser->forceSetCookie = true;
            $frontendUser->dontSetCookie = false;
        }
        return $request;
    }

    /**
     * Register the frontend user as aspect
     *
     * @param Context $context
     * @param AbstractUserAuthentication $user
     */
    protected function setFrontendUserAspect(Context $context, AbstractUserAuthentication $user)
    {
        $context->setAspect('frontend.user', GeneralUtility::makeInstance(UserAspect::class, $user));
    }
}
