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
 * Contains the TYPO3 Core Engine
 *
 * $Id$
 * Revised for TYPO3 3.6 August/2003 by Kasper Skaarhoj
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  182: class t3lib_TCEmain	
 *  269:     function start($data,$cmd,$altUserObject='')	
 *  314:     function setMirror($mirror)	
 *  339:     function setDefaultsFromUserTS($userTS)	
 *  363:     function process_uploads($postFiles)	
 *  393:     function process_uploads_traverseArray(&$outputArr,$inputArr,$keyToSet)	
 *
 *              SECTION: PROCESSING DATA
 *  429:     function process_datamap() 
 *  600:     function fillInFieldArray($table,$id,$fieldArray,$incomingFieldArray,$realPid,$status,$tscPID)	
 *  756:     function checkModifyAccessList($table)	
 *  768:     function isRecordInWebMount($table,$id)	
 *  782:     function isInWebMount($pid)	
 *  799:     function checkRecordUpdateAccess($table,$id)	
 *  824:     function checkRecordInsertAccess($insertTable,$pid,$action=1)	
 *  860:     function isTableAllowedForThisPage($page_uid, $checkTable)	
 *  895:     function doesRecordExist($table,$id,$perms)	
 *  958:     function doesBranchExist($inList,$pid,$perms, $recurse)	
 *  987:     function pageInfo($id,$field)	
 * 1007:     function recordInfo($table,$id,$fieldList)	
 * 1024:     function getRecordProperties($table,$id)	
 * 1036:     function getRecordPropertiesFromRow($table,$row)	
 * 1055:     function setTSconfigPermissions($fieldArray,$TSConfig_p)	
 * 1071:     function newFieldArray($table)	
 * 1102:     function overrideFieldArray($table,$data)	
 * 1115:     function assemblePermissions($string)	
 *
 *              SECTION: Evaluation of input values
 * 1164:     function checkValue($table,$field,$value,$id,$status,$realPid)	
 * 1220:     function checkValue_SW($res,$value,$tcaFieldConf,$table,$id,$curValue,$status,$realPid,$recFID,$field,$uploadedFiles)	
 * 1259:     function checkValue_input($res,$value,$tcaFieldConf,$PP,$field='')	
 * 1297:     function checkValue_check($res,$value,$tcaFieldConf,$PP)	
 * 1320:     function checkValue_radio($res,$value,$tcaFieldConf,$PP)	
 * 1345:     function checkValue_group_select($res,$value,$tcaFieldConf,$PP,$uploadedFiles)	
 * 1423:     function checkValue_group_select_file($valueArray,$tcaFieldConf,$curValue,$uploadedFileArray,$status,$table,$id,$recFID)	
 * 1568:     function checkValue_flex($res,$value,$tcaFieldConf,$PP,$uploadedFiles,$curRecordArr,$field)	
 * 1627:     function _DELETE_FLEX_FORMdata(&$valueArrayToRemoveFrom,$deleteCMDS)	
 *
 *              SECTION: Helper functions for evaluation functions.
 * 1675:     function getUnique($table,$field,$value,$id,$newPid=0)	
 * 1708:     function checkValue_input_Eval($value,$evalArray,$is_in)	
 * 1796:     function checkValue_group_select_processDBdata($valueArray,$tcaFieldConf,$id,$status,$type)	
 * 1829:     function checkValue_group_select_explodeSelectGroupValue($value)	
 * 1850:     function checkValue_flex_procInData($dataPart,$dataPart_current,$uploadedFiles,$dataStructArray,$pParams)	
 * 1884:     function checkValue_flex_procInData_travDS(&$dataValues,$dataValues_current,$uploadedFiles,$DSelements,$pParams)	
 *
 *              SECTION: ...
 * 1984:     function updateDB($table,$id,$fieldArray)	
 * 2023:     function compareFieldArrayWithCurrentAndUnset($table,$id,$fieldArray)	
 * 2069:     function insertDB($table,$id,$fieldArray)	
 * 2120:     function checkStoredRecord($table,$id,$fieldArray,$action)	
 * 2146:     function dbAnalysisStoreExec()	
 * 2162:     function removeRegisteredFiles()	
 * 2180:     function clear_cache($table,$uid) 
 * 2261:     function getPID($table,$uid)	
 *
 *              SECTION: PROCESSING COMMANDS
 * 2303:     function process_cmdmap() 
 * 2360:     function moveRecord($table,$uid,$destPid)	
 * 2493:     function copyRecord($table,$uid,$destPid,$first=0)	
 * 2630:     function copyPages($uid,$destPid)	
 * 2680:     function copySpecificPage($uid,$destPid,$copyTablesArray,$first=0)	
 * 2708:     function int_pageTreeInfo($CPtable,$pid,$counter, $rootID)	
 * 2730:     function compileAdminTables()	
 * 2747:     function fixUniqueInPid($table,$uid)	
 * 2783:     function fixCopyAfterDuplFields($table,$uid,$prevUid,$update, $newData=array())	
 * 2808:     function extFileFields ($table)	
 * 2834:     function getCopyHeader ($table,$pid,$field,$value,$count,$prevTitle="")	
 * 2860:     function resolvePid ($table,$pid)	
 * 2878:     function prependLabel ($table)	
 * 2895:     function clearPrefixFromValue($table,$value)	
 * 2906:     function remapListedDBRecords()	
 * 2969:     function extFileFunctions($table,$field,$filelist,$func)	
 * 3001:     function deleteRecord($table,$uid, $noRecordCheck)	
 * 3057:     function deletePages($uid)	
 * 3099:     function deleteSpecificPage($uid)	
 * 3124:     function noRecordsFromUnallowedTables($inList)	
 *
 *              SECTION: MISC FUNCTIONS
 * 3186:     function getSortNumber($table,$uid,$pid)	
 * 3247:     function resorting($table,$pid,$sortRow, $return_SortNumber_After_This_Uid) 
 * 3276:     function rmComma ($input)	
 * 3286:     function destPathFromUploadFolder ($folder)	
 * 3297:     function destNotInsideSelf ($dest,$id)	
 * 3326:     function getExcludeListArray()	
 * 3350:     function doesPageHaveUnallowedTables($page_uid,$doktype)	
 * 3380:     function deleteClause($table)	
 * 3396:     function tableReadOnly($table)	
 * 3408:     function tableAdminOnly($table)	
 * 3422:     function getInterfacePagePositionID($uid)	
 * 3451:     function getTCEMAIN_TSconfig($tscPID)	
 * 3465:     function getTableEntries($table,$TSconfig)	
 * 3479:     function setHistory($table,$id,$logId)		
 * 3516:     function clearHistory($table,$id,$keepEntries=10,$maxAgeSeconds=604800)		
 * 3565:     function log($table,$recuid,$action,$recpid,$error,$details,$details_nr=0,$data=array(),$event_pid=-1,$NEWid="") 
 * 3579:     function printLogErrorMessages($redirect)	
 * 3625:     function clear_cacheCmd($cacheCmd)	
 *
 * TOTAL FUNCTIONS: 84
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

 


// *******************************
// Including necessary libraries
// *******************************
require_once (PATH_t3lib."class.t3lib_loaddbgroup.php");
require_once (PATH_t3lib."class.t3lib_parsehtml_proc.php");
require_once (PATH_t3lib."class.t3lib_stdgraphic.php");
require_once (PATH_t3lib."class.t3lib_basicfilefunc.php");













/**
 * This is the TYPO3 Core Engine class for manipulation of the database
 * This class is used by eg. the tce_db.php script which provides an the interface for POST forms to this class.
 * 
 * Dependencies:
 * - $GLOBALS["TCA"] must exist
 * - $GLOBALS["LANG"] (languageobject) may be preferred, but not fatal.
 * 
 * Note: Seems like many instances of array_merge() in this class are candidates for t3lib_div::array_merge() if integer-keys will some day make trouble...
 * 
 * tce_db.php for further comments and SYNTAX! Also see document "Inside TYPO3" for details.
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_TCEmain	{
	var $debug = 0;
	var $log_table = "sys_log";
	
	var $clearCache_like = 0;		// If set, the clear_cache function will make a text-search for references to the page-id for which to clear cache and delete cached information for those pages in the result.
	var $checkStoredRecords = 1;	// This will read the record after having updated or inserted it. If anything is not properly submitted an error is written to the log. This feature consumes extra time by selecting records
	var $checkStoredRecords_loose=1;	// If set, values '' and 0 will equal each other when the stored records are checked.
	var $sortIntervals = 256;		// The interval between sorting numbers used with tables with a "sorting" field defined. Min 1

	var $deleteTree = 0;			// Boolean. If this is set, then a page is deleted by deleting the whole branch under it (user must have deletepermissions to it all). If not set, then the page is delete ONLY if it has no branch
	var $copyTree = 0;				// int. If 0 then branch is NOT copied. If 1 then pages on the 1st level is copied. If 2 then pages on the second level is copied ... and so on
	var $neverHideAtCopy = 0;		// Boolean. If set, then the "hideAtCopy" flag for tables will be ignored.
	var $reverseOrder=0;			// boolean. If set, the dataarray is reversed in the order, which is a nice thing if you're creating a whole new bunch of records.
	var $copyWhichTables = "*";		// This list of tables decides which tables will be copied. If empty then none will. If "*" then all will (that the user has permission to of course)
	var $stripslashes_values=1;		// If set, incoming values in the data-array have their slashes stripped. This is default, because tce_main expects HTTP_POST_VARS and HTTP_GET_VARS to be slashed (which is probably done in init.php). If you supply your own data to the data-array, you can just unset this flag and slashes will not be stripped then.
	var $storeLogMessages=1;		// If set, the default log-messages will be stored. This should not be necessary if the locallang-file for the log-display is properly configured. So disabling this will just save some database-space as the default messages are not saved.
	var $enableLogging=1;			// If set, actions are logged.

//	var $history=1;					// Bit-array: Bit0: History on/off. DEPENDS on checkSimilar to be set!
	var $checkSimilar=1;			// Boolean: If set, only fields which are different from the database values are saved! In fact, if a whole input array is similar, it's not saved then.
	var $dontProcessTransformations=0;	// Boolean: If set, then transformations are NOT performed on the input.
	var $disableRTE = 0;			// Boolean: If set, the RTE is expected to have been disabled in the interface which submitted information. Thus transformations related to the RTE is not done.
	
	var $pMap = Array(		// Permission mapping
		"show" => 1,			// 1st bit
		"edit" => 2,			// 2nd bit
		"delete" => 4,			// 3rd bit
		"new" => 8,				// 4th bit
		"editcontent" => 16		// 5th bit
	);
	var $defaultPermissions = array(		// Can be overridden from $TYPO3_CONF_VARS
		"user" => "show,edit,delete,new,editcontent",
		"group" => "show,edit,new,editcontent",
		"everybody" => ""
	);
	

	var $alternativeFileName=array();		// Use this array to force another name onto a file. Eg. if you set ["/tmp/blablabal"] = "my_file.txt" and "/tmp/blablabal" is set for a certain file-field, then "my_file.txt" will be used as the name instead.
	var $data_disableFields=array();		// If entries are set in this array corresponding to fields for update, they are ignored and thus NOT updated. You could set this array from a series of checkboxes with value=0 and hidden fields before the checkbox with 1. Then an empty checkbox will disable the field.
	var $defaultValues=array();				// You can set this array on the form $defaultValues[$table][$field] = $value to override the default values fetched from TCA. You must set this externally.
	var $overrideValues=array();			// You can set this array on the form $overrideValues[$table][$field] = $value to override the incoming data. You must set this externally. You must make sure the fields in this array are also found in the table, because it's not checked. All columns can be set by this array!
	
		// *********
		// internal
		// *********
	var $fileFunc;		// May contain an object
	var $last_log_id;	
	var $BE_USER;		// The user-object the the script uses. If not set from outside, this is set to the current global $BE_USER.
	var $userid;		// will be set to uid of be_user executing this script
	var $username;		// will be set to username of be_user executing this script
	var $admin;			// will be set if user is admin
	var $exclude_array;	// the list of <table>-<fields> that cannot be edited. This is compiled from TCA/exclude-flag combined with non_exclude_fields for the user.

	var $data = Array();
	var $datamap = Array();
	var $cmd = Array();
	var $cmdmap = Array();
	var $uploadedFileArray = array();

	var $cachedTSconfig = array();
	var $substNEWwithIDs = Array();
	var $substNEWwithIDs_table = Array();
	var $recUpdateAccessCache = Array();	// Used by function checkRecordUpdateAccess() to store whether a record is updatable or not.
	var $recInsertAccessCache = Array();
	var $isRecordInWebMount_Cache=array();
	var $isInWebMount_Cache=array();
	var $pageCache = Array();
	var $copyMappingArray = Array();		// Use by the copy action to track the ids of new pages so subpages are correctly inserted!
	var $copyMappingArray_merged = Array();		// This array is the sum of all copying operations in this class
	var $registerDBList=array();				
	var $dbAnalysisStore=array();
	var $removeFilesStore=array();
	var $copiedFileMap=array();
	



	/**
	 * Initializing.
	 * For details, see 'Inside TYPO3' document.
	 * This function does not start the processing of data, by merely initializes the object
	 * 
	 * @param	array		Data to be modified or inserted in the database
	 * @param	array		Commands to copy, move, delete records.
	 * @param	object		An alternative userobject you can set instead of the default, which is $GLOBALS['BE_USER']
	 * @return	void		
	 */
	function start($data,$cmd,$altUserObject='')	{
			// Initializing BE_USER
		$this->BE_USER = is_object($altUserObject) ? $altUserObject : $GLOBALS['BE_USER'];
		$this->userid = $this->BE_USER->user['uid'];
		$this->username = $this->BE_USER->user['username'];
		$this->admin = $this->BE_USER->user['admin'];
		
			// Initializing default permissions for pages
		$defaultPermissions = $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPermissions'];
		if (isset($defaultPermissions['user']))		{$this->defaultPermissions['user'] = $defaultPermissions['user'];}
		if (isset($defaultPermissions['group']))		{$this->defaultPermissions['group'] = $defaultPermissions['group'];}
		if (isset($defaultPermissions['everybody']))		{$this->defaultPermissions['everybody'] = $defaultPermissions['everybody'];}
		
			// generates the excludelist, based on TCA/exclude-flag and non_exclude_fields for the user:
		$this->exclude_array = ($this->admin) ? array() : $this->getExcludeListArray();

			// Setting the data and cmd arrays
		if (is_array($data)) {
			reset($data);
			$this->datamap = $data;
		}
		if (is_array($cmd))	{
			reset($cmd);
			$this->cmdmap = $cmd;
		}
		
			// Output debug information
		if ($this->debug)	{
			echo '<BR>User Information:';
			debug(Array('username'=>$this->username, 'userid'=>$this->userid, 'admin'=>$this->admin));
			echo '<BR>DataMap:';
			debug($this->data);
			debug($this->datamap);
			echo '<BR>CommandMap:';
			debug($this->cmd);
			debug($this->cmdmap);
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	array		This array has the syntax $mirror[table_name][uid] = [list of uids to copy data-value TO!]
	 * @return	void		
	 */
	function setMirror($mirror)	{
		if (is_array($mirror))	{
			reset($mirror);
			while(list($table,$uid_array)=each($mirror))	{
				if (isset($this->datamap[$table]))	{
					reset($uid_array);
					while (list($id,$uidList) = each($uid_array))	{
						if (isset($this->datamap[$table][$id]))	{
							$theIdsInArray = t3lib_div::trimExplode(',',$uidList,1);
							while(list(,$copyToUid)=each($theIdsInArray))	{	
								$this->datamap[$table][$copyToUid] = $this->datamap[$table][$id];
							}
						}
					}			
				}
			}
		}
	}

	/**
	 * Initializes default values coming from User TSconfig
	 * 
	 * @param	array		User TSconfig array
	 * @return	void		
	 */
	function setDefaultsFromUserTS($userTS)	{
		global $TCA;
		if (is_array($userTS))	{
			reset($userTS);
			while(list($k,$v)=each($userTS))	{
				$k=substr($k,0,-1);
				if ($k && is_array($v) && isset($TCA[$k]))	{
					if (is_array($this->defaultValues[$k]))	{
						$this->defaultValues[$k] = array_merge($this->defaultValues[$k],$v);
					} else {
						$this->defaultValues[$k] = $v;
					}
				}
			}
		}
	}

	/**
	 * Processing of uploaded files.
	 * It turns out that some versions of PHP arranges submitted data for files different if sent in an array. This function will unify this so the internal array $this->uploadedFileArray will always contain files arranged in the same structure.
	 * 
	 * @param	array		HTTP_POST_FILES array
	 * @return	void		
	 */
	function process_uploads($postFiles)	{
		if (is_array($postFiles))	{
			reset($postFiles);
			$subA = current($postFiles);
			if (is_array($subA))	{
				if (is_array($subA['name']) && is_array($subA['type']) && is_array($subA['tmp_name']) && is_array($subA['size']))	{
						// Initialize the uploadedFilesArray:
					$this->uploadedFileArray=array();
					
						// For each entry:
					foreach($subA as $key => $values)	{
						$this->process_uploads_traverseArray($this->uploadedFileArray,$values,$key);
					}
				} else {
					$this->uploadedFileArray=$subA;
				}
			}
		}
	}
	
	/**
	 * Traverse the upload array if needed to rearrange values.
	 * 
	 * @param	array		$this->uploadedFileArray passed by reference
	 * @param	array		Input array  (HTTP_POST_FILES parts)
	 * @param	string		The current HTTP_POST_FILES array key to set on the outermost level.
	 * @return	void		
	 * @access private
	 * @see process_uploads()
	 */
	function process_uploads_traverseArray(&$outputArr,$inputArr,$keyToSet)	{
		if (is_array($inputArr))	{
			foreach($inputArr as $key => $value)	{
				$this->process_uploads_traverseArray($outputArr[$key],$inputArr[$key],$keyToSet);
			}
		} else {
			$outputArr[$keyToSet]=$inputArr;
		}
	}
	
	
	
	











	/*********************************************
	 *
	 * PROCESSING DATA
	 * 
	 *********************************************/

	/**
	 * Processing the data-array
	 * Call this function to process the data-array set by start()
	 * 
	 * @return	[type]		...
	 */
	function process_datamap() {
		global $TCA;
		reset ($this->datamap);

			// Organize tables so that the pages-table are always processed first. This is required if you want to make sure that content pointing to a new page will be created.
		$orderOfTables = Array();
		if (isset($this->datamap['pages']))	{		// Set pages first.
			$orderOfTables[]="pages";
		}
		while (list($table,) = each($this->datamap))	{
			if ($table!="pages")	{
				$orderOfTables[]=$table;
			}
		}
			// Process the tables...
		reset($orderOfTables);
		while (list(,$table) = each($orderOfTables))	{			// Have found table
				/* Check if 
					- table is set in $TCA, 
					- table is NOT readOnly, 
					- the table is set with content in the data-array (if not, there's nothing to process...) 
					- permissions for tableaccess OK
				*/
			$modifyAccessList = $this->checkModifyAccessList($table);
			if (!$modifyAccessList)	{
				$this->log($table,$id,2,0,1,"Attempt to modify table '%s' without permission",1,array($table));
			}
			if (isset($TCA[$table]) && !$this->tableReadOnly($table) && is_array($this->datamap[$table]) && $modifyAccessList)	{
				if ($this->reverseOrder)	{
					$this->datamap[$table] = array_reverse ($this->datamap[$table], 1);
				}
				reset ($this->datamap[$table]);

					// For each record from the table, do:
					// $id is the record uid, may be a string if new records...
					// $incomingFieldArray is the array of fields
				while (list($id,$incomingFieldArray) = each($this->datamap[$table]))	{
					if (is_array($incomingFieldArray))	{ 
						if ($this->debug)	{
							debug("INCOMING RECORD: ".$table.":".$id);
							debug($incomingFieldArray);
						}
							// ******************************
							// Checking access to the record
							// ******************************
						$recordAccess=0;
						$old_pid_value = "";
						if (!t3lib_div::testInt($id)) {               // Is it a new record? (Then Id is a string)
							$fieldArray = $this->newFieldArray($table);	// Get a fieldArray with default values
							if (isset($incomingFieldArray["pid"]))	{	// A pid must be set for new records.
									// $value = the pid
		 						$pid_value = $incomingFieldArray["pid"];

									// Checking and finding numerical pid, it may be a string-reference to another value
								$OK = 1;
								if (strstr($pid_value,"NEW"))	{	// If a NEW... id
									if (substr($pid_value,0,1)=="-") {$negFlag=-1;$pid_value=substr($pid_value,1);} else {$negFlag=1;}
									if (isset($this->substNEWwithIDs[$pid_value]))	{	// Trying to find the correct numerical value as it should be mapped by earlier processing of another new record.
										$old_pid_value = $pid_value;
										$pid_value=intval($negFlag*$this->substNEWwithIDs[$pid_value]); 
									} else {$OK = 0;}	// If not found in the substArray we must stop the proces...
								}
								$pid_value = intval($pid_value);

									// The $pid_value is now the numerical pid at this point
								if ($OK)	{
									$sortRow = $TCA[$table]["ctrl"]["sortby"];
									if ($pid_value>=0)	{	// Points to a page on which to insert the element, possibly in the top of the page
										if ($sortRow)	{	// If this table is sorted we better find the top sorting number
											$fieldArray[$sortRow] = $this->getSortNumber($table,0,$pid_value);
										}
										$fieldArray["pid"] = $pid_value;	// The numerical pid is inserted in the data array
									} else {	// points to another record before ifself
										if ($sortRow)	{	// If this table is sorted we better find the top sorting number
											$tempArray=$this->getSortNumber($table,0,$pid_value);	// Because $pid_value is < 0, getSortNumber returns an array
											$fieldArray["pid"] = $tempArray["pid"];
											$fieldArray[$sortRow] = $tempArray["sortNumber"];
										} else {	// Here we fetch the PID of the record that we point to...
											$tempdata = $this->recordInfo($table,abs($pid_value),"pid");
											$fieldArray["pid"]=$tempdata["pid"];
										}
									}
								}
							}
							$theRealPid = $fieldArray["pid"];
								// Now, check if we may insert records on this pid.
							if ($theRealPid>=0)	{
								$recordAccess = $this->checkRecordInsertAccess($table,$theRealPid);	// Checks if records can be inserted on this $pid.
							} else {
								debug("Internal ERROR: pid should not be less than zero!");
							}
							$status='new';						// Yes new record, change $record_status to 'insert'
						} else {	// Nope... $id is a number
							$fieldArray = Array();
							$recordAccess = $this->checkRecordUpdateAccess($table,$id);
							if (!$recordAccess)		{
								$propArr = $this->getRecordProperties($table,$id);
								$this->log($table,$id,2,0,1,"Attempt to modify record '%s' (%s) without permission. Or non-existing page.",2,array($propArr["header"],$table.":".$id),$propArr["event_pid"]);
							} else {	// Here we fetch the PID of the record that we point to...
								$tempdata = $this->recordInfo($table,$id,"pid");
								$theRealPid=$tempdata["pid"];
							}
							$status='update';	// the default is 'update' 
						}
						
						if ($this->debug)	{debug("STATUS: ".$status."  RecordAccess:".$recordAccess);	}
							// **************************************
							// If access was granted above, proceed:
							// **************************************
						if ($recordAccess)	{
//debug("tce_main",-2);
							list($tscPID)=t3lib_BEfunc::getTSCpid($table,$id,$old_pid_value ? $old_pid_value : $fieldArray["pid"]);	// Here the "pid" is sent IF NOT the old pid was a string pointing to a place in the subst-id array.
							$TSConfig = $this->getTCEMAIN_TSconfig($tscPID);
//debug($TSConfig);
							if ($status=="new" && $table=="pages" && is_array($TSConfig["permissions."]))	{
								$fieldArray = $this->setTSconfigPermissions($fieldArray,$TSConfig["permissions."]);
							}

//debug(array($table,$tscPID)); 
						
							$fieldArray = $this->fillInFieldArray($table,$id,$fieldArray,$incomingFieldArray,$theRealPid,$status,$tscPID);
							$fieldArray = $this->overrideFieldArray($table,$fieldArray);

								// Setting system fields 
							if ($status=="new")	{
								if ($TCA[$table]["ctrl"]["crdate"])	{
									$fieldArray[$TCA[$table]["ctrl"]["crdate"]]=time();
								}
								if ($TCA[$table]["ctrl"]["cruser_id"])	{
									$fieldArray[$TCA[$table]["ctrl"]["cruser_id"]]=$this->userid;
								}
							} elseif ($this->checkSimilar) {
								$fieldArray = $this->compareFieldArrayWithCurrentAndUnset($table,$id,$fieldArray);
							}
							if ($TCA[$table]["ctrl"]["tstamp"])	{
								$fieldArray[$TCA[$table]["ctrl"]["tstamp"]]=time();
							}

								// Performing insert/update
							if ($status=="new")	{
//								if ($pid_value<0)	{$fieldArray = $this->fixCopyAfterDuplFields($table,$id,abs($pid_value),0,$fieldArray);}	// Out-commented 02-05-02: I couldn't understand WHY this is needed for NEW records. Obviously to proces records being copied? Problem is that the fields are not set anyways and the copying function should basically take care of this!
								$this->insertDB($table,$id,$fieldArray);
							} else {
								$this->updateDB($table,$id,$fieldArray);
							}
						}	// if ($recordAccess)	{
					}	// if (is_array($incomingFieldArray))	{ 
				}
			}					
		}
		$this->dbAnalysisStoreExec();
		$this->removeRegisteredFiles();
	}

	/**
	 * Filling in the field array
	 * 
	 * $incomingFieldArray is which fields/values you want to set
	 * Preset $fieldArray with "pid" maybe (pid and uid will be not be overridden anyway)
	 * $this->exclude_array is used to filter fields if needed.
	 * $status = "new" or "update"
	 * 
	 * @param	[type]		$table: ...
	 * @param	[type]		$id: ...
	 * @param	[type]		$fieldArray: ...
	 * @param	[type]		$incomingFieldArray: ...
	 * @param	[type]		$realPid: ...
	 * @param	[type]		$status: ...
	 * @param	[type]		$tscPID: ...
	 * @return	[type]		...
	 */
	function fillInFieldArray($table,$id,$fieldArray,$incomingFieldArray,$realPid,$status,$tscPID)	{
		global $TCA;
		$fieldArray_orig = $fieldArray;
		t3lib_div::loadTCA($table);
			/*
				In the following all incoming value-fields are tested:
				- Are the user allowed to change the field?
				- Is the field uid/pid (which are already set)
				- perms-fields for pages-table, then do special things...
				- If the field is nothing of the above and the field is configured in TCA, the fieldvalues are evaluated by ->checkValue
				
				If everything is OK, the field is entered into $fieldArray[]
			*/
		reset ($incomingFieldArray);		// Reset the array of fields
		while (list($field,$fieldValue) = each($incomingFieldArray))	{
			if (!in_array($table."-".$field, $this->exclude_array) && !$this->data_disableFields[$table][$id][$field])	{	// The field must be editable.
				if ($this->stripslashes_values)	{
					if (is_array($fieldValue))	{
						t3lib_div::stripSlashesOnArray($fieldValue);
					} else $fieldValue=stripslashes($fieldValue);
				}		// Strip slashes
				switch ($field)	{
					case "uid":
					case "pid":
						// Nothing happens, already set
					break;
					case "perms_userid":
					case "perms_groupid":
					case "perms_user":
					case "perms_group":
					case "perms_everybody":
							// Permissions can be edited by the owner or the administrator
						if ($table=="pages" && ($this->admin || $status=="new" || $this->pageInfo($id,"perms_userid")==$this->userid) )	{
							$value=intval($fieldValue);
							switch($field)	{
								case "perms_userid":
									$fieldArray[$field]=$value;
								break;
								case "perms_groupid":
									$fieldArray[$field]=$value;
								break;
								default:
									if ($value>=0 && $value<pow(2,5))	{
										$fieldArray[$field]=$value;
									}
								break;
							}
						}
					break;
					default:
						if (isset($TCA[$table]["columns"][$field]))	{
								// Evaluating the value.
							$res = $this->checkValue($table,$field,$fieldValue,$id,$status,$realPid);
							if (isset($res["value"]))	{
								$fieldArray[$field]=$res["value"];
							}
						}
					break;
				}
			}
		}
		
			// Checking for RTE-transformations of fields:
		if (strstr($id,"NEW"))	{
			$currentRecord = $fieldArray_orig;	// changed to "_orig" 170502 - must have the "current" array - not the values set in the form.
		} else {
			$currentRecord = $this->recordInfo($table,$id,"*");	// We must use the current values as basis for this!
		}
//debug($currentRecord);
//			debug($id);
		$types_fieldConfig=t3lib_BEfunc::getTCAtypes($table,$currentRecord);
		$theTypeString = t3lib_BEfunc::getTCAtypeValue($table,$currentRecord);
		if (is_array($types_fieldConfig))	{
			reset($types_fieldConfig);
			while(list(,$vconf)=each($types_fieldConfig))	{
					// Write file configuration:
				$eFile=t3lib_parsehtml_proc::evalWriteFile($vconf["spec"]["static_write"],array_merge($currentRecord,$fieldArray));	// inserted array_merge($currentRecord,$fieldArray) 170502
//debug($eFile);
					// RTE transformations:
				if (!$this->dontProcessTransformations)	{
					if ($vconf["spec"]["richtext"] && !$this->disableRTE)	{
							// Cross transformation?
						$this->crossRTEtransformation=0;	// Crosstransformation is, when a record is saved, the CType has changed and the other type might also use the RTE - then the transformation of THAT rte is used instead. This is usefull only if we know the TBE interface did it, because in that interface the CType value changes the interface and allows extended options in RTE without first saving the type-shift.
						if ($this->crossRTEtransformation)	{
							$next_types_fieldConfig=t3lib_BEfunc::getTCAtypes($table,array_merge($currentRecord,$fieldArray),1);
							if ($next_types_fieldConfig[$vconf["field"]]["spec"]["richtext"])	{	// RTE must be enabled for the fields
								$vconf["spec"] = $next_types_fieldConfig[$vconf["field"]]["spec"];
								$theTypeString = t3lib_BEfunc::getTCAtypeValue($table,array_merge($currentRecord,$fieldArray));
							}
						}
//debug($theTypeString);
							// transform if...
						if ($vconf["spec"]["rte_transform"])	{
							$p=t3lib_BEfunc::getSpecConfParametersFromArray($vconf["spec"]["rte_transform"]["parameters"]);
							if ($p["mode"])	{	// There must be a mode set for transformation
								if (isset($fieldArray[$vconf["field"]]))	{
									if ($tscPID>=0)	{
//debug("RTEsetup");
										$RTEsetup = $this->BE_USER->getTSConfig("RTE",t3lib_BEfunc::getPagesTSconfig($tscPID));
										$thisConfig = t3lib_BEfunc::RTEsetup($RTEsetup["properties"],$table,$vconf["field"],$theTypeString);
										if (!$thisConfig["disabled"] && (!$p["flag"] || !$currentRecord[$p["flag"]]) && $this->BE_USER->isRTE())	{	// ... and any disable flag should not be set!
											$parseHTML = t3lib_div::makeInstance("t3lib_parsehtml_proc");
											$parseHTML->init($table.":".$vconf["field"],$currentRecord["pid"]);
											if (is_array($eFile))	{$parseHTML->setRelPath(dirname($eFile["relEditFile"]));}
											$fieldArray[$vconf["field"]]=$parseHTML->RTE_transform($fieldArray[$vconf["field"]],$vconf["spec"],"db",$thisConfig);
										}
									}
								}
							}
						}
					}
				}

					// Write file configuration:
				if (is_array($eFile))	{
					$mixedRec = array_merge($currentRecord,$fieldArray);
					$SW_fileContent = t3lib_div::getUrl($eFile["editFile"]);
					$parseHTML = t3lib_div::makeInstance("t3lib_parsehtml_proc");
					$parseHTML->init("","");

					$eFileMarker = $eFile["markerField"]&&trim($mixedRec[$eFile["markerField"]]) ? trim($mixedRec[$eFile["markerField"]]) : "###TYPO3_STATICFILE_EDIT###";
					$insertContent = str_replace($eFileMarker,"",$mixedRec[$eFile["contentField"]]);	// must replace the marker if present in content!

					$SW_fileNewContent = $parseHTML->substituteSubpart(
						$SW_fileContent,
						$eFileMarker,
						chr(10).$insertContent.chr(10),
						1,1);
					t3lib_div::writeFile($eFile["editFile"],$SW_fileNewContent);
					
						// Write status:
					if (!strstr($id,"NEW") && $eFile["statusField"])	{
						$SWq = t3lib_BEfunc::DBcompileUpdate($table,"uid=".intval($id),
							array(
								$eFile["statusField"]=>$eFile["relEditFile"]." updated ".date("d-m-Y H:i:s").", bytes ".strlen($mixedRec[$eFile["contentField"]])
							)
						);
						mysql(TYPO3_db,$SWq);
					}
				} elseif ($eFile && is_string($eFile))	{
					$this->log($insertTable,$id,2,0,1,"Write-file error: '%s'",13,array($eFile),$realPid);
				}
			}
		}
			// Return fieldArray
		return $fieldArray;
	}
	
	/**
	 * Checking group modify_table access list
	 * 
	 * Returns true if the user has general access to modify the $table
	 * 
	 * @param	[type]		$table: ...
	 * @return	[type]		...
	 */
	function checkModifyAccessList($table)	{
		$res = ($this->admin || (!$this->tableAdminOnly($table) && t3lib_div::inList($this->BE_USER->groupData["tables_modify"],$table)));
		return $res;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$table: ...
	 * @param	[type]		$id: ...
	 * @return	[type]		...
	 */
	function isRecordInWebMount($table,$id)	{
		if (!isset($this->isRecordInWebMount_Cache[$table.":".$id]))	{
			$recP=$this->getRecordProperties($table,$id);
			$this->isRecordInWebMount_Cache[$table.":".$id]=$this->isInWebMount($recP["event_pid"]);
		}
		return $this->isRecordInWebMount_Cache[$table.":".$id];
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$pid: ...
	 * @return	[type]		...
	 */
	function isInWebMount($pid)	{
		if (!isset($this->isInWebMount_Cache[$pid]))	{
			$this->isInWebMount_Cache[$pid]=$this->BE_USER->isInWebMount($pid);
		}
//debug($this->isInWebMount_Cache);
		return $this->isInWebMount_Cache[$pid];
	}
	
	/**
	 * Checks if user may update a certain record.
	 * 
	 * Returns true if the user may update the record given by $table and $id
	 * 
	 * @param	[type]		$table: ...
	 * @param	[type]		$id: ...
	 * @return	[type]		...
	 */
	function checkRecordUpdateAccess($table,$id)	{
		global $TCA;
		$res = 0;
		if ($TCA[$table] && intval($id)>0)	{
			if (isset($this->recUpdateAccessCache[$table][$id]))	{	// If information is cached, return it
				return $this->recUpdateAccessCache[$table][$id];
				// Check if record exists and 1) if "pages" the page may be edited, 2) if page-content the page allows for editing
			} elseif ($this->doesRecordExist($table,$id,"edit"))	{
				$res = 1;
			}
			$this->recUpdateAccessCache[$table][$id]=$res;	// Cache the result
		}
		return $res;
	}

	/**
	 * Checks if user may insert a certain record.
	 * 
	 * Returns true if the user may insert a record from table $insertTable on page $pid
	 * 
	 * @param	[type]		$insertTable: ...
	 * @param	[type]		$pid: ...
	 * @param	[type]		$action: ...
	 * @return	[type]		...
	 */
	function checkRecordInsertAccess($insertTable,$pid,$action=1)	{
		global $TCA;
		$res = 0;
		$pid = intval($pid);
		if ($pid>=0)	{
			if (isset($this->recInsertAccessCache[$insertTable][$pid]))	{	// If information is cached, return it
				return $this->recInsertAccessCache[$insertTable][$pid];
			} else {
					// If either admin and root-level or if page record exists and 1) if "pages" you may create new ones 2) if page-content, new content items may be inserted on the $pid page
				if ( (!$pid && $this->admin) || $this->doesRecordExist("pages",$pid,($insertTable=="pages"?$this->pMap["new"]:$this->pMap["editcontent"])) )	{		// Check permissions
					if ($this->isTableAllowedForThisPage($pid, $insertTable))	{
						$res = 1;
						$this->recInsertAccessCache[$insertTable][$pid]=$res;	// Cache the result
					} else {
						$propArr = $this->getRecordProperties("pages",$pid);
						$this->log($insertTable,$pid,$action,0,1,"Attempt to insert record on page '%s' (%s) where this table, %s, is not allowed",11,array($propArr["header"],$pid,$insertTable),$propArr["event_pid"]);
					}
				} else {
					$propArr = $this->getRecordProperties("pages",$pid);
					$this->log($insertTable,$pid,$action,0,1,"Attempt to insert a record on page '%s' (%s) from table '%s' without permissions. Or non-existing page.",12,array($propArr["header"],$pid,$insertTable),$propArr["event_pid"]);
				}
			}
		}
		return $res;
	}

	/**
	 * Checks is a table is allowed on a certain page.
	 * 
	 * $checkTable is the tablename
	 * $page_uid is the uid of the page to check
	 * 
	 * @param	[type]		$page_uid: ...
	 * @param	[type]		$checkTable: ...
	 * @return	[type]		...
	 */
	function isTableAllowedForThisPage($page_uid, $checkTable)	{
		global $TCA, $PAGES_TYPES;
		$page_uid = intval($page_uid);
		
			// Check if rootLevel flag is set and we're trying to insert on rootLevel - and reversed - and that the table is not "pages" which are allowed anywhere.
		if (($TCA[$checkTable]["ctrl"]["rootLevel"] xor !$page_uid) && $TCA[$checkTable]["ctrl"]["rootLevel"]!=-1 && $checkTable!="pages")	{	
			return false;
		}
		
			// Check root-level
		if (!$page_uid)	{
			if ($this->admin)	{
				return true;
			}
		} else {
				// Check non-root-level
			$doktype = $this->pageInfo($page_uid,"doktype");
			$allowedTableList = isset($PAGES_TYPES[$doktype]["allowedTables"]) ? $PAGES_TYPES[$doktype]["allowedTables"] : $PAGES_TYPES["default"]["allowedTables"];
			$allowedArray = t3lib_div::trimExplode(",",$allowedTableList,1);
			if (strstr($allowedTableList,"*") || in_array($checkTable,$allowedArray))	{		// If all tables or the table is listed as a allowed type, return true
				return true;
			}
		}
	}

	/**
	 * Checks if record exists
	 * 
	 * Returns true if the record given by $table,$id and $perms (which is either a number that is bitwise AND'ed or a string, which points to a key in the ->pMap array)
	 * 
	 * @param	[type]		$table: ...
	 * @param	[type]		$id: ...
	 * @param	[type]		$perms: ...
	 * @return	[type]		...
	 */
	function doesRecordExist($table,$id,$perms)	{
		global $TCA;

		$res = 0;
		$id = intval($id);

			// Processing the incoming $perms
		if (!t3lib_div::testInt($perms))	{
			if ($table!="pages")	{
				switch($perms)	{
					case "edit":
					case "delete":
					case "new":
						$perms = "editcontent";		// This holds it all in case the record is not page!!
					break;
				}
			}
			$perms=intval($this->pMap[$perms]);
		} else {
			$perms = intval($perms);
		}

		if (!$perms)	{debug("Internal ERROR: no permissions to check for non-admin user.");}
	
			// For all tables: Check if record exists:
			// Notice: If $perms are 0 (zero) no perms-clause is added!
		if ($TCA[$table] && $id>0 && ($this->isRecordInWebMount($table,$id)||$this->admin))	{		// 130502: added isRecordInWebMount() to check for pages being inside the page mounts...!
			if ($table != "pages")	{
				$query = "select $table.uid from $table,pages where $table.pid=pages.uid && $table.uid=".$id.$this->deleteClause("pages");
				if ($perms && !$this->admin)	{	$query.=" AND ".$this->BE_USER->getPagePermsClause($perms);		}	// admin users don't need check
				$mres = mysql(TYPO3_db,$query);
				echo mysql_error();
				if (mysql_num_rows($mres))	{
					return true;
				} else {
					if ($this->admin)	{	// admin may do stuff on records in the root
						$query = "select uid from $table where uid=".$id.$this->deleteClause($table);
						$mres = mysql(TYPO3_db,$query);
						return mysql_num_rows($mres);
					}
				}
			} else {
				$query = "select uid from pages where uid=".$id.$this->deleteClause("pages");
				if ($perms && !$this->admin)	{	$query.=" AND ".$this->BE_USER->getPagePermsClause($perms);		}	// admin users don't need check
				$mres = mysql(TYPO3_db,$query);
				return mysql_num_rows($mres);
			}
		}
	}

	/**
	 * Checks if a whole branch of pages exists
	 * 
	 * Tests the branch under $pid (like doesRecordExist). It doesn't test the page with $pid as uid. Use doesRecordExist() for this purpose
	 * Returns an ID-list or "" if OK. Else -1 which means that somewhere there was no permission (eg. to delete).
	 * if $recurse is set, then the function will follow subpages. This MUST be set, if we need the idlist for deleting pages or else we get an incomplete list
	 * 
	 * @param	[type]		$inList: ...
	 * @param	[type]		$pid: ...
	 * @param	[type]		$perms: ...
	 * @param	[type]		$recurse: ...
	 * @return	[type]		...
	 */
	function doesBranchExist($inList,$pid,$perms, $recurse)	{	
		global $TCA;
		$pid = intval($pid);
		$perms = intval($perms);
		if ($pid>=0)	{
			$query = "select uid, perms_userid, perms_groupid, perms_user, perms_group, perms_everybody from pages where pid=".$pid.$this->deleteClause("pages")." order by sorting";
			$mres = mysql(TYPO3_db,$query);
			while ($row = mysql_fetch_assoc($mres))	{
				if ($this->admin || $this->BE_USER->doesUserHaveAccess($row,$perms))	{	// IF admin, then it's OK
					$inList.=$row['uid'].",";
					if ($recurse)	{	// Follow the subpages recursively...
						$inList = $this->doesBranchExist($inList, $row['uid'], $perms, $recurse);
						if ($inList == -1)	{return -1;}		// No permissions somewhere in the branch
					}
				} else {
					return -1;		// No permissions
				}
			}
		}
		return $inList;
	}

	/**
	 * Returns the value of the $field from page $id
	 * 
	 * @param	[type]		$id: ...
	 * @param	[type]		$field: ...
	 * @return	[type]		...
	 */
	function pageInfo($id,$field)	{
		if (!isset($this->pageCache[$id]))	{
			$res = mysql(TYPO3_db,"select * from pages where uid='$id'");
			if (mysql_num_rows($res))	{
				$this->pageCache[$id]=mysql_fetch_assoc($res);
			}
		}
		return $this->pageCache[$id][$field];
	}

	/**
	 * Returns the row of a record given by $table and $id and $fieldList (list of fields, may be "*")
	 * 
	 * No check for deleted or access!
	 * 
	 * @param	[type]		$table: ...
	 * @param	[type]		$id: ...
	 * @param	[type]		$fieldList: ...
	 * @return	[type]		...
	 */
	function recordInfo($table,$id,$fieldList)	{
		global $TCA;
		if ($TCA[$table])	{
			$res = mysql(TYPO3_db,'SELECT '.$fieldList.' FROM '.$table.' WHERE uid='.intval($id));
			if (mysql_num_rows($res))	{
				return mysql_fetch_assoc($res);
			}
		}
	}

	/**
	 * Returns an array with record properties, like header and pid
	 * 
	 * @param	[type]		$table: ...
	 * @param	[type]		$id: ...
	 * @return	[type]		...
	 */
	function getRecordProperties($table,$id)	{
		$row = ($table=="pages" && !$id) ? array("title"=>"[root-level]", "uid" => 0, "pid" => 0) :$this->recordInfo($table,$id,"*");
		return $this->getRecordPropertiesFromRow($table,$row);
	}

	/**
	 * Returns an array with record properties, like header and pid, based on the row
	 * 
	 * @param	[type]		$table: ...
	 * @param	[type]		$row: ...
	 * @return	[type]		...
	 */
	function getRecordPropertiesFromRow($table,$row)	{
		global $TCA;
		if ($TCA[$table])	{
			$out = array(
				"header" => $row[$TCA[$table]["ctrl"]["label"]],
				"pid" => $row["pid"],
				"event_pid" => ($table=="pages"?$row["uid"]:$row["pid"])
			);
			return $out;
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$fieldArray: ...
	 * @param	[type]		$TSConfig_p: ...
	 * @return	[type]		...
	 */
	function setTSconfigPermissions($fieldArray,$TSConfig_p)	{
		if (strcmp($TSConfig_p["userid"],""))	$fieldArray["perms_userid"]=intval($TSConfig_p["userid"]);
		if (strcmp($TSConfig_p["groupid"],""))	$fieldArray["perms_groupid"]=intval($TSConfig_p["groupid"]);
		if (strcmp($TSConfig_p["user"],""))			$fieldArray["perms_user"]=t3lib_div::testInt($TSConfig_p["user"]) ? $TSConfig_p["user"] : $this->assemblePermissions($TSConfig_p["user"]);
		if (strcmp($TSConfig_p["group"],""))		$fieldArray["perms_group"]=t3lib_div::testInt($TSConfig_p["group"]) ? $TSConfig_p["group"] : $this->assemblePermissions($TSConfig_p["group"]);
		if (strcmp($TSConfig_p["everybody"],""))	$fieldArray["perms_everybody"]=t3lib_div::testInt($TSConfig_p["everybody"]) ? $TSConfig_p["everybody"] : $this->assemblePermissions($TSConfig_p["everybody"]);

		return $fieldArray;
	}	
	
	/**
	 * Returns a fieldArray with default values.
	 * 
	 * @param	[type]		$table: ...
	 * @return	[type]		...
	 */
	function newFieldArray($table)	{
		global $TCA;
		t3lib_div::loadTCA($table);
		$fieldArray=Array();
		if (is_array($TCA[$table]["columns"]))	{
			reset ($TCA[$table]["columns"]);
			while (list($field,$content)=each($TCA[$table]["columns"]))	{
				if (isset($this->defaultValues[$table][$field]))	{
					$fieldArray[$field] = $this->defaultValues[$table][$field];
				} elseif (isset($content["config"]["default"]))	{
					$fieldArray[$field] = $content["config"]["default"];
				}
			}
		}
		if ($table=="pages")	{		// Set default permissions for a page.
			$fieldArray["perms_userid"] = $this->userid;
			$fieldArray["perms_groupid"] = intval($this->BE_USER->firstMainGroup);
			$fieldArray["perms_user"] = $this->assemblePermissions($this->defaultPermissions["user"]);
			$fieldArray["perms_group"] = $this->assemblePermissions($this->defaultPermissions["group"]);
			$fieldArray["perms_everybody"] = $this->assemblePermissions($this->defaultPermissions["everybody"]);
		}
		return $fieldArray;
	}
	
	/**
	 * Returns the $data array from $table overridden in the fields defined in ->overrideValues.
	 * 
	 * @param	[type]		$table: ...
	 * @param	[type]		$data: ...
	 * @return	[type]		...
	 */
	function overrideFieldArray($table,$data)	{
		if (is_array($this->overrideValues[$table]))	{
			$data = array_merge($data,$this->overrideValues[$table]);			// Candidate for t3lib_div::array_merge() if integer-keys will some day make trouble...
		}
		return $data;
	}

	/**
	 * Calculates the bitvalue of the permissions given in a string, comma-sep
	 * 
	 * @param	[type]		$string: ...
	 * @return	[type]		...
	 */
	function assemblePermissions($string)	{
		$keyArr = t3lib_div::trimExplode(",",$string,1);
		$value=0;
		while(list(,$key)=each($keyArr))	{
			if ($key && isset($this->pMap[$key]))	{
				$value |= $this->pMap[$key];
			}
		}
		return $value;
	}




















	/*********************************************
	 *
	 * Evaluation of input values
	 *
	 ********************************************/

	/**
	 * Evaluates a value according to $table/$field settings.
	 * This function is for real database field - not FlexForm fields.
	 * NOTICE: Calling this function expects this: 1) That the data is saved! (files are copied and so on) 2) That files registered for deletion IS deleted at the end (with ->removeRegisteredFiles() )
	 * 
	 * @param	string		Table name
	 * @param	string		Field name
	 * @param	string		Value to be evaluated. Notice, this is the INPUT value from the form. The original value (from any existing record) must be manually looked up inside the function if needed.
	 * @param	string		The record-uid, mainly - but not exclusively - used for logging
	 * @param	string		"update" or "new" flag
	 * @param	[type]		$realPid: ...
	 * @return	string		Returns the evaluated $value
	 */
	function checkValue($table,$field,$value,$id,$status,$realPid)	{
		global $TCA, $PAGES_TYPES;
		t3lib_div::loadTCA($table);

		$res = Array();	// result array
		$recFID = $table.':'.$id.':'.$field;
		
			// Processing special case of field pages.doktype
		if ($table=='pages' && $field=='doktype')	{
				// If the user may not use this specific doktype, we issue a warning
			if (! ($this->admin || t3lib_div::inList($this->BE_USER->groupData['pagetypes_select'],$value)))	{
				$propArr = $this->getRecordProperties($table,$id);
				$this->log($table,$id,5,0,1,"You cannot change the 'doktype' of page '%s' to the desired value.",1,array($propArr['header']),$propArr['event_pid']);
				return $res;
			};
			if ($status=='update')	{
					// This checks if 1) we should check for disallowed tables and 2) the there are records from disallowed tables on the current page
				$onlyAllowedTables = isset($PAGES_TYPES[$value]['onlyAllowedTables']) ? $PAGES_TYPES[$value]['onlyAllowedTables'] : $PAGES_TYPES['default']['onlyAllowedTables'];
				if ($onlyAllowedTables)	{
					$theWrongTables = $this->doesPageHaveUnallowedTables($id,$value);
					if ($theWrongTables)	{
						$propArr = $this->getRecordProperties($table,$id);
						$this->log($table,$id,5,0,1,"'doktype' of page '%s' could not be changed because the page contains records from disallowed tables; %s",2,array($propArr['header'],$theWrongTables),$propArr['event_pid']);
						return $res;
					}
				}
			}
		}

			// Get current value:
		$curValueRec = $this->recordInfo($table,$id,$field);
		$curValue = $curValueRec[$field];
							
			// Getting config for the field
		$tcaFieldConf = $TCA[$table]['columns'][$field]['config'];
		
			// Preform processing:
		$res = $this->checkValue_SW($res,$value,$tcaFieldConf,$table,$id,$curValue,$status,$realPid,$recFID,$field,$this->uploadedFileArray[$table][$id][$field]);

		return $res;
	}
	
	/**
	 * @param	[type]		$res: ...
	 * @param	[type]		$value: ...
	 * @param	[type]		$tcaFieldConf: ...
	 * @param	[type]		$table: ...
	 * @param	[type]		$id: ...
	 * @param	[type]		$curValue: ...
	 * @param	[type]		$status: ...
	 * @param	[type]		$realPid: ...
	 * @param	[type]		$recFID: ...
	 * @param	string		Field name. Must NOT be set if the call is for a flexform field (since flexforms are not allowed within flexforms).
	 * @param	[type]		$uploadedFiles: ...
	 * @return	[type]		...
	 */
	function checkValue_SW($res,$value,$tcaFieldConf,$table,$id,$curValue,$status,$realPid,$recFID,$field,$uploadedFiles)	{
		switch ($tcaFieldConf['type']) {
			case 'text':
			case 'passthrough':
				$res['value']=$value;
			break;
			case 'input':
				$res = $this->checkValue_input($res,$value,$tcaFieldConf,array($table,$id,$curValue,$status,$realPid,$recFID),$field);
			break;
			case 'check':
				$res = $this->checkValue_check($res,$value,$tcaFieldConf,array($table,$id,$curValue,$status,$realPid,$recFID));
			break;
			case 'radio':
				$res = $this->checkValue_radio($res,$value,$tcaFieldConf,array($table,$id,$curValue,$status,$realPid,$recFID));
			break;
			case 'group':
			case 'select':
				$res = $this->checkValue_group_select($res,$value,$tcaFieldConf,array($table,$id,$curValue,$status,$realPid,$recFID),$uploadedFiles);
			break;
			case 'flex':
				if ($field)	{	// FlexForms are only allowed for real fields.
					$res = $this->checkValue_flex($res,$value,$tcaFieldConf,array($table,$id,$curValue,$status,$realPid,$recFID),$uploadedFiles,$this->recordInfo($table,$id,'*'),$field);
				}
			break;
		}

		return $res;
	}

	/**
	 * Evaluate "input" type values.
	 * 
	 * @param	array		The result array. The processed value (if any!) is set in the "value" key.
	 * @param	string		The value to set.
	 * @param	array		Field configuration from TCA
	 * @param	array		Additional parameters in a numeric array: $table,$id,$curValue,$status,$realPid,$recFID
	 * @param	[type]		$field: ...
	 * @return	array		Modified $res array
	 */
	function checkValue_input($res,$value,$tcaFieldConf,$PP,$field='')	{
		list($table,$id,$curValue,$status,$realPid,$recFID) = $PP;

			// Secures the string-length to be less than max. Will probably make problems with multi-byte strings!
		if (intval($tcaFieldConf['max'])>0)	{$value = substr($value,0,intval($tcaFieldConf['max']));}
		
			// Checking range of value:
		if ($tcaFieldConf['range'] && $value!=$tcaFieldConf['checkbox'])	{	// If value is not set to the allowed checkbox-value then it is checked against the ranges
			if (isset($tcaFieldConf['range']['upper'])&&$value>$tcaFieldConf['range']['upper'])	{$value=$tcaFieldConf['range']['upper'];}
			if (isset($tcaFieldConf['range']['lower'])&&$value<$tcaFieldConf['range']['lower'])	{$value=$tcaFieldConf['range']['lower'];}
		}
		
			// Process evaluation settings:
		$evalCodesArray = t3lib_div::trimExplode(',',$tcaFieldConf['eval'],1);
		$res = $this->checkValue_input_Eval($value,$evalCodesArray,$tcaFieldConf['is_in']);
		
			// Process UNIQUE settings:
		if ($field)	{	// Field is NOT set for flexForms - which also means that uniqueInPid and unique is NOT available for flexForm fields!
			if ($res['value'] && in_array('uniqueInPid',$evalCodesArray))	{
				$res['value'] = $this->getUnique($table,$field,$res['value'],$id,$realPid);
			}
			if ($res['value'] && in_array('unique',$evalCodesArray))	{
				$res['value'] = $this->getUnique($table,$field,$res['value'],$id);
			}
		}
		
		return $res;
	}

	/**
	 * Evaluates "check" type values.
	 * 
	 * @param	array		The result array. The processed value (if any!) is set in the "value" key.
	 * @param	string		The value to set.
	 * @param	array		Field configuration from TCA
	 * @param	array		Additional parameters in a numeric array: $table,$id,$curValue,$status,$realPid,$recFID
	 * @return	array		Modified $res array
	 */
	function checkValue_check($res,$value,$tcaFieldConf,$PP)	{
		list($table,$id,$curValue,$status,$realPid,$recFID) = $PP;

		$itemC = count($tcaFieldConf['items']);
		if (!$itemC)	{$itemC=1;}
		$maxV = pow(2,$itemC);
		
		if ($value<0)	{$value=0;}
		if ($value>$maxV)	{$value=$maxV;}
		$res['value'] = $value;	
		
		return $res;
	}

	/**
	 * Evaluates "radio" type values.
	 * 
	 * @param	array		The result array. The processed value (if any!) is set in the "value" key.
	 * @param	string		The value to set.
	 * @param	array		Field configuration from TCA
	 * @param	array		Additional parameters in a numeric array: $table,$id,$curValue,$status,$realPid,$recFID
	 * @return	array		Modified $res array
	 */
	function checkValue_radio($res,$value,$tcaFieldConf,$PP)	{
		list($table,$id,$curValue,$status,$realPid,$recFID) = $PP;

		if (is_array($tcaFieldConf['items']))	{
			foreach($tcaFieldConf['items'] as $set)	{
				if (!strcmp($set[1],$value))	{
					$res['value'] = $value;
					break;
				}
			}
		}	
		
		return $res;
	}

	/**
	 * Evaluates "group" or "select" type values.
	 * 
	 * @param	array		The result array. The processed value (if any!) is set in the "value" key.
	 * @param	string		The value to set.
	 * @param	array		Field configuration from TCA
	 * @param	array		Additional parameters in a numeric array: $table,$id,$curValue,$status,$realPid,$recFID
	 * @param	[type]		$uploadedFiles: ...
	 * @return	array		Modified $res array
	 */
	function checkValue_group_select($res,$value,$tcaFieldConf,$PP,$uploadedFiles)	{
		list($table,$id,$curValue,$status,$realPid,$recFID) = $PP;

			// This converts all occurencies of "&#123;" to the byte 123 in the string - this is needed in very rare cases where filenames with special characters (like Ê¯Â, umlaud etc) gets sent to the server as HTML entities instead of bytes. The error is done only by MSIE, not MOzilla and Opera.
			// Anyways, this should NOT disturb anything else:
		$value = $this->convNumEntityToByteValue($value);
		
			// When values are send as group or select they come as comma-separated values which are exploded by this function:
		$valueArray = $this->checkValue_group_select_explodeSelectGroupValue($value);

			// If not multiple is set, then remove duplicates:
		if (!$tcaFieldConf['multiple'])	{
			$valueArray = array_unique($valueArray);
		}

		// This could be a good spot for parsing the array through a validation-function which checks if the values are allright
		// NOTE!!! Must check max-items of files before the later check because that check would just leave out filenames if there are too many!!



			// For group types:
		if ($tcaFieldConf['type']=='group')	{
			switch($tcaFieldConf['internal_type'])	{
				case 'file':
					$valueArray = $this->checkValue_group_select_file(
						$valueArray,
						$tcaFieldConf,
						$curValue,
						$uploadedFiles,
						$status,
						$table,
						$id,
						$recFID
					);
				break;
				case 'db':
					$valueArray = $this->checkValue_group_select_processDBdata($valueArray,$tcaFieldConf,$id,$status,'group');
				break;
			}
		}
			// For select types which has a foreign table attached:
		if ($tcaFieldConf['type']=='select' && $tcaFieldConf['foreign_table'])	{
			$valueArray = $this->checkValue_group_select_processDBdata($valueArray,$tcaFieldConf,$id,$status,'select');
		}



			// Checking the number of items, that it is correct.
			// If files, there MUST NOT be too many files in the list at this point, so check that prior to this code.
		$valueArrayC = count($valueArray);
		$minI = isset($tcaFieldConf['minitems']) ? intval($tcaFieldConf['minitems']):0;

			// NOTE to the comment: It's not really possible to check for too few items, because you must then determine first, if the field is actual used regarding the CType. 
		$maxI = isset($tcaFieldConf['maxitems']) ? intval($tcaFieldConf['maxitems']):1;
		if ($valueArrayC > $maxI)	{$valueArrayC=$maxI;}	// Checking for not too many elements

			// Dumping array to list
		$newVal=array();
		foreach($valueArray as $nextVal)	{
			if ($valueArrayC==0)	{break;}
			$valueArrayC--;
			$newVal[]=$nextVal;
		}
		$res['value']=implode(',',$newVal);

		return $res;
	}

	/**
	 * Handling files for group/select function
	 * 
	 * @param	[type]		$valueArray: ...
	 * @param	[type]		$tcaFieldConf: ...
	 * @param	[type]		$curValue: ...
	 * @param	[type]		$uploadedFileArray: ...
	 * @param	[type]		$status: ...
	 * @param	[type]		$table: ...
	 * @param	[type]		$id: ...
	 * @param	[type]		$recFID: ...
	 * @return	array		Modified value array
	 * @see checkValue_group_select()
	 */
	function checkValue_group_select_file($valueArray,$tcaFieldConf,$curValue,$uploadedFileArray,$status,$table,$id,$recFID)	{

			// If any files are uploaded:
		if (is_array($uploadedFileArray) && 
			$uploadedFileArray['name'] && 
			strcmp($uploadedFileArray['tmp_name'],'none'))	{
				$valueArray[]=$uploadedFileArray['tmp_name'];
				$this->alternativeFileName[$uploadedFileArray['tmp_name']] = $uploadedFileArray['name'];
		}

			// Creating fileFunc object.
		if (!$this->fileFunc)	{
			$this->fileFunc = t3lib_div::makeInstance('t3lib_basicFileFunctions');
			$this->include_filefunctions=1;
		}
			// Setting permitted extensions.
		$all_files = Array();
		$all_files['webspace']['allow'] = $tcaFieldConf['allowed'];
		$all_files['webspace']['deny'] = $tcaFieldConf['disallowed'] ? $tcaFieldConf['disallowed'] : '*';
		$all_files['ftpspace'] = $all_files['webspace'];
		$this->fileFunc->init('', $all_files);
		
			// If there is an upload folder defined:
		if ($tcaFieldConf['uploadfolder'])	{
				// For logging..
			$propArr = $this->getRecordProperties($table,$id);

				// Get destrination path:
			$dest = $this->destPathFromUploadFolder($tcaFieldConf['uploadfolder']);

				// If we are updating:
			if ($status=='update')	{

					// Finding the CURRENT files listed, either from MM or from the current record.
				$theFileValues=array();
				if ($tcaFieldConf['MM'])	{	// If MM relations for the files also!
					$dbAnalysis = t3lib_div::makeInstance('t3lib_loadDBGroup');
					$dbAnalysis->start('','files',$tcaFieldConf['MM'],$id);
					reset($dbAnalysis->itemArray);
					while (list($somekey,$someval)=each($dbAnalysis->itemArray))	{
						if ($someval['id'])	{
							$theFileValues[]=$someval['id'];
						}
					}
				} else {
					$theFileValues=t3lib_div::trimExplode(',',$curValue,1);
				}

					// DELETE files: If existing files were found, traverse those and register files for deletion which has been removed:
				if (count($theFileValues))	{
						// Traverse the input values and for all input values which match an EXISTING value, remove the existing from $theFileValues array (this will result in an array of all the existing files which should be deleted!)
					foreach($valueArray as $key => $theFile)	{
						if ($theFile && !strstr(t3lib_div::fixWindowsFilePath($theFile),'/'))	{
							$theFileValues = t3lib_div::removeArrayEntryByValue($theFileValues,$theFile);
						}
					}										

						// This array contains the filenames in the uploadfolder that should be deleted:
					foreach($theFileValues as $key => $theFile)	{
						$theFile = trim($theFile);
						if (@is_file($dest.'/'.$theFile))	{
							$this->removeFilesStore[]=$dest.'/'.$theFile;
						} elseif ($theFile) {
							$this->log($table,$id,5,0,1,"Could not delete file '%s' (does not exist). (%s)",10,array($dest.'/'.$theFile, $recFID),$propArr['event_pid']);
						}
					}										
				}
			}
			
				// Traverse the submitted values:
			foreach($valueArray as $key => $theFile)	{
					// NEW FILES? If the value contains '/' it indicates, that the file is new and should be added to the uploadsdir (whether its absolute or relative does not matter here)
				if (strstr(t3lib_div::fixWindowsFilePath($theFile),'/'))	{
						// Init:
					$maxSize = intval($tcaFieldConf['max_size']);
					$cmd='';
					$theDestFile='';		// Must be cleared. Else a faulty fileref may be inserted if the below code returns an error!! (Change: 22/12/2000)

						// Check various things before copying file:
					if (@is_dir($dest) && (@is_file($theFile) || @is_uploaded_file($theFile)))	{		// File and destination must exist
					
							// Finding size. For safe_mode we have to rely on the size in the upload array if the file is uploaded.
						if (is_uploaded_file($theFile) && $theFile==$uploadedFileArray['tmp_name'])	{
							$fileSize = $uploadedFileArray['size'];
						} else {
							$fileSize = filesize($theFile);
						}
						
						if (!$maxSize || $fileSize<=($maxSize*1024))	{	// Check file size:
								// Prepare filename:
							$theEndFileName = isset($this->alternativeFileName[$theFile]) ? $this->alternativeFileName[$theFile] : $theFile;
							$fI = t3lib_div::split_fileref($theEndFileName);

								// Check for allowed extension:
							if ($this->fileFunc->checkIfAllowed($fI['fileext'], $dest, $theEndFileName)) {
								$theDestFile = $this->fileFunc->getUniqueName($this->fileFunc->cleanFileName($fI['file']), $dest);
								
									// If we have a unique destination filename, then write the file:
								if ($theDestFile)	{
									t3lib_div::upload_copy_move($theFile,$theDestFile);
									$this->copiedFileMap[$theFile]=$theDestFile;
									clearstatcache();
									if (!@is_file($theDestFile))	$this->log($table,$id,5,0,1,"Copying file '%s' failed!: The destination path (%s) may be write protected. Please make it write enabled!. (%s)",16,array($theFile, dirname($theDestFile), $recFID),$propArr['event_pid']);
								} else $this->log($table,$id,5,0,1,"Copying file '%s' failed!: No destination file (%s) possible!. (%s)",11,array($theFile, $theDestFile, $recFID),$propArr['event_pid']);
							} else $this->log($table,$id,5,0,1,"Fileextension '%s' not allowed. (%s)",12,array($fI['fileext'], $recFID),$propArr['event_pid']);
						} else $this->log($table,$id,5,0,1,"Filesize (%s) of file '%s' exceeds limit (%s). (%s)",13,array(t3lib_div::formatSize($fileSize),$theFile,t3lib_div::formatSize($maxSize*1024),$recFID),$propArr['event_pid']);
					} else $this->log($table,$id,5,0,1,"The destination (%s) or the source file (%s) does not exist. (%s)",14,array($dest, $theFile, $recFID),$propArr['event_pid']);

						// If the destination file was created, we will set the new filename in the value array, otherwise unset the entry in the value array!
					if (@is_file($theDestFile))	{
						$info = t3lib_div::split_fileref($theDestFile);
						$valueArray[$key]=$info['file']; // The value is set to the new filename
					} else {
						unset($valueArray[$key]);	// The value is set to the new filename
					}
				}
			}

				// If MM relations for the files, we will set the relations as MM records and change the valuearray to contain a single entry with a count of the number of files!
			if ($tcaFieldConf['MM'])	{
				$dbAnalysis = t3lib_div::makeInstance('t3lib_loadDBGroup');
				$dbAnalysis->tableArray['files']=array();	// dummy

				reset($valueArray);
				while (list($key,$theFile)=each($valueArray))	{
						// explode files
						$dbAnalysis->itemArray[]['id']=$theFile;
				}
				if ($status=='update')	{
					$dbAnalysis->writeMM($tcaFieldConf['MM'],$id,0);
				} else {
					$this->dbAnalysisStore[] = array($dbAnalysis, $tcaFieldConf['MM'], $id, 0);	// This will be traversed later to execute the actions
				}
				$cc=count($dbAnalysis->itemArray);
				$valueArray = array($cc);
			}
		}	
		
		return $valueArray;
	}
	
	/**
	 * Evaluates "flex" type values.
	 * 
	 * @param	array		The result array. The processed value (if any!) is set in the "value" key.
	 * @param	string		The value to set.
	 * @param	array		Field configuration from TCA
	 * @param	array		Additional parameters in a numeric array: $table,$id,$curValue,$status,$realPid,$recFID
	 * @param	array		Uploaded files for the field
	 * @param	array		Current record array.
	 * @param	[type]		$field: ...
	 * @return	array		Modified $res array
	 */
	function checkValue_flex($res,$value,$tcaFieldConf,$PP,$uploadedFiles,$curRecordArr,$field)	{
		list($table,$id,$curValue,$status,$realPid,$recFID) = $PP;

		if (is_array($value))	{

				// Get current value array:
			$dataStructArray = t3lib_BEfunc::getFlexFormDS($tcaFieldConf,$curRecordArr,$table);
			$currentValueArray = t3lib_div::xml2array($curValue);
			if (is_array($currentValueArray['meta']['currentLangId']))		unset($currentValueArray['meta']['currentLangId']);	// Remove all old meta for languages...

				// Evaluation of input values:
			$value['data'] = $this->checkValue_flex_procInData($value['data'],$currentValueArray['data'],$uploadedFiles['data'],$dataStructArray,array($table,$id,$curValue,$status,$realPid,$recFID));

				// Create XML and convert charsets from input value:
			$xmlValue = t3lib_div::array2xml($value,'',0,'T3FlexForms');

				// If we wanted to set UTF fixed:
			// $storeInCharset='utf-8';
			// $currentCharset=$GLOBALS['LANG']->charSet;
			// $xmlValue = $GLOBALS['LANG']->csConvObj->conv($xmlValue,$currentCharset,$storeInCharset,1);
			$storeInCharset=$GLOBALS['LANG']->charSet;
			
				// Merge them together IF they are both arrays:
				// Here we convert the currently submitted values BACK to an array, then merge the two and then BACK to XML again. This is needed to ensure the charsets are the same (provided that the current value was already stored IN the charset that the new value is converted to).
			if (is_array($currentValueArray))	{
				$arrValue = t3lib_div::xml2array($xmlValue);
				$arrValue = t3lib_div::array_merge_recursive_overrule($currentValueArray,$arrValue);
				$xmlValue = t3lib_div::array2xml($arrValue,'',0,'T3FlexForms');
			}
			
				// Temporary fix to delete elements:
			$deleteCMDs=t3lib_div::GPvar('_DELETE_FLEX_FORMdata');
			
			if (is_array($deleteCMDs[$table][$id][$field]['data']))	{
				$arrValue = t3lib_div::xml2array($xmlValue);
				$this->_DELETE_FLEX_FORMdata($arrValue['data'],$deleteCMDs[$table][$id][$field]['data']);
#debug($deleteCMDs[$table][$id][$field]['data']);
#debug($arrValue);
				$xmlValue = t3lib_div::array2xml($arrValue,'',0,'T3FlexForms');
			}

				// Create the value XML:
			$res['value']='';
			$res['value'].='<?xml version="1.0" encoding="'.$storeInCharset.'" standalone="yes" ?>'.chr(10);
			$res['value'].=$xmlValue;
		} else {
			$res['value']=$value;
		}		
		
		return $res;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$$valueArrayToRemoveFrom: ...
	 * @param	[type]		$deleteCMDS: ...
	 * @return	[type]		...
	 */
	function _DELETE_FLEX_FORMdata(&$valueArrayToRemoveFrom,$deleteCMDS)	{	
		if (is_array($valueArrayToRemoveFrom) && is_array($deleteCMDS))	{
			foreach($deleteCMDS as $key => $value)	{
				if (is_array($deleteCMDS[$key]))	{
					$this->_DELETE_FLEX_FORMdata($valueArrayToRemoveFrom[$key],$deleteCMDS[$key]);
				} else {
					unset($valueArrayToRemoveFrom[$key]);
				}
			}
		}
	}




















	/*********************************************
	 *
	 * Helper functions for evaluation functions.
	 *
	 ********************************************/


	/**
	 * Gets a unique value for $table/$id/$field based on $value
	 * 
	 * @param	string		Table name
	 * @param	string		Field name for which $value must be unique
	 * @param	string		Value string.
	 * @param	[type]		$id: ...
	 * @param	integer		If set, the value will be unique for this PID
	 * @return	string		Modified value (if not-unique). Will be the value appended with a number (until 100, then the function just breaks).
	 */
	function getUnique($table,$field,$value,$id,$newPid=0)	{
		global $TCA;
		
		t3lib_div::loadTCA($table);
		$whereAdd='';
		$newValue='';
		if (intval($newPid))	{$whereAdd.=' AND pid='.intval($newPid);}
		$whereAdd.=$this->deleteClause($table);
		
		if (isset($TCA[$table]['columns'][$field]))	{
				// Look for a record which might already have the value:
			$res = mysql(TYPO3_db,'SELECT uid FROM '.$table.' WHERE '.$field.'="'.addslashes($value).'" AND uid!='.intval($id).$whereAdd);
			$counter=0;
				// For as long as records with the test-value existing, try again (with incremented numbers appended).
			while (mysql_num_rows($res))	{
				$newValue = $value.$counter;
				$res = mysql(TYPO3_db,'SELECT uid FROM '.$table.' WHERE '.$field.'="'.addslashes($newValue).'" AND uid!='.intval($id).$whereAdd);
				$counter++;
				if ($counter>100)	{break;}
			}
			$value = $newValue ? $newValue : $value;
		}	
		return $value;
	}

	/**
	 * Evaluation of 'input'-type values based on 'eval' list
	 * 
	 * @param	string		Value
	 * @param	array		Array of evaluations to traverse.
	 * @param	string		Is-in string
	 * @return	string		Modified $value
	 */
	function checkValue_input_Eval($value,$evalArray,$is_in)	{
		$res = Array();
		$newValue = $value;
		$set = true;
		
		foreach($evalArray as $func)	{
			switch($func)	{
				case 'int':
				case 'year':
				case 'date':
				case 'datetime':
				case 'time':
				case 'timesec':
					$value = intval($value);
				break;
				case 'double2':
					$theDec = 0;
					for ($a=strlen($value); $a>0; $a--)	{
						if (substr($value,$a-1,1)=='.' || substr($value,$a-1,1)==',')	{
							$theDec = substr($value,$a);
							$value = substr($value,0,$a-1);
							break;
						}
					}
					$theDec = ereg_replace('[^0-9]','',$theDec).'00';
					$value = intval(str_replace(' ','',$value)).'.'.substr($theDec,0,2);
				break;
				case 'md5':
					if (strlen($value)!=32){$set=false;}
				break;
				case 'trim':
					$value = trim($value);
				break;
				case 'upper':
					$value = strtoupper($value);
#					$value = strtr($value, '·È˙Ì‚Í˚ÙÓÊ¯Â‰ˆ¸', '¡…⁄Õ¬ €‘Œ∆ÿ≈ƒ÷‹');	// WILL make trouble with other charsets than ISO-8859-1, so what do we do here? PHP-function which can handle this for other charsets? Currently the browsers JavaScript will fix it.
				break;
				case 'lower':
					$value = strtolower($value);
#					$value = strtr($value, '¡…⁄Õ¬ €‘Œ∆ÿ≈ƒ÷‹', '·È˙Ì‚Í˚ÙÓÊ¯Â‰ˆ¸');	// WILL make trouble with other charsets than ISO-8859-1, so what do we do here? PHP-function which can handle this for other charsets? Currently the browsers JavaScript will fix it.
				break;
				case 'required':
					if (!$value)	{$set=0;}
				break;
				case 'is_in':
					$c=strlen($value);
					if ($c)	{
						$newVal = '';
						for ($a=0;$a<$c;$a++)	{
							$char = substr($value,$a,1);
							if (strstr($is_in,$char))	{
								$newVal.=$char;
							}
						}
						$value = $newVal;
					}
				break;
				case 'nospace':
					$value = str_replace(' ','',$value);
				break;		
				case 'alpha':
					$value = ereg_replace('[^a-zA-Z]','',$value);
				break;	
				case 'num':
					$value = ereg_replace('[^0-9]','',$value);
				break;	
				case 'alphanum':
					$value = ereg_replace('[^a-zA-Z0-9]','',$value);
				break;	
				case 'alphanum_x':
					$value = ereg_replace('[^a-zA-Z0-9_-]','',$value);
				break;
			}
		}
		if ($set)	{$res['value'] = $value;}
		return $res;
	}	

	/**
	 * Returns data for group/db and select fields
	 * 
	 * @param	array		Current value array
	 * @param	array		TCA field config
	 * @param	integer		Record id, used for look-up of MM relations (local_uid)
	 * @param	string		Status string ("update" or "new")
	 * @param	string		The type, either "select" or "group"
	 * @return	array		Modified value array
	 */
	function checkValue_group_select_processDBdata($valueArray,$tcaFieldConf,$id,$status,$type)	{
		$tables = $type=='group'?$tcaFieldConf['allowed']:$tcaFieldConf['foreign_table'].','.$tcaFieldConf['neg_foreign_table'];
		$prep = $type=='group'?$tcaFieldConf['prepend_tname']:$tcaFieldConf['neg_foreign_table'];

		$dbAnalysis = t3lib_div::makeInstance('t3lib_loadDBGroup');
		$dbAnalysis->registerNonTableValues=$tcaFieldConf['allowNonIdValues'] ? 1 : 0;
		$dbAnalysis->start(implode($valueArray,','),$tables);

		if ($tcaFieldConf['MM'])	{
			if ($status=='update')	{
				$dbAnalysis->writeMM($tcaFieldConf['MM'],$id,$prep);
			} else {
				$this->dbAnalysisStore[] = array($dbAnalysis,$tcaFieldConf['MM'],$id,$prep);	// This will be traversed later to execute the actions
			}
			$cc=count($dbAnalysis->itemArray);
			$valueArray = array($cc);
		} else {
			$valueArray = $dbAnalysis->getValueArray($prep);
			if ($type=='select' && $prep)	{
				$valueArray = $dbAnalysis->convertPosNeg($valueArray,$tcaFieldConf['foreign_table'],$tcaFieldConf['neg_foreign_table']);
			}
		}

			// Here we should se if 1) the records exist anymore, 2) which are new and check if the BE_USER has read-access to the new ones.
		return $valueArray;
	}

	/**
	 * Explodes the $value, which is a list of files/uids (group select)
	 * 
	 * @param	string		Input string, comma separated values. For each part it will also be detected if a "|" is found and the first part will then be used if that is the case. Further the value will be rawurldecoded.
	 * @return	array		The value array.
	 */
	function checkValue_group_select_explodeSelectGroupValue($value)	{
		$valueArray = t3lib_div::trimExplode(',',$value,1);
		reset($valueArray);
		while(list($key,$newVal)=each($valueArray))	{
			$temp=explode('|',$newVal,2);
			$valueArray[$key] = str_replace(',','',str_replace('|','',rawurldecode($temp[0])));
		}
		return $valueArray;
	}
		
	/**
	 * Processing the input data for flexforms. This will traverse all sheets / languages and for each it will traverse the sub-structure.
	 * 
	 * @param	array		The "data" part of the INPUT flexform data
	 * @param	array		The "data" part of the CURRENT flexform data
	 * @param	array		The uploaded files for the "data" part of the INPUT flexform data
	 * @param	array		Data structure for the form (might be sheets or not). Only values in the data array which has a configuration in the data structure will be processed.
	 * @param	array		A set of parameters to pass through for the calling of the evaluation functions
	 * @return	array		The modified "data" part.
	 * @see checkValue_flex_procInData_travDS()
	 */
	function checkValue_flex_procInData($dataPart,$dataPart_current,$uploadedFiles,$dataStructArray,$pParams)	{
#debug(array($dataPart,$dataPart_current));
		if (is_array($dataPart))	{
			foreach($dataPart as $sKey => $sheetDef)	{
				list ($dataStruct,$actualSheet) = t3lib_div::resolveSheetDefInDS($dataStructArray,$sKey);

				if (is_array($dataStruct) && $actualSheet==$sKey && is_array($sheetDef))	{
					foreach($sheetDef as $lKey => $lData)	{
						$this->checkValue_flex_procInData_travDS(
							$dataPart[$sKey][$lKey],
							$dataPart_current[$sKey][$lKey],
							$uploadedFiles[$sKey][$lKey],
							$dataStruct['ROOT']['el'],
							$pParams
						);
					}
				}
			}
		}
	
		return $dataPart;	
	}

	/**
	 * Processing of the sheet/language data array
	 * 
	 * @param	array		Multidimensional Data array for sheet/language, passed by reference!
	 * @param	array		Data structure which fits the data array
	 * @param	array		A set of parameters to pass through for the calling of the evaluation functions
	 * @param	[type]		$DSelements: ...
	 * @param	[type]		$pParams: ...
	 * @return	void		
	 * @see checkValue_flex_procInData()
	 */
	function checkValue_flex_procInData_travDS(&$dataValues,$dataValues_current,$uploadedFiles,$DSelements,$pParams)	{
		if (is_array($DSelements))	{
			
				// For each DS element:
			foreach($DSelements as $key => $dsConf)	{
					
						// Array/Section:
				if ($DSelements[$key]['type']=='array')	{
					if (is_array($dataValues[$key]['el']))	{
						if ($DSelements[$key]['section'])	{
							foreach($dataValues[$key]['el'] as $ik => $el)	{
								$theKey = key($el);
								if (is_array($dataValues[$key]['el'][$ik][$theKey]['el']))	{
									$this->checkValue_flex_procInData_travDS(
											$dataValues[$key]['el'][$ik][$theKey]['el'],
											$dataValues_current[$key]['el'][$ik][$theKey]['el'],
											$uploadedFiles[$key]['el'][$ik][$theKey]['el'],
											$DSelements[$key]['el'][$theKey]['el'],
											$pParams
										);
								}
							}
						} else {
							if (!isset($dataValues[$key]['el']))	$dataValues[$key]['el']=array();
							$this->checkValue_flex_procInData_travDS(
									$dataValues[$key]['el'],
									$dataValues_current[$key]['el'],
									$uploadedFiles[$key]['el'],
									$DSelements[$key]['el'],
									$pParams
								);
						}
					}
				} else {
					if (is_array($dsConf['TCEforms']['config']) && is_array($dataValues[$key]))	{
						foreach($dataValues[$key] as $vKey => $data)	{
							list($CVtable,$CVid,$CVcurValue,$CVstatus,$CVrealPid,$CVrecFID) = $pParams;

							$res = $this->checkValue_SW(
										array(),
										$dataValues[$key][$vKey],
										$dsConf['TCEforms']['config'],
										$CVtable,
										$CVid,
										$dataValues_current[$key][$vKey],
										$CVstatus,
										$CVrealPid,
										$CVrecFID,
										'',
										$uploadedFiles[$key][$vKey]
									);
							
								// Evaluating the value.
							if (isset($res["value"]))	{
								$dataValues[$key][$vKey]=$res["value"];
							}		

						}			
					}
				}
			}
		}
	}	




















	/*********************************************
	 *
	 * ...
	 *
	 ********************************************/


	/**
	 * Update database
	 * 
	 * Does not check permissions but expects them to be verified on beforehand
	 * 
	 * @param	[type]		$table: ...
	 * @param	[type]		$id: ...
	 * @param	[type]		$fieldArray: ...
	 * @return	[type]		...
	 */
	function updateDB($table,$id,$fieldArray)	{
		global $TCA;
		if ($this->debug)	{debug($fieldArray);}
		if (is_array($fieldArray) && $TCA[$table] && intval($id) )	{
			reset($fieldArray);
			$fA=array();
			while (list($col,$val)=each($fieldArray))	{
				if ($col != "uid")	{	// Do not update the uid field
					$fA[]=$col."='".addslashes($val)."'";
				}
			}
			if (count($fA))	{
				$query= "UPDATE ".$table." set ".implode($fA,",")." WHERE uid = ".intval($id);
				@mysql(TYPO3_db,$query);
				if ($this->debug)	{echo $query."<BR>".mysql_error();}
				if (!mysql_error())	{
					if ($this->checkStoredRecords)	{$newRow = $this->checkStoredRecord($table,$id,$fieldArray,2);}
					$propArr=$this->getRecordPropertiesFromRow($table,$newRow);
					$theLogId = $this->log($table,$id,2,$recpid,0,"Record '%s' (%s) was updated.",10,array($propArr["header"],$table.":".$id),$propArr["event_pid"]);
					$this->setHistory($table,$id,$theLogId);
					$this->clear_cache($table,$id);
					if ($table=="pages")	unset($this->pageCache[$id]);	// Unset the pageCache for the id if table was page.
				} else {
					$this->log($table,$id,2,0,2,"MySQL error: '%s' (%s)",12,array(mysql_error(),$table.":".$id));
				}
			}
		}
	}
	
	/**
	 * Compares the incoming field array with the current record and unsets all fields which are the same.
	 * If the returned array is empty, then the record should not be updated!
	 * $fieldArray must be an array.
	 * 
	 * @param	[type]		$table: ...
	 * @param	[type]		$id: ...
	 * @param	[type]		$fieldArray: ...
	 * @return	[type]		...
	 */
	function compareFieldArrayWithCurrentAndUnset($table,$id,$fieldArray)	{
		unset($currentRecord);
		$res = mysql(TYPO3_db,"SELECT * FROM $table WHERE uid=".intval($id));
		
			// Fetch the types of the fields.
		if (mysql_num_rows($res))	{
			$currentRecord = mysql_fetch_assoc($res);
			$c=0;
			reset($currentRecord);
			$cRecTypes=array();
			while (list($col,$val)=each($currentRecord))	{
				$cRecTypes[$col]=mysql_field_type($res,$c);
				$c++;
			}
		}

			// Unset the fields which are similar.
		if (is_array($currentRecord))	{	// If current record exists...
			reset($fieldArray);
			while (list($col,$val)=each($fieldArray))	{
				if (!isset($currentRecord[$col]) || 
						!strcmp($val,$currentRecord[$col]) ||
						($cRecTypes[$col]=="int" && $currentRecord[$col]==0 && !strcmp($val,""))	// Now, a situation where TYPO3 tries to put an empty string into an integer field, we should not strcmp the integer-zero and '', but rather accept them to be similar.
					)	{	// The field must exist in the current record and it MUST be different to the letter.
					unset($fieldArray[$col]);
				} else {
					$this->historyRecords[$table.":".$id]["oldRecord"][$col] = $currentRecord[$col];
					$this->historyRecords[$table.":".$id]["newRecord"][$col] = $fieldArray[$col];
				}
			}
		} else {	// If the current record does not exist this is an error anyways and we just return an empty array here.
			$fieldArray=array();
		}
		return $fieldArray;
	}
	
	/**
	 * Insert into database
	 * 
	 * Does not check permissions but expects them to be verified on beforehand
	 * 
	 * @param	[type]		$table: ...
	 * @param	[type]		$id: ...
	 * @param	[type]		$fieldArray: ...
	 * @return	[type]		...
	 */
	function insertDB($table,$id,$fieldArray)	{
		global $TCA;
		if ($this->debug)	{debug($fieldArray);}
		if (is_array($fieldArray) && $TCA[$table] && isset($fieldArray["pid"]))	{
			reset($fieldArray);
			$fA=array();
			while (list($col,$val)=each($fieldArray))	{
				if ($col != "uid")	{	// Cannot insert uid
					$fA["f"][]=$col;
					$fA["v"][]="'".addslashes($val)."'";
				}
			}
			if (count($fA))	{
				$query = "INSERT INTO ".$table." (".implode($fA["f"],",").") VALUES (".implode($fA["v"],",").")";
				@mysql(TYPO3_db,$query);
				if ($this->debug)	{echo $query."<BR>".mysql_error();}
				if (!mysql_error())	{
					$NEW_id = $id;	// the NEW_id now holds the "NEWaÊsdfjÊs9345" -id
					$id = mysql_insert_id();
					$this->substNEWwithIDs[$NEW_id] = $id;
					$this->substNEWwithIDs_table[$NEW_id] = $table;
					if($this->debug)	{debug($this->substNEWwithIDs);}
					if ($table=="pages")	{
						$thePositionID = $this->getInterfacePagePositionID($id);
					} else {
						$thePositionID=0;
					}
						// Checking the record is properly saved and writing to log
					if ($this->checkStoredRecords)	{$newRow=$this->checkStoredRecord($table,$id,$fieldArray,1);}
					$propArr=$this->getRecordPropertiesFromRow($table,$newRow);
					$page_propArr= $this->getRecordProperties("pages",$propArr["pid"]);
					$this->log($table,$id,1,$thePositionID,0,"Record '%s' (%s) was inserted on page '%s' (%s)",10,array($propArr["header"],$table.":".$id,$page_propArr["header"],$newRow["pid"]),$newRow["pid"],$NEW_id);
					$this->clear_cache($table,$id);
				} else {
					$this->log($table,$id,1,0,2,"MySQL error: '%s' (%s)",12,array(mysql_error(),$table.":".$id));
				}
			}
		}
	}

	/**
	 * Checking stored record to see if the written values are properly updated.
	 * 
	 * $action is only for logging
	 * 
	 * @param	[type]		$table: ...
	 * @param	[type]		$id: ...
	 * @param	[type]		$fieldArray: ...
	 * @param	[type]		$action: ...
	 * @return	[type]		...
	 */
	function checkStoredRecord($table,$id,$fieldArray,$action)	{
		global $TCA;
		$id = intval($id);
		if ($TCA[$table] && $id)	{
			$res = mysql(TYPO3_db,"select * from $table where uid = $id");
			if ($row=mysql_fetch_assoc($res))	{
				reset($fieldArray);
				$errorString=array();
				while(list($key,$value)=each($fieldArray)){
					if ($this->checkStoredRecords_loose && !$value && !$row[$key])	{
						// Nothing...
					} elseif (strcmp($value,$row[$key]))	{$errorString[]=$key;}
				}
				if (count($errorString))	{
					$this->log($table,$id,$action,0,102,"These fields are not properly updated in database: (".implode(",",$errorString).") Probably value mismatch with fieldtype.");			
				}
				return $row;
			}
		}
	}
	
	/**
	 * Executing dbAnalysisStore
	 * 
	 * @return	[type]		...
	 */
	function dbAnalysisStoreExec()	{
		reset($this->dbAnalysisStore);
		while(list($k,$v)=each($this->dbAnalysisStore))	{
			$id = $this->substNEWwithIDs[$v[2]];
			if ($id)	{
				$v[2] = $id;
				$v[0]->writeMM($v[1],$v[2],$v[3]);
			}
		}
	}

	/**
	 * Executing dbAnalysisStore
	 * 
	 * @return	[type]		...
	 */
	function removeRegisteredFiles()	{
		reset($this->removeFilesStore);
		while(list($k,$v)=each($this->removeFilesStore))	{
			unlink($v);
//			debug($v,1);
		}
	}

	/**
	 * Clearing the cache based on a page being updated
	 * 
	 * If the $table is "pages" then cache is cleared for all pages on the same level (and subsequent?)
	 * Else just clear the cache for the parent page of the record.
	 * 
	 * @param	[type]		$table: ...
	 * @param	[type]		$uid: ...
	 * @return	[type]		...
	 */
	function clear_cache($table,$uid) {
		global $TCA;
		$uid = intval($uid);
		if ($TCA[$table] && $uid > 0)	{
			if ($table=='pages')	{
					// Builds list of pages on the SAME level as this page
				$res_tmp = mysql(TYPO3_db,"select A.pid as pid ,B.uid as uid ,B.title as title from $table A, $table B where A.uid=$uid and B.pid=A.pid");
				$list_cache='(';
				while ($row_tmp = mysql_fetch_assoc($res_tmp)) {
					$list_cache.=$row_tmp["uid"].',';
					$pid_tmp=$row_tmp["pid"];
				}
				$list_cache.=$pid_tmp.')';
				$query = "DELETE FROM cache_pages WHERE page_id IN $list_cache";
				if ($this->debug)	{echo $query."<BR>";}
				$res_tmp = mysql(TYPO3_db,$query);
				if ($this->debug)	{echo mysql_affected_rows()."<BR>";}

					// $query2 is used to clear the caching of the template-setup. This should only be needed when pages are moved or the template is rebuild. I should consider doing this only in these cases or make it a manual operation...
				$query2 = "DELETE FROM cache_pagesection WHERE page_id IN $list_cache";
				$res_tmp = mysql(TYPO3_db,$query2);

					// Deletes all cached pages with a reference to the page. This is being tested
				if ($this->clearCache_like)	{
					$query = "DELETE FROM cache_pages WHERE (".
								"HTML like '%?id=".$uid."%'".
								" OR HTML like '%?id=".$uid."&%'".
								" OR HTML like '%?".$uid."%'".
								" OR HTML like '%?".$uid."&%'".
								")";
					if ($this->debug)	{echo $query."<BR>";}
					$res_tmp = mysql(TYPO3_db,$query);
					if ($this->debug)	{echo mysql_affected_rows()."<BR>";}
				}
			} else {
				$uid_page = $this->getPID($table,$uid);
				if ($uid_page>0)	{
					$query="delete from cache_pages where page_id=$uid_page";
					if ($this->debug)	{echo $query."<BR>";}
					$res_tmp = mysql(TYPO3_db,$query);
					if ($this->debug)	{echo mysql_affected_rows()."<BR>";}


					$query2="delete from cache_pagesection where page_id=$uid_page";
					$res_tmp = mysql(TYPO3_db,$query2);
				}
			}
			
				// Clear cache for pages entered in TSconfig:
			list($tscPID)=t3lib_BEfunc::getTSCpid($table,$uid,'');
			$TSConfig = $this->getTCEMAIN_TSconfig($tscPID);
			if ($TSConfig['clearCacheCmd'])	{
				$Commands = t3lib_div::trimExplode(',',strtolower($TSConfig['clearCacheCmd']),1);
				$Commands = array_unique($Commands);
				foreach($Commands as $cmdPart)	{
					$this->clear_cacheCmd($cmdPart);	
				}
			}

				// Call post processing function for clear-cache:
			global $TYPO3_CONF_VARS;
			if (is_array($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc']))	{
				$_params = array('table' => $table,'uid' => $uid,'uid_page' => $uid_page);
				foreach($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'] as $_funcRef)	{
					t3lib_div::callUserFunction($_funcRef,$_params,$this);
				}
			}		
		}
	}

	/**
	 * Returns the pid of a record from $table with $uid
	 * 
	 * @param	[type]		$table: ...
	 * @param	[type]		$uid: ...
	 * @return	[type]		...
	 */
	function getPID($table,$uid)	{
		$res_tmp = mysql(TYPO3_db,"select pid from $table where uid=".intval($uid));
		if (mysql_num_rows($res_tmp))	{
			return mysql_result($res_tmp,0,"pid");
		} else {return "";}
	}



















	
	
	
	
	
	
	/*********************************************
	 *
	 * PROCESSING COMMANDS
	 * 
	 ********************************************/

	/**
	 * Processing the cmd-array
	 * 
	 * @return	[type]		...
	 */
	function process_cmdmap() {
		global $TCA;
#debug($this->cmdmap);
		reset ($this->cmdmap);
		while (list($table,) = each($this->cmdmap))	{
			$modifyAccessList = $this->checkModifyAccessList($table);
			if (!$modifyAccessList)	{
				$this->log($table,$id,2,0,1,"Attempt to modify table '%s' without permission",1,array($table));
			}
			if (isset($TCA[$table]) && !$this->tableReadOnly($table) && is_array($this->cmdmap[$table]) && $modifyAccessList)	{	    		// Is table from $TCA and
#debug();
				reset ($this->cmdmap[$table]);
				while (list($id,$incomingCmdArray) = each($this->cmdmap[$table]))	{			// Har fundet en tabel
					if (is_array($incomingCmdArray))	{	// Har fundet et ID-nummer
						reset($incomingCmdArray);
						$command = key($incomingCmdArray);
 						$value = current($incomingCmdArray);
						switch ($command)	{
							case "move":
								$this->moveRecord($table,$id,$value);
							break;
							case "copy":
								$this->copyMappingArray = Array();		// Must clear this array before call from here to those functions.
								if ($table == "pages")	{
									$this->copyPages($id,$value);
								} else {
									$this->copyRecord($table,$id,$value,1);
#debug(array($table,$id,$value));
								}
									// Merging the copy-array info together for remapping purposes.
								$this->copyMappingArray_merged= t3lib_div::array_merge_recursive_overrule($this->copyMappingArray_merged,$this->copyMappingArray);
							break;
							case "delete":
								if ($table == "pages")	{
									$this->deletePages($id);
								} else {
									$this->deleteRecord($table,$id, 0);
								}
							break;
						}
					}
				}
			}
		}
		$this->remapListedDBRecords();
	}

	/**
	 * Moving records
	 * 
	 * $destPid: >=0 then it points to a page-id on which to insert the record (as the first element). <0 then it points to a uid from its own table after which to insert it (works if
	 * 
	 * @param	[type]		$table: ...
	 * @param	[type]		$uid: ...
	 * @param	[type]		$destPid: ...
	 * @return	[type]		...
	 */
	function moveRecord($table,$uid,$destPid)	{
		global $TCA;
		$sortRow = $TCA[$table]["ctrl"]["sortby"];
		$destPid = intval($destPid);
		$origDestPid = $destPid;
		if ($TCA[$table])	{
			$propArr = $this->getRecordProperties($table,$uid);	// Get this before we change the pid (for logging)
			$resolvedPid = $this->resolvePid($table,$destPid);	// This is the actual pid of the moving.

				// Finding out, if the record may be moved from where it is. If the record is a non-page, then it depends on edit-permissions. 
				// If the record is a page, then there are two options: If the page is moved within itself, (same pid) it's edit-perms of the pid. If moved to another place then its both delete-perms of the pid and new-page perms on the destination.
			if ($table!="pages" || $resolvedPid==$propArr["pid"])	{
				$mayMoveAccess=$this->checkRecordUpdateAccess($table,$uid);	// Edit rights for the record...
			} else {
				$mayMoveAccess=$this->doesRecordExist($table,$uid,"delete");
			}
			
				// Finding out, if the record may be moved TO another place. Here we check insert-rights (non-pages = edit, pages = new), unless the pages is moved on the same pid, then edit-rights are checked
			if ($table!="pages" || $resolvedPid!=$propArr["pid"])	{
				$mayInsertAccess = $this->checkRecordInsertAccess($table,$resolvedPid,4);	// Edit rights for the record...
			} else {
				$mayInsertAccess=$this->checkRecordUpdateAccess($table,$uid);
			}

				// Checking if the pid is negativ, but no sorting row is defined. In that case, find the correct pid. Basically this check make the error message 4-13 meaning less... But you can always remove this check if you prefer the error instead of a no-good action (which is to move the record to its own page...)
			if ($destPid<0 && !$sortRow)	{	
				$destPid = $resolvedPid;
			}
			
				// 
			if ($TCA[$table]["ctrl"]["tstamp"])	{
				$tstampC = ", ".$TCA[$table]["ctrl"]["tstamp"]."=".time();
			} else $tstampC="";

			
			if ($mayMoveAccess)	{
				if ($destPid>=0)	{	// insert as first element on page (where uid = $destPid)
					if ($mayInsertAccess)	{
						if ($table!="pages" || $this->destNotInsideSelf ($destPid,$uid))	{
							$this->clear_cache($table,$uid);	// clear cache before moving 
							if ($sortRow)	{	// table is sorted by 'sortby'
								$sortNumber = $this->getSortNumber($table,$uid,$destPid);
								$query = "UPDATE $table SET pid='$destPid', $sortRow='$sortNumber'".$tstampC." WHERE uid = '$uid'";		// We now update the pid and sortnumber
								$res = mysql(TYPO3_db,$query);
								if ($this->debug)	{echo $table.$uid.": Update pid(".$destPid.") and sorting number(".$sortNumber.")<BR>";}
							} else {	// table is NOT sorted
								$query = "UPDATE $table SET pid='$destPid'".$tstampC." WHERE uid = '$uid'";	// We need only update the pid as this table is not sorted
								$res = mysql(TYPO3_db,$query);
								if ($this->debug)	{echo $table.$uid.": Update pid only (no sorting)<BR>";}
							}
							
									// Logging...
							$newPropArr = $this->getRecordProperties($table,$uid);
							$oldpagePropArr = $this->getRecordProperties("pages",$propArr["pid"]);
							$newpagePropArr = $this->getRecordProperties("pages",$destPid);

							if ($destPid!=$propArr["pid"])	{
								$this->log($table,$uid,4,$destPid,0,"Moved record '%s' (%s) to page '%s' (%s)",2,array($propArr["header"],$table.":".$uid, $newpagePropArr["header"], $newPropArr["pid"]),$propArr["pid"]);	// Logged to old page
								$this->log($table,$uid,4,$destPid,0,"Moved record '%s' (%s) from page '%s' (%s)",3,array($propArr["header"],$table.":".$uid, $oldpagePropArr["header"], $propArr["pid"]),$destPid);	// Logged to new page
							} else {
								$this->log($table,$uid,4,$destPid,0,"Moved record '%s' (%s) on page '%s' (%s)",4,array($propArr["header"],$table.":".$uid, $oldpagePropArr["header"], $propArr["pid"]),$destPid);	// Logged to new page
							}
							$this->clear_cache($table,$uid);	// clear cache after moving 
							$this->fixUniqueInPid($table,$uid);
								// fixCopyAfterDuplFields
							if ($origDestPid<0)	{$this->fixCopyAfterDuplFields($table,$uid,abs($origDestPid),1);}	// origDestPid is retrieve before it may possibly be converted to resolvePid if the table is not sorted anyway. In this way, copying records to after another records which are not sorted still lets you use this function in order to copy fields from the one before.
						} else {
							$destPropArr = $this->getRecordProperties("pages",$destPid);
							$this->log($table,$uid,4,0,1,"Attempt to move page '%s' (%s) to inside of its own rootline (at page '%s' (%s))",10,array($propArr["header"],$uid, $destPropArr["header"], $destPid),$propArr["pid"]);
						}
					}
				} else {	// Put after another record
					if ($sortRow)	{	// table is being sorted
						$sortInfo = $this->getSortNumber($table,$uid,$destPid);
						$destPid = $sortInfo["pid"];	// Setting the destPid to the new pid of the record.
						if (is_array($sortInfo))	{	// If not an array, there was an error (which is already logged)
							if ($mayInsertAccess)	{
								if ($table!="pages" || $this->destNotInsideSelf ($destPid,$uid))	{
									$this->clear_cache($table,$uid);	// clear cache before moving 
									$query = "UPDATE $table SET pid='".$destPid."', $sortRow='".$sortInfo["sortNumber"]."'".$tstampC." WHERE uid = '$uid'";		// We now update the pid and sortnumber
									$res = mysql(TYPO3_db,$query);
									if ($this->debug)	{echo $table.$uid.": Update pid(".$destPid.") and sorting number(".$sortInfo["sortNumber"].")<BR>";}
										// Logging...
									if ($table=="pages")	{
										$thePositionID = $this->getInterfacePagePositionID($uid);
									} else {
										$thePositionID=0;
									}
									
									$this->log($table,$uid,4,$thePositionID,0,"");
									// Logging...
									$newPropArr = $this->getRecordProperties($table,$uid);
									$oldpagePropArr = $this->getRecordProperties("pages",$propArr["pid"]);
									if ($destPid!=$propArr["pid"])	{
										$newpagePropArr = $this->getRecordProperties("pages",$destPid);
										$this->log($table,$uid,4,$thePositionID,0,"Moved record '%s' (%s) to page '%s' (%s)",2,array($propArr["header"],$table.":".$uid, $newpagePropArr["header"], $newPropArr["pid"]),$propArr["pid"]);	// Logged to old page
										$this->log($table,$uid,4,$thePositionID,0,"Moved record '%s' (%s) from page '%s' (%s)",3,array($propArr["header"],$table.":".$uid, $oldpagePropArr["header"], $propArr["pid"]),$destPid);	// Logged to new page
									} else {
										$this->log($table,$uid,4,$thePositionID,0,"Moved record '%s' (%s) on page '%s' (%s)",4,array($propArr["header"],$table.":".$uid, $oldpagePropArr["header"], $propArr["pid"]),$destPid);	// Logged to new page
									}
									$this->clear_cache($table,$uid);	// clear cache after moving 
										// fixUniqueInPid
									$this->fixUniqueInPid($table,$uid);
										// fixCopyAfterDuplFields
									if ($origDestPid<0)	{$this->fixCopyAfterDuplFields($table,$uid,abs($origDestPid),1);}
								} else {
									$destPropArr = $this->getRecordProperties("pages",$destPid);
									$this->log($table,$uid,4,0,1,"Attempt to move page '%s' (%s) to inside of its own rootline (at page '%s' (%s))",10,array($propArr["header"],$uid, $destPropArr["header"], $destPid),$propArr["pid"]);
								}
							}
						}
					} else {
						$this->log($table,$uid,4,0,1,"Attempt to move record '%s' (%s) to after another record, although the table has no sorting row.",13,array($propArr["header"],$table.":".$uid),$propArr["event_pid"]);
					}
				}
			} else {
				$this->log($table,$uid,4,0,1,"Attempt to move record '%s' (%s) without having permissions to do so",14,array($propArr["header"],$table.":".$uid),$propArr["event_pid"]);
			}
		}
	}

	/**
	 * Copying records
	 * 
	 * $destPid: >=0 then it points to a page-id on which to insert the record (as the first element). <0 then it points to a uid from its own table after which to insert it (works if
	 * $first is a flag set, if the record copied is NOT a "slave" to another record copied. That is, if this record was asked to be copied in the cmd-array
	 * 
	 * @param	[type]		$table: ...
	 * @param	[type]		$uid: ...
	 * @param	[type]		$destPid: ...
	 * @param	[type]		$first: ...
	 * @return	[type]		...
	 */
	function copyRecord($table,$uid,$destPid,$first=0)	{
		global $TCA;
		$uid = intval($uid);
		if ($TCA[$table] && $uid)	{
			t3lib_div::loadTCA($table);
			if ($this->doesRecordExist($table,$uid,"show"))	{
				$data = Array();
				$nonFields = explode(",","uid,perms_userid,perms_groupid,perms_user,perms_group,perms_everybody");
				$row = $this->recordInfo($table,$uid,"*");
				if (is_array($row))	{
					$theNewID = uniqid("NEW");
//					$fileFieldArr = $this->extFileFields($table);		// Fetches all fields that holds references to files
					$enableField = isset($TCA[$table]["ctrl"]["enablecolumns"]) ? $TCA[$table]["ctrl"]["enablecolumns"]["disabled"] : "";
					$headerField = $TCA[$table]["ctrl"]["label"];
					$defaultData = $this->newFieldArray($table);
					
					$tscPID=t3lib_BEfunc::getTSconfig_pidValue($table,$uid,$destPid);	// NOT using t3lib_BEfunc::getTSCpid() because we need the real pid - not the id of a page, if the input is a page...
					$TSConfig = $this->getTCEMAIN_TSconfig($tscPID);
					$tE = $this->getTableEntries($table,$TSConfig);
//debug(array($table,$destPid,$TSConfig));

					reset($row);
					while (list($field,$value)=each($row))	{
						if (!in_array($field,$nonFields))	{
							$conf = $TCA[$table]["columns"][$field]["config"];
							
							if ($field=="pid")	{
								$value = $destPid;
							}

							if ($TCA[$table]["ctrl"]["setToDefaultOnCopy"] && t3lib_div::inList($TCA[$table]["ctrl"]["setToDefaultOnCopy"],$field))	{
								$value = $defaultData[$field];
							} else {
								if ($first && $field==$enableField && $TCA[$table]["ctrl"]["hideAtCopy"] && !$this->neverHideAtCopy && !$tE["disableHideAtCopy"])	{
									$value=1;
								}
								if ($first && $field==$headerField && $TCA[$table]["ctrl"]["prependAtCopy"] && !$tE["disablePrependAtCopy"])	{
									$value = $this->getCopyHeader ($table,$this->resolvePid($table,$destPid),$field,$this->clearPrefixFromValue($table,$value),0);
								}
	
									// Take care of files...
								if ($conf["type"]=="group" && $conf["internal_type"]=="file")	{
									if ($conf["MM"])	{
										$theFileValues=array();
										$dbAnalysis = t3lib_div::makeInstance("t3lib_loadDBGroup");
										$dbAnalysis->start("","files",$conf["MM"],$uid);
										reset($dbAnalysis->itemArray);
										while (list($somekey,$someval)=each($dbAnalysis->itemArray))	{
	//										debug($someval["id"]);
											if ($someval["id"])	{
												$theFileValues[]=$someval["id"];
											}
										}
									} else {
										$theFileValues = explode(",",$value);
									}
	//								debug($theFileValues);
									reset($theFileValues);
									$uploadFolder = $conf["uploadfolder"];
									$dest = $this->destPathFromUploadFolder($uploadFolder);
									$newValue = array();
									while (list(,$file)=each($theFileValues))	{
										if (trim($file))	{
											$realFile = $dest."/".trim($file);
											if (@is_file($realFile))	{
												$newValue[]=$realFile;
											}
										}
									}
									$value = implode(",",$newValue);
								}
									// db record lists:
								if (($conf["type"]=="group" && $conf["internal_type"]=="db") ||	($conf["type"]=="select" && $conf["foreign_table"]))	{
									$allowedTables = $conf["type"]=="group" ? $conf["allowed"] : $conf["foreign_table"].",".$conf["neg_foreign_table"];
									$prependName = $conf["type"]=="group" ? $conf["prepend_tname"] : $conf["neg_foreign_table"];
									if ($conf["MM"])	{
										$dbAnalysis = t3lib_div::makeInstance("t3lib_loadDBGroup");
										$dbAnalysis->start("",$allowedTables,$conf["MM"],$uid);
										$value = implode(",",$dbAnalysis->getValueArray($prependName));
									}
									if ($value)	{
										$this->registerDBList[$table][$uid][$field]=$value;
									}
								}
							}

								// Add value to array.
							$value=addSlashes($value);	// Added 15-03-00
							$data[$table][$theNewID][$field]=$value;
						}
					}

						// Added 02-05-02 to set the fields correctly for copied records...
					if ($destPid<0 && is_array($data[$table][$theNewID]))	{
						$copyAfterFields = $this->fixCopyAfterDuplFields($table,$uid,abs($destPid),0);
						$data[$table][$theNewID] = array_merge($data[$table][$theNewID],$copyAfterFields);
//debug($data[$table][$theNewID]);
					}	// origDestPid is retrieve before it may possibly be converted to resolvePid if the table is not sorted anyway. In this way, copying records to after another records which are not sorted still lets you use this function in order to copy fields from the one before.

					
						// Do the copy:
//debug($data[$table][$theNewID]);
					$copyTCE = t3lib_div::makeInstance("t3lib_TCEmain");
					$copyTCE->copyTree = $this->copyTree;
					$copyTCE->cachedTSconfig = $this->cachedTSconfig;	// Copy forth the cached TSconfig
					$copyTCE->debug = $this->debug;
					$copyTCE->dontProcessTransformations=1;		// Transformations should NOT be carried out during copy
	//				$copyTCE->enableLogging = $table=="pages"?1:0;	// If enabled the list-view does not update...
	
					$copyTCE->start($data,"",$this->BE_USER);
					$copyTCE->process_datamap();

					$theNewSQLID = $copyTCE->substNEWwithIDs[$theNewID];
					if ($theNewSQLID)	{
						$this->copyMappingArray[$table][$uid] = $theNewSQLID;
					}
					$this->cachedTSconfig = $copyTCE->cachedTSconfig;	// Copy back the cached TSconfig
					unset($copyTCE);
				} else {
					$this->log($table,$uid,3,0,1,"Attempt to copy record that did not exist!");
				}
			} else {
				$this->log($table,$uid,3,0,1,"Attempt to copy record without permission");
			}
		}		
	}	

	/**
	 * Copying pages
	 * 
	 * Main function for copying pages.
	 * $destPid: >=0 then it points to a page-id on which to insert the record (as the first element). <0 then it points to a uid from its own table after which to insert it (works if
	 * 
	 * @param	[type]		$uid: ...
	 * @param	[type]		$destPid: ...
	 * @return	[type]		...
	 */
	function copyPages($uid,$destPid)	{
		$uid = intval($uid);
		$destPid = intval($destPid);
//		$this->copyMappingArray = Array();		// This must be done, but it's comment out because it's done in process_cmdmap()

			// Finding list of tables to copy.
		$copyTablesArray = ($this->admin) ? $this->compileAdminTables() : explode(",",$this->BE_USER->groupData["tables_modify"]);	// These are the tables, the user may modify
		if (!strstr($this->copyWhichTables,"*"))	{		// If not all tables are allowed then make a list of allowed tables: That is the tables that figure in both allowed tables and the copyTable-list
			reset($copyTablesArray);
			while(list($k,$table)=each($copyTablesArray))	{
				if (!$table || !t3lib_div::inList($this->copyWhichTables.",pages",$table))	{	// pages are always going...
					unset($copyTablesArray[$k]);
				}
			}
		}
		$copyTablesArray = array_unique($copyTablesArray);
		if ($this->admin || in_array("pages",$copyTablesArray))	{	// If we're allowed to copy pages
			$this->copySpecificPage($uid,$destPid,$copyTablesArray,1);	// Copy this page we're on. And set first-flag!
			$theNewRootID = $this->copyMappingArray["pages"][$uid];		// This is the new ID of the rootpage of the copyaction. This ID is excluded when the list is gathered lateron
			if ($theNewRootID && $this->copyTree)	{	// If we're going to copy recursively...
				$CPtable = $this->int_pageTreeInfo(Array(), $uid, intval($this->copyTree), $theNewRootID);
				if ($this->debug) {debug($CPtable);}
				// Now copying the pages
				reset($CPtable);
				while (list($thePageUid,$thePagePid)=each($CPtable))	{
					$newPid = $this->copyMappingArray["pages"][$thePagePid];
					if (isset($newPid))	{
						$this->copySpecificPage($thePageUid,$newPid,$copyTablesArray);
					} else {
						$this->log("pages",$uid,5,0,1,"Something went wrong during copying branch");
						break;
					}
				}
			}	// else the page was not copied. Too bad...
		} else {
			$this->log("pages",$uid,5,0,1,"Attempt to copy page without permission to this table");
		}
	}

	/**
	 * Copying a single page ($uid) to $destPid and all tables in the array copyTablesArray.
	 * 
	 * $destPid: >=0 then it points to a page-id on which to insert the record (as the first element). <0 then it points to a uid from its own table after which to insert it (works if
	 * 
	 * @param	[type]		$uid: ...
	 * @param	[type]		$destPid: ...
	 * @param	[type]		$copyTablesArray: ...
	 * @param	[type]		$first: ...
	 * @return	[type]		...
	 */
	function copySpecificPage($uid,$destPid,$copyTablesArray,$first=0)	{
		global $TCA;
		$this->copyRecord("pages",$uid,$destPid,$first);	// Copy the page itselft.
		$theNewRootID = $this->copyMappingArray["pages"][$uid];	// The new uid
		if ($theNewRootID)	{		// copy of the page went fine
			reset($copyTablesArray);
			while(list(,$table)=each($copyTablesArray))	{
				if ($table && $TCA[$table] && $table!="pages")	{	// all records under the page is copied.
					$orderby = ($TCA[$table]["ctrl"]["sortby"]) ? " ORDER BY ".$TCA[$table]["ctrl"]["sortby"]." DESC" : "";
					$query = "SELECT uid FROM $table WHERE pid = $uid ".$this->deleteClause($table).$orderby;
					$mres = mysql(TYPO3_db,$query);
					while ($row = mysql_fetch_assoc($mres))	{
						$this->copyRecord($table,$row['uid'], $theNewRootID);	// Copying each of the underlying records...
					}
				}
			}
		}
	}

	/**
	 * Returns array, $CPtable, of pages under the $pid going down to $counter levels
	 * 
	 * @param	[type]		$CPtable: ...
	 * @param	[type]		$pid: ...
	 * @param	[type]		$counter: ...
	 * @param	[type]		$rootID: ...
	 * @return	[type]		...
	 */
	function int_pageTreeInfo($CPtable,$pid,$counter, $rootID)	{
		if ($counter)	{
			$addW =  !$this->admin ? " AND ".$this->BE_USER->getPagePermsClause($this->pMap["show"]) : "";
	 		$query = "SELECT uid FROM pages WHERE pid = $pid ".$this->deleteClause("pages").$addW." ORDER BY sorting DESC";
	 		$mres = mysql(TYPO3_db,$query);
	 		while($row=mysql_fetch_assoc($mres))	{
				if ($row["uid"]!=$rootID)	{
		 			$CPtable[$row["uid"]]=$pid;
		 			if ($counter-1)	{	// If the uid is NOT the rootID of the copyaction and if we are supposed to walk further down
		 				$CPtable = $this->int_pageTreeInfo($CPtable,$row['uid'],$counter-1, $rootID);
		 			}
				}
	 		}
		}
	 	return $CPtable;
	}

	/**
	 * List of all tables (those administrators has access to)
	 * 
	 * @return	[type]		...
	 */
	function compileAdminTables()	{
		global $TCA;
		reset ($TCA);
		$listArr = array();
		while (list($table)=each($TCA))	{
			$listArr[]=$table;
		}
		return $listArr;	
	}
	
	/**
	 * Checks if any uniqueInPid eval input fields are in the record and if so, they are re-written to be correct.
	 * 
	 * @param	[type]		$table: ...
	 * @param	[type]		$uid: ...
	 * @return	[type]		...
	 */
	function fixUniqueInPid($table,$uid)	{
		global $TCA;
		if ($TCA[$table])	{
			t3lib_div::loadTCA($table);
			reset ($TCA[$table]["columns"]);
			$curData=$this->recordInfo($table,$uid,"*");
			$newData=array();
			while (list($field,$conf)=each($TCA[$table]["columns"]))	{
				if ($conf["config"]["type"]=="input")	{
					$evalCodesArray = t3lib_div::trimExplode(",",$conf["config"]["eval"],1);
					if (in_array("uniqueInPid",$evalCodesArray))	{
						$newV = $this->getUnique($table,$field,$curData[$field],$uid,$curData["pid"]);
						if (strcmp($newV,$curData[$field]))	{
							$newData[$field]=$newV;
						}
					}
				}
			}
				// IF there are changed fields, then update the database
			if (count($newData))	{
				$this->updateDB($table,$uid,$newData);
			}
		}
	}

	/**
	 * When er record is copied you can specify fields from the previous record which should be copied into the new one
	 * This function is also called with new elements. But then $update must be set to zero and $newData containing the data array. In that case data in the incoming array is NOT overridden. (250202)
	 * 
	 * @param	[type]		$table: ...
	 * @param	[type]		$uid: ...
	 * @param	[type]		$prevUid: ...
	 * @param	[type]		$update: ...
	 * @param	[type]		$newData: ...
	 * @return	[type]		...
	 */
	function fixCopyAfterDuplFields($table,$uid,$prevUid,$update, $newData=array())	{
		global $TCA;
		if ($TCA[$table] && $TCA[$table]["ctrl"]["copyAfterDuplFields"])	{
			t3lib_div::loadTCA($table);
			$prevData=$this->recordInfo($table,$prevUid,"*");
			$theFields = t3lib_div::trimExplode(",",$TCA[$table]["ctrl"]["copyAfterDuplFields"],1);
			reset($theFields);
			while(list(,$field)=each($theFields))	{
				if ($TCA[$table]["columns"][$field] && ($update || !isset($newData[$field])))	{
					$newData[$field]=$prevData[$field];
				}
			}
			if ($update && count($newData))	{
				$this->updateDB($table,$uid,$newData);
			}
		}
		return $newData;
	}

	/**
	 * Returns all fieldnames from a table which are a list of files
	 * 
	 * @param	[type]		$table: ...
	 * @return	[type]		...
	 */
	function extFileFields ($table)	{
		global $TCA;
		$listArr=array();
		t3lib_div::loadTCA($table);
		if ($TCA[$table]["columns"])	{
			reset($TCA[$table]["columns"]);
			while (list($field,$configArr)=each($TCA[$table]["columns"]))	{
				if ($configArr["config"]["type"]=="group" && $configArr["config"]["internal_type"]=="file")	{
					$listArr[]=$field;
				}
			}
		}
		return $listArr;
	}

	/**
	 * Get copy header
	 * 
	 * @param	[type]		$table: ...
	 * @param	[type]		$pid: ...
	 * @param	[type]		$field: ...
	 * @param	[type]		$value: ...
	 * @param	[type]		$count: ...
	 * @param	[type]		$prevTitle: ...
	 * @return	[type]		...
	 */
	function getCopyHeader ($table,$pid,$field,$value,$count,$prevTitle="")	{
		global $TCA;
		if ($count)	{
			$checkTitle = $value.sprintf($this->prependLabel($table),$count);
		}	else {
			$checkTitle = $value;
		}
		if ($prevTitle != $checkTitle || $count<100)	{
			$query = "SELECT uid FROM ".$table." WHERE pid=".$pid." AND ".$field."='".addslashes($checkTitle)."'".$this->deleteClause($table)." LIMIT 1";
			$res = mysql(TYPO3_db,$query);
			echo mysql_error();
			if (mysql_num_rows($res))	{
				return $this->getCopyHeader($table,$pid,$field,$value,$count+1,$checkTitle);
			} else {
				return $checkTitle;
			}
		} else {return $checkTitle;}
	}

	/**
	 * Get the final pid based on $table and $pid ($destPid type... pos/neg)
	 * 
	 * @param	[type]		$table: ...
	 * @param	[type]		$pid: ...
	 * @return	[type]		...
	 */
	function resolvePid ($table,$pid)	{
		global $TCA;
		$pid=intval($pid);
		if ($pid < 0)	{
			$query = "SELECT pid FROM ".$table." WHERE uid=".abs($pid);
			$res = mysql(TYPO3_db,$query);
			$row = mysql_fetch_assoc($res);
			$pid = $row["pid"];
		}
		return $pid;
	}

	/**
	 * return prepend label
	 * 
	 * @param	[type]		$table: ...
	 * @return	[type]		...
	 */
	function prependLabel ($table)	{
		global $TCA;
		if (is_object($GLOBALS["LANG"]))	{
			$label = $GLOBALS["LANG"]->sL($TCA[$table]["ctrl"]["prependAtCopy"]);
		} else {
			list($label) = explode("|",$TCA[$table]["ctrl"]["prependAtCopy"]);
		}
		return $label;
	}
	
	/**
	 * Removes the prependAtCopy prefix on values
	 * 
	 * @param	[type]		$table: ...
	 * @param	[type]		$value: ...
	 * @return	[type]		...
	 */
	function clearPrefixFromValue($table,$value)	{
		global $TCA;
		$regex = sprintf(quotemeta($this->prependLabel($table)),"[0-9]*")."$";
		return @ereg_replace($regex,"",$value);
	}
	
	/**
	 * Processes the list of DBrecords registered by the copy-function.
	 * 
	 * @return	[type]		...
	 */
	function remapListedDBRecords()	{
		global $TCA;
//		debug($this->registerDBList);
//		debug($this->copyMappingArray_merged);
		if (count($this->registerDBList))	{
			reset($this->registerDBList);
			while(list($table,$records)=each($this->registerDBList))	{
				t3lib_div::loadTCA($table);
				reset($records);
				while(list($uid,$fields)=each($records))	{
					$newData=array();
					$theUidToUpdate = $this->copyMappingArray_merged[$table][$uid];
					reset($fields);
					while(list($fieldName,$value)=each($fields))	{
						$conf=$TCA[$table]["columns"][$fieldName]["config"];
						$set=0;
						$allowedTables = $conf["type"]=="group" ? $conf["allowed"] : $conf["foreign_table"].",".$conf["neg_foreign_table"];
						$prependName = $conf["type"]=="group" ? $conf["prepend_tname"] : "";
						$dontRemapTables = t3lib_div::trimExplode(",",$conf["dontRemapTablesOnCopy"],1);

						$dbAnalysis = t3lib_div::makeInstance("t3lib_loadDBGroup");
						$dbAnalysis->registerNonTableValues = ($conf['type']=='select' && $conf['allowNonIdValues']) ? 1 : 0;
						$dbAnalysis->start($value,$allowedTables, $conf["MM"], $theUidToUpdate);

						reset($dbAnalysis->itemArray);
						while(list($k,$v) = each($dbAnalysis->itemArray))	{
							$mapID = $this->copyMappingArray_merged[$v["table"]][$v["id"]];
							if ($mapID && !in_array($v["table"],$dontRemapTables))	{
								$dbAnalysis->itemArray[$k]["id"]=$mapID;
								$set=1;
							}
						}
						if ($set)	{
							if ($conf["MM"])	{
								$dbAnalysis->writeMM($conf["MM"],$theUidToUpdate,$prependName);
								$valueArray = array();
							} else {
								$vArray = $dbAnalysis->getValueArray($prependName);
								if ($conf['type']=='select')	{
									$vArray = $dbAnalysis->convertPosNeg($vArray,$conf['foreign_table'],$conf['neg_foreign_table']);
								}
								$newData[$fieldName] = implode(",",$vArray);
							}
						}
					}
					if (count($newData))	{	// If any fields were changed, those fields are updated!
						$this->updateDB($table,$theUidToUpdate,$newData);
	//					debug($newData);
					}
				}
			}
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$table: ...
	 * @param	[type]		$field: ...
	 * @param	[type]		$filelist: ...
	 * @param	[type]		$func: ...
	 * @return	[type]		...
	 */
	function extFileFunctions($table,$field,$filelist,$func)	{
		global $TCA;
		t3lib_div::loadTCA($table);
		$uploadFolder = $TCA[$table]["columns"][$field]["config"]["uploadfolder"];
		if ($uploadFolder && trim($filelist))	{
			$uploadPath = $this->destPathFromUploadFolder($uploadFolder);
			$fileArray = explode(",",$filelist);
			while (list(,$theFile)=each($fileArray))	{
				$theFile=trim($theFile);
				if ($theFile)	{
					switch($func)	{
						case "deleteAll":
							if (@is_file($uploadPath."/".$theFile))	{
								unlink ($uploadPath."/".$theFile);
							} else {
								$this->log($table,0,3,0,100,"Delete: Referenced file that was supposed to be deleted together with it's record didn't exist");
							}
						break;
					}
				}
			}
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$table: ...
	 * @param	[type]		$uid: ...
	 * @param	[type]		$noRecordCheck: ...
	 * @return	[type]		...
	 */
	function deleteRecord($table,$uid, $noRecordCheck)	{
			// This function may not be used to delete pages-records unless the underlying records are already deleted
			// If $noRecordCheck is set, then the function does not check permissions
		global $TCA;
		$uid = intval($uid);
		if ($TCA[$table] && $uid)	{
			$deleteRow = $TCA[$table]["ctrl"]["delete"];
			if ($noRecordCheck || $this->doesRecordExist($table,$uid,"delete"))	{
				if ($deleteRow)	{
					$sortOpt = $TCA[$table]["ctrl"]["sortby"];
					if ($sortOpt)	{		// If the table is sorted, then the sorting number is set very high
						$sortOpt = ", ".$sortOpt." = 1000000000";
					} else {
						$sortOpt = "";
					}
					$query="update $table set $deleteRow = 1".$sortOpt." where uid = $uid";
				} else {
					$query="delete from $table where uid = $uid";
					$fileFieldArr = $this->extFileFields($table);		// Fetches all fields that holds references to files
					if (count($fileFieldArr))	{
						$preQuery = "SELECT ".implode(",",$fileFieldArr)." FROM $table WHERE uid = $uid";
						$mres = mysql(TYPO3_db,$preQuery);
						if ($row=mysql_fetch_assoc($mres))	{
							$fArray = $fileFieldArr;
							reset($fArray);
							while (list(,$theField)=each($fArray))	{	// MISSING: Support for MM file relations
								$this->extFileFunctions($table,$theField,$row[$theField],"deleteAll");		// This deletes files that belonged to this record.
							}
						} else {
							$this->log($table,$uid,3,0,100,"Delete: Zero rows in result when trying to read filenames from record which should be deleted");
						}
					}
				}
				if ($uid)	{		// ekstra check
					$this->clear_cache($table,$uid);
					@mysql(TYPO3_db,$query);
					if ($this->debug)	{echo $query."<BR>".mysql_error();}
					if (!mysql_error())	{
						$this->log($table,$uid,3,0,0,"");
					} else {
						$this->log($table,$uid,3,0,100,mysql_error());
					}
				}
				$this->clear_cache($table,$uid);	// clear cache
			} else {
				$this->log($table,$uid,3,0,1,"Attempt to delete record without delete-permissions");
			}
		}		
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$uid: ...
	 * @return	[type]		...
	 */
	function deletePages($uid)	{
		if ($this->doesRecordExist("pages",$uid,"delete"))	{	// If we may at all delete this page
			if ($this->deleteTree)	{
				$brExist = $this->doesBranchExist("",$uid,$this->pMap["delete"],1);	// returns the branch
				if ($brExist != -1)	{	// Checks if we had permissions
					if ($this->noRecordsFromUnallowedTables($brExist.$uid))	{
						$uidArray = explode(",",$brExist);
						while (list(,$listUid)=each($uidArray))	{
							if (trim($listUid))	{
								$this->deleteSpecificPage($listUid);
							}
						}
						$this->deleteSpecificPage($uid);
					} else {
						$this->log("pages",$uid,3,0,1,"Attempt to delete records from disallowed tables");
					}
				} else {
					$this->log("pages",$uid,3,0,1,"Attempt to delete pages in branch without permissions");
				}						
			} else {
				$brExist = $this->doesBranchExist("",$uid,$this->pMap["delete"],1);	// returns the branch
				if ($brExist == "")	{	// Checks if branch exists
					if ($this->noRecordsFromUnallowedTables($uid))	{
						$this->deleteSpecificPage($uid);
					} else {
						$this->log("pages",$uid,3,0,1,"Attempt to delete records from disallowed tables");
					}
				} else {
					$this->log("pages",$uid,3,0,1,"Attempt to delete page which has subpages");
				}
			}
		} else {
			$this->log("pages",$uid,3,0,1,"Attempt to delete page without permissions");
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$uid: ...
	 * @return	[type]		...
	 */
	function deleteSpecificPage($uid)	{
		// internal function !!
		global $TCA;
		reset ($TCA);
		$uid = intval($uid);
		if ($uid)	{
			while (list($table)=each($TCA))	{
				if ($table!="pages")	{
					$query = "select uid from $table where pid = $uid ".$this->deleteClause($table);
					$mres = mysql(TYPO3_db,$query);
					while ($row = mysql_fetch_assoc($mres))	{
						$this->deleteRecord($table,$row['uid'], 1);
					}
				}
			}
			$this->deleteRecord("pages",$uid, 1);
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$inList: ...
	 * @return	[type]		...
	 */
	function noRecordsFromUnallowedTables($inList)	{
		// used by the deleteFunctions to check if there are records from disallowed tables under the pages to be deleted. Return true, if permission granted
		global $TCA;
		reset ($TCA);
		$inList = trim($this->rmComma(trim($inList)));
		if ($inList && !$this->admin)	{
			while (list($table)=each($TCA))	{
				$mres = mysql(TYPO3_db,"select count(*) from $table where pid in ($inList)");
				$count = mysql_fetch_row($mres);
				if ($count[0] && ($this->tableReadOnly($table) || !$this->checkModifyAccessList($table)))	{
					return false;
				}
			}
		}
		return true;
	}






	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	/*********************************************
	 *
	 * MISC FUNCTIONS
	 * 
	 ********************************************/

	/**
	 * Returning sorting number
	 * 
	 * $table is the tablename, 
	 * $uid is set, if the record exists already, 
	 * $pid is the pointer to the position, neg=before other record, pos=on-top of page. $pid must be an integer
	 * 
	 * Returns false if the sortby field does not exist.
	 * 
	 * @param	[type]		$table: ...
	 * @param	[type]		$uid: ...
	 * @param	[type]		$pid: ...
	 * @return	[type]		...
	 */
	function getSortNumber($table,$uid,$pid)	{
		global $TCA;
		if ($TCA[$table] && $TCA[$table]["ctrl"]["sortby"])	{
			$sortRow = $TCA[$table]["ctrl"]["sortby"];
			if ($pid>=0)	{	// Sorting number is in the top
				$res=mysql(TYPO3_db,"SELECT $sortRow,pid,uid FROM $table WHERE pid ='$pid' ".$this->deleteClause($table)." ORDER BY $sortRow ASC LIMIT 1");		// Fetches the first record under this pid
				if ($row=mysql_fetch_assoc($res))	{	// There was a page
					if ($row['uid']==$uid)	{	// The top record was the record it self, so we return its current sortnumber
						if ($this->debug)	{echo $table.": The top page was it self, pid=".$pid."..<BR>";}
						return $row[$sortRow];
					}
					if ($row[$sortRow] < 1) {	// If the pages sortingnumber < 1 we must resort the records under this pid
						$this->resorting($table,$pid,$sortRow,0);
						return $this->sortIntervals;
					} else {
						return floor($row[$sortRow]/2);
					}
				} else {	// No pages, so we choose the default value as sorting-number
					if ($this->debug)	{echo $table.": No previous rec on this parent page, pid=".$pid."..<BR>";}
					return $this->sortIntervals;
				}
			} else {	// Sorting number is inside the list
				$res=mysql(TYPO3_db,"select $sortRow,pid,uid from $table where uid ='".abs($pid)."' ".$this->deleteClause($table));		// Fetches the record which is supposed to be the prev record
				if ($row=mysql_fetch_assoc($res))	{	// There was a record
					if ($row['uid']==$uid)	{	// If the record happends to be it self
						if ($this->debug)	{echo $table.": The previous page was it self, uid=".$uid."..<BR>";}
						$sortNumber = $row[$sortRow];
					} else {
						$subres=mysql(TYPO3_db,"SELECT $sortRow,pid,uid FROM $table WHERE pid='".$row['pid']."' AND $sortRow>='".$row[$sortRow]."' ".$this->deleteClause($table)." ORDER BY $sortRow ASC LIMIT 2");		// Fetches the next record in order to calculate the in bewteen sortNumber
						if (mysql_num_rows($subres)==2)	{	// There was a record afterwards
							mysql_data_seek($subres, 1);	// forwards to the second result
							$subrow=mysql_fetch_assoc($subres);	// There was a record afterwards
							$sortNumber = $row[$sortRow]+ floor(($subrow[$sortRow]-$row[$sortRow])/2);	// The sortNumber is found in between these values
							if ($sortNumber<=$row[$sortRow] || $sortNumber>=$subrow[$sortRow])	{	// The sortNumber happend NOT to be between the two surrounding numbers, so we'll have to resort the list
								$sortNumber = $this->resorting($table,$row['pid'],$sortRow,  $row['uid']);	// By this special param, resorting reserves and returns the sortnumber after the uid
							}
						} else {	// If after the last record in the list, we just add the sortInterval to the last sortvalue
							$sortNumber = $row[$sortRow]+$this->sortIntervals;
						}
					}
					return Array("pid"=>$row['pid'], "sortNumber"=>$sortNumber);
				} else {
					$propArr = $this->getRecordProperties($table,$uid);
					$this->log($table,$uid,4,0,1,"Attempt to move record '%s' (%s) to after a non-existing record (uid=%s)",1,array($propArr["header"],$table.":".$uid,abs($pid)),$propArr["pid"]);	// OK, dont insert $propArr["event_pid"] here...
					return false;	// There MUST be a page or else this cannot work
				}				
			}
		}
	}

	/**
	 * Resorts a table.
	 * 
	 * Used internally by getSortNumber()
	 * 
	 * @param	[type]		$table: ...
	 * @param	[type]		$pid: ...
	 * @param	[type]		$sortRow: ...
	 * @param	[type]		$return_SortNumber_After_This_Uid: ...
	 * @return	[type]		...
	 */
	function resorting($table,$pid,$sortRow, $return_SortNumber_After_This_Uid) {
		global $TCA;
		if ($TCA[$table] && $sortRow && $TCA[$table]["ctrl"]["sortby"]==$sortRow)	{
			if ($this->debug)	{echo $table.": Resorting pid=".$pid."..<BR>";}
			$returnVal = 0;
			$res=mysql(TYPO3_db,"select uid from $table where pid='$pid' ".$this->deleteClause($table)." order by $sortRow asc");
			$intervals = $this->sortIntervals;
			$i= $intervals*2;
			while ($row=mysql_fetch_assoc($res)) {
				$uid=intval($row["uid"]);
				if ($uid)	{
					mysql(TYPO3_db,"UPDATE $table SET $sortRow='$i' where uid=$uid");
					if ($uid==$return_SortNumber_After_This_Uid)	{		// This is used to return a sortingValue if the list is resorted because of inserting records inside the list and not in the top
						$i=$i+$intervals;
						$returnVal=$i;
					}
				} else {die ("Fatal ERROR!! No Uid at resorting.");}
				$i=$i+$intervals;
			}
			return $returnVal;
		}
	}

	/**
	 * Returns the $input string without a comma in the end
	 * 
	 * @param	[type]		$input: ...
	 * @return	[type]		...
	 */
	function rmComma ($input)	{
		return ereg_replace(",$","",$input);
	}

	/**
	 * Converts a HTML entity (like &#123;) to the character "123"
	 * 
	 * @param	string		Input string
	 * @return	string		Output string
	 */	
	function convNumEntityToByteValue($input)	{
		$token = md5(microtime());
		$parts = explode($token,ereg_replace('(&#([0-9]+);)',$token.'\2'.$token,$input));

		foreach($parts as $k => $v)	{
			if ($k%2)	{
				$v = intval($v);
				if ($v > 32)	{	// Just to make sure that control bytes are not converted.
					$parts[$k] =chr(intval($v));
				}
			}
		}	
		
		return implode('',$parts);
	}

	/**
	 * Returns absolute destination path for the uploadfolder, $folder
	 * 
	 * @param	[type]		$folder: ...
	 * @return	[type]		...
	 */
	function destPathFromUploadFolder ($folder)	{
		return PATH_site.$folder;
	}

	/**
	 * Checks if $id is a uid in the rootline from $dest
	 * 
	 * @param	[type]		$dest: ...
	 * @param	[type]		$id: ...
	 * @return	[type]		...
	 */
	function destNotInsideSelf ($dest,$id)	{
		$loopCheck = 100;
		$deleteClause = $this->deleteClause("pages");
		$dest=intval($dest);
		$id=intval($id);
		if ($dest==$id)	{
			return false;
		}
		while ($dest!=0 && $loopCheck>0)	{
			$loopCheck--;
			$query = "select pid, uid from pages where uid = '$dest' ".$deleteClause;
			$res = mysql(TYPO3_db,$query);
			if ($row = mysql_fetch_assoc($res))	{
				$dest = $row[pid];
				if ($dest==$id)	{
					return false;
				}
			} else {
				return false;
			}
		}
		return true;
	}

	/**
	 * Generate an array of fields to be excluded from editing for the user.
	 * 
	 * @return	[type]		...
	 */
	function getExcludeListArray()	{
		global $TCA;
		$list = array();
		reset($TCA);
		while (list($table)=each($TCA))	{
			t3lib_div::loadTCA($table);
			while (list($field,$config)=each($TCA[$table]["columns"]))	{
				if ($config["exclude"] && !t3lib_div::inList($this->BE_USER->groupData["non_exclude_fields"],$table.":".$field))	{
					$list[]=$table."-".$field;
				}
			}
		}
		return $list;
	}

	/**
	 * Checks if there are records on a page from tables that are not allowed
	 * 
	 * Returns a list of the tables that are 'present' on the page but not allowed with the page_uid/doktype
	 * 
	 * @param	[type]		$page_uid: ...
	 * @param	[type]		$doktype: ...
	 * @return	[type]		...
	 */
	function doesPageHaveUnallowedTables($page_uid,$doktype)	{
		global $TCA, $PAGES_TYPES;
		$page_uid = intval($page_uid);
		if (!$page_uid)	{return false;}	// Not a number. Probably a new page

		$allowedTableList = isset($PAGES_TYPES[$doktype]["allowedTables"]) ? $PAGES_TYPES[$doktype]["allowedTables"] : $PAGES_TYPES["default"]["allowedTables"];
		$allowedArray = t3lib_div::trimExplode(",",$allowedTableList,1);
		if (strstr($allowedTableList,"*"))	{	// If all tables is OK the return true
			return false;	// OK...
		}
		reset ($TCA);
		$tableList = array();
		while (list($table)=each($TCA))	{
			if (!in_array($table,$allowedArray))	{	// If the table is not in the allowed list, check if there are records...
				$mres = mysql(TYPO3_db,"select count(*) from $table where pid = $page_uid");
				$count = mysql_fetch_row($mres);
				if ($count[0])	{
					$tableList[]=$table;
				}
			}
		}
		return implode($tableList,",");
	}

	/**
	 * Returns delete-clause for the $table
	 * 
	 * @param	[type]		$table: ...
	 * @return	[type]		...
	 */
	function deleteClause($table)	{
			// Returns the proper delete-clause if any for a table from TCA
		global $TCA;
		if ($TCA[$table]["ctrl"]["delete"])	{
			return " AND NOT ".$table.".".$TCA[$table]["ctrl"]["delete"];
		} else {
			return "";
		}
	}

	/**
	 * Checks if the $table is readOnly
	 * 
	 * @param	[type]		$table: ...
	 * @return	[type]		...
	 */
	function tableReadOnly($table)	{
			// returns true if table is readonly
		global $TCA;
		return ($TCA[$table]["ctrl"]["readOnly"] ? 1 : 0);	
	}

	/**
	 * Checks if the $table is only editable by admin-users
	 * 
	 * @param	[type]		$table: ...
	 * @return	[type]		...
	 */
	function tableAdminOnly($table)	{
			// returns true if table is admin-only
		global $TCA;
		return ($TCA[$table]["ctrl"]["adminOnly"] ? 1 : 0);	
	}

	/**
	 * Finds the Position-ID for this page. This is very handy when we need to update a page in the pagetree in the TYPO3 interface.
	 * 
	 * Usage: 2 (class t3lib_tcemain)
	 * 
	 * @param	[type]		$uid: ...
	 * @return	[type]		...
	 */
	function getInterfacePagePositionID($uid)	{
		global $TCA;
		$perms_clause = $this->BE_USER->getPagePermsClause(1);		
		$deleted = $TCA['pages']['ctrl']['delete'] ? 'AND NOT A.'.$TCA['pages']['ctrl']['delete'].' AND NOT pages.'.$TCA['pages']['ctrl']['delete'].' ' : '';
		
			// This fetches a list of 1 or 2 pages, where - if 2 - the 2nd is the page BEFORE this ($uid). If 1 then the page ($uid) is at the top itself
 		$query='SELECT pages.uid, pages.pid FROM pages A, pages WHERE A.pid=pages.pid AND A.uid = "'.$uid.'" '.
				$deleted.
				'AND pages.sorting<=A.sorting '.
				'AND '.$perms_clause.
				' ORDER BY pages.sorting DESC LIMIT 2';
		$subres = mysql(TYPO3_db,$query);
		echo mysql_error();
		if (mysql_num_rows($subres)==2)	{	// There was a record before
			mysql_data_seek($subres, 1);	// forwards to the second result
			$row=mysql_fetch_assoc($subres);
			return -$row['uid'];
		} else {
			$row=mysql_fetch_assoc($subres);
			return $row['pid'];
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$tscPID: ...
	 * @return	[type]		...
	 */
	function getTCEMAIN_TSconfig($tscPID)	{
		if (!isset($this->cachedTSconfig[$tscPID]))	{
			$this->cachedTSconfig[$tscPID] = $this->BE_USER->getTSConfig("TCEMAIN",t3lib_BEfunc::getPagesTSconfig($tscPID));
		}
		return $this->cachedTSconfig[$tscPID]["properties"];
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$table: ...
	 * @param	[type]		$TSconfig: ...
	 * @return	[type]		...
	 */
	function getTableEntries($table,$TSconfig)	{
		$tA = is_array($TSconfig["table."][$table."."]) ? $TSconfig["table."][$table."."] : array();;
		$dA = is_array($TSconfig["default."]) ? $TSconfig["default."] : array();
		return t3lib_div::array_merge_recursive_overrule($dA,$tA);
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$table: ...
	 * @param	[type]		$id: ...
	 * @param	[type]		$logId: ...
	 * @return	[type]		...
	 */
	function setHistory($table,$id,$logId)		{
		if (isset($this->historyRecords[$table.":".$id]))	{
//debug("History",-1);
			list($tscPID)=t3lib_BEfunc::getTSCpid($table,$id,"");
			$TSConfig = $this->getTCEMAIN_TSconfig($tscPID);
			
			$tE = $this->getTableEntries($table,$TSConfig);
			$keepEntries= strcmp($tE["history."]["keepEntries"],"") ? t3lib_div::intInRange($tE["history."]["keepEntries"],0,200) : 10;
			$maxAgeSeconds=60*60*24*(strcmp($tE["history."]["maxAgeDays"],"") ? t3lib_div::intInRange($tE["history."]["maxAgeDays"],0,200) : 7);	// one week
			$this->clearHistory($table,$id,t3lib_div::intInRange($keepEntries-1,0),$maxAgeSeconds);
//debug($keepEntries);
			if ($keepEntries)	{
				$fields_values=array();
				$fields_values["history_data"]=serialize($this->historyRecords[$table.":".$id]);
				$fields_values["fieldlist"]=implode(",",array_keys($this->historyRecords[$table.":".$id]["newRecord"]));
				$fields_values["tstamp"]=time();
				$fields_values["tablename"]=$table;
				$fields_values["recuid"]=$id;
				$fields_values["sys_log_uid"]=$logId;
				$query = t3lib_BEfunc::DBcompileInsert("sys_history",$fields_values);
				$res = mysql(TYPO3_db,$query);
			}
		}
	}

	/**
	 * 604800 = 60*60*24*7
	 * $keepEntries (int+) defines the number of current entries from sys_history table to keep in addition to the new one which is put in.
	 * $maxAgeSeconds (int+) however will set a max age in seconds so that any entry older than current time minus the age removed no matter what. If zero, this is not effective.
	 * All snapshots are excluded of course.
	 * 
	 * @param	[type]		$table: ...
	 * @param	[type]		$id: ...
	 * @param	[type]		$keepEntries: ...
	 * @param	[type]		$maxAgeSeconds: ...
	 * @return	[type]		...
	 */
	function clearHistory($table,$id,$keepEntries=10,$maxAgeSeconds=604800)		{
		$tstampLimit = $maxAgeSeconds ? time()-$maxAgeSeconds : 0;
		
		$select = "SELECT uid,tstamp FROM sys_history";
		$where=" WHERE 
			tablename='".$table."' 
			AND recuid='".$id."' 
			AND snapshot=0";

		$res = mysql(TYPO3_db,$select.$where." ORDER BY uid DESC LIMIT ".intval($keepEntries).",1");
		$resRow = mysql_fetch_assoc($res);
		if ($tstampLimit && intval($resRow["tstamp"])<$tstampLimit)	{
			$res = mysql(TYPO3_db,$select.$where." AND tstamp<".intval($tstampLimit)." ORDER BY uid DESC LIMIT 1");
			$resRow = mysql_fetch_assoc($res);
			$delQ = "DELETE FROM sys_history".$where." AND uid<=".intval($resRow["uid"]);
		} elseif (is_array($resRow)) {
			$delQ = "DELETE FROM sys_history".$where." AND uid<=".intval($resRow["uid"]);
		}
		$res = mysql(TYPO3_db,$delQ);
	}

	/**
	 * Logging actions
	 * 
	 * $table:		The table name
	 * $recuid:		The record uid
	 * 
	 * $action:		The action number. 1=new record, 2=update record, 3= delete record, 4= move record, 5= Check/evaluate
	 * $recpid:		Normally 0 (zero). If set, it indicates that this log-entry is used to notify the backend of a record which is moved to another location
	 * 
	 * $error:		The severity: 0 = message, 1 = error, 2 = System Error, 3 = security notice (admin)
	 * $details:	This is the default, raw error message in english
	 * $details_nr:	This number is unique for every combination of $type and $action. This is the error-message number, which can later be used to translate error messages.
	 * $data:		Array with special information that may go into $details by "%s" marks / sprintf() when the log is shown
	 * $event_pid:	The page_uid (pid) where the event occurred. Used to select log-content for specific pages.
	 * 
	 * @param	[type]		$table: ...
	 * @param	[type]		$recuid: ...
	 * @param	[type]		$action: ...
	 * @param	[type]		$recpid: ...
	 * @param	[type]		$error: ...
	 * @param	[type]		$details: ...
	 * @param	[type]		$details_nr: ...
	 * @param	[type]		$data: ...
	 * @param	[type]		$event_pid: ...
	 * @param	[type]		$NEWid: ...
	 * @return	[type]		...
	 * @see	class.t3lib_userauthgroup.php
	 */
	function log($table,$recuid,$action,$recpid,$error,$details,$details_nr=0,$data=array(),$event_pid=-1,$NEWid="") {
		if ($this->enableLogging)	{
			$type=1;	// Type value for tce_db.php
			if (!$this->storeLogMessages)	{$details="";}
			return $this->BE_USER->writelog($type,$action,$error,$details_nr,$details,$data,$table,$recuid,$recpid,$event_pid,$NEWid);
		}
	}
	
	/**
	 * Print log messages from this request out.
	 * 
	 * @param	[type]		$redirect: ...
	 * @return	[type]		...
	 */
	function printLogErrorMessages($redirect)	{
		$query = "SELECT * FROM sys_log WHERE ".
		"type=1 AND userid=".intval($this->BE_USER->user["uid"])." AND tstamp=".intval($GLOBALS["EXEC_TIME"]).
		" AND error!=0";
		
		$res_log=mysql(TYPO3_db,$query);
		echo mysql_error();
		$errorJS=array();
		while ($row=mysql_fetch_assoc($res_log)) {
			$log_data=unserialize($row["log_data"]);
			$errorJS[]=$row[error].': '.sprintf($row["details"], $log_data[0],$log_data[1],$log_data[2],$log_data[3],$log_data[4]);
		}
		
//		$errorJS[]="lkajsdlfkjsaldkf";
		if (count($errorJS))	{
			$error_doc = t3lib_div::makeInstance("template");
			$error_doc->backPath = "";

			$content.=$error_doc->startPage("tce_db.php Error output");
			
			$lines[]='<tr class="bgColor5"><td colspan=2 align=center><strong>Errors:</strong></td></tr>';
			reset($errorJS);
			while(list(,$line)=each($errorJS))	{
				$lines[]='<tr class="bgColor4"><td valign=top><img'.t3lib_iconWorks::skinImg('','gfx/icon_fatalerror.gif','width="18" height="16"').' alt="" /></td><td>'.htmlspecialchars($line).'</td></tr>';
			}
			
			$lines[]='<tr><td colspan=2 align=center><BR><form action=""><input type="submit" value="Continue" onClick="document.location=\''.$redirect.'\';return false;"></form></td></tr>';
			$content.= '<BR><BR><table border=0 cellpadding=1 cellspacing=1 width=300 align=center>'.implode("",$lines).'</table>';
			$content.= $error_doc->endPage();
			echo $content;
			exit;
		}
	}

	/**
	 * Clears the cache based on a command, $cacheCmd
	 * 
	 * $cacheCmd='pages':	Clears cache for all pages. Requires admin-flag to be set for BE_USER
	 * $cacheCmd='all':		Clears all cache_tables. This is necessary if templates are updated. Requires admin-flag to be set for BE_USER
	 * $cacheCmd=[integer]:		Clears cache for the page pointed to by $cacheCmd (an integer).
	 * 
	 * Can call a list of post processing functions as defined in $TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'] (num array with values being the function references, called by t3lib_div::callUserFunction())
	 * 
	 * @param	string		The cache comment, see above description.
	 * @return	void		
	 */
	function clear_cacheCmd($cacheCmd)	{
		global $TYPO3_CONF_VARS;
		
			// Clear cache for either ALL pages or ALL tables!
		switch($cacheCmd)	{
			case "pages":
				if ($this->admin || $this->BE_USER->getTSConfigVal("options.clearCache.pages"))	{
					if (t3lib_extMgm::isLoaded("cms"))	{
						$res = mysql(TYPO3_db,"DELETE FROM cache_pages");
					}
				}
			break;
			case "all":
				if ($this->admin || $this->BE_USER->getTSConfigVal("options.clearCache.all"))	{
					if (t3lib_extMgm::isLoaded("cms"))	{
						$res = mysql(TYPO3_db,"DELETE FROM cache_pages");
						$res = mysql(TYPO3_db,"DELETE FROM cache_pagesection");
					}
					$res = mysql(TYPO3_db,"DELETE FROM cache_hash");
				}
			break;
		}
			// Clear cache for a page ID!
		if (t3lib_div::testInt($cacheCmd))	{
			if (t3lib_extMgm::isLoaded("cms"))	{
				$res = mysql(TYPO3_db,"DELETE FROM cache_pages WHERE page_id=".intval($cacheCmd));
			}
		}
		
			// Call post processing function for clear-cache:
		if (is_array($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc']))	{
			$_params = array('cacheCmd'=>$cacheCmd);
			foreach($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'] as $_funcRef)	{
				t3lib_div::callUserFunction($_funcRef,$_params,$this);
			}
		}		
	}
}




/*
Log messages:
[action]-[details_nr.]

REMEMBER to UPDATE the real messages set in tools/log/localconf_log.php

0-1:	Referer host '%s' and server host '%s' did not match!
1-11:	Attempt to insert record on page '%s' (%s) where this table, %s, is not allowed
1-12:	Attempt to insert a record on page '%s' (%s) from table '%s' without permissions. Or non-existing page.
2-1:	Attempt to modify table '%s' without permission
2-2:	Attempt to modify record '%s' (%s) without permission. Or non-existing page.
2-10:	Record '%s' (%s) was updated.
2-12:	MySQL error: '%s' (%s)
2-13:	Write-file error: '%s'
4-1:	Attempt to move record '%s' (%s) to after a non-existing record (uid=%s)
4-2:	Moved record '%s' (%s) to page '%s' (%s)
4-3:	Moved record '%s' (%s) from page '%s' (%s)
4-4:	Moved record '%s' (%s) on page '%s' (%s)
4-10:	Attempt to move page '%s' (%s) to inside of its own rootline (at page '%s' (%s))
4-11:	Attempt to insert record on page '%s' (%s) where this table, %s, is not allowed
4-12:	Attempt to insert a record on page '%s' (%s) from table '%s' without permissions. Or non-existing page.
4-13:	Attempt to move record '%s' (%s) to after another record, although the table has no sorting row.
4-14:	Attempt to move record '%s' (%s) without having permissions to do so
5-1:	You cannot change the 'doktype' of page '%s' to the desired value.
5-2:	'doktype' of page '%s' could not be changed because the page contains records from disallowed tables; %s
5-3:	Too few items in the list of values. (%s)
5-10:	Could not delete file '%s' (does not exist). (%s)
5-11:	Copying file '%s' failed!: No destination file (%s) possible!. (%s)
5-12:	Fileextension '%' not allowed. (%s)
5-13:	Filesize (%s) of file '%s' exceeds limit (%s). (%s)
5-14:	The destination (%s) or the source file (%s) does not exist. (%s)
5-15:	Copying to file '%s' failed! (%s)
5-16:	Copying file '%s' failed!: The destination path (%s) may be write protected. Please make it write enabled!. (%s)

*/



if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["t3lib/class.t3lib_tcemain.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["t3lib/class.t3lib_tcemain.php"]);
}
?>
