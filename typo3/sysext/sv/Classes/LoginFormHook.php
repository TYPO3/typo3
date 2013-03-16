<?php
namespace TYPO3\CMS\Sv;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Dmitry Dulepov <dmitry@typo3.org>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * This class contains a BE login form hook. It adds all necessary JavaScript
 * for the superchallenged authentication.
 *
 * @author Dmitry Dulepov <dmitry@typo3.org>
 */
class LoginFormHook {

	/**
	 * Provides form code for the superchallenged authentication.
	 *
	 * @param array $params Parameters to the script
	 * @param \TYPO3\CMS\Backend\Controller\LoginController $pObj Calling object
	 * @return string The code for the login form
	 */
	public function getLoginFormTag(array $params, \TYPO3\CMS\Backend\Controller\LoginController &$pObj) {
		// Get the code according to the login level
		switch ($pObj->loginSecurityLevel) {
		case 'challenged':

		case 'superchallenged':
			$_SESSION['login_challenge'] = $this->getChallenge();
			$content = '<form action="index.php" method="post" name="loginform" ' . 'onsubmit="doChallengeResponse(' . ($pObj->loginSecurityLevel == 'challenged' ? 0 : 1) . ');">' . '<input type="hidden" name="challenge" value="' . htmlspecialchars($_SESSION['login_challenge']) . '" />';
			break;
		case 'normal':
			$content = '<form action="index.php" method="post" name="loginform" onsubmit="document.loginform.userident.value=document.loginform.p_field.value;document.loginform.p_field.value=\'\';return true;">';
			break;
		default:
			// No code for unknown level!
			$content = '';
		}
		return $content;
	}

	/**
	 * Provides form code for the superchallenged authentication.
	 *
	 * @param array $params Parameters to the script
	 * @param \TYPO3\CMS\Backend\Controller\LoginController $pObj Calling object
	 * @return string The code for the login form
	 */
	public function getLoginScripts(array $params, \TYPO3\CMS\Backend\Controller\LoginController &$pObj) {
		$content = '';
		if ($pObj->loginSecurityLevel == 'superchallenged' || $pObj->loginSecurityLevel == 'challenged') {
			$content = '
				<script type="text/javascript" src="md5.js"></script>
				' . $GLOBALS['TBE_TEMPLATE']->wrapScriptTags('
					function doChallengeResponse(superchallenged) {	//
						password = document.loginform.p_field.value;
						if (password) {
							if (superchallenged) {
								password = MD5(password);	// this makes it superchallenged!!
							}
							str = document.loginform.username.value+":"+password+":"+document.loginform.challenge.value;
							document.loginform.userident.value = MD5(str);
							document.loginform.p_field.value = "";
							return true;
						}
					}
					');
		}
		return $content;
	}

	/**
	 * Create a random challenge string
	 *
	 * @return string Challenge value
	 */
	protected function getChallenge() {
		$challenge = md5(uniqid('') . getmypid());
		return $challenge;
	}

}


?>