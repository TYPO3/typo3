<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2008 Steffen Kamper <info@sk-typo3.de>
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

require_once(PATH_tslib.'class.tslib_pibase.php');


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
	var $pi_checkCHash = true;
	var $userIsLoggedIn;	// Is user logged in?
	var $template;
	var $uploadDir;
	var $redirectUrl;

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
			$this->spid = $this->conf['storagePid'];
		} else {
			$pids = $GLOBALS['TSFE']->getStorageSiterootPids();
			$this->spid = $pids['_STORAGE_PID'];
		}

			// GPvars:
		$this->logintype = t3lib_div::_GP('logintype');
		$this->redirectUrl = t3lib_div::_GP('redirect_url');

			// if config.typolinkLinkAccessRestrictedPages is set, the var is return_url
		$returnUrl =  t3lib_div::_GP('return_url');  
		if ($returnUrl) {
			$this->redirectUrl = $returnUrl;	 
		}


			// Get Template
		$templateFile = $this->conf['templateFile'] ? $this->conf['templateFile'] : 'EXT:felogin/template.html';
		$this->template = $this->cObj->fileResource($templateFile);

			// Is user logged in?
		$this->userIsLoggedIn = $GLOBALS['TSFE']->loginUser;

			// Redirect
		if ($this->conf['redirectMode'] && !$this->conf['redirectDisable']) {
			$this->redirectUrl = $this->processRedirect();
		}
		$this->redirectUrl = $this->validateRedirectUrl($this->redirectUrl);


			// What to display
		$content='';
		if ($this->piVars['forgot']) {
			$content .= $this->showForgot();
		} else {
			if($this->userIsLoggedIn && !$this->logintype) {
				$content .= $this->showLogout();
			} else {
				$content .= $this->showLogin();
			}
		}



			// Process the redirect
		if (($this->logintype === 'login' || $this->logintype === 'logout') && $this->redirectUrl) {
			if (!$GLOBALS['TSFE']->fe_user->cookieId) {
				$content .= '<p style="color:red; font-weight:bold;">' . $this->pi_getLL('cookie_warning', '', 1) . '</p>';
			} else {
				header('Location: '.t3lib_div::locationHeaderUrl($this->redirectUrl));
				exit;
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

		if ($this->piVars['forgot_email']) {
			if (t3lib_div::validEmail($this->piVars['forgot_email'])) {
					// look for user record and send the password
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'uid, username, password',
					'fe_users',
					'email='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->piVars['forgot_email'], 'fe_users').' AND pid IN ('.$GLOBALS['TYPO3_DB']->cleanIntList($this->spid).') '.$this->cObj->enableFields('fe_users')
				);

				if ($GLOBALS['TYPO3_DB']->sql_num_rows($res)) {
					$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
					$msg = sprintf($this->pi_getLL('ll_forgot_email_password', '', 0), $this->piVars['forgot_email'], $row['username'], $row['password']);
				} else {
					$msg = sprintf($this->pi_getLL('ll_forgot_email_nopassword', '', 0), $this->piVars['forgot_email']);
				}


					// Generate new password with md5 and save it in user record
				if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) && t3lib_extMgm::isLoaded('kb_md5fepw')) {
					$newPass = $this->generatePassword(8);
					$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
						'fe_users',
						'uid=' . $row['uid'],
						array('password' => md5($newPass))
					);
					$msg = sprintf($this->pi_getLL('ll_forgot_email_password', '', 0),$this->piVars['forgot_email'], $row['username'], $newPass);
				}

				$this->cObj->sendNotifyEmail($msg, $this->piVars['forgot_email'], '', $this->conf['email_from'], $this->conf['email_fromName'], $this->conf['replyTo']);
				$markerArray['###STATUS_MESSAGE###'] = $this->cObj->stdWrap(sprintf($this->pi_getLL('ll_forgot_message_emailSent', '', 1), '<em>' . htmlspecialchars($this->piVars['forgot_email']) .'</em>'), $this->conf['forgotMessage_stdWrap.']);
				$subpartArray['###FORGOT_FORM###'] = '';


			} else {
					//wrong email
				$markerArray['###STATUS_MESSAGE###'] = $this->getDisplayText('forgot_message',$this->conf['forgotMessage_stdWrap.']);
				$markerArray['###BACKLINK_LOGIN###'] = '';
			}
		} else {
			$markerArray['###STATUS_MESSAGE###'] = $this->getDisplayText('forgot_message',$this->conf['forgotMessage_stdWrap.']);
			$markerArray['###BACKLINK_LOGIN###'] = '';
		}

		$markerArray['###BACKLINK_LOGIN###'] = $this->getPageLink($this->pi_getLL('ll_forgot_header_backToLogin', '', 1), array());
		$markerArray['###STATUS_HEADER###'] = $this->getDisplayText('forgot_header',$this->conf['forgotHeader_stdWrap.']);

		$markerArray['###LEGEND###'] = $this->pi_getLL('send_password', '', 1);
		$markerArray['###ACTION_URI###'] = $this->getPageLink('',array($this->prefixId.'[forgot]'=>1),true);
		$markerArray['###EMAIL_LABEL###'] = $this->pi_getLL('your_email', '', 1);
		$markerArray['###FORGOT_PASSWORD_ENTEREMAIL###'] = $this->pi_getLL('forgot_password_enterEmail', '', 1);
		$markerArray['###FORGOT_EMAIL###'] = $this->prefixId.'[forgot_email]';
		$markerArray['###SEND_PASSWORD###'] = $this->pi_getLL('send_password', '', 1);

		return $this->cObj->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, $linkpartArray);
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
				$markerArray['###STATUS_MESSAGE###'] = str_replace('###USER###',htmlspecialchars($GLOBALS['TSFE']->fe_user->user['username']),$this->getDisplayText('success_message',$this->conf['successMessage_stdWrap.']));
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
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['loginFormOnSubmitFuncs'])) {
			$_params = array();
			$onSubmitAr = array();
			$extraHiddenAr = array();
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['loginFormOnSubmitFuncs'] as $funcRef) {
				list($onSub, $hid) = t3lib_div::callUserFunction($funcRef, $_params, $this);
				$onSubmitAr[] = $onSub;
				$extraHiddenAr[] = $hid;
			}
		}
		if (count($onSubmitAr)) {
			$onSubmit = implode('; ', $onSubmitAr).'; return true;';
			$extraHidden = implode(chr(10), $extraHiddenAr);
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
		$markerArray['###REDIRECT_URL###'] = $gpRedirectUrl ? htmlspecialchars($gpRedirectUrl) : htmlspecialchars($this->redirectUrl);

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
		if ($this->conf['redirectMode']) {
			foreach (t3lib_div::trimExplode(',', $this->conf['redirectMode'],1) as $redirMethod) {
				if ($GLOBALS['TSFE']->loginUser && $this->logintype === 'login') {
						// logintype is needed because the login-page wouldn't be accessible anymore after a login (would always redirect)
					switch ($redirMethod) {
						case 'groupLogin': // taken from dkd_redirect_at_login written by Ingmar Schlecht; database-field changed
							$groupData = $GLOBALS['TSFE']->fe_user->groupData;
							$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
								'felogin_redirectPid',
								$GLOBALS['TSFE']->fe_user->usergroup_table,
								'felogin_redirectPid!="" AND uid IN ('.implode(',',$groupData['uid']).')'
							);
							if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res))	{
								$redirect_url = $this->pi_getPageLink($row[0],array(),true); // take the first group with a redirect page
							}
						break;
						case 'userLogin':
							$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
								'felogin_redirectPid',
								$GLOBALS['TSFE']->fe_user->user_table,
								$GLOBALS['TSFE']->fe_user->userid_column . '=' . $GLOBALS['TSFE']->fe_user->user['uid'] . ' AND felogin_redirectPid!=""'
							);
							if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res))	{
								$redirect_url = $this->pi_getPageLink($row[0],array(),true);
							}
						break;
						case 'login':
							if ($this->conf['redirectPageLogin']) {
								$redirect_url = $this->pi_getPageLink(intval($this->conf['redirectPageLogin']),array(),true);
							}
						break;
						case 'getpost':
							$redirect_url = $this->redirectUrl;
						break;
						case 'referer':
							$redirect_url = t3lib_div::getIndpEnv('HTTP_REFERER');
								// avoid forced logout, when trying to login immediatly after a logout
							$redirect_url = ereg_replace("[&?]logintype=[a-z]+", '', $redirect_url);
						break;
						case 'refererDomains':
								// Auto redirect.
								// Feature to redirect to the page where the user came from (HTTP_REFERER).
								// Allowed domains to redirect to, can be configured with plugin.tx_felogin_pi1.domains
								// Thanks to plan2.net / Martin Kutschker for implementing this feature.
							if (!$redirect_url && $this->conf['domains']) {
								$redirect_url = t3lib_div::getIndpEnv('HTTP_REFERER');
									// is referring url allowed to redirect?
								$match = array();
								if (ereg('^http://([[:alnum:]._-]+)/', $redirect_url, $match)) {
									$redirect_domain = $match[1];
									$found = false;
									foreach(split(',', $this->conf['domains']) as $d) {
										if (ereg('(^|\.)'.$d.'$', $redirect_domain)) {
											$found = true;
											break;
										}
									}
									if (!$found) {
										$redirect_url = '';
									}
								}

									// Avoid forced logout, when trying to login immediatly after a logout
								$redirect_url = ereg_replace("[&?]logintype=[a-z]+", "", $redirect_url);
							}
						break;
					}
				} else if ($this->logintype === 'login') { // after login-error
					switch ($redirMethod) {
						case 'loginError':
							if ($this->conf['redirectPageLoginError']) {
								$redirect_url = $this->pi_getPageLink(intval($this->conf['redirectPageLoginError']), array(), true);
							}
						break;
					}
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
								$redirect_url = $this->pi_getPageLink(intval($this->conf['redirectPageLogout']), array(), true);
							}
						break;
					}
				} else { // not logged in
						// Placeholder for maybe future options
					switch ($redirMethod) {
						case 'getpost':
							// preserve the get/post value
							$redirect_url = $this->redirectUrl;
						break;
					}
				}

				if ($redirect_url && $this->conf['redirectFirstMethod']) {
					break;
				}
			}
		}
		return $redirect_url;
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
			$flex['recursive'] = $this->flexFormValue('recursive',	'sDEF');
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

		foreach ($getVars as $key=>$val) {
			if (stristr($key,$this->prefixId) === false) {
				if (is_array($val)) {
					foreach ($val as $key1=>$val1) {
						if ($this->conf['preserveGETvars']=='all' || in_array($key.'['.$key1.']',$preserveVars)) {
							$params.='&'.$key.'['.$key1.']='.$val1;
						}
					}
				} else {
					if (!in_array($key,array('id','no_cache','logintype','redirect_url','cHash'))) {
						$params.='&'.$key.'='.$val;
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
				$pass .= ''.$char;
			} else {
				$pass .= chr($char-10+97);
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
		return $this->flexFormValue($label,'s_messages') ? $this->cObj->stdWrap($this->flexFormValue($label,'s_messages'),$stdWrapArray) : $this->cObj->stdWrap($this->pi_getLL('ll_'.$label, '', 1), $stdWrapArray);
	}

	/**
	 * Returns a valid and XSS cleaned url for redirect, checked against configuration "allowedRedirectHosts"
	 *
	 * @param string $url
	 * @return string cleaned referer or empty string if not valid
	 */
	protected function validateRedirectUrl($url) {
		$url = strval($url);
		if ($url === '') {
			return '';
		}

		$decodedUrl = rawurldecode($url);
		$sanitizedUrl = t3lib_div::removeXSS($decodedUrl);

		if ($decodedUrl !== $sanitizedUrl || preg_match('#["<>\\\]+#', $url)) {
			t3lib_div::sysLog(sprintf($this->pi_getLL('xssAttackDetected'), $url), 'felogin', t3lib_div::SYSLOG_SEVERITY_WARNING);
			return '';
		}

			// Validate the URL:
		if ($this->isRelativeUrl($url) || $this->isInCurrentDomain($url) || $this->isInLocalDomain($url)) {
			return $url;
		}

			// URL is not allowed
		t3lib_div::sysLog(sprintf($this->pi_getLL('noValidRedirectUrl'), $url), 'felogin', t3lib_div::SYSLOG_SEVERITY_WARNING);
		return '';
	}

	/**
	 * Determines whether the URL is on the current host
	 * and belongs to the current TYPO3 installation.
	 *
	 * @param string $url URL to be checked
	 * @return boolean Whether the URL belongs to the current TYPO3 installation
	 */
	protected function isInCurrentDomain($url) {
		return (t3lib_div::isOnCurrentHost($url) && t3lib_div::isFirstPartOfStr($url, t3lib_div::getIndpEnv('TYPO3_SITE_URL')));
	}

	/**
	 * Determines whether the URL matches a domain
	 * in the sys_domain databse table.
	 *
	 * @param string $url Absolute URL which needs to be checked
	 * @return boolean Whether the URL is considered to be local
	 */
	protected function isInLocalDomain($url) {
		$result = FALSE;

		if (t3lib_div::isValidUrl($url)) {
			$parsedUrl = parse_url($url);
			if ($parsedUrl['scheme'] === 'http' || $parsedUrl['scheme'] === 'https' ) {
				$host = $parsedUrl['host'];
					// Removes the last path segment and slash sequences like /// (if given):
				$path = preg_replace('#/+[^/]*$#', '', $parsedUrl['path']);

				$localDomains = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
					'domainName',
					'sys_domain',
					'1=1' . $this->cObj->enableFields('sys_domain')
				);
				if (is_array($localDomains)) {
					foreach ($localDomains as $localDomain) {
							// strip trailing slashes (if given)
						$domainName = rtrim($localDomain['domainName'], '/');
						if (t3lib_div::isFirstPartOfStr($host. $path . '/', $domainName . '/')) {
							$result = TRUE;
							break;
						}
					}
				}
			}
		}
		return $result;
	}

	/**
	 * Determines wether the URL is relative to the
	 * current TYPO3 installation.
	 *
	 * @param string $url URL which needs to be checked
	 * @return boolean Whether the URL is considered to be relative
	 */
	protected function isRelativeUrl($url) {
		$parsedUrl = @parse_url($url);
		if ($parsedUrl !== FALSE && !isset($parsedUrl['scheme']) && !isset($parsedUrl['host'])) {
				// If the relative URL starts with a slash, we need to check if it's within the current site path
			return (!t3lib_div::isFirstPartOfStr($parsedUrl['path'], '/') || t3lib_div::isFirstPartOfStr($parsedUrl['path'], t3lib_div::getIndpEnv('TYPO3_SITE_PATH')));
		}
		return FALSE;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/felogin/pi1/class.tx_felogin_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/felogin/pi1/class.tx_felogin_pi1.php']);
}

?>
