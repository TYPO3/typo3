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
 * Contains an extension class specifically for authentication/initialization of backend users in TYPO3
 *
 * $Id$
 * Revised for TYPO3 3.6 July/2003 by Kasper Skaarhoj
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  112: class t3lib_userAuthGroup extends t3lib_userAuth 
 *
 *              SECTION: Permission checking functions:
 *  170:     function isAdmin()	
 *  182:     function isMemberOfGroup($groupId)	
 *  204:     function doesUserHaveAccess($row,$perms)	
 *  221:     function isInWebMount($id,$readPerms='',$exitOnError=0)	
 *  248:     function modAccess($conf,$exitOnError)	
 *  284:     function getPagePermsClause($perms)	
 *  310:     function calcPerms($row)	
 *  332:     function isRTE()	
 *  358:     function check ($type,$value)	
 *  375:     function isPSet($lCP,$table,$type='')	
 *  392:     function mayMakeShortcut()	
 *
 *              SECTION: Miscellaneous functions
 *  420:     function getTSConfig($objectString,$config='')	
 *  446:     function getTSConfigVal($objectString)	
 *  458:     function getTSConfigProp($objectString)	
 *  470:     function inList($in_list,$item)	
 *  480:     function returnWebmounts()	
 *  490:     function returnFilemounts()	
 *
 *              SECTION: Authentication methods
 *  520:     function fetchGroupData()	
 *  645:     function fetchGroups($grList,$idList='')	
 *  719:     function setCachedList($cList)	
 *  740:     function addFileMount($title, $altTitle, $path, $webspace, $type)	
 *  787:     function addTScomment($str)	
 *
 *              SECTION: Logging
 *  834:     function writelog($type,$action,$error,$details_nr,$details,$data,$tablename='',$recuid='',$recpid='',$event_pid=-1,$NEWid='') 
 *  871:     function checkLogFailures($email, $secondsBack=3600, $max=3)	
 *
 * TOTAL FUNCTIONS: 24
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

	// Need this for parsing User TSconfig
require_once (PATH_t3lib.'class.t3lib_tsparser.php'); 





















/**
 * Extension to class.t3lib_userauth.php; Authentication of users in TYPO3 Backend
 * 
 * Actually this class is extended again by t3lib_beuserauth which is the actual backend user class that will be instantiated.
 * In fact the two classes t3lib_beuserauth and this class could just as well be one, single class since t3lib_userauthgroup is not - to my knowledge - used separately elsewhere. But for historical reasons they are two separate classes.
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_userAuthGroup extends t3lib_userAuth {
	var $usergroup_column = 'usergroup';		// Should be set to the usergroup-column (id-list) in the user-record
	var $usergroup_table = 'be_groups';			// The name of the group-table

		// internal
	var $groupData = Array(				// This array holds lists of eg. tables, fields and other values related to the permission-system. See fetchGroupData
		'filemounts' => Array()			// Filemounts are loaded here
	);

	var $userGroups = Array();			// This array will hold the groups that the user is a member of
	var $userGroupsUID = Array();		// This array holds the uid's of the groups in the listed order
	var $groupList ='';					// This is $this->userGroupsUID imploded to a comma list... Will correspond to the 'usergroup_cached_list'
	var $dataLists=array(				// Used internally to accumulate data for the user-group. DONT USE THIS EXTERNALLY! Use $this->groupData instead
		'webmount_list'=>'',
		'filemount_list'=>'',
		'modList'=>'',
		'tables_select'=>'',
		'tables_modify'=>'',
		'pagetypes_select'=>'',
		'non_exclude_fields'=>''
	);
	var $includeHierarchy=array();		// For debugging/display of order in which subgroups are included.
	var $includeGroupArray=array();		// List of group_id's in the order they are processed.

	var $OS='';							// Set to 'WIN', if windows
	var $TSdataArray=array();			// Used to accumulate the TSconfig data of the user
	var $userTS_text = '';				// Contains the non-parsed user TSconfig
	var $userTS = array();				// Contains the parsed user TSconfig
	var $userTSUpdated=0;				// Set internally if the user TSconfig was parsed and needs to be cached.
	var $userTS_dontGetCached=0;		// Set this from outside if you want the user TSconfig to ALWAYS be parsed and not fetched from cache.
	







	







	/************************************
	 *
	 * Permission checking functions:
	 *
	 ************************************/

	/**
	 * Returns true if user is admin
	 * Basically this function evaluates if the ->user[admin] field has bit 0 set. If so, user is admin.
	 * 
	 * @return	boolean		
	 */
	function isAdmin()	{
		return (($this->user['admin']&1) ==1);
	}
	
	/**
	 * Returns true if the current user is a member of group $groupId
	 * $groupId must be set. $this->groupList must contain groups
	 * Will return true also if the user is a member of a group through subgroups.
	 * 
	 * @param	integer		Group ID to look for in $this->groupList
	 * @return	boolean		
	 */
	function isMemberOfGroup($groupId)	{
		$groupId = intval($groupId);
		if ($this->groupList && $groupId)	{
			return $this->inList($this->groupList, $groupId);
		}
	}
	
	/**
	 * Checks if the permissions is granted based on a page-record ($row) and $perms (binary and'ed)
	 * 
	 * Bits for permissions, see $perms variable:
	 * 
	 * 		1 - Show:	See/Copy page and the pagecontent.
	 * 		16- Edit pagecontent: Change/Add/Delete/Move pagecontent.
	 * 		2- Edit page: Change/Move the page, eg. change title, startdate, hidden.
	 * 		4- Delete page: Delete the page and pagecontent.
	 * 		8- New pages: Create new pages under the page.
	 * 
	 * @param	array		$row is the pagerow for which the permissions is checked
	 * @param	integer		$perms is the binary representation of the permission we are going to check. Every bit in this number represents a permission that must be set. See function explanation.
	 * @return	boolean		True or False upon evaluation
	 */
	function doesUserHaveAccess($row,$perms)	{	
		$userPerms = $this->calcPerms($row);
		return ($userPerms & $perms)==$perms;
	}
	
	/**
	 * Checks if the page id, $id, is found within the webmounts set up for the user.
	 * This should ALWAYS be checked for any page id a user works with, whether it's about reading, writing or whatever.
	 * The point is that this will add the security that a user can NEVER touch parts outside his mounted pages in the page tree. This is otherwise possible if the raw page permissions allows for it. So this security check just makes it easier to make safe user configurations.
	 * If the user is admin OR if this feature is disabled (fx. by setting TYPO3_CONF_VARS['BE']['lockBeUserToDBmounts']=0) then it returns "1" right away
	 * Otherwise the function will return the uid of the webmount which was first found in the rootline of the input page $id
	 * 
	 * @param	integer		Page ID to check
	 * @param	string		Content of "->getPagePermsClause(1)" (read-permissions). If not set, they will be internally calculated (but if you have the correct value right away you can save that database lookup!)
	 * @param	boolean		If set, then the function will exit with an error message.
	 * @return	integer		The page UID of a page in the rootline that matched a mount point
	 */
	function isInWebMount($id,$readPerms='',$exitOnError=0)	{
		if (!$GLOBALS['TYPO3_CONF_VARS']['BE']['lockBeUserToDBmounts'] || $this->isAdmin())	return 1;
		$id = intval($id);
		if (!$readPerms)	$readPerms = $this->getPagePermsClause(1);
		if ($id>0)	{
			$wM=$this->returnWebmounts();
			$rL=t3lib_BEfunc::BEgetRootLine($id,' AND '.$readPerms);
			reset($rL);
			while(list(,$v)=each($rL))	{
				if ($v['uid'] && in_array($v['uid'],$wM))	{
					return $v['uid'];
				}
			}
		}
		if ($exitOnError)	{
			t3lib_BEfunc::typo3PrintError ('Access Error','This page is not within your DB-mounts',0);
			exit;
		}
	}
	
	/**
	 * Checks access to a backend module with the $MCONF passed as first argument
	 * 
	 * @param	array		$MCONF array of a backend module!
	 * @param	boolean		If set, an array will issue an error message and exit.
	 * @return	boolean		Will return true if $MCONF['access'] is not set at all, if the BE_USER is admin or if the module is enabled in the be_users/be_groups records of the user (specifically enabled). Will return false if the module name is not even found in $TBE_MODULES
	 */
	function modAccess($conf,$exitOnError)	{
		if (!t3lib_BEfunc::isModuleSetInTBE_MODULES($conf['name']))	{
			if ($exitOnError)	{
				t3lib_BEfunc::typo3PrintError ('Fatal Error','This module "'.$conf['name'].'" is not enabled in TBE_MODULES',0);
				exit;
			}
			return false;
		}

			// Returns true if conf[access] is not set at all or if the user is admin
		if (!$conf['access']  ||  $this->isAdmin()) return true;

			// If $conf['access'] is set but not with 'admin' then we return true, if the module is found in the modList
		if (!strstr($conf['access'],'admin') && $conf['name'])	{
			$acs = $this->check('modules',$conf['name']);
		}
		if (!$acs && $exitOnError)	{
			t3lib_BEfunc::typo3PrintError ('Access Error','You don\'t have access to this module.',0);
			exit;
		} else return $acs;
	}
	
	/**
	 * Returns a WHERE-clause for the pages-table where user permissions according to input argument, $perms, is validated.
	 * $perms is the 'mask' used to select. Fx. if $perms is 1 then you'll get all pages that a user can actually see!
	 * 	 	2^0 = show (1)
	 * 		2^1 = edit (2)
	 * 		2^2 = delete (4)
	 * 		2^3 = new (8)
	 * If the user is 'admin' " 1=1" is returned (no effect)
	 * If the user is not set at all (->user is not an array), then " 1=0" is returned (will cause no selection results at all)
	 * The 95% use of this function is "->getPagePermsClause(1)" which will return WHERE clauses for *selecting* pages in backend listings - in other words will this check read permissions.
	 * 
	 * @param	integer		Permission mask to use, see function description
	 * @return	string		Part of where clause. Prefix " AND " to this.
	 */
	function getPagePermsClause($perms)	{
		if (is_array($this->user))	{
			if ($this->isAdmin())	{
				return ' 1=1';
			}

			$perms = intval($perms);	// Make sure it's integer.
			$str= ' ('.
				'(pages.perms_everybody & '.$perms.' = '.$perms.')'.	// Everybody
				'OR(pages.perms_userid = '.$this->user['uid'].' AND pages.perms_user & '.$perms.' = '.$perms.')';	// User
			if ($this->groupList){$str.='OR(pages.perms_groupid in ('.$this->groupList.') AND pages.perms_group & '.$perms.' = '.$perms.')';}	// Group (if any is set)
			$str.=')';
			return $str;
		} else {
			return ' 1=0';
		}
	}
	
	/**
	 * Returns a combined binary representation of the current users permissions for the page-record, $row.
	 * The perms for user, group and everybody is OR'ed together (provided that the page-owner is the user and for the groups that the user is a member of the group
	 * If the user is admin, 31 is returned	(full permissions for all five flags)
	 * 
	 * @param	array		Input page row with all perms_* fields available.
	 * @return	integer		Bitwise representation of the users permissions in relation to input page row, $row
	 */
	function calcPerms($row)	{
		if ($this->isAdmin()) {return 31;}		// Return 31 for admin users.
		
		$out=0;	
		if (isset($row['perms_userid']) && isset($row['perms_user']) && isset($row['perms_groupid']) && isset($row['perms_group']) && isset($row['perms_everybody']) && isset($this->groupList))	{
			if ($this->user['uid']==$row['perms_userid'])	{
				$out|=$row['perms_user'];
			}
			if ($this->isMemberOfGroup($row['perms_groupid']))	{
				$out|=$row['perms_group'];
			}
			$out|=$row['perms_everybody'];
		}
		return $out;
	}

	/**
	 * Returns true if the RTE (Rich Text Editor) can be enabled for the user
	 * Strictly this is not permissions being checked but rather a series of settings like a loaded extension, browser/client type and a configuration option in ->uc[edit_RTE]
	 * 
	 * @return	boolean		
	 */
	function isRTE()	{
		global $CLIENT;
		if (
			t3lib_extMgm::isLoaded('rte') && 
			$CLIENT['BROWSER']=='msie' && 
			$CLIENT['SYSTEM']=='win' && 
			$CLIENT['VERSION']>=5 && 
			$this->uc['edit_RTE'] &&
			$GLOBALS['TYPO3_CONF_VARS']['BE']['RTEenabled']
			)	{
				return 1;
		} else {
			return 0;
		}
	}
	
	/**
	 * Returns true if the $value is found in the list in a $this->groupData[] index pointed to by $type (array key). 
	 * Can thus be users to check for modules, exclude-fields, select/modify permissions for tables etc.
	 * If user is admin true is also returned
	 * Please see the document Inside TYPO3 for examples.
	 * 
	 * @param	string		The type value; "webmounts", "filemounts", "pagetypes_select", "tables_select", "tables_modify", "non_exclude_fields", "modules"
	 * @param	string		String to search for in the groupData-list
	 * @return	boolean		True if permission is granted (that is, the value was found in the groupData list - or the BE_USER is "admin")
	 */
	function check ($type,$value)	{
		if (isset($this->groupData[$type]))	{
			if ($this->isAdmin() || $this->inList($this->groupData[$type],$value)) {
				return 1;
			}
		}
	}

	/**
	 * Will check a type of permission against the compiled permission integer, $lCP, and in relation to table, $table
	 * 
	 * @param	integer		$lCP could typically be the "compiled permissions" integer returned by ->calcPerms
	 * @param	string		$table is the tablename to check: If "pages" table then edit,new,delete and editcontent permissions can be checked. Other tables will be checked for "editcontent" only (and $type will be ignored)
	 * @param	string		For $table='pages' this can be 'edit' (2), 'new' (8 or 16), 'delete' (4), 'editcontent' (16). For all other tables this is ignored. (16 is used)
	 * @return	boolean		
	 * @access private
	 */
	function isPSet($lCP,$table,$type='')	{
		if ($this->isAdmin())	return true;
		if ($table=='pages')	{
			if ($type=='edit')	return $lCP & 2;
			if ($type=='new')	return ($lCP & 8) || ($lCP & 16);	// Create new page OR pagecontent
			if ($type=='delete')	return $lCP & 4;
			if ($type=='editcontent')	return $lCP & 16;
		} else {
			return $lCP & 16;
		}
	}
	
	/**
	 * Returns true if the BE_USER is allowed to *create* shortcuts in the backend modules
	 * 
	 * @return	boolean		
	 */
	function mayMakeShortcut()	{
		return $this->getTSConfigVal('options.shortcutFrame') && !$this->getTSConfigVal('options.mayNotCreateEditShortcuts');
	}

	
	
	





	
	/*************************************
	 *
	 * Miscellaneous functions
	 *
	 *************************************/

	/**
	 * Returns the value/properties of a TS-object as given by $objectString, eg. 'options.dontMountAdminMounts'
	 * Nice (general!) function for returning a part of a TypoScript array!
	 * 
	 * @param	string		Pointer to an "object" in the TypoScript array, fx. 'options.dontMountAdminMounts'
	 * @param	array		Optional TSconfig array: If array, then this is used and not $this->userTS. If not array, $this->userTS is used.
	 * @return	array		An array with two keys, "value" and "properties" where "value" is a string with the value of the objectsting and "properties" is an array with the properties of the objectstring.
	 * @params	array	An array with the TypoScript where the $objectString is located. If this argument is not an array, then internal ->userTS (User TSconfig for the current BE_USER) will be used instead.
	 */
	function getTSConfig($objectString,$config='')	{
		if (!is_array($config))	{
			$config=$this->userTS;	// Getting Root-ts if not sent
		}
		$TSConf=array();
		$parts = explode('.',$objectString,2);
		$key = $parts[0];
		if (trim($key))	{
			if (count($parts)>1 && trim($parts[1]))	{
				// Go on, get the next level
				if (is_array($config[$key.'.']))	$TSConf = $this->getTSConfig($parts[1],$config[$key.'.']);
			} else {
				$TSConf['value']=$config[$key];
				$TSConf['properties']=$config[$key.'.'];
			}
		}
		return $TSConf;
	}
	
	/**
	 * Returns the "value" of the $objectString from the BE_USERS "User TSconfig" array
	 * 
	 * @param	string		Object string, eg. "somestring.someproperty.somesubproperty"
	 * @return	string		The value for that object string (object path)
	 * @see	getTSConfig()
	 */
	function getTSConfigVal($objectString)	{
		$TSConf = $this->getTSConfig($objectString);
		return $TSConf['value'];
	}
	
	/**
	 * Returns the "properties" of the $objectString from the BE_USERS "User TSconfig" array
	 * 
	 * @param	string		Object string, eg. "somestring.someproperty.somesubproperty"
	 * @return	array		The properties for that object string (object path) - if any
	 * @see	getTSConfig()
	 */
	function getTSConfigProp($objectString)	{
		$TSConf = $this->getTSConfig($objectString);
		return $TSConf['properties'];
	}
	
	/**
	 * Returns true if $item is in $in_list
	 * 
	 * @param	string		Comma list with items, no spaces between items!
	 * @param	string		The string to find in the list of items
	 * @return	string		Boolean
	 */
	function inList($in_list,$item)	{
		return strstr(','.$in_list.',', ','.$item.',');
	}
	
	/**
	 * Returns an array with the webmounts. 
	 * If no webmounts, and empty array is returned.
	 * 
	 * @return	array		
	 */
	function returnWebmounts()	{
		return (string)($this->groupData['webmounts'])!='' ? explode(',',$this->groupData['webmounts']) : Array();
	}
	
	/**
	 * Returns an array with the filemounts for the user. Each filemount is represented with an array of a "name", "path" and "type". 
	 * If no filemounts an empty array is returned.
	 * 
	 * @return	array		
	 */
	function returnFilemounts()	{
		return $this->groupData['filemounts'];
	}
	

	
	
	
	
	
	
	
	
	
	
	/*************************************
	 *
	 * Authentication methods
	 *
	 *************************************/
	
	
	/**
	 * Initializes a lot of stuff like the access-lists, database-mountpoints and filemountpoints
	 * This method is called by ->backendCheckLogin() (from extending class t3lib_beuserauth) if the backend user login has verified OK.
	 * 
	 * @return	void		
	 * @access private
	 * @see t3lib_TSparser
	 */
	function fetchGroupData()	{
		if ($this->user['uid'])	{

				// Get lists for the be_user record and set them as default/primary values.
			$this->dataLists['modList'] = $this->user['userMods'];					// Enabled Backend Modules
			$this->dataLists['webmount_list'] = $this->user['db_mountpoints'];		// Database mountpoints	
			$this->dataLists['filemount_list'] = $this->user['file_mountpoints'];	// File mountpoints

				// Setting default User TSconfig:
			$this->TSdataArray[]=$this->addTScomment('From $GLOBALS["TYPO3_CONF_VARS"]["BE"]["defaultUserTSconfig"]:').
									$GLOBALS['TYPO3_CONF_VARS']['BE']['defaultUserTSconfig'];
		
				// Default TSconfig for admin-users
			if ($this->isAdmin())	{
				$this->TSdataArray[]=$this->addTScomment('"admin" user presets:').'
					admPanel.enable.all = 1
					setup.default.deleteCmdInClipboard = 1
					options.shortcutFrame=1
				';
				if (t3lib_extMgm::isLoaded('tt_news'))	{
					$this->TSdataArray[]='
						// Setting defaults for tt_news author / email...
						TCAdefaults.tt_news.author = '.$this->user['realName'].'
						TCAdefaults.tt_news.author_email = '.$this->user['email'].'
					';
				}
				if (t3lib_extMgm::isLoaded('sys_note'))	{
					$this->TSdataArray[]='
						// Setting defaults for sys_note author / email...
						TCAdefaults.sys_note.author = '.$this->user['realName'].'
						TCAdefaults.sys_note.email = '.$this->user['email'].'
					';
				}
			}

				// FILE MOUNTS:
				// Admin users has the base fileadmin dir mounted
			if ($this->isAdmin() && $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'])	{	
				$this->addFileMount($GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'], '', PATH_site.$GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'], 0, '');
			}
			
				// If userHomePath is set, we attempt to mount it
			if ($GLOBALS['TYPO3_CONF_VARS']['BE']['userHomePath'])	{
					// First try and mount with [uid]_[username]
				$didMount=$this->addFileMount($this->user['username'], '',$GLOBALS['TYPO3_CONF_VARS']['BE']['userHomePath'].$this->user['uid'].'_'.$this->user['username'].$GLOBALS['TYPO3_CONF_VARS']['BE']['userUploadDir'], 0, 'user');
				if (!$didMount)	{
						// If that failed, try and mount with only [uid]
					$this->addFileMount($this->user['username'], '', $GLOBALS['TYPO3_CONF_VARS']['BE']['userHomePath'].$this->user['uid'].$GLOBALS['TYPO3_CONF_VARS']['BE']['userUploadDir'], 0, 'user');
				}
			}

				// BE_GROUPS:
				// Get the groups...
#			$grList = t3lib_BEfunc::getSQLselectableList($this->user[$this->usergroup_column],$this->usergroup_table,$this->usergroup_table);
			$grList = implode(',',t3lib_div::intExplode(',',$this->user[$this->usergroup_column]));	// 240203: Since the group-field never contains any references to groups with a prepended table name we think it's safe to just intExplode and re-implode - which should be much faster than the other function call.
			if ($grList)	{
					// Fetch groups will add a lot of information to the internal arrays: modules, accesslists, TSconfig etc. Refer to fetchGroups() function.
				$this->fetchGroups($grList);
			}

				// Add the TSconfig for this specific user:
			$this->TSdataArray[] = $this->addTScomment('USER TSconfig field').$this->user['TSconfig'];
				// Check include lines.
			$this->TSdataArray = t3lib_TSparser::checkIncludeLines_array($this->TSdataArray);

				// Parsing the user TSconfig (or getting from cache)
			$this->userTS_text = implode($this->TSdataArray,chr(10).'[GLOBAL]'.chr(10));	// Imploding with "[global]" will make sure that non-ended confinements with braces are ignored.
			$hash = md5('userTS:'.$this->userTS_text);
			$cachedContent = t3lib_BEfunc::getHash($hash,0);
			if (isset($cachedContent) && !$this->userTS_dontGetCached)	{
				$this->userTS = unserialize($cachedContent);
			} else {
				$parseObj = t3lib_div::makeInstance('t3lib_TSparser');
				$parseObj->parse($this->userTS_text);
				$this->userTS = $parseObj->setup;
				t3lib_BEfunc::storeHash($hash,serialize($this->userTS),'BE_USER_TSconfig');
					// Update UC:
				$this->userTSUpdated=1;
			}

				// Processing webmounts
			if ($this->isAdmin() && !$this->getTSConfigVal('options.dontMountAdminMounts'))	{	// Admin's always have the root mounted
				$this->dataLists['webmount_list']='0,'.$this->dataLists['webmount_list'];
			}

				// Processing filemounts
			$this->dataLists['filemount_list']=t3lib_div::uniqueList($this->dataLists['filemount_list']);
			if ($this->dataLists['filemount_list'])	{
				$res = mysql(TYPO3_db,'SELECT * FROM sys_filemounts 
							WHERE NOT deleted 
							AND NOT hidden 
							AND pid=0 
							AND uid IN ('.$this->dataLists['filemount_list'].')'
						);
				while ($row=mysql_fetch_assoc($res))	{
					$this->addFileMount($row['title'], $row['path'], $row['path'], $row['base']?1:0, '');
				}
			}
			
				// The lists are cleaned for duplicates
			$this->groupData['webmounts'] = t3lib_div::uniqueList($this->dataLists['webmount_list']);
			$this->groupData['pagetypes_select'] = t3lib_div::uniqueList($this->dataLists['pagetypes_select']);
			$this->groupData['tables_select'] = t3lib_div::uniqueList($this->dataLists['tables_modify'].','.$this->dataLists['tables_select']);
			$this->groupData['tables_modify'] = t3lib_div::uniqueList($this->dataLists['tables_modify']);
			$this->groupData['non_exclude_fields'] = t3lib_div::uniqueList($this->dataLists['non_exclude_fields']);
			$this->groupData['modules'] = t3lib_div::uniqueList($this->dataLists['modList']);

				// populating the $this->userGroupsUID -array with the groups in the order in which they were LAST included.!!
			$this->userGroupsUID = array_reverse(array_unique(array_reverse($this->includeGroupArray)));
			
				// Finally this is the list of group_uid's in the order they are parsed (including subgroups!) and without duplicates (duplicates are presented with their last entrance in the list, which thus reflects the order of the TypoScript in TSconfig)
			$this->groupList = implode(',',$this->userGroupsUID);
			$this->setCachedList($this->groupList);
		}
	}
	
	/**
	 * Fetches the group records, subgroups and fills internal arrays.
	 * Function is called recursively to fetch subgroups
	 * 
	 * @param	string		Commalist of be_groups uid numbers
	 * @param	string		List of already processed be_groups-uids so the function will not fall into a eternal recursion.
	 * @return	void		
	 * @access private
	 */
	function fetchGroups($grList,$idList='')	{

			// Fetching records of the groups in $grList (which are not blocked by lockedToDomain either):
		$lockToDomain_SQL = ' AND (lockToDomain="" OR lockToDomain="'.t3lib_div::getIndpEnv('HTTP_HOST').'")';
		$res = mysql(TYPO3_db,'SELECT * FROM '.$this->usergroup_table.' 
				WHERE NOT deleted 
				AND NOT hidden 
				AND pid=0 
				AND uid IN ('.$grList.')'.
				$lockToDomain_SQL);

			// The userGroups array is filled
		while ($row=mysql_fetch_assoc($res))	{
			$this->userGroups[$row['uid']]=$row;
		}
		
			// Traversing records in the correct order
		$include_staticArr = t3lib_div::intExplode(',',$grList);
		reset($include_staticArr);
		while(list(,$uid)=each($include_staticArr))	{	// traversing list

				// Get row:
			$row=$this->userGroups[$uid];
			if (is_array($row) && !t3lib_div::inList($idList,$uid))	{	// Must be an array and $uid should not be in the idList, because then it is somewhere previously in the grouplist

					// Include sub groups
				if (trim($row['subgroup']))	{
					$theList = implode(t3lib_div::intExplode(',',$row['subgroup']),',');	// Make integer list
					$this->fetchGroups($theList, $idList.','.$uid);		// Call recursively, pass along list of already processed groups so they are not recursed again.
				}
					// Add the group uid, current list, TSconfig to the internal arrays.
				$this->includeGroupArray[]=$uid;
				$this->includeHierarchy[]=$idList;
				$this->TSdataArray[] = $this->addTScomment('Group "'.$row['title'].'" ['.$row['uid'].'] TSconfig field:').$row['TSconfig'];

					// Mount group database-mounts
				if (($this->user['options']&1) == 1)	{	$this->dataLists['webmount_list'].= ','.$row['db_mountpoints'];	}
	
					// Mount group file-mounts
				if (($this->user['options']&2) == 2)	{	$this->dataLists['filemount_list'].= ','.$row['file_mountpoints'];	}
	
					// Mount group home-dirs
				if (($this->user['options']&2) == 2)	{
						// If groupHomePath is set, we attempt to mount it
					if ($GLOBALS['TYPO3_CONF_VARS']['BE']['groupHomePath'])	{
						$this->addFileMount($row['title'], '', $GLOBALS['TYPO3_CONF_VARS']['BE']['groupHomePath'].$row['uid'], 0, 'group');
					}
				}

					// The lists are made: groupMods, tables_select, tables_modify, pagetypes_select, non_exclude_fields
				if ($row['inc_access_lists']==1)	{
					$this->dataLists['modList'].= ','.$row['groupMods'];
					$this->dataLists['tables_select'].= ','.$row['tables_select'];
					$this->dataLists['tables_modify'].= ','.$row['tables_modify'];
					$this->dataLists['pagetypes_select'].= ','.$row['pagetypes_select'];
					$this->dataLists['non_exclude_fields'].= ','.$row['non_exclude_fields'];
				}
					// If this function is processing the users OWN group-list (not subgroups) AND if the ->firstMainGroup is not set, then the ->firstMainGroup will be set.
				if (!strcmp($idList,'') && !$this->firstMainGroup)	{
					$this->firstMainGroup=$uid;
				}
			}
		}
		
	}
	
	/**
	 * Updates the field be_users.usergroup_cached_list if the groupList of the user has changed/is different from the current list.
	 * The field "usergroup_cached_list" contains the list of groups which the user is a member of. After authentication (where these functions are called...) one can depend on this list being a representation of the exact groups/subgroups which the BE_USER has membership with.
	 * 
	 * @param	string		The newly compiled group-list which must be compared with the current list in the user record and possibly stored if a difference is detected.
	 * @return	void		
	 * @access private
	 */
	function setCachedList($cList)	{
		if ((string)$cList != (string)$this->user['usergroup_cached_list'])	{
			$query='UPDATE be_users SET usergroup_cached_list="'.addslashes($cList).'" WHERE uid='.intval($this->user['uid']);
			$res = mysql(TYPO3_db,$query);
		}
	}

	/**
	 * Adds a filemount to the users array of filemounts, $this->groupData['filemounts'][hash_key] = Array ('name'=>$name, 'path'=>$path, 'type'=>$type);
	 * Is a part of the authentication proces of the user.
	 * A final requirement for a path being mounted is that a) it MUST return true on is_dir(), b) must contain either PATH_site+'fileadminDir' OR 'lockRootPath' - if lockRootPath is set - as first part of string!
	 * Paths in the mounted information will always be absolute and have a trailing slash.
	 * 
	 * @param	string		$title will be the (root)name of the filemount in the folder tree
	 * @param	string		$altTitle will be the (root)name of the filemount IF $title is not true (blank or zero)
	 * @param	string		$path is the path which should be mounted. Will accept backslash in paths on windows servers (will substituted with forward slash). The path should be 1) relative to TYPO3_CONF_VARS[BE][fileadminDir] if $webspace is set, otherwise absolute.
	 * @param	boolean		If $webspace is set, the $path is relative to 'fileadminDir' in TYPO3_CONF_VARS, otherwise $path is absolute. 'fileadminDir' must be set to allow mounting of relative paths.
	 * @param	string		Type of filemount; Can be blank (regular) or "user" / "group" (for user and group filemounts presumably). Probably sets the icon first and foremost.
	 * @return	boolean		Returns "1" if the requested filemount was mounted, otherwise no return value.
	 * @access private
	 */
	function addFileMount($title, $altTitle, $path, $webspace, $type)	{
			// Return false if fileadminDir is not set and we try to mount a relative path
		if ($webspace && !$GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'])	return false;

			// Trimming and pre-processing
		$path=trim($path);
		if ($this->OS=='WIN')	{		// with WINDOWS convert backslash to slash!!
			$path=str_replace('\\','/',$path);
		}
			// If the path is true and validates as a valid path string:
		if ($path && t3lib_div::validPathStr($path))	{
				// these lines remove all slashes and dots before and after the path
			$path=ereg_replace('^[\/\. ]*','',$path);	
			$path=trim(ereg_replace('[\/\. ]*$','',$path));
			
				
			if ($path)	{	// there must be some chars in the path
				$fdir=PATH_site.$GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'];	// fileadmin dir, absolute
				if ($webspace)	{
					$path=$fdir.$path;	// PATH_site + fileadmin dir is prepended
				} else {
					if ($this->OS!='WIN')	{		// with WINDOWS no prepending!!
						$path='/'.$path;	// root-level is the start...
					}
				}
				$path.='/';

					// We now have a path with slash after and slash before (if unix)
				if (@is_dir($path) &&
					(($GLOBALS['TYPO3_CONF_VARS']['BE']['lockRootPath'] && t3lib_div::isFirstPartOfStr($path,$GLOBALS['TYPO3_CONF_VARS']['BE']['lockRootPath'])) || t3lib_div::isFirstPartOfStr($path,$fdir)))	{
							// Alternative title?
						$name = $title ? $title : $altTitle;
							// Adds the filemount. The same filemount with same name, type and path cannot be set up twice because of the hash string used as key.
						$this->groupData['filemounts'][md5($name.'|'.$path.'|'.$type)] = Array('name'=>$name, 'path'=>$path, 'type'=>$type);
							// Return true - went well, success!
						return 1;
				}
			}
		}
	}
	
	/**
	 * Creates a TypoScript comment with the string text inside.
	 * 
	 * @param	string		The text to wrap in comment prefixes and delimiters.
	 * @return	string		TypoScript comment with the string text inside.
	 */
	function addTScomment($str)	{
		$delimiter = '# ***********************************************';
		
		$out = $delimiter.chr(10);
		$lines = t3lib_div::trimExplode(chr(10),$str);
		foreach($lines as $v)	{
			$out.= '# '.$v.chr(10);
		}
		$out.= $delimiter.chr(10);
		return $out;
	}
	

	
	
	
	
	
	
	
	
	
	
	/************************************
	 *
	 * Logging
	 *
	 ************************************/


	/**
	 * Writes an entry in the logfile
	 * ... Still missing documentation for syntax etc...
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
	 * @param	integer		$event_pid: The page_uid (pid) where the event occurred. Used to select log-content for specific pages.
	 * @param	string		$NEWid: NEWid string
	 * @return	void		
	 */
	function writelog($type,$action,$error,$details_nr,$details,$data,$tablename='',$recuid='',$recpid='',$event_pid=-1,$NEWid='') {
		$userid = $this->user['uid'];
		$tstamp = $GLOBALS['EXEC_TIME'];
		if (TYPO3_db)	{
			$fields_values=array();
			$fields_values['userid']=intval($userid);
			$fields_values['type']=intval($type);
			$fields_values['action']=intval($action);
			$fields_values['error']=intval($error);
			$fields_values['details_nr']=intval($details_nr);
			$fields_values['details']=$details;
			$fields_values['log_data']=serialize($data);
			$fields_values['tablename']=$tablename;
			$fields_values['recuid']=intval($recuid);
			$fields_values['recpid']=intval($recpid);
			$fields_values['IP']=t3lib_div::getIndpEnv('REMOTE_ADDR');
			$fields_values['tstamp']=$tstamp;
			$fields_values['event_pid']=intval($event_pid);
			$fields_values['NEWid']=$NEWid;
			
			$query = t3lib_BEfunc::DBcompileInsert('sys_log',$fields_values,1);

			mysql(TYPO3_db,$query);
			return mysql_insert_id();
		}
	}
	
	/**
	 * Sends a warning to $email if there has been a certain amount of failed logins during a period.
	 * If a login fails, this function is called. It will look up the sys_log to see if there has been more than $max failed logins the last $secondsBack seconds (default 3600). If so, an email with a warning is sent to $email.
	 * 
	 * @param	string		Email address
	 * @param	integer		Number of sections back in time to check. This is a kind of limit for how many failures an hour for instance.
	 * @param	integer		Max allowed failures before a warning mail is sent
	 * @return	void		
	 * @access private
	 */
	function checkLogFailures($email, $secondsBack=3600, $max=3)	{
		if ($email)	{

				// get last flag set in the log for sending 
			$theTimeBack = time()-$secondsBack;
			$query = 'SELECT tstamp FROM sys_log 
					WHERE type=255 
					AND action=4 
					AND tstamp>'.intval($theTimeBack).' 
					ORDER BY tstamp DESC LIMIT 1';
			$res = mysql(TYPO3_db,$query);
			if ($testRow = mysql_fetch_assoc($res))	{
				$theTimeBack = $testRow['tstamp'];
			}
			
				// Check for more than $max number of error failures with the last period.
			$query = 'SELECT * FROM sys_log 
					WHERE type=255 
					AND action=3 
					AND error!=0 
					AND tstamp>'.$theTimeBack.' 
					ORDER BY tstamp';
			$res = mysql(TYPO3_db,$query);
			if (mysql_num_rows($res) > $max)	{
					// OK, so there were more than the max allowed number of login failures - so we will send an email then.
				$subject = 'TYPO3 Login Failure Warning (at '.$GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'].')';
				$email_body = '
There has been numerous attempts ('.mysql_num_rows($res).') to login at the TYPO3
site "'.$GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'].'" ('.t3lib_div::getIndpEnv('HTTP_HOST').').

This is a dump of the failures:

';
				while($testRows=mysql_fetch_assoc($res))	{
					$theData = unserialize($testRows['log_data']);
					$email_body.=date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'].' H:i',$testRows['tstamp']).':  '.@sprintf($testRows['details'],''.$theData[0],''.$theData[1],''.$theData[2]);
					$email_body.=chr(10);
				}
				mail(	$email,
						$subject,
						$email_body,
						'From: TYPO3 Login WARNING<>'
				);
				$this->writelog(255,4,0,3,'Failure warning (%s failures within %s seconds) sent by email to %s',Array(mysql_num_rows($res),$secondsBack,$email));	// Logout written to log
			}
		}
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_userauthgroup.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_userauthgroup.php']);
}
?>