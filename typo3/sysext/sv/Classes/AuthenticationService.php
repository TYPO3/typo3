<?php
namespace TYPO3\CMS\Sv;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2004-2013 René Fritz <r.fritz@colorcube.de>
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
 * Service 'User authentication' for the 'sv' extension.
 *
 * @author René Fritz <r.fritz@colorcube.de>
 */
/**
 * Authentication services class
 *
 * @author René Fritz <r.fritz@colorcube.de>
 */
class AuthenticationService extends \TYPO3\CMS\Sv\AbstractAuthenticationService {

	/**
	 * Process the submitted credentials.
	 * In this case hash the clear text password if it has been submitted.
	 *
	 * @param array $loginData Credentials that are submitted and potentially modified by other services
	 * @param string $passwordTransmissionStrategy Keyword of how the password has been hashed or encrypted before submission
	 * @return bool
	 */
	public function processLoginData(array &$loginData, $passwordTransmissionStrategy) {
		$isProcessed = TRUE;
		// Processing data according to the state it was submitted in.
		switch ($passwordTransmissionStrategy) {
		case 'normal':
			$loginData['uident_text'] = $loginData['uident'];
			break;
		case 'challenged':
			$loginData['uident_text'] = '';
			$loginData['uident_challenged'] = $loginData['uident'];
			$loginData['uident_superchallenged'] = '';
			break;
		case 'superchallenged':
			$loginData['uident_text'] = '';
			$loginData['uident_challenged'] = '';
			$loginData['uident_superchallenged'] = $loginData['uident'];
			break;
		default:
			$isProcessed = FALSE;
		}
		if (!empty($loginData['uident_text'])) {
			$loginData['uident_challenged'] = (string) md5(($loginData['uname'] . ':' . $loginData['uident_text'] . ':' . $loginData['chalvalue']));
			$loginData['uident_superchallenged'] = (string) md5(($loginData['uname'] . ':' . md5($loginData['uident_text']) . ':' . $loginData['chalvalue']));
			$this->processOriginalPasswordValue($loginData);
			$isProcessed = TRUE;
		}
		return $isProcessed;
	}

	/**
	 * This method ensures backwards compatibility of the processed loginData
	 * with older TYPO3 versions.
	 * Starting with TYPO3 6.1 $loginData['uident'] will always contain the raw
	 * value of the submitted password field and will not be processed any further.
	 *
	 * @param array $loginData
	 * @deprecated will be removed with 6.1
	 */
	protected function processOriginalPasswordValue(&$loginData) {
		if ($this->authInfo['security_level'] === 'superchallenged') {
			$loginData['uident'] = $loginData['uident_superchallenged'];
		} elseif ($this->authInfo['security_level'] === 'challenged') {
			$loginData['uident'] = $loginData['uident_challenged'];
		}
	}

	/**
	 * Find a user (eg. look up the user record in database when a login is sent)
	 *
	 * @return mixed User array or FALSE
	 * @todo Define visibility
	 */
	public function getUser() {
		$user = FALSE;
		if ($this->login['status'] == 'login') {
			if ($this->login['uident']) {
				$user = $this->fetchUserRecord($this->login['uname']);
				if (!is_array($user)) {
					// Failed login attempt (no username found)
					$this->writelog(255, 3, 3, 2, 'Login-attempt from %s (%s), username \'%s\' not found!!', array($this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->login['uname']));
					// Logout written to log
					\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(sprintf('Login-attempt from %s (%s), username \'%s\' not found!', $this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->login['uname']), 'Core', \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_WARNING);
				} else {
					if ($this->writeDevLog) {
						\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('User found: ' . \TYPO3\CMS\Core\Utility\GeneralUtility::arrayToLogString($user, array($this->db_user['userid_column'], $this->db_user['username_column'])), 'TYPO3\\CMS\\Sv\\AuthenticationService');
					}
				}
			} else {
				// Failed Login attempt (no password given)
				$this->writelog(255, 3, 3, 2, 'Login-attempt from %s (%s) for username \'%s\' with an empty password!', array($this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->login['uname']));
				\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(sprintf('Login-attempt from %s (%s), for username \'%s\' with an empty password!', $this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->login['uname']), 'Core', \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_WARNING);
			}
		}
		return $user;
	}

	/**
	 * Authenticate a user (Check various conditions for the user that might invalidate its authentication, eg. password match, domain, IP, etc.)
	 *
	 * @param array $user Data of user.
	 * @return boolean
	 */
	public function authUser(array $user) {
		$OK = 100;
		if ($this->login['uident'] && $this->login['uname']) {
			// Checking password match for user:
			$OK = $this->compareUident($user, $this->login);
			if (!$OK) {
				// Failed login attempt (wrong password) - write that to the log!
				if ($this->writeAttemptLog) {
					$this->writelog(255, 3, 3, 1, 'Login-attempt from %s (%s), username \'%s\', password not accepted!', array($this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->login['uname']));
					\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(sprintf('Login-attempt from %s (%s), username \'%s\', password not accepted!', $this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->login['uname']), 'Core', \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_WARNING);
				}
				if ($this->writeDevLog) {
					\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('Password not accepted: ' . $this->login['uident'], 'TYPO3\\CMS\\Sv\\AuthenticationService', 2);
				}
			}
			// Checking the domain (lockToDomain)
			if ($OK && $user['lockToDomain'] && $user['lockToDomain'] != $this->authInfo['HTTP_HOST']) {
				// Lock domain didn't match, so error:
				if ($this->writeAttemptLog) {
					$this->writelog(255, 3, 3, 1, 'Login-attempt from %s (%s), username \'%s\', locked domain \'%s\' did not match \'%s\'!', array($this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $user[$this->db_user['username_column']], $user['lockToDomain'], $this->authInfo['HTTP_HOST']));
					\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(sprintf('Login-attempt from %s (%s), username \'%s\', locked domain \'%s\' did not match \'%s\'!', $this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $user[$this->db_user['username_column']], $user['lockToDomain'], $this->authInfo['HTTP_HOST']), 'Core', \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_WARNING);
				}
				$OK = FALSE;
			}
		}
		return $OK;
	}

	/**
	 * Find usergroup records, currently only for frontend
	 *
	 * @param array $user Data of user.
	 * @param array $knownGroups Group data array of already known groups. This is handy if you want select other related groups. Keys in this array are unique IDs of those groups.
	 * @return mixed Groups array, keys = uid which must be unique
	 * @todo Define visibility
	 */
	public function getGroups($user, $knownGroups) {
		global $TYPO3_CONF_VARS;
		$groupDataArr = array();
		if ($this->mode == 'getGroupsFE') {
			$groups = array();
			if (is_array($user) && $user[$this->db_user['usergroup_column']]) {
				$groupList = $user[$this->db_user['usergroup_column']];
				$groups = array();
				$this->getSubGroups($groupList, '', $groups);
			}
			// ADD group-numbers if the IPmask matches.
			if (is_array($TYPO3_CONF_VARS['FE']['IPmaskMountGroups'])) {
				foreach ($TYPO3_CONF_VARS['FE']['IPmaskMountGroups'] as $IPel) {
					if ($this->authInfo['REMOTE_ADDR'] && $IPel[0] && \TYPO3\CMS\Core\Utility\GeneralUtility::cmpIP($this->authInfo['REMOTE_ADDR'], $IPel[0])) {
						$groups[] = intval($IPel[1]);
					}
				}
			}
			$groups = array_unique($groups);
			if (count($groups)) {
				$list = implode(',', $groups);
				if ($this->writeDevLog) {
					\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('Get usergroups with id: ' . $list, 'TYPO3\\CMS\\Sv\\AuthenticationService');
				}
				$lockToDomain_SQL = ' AND (lockToDomain=\'\' OR lockToDomain IS NULL OR lockToDomain=\'' . $this->authInfo['HTTP_HOST'] . '\')';
				if (!$this->authInfo['showHiddenRecords']) {
					$hiddenP = 'AND hidden=0 ';
				}
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $this->db_groups['table'], 'deleted=0 ' . $hiddenP . ' AND uid IN (' . $list . ')' . $lockToDomain_SQL);
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					$groupDataArr[$row['uid']] = $row;
				}
				if ($res) {
					$GLOBALS['TYPO3_DB']->sql_free_result($res);
				}
			} else {
				if ($this->writeDevLog) {
					\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('No usergroups found.', 'TYPO3\\CMS\\Sv\\AuthenticationService', 2);
				}
			}
		} elseif ($this->mode == 'getGroupsBE') {

		}
		return $groupDataArr;
	}

	/**
	 * Fetches subgroups of groups. Function is called recursively for each subgroup.
	 * Function was previously copied from
	 * \TYPO3\CMS\Core\Authentication\BackendUserAuthentication->fetchGroups and has been slightly modified.
	 *
	 * @param string $grList Commalist of fe_groups uid numbers
	 * @param string $idList List of already processed fe_groups-uids so the function will not fall into a eternal recursion.
	 * @param array $groups
	 * @return array
	 * @access private
	 * @todo Define visibility
	 */
	public function getSubGroups($grList, $idList = '', &$groups) {
		// Fetching records of the groups in $grList (which are not blocked by lockedToDomain either):
		$lockToDomain_SQL = ' AND (lockToDomain=\'\' OR lockToDomain IS NULL OR lockToDomain=\'' . $this->authInfo['HTTP_HOST'] . '\')';
		if (!$this->authInfo['showHiddenRecords']) {
			$hiddenP = 'AND hidden=0 ';
		}
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,subgroup', 'fe_groups', 'deleted=0 ' . $hiddenP . ' AND uid IN (' . $grList . ')' . $lockToDomain_SQL);
		// Internal group record storage
		$groupRows = array();
		// The groups array is filled
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			if (!in_array($row['uid'], $groups)) {
				$groups[] = $row['uid'];
			}
			$groupRows[$row['uid']] = $row;
		}
		// Traversing records in the correct order
		$include_staticArr = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $grList);
		// traversing list
		foreach ($include_staticArr as $uid) {
			// Get row:
			$row = $groupRows[$uid];
			// Must be an array and $uid should not be in the idList, because then it is somewhere previously in the grouplist
			if (is_array($row) && !\TYPO3\CMS\Core\Utility\GeneralUtility::inList($idList, $uid)) {
				// Include sub groups
				if (trim($row['subgroup'])) {
					// Make integer list
					$theList = implode(',', \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $row['subgroup']));
					// Call recursively, pass along list of already processed groups so they are not recursed again.
					$this->getSubGroups($theList, $idList . ',' . $uid, $groups);
				}
			}
		}
	}

}


?>