<?php
namespace TYPO3\CMS\Core\Authentication;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Authentication of users in TYPO3
 *
 * This class is used to authenticate a login user.
 * The class is used by both the frontend and backend.
 * In both cases this class is a parent class to beuserauth and feuserauth
 *
 * See Inside TYPO3 for more information about the API of the class and internal variables.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author René Fritz <r.fritz@colorcube.de>
 */
abstract class AbstractUserAuthentication {

	// Which global database to connect to
	/**
	 * @todo Define visibility
	 */
	public $global_database = '';

	// Table to use for session data.
	/**
	 * @todo Define visibility
	 */
	public $session_table = '';

	// Session/Cookie name
	/**
	 * @todo Define visibility
	 */
	public $name = '';

	// Session/GET-var name
	/**
	 * @todo Define visibility
	 */
	public $get_name = '';

	// Table in database with userdata
	/**
	 * @todo Define visibility
	 */
	public $user_table = '';

	// Column for login-name
	/**
	 * @todo Define visibility
	 */
	public $username_column = '';

	// Column for password
	/**
	 * @todo Define visibility
	 */
	public $userident_column = '';

	// Column for user-id
	/**
	 * @todo Define visibility
	 */
	public $userid_column = '';

	/**
	 * @todo Define visibility
	 */
	public $lastLogin_column = '';

	/**
	 * @todo Define visibility
	 */
	public $enablecolumns = array(
		'rootLevel' => '',
		// Boolean: If TRUE, 'AND pid=0' will be a part of the query...
		'disabled' => '',
		'starttime' => '',
		'endtime' => '',
		'deleted' => ''
	);

	// Formfield with login-name
	/**
	 * @todo Define visibility
	 */
	public $formfield_uname = '';

	// Formfield with password
	/**
	 * @todo Define visibility
	 */
	public $formfield_uident = '';

	// Formfield with a unique value which is used to encrypt the password and username
	/**
	 * @todo Define visibility
	 */
	public $formfield_chalvalue = '';

	// Formfield with status: *'login', 'logout'. If empty login is not verified.
	/**
	 * @todo Define visibility
	 */
	public $formfield_status = '';

	/**
	 * Sets the level of security. *'normal' = clear-text. 'challenged' = hashed password/username.
	 * from form in $formfield_uident. 'superchallenged' = hashed password hashed again with username.
	 *
	 * @var string
	 * @deprecated since 4.7 will be removed in 6.1
	 */
	public $security_level = 'normal';

	// Server session lifetime. If > 0: session-timeout in seconds. If FALSE or
	// <0: no timeout. If string: The string is a fieldname from the usertable
	// where the timeout can be found.
	/**
	 * @todo Define visibility
	 */
	public $auth_timeout_field = 0;

	// Client session lifetime. 0 = Session-cookies. If session-cookies, the
	// browser will stop the session when the browser is closed. Otherwise this
	// specifies the lifetime of a cookie that keeps the session.
	/**
	 * @todo Define visibility
	 */
	public $lifetime = 0;

	// GarbageCollection. Purge all server session data older than $gc_time seconds.
	// 0 = default to $this->timeout or use 86400 seconds (1 day) if $this->lifetime
	// is 0
	/**
	 * @todo Define visibility
	 */
	public $gc_time = 0;

	// Possibility (in percent) for GarbageCollection to be run.
	/**
	 * @todo Define visibility
	 */
	public $gc_probability = 1;

	// Decides if the writelog() function is called at login and logout
	/**
	 * @todo Define visibility
	 */
	public $writeStdLog = FALSE;

	// If the writelog() functions is called if a login-attempt has be tried
	// without success
	/**
	 * @todo Define visibility
	 */
	public $writeAttemptLog = FALSE;

	// If this is set, headers is sent to assure, caching is NOT done
	/**
	 * @todo Define visibility
	 */
	public $sendNoCacheHeaders = TRUE;

	// If this is set, authentication is also accepted by the $_GET.
	// Notice that the identification is NOT 128bit MD5 hash but reduced.
	// This is done in order to minimize the size for mobile-devices, such as WAP-phones
	/**
	 * @todo Define visibility
	 */
	public $getFallBack = FALSE;

	// The ident-hash is normally 32 characters and should be! But if you are making
	// sites for WAP-devices og other lowbandwidth stuff, you may shorten the length.
	// Never let this value drop below 6. A length of 6 would give you more than
	// 16 mio possibilities.
	/**
	 * @todo Define visibility
	 */
	public $hash_length = 32;

	// Setting this flag TRUE lets user-authetication happen from GET_VARS if
	// POST_VARS are not set. Thus you may supply username/password from the URL.
	/**
	 * @todo Define visibility
	 */
	public $getMethodEnabled = FALSE;

	// If set, will lock the session to the users IP address (all four numbers.
	// Reducing to 1-3 means that only first,
	// second or third part of the IP address is used).
	/**
	 * @todo Define visibility
	 */
	public $lockIP = 4;

	// Keyword list (commalist with no spaces!): "useragent".
	// Each keyword indicates some information that can be included in
	// a integer hash made to lock down usersessions. Configurable through
	// $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['lockHashKeyWords']
	/**
	 * @todo Define visibility
	 */
	public $lockHashKeyWords = 'useragent';

	// warning -emailaddress:
	/**
	 * @todo Define visibility
	 */
	public $warningEmail = '';

	// Period back in time (in seconds) in which number of failed logins are collected
	/**
	 * @todo Define visibility
	 */
	public $warningPeriod = 3600;

	// The maximum accepted number of warnings before an email is sent
	/**
	 * @todo Define visibility
	 */
	public $warningMax = 3;

	// If set, the user-record must $checkPid_value as pid
	/**
	 * @todo Define visibility
	 */
	public $checkPid = TRUE;

	// The pid, the user-record must have as page-id
	/**
	 * @todo Define visibility
	 */
	public $checkPid_value = 0;

	// Internals
	// Internal: Will contain session_id (MD5-hash)
	/**
	 * @todo Define visibility
	 */
	public $id;

	// Internal: Will contain the session_id gotten from cookie or GET method.
	// This is used in statistics as a reliable cookie (one which is known
	// to come from $_COOKIE).
	/**
	 * @todo Define visibility
	 */
	public $cookieId;

	// Indicates if an authentication was started but failed
	/**
	 * @todo Define visibility
	 */
	public $loginFailure = FALSE;

	// Will be set to TRUE if the login session is actually written during auth-check.
	/**
	 * @todo Define visibility
	 */
	public $loginSessionStarted = FALSE;

	// Internal: Will contain user- AND session-data from database (joined tables)
	/**
	 * @todo Define visibility
	 */
	public $user;

	// Internal: Will will be set to the url--ready (eg. '&login=ab7ef8d...')
	//GET-auth-var if getFallBack is TRUE. Should be inserted in links!
	/**
	 * @todo Define visibility
	 */
	public $get_URL_ID = '';

	// Will be set to TRUE if a new session ID was created
	/**
	 * @todo Define visibility
	 */
	public $newSessionID = FALSE;

	// Will force the session cookie to be set every time (lifetime must be 0)
	/**
	 * @todo Define visibility
	 */
	public $forceSetCookie = FALSE;

	// Will prevent the setting of the session cookie (takes precedence over forceSetCookie)
	/**
	 * @todo Define visibility
	 */
	public $dontSetCookie = FALSE;

	// If set, the challenge value will be stored in a session as well so the
	// server can check that is was not forged.
	/**
	 * @todo Define visibility
	 */
	public $challengeStoredInCookie = FALSE;

	// Login type, used for services.
	/**
	 * @todo Define visibility
	 */
	public $loginType = '';

	// "auth" services configuration array from $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']
	/**
	 * @todo Define visibility
	 */
	public $svConfig = array();

	// Write messages into the devlog?
	/**
	 * @todo Define visibility
	 */
	public $writeDevLog = FALSE;

	/**
	 * Starts a user session
	 * Typical configurations will:
	 * a) check if session cookie was set and if not, set one,
	 * b) check if a password/username was sent and if so, try to authenticate the user
	 * c) Lookup a session attached to a user and check timeout etc.
	 * d) Garbage collection, setting of no-cache headers.
	 * If a user is authenticated the database record of the user (array) will be set in the ->user internal variable.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function start() {
		// Backend or frontend login - used for auth services
		if (empty($this->loginType)) {
			throw new \TYPO3\CMS\Core\Exception('No loginType defined, should be set explicitly by subclass');
		}
		// Set level to normal if not already set
		if (!$this->security_level) {
			// Notice: cannot use TYPO3_MODE here because BE user can be logged in and operate inside FE!
			$this->security_level = trim($GLOBALS['TYPO3_CONF_VARS'][$this->loginType]['loginSecurityLevel']);
			if (!$this->security_level) {
				$this->security_level = 'normal';
			}
		}
		// Enable dev logging if set
		if ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['writeDevLog']) {
			$this->writeDevLog = TRUE;
		}
		if ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['writeDevLog' . $this->loginType]) {
			$this->writeDevLog = TRUE;
		}
		if (TYPO3_DLOG) {
			$this->writeDevLog = TRUE;
		}
		if ($this->writeDevLog) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('## Beginning of auth logging.', 'TYPO3\\CMS\\Core\\Authentication\\AbstractUserAuthentication');
		}
		// Init vars.
		$mode = '';
		$this->newSessionID = FALSE;
		// $id is set to ses_id if cookie is present. Else set to FALSE, which will start a new session
		$id = $this->getCookie($this->name);
		$this->svConfig = $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth'];
		// If we have a flash client, take the ID from the GP
		if (!$id && $GLOBALS['CLIENT']['BROWSER'] == 'flash') {
			$id = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP($this->name);
		}
		// If fallback to get mode....
		if (!$id && $this->getFallBack && $this->get_name) {
			$id = isset($_GET[$this->get_name]) ? \TYPO3\CMS\Core\Utility\GeneralUtility::_GET($this->get_name) : '';
			if (strlen($id) != $this->hash_length) {
				$id = '';
			}
			$mode = 'get';
		}
		$this->cookieId = $id;
		// If new session or client tries to fix session...
		if (!$id || !$this->isExistingSessionRecord($id)) {
			// New random session-$id is made
			$id = $this->createSessionId();
			// New session
			$this->newSessionID = TRUE;
		}
		// Internal var 'id' is set
		$this->id = $id;
		// If fallback to get mode....
		if ($mode == 'get' && $this->getFallBack && $this->get_name) {
			$this->get_URL_ID = '&' . $this->get_name . '=' . $id;
		}
		// Set session hashKey lock keywords from configuration; currently only 'useragent' can be used.
		$this->lockHashKeyWords = $GLOBALS['TYPO3_CONF_VARS'][$this->loginType]['lockHashKeyWords'];
		// Make certain that NO user is set initially
		$this->user = '';
		// Set all possible headers that could ensure that the script is not cached on the client-side
		if ($this->sendNoCacheHeaders) {
			header('Expires: 0');
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
			$cacheControlHeader = 'no-cache, must-revalidate';
			$pragmaHeader = 'no-cache';
			// Prevent error message in IE when using a https connection
			// see http://forge.typo3.org/issues/24125
			$clientInfo = \TYPO3\CMS\Core\Utility\GeneralUtility::clientInfo();
			if ($clientInfo['BROWSER'] === 'msie' && \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SSL')) {
				// Some IEs can not handle no-cache
				// see http://support.microsoft.com/kb/323308/en-us
				$cacheControlHeader = 'must-revalidate';
				// IE needs "Pragma: private" if SSL connection
				$pragmaHeader = 'private';
			}
			header('Cache-Control: ' . $cacheControlHeader);
			header('Pragma: ' . $pragmaHeader);
		}
		// Check to see if anyone has submitted login-information and if so register
		// the user with the session. $this->user[uid] may be used to write log...
		$this->checkAuthentication();
		// Make certain that NO user is set initially. ->check_authentication may
		// have set a session-record which will provide us with a user record in the next section:
		unset($this->user);
		// Determine whether we need to skip session update.
		// This is used mainly for checking session timeout without
		// refreshing the session itself while checking.
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('skipSessionUpdate')) {
			$skipSessionUpdate = TRUE;
		} else {
			$skipSessionUpdate = FALSE;
		}
		// Re-read user session
		$this->user = $this->fetchUserSession($skipSessionUpdate);
		if ($this->writeDevLog && is_array($this->user)) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('User session finally read: ' . \TYPO3\CMS\Core\Utility\GeneralUtility::arrayToLogString($this->user, array($this->userid_column, $this->username_column)), 'TYPO3\\CMS\\Core\\Authentication\\AbstractUserAuthentication', -1);
		}
		if ($this->writeDevLog && !is_array($this->user)) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('No user session found.', 'TYPO3\\CMS\\Core\\Authentication\\AbstractUserAuthentication', 2);
		}
		// Setting cookies
		if (!$this->dontSetCookie) {
			$this->setSessionCookie();
		}
		// Hook for alternative ways of filling the $this->user array (is used by the "timtaw" extension)
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['postUserLookUp'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['postUserLookUp'] as $funcName) {
				$_params = array(
					'pObj' => &$this
				);
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcName, $_params, $this);
			}
		}
		// Set $this->gc_time if not explicitely specified
		if ($this->gc_time == 0) {
			// Default to 1 day if $this->auth_timeout_field is 0
			$this->gc_time = $this->auth_timeout_field == 0 ? 86400 : $this->auth_timeout_field;
		}
		// If we're lucky we'll get to clean up old sessions....
		if (rand() % 100 <= $this->gc_probability) {
			$this->gc();
		}
	}

	/**
	 * Sets the session cookie for the current disposal.
	 *
	 * @return void
	 */
	protected function setSessionCookie() {
		$isSetSessionCookie = $this->isSetSessionCookie();
		$isRefreshTimeBasedCookie = $this->isRefreshTimeBasedCookie();
		if ($isSetSessionCookie || $isRefreshTimeBasedCookie) {
			$settings = $GLOBALS['TYPO3_CONF_VARS']['SYS'];
			// Get the domain to be used for the cookie (if any):
			$cookieDomain = $this->getCookieDomain();
			// If no cookie domain is set, use the base path:
			$cookiePath = $cookieDomain ? '/' : \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_PATH');
			// If the cookie lifetime is set, use it:
			$cookieExpire = $isRefreshTimeBasedCookie ? $GLOBALS['EXEC_TIME'] + $this->lifetime : 0;
			// Use the secure option when the current request is served by a secure connection:
			$cookieSecure = (bool) $settings['cookieSecure'] && \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SSL');
			// Deliver cookies only via HTTP and prevent possible XSS by JavaScript:
			$cookieHttpOnly = (bool) $settings['cookieHttpOnly'];
			// Do not set cookie if cookieSecure is set to "1" (force HTTPS) and no secure channel is used:
			if ((int) $settings['cookieSecure'] !== 1 || \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SSL')) {
				setcookie($this->name, $this->id, $cookieExpire, $cookiePath, $cookieDomain, $cookieSecure, $cookieHttpOnly);
			} else {
				throw new \TYPO3\CMS\Core\Exception('Cookie was not set since HTTPS was forced in $TYPO3_CONF_VARS[SYS][cookieSecure].', 1254325546);
			}
			if ($this->writeDevLog) {
				$devLogMessage = ($isRefreshTimeBasedCookie ? 'Updated Cookie: ' : 'Set Cookie: ') . $this->id;
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog($devLogMessage . ($cookieDomain ? ', ' . $cookieDomain : ''), 'TYPO3\\CMS\\Core\\Authentication\\AbstractUserAuthentication');
			}
		}
	}

	/**
	 * Gets the domain to be used on setting cookies.
	 * The information is taken from the value in $GLOBALS['TYPO3_CONF_VARS']['SYS']['cookieDomain'].
	 *
	 * @return string The domain to be used on setting cookies
	 */
	protected function getCookieDomain() {
		$result = '';
		$cookieDomain = $GLOBALS['TYPO3_CONF_VARS']['SYS']['cookieDomain'];
		// If a specific cookie domain is defined for a given TYPO3_MODE,
		// use that domain
		if (!empty($GLOBALS['TYPO3_CONF_VARS'][$this->loginType]['cookieDomain'])) {
			$cookieDomain = $GLOBALS['TYPO3_CONF_VARS'][$this->loginType]['cookieDomain'];
		}
		if ($cookieDomain) {
			if ($cookieDomain[0] == '/') {
				$match = array();
				$matchCnt = @preg_match($cookieDomain, \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY'), $match);
				if ($matchCnt === FALSE) {
					\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog('The regular expression for the cookie domain (' . $cookieDomain . ') contains errors. The session is not shared across sub-domains.', 'Core', \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
				} elseif ($matchCnt) {
					$result = $match[0];
				}
			} else {
				$result = $cookieDomain;
			}
		}
		return $result;
	}

	/**
	 * Get the value of a specified cookie.
	 *
	 * Uses HTTP_COOKIE, if available, to avoid a IE8 bug where multiple
	 * cookies with the same name might be returned if the user accessed
	 * the site without "www." first and switched to "www." later:
	 * Cookie: fe_typo_user=AAA; fe_typo_user=BBB
	 * In this case PHP will set _COOKIE as the first cookie, when we
	 * would need the last one (which is what this function then returns).
	 *
	 * @param string $cookieName The cookie ID
	 * @return string The value stored in the cookie
	 */
	protected function getCookie($cookieName) {
		if (isset($_SERVER['HTTP_COOKIE'])) {
			$cookies = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(';', $_SERVER['HTTP_COOKIE']);
			foreach ($cookies as $cookie) {
				list($name, $value) = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('=', $cookie);
				if (strcmp(trim($name), $cookieName) == 0) {
					// Use the last one
					$cookieValue = urldecode($value);
				}
			}
		} else {
			// Fallback if there is no HTTP_COOKIE, use original method:
			$cookieValue = isset($_COOKIE[$cookieName]) ? stripslashes($_COOKIE[$cookieName]) : '';
		}
		return $cookieValue;
	}

	/**
	 * Determine whether a session cookie needs to be set (lifetime=0)
	 *
	 * @return boolean
	 * @internal
	 * @todo Define visibility
	 */
	public function isSetSessionCookie() {
		return ($this->newSessionID || $this->forceSetCookie) && $this->lifetime == 0;
	}

	/**
	 * Determine whether a non-session cookie needs to be set (lifetime>0)
	 *
	 * @return boolean
	 * @internal
	 * @todo Define visibility
	 */
	public function isRefreshTimeBasedCookie() {
		return $this->lifetime > 0;
	}

	/**
	 * Checks if a submission of username and password is present or use other authentication by auth services
	 *
	 * @return void
	 * @internal
	 * @todo Define visibility
	 */
	public function checkAuthentication() {
		// No user for now - will be searched by service below
		$tempuserArr = array();
		$tempuser = FALSE;
		// User is not authenticated by default
		$authenticated = FALSE;
		// User want to login with passed login data (name/password)
		$activeLogin = FALSE;
		// Indicates if an active authentication failed (not auto login)
		$this->loginFailure = FALSE;
		if ($this->writeDevLog) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('Login type: ' . $this->loginType, 'TYPO3\\CMS\\Core\\Authentication\\AbstractUserAuthentication');
		}
		// The info array provide additional information for auth services
		$authInfo = $this->getAuthInfoArray();
		// Get Login/Logout data submitted by a form or params
		$loginData = $this->getLoginFormData();
		if ($this->writeDevLog) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('Login data: ' . \TYPO3\CMS\Core\Utility\GeneralUtility::arrayToLogString($loginData), 'TYPO3\\CMS\\Core\\Authentication\\AbstractUserAuthentication');
		}
		// Active logout (eg. with "logout" button)
		if ($loginData['status'] == 'logout') {
			if ($this->writeStdLog) {
				// $type,$action,$error,$details_nr,$details,$data,$tablename,$recuid,$recpid
				$this->writelog(255, 2, 0, 2, 'User %s logged out', array($this->user['username']), '', 0, 0);
			}
			// Logout written to log
			if ($this->writeDevLog) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('User logged out. Id: ' . $this->id, 'TYPO3\\CMS\\Core\\Authentication\\AbstractUserAuthentication', -1);
			}
			$this->logoff();
		}
		// Active login (eg. with login form)
		if ($loginData['status'] == 'login') {
			$activeLogin = TRUE;
			if ($this->writeDevLog) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('Active login (eg. with login form)', 'TYPO3\\CMS\\Core\\Authentication\\AbstractUserAuthentication');
			}
			// check referer for submitted login values
			if ($this->formfield_status && $loginData['uident'] && $loginData['uname']) {
				$httpHost = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY');
				if (!$this->getMethodEnabled && ($httpHost != $authInfo['refInfo']['host'] && !$GLOBALS['TYPO3_CONF_VARS']['SYS']['doNotCheckReferer'])) {
					throw new \RuntimeException('TYPO3 Fatal Error: Error: This host address ("' . $httpHost . '") and the referer host ("' . $authInfo['refInfo']['host'] . '") mismatches!<br />
						It\'s possible that the environment variable HTTP_REFERER is not passed to the script because of a proxy.<br />
						The site administrator can disable this check in the "All Configuration" section of the Install Tool (flag: TYPO3_CONF_VARS[SYS][doNotCheckReferer]).', 1270853930);
				}
				// Delete old user session if any
				$this->logoff();
			}
			// Refuse login for _CLI users, if not processing a CLI request type
			// (although we shouldn't be here in case of a CLI request type)
			if (strtoupper(substr($loginData['uname'], 0, 5)) == '_CLI_' && !(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI)) {
				throw new \RuntimeException('TYPO3 Fatal Error: You have tried to login using a CLI user. Access prohibited!', 1270853931);
			}
		}
		// The following code makes auto-login possible (if configured). No submitted data needed
		// Determine whether we need to skip session update.
		// This is used mainly for checking session timeout without
		// refreshing the session itself while checking.
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('skipSessionUpdate')) {
			$skipSessionUpdate = TRUE;
		} else {
			$skipSessionUpdate = FALSE;
		}
		// Re-read user session
		$authInfo['userSession'] = $this->fetchUserSession($skipSessionUpdate);
		$haveSession = is_array($authInfo['userSession']) ? TRUE : FALSE;
		if ($this->writeDevLog) {
			if ($haveSession) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('User session found: ' . \TYPO3\CMS\Core\Utility\GeneralUtility::arrayToLogString($authInfo['userSession'], array($this->userid_column, $this->username_column)), 'TYPO3\\CMS\\Core\\Authentication\\AbstractUserAuthentication', 0);
			}
			if (is_array($this->svConfig['setup'])) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('SV setup: ' . \TYPO3\CMS\Core\Utility\GeneralUtility::arrayToLogString($this->svConfig['setup']), 'TYPO3\\CMS\\Core\\Authentication\\AbstractUserAuthentication', 0);
			}
		}
		// Fetch user if ...
		if ($activeLogin || !$haveSession && $this->svConfig['setup'][$this->loginType . '_fetchUserIfNoSession'] || $this->svConfig['setup'][$this->loginType . '_alwaysFetchUser']) {
			// Use 'auth' service to find the user
			// First found user will be used
			$serviceChain = '';
			$subType = 'getUser' . $this->loginType;
			while (is_object($serviceObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstanceService('auth', $subType, $serviceChain))) {
				$serviceChain .= ',' . $serviceObj->getServiceKey();
				$serviceObj->initAuth($subType, $loginData, $authInfo, $this);
				if ($row = $serviceObj->getUser()) {
					$tempuserArr[] = $row;
					if ($this->writeDevLog) {
						\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('User found: ' . \TYPO3\CMS\Core\Utility\GeneralUtility::arrayToLogString($row, array($this->userid_column, $this->username_column)), 'TYPO3\\CMS\\Core\\Authentication\\AbstractUserAuthentication', 0);
					}
					// User found, just stop to search for more if not configured to go on
					if (!$this->svConfig['setup'][($this->loginType . '_fetchAllUsers')]) {
						break;
					}
				}
				unset($serviceObj);
			}
			unset($serviceObj);
			if ($this->writeDevLog && $this->svConfig['setup'][$this->loginType . '_alwaysFetchUser']) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog($this->loginType . '_alwaysFetchUser option is enabled', 'TYPO3\\CMS\\Core\\Authentication\\AbstractUserAuthentication');
			}
			if ($this->writeDevLog && $serviceChain) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog($subType . ' auth services called: ' . $serviceChain, 'TYPO3\\CMS\\Core\\Authentication\\AbstractUserAuthentication');
			}
			if ($this->writeDevLog && !count($tempuserArr)) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('No user found by services', 'TYPO3\\CMS\\Core\\Authentication\\AbstractUserAuthentication');
			}
			if ($this->writeDevLog && count($tempuserArr)) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(count($tempuserArr) . ' user records found by services', 'TYPO3\\CMS\\Core\\Authentication\\AbstractUserAuthentication');
			}
		}
		// If no new user was set we use the already found user session
		if (!count($tempuserArr) && $haveSession) {
			$tempuserArr[] = $authInfo['userSession'];
			$tempuser = $authInfo['userSession'];
			// User is authenticated because we found a user session
			$authenticated = TRUE;
			if ($this->writeDevLog) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('User session used: ' . \TYPO3\CMS\Core\Utility\GeneralUtility::arrayToLogString($authInfo['userSession'], array($this->userid_column, $this->username_column)), 'TYPO3\\CMS\\Core\\Authentication\\AbstractUserAuthentication');
			}
		}
		// Re-auth user when 'auth'-service option is set
		if ($this->svConfig['setup'][$this->loginType . '_alwaysAuthUser']) {
			$authenticated = FALSE;
			if ($this->writeDevLog) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('alwaysAuthUser option is enabled', 'TYPO3\\CMS\\Core\\Authentication\\AbstractUserAuthentication');
			}
		}
		// Authenticate the user if needed
		if (count($tempuserArr) && !$authenticated) {
			foreach ($tempuserArr as $tempuser) {
				// Use 'auth' service to authenticate the user
				// If one service returns FALSE then authentication failed
				// a service might return 100 which means there's no reason to stop but the user can't be authenticated by that service
				if ($this->writeDevLog) {
					\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('Auth user: ' . \TYPO3\CMS\Core\Utility\GeneralUtility::arrayToLogString($tempuser), 'TYPO3\\CMS\\Core\\Authentication\\AbstractUserAuthentication');
				}
				$serviceChain = '';
				$subType = 'authUser' . $this->loginType;
				while (is_object($serviceObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstanceService('auth', $subType, $serviceChain))) {
					$serviceChain .= ',' . $serviceObj->getServiceKey();
					$serviceObj->initAuth($subType, $loginData, $authInfo, $this);
					if (($ret = $serviceObj->authUser($tempuser)) > 0) {
						// If the service returns >=200 then no more checking is needed - useful for IP checking without password
						if (intval($ret) >= 200) {
							$authenticated = TRUE;
							break;
						} elseif (intval($ret) >= 100) {

						} else {
							$authenticated = TRUE;
						}
					} else {
						$authenticated = FALSE;
						break;
					}
					unset($serviceObj);
				}
				unset($serviceObj);
				if ($this->writeDevLog && $serviceChain) {
					\TYPO3\CMS\Core\Utility\GeneralUtility::devLog($subType . ' auth services called: ' . $serviceChain, 'TYPO3\\CMS\\Core\\Authentication\\AbstractUserAuthentication');
				}
				if ($authenticated) {
					// Leave foreach() because a user is authenticated
					break;
				}
			}
		}
		// If user is authenticated a valid user is in $tempuser
		if ($authenticated) {
			// Reset failure flag
			$this->loginFailure = FALSE;
			// Insert session record if needed:
			if (!($haveSession && ($tempuser['ses_id'] == $this->id || $tempuser['uid'] == $authInfo['userSession']['ses_userid']))) {
				$this->createUserSession($tempuser);
				// The login session is started.
				$this->loginSessionStarted = TRUE;
			}
			// User logged in - write that to the log!
			if ($this->writeStdLog && $activeLogin) {
				$this->writelog(255, 1, 0, 1, 'User %s logged in from %s (%s)', array($tempuser[$this->username_column], \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR'), \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_HOST')), '', '', '', -1, '', $tempuser['uid']);
			}
			if ($this->writeDevLog && $activeLogin) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('User ' . $tempuser[$this->username_column] . ' logged in from ' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR') . ' (' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_HOST') . ')', 'TYPO3\\CMS\\Core\\Authentication\\AbstractUserAuthentication', -1);
			}
			if ($this->writeDevLog && !$activeLogin) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('User ' . $tempuser[$this->username_column] . ' authenticated from ' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR') . ' (' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_HOST') . ')', 'TYPO3\\CMS\\Core\\Authentication\\AbstractUserAuthentication', -1);
			}
			if ($GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSL'] == 3 && $this->user_table == 'be_users') {
				$requestStr = substr(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_SCRIPT'), strlen(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . TYPO3_mainDir));
				$backendScript = \TYPO3\CMS\Backend\Utility\BackendUtility::getBackendScript();
				if ($requestStr == $backendScript && \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SSL')) {
					list(, $url) = explode('://', \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL'), 2);
					list($server, $address) = explode('/', $url, 2);
					if (intval($GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSLPort'])) {
						$sslPortSuffix = ':' . intval($GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSLPort']);
						// strip port from server
						$server = str_replace($sslPortSuffix, '', $server);
					}
					\TYPO3\CMS\Core\Utility\HttpUtility::redirect('http://' . $server . '/' . $address . TYPO3_mainDir . $backendScript);
				}
			}
		} elseif ($activeLogin || count($tempuserArr)) {
			$this->loginFailure = TRUE;
			if ($this->writeDevLog && !count($tempuserArr) && $activeLogin) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('Login failed: ' . \TYPO3\CMS\Core\Utility\GeneralUtility::arrayToLogString($loginData), 'TYPO3\\CMS\\Core\\Authentication\\AbstractUserAuthentication', 2);
			}
			if ($this->writeDevLog && count($tempuserArr)) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('Login failed: ' . \TYPO3\CMS\Core\Utility\GeneralUtility::arrayToLogString($tempuser, array($this->userid_column, $this->username_column)), 'TYPO3\\CMS\\Core\\Authentication\\AbstractUserAuthentication', 2);
			}
		}
		// If there were a login failure, check to see if a warning email should be sent:
		if ($this->loginFailure && $activeLogin) {
			if ($this->writeDevLog) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('Call checkLogFailures: ' . \TYPO3\CMS\Core\Utility\GeneralUtility::arrayToLogString(array('warningEmail' => $this->warningEmail, 'warningPeriod' => $this->warningPeriod, 'warningMax' => $this->warningMax)), 'TYPO3\\CMS\\Core\\Authentication\\AbstractUserAuthentication', -1);
			}
			$this->checkLogFailures($this->warningEmail, $this->warningPeriod, $this->warningMax);
		}
	}

	/**
	 * Creates a new session ID.
	 *
	 * @return string The new session ID
	 */
	public function createSessionId() {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::getRandomHexString($this->hash_length);
	}

	/*************************
	 *
	 * User Sessions
	 *
	 *************************/
	/**
	 * Creates a user session record.
	 *
	 * @param array $tempuser User data array
	 * @return void
	 * @todo Define visibility
	 */
	public function createUserSession($tempuser) {
		if ($this->writeDevLog) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('Create session ses_id = ' . $this->id, 'TYPO3\\CMS\\Core\\Authentication\\AbstractUserAuthentication');
		}
		// Delete session entry first
		$GLOBALS['TYPO3_DB']->exec_DELETEquery($this->session_table, 'ses_id = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($this->id, $this->session_table) . '
						AND ses_name = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($this->name, $this->session_table));
		// Re-create session entry
		$insertFields = $this->getNewSessionRecord($tempuser);
		$GLOBALS['TYPO3_DB']->exec_INSERTquery($this->session_table, $insertFields);
		// Updating lastLogin_column carrying information about last login.
		if ($this->lastLogin_column) {
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery($this->user_table, $this->userid_column . '=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($tempuser[$this->userid_column], $this->user_table), array($this->lastLogin_column => $GLOBALS['EXEC_TIME']));
		}
	}

	/**
	 * Returns a new session record for the current user for insertion into the DB.
	 * This function is mainly there as a wrapper for inheriting classes to override it.
	 *
	 * @param array $tempuser
	 * @return array User session record
	 * @todo Define visibility
	 */
	public function getNewSessionRecord($tempuser) {
		return array(
			'ses_id' => $this->id,
			'ses_name' => $this->name,
			'ses_iplock' => $tempuser['disableIPlock'] ? '[DISABLED]' : $this->ipLockClause_remoteIPNumber($this->lockIP),
			'ses_hashlock' => $this->hashLockClause_getHashInt(),
			'ses_userid' => $tempuser[$this->userid_column],
			'ses_tstamp' => $GLOBALS['EXEC_TIME']
		);
	}

	/**
	 * Read the user session from db.
	 *
	 * @param boolean $skipSessionUpdate
	 * @return array User session data
	 * @todo Define visibility
	 */
	public function fetchUserSession($skipSessionUpdate = FALSE) {
		$user = '';
		if ($this->writeDevLog) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('Fetch session ses_id = ' . $this->id, 'TYPO3\\CMS\\Core\\Authentication\\AbstractUserAuthentication');
		}
		// Fetch the user session from the DB
		$statement = $this->fetchUserSessionFromDB();
		$user = FALSE;
		if ($statement) {
			$statement->execute();
			$user = $statement->fetch();
			$statement->free();
		}
		if ($statement && $user) {
			// A user was found
			if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($this->auth_timeout_field)) {
				// Get timeout from object
				$timeout = intval($this->auth_timeout_field);
			} else {
				// Get timeout-time from usertable
				$timeout = intval($user[$this->auth_timeout_field]);
			}
			// If timeout > 0 (TRUE) and currenttime has not exceeded the latest sessions-time plus the timeout in seconds then accept user
			// Option later on: We could check that last update was at least x seconds ago in order not to update twice in a row if one script redirects to another...
			if ($timeout > 0 && $GLOBALS['EXEC_TIME'] < $user['ses_tstamp'] + $timeout) {
				if (!$skipSessionUpdate) {
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery($this->session_table, 'ses_id=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($this->id, $this->session_table) . '
												AND ses_name=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($this->name, $this->session_table), array('ses_tstamp' => $GLOBALS['EXEC_TIME']));
					// Make sure that the timestamp is also updated in the array
					$user['ses_tstamp'] = $GLOBALS['EXEC_TIME'];
				}
			} else {
				// Delete any user set...
				$this->logoff();
			}
		} else {
			// Delete any user set...
			$this->logoff();
		}
		return $user;
	}

	/**
	 * Log out current user!
	 * Removes the current session record, sets the internal ->user array to a blank string; Thereby the current user (if any) is effectively logged out!
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function logoff() {
		if ($this->writeDevLog) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('logoff: ses_id = ' . $this->id, 'TYPO3\\CMS\\Core\\Authentication\\AbstractUserAuthentication');
		}
		// Release the locked records
		\TYPO3\CMS\Backend\Utility\BackendUtility::lockRecords();
		// Hook for pre-processing the logoff() method, requested and implemented by andreas.otto@dkd.de:
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_pre_processing'])) {
			$_params = array();
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_pre_processing'] as $_funcRef) {
				if ($_funcRef) {
					\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $_params, $this);
				}
			}
		}
		$GLOBALS['TYPO3_DB']->exec_DELETEquery($this->session_table, 'ses_id = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($this->id, $this->session_table) . '
						AND ses_name = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($this->name, $this->session_table));
		$this->user = '';
		// Hook for post-processing the logoff() method, requested and implemented by andreas.otto@dkd.de:
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_post_processing'])) {
			$_params = array();
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_post_processing'] as $_funcRef) {
				if ($_funcRef) {
					\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $_params, $this);
				}
			}
		}
	}

	/**
	 * Determine whether there's an according session record to a given session_id
	 * in the database. Don't care if session record is still valid or not.
	 *
	 * @param integer $id Claimed Session ID
	 * @return boolean Returns TRUE if a corresponding session was found in the database
	 * @todo Define visibility
	 */
	public function isExistingSessionRecord($id) {
		$statement = $GLOBALS['TYPO3_DB']->prepare_SELECTquery('COUNT(*)', $this->session_table, 'ses_id = :ses_id');
		$statement->execute(array(':ses_id' => $id));
		$row = $statement->fetch(\TYPO3\CMS\Core\Database\PreparedStatement::FETCH_NUM);
		$statement->free();
		return $row[0] ? TRUE : FALSE;
	}

	/*************************
	 *
	 * SQL Functions
	 *
	 *************************/
	/**
	 * The session_id is used to find user in the database.
	 * Two tables are joined: The session-table with user_id of the session and the usertable with its primary key
	 * if the client is flash (e.g. from a flash application inside TYPO3 that does a server request)
	 * then don't evaluate with the hashLockClause, as the client/browser is included in this hash
	 * and thus, the flash request would be rejected
	 *
	 * @return \TYPO3\CMS\Core\Database\PreparedStatement
	 * @access private
	 */
	protected function fetchUserSessionFromDB() {
		$statement = NULL;
		$ipLockClause = $this->ipLockClause();
		if ($GLOBALS['CLIENT']['BROWSER'] == 'flash') {
			// If on the flash client, the veri code is valid, then the user session is fetched
			// from the DB without the hashLock clause
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('vC') == $this->veriCode()) {
				$statement = $GLOBALS['TYPO3_DB']->prepare_SELECTquery('*', $this->session_table . ',' . $this->user_table, $this->session_table . '.ses_id = :ses_id
						AND ' . $this->session_table . '.ses_name = :ses_name
						AND ' . $this->session_table . '.ses_userid = ' . $this->user_table . '.' . $this->userid_column . '
						' . $ipLockClause['where'] . '
						' . $this->user_where_clause());
				$statement->bindValues(array(
					':ses_id' => $this->id,
					':ses_name' => $this->name
				));
				$statement->bindValues($ipLockClause['parameters']);
			}
		} else {
			$statement = $GLOBALS['TYPO3_DB']->prepare_SELECTquery('*', $this->session_table . ',' . $this->user_table, $this->session_table . '.ses_id = :ses_id
					AND ' . $this->session_table . '.ses_name = :ses_name
					AND ' . $this->session_table . '.ses_userid = ' . $this->user_table . '.' . $this->userid_column . '
					' . $ipLockClause['where'] . '
					' . $this->hashLockClause() . '
					' . $this->user_where_clause());
			$statement->bindValues(array(
				':ses_id' => $this->id,
				':ses_name' => $this->name
			));
			$statement->bindValues($ipLockClause['parameters']);
		}
		return $statement;
	}

	/**
	 * This returns the where-clause needed to select the user with respect flags like deleted, hidden, starttime, endtime
	 *
	 * @return string
	 * @access private
	 */
	protected function user_where_clause() {
		return ($this->enablecolumns['rootLevel'] ? 'AND ' . $this->user_table . '.pid=0 ' : '') . ($this->enablecolumns['disabled'] ? ' AND ' . $this->user_table . '.' . $this->enablecolumns['disabled'] . '=0' : '') . ($this->enablecolumns['deleted'] ? ' AND ' . $this->user_table . '.' . $this->enablecolumns['deleted'] . '=0' : '') . ($this->enablecolumns['starttime'] ? ' AND (' . $this->user_table . '.' . $this->enablecolumns['starttime'] . '<=' . $GLOBALS['EXEC_TIME'] . ')' : '') . ($this->enablecolumns['endtime'] ? ' AND (' . $this->user_table . '.' . $this->enablecolumns['endtime'] . '=0 OR ' . $this->user_table . '.' . $this->enablecolumns['endtime'] . '>' . $GLOBALS['EXEC_TIME'] . ')' : '');
	}

	/**
	 * This returns the where prepared statement-clause needed to lock a user to the IP address
	 *
	 * @return array
	 * @access private
	 */
	protected function ipLockClause() {
		$statementClause = array(
			'where' => '',
			'parameters' => array()
		);
		if ($this->lockIP) {
			$statementClause['where'] = 'AND (
				' . $this->session_table . '.ses_iplock = :ses_iplock
				OR ' . $this->session_table . '.ses_iplock=\'[DISABLED]\'
				)';
			$statementClause['parameters'] = array(
				':ses_iplock' => $this->ipLockClause_remoteIPNumber($this->lockIP)
			);
		}
		return $statementClause;
	}

	/**
	 * Returns the IP address to lock to.
	 * The IP address may be partial based on $parts.
	 *
	 * @param integer $parts 1-4: Indicates how many parts of the IP address to return. 4 means all, 1 means only first number.
	 * @return string (Partial) IP address for REMOTE_ADDR
	 * @access private
	 */
	protected function ipLockClause_remoteIPNumber($parts) {
		$IP = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR');
		if ($parts >= 4) {
			return $IP;
		} else {
			$parts = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($parts, 1, 3);
			$IPparts = explode('.', $IP);
			for ($a = 4; $a > $parts; $a--) {
				unset($IPparts[$a - 1]);
			}
			return implode('.', $IPparts);
		}
	}

	/**
	 * VeriCode returns 10 first chars of a md5 hash of the session cookie AND the encryptionKey from TYPO3_CONF_VARS.
	 * This code is used as an alternative verification when the JavaScript interface executes cmd's to tce_db.php from eg. MSIE 5.0 because the proper referer is not passed with this browser...
	 *
	 * @return string
	 */
	public function veriCode() {
		return substr(md5($this->id . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']), 0, 10);
	}

	/**
	 * This returns the where-clause needed to lock a user to a hash integer
	 *
	 * @return string
	 * @access private
	 */
	protected function hashLockClause() {
		$wherePart = 'AND ' . $this->session_table . '.ses_hashlock=' . intval($this->hashLockClause_getHashInt());
		return $wherePart;
	}

	/**
	 * Creates hash integer to lock user to. Depends on configured keywords
	 *
	 * @return integer Hash integer
	 * @access private
	 */
	protected function hashLockClause_getHashInt() {
		$hashStr = '';
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList($this->lockHashKeyWords, 'useragent')) {
			$hashStr .= ':' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_USER_AGENT');
		}
		return \TYPO3\CMS\Core\Utility\GeneralUtility::md5int($hashStr);
	}

	/*************************
	 *
	 * Session and Configuration Handling
	 *
	 *************************/
	/**
	 * This writes $variable to the user-record. This is a way of providing session-data.
	 * You can fetch the data again through $this->uc in this class!
	 * If $variable is not an array, $this->uc is saved!
	 *
	 * @param array $variable An array you want to store for the user as session data. If $variable is not supplied (is blank string), the internal variable, ->uc, is stored by default
	 * @return void
	 * @todo Define visibility
	 */
	public function writeUC($variable = '') {
		if (is_array($this->user) && $this->user[$this->userid_column]) {
			if (!is_array($variable)) {
				$variable = $this->uc;
			}
			if ($this->writeDevLog) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('writeUC: ' . $this->userid_column . '=' . intval($this->user[$this->userid_column]), 'TYPO3\\CMS\\Core\\Authentication\\AbstractUserAuthentication');
			}
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery($this->user_table, $this->userid_column . '=' . intval($this->user[$this->userid_column]), array('uc' => serialize($variable)));
		}
	}

	/**
	 * Sets $theUC as the internal variable ->uc IF $theUC is an array. If $theUC is FALSE, the 'uc' content from the ->user array will be unserialized and restored in ->uc
	 *
	 * @param mixed $theUC If an array, then set as ->uc, otherwise load from user record
	 * @return void
	 * @todo Define visibility
	 */
	public function unpack_uc($theUC = '') {
		if (!$theUC) {
			$theUC = unserialize($this->user['uc']);
		}
		if (is_array($theUC)) {
			$this->uc = $theUC;
		}
	}

	/**
	 * Stores data for a module.
	 * The data is stored with the session id so you can even check upon retrieval if the module data is from a previous session or from the current session.
	 *
	 * @param string $module Is the name of the module ($MCONF['name'])
	 * @param mixed $data Is the data you want to store for that module (array, string, ...)
	 * @param boolean $noSave If $noSave is set, then the ->uc array (which carries all kinds of user data) is NOT written immediately, but must be written by some subsequent call.
	 * @return void
	 * @todo Define visibility
	 */
	public function pushModuleData($module, $data, $noSave = 0) {
		$this->uc['moduleData'][$module] = $data;
		$this->uc['moduleSessionID'][$module] = $this->id;
		if (!$noSave) {
			$this->writeUC();
		}
	}

	/**
	 * Gets module data for a module (from a loaded ->uc array)
	 *
	 * @param string $module Is the name of the module ($MCONF['name'])
	 * @param string $type If $type = 'ses' then module data is returned only if it was stored in the current session, otherwise data from a previous session will be returned (if available).
	 * @return mixed The module data if available: $this->uc['moduleData'][$module];
	 * @todo Define visibility
	 */
	public function getModuleData($module, $type = '') {
		if ($type != 'ses' || $this->uc['moduleSessionID'][$module] == $this->id) {
			return $this->uc['moduleData'][$module];
		}
	}

	/**
	 * Returns the session data stored for $key.
	 * The data will last only for this login session since it is stored in the session table.
	 *
	 * @param string $key Pointer to an associative key in the session data array which is stored serialized in the field "ses_data" of the session table.
	 * @return mixed
	 * @todo Define visibility
	 */
	public function getSessionData($key) {
		$sesDat = unserialize($this->user['ses_data']);
		return $sesDat[$key];
	}

	/**
	 * Sets the session data ($data) for $key and writes all session data (from ->user['ses_data']) to the database.
	 * The data will last only for this login session since it is stored in the session table.
	 *
	 * @param string $key Pointer to an associative key in the session data array which is stored serialized in the field "ses_data" of the session table.
	 * @param mixed $data The variable to store in index $key
	 * @return void
	 * @todo Define visibility
	 */
	public function setAndSaveSessionData($key, $data) {
		$sesDat = unserialize($this->user['ses_data']);
		$sesDat[$key] = $data;
		$this->user['ses_data'] = serialize($sesDat);
		if ($this->writeDevLog) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('setAndSaveSessionData: ses_id = ' . $this->user['ses_id'], 'TYPO3\\CMS\\Core\\Authentication\\AbstractUserAuthentication');
		}
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery($this->session_table, 'ses_id=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($this->user['ses_id'], $this->session_table), array('ses_data' => $this->user['ses_data']));
	}

	/*************************
	 *
	 * Misc
	 *
	 *************************/
	/**
	 * Returns an info array with Login/Logout data submitted by a form or params
	 *
	 * @return array
	 * @internal
	 * @todo Define visibility
	 */
	public function getLoginFormData() {
		$loginData = array();
		if ($this->getMethodEnabled) {
			$loginData['status'] = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP($this->formfield_status);
			$loginData['uname'] = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP($this->formfield_uname);
			$loginData['uident'] = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP($this->formfield_uident);
			$loginData['chalvalue'] = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP($this->formfield_chalvalue);
		} else {
			$loginData['status'] = \TYPO3\CMS\Core\Utility\GeneralUtility::_POST($this->formfield_status);
			$loginData['uname'] = \TYPO3\CMS\Core\Utility\GeneralUtility::_POST($this->formfield_uname);
			$loginData['uident'] = \TYPO3\CMS\Core\Utility\GeneralUtility::_POST($this->formfield_uident);
			$loginData['chalvalue'] = \TYPO3\CMS\Core\Utility\GeneralUtility::_POST($this->formfield_chalvalue);
		}
		// Only process the login data if a login is requested
		if ($loginData['status'] === 'login') {
			$loginData = $this->processLoginData($loginData);
		}
		return $loginData;
	}

	/**
	 * Processes Login data submitted by a form or params depending on the
	 * passwordTransmissionStrategy
	 *
	 * @param array $loginData Login data array
	 * @param string $passwordTransmissionStrategy Alternative passwordTransmissionStrategy. Used when authentication services wants to override the default.
	 * @return array
	 * @internal
	 * @todo Define visibility
	 */
	public function processLoginData($loginData, $passwordTransmissionStrategy = '') {
		$passwordTransmissionStrategy = $passwordTransmissionStrategy ? $passwordTransmissionStrategy : ($GLOBALS['TYPO3_CONF_VARS'][$this->loginType]['loginSecurityLevel'] ? trim($GLOBALS['TYPO3_CONF_VARS'][$this->loginType]['loginSecurityLevel']) : $this->security_level);
		if ($this->writeDevLog) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('Login data before processing: ' . \TYPO3\CMS\Core\Utility\GeneralUtility::arrayToLogString($loginData), 'TYPO3\\CMS\\Core\\Authentication\\AbstractUserAuthentication');
		}
		$serviceChain = '';
		$subType = 'processLoginData' . $this->loginType;
		$authInfo = $this->getAuthInfoArray();
		$isLoginDataProcessed = FALSE;
		$processedLoginData = $loginData;
		while (is_object($serviceObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstanceService('auth', $subType, $serviceChain))) {
			$serviceChain .= ',' . $serviceObject->getServiceKey();
			$serviceObject->initAuth($subType, $loginData, $authInfo, $this);
			$serviceResult = $serviceObject->processLoginData($processedLoginData, $passwordTransmissionStrategy);
			if (!empty($serviceResult)) {
				$isLoginDataProcessed = TRUE;
				// If the service returns >=200 then no more processing is needed
				if (intval($serviceResult) >= 200) {
					unset($serviceObject);
					break;
				}
			}
			unset($serviceObject);
		}
		if ($isLoginDataProcessed) {
			$loginData = $processedLoginData;
			if ($this->writeDevLog) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('Processed login data: ' . \TYPO3\CMS\Core\Utility\GeneralUtility::arrayToLogString($processedLoginData), 'TYPO3\\CMS\\Core\\Authentication\\AbstractUserAuthentication');
			}
		}
		return $loginData;
	}

	/**
	 * Returns an info array which provides additional information for auth services
	 *
	 * @return array
	 * @internal
	 * @todo Define visibility
	 */
	public function getAuthInfoArray() {
		$authInfo = array();
		$authInfo['loginType'] = $this->loginType;
		$authInfo['refInfo'] = parse_url(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_REFERER'));
		$authInfo['HTTP_HOST'] = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_HOST');
		$authInfo['REMOTE_ADDR'] = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR');
		$authInfo['REMOTE_HOST'] = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_HOST');
		/** @deprecated the usage of $authInfo['security_level'] is deprecated since 4.7 */
		$authInfo['security_level'] = $this->security_level;
		$authInfo['showHiddenRecords'] = $this->showHiddenRecords;
		// Can be overidden in localconf by SVCONF:
		$authInfo['db_user']['table'] = $this->user_table;
		$authInfo['db_user']['userid_column'] = $this->userid_column;
		$authInfo['db_user']['username_column'] = $this->username_column;
		$authInfo['db_user']['userident_column'] = $this->userident_column;
		$authInfo['db_user']['usergroup_column'] = $this->usergroup_column;
		$authInfo['db_user']['enable_clause'] = $this->user_where_clause();
		$authInfo['db_user']['checkPidList'] = $this->checkPid ? $this->checkPid_value : '';
		$authInfo['db_user']['check_pid_clause'] = $this->checkPid ? ' AND pid IN (' . $GLOBALS['TYPO3_DB']->cleanIntList($authInfo['db_user']['checkPidList']) . ')' : '';
		$authInfo['db_groups']['table'] = $this->usergroup_table;
		return $authInfo;
	}

	/**
	 * Check the login data with the user record data for builtin login methods
	 *
	 * @param array $user User data array
	 * @param array $loginData Login data array
	 * @param string $passwordCompareStrategy Alternative passwordCompareStrategy. Used when authentication services wants to override the default.
	 * @return boolean TRUE if login data matched
	 * @todo Define visibility
	 */
	public function compareUident($user, $loginData, $passwordCompareStrategy = '') {
		$OK = FALSE;
		$passwordCompareStrategy = $passwordCompareStrategy ? $passwordCompareStrategy : $this->security_level;
		switch ($passwordCompareStrategy) {
		case 'superchallenged':

		case 'challenged':
			// Check challenge stored in cookie:
			if ($this->challengeStoredInCookie) {
				session_start();
				if ($_SESSION['login_challenge'] !== $loginData['chalvalue']) {
					if ($this->writeDevLog) {
						\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('PHP Session stored challenge "' . $_SESSION['login_challenge'] . '" and submitted challenge "' . $loginData['chalvalue'] . '" did not match, so authentication failed!', 'TYPO3\\CMS\\Core\\Authentication\\AbstractUserAuthentication', 2);
					}
					$this->logoff();
					return FALSE;
				}
			}
			if ((string) $loginData[('uident_' . $passwordCompareStrategy)] === (string) md5(($user[$this->username_column] . ':' . $user[$this->userident_column] . ':' . $loginData['chalvalue']))) {
				$OK = TRUE;
			}
			break;
		default:
			// normal
			if ((string) $loginData['uident_text'] === (string) $user[$this->userident_column]) {
				$OK = TRUE;
			}
			break;
		}
		return $OK;
	}

	/**
	 * Garbage collector, removing old expired sessions.
	 *
	 * @return void
	 * @internal
	 * @todo Define visibility
	 */
	public function gc() {
		$GLOBALS['TYPO3_DB']->exec_DELETEquery($this->session_table, 'ses_tstamp < ' . intval(($GLOBALS['EXEC_TIME'] - $this->gc_time)) . ' AND ses_name = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($this->name, $this->session_table));
	}

	/**
	 * DUMMY: Writes to log database table (in some extension classes)
	 *
	 * @param integer $type denotes which module that has submitted the entry. This is the current list:  1=tce_db; 2=tce_file; 3=system (eg. sys_history save); 4=modules; 254=Personal settings changed; 255=login / out action: 1=login, 2=logout, 3=failed login (+ errorcode 3), 4=failure_warning_email sent
	 * @param integer $action denotes which specific operation that wrote the entry (eg. 'delete', 'upload', 'update' and so on...). Specific for each $type. Also used to trigger update of the interface. (see the log-module for the meaning of each number !!)
	 * @param integer $error flag. 0 = message, 1 = error (user problem), 2 = System Error (which should not happen), 3 = security notice (admin)
	 * @param integer $details_nr The message number. Specific for each $type and $action. in the future this will make it possible to translate errormessages to other languages
	 * @param string $details Default text that follows the message
	 * @param array $data Data that follows the log. Might be used to carry special information. If an array the first 5 entries (0-4) will be sprintf'ed the details-text...
	 * @param string $tablename Special field used by tce_main.php. These ($tablename, $recuid, $recpid) holds the reference to the record which the log-entry is about. (Was used in attic status.php to update the interface.)
	 * @param integer $recuid Special field used by tce_main.php. These ($tablename, $recuid, $recpid) holds the reference to the record which the log-entry is about. (Was used in attic status.php to update the interface.)
	 * @param integer $recpid Special field used by tce_main.php. These ($tablename, $recuid, $recpid) holds the reference to the record which the log-entry is about. (Was used in attic status.php to update the interface.)
	 * @return void
	 * @todo Define visibility
	 */
	public function writelog($type, $action, $error, $details_nr, $details, $data, $tablename, $recuid, $recpid) {

	}

	/**
	 * DUMMY: Check login failures (in some extension classes)
	 *
	 * @param string $email Email address
	 * @param integer $secondsBack Number of sections back in time to check. This is a kind of limit for how many failures an hour for instance
	 * @param integer $maxFailures Max allowed failures before a warning mail is sent
	 * @return void
	 * @ignore
	 * @todo Define visibility
	 */
	public function checkLogFailures($email, $secondsBack, $maxFailures) {

	}

	/**
	 * Raw initialization of the be_user with uid=$uid
	 * This will circumvent all login procedures and select a be_users record from the
	 * database and set the content of ->user to the record selected.
	 * Thus the BE_USER object will appear like if a user was authenticated - however without
	 * a session id and the fields from the session table of course.
	 * Will check the users for disabled, start/endtime, etc. ($this->user_where_clause())
	 *
	 * @param integer $uid The UID of the backend user to set in ->user
	 * @return void
	 * @internal
	 * @see SC_mod_tools_be_user_index::compareUsers(), SC_mod_user_setup_index::simulateUser(), freesite_admin::startCreate()
	 * @todo Define visibility
	 */
	public function setBeUserByUid($uid) {
		$this->user = $this->getRawUserByUid($uid);
	}

	/**
	 * Raw initialization of the be_user with username=$name
	 *
	 * @param string $name The username to look up.
	 * @return void
	 * @see \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication::setBeUserByUid()
	 * @internal
	 * @todo Define visibility
	 */
	public function setBeUserByName($name) {
		$this->user = $this->getRawUserByName($name);
	}

	/**
	 * Fetching raw user record with uid=$uid
	 *
	 * @param integer $uid The UID of the backend user to set in ->user
	 * @return array user record or FALSE
	 * @internal
	 * @todo Define visibility
	 */
	public function getRawUserByUid($uid) {
		$user = FALSE;
		$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $this->user_table, 'uid=' . intval($uid) . ' ' . $this->user_where_clause());
		if ($dbres) {
			$user = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres);
			$GLOBALS['TYPO3_DB']->sql_free_result($dbres);
		}
		return $user;
	}

	/**
	 * Fetching raw user record with username=$name
	 *
	 * @param string $name The username to look up.
	 * @return array user record or FALSE
	 * @see \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication::getUserByUid()
	 * @internal
	 * @todo Define visibility
	 */
	public function getRawUserByName($name) {
		$user = FALSE;
		$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $this->user_table, 'username=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($name, $this->user_table) . ' ' . $this->user_where_clause());
		if ($dbres) {
			$user = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres);
			$GLOBALS['TYPO3_DB']->sql_free_result($dbres);
		}
		return $user;
	}

	/*************************
	 *
	 * Create/update user - EXPERIMENTAL
	 *
	 *************************/
	/**
	 * Get a user from DB by username
	 * provided for usage from services
	 *
	 * @param array $dbUser User db table definition: $this->db_user
	 * @param string $username user name
	 * @param string $extraWhere Additional WHERE clause: " AND ...
	 * @return mixed User array or FALSE
	 * @todo Define visibility
	 */
	public function fetchUserRecord($dbUser, $username, $extraWhere = '') {
		$user = FALSE;
		$usernameClause = $username ? $dbUser['username_column'] . '=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($username, $dbUser['table']) : '1=1';
		if ($username || $extraWhere) {
			// Look up the user by the username and/or extraWhere:
			$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $dbUser['table'], $usernameClause . $dbUser['check_pid_clause'] . $dbUser['enable_clause'] . $extraWhere);
			if ($dbres) {
				$user = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres);
				$GLOBALS['TYPO3_DB']->sql_free_result($dbres);
			}
		}
		return $user;
	}

}


?>