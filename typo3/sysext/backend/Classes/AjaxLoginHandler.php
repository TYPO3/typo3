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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\AjaxRequestHandler;

/**
 * This is the ajax handler for backend login after timeout.
 */
class AjaxLoginHandler {

	/**
	 * Handles the actual login process, more specifically it defines the response.
	 * The login details were sent in as part of the ajax request and automatically logged in
	 * the user inside the TYPO3 CMS bootstrap part of the ajax call. If that was successful, we have
	 * a BE user and reset the timer and hide the login window.
	 * If it was unsuccessful, we display that and show the login box again.
	 *
	 * @param array $parameters Parameters (not used)
	 * @param AjaxRequestHandler $ajaxObj The calling parent AJAX object
	 * @return void
	 */
	public function login(array $parameters, AjaxRequestHandler $ajaxObj) {
		if ($this->isAuthorizedBackendSession()) {
			$json = array('success' => TRUE);
			if ($this->hasLoginBeenProcessed()) {
				$formProtection = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get();
				$formProtection->setSessionTokenFromRegistry();
				$formProtection->persistSessionToken();
			}
		} else {
			$json = array('success' => FALSE);
		}
		$ajaxObj->addContent('login', $json);
		$ajaxObj->setContentFormat('json');
	}

	/**
	 * Checks if a user is logged in and the session is active.
	 *
	 * @return bool
	 */
	protected function isAuthorizedBackendSession() {
		$backendUser = $this->getBackendUser();
		return $backendUser !== NULL && $backendUser instanceof BackendUserAuthentication && isset($backendUser->user['uid']);
	}

	/**
	 * Check whether the user was already authorized or not
	 *
	 * @return bool
	 */
	protected function hasLoginBeenProcessed() {
		$loginFormData = $this->getBackendUser()->getLoginFormData();
		return $loginFormData['status'] === 'login' && !empty($loginFormData['uname']) && !empty($loginFormData['uident']);
	}

	/**
	 * Logs out the current BE user
	 *
	 * @param array $parameters Parameters (not used)
	 * @param AjaxRequestHandler $ajaxObj The calling parent AJAX object
	 * @return void
	 */
	public function logout(array $parameters, AjaxRequestHandler $ajaxObj) {
		$backendUser = $this->getBackendUser();
		$backendUser->logoff();
		$ajaxObj->addContent('logout', array(
			'success' => !isset($backendUser->user['uid']))
		);
		$ajaxObj->setContentFormat('json');
	}

	/**
	 * Refreshes the login without needing login information. We just refresh the session.
	 *
	 * @param array $parameters Parameters (not used)
	 * @param AjaxRequestHandler $ajaxObj The calling parent AJAX object
	 * @return void
	 */
	public function refreshLogin(array $parameters, AjaxRequestHandler $ajaxObj) {
		$this->getBackendUser()->checkAuthentication();
		$ajaxObj->addContent('refresh', array('success' => TRUE));
		$ajaxObj->setContentFormat('json');
	}

	/**
	 * Checks if the user session is expired yet
	 *
	 * @param array $parameters Parameters (not used)
	 * @param AjaxRequestHandler $ajaxObj The calling parent AJAX object
	 * @return void
	 */
	public function isTimedOut(array $parameters, AjaxRequestHandler $ajaxObj) {
		$ajaxObj->setContentFormat('json');
		$response = array(
			'timed_out' => FALSE,
			'will_time_out' => FALSE,
			'locked' => FALSE
		);
		$backendUser = $this->getBackendUser();
		if (@is_file(PATH_typo3conf . 'LOCK_BACKEND')) {
			$response['locked'] = TRUE;
		} elseif (!isset($backendUser->user['uid'])) {
			$response['timed_out'] = TRUE;
		} else {
			$backendUser->fetchUserSession(TRUE);
			$ses_tstamp = $backendUser->user['ses_tstamp'];
			$timeout = $backendUser->auth_timeout_field;
			// If 120 seconds from now is later than the session timeout, we need to show the refresh dialog.
			// 120 is somewhat arbitrary to allow for a little room during the countdown and load times, etc.
			$response['will_time_out'] = $GLOBALS['EXEC_TIME'] >= $ses_tstamp + $timeout - 120;
		}
		$ajaxObj->addContent('login', $response);
	}

	/**
	 * @return BackendUserAuthentication|NULL
	 */
	protected function getBackendUser() {
		return isset($GLOBALS['BE_USER']) ? $GLOBALS['BE_USER'] : NULL;
	}
}
