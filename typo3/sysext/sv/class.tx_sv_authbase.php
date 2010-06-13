<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004-2010 René Fritz <r.fritz@colorcube.de>
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
 * Service base class for 'User authentication'.
 *
 * @author	René Fritz <r.fritz@colorcube.de>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   62: class tx_sv_authbase extends t3lib_svbase
 *   87:     function initAuth($mode, $loginData, $authInfo, &$pObj)
 *  110:     function compareUident($user, $loginData, $security_level='')
 *  129:     function writelog($type,$action,$error,$details_nr,$details,$data,$tablename='',$recuid='',$recpid='')
 *
 *              SECTION: create/update user - EXPERIMENTAL
 *  158:     function fetchUserRecord($username, $extraWhere='', $dbUserSetup='')
 *
 * TOTAL FUNCTIONS: 4
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require_once(PATH_t3lib . 'class.t3lib_svbase.php');


/**
 * Authentication services class
 *
 * @author	René Fritz <r.fritz@colorcube.de>
 * @package TYPO3
 * @subpackage tx_sv
 */
class tx_sv_authbase extends t3lib_svbase 	{

	var $pObj; 			// Parent object

	var $mode;			// Subtype of the service which is used to call the service.

	var $login = array();		// Submitted login form data
	var $authInfo = array();	// Various data

	var $db_user = array();		// User db table definition
	var $db_groups = array();	// Usergroups db table definition

	var $writeAttemptLog = false;	// If the writelog() functions is called if a login-attempt has be tried without success
	var $writeDevLog = false;	// If the t3lib_div::devLog() function should be used


	/**
	 * Initialize authentication service
	 *
	 * @param	string		Subtype of the service which is used to call the service.
	 * @param	array		Submitted login form data
	 * @param	array		Information array. Holds submitted form data etc.
	 * @param	object		Parent object
	 * @return	void
	 */
	function initAuth($mode, $loginData, $authInfo, $pObj) {

		$this->pObj = $pObj;

		$this->mode = $mode;	// sub type
		$this->login = $loginData;
		$this->authInfo = $authInfo;

		$this->db_user = $this->getServiceOption('db_user', $authInfo['db_user'], FALSE);
		$this->db_groups = $this->getServiceOption('db_groups', $authInfo['db_groups'], FALSE);

		$this->writeAttemptLog = $this->pObj->writeAttemptLog;
		$this->writeDevLog	 = $this->pObj->writeDevLog;
	}

 	/**
	 * Check the login data with the user record data for builtin login methods
	 *
	 * @param	array		user data array
	 * @param	array		login data array
	 * @param	string		security_level
	 * @return	boolean		true if login data matched
	 */
	function compareUident($user, $loginData, $security_level='') {
		return $this->pObj->compareUident($user, $loginData, $security_level);
	}

	/**
	 * Writes to log database table in pObj
	 *
	 * @param	integer		$type: denotes which module that has submitted the entry. This is the current list:  1=tce_db; 2=tce_file; 3=system (eg. sys_history save); 4=modules; 254=Personal settings changed; 255=login / out action: 1=login, 2=logout, 3=failed login (+ errorcode 3), 4=failure_warning_email sent
	 * @param	integer		$action: denotes which specific operation that wrote the entry (eg. 'delete', 'upload', 'update' and so on...). Specific for each $type. Also used to trigger update of the interface. (see the log-module for the meaning of each number !!)
	 * @param	integer		$error: flag. 0 = message, 1 = error (user problem), 2 = System Error (which should not happen), 3 = security notice (admin)
	 * @param	integer		$details_nr: The message number. Specific for each $type and $action. in the future this will make it possible to translate errormessages to other languages
	 * @param	string		$details: Default text that follows the message
	 * @param	array		$data: Data that follows the log. Might be used to carry special information. If an array the first 5 entries (0-4) will be sprintf'ed the details-text...
	 * @param	string		$tablename: Special field used by tce_main.php. These ($tablename, $recuid, $recpid) holds the reference to the record which the log-entry is about. (Was used in attic status.php to update the interface.)
	 * @param	integer		$recuid: Special field used by tce_main.php. These ($tablename, $recuid, $recpid) holds the reference to the record which the log-entry is about. (Was used in attic status.php to update the interface.)
	 * @param	integer		$recpid: Special field used by tce_main.php. These ($tablename, $recuid, $recpid) holds the reference to the record which the log-entry is about. (Was used in attic status.php to update the interface.)
	 * @return	void
	 * @see t3lib_userauthgroup::writelog()
	 */
	function writelog($type,$action,$error,$details_nr,$details,$data,$tablename='',$recuid='',$recpid='')	{
		if($this->writeAttemptLog) {
			$this->pObj->writelog($type,$action,$error,$details_nr,$details,$data,$tablename,$recuid,$recpid);
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
	 * @param	string		user name
	 * @param	string		additional WHERE clause: " AND ...
	 * @param	array		User db table definition: $this->db_user
	 * @return	mixed		user array or false
	 */
	function fetchUserRecord($username, $extraWhere='', $dbUserSetup='')	{

		$dbUser = is_array($dbUserSetup) ? $dbUserSetup : $this->db_user;
		$user = $this->pObj->fetchUserRecord($dbUser, $username, $extraWhere);

		return $user;
	}
}

?>