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
use TYPO3\CMS\Backend\FrontendBackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This middleware authenticates a Backend User (be_user) (pre)-viewing a frontend page.
 *
 * This middleware also ensures that $GLOBALS['LANG'] is available, however it is possible that
 * a different middleware later-on might unset the BE_USER as he/she is not allowed to preview a certain
 * page due to rights management. As this can only happen once the page ID is resolved, this will happen
 * after the routing middleware.
 *
 * Currently, this middleware depends on the availability of $GLOBALS['TSFE'], however, this is solely
 * due to backwards-compatibility and will be disabled in the future.
 */
class BackendUserAuthenticator implements MiddlewareInterface
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
        // PRE BE_USER HOOK
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['preBeUser'])) {
            trigger_error('The "preBeUser" hook will be removed in TYPO3 v10.0 in favor of PSR-15. Use a middleware instead.', E_USER_DEPRECATED);
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['preBeUser'] as $_funcRef) {
                $_params = [];
                GeneralUtility::callUserFunction($_funcRef, $_params, $GLOBALS['TSFE']);
            }
        }

        // Initializing a possible logged-in Backend User
        // If the backend cookie is set,
        // we proceed and check if a backend user is logged in.
        $backendUserObject = null;
        if (isset($request->getCookieParams()[BackendUserAuthentication::getCookieName()])) {
            $backendUserObject = $this->initializeBackendUser($request);
        }

        $GLOBALS['BE_USER'] = $backendUserObject;

        // POST BE_USER HOOK
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['postBeUser'])) {
            $_params = [
                'BE_USER' => &$GLOBALS['BE_USER']
            ];
            trigger_error('The "postBeUser" hook will be removed in TYPO3 v10.0 in favor of PSR-15. Use a middleware instead.', E_USER_DEPRECATED);
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['postBeUser'] as $_funcRef) {
                GeneralUtility::callUserFunction($_funcRef, $_params, $GLOBALS['TSFE']);
            }
        }

        // Load specific dependencies which are necessary for a valid Backend User
        // like $GLOBALS['LANG'] for labels in the language of the BE User, the router, and ext_tables.php for all modules
        // So things like Frontend Editing and Admin Panel can use this for generating links to the TYPO3 Backend.
        if ($GLOBALS['BE_USER'] instanceof FrontendBackendUserAuthentication) {
            Bootstrap::initializeLanguageObject();
            Bootstrap::initializeBackendRouter();
            Bootstrap::loadExtTables();
            $this->setBackendUserAspect(GeneralUtility::makeInstance(Context::class), $GLOBALS['BE_USER']);
        }

        return $handler->handle($request);
    }

    /**
     * Creates the backend user object and returns it.
     *
     * @param ServerRequestInterface $request
     * @return FrontendBackendUserAuthentication|null the backend user object or null if there was no valid user found
     * @throws \TYPO3\CMS\Core\Exception
     */
    protected function initializeBackendUser(ServerRequestInterface $request)
    {
        // New backend user object
        $backendUserObject = GeneralUtility::makeInstance(FrontendBackendUserAuthentication::class);
        $backendUserObject->start();
        $backendUserObject->unpack_uc();
        if (!empty($backendUserObject->user['uid'])) {
            $backendUserObject->fetchGroupData();
        }
        // Unset the user initialization if any setting / restriction applies
        if (!$this->isAuthenticated($backendUserObject, $request->getAttribute('normalizedParams'))) {
            $backendUserObject = null;
        }
        return $backendUserObject;
    }

    /**
     * Implementing the access checks that the TYPO3 CMS bootstrap script does before a user is ever logged in.
     *
     * @param FrontendBackendUserAuthentication $user
     * @param NormalizedParams $normalizedParams
     * @return bool Returns TRUE if access is OK
     */
    protected function isAuthenticated(FrontendBackendUserAuthentication $user, NormalizedParams $normalizedParams)
    {
        // Check IP
        $ipMask = trim($GLOBALS['TYPO3_CONF_VARS']['BE']['IPmaskList'] ?? '');
        if ($ipMask && !GeneralUtility::cmpIP($normalizedParams->getRemoteAddress(), $ipMask)) {
            return false;
        }
        // Check SSL (https)
        if ((bool)$GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSL'] && !$normalizedParams->isHttps()) {
            return false;
        }
        return $user->backendCheckLogin();
    }

    /**
     * Register the backend user as aspect
     *
     * @param Context $context
     * @param BackendUserAuthentication|null $user
     */
    protected function setBackendUserAspect(Context $context, BackendUserAuthentication $user)
    {
        $context->setAspect('backend.user', GeneralUtility::makeInstance(UserAspect::class, $user));
        $context->setAspect('workspace', GeneralUtility::makeInstance(WorkspaceAspect::class, $user->workspace));
    }
}
