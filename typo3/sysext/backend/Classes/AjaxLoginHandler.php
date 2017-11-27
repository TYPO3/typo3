<?php
namespace TYPO3\CMS\Backend;

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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * This is the ajax handler for backend login after timeout.
 */
class AjaxLoginHandler
{
    /**
     * Handles the actual login process, more specifically it defines the response.
     * The login details were sent in as part of the ajax request and automatically logged in
     * the user inside the TYPO3 CMS bootstrap part of the ajax call. If that was successful, we have
     * a BE user and reset the timer and hide the login window.
     * If it was unsuccessful, we display that and show the login box again.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function loginAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        if ($this->isAuthorizedBackendSession()) {
            $result = ['success' => true];
            if ($this->hasLoginBeenProcessed()) {
                $formProtection = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get();
                $formProtection->setSessionTokenFromRegistry();
                $formProtection->persistSessionToken();
            }
        } else {
            $result = ['success' => false];
        }

        $response->getBody()->write(json_encode(['login' => $result]));
        return $response;
    }

    /**
     * Logs out the current BE user
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function logoutAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $backendUser = $this->getBackendUser();
        $backendUser->logoff();

        $response->getBody()->write(json_encode([
            'logout' => [
                'success' => !isset($backendUser->user['uid'])
            ]
        ]));
        return $response;
    }

    /**
     * Refreshes the login without needing login information. We just refresh the session.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function refreshAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->getBackendUser()->checkAuthentication();

        $response->getBody()->write(json_encode([
            'refresh' => [
                'success' => true
            ]
        ]));
        return $response;
    }

    /**
     * Checks if the user session is expired yet
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function isTimedOutAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $session = [
            'timed_out' => false,
            'will_time_out' => false,
            'locked' => false
        ];
        $backendUser = $this->getBackendUser();
        if (@is_file(PATH_typo3conf . 'LOCK_BACKEND')) {
            $session['locked'] = true;
        } elseif (!isset($backendUser->user['uid'])) {
            $session['timed_out'] = true;
        } else {
            $backendUser->fetchUserSession(true);
            $ses_tstamp = $backendUser->user['ses_tstamp'];
            $timeout = $backendUser->sessionTimeout;
            // If 120 seconds from now is later than the session timeout, we need to show the refresh dialog.
            // 120 is somewhat arbitrary to allow for a little room during the countdown and load times, etc.
            $session['will_time_out'] = $GLOBALS['EXEC_TIME'] >= $ses_tstamp + $timeout - 120;
        }
        $response->getBody()->write(json_encode(['login' => $session]));
        return $response;
    }

    /**
     * Checks if a user is logged in and the session is active.
     *
     * @return bool
     */
    protected function isAuthorizedBackendSession()
    {
        $backendUser = $this->getBackendUser();
        return $backendUser !== null && $backendUser instanceof BackendUserAuthentication && isset($backendUser->user['uid']);
    }

    /**
     * Check whether the user was already authorized or not
     *
     * @return bool
     */
    protected function hasLoginBeenProcessed()
    {
        $loginFormData = $this->getBackendUser()->getLoginFormData();
        return $loginFormData['status'] === 'login' && !empty($loginFormData['uname']) && !empty($loginFormData['uident']);
    }

    /**
     * @return BackendUserAuthentication|null
     */
    protected function getBackendUser()
    {
        return isset($GLOBALS['BE_USER']) ? $GLOBALS['BE_USER'] : null;
    }
}
