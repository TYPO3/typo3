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

namespace TYPO3\CMS\Frontend\Authentication;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Authentication\GroupResolver;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Session\UserSession;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Extension class for Front End User Authentication.
 */
class FrontendUserAuthentication extends AbstractUserAuthentication
{
    /**
     * Login type, used for services.
     * @var string
     */
    public $loginType = 'FE';

    /**
     * Form field with login-name
     * @var string
     */
    public $formfield_uname = 'user';

    /**
     * Form field with password
     * @var string
     */
    public $formfield_uident = 'pass';

    /**
     * Form field with status: *'login', 'logout'. If empty login is not verified.
     * @var string
     */
    public $formfield_status = 'logintype';

    /**
     * form field with 0 or 1
     * 1 = permanent login enabled
     * 0 = session is valid for a browser session only
     * @var string
     */
    public $formfield_permanent = 'permalogin';

    /**
     * Table in database with user data
     * @var string
     */
    public $user_table = 'fe_users';

    /**
     * Column for login-name
     * @var string
     */
    public $username_column = 'username';

    /**
     * Column for password
     * @var string
     */
    public $userident_column = 'password';

    /**
     * Column for user-id
     * @var string
     */
    public $userid_column = 'uid';

    /**
     * Column name for last login timestamp
     * @var string
     */
    public $lastLogin_column = 'lastlogin';

    /**
     * @var string
     */
    public $usergroup_column = 'usergroup';

    /**
     * @var string
     */
    public $usergroup_table = 'fe_groups';

    /**
     * Enable field columns of user table
     * @var array
     */
    public $enablecolumns = [
        'deleted' => 'deleted',
        'disabled' => 'disable',
        'starttime' => 'starttime',
        'endtime' => 'endtime',
    ];

    /**
     * @var array
     */
    public $groupData = [
        'title' => [],
        'uid' => [],
        'pid' => [],
    ];

    /**
     * Used to accumulate the TSconfig data of the user
     * @var array
     */
    protected $TSdataArray = [];

    /**
     * @var array
     */
    protected $userTS = [];

    /**
     * @var bool
     */
    protected $userData_change = false;

    /**
     * @var bool
     */
    public $is_permanent = false;

    /**
     * @var bool
     */
    protected $loginHidden = false;

    /**
     * Will force the session cookie to be set every time (lifetime must be 0).
     * @var bool
     */
    protected $forceSetCookie = false;

    /**
     * Will prevent the setting of the session cookie (takes precedence over forceSetCookie)
     * Disable cookie by default, will be activated if saveSessionData() is called,
     * a user is logging-in or an existing session is found
     * @var bool
     */
    public $dontSetCookie = true;

    public function __construct()
    {
        $this->name = self::getCookieName();
        parent::__construct();
        $this->checkPid = $GLOBALS['TYPO3_CONF_VARS']['FE']['checkFeUserPid'];
    }

    /**
     * Returns the configured cookie name
     *
     * @return string
     */
    public static function getCookieName()
    {
        $configuredCookieName = trim($GLOBALS['TYPO3_CONF_VARS']['FE']['cookieName']);
        if (empty($configuredCookieName)) {
            $configuredCookieName = 'fe_typo_user';
        }
        return $configuredCookieName;
    }

    /**
     * Determine whether a session cookie needs to be set (lifetime=0)
     *
     * @return bool
     * @internal
     */
    public function isSetSessionCookie()
    {
        return ($this->userSession->isNew() || $this->forceSetCookie)
            && ((int)$this->lifetime === 0 || !$this->userSession->isPermanent());
    }

    /**
     * Determine whether a non-session cookie needs to be set (lifetime>0)
     *
     * @return bool
     * @internal
     */
    public function isRefreshTimeBasedCookie()
    {
        return $this->lifetime > 0 && $this->userSession->isPermanent();
    }

    /**
     * Returns an info array with Login/Logout data submitted by a form or params
     *
     * @return array
     * @see AbstractUserAuthentication::getLoginFormData()
     */
    public function getLoginFormData()
    {
        $loginData = parent::getLoginFormData();
        if ($GLOBALS['TYPO3_CONF_VARS']['FE']['permalogin'] == 0 || $GLOBALS['TYPO3_CONF_VARS']['FE']['permalogin'] == 1) {
            $isPermanent = GeneralUtility::_POST($this->formfield_permanent);
            if (strlen((string)$isPermanent) != 1) {
                $isPermanent = $GLOBALS['TYPO3_CONF_VARS']['FE']['permalogin'];
            } elseif (!$isPermanent) {
                // To make sure the user gets a session cookie and doesn't keep a possibly existing time based cookie,
                // we need to force setting the session cookie here
                $this->forceSetCookie = true;
            }
            $isPermanent = (bool)$isPermanent;
        } elseif ($GLOBALS['TYPO3_CONF_VARS']['FE']['permalogin'] == 2) {
            $isPermanent = true;
        } else {
            $isPermanent = false;
        }
        $loginData['permanent'] = $isPermanent;
        $this->is_permanent = $isPermanent;
        return $loginData;
    }

    /**
     * Creates a user session record and returns its values.
     * However, as the FE user cookie is normally not set, this has to be done
     * before the parent class is doing the rest.
     *
     * @param array $tempuser User data array
     * @return UserSession The session data for the newly created session.
     */
    public function createUserSession(array $tempuser): UserSession
    {
        // At this point we do not know if we need to set a session or a permanent cookie
        // So we force the cookie to be set after authentication took place, which will
        // then call setSessionCookie(), which will set a cookie with correct settings.
        $this->dontSetCookie = false;
        $tempUserId = (int)($tempuser[$this->userid_column] ?? 0);
        $session = $this->userSessionManager->elevateToFixatedUserSession(
            $this->userSession,
            $tempUserId,
            (bool)$this->is_permanent
        );
        // Updating lastLogin_column carrying information about last login.
        $this->updateLoginTimestamp($tempUserId);
        return $session;
    }

    /**
     * Will select all fe_groups records that the current fe_user is member of.
     *
     * It also accumulates the TSconfig for the fe_user/fe_groups in ->TSdataArray
     *
     * @param ServerRequestInterface|null $request (will become a requirement in v12.0)
     */
    public function fetchGroupData(ServerRequestInterface $request = null)
    {
        $this->TSdataArray = [];
        $this->userTS = [];
        $this->userGroups = [];
        $this->groupData = [
            'title' => [],
            'uid' => [],
            'pid' => [],
        ];
        // Setting default configuration:
        $this->TSdataArray[] = $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultUserTSconfig'];

        $groupDataArr = [];
        if (is_array($this->user)) {
            $this->logger->debug('Get usergroups for user', [
                $this->userid_column => $this->user[$this->userid_column],
                $this->username_column => $this->user[$this->username_column],
            ]);
            $groupDataArr = GeneralUtility::makeInstance(GroupResolver::class)->resolveGroupsForUser($this->user, $this->usergroup_table);
        }
        // Fire an event for any kind of user (even when no specific user is here, using hideLogin feature)
        $dispatcher = GeneralUtility::makeInstance(EventDispatcherInterface::class);
        $event = $dispatcher->dispatch(new ModifyResolvedFrontendGroupsEvent($this, $groupDataArr, $request ?? $GLOBALS['TYPO3_REQUEST'] ?? null));
        $groupDataArr = $event->getGroups();

        if (empty($groupDataArr)) {
            $this->logger->debug('No usergroups found');
        } else {
            $this->logger->debug('{count} usergroup records found', ['count' => count($groupDataArr)]);
        }
        foreach ($groupDataArr as $groupData) {
            $groupId = (int)$groupData['uid'];
            $this->groupData['title'][$groupId] = $groupData['title'] ?? '';
            $this->groupData['uid'][$groupId] = $groupData['uid'] ?? 0;
            $this->groupData['pid'][$groupId] = $groupData['pid'] ?? 0;
            $this->TSdataArray[] = $groupData['TSconfig'] ?? '';
            $this->userGroups[$groupId] = $groupData;
        }
        $this->TSdataArray[] = $this->user['TSconfig'] ?? '';
        // Sort information
        ksort($this->groupData['title']);
        ksort($this->groupData['uid']);
        ksort($this->groupData['pid']);
    }

    /**
     * Initializes the front-end user groups for the context API,
     * based on the user groups and the logged-in state.
     *
     * @param bool $respectUserGroups used with the $TSFE->loginAllowedInBranch flag to disable the inclusion of the users' groups
     * @return UserAspect
     */
    public function createUserAspect(bool $respectUserGroups = true): UserAspect
    {
        $userGroups = [0];
        $isUserAndGroupSet = is_array($this->user) && !empty($this->userGroups);
        if ($isUserAndGroupSet) {
            // group -2 is not an existing group, but denotes a 'default' group when a user IS logged in.
            // This is used to let elements be shown for all logged in users!
            $userGroups[] = -2;
            $groupsFromUserRecord = array_keys($this->userGroups);
        } else {
            // group -1 is not an existing group, but denotes a 'default' group when not logged in.
            // This is used to let elements be hidden, when a user is logged in!
            $userGroups[] = -1;
            if ($respectUserGroups) {
                // For cases where logins are not banned from a branch usergroups can be set based on IP masks so we should add the usergroups uids.
                $groupsFromUserRecord = array_keys($this->userGroups);
            } else {
                // Set to blank since we will NOT risk any groups being set when no logins are allowed!
                $groupsFromUserRecord = [];
            }
        }
        // Make unique and sort the groups
        $groupsFromUserRecord = array_unique($groupsFromUserRecord);
        if ($respectUserGroups && !empty($groupsFromUserRecord)) {
            sort($groupsFromUserRecord);
            $userGroups = array_merge($userGroups, $groupsFromUserRecord);
        }

        // For every 60 seconds the is_online timestamp for a logged-in user is updated
        if ($isUserAndGroupSet) {
            $this->updateOnlineTimestamp();
        }

        $this->logger->debug('Valid frontend usergroups: {groups}', ['groups' => implode(',', $userGroups)]);
        return GeneralUtility::makeInstance(UserAspect::class, $this, $userGroups);
    }
    /**
     * Returns the parsed TSconfig for the fe_user
     * The TSconfig will be cached in $this->userTS.
     *
     * @return array TSconfig array for the fe_user
     */
    public function getUserTSconf()
    {
        if ($this->userTS === [] && !empty($this->TSdataArray)) {
            // Parsing the user TS (or getting from cache)
            $this->TSdataArray = TypoScriptParser::checkIncludeLines_array($this->TSdataArray);
            $userTS = implode(LF . '[GLOBAL]' . LF, $this->TSdataArray);
            $parseObj = GeneralUtility::makeInstance(TypoScriptParser::class);
            $parseObj->parse($userTS);
            $this->userTS = $parseObj->setup;
        }
        return $this->userTS ?? [];
    }

    /*****************************************
     *
     * Session data management functions
     *
     ****************************************/
    /**
     * Will write UC and session data.
     * If the flag $this->userData_change has been set, the function ->writeUC is called (which will save persistent user session data)
     *
     * @see getKey()
     * @see setKey()
     */
    public function storeSessionData()
    {
        // Saves UC and SesData if changed.
        if ($this->userData_change) {
            $this->writeUC();
        }

        if ($this->userSession->dataWasUpdated()) {
            if (!$this->userSession->hasData()) {
                // Remove session-data
                $this->removeSessionData();
                // Remove cookie if not logged in as the session data is removed as well
                if (empty($this->user['uid']) && !$this->loginHidden && $this->isCookieSet()) {
                    $this->removeCookie($this->name);
                }
            } elseif (!$this->userSessionManager->isSessionPersisted($this->userSession)) {
                // Create a new session entry in the backend
                $this->userSession = $this->userSessionManager->fixateAnonymousSession($this->userSession, (bool)$this->is_permanent);
                // Now set the cookie (= fix the session)
                $this->setSessionCookie();
            } else {
                // Update session data of an already fixated session
                $this->userSession = $this->userSessionManager->updateSession($this->userSession);
            }
        }
    }

    /**
     * Removes data of the current session.
     */
    public function removeSessionData()
    {
        $this->userSession->overrideData([]);
        if ($this->userSessionManager->isSessionPersisted($this->userSession)) {
            // Remove session record if $this->user is empty is if session is anonymous
            if ((empty($this->user) && !$this->loginHidden) || $this->userSession->isAnonymous()) {
                $this->userSessionManager->removeSession($this->userSession);
            } else {
                $this->userSession = $this->userSessionManager->updateSession($this->userSession);
            }
        }
    }

    /**
     * Regenerate the session ID and transfer the session to new ID
     * Call this method whenever a user proceeds to a higher authorization level
     * e.g. when an anonymous session is now authenticated.
     * Forces cookie to be set
     */
    protected function regenerateSessionId()
    {
        parent::regenerateSessionId();
        // We force the cookie to be set later in the authentication process
        $this->dontSetCookie = false;
    }

    /**
     * Returns session data for the fe_user; Either persistent data following the fe_users uid/profile (requires login)
     * or current-session based (not available when browse is closed, but does not require login)
     *
     * @param string $type Session data type; Either "user" (persistent, bound to fe_users profile) or "ses" (temporary, bound to current session cookie)
     * @param string $key Key from the data array to return; The session data (in either case) is an array ($this->uc / $this->sessionData) and this value determines which key to return the value for.
     * @return mixed Returns whatever value there was in the array for the key, $key
     * @see setKey()
     */
    public function getKey($type, $key)
    {
        if (!$key) {
            return null;
        }
        $value = null;
        switch ($type) {
            case 'user':
                $value = $this->uc[$key] ?? null;
                break;
            case 'ses':
                $value = $this->getSessionData($key);
                break;
        }
        return $value;
    }

    /**
     * Saves session data, either persistent or bound to current session cookie. Please see getKey() for more details.
     * When a value is set the flag $this->userData_change will be set so that the final call to ->storeSessionData() will know if a change has occurred and needs to be saved to the database.
     * Notice: Simply calling this function will not save the data to the database! The actual saving is done in storeSessionData() which is called as some of the last things in \TYPO3\CMS\Frontend\Http\RequestHandler. So if you exit before this point, nothing gets saved of course! And the solution is to call $GLOBALS['TSFE']->storeSessionData(); before you exit.
     *
     * @param string $type Session data type; Either "user" (persistent, bound to fe_users profile) or "ses" (temporary, bound to current session cookie)
     * @param string $key Key from the data array to store incoming data in; The session data (in either case) is an array ($this->uc / $this->sessionData) and this value determines in which key the $data value will be stored.
     * @param mixed $data The data value to store in $key
     * @see setKey()
     * @see storeSessionData()
     */
    public function setKey($type, $key, $data)
    {
        if (!$key) {
            return;
        }
        switch ($type) {
            case 'user':
                if ($this->user['uid'] ?? 0) {
                    if ($data === null) {
                        unset($this->uc[$key]);
                    } else {
                        $this->uc[$key] = $data;
                    }
                    $this->userData_change = true;
                }
                break;
            case 'ses':
                $this->setSessionData($key, $data);
                break;
        }
    }

    /**
     * Saves the tokens so that they can be used by a later incarnation of this class.
     *
     * @param string $key
     * @param mixed $data
     */
    public function setAndSaveSessionData($key, $data)
    {
        $this->setSessionData($key, $data);
        $this->storeSessionData();
    }

    /**
     * Hide the current login
     *
     * This is used by the fe_login_mode feature for pages.
     * A current login is unset, but we remember that there has been one.
     */
    public function hideActiveLogin()
    {
        $this->user = null;
        $this->loginHidden = true;
    }

    /**
     * Update the field "is_online" every 60 seconds of a logged-in user
     *
     * @internal
     */
    public function updateOnlineTimestamp()
    {
        if (!is_array($this->user)
            || !($this->user['uid'] ?? 0)
            || $this->user['uid'] === PHP_INT_MAX // Simulated preview user (flagged with PHP_INT_MAX uid)
            || ($this->user['is_online'] ?? 0) >= $GLOBALS['EXEC_TIME'] - 60) {
            return;
        }
        $dbConnection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->user_table);
        $dbConnection->update(
            $this->user_table,
            ['is_online' => $GLOBALS['EXEC_TIME']],
            ['uid' => (int)$this->user['uid']]
        );
        $this->user['is_online'] = $GLOBALS['EXEC_TIME'];
    }
}
