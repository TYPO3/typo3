<?php
namespace TYPO3\CMS\Perm\Controller;

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
 *  A copy is found in the text file GPL.txt and important notices to the license
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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;

/**
 * Module: Permission setting
 *
 * Script Class for the Web > Access module
 * This module lets you view and change permissions for pages.
 *
 * Variables:
 * $this->MOD_SETTINGS['depth']: int 1-3: decides the depth of the list
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author Andreas Kundoch <typo3@mehrwert.de>
 */
class PermissionModuleController {

	/**
	 * Number of levels to enable recursive settings for
	 *
	 * @var integer
	 */
	public $getLevels = 10;

	/**
	 * Module config
	 * Internal static
	 *
	 * @var array
	 */
	protected $MCONF = array();

	/**
	 * Document Template Object
	 *
	 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
	 */
	public $doc;

	/**
	 * Content accumulation
	 *
	 * @var string
	 */
	public $content;

	/**
	 * Module menu
	 *
	 * @var array
	 */
	public $MOD_MENU = array();

	/**
	 * Module settings, cleansed.
	 *
	 * @var array
	 */
	public $MOD_SETTINGS = array();

	/**
	 * Page select permissions
	 *
	 * @var string
	 */
	public $perms_clause;

	/**
	 * Current page record
	 *
	 * @var array
	 */
	public $pageinfo;

	/**
	 * Background color 1
	 *
	 * @var string
	 */
	public $color;

	/**
	 * Background color 2
	 *
	 * @var string
	 */
	public $color2;

	/**
	 * Background color 3
	 *
	 * @var string
	 */
	public $color3;

	/**
	 * Set internally if the current user either OWNS the page OR is admin user!
	 *
	 * @var boolean
	 */
	public $editingAllowed;

	/**
	 * Internal, static: GPvars: Page id.
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * If set, editing of the page permissions will occur (showing the editing screen). Notice:
	 * This value is evaluated against permissions and so it will change internally!
	 *
	 * @var boolean
	 */
	public $edit;

	/**
	 * ID to return to after editing.
	 *
	 * @var integer
	 */
	public $return_id;

	/**
	 * Id of the page which was just edited.
	 *
	 * @var integer
	 */
	public $lastEdited;

	/**
	 * Constructor
	 */
	public function __construct() {
		$GLOBALS['LANG']->includeLLFile('EXT:lang/locallang_mod_web_perm.xlf');
		$GLOBALS['BE_USER']->modAccess($GLOBALS['MCONF'], TRUE);
	}

	/**
	 * Initialization of the class
	 *
	 * @return void
	 */
	public function init() {
		// Setting GPvars:
		$this->id = (int)GeneralUtility::_GP('id');
		$this->edit = GeneralUtility::_GP('edit');
		$this->return_id = GeneralUtility::_GP('return_id');
		$this->lastEdited = GeneralUtility::_GP('lastEdited');
		// Module name;
		$this->MCONF = $GLOBALS['MCONF'];
		// Page select clause:
		$this->perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
		// Initializing document template object:
		$this->doc = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setModuleTemplate('EXT:perm/Resources/Private/Templates/perm.html');
		$this->doc->form = '<form action="' . $GLOBALS['BACK_PATH'] . 'tce_db.php" method="post" name="editform">';
		$this->doc->loadJavascriptLib('js/jsfunc.updateform.js');
		$this->doc->getPageRenderer()->loadPrototype();
		$this->doc->loadJavascriptLib(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('perm') . 'mod1/perm.js');
		// Setting up the context sensitive menu:
		$this->doc->getContextMenuCode();
		// Set up menus:
		$this->menuConfig();
	}

	/**
	 * Configuration of the menu and initialization of ->MOD_SETTINGS
	 *
	 * @return void
	 */
	public function menuConfig() {
		$level = $GLOBALS['LANG']->getLL('levels');
		$this->MOD_MENU = array(
			'depth' => array(
				1 => '1 ' . $level,
				2 => '2 ' . $level,
				3 => '3 ' . $level,
				4 => '4 ' . $level,
				10 => '10 ' . $level
			)
		);
		// Clean up settings:
		$this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, GeneralUtility::_GP('SET'), $this->MCONF['name']);
	}

	/**
	 * Main function, creating the content for the access editing forms/listings
	 *
	 * @return void
	 */
	public function main() {
		// Access check...
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = BackendUtility::readPageAccess($this->id, $this->perms_clause);
		$access = is_array($this->pageinfo);
		// Checking access:
		if ($this->id && $access || $GLOBALS['BE_USER']->isAdmin() && !$this->id) {
			if ($GLOBALS['BE_USER']->isAdmin() && !$this->id) {
				$this->pageinfo = array('title' => '[root-level]', 'uid' => 0, 'pid' => 0);
			}
			// This decides if the editform can and will be drawn:
			$this->editingAllowed = $this->pageinfo['perms_userid'] == $GLOBALS['BE_USER']->user['uid'] || $GLOBALS['BE_USER']->isAdmin();
			$this->edit = $this->edit && $this->editingAllowed;
			// If $this->edit then these functions are called in the end of the page...
			if ($this->edit) {
				$this->doc->postCode .= $this->doc->wrapScriptTags('
					setCheck("check[perms_user]", "data[pages][' . $this->id . '][perms_user]");
					setCheck("check[perms_group]", "data[pages][' . $this->id . '][perms_group]");
					setCheck("check[perms_everybody]", "data[pages][' . $this->id . '][perms_everybody]");
				');
			}

			// Draw the HTML page header.
			$this->content .= $this->doc->header($GLOBALS['LANG']->getLL('permissions') . ($this->edit ? ': ' . $GLOBALS['LANG']->getLL('Edit') : ''));
			$vContent = $this->doc->getVersionSelector($this->id, 1);
			if ($vContent) {
				$this->content .= $this->doc->section('', $vContent);
			}

			// Main function, branching out:
			if (!$this->edit) {
				$this->notEdit();
			} else {
				$this->doEdit();
			}

			$docHeaderButtons = $this->getButtons();
			$markers['CSH'] = $this->docHeaderButtons['csh'];
			$markers['FUNC_MENU'] = BackendUtility::getFuncMenu($this->id, 'SET[mode]', $this->MOD_SETTINGS['mode'], $this->MOD_MENU['mode']);
			$markers['CONTENT'] = $this->content;

			// Build the <body> for the module
			$this->content = $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		} else {
			// If no access or if ID == zero
			$this->content = $this->doc->header($GLOBALS['LANG']->getLL('permissions'));
		}
		// Renders the module page
		$this->content = $this->doc->render($GLOBALS['LANG']->getLL('permissions'), $this->content);
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return void
	 */
	public function printContent() {
		$this->content = $this->doc->insertStylesAndJS($this->content);
		echo $this->content;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return 	array		all available buttons as an assoc. array
	 */
	protected function getButtons() {
		$buttons = array(
			'csh' => '',
			'view' => '',
			'shortcut' => ''
		);
		// CSH
		$buttons['csh'] = BackendUtility::cshItem('_MOD_web_info', '', $GLOBALS['BACK_PATH'], '', TRUE);
		// View page
		$buttons['view'] = '<a href="#" onclick="' . htmlspecialchars(BackendUtility::viewonclick($this->pageinfo['uid'], $GLOBALS['BACK_PATH'], BackendUtility::BEgetRootLine($this->pageinfo['uid']))) . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.showPage', TRUE) . '">' . IconUtility::getSpriteIcon('actions-document-view') . '</a>';
		// Shortcut
		if ($GLOBALS['BE_USER']->mayMakeShortcut()) {
			$buttons['shortcut'] = $this->doc->makeShortcutIcon('id, edit_record, pointer, new_unique_uid, search_field, search_levels, showLimit', implode(',', array_keys($this->MOD_MENU)), $this->MCONF['name']);
		}
		return $buttons;
	}

	/*****************************
	 *
	 * Listing and Form rendering
	 *
	 *****************************/
	/**
	 * Creating form for editing the permissions	($this->edit = TRUE)
	 * (Adding content to internal content variable)
	 *
	 * @return void
	 */
	public function doEdit() {
		if ($GLOBALS['BE_USER']->workspace != 0) {
			// Adding section with the permission setting matrix:
			$flashMessage = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $GLOBALS['LANG']->getLL('WorkspaceWarningText'), $GLOBALS['LANG']->getLL('WorkspaceWarning'), \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING);
			/** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
			$flashMessageService = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessageService');
			/** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
			$defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
			$defaultFlashMessageQueue->enqueue($flashMessage);
		}
		// Get usernames and groupnames
		$beGroupArray = BackendUtility::getListGroupNames('title,uid');
		$beGroupKeys = array_keys($beGroupArray);
		$beUserArray = BackendUtility::getUserNames();
		if (!$GLOBALS['BE_USER']->isAdmin()) {
			$beUserArray = BackendUtility::blindUserNames($beUserArray, $beGroupKeys, 1);
		}
		$beGroupArray_o = ($beGroupArray = BackendUtility::getGroupNames());
		if (!$GLOBALS['BE_USER']->isAdmin()) {
			$beGroupArray = BackendUtility::blindGroupNames($beGroupArray_o, $beGroupKeys, 1);
		}

		// Owner selector:
		$options = '';

		// flag: is set if the page-userid equals one from the user-list
		$userset = 0;
		foreach ($beUserArray as $uid => $row) {
			if ($uid == $this->pageinfo['perms_userid']) {
				$userset = 1;
				$selected = ' selected="selected"';
			} else {
				$selected = '';
			}
			$options .= '<option value="' . $uid . '"' . $selected . '>' . htmlspecialchars($row['username']) . '</option>';
		}
		$options = '<option value="0"></option>' . $options;
		$selector = '<select name="data[pages][' . $this->id . '][perms_userid]">' . $options . '</select>';
		$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('Owner'), $selector, TRUE);

		// Group selector:
		$options = '';
		$userset = 0;
		foreach ($beGroupArray as $uid => $row) {
			if ($uid == $this->pageinfo['perms_groupid']) {
				$userset = 1;
				$selected = ' selected="selected"';
			} else {
				$selected = '';
			}
			$options .= '<option value="' . $uid . '"' . $selected . '>' . htmlspecialchars($row['title']) . '</option>';
		}

		// If the group was not set AND there is a group for the page
		if (!$userset && $this->pageinfo['perms_groupid']) {
			$options = '<option value="' . $this->pageinfo['perms_groupid'] . '" selected="selected">' . htmlspecialchars($beGroupArray_o[$this->pageinfo['perms_groupid']]['title']) . '</option>' . $options;
		}
		$options = '<option value="0"></option>' . $options;
		$selector = '<select name="data[pages][' . $this->id . '][perms_groupid]">' . $options . '</select>';

		$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('Group'), $selector, TRUE);

		// Permissions checkbox matrix:
		$code = '
			<table class="t3-table" id="typo3-permissionMatrix">
				<thead>
					<tr>
						<th></th>
						<th>' . $GLOBALS['LANG']->getLL('1', TRUE) . '</th>
						<th>' . $GLOBALS['LANG']->getLL('16', TRUE) . '</th>
						<th>' . $GLOBALS['LANG']->getLL('2', TRUE) . '</th>
						<th>' . $GLOBALS['LANG']->getLL('4', TRUE) . '</th>
						<th>' . $GLOBALS['LANG']->getLL('8', TRUE) . '</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><strong>' . $GLOBALS['LANG']->getLL('Owner', TRUE) . '</strong></td>
						<td>' . $this->printCheckBox('perms_user', 1) . '</td>
						<td>' . $this->printCheckBox('perms_user', 5) . '</td>
						<td>' . $this->printCheckBox('perms_user', 2) . '</td>
						<td>' . $this->printCheckBox('perms_user', 3) . '</td>
						<td>' . $this->printCheckBox('perms_user', 4) . '</td>
					</tr>
					<tr>
						<td><strong>' . $GLOBALS['LANG']->getLL('Group', TRUE) . '</strong></td>
						<td>' . $this->printCheckBox('perms_group', 1) . '</td>
						<td>' . $this->printCheckBox('perms_group', 5) . '</td>
						<td>' . $this->printCheckBox('perms_group', 2) . '</td>
						<td>' . $this->printCheckBox('perms_group', 3) . '</td>
						<td>' . $this->printCheckBox('perms_group', 4) . '</td>
					</tr>
					<tr>
						<td><strong>' . $GLOBALS['LANG']->getLL('Everybody', TRUE) . '</strong></td>
						<td>' . $this->printCheckBox('perms_everybody', 1) . '</td>
						<td>' . $this->printCheckBox('perms_everybody', 5) . '</td>
						<td>' . $this->printCheckBox('perms_everybody', 2) . '</td>
						<td>' . $this->printCheckBox('perms_everybody', 3) . '</td>
						<td>' . $this->printCheckBox('perms_everybody', 4) . '</td>
					</tr>
				</tbody>
			</table>

			<input type="hidden" name="data[pages][' . $this->id . '][perms_user]" value="' . $this->pageinfo['perms_user'] . '" />
			<input type="hidden" name="data[pages][' . $this->id . '][perms_group]" value="' . $this->pageinfo['perms_group'] . '" />
			<input type="hidden" name="data[pages][' . $this->id . '][perms_everybody]" value="' . $this->pageinfo['perms_everybody'] . '" />
			' . $this->getRecursiveSelect($this->id, $this->perms_clause) . '
			<input type="submit" name="submit" value="' . $GLOBALS['LANG']->getLL('Save', TRUE) . '" />' . '<input type="submit" value="' . $GLOBALS['LANG']->getLL('Abort', TRUE) . '" onclick="' . htmlspecialchars(('jumpToUrl(' . GeneralUtility::quoteJSvalue((BackendUtility::getModuleUrl('web_perm') . '&id=' . $this->id), TRUE) . '); return false;')) . '" />
			<input type="hidden" name="redirect" value="' . htmlspecialchars((BackendUtility::getModuleUrl('web_perm') . '&mode=' . $this->MOD_SETTINGS['mode'] . '&depth=' . $this->MOD_SETTINGS['depth'] . '&id=' . (int)$this->return_id . '&lastEdited=' . $this->id)) . '" />
			' . \TYPO3\CMS\Backend\Form\FormEngine::getHiddenTokenField('tceAction');

		// Adding section with the permission setting matrix:
		$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('permissions'), $code, TRUE);

		// CSH for permissions setting
		$this->content .= BackendUtility::cshItem('xMOD_csh_corebe', 'perm_module_setting', $GLOBALS['BACK_PATH'], '<br /><br />');

		// Adding help text:
		if ($GLOBALS['BE_USER']->uc['helpText']) {
			$legendText = '<p><strong>' . $GLOBALS['LANG']->getLL('1', TRUE) . '</strong>: ' . $GLOBALS['LANG']->getLL('1_t', TRUE) . '<br />';
			$legendText .= '<strong>' . $GLOBALS['LANG']->getLL('16', TRUE) . '</strong>: ' . $GLOBALS['LANG']->getLL('16_t', TRUE) . '<br />';
			$legendText .= '<strong>' . $GLOBALS['LANG']->getLL('2', TRUE) . '</strong>: ' . $GLOBALS['LANG']->getLL('2_t', TRUE) . '<br />';
			$legendText .= '<strong>' . $GLOBALS['LANG']->getLL('4', TRUE) . '</strong>: ' . $GLOBALS['LANG']->getLL('4_t', TRUE) . '<br />';
			$legendText .= '<strong>' . $GLOBALS['LANG']->getLL('8', TRUE) . '</strong>: ' . $GLOBALS['LANG']->getLL('8_t', TRUE) . '</p>';

			$code = $legendText . '<p>' . $GLOBALS['LANG']->getLL('def', TRUE) . '</p>';

			$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('Legend', TRUE), $code, TRUE);
		}
	}

	/**
	 * Showing the permissions in a tree ($this->edit = FALSE)
	 * (Adding content to internal content variable)
	 *
	 * @return void
	 */
	public function notEdit() {
		// Get usernames and groupnames: The arrays we get in return contains only 1) users which are members of the groups of the current user, 2) groups that the current user is member of
		$beGroupKeys = $GLOBALS['BE_USER']->userGroupsUID;
		$beUserArray = BackendUtility::getUserNames();
		if (!$GLOBALS['BE_USER']->isAdmin()) {
			$beUserArray = BackendUtility::blindUserNames($beUserArray, $beGroupKeys, 0);
		}
		$beGroupArray = BackendUtility::getGroupNames();
		if (!$GLOBALS['BE_USER']->isAdmin()) {
			$beGroupArray = BackendUtility::blindGroupNames($beGroupArray, $beGroupKeys, 0);
		}

		// Length of strings:
		$tLen = 20;

		// Selector for depth:
		$code = $GLOBALS['LANG']->getLL('Depth') . ': ';
		$code .= BackendUtility::getFuncMenu($this->id, 'SET[depth]', $this->MOD_SETTINGS['depth'], $this->MOD_MENU['depth']);
		$this->content .= $this->doc->section('', $code);

		/** @var \TYPO3\CMS\Backend\Tree\View\PageTreeView */
		$tree = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Tree\\View\\PageTreeView');
		$tree->init('AND ' . $this->perms_clause);
		$tree->addField('perms_user', 1);
		$tree->addField('perms_group', 1);
		$tree->addField('perms_everybody', 1);
		$tree->addField('perms_userid', 1);
		$tree->addField('perms_groupid', 1);
		$tree->addField('hidden');
		$tree->addField('fe_group');
		$tree->addField('starttime');
		$tree->addField('endtime');
		$tree->addField('editlock');

		// Creating top icon; the current page
		$HTML = IconUtility::getSpriteIconForRecord('pages', $this->pageinfo);
		$tree->tree[] = array('row' => $this->pageinfo, 'HTML' => $HTML);

		// Create the tree from $this->id:
		$tree->getTree($this->id, $this->MOD_SETTINGS['depth'], '');

		// Make header of table:
		$code = '
			<thead>
				<tr>
					<th colspan="2">&nbsp;</th>
					<th>' . $GLOBALS['LANG']->getLL('Owner', TRUE) . '</th>
					<th align="center">' . $GLOBALS['LANG']->getLL('Group', TRUE) . '</th>
					<th align="center">' . $GLOBALS['LANG']->getLL('Everybody', TRUE) . '</th>
					<th align="center">' . $GLOBALS['LANG']->getLL('EditLock', TRUE) . '</th>
				</tr>
			</thead>
		';

		// Traverse tree:
		foreach ($tree->tree as $data) {
			$cells = array();
			$pageId = $data['row']['uid'];

			// Background colors:
			$bgCol = $this->lastEdited == $pageId ? ' class="bgColor-20"' : '';
			$lE_bgCol = $bgCol;

			// User/Group names:
			$userName = $beUserArray[$data['row']['perms_userid']] ?
					$beUserArray[$data['row']['perms_userid']]['username'] :
					($data['row']['perms_userid'] ? $data['row']['perms_userid'] : '');

			if ($data['row']['perms_userid'] && !$beUserArray[$data['row']['perms_userid']]) {
				$userName = \TYPO3\CMS\Perm\Controller\PermissionAjaxController::renderOwnername($pageId, $data['row']['perms_userid'], htmlspecialchars(GeneralUtility::fixed_lgd_cs($userName, 20)), FALSE);
			} else {
				$userName = \TYPO3\CMS\Perm\Controller\PermissionAjaxController::renderOwnername($pageId, $data['row']['perms_userid'], htmlspecialchars(GeneralUtility::fixed_lgd_cs($userName, 20)));
			}

			$groupName = $beGroupArray[$data['row']['perms_groupid']] ?
					$beGroupArray[$data['row']['perms_groupid']]['title'] :
					($data['row']['perms_groupid'] ? $data['row']['perms_groupid'] : '');

			if ($data['row']['perms_groupid'] && !$beGroupArray[$data['row']['perms_groupid']]) {
				$groupName = \TYPO3\CMS\Perm\Controller\PermissionAjaxController::renderGroupname($pageId, $data['row']['perms_groupid'], htmlspecialchars(GeneralUtility::fixed_lgd_cs($groupName, 20)), FALSE);
			} else {
				$groupName = \TYPO3\CMS\Perm\Controller\PermissionAjaxController::renderGroupname($pageId, $data['row']['perms_groupid'], htmlspecialchars(GeneralUtility::fixed_lgd_cs($groupName, 20)));
			}

			// Seeing if editing of permissions are allowed for that page:
			$editPermsAllowed = $data['row']['perms_userid'] == $GLOBALS['BE_USER']->user['uid'] || $GLOBALS['BE_USER']->isAdmin();

			// First column:
			$cellAttrib = $data['row']['_CSSCLASS'] ? ' class="' . $data['row']['_CSSCLASS'] . '"' : '';
			$cells[] = '<td align="left" nowrap="nowrap"' . ($cellAttrib ? $cellAttrib : $bgCol) . '>' .
					$data['HTML'] . htmlspecialchars(GeneralUtility::fixed_lgd_cs($data['row']['title'], $tLen)) . '</td>';

			// "Edit permissions" -icon
			if ($editPermsAllowed && $pageId) {
				$aHref = BackendUtility::getModuleUrl('web_perm') . '&mode=' . $this->MOD_SETTINGS['mode'] . '&depth=' . $this->MOD_SETTINGS['depth'] . '&id=' . ($data['row']['_ORIG_uid'] ? $data['row']['_ORIG_uid'] : $pageId) . '&return_id=' . $this->id . '&edit=1';
				$cells[] = '<td' . $bgCol . '><a href="' . htmlspecialchars($aHref) . '" title="' . $GLOBALS['LANG']->getLL('ch_permissions', TRUE) . '">' .
						IconUtility::getSpriteIcon('actions-document-open') . '</a></td>';
			} else {
				$cells[] = '<td' . $bgCol . '></td>';
			}

			$cells[] = '
				<td' . $bgCol . ' nowrap="nowrap">' . ($pageId ? \TYPO3\CMS\Perm\Controller\PermissionAjaxController::renderPermissions($data['row']['perms_user'], $pageId, 'user') . ' ' . $userName : '') . '</td>
				<td' . $bgCol . ' nowrap="nowrap">' . ($pageId ? \TYPO3\CMS\Perm\Controller\PermissionAjaxController::renderPermissions($data['row']['perms_group'], $pageId, 'group') . ' ' . $groupName : '') . '</td>
				<td' . $bgCol . ' nowrap="nowrap">' . ($pageId ? ' ' . \TYPO3\CMS\Perm\Controller\PermissionAjaxController::renderPermissions($data['row']['perms_everybody'], $pageId, 'everybody') : '') . '</td>
				<td' . $bgCol . ' nowrap="nowrap">' . ($data['row']['editlock'] ? '<span id="el_' . $pageId . '" class="editlock"><a class="editlock" onclick="WebPermissions.toggleEditLock(\'' . $pageId . '\', \'1\');" title="' . $GLOBALS['LANG']->getLL('EditLock_descr', TRUE) . '">' . IconUtility::getSpriteIcon('status-warning-lock') . '</a></span>' : ($pageId === 0 ? '' : '<span id="el_' . $pageId . '" class="editlock"><a class="editlock" onclick="WebPermissions.toggleEditLock(\'' . $pageId . '\', \'0\');" title="Enable the &raquo;Admin-only&laquo; edit lock for this page">[+]</a></span>')) . '</td>
			';

			// Compile table row:
			$code .= '<tr>' . implode('', $cells) . '</tr>';
		}

		// Wrap rows in table tags:
		$code = '<table class="t3-table" id="typo3-permissionList">' . $code . '</table>';

		// Adding the content as a section:
		$this->content .= $this->doc->section('', $code);

		// CSH for permissions setting
		$this->content .= BackendUtility::cshItem('xMOD_csh_corebe', 'perm_module', $GLOBALS['BACK_PATH'], '<br />|');

		// Creating legend table:
		$legendText = '<strong>' . $GLOBALS['LANG']->getLL('1', TRUE) . '</strong>: ' . $GLOBALS['LANG']->getLL('1_t', TRUE);
		$legendText .= '<br /><strong>' . $GLOBALS['LANG']->getLL('16', TRUE) . '</strong>: ' . $GLOBALS['LANG']->getLL('16_t', TRUE);
		$legendText .= '<br /><strong>' . $GLOBALS['LANG']->getLL('2', TRUE) . '</strong>: ' . $GLOBALS['LANG']->getLL('2_t', TRUE);
		$legendText .= '<br /><strong>' . $GLOBALS['LANG']->getLL('4', TRUE) . '</strong>: ' . $GLOBALS['LANG']->getLL('4_t', TRUE);
		$legendText .= '<br /><strong>' . $GLOBALS['LANG']->getLL('8', TRUE) . '</strong>: ' . $GLOBALS['LANG']->getLL('8_t', TRUE);

		$code = '<div id="permission-information">
					<img' . IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/legend.gif', 'width="86" height="75"') . ' alt="" />
				<div class="text">' . $legendText . '</div></div>';

		$code .= '<div id="perm-legend">' . $GLOBALS['LANG']->getLL('def', TRUE);
		$code .= '<br /><br />' . IconUtility::getSpriteIcon('status-status-permission-granted') . ': ' . $GLOBALS['LANG']->getLL('A_Granted', TRUE);
		$code .= '<br />' . IconUtility::getSpriteIcon('status-status-permission-denied') . ': ' . $GLOBALS['LANG']->getLL('A_Denied', TRUE);
		$code .= '</div>';

		// Adding section with legend code:
		$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('Legend') . ':', $code, TRUE, TRUE);
	}

	/*****************************
	 *
	 * Helper functions
	 *
	 *****************************/
	/**
	 * Print a checkbox for the edit-permission form
	 *
	 * @param string $checkName Checkbox name key
	 * @param integer $num Checkbox number index
	 * @return string HTML checkbox
	 */
	public function printCheckBox($checkName, $num) {
		$onclick = 'checkChange(\'check[' . $checkName . ']\', \'data[pages][' . $GLOBALS['SOBE']->id . '][' . $checkName . ']\')';
		return '<input type="checkbox" name="check[' . $checkName . '][' . $num . ']" onclick="' . htmlspecialchars($onclick) . '" /><br />';
	}

	/**
	 * Finding tree and offer setting of values recursively.
	 *
	 * @param integer $id Page id.
	 * @param string $perms_clause Select clause
	 * @return string Select form element for recursive levels (if any levels are found)
	 */
	public function getRecursiveSelect($id, $perms_clause) {
		// Initialize tree object:
		$tree = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Tree\\View\\PageTreeView');
		$tree->init('AND ' . $perms_clause);
		$tree->addField('perms_userid', 1);
		$tree->makeHTML = 0;
		$tree->setRecs = 1;
		// Make tree:
		$tree->getTree($id, $this->getLevels, '');
		// If there are a hierarchy of page ids, then...
		if ($GLOBALS['BE_USER']->user['uid'] && count($tree->orig_ids_hierarchy)) {
			// Init:
			$label_recur = $GLOBALS['LANG']->getLL('recursive');
			$label_levels = $GLOBALS['LANG']->getLL('levels');
			$label_pA = $GLOBALS['LANG']->getLL('pages_affected');
			$theIdListArr = array();
			$opts = '
						<option value=""></option>';
			// Traverse the number of levels we want to allow recursive setting of permissions for:
			for ($a = $this->getLevels; $a > 0; $a--) {
				if (is_array($tree->orig_ids_hierarchy[$a])) {
					foreach ($tree->orig_ids_hierarchy[$a] as $theId) {
						if ($GLOBALS['BE_USER']->isAdmin() || $GLOBALS['BE_USER']->user['uid'] == $tree->recs[$theId]['perms_userid']) {
							$theIdListArr[] = $theId;
						}
					}
					$lKey = $this->getLevels - $a + 1;
					$opts .= '
						<option value="' . htmlspecialchars(implode(',', $theIdListArr)) . '">' . htmlspecialchars($label_recur . ' ' . $lKey . ' ' . $label_levels, ENT_COMPAT, 'UTF-8', FALSE) . ' (' . count($theIdListArr) . ' ' . $label_pA . ')' . '</option>';
				}
			}
			// Put the selector box together:
			$theRecursiveSelect = '<br />
					<select name="mirror[pages][' . $id . ']">
						' . $opts . '
					</select>
				<br /><br />';
		} else {
			$theRecursiveSelect = '';
		}
		// Return selector box element:
		return $theRecursiveSelect;
	}

}
