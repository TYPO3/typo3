<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2011 mehrwert (typo3@mehrwert.de)
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
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   66: class SC_mod_web_perm_ajax
 *
 *              SECTION: Init method for this class
 *   97:     public function __construct()
 *
 *              SECTION: Main dispatcher method
 *  143:     public function dispatch($params = array(), TYPO3AJAX &$ajaxObj = null)
 *
 *              SECTION: Helpers for this script
 *  259:     private function renderUserSelector($page, $ownerUid, $username = '')
 *  302:     private function renderGroupSelector($page, $groupUid, $groupname = '')
 *  350:     private function renderOwnername($page, $ownerUid, $username)
 *  363:     private function renderGroupname($page, $groupUid, $groupname)
 *  375:     private function renderToggleEditLock($page, $editlockstate)
 *  389:     private function renderPermissions($int, $pageId = 0, $who = 'user')
 *
 * TOTAL FUNCTIONS: 8
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

$GLOBALS['LANG']->includeLLFile('EXT:lang/locallang_mod_web_perm.xml');

/**
 * This class extends the permissions module in the TYPO3 Backend to provide
 * convenient methods of editing of page permissions (including page ownership
 * (user and group)) via new TYPO3AJAX facility
 *
 * @author		Andreas Kundoch <typo3@mehrwert.de>
 * @version		$Id$
 * @package		TYPO3
 * @subpackage	core
 * @license		GPL
 * @since		TYPO3_4-2
 */
class SC_mod_web_perm_ajax {

	protected $conf = array();	// The local configuration array
	protected $backPath = '../../../';	// TYPO3 Back Path

	/********************************************
	 *
	 * Init method for this class
	 *
	 ********************************************/

	/**
	 * The constructor of this class
	 *
	 * @return	Void
	 */
	public function __construct() {

			// Configuration, variable assignment
		$this->conf['page']          = t3lib_div::_POST('page');
		$this->conf['who']           = t3lib_div::_POST('who');
		$this->conf['mode']          = t3lib_div::_POST('mode');
		$this->conf['bits']          = intval(t3lib_div::_POST('bits'));
		$this->conf['permissions']   = intval(t3lib_div::_POST('permissions'));
		$this->conf['action']	     = t3lib_div::_POST('action');
		$this->conf['ownerUid']      = intval(t3lib_div::_POST('ownerUid'));
		$this->conf['username']      = t3lib_div::_POST('username');
		$this->conf['groupUid']      = intval(t3lib_div::_POST('groupUid'));
		$this->conf['groupname']     = t3lib_div::_POST('groupname');
		$this->conf['editLockState'] = intval(t3lib_div::_POST('editLockState'));

			// User: Replace some parts of the posted values
		$this->conf['new_owner_uid'] = intval(t3lib_div::_POST('newOwnerUid'));
		$temp_owner_data = t3lib_BEfunc::getUserNames(
			'username, uid',
			' AND uid = ' . $this->conf['new_owner_uid']
		);
		$this->conf['new_owner_username'] = htmlspecialchars(
			$temp_owner_data[$this->conf['new_owner_uid']]['username']
		);

			// Group: Replace some parts of the posted values
		$this->conf['new_group_uid'] = intval(t3lib_div::_POST('newGroupUid'));
		$temp_group_data             = t3lib_BEfunc::getGroupNames(
			'title,uid',
			' AND uid = ' . $this->conf['new_group_uid']
		);
		$this->conf['new_group_username'] = htmlspecialchars(
			$temp_group_data[$this->conf['new_group_uid']]['title']
		);

	}

	/********************************************
	 *
	 * Main dispatcher method
	 *
	 ********************************************/

	/**
	 * The main dispatcher function. Collect data and prepare HTML output.
	 *
	 * @param	array		$params: array of parameters from the AJAX interface, currently unused
	 * @param	TYPO3AJAX		$ajaxObj: object of type TYPO3AJAX
	 * @return	Void
	 */
	public function dispatch($params = array(), TYPO3AJAX &$ajaxObj = null) {
		$content = '';

			// Basic test for required value
		if ($this->conf['page'] > 0) {

				// Init TCE for execution of update
			$tce = t3lib_div::makeInstance('t3lib_TCEmain');
			$tce->stripslashes_values = 1;

				// Determine the scripts to execute
			switch ($this->conf['action']) {

					// Return the select to change the owner (BE user) of the page
				case 'show_change_owner_selector':
					$content = $this->renderUserSelector($this->conf['page'], $this->conf['ownerUid'], $this->conf['username']);
					break;

					// Change the owner and return the new owner HTML snippet
				case 'change_owner':
					if (is_int($this->conf['new_owner_uid'])) {
							// Prepare data to change
						$data = array();
						$data['pages'][$this->conf['page']]['perms_userid'] = $this->conf['new_owner_uid'];

							// Execute TCE Update
						$tce->start($data, array());
						$tce->process_datamap();
						$content = $this->renderOwnername($this->conf['page'], $this->conf['new_owner_uid'], $this->conf['new_owner_username']);
					} else {
						$ajaxObj->setError('An error occured: No page owner uid specified.');
					}
					break;

					// Return the select to change the group (BE group) of the page
				case 'show_change_group_selector':
					$content = $this->renderGroupSelector($this->conf['page'], $this->conf['groupUid'], $this->conf['groupname']);
					break;

					// Change the group and return the new group HTML snippet
				case 'change_group':
					if (is_int($this->conf['new_group_uid'])) {

							// Prepare data to change
						$data = array();
						$data['pages'][$this->conf['page']]['perms_groupid'] = $this->conf['new_group_uid'];

							// Execute TCE Update
						$tce->start($data, array());
						$tce->process_datamap();

						$content = $this->renderGroupname($this->conf['page'], $this->conf['new_group_uid'], $this->conf['new_group_username']);
					} else {
						$ajaxObj->setError('An error occured: No page group uid specified.');
					}
					break;

					// Change the group and return the new group HTML snippet
				case 'toggle_edit_lock':

						// Prepare data to change
					$data = array();
					$data['pages'][$this->conf['page']]['editlock'] = ($this->conf['editLockState'] === 1 ? 0 : 1);

						// Execute TCE Update
					$tce->start($data, array());
					$tce->process_datamap();

					$content = $this->renderToggleEditLock($this->conf['page'], $data['pages'][$this->conf['page']]['editlock']);
					break;

					// The script defaults to change permissions
				default:
					if ($this->conf['mode'] == 'delete') {
						$this->conf['permissions'] = intval($this->conf['permissions'] - $this->conf['bits']);
					} else {
						$this->conf['permissions'] = intval($this->conf['permissions'] + $this->conf['bits']);
					}

						// Prepare data to change
					$data = array();
					$data['pages'][$this->conf['page']]['perms_'.$this->conf['who']] = $this->conf['permissions'];

						// Execute TCE Update
					$tce->start($data, array());
					$tce->process_datamap();

					$content = $this->renderPermissions($this->conf['permissions'], $this->conf['page'], $this->conf['who']);
			}
		} else {
			$ajaxObj->setError('This script cannot be called directly.');
		}
		$ajaxObj->addContent($this->conf['page'].'_'.$this->conf['who'], $content);
	}

	/********************************************
	 *
	 * Helpers for this script
	 *
	 ********************************************/

	/**
	 * Generate the user selector element
	 *
	 * @param	Integer		$page: The page id to change the user for
	 * @param	Integer		$ownerUid: The page owner uid
	 * @param	String		$username: The username to display
	 * @return	String		The html select element
	 */
	protected function renderUserSelector($page, $ownerUid, $username = '') {

			// Get usernames
		$beUsers = t3lib_BEfunc::getUserNames();

			// Init groupArray
		$groups = array();

		if (!$GLOBALS['BE_USER']->isAdmin()) {
			$beUsers = t3lib_BEfunc::blindUserNames($beUsers, $groups, 1);
		}

			// Owner selector:
		$options = '';

			// Loop through the users
		foreach ($beUsers as $uid => $row) {
			$selected = ($uid == $ownerUid	? ' selected="selected"' : '');
			$options .= '<option value="'.$uid.'"'.$selected.'>'.htmlspecialchars($row['username']).'</option>';
		}

		$elementId = 'o_'.$page;
		$options = '<option value="0"></option>'.$options;
		$selector = '<select name="new_page_owner" id="new_page_owner">'.$options.'</select>';
		$saveButton = '<a onclick="WebPermissions.changeOwner('.$page.', '.$ownerUid.', \''.$elementId.'\');" title="Change owner">' . t3lib_iconWorks::getSpriteIcon('actions-document-save') . '</a>';
		$cancelButton = '<a onclick="WebPermissions.restoreOwner('.$page.', '.$ownerUid.', \''.($username == '' ? '<span class=not_set>[not set]</span>' : htmlspecialchars($username)).'\', \''.$elementId.'\');" title="Cancel">' . t3lib_iconWorks::getSpriteIcon('actions-document-close') . '</a>';
		$ret = $selector.$saveButton.$cancelButton;
		return $ret;
	}

	/**
	 * Generate the group selector element
	 *
	 * @param	Integer		$page: The page id to change the user for
	 * @param	Integer		$groupUid: The page group uid
	 * @param	String		$username: The username to display
	 * @return	String		The html select element
	 */
	protected function renderGroupSelector($page, $groupUid, $groupname = '') {

			// Get usernames
		$beGroups = t3lib_BEfunc::getListGroupNames('title,uid');
		$beGroupKeys = array_keys($beGroups);
		$beGroupsO = $beGroups = t3lib_BEfunc::getGroupNames();
		if (!$GLOBALS['BE_USER']->isAdmin()) {
			$beGroups = t3lib_BEfunc::blindGroupNames($beGroupsO, $beGroupKeys, 1);
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
			$options .= '<option value="'.$uid.'"'.$selected.'>'.htmlspecialchars($row['title']).'</option>';
		}

			// If the group was not set AND there is a group for the page
		if (!$userset && $groupUid) {
			$options = '<option value="'.$groupUid.'" selected="selected">'.htmlspecialchars($beGroupsO[$groupUid]['title']).'</option>'.$options;
		}

		$elementId = 'g_'.$page;
		$options = '<option value="0"></option>'.$options;
		$selector = '<select name="new_page_group" id="new_page_group">'.$options.'</select>';
		$saveButton = '<a onclick="WebPermissions.changeGroup('.$page.', '.$groupUid.', \''.$elementId.'\');" title="Change group">' . t3lib_iconWorks::getSpriteIcon('actions-document-save') . '</a>';
		$cancelButton = '<a onclick="WebPermissions.restoreGroup('.$page.', '.$groupUid.', \''.($groupname == '' ? '<span class=not_set>[not set]</span>' : htmlspecialchars($groupname)).'\', \''.$elementId.'\');" title="Cancel">' . t3lib_iconWorks::getSpriteIcon('actions-document-close') . '</a>';
		$ret = $selector.$saveButton.$cancelButton;
		return $ret;
	}


	/**
	 * Print the string with the new owner of a page record
	 *
	 * @param	Integer		$page: The TYPO3 page id
	 * @param	Integer		$ownerUid: The new page user uid
	 * @param	String		$username: The TYPO3 BE username (used to display in the element)
	 * @param	Boolean		$validUser: Must be set to FALSE, if the user has no name or is deleted
	 * @return	String		The new group wrapped in HTML
	 */
	public function renderOwnername($page, $ownerUid, $username, $validUser = true) {
		$elementId = 'o_'.$page;
		$ret = '<span id="' . $elementId . '"><a class="ug_selector" onclick="WebPermissions.showChangeOwnerSelector(' . $page . ', ' . $ownerUid . ', \'' . $elementId.'\', \'' . htmlspecialchars($username) . '\');">' . ($validUser ? ($username == '' ? ('<span class=not_set>['. $GLOBALS['LANG']->getLL('notSet') .']</span>') : htmlspecialchars(t3lib_div::fixed_lgd_cs($username, 20))) :  ('<span class=not_set title="' . htmlspecialchars(t3lib_div::fixed_lgd_cs($username, 20)) . '">[' . $GLOBALS['LANG']->getLL('deleted') . ']</span>')) . '</a></span>';
		return $ret;
	}


	/**
	 * Print the string with the new group of a page record
	 *
	 * @param	Integer		$page: The TYPO3 page id
	 * @param	Integer		$groupUid: The new page group uid
	 * @param	String		$groupname: The TYPO3 BE groupname (used to display in the element)
	 * @param	Boolean		$validGroup: Must be set to FALSE, if the group has no name or is deleted
	 * @return	String		The new group wrapped in HTML
	 */
	public function renderGroupname($page, $groupUid, $groupname, $validGroup = true) {
		$elementId = 'g_'.$page;
		$ret = '<span id="'.$elementId . '"><a class="ug_selector" onclick="WebPermissions.showChangeGroupSelector(' . $page . ', ' . $groupUid . ', \'' . $elementId . '\', \'' . htmlspecialchars($groupname) . '\');">'. ($validGroup ? ($groupname == '' ? ('<span class=not_set>['. $GLOBALS['LANG']->getLL('notSet') .']</span>') : htmlspecialchars(t3lib_div::fixed_lgd_cs($groupname, 20))) : ('<span class=not_set title="' . htmlspecialchars(t3lib_div::fixed_lgd_cs($groupname, 20)) . '">[' . $GLOBALS['LANG']->getLL('deleted') . ']</span>')) . '</a></span>';
		return $ret;
	}


	/**
	 * Print the string with the new edit lock state of a page record
	 *
	 * @param	Integer		$page: The TYPO3 page id
	 * @param	String		$editlockstate: The state of the TYPO3 page (locked, unlocked)
	 * @return	String		The new edit lock string wrapped in HTML
	 */
	protected function renderToggleEditLock($page, $editLockState) {
		if ($editLockState === 1) {
			$ret = '<a class="editlock" onclick="WebPermissions.toggleEditLock('.$page.', 1);" title="The page and all content is locked for editing by all non-Admin users.">' . t3lib_iconWorks::getSpriteIcon('status-warning-lock') . '</a>';
		} else {
			$ret = '<a class="editlock" onclick="WebPermissions.toggleEditLock('.$page.', 0);" title="Enable the &raquo;Admin-only&laquo; edit lock for this page">[+]</a>';
		}
		return $ret;
	}


	/**
	 * Print a set of permissions. Also used in index.php
	 *
	 * @param	integer		Permission integer (bits)
	 * @param	Integer		$page: The TYPO3 page id
	 * @param	String		$who: The scope (user, group or everybody)
	 * @return	string		HTML marked up x/* indications.
	 */
	public function renderPermissions($int, $pageId = 0, $who = 'user') {
		global $LANG;
		$str = '';

		$permissions = array(1,16,2,4,8);
		foreach ($permissions as $permission) {
			if ($int&$permission) {
				$str .= t3lib_iconWorks::getSpriteIcon('status-status-permission-granted',array('tag'=>'a','title'=>$LANG->getLL($permission,1), 'onclick'=> 'WebPermissions.setPermissions('.$pageId.', '.$permission.', \'delete\', \''.$who.'\', '.$int.');'));
			} else {
				$str .= t3lib_iconWorks::getSpriteIcon('status-status-permission-denied',array('tag'=>'a','title'=>$LANG->getLL($permission,1),'onclick'=>'WebPermissions.setPermissions('.$pageId.', '.$permission.', \'add\', \''.$who.'\', '.$int.');'));
			}
		}
		return '<span id="'.$pageId.'_'.$who.'">'.$str.'</span>';
	}

}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/mod/web/perm/class.sc_mod_web_perm_ajax.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/mod/web/perm/class.sc_mod_web_perm_ajax.php']);
}

?>