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
 * Service 'User authentication' for the 'sv' extension.
 *
 * @author	René Fritz <r.fritz@colorcube.de>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   56: class tx_sv_auth extends tx_sv_authbase
 *   64:     function getUser()
 *   89:     function authUser($user)
 *  129:     function getGroups($user, $knownGroups)
 *
 * TOTAL FUNCTIONS: 3
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */



/**
 * Authentication services class
 *
 * @author	René Fritz <r.fritz@colorcube.de>
 * @package TYPO3
 * @subpackage tx_sv
 */
class tx_sv_auth extends tx_sv_authbase 	{


	/**
	 * Find a user (eg. look up the user record in database when a login is sent)
	 *
	 * @return	mixed		user array or false
	 */
	function getUser()	{
		$user = false;

		if ($this->login['status']=='login' && $this->login['uident'])	{

			$user = $this->fetchUserRecord($this->login['uname']);

			if(!is_array($user)) {
					// Failed login attempt (no username found)
				$this->writelog(255,3,3,2,
					"Login-attempt from %s (%s), username '%s' not found!!",
					Array($this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->login['uname']));	// Logout written to log
				t3lib_div::sysLog(
					sprintf( "Login-attempt from %s (%s), username '%s' not found!", $this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->login['uname'] ),
					'Core',
					0
				);
			} else {
				if ($this->writeDevLog) 	t3lib_div::devLog('User found: '.t3lib_div::arrayToLogString($user, array($this->db_user['userid_column'],$this->db_user['username_column'])), 'tx_sv_auth');
			}
		}
		return $user;
	}

	/**
	 * Authenticate a user (Check various conditions for the user that might invalidate its authentication, eg. password match, domain, IP, etc.)
	 *
	 * @param	array		Data of user.
	 * @return	boolean
	 */
	function authUser($user)	{
		$OK = 100;

		if ($this->login['uident'] && $this->login['uname'])	{

				// Checking password match for user:
			$OK = $this->compareUident($user, $this->login);

			if(!$OK)     {
					// Failed login attempt (wrong password) - write that to the log!
				if ($this->writeAttemptLog) {
					$this->writelog(255,3,3,1,
						"Login-attempt from %s (%s), username '%s', password not accepted!",
						Array($this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->login['uname']));
					t3lib_div::sysLog(
						sprintf( "Login-attempt from %s (%s), username '%s', password not accepted!", $this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->login['uname'] ),
						'Core',
						0
					);
				}
				if ($this->writeDevLog) 	t3lib_div::devLog('Password not accepted: '.$this->login['uident'], 'tx_sv_auth', 2);
			}

				// Checking the domain (lockToDomain)
			if ($OK && $user['lockToDomain'] && $user['lockToDomain']!=$this->authInfo['HTTP_HOST'])	{
					// Lock domain didn't match, so error:
				if ($this->writeAttemptLog) {
					$this->writelog(255,3,3,1,
						"Login-attempt from %s (%s), username '%s', locked domain '%s' did not match '%s'!",
						Array($this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $user[$this->db_user['username_column']], $user['lockToDomain'], $this->authInfo['HTTP_HOST']));
					t3lib_div::sysLog(
						sprintf( "Login-attempt from %s (%s), username '%s', locked domain '%s' did not match '%s'!", $this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $user[$this->db_user['username_column']], $user['lockToDomain'], $this->authInfo['HTTP_HOST'] ),
						'Core',
						0
					);
				}
				$OK = false;
			}
		}

		return $OK;
	}

	/**
	 * Find usergroup records, currently only for frontend
	 *
	 * @param	array		Data of user.
	 * @param	array		Group data array of already known groups. This is handy if you want select other related groups. Keys in this array are unique IDs of those groups.
	 * @return	mixed		Groups array, keys = uid which must be unique
	 */
	function getGroups($user, $knownGroups)	{
		global $TYPO3_CONF_VARS;

		$groupDataArr = array();

		if($this->mode=='getGroupsFE')	{

			$groups = array();
			if (is_array($user) && $user[$this->db_user['usergroup_column']])	{
				$groupList = $user[$this->db_user['usergroup_column']];
				$groups = array();
				$this->getSubGroups($groupList,'',$groups);
			}

				// ADD group-numbers if the IPmask matches.
			if (is_array($TYPO3_CONF_VARS['FE']['IPmaskMountGroups']))	{
				foreach($TYPO3_CONF_VARS['FE']['IPmaskMountGroups'] as $IPel)	{
					if ($this->authInfo['REMOTE_ADDR'] && $IPel[0] && t3lib_div::cmpIP($this->authInfo['REMOTE_ADDR'],$IPel[0]))	{$groups[]=intval($IPel[1]);}
				}
			}

			$groups = array_unique($groups);

			if (count($groups))	{
				$list = implode(',',$groups);

				if ($this->writeDevLog) 	t3lib_div::devLog('Get usergroups with id: '.$list, 'tx_sv_auth');

				$lockToDomain_SQL = ' AND (lockToDomain=\'\' OR lockToDomain IS NULL OR lockToDomain=\''.$this->authInfo['HTTP_HOST'].'\')';
				if (!$this->authInfo['showHiddenRecords'])	$hiddenP = 'AND hidden=0 ';
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $this->db_groups['table'], 'deleted=0 '.$hiddenP.' AND uid IN ('.$list.')'.$lockToDomain_SQL);
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
					$groupDataArr[$row['uid']] = $row;
				}
				if ($res)	$GLOBALS['TYPO3_DB']->sql_free_result($res);

			} else {
				if ($this->writeDevLog) 	t3lib_div::devLog('No usergroups found.', 'tx_sv_auth', 2);
			}
		} elseif ($this->mode=='getGroupsBE') {

			# Get the BE groups here
			# still needs to be implemented in t3lib_userauthgroup
		}

		return $groupDataArr;
	}

	/**
	 * Fetches subgroups of groups. Function is called recursively for each subgroup.
	 * Function was previously copied from t3lib_userAuthGroup->fetchGroups and has been slightly modified.
	 *
	 * @param	string		Commalist of fe_groups uid numbers
	 * @param	string		List of already processed fe_groups-uids so the function will not fall into a eternal recursion.
	 * @return	array
	 * @access private
	 */
	function getSubGroups($grList, $idList='', &$groups)	{

			// Fetching records of the groups in $grList (which are not blocked by lockedToDomain either):
		$lockToDomain_SQL = ' AND (lockToDomain=\'\' OR lockToDomain IS NULL OR lockToDomain=\''.$this->authInfo['HTTP_HOST'].'\')';
		if (!$this->authInfo['showHiddenRecords'])	$hiddenP = 'AND hidden=0 ';
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,subgroup', 'fe_groups', 'deleted=0 '.$hiddenP.' AND uid IN ('.$grList.')'.$lockToDomain_SQL);

		$groupRows = array();	// Internal group record storage

			// The groups array is filled
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			if(!in_array($row['uid'], $groups))	{ $groups[] = $row['uid']; }
			$groupRows[$row['uid']] = $row;
		}

			// Traversing records in the correct order
		$include_staticArr = t3lib_div::intExplode(',', $grList);
		foreach($include_staticArr as $uid)	{	// traversing list

				// Get row:
			$row=$groupRows[$uid];
			if (is_array($row) && !t3lib_div::inList($idList,$uid))	{	// Must be an array and $uid should not be in the idList, because then it is somewhere previously in the grouplist

					// Include sub groups
				if (trim($row['subgroup']))	{
					$theList = implode(',',t3lib_div::intExplode(',',$row['subgroup']));	// Make integer list
					$this->getSubGroups($theList, $idList.','.$uid, $groups);		// Call recursively, pass along list of already processed groups so they are not recursed again.
				}
			}
		}
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sv/class.tx_sv_auth.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sv/class.tx_sv_auth.php']);
}
?>