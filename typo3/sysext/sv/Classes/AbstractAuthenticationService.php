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
 * Authentication services class
 *
 * @author René Fritz <r.fritz@colorcube.de>
 */
class AbstractAuthenticationService extends \TYPO3\CMS\Core\Service\AbstractService {

	/**
	 * User object
	 *
	 * @var \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication
	 * @todo Define visibility
	 */
	public $pObj;

	// Subtype of the service which is used to call the service.
	/**
	 * @todo Define visibility
	 */
	public $mode;

	// Submitted login form data
	/**
	 * @todo Define visibility
	 */
	public $login = array();

	// Various data
	/**
	 * @todo Define visibility
	 */
	public $authInfo = array();

	// User db table definition
	/**
	 * @todo Define visibility
	 */
	public $db_user = array();

	// Usergroups db table definition
	/**
	 * @todo Define visibility
	 */
	public $db_groups = array();

	// If the writelog() functions is called if a login-attempt has be tried without success
	/**
	 * @todo Define visibility
	 */
	public $writeAttemptLog = FALSE;

	// If the \TYPO3\CMS\Core\Utility\GeneralUtility::devLog() function should be used
	/**
	 * @todo Define visibility
	 */
	public $writeDevLog = FALSE;

	/**
	 * Initialize authentication service
	 *
	 * @param string $mode Subtype of the service which is used to call the service.
	 * @param array $loginData Submitted login form data
	 * @param array $authInfo Information array. Holds submitted form data etc.
	 * @param object $pObj Parent object
	 * @return void
	 * @todo Define visibility
	 */
	public function initAuth($mode, $loginData, $authInfo, $pObj) {
		$this->pObj = $pObj;
		// Sub type
		$this->mode = $mode;
		$this->login = $loginData;
		$this->authInfo = $authInfo;
		$this->db_user = $this->getServiceOption('db_user', $authInfo['db_user'], FALSE);
		$this->db_groups = $this->getServiceOption('db_groups', $authInfo['db_groups'], FALSE);
		$this->writeAttemptLog = $this->pObj->writeAttemptLog;
		$this->writeDevLog = $this->pObj->writeDevLog;
	}

	/**
	 * Check the login data with the user record data for builtin login methods
	 *
	 * @param array $user User data array
	 * @param array $loginData Login data array
	 * @param string $passwordCompareStrategy Password compare strategy
	 * @return boolean TRUE if login data matched
	 * @todo Define visibility
	 */
	public function compareUident(array $user, array $loginData, $passwordCompareStrategy = '') {
		if ($this->authInfo['loginType'] === 'BE') {
			// Challenge is only stored in session during BE login with the superchallenged login type.
			// In the frontend context the challenge is never stored in the session.
			if ($passwordCompareStrategy !== 'superchallenged') {
				$this->pObj->challengeStoredInCookie = FALSE;
			}
			// The TYPO3 standard login service relies on $passwordCompareStrategy being set
			// to 'superchallenged' because of the password in the database is stored as md5 hash
			$passwordCompareStrategy = 'superchallenged';
		}
		return $this->pObj->compareUident($user, $loginData, $passwordCompareStrategy);
	}

	/**
	 * Writes to log database table in pObj
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
	public function writelog($type, $action, $error, $details_nr, $details, $data, $tablename = '', $recuid = '', $recpid = '') {
		if ($this->writeAttemptLog) {
			$this->pObj->writelog($type, $action, $error, $details_nr, $details, $data, $tablename, $recuid, $recpid);
		}
	}

	/*************************
	 *
	 * create/update user - EXPERIMENTAL
	 *
	 *************************/
	/**
	 * Get a user from DB by username
	 *
	 * @param string $username User name
	 * @param string $extraWhere Additional WHERE clause: " AND ...
	 * @param array $dbUserSetup User db table definition: $this->db_user
	 * @return mixed User array or FALSE
	 * @todo Define visibility
	 */
	public function fetchUserRecord($username, $extraWhere = '', $dbUserSetup = '') {
		$dbUser = is_array($dbUserSetup) ? $dbUserSetup : $this->db_user;
		$user = $this->pObj->fetchUserRecord($dbUser, $username, $extraWhere);
		return $user;
	}

}


?>