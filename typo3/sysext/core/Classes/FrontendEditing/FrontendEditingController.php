<?php
namespace TYPO3\CMS\Core\FrontendEditing;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2013 Jeff Segars <jeff@webempoweredchurch.org>
 *  (c) 2008-2013 David Slayback <dave@webempoweredchurch.org>
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
 * @author Jeff Segars <jeff@webempoweredchurch.org>
 * @author David Slayback <dave@webempoweredchurch.org>
 */
class FrontendEditingController {

	/**
	 * GET/POST parameters for the FE editing.
	 * Accessed as $GLOBALS['BE_USER']->frontendEdit->TSFE_EDIT, thus public
	 *
	 * @var array
	 */
	public $TSFE_EDIT;

	/**
	 * @var \TYPO3\CMS\Core\DataHandling\DataHandler
	 */
	protected $tce;

	/**
	 * Initializes configuration options.
	 *
	 * @return void
	 */
	public function initConfigOptions() {
		$this->TSFE_EDIT = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('TSFE_EDIT');
		// Include classes for editing IF editing module in Admin Panel is open
		if ($GLOBALS['BE_USER']->isFrontendEditingActive()) {
			if ($this->isEditAction()) {
				$this->editAction();
			}
		}
	}

	/**
	 * Generates the "edit panels" which can be shown for a page or records on a page when the Admin Panel is enabled for a backend users surfing the frontend.
	 * With the "edit panel" the user will see buttons with links to editing, moving, hiding, deleting the element
	 * This function is used for the cObject EDITPANEL and the stdWrap property ".editPanel"
	 *
	 * @param string $content A content string containing the content related to the edit panel. For cObject "EDITPANEL" this is empty but not so for the stdWrap property. The edit panel is appended to this string and returned.
	 * @param array $conf TypoScript configuration properties for the editPanel
	 * @param string $currentRecord The "table:uid" of the record being shown. If empty string then $this->currentRecord is used. For new records (set by $conf['newRecordFromTable']) it's auto-generated to "[tablename]:NEW
	 * @param array $dataArray Alternative data array to use. Default is $this->data
	 * @return string The input content string with the editPanel appended. This function returns only an edit panel appended to the content string if a backend user is logged in (and has the correct permissions). Otherwise the content string is directly returned.
	 */
	public function displayEditPanel($content, array $conf, $currentRecord, array $dataArray) {
		if ($conf['newRecordFromTable']) {
			$currentRecord = $conf['newRecordFromTable'] . ':NEW';
			$conf['allow'] = 'new';
			$checkEditAccessInternals = FALSE;
		} else {
			$checkEditAccessInternals = TRUE;
		}
		list($table, $uid) = explode(':', $currentRecord);
		// Page ID for new records, 0 if not specified
		$newRecordPid = intval($conf['newRecordInPid']);
		if (!$conf['onlyCurrentPid'] || $dataArray['pid'] == $GLOBALS['TSFE']->id) {
			if ($table == 'pages') {
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
		if ($GLOBALS['TSFE']->displayEditIcons && $table && $this->allowedToEdit($table, $dataArray, $conf, $checkEditAccessInternals) && $this->allowedToEditLanguage($table, $dataArray)) {
			$editClass = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/classes/class.frontendedit.php']['edit'];
			if ($editClass) {
				$edit = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($editClass, FALSE);
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
	 * @param string $content The content to which the edit icons should be appended
	 * @param string $params The parameters defining which table and fields to edit. Syntax is [tablename]:[fieldname],[fieldname],[fieldname],... OR [fieldname],[fieldname],[fieldname],... (basically "[tablename]:" is optional, default table is the one of the "current record" used in the function). The fieldlist is sent as "&columnsOnly=" parameter to alt_doc.php
	 * @param array $conf TypoScript properties for configuring the edit icons.
	 * @param string $currentRecord The "table:uid" of the record being shown. If empty string then $this->currentRecord is used. For new records (set by $conf['newRecordFromTable']) it's auto-generated to "[tablename]:NEW
	 * @param array $dataArray Alternative data array to use. Default is $this->data
	 * @param string $addUrlParamStr Additional URL parameters for the link pointing to alt_doc.php
	 * @return string The input content string, possibly with edit icons added (not necessarily in the end but just after the last string of normal content.
	 */
	public function displayEditIcons($content, $params, array $conf = array(), $currentRecord = '', array $dataArray = array(), $addUrlParamStr = '') {
		// Check incoming params:
		list($currentRecordTable, $currentRecordUID) = explode(':', $currentRecord);
		list($fieldList, $table) = array_reverse(\TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(':', $params, 1));
		// Reverse the array because table is optional
		if (!$table) {
			$table = $currentRecordTable;
		} elseif ($table != $currentRecordTable) {
			// If the table is set as the first parameter, and does not match the table of the current record, then just return.
			return $content;
		}
		$editUid = $dataArray['_LOCALIZED_UID'] ? $dataArray['_LOCALIZED_UID'] : $currentRecordUID;
		// Edit icons imply that the editing action is generally allowed, assuming page and content element permissions permit it.
		if (!array_key_exists('allow', $conf)) {
			$conf['allow'] = 'edit';
		}
		if ($GLOBALS['TSFE']->displayFieldEditIcons && $table && $this->allowedToEdit($table, $dataArray, $conf) && $fieldList && $this->allowedToEditLanguage($table, $dataArray)) {
			$editClass = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/classes/class.frontendedit.php']['edit'];
			if ($editClass) {
				$edit = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($editClass);
				if (is_object($edit)) {
					$content = $edit->editIcons($content, $params, $conf, $currentRecord, $dataArray, $addUrlParamStr, $table, $editUid, $fieldList);
				}
			}
		}
		return $content;
	}

	/*****************************************************
	 *
	 * Frontend Editing
	 *
	 ****************************************************/
	/**
	 * Returns TRUE if an edit-action is sent from the Admin Panel
	 *
	 * @return boolean
	 * @see index_ts.php
	 */
	public function isEditAction() {
		if (is_array($this->TSFE_EDIT)) {
			if ($this->TSFE_EDIT['cancel']) {
				unset($this->TSFE_EDIT['cmd']);
			} else {
				$cmd = (string) $this->TSFE_EDIT['cmd'];
				if (($cmd != 'edit' || is_array($this->TSFE_EDIT['data']) && ($this->TSFE_EDIT['doSave'] || $this->TSFE_EDIT['update'] || $this->TSFE_EDIT['update_close'])) && $cmd != 'new') {
					// $cmd can be a command like "hide" or "move". If $cmd is "edit" or "new" it's an indication to show the formfields. But if data is sent with update-flag then $cmd = edit is accepted because edit may be sent because of .keepGoing flag.
					return TRUE;
				}
			}
		}
		return FALSE;
	}

	/**
	 * Returns TRUE if an edit form is shown on the page.
	 * Used from index_ts.php where a TRUE return-value will result in classes etc. being included.
	 *
	 * @return boolean
	 * @see index_ts.php
	 */
	public function isEditFormShown() {
		if (is_array($this->TSFE_EDIT)) {
			$cmd = (string) $this->TSFE_EDIT['cmd'];
			if ($cmd == 'edit' || $cmd == 'new') {
				return TRUE;
			}
		}
	}

	/**
	 * Management of the on-page frontend editing forms and edit panels.
	 * Basically taking in the data and commands and passes them on to the proper classes as they should be.
	 *
	 * @return void
	 * @throws UnexpectedValueException if TSFE_EDIT[cmd] is not a valid command
	 * @see index_ts.php
	 */
	public function editAction() {
		// Commands
		list($table, $uid) = explode(':', $this->TSFE_EDIT['record']);
		$uid = intval($uid);
		$cmd = $this->TSFE_EDIT['cmd'];
		// Look for some TSFE_EDIT data that indicates we should save.
		if (($this->TSFE_EDIT['doSave'] || $this->TSFE_EDIT['update'] || $this->TSFE_EDIT['update_close']) && is_array($this->TSFE_EDIT['data'])) {
			$cmd = 'save';
		}
		if ($cmd == 'save' || $cmd && $table && $uid && isset($GLOBALS['TCA'][$table])) {
			// Hook for defining custom editing actions. Naming is incorrect, but preserves compatibility.
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tsfebeuserauth.php']['extEditAction'])) {
				$_params = array();
				foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tsfebeuserauth.php']['extEditAction'] as $_funcRef) {
					\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $_params, $this);
				}
			}
			// Perform the requested editing command.
			$cmdAction = 'do' . ucwords($cmd);
			if (is_callable(array($this, $cmdAction))) {
				$this->{$cmdAction}($table, $uid);
			} else {
				throw new \UnexpectedValueException('The specified frontend edit command (' . $cmd . ') is not valid.', 1225818120);
			}
		}
	}

	/**
	 * Hides a specific record.
	 *
	 * @param string $table The table name for the record to hide.
	 * @param integer $uid The UID for the record to hide.
	 * @return void
	 */
	public function doHide($table, $uid) {
		$hideField = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'];
		if ($hideField) {
			$recData = array();
			$recData[$table][$uid][$hideField] = 1;
			$this->initializeTceMain();
			$this->tce->start($recData, array());
			$this->tce->process_datamap();
		}
	}

	/**
	 * Unhides (shows) a specific record.
	 *
	 * @param string $table The table name for the record to unhide.
	 * @param integer $uid The UID for the record to unhide.
	 * @return void
	 */
	public function doUnhide($table, $uid) {
		$hideField = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'];
		if ($hideField) {
			$recData = array();
			$recData[$table][$uid][$hideField] = 0;
			$this->initializeTceMain();
			$this->tce->start($recData, array());
			$this->tce->process_datamap();
		}
	}

	/**
	 * Moves a record up.
	 *
	 * @param string $table The table name for the record to move.
	 * @param integer $uid The UID for the record to hide.
	 * @return void
	 */
	public function doUp($table, $uid) {
		$this->move($table, $uid, 'up');
	}

	/**
	 * Moves a record down.
	 *
	 * @param string $table The table name for the record to move.
	 * @param integer $uid The UID for the record to move.
	 * @return void
	 */
	public function doDown($table, $uid) {
		$this->move($table, $uid, 'down');
	}

	/**
	 * Moves a record after a given element. Used for drag.
	 *
	 * @param string $table The table name for the record to move.
	 * @param integer $uid The UID for the record to move.
	 * @return void
	 */
	public function doMoveAfter($table, $uid) {
		$afterUID = $GLOBALS['BE_USER']->frontendEdit->TSFE_EDIT['moveAfter'];
		$this->move($table, $uid, '', $afterUID);
	}

	/**
	 * Moves a record
	 *
	 * @param string $table The table name for the record to move.
	 * @param integer $uid The UID for the record to move.
	 * @param string $direction The direction to move, either 'up' or 'down'.
	 * @param integer $afterUID The UID of record to move after. This is specified for dragging only.
	 * @return void
	 */
	protected function move($table, $uid, $direction = '', $afterUID = 0) {
		$cmdData = array();
		$sortField = $GLOBALS['TCA'][$table]['ctrl']['sortby'];
		if ($sortField) {
			// Get self
			$fields = array_unique(\TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$table]['ctrl']['copyAfterDuplFields'] . ',uid,pid,' . $sortField, TRUE));
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(implode(',', $fields), $table, 'uid=' . $uid);
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				// Record before or after
				if ($GLOBALS['BE_USER']->adminPanel instanceof \TYPO3\CMS\Frontend\View\AdminPanelView && $GLOBALS['BE_USER']->adminPanel->extGetFeAdminValue('preview')) {
					$ignore = array('starttime' => 1, 'endtime' => 1, 'disabled' => 1, 'fe_group' => 1);
				}
				$copyAfterFieldsQuery = '';
				if ($GLOBALS['TCA'][$table]['ctrl']['copyAfterDuplFields']) {
					$cAFields = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$table]['ctrl']['copyAfterDuplFields'], TRUE);
					foreach ($cAFields as $fieldName) {
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
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,pid', $table, 'pid=' . intval($row['pid']) . $sortCheck . $copyAfterFieldsQuery . $GLOBALS['TSFE']->sys_page->enableFields($table, '', $ignore), '', $sortField . ' ' . $order, '2');
				if ($row2 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					if ($afterUID) {
						$cmdData[$table][$uid]['move'] = -$afterUID;
					} elseif ($direction == 'down') {
						$cmdData[$table][$uid]['move'] = -$row2['uid'];
					} elseif ($row3 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
						// Must take the second record above...
						$cmdData[$table][$uid]['move'] = -$row3['uid'];
					} else {
						// ... and if that does not exist, use pid
						$cmdData[$table][$uid]['move'] = $row['pid'];
					}
				} elseif ($direction == 'up') {
					$cmdData[$table][$uid]['move'] = $row['pid'];
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
			}
			if (!empty($cmdData)) {
				$this->initializeTceMain();
				$this->tce->start(array(), $cmdData);
				$this->tce->process_cmdmap();
			}
		}
	}

	/**
	 * Deletes a specific record.
	 *
	 * @param string $table The table name for the record to delete.
	 * @param integer $uid The UID for the record to delete.
	 * @return void
	 */
	public function doDelete($table, $uid) {
		$cmdData[$table][$uid]['delete'] = 1;
		if (count($cmdData)) {
			$this->initializeTceMain();
			$this->tce->start(array(), $cmdData);
			$this->tce->process_cmdmap();
		}
	}

	/**
	 * Saves a record based on its data array.
	 *
	 * @param string $table The table name for the record to save.
	 * @param integer $uid The UID for the record to save.
	 * @return void
	 */
	public function doSave($table, $uid) {
		$data = $this->TSFE_EDIT['data'];
		if (!empty($data)) {
			$this->initializeTceMain();
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
	 * Saves a record based on its data array and closes it.
	 *
	 * @param string $table The table name for the record to save.
	 * @param integer $uid The UID for the record to save.
	 * @return void
	 * @note 	This method is only a wrapper for doSave() but is needed so
	 */
	public function doSaveAndClose($table, $uid) {
		$this->doSave($table, $uid);
	}

	/**
	 * Stub for closing a record. No real functionality needed since content
	 * element rendering will take care of everything.
	 *
	 * @param string $table The table name for the record to close.
	 * @param integer $uid The UID for the record to close.
	 * @return void
	 */
	public function doClose($table, $uid) {

	}

	/**
	 * Checks whether the user has access to edit the language for the
	 * requested record.
	 *
	 * @param string $table The name of the table.
	 * @param array $currentRecord The record.
	 * @return boolean
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
			$languageAccess = TRUE;
		} else {
			$languageAccess = FALSE;
		}
		return $languageAccess;
	}

	/**
	 * Checks whether the user is allowed to edit the requested table.
	 *
	 * @param string $table The name of the table.
	 * @param array $dataArray The data array.
	 * @param array $conf The configuration array for the edit panel.
	 * @param boolean $checkEditAccessInternals Boolean indicating whether recordEditAccessInternals should not be checked. Defaults
	 * @return boolean
	 */
	protected function allowedToEdit($table, array $dataArray, array $conf, $checkEditAccessInternals = TRUE) {
		// Unless permissions specifically allow it, editing is not allowed.
		$mayEdit = FALSE;
		if ($checkEditAccessInternals) {
			$editAccessInternals = $GLOBALS['BE_USER']->recordEditAccessInternals($table, $dataArray, FALSE, FALSE);
		} else {
			$editAccessInternals = TRUE;
		}
		if ($editAccessInternals) {
			if ($table == 'pages') {
				// 2 = permission to edit the page
				if ($GLOBALS['BE_USER']->isAdmin() || $GLOBALS['BE_USER']->doesUserHaveAccess($dataArray, 2)) {
					$mayEdit = TRUE;
				}
			} else {
				// 16 = permission to edit content on the page
				if ($GLOBALS['BE_USER']->isAdmin() || $GLOBALS['BE_USER']->doesUserHaveAccess(\TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages', $dataArray['pid']), 16)) {
					$mayEdit = TRUE;
				}
			}
			if (!$conf['onlyCurrentPid'] || $dataArray['pid'] == $GLOBALS['TSFE']->id) {
				// Permissions:
				$types = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', \TYPO3\CMS\Core\Utility\GeneralUtility::strtolower($conf['allow']), 1);
				$allow = array_flip($types);
				$perms = $GLOBALS['BE_USER']->calcPerms($GLOBALS['TSFE']->page);
				if ($table == 'pages') {
					$allow = $this->getAllowedEditActions($table, $conf, $dataArray['pid'], $allow);
					// Can only display editbox if there are options in the menu
					if (count($allow)) {
						$mayEdit = TRUE;
					}
				} else {
					$mayEdit = count($allow) && $perms & 16;
				}
			}
		}
		return $mayEdit;
	}

	/**
	 * Takes an array of generally allowed actions and filters that list based on page and content permissions.
	 *
	 * @param string $table The name of the table.
	 * @param array $conf The configuration array.
	 * @param integer $pid The PID where editing will occur.
	 * @param string $allow Comma-separated list of actions that are allowed in general.
	 * @return array
	 */
	protected function getAllowedEditActions($table, array $conf, $pid, $allow = '') {
		if (!$allow) {
			$types = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', \TYPO3\CMS\Core\Utility\GeneralUtility::strtolower($conf['allow']), TRUE);
			$allow = array_flip($types);
		}
		if (!$conf['onlyCurrentPid'] || $pid == $GLOBALS['TSFE']->id) {
			// Permissions
			$types = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', \TYPO3\CMS\Core\Utility\GeneralUtility::strtolower($conf['allow']), TRUE);
			$allow = array_flip($types);
			$perms = $GLOBALS['BE_USER']->calcPerms($GLOBALS['TSFE']->page);
			if ($table == 'pages') {
				// Rootpage
				if (count($GLOBALS['TSFE']->config['rootLine']) == 1) {
					unset($allow['move']);
					unset($allow['hide']);
					unset($allow['delete']);
				}
				if (!($perms & 2)) {
					unset($allow['edit']);
					unset($allow['move']);
					unset($allow['hide']);
				}
				if (!($perms & 4)) {
					unset($allow['delete']);
				}
				if (!($perms & 8)) {
					unset($allow['new']);
				}
			}
		}
		return $allow;
	}

	/**
	 * Adds any extra Javascript includes needed for Front-end editing
	 *
	 * @return string
	 */
	public function getJavascriptIncludes() {
		// No extra JS includes needed
		return '';
	}

	/**
	 * Gets the hidden fields (array key=field name, value=field value) to be used in the edit panel for a particular content element.
	 * In the normal case, no hidden fields are needed but special controllers such as TemplaVoila need to track flexform pointers, etc.
	 *
	 * @param array $dataArray The data array for a specific content element.
	 * @return array
	 */
	public function getHiddenFields(array $dataArray) {
		// No special hidden fields needed.
		return array();
	}

	/**
	 * Initializes \TYPO3\CMS\Core\DataHandling\DataHandler since it is used on modification actions.
	 *
	 * @return void
	 */
	protected function initializeTceMain() {
		if (!isset($this->tce)) {
			$this->tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
			$this->tce->stripslashes_values = 0;
		}
	}

}


?>