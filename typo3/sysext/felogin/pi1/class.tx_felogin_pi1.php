<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2010 Steffen Kamper <info@sk-typo3.de>
*  Based on Newloginbox (c) 2002-2004 Kasper Skaarhoj <kasper@typo3.com>
*
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
*
*  The code was adapted from newloginbox, see manual for detailed description
***************************************************************/
/**
 * Plugin 'Website User Login' for the 'felogin' extension.
 *
 * @author	Steffen Kamper <info@sk-typo3.de>
 * @package	TYPO3
 * @subpackage	tx_felogin
 */
class tx_felogin_pi1 extends tslib_pibase {
	var $prefixId      = 'tx_felogin_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_felogin_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'felogin';	// The extension key.
	public $pi_checkCHash = false;
	public $pi_USER_INT_obj = true;

	protected $userIsLoggedIn;	// Is user logged in?
	protected $template;	// holds the template for FE rendering
	protected $uploadDir;	// upload dir, used for flexform template files
	protected $redirectUrl;	// URL for the redirect
	protected $noRedirect = false;	// flag for disable the redirect
	protected $logintype;	// logintype (given as GPvar), possible: login, logout

	/**
	 * The main method of the plugin
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 *
	 * @return	string		The	content that is displayed on the website
	 */
	public function main($content,$conf)	{

			// Loading TypoScript array into object variable:
		$this->conf = $conf;
		$this->uploadDir = 'uploads/tx_felogin/';

			// Loading default pivars
		$this->pi_setPiVarDefaults();

			// Loading language-labels
		$this->pi_loadLL();

			// Init FlexForm configuration for plugin:
		$this->pi_initPIflexForm();
		$this->mergeflexFormValuesIntoConf();


			// Get storage PIDs:
		if ($this->conf['storagePid']) {
			if (intval($this->conf['recursive'])) {
				$this->spid = $this->pi_getPidList($this->conf['storagePid'], intval($this->conf['recursive']));
			} else {
				$this->spid = $this->conf['storagePid'];
			}
		} else {
			$pids = $GLOBALS['TSFE']->getStorageSiterootPids();
			$this->spid = $pids['_STORAGE_PID'];
		}

			// GPvars:
		$this->logintype = t3lib_div::_GP('logintype');
		$this->referer = t3lib_div::_GP('referer');
		$this->noRedirect = ($this->piVars['noredirect'] || $this->conf['redirectDisable']);

			// if config.typolinkLinkAccessRestrictedPages is set, the var is return_url
		$returnUrl =  t3lib_div::_GP('return_url');
		if ($returnUrl) {
			$this->redirectUrl = $returnUrl;
		} else {
			$this->redirectUrl = t3lib_div::_GP('redirect_url');
		}

			// Get Template
		$templateFile = $this->conf['templateFile'] ? $this->conf['templateFile'] : 'EXT:felogin/template.html';
		$this->template = $this->cObj->fileResource($templateFile);

			// Is user logged in?
		$this->userIsLoggedIn = $GLOBALS['TSFE']->loginUser;

			// Redirect
		if ($this->conf['redirectMode'] && !$this->conf['redirectDisable'] && !$this->noRedirect) {
			$redirectUrl = $this->processRedirect();
			if (count($redirectUrl)) {
				$this->redirectUrl = $this->conf['redirectFirstMethod'] ? array_shift($redirectUrl) : array_pop($redirectUrl);
			} else {
				$this->redirectUrl = '';
		}
		}

			// What to display
		$content='';
		if ($this->piVars['forgot']) {
			$content .= $this->showForgot();
		} elseif ($this->piVars['forgothash']) {
			$content .= $this->changePassword();
		} else {
			if($this->userIsLoggedIn && !$this->logintype) {
				$content .= $this->showLogout();
			} else {
				$content .= $this->showLogin();
			}
		}
		
			// Process the redirect
		if (($this->logintype === 'login' || $this->logintype === 'logout') && $this->redirectUrl && !$this->noRedirect) {
			if (!$GLOBALS['TSFE']->fe_user->cookieId) {
				$content .= $this->cObj->stdWrap($this->pi_getLL('cookie_warning', '', 1), $this->conf['cookieWarning_stdWrap.']);
			} else {
				t3lib_utility_Http::redirect($this->redirectUrl);
			}
		}

			// Adds hook for processing of extra item markers / special
		if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['postProcContent']) && is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['postProcContent'])) {
			$_params = array(
				'content' => $content
			);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['postProcContent'] as $_funcRef) {
				$content = t3lib_div::callUserFunction($_funcRef, $_params, $this);
			}
		}

		return $this->conf['wrapContentInBaseClass'] ? $this->pi_wrapInBaseClass($content) : $content;

	}

	 /**
	  * Shows the forgot password form
	  *
	  * @return	string		content
	  */
	 protected function showForgot() {
		$subpart = $this->cObj->getSubpart($this->template, '###TEMPLATE_FORGOT###');
		$subpartArray = $linkpartArray = array();
		$postData =  t3lib_div::_POST($this->prefixId);

		if ($postData['forgot_email']) {

				// get hashes for compare
			$postedHash = $postData['forgot_hash'];
			$hashData = $GLOBALS['TSFE']->fe_user->getKey('ses', 'forgot_hash');


			if ($postedHash === $hashData['forgot_hash']) {
				$row = FALSE;

					// look for user record
				$data = $GLOBALS['TYPO3_DB']->fullQuoteStr($this->piVars['forgot_email'], 'fe_users');
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'uid, username, password, email',
					'fe_users',
					'(email=' . $data .' OR username=' . $data . ') AND pid IN ('.$GLOBALS['TYPO3_DB']->cleanIntList($this->spid).') '.$this->cObj->enableFields('fe_users')
				);

				if ($GLOBALS['TYPO3_DB']->sql_num_rows($res)) {
					$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
				}

				if ($row) {
							// generate an email with the hashed link
						$error = $this->generateAndSendHash($row);
				}
					// generate message
				if ($error) {
					$markerArray['###STATUS_MESSAGE###'] = $this->cObj->stdWrap($error, $this->conf['forgotMessage_stdWrap.']);
				} else {
					$markerArray['###STATUS_MESSAGE###'] = $this->cObj->stdWrap($this->pi_getLL('ll_forgot_reset_message_emailSent', '', 1), $this->conf['forgotMessage_stdWrap.']);
				}
				$subpartArray['###FORGOT_FORM###'] = '';


			} else {
					//wrong email
				$markerArray['###STATUS_MESSAGE###'] = $this->getDisplayText('forgot_reset_message', $this->conf['forgotMessage_stdWrap.']);
				$markerArray['###BACKLINK_LOGIN###'] = '';
			}
		} else {
			$markerArray['###STATUS_MESSAGE###'] = $this->getDisplayText('forgot_reset_message', $this->conf['forgotMessage_stdWrap.']);
			$markerArray['###BACKLINK_LOGIN###'] = '';
		}

		$markerArray['###BACKLINK_LOGIN###'] = $this->getPageLink($this->pi_getLL('ll_forgot_header_backToLogin', '', 1), array());
		$markerArray['###STATUS_HEADER###'] = $this->getDisplayText('forgot_header', $this->conf['forgotHeader_stdWrap.']);

		$markerArray['###LEGEND###'] = $this->pi_getLL('reset_password', '', 1);
		$markerArray['###ACTION_URI###'] = $this->getPageLink('', array($this->prefixId . '[forgot]'=>1), true);
		$markerArray['###EMAIL_LABEL###'] = $this->pi_getLL('your_email', '', 1);
		$markerArray['###FORGOT_PASSWORD_ENTEREMAIL###'] = $this->pi_getLL('forgot_password_enterEmail', '', 1);
		$markerArray['###FORGOT_EMAIL###'] = $this->prefixId.'[forgot_email]';
		$markerArray['###SEND_PASSWORD###'] = $this->pi_getLL('reset_password', '', 1);

		$markerArray['###DATA_LABEL###'] = $this->pi_getLL('ll_enter_your_data', '', 1);



		$markerArray = array_merge($markerArray, $this->getUserFieldMarkers());

			// generate hash
		$hash = md5($this->generatePassword(3));
		$markerArray['###FORGOTHASH###'] = $hash;
			// set hash in feuser session
		$GLOBALS['TSFE']->fe_user->setKey('ses', 'forgot_hash', array('forgot_hash' => $hash));


		return $this->cObj->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, $linkpartArray);
	}

	/**
	 * This function checks the hash from link and checks the validity. If it's valid it shows the form for
	 * changing the password and process the change of password after submit, if not valid it returns the error message
	 *
	 * @return	string		The content.
	 */
	protected function changePassword() {

		$subpartArray = $linkpartArray = array();
		$done = false;

		$minLength = intval($this->conf['newPasswordMinLength']) ? intval($this->conf['newPasswordMinLength']) : 6;

		$subpart = $this->cObj->getSubpart($this->template, '###TEMPLATE_CHANGEPASSWORD###');

		$markerArray['###STATUS_HEADER###'] = $this->getDisplayText('change_password_header', $this->conf['changePasswordHeader_stdWrap.']);
		$markerArray['###STATUS_MESSAGE###'] = sprintf($this->getDisplayText('change_password_message', $this->conf['changePasswordMessage_stdWrap.']), $minLength);

		$markerArray['###BACKLINK_LOGIN###'] = '';
		$uid = $this->piVars['user'];
		$piHash = $this->piVars['forgothash'];

		$hash = explode('|', $piHash);
		if (intval($uid) == 0) {
			$markerArray['###STATUS_MESSAGE###'] = $this->getDisplayText('change_password_notvalid_message', $this->conf['changePasswordMessage_stdWrap.']);
			$subpartArray['###CHANGEPASSWORD_FORM###'] = '';
		} else {
			$user = $this->pi_getRecord('fe_users', intval($uid));
			$userHash = $user['felogin_forgotHash'];
			$compareHash = explode('|', $userHash);

			if (!$compareHash || !$compareHash[1] || $compareHash[0] < time() ||  $hash[0] != $compareHash[0] ||  md5($hash[1]) != $compareHash[1]) {
				$markerArray['###STATUS_MESSAGE###'] = $this->getDisplayText('change_password_notvalid_message',$this->conf['changePasswordMessage_stdWrap.']);
				$subpartArray['###CHANGEPASSWORD_FORM###'] = '';
			} else {
					// all is fine, continue with new password
				$postData = t3lib_div::_POST($this->prefixId);

				if (isset($postData['changepasswordsubmit'])) {
					if (strlen($postData['password1']) < $minLength) {
			 			$markerArray['###STATUS_MESSAGE###'] = sprintf($this->getDisplayText('change_password_tooshort_message', $this->conf['changePasswordMessage_stdWrap.']), $minLength);
					} elseif ($postData['password1'] != $postData['password2']) {
						$markerArray['###STATUS_MESSAGE###'] = sprintf($this->getDisplayText('change_password_notequal_message', $this->conf['changePasswordMessage_stdWrap.']), $minLength);
					} else {
						$newPass = $postData['password1'];

						if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['password_changed']) {
							$_params = array(
								'user' => $user,
								'newPassword' => $newPass,
							);
							foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['password_changed'] as $_funcRef) {
								if ($_funcRef) {
									t3lib_div::callUserFunction($_funcRef, $_params, $this);
								}
							}
							$newPass = $_params['newPassword'];
						}

							// save new password and clear DB-hash
						$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
								'fe_users',
								'uid=' . $user['uid'],
								array('password' => $newPass, 'felogin_forgotHash' => '')
							);
						$markerArray['###STATUS_MESSAGE###'] = $this->getDisplayText('change_password_done_message', $this->conf['changePasswordMessage_stdWrap.']);
						$done = true;
						$subpartArray['###CHANGEPASSWORD_FORM###'] = '';
						$markerArray['###BACKLINK_LOGIN###'] = $this->getPageLink($this->pi_getLL('ll_forgot_header_backToLogin', '', 1), array());
					}
				}

				if (!$done) {
					// Change password form
					$markerArray['###ACTION_URI###'] = $this->pi_getPageLink($GLOBALS['TSFE']->id, '', array(
						$this->prefixId . '[user]' => $user['uid'],
						$this->prefixId . '[forgothash]' => $piHash
					));
					$markerArray['###LEGEND###'] = $this->pi_getLL('change_password', '', 1);
					$markerArray['###NEWPASSWORD1_LABEL###'] = $this->pi_getLL('newpassword_label1', '', 1);
					$markerArray['###NEWPASSWORD2_LABEL###'] = $this->pi_getLL('newpassword_label2', '', 1);
					$markerArray['###NEWPASSWORD1###'] = $this->prefixId . '[password1]';
					$markerArray['###NEWPASSWORD2###'] = $this->prefixId . '[password2]';
					$markerArray['###STORAGE_PID###'] = $this->spid;
					$markerArray['###SEND_PASSWORD###'] = $this->pi_getLL('change_password', '', 1);
					$markerArray['###FORGOTHASH###'] = $piHash;
				}
			}
		}

		return $this->cObj->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, $linkpartArray);
	}

	/**
	 * generates a hashed link and send it with email
	 *
	 * @param	array		$user   contains user data
	 * @return	string		Empty string with success, error message with no success
	 */
	protected function generateAndSendHash($user) {
		$hours = intval($this->conf['forgotLinkHashValidTime']) > 0 ? intval($this->conf['forgotLinkHashValidTime']) : 24;
		$validEnd = time() + 3600 * $hours;
		$validEndString = date($this->conf['dateFormat'], $validEnd);

		$hash =  md5(rand());
		$randHash = $validEnd . '|' . $hash;
		$randHashDB = $validEnd . '|' . md5($hash);

		//write hash to DB
		$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('fe_users', 'uid=' . $user['uid'], array('felogin_forgotHash' => $randHashDB));

		// send hashlink to user
		$this->conf['linkPrefix'] = -1;
		$isAbsRelPrefix = !empty($GLOBALS['TSFE']->absRefPrefix);
		$isBaseURL  = !empty($GLOBALS['TSFE']->baseUrl);
		$isFeloginBaseURL = !empty($this->conf['feloginBaseURL']);

		if ($isFeloginBaseURL) {
				// first priority
			$this->conf['linkPrefix'] = $this->conf['feloginBaseURL'];
		} else {
			if ($isBaseURL) {
					// 3rd priority
				$this->conf['linkPrefix'] = $GLOBALS['TSFE']->baseUrl;
			}
		}

		if ($this->conf['linkPrefix'] == -1 && !$isAbsRelPrefix) {
				// no preix is set, return the error
			return $this->pi_getLL('ll_change_password_nolinkprefix_message');
		}

		$link = ($isAbsRelPrefix ? '' : $this->conf['linkPrefix']) . $this->pi_getPageLink($GLOBALS['TSFE']->id, '', array(
			$this->prefixId . '[user]' => $user['uid'],
			$this->prefixId . '[forgothash]' => $randHash
		));

		$msg = sprintf($this->pi_getLL('ll_forgot_validate_reset_password', '', 0), $user['username'], $link, $validEndString);

			// no RDCT - Links for security reasons
		$oldSetting = $GLOBALS['TSFE']->config['config']['notification_email_urlmode'];
		$GLOBALS['TSFE']->config['config']['notification_email_urlmode'] = 0;
			// send the email
		$this->cObj->sendNotifyEmail($msg, $user['email'], '', $this->conf['email_from'], $this->conf['email_fromName'], $this->conf['replyTo']);
			// restore settings
		$GLOBALS['TSFE']->config['config']['notification_email_urlmode'] = $oldSetting;

		return '';
	}

	/**
	 * Shows logout form
	 *
	 * @return	string		The content.
	 */
	protected function showLogout() {
		$subpart = $this->cObj->getSubpart($this->template, '###TEMPLATE_LOGOUT###');
		$subpartArray = $linkpartArray = array();

		$markerArray['###STATUS_HEADER###'] = $this->getDisplayText('status_header',$this->conf['logoutHeader_stdWrap.']);
		$markerArray['###STATUS_MESSAGE###']=$this->getDisplayText('status_message',$this->conf['logoutMessage_stdWrap.']);$this->cObj->stdWrap($this->flexFormValue('message','s_status'),$this->conf['logoutMessage_stdWrap.']);

		$markerArray['###LEGEND###'] = $this->pi_getLL('logout', '', 1);
		$markerArray['###ACTION_URI###'] = $this->getPageLink('',array(),true);
		$markerArray['###LOGOUT_LABEL###'] = $this->pi_getLL('logout', '', 1);
		$markerArray['###NAME###'] = htmlspecialchars($GLOBALS['TSFE']->fe_user->user['name']);
		$markerArray['###STORAGE_PID###'] = $this->spid;
		$markerArray['###USERNAME###'] = htmlspecialchars($GLOBALS['TSFE']->fe_user->user['username']);
		$markerArray['###USERNAME_LABEL###'] = $this->pi_getLL('username', '', 1);
		$markerArray['###NOREDIRECT###'] = $this->noRedirect ? '1' : '0';
		$markerArray['###PREFIXID###'] = $this->prefixId;
		$markerArray = array_merge($markerArray, $this->getUserFieldMarkers());

		if ($this->redirectUrl) {
				// use redirectUrl for action tag because of possible access restricted pages
			$markerArray['###ACTION_URI###'] = htmlspecialchars($this->redirectUrl);
			$this->redirectUrl = '';
		}
		return $this->cObj->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, $linkpartArray);
	}

	/**
	 * Shows login form
	 *
	 * @return	string		content
	 */
	 protected function showLogin() {
		$subpart = $this->cObj->getSubpart($this->template, '###TEMPLATE_LOGIN###');
		$subpartArray = $linkpartArray = array();

		$gpRedirectUrl = '';

		$markerArray['###LEGEND###'] = $this->pi_getLL('oLabel_header_welcome', '', 1);

		if($this->logintype === 'login') {
			if($this->userIsLoggedIn) {
					// login success
				$markerArray['###STATUS_HEADER###'] = $this->getDisplayText('success_header',$this->conf['successHeader_stdWrap.']);
				$markerArray['###STATUS_MESSAGE###'] = $this->getDisplayText('success_message', $this->conf['successMessage_stdWrap.']);
				$markerArray = array_merge($markerArray, $this->getUserFieldMarkers());
				$subpartArray['###LOGIN_FORM###'] = '';

					// Hook for general actions after after login has been confirmed (by Thomas Danzl <thomas@danzl.org>)
				if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['login_confirmed']) {
					$_params = array();
					foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['login_confirmed'] as $_funcRef) {
						if ($_funcRef) {
							t3lib_div::callUserFunction($_funcRef, $_params, $this);
						}
					}
				}

			} else {
					// login error
				$markerArray['###STATUS_HEADER###'] = $this->getDisplayText('error_header',$this->conf['errorHeader_stdWrap.']);
				$markerArray['###STATUS_MESSAGE###'] = $this->getDisplayText('error_message',$this->conf['errorMessage_stdWrap.']);
				$gpRedirectUrl = t3lib_div::_GP('redirect_url');
			}
		} else {
			if($this->logintype === 'logout') {
					// login form after logout
				$markerArray['###STATUS_HEADER###'] = $this->getDisplayText('logout_header',$this->conf['welcomeHeader_stdWrap.']);
				$markerArray['###STATUS_MESSAGE###'] = $this->getDisplayText('logout_message',$this->conf['welcomeMessage_stdWrap.']);
			} else {
					// login form
				$markerArray['###STATUS_HEADER###'] = $this->getDisplayText('welcome_header',$this->conf['welcomeHeader_stdWrap.']);
				$markerArray['###STATUS_MESSAGE###'] = $this->getDisplayText('welcome_message',$this->conf['welcomeMessage_stdWrap.']);
			}
		}


			// Hook (used by kb_md5fepw extension by Kraft Bernhard <kraftb@gmx.net>)
			// This hook allows to call User JS functions.
			// The methods should also set the required JS functions to get included
		$onSubmit = '';
		$extraHidden = '';
		$onSubmitAr = array();
		$extraHiddenAr = array();

	 		// check for referer redirect method. if present, save referer in form field
		if (t3lib_div::inList($this->conf['redirectMode'], 'referer') || t3lib_div::inList($this->conf['redirectMode'], 'refererDomains')) {
			$referer = $this->referer ? $this->referer : t3lib_div::getIndpEnv('HTTP_REFERER');
			if ($referer) {
				$extraHiddenAr[] = '<input type="hidden" name="referer" value="' . htmlspecialchars($referer) . '" />';
			}
		}

		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['loginFormOnSubmitFuncs'])) {
			$_params = array();
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['loginFormOnSubmitFuncs'] as $funcRef) {
				list($onSub, $hid) = t3lib_div::callUserFunction($funcRef, $_params, $this);
				$onSubmitAr[] = $onSub;
				$extraHiddenAr[] = $hid;
			}
		}
		if (count($onSubmitAr)) {
			$onSubmit = implode('; ', $onSubmitAr).'; return true;';
		}
		if (count($extraHiddenAr)) {
			$extraHidden = implode(LF, $extraHiddenAr);
		}

		if (!$gpRedirectUrl && $this->redirectUrl) {
			$gpRedirectUrl = $this->redirectUrl;
		}

			// Login form
		$markerArray['###ACTION_URI###'] = $this->getPageLink('',array(),true);
		$markerArray['###EXTRA_HIDDEN###'] = $extraHidden; // used by kb_md5fepw extension...
		$markerArray['###LEGEND###'] = $this->pi_getLL('login', '', 1);
		$markerArray['###LOGIN_LABEL###'] = $this->pi_getLL('login', '', 1);
		$markerArray['###ON_SUBMIT###'] = $onSubmit; // used by kb_md5fepw extension...
		$markerArray['###PASSWORD_LABEL###'] = $this->pi_getLL('password', '', 1);
		$markerArray['###STORAGE_PID###'] = $this->spid;
		$markerArray['###USERNAME_LABEL###'] = $this->pi_getLL('username', '', 1);
		$markerArray['###REDIRECT_URL###'] = htmlspecialchars($gpRedirectUrl);
		$markerArray['###NOREDIRECT###'] = $this->noRedirect ? '1' : '0';
		$markerArray['###PREFIXID###'] = $this->prefixId;
		$markerArray = array_merge($markerArray, $this->getUserFieldMarkers());

		if ($this->flexFormValue('showForgotPassword','sDEF') || $this->conf['showForgotPasswordLink']) {
			$linkpartArray['###FORGOT_PASSWORD_LINK###'] = explode('|',$this->getPageLink('|',array($this->prefixId.'[forgot]'=>1)));
			$markerArray['###FORGOT_PASSWORD###'] = $this->pi_getLL('ll_forgot_header', '', 1);
		} else {
			$subpartArray['###FORGOTP_VALID###'] = '';
		}


			// Permanent Login is only possible if permalogin is not deactivated (-1) and lifetime is greater than 0
		if ($this->conf['showPermaLogin'] && t3lib_div::inList('0,1,2', $GLOBALS['TYPO3_CONF_VARS']['FE']['permalogin']) && $GLOBALS['TYPO3_CONF_VARS']['FE']['lifetime'] > 0) {
			$markerArray['###PERMALOGIN###'] = $this->pi_getLL('permalogin', '', 1);
			if($GLOBALS['TYPO3_CONF_VARS']['FE']['permalogin'] == 1) {
				$markerArray['###PERMALOGIN_HIDDENFIELD_ATTRIBUTES###'] = 'disabled="disabled"';
				$markerArray['###PERMALOGIN_CHECKBOX_ATTRIBUTES###'] = 'checked="checked"';
			} else {
				$markerArray['###PERMALOGIN_HIDDENFIELD_ATTRIBUTES###'] = '';
				$markerArray['###PERMALOGIN_CHECKBOX_ATTRIBUTES###'] = '';
			}
		} else {
			$subpartArray['###PERMALOGIN_VALID###'] = '';
		}
		return $this->cObj->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, $linkpartArray);
	}

	/**
	 * Process redirect methods. The function searches for a redirect url using all configured methods.
	 *
	 * @return	string		redirect url
	 */
	 protected function processRedirect() {
	 	$redirect_url = array();
		if ($this->conf['redirectMode']) {
			$redirectMethods = t3lib_div::trimExplode(',', $this->conf['redirectMode'], TRUE);
			foreach ($redirectMethods as $redirMethod) {
				if ($GLOBALS['TSFE']->loginUser && $this->logintype === 'login') {
						// logintype is needed because the login-page wouldn't be accessible anymore after a login (would always redirect)
					switch ($redirMethod) {
						case 'groupLogin': // taken from dkd_redirect_at_login written by Ingmar Schlecht; database-field changed
							$groupData = $GLOBALS['TSFE']->fe_user->groupData;
							$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
								'felogin_redirectPid',
								$GLOBALS['TSFE']->fe_user->usergroup_table,
								'felogin_redirectPid!="" AND uid IN (' . implode(',', $groupData['uid']) . ')'
							);
							if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res))	{
								$redirect_url[] = $this->pi_getPageLink($row[0]); // take the first group with a redirect page
							}
						break;
						case 'userLogin':
							$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
								'felogin_redirectPid',
								$GLOBALS['TSFE']->fe_user->user_table,
								$GLOBALS['TSFE']->fe_user->userid_column . '=' . $GLOBALS['TSFE']->fe_user->user['uid'] . ' AND felogin_redirectPid!=""'
							);
							if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res))	{
								$redirect_url[] = $this->pi_getPageLink($row[0]);
							}
						break;
						case 'login':
							if ($this->conf['redirectPageLogin']) {
								$redirect_url[] = $this->pi_getPageLink(intval($this->conf['redirectPageLogin']));
							}
						break;
						case 'getpost':
							$redirect_url[] = $this->redirectUrl;
						break;
						case 'referer':
								// avoid forced logout, when trying to login immediatly after a logout
							$redirect_url[] = preg_replace('/[&?]logintype=[a-z]+/', '', $this->referer);
						break;
						case 'refererDomains':
								// Auto redirect.
								// Feature to redirect to the page where the user came from (HTTP_REFERER).
								// Allowed domains to redirect to, can be configured with plugin.tx_felogin_pi1.domains
								// Thanks to plan2.net / Martin Kutschker for implementing this feature.
							if ($this->conf['domains']) {
								$url = $this->referer;
									// is referring url allowed to redirect?
								$match = array();
								if (preg_match('/^http://([[:alnum:]._-]+)//', $url, $match)) {
									$redirect_domain = $match[1];
									$found = false;
									foreach(split(',', $this->conf['domains']) as $d) {
										if (preg_match('/(^|\.)/'.$d.'$', $redirect_domain)) {
											$found = true;
											break;
										}
									}
									if (!$found) {
										$url = '';
									}
								}

									// Avoid forced logout, when trying to login immediatly after a logout
								if ($url) {
									$redirect_url[] = preg_replace('/[&?]logintype=[a-z]+/', '', $url);
							}
							}
						break;
					}
				} else if ($this->logintype === 'login') { // after login-error
					switch ($redirMethod) {
						case 'loginError':
							if ($this->conf['redirectPageLoginError']) {
								$redirect_url[] = $this->pi_getPageLink(intval($this->conf['redirectPageLoginError']));
							}
						break;
					}
				} elseif (($this->logintype == '') && ($redirMethod == 'login') && $this->conf['redirectPageLogin']) {
						// if login and page not accessible
					$this->cObj->typolink('', array(
						'parameter' 				=> $this->conf['redirectPageLogin'],
						'linkAccessRestrictedPages' => TRUE,
					));
					$redirect_url[] = $this->cObj->lastTypoLinkUrl;

				} elseif (($this->logintype == '') && ($redirMethod == 'logout') && $this->conf['redirectPageLogout'] && $GLOBALS['TSFE']->loginUser) {
						// if logout and page not accessible
					$redirect_url[] = $this->pi_getPageLink(intval($this->conf['redirectPageLogout']));

				} elseif ($this->logintype === 'logout') { // after logout

					// Hook for general actions after after logout has been confirmed
					if ($this->logintype === 'logout' && $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['logout_confirmed']) {
						$_params = array();
						foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['logout_confirmed'] as $_funcRef) {
							if ($_funcRef) {
								t3lib_div::callUserFunction($_funcRef, $_params, $this);
							}
						}
					}

					switch ($redirMethod) {
						case 'logout':
							if ($this->conf['redirectPageLogout']) {
								$redirect_url[] = $this->pi_getPageLink(intval($this->conf['redirectPageLogout']));
							}
						break;
					}
				} else { // not logged in
						// Placeholder for maybe future options
					switch ($redirMethod) {
						case 'getpost':
							// preserve the get/post value
							$redirect_url[] = $this->redirectUrl;
						break;
					}
				}

				}
			}
				// remove empty values
			if (count($redirect_url)) {
				return t3lib_div::trimExplode(',', implode(',', $redirect_url), TRUE);
			} else {
				return array();
		}
	}

	/**
	 * Reads flexform configuration and merge it with $this->conf
	 *
	 * @return	void
	 */
	 protected function mergeflexFormValuesIntoConf() {
		$flex = array();
		if ($this->flexFormValue('showForgotPassword', 'sDEF')) {
			$flex['showForgotPassword'] = $this->flexFormValue('showForgotPassword','sDEF');
		}

		if ($this->flexFormValue('showPermaLogin', 'sDEF')) {
			$flex['showPermaLogin'] = $this->flexFormValue('showPermaLogin', 'sDEF');
		}

		if ($this->flexFormValue('pages', 'sDEF')) {
			$flex['pages'] = $this->flexFormValue('pages', 'sDEF');
		}

		if ($this->flexFormValue('recursive', 'sDEF')) {
			$flex['recursive'] = $this->flexFormValue('recursive', 'sDEF');
		}

		if ($this->flexFormValue('templateFile', 'sDEF')) {
			$flex['templateFile'] = $this->uploadDir . $this->flexFormValue('templateFile', 'sDEF');
		}

		if ($this->flexFormValue('redirectMode', 's_redirect')) {
			$flex['redirectMode'] = $this->flexFormValue('redirectMode', 's_redirect');
		}

		if ($this->flexFormValue('redirectFirstMethod', 's_redirect')) {
			$flex['redirectFirstMethod'] = $this->flexFormValue('redirectFirstMethod', 's_redirect');
		}

		if ($this->flexFormValue('redirectDisable', 's_redirect')) {
			$flex['redirectDisable'] = $this->flexFormValue('redirectDisable', 's_redirect');
		}

		if ($this->flexFormValue('redirectPageLogin', 's_redirect')) {
			$flex['redirectPageLogin'] = $this->flexFormValue('redirectPageLogin', 's_redirect');
		}

		if ($this->flexFormValue('redirectPageLoginError', 's_redirect')) {
			$flex['redirectPageLoginError'] = $this->flexFormValue('redirectPageLoginError','s_redirect');
		}

		if ($this->flexFormValue('redirectPageLogout', 's_redirect')) {
			$flex['redirectPageLogout'] = $this->flexFormValue('redirectPageLogout', 's_redirect');
		}

		$pid = $flex['pages'] ? $this->pi_getPidList($flex['pages'], $flex['recursive']) : 0;
		if ($pid > 0) {
			$flex['storagePid'] = $pid;
		}

		$this->conf = array_merge($this->conf, $flex);
	}

	/**
	 * Loads a variable from the flexform
	 *
	 * @param	string		name of variable
	 * @param	string		name of sheet
	 * @return	string		value of var
	 */
	protected function flexFormValue($var, $sheet) {
		return $this->pi_getFFvalue($this->cObj->data['pi_flexform'], $var,$sheet);
	}

	/**
	 * Generate link with typolink function
	 *
	 * @param	string		linktext
	 * @param	array		link vars
	 * @param	boolean		true: returns only url  false (default) returns the link)
	 *
	 * @return	string		link or url
	 */
	 protected function getPageLink($label, $piVars,$returnUrl = false) {
		$additionalParams = '';

		if (count($piVars)) {
			foreach($piVars as $key=>$val) {
				$additionalParams .= '&' . $key . '=' . $val;
			}
		}
			// should GETvars be preserved?
		if ($this->conf['preserveGETvars'])	{
			$additionalParams .= $this->getPreserveGetVars();
		}

		$this->conf['linkConfig.']['parameter'] = $GLOBALS['TSFE']->id;
		if ($additionalParams)	{
			$this->conf['linkConfig.']['additionalParams'] =  $additionalParams;
		}

		if ($returnUrl) {
			return htmlspecialchars($this->cObj->typolink_url($this->conf['linkConfig.']));
		} else {
			return $this->cObj->typolink($label,$this->conf['linkConfig.']);
		}
	}

	/**
	 * Is used by TS-setting preserveGETvars
	 * possible values are "all" or a commaseperated list of GET-vars
	 * they are used as additionalParams for link generation
	 *
	 * @return	string		additionalParams-string
	 */
	 protected function getPreserveGetVars() {

		$params = '';
		$preserveVars =! ($this->conf['preserveGETvars'] || $this->conf['preserveGETvars']=='all' ? array() : implode(',', (array)$this->conf['preserveGETvars']));
		$getVars = t3lib_div::_GET();

		foreach ($getVars as $key => $val) {
			if (stristr($key,$this->prefixId) === false) {
				if (is_array($val)) {
					foreach ($val as $key1 => $val1) {
						if ($this->conf['preserveGETvars'] == 'all' || in_array($key . '[' . $key1 .']', $preserveVars)) {
							$params .= '&' . $key . '[' . $key1 . ']=' . $val1;
						}
					}
				} else {
					if (!in_array($key, array('id','no_cache','logintype','redirect_url','cHash'))) {
						$params .= '&' . $key . '=' . $val;
					}
				}
			}
		}
		return $params;
	}

	/**
	 * Is used by forgot password - function with md5 option.
	 *
	 * @author	Bernhard Kraft
	 *
	 * @param	int			length of new password
	 * @return	string		new password
	 */
	 protected function generatePassword($len) {
		$pass = '';
		while ($len--) {
			$char = rand(0,35);
			if ($char < 10) {
				$pass .= '' . $char;
			} else {
				$pass .= chr($char - 10 + 97);
			}
		}
		return $pass;
	}

	/**
	 * Returns the header / message value from flexform if present, else from locallang.xml
	 *
	 * @param	string		label name
	 * @param	string		TS stdWrap array
	 * @return	string		label text
	 */
	protected function getDisplayText($label, $stdWrapArray=array()) {
		$text = $this->flexFormValue($label, 's_messages') ? $this->cObj->stdWrap($this->flexFormValue($label, 's_messages'), $stdWrapArray) : $this->cObj->stdWrap($this->pi_getLL('ll_'.$label, '', 1), $stdWrapArray);
		$replace = $this->getUserFieldMarkers();
		return strtr($text, $replace);
	}

	/**
	 * Returns Array of markers filled with user fields
	 *
	 * @return	array		marker array
	 */
	protected function getUserFieldMarkers() {
		$marker = array();
		// replace markers with fe_user data
		if ($GLOBALS['TSFE']->fe_user->user) {
			// all fields of fe_user will be replaced, scheme is ###FEUSER_FIELDNAME###
			foreach ($GLOBALS['TSFE']->fe_user->user as $field => $value) {
				$marker['###FEUSER_' . t3lib_div::strtoupper($field) . '###'] = $this->cObj->stdWrap($value, $this->conf['userfields.'][$field . '.']);
			}
			// add ###USER### for compatibility
			$marker['###USER###'] = $marker['###FEUSER_USERNAME###'];
		}
		return $marker;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/felogin/pi1/class.tx_felogin_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/felogin/pi1/class.tx_felogin_pi1.php']);
}

?>