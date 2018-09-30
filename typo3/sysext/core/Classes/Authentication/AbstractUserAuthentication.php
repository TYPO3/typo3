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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DefaultRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionContainerInterface;
use TYPO3\CMS\Core\Database\Query\Restriction\RootLevelRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Session\Backend\Exception\SessionNotFoundException;
use TYPO3\CMS\Core\Session\Backend\SessionBackendInterface;
use TYPO3\CMS\Core\Session\SessionManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Authentication of users in TYPO3
 *
 * This class is used to authenticate a login user.
 * The class is used by both the frontend and backend.
 * In both cases this class is a parent class to BackendUserAuthentication and FrontendUserAuthentication
 *
 * See Inside TYPO3 for more information about the API of the class and internal variables.
 */
abstract class AbstractUserAuthentication implements LoggerAwareInterface
{
    use LoggerAwareTrait;

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
        'deleted' => '',
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
     * Session timeout (on the server)
     *
     * If >0: session-timeout in seconds.
     * If <=0: Instant logout after login.
     *
     * @var int
     */
    public $sessionTimeout = 0;

    /**
     * Name for a field to fetch the server session timeout from.
     * If not empty this is a field name from the user table where the timeout can be found.
     * @var string
     */
    public $auth_timeout_field = '';

    /**
     * Lifetime for the session-cookie (on the client)
     *
     * If >0: permanent cookie with given lifetime
     * If 0: session-cookie
     * Session-cookie means the browser will remove it when the browser is closed.
     *
     * @var int
     */
    public $lifetime = 0;

    /**
     * GarbageCollection
     * Purge all server session data older than $gc_time seconds.
     * 0 = default to $this->sessionTimeout or use 86400 seconds (1 day) if $this->sessionTimeout == 0
     * @var int
     */
    public $gc_time = 0;

    /**
     * Probability for garbage collection to be run (in percent)
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
     * @var array|null contains user- AND session-data from database (joined tables)
     * @internal
     */
    public $user;

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
     * @var array
     */
    public $uc;

    /**
     * @var SessionBackendInterface
     */
    protected $sessionBackend;

    /**
     * Holds deserialized data from session records.
     * 'Reserved' keys are:
     *   - 'sys': Reserved for TypoScript standard code.
     * @var array
     */
    protected $sessionData = [];

    /**
     * Initialize some important variables
     */
    public function __construct()
    {
        // This function has to stay even if it's empty
        // Implementations of that abstract class might call parent::__construct();
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
     * @throws Exception
     */
    public function start()
    {
        // Backend or frontend login - used for auth services
        if (empty($this->loginType)) {
            throw new Exception('No loginType defined, should be set explicitly by subclass', 1476045345);
        }
        $this->logger->debug('## Beginning of auth logging.');
        // Init vars.
        $mode = '';
        $this->newSessionID = false;
        // $id is set to ses_id if cookie is present. Else set to FALSE, which will start a new session
        $id = $this->getCookie($this->name);
        $this->svConfig = $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth'] ?? [];

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
        if ($mode === 'get' && $this->getFallBack && $this->get_name) {
            $this->get_URL_ID = '&' . $this->get_name . '=' . $id;
        }
        // Make certain that NO user is set initially
        $this->user = null;
        // Set all possible headers that could ensure that the script is not cached on the client-side
        $this->sendHttpHeaders();
        // Load user session, check to see if anyone has submitted login-information and if so authenticate
        // the user with the session. $this->user[uid] may be used to write log...
        $this->checkAuthentication();
        // Setting cookies
        if (!$this->dontSetCookie) {
            $this->setSessionCookie();
        }
        // Hook for alternative ways of filling the $this->user array (is used by the "timtaw" extension)
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['postUserLookUp'] ?? [] as $funcName) {
            $_params = [
                'pObj' => $this,
            ];
            GeneralUtility::callUserFunction($funcName, $_params, $this);
        }
        // Set $this->gc_time if not explicitly specified
        if ($this->gc_time === 0) {
            // Default to 86400 seconds (1 day) if $this->sessionTimeout is 0
            $this->gc_time = $this->sessionTimeout === 0 ? 86400 : $this->sessionTimeout;
        }
        // If we're lucky we'll get to clean up old sessions
        if (rand() % 100 <= $this->gc_probability) {
            $this->gc();
        }
    }

    /**
     * Set all possible headers that could ensure that the script
     * is not cached on the client-side.
     *
     * Only do this if $this->sendNoCacheHeaders is set.
     */
    protected function sendHttpHeaders()
    {
        // skip sending the "no-cache" headers if it's a CLI request or the no-cache headers should not be sent.
        if (!$this->sendNoCacheHeaders || Environment::isCli()) {
            return;
        }
        $httpHeaders = $this->getHttpHeaders();
        foreach ($httpHeaders as $httpHeaderName => $value) {
            header($httpHeaderName . ': ' . $value);
        }
    }

    /**
     * Get the http headers to be sent if an authenticated user is available, in order to disallow
     * browsers to store the response on the client side.
     *
     * @return array
     */
    protected function getHttpHeaders(): array
    {
        $headers = [
            'Expires' => 0,
            'Last-Modified' => gmdate('D, d M Y H:i:s') . ' GMT'
        ];
        $cacheControlHeader = 'no-cache, must-revalidate';
        $pragmaHeader = 'no-cache';
        // Prevent error message in IE when using a https connection
        // see http://forge.typo3.org/issues/24125
        if (strpos(GeneralUtility::getIndpEnv('HTTP_USER_AGENT'), 'MSIE') !== false
            && GeneralUtility::getIndpEnv('TYPO3_SSL')) {
            // Some IEs can not handle no-cache
            // see http://support.microsoft.com/kb/323308/en-us
            $cacheControlHeader = 'must-revalidate';
            // IE needs "Pragma: private" if SSL connection
            $pragmaHeader = 'private';
        }
        $headers['Cache-Control'] = $cacheControlHeader;
        $headers['Pragma'] = $pragmaHeader;
        return $headers;
    }

    /**
     * Sets the session cookie for the current disposal.
     *
     * @throws Exception
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
            // Do not set cookie if cookieSecure is set to "1" (force HTTPS) and no secure channel is used:
            if ((int)$settings['cookieSecure'] !== 1 || GeneralUtility::getIndpEnv('TYPO3_SSL')) {
                setcookie($this->name, $this->id, $cookieExpire, $cookiePath, $cookieDomain, $cookieSecure, true);
                $this->cookieWasSetOnCurrentRequest = true;
            } else {
                throw new Exception('Cookie was not set since HTTPS was forced in $TYPO3_CONF_VARS[SYS][cookieSecure].', 1254325546);
            }
            $this->logger->debug(
                ($isRefreshTimeBasedCookie ? 'Updated Cookie: ' : 'Set Cookie: ')
                . $this->id . ($cookieDomain ? ', ' . $cookieDomain : '')
            );
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
            if ($cookieDomain[0] === '/') {
                $match = [];
                $matchCnt = @preg_match($cookieDomain, GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY'), $match);
                if ($matchCnt === false) {
                    $this->logger->critical('The regular expression for the cookie domain (' . $cookieDomain . ') contains errors. The session is not shared across sub-domains.');
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
        $this->logger->debug('Login type: ' . $this->loginType);
        // The info array provide additional information for auth services
        $authInfo = $this->getAuthInfoArray();
        // Get Login/Logout data submitted by a form or params
        $loginData = $this->getLoginFormData();
        $this->logger->debug('Login data', $loginData);
        // Active logout (eg. with "logout" button)
        if ($loginData['status'] === LoginType::LOGOUT) {
            if ($this->writeStdLog) {
                // $type,$action,$error,$details_nr,$details,$data,$tablename,$recuid,$recpid
                $this->writelog(255, 2, 0, 2, 'User %s logged out', [$this->user['username']], '', 0, 0);
            }
            $this->logger->info('User logged out. Id: ' . $this->id);
            $this->logoff();
        }
        // Determine whether we need to skip session update.
        // This is used mainly for checking session timeout in advance without refreshing the current session's timeout.
        $skipSessionUpdate = (bool)GeneralUtility::_GP('skipSessionUpdate');
        $haveSession = false;
        $anonymousSession = false;
        if (!$this->newSessionID) {
            // Read user session
            $authInfo['userSession'] = $this->fetchUserSession($skipSessionUpdate);
            $haveSession = is_array($authInfo['userSession']);
            if ($haveSession && !empty($authInfo['userSession']['ses_anonymous'])) {
                $anonymousSession = true;
            }
        }

        // Active login (eg. with login form).
        if (!$haveSession && $loginData['status'] === LoginType::LOGIN) {
            $activeLogin = true;
            $this->logger->debug('Active login (eg. with login form)');
            // check referrer for submitted login values
            if ($this->formfield_status && $loginData['uident'] && $loginData['uname']) {
                // Delete old user session if any
                $this->logoff();
            }
            // Refuse login for _CLI users, if not processing a CLI request type
            // (although we shouldn't be here in case of a CLI request type)
            if (stripos($loginData['uname'], '_CLI_') === 0 && !Environment::isCli()) {
                throw new \RuntimeException('TYPO3 Fatal Error: You have tried to login using a CLI user. Access prohibited!', 1270853931);
            }
        }

        // Cause elevation of privilege, make sure regenerateSessionId is called later on
        if ($anonymousSession && $loginData['status'] === LoginType::LOGIN) {
            $activeLogin = true;
        }

        if ($haveSession) {
            $this->logger->debug('User session found', [
                $this->userid_column => $authInfo['userSession'][$this->userid_column] ?? null,
                $this->username_column => $authInfo['userSession'][$this->username_column] ?? null,
            ]);
        } else {
            $this->logger->debug('No user session found');
        }
        if (is_array($this->svConfig['setup'] ?? false)) {
            $this->logger->debug('SV setup', $this->svConfig['setup']);
        }

        // Fetch user if ...
        if (
            $activeLogin || !empty($this->svConfig['setup'][$this->loginType . '_alwaysFetchUser'])
            || !$haveSession && !empty($this->svConfig['setup'][$this->loginType . '_fetchUserIfNoSession'])
        ) {
            // Use 'auth' service to find the user
            // First found user will be used
            $subType = 'getUser' . $this->loginType;
            foreach ($this->getAuthServices($subType, $loginData, $authInfo) as $serviceObj) {
                if ($row = $serviceObj->getUser()) {
                    $tempuserArr[] = $row;
                    $this->logger->debug('User found', [
                        $this->userid_column => $row[$this->userid_column],
                        $this->username_column => $row[$this->username_column],
                    ]);
                    // User found, just stop to search for more if not configured to go on
                    if (empty($this->svConfig['setup'][$this->loginType . '_fetchAllUsers'])) {
                        break;
                    }
                }
            }

            if (!empty($this->svConfig['setup'][$this->loginType . '_alwaysFetchUser'])) {
                $this->logger->debug($this->loginType . '_alwaysFetchUser option is enabled');
            }
            if (empty($tempuserArr)) {
                $this->logger->debug('No user found by services');
            } else {
                $this->logger->debug(count($tempuserArr) . ' user records found by services');
            }
        }

        // If no new user was set we use the already found user session
        if (empty($tempuserArr) && $haveSession && !$anonymousSession) {
            $tempuserArr[] = $authInfo['userSession'];
            $tempuser = $authInfo['userSession'];
            // User is authenticated because we found a user session
            $authenticated = true;
            $this->logger->debug('User session used', [
                $this->userid_column => $authInfo['userSession'][$this->userid_column],
                $this->username_column => $authInfo['userSession'][$this->username_column],
            ]);
        }
        // Re-auth user when 'auth'-service option is set
        if (!empty($this->svConfig['setup'][$this->loginType . '_alwaysAuthUser'])) {
            $authenticated = false;
            $this->logger->debug('alwaysAuthUser option is enabled');
        }
        // Authenticate the user if needed
        if (!empty($tempuserArr) && !$authenticated) {
            foreach ($tempuserArr as $tempuser) {
                // Use 'auth' service to authenticate the user
                // If one service returns FALSE then authentication failed
                // a service might return 100 which means there's no reason to stop but the user can't be authenticated by that service
                $this->logger->debug('Auth user', $tempuser);
                $subType = 'authUser' . $this->loginType;

                foreach ($this->getAuthServices($subType, $loginData, $authInfo) as $serviceObj) {
                    if (($ret = $serviceObj->authUser($tempuser)) > 0) {
                        // If the service returns >=200 then no more checking is needed - useful for IP checking without password
                        if ((int)$ret >= 200) {
                            $authenticated = true;
                            break;
                        }
                        if ((int)$ret >= 100) {
                        } else {
                            $authenticated = true;
                        }
                    } else {
                        $authenticated = false;
                        break;
                    }
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
            if (!$haveSession || $anonymousSession || $tempuser['ses_id'] != $this->id && $tempuser['uid'] != $authInfo['userSession']['ses_userid']) {
                $sessionData = $this->createUserSession($tempuser);

                // Preserve session data on login
                if ($anonymousSession) {
                    $sessionData = $this->getSessionBackend()->update(
                        $this->id,
                        ['ses_data' => $authInfo['userSession']['ses_data']]
                    );
                }

                $this->user = array_merge(
                    $tempuser,
                    $sessionData
                );
                // The login session is started.
                $this->loginSessionStarted = true;
                if (is_array($this->user)) {
                    $this->logger->debug('User session finally read', [
                        $this->userid_column => $this->user[$this->userid_column],
                        $this->username_column => $this->user[$this->username_column],
                    ]);
                }
            } elseif ($haveSession) {
                // if we come here the current session is for sure not anonymous as this is a pre-condition for $authenticated = true
                $this->user = $authInfo['userSession'];
            }

            if ($activeLogin && !$this->newSessionID) {
                $this->regenerateSessionId();
            }

            // User logged in - write that to the log!
            if ($this->writeStdLog && $activeLogin) {
                $this->writelog(255, 1, 0, 1, 'User %s logged in from ###IP###', [$tempuser[$this->username_column]], '', '', '');
            }
            if ($activeLogin) {
                $this->logger->info('User ' . $tempuser[$this->username_column] . ' logged in from ' . GeneralUtility::getIndpEnv('REMOTE_ADDR'));
            }
            if (!$activeLogin) {
                $this->logger->debug('User ' . $tempuser[$this->username_column] . ' authenticated from ' . GeneralUtility::getIndpEnv('REMOTE_ADDR'));
            }
        } else {
            // User was not authenticated, so we should reuse the existing anonymous session
            if ($anonymousSession) {
                $this->user = $authInfo['userSession'];
            }

            // Mark the current login attempt as failed
            if ($activeLogin || !empty($tempuserArr)) {
                $this->loginFailure = true;
                if (empty($tempuserArr) && $activeLogin) {
                    $logData = [
                        'loginData' => $loginData
                    ];
                    $this->logger->warning('Login failed', $logData);
                }
                if (!empty($tempuserArr)) {
                    $logData = [
                        $this->userid_column => $tempuser[$this->userid_column],
                        $this->username_column => $tempuser[$this->username_column],
                    ];
                    $this->logger->warning('Login failed', $logData);
                }
            }
        }

        // If there were a login failure, check to see if a warning email should be sent:
        if ($this->loginFailure && $activeLogin) {
            $this->logger->debug(
                'Call checkLogFailures',
                [
                    'warningEmail' => $this->warningEmail,
                    'warningPeriod' => $this->warningPeriod,
                    'warningMax' => $this->warningMax
                ]
            );

            // Hook to implement login failure tracking methods
            $_params = [];
            $sleep = true;
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['postLoginFailureProcessing'] ?? [] as $_funcRef) {
                GeneralUtility::callUserFunction($_funcRef, $_params, $this);
                $sleep = false;
            }

            if ($sleep) {
                // No hooks were triggered - default login failure behavior is to sleep 5 seconds
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
        return GeneralUtility::makeInstance(Random::class)->generateRandomHexString($this->hash_length);
    }

    /**
     * Initializes authentication services to be used in a foreach loop
     *
     * @param string $subType e.g. getUserFE
     * @param array $loginData
     * @param array $authInfo
     * @return \Traversable A generator of service objects
     */
    protected function getAuthServices(string $subType, array $loginData, array $authInfo): \Traversable
    {
        $serviceChain = '';
        while (is_object($serviceObj = GeneralUtility::makeInstanceService('auth', $subType, $serviceChain))) {
            $serviceChain .= ',' . $serviceObj->getServiceKey();
            $serviceObj->initAuth($subType, $loginData, $authInfo, $this);
            yield $serviceObj;
        }
        if ($serviceChain) {
            $this->logger->debug($subType . ' auth services called: ' . $serviceChain);
        }
    }

    /**
     * Regenerate the session ID and transfer the session to new ID
     * Call this method whenever a user proceeds to a higher authorization level
     * e.g. when an anonymous session is now authenticated.
     *
     * @param array $existingSessionRecord If given, this session record will be used instead of fetching again
     * @param bool $anonymous If true session will be regenerated as anonymous session
     */
    protected function regenerateSessionId(array $existingSessionRecord = [], bool $anonymous = false)
    {
        if (empty($existingSessionRecord)) {
            $existingSessionRecord = $this->getSessionBackend()->get($this->id);
        }

        // Update session record with new ID
        $oldSessionId = $this->id;
        $this->id = $this->createSessionId();
        $updatedSession = $this->getSessionBackend()->set($this->id, $existingSessionRecord);
        $this->sessionData = unserialize($updatedSession['ses_data']);
        // Merge new session data into user/session array
        $this->user = array_merge($this->user ?? [], $updatedSession);
        $this->getSessionBackend()->remove($oldSessionId);
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
        $this->logger->debug('Create session ses_id = ' . $this->id);
        // Delete any session entry first
        $this->getSessionBackend()->remove($this->id);
        // Re-create session entry
        $sessionRecord = $this->getNewSessionRecord($tempuser);
        $sessionRecord = $this->getSessionBackend()->set($this->id, $sessionRecord);
        // Updating lastLogin_column carrying information about last login.
        $this->updateLoginTimestamp($tempuser[$this->userid_column]);
        return $sessionRecord;
    }

    /**
     * Updates the last login column in the user with the given id
     *
     * @param int $userId
     */
    protected function updateLoginTimestamp(int $userId)
    {
        if ($this->lastLogin_column) {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->user_table);
            $connection->update(
                $this->user_table,
                [$this->lastLogin_column => $GLOBALS['EXEC_TIME']],
                [$this->userid_column => $userId]
            );
        }
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
            'ses_iplock' => $sessionIpLock,
            'ses_userid' => $tempuser[$this->userid_column] ?? 0,
            'ses_tstamp' => $GLOBALS['EXEC_TIME'],
            'ses_data' => '',
        ];
    }

    /**
     * Read the user session from db.
     *
     * @param bool $skipSessionUpdate
     * @return array|bool User session data, false if $this->id does not represent valid session
     */
    public function fetchUserSession($skipSessionUpdate = false)
    {
        $this->logger->debug('Fetch session ses_id = ' . $this->id);
        try {
            $sessionRecord = $this->getSessionBackend()->get($this->id);
        } catch (SessionNotFoundException $e) {
            return false;
        }

        $this->sessionData = unserialize($sessionRecord['ses_data']);
        // Session is anonymous so no need to fetch user
        if (!empty($sessionRecord['ses_anonymous'])) {
            return $sessionRecord;
        }

        // Fetch the user from the DB
        $userRecord = $this->getRawUserByUid((int)$sessionRecord['ses_userid']);
        if ($userRecord) {
            $userRecord = array_merge($sessionRecord, $userRecord);
            // A user was found
            $userRecord['ses_tstamp'] = (int)$userRecord['ses_tstamp'];
            $userRecord['is_online'] = (int)$userRecord['ses_tstamp'];

            if (!empty($this->auth_timeout_field)) {
                // Get timeout-time from usertable
                $timeout = (int)$userRecord[$this->auth_timeout_field];
            } else {
                $timeout = $this->sessionTimeout;
            }
            // If timeout > 0 (TRUE) and current time has not exceeded the latest sessions-time plus the timeout in seconds then accept user
            // Use a gracetime-value to avoid updating a session-record too often
            if ($timeout > 0 && $GLOBALS['EXEC_TIME'] < $userRecord['ses_tstamp'] + $timeout) {
                $sessionUpdateGracePeriod = 61;
                if (!$skipSessionUpdate && $GLOBALS['EXEC_TIME'] > ($userRecord['ses_tstamp'] + $sessionUpdateGracePeriod)) {
                    // Update the session timestamp by writing a dummy update. (Backend will update the timestamp)
                    $updatesSession = $this->getSessionBackend()->update($this->id, []);
                    $userRecord = array_merge($userRecord, $updatesSession);
                }
            } else {
                // Delete any user set...
                $this->logoff();
                $userRecord = false;
            }
        }
        return $userRecord;
    }

    /**
     * Log out current user!
     * Removes the current session record, sets the internal ->user array to a blank string;
     * Thereby the current user (if any) is effectively logged out!
     */
    public function logoff()
    {
        $this->logger->debug('logoff: ses_id = ' . $this->id);

        $_params = [];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_pre_processing'] ?? [] as $_funcRef) {
            if ($_funcRef) {
                GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
        }
        $this->performLogoff();

        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_post_processing'] ?? [] as $_funcRef) {
            if ($_funcRef) {
                GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
        }
    }

    /**
     * Perform the logoff action. Called from logoff() as a way to allow subclasses to override
     * what happens when a user logs off, without needing to reproduce the hook calls and logging
     * that happens in the public logoff() API method.
     */
    protected function performLogoff()
    {
        if ($this->id) {
            $this->getSessionBackend()->remove($this->id);
        }
        $this->user = null;
    }

    /**
     * Empty / unset the cookie
     *
     * @param string $cookieName usually, this is $this->name
     */
    public function removeCookie($cookieName)
    {
        $cookieDomain = $this->getCookieDomain();
        // If no cookie domain is set, use the base path
        $cookiePath = $cookieDomain ? '/' : GeneralUtility::getIndpEnv('TYPO3_SITE_PATH');
        setcookie($cookieName, null, -1, $cookiePath, $cookieDomain);
    }

    /**
     * Determine whether there's an according session record to a given session_id.
     * Don't care if session record is still valid or not.
     *
     * @param string $id Claimed Session ID
     * @return bool Returns TRUE if a corresponding session was found in the database
     */
    public function isExistingSessionRecord($id)
    {
        try {
            $sessionRecord = $this->getSessionBackend()->get($id);
            if (empty($sessionRecord)) {
                return false;
            }
            // If the session does not match the current IP lock, it should be treated as invalid
            // and a new session should be created.
            if ($sessionRecord['ses_iplock'] !== $this->ipLockClause_remoteIPNumber($this->lockIP) && $sessionRecord['ses_iplock'] !== '[DISABLED]') {
                return false;
            }
            return true;
        } catch (SessionNotFoundException $e) {
            return false;
        }
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
     * This returns the restrictions needed to select the user respecting
     * enable columns and flags like deleted, hidden, starttime, endtime
     * and rootLevel
     *
     * @return QueryRestrictionContainerInterface
     * @internal
     */
    protected function userConstraints(): QueryRestrictionContainerInterface
    {
        $restrictionContainer = GeneralUtility::makeInstance(DefaultRestrictionContainer::class);

        if (empty($this->enablecolumns['disabled'])) {
            $restrictionContainer->removeByType(HiddenRestriction::class);
        }

        if (empty($this->enablecolumns['deleted'])) {
            $restrictionContainer->removeByType(DeletedRestriction::class);
        }

        if (empty($this->enablecolumns['starttime'])) {
            $restrictionContainer->removeByType(StartTimeRestriction::class);
        }

        if (empty($this->enablecolumns['endtime'])) {
            $restrictionContainer->removeByType(EndTimeRestriction::class);
        }

        if (!empty($this->enablecolumns['rootLevel'])) {
            $restrictionContainer->add(GeneralUtility::makeInstance(RootLevelRestriction::class, [$this->user_table]));
        }

        return $restrictionContainer;
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
        }
        $parts = MathUtility::forceIntegerInRange($parts, 1, 3);
        $IPparts = explode('.', $IP);
        for ($a = 4; $a > $parts; $a--) {
            unset($IPparts[$a - 1]);
        }
        return implode('.', $IPparts);
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
     * @param array|string $variable An array you want to store for the user as session data. If $variable is not supplied (is null), the internal variable, ->uc, is stored by default
     */
    public function writeUC($variable = '')
    {
        if (is_array($this->user) && $this->user[$this->userid_column]) {
            if (!is_array($variable)) {
                $variable = $this->uc;
            }
            $this->logger->debug('writeUC: ' . $this->userid_column . '=' . (int)$this->user[$this->userid_column]);
            GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->user_table)->update(
                $this->user_table,
                ['uc' => serialize($variable)],
                [$this->userid_column => (int)$this->user[$this->userid_column]],
                ['uc' => Connection::PARAM_LOB]
            );
        }
    }

    /**
     * Sets $theUC as the internal variable ->uc IF $theUC is an array.
     * If $theUC is FALSE, the 'uc' content from the ->user array will be unserialized and restored in ->uc
     *
     * @param mixed $theUC If an array, then set as ->uc, otherwise load from user record
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
        if ($type !== 'ses' || (isset($this->uc['moduleSessionID'][$module]) && $this->uc['moduleSessionID'][$module] == $this->id)) {
            return $this->uc['moduleData'][$module];
        }
        return null;
    }

    /**
     * Returns the session data stored for $key.
     * The data will last only for this login session since it is stored in the user session.
     *
     * @param string $key The key associated with the session data
     * @return mixed
     */
    public function getSessionData($key)
    {
        return $this->sessionData[$key] ?? null;
    }

    /**
     * Set session data by key.
     * The data will last only for this login session since it is stored in the user session.
     *
     * @param string $key A non empty string to store the data under
     * @param mixed $data Data store store in session
     */
    public function setSessionData($key, $data)
    {
        if (empty($key)) {
            throw new \InvalidArgumentException('Argument key must not be empty', 1484311516);
        }
        $this->sessionData[$key] = $data;
    }

    /**
     * Sets the session data ($data) for $key and writes all session data (from ->user['ses_data']) to the database.
     * The data will last only for this login session since it is stored in the session table.
     *
     * @param string $key Pointer to an associative key in the session data array which is stored serialized in the field "ses_data" of the session table.
     * @param mixed $data The data to store in index $key
     */
    public function setAndSaveSessionData($key, $data)
    {
        $this->sessionData[$key] = $data;
        $this->user['ses_data'] = serialize($this->sessionData);
        $this->logger->debug('setAndSaveSessionData: ses_id = ' . $this->id);
        $updatedSession = $this->getSessionBackend()->update(
            $this->id,
            ['ses_data' => $this->user['ses_data']]
        );
        $this->user = array_merge($this->user ?? [], $updatedSession);
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
        if ($loginData['status'] === LoginType::LOGIN) {
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
        $this->logger->debug('Login data before processing', $loginData);
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
            $this->logger->debug('Processed login data', $processedLoginData);
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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->user_table);
        $expressionBuilder = $queryBuilder->expr();
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
        $authInfo['db_user']['enable_clause'] = $this->userConstraints()->buildExpression(
            [$this->user_table => $this->user_table],
            $expressionBuilder
        );
        if ($this->checkPid && $this->checkPid_value !== null) {
            $authInfo['db_user']['checkPidList'] = $this->checkPid_value;
            $authInfo['db_user']['check_pid_clause'] = $expressionBuilder->in(
                'pid',
                GeneralUtility::intExplode(',', $this->checkPid_value)
            );
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
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    public function compareUident($user, $loginData, $passwordCompareStrategy = '')
    {
        trigger_error('This method will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        return (string)$loginData['uident_text'] !== '' && (string)$loginData['uident_text'] === (string)$user[$this->userident_column];
    }

    /**
     * Garbage collector, removing old expired sessions.
     *
     * @internal
     */
    public function gc()
    {
        $this->getSessionBackend()->collectGarbage($this->gc_time);
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
     * @internal
     */
    public function setBeUserByUid($uid)
    {
        $this->user = $this->getRawUserByUid($uid);
    }

    /**
     * Raw initialization of the be_user with username=$name
     *
     * @param string $name The username to look up.
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
        $query = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->user_table);
        $query->setRestrictions($this->userConstraints());
        $query->select('*')
            ->from($this->user_table)
            ->where($query->expr()->eq('uid', $query->createNamedParameter($uid, \PDO::PARAM_INT)));

        return $query->execute()->fetch();
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
        $query = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->user_table);
        $query->setRestrictions($this->userConstraints());
        $query->select('*')
            ->from($this->user_table)
            ->where($query->expr()->eq('username', $query->createNamedParameter($name, \PDO::PARAM_STR)));

        return $query->execute()->fetch();
    }

    /**
     * Get a user from DB by username
     * provided for usage from services
     *
     * @param array $dbUser User db table definition: $this->db_user
     * @param string $username user name
     * @param string $extraWhere Additional WHERE clause: " AND ...
     * @return mixed User array or FALSE
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function fetchUserRecord($dbUser, $username, $extraWhere = '')
    {
        trigger_error('This method will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        $user = false;
        if ($username || $extraWhere) {
            $query = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($dbUser['table']);
            $query->getRestrictions()->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

            $constraints = array_filter([
                QueryHelper::stripLogicalOperatorPrefix($dbUser['check_pid_clause']),
                QueryHelper::stripLogicalOperatorPrefix($dbUser['enable_clause']),
                QueryHelper::stripLogicalOperatorPrefix($extraWhere),
            ]);

            if (!empty($username)) {
                array_unshift(
                    $constraints,
                    $query->expr()->eq(
                        $dbUser['username_column'],
                        $query->createNamedParameter($username, \PDO::PARAM_STR)
                    )
                );
            }

            $user = $query->select('*')
                ->from($dbUser['table'])
                ->where(...$constraints)
                ->execute()
                ->fetch();
        }
        return $user;
    }

    /**
     * @internal
     * @return string
     */
    public function getSessionId(): string
    {
        return $this->id;
    }

    /**
     * @internal
     * @return string
     */
    public function getLoginType(): string
    {
        return $this->loginType;
    }

    /**
     * Returns initialized session backend. Returns same session backend if called multiple times
     *
     * @return SessionBackendInterface
     */
    protected function getSessionBackend()
    {
        if (!isset($this->sessionBackend)) {
            $this->sessionBackend = GeneralUtility::makeInstance(SessionManager::class)->getSessionBackend($this->loginType);
        }
        return $this->sessionBackend;
    }
}
