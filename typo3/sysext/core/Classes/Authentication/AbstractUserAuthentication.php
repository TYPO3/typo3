<?php
namespace TYPO3\CMS\Core\Authentication;

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

use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Authentication of users in TYPO3
 *
 * This class is used to authenticate a login user.
 * The class is used by both the frontend and backend.
 * In both cases this class is a parent class to BackendUserAuthentication and FrontenUserAuthentication
 *
 * See Inside TYPO3 for more information about the API of the class and internal variables.
 */
abstract class AbstractUserAuthentication
{
    /**
     * Table to use for session data
     * @var string
     */
    public $session_table = '';

    /**
     * Session/Cookie name
     * @var string
     */
    public $name = '';

    /**
     * Session/GET-var name
     * @var string
     */
    public $get_name = '';

    /**
     * Table in database with user data
     * @var string
     */
    public $user_table = '';

    /**
     * Table in database with user groups
     * @var string
     */
    public $usergroup_table = '';

    /**
     * Column for login-name
     * @var string
     */
    public $username_column = '';

    /**
     * Column for password
     * @var string
     */
    public $userident_column = '';

    /**
     * Column for user-id
     * @var string
     */
    public $userid_column = '';

    /**
     * Column for user group information
     * @var string
     */
    public $usergroup_column = '';

    /**
     * Column name for last login timestamp
     * @var string
     */
    public $lastLogin_column = '';

    /**
     * Enable field columns of user table
     * @var array
     */
    public $enablecolumns = [
        'rootLevel' => '',
        // Boolean: If TRUE, 'AND pid=0' will be a part of the query...
        'disabled' => '',
        'starttime' => '',
        'endtime' => '',
        'deleted' => ''
    ];

    /**
     * @var bool
     */
    public $showHiddenRecords = false;

    /**
     * Form field with login-name
     * @var string
     */
    public $formfield_uname = '';

    /**
     * Form field with password
     * @var string
     */
    public $formfield_uident = '';

    /**
     * Form field with status: *'login', 'logout'. If empty login is not verified.
     * @var string
     */
    public $formfield_status = '';

    /**
     * Server session lifetime.
     * If > 0: session-timeout in seconds.
     * If <=0: Instant logout after login.
     * If string: The value is a field name from the user table where the timeout can be found.
     * @var int|string
     */
    public $auth_timeout_field = 0;

    /**
     * Client session lifetime.
     * 0 = Session-cookie.
     * If session-cookies, the browser will stop the session when the browser is closed.
     * Otherwise this specifies the lifetime of a cookie that keeps the session.
     * @var int
     */
    public $lifetime = 0;

    /**
     * GarbageCollection
     * Purge all server session data older than $gc_time seconds.
     * 0 = default to $this->auth_timeout_field or use 86400 seconds (1 day) if $this->auth_timeout_field == 0
     * @var int
     */
    public $gc_time = 0;

    /**
     * Probability for g arbage collection to be run (in percent)
     * @var int
     */
    public $gc_probability = 1;

    /**
     * Decides if the writelog() function is called at login and logout
     * @var bool
     */
    public $writeStdLog = false;

    /**
     * Log failed login attempts
     * @var bool
     */
    public $writeAttemptLog = false;

    /**
     * Send no-cache headers
     * @var bool
     */
    public $sendNoCacheHeaders = true;

    /**
     * If this is set, authentication is also accepted by $_GET.
     * Notice that the identification is NOT 128bit MD5 hash but reduced.
     * This is done in order to minimize the size for mobile-devices, such as WAP-phones
     * @var bool
     */
    public $getFallBack = false;

    /**
     * The ident-hash is normally 32 characters and should be!
     * But if you are making sites for WAP-devices or other low-bandwidth stuff,
     * you may shorten the length.
     * Never let this value drop below 6!
     * A length of 6 would give you more than 16 mio possibilities.
     * @var int
     */
    public $hash_length = 32;

    /**
     * Setting this flag TRUE lets user-authentication happen from GET_VARS if
     * POST_VARS are not set. Thus you may supply username/password with the URL.
     * @var bool
     */
    public $getMethodEnabled = false;

    /**
     * If set to 4, the session will be locked to the user's IP address (all four numbers).
     * Reducing this to 1-3 means that only the given number of parts of the IP address is used.
     * @var int
     */
    public $lockIP = 4;

    /**
     * Keyword list (comma separated list with no spaces!)
     * Each keyword indicates some information that can be included in a hash made to lock down user sessions.
     * Configurable by $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['lockHashKeyWords']
     * @var string
     */
    public $lockHashKeyWords = 'useragent';

    /**
     * @var string
     */
    public $warningEmail = '';

    /**
     * Time span (in seconds) within the number of failed logins are collected
     * @var int
     */
    public $warningPeriod = 3600;

    /**
     * The maximum accepted number of warnings before an email to $warningEmail is sent
     * @var int
     */
    public $warningMax = 3;

    /**
     * If set, the user-record must be stored at the page defined by $checkPid_value
     * @var bool
     */
    public $checkPid = true;

    /**
     * The page id the user record must be stored at
     * @var int
     */
    public $checkPid_value = 0;

    /**
     * session_id (MD5-hash)
     * @var string
     * @internal
     */
    public $id;

    /**
     * Indicates if an authentication was started but failed
     * @var bool
     */
    public $loginFailure = false;

    /**
     * Will be set to TRUE if the login session is actually written during auth-check.
     * @var bool
     */
    public $loginSessionStarted = false;

    /**
     * @var array|NULL contains user- AND session-data from database (joined tables)
     * @internal
     */
    public $user = null;

    /**
     * Will be added to the url (eg. '&login=ab7ef8d...')
     * GET-auth-var if getFallBack is TRUE. Should be inserted in links!
     * @var string
     * @internal
     */
    public $get_URL_ID = '';

    /**
     * Will be set to TRUE if a new session ID was created
     * @var bool
     */
    public $newSessionID = false;

    /**
     * Will force the session cookie to be set every time (lifetime must be 0)
     * @var bool
     */
    public $forceSetCookie = false;

    /**
     * Will prevent the setting of the session cookie (takes precedence over forceSetCookie)
     * @var bool
     */
    public $dontSetCookie = false;

    /**
     * @var bool
     */
    protected $cookieWasSetOnCurrentRequest = false;

    /**
     * Login type, used for services.
     * @var string
     */
    public $loginType = '';

    /**
     * "auth" services configuration array from $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']
     * @var array
     */
    public $svConfig = [];

    /**
     * Write messages to the devlog
     * @var bool
     */
    public $writeDevLog = false;

    /**
     * @var array
     */
    public $uc;

    /**
     * @var DatabaseConnection
     */
    protected $db = null;

    /**
     * Initialize some important variables
     */
    public function __construct()
    {
        $this->db = $this->getDatabaseConnection();
    }

    /**
     * Starts a user session
     * Typical configurations will:
     * a) check if session cookie was set and if not, set one,
     * b) check if a password/username was sent and if so, try to authenticate the user
     * c) Lookup a session attached to a user and check timeout etc.
     * d) Garbage collection, setting of no-cache headers.
     * If a user is authenticated the database record of the user (array) will be set in the ->user internal variable.
     *
     * @throws \TYPO3\CMS\Core\Exception
     * @return void
     */
    public function start()
    {
        // Backend or frontend login - used for auth services
        if (empty($this->loginType)) {
            throw new \TYPO3\CMS\Core\Exception('No loginType defined, should be set explicitly by subclass');
        }
        // Enable dev logging if set
        if ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['writeDevLog']) {
            $this->writeDevLog = true;
        }
        if ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['writeDevLog' . $this->loginType]) {
            $this->writeDevLog = true;
        }
        if (TYPO3_DLOG) {
            $this->writeDevLog = true;
        }
        if ($this->writeDevLog) {
            GeneralUtility::devLog('## Beginning of auth logging.', \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication::class);
        }
        // Init vars.
        $mode = '';
        $this->newSessionID = false;
        // $id is set to ses_id if cookie is present. Else set to FALSE, which will start a new session
        $id = $this->getCookie($this->name);
        $this->svConfig = $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth'];

        // If fallback to get mode....
        if (!$id && $this->getFallBack && $this->get_name) {
            $id = isset($_GET[$this->get_name]) ? GeneralUtility::_GET($this->get_name) : '';
            if (strlen($id) != $this->hash_length) {
                $id = '';
            }
            $mode = 'get';
        }

        // If new session or client tries to fix session...
        if (!$id || !$this->isExistingSessionRecord($id)) {
            // New random session-$id is made
            $id = $this->createSessionId();
            // New session
            $this->newSessionID = true;
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
        $this->user = null;
        // Set all possible headers that could ensure that the script is not cached on the client-side
        if ($this->sendNoCacheHeaders && !(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI)) {
            header('Expires: 0');
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
            $cacheControlHeader = 'no-cache, must-revalidate';
            $pragmaHeader = 'no-cache';
            // Prevent error message in IE when using a https connection
            // see http://forge.typo3.org/issues/24125
            $clientInfo = GeneralUtility::clientInfo();
            if ($clientInfo['BROWSER'] === 'msie' && GeneralUtility::getIndpEnv('TYPO3_SSL')) {
                // Some IEs can not handle no-cache
                // see http://support.microsoft.com/kb/323308/en-us
                $cacheControlHeader = 'must-revalidate';
                // IE needs "Pragma: private" if SSL connection
                $pragmaHeader = 'private';
            }
            header('Cache-Control: ' . $cacheControlHeader);
            header('Pragma: ' . $pragmaHeader);
        }
        // Load user session, check to see if anyone has submitted login-information and if so authenticate
        // the user with the session. $this->user[uid] may be used to write log...
        $this->checkAuthentication();
        // Setting cookies
        if (!$this->dontSetCookie) {
            $this->setSessionCookie();
        }
        // Hook for alternative ways of filling the $this->user array (is used by the "timtaw" extension)
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['postUserLookUp'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['postUserLookUp'] as $funcName) {
                $_params = [
                    'pObj' => &$this
                ];
                GeneralUtility::callUserFunction($funcName, $_params, $this);
            }
        }
        // Set $this->gc_time if not explicitly specified
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
     * @throws \TYPO3\CMS\Core\Exception
     */
    protected function setSessionCookie()
    {
        $isSetSessionCookie = $this->isSetSessionCookie();
        $isRefreshTimeBasedCookie = $this->isRefreshTimeBasedCookie();
        if ($isSetSessionCookie || $isRefreshTimeBasedCookie) {
            $settings = $GLOBALS['TYPO3_CONF_VARS']['SYS'];
            // Get the domain to be used for the cookie (if any):
            $cookieDomain = $this->getCookieDomain();
            // If no cookie domain is set, use the base path:
            $cookiePath = $cookieDomain ? '/' : GeneralUtility::getIndpEnv('TYPO3_SITE_PATH');
            // If the cookie lifetime is set, use it:
            $cookieExpire = $isRefreshTimeBasedCookie ? $GLOBALS['EXEC_TIME'] + $this->lifetime : 0;
            // Use the secure option when the current request is served by a secure connection:
            $cookieSecure = (bool)$settings['cookieSecure'] && GeneralUtility::getIndpEnv('TYPO3_SSL');
            // Deliver cookies only via HTTP and prevent possible XSS by JavaScript:
            $cookieHttpOnly = (bool)$settings['cookieHttpOnly'];
            // Do not set cookie if cookieSecure is set to "1" (force HTTPS) and no secure channel is used:
            if ((int)$settings['cookieSecure'] !== 1 || GeneralUtility::getIndpEnv('TYPO3_SSL')) {
                setcookie($this->name, $this->id, $cookieExpire, $cookiePath, $cookieDomain, $cookieSecure, $cookieHttpOnly);
                $this->cookieWasSetOnCurrentRequest = true;
            } else {
                throw new \TYPO3\CMS\Core\Exception('Cookie was not set since HTTPS was forced in $TYPO3_CONF_VARS[SYS][cookieSecure].', 1254325546);
            }
            if ($this->writeDevLog) {
                $devLogMessage = ($isRefreshTimeBasedCookie ? 'Updated Cookie: ' : 'Set Cookie: ') . $this->id;
                GeneralUtility::devLog($devLogMessage . ($cookieDomain ? ', ' . $cookieDomain : ''), \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication::class);
            }
        }
    }

    /**
     * Gets the domain to be used on setting cookies.
     * The information is taken from the value in $GLOBALS['TYPO3_CONF_VARS']['SYS']['cookieDomain'].
     *
     * @return string The domain to be used on setting cookies
     */
    protected function getCookieDomain()
    {
        $result = '';
        $cookieDomain = $GLOBALS['TYPO3_CONF_VARS']['SYS']['cookieDomain'];
        // If a specific cookie domain is defined for a given TYPO3_MODE,
        // use that domain
        if (!empty($GLOBALS['TYPO3_CONF_VARS'][$this->loginType]['cookieDomain'])) {
            $cookieDomain = $GLOBALS['TYPO3_CONF_VARS'][$this->loginType]['cookieDomain'];
        }
        if ($cookieDomain) {
            if ($cookieDomain[0] == '/') {
                $match = [];
                $matchCnt = @preg_match($cookieDomain, GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY'), $match);
                if ($matchCnt === false) {
                    GeneralUtility::sysLog('The regular expression for the cookie domain (' . $cookieDomain . ') contains errors. The session is not shared across sub-domains.', 'core', GeneralUtility::SYSLOG_SEVERITY_ERROR);
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
     * @param string $cookieName The cookie ID
     * @return string The value stored in the cookie
     */
    protected function getCookie($cookieName)
    {
        return isset($_COOKIE[$cookieName]) ? stripslashes($_COOKIE[$cookieName]) : '';
    }

    /**
     * Determine whether a session cookie needs to be set (lifetime=0)
     *
     * @return bool
     * @internal
     */
    public function isSetSessionCookie()
    {
        return ($this->newSessionID || $this->forceSetCookie) && $this->lifetime == 0;
    }

    /**
     * Determine whether a non-session cookie needs to be set (lifetime>0)
     *
     * @return bool
     * @internal
     */
    public function isRefreshTimeBasedCookie()
    {
        return $this->lifetime > 0;
    }

    /**
     * Checks if a submission of username and password is present or use other authentication by auth services
     *
     * @throws \RuntimeException
     * @return void
     * @internal
     */
    public function checkAuthentication()
    {
        // No user for now - will be searched by service below
        $tempuserArr = [];
        $tempuser = false;
        // User is not authenticated by default
        $authenticated = false;
        // User want to login with passed login data (name/password)
        $activeLogin = false;
        // Indicates if an active authentication failed (not auto login)
        $this->loginFailure = false;
        if ($this->writeDevLog) {
            GeneralUtility::devLog('Login type: ' . $this->loginType, \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication::class);
        }
        // The info array provide additional information for auth services
        $authInfo = $this->getAuthInfoArray();
        // Get Login/Logout data submitted by a form or params
        $loginData = $this->getLoginFormData();
        if ($this->writeDevLog) {
            GeneralUtility::devLog('Login data: ' . GeneralUtility::arrayToLogString($loginData), \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication::class);
        }
        // Active logout (eg. with "logout" button)
        if ($loginData['status'] === 'logout') {
            if ($this->writeStdLog) {
                // $type,$action,$error,$details_nr,$details,$data,$tablename,$recuid,$recpid
                $this->writelog(255, 2, 0, 2, 'User %s logged out', [$this->user['username']], '', 0, 0);
            }
            // Logout written to log
            if ($this->writeDevLog) {
                GeneralUtility::devLog('User logged out. Id: ' . $this->id, \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication::class, -1);
            }
            $this->logoff();
        }
        // Determine whether we need to skip session update.
        // This is used mainly for checking session timeout in advance without refreshing the current session's timeout.
        $skipSessionUpdate = (bool)GeneralUtility::_GP('skipSessionUpdate');
        $haveSession = false;
        if (!$this->newSessionID) {
            // Read user session
            $authInfo['userSession'] = $this->fetchUserSession($skipSessionUpdate);
            $haveSession = is_array($authInfo['userSession']);
        }
        // Active login (eg. with login form)
        if (!$haveSession && $loginData['status'] === 'login') {
            $activeLogin = true;
            if ($this->writeDevLog) {
                GeneralUtility::devLog('Active login (eg. with login form)', \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication::class);
            }
            // check referer for submitted login values
            if ($this->formfield_status && $loginData['uident'] && $loginData['uname']) {
                $httpHost = GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY');
                if (!$this->getMethodEnabled && ($httpHost != $authInfo['refInfo']['host'] && !$GLOBALS['TYPO3_CONF_VARS']['SYS']['doNotCheckReferer'])) {
                    throw new \RuntimeException('TYPO3 Fatal Error: Error: This host address ("' . $httpHost . '") and the referer host ("' . $authInfo['refInfo']['host'] . '") mismatches! ' .
                        'It is possible that the environment variable HTTP_REFERER is not passed to the script because of a proxy. ' .
                        'The site administrator can disable this check in the "All Configuration" section of the Install Tool (flag: TYPO3_CONF_VARS[SYS][doNotCheckReferer]).', 1270853930);
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
        if ($this->writeDevLog) {
            if ($haveSession) {
                GeneralUtility::devLog('User session found: ' . GeneralUtility::arrayToLogString($authInfo['userSession'], [$this->userid_column, $this->username_column]), \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication::class, 0);
            } else {
                GeneralUtility::devLog('No user session found.', \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication::class, 2);
            }
            if (is_array($this->svConfig['setup'])) {
                GeneralUtility::devLog('SV setup: ' . GeneralUtility::arrayToLogString($this->svConfig['setup']), \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication::class, 0);
            }
        }
        // Fetch user if ...
        if (
            $activeLogin || $this->svConfig['setup'][$this->loginType . '_alwaysFetchUser']
            || !$haveSession && $this->svConfig['setup'][$this->loginType . '_fetchUserIfNoSession']
        ) {
            // Use 'auth' service to find the user
            // First found user will be used
            $serviceChain = '';
            $subType = 'getUser' . $this->loginType;
            while (is_object($serviceObj = GeneralUtility::makeInstanceService('auth', $subType, $serviceChain))) {
                $serviceChain .= ',' . $serviceObj->getServiceKey();
                $serviceObj->initAuth($subType, $loginData, $authInfo, $this);
                if ($row = $serviceObj->getUser()) {
                    $tempuserArr[] = $row;
                    if ($this->writeDevLog) {
                        GeneralUtility::devLog('User found: ' . GeneralUtility::arrayToLogString($row, [$this->userid_column, $this->username_column]), \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication::class, 0);
                    }
                    // User found, just stop to search for more if not configured to go on
                    if (!$this->svConfig['setup'][$this->loginType . '_fetchAllUsers']) {
                        break;
                    }
                }
                unset($serviceObj);
            }
            unset($serviceObj);
            if ($this->writeDevLog && $this->svConfig['setup'][$this->loginType . '_alwaysFetchUser']) {
                GeneralUtility::devLog($this->loginType . '_alwaysFetchUser option is enabled', \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication::class);
            }
            if ($this->writeDevLog && $serviceChain) {
                GeneralUtility::devLog($subType . ' auth services called: ' . $serviceChain, \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication::class);
            }
            if ($this->writeDevLog && empty($tempuserArr)) {
                GeneralUtility::devLog('No user found by services', \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication::class);
            }
            if ($this->writeDevLog && !empty($tempuserArr)) {
                GeneralUtility::devLog(count($tempuserArr) . ' user records found by services', \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication::class);
            }
        }
        // If no new user was set we use the already found user session
        if (empty($tempuserArr) && $haveSession) {
            $tempuserArr[] = $authInfo['userSession'];
            $tempuser = $authInfo['userSession'];
            // User is authenticated because we found a user session
            $authenticated = true;
            if ($this->writeDevLog) {
                GeneralUtility::devLog('User session used: ' . GeneralUtility::arrayToLogString($authInfo['userSession'], [$this->userid_column, $this->username_column]), \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication::class);
            }
        }
        // Re-auth user when 'auth'-service option is set
        if ($this->svConfig['setup'][$this->loginType . '_alwaysAuthUser']) {
            $authenticated = false;
            if ($this->writeDevLog) {
                GeneralUtility::devLog('alwaysAuthUser option is enabled', \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication::class);
            }
        }
        // Authenticate the user if needed
        if (!empty($tempuserArr) && !$authenticated) {
            foreach ($tempuserArr as $tempuser) {
                // Use 'auth' service to authenticate the user
                // If one service returns FALSE then authentication failed
                // a service might return 100 which means there's no reason to stop but the user can't be authenticated by that service
                if ($this->writeDevLog) {
                    GeneralUtility::devLog('Auth user: ' . GeneralUtility::arrayToLogString($tempuser), \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication::class);
                }
                $serviceChain = '';
                $subType = 'authUser' . $this->loginType;
                while (is_object($serviceObj = GeneralUtility::makeInstanceService('auth', $subType, $serviceChain))) {
                    $serviceChain .= ',' . $serviceObj->getServiceKey();
                    $serviceObj->initAuth($subType, $loginData, $authInfo, $this);
                    if (($ret = $serviceObj->authUser($tempuser)) > 0) {
                        // If the service returns >=200 then no more checking is needed - useful for IP checking without password
                        if ((int)$ret >= 200) {
                            $authenticated = true;
                            break;
                        } elseif ((int)$ret >= 100) {
                        } else {
                            $authenticated = true;
                        }
                    } else {
                        $authenticated = false;
                        break;
                    }
                    unset($serviceObj);
                }
                unset($serviceObj);
                if ($this->writeDevLog && $serviceChain) {
                    GeneralUtility::devLog($subType . ' auth services called: ' . $serviceChain, \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication::class);
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
            $this->loginFailure = false;
            // Insert session record if needed:
            if (!($haveSession && ($tempuser['ses_id'] == $this->id || $tempuser['uid'] == $authInfo['userSession']['ses_userid']))) {
                $sessionData = $this->createUserSession($tempuser);
                if ($sessionData) {
                    $this->user = array_merge(
                        $tempuser,
                        $sessionData
                    );
                }
                // The login session is started.
                $this->loginSessionStarted = true;
                if ($this->writeDevLog && is_array($this->user)) {
                    GeneralUtility::devLog('User session finally read: ' . GeneralUtility::arrayToLogString($this->user, [$this->userid_column, $this->username_column]), \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication::class, -1);
                }
            } elseif ($haveSession) {
                $this->user = $authInfo['userSession'];
            }
            if ($activeLogin && !$this->newSessionID) {
                $this->regenerateSessionId();
            }
            // User logged in - write that to the log!
            if ($this->writeStdLog && $activeLogin) {
                $this->writelog(255, 1, 0, 1, 'User %s logged in from %s (%s)', [$tempuser[$this->username_column], GeneralUtility::getIndpEnv('REMOTE_ADDR'), GeneralUtility::getIndpEnv('REMOTE_HOST')], '', '', '', -1, '', $tempuser['uid']);
            }
            if ($this->writeDevLog && $activeLogin) {
                GeneralUtility::devLog('User ' . $tempuser[$this->username_column] . ' logged in from ' . GeneralUtility::getIndpEnv('REMOTE_ADDR') . ' (' . GeneralUtility::getIndpEnv('REMOTE_HOST') . ')', \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication::class, -1);
            }
            if ($this->writeDevLog && !$activeLogin) {
                GeneralUtility::devLog('User ' . $tempuser[$this->username_column] . ' authenticated from ' . GeneralUtility::getIndpEnv('REMOTE_ADDR') . ' (' . GeneralUtility::getIndpEnv('REMOTE_HOST') . ')', \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication::class, -1);
            }
        } elseif ($activeLogin || !empty($tempuserArr)) {
            $this->loginFailure = true;
            if ($this->writeDevLog && empty($tempuserArr) && $activeLogin) {
                GeneralUtility::devLog('Login failed: ' . GeneralUtility::arrayToLogString($loginData), \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication::class, 2);
            }
            if ($this->writeDevLog && !empty($tempuserArr)) {
                GeneralUtility::devLog('Login failed: ' . GeneralUtility::arrayToLogString($tempuser, [$this->userid_column, $this->username_column]), \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication::class, 2);
            }
        }
        // If there were a login failure, check to see if a warning email should be sent:
        if ($this->loginFailure && $activeLogin) {
            if ($this->writeDevLog) {
                GeneralUtility::devLog('Call checkLogFailures: ' . GeneralUtility::arrayToLogString(['warningEmail' => $this->warningEmail, 'warningPeriod' => $this->warningPeriod, 'warningMax' => $this->warningMax]), \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication::class, -1);
            }

            // Hook to implement login failure tracking methods
            if (
                !empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['postLoginFailureProcessing'])
                && is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['postLoginFailureProcessing'])
            ) {
                $_params = [];
                foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['postLoginFailureProcessing'] as $_funcRef) {
                    GeneralUtility::callUserFunction($_funcRef, $_params, $this);
                }
            } else {
                // If no hook is implemented, wait for 5 seconds
                sleep(5);
            }

            $this->checkLogFailures($this->warningEmail, $this->warningPeriod, $this->warningMax);
        }
    }

    /**
     * Creates a new session ID.
     *
     * @return string The new session ID
     */
    public function createSessionId()
    {
        return GeneralUtility::getRandomHexString($this->hash_length);
    }

    /**
     * Regenerate the session ID and transfer the session to new ID
     * Call this method whenever a user proceeds to a higher authorization level
     * e.g. when an anonymous session is now authenticated.
     */
    protected function regenerateSessionId()
    {
        $oldSessionId = $this->id;
        $this->id = $this->createSessionId();
        // Update session record with new ID
        $this->db->exec_UPDATEquery(
            $this->session_table,
            'ses_id = ' . $this->db->fullQuoteStr($oldSessionId, $this->session_table)
                . ' AND ses_name = ' . $this->db->fullQuoteStr($this->name, $this->session_table),
            ['ses_id' => $this->id]
        );
        $this->user['ses_id'] = $this->id;
        $this->newSessionID = true;
    }

    /*************************
     *
     * User Sessions
     *
     *************************/
    /**
     * Creates a user session record and returns its values.
     *
     * @param array $tempuser User data array
     *
     * @return array The session data for the newly created session.
     */
    public function createUserSession($tempuser)
    {
        if ($this->writeDevLog) {
            GeneralUtility::devLog('Create session ses_id = ' . $this->id, \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication::class);
        }
        // Delete session entry first
        $this->db->exec_DELETEquery(
            $this->session_table,
            'ses_id = ' . $this->db->fullQuoteStr($this->id, $this->session_table)
                . ' AND ses_name = ' . $this->db->fullQuoteStr($this->name, $this->session_table)
        );
        // Re-create session entry
        $insertFields = $this->getNewSessionRecord($tempuser);
        $inserted = (bool)$this->db->exec_INSERTquery($this->session_table, $insertFields);
        if (!$inserted) {
            $message = 'Session data could not be written to DB. Error: ' . $this->db->sql_error();
            GeneralUtility::sysLog($message, 'core', GeneralUtility::SYSLOG_SEVERITY_WARNING);
            if ($this->writeDevLog) {
                GeneralUtility::devLog($message, \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication::class, 2);
            }
        }
        // Updating lastLogin_column carrying information about last login.
        if ($this->lastLogin_column && $inserted) {
            $this->db->exec_UPDATEquery(
                $this->user_table,
                $this->userid_column . '=' . $this->db->fullQuoteStr($tempuser[$this->userid_column], $this->user_table),
                [$this->lastLogin_column => $GLOBALS['EXEC_TIME']]
            );
        }

        return $inserted ? $insertFields : [];
    }

    /**
     * Returns a new session record for the current user for insertion into the DB.
     * This function is mainly there as a wrapper for inheriting classes to override it.
     *
     * @param array $tempuser
     * @return array User session record
     */
    public function getNewSessionRecord($tempuser)
    {
        $sessionIpLock = '[DISABLED]';
        if ($this->lockIP && empty($tempuser['disableIPlock'])) {
            $sessionIpLock = $this->ipLockClause_remoteIPNumber($this->lockIP);
        }

        return [
            'ses_id' => $this->id,
            'ses_name' => $this->name,
            'ses_iplock' => $sessionIpLock,
            'ses_hashlock' => $this->hashLockClause_getHashInt(),
            'ses_userid' => $tempuser[$this->userid_column],
            'ses_tstamp' => $GLOBALS['EXEC_TIME'],
            'ses_data' => ''
        ];
    }

    /**
     * Read the user session from db.
     *
     * @param bool $skipSessionUpdate
     * @return array User session data
     */
    public function fetchUserSession($skipSessionUpdate = false)
    {
        $user = false;
        if ($this->writeDevLog) {
            GeneralUtility::devLog('Fetch session ses_id = ' . $this->id, \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication::class);
        }

        // Fetch the user session from the DB
        $statement = $this->fetchUserSessionFromDB();

        if ($statement) {
            $statement->execute();
            $user = $statement->fetch();
            $statement->free();
        }
        if ($user) {
            // A user was found
            if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($this->auth_timeout_field)) {
                // Get timeout from object
                $timeout = (int)$this->auth_timeout_field;
            } else {
                // Get timeout-time from usertable
                $timeout = (int)$user[$this->auth_timeout_field];
            }
            // If timeout > 0 (TRUE) and current time has not exceeded the latest sessions-time plus the timeout in seconds then accept user
            // Use a gracetime-value to avoid updating a session-record too often
            if ($timeout > 0 && $GLOBALS['EXEC_TIME'] < $user['ses_tstamp'] + $timeout) {
                $sessionUpdateGracePeriod = 61;
                if (!$skipSessionUpdate && $GLOBALS['EXEC_TIME'] > ($user['ses_tstamp'] + $sessionUpdateGracePeriod)) {
                    $this->db->exec_UPDATEquery($this->session_table, 'ses_id=' . $this->db->fullQuoteStr($this->id, $this->session_table)
                        . ' AND ses_name=' . $this->db->fullQuoteStr($this->name, $this->session_table), ['ses_tstamp' => $GLOBALS['EXEC_TIME']]);
                    // Make sure that the timestamp is also updated in the array
                    $user['ses_tstamp'] = $GLOBALS['EXEC_TIME'];
                }
            } else {
                // Delete any user set...
                $this->logoff();
                $user = false;
            }
        }
        return $user;
    }

    /**
     * Log out current user!
     * Removes the current session record, sets the internal ->user array to a blank string;
     * Thereby the current user (if any) is effectively logged out!
     *
     * @return void
     */
    public function logoff()
    {
        if ($this->writeDevLog) {
            GeneralUtility::devLog('logoff: ses_id = ' . $this->id, \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication::class);
        }
        // Release the locked records
        \TYPO3\CMS\Backend\Utility\BackendUtility::lockRecords();
        // Hook for pre-processing the logoff() method, requested and implemented by andreas.otto@dkd.de:
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_pre_processing'])) {
            $_params = [];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_pre_processing'] as $_funcRef) {
                if ($_funcRef) {
                    GeneralUtility::callUserFunction($_funcRef, $_params, $this);
                }
            }
        }
        $this->db->exec_DELETEquery($this->session_table, 'ses_id = ' . $this->db->fullQuoteStr($this->id, $this->session_table) . '
						AND ses_name = ' . $this->db->fullQuoteStr($this->name, $this->session_table));
        $this->user = null;
        // Hook for post-processing the logoff() method, requested and implemented by andreas.otto@dkd.de:
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_post_processing'])) {
            $_params = [];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_post_processing'] as $_funcRef) {
                if ($_funcRef) {
                    GeneralUtility::callUserFunction($_funcRef, $_params, $this);
                }
            }
        }
    }

    /**
     * Empty / unset the cookie
     *
     * @param string $cookieName usually, this is $this->name
     * @return void
     */
    public function removeCookie($cookieName)
    {
        $cookieDomain = $this->getCookieDomain();
        // If no cookie domain is set, use the base path
        $cookiePath = $cookieDomain ? '/' : GeneralUtility::getIndpEnv('TYPO3_SITE_PATH');
        setcookie($cookieName, null, -1, $cookiePath, $cookieDomain);
    }

    /**
     * Determine whether there's an according session record to a given session_id
     * in the database. Don't care if session record is still valid or not.
     *
     * @param int $id Claimed Session ID
     * @return bool Returns TRUE if a corresponding session was found in the database
     */
    public function isExistingSessionRecord($id)
    {
        $statement = $this->db->prepare_SELECTquery('COUNT(*)', $this->session_table, 'ses_id = :ses_id');
        $statement->execute([':ses_id' => $id]);
        $row = $statement->fetch(\TYPO3\CMS\Core\Database\PreparedStatement::FETCH_NUM);
        $statement->free();
        return (bool)$row[0];
    }

    /**
     * Returns whether this request is going to set a cookie
     * or a cookie was already found in the system
     *
     * @return bool Returns TRUE if a cookie is set
     */
    public function isCookieSet()
    {
        return $this->cookieWasSetOnCurrentRequest || $this->getCookie($this->name);
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
    protected function fetchUserSessionFromDB()
    {
        $statement = null;
        $ipLockClause = $this->ipLockClause();
        $statement = $this->db->prepare_SELECTquery('*', $this->session_table . ',' . $this->user_table, $this->session_table . '.ses_id = :ses_id
					AND ' . $this->session_table . '.ses_name = :ses_name
					AND ' . $this->session_table . '.ses_userid = ' . $this->user_table . '.' . $this->userid_column . '
					' . $ipLockClause['where'] . '
					' . $this->hashLockClause() . '
					' . $this->user_where_clause());
        $statement->bindValues([
                ':ses_id' => $this->id,
                ':ses_name' => $this->name
            ]);
        $statement->bindValues($ipLockClause['parameters']);
        return $statement;
    }

    /**
     * This returns the where-clause needed to select the user
     * with respect flags like deleted, hidden, starttime, endtime
     *
     * @return string
     * @access private
     */
    protected function user_where_clause()
    {
        $whereClause = '';
        if ($this->enablecolumns['rootLevel']) {
            $whereClause .= 'AND ' . $this->user_table . '.pid=0 ';
        }
        if ($this->enablecolumns['disabled']) {
            $whereClause .= ' AND ' . $this->user_table . '.' . $this->enablecolumns['disabled'] . '=0';
        }
        if ($this->enablecolumns['deleted']) {
            $whereClause .= ' AND ' . $this->user_table . '.' . $this->enablecolumns['deleted'] . '=0';
        }
        if ($this->enablecolumns['starttime']) {
            $whereClause .= ' AND (' . $this->user_table . '.' . $this->enablecolumns['starttime'] . '<=' . $GLOBALS['EXEC_TIME'] . ')';
        }
        if ($this->enablecolumns['endtime']) {
            $whereClause .= ' AND (' . $this->user_table . '.' . $this->enablecolumns['endtime'] . '=0 OR '
                . $this->user_table . '.' . $this->enablecolumns['endtime'] . '>' . $GLOBALS['EXEC_TIME'] . ')';
        }
        return $whereClause;
    }

    /**
     * This returns the where prepared statement-clause needed to lock a user to the IP address
     *
     * @return array
     * @access private
     */
    protected function ipLockClause()
    {
        $statementClause = [
            'where' => '',
            'parameters' => []
        ];
        if ($this->lockIP) {
            $statementClause['where'] = 'AND (
				' . $this->session_table . '.ses_iplock = :ses_iplock
				OR ' . $this->session_table . '.ses_iplock=\'[DISABLED]\'
				)';
            $statementClause['parameters'] = [
                ':ses_iplock' => $this->ipLockClause_remoteIPNumber($this->lockIP)
            ];
        }
        return $statementClause;
    }

    /**
     * Returns the IP address to lock to.
     * The IP address may be partial based on $parts.
     *
     * @param int $parts 1-4: Indicates how many parts of the IP address to return. 4 means all, 1 means only first number.
     * @return string (Partial) IP address for REMOTE_ADDR
     */
    protected function ipLockClause_remoteIPNumber($parts)
    {
        $IP = GeneralUtility::getIndpEnv('REMOTE_ADDR');
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
     * This code is used as an alternative verification when the JavaScript interface executes cmd's to
     * tce_db.php from eg. MSIE 5.0 because the proper referer is not passed with this browser...
     *
     * @return string
     */
    public function veriCode()
    {
        return substr(md5($this->id . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']), 0, 10);
    }

    /**
     * This returns the where-clause needed to lock a user to a hash integer
     *
     * @return string
     * @access private
     */
    protected function hashLockClause()
    {
        return 'AND ' . $this->session_table . '.ses_hashlock=' . $this->hashLockClause_getHashInt();
    }

    /**
     * Creates hash integer to lock user to. Depends on configured keywords
     *
     * @return int Hash integer
     * @access private
     */
    protected function hashLockClause_getHashInt()
    {
        $hashStr = '';
        if (GeneralUtility::inList($this->lockHashKeyWords, 'useragent')) {
            $hashStr .= ':' . GeneralUtility::getIndpEnv('HTTP_USER_AGENT');
        }
        return GeneralUtility::md5int($hashStr);
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
     * @param array|string $variable An array you want to store for the user as session data. If $variable is not supplied (is blank string), the internal variable, ->uc, is stored by default
     * @return void
     */
    public function writeUC($variable = '')
    {
        if (is_array($this->user) && $this->user[$this->userid_column]) {
            if (!is_array($variable)) {
                $variable = $this->uc;
            }
            if ($this->writeDevLog) {
                GeneralUtility::devLog('writeUC: ' . $this->userid_column . '=' . (int)$this->user[$this->userid_column], \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication::class);
            }
            $this->db->exec_UPDATEquery($this->user_table, $this->userid_column . '=' . (int)$this->user[$this->userid_column], ['uc' => serialize($variable)]);
        }
    }

    /**
     * Sets $theUC as the internal variable ->uc IF $theUC is an array.
     * If $theUC is FALSE, the 'uc' content from the ->user array will be unserialized and restored in ->uc
     *
     * @param mixed $theUC If an array, then set as ->uc, otherwise load from user record
     * @return void
     */
    public function unpack_uc($theUC = '')
    {
        if (!$theUC && isset($this->user['uc'])) {
            $theUC = unserialize($this->user['uc']);
        }
        if (is_array($theUC)) {
            $this->uc = $theUC;
        }
    }

    /**
     * Stores data for a module.
     * The data is stored with the session id so you can even check upon retrieval
     * if the module data is from a previous session or from the current session.
     *
     * @param string $module Is the name of the module ($MCONF['name'])
     * @param mixed $data Is the data you want to store for that module (array, string, ...)
     * @param bool|int $noSave If $noSave is set, then the ->uc array (which carries all kinds of user data) is NOT written immediately, but must be written by some subsequent call.
     * @return void
     */
    public function pushModuleData($module, $data, $noSave = 0)
    {
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
     */
    public function getModuleData($module, $type = '')
    {
        if ($type != 'ses' || (isset($this->uc['moduleSessionID'][$module]) && $this->uc['moduleSessionID'][$module] == $this->id)) {
            return $this->uc['moduleData'][$module];
        }
        return null;
    }

    /**
     * Returns the session data stored for $key.
     * The data will last only for this login session since it is stored in the session table.
     *
     * @param string $key Pointer to an associative key in the session data array which is stored serialized in the field "ses_data" of the session table.
     * @return mixed
     */
    public function getSessionData($key)
    {
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
     */
    public function setAndSaveSessionData($key, $data)
    {
        $sesDat = unserialize($this->user['ses_data']);
        $sesDat[$key] = $data;
        $this->user['ses_data'] = serialize($sesDat);
        if ($this->writeDevLog) {
            GeneralUtility::devLog('setAndSaveSessionData: ses_id = ' . $this->user['ses_id'], \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication::class);
        }
        $this->db->exec_UPDATEquery($this->session_table, 'ses_id=' . $this->db->fullQuoteStr($this->user['ses_id'], $this->session_table), ['ses_data' => $this->user['ses_data']]);
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
     */
    public function getLoginFormData()
    {
        $loginData = [];
        $loginData['status'] = GeneralUtility::_GP($this->formfield_status);
        if ($this->getMethodEnabled) {
            $loginData['uname'] = GeneralUtility::_GP($this->formfield_uname);
            $loginData['uident'] = GeneralUtility::_GP($this->formfield_uident);
        } else {
            $loginData['uname'] = GeneralUtility::_POST($this->formfield_uname);
            $loginData['uident'] = GeneralUtility::_POST($this->formfield_uident);
        }
        // Only process the login data if a login is requested
        if ($loginData['status'] === 'login') {
            $loginData = $this->processLoginData($loginData);
        }
        $loginData = array_map('trim', $loginData);
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
     */
    public function processLoginData($loginData, $passwordTransmissionStrategy = '')
    {
        $loginSecurityLevel = trim($GLOBALS['TYPO3_CONF_VARS'][$this->loginType]['loginSecurityLevel']) ?: 'normal';
        $passwordTransmissionStrategy = $passwordTransmissionStrategy ?: $loginSecurityLevel;
        if ($this->writeDevLog) {
            GeneralUtility::devLog('Login data before processing: ' . GeneralUtility::arrayToLogString($loginData), \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication::class);
        }
        $serviceChain = '';
        $subType = 'processLoginData' . $this->loginType;
        $authInfo = $this->getAuthInfoArray();
        $isLoginDataProcessed = false;
        $processedLoginData = $loginData;
        while (is_object($serviceObject = GeneralUtility::makeInstanceService('auth', $subType, $serviceChain))) {
            $serviceChain .= ',' . $serviceObject->getServiceKey();
            $serviceObject->initAuth($subType, $loginData, $authInfo, $this);
            $serviceResult = $serviceObject->processLoginData($processedLoginData, $passwordTransmissionStrategy);
            if (!empty($serviceResult)) {
                $isLoginDataProcessed = true;
                // If the service returns >=200 then no more processing is needed
                if ((int)$serviceResult >= 200) {
                    unset($serviceObject);
                    break;
                }
            }
            unset($serviceObject);
        }
        if ($isLoginDataProcessed) {
            $loginData = $processedLoginData;
            if ($this->writeDevLog) {
                GeneralUtility::devLog('Processed login data: ' . GeneralUtility::arrayToLogString($processedLoginData), \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication::class);
            }
        }
        return $loginData;
    }

    /**
     * Returns an info array which provides additional information for auth services
     *
     * @return array
     * @internal
     */
    public function getAuthInfoArray()
    {
        $authInfo = [];
        $authInfo['loginType'] = $this->loginType;
        $authInfo['refInfo'] = parse_url(GeneralUtility::getIndpEnv('HTTP_REFERER'));
        $authInfo['HTTP_HOST'] = GeneralUtility::getIndpEnv('HTTP_HOST');
        $authInfo['REMOTE_ADDR'] = GeneralUtility::getIndpEnv('REMOTE_ADDR');
        $authInfo['REMOTE_HOST'] = GeneralUtility::getIndpEnv('REMOTE_HOST');
        $authInfo['showHiddenRecords'] = $this->showHiddenRecords;
        // Can be overidden in localconf by SVCONF:
        $authInfo['db_user']['table'] = $this->user_table;
        $authInfo['db_user']['userid_column'] = $this->userid_column;
        $authInfo['db_user']['username_column'] = $this->username_column;
        $authInfo['db_user']['userident_column'] = $this->userident_column;
        $authInfo['db_user']['usergroup_column'] = $this->usergroup_column;
        $authInfo['db_user']['enable_clause'] = $this->user_where_clause();
        if ($this->checkPid && $this->checkPid_value !== null) {
            $authInfo['db_user']['checkPidList'] = $this->checkPid_value;
            $authInfo['db_user']['check_pid_clause'] = ' AND pid IN (' .
                $this->db->cleanIntList($this->checkPid_value) . ')';
        } else {
            $authInfo['db_user']['checkPidList'] = '';
            $authInfo['db_user']['check_pid_clause'] = '';
        }
        $authInfo['db_groups']['table'] = $this->usergroup_table;
        return $authInfo;
    }

    /**
     * Check the login data with the user record data for builtin login methods
     *
     * @param array $user User data array
     * @param array $loginData Login data array
     * @param string $passwordCompareStrategy Alternative passwordCompareStrategy. Used when authentication services wants to override the default.
     * @return bool TRUE if login data matched
     */
    public function compareUident($user, $loginData, $passwordCompareStrategy = '')
    {
        return (string)$loginData['uident_text'] !== '' && (string)$loginData['uident_text'] === (string)$user[$this->userident_column];
    }

    /**
     * Garbage collector, removing old expired sessions.
     *
     * @return void
     * @internal
     */
    public function gc()
    {
        $this->db->exec_DELETEquery($this->session_table, 'ses_tstamp < ' . (int)($GLOBALS['EXEC_TIME'] - $this->gc_time) . ' AND ses_name = ' . $this->db->fullQuoteStr($this->name, $this->session_table));
    }

    /**
     * DUMMY: Writes to log database table (in some extension classes)
     *
     * @param int $type denotes which module that has submitted the entry. This is the current list:  1=tce_db; 2=tce_file; 3=system (eg. sys_history save); 4=modules; 254=Personal settings changed; 255=login / out action: 1=login, 2=logout, 3=failed login (+ errorcode 3), 4=failure_warning_email sent
     * @param int $action denotes which specific operation that wrote the entry (eg. 'delete', 'upload', 'update' and so on...). Specific for each $type. Also used to trigger update of the interface. (see the log-module for the meaning of each number !!)
     * @param int $error flag. 0 = message, 1 = error (user problem), 2 = System Error (which should not happen), 3 = security notice (admin)
     * @param int $details_nr The message number. Specific for each $type and $action. in the future this will make it possible to translate errormessages to other languages
     * @param string $details Default text that follows the message
     * @param array $data Data that follows the log. Might be used to carry special information. If an array the first 5 entries (0-4) will be sprintf'ed the details-text...
     * @param string $tablename Special field used by tce_main.php. These ($tablename, $recuid, $recpid) holds the reference to the record which the log-entry is about. (Was used in attic status.php to update the interface.)
     * @param int $recuid Special field used by tce_main.php. These ($tablename, $recuid, $recpid) holds the reference to the record which the log-entry is about. (Was used in attic status.php to update the interface.)
     * @param int $recpid Special field used by tce_main.php. These ($tablename, $recuid, $recpid) holds the reference to the record which the log-entry is about. (Was used in attic status.php to update the interface.)
     * @return void
     */
    public function writelog($type, $action, $error, $details_nr, $details, $data, $tablename, $recuid, $recpid)
    {
    }

    /**
     * DUMMY: Check login failures (in some extension classes)
     *
     * @param string $email Email address
     * @param int $secondsBack Number of sections back in time to check. This is a kind of limit for how many failures an hour for instance
     * @param int $maxFailures Max allowed failures before a warning mail is sent
     * @return void
     * @ignore
     */
    public function checkLogFailures($email, $secondsBack, $maxFailures)
    {
    }

    /**
     * Raw initialization of the be_user with uid=$uid
     * This will circumvent all login procedures and select a be_users record from the
     * database and set the content of ->user to the record selected.
     * Thus the BE_USER object will appear like if a user was authenticated - however without
     * a session id and the fields from the session table of course.
     * Will check the users for disabled, start/endtime, etc. ($this->user_where_clause())
     *
     * @param int $uid The UID of the backend user to set in ->user
     * @return void
     * @internal
     * @see SC_mod_tools_be_user_index::compareUsers(), \TYPO3\CMS\Setup\Controller\SetupModuleController::simulateUser(), freesite_admin::startCreate()
     */
    public function setBeUserByUid($uid)
    {
        $this->user = $this->getRawUserByUid($uid);
    }

    /**
     * Raw initialization of the be_user with username=$name
     *
     * @param string $name The username to look up.
     * @return void
     * @see \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication::setBeUserByUid()
     * @internal
     */
    public function setBeUserByName($name)
    {
        $this->user = $this->getRawUserByName($name);
    }

    /**
     * Fetching raw user record with uid=$uid
     *
     * @param int $uid The UID of the backend user to set in ->user
     * @return array user record or FALSE
     * @internal
     */
    public function getRawUserByUid($uid)
    {
        $user = false;
        $dbres = $this->db->exec_SELECTquery('*', $this->user_table, 'uid=' . (int)$uid . ' ' . $this->user_where_clause());
        if ($dbres) {
            $user = $this->db->sql_fetch_assoc($dbres);
            $this->db->sql_free_result($dbres);
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
     */
    public function getRawUserByName($name)
    {
        $user = false;
        $dbres = $this->db->exec_SELECTquery('*', $this->user_table, 'username=' . $this->db->fullQuoteStr($name, $this->user_table) . ' ' . $this->user_where_clause());
        if ($dbres) {
            $user = $this->db->sql_fetch_assoc($dbres);
            $this->db->sql_free_result($dbres);
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
     */
    public function fetchUserRecord($dbUser, $username, $extraWhere = '')
    {
        $user = false;
        $usernameClause = $username ? $dbUser['username_column'] . '=' . $this->db->fullQuoteStr($username, $dbUser['table']) : '1=1';
        if ($username || $extraWhere) {
            // Look up the user by the username and/or extraWhere:
            $dbres = $this->db->exec_SELECTquery('*', $dbUser['table'], $usernameClause . $dbUser['check_pid_clause'] . $dbUser['enable_clause'] . $extraWhere);
            if ($dbres) {
                $user = $this->db->sql_fetch_assoc($dbres);
                $this->db->sql_free_result($dbres);
            }
        }
        return $user;
    }

    /**
     * Get global database connection
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
