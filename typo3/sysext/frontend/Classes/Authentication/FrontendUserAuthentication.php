<?php
namespace TYPO3\CMS\Frontend\Authentication;

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
 * Front End session user. Login and session data
 * Included from index_ts.php
 *
 * Revised for TYPO3 3.6 June/2003 by Kasper Skårhøj
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author René Fritz <r.fritz@colorcube.de>
 */
/**
 * Extension class for Front End User Authentication.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author René Fritz <r.fritz@colorcube.de>
 */
class FrontendUserAuthentication extends \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication {

	// formfield with 0 or 1 // 1 = permanent login enabled // 0 = session is valid for a browser session only
	/**
	 * @todo Define visibility
	 */
	public $formfield_permanent = 'permalogin';

	// Lifetime of session data in seconds.
	protected $sessionDataLifetime = 86400;

	/**
	 * @todo Define visibility
	 */
	public $usergroup_column = 'usergroup';

	/**
	 * @todo Define visibility
	 */
	public $usergroup_table = 'fe_groups';

	/**
	 * @todo Define visibility
	 */
	public $groupData = array(
		'title' => array(),
		'uid' => array(),
		'pid' => array()
	);

	// Used to accumulate the TSconfig data of the user
	/**
	 * @todo Define visibility
	 */
	public $TSdataArray = array();

	/**
	 * @todo Define visibility
	 */
	public $userTS = array();

	/**
	 * @todo Define visibility
	 */
	public $userTSUpdated = 0;

	/**
	 * @todo Define visibility
	 */
	public $showHiddenRecords = 0;

	// Session and user data:
	/*
	There are two types of data that can be stored: UserData and Session-Data. Userdata is for the login-user, and session-data for anyone viewing the pages.
	'Keys' are keys in the internal dataarray of the data. When you get or set a key in one of the data-spaces (user or session) you decide the type of the variable (not object though)
	'Reserved' keys are:
	- 'recs': Array: Used to 'register' records, eg in a shopping basket. Structure: [recs][tablename][record_uid]=number
	- sys: Reserved for TypoScript standard code.
	 */
	/**
	 * @todo Define visibility
	 */
	public $sesData = array();

	/**
	 * @todo Define visibility
	 */
	public $sesData_change = 0;

	/**
	 * @todo Define visibility
	 */
	public $userData_change = 0;

	protected $sessionDataTimestamp = NULL;

	/**
	 * Default constructor.
	 */
	public function __construct() {
		$this->session_table = 'fe_sessions';
		$this->name = self::getCookieName();
		$this->get_name = 'ftu';
		$this->loginType = 'FE';
		$this->user_table = 'fe_users';
		$this->username_column = 'username';
		$this->userident_column = 'password';
		$this->userid_column = 'uid';
		$this->lastLogin_column = 'lastlogin';
		$this->enablecolumns = array(
			'deleted' => 'deleted',
			'disabled' => 'disable',
			'starttime' => 'starttime',
			'endtime' => 'endtime'
		);
		$this->formfield_uname = 'user';
		$this->formfield_uident = 'pass';
		$this->formfield_chalvalue = 'challenge';
		$this->formfield_status = 'logintype';
		$this->security_level = '';
		$this->auth_timeout_field = 6000;
		$this->sendNoCacheHeaders = FALSE;
		$this->getFallBack = TRUE;
		$this->getMethodEnabled = TRUE;
	}

	/**
	 * Returns the configured cookie name
	 *
	 * @return string
	 */
	static public function getCookieName() {
		$configuredCookieName = trim($GLOBALS['TYPO3_CONF_VARS']['FE']['cookieName']);
		if (empty($configuredCookieName)) {
			$configuredCookieName = 'fe_typo_user';
		}
		return $configuredCookieName;
	}

	/**
	 * Starts a user session
	 *
	 * @return void
	 * @see \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication::start()
	 * @todo Define visibility
	 */
	public function start() {
		if (intval($this->auth_timeout_field) > 0 && intval($this->auth_timeout_field) < $this->lifetime) {
			// If server session timeout is non-zero but less than client session timeout: Copy this value instead.
			$this->auth_timeout_field = $this->lifetime;
		}
		$this->sessionDataLifetime = intval($GLOBALS['TYPO3_CONF_VARS']['FE']['sessionDataLifetime']);
		if ($this->sessionDataLifetime <= 0) {
			$this->sessionDataLifetime = 86400;
		}
		parent::start();
	}

	/**
	 * Returns a new session record for the current user for insertion into the DB.
	 *
	 * @return array User session record
	 * @todo Define visibility
	 */
	public function getNewSessionRecord($tempuser) {
		$insertFields = parent::getNewSessionRecord($tempuser);
		$insertFields['ses_permanent'] = $this->is_permanent;
		return $insertFields;
	}

	/**
	 * Determine whether a session cookie needs to be set (lifetime=0)
	 *
	 * @return boolean
	 * @internal
	 * @todo Define visibility
	 */
	public function isSetSessionCookie() {
		$retVal = ($this->newSessionID || $this->forceSetCookie) && ($this->lifetime == 0 || !$this->user['ses_permanent']);
		return $retVal;
	}

	/**
	 * Determine whether a non-session cookie needs to be set (lifetime>0)
	 *
	 * @return boolean
	 * @internal
	 * @todo Define visibility
	 */
	public function isRefreshTimeBasedCookie() {
		return $this->lifetime > 0 && $this->user['ses_permanent'];
	}

	/**
	 * Returns an info array with Login/Logout data submitted by a form or params
	 *
	 * @return array
	 * @see \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication::getLoginFormData()
	 * @todo Define visibility
	 */
	public function getLoginFormData() {
		$loginData = parent::getLoginFormData();
		if ($GLOBALS['TYPO3_CONF_VARS']['FE']['permalogin'] == 0 || $GLOBALS['TYPO3_CONF_VARS']['FE']['permalogin'] == 1) {
			if ($this->getMethodEnabled) {
				$isPermanent = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP($this->formfield_permanent);
			} else {
				$isPermanent = \TYPO3\CMS\Core\Utility\GeneralUtility::_POST($this->formfield_permanent);
			}
			if (strlen($isPermanent) != 1) {
				$isPermanent = $GLOBALS['TYPO3_CONF_VARS']['FE']['permalogin'];
			} elseif (!$isPermanent) {
				// To make sure the user gets a session cookie and doesn't keep a possibly existing time based cookie,
				// we need to force seeting the session cookie here
				$this->forceSetCookie = TRUE;
			}
			$isPermanent = $isPermanent ? 1 : 0;
		} elseif ($GLOBALS['TYPO3_CONF_VARS']['FE']['permalogin'] == 2) {
			$isPermanent = 1;
		} else {
			$isPermanent = 0;
		}
		$loginData['permanent'] = $isPermanent;
		$this->is_permanent = $isPermanent;
		return $loginData;
	}

	/**
	 * Will select all fe_groups records that the current fe_user is member of - and which groups are also allowed in the current domain.
	 * It also accumulates the TSconfig for the fe_user/fe_groups in ->TSdataArray
	 *
	 * @return integer Returns the number of usergroups for the frontend users (if the internal user record exists and the usergroup field contains a value)
	 * @todo Define visibility
	 */
	public function fetchGroupData() {
		$this->TSdataArray = array();
		$this->userTS = array();
		$this->userTSUpdated = 0;
		$this->groupData = array(
			'title' => array(),
			'uid' => array(),
			'pid' => array()
		);
		// Setting default configuration:
		$this->TSdataArray[] = $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultUserTSconfig'];
		// Get the info data for auth services
		$authInfo = $this->getAuthInfoArray();
		if ($this->writeDevLog) {
			if (is_array($this->user)) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('Get usergroups for user: ' . \TYPO3\CMS\Core\Utility\GeneralUtility::arrayToLogString($this->user, array($this->userid_column, $this->username_column)), 'TYPO3\\CMS\\Frontend\\Authentication\\FrontendUserAuthentication');
			} else {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('Get usergroups for "anonymous" user', 'TYPO3\\CMS\\Frontend\\Authentication\\FrontendUserAuthentication');
			}
		}
		$groupDataArr = array();
		// Use 'auth' service to find the groups for the user
		$serviceChain = '';
		$subType = 'getGroups' . $this->loginType;
		while (is_object($serviceObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstanceService('auth', $subType, $serviceChain))) {
			$serviceChain .= ',' . $serviceObj->getServiceKey();
			$serviceObj->initAuth($subType, array(), $authInfo, $this);
			$groupData = $serviceObj->getGroups($this->user, $groupDataArr);
			if (is_array($groupData) && count($groupData)) {
				// Keys in $groupData should be unique ids of the groups (like "uid") so this function will override groups.
				$groupDataArr = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge($groupDataArr, $groupData);
			}
			unset($serviceObj);
		}
		if ($this->writeDevLog && $serviceChain) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::devLog($subType . ' auth services called: ' . $serviceChain, 'TYPO3\\CMS\\Frontend\\Authentication\\FrontendUserAuthentication');
		}
		if ($this->writeDevLog && !count($groupDataArr)) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('No usergroups found by services', 'TYPO3\\CMS\\Frontend\\Authentication\\FrontendUserAuthentication');
		}
		if ($this->writeDevLog && count($groupDataArr)) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(count($groupDataArr) . ' usergroup records found by services', 'TYPO3\\CMS\\Frontend\\Authentication\\FrontendUserAuthentication');
		}
		// Use 'auth' service to check the usergroups if they are really valid
		foreach ($groupDataArr as $groupData) {
			// By default a group is valid
			$validGroup = TRUE;
			$serviceChain = '';
			$subType = 'authGroups' . $this->loginType;
			while (is_object($serviceObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstanceService('auth', $subType, $serviceChain))) {
				$serviceChain .= ',' . $serviceObj->getServiceKey();
				$serviceObj->initAuth($subType, array(), $authInfo, $this);
				if (!$serviceObj->authGroup($this->user, $groupData)) {
					$validGroup = FALSE;
					if ($this->writeDevLog) {
						\TYPO3\CMS\Core\Utility\GeneralUtility::devLog($subType . ' auth service did not auth group: ' . \TYPO3\CMS\Core\Utility\GeneralUtility::arrayToLogString($groupData, 'uid,title'), 'TYPO3\\CMS\\Frontend\\Authentication\\FrontendUserAuthentication', 2);
					}
					break;
				}
				unset($serviceObj);
			}
			unset($serviceObj);
			if ($validGroup) {
				$this->groupData['title'][$groupData['uid']] = $groupData['title'];
				$this->groupData['uid'][$groupData['uid']] = $groupData['uid'];
				$this->groupData['pid'][$groupData['uid']] = $groupData['pid'];
				$this->groupData['TSconfig'][$groupData['uid']] = $groupData['TSconfig'];
			}
		}
		if (count($this->groupData) && count($this->groupData['TSconfig'])) {
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
		return count($this->groupData['uid']) ? count($this->groupData['uid']) : 0;
	}

	/**
	 * Returns the parsed TSconfig for the fe_user
	 * First time this function is called it will parse the TSconfig and store it in $this->userTS. Subsequent requests will not re-parse the TSconfig but simply return what is already in $this->userTS
	 *
	 * @return array TSconfig array for the fe_user
	 * @todo Define visibility
	 */
	public function getUserTSconf() {
		if (!$this->userTSUpdated) {
			// Parsing the user TS (or getting from cache)
			$this->TSdataArray = \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser::checkIncludeLines_array($this->TSdataArray);
			$userTS = implode(LF . '[GLOBAL]' . LF, $this->TSdataArray);
			$parseObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\Parser\\TypoScriptParser');
			$parseObj->parse($userTS);
			$this->userTS = $parseObj->setup;
			$this->userTSUpdated = 1;
		}
		return $this->userTS;
	}

	/*****************************************
	 *
	 * Session data management functions
	 *
	 ****************************************/
	/**
	 * Fetches the session data for the user (from the fe_session_data table) based on the ->id of the current user-session.
	 * The session data is restored to $this->sesData
	 * 1/100 calls will also do a garbage collection.
	 *
	 * @return void
	 * @access private
	 * @see storeSessionData()
	 * @todo Define visibility
	 */
	public function fetchSessionData() {
		// Gets SesData if any AND if not already selected by session fixation check in ->isExistingSessionRecord()
		if ($this->id && !count($this->sesData)) {
			$statement = $GLOBALS['TYPO3_DB']->prepare_SELECTquery('*', 'fe_session_data', 'hash = :hash');
			$statement->execute(array(':hash' => $this->id));
			if (($sesDataRow = $statement->fetch()) !== FALSE) {
				$this->sesData = unserialize($sesDataRow['content']);
				$this->sessionDataTimestamp = $sesDataRow['tstamp'];
			}
			$statement->free();
		}
	}

	/**
	 * Will write UC and session data.
	 * If the flag $this->userData_change has been set, the function ->writeUC is called (which will save persistent user session data)
	 * If the flag $this->sesData_change has been set, the fe_session_data table is updated with the content of $this->sesData
	 * If the $this->sessionDataTimestamp is NULL there was no session record yet, so we need to insert it into the database
	 *
	 * @return void
	 * @see fetchSessionData(), getKey(), setKey()
	 * @todo Define visibility
	 */
	public function storeSessionData() {
		// Saves UC and SesData if changed.
		if ($this->userData_change) {
			$this->writeUC('');
		}
		if ($this->sesData_change && $this->id) {
			if (empty($this->sesData)) {
				// Remove session-data
				$this->removeSessionData();
			} elseif ($this->sessionDataTimestamp === NULL) {
				// Write new session-data
				$insertFields = array(
					'hash' => $this->id,
					'content' => serialize($this->sesData),
					'tstamp' => $GLOBALS['EXEC_TIME']
				);
				$this->sessionDataTimestamp = $GLOBALS['EXEC_TIME'];
				$GLOBALS['TYPO3_DB']->exec_INSERTquery('fe_session_data', $insertFields);
			} else {
				// Update session data
				$updateFields = array(
					'content' => serialize($this->sesData),
					'tstamp' => $GLOBALS['EXEC_TIME']
				);
				$this->sessionDataTimestamp = $GLOBALS['EXEC_TIME'];
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery('fe_session_data', 'hash=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($this->id, 'fe_session_data'), $updateFields);
			}
		}
	}

	/**
	 * Removes data of the current session.
	 *
	 * @return void
	 */
	public function removeSessionData() {
		$GLOBALS['TYPO3_DB']->exec_DELETEquery('fe_session_data', 'hash=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($this->id, 'fe_session_data'));
	}

	/**
	 * Executes the garbage collection of session data and session.
	 * The lifetime of session data is defined by $TYPO3_CONF_VARS['FE']['sessionDataLifetime'].
	 *
	 * @return void
	 */
	public function gc() {
		$timeoutTimeStamp = intval($GLOBALS['EXEC_TIME'] - $this->sessionDataLifetime);
		$GLOBALS['TYPO3_DB']->exec_DELETEquery('fe_session_data', 'tstamp < ' . $timeoutTimeStamp);
		parent::gc();
	}

	/**
	 * Returns session data for the fe_user; Either persistent data following the fe_users uid/profile (requires login) or current-session based (not available when browse is closed, but does not require login)
	 *
	 * @param string $type Session data type; Either "user" (persistent, bound to fe_users profile) or "ses" (temporary, bound to current session cookie)
	 * @param string $key Key from the data array to return; The session data (in either case) is an array ($this->uc / $this->sesData) and this value determines which key to return the value for.
	 * @return mixed Returns whatever value there was in the array for the key, $key
	 * @see setKey()
	 * @todo Define visibility
	 */
	public function getKey($type, $key) {
		if ($key) {
			switch ($type) {
			case 'user':
				return $this->uc[$key];
				break;
			case 'ses':
				return $this->sesData[$key];
				break;
			}
		}
	}

	/**
	 * Saves session data, either persistent or bound to current session cookie. Please see getKey() for more details.
	 * When a value is set the flags $this->userData_change or $this->sesData_change will be set so that the final call to ->storeSessionData() will know if a change has occurred and needs to be saved to the database.
	 * Notice: The key "recs" is already used by the function record_registration() which stores table/uid=value pairs in that key. This is used for the shopping basket among other things.
	 * Notice: Simply calling this function will not save the data to the database! The actual saving is done in storeSessionData() which is called as some of the last things in index_ts.php. So if you exit before this point, nothing gets saved of course! And the solution is to call $GLOBALS['TSFE']->storeSessionData(); before you exit.
	 *
	 * @param string $type Session data type; Either "user" (persistent, bound to fe_users profile) or "ses" (temporary, bound to current session cookie)
	 * @param string $key Key from the data array to store incoming data in; The session data (in either case) is an array ($this->uc / $this->sesData) and this value determines in which key the $data value will be stored.
	 * @param mixed $data The data value to store in $key
	 * @return void
	 * @see setKey(), storeSessionData(), record_registration()
	 * @todo Define visibility
	 */
	public function setKey($type, $key, $data) {
		if ($key) {
			switch ($type) {
			case 'user':
				if ($this->user['uid']) {
					if ($data === NULL) {
						unset($this->uc[$key]);
					} else {
						$this->uc[$key] = $data;
					}
					$this->userData_change = 1;
				}
				break;
			case 'ses':
				if ($data === NULL) {
					unset($this->sesData[$key]);
				} else {
					$this->sesData[$key] = $data;
				}
				$this->sesData_change = 1;
				break;
			}
		}
	}

	/**
	 * Returns the session data stored for $key.
	 * The data will last only for this login session since it is stored in the session table.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function getSessionData($key) {
		return $this->getKey('ses', $key);
	}

	/**
	 * Saves the tokens so that they can be used by a later incarnation of this class.
	 *
	 * @param string $key
	 * @param mixed $data
	 * @return void
	 */
	public function setAndSaveSessionData($key, $data) {
		$this->setKey('ses', $key, $data);
		$this->storeSessionData();
	}

	/**
	 * Registration of records/"shopping basket" in session data
	 * This will take the input array, $recs, and merge into the current "recs" array found in the session data.
	 * If a change in the recs storage happens (which it probably does) the function setKey() is called in order to store the array again.
	 *
	 * @param array $recs The data array to merge into/override the current recs values. The $recs array is constructed as [table]][uid] = scalar-value (eg. string/integer).
	 * @param integer $maxSizeOfSessionData The maximum size of stored session data. If zero, no limit is applied and even confirmation of cookie session is discarded.
	 * @return void
	 * @todo Define visibility
	 */
	public function record_registration($recs, $maxSizeOfSessionData = 0) {
		// Storing value ONLY if there is a confirmed cookie set (->cookieID),
		// otherwise a shellscript could easily be spamming the fe_sessions table
		// with bogus content and thus bloat the database
		if (!$maxSizeOfSessionData || $this->cookieId) {
			if ($recs['clear_all']) {
				$this->setKey('ses', 'recs', array());
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
	 * Determine whether there's an according session record to a given session_id
	 * in the database. Don't care if session record is still valid or not.
	 *
	 * This calls the parent function but additionally tries to look up the session ID in the "fe_session_data" table.
	 *
	 * @param integer $id Claimed Session ID
	 * @return boolean Returns TRUE if a corresponding session was found in the database
	 * @todo Define visibility
	 */
	public function isExistingSessionRecord($id) {
		// Perform check in parent function
		$count = parent::isExistingSessionRecord($id);
		// Check if there are any fe_session_data records for the session ID the client claims to have
		if ($count == FALSE) {
			$statement = $GLOBALS['TYPO3_DB']->prepare_SELECTquery('content,tstamp', 'fe_session_data', 'hash = :hash');
			$res = $statement->execute(array(':hash' => $id));
			if ($res !== FALSE) {
				if ($sesDataRow = $statement->fetch()) {
					$count = TRUE;
					$this->sesData = unserialize($sesDataRow['content']);
					$this->sessionDataTimestamp = $sesDataRow['tstamp'];
				}
				$statement->free();
			}
		}
		return $count;
	}

}


?>