<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2004 Kasper Skaarhoj (kasper@typo3.com)
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
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @coauthor	René Fritz <r.fritz@colorcube.de>
 */



class tx_sv_auth extends tx_sv_authbase 	{


	/**
	 * find a user
	 *
	 * @return	mixed	user array or false
	 */
	function getUser()	{
		$user = false;

		if ($this->login['uident'] && $this->login['uname'])	{

				// Look up the new user by the username:
			$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
							'*',
							$this->db_user['table'],
								$this->db_user['username_column'].'="'.$GLOBALS['TYPO3_DB']->quoteStr($this->login['uname'], $this->db_user['table']).'"'.
								$this->db_user['check_pid_clause'].
								$this->db_user['enable_clause']
					);

			if ($dbres)	{
				$user = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres);
				$GLOBALS['TYPO3_DB']->sql_free_result($dbres);
			}
			
			if(!is_array($user)) {
					// Failed login attempt (no username found)
				if ($this->pObj->writeAttemptLog) {
					$this->writelog(255,3,3,2,
						"Login-attempt from %s (%s), username '%s' not found!!",
						Array($this->info['REMOTE_ADDR'], $this->info['REMOTE_HOST'], $this->login['uname']));	// Logout written to log
				}
			} else {
				if ($this->writeDevLog) 	t3lib_div::devLog('User found: '.t3lib_div::arrayToLogString($user, array($this->db_user['userid_column'],$this->db_user['username_column'])), 'tx_sv_auth');
			}
		}
		return $user;
	}

	/**
	 * authenticate a user
	 *
	 * @param	array 	Data of user.
	 * @param	array 	Information array. Holds submitted form data etc.
	 * @param	string 	subtype of the service which is used to call this service.
	 * @return	boolean
	 */
	function authUser($user)	{
		$OK = 100;

		if ($this->login['uident'] && $this->login['uname'])	{
			$OK = false;
			
				// check the password
			switch ($this->info['security_level'])	{
				case 'superchallenged':		// If superchallenged the password in the database ($user[$this->db_user['userident_column']]) must be a md5-hash of the original password.
				case 'challenged':
					if ((string)$this->login['uident'] == (string)md5($user[$this->db_user['username_column']].':'.$user[$this->db_user['userident_column']].':'.$this->login['chalvalue']))	{
						$OK = true;
					};
				break;
				default:	// normal
					if ((string)$this->login['uident'] == (string)$user[$this->db_user['userident_column']])	{
						$OK = true;
					};
				break;
			}

			if(!$OK)     {
					// Failed login attempt (wrong password) - write that to the log!
				if ($this->writeAttemptLog) {
					$this->writelog(255,3,3,1,
						"Login-attempt from %s (%s), username '%s', password not accepted!",
						Array($this->info['REMOTE_ADDR'], $this->info['REMOTE_HOST'], $this->login['uname']));
				}
				if ($this->writeDevLog) 	t3lib_div::devLog('Password not accepted: '.$this->login['uident'], 'tx_sv_auth', 2);
			}

				// Checking the domain (lockToDomain)
			if ($OK && $user['lockToDomain'] && $user['lockToDomain']!=$this->info['HTTP_HOST'])	{
					// Lock domain didn't match, so error:
				if ($this->writeAttemptLog) {
					$this->writelog(255,3,3,1,
						"Login-attempt from %s (%s), username '%s', locked domain '%s' did not match '%s'!",
						Array($this->info['REMOTE_ADDR'], $this->info['REMOTE_HOST'], $user[$this->db_user['username_column']], $user['lockToDomain'], $this->info['HTTP_HOST']));
				}
				$OK = false;
			}
		} elseif ($info['userSession'][$this->db_user['userid_column']]) {
				// There's already a cookie session user. That's fine
			$OK = true;
		}

		return $OK;
	}


	/**
	 * find usergroups
	 *
	 * @param	array 	Data of user.
	 * @param	array 	Group data array of already known groups. This is handy if you want select other related groups.
	 * @param	string 	subtype of the service which is used to call this service.
	 * @return	mixed 	groups array
	 */
	function getGroups($user, $knownGroups)	{

		$groupDataArr = array();
		
		if($this->mode=='getGroupsFE') 	{

			$groups = array();

			if (is_array($user) && $user[$this->db_user['usergroup_column']])	{
				$groups = t3lib_div::intExplode(',',$user[$this->db_user['usergroup_column']]);
			}


				// ADD group-numbers if the IPmask matches.
			if (is_array($this->pObj->TYPO3_CONF_VARS['FE']['IPmaskMountGroups']))	{
				foreach($this->pObj->TYPO3_CONF_VARS['FE']['IPmaskMountGroups'] as $IPel)	{
					if ($this->info['REMOTE_ADDR'] && $IPel[0] && t3lib_div::cmpIP($this->info['REMOTE_ADDR'],$IPel[0]))	{$groups[]=intval($IPel[1]);}
				}
			}
			$groups = array_unique($groups);

			if (count($groups))	{
				$list = implode($groups,',');
				
				if ($this->writeDevLog) 	t3lib_div::devLog('Get usergroups with id: '.$list, 'tx_sv_auth');

				$lockToDomain_SQL = ' AND (lockToDomain="" OR lockToDomain="'.$this->info['HTTP_HOST'].'")';
				if (!$this->info['showHiddenRecords'])	$hiddenP = 'AND NOT hidden ';
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $this->db_groups['table'], 'NOT deleted '.$hiddenP.' AND uid IN ('.$list.')'.$lockToDomain_SQL);
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
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sv/class.tx_sv_auth.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sv/class.tx_sv_auth.php']);
}
?>