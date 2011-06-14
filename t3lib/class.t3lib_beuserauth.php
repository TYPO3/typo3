<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Contains class for TYPO3 backend user authentication
 *
 * Revised for TYPO3 3.6 July/2003 by Kasper Skårhøj
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @internal
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   76: class t3lib_beUserAuth extends t3lib_userAuthGroup
 *  150:	 function trackBeUser($flag)
 *  168:	 function checkLockToIP()
 *  188:	 function backendCheckLogin()
 *  216:	 function checkCLIuser()
 *  240:	 function backendSetUC()
 *  278:	 function overrideUC()
 *  288:	 function resetUC()
 *  301:	 function emailAtLogin()
 *  353:	 function veriCode()
 *
 * TOTAL FUNCTIONS: 9
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


/**
 * TYPO3 user authentication, backend
 * Could technically have been the same class as t3lib_userauthgroup since these two are always used together and only together.
 * t3lib_userauthgroup contains most of the functions used for checking permissions, authenticating users, setting up the user etc. This class is most interesting in terms of an API for user from outside.
 * This class contains the configuration of the database fields used plus some functions for the authentication process of backend users.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_beUserAuth extends t3lib_userAuthGroup {
	var $session_table = 'be_sessions'; // Table to use for session data.

	var $user_table = 'be_users'; // Table in database with userdata
	var $username_column = 'username'; // Column for login-name
	var $userident_column = 'password'; // Column for password
	var $userid_column = 'uid'; // Column for user-id
	var $lastLogin_column = 'lastlogin';

	var $enablecolumns = Array(
		'rootLevel' => 1,
		'deleted' => 'deleted',
		'disabled' => 'disable',
		'starttime' => 'starttime',
		'endtime' => 'endtime'
	);

	var $formfield_uname = 'username'; // formfield with login-name
	var $formfield_uident = 'userident'; // formfield with password
	var $formfield_chalvalue = 'challenge'; // formfield with a unique value which is used to encrypt the password and username
	var $formfield_status = 'login_status'; // formfield with status: *'login', 'logout'

	var $writeStdLog = 1; // Decides if the writelog() function is called at login and logout
	var $writeAttemptLog = 1; // If the writelog() functions is called if a login-attempt has be tried without success

	var $auth_timeout_field = 6000; // if > 0 : session-timeout in seconds. if FALSE/<0 : no timeout. if string: The string is fieldname from the usertable where the timeout can be found.
	var $lifetime = 0; // 0 = Session-cookies. If session-cookies, the browser will stop session when the browser is closed. Else it keeps the session for $lifetime seconds.
	var $challengeStoredInCookie = TRUE;


		// User Config:
	var $uc;

		// User Config Default values:
		// The array may contain other fields for configuration. For this, see "setup" extension and "TSConfig" document (User TSconfig, "setup.[xxx]....")
	/*
		   Reserved keys for other storage of session data:
		   moduleData
		   moduleSessionID
	   */
	var $uc_default = Array(
		'interfaceSetup' => '', // serialized content that is used to store interface pane and menu positions. Set by the logout.php-script
		'moduleData' => Array(), // user-data for the modules
		'thumbnailsByDefault' => 0,
		'emailMeAtLogin' => 0,
		'condensedMode' => 0,
		'noMenuMode' => 0,
		'startModule' => 'help_aboutmodules',
		'hideSubmoduleIcons' => 0,
		'helpText' => 1,
		'titleLen' => 30,
		'edit_wideDocument' => '0',
		'edit_showFieldHelp' => 'icon',
		'edit_RTE' => '1',
		'edit_docModuleUpload' => '1',
		'enableFlashUploader' => '1',
		'disableCMlayers' => 0,
		'navFrameWidth' => '', // Default is 245 pixels
		'navFrameResizable' => 0,
		'resizeTextareas' => 1,
		'resizeTextareas_MaxHeight' => 300,
		'resizeTextareas_Flexible' => 1,
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name = self::getCookieName();
		$this->loginType = 'BE';
	}

	/**
	 * @static
	 * @return string
	 *
	 * returns the configured cookie name
	 */
	public static function getCookieName() {
		$configuredCookieName = trim($GLOBALS['TYPO3_CONF_VARS']['BE']['cookieName']);
		if (empty($configuredCookieName)) {
			$configuredCookieName = 'be_typo_user';
		}
		return $configuredCookieName;
	}


	/**
	 * Sets the security level for the Backend login
	 *
	 * @return	void
	 */
	function start() {
		$securityLevel = trim($GLOBALS['TYPO3_CONF_VARS']['BE']['loginSecurityLevel']);
		$this->security_level = $securityLevel ? $securityLevel : 'superchallenged';

		parent::start();
	}

	/**
	 * If TYPO3_CONF_VARS['BE']['enabledBeUserIPLock'] is enabled and an IP-list is found in the User TSconfig objString "options.lockToIP", then make an IP comparison with REMOTE_ADDR and return the outcome (TRUE/FALSE)
	 *
	 * @return	boolean		TRUE, if IP address validates OK (or no check is done at all)
	 * @access private
	 */
	function checkLockToIP() {
		$out = 1;
		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['enabledBeUserIPLock']) {
			$IPList = $this->getTSConfigVal('options.lockToIP');
			if (trim($IPList)) {
				$baseIP = t3lib_div::getIndpEnv('REMOTE_ADDR');
				$out = t3lib_div::cmpIP($baseIP, $IPList);
			}
		}
		return $out;
	}

	/**
	 * Check if user is logged in and if so, call ->fetchGroupData() to load group information and access lists of all kind, further check IP, set the ->uc array and send login-notification email if required.
	 * If no user is logged in the default behaviour is to exit with an error message, but this will happen ONLY if the constant TYPO3_PROCEED_IF_NO_USER is set TRUE.
	 * This function is called right after ->start() in fx. init.php
	 *
	 * @return	void
	 */
	function backendCheckLogin() {
		if (!$this->user['uid']) {
			if (!defined('TYPO3_PROCEED_IF_NO_USER') || !TYPO3_PROCEED_IF_NO_USER) {
				t3lib_utility_Http::redirect($GLOBALS['BACK_PATH']);
			}
		} else { // ...and if that's the case, call these functions
			$this->fetchGroupData(); //	The groups are fetched and ready for permission checking in this initialization.	Tables.php must be read before this because stuff like the modules has impact in this
			if ($this->checkLockToIP()) {
				if ($this->isUserAllowedToLogin()) {
					$this->backendSetUC(); // Setting the UC array. It's needed with fetchGroupData first, due to default/overriding of values.
					$this->emailAtLogin(); // email at login - if option set.
				} else {
					throw new RuntimeException('Login Error: TYPO3 is in maintenance mode at the moment. Only administrators are allowed access.', 1294585860);
				}
			} else {
				throw new RuntimeException('Login Error: IP locking prevented you from being authorized. Can\'t proceed, sorry.', 1294585861);
			}
		}
	}

	/**
	 * If the backend script is in CLI mode, it will try to load a backend user named by the CLI module name (in lowercase)
	 *
	 * @return	boolean		Returns TRUE if a CLI user was loaded, otherwise FALSE!
	 */
	function checkCLIuser() {
			// First, check if cliMode is enabled:
		if (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI) {
			if (!$this->user['uid']) {
				if (substr($GLOBALS['MCONF']['name'], 0, 5) == '_CLI_') {
					$userName = strtolower($GLOBALS['MCONF']['name']);
					$this->setBeUserByName($userName);
					if ($this->user['uid']) {
						if (!$this->isAdmin()) {
							return TRUE;
						} else {
							die('ERROR: CLI backend user "' . $userName . '" was ADMIN which is not allowed!' . LF . LF);
						}
					} else {
						die('ERROR: No backend user named "' . $userName . '" was found! [Database: ' . TYPO3_db . ']' . LF . LF);
					}
				} else {
					die('ERROR: Module name, "' . $GLOBALS['MCONF']['name'] . '", was not prefixed with "_CLI_"' . LF . LF);
				}
			} else {
				die('ERROR: Another user was already loaded which is impossible in CLI mode!' . LF . LF);
			}
		}
	}

	/**
	 * Initialize the internal ->uc array for the backend user
	 * Will make the overrides if necessary, and write the UC back to the be_users record if changes has happend
	 *
	 * @return	void
	 * @internal
	 */
	function backendSetUC() {
			// UC - user configuration is a serialized array inside the userobject
		$temp_theSavedUC = unserialize($this->user['uc']); // if there is a saved uc we implement that instead of the default one.
		if (is_array($temp_theSavedUC)) {
			$this->unpack_uc($temp_theSavedUC);
		}
			// Setting defaults if uc is empty
		if (!is_array($this->uc)) {
			$this->uc = array_merge(
				$this->uc_default,
				(array) $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultUC'],
				t3lib_div::removeDotsFromTS((array) $this->getTSConfigProp('setup.default'))
			);
			$this->overrideUC();
			$U = 1;
		}
			// If TSconfig is updated, update the defaultUC.
		if ($this->userTSUpdated) {
			$this->overrideUC();
			$U = 1;
		}
			// Setting default lang from be_user record.
		if (!isset($this->uc['lang'])) {
			$this->uc['lang'] = $this->user['lang'];
			$U = 1;
		}
			// Setting the time of the first login:
		if (!isset($this->uc['firstLoginTimeStamp'])) {
			$this->uc['firstLoginTimeStamp'] = $GLOBALS['EXEC_TIME'];
			$U = TRUE;
		}

			// Saving if updated.
		if ($U) {
			$this->writeUC(); // Method from the t3lib_userauth class.
		}
	}

	/**
	 * Override: Call this function every time the uc is updated.
	 * That is 1) by reverting to default values, 2) in the setup-module, 3) userTS changes (userauthgroup)
	 *
	 * @return	void
	 * @internal
	 */
	function overrideUC() {
		$this->uc = array_merge((array) $this->uc, (array) $this->getTSConfigProp('setup.override')); // Candidate for t3lib_div::array_merge() if integer-keys will some day make trouble...
	}

	/**
	 * Clears the user[uc] and ->uc to blank strings. Then calls ->backendSetUC() to fill it again with reset contents
	 *
	 * @return	void
	 * @internal
	 */
	function resetUC() {
		$this->user['uc'] = '';
		$this->uc = '';
		$this->backendSetUC();
	}

	/**
	 * Will send an email notification to warning_email_address/the login users email address when a login session is just started.
	 * Depends on various parameters whether mails are send and to whom.
	 *
	 * @return	void
	 * @access private
	 */
	function emailAtLogin() {
		if ($this->loginSessionStarted) {
				// Send notify-mail
			$subject = 'At "' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . '"' .
					' from ' . t3lib_div::getIndpEnv('REMOTE_ADDR') .
					(t3lib_div::getIndpEnv('REMOTE_HOST') ? ' (' . t3lib_div::getIndpEnv('REMOTE_HOST') . ')' : '');
			$msg = sprintf('User "%s" logged in from %s (%s) at "%s" (%s)',
				$this->user['username'],
				t3lib_div::getIndpEnv('REMOTE_ADDR'),
				t3lib_div::getIndpEnv('REMOTE_HOST'),
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],
				t3lib_div::getIndpEnv('HTTP_HOST')
			);

				// Warning email address
			if ($GLOBALS['TYPO3_CONF_VARS']['BE']['warning_email_addr']) {
				$warn = 0;
				$prefix = '';
				if (intval($GLOBALS['TYPO3_CONF_VARS']['BE']['warning_mode']) & 1) { // first bit: All logins
					$warn = 1;
					$prefix = $this->isAdmin() ? '[AdminLoginWarning]' : '[LoginWarning]';
				}
				if ($this->isAdmin() && (intval($GLOBALS['TYPO3_CONF_VARS']['BE']['warning_mode']) & 2)) { // second bit: Only admin-logins
					$warn = 1;
					$prefix = '[AdminLoginWarning]';
				}
				if ($warn) {
					$from = t3lib_utility_Mail::getSystemFrom();
					/** @var $mail t3lib_mail_Message */
					$mail = t3lib_div::makeInstance('t3lib_mail_Message');
					$mail->setTo($GLOBALS['TYPO3_CONF_VARS']['BE']['warning_email_addr'])
							->setFrom($from)
							->setSubject($prefix . ' ' . $subject)
							->setBody($msg);
					$mail->send();
				}
			}

				// If An email should be sent to the current user, do that:
			if ($this->uc['emailMeAtLogin'] && strstr($this->user['email'], '@')) {
				$from = t3lib_utility_Mail::getSystemFrom();
				/** @var $mail t3lib_mail_Message */
				$mail = t3lib_div::makeInstance('t3lib_mail_Message');
				$mail->setTo($this->user['email'])
						->setFrom($from)
						->setSubject($subject)
						->setBody($msg);
				$mail->send();
			}
		}
	}

	/**
	 * Determines whether a backend user is allowed to access the backend.
	 *
	 * The conditions are:
	 *	+ backend user is a regular user and adminOnly is not defined
	 *	+ backend user is an admin user
	 *	+ backend user is used in CLI context and adminOnly is explicitely set to "2"
	 *
	 * @return	boolean		Whether a backend user is allowed to access the backend
	 */
	protected function isUserAllowedToLogin() {
		$isUserAllowedToLogin = FALSE;
		$adminOnlyMode = $GLOBALS['TYPO3_CONF_VARS']['BE']['adminOnly'];

			// Backend user is allowed if adminOnly is not set or user is an admin:
		if (!$adminOnlyMode || $this->isAdmin()) {
			$isUserAllowedToLogin = TRUE;
			// Backend user is allowed if adminOnly is set to 2 (CLI) and a CLI process is running:
		} elseif ($adminOnlyMode == 2 && (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI)) {
			$isUserAllowedToLogin = TRUE;
		}

		return $isUserAllowedToLogin;
	}

	/**
	 * Logs out the current user and clears the form protection tokens.
	 */
	public function logoff() {
		if (isset($GLOBALS['BE_USER'])) {
			t3lib_formProtection_Factory::get()->clean();
		}
		parent::logoff();
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_beuserauth.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_beuserauth.php']);
}

?>
