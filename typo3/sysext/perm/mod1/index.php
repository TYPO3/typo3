<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Module: Permission setting
 *
 * $Id$
 * Revised for TYPO3 3.6 November/2003 by Kasper Skårhøj
 * XHTML compliant
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   90: class SC_mod_web_perm_index
 *  194:     public function init()
 *  246:     public function menuConfig()
 *  277:     public function main()
 *  344:     public function printContent()
 *  354:     private function getButtons()
 *
 *              SECTION: Listing and Form rendering
 *  398:     public function doEdit()
 *  545:     public function notEdit()
 *
 *              SECTION: Helper functions
 *  739:     public function printCheckBox($checkName, $num)
 *  752:     public function printPerms($int, $pageId = 0, $who = 'user')
 *  772:     public function groupPerms($row, $firstGroup)
 *  789:     public function getRecursiveSelect($id,$perms_clause)
 *
 * TOTAL FUNCTIONS: 11
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

unset($MCONF);
require('conf.php');
require($BACK_PATH.'init.php');
require($BACK_PATH.'template.php');
require('class.sc_mod_web_perm_ajax.php');
$LANG->includeLLFile('EXT:lang/locallang_mod_web_perm.xml');

$BE_USER->modAccess($MCONF,1);






/**
 * Module: Permission setting
 *
 * Script Class for the Web > Access module
 * This module lets you view and change permissions for pages.
 *
 * Variables:
 * $this->MOD_SETTINGS['depth']: intval 1-3: decides the depth of the list
 * $this->MOD_SETTINGS['mode']: 'perms' / '': decides if we view a user-overview or the permissions.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	Andreas Kundoch <typo3@mehrwert.de>
 * @package	TYPO3
 * @subpackage	core
 * @version	$Id$
 */
class SC_mod_web_perm_index {

	/**
	 * Number of levels to enable recursive settings for
	 * @var integer
	 */
	public $getLevels = 10;

	/**
	 * Module config
	 * Internal static
	 * @var array
	 */
	protected $MCONF = array();

	/**
	 * Document Template Object
	 * @var template
	 */
	public $doc;

	/**
	 * Content accumulation
	 * @var string
	 */
	public $content;

	/**
	 * Module menu
	 * @var array
	 */
	public $MOD_MENU = array();

	/**
	 * Module settings, cleansed.
	 * @var aray
	 */
	public $MOD_SETTINGS = array();

	/**
	 * Page select permissions
	 * @var string
	 */
	public $perms_clause;

	/**
	 * Current page record
	 * @var array
	 */
	public $pageinfo;

	/**
	 *  Background color 1
	 * @var string
	 */
	public $color;

	/**
	 * Background color 2
	 * @var string
	 */
	public $color2;

	/**
	 * Background color 3
	 * @var string
	 */
	public $color3;

	/**
	 * Set internally if the current user either OWNS the page OR is admin user!
	 * @var boolean
	 */
	public $editingAllowed;

	/**
	 * Internal, static: GPvars: Page id.
	 * @var integer
	 */
	public $id;

	/**
	 * If set, editing of the page permissions will occur (showing the editing screen). Notice:
	 * This value is evaluated against permissions and so it will change internally!
	 * @var boolean
	 */
	public $edit;

	/**
	 * ID to return to after editing.
	 * @var integer
	 */
	public $return_id;

	/**
	 * Id of the page which was just edited.
	 * @var integer
	 */
	public $lastEdited;

	/**
	 * Initialization of the class
	 *
	 * @return	void
	 */
	public function init() {

			// Setting GPvars:
		$this->id = intval(t3lib_div::_GP('id'));
		$this->edit = t3lib_div::_GP('edit');
		$this->return_id = t3lib_div::_GP('return_id');
		$this->lastEdited = t3lib_div::_GP('lastEdited');

			// Module name;
		$this->MCONF = $GLOBALS['MCONF'];

			// Page select clause:
		$this->perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);

			// Initializing document template object:
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setModuleTemplate('templates/perm.html');
		$this->doc->form = '<form action="'.$GLOBALS['BACK_PATH'].'tce_db.php" method="post" name="editform">';
		$this->doc->loadJavascriptLib('../t3lib/jsfunc.updateform.js');
		$this->doc->getPageRenderer()->loadPrototype();
		$this->doc->loadJavascriptLib(TYPO3_MOD_PATH . 'perm.js');

			// Setting up the context sensitive menu:
		$this->doc->getContextMenuCode();

			// Set up menus:
		$this->menuConfig();
	}

	/**
	 * Configuration of the menu and initialization of ->MOD_SETTINGS
	 *
	 * @return	void
	 */
	public function menuConfig() {
		global $LANG;

			// MENU-ITEMS:
			// If array, then it's a selector box menu
			// If empty string it's just a variable, that'll be saved.
			// Values NOT in this array will not be saved in the settings-array for the module.
		$temp = $LANG->getLL('levels');
		$this->MOD_MENU = array(
			'depth' => array(
				1 => '1 '.$temp,
				2 => '2 '.$temp,
				3 => '3 '.$temp,
				4 => '4 '.$temp,
				10 => '10 '.$temp
			),
			'mode' => array(
				0 => $LANG->getLL('user_overview'),
				'perms' => $LANG->getLL('permissions')
			)
		);

			// Clean up settings:
		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::_GP('SET'), $this->MCONF['name']);
	}

	/**
	 * Main function, creating the content for the access editing forms/listings
	 *
	 * @return	void
	 */
	public function main() {
		global $BE_USER, $LANG;

			// Access check...
			// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id, $this->perms_clause);
		$access = is_array($this->pageinfo);

			// Checking access:
		if (($this->id && $access) || ($BE_USER->isAdmin() && !$this->id)) {
			if ($BE_USER->isAdmin() && !$this->id)	{
				$this->pageinfo=array('title' => '[root-level]','uid'=>0,'pid'=>0);
			}

				// This decides if the editform can and will be drawn:
			$this->editingAllowed = ($this->pageinfo['perms_userid']==$BE_USER->user['uid'] || $BE_USER->isAdmin());
			$this->edit = $this->edit && $this->editingAllowed;

				// If $this->edit then these functions are called in the end of the page...
			if ($this->edit)	{
				$this->doc->postCode.= $this->doc->wrapScriptTags('
					setCheck("check[perms_user]","data[pages]['.$this->id.'][perms_user]");
					setCheck("check[perms_group]","data[pages]['.$this->id.'][perms_group]");
					setCheck("check[perms_everybody]","data[pages]['.$this->id.'][perms_everybody]");
				');
			}

				// Draw the HTML page header.
			$this->content.=$this->doc->header($LANG->getLL('permissions') . ($this->edit ? ': '.$LANG->getLL('Edit') : ''));
			$this->content.=$this->doc->spacer(5);

			$vContent = $this->doc->getVersionSelector($this->id,1);
			if ($vContent) {
				$this->content .= $this->doc->section('',$vContent);
			}

				// Main function, branching out:
			if (!$this->edit) {
				$this->notEdit();
			} else {
				$this->doEdit();
			}

			$docHeaderButtons = $this->getButtons();

			$markers['CSH'] = $this->docHeaderButtons['csh'];
			$markers['FUNC_MENU'] = t3lib_BEfunc::getFuncMenu($this->id, 'SET[mode]', $this->MOD_SETTINGS['mode'], $this->MOD_MENU['mode']);
			$markers['CONTENT'] = $this->content;

				// Build the <body> for the module
			$this->content = $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		} else {
				// If no access or if ID == zero
			$this->content =$this->doc->header($LANG->getLL('permissions'));
		}
			// Renders the module page
		$this->content = $this->doc->render(
			$LANG->getLL('permissions'),
			$this->content
		);
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return	void
	 */
	public function printContent() {
		$this->content = $this->doc->insertStylesAndJS($this->content);
		echo $this->content;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return	array		all available buttons as an assoc. array
	 */
	protected function getButtons() {

		$buttons = array(
			'csh' => '',
			'view' => '',
			'record_list' => '',
			'shortcut' => '',
		);
			// CSH
		$buttons['csh'] = t3lib_BEfunc::cshItem('_MOD_web_info', '', $GLOBALS['BACK_PATH'], '', TRUE);

			// View page
		$buttons['view'] = '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::viewonclick($this->pageinfo['uid'], $GLOBALS['BACK_PATH'], t3lib_BEfunc::BEgetRootLine($this->pageinfo['uid']))) . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.showPage', 1) . '">' .
					t3lib_iconWorks::getSpriteIcon('actions-document-view') .
				'</a>';

			// Shortcut
		if ($GLOBALS['BE_USER']->mayMakeShortcut())	{
			$buttons['shortcut'] = $this->doc->makeShortcutIcon('id, edit_record, pointer, new_unique_uid, search_field, search_levels, showLimit', implode(',', array_keys($this->MOD_MENU)), $this->MCONF['name']);
		}

			// If access to Web>List for user, then link to that module.
		$buttons['record_list'] = t3lib_BEfunc::getListViewLink(
			array(
				'id' => $this->pageinfo['uid'],
				'returnUrl' => t3lib_div::getIndpEnv('REQUEST_URI'),
			),
			$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.showList')
		);
		return $buttons;
	}








	/*****************************
	 *
	 * Listing and Form rendering
	 *
	 *****************************/

	/**
	 * Creating form for editing the permissions	($this->edit = true)
	 * (Adding content to internal content variable)
	 *
	 * @return	void
	 */
	public function doEdit() {
		global $BE_USER,$LANG;

		if ($BE_USER->workspace != 0) {
				// Adding section with the permission setting matrix:
			$lockedMessage = t3lib_div::makeInstance(
				't3lib_FlashMessage',
				$LANG->getLL('WorkspaceWarningText'),
				$LANG->getLL('WorkspaceWarning'),
				t3lib_FlashMessage::WARNING
			);
			t3lib_FlashMessageQueue::addMessage($lockedMessage);
		}

			// Get usernames and groupnames
		$beGroupArray = t3lib_BEfunc::getListGroupNames('title,uid');
		$beGroupKeys = array_keys($beGroupArray);

		$beUserArray = t3lib_BEfunc::getUserNames();
		if (!$GLOBALS['BE_USER']->isAdmin()) {
			$beUserArray = t3lib_BEfunc::blindUserNames($beUserArray,$beGroupKeys,1);
		}
		$beGroupArray_o = $beGroupArray = t3lib_BEfunc::getGroupNames();
		if (!$GLOBALS['BE_USER']->isAdmin()) {
			$beGroupArray = t3lib_BEfunc::blindGroupNames($beGroupArray_o,$beGroupKeys,1);
		}
		$firstGroup = $beGroupKeys[0] ? $beGroupArray[$beGroupKeys[0]] : '';	// data of the first group, the user is member of


			// Owner selector:
		$options='';
		$userset=0;	// flag: is set if the page-userid equals one from the user-list
		foreach($beUserArray as $uid => $row)	{
			if ($uid==$this->pageinfo['perms_userid'])	{
				$userset = 1;
				$selected=' selected="selected"';
			} else {
				$selected='';
			}
			$options.='
				<option value="'.$uid.'"'.$selected.'>'.htmlspecialchars($row['username']).'</option>';
		}
		$options='
				<option value="0"></option>'.$options;
		$selector='
			<select name="data[pages]['.$this->id.'][perms_userid]">
				'.$options.'
			</select>';

		$this->content.=$this->doc->section($LANG->getLL('Owner').':',$selector);


			// Group selector:
		$options='';
		$userset=0;
		foreach($beGroupArray as $uid => $row)	{
			if ($uid==$this->pageinfo['perms_groupid'])	{
				$userset = 1;
				$selected=' selected="selected"';
			} else {
				$selected='';
			}
			$options.='
				<option value="'.$uid.'"'.$selected.'>'.htmlspecialchars($row['title']).'</option>';
		}
		if (!$userset && $this->pageinfo['perms_groupid'])	{	// If the group was not set AND there is a group for the page
			$options='
				<option value="'.$this->pageinfo['perms_groupid'].'" selected="selected">'.
						htmlspecialchars($beGroupArray_o[$this->pageinfo['perms_groupid']]['title']).
						'</option>'.
						$options;
		}
		$options='
				<option value="0"></option>'.$options;
		$selector='
			<select name="data[pages]['.$this->id.'][perms_groupid]">
				'.$options.'
			</select>';

		$this->content.=$this->doc->divider(5);
		$this->content.=$this->doc->section($LANG->getLL('Group').':',$selector);

			// Permissions checkbox matrix:
		$code='
			<table border="0" cellspacing="2" cellpadding="0" id="typo3-permissionMatrix">
				<tr>
					<td></td>
					<td class="bgColor2">'.str_replace(' ','<br />',$LANG->getLL('1',1)).'</td>
					<td class="bgColor2">'.str_replace(' ','<br />',$LANG->getLL('16',1)).'</td>
					<td class="bgColor2">'.str_replace(' ','<br />',$LANG->getLL('2',1)).'</td>
					<td class="bgColor2">'.str_replace(' ','<br />',$LANG->getLL('4',1)).'</td>
					<td class="bgColor2">'.str_replace(' ','<br />',$LANG->getLL('8',1)).'</td>
				</tr>
				<tr>
					<td align="right" class="bgColor2">'.$LANG->getLL('Owner',1).'</td>
					<td class="bgColor-20">'.$this->printCheckBox('perms_user',1).'</td>
					<td class="bgColor-20">'.$this->printCheckBox('perms_user',5).'</td>
					<td class="bgColor-20">'.$this->printCheckBox('perms_user',2).'</td>
					<td class="bgColor-20">'.$this->printCheckBox('perms_user',3).'</td>
					<td class="bgColor-20">'.$this->printCheckBox('perms_user',4).'</td>
				</tr>
				<tr>
					<td align="right" class="bgColor2">'.$LANG->getLL('Group',1).'</td>
					<td class="bgColor-20">'.$this->printCheckBox('perms_group',1).'</td>
					<td class="bgColor-20">'.$this->printCheckBox('perms_group',5).'</td>
					<td class="bgColor-20">'.$this->printCheckBox('perms_group',2).'</td>
					<td class="bgColor-20">'.$this->printCheckBox('perms_group',3).'</td>
					<td class="bgColor-20">'.$this->printCheckBox('perms_group',4).'</td>
				</tr>
				<tr>
					<td align="right" class="bgColor2">'.$LANG->getLL('Everybody',1).'</td>
					<td class="bgColor-20">'.$this->printCheckBox('perms_everybody',1).'</td>
					<td class="bgColor-20">'.$this->printCheckBox('perms_everybody',5).'</td>
					<td class="bgColor-20">'.$this->printCheckBox('perms_everybody',2).'</td>
					<td class="bgColor-20">'.$this->printCheckBox('perms_everybody',3).'</td>
					<td class="bgColor-20">'.$this->printCheckBox('perms_everybody',4).'</td>
				</tr>
			</table>
			<br />

			<input type="hidden" name="data[pages]['.$this->id.'][perms_user]" value="'.$this->pageinfo['perms_user'].'" />
			<input type="hidden" name="data[pages]['.$this->id.'][perms_group]" value="'.$this->pageinfo['perms_group'].'" />
			<input type="hidden" name="data[pages]['.$this->id.'][perms_everybody]" value="'.$this->pageinfo['perms_everybody'].'" />
			'.$this->getRecursiveSelect($this->id,$this->perms_clause).'
			<input type="submit" name="submit" value="'.$LANG->getLL('Save',1).'" />'.
			'<input type="submit" value="'.$LANG->getLL('Abort',1).'" onclick="'.htmlspecialchars('jumpToUrl(\'index.php?id='.$this->id.'\'); return false;').'" />
			<input type="hidden" name="redirect" value="'.htmlspecialchars(TYPO3_MOD_PATH.'index.php?mode='.$this->MOD_SETTINGS['mode'].'&depth='.$this->MOD_SETTINGS['depth'].'&id='.intval($this->return_id).'&lastEdited='.$this->id).'" />
		' . t3lib_TCEforms::getHiddenTokenField('tceAction');

			// Adding section with the permission setting matrix:
		$this->content.=$this->doc->divider(5);
		$this->content.=$this->doc->section($LANG->getLL('permissions').':',$code);

			// CSH for permissions setting
		$this->content.= t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'perm_module_setting', $GLOBALS['BACK_PATH'], '<br /><br />');

			// Adding help text:
		if ($BE_USER->uc['helpText'])	{
			$this->content.=$this->doc->divider(20);
			$legendText = '<strong>'.$LANG->getLL('1',1).'</strong>: '.$LANG->getLL('1_t',1);
			$legendText.= '<br /><strong>'.$LANG->getLL('16',1).'</strong>: '.$LANG->getLL('16_t',1);
			$legendText.= '<br /><strong>'.$LANG->getLL('2',1).'</strong>: '.$LANG->getLL('2_t',1);
			$legendText.= '<br /><strong>'.$LANG->getLL('4',1).'</strong>: '.$LANG->getLL('4_t',1);
			$legendText.= '<br /><strong>'.$LANG->getLL('8',1).'</strong>: '.$LANG->getLL('8_t',1);

			$code=$legendText.'<br /><br />'.$LANG->getLL('def',1);
			$this->content.=$this->doc->section($LANG->getLL('Legend',1).':',$code);
		}
	}

	/**
	 * Showing the permissions in a tree ($this->edit = false)
	 * (Adding content to internal content variable)
	 *
	 * @return	void
	 */
	public function notEdit() {
		global $BE_USER,$LANG,$BACK_PATH;

			// Get usernames and groupnames: The arrays we get in return contains only 1) users which are members of the groups of the current user, 2) groups that the current user is member of
		$beGroupKeys = $BE_USER->userGroupsUID;
		$beUserArray = t3lib_BEfunc::getUserNames();
		if (!$GLOBALS['BE_USER']->isAdmin()) {
			$beUserArray = t3lib_BEfunc::blindUserNames($beUserArray,$beGroupKeys,0);
		}
		$beGroupArray = t3lib_BEfunc::getGroupNames();
		if (!$GLOBALS['BE_USER']->isAdmin()) {
			$beGroupArray = t3lib_BEfunc::blindGroupNames($beGroupArray,$beGroupKeys,0);
		}

			// Length of strings:
		$tLen= ($this->MOD_SETTINGS['mode']=='perms' ? 20 : 30);


			// Selector for depth:
		$code.=$LANG->getLL('Depth').': ';
		$code.=t3lib_BEfunc::getFuncMenu($this->id,'SET[depth]',$this->MOD_SETTINGS['depth'],$this->MOD_MENU['depth']);
		$this->content.=$this->doc->section('',$code);
		$this->content.=$this->doc->spacer(5);

			// Initialize tree object:
		$tree = t3lib_div::makeInstance('t3lib_pageTree');
		$tree->init('AND '.$this->perms_clause);

		$tree->addField('perms_user',1);
		$tree->addField('perms_group',1);
		$tree->addField('perms_everybody',1);
		$tree->addField('perms_userid',1);
		$tree->addField('perms_groupid',1);
		$tree->addField('hidden');
		$tree->addField('fe_group');
		$tree->addField('starttime');
		$tree->addField('endtime');
		$tree->addField('editlock');

			// Creating top icon; the current page
		$HTML=t3lib_iconWorks::getSpriteIconForRecord('pages',$this->pageinfo);
		$tree->tree[] = array('row'=>$this->pageinfo,'HTML'=>$HTML);

			// Create the tree from $this->id:
		$tree->getTree($this->id,$this->MOD_SETTINGS['depth'],'');

			// Make header of table:
		$code='';
		if ($this->MOD_SETTINGS['mode']=='perms') {
			$code.='
				<tr class="t3-row-header">
					<td colspan="2">&nbsp;</td>
					<td><img' . t3lib_iconWorks::skinImg($BACK_PATH, 'gfx/line.gif', 'width="5" height="16"') . ' alt="" /></td>
					<td>' . $LANG->getLL('Owner', TRUE) . '</td>
					<td><img' . t3lib_iconWorks::skinImg($BACK_PATH, 'gfx/line.gif', 'width="5" height="16"') . ' alt="" /></td>
					<td align="center">' . $LANG->getLL('Group', TRUE) . '</td>
					<td><img' . t3lib_iconWorks::skinImg($BACK_PATH, 'gfx/line.gif', 'width="5" height="16"') . ' alt="" /></td>
					<td align="center">' . $LANG->getLL('Everybody', TRUE) . '</td>
					<td><img' . t3lib_iconWorks::skinImg($BACK_PATH, 'gfx/line.gif', 'width="5" height="16"') . ' alt="" /></td>
					<td align="center">' . $LANG->getLL('EditLock', TRUE) . '</td>
				</tr>
			';
		} else {
			$code.='
				<tr class="t3-row-header">
					<td colspan="2">&nbsp;</td>
					<td><img' . t3lib_iconWorks::skinImg($BACK_PATH, 'gfx/line.gif', 'width="5" height="16"') . ' alt="" /></td>
					<td align="center" nowrap="nowrap">' . $LANG->getLL('User', TRUE) . ': ' . htmlspecialchars($BE_USER->user['username']) . '</td>
					' . (!$BE_USER->isAdmin() ? '<td><img' . t3lib_iconWorks::skinImg($BACK_PATH, 'gfx/line.gif', 'width="5" height="16"') . ' alt="" /></td>
					<td align="center">' . $LANG->getLL('EditLock', TRUE) . '</td>' : '') . '
				</tr>';
		}

			// Traverse tree:
		foreach ($tree->tree as $data) {
			$cells = array();
			$pageId = $data['row']['uid'];

				// Background colors:
			$bgCol = ($this->lastEdited == $pageId ? ' class="bgColor-20"' : '');
			$lE_bgCol = $bgCol;

				// User/Group names:
			$userName = $beUserArray[$data['row']['perms_userid']] ? $beUserArray[$data['row']['perms_userid']]['username'] : ($data['row']['perms_userid'] ?  $data['row']['perms_userid'] : '');
			if ($data['row']['perms_userid'] && (!$beUserArray[$data['row']['perms_userid']])) {
				$userName = SC_mod_web_perm_ajax::renderOwnername($pageId, $data['row']['perms_userid'], htmlspecialchars(t3lib_div::fixed_lgd_cs($userName, 20)), false);
			} else {
				$userName = SC_mod_web_perm_ajax::renderOwnername($pageId, $data['row']['perms_userid'], htmlspecialchars(t3lib_div::fixed_lgd_cs($userName, 20)));
			}

			$groupName = $beGroupArray[$data['row']['perms_groupid']] ? $beGroupArray[$data['row']['perms_groupid']]['title']  : ($data['row']['perms_groupid'] ?  $data['row']['perms_groupid']  : '');
			if ($data['row']['perms_groupid'] && (!$beGroupArray[$data['row']['perms_groupid']])) {
				$groupName = SC_mod_web_perm_ajax::renderGroupname($pageId, $data['row']['perms_groupid'], htmlspecialchars(t3lib_div::fixed_lgd_cs($groupName, 20)), false);
			} else {
				$groupName = SC_mod_web_perm_ajax::renderGroupname($pageId, $data['row']['perms_groupid'], htmlspecialchars(t3lib_div::fixed_lgd_cs($groupName, 20)));
			}

				// Seeing if editing of permissions are allowed for that page:
			$editPermsAllowed = ($data['row']['perms_userid'] == $BE_USER->user['uid'] || $BE_USER->isAdmin());


				// First column:
			$cellAttrib = ($data['row']['_CSSCLASS'] ? ' class="'.$data['row']['_CSSCLASS'].'"' : '');
			$cells[]='
					<td align="left" nowrap="nowrap"'.($cellAttrib ? $cellAttrib : $bgCol).'>'.$data['HTML'].htmlspecialchars(t3lib_div::fixed_lgd_cs($data['row']['title'],$tLen)).'&nbsp;</td>';

				// "Edit permissions" -icon
			if ($editPermsAllowed && $pageId) {
				$aHref = 'index.php?mode='.$this->MOD_SETTINGS['mode'].'&depth='.$this->MOD_SETTINGS['depth'].'&id='.($data['row']['_ORIG_uid'] ? $data['row']['_ORIG_uid'] : $pageId).'&return_id='.$this->id.'&edit=1';
				$cells[]='
					<td'.$bgCol.'><a href="'.htmlspecialchars($aHref).'" title="'.$LANG->getLL('ch_permissions',1).'">' . t3lib_iconWorks::getSpriteIcon('actions-document-open') . '</a></td>';
			} else {
				$cells[]='
					<td'.$bgCol.'></td>';
			}

				// Rest of columns (depending on mode)
			if ($this->MOD_SETTINGS['mode'] == 'perms') {
				$cells[]='
					<td' . $bgCol . ' class="center"><img' . t3lib_iconWorks::skinImg($BACK_PATH, 'gfx/line.gif', 'width="5" height="16"') . ' alt="" /></td>
					<td'.$bgCol.' nowrap="nowrap">'.($pageId ? SC_mod_web_perm_ajax::renderPermissions($data['row']['perms_user'], $pageId, 'user').' '.$userName : '').'</td>

					<td' . $bgCol . ' class="center"><img' . t3lib_iconWorks::skinImg($BACK_PATH, 'gfx/line.gif', 'width="5" height="16"') . ' alt="" /></td>
					<td'.$bgCol.' nowrap="nowrap">'.($pageId ? SC_mod_web_perm_ajax::renderPermissions($data['row']['perms_group'], $pageId, 'group').' '.$groupName : '').'</td>

					<td' . $bgCol . ' class="center"><img' . t3lib_iconWorks::skinImg($BACK_PATH, 'gfx/line.gif', 'width="5" height="16"') . ' alt="" /></td>
					<td'.$bgCol.' nowrap="nowrap">'.($pageId ? ' '.SC_mod_web_perm_ajax::renderPermissions($data['row']['perms_everybody'], $pageId, 'everybody') : '').'</td>

					<td' . $bgCol . ' class="center"><img' . t3lib_iconWorks::skinImg($BACK_PATH, 'gfx/line.gif', 'width="5" height="16"') . ' alt="" /></td>
					<td'.$bgCol.' nowrap="nowrap">'.($data['row']['editlock']?'<span id="el_'.$pageId.'" class="editlock"><a class="editlock" onclick="WebPermissions.toggleEditLock(\''.$pageId.'\', \'1\');" title="'.$LANG->getLL('EditLock_descr',1).'">' .
						t3lib_iconWorks::getSpriteIcon('status-warning-lock') . '</a></span>' : ( $pageId === 0 ? '' : '<span id="el_'.$pageId.'" class="editlock"><a class="editlock" onclick="WebPermissions.toggleEditLock(\''.$pageId.'\', \'0\');" title="Enable the &raquo;Admin-only&laquo; edit lock for this page">[+]</a></span>')).'</td>
				';
			} else {
				$cells[]='
					<td' . $bgCol . ' class="center"><img' . t3lib_iconWorks::skinImg($BACK_PATH, 'gfx/line.gif', 'width="5" height="16"') . ' alt="" /></td>';

				$bgCol = ($BE_USER->user['uid'] == $data['row']['perms_userid'] ? ' class="bgColor-20"' : $lE_bgCol);

				// FIXME $owner undefined
				$cells[]='
					<td'.$bgCol.' nowrap="nowrap" align="center">'.($pageId ? $owner.SC_mod_web_perm_ajax::renderPermissions($BE_USER->calcPerms($data['row']), $pageId, 'user') : '').'</td>
					'.(!$BE_USER->isAdmin()?'
					<td' . $bgCol . ' class="center"><img' . t3lib_iconWorks::skinImg($BACK_PATH, 'gfx/line.gif', 'width="5" height="16"') . ' alt="" /></td>
					<td'.$bgCol.' nowrap="nowrap">'.($data['row']['editlock'] ? t3lib_iconWorks::getSpriteIcon('status-warning-lock', array('title' => $LANG->getLL('EditLock_descr', TRUE))) : '').'</td>
					':'');
				$bgCol = $lE_bgCol;
			}

				// Compile table row:
			$code .= '
				<tr>
					'.implode('
					',$cells).'
				</tr>';
		}

			// Wrap rows in table tags:
		$code = '<table border="0" cellspacing="0" cellpadding="0" id="typo3-permissionList">' . $code . '</table>';

			// Adding the content as a section:
		$this->content.=$this->doc->section('',$code);

			// CSH for permissions setting
		$this->content.= t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'perm_module', $GLOBALS['BACK_PATH'], '<br />|');

			// Creating legend table:
		$legendText = '<strong>'.$LANG->getLL('1',1).'</strong>: '.$LANG->getLL('1_t',1);
		$legendText.= '<br /><strong>'.$LANG->getLL('16',1).'</strong>: '.$LANG->getLL('16_t',1);
		$legendText.= '<br /><strong>'.$LANG->getLL('2',1).'</strong>: '.$LANG->getLL('2_t',1);
		$legendText.= '<br /><strong>'.$LANG->getLL('4',1).'</strong>: '.$LANG->getLL('4_t',1);
		$legendText.= '<br /><strong>'.$LANG->getLL('8',1).'</strong>: '.$LANG->getLL('8_t',1);

		$code='<table border="0" id="typo3-legendTable">
			<tr>
				<td valign="top">
					<img' . t3lib_iconWorks::skinImg($BACK_PATH, 'gfx/legend.gif', 'width="86" height="75"') . ' alt="" />
				</td>
				<td valign="top" nowrap="nowrap">'.$legendText.'</td>
			</tr>
		</table>';
		$code.='<div id="perm-legend">'.$LANG->getLL('def',1);
		$code.='<br /><br />'.t3lib_iconWorks::getSpriteIcon('status-status-permission-granted').': '.$LANG->getLL('A_Granted', 1);
		$code.='<br />'.t3lib_iconWorks::getSpriteIcon('status-status-permission-denied').': '.$LANG->getLL('A_Denied', 1);
		$code.='</div>';

			// Adding section with legend code:
		$this->content.=$this->doc->spacer(20);
		$this->content.=$this->doc->section($LANG->getLL('Legend').':',$code,0,1);
	}







	/*****************************
	 *
	 * Helper functions
	 *
	 *****************************/

	/**
	 * Print a checkbox for the edit-permission form
	 *
	 * @param	string		Checkbox name key
	 * @param	integer		Checkbox number index
	 * @return	string		HTML checkbox
	 */
	public function printCheckBox($checkName, $num) {
		$onclick = 'checkChange(\'check['.$checkName.']\', \'data[pages]['.$GLOBALS['SOBE']->id.']['.$checkName.']\')';
		return '<input type="checkbox" name="check['.$checkName.']['.$num.']" onclick="'.htmlspecialchars($onclick).'" /><br />';
	}


	/**
	 * Returns the permissions for a group based of the perms_groupid of $row. If the $row[perms_groupid] equals the $firstGroup[uid] then the function returns perms_everybody OR'ed with perms_group, else just perms_everybody
	 *
	 * @param	array		Row array (from pages table)
	 * @param	array		First group data
	 * @return	integer		Integer: Combined permissions.
	 */
	public function groupPerms($row, $firstGroup) {
		if (is_array($row))	{
			$out = intval($row['perms_everybody']);
			if ($row['perms_groupid'] && $firstGroup['uid']==$row['perms_groupid'])	{
				$out |= intval($row['perms_group']);
			}
			return $out;
		}
	}

	/**
	 * Finding tree and offer setting of values recursively.
	 *
	 * @param	integer		Page id.
	 * @param	string		Select clause
	 * @return	string		Select form element for recursive levels (if any levels are found)
	 */
	public function getRecursiveSelect($id,$perms_clause) {

			// Initialize tree object:
		$tree = t3lib_div::makeInstance('t3lib_pageTree');
		$tree->init('AND '.$perms_clause);
		$tree->addField('perms_userid',1);
		$tree->makeHTML=0;
		$tree->setRecs = 1;

			// Make tree:
		$tree->getTree($id,$this->getLevels,'');

			// If there are a hierarchy of page ids, then...
		if ($GLOBALS['BE_USER']->user['uid'] && count($tree->orig_ids_hierarchy)) {

				// Init:
			$label_recur = $GLOBALS['LANG']->getLL('recursive');
			$label_levels = $GLOBALS['LANG']->getLL('levels');
			$label_pA = $GLOBALS['LANG']->getLL('pages_affected');
			$theIdListArr=array();
			$opts='
						<option value=""></option>';

				// Traverse the number of levels we want to allow recursive setting of permissions for:
			for ($a=$this->getLevels;$a>0;$a--)	{
				if (is_array($tree->orig_ids_hierarchy[$a]))	{
					foreach($tree->orig_ids_hierarchy[$a] as $theId)	{
						if ($GLOBALS['BE_USER']->isAdmin() || $GLOBALS['BE_USER']->user['uid']==$tree->recs[$theId]['perms_userid'])	{
							$theIdListArr[]=$theId;
						}
					}
					$lKey = $this->getLevels-$a+1;
					$opts.='
						<option value="'.htmlspecialchars(implode(',',$theIdListArr)).'">'.
							t3lib_div::deHSCentities(htmlspecialchars($label_recur.' '.$lKey.' '.$label_levels)).' ('.count($theIdListArr).' '.$label_pA.')'.
							'</option>';
				}
			}

				// Put the selector box together:
			$theRecursiveSelect = '<br />
					<select name="mirror[pages]['.$id.']">
						'.$opts.'
					</select>
				<br /><br />';
		} else {
			$theRecursiveSelect = '';
		}

			// Return selector box element:
		return $theRecursiveSelect;
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/mod/web/perm/index.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/mod/web/perm/index.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('SC_mod_web_perm_index');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

?>
