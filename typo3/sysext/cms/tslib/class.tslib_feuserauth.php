<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2005 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * $Id$
 * Revised for TYPO3 3.6 June/2003 by Kasper Skaarhoj
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   77: class tslib_feUserAuth extends t3lib_userAuth
 *  141:     function fetchGroupData()
 *  194:     function getUserTSconf()
 *
 *              SECTION: Session data management functions
 *  239:     function fetchSessionData()
 *  261:     function storeSessionData()
 *  287:     function getKey($type,$key)
 *  312:     function setKey($type,$key,$data)
 *  337:     function record_registration($recs)
 *
 * TOTAL FUNCTIONS: 7
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */














/**
 * Extension class for Front End User Authentication.
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tslib
 */
class tslib_feUserAuth extends t3lib_userAuth {
	var $session_table = 'fe_sessions'; 		// Table to use for session data.
	var $name = 'fe_typo_user';                 // Session/Cookie name
	var $get_name = 'ftu';		                	 // Session/GET-var name

	var $user_table = 'fe_users'; 					// Table in database with userdata
	var $username_column = 'username'; 				// Column for login-name
	var $userident_column = 'password'; 			// Column for password
	var $userid_column = 'uid'; 					// Column for user-id
	var $lastLogin_column = 'lastlogin';

	var $enablecolumns = Array (
		'deleted' => 'deleted',
		'disabled' => 'disable',
		'starttime' => 'starttime',
		'endtime' => 'endtime'
	);
	var $formfield_uname = 'user'; 				// formfield with login-name
	var $formfield_uident = 'pass'; 			// formfield with password
	var $formfield_chalvalue = 'challenge';		// formfield with a unique value which is used to encrypt the password and username
	var $formfield_status = 'logintype'; 		// formfield with status: *'login', 'logout'
	var $security_level = '';					// sets the level of security. *'normal' = clear-text. 'challenged' = hashed password/username from form in $formfield_uident. 'superchallenged' = hashed password hashed again with username.

	var $auth_include = '';						// this is the name of the include-file containing the login form. If not set, login CAN be anonymous. If set login IS needed.

	var $auth_timeout_field = 6000;				// if > 0 : session-timeout in seconds. if false/<0 : no timeout. if string: The string is fieldname from the usertable where the timeout can be found.

	var $lifetime = 0;                  		// 0 = Session-cookies. If session-cookies, the browser will stop session when the browser is closed. Else it keeps the session for $lifetime seconds.
	var $sendNoCacheHeaders = 0;
	var $getFallBack = 1;						// If this is set, authentication is also accepted by the _GET. Notice that the identification is NOT 128bit MD5 hash but reduced. This is done in order to minimize the size for mobile-devices, such as WAP-phones
	var $hash_length = 10;
	var $getMethodEnabled = 1;					// Login may be supplied by url.

	var $usergroup_column = 'usergroup';
	var $usergroup_table = 'fe_groups';
	var $groupData = Array(
		'title' =>Array(),
		'uid' =>Array(),
		'pid' =>Array()
	);
	var $TSdataArray=array();		// Used to accumulate the TSconfig data of the user
	var $userTS = array();
	var $userTSUpdated=0;
	var $showHiddenRecords=0;

		// Session and user data:
		/*
			There are two types of data that can be stored: UserData and Session-Data. Userdata is for the login-user, and session-data for anyone viewing the pages.
			'Keys' are keys in the internal dataarray of the data. When you get or set a key in one of the data-spaces (user or session) you decide the type of the variable (not object though)
			'Reserved' keys are:
				- 'recs': Array: Used to 'register' records, eg in a shopping basket. Structure: [recs][tablename][record_uid]=number
				- sys: Reserved for TypoScript standard code.
		*/
	var $sesData = Array();
	var $sesData_change = 0;
	var $userData_change = 0;


	/**
	 * Will select all fe_groups records that the current fe_user is member of - and which groups are also allowed in the current domain.
	 * It also accumulates the TSconfig for the fe_user/fe_groups in ->TSdataArray
	 *
	 * @return	integer		Returns the number of usergroups for the frontend users (if the internal user record exists and the usergroup field contains a value)
	 */
	function fetchGroupData()	{
		$this->TSdataArray = array();
		$this->userTS = array();
		$this->userTSUpdated = 0;
		$this->groupData = Array(
			'title' => Array(),
			'uid' => Array(),
			'pid' => Array()
		);

			// Setting default configuration:
		$this->TSdataArray[]=$GLOBALS['TYPO3_CONF_VARS']['FE']['defaultUserTSconfig'];

		if (is_array($this->user) && $this->user['usergroup'])	{
			$groups = t3lib_div::intExplode(',',$this->user['usergroup']);
			$list = implode(',',$groups);
			$lockToDomain_SQL = ' AND (lockToDomain=\'\' OR lockToDomain=\''.t3lib_div::getIndpEnv('HTTP_HOST').'\')';
			if (!$this->showHiddenRecords)	$hiddenP = 'AND hidden=0 ';

			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $this->usergroup_table, 'deleted=0 '.$hiddenP.'AND uid IN ('.$list.')'.$lockToDomain_SQL);
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				$this->groupData['title'][$row['uid']] = $row['title'];
				$this->groupData['uid'][$row['uid']] = $row['uid'];
				$this->groupData['pid'][$row['uid']] = $row['pid'];
				$this->groupData['TSconfig'][$row['uid']] = $row['TSconfig'];
			}

			if ($GLOBALS['TYPO3_DB']->sql_num_rows($res))	{
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
				// TSconfig:
				reset($groups);
				while(list(,$TSuid)=each($groups))	{
					$this->TSdataArray[]=$this->groupData['TSconfig'][$TSuid];
				}
				$this->TSdataArray[]=$this->user['TSconfig'];

				// Sort information
				ksort($this->groupData['title']);
				ksort($this->groupData['uid']);
				ksort($this->groupData['pid']);
				return count($this->groupData['uid']);
			} else {
				return 0;
			}
		}
	}

	/**
	 * Returns the parsed TSconfig for the fe_user
	 * First time this function is called it will parse the TSconfig and store it in $this->userTS. Subsequent requests will not re-parse the TSconfig but simply return what is already in $this->userTS
	 *
	 * @return	array		TSconfig array for the fe_user
	 */
	function getUserTSconf()	{
		if (!$this->userTSUpdated) {
				// Parsing the user TS (or getting from cache)
			$this->TSdataArray = t3lib_TSparser::checkIncludeLines_array($this->TSdataArray);
			$userTS = implode(chr(10).'[GLOBAL]'.chr(10),$this->TSdataArray);
			$parseObj = t3lib_div::makeInstance('t3lib_TSparser');
			$parseObj->parse($userTS);
			$this->userTS = $parseObj->setup;

			$this->userTSUpdated=1;
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
	 * @return	void
	 * @access private
	 * @see storeSessionData()
	 */
	function fetchSessionData()	{
		// Gets SesData if any
		if ($this->id)	{
			$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'fe_session_data', 'hash='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->id, 'fe_session_data'));
			if ($sesDataRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres))	{
				$this->sesData = unserialize($sesDataRow['content']);
			}
		}
			// delete old data:
		if ((rand()%100) <= 1) {		// a possibility of 1 % for garbage collection.
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('fe_session_data', 'tstamp < '.intval(time()-3600*24));		// all data older than 24 hours are deleted.
		}
	}

	/**
	 * Will write UC and session data.
	 * If the flag $this->userData_change has been set, the function ->writeUC is called (which will save persistent user session data)
	 * If the flag $this->sesData_change has been set, the fe_session_data table is updated with the content of $this->sesData (deleting any old record, inserting new)
	 *
	 * @return	void
	 * @see fetchSessionData(), getKey(), setKey()
	 */
	function storeSessionData()	{
			// Saves UC and SesData if changed.
		if ($this->userData_change)	{
			$this->writeUC('');
		}
		if ($this->sesData_change)	{
			if ($this->id)	{
				$insertFields = array (
					'hash' => $this->id,
					'content' => serialize($this->sesData),
					'tstamp' => time()
				);
				$GLOBALS['TYPO3_DB']->exec_DELETEquery('fe_session_data', 'hash='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->id, 'fe_session_data'));
				$GLOBALS['TYPO3_DB']->exec_INSERTquery('fe_session_data', $insertFields);
			}
		}
	}

	/**
	 * Returns session data for the fe_user; Either persistent data following the fe_users uid/profile (requires login) or current-session based (not available when browse is closed, but does not require login)
	 *
	 * @param	string		Session data type; Either "user" (persistent, bound to fe_users profile) or "ses" (temporary, bound to current session cookie)
	 * @param	string		Key from the data array to return; The session data (in either case) is an array ($this->uc / $this->sesData) and this value determines which key to return the value for.
	 * @return	mixed		Returns whatever value there was in the array for the key, $key
	 * @see setKey()
	 */
	function getKey($type,$key) {
		if ($key)	{
			switch($type)	{
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
	 * @param	string		Session data type; Either "user" (persistent, bound to fe_users profile) or "ses" (temporary, bound to current session cookie)
	 * @param	string		Key from the data array to store incoming data in; The session data (in either case) is an array ($this->uc / $this->sesData) and this value determines in which key the $data value will be stored.
	 * @param	mixed		The data value to store in $key
	 * @return	void
	 * @see setKey(), storeSessionData(), record_registration()
	 */
	function setKey($type,$key,$data)	{
		if ($key)	{
			switch($type)	{
				case 'user':
					if ($this->user['uid'])	{
						$this->uc[$key]=$data;
						$this->userData_change=1;
					}
				break;
				case 'ses':
					$this->sesData[$key]=$data;
					$this->sesData_change=1;
				break;
			}
		}
	}

	/**
	 * Registration of records/"shopping basket" in session data
	 * This will take the input array, $recs, and merge into the current "recs" array found in the session data.
	 * If a change in the recs storage happens (which it probably does) the function setKey() is called in order to store the array again.
	 *
	 * @param	array		The data array to merge into/override the current recs values. The $recs array is constructed as [table]][uid] = scalar-value (eg. string/integer).
	 * @return	void
	 */
	function record_registration($recs)	{
		if ($recs['clear_all'])	{
			$this->setKey('ses','recs','');
		}
		$change=0;
		$recs_array=$this->getKey('ses','recs');
		reset($recs);
		while(list($table,$data)=each($recs))	{
			if (is_array($data))	{
				reset($data);
				while(list($rec_id,$value)=each($data))	{
					if ($value != $recs_array[$table][$rec_id])	{
						$recs_array[$table][$rec_id] = $value;
						$change=1;
					}
				}
			}
		}
		if ($change)	{
			$this->setKey('ses','recs',$recs_array);
		}
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['tslib/class.tslib_feuserauth.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['tslib/class.tslib_feuserauth.php']);
}
?>
