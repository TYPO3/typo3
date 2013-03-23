<?php
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Extension to class.t3lib_userauth.php; Authentication of users in TYPO3 Backend
 *
 * Actually this class is extended again by t3lib_beuserauth which is the actual backend user class that will be instantiated.
 * In fact the two classes t3lib_beuserauth and this class could just as well be one, single class since t3lib_userauthgroup is not - to my knowledge - used separately elsewhere. But for historical reasons they are two separate classes.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @deprecated since 6.0, will be removed in 6.2. The functionality was merged wih t3lib_beUserAuth
 */
abstract class t3lib_userAuthGroup extends t3lib_userAuth {

	// Should be set to the usergroup-column (id-list) in the user-record
	/**
	 * @todo Define visibility
	 */
	public $usergroup_column = 'usergroup';

	// The name of the group-table
	/**
	 * @todo Define visibility
	 */
	public $usergroup_table = 'be_groups';

	// Internal
	// This array holds lists of eg. tables, fields and other values related to the permission-system. See fetchGroupData
	/**
	 * @todo Define visibility
	 */
	public $groupData = array(
		'filemounts' => array()
	);

	// User workspace. -99 is ERROR (none available), -1 is offline, 0 is online, >0 is custom workspaces.
	/**
	 * @todo Define visibility
	 */
	public $workspace = -99;

	// Custom workspace record if any
	/**
	 * @todo Define visibility
	 */
	public $workspaceRec = array();

	// This array will hold the groups that the user is a member of
	/**
	 * @todo Define visibility
	 */
	public $userGroups = array();

	// This array holds the uid's of the groups in the listed order
	/**
	 * @todo Define visibility
	 */
	public $userGroupsUID = array();

	// This is $this->userGroupsUID imploded to a comma list... Will correspond to the 'usergroup_cached_list'
	/**
	 * @todo Define visibility
	 */
	public $groupList = '';

	// Used internally to accumulate data for the user-group. DONT USE THIS EXTERNALLY! Use $this->groupData instead
	/**
	 * @todo Define visibility
	 */
	public $dataLists = array(
		'webmount_list' => '',
		'filemount_list' => '',
		'fileoper_perms' => 0,
		'modList' => '',
		'tables_select' => '',
		'tables_modify' => '',
		'pagetypes_select' => '',
		'non_exclude_fields' => '',
		'explicit_allowdeny' => '',
		'allowed_languages' => '',
		'workspace_perms' => '',
		'custom_options' => ''
	);

	// For debugging/display of order in which subgroups are included.
	/**
	 * @todo Define visibility
	 */
	public $includeHierarchy = array();

	// List of group_id's in the order they are processed.
	/**
	 * @todo Define visibility
	 */
	public $includeGroupArray = array();

	// Set to 'WIN', if windows
	/**
	 * @todo Define visibility
	 */
	public $OS = '';

	// Used to accumulate the TSconfig data of the user
	/**
	 * @todo Define visibility
	 */
	public $TSdataArray = array();

	// Contains the non-parsed user TSconfig
	/**
	 * @todo Define visibility
	 */
	public $userTS_text = '';

	// Contains the parsed user TSconfig
	/**
	 * @todo Define visibility
	 */
	public $userTS = array();

	// Set internally if the user TSconfig was parsed and needs to be cached.
	/**
	 * @todo Define visibility
	 */
	public $userTSUpdated = 0;

	// Set this from outside if you want the user TSconfig to ALWAYS be parsed and not fetched from cache.
	/**
	 * @todo Define visibility
	 */
	public $userTS_dontGetCached = 0;

	// RTE availability errors collected.
	/**
	 * @todo Define visibility
	 */
	public $RTE_errors = array();

	// Contains last error message
	/**
	 * @todo Define visibility
	 */
	public $errorMsg = '';

	// Cache for checkWorkspaceCurrent()
	/**
	 * @todo Define visibility
	 */
	public $checkWorkspaceCurrent_cache = NULL;

	/**
	 * @var \TYPO3\CMS\Core\Resource\ResourceStorage[]
	 */
	protected $fileStorages;

	/**
	 * @var array
	 */
	protected $filePermissions;

	/**
	 * Constructor deprecates class.
	 */
	public function __construct() {
		t3lib_div::logDeprecatedFunction('Class t3lib_userAuthGroup is deprecated and unused since TYPO3 6.0. ' . 'All methods are merged with t3lib_beUserAuth.');
	}

	/************************************
	 *
	 * Permission checking functions:
	 *
	 ************************************/
	/**
	 * Returns TRUE if user is admin
	 * Basically this function evaluates if the ->user[admin] field has bit 0 set. If so, user is admin.
	 *
	 * @return boolean
	 * @todo Define visibility
	 */
	public function isAdmin() {
		return ($this->user['admin'] & 1) == 1;
	}

	/**
	 * Returns TRUE if the current user is a member of group $groupId
	 * $groupId must be set. $this->groupList must contain groups
	 * Will return TRUE also if the user is a member of a group through subgroups.
	 *
	 * @param integer $groupId Group ID to look for in $this->groupList
	 * @return boolean
	 * @todo Define visibility
	 */
	public function isMemberOfGroup($groupId) {
		$groupId = intval($groupId);
		if ($this->groupList && $groupId) {
			return t3lib_div::inList($this->groupList, $groupId);
		}
	}

	/**
	 * Checks if the permissions is granted based on a page-record ($row) and $perms (binary and'ed)
	 *
	 * Bits for permissions, see $perms variable:
	 *
	 * 1 - Show:	See/Copy page and the pagecontent.
	 * 16- Edit pagecontent: Change/Add/Delete/Move pagecontent.
	 * 2- Edit page: Change/Move the page, eg. change title, startdate, hidden.
	 * 4- Delete page: Delete the page and pagecontent.
	 * 8- New pages: Create new pages under the page.
	 *
	 * @param array $row Is the pagerow for which the permissions is checked
	 * @param integer $perms Is the binary representation of the permission we are going to check. Every bit in this number represents a permission that must be set. See function explanation.
	 * @return boolean TRUE or False upon evaluation
	 * @todo Define visibility
	 */
	public function doesUserHaveAccess($row, $perms) {
		$userPerms = $this->calcPerms($row);
		return ($userPerms & $perms) == $perms;
	}

	/**
	 * Checks if the page id, $id, is found within the webmounts set up for the user.
	 * This should ALWAYS be checked for any page id a user works with, whether it's about reading, writing or whatever.
	 * The point is that this will add the security that a user can NEVER touch parts outside his mounted pages in the page tree. This is otherwise possible if the raw page permissions allows for it. So this security check just makes it easier to make safe user configurations.
	 * If the user is admin OR if this feature is disabled (fx. by setting TYPO3_CONF_VARS['BE']['lockBeUserToDBmounts']=0) then it returns "1" right away
	 * Otherwise the function will return the uid of the webmount which was first found in the rootline of the input page $id
	 *
	 * @param integer $id Page ID to check
	 * @param string $readPerms Content of "->getPagePermsClause(1)" (read-permissions). If not set, they will be internally calculated (but if you have the correct value right away you can save that database lookup!)
	 * @param boolean $exitOnError If set, then the function will exit with an error message.
	 * @return integer The page UID of a page in the rootline that matched a mount point
	 * @todo Define visibility
	 */
	public function isInWebMount($id, $readPerms = '', $exitOnError = 0) {
		if (!$GLOBALS['TYPO3_CONF_VARS']['BE']['lockBeUserToDBmounts'] || $this->isAdmin()) {
			return 1;
		}
		$id = intval($id);
		// Check if input id is an offline version page in which case we will map id to the online version:
		$checkRec = t3lib_beFUnc::getRecord('pages', $id, 'pid,t3ver_oid');
		if ($checkRec['pid'] == -1) {
			$id = intval($checkRec['t3ver_oid']);
		}
		if (!$readPerms) {
			$readPerms = $this->getPagePermsClause(1);
		}
		if ($id > 0) {
			$wM = $this->returnWebmounts();
			$rL = t3lib_BEfunc::BEgetRootLine($id, ' AND ' . $readPerms);
			foreach ($rL as $v) {
				if ($v['uid'] && in_array($v['uid'], $wM)) {
					return $v['uid'];
				}
			}
		}
		if ($exitOnError) {
			throw new \RuntimeException('Access Error: This page is not within your DB-mounts', 1294586445);
		}
	}

	/**
	 * Checks access to a backend module with the $MCONF passed as first argument
	 *
	 * @param array $conf $MCONF array of a backend module!
	 * @param boolean $exitOnError If set, an array will issue an error message and exit.
	 * @return boolean Will return TRUE if $MCONF['access'] is not set at all, if the BE_USER is admin or if the module is enabled in the be_users/be_groups records of the user (specifically enabled). Will return FALSE if the module name is not even found in $TBE_MODULES
	 * @todo Define visibility
	 */
	public function modAccess($conf, $exitOnError) {
		if (!t3lib_BEfunc::isModuleSetInTBE_MODULES($conf['name'])) {
			if ($exitOnError) {
				throw new \RuntimeException('Fatal Error: This module "' . $conf['name'] . '" is not enabled in TBE_MODULES', 1294586446);
			}
			return FALSE;
		}
		// Workspaces check:
		if (t3lib_extMgm::isLoaded('workspaces') && $conf['workspaces']) {
			if ($this->workspace === 0 && t3lib_div::inList($conf['workspaces'], 'online') || $this->workspace === -1 && t3lib_div::inList($conf['workspaces'], 'offline') || $this->workspace > 0 && t3lib_div::inList($conf['workspaces'], 'custom')) {

			} else {
				if ($exitOnError) {
					throw new \RuntimeException('Workspace Error: This module "' . $conf['name'] . '" is not available under the current workspace', 1294586447);
				}
				return FALSE;
			}
		}
		// Returns TRUE if conf[access] is not set at all or if the user is admin
		if (!$conf['access'] || $this->isAdmin()) {
			return TRUE;
		}
		// If $conf['access'] is set but not with 'admin' then we return TRUE, if the module is found in the modList
		if (!strstr($conf['access'], 'admin') && $conf['name']) {
			$acs = $this->check('modules', $conf['name']);
		}
		if (!$acs && $exitOnError) {
			throw new \RuntimeException('Access Error: You don\'t have access to this module.', 1294586448);
		} else {
			return $acs;
		}
	}

	/**
	 * Returns a WHERE-clause for the pages-table where user permissions according to input argument, $perms, is validated.
	 * $perms is the "mask" used to select. Fx. if $perms is 1 then you'll get all pages that a user can actually see!
	 * 2^0 = show (1)
	 * 2^1 = edit (2)
	 * 2^2 = delete (4)
	 * 2^3 = new (8)
	 * If the user is 'admin' " 1=1" is returned (no effect)
	 * If the user is not set at all (->user is not an array), then " 1=0" is returned (will cause no selection results at all)
	 * The 95% use of this function is "->getPagePermsClause(1)" which will return WHERE clauses for *selecting* pages in backend listings - in other words this will check read permissions.
	 *
	 * @param integer $perms Permission mask to use, see function description
	 * @return string Part of where clause. Prefix " AND " to this.
	 * @todo Define visibility
	 */
	public function getPagePermsClause($perms) {
		if (is_array($this->user)) {
			if ($this->isAdmin()) {
				return ' 1=1';
			}
			$perms = intval($perms);
			// Make sure it's integer.
			$str = ' (' . '(pages.perms_everybody & ' . $perms . ' = ' . $perms . ')' . ' OR (pages.perms_userid = ' . $this->user['uid'] . ' AND pages.perms_user & ' . $perms . ' = ' . $perms . ')';
			// User
			if ($this->groupList) {
				// Group (if any is set)
				$str .= ' OR (pages.perms_groupid in (' . $this->groupList . ') AND pages.perms_group & ' . $perms . ' = ' . $perms . ')';
			}
			$str .= ')';
			// ****************
			// getPagePermsClause-HOOK
			// ****************
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['getPagePermsClause'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['getPagePermsClause'] as $_funcRef) {
					$_params = array('currentClause' => $str, 'perms' => $perms);
					$str = t3lib_div::callUserFunction($_funcRef, $_params, $this);
				}
			}
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
	 * @param array $row Input page row with all perms_* fields available.
	 * @return integer Bitwise representation of the users permissions in relation to input page row, $row
	 * @todo Define visibility
	 */
	public function calcPerms($row) {
		// Return 31 for admin users.
		if ($this->isAdmin()) {
			return 31;
		}
		$out = 0;
		if (isset($row['perms_userid']) && isset($row['perms_user']) && isset($row['perms_groupid']) && isset($row['perms_group']) && isset($row['perms_everybody']) && isset($this->groupList)) {
			if ($this->user['uid'] == $row['perms_userid']) {
				$out |= $row['perms_user'];
			}
			if ($this->isMemberOfGroup($row['perms_groupid'])) {
				$out |= $row['perms_group'];
			}
			$out |= $row['perms_everybody'];
		}
		// ****************
		// CALCPERMS hook
		// ****************
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['calcPerms'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['calcPerms'] as $_funcRef) {
				$_params = array(
					'row' => $row,
					'outputPermissions' => $out
				);
				$out = t3lib_div::callUserFunction($_funcRef, $_params, $this);
			}
		}
		return $out;
	}

	/**
	 * Returns TRUE if the RTE (Rich Text Editor) can be enabled for the user
	 * Strictly this is not permissions being checked but rather a series of settings like a loaded extension, browser/client type and a configuration option in ->uc[edit_RTE]
	 * The reasons for a FALSE return can be found in $this->RTE_errors
	 *
	 * @return boolean
	 * @todo Define visibility
	 */
	public function isRTE() {
		// Start:
		$this->RTE_errors = array();
		if (!$this->uc['edit_RTE']) {
			$this->RTE_errors[] = 'RTE is not enabled for user!';
		}
		if (!$GLOBALS['TYPO3_CONF_VARS']['BE']['RTEenabled']) {
			$this->RTE_errors[] = 'RTE is not enabled in $TYPO3_CONF_VARS["BE"]["RTEenabled"]';
		}
		// Acquire RTE object:
		$RTE = t3lib_BEfunc::RTEgetObj();
		if (!is_object($RTE)) {
			$this->RTE_errors = array_merge($this->RTE_errors, $RTE);
		}
		if (!count($this->RTE_errors)) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * Returns TRUE if the $value is found in the list in a $this->groupData[] index pointed to by $type (array key).
	 * Can thus be users to check for modules, exclude-fields, select/modify permissions for tables etc.
	 * If user is admin TRUE is also returned
	 * Please see the document Inside TYPO3 for examples.
	 *
	 * @param string $type The type value; "webmounts", "filemounts", "pagetypes_select", "tables_select", "tables_modify", "non_exclude_fields", "modules
	 * @param string $value String to search for in the groupData-list
	 * @return boolean TRUE if permission is granted (that is, the value was found in the groupData list - or the BE_USER is "admin")
	 * @todo Define visibility
	 */
	public function check($type, $value) {
		if (isset($this->groupData[$type])) {
			if ($this->isAdmin() || t3lib_div::inList($this->groupData[$type], $value)) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Checking the authMode of a select field with authMode set
	 *
	 * @param string $table Table name
	 * @param string $field Field name (must be configured in TCA and of type "select" with authMode set!)
	 * @param string $value Value to evaluation (single value, must not contain any of the chars ":,|")
	 * @param string $authMode Auth mode keyword (explicitAllow, explicitDeny, individual)
	 * @return boolean TRUE or FALSE whether access is granted or not.
	 * @todo Define visibility
	 */
	public function checkAuthMode($table, $field, $value, $authMode) {
		// Admin users can do anything:
		if ($this->isAdmin()) {
			return TRUE;
		}
		// Allow all blank values:
		if (!strcmp($value, '')) {
			return TRUE;
		}
		// Certain characters are not allowed in the value
		if (preg_match('/[:|,]/', $value)) {
			return FALSE;
		}
		// Initialize:
		$testValue = $table . ':' . $field . ':' . $value;
		$out = TRUE;
		// Checking value:
		switch ((string) $authMode) {
		case 'explicitAllow':
			if (!t3lib_div::inList($this->groupData['explicit_allowdeny'], ($testValue . ':ALLOW'))) {
				$out = FALSE;
			}
			break;
		case 'explicitDeny':
			if (t3lib_div::inList($this->groupData['explicit_allowdeny'], $testValue . ':DENY')) {
				$out = FALSE;
			}
			break;
		case 'individual':
			if (is_array($GLOBALS['TCA'][$table]) && is_array($GLOBALS['TCA'][$table]['columns'][$field])) {
				$items = $GLOBALS['TCA'][$table]['columns'][$field]['config']['items'];
				if (is_array($items)) {
					foreach ($items as $iCfg) {
						if (!strcmp($iCfg[1], $value) && $iCfg[4]) {
							switch ((string) $iCfg[4]) {
							case 'EXPL_ALLOW':
								if (!t3lib_div::inList($this->groupData['explicit_allowdeny'], ($testValue . ':ALLOW'))) {
									$out = FALSE;
								}
								break;
							case 'EXPL_DENY':
								if (t3lib_div::inList($this->groupData['explicit_allowdeny'], $testValue . ':DENY')) {
									$out = FALSE;
								}
								break;
							}
							break;
						}
					}
				}
			}
			break;
		}
		return $out;
	}

	/**
	 * Checking if a language value (-1, 0 and >0 for sys_language records) is allowed to be edited by the user.
	 *
	 * @param integer $langValue Language value to evaluate
	 * @return boolean Returns TRUE if the language value is allowed, otherwise FALSE.
	 * @todo Define visibility
	 */
	public function checkLanguageAccess($langValue) {
		// The users language list must be non-blank - otherwise all languages are allowed.
		if (strcmp(trim($this->groupData['allowed_languages']), '')) {
			$langValue = intval($langValue);
			// Language must either be explicitly allowed OR the lang Value be "-1" (all languages)
			if ($langValue != -1 && !$this->check('allowed_languages', $langValue)) {
				return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * Check if user has access to all existing localizations for a certain record
	 *
	 * @param string $table The table
	 * @param array $record The current record
	 * @return boolean
	 * @todo Define visibility
	 */
	public function checkFullLanguagesAccess($table, $record) {
		$recordLocalizationAccess = $this->checkLanguageAccess(0);
		if ($recordLocalizationAccess && (t3lib_BEfunc::isTableLocalizable($table) || isset($GLOBALS['TCA'][$table]['ctrl']['transForeignTable']))) {
			if (isset($GLOBALS['TCA'][$table]['ctrl']['transForeignTable'])) {
				$l10nTable = $GLOBALS['TCA'][$table]['ctrl']['transForeignTable'];
				$pointerField = $GLOBALS['TCA'][$l10nTable]['ctrl']['transOrigPointerField'];
				$pointerValue = $record['uid'];
			} else {
				$l10nTable = $table;
				$pointerField = $GLOBALS['TCA'][$l10nTable]['ctrl']['transOrigPointerField'];
				$pointerValue = $record[$pointerField] > 0 ? $record[$pointerField] : $record['uid'];
			}
			$recordLocalizations = t3lib_BEfunc::getRecordsByField($l10nTable, $pointerField, $pointerValue, '', '', '', '1');
			if (is_array($recordLocalizations)) {
				foreach ($recordLocalizations as $localization) {
					$recordLocalizationAccess = $recordLocalizationAccess && $this->checkLanguageAccess($localization[$GLOBALS['TCA'][$l10nTable]['ctrl']['languageField']]);
					if (!$recordLocalizationAccess) {
						break;
					}
				}
			}
		}
		return $recordLocalizationAccess;
	}

	/**
	 * Checking if a user has editing access to a record from a $GLOBALS['TCA'] table.
	 * The checks does not take page permissions and other "environmental" things into account. It only deal with record internals; If any values in the record fields disallows it.
	 * For instance languages settings, authMode selector boxes are evaluated (and maybe more in the future).
	 * It will check for workspace dependent access.
	 * The function takes an ID (integer) or row (array) as second argument.
	 *
	 * @param string $table Table name
	 * @param mixed $idOrRow If integer, then this is the ID of the record. If Array this just represents fields in the record.
	 * @param boolean $newRecord Set, if testing a new (non-existing) record array. Will disable certain checks that doesn't make much sense in that context.
	 * @param boolean $deletedRecord Set, if testing a deleted record array.
	 * @param boolean $checkFullLanguageAccess Set, whenever access to all translations of the record is required
	 * @return boolean TRUE if OK, otherwise FALSE
	 * @todo Define visibility
	 */
	public function recordEditAccessInternals($table, $idOrRow, $newRecord = FALSE, $deletedRecord = FALSE, $checkFullLanguageAccess = FALSE) {
		if (isset($GLOBALS['TCA'][$table])) {
			// Always return TRUE for Admin users.
			if ($this->isAdmin()) {
				return TRUE;
			}
			// Fetching the record if the $idOrRow variable was not an array on input:
			if (!is_array($idOrRow)) {
				if ($deletedRecord) {
					$idOrRow = t3lib_BEfunc::getRecord($table, $idOrRow, '*', '', FALSE);
				} else {
					$idOrRow = t3lib_BEfunc::getRecord($table, $idOrRow);
				}
				if (!is_array($idOrRow)) {
					$this->errorMsg = 'ERROR: Record could not be fetched.';
					return FALSE;
				}
			}
			// Checking languages:
			if ($GLOBALS['TCA'][$table]['ctrl']['languageField']) {
				// Language field must be found in input row - otherwise it does not make sense.
				if (isset($idOrRow[$GLOBALS['TCA'][$table]['ctrl']['languageField']])) {
					if (!$this->checkLanguageAccess($idOrRow[$GLOBALS['TCA'][$table]['ctrl']['languageField']])) {
						$this->errorMsg = 'ERROR: Language was not allowed.';
						return FALSE;
					} elseif ($checkFullLanguageAccess && $idOrRow[$GLOBALS['TCA'][$table]['ctrl']['languageField']] == 0 && !$this->checkFullLanguagesAccess($table, $idOrRow)) {
						$this->errorMsg = 'ERROR: Related/affected language was not allowed.';
						return FALSE;
					}
				} else {
					$this->errorMsg = 'ERROR: The "languageField" field named "' . $GLOBALS['TCA'][$table]['ctrl']['languageField'] . '" was not found in testing record!';
					return FALSE;
				}
			} elseif (isset($GLOBALS['TCA'][$table]['ctrl']['transForeignTable']) && $checkFullLanguageAccess && !$this->checkFullLanguagesAccess($table, $idOrRow)) {
				return FALSE;
			}
			// Checking authMode fields:
			if (is_array($GLOBALS['TCA'][$table]['columns'])) {
				foreach ($GLOBALS['TCA'][$table]['columns'] as $fieldName => $fieldValue) {
					if (isset($idOrRow[$fieldName])) {
						if ($fieldValue['config']['type'] == 'select' && $fieldValue['config']['authMode'] && !strcmp($fieldValue['config']['authMode_enforce'], 'strict')) {
							if (!$this->checkAuthMode($table, $fieldName, $idOrRow[$fieldName], $fieldValue['config']['authMode'])) {
								$this->errorMsg = 'ERROR: authMode "' . $fieldValue['config']['authMode'] . '" failed for field "' . $fieldName . '" with value "' . $idOrRow[$fieldName] . '" evaluated';
								return FALSE;
							}
						}
					}
				}
			}
			// Checking "editlock" feature (doesn't apply to new records)
			if (!$newRecord && $GLOBALS['TCA'][$table]['ctrl']['editlock']) {
				if (isset($idOrRow[$GLOBALS['TCA'][$table]['ctrl']['editlock']])) {
					if ($idOrRow[$GLOBALS['TCA'][$table]['ctrl']['editlock']]) {
						$this->errorMsg = 'ERROR: Record was locked for editing. Only admin users can change this state.';
						return FALSE;
					}
				} else {
					$this->errorMsg = 'ERROR: The "editLock" field named "' . $GLOBALS['TCA'][$table]['ctrl']['editlock'] . '" was not found in testing record!';
					return FALSE;
				}
			}
			// Checking record permissions
			// THIS is where we can include a check for "perms_" fields for other records than pages...
			// Process any hooks
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['recordEditAccessInternals'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['recordEditAccessInternals'] as $funcRef) {
					$params = array(
						'table' => $table,
						'idOrRow' => $idOrRow,
						'newRecord' => $newRecord
					);
					if (!t3lib_div::callUserFunction($funcRef, $params, $this)) {
						return FALSE;
					}
				}
			}
			// Finally, return TRUE if all is well.
			return TRUE;
		}
	}

	/**
	 * Checks a type of permission against the compiled permission integer, $compiledPermissions, and in relation to table, $tableName
	 *
	 * @param integer $compiledPermissions Could typically be the "compiled permissions" integer returned by ->calcPerms
	 * @param string $tableName Is the tablename to check: If "pages" table then edit,new,delete and editcontent permissions can be checked. Other tables will be checked for "editcontent" only (and $type will be ignored)
	 * @param string $actionType For $tableName='pages' this can be 'edit' (2), 'new' (8 or 16), 'delete' (4), 'editcontent' (16). For all other tables this is ignored. (16 is used)
	 * @return boolean
	 * @access public (used by typo3/alt_clickmenu.php)
	 */
	public function isPSet($compiledPermissions, $tableName, $actionType = '') {
		if ($this->isAdmin()) {
			$result = TRUE;
		} elseif ($tableName == 'pages') {
			switch ($actionType) {
			case 'edit':
				$result = ($compiledPermissions & 2) !== 0;
				break;
			case 'new':
				// Create new page OR page content
				$result = ($compiledPermissions & 8 + 16) !== 0;
				break;
			case 'delete':
				$result = ($compiledPermissions & 4) !== 0;
				break;
			case 'editcontent':
				$result = ($compiledPermissions & 16) !== 0;
				break;
			default:
				$result = FALSE;
			}
		} else {
			$result = ($compiledPermissions & 16) !== 0;
		}
		return $result;
	}

	/**
	 * Returns TRUE if the BE_USER is allowed to *create* shortcuts in the backend modules
	 *
	 * @return boolean
	 * @todo Define visibility
	 */
	public function mayMakeShortcut() {
		return $this->getTSConfigVal('options.enableBookmarks') && !$this->getTSConfigVal('options.mayNotCreateEditBookmarks');
	}

	/**
	 * Checking if editing of an existing record is allowed in current workspace if that is offline.
	 * Rules for editing in offline mode:
	 * - record supports versioning and is an offline version from workspace and has the corrent stage
	 * - or record (any) is in a branch where there is a page which is a version from the workspace and where the stage is not preventing records
	 *
	 * @param string $table Table of record
	 * @param array $recData Integer (record uid) or array where fields are at least: pid, t3ver_wsid, t3ver_stage (if versioningWS is set)
	 * @return string String error code, telling the failure state. FALSE=All ok
	 * @todo Define visibility
	 */
	public function workspaceCannotEditRecord($table, $recData) {
		// Only test offline spaces:
		if ($this->workspace !== 0) {
			if (!is_array($recData)) {
				$recData = t3lib_BEfunc::getRecord($table, $recData, 'pid' . ($GLOBALS['TCA'][$table]['ctrl']['versioningWS'] ? ',t3ver_wsid,t3ver_stage' : ''));
			}
			if (is_array($recData)) {
				// We are testing a "version" (identified by a pid of -1): it can be edited provided that workspace matches and versioning is enabled for the table.
				if ((int) $recData['pid'] === -1) {
					// No versioning, basic error, inconsistency even! Such records should not have a pid of -1!
					if (!$GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
						return 'Versioning disabled for table';
					} elseif ((int) $recData['t3ver_wsid'] !== $this->workspace) {
						// So does workspace match?
						return 'Workspace ID of record didn\'t match current workspace';
					} else {
						// So is the user allowed to "use" the edit stage within the workspace?
						return $this->workspaceCheckStageForCurrent(0) ? FALSE : 'User\'s access level did not allow for editing';
					}
				} else {
					// We are testing a "live" record:
					// For "Live" records, check that PID for table allows editing
					if ($res = $this->workspaceAllowLiveRecordsInPID($recData['pid'], $table)) {
						// Live records are OK in this branch, but what about the stage of branch point, if any:
						// OK
						return $res > 0 ? FALSE : 'Stage for versioning root point and users access level did not allow for editing';
					} else {
						// If not offline and not in versionized branch, output error:
						return 'Online record was not in versionized branch!';
					}
				}
			} else {
				return 'No record';
			}
		} else {
			// OK because workspace is 0
			return FALSE;
		}
	}

	/**
	 * Evaluates if a user is allowed to edit the offline version
	 *
	 * @param string $table Table of record
	 * @param array $recData Integer (record uid) or array where fields are at least: pid, t3ver_wsid, t3ver_stage (if versioningWS is set)
	 * @return string String error code, telling the failure state. FALSE=All ok
	 * @see workspaceCannotEditRecord()
	 * @todo Define visibility
	 */
	public function workspaceCannotEditOfflineVersion($table, $recData) {
		if ($GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
			if (!is_array($recData)) {
				$recData = t3lib_BEfunc::getRecord($table, $recData, 'uid,pid,t3ver_wsid,t3ver_stage');
			}
			if (is_array($recData)) {
				if ((int) $recData['pid'] === -1) {
					return $this->workspaceCannotEditRecord($table, $recData);
				} else {
					return 'Not an offline version';
				}
			} else {
				return 'No record';
			}
		} else {
			return 'Table does not support versioning.';
		}
	}

	/**
	 * Check if "live" records from $table may be created or edited in this PID.
	 * If the answer is FALSE it means the only valid way to create or edit records in the PID is by versioning
	 * If the answer is 1 or 2 it means it is OK to create a record, if -1 it means that it is OK in terms of versioning because the element was within a versionized branch but NOT ok in terms of the state the root point had!
	 *
	 * @param integer $pid PID value to check for.
	 * @param string $table Table name
	 * @return mixed Returns FALSE if a live record cannot be created and must be versionized in order to do so. 2 means a) Workspace is "Live" or workspace allows "live edit" of records from non-versionized tables (and the $table is not versionizable). 1 and -1 means the pid is inside a versionized branch where -1 means that the branch-point did NOT allow a new record according to its state.
	 * @todo Define visibility
	 */
	public function workspaceAllowLiveRecordsInPID($pid, $table) {
		// Always for Live workspace AND if live-edit is enabled and tables are completely without versioning it is ok as well.
		if ($this->workspace === 0 || $this->workspaceRec['live_edit'] && !$GLOBALS['TCA'][$table]['ctrl']['versioningWS'] || $GLOBALS['TCA'][$table]['ctrl']['versioningWS_alwaysAllowLiveEdit']) {
			// OK to create for this table.
			return 2;
		} else {
			// If the answer is FALSE it means the only valid way to create or edit records in the PID is by versioning
			return FALSE;
		}
	}

	/**
	 * Evaluates if a record from $table can be created in $pid
	 *
	 * @param integer $pid Page id. This value must be the _ORIG_uid if available: So when you have pages versionized as "page" or "element" you must supply the id of the page version in the workspace!
	 * @param string $table Table name
	 * @return boolean TRUE if OK.
	 * @todo Define visibility
	 */
	public function workspaceCreateNewRecord($pid, $table) {
		if ($res = $this->workspaceAllowLiveRecordsInPID($pid, $table)) {
			// If LIVE records cannot be created in the current PID due to workspace restrictions, prepare creation of placeholder-record
			if ($res < 0) {
				// Stage for versioning root point and users access level did not allow for editing
				return FALSE;
			}
		} elseif (!$GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
			// So, if no live records were allowed, we have to create a new version of this record:
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Evaluates if auto creation of a version of a record is allowed.
	 *
	 * @param string $table Table of the record
	 * @param integer $id UID of record
	 * @param integer $recpid PID of record
	 * @return boolean TRUE if ok.
	 * @todo Define visibility
	 */
	public function workspaceAllowAutoCreation($table, $id, $recpid) {
		// Auto-creation of version: In offline workspace, test if versioning is
		// enabled and look for workspace version of input record.
		// If there is no versionized record found we will create one and save to that.
		if ($this->workspace !== 0 && $GLOBALS['TCA'][$table]['ctrl']['versioningWS'] && $recpid >= 0 &&
			!t3lib_BEfunc::getWorkspaceVersionOfRecord($this->workspace, $table, $id, 'uid')) {
			// There must be no existing version of this record in workspace.
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Checks if an element stage allows access for the user in the current workspace
	 * In live workspace (= 0) access is always granted for any stage.
	 * Admins are always allowed.
	 * An option for custom workspaces allows members to also edit when the stage is "Review"
	 *
	 * @param integer $stage Stage id from an element: -1,0 = editing, 1 = reviewer, >1 = owner
	 * @return boolean TRUE if user is allowed access
	 * @todo Define visibility
	 */
	public function workspaceCheckStageForCurrent($stage) {
		// Always allow for admins
		if ($this->isAdmin()) {
			return TRUE;
		}
		if ($this->workspace !== 0 && t3lib_extMgm::isLoaded('workspaces')) {
			$stage = intval($stage);
			$stat = $this->checkWorkspaceCurrent();
			// Check if custom staging is activated
			$workspaceRec = t3lib_BEfunc::getRecord('sys_workspace', $stat['uid']);
			if ($workspaceRec['custom_stages'] > 0 && $stage !== 0 && $stage !== -10) {
				// Get custom stage record
				$workspaceStageRec = t3lib_BEfunc::getRecord('sys_workspace_stage', $stage);
				// Check if the user is responsible for the current stage
				if (t3lib_div::inList($workspaceStageRec['responsible_persons'], 'be_users_' . $this->user['uid']) && $stat['_ACCESS'] === 'member' || $stat['_ACCESS'] === 'owner') {
					return TRUE;
				}
				// Check if the user is in a group which is responsible for the current stage
				foreach ($this->userGroupsUID as $groupUid) {
					if (t3lib_div::inList($workspaceStageRec['responsible_persons'], 'be_groups_' . $groupUid) && $stat['_ACCESS'] === 'member' || $stat['_ACCESS'] === 'owner') {
						return TRUE;
					}
				}
			} elseif ($stage == -10 || $stage == -20) {
				if ($stat['_ACCESS'] === 'owner') {
					return TRUE;
				} else {
					return FALSE;
				}
			} else {
				$memberStageLimit = $this->workspaceRec['review_stage_edit'] ? 1 : 0;
				if ($stage <= $memberStageLimit && $stat['_ACCESS'] === 'member' || $stage <= 1 && $stat['_ACCESS'] === 'reviewer' || $stat['_ACCESS'] === 'owner') {
					return TRUE;
				}
			}
		} else {
			// Always OK for live workspace.
			return TRUE;
		}
	}

	/**
	 * Returns TRUE if the user has access to publish content from the workspace ID given.
	 * Admin-users are always granted access to do this
	 * If the workspace ID is 0 (live) all users have access also
	 * For custom workspaces it depends on whether the user is owner OR like with draft workspace if the user has access to Live workspace.
	 *
	 * @param integer $wsid Workspace UID; 0,1+
	 * @return boolean Returns TRUE if the user has access to publish content from the workspace ID given.
	 * @todo Define visibility
	 */
	public function workspacePublishAccess($wsid) {
		if ($this->isAdmin()) {
			return TRUE;
		}
		// If no access to workspace, of course you cannot publish!
		$retVal = FALSE;
		$wsAccess = $this->checkWorkspace($wsid);
		if ($wsAccess) {
			switch ($wsAccess['uid']) {
			case 0:
				// Live workspace
				// If access to Live workspace, no problem.
				$retVal = TRUE;
				break;
			default:
				// Custom workspace
				$retVal = $wsAccess['_ACCESS'] === 'owner' || $this->checkWorkspace(0) && !($wsAccess['publish_access'] & 2);
				// Either be an adminuser OR have access to online workspace which is OK as well as long as publishing access is not limited by workspace option.
				break;
			}
		}
		return $retVal;
	}

	/**
	 * Workspace swap-mode access?
	 *
	 * @return boolean Returns TRUE if records can be swapped in the current workspace, otherwise FALSE
	 * @todo Define visibility
	 */
	public function workspaceSwapAccess() {
		if ($this->workspace > 0 && (int) $this->workspaceRec['swap_modes'] === 2) {
			return FALSE;
		} else {
			return TRUE;
		}
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
	 * @param string $objectString Pointer to an "object" in the TypoScript array, fx. 'options.dontMountAdminMounts'
	 * @param array $config Optional TSconfig array: If array, then this is used and not $this->userTS. If not array, $this->userTS is used.
	 * @return array An array with two keys, "value" and "properties" where "value" is a string with the value of the objectsting and "properties" is an array with the properties of the objectstring.
	 * @todo Define visibility
	 */
	public function getTSConfig($objectString, $config = '') {
		if (!is_array($config)) {
			// Getting Root-ts if not sent
			$config = $this->userTS;
		}
		$TSConf = array();
		$parts = explode('.', $objectString, 2);
		$key = $parts[0];
		if (trim($key)) {
			if (count($parts) > 1 && trim($parts[1])) {
				// Go on, get the next level
				if (is_array($config[$key . '.'])) {
					$TSConf = $this->getTSConfig($parts[1], $config[$key . '.']);
				}
			} else {
				$TSConf['value'] = $config[$key];
				$TSConf['properties'] = $config[$key . '.'];
			}
		}
		return $TSConf;
	}

	/**
	 * Returns the "value" of the $objectString from the BE_USERS "User TSconfig" array
	 *
	 * @param string $objectString Object string, eg. "somestring.someproperty.somesubproperty
	 * @return string The value for that object string (object path)
	 * @see 	getTSConfig()
	 * @todo Define visibility
	 */
	public function getTSConfigVal($objectString) {
		$TSConf = $this->getTSConfig($objectString);
		return $TSConf['value'];
	}

	/**
	 * Returns the "properties" of the $objectString from the BE_USERS "User TSconfig" array
	 *
	 * @param string $objectString Object string, eg. "somestring.someproperty.somesubproperty
	 * @return array The properties for that object string (object path) - if any
	 * @see 	getTSConfig()
	 * @todo Define visibility
	 */
	public function getTSConfigProp($objectString) {
		$TSConf = $this->getTSConfig($objectString);
		return $TSConf['properties'];
	}

	/**
	 * Returns an array with the webmounts.
	 * If no webmounts, and empty array is returned.
	 * NOTICE: Deleted pages WILL NOT be filtered out! So if a mounted page has been deleted it is STILL coming out as a webmount. This is not checked due to performance.
	 *
	 * @return array
	 * @todo Define visibility
	 */
	public function returnWebmounts() {
		return (string) $this->groupData['webmounts'] != '' ? explode(',', $this->groupData['webmounts']) : array();
	}

	/**
	 * Returns TRUE or FALSE, depending if an alert popup (a javascript confirmation) should be shown
	 * call like $GLOBALS['BE_USER']->jsConfirmation($BITMASK)
	 *
	 * 1 - typeChange
	 * 2 - copy/move/paste
	 * 4 - delete
	 * 8 - frontend editing
	 * 128 - other (not used yet)
	 *
	 * @param integer $bitmask Bitmask
	 * @return boolean TRUE if the confirmation should be shown
	 * @todo Define visibility
	 */
	public function jsConfirmation($bitmask) {
		$alertPopup = $GLOBALS['BE_USER']->getTSConfig('options.alertPopups');
		if (empty($alertPopup['value'])) {
			// Default: show all warnings
			$alertPopup = 255;
		} else {
			$alertPopup = (int) $alertPopup['value'];
		}
		// Show confirmation
		if (($alertPopup & $bitmask) == $bitmask) {
			return 1;
		} else {
			// don't show confirmation
			return 0;
		}
	}

	/*************************************
	 *
	 * Authentication methods
	 *
	 *************************************/
	/**
	 * Initializes a lot of stuff like the access-lists, database-mountpoints and filemountpoints
	 * This method is called by ->backendCheckLogin() (from extending class t3lib_beuserauth) if the backend user login has verified OK.
	 * Generally this is required initialization of a backend user.
	 *
	 * @return void
	 * @access private
	 * @see t3lib_TSparser
	 * @todo Define visibility
	 */
	public function fetchGroupData() {
		if ($this->user['uid']) {
			// Get lists for the be_user record and set them as default/primary values.
			// Enabled Backend Modules
			$this->dataLists['modList'] = $this->user['userMods'];
			// Add Allowed Languages
			$this->dataLists['allowed_languages'] = $this->user['allowed_languages'];
			// Set user value for workspace permissions.
			$this->dataLists['workspace_perms'] = $this->user['workspace_perms'];
			// Database mountpoints
			$this->dataLists['webmount_list'] = $this->user['db_mountpoints'];
			// File mountpoints
			$this->dataLists['filemount_list'] = $this->user['file_mountpoints'];
			// Fileoperation permissions
			$this->dataLists['fileoper_perms'] = (int) $this->user['fileoper_perms'];
			// Setting default User TSconfig:
			$this->TSdataArray[] = $this->addTScomment('From $GLOBALS["TYPO3_CONF_VARS"]["BE"]["defaultUserTSconfig"]:') . $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultUserTSconfig'];
			// Default TSconfig for admin-users
			if ($this->isAdmin()) {
				$this->TSdataArray[] = $this->addTScomment('"admin" user presets:') . '
					admPanel.enable.all = 1
				';
				if (t3lib_extMgm::isLoaded('sys_note')) {
					$this->TSdataArray[] = '
							// Setting defaults for sys_note author / email...
						TCAdefaults.sys_note.author = ' . $this->user['realName'] . '
						TCAdefaults.sys_note.email = ' . $this->user['email'] . '
					';
				}
			}
			// BE_GROUPS:
			// Get the groups...
			// 240203: Since the group-field never contains any references to groups with a prepended table name
			// we think it's safe to just intExplode and re-implode - which should be much faster than the other function call.
			$grList = $GLOBALS['TYPO3_DB']->cleanIntList($this->user[$this->usergroup_column]);
			if ($grList) {
				// Fetch groups will add a lot of information to the internal arrays: modules, accesslists, TSconfig etc. Refer to fetchGroups() function.
				$this->fetchGroups($grList);
			}
			// Add the TSconfig for this specific user:
			$this->TSdataArray[] = $this->addTScomment('USER TSconfig field') . $this->user['TSconfig'];
			// Check include lines.
			$this->TSdataArray = t3lib_TSparser::checkIncludeLines_array($this->TSdataArray);
			// Imploding with "[global]" will make sure that non-ended confinements with braces are ignored.
			$this->userTS_text = implode(LF . '[GLOBAL]' . LF, $this->TSdataArray);
			if ($GLOBALS['TYPO3_CONF_VARS']['BE']['TSconfigConditions'] && !$this->userTS_dontGetCached) {
				// Perform TS-Config parsing with condition matching
				$parseObj = t3lib_div::makeInstance('t3lib_TSparser_TSconfig');
				$res = $parseObj->parseTSconfig($this->userTS_text, 'userTS');
				if ($res) {
					$this->userTS = $res['TSconfig'];
					$this->userTSUpdated = $res['cached'] ? 0 : 1;
				}
			} else {
				// Parsing the user TSconfig (or getting from cache)
				$hash = md5('userTS:' . $this->userTS_text);
				$cachedContent = t3lib_BEfunc::getHash($hash);
				if (isset($cachedContent) && !$this->userTS_dontGetCached) {
					$this->userTS = unserialize($cachedContent);
				} else {
					$parseObj = t3lib_div::makeInstance('t3lib_TSparser');
					$parseObj->parse($this->userTS_text);
					$this->userTS = $parseObj->setup;
					t3lib_BEfunc::storeHash($hash, serialize($this->userTS), 'BE_USER_TSconfig');
					// Update UC:
					$this->userTSUpdated = 1;
				}
			}
			// Processing webmounts
			// Admin's always have the root mounted
			if ($this->isAdmin() && !$this->getTSConfigVal('options.dontMountAdminMounts')) {
				$this->dataLists['webmount_list'] = '0,' . $this->dataLists['webmount_list'];
			}
			// The lists are cleaned for duplicates
			$this->groupData['webmounts'] = t3lib_div::uniqueList($this->dataLists['webmount_list']);
			$this->groupData['pagetypes_select'] = t3lib_div::uniqueList($this->dataLists['pagetypes_select']);
			$this->groupData['tables_select'] = t3lib_div::uniqueList($this->dataLists['tables_modify'] . ',' . $this->dataLists['tables_select']);
			$this->groupData['tables_modify'] = t3lib_div::uniqueList($this->dataLists['tables_modify']);
			$this->groupData['non_exclude_fields'] = t3lib_div::uniqueList($this->dataLists['non_exclude_fields']);
			$this->groupData['explicit_allowdeny'] = t3lib_div::uniqueList($this->dataLists['explicit_allowdeny']);
			$this->groupData['allowed_languages'] = t3lib_div::uniqueList($this->dataLists['allowed_languages']);
			$this->groupData['custom_options'] = t3lib_div::uniqueList($this->dataLists['custom_options']);
			$this->groupData['modules'] = t3lib_div::uniqueList($this->dataLists['modList']);
			$this->groupData['fileoper_perms'] = $this->dataLists['fileoper_perms'];
			$this->groupData['workspace_perms'] = $this->dataLists['workspace_perms'];
			// Populating the $this->userGroupsUID -array with the groups in the order in which they were LAST included.!!
			$this->userGroupsUID = array_reverse(array_unique(array_reverse($this->includeGroupArray)));
			// Finally this is the list of group_uid's in the order they are parsed (including subgroups!) and without duplicates (duplicates are presented with their last entrance in the list, which thus reflects the order of the TypoScript in TSconfig)
			$this->groupList = implode(',', $this->userGroupsUID);
			$this->setCachedList($this->groupList);
			// Checking read access to webmounts:
			if (trim($this->groupData['webmounts']) !== '') {
				$webmounts = explode(',', $this->groupData['webmounts']);
				// Explode mounts
				// Selecting all webmounts with permission clause for reading
				$MProws = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid', 'pages', 'deleted=0 AND uid IN (' . $this->groupData['webmounts'] . ') AND ' . $this->getPagePermsClause(1), '', '', '', 'uid');
				foreach ($webmounts as $idx => $mountPointUid) {
					// If the mount ID is NOT found among selected pages, unset it:
					if ($mountPointUid > 0 && !isset($MProws[$mountPointUid])) {
						unset($webmounts[$idx]);
					}
				}
				// Implode mounts in the end.
				$this->groupData['webmounts'] = implode(',', $webmounts);
			}
			// Setting up workspace situation (after webmounts are processed!):
			$this->workspaceInit();
		}
	}

	/**
	 * Fetches the group records, subgroups and fills internal arrays.
	 * Function is called recursively to fetch subgroups
	 *
	 * @param string $grList Commalist of be_groups uid numbers
	 * @param string $idList List of already processed be_groups-uids so the function will not fall into a eternal recursion.
	 * @return void
	 * @access private
	 * @todo Define visibility
	 */
	public function fetchGroups($grList, $idList = '') {
		// Fetching records of the groups in $grList (which are not blocked by lockedToDomain either):
		$lockToDomain_SQL = ' AND (lockToDomain=\'\' OR lockToDomain IS NULL OR lockToDomain=\'' . t3lib_div::getIndpEnv('HTTP_HOST') . '\')';
		$whereSQL = 'deleted=0 AND hidden=0 AND pid=0 AND uid IN (' . $grList . ')' . $lockToDomain_SQL;
		// Hook for manipulation of the WHERE sql sentence which controls which BE-groups are included
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['fetchGroupQuery'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['fetchGroupQuery'] as $classRef) {
				$hookObj = t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'fetchGroupQuery_processQuery')) {
					$whereSQL = $hookObj->fetchGroupQuery_processQuery($this, $grList, $idList, $whereSQL);
				}
			}
		}
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $this->usergroup_table, $whereSQL);
		// The userGroups array is filled
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$this->userGroups[$row['uid']] = $row;
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		// Traversing records in the correct order
		$include_staticArr = t3lib_div::intExplode(',', $grList);
		// Traversing list
		foreach ($include_staticArr as $key => $uid) {
			// Get row:
			$row = $this->userGroups[$uid];
			// Must be an array and $uid should not be in the idList, because then it is somewhere previously in the grouplist
			if (is_array($row) && !t3lib_div::inList($idList, $uid)) {
				// Include sub groups
				if (trim($row['subgroup'])) {
					// Make integer list
					$theList = implode(',', t3lib_div::intExplode(',', $row['subgroup']));
					// Call recursively, pass along list of already processed groups so they are not recursed again.
					$this->fetchGroups($theList, $idList . ',' . $uid);
				}
				// Add the group uid, current list, TSconfig to the internal arrays.
				$this->includeGroupArray[] = $uid;
				$this->includeHierarchy[] = $idList;
				$this->TSdataArray[] = $this->addTScomment(('Group "' . $row['title'] . '" [' . $row['uid'] . '] TSconfig field:')) . $row['TSconfig'];
				// Mount group database-mounts
				if (($this->user['options'] & 1) == 1) {
					$this->dataLists['webmount_list'] .= ',' . $row['db_mountpoints'];
				}
				// Mount group file-mounts
				if (($this->user['options'] & 2) == 2) {
					$this->dataLists['filemount_list'] .= ',' . $row['file_mountpoints'];
				}
				// The lists are made: groupMods, tables_select, tables_modify, pagetypes_select, non_exclude_fields, explicit_allowdeny, allowed_languages, custom_options
				if ($row['inc_access_lists'] == 1) {
					$this->dataLists['modList'] .= ',' . $row['groupMods'];
					$this->dataLists['tables_select'] .= ',' . $row['tables_select'];
					$this->dataLists['tables_modify'] .= ',' . $row['tables_modify'];
					$this->dataLists['pagetypes_select'] .= ',' . $row['pagetypes_select'];
					$this->dataLists['non_exclude_fields'] .= ',' . $row['non_exclude_fields'];
					$this->dataLists['explicit_allowdeny'] .= ',' . $row['explicit_allowdeny'];
					$this->dataLists['allowed_languages'] .= ',' . $row['allowed_languages'];
					$this->dataLists['custom_options'] .= ',' . $row['custom_options'];
				}
				// Setting fileoperation permissions
				$this->dataLists['fileoper_perms'] |= (int) $row['fileoper_perms'];
				// Setting workspace permissions:
				$this->dataLists['workspace_perms'] |= $row['workspace_perms'];
				// If this function is processing the users OWN group-list (not subgroups) AND if the ->firstMainGroup is not set, then the ->firstMainGroup will be set.
				if (!strcmp($idList, '') && !$this->firstMainGroup) {
					$this->firstMainGroup = $uid;
				}
			}
		}
		// ****************
		// HOOK: fetchGroups_postProcessing
		// ****************
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['fetchGroups_postProcessing'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['fetchGroups_postProcessing'] as $_funcRef) {
				$_params = array();
				t3lib_div::callUserFunction($_funcRef, $_params, $this);
			}
		}
	}

	/**
	 * Updates the field be_users.usergroup_cached_list if the groupList of the user has changed/is different from the current list.
	 * The field "usergroup_cached_list" contains the list of groups which the user is a member of. After authentication (where these functions are called...) one can depend on this list being a representation of the exact groups/subgroups which the BE_USER has membership with.
	 *
	 * @param string $cList The newly compiled group-list which must be compared with the current list in the user record and possibly stored if a difference is detected.
	 * @return void
	 * @access private
	 * @todo Define visibility
	 */
	public function setCachedList($cList) {
		if ((string) $cList != (string) $this->user['usergroup_cached_list']) {
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('be_users', 'uid=' . intval($this->user['uid']), array('usergroup_cached_list' => $cList));
		}
	}

	/***********************
	 * FILE MOUNT FUNCTIONS
	 **********************/
	/**
	 * Sets up all file storages for a user.
	 * Needs to be called AFTER the groups have been loaded.
	 *
	 * @return void
	 */
	protected function initializeFileStorages() {
		$this->fileStorages = array();
		/** @var $storageRepository \TYPO3\CMS\Core\Resource\StorageRepository */
		$storageRepository = t3lib_div::makeInstance('\TYPO3\CMS\Core\Resource\StorageRepository');
		// Admin users have all file storages visible, without any filters
		if ($this->isAdmin()) {
			$storageObjects = $storageRepository->findAll();
			foreach ($storageObjects as $storageObject) {
				$this->fileStorages[$storageObject->getUid()] = $storageObject;
			}
		} else {
			// If userHomePath is set, we attempt to mount it
			if ($GLOBALS['TYPO3_CONF_VARS']['BE']['userHomePath']) {
				list($userHomeStorageUid, $userHomeFilter) = explode(':', $GLOBALS['TYPO3_CONF_VARS']['BE']['userHomePath'], 2);
				$userHomeStorageUid = intval($userHomeStorageUid);
				if ($userHomeStorageUid > 0) {
					$storageObject = $storageRepository->findByUid($userHomeStorageUid);
					// First try and mount with [uid]_[username]
					$userHomeFilterIdentifier = $userHomeFilter . $this->user['uid'] . '_' . $this->user['username'] . $GLOBALS['TYPO3_CONF_VARS']['BE']['userUploadDir'];
					$didMount = $storageObject->addFileMount($userHomeFilterIdentifier);
					// If that failed, try and mount with only [uid]
					if (!$didMount) {
						$userHomeFilterIdentifier = $userHomeFilter . $this->user['uid'] . '_' . $this->user['username'] . $GLOBALS['TYPO3_CONF_VARS']['BE']['userUploadDir'];
						$storageObject->addFileMount($userHomeFilterIdentifier);
					}
					$this->fileStorages[$storageObject->getUid()] = $storageObject;
				}
			}
			// Mount group home-dirs
			if (($this->user['options'] & 2) == 2 && $GLOBALS['TYPO3_CONF_VARS']['BE']['groupHomePath'] != '') {
				// If groupHomePath is set, we attempt to mount it
				list($groupHomeStorageUid, $groupHomeFilter) = explode(':', $GLOBALS['TYPO3_CONF_VARS']['BE']['groupHomePath'], 2);
				$groupHomeStorageUid = intval($groupHomeStorageUid);
				if ($groupHomeStorageUid > 0) {
					$storageObject = $storageRepository->findByUid($groupHomeStorageUid);
					foreach ($this->userGroups as $groupUid => $groupData) {
						$groupHomeFilterIdentifier = $groupHomeFilter . $groupData['uid'];
						$storageObject->addFileMount($groupHomeFilterIdentifier);
					}
					$this->fileStorages[$storageObject->getUid()] = $storageObject;
				}
			}
			// Processing filemounts (both from the user and the groups)
			$this->dataLists['filemount_list'] = t3lib_div::uniqueList($this->dataLists['filemount_list']);
			if ($this->dataLists['filemount_list']) {
				$orderBy = $GLOBALS['TCA']['sys_filemounts']['ctrl']['default_sortby'] ? $GLOBALS['TYPO3_DB']->stripOrderBy($GLOBALS['TCA']['sys_filemounts']['ctrl']['default_sortby']) : 'sorting';
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_filemounts', 'deleted=0 AND hidden=0 AND pid=0 AND uid IN (' . $this->dataLists['filemount_list'] . ')', '', $orderBy);
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					$storageObject = $storageRepository->findByUid($row['base']);
					$storageObject->addFileMount($row['path'], $row);
					$this->fileStorages[$storageObject->getUid()] = $storageObject;
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
			}
		}
		// Injects the users' permissions to each storage
		foreach ($this->fileStorages as $storageObject) {
			$storagePermissions = $this->getFilePermissionsForStorage($storageObject);
			$storageObject->setUserPermissions($storagePermissions);
		}
		// more narrowing down through the workspace
		$this->initializeFileStoragesForWorkspace();
		// this has to be called always in order to set certain filters
		// @todo Should be in BE_USER object then
		$GLOBALS['BE_USER']->evaluateUserSpecificFileFilterSettings();
	}

	/**
	 * Returns an array with the filemounts for the user.
	 * Each filemount is represented with an array of a "name", "path" and "type".
	 * If no filemounts an empty array is returned.
	 *
	 * @api
	 * @return \TYPO3\CMS\Core\Resource\ResourceStorage[]
	 */
	public function getFileStorages() {
		// Initializing file mounts after the groups are fetched
		if ($this->fileStorages === NULL) {
			$this->initializeFileStorages();
		}
		return $this->fileStorages;
	}

	/**
	 * Adds filters based on what the user has set
	 * this should be done in this place, and called whenever needed,
	 * but only when needed
	 *
	 * @return void
	 * @todo Should be in BE_USER object then
	 */
	public function evaluateUserSpecificFileFilterSettings() {
		// Add the option for also displaying the non-hidden files
		if ($this->uc['showHiddenFilesAndFolders']) {
			\TYPO3\CMS\Core\Resource\Filter\FileNameFilter::setShowHiddenFilesAndFolders(TRUE);
		}
	}

	/**
	 * Returns the information about file permissions
	 * previously, this was stored in the DB fields (fileoper_perms)
	 * but is now handled via userTSconfig
	 *
	 * permissions.file.default {
	 * addFile = 1
	 * readFile = 1
	 * editFile = 1
	 * writeFile = 1
	 * uploadFile = 1
	 * copyFile = 1
	 * moveFile = 1
	 * renameFile = 1
	 * unzipFile = 1
	 * removeFile = 1
	 *
	 * addFolder = 1
	 * readFolder = 1
	 * moveFolder = 1
	 * writeFolder = 1
	 * renameFolder = 1
	 * removeFolder = 1
	 * removeSubfolders = 1
	 * }
	 *
	 * # overwrite settings for a specific storageObject
	 * permissions.file.storage.StorageUid {
	 * readFile = 0
	 * removeSubfolders = 1
	 * }
	 *
	 * Please note that these permissions only apply, if the storage has the
	 * capabilities (browseable, writable), and if the driver allows for writing etc
	 *
	 * @api
	 * @return array
	 */
	public function getFilePermissions() {
		if (!isset($this->filePermissions)) {
			$defaultOptions = array(
				'addFile' => TRUE,
				// new option
				'readFile' => TRUE,
				// new option, generic check of the user rights
				'editFile' => TRUE,
				// new option
				'writeFile' => TRUE,
				// new option, generic check of the user rights
				'uploadFile' => TRUE,
				'copyFile' => TRUE,
				'moveFile' => TRUE,
				'renameFile' => TRUE,
				'unzipFile' => TRUE,
				'removeFile' => TRUE,
				'addFolder' => TRUE,
				'readFolder' => TRUE,
				// new option,, generic check of the user rights
				'moveFolder' => TRUE,
				'renameFolder' => TRUE,
				'writeFolder' => TRUE,
				// new option, generic check of the user rights
				'removeFolder' => TRUE,
				'removeSubfolders' => TRUE
			);
			if (!$this->isAdmin()) {
				$this->filePermissions = $this->getTSConfig('permissions.file.default');
				if (!is_array($this->filePermissions)) {
					$oldFileOperationPermissions = $this->getFileoperationPermissions();
					// Lower permissions if the old file operation permissions are not set
					if ($oldFileOperationPermissions ^ 1) {
						$defaultOptions['addFile'] = FALSE;
						$defaultOptions['uploadFile'] = FALSE;
						$defaultOptions['copyFile'] = FALSE;
						$defaultOptions['moveFile'] = FALSE;
						$defaultOptions['renameFile'] = FALSE;
						$defaultOptions['removeFile'] = FALSE;
					}
					if ($oldFileOperationPermissions ^ 2) {
						$defaultOptions['unzipFile'] = FALSE;
					}
					if ($oldFileOperationPermissions ^ 4) {
						$defaultOptions['addFolder'] = FALSE;
						$defaultOptions['moveFolder'] = FALSE;
						$defaultOptions['renameFolder'] = FALSE;
						$defaultOptions['removeFolder'] = FALSE;
					}
					if ($oldFileOperationPermissions ^ 8) {
						$defaultOptions['copyFolder'] = FALSE;
					}
					if ($oldFileOperationPermissions ^ 16) {
						$defaultOptions['removeSubfolders'] = FALSE;
					}
				}
			}
			$this->filePermissions = $defaultOptions;
		}
		return $this->filePermissions;
	}

	/**
	 * Gets the file permissions for a storage
	 * by merging any storage-specific permissions for a
	 * storage with the default settings
	 *
	 * @api
	 * @param \TYPO3\CMS\Core\Resource\ResourceStorage $storageObject
	 * @return array
	 */
	public function getFilePermissionsForStorage(\TYPO3\CMS\Core\Resource\ResourceStorage $storageObject) {
		$defaultFilePermissions = $this->getFilePermissions();
		$storageFilePermissions = $this->getTSConfig('permissions.file.storage.' . $storageObject->getUid());
		if (is_array($storageFilePermissions) && count($storageFilePermissions)) {
			return array_merge($storageFilePermissions, $defaultFilePermissions);
		} else {
			return $defaultFilePermissions;
		}
	}

	/**
	 * Returns a \TYPO3\CMS\Core\Resource\Folder object that is used for uploading
	 * files by default.
	 * This is used for RTE and its magic images, as well as uploads
	 * in the TCEforms fields, unless otherwise configured (will be added in the future)
	 *
	 * the default upload folder for a user is the defaultFolder on the first
	 * filestorage/filemount that the user can access
	 * however, you can set the users' upload folder like this:
	 *
	 * options.defaultUploadFolder = 3:myfolder/yourfolder/
	 *
	 * @return \TYPO3\CMS\Core\Resource\Folder|boolean The default upload folder for this user
	 */
	public function getDefaultUploadFolder() {
		$uploadFolder = $this->getTSConfigVal('options.defaultUploadFolder');
		if ($uploadFolder) {
			$uploadFolder = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->getFolderObjectFromCombinedIdentifier($uploadFolder);
		} else {
			$storages = $this->getFileStorages();
			if (count($storages) > 0) {
				/** @var $firstStorage \TYPO3\CMS\Core\Resource\ResourceStorage */
				$firstStorage = reset($storages);
				$uploadFolder = $firstStorage->getDefaultFolder();
			}
		}
		if ($uploadFolder instanceof \TYPO3\CMS\Core\Resource\Folder) {
			return $uploadFolder;
		} else {
			return FALSE;
		}
	}

	/**
	 * Returns an integer bitmask that represents the permissions for file operations.
	 * Permissions of the user and groups the user is a member of were combined by a logical OR.
	 *
	 * Meaning of each bit:
	 * 1 - Files: Upload,Copy,Move,Delete,Rename
	 * 2 - Files: Unzip
	 * 4 - Directory: Move,Delete,Rename,New
	 * 8 - Directory: Copy
	 * 16 - Directory: Delete recursively (rm -Rf)
	 *
	 * @return integer File operation permission bitmask
	 * @deprecated since TYPO3 6.0, use the TSconfig settings instead
	 */
	public function getFileoperationPermissions() {
		t3lib_div::logDeprecatedFunction();
		if ($this->isAdmin()) {
			return 31;
		} else {
			return $this->groupData['fileoper_perms'];
		}
	}

	/**
	 * Adds a filemount to the users array of filemounts, $this->groupData['filemounts'][hash_key] = Array ('name'=>$name, 'path'=>$path, 'type'=>$type);
	 * Is a part of the authentication proces of the user.
	 * A final requirement for a path being mounted is that a) it MUST return TRUE on is_dir(), b) must contain either PATH_site+'fileadminDir' OR 'lockRootPath' - if lockRootPath is set - as first part of string!
	 * Paths in the mounted information will always be absolute and have a trailing slash.
	 *
	 * @param string $title Will be the (root)name of the filemount in the folder tree
	 * @param string $altTitle Will be the (root)name of the filemount IF $title is not TRUE (blank or zero)
	 * @param string $path Is the path which should be mounted. Will accept backslash in paths on windows servers (will substituted with forward slash). The path should be 1) relative to TYPO3_CONF_VARS[BE][fileadminDir] if $webspace is set, otherwise absolute.
	 * @param boolean $webspace If $webspace is set, the $path is relative to 'fileadminDir' in TYPO3_CONF_VARS, otherwise $path is absolute. 'fileadminDir' must be set to allow mounting of relative paths.
	 * @param string $type Type of filemount; Can be blank (regular) or "user" / "group" (for user and group filemounts presumably). Probably sets the icon first and foremost.
	 * @return boolean Returns "1" if the requested filemount was mounted, otherwise no return value.
	 * @deprecated since TYPO3 6.0, will be removed in TYPO3 6.1, all data is stored in $this->fileStorages now, see initializeFileStorages()
	 * @access private
	 * @todo Define visibility
	 */
	public function addFileMount($title, $altTitle, $path, $webspace, $type) {
		t3lib_div::logDeprecatedFunction();
		// Return FALSE if fileadminDir is not set and we try to mount a relative path
		if ($webspace && !$GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir']) {
			return FALSE;
		}
		// Trimming and pre-processing
		$path = trim($path);
		// with WINDOWS convert backslash to slash!!
		if ($this->OS == 'WIN') {
			$path = str_replace('\\', '/', $path);
		}
		// If the path is TRUE and validates as a valid path string:
		if ($path && t3lib_div::validPathStr($path)) {
			// normalize path: remove leading '/' and './', and trailing '/' and '/.'
			$path = trim($path);
			$path = preg_replace('#^\\.?/|/\\.?$#', '', $path);
			// There must be some chars in the path
			if ($path) {
				// Fileadmin dir, absolute
				$fdir = PATH_site . $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'];
				if ($webspace) {
					$path = $fdir . $path;
				} else {
					// With WINDOWS no prepending!!
					if ($this->OS != 'WIN') {
						// With WINDOWS no prepending!!
						$path = '/' . $path;
					}
				}
				$path .= '/';
				// We now have a path with slash after and slash before (if unix)
				if (@is_dir($path) && ($GLOBALS['TYPO3_CONF_VARS']['BE']['lockRootPath'] && t3lib_div::isFirstPartOfStr($path, $GLOBALS['TYPO3_CONF_VARS']['BE']['lockRootPath']) || t3lib_div::isFirstPartOfStr($path, $fdir))) {
					// Alternative title?
					$name = $title ? $title : $altTitle;
					// Adds the filemount. The same filemount with same name, type and path cannot be set up twice because of the hash string used as key.
					$this->groupData['filemounts'][md5($name . '|' . $path . '|' . $type)] = array('name' => $name, 'path' => $path, 'type' => $type);
					// Return TRUE - went well, success!
					return 1;
				}
			}
		}
	}

	/**
	 * Returns an array with the filemounts for the user. Each filemount is represented with an array of a "name", "path" and "type".
	 * If no filemounts an empty array is returned.
	 *
	 * @return array
	 * @deprecated since TYPO3 6.0, will be removed in TYPO3 6.1 as getFileStorages() should be the one to be used
	 * @todo Define visibility
	 */
	public function returnFilemounts() {
		t3lib_div::logDeprecatedFunction();
		// initialize the file storages in order to set some default settings in any time
		$this->getFileStorages();
		return $this->groupData['filemounts'];
	}

	/**
	 * Creates a TypoScript comment with the string text inside.
	 *
	 * @param string $str The text to wrap in comment prefixes and delimiters.
	 * @return string TypoScript comment with the string text inside.
	 * @todo Define visibility
	 */
	public function addTScomment($str) {
		$delimiter = '# ***********************************************';
		$out = $delimiter . LF;
		$lines = t3lib_div::trimExplode(LF, $str);
		foreach ($lines as $v) {
			$out .= '# ' . $v . LF;
		}
		$out .= $delimiter . LF;
		return $out;
	}

	/************************************
	 *
	 * Workspaces
	 *
	 ************************************/
	/**
	 * Initializing workspace.
	 * Called from within this function, see fetchGroupData()
	 *
	 * @return void
	 * @see fetchGroupData()
	 * @todo Define visibility
	 */
	public function workspaceInit() {
		// Initializing workspace by evaluating and setting the workspace, possibly updating it in the user record!
		$this->setWorkspace($this->user['workspace_id']);
		// Limiting the DB mountpoints if there any selected in the workspace record
		$this->initializeDbMountpointsInWorkspace();
		if ($allowed_languages = $this->getTSConfigVal('options.workspaces.allowed_languages.' . $this->workspace)) {
			$this->groupData['allowed_languages'] = $allowed_languages;
			$this->groupData['allowed_languages'] = t3lib_div::uniqueList($this->groupData['allowed_languages']);
		}
	}

	/**
	 * Limiting the DB mountpoints if there any selected in the workspace record
	 *
	 * @return void
	 */
	protected function initializeDbMountpointsInWorkspace() {
		$dbMountpoints = trim($this->workspaceRec['db_mountpoints']);
		if ($this->workspace > 0 && $dbMountpoints != '') {
			$filteredDbMountpoints = array();
			// Notice: We cannot call $this->getPagePermsClause(1); as usual because the group-list is not available at this point. But bypassing is fine because all we want here is check if the workspace mounts are inside the current webmounts rootline.
			// The actual permission checking on page level is done elsewhere as usual anyway before the page tree is rendered.
			$readPerms = '1=1';
			// Traverse mount points of the
			$dbMountpoints = t3lib_div::intExplode(',', $dbMountpoints);
			foreach ($dbMountpoints as $mpId) {
				if ($this->isInWebMount($mpId, $readPerms)) {
					$filteredDbMountpoints[] = $mpId;
				}
			}
			// Re-insert webmounts:
			$filteredDbMountpoints = array_unique($filteredDbMountpoints);
			$this->groupData['webmounts'] = implode(',', $filteredDbMountpoints);
		}
	}

	/**
	 * Adds more limitations for users who are no admins
	 * this was previously in workspaceInit but has now been moved to "
	 *
	 * @return void
	 */
	protected function initializeFileStoragesForWorkspace() {
		// Filtering the file mountpoints
		// if there some selected in the workspace record
		if ($this->workspace > 0) {
			$storageFiltersInWorkspace = trim($this->workspaceRec['file_mountpoints']);
			// no custom filemounts that should serve as filter or user is admin
			// so all user mountpoints are re-applied
			if (!$this->isAdmin() && $storageFiltersInWorkspace !== '') {
				// empty the fileStorages (will be re-applied later)
				$existingFileStoragesOfUser = $this->fileStorages;
				$this->fileStorages = array();
				$storageRepository = t3lib_div::makeInstance('\TYPO3\CMS\Core\Resource\StorageRepository');
				// Fetching all filemounts from the workspace
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_filemounts', 'deleted = 0 AND hidden = 0 AND pid = 0 AND uid IN (' . $GLOBALS['TYPO3_DB']->cleanIntList($storageFiltersInWorkspace) . ')');
				// add every filemount of this workspace record
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					// get the added entry, and check if it was in the users' original filemounts
					// if not, remove it from the new filemount list again
					// see self::addFileMount
					// TODO: check if the filter is narrowing down the existing user
					$storageObject = $storageRepository->findByUid($row['base']);
					if (isset($existingFileStoragesOfUser[$storageObject->getUid()])) {
						$storageObject->addFileMount($row['path']);
						$this->fileStorages[$storageObject->getUid()] = $storageObject;
					}
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
			}
		}
	}

	/**
	 * Checking if a workspace is allowed for backend user
	 *
	 * @param mixed $wsRec If integer, workspace record is looked up, if array it is seen as a Workspace record with at least uid, title, members and adminusers columns. Can be faked for workspaces uid 0 and -1 (online and offline)
	 * @param string $fields List of fields to select. Default fields are: uid,title,adminusers,members,reviewers,publish_access,stagechg_notification
	 * @return array TRUE if access. Output will also show how access was granted. Admin users will have a true output regardless of input.
	 * @todo Define visibility
	 */
	public function checkWorkspace($wsRec, $fields = 'uid,title,adminusers,members,reviewers,publish_access,stagechg_notification') {
		$retVal = FALSE;
		// If not array, look up workspace record:
		if (!is_array($wsRec)) {
			switch ((string) $wsRec) {
			case '0':
				$wsRec = array('uid' => $wsRec);
				break;
			default:
				if (t3lib_extMgm::isLoaded('workspaces')) {
					$wsRec = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow($fields, 'sys_workspace', 'pid=0 AND uid=' . intval($wsRec) . t3lib_BEfunc::deleteClause('sys_workspace'), '', 'title');
				}
				break;
			}
		}
		// If wsRec is set to an array, evaluate it:
		if (is_array($wsRec)) {
			if ($this->isAdmin()) {
				return array_merge($wsRec, array('_ACCESS' => 'admin'));
			} else {
				switch ((string) $wsRec['uid']) {
				case '0':
					$retVal = $this->groupData['workspace_perms'] & 1 ? array_merge($wsRec, array('_ACCESS' => 'online')) : FALSE;
					break;
				default:
					// Checking if the guy is admin:
					if (t3lib_div::inList($wsRec['adminusers'], 'be_users_' . $this->user['uid'])) {
						return array_merge($wsRec, array('_ACCESS' => 'owner'));
					}
					// Checking if he is owner through a user group of his:
					foreach ($this->userGroupsUID as $groupUid) {
						if (t3lib_div::inList($wsRec['adminusers'], 'be_groups_' . $groupUid)) {
							return array_merge($wsRec, array('_ACCESS' => 'owner'));
						}
					}
					// Checking if he is reviewer user:
					if (t3lib_div::inList($wsRec['reviewers'], 'be_users_' . $this->user['uid'])) {
						return array_merge($wsRec, array('_ACCESS' => 'reviewer'));
					}
					// Checking if he is reviewer through a user group of his:
					foreach ($this->userGroupsUID as $groupUid) {
						if (t3lib_div::inList($wsRec['reviewers'], 'be_groups_' . $groupUid)) {
							return array_merge($wsRec, array('_ACCESS' => 'reviewer'));
						}
					}
					// Checking if he is member as user:
					if (t3lib_div::inList($wsRec['members'], 'be_users_' . $this->user['uid'])) {
						return array_merge($wsRec, array('_ACCESS' => 'member'));
					}
					// Checking if he is member through a user group of his:
					foreach ($this->userGroupsUID as $groupUid) {
						if (t3lib_div::inList($wsRec['members'], 'be_groups_' . $groupUid)) {
							return array_merge($wsRec, array('_ACCESS' => 'member'));
						}
					}
					break;
				}
			}
		}
		return $retVal;
	}

	/**
	 * Uses checkWorkspace() to check if current workspace is available for user. This function caches the result and so can be called many times with no performance loss.
	 *
	 * @return array See checkWorkspace()
	 * @see checkWorkspace()
	 * @todo Define visibility
	 */
	public function checkWorkspaceCurrent() {
		if (!isset($this->checkWorkspaceCurrent_cache)) {
			$this->checkWorkspaceCurrent_cache = $this->checkWorkspace($this->workspace);
		}
		return $this->checkWorkspaceCurrent_cache;
	}

	/**
	 * Setting workspace ID
	 *
	 * @param integer $workspaceId ID of workspace to set for backend user. If not valid the default workspace for BE user is found and set.
	 * @return void
	 * @todo Define visibility
	 */
	public function setWorkspace($workspaceId) {
		// Check workspace validity and if not found, revert to default workspace.
		if ($this->workspaceRec = $this->checkWorkspace($workspaceId, '*')) {
			// Set workspace ID internally
			$this->workspace = (int) $workspaceId;
		} else {
			$this->workspace = (int) $this->getDefaultWorkspace();
			$this->workspaceRec = $this->checkWorkspace($this->workspace, '*');
		}
		// Unset access cache:
		unset($this->checkWorkspaceCurrent_cache);
		// If ID is different from the stored one, change it:
		if (strcmp($this->workspace, $this->user['workspace_id'])) {
			$this->user['workspace_id'] = $this->workspace;
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('be_users', 'uid=' . intval($this->user['uid']), array('workspace_id' => $this->user['workspace_id']));
			$this->simplelog('User changed workspace to "' . $this->workspace . '"');
		}
	}

	/**
	 * Setting workspace preview state for user:
	 *
	 * @param boolean $previewState State of user preview.
	 * @return void
	 * @todo Define visibility
	 */
	public function setWorkspacePreview($previewState) {
		$this->user['workspace_preview'] = $previewState;
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery('be_users', 'uid=' . intval($this->user['uid']), array('workspace_preview' => $this->user['workspace_preview']));
	}

	/**
	 * Return default workspace ID for user,
	 * If EXT:workspaces is not installed the user will be pushed the the
	 * Live workspace
	 *
	 * @return integer Default workspace id. If no workspace is available it will be "-99
	 * @todo Define visibility
	 */
	public function getDefaultWorkspace() {
		$defaultWorkspace = -99;
		if (!t3lib_extMgm::isLoaded('workspaces') || $this->checkWorkspace(0)) {
			// Check online
			$defaultWorkspace = 0;
		} elseif ($this->checkWorkspace(-1)) {
			// Check offline
			$defaultWorkspace = -1;
		} elseif (t3lib_extMgm::isLoaded('workspaces')) {
			// Traverse custom workspaces:
			$workspaces = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,title,adminusers,members,reviewers', 'sys_workspace', 'pid=0' . t3lib_BEfunc::deleteClause('sys_workspace'), '', 'title');
			foreach ($workspaces as $rec) {
				if ($this->checkWorkspace($rec)) {
					$defaultWorkspace = $rec['uid'];
					break;
				}
			}
		}
		return $defaultWorkspace;
	}

	/************************************
	 *
	 * Logging
	 *
	 ************************************/
	/**
	 * Writes an entry in the logfile/table
	 * Documentation in "TYPO3 Core API"
	 *
	 * @param integer $type Denotes which module that has submitted the entry. See "TYPO3 Core API". Use "4" for extensions.
	 * @param integer $action Denotes which specific operation that wrote the entry. Use "0" when no sub-categorizing applies
	 * @param integer $error Flag. 0 = message, 1 = error (user problem), 2 = System Error (which should not happen), 3 = security notice (admin)
	 * @param integer $details_nr The message number. Specific for each $type and $action. This will make it possible to translate errormessages to other languages
	 * @param string $details Default text that follows the message (in english!). Possibly translated by identification through type/action/details_nr
	 * @param array $data Data that follows the log. Might be used to carry special information. If an array the first 5 entries (0-4) will be sprintf'ed with the details-text
	 * @param string $tablename Table name. Special field used by tce_main.php.
	 * @param integer $recuid Record UID. Special field used by tce_main.php.
	 * @param integer $recpid Record PID. Special field used by tce_main.php. OBSOLETE
	 * @param integer $event_pid The page_uid (pid) where the event occurred. Used to select log-content for specific pages.
	 * @param string $NEWid Special field used by tce_main.php. NEWid string of newly created records.
	 * @param integer $userId Alternative Backend User ID (used for logging login actions where this is not yet known).
	 * @return integer Log entry ID.
	 * @todo Define visibility
	 */
	public function writelog($type, $action, $error, $details_nr, $details, $data, $tablename = '', $recuid = '', $recpid = '', $event_pid = -1, $NEWid = '', $userId = 0) {
		$fields_values = array(
			'userid' => $userId ? $userId : intval($this->user['uid']),
			'type' => intval($type),
			'action' => intval($action),
			'error' => intval($error),
			'details_nr' => intval($details_nr),
			'details' => $details,
			'log_data' => serialize($data),
			'tablename' => $tablename,
			'recuid' => intval($recuid),
			'IP' => t3lib_div::getIndpEnv('REMOTE_ADDR'),
			'tstamp' => $GLOBALS['EXEC_TIME'],
			'event_pid' => intval($event_pid),
			'NEWid' => $NEWid,
			'workspace' => $this->workspace
		);
		$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_log', $fields_values);
		return $GLOBALS['TYPO3_DB']->sql_insert_id();
	}

	/**
	 * Simple logging function
	 *
	 * @param string $message Log message
	 * @param string $extKey Option extension key / module name
	 * @param integer $error Error level. 0 = message, 1 = error (user problem), 2 = System Error (which should not happen), 3 = security notice (admin)
	 * @return integer Log entry UID
	 * @todo Define visibility
	 */
	public function simplelog($message, $extKey = '', $error = 0) {
		return $this->writelog(4, 0, $error, 0, ($extKey ? '[' . $extKey . '] ' : '') . $message, array());
	}

	/**
	 * Sends a warning to $email if there has been a certain amount of failed logins during a period.
	 * If a login fails, this function is called. It will look up the sys_log to see if there has been more than $max failed logins the last $secondsBack seconds (default 3600). If so, an email with a warning is sent to $email.
	 *
	 * @param string $email Email address
	 * @param integer $secondsBack Number of sections back in time to check. This is a kind of limit for how many failures an hour for instance.
	 * @param integer $max Max allowed failures before a warning mail is sent
	 * @return void
	 * @access private
	 * @todo Define visibility
	 */
	public function checkLogFailures($email, $secondsBack = 3600, $max = 3) {
		if ($email) {
			// Get last flag set in the log for sending
			$theTimeBack = $GLOBALS['EXEC_TIME'] - $secondsBack;
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tstamp', 'sys_log', 'type=255 AND action=4 AND tstamp>' . intval($theTimeBack), '', 'tstamp DESC', '1');
			if ($testRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$theTimeBack = $testRow['tstamp'];
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
			// Check for more than $max number of error failures with the last period.
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_log', 'type=255 AND action=3 AND error<>0 AND tstamp>' . intval($theTimeBack), '', 'tstamp');
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) > $max) {
				// OK, so there were more than the max allowed number of login failures - so we will send an email then.
				$subject = 'TYPO3 Login Failure Warning (at ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . ')';
				$email_body = 'There have been some attempts (' . $GLOBALS['TYPO3_DB']->sql_num_rows($res) . ') to login at the TYPO3
site "' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . '" (' . t3lib_div::getIndpEnv('HTTP_HOST') . ').

This is a dump of the failures:

';
				while ($testRows = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					$theData = unserialize($testRows['log_data']);
					$email_body .= date(($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] . ' ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm']), $testRows['tstamp']) . ':  ' . @sprintf($testRows['details'], ('' . $theData[0]), ('' . $theData[1]), ('' . $theData[2]));
					$email_body .= LF;
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
				$from = t3lib_utility_Mail::getSystemFrom();
				/** @var $mail t3lib_mail_Message */
				$mail = t3lib_div::makeInstance('t3lib_mail_Message');
				$mail->setTo($email)->setFrom($from)->setSubject($subject)->setBody($email_body);
				$mail->send();
				// Logout written to log
				$this->writelog(255, 4, 0, 3, 'Failure warning (%s failures within %s seconds) sent by email to %s', array($GLOBALS['TYPO3_DB']->sql_num_rows($res), $secondsBack, $email));
			}
		}
	}

}

?>