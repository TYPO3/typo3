<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Jeff Segars <jeff@webempoweredchurch.org>
*  (c) 2008 David Slayback <dave@webempoweredchurch.org>
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
 * Controller class for frontend editing.
 *
 * $Id$
 *
 * @author	Jeff Segars <jeff@webempoweredchurch.org>
 * @author	David Slayback <dave@webempoweredchurch.org>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_frontendedit {

	/**
	 * TCEmain object.
	 *
	 * @var t3lib_tcemain
	 */
	protected $tce;


	/**
	 * Force preview?
	 *
	 * @var boolean
	 */
	protected $ext_forcePreview = false;

	/**
	 * Comma separated list of page UIDs to be published.
	 *
	 * @var	string
	 */
	protected $extPublishList = '';

	/**
	 * Creates and initializes the TCEmain object.
	 *
	 * @return	void
	 */
	public function __construct() {
		$this->tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$this->tce->stripslashes_values=0;
	}

	/**
	 * Initializes configuration options.
	 *
	 * @return	void
	 */
	public function initConfigOptions() {
		$this->saveConfigOptions();
		$this->TSFE_EDIT = t3lib_div::_POST('TSFE_EDIT');

			// Setting some values based on the admin panel
		$GLOBALS['TSFE']->forceTemplateParsing = $this->extGetFeAdminValue('tsdebug', 'forceTemplateParsing');
		$GLOBALS['TSFE']->displayEditIcons = $this->extGetFeAdminValue('edit', 'displayIcons');
		$GLOBALS['TSFE']->displayFieldEditIcons = $this->extGetFeAdminValue('edit', 'displayFieldIcons');

		if ($this->extGetFeAdminValue('tsdebug', 'displayQueries')) {
			if ($GLOBALS['TYPO3_DB']->explainOutput == 0) {		// do not override if the value is already set in t3lib_db
					// Enable execution of EXPLAIN SELECT queries
				$GLOBALS['TYPO3_DB']->explainOutput = 3;
			}
		}

		if (t3lib_div::_GP('ADMCMD_editIcons')) {
			$GLOBALS['TSFE']->displayFieldEditIcons=1;
			$GLOBALS['BE_USER']->uc['TSFE_adminConfig']['edit_editNoPopup']=1;
		}

		if (t3lib_div::_GP('ADMCMD_simUser')) {
			$GLOBALS['BE_USER']->uc['TSFE_adminConfig']['preview_simulateUserGroup']=intval(t3lib_div::_GP('ADMCMD_simUser'));
			$this->ext_forcePreview = true;
		}

		if (t3lib_div::_GP('ADMCMD_simTime')) {
			$GLOBALS['BE_USER']->uc['TSFE_adminConfig']['preview_simulateDate']=intval(t3lib_div::_GP('ADMCMD_simTime'));
			$this->ext_forcePreview = true;
		}

			// Include classes for editing IF editing module in Admin Panel is open
		if (($this->isAdminModuleEnabled('edit') && $this->isAdminModuleOpen('edit')) || $GLOBALS['TSFE']->displayEditIcons == 1) {
			$GLOBALS['TSFE']->includeTCA();
			if ($this->isEditAction()) {
				require_once (PATH_t3lib . 'class.t3lib_tcemain.php');
				$this->editAction();
			}

			if ($this->isEditFormShown()) {
				require_once(PATH_t3lib . 'class.t3lib_tceforms.php');
				require_once(PATH_t3lib . 'class.t3lib_iconworks.php');
				require_once(PATH_t3lib . 'class.t3lib_loaddbgroup.php');
				require_once(PATH_t3lib . 'class.t3lib_transferdata.php');
			}
		}

		if ($GLOBALS['TSFE']->forceTemplateParsing || $GLOBALS['TSFE']->displayEditIcons || $GLOBALS['TSFE']->displayFieldEditIcons) {
			$GLOBALS['TSFE']->set_no_cache();
		}
	}


	/**
	 * Delegates to the appropriate view and renders the admin panel content.
	 *
	 * @return	string.
	 */
	public function displayAdmin() {
		$content = '';
		$adminClass = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/classes/class.frontendedit.php']['admin'];
		if ($adminClass && !$GLOBALS['BE_USER']->extAdminConfig['hide'] && $GLOBALS['TSFE']->config['config']['admPanel']) {
			$admin = &t3lib_div::getUserObj($adminClass);
			if (is_object($admin)) {
				$content =  $admin->display();
			}
		}

		return $content;
	}

	/**
	 * Generates the "edit panels" which can be shown for a page or records on a page when the Admin Panel is enabled for a backend users surfing the frontend.
	 * With the "edit panel" the user will see buttons with links to editing, moving, hiding, deleting the element
	 * This function is used for the cObject EDITPANEL and the stdWrap property ".editPanel"
	 *
	 * @param	string		A content string containing the content related to the edit panel. For cObject "EDITPANEL" this is empty but not so for the stdWrap property. The edit panel is appended to this string and returned.
	 * @param	array		TypoScript configuration properties for the editPanel
	 * @param	string		The "table:uid" of the record being shown. If empty string then $this->currentRecord is used. For new records (set by $conf['newRecordFromTable']) it's auto-generated to "[tablename]:NEW"
	 * @param	array		Alternative data array to use. Default is $this->data
	 * @return	string		The input content string with the editPanel appended. This function returns only an edit panel appended to the content string if a backend user is logged in (and has the correct permissions). Otherwise the content string is directly returned.
	 * @link	http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=375&cHash=7d8915d508
	 */
	public function displayEditPanel($content, array $conf, $currentRecord, array $dataArray) {
		if ($conf['newRecordFromTable']) {
			$currentRecord = $conf['newRecordFromTable'] . ':NEW';
			$conf['allow'] = 'new';
		}

		list($table, $uid) = explode(':', $currentRecord);

			// Page ID for new records, 0 if not specified
		$newRecordPid = intval($conf['newRecordInPid']);
		if (!$conf['onlyCurrentPid'] || $dataArray['pid'] == $GLOBALS['TSFE']->id) {
			if ($table=='pages') {
				$newUid = $uid;
			} else {
				if ($conf['newRecordFromTable']) {
					$newUid = $GLOBALS['TSFE']->id;
					if ($newRecordPid) {
						 $newUid = $newRecordPid;
					}
				} else {
					$newUid = -1 * $uid;
				}
			}
		}

		if ($GLOBALS['TSFE']->displayEditIcons && $table && $this->allowedToEdit($table, $dataArray, $conf) && $this->allowedToEditLanguage($table, $dataArray)) {
			$editClass = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/classes/class.frontendedit.php']['edit'];
			if ($editClass) {
				$edit = &t3lib_div::getUserObj($editClass, false);
				if (is_object($edit)) {
					$allowedActions = $this->getAllowedEditActions($table, $conf, $dataArray['pid']);
					$content = $edit->editPanel($content, $conf, $currentRecord, $dataArray, $table, $allowedActions, $newUid, $this->getHiddenFields($dataArray));
				}
			}
		}

		return $content;
	}

	/**
	 * Adds an edit icon to the content string. The edit icon links to alt_doc.php with proper parameters for editing the table/fields of the context.
	 * This implements TYPO3 context sensitive editing facilities. Only backend users will have access (if properly configured as well).
	 *
	 * @param	string		The content to which the edit icons should be appended
	 * @param	string		The parameters defining which table and fields to edit. Syntax is [tablename]:[fieldname],[fieldname],[fieldname],... OR [fieldname],[fieldname],[fieldname],... (basically "[tablename]:" is optional, default table is the one of the "current record" used in the function). The fieldlist is sent as "&columnsOnly=" parameter to alt_doc.php
	 * @param	array		TypoScript properties for configuring the edit icons.
	 * @param	string		The "table:uid" of the record being shown. If empty string then $this->currentRecord is used. For new records (set by $conf['newRecordFromTable']) it's auto-generated to "[tablename]:NEW"
	 * @param	array		Alternative data array to use. Default is $this->data
	 * @param	string		Additional URL parameters for the link pointing to alt_doc.php
	 * @return	string		The input content string, possibly with edit icons added (not necessarily in the end but just after the last string of normal content.
	 */

	public function displayEditIcons($content, $params, array $conf=array(), $currentRecord = '', array $dataArray = array(), $addUrlParamStr = '') {
			// Check incoming params:
		list($currentRecordTable, $currentRecordUID) = explode(':', $currentRecord);
		list($fieldList, $table) = array_reverse(t3lib_div::trimExplode(':', $params, 1)); // Reverse the array because table is optional
		if (!$table) {
			$table = $currentRecordTable;
		} elseif ($table != $currentRecordTable) {
				return $content;	// If the table is set as the first parameter, and does not match the table of the current record, then just return.
		}

		$editUid = $dataArray['_LOCALIZED_UID'] ? $dataArray['_LOCALIZED_UID'] : $currentRecordUID;

			// Edit icons imply that the editing action is generally allowed, assuming page and content element permissions permit it.
		if (!array_key_exists('allow', $conf)) {
			$conf['allow'] = 'edit';
		}

		if ($GLOBALS['TSFE']->displayFieldEditIcons && $table && $this->allowedToEdit($table, $dataArray, $conf) && $fieldList && $this->allowedToEditLanguage($table, $dataArray)) {
			$editClass = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/classes/class.frontendedit.php']['edit'];
			if ($editClass) {
				$edit = &t3lib_div::getUserObj($editClass);
				if (is_object($edit)) {
					$content = $edit->editIcons($content, $params, $conf, $currentRecord, $dataArray, $addURLParamStr, $table, $editUid, $fieldList);
				}
			}
		}

		return $content;
	}

	/**
	 * Checks if a Admin Panel section ("module") is available for the user. If so, true is returned.
	 *
	 * @param	string		The module key, eg. "edit", "preview", "info" etc.
	 * @return	boolean
	 */
	public function isAdminModuleEnabled($key) {
			// Returns true if the module checked is "preview" and the forcePreview flag is set.
		if ($key=='preview' && $this->ext_forcePreview) {
			return true;
		}

			// If key is not set, only "all" is checked
		if ($GLOBALS['BE_USER']->extAdminConfig['enable.']['all']) {
			return true;
		}

		if ($GLOBALS['BE_USER']->extAdminConfig['enable.'][$key]) {
			return true;
		}
	}

	/**
	 * Saves any change in settings made in the Admin Panel.
	 * Called from index_ts.php right after access check for the Admin Panel
	 *
	 * @return	void
	 */
	public function saveConfigOptions() {
		$input = t3lib_div::_GP('TSFE_ADMIN_PANEL');
		if (is_array($input)) {
				// Setting
			$GLOBALS['BE_USER']->uc['TSFE_adminConfig'] = array_merge(!is_array($GLOBALS['BE_USER']->uc['TSFE_adminConfig']) ? array() : $GLOBALS['BE_USER']->uc['TSFE_adminConfig'], $input);			// Candidate for t3lib_div::array_merge() if integer-keys will some day make trouble...
			unset($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['action']);

				// Actions:
			if ($input['action']['clearCache'] && $this->isAdminModuleEnabled('cache')) {
				$GLOBALS['BE_USER']->extPageInTreeInfo=array();
				$theStartId = intval($input['cache_clearCacheId']);
				$GLOBALS['TSFE']->clearPageCacheContent_pidList($GLOBALS['BE_USER']->extGetTreeList($theStartId, $this->extGetFeAdminValue('cache', 'clearCacheLevels'), 0, $GLOBALS['BE_USER']->getPagePermsClause(1)) . $theStartId);
			}
			if ($input['action']['publish'] && $this->isAdminModuleEnabled('publish')) {
				$theStartId = intval($input['publish_id']);
				$this->extPublishList = $GLOBALS['BE_USER']->extGetTreeList($theStartId, $this->extGetFeAdminValue('publish', 'levels'), 0, $GLOBALS['BE_USER']->getPagePermsClause(1)) . $theStartId;
			}

				// Saving
			$GLOBALS['BE_USER']->writeUC();
		}
		$GLOBALS['TT']->LR = $this->extGetFeAdminValue('tsdebug', 'LR');

		if ($this->extGetFeAdminValue('cache', 'noCache')) {
			$GLOBALS['TSFE']->set_no_cache();
		}

			// Hook for post processing the frontend admin configuration. Added with TYPO3 4.2, so naming is now incorrect but preserves compatibility.
			// @deprecated	since TYPO3 4.3
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tsfebeuserauth.php']['extSaveFeAdminConfig-postProc'])) {
			$_params = array('input' => &$input, 'pObj' => &$this);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tsfebeuserauth.php']['extSaveFeAdminConfig-postProc'] as $_funcRef) {
				t3lib_div::callUserFunction($_funcRef, $_params, $this);
			}
		}
	}

	/**
	 * Returns the value for a Admin Panel setting. You must specify both the module-key and the internal setting key.
	 *
	 * @param	string		Module key
	 * @param	string		Setting key
	 * @return	string		The setting value
	 */
	public function extGetFeAdminValue($pre, $val='') {
			// Check if module is enabled.
		if ($this->isAdminModuleEnabled($pre)) {
				// Exceptions where the values can be overridden from backend:
				// deprecated
			if ($pre . '_' . $val == 'edit_displayIcons' && $GLOBALS['BE_USER']->extAdminConfig['module.']['edit.']['forceDisplayIcons']) {
				return true;
			}
			if ($pre . '_' . $val == 'edit_displayFieldIcons' && $GLOBALS['BE_USER']->extAdminConfig['module.']['edit.']['forceDisplayFieldIcons']) {
				return true;
			}

				// override all settings with user TSconfig
			if ($GLOBALS['BE_USER']->extAdminConfig['override.'][$pre . '.'][$val] && $val) {
				return $GLOBALS['BE_USER']->extAdminConfig['override.'][$pre . '.'][$val];
			}
			if ($GLOBALS['BE_USER']->extAdminConfig['override.'][$pre]) {
				return $GLOBALS['BE_USER']->extAdminConfig['override.'][$pre];
			}

			$retVal = $val ? $GLOBALS['BE_USER']->uc['TSFE_adminConfig'][$pre . '_' . $val] : 1;

			if ($pre=='preview' && $this->ext_forcePreview) {
				if (!$val) {
					return true;
				} else {
					return $retVal;
				}
			}
				// regular check:
			if ($this->isAdminModuleOpen($pre)) {	// See if the menu is expanded!
				return $retVal;
			}

				// Hook for post processing the frontend admin configuration. Added with TYPO3 4.2, so naming is now incorrect but preserves compatibility.
				// @deprecated	since TYPO3 4.3
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tsfebeuserauth.php']['extEditAction-postProc'])) {
				$_params = array('cmd' => &$cmd, 'tce' => &$this->tce, 'pObj' => &$this);
				foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tsfebeuserauth.php']['extEditAction-postProc'] as $_funcRef) {
					t3lib_div::callUserFunction($_funcRef, $_params, $this);
				}
			}
		}
	}

	/**
	 * Enables the force preview option.
	 *
	 * @return	void
	 */
	public function forcePreview() {
		$this->ext_forcePreview = true;
	}

	/**
	 * Returns the comma-separated list of page UIDs to be published.
	 *
	 * @return	string
	 */
	public function getExtPublishList() {
		return $this->extPublishList;
	}

	/**
	 * Returns true if admin panel module is open
	 *
	 * @param	string		Module key
	 * @return	boolean		True, if the admin panel is open for the specified admin panel module key.
	 */
	public function isAdminModuleOpen($pre) {
		return $GLOBALS['BE_USER']->uc['TSFE_adminConfig']['display_top'] && $GLOBALS['BE_USER']->uc['TSFE_adminConfig']['display_' . $pre];
	}

	/*****************************************************
	 *
	 * Frontend Editing
	 *
	 ****************************************************/

	/**
	 * Returns true if an edit-action is sent from the Admin Panel
	 *
	 * @return	boolean
	 * @see	index_ts.php
	 */
	public function isEditAction() {
		if (is_array($this->TSFE_EDIT)) {
			if ($this->TSFE_EDIT['cancel']) {
				unset($this->TSFE_EDIT['cmd']);
			} else {
				$cmd = (string) $this->TSFE_EDIT['cmd'];
				if (($cmd != 'edit' || (is_array($this->TSFE_EDIT['data']) && ($this->TSFE_EDIT['doSave'] || $this->TSFE_EDIT['update'] || $this->TSFE_EDIT['update_close']))) && $cmd != 'new') {
						// $cmd can be a command like "hide" or "move". If $cmd is "edit" or "new" it's an indication to show the formfields. But if data is sent with update-flag then $cmd = edit is accepted because edit may be sent because of .keepGoing flag.
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Returns true if an edit form is shown on the page.
	 * Used from index_ts.php where a true return-value will result in classes etc. being included.
	 *
	 * @return	boolean
	 * @see	index_ts.php
	 */
	public function isEditFormShown() {
		if (is_array($this->TSFE_EDIT)) {
			$cmd = (string) $this->TSFE_EDIT['cmd'];
			if ($cmd == 'edit' || $cmd == 'new') {
				return true;
			}
		}
	}

	/**
	 * Management of the on-page frontend editing forms and edit panels.
	 * Basically taking in the data and commands and passes them on to the proper classes as they should be.
	 *
	 * @return	void
	 * @throws UnexpectedValueException if TSFE_EDIT[cmd] is not a valid command
	 * @see	index_ts.php
	 */
	public function editAction() {
			// Commands:
		list($table, $uid) = explode(':', $this->TSFE_EDIT['record']);
		$cmd = $this->TSFE_EDIT['cmd'];
		
			// Look for some TSFE_EDIT data that indicates we should save.
		if (($this->TSFE_EDIT['doSave'] || $this->TSFE_EDIT['update'] || $this->TSFE_EDIT['update_close']) && is_array($this->TSFE_EDIT['data'])) {
			$cmd = 'save';
		}

		if (($cmd == 'save') || ($cmd && $table && $uid && isset($GLOBALS['TCA'][$table]))) {
				// Hook for defining custom editing actions. Naming is incorrect, but preserves compatibility.
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tsfebeuserauth.php']['extEditAction'])) {
				$_params = array();
				foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tsfebeuserauth.php']['extEditAction'] as $_funcRef) {
					t3lib_div::callUserFunction($_funcRef, $_params, $this);
				}
			}

				// Perform the requested editing command.
			$cmdAction = 'do' . ucwords($cmd);
			if (is_callable(array($this, $cmdAction))) {
				$this->$cmdAction($table, $uid);
			} else {
				throw new UnexpectedValueException(
					'The specified frontend edit command (' . $cmd . ') is not valid.',
					1225818120
				);
			}
		}
	}
	
	/**
	 * Hides a specific record.
	 *
	 * @param	string		The table name for the record to hide.
	 * @param	integer		The UID for the record to hide.
	 * @return	void
	 */
	public function doHide($table, $uid) {
		$hideField = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'];
		if ($hideField) {
			$recData = array();
			$recData[$table][$uid][$hideField] = 1;
			$this->tce->start($recData, array());
			$this->tce->process_datamap();
		}
	}

	/**
	 * Unhides (shows) a specific record.
	 *
	 * @param	string		The table name for the record to unhide.
	 * @param	integer		The UID for the record to unhide.
	 * @return	void
	 */
	public function doUnhide($table, $uid) {
		$hideField = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'];
		if ($hideField) {
			$recData = array();
			$recData[$table][$uid][$hideField] = 0;
			$this->tce->start($recData, array());
			$this->tce->process_datamap();
		}
	}

	/**
	 * Moves a record up.
	 *
	 * @param	string		The table name for the record to move.
	 * @param	integer		The UID for the record to hide.
	 * @return	void
	 */
	public function doUp($table, $uid) {
		$this->move($table, $uid, 'up');
	}

	/**
	 * Moves a record down.
	 *
	 * @param	string		The table name for the record to move.
	 * @param	integer		The UID for the record to move.
	 * @return	void
	 */
	public function doDown($table, $uid) {
		$this->move($table, $uid, 'down');
	}

	/**
	 * Moves a record after a given element. Used for drag.
	 *
	 * @param	string		The table name for the record to move.
	 * @param	integer		The UID for the record to move.
	 * @return	void
	 */
	public function doMoveAfter($table, $uid) {
		$afterUID = $GLOBALS['BE_USER']->frontendEdit->TSFE_EDIT['moveAfter'];
		$this->move($table, $uid, '', $afterUID);
	}

	/**
	 * Moves a record
	 *
	 * @param	string		The table name for the record to move.
	 * @param	integer		The UID for the record to move.
	 * @param	string		The direction to move, either 'up' or 'down'. 
	 * @param	integer		The UID of record to move after. This is specified for dragging only.
	 * @return	void
	 */
	protected function move($table, $uid, $direction='', $afterUID=0) {
		$cmdData = array();
		$sortField = $GLOBALS['TCA'][$table]['ctrl']['sortby'];
		if ($sortField) {
				// Get self:
			$fields = array_unique(t3lib_div::trimExplode(',', $GLOBALS['TCA'][$table]['ctrl']['copyAfterDuplFields'] . ',uid,pid,' . $sortField, true));
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(implode(',', $fields), $table, 'uid=' . $uid);
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					// record before or after
				$preview = $this->extGetFeAdminValue('preview');
				$copyAfterFieldsQuery = '';
				if ($preview) {
					$ignore = array('starttime'=>1, 'endtime'=>1, 'disabled'=>1, 'fe_group'=>1);
				}
				if ($GLOBALS['TCA'][$table]['ctrl']['copyAfterDuplFields']) {
					$cAFields = t3lib_div::trimExplode(',', $GLOBALS['TCA'][$table]['ctrl']['copyAfterDuplFields'], true);
					foreach($cAFields as $fieldName) {
						$copyAfterFieldsQuery .= ' AND ' . $fieldName . '="' . $row[$fieldName] . '"';
					}
				}
				if (!empty($direction)) {
					if ($direction == 'up') {
						$operator = '<';
						$order = 'DESC';
					} else {
						$operator = '>';
						$order = 'ASC';
					}			
					$sortCheck = ' AND ' . $sortField . $operator . intval($row[$sortField]);
				}
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
							'uid,pid',
							$table,
							'pid=' . intval($row['pid']) .
								$sortCheck .
								$copyAfterFieldsQuery .
								$GLOBALS['TSFE']->sys_page->enableFields($table, '', $ignore),
							'',
							$sortField . ' ' . $order,
							'2'
						);
				if ($row2 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					if ($afterUID) {
						$cmdData[$table][$uid]['move'] = -$afterUID;
					}
					elseif ($direction == 'down') {
						$cmdData[$table][$uid]['move'] = -$row2['uid'];
					} 
					elseif ($row3 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {	// Must take the second record above...
						$cmdData[$table][$uid]['move'] = -$row3['uid'];
					}
					else {	// ... and if that does not exist, use pid
						$cmdData[$table][$uid]['move'] = $row['pid'];
					}
				} elseif ($direction == 'up') {
					$cmdData[$table][$uid]['move'] = $row['pid'];
				}
			}
			if (!empty($cmdData)) {
				$this->tce->start(array(), $cmdData);
				$this->tce->process_cmdmap();
			}
		}
	}

	/**
	 * Deletes a specific record.
	 *
	 * @param	string		The table name for the record to delete.
	 * @param	integer		The UID for the record to delete.
	 * @return	void
	 */
	public function doDelete($table, $uid) {
		$cmdData[$table][$uid]['delete'] = 1;
		if (count($cmdData)) {
			$this->tce->start(array(), $cmdData);
			$this->tce->process_cmdmap();
		}
	}

	/**
	 * Saves a record based on its data array.
	 *
	 * @param	string		The table name for the record to save.
	 * @param	integer		The UID for the record to save.
	 * @return	void
	 */
	public function doSave($table, $uid) {
		$data = $this->TSFE_EDIT['data'];

		if (!empty($data)) {
			$this->tce->start($data, array());
			$this->tce->process_uploads($_FILES);
			$this->tce->process_datamap();

				// Save the new UID back into TSFE_EDIT
			$newUID = $this->tce->substNEWwithIDs['NEW'];
			if ($newUID) {
				$GLOBALS['BE_USER']->frontendEdit->TSFE_EDIT['newUID'] = $newUID;
			}
		}
	}
	
	/**
	 * Stub for closing a record. No real functionality needed since content
	 * element rendering will take care of everything.
	 *
	 * @param	string		The table name for the record to close.
	 * @param	integer		The UID for the record to close.
	 * @return	void
	 */
	public function doClose($table, $uid) {
		// Do nothing.
	}

	/**
	 * Checks whether the user has access to edit the language for the
	 * requested record.
	 *
	 * @param	string		The name of the table.
	 * @param	array		The record.
	 * @return	boolean
	 */
	protected function allowedToEditLanguage($table, array $currentRecord) {
			// If no access right to record languages, return immediately
		if ($table === 'pages') {
			$lang = $GLOBALS['TSFE']->sys_language_uid;
		} elseif ($table === 'tt_content') {
			$lang = $GLOBALS['TSFE']->sys_language_content;
		} elseif ($GLOBALS['TCA'][$table]['ctrl']['languageField']) {
			$lang = $currentRecord[$GLOBALS['TCA'][$table]['ctrl']['languageField']];
		} else {
			$lang = -1;
		}

		if ($GLOBALS['BE_USER']->checkLanguageAccess($lang)) {
			$languageAccess = true;
		} else {
			$languageAccess = false;
		}

		return $languageAccess;
	}

	/**
	 * Checks whether the user is allowed to edit the requested table.
	 *
	 * @param	string	The name of the table.
	 * @param	array	The data array.
	 * @param	array	The configuration array for the edit panel.
	 * @return	boolean
	 */
	protected function allowedToEdit($table, array $dataArray, array $conf) {

			// Unless permissions specifically allow it, editing is not allowed.
		$mayEdit = false;

		if ($table=='pages') {
				// 2 = permission to edit the page
			if ($GLOBALS['BE_USER']->isAdmin() || $GLOBALS['BE_USER']->doesUserHaveAccess($dataArray, 2)) {
				$mayEdit = true;
			}
		} else {
				// 16 = permission to edit content on the page
			if ($GLOBALS['BE_USER']->isAdmin() || $GLOBALS['BE_USER']->doesUserHaveAccess(t3lib_BEfunc::getRecord('pages', $dataArray['pid']), 16)) {
				$mayEdit = true;
			}
		}

		if (!$conf['onlyCurrentPid'] || ($dataArray['pid'] == $GLOBALS['TSFE']->id)) {
				// Permissions:
			$types = t3lib_div::trimExplode(',', t3lib_div::strtolower($conf['allow']),1);
			$allow = array_flip($types);

			$perms = $GLOBALS['BE_USER']->calcPerms($GLOBALS['TSFE']->page);
			if ($table == 'pages') {
				$allow = $this->getAllowedEditActions($table, $conf, $dataArray['pid'], $allow);

					// Can only display editbox if there are options in the menu
				if (count($allow)) {
					$mayEdit = true;
				}
			} else {
				$mayEdit = count($allow) && ($perms & 16);
			}
		}

		return $mayEdit;
	}

	/**
	 * Takes an array of generally allowed actions and filters that list based on page and content permissions.
	 *
	 * @param	string	The name of the table.
	 * @param	array	The configuration array.
	 * @param	integer	The PID where editing will occur.
	 * @param	string	Comma-separated list of actions that are allowed in general.
	 * @return	array
	 */
	protected function getAllowedEditActions($table, array $conf, $pid, $allow = '') {

		if (!$allow) {
			$types = t3lib_div::trimExplode(',', t3lib_div::strtolower($conf['allow']), true);
			$allow = array_flip($types);
		}

		if (!$conf['onlyCurrentPid'] || $pid == $GLOBALS['TSFE']->id) {
				// Permissions:
			$types = t3lib_div::trimExplode(',', t3lib_div::strtolower($conf['allow']), true);
			$allow = array_flip($types);

			$perms = $GLOBALS['BE_USER']->calcPerms($GLOBALS['TSFE']->page);
			if ($table=='pages') {
					// rootpage!
				if (count($GLOBALS['TSFE']->config['rootLine']) == 1) {
					unset($allow['move']);
					unset($allow['hide']);
					unset($allow['delete']);
				}
				if (!($perms & 2)){
					unset($allow['edit']);
					unset($allow['move']);
					unset($allow['hide']);
				}
				if (!($perms & 4)) {
					unset($allow['delete']);
				}
				if (!($perms&8)) {
					unset($allow['new']);
				}
			}
		}

		return $allow;
	}

	/**
	 * Adds any extra Javascript includes needed for Front-end editing
	 *
	 * @param	none
	 * @return	string
	 */
	public function getJavascriptIncludes() {
			// No extra JS includes needed
		return '';
	}
		
	/**
	 * Gets the hidden fields (array key=field name, value=field value) to be used in the edit panel for a particular content element.
	 * In the normal case, no hidden fields are needed but special controllers such as TemplaVoila need to track flexform pointers, etc.
	 *
	 * @param	array	The data array for a specific content element.
	 * @return	array
	 */
	public function getHiddenFields(array $dataArray) {
			// No special hidden fields needed.
		return array();
	}	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_frontendedit.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_frontendedit.php']);
}

?>