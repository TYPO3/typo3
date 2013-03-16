<?php
namespace TYPO3\CMS\Perm\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2013 mehrwert (typo3@mehrwert.de)
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
 * This class extends the permissions module in the TYPO3 Backend to provide
 * convenient methods of editing of page permissions (including page ownership
 * (user and group)) via new TYPO3AJAX facility
 *
 * @author Andreas Kundoch <typo3@mehrwert.de>
 * @license GPL
 * @since TYPO3_4-2
 */
class PermissionAjaxController {

	// The local configuration array
	protected $conf = array();

	// TYPO3 Back Path
	protected $backPath = '../../../';

	/********************************************
	 *
	 * Init method for this class
	 *
	 ********************************************/
	/**
	 * The constructor of this class
	 */
	public function __construct() {
		// Configuration, variable assignment
		$this->conf['page'] = \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('page');
		$this->conf['who'] = \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('who');
		$this->conf['mode'] = \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('mode');
		$this->conf['bits'] = intval(\TYPO3\CMS\Core\Utility\GeneralUtility::_POST('bits'));
		$this->conf['permissions'] = intval(\TYPO3\CMS\Core\Utility\GeneralUtility::_POST('permissions'));
		$this->conf['action'] = \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('action');
		$this->conf['ownerUid'] = intval(\TYPO3\CMS\Core\Utility\GeneralUtility::_POST('ownerUid'));
		$this->conf['username'] = \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('username');
		$this->conf['groupUid'] = intval(\TYPO3\CMS\Core\Utility\GeneralUtility::_POST('groupUid'));
		$this->conf['groupname'] = \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('groupname');
		$this->conf['editLockState'] = intval(\TYPO3\CMS\Core\Utility\GeneralUtility::_POST('editLockState'));
		// User: Replace some parts of the posted values
		$this->conf['new_owner_uid'] = intval(\TYPO3\CMS\Core\Utility\GeneralUtility::_POST('newOwnerUid'));
		$temp_owner_data = \TYPO3\CMS\Backend\Utility\BackendUtility::getUserNames('username, uid', ' AND uid = ' . $this->conf['new_owner_uid']);
		$this->conf['new_owner_username'] = htmlspecialchars($temp_owner_data[$this->conf['new_owner_uid']]['username']);
		// Group: Replace some parts of the posted values
		$this->conf['new_group_uid'] = intval(\TYPO3\CMS\Core\Utility\GeneralUtility::_POST('newGroupUid'));
		$temp_group_data = \TYPO3\CMS\Backend\Utility\BackendUtility::getGroupNames('title,uid', ' AND uid = ' . $this->conf['new_group_uid']);
		$this->conf['new_group_username'] = htmlspecialchars($temp_group_data[$this->conf['new_group_uid']]['title']);
	}

	/********************************************
	 *
	 * Main dispatcher method
	 *
	 ********************************************/
	/**
	 * The main dispatcher function. Collect data and prepare HTML output.
	 *
	 * @param array $params array of parameters from the AJAX interface, currently unused
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj object of type TYPO3AJAX
	 * @return void
	 */
	public function dispatch($params = array(), \TYPO3\CMS\Core\Http\AjaxRequestHandler &$ajaxObj = NULL) {
		$content = '';
		// Basic test for required value
		if ($this->conf['page'] > 0) {
			// Init TCE for execution of update
			/** @var $tce \TYPO3\CMS\Core\DataHandling\DataHandler */
			$tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
			$tce->stripslashes_values = 1;
			// Determine the scripts to execute
			switch ($this->conf['action']) {
			case 'show_change_owner_selector':
				$content = $this->renderUserSelector($this->conf['page'], $this->conf['ownerUid'], $this->conf['username']);
				break;
			case 'change_owner':
				if (is_int($this->conf['new_owner_uid'])) {
					// Prepare data to change
					$data = array();
					$data['pages'][$this->conf['page']]['perms_userid'] = $this->conf['new_owner_uid'];
					// Execute TCE Update
					$tce->start($data, array());
					$tce->process_datamap();
					$content = self::renderOwnername($this->conf['page'], $this->conf['new_owner_uid'], $this->conf['new_owner_username']);
				} else {
					$ajaxObj->setError('An error occured: No page owner uid specified.');
				}
				break;
			case 'show_change_group_selector':
				$content = $this->renderGroupSelector($this->conf['page'], $this->conf['groupUid'], $this->conf['groupname']);
				break;
			case 'change_group':
				if (is_int($this->conf['new_group_uid'])) {
					// Prepare data to change
					$data = array();
					$data['pages'][$this->conf['page']]['perms_groupid'] = $this->conf['new_group_uid'];
					// Execute TCE Update
					$tce->start($data, array());
					$tce->process_datamap();
					$content = self::renderGroupname($this->conf['page'], $this->conf['new_group_uid'], $this->conf['new_group_username']);
				} else {
					$ajaxObj->setError('An error occured: No page group uid specified.');
				}
				break;
			case 'toggle_edit_lock':
				// Prepare data to change
				$data = array();
				$data['pages'][$this->conf['page']]['editlock'] = $this->conf['editLockState'] === 1 ? 0 : 1;
				// Execute TCE Update
				$tce->start($data, array());
				$tce->process_datamap();
				$content = $this->renderToggleEditLock($this->conf['page'], $data['pages'][$this->conf['page']]['editlock']);
				break;
			default:
				if ($this->conf['mode'] == 'delete') {
					$this->conf['permissions'] = intval($this->conf['permissions'] - $this->conf['bits']);
				} else {
					$this->conf['permissions'] = intval($this->conf['permissions'] + $this->conf['bits']);
				}
				// Prepare data to change
				$data = array();
				$data['pages'][$this->conf['page']]['perms_' . $this->conf['who']] = $this->conf['permissions'];
				// Execute TCE Update
				$tce->start($data, array());
				$tce->process_datamap();
				$content = self::renderPermissions($this->conf['permissions'], $this->conf['page'], $this->conf['who']);
			}
		} else {
			$ajaxObj->setError('This script cannot be called directly.');
		}
		$ajaxObj->addContent($this->conf['page'] . '_' . $this->conf['who'], $content);
	}

	/********************************************
	 *
	 * Helpers for this script
	 *
	 ********************************************/
	/**
	 * Generate the user selector element
	 *
	 * @param integer $page The page id to change the user for
	 * @param integer $ownerUid The page owner uid
	 * @param string $username The username to display
	 * @return string The html select element
	 */
	protected function renderUserSelector($page, $ownerUid, $username = '') {
		// Get usernames
		$beUsers = \TYPO3\CMS\Backend\Utility\BackendUtility::getUserNames();
		// Init groupArray
		$groups = array();
		if (!$GLOBALS['BE_USER']->isAdmin()) {
			$beUsers = \TYPO3\CMS\Backend\Utility\BackendUtility::blindUserNames($beUsers, $groups, 1);
		}
		// Owner selector:
		$options = '';
		// Loop through the users
		foreach ($beUsers as $uid => $row) {
			$selected = $uid == $ownerUid ? ' selected="selected"' : '';
			$options .= '<option value="' . $uid . '"' . $selected . '>' . htmlspecialchars($row['username']) . '</option>';
		}
		$elementId = 'o_' . $page;
		$options = '<option value="0"></option>' . $options;
		$selector = '<select name="new_page_owner" id="new_page_owner">' . $options . '</select>';
		$saveButton = '<a onclick="WebPermissions.changeOwner(' . $page . ', ' . $ownerUid . ', \'' . $elementId . '\');" title="Change owner">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-save') . '</a>';
		$cancelButton = '<a onclick="WebPermissions.restoreOwner(' . $page . ', ' . $ownerUid . ', \'' . ($username == '' ? '<span class=not_set>[not set]</span>' : htmlspecialchars($username)) . '\', \'' . $elementId . '\');" title="Cancel">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-close') . '</a>';
		$ret = $selector . $saveButton . $cancelButton;
		return $ret;
	}

	/**
	 * Generate the group selector element
	 *
	 * @param integer $page The page id to change the user for
	 * @param integer $groupUid The page group uid
	 * @param string $username The username to display
	 * @return string The html select element
	 */
	protected function renderGroupSelector($page, $groupUid, $groupname = '') {
		// Get usernames
		$beGroups = \TYPO3\CMS\Backend\Utility\BackendUtility::getListGroupNames('title,uid');
		$beGroupKeys = array_keys($beGroups);
		$beGroupsO = ($beGroups = \TYPO3\CMS\Backend\Utility\BackendUtility::getGroupNames());
		if (!$GLOBALS['BE_USER']->isAdmin()) {
			$beGroups = \TYPO3\CMS\Backend\Utility\BackendUtility::blindGroupNames($beGroupsO, $beGroupKeys, 1);
		}
		// Group selector:
		$options = '';
		// flag: is set if the page-groupid equals one from the group-list
		$userset = 0;
		// Loop through the groups
		foreach ($beGroups as $uid => $row) {
			if ($uid == $groupUid) {
				$userset = 1;
				$selected = ' selected="selected"';
			} else {
				$selected = '';
			}
			$options .= '<option value="' . $uid . '"' . $selected . '>' . htmlspecialchars($row['title']) . '</option>';
		}
		// If the group was not set AND there is a group for the page
		if (!$userset && $groupUid) {
			$options = '<option value="' . $groupUid . '" selected="selected">' . htmlspecialchars($beGroupsO[$groupUid]['title']) . '</option>' . $options;
		}
		$elementId = 'g_' . $page;
		$options = '<option value="0"></option>' . $options;
		$selector = '<select name="new_page_group" id="new_page_group">' . $options . '</select>';
		$saveButton = '<a onclick="WebPermissions.changeGroup(' . $page . ', ' . $groupUid . ', \'' . $elementId . '\');" title="Change group">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-save') . '</a>';
		$cancelButton = '<a onclick="WebPermissions.restoreGroup(' . $page . ', ' . $groupUid . ', \'' . ($groupname == '' ? '<span class=not_set>[not set]</span>' : htmlspecialchars($groupname)) . '\', \'' . $elementId . '\');" title="Cancel">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-close') . '</a>';
		$ret = $selector . $saveButton . $cancelButton;
		return $ret;
	}

	/**
	 * Print the string with the new owner of a page record
	 *
	 * @param integer $page The TYPO3 page id
	 * @param integer $ownerUid The new page user uid
	 * @param string $username The TYPO3 BE username (used to display in the element)
	 * @param boolean $validUser Must be set to FALSE, if the user has no name or is deleted
	 * @return string The new group wrapped in HTML
	 */
	static public function renderOwnername($page, $ownerUid, $username, $validUser = TRUE) {
		$elementId = 'o_' . $page;
		$ret = '<span id="' . $elementId . '"><a class="ug_selector" onclick="WebPermissions.showChangeOwnerSelector(' . $page . ', ' . $ownerUid . ', \'' . $elementId . '\', \'' . htmlspecialchars($username) . '\');">' . ($validUser ? ($username == '' ? '<span class=not_set>[' . $GLOBALS['LANG']->getLL('notSet') . ']</span>' : htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($username, 20))) : '<span class=not_set title="' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($username, 20)) . '">[' . $GLOBALS['LANG']->getLL('deleted') . ']</span>') . '</a></span>';
		return $ret;
	}

	/**
	 * Print the string with the new group of a page record
	 *
	 * @param integer $page The TYPO3 page id
	 * @param integer $groupUid The new page group uid
	 * @param string $groupname The TYPO3 BE groupname (used to display in the element)
	 * @param boolean $validGroup Must be set to FALSE, if the group has no name or is deleted
	 * @return string The new group wrapped in HTML
	 */
	static public function renderGroupname($page, $groupUid, $groupname, $validGroup = TRUE) {
		$elementId = 'g_' . $page;
		$ret = '<span id="' . $elementId . '"><a class="ug_selector" onclick="WebPermissions.showChangeGroupSelector(' . $page . ', ' . $groupUid . ', \'' . $elementId . '\', \'' . htmlspecialchars($groupname) . '\');">' . ($validGroup ? ($groupname == '' ? '<span class=not_set>[' . $GLOBALS['LANG']->getLL('notSet') . ']</span>' : htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($groupname, 20))) : '<span class=not_set title="' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($groupname, 20)) . '">[' . $GLOBALS['LANG']->getLL('deleted') . ']</span>') . '</a></span>';
		return $ret;
	}

	/**
	 * Print the string with the new edit lock state of a page record
	 *
	 * @param integer $page The TYPO3 page id
	 * @param string $editlockstate The state of the TYPO3 page (locked, unlocked)
	 * @return string The new edit lock string wrapped in HTML
	 */
	protected function renderToggleEditLock($page, $editLockState) {
		if ($editLockState === 1) {
			$ret = '<a class="editlock" onclick="WebPermissions.toggleEditLock(' . $page . ', 1);" title="The page and all content is locked for editing by all non-Admin users.">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-warning-lock') . '</a>';
		} else {
			$ret = '<a class="editlock" onclick="WebPermissions.toggleEditLock(' . $page . ', 0);" title="Enable the &raquo;Admin-only&laquo; edit lock for this page">[+]</a>';
		}
		return $ret;
	}

	/**
	 * Print a set of permissions. Also used in index.php
	 *
	 * @param integer $int Permission integer (bits)
	 * @param integer $page The TYPO3 page id
	 * @param string $who The scope (user, group or everybody)
	 * @return string HTML marked up x/* indications.
	 */
	static public function renderPermissions($int, $pageId = 0, $who = 'user') {
		$str = '';
		$permissions = array(1, 16, 2, 4, 8);
		foreach ($permissions as $permission) {
			if ($int & $permission) {
				$str .= \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-status-permission-granted', array(
					'tag' => 'a',
					'title' => $GLOBALS['LANG']->getLL($permission, TRUE),
					'onclick' => 'WebPermissions.setPermissions(' . $pageId . ', ' . $permission . ', \'delete\', \'' . $who . '\', ' . $int . ');',
					'style' => 'cursor:pointer'
				));
			} else {
				$str .= \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-status-permission-denied', array(
					'tag' => 'a',
					'title' => $GLOBALS['LANG']->getLL($permission, TRUE),
					'onclick' => 'WebPermissions.setPermissions(' . $pageId . ', ' . $permission . ', \'add\', \'' . $who . '\', ' . $int . ');',
					'style' => 'cursor:pointer'
				));
			}
		}
		return '<span id="' . $pageId . '_' . $who . '">' . $str . '</span>';
	}

}


?>