<?php
namespace TYPO3\CMS\Frontend\Authentication;

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

use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Session\Backend\Exception\SessionNotFoundException;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Extension class for Front End User Authentication.
 */
class FrontendUserAuthentication extends AbstractUserAuthentication
{
    /**
     * form field with 0 or 1
     * 1 = permanent login enabled
     * 0 = session is valid for a browser session only
     * @var string
     */
    public $formfield_permanent = 'permalogin';

    /**
     * Lifetime of anonymous session data in seconds.
     * @var int
     */
    protected $sessionDataLifetime = 86400;

    /**
     * Session timeout (on the server)
     *
     * If >0: session-timeout in seconds.
     * If <=0: Instant logout after login.
     *
     * @var int
     */
    public $sessionTimeout = 6000;

    /**
     * @var string
     */
    public $usergroup_column = 'usergroup';

    /**
     * @var string
     */
    public $usergroup_table = 'fe_groups';

    /**
     * @var array
     */
    public $groupData = [
        'title' => [],
        'uid' => [],
        'pid' => []
    ];

    /**
     * Used to accumulate the TSconfig data of the user
     * @var array
     */
    public $TSdataArray = [];

    /**
     * @var array
     */
    public $userTS = [];

    /**
     * @var bool
     */
    public $userTSUpdated = false;

    /**
     * @var bool
     */
    public $sesData_change = false;

    /**
     * @var bool
     */
    public $userData_change = false;

    /**
     * @var bool
     */
    public $is_permanent = false;

    /**
     * @var bool
     */
    protected $loginHidden = false;

    /**
     * Default constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // Disable cookie by default, will be activated if saveSessionData() is called,
        // a user is logging-in or an existing session is found
        $this->dontSetCookie = true;

        $this->name = self::getCookieName();
        $this->get_name = 'ftu';
        $this->loginType = 'FE';
        $this->user_table = 'fe_users';
        $this->username_column = 'username';
        $this->userident_column = 'password';
        $this->userid_column = 'uid';
        $this->lastLogin_column = 'lastlogin';
        $this->enablecolumns = [
            'deleted' => 'deleted',
            'disabled' => 'disable',
            'starttime' => 'starttime',
            'endtime' => 'endtime'
        ];
        $this->formfield_uname = 'user';
        $this->formfield_uident = 'pass';
        $this->formfield_status = 'logintype';
        $this->sendNoCacheHeaders = false;
        $this->getFallBack = true;
        $this->getMethodEnabled = true;
        $this->lockIP = $GLOBALS['TYPO3_CONF_VARS']['FE']['lockIP'];
        $this->checkPid = $GLOBALS['TYPO3_CONF_VARS']['FE']['checkFeUserPid'];
        $this->lifetime = (int)$GLOBALS['TYPO3_CONF_VARS']['FE']['lifetime'];
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
     * Starts a user session
     *
     * @see AbstractUserAuthentication::start()
     */
    public function start()
    {
        if ((int)$this->sessionTimeout > 0 && $this->sessionTimeout < $this->lifetime) {
            // If server session timeout is non-zero but less than client session timeout: Copy this value instead.
            $this->sessionTimeout = $this->lifetime;
        }
        $this->sessionDataLifetime = (int)$GLOBALS['TYPO3_CONF_VARS']['FE']['sessionDataLifetime'];
        if ($this->sessionDataLifetime <= 0) {
            $this->sessionDataLifetime = 86400;
        }
        parent::start();
    }

    /**
     * Returns a new session record for the current user for insertion into the DB.
     *
     * @param array $tempuser
     * @return array User session record
     */
    public function getNewSessionRecord($tempuser)
    {
        $insertFields = parent::getNewSessionRecord($tempuser);
        $insertFields['ses_permanent'] = $this->is_permanent ? 1 : 0;
        return $insertFields;
    }

    /**
     * Determine whether a session cookie needs to be set (lifetime=0)
     *
     * @return bool
     * @internal
     */
    public function isSetSessionCookie()
    {
        return ($this->newSessionID || $this->forceSetCookie)
            && ((int)$this->lifetime === 0 || !isset($this->user['ses_permanent']) || !$this->user['ses_permanent']);
    }

    /**
     * Determine whether a non-session cookie needs to be set (lifetime>0)
     *
     * @return bool
     * @internal
     */
    public function isRefreshTimeBasedCookie()
    {
        return $this->lifetime > 0 && isset($this->user['ses_permanent']) && $this->user['ses_permanent'];
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
            if ($this->getMethodEnabled) {
                $isPermanent = GeneralUtility::_GP($this->formfield_permanent);
            } else {
                $isPermanent = GeneralUtility::_POST($this->formfield_permanent);
            }
            if (strlen($isPermanent) != 1) {
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
     * @return array The session data for the newly created session.
     */
    public function createUserSession($tempuser)
    {
        // At this point we do not know if we need to set a session or a permanent cookie
        // So we force the cookie to be set after authentication took place, which will
        // then call setSessionCookie(), which will set a cookie with correct settings.
        $this->dontSetCookie = false;
        return parent::createUserSession($tempuser);
    }

    /**
     * Will select all fe_groups records that the current fe_user is member of
     * and which groups are also allowed in the current domain.
     * It also accumulates the TSconfig for the fe_user/fe_groups in ->TSdataArray
     *
     * @return int Returns the number of usergroups for the frontend users (if the internal user record exists and the usergroup field contains a value)
     */
    public function fetchGroupData()
    {
        $this->TSdataArray = [];
        $this->userTS = [];
        $this->userTSUpdated = false;
        $this->groupData = [
            'title' => [],
            'uid' => [],
            'pid' => []
        ];
        // Setting default configuration:
        $this->TSdataArray[] = $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultUserTSconfig'];
        // Get the info data for auth services
        $authInfo = $this->getAuthInfoArray();
        if ($this->writeDevLog) {
            if (is_array($this->user)) {
                GeneralUtility::devLog('Get usergroups for user: ' . GeneralUtility::arrayToLogString($this->user, [$this->userid_column, $this->username_column]), __CLASS__);
            } else {
                GeneralUtility::devLog('Get usergroups for "anonymous" user', __CLASS__);
            }
        }
        $groupDataArr = [];
        // Use 'auth' service to find the groups for the user
        $serviceChain = '';
        $subType = 'getGroups' . $this->loginType;
        while (is_object($serviceObj = GeneralUtility::makeInstanceService('auth', $subType, $serviceChain))) {
            $serviceChain .= ',' . $serviceObj->getServiceKey();
            $serviceObj->initAuth($subType, [], $authInfo, $this);
            $groupData = $serviceObj->getGroups($this->user, $groupDataArr);
            if (is_array($groupData) && !empty($groupData)) {
                // Keys in $groupData should be unique ids of the groups (like "uid") so this function will override groups.
                $groupDataArr = $groupData + $groupDataArr;
            }
            unset($serviceObj);
        }
        if ($this->writeDevLog && $serviceChain) {
            GeneralUtility::devLog($subType . ' auth services called: ' . $serviceChain, __CLASS__);
        }
        if ($this->writeDevLog && empty($groupDataArr)) {
            GeneralUtility::devLog('No usergroups found by services', __CLASS__);
        }
        if ($this->writeDevLog && !empty($groupDataArr)) {
            GeneralUtility::devLog(count($groupDataArr) . ' usergroup records found by services', __CLASS__);
        }
        // Use 'auth' service to check the usergroups if they are really valid
        foreach ($groupDataArr as $groupData) {
            // By default a group is valid
            $validGroup = true;
            $serviceChain = '';
            $subType = 'authGroups' . $this->loginType;
            while (is_object($serviceObj = GeneralUtility::makeInstanceService('auth', $subType, $serviceChain))) {
                $serviceChain .= ',' . $serviceObj->getServiceKey();
                $serviceObj->initAuth($subType, [], $authInfo, $this);
                if (!$serviceObj->authGroup($this->user, $groupData)) {
                    $validGroup = false;
                    if ($this->writeDevLog) {
                        GeneralUtility::devLog($subType . ' auth service did not auth group: ' . GeneralUtility::arrayToLogString($groupData, 'uid,title'), __CLASS__, 2);
                    }
                    break;
                }
                unset($serviceObj);
            }
            unset($serviceObj);
            if ($validGroup && (string)$groupData['uid'] !== '') {
                $this->groupData['title'][$groupData['uid']] = $groupData['title'];
                $this->groupData['uid'][$groupData['uid']] = $groupData['uid'];
                $this->groupData['pid'][$groupData['uid']] = $groupData['pid'];
                $this->groupData['TSconfig'][$groupData['uid']] = $groupData['TSconfig'];
            }
        }
        if (!empty($this->groupData) && !empty($this->groupData['TSconfig'])) {
            // TSconfig: collect it in the order it was collected
            foreach ($this->groupData['TSconfig'] as $TSdata) {
                $this->TSdataArray[] = $TSdata;
            }
            $this->TSdataArray[] = $this->user['TSconfig'];
            // Sort information
            ksort($this->groupData['title']);
            ksort($this->groupData['uid']);
            ksort($this->groupData['pid']);
        }
        return !empty($this->groupData['uid']) ? count($this->groupData['uid']) : 0;
    }

    /**
     * Returns the parsed TSconfig for the fe_user
     * The TSconfig will be cached in $this->userTS.
     *
     * @return array TSconfig array for the fe_user
     */
    public function getUserTSconf()
    {
        if (!$this->userTSUpdated) {
            // Parsing the user TS (or getting from cache)
            $this->TSdataArray = TypoScriptParser::checkIncludeLines_array($this->TSdataArray);
            $userTS = implode(LF . '[GLOBAL]' . LF, $this->TSdataArray);
            $parseObj = GeneralUtility::makeInstance(TypoScriptParser::class);
            $parseObj->parse($userTS);
            $this->userTS = $parseObj->setup;
            $this->userTSUpdated = true;
        }
        return $this->userTS;
    }

    /*****************************************
     *
     * Session data management functions
     *
     ****************************************/
    /**
     * Will write UC and session data.
     * If the flag $this->userData_change has been set, the function ->writeUC is called (which will save persistent user session data)
     * If the flag $this->sesData_change has been set, the current session record is updated with the content of $this->sessionData
     *
     * @see getKey(), setKey()
     */
    public function storeSessionData()
    {
        // Saves UC and SesData if changed.
        if ($this->userData_change) {
            $this->writeUC();
        }

        if ($this->sesData_change && $this->id) {
            if (empty($this->sessionData)) {
                // Remove session-data
                $this->removeSessionData();
                // Remove cookie if not logged in as the session data is removed as well
                if (empty($this->user['uid']) && !$this->loginHidden && $this->isCookieSet()) {
                    $this->removeCookie($this->name);
                }
            } elseif (!$this->isExistingSessionRecord($this->id)) {
                $sessionRecord = $this->getNewSessionRecord([]);
                $sessionRecord['ses_anonymous'] = 1;
                $sessionRecord['ses_data'] = serialize($this->sessionData);
                $updatedSession = $this->getSessionBackend()->set($this->id, $sessionRecord);
                $this->user = array_merge($this->user ?? [], $updatedSession);
                // Now set the cookie (= fix the session)
                $this->setSessionCookie();
            } else {
                // Update session data
                $insertFields = [
                    'ses_data' => serialize($this->sessionData)
                ];
                $updatedSession = $this->getSessionBackend()->update($this->id, $insertFields);
                $this->user = array_merge($this->user ?? [], $updatedSession);
            }
        }
    }

    /**
     * Removes data of the current session.
     */
    public function removeSessionData()
    {
        if (!empty($this->sessionData)) {
            $this->sesData_change = true;
        }
        $this->sessionData = [];

        if ($this->isExistingSessionRecord($this->id)) {
            // Remove session record if $this->user is empty is if session is anonymous
            if ((empty($this->user) && !$this->loginHidden) || $this->user['ses_anonymous']) {
                $this->getSessionBackend()->remove($this->id);
            } else {
                $this->getSessionBackend()->update($this->id, [
                    'ses_data' => ''
                ]);
            }
        }
    }

    /**
     * Removes the current session record, sets the internal ->user array to null,
     * Thereby the current user (if any) is effectively logged out!
     * Additionally the cookie is removed, but only if there is no session data.
     * If session data exists, only the user information is removed and the session
     * gets converted into an anonymous session.
     */
    protected function performLogoff()
    {
        $oldSession = [];
        $sessionData = [];
        try {
            // Session might not be loaded at this point, so fetch it
            $oldSession = $this->getSessionBackend()->get($this->id);
            $sessionData = unserialize($oldSession['ses_data']);
        } catch (SessionNotFoundException $e) {
            // Leave uncaught, will unset cookie later in this method
        }

        if (!empty($sessionData)) {
            // Regenerate session as anonymous
            $this->regenerateSessionId($oldSession, true);
        } else {
            $this->user = null;
            $this->getSessionBackend()->remove($this->id);
            if ($this->isCookieSet()) {
                $this->removeCookie($this->name);
            }
        }
    }

    /**
     * Regenerate the session ID and transfer the session to new ID
     * Call this method whenever a user proceeds to a higher authorization level
     * e.g. when an anonymous session is now authenticated.
     * Forces cookie to be set
     *
     * @param array $existingSessionRecord If given, this session record will be used instead of fetching again'
     * @param bool $anonymous If true session will be regenerated as anonymous session
     */
    protected function regenerateSessionId(array $existingSessionRecord = [], bool $anonymous = false)
    {
        if (empty($existingSessionRecord)) {
            $existingSessionRecord = $this->getSessionBackend()->get($this->id);
        }
        $existingSessionRecord['ses_anonymous'] = (int)$anonymous;
        if ($anonymous) {
            $existingSessionRecord['ses_userid'] = 0;
        }
        parent::regenerateSessionId($existingSessionRecord, $anonymous);
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
                $value = $this->uc[$key];
                break;
            case 'ses':
                $value = $this->getSessionData($key);
                break;
        }
        return $value;
    }

    /**
     * Saves session data, either persistent or bound to current session cookie. Please see getKey() for more details.
     * When a value is set the flags $this->userData_change or $this->sesData_change will be set so that the final call to ->storeSessionData() will know if a change has occurred and needs to be saved to the database.
     * Notice: The key "recs" is already used by the function record_registration() which stores table/uid=value pairs in that key. This is used for the shopping basket among other things.
     * Notice: Simply calling this function will not save the data to the database! The actual saving is done in storeSessionData() which is called as some of the last things in \TYPO3\CMS\Frontend\Http\RequestHandler. So if you exit before this point, nothing gets saved of course! And the solution is to call $GLOBALS['TSFE']->storeSessionData(); before you exit.
     *
     * @param string $type Session data type; Either "user" (persistent, bound to fe_users profile) or "ses" (temporary, bound to current session cookie)
     * @param string $key Key from the data array to store incoming data in; The session data (in either case) is an array ($this->uc / $this->sessionData) and this value determines in which key the $data value will be stored.
     * @param mixed $data The data value to store in $key
     * @see setKey(), storeSessionData(), record_registration()
     */
    public function setKey($type, $key, $data)
    {
        if (!$key) {
            return;
        }
        switch ($type) {
            case 'user':
                if ($this->user['uid']) {
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
     * Set session data by key.
     * The data will last only for this login session since it is stored in the user session.
     *
     * @param string $key A non empty string to store the data under
     * @param mixed $data Data store store in session
     */
    public function setSessionData($key, $data)
    {
        $this->sesData_change = true;
        if ($data === null) {
            unset($this->sessionData[$key]);
            return;
        }
        parent::setSessionData($key, $data);
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
     * Registration of records/"shopping basket" in session data
     * This will take the input array, $recs, and merge into the current "recs" array found in the session data.
     * If a change in the recs storage happens (which it probably does) the function setKey() is called in order to store the array again.
     *
     * @param array $recs The data array to merge into/override the current recs values. The $recs array is constructed as [table]][uid] = scalar-value (eg. string/integer).
     * @param int $maxSizeOfSessionData The maximum size of stored session data. If zero, no limit is applied and even confirmation of cookie session is discarded.
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9. Automatically feeding a "basket" by magic GET/POST keyword "recs" has been deprecated.
     */
    public function record_registration($recs, $maxSizeOfSessionData = 0)
    {
        GeneralUtility::logDeprecatedFunction();
        // Storing value ONLY if there is a confirmed cookie set,
        // otherwise a shellscript could easily be spamming the fe_sessions table
        // with bogus content and thus bloat the database
        if (!$maxSizeOfSessionData || $this->isCookieSet()) {
            if ($recs['clear_all']) {
                $this->setKey('ses', 'recs', []);
            }
            $change = 0;
            $recs_array = $this->getKey('ses', 'recs');
            foreach ($recs as $table => $data) {
                if (is_array($data)) {
                    foreach ($data as $rec_id => $value) {
                        if ($value != $recs_array[$table][$rec_id]) {
                            $recs_array[$table][$rec_id] = $value;
                            $change = 1;
                        }
                    }
                }
            }
            if ($change && (!$maxSizeOfSessionData || strlen(serialize($recs_array)) < $maxSizeOfSessionData)) {
                $this->setKey('ses', 'recs', $recs_array);
            }
        }
    }

    /**
     * Garbage collector, removing old expired sessions.
     *
     * @internal
     */
    public function gc()
    {
        $this->getSessionBackend()->collectGarbage($this->gc_time, $this->sessionDataLifetime);
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
}
