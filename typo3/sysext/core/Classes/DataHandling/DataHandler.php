<?php
namespace TYPO3\CMS\Core\DataHandling;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * This is the TYPO3 Core Engine class for manipulation of the database
 * This class is used by eg. the tce_db.php script which provides an the interface for POST forms to this class.
 *
 * Dependencies:
 * - $GLOBALS['TCA'] must exist
 * - $GLOBALS['LANG'] must exist
 *
 * tce_db.php for further comments and SYNTAX! Also see document 'TYPO3 Core API' for details.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class DataHandler {

	// *********************
	// Public variables you can configure before using the class:
	// *********************
	// Boolean: If TRUE, the default log-messages will be stored. This should not be necessary if the locallang-file for the log-display is properly configured. So disabling this will just save some database-space as the default messages are not saved.
	/**
	 * @todo Define visibility
	 */
	public $storeLogMessages = TRUE;

	// Boolean: If TRUE, actions are logged to sys_log.
	/**
	 * @todo Define visibility
	 */
	public $enableLogging = TRUE;

	// Boolean: If TRUE, the datamap array is reversed in the order, which is a nice thing if you're creating a whole new bunch of records.
	/**
	 * @todo Define visibility
	 */
	public $reverseOrder = FALSE;

	// Boolean: If TRUE, only fields which are different from the database values are saved! In fact, if a whole input array is similar, it's not saved then.
	/**
	 * @todo Define visibility
	 */
	public $checkSimilar = TRUE;

	// Boolean: If TRUE, incoming values in the data-array have their slashes stripped. ALWAYS SET THIS TO ZERO and supply an unescaped data array instead. This switch may totally disappear in future versions of this class!
	/**
	 * @todo Define visibility
	 */
	public $stripslashes_values = TRUE;

	// Boolean: This will read the record after having updated or inserted it. If anything is not properly submitted an error is written to the log. This feature consumes extra time by selecting records
	/**
	 * @todo Define visibility
	 */
	public $checkStoredRecords = TRUE;

	// Boolean: If set, values '' and 0 will equal each other when the stored records are checked.
	/**
	 * @todo Define visibility
	 */
	public $checkStoredRecords_loose = TRUE;

	// Boolean. If this is set, then a page is deleted by deleting the whole branch under it (user must have deletepermissions to it all). If not set, then the page is deleted ONLY if it has no branch
	/**
	 * @todo Define visibility
	 */
	public $deleteTree = FALSE;

	// Boolean. If set, then the 'hideAtCopy' flag for tables will be ignored.
	/**
	 * @todo Define visibility
	 */
	public $neverHideAtCopy = FALSE;

	// Boolean: If set, then transformations are NOT performed on the input.
	/**
	 * @todo Define visibility
	 */
	public $dontProcessTransformations = FALSE;

	// Boolean: If set, .vDEFbase values are unset in flexforms.
	/**
	 * @todo Define visibility
	 */
	public $clear_flexFormData_vDEFbase = FALSE;

	// Boolean/Mixed: TRUE: (traditional) Updates when record is saved. For flexforms, updates if change is made to the localized value. FALSE: Will not update anything. "FORCE_FFUPD" (string): Like TRUE, but will force update to the FlexForm Field
	/**
	 * @todo Define visibility
	 */
	public $updateModeL10NdiffData = TRUE;

	// Boolean: If TRUE, the translation diff. fields will in fact be reset so that they indicate that all needs to change again! It's meant as the opposite of declaring the record translated.
	/**
	 * @todo Define visibility
	 */
	public $updateModeL10NdiffDataClear = FALSE;

	// Boolean: If TRUE, workspace restrictions are bypassed on edit an create actions (process_datamap()). YOU MUST KNOW what you do if you use this feature!
	/**
	 * @todo Define visibility
	 */
	public $bypassWorkspaceRestrictions = FALSE;

	// Boolean: If TRUE, file handling of attached files (addition, deletion etc) is bypassed - the value is saved straight away. YOU MUST KNOW what you are doing with this feature!
	/**
	 * @todo Define visibility
	 */
	public $bypassFileHandling = FALSE;

	// Boolean: If TRUE, access check, check for deleted etc. for records is bypassed. YOU MUST KNOW what you are doing if you use this feature!
	/**
	 * @todo Define visibility
	 */
	public $bypassAccessCheckForRecords = FALSE;

	// String. Comma-list. This list of tables decides which tables will be copied. If empty then none will. If '*' then all will (that the user has permission to of course)
	/**
	 * @todo Define visibility
	 */
	public $copyWhichTables = '*';

	// Integer. If 0 then branch is NOT copied. If 1 then pages on the 1st level is copied. If 2 then pages on the second level is copied ... and so on
	/**
	 * @todo Define visibility
	 */
	public $copyTree = 0;

	// Array [table][fields]=value: New records are created with default values and you can set this array on the form $defaultValues[$table][$field] = $value to override the default values fetched from TCA. If ->setDefaultsFromUserTS is called UserTSconfig default values will overrule existing values in this array (thus UserTSconfig overrules externally set defaults which overrules TCA defaults)
	/**
	 * @todo Define visibility
	 */
	public $defaultValues = array();

	// Array [table][fields]=value: You can set this array on the form $overrideValues[$table][$field] = $value to override the incoming data. You must set this externally. You must make sure the fields in this array are also found in the table, because it's not checked. All columns can be set by this array!
	/**
	 * @todo Define visibility
	 */
	public $overrideValues = array();

	// Array [filename]=alternative_filename: Use this array to force another name onto a file. Eg. if you set ['/tmp/blablabal'] = 'my_file.txt' and '/tmp/blablabal' is set for a certain file-field, then 'my_file.txt' will be used as the name instead.
	/**
	 * @todo Define visibility
	 */
	public $alternativeFileName = array();

	// Array [filename]=alternative_filepath: Same as alternativeFileName but with relative path to the file
	/**
	 * @todo Define visibility
	 */
	public $alternativeFilePath = array();

	// If entries are set in this array corresponding to fields for update, they are ignored and thus NOT updated. You could set this array from a series of checkboxes with value=0 and hidden fields before the checkbox with 1. Then an empty checkbox will disable the field.
	/**
	 * @todo Define visibility
	 */
	public $data_disableFields = array();

	// Use this array to validate suggested uids for tables by setting [table]:[uid]. This is a dangerous option since it will force the inserted record to have a certain UID. The value just have to be TRUE, but if you set it to "DELETE" it will make sure any record with that UID will be deleted first (raw delete). The option is used for import of T3D files when synchronizing between two mirrored servers. As a security measure this feature is available only for Admin Users (for now)
	/**
	 * @todo Define visibility
	 */
	public $suggestedInsertUids = array();

	// Object. Call back object for flex form traversation. Useful when external classes wants to use the iteration functions inside tcemain for traversing a FlexForm structure.
	/**
	 * @todo Define visibility
	 */
	public $callBackObj;

	// *********************
	// Internal variables (mapping arrays) which can be used (read-only) from outside
	// *********************
	// Contains mapping of auto-versionized records.
	/**
	 * @todo Define visibility
	 */
	public $autoVersionIdMap = array();

	// When new elements are created, this array contains a map between their "NEW..." string IDs and the eventual UID they got when stored in database
	/**
	 * @todo Define visibility
	 */
	public $substNEWwithIDs = array();

	// Like $substNEWwithIDs, but where each old "NEW..." id is mapped to the table it was from.
	/**
	 * @todo Define visibility
	 */
	public $substNEWwithIDs_table = array();

	// Holds the tables and there the ids of newly created child records from IRRE
	/**
	 * @todo Define visibility
	 */
	public $newRelatedIDs = array();

	// This array is the sum of all copying operations in this class. May be READ from outside, thus partly public.
	/**
	 * @todo Define visibility
	 */
	public $copyMappingArray_merged = array();

	// A map between input file name and final destination for files being attached to records.
	/**
	 * @todo Define visibility
	 */
	public $copiedFileMap = array();

	// Contains [table][id][field] of fiels where RTEmagic images was copied. Holds old filename as key and new filename as value.
	/**
	 * @todo Define visibility
	 */
	public $RTEmagic_copyIndex = array();

	// Errors are collected in this variable.
	/**
	 * @todo Define visibility
	 */
	public $errorLog = array();

	// *********************
	// Internal Variables, do not touch.
	// *********************
	// Variables set in init() function:
	/**
	 * The user-object the script uses. If not set from outside, this is set to the current global $BE_USER.
	 *
	 * @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 * @todo Define visibility
	 */
	public $BE_USER;

	// Will be set to uid of be_user executing this script
	/**
	 * @todo Define visibility
	 */
	public $userid;

	// Will be set to username of be_user executing this script
	/**
	 * @todo Define visibility
	 */
	public $username;

	// Will be set if user is admin
	/**
	 * @todo Define visibility
	 */
	public $admin;

	// Can be overridden from $GLOBALS['TYPO3_CONF_VARS']
	/**
	 * @todo Define visibility
	 */
	public $defaultPermissions = array(
		'user' => 'show,edit,delete,new,editcontent',
		'group' => 'show,edit,new,editcontent',
		'everybody' => ''
	);

	// The list of <table>-<fields> that cannot be edited by user. This is compiled from TCA/exclude-flag combined with non_exclude_fields for the user.
	/**
	 * @todo Define visibility
	 */
	public $exclude_array;

	/**
	 * Data submitted from the form view, used to control behaviours,
	 * e.g. this is used to activate/deactive fields and thus store NULL values
	 *
	 * @var array
	 */
	protected $control = array();

	// Set with incoming data array
	/**
	 * @todo Define visibility
	 */
	public $datamap = array();

	// Set with incoming cmd array
	/**
	 * @todo Define visibility
	 */
	public $cmdmap = array();

	// Internal static:
	// Permission mapping
	/**
	 * @todo Define visibility
	 */
	public $pMap = array(
		'show' => 1,
		// 1st bit
		'edit' => 2,
		// 2nd bit
		'delete' => 4,
		// 3rd bit
		'new' => 8,
		// 4th bit
		'editcontent' => 16
	);

	// Integer: The interval between sorting numbers used with tables with a 'sorting' field defined. Min 1
	/**
	 * @todo Define visibility
	 */
	public $sortIntervals = 256;

	// Internal caching arrays
	// Used by function checkRecordUpdateAccess() to store whether a record is updateable or not.
	/**
	 * @todo Define visibility
	 */
	public $recUpdateAccessCache = array();

	// User by function checkRecordInsertAccess() to store whether a record can be inserted on a page id
	/**
	 * @todo Define visibility
	 */
	public $recInsertAccessCache = array();

	// Caching array for check of whether records are in a webmount
	/**
	 * @todo Define visibility
	 */
	public $isRecordInWebMount_Cache = array();

	// Caching array for page ids in webmounts
	/**
	 * @todo Define visibility
	 */
	public $isInWebMount_Cache = array();

	// Caching for collecting TSconfig for page ids
	/**
	 * @todo Define visibility
	 */
	public $cachedTSconfig = array();

	// Used for caching page records in pageInfo()
	/**
	 * @todo Define visibility
	 */
	public $pageCache = array();

	// Array caching workspace access for BE_USER
	/**
	 * @todo Define visibility
	 */
	public $checkWorkspaceCache = array();

	// Other arrays:
	// For accumulation of MM relations that must be written after new records are created.
	/**
	 * @todo Define visibility
	 */
	public $dbAnalysisStore = array();

	// For accumulation of files which must be deleted after processing of all input content
	/**
	 * @todo Define visibility
	 */
	public $removeFilesStore = array();

	// Uploaded files, set by process_uploads()
	/**
	 * @todo Define visibility
	 */
	public $uploadedFileArray = array();

	// Used for tracking references that might need correction after operations
	/**
	 * @todo Define visibility
	 */
	public $registerDBList = array();

	// Used for tracking references that might need correction in pid field after operations (e.g. IRRE)
	/**
	 * @todo Define visibility
	 */
	public $registerDBPids = array();

	// Used by the copy action to track the ids of new pages so subpages are correctly inserted! THIS is internally cleared for each executed copy operation! DO NOT USE THIS FROM OUTSIDE! Read from copyMappingArray_merged instead which is accumulating this information.
	/**
	 * @todo Define visibility
	 */
	public $copyMappingArray = array();

	// array used for remapping uids and values at the end of process_datamap
	/**
	 * @todo Define visibility
	 */
	public $remapStack = array();

	// array used for remapping uids and values at the end of process_datamap (e.g. $remapStackRecords[<table>][<uid>] = <index in $remapStack>)
	/**
	 * @todo Define visibility
	 */
	public $remapStackRecords = array();

	// array used for checking whether new children need to be remapped
	protected $remapStackChildIds = array();

	// array used for executing addition actions after remapping happened (sett processRemapStack())
	protected $remapStackActions = array();

	// array used for executing post-processing on the reference index
	protected $remapStackRefIndex = array();

	// array used for additional calls to $this->updateRefIndex
	/**
	 * @todo Define visibility
	 */
	public $updateRefIndexStack = array();

	// tells, that this TCEmain was called from tx_impext - this variable is set by tx_impexp
	/**
	 * @todo Define visibility
	 */
	public $callFromImpExp = FALSE;

	// Array for new flexform index mapping
	/**
	 * @todo Define visibility
	 */
	public $newIndexMap = array();

	// Various
	/**
	 * basicFileFunctions object
	 * For "singleTon" file-manipulation object
	 *
	 * @var \TYPO3\CMS\Core\Utility\File\BasicFileUtility
	 * @todo Define visibility
	 */
	public $fileFunc;

	// Set to "currentRecord" during checking of values.
	/**
	 * @todo Define visibility
	 */
	public $checkValue_currentRecord = array();

	// A signal flag used to tell file processing that autoversioning has happend and hence certain action should be applied.
	/**
	 * @todo Define visibility
	 */
	public $autoVersioningUpdate = FALSE;

	// Disable delete clause
	protected $disableDeleteClause = FALSE;

	protected $checkModifyAccessListHookObjects;

	protected $version_remapMMForVersionSwap_reg;

	/**
	 * The outer most instance of \TYPO3\CMS\Core\DataHandling\DataHandler:
	 * This object instantiates itself on versioning and localization ...
	 *
	 * @var \TYPO3\CMS\Core\DataHandling\DataHandler
	 */
	protected $outerMostInstance = NULL;

	/**
	 * @param array $control
	 */
	public function setControl(array $control) {
		$this->control = $control;
	}

	/**
	 * Initializing.
	 * For details, see 'TYPO3 Core API' document.
	 * This function does not start the processing of data, but merely initializes the object
	 *
	 * @param array $data Data to be modified or inserted in the database
	 * @param array $cmd Commands to copy, move, delete, localize, versionize records.
	 * @param object $altUserObject An alternative userobject you can set instead of the default, which is $GLOBALS['BE_USER']
	 * @return void
	 */
	public function start($data, $cmd, $altUserObject = '') {
		// Initializing BE_USER
		$this->BE_USER = is_object($altUserObject) ? $altUserObject : $GLOBALS['BE_USER'];
		$this->userid = $this->BE_USER->user['uid'];
		$this->username = $this->BE_USER->user['username'];
		$this->admin = $this->BE_USER->user['admin'];
		if ($this->BE_USER->uc['recursiveDelete']) {
			$this->deleteTree = 1;
		}
		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['explicitConfirmationOfTranslation'] && $this->updateModeL10NdiffData === TRUE) {
			$this->updateModeL10NdiffData = FALSE;
		}
		// Initializing default permissions for pages
		$defaultPermissions = $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPermissions'];
		if (isset($defaultPermissions['user'])) {
			$this->defaultPermissions['user'] = $defaultPermissions['user'];
		}
		if (isset($defaultPermissions['group'])) {
			$this->defaultPermissions['group'] = $defaultPermissions['group'];
		}
		if (isset($defaultPermissions['everybody'])) {
			$this->defaultPermissions['everybody'] = $defaultPermissions['everybody'];
		}
		// generates the excludelist, based on TCA/exclude-flag and non_exclude_fields for the user:
		$this->exclude_array = $this->admin ? array() : $this->getExcludeListArray();
		// Setting the data and cmd arrays
		if (is_array($data)) {
			reset($data);
			$this->datamap = $data;
		}
		if (is_array($cmd)) {
			reset($cmd);
			$this->cmdmap = $cmd;
		}
	}

	/**
	 * Function that can mirror input values in datamap-array to other uid numbers.
	 * Example: $mirror[table][11] = '22,33' will look for content in $this->datamap[table][11] and copy it to $this->datamap[table][22] and $this->datamap[table][33]
	 *
	 * @param array $mirror This array has the syntax $mirror[table_name][uid] = [list of uids to copy data-value TO!]
	 * @return void
	 * @todo Define visibility
	 */
	public function setMirror($mirror) {
		if (is_array($mirror)) {
			foreach ($mirror as $table => $uid_array) {
				if (isset($this->datamap[$table])) {
					foreach ($uid_array as $id => $uidList) {
						if (isset($this->datamap[$table][$id])) {
							$theIdsInArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $uidList, 1);
							foreach ($theIdsInArray as $copyToUid) {
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
	 * @param array $userTS User TSconfig array
	 * @return void
	 * @todo Define visibility
	 */
	public function setDefaultsFromUserTS($userTS) {
		if (is_array($userTS)) {
			foreach ($userTS as $k => $v) {
				$k = substr($k, 0, -1);
				if ($k && is_array($v) && isset($GLOBALS['TCA'][$k])) {
					if (is_array($this->defaultValues[$k])) {
						$this->defaultValues[$k] = array_merge($this->defaultValues[$k], $v);
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
	 * @param array $postFiles $_FILES array
	 * @return void
	 * @todo Define visibility
	 */
	public function process_uploads($postFiles) {
		if (is_array($postFiles)) {
			// Editing frozen:
			if ($this->BE_USER->workspace !== 0 && $this->BE_USER->workspaceRec['freeze']) {
				$this->newlog('All editing in this workspace has been frozen!', 1);
				return FALSE;
			}
			$subA = reset($postFiles);
			if (is_array($subA)) {
				if (is_array($subA['name']) && is_array($subA['type']) && is_array($subA['tmp_name']) && is_array($subA['size'])) {
					// Initialize the uploadedFilesArray:
					$this->uploadedFileArray = array();
					// For each entry:
					foreach ($subA as $key => $values) {
						$this->process_uploads_traverseArray($this->uploadedFileArray, $values, $key);
					}
				} else {
					$this->uploadedFileArray = $subA;
				}
			}
		}
	}

	/**
	 * Traverse the upload array if needed to rearrange values.
	 *
	 * @param array $outputArr $this->uploadedFileArray passed by reference
	 * @param array $inputArr Input array  ($_FILES parts)
	 * @param string $keyToSet The current $_FILES array key to set on the outermost level.
	 * @return void
	 * @access private
	 * @see process_uploads()
	 * @todo Define visibility
	 */
	public function process_uploads_traverseArray(&$outputArr, $inputArr, $keyToSet) {
		if (is_array($inputArr)) {
			foreach ($inputArr as $key => $value) {
				$this->process_uploads_traverseArray($outputArr[$key], $inputArr[$key], $keyToSet);
			}
		} else {
			$outputArr[$keyToSet] = $inputArr;
		}
	}

	/*********************************************
	 *
	 * HOOKS
	 *
	 *********************************************/
	/**
	 * Hook: processDatamap_afterDatabaseOperations
	 * (calls $hookObj->processDatamap_afterDatabaseOperations($status, $table, $id, $fieldArray, $this);)
	 *
	 * Note: When using the hook after INSERT operations, you will only get the temporary NEW... id passed to your hook as $id,
	 * but you can easily translate it to the real uid of the inserted record using the $this->substNEWwithIDs array.
	 *
	 * @param object $hookObjectsArr (reference) Array with hook objects
	 * @param string $status (reference) Status of the current operation, 'new' or 'update
	 * @param string $table (refrence) The table currently processing data for
	 * @param string $id (reference) The record uid currently processing data for, [integer] or [string] (like 'NEW...')
	 * @param array $fieldArray (reference) The field array of a record
	 * @return void
	 * @todo Define visibility
	 */
	public function hook_processDatamap_afterDatabaseOperations(&$hookObjectsArr, &$status, &$table, &$id, &$fieldArray) {
		// Process hook directly:
		if (!isset($this->remapStackRecords[$table][$id])) {
			foreach ($hookObjectsArr as $hookObj) {
				if (method_exists($hookObj, 'processDatamap_afterDatabaseOperations')) {
					$hookObj->processDatamap_afterDatabaseOperations($status, $table, $id, $fieldArray, $this);
				}
			}
		} else {
			$this->remapStackRecords[$table][$id]['processDatamap_afterDatabaseOperations'] = array(
				'status' => $status,
				'fieldArray' => $fieldArray,
				'hookObjectsArr' => $hookObjectsArr
			);
		}
	}

	/**
	 * Gets the 'checkModifyAccessList' hook objects.
	 * The first call initializes the accordant objects.
	 *
	 * @return array The 'checkModifyAccessList' hook objects (if any)
	 */
	protected function getCheckModifyAccessListHookObjects() {
		if (!isset($this->checkModifyAccessListHookObjects)) {
			$this->checkModifyAccessListHookObjects = array();
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['checkModifyAccessList'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['checkModifyAccessList'] as $classData) {
					$hookObject = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classData);
					if (!$hookObject instanceof \TYPO3\CMS\Core\DataHandling\DataHandlerCheckModifyAccessListHookInterface) {
						throw new \UnexpectedValueException('$hookObject must implement interface \\TYPO3\\CMS\\Core\\DataHandling\\DataHandlerCheckModifyAccessListHookInterface', 1251892472);
					}
					$this->checkModifyAccessListHookObjects[] = $hookObject;
				}
			}
		}
		return $this->checkModifyAccessListHookObjects;
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
	 * @return void
	 */
	public function process_datamap() {
		$this->controlActiveElements();

		// Keep versionized(!) relations here locally:
		$registerDBList = array();
		$this->registerElementsToBeDeleted();
		$this->datamap = $this->unsetElementsToBeDeleted($this->datamap);
		// Editing frozen:
		if ($this->BE_USER->workspace !== 0 && $this->BE_USER->workspaceRec['freeze']) {
			$this->newlog('All editing in this workspace has been frozen!', 1);
			return FALSE;
		}
		// First prepare user defined objects (if any) for hooks which extend this function:
		$hookObjectsArr = array();
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'] as $classRef) {
				$hookObject = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
				if (method_exists($hookObject, 'processDatamap_beforeStart')) {
					$hookObject->processDatamap_beforeStart($this);
				}
				$hookObjectsArr[] = $hookObject;
			}
		}
		// Organize tables so that the pages-table is always processed first. This is required if you want to make sure that content pointing to a new page will be created.
		$orderOfTables = array();
		// Set pages first.
		if (isset($this->datamap['pages'])) {
			$orderOfTables[] = 'pages';
		}
		$orderOfTables = array_unique(array_merge($orderOfTables, array_keys($this->datamap)));
		// Process the tables...
		foreach ($orderOfTables as $table) {
			// Check if
			//	   - table is set in $GLOBALS['TCA'],
			//	   - table is NOT readOnly
			//	   - the table is set with content in the data-array (if not, there's nothing to process...)
			//	   - permissions for tableaccess OK
			$modifyAccessList = $this->checkModifyAccessList($table);
			if (!$modifyAccessList) {
				$id = 0;
				$this->log($table, $id, 2, 0, 1, 'Attempt to modify table \'%s\' without permission', 1, array($table));
			}
			if (isset($GLOBALS['TCA'][$table]) && !$this->tableReadOnly($table) && is_array($this->datamap[$table]) && $modifyAccessList) {
				if ($this->reverseOrder) {
					$this->datamap[$table] = array_reverse($this->datamap[$table], 1);
				}
				// For each record from the table, do:
				// $id is the record uid, may be a string if new records...
				// $incomingFieldArray is the array of fields
				foreach ($this->datamap[$table] as $id => $incomingFieldArray) {
					if (is_array($incomingFieldArray)) {
						// Handle native date/time fields
						$dateTimeFormats = $GLOBALS['TYPO3_DB']->getDateTimeFormats($table);
						foreach ($GLOBALS['TCA'][$table]['columns'] as $column => $config) {
							if (isset($incomingFieldArray[$column])) {
								if (isset($config['config']['dbType']) && \TYPO3\CMS\Core\Utility\GeneralUtility::inList('date,datetime', $config['config']['dbType'])) {
									$emptyValue = $dateTimeFormats[$config['config']['dbType']]['empty'];
									$format = $dateTimeFormats[$config['config']['dbType']]['format'];
									$incomingFieldArray[$column] = $incomingFieldArray[$column] ? date($format, $incomingFieldArray[$column]) : $emptyValue;
								}
							}
						}
						// Hook: processDatamap_preProcessFieldArray
						foreach ($hookObjectsArr as $hookObj) {
							if (method_exists($hookObj, 'processDatamap_preProcessFieldArray')) {
								$hookObj->processDatamap_preProcessFieldArray($incomingFieldArray, $table, $id, $this);
							}
						}
						// ******************************
						// Checking access to the record
						// ******************************
						$createNewVersion = FALSE;
						$recordAccess = FALSE;
						$old_pid_value = '';
						$this->autoVersioningUpdate = FALSE;
						// Is it a new record? (Then Id is a string)
						if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($id)) {
							// Get a fieldArray with default values
							$fieldArray = $this->newFieldArray($table);
							// A pid must be set for new records.
							if (isset($incomingFieldArray['pid'])) {
								// $value = the pid
								$pid_value = $incomingFieldArray['pid'];
								// Checking and finding numerical pid, it may be a string-reference to another value
								$OK = 1;
								// If a NEW... id
								if (strstr($pid_value, 'NEW')) {
									if (substr($pid_value, 0, 1) == '-') {
										$negFlag = -1;
										$pid_value = substr($pid_value, 1);
									} else {
										$negFlag = 1;
									}
									// Trying to find the correct numerical value as it should be mapped by earlier processing of another new record.
									if (isset($this->substNEWwithIDs[$pid_value])) {
										if ($negFlag === 1) {
											$old_pid_value = $this->substNEWwithIDs[$pid_value];
										}
										$pid_value = intval($negFlag * $this->substNEWwithIDs[$pid_value]);
									} else {
										$OK = 0;
									}
								}
								$pid_value = intval($pid_value);
								// The $pid_value is now the numerical pid at this point
								if ($OK) {
									$sortRow = $GLOBALS['TCA'][$table]['ctrl']['sortby'];
									// Points to a page on which to insert the element, possibly in the top of the page
									if ($pid_value >= 0) {
										// If this table is sorted we better find the top sorting number
										if ($sortRow) {
											$fieldArray[$sortRow] = $this->getSortNumber($table, 0, $pid_value);
										}
										// The numerical pid is inserted in the data array
										$fieldArray['pid'] = $pid_value;
									} else {
										// points to another record before ifself
										// If this table is sorted we better find the top sorting number
										if ($sortRow) {
											// Because $pid_value is < 0, getSortNumber returns an array
											$tempArray = $this->getSortNumber($table, 0, $pid_value);
											$fieldArray['pid'] = $tempArray['pid'];
											$fieldArray[$sortRow] = $tempArray['sortNumber'];
										} else {
											// Here we fetch the PID of the record that we point to...
											$tempdata = $this->recordInfo($table, abs($pid_value), 'pid');
											$fieldArray['pid'] = $tempdata['pid'];
										}
									}
								}
							}
							$theRealPid = $fieldArray['pid'];
							// Now, check if we may insert records on this pid.
							if ($theRealPid >= 0) {
								// Checks if records can be inserted on this $pid.
								$recordAccess = $this->checkRecordInsertAccess($table, $theRealPid);
								if ($recordAccess) {
									$this->addDefaultPermittedLanguageIfNotSet($table, $incomingFieldArray);
									$recordAccess = $this->BE_USER->recordEditAccessInternals($table, $incomingFieldArray, TRUE);
									if (!$recordAccess) {
										$this->newlog('recordEditAccessInternals() check failed. [' . $this->BE_USER->errorMsg . ']', 1);
									} elseif (!$this->bypassWorkspaceRestrictions) {
										// Workspace related processing:
										// If LIVE records cannot be created in the current PID due to workspace restrictions, prepare creation of placeholder-record
										if ($res = $this->BE_USER->workspaceAllowLiveRecordsInPID($theRealPid, $table)) {
											if ($res < 0) {
												$recordAccess = FALSE;
												$this->newlog('Stage for versioning root point and users access level did not allow for editing', 1);
											}
										} else {
											// So, if no live records were allowed, we have to create a new version of this record:
											if ($GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
												$createNewVersion = TRUE;
											} else {
												$recordAccess = FALSE;
												$this->newlog('Record could not be created in this workspace in this branch', 1);
											}
										}
									}
								}
							} else {
								debug('Internal ERROR: pid should not be less than zero!');
							}
							// Yes new record, change $record_status to 'insert'
							$status = 'new';
						} else {
							// Nope... $id is a number
							$fieldArray = array();
							$recordAccess = $this->checkRecordUpdateAccess($table, $id, $incomingFieldArray, $hookObjectsArr);
							if (!$recordAccess) {
								$propArr = $this->getRecordProperties($table, $id);
								$this->log($table, $id, 2, 0, 1, 'Attempt to modify record \'%s\' (%s) without permission. Or non-existing page.', 2, array($propArr['header'], $table . ':' . $id), $propArr['event_pid']);
							} else {
								// Next check of the record permissions (internals)
								$recordAccess = $this->BE_USER->recordEditAccessInternals($table, $id);
								if (!$recordAccess) {
									$propArr = $this->getRecordProperties($table, $id);
									$this->newlog('recordEditAccessInternals() check failed. [' . $this->BE_USER->errorMsg . ']', 1);
								} else {
									// Here we fetch the PID of the record that we point to...
									$tempdata = $this->recordInfo($table, $id, 'pid' . ($GLOBALS['TCA'][$table]['ctrl']['versioningWS'] ? ',t3ver_wsid,t3ver_stage' : ''));
									$theRealPid = $tempdata['pid'];
									// Use the new id of the versionized record we're trying to write to:
									// (This record is a child record of a parent and has already been versionized.)
									if ($this->autoVersionIdMap[$table][$id]) {
										// For the reason that creating a new version of this record, automatically
										// created related child records (e.g. "IRRE"), update the accordant field:
										$this->getVersionizedIncomingFieldArray($table, $id, $incomingFieldArray, $registerDBList);
										// Use the new id of the copied/versionized record:
										$id = $this->autoVersionIdMap[$table][$id];
										$recordAccess = TRUE;
										$this->autoVersioningUpdate = TRUE;
									} elseif (!$this->bypassWorkspaceRestrictions && ($errorCode = $this->BE_USER->workspaceCannotEditRecord($table, $tempdata))) {
										$recordAccess = FALSE;
										// Versioning is required and it must be offline version!
										// Check if there already is a workspace version
										$WSversion = \TYPO3\CMS\Backend\Utility\BackendUtility::getWorkspaceVersionOfRecord($this->BE_USER->workspace, $table, $id, 'uid,t3ver_oid');
										if ($WSversion) {
											$id = $WSversion['uid'];
											$recordAccess = TRUE;
										} elseif ($this->BE_USER->workspaceAllowAutoCreation($table, $id, $theRealPid)) {
											$tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
											/** @var $tce \TYPO3\CMS\Core\DataHandling\DataHandler */
											$tce->stripslashes_values = 0;
											// Setting up command for creating a new version of the record:
											$cmd = array();
											$cmd[$table][$id]['version'] = array(
												'action' => 'new',
												'treeLevels' => -1,
												// Default is to create a version of the individual records... element versioning that is.
												'label' => 'Auto-created for WS #' . $this->BE_USER->workspace
											);
											$tce->start(array(), $cmd);
											$tce->process_cmdmap();
											$this->errorLog = array_merge($this->errorLog, $tce->errorLog);
											// If copying was successful, share the new uids (also of related children):
											if ($tce->copyMappingArray[$table][$id]) {
												foreach ($tce->copyMappingArray as $origTable => $origIdArray) {
													foreach ($origIdArray as $origId => $newId) {
														$this->uploadedFileArray[$origTable][$newId] = $this->uploadedFileArray[$origTable][$origId];
														$this->autoVersionIdMap[$origTable][$origId] = $newId;
													}
												}
												$this->RTEmagic_copyIndex = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($this->RTEmagic_copyIndex, $tce->RTEmagic_copyIndex);
												// See where RTEmagic_copyIndex is used inside fillInFieldArray() for more information...
												// Update registerDBList, that holds the copied relations to child records:
												$registerDBList = array_merge($registerDBList, $tce->registerDBList);
												// For the reason that creating a new version of this record, automatically
												// created related child records (e.g. "IRRE"), update the accordant field:
												$this->getVersionizedIncomingFieldArray($table, $id, $incomingFieldArray, $registerDBList);
												// Use the new id of the copied/versionized record:
												$id = $this->autoVersionIdMap[$table][$id];
												$recordAccess = TRUE;
												$this->autoVersioningUpdate = TRUE;
											} else {
												$this->newlog('Could not be edited in offline workspace in the branch where found (failure state: \'' . $errorCode . '\'). Auto-creation of version failed!', 1);
											}
										} else {
											$this->newlog('Could not be edited in offline workspace in the branch where found (failure state: \'' . $errorCode . '\'). Auto-creation of version not allowed in workspace!', 1);
										}
									}
								}
							}
							// The default is 'update'
							$status = 'update';
						}
						// If access was granted above, proceed to create or update record:
						if ($recordAccess) {
							// Here the "pid" is set IF NOT the old pid was a string pointing to a place in the subst-id array.
							list($tscPID) = \TYPO3\CMS\Backend\Utility\BackendUtility::getTSCpid($table, $id, $old_pid_value ? $old_pid_value : $fieldArray['pid']);
							$TSConfig = $this->getTCEMAIN_TSconfig($tscPID);
							if ($status == 'new' && $table == 'pages' && is_array($TSConfig['permissions.'])) {
								$fieldArray = $this->setTSconfigPermissions($fieldArray, $TSConfig['permissions.']);
							}
							if ($createNewVersion) {
								$newVersion_placeholderFieldArray = $fieldArray;
							}
							// Processing of all fields in incomingFieldArray and setting them in $fieldArray
							$fieldArray = $this->fillInFieldArray($table, $id, $fieldArray, $incomingFieldArray, $theRealPid, $status, $tscPID);
							// NOTICE! All manipulation beyond this point bypasses both "excludeFields" AND possible "MM" relations / file uploads to field!
							// Forcing some values unto field array:
							// NOTICE: This overriding is potentially dangerous; permissions per field is not checked!!!
							$fieldArray = $this->overrideFieldArray($table, $fieldArray);
							if ($createNewVersion) {
								$newVersion_placeholderFieldArray = $this->overrideFieldArray($table, $newVersion_placeholderFieldArray);
							}
							// Setting system fields
							if ($status == 'new') {
								if ($GLOBALS['TCA'][$table]['ctrl']['crdate']) {
									$fieldArray[$GLOBALS['TCA'][$table]['ctrl']['crdate']] = $GLOBALS['EXEC_TIME'];
									if ($createNewVersion) {
										$newVersion_placeholderFieldArray[$GLOBALS['TCA'][$table]['ctrl']['crdate']] = $GLOBALS['EXEC_TIME'];
									}
								}
								if ($GLOBALS['TCA'][$table]['ctrl']['cruser_id']) {
									$fieldArray[$GLOBALS['TCA'][$table]['ctrl']['cruser_id']] = $this->userid;
									if ($createNewVersion) {
										$newVersion_placeholderFieldArray[$GLOBALS['TCA'][$table]['ctrl']['cruser_id']] = $this->userid;
									}
								}
							} elseif ($this->checkSimilar) {
								// Removing fields which are equal to the current value:
								$fieldArray = $this->compareFieldArrayWithCurrentAndUnset($table, $id, $fieldArray);
							}
							if ($GLOBALS['TCA'][$table]['ctrl']['tstamp'] && count($fieldArray)) {
								$fieldArray[$GLOBALS['TCA'][$table]['ctrl']['tstamp']] = $GLOBALS['EXEC_TIME'];
								if ($createNewVersion) {
									$newVersion_placeholderFieldArray[$GLOBALS['TCA'][$table]['ctrl']['tstamp']] = $GLOBALS['EXEC_TIME'];
								}
							}
							// Set stage to "Editing" to make sure we restart the workflow
							if ($GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
								$fieldArray['t3ver_stage'] = 0;
							}
							// Hook: processDatamap_postProcessFieldArray
							foreach ($hookObjectsArr as $hookObj) {
								if (method_exists($hookObj, 'processDatamap_postProcessFieldArray')) {
									$hookObj->processDatamap_postProcessFieldArray($status, $table, $id, $fieldArray, $this);
								}
							}
							// Performing insert/update. If fieldArray has been unset by some userfunction (see hook above), don't do anything
							// Kasper: Unsetting the fieldArray is dangerous; MM relations might be saved already and files could have been uploaded that are now "lost"
							if (is_array($fieldArray)) {
								if ($status == 'new') {
									// This creates a new version of the record with online placeholder and offline version
									if ($createNewVersion) {
										$newVersion_placeholderFieldArray['t3ver_label'] = 'INITIAL PLACEHOLDER';
										// Setting placeholder state value for temporary record
										$newVersion_placeholderFieldArray['t3ver_state'] = 1;
										// Setting workspace - only so display of place holders can filter out those from other workspaces.
										$newVersion_placeholderFieldArray['t3ver_wsid'] = $this->BE_USER->workspace;
										$newVersion_placeholderFieldArray[$GLOBALS['TCA'][$table]['ctrl']['label']] = '[PLACEHOLDER, WS#' . $this->BE_USER->workspace . ']';
										// Saving placeholder as 'original'
										$this->insertDB($table, $id, $newVersion_placeholderFieldArray, FALSE);
										// For the actual new offline version, set versioning values to point to placeholder:
										$fieldArray['pid'] = -1;
										$fieldArray['t3ver_oid'] = $this->substNEWwithIDs[$id];
										$fieldArray['t3ver_id'] = 1;
										// Setting placeholder state value for version (so it can know it is currently a new version...)
										$fieldArray['t3ver_state'] = -1;
										$fieldArray['t3ver_label'] = 'First draft version';
										$fieldArray['t3ver_wsid'] = $this->BE_USER->workspace;
										// When inserted, $this->substNEWwithIDs[$id] will be changed to the uid of THIS version and so the interface will pick it up just nice!
										$phShadowId = $this->insertDB($table, $id, $fieldArray, TRUE, 0, TRUE);
										if ($phShadowId) {
											// Processes fields of the placeholder record:
											$this->triggerRemapAction($table, $id, array($this, 'placeholderShadowing'), array($table, $phShadowId));
											// Hold auto-versionized ids of placeholders:
											$this->autoVersionIdMap[$table][$this->substNEWwithIDs[$id]] = $phShadowId;
										}
									} else {
										$this->insertDB($table, $id, $fieldArray, FALSE, $incomingFieldArray['uid']);
									}
								} else {
									$this->updateDB($table, $id, $fieldArray);
									$this->placeholderShadowing($table, $id);
								}
							}
							// Hook: processDatamap_afterDatabaseOperations
							// Note: When using the hook after INSERT operations, you will only get the temporary NEW... id passed to your hook as $id,
							// but you can easily translate it to the real uid of the inserted record using the $this->substNEWwithIDs array.
							$this->hook_processDatamap_afterDatabaseOperations($hookObjectsArr, $status, $table, $id, $fieldArray);
						}
					}
				}
			}
		}
		// Process the stack of relations to remap/correct
		$this->processRemapStack();
		$this->dbAnalysisStoreExec();
		$this->removeRegisteredFiles();
		// Hook: processDatamap_afterAllOperations
		// Note: When this hook gets called, all operations on the submitted data have been finished.
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'processDatamap_afterAllOperations')) {
				$hookObj->processDatamap_afterAllOperations($this);
			}
		}
		if ($this->isOuterMostInstance()) {
			$this->resetElementsToBeDeleted();
		}
	}

	/**
	 * Fix shadowing of data in case we are editing a offline version of a live "New" placeholder record:
	 *
	 * @param string $table Table name
	 * @param integer $id Record uid
	 * @return void
	 * @todo Define visibility
	 */
	public function placeholderShadowing($table, $id) {
		if ($liveRec = \TYPO3\CMS\Backend\Utility\BackendUtility::getLiveVersionOfRecord($table, $id, '*')) {
			if ((int) $liveRec['t3ver_state'] > 0) {
				$justStoredRecord = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($table, $id);
				$newRecord = array();
				$shadowCols = $GLOBALS['TCA'][$table]['ctrl']['shadowColumnsForNewPlaceholders'];
				$shadowCols .= ',' . $GLOBALS['TCA'][$table]['ctrl']['languageField'];
				$shadowCols .= ',' . $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'];
				$shadowCols .= ',' . $GLOBALS['TCA'][$table]['ctrl']['type'];
				$shadowCols .= ',' . $GLOBALS['TCA'][$table]['ctrl']['label'];
				$shadowColumns = array_unique(\TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $shadowCols, 1));
				foreach ($shadowColumns as $fieldName) {
					if (strcmp($justStoredRecord[$fieldName], $liveRec[$fieldName]) && isset($GLOBALS['TCA'][$table]['columns'][$fieldName]) && $fieldName !== 'uid' && $fieldName !== 'pid') {
						$newRecord[$fieldName] = $justStoredRecord[$fieldName];
					}
				}
				if (count($newRecord)) {
					$this->newlog2('Shadowing done on fields <i>' . implode(',', array_keys($newRecord)) . '</i> in placeholder record ' . $table . ':' . $liveRec['uid'] . ' (offline version UID=' . $id . ')', $table, $liveRec['uid'], $liveRec['pid']);
					$this->updateDB($table, $liveRec['uid'], $newRecord);
				}
			}
		}
	}

	/**
	 * Filling in the field array
	 * $this->exclude_array is used to filter fields if needed.
	 *
	 * @param string $table Table name
	 * @param integer $id Record ID
	 * @param array $fieldArray Default values, Preset $fieldArray with 'pid' maybe (pid and uid will be not be overridden anyway)
	 * @param array $incomingFieldArray Is which fields/values you want to set. There are processed and put into $fieldArray if OK
	 * @param integer $realPid The real PID value of the record. For updates, this is just the pid of the record. For new records this is the PID of the page where it is inserted.
	 * @param string $status Is 'new' or 'update'
	 * @param integer $tscPID TSconfig PID
	 * @return array Field Array
	 * @todo Define visibility
	 */
	public function fillInFieldArray($table, $id, $fieldArray, $incomingFieldArray, $realPid, $status, $tscPID) {
		// Initialize:
		$originalLanguageRecord = NULL;
		$originalLanguage_diffStorage = NULL;
		$diffStorageFlag = FALSE;
		// Setting 'currentRecord' and 'checkValueRecord':
		if (strstr($id, 'NEW')) {
			// Must have the 'current' array - not the values after processing below...
			$currentRecord = ($checkValueRecord = $fieldArray);
			// IF $incomingFieldArray is an array, overlay it.
			// The point is that when new records are created as copies with flex type fields there might be a field containing information about which DataStructure to use and without that information the flexforms cannot be correctly processed.... This should be OK since the $checkValueRecord is used by the flexform evaluation only anyways...
			if (is_array($incomingFieldArray) && is_array($checkValueRecord)) {
				$checkValueRecord = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($checkValueRecord, $incomingFieldArray);
			}
		} else {
			// We must use the current values as basis for this!
			$currentRecord = ($checkValueRecord = $this->recordInfo($table, $id, '*'));
			// This is done to make the pid positive for offline versions; Necessary to have diff-view for pages_language_overlay in workspaces.
			\TYPO3\CMS\Backend\Utility\BackendUtility::fixVersioningPid($table, $currentRecord);
			// Get original language record if available:
			if (is_array($currentRecord) && $GLOBALS['TCA'][$table]['ctrl']['transOrigDiffSourceField'] && $GLOBALS['TCA'][$table]['ctrl']['languageField'] && $currentRecord[$GLOBALS['TCA'][$table]['ctrl']['languageField']] > 0 && $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] && intval($currentRecord[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']]) > 0) {
				$lookUpTable = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerTable'] ? $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerTable'] : $table;
				$originalLanguageRecord = $this->recordInfo($lookUpTable, $currentRecord[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']], '*');
				\TYPO3\CMS\Backend\Utility\BackendUtility::workspaceOL($lookUpTable, $originalLanguageRecord);
				$originalLanguage_diffStorage = unserialize($currentRecord[$GLOBALS['TCA'][$table]['ctrl']['transOrigDiffSourceField']]);
			}
		}
		$this->checkValue_currentRecord = $checkValueRecord;
		// In the following all incoming value-fields are tested:
		// - Are the user allowed to change the field?
		// - Is the field uid/pid (which are already set)
		// - perms-fields for pages-table, then do special things...
		// - If the field is nothing of the above and the field is configured in TCA, the fieldvalues are evaluated by ->checkValue
		// If everything is OK, the field is entered into $fieldArray[]
		foreach ($incomingFieldArray as $field => $fieldValue) {
			if (!in_array(($table . '-' . $field), $this->exclude_array) && !$this->data_disableFields[$table][$id][$field]) {
				// The field must be editable.
				// Checking if a value for language can be changed:
				$languageDeny = $GLOBALS['TCA'][$table]['ctrl']['languageField'] && !strcmp($GLOBALS['TCA'][$table]['ctrl']['languageField'], $field) && !$this->BE_USER->checkLanguageAccess($fieldValue);
				if (!$languageDeny) {
					// Stripping slashes - will probably be removed the day $this->stripslashes_values is removed as an option...
					if ($this->stripslashes_values) {
						if (is_array($fieldValue)) {
							\TYPO3\CMS\Core\Utility\GeneralUtility::stripSlashesOnArray($fieldValue);
						} else {
							$fieldValue = stripslashes($fieldValue);
						}
					}
					switch ($field) {
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
						if ($table == 'pages' && ($this->admin || $status == 'new' || $this->pageInfo($id, 'perms_userid') == $this->userid)) {
							$value = intval($fieldValue);
							switch ($field) {
							case 'perms_userid':
								$fieldArray[$field] = $value;
								break;
							case 'perms_groupid':
								$fieldArray[$field] = $value;
								break;
							default:
								if ($value >= 0 && $value < pow(2, 5)) {
									$fieldArray[$field] = $value;
								}
								break;
							}
						}
						break;
					case 't3ver_oid':

					case 't3ver_id':

					case 't3ver_wsid':

					case 't3ver_state':

					case 't3ver_count':

					case 't3ver_stage':

					case 't3ver_tstamp':
						// t3ver_label is not here because it CAN be edited as a regular field!
						break;
					default:
						if (isset($GLOBALS['TCA'][$table]['columns'][$field])) {
							// Evaluating the value
							$res = $this->checkValue($table, $field, $fieldValue, $id, $status, $realPid, $tscPID);
							if (array_key_exists('value', $res)) {
								$fieldArray[$field] = $res['value'];
							}
							// Add the value of the original record to the diff-storage content:
							if ($this->updateModeL10NdiffData && $GLOBALS['TCA'][$table]['ctrl']['transOrigDiffSourceField']) {
								$originalLanguage_diffStorage[$field] = $this->updateModeL10NdiffDataClear ? '' : $originalLanguageRecord[$field];
								$diffStorageFlag = TRUE;
							}
							// If autoversioning is happening we need to perform a nasty hack. The case is parallel to a similar hack inside checkValue_group_select_file().
							// When a copy or version is made of a record, a search is made for any RTEmagic* images in fields having the "images" soft reference parser applied.
							// That should be TRUE for RTE fields. If any are found they are duplicated to new names and the file reference in the bodytext is updated accordingly.
							// However, with auto-versioning the submitted content of the field will just overwrite the corrected values. This leaves a) lost RTEmagic files and b) creates a double reference to the old files.
							// The only solution I can come up with is detecting when auto versioning happens, then see if any RTEmagic images was copied and if so make a stupid string-replace of the content !
							if ($this->autoVersioningUpdate === TRUE) {
								if (is_array($this->RTEmagic_copyIndex[$table][$id][$field])) {
									foreach ($this->RTEmagic_copyIndex[$table][$id][$field] as $oldRTEmagicName => $newRTEmagicName) {
										$fieldArray[$field] = str_replace(' src="' . $oldRTEmagicName . '"', ' src="' . $newRTEmagicName . '"', $fieldArray[$field]);
									}
								}
							}
						} elseif ($GLOBALS['TCA'][$table]['ctrl']['origUid'] === $field) {
							// Allow value for original UID to pass by...
							$fieldArray[$field] = $fieldValue;
						}
						break;
					}
				}
			}
		}
		// Add diff-storage information:
		if ($diffStorageFlag && !isset($fieldArray[$GLOBALS['TCA'][$table]['ctrl']['transOrigDiffSourceField']])) {
			// If the field is set it would probably be because of an undo-operation - in which case we should not update the field of course...
			$fieldArray[$GLOBALS['TCA'][$table]['ctrl']['transOrigDiffSourceField']] = serialize($originalLanguage_diffStorage);
		}
		// Checking for RTE-transformations of fields:
		$types_fieldConfig = \TYPO3\CMS\Backend\Utility\BackendUtility::getTCAtypes($table, $currentRecord);
		$theTypeString = \TYPO3\CMS\Backend\Utility\BackendUtility::getTCAtypeValue($table, $currentRecord);
		if (is_array($types_fieldConfig)) {
			foreach ($types_fieldConfig as $vconf) {
				// Write file configuration:
				// inserted array_merge($currentRecord,$fieldArray) 170502
				$eFile = \TYPO3\CMS\Core\Html\RteHtmlParser::evalWriteFile($vconf['spec']['static_write'], array_merge($currentRecord, $fieldArray));
				// RTE transformations:
				if (!$this->dontProcessTransformations) {
					if (isset($fieldArray[$vconf['field']])) {
						// Look for transformation flag:
						switch ((string) $incomingFieldArray[('_TRANSFORM_' . $vconf['field'])]) {
						case 'RTE':
							$RTEsetup = $this->BE_USER->getTSConfig('RTE', \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($tscPID));
							$thisConfig = \TYPO3\CMS\Backend\Utility\BackendUtility::RTEsetup($RTEsetup['properties'], $table, $vconf['field'], $theTypeString);
							// Set alternative relative path for RTE images/links:
							$RTErelPath = is_array($eFile) ? dirname($eFile['relEditFile']) : '';
							// Get RTE object, draw form and set flag:
							$RTEobj = \TYPO3\CMS\Backend\Utility\BackendUtility::RTEgetObj();
							if (is_object($RTEobj)) {
								$fieldArray[$vconf['field']] = $RTEobj->transformContent('db', $fieldArray[$vconf['field']], $table, $vconf['field'], $currentRecord, $vconf['spec'], $thisConfig, $RTErelPath, $currentRecord['pid']);
							} else {
								debug('NO RTE OBJECT FOUND!');
							}
							break;
						}
					}
				}
				// Write file configuration:
				if (is_array($eFile)) {
					$mixedRec = array_merge($currentRecord, $fieldArray);
					$SW_fileContent = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($eFile['editFile']);
					$parseHTML = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Html\\RteHtmlParser');
					/** @var $parseHTML \TYPO3\CMS\Core\Html\RteHtmlParser */
					$parseHTML->init('', '');
					$eFileMarker = $eFile['markerField'] && trim($mixedRec[$eFile['markerField']]) ? trim($mixedRec[$eFile['markerField']]) : '###TYPO3_STATICFILE_EDIT###';
					// Must replace the marker if present in content!
					$insertContent = str_replace($eFileMarker, '', $mixedRec[$eFile['contentField']]);
					$SW_fileNewContent = $parseHTML->substituteSubpart($SW_fileContent, $eFileMarker, LF . $insertContent . LF, 1, 1);
					\TYPO3\CMS\Core\Utility\GeneralUtility::writeFile($eFile['editFile'], $SW_fileNewContent);
					// Write status:
					if (!strstr($id, 'NEW') && $eFile['statusField']) {
						$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid=' . intval($id), array(
							$eFile['statusField'] => $eFile['relEditFile'] . ' updated ' . date('d-m-Y H:i:s') . ', bytes ' . strlen($mixedRec[$eFile['contentField']])
						));
					}
				} elseif ($eFile && is_string($eFile)) {
					$this->log($table, $id, 2, 0, 1, 'Write-file error: \'%s\'', 13, array($eFile), $realPid);
				}
			}
		}
		// Return fieldArray
		return $fieldArray;
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
	 * @param string $table Table name
	 * @param string $field Field name
	 * @param string $value Value to be evaluated. Notice, this is the INPUT value from the form. The original value (from any existing record) must be manually looked up inside the function if needed - or taken from $currentRecord array.
	 * @param string $id The record-uid, mainly - but not exclusively - used for logging
	 * @param string $status 'update' or 'new' flag
	 * @param integer $realPid The real PID value of the record. For updates, this is just the pid of the record. For new records this is the PID of the page where it is inserted. If $realPid is -1 it means that a new version of the record is being inserted.
	 * @param integer $tscPID tscPID
	 * @return array Returns the evaluated $value as key "value" in this array. Can be checked with isset($res['value']) ...
	 * @todo Define visibility
	 */
	public function checkValue($table, $field, $value, $id, $status, $realPid, $tscPID) {
		// Result array
		$res = array();
		$recFID = $table . ':' . $id . ':' . $field;
		// Processing special case of field pages.doktype
		if (($table === 'pages' || $table === 'pages_language_overlay') && $field === 'doktype') {
			// If the user may not use this specific doktype, we issue a warning
			if (!($this->admin || \TYPO3\CMS\Core\Utility\GeneralUtility::inList($this->BE_USER->groupData['pagetypes_select'], $value))) {
				$propArr = $this->getRecordProperties($table, $id);
				$this->log($table, $id, 5, 0, 1, 'You cannot change the \'doktype\' of page \'%s\' to the desired value.', 1, array($propArr['header']), $propArr['event_pid']);
				return $res;
			}
			if ($status == 'update') {
				// This checks 1) if we should check for disallowed tables and 2) if there are records from disallowed tables on the current page
				$onlyAllowedTables = isset($GLOBALS['PAGES_TYPES'][$value]['onlyAllowedTables']) ? $GLOBALS['PAGES_TYPES'][$value]['onlyAllowedTables'] : $GLOBALS['PAGES_TYPES']['default']['onlyAllowedTables'];
				if ($onlyAllowedTables) {
					$theWrongTables = $this->doesPageHaveUnallowedTables($id, $value);
					if ($theWrongTables) {
						$propArr = $this->getRecordProperties($table, $id);
						$this->log($table, $id, 5, 0, 1, '\'doktype\' of page \'%s\' could not be changed because the page contains records from disallowed tables; %s', 2, array($propArr['header'], $theWrongTables), $propArr['event_pid']);
						return $res;
					}
				}
			}
		}
		// Get current value:
		$curValueRec = $this->recordInfo($table, $id, $field);
		$curValue = $curValueRec[$field];
		// Getting config for the field
		$tcaFieldConf = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
		// Preform processing:
		$res = $this->checkValue_SW($res, $value, $tcaFieldConf, $table, $id, $curValue, $status, $realPid, $recFID, $field, $this->uploadedFileArray[$table][$id][$field], $tscPID);
		return $res;
	}

	/**
	 * Branches out evaluation of a field value based on its type as configured in $GLOBALS['TCA']
	 * Can be called for FlexForm pseudo fields as well, BUT must not have $field set if so.
	 *
	 * @param array $res The result array. The processed value (if any!) is set in the "value" key.
	 * @param string $value The value to set.
	 * @param array $tcaFieldConf Field configuration from $GLOBALS['TCA']
	 * @param string $table Table name
	 * @param integer $id Return UID
	 * @param [type] $curValue
	 * @param [type] $status
	 * @param integer $realPid The real PID value of the record. For updates, this is just the pid of the record. For new records this is the PID of the page where it is inserted. If $realPid is -1 it means that a new version of the record is being inserted.
	 * @param [type] $recFID
	 * @param string $field Field name. Must NOT be set if the call is for a flexform field (since flexforms are not allowed within flexforms).
	 * @param [type] $uploadedFiles
	 * @param [type] $tscPID
	 * @param array $additionalData Additional data to be forwarded to sub-processors
	 * @return array Returns the evaluated $value as key "value" in this array.
	 * @todo Define visibility
	 */
	public function checkValue_SW($res, $value, $tcaFieldConf, $table, $id, $curValue, $status, $realPid, $recFID, $field, $uploadedFiles, $tscPID, array $additionalData = NULL) {
		// Convert to NULL value if defined in TCA
		if ($value === NULL && !empty($tcaFieldConf['eval']) && \TYPO3\CMS\Core\Utility\GeneralUtility::inList($tcaFieldConf['eval'], 'null')) {
			$res = array('value' => NULL);
			return $res;
		}

		$PP = array($table, $id, $curValue, $status, $realPid, $recFID, $tscPID);
		switch ($tcaFieldConf['type']) {
		case 'text':
			$res = $this->checkValue_text($res, $value, $tcaFieldConf, $PP, $field);
			break;
		case 'passthrough':

		case 'user':
			$res['value'] = $value;
			break;
		case 'input':
			$res = $this->checkValue_input($res, $value, $tcaFieldConf, $PP, $field);
			break;
		case 'check':
			$res = $this->checkValue_check($res, $value, $tcaFieldConf, $PP);
			break;
		case 'radio':
			$res = $this->checkValue_radio($res, $value, $tcaFieldConf, $PP);
			break;
		case 'group':

		case 'select':
			$res = $this->checkValue_group_select($res, $value, $tcaFieldConf, $PP, $uploadedFiles, $field);
			break;
		case 'inline':
			$res = $this->checkValue_inline($res, $value, $tcaFieldConf, $PP, $field, $additionalData);
			break;
		case 'flex':
			// FlexForms are only allowed for real fields.
			if ($field) {
				$res = $this->checkValue_flex($res, $value, $tcaFieldConf, $PP, $uploadedFiles, $field);
			}
			break;
		default:
			break;
		}
		return $res;
	}

	/**
	 * Evaluate "text" type values.
	 *
	 * @param array $res The result array. The processed value (if any!) is set in the "value" key.
	 * @param string $value The value to set.
	 * @param array $tcaFieldConf Field configuration from TCA
	 * @param array $PP Additional parameters in a numeric array: $table,$id,$curValue,$status,$realPid,$recFID
	 * @param string $field Field name
	 * @return array Modified $res array
	 * @todo Define visibility
	 */
	public function checkValue_text($res, $value, $tcaFieldConf, $PP, $field = '') {
		$evalCodesArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $tcaFieldConf['eval'], 1);
		$res = $this->checkValue_text_Eval($value, $evalCodesArray, $tcaFieldConf['is_in']);
		return $res;
	}

	/**
	 * Evaluate "input" type values.
	 *
	 * @param array $res The result array. The processed value (if any!) is set in the "value" key.
	 * @param string $value The value to set.
	 * @param array $tcaFieldConf Field configuration from TCA
	 * @param array $PP Additional parameters in a numeric array: $table,$id,$curValue,$status,$realPid,$recFID
	 * @param string $field Field name
	 * @return array Modified $res array
	 * @todo Define visibility
	 */
	public function checkValue_input($res, $value, $tcaFieldConf, $PP, $field = '') {
		list($table, $id, $curValue, $status, $realPid, $recFID) = $PP;
		// Handle native date/time fields
		$dateTimeFormats = $GLOBALS['TYPO3_DB']->getDateTimeFormats($table);
		if (isset($tcaFieldConf['dbType']) && \TYPO3\CMS\Core\Utility\GeneralUtility::inList('date,datetime', $tcaFieldConf['dbType'])) {
			// Convert the date/time into a timestamp for the sake of the checks
			$emptyValue = $dateTimeFormats[$tcaFieldConf['dbType']]['empty'];
			$timeOffset = $tcaFieldConf['dbType'] === 'date' ? 3600 * $GLOBALS['TYPO3_CONF_VARS']['SYS']['serverTimeZone'] : 0;
			$value = $value === $emptyValue ? 0 : strtotime($value) + $timeOffset;
		}
		// Secures the string-length to be less than max.
		if (intval($tcaFieldConf['max']) > 0) {
			$value = $GLOBALS['LANG']->csConvObj->substr($GLOBALS['LANG']->charSet, $value, 0, intval($tcaFieldConf['max']));
		}
		// Checking range of value:
		if ($tcaFieldConf['range'] && $value != $tcaFieldConf['checkbox'] && intval($value) !== intval($tcaFieldConf['default'])) {
			if (isset($tcaFieldConf['range']['upper']) && intval($value) > intval($tcaFieldConf['range']['upper'])) {
				$value = $tcaFieldConf['range']['upper'];
			}
			if (isset($tcaFieldConf['range']['lower']) && intval($value) < intval($tcaFieldConf['range']['lower'])) {
				$value = $tcaFieldConf['range']['lower'];
			}
		}
		// Process evaluation settings:
		$evalCodesArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $tcaFieldConf['eval'], 1);
		$res = $this->checkValue_input_Eval($value, $evalCodesArray, $tcaFieldConf['is_in']);
		// Process UNIQUE settings:
		// Field is NOT set for flexForms - which also means that uniqueInPid and unique is NOT available for flexForm fields! Also getUnique should not be done for versioning and if PID is -1 ($realPid<0) then versioning is happening...
		if ($field && $realPid >= 0) {
			if ($res['value'] && in_array('uniqueInPid', $evalCodesArray)) {
				$res['value'] = $this->getUnique($table, $field, $res['value'], $id, $realPid);
			}
			if ($res['value'] && in_array('unique', $evalCodesArray)) {
				$res['value'] = $this->getUnique($table, $field, $res['value'], $id);
			}
		}
		// Handle native date/time fields
		if (isset($tcaFieldConf['dbType']) && \TYPO3\CMS\Core\Utility\GeneralUtility::inList('date,datetime', $tcaFieldConf['dbType'])) {
			// Convert the timestamp back to a date/time
			$emptyValue = $dateTimeFormats[$tcaFieldConf['dbType']]['empty'];
			$format = $dateTimeFormats[$tcaFieldConf['dbType']]['format'];
			$res['value'] = $res['value'] ? date($format, $res['value']) : $emptyValue;
		}
		return $res;
	}

	/**
	 * Evaluates 'check' type values.
	 *
	 * @param array $res The result array. The processed value (if any!) is set in the 'value' key.
	 * @param string $value The value to set.
	 * @param array $tcaFieldConf Field configuration from TCA
	 * @param array $PP Additional parameters in a numeric array: $table,$id,$curValue,$status,$realPid,$recFID
	 * @return array Modified $res array
	 * @todo Define visibility
	 */
	public function checkValue_check($res, $value, $tcaFieldConf, $PP) {
		list($table, $id, $curValue, $status, $realPid, $recFID) = $PP;
		$itemC = count($tcaFieldConf['items']);
		if (!$itemC) {
			$itemC = 1;
		}
		$maxV = pow(2, $itemC);
		if ($value < 0) {
			$value = 0;
		}
		if ($value > $maxV) {
			$value = $maxV;
		}
		$res['value'] = $value;
		return $res;
	}

	/**
	 * Evaluates 'radio' type values.
	 *
	 * @param array $res The result array. The processed value (if any!) is set in the 'value' key.
	 * @param string $value The value to set.
	 * @param array $tcaFieldConf Field configuration from TCA
	 * @param array $PP Additional parameters in a numeric array: $table,$id,$curValue,$status,$realPid,$recFID
	 * @return array Modified $res array
	 * @todo Define visibility
	 */
	public function checkValue_radio($res, $value, $tcaFieldConf, $PP) {
		list($table, $id, $curValue, $status, $realPid, $recFID) = $PP;
		if (is_array($tcaFieldConf['items'])) {
			foreach ($tcaFieldConf['items'] as $set) {
				if (!strcmp($set[1], $value)) {
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
	 * @param array $res The result array. The processed value (if any!) is set in the 'value' key.
	 * @param string $value The value to set.
	 * @param array $tcaFieldConf Field configuration from TCA
	 * @param array $PP Additional parameters in a numeric array: $table,$id,$curValue,$status,$realPid,$recFID
	 * @param array $uploadedFiles
	 * @param string $field Field name
	 * @return array Modified $res array
	 * @todo Define visibility
	 */
	public function checkValue_group_select($res, $value, $tcaFieldConf, $PP, $uploadedFiles, $field) {
		list($table, $id, $curValue, $status, $realPid, $recFID) = $PP;
		// Detecting if value sent is an array and if so, implode it around a comma:
		if (is_array($value)) {
			$value = implode(',', $value);
		}
		// This converts all occurencies of '&#123;' to the byte 123 in the string - this is needed in very rare cases where filenames with special characters (like ???, umlaud etc) gets sent to the server as HTML entities instead of bytes. The error is done only by MSIE, not Mozilla and Opera.
		// Anyways, this should NOT disturb anything else:
		$value = $this->convNumEntityToByteValue($value);
		// When values are sent as group or select they come as comma-separated values which are exploded by this function:
		$valueArray = $this->checkValue_group_select_explodeSelectGroupValue($value);
		// If not multiple is set, then remove duplicates:
		if (!$tcaFieldConf['multiple']) {
			$valueArray = array_unique($valueArray);
		}
		// If an exclusive key is found, discard all others:
		if ($tcaFieldConf['type'] == 'select' && $tcaFieldConf['exclusiveKeys']) {
			$exclusiveKeys = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $tcaFieldConf['exclusiveKeys']);
			foreach ($valueArray as $kk => $vv) {
				// $vv is the item key!
				if (in_array($vv, $exclusiveKeys)) {
					$valueArray = array($kk => $vv);
					break;
				}
			}
		}
		// This could be a good spot for parsing the array through a validation-function which checks if the values are alright (except that database references are not in their final form - but that is the point, isn't it?)
		// NOTE!!! Must check max-items of files before the later check because that check would just leave out filenames if there are too many!!
		$valueArray = $this->applyFiltersToValues($tcaFieldConf, $valueArray);
		// Checking for select / authMode, removing elements from $valueArray if any of them is not allowed!
		if ($tcaFieldConf['type'] == 'select' && $tcaFieldConf['authMode']) {
			$preCount = count($valueArray);
			foreach ($valueArray as $kk => $vv) {
				if (!$this->BE_USER->checkAuthMode($table, $field, $vv, $tcaFieldConf['authMode'])) {
					unset($valueArray[$kk]);
				}
			}
			// During the check it turns out that the value / all values were removed - we respond by simply returning an empty array so nothing is written to DB for this field.
			if ($preCount && !count($valueArray)) {
				return array();
			}
		}
		// For group types:
		if ($tcaFieldConf['type'] == 'group') {
			switch ($tcaFieldConf['internal_type']) {
			case 'file_reference':

			case 'file':
				$valueArray = $this->checkValue_group_select_file($valueArray, $tcaFieldConf, $curValue, $uploadedFiles, $status, $table, $id, $recFID);
				break;
			case 'db':
				$valueArray = $this->checkValue_group_select_processDBdata($valueArray, $tcaFieldConf, $id, $status, 'group', $table, $field);
				break;
			}
		}
		// For select types which has a foreign table attached:
		if ($tcaFieldConf['type'] == 'select' && $tcaFieldConf['foreign_table']) {
			// check, if there is a NEW... id in the value, that should be substituded later
			if (strpos($value, 'NEW') !== FALSE) {
				$this->remapStackRecords[$table][$id] = array('remapStackIndex' => count($this->remapStack));
				$this->addNewValuesToRemapStackChildIds($valueArray);
				$this->remapStack[] = array(
					'func' => 'checkValue_group_select_processDBdata',
					'args' => array($valueArray, $tcaFieldConf, $id, $status, 'select', $table, $field),
					'pos' => array('valueArray' => 0, 'tcaFieldConf' => 1, 'id' => 2, 'table' => 5),
					'field' => $field
				);
				$unsetResult = TRUE;
			} else {
				$valueArray = $this->checkValue_group_select_processDBdata($valueArray, $tcaFieldConf, $id, $status, 'select', $table, $field);
			}
		}
		if (!$unsetResult) {
			$newVal = $this->checkValue_checkMax($tcaFieldConf, $valueArray);
			$res['value'] = implode(',', $newVal);
		} else {
			unset($res['value']);
		}
		return $res;
	}

	/**
	 * Applies the filter methods from a column's TCA configuration to a value array.
	 *
	 * @param array $tcaFieldConfiguration
	 * @param array $values
	 * @return array|mixed
	 * @throws \RuntimeException
	 */
	protected function applyFiltersToValues(array $tcaFieldConfiguration, array $values) {
		if (empty($tcaFieldConfiguration['filter']) || !is_array($tcaFieldConfiguration['filter'])) {
			return $values;
		}
		foreach ($tcaFieldConfiguration['filter'] as $filter) {
			if (empty($filter['userFunc'])) {
				continue;
			}
			$parameters = $filter['parameters'] ? $filter['parameters'] : array();
			$parameters['values'] = $values;
			$parameters['tcaFieldConfig'] = $tcaFieldConfiguration;
			$values = \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($filter['userFunc'], $parameters, $this);
			if (!is_array($values)) {
				throw new \RuntimeException('Failed calling filter userFunc.', 1336051942);
			}
		}
		return $values;
	}

	/**
	 * Handling files for group/select function
	 *
	 * @param array $valueArray Array of incoming file references. Keys are numeric, values are files (basically, this is the exploded list of incoming files)
	 * @param array $tcaFieldConf Configuration array from TCA of the field
	 * @param string $curValue Current value of the field
	 * @param array $uploadedFileArray Array of uploaded files, if any
	 * @param string $status Status ("update" or ?)
	 * @param string $table tablename of record
	 * @param integer $id UID of record
	 * @param string $recFID Field identifier ([table:uid:field:....more for flexforms?]
	 * @return array Modified value array
	 * @see checkValue_group_select()
	 * @todo Define visibility
	 */
	public function checkValue_group_select_file($valueArray, $tcaFieldConf, $curValue, $uploadedFileArray, $status, $table, $id, $recFID) {
		// If file handling should NOT be bypassed, do processing:
		if (!$this->bypassFileHandling) {
			// If any files are uploaded, add them to value array
			// Numeric index means that there are multiple files
			if (isset($uploadedFileArray[0])) {
				$uploadedFiles = $uploadedFileArray;
			} else {
				// There is only one file
				$uploadedFiles = array($uploadedFileArray);
			}
			foreach ($uploadedFiles as $uploadedFileArray) {
				if (!empty($uploadedFileArray['name']) && $uploadedFileArray['tmp_name'] !== 'none') {
					$valueArray[] = $uploadedFileArray['tmp_name'];
					$this->alternativeFileName[$uploadedFileArray['tmp_name']] = $uploadedFileArray['name'];
				}
			}
			// Creating fileFunc object.
			if (!$this->fileFunc) {
				$this->fileFunc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Utility\\File\\BasicFileUtility');
				$this->include_filefunctions = 1;
			}
			// Setting permitted extensions.
			$all_files = array();
			$all_files['webspace']['allow'] = $tcaFieldConf['allowed'];
			$all_files['webspace']['deny'] = $tcaFieldConf['disallowed'] ? $tcaFieldConf['disallowed'] : '*';
			$all_files['ftpspace'] = $all_files['webspace'];
			$this->fileFunc->init('', $all_files);
		}
		// If there is an upload folder defined:
		if ($tcaFieldConf['uploadfolder'] && $tcaFieldConf['internal_type'] == 'file') {
			// If filehandling should NOT be bypassed, do processing:
			if (!$this->bypassFileHandling) {
				// For logging..
				$propArr = $this->getRecordProperties($table, $id);
				// Get destrination path:
				$dest = $this->destPathFromUploadFolder($tcaFieldConf['uploadfolder']);
				// If we are updating:
				if ($status == 'update') {
					// Traverse the input values and convert to absolute filenames in case the update happens to an autoVersionized record.
					// Background: This is a horrible workaround! The problem is that when a record is auto-versionized the files of the record get copied and therefore get new names which is overridden with the names from the original record in the incoming data meaning both lost files and double-references!
					// The only solution I could come up with (except removing support for managing files when autoversioning) was to convert all relative files to absolute names so they are copied again (and existing files deleted). This should keep references intact but means that some files are copied, then deleted after being copied _again_.
					// Actually, the same problem applies to database references in case auto-versioning would include sub-records since in such a case references are remapped - and they would be overridden due to the same principle then.
					// Illustration of the problem comes here:
					// We have a record 123 with a file logo.gif. We open and edit the files header in a workspace. So a new version is automatically made.
					// The versions uid is 456 and the file is copied to "logo_01.gif". But the form data that we sent was based on uid 123 and hence contains the filename "logo.gif" from the original.
					// The file management code below will do two things: First it will blindly accept "logo.gif" as a file attached to the record (thus creating a double reference) and secondly it will find that "logo_01.gif" was not in the incoming filelist and therefore should be deleted.
					// If we prefix the incoming file "logo.gif" with its absolute path it will be seen as a new file added. Thus it will be copied to "logo_02.gif". "logo_01.gif" will still be deleted but since the files are the same the difference is zero - only more processing and file copying for no reason. But it will work.
					if ($this->autoVersioningUpdate === TRUE) {
						foreach ($valueArray as $key => $theFile) {
							// If it is an already attached file...
							if ($theFile === basename($theFile)) {
								$valueArray[$key] = PATH_site . $tcaFieldConf['uploadfolder'] . '/' . $theFile;
							}
						}
					}
					// Finding the CURRENT files listed, either from MM or from the current record.
					$theFileValues = array();
					// If MM relations for the files also!
					if ($tcaFieldConf['MM']) {
						$dbAnalysis = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\RelationHandler');
						/** @var $dbAnalysis \TYPO3\CMS\Core\Database\RelationHandler */
						$dbAnalysis->start('', 'files', $tcaFieldConf['MM'], $id);
						foreach ($dbAnalysis->itemArray as $item) {
							if ($item['id']) {
								$theFileValues[] = $item['id'];
							}
						}
					} else {
						$theFileValues = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $curValue, 1);
					}
					$currentFilesForHistory = implode(',', $theFileValues);
					// DELETE files: If existing files were found, traverse those and register files for deletion which has been removed:
					if (count($theFileValues)) {
						// Traverse the input values and for all input values which match an EXISTING value, remove the existing from $theFileValues array (this will result in an array of all the existing files which should be deleted!)
						foreach ($valueArray as $key => $theFile) {
							if ($theFile && !strstr(\TYPO3\CMS\Core\Utility\GeneralUtility::fixWindowsFilePath($theFile), '/')) {
								$theFileValues = \TYPO3\CMS\Core\Utility\GeneralUtility::removeArrayEntryByValue($theFileValues, $theFile);
							}
						}
						// This array contains the filenames in the uploadfolder that should be deleted:
						foreach ($theFileValues as $key => $theFile) {
							$theFile = trim($theFile);
							if (@is_file(($dest . '/' . $theFile))) {
								$this->removeFilesStore[] = $dest . '/' . $theFile;
							} elseif ($theFile) {
								$this->log($table, $id, 5, 0, 1, 'Could not delete file \'%s\' (does not exist). (%s)', 10, array($dest . '/' . $theFile, $recFID), $propArr['event_pid']);
							}
						}
					}
				}
				// Traverse the submitted values:
				foreach ($valueArray as $key => $theFile) {
					// Init:
					$maxSize = intval($tcaFieldConf['max_size']);
					// Must be cleared. Else a faulty fileref may be inserted if the below code returns an error!
					$theDestFile = '';
					// a FAL file was added, now resolve the file object and get the absolute path
					// @todo in future versions this needs to be modified to handle FAL objects natively
					if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($theFile)) {
						$fileObject = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->getFileObject($theFile);
						$theFile = $fileObject->getForLocalProcessing(FALSE);
					}
					// NEW FILES? If the value contains '/' it indicates, that the file
					// is new and should be added to the uploadsdir (whether its absolute or relative does not matter here)
					if (strstr(\TYPO3\CMS\Core\Utility\GeneralUtility::fixWindowsFilePath($theFile), '/')) {
						// Check various things before copying file:
						// File and destination must exist
						if (@is_dir($dest) && (@is_file($theFile) || @is_uploaded_file($theFile))) {
							// Finding size.
							if (is_uploaded_file($theFile) && $theFile == $uploadedFileArray['tmp_name']) {
								$fileSize = $uploadedFileArray['size'];
							} else {
								$fileSize = filesize($theFile);
							}
							// Check file size:
							if (!$maxSize || $fileSize <= $maxSize * 1024) {
								// Prepare filename:
								$theEndFileName = isset($this->alternativeFileName[$theFile]) ? $this->alternativeFileName[$theFile] : $theFile;
								$fI = \TYPO3\CMS\Core\Utility\GeneralUtility::split_fileref($theEndFileName);
								// Check for allowed extension:
								if ($this->fileFunc->checkIfAllowed($fI['fileext'], $dest, $theEndFileName)) {
									$theDestFile = $this->fileFunc->getUniqueName($this->fileFunc->cleanFileName($fI['file']), $dest);
									// If we have a unique destination filename, then write the file:
									if ($theDestFile) {
										\TYPO3\CMS\Core\Utility\GeneralUtility::upload_copy_move($theFile, $theDestFile);
										// Hook for post-processing the upload action
										if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processUpload'])) {
											foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processUpload'] as $classRef) {
												$hookObject = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
												if (!$hookObject instanceof \TYPO3\CMS\Core\DataHandling\DataHandlerProcessUploadHookInterface) {
													throw new \UnexpectedValueException('$hookObject must implement interface TYPO3\\CMS\\Core\\DataHandling\\DataHandlerProcessUploadHookInterface', 1279962349);
												}
												$hookObject->processUpload_postProcessAction($theDestFile, $this);
											}
										}
										$this->copiedFileMap[$theFile] = $theDestFile;
										clearstatcache();
										if (!@is_file($theDestFile)) {
											$this->log($table, $id, 5, 0, 1, 'Copying file \'%s\' failed!: The destination path (%s) may be write protected. Please make it write enabled!. (%s)', 16, array($theFile, dirname($theDestFile), $recFID), $propArr['event_pid']);
										}
									} else {
										$this->log($table, $id, 5, 0, 1, 'Copying file \'%s\' failed!: No destination file (%s) possible!. (%s)', 11, array($theFile, $theDestFile, $recFID), $propArr['event_pid']);
									}
								} else {
									$this->log($table, $id, 5, 0, 1, 'File extension \'%s\' not allowed. (%s)', 12, array($fI['fileext'], $recFID), $propArr['event_pid']);
								}
							} else {
								$this->log($table, $id, 5, 0, 1, 'Filesize (%s) of file \'%s\' exceeds limit (%s). (%s)', 13, array(\TYPO3\CMS\Core\Utility\GeneralUtility::formatSize($fileSize), $theFile, \TYPO3\CMS\Core\Utility\GeneralUtility::formatSize($maxSize * 1024), $recFID), $propArr['event_pid']);
							}
						} else {
							$this->log($table, $id, 5, 0, 1, 'The destination (%s) or the source file (%s) does not exist. (%s)', 14, array($dest, $theFile, $recFID), $propArr['event_pid']);
						}
						// If the destination file was created, we will set the new filename in
						// the value array, otherwise unset the entry in the value array!
						if (@is_file($theDestFile)) {
							$info = \TYPO3\CMS\Core\Utility\GeneralUtility::split_fileref($theDestFile);
							// The value is set to the new filename
							$valueArray[$key] = $info['file'];
						} else {
							// The value is set to the new filename
							unset($valueArray[$key]);
						}
					}
				}
			}
			// If MM relations for the files, we will set the relations as MM records and change the valuearray to contain a single entry with a count of the number of files!
			if ($tcaFieldConf['MM']) {
				/** @var $dbAnalysis \TYPO3\CMS\Core\Database\RelationHandler */
				$dbAnalysis = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\RelationHandler');
				// Dummy
				$dbAnalysis->tableArray['files'] = array();
				foreach ($valueArray as $key => $theFile) {
					// Explode files
					$dbAnalysis->itemArray[]['id'] = $theFile;
				}
				if ($status == 'update') {
					$dbAnalysis->writeMM($tcaFieldConf['MM'], $id, 0);
					$newFiles = implode(',', $dbAnalysis->getValueArray());
					list(, , $recFieldName) = explode(':', $recFID);
					if ($currentFilesForHistory != $newFiles) {
						$this->mmHistoryRecords[$table . ':' . $id]['oldRecord'][$recFieldName] = $currentFilesForHistory;
						$this->mmHistoryRecords[$table . ':' . $id]['newRecord'][$recFieldName] = $newFiles;
					} else {
						$this->mmHistoryRecords[$table . ':' . $id]['oldRecord'][$recFieldName] = '';
						$this->mmHistoryRecords[$table . ':' . $id]['newRecord'][$recFieldName] = '';
					}
				} else {
					$this->dbAnalysisStore[] = array($dbAnalysis, $tcaFieldConf['MM'], $id, 0);
				}
				$valueArray = $dbAnalysis->countItems();
			}
		} else {
			if (count($valueArray)) {
				// If filehandling should NOT be bypassed, do processing:
				if (!$this->bypassFileHandling) {
					// For logging..
					$propArr = $this->getRecordProperties($table, $id);
					foreach ($valueArray as &$theFile) {
						// FAL handling: it's a UID, thus it is resolved to the absolute path
						if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($theFile)) {
							$fileObject = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->getFileObject($theFile);
							$theFile = $fileObject->getForLocalProcessing(FALSE);
						}
						if ($this->alternativeFilePath[$theFile]) {
							// If alernative File Path is set for the file, then it was an import
							// don't import the file if it already exists
							if (@is_file((PATH_site . $this->alternativeFilePath[$theFile]))) {
								$theFile = PATH_site . $this->alternativeFilePath[$theFile];
							} elseif (@is_file($theFile)) {
								$dest = dirname(PATH_site . $this->alternativeFilePath[$theFile]);
								if (!@is_dir($dest)) {
									\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir_deep(PATH_site, dirname($this->alternativeFilePath[$theFile]) . '/');
								}
								// Init:
								$maxSize = intval($tcaFieldConf['max_size']);
								$cmd = '';
								// Must be cleared. Else a faulty fileref may be inserted if the below code returns an error!
								$theDestFile = '';
								$fileSize = filesize($theFile);
								// Check file size:
								if (!$maxSize || $fileSize <= $maxSize * 1024) {
									// Prepare filename:
									$theEndFileName = isset($this->alternativeFileName[$theFile]) ? $this->alternativeFileName[$theFile] : $theFile;
									$fI = \TYPO3\CMS\Core\Utility\GeneralUtility::split_fileref($theEndFileName);
									// Check for allowed extension:
									if ($this->fileFunc->checkIfAllowed($fI['fileext'], $dest, $theEndFileName)) {
										$theDestFile = PATH_site . $this->alternativeFilePath[$theFile];
										// Write the file:
										if ($theDestFile) {
											\TYPO3\CMS\Core\Utility\GeneralUtility::upload_copy_move($theFile, $theDestFile);
											$this->copiedFileMap[$theFile] = $theDestFile;
											clearstatcache();
											if (!@is_file($theDestFile)) {
												$this->log($table, $id, 5, 0, 1, 'Copying file \'%s\' failed!: The destination path (%s) may be write protected. Please make it write enabled!. (%s)', 16, array($theFile, dirname($theDestFile), $recFID), $propArr['event_pid']);
											}
										} else {
											$this->log($table, $id, 5, 0, 1, 'Copying file \'%s\' failed!: No destination file (%s) possible!. (%s)', 11, array($theFile, $theDestFile, $recFID), $propArr['event_pid']);
										}
									} else {
										$this->log($table, $id, 5, 0, 1, 'File extension \'%s\' not allowed. (%s)', 12, array($fI['fileext'], $recFID), $propArr['event_pid']);
									}
								} else {
									$this->log($table, $id, 5, 0, 1, 'Filesize (%s) of file \'%s\' exceeds limit (%s). (%s)', 13, array(\TYPO3\CMS\Core\Utility\GeneralUtility::formatSize($fileSize), $theFile, \TYPO3\CMS\Core\Utility\GeneralUtility::formatSize($maxSize * 1024), $recFID), $propArr['event_pid']);
								}
								// If the destination file was created, we will set the new filename in the value array, otherwise unset the entry in the value array!
								if (@is_file($theDestFile)) {
									// The value is set to the new filename
									$theFile = $theDestFile;
								} else {
									// The value is set to the new filename
									unset($theFile);
								}
							}
						}
						$theFile = \TYPO3\CMS\Core\Utility\GeneralUtility::fixWindowsFilePath($theFile);
						if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($theFile, PATH_site)) {
							$theFile = substr($theFile, strlen(PATH_site));
						}
					}
					unset($theFile);
				}
			}
		}
		return $valueArray;
	}

	/**
	 * Evaluates 'flex' type values.
	 *
	 * @param array $res The result array. The processed value (if any!) is set in the 'value' key.
	 * @param string $value The value to set.
	 * @param array $tcaFieldConf Field configuration from TCA
	 * @param array $PP Additional parameters in a numeric array: $table,$id,$curValue,$status,$realPid,$recFID
	 * @param array $uploadedFiles Uploaded files for the field
	 * @param string $field Field name
	 * @return array Modified $res array
	 * @todo Define visibility
	 */
	public function checkValue_flex($res, $value, $tcaFieldConf, $PP, $uploadedFiles, $field) {
		list($table, $id, $curValue, $status, $realPid, $recFID) = $PP;

		if (is_array($value)) {
			// This value is necessary for flex form processing to happen on flexform fields in page records when they are copied.
			// The problem is, that when copying a page, flexfrom XML comes along in the array for the new record - but since $this->checkValue_currentRecord does not have a uid or pid for that sake, the \TYPO3\CMS\Backend\Utility\BackendUtility::getFlexFormDS() function returns no good DS. For new records we do know the expected PID so therefore we send that with this special parameter. Only active when larger than zero.
			$newRecordPidValue = $status == 'new' ? $realPid : 0;
			// Get current value array:
			$dataStructArray = \TYPO3\CMS\Backend\Utility\BackendUtility::getFlexFormDS($tcaFieldConf, $this->checkValue_currentRecord, $table, '', TRUE, $newRecordPidValue);
			$currentValueArray = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($curValue);
			if (!is_array($currentValueArray)) {
				$currentValueArray = array();
			}
			if (is_array($currentValueArray['meta']['currentLangId'])) {
				unset($currentValueArray['meta']['currentLangId']);
			}
			// Remove all old meta for languages...
			// Evaluation of input values:
			$value['data'] = $this->checkValue_flex_procInData($value['data'], $currentValueArray['data'], $uploadedFiles['data'], $dataStructArray, $PP);
			// Create XML and convert charsets from input value:
			$xmlValue = $this->checkValue_flexArray2Xml($value, TRUE);
			// If we wanted to set UTF fixed:
			// $storeInCharset='utf-8';
			// $currentCharset=$GLOBALS['LANG']->charSet;
			// $xmlValue = $GLOBALS['LANG']->csConvObj->conv($xmlValue,$currentCharset,$storeInCharset,1);
			$storeInCharset = $GLOBALS['LANG']->charSet;
			// Merge them together IF they are both arrays:
			// Here we convert the currently submitted values BACK to an array, then merge the two and then BACK to XML again. This is needed to ensure the charsets are the same (provided that the current value was already stored IN the charset that the new value is converted to).
			if (is_array($currentValueArray)) {
				$arrValue = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($xmlValue);
				$arrValue = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($currentValueArray, $arrValue);
				$xmlValue = $this->checkValue_flexArray2Xml($arrValue, TRUE);
			}
			// Action commands (sorting order and removals of elements)
			$actionCMDs = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('_ACTION_FLEX_FORMdata');
			if (is_array($actionCMDs[$table][$id][$field]['data'])) {
				$arrValue = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($xmlValue);
				$this->_ACTION_FLEX_FORMdata($arrValue['data'], $actionCMDs[$table][$id][$field]['data']);
				$xmlValue = $this->checkValue_flexArray2Xml($arrValue, TRUE);
			}
			// Create the value XML:
			$res['value'] = '';
			$res['value'] .= $xmlValue;
		} else {
			// Passthrough...:
			$res['value'] = $value;
		}

		return $res;
	}

	/**
	 * Converts an array to FlexForm XML
	 *
	 * @param array $array Array with FlexForm data
	 * @param boolean $addPrologue If set, the XML prologue is returned as well.
	 * @return string Input array converted to XML
	 * @todo Define visibility
	 */
	public function checkValue_flexArray2Xml($array, $addPrologue = FALSE) {
		/** @var $flexObj \TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools */
		$flexObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Configuration\\FlexForm\\FlexFormTools');
		return $flexObj->flexArray2Xml($array, $addPrologue);
	}

	/**
	 * Actions for flex form element (move, delete)
	 *
	 * @param array &$valueArrayToRemoveFrom by reference
	 * @param array $deleteCMDS
	 * @return void
	 * @todo Define visibility
	 */
	public function _ACTION_FLEX_FORMdata(&$valueArray, $actionCMDs) {
		if (is_array($valueArray) && is_array($actionCMDs)) {
			foreach ($actionCMDs as $key => $value) {
				if ($key == '_ACTION') {
					// First, check if there are "commands":
					if (current($actionCMDs[$key]) !== '') {
						asort($actionCMDs[$key]);
						$newValueArray = array();
						foreach ($actionCMDs[$key] as $idx => $order) {
							if (substr($idx, 0, 3) == 'ID-') {
								$idx = $this->newIndexMap[$idx];
							}
							// Just one reflection here: It is clear that when removing elements from a flexform, then we will get lost files unless we act on this delete operation by traversing and deleting files that were referred to.
							if ($order != 'DELETE') {
								$newValueArray[$idx] = $valueArray[$idx];
							}
							unset($valueArray[$idx]);
						}
						$valueArray = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge($newValueArray, $valueArray);
					}
				} elseif (is_array($actionCMDs[$key]) && isset($valueArray[$key])) {
					$this->_ACTION_FLEX_FORMdata($valueArray[$key], $actionCMDs[$key]);
				}
			}
		}
	}

	/**
	 * Evaluates 'inline' type values.
	 * (partly copied from the select_group function on this issue)
	 *
	 * @param array $res The result array. The processed value (if any!) is set in the 'value' key.
	 * @param string $value The value to set.
	 * @param array $tcaFieldConf Field configuration from TCA
	 * @param array $PP Additional parameters in a numeric array: $table,$id,$curValue,$status,$realPid,$recFID
	 * @param string $field Field name
	 * @param array $additionalData Additional data to be forwarded to sub-processors
	 * @return array Modified $res array
	 * @todo Define visibility
	 */
	public function checkValue_inline($res, $value, $tcaFieldConf, $PP, $field, array $additionalData = NULL) {
		list($table, $id, $curValue, $status, $realPid, $recFID) = $PP;
		if (!$tcaFieldConf['foreign_table']) {
			// Fatal error, inline fields should always have a foreign_table defined
			return FALSE;
		}
		// When values are sent they come as comma-separated values which are exploded by this function:
		$valueArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $value);
		// Remove duplicates: (should not be needed)
		$valueArray = array_unique($valueArray);
		// Example for received data:
		// $value = 45,NEW4555fdf59d154,12,123
		// We need to decide whether we use the stack or can save the relation directly.
		if (strpos($value, 'NEW') !== FALSE || !\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($id)) {
			$this->remapStackRecords[$table][$id] = array('remapStackIndex' => count($this->remapStack));
			$this->addNewValuesToRemapStackChildIds($valueArray);
			$this->remapStack[] = array(
				'func' => 'checkValue_inline_processDBdata',
				'args' => array($valueArray, $tcaFieldConf, $id, $status, $table, $field, $additionalData),
				'pos' => array('valueArray' => 0, 'tcaFieldConf' => 1, 'id' => 2, 'table' => 4),
				'additionalData' => $additionalData,
				'field' => $field,
			);
			unset($res['value']);
		} elseif ($value || \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($id)) {
			$res['value'] = $this->checkValue_inline_processDBdata($valueArray, $tcaFieldConf, $id, $status, $table, $field, $additionalData);
		}
		return $res;
	}

	/**
	 * Checks if a fields has more items than defined via TCA in maxitems.
	 * If there are more items than allowd, the item list is truncated to the defined number.
	 *
	 * @param array $tcaFieldConf Field configuration from TCA
	 * @param array $valueArray Current value array of items
	 * @return array The truncated value array of items
	 * @todo Define visibility
	 */
	public function checkValue_checkMax($tcaFieldConf, $valueArray) {
		// BTW, checking for min and max items here does NOT make any sense when MM is used because the above function calls will just return an array with a single item (the count) if MM is used... Why didn't I perform the check before? Probably because we could not evaluate the validity of record uids etc... Hmm...
		$valueArrayC = count($valueArray);
		// NOTE to the comment: It's not really possible to check for too few items, because you must then determine first, if the field is actual used regarding the CType.
		$maxI = isset($tcaFieldConf['maxitems']) ? intval($tcaFieldConf['maxitems']) : 1;
		if ($valueArrayC > $maxI) {
			$valueArrayC = $maxI;
		}
		// Checking for not too many elements
		// Dumping array to list
		$newVal = array();
		foreach ($valueArray as $nextVal) {
			if ($valueArrayC == 0) {
				break;
			}
			$valueArrayC--;
			$newVal[] = $nextVal;
		}
		return $newVal;
	}

	/*********************************************
	 *
	 * Helper functions for evaluation functions.
	 *
	 ********************************************/
	/**
	 * Gets a unique value for $table/$id/$field based on $value
	 *
	 * @param string $table Table name
	 * @param string $field Field name for which $value must be unique
	 * @param string $value Value string.
	 * @param integer $id UID to filter out in the lookup (the record itself...)
	 * @param integer $newPid If set, the value will be unique for this PID
	 * @return string Modified value (if not-unique). Will be the value appended with a number (until 100, then the function just breaks).
	 * @todo Define visibility
	 */
	public function getUnique($table, $field, $value, $id, $newPid = 0) {
		// Initialize:
		$whereAdd = '';
		$newValue = '';
		if (intval($newPid)) {
			$whereAdd .= ' AND pid=' . intval($newPid);
		} else {
			$whereAdd .= ' AND pid>=0';
		}
		// "AND pid>=0" for versioning
		$whereAdd .= $this->deleteClause($table);
		// If the field is configured in TCA, proceed:
		if (is_array($GLOBALS['TCA'][$table]) && is_array($GLOBALS['TCA'][$table]['columns'][$field])) {
			// Look for a record which might already have the value:
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', $table, $field . '=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($value, $table) . ' AND uid<>' . intval($id) . $whereAdd);
			$counter = 0;
			// For as long as records with the test-value existing, try again (with incremented numbers appended).
			while ($GLOBALS['TYPO3_DB']->sql_num_rows($res)) {
				$newValue = $value . $counter;
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', $table, $field . '=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($newValue, $table) . ' AND uid<>' . intval($id) . $whereAdd);
				$counter++;
				if ($counter > 100) {
					break;
				}
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
			// If the new value is there:
			$value = strlen($newValue) ? $newValue : $value;
		}
		return $value;
	}

	/**
	 * @param string $value The field value to be evaluated
	 * @param array $evalArray Array of evaluations to traverse.
	 * @param string $is_in The "is_in" value of the field configuration from TCA
	 * @return array
	 * @todo Define visibility
	 */
	public function checkValue_text_Eval($value, $evalArray, $is_in) {
		$res = array();
		$newValue = $value;
		$set = TRUE;
		foreach ($evalArray as $func) {
			switch ($func) {
			case 'trim':
				$value = trim($value);
				break;
			case 'required':
				if (!$value) {
					$set = 0;
				}
				break;
			default:
				$evalObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][$func] . ':&' . $func);
				if (is_object($evalObj) && method_exists($evalObj, 'evaluateFieldValue')) {
					$value = $evalObj->evaluateFieldValue($value, $is_in, $set);
				}
				break;
			}
		}
		if ($set) {
			$res['value'] = $value;
		}
		return $res;
	}

	/**
	 * Evaluation of 'input'-type values based on 'eval' list
	 *
	 * @param string $value Value to evaluate
	 * @param array $evalArray Array of evaluations to traverse.
	 * @param string $is_in Is-in string for 'is_in' evaluation
	 * @return array Modified $value in key 'value' or empty array
	 * @todo Define visibility
	 */
	public function checkValue_input_Eval($value, $evalArray, $is_in) {
		$res = array();
		$newValue = $value;
		$set = TRUE;
		foreach ($evalArray as $func) {
			switch ($func) {
			case 'int':

			case 'year':

			case 'time':

			case 'timesec':
				$value = intval($value);
				break;
			case 'date':

			case 'datetime':
				$value = intval($value);
				if ($value > 0 && !$this->dontProcessTransformations) {
					$value -= date('Z', $value);
				}
				break;
			case 'double2':
				$value = preg_replace('/[^0-9,\\.-]/', '', $value);
				$negative = substr($value, 0, 1) == '-';
				$value = strtr($value, array(',' => '.', '-' => ''));
				if (strpos($value, '.') === FALSE) {
					$value .= '.0';
				}
				$valueArray = explode('.', $value);
				$dec = array_pop($valueArray);
				$value = join('', $valueArray) . '.' . $dec;
				if ($negative) {
					$value *= -1;
				}
				$value = number_format($value, 2, '.', '');
				break;
			case 'md5':
				if (strlen($value) != 32) {
					$set = FALSE;
				}
				break;
			case 'trim':
				$value = trim($value);
				break;
			case 'upper':
				$value = $GLOBALS['LANG']->csConvObj->conv_case($GLOBALS['LANG']->charSet, $value, 'toUpper');
				break;
			case 'lower':
				$value = $GLOBALS['LANG']->csConvObj->conv_case($GLOBALS['LANG']->charSet, $value, 'toLower');
				break;
			case 'required':
				if (!isset($value) || $value === '') {
					$set = FALSE;
				}
				break;
			case 'is_in':
				$c = strlen($value);
				if ($c) {
					$newVal = '';
					for ($a = 0; $a < $c; $a++) {
						$char = substr($value, $a, 1);
						if (strpos($is_in, $char) !== FALSE) {
							$newVal .= $char;
						}
					}
					$value = $newVal;
				}
				break;
			case 'nospace':
				$value = str_replace(' ', '', $value);
				break;
			case 'alpha':
				$value = preg_replace('/[^a-zA-Z]/', '', $value);
				break;
			case 'num':
				$value = preg_replace('/[^0-9]/', '', $value);
				break;
			case 'alphanum':
				$value = preg_replace('/[^a-zA-Z0-9]/', '', $value);
				break;
			case 'alphanum_x':
				$value = preg_replace('/[^a-zA-Z0-9_-]/', '', $value);
				break;
			case 'domainname':
				if (!preg_match('/^[a-z0-9\\.\\-]*$/i', $value)) {
					\TYPO3\CMS\Core\Utility\GeneralUtility::requireOnce(PATH_typo3 . 'contrib/idna/idna_convert.class.php');
					$idnaConvert = new \idna_convert();
					$idnaConvert->set_parameter('idn_version', '2008');
					$value = $idnaConvert->encode($value);
					unset($idnaConvert);
				}
				break;
			default:
				$evalObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][$func] . ':&' . $func);
				if (is_object($evalObj) && method_exists($evalObj, 'evaluateFieldValue')) {
					$value = $evalObj->evaluateFieldValue($value, $is_in, $set);
				}
				break;
			}
		}
		if ($set) {
			$res['value'] = $value;
		}
		return $res;
	}

	/**
	 * Returns data for group/db and select fields
	 *
	 * @param array $valueArray Current value array
	 * @param array $tcaFieldConf TCA field config
	 * @param integer $id Record id, used for look-up of MM relations (local_uid)
	 * @param string $status Status string ('update' or 'new')
	 * @param string $type The type, either 'select', 'group' or 'inline'
	 * @param string $currentTable Table name, needs to be passed to \TYPO3\CMS\Core\Database\RelationHandler
	 * @param string $currentField field name, needs to be set for writing to sys_history
	 * @return array Modified value array
	 * @todo Define visibility
	 */
	public function checkValue_group_select_processDBdata($valueArray, $tcaFieldConf, $id, $status, $type, $currentTable, $currentField) {
		$tables = $type == 'group' ? $tcaFieldConf['allowed'] : $tcaFieldConf['foreign_table'] . ',' . $tcaFieldConf['neg_foreign_table'];
		$prep = $type == 'group' ? $tcaFieldConf['prepend_tname'] : $tcaFieldConf['neg_foreign_table'];
		$newRelations = implode(',', $valueArray);
		/** @var $dbAnalysis \TYPO3\CMS\Core\Database\RelationHandler */
		$dbAnalysis = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\RelationHandler');
		$dbAnalysis->registerNonTableValues = $tcaFieldConf['allowNonIdValues'] ? 1 : 0;
		$dbAnalysis->start($newRelations, $tables, '', 0, $currentTable, $tcaFieldConf);
		if ($tcaFieldConf['MM']) {
			if ($status == 'update') {
				/** @var $oldRelations_dbAnalysis \TYPO3\CMS\Core\Database\RelationHandler */
				$oldRelations_dbAnalysis = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\RelationHandler');
				$oldRelations_dbAnalysis->registerNonTableValues = $tcaFieldConf['allowNonIdValues'] ? 1 : 0;
				// Db analysis with $id will initialize with the existing relations
				$oldRelations_dbAnalysis->start('', $tables, $tcaFieldConf['MM'], $id, $currentTable, $tcaFieldConf);
				$oldRelations = implode(',', $oldRelations_dbAnalysis->getValueArray());
				$dbAnalysis->writeMM($tcaFieldConf['MM'], $id, $prep);
				if ($oldRelations != $newRelations) {
					$this->mmHistoryRecords[$currentTable . ':' . $id]['oldRecord'][$currentField] = $oldRelations;
					$this->mmHistoryRecords[$currentTable . ':' . $id]['newRecord'][$currentField] = $newRelations;
				} else {
					$this->mmHistoryRecords[$currentTable . ':' . $id]['oldRecord'][$currentField] = '';
					$this->mmHistoryRecords[$currentTable . ':' . $id]['newRecord'][$currentField] = '';
				}
			} else {
				$this->dbAnalysisStore[] = array($dbAnalysis, $tcaFieldConf['MM'], $id, $prep, $currentTable);
			}
			$valueArray = $dbAnalysis->countItems();
		} else {
			$valueArray = $dbAnalysis->getValueArray($prep);
			if ($type == 'select' && $prep) {
				$valueArray = $dbAnalysis->convertPosNeg($valueArray, $tcaFieldConf['foreign_table'], $tcaFieldConf['neg_foreign_table']);
			}
		}
		// Here we should see if 1) the records exist anymore, 2) which are new and check if the BE_USER has read-access to the new ones.
		return $valueArray;
	}

	/**
	 * Explodes the $value, which is a list of files/uids (group select)
	 *
	 * @param string $value Input string, comma separated values. For each part it will also be detected if a '|' is found and the first part will then be used if that is the case. Further the value will be rawurldecoded.
	 * @return array The value array.
	 * @todo Define visibility
	 */
	public function checkValue_group_select_explodeSelectGroupValue($value) {
		$valueArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $value, 1);
		foreach ($valueArray as &$newVal) {
			$temp = explode('|', $newVal, 2);
			$newVal = str_replace(',', '', str_replace('|', '', rawurldecode($temp[0])));
		}
		unset($newVal);
		return $valueArray;
	}

	/**
	 * Starts the processing the input data for flexforms. This will traverse all sheets / languages and for each it will traverse the sub-structure.
	 * See checkValue_flex_procInData_travDS() for more details.
	 * WARNING: Currently, it traverses based on the actual _data_ array and NOT the _structure_. This means that values for non-valid fields, lKey/vKey/sKeys will be accepted! For traversal of data with a call back function you should rather use \TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools
	 *
	 * @param array $dataPart The 'data' part of the INPUT flexform data
	 * @param array $dataPart_current The 'data' part of the CURRENT flexform data
	 * @param array $uploadedFiles The uploaded files for the 'data' part of the INPUT flexform data
	 * @param array $dataStructArray Data structure for the form (might be sheets or not). Only values in the data array which has a configuration in the data structure will be processed.
	 * @param array $pParams A set of parameters to pass through for the calling of the evaluation functions
	 * @param string $callBackFunc Optional call back function, see checkValue_flex_procInData_travDS()  DEPRICATED, use \TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools instead for traversal!
	 * @return array The modified 'data' part.
	 * @see checkValue_flex_procInData_travDS()
	 * @todo Define visibility
	 */
	public function checkValue_flex_procInData($dataPart, $dataPart_current, $uploadedFiles, $dataStructArray, $pParams, $callBackFunc = '') {
		if (is_array($dataPart)) {
			foreach ($dataPart as $sKey => $sheetDef) {
				list($dataStruct, $actualSheet) = \TYPO3\CMS\Core\Utility\GeneralUtility::resolveSheetDefInDS($dataStructArray, $sKey);
				if (is_array($dataStruct) && $actualSheet == $sKey && is_array($sheetDef)) {
					foreach ($sheetDef as $lKey => $lData) {
						$this->checkValue_flex_procInData_travDS($dataPart[$sKey][$lKey], $dataPart_current[$sKey][$lKey], $uploadedFiles[$sKey][$lKey], $dataStruct['ROOT']['el'], $pParams, $callBackFunc, $sKey . '/' . $lKey . '/');
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
	 * @param array $dataValues New values (those being processed): Multidimensional Data array for sheet/language, passed by reference!
	 * @param array $dataValues_current Current values: Multidimensional Data array. May be empty array() if not needed (for callBackFunctions)
	 * @param array $uploadedFiles Uploaded files array for sheet/language. May be empty array() if not needed (for callBackFunctions)
	 * @param array $DSelements Data structure which fits the data array
	 * @param array $pParams A set of parameters to pass through for the calling of the evaluation functions / call back function
	 * @param string $callBackFunc Call back function, default is checkValue_SW(). If $this->callBackObj is set to an object, the callback function in that object is called instead.
	 * @param string $structurePath
	 * @return void
	 * @see checkValue_flex_procInData()
	 * @todo Define visibility
	 */
	public function checkValue_flex_procInData_travDS(&$dataValues, $dataValues_current, $uploadedFiles, $DSelements, $pParams, $callBackFunc, $structurePath) {
		if (is_array($DSelements)) {
			// For each DS element:
			foreach ($DSelements as $key => $dsConf) {
				// Array/Section:
				if ($DSelements[$key]['type'] == 'array') {
					if (is_array($dataValues[$key]['el'])) {
						if ($DSelements[$key]['section']) {
							$newIndexCounter = 0;
							foreach ($dataValues[$key]['el'] as $ik => $el) {
								if (is_array($el)) {
									if (!is_array($dataValues_current[$key]['el'])) {
										$dataValues_current[$key]['el'] = array();
									}
									$theKey = key($el);
									if (is_array($dataValues[$key]['el'][$ik][$theKey]['el'])) {
										$this->checkValue_flex_procInData_travDS($dataValues[$key]['el'][$ik][$theKey]['el'], is_array($dataValues_current[$key]['el'][$ik]) ? $dataValues_current[$key]['el'][$ik][$theKey]['el'] : array(), $uploadedFiles[$key]['el'][$ik][$theKey]['el'], $DSelements[$key]['el'][$theKey]['el'], $pParams, $callBackFunc, $structurePath . $key . '/el/' . $ik . '/' . $theKey . '/el/');
										// If element is added dynamically in the flexform of TCEforms, we map the ID-string to the next numerical index we can have in that particular section of elements:
										// The fact that the order changes is not important since order is controlled by a separately submitted index.
										if (substr($ik, 0, 3) == 'ID-') {
											$newIndexCounter++;
											// Set mapping index
											$this->newIndexMap[$ik] = (is_array($dataValues_current[$key]['el']) && count($dataValues_current[$key]['el']) ? max(array_keys($dataValues_current[$key]['el'])) : 0) + $newIndexCounter;
											// Transfer values
											$dataValues[$key]['el'][$this->newIndexMap[$ik]] = $dataValues[$key]['el'][$ik];
											// Unset original
											unset($dataValues[$key]['el'][$ik]);
										}
									}
								}
							}
						} else {
							if (!isset($dataValues[$key]['el'])) {
								$dataValues[$key]['el'] = array();
							}
							$this->checkValue_flex_procInData_travDS($dataValues[$key]['el'], $dataValues_current[$key]['el'], $uploadedFiles[$key]['el'], $DSelements[$key]['el'], $pParams, $callBackFunc, $structurePath . $key . '/el/');
						}
					}
				} else {
					if (is_array($dsConf['TCEforms']['config']) && is_array($dataValues[$key])) {
						foreach ($dataValues[$key] as $vKey => $data) {
							if ($callBackFunc) {
								if (is_object($this->callBackObj)) {
									$res = $this->callBackObj->{$callBackFunc}($pParams, $dsConf['TCEforms']['config'], $dataValues[$key][$vKey], $dataValues_current[$key][$vKey], $uploadedFiles[$key][$vKey], $structurePath . $key . '/' . $vKey . '/');
								} else {
									$res = $this->{$callBackFunc}($pParams, $dsConf['TCEforms']['config'], $dataValues[$key][$vKey], $dataValues_current[$key][$vKey], $uploadedFiles[$key][$vKey], $structurePath . $key . '/' . $vKey . '/');
								}
							} else {
								// Default
								list($CVtable, $CVid, $CVcurValue, $CVstatus, $CVrealPid, $CVrecFID, $CVtscPID) = $pParams;

								$additionalData = array(
									'flexFormId' => $CVrecFID,
									'flexFormPath' => trim(rtrim($structurePath, '/') . '/' . $key . '/' . $vKey, '/'),
								);

								$res = $this->checkValue_SW(array(), $dataValues[$key][$vKey], $dsConf['TCEforms']['config'], $CVtable, $CVid, $dataValues_current[$key][$vKey], $CVstatus, $CVrealPid, $CVrecFID, '', $uploadedFiles[$key][$vKey], $CVtscPID, $additionalData);
								// Look for RTE transformation of field:
								if ($dataValues[$key]['_TRANSFORM_' . $vKey] == 'RTE' && !$this->dontProcessTransformations) {
									// Unsetting trigger field - we absolutely don't want that into the data storage!
									unset($dataValues[$key]['_TRANSFORM_' . $vKey]);
									if (isset($res['value'])) {
										// Calculating/Retrieving some values here:
										list(, , $recFieldName) = explode(':', $CVrecFID);
										$theTypeString = \TYPO3\CMS\Backend\Utility\BackendUtility::getTCAtypeValue($CVtable, $this->checkValue_currentRecord);
										$specConf = \TYPO3\CMS\Backend\Utility\BackendUtility::getSpecConfParts('', $dsConf['TCEforms']['defaultExtras']);
										// Find, thisConfig:
										$RTEsetup = $this->BE_USER->getTSConfig('RTE', \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($CVtscPID));
										$thisConfig = \TYPO3\CMS\Backend\Utility\BackendUtility::RTEsetup($RTEsetup['properties'], $CVtable, $recFieldName, $theTypeString);
										// Get RTE object, draw form and set flag:
										$RTEobj = \TYPO3\CMS\Backend\Utility\BackendUtility::RTEgetObj();
										if (is_object($RTEobj)) {
											$res['value'] = $RTEobj->transformContent('db', $res['value'], $CVtable, $recFieldName, $this->checkValue_currentRecord, $specConf, $thisConfig, '', $CVrealPid);
										} else {
											debug('NO RTE OBJECT FOUND!');
										}
									}
								}
							}
							// Adding the value:
							if (isset($res['value'])) {
								$dataValues[$key][$vKey] = $res['value'];
							}
							// Finally, check if new and old values are different (or no .vDEFbase value is found) and if so, we record the vDEF value for diff'ing.
							// We do this after $dataValues has been updated since I expect that $dataValues_current holds evaluated values from database (so this must be the right value to compare with).
							if (substr($vKey, -9) != '.vDEFbase') {
								if ($this->clear_flexFormData_vDEFbase) {
									$dataValues[$key][$vKey . '.vDEFbase'] = '';
								} elseif ($this->updateModeL10NdiffData && $GLOBALS['TYPO3_CONF_VARS']['BE']['flexFormXMLincludeDiffBase'] && $vKey !== 'vDEF' && (strcmp($dataValues[$key][$vKey], $dataValues_current[$key][$vKey]) || !isset($dataValues_current[$key][($vKey . '.vDEFbase')]) || $this->updateModeL10NdiffData === 'FORCE_FFUPD')) {
									// Now, check if a vDEF value is submitted in the input data, if so we expect this has been processed prior to this operation (normally the case since those fields are higher in the form) and we can use that:
									if (isset($dataValues[$key]['vDEF'])) {
										$diffValue = $dataValues[$key]['vDEF'];
									} else {
										// If not found (for translators with no access to the default language) we use the one from the current-value data set:
										$diffValue = $dataValues_current[$key]['vDEF'];
									}
									// Setting the reference value for vDEF for this translation. This will be used for translation tools to make a diff between the vDEF and vDEFbase to see if an update would be fitting.
									$dataValues[$key][$vKey . '.vDEFbase'] = $this->updateModeL10NdiffDataClear ? '' : $diffValue;
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Returns data for inline fields.
	 *
	 * @param array $valueArray Current value array
	 * @param array $tcaFieldConf TCA field config
	 * @param integer $id Record id
	 * @param string $status Status string ('update' or 'new')
	 * @param string $table Table name, needs to be passed to \TYPO3\CMS\Core\Database\RelationHandler
	 * @param string $field The current field the values are modified for
	 * @param array $additionalData Additional data to be forwarded to sub-processors
	 * @return string Modified values
	 */
	protected function checkValue_inline_processDBdata($valueArray, $tcaFieldConf, $id, $status, $table, $field, array $additionalData = NULL) {
		$newValue = '';
		$foreignTable = $tcaFieldConf['foreign_table'];
		$valueArray = $this->applyFiltersToValues($tcaFieldConf, $valueArray);
		// Fetch the related child records using \TYPO3\CMS\Core\Database\RelationHandler
		/** @var $dbAnalysis \TYPO3\CMS\Core\Database\RelationHandler */
		$dbAnalysis = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\RelationHandler');
		$dbAnalysis->start(implode(',', $valueArray), $foreignTable, '', 0, $table, $tcaFieldConf);
		// If the localizationMode is set to 'keep', the children for the localized parent are kept as in the original untranslated record:
		$localizationMode = \TYPO3\CMS\Backend\Utility\BackendUtility::getInlineLocalizationMode($table, $tcaFieldConf);
		if ($localizationMode == 'keep' && $status == 'update') {
			// Fetch the current record and determine the original record:
			$row = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL($table, $id);
			if (is_array($row)) {
				$language = intval($row[$GLOBALS['TCA'][$table]['ctrl']['languageField']]);
				$transOrigPointer = intval($row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']]);
				// If language is set (e.g. 1) and also transOrigPointer (e.g. 123), use transOrigPointer as uid:
				if ($language > 0 && $transOrigPointer) {
					$id = $transOrigPointer;
					// If we're in active localizationMode 'keep', prevent from writing data to the field of the parent record:
					// (on removing the localized parent, the original (untranslated) children would then also be removed)
					$keepTranslation = TRUE;
				}
			}
		}
		// IRRE with a pointer field (database normalization):
		if ($tcaFieldConf['foreign_field']) {
			// if the record was imported, sorting was also imported, so skip this
			$skipSorting = $this->callFromImpExp ? TRUE : FALSE;
			// update record in intermediate table (sorting & pointer uid to parent record)
			$dbAnalysis->writeForeignField($tcaFieldConf, $id, 0, $skipSorting);
			$newValue = $keepTranslation ? 0 : $dbAnalysis->countItems(FALSE);
		} else {
			if ($this->getInlineFieldType($tcaFieldConf) == 'mm') {
				// In order to fully support all the MM stuff, directly call checkValue_group_select_processDBdata instead of repeating the needed code here
				$valueArray = $this->checkValue_group_select_processDBdata($valueArray, $tcaFieldConf, $id, $status, 'select', $table, $field);
				$newValue = $keepTranslation ? 0 : $valueArray[0];
			} else {
				$valueArray = $dbAnalysis->getValueArray();
				// Checking that the number of items is correct:
				$valueArray = $this->checkValue_checkMax($tcaFieldConf, $valueArray);
				// If a valid translation of the 'keep' mode is active, update relations in the original(!) record:
				if ($keepTranslation) {
					$this->updateDB($table, $transOrigPointer, array($field => implode(',', $valueArray)));
				} else {
					$newValue = implode(',', $valueArray);
				}
			}
		}
		return $newValue;
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
	 * @return void
	 */
	public function process_cmdmap() {
		// Editing frozen:
		if ($this->BE_USER->workspace !== 0 && $this->BE_USER->workspaceRec['freeze']) {
			$this->newlog('All editing in this workspace has been frozen!', 1);
			return FALSE;
		}
		// Hook initialization:
		$hookObjectsArr = array();
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'] as $classRef) {
				$hookObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
				if (method_exists($hookObj, 'processCmdmap_beforeStart')) {
					$hookObj->processCmdmap_beforeStart($this);
				}
				$hookObjectsArr[] = $hookObj;
			}
		}
		// Traverse command map:
		foreach (array_keys($this->cmdmap) as $table) {
			// Check if the table may be modified!
			$modifyAccessList = $this->checkModifyAccessList($table);
			if (!$modifyAccessList) {
				$id = 0;
				$this->log($table, $id, 2, 0, 1, 'Attempt to modify table \'%s\' without permission', 1, array($table));
			}
			// FIXME: $id not set here (Comment added by Sebastian Kurfürst)
			// Check basic permissions and circumstances:
			if (isset($GLOBALS['TCA'][$table]) && !$this->tableReadOnly($table) && is_array($this->cmdmap[$table]) && $modifyAccessList) {
				// Traverse the command map:
				foreach ($this->cmdmap[$table] as $id => $incomingCmdArray) {
					if (is_array($incomingCmdArray)) {
						// have found a command.
						// Get command and value (notice, only one command is observed at a time!):
						reset($incomingCmdArray);
						$command = key($incomingCmdArray);
						$value = current($incomingCmdArray);
						foreach ($hookObjectsArr as $hookObj) {
							if (method_exists($hookObj, 'processCmdmap_preProcess')) {
								$hookObj->processCmdmap_preProcess($command, $table, $id, $value, $this);
							}
						}
						// Init copyMapping array:
						// Must clear this array before call from here to those functions:
						// Contains mapping information between new and old id numbers.
						$this->copyMappingArray = array();
						// process the command
						$commandIsProcessed = FALSE;
						foreach ($hookObjectsArr as $hookObj) {
							if (method_exists($hookObj, 'processCmdmap')) {
								$hookObj->processCmdmap($command, $table, $id, $value, $commandIsProcessed, $this);
							}
						}
						// Only execute default commands if a hook hasn't been processed the command already
						if (!$commandIsProcessed) {
							// Branch, based on command
							switch ($command) {
							case 'move':
								$this->moveRecord($table, $id, $value);
								break;
							case 'copy':
								if ($table === 'pages') {
									$this->copyPages($id, $value);
								} else {
									$this->copyRecord($table, $id, $value, 1);
								}
								break;
							case 'localize':
								$this->localize($table, $id, $value);
								break;
							case 'inlineLocalizeSynchronize':
								$this->inlineLocalizeSynchronize($table, $id, $value);
								break;
							case 'delete':
								$this->deleteAction($table, $id);
								break;
							case 'undelete':
								$this->undeleteRecord($table, $id);
								break;
							}
						}
						foreach ($hookObjectsArr as $hookObj) {
							if (method_exists($hookObj, 'processCmdmap_postProcess')) {
								$hookObj->processCmdmap_postProcess($command, $table, $id, $value, $this);
							}
						}
						// Merging the copy-array info together for remapping purposes.
						$this->copyMappingArray_merged = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($this->copyMappingArray_merged, $this->copyMappingArray);
					}
				}
			}
		}
		// Finally, before exit, check if there are ID references to remap.
		// This might be the case if versioning or copying has taken place!
		$this->remapListedDBRecords();
		$this->processRemapStack();
		foreach ($hookObjectsArr as $hookObj) {
			if (method_exists($hookObj, 'processCmdmap_afterFinish')) {
				$hookObj->processCmdmap_afterFinish($this);
			}
		}
		if ($this->isOuterMostInstance()) {
			$this->resetNestedElementCalls();
		}
	}

	/*********************************************
	 *
	 * Cmd: Copying
	 *
	 ********************************************/
	/**
	 * Copying a single record
	 *
	 * @param string $table Element table
	 * @param integer $uid Element UID
	 * @param integer $destPid: >=0 then it points to a page-id on which to insert the record (as the first element). <0 then it points to a uid from its own table after which to insert it (works if
	 * @param boolean $first Is a flag set, if the record copied is NOT a 'slave' to another record copied. That is, if this record was asked to be copied in the cmd-array
	 * @param array $overrideValues Associative array with field/value pairs to override directly. Notice; Fields must exist in the table record and NOT be among excluded fields!
	 * @param string $excludeFields Commalist of fields to exclude from the copy process (might get default values)
	 * @param integer $language Language ID (from sys_language table)
	 * @return integer ID of new record, if any
	 * @todo Define visibility
	 */
	public function copyRecord($table, $uid, $destPid, $first = 0, $overrideValues = array(), $excludeFields = '', $language = 0) {
		$uid = ($origUid = intval($uid));
		// Only copy if the table is defined in $GLOBALS['TCA'], a uid is given and the record wasn't copied before:
		if ($GLOBALS['TCA'][$table] && $uid && !$this->isRecordCopied($table, $uid)) {
			// This checks if the record can be selected which is all that a copy action requires.
			if ($this->doesRecordExist($table, $uid, 'show')) {
				$fullLanguageCheckNeeded = $table != 'pages';
				//Used to check language and general editing rights
				if ($language > 0 && $this->BE_USER->checkLanguageAccess($language) || $this->BE_USER->recordEditAccessInternals($table, $uid, FALSE, FALSE, $fullLanguageCheckNeeded)) {
					$data = array();
					$nonFields = array_unique(\TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', 'uid,perms_userid,perms_groupid,perms_user,perms_group,perms_everybody,t3ver_oid,t3ver_wsid,t3ver_id,t3ver_label,t3ver_state,t3ver_count,t3ver_stage,t3ver_tstamp,' . $excludeFields, 1));
					// So it copies (and localized) content from workspace...
					$row = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL($table, $uid);
					if (is_array($row)) {
						// Initializing:
						$theNewID = uniqid('NEW');
						$enableField = isset($GLOBALS['TCA'][$table]['ctrl']['enablecolumns']) ? $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'] : '';
						$headerField = $GLOBALS['TCA'][$table]['ctrl']['label'];
						// Getting default data:
						$defaultData = $this->newFieldArray($table);
						// Getting "copy-after" fields if applicable:
						$copyAfterFields = $destPid < 0 ? $this->fixCopyAfterDuplFields($table, $uid, abs($destPid), 0) : array();
						// Page TSconfig related:
						// NOT using \TYPO3\CMS\Backend\Utility\BackendUtility::getTSCpid() because we need the real pid - not the ID of a page, if the input is a page...
						$tscPID = \TYPO3\CMS\Backend\Utility\BackendUtility::getTSconfig_pidValue($table, $uid, $destPid);
						$TSConfig = $this->getTCEMAIN_TSconfig($tscPID);
						$tE = $this->getTableEntries($table, $TSConfig);
						// Traverse ALL fields of the selected record:
						foreach ($row as $field => $value) {
							if (!in_array($field, $nonFields)) {
								// Get TCA configuration for the field:
								$conf = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
								// Preparation/Processing of the value:
								// "pid" is hardcoded of course:
								if ($field == 'pid') {
									$value = $destPid;
								} elseif (isset($overrideValues[$field])) {
									// Override value...
									$value = $overrideValues[$field];
								} elseif (isset($copyAfterFields[$field])) {
									// Copy-after value if available:
									$value = $copyAfterFields[$field];
								} elseif ($GLOBALS['TCA'][$table]['ctrl']['setToDefaultOnCopy'] && \TYPO3\CMS\Core\Utility\GeneralUtility::inList($GLOBALS['TCA'][$table]['ctrl']['setToDefaultOnCopy'], $field)) {
									$value = $defaultData[$field];
								} else {
									// Hide at copy may override:
									if ($first && $field == $enableField && $GLOBALS['TCA'][$table]['ctrl']['hideAtCopy'] && !$this->neverHideAtCopy && !$tE['disableHideAtCopy']) {
										$value = 1;
									}
									// Prepend label on copy:
									if ($first && $field == $headerField && $GLOBALS['TCA'][$table]['ctrl']['prependAtCopy'] && !$tE['disablePrependAtCopy']) {
										$value = $this->getCopyHeader($table, $this->resolvePid($table, $destPid), $field, $this->clearPrefixFromValue($table, $value), 0);
									}
									// Processing based on the TCA config field type (files, references, flexforms...)
									$value = $this->copyRecord_procBasedOnFieldType($table, $uid, $field, $value, $row, $conf, $tscPID, $language);
								}
								// Add value to array.
								$data[$table][$theNewID][$field] = $value;
							}
						}
						// Overriding values:
						if ($GLOBALS['TCA'][$table]['ctrl']['editlock']) {
							$data[$table][$theNewID][$GLOBALS['TCA'][$table]['ctrl']['editlock']] = 0;
						}
						// Setting original UID:
						if ($GLOBALS['TCA'][$table]['ctrl']['origUid']) {
							$data[$table][$theNewID][$GLOBALS['TCA'][$table]['ctrl']['origUid']] = $uid;
						}
						// Do the copy by simply submitting the array through TCEmain:
						/** @var $copyTCE \TYPO3\CMS\Core\DataHandling\DataHandler */
						$copyTCE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
						$copyTCE->stripslashes_values = 0;
						$copyTCE->copyTree = $this->copyTree;
						// Copy forth the cached TSconfig
						$copyTCE->cachedTSconfig = $this->cachedTSconfig;
						// Transformations should NOT be carried out during copy
						$copyTCE->dontProcessTransformations = 1;
						$copyTCE->start($data, '', $this->BE_USER);
						$copyTCE->process_datamap();
						// Getting the new UID:
						$theNewSQLID = $copyTCE->substNEWwithIDs[$theNewID];
						if ($theNewSQLID) {
							$this->copyRecord_fixRTEmagicImages($table, \TYPO3\CMS\Backend\Utility\BackendUtility::wsMapId($table, $theNewSQLID));
							$this->copyMappingArray[$table][$origUid] = $theNewSQLID;
							// Keep automatically versionized record information:
							if (isset($copyTCE->autoVersionIdMap[$table][$theNewSQLID])) {
								$this->autoVersionIdMap[$table][$theNewSQLID] = $copyTCE->autoVersionIdMap[$table][$theNewSQLID];
							}
						}
						// Copy back the cached TSconfig
						$this->cachedTSconfig = $copyTCE->cachedTSconfig;
						$this->errorLog = array_merge($this->errorLog, $copyTCE->errorLog);
						unset($copyTCE);
						if ($language == 0) {
							//repointing the new translation records to the parent record we just created
							$overrideValues[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']] = $theNewSQLID;
							$this->copyL10nOverlayRecords($table, $uid, $destPid, $first, $overrideValues, $excludeFields);
						}
						return $theNewSQLID;
					} else {
						$this->log($table, $uid, 3, 0, 1, 'Attempt to copy record that did not exist!');
					}
				} else {
					$this->log($table, $uid, 3, 0, 1, 'Attempt to copy record without having permissions to do so. [' . $this->BE_USER->errorMsg . '].');
				}
			} else {
				$this->log($table, $uid, 3, 0, 1, 'Attempt to copy record without permission');
			}
		}
	}

	/**
	 * Copying pages
	 * Main function for copying pages.
	 *
	 * @param integer $uid Page UID to copy
	 * @param integer $destPid Destination PID: >=0 then it points to a page-id on which to insert the record (as the first element). <0 then it points to a uid from its own table after which to insert it (works if
	 * @return void
	 * @todo Define visibility
	 */
	public function copyPages($uid, $destPid) {
		// Initialize:
		$uid = intval($uid);
		$destPid = intval($destPid);
		// Finding list of tables to copy.
		// These are the tables, the user may modify
		$copyTablesArray = $this->admin ? $this->compileAdminTables() : explode(',', $this->BE_USER->groupData['tables_modify']);
		// If not all tables are allowed then make a list of allowed tables: That is the tables that figure in both allowed tables AND the copyTable-list
		if (!strstr($this->copyWhichTables, '*')) {
			foreach ($copyTablesArray as $k => $table) {
				// Pages are always going...
				if (!$table || !\TYPO3\CMS\Core\Utility\GeneralUtility::inList(($this->copyWhichTables . ',pages'), $table)) {
					unset($copyTablesArray[$k]);
				}
			}
		}
		$copyTablesArray = array_unique($copyTablesArray);
		// Begin to copy pages if we're allowed to:
		if ($this->admin || in_array('pages', $copyTablesArray)) {
			// Copy this page we're on. And set first-flag (this will trigger that the record is hidden if that is configured)!
			$theNewRootID = $this->copySpecificPage($uid, $destPid, $copyTablesArray, 1);
			// If we're going to copy recursively...:
			if ($theNewRootID && $this->copyTree) {
				// Get ALL subpages to copy (read-permissions are respected!):
				$CPtable = $this->int_pageTreeInfo(array(), $uid, intval($this->copyTree), $theNewRootID);
				// Now copying the subpages:
				foreach ($CPtable as $thePageUid => $thePagePid) {
					$newPid = $this->copyMappingArray['pages'][$thePagePid];
					if (isset($newPid)) {
						$this->copySpecificPage($thePageUid, $newPid, $copyTablesArray);
					} else {
						$this->log('pages', $uid, 5, 0, 1, 'Something went wrong during copying branch');
						break;
					}
				}
			}
		} else {
			$this->log('pages', $uid, 5, 0, 1, 'Attempt to copy page without permission to this table');
		}
	}

	/**
	 * Copying a single page ($uid) to $destPid and all tables in the array copyTablesArray.
	 *
	 * @param integer $uid Page uid
	 * @param integer $destPid Destination PID: >=0 then it points to a page-id on which to insert the record (as the first element). <0 then it points to a uid from its own table after which to insert it (works if
	 * @param array $copyTablesArray Table on pages to copy along with the page.
	 * @param boolean $first Is a flag set, if the record copied is NOT a 'slave' to another record copied. That is, if this record was asked to be copied in the cmd-array
	 * @return integer The id of the new page, if applicable.
	 * @todo Define visibility
	 */
	public function copySpecificPage($uid, $destPid, $copyTablesArray, $first = 0) {
		// Copy the page itself:
		$theNewRootID = $this->copyRecord('pages', $uid, $destPid, $first);
		// If a new page was created upon the copy operation we will proceed with all the tables ON that page:
		if ($theNewRootID) {
			foreach ($copyTablesArray as $table) {
				// All records under the page is copied.
				if ($table && is_array($GLOBALS['TCA'][$table]) && $table != 'pages') {
					$mres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', $table, 'pid=' . intval($uid) . $this->deleteClause($table), '', $GLOBALS['TCA'][$table]['ctrl']['sortby'] ? $GLOBALS['TCA'][$table]['ctrl']['sortby'] . ' DESC' : '');
					while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($mres)) {
						// Copying each of the underlying records...
						$this->copyRecord($table, $row['uid'], $theNewRootID);
					}
					$GLOBALS['TYPO3_DB']->sql_free_result($mres);
				}
			}
			return $theNewRootID;
		}
	}

	/**
	 * Copying records, but makes a "raw" copy of a record.
	 * Basically the only thing observed is field processing like the copying of files and correction of ids. All other fields are 1-1 copied.
	 * Technically the copy is made with THIS instance of the tcemain class contrary to copyRecord() which creates a new instance and uses the processData() function.
	 * The copy is created by insertNewCopyVersion() which bypasses most of the regular input checking associated with processData() - maybe copyRecord() should even do this as well!?
	 * This function is used to create new versions of a record.
	 * NOTICE: DOES NOT CHECK PERMISSIONS to create! And since page permissions are just passed through and not changed to the user who executes the copy we cannot enforce permissions without getting an incomplete copy - unless we change permissions of course.
	 *
	 * @param string $table Element table
	 * @param integer $uid Element UID
	 * @param integer $pid Element PID (real PID, not checked)
	 * @param array $overrideArray Override array - must NOT contain any fields not in the table!
	 * @return array $workspaceOptions Options to be forwarded if actions happen on a workspace currently
	 * @return integer Returns the new ID of the record (if applicable)
	 * @todo Define visibility
	 */
	public function copyRecord_raw($table, $uid, $pid, $overrideArray = array(), array $workspaceOptions = array()) {
		$uid = intval($uid);
		// Stop any actions if the record is marked to be deleted:
		// (this can occur if IRRE elements are versionized and child elements are removed)
		if ($this->isElementToBeDeleted($table, $uid)) {
			return NULL;
		}
		// Only copy if the table is defined in TCA, a uid is given and the record wasn't copied before:
		if ($GLOBALS['TCA'][$table] && $uid && !$this->isRecordCopied($table, $uid)) {
			if ($this->doesRecordExist($table, $uid, 'show')) {
				// Set up fields which should not be processed. They are still written - just passed through no-questions-asked!
				$nonFields = array('uid', 'pid', 't3ver_id', 't3ver_oid', 't3ver_wsid', 't3ver_label', 't3ver_state', 't3ver_count', 't3ver_stage', 't3ver_tstamp', 'perms_userid', 'perms_groupid', 'perms_user', 'perms_group', 'perms_everybody');
				// Select main record:
				$row = $this->recordInfo($table, $uid, '*');
				if (is_array($row)) {
					// Merge in override array.
					$row = array_merge($row, $overrideArray);
					// Traverse ALL fields of the selected record:
					foreach ($row as $field => $value) {
						if (!in_array($field, $nonFields)) {
							// Get TCA configuration for the field:
							$conf = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
							if (is_array($conf)) {
								// Processing based on the TCA config field type (files, references, flexforms...)
								$value = $this->copyRecord_procBasedOnFieldType($table, $uid, $field, $value, $row, $conf, $pid, 0, $workspaceOptions);
							}
							// Add value to array.
							$row[$field] = $value;
						}
					}
					// Force versioning related fields:
					$row['pid'] = $pid;
					// Setting original UID:
					if ($GLOBALS['TCA'][$table]['ctrl']['origUid']) {
						$row[$GLOBALS['TCA'][$table]['ctrl']['origUid']] = $uid;
					}
					// Do the copy by internal function
					$theNewSQLID = $this->insertNewCopyVersion($table, $row, $pid);
					if ($theNewSQLID) {
						$this->dbAnalysisStoreExec();
						$this->dbAnalysisStore = array();
						$this->copyRecord_fixRTEmagicImages($table, \TYPO3\CMS\Backend\Utility\BackendUtility::wsMapId($table, $theNewSQLID));
						return $this->copyMappingArray[$table][$uid] = $theNewSQLID;
					}
				} else {
					$this->log($table, $uid, 3, 0, 1, 'Attempt to rawcopy/versionize record that did not exist!');
				}
			} else {
				$this->log($table, $uid, 3, 0, 1, 'Attempt to rawcopy/versionize record without copy permission');
			}
		}
	}

	/**
	 * Inserts a record in the database, passing TCA configuration values through checkValue() but otherwise does NOTHING and checks nothing regarding permissions.
	 * Passes the "version" parameter to insertDB() so the copy will look like a new version in the log - should probably be changed or modified a bit for more broad usage...
	 *
	 * @param string $table Table name
	 * @param array $fieldArray Field array to insert as a record
	 * @param integer $realPid The value of PID field.  -1 is indication that we are creating a new version!
	 * @return integer Returns the new ID of the record (if applicable)
	 * @todo Define visibility
	 */
	public function insertNewCopyVersion($table, $fieldArray, $realPid) {
		$id = uniqid('NEW');
		// $fieldArray is set as current record.
		// The point is that when new records are created as copies with flex type fields there might be a field containing information about which DataStructure to use and without that information the flexforms cannot be correctly processed.... This should be OK since the $checkValueRecord is used by the flexform evaluation only anyways...
		$this->checkValue_currentRecord = $fieldArray;
		// Makes sure that transformations aren't processed on the copy.
		$backupDontProcessTransformations = $this->dontProcessTransformations;
		$this->dontProcessTransformations = TRUE;
		// Traverse record and input-process each value:
		foreach ($fieldArray as $field => $fieldValue) {
			if (isset($GLOBALS['TCA'][$table]['columns'][$field])) {
				// Evaluating the value.
				$res = $this->checkValue($table, $field, $fieldValue, $id, 'new', $realPid, 0);
				if (isset($res['value'])) {
					$fieldArray[$field] = $res['value'];
				}
			}
		}
		// System fields being set:
		if ($GLOBALS['TCA'][$table]['ctrl']['crdate']) {
			$fieldArray[$GLOBALS['TCA'][$table]['ctrl']['crdate']] = $GLOBALS['EXEC_TIME'];
		}
		if ($GLOBALS['TCA'][$table]['ctrl']['cruser_id']) {
			$fieldArray[$GLOBALS['TCA'][$table]['ctrl']['cruser_id']] = $this->userid;
		}
		if ($GLOBALS['TCA'][$table]['ctrl']['tstamp']) {
			$fieldArray[$GLOBALS['TCA'][$table]['ctrl']['tstamp']] = $GLOBALS['EXEC_TIME'];
		}
		// Finally, insert record:
		$this->insertDB($table, $id, $fieldArray, TRUE);
		// Resets dontProcessTransformations to the previous state.
		$this->dontProcessTransformations = $backupDontProcessTransformations;
		// Return new id:
		return $this->substNEWwithIDs[$id];
	}

	/**
	 * Processing/Preparing content for copyRecord() function
	 *
	 * @param string $table Table name
	 * @param integer $uid Record uid
	 * @param string $field Field name being processed
	 * @param string $value Input value to be processed.
	 * @param array $row Record array
	 * @param array $conf TCA field configuration
	 * @param integer $realDestPid Real page id (pid) the record is copied to
	 * @param integer $language Language ID (from sys_language table) used in the duplicated record
	 * @return array $workspaceOptions Options to be forwarded if actions happen on a workspace currently
	 * @access private
	 * @see copyRecord()
	 * @todo Define visibility
	 */
	public function copyRecord_procBasedOnFieldType($table, $uid, $field, $value, $row, $conf, $realDestPid, $language = 0, array $workspaceOptions = array()) {
		// Process references and files, currently that means only the files, prepending absolute paths (so the TCEmain engine will detect the file as new and one that should be made into a copy)
		$value = $this->copyRecord_procFilesRefs($conf, $uid, $value);
		$inlineSubType = $this->getInlineFieldType($conf);
		// Get the localization mode for the current (parent) record (keep|select):
		$localizationMode = \TYPO3\CMS\Backend\Utility\BackendUtility::getInlineLocalizationMode($table, $field);
		// Register if there are references to take care of or MM is used on an inline field (no change to value):
		if ($this->isReferenceField($conf) || $inlineSubType == 'mm') {
			$allowedTables = $conf['type'] == 'group' ? $conf['allowed'] : $conf['foreign_table'] . ',' . $conf['neg_foreign_table'];
			$prependName = $conf['type'] == 'group' ? $conf['prepend_tname'] : $conf['neg_foreign_table'];
			$mmTable = isset($conf['MM']) && $conf['MM'] ? $conf['MM'] : '';
			$localizeForeignTable = isset($conf['foreign_table']) && \TYPO3\CMS\Backend\Utility\BackendUtility::isTableLocalizable($conf['foreign_table']);
			$localizeReferences = $localizeForeignTable && isset($conf['localizeReferencesAtParentLocalization']) && $conf['localizeReferencesAtParentLocalization'];
			$localizeChildren = $localizeForeignTable && isset($conf['behaviour']['localizeChildrenAtParentLocalization']) && $conf['behaviour']['localizeChildrenAtParentLocalization'];
			/** @var $dbAnalysis \TYPO3\CMS\Core\Database\RelationHandler */
			$dbAnalysis = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\RelationHandler');
			$dbAnalysis->start($value, $allowedTables, $mmTable, $uid, $table, $conf);
			// Localize referenced records of select fields:
			if ($language > 0 && ($localizeReferences && empty($mmTable) || $localizeChildren && $localizationMode === 'select' && $inlineSubType === 'mm')) {
				foreach ($dbAnalysis->itemArray as $index => $item) {
					// Since select fields can reference many records, check whether there's already a localization:
					$recordLocalization = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordLocalization($item['table'], $item['id'], $language);
					if ($recordLocalization) {
						$dbAnalysis->itemArray[$index]['id'] = $recordLocalization[0]['uid'];
					} elseif ($this->isNestedElementCallRegistered($item['table'], $item['id'], 'localize') === FALSE) {
						$dbAnalysis->itemArray[$index]['id'] = $this->localize($item['table'], $item['id'], $language);
					}
				}
				$value = implode(',', $dbAnalysis->getValueArray($prependName));
			} elseif ($language > 0 && $localizeChildren === FALSE && $localizationMode === 'select' && $inlineSubType === 'mm') {
				foreach ($dbAnalysis->itemArray as $index => $item) {
					// Since select fields can reference many records, check whether there's already a localization:
					$recordLocalization = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordLocalization($item['table'], $item['id'], $language);
					if ($recordLocalization) {
						$dbAnalysis->itemArray[$index]['id'] = $recordLocalization[0]['uid'];
					} elseif ($this->isNestedElementCallRegistered($item['table'], $item['id'], 'localize') === FALSE) {
						unset($dbAnalysis->itemArray[$index]);
					}
				}
				$value = implode(',', $dbAnalysis->getValueArray($prependName));
			} elseif ($mmTable) {
				$value = implode(',', $dbAnalysis->getValueArray($prependName));
			}
			// Setting the value in this array will notify the remapListedDBRecords() function that this field MAY need references to be corrected
			if ($value) {
				$this->registerDBList[$table][$uid][$field] = $value;
			}
		} elseif ($inlineSubType !== FALSE) {
			// Localization in mode 'keep', isn't a real localization, but keeps the children of the original parent record:
			if ($language > 0 && $localizationMode == 'keep') {
				$value = $inlineSubType == 'field' ? 0 : '';
			} else {
				// Fetch the related child records using \TYPO3\CMS\Core\Database\RelationHandler
				/** @var $dbAnalysis \TYPO3\CMS\Core\Database\RelationHandler */
				$dbAnalysis = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\RelationHandler');
				$dbAnalysis->start($value, $conf['foreign_table'], '', $uid, $table, $conf);
				// Walk through the items, copy them and remember the new id:
				foreach ($dbAnalysis->itemArray as $k => $v) {
					// If language is set and differs from original record, this isn't a copy action but a localization of our parent/ancestor:
					if ($language > 0 && \TYPO3\CMS\Backend\Utility\BackendUtility::isTableLocalizable($table) && $language != $row[$GLOBALS['TCA'][$table]['ctrl']['languageField']]) {
						// If children should be localized when the parent gets localized the first time, just do it:
						if ($localizationMode != FALSE && isset($conf['behaviour']['localizeChildrenAtParentLocalization']) && $conf['behaviour']['localizeChildrenAtParentLocalization']) {
							$newId = $this->localize($v['table'], $v['id'], $language);
						}
					} else {
						if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($realDestPid)) {
							$newId = $this->copyRecord($v['table'], $v['id'], -$v['id']);
						} elseif ($realDestPid == -1 && \TYPO3\CMS\Backend\Utility\BackendUtility::isTableWorkspaceEnabled($v['table'])) {
							$workspaceVersion = \TYPO3\CMS\Backend\Utility\BackendUtility::getWorkspaceVersionOfRecord($this->BE_USER->workspace, $v['table'], $v['id'], 'uid');
							// If workspace version does not exist, create a new one:
							if ($workspaceVersion === FALSE) {
								$newId = $this->versionizeRecord($v['table'], $v['id'], isset($workspaceOptions['label']) ? $workspaceOptions['label'] : 'Auto-created for WS #' . $this->BE_USER->workspace, isset($workspaceOptions['delete']) ? $workspaceOptions['delete'] : FALSE);
							} else {
								$newId = $workspaceVersion['uid'];
							}
						} else {
							$newId = $this->copyRecord_raw($v['table'], $v['id'], $realDestPid, array(), $workspaceOptions);
						}
					}
					// If the current field is set on a page record, update the pid of related child records:
					if ($table == 'pages') {
						$this->registerDBPids[$v['table']][$v['id']] = $uid;
					} elseif (isset($this->registerDBPids[$table][$uid])) {
						$this->registerDBPids[$v['table']][$v['id']] = $this->registerDBPids[$table][$uid];
					}
					$dbAnalysis->itemArray[$k]['id'] = $newId;
				}
				// Store the new values, we will set up the uids for the subtype later on (exception keep localization from original record):
				$value = implode(',', $dbAnalysis->getValueArray());
				$this->registerDBList[$table][$uid][$field] = $value;
			}
		}
		// For "flex" fieldtypes we need to traverse the structure for two reasons: If there are file references they have to be prepended with absolute paths and if there are database reference they MIGHT need to be remapped (still done in remapListedDBRecords())
		if ($conf['type'] == 'flex') {
			// Get current value array:
			$dataStructArray = \TYPO3\CMS\Backend\Utility\BackendUtility::getFlexFormDS($conf, $row, $table);
			$currentValueArray = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($value);
			// Traversing the XML structure, processing files:
			if (is_array($currentValueArray)) {
				$currentValueArray['data'] = $this->checkValue_flex_procInData($currentValueArray['data'], array(), array(), $dataStructArray, array($table, $uid, $field, $realDestPid), 'copyRecord_flexFormCallBack');
				// Setting value as an array! -> which means the input will be processed according to the 'flex' type when the new copy is created.
				$value = $currentValueArray;
			}
		}
		return $value;
	}

	/**
	 * Callback function for traversing the FlexForm structure in relation to creating copied files of file relations inside of flex form structures.
	 *
	 * @param array $pParams Array of parameters in num-indexes: table, uid, field
	 * @param array $dsConf TCA field configuration (from Data Structure XML)
	 * @param string $dataValue The value of the flexForm field
	 * @param string $dataValue_ext1 Not used.
	 * @param string $dataValue_ext2 Not used.
	 * @return array Result array with key "value" containing the value of the processing.
	 * @see copyRecord(), checkValue_flex_procInData_travDS()
	 * @todo Define visibility
	 */
	public function copyRecord_flexFormCallBack($pParams, $dsConf, $dataValue, $dataValue_ext1, $dataValue_ext2) {
		// Extract parameters:
		list($table, $uid, $field, $realDestPid) = $pParams;
		// Process references and files, currently that means only the files, prepending absolute paths:
		$dataValue = $this->copyRecord_procFilesRefs($dsConf, $uid, $dataValue);
		// If references are set for this field, set flag so they can be corrected later (in ->remapListedDBRecords())
		if ($this->isReferenceField($dsConf) && strlen($dataValue)) {
			$dataValue = $this->copyRecord_procBasedOnFieldType($table, $uid, $field, $dataValue, array(), $dsConf, $realDestPid);
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
	 * @param array $conf TCE field config
	 * @param integer $uid Record UID
	 * @param string $value Field value (eg. list of files)
	 * @return string The (possibly modified) value
	 * @see copyRecord(), copyRecord_flexFormCallBack()
	 * @todo Define visibility
	 */
	public function copyRecord_procFilesRefs($conf, $uid, $value) {
		// Prepend absolute paths to files:
		if ($conf['type'] == 'group' && ($conf['internal_type'] == 'file' || $conf['internal_type'] == 'file_reference')) {
			// Get an array with files as values:
			if ($conf['MM']) {
				$theFileValues = array();
				/** @var $dbAnalysis \TYPO3\CMS\Core\Database\RelationHandler */
				$dbAnalysis = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\RelationHandler');
				$dbAnalysis->start('', 'files', $conf['MM'], $uid);
				foreach ($dbAnalysis->itemArray as $somekey => $someval) {
					if ($someval['id']) {
						$theFileValues[] = $someval['id'];
					}
				}
			} else {
				$theFileValues = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $value, 1);
			}
			// Traverse this array of files:
			$uploadFolder = $conf['internal_type'] == 'file' ? $conf['uploadfolder'] : '';
			$dest = $this->destPathFromUploadFolder($uploadFolder);
			$newValue = array();
			foreach ($theFileValues as $file) {
				if (trim($file)) {
					$realFile = str_replace('//', '/', $dest . '/' . trim($file));
					if (@is_file($realFile)) {
						$newValue[] = $realFile;
					}
				}
			}
			// Implode the new filelist into the new value (all files have absolute paths now which means they will get copied when entering TCEmain as new values...)
			$value = implode(',', $newValue);
		}
		// Return the new value:
		return $value;
	}

	/**
	 * Copies any "RTEmagic" image files found in record with table/id to new names.
	 * Usage: After copying a record this function should be called to search for "RTEmagic"-images inside the record. If such are found they should be duplicated to new names so all records have a 1-1 relation to them.
	 * Reason for copying RTEmagic files: a) if you remove an RTEmagic image from a record it will remove the file - any other record using it will have a lost reference! b) RTEmagic images keeps an original and a copy. The copy always is re-calculated to have the correct physical measures as the HTML tag inserting it defines. This is calculated from the original. Two records using the same image could have difference HTML-width/heights for the image and the copy could only comply with one of them. If you don't want a 1-1 relation you should NOT use RTEmagic files but just insert it as a normal file reference to a file inside fileadmin/ folder
	 *
	 * @param string $table Table name
	 * @param integer $theNewSQLID Record UID
	 * @return void
	 * @todo Define visibility
	 */
	public function copyRecord_fixRTEmagicImages($table, $theNewSQLID) {
		// Creating fileFunc object.
		if (!$this->fileFunc) {
			$this->fileFunc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Utility\\File\\BasicFileUtility');
			$this->include_filefunctions = 1;
		}
		// Select all RTEmagic files in the reference table from the table/ID
		$recs = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'sys_refindex', 'ref_table=' . $GLOBALS['TYPO3_DB']->fullQuoteStr('_FILE', 'sys_refindex') . ' AND ref_string LIKE ' . $GLOBALS['TYPO3_DB']->fullQuoteStr('%/RTEmagic%', 'sys_refindex') . ' AND softref_key=' . $GLOBALS['TYPO3_DB']->fullQuoteStr('images', 'sys_refindex') . ' AND tablename=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($table, 'sys_refindex') . ' AND recuid=' . intval($theNewSQLID), '', 'sorting DESC');
		// Traverse the files found and copy them:
		if (is_array($recs)) {
			foreach ($recs as $rec) {
				$filename = basename($rec['ref_string']);
				$fileInfo = array();
				if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($filename, 'RTEmagicC_')) {
					$fileInfo['exists'] = @is_file((PATH_site . $rec['ref_string']));
					$fileInfo['original'] = substr($rec['ref_string'], 0, -strlen($filename)) . 'RTEmagicP_' . preg_replace('/\\.[[:alnum:]]+$/', '', substr($filename, 10));
					$fileInfo['original_exists'] = @is_file((PATH_site . $fileInfo['original']));
					// CODE from tx_impexp and class.rte_images.php adapted for use here:
					if ($fileInfo['exists'] && $fileInfo['original_exists']) {
						// Initialize; Get directory prefix for file and set the original name:
						$dirPrefix = dirname($rec['ref_string']) . '/';
						$rteOrigName = basename($fileInfo['original']);
						// If filename looks like an RTE file, and the directory is in "uploads/", then process as a RTE file!
						if ($rteOrigName && \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($dirPrefix, 'uploads/') && @is_dir((PATH_site . $dirPrefix))) {
							// RTE:
							// From the "original" RTE filename, produce a new "original" destination filename which is unused.
							$origDestName = $this->fileFunc->getUniqueName($rteOrigName, PATH_site . $dirPrefix);
							// Create copy file name:
							$pI = pathinfo($rec['ref_string']);
							$copyDestName = dirname($origDestName) . '/RTEmagicC_' . substr(basename($origDestName), 10) . '.' . $pI['extension'];
							if (!@is_file($copyDestName) && !@is_file($origDestName) && $origDestName === \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($origDestName) && $copyDestName === \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($copyDestName)) {
								// Making copies:
								\TYPO3\CMS\Core\Utility\GeneralUtility::upload_copy_move(PATH_site . $fileInfo['original'], $origDestName);
								\TYPO3\CMS\Core\Utility\GeneralUtility::upload_copy_move(PATH_site . $rec['ref_string'], $copyDestName);
								clearstatcache();
								// Register this:
								$this->RTEmagic_copyIndex[$rec['tablename']][$rec['recuid']][$rec['field']][$rec['ref_string']] = substr($copyDestName, strlen(PATH_site));
								// Check and update the record using \TYPO3\CMS\Core\Database\ReferenceIndex
								if (@is_file($copyDestName)) {
									$sysRefObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\ReferenceIndex');
									$error = $sysRefObj->setReferenceValue($rec['hash'], substr($copyDestName, strlen(PATH_site)), FALSE, TRUE);
									if ($error) {
										echo $this->newlog('TYPO3\\CMS\\Core\\Database\\ReferenceIndex::setReferenceValue(): ' . $error, 1);
									}
								} else {
									$this->newlog('File "' . $copyDestName . '" was not created!', 1);
								}
							} else {
								$this->newlog('Could not construct new unique names for file!', 1);
							}
						} else {
							$this->newlog('Maybe directory of file was not within "uploads/"?', 1);
						}
					} else {
						$this->newlog('Trying to copy RTEmagic files (' . $rec['ref_string'] . ' / ' . $fileInfo['original'] . ') but one or both were missing', 1);
					}
				}
			}
		}
	}

	/**
	 * Find l10n-overlay records and perform the requested copy action for these records.
	 *
	 * @param string $table Record Table
	 * @param string $uid Record UID
	 * @param string $destPid Position to copy to
	 * @param boolean $first
	 * @param array $overrideValues
	 * @param string $excludeFields
	 * @return void
	 * @todo Define visibility
	 */
	public function copyL10nOverlayRecords($table, $uid, $destPid, $first = 0, $overrideValues = array(), $excludeFields = '') {
		// There's no need to perform this for page-records or for tables that are not localizeable
		if (!\TYPO3\CMS\Backend\Utility\BackendUtility::isTableLocalizable($table) || !empty($GLOBALS['TCA'][$table]['ctrl']['transForeignTable']) || !empty($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerTable'])) {
			return;
		}
		$where = '';
		if (isset($GLOBALS['TCA'][$table]['ctrl']['versioningWS']) && $GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
			$where = ' AND t3ver_oid=0';
		}
		// If $destPid is < 0, get the pid of the record with uid equal to abs($destPid)
		$tscPID = \TYPO3\CMS\Backend\Utility\BackendUtility::getTSconfig_pidValue($table, $uid, $destPid);
		// Get the localized records to be copied
		$l10nRecords = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordsByField($table, $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'], $uid, $where);
		if (is_array($l10nRecords)) {
			// If $destPid < 0, then it is the uid of the original language record we are inserting after
			if ($destPid < 0) {
				$localizedDestPids = array();
				// Get the localized records of the record we are inserting after
				$destL10nRecords = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordsByField($table, $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'], abs($destPid), $where);
				// Index the localized record uids by language
				if (is_array($destL10nRecords)) {
					foreach ($destL10nRecords as $record) {
						$localizedDestPids[$record[$GLOBALS['TCA'][$table]['ctrl']['languageField']]] = -$record['uid'];
					}
				}
			}
			// Copy the localized records after the corresponding localizations of the destination record
			foreach ($l10nRecords as $record) {
				$localizedDestPid = intval($localizedDestPids[$record[$GLOBALS['TCA'][$table]['ctrl']['languageField']]]);
				if ($localizedDestPid < 0) {
					$this->copyRecord($table, $record['uid'], $localizedDestPid, $first, $overrideValues, $excludeFields, $record[$GLOBALS['TCA'][$table]['ctrl']['languageField']]);
				} else {
					$this->copyRecord($table, $record['uid'], $destPid < 0 ? $tscPID : $destPid, $first, $overrideValues, $excludeFields, $record[$GLOBALS['TCA'][$table]['ctrl']['languageField']]);
				}
			}
		}
	}

	/*********************************************
	 *
	 * Cmd: Moving, Localizing
	 *
	 ********************************************/
	/**
	 * Moving single records
	 *
	 * @param string $table Table name to move
	 * @param integer $uid Record uid to move
	 * @param integer $destPid Position to move to: $destPid: >=0 then it points to a page-id on which to insert the record (as the first element). <0 then it points to a uid from its own table after which to insert it (works if
	 * @return void
	 * @todo Define visibility
	 */
	public function moveRecord($table, $uid, $destPid) {
		if ($GLOBALS['TCA'][$table]) {
			// In case the record to be moved turns out to be an offline version,
			// we have to find the live version and work on that one (this case
			// happens for pages with "branch" versioning type)
			// @deprecated note: as "branch" versioning is deprecated since TYPO3 4.2, this
			// functionality will be removed in TYPO3 4.7 (note by benni: a hook could replace this)
			if ($lookForLiveVersion = \TYPO3\CMS\Backend\Utility\BackendUtility::getLiveVersionOfRecord($table, $uid, 'uid')) {
				$uid = $lookForLiveVersion['uid'];
			}
			// Initialize:
			$destPid = intval($destPid);
			// Get this before we change the pid (for logging)
			$propArr = $this->getRecordProperties($table, $uid);
			$moveRec = $this->getRecordProperties($table, $uid, TRUE);
			// This is the actual pid of the moving to destination
			$resolvedPid = $this->resolvePid($table, $destPid);
			// Finding out, if the record may be moved from where it is. If the record is a non-page, then it depends on edit-permissions.
			// If the record is a page, then there are two options: If the page is moved within itself, (same pid) it's edit-perms of the pid. If moved to another place then its both delete-perms of the pid and new-page perms on the destination.
			if ($table != 'pages' || $resolvedPid == $moveRec['pid']) {
				// Edit rights for the record...
				$mayMoveAccess = $this->checkRecordUpdateAccess($table, $uid);
			} else {
				$mayMoveAccess = $this->doesRecordExist($table, $uid, 'delete');
			}
			// Finding out, if the record may be moved TO another place. Here we check insert-rights (non-pages = edit, pages = new), unless the pages are moved on the same pid, then edit-rights are checked
			if ($table != 'pages' || $resolvedPid != $moveRec['pid']) {
				// Insert rights for the record...
				$mayInsertAccess = $this->checkRecordInsertAccess($table, $resolvedPid, 4);
			} else {
				$mayInsertAccess = $this->checkRecordUpdateAccess($table, $uid);
			}
			// Checking if there is anything else disallowing moving the record by checking if editing is allowed
			$fullLanguageCheckNeeded = $table != 'pages';
			$mayEditAccess = $this->BE_USER->recordEditAccessInternals($table, $uid, FALSE, FALSE, $fullLanguageCheckNeeded);
			// If moving is allowed, begin the processing:
			if ($mayEditAccess) {
				if ($mayMoveAccess) {
					if ($mayInsertAccess) {
						$recordWasMoved = FALSE;
						// Move the record via a hook, used e.g. for versioning
						if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['moveRecordClass'])) {
							foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['moveRecordClass'] as $classRef) {
								$hookObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
								if (method_exists($hookObj, 'moveRecord')) {
									$hookObj->moveRecord($table, $uid, $destPid, $propArr, $moveRec, $resolvedPid, $recordWasMoved, $this);
								}
							}
						}
						// Move the record if a hook hasn't moved it yet
						if (!$recordWasMoved) {
							$this->moveRecord_raw($table, $uid, $destPid);
						}
					} else {
						$this->log($table, $uid, 4, 0, 1, 'Attempt to move record \'%s\' (%s) without having permissions to insert.', 14, array($propArr['header'], $table . ':' . $uid), $propArr['event_pid']);
					}
				} else {
					$this->log($table, $uid, 4, 0, 1, 'Attempt to move record \'%s\' (%s) without having permissions to do so.', 14, array($propArr['header'], $table . ':' . $uid), $propArr['event_pid']);
				}
			} else {
				$this->log($table, $uid, 4, 0, 1, 'Attempt to move record "%s" (%s) without having permissions to do so. [' . $this->BE_USER->errorMsg . ']', 14, array($propArr['header'], $table . ':' . $uid), $propArr['event_pid']);
			}
		}
	}

	/**
	 * Moves a record without checking security of any sort.
	 * USE ONLY INTERNALLY
	 *
	 * @param string $table Table name to move
	 * @param integer $uid Record uid to move
	 * @param integer $destPid Position to move to: $destPid: >=0 then it points to a page-id on which to insert the record (as the first element). <0 then it points to a uid from its own table after which to insert it (works if
	 * @return void
	 * @see moveRecord()
	 * @todo Define visibility
	 */
	public function moveRecord_raw($table, $uid, $destPid) {
		$sortRow = $GLOBALS['TCA'][$table]['ctrl']['sortby'];
		$origDestPid = $destPid;
		// This is the actual pid of the moving to destination
		$resolvedPid = $this->resolvePid($table, $destPid);
		// Checking if the pid is negative, but no sorting row is defined. In that case, find the correct pid. Basically this check make the error message 4-13 meaning less... But you can always remove this check if you prefer the error instead of a no-good action (which is to move the record to its own page...)
		// $destPid>=0 because we must correct pid in case of versioning "page" types.
		if ($destPid < 0 && !$sortRow || $destPid >= 0) {
			$destPid = $resolvedPid;
		}
		// Get this before we change the pid (for logging)
		$propArr = $this->getRecordProperties($table, $uid);
		$moveRec = $this->getRecordProperties($table, $uid, TRUE);
		// Prepare user defined objects (if any) for hooks which extend this function:
		$hookObjectsArr = array();
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['moveRecordClass'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['moveRecordClass'] as $classRef) {
				$hookObjectsArr[] = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
			}
		}
		// Timestamp field:
		$updateFields = array();
		if ($GLOBALS['TCA'][$table]['ctrl']['tstamp']) {
			$updateFields[$GLOBALS['TCA'][$table]['ctrl']['tstamp']] = $GLOBALS['EXEC_TIME'];
		}
		// Insert as first element on page (where uid = $destPid)
		if ($destPid >= 0) {
			if ($table != 'pages' || $this->destNotInsideSelf($destPid, $uid)) {
				// Clear cache before moving
				$this->clear_cache($table, $uid);
				// Setting PID
				$updateFields['pid'] = $destPid;
				// Table is sorted by 'sortby'
				if ($sortRow) {
					$sortNumber = $this->getSortNumber($table, $uid, $destPid);
					$updateFields[$sortRow] = $sortNumber;
				}
				// Check for child records that have also to be moved
				$this->moveRecord_procFields($table, $uid, $destPid);
				// Create query for update:
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid=' . intval($uid), $updateFields);
				// Check for the localizations of that element
				$this->moveL10nOverlayRecords($table, $uid, $destPid, $destPid);
				// Call post processing hooks:
				foreach ($hookObjectsArr as $hookObj) {
					if (method_exists($hookObj, 'moveRecord_firstElementPostProcess')) {
						$hookObj->moveRecord_firstElementPostProcess($table, $uid, $destPid, $moveRec, $updateFields, $this);
					}
				}
				// Logging...
				$newPropArr = $this->getRecordProperties($table, $uid);
				$oldpagePropArr = $this->getRecordProperties('pages', $propArr['pid']);
				$newpagePropArr = $this->getRecordProperties('pages', $destPid);
				if ($destPid != $propArr['pid']) {
					// Logged to old page
					$this->log($table, $uid, 4, $destPid, 0, 'Moved record \'%s\' (%s) to page \'%s\' (%s)', 2, array($propArr['header'], $table . ':' . $uid, $newpagePropArr['header'], $newPropArr['pid']), $propArr['pid']);
					// Logged to new page
					$this->log($table, $uid, 4, $destPid, 0, 'Moved record \'%s\' (%s) from page \'%s\' (%s)', 3, array($propArr['header'], $table . ':' . $uid, $oldpagePropArr['header'], $propArr['pid']), $destPid);
				} else {
					// Logged to new page
					$this->log($table, $uid, 4, $destPid, 0, 'Moved record \'%s\' (%s) on page \'%s\' (%s)', 4, array($propArr['header'], $table . ':' . $uid, $oldpagePropArr['header'], $propArr['pid']), $destPid);
				}
				// Clear cache after moving
				$this->clear_cache($table, $uid);
				$this->fixUniqueInPid($table, $uid);
				// fixCopyAfterDuplFields
				if ($origDestPid < 0) {
					$this->fixCopyAfterDuplFields($table, $uid, abs($origDestPid), 1);
				}
			} else {
				$destPropArr = $this->getRecordProperties('pages', $destPid);
				$this->log($table, $uid, 4, 0, 1, 'Attempt to move page \'%s\' (%s) to inside of its own rootline (at page \'%s\' (%s))', 10, array($propArr['header'], $uid, $destPropArr['header'], $destPid), $propArr['pid']);
			}
		} else {
			// Put after another record
			// Table is being sorted
			if ($sortRow) {
				// Save the position to which the original record is requested to be moved
				$originalRecordDestinationPid = $destPid;
				$sortInfo = $this->getSortNumber($table, $uid, $destPid);
				// Setting the destPid to the new pid of the record.
				$destPid = $sortInfo['pid'];
				// If not an array, there was an error (which is already logged)
				if (is_array($sortInfo)) {
					if ($table != 'pages' || $this->destNotInsideSelf($destPid, $uid)) {
						// clear cache before moving
						$this->clear_cache($table, $uid);
						// We now update the pid and sortnumber
						$updateFields['pid'] = $destPid;
						$updateFields[$sortRow] = $sortInfo['sortNumber'];
						// Check for child records that have also to be moved
						$this->moveRecord_procFields($table, $uid, $destPid);
						// Create query for update:
						$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid=' . intval($uid), $updateFields);
						// Check for the localizations of that element
						$this->moveL10nOverlayRecords($table, $uid, $destPid, $originalRecordDestinationPid);
						// Call post processing hooks:
						foreach ($hookObjectsArr as $hookObj) {
							if (method_exists($hookObj, 'moveRecord_afterAnotherElementPostProcess')) {
								$hookObj->moveRecord_afterAnotherElementPostProcess($table, $uid, $destPid, $origDestPid, $moveRec, $updateFields, $this);
							}
						}
						// Logging...
						$newPropArr = $this->getRecordProperties($table, $uid);
						$oldpagePropArr = $this->getRecordProperties('pages', $propArr['pid']);
						if ($destPid != $propArr['pid']) {
							$newpagePropArr = $this->getRecordProperties('pages', $destPid);
							// Logged to old page
							$this->log($table, $uid, 4, 0, 0, 'Moved record \'%s\' (%s) to page \'%s\' (%s)', 2, array($propArr['header'], $table . ':' . $uid, $newpagePropArr['header'], $newPropArr['pid']), $propArr['pid']);
							// Logged to old page
							$this->log($table, $uid, 4, 0, 0, 'Moved record \'%s\' (%s) from page \'%s\' (%s)', 3, array($propArr['header'], $table . ':' . $uid, $oldpagePropArr['header'], $propArr['pid']), $destPid);
						} else {
							// Logged to old page
							$this->log($table, $uid, 4, 0, 0, 'Moved record \'%s\' (%s) on page \'%s\' (%s)', 4, array($propArr['header'], $table . ':' . $uid, $oldpagePropArr['header'], $propArr['pid']), $destPid);
						}
						// Clear cache after moving
						$this->clear_cache($table, $uid);
						// fixUniqueInPid
						$this->fixUniqueInPid($table, $uid);
						// fixCopyAfterDuplFields
						if ($origDestPid < 0) {
							$this->fixCopyAfterDuplFields($table, $uid, abs($origDestPid), 1);
						}
					} else {
						$destPropArr = $this->getRecordProperties('pages', $destPid);
						$this->log($table, $uid, 4, 0, 1, 'Attempt to move page \'%s\' (%s) to inside of its own rootline (at page \'%s\' (%s))', 10, array($propArr['header'], $uid, $destPropArr['header'], $destPid), $propArr['pid']);
					}
				}
			} else {
				$this->log($table, $uid, 4, 0, 1, 'Attempt to move record \'%s\' (%s) to after another record, although the table has no sorting row.', 13, array($propArr['header'], $table . ':' . $uid), $propArr['event_pid']);
			}
		}
	}

	/**
	 * Walk through all fields of the moved record and look for children of e.g. the inline type.
	 * If child records are found, they are also move to the new $destPid.
	 *
	 * @param string $table Record Table
	 * @param string $uid Record UID
	 * @param string $destPid Position to move to
	 * @return void
	 * @todo Define visibility
	 */
	public function moveRecord_procFields($table, $uid, $destPid) {
		$conf = $GLOBALS['TCA'][$table]['columns'];
		$row = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL($table, $uid);
		if (is_array($row)) {
			foreach ($row as $field => $value) {
				$this->moveRecord_procBasedOnFieldType($table, $uid, $destPid, $field, $value, $conf[$field]['config']);
			}
		}
	}

	/**
	 * Move child records depending on the field type of the parent record.
	 *
	 * @param string $table Record Table
	 * @param string $uid Record UID
	 * @param string $destPid Position to move to
	 * @param string $field Record field
	 * @param string $value Record field value
	 * @param array $conf TCA configuration of current field
	 * @return void
	 * @todo Define visibility
	 */
	public function moveRecord_procBasedOnFieldType($table, $uid, $destPid, $field, $value, $conf) {
		$moveTable = '';
		$moveIds = array();
		if ($conf['type'] == 'inline') {
			$foreign_table = $conf['foreign_table'];
			$moveChildrenWithParent = !isset($conf['behaviour']['disableMovingChildrenWithParent']) || !$conf['behaviour']['disableMovingChildrenWithParent'];
			if ($foreign_table && $moveChildrenWithParent) {
				$inlineType = $this->getInlineFieldType($conf);
				if ($inlineType == 'list' || $inlineType == 'field') {
					$moveTable = $foreign_table;
					if ($table == 'pages') {
						// If the inline elements are related to a page record,
						// make sure they reside at that page and not at its parent
						$destPid = $uid;
					}
					$dbAnalysis = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\RelationHandler');
					$dbAnalysis->start($value, $conf['foreign_table'], '', $uid, $table, $conf);
				}
			}
		}
		// Move the records
		if (isset($dbAnalysis)) {
			// Moving records to a positive destination will insert each
			// record at the beginning, thus the order is reversed here:
			foreach (array_reverse($dbAnalysis->itemArray) as $v) {
				$this->moveRecord($v['table'], $v['id'], $destPid);
			}
		}
	}

	/**
	 * Find l10n-overlay records and perform the requested move action for these records.
	 *
	 * @param string $table Record Table
	 * @param string $uid Record UID
	 * @param string $destPid Position to move to
	 * @param string $originalRecordDestinationPid Position to move the original record to
	 * @return void
	 * @todo Define visibility
	 */
	public function moveL10nOverlayRecords($table, $uid, $destPid, $originalRecordDestinationPid) {
		// There's no need to perform this for page-records or not localizeable tables
		if (!\TYPO3\CMS\Backend\Utility\BackendUtility::isTableLocalizable($table) || !empty($GLOBALS['TCA'][$table]['ctrl']['transForeignTable']) || !empty($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerTable'])) {
			return;
		}
		$where = '';
		if (isset($GLOBALS['TCA'][$table]['ctrl']['versioningWS']) && $GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
			$where = ' AND t3ver_oid=0';
		}
		$l10nRecords = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordsByField($table, $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'], $uid, $where);
		if (is_array($l10nRecords)) {
			// If $$originalRecordDestinationPid < 0, then it is the uid of the original language record we are inserting after
			if ($originalRecordDestinationPid < 0) {
				$localizedDestPids = array();
				// Get the localized records of the record we are inserting after
				$destL10nRecords = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordsByField($table, $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'], abs($originalRecordDestinationPid), $where);
				// Index the localized record uids by language
				if (is_array($destL10nRecords)) {
					foreach ($destL10nRecords as $record) {
						$localizedDestPids[$record[$GLOBALS['TCA'][$table]['ctrl']['languageField']]] = -$record['uid'];
					}
				}
			}
			// Move the localized records after the corresponding localizations of the destination record
			foreach ($l10nRecords as $record) {
				$localizedDestPid = intval($localizedDestPids[$record[$GLOBALS['TCA'][$table]['ctrl']['languageField']]]);
				if ($localizedDestPid < 0) {
					$this->moveRecord($table, $record['uid'], $localizedDestPid);
				} else {
					$this->moveRecord($table, $record['uid'], $destPid);
				}
			}
		}
	}

	/**
	 * Localizes a record to another system language
	 * In reality it only works if transOrigPointerTable is not set. For "pages" the implementation is hardcoded
	 *
	 * @param string $table Table name
	 * @param integer $uid Record uid (to be localized)
	 * @param integer $language Language ID (from sys_language table)
	 * @return mixed The uid (integer) of the new translated record or FALSE (boolean) if something went wrong
	 * @todo Define visibility
	 */
	public function localize($table, $uid, $language) {
		$newId = FALSE;
		$uid = intval($uid);
		if ($GLOBALS['TCA'][$table] && $uid && $this->isNestedElementCallRegistered($table, $uid, 'localize') === FALSE) {
			$this->registerNestedElementCall($table, $uid, 'localize');
			if ($GLOBALS['TCA'][$table]['ctrl']['languageField'] && $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] && !$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerTable'] || $table === 'pages') {
				if ($langRec = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('sys_language', intval($language), 'uid,title')) {
					if ($this->doesRecordExist($table, $uid, 'show')) {
						// Getting workspace overlay if possible - this will localize versions in workspace if any
						$row = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL($table, $uid);
						if (is_array($row)) {
							if ($row[$GLOBALS['TCA'][$table]['ctrl']['languageField']] <= 0 || $table === 'pages') {
								if ($row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']] == 0 || $table === 'pages') {
									if ($table === 'pages') {
										$pass = $GLOBALS['TCA'][$table]['ctrl']['transForeignTable'] === 'pages_language_overlay' && !\TYPO3\CMS\Backend\Utility\BackendUtility::getRecordsByField('pages_language_overlay', 'pid', $uid, (' AND ' . $GLOBALS['TCA']['pages_language_overlay']['ctrl']['languageField'] . '=' . intval($langRec['uid'])));
										$Ttable = 'pages_language_overlay';
									} else {
										$pass = !\TYPO3\CMS\Backend\Utility\BackendUtility::getRecordLocalization($table, $uid, $langRec['uid'], ('AND pid=' . intval($row['pid'])));
										$Ttable = $table;
									}
									if ($pass) {
										// Initialize:
										$overrideValues = array();
										$excludeFields = array();
										// Set override values:
										$overrideValues[$GLOBALS['TCA'][$Ttable]['ctrl']['languageField']] = $langRec['uid'];
										$overrideValues[$GLOBALS['TCA'][$Ttable]['ctrl']['transOrigPointerField']] = $uid;
										// Copy the type (if defined in both tables) from the original record so that translation has same type as original record
										if (isset($GLOBALS['TCA'][$table]['ctrl']['type']) && isset($GLOBALS['TCA'][$Ttable]['ctrl']['type'])) {
											$overrideValues[$GLOBALS['TCA'][$Ttable]['ctrl']['type']] = $row[$GLOBALS['TCA'][$table]['ctrl']['type']];
										}
										// Set exclude Fields:
										foreach ($GLOBALS['TCA'][$Ttable]['columns'] as $fN => $fCfg) {
											// Check if we are just prefixing:
											if ($fCfg['l10n_mode'] == 'prefixLangTitle') {
												if (($fCfg['config']['type'] == 'text' || $fCfg['config']['type'] == 'input') && strlen($row[$fN])) {
													list($tscPID) = \TYPO3\CMS\Backend\Utility\BackendUtility::getTSCpid($table, $uid, '');
													$TSConfig = $this->getTCEMAIN_TSconfig($tscPID);
													if (isset($TSConfig['translateToMessage']) && strlen($TSConfig['translateToMessage'])) {
														$translateToMsg = @sprintf($TSConfig['translateToMessage'], $langRec['title']);
													}
													if (!strlen($translateToMsg)) {
														$translateToMsg = 'Translate to ' . $langRec['title'] . ':';
													}
													$overrideValues[$fN] = '[' . $translateToMsg . '] ' . $row[$fN];
												}
											} elseif (\TYPO3\CMS\Core\Utility\GeneralUtility::inList('exclude,noCopy,mergeIfNotBlank', $fCfg['l10n_mode']) && $fN != $GLOBALS['TCA'][$Ttable]['ctrl']['languageField'] && $fN != $GLOBALS['TCA'][$Ttable]['ctrl']['transOrigPointerField']) {
												// Otherwise, do not copy field (unless it is the language field or
												// pointer to the original language)
												$excludeFields[] = $fN;
											}
										}
										if ($Ttable === $table) {
											// Get the uid of record after which this localized record should be inserted
											$previousUid = $this->getPreviousLocalizedRecordUid($table, $uid, $row['pid'], $language);
											// Execute the copy:
											$newId = $this->copyRecord($table, $uid, -$previousUid, 1, $overrideValues, implode(',', $excludeFields), $language);
											$autoVersionNewId = $this->getAutoVersionId($table, $newId);
											if (is_null($autoVersionNewId) === FALSE) {
												$this->triggerRemapAction($table, $newId, array($this, 'placeholderShadowing'), array($table, $autoVersionNewId), TRUE);
											}
										} else {
											// Create new record:
											/** @var $copyTCE \TYPO3\CMS\Core\DataHandling\DataHandler */
											$copyTCE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
											// Copy forth the cached TSconfig
											$copyTCE->stripslashes_values = 0;
											$copyTCE->cachedTSconfig = $this->cachedTSconfig;
											// Transformations should NOT be carried out during copy
											$copyTCE->dontProcessTransformations = 1;
											$copyTCE->start(array($Ttable => array('NEW' => $overrideValues)), '', $this->BE_USER);
											$copyTCE->process_datamap();
											// Getting the new UID as if it had been copied:
											$theNewSQLID = $copyTCE->substNEWwithIDs['NEW'];
											if ($theNewSQLID) {
												// If is by design that $Ttable is used and not $table! See "l10nmgr" extension. Could be debated, but this is what I chose for this "pseudo case"
												$this->copyMappingArray[$Ttable][$uid] = $theNewSQLID;
												$newId = $theNewSQLID;
											}
										}
									} else {
										$this->newlog('Localization failed; There already was a localization for this language of the record!', 1);
									}
								} else {
									$this->newlog('Localization failed; Source record contained a reference to an original default record (which is strange)!', 1);
								}
							} else {
								$this->newlog('Localization failed; Source record had another language than "Default" or "All" defined!', 1);
							}
						} else {
							$this->newlog('Attempt to localize record that did not exist!', 1);
						}
					} else {
						$this->newlog('Attempt to localize record without permission', 1);
					}
				} else {
					$this->newlog('Sys language UID "' . $language . '" not found valid!', 1);
				}
			} else {
				$this->newlog('Localization failed; "languageField" and "transOrigPointerField" must be defined for the table!', 1);
			}
		}
		return $newId;
	}

	/**
	 * Performs localization or synchronization of child records.
	 *
	 * @param string $table The table of the localized parent record
	 * @param integer $id The uid of the localized parent record
	 * @param string $command Defines the type 'localize' or 'synchronize' (string) or a single uid to be localized (integer)
	 * @return void
	 */
	protected function inlineLocalizeSynchronize($table, $id, $command) {
		// <field>, (localize | synchronize | <uid>):
		$parts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $command);
		$field = $parts[0];
		$type = $parts[1];
		if ($field && (\TYPO3\CMS\Core\Utility\GeneralUtility::inList('localize,synchronize', $type) || \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($type)) && isset($GLOBALS['TCA'][$table]['columns'][$field]['config'])) {
			$config = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
			$foreignTable = $config['foreign_table'];
			$localizationMode = \TYPO3\CMS\Backend\Utility\BackendUtility::getInlineLocalizationMode($table, $config);
			if ($localizationMode == 'select') {
				$parentRecord = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL($table, $id);
				$language = intval($parentRecord[$GLOBALS['TCA'][$table]['ctrl']['languageField']]);
				$transOrigPointer = intval($parentRecord[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']]);
				$transOrigTable = \TYPO3\CMS\Backend\Utility\BackendUtility::getOriginalTranslationTable($table);
				$childTransOrigPointerField = $GLOBALS['TCA'][$foreignTable]['ctrl']['transOrigPointerField'];

				if ($parentRecord && is_array($parentRecord) && $language > 0 && $transOrigPointer) {
					$inlineSubType = $this->getInlineFieldType($config);
					$transOrigRecord = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL($transOrigTable, $transOrigPointer);

					if ($inlineSubType !== FALSE) {
						$removeArray = array();
						$mmTable = $inlineSubType == 'mm' && isset($config['MM']) && $config['MM'] ? $config['MM'] : '';
						// Fetch children from original language parent:
						/** @var $dbAnalysisOriginal \TYPO3\CMS\Core\Database\RelationHandler */
						$dbAnalysisOriginal = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\RelationHandler');
						$dbAnalysisOriginal->start($transOrigRecord[$field], $foreignTable, $mmTable, $transOrigRecord['uid'], $transOrigTable, $config);
						$elementsOriginal = array();
						foreach ($dbAnalysisOriginal->itemArray as $item) {
							$elementsOriginal[$item['id']] = $item;
						}
						unset($dbAnalysisOriginal);
						// Fetch children from current localized parent:
						/** @var $dbAnalysisCurrent \TYPO3\CMS\Core\Database\RelationHandler */
						$dbAnalysisCurrent = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\RelationHandler');
						$dbAnalysisCurrent->start($parentRecord[$field], $foreignTable, $mmTable, $id, $table, $config);
						// Perform synchronization: Possibly removal of already localized records:
						if ($type == 'synchronize') {
							foreach ($dbAnalysisCurrent->itemArray as $index => $item) {
								$childRecord = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL($item['table'], $item['id']);
								if (isset($childRecord[$childTransOrigPointerField]) && $childRecord[$childTransOrigPointerField] > 0) {
									$childTransOrigPointer = $childRecord[$childTransOrigPointerField];
									// If snychronization is requested, child record was translated once, but original record does not exist anymore, remove it:
									if (!isset($elementsOriginal[$childTransOrigPointer])) {
										unset($dbAnalysisCurrent->itemArray[$index]);
										$removeArray[$item['table']][$item['id']]['delete'] = 1;
									}
								}
							}
						}
						// Perform synchronization/localization: Possibly add unlocalized records for original language:
						if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($type) && isset($elementsOriginal[$type])) {
							$item = $elementsOriginal[$type];
							$item['id'] = $this->localize($item['table'], $item['id'], $language);
							$item['id'] = $this->overlayAutoVersionId($item['table'], $item['id']);
							$dbAnalysisCurrent->itemArray[] = $item;
						} elseif (\TYPO3\CMS\Core\Utility\GeneralUtility::inList('localize,synchronize', $type)) {
							foreach ($elementsOriginal as $originalId => $item) {
								$item['id'] = $this->localize($item['table'], $item['id'], $language);
								$item['id'] = $this->overlayAutoVersionId($item['table'], $item['id']);
								$dbAnalysisCurrent->itemArray[] = $item;
							}
						}
						// Store the new values, we will set up the uids for the subtype later on (exception keep localization from original record):
						$value = implode(',', $dbAnalysisCurrent->getValueArray());
						$this->registerDBList[$table][$id][$field] = $value;
						// Remove child records (if synchronization requested it):
						if (is_array($removeArray) && count($removeArray)) {
							$tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
							$tce->stripslashes_values = FALSE;
							$tce->start(array(), $removeArray);
							$tce->process_cmdmap();
							unset($tce);
						}
						// Handle, reorder and store relations:
						if ($inlineSubType == 'list') {
							$updateFields = array($field => $value);
						} elseif ($inlineSubType == 'field') {
							$dbAnalysisCurrent->writeForeignField($config, $id);
							$updateFields = array($field => $dbAnalysisCurrent->countItems(FALSE));
						} elseif ($inlineSubType == 'mm') {
							$dbAnalysisCurrent->writeMM($config['MM'], $id);
							$updateFields = array($field => $dbAnalysisCurrent->countItems(FALSE));
						}
						// Update field referencing to child records of localized parent record:
						if (is_array($updateFields) && count($updateFields)) {
							$this->updateDB($table, $id, $updateFields);
						}
					}
				}
			}
		}
	}

	/*********************************************
	 *
	 * Cmd: Deleting
	 *
	 ********************************************/
	/**
	 * Delete a single record
	 *
	 * @param string $table Table name
	 * @param integer $id Record UID
	 * @return void
	 * @todo Define visibility
	 */
	public function deleteAction($table, $id) {
		$recordToDelete = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($table, $id);
		// Record asked to be deleted was found:
		if (is_array($recordToDelete)) {
			$recordWasDeleted = FALSE;
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'] as $classRef) {
					$hookObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
					if (method_exists($hookObj, 'processCmdmap_deleteAction')) {
						$hookObj->processCmdmap_deleteAction($table, $id, $recordToDelete, $recordWasDeleted, $this);
					}
				}
			}
			// Delete the record if a hook hasn't deleted it yet
			if (!$recordWasDeleted) {
				$this->deleteEl($table, $id);
			}
		}
	}

	/**
	 * Delete element from any table
	 *
	 * @param string $table Table name
	 * @param integer $uid Record UID
	 * @param boolean $noRecordCheck Flag: If $noRecordCheck is set, then the function does not check permission to delete record
	 * @param boolean $forceHardDelete If TRUE, the "deleted" flag is ignored if applicable for record and the record is deleted COMPLETELY!
	 * @return void
	 * @todo Define visibility
	 */
	public function deleteEl($table, $uid, $noRecordCheck = FALSE, $forceHardDelete = FALSE) {
		if ($table == 'pages') {
			$this->deletePages($uid, $noRecordCheck, $forceHardDelete);
		} else {
			$this->deleteVersionsForRecord($table, $uid, $forceHardDelete);
			$this->deleteRecord($table, $uid, $noRecordCheck, $forceHardDelete);
		}
	}

	/**
	 * Delete versions for element from any table
	 *
	 * @param string $table Table name
	 * @param integer $uid Record UID
	 * @param boolean $forceHardDelete If TRUE, the "deleted" flag is ignored if applicable for record and the record is deleted COMPLETELY!
	 * @return void
	 * @todo Define visibility
	 */
	public function deleteVersionsForRecord($table, $uid, $forceHardDelete) {
		$versions = \TYPO3\CMS\Backend\Utility\BackendUtility::selectVersionsOfRecord($table, $uid, 'uid,pid');
		if (is_array($versions)) {
			foreach ($versions as $verRec) {
				if (!$verRec['_CURRENT_VERSION']) {
					if ($table == 'pages') {
						$this->deletePages($verRec['uid'], TRUE, $forceHardDelete);
					} else {
						$this->deleteRecord($table, $verRec['uid'], TRUE, $forceHardDelete);
					}
				}
			}
		}
	}

	/**
	 * Undelete a single record
	 *
	 * @param string $table Table name
	 * @param integer $uid Record UID
	 * @return void
	 * @todo Define visibility
	 */
	public function undeleteRecord($table, $uid) {
		if ($this->isRecordUndeletable($table, $uid)) {
			$this->deleteRecord($table, $uid, TRUE, FALSE, TRUE);
		}
	}

	/**
	 * Deleting/Undeleting a record
	 * This function may not be used to delete pages-records unless the underlying records are already deleted
	 * Deletes a record regardless of versioning state (live or offline, doesn't matter, the uid decides)
	 * If both $noRecordCheck and $forceHardDelete are set it could even delete a "deleted"-flagged record!
	 *
	 * @param string $table Table name
	 * @param integer $uid Record UID
	 * @param boolean $noRecordCheck Flag: If $noRecordCheck is set, then the function does not check permission to delete record
	 * @param boolean $forceHardDelete If TRUE, the "deleted" flag is ignored if applicable for record and the record is deleted COMPLETELY!
	 * @param boolean $undeleteRecord If TRUE, the "deleted" flag is set to 0 again and thus, the item is undeleted.
	 * @return void
	 * @todo Define visibility
	 */
	public function deleteRecord($table, $uid, $noRecordCheck = FALSE, $forceHardDelete = FALSE, $undeleteRecord = FALSE) {
		// Checking if there is anything else disallowing deleting the record by checking if editing is allowed
		$mayEditAccess = $this->BE_USER->recordEditAccessInternals($table, $uid, FALSE, $undeleteRecord, TRUE);
		$uid = intval($uid);
		if ($GLOBALS['TCA'][$table] && $uid) {
			if ($mayEditAccess) {
				if ($noRecordCheck || $this->doesRecordExist($table, $uid, 'delete')) {
					// Clear cache before deleting the record, else the correct page cannot be identified by clear_cache
					$this->clear_cache($table, $uid);
					$propArr = $this->getRecordProperties($table, $uid);
					$pagePropArr = $this->getRecordProperties('pages', $propArr['pid']);
					$deleteRow = $GLOBALS['TCA'][$table]['ctrl']['delete'];
					if ($deleteRow && !$forceHardDelete) {
						$value = $undeleteRecord ? 0 : 1;
						$updateFields = array(
							$deleteRow => $value
						);
						if ($GLOBALS['TCA'][$table]['ctrl']['tstamp']) {
							$updateFields[$GLOBALS['TCA'][$table]['ctrl']['tstamp']] = $GLOBALS['EXEC_TIME'];
						}
						// If the table is sorted, then the sorting number is set very high
						if ($GLOBALS['TCA'][$table]['ctrl']['sortby'] && !$undeleteRecord) {
							$updateFields[$GLOBALS['TCA'][$table]['ctrl']['sortby']] = 1000000000;
						}
						// before (un-)deleting this record, check for child records or references
						$this->deleteRecord_procFields($table, $uid, $undeleteRecord);
						$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid=' . intval($uid), $updateFields);
						// Delete all l10n records aswell, impossible during undelete because it might bring too many records back to life
						if (!$undeleteRecord) {
							$this->deleteL10nOverlayRecords($table, $uid);
						}
					} else {
						// Fetches all fields with flexforms and look for files to delete:
						foreach ($GLOBALS['TCA'][$table]['columns'] as $fieldName => $cfg) {
							$conf = $cfg['config'];
							switch ($conf['type']) {
							case 'flex':
								$flexObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Configuration\\FlexForm\\FlexFormTools');
								$flexObj->traverseFlexFormXMLData($table, $fieldName, \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordRaw($table, 'uid=' . intval($uid)), $this, 'deleteRecord_flexFormCallBack');
								break;
							}
						}
						// Fetches all fields that holds references to files
						$fileFieldArr = $this->extFileFields($table);
						if (count($fileFieldArr)) {
							$mres = $GLOBALS['TYPO3_DB']->exec_SELECTquery(implode(',', $fileFieldArr), $table, 'uid=' . intval($uid));
							if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($mres)) {
								$fArray = $fileFieldArr;
								// MISSING: Support for MM file relations!
								foreach ($fArray as $theField) {
									// This deletes files that belonged to this record.
									$this->extFileFunctions($table, $theField, $row[$theField], 'deleteAll');
								}
							} else {
								$this->log($table, $uid, 3, 0, 100, 'Delete: Zero rows in result when trying to read filenames from record which should be deleted');
							}
							$GLOBALS['TYPO3_DB']->sql_free_result($mres);
						}
						// Delete the hard way...:
						$GLOBALS['TYPO3_DB']->exec_DELETEquery($table, 'uid=' . intval($uid));
						$this->deleteL10nOverlayRecords($table, $uid);
					}
					// 1 means insert, 3 means delete
					$state = $undeleteRecord ? 1 : 3;
					if (!$GLOBALS['TYPO3_DB']->sql_error()) {
						if ($forceHardDelete) {
							$message = 'Record \'%s\' (%s) was deleted unrecoverable from page \'%s\' (%s)';
						} else {
							$message = $state == 1 ? 'Record \'%s\' (%s) was restored on page \'%s\' (%s)' : 'Record \'%s\' (%s) was deleted from page \'%s\' (%s)';
						}
						$this->log($table, $uid, $state, 0, 0, $message, 0, array(
							$propArr['header'],
							$table . ':' . $uid,
							$pagePropArr['header'],
							$propArr['pid']
						), $propArr['event_pid']);
					} else {
						$this->log($table, $uid, $state, 0, 100, $GLOBALS['TYPO3_DB']->sql_error());
					}
					// Update reference index:
					$this->updateRefIndex($table, $uid);
					// If there are entries in the updateRefIndexStack
					if (is_array($this->updateRefIndexStack[$table]) && is_array($this->updateRefIndexStack[$table][$uid])) {
						while ($args = array_pop($this->updateRefIndexStack[$table][$uid])) {
							// $args[0]: table, $args[1]: uid
							$this->updateRefIndex($args[0], $args[1]);
						}
						unset($this->updateRefIndexStack[$table][$uid]);
					}
				} else {
					$this->log($table, $uid, 3, 0, 1, 'Attempt to delete record without delete-permissions');
				}
			} else {
				$this->log($table, $uid, 3, 0, 1, 'Attempt to delete record without delete-permissions. [' . $this->BE_USER->errorMsg . ']');
			}
		}
	}

	/**
	 * Call back function for deleting file relations for flexform fields in records which are being completely deleted.
	 *
	 * @param array $dsArr
	 * @param string $dataValue
	 * @param array $PA
	 * @param $structurePath Not used
	 * @param $pObj Not used
	 * @return void
	 * @todo Define visibility
	 */
	public function deleteRecord_flexFormCallBack($dsArr, $dataValue, $PA, $structurePath, $pObj) {
		// Use reference index object to find files in fields:
		$refIndexObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\ReferenceIndex');
		$files = $refIndexObj->getRelations_procFiles($dataValue, $dsArr['TCEforms']['config'], $PA['uid']);
		// Traverse files and delete them:
		if (is_array($files)) {
			foreach ($files as $dat) {
				if (@is_file($dat['ID_absFile'])) {
					unlink($dat['ID_absFile']);
				} else {
					$this->log('', 0, 3, 0, 100, 'Delete: Referenced file \'' . $dat['ID_absFile'] . '\' that was supposed to be deleted together with its record which didn\'t exist');
				}
			}
		}
	}

	/**
	 * Used to delete page because it will check for branch below pages and unallowed tables on the page as well.
	 *
	 * @param integer $uid Page id
	 * @param boolean $force If TRUE, pages are not checked for permission.
	 * @param boolean $forceHardDelete If TRUE, the "deleted" flag is ignored if applicable for record and the record is deleted COMPLETELY!
	 * @return void
	 * @todo Define visibility
	 */
	public function deletePages($uid, $force = FALSE, $forceHardDelete = FALSE) {
		// Getting list of pages to delete:
		if ($force) {
			// Returns the branch WITHOUT permission checks (0 secures that)
			$brExist = $this->doesBranchExist('', $uid, 0, 1);
			$res = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $brExist . $uid, 1);
		} else {
			$res = $this->canDeletePage($uid);
		}
		// Perform deletion if not error:
		if (is_array($res)) {
			foreach ($res as $deleteId) {
				$this->deleteSpecificPage($deleteId, $forceHardDelete);
			}
		} else {
			$this->newlog($res, 1);
		}
	}

	/**
	 * Delete a page and all records on it.
	 *
	 * @param integer $uid Page id
	 * @param boolean $forceHardDelete If TRUE, the "deleted" flag is ignored if applicable for record and the record is deleted COMPLETELY!
	 * @return void
	 * @access private
	 * @see deletePages()
	 * @todo Define visibility
	 */
	public function deleteSpecificPage($uid, $forceHardDelete = FALSE) {
		$uid = intval($uid);
		if ($uid) {
			foreach (array_keys($GLOBALS['TCA']) as $table) {
				if ($table != 'pages') {
					$mres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', $table, 'pid=' . intval($uid) . $this->deleteClause($table));
					while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($mres)) {
						$this->deleteVersionsForRecord($table, $row['uid'], $forceHardDelete);
						$this->deleteRecord($table, $row['uid'], TRUE, $forceHardDelete);
					}
					$GLOBALS['TYPO3_DB']->sql_free_result($mres);
				}
			}
			$this->deleteVersionsForRecord('pages', $uid, $forceHardDelete);
			$this->deleteRecord('pages', $uid, TRUE, $forceHardDelete);
		}
	}

	/**
	 * Used to evaluate if a page can be deleted
	 *
	 * @param integer $uid Page id
	 * @return mixed If array: List of page uids to traverse and delete (means OK), if string: error code.
	 * @todo Define visibility
	 */
	public function canDeletePage($uid) {
		// If we may at all delete this page
		if ($this->doesRecordExist('pages', $uid, 'delete')) {
			if ($this->deleteTree) {
				// Returns the branch
				$brExist = $this->doesBranchExist('', $uid, $this->pMap['delete'], 1);
				// Checks if we had permissions
				if ($brExist != -1) {
					if ($this->noRecordsFromUnallowedTables($brExist . $uid)) {
						$pagesInBranch = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $brExist . $uid, 1);
						foreach ($pagesInBranch as $pageInBranch) {
							if (!$this->BE_USER->recordEditAccessInternals('pages', $pageInBranch, FALSE, FALSE, TRUE)) {
								return 'Attempt to delete page which has prohibited localizations.';
							}
						}
						return $pagesInBranch;
					} else {
						return 'Attempt to delete records from disallowed tables';
					}
				} else {
					return 'Attempt to delete pages in branch without permissions';
				}
			} else {
				// returns the branch
				$brExist = $this->doesBranchExist('', $uid, $this->pMap['delete'], 1);
				// Checks if branch exists
				if ($brExist == '') {
					if ($this->noRecordsFromUnallowedTables($uid)) {
						if ($this->BE_USER->recordEditAccessInternals('pages', $uid, FALSE, FALSE, TRUE)) {
							return array($uid);
						} else {
							return 'Attempt to delete page which has prohibited localizations.';
						}
					} else {
						return 'Attempt to delete records from disallowed tables';
					}
				} else {
					return 'Attempt to delete page which has subpages';
				}
			}
		} else {
			return 'Attempt to delete page without permissions';
		}
	}

	/**
	 * Returns TRUE if record CANNOT be deleted, otherwise FALSE. Used to check before the versioning API allows a record to be marked for deletion.
	 *
	 * @param string $table Record Table
	 * @param integer $id Record UID
	 * @return string Returns a string IF there is an error (error string explaining). FALSE means record can be deleted
	 * @todo Define visibility
	 */
	public function cannotDeleteRecord($table, $id) {
		if ($table === 'pages') {
			$res = $this->canDeletePage($id);
			return is_array($res) ? FALSE : $res;
		} else {
			return $this->doesRecordExist($table, $id, 'delete') ? FALSE : 'No permission to delete record';
		}
	}

	/**
	 * Determines whether a record can be undeleted.
	 *
	 * @param string $table Table name of the record
	 * @param integer $uid uid of the record
	 * @return boolean Whether the record can be undeleted
	 */
	public function isRecordUndeletable($table, $uid) {
		$result = FALSE;
		$record = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($table, $uid, 'pid', '', FALSE);
		if ($record['pid']) {
			$page = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages', $record['pid'], 'deleted, title, uid', '', FALSE);
			// The page containing the record is not deleted, thus the record can be undeleted:
			if (!$page['deleted']) {
				$result = TRUE;
			} else {
				$this->log($table, $uid, 'isRecordUndeletable', '', 1, 'Record cannot be undeleted since the page containing it is deleted! Undelete page "' . $page['title'] . ' (UID: ' . $page['uid'] . ')" first');
			}
		} else {
			// The page containing the record is on rootlevel, so there is no parent record to check, and the record can be undeleted:
			$result = TRUE;
		}
		return $result;
	}

	/**
	 * Before a record is deleted, check if it has references such as inline type or MM references.
	 * If so, set these child records also to be deleted.
	 *
	 * @param string $table Record Table
	 * @param string $uid Record UID
	 * @param boolean $undeleteRecord If a record should be undeleted (e.g. from history/undo)
	 * @return void
	 * @see 	 deleteRecord()
	 * @todo Define visibility
	 */
	public function deleteRecord_procFields($table, $uid, $undeleteRecord = FALSE) {
		$conf = $GLOBALS['TCA'][$table]['columns'];
		$row = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($table, $uid, '*', '', FALSE);
		foreach ($row as $field => $value) {
			$this->deleteRecord_procBasedOnFieldType($table, $uid, $field, $value, $conf[$field]['config'], $undeleteRecord);
		}
	}

	/**
	 * Process fields of a record to be deleted and search for special handling, like
	 * inline type, MM records, etc.
	 *
	 * @param string $table Record Table
	 * @param string $uid Record UID
	 * @param string $field Record field
	 * @param string $value Record field value
	 * @param array $conf TCA configuration of current field
	 * @param boolean $undeleteRecord If a record should be undeleted (e.g. from history/undo)
	 * @return void
	 * @see 	 deleteRecord()
	 * @todo Define visibility
	 */
	public function deleteRecord_procBasedOnFieldType($table, $uid, $field, $value, $conf, $undeleteRecord = FALSE) {
		if ($conf['type'] == 'inline') {
			$foreign_table = $conf['foreign_table'];
			if ($foreign_table) {
				$inlineType = $this->getInlineFieldType($conf);
				if ($inlineType == 'list' || $inlineType == 'field') {
					$dbAnalysis = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\RelationHandler');
					$dbAnalysis->start($value, $conf['foreign_table'], '', $uid, $table, $conf);
					$dbAnalysis->undeleteRecord = TRUE;
					// Walk through the items and remove them
					foreach ($dbAnalysis->itemArray as $v) {
						if (!$undeleteRecord) {
							$this->deleteAction($v['table'], $v['id']);
						} else {
							$this->undeleteRecord($v['table'], $v['id']);
						}
					}
				}
			}
		} elseif ($this->isReferenceField($conf)) {
			$allowedTables = $conf['type'] == 'group' ? $conf['allowed'] : $conf['foreign_table'] . ',' . $conf['neg_foreign_table'];
			$prependName = $conf['type'] == 'group' ? $conf['prepend_tname'] : $conf['neg_foreign_table'];
			$dbAnalysis = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\RelationHandler');
			$dbAnalysis->start($value, $allowedTables, $conf['MM'], $uid, $table, $conf);
			foreach ($dbAnalysis->itemArray as $v) {
				$this->updateRefIndexStack[$table][$uid][] = array($v['table'], $v['id']);
			}
		}
	}

	/**
	 * Find l10n-overlay records and perform the requested delete action for these records.
	 *
	 * @param string $table Record Table
	 * @param string $uid Record UID
	 * @return void
	 * @todo Define visibility
	 */
	public function deleteL10nOverlayRecords($table, $uid) {
		// Check whether table can be localized or has a different table defined to store localizations:
		if (!\TYPO3\CMS\Backend\Utility\BackendUtility::isTableLocalizable($table) || !empty($GLOBALS['TCA'][$table]['ctrl']['transForeignTable']) || !empty($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerTable'])) {
			return;
		}
		$where = '';
		if (isset($GLOBALS['TCA'][$table]['ctrl']['versioningWS']) && $GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
			$where = ' AND t3ver_oid=0';
		}
		$l10nRecords = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordsByField($table, $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'], $uid, $where);
		if (is_array($l10nRecords)) {
			foreach ($l10nRecords as $record) {
				$this->deleteAction($table, intval($record['t3ver_oid']) > 0 ? intval($record['t3ver_oid']) : intval($record['uid']));
			}
		}
	}

	/*********************************************
	 *
	 * Cmd: Versioning
	 *
	 ********************************************/
	/**
	 * Creates a new version of a record
	 * (Requires support in the table)
	 *
	 * @param string $table Table name
	 * @param integer $id Record uid to versionize
	 * @param string $label Version label
	 * @param boolean $delete If TRUE, the version is created to delete the record.
	 * @return integer Returns the id of the new version (if any)
	 * @see copyRecord()
	 * @todo Define visibility
	 */
	public function versionizeRecord($table, $id, $label, $delete = FALSE) {
		$id = intval($id);
		// Stop any actions if the record is marked to be deleted:
		// (this can occur if IRRE elements are versionized and child elements are removed)
		if ($this->isElementToBeDeleted($table, $id)) {
			return NULL;
		}
		if ($GLOBALS['TCA'][$table] && $GLOBALS['TCA'][$table]['ctrl']['versioningWS'] && $id > 0) {
			if ($this->doesRecordExist($table, $id, 'show')) {
				// Select main record:
				$row = $this->recordInfo($table, $id, 'pid,t3ver_id,t3ver_state');
				if (is_array($row)) {
					// Record must be online record
					if ($row['pid'] >= 0) {
						// Record must not be placeholder for moving.
						if ($row['t3ver_state'] != 3) {
							if (!$delete || !$this->cannotDeleteRecord($table, $id)) {
								// Look for next version number:
								$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('t3ver_id', $table, '((pid=-1 && t3ver_oid=' . $id . ') OR uid=' . $id . ')' . $this->deleteClause($table), '', 't3ver_id DESC', '1');
								list($highestVerNumber) = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
								$GLOBALS['TYPO3_DB']->sql_free_result($res);
								// Look for version number of the current:
								$subVer = $row['t3ver_id'] . '.' . ($highestVerNumber + 1);
								// Set up the values to override when making a raw-copy:
								$overrideArray = array(
									't3ver_id' => $highestVerNumber + 1,
									't3ver_oid' => $id,
									't3ver_label' => $label ? $label : $subVer . ' / ' . date('d-m-Y H:m:s'),
									't3ver_wsid' => $this->BE_USER->workspace,
									't3ver_state' => $delete ? 2 : 0,
									't3ver_count' => 0,
									't3ver_stage' => 0,
									't3ver_tstamp' => 0
								);
								if ($GLOBALS['TCA'][$table]['ctrl']['editlock']) {
									$overrideArray[$GLOBALS['TCA'][$table]['ctrl']['editlock']] = 0;
								}
								// Checking if the record already has a version in the current workspace of the backend user
								$workspaceCheck = TRUE;
								if ($this->BE_USER->workspace !== 0) {
									// Look for version already in workspace:
									$workspaceCheck = \TYPO3\CMS\Backend\Utility\BackendUtility::getWorkspaceVersionOfRecord($this->BE_USER->workspace, $table, $id, 'uid') ? FALSE : TRUE;
								}
								if ($workspaceCheck) {
									// Create raw-copy and return result:
									// The information of the label to be used for the workspace record
									// as well as the information whether the record shall be removed
									// must be forwarded (creating remove placeholders on a workspace are
									// done by copying the record and override several fields).
									$workspaceOptions = array();
									if ($delete) {
										$workspaceOptions['delete'] = $delete;
										$workspaceOptions['label'] = $label;
									}
									return $this->copyRecord_raw($table, $id, -1, $overrideArray, $workspaceOptions);
								} else {
									$this->newlog('Record "' . $table . ':' . $id . '" you wanted to versionize was already a version in the workspace (wsid=' . $this->BE_USER->workspace . ')!', 1);
								}
							} else {
								$this->newlog('Record cannot be deleted: ' . $this->cannotDeleteRecord($table, $id), 1);
							}
						} else {
							$this->newlog('Record cannot be versioned because it is a placeholder for a moving operation', 1);
						}
					} else {
						$this->newlog('Record "' . $table . ':' . $id . '" you wanted to versionize was already a version in archive (pid=-1)!', 1);
					}
				} else {
					$this->newlog('Record "' . $table . ':' . $id . '" you wanted to versionize did not exist!', 1);
				}
			} else {
				$this->newlog('You didnt have correct permissions to make a new version (copy) of this record "' . $table . '" / ' . $id, 1);
			}
		} else {
			$this->newlog('Versioning is not supported for this table "' . $table . '" / ' . $id, 1);
		}
	}

	/**
	 * Swaps MM-relations for current/swap record, see version_swap()
	 *
	 * @param string $table Table for the two input records
	 * @param integer $id Current record (about to go offline)
	 * @param integer $swapWith Swap record (about to go online)
	 * @return void
	 * @see version_swap()
	 * @todo Define visibility
	 */
	public function version_remapMMForVersionSwap($table, $id, $swapWith) {
		// Actually, selecting the records fully is only need if flexforms are found inside... This could be optimized ...
		$currentRec = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($table, $id);
		$swapRec = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($table, $swapWith);
		$this->version_remapMMForVersionSwap_reg = array();
		foreach ($GLOBALS['TCA'][$table]['columns'] as $field => $fConf) {
			$conf = $fConf['config'];
			if ($this->isReferenceField($conf)) {
				$allowedTables = $conf['type'] == 'group' ? $conf['allowed'] : $conf['foreign_table'] . ',' . $conf['neg_foreign_table'];
				$prependName = $conf['type'] == 'group' ? $conf['prepend_tname'] : $conf['neg_foreign_table'];
				if ($conf['MM']) {
					/** @var $dbAnalysis \TYPO3\CMS\Core\Database\RelationHandler */
					$dbAnalysis = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\RelationHandler');
					$dbAnalysis->start('', $allowedTables, $conf['MM'], $id, $table, $conf);
					if (count($dbAnalysis->getValueArray($prependName))) {
						$this->version_remapMMForVersionSwap_reg[$id][$field] = array($dbAnalysis, $conf['MM'], $prependName);
					}
					/** @var $dbAnalysis \TYPO3\CMS\Core\Database\RelationHandler */
					$dbAnalysis = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\RelationHandler');
					$dbAnalysis->start('', $allowedTables, $conf['MM'], $swapWith, $table, $conf);
					if (count($dbAnalysis->getValueArray($prependName))) {
						$this->version_remapMMForVersionSwap_reg[$swapWith][$field] = array($dbAnalysis, $conf['MM'], $prependName);
					}
				}
			} elseif ($conf['type'] == 'flex') {
				// Current record
				$dataStructArray = \TYPO3\CMS\Backend\Utility\BackendUtility::getFlexFormDS($conf, $currentRec, $table);
				$currentValueArray = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($currentRec[$field]);
				if (is_array($currentValueArray)) {
					$this->checkValue_flex_procInData($currentValueArray['data'], array(), array(), $dataStructArray, array($table, $id, $field), 'version_remapMMForVersionSwap_flexFormCallBack');
				}
				// Swap record
				$dataStructArray = \TYPO3\CMS\Backend\Utility\BackendUtility::getFlexFormDS($conf, $swapRec, $table);
				$currentValueArray = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($swapRec[$field]);
				if (is_array($currentValueArray)) {
					$this->checkValue_flex_procInData($currentValueArray['data'], array(), array(), $dataStructArray, array($table, $swapWith, $field), 'version_remapMMForVersionSwap_flexFormCallBack');
				}
			}
		}
		// Execute:
		$this->version_remapMMForVersionSwap_execSwap($table, $id, $swapWith);
	}

	/**
	 * Callback function for traversing the FlexForm structure in relation to ...
	 *
	 * @param array $pParams Array of parameters in num-indexes: table, uid, field
	 * @param array $dsConf TCA field configuration (from Data Structure XML)
	 * @param string $dataValue The value of the flexForm field
	 * @param string $dataValue_ext1 Not used.
	 * @param string $dataValue_ext2 Not used.
	 * @param string $path Path in flexforms
	 * @return array Result array with key "value" containing the value of the processing.
	 * @see version_remapMMForVersionSwap(), checkValue_flex_procInData_travDS()
	 * @todo Define visibility
	 */
	public function version_remapMMForVersionSwap_flexFormCallBack($pParams, $dsConf, $dataValue, $dataValue_ext1, $dataValue_ext2, $path) {
		// Extract parameters:
		list($table, $uid, $field) = $pParams;
		if ($this->isReferenceField($dsConf)) {
			$allowedTables = $dsConf['type'] == 'group' ? $dsConf['allowed'] : $dsConf['foreign_table'] . ',' . $dsConf['neg_foreign_table'];
			$prependName = $dsConf['type'] == 'group' ? $dsConf['prepend_tname'] : $dsConf['neg_foreign_table'];
			if ($dsConf['MM']) {
				/** @var $dbAnalysis \TYPO3\CMS\Core\Database\RelationHandler */
				$dbAnalysis = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\RelationHandler');
				$dbAnalysis->start('', $allowedTables, $dsConf['MM'], $uid, $table, $dsConf);
				$this->version_remapMMForVersionSwap_reg[$uid][$field . '/' . $path] = array($dbAnalysis, $dsConf['MM'], $prependName);
			}
		}
	}

	/**
	 * Performing the remapping operations found necessary in version_remapMMForVersionSwap()
	 * It must be done in three steps with an intermediate "fake" uid. The UID can be something else than -$id (fx. 9999999+$id if you dare... :-)- as long as it is unique.
	 *
	 * @param string $table Table for the two input records
	 * @param integer $id Current record (about to go offline)
	 * @param integer $swapWith Swap record (about to go online)
	 * @return void
	 * @see version_remapMMForVersionSwap()
	 * @todo Define visibility
	 */
	public function version_remapMMForVersionSwap_execSwap($table, $id, $swapWith) {
		if (is_array($this->version_remapMMForVersionSwap_reg[$id])) {
			foreach ($this->version_remapMMForVersionSwap_reg[$id] as $field => $str) {
				$str[0]->remapMM($str[1], $id, -$id, $str[2]);
			}
		}
		if (is_array($this->version_remapMMForVersionSwap_reg[$swapWith])) {
			foreach ($this->version_remapMMForVersionSwap_reg[$swapWith] as $field => $str) {
				$str[0]->remapMM($str[1], $swapWith, $id, $str[2]);
			}
		}
		if (is_array($this->version_remapMMForVersionSwap_reg[$id])) {
			foreach ($this->version_remapMMForVersionSwap_reg[$id] as $field => $str) {
				$str[0]->remapMM($str[1], -$id, $swapWith, $str[2]);
			}
		}
	}

	/*********************************************
	 *
	 * Cmd: Helper functions
	 *
	 ********************************************/
	/**
	 * Processes the fields with references as registered during the copy process. This includes all FlexForm fields which had references.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function remapListedDBRecords() {
		if (count($this->registerDBList)) {
			foreach ($this->registerDBList as $table => $records) {
				foreach ($records as $uid => $fields) {
					$newData = array();
					$theUidToUpdate = $this->copyMappingArray_merged[$table][$uid];
					$theUidToUpdate_saveTo = \TYPO3\CMS\Backend\Utility\BackendUtility::wsMapId($table, $theUidToUpdate);
					foreach ($fields as $fieldName => $value) {
						$conf = $GLOBALS['TCA'][$table]['columns'][$fieldName]['config'];
						switch ($conf['type']) {
						case 'group':

						case 'select':
							$vArray = $this->remapListedDBRecords_procDBRefs($conf, $value, $theUidToUpdate, $table);
							if (is_array($vArray)) {
								$newData[$fieldName] = implode(',', $vArray);
							}
							break;
						case 'flex':
							if ($value == 'FlexForm_reference') {
								// This will fetch the new row for the element
								$origRecordRow = $this->recordInfo($table, $theUidToUpdate, '*');
								if (is_array($origRecordRow)) {
									\TYPO3\CMS\Backend\Utility\BackendUtility::workspaceOL($table, $origRecordRow);
									// Get current data structure and value array:
									$dataStructArray = \TYPO3\CMS\Backend\Utility\BackendUtility::getFlexFormDS($conf, $origRecordRow, $table);
									$currentValueArray = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($origRecordRow[$fieldName]);
									// Do recursive processing of the XML data:
									$currentValueArray['data'] = $this->checkValue_flex_procInData($currentValueArray['data'], array(), array(), $dataStructArray, array($table, $theUidToUpdate, $fieldName), 'remapListedDBRecords_flexFormCallBack');
									// The return value should be compiled back into XML, ready to insert directly in the field (as we call updateDB() directly later):
									if (is_array($currentValueArray['data'])) {
										$newData[$fieldName] = $this->checkValue_flexArray2Xml($currentValueArray, TRUE);
									}
								}
							}
							break;
						case 'inline':
							$this->remapListedDBRecords_procInline($conf, $value, $uid, $table);
							break;
						default:
							debug('Field type should not appear here: ' . $conf['type']);
							break;
						}
					}
					// If any fields were changed, those fields are updated!
					if (count($newData)) {
						$this->updateDB($table, $theUidToUpdate_saveTo, $newData);
					}
				}
			}
		}
	}

	/**
	 * Callback function for traversing the FlexForm structure in relation to creating copied files of file relations inside of flex form structures.
	 *
	 * @param array $pParams Set of parameters in numeric array: table, uid, field
	 * @param array $dsConf TCA config for field (from Data Structure of course)
	 * @param string $dataValue Field value (from FlexForm XML)
	 * @param string $dataValue_ext1 Not used
	 * @param string $dataValue_ext2 Not used
	 * @return array Array where the "value" key carries the value.
	 * @see checkValue_flex_procInData_travDS(), remapListedDBRecords()
	 * @todo Define visibility
	 */
	public function remapListedDBRecords_flexFormCallBack($pParams, $dsConf, $dataValue, $dataValue_ext1, $dataValue_ext2) {
		// Extract parameters:
		list($table, $uid, $field) = $pParams;
		// If references are set for this field, set flag so they can be corrected later:
		if ($this->isReferenceField($dsConf) && strlen($dataValue)) {
			$vArray = $this->remapListedDBRecords_procDBRefs($dsConf, $dataValue, $uid, $table);
			if (is_array($vArray)) {
				$dataValue = implode(',', $vArray);
			}
		}
		// Return
		return array('value' => $dataValue);
	}

	/**
	 * Performs remapping of old UID values to NEW uid values for a DB reference field.
	 *
	 * @param array $conf TCA field config
	 * @param string $value Field value
	 * @param integer $MM_localUid UID of local record (for MM relations - might need to change if support for FlexForms should be done!)
	 * @param string $table Table name
	 * @return array Returns array of items ready to implode for field content.
	 * @see remapListedDBRecords()
	 * @todo Define visibility
	 */
	public function remapListedDBRecords_procDBRefs($conf, $value, $MM_localUid, $table) {
		// Initialize variables
		// Will be set TRUE if an upgrade should be done...
		$set = FALSE;
		// Allowed tables for references.
		$allowedTables = $conf['type'] == 'group' ? $conf['allowed'] : $conf['foreign_table'] . ',' . $conf['neg_foreign_table'];
		// Table name to prepend the UID
		$prependName = $conf['type'] == 'group' ? $conf['prepend_tname'] : '';
		// Which tables that should possibly not be remapped
		$dontRemapTables = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $conf['dontRemapTablesOnCopy'], 1);
		// Convert value to list of references:
		$dbAnalysis = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\RelationHandler');
		$dbAnalysis->registerNonTableValues = $conf['type'] == 'select' && $conf['allowNonIdValues'] ? 1 : 0;
		$dbAnalysis->start($value, $allowedTables, $conf['MM'], $MM_localUid, $table, $conf);
		// Traverse those references and map IDs:
		foreach ($dbAnalysis->itemArray as $k => $v) {
			$mapID = $this->copyMappingArray_merged[$v['table']][$v['id']];
			if ($mapID && !in_array($v['table'], $dontRemapTables)) {
				$dbAnalysis->itemArray[$k]['id'] = $mapID;
				$set = TRUE;
			}
		}
		// If a change has been done, set the new value(s)
		if ($set) {
			if ($conf['MM']) {
				$dbAnalysis->writeMM($conf['MM'], $MM_localUid, $prependName);
			} else {
				$vArray = $dbAnalysis->getValueArray($prependName);
				if ($conf['type'] == 'select') {
					$vArray = $dbAnalysis->convertPosNeg($vArray, $conf['foreign_table'], $conf['neg_foreign_table']);
				}
				return $vArray;
			}
		}
	}

	/**
	 * Performs remapping of old UID values to NEW uid values for a inline field.
	 *
	 * @param array $conf TCA field config
	 * @param string $value Field value
	 * @param integer $uid The uid of the ORIGINAL record
	 * @param string $table Table name
	 * @return void
	 * @todo Define visibility
	 */
	public function remapListedDBRecords_procInline($conf, $value, $uid, $table) {
		$theUidToUpdate = $this->copyMappingArray_merged[$table][$uid];
		if ($conf['foreign_table']) {
			$inlineType = $this->getInlineFieldType($conf);
			if ($inlineType == 'mm') {
				$this->remapListedDBRecords_procDBRefs($conf, $value, $theUidToUpdate, $table);
			} elseif ($inlineType !== FALSE) {
				/** @var $dbAnalysis \TYPO3\CMS\Core\Database\RelationHandler */
				$dbAnalysis = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\RelationHandler');
				$dbAnalysis->start($value, $conf['foreign_table'], '', 0, $table, $conf);
				// Update child records if using pointer fields ('foreign_field'):
				if ($inlineType == 'field') {
					$dbAnalysis->writeForeignField($conf, $uid, $theUidToUpdate);
				}
				// If the current field is set on a page record, update the pid of related child records:
				if ($table == 'pages') {
					$thePidToUpdate = $theUidToUpdate;
				} elseif (isset($this->registerDBPids[$table][$uid])) {
					$thePidToUpdate = $this->registerDBPids[$table][$uid];
					$thePidToUpdate = $this->copyMappingArray_merged['pages'][$thePidToUpdate];
				}
				// Update child records if change to pid is required (only if the current record is not on a workspace):
				if ($thePidToUpdate) {
					$updateValues = array('pid' => $thePidToUpdate);
					foreach ($dbAnalysis->itemArray as $v) {
						if ($v['id'] && $v['table'] && is_null(\TYPO3\CMS\Backend\Utility\BackendUtility::getLiveVersionIdOfRecord($v['table'], $v['id']))) {
							$GLOBALS['TYPO3_DB']->exec_UPDATEquery($v['table'], 'uid=' . intval($v['id']), $updateValues);
						}
					}
				}
			}
		}
	}

	/**
	 * Processes the $this->remapStack at the end of copying, inserting, etc. actions.
	 * The remapStack takes care about the correct mapping of new and old uids in case of relational data.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function processRemapStack() {
		// Processes the remap stack:
		if (is_array($this->remapStack)) {
			$remapFlexForms = array();

			foreach ($this->remapStack as $remapAction) {
				// If no position index for the arguments was set, skip this remap action:
				if (!is_array($remapAction['pos'])) {
					continue;
				}
				// Load values from the argument array in remapAction:
				$field = $remapAction['field'];
				$id = $remapAction['args'][$remapAction['pos']['id']];
				$rawId = $id;
				$table = $remapAction['args'][$remapAction['pos']['table']];
				$valueArray = $remapAction['args'][$remapAction['pos']['valueArray']];
				$tcaFieldConf = $remapAction['args'][$remapAction['pos']['tcaFieldConf']];
				$additionalData = $remapAction['additionalData'];
				// The record is new and has one or more new ids (in case of versioning/workspaces):
				if (strpos($id, 'NEW') !== FALSE) {
					// Replace NEW...-ID with real uid:
					$id = $this->substNEWwithIDs[$id];
					// If the new parent record is on a non-live workspace or versionized, it has another new id:
					if (isset($this->autoVersionIdMap[$table][$id])) {
						$id = $this->autoVersionIdMap[$table][$id];
					}
					$remapAction['args'][$remapAction['pos']['id']] = $id;
				}
				// Replace relations to NEW...-IDs in field value (uids of child records):
				if (is_array($valueArray)) {
					$foreign_table = $tcaFieldConf['foreign_table'];
					foreach ($valueArray as $key => $value) {
						if (strpos($value, 'NEW') !== FALSE) {
							$value = $this->substNEWwithIDs[$value];
							// The record is new, but was also auto-versionized and has another new id:
							if (isset($this->autoVersionIdMap[$foreign_table][$value])) {
								$value = $this->autoVersionIdMap[$foreign_table][$value];
							}
							// Set a hint that this was a new child record:
							$this->newRelatedIDs[$foreign_table][] = $value;
							$valueArray[$key] = $value;
						}
					}
					$remapAction['args'][$remapAction['pos']['valueArray']] = $valueArray;
				}
				// Process the arguments with the defined function:
				$newValue = call_user_func_array(array($this, $remapAction['func']), $remapAction['args']);
				// If array is returned, check for maxitems condition, if string is returned this was already done:
				if (is_array($newValue)) {
					$newValue = implode(',', $this->checkValue_checkMax($tcaFieldConf, $newValue));
				}
				// Update in database (list of children (csv) or number of relations (foreign_field)):
				if (!empty($field)) {
					$this->updateDB($table, $id, array($field => $newValue));
				// Collect data to update FlexForms
				} elseif (!empty($additionalData['flexFormId']) && !empty($additionalData['flexFormPath'])) {
					$flexFormId = $additionalData['flexFormId'];
					$flexFormPath = $additionalData['flexFormPath'];

					if (!isset($remapFlexForms[$flexFormId])) {
						$remapFlexForms[$flexFormId] = array();
					}

					$remapFlexForms[$flexFormId][$flexFormPath] = $newValue;
				}
				// Process waiting Hook: processDatamap_afterDatabaseOperations:
				if (isset($this->remapStackRecords[$table][$rawId]['processDatamap_afterDatabaseOperations'])) {
					$hookArgs = $this->remapStackRecords[$table][$rawId]['processDatamap_afterDatabaseOperations'];
					// Update field with remapped data:
					$hookArgs['fieldArray'][$field] = $newValue;
					// Process waiting hook objects:
					$hookObjectsArr = $hookArgs['hookObjectsArr'];
					foreach ($hookObjectsArr as $hookObj) {
						if (method_exists($hookObj, 'processDatamap_afterDatabaseOperations')) {
							$hookObj->processDatamap_afterDatabaseOperations($hookArgs['status'], $table, $rawId, $hookArgs['fieldArray'], $this);
						}
					}
				}
			}

			if ($remapFlexForms) {
				foreach ($remapFlexForms as $flexFormId => $modifications) {
					$this->updateFlexFormData($flexFormId, $modifications);
				}
			}
		}
		// Processes the remap stack actions:
		if ($this->remapStackActions) {
			foreach ($this->remapStackActions as $action) {
				if (isset($action['callback']) && isset($action['arguments'])) {
					call_user_func_array($action['callback'], $action['arguments']);
				}
			}
		}
		// Processes the reference index updates of the remap stack:
		foreach ($this->remapStackRefIndex as $table => $idArray) {
			foreach ($idArray as $id) {
				$this->updateRefIndex($table, $id);
				unset($this->remapStackRefIndex[$table][$id]);
			}
		}
		// Reset:
		$this->remapStack = array();
		$this->remapStackRecords = array();
		$this->remapStackActions = array();
		$this->remapStackRefIndex = array();
	}

	/**
	 * Updates FlexForm data.
	 *
	 * @param string $flexFormId, e.g. <table>:<uid>:<field>
	 * @param array $modifications Modifications with paths and values (e.g. 'sDEF/lDEV/field/vDEF' => 'TYPO3')
	 * @return void
	 */
	protected function updateFlexFormData($flexFormId, array $modifications) {
		list ($table, $uid, $field) = explode(':', $flexFormId, 3);
		$record = $this->recordInfo($table, $uid, '*');

		if (!$table || !$uid || !$field || !is_array($record)) {
			return;
		}

		\TYPO3\CMS\Backend\Utility\BackendUtility::workspaceOL($table, $record);

		// Get current data structure and value array:
		$valueStructure = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($record[$field]);

		// Do recursive processing of the XML data:
		foreach ($modifications as $path => $value) {
			$valueStructure['data'] = \TYPO3\CMS\Core\Utility\ArrayUtility::setValueByPath(
				$valueStructure['data'], $path, $value
			);
		}

		if (is_array($valueStructure['data'])) {
			// The return value should be compiled back into XML
			$values = array(
				$field => $this->checkValue_flexArray2Xml($valueStructure, TRUE),
			);

			$this->updateDB($table, $uid, $values);
		}
	}

	/**
	 * Triggers a remap action for a specific record.
	 *
	 * Some records are post-processed by the processRemapStack() method (e.g. IRRE children).
	 * This method determines wether an action/modification is executed directly to a record
	 * or is postponed to happen after remapping data.
	 *
	 * @param string $table Name of the table
	 * @param string $id Id of the record (can also be a "NEW..." string)
	 * @param array $callback The method to be called
	 * @param array $arguments The arguments to be submitted to the callback method
	 * @param boolean $forceRemapStackActions Whether to force to use the stack
	 * @return void
	 * @see processRemapStack
	 */
	protected function triggerRemapAction($table, $id, array $callback, array $arguments, $forceRemapStackActions = FALSE) {
		// Check whether the affected record is marked to be remapped:
		if (!$forceRemapStackActions && !isset($this->remapStackRecords[$table][$id]) && !isset($this->remapStackChildIds[$id])) {
			call_user_func_array($callback, $arguments);
		} else {
			$this->addRemapAction($table, $id, $callback, $arguments);
		}
	}

	/**
	 * Adds an instruction to the remap action stack (used with IRRE).
	 *
	 * @param string $table The affected table
	 * @param integer $id The affected ID
	 * @param array $callback The callback information (object and method)
	 * @param array $arguments The arguments to be used with the callback
	 * @return void
	 */
	public function addRemapAction($table, $id, array $callback, array $arguments) {
		$this->remapStackActions[] = array(
			'affects' => array(
				'table' => $table,
				'id' => $id
			),
			'callback' => $callback,
			'arguments' => $arguments
		);
	}

	/**
	 * Adds a table-id-pair to the reference index remapping stack.
	 *
	 * @param string $table
	 * @param integer $id
	 * @return void
	 */
	public function addRemapStackRefIndex($table, $id) {
		$this->remapStackRefIndex[$table][$id] = $id;
	}

	/**
	 * If a parent record was versionized on a workspace in $this->process_datamap,
	 * it might be possible, that child records (e.g. on using IRRE) were affected.
	 * This function finds these relations and updates their uids in the $incomingFieldArray.
	 * The $incomingFieldArray is updated by reference!
	 *
	 * @param string $table Table name of the parent record
	 * @param integer $id Uid of the parent record
	 * @param array $incomingFieldArray Reference to the incominfFieldArray of process_datamap
	 * @param array $registerDBList Reference to the $registerDBList array that was created/updated by versionizing calls to TCEmain in process_datamap.
	 * @return void
	 * @todo Define visibility
	 */
	public function getVersionizedIncomingFieldArray($table, $id, &$incomingFieldArray, &$registerDBList) {
		if (is_array($registerDBList[$table][$id])) {
			foreach ($incomingFieldArray as $field => $value) {
				$fieldConf = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
				if ($registerDBList[$table][$id][$field] && ($foreignTable = $fieldConf['foreign_table'])) {
					$newValueArray = array();
					$origValueArray = explode(',', $value);
					// Update the uids of the copied records, but also take care about new records:
					foreach ($origValueArray as $childId) {
						$newValueArray[] = $this->autoVersionIdMap[$foreignTable][$childId] ? $this->autoVersionIdMap[$foreignTable][$childId] : $childId;
					}
					// Set the changed value to the $incomingFieldArray
					$incomingFieldArray[$field] = implode(',', $newValueArray);
				}
			}
			// Clean up the $registerDBList array:
			unset($registerDBList[$table][$id]);
			if (!count($registerDBList[$table])) {
				unset($registerDBList[$table]);
			}
		}
	}

	/*****************************
	 *
	 * Access control / Checking functions
	 *
	 *****************************/
	/**
	 * Checking group modify_table access list
	 *
	 * @param string $table Table name
	 * @return boolean Returns TRUE if the user has general access to modify the $table
	 * @todo Define visibility
	 */
	public function checkModifyAccessList($table) {
		$res = $this->admin || !$this->tableAdminOnly($table) && \TYPO3\CMS\Core\Utility\GeneralUtility::inList($this->BE_USER->groupData['tables_modify'], $table);
		// Hook 'checkModifyAccessList': Post-processing of the state of access
		foreach ($this->getCheckModifyAccessListHookObjects() as $hookObject) {
			/** @var $hookObject \TYPO3\CMS\Core\DataHandling\DataHandlerCheckModifyAccessListHookInterface */
			$hookObject->checkModifyAccessList($res, $table, $this);
		}
		return $res;
	}

	/**
	 * Checking if a record with uid $id from $table is in the BE_USERS webmounts which is required for editing etc.
	 *
	 * @param string $table Table name
	 * @param integer $id UID of record
	 * @return boolean Returns TRUE if OK. Cached results.
	 * @todo Define visibility
	 */
	public function isRecordInWebMount($table, $id) {
		if (!isset($this->isRecordInWebMount_Cache[($table . ':' . $id)])) {
			$recP = $this->getRecordProperties($table, $id);
			$this->isRecordInWebMount_Cache[$table . ':' . $id] = $this->isInWebMount($recP['event_pid']);
		}
		return $this->isRecordInWebMount_Cache[$table . ':' . $id];
	}

	/**
	 * Checks if the input page ID is in the BE_USER webmounts
	 *
	 * @param integer $pid Page ID to check
	 * @return boolean TRUE if OK. Cached results.
	 * @todo Define visibility
	 */
	public function isInWebMount($pid) {
		if (!isset($this->isInWebMount_Cache[$pid])) {
			$this->isInWebMount_Cache[$pid] = $this->BE_USER->isInWebMount($pid);
		}
		return $this->isInWebMount_Cache[$pid];
	}

	/**
	 * Checks if user may update a record with uid=$id from $table
	 *
	 * @param string $table Record table
	 * @param integer $id Record UID
	 * @param array $data Record data
	 * @param array $hookObjectsArr Hook objects
	 * @return boolean Returns TRUE if the user may update the record given by $table and $id
	 * @todo Define visibility
	 */
	public function checkRecordUpdateAccess($table, $id, $data = FALSE, &$hookObjectsArr = FALSE) {
		$res = NULL;
		if (is_array($hookObjectsArr)) {
			foreach ($hookObjectsArr as $hookObj) {
				if (method_exists($hookObj, 'checkRecordUpdateAccess')) {
					$res = $hookObj->checkRecordUpdateAccess($table, $id, $data, $res, $this);
				}
			}
		}
		if ($res === 1 || $res === 0) {
			return $res;
		} else {
			$res = 0;
		}
		if ($GLOBALS['TCA'][$table] && intval($id) > 0) {
			// If information is cached, return it
			if (isset($this->recUpdateAccessCache[$table][$id])) {
				return $this->recUpdateAccessCache[$table][$id];
			} elseif ($this->doesRecordExist($table, $id, 'edit')) {
				$res = 1;
			}
			// Cache the result
			$this->recUpdateAccessCache[$table][$id] = $res;
		}
		return $res;
	}

	/**
	 * Checks if user may insert a record from $insertTable on $pid
	 * Does not check for workspace, use BE_USER->workspaceAllowLiveRecordsInPID for this in addition to this function call.
	 *
	 * @param string $insertTable Tablename to check
	 * @param integer $pid Integer PID
	 * @param integer $action For logging: Action number.
	 * @return boolean Returns TRUE if the user may insert a record from table $insertTable on page $pid
	 * @todo Define visibility
	 */
	public function checkRecordInsertAccess($insertTable, $pid, $action = 1) {
		$res = 0;
		$pid = intval($pid);
		if ($pid >= 0) {
			// If information is cached, return it
			if (isset($this->recInsertAccessCache[$insertTable][$pid])) {
				return $this->recInsertAccessCache[$insertTable][$pid];
			} else {
				// If either admin and root-level or if page record exists and 1) if 'pages' you may create new ones 2) if page-content, new content items may be inserted on the $pid page
				if (!$pid && $this->admin || $this->doesRecordExist('pages', $pid, ($insertTable == 'pages' ? $this->pMap['new'] : $this->pMap['editcontent']))) {
					// Check permissions
					if ($this->isTableAllowedForThisPage($pid, $insertTable)) {
						$res = 1;
						// Cache the result
						$this->recInsertAccessCache[$insertTable][$pid] = $res;
					} else {
						$propArr = $this->getRecordProperties('pages', $pid);
						$this->log($insertTable, $pid, $action, 0, 1, 'Attempt to insert record on page \'%s\' (%s) where this table, %s, is not allowed', 11, array($propArr['header'], $pid, $insertTable), $propArr['event_pid']);
					}
				} else {
					$propArr = $this->getRecordProperties('pages', $pid);
					$this->log($insertTable, $pid, $action, 0, 1, 'Attempt to insert a record on page \'%s\' (%s) from table \'%s\' without permissions. Or non-existing page.', 12, array($propArr['header'], $pid, $insertTable), $propArr['event_pid']);
				}
			}
		}
		return $res;
	}

	/**
	 * Checks if a table is allowed on a certain page id according to allowed tables set for the page "doktype" and its [ctrl][rootLevel]-settings if any.
	 *
	 * @param integer $page_uid Page id for which to check, including 0 (zero) if checking for page tree root.
	 * @param string $checkTable Table name to check
	 * @return boolean TRUE if OK
	 * @todo Define visibility
	 */
	public function isTableAllowedForThisPage($page_uid, $checkTable) {
		$page_uid = intval($page_uid);
		// Check if rootLevel flag is set and we're trying to insert on rootLevel - and reversed - and that the table is not "pages" which are allowed anywhere.
		if (($GLOBALS['TCA'][$checkTable]['ctrl']['rootLevel'] xor !$page_uid) && $GLOBALS['TCA'][$checkTable]['ctrl']['rootLevel'] != -1 && $checkTable != 'pages') {
			return FALSE;
		}
		// Check root-level
		if (!$page_uid) {
			if ($this->admin) {
				return TRUE;
			}
		} else {
			// Check non-root-level
			$doktype = $this->pageInfo($page_uid, 'doktype');
			$allowedTableList = isset($GLOBALS['PAGES_TYPES'][$doktype]['allowedTables']) ? $GLOBALS['PAGES_TYPES'][$doktype]['allowedTables'] : $GLOBALS['PAGES_TYPES']['default']['allowedTables'];
			$allowedArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $allowedTableList, 1);
			// If all tables or the table is listed as a allowed type, return TRUE
			if (strstr($allowedTableList, '*') || in_array($checkTable, $allowedArray)) {
				return TRUE;
			}
		}
	}

	/**
	 * Checks if record can be selected based on given permission criteria
	 *
	 * @param string $table Record table name
	 * @param integer $id Record UID
	 * @param mixed $perms Permission restrictions to observe: Either an integer that will be bitwise AND'ed or a string, which points to a key in the ->pMap array
	 * @return boolean Returns TRUE if the record given by $table, $id and $perms can be selected
	 * @todo Define visibility
	 */
	public function doesRecordExist($table, $id, $perms) {
		if ($this->bypassAccessCheckForRecords) {
			return is_array(\TYPO3\CMS\Backend\Utility\BackendUtility::getRecordRaw($table, 'uid=' . intval($id), 'uid'));
		}
		$res = 0;
		$id = intval($id);
		// Processing the incoming $perms (from possible string to integer that can be AND'ed)
		if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($perms)) {
			if ($table != 'pages') {
				switch ($perms) {
				case 'edit':

				case 'delete':

				case 'new':
					// This holds it all in case the record is not page!!
					$perms = 'editcontent';
					break;
				}
			}
			$perms = intval($this->pMap[$perms]);
		} else {
			$perms = intval($perms);
		}
		if (!$perms) {
			throw new \RuntimeException('Internal ERROR: no permissions to check for non-admin user', 1270853920);
		}
		// For all tables: Check if record exists:
		$isWebMountRestrictionIgnored = \TYPO3\CMS\Backend\Utility\BackendUtility::isWebMountRestrictionIgnored($table);
		if (is_array($GLOBALS['TCA'][$table]) && $id > 0 && ($isWebMountRestrictionIgnored || $this->isRecordInWebMount($table, $id) || $this->admin)) {
			if ($table != 'pages') {
				// Find record without checking page:
				$mres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,pid', $table, 'uid=' . intval($id) . $this->deleteClause($table));
				// THIS SHOULD CHECK FOR editlock I think!
				$output = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($mres);
				\TYPO3\CMS\Backend\Utility\BackendUtility::fixVersioningPid($table, $output, TRUE);
				// If record found, check page as well:
				if (is_array($output)) {
					// Looking up the page for record:
					$mres = $this->doesRecordExist_pageLookUp($output['pid'], $perms);
					$pageRec = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($mres);
					// Return TRUE if either a page was found OR if the PID is zero AND the user is ADMIN (in which case the record is at root-level):
					$isRootLevelRestrictionIgnored = \TYPO3\CMS\Backend\Utility\BackendUtility::isRootLevelRestrictionIgnored($table);
					if (is_array($pageRec) || !$output['pid'] && ($isRootLevelRestrictionIgnored || $this->admin)) {
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
	 * @param integer $id Page id
	 * @param integer $perms Permission integer
	 * @return boolean|\mysqli_result|object MySQLi result object / DBAL object (from exec_SELECTquery())
	 * @access private
	 * @see doesRecordExist()
	 * @todo Define visibility
	 */
	public function doesRecordExist_pageLookUp($id, $perms) {
		return $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'pages', 'uid=' . intval($id) . $this->deleteClause('pages') . ($perms && !$this->admin ? ' AND ' . $this->BE_USER->getPagePermsClause($perms) : '') . (!$this->admin && $GLOBALS['TCA']['pages']['ctrl']['editlock'] && $perms & 2 + 4 + 16 ? ' AND ' . $GLOBALS['TCA']['pages']['ctrl']['editlock'] . '=0' : ''));
	}

	/**
	 * Checks if a whole branch of pages exists
	 *
	 * Tests the branch under $pid (like doesRecordExist). It doesn't test the page with $pid as uid. Use doesRecordExist() for this purpose
	 * Returns an ID-list or "" if OK. Else -1 which means that somewhere there was no permission (eg. to delete).
	 * if $recurse is set, then the function will follow subpages. This MUST be set, if we need the idlist for deleting pages or else we get an incomplete list
	 *
	 * @param string $inList List of page uids, this is added to and outputted in the end
	 * @param integer $pid Page ID to select subpages from.
	 * @param integer $perms Perms integer to check each page record for.
	 * @param boolean $recurse Recursion flag: If set, it will go out through the branch.
	 * @return string List of integers in branch
	 * @todo Define visibility
	 */
	public function doesBranchExist($inList, $pid, $perms, $recurse) {
		$pid = intval($pid);
		$perms = intval($perms);
		if ($pid >= 0) {
			$mres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, perms_userid, perms_groupid, perms_user, perms_group, perms_everybody', 'pages', 'pid=' . intval($pid) . $this->deleteClause('pages'), '', 'sorting');
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($mres)) {
				// IF admin, then it's OK
				if ($this->admin || $this->BE_USER->doesUserHaveAccess($row, $perms)) {
					$inList .= $row['uid'] . ',';
					if ($recurse) {
						// Follow the subpages recursively...
						$inList = $this->doesBranchExist($inList, $row['uid'], $perms, $recurse);
						if ($inList == -1) {
							return -1;
						}
					}
				} else {
					// No permissions
					return -1;
				}
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($mres);
		}
		return $inList;
	}

	/**
	 * Checks if the $table is readOnly
	 *
	 * @param string $table Table name
	 * @return boolean TRUE, if readonly
	 * @todo Define visibility
	 */
	public function tableReadOnly($table) {
		// Returns TRUE if table is readonly
		return $GLOBALS['TCA'][$table]['ctrl']['readOnly'] ? TRUE : FALSE;
	}

	/**
	 * Checks if the $table is only editable by admin-users
	 *
	 * @param string $table Table name
	 * @return boolean TRUE, if readonly
	 * @todo Define visibility
	 */
	public function tableAdminOnly($table) {
		// Returns TRUE if table is admin-only
		return $GLOBALS['TCA'][$table]['ctrl']['adminOnly'] ? TRUE : FALSE;
	}

	/**
	 * Checks if piage $id is a uid in the rootline from page id, $dest
	 * Used when moving a page
	 *
	 * @param integer $dest Destination Page ID to test
	 * @param integer $id Page ID to test for presence inside Destination
	 * @return boolean Returns FALSE if ID is inside destination (including equal to)
	 * @todo Define visibility
	 */
	public function destNotInsideSelf($dest, $id) {
		$loopCheck = 100;
		$dest = intval($dest);
		$id = intval($id);
		if ($dest == $id) {
			return FALSE;
		}
		while ($dest != 0 && $loopCheck > 0) {
			$loopCheck--;
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('pid, uid, t3ver_oid,t3ver_wsid', 'pages', 'uid=' . intval($dest) . $this->deleteClause('pages'));
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				\TYPO3\CMS\Backend\Utility\BackendUtility::fixVersioningPid('pages', $row);
				if ($row['pid'] == $id) {
					return FALSE;
				} else {
					$dest = $row['pid'];
				}
			} else {
				return FALSE;
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}
		return TRUE;
	}

	/**
	 * Generate an array of fields to be excluded from editing for the user. Based on "exclude"-field in TCA and a look up in non_exclude_fields
	 * Will also generate this list for admin-users so they must be check for before calling the function
	 *
	 * @return array Array of [table]-[field] pairs to exclude from editing.
	 * @todo Define visibility
	 */
	public function getExcludeListArray() {
		$list = array();
		foreach (array_keys($GLOBALS['TCA']) as $table) {
			if (isset($GLOBALS['TCA'][$table]['columns'])) {
				foreach ($GLOBALS['TCA'][$table]['columns'] as $field => $config) {
					if ($config['exclude'] && !\TYPO3\CMS\Core\Utility\GeneralUtility::inList($this->BE_USER->groupData['non_exclude_fields'], ($table . ':' . $field))) {
						$list[] = $table . '-' . $field;
					}
				}
			}
		}
		return $list;
	}

	/**
	 * Checks if there are records on a page from tables that are not allowed
	 *
	 * @param integer $page_uid Page ID
	 * @param integer $doktype Page doktype
	 * @return array Returns a list of the tables that are 'present' on the page but not allowed with the page_uid/doktype
	 * @todo Define visibility
	 */
	public function doesPageHaveUnallowedTables($page_uid, $doktype) {
		$page_uid = intval($page_uid);
		if (!$page_uid) {
			// Not a number. Probably a new page
			return FALSE;
		}
		$allowedTableList = isset($GLOBALS['PAGES_TYPES'][$doktype]['allowedTables']) ? $GLOBALS['PAGES_TYPES'][$doktype]['allowedTables'] : $GLOBALS['PAGES_TYPES']['default']['allowedTables'];
		$allowedArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $allowedTableList, 1);
		// If all tables is OK the return TRUE
		if (strstr($allowedTableList, '*')) {
			// OK...
			return FALSE;
		}
		$tableList = array();
		foreach (array_keys($GLOBALS['TCA']) as $table) {
			// If the table is not in the allowed list, check if there are records...
			if (!in_array($table, $allowedArray)) {
				$count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('uid', $table, 'pid=' . intval($page_uid));
				if ($count) {
					$tableList[] = $table;
				}
			}
		}
		return implode(',', $tableList);
	}

	/*****************************
	 *
	 * Information lookup
	 *
	 *****************************/
	/**
	 * Returns the value of the $field from page $id
	 * NOTICE; the function caches the result for faster delivery next time. You can use this function repeatedly without performanceloss since it doesn't look up the same record twice!
	 *
	 * @param integer $id Page uid
	 * @param string $field Field name for which to return value
	 * @return string Value of the field. Result is cached in $this->pageCache[$id][$field] and returned from there next time!
	 * @todo Define visibility
	 */
	public function pageInfo($id, $field) {
		if (!isset($this->pageCache[$id])) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'pages', 'uid=' . intval($id));
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($res)) {
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
	 * @param string $table Table name
	 * @param integer $id UID of the record from $table
	 * @param string $fieldList Field list for the SELECT query, eg. "*" or "uid,pid,...
	 * @return mixed Returns the selected record on success, otherwise FALSE.
	 * @todo Define visibility
	 */
	public function recordInfo($table, $id, $fieldList) {
		if (is_array($GLOBALS['TCA'][$table])) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fieldList, $table, 'uid=' . intval($id));
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($res)) {
				$result = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
				return $result;
			}
		}
	}

	/**
	 * Returns an array with record properties, like header and pid
	 * No check for deleted or access is done!
	 * For versionized records, pid is resolved to its live versions pid.
	 * Used for loggin
	 *
	 * @param string $table Table name
	 * @param integer $id Uid of record
	 * @param boolean $noWSOL If set, no workspace overlay is performed
	 * @return array Properties of record
	 * @todo Define visibility
	 */
	public function getRecordProperties($table, $id, $noWSOL = FALSE) {
		$row = $table == 'pages' && !$id ? array('title' => '[root-level]', 'uid' => 0, 'pid' => 0) : $this->recordInfo($table, $id, '*');
		if (!$noWSOL) {
			\TYPO3\CMS\Backend\Utility\BackendUtility::workspaceOL($table, $row);
		}
		return $this->getRecordPropertiesFromRow($table, $row);
	}

	/**
	 * Returns an array with record properties, like header and pid, based on the row
	 *
	 * @param string $table Table name
	 * @param array $row Input row
	 * @return array Output array
	 * @todo Define visibility
	 */
	public function getRecordPropertiesFromRow($table, $row) {
		if ($GLOBALS['TCA'][$table]) {
			\TYPO3\CMS\Backend\Utility\BackendUtility::fixVersioningPid($table, $row);
			$out = array(
				'header' => \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle($table, $row),
				'pid' => $row['pid'],
				'event_pid' => $this->eventPid($table, isset($row['_ORIG_pid']) ? $row['t3ver_oid'] : $row['uid'], $row['pid']),
				't3ver_state' => $GLOBALS['TCA'][$table]['ctrl']['versioningWS'] ? $row['t3ver_state'] : '',
				'_ORIG_pid' => $row['_ORIG_pid']
			);
			return $out;
		}
	}

	/**
	 * @param string $table
	 * @param integer $uid
	 * @param integer $pid
	 * @return integer
	 * @todo Define visibility
	 */
	public function eventPid($table, $uid, $pid) {
		return $table == 'pages' ? $uid : $pid;
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
	 * @param string $table Record table name
	 * @param integer $id Record uid
	 * @param array $fieldArray Array of field=>value pairs to insert. FIELDS MUST MATCH the database FIELDS. No check is done.
	 * @return void
	 * @todo Define visibility
	 */
	public function updateDB($table, $id, $fieldArray) {
		if (is_array($fieldArray) && is_array($GLOBALS['TCA'][$table]) && intval($id)) {
			// Do NOT update the UID field, ever!
			unset($fieldArray['uid']);
			if (count($fieldArray)) {
				$fieldArray = $this->insertUpdateDB_preprocessBasedOnFieldType($table, $fieldArray);
				// Execute the UPDATE query:
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid=' . intval($id), $fieldArray);
				// If succees, do...:
				if (!$GLOBALS['TYPO3_DB']->sql_error()) {
					if ($this->checkStoredRecords) {
						$newRow = $this->checkStoredRecord($table, $id, $fieldArray, 2);
					}
					// Update reference index:
					$this->updateRefIndex($table, $id);
					// Set log entry:
					$propArr = $this->getRecordPropertiesFromRow($table, $newRow);
					$theLogId = $this->log($table, $id, 2, $propArr['pid'], 0, 'Record \'%s\' (%s) was updated.' . ($propArr['_ORIG_pid'] == -1 ? ' (Offline version).' : ' (Online).'), 10, array($propArr['header'], $table . ':' . $id), $propArr['event_pid']);
					// Set History data:
					$this->setHistory($table, $id, $theLogId);
					// Clear cache for relevant pages:
					$this->clear_cache($table, $id);
					// Unset the pageCache for the id if table was page.
					if ($table == 'pages') {
						unset($this->pageCache[$id]);
					}
				} else {
					$this->log($table, $id, 2, 0, 2, 'SQL error: \'%s\' (%s)', 12, array($GLOBALS['TYPO3_DB']->sql_error(), $table . ':' . $id));
				}
			}
		}
	}

	/**
	 * Insert into database
	 * Does not check permissions but expects them to be verified on beforehand
	 *
	 * @param string $table Record table name
	 * @param string $id "NEW...." uid string
	 * @param array $fieldArray Array of field=>value pairs to insert. FIELDS MUST MATCH the database FIELDS. No check is done. "pid" must point to the destination of the record!
	 * @param boolean $newVersion Set to TRUE if new version is created.
	 * @param integer $suggestedUid Suggested UID value for the inserted record. See the array $this->suggestedInsertUids; Admin-only feature
	 * @param boolean $dontSetNewIdIndex If TRUE, the ->substNEWwithIDs array is not updated. Only useful in very rare circumstances!
	 * @return integer Returns ID on success.
	 * @todo Define visibility
	 */
	public function insertDB($table, $id, $fieldArray, $newVersion = FALSE, $suggestedUid = 0, $dontSetNewIdIndex = FALSE) {
		if (is_array($fieldArray) && is_array($GLOBALS['TCA'][$table]) && isset($fieldArray['pid'])) {
			// Do NOT insert the UID field, ever!
			unset($fieldArray['uid']);
			if (count($fieldArray)) {
				// Check for "suggestedUid".
				// This feature is used by the import functionality to force a new record to have a certain UID value.
				// This is only recommended for use when the destination server is a passive mirrow of another server.
				// As a security measure this feature is available only for Admin Users (for now)
				$suggestedUid = intval($suggestedUid);
				if ($this->BE_USER->isAdmin() && $suggestedUid && $this->suggestedInsertUids[$table . ':' . $suggestedUid]) {
					// When the value of ->suggestedInsertUids[...] is "DELETE" it will try to remove the previous record
					if ($this->suggestedInsertUids[$table . ':' . $suggestedUid] === 'DELETE') {
						// DELETE:
						$GLOBALS['TYPO3_DB']->exec_DELETEquery($table, 'uid=' . intval($suggestedUid));
					}
					$fieldArray['uid'] = $suggestedUid;
				}
				$fieldArray = $this->insertUpdateDB_preprocessBasedOnFieldType($table, $fieldArray);
				// Execute the INSERT query:
				$GLOBALS['TYPO3_DB']->exec_INSERTquery($table, $fieldArray);
				// If succees, do...:
				if (!$GLOBALS['TYPO3_DB']->sql_error()) {
					// Set mapping for NEW... -> real uid:
					// the NEW_id now holds the 'NEW....' -id
					$NEW_id = $id;
					$id = $GLOBALS['TYPO3_DB']->sql_insert_id();
					if (!$dontSetNewIdIndex) {
						$this->substNEWwithIDs[$NEW_id] = $id;
						$this->substNEWwithIDs_table[$NEW_id] = $table;
					}
					// Checking the record is properly saved and writing to log
					if ($this->checkStoredRecords) {
						$newRow = $this->checkStoredRecord($table, $id, $fieldArray, 1);
					}
					// Update reference index:
					$this->updateRefIndex($table, $id);
					if ($newVersion) {
						$propArr = $this->getRecordPropertiesFromRow($table, $newRow);
						$this->log($table, $id, 1, 0, 0, 'New version created of table \'%s\', uid \'%s\'. UID of new version is \'%s\'', 10, array($table, $fieldArray['t3ver_oid'], $id), $propArr['event_pid'], $NEW_id);
					} else {
						$propArr = $this->getRecordPropertiesFromRow($table, $newRow);
						$page_propArr = $this->getRecordProperties('pages', $propArr['pid']);
						$this->log($table, $id, 1, 0, 0, 'Record \'%s\' (%s) was inserted on page \'%s\' (%s)', 10, array($propArr['header'], $table . ':' . $id, $page_propArr['header'], $newRow['pid']), $newRow['pid'], $NEW_id);
						// Clear cache for relavant pages:
						$this->clear_cache($table, $id);
					}
					return $id;
				} else {
					$this->log($table, $id, 1, 0, 2, 'SQL error: \'%s\' (%s)', 12, array($GLOBALS['TYPO3_DB']->sql_error(), $table . ':' . $id));
				}
			}
		}
	}

	/**
	 * Checking stored record to see if the written values are properly updated.
	 *
	 * @param string $table Record table name
	 * @param integer $id Record uid
	 * @param array $fieldArray Array of field=>value pairs to insert/update
	 * @param string $action Action, for logging only.
	 * @return array Selected row
	 * @see insertDB(), updateDB()
	 * @todo Define visibility
	 */
	public function checkStoredRecord($table, $id, $fieldArray, $action) {
		$id = intval($id);
		if (is_array($GLOBALS['TCA'][$table]) && $id) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, 'uid=' . intval($id));
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				// Traverse array of values that was inserted into the database and compare with the actually stored value:
				$errorString = array();
				foreach ($fieldArray as $key => $value) {
					if ($this->checkStoredRecords_loose && !$value && !$row[$key]) {

					} elseif (strcmp($value, $row[$key])) {
						$errorString[] = $key;
					}
				}
				// Set log message if there were fields with unmatching values:
				if (count($errorString)) {
					$this->log($table, $id, $action, 0, 102, 'These fields are not properly updated in database: (' . implode(',', $errorString) . ') Probably value mismatch with fieldtype.');
				}
				// Return selected rows:
				return $row;
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}
	}

	/**
	 * Setting sys_history record, based on content previously set in $this->historyRecords[$table . ':' . $id] (by compareFieldArrayWithCurrentAndUnset())
	 *
	 * @param string $table Table name
	 * @param integer $id Record ID
	 * @param integer $logId Log entry ID, important for linking between log and history views
	 * @return void
	 * @todo Define visibility
	 */
	public function setHistory($table, $id, $logId) {
		if (isset($this->historyRecords[$table . ':' . $id])) {
			$fields_values = array();
			$fields_values['history_data'] = serialize($this->historyRecords[$table . ':' . $id]);
			$fields_values['fieldlist'] = implode(',', array_keys($this->historyRecords[$table . ':' . $id]['newRecord']));
			$fields_values['tstamp'] = $GLOBALS['EXEC_TIME'];
			$fields_values['tablename'] = $table;
			$fields_values['recuid'] = $id;
			$fields_values['sys_log_uid'] = $logId;
			$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_history', $fields_values);
		}
	}

	/**
	 * Update Reference Index (sys_refindex) for a record
	 * Should be called any almost any update to a record which could affect references inside the record.
	 *
	 * @param string $table Table name
	 * @param integer $id Record UID
	 * @return void
	 * @todo Define visibility
	 */
	public function updateRefIndex($table, $id) {
		/** @var $refIndexObj \TYPO3\CMS\Core\Database\ReferenceIndex */
		$refIndexObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\ReferenceIndex');
		$result = $refIndexObj->updateRefIndexTable($table, $id);
	}

	/*********************************************
	 *
	 * Misc functions
	 *
	 ********************************************/
	/**
	 * Returning sorting number for tables with a "sortby" column
	 * Using when new records are created and existing records are moved around.
	 *
	 * @param string $table Table name
	 * @param integer $uid Uid of record to find sorting number for. May be zero in case of new.
	 * @param integer $pid Positioning PID, either >=0 (pointing to page in which case we find sorting number for first record in page) or <0 (pointing to record in which case to find next sorting number after this record)
	 * @return mixed Returns integer if PID is >=0, otherwise an array with PID and sorting number. Possibly FALSE in case of error.
	 * @todo Define visibility
	 */
	public function getSortNumber($table, $uid, $pid) {
		if ($GLOBALS['TCA'][$table] && $GLOBALS['TCA'][$table]['ctrl']['sortby']) {
			$sortRow = $GLOBALS['TCA'][$table]['ctrl']['sortby'];
			// Sorting number is in the top
			if ($pid >= 0) {
				// Fetches the first record under this pid
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($sortRow . ',pid,uid', $table, 'pid=' . intval($pid) . $this->deleteClause($table), '', $sortRow . ' ASC', '1');
				// There was an element
				if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					// The top record was the record it self, so we return its current sortnumber
					if ($row['uid'] == $uid) {
						return $row[$sortRow];
					}
					// If the pages sortingnumber < 1 we must resort the records under this pid
					if ($row[$sortRow] < 1) {
						$this->resorting($table, $pid, $sortRow, 0);
						// First sorting number after resorting
						return $this->sortIntervals;
					} else {
						// Sorting number between current top element and zero
						return floor($row[$sortRow] / 2);
					}
				} else {
					// No pages, so we choose the default value as sorting-number
					// First sorting number if no elements.
					return $this->sortIntervals;
				}
			} else {
				// Sorting number is inside the list
				// Fetches the record which is supposed to be the prev record
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($sortRow . ',pid,uid', $table, 'uid=' . abs($pid) . $this->deleteClause($table));
				// There was a record
				if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					// Look, if the record UID happens to be an offline record. If so, find its live version. Offline uids will be used when a page is versionized as "branch" so this is when we must correct - otherwise a pid of "-1" and a wrong sort-row number is returned which we don't want.
					if ($lookForLiveVersion = \TYPO3\CMS\Backend\Utility\BackendUtility::getLiveVersionOfRecord($table, $row['uid'], $sortRow . ',pid,uid')) {
						$row = $lookForLiveVersion;
					}
					// If the record should be inserted after itself, keep the current sorting information:
					if ($row['uid'] == $uid) {
						$sortNumber = $row[$sortRow];
					} else {
						$subres = $GLOBALS['TYPO3_DB']->exec_SELECTquery($sortRow . ',pid,uid', $table, 'pid=' . intval($row['pid']) . ' AND ' . $sortRow . '>=' . intval($row[$sortRow]) . $this->deleteClause($table), '', $sortRow . ' ASC', '2');
						// Fetches the next record in order to calculate the in-between sortNumber
						// There was a record afterwards
						if ($GLOBALS['TYPO3_DB']->sql_num_rows($subres) == 2) {
							// Forward to the second result...
							$GLOBALS['TYPO3_DB']->sql_fetch_assoc($subres);
							// There was a record afterwards
							$subrow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($subres);
							// The sortNumber is found in between these values
							$sortNumber = $row[$sortRow] + floor(($subrow[$sortRow] - $row[$sortRow]) / 2);
							// The sortNumber happend NOT to be between the two surrounding numbers, so we'll have to resort the list
							if ($sortNumber <= $row[$sortRow] || $sortNumber >= $subrow[$sortRow]) {
								// By this special param, resorting reserves and returns the sortnumber after the uid
								$sortNumber = $this->resorting($table, $row['pid'], $sortRow, $row['uid']);
							}
						} else {
							// If after the last record in the list, we just add the sortInterval to the last sortvalue
							$sortNumber = $row[$sortRow] + $this->sortIntervals;
						}
						$GLOBALS['TYPO3_DB']->sql_free_result($subres);
					}
					return array('pid' => $row['pid'], 'sortNumber' => $sortNumber);
				} else {
					$propArr = $this->getRecordProperties($table, $uid);
					// OK, dont insert $propArr['event_pid'] here...
					$this->log($table, $uid, 4, 0, 1, 'Attempt to move record \'%s\' (%s) to after a non-existing record (uid=%s)', 1, array($propArr['header'], $table . ':' . $uid, abs($pid)), $propArr['pid']);
					// There MUST be a page or else this cannot work
					return FALSE;
				}
			}
		}
	}

	/**
	 * Resorts a table.
	 * Used internally by getSortNumber()
	 *
	 * @param string $table Table name
	 * @param integer $pid Pid in which to resort records.
	 * @param string $sortRow Sorting row
	 * @param integer $return_SortNumber_After_This_Uid Uid of record from $table in this $pid and for which the return value will be set to a free sorting number after that record. This is used to return a sortingValue if the list is resorted because of inserting records inside the list and not in the top
	 * @return integer If $return_SortNumber_After_This_Uid is set, will contain usable sorting number after that record if found (otherwise 0)
	 * @access private
	 * @see getSortNumber()
	 * @todo Define visibility
	 */
	public function resorting($table, $pid, $sortRow, $return_SortNumber_After_This_Uid) {
		if ($GLOBALS['TCA'][$table] && $sortRow && $GLOBALS['TCA'][$table]['ctrl']['sortby'] == $sortRow) {
			$returnVal = 0;
			$intervals = $this->sortIntervals;
			$i = $intervals * 2;
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', $table, 'pid=' . intval($pid) . $this->deleteClause($table), '', $sortRow . ' ASC');
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$uid = intval($row['uid']);
				if ($uid) {
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid=' . intval($uid), array($sortRow => $i));
					// This is used to return a sortingValue if the list is resorted because of inserting records inside the list and not in the top
					if ($uid == $return_SortNumber_After_This_Uid) {
						$i = $i + $intervals;
						$returnVal = $i;
					}
				} else {
					die('Fatal ERROR!! No Uid at resorting.');
				}
				$i = $i + $intervals;
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
			return $returnVal;
		}
	}

	/**
	 * Returning uid of previous localized record, if any, for tables with a "sortby" column
	 * Used when new localized records are created so that localized records are sorted in the same order as the default language records
	 *
	 * @param string $table Table name
	 * @param integer $uid Uid of default language record
	 * @param integer $pid Pid of default language record
	 * @param integer $language Language of localization
	 * @return integer uid of record after which the localized record should be inserted
	 */
	protected function getPreviousLocalizedRecordUid($table, $uid, $pid, $language) {
		$previousLocalizedRecordUid = $uid;
		if ($GLOBALS['TCA'][$table] && $GLOBALS['TCA'][$table]['ctrl']['sortby']) {
			$sortRow = $GLOBALS['TCA'][$table]['ctrl']['sortby'];
			$select = $sortRow . ',pid,uid';
			// For content elements, we also need the colPos
			if ($table === 'tt_content') {
				$select .= ',colPos';
			}
			// Get the sort value of the default language record
			$row = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($table, $uid, $select);
			if (is_array($row)) {
				// Find the previous record in default language on the same page
				$where = 'pid=' . intval($pid) . ' AND ' . 'sys_language_uid=0' . ' AND ' . $sortRow . '<' . intval($row[$sortRow]);
				// Respect the colPos for content elements
				if ($table === 'tt_content') {
					$where .= ' AND colPos=' . intval($row['colPos']);
				}
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table, $where . $this->deleteClause($table), '', $sortRow . ' DESC', '1');
				// If there is an element, find its localized record in specified localization language
				if ($previousRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					$previousLocalizedRecord = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordLocalization($table, $previousRow['uid'], $language);
					if (is_array($previousLocalizedRecord[0])) {
						$previousLocalizedRecordUid = $previousLocalizedRecord[0]['uid'];
					}
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
			}
		}
		return $previousLocalizedRecordUid;
	}

	/**
	 * Setting up perms_* fields in $fieldArray based on TSconfig input
	 * Used for new pages
	 *
	 * @param array $fieldArray Field Array, returned with modifications
	 * @param array $TSConfig_p TSconfig properties
	 * @return array Modified Field Array
	 * @todo Define visibility
	 */
	public function setTSconfigPermissions($fieldArray, $TSConfig_p) {
		if (strcmp($TSConfig_p['userid'], '')) {
			$fieldArray['perms_userid'] = intval($TSConfig_p['userid']);
		}
		if (strcmp($TSConfig_p['groupid'], '')) {
			$fieldArray['perms_groupid'] = intval($TSConfig_p['groupid']);
		}
		if (strcmp($TSConfig_p['user'], '')) {
			$fieldArray['perms_user'] = \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($TSConfig_p['user']) ? $TSConfig_p['user'] : $this->assemblePermissions($TSConfig_p['user']);
		}
		if (strcmp($TSConfig_p['group'], '')) {
			$fieldArray['perms_group'] = \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($TSConfig_p['group']) ? $TSConfig_p['group'] : $this->assemblePermissions($TSConfig_p['group']);
		}
		if (strcmp($TSConfig_p['everybody'], '')) {
			$fieldArray['perms_everybody'] = \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($TSConfig_p['everybody']) ? $TSConfig_p['everybody'] : $this->assemblePermissions($TSConfig_p['everybody']);
		}
		return $fieldArray;
	}

	/**
	 * Returns a fieldArray with default values. Values will be picked up from the TCA array looking at the config key "default" for each column. If values are set in ->defaultValues they will overrule though.
	 * Used for new records and during copy operations for defaults
	 *
	 * @param string $table Table name for which to set default values.
	 * @return array Array with default values.
	 * @todo Define visibility
	 */
	public function newFieldArray($table) {
		$fieldArray = array();
		if (is_array($GLOBALS['TCA'][$table]['columns'])) {
			foreach ($GLOBALS['TCA'][$table]['columns'] as $field => $content) {
				if (isset($this->defaultValues[$table][$field])) {
					$fieldArray[$field] = $this->defaultValues[$table][$field];
				} elseif (isset($content['config']['default'])) {
					$fieldArray[$field] = $content['config']['default'];
				}
			}
		}
		// Set default permissions for a page.
		if ($table === 'pages') {
			$fieldArray['perms_userid'] = $this->userid;
			$fieldArray['perms_groupid'] = intval($this->BE_USER->firstMainGroup);
			$fieldArray['perms_user'] = $this->assemblePermissions($this->defaultPermissions['user']);
			$fieldArray['perms_group'] = $this->assemblePermissions($this->defaultPermissions['group']);
			$fieldArray['perms_everybody'] = $this->assemblePermissions($this->defaultPermissions['everybody']);
		}
		return $fieldArray;
	}

	/**
	 * If a "languageField" is specified for $table this function will add a possible value to the incoming array if none is found in there already.
	 *
	 * @param string $table Table name
	 * @param array $incomingFieldArray Incoming array (passed by reference)
	 * @return void
	 * @todo Define visibility
	 */
	public function addDefaultPermittedLanguageIfNotSet($table, &$incomingFieldArray) {
		// Checking languages:
		if ($GLOBALS['TCA'][$table]['ctrl']['languageField']) {
			if (!isset($incomingFieldArray[$GLOBALS['TCA'][$table]['ctrl']['languageField']])) {
				// Language field must be found in input row - otherwise it does not make sense.
				$rows = array_merge(array(array('uid' => 0)), $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid', 'sys_language', 'pid=0' . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('sys_language')), array(array('uid' => -1)));
				foreach ($rows as $r) {
					if ($this->BE_USER->checkLanguageAccess($r['uid'])) {
						$incomingFieldArray[$GLOBALS['TCA'][$table]['ctrl']['languageField']] = $r['uid'];
						break;
					}
				}
			}
		}
	}

	/**
	 * Returns the $data array from $table overridden in the fields defined in ->overrideValues.
	 *
	 * @param string $table Table name
	 * @param array $data Data array with fields from table. These will be overlaid with values in $this->overrideValues[$table]
	 * @return array Data array, processed.
	 * @todo Define visibility
	 */
	public function overrideFieldArray($table, $data) {
		if (is_array($this->overrideValues[$table])) {
			$data = array_merge($data, $this->overrideValues[$table]);
		}
		return $data;
	}

	/**
	 * Compares the incoming field array with the current record and unsets all fields which are the same.
	 * Used for existing records being updated
	 *
	 * @param string $table Record table name
	 * @param integer $id Record uid
	 * @param array $fieldArray Array of field=>value pairs intended to be inserted into the database. All keys with values matching exactly the current value will be unset!
	 * @return array Returns $fieldArray. If the returned array is empty, then the record should not be updated!
	 * @todo Define visibility
	 */
	public function compareFieldArrayWithCurrentAndUnset($table, $id, $fieldArray) {
		// Fetch the original record:
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, 'uid=' . intval($id));
		$currentRecord = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		// If the current record exists (which it should...), begin comparison:
		if (is_array($currentRecord)) {
			// Read all field types:
			$c = 0;
			$cRecTypes = array();
			foreach ($currentRecord as $col => $val) {
				$cRecTypes[$col] = $GLOBALS['TYPO3_DB']->sql_field_type($res, $c);
				$c++;
			}
			// Free result:
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
			// Unset the fields which are similar:
			foreach ($fieldArray as $col => $val) {
				$fieldConfiguration = $GLOBALS['TCA'][$table]['columns'][$col]['config'];
				$isNullField = (!empty($fieldConfiguration['eval']) && \TYPO3\CMS\Core\Utility\GeneralUtility::inList($fieldConfiguration['eval'], 'null'));

				// Unset fields if stored and submitted values are equal - except the current field holds MM relations.
				// In general this avoids to store superfluous data which also will be visualized in the editing history.
				if (!$fieldConfiguration['MM'] && $this->isSubmittedValueEqualToStoredValue($val, $currentRecord[$col], $cRecTypes[$col], $isNullField)) {
					unset($fieldArray[$col]);
				} else {
					if (!isset($this->mmHistoryRecords[($table . ':' . $id)]['oldRecord'][$col])) {
						$this->historyRecords[$table . ':' . $id]['oldRecord'][$col] = $currentRecord[$col];
					} elseif ($this->mmHistoryRecords[$table . ':' . $id]['oldRecord'][$col] != $this->mmHistoryRecords[$table . ':' . $id]['newRecord'][$col]) {
						$this->historyRecords[$table . ':' . $id]['oldRecord'][$col] = $this->mmHistoryRecords[$table . ':' . $id]['oldRecord'][$col];
					}
					if (!isset($this->mmHistoryRecords[($table . ':' . $id)]['newRecord'][$col])) {
						$this->historyRecords[$table . ':' . $id]['newRecord'][$col] = $fieldArray[$col];
					} elseif ($this->mmHistoryRecords[$table . ':' . $id]['newRecord'][$col] != $this->mmHistoryRecords[$table . ':' . $id]['oldRecord'][$col]) {
						$this->historyRecords[$table . ':' . $id]['newRecord'][$col] = $this->mmHistoryRecords[$table . ':' . $id]['newRecord'][$col];
					}
				}
			}
		} else {
			// If the current record does not exist this is an error anyways and we just return an empty array here.
			$fieldArray = array();
		}
		return $fieldArray;
	}

	/**
	 * Determines whether submitted values and stored values are equal.
	 * This prevents from adding superfluous field changes which would be shown in the record history as well.
	 * For NULL fields (see accordant TCA definition 'eval' = 'null'), a special handling is required since
	 * (!strcmp(NULL, '')) would be a false-positive.
	 *
	 * @param mixed $submittedValue Value that has submitted (e.g. from a backend form)
	 * @param mixed $storedValue Value that is currently stored in the database
	 * @param string $storedType SQL type of the stored value column (see mysql_field_type(), e.g 'int', 'string',  ...)
	 * @param boolean $allowNull Whether NULL values are allowed by accordant TCA definition ('eval' = 'null')
	 * @return boolean Whether both values are considered to be equal
	 */
	protected function isSubmittedValueEqualToStoredValue($submittedValue, $storedValue, $storedType, $allowNull = FALSE) {
		// No NULL values are allowed, this is the regular behaviour.
		// Thus, check whether strings are the same or whether integer values are empty ("0" or "").
		if (!$allowNull) {
			$result = (
				!strcmp($submittedValue, $storedValue)
				|| $storedType == 'int' && $storedValue == 0 && !strcmp($submittedValue, '')
			);
		// Null values are allowed, but currently there's a real (not NULL) value.
		// Thus, ensure no NULL value was submitted and fallback to the regular behaviour.
		} elseif ($storedValue !== NULL) {
			$result = (
				$submittedValue !== NULL
				&& $this->isSubmittedValueEqualToStoredValue($submittedValue, $storedValue, $storedType, FALSE)
			);
		// Null values are allowed, and currently there's a NULL value.
		// Thus, check whether a NULL value was submitted.
		} else {
			$result = ($submittedValue === NULL);
		}

		return $result;
	}

	/**
	 * Calculates the bitvalue of the permissions given in a string, comma-sep
	 *
	 * @param string $string List of pMap strings
	 * @return integer Integer mask
	 * @see setTSconfigPermissions(), newFieldArray()
	 * @todo Define visibility
	 */
	public function assemblePermissions($string) {
		$keyArr = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $string, 1);
		$value = 0;
		foreach ($keyArr as $key) {
			if ($key && isset($this->pMap[$key])) {
				$value |= $this->pMap[$key];
			}
		}
		return $value;
	}

	/**
	 * Returns the $input string without a comma in the end
	 *
	 * @param string $input Input string
	 * @return string Output string with any comma in the end removed, if any.
	 * @todo Define visibility
	 */
	public function rmComma($input) {
		return rtrim($input, ',');
	}

	/**
	 * Converts a HTML entity (like &#123;) to the character '123'
	 *
	 * @param string $input Input string
	 * @return string Output string
	 * @todo Define visibility
	 */
	public function convNumEntityToByteValue($input) {
		$token = md5(microtime());
		$parts = explode($token, preg_replace('/(&#([0-9]+);)/', $token . '\\2' . $token, $input));
		foreach ($parts as $k => $v) {
			if ($k % 2) {
				$v = intval($v);
				// Just to make sure that control bytes are not converted.
				if ($v > 32) {
					$parts[$k] = chr(intval($v));
				}
			}
		}
		return implode('', $parts);
	}

	/**
	 * Returns absolute destination path for the uploadfolder, $folder
	 *
	 * @param string $folder Upload folder name, relative to PATH_site
	 * @return string Input string prefixed with PATH_site
	 * @todo Define visibility
	 */
	public function destPathFromUploadFolder($folder) {
		return PATH_site . $folder;
	}

	/**
	 * Disables the delete clause for fetching records.
	 * In general only undeleted records will be used. If the delete
	 * clause is disabled, also deleted records are taken into account.
	 *
	 * @return void
	 */
	public function disableDeleteClause() {
		$this->disableDeleteClause = TRUE;
	}

	/**
	 * Returns delete-clause for the $table
	 *
	 * @param string $table Table name
	 * @return string Delete clause
	 * @todo Define visibility
	 */
	public function deleteClause($table) {
		// Returns the proper delete-clause if any for a table from TCA
		if (!$this->disableDeleteClause && $GLOBALS['TCA'][$table]['ctrl']['delete']) {
			return ' AND ' . $table . '.' . $GLOBALS['TCA'][$table]['ctrl']['delete'] . '=0';
		} else {
			return '';
		}
	}

	/**
	 * Return TSconfig for a page id
	 *
	 * @param integer $tscPID Page id (PID) from which to get configuration.
	 * @return array TSconfig array, if any
	 * @todo Define visibility
	 */
	public function getTCEMAIN_TSconfig($tscPID) {
		if (!isset($this->cachedTSconfig[$tscPID])) {
			$this->cachedTSconfig[$tscPID] = $this->BE_USER->getTSConfig('TCEMAIN', \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($tscPID));
		}
		return $this->cachedTSconfig[$tscPID]['properties'];
	}

	/**
	 * Extract entries from TSconfig for a specific table. This will merge specific and default configuration together.
	 *
	 * @param string $table Table name
	 * @param array $TSconfig TSconfig for page
	 * @return array TSconfig merged
	 * @see getTCEMAIN_TSconfig()
	 * @todo Define visibility
	 */
	public function getTableEntries($table, $TSconfig) {
		$tA = is_array($TSconfig['table.'][$table . '.']) ? $TSconfig['table.'][$table . '.'] : array();
		$dA = is_array($TSconfig['default.']) ? $TSconfig['default.'] : array();
		return \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($dA, $tA);
	}

	/**
	 * Returns the pid of a record from $table with $uid
	 *
	 * @param string $table Table name
	 * @param integer $uid Record uid
	 * @return integer PID value (unless the record did not exist in which case FALSE)
	 * @todo Define visibility
	 */
	public function getPID($table, $uid) {
		$res_tmp = $GLOBALS['TYPO3_DB']->exec_SELECTquery('pid', $table, 'uid=' . intval($uid));
		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_tmp)) {
			return $row['pid'];
		}
	}

	/**
	 * Executing dbAnalysisStore
	 * This will save MM relations for new records but is executed after records are created because we need to know the ID of them
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function dbAnalysisStoreExec() {
		foreach ($this->dbAnalysisStore as $action) {
			$id = \TYPO3\CMS\Backend\Utility\BackendUtility::wsMapId($action[4], \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($action[2]) ? $action[2] : $this->substNEWwithIDs[$action[2]]);
			if ($id) {
				$action[0]->writeMM($action[1], $id, $action[3]);
			}
		}
	}

	/**
	 * Removing files registered for removal before exit
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function removeRegisteredFiles() {
		foreach ($this->removeFilesStore as $file) {
			unlink($file);
		}
	}

	/**
	 * Unlink (delete) core cache files
	 *
	 * @return integer The number of files deleted
	 * @deprecated since 6.0, will be removed in two versions, use \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::removeCacheFiles() instead
	 * @todo Define visibility
	 */
	public function removeCacheFiles() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		return \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::removeCacheFiles();
	}

	/**
	 * Returns array, $CPtable, of pages under the $pid going down to $counter levels.
	 * Selecting ONLY pages which the user has read-access to!
	 *
	 * @param array $CPtable Accumulation of page uid=>pid pairs in branch of $pid
	 * @param integer $pid Page ID for which to find subpages
	 * @param integer $counter Number of levels to go down.
	 * @param integer $rootID ID of root point for new copied branch: The idea seems to be that a copy is not made of the already new page!
	 * @return array Return array.
	 * @todo Define visibility
	 */
	public function int_pageTreeInfo($CPtable, $pid, $counter, $rootID) {
		if ($counter) {
			$addW = !$this->admin ? ' AND ' . $this->BE_USER->getPagePermsClause($this->pMap['show']) : '';
			$mres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'pages', 'pid=' . intval($pid) . $this->deleteClause('pages') . $addW, '', 'sorting DESC');
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($mres)) {
				if ($row['uid'] != $rootID) {
					$CPtable[$row['uid']] = $pid;
					// If the uid is NOT the rootID of the copyaction and if we are supposed to walk further down
					if ($counter - 1) {
						$CPtable = $this->int_pageTreeInfo($CPtable, $row['uid'], $counter - 1, $rootID);
					}
				}
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($mres);
		}
		return $CPtable;
	}

	/**
	 * List of all tables (those administrators has access to = array_keys of $GLOBALS['TCA'])
	 *
	 * @return array Array of all TCA table names
	 * @todo Define visibility
	 */
	public function compileAdminTables() {
		return array_keys($GLOBALS['TCA']);
	}

	/**
	 * Checks if any uniqueInPid eval input fields are in the record and if so, they are re-written to be correct.
	 *
	 * @param string $table Table name
	 * @param integer $uid Record UID
	 * @return void
	 * @todo Define visibility
	 */
	public function fixUniqueInPid($table, $uid) {
		if ($GLOBALS['TCA'][$table]) {
			$curData = $this->recordInfo($table, $uid, '*');
			$newData = array();
			foreach ($GLOBALS['TCA'][$table]['columns'] as $field => $conf) {
				if ($conf['config']['type'] == 'input') {
					$evalCodesArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $conf['config']['eval'], 1);
					if (in_array('uniqueInPid', $evalCodesArray)) {
						$newV = $this->getUnique($table, $field, $curData[$field], $uid, $curData['pid']);
						if (strcmp($newV, $curData[$field])) {
							$newData[$field] = $newV;
						}
					}
				}
			}
			// IF there are changed fields, then update the database
			if (count($newData)) {
				$this->updateDB($table, $uid, $newData);
			}
		}
	}

	/**
	 * When er record is copied you can specify fields from the previous record which should be copied into the new one
	 * This function is also called with new elements. But then $update must be set to zero and $newData containing the data array. In that case data in the incoming array is NOT overridden. (250202)
	 *
	 * @param string $table Table name
	 * @param integer $uid Record UID
	 * @param integer $prevUid UID of previous record
	 * @param boolean $update If set, updates the record
	 * @param array $newData Input array. If fields are already specified AND $update is not set, values are not set in output array.
	 * @return array Output array (For when the copying operation needs to get the information instead of updating the info)
	 * @todo Define visibility
	 */
	public function fixCopyAfterDuplFields($table, $uid, $prevUid, $update, $newData = array()) {
		if ($GLOBALS['TCA'][$table] && $GLOBALS['TCA'][$table]['ctrl']['copyAfterDuplFields']) {
			$prevData = $this->recordInfo($table, $prevUid, '*');
			$theFields = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$table]['ctrl']['copyAfterDuplFields'], 1);
			foreach ($theFields as $field) {
				if ($GLOBALS['TCA'][$table]['columns'][$field] && ($update || !isset($newData[$field]))) {
					$newData[$field] = $prevData[$field];
				}
			}
			if ($update && count($newData)) {
				$this->updateDB($table, $uid, $newData);
			}
		}
		return $newData;
	}

	/**
	 * Returns all fieldnames from a table which are a list of files
	 *
	 * @param string $table Table name
	 * @return array Array of fieldnames that are either "group" or "file" types.
	 * @todo Define visibility
	 */
	public function extFileFields($table) {
		$listArr = array();
		if (isset($GLOBALS['TCA'][$table]['columns'])) {
			foreach ($GLOBALS['TCA'][$table]['columns'] as $field => $configArr) {
				if ($configArr['config']['type'] == 'group' && ($configArr['config']['internal_type'] == 'file' || $configArr['config']['internal_type'] == 'file_reference')) {
					$listArr[] = $field;
				}
			}
		}
		return $listArr;
	}

	/**
	 * Returns all fieldnames from a table which have the unique evaluation type set.
	 *
	 * @param string $table Table name
	 * @return array Array of fieldnames
	 * @deprecated since 6.1, will be removed in two versions, use \TYPO3\CMS\Version\Hook\DataHandlerHook::getUniqueFields() instead
	 */
	public function getUniqueFields($table) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		$listArr = array();
		if ($GLOBALS['TCA'][$table]['columns']) {
			foreach ($GLOBALS['TCA'][$table]['columns'] as $field => $configArr) {
				if ($configArr['config']['type'] === 'input') {
					$evalCodesArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $configArr['config']['eval'], 1);
					if (in_array('uniqueInPid', $evalCodesArray) || in_array('unique', $evalCodesArray)) {
						$listArr[] = $field;
					}
				}
			}
		}
		return $listArr;
	}

	/**
	 * Returns TRUE if the TCA/columns field type is a DB reference field
	 *
	 * @param array $conf Config array for TCA/columns field
	 * @return boolean TRUE if DB reference field (group/db or select with foreign-table)
	 * @todo Define visibility
	 */
	public function isReferenceField($conf) {
		return $conf['type'] == 'group' && $conf['internal_type'] == 'db' || $conf['type'] == 'select' && $conf['foreign_table'];
	}

	/**
	 * Returns the subtype as a string of an inline field.
	 * If it's not a inline field at all, it returns FALSE.
	 *
	 * @param array $conf Config array for TCA/columns field
	 * @return mixed string Inline subtype (field|mm|list), boolean: FALSE
	 * @todo Define visibility
	 */
	public function getInlineFieldType($conf) {
		if ($conf['type'] !== 'inline' || !$conf['foreign_table']) {
			return FALSE;
		}
		if ($conf['foreign_field']) {
			// The reference to the parent is stored in a pointer field in the child record
			return 'field';
		} elseif ($conf['MM']) {
			// Regular MM intermediate table is used to store data
			return 'mm';
		} else {
			// An item list (separated by comma) is stored (like select type is doing)
			return 'list';
		}
	}

	/**
	 * Get modified header for a copied record
	 *
	 * @param string $table Table name
	 * @param integer $pid PID value in which other records to test might be
	 * @param string $field Field name to get header value for.
	 * @param string $value Current field value
	 * @param integer $count Counter (number of recursions)
	 * @param string $prevTitle Previous title we checked for (in previous recursion)
	 * @return string The field value, possibly appended with a "copy label
	 * @todo Define visibility
	 */
	public function getCopyHeader($table, $pid, $field, $value, $count, $prevTitle = '') {
		// Set title value to check for:
		if ($count) {
			$checkTitle = $value . rtrim((' ' . sprintf($this->prependLabel($table), $count)));
		} else {
			$checkTitle = $value;
		}
		// Do check:
		if ($prevTitle != $checkTitle || $count < 100) {
			$rowCount = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('uid', $table, 'pid=' . intval($pid) . ' AND ' . $field . '=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($checkTitle, $table) . $this->deleteClause($table));
			if ($rowCount) {
				return $this->getCopyHeader($table, $pid, $field, $value, $count + 1, $checkTitle);
			}
		}
		// Default is to just return the current input title if no other was returned before:
		return $checkTitle;
	}

	/**
	 * Return "copy" label for a table. Although the name is "prepend" it actually APPENDs the label (after ...)
	 *
	 * @param string $table Table name
	 * @return string Label to append, containing "%s" for the number
	 * @see getCopyHeader()
	 * @todo Define visibility
	 */
	public function prependLabel($table) {
		if (is_object($GLOBALS['LANG'])) {
			$label = $GLOBALS['LANG']->sL($GLOBALS['TCA'][$table]['ctrl']['prependAtCopy']);
		} else {
			list($label) = explode('|', $GLOBALS['TCA'][$table]['ctrl']['prependAtCopy']);
		}
		return $label;
	}

	/**
	 * Get the final pid based on $table and $pid ($destPid type... pos/neg)
	 *
	 * @param string $table Table name
	 * @param integer $pid "Destination pid" : If the value is >= 0 it's just returned directly (through intval() though) but if the value is <0 then the method looks up the record with the uid equal to abs($pid) (positive number) and returns the PID of that record! The idea is that negative numbers point to the record AFTER WHICH the position is supposed to be!
	 * @return integer
	 * @todo Define visibility
	 */
	public function resolvePid($table, $pid) {
		$pid = intval($pid);
		if ($pid < 0) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('pid', $table, 'uid=' . abs($pid));
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
			// Look, if the record UID happens to be an offline record. If so, find its live version.
			// Offline uids will be used when a page is versionized as "branch" so this is when we
			// must correct - otherwise a pid of "-1" and a wrong sort-row number
			// is returned which we don't want.
			if ($lookForLiveVersion = \TYPO3\CMS\Backend\Utility\BackendUtility::getLiveVersionOfRecord($table, abs($pid), 'pid')) {
				$row = $lookForLiveVersion;
			}
			$pid = intval($row['pid']);
		}
		return $pid;
	}

	/**
	 * Removes the prependAtCopy prefix on values
	 *
	 * @param string $table Table name
	 * @param string $value The value to fix
	 * @return string Clean name
	 * @todo Define visibility
	 */
	public function clearPrefixFromValue($table, $value) {
		$regex = '/' . sprintf(quotemeta($this->prependLabel($table)), '[0-9]*') . '$/';
		return @preg_replace($regex, '', $value);
	}

	/**
	 * File functions on external file references. eg. deleting files when deleting record
	 *
	 * @param string $table Table name
	 * @param string $field Field name
	 * @param string $filelist List of files to work on from field
	 * @param string $func Function, eg. "deleteAll" which will delete all files listed.
	 * @return void
	 * @todo Define visibility
	 */
	public function extFileFunctions($table, $field, $filelist, $func) {
		$uploadFolder = $GLOBALS['TCA'][$table]['columns'][$field]['config']['uploadfolder'];
		if ($uploadFolder && trim($filelist) && $GLOBALS['TCA'][$table]['columns'][$field]['config']['internal_type'] == 'file') {
			$uploadPath = $this->destPathFromUploadFolder($uploadFolder);
			$fileArray = explode(',', $filelist);
			foreach ($fileArray as $theFile) {
				$theFile = trim($theFile);
				if ($theFile) {
					switch ($func) {
					case 'deleteAll':
						if (@is_file(($uploadPath . '/' . $theFile))) {
							unlink($uploadPath . '/' . $theFile);
						} else {
							$this->log($table, 0, 3, 0, 100, 'Delete: Referenced file that was supposed to be deleted together with it\'s record didn\'t exist');
						}
						break;
					}
				}
			}
		}
	}

	/**
	 * Used by the deleteFunctions to check if there are records from disallowed tables under the pages to be deleted.
	 *
	 * @param string $inList List of page integers
	 * @return boolean Return TRUE, if permission granted
	 * @todo Define visibility
	 */
	public function noRecordsFromUnallowedTables($inList) {
		$inList = trim($this->rmComma(trim($inList)));
		if ($inList && !$this->admin) {
			foreach (array_keys($GLOBALS['TCA']) as $table) {
				$count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('uid', $table, 'pid IN (' . $inList . ')' . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table));
				if ($count && ($this->tableReadOnly($table) || !$this->checkModifyAccessList($table))) {
					return FALSE;
				}
			}
		}
		return TRUE;
	}

	/**
	 * Determine if a record was copied or if a record is the result of a copy action.
	 *
	 * @param string $table The tablename of the record
	 * @param integer $uid The uid of the record
	 * @return boolean Returns TRUE if the record is copied or is the result of a copy action
	 * @todo Define visibility
	 */
	public function isRecordCopied($table, $uid) {
		// If the record was copied:
		if (isset($this->copyMappingArray[$table][$uid])) {
			return TRUE;
		} elseif (isset($this->copyMappingArray[$table]) && in_array($uid, array_values($this->copyMappingArray[$table]))) {
			return TRUE;
		}
		return FALSE;
	}

	/******************************
	 *
	 * Clearing cache
	 *
	 ******************************/
	/**
	 * Clearing the cache based on a page being updated
	 * If the $table is 'pages' then cache is cleared for all pages on the same level (and subsequent?)
	 * Else just clear the cache for the parent page of the record.
	 *
	 * @param string $table Table name of record that was just updated.
	 * @param integer $uid UID of updated / inserted record
	 * @return void
	 * @todo Define visibility
	 */
	public function clear_cache($table, $uid) {
		$uid = intval($uid);
		$pageUid = 0;
		if (is_array($GLOBALS['TCA'][$table]) && $uid > 0) {
			// Get Page TSconfig relavant:
			list($tscPID) = \TYPO3\CMS\Backend\Utility\BackendUtility::getTSCpid($table, $uid, '');
			$TSConfig = $this->getTCEMAIN_TSconfig($tscPID);
			if (!$TSConfig['clearCache_disable']) {
				// If table is "pages":
				if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('cms')) {
					$list_cache = array();
					if ($table === 'pages' || $table === 'pages_language_overlay') {
						if ($table === 'pages_language_overlay') {
							$pageUid = $this->getPID($table, $uid);
						} else {
							$pageUid = $uid;
						}
						// Builds list of pages on the SAME level as this page (siblings)
						$res_tmp = $GLOBALS['TYPO3_DB']->exec_SELECTquery('A.pid AS pid, B.uid AS uid', 'pages A, pages B', 'A.uid=' . intval($pageUid) . ' AND B.pid=A.pid AND B.deleted=0');
						$pid_tmp = 0;
						while ($row_tmp = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_tmp)) {
							$list_cache[] = $row_tmp['uid'];
							$pid_tmp = $row_tmp['pid'];
							// Add children as well:
							if ($TSConfig['clearCache_pageSiblingChildren']) {
								$res_tmp2 = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'pages', 'pid=' . intval($row_tmp['uid']) . ' AND deleted=0');
								while ($row_tmp2 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_tmp2)) {
									$list_cache[] = $row_tmp2['uid'];
								}
								$GLOBALS['TYPO3_DB']->sql_free_result($res_tmp2);
							}
						}
						$GLOBALS['TYPO3_DB']->sql_free_result($res_tmp);
						// Finally, add the parent page as well:
						$list_cache[] = $pid_tmp;
						// Add grand-parent as well:
						if ($TSConfig['clearCache_pageGrandParent']) {
							$res_tmp = $GLOBALS['TYPO3_DB']->exec_SELECTquery('pid', 'pages', 'uid=' . intval($pid_tmp));
							if ($row_tmp = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_tmp)) {
								$list_cache[] = $row_tmp['pid'];
							}
							$GLOBALS['TYPO3_DB']->sql_free_result($res_tmp);
						}
					} else {
						// For other tables than "pages", delete cache for the records "parent page".
						$list_cache[] = ($pageUid = intval($this->getPID($table, $uid)));
					}
					// Call pre-processing function for clearing of cache for page ids:
					if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearPageCacheEval'])) {
						foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearPageCacheEval'] as $funcName) {
							$_params = array('pageIdArray' => &$list_cache, 'table' => $table, 'uid' => $uid, 'functionID' => 'clear_cache()');
							// Returns the array of ids to clear, FALSE if nothing should be cleared! Never an empty array!
							\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcName, $_params, $this);
						}
					}
					// Delete cache for selected pages:
					if (is_array($list_cache)) {
						$pageIds = $GLOBALS['TYPO3_DB']->cleanIntArray($list_cache);
						foreach ($pageIds as $pageId) {
							$GLOBALS['typo3CacheManager']->flushCachesByTag('pageId_' . $pageId);
						}
					}
					// Delete cache for current table and record
					$GLOBALS['typo3CacheManager']->flushCachesByTag($table);
					$GLOBALS['typo3CacheManager']->flushCachesByTag($table . '_' . $uid);
				}
			}
			// Clear cache for pages entered in TSconfig:
			if ($TSConfig['clearCacheCmd']) {
				$Commands = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $TSConfig['clearCacheCmd'], 1);
				$Commands = array_unique($Commands);
				foreach ($Commands as $cmdPart) {
					$this->clear_cacheCmd($cmdPart);
				}
			}
			// Call post processing function for clear-cache:
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'])) {
				$_params = array('table' => $table, 'uid' => $uid, 'uid_page' => $pageUid, 'TSConfig' => $TSConfig);
				foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'] as $_funcRef) {
					\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $_params, $this);
				}
			}
		}
	}

	/**
	 * Clears the cache based on the command $cacheCmd.
	 *
	 * $cacheCmd='pages':	Clears cache for all pages. Requires admin-flag to
	 * be set for BE_USER.
	 *
	 * $cacheCmd='all':		Clears all cache_tables. This is necessary if
	 * templates are updated. Requires admin-flag to be set for BE_USER.
	 *
	 * $cacheCmd=[integer]:	Clears cache for the page pointed to by $cacheCmd
	 * (an integer).
	 *
	 * $cacheCmd='cacheTag:[string]':  Flush page and pagesection cache by given tag
	 *
	 * $cacheCmd='cacheId:[string]':  Removes cache identifier from page and page section cache
	 *
	 * Can call a list of post processing functions as defined in
	 * $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc']
	 * (numeric array with values being the function references, called by
	 * \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction()).
	 *
	 * Note: The following cache_* are intentionally not cleared by
	 * $cacheCmd='all':
	 *
	 * - cache_md5params:	RDCT redirects.
	 * - cache_imagesizes:	Clearing this table would cause a lot of unneeded
	 * Imagemagick calls because the size informations have
	 * to be fetched again after clearing.
	 *
	 * @param string $cacheCmd The cache command, see above description
	 * @return void
	 */
	public function clear_cacheCmd($cacheCmd) {
		if (is_object($this->BE_USER)) {
			$this->BE_USER->writelog(3, 1, 0, 0, 'User %s has cleared the cache (cacheCmd=%s)', array($this->BE_USER->user['username'], $cacheCmd));
		}
		// Clear cache for either ALL pages or ALL tables!
		switch (strtolower($cacheCmd)) {
		case 'pages':
			if ($this->admin || $this->BE_USER->getTSConfigVal('options.clearCache.pages')) {
				$this->internal_clearPageCache();
			}
			break;
		case 'all':
			if ($this->admin || $this->BE_USER->getTSConfigVal('options.clearCache.all')) {
				// Clear all caching framework caches
				$GLOBALS['typo3CacheManager']->flushCaches();
				if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('cms')) {
					$GLOBALS['TYPO3_DB']->exec_TRUNCATEquery('cache_treelist');
				}
				// Clearing additional cache tables:
				if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearAllCache_additionalTables'])) {
					foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearAllCache_additionalTables'] as $tableName) {
						if (!preg_match('/[^[:alnum:]_]/', $tableName) && substr($tableName, -5) === 'cache') {
							$GLOBALS['TYPO3_DB']->exec_TRUNCATEquery($tableName);
						} else {
							throw new \RuntimeException('TYPO3 Fatal Error: Trying to flush table "' . $tableName . '" with "Clear All Cache"', 1270853922);
						}
					}
				}
			}
			\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::removeCacheFiles();
			break;
		case 'temp_cached':
			\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::removeCacheFiles();
			break;
		}
		$tagsToFlush = array();
		// Clear cache for a page ID!
		if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($cacheCmd)) {
			if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('cms')) {
				$list_cache = array($cacheCmd);
				// Call pre-processing function for clearing of cache for page ids:
				if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearPageCacheEval'])) {
					foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearPageCacheEval'] as $funcName) {
						$_params = array('pageIdArray' => &$list_cache, 'cacheCmd' => $cacheCmd, 'functionID' => 'clear_cacheCmd()');
						// Returns the array of ids to clear, FALSE if nothing should be cleared! Never an empty array!
						\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcName, $_params, $this);
					}
				}
				// Delete cache for selected pages:
				if (is_array($list_cache)) {
					foreach ($list_cache as $pageId) {
						$tagsToFlush[] = 'pageId_' . (int) $pageId;
					}
				}
			}
		}
		// flush cache by tag
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr(strtolower($cacheCmd), 'cachetag:')) {
			$cacheTag = substr($cacheCmd, 9);
			$tagsToFlush[] = $cacheTag;
		}
		// process caching framwork operations
		if (count($tagsToFlush) > 0) {
			/** @var $pageCache \TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend */
			$pageCache = $GLOBALS['typo3CacheManager']->getCache('cache_pages');
			/** @var $pageSectionCache \TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend */
			$pageSectionCache = $GLOBALS['typo3CacheManager']->getCache('cache_pagesection');
			foreach ($tagsToFlush as $tag) {
				$pageCache->flushByTag($tag);
				$pageSectionCache->flushByTag($tag);
			}
		}
		// Call post processing function for clear-cache:
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'])) {
			$_params = array('cacheCmd' => strtolower($cacheCmd));
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'] as $_funcRef) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $_params, $this);
			}
		}
	}

	/*****************************
	 *
	 * Logging
	 *
	 *****************************/
	/**
	 * Logging actions from TCEmain
	 *
	 * @param string $table Table name the log entry is concerned with. Blank if NA
	 * @param integer $recuid Record UID. Zero if NA
	 * @param integer $action Action number: 0=No category, 1=new record, 2=update record, 3= delete record, 4= move record, 5= Check/evaluate
	 * @param integer $recpid Normally 0 (zero). If set, it indicates that this log-entry is used to notify the backend of a record which is moved to another location
	 * @param integer $error The severity: 0 = message, 1 = error, 2 = System Error, 3 = security notice (admin)
	 * @param string $details Default error message in english
	 * @param integer $details_nr This number is unique for every combination of $type and $action. This is the error-message number, which can later be used to translate error messages. 0 if not categorized, -1 if temporary
	 * @param array $data Array with special information that may go into $details by '%s' marks / sprintf() when the log is shown
	 * @param integer $event_pid The page_uid (pid) where the event occurred. Used to select log-content for specific pages.
	 * @param string $NEWid NEW id for new records
	 * @return integer Log entry UID
	 * @todo Define visibility
	 */
	public function log($table, $recuid, $action, $recpid, $error, $details, $details_nr = -1, $data = array(), $event_pid = -1, $NEWid = '') {
		if ($this->enableLogging) {
			// Type value for tce_db.php
			$type = 1;
			if (!$this->storeLogMessages) {
				$details = '';
			}
			if ($error > 0) {
				$detailMessage = $details;
				if (is_array($data)) {
					$detailMessage = vsprintf($details, $data);
				}
				$this->errorLog[] = '[' . $type . '.' . $action . '.' . $details_nr . ']: ' . $detailMessage;
			}
			return $this->BE_USER->writelog($type, $action, $error, $details_nr, $details, $data, $table, $recuid, $recpid, $event_pid, $NEWid);
		}
	}

	/**
	 * Simple logging function meant to be used when logging messages is not yet fixed.
	 *
	 * @param string $message Message string
	 * @param integer $error Error code, see log()
	 * @return integer Log entry UID
	 * @see log()
	 * @todo Define visibility
	 */
	public function newlog($message, $error = 0) {
		return $this->log('', 0, 0, 0, $error, '[newlog()] ' . $message, -1);
	}

	/**
	 * Simple logging function meant to bridge the gap between newlog() and log() with a littme more info, in particular the record table/uid and event_pid so we can filter messages pr page.
	 *
	 * @param string $message Message string
	 * @param string $table Table name
	 * @param integer $uid Record uid
	 * @param integer $pid Record PID (from page tree). Will be turned into an event_pid internally in function: Meaning that the PID for a page will be its own UID, not its page tree PID.
	 * @param integer $error Error code, see log()
	 * @return integer Log entry UID
	 * @see log()
	 * @todo Define visibility
	 */
	public function newlog2($message, $table, $uid, $pid = FALSE, $error = 0) {
		if ($pid === FALSE) {
			$propArr = $this->getRecordProperties($table, $uid);
			$pid = $propArr['pid'];
		}
		return $this->log($table, $uid, 0, 0, $error, $message, -1, array(), $this->eventPid($table, $uid, $pid));
	}

	/**
	 * Print log error messages from the operations of this script instance
	 *
	 * @param string $redirect Redirect URL (for creating link in message)
	 * @return void (Will exit on error)
	 * @todo Define visibility
	 */
	public function printLogErrorMessages($redirect) {
		$res_log = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_log', 'type=1 AND userid=' . intval($this->BE_USER->user['uid']) . ' AND tstamp=' . intval($GLOBALS['EXEC_TIME']) . '	AND error<>0');
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_log)) {
			$log_data = unserialize($row['log_data']);
			$msg = $row['error'] . ': ' . sprintf($row['details'], $log_data[0], $log_data[1], $log_data[2], $log_data[3], $log_data[4]);
			$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', htmlspecialchars($msg), '', \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR, TRUE);
			/** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
			$flashMessageService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessageService');
			/** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
			$defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
			$defaultFlashMessageQueue->enqueue($flashMessage);
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res_log);
	}

	/*****************************
	 *
	 * Internal (do not use outside Core!)
	 *
	 *****************************/
	/**
	 * Clears page cache. Takes into account file cache.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function internal_clearPageCache() {
		if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('cms')) {
			$GLOBALS['typo3CacheManager']->getCache('cache_pages')->flush();
		}
	}

	/**
	 * Proprocesses field array based on field type. Some fields must be adjusted
	 * before going to database. This is done on the copy of the field array because
	 * original values are used in remap action later.
	 *
	 * @param stringb$table	Table name
	 * @param array $fieldArray	Field array to check
	 * @return array Updated field array
	 * @todo Define visibility
	 */
	public function insertUpdateDB_preprocessBasedOnFieldType($table, $fieldArray) {
		$result = $fieldArray;
		foreach ($fieldArray as $field => $value) {
			switch ($GLOBALS['TCA'][$table]['columns'][$field]['config']['type']) {
			case 'inline':
				if ($GLOBALS['TCA'][$table]['columns'][$field]['config']['foreign_field']) {
					if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($value)) {
						$result[$field] = count(\TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $value, TRUE));
					}
				}
				break;
			}
		}
		return $result;
	}

	/**
	 * Gets the automatically versionized id of a record.
	 *
	 * @param string $table Name of the table
	 * @param integer $id Uid of the record
	 * @return integer
	 */
	public function getAutoVersionId($table, $id) {
		$result = NULL;
		if (isset($this->autoVersionIdMap[$table][$id])) {
			$result = $this->autoVersionIdMap[$table][$id];
		}
		return $result;
	}

	/**
	 * Overlays the automatically versionized id of a record.
	 *
	 * @param string $table Name of the table
	 * @param integer $id Uid of the record
	 * @return integer
	 */
	protected function overlayAutoVersionId($table, $id) {
		$autoVersionId = $this->getAutoVersionId($table, $id);
		if (is_null($autoVersionId) === FALSE) {
			$id = $autoVersionId;
		}
		return $id;
	}

	/**
	 * Adds new values to the remapStackChildIds array.
	 *
	 * @param array $idValues uid values
	 * @return void
	 */
	protected function addNewValuesToRemapStackChildIds(array $idValues) {
		foreach ($idValues as $idValue) {
			if (strpos($idValue, 'NEW') === 0) {
				$this->remapStackChildIds[$idValue] = TRUE;
			}
		}
	}

	/**
	 * Gets the outer most instance of \TYPO3\CMS\Core\DataHandling\DataHandler
	 * Since \TYPO3\CMS\Core\DataHandling\DataHandler can create nested objects of itself,
	 * this method helps to determine the first (= outer most) one.
	 *
	 * @return \TYPO3\CMS\Core\DataHandling\DataHandler
	 */
	protected function getOuterMostInstance() {
		if (!isset($this->outerMostInstance)) {
			$stack = array_reverse(debug_backtrace());
			foreach ($stack as $stackItem) {
				if (isset($stackItem['object']) && $stackItem['object'] instanceof \TYPO3\CMS\Core\DataHandling\DataHandler) {
					$this->outerMostInstance = $stackItem['object'];
					break;
				}
			}
		}
		return $this->outerMostInstance;
	}

	/**
	 * Determines whether the this object is the outer most instance of itself
	 * Since DataHandler can create nested objects of itself,
	 * this method helps to determine the first (= outer most) one.
	 *
	 * @return boolean
	 */
	public function isOuterMostInstance() {
		return $this->getOuterMostInstance() === $this;
	}

	/**
	 * Gets an instance of the memory cache.
	 *
	 * @return \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend
	 */
	protected function getMemoryCache() {
		return $GLOBALS['typo3CacheManager']->getCache('cache_runtime');
	}

	/**
	 * Determines nested element calls.
	 *
	 * @param string $table Name of the table
	 * @param integer $id Uid of the record
	 * @param string $identifier Name of the action to be checked
	 * @return boolean
	 */
	protected function isNestedElementCallRegistered($table, $id, $identifier) {
		$nestedElementCalls = (array) $this->getMemoryCache()->get('nestedElementCalls');
		return isset($nestedElementCalls[$identifier][$table][$id]);
	}

	/**
	 * Registers nested elements calls.
	 * This is used to track nested calls (e.g. for following m:n relations).
	 *
	 * @param string $table Name of the table
	 * @param integer $id Uid of the record
	 * @param string $identifier Name of the action to be tracked
	 * @return void
	 */
	protected function registerNestedElementCall($table, $id, $identifier) {
		$nestedElementCalls = (array) $this->getMemoryCache()->get('nestedElementCalls');
		$nestedElementCalls[$identifier][$table][$id] = TRUE;
		$this->getMemoryCache()->set('nestedElementCalls', $nestedElementCalls);
	}

	/**
	 * Resets the nested element calls.
	 *
	 * @return void
	 */
	protected function resetNestedElementCalls() {
		$this->getMemoryCache()->remove('nestedElementCalls');
	}

	/**
	 * Determines whether an element was registered to be deleted in the registry.
	 *
	 * @param string $table Name of the table
	 * @param integer $id Uid of the record
	 * @return boolean
	 * @see registerElementsToBeDeleted
	 * @see resetElementsToBeDeleted
	 * @see copyRecord_raw
	 * @see versionizeRecord
	 */
	protected function isElementToBeDeleted($table, $id) {
		$elementsToBeDeleted = (array) $this->getMemoryCache()->get('core-t3lib_TCEmain-elementsToBeDeleted');
		return isset($elementsToBeDeleted[$table][$id]);
	}

	/**
	 * Registers elements to be deleted in the registry.
	 *
	 * @return void
	 * @see process_datamap
	 */
	protected function registerElementsToBeDeleted() {
		$elementsToBeDeleted = (array) $this->getMemoryCache()->get('core-t3lib_TCEmain-elementsToBeDeleted');
		$this->getMemoryCache()->set('core-t3lib_TCEmain-elementsToBeDeleted', array_merge($elementsToBeDeleted, $this->getCommandMapElements('delete')));
	}

	/**
	 * Resets the elements to be deleted in the registry.
	 *
	 * @return void
	 * @see process_datamap
	 */
	protected function resetElementsToBeDeleted() {
		$this->getMemoryCache()->remove('core-t3lib_TCEmain-elementsToBeDeleted');
	}

	/**
	 * Unsets elements (e.g. of the data map) that shall be deleted.
	 * This avoids to modify records that will be deleted later on.
	 *
	 * @param array $elements Elements to be modified
	 * @return array
	 */
	protected function unsetElementsToBeDeleted(array $elements) {
		$elements = \TYPO3\CMS\Core\Utility\GeneralUtility::arrayDiffAssocRecursive($elements, $this->getCommandMapElements('delete'));
		foreach ($elements as $key => $value) {
			if (empty($value)) {
				unset($elements[$key]);
			}
		}
		return $elements;
	}

	/**
	 * Gets elements of the command map that match a particular command.
	 *
	 * @param string $needle The command to be matched
	 * @return array
	 */
	protected function getCommandMapElements($needle) {
		$elements = array();
		foreach ($this->cmdmap as $tableName => $idArray) {
			foreach ($idArray as $id => $commandArray) {
				foreach ($commandArray as $command => $value) {
					if ($value && $command == $needle) {
						$elements[$tableName][$id] = TRUE;
					}
				}
			}
		}
		return $elements;
	}

	/**
	 * Controls active elements and sets NULL values if not active.
	 * Datamap is modified accordant to submitted control values.
	 *
	 * @return void
	 */
	protected function controlActiveElements() {
		if (!empty($this->control['active'])) {
			$this->setNullValues(
				$this->control['active'],
				$this->datamap
			);
		}
	}

	/**
	 * Sets NULL values in haystack array.
	 * The general behaviour in the user interface is to enable/activate fields.
	 * Thus, this method uses NULL as value to be stored if a field is not active.
	 *
	 * @param array $active Hierarchical array with active elements
	 * @param array $haystack Hierachical array with haystack to be modified
	 * @return void
	 */
	protected function setNullValues(array $active, array &$haystack) {
		foreach ($active as $key => $value) {
			// Nested data is processes recursively
			if (is_array($value)) {
				$this->setNullValues(
					$value,
					$haystack[$key]
				);
			// Field has not been activated in the user interface,
			// thus a NULL value shall be stored in the database
			} elseif ($value == 0) {
				$haystack[$key] = NULL;
			}
		}
	}

}


?>
