<?php

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

namespace TYPO3\CMS\Core\Authentication;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\Cookie;
use TYPO3\CMS\Core\Authentication\Event\AfterUserLoggedOutEvent;
use TYPO3\CMS\Core\Authentication\Event\BeforeRequestTokenProcessedEvent;
use TYPO3\CMS\Core\Authentication\Event\BeforeUserLogoutEvent;
use TYPO3\CMS\Core\Authentication\Event\LoginAttemptFailedEvent;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderRegistry;
use TYPO3\CMS\Core\Authentication\Mfa\MfaRequiredException;
use TYPO3\CMS\Core\Compatibility\PublicMethodDeprecationTrait;
use TYPO3\CMS\Core\Compatibility\PublicPropertyDeprecationTrait;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\SecurityAspect;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DefaultRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\PageIdListRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionContainerInterface;
use TYPO3\CMS\Core\Database\Query\Restriction\RootLevelRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Http\CookieHeaderTrait;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\SetCookieBehavior;
use TYPO3\CMS\Core\Http\SetCookieService;
use TYPO3\CMS\Core\Security\RequestToken;
use TYPO3\CMS\Core\Session\UserSession;
use TYPO3\CMS\Core\Session\UserSessionManager;
use TYPO3\CMS\Core\SysLog\Action\Login as SystemLogLoginAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\SysLog\Type as SystemLogType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Authentication of users in TYPO3
 *
 * This class is used to authenticate a login user.
 * The class is used by both the frontend and backend.
 * In both cases this class is a parent class to BackendUserAuthentication and FrontendUserAuthentication
 */
abstract class AbstractUserAuthentication implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    use CookieHeaderTrait;
    use PublicPropertyDeprecationTrait;
    use PublicMethodDeprecationTrait;

    /**
     * List previously publicly accessible variables
     */
    private array $deprecatedPublicProperties = [
        'lastLogin_column' => 'Using $lastLogin_column is marked as internal and will not be possible to access anymore in TYPO3 v13.0. Use AuthenticationServices to read or modify different form field values.',
        'formfield_uname' => 'Using $formfield_uname is marked as internal and will not be possible to access anymore in TYPO3 v13.0. Use AuthenticationServices to read or modify different form field values.',
        'formfield_uident' => 'Using $formfield_uident is marked as internal and will not be possible to access anymore in TYPO3 v13.0. Use AuthenticationServices to read or modify different form field values.',
        'formfield_status' => 'Using $formfield_status is marked as internal and will not be possible to access anymore in TYPO3 v13.0. Use AuthenticationServices to read or modify different form field values.',
        'loginSessionStarted' => 'Using $loginSessionStarted is marked as internal and will not be possible to access anymore in TYPO3 v13.0. Use AuthenticationServices or UserSession to detect if a session has just been started.',
        'dontSetCookie' => 'Using $dontSetCookie is marked as internal and will not be possible to access anymore in TYPO3 v13.0. Use a custom PSR-15 middleware to override custom cookie overrides instead.',
    ];

    /**
     * List previously publicly accessible methods
     */
    private array $deprecatedPublicMethods = [
        'isSetSessionCookie' => 'Using AbstractUserAuthentication->isSetSessionCookie() is marked as internal and cannot be called directly anymore in TYPO3 v13.0.',
        'isRefreshTimeBasedCookie' => 'Using AbstractUserAuthentication->isRefreshTimeBasedCookie() is marked as internal and cannot be called directly anymore in TYPO3 v13.0.',
        'removeCookie' => 'Using AbstractUserAuthentication->removeCookie() is marked as internal and cannot be called directly anymore in TYPO3 v13.0.',
        'isCookieSet' => 'Using AbstractUserAuthentication->isCookieSet() is marked as internal and cannot be called directly anymore in TYPO3 v13.0.',
        'unpack_uc' => 'Using AbstractUserAuthentication->unpack_uc() is marked as internal and cannot be called directly anymore in TYPO3 v13.0.',
        'appendCookieToResponse' => 'Using AbstractUserAuthentication->appendCookieToResponse() is marked as internal and cannot be called directly anymore in TYPO3 v13.0.',
    ];

    /**
     * Session/Cookie name
     * @var string
     */
    public $name = '';

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
     * @internal since TYPO3 v12. This is not considered public API anymore, as this property should be defined in another place in the future.
     */
    protected $lastLogin_column = '';

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
     * Form field with login-name
     * @var string
     * @internal since TYPO3 v12. This is not considered public API anymore, as this property should be defined in another place in the future.
     */
    protected $formfield_uname = '';

    /**
     * Form field with password
     * @var string
     * @internal since TYPO3 v12. This is not considered public API anymore, as this property should be defined in another place in the future.
     */
    protected $formfield_uident = '';

    /**
     * Form field with status: *'login', 'logout'. If empty login is not verified.
     * @var string
     * @internal since TYPO3 v12. This is not considered public API anymore, as this property should be defined in another place in the future.
     */
    protected $formfield_status = '';

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
     * If set, the user-record must be stored at the page defined by $checkPid_value
     * @var bool
     */
    public $checkPid = true;

    /**
     * The page id the user record must be stored at, can also hold a comma separated list of pids
     * @var int|string|null
     */
    public $checkPid_value = 0;

    /**
     * Will be set to TRUE if the login session is actually written during auth-check.
     * @var bool
     * @internal since TYPO3 v12. This is not considered public API anymore, as this property should be defined in another place in the future.
     */
    protected $loginSessionStarted = false;

    /**
     * @var array|null contains user- AND session-data from database (joined tables)
     * @internal
     */
    public $user;

    /**
     * This array will hold the groups that the user is a member of
     */
    public array $userGroups = [];

    /**
     * Will prevent the setting of the session cookie
     * @var bool
     * @internal since TYPO3 v12. This is not considered public API anymore, as this property should be defined in another place in the future.
     */
    protected $dontSetCookie = false;

    /**
     * Login type, used for services.
     * @var string
     */
    public $loginType = '';

    /**
     * User Settings (= preferences)
     */
    public array $uc = [];

    protected ?UserSession $userSession = null;

    protected UserSessionManager $userSessionManager;

    /**
     * If set, this cookie will be set to the response.
     */
    protected SetCookieBehavior $setCookie = SetCookieBehavior::None;

    /**
     * Initialize some important variables
     *
     * @throws Exception
     */
    public function __construct()
    {
        // Backend or frontend login - used for auth services
        if (empty($this->loginType)) {
            throw new Exception('No loginType defined, must be set explicitly by subclass', 1476045345);
        }
    }

    /**
     * Currently needed for various unit tests, until start() and checkAuthentication() methods
     * are smaller and extracted from this class.
     *
     * @internal
     */
    public function initializeUserSessionManager(?UserSessionManager $userSessionManager = null): void
    {
        $this->userSessionManager = $userSessionManager ?? UserSessionManager::create($this->loginType);
        $this->userSession = $this->userSessionManager->createAnonymousSession();
    }

    /**
     * Starts a user session
     * Typical configurations will:
     * a) check if session cookie was set and if not, set one,
     * b) check if a password/username was sent and if so, try to authenticate the user
     * c) Lookup a session attached to a user and check timeout etc.
     * d) Garbage collection, setting of no-cache headers.
     * If a user is authenticated the database record of the user (array) will be set in the ->user internal variable.
     */
    public function start(ServerRequestInterface $request)
    {
        $this->logger->debug('## Beginning of auth logging.');

        // Make certain that NO user is set initially
        $this->user = null;

        if (!isset($this->userSessionManager)) {
            $this->initializeUserSessionManager();
        }
        $this->userSession = $this->userSessionManager->createFromRequestOrAnonymous($request, $this->name);

        // Load user session, check to see if anyone has submitted login-information and if so authenticate
        // the user with the session. $this->user[uid] may be used to write log...
        try {
            $this->checkAuthentication($request);
        } catch (MfaRequiredException $mfaRequiredException) {
            // Ensure the cookie is still set to keep the user session available
            if ($this->shallSetSessionCookie()) {
                $this->setSessionCookie();
            }
            throw $mfaRequiredException;
        }
        if ($this->shallSetSessionCookie()) {
            $this->setSessionCookie();
        }
        // Hook for alternative ways of filling the $this->user array (is used by the "timtaw" extension)
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['postUserLookUp'] ?? [] as $funcName) {
            $_params = [
                'pObj' => $this,
            ];
            GeneralUtility::callUserFunction($funcName, $_params, $this);
        }
    }

    /**
     * Used to apply a cookie to a PSR-7 Response.
     *
     * @todo: should go into a middleware?
     * @internal since TYPO3 v12. This is not considered public API anymore, as this method should be defined in another
     * place in the future. If really needed implement the logic in an AuthenticationService or custom PHP class.
     */
    public function appendCookieToResponse(ResponseInterface $response, ?NormalizedParams $normalizedParams = null): ResponseInterface
    {
        if ($this->setCookie === SetCookieBehavior::None) {
            return $response;
        }
        if ($normalizedParams === null) {
            $normalizedParams = NormalizedParams::createFromRequest($GLOBALS['TYPO3_REQUEST']);
        }
        $setCookieService = SetCookieService::create($this->name, $this->loginType);
        if ($this->setCookie === SetCookieBehavior::Send) {
            $cookieObject = $setCookieService->setSessionCookie($this->userSession, $normalizedParams);
            if ($cookieObject) {
                $response = $response->withAddedHeader('Set-Cookie', $cookieObject->__toString());
            }
        }
        if ($this->setCookie === SetCookieBehavior::Remove) {
            $cookieObject = $setCookieService->removeCookie($normalizedParams);
            $response = $response->withAddedHeader('Set-Cookie', $cookieObject->__toString());
        }
        return $response;
    }

    /**
     * Sets the setCookie directive to "Send", which will then result in appending
     * a new cookie to the PSR-7 response, see appendCookieToResponse().
     * In case this method is called, the cookie needs to be set later.
     */
    protected function setSessionCookie()
    {
        $this->setCookie = SetCookieBehavior::Send;
    }

    /**
     * Determines whether setting the session cookie is generally enabled,
     * or the current session is a non-session cookie (FE permalogin).
     */
    protected function shallSetSessionCookie(): bool
    {
        return !$this->dontSetCookie
            || SetCookieService::create($this->name, $this->loginType)->isRefreshTimeBasedCookie($this->userSession);
    }

    /**
     * Determine whether a session cookie needs to be set (lifetime=0)
     *
     * @return bool
     * @internal since TYPO3 v12. This is not considered public API anymore, if really needed implement the logic in an AuthenticationService or custom PHP class.
     */
    protected function isSetSessionCookie()
    {
        return SetCookieService::create($this->name, $this->loginType)->isSetSessionCookie($this->userSession);
    }

    /**
     * Determine whether a non-session cookie needs to be set (lifetime>0)
     *
     * @return bool
     * @internal since TYPO3 v12. This is not considered public API anymore, if really needed implement the logic in an AuthenticationService or custom PHP class.
     */
    protected function isRefreshTimeBasedCookie()
    {
        return SetCookieService::create($this->name, $this->loginType)->isRefreshTimeBasedCookie($this->userSession);
    }

    /**
     * "auth" services configuration array from $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']
     */
    protected function getAuthServiceConfiguration(): array
    {
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup'] ?? null)) {
            return $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup'];
        }
        return [];
    }

    /**
     * Checks if a submission of username and password is present or use other authentication by auth services
     *
     * @throws MfaRequiredException
     * @internal
     */
    public function checkAuthentication(ServerRequestInterface $request)
    {
        $authConfiguration = $this->getAuthServiceConfiguration();
        if (!empty($authConfiguration)) {
            $this->logger->debug('Authentication Service Configuration found.', ['auth_configuration' => $authConfiguration]);
        }
        $userRecordCandidate = false;
        // User is not authenticated by default
        $authenticated = false;
        // User want to login with passed login data (name/password)
        $activeLogin = false;
        $this->logger->debug('Login type: {type}', ['type' => $this->loginType]);
        // Get Login/Logout data submitted by a form or params
        $loginData = $this->getLoginFormData($request);
        $this->logger->debug('Login data', $this->removeSensitiveLoginDataForLoggingInfo($loginData));
        // Active logout (eg. with "logout" button)
        if ($loginData['status'] === LoginType::LOGOUT) {
            if ($this->writeStdLog) {
                // $type,$action,$error,$details_nr,$details,$data,$tablename,$recuid,$recpid
                $this->writelog(SystemLogType::LOGIN, SystemLogLoginAction::LOGOUT, SystemLogErrorClassification::MESSAGE, 2, 'User %s logged out', [$this->user['username']], '', 0, 0);
            }
            $this->logger->info('User logged out. Id: {session}', ['session' => sha1($this->userSession->getIdentifier())]);
            $this->logoff();
        }
        // Determine whether we need to skip session update.
        // This is used mainly for checking session timeout in advance without refreshing the current session's timeout.
        $skipSessionUpdate = (bool)($request->getQueryParams()['skipSessionUpdate'] ?? false);
        $isExistingSession = false;
        $anonymousSession = false;
        $authenticatedUserFromSession = null;
        if (!$this->userSession->isNew()) {
            // Read user data if this is bound to a user
            // However, if the user data is not valid, or the session has timed out we'll recreate a new anonymous session
            if ($this->userSession->getUserId() > 0) {
                $authenticatedUserFromSession = $this->fetchValidUserFromSessionOrDestroySession($skipSessionUpdate);
            }
            $isExistingSession = !$this->userSession->isNew();
            $anonymousSession = $isExistingSession && $this->userSession->isAnonymous();
        }

        // Active login (eg. with login form).
        if ($loginData['status'] === LoginType::LOGIN) {
            if (!$isExistingSession) {
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
            // Note for further research: $anonymousSession actually implies having $isExistingSession = true
            // allowing to further simplify this concern.
            if ($anonymousSession) {
                $activeLogin = true;
            }
        }

        if ($isExistingSession && $authenticatedUserFromSession !== null) {
            $this->logger->debug('User found in session', [
                $this->userid_column => $authenticatedUserFromSession[$this->userid_column] ?? null,
                $this->username_column => $authenticatedUserFromSession[$this->username_column] ?? null,
            ]);
        } else {
            $this->logger->debug('No user session found');
        }

        if ($activeLogin) {
            $context = GeneralUtility::makeInstance(Context::class);
            $securityAspect = SecurityAspect::provideIn($context);
            $requestToken = $securityAspect->getReceivedRequestToken();

            $event = new BeforeRequestTokenProcessedEvent($this, $request, $requestToken);
            GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch($event);
            $requestToken = $event->getRequestToken();

            $requestTokenScopeMatches = ($requestToken->scope ?? null) === 'core/user-auth/' . strtolower($this->loginType);
            if (!$requestTokenScopeMatches) {
                $this->logger->debug('Missing or invalid request token during login', ['requestToken' => $requestToken]);
                // important: disable `$activeLogin` state
                $activeLogin = false;
            } elseif ($requestToken instanceof RequestToken && $requestToken->getSigningSecretIdentifier() !== null) {
                $securityAspect->getSigningSecretResolver()->revokeIdentifier(
                    $requestToken->getSigningSecretIdentifier()
                );
            }
        }

        // Fetch users from the database (or somewhere else)
        $possibleUsers = $this->fetchPossibleUsers($loginData, $activeLogin, $isExistingSession, $authenticatedUserFromSession, $request);

        // If no new user was set we use the already found user session
        if (empty($possibleUsers) && $isExistingSession && !$anonymousSession) {
            // Check if the previous services returned a proper user
            if (is_array($authenticatedUserFromSession)) {
                $possibleUsers[] = $authenticatedUserFromSession;
                $userRecordCandidate = $authenticatedUserFromSession;
                // User is authenticated because we found a user session
                $authenticated = true;
                $this->logger->debug('User session used', [
                    $this->userid_column => $authenticatedUserFromSession[$this->userid_column] ?? '',
                    $this->username_column => $authenticatedUserFromSession[$this->username_column] ?? '',
                ]);
            }
        }

        // Re-auth user when 'auth'-service option is set
        if (!empty($authConfiguration[$this->loginType . '_alwaysAuthUser'])) {
            $authenticated = false;
            $this->logger->debug('alwaysAuthUser option is enabled');
        }
        // Authenticate the user if needed
        if (!empty($possibleUsers) && !$authenticated) {
            foreach ($possibleUsers as $userRecordCandidate) {
                // Use 'auth' service to authenticate the user
                // If one service returns FALSE then authentication failed
                // a service might return 100 which means there's no reason to stop but the user can't be authenticated by that service
                $this->logger->debug('Auth user', $this->removeSensitiveLoginDataForLoggingInfo($userRecordCandidate, true));
                $subType = 'authUser' . $this->loginType;

                /** @var AuthenticationService $serviceObj */
                foreach ($this->getAuthServices($subType, $loginData, $authenticatedUserFromSession, $request) as $serviceObj) {
                    if (($ret = (int)$serviceObj->authUser($userRecordCandidate)) > 0) {
                        // If the service returns >=200 then no more checking is needed - useful for IP checking without password
                        if ($ret >= 200) {
                            $authenticated = true;
                            break;
                        }
                        if ($ret < 100) {
                            $authenticated = true;
                        }
                        // $ret is between 100 and 199 which means "I'm not responsible, ask others"
                    } else {
                        // $ret is < 0
                        $authenticated = false;
                        break;
                    }
                }

                if ($authenticated) {
                    // Leave foreach() because a user is authenticated
                    break;
                }
            }
            // mimic user authentication to mitigate observable timing discrepancies
            // @link https://cwe.mitre.org/data/definitions/208.html
        } elseif ($activeLogin) {
            $subType = 'authUser' . $this->loginType;
            foreach ($this->getAuthServices($subType, $loginData, $authenticatedUserFromSession, $request) as $serviceObj) {
                if ($serviceObj instanceof MimicServiceInterface && $serviceObj->mimicAuthUser() === false) {
                    break;
                }
            }
        }

        // If user is authenticated, then a valid user is found in $userRecordCandidate
        if ($authenticated) {
            // Insert session record if needed
            if (!$isExistingSession
                || $anonymousSession
                || (int)($userRecordCandidate[$this->userid_column] ?? 0) !== $this->userSession->getUserId()
            ) {
                $sessionData = $this->userSession->getData();
                // Create a new session with a fixated user
                $this->userSession = $this->createUserSession($userRecordCandidate);

                // Preserve session data on login
                if ($anonymousSession || $isExistingSession) {
                    $this->userSession->overrideData($sessionData);
                }

                $this->user = array_merge($userRecordCandidate, $this->user ?? []);

                // The login session is started.
                $this->loginSessionStarted = true;
                $this->logger->debug('User session finally read', [
                    $this->userid_column => $this->user[$this->userid_column],
                    $this->username_column => $this->user[$this->username_column],
                ]);
            } else {
                // if we come here the current session is for sure not anonymous as this is a pre-condition for $authenticated = true
                $this->user = $authenticatedUserFromSession;
            }

            if ($activeLogin && !$this->userSession->isNew()) {
                $this->regenerateSessionId();
            }

            // Since the user is not fully authenticated we need to unpack UC here to be
            // able to retrieve a possible defined default (preferred) MFA provider.
            $this->unpack_uc();

            if ($activeLogin) {
                // User logged in - write that to the log!
                if ($this->writeStdLog) {
                    $this->writelog(SystemLogType::LOGIN, SystemLogLoginAction::LOGIN, SystemLogErrorClassification::MESSAGE, 1, 'User %s logged in from ###IP###', [$userRecordCandidate[$this->username_column]], '', '', '');
                }
                $this->logger->info('User {username} logged in from {ip}', [
                    'username' => $userRecordCandidate[$this->username_column],
                    'ip' => GeneralUtility::getIndpEnv('REMOTE_ADDR'),
                ]);
            } else {
                $this->logger->debug('User {username} authenticated from {ip}', [
                    'username' => $userRecordCandidate[$this->username_column],
                    'ip' => GeneralUtility::getIndpEnv('REMOTE_ADDR'),
                ]);
            }
            // Check if multi-factor authentication is required
            $this->evaluateMfaRequirements();
        } else {
            // Mark the current login attempt as failed
            if (empty($possibleUsers) && $activeLogin) {
                $this->logger->debug('Login failed', [
                    'loginData' => $this->removeSensitiveLoginDataForLoggingInfo($loginData),
                ]);
            } elseif (!empty($possibleUsers)) {
                $this->logger->debug('Login failed', [
                    $this->userid_column => $userRecordCandidate[$this->userid_column],
                    $this->username_column => $userRecordCandidate[$this->username_column],
                ]);
            }

            // If there were a login failure, check to see if a warning email should be sent
            if ($activeLogin) {
                GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch(
                    new LoginAttemptFailedEvent($this, $request, $this->removeSensitiveLoginDataForLoggingInfo($loginData))
                );
                $this->handleLoginFailure();
            }
        }
    }

    /**
     * Loads users from various sources (= authentication services) as an array of arrays.
     *
     * @param array|null $authenticatedUserFromSession if we have a user from an existing session, this is set here, otherwise null
     */
    protected function fetchPossibleUsers(array $loginData, bool $activeLogin, bool $isExistingSession, ?array $authenticatedUserFromSession, ServerRequestInterface $request): array
    {
        $possibleUsers = [];
        $authConfiguration = $this->getAuthServiceConfiguration();
        $alwaysFetchUsers = !empty($authConfiguration[$this->loginType . '_alwaysFetchUser']);
        $fetchUsersIfNoSessionIsGiven = !empty($authConfiguration[$this->loginType . '_fetchUserIfNoSession']);
        if (
            $activeLogin
            || $alwaysFetchUsers
            || (!$isExistingSession && $fetchUsersIfNoSessionIsGiven)
        ) {
            // Use 'auth' service to find the user
            // First found user will be used
            $subType = 'getUser' . $this->loginType;
            /** @var AuthenticationService $serviceObj */
            foreach ($this->getAuthServices($subType, $loginData, $authenticatedUserFromSession, $request) as $serviceObj) {
                $row = $serviceObj->getUser();
                if (is_array($row)) {
                    $possibleUsers[] = $row;
                    $this->logger->debug('User found', [
                        $this->userid_column => $row[$this->userid_column],
                        $this->username_column => $row[$this->username_column],
                    ]);
                    // User found, just stop to search for more if not configured to go on
                    if (empty($authConfiguration[$this->loginType . '_fetchAllUsers'])) {
                        break;
                    }
                }
            }

            if ($alwaysFetchUsers) {
                $this->logger->debug($this->loginType . '_alwaysFetchUser option is enabled');
            }
            if (empty($possibleUsers)) {
                $this->logger->debug('No user found by services');
            } else {
                $this->logger->debug('{count} user records found by services', ['count' => count($possibleUsers)]);
            }
        }
        return $possibleUsers;
    }

    /**
     * This method checks if the user is authenticated but has not succeeded in
     * passing his MFA challenge. This method can therefore only be used if a user
     * has been authenticated against his first authentication method (username+password
     * or any other authentication token).
     *
     * @throws MfaRequiredException
     * @internal
     */
    protected function evaluateMfaRequirements(): void
    {
        // MFA has been validated already, nothing to do
        if ($this->getSessionData('mfa')) {
            return;
        }
        // If the user session does not contain the 'mfa' key - indicating that MFA is already
        // passed - get the first provider for authentication, which is either the default provider
        // or the first active provider (based on the providers configured ordering).
        $provider = GeneralUtility::makeInstance(MfaProviderRegistry::class)->getFirstAuthenticationAwareProvider($this);
        // Throw an exception (hopefully caught in a middleware) when an active provider for the user exists
        if ($provider !== null) {
            throw new MfaRequiredException($provider, 1613687097);
        }
    }

    /**
     * Whether the user is required to set up MFA
     *
     * @internal
     */
    public function isMfaSetupRequired(): bool
    {
        return false;
    }

    /**
     * Implement functionality when there was a failed login
     */
    protected function handleLoginFailure(): void
    {
        if (($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['postLoginFailureProcessing'] ?? []) !== []) {
            trigger_error(
                'The hook $TYPO3_CONF_VARS[\'SC_OPTIONS\'][\'t3lib/class.t3lib_userauth.php\'][\'postLoginFailureProcessing\']'
                . ' will be removed in TYPO3 v13.0. Use the PSR-14 event LoginAttemptFailedEvent.',
                E_USER_DEPRECATED
            );
        }
        $_params = [];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['postLoginFailureProcessing'] ?? [] as $hookIdentifier => $_funcRef) {
            GeneralUtility::callUserFunction($_funcRef, $_params, $this);
        }
    }

    /**
     * Initializes authentication services to be used in a foreach loop
     *
     * @param string $subType e.g. getUserFE
     * @param array|null $authenticatedUserFromSession the user which was loaded from the session, or null if none was found
     * @return \Traversable A generator of service objects
     */
    protected function getAuthServices(string $subType, array $loginData, ?array $authenticatedUserFromSession, ServerRequestInterface $request): \Traversable
    {
        $serviceChain = [];
        // The info array provide additional information for auth services
        $authInfo = $this->getAuthInfoArray($request);
        if ($authenticatedUserFromSession !== null) {
            $authInfo['user'] = $authenticatedUserFromSession;
        }
        while (is_object($serviceObj = GeneralUtility::makeInstanceService('auth', $subType, $serviceChain))) {
            $serviceChain[] = $serviceObj->getServiceKey();
            $serviceObj->initAuth($subType, $loginData, $authInfo, $this);
            yield $serviceObj;
        }
        if (!empty($serviceChain)) {
            $this->logger->debug('{subtype} auth services called: {chain}', [
                'subtype' => $subType,
                'chain' => implode(',', $serviceChain),
            ]);
        }
    }

    /**
     * Regenerate the session ID and transfer the session to new ID
     * Call this method whenever a user proceeds to a higher authorization level
     * e.g. when an anonymous session is now authenticated.
     */
    protected function regenerateSessionId()
    {
        $this->userSession = $this->userSessionManager->regenerateSession($this->userSession->getIdentifier());
    }

    /*************************
     *
     * User Sessions
     *
     *************************/

    /**
     * Creates a user session record and returns its values.
     *
     * @param array $userRecordCandidate User data array
     * @return UserSession The session data for the newly created session.
     */
    public function createUserSession(array $userRecordCandidate): UserSession
    {
        // Needed for testing framework
        if (!isset($this->userSessionManager)) {
            $this->initializeUserSessionManager();
        }
        $userRecordCandidateId = (int)($userRecordCandidate[$this->userid_column] ?? 0);
        $session = $this->userSessionManager->elevateToFixatedUserSession($this->userSession, $userRecordCandidateId);
        // Updating lastLogin_column carrying information about last login.
        $this->updateLoginTimestamp($userRecordCandidateId);
        return $session;
    }

    /**
     * Updates the last login column in the user with the given id
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
            $this->user[$this->lastLogin_column] = $GLOBALS['EXEC_TIME'];
        }
    }

    /**
     * If the session is bound to a user, this method fetches the user record, and returns it.
     * If the session has a timeout, the session date is extended if needed. Also the ìs_online
     * flag is updated for the user.
     *
     * However, if the session has expired the session is removed and the request is treated as an anonymous session.
     *
     * @param bool $skipSessionUpdate
     */
    protected function fetchValidUserFromSessionOrDestroySession(bool $skipSessionUpdate = false): ?array
    {
        if ($this->userSession->isAnonymous()) {
            return null;
        }
        // Fetch the user from the DB
        $userRecord = $this->getRawUserByUid($this->userSession->getUserId() ?? 0);
        if ($userRecord) {
            // A user was found
            $userRecord['is_online'] = $this->userSession->getLastUpdated();
            if (!$this->userSessionManager->hasExpired($this->userSession)) {
                if (!$skipSessionUpdate) {
                    $this->userSession = $this->userSessionManager->updateSessionTimestamp($this->userSession);
                }
            } else {
                // Delete any user set...
                $this->logoff();
                $userRecord = false;
                $this->userSession = $this->userSessionManager->createAnonymousSession();
            }
        }
        return is_array($userRecord) ? $userRecord : null;
    }

    /**
     * Regenerates the session ID and sets the cookie again.
     *
     * @internal
     */
    public function enforceNewSessionId()
    {
        $this->regenerateSessionId();
        $this->setSessionCookie();
    }

    /**
     * Log out current user!
     * Removes the current session record, sets the internal ->user array to a blank string;
     * Thereby the current user (if any) is effectively logged out!
     */
    public function logoff()
    {
        $this->logger->debug('logoff: ses_id = {session}', ['session' => sha1($this->userSession->getIdentifier())]);

        $dispatcher = GeneralUtility::makeInstance(EventDispatcherInterface::class);

        $event = new BeforeUserLogoutEvent($this, $this->userSession);
        $event = $dispatcher->dispatch($event);

        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_pre_processing'] ?? null)) {
            trigger_error(
                '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'t3lib/class.t3lib_userauth.php\'][\'logoff_pre_processing\'] will be removed in TYPO3 v13.0. Use the PSR-14 "BeforeUserLogoutEvent" instead.',
                E_USER_DEPRECATED
            );
        }

        if ($event->shouldLogout()) {
            $_params = [];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_pre_processing'] ?? [] as $_funcRef) {
                if ($_funcRef) {
                    GeneralUtility::callUserFunction($_funcRef, $_params, $this);
                }
            }
            $this->performLogoff();
        }

        $dispatcher->dispatch(new AfterUserLoggedOutEvent($this));

        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_post_processing'] ?? null)) {
            trigger_error(
                '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'t3lib/class.t3lib_userauth.php\'][\'logoff_post_processing\'] will be removed in TYPO3 v13.0. Use the PSR-14 "BeforeUserLogoutEvent" instead.',
                E_USER_DEPRECATED
            );
        }
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
        if ($this->userSession) {
            $this->userSessionManager->removeSession($this->userSession);
        }
        $this->userSession = $this->userSessionManager->createAnonymousSession();
        $this->user = null;
        if ($this->isCookieSet()) {
            $this->removeCookie();
        }
    }

    /**
     * Empty / unset the cookie
     *
     * @param string|null $cookieName usually, this is $this->name
     * @internal since TYPO3 v12. This is not considered public API anymore, if really needed implement the logic in an AuthenticationService or custom PHP class.
     */
    public function removeCookie($cookieName = null)
    {
        $this->setCookie = SetCookieBehavior::Remove;
    }

    /**
     * Returns whether this request is going to set a cookie
     * or a cookie was already found in the system
     *
     * @return bool Returns TRUE if a cookie is set
     * @internal since TYPO3 v12. This is not considered public API anymore, if really needed implement the logic in an AuthenticationService or custom PHP class.
     */
    protected function isCookieSet()
    {
        return SetCookieService::create($this->name, $this->loginType)->isCookieSet(
            $GLOBALS['TYPO3_REQUEST'] ?? null,
            $this->userSession
        );
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

        if ($this->checkPid && $this->checkPid_value !== null && $this->checkPid_value !== '') {
            $restrictionContainer->add(
                GeneralUtility::makeInstance(
                    PageIdListRestriction::class,
                    [$this->user_table],
                    GeneralUtility::intExplode(',', (string)$this->checkPid_value, true)
                )
            );
        }

        return $restrictionContainer;
    }

    /*************************
     *
     * Session and Configuration Handling
     *
     *************************/
    /**
     * This writes $this->>uc to the user-record. This is a way of providing session-data.
     * You can fetch the data again through $this->uc in this class!
     */
    public function writeUC()
    {
        if (is_array($this->user) && $this->user[$this->userid_column]) {
            $this->logger->debug('writeUC: {userid_column}={value}', [
                'userid_column' => $this->userid_column,
                'value' => $this->user[$this->userid_column],
            ]);
            GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->user_table)->update(
                $this->user_table,
                ['uc' => serialize($this->uc)],
                [$this->userid_column => (int)$this->user[$this->userid_column]],
                ['uc' => Connection::PARAM_LOB]
            );
        }
    }

    /**
     * Unserializes the user configuration from the user record into $this->>uc
     * @internal since TYPO3 v12. This is not considered public API anymore, if really needed implement the logic in an AuthenticationService or custom PHP class.
     */
    protected function unpack_uc()
    {
        if (isset($this->user['uc'])) {
            $theUC = unserialize($this->user['uc'], ['allowed_classes' => false]);
            if (is_array($theUC)) {
                $this->uc = $theUC;
            }
        }
    }

    /**
     * Stores data for a module.
     * The data is stored with the session ID, so you can even check upon retrieval
     * if the module data is from a previous session or from the current session.
     *
     * @param string $module Is the identifier of the module, e.g. "web_info"
     * @param mixed $data Is the data you want to store for that module (array, string, ...)
     * @param bool $dontPersistImmediately If set, then the ->uc array (which carries all kinds of user data) is NOT written immediately, but must be written by some subsequent call.
     */
    public function pushModuleData(string $module, mixed $data, bool $dontPersistImmediately = false): void
    {
        $sessionHash = GeneralUtility::hmac(
            $this->userSession->getIdentifier(),
            'core-session-hash'
        );
        $this->uc['moduleData'][$module] = $data;
        $this->uc['moduleSessionID'][$module] = $sessionHash;
        if ($dontPersistImmediately === false) {
            $this->writeUC();
        }
    }

    /**
     * Gets module data for a module (from a loaded ->uc array)
     *
     * @param string $module Is the identifier of the module, e.g. "web_info"
     * @param string $type If $type = 'ses' then module data is returned only if it was stored in the current session, otherwise data from a previous session will be returned (if available).
     * @return mixed The module data if available: $this->uc['moduleData'][$module];
     */
    public function getModuleData(string $module, string $type = ''): mixed
    {
        $sessionHash = GeneralUtility::hmac(
            $this->userSession->getIdentifier(),
            'core-session-hash'
        );
        $sessionData = $this->uc['moduleData'][$module] ?? null;
        $moduleSessionIdHash = $this->uc['moduleSessionID'][$module] ?? null;
        if ($type !== 'ses' || ($sessionData !== null && $moduleSessionIdHash === $sessionHash)) {
            return $sessionData;
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
        return $this->userSession ? $this->userSession->get($key) : '';
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
        $this->userSession->set($key, $data);
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
        $this->userSession->set($key, $data);
        $this->logger->debug('setAndSaveSessionData: ses_id = {session}', ['session' => sha1($this->userSession->getIdentifier())]);
        $this->userSession = $this->userSessionManager->updateSession($this->userSession);
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
    public function getLoginFormData(ServerRequestInterface $request)
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();
        $loginData = [
            'status' => StringUtility::filter($parsedBody[$this->formfield_status] ?? $queryParams[$this->formfield_status] ?? null),
            'uname'  => StringUtility::filter($parsedBody[$this->formfield_uname] ?? '', ''),
            'uident' => StringUtility::filter($parsedBody[$this->formfield_uident] ?? '', ''),
        ];
        // Only process the login data if a login is requested
        if ($loginData['status'] === LoginType::LOGIN) {
            $loginData = $this->processLoginData($loginData, $request);
        }
        return $loginData;
    }

    public function isActiveLogin(ServerRequestInterface $request): bool
    {
        $status = $request->getParsedBody()[$this->formfield_status] ?? $request->getQueryParams()[$this->formfield_status] ?? '';
        return $status === LoginType::LOGIN;
    }

    /**
     * Processes Login data submitted by a form or params
     *
     * @param array $loginData Login data array
     * @return array
     * @internal
     */
    public function processLoginData(array $loginData, ServerRequestInterface $request): array
    {
        $this->logger->debug('Login data before processing', $this->removeSensitiveLoginDataForLoggingInfo($loginData));
        $subType = 'processLoginData' . $this->loginType;
        $isLoginDataProcessed = false;
        $processedLoginData = $loginData;
        /** @var AuthenticationService $serviceObject */
        foreach ($this->getAuthServices($subType, $loginData, null, $request) as $serviceObject) {
            $serviceResult = $serviceObject->processLoginData($processedLoginData, 'normal');
            if (!empty($serviceResult)) {
                $isLoginDataProcessed = true;
                // If the service returns >=200 then no more processing is needed
                if ((int)$serviceResult >= 200) {
                    break;
                }
            }
        }
        if ($isLoginDataProcessed) {
            $loginData = $processedLoginData;
            $this->logger->debug('Processed login data', $this->removeSensitiveLoginDataForLoggingInfo($processedLoginData));
        }
        return $loginData;
    }

    /**
     * Removes any sensitive data from the incoming data (either from loginData, processedLogin data
     * or the user record from the DB).
     *
     * No type hinting is added because it might be possible that the incoming data is of any other type.
     *
     * @param mixed|array $data
     * @param bool $isUserRecord
     * @return mixed
     */
    protected function removeSensitiveLoginDataForLoggingInfo($data, bool $isUserRecord = false)
    {
        if ($isUserRecord && is_array($data)) {
            $fieldNames = ['uid', 'pid', 'tstamp', 'crdate', 'deleted', 'disabled', 'starttime', 'endtime', 'username', 'admin', 'usergroup', 'db_mountpoints', 'file_mountpoints', 'file_permissions', 'workspace_perms', 'lastlogin', 'workspace_id', 'category_perms'];
            $data = array_intersect_key($data, array_combine($fieldNames, $fieldNames));
        }
        if (isset($data['uident'])) {
            $data['uident'] = '********';
        }
        if (isset($data['uident_text'])) {
            $data['uident_text'] = '********';
        }
        if (isset($data['password'])) {
            $data['password'] = '********';
        }
        return $data;
    }

    /**
     * Returns an info array which provides additional information for auth services
     *
     * @return array
     * @internal
     */
    public function getAuthInfoArray(ServerRequestInterface $request)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->user_table);
        $expressionBuilder = $queryBuilder->expr();
        $authInfo = [];
        $authInfo['loginType'] = $this->loginType;
        $authInfo['request'] = $request;
        $authInfo['refInfo'] = parse_url(GeneralUtility::getIndpEnv('HTTP_REFERER'));
        $authInfo['HTTP_HOST'] = GeneralUtility::getIndpEnv('HTTP_HOST');
        $authInfo['REMOTE_ADDR'] = GeneralUtility::getIndpEnv('REMOTE_ADDR');
        $authInfo['REMOTE_HOST'] = GeneralUtility::getIndpEnv('REMOTE_HOST');
        // Can be overridden in localconf by SVCONF:
        $authInfo['db_user']['table'] = $this->user_table;
        $authInfo['db_user']['userid_column'] = $this->userid_column;
        $authInfo['db_user']['username_column'] = $this->username_column;
        $authInfo['db_user']['userident_column'] = $this->userident_column;
        $authInfo['db_user']['enable_clause'] = $this->userConstraints()->buildExpression(
            [$this->user_table => $this->user_table],
            $expressionBuilder
        );
        return $authInfo;
    }

    /**
     * DUMMY: Writes to log database table (in some extension classes)
     *
     * @param int $type denotes which module that has submitted the entry. This is the current list:  1=tce_db; 2=tce_file; 3=system (eg. sys_history save); 4=modules; 254=Personal settings changed; 255=login / out action: 1=login, 2=logout, 3=failed login (+ errorcode 3), 4=failure_warning_email sent
     * @param int $action denotes which specific operation that wrote the entry (eg. 'delete', 'upload', 'update' and so on...). Specific for each $type. Also used to trigger update of the interface. (see the log-module for the meaning of each number !!)
     * @param int $error flag. 0 = message, 1 = error (user problem), 2 = System Error (which should not happen), 3 = security notice (admin)
     * @param int $details_nr The message number. Specific for each $type and $action. in the future this will make it possible to translate error messages to other languages
     * @param string $details Default text that follows the message
     * @param array $data Data that follows the log. Might be used to carry special information. If an array the first 5 entries (0-4) will be sprintf'ed the details-text...
     * @param string $tablename Special field used by tce_main.php. These ($tablename, $recuid, $recpid) holds the reference to the record which the log-entry is about. (Was used in attic status.php to update the interface.)
     * @param int|string $recuid Special field used by tce_main.php. These ($tablename, $recuid, $recpid) holds the reference to the record which the log-entry is about. (Was used in attic status.php to update the interface.)
     * @param int|string $recpid Special field used by tce_main.php. These ($tablename, $recuid, $recpid) holds the reference to the record which the log-entry is about. (Was used in attic status.php to update the interface.)
     */
    public function writelog($type, $action, $error, $details_nr, $details, $data, $tablename, $recuid, $recpid) {}

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
        $this->user = $this->getRawUserByName($name) ?: null;
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
            ->where($query->expr()->eq($this->userid_column, $query->createNamedParameter($uid, Connection::PARAM_INT)));

        return $query->executeQuery()->fetchAssociative();
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
            ->where($query->expr()->eq($this->username_column, $query->createNamedParameter($name)));

        return $query->executeQuery()->fetchAssociative();
    }

    public function getSession(): UserSession
    {
        return $this->userSession;
    }
}
