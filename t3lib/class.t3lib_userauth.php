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
 * Contains a base class for authentication of users in TYPO3, both frontend and backend.
 *
 * $Id$
 * Revised for TYPO3 3.6 July/2003 by Kasper Skaarhoj
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   89: class t3lib_userAuth
 *  160:     function start()
 *  262:     function check_authentication()
 *  423:     function redirect()
 *  436:     function logoff()
 *  451:     function gc()
 *  465:     function user_where_clause()
 *  479:     function ipLockClause()
 *  497:     function ipLockClause_remoteIPNumber($parts)
 *  518:     function hashLockClause()
 *  529:     function hashLockClause_getHashInt()
 *  545:     function writeUC($variable='')
 *  568:     function writelog($type,$action,$error,$details_nr,$details,$data,$tablename,$recuid,$recpid)
 *  577:     function checkLogFailures()
 *  586:     function unpack_uc($theUC='')
 *  602:     function pushModuleData($module,$data,$noSave=0)
 *  615:     function getModuleData($module,$type='')
 *  628:     function getSessionData($key)
 *  641:     function setAndSaveSessionData($key,$data)
 *  660:     function setBeUserByUid($uid)
 *  673:     function setBeUserByName($name)
 *
 * TOTAL FUNCTIONS: 20
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */











/**
 * Authentication of users in TYPO3
 *
 * This class is used to authenticate a login user.
 * The class is used by both the frontend and backend. In both cases this class is a parent class to beuserauth and feuserauth
 *
 * See Inside TYPO3 for more information about the API of the class and internal variables.
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_userAuth {
	var $global_database = '';		// Which global database to connect to
	var $session_table = '';		// Table to use for session data.
	var $name = '';					// Session/Cookie name
	var $get_name = '';				// Session/GET-var name

	var $user_table = '';			// Table in database with userdata
	var $username_column = '';		// Column for login-name
	var $userident_column = '';		// Column for password
	var $userid_column = '';		// Column for user-id
	var $lastLogin_column = '';

	var $enablecolumns = Array (
		'rootLevel' => '',			// Boolean: If true, 'AND pid=0' will be a part of the query...
		'disabled' => '',
		'starttime' => '',
		'endtime' => '',
		'deleted' => ''
	);

	var $formfield_uname = ''; 			// formfield with login-name
	var $formfield_uident = ''; 		// formfield with password
	var $formfield_chalvalue = '';		// formfield with a unique value which is used to encrypt the password and username
	var $formfield_status = ''; 		// formfield with status: *'login', 'logout'. If empty login is not verified.
	var $security_level = '';			// sets the level of security. *'normal' = clear-text. 'challenged' = hashed password/username from form in $formfield_uident. 'superchallenged' = hashed password hashed again with username.

	var $auth_include = '';				// this is the name of the include-file containing the login form. If not set, login CAN be anonymous. If set login IS needed.

	var $auth_timeout_field = 0;		// if > 0 : session-timeout in seconds. if string: The string is fieldname from the usertable where the timeout can be found.
	var $lifetime = 0;                  // 0 = Session-cookies. If session-cookies, the browser will stop session when the browser is closed. Else it keeps the session for $lifetime seconds.
	var $gc_time  = 24;               	// GarbageCollection. Purge all session data older than $gc_time hours.
	var $gc_probability = 1;			// Possibility (in percent) for GarbageCollection to be run.
	var $writeStdLog = 0;					// Decides if the writelog() function is called at login and logout
	var $writeAttemptLog = 0;				// If the writelog() functions is called if a login-attempt has be tried without success
	var $sendNoCacheHeaders = 1;		// If this is set, headers is sent to assure, caching is NOT done
	var $getFallBack = 0;				// If this is set, authentication is also accepted by the $_GET. Notice that the identification is NOT 128bit MD5 hash but reduced. This is done in order to minimize the size for mobile-devices, such as WAP-phones
	var $hash_length = 32;				// The ident-hash is normally 32 characters and should be! But if you are making sites for WAP-devices og other lowbandwidth stuff, you may shorten the length. Never let this value drop below 6. A length of 6 would give you more than 16 mio possibilities.
	var $getMethodEnabled = 0;			// Setting this flag true lets user-authetication happen from GET_VARS if POST_VARS are not set. Thus you may supply username/password from the URL.
	var $lockIP = 4;					// If set, will lock the session to the users IP address (all four numbers. Reducing to 1-3 means that only first, second or third part of the IP address is used).
	var $lockHashKeyWords = 'useragent';	// Keyword list (commalist with no spaces!): "useragent". Each keyword indicates some information that can be included in a integer hash made to lock down usersessions.

	var $warningEmail = '';				// warning -emailaddress:
	var $warningPeriod = 3600;			// Period back in time (in seconds) in which number of failed logins are collected
	var $warningMax = 3;				// The maximum accepted number of warnings before an email is sent
	var $checkPid=1;					// If set, the user-record must $checkPid_value as pid
	var $checkPid_value=0;				// The pid, the user-record must have as page-id

		// Internals
	var $id;							// Internal: Will contain session_id (MD5-hash)
	var $cookieId;						// Internal: Will contain the session_id gotten from cookie or GET method. This is used in statistics as a reliable cookie (one which is known to come from $_COOKIE).
	var $loginSessionStarted = 0;		// Will be set to 1 if the login session is actually written during auth-check.

	var $user;							// Internal: Will contain user- AND session-data from database (joined tables)
	var $get_URL_ID = '';				// Internal: Will will be set to the url--ready (eg. '&login=ab7ef8d...') GET-auth-var if getFallBack is true. Should be inserted in links!

	var $forceSetCookie=0;				// Will force the session cookie to be set everytime (lifetime must be 0)
	var $dontSetCookie=0;				// Will prevent the setting of the session cookie (takes precedence over forceSetCookie)
	var $challengeStoredInCookie=0;		// If set, the challenge value will be stored in a session as well so the server can check that is was not forged.


	/**
	 * Starts a user session
	 * Typical configurations will:
	 * a) check if session cookie was set and if not, set one,
	 * b) check if a password/username was sent and if so, try to authenticate the user
	 * c) Lookup a session attached to a user and check timeout etc.
	 * d) Garbage collection, setting of no-cache headers.
	 * If a user is authenticated the database record of the user (array) will be set in the ->user internal variable.
	 *
	 * @return	void
	 */
	function start() {

			// Init vars.
		$mode='';
		$new_id = false;				// Default: not a new session
		$id = isset($_COOKIE[$this->name]) ? stripslashes($_COOKIE[$this->name]) : '';	// $id is set to ses_id if cookie is present. Else set to false, which will start a new session
		$this->hash_length = t3lib_div::intInRange($this->hash_length,6,32);

			// If fallback to get mode....
		if (!$id && $this->getFallBack && $this->get_name)	{
			$id = isset($_GET[$this->get_name]) ? t3lib_div::_GET($this->get_name) : '';
			if (strlen($id)!=$this->hash_length)	$id='';
			$mode='get';
		}
		$this->cookieId = $id;

		if (!$id)	{					// If new session...
    		$id = substr(md5(uniqid('')),0,$this->hash_length);		// New random session-$id is made
			$new_id = true;				// New session
		}
			// Internal var 'id' is set
		$this->id = $id;
		if ($mode=='get' && $this->getFallBack && $this->get_name)	{	// If fallback to get mode....
			$this->get_URL_ID = '&'.$this->get_name.'='.$id;
		}
		$this->user = '';				// Make certain that NO user is set initially

			// Setting cookies
        if (($new_id || $this->forceSetCookie) && $this->lifetime==0 ) {		// If new session and the cookie is a sessioncookie, we need to set it only once!
          if (!$this->dontSetCookie)	SetCookie($this->name, $id, 0, '/');		// Cookie is set
        }
        if ($this->lifetime > 0) {		// If it is NOT a session-cookie, we need to refresh it.
          if (!$this->dontSetCookie)	SetCookie($this->name, $id, time()+$this->lifetime, '/');
        }

			// Check to see if anyone has submitted login-information and if so register the user with the session. $this->user[uid] may be used to write log...
		if ($this->formfield_status)	{
			$this->check_authentication();
		}
		unset($this->user);				// Make certain that NO user is set initially. ->check_authentication may have set a session-record which will provide us with a user record in the next section:


			// The session_id is used to find user in the database. Two tables are joined: The session-table with user_id of the session and the usertable with its primary key
		$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'*',
						$this->session_table.','.$this->user_table,
						$this->session_table.'.ses_id = '.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->id, $this->session_table).'
							AND '.$this->session_table.'.ses_name = '.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->name, $this->session_table).'
							AND '.$this->session_table.'.ses_userid = '.$this->user_table.'.'.$this->userid_column.'
							'.$this->ipLockClause().'
							'.$this->hashLockClause().'
							'.$this->user_where_clause()
					);

		if ($this->user = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres))	{
				// A user was found
			if (is_string($this->auth_timeout_field))	{
				$timeout = intval($this->user[$this->auth_timeout_field]);		// Get timeout-time from usertable
			} else {
				$timeout = intval($this->auth_timeout_field);					// Get timeout from object
			}
				// If timeout > 0 (true) and currenttime has not exceeded the latest sessions-time plus the timeout in seconds then accept user
				// Option later on: We could check that last update was at least x seconds ago in order not to update twice in a row if one script redirects to another...
			if ($timeout>0 && ($GLOBALS['EXEC_TIME'] < ($this->user['ses_tstamp']+$timeout)))	{
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
											$this->session_table,
											'ses_id='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->id, $this->session_table).'
												AND ses_name='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->name, $this->session_table),
											array('ses_tstamp' => $GLOBALS['EXEC_TIME'])
										);
					$this->user['ses_tstamp'] = $GLOBALS['EXEC_TIME'];	// Make sure that the timestamp is also updated in the array
			} else {
				$this->user = '';
				$this->logoff();		// delete any user set...
			}
		} else {
			$this->logoff();		// delete any user set...
		}

		$this->redirect();		// If any redirection (inclusion of file) then it will happen in this function

			// Set all posible headers that could ensure that the script is not cached on the client-side
		if ($this->sendNoCacheHeaders)	{
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
			header('Expires: 0');
			header('Cache-Control: no-cache, must-revalidate');
			header('Pragma: no-cache');
		}

			// If we're lucky we'll get to clean up old sessions....
		if ((rand()%100) <= $this->gc_probability) {
			$this->gc();
		}
	}

	/**
	 * Checks if a submission of username and password is present
	 *
	 * @return	string		Returns "login" if login, "logout" if logout, or empty if $F_status was none of these values.
	 * @internal
	 */
	function check_authentication() {

			// The values fetched from input variables here are supposed to already BE slashed...
		if ($this->getMethodEnabled)	{
			$F_status = t3lib_div::_GP($this->formfield_status);
			$F_uname = t3lib_div::_GP($this->formfield_uname);
			$F_uident = t3lib_div::_GP($this->formfield_uident);
			$F_chalvalue = t3lib_div::_GP($this->formfield_chalvalue);
		} else {
			$F_status = t3lib_div::_POST($this->formfield_status);
			$F_uname = t3lib_div::_POST($this->formfield_uname);
			$F_uident = t3lib_div::_POST($this->formfield_uident);
			$F_chalvalue = t3lib_div::_POST($this->formfield_chalvalue);
		}

		switch ($F_status)	{
			case 'login':
				$refInfo=parse_url(t3lib_div::getIndpEnv('HTTP_REFERER'));
				$httpHost = t3lib_div::getIndpEnv('TYPO3_HOST_ONLY');
				if (!$this->getMethodEnabled && ($httpHost!=$refInfo['host'] && !$GLOBALS['TYPO3_CONF_VARS']['SYS']['doNotCheckReferer']))	{
					die('Error: This host address ("'.$httpHost.'") and the referer host ("'.$refInfo['host'].'") mismatches!<br />
						It\'s possible that the environment variable HTTP_REFERER is not passed to the script because of a proxy.<br />
						The site administrator can disable this check in the "All Configuration" section of the Install Tool (flag: TYPO3_CONF_VARS[SYS][doNotCheckReferer]).');
				}
				if ($F_uident && $F_uname)	{

						// Reset this flag
					$loginFailure=0;

						// delete old user session if any
					$this->logoff();

						// Look up the new user by the username:
					$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
									'*',
									$this->user_table,
									($this->checkPid ? 'pid IN ('.$GLOBALS['TYPO3_DB']->cleanIntList($this->checkPid_value).') AND ' : '').
										$this->username_column.'='.$GLOBALS['TYPO3_DB']->fullQuoteStr($F_uname, $this->user_table).' '.
										$this->user_where_clause()
							);

						// Enter, if a user was found:
					if ($tempuser = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres))	{
							// Internal user record set (temporarily)
						$this->user = $tempuser;

							// Default: not OK - will be set true if password matches in the comparison hereafter
						$OK = false;

							// check the password
						switch ($this->security_level)	{
							case 'superchallenged':		// If superchallenged the password in the database ($tempuser[$this->userident_column]) must be a md5-hash of the original password.
							case 'challenged':

								if ($this->challengeStoredInCookie)	{
									session_start();
									if ($_SESSION['login_challenge'] !== $F_chalvalue) {
										$this->logoff();
										return 'login';
									}
								}

								if (!strcmp($F_uident,md5($tempuser[$this->username_column].':'.$tempuser[$this->userident_column].':'.$F_chalvalue)))	{
									$OK = true;
								};
							break;
							default:	// normal
								if (!strcmp($F_uident,$tempuser[$this->userident_column]))	{
									$OK = true;
								};
							break;
						}

							// Write session-record in case user was verified OK
						if ($OK)	{
								// Checking the domain (lockToDomain)
							if ($this->user['lockToDomain'] && $this->user['lockToDomain']!=t3lib_div::getIndpEnv('HTTP_HOST'))	{
									// Lock domain didn't match, so error:
								if ($this->writeAttemptLog) {
									$this->writelog(255,3,3,1,
										"Login-attempt from %s (%s), username '%s', locked domain '%s' did not match '%s'!",
										Array(t3lib_div::getIndpEnv('REMOTE_ADDR'),t3lib_div::getIndpEnv('REMOTE_HOST'),$F_uname,$this->user['lockToDomain'],t3lib_div::getIndpEnv('HTTP_HOST')));
								}
								$loginFailure=1;
							} else {
									// The loginsession is started.
								$this->loginSessionStarted = 1;

									// Inserting session record:
								$insertFields = array(
									'ses_id' => $this->id,
									'ses_name' => $this->name,
									'ses_iplock' => $this->user['disableIPlock'] ? '[DISABLED]' : $this->ipLockClause_remoteIPNumber($this->lockIP),
									'ses_hashlock' => $this->hashLockClause_getHashInt(),
									'ses_userid' => $tempuser[$this->userid_column],
									'ses_tstamp' => $GLOBALS['EXEC_TIME']
								);
								$GLOBALS['TYPO3_DB']->exec_INSERTquery($this->session_table, $insertFields);

									// Updating column carrying information about last login.
								if ($this->lastLogin_column)	{
									$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
															$this->user_table,
															$this->userid_column.'='.$GLOBALS['TYPO3_DB']->fullQuoteStr($tempuser[$this->userid_column], $this->user_table),
															array($this->lastLogin_column => $GLOBALS['EXEC_TIME'])
														);
								}
									// User logged in - write that to the log!
								if ($this->writeStdLog) {
									$this->writelog(255,1,0,1,
										'User %s logged in from %s (%s)',
										Array($this->user['username'],t3lib_div::getIndpEnv('REMOTE_ADDR'),t3lib_div::getIndpEnv('REMOTE_HOST')));
								}
							}
						} else {
								// Failed login attempt (wrong password) - write that to the log!
							if ($this->writeAttemptLog) {
								$this->writelog(255,3,3,1,
									"Login-attempt from %s (%s), username '%s', password not accepted!",
									Array(t3lib_div::getIndpEnv('REMOTE_ADDR'),t3lib_div::getIndpEnv('REMOTE_HOST'),$F_uname));
							}
							$loginFailure=1;
						}
							// Make sure to clear the user again!!
						unset($this->user);
					} else {
							// Failed login attempt (no username found)
						if ($this->writeAttemptLog) {
							$this->writelog(255,3,3,2,
								"Login-attempt from %s (%s), username '%s' not found!!",
								Array(t3lib_div::getIndpEnv('REMOTE_ADDR'),t3lib_div::getIndpEnv('REMOTE_HOST'),$F_uname));	// Logout written to log
						}
						$loginFailure=1;
					}

						// If there were a login failure, check to see if a warning email should be sent:
					if ($loginFailure)	{
						$this->checkLogFailures($this->warningEmail, $this->warningPeriod, $this->warningMax);
					}
				}

					// Return "login" - since this was the $F_status
				return 'login';
			break;
			case 'logout':
					// Just logout:
				if ($this->writeStdLog) 	$this->writelog(255,2,0,2,'User %s logged out',Array($this->user['username']));	// Logout written to log
				$this->logoff();

					// Return "logout" - since this was the $F_status
				return 'logout';
			break;
		}
	}

	/**
	 * Redirect to somewhere. Obsolete, depreciated etc.
	 *
	 * @return	void
	 * @ignore
	 */
	function redirect() {
		if (!$this->userid && $this->auth_url)	{	 // if no userid AND an include-document for login is given
			include ($this->auth_include);
			exit;
		}
	}

	/**
	 * Log out current user!
	 * Removes the current session record, sets the internal ->user array to a blank string; Thereby the current user (if any) is effectively logged out!
	 *
	 * @return	void
	 */
	function logoff() {
		$GLOBALS['TYPO3_DB']->exec_DELETEquery(
					$this->session_table,
					'ses_id = '.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->id, $this->session_table).'
						AND ses_name = '.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->name, $this->session_table)
				);
		$this->user = "";
	}

	/**
	 * Garbage collector, removing old expired sessions.
	 *
	 * @return	void
	 * @internal
	 */
	function gc() {
		$GLOBALS['TYPO3_DB']->exec_DELETEquery(
					$this->session_table,
					'ses_tstamp < '.intval(time()-($this->gc_time*60*60)).'
						AND ses_name = '.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->name, $this->session_table)
				);
	}

	/**
	 * This returns the where-clause needed to select the user with respect flags like deleted, hidden, starttime, endtime
	 *
	 * @return	string
	 * @access private
	 */
	function user_where_clause()	{
		return  (($this->enablecolumns['rootLevel']) ? 'AND '.$this->user_table.'.pid=0 ' : '').
				(($this->enablecolumns['disabled']) ? ' AND '.$this->user_table.'.'.$this->enablecolumns['disabled'].'=0' : '').
				(($this->enablecolumns['deleted']) ? ' AND '.$this->user_table.'.'.$this->enablecolumns['deleted'].'=0' : '').
				(($this->enablecolumns['starttime']) ? ' AND ('.$this->user_table.'.'.$this->enablecolumns['starttime'].'<='.time().')' : '').
				(($this->enablecolumns['endtime']) ? ' AND ('.$this->user_table.'.'.$this->enablecolumns['endtime'].'=0 OR '.$this->user_table.'.'.$this->enablecolumns['endtime'].'>'.time().')' : '');
	}

	/**
	 * This returns the where-clause needed to lock a user to the IP address
	 *
	 * @return	string
	 * @access private
	 */
	function ipLockClause()	{
		if ($this->lockIP)	{
			$wherePart = 'AND (
				'.$this->session_table.'.ses_iplock='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->ipLockClause_remoteIPNumber($this->lockIP),$this->session_table).'
				OR '.$this->session_table.'.ses_iplock=\'[DISABLED]\'
				)';
			return $wherePart;
		}
	}

	/**
	 * Returns the IP address to lock to.
	 * The IP address may be partial based on $parts.
	 *
	 * @param	integer		1-4: Indicates how many parts of the IP address to return. 4 means all, 1 means only first number.
	 * @return	string		(Partial) IP address for REMOTE_ADDR
	 * @access private
	 */
	function ipLockClause_remoteIPNumber($parts)	{
		$IP = t3lib_div::getIndpEnv('REMOTE_ADDR');

		if ($parts>=4)	{
			return $IP;
		} else {
			$parts = t3lib_div::intInRange($parts,1,3);
			$IPparts = explode('.',$IP);
			for($a=4;$a>$parts;$a--)	{
				unset($IPparts[$a-1]);
			}
			return implode('.',$IPparts);
		}
	}

	/**
	 * This returns the where-clause needed to lock a user to a hash integer
	 *
	 * @return	string
	 * @access private
	 */
	function hashLockClause()	{
		$wherePart = 'AND '.$this->session_table.'.ses_hashlock='.intval($this->hashLockClause_getHashInt());
		return $wherePart;
	}

	/**
	 * Creates hash integer to lock user to. Depends on configured keywords
	 *
	 * @return	integer		Hash integer
	 * @access private
	 */
	function hashLockClause_getHashInt()	{
		$hashStr = '';

		if (t3lib_div::inList($this->lockHashKeyWords,'useragent'))	$hashStr.=':'.t3lib_div::getIndpEnv('HTTP_USER_AGENT');

		return t3lib_div::md5int($hashStr);
	}

	/**
	 * This writes $variable to the user-record. This is a way of providing session-data.
	 * You can fetch the data again through $this->uc in this class!
	 * If $variable is not an array, $this->uc is saved!
	 *
	 * @param	array		An array you want to store for the user as session data. If $variable is not supplied (is blank string), the internal variable, ->uc, is stored by default
	 * @return	void
	 */
	function writeUC($variable='')	{
		if (is_array($this->user) && $this->user['uid'])	{
			if (!is_array($variable)) { $variable = $this->uc; }

			$GLOBALS['TYPO3_DB']->exec_UPDATEquery($this->user_table, 'uid='.intval($this->user['uid']), array('uc' => serialize($variable)));
		}
	}

	/**
	 * DUMMY: Writes to log database table (in some extension classes)
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
	function writelog($type,$action,$error,$details_nr,$details,$data,$tablename,$recuid,$recpid)	{
	}

	/**
	 * DUMMY: Check login failures (in some extension classes)
	 *
	 * @return	void
	 * @ignore
	 */
	function checkLogFailures()	{
	}

	/**
	 * Sets $theUC as the internal variable ->uc IF $theUC is an array. If $theUC is false, the 'uc' content from the ->user array will be unserialized and restored in ->uc
	 *
	 * @param	mixed		If an array, then set as ->uc, otherwise load from user record
	 * @return	void
	 */
	function unpack_uc($theUC='') {
		if (!$theUC) 	$theUC=unserialize($this->user['uc']);
		if (is_array($theUC))	{
			$this->uc=$theUC;
		}
	}

	/**
	 * Stores data for a module.
	 * The data is stored with the session id so you can even check upon retrieval if the module data is from a previous session or from the current session.
	 *
	 * @param	string		$module is the name of the module ($MCONF['name'])
	 * @param	mixed		$data is the data you want to store for that module (array, string, ...)
	 * @param	boolean		If $noSave is set, then the ->uc array (which carries all kinds of user data) is NOT written immediately, but must be written by some subsequent call.
	 * @return	void
	 */
	function pushModuleData($module,$data,$noSave=0)	{
		$this->uc['moduleData'][$module] = $data;
		$this->uc['moduleSessionID'][$module] = $this->id;
		if (!$noSave) $this->writeUC();
	}

	/**
	 * Gets module data for a module (from a loaded ->uc array)
	 *
	 * @param	string		$module is the name of the module ($MCONF['name'])
	 * @param	string		If $type = 'ses' then module data is returned only if it was stored in the current session, otherwise data from a previous session will be returned (if available).
	 * @return	mixed		The module data if available: $this->uc['moduleData'][$module];
	 */
	function getModuleData($module,$type='')	{
		if ($type!='ses' || $this->uc['moduleSessionID'][$module]==$this->id) {
			return $this->uc['moduleData'][$module];
		}
	}

	/**
	 * Returns the session data stored for $key.
	 * The data will last only for this login session since it is stored in the session table.
	 *
	 * @param	string		Pointer to an associative key in the session data array which is stored serialized in the field "ses_data" of the session table.
	 * @return	mixed
	 */
	function getSessionData($key)	{
		$sesDat = unserialize($this->user['ses_data']);
		return $sesDat[$key];
	}

	/**
	 * Sets the session data ($data) for $key and writes all session data (from ->user['ses_data']) to the database.
	 * The data will last only for this login session since it is stored in the session table.
	 *
	 * @param	string		Pointer to an associative key in the session data array which is stored serialized in the field "ses_data" of the session table.
	 * @param	mixed		The variable to store in index $key
	 * @return	void
	 */
	function setAndSaveSessionData($key,$data)	{
		$sesDat = unserialize($this->user['ses_data']);
		$sesDat[$key] = $data;
		$this->user['ses_data'] = serialize($sesDat);

		$GLOBALS['TYPO3_DB']->exec_UPDATEquery($this->session_table, 'ses_id='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->user['ses_id'], $this->session_table), array('ses_data' => $this->user['ses_data']));
	}

	/**
	 * Raw initialization of the be_user with uid=$uid
	 * This will circumvent all login procedures and select a be_users record from the database and set the content of ->user to the record selected. Thus the BE_USER object will appear like if a user was authenticated - however without a session id and the fields from the session table of course.
	 * Will check the users for disabled, start/endtime, etc. ($this->user_where_clause())
	 *
	 * @param	integer		The UID of the backend user to set in ->user
	 * @return	void
	 * @params integer	'uid' of be_users record to select and set.
	 * @internal
	 * @see SC_mod_tools_be_user_index::compareUsers(), SC_mod_user_setup_index::simulateUser(), freesite_admin::startCreate()
	 */
	function setBeUserByUid($uid)	{
		$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $this->user_table, 'uid='.intval($uid).' '.$this->user_where_clause());
		$this->user = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres);
	}

	/**
	 * Raw initialization of the be_user with username=$name
	 *
	 * @param	string		The username to look up.
	 * @return	void
	 * @see	t3lib_userAuth::setBeUserByUid()
	 * @internal
	 */
	function setBeUserByName($name)	{
		$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $this->user_table, 'username='.$GLOBALS['TYPO3_DB']->fullQuoteStr($name, $this->user_table).' '.$this->user_where_clause());
		$this->user = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres);
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_userauth.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_userauth.php']);
}
?>
