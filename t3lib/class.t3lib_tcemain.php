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
 * Contains the TYPO3 Core Engine
 *
 * $Id$
 * Revised for TYPO3 3.6 August/2003 by Kasper Skaarhoj
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  199: class t3lib_TCEmain
 *  288:     function start($data,$cmd,$altUserObject='')
 *  321:     function setMirror($mirror)
 *  346:     function setDefaultsFromUserTS($userTS)
 *  369:     function process_uploads($postFiles)
 *  399:     function process_uploads_traverseArray(&$outputArr,$inputArr,$keyToSet)
 *
 *              SECTION: PROCESSING DATA
 *  435:     function process_datamap()
 *  628:     function fillInFieldArray($table,$id,$fieldArray,$incomingFieldArray,$realPid,$status,$tscPID)
 *  819:     function checkModifyAccessList($table)
 *  831:     function isRecordInWebMount($table,$id)
 *  845:     function isInWebMount($pid)
 *  862:     function checkRecordUpdateAccess($table,$id)
 *  887:     function checkRecordInsertAccess($insertTable,$pid,$action=1)
 *  923:     function isTableAllowedForThisPage($page_uid, $checkTable)
 *  958:     function doesRecordExist($table,$id,$perms)
 * 1021:     function doesRecordExist_pageLookUp($id, $perms)
 * 1047:     function doesBranchExist($inList,$pid,$perms, $recurse)
 * 1082:     function pageInfo($id,$field)
 * 1102:     function recordInfo($table,$id,$fieldList)
 * 1119:     function getRecordProperties($table,$id)
 * 1132:     function getRecordPropertiesFromRow($table,$row)
 * 1151:     function setTSconfigPermissions($fieldArray,$TSConfig_p)
 * 1167:     function newFieldArray($table)
 * 1198:     function overrideFieldArray($table,$data)
 * 1211:     function assemblePermissions($string)
 *
 *              SECTION: Evaluation of input values
 * 1261:     function checkValue($table,$field,$value,$id,$status,$realPid,$tscPID)
 * 1321:     function checkValue_SW($res,$value,$tcaFieldConf,$table,$id,$curValue,$status,$realPid,$recFID,$field,$uploadedFiles,$tscPID)
 * 1367:     function checkValue_input($res,$value,$tcaFieldConf,$PP,$field='')
 * 1405:     function checkValue_check($res,$value,$tcaFieldConf,$PP)
 * 1428:     function checkValue_radio($res,$value,$tcaFieldConf,$PP)
 * 1454:     function checkValue_group_select($res,$value,$tcaFieldConf,$PP,$uploadedFiles,$field)
 * 1554:     function checkValue_group_select_file($valueArray,$tcaFieldConf,$curValue,$uploadedFileArray,$status,$table,$id,$recFID)
 * 1707:     function checkValue_flex($res,$value,$tcaFieldConf,$PP,$uploadedFiles,$field)
 * 1765:     function checkValue_flexArray2Xml($array)
 * 1782:     function _DELETE_FLEX_FORMdata(&$valueArrayToRemoveFrom,$deleteCMDS)
 *
 *              SECTION: Helper functions for evaluation functions.
 * 1830:     function getUnique($table,$field,$value,$id,$newPid=0)
 * 1868:     function checkValue_input_Eval($value,$evalArray,$is_in)
 * 1956:     function checkValue_group_select_processDBdata($valueArray,$tcaFieldConf,$id,$status,$type)
 * 1989:     function checkValue_group_select_explodeSelectGroupValue($value)
 * 2012:     function checkValue_flex_procInData($dataPart,$dataPart_current,$uploadedFiles,$dataStructArray,$pParams,$callBackFunc='')
 * 2051:     function checkValue_flex_procInData_travDS(&$dataValues,$dataValues_current,$uploadedFiles,$DSelements,$pParams,$callBackFunc,$structurePath)
 *
 *              SECTION: Storing data to Database Layer
 * 2204:     function updateDB($table,$id,$fieldArray)
 * 2250:     function compareFieldArrayWithCurrentAndUnset($table,$id,$fieldArray)
 * 2304:     function insertDB($table,$id,$fieldArray,$newVersion=FALSE,$suggestedUid=0)
 * 2376:     function checkStoredRecord($table,$id,$fieldArray,$action)
 * 2412:     function dbAnalysisStoreExec()
 * 2428:     function removeRegisteredFiles()
 * 2445:     function clear_cache($table,$uid)
 * 2549:     function getPID($table,$uid)
 *
 *              SECTION: PROCESSING COMMANDS
 * 2592:     function process_cmdmap()
 * 2680:     function moveRecord($table,$uid,$destPid)
 * 2824:     function copyRecord($table,$uid,$destPid,$first=0,$overrideValues=array(),$excludeFields='')
 * 2933:     function copyRecord_raw($table,$uid,$pid,$overrideArray=array())
 * 2989:     function insertNewCopyVersion($table,$fieldArray,$realPid)
 * 3040:     function copyRecord_procBasedOnFieldType($table,$uid,$field,$value,$row,$conf)
 * 3093:     function copyRecord_localize($table,$uid,$language)
 * 3152:     function copyRecord_flexFormCallBack($pParams, $dsConf, $dataValue, $dataValue_ext1, $dataValue_ext2)
 * 3180:     function copyRecord_procFilesRefs($conf, $uid, $value)
 * 3231:     function copyPages($uid,$destPid)
 * 3286:     function copySpecificPage($uid,$destPid,$copyTablesArray,$first=0)
 * 3316:     function versionizeRecord($table,$id,$label)
 * 3369:     function versionizePages($uid,$label)
 * 3426:     function rawCopyPageContent($old_pid,$new_pid,$copyTablesArray)
 * 3451:     function version_swap($table,$id,$swapWith,$swapContent)
 * 3575:     function int_pageTreeInfo($CPtable,$pid,$counter, $rootID)
 * 3596:     function compileAdminTables()
 * 3613:     function fixUniqueInPid($table,$uid)
 * 3649:     function fixCopyAfterDuplFields($table,$uid,$prevUid,$update, $newData=array())
 * 3674:     function extFileFields ($table)
 * 3700:     function getCopyHeader($table,$pid,$field,$value,$count,$prevTitle='')
 * 3729:     function prependLabel($table)
 * 3746:     function resolvePid($table,$pid)
 * 3764:     function clearPrefixFromValue($table,$value)
 * 3775:     function remapListedDBRecords()
 * 3858:     function remapListedDBRecords_flexFormCallBack($pParams, $dsConf, $dataValue, $dataValue_ext1, $dataValue_ext2)
 * 3884:     function remapListedDBRecords_procDBRefs($conf, $value, $MM_localUid)
 * 3929:     function extFileFunctions($table,$field,$filelist,$func)
 * 3961:     function deleteRecord($table,$uid, $noRecordCheck)
 * 4019:     function deletePages($uid)
 * 4061:     function deleteSpecificPage($uid)
 * 4085:     function noRecordsFromUnallowedTables($inList)
 *
 *              SECTION: MISC FUNCTIONS
 * 4147:     function getSortNumber($table,$uid,$pid)
 * 4212:     function resorting($table,$pid,$sortRow, $return_SortNumber_After_This_Uid)
 * 4241:     function rmComma ($input)
 * 4251:     function convNumEntityToByteValue($input)
 * 4273:     function destPathFromUploadFolder ($folder)
 * 4284:     function destNotInsideSelf ($dest,$id)
 * 4310:     function getExcludeListArray()
 * 4334:     function doesPageHaveUnallowedTables($page_uid,$doktype)
 * 4367:     function deleteClause($table)
 * 4383:     function tableReadOnly($table)
 * 4395:     function tableAdminOnly($table)
 * 4409:     function getInterfacePagePositionID($uid)
 * 4442:     function isReferenceField($conf)
 * 4452:     function getTCEMAIN_TSconfig($tscPID)
 * 4466:     function getTableEntries($table,$TSconfig)
 * 4480:     function setHistory($table,$id,$logId)
 * 4517:     function clearHistory($table,$id,$keepEntries=10,$maxAgeSeconds=604800)
 * 4565:     function log($table,$recuid,$action,$recpid,$error,$details,$details_nr=0,$data=array(),$event_pid=-1,$NEWid='')
 * 4579:     function printLogErrorMessages($redirect)
 * 4641:     function clear_cacheCmd($cacheCmd)
 * 4717:     function removeCacheFiles()
 *
 * TOTAL FUNCTIONS: 101
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */




// *******************************
// Including necessary libraries
// *******************************
require_once (PATH_t3lib.'class.t3lib_loaddbgroup.php');
require_once (PATH_t3lib.'class.t3lib_parsehtml_proc.php');
require_once (PATH_t3lib.'class.t3lib_stdgraphic.php');
require_once (PATH_t3lib.'class.t3lib_basicfilefunc.php');













/**
 * This is the TYPO3 Core Engine class for manipulation of the database
 * This class is used by eg. the tce_db.php script which provides an the interface for POST forms to this class.
 *
 * Dependencies:
 * - $GLOBALS['TCA'] must exist
 * - $GLOBALS['LANG'] (languageobject) may be preferred, but not fatal.
 *
 * Note: Seems like many instances of array_merge() in this class are candidates for t3lib_div::array_merge() if integer-keys will some day make trouble...
 *
 * tce_db.php for further comments and SYNTAX! Also see document 'Inside TYPO3' for details.
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_TCEmain	{
	var $log_table = 'sys_log';

	var $checkStoredRecords = 1;	// This will read the record after having updated or inserted it. If anything is not properly submitted an error is written to the log. This feature consumes extra time by selecting records
	var $checkStoredRecords_loose=1;	// If set, values '' and 0 will equal each other when the stored records are checked.
	var $sortIntervals = 256;		// The interval between sorting numbers used with tables with a 'sorting' field defined. Min 1

	var $deleteTree = 0;			// Boolean. If this is set, then a page is deleted by deleting the whole branch under it (user must have deletepermissions to it all). If not set, then the page is delete ONLY if it has no branch
	var $copyTree = 0;				// int. If 0 then branch is NOT copied. If 1 then pages on the 1st level is copied. If 2 then pages on the second level is copied ... and so on
	var $versionizeTree = 0;		// int. If 0 then branch is NOT versionized. If 1 then pages on the 1st level is versionized. If 2 then pages on the second level is versionized ... and so on
	var $neverHideAtCopy = 0;		// Boolean. If set, then the 'hideAtCopy' flag for tables will be ignored.
	var $reverseOrder=0;			// boolean. If set, the dataarray is reversed in the order, which is a nice thing if you're creating a whole new bunch of records.
	var $copyWhichTables = '*';		// This list of tables decides which tables will be copied. If empty then none will. If '*' then all will (that the user has permission to of course)
	var $stripslashes_values=1;		// If set, incoming values in the data-array have their slashes stripped. ALWAYS SET THIS TO ZERO and supply an unescaped data array instead. This switch may totally disappear in future versions of this class!
	var $storeLogMessages=1;		// If set, the default log-messages will be stored. This should not be necessary if the locallang-file for the log-display is properly configured. So disabling this will just save some database-space as the default messages are not saved.
	var $enableLogging=1;			// If set, actions are logged.

	var $callBackObj;				// Call back object for flex form traversation. Useful when external classes wants to use the iteration functions inside tcemain for traversing a FlexForm structure.

//	var $history=1;					// Bit-array: Bit0: History on/off. DEPENDS on checkSimilar to be set!
	var $checkSimilar=1;			// Boolean: If set, only fields which are different from the database values are saved! In fact, if a whole input array is similar, it's not saved then.
	var $dontProcessTransformations=0;	// Boolean: If set, then transformations are NOT performed on the input.
#	var $disableRTE = 0;			// Boolean: If set, the RTE is expected to have been disabled in the interface which submitted information. Thus transformations related to the RTE is not done.

	var $pMap = Array(		// Permission mapping
		'show' => 1,			// 1st bit
		'edit' => 2,			// 2nd bit
		'delete' => 4,			// 3rd bit
		'new' => 8,				// 4th bit
		'editcontent' => 16		// 5th bit
	);
	var $defaultPermissions = array(		// Can be overridden from $TYPO3_CONF_VARS
		'user' => 'show,edit,delete,new,editcontent',
		'group' => 'show,edit,new,editcontent',
		'everybody' => ''
	);


	var $alternativeFileName=array();		// Use this array to force another name onto a file. Eg. if you set ['/tmp/blablabal'] = 'my_file.txt' and '/tmp/blablabal' is set for a certain file-field, then 'my_file.txt' will be used as the name instead.
	var $data_disableFields=array();		// If entries are set in this array corresponding to fields for update, they are ignored and thus NOT updated. You could set this array from a series of checkboxes with value=0 and hidden fields before the checkbox with 1. Then an empty checkbox will disable the field.
	var $defaultValues=array();				// You can set this array on the form $defaultValues[$table][$field] = $value to override the default values fetched from TCA. You must set this externally.
	var $overrideValues=array();			// You can set this array on the form $overrideValues[$table][$field] = $value to override the incoming data. You must set this externally. You must make sure the fields in this array are also found in the table, because it's not checked. All columns can be set by this array!
	var $suggestedInsertUids=array();		// Use this array to validate suggested uids for tables by setting [table]:[uid]. This is a dangerous option since it will force the inserted record to have a certain UID. The value just have to be true, but if you set it to "DELETE" it will make sure any record with that UID will be deleted first (raw delete). The option is used for import of T3D files when synchronizing between two mirrored servers. As a security measure this feature is available only for Admin Users (for now)

		// *********
		// internal
		// *********
	var $fileFunc;		// May contain an object
	var $last_log_id;
	var $BE_USER;		// The user-object the script uses. If not set from outside, this is set to the current global $BE_USER.
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
	var $recUpdateAccessCache = Array();	// Used by function checkRecordUpdateAccess() to store whether a record is updateable or not.
	var $recInsertAccessCache = Array();
	var $isRecordInWebMount_Cache=array();
	var $isInWebMount_Cache=array();
	var $pageCache = Array();					// Used for caching page records in pageInfo()
	var $copyMappingArray = Array();			// Use by the copy action to track the ids of new pages so subpages are correctly inserted!
	var $copyMappingArray_merged = Array();		// This array is the sum of all copying operations in this class. May be READ from outside, thus partly public.
	var $registerDBList=array();
	var $dbAnalysisStore=array();
	var $removeFilesStore=array();
	var $copiedFileMap=array();

	var $checkValue_currentRecord=array();		// Set to "currentRecord" during checking of values.


	/**
	 * Initializing.
	 * For details, see 'TYPO3 Core API' document.
	 * This function does not start the processing of data, but merely initializes the object
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
			foreach($userTS as $k => $v)	{
				$k = substr($k,0,-1);
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
	 * @param	array		$_FILES array
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
	 * @param	array		Input array  ($_FILES parts)
	 * @param	string		The current $_FILES array key to set on the outermost level.
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
	 * @return	void
	 */
	function process_datamap() {
		global $TCA, $TYPO3_CONF_VARS;

			// First prepare user defined objects (if any) for hooks which extend this function:
		$hookObjectsArr = array();
		if (is_array ($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'])) {
			foreach ($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'] as $classRef) {
				$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}

			// Organize tables so that the pages-table are always processed first. This is required if you want to make sure that content pointing to a new page will be created.
		$orderOfTables = Array();
		if (isset($this->datamap['pages']))	{		// Set pages first.
			$orderOfTables[]='pages';
		}
		reset($this->datamap);
		while (list($table,) = each($this->datamap))	{
			if ($table!='pages')	{
				$orderOfTables[]=$table;
			}
		}

			// Process the tables...
		foreach($orderOfTables as $table)	{
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
					$this->datamap[$table] = array_reverse($this->datamap[$table], 1);
				}

					// For each record from the table, do:
					// $id is the record uid, may be a string if new records...
					// $incomingFieldArray is the array of fields
				foreach($this->datamap[$table] as $id => $incomingFieldArray)	{
					if (is_array($incomingFieldArray))	{

							// Hook: processDatamap_preProcessIncomingFieldArray
						foreach($hookObjectsArr as $hookObj)	{
							if (method_exists($hookObj, 'processDatamap_preProcessFieldArray')) {
								$hookObj->processDatamap_preProcessFieldArray($incomingFieldArray, $table, $id, $this);
							}
						}

							// ******************************
							// Checking access to the record
							// ******************************
						$recordAccess = 0;
						$old_pid_value = '';
						if (!t3lib_div::testInt($id)) {               // Is it a new record? (Then Id is a string)
							$fieldArray = $this->newFieldArray($table);	// Get a fieldArray with default values
							if (isset($incomingFieldArray['pid']))	{	// A pid must be set for new records.
									// $value = the pid
		 						$pid_value = $incomingFieldArray['pid'];

									// Checking and finding numerical pid, it may be a string-reference to another value
								$OK = 1;
								if (strstr($pid_value,'NEW'))	{	// If a NEW... id
									if (substr($pid_value,0,1)=='-') {$negFlag=-1;$pid_value=substr($pid_value,1);} else {$negFlag=1;}
									if (isset($this->substNEWwithIDs[$pid_value]))	{	// Trying to find the correct numerical value as it should be mapped by earlier processing of another new record.
										$old_pid_value = $pid_value;
										$pid_value=intval($negFlag*$this->substNEWwithIDs[$pid_value]);
									} else {$OK = 0;}	// If not found in the substArray we must stop the proces...
								}
								$pid_value = intval($pid_value);

									// The $pid_value is now the numerical pid at this point
								if ($OK)	{
									$sortRow = $TCA[$table]['ctrl']['sortby'];
									if ($pid_value>=0)	{	// Points to a page on which to insert the element, possibly in the top of the page
										if ($sortRow)	{	// If this table is sorted we better find the top sorting number
											$fieldArray[$sortRow] = $this->getSortNumber($table,0,$pid_value);
										}
										$fieldArray['pid'] = $pid_value;	// The numerical pid is inserted in the data array
									} else {	// points to another record before ifself
										if ($sortRow)	{	// If this table is sorted we better find the top sorting number
											$tempArray=$this->getSortNumber($table,0,$pid_value);	// Because $pid_value is < 0, getSortNumber returns an array
											$fieldArray['pid'] = $tempArray['pid'];
											$fieldArray[$sortRow] = $tempArray['sortNumber'];
										} else {	// Here we fetch the PID of the record that we point to...
											$tempdata = $this->recordInfo($table,abs($pid_value),'pid');
											$fieldArray['pid']=$tempdata['pid'];
										}
									}
								}
							}
							$theRealPid = $fieldArray['pid'];
								// Now, check if we may insert records on this pid.
							if ($theRealPid>=0)	{
								$recordAccess = $this->checkRecordInsertAccess($table,$theRealPid);	// Checks if records can be inserted on this $pid.
							} else {
								debug('Internal ERROR: pid should not be less than zero!');
							}
							$status = 'new';						// Yes new record, change $record_status to 'insert'
						} else {	// Nope... $id is a number
							$fieldArray = Array();
							$recordAccess = $this->checkRecordUpdateAccess($table,$id);
							if (!$recordAccess)		{
								$propArr = $this->getRecordProperties($table,$id);
								$this->log($table,$id,2,0,1,"Attempt to modify record '%s' (%s) without permission. Or non-existing page.",2,array($propArr['header'],$table.':'.$id),$propArr['event_pid']);
							} else {	// Next check of the record permissions (internals)
								$recordAccess = $this->BE_USER->recordEditAccessInternals($table,$id);
								if (!$recordAccess)		{
									$propArr = $this->getRecordProperties($table,$id);
									$this->log($table,$id,2,0,1,"recordEditAccessInternals() check failed. [".$this->BE_USER->errorMsg."]",2,array($propArr['header'],$table.':'.$id),$propArr['event_pid']);
								} else {	// Here we fetch the PID of the record that we point to...
									$tempdata = $this->recordInfo($table,$id,'pid');
									$theRealPid = $tempdata['pid'];
								}
							}
							$status = 'update';	// the default is 'update'
						}

							// **************************************
							// If access was granted above, proceed:
							// **************************************
						if ($recordAccess)	{

							list($tscPID) = t3lib_BEfunc::getTSCpid($table,$id,$old_pid_value ? $old_pid_value : $fieldArray['pid']);	// Here the "pid" is sent IF NOT the old pid was a string pointing to a place in the subst-id array.
							$TSConfig = $this->getTCEMAIN_TSconfig($tscPID);
							if ($status=='new' && $table=='pages' && is_array($TSConfig['permissions.']))	{
								$fieldArray = $this->setTSconfigPermissions($fieldArray,$TSConfig['permissions.']);
							}

							$fieldArray = $this->fillInFieldArray($table,$id,$fieldArray,$incomingFieldArray,$theRealPid,$status,$tscPID);

								// NOTICE! All manipulation beyond this point bypasses both "excludeFields" AND possible "MM" relations / file uploads to field!

							$fieldArray = $this->overrideFieldArray($table,$fieldArray);	// NOTICE: This overriding is potentially dangerous; permissions per field is not checked!!!

								// Setting system fields
							if ($status=='new')	{
								if ($TCA[$table]['ctrl']['crdate'])	{
									$fieldArray[$TCA[$table]['ctrl']['crdate']]=time();
								}
								if ($TCA[$table]['ctrl']['cruser_id'])	{
									$fieldArray[$TCA[$table]['ctrl']['cruser_id']]=$this->userid;
								}
							} elseif ($this->checkSimilar) {	// Removing fields which are equal to the current value:
								$fieldArray = $this->compareFieldArrayWithCurrentAndUnset($table,$id,$fieldArray);
							}
							if ($TCA[$table]['ctrl']['tstamp'])	{
								$fieldArray[$TCA[$table]['ctrl']['tstamp']]=time();
							}

								// Hook: processDatamap_postProcessFieldArray
							foreach($hookObjectsArr as $hookObj)	{
								if (method_exists($hookObj, 'processDatamap_postProcessFieldArray')) {
									$hookObj->processDatamap_postProcessFieldArray($status, $table, $id, $fieldArray, $this);
								}
							}

								// Performing insert/update. If fieldArray has been unset by some userfunction (see hook above), don't do anything
								// Kasper: Unsetting the fieldArray is dangerous; MM relations might be saved already and files could have been uploaded that are now "lost"
							if (is_array($fieldArray)) {
								if ($status=='new')	{
	//								if ($pid_value<0)	{$fieldArray = $this->fixCopyAfterDuplFields($table,$id,abs($pid_value),0,$fieldArray);}	// Out-commented 02-05-02: I couldn't understand WHY this is needed for NEW records. Obviously to proces records being copied? Problem is that the fields are not set anyways and the copying function should basically take care of this!
									$this->insertDB($table,$id,$fieldArray,FALSE,$incomingFieldArray['uid']);
								} else {
									$this->updateDB($table,$id,$fieldArray);
								}
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
	 * $this->exclude_array is used to filter fields if needed.
	 *
	 * @param	string		Table name
	 * @param	[type]		$id: ...
	 * @param	array		Default values, Preset $fieldArray with 'pid' maybe (pid and uid will be not be overridden anyway)
	 * @param	array		$incomingFieldArray is which fields/values you want to set. There are processed and put into $fieldArray if OK
	 * @param	integer		The real PID value of the record. For updates, this is just the pid of the record. For new records this is the PID of the page where it is inserted.
	 * @param	string		$status = 'new' or 'update'
	 * @param	[type]		$tscPID: ...
	 * @return	[type]		...
	 */
	function fillInFieldArray($table,$id,$fieldArray,$incomingFieldArray,$realPid,$status,$tscPID)	{
		global $TCA;

			// Initialize:
		t3lib_div::loadTCA($table);
		unset($originalLanguageRecord);
		unset($originalLanguage_diffStorage);
		$diffStorageFlag = FALSE;

			// Setting 'currentRecord' and 'checkValueRecord':
		if (strstr($id,'NEW'))	{
			$currentRecord = $checkValueRecord = $fieldArray;	// must have the 'current' array - not the values after processing below...

				// IF $incomingFieldArray is an array, overlay it.
				// The point is that when new records are created as copies with flex type fields there might be a field containing information about which DataStructure to use and without that information the flexforms cannot be correctly processed.... This should be OK since the $checkValueRecord is used by the flexform evaluation only anyways...
			if (is_array($incomingFieldArray) && is_array($checkValueRecord))	{
				$checkValueRecord = t3lib_div::array_merge_recursive_overrule($checkValueRecord, $incomingFieldArray);
			}
		} else {
			$currentRecord = $checkValueRecord = $this->recordInfo($table,$id,'*');	// We must use the current values as basis for this!

				// Get original language record if available:
			if (is_array($currentRecord)
					&& $TCA[$table]['ctrl']['transOrigDiffSourceField']
					&& $TCA[$table]['ctrl']['languageField']
					&& $currentRecord[$TCA[$table]['ctrl']['languageField']] > 0
					&& $TCA[$table]['ctrl']['transOrigPointerField']
					&& intval($currentRecord[$TCA[$table]['ctrl']['transOrigPointerField']]) > 0)	{

				$lookUpTable = $TCA[$table]['ctrl']['transOrigPointerTable'] ? $TCA[$table]['ctrl']['transOrigPointerTable'] : $table;
				$originalLanguageRecord = $this->recordInfo($lookUpTable,$currentRecord[$TCA[$table]['ctrl']['transOrigPointerField']],'*');
				$originalLanguage_diffStorage = unserialize($currentRecord[$TCA[$table]['ctrl']['transOrigDiffSourceField']]);
			}
		}
		$this->checkValue_currentRecord = $checkValueRecord;

			/*
				In the following all incoming value-fields are tested:
				- Are the user allowed to change the field?
				- Is the field uid/pid (which are already set)
				- perms-fields for pages-table, then do special things...
				- If the field is nothing of the above and the field is configured in TCA, the fieldvalues are evaluated by ->checkValue

				If everything is OK, the field is entered into $fieldArray[]
			*/
		foreach($incomingFieldArray as $field => $fieldValue)	{
			if (!in_array($table.'-'.$field, $this->exclude_array) && !$this->data_disableFields[$table][$id][$field])	{	// The field must be editable.

					// Checking language:
				$languageDeny = $TCA[$table]['ctrl']['languageField'] && !strcmp($TCA[$table]['ctrl']['languageField'], $field) && !$this->BE_USER->checkLanguageAccess($fieldValue);

				if (!$languageDeny)	{
						// Stripping slashes - will probably be removed the day $this->stripslashes_values is removed as an option...
					if ($this->stripslashes_values)	{
						if (is_array($fieldValue))	{
							t3lib_div::stripSlashesOnArray($fieldValue);
						} else $fieldValue = stripslashes($fieldValue);
					}

					switch ($field)	{
						case 'uid':
						case 'pid':
							// Nothing happens, already set
						break;
						case 'perms_userid':
						case 'perms_groupid':
						case 'perms_user':
						case 'perms_group':
						case 'perms_everybody':
								// Permissions can be edited by the owner or the administrator
							if ($table=='pages' && ($this->admin || $status=='new' || $this->pageInfo($id,'perms_userid')==$this->userid) )	{
								$value=intval($fieldValue);
								switch($field)	{
									case 'perms_userid':
										$fieldArray[$field]=$value;
									break;
									case 'perms_groupid':
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
						case 't3ver_oid':
						case 't3ver_id':
							// t3ver_label is not here because it CAN be edited as a regular field!
						break;
						default:
							if (isset($TCA[$table]['columns'][$field]))	{
									// Evaluating the value.
								$res = $this->checkValue($table,$field,$fieldValue,$id,$status,$realPid,$tscPID);
								if (isset($res['value']))	{
									$fieldArray[$field]=$res['value'];

										// Add the value of the original record to the diff-storage content:
									if ($TCA[$table]['ctrl']['transOrigDiffSourceField'])	{
										$originalLanguage_diffStorage[$field] = $originalLanguageRecord[$field];
										$diffStorageFlag = TRUE;
									}
								}
							}


						break;
					}
				}	// Checking language.
			}	// Check exclude fields / disabled fields...
		}

			// Add diff-storage information:
		if ($diffStorageFlag && !isset($fieldArray[$TCA[$table]['ctrl']['transOrigDiffSourceField']]))	{	// If the field is set it would probably be because of an undo-operation - in which case we should not update the field of course...
			 $fieldArray[$TCA[$table]['ctrl']['transOrigDiffSourceField']] = serialize($originalLanguage_diffStorage);
		}

			// Checking for RTE-transformations of fields:
		$types_fieldConfig = t3lib_BEfunc::getTCAtypes($table,$currentRecord);
		$theTypeString = t3lib_BEfunc::getTCAtypeValue($table,$currentRecord);
		if (is_array($types_fieldConfig))	{
			reset($types_fieldConfig);
			while(list(,$vconf) = each($types_fieldConfig))	{
					// Write file configuration:
				$eFile = t3lib_parsehtml_proc::evalWriteFile($vconf['spec']['static_write'],array_merge($currentRecord,$fieldArray));	// inserted array_merge($currentRecord,$fieldArray) 170502

					// RTE transformations:
				if (!$this->dontProcessTransformations)	{
					if (isset($fieldArray[$vconf['field']]))	{
							// Look for transformation flag:
						switch((string)$incomingFieldArray['_TRANSFORM_'.$vconf['field']])	{
							case 'RTE':
								$RTEsetup = $this->BE_USER->getTSConfig('RTE',t3lib_BEfunc::getPagesTSconfig($tscPID));
								$thisConfig = t3lib_BEfunc::RTEsetup($RTEsetup['properties'],$table,$vconf['field'],$theTypeString);

									// Set alternative relative path for RTE images/links:
								$RTErelPath = is_array($eFile) ? dirname($eFile['relEditFile']) : '';

									// Get RTE object, draw form and set flag:
								$RTEobj = &t3lib_BEfunc::RTEgetObj();
								if (is_object($RTEobj))	{
									$fieldArray[$vconf['field']] = $RTEobj->transformContent('db',$fieldArray[$vconf['field']],$table,$vconf['field'],$currentRecord,$vconf['spec'],$thisConfig,$RTErelPath,$currentRecord['pid']);
								} else {
									debug('NO RTE OBJECT FOUND!');
								}
							break;
						}
					}
				}

					// Write file configuration:
				if (is_array($eFile))	{
					$mixedRec = array_merge($currentRecord,$fieldArray);
					$SW_fileContent = t3lib_div::getUrl($eFile['editFile']);
					$parseHTML = t3lib_div::makeInstance('t3lib_parsehtml_proc');
					$parseHTML->init('','');

					$eFileMarker = $eFile['markerField']&&trim($mixedRec[$eFile['markerField']]) ? trim($mixedRec[$eFile['markerField']]) : '###TYPO3_STATICFILE_EDIT###';
					$insertContent = str_replace($eFileMarker,'',$mixedRec[$eFile['contentField']]);	// must replace the marker if present in content!

					$SW_fileNewContent = $parseHTML->substituteSubpart($SW_fileContent, $eFileMarker, chr(10).$insertContent.chr(10), 1, 1);
					t3lib_div::writeFile($eFile['editFile'],$SW_fileNewContent);

						// Write status:
					if (!strstr($id,'NEW') && $eFile['statusField'])	{
						$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
							$table,
							'uid='.intval($id),
							array(
								$eFile['statusField'] => $eFile['relEditFile'].' updated '.date('d-m-Y H:i:s').', bytes '.strlen($mixedRec[$eFile['contentField']])
							)
						);
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
		$res = ($this->admin || (!$this->tableAdminOnly($table) && t3lib_div::inList($this->BE_USER->groupData['tables_modify'],$table)));
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
		if (!isset($this->isRecordInWebMount_Cache[$table.':'.$id]))	{
			$recP=$this->getRecordProperties($table,$id);
			$this->isRecordInWebMount_Cache[$table.':'.$id]=$this->isInWebMount($recP['event_pid']);
		}
		return $this->isRecordInWebMount_Cache[$table.':'.$id];
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
				// Check if record exists and 1) if 'pages' the page may be edited, 2) if page-content the page allows for editing
			} elseif ($this->doesRecordExist($table,$id,'edit'))	{
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
					// If either admin and root-level or if page record exists and 1) if 'pages' you may create new ones 2) if page-content, new content items may be inserted on the $pid page
				if ( (!$pid && $this->admin) || $this->doesRecordExist('pages',$pid,($insertTable=='pages'?$this->pMap['new']:$this->pMap['editcontent'])) )	{		// Check permissions
					if ($this->isTableAllowedForThisPage($pid, $insertTable))	{
						$res = 1;
						$this->recInsertAccessCache[$insertTable][$pid]=$res;	// Cache the result
					} else {
						$propArr = $this->getRecordProperties('pages',$pid);
						$this->log($insertTable,$pid,$action,0,1,"Attempt to insert record on page '%s' (%s) where this table, %s, is not allowed",11,array($propArr['header'],$pid,$insertTable),$propArr['event_pid']);
					}
				} else {
					$propArr = $this->getRecordProperties('pages',$pid);
					$this->log($insertTable,$pid,$action,0,1,"Attempt to insert a record on page '%s' (%s) from table '%s' without permissions. Or non-existing page.",12,array($propArr['header'],$pid,$insertTable),$propArr['event_pid']);
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
		if (($TCA[$checkTable]['ctrl']['rootLevel'] xor !$page_uid) && $TCA[$checkTable]['ctrl']['rootLevel']!=-1 && $checkTable!='pages')	{
			return false;
		}

			// Check root-level
		if (!$page_uid)	{
			if ($this->admin)	{
				return true;
			}
		} else {
				// Check non-root-level
			$doktype = $this->pageInfo($page_uid,'doktype');
			$allowedTableList = isset($PAGES_TYPES[$doktype]['allowedTables']) ? $PAGES_TYPES[$doktype]['allowedTables'] : $PAGES_TYPES['default']['allowedTables'];
			$allowedArray = t3lib_div::trimExplode(',',$allowedTableList,1);
			if (strstr($allowedTableList,'*') || in_array($checkTable,$allowedArray))	{		// If all tables or the table is listed as a allowed type, return true
				return true;
			}
		}
	}

	/**
	 * Checks if record exists
	 *
	 * Returns true if the record given by $table, $id and $perms
	 *
	 * @param	string		Record table name
	 * @param	integer		Record UID
	 * @param	mixed		Permission restrictions to observe: Either an integer that will be bitwise AND'ed or a string, which points to a key in the ->pMap array
	 * @return	[type]		...
	 */
	function doesRecordExist($table,$id,$perms)	{
		global $TCA;

		$res = 0;
		$id = intval($id);

			// Processing the incoming $perms (from possible string to integer that can be AND'ed)
		if (!t3lib_div::testInt($perms))	{
			if ($table!='pages')	{
				switch($perms)	{
					case 'edit':
					case 'delete':
					case 'new':
						$perms = 'editcontent';		// This holds it all in case the record is not page!!
					break;
				}
			}
			$perms = intval($this->pMap[$perms]);
		} else {
			$perms = intval($perms);
		}

		if (!$perms)	{debug('Internal ERROR: no permissions to check for non-admin user.');}

			// For all tables: Check if record exists:
			// Notice: If $perms are 0 (zero) no perms-clause is added!
		if (is_array($TCA[$table]) && $id>0 && ($this->isRecordInWebMount($table,$id) || $this->admin))	{
			if ($table != 'pages')	{

					// Find record without checking page:
				$mres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,pid', $table, 'uid='.intval($id).$this->deleteClause($table));
				$output = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($mres);
				t3lib_BEfunc::fixVersioningPid($table,$output);

					// If record found, check page as well:
				if (is_array($output))	{

						// Looking up the page for record:
					$mres = $this->doesRecordExist_pageLookUp($output['pid'], $perms);
					$pageRec = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($mres);

						// Return true if either a page was found OR if the PID is zero AND the user is ADMIN (in which case the record is at root-level):
					if (is_array($pageRec) || (!$output['pid'] && $this->admin))	{
						return TRUE;
					}
				}
				return FALSE;
			} else {
				$mres = $this->doesRecordExist_pageLookUp($id, $perms);
				return $GLOBALS['TYPO3_DB']->sql_num_rows($mres);
			}
		}
	}

	/**
	 * Looks up a page based on permissions.
	 *
	 * @param	integer		Page id
	 * @param	integer		Permission integer
	 * @return	pointer		MySQL result pointer (from exec_SELECTquery())
	 * @access private
	 * @see doesRecordExist()
	 */
	function doesRecordExist_pageLookUp($id, $perms)	{
		global $TCA;

		return $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid',
			'pages',
			'uid='.intval($id).
				$this->deleteClause('pages').
				($perms && !$this->admin ? ' AND '.$this->BE_USER->getPagePermsClause($perms) : '').
				(!$this->admin && $TCA['pages']['ctrl']['editlock'] && ($perms & (2+4+16)) ? ' AND '.$TCA['pages']['ctrl']['editlock'].'=0':'')	// admin users don't need check
		);
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
			$mres = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'uid, perms_userid, perms_groupid, perms_user, perms_group, perms_everybody',
						'pages',
						'pid='.intval($pid).$this->deleteClause('pages'),
						'',
						'sorting'
					);
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($mres))	{
				if ($this->admin || $this->BE_USER->doesUserHaveAccess($row,$perms))	{	// IF admin, then it's OK
					$inList.=$row['uid'].',';
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
	 * NOTICE; the function caches the result for faster delivery next time. You can use this function repeatedly without performanceloss since it doesn't look up the same record twice!
	 *
	 * @param	integer		Page uid
	 * @param	string		Field name for which to return value
	 * @return	string		Value of the field. Result is cached in $this->pageCache[$id][$field] and returned from there next time!
	 */
	function pageInfo($id,$field)	{
		if (!isset($this->pageCache[$id]))	{
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'pages', 'uid='.intval($id));
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($res))	{
				$this->pageCache[$id] = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}
		return $this->pageCache[$id][$field];
	}

	/**
	 * Returns the row of a record given by $table and $id and $fieldList (list of fields, may be '*')
	 * NOTICE: No check for deleted or access!
	 *
	 * @param	string		Table name
	 * @param	integer		UID of the record from $table
	 * @param	string		Field list for the SELECT query, eg. "*" or "uid,pid,..."
	 * @return	mixed		Returns the selected record on success, otherwise false.
	 */
	function recordInfo($table,$id,$fieldList)	{
		global $TCA;
		if (is_array($TCA[$table]))	{
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fieldList, $table, 'uid='.intval($id));
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($res))	{
				return $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
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
		$row = ($table=='pages' && !$id) ? array('title'=>'[root-level]', 'uid' => 0, 'pid' => 0) :$this->recordInfo($table,$id,'*');
		t3lib_BEfunc::fixVersioningPid($table,$row);
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
				'header' => $row[$TCA[$table]['ctrl']['label']],
				'pid' => $row['pid'],
				'event_pid' => ($table=='pages'?$row['uid']:$row['pid'])
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
		if (strcmp($TSConfig_p['userid'],''))	$fieldArray['perms_userid']=intval($TSConfig_p['userid']);
		if (strcmp($TSConfig_p['groupid'],''))	$fieldArray['perms_groupid']=intval($TSConfig_p['groupid']);
		if (strcmp($TSConfig_p['user'],''))			$fieldArray['perms_user']=t3lib_div::testInt($TSConfig_p['user']) ? $TSConfig_p['user'] : $this->assemblePermissions($TSConfig_p['user']);
		if (strcmp($TSConfig_p['group'],''))		$fieldArray['perms_group']=t3lib_div::testInt($TSConfig_p['group']) ? $TSConfig_p['group'] : $this->assemblePermissions($TSConfig_p['group']);
		if (strcmp($TSConfig_p['everybody'],''))	$fieldArray['perms_everybody']=t3lib_div::testInt($TSConfig_p['everybody']) ? $TSConfig_p['everybody'] : $this->assemblePermissions($TSConfig_p['everybody']);

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
		if (is_array($TCA[$table]['columns']))	{
			reset ($TCA[$table]['columns']);
			while (list($field,$content)=each($TCA[$table]['columns']))	{
				if (isset($this->defaultValues[$table][$field]))	{
					$fieldArray[$field] = $this->defaultValues[$table][$field];
				} elseif (isset($content['config']['default']))	{
					$fieldArray[$field] = $content['config']['default'];
				}
			}
		}
		if ($table=='pages')	{		// Set default permissions for a page.
			$fieldArray['perms_userid'] = $this->userid;
			$fieldArray['perms_groupid'] = intval($this->BE_USER->firstMainGroup);
			$fieldArray['perms_user'] = $this->assemblePermissions($this->defaultPermissions['user']);
			$fieldArray['perms_group'] = $this->assemblePermissions($this->defaultPermissions['group']);
			$fieldArray['perms_everybody'] = $this->assemblePermissions($this->defaultPermissions['everybody']);
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
		$keyArr = t3lib_div::trimExplode(',',$string,1);
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
	 * This function is for real database fields - NOT FlexForm "pseudo" fields.
	 * NOTICE: Calling this function expects this: 1) That the data is saved! (files are copied and so on) 2) That files registered for deletion IS deleted at the end (with ->removeRegisteredFiles() )
	 *
	 * @param	string		Table name
	 * @param	string		Field name
	 * @param	string		Value to be evaluated. Notice, this is the INPUT value from the form. The original value (from any existing record) must be manually looked up inside the function if needed - or taken from $currentRecord array.
	 * @param	string		The record-uid, mainly - but not exclusively - used for logging
	 * @param	string		'update' or 'new' flag
	 * @param	integer		The real PID value of the record. For updates, this is just the pid of the record. For new records this is the PID of the page where it is inserted. If $realPid is -1 it means that a new version of the record is being inserted.
	 * @param	integer		$tscPID
	 * @return	array		Returns the evaluated $value as key "value" in this array. Can be checked with isset($res['value']) ...
	 */
	function checkValue($table,$field,$value,$id,$status,$realPid,$tscPID)	{
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
					// This checks 1) if we should check for disallowed tables and 2) if there are records from disallowed tables on the current page
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
		$res = $this->checkValue_SW($res,$value,$tcaFieldConf,$table,$id,$curValue,$status,$realPid,$recFID,$field,$this->uploadedFileArray[$table][$id][$field],$tscPID);

		return $res;
	}

	/**
	 * Branches out evaluation of a field value based on its type as configured in TCA
	 * Can be called for FlexForm pseudo fields as well, BUT must not have $field set if so.
	 *
	 * @param	array		The result array. The processed value (if any!) is set in the "value" key.
	 * @param	string		The value to set.
	 * @param	array		Field configuration from TCA
	 * @param	string		Table name
	 * @param	integer		Return UID
	 * @param	[type]		$curValue: ...
	 * @param	[type]		$status: ...
	 * @param	integer		The real PID value of the record. For updates, this is just the pid of the record. For new records this is the PID of the page where it is inserted. If $realPid is -1 it means that a new version of the record is being inserted.
	 * @param	[type]		$recFID: ...
	 * @param	string		Field name. Must NOT be set if the call is for a flexform field (since flexforms are not allowed within flexforms).
	 * @param	[type]		$uploadedFiles: ...
	 * @param	[type]		$tscPID: ...
	 * @return	array		Returns the evaluated $value as key "value" in this array.
	 */
	function checkValue_SW($res,$value,$tcaFieldConf,$table,$id,$curValue,$status,$realPid,$recFID,$field,$uploadedFiles,$tscPID)	{

		$PP = array($table,$id,$curValue,$status,$realPid,$recFID,$tscPID);

		switch ($tcaFieldConf['type']) {
			case 'text':
			case 'passthrough':
			case 'user':
				$res['value'] = $value;
			break;
			case 'input':
				$res = $this->checkValue_input($res,$value,$tcaFieldConf,$PP,$field);
			break;
			case 'check':
				$res = $this->checkValue_check($res,$value,$tcaFieldConf,$PP);
			break;
			case 'radio':
				$res = $this->checkValue_radio($res,$value,$tcaFieldConf,$PP);
			break;
			case 'group':
			case 'select':
				$res = $this->checkValue_group_select($res,$value,$tcaFieldConf,$PP,$uploadedFiles,$field);
			break;
			case 'flex':
				if ($field)	{	// FlexForms are only allowed for real fields.
					$res = $this->checkValue_flex($res,$value,$tcaFieldConf,$PP,$uploadedFiles,$field);
				}
			break;
			default:
				#debug(array($tcaFieldConf,$res,$value),'NON existing field type:');
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
	 * @param	string		Field name
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
		if ($field && $realPid>=0)	{	// Field is NOT set for flexForms - which also means that uniqueInPid and unique is NOT available for flexForm fields! Also getUnique should not be done for versioning and if PID is -1 ($realPid<0) then versioning is happening...
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
	 * Evaluates 'check' type values.
	 *
	 * @param	array		The result array. The processed value (if any!) is set in the 'value' key.
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
	 * Evaluates 'radio' type values.
	 *
	 * @param	array		The result array. The processed value (if any!) is set in the 'value' key.
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
	 * Evaluates 'group' or 'select' type values.
	 *
	 * @param	array		The result array. The processed value (if any!) is set in the 'value' key.
	 * @param	string		The value to set.
	 * @param	array		Field configuration from TCA
	 * @param	array		Additional parameters in a numeric array: $table,$id,$curValue,$status,$realPid,$recFID
	 * @param	[type]		$uploadedFiles: ...
	 * @param	string		Field name
	 * @return	array		Modified $res array
	 */
	function checkValue_group_select($res,$value,$tcaFieldConf,$PP,$uploadedFiles,$field)	{
		list($table,$id,$curValue,$status,$realPid,$recFID) = $PP;

			// Detecting if value send is an array and if so, implode it around a comma:
		if (is_array($value))	{
			$value = implode(',',$value);
		}

			// This converts all occurencies of '&#123;' to the byte 123 in the string - this is needed in very rare cases where filenames with special characters (like Ê¯Â, umlaud etc) gets sent to the server as HTML entities instead of bytes. The error is done only by MSIE, not Mozilla and Opera.
			// Anyways, this should NOT disturb anything else:
		$value = $this->convNumEntityToByteValue($value);

			// When values are send as group or select they come as comma-separated values which are exploded by this function:
		$valueArray = $this->checkValue_group_select_explodeSelectGroupValue($value);

			// If not multiple is set, then remove duplicates:
		if (!$tcaFieldConf['multiple'])	{
			$valueArray = array_unique($valueArray);
		}

		// This could be a good spot for parsing the array through a validation-function which checks if the values are allright (except that database references are not in their final form - but that is the point, isn't it?)
		// NOTE!!! Must check max-items of files before the later check because that check would just leave out filenames if there are too many!!

			// Checking for select / authMode, removing elements from $valueArray if any of them is not allowed!
		if ($tcaFieldConf['type']=='select' && $tcaFieldConf['authMode'])	{
			$preCount = count($valueArray);
			foreach($valueArray as $kk => $vv)	{
				if (!$this->BE_USER->checkAuthMode($table,$field,$vv,$tcaFieldConf['authMode']))	{
					unset($valueArray[$kk]);
				}
			}

				// During the check it turns out that the value / all values were removed - we respond by simply returning an empty array so nothing is written to DB for this field.
			if ($preCount && !count($valueArray))	{
				return array();
			}
		}

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

// BTW, checking for min and max items here does NOT make any sense when MM is used because the above function calls will just return an array with a single item (the count) if MM is used... Why didn't I perform the check before? Probably because we could not evaluate the validity of record uids etc... Hmm...

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
		$res['value'] = implode(',',$newVal);

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
									$this->copiedFileMap[$theFile] = $theDestFile;
									clearstatcache();
									if (!@is_file($theDestFile))	$this->log($table,$id,5,0,1,"Copying file '%s' failed!: The destination path (%s) may be write protected. Please make it write enabled!. (%s)",16,array($theFile, dirname($theDestFile), $recFID),$propArr['event_pid']);
								} else $this->log($table,$id,5,0,1,"Copying file '%s' failed!: No destination file (%s) possible!. (%s)",11,array($theFile, $theDestFile, $recFID),$propArr['event_pid']);
							} else $this->log($table,$id,5,0,1,"Fileextension '%s' not allowed. (%s)",12,array($fI['fileext'], $recFID),$propArr['event_pid']);
						} else $this->log($table,$id,5,0,1,"Filesize (%s) of file '%s' exceeds limit (%s). (%s)",13,array(t3lib_div::formatSize($fileSize),$theFile,t3lib_div::formatSize($maxSize*1024),$recFID),$propArr['event_pid']);
					} else $this->log($table,$id,5,0,1,'The destination (%s) or the source file (%s) does not exist. (%s)',14,array($dest, $theFile, $recFID),$propArr['event_pid']);

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
	 * Evaluates 'flex' type values.
	 *
	 * @param	array		The result array. The processed value (if any!) is set in the 'value' key.
	 * @param	string		The value to set.
	 * @param	array		Field configuration from TCA
	 * @param	array		Additional parameters in a numeric array: $table,$id,$curValue,$status,$realPid,$recFID
	 * @param	array		Uploaded files for the field
	 * @param	array		Current record array.
	 * @param	string		Field name
	 * @return	array		Modified $res array
	 */
	function checkValue_flex($res,$value,$tcaFieldConf,$PP,$uploadedFiles,$field)	{
		list($table,$id,$curValue,$status,$realPid,$recFID) = $PP;

		if (is_array($value))	{

				// Get current value array:
			$dataStructArray = t3lib_BEfunc::getFlexFormDS($tcaFieldConf,$this->checkValue_currentRecord,$table);
#debug($this->checkValue_currentRecord);
			$currentValueArray = t3lib_div::xml2array($curValue);
			if (!is_array($currentValueArray))	$currentValueArray = array();
			if (is_array($currentValueArray['meta']['currentLangId']))		unset($currentValueArray['meta']['currentLangId']);	// Remove all old meta for languages...

				// Evaluation of input values:
			$value['data'] = $this->checkValue_flex_procInData($value['data'],$currentValueArray['data'],$uploadedFiles['data'],$dataStructArray,$PP);

				// Create XML and convert charsets from input value:
			$xmlValue = $this->checkValue_flexArray2Xml($value);

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
				$xmlValue = $this->checkValue_flexArray2Xml($arrValue);
			}

				// Temporary fix to delete flex form elements:
			$deleteCMDs = t3lib_div::_GP('_DELETE_FLEX_FORMdata');
			if (is_array($deleteCMDs[$table][$id][$field]['data']))	{
				$arrValue = t3lib_div::xml2array($xmlValue);
				$this->_DELETE_FLEX_FORMdata($arrValue['data'],$deleteCMDs[$table][$id][$field]['data']);
				$xmlValue = $this->checkValue_flexArray2Xml($arrValue);
			}

				// Temporary fix to move flex form elements up:
			$moveCMDs = t3lib_div::_GP('_MOVEUP_FLEX_FORMdata');
			if (is_array($moveCMDs[$table][$id][$field]['data']))	{
				$arrValue = t3lib_div::xml2array($xmlValue);
				$this->_MOVE_FLEX_FORMdata($arrValue['data'],$moveCMDs[$table][$id][$field]['data'], 'up');
				$xmlValue = $this->checkValue_flexArray2Xml($arrValue);
			}

				// Temporary fix to move flex form elements down:
			$moveCMDs = t3lib_div::_GP('_MOVEDOWN_FLEX_FORMdata');
			if (is_array($moveCMDs[$table][$id][$field]['data']))	{
				$arrValue = t3lib_div::xml2array($xmlValue);
				$this->_MOVE_FLEX_FORMdata($arrValue['data'],$moveCMDs[$table][$id][$field]['data'], 'down');
				$xmlValue = $this->checkValue_flexArray2Xml($arrValue);
			}

				// Create the value XML:
			$res['value']='';
			$res['value'].='<?xml version="1.0" encoding="'.$storeInCharset.'" standalone="yes" ?>'.chr(10);
			$res['value'].=$xmlValue;
		} else {	// Passthrough...:
			$res['value']=$value;
		}

		return $res;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$array: ...
	 * @return	[type]		...
	 */
	function checkValue_flexArray2Xml($array)	{
		$output = t3lib_div::array2xml($array,'',0,'T3FlexForms',4,array('parentTagMap' => array(
/*								'data' => 'sheets',
								'sheets' => 'language',
								'language' => 'fieldname',
								'el' => 'fieldname'		*/
							)));
		return $output;
	}

	/**
	 * Deletes a flex form element
	 *
	 * @param	array		&$valueArrayToRemoveFrom: by reference
	 * @param	[type]		$deleteCMDS: ...	 * 
	 * @return	void
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

	/**
	 * Deletes a flex form element
	 *
	 * @param	array		&$valueArrayToMoveIn: by reference
	 * @param	[type]		$moveCMDS: ...	 *
	 * @param	string		$direction: 'up' or 'down' 
	 * @return	void
	 * TODO: Like _DELETE_FLEX_FORMdata, this is only a temporary solution!
	 */
	function _MOVE_FLEX_FORMdata(&$valueArrayToMoveIn, $moveCMDS, $direction)	{
		if (is_array($valueArrayToMoveIn) && is_array($moveCMDS))	{

				// Only execute the first move command:
			list ($key, $value) = each ($moveCMDS);
			
			if (is_array($moveCMDS[$key]))	{
				$this->_MOVE_FLEX_FORMdata($valueArrayToMoveIn[$key],$moveCMDS[$key], $direction);
			} else {
				switch ($direction) {
					case 'up':
						if ($key > 1) {
							$tmpArr = $valueArrayToMoveIn[$key];
							$valueArrayToMoveIn[$key] = $valueArrayToMoveIn[$key-1];
							$valueArrayToMoveIn[$key-1] = $tmpArr; 
						}
					break;
					case 'down':
						if ($key < count($valueArrayToMoveIn)) {
							$tmpArr = $valueArrayToMoveIn[$key];
							$valueArrayToMoveIn[$key] = $valueArrayToMoveIn[$key+1];
							$valueArrayToMoveIn[$key+1] = $tmpArr; 
						}
					break;				
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
	 * @param	integer		UID to filter out in the lookup (the record itself...)
	 * @param	integer		If set, the value will be unique for this PID
	 * @return	string		Modified value (if not-unique). Will be the value appended with a number (until 100, then the function just breaks).
	 */
	function getUnique($table,$field,$value,$id,$newPid=0)	{
		global $TCA;

			// Initialize:
		t3lib_div::loadTCA($table);
		$whereAdd='';
		$newValue='';
		if (intval($newPid))	{ $whereAdd.=' AND pid='.intval($newPid); } else { $whereAdd.=' AND pid>=0'; }	// "AND pid>=0" for versioning
		$whereAdd.=$this->deleteClause($table);

			// If the field is configured in TCA, proceed:
		if (is_array($TCA[$table]) && is_array($TCA[$table]['columns'][$field]))	{

				// Look for a record which might already have the value:
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', $table, $field.'='.$GLOBALS['TYPO3_DB']->fullQuoteStr($value, $table).' AND uid!='.intval($id).$whereAdd);
			$counter = 0;

				// For as long as records with the test-value existing, try again (with incremented numbers appended).
			while ($GLOBALS['TYPO3_DB']->sql_num_rows($res))	{
				$newValue = $value.$counter;
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', $table, $field.'='.$GLOBALS['TYPO3_DB']->fullQuoteStr($newValue, $table).' AND uid!='.intval($id).$whereAdd);
				$counter++;
				if ($counter>100)	{ break; }	// At "100" it will give up and accept a duplicate - should probably be fixed to a small hash string instead...!
			}
				// If the new value is there:
			$value = strlen($newValue) ? $newValue : $value;
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
	 * @param	string		Status string ('update' or 'new')
	 * @param	string		The type, either 'select' or 'group'
	 * @return	array		Modified value array
	 */
	function checkValue_group_select_processDBdata($valueArray,$tcaFieldConf,$id,$status,$type)	{
		$tables = $type=='group'?$tcaFieldConf['allowed']:$tcaFieldConf['foreign_table'].','.$tcaFieldConf['neg_foreign_table'];
		$prep = $type=='group'?$tcaFieldConf['prepend_tname']:$tcaFieldConf['neg_foreign_table'];

		$dbAnalysis = t3lib_div::makeInstance('t3lib_loadDBGroup');
		$dbAnalysis->registerNonTableValues=$tcaFieldConf['allowNonIdValues'] ? 1 : 0;
		$dbAnalysis->start(implode(',',$valueArray),$tables);

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
	 * @param	string		Input string, comma separated values. For each part it will also be detected if a '|' is found and the first part will then be used if that is the case. Further the value will be rawurldecoded.
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
	 * Starts the processing the input data for flexforms. This will traverse all sheets / languages and for each it will traverse the sub-structure.
	 * See checkValue_flex_procInData_travDS() for more details.
	 *
	 * @param	array		The 'data' part of the INPUT flexform data
	 * @param	array		The 'data' part of the CURRENT flexform data
	 * @param	array		The uploaded files for the 'data' part of the INPUT flexform data
	 * @param	array		Data structure for the form (might be sheets or not). Only values in the data array which has a configuration in the data structure will be processed.
	 * @param	array		A set of parameters to pass through for the calling of the evaluation functions
	 * @param	string		Optional call back function, see checkValue_flex_procInData_travDS()
	 * @return	array		The modified 'data' part.
	 * @see checkValue_flex_procInData_travDS()
	 */
	function checkValue_flex_procInData($dataPart,$dataPart_current,$uploadedFiles,$dataStructArray,$pParams,$callBackFunc='')	{
#debug(array($dataPart,$dataPart_current,$dataStructArray));
		if (is_array($dataPart))	{
			foreach($dataPart as $sKey => $sheetDef)	{
				list ($dataStruct,$actualSheet) = t3lib_div::resolveSheetDefInDS($dataStructArray,$sKey);
#debug(array($dataStruct,$actualSheet,$sheetDef,$actualSheet,$sKey));
				if (is_array($dataStruct) && $actualSheet==$sKey && is_array($sheetDef))	{
					foreach($sheetDef as $lKey => $lData)	{
						$this->checkValue_flex_procInData_travDS(
							$dataPart[$sKey][$lKey],
							$dataPart_current[$sKey][$lKey],
							$uploadedFiles[$sKey][$lKey],
							$dataStruct['ROOT']['el'],
							$pParams,
							$callBackFunc,
							$sKey.'/'.$lKey.'/'
						);
					}
				}
			}
		}

		return $dataPart;
	}

	/**
	 * Processing of the sheet/language data array
	 * When it finds a field with a value the processing is done by ->checkValue_SW() by default but if a call back function name is given that method in this class will be called for the processing instead.
	 *
	 * @param	array		New values (those being processed): Multidimensional Data array for sheet/language, passed by reference!
	 * @param	array		Current values: Multidimensional Data array. May be empty array() if not needed (for callBackFunctions)
	 * @param	array		Uploaded files array for sheet/language. May be empty array() if not needed (for callBackFunctions)
	 * @param	array		Data structure which fits the data array
	 * @param	array		A set of parameters to pass through for the calling of the evaluation functions / call back function
	 * @param	string		Call back function, default is checkValue_SW(). If $this->callBackObj is set to an object, the callback function in that object is called instead.
	 * @param	[type]		$structurePath: ...
	 * @return	void
	 * @see checkValue_flex_procInData()
	 */
	function checkValue_flex_procInData_travDS(&$dataValues,$dataValues_current,$uploadedFiles,$DSelements,$pParams,$callBackFunc,$structurePath)	{
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
											$pParams,
											$callBackFunc,
											$structurePath.$key.'/el/'.$ik.'/'.$theKey.'/el/'
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
									$pParams,
									$callBackFunc,
									$structurePath.$key.'/el/'
								);
						}
					}
				} else {
					if (is_array($dsConf['TCEforms']['config']) && is_array($dataValues[$key]))	{
						foreach($dataValues[$key] as $vKey => $data)	{

							if ($callBackFunc)	{
								if (is_object($this->callBackObj))	{
									$res = $this->callBackObj->$callBackFunc(
												$pParams,
												$dsConf['TCEforms']['config'],
												$dataValues[$key][$vKey],
												$dataValues_current[$key][$vKey],
												$uploadedFiles[$key][$vKey],
												$structurePath.$key.'/'.$vKey.'/'
											);
								} else {
									$res = $this->$callBackFunc(
												$pParams,
												$dsConf['TCEforms']['config'],
												$dataValues[$key][$vKey],
												$dataValues_current[$key][$vKey],
												$uploadedFiles[$key][$vKey]
											);
								}
							} else {	// Default
								list($CVtable,$CVid,$CVcurValue,$CVstatus,$CVrealPid,$CVrecFID,$CVtscPID) = $pParams;

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
											$uploadedFiles[$key][$vKey],
											array(),
											$CVtscPID
										);

									// Look for RTE transformation of field:
								if ($dataValues[$key]['_TRANSFORM_'.$vKey] == 'RTE' && !$this->dontProcessTransformations)	{

										// Unsetting trigger field - we absolutely don't want that into the data storage!
									unset($dataValues[$key]['_TRANSFORM_'.$vKey]);

									if (isset($res['value']))	{

											// Calculating/Retrieving some values here:
										list(,,$recFieldName) = explode(':', $CVrecFID);
										$theTypeString = t3lib_BEfunc::getTCAtypeValue($CVtable,$this->checkValue_currentRecord);
										$specConf = t3lib_BEfunc::getSpecConfParts('',$dsConf['TCEforms']['defaultExtras']);

											// Find, thisConfig:
										$RTEsetup = $this->BE_USER->getTSConfig('RTE',t3lib_BEfunc::getPagesTSconfig($CVtscPID));
										$thisConfig = t3lib_BEfunc::RTEsetup($RTEsetup['properties'],$CVtable,$recFieldName,$theTypeString);

											// Get RTE object, draw form and set flag:
										$RTEobj = &t3lib_BEfunc::RTEgetObj();
										if (is_object($RTEobj))	{
											$res['value'] = $RTEobj->transformContent('db',$res['value'],$CVtable,$recFieldName,$this->checkValue_currentRecord,$specConf,$thisConfig,'',$CVrealPid);
										} else {
											debug('NO RTE OBJECT FOUND!');
										}
									}
								}
							}

								// Adding the value:
							if (isset($res['value']))	{
								$dataValues[$key][$vKey] = $res['value'];
							}
						}
					}
				}
			}
		}
	}




















	/*********************************************
	 *
	 * Storing data to Database Layer
	 *
	 ********************************************/


	/**
	 * Update database record
	 * Does not check permissions but expects them to be verified on beforehand
	 *
	 * @param	string		Record table name
	 * @param	integer		Record uid
	 * @param	array		Array of field=>value pairs to insert. FIELDS MUST MATCH the database FIELDS. No check is done.
	 * @return	void
	 */
	function updateDB($table,$id,$fieldArray)	{
		global $TCA;

		if (is_array($fieldArray) && is_array($TCA[$table]) && intval($id))	{
			unset($fieldArray['uid']);	// Do NOT update the UID field, ever!

			if (count($fieldArray))	{

					// Execute the UPDATE query:
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid='.intval($id), $fieldArray);

					// If succees, do...:
				if (!$GLOBALS['TYPO3_DB']->sql_error())	{
					if ($this->checkStoredRecords)	{
						$newRow = $this->checkStoredRecord($table,$id,$fieldArray,2);
					}

						// Set log entry:
					$propArr = $this->getRecordPropertiesFromRow($table,$newRow);
					$theLogId = $this->log($table,$id,2,$recpid,0,"Record '%s' (%s) was updated.",10,array($propArr['header'],$table.':'.$id),$propArr['event_pid']);

						// Set History data:
					$this->setHistory($table,$id,$theLogId);

						// Clear cache for relavant pages:
					$this->clear_cache($table,$id);

						// Unset the pageCache for the id if table was page.
					if ($table=='pages')	unset($this->pageCache[$id]);
				} else {
					$this->log($table,$id,2,0,2,"SQL error: '%s' (%s)",12,array($GLOBALS['TYPO3_DB']->sql_error(),$table.':'.$id));
				}
			}
		}
	}

	/**
	 * Compares the incoming field array with the current record and unsets all fields which are the same.
	 * If the returned array is empty, then the record should not be updated!
	 * $fieldArray must be an array.
	 *
	 * @param	string		Record table name
	 * @param	integer		Record uid
	 * @param	array		Array of field=>value pairs intended to be inserted into the database. All keys with values matching exactly the current value will be unset!
	 * @return	array		Returns $fieldArray
	 */
	function compareFieldArrayWithCurrentAndUnset($table,$id,$fieldArray)	{

			// Fetch the original record:
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, 'uid='.intval($id));
		$currentRecord = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);

			// If the current record exists (which it should...), begin comparison:
		if (is_array($currentRecord))	{

				// Read all field types:
			$c = 0;
			$cRecTypes = array();
			foreach($currentRecord as $col => $val)	{
// DBAL
#				$cRecTypes[$col] = $GLOBALS['TYPO3_DB']->sql_field_type($table,$col);
				$cRecTypes[$col] = $GLOBALS['TYPO3_DB']->sql_field_type($res,$c);
				$c++;
			}
#debug($cRecTypes);

				// Free result:
			$GLOBALS['TYPO3_DB']->sql_free_result($res);

				// Unset the fields which are similar:
			foreach($fieldArray as $col => $val)	{
				if (
						#!isset($currentRecord[$col]) ||		// Unset fields which were NOT found in the current record! [Uncommented because NULL fields will not return an entry in the array!]
						!strcmp($val,$currentRecord[$col]) ||	// Unset fields which matched exactly.
						($cRecTypes[$col]=='int' && $currentRecord[$col]==0 && !strcmp($val,''))	// Now, a situation where TYPO3 tries to put an empty string into an integer field, we should not strcmp the integer-zero and '', but rather accept them to be similar.
					)	{
					unset($fieldArray[$col]);
				} else {
					$this->historyRecords[$table.':'.$id]['oldRecord'][$col] = $currentRecord[$col];
					$this->historyRecords[$table.':'.$id]['newRecord'][$col] = $fieldArray[$col];
				}
			}
		} else {	// If the current record does not exist this is an error anyways and we just return an empty array here.
			$fieldArray = array();
		}

		return $fieldArray;
	}

	/**
	 * Insert into database
	 * Does not check permissions but expects them to be verified on beforehand
	 *
	 * @param	string		Record table name
	 * @param	string		"NEW...." uid string
	 * @param	array		Array of field=>value pairs to insert. FIELDS MUST MATCH the database FIELDS. No check is done. "pid" must point to the destination of the record!
	 * @param	boolean		Set to true if new version is created.
	 * @param	integer		Suggested UID value for the inserted record. See the array $this->suggestedInsertUids; Admin-only feature
	 * @return	void
	 */
	function insertDB($table,$id,$fieldArray,$newVersion=FALSE,$suggestedUid=0)	{
		global $TCA;

		if (is_array($fieldArray) && is_array($TCA[$table]) && isset($fieldArray['pid']))	{
			unset($fieldArray['uid']);	// Do NOT insert the UID field, ever!

			if (count($fieldArray))	{

					// Check for "suggestedUid".
					// This feature is used by the import functionality to force a new record to have a certain UID value.
					// This is only recommended for use when the destination server is a passive mirrow of another server.
					// As a security measure this feature is available only for Admin Users (for now)
				$suggestedUid = intval($suggestedUid);
				if ($this->BE_USER->isAdmin() && $suggestedUid && $this->suggestedInsertUids[$table.':'.$suggestedUid])	{
						// When the value of ->suggestedInsertUids[...] is "DELETE" it will try to remove the previous record
					if ($this->suggestedInsertUids[$table.':'.$suggestedUid]==='DELETE')	{
							// DELETE:
						$GLOBALS['TYPO3_DB']->exec_DELETEquery($table, 'uid='.intval($suggestedUid));
					}
					$fieldArray['uid'] = $suggestedUid;
				}

					// Execute the INSERT query:
				$GLOBALS['TYPO3_DB']->exec_INSERTquery($table, $fieldArray);

					// If succees, do...:
				if (!$GLOBALS['TYPO3_DB']->sql_error())	{

						// Set mapping for NEW... -> real uid:
					$NEW_id = $id;		// the NEW_id now holds the 'NEW....' -id
					$id = $GLOBALS['TYPO3_DB']->sql_insert_id();
					$this->substNEWwithIDs[$NEW_id] = $id;
					$this->substNEWwithIDs_table[$NEW_id] = $table;

						// Checking the record is properly saved and writing to log
					if ($this->checkStoredRecords)	{
						$newRow = $this->checkStoredRecord($table,$id,$fieldArray,1);
					}

					if ($newVersion)	{
						$this->log($table,$id,1,0,0,"New version created of table '%s', uid '%s'",10,array($table,$fieldArray['t3ver_oid']),$newRow['pid'],$NEW_id);
					} else {
							// Set log entry:
						if ($table=='pages')	{
							$thePositionID = $this->getInterfacePagePositionID($id);
						} else {
							$thePositionID = 0;
						}
						$propArr = $this->getRecordPropertiesFromRow($table,$newRow);
						$page_propArr = $this->getRecordProperties('pages',$propArr['pid']);
						$this->log($table,$id,1,$thePositionID,0,"Record '%s' (%s) was inserted on page '%s' (%s)",10,array($propArr['header'],$table.':'.$id,$page_propArr['header'],$newRow['pid']),$newRow['pid'],$NEW_id);

							// Clear cache for relavant pages:
						$this->clear_cache($table,$id);
					}
				} else {
					$this->log($table,$id,1,0,2,"SQL error: '%s' (%s)",12,array($GLOBALS['TYPO3_DB']->sql_error(),$table.':'.$id));
				}
			}
		}
	}

	/**
	 * Checking stored record to see if the written values are properly updated.
	 *
	 * @param	string		Record table name
	 * @param	integer		Record uid
	 * @param	array		Array of field=>value pairs to insert/update
	 * @param	string		Action, for logging only.
	 * @return	array		Selected row
	 * @see insertDB(), updateDB()
	 */
	function checkStoredRecord($table,$id,$fieldArray,$action)	{
		global $TCA;

		$id = intval($id);
		if (is_array($TCA[$table]) && $id)	{
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, 'uid='.intval($id));
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
					// Traverse array of values that was inserted into the database and compare with the actually stored value:
				$errorString = array();
				foreach($fieldArray as $key => $value)	{
					if ($this->checkStoredRecords_loose && !$value && !$row[$key])	{
						// Nothing...
					} elseif (strcmp($value,$row[$key]))	{
					  // DEBUGGING KFISH
					  // debug(array("$value != ".$row[$key]));
						$errorString[] = $key;
					}
				}

					// Set log message if there were fields with unmatching values:
				if (count($errorString))	{
					$this->log($table,$id,$action,0,102,'These fields are not properly updated in database: ('.implode(',',$errorString).') Probably value mismatch with fieldtype.');
				}

					// Return selected rows:
				return $row;
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
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
	 * If the $table is 'pages' then cache is cleared for all pages on the same level (and subsequent?)
	 * Else just clear the cache for the parent page of the record.
	 *
	 * @param	string		Table name of record that was just updated.
	 * @param	integer		UID of updated / inserted record
	 * @return	void
	 */
	function clear_cache($table,$uid) {
		global $TCA, $TYPO3_CONF_VARS;

		$uid = intval($uid);
		if (is_array($TCA[$table]) && $uid > 0)	{

				// Get Page TSconfig relavant:
			list($tscPID) = t3lib_BEfunc::getTSCpid($table,$uid,'');
			$TSConfig = $this->getTCEMAIN_TSconfig($tscPID);

			if (!$TSConfig['clearCache_disable'])	{

					// If table is "pages":
				if (t3lib_extMgm::isLoaded('cms'))	{
					$list_cache = array();
					if ($table=='pages')	{

							// Builds list of pages on the SAME level as this page (siblings)
						$res_tmp = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
										'A.pid AS pid, B.uid AS uid',
										'pages A, pages B',
										'A.uid='.intval($uid).' AND B.pid=A.pid AND B.deleted=0'
									);

						$pid_tmp = 0;
						while ($row_tmp = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_tmp)) {
							$list_cache[] = $row_tmp['uid'];
							$pid_tmp = $row_tmp['pid'];

								// Add children as well:
							if ($TSConfig['clearCache_pageSiblingChildren'])	{
								$res_tmp2 = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
												'uid',
												'pages',
												'pid='.intval($row_tmp['uid']).' AND deleted=0'
											);
								while ($row_tmp2 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_tmp2))	{
									$list_cache[] = $row_tmp2['uid'];
								}
							}
						}

							// Finally, add the parent page as well:
						$list_cache[] = $pid_tmp;

							// Add grand-parent as well:
						if ($TSConfig['clearCache_pageGrandParent'])	{
							$res_tmp = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
											'pid',
											'pages',
											'uid='.intval($pid_tmp)
										);
							if ($row_tmp = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_tmp))	{
								$list_cache[] = $row_tmp['pid'];
							}
						}
					} else {	// For other tables than "pages", delete cache for the records "parent page".
						$list_cache[] = intval($this->getPID($table,$uid));
					}

						// Call pre-processing function for clearing of cache for page ids:
					if (is_array($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearPageCacheEval']))	{
						foreach($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearPageCacheEval'] as $funcName)	{
							$_params = array('pageIdArray' => &$list_cache, 'table' => $table, 'uid' => $uid, 'functionID' => 'clear_cache()');
								// Returns the array of ids to clear, false if nothing should be cleared! Never an empty array!
							t3lib_div::callUserFunction($funcName,$_params,$this);
						}
					}

						// Delete cache for selected pages:
					if (is_array($list_cache))	{
						$GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_pages','page_id IN ('.implode(',',$GLOBALS['TYPO3_DB']->cleanIntArray($list_cache)).')');
						$GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_pagesection', 'page_id IN ('.implode(',',$GLOBALS['TYPO3_DB']->cleanIntArray($list_cache)).')');
					}
				}
			}

				// Clear cache for pages entered in TSconfig:
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
				$_params = array('table' => $table,'uid' => $uid,'uid_page' => $uid_page,'TSConfig' => $TSConfig);
				foreach($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'] as $_funcRef)	{
					t3lib_div::callUserFunction($_funcRef,$_params,$this);
				}
			}
		}
	}

	/**
	 * Returns the pid of a record from $table with $uid
	 *
	 * @param	string		Table name
	 * @param	integer		Record uid
	 * @return	integer		PID value (unless the record did not exist in which case FALSE)
	 */
	function getPID($table,$uid)	{
		$res_tmp = $GLOBALS['TYPO3_DB']->exec_SELECTquery('pid', $table, 'uid='.intval($uid));
		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_tmp))	{
			return $row['pid'];
		}
	}

























	/*********************************************
	 *
	 * PROCESSING COMMANDS
	 *
	 ********************************************/

	/**
	 * Processing the cmd-array
	 * See "TYPO3 Core API" for a description of the options.
	 *
	 * @return	void
	 */
	function process_cmdmap() {
		global $TCA;

			// Traverse command map:
		reset ($this->cmdmap);
		while (list($table,) = each($this->cmdmap))	{

				// Check if the table may be modified!
			$modifyAccessList = $this->checkModifyAccessList($table);
			if (!$modifyAccessList)	{
				$this->log($table,$id,2,0,1,"Attempt to modify table '%s' without permission",1,array($table));
			}

				// Check basic permissions and circumstances:
			if (isset($TCA[$table]) && !$this->tableReadOnly($table) && is_array($this->cmdmap[$table]) && $modifyAccessList)	{

					// Traverse the command map:
				foreach($this->cmdmap[$table] as $id => $incomingCmdArray)	{
					if (is_array($incomingCmdArray))	{	// have found a command.

							// Get command and value (notice, only one command is observed at a time!):
						reset($incomingCmdArray);
						$command = key($incomingCmdArray);
 						$value = current($incomingCmdArray);

							// Init copyMapping array:
						$this->copyMappingArray = Array();		// Must clear this array before call from here to those functions: Contains mapping information between new and old id numbers.

							// Branch, based on command
						switch ($command)	{
							case 'move':
								$this->moveRecord($table,$id,$value);
							break;
							case 'copy':
								if ($table == 'pages')	{
									$this->copyPages($id,$value);
								} else {
									$this->copyRecord($table,$id,$value,1);
								}
							break;
							case 'localize':
								$this->copyRecord_localize($table,$id,$value);
							break;
							case 'version':
								switch ((string)$value['action'])	{
									case 'new':
										$this->versionizeTree = t3lib_div::intInRange($value['treeLevels'],-1,4);	// Max 4 levels of versioning...
										if ($table == 'pages' && $this->versionizeTree>=0)	{
											$this->versionizePages($id,$value['label']);
										} else {
											$this->versionizeRecord($table,$id,$value['label']);
										}
									break;
									case 'swap':
										$this->version_swap($table,$id,$value['swapWith'],$value['swapContent']);
									break;
								}
							break;
							case 'delete':
								if ($table == 'pages')	{
									$this->deletePages($id);
								} else {
									$this->deleteRecord($table,$id, 0);
								}
							break;
						}
							// Merging the copy-array info together for remapping purposes.
						$this->copyMappingArray_merged= t3lib_div::array_merge_recursive_overrule($this->copyMappingArray_merged,$this->copyMappingArray);
					}
				}
			}
		}

#debug($this->copyMappingArray_merged,'$this->copyMappingArray_merged');
#debug($this->registerDBList,'$this->registerDBList');

			// Finally, before exit, check if there are ID references to remap. This might be the case if versioning or copying has taken place!
		$this->remapListedDBRecords();
	}

	/**
	 * Moving records
	 *
	 * @param	string		Table name to move
	 * @param	integer		Record uid to move
	 * @param	integer		Position to move to: $destPid: >=0 then it points to a page-id on which to insert the record (as the first element). <0 then it points to a uid from its own table after which to insert it (works if
	 * @return	void
	 */
	function moveRecord($table,$uid,$destPid)	{
		global $TCA;

			// Initialize:
		$sortRow = $TCA[$table]['ctrl']['sortby'];
		$destPid = intval($destPid);
		$origDestPid = $destPid;

		if ($TCA[$table])	{
			$propArr = $this->getRecordProperties($table,$uid);	// Get this before we change the pid (for logging)
			$resolvedPid = $this->resolvePid($table,$destPid);	// This is the actual pid of the moving.

				// Finding out, if the record may be moved from where it is. If the record is a non-page, then it depends on edit-permissions.
				// If the record is a page, then there are two options: If the page is moved within itself, (same pid) it's edit-perms of the pid. If moved to another place then its both delete-perms of the pid and new-page perms on the destination.
			if ($table!='pages' || $resolvedPid==$propArr['pid'])	{
				$mayMoveAccess = $this->checkRecordUpdateAccess($table,$uid);	// Edit rights for the record...
			} else {
				$mayMoveAccess = $this->doesRecordExist($table,$uid,'delete');
			}

				// Finding out, if the record may be moved TO another place. Here we check insert-rights (non-pages = edit, pages = new), unless the pages is moved on the same pid, then edit-rights are checked
			if ($table!='pages' || $resolvedPid!=$propArr['pid'])	{
				$mayInsertAccess = $this->checkRecordInsertAccess($table,$resolvedPid,4);	// Edit rights for the record...
			} else {
				$mayInsertAccess = $this->checkRecordUpdateAccess($table,$uid);
			}

				// Checking if the pid is negativ, but no sorting row is defined. In that case, find the correct pid. Basically this check make the error message 4-13 meaning less... But you can always remove this check if you prefer the error instead of a no-good action (which is to move the record to its own page...)
			if ($destPid<0 && !$sortRow)	{
				$destPid = $resolvedPid;
			}

				// Timestamp field:
			$updateFields = array();
			if ($TCA[$table]['ctrl']['tstamp'])	{
				$updateFields[$TCA[$table]['ctrl']['tstamp']] = time();
			}

				// If moving is allowed, begin the processing:
			if ($mayMoveAccess)	{
				if ($destPid>=0)	{	// insert as first element on page (where uid = $destPid)
					if ($mayInsertAccess)	{
						if ($table!='pages' || $this->destNotInsideSelf ($destPid,$uid))	{
							$this->clear_cache($table,$uid);	// clear cache before moving

							$updateFields['pid'] = $destPid;	// Setting PID

								// table is sorted by 'sortby'
							if ($sortRow)	{
								$sortNumber = $this->getSortNumber($table,$uid,$destPid);
								$updateFields[$sortRow] = $sortNumber;
							}

								// Create query for update:
							$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid='.intval($uid), $updateFields);

								// Logging...
							$newPropArr = $this->getRecordProperties($table,$uid);
							$oldpagePropArr = $this->getRecordProperties('pages',$propArr['pid']);
							$newpagePropArr = $this->getRecordProperties('pages',$destPid);

							if ($destPid!=$propArr['pid'])	{
								$this->log($table,$uid,4,$destPid,0,"Moved record '%s' (%s) to page '%s' (%s)",2,array($propArr['header'],$table.':'.$uid, $newpagePropArr['header'], $newPropArr['pid']),$propArr['pid']);	// Logged to old page
								$this->log($table,$uid,4,$destPid,0,"Moved record '%s' (%s) from page '%s' (%s)",3,array($propArr['header'],$table.':'.$uid, $oldpagePropArr['header'], $propArr['pid']),$destPid);	// Logged to new page
							} else {
								$this->log($table,$uid,4,$destPid,0,"Moved record '%s' (%s) on page '%s' (%s)",4,array($propArr['header'],$table.':'.$uid, $oldpagePropArr['header'], $propArr['pid']),$destPid);	// Logged to new page
							}
							$this->clear_cache($table,$uid);	// clear cache after moving
							$this->fixUniqueInPid($table,$uid);
								// fixCopyAfterDuplFields
							if ($origDestPid<0)	{$this->fixCopyAfterDuplFields($table,$uid,abs($origDestPid),1);}	// origDestPid is retrieve before it may possibly be converted to resolvePid if the table is not sorted anyway. In this way, copying records to after another records which are not sorted still lets you use this function in order to copy fields from the one before.
						} else {
							$destPropArr = $this->getRecordProperties('pages',$destPid);
							$this->log($table,$uid,4,0,1,"Attempt to move page '%s' (%s) to inside of its own rootline (at page '%s' (%s))",10,array($propArr['header'],$uid, $destPropArr['header'], $destPid),$propArr['pid']);
						}
					}
				} else {	// Put after another record
					if ($sortRow)	{	// table is being sorted
						$sortInfo = $this->getSortNumber($table,$uid,$destPid);
						$destPid = $sortInfo['pid'];	// Setting the destPid to the new pid of the record.
						if (is_array($sortInfo))	{	// If not an array, there was an error (which is already logged)
							if ($mayInsertAccess)	{
								if ($table!='pages' || $this->destNotInsideSelf($destPid,$uid))	{
									$this->clear_cache($table,$uid);	// clear cache before moving

										// We now update the pid and sortnumber
									$updateFields['pid'] = $destPid;
									$updateFields[$sortRow] = $sortInfo['sortNumber'];
									$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid='.intval($uid), $updateFields);

										// Logging...
									if ($table=='pages')	{
										$thePositionID = $this->getInterfacePagePositionID($uid);
									} else {
										$thePositionID = 0;
									}
									$this->log($table,$uid,4,$thePositionID,0,'');

										// Logging...
									$newPropArr = $this->getRecordProperties($table,$uid);
									$oldpagePropArr = $this->getRecordProperties('pages',$propArr['pid']);
									if ($destPid!=$propArr['pid'])	{
										$newpagePropArr = $this->getRecordProperties('pages',$destPid);
										$this->log($table,$uid,4,$thePositionID,0,"Moved record '%s' (%s) to page '%s' (%s)",2,array($propArr['header'],$table.':'.$uid, $newpagePropArr['header'], $newPropArr['pid']),$propArr['pid']);	// Logged to old page
										$this->log($table,$uid,4,$thePositionID,0,"Moved record '%s' (%s) from page '%s' (%s)",3,array($propArr['header'],$table.':'.$uid, $oldpagePropArr['header'], $propArr['pid']),$destPid);	// Logged to new page
									} else {
										$this->log($table,$uid,4,$thePositionID,0,"Moved record '%s' (%s) on page '%s' (%s)",4,array($propArr['header'],$table.':'.$uid, $oldpagePropArr['header'], $propArr['pid']),$destPid);	// Logged to new page
									}

										// clear cache after moving
									$this->clear_cache($table,$uid);

										// fixUniqueInPid
									$this->fixUniqueInPid($table,$uid);

										// fixCopyAfterDuplFields
									if ($origDestPid<0)	{$this->fixCopyAfterDuplFields($table,$uid,abs($origDestPid),1);}
								} else {
									$destPropArr = $this->getRecordProperties('pages',$destPid);
									$this->log($table,$uid,4,0,1,"Attempt to move page '%s' (%s) to inside of its own rootline (at page '%s' (%s))",10,array($propArr['header'],$uid, $destPropArr['header'], $destPid),$propArr['pid']);
								}
							}
						}
					} else {
						$this->log($table,$uid,4,0,1,"Attempt to move record '%s' (%s) to after another record, although the table has no sorting row.",13,array($propArr['header'],$table.':'.$uid),$propArr['event_pid']);
					}
				}
			} else {
				$this->log($table,$uid,4,0,1,"Attempt to move record '%s' (%s) without having permissions to do so",14,array($propArr['header'],$table.':'.$uid),$propArr['event_pid']);
			}
		}
	}

	/**
	 * Copying records
	 *
	 * @param	string		Element table
	 * @param	integer		Element UID
	 * @param	integer		$destPid: >=0 then it points to a page-id on which to insert the record (as the first element). <0 then it points to a uid from its own table after which to insert it (works if
	 * @param	boolean		$first is a flag set, if the record copied is NOT a 'slave' to another record copied. That is, if this record was asked to be copied in the cmd-array
	 * @param	array		Associative array with field/value pairs to override directly. Notice; Fields must exist in the table record and NOT be among excluded fields!
	 * @param	string		Commalist of fields to exclude from the copy process (might get default values)
	 * @return	void
	 */
	function copyRecord($table,$uid,$destPid,$first=0,$overrideValues=array(),$excludeFields='')	{
		global $TCA;

		$uid = intval($uid);
		if ($TCA[$table] && $uid)	{
			t3lib_div::loadTCA($table);
			if ($this->doesRecordExist($table,$uid,'show'))	{		// This checks if the record can be selected which is all that a copy action requires.
				$data = Array();

				$nonFields = array_unique(t3lib_div::trimExplode(',','uid,perms_userid,perms_groupid,perms_user,perms_group,perms_everybody,t3ver_oid,t3ver_id,t3ver_label,'.$excludeFields,1));

				$row = $this->recordInfo($table,$uid,'*');
				if (is_array($row))	{

						// Initializing:
					$theNewID = uniqid('NEW');
					$enableField = isset($TCA[$table]['ctrl']['enablecolumns']) ? $TCA[$table]['ctrl']['enablecolumns']['disabled'] : '';
					$headerField = $TCA[$table]['ctrl']['label'];

						// Getting default data:
					$defaultData = $this->newFieldArray($table);

						// Getting "copy-after" fields if applicable:
						// origDestPid is retrieve before it may possibly be converted to resolvePid if the table is not sorted anyway. In this way, copying records to after another records which are not sorted still lets you use this function in order to copy fields from the one before.
					$copyAfterFields = $destPid<0 ? $this->fixCopyAfterDuplFields($table,$uid,abs($destPid),0) : array();

						// Page TSconfig related:
					$tscPID = t3lib_BEfunc::getTSconfig_pidValue($table,$uid,$destPid);	// NOT using t3lib_BEfunc::getTSCpid() because we need the real pid - not the id of a page, if the input is a page...
					$TSConfig = $this->getTCEMAIN_TSconfig($tscPID);
					$tE = $this->getTableEntries($table,$TSConfig);

						// Traverse ALL fields of the selected record:
					foreach($row as $field => $value)	{
						if (!in_array($field,$nonFields))	{

								// Get TCA configuration for the field:
							$conf = $TCA[$table]['columns'][$field]['config'];

								// Preparation/Processing of the value:
							if ($field=='pid')	{	// "pid" is hardcoded of course:
								$value = $destPid;
							} elseif (isset($overrideValues[$field]))	{	// Override value...
								$value = $overrideValues[$field];
							} elseif (isset($copyAfterFields[$field]))	{	// Copy-after value if available:
								$value = $copyAfterFields[$field];
							} elseif ($TCA[$table]['ctrl']['setToDefaultOnCopy'] && t3lib_div::inList($TCA[$table]['ctrl']['setToDefaultOnCopy'],$field))	{	// Revert to default for some fields:
								$value = $defaultData[$field];
							} else {
									// Hide at copy may override:
								if ($first && $field==$enableField && $TCA[$table]['ctrl']['hideAtCopy'] && !$this->neverHideAtCopy && !$tE['disableHideAtCopy'])	{
									$value=1;
								}
									// Prepend label on copy:
								if ($first && $field==$headerField && $TCA[$table]['ctrl']['prependAtCopy'] && !$tE['disablePrependAtCopy'])	{
									$value = $this->getCopyHeader($table,$this->resolvePid($table,$destPid),$field,$this->clearPrefixFromValue($table,$value),0);
								}
									// Processing based on the TCA config field type (files, references, flexforms...)
								$value = $this->copyRecord_procBasedOnFieldType($table,$uid,$field,$value,$row,$conf);
							}

								// Add value to array.
							$data[$table][$theNewID][$field] = $value;
						}

							// Overriding values:
						if ($TCA[$table]['ctrl']['editlock'])	{
							$data[$table][$theNewID][$TCA[$table]['ctrl']['editlock']] = 0;
						}
					}

						// Do the copy by simply submitting the array through TCEmain:
					$copyTCE = t3lib_div::makeInstance('t3lib_TCEmain');
					$copyTCE->stripslashes_values = 0;
					$copyTCE->copyTree = $this->copyTree;
					$copyTCE->cachedTSconfig = $this->cachedTSconfig;	// Copy forth the cached TSconfig
					$copyTCE->dontProcessTransformations=1;		// Transformations should NOT be carried out during copy
	//				$copyTCE->enableLogging = $table=='pages'?1:0;	// If enabled the list-view does not update...

					$copyTCE->start($data,'',$this->BE_USER);
					$copyTCE->process_datamap();

						// Getting the new UID:
					$theNewSQLID = $copyTCE->substNEWwithIDs[$theNewID];
					if ($theNewSQLID)	{
						$this->copyMappingArray[$table][$uid] = $theNewSQLID;
					}

						// Copy back the cached TSconfig
					$this->cachedTSconfig = $copyTCE->cachedTSconfig;
					unset($copyTCE);
				} else $this->log($table,$uid,3,0,1,'Attempt to copy record that did not exist!');
			} else $this->log($table,$uid,3,0,1,'Attempt to copy record without permission');
		}
	}

	/**
	 * Copying records, but makes a "raw" copy of a record.
	 * Basically the only thing observed is field processing like the copying of files and correct of ids. All other fields are 1-1 copied.
	 * Technically the copy is made with THIS instance of the tcemain class contrary to copyRecord() which creates a new instance and uses the processData() function.
	 * The copy is created by insertNewCopyVersion() which bypasses most of the regular input checking associated with processData() - maybe copyRecord() should even do this as well!?
	 * This function is used to create new versions of a record.
	 * NOTICE: DOES NOT CHECK PERMISSIONS to create! And since page permissions are just passed through and not changed to the user who executes the copy we cannot enforce permissions without getting an incomplete copy - unless we change permissions of course.
	 *
	 * @param	string		Element table
	 * @param	integer		Element UID
	 * @param	integer		Element PID (real PID, not checked)
	 * @param	array		Override array - must NOT contain any fields not in the table!
	 * @return	integer		Returns the new ID of the record (if applicable)
	 */
	function copyRecord_raw($table,$uid,$pid,$overrideArray=array())	{
		global $TCA;

		$uid = intval($uid);
		if ($TCA[$table] && $uid)	{
			t3lib_div::loadTCA($table);
			if ($this->doesRecordExist($table,$uid,'show'))	{

					// Set up fields which should not be processed. They are still written - just passed through no-questions-asked!
				$nonFields = array('uid','pid','t3ver_id','t3ver_oid','t3ver_label','perms_userid','perms_groupid','perms_user','perms_group','perms_everybody');

					// Select main record:
				$row = $this->recordInfo($table,$uid,'*');
				if (is_array($row))	{

						// Merge in override array.
					$row = array_merge($row,$overrideArray);

						// Traverse ALL fields of the selected record:
					foreach($row as $field => $value)	{
						if (!in_array($field,$nonFields))	{

								// Get TCA configuration for the field:
							$conf = $TCA[$table]['columns'][$field]['config'];
							if (is_array($conf))	{
									// Processing based on the TCA config field type (files, references, flexforms...)
								$value = $this->copyRecord_procBasedOnFieldType($table,$uid,$field,$value,$row,$conf);
							}

								// Add value to array.
							$row[$field] = $value;
						}
					}

						// Force versioning related fields:
					$row['pid'] = $pid;

						// Do the copy by internal function
					$theNewSQLID = $this->insertNewCopyVersion($table,$row,$pid);
					if ($theNewSQLID)	{
						return $this->copyMappingArray[$table][$uid] = $theNewSQLID;
					}
				} else $this->log($table,$uid,3,0,1,'Attempt to rawcopy/versionize record that did not exist!');
			} else $this->log($table,$uid,3,0,1,'Attempt to rawcopy/versionize record without copy permission');
		}
	}

	/**
	 * Inserts a record in the database, passing TCA configuration values through checkValue() but otherwise does NOTHING and checks nothing regarding permissions.
	 * Passes the "version" parameter to insertDB() so the copy will look like a new version in the log - should probably be changed or modified a bit for more broad usage...
	 *
	 * @param	string		Table name
	 * @param	array		Field array to insert as a record
	 * @param	integer		The value of PID field.  -1 is indication that we are creating a new version!
	 * @return	integer		Returns the new ID of the record (if applicable)
	 */
	function insertNewCopyVersion($table,$fieldArray,$realPid)	{
		global $TCA;

		$id = uniqid('NEW');

			// $fieldArray is set as current record.
			// The point is that when new records are created as copies with flex type fields there might be a field containing information about which DataStructure to use and without that information the flexforms cannot be correctly processed.... This should be OK since the $checkValueRecord is used by the flexform evaluation only anyways...
		$this->checkValue_currentRecord = $fieldArray;

			// Traverse record and input-process each value:
		foreach($fieldArray as $field => $fieldValue)	{
			if (isset($TCA[$table]['columns'][$field]))	{
					// Evaluating the value.
				$res = $this->checkValue($table,$field,$fieldValue,$id,'new',$realPid,0);
				if (isset($res['value']))	{
					$fieldArray[$field] = $res['value'];
				}
			}
		}

			// System fields being set:
		if ($TCA[$table]['ctrl']['crdate'])	{
			$fieldArray[$TCA[$table]['ctrl']['crdate']]=time();
		}
		if ($TCA[$table]['ctrl']['cruser_id'])	{
			$fieldArray[$TCA[$table]['ctrl']['cruser_id']]=$this->userid;
		}
		if ($TCA[$table]['ctrl']['tstamp'])	{
			$fieldArray[$TCA[$table]['ctrl']['tstamp']]=time();
		}

			// Finally, insert record:
		$this->insertDB($table,$id,$fieldArray, TRUE);

			// Return new id:
		return $this->substNEWwithIDs[$id];
	}

	/**
	 * Processing/Preparing content for copyRecord() function
	 *
	 * @param	string		Table name
	 * @param	integer		Record uid
	 * @param	string		Field name being processed
	 * @param	string		Input value to be processed.
	 * @param	array		Record array
	 * @param	array		TCA field configuration
	 * @return	mixed		Processed value. Normally a string/integer, but can be an array for flexforms!
	 * @access private
	 * @see copyRecord()
	 */
	function copyRecord_procBasedOnFieldType($table,$uid,$field,$value,$row,$conf)	{
		global $TCA;

			// Process references and files, currently that means only the files, prepending absolute paths (so the TCEmain engine will detect the file as new and one that should be made into a copy)
		$value = $this->copyRecord_procFilesRefs($conf, $uid, $value);


			// Register if there are references to take care of (no change to value):
		if ($this->isReferenceField($conf))	{
			$allowedTables = $conf['type']=='group' ? $conf['allowed'] : $conf['foreign_table'].','.$conf['neg_foreign_table'];
			$prependName = $conf['type']=='group' ? $conf['prepend_tname'] : $conf['neg_foreign_table'];
			if ($conf['MM'])	{
				$dbAnalysis = t3lib_div::makeInstance('t3lib_loadDBGroup');
				$dbAnalysis->start('',$allowedTables,$conf['MM'],$uid);
				$value = implode(',',$dbAnalysis->getValueArray($prependName));
			}
			if ($value)	{	// Setting the value in this array will notify the remapListedDBRecords() function that this field MAY need references to be corrected
				$this->registerDBList[$table][$uid][$field] = $value;
			}
		}

			// For "flex" fieldtypes we need to traverse the structure for two reasons: If there are file references they have to be prepended with absolute paths and if there are database reference they MIGHT need to be remapped (still done in remapListedDBRecords())
		if ($conf['type']=='flex')	{

				// Get current value array:
			$dataStructArray = t3lib_BEfunc::getFlexFormDS($conf, $row, $table);
			$currentValueArray = t3lib_div::xml2array($value);

				// Traversing the XML structure, processing files:
			if (is_array($currentValueArray))	{
				$currentValueArray['data'] = $this->checkValue_flex_procInData(
							$currentValueArray['data'],
							array(),	// Not used.
							array(),	// Not used.
							$dataStructArray,
							array($table,$uid,$field),	// Parameters.
							'copyRecord_flexFormCallBack'
						);
				$value = $currentValueArray;	// Setting value as an array! -> which means the input will be processed according to the 'flex' type when the new copy is created.
			}
		}

		return $value;
	}

	/**
	 * Localizes a record to another system language
	 *
	 * @param	string		Table name
	 * @param	integer		Record uid (to be localized)
	 * @param	integer		Language ID (from sys_language table)
	 * @return
	 */
	function copyRecord_localize($table,$uid,$language)	{
		global $TCA;

		$uid = intval($uid);

		if ($TCA[$table] && $uid)	{
			t3lib_div::loadTCA($table);

			if ($TCA[$table]['ctrl']['languageField'] && $TCA[$table]['ctrl']['transOrigPointerField'])	{
				if ($langRec = t3lib_BEfunc::getRecord('sys_language',intval($language),'uid,title'))	{
					if ($this->doesRecordExist($table,$uid,'show'))	{

						$row = $this->recordInfo($table,$uid,'*');
						if (is_array($row))	{
							if ($row[$TCA[$table]['ctrl']['languageField']] <= 0)	{
								if ($row[$TCA[$table]['ctrl']['transOrigPointerField']] == 0)	{
									if (!t3lib_BEfunc::getRecordsByField($table,$TCA[$table]['ctrl']['transOrigPointerField'],$uid,'AND pid='.intval($row['pid']).' AND '.$TCA[$table]['ctrl']['languageField'].'='.$langRec['uid']))	{

											// Initialize:
										$overrideValues = array();
										$excludeFields = array();

											// Set override values:
										$overrideValues[$TCA[$table]['ctrl']['languageField']] = $langRec['uid'];
										$overrideValues[$TCA[$table]['ctrl']['transOrigPointerField']] = $uid;

											// Set exclude Fields:
										foreach($TCA[$table]['columns'] as $fN => $fCfg)	{
											if ($fCfg['l10n_mode']=='prefixLangTitle')	{	// Check if we are just prefixing:
												if ($fCfg['config']['type']=='text' || $fCfg['config']['type']=='input')	{
													$overrideValues[$fN] = '[Translate to '.$langRec['title'].':] '.$row[$fN];
												}
											} elseif (t3lib_div::inList('exclude,noCopy,mergeIfNotBlank',$fCfg['l10n_mode']) && $fN!=$TCA[$table]['ctrl']['languageField'] && $fN!=$TCA[$table]['ctrl']['transOrigPointerField']) {	 // Otherwise, do not copy field (unless it is the language field or pointer to the original language)
												$excludeFields[] = $fN;
											}
										}
											// Execute the copy:
										$this->copyRecord($table,$uid,-$uid,1,$overrideValues,implode(',',$excludeFields));
									} else $this->log($table,$uid,3,0,1,'Localization failed; There already was a localization for this language of the record!');
								} else $this->log($table,$uid,3,0,1,'Localization failed; Source record contained a reference to an original default record (which is strange)!');
							} else $this->log($table,$uid,3,0,1,'Localization failed; Source record had another language than "Default" or "All" defined!');
						} else $this->log($table,$uid,3,0,1,'Attempt to localize record that did not exist!');
					} else $this->log($table,$uid,3,0,1,'Attempt to localize record without permission');
				} else $this->log($table,$uid,3,0,1,'Sys language UID "'.$language.'" not found valid!');
			} else $this->log($table,$uid,3,0,1,'Localization failed; "languageField" and "transOrigPointerField" must be defined for the table!');
		}
	}

	/**
	 * Callback function for traversing the FlexForm structure in relation to creating copied files of file relations inside of flex form structures.
	 *
	 * @param	array		Array of parameters in num-indexes: table, uid, field
	 * @param	array		TCA field configuration (from Data Structure XML)
	 * @param	string		The value of the flexForm field
	 * @param	string		Not used.
	 * @param	string		Not used.
	 * @return	array		Result array with key "value" containing the value of the processing.
	 * @see copyRecord(), checkValue_flex_procInData_travDS()
	 */
	function copyRecord_flexFormCallBack($pParams, $dsConf, $dataValue, $dataValue_ext1, $dataValue_ext2)	{

			// Extract parameters:
		list($table, $uid, $field) = $pParams;

			// Process references and files, currently that means only the files, prepending absolute paths:
		$dataValue = $this->copyRecord_procFilesRefs($dsConf, $uid, $dataValue);

			// If references are set for this field, set flag so they can be corrected later (in ->remapListedDBRecords())
		if ($this->isReferenceField($dsConf) && strlen($dataValue)) {
			$this->registerDBList[$table][$uid][$field] = 'FlexForm_reference';
		}

			// Return
		return array('value' => $dataValue);
	}

	/**
	 * Modifying a field value for any situation regarding files/references:
	 * For attached files: take current filenames and prepend absolute paths so they get copied.
	 * For DB references: Nothing done.
	 *
	 * @param	array		TCE field config
	 * @param	integer		Record UID
	 * @param	string		Field value (eg. list of files)
	 * @return	string		The (possibly modified) value
	 * @see copyRecord(), copyRecord_flexFormCallBack()
	 */
	function copyRecord_procFilesRefs($conf, $uid, $value)	{

			// Prepend absolute paths to files:
		if ($conf['type']=='group' && $conf['internal_type']=='file')	{

				// Get an array with files as values:
			if ($conf['MM'])	{
				$theFileValues = array();

				$dbAnalysis = t3lib_div::makeInstance('t3lib_loadDBGroup');
				$dbAnalysis->start('', 'files', $conf['MM'], $uid);

				foreach($dbAnalysis->itemArray as $somekey => $someval)	{
					if ($someval['id'])	{
						$theFileValues[] = $someval['id'];
					}
				}
			} else {
				$theFileValues = t3lib_div::trimExplode(',',$value,1);
			}

				// Traverse this array of files:
			$uploadFolder = $conf['uploadfolder'];
			$dest = $this->destPathFromUploadFolder($uploadFolder);
			$newValue = array();

			foreach($theFileValues as $file)	{
				if (trim($file))	{
					$realFile = $dest.'/'.trim($file);
					if (@is_file($realFile))	{
						$newValue[] = $realFile;
					}
				}
			}

				// Implode the new filelist into the new value (all files have absolute paths now which means they will get copied when entering TCEmain as new values...)
			$value = implode(',',$newValue);
		}

			// Return the new value:
		return $value;
	}

	/**
	 * Copying pages
	 * Main function for copying pages.
	 *
	 * @param	integer		Page UID to copy
	 * @param	integer		Destination PID: >=0 then it points to a page-id on which to insert the record (as the first element). <0 then it points to a uid from its own table after which to insert it (works if
	 * @return	void
	 */
	function copyPages($uid,$destPid)	{

			// Initialize:
		$uid = intval($uid);
		$destPid = intval($destPid);

			// Finding list of tables to copy.
		$copyTablesArray = $this->admin ? $this->compileAdminTables() : explode(',',$this->BE_USER->groupData['tables_modify']);	// These are the tables, the user may modify
		if (!strstr($this->copyWhichTables,'*'))	{		// If not all tables are allowed then make a list of allowed tables: That is the tables that figure in both allowed tables AND the copyTable-list
			foreach($copyTablesArray as $k => $table)	{
				if (!$table || !t3lib_div::inList($this->copyWhichTables.',pages',$table))	{	// pages are always going...
					unset($copyTablesArray[$k]);
				}
			}
		}
		$copyTablesArray = array_unique($copyTablesArray);

			// Begin to copy pages if we're allowed to:
		if ($this->admin || in_array('pages',$copyTablesArray))	{

				// Copy this page we're on. And set first-flag (this will trigger that the record is hidden if that is configured)!
			$this->copySpecificPage($uid,$destPid,$copyTablesArray,1);
			$theNewRootID = $this->copyMappingArray['pages'][$uid];		// This is the new ID of the rootpage of the copy-action. This ID is excluded when the list is gathered lateron

				// If we're going to copy recursively...:
			if ($theNewRootID && $this->copyTree)	{

					// Get ALL subpages to copy:
				$CPtable = $this->int_pageTreeInfo(Array(), $uid, intval($this->copyTree), $theNewRootID);

					// Now copying the subpages:
				foreach($CPtable as $thePageUid => $thePagePid)	{
					$newPid = $this->copyMappingArray['pages'][$thePagePid];
					if (isset($newPid))	{
						$this->copySpecificPage($thePageUid,$newPid,$copyTablesArray);
					} else {
						$this->log('pages',$uid,5,0,1,'Something went wrong during copying branch');
						break;
					}
				}
			}	// else the page was not copied. Too bad...
		} else {
			$this->log('pages',$uid,5,0,1,'Attempt to copy page without permission to this table');
		}
	}

	/**
	 * Copying a single page ($uid) to $destPid and all tables in the array copyTablesArray.
	 *
	 * @param	integer		Page uid
	 * @param	integer		Destination PID: >=0 then it points to a page-id on which to insert the record (as the first element). <0 then it points to a uid from its own table after which to insert it (works if
	 * @param	array		Table on pages to copy along with the page.
	 * @param	boolean		$first is a flag set, if the record copied is NOT a 'slave' to another record copied. That is, if this record was asked to be copied in the cmd-array
	 * @return	void
	 */
	function copySpecificPage($uid,$destPid,$copyTablesArray,$first=0)	{
		global $TCA;

			// Copy the page itself:
		$this->copyRecord('pages',$uid,$destPid,$first);
		$theNewRootID = $this->copyMappingArray['pages'][$uid];	// The new uid

			// If a new page was created upon the copy operation we will proceed with all the tables ON that page:
		if ($theNewRootID)	{
			foreach($copyTablesArray as $table)	{
				if ($table && is_array($TCA[$table]) && $table!='pages')	{	// all records under the page is copied.
					$mres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', $table, 'pid='.intval($uid).$this->deleteClause($table), '', ($TCA[$table]['ctrl']['sortby'] ? $TCA[$table]['ctrl']['sortby'].' DESC' : ''));
					while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($mres))	{
						$this->copyRecord($table,$row['uid'], $theNewRootID);	// Copying each of the underlying records...
					}
				}
			}
		}
	}

	/**
	 * Creates a new version of a record
	 * (Requires support in the table)
	 *
	 * @param	string		Table name
	 * @param	integer		Record uid to versionize
	 * @param	string		Version label
	 * @return	integer		Returns the id of the new version (if any)
	 * @see copyRecord()
	 */
	function versionizeRecord($table,$id,$label)	{
		global $TCA;

		$id = intval($id);

		if ($TCA[$table] && $TCA[$table]['ctrl']['versioning'] && $id>0)	{
			if ($this->doesRecordExist($table,$id,'show') && $this->doesRecordExist($table,$id,'edit'))	{

					// Select main record:
				$row = $this->recordInfo($table,$id,'pid,t3ver_id');
				if (is_array($row))	{
					if ($row['pid']>=0)	{

							// Look for next version number:
						$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
							't3ver_id',
							$table,
							'(t3ver_oid='.$id.' || uid='.$id.')'.$this->deleteClause($table),
							'',
							't3ver_id DESC',
							'1'
						);
						list($highestVerNumber) = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);

							// Look for version number of the current:
						$subVer = $row['t3ver_id'].'.'.($highestVerNumber+1);

							// Set up the values to override when making a raw-copy:
						$overrideArray = array(
							't3ver_id' => $highestVerNumber+1,
							't3ver_oid' => $id,
							't3ver_label' => ($label ? $label : $subVer.' / '.date('d-m-Y H:m:s'))
						);
						if ($TCA[$table]['ctrl']['editlock'])	{
							$overrideArray[$TCA[$table]['ctrl']['editlock']] = 0;
						}

							// Create raw-copy and return result:
						return $this->copyRecord_raw($table,$id,-1,$overrideArray);
					} else $this->log($table,$id,0,0,1,'Record you wanted to versionize was already a version in archive (pid=-1)!');
				} else $this->log($table,$id,0,0,1,'Record you wanted to versionize didnt exist!');
			} else $this->log($table,$id,0,0,1,'You didnt have correct permissions to make a new version (copy) of this record "'.$table.'" / '.$id);
		} else $this->log($table,$id,0,0,1,'Versioning is not supported for this table "'.$table.'" / '.$id);
	}

	/**
	 * Creates a new version of a page including content and possible subpages.
	 *
	 * @param	integer		Page uid to create new version of.
	 * @param	string		Version label
	 * @return	void
	 * @see copyPages()
	 */
	function versionizePages($uid,$label)	{
		global $TCA;

		$uid = intval($uid);

			// Finding list of tables ALLOWED to be copied
		$allowedTablesArray = $this->admin ? $this->compileAdminTables() : explode(',',$this->BE_USER->groupData['tables_modify']);	// These are the tables, the user may modify

			// Make list of tables that should come along with a new version of the page:
		$verTablesArray = array();
		$allTables = array_keys($TCA);
		foreach($allTables as $tN)	{
			if ($tN!='pages' && $TCA[$tN]['ctrl']['versioning_followPages'] && ($this->admin || in_array($tN, $allowedTablesArray)))	{
				$verTablesArray[] = $tN;
			}
		}

			// Begin to copy pages if we're allowed to:
		if ($this->admin || in_array('pages',$allowedTablesArray))	{

				// Versionize this page:
			$theNewRootID = $this->versionizeRecord('pages',$uid,$label);
			$this->rawCopyPageContent($uid,$theNewRootID,$verTablesArray);

				// If we're going to copy recursively...:
			if ($theNewRootID && $this->versionizeTree > 0)	{

					// Get ALL subpages to copy:
				$CPtable = $this->int_pageTreeInfo(Array(), $uid, intval($this->versionizeTree), $theNewRootID);

					// Now copying the subpages:
				foreach($CPtable as $thePageUid => $thePagePid)	{
					$newPid = $this->copyMappingArray['pages'][$thePagePid];
					if (isset($newPid))	{
						$theNewRootID = $this->copyRecord_raw('pages',$thePageUid,$newPid);
						$this->rawCopyPageContent($thePageUid,$theNewRootID,$verTablesArray);
					} else {
						$this->log('pages',$uid,0,0,1,'Something went wrong during copying branch (for versioning)');
						break;
					}
				}
			}	// else the page was not copied. Too bad...
		} else {
			$this->log('pages',$uid,0,0,1,'Attempt to versionize page without permission to this table');
		}
	}

	/**
	 * Copies all records from tables in $copyTablesArray from page with $old_pid to page with $new_pid
	 * Uses raw-copy for the operation (meant for versioning!)
	 *
	 * @param	integer		Current page id.
	 * @param	integer		New page id
	 * @param	array		Array of tables from which to copy
	 * @return	void
	 * @see versionizePages()
	 */
	function rawCopyPageContent($old_pid,$new_pid,$copyTablesArray)	{
		global $TCA;

		if ($new_pid)	{
			foreach($copyTablesArray as $table)	{
				if ($table && is_array($TCA[$table]) && $table!='pages')	{	// all records under the page is copied.
					$mres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', $table, 'pid='.intval($old_pid).$this->deleteClause($table));
					while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($mres))	{
						$this->copyRecord_raw($table,$row['uid'],$new_pid);	// Copying each of the underlying records (method RAW)
					}
				}
			}
		}
	}

	/**
	 * Swapping versions of a record
	 * Version from archive (future/past, called "swap version") will get the uid of the "t3ver_oid", the official element with uid = "t3ver_oid" will get the new versions old uid. PIDs are swapped also
	 *
	 * @param	string		Table name
	 * @param	integer		UID of the online record to swap
	 * @param	integer		UID of the archived version to swap with!
	 * @param	string		If set, swap content; If set then - for pages - the page content PIDs are swapped as well. If set to "ALL" then subpages are swapped as well!
	 * @return	void
	 */
	function version_swap($table,$id,$swapWith,$swapContent)	{
		global $TCA;

		/*
		Version ID swapping principles:
		  - Version from archive (future/past, called "swap version") will get the uid of the "t3ver_oid", the official element with uid = "t3ver_oid" will get the new versions old uid. PIDs are swapped also

			uid		pid			uid		t3ver_oid	pid
		1:	13		123	 -->	-13		247			123		(Original has negated UID, and sets t3ver_oid to the final UID (which is nice to know for recovery). PID is unchanged at this point)
		2:	247		-1	 -->	13		13			123		(Swap version gets original UID, correct t3ver_oid (not required for online version) and is moved to the final PID (123))
		3:	-13		123	 -->	247		13			-1		(Original gets the swap versions old UID, has t3ver_oid set correctly (important) and the ver. repository PID set right.)

			13 is online UID,
			247 is specific versions UID
			123 is the PID of the original record
			-1 is the versioning repository PID

			Recovery Process:
				Search for negative UID (here "-13"):
					YES: Step 1 completed, but at least step 3 didn't.
						Search for the negativ UIDs positive (here: "13")
							YES: Step 2 completed: Rollback: "t3ver_oid" of the -uid record shows the original UID of the swap record. Use that to change back UID and pid to -1. After that, proceed with recovery for step 1 (see below)
							NO: Only Step 1 completed! Rollback: Just change uid "-13" to "13" and "t3ver_oid" to "13" (not important)
					NO: No problems.
		*/

			// First, check if we may actually edit this record:
		if ($this->checkRecordUpdateAccess($table,$id))	{

				// Find fields to select:
			$keepFields = array();	// Keep-fields can be used for other fields than "sortby" if needed in the future...
			$selectFields = array('uid','pid','t3ver_oid');
			if ($TCA[$table]['ctrl']['sortby'])	{
				$selectFields[] = $keepFields[] = $TCA[$table]['ctrl']['sortby'];
			}
			$selectFields = array_unique($selectFields);

				// Select the two versions:
			$curVersion = t3lib_BEfunc::getRecord($table,$id,implode(',',$selectFields));
			$swapVersion = t3lib_BEfunc::getRecord($table,$swapWith,implode(',',$selectFields));

			if (is_array($curVersion) && is_array($swapVersion))	{
				if (!is_array(t3lib_BEfunc::getRecord($table,-$id,'uid')))	{

						// Add "keepfields"
					$swapVerBaseArray = array();
					foreach($keepFields as $fN)	{
						$swapVerBaseArray[$fN] = $curVersion[$fN];
					}
#debug($swapVerBaseArray);
						// Check if the swapWith record really IS a version of the original!
					if ($swapVersion['pid']==-1 && $swapVersion['t3ver_oid']==$id)	{
#debug($curVersion,'$curVersion');
#debug($swapVersion,'$swapVersion');
						$sqlErrors=array();

							// Step 1:
						$sArray = array();
						$sArray['uid'] = -intval($id);
						$sArray['t3ver_oid'] = intval($swapWith);
						$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table,'uid='.intval($id),$sArray);
						if ($GLOBALS['TYPO3_DB']->sql_error())	$sqlErrors[]=$GLOBALS['TYPO3_DB']->sql_error();

							// Step 2:
						$sArray = $swapVerBaseArray;
						$sArray['uid'] = intval($id);
						$sArray['t3ver_oid'] = intval($id);
						$sArray['pid'] = intval($curVersion['pid']);
						$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table,'uid='.intval($swapWith),$sArray);
						if ($GLOBALS['TYPO3_DB']->sql_error())	$sqlErrors[]=$GLOBALS['TYPO3_DB']->sql_error();

							// Step 3:
						$sArray = array();
						$sArray['uid'] = intval($swapWith);
						$sArray['t3ver_oid'] = intval($id);
						$sArray['pid'] = -1;
						$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table,'uid=-'.intval($id),$sArray);
						if ($GLOBALS['TYPO3_DB']->sql_error())	$sqlErrors[]=$GLOBALS['TYPO3_DB']->sql_error();

						if (!count($sqlErrors))	{
							$this->log($table,$id,0,0,0,'Swapping successful for table "'.$table.'" uid '.$id.'=>'.$swapWith);

								// SWAPPING pids for subrecords:
							if ($table=='pages' && $swapContent)	{

								// Collect table names that should be copied along with the tables:
								foreach($TCA as $tN => $tCfg)	{
									if ($TCA[$tN]['ctrl']['versioning_followPages'] || ($tN=='pages' && $swapContent==='ALL'))	{		// THIS produces the problem that some records might be left inside a versionized branch. Question is; Should ALL records swap pids, not only the versioning_followPages ones?
										$temporaryPid = -($id+1000000);

										$GLOBALS['TYPO3_DB']->exec_UPDATEquery($tN,'pid='.intval($id),array('pid'=>$temporaryPid));
										if ($GLOBALS['TYPO3_DB']->sql_error())	$sqlErrors[]=$GLOBALS['TYPO3_DB']->sql_error();

										$GLOBALS['TYPO3_DB']->exec_UPDATEquery($tN,'pid='.intval($swapWith),array('pid'=>$id));
										if ($GLOBALS['TYPO3_DB']->sql_error())	$sqlErrors[]=$GLOBALS['TYPO3_DB']->sql_error();

										$GLOBALS['TYPO3_DB']->exec_UPDATEquery($tN,'pid='.intval($temporaryPid),array('pid'=>$swapWith));
										if ($GLOBALS['TYPO3_DB']->sql_error())	$sqlErrors[]=$GLOBALS['TYPO3_DB']->sql_error();

										if (count($sqlErrors))	{
											$this->log($table,$id,0,0,1,'During Swapping: SQL errors happend: '.implode('; ',$sqlErrors));
										}
									}
								}
							}
								// Clear cache:
							$this->clear_cache($table,$id);

						} else $this->log($table,$id,0,0,1,'During Swapping: SQL errors happend: '.implode('; ',$sqlErrors));
					} else $this->log($table,$id,0,0,1,'In swap version, either pid was not -1 or the t3ver_oid didn\'t match the id of the online version as it must!');
				} else $this->log($table,$id,0,0,1,'Error: A record with a negative UID existed - that indicates some inconsistency in the database from prior versioning actions!');
			} else $this->log($table,$id,0,0,1,'Error: Either online or swap version could not be selected!');
		} else $this->log($table,$id,0,0,1,'Error: You cannot swap versions for a record you do not have access to edit!');
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
			$addW =  !$this->admin ? ' AND '.$this->BE_USER->getPagePermsClause($this->pMap['show']) : '';
	 		$mres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'pages', 'pid='.intval($pid).$this->deleteClause('pages').$addW, '', 'sorting DESC');
	 		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($mres))	{
				if ($row['uid']!=$rootID)	{
		 			$CPtable[$row['uid']] = $pid;
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
			reset ($TCA[$table]['columns']);
			$curData=$this->recordInfo($table,$uid,'*');
			$newData=array();
			while (list($field,$conf)=each($TCA[$table]['columns']))	{
				if ($conf['config']['type']=='input')	{
					$evalCodesArray = t3lib_div::trimExplode(',',$conf['config']['eval'],1);
					if (in_array('uniqueInPid',$evalCodesArray))	{
						$newV = $this->getUnique($table,$field,$curData[$field],$uid,$curData['pid']);
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
		if ($TCA[$table] && $TCA[$table]['ctrl']['copyAfterDuplFields'])	{
			t3lib_div::loadTCA($table);
			$prevData=$this->recordInfo($table,$prevUid,'*');
			$theFields = t3lib_div::trimExplode(',',$TCA[$table]['ctrl']['copyAfterDuplFields'],1);
			reset($theFields);
			while(list(,$field)=each($theFields))	{
				if ($TCA[$table]['columns'][$field] && ($update || !isset($newData[$field])))	{
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
		if ($TCA[$table]['columns'])	{
			reset($TCA[$table]['columns']);
			while (list($field,$configArr)=each($TCA[$table]['columns']))	{
				if ($configArr['config']['type']=='group' && $configArr['config']['internal_type']=='file')	{
					$listArr[]=$field;
				}
			}
		}
		return $listArr;
	}

	/**
	 * Get copy header
	 *
	 * @param	string		Table name
	 * @param	integer		PID value in which other records to test might be
	 * @param	string		Field name to get header value for.
	 * @param	string		Current field value
	 * @param	integer		Counter (number of recursions)
	 * @param	string		Previous title we checked for (in previous recursion)
	 * @return	string		The field value, possibly appended with a "copy label"
	 */
	function getCopyHeader($table,$pid,$field,$value,$count,$prevTitle='')	{
		global $TCA;

			// Set title value to check for:
		if ($count)	{
			$checkTitle = $value.rtrim(' '.sprintf($this->prependLabel($table),$count));
		}	else {
			$checkTitle = $value;
		}

			// Do check:
		if ($prevTitle != $checkTitle || $count<100)	{
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', $table, 'pid='.intval($pid).' AND '.$field.'='.$GLOBALS['TYPO3_DB']->fullQuoteStr($checkTitle, $table).$this->deleteClause($table), '', '', '1');
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($res))	{
				return $this->getCopyHeader($table,$pid,$field,$value,$count+1,$checkTitle);
			}
		}

			// Default is to just return the current input title if no other was returned before:
		return $checkTitle;
	}

	/**
	 * Return "copy" label for a table. Although the name is "prepend" it actually APPENDs the label (after ...)
	 *
	 * @param	string		Table name
	 * @return	string		Label to append, containing "%s" for the number
	 * @see getCopyHeader()
	 */
	function prependLabel($table)	{
		global $TCA;
		if (is_object($GLOBALS['LANG']))	{
			$label = $GLOBALS['LANG']->sL($TCA[$table]['ctrl']['prependAtCopy']);
		} else {
			list($label) = explode('|',$TCA[$table]['ctrl']['prependAtCopy']);
		}
		return $label;
	}

	/**
	 * Get the final pid based on $table and $pid ($destPid type... pos/neg)
	 *
	 * @param	string		Table name
	 * @param	integer		"Destination pid" : If the value is >= 0 it's just returned directly (through intval() though) but if the value is <0 then the method looks up the record with the uid equal to abs($pid) (positive number) and returns the PID of that record! The idea is that negative numbers point to the record AFTER WHICH the position is supposed to be!
	 * @return	integer
	 */
	function resolvePid($table,$pid)	{
		global $TCA;
		$pid=intval($pid);
		if ($pid < 0)	{
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('pid', $table, 'uid='.abs($pid));
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			$pid = intval($row['pid']);
		}
		return $pid;
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
		$regex = sprintf(quotemeta($this->prependLabel($table)),'[0-9]*').'$';
		return @ereg_replace($regex,'',$value);
	}

	/**
	 * Processes the fields with references as registered during the copy process. This includes all FlexForm fields which had references.
	 *
	 * @return	void
	 */
	function remapListedDBRecords()	{
		global $TCA;
#debug($this->registerDBList);
#debug($this->copyMappingArray_merged);
		if (count($this->registerDBList))	{
			reset($this->registerDBList);
			while(list($table,$records)=each($this->registerDBList))	{
				t3lib_div::loadTCA($table);
				reset($records);
				while(list($uid,$fields)=each($records))	{
					$newData = array();
					$theUidToUpdate = $this->copyMappingArray_merged[$table][$uid];

					foreach($fields as $fieldName => $value)	{
						$conf = $TCA[$table]['columns'][$fieldName]['config'];

						switch($conf['type'])	{
							case 'group':
							case 'select':
								$vArray = $this->remapListedDBRecords_procDBRefs($conf, $value, $theUidToUpdate);
								if (is_array($vArray))	{
									$newData[$fieldName] = implode(',',$vArray);
								}
							break;
							case 'flex':
								if ($value=='FlexForm_reference')	{
									$origRecordRow = $this->recordInfo($table,$theUidToUpdate,'*');	// This will fetch the new row for the element

									if (is_array($origRecordRow))	{

											// Get current data structure and value array:
										$dataStructArray = t3lib_BEfunc::getFlexFormDS($conf, $origRecordRow, $table);
										$currentValueArray = t3lib_div::xml2array($origRecordRow[$fieldName]);
#debug($dataStructArray);
#debug($currentValueArray);
#debug($origRecordRow);
#debug($currentValueArray['data']);
											// Do recursive processing of the XML data:
										$currentValueArray['data'] = $this->checkValue_flex_procInData(
													$currentValueArray['data'],
													array(),	// Not used.
													array(),	// Not used.
													$dataStructArray,
													array($table,$theUidToUpdate,$fieldName),	// Parameters.
													'remapListedDBRecords_flexFormCallBack'
												);
#debug($currentValueArray['data']);
											// The return value should be compiled back into XML, ready to insert directly in the field (as we call updateDB() directly later):
										if (is_array($currentValueArray['data']))	{
											$newData[$fieldName] =
												'<?xml version="1.0" encoding="'.$GLOBALS['LANG']->charSet.'" standalone="yes" ?>'.chr(10).
												$this->checkValue_flexArray2Xml($currentValueArray);
										}
									}
								}
							break;
							default:
								debug('Field type should not appear here: '. $conf['type']);
							break;
						}
					}

					if (count($newData))	{	// If any fields were changed, those fields are updated!
						$this->updateDB($table,$theUidToUpdate,$newData);
#debug($this->recordInfo($table,$theUidToUpdate,'*'),'Stored result:');
	//					debug($newData);
					}
				}
			}
		}
	}

	/**
	 * Callback function for traversing the FlexForm structure in relation to creating copied files of file relations inside of flex form structures.
	 *
	 * @param	array		Set of parameters in numeric array: table, uid, field
	 * @param	array		TCA config for field (from Data Structure of course)
	 * @param	string		Field value (from FlexForm XML)
	 * @param	string		Not used
	 * @param	string		Not used
	 * @return	array		Array where the "value" key carries the value.
	 * @see checkValue_flex_procInData_travDS(), remapListedDBRecords()
	 */
	function remapListedDBRecords_flexFormCallBack($pParams, $dsConf, $dataValue, $dataValue_ext1, $dataValue_ext2)	{

			// Extract parameters:
		list($table,$uid,$field)	= $pParams;

			// If references are set for this field, set flag so they can be corrected later:
		if ($this->isReferenceField($dsConf) && strlen($dataValue)) {
			$vArray = $this->remapListedDBRecords_procDBRefs($dsConf, $dataValue, $uid);
			if (is_array($vArray))	{
				$dataValue = implode(',',$vArray);
			}
		}

			// Return
		return array('value' => $dataValue);
	}

	/**
	 * Performs remapping of old UID values to NEW uid values for a DB reference field.
	 *
	 * @param	array		TCA field config
	 * @param	string		Field value
	 * @param	integer		UID of local record (for MM relations - might need to change if support for FlexForms should be done!)
	 * @return	array		Returns array of items ready to implode for field content.
	 * @see remapListedDBRecords()
	 */
	function remapListedDBRecords_procDBRefs($conf, $value, $MM_localUid)	{

			// Initialize variables
		$set = FALSE;	// Will be set true if an upgrade should be done...
		$allowedTables = $conf['type']=='group' ? $conf['allowed'] : $conf['foreign_table'].','.$conf['neg_foreign_table'];		// Allowed tables for references.
		$prependName = $conf['type']=='group' ? $conf['prepend_tname'] : '';	// Table name to prepend the UID
		$dontRemapTables = t3lib_div::trimExplode(',',$conf['dontRemapTablesOnCopy'],1);	// Which tables that should possibly not be remapped

			// Convert value to list of references:
		$dbAnalysis = t3lib_div::makeInstance('t3lib_loadDBGroup');
		$dbAnalysis->registerNonTableValues = ($conf['type']=='select' && $conf['allowNonIdValues']) ? 1 : 0;
		$dbAnalysis->start($value, $allowedTables, $conf['MM'], $MM_localUid);

			// Traverse those references and map IDs:
		foreach($dbAnalysis->itemArray as $k => $v)	{
			$mapID = $this->copyMappingArray_merged[$v['table']][$v['id']];
			if ($mapID && !in_array($v['table'],$dontRemapTables))	{
				$dbAnalysis->itemArray[$k]['id'] = $mapID;
				$set = TRUE;
			}
		}

			// If a change has been done, set the new value(s)
		if ($set)	{
			if ($conf['MM'])	{
				$dbAnalysis->writeMM($conf['MM'], $theUidToUpdate, $prependName);
			} else {
				$vArray = $dbAnalysis->getValueArray($prependName);
				if ($conf['type']=='select')	{
					$vArray = $dbAnalysis->convertPosNeg($vArray, $conf['foreign_table'], $conf['neg_foreign_table']);
				}
				return $vArray;
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
		$uploadFolder = $TCA[$table]['columns'][$field]['config']['uploadfolder'];
		if ($uploadFolder && trim($filelist))	{
			$uploadPath = $this->destPathFromUploadFolder($uploadFolder);
			$fileArray = explode(',',$filelist);
			while (list(,$theFile)=each($fileArray))	{
				$theFile=trim($theFile);
				if ($theFile)	{
					switch($func)	{
						case 'deleteAll':
							if (@is_file($uploadPath.'/'.$theFile))	{
								unlink ($uploadPath.'/'.$theFile);
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
			$deleteRow = $TCA[$table]['ctrl']['delete'];
			if ($noRecordCheck || $this->doesRecordExist($table,$uid,'delete'))	{
				if ($deleteRow)	{
					$updateFields = array(
						$deleteRow => 1
					);

						// If the table is sorted, then the sorting number is set very high
					if ($TCA[$table]['ctrl']['sortby'])	{
						$updateFields[$TCA[$table]['ctrl']['sortby']] = 1000000000;
					}

					$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid='.intval($uid), $updateFields);
				} else {

						// Fetches all fields that holds references to files
					$fileFieldArr = $this->extFileFields($table);
					if (count($fileFieldArr))	{
						$mres = $GLOBALS['TYPO3_DB']->exec_SELECTquery(implode(',',$fileFieldArr), $table, 'uid='.intval($uid));
						if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($mres))	{
							$fArray = $fileFieldArr;

							foreach($fArray as $theField)	{	// MISSING: Support for MM file relations!
								$this->extFileFunctions($table,$theField,$row[$theField],'deleteAll');		// This deletes files that belonged to this record.
							}
						} else {
							$this->log($table,$uid,3,0,100,'Delete: Zero rows in result when trying to read filenames from record which should be deleted');
						}
					}

					$GLOBALS['TYPO3_DB']->exec_DELETEquery($table, 'uid='.intval($uid));
				}

				if (!$GLOBALS['TYPO3_DB']->sql_error())	{
					$this->log($table,$uid,3,0,0,'');
				} else {
					$this->log($table,$uid,3,0,100,$GLOBALS['TYPO3_DB']->sql_error());
				}

				$this->clear_cache($table,$uid);	// clear cache
			} else {
				$this->log($table,$uid,3,0,1,'Attempt to delete record without delete-permissions');
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
		if ($this->doesRecordExist('pages',$uid,'delete'))	{	// If we may at all delete this page
			if ($this->deleteTree)	{
				$brExist = $this->doesBranchExist('',$uid,$this->pMap['delete'],1);	// returns the branch
				if ($brExist != -1)	{	// Checks if we had permissions
					if ($this->noRecordsFromUnallowedTables($brExist.$uid))	{
						$uidArray = explode(',',$brExist);
						while (list(,$listUid)=each($uidArray))	{
							if (trim($listUid))	{
								$this->deleteSpecificPage($listUid);
							}
						}
						$this->deleteSpecificPage($uid);
					} else {
						$this->log('pages',$uid,3,0,1,'Attempt to delete records from disallowed tables');
					}
				} else {
					$this->log('pages',$uid,3,0,1,'Attempt to delete pages in branch without permissions');
				}
			} else {
				$brExist = $this->doesBranchExist('',$uid,$this->pMap['delete'],1);	// returns the branch
				if ($brExist == '')	{	// Checks if branch exists
					if ($this->noRecordsFromUnallowedTables($uid))	{
						$this->deleteSpecificPage($uid);
					} else {
						$this->log('pages',$uid,3,0,1,'Attempt to delete records from disallowed tables');
					}
				} else {
					$this->log('pages',$uid,3,0,1,'Attempt to delete page which has subpages');
				}
			}
		} else {
			$this->log('pages',$uid,3,0,1,'Attempt to delete page without permissions');
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
				if ($table!='pages')	{
					$mres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', $table, 'pid='.intval($uid).$this->deleteClause($table));
					while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($mres))	{
						$this->deleteRecord($table,$row['uid'], 1);
					}
				}
			}
			$this->deleteRecord('pages',$uid, 1);
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
			while (list($table) = each($TCA))	{
				$mres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('count(*)', $table, 'pid IN ('.$inList.')');
				$count = $GLOBALS['TYPO3_DB']->sql_fetch_row($mres);
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
		if ($TCA[$table] && $TCA[$table]['ctrl']['sortby'])	{
			$sortRow = $TCA[$table]['ctrl']['sortby'];
			if ($pid>=0)	{	// Sorting number is in the top
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($sortRow.',pid,uid', $table, 'pid='.intval($pid).$this->deleteClause($table), '', $sortRow.' ASC', '1');		// Fetches the first record under this pid
				if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{	// There was a page
					if ($row['uid']==$uid)	{	// The top record was the record it self, so we return its current sortnumber
						return $row[$sortRow];
					}
					if ($row[$sortRow] < 1) {	// If the pages sortingnumber < 1 we must resort the records under this pid
						$this->resorting($table,$pid,$sortRow,0);
						return $this->sortIntervals;
					} else {
						return floor($row[$sortRow]/2);
					}
				} else {	// No pages, so we choose the default value as sorting-number
					return $this->sortIntervals;
				}
			} else {	// Sorting number is inside the list
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($sortRow.',pid,uid', $table, 'uid='.abs($pid).$this->deleteClause($table));		// Fetches the record which is supposed to be the prev record
				if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{	// There was a record
					if ($row['uid']==$uid)	{	// If the record happends to be it self
						$sortNumber = $row[$sortRow];
					} else {
						$subres = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
										$sortRow.',pid,uid',
										$table,
										'pid='.intval($row['pid']).' AND '.$sortRow.'>='.intval($row[$sortRow]).$this->deleteClause($table),
										'',
										$sortRow.' ASC',
										'2'
									);		// Fetches the next record in order to calculate the in between sortNumber
						if ($GLOBALS['TYPO3_DB']->sql_num_rows($subres)==2)	{	// There was a record afterwards
							$GLOBALS['TYPO3_DB']->sql_fetch_assoc($subres);				// Forward to the second result...
							$subrow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($subres);	// There was a record afterwards
							$sortNumber = $row[$sortRow]+ floor(($subrow[$sortRow]-$row[$sortRow])/2);	// The sortNumber is found in between these values
							if ($sortNumber<=$row[$sortRow] || $sortNumber>=$subrow[$sortRow])	{	// The sortNumber happend NOT to be between the two surrounding numbers, so we'll have to resort the list
								$sortNumber = $this->resorting($table,$row['pid'],$sortRow,  $row['uid']);	// By this special param, resorting reserves and returns the sortnumber after the uid
							}
						} else {	// If after the last record in the list, we just add the sortInterval to the last sortvalue
							$sortNumber = $row[$sortRow]+$this->sortIntervals;
						}
					}
					return Array('pid' => $row['pid'], 'sortNumber' => $sortNumber);
				} else {
					$propArr = $this->getRecordProperties($table,$uid);
					$this->log($table,$uid,4,0,1,"Attempt to move record '%s' (%s) to after a non-existing record (uid=%s)",1,array($propArr['header'],$table.':'.$uid,abs($pid)),$propArr['pid']);	// OK, dont insert $propArr['event_pid'] here...
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
		if ($TCA[$table] && $sortRow && $TCA[$table]['ctrl']['sortby']==$sortRow)	{
			$returnVal = 0;
			$intervals = $this->sortIntervals;
			$i = $intervals*2;

			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', $table, 'pid='.intval($pid).$this->deleteClause($table), '', $sortRow.' ASC');
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$uid=intval($row['uid']);
				if ($uid)	{
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid='.intval($uid), array($sortRow=>$i));
					if ($uid==$return_SortNumber_After_This_Uid)	{		// This is used to return a sortingValue if the list is resorted because of inserting records inside the list and not in the top
						$i = $i+$intervals;
						$returnVal=$i;
					}
				} else {die ('Fatal ERROR!! No Uid at resorting.');}
				$i = $i+$intervals;
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
		return ereg_replace(',$','',$input);
	}

	/**
	 * Converts a HTML entity (like &#123;) to the character '123'
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
		$dest = intval($dest);
		$id = intval($id);
		if ($dest==$id)	{
			return false;
		}
		while ($dest!=0 && $loopCheck>0)	{
			$loopCheck--;
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('pid, uid', 'pages', 'uid='.intval($dest).$this->deleteClause('pages'));
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				if ($row['pid']==$id)	{
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
			while (list($field,$config)=each($TCA[$table]['columns']))	{
				if ($config['exclude'] && !t3lib_div::inList($this->BE_USER->groupData['non_exclude_fields'],$table.':'.$field))	{
					$list[]=$table.'-'.$field;
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
		if (!$page_uid)	{
			return FALSE; 	// Not a number. Probably a new page
		}

		$allowedTableList = isset($PAGES_TYPES[$doktype]['allowedTables']) ? $PAGES_TYPES[$doktype]['allowedTables'] : $PAGES_TYPES['default']['allowedTables'];
		$allowedArray = t3lib_div::trimExplode(',',$allowedTableList,1);
		if (strstr($allowedTableList,'*'))	{	// If all tables is OK the return true
			return FALSE;	// OK...
		}

		reset ($TCA);
		$tableList = array();
		while (list($table)=each($TCA))	{
			if (!in_array($table,$allowedArray))	{	// If the table is not in the allowed list, check if there are records...
				$mres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('count(*)', $table, 'pid='.intval($page_uid));
				$count = $GLOBALS['TYPO3_DB']->sql_fetch_row($mres);
				if ($count[0])	{
					$tableList[]=$table;
				}
			}
		}
		return implode(',',$tableList);
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
		if ($TCA[$table]['ctrl']['delete'])	{
			return ' AND '.$table.'.'.$TCA[$table]['ctrl']['delete'].'=0';
		} else {
			return '';
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
		return ($TCA[$table]['ctrl']['readOnly'] ? 1 : 0);
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
		return ($TCA[$table]['ctrl']['adminOnly'] ? 1 : 0);
	}

	/**
	 * Finds the Position-ID for this page. This is very handy when we need to update a page in the pagetree in the TYPO3 interface.
	 * OBSOLETE WITH the new backend?
	 * Usage: 2 (class t3lib_tcemain)
	 *
	 * @param	[type]		$uid: ...
	 * @return	[type]		...
	 */
	function getInterfacePagePositionID($uid)	{
		global $TCA;
		$perms_clause = $this->BE_USER->getPagePermsClause(1);
		$deleted = $TCA['pages']['ctrl']['delete'] ? 'AND A.'.$TCA['pages']['ctrl']['delete'].'=0 AND pages.'.$TCA['pages']['ctrl']['delete'].'=0 ' : '';

			// This fetches a list of 1 or 2 pages, where - if 2 - the 2nd is the page BEFORE this ($uid). If 1 then the page ($uid) is at the top itself
 		$subres = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'pages.uid, pages.pid',
					'pages A, pages',
					'A.pid=pages.pid AND A.uid=\''.$uid.'\'
						'.$deleted.'
						AND pages.sorting<=A.sorting
						AND '.$perms_clause,
					'',
					'pages.sorting DESC',
					'2'
				);
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($subres)==2)	{	// There was a record before
			$GLOBALS['TYPO3_DB']->sql_fetch_assoc($subres);		// forwards to the second result
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($subres);
			return -$row['uid'];
		} else {
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($subres);
			return $row['pid'];
		}
	}

	/**
	 * Returns true if the TCA/columns field type is a DB reference field
	 *
	 * @param	array		config array for TCA/columns field
	 * @return	boolean		True if DB reference field (group/db or select with foreign-table)
	 */
	function isReferenceField($conf)	{
		return ($conf['type']=='group' && $conf['internal_type']=='db') ||	($conf['type']=='select' && $conf['foreign_table']);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$tscPID: ...
	 * @return	[type]		...
	 */
	function getTCEMAIN_TSconfig($tscPID)	{
		if (!isset($this->cachedTSconfig[$tscPID]))	{
			$this->cachedTSconfig[$tscPID] = $this->BE_USER->getTSConfig('TCEMAIN',t3lib_BEfunc::getPagesTSconfig($tscPID));
		}
		return $this->cachedTSconfig[$tscPID]['properties'];
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$table: ...
	 * @param	[type]		$TSconfig: ...
	 * @return	[type]		...
	 */
	function getTableEntries($table,$TSconfig)	{
		$tA = is_array($TSconfig['table.'][$table.'.']) ? $TSconfig['table.'][$table.'.'] : array();;
		$dA = is_array($TSconfig['default.']) ? $TSconfig['default.'] : array();
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
		if (isset($this->historyRecords[$table.':'.$id]))	{

			list($tscPID) = t3lib_BEfunc::getTSCpid($table,$id,'');
			$TSConfig = $this->getTCEMAIN_TSconfig($tscPID);

			$tE = $this->getTableEntries($table,$TSConfig);
			$keepEntries = strcmp($tE['history.']['keepEntries'],'') ? t3lib_div::intInRange($tE['history.']['keepEntries'],0,200) : 10;
			$maxAgeSeconds = 60*60*24*(strcmp($tE['history.']['maxAgeDays'],'') ? t3lib_div::intInRange($tE['history.']['maxAgeDays'],0,200) : 7);	// one week
			$this->clearHistory($table,$id,t3lib_div::intInRange($keepEntries-1,0),$maxAgeSeconds);

			if ($keepEntries)	{
				$fields_values = array();
				$fields_values['history_data'] = serialize($this->historyRecords[$table.':'.$id]);
				$fields_values['fieldlist'] = implode(',',array_keys($this->historyRecords[$table.':'.$id]['newRecord']));
				$fields_values['tstamp'] = time();
				$fields_values['tablename'] = $table;
				$fields_values['recuid'] = $id;
				$fields_values['sys_log_uid'] = $logId;

				$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_history', $fields_values);
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

		$where = '
			tablename='.$GLOBALS['TYPO3_DB']->fullQuoteStr($table, 'sys_history').'
			AND recuid='.intval($id).'
			AND snapshot=0';

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,tstamp', 'sys_history', $where, '', 'uid DESC', intval($keepEntries).',1');
		$resRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		if ($tstampLimit && intval($resRow['tstamp'])<$tstampLimit)	{
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,tstamp', 'sys_history', $where.' AND tstamp<'.intval($tstampLimit), '', 'uid DESC', '1');
			$resRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);

			$GLOBALS['TYPO3_DB']->exec_DELETEquery('sys_history', $where.' AND uid<='.intval($resRow['uid']));
		} elseif (is_array($resRow)) {
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('sys_history', $where.' AND uid<='.intval($resRow['uid']));
		}
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
	 * $data:		Array with special information that may go into $details by '%s' marks / sprintf() when the log is shown
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
	function log($table,$recuid,$action,$recpid,$error,$details,$details_nr=0,$data=array(),$event_pid=-1,$NEWid='') {
		if ($this->enableLogging)	{
			$type=1;	// Type value for tce_db.php
			if (!$this->storeLogMessages)	{$details='';}
			return $this->BE_USER->writelog($type,$action,$error,$details_nr,$details,$data,$table,$recuid,$recpid,$event_pid,$NEWid);
		}
	}

	/**
	 * Print log error messages from the operations of this script instance
	 *
	 * @param	string		Redirect URL (for creating link in message)
	 * @return	void		(Will exit on error)
	 */
	function printLogErrorMessages($redirect)	{
		$res_log = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'*',
					'sys_log',
					'type=1 AND userid='.intval($this->BE_USER->user['uid']).' AND tstamp='.intval($GLOBALS['EXEC_TIME']).'	AND error!=0'
				);
		$errorJS = array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_log)) {
			$log_data = unserialize($row['log_data']);
			$errorJS[] = $row[error].': '.sprintf($row['details'], $log_data[0],$log_data[1],$log_data[2],$log_data[3],$log_data[4]);
		}

		if (count($errorJS))	{
			$error_doc = t3lib_div::makeInstance('template');
			$error_doc->backPath = '';

			$content.= $error_doc->startPage('tce_db.php Error output');

			$lines[] = '
					<tr class="bgColor5">
						<td colspan="2" align="center"><strong>Errors:</strong></td>
					</tr>';

			foreach($errorJS as $line)	{
				$lines[] = '
					<tr class="bgColor4">
						<td valign="top"><img'.t3lib_iconWorks::skinImg('','gfx/icon_fatalerror.gif','width="18" height="16"').' alt="" /></td>
						<td>'.htmlspecialchars($line).'</td>
					</tr>';
			}

			$lines[] = '
					<tr>
						<td colspan="2" align="center"><br />'.
						'<form action=""><input type="submit" value="Continue" onclick="'.htmlspecialchars('document.location=\''.$redirect.'\';return false;').'"></form>'.
						'</td>
					</tr>';

			$content.= '
				<br/><br/>
				<table border="0" cellpadding="1" cellspacing="1" width="300" align="center">
					'.implode('',$lines).'
				</table>';

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
			case 'pages':
				if ($this->admin || $this->BE_USER->getTSConfigVal('options.clearCache.pages'))	{
					if (t3lib_extMgm::isLoaded('cms'))	{
						$GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_pages','');
					}
				}
			break;
			case 'all':
				if ($this->admin || $this->BE_USER->getTSConfigVal('options.clearCache.all'))	{
					if (t3lib_extMgm::isLoaded('cms'))	{
						$GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_pages','');
						$GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_pagesection','');
					}
					$GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_hash','');

						// Clearing additional cache tables:
					if (is_array($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearAllCache_additionalTables']))	{
						foreach($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearAllCache_additionalTables'] as $tableName)	{
							if (!ereg('[^[:alnum:]_]',$tableName) && substr($tableName,-5)=='cache')	{
								$GLOBALS['TYPO3_DB']->exec_DELETEquery($tableName,'');
							} else {
								die('Fatal Error: Trying to flush table "'.$tableName.'" with "Clear All Cache"');
							}
						}
					}
				}
			break;
			case 'temp_CACHED':
				if ($this->admin && $TYPO3_CONF_VARS['EXT']['extCache'])	{
					$this->removeCacheFiles();
				}
			break;
		}

			// Clear cache for a page ID!
		if (t3lib_div::testInt($cacheCmd))	{
			if (t3lib_extMgm::isLoaded('cms'))	{

				$list_cache = array($cacheCmd);

					// Call pre-processing function for clearing of cache for page ids:
				if (is_array($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearPageCacheEval']))	{
					foreach($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearPageCacheEval'] as $funcName)	{
						$_params = array('pageIdArray' => &$list_cache, 'cacheCmd' => $cacheCmd, 'functionID' => 'clear_cacheCmd()');
							// Returns the array of ids to clear, false if nothing should be cleared! Never an empty array!
						t3lib_div::callUserFunction($funcName,$_params,$this);
					}
				}

					// Delete cache for selected pages:
				if (is_array($list_cache))	{
					$GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_pages','page_id IN ('.implode(',',$GLOBALS['TYPO3_DB']->cleanIntArray($list_cache)).')');
					$GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_pagesection', 'page_id IN ('.implode(',',$GLOBALS['TYPO3_DB']->cleanIntArray($list_cache)).')');	// Originally, cache_pagesection was not cleared with cache_pages!
				}
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

	/**
	 * Unlink (delete) typo3conf/temp_CACHED_*.php cache files
	 *
	 * @return	integer		The number of files deleted
	 */
	function removeCacheFiles()	{
		$cacheFiles=t3lib_extMgm::currentCacheFiles();
		$out=0;
		if (is_array($cacheFiles))	{
			reset($cacheFiles);
			while(list(,$cfile)=each($cacheFiles))	{
				@unlink($cfile);
				clearstatcache();
				$out++;
			}
		}

		return $out;
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
2-12:	SQL error: '%s' (%s)
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



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_tcemain.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_tcemain.php']);
}
?>
