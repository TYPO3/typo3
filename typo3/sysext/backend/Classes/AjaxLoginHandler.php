<?php
namespace TYPO3\CMS\Backend;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2013 Christoph Koehler (christoph@webempoweredchurch.org)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * This is the ajax handler for backend login after timeout.
 *
 * @author Christoph Koehler <christoph@webempoweredchurch.org>
 */
class AjaxLoginHandler {

	/**
	 * Handles the actual login process, more specifically it defines the response.
	 * The login details were sent in as part of the ajax request and automatically logged in
	 * the user inside the init.php part of the ajax call. If that was successful, we have
	 * a BE user and reset the timer and hide the login window.
	 * If it was unsuccessful, we display that and show the login box again.
	 *
	 * @param array $parameters Parameters (not used)
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj The calling parent AJAX object
	 * @return void
	 */
	public function login(array $parameters, \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj) {
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
	 * @return boolean
	 */
	protected function isAuthorizedBackendSession() {
		return isset($GLOBALS['BE_USER']) && $GLOBALS['BE_USER'] instanceof \TYPO3\CMS\Core\Authentication\BackendUserAuthentication && isset($GLOBALS['BE_USER']->user['uid']);
	}

	/**
	 * Check whether the user was already authorized or not
	 *
	 * @return boolean
	 */
	protected function hasLoginBeenProcessed() {
		$loginFormData = $GLOBALS['BE_USER']->getLoginFormData();
		return $loginFormData['status'] == 'login' && isset($loginFormData['uname']) && isset($loginFormData['uident']) && isset($loginFormData['chalvalue']) && (string) $_COOKIE[\TYPO3\CMS\Core\Authentication\BackendUserAuthentication::getCookieName()] !== (string) $GLOBALS['BE_USER']->id;
	}

	/**
	 * Logs out the current BE user
	 *
	 * @param array $parameters Parameters (not used)
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj The calling parent AJAX object
	 * @return void
	 */
	public function logout(array $parameters, \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj) {
		$GLOBALS['BE_USER']->logoff();
		if ($GLOBALS['BE_USER']->user['uid']) {
			$ajaxObj->addContent('logout', array('success' => FALSE));
		} else {
			$ajaxObj->addContent('logout', array('success' => TRUE));
		}
		$ajaxObj->setContentFormat('json');
	}

	/**
	 * Refreshes the login without needing login information. We just refresh the session.
	 *
	 * @param array $parameters Parameters (not used)
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj The calling parent AJAX object
	 * @return void
	 */
	public function refreshLogin(array $parameters, \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj) {
		$GLOBALS['BE_USER']->checkAuthentication();
		$ajaxObj->addContent('refresh', array('success' => TRUE));
		$ajaxObj->setContentFormat('json');
	}

	/**
	 * Checks if the user session is expired yet
	 *
	 * @param array $parameters Parameters (not used)
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj The calling parent AJAX object
	 * @return void
	 * @todo Define visibility
	 */
	public function isTimedOut(array $parameters, \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj) {
		if (is_object($GLOBALS['BE_USER'])) {
			$ajaxObj->setContentFormat('json');
			if (@is_file((PATH_typo3conf . 'LOCK_BACKEND'))) {
				$ajaxObj->addContent('login', array('will_time_out' => FALSE, 'locked' => TRUE));
				$ajaxObj->setContentFormat('json');
			} elseif (!isset($GLOBALS['BE_USER']->user['uid'])) {
				$ajaxObj->addContent('login', array('timed_out' => TRUE));
			} else {
				$GLOBALS['BE_USER']->fetchUserSession(TRUE);
				$ses_tstamp = $GLOBALS['BE_USER']->user['ses_tstamp'];
				$timeout = $GLOBALS['BE_USER']->auth_timeout_field;
				// If 120 seconds from now is later than the session timeout, we need to show the refresh dialog.
				// 120 is somewhat arbitrary to allow for a little room during the countdown and load times, etc.
				if ($GLOBALS['EXEC_TIME'] >= $ses_tstamp + $timeout - 120) {
					$ajaxObj->addContent('login', array('will_time_out' => TRUE));
				} else {
					$ajaxObj->addContent('login', array('will_time_out' => FALSE));
				}
			}
		} else {
			$ajaxObj->addContent('login', array('success' => FALSE, 'error' => 'No BE_USER object'));
		}
	}

	/**
	 * Gets a MD5 challenge.
	 *
	 * @param array $parameters Parameters (not used)
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $parent The calling parent AJAX object
	 * @return void
	 */
	public function getChallenge(array $parameters, \TYPO3\CMS\Core\Http\AjaxRequestHandler $parent) {
		session_start();
		$_SESSION['login_challenge'] = md5(uniqid('') . getmypid());
		session_commit();
		$parent->addContent('challenge', $_SESSION['login_challenge']);
		$parent->setContentFormat('json');
	}

}


?>