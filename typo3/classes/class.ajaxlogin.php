<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Christoph Koehler (christoph@webempoweredchurch.org)
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
 * @author	Christoph Koehler <christoph@webempoweredchurch.org>
 */
class AjaxLogin {

	/**
	 * Handles the actual login process, more specifically it defines the response.
	 * The login details were sent in as part of the ajax request and automatically logged in
	 * the user inside the init.php part of the ajax call. If that was successful, we have
	 * a BE user and reset the timer and hide the login window.
	 * If it was unsuccessful, we display that and show the login box again.
	 *
	 * @param string $params 	Always empty.
	 * @param string $ajaxObj	The Ajax object used to return content and set content types
	 * @return void
	 */
	public function login($params = array(), TYPO3AJAX &$ajaxObj = null) {
		if ($GLOBALS['BE_USER']->user['uid']) {
			$json = '{success: true}';
		} else {
			$json = '{success: false}';
		}
		$ajaxObj->addContent('login', $json);
	}

	/**
	 * Logs out the current BE user
	 *
	 * @param string $params 		Always empty.
	 * @param string $TYPO3AJAX     The Ajax object used to return content and set content types
	 * @return void
	 */
	public function logout($params = array(), TYPO3AJAX &$ajaxObj = null) {
		$GLOBALS['BE_USER']->logoff();
		if($GLOBALS['BE_USER']->user['uid']) {
			$ajaxObj->addContent('logout', '{sucess: false}');
		} else {
			$ajaxObj->addContent('logout', '{sucess: true}');
		}
	}

	/**
	 * Refreshes the login without needing login information. We just refresh the session.
	 *
	 *
	 * @param string $params		Always empty.
	 * @param string $ajaxObj       The Ajax object used to return content and set content types
	 * @return void
	 */
	public function refreshLogin($params = array(), TYPO3AJAX &$ajaxObj = null) {
		$GLOBALS['BE_USER']->checkAuthentication();
		$ajaxObj->addContent('refresh', '{sucess: true}');
	}


	/**
	 * Checks if the user session is expired yet
	 *
	 * @param string $params 		Always empty.
	 * @param string $TYPO3AJAX     The Ajax object used to return content and set content types
	 * @return void
	 */
	function isTimedOut($params = array(), TYPO3AJAX &$ajaxObj = null) {
		if(is_object($GLOBALS['BE_USER'])) {
			$GLOBALS['BE_USER']->fetchUserSession(true);
			$ses_tstamp = $GLOBALS['BE_USER']->user['ses_tstamp'];
			$timeout = $GLOBALS['BE_USER']->auth_timeout_field;

			// if 120 seconds from now is later than the session timeout, we need to show the refresh dialog.
			// 120 is somewhat arbitrary to allow for a little room during the countdown and load times, etc.
			if($GLOBALS['EXEC_TIME'] >= $ses_tstamp+$timeout-120) {
				$ajaxObj->addContent('login', '{timed_out: true}');
				$ajaxObj->setContentFormat('json');
			} else {
				$ajaxObj->addContent('login', '{timed_out: false}');
				$ajaxObj->setContentFormat('json');
			}
		} else {
			$ajaxObj->addContent('login', '{success: false, error: "No BE_USER object"}');
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/classes/class.ajaxlogin.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/classes/class.ajaxlogin.php']);
}

?>