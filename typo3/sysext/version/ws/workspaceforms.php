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
 * Module: Workspace manager
 *
 * $Id$
 *
 * @author	Dmitry Dulepov <typo3@accio.lv>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   93: class SC_mod_user_ws_workspaceForms extends t3lib_SCbase
 *
 *              SECTION: PUBLIC MODULE METHODS
 *  123:     function init()
 *  158:     function main()
 *  233:     function printContent()
 *
 *              SECTION: PRIVATE FUNCTIONS
 *  257:     function initTCEForms()
 *  284:     function getModuleParameters()
 *  302:     function getTitle()
 *  321:     function buildForm()
 *  330:     function buildEditForm()
 *  395:     function buildNewForm()
 *  458:     function createButtons()
 *  484:     function getOwnerUser($uid)
 *  510:     function processData()
 *  554:     function fixVariousTCAFields()
 *  566:     function fixTCAUserField($fieldName)
 *  593:     function checkWorkspaceAccess()
 *
 *
 *  606: class user_SC_mod_user_ws_workspaceForms
 *  615:     function processUserAndGroups($conf, $tceforms)
 *
 * TOTAL FUNCTIONS: 16
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


// Initialize module:
unset($MCONF);
require('conf.php');
require($BACK_PATH.'init.php');
require($BACK_PATH.'template.php');
$BE_USER->modAccess($MCONF,1);

// Include libraries of various kinds used inside:
$LANG->includeLLFile('EXT:lang/locallang_mod_user_ws.xml');

/**
 * Module: Workspace forms for editing/creating workspaces.
 *
 * @author	Dmitry Dulepov <typo3@fm-world.ru>
 * @package TYPO3
 * @subpackage core
 */
class SC_mod_user_ws_workspaceForms extends t3lib_SCbase {

	// Default variables for backend modules
	var $MCONF = array();				// Module configuration
	var $MOD_MENU = array();			// Module menu items
	var $MOD_SETTINGS = array();		// Module session settings

	/**
	 * Document Template Object
	 *
	 * @var mediumDoc
	 */
	var $doc;
	var $content;						// Accumulated content

	// internal variables
	var	$isEditAction = false;			// true if about to edit workspace
	var $workspaceId;					// ID of the workspace that we will edit. Set only if $isEditAction is true.

	/**
	 * An instance of t3lib_TCEForms
	 *
	 * @var t3lib_TCEforms
	 */
	var $tceforms;






	/*************************
	 *
	 * PUBLIC MODULE METHODS
	 *
	 *************************/

	/**
	 * Initializes the module. See <code>t3lib_SCbase::init()</code> for more information.
	 *
	 * @return	void
	 */
	function init()	{
		// Setting module configuration:
		$this->MCONF = $GLOBALS['MCONF'];

		// Initialize Document Template object:
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setModuleTemplate('templates/ws_forms.html');
		$this->doc->form = '<form action="' . t3lib_div::getIndpEnv('SCRIPT_NAME').'" method="post" enctype="'.$GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'].'" name="editform" onsubmit="return TBE_EDITOR.checkSubmit(1);">';

		$this->doc->getContextMenuCode();

		// Parent initialization:
		t3lib_SCbase::init();
	}











	/**
	 * Creates module content.
	 *
	 * @return	void
	 */
	function main()	{
		global	$LANG;

		// see what we have to do and get parameters (call before processing data!!!)
		$this->getModuleParameters();

		$hasAccess = (
			$GLOBALS['BE_USER']->isAdmin() ||
			0 != ($GLOBALS['BE_USER']->groupData['workspace_perms'] & 4) ||
			($this->isEditAction && $this->checkWorkspaceAccess())
		);

		if (!$hasAccess) {
			$title = $this->getTitle();
			$this->content .= $this->doc->startPage($title);
			$this->content .= $this->doc->header($title);
			$this->content .= $this->doc->spacer(5);
			$this->content .= $LANG->getLL($this->isEditAction ? 'edit_workspace_no_permission' : 'create_workspace_no_permission');
			$this->content .= $this->doc->spacer(5);
			$goBack = $GLOBALS['LANG']->getLL('edit_workspace_go_back');
			$this->content .= t3lib_iconWorks::getSpriteIcon('actions-view-go-back') .
						'<a href="javascript:history.back()" title="'. $goBack . '">' .
						$goBack .
						'</a>';
			$this->content .= $this->doc->endPage();
			return;
		}

		// process submission (this may override action and workspace ID!)
		if (t3lib_div::_GP('workspace_form_submited')) {
			$this->processData();
			// if 'Save&Close' was pressed, redirect to main module script
			if (t3lib_div::_GP('_saveandclosedok_x')) {
				// `n` below is to prevent caching
				t3lib_utility_Http::redirect('index.php?n=' . uniqid(''));
			}
		}

		$this->initTCEForms();

		//
		// start page
		//
		$this->content .= $this->doc->header($this->getTitle());
		$this->content .= $this->doc->spacer(5);

		//
		// page content
		//
		$this->content .= $this->tceforms->printNeededJSFunctions_top();
		$this->content .= $this->buildForm();
		$this->content .= $this->tceforms->printNeededJSFunctions();

			// Setting up the buttons and markers for docheader
		$docHeaderButtons = $this->getButtons();
		// $markers['CSH'] = $docHeaderButtons['csh'];
		$markers['CONTENT'] = $this->content;

			// Build the <body> for the module
		$this->content = $this->doc->startPage($this->getTitle());
		$this->content.= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		$this->content.= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
	}

	/**
	 * Outputs module content to the browser.
	 *
	 * @return	void
	 */
	function printContent()	{
		echo $this->content;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return	array	all available buttons as an assoc. array
	 */
	protected function getButtons()	{
		global $LANG;

		$buttons = array(
			'close' => '',
			'save' => '',
			'save_close' => ''
		);

			// Close,  `n` below is simply to prevent caching
		$buttons['close'] = '<a href="index.php?n=' . uniqid('wksp') . '" title="' . $LANG->sL('LLL:EXT:lang/locallang_core.php:rm.closeDoc', 1) . '">' . t3lib_iconWorks::getSpriteIcon('actions-document-close') . '</a>';
			// Save
		$buttons['save'] = '<input type="image" class="c-inputButton" name="_savedok"' . t3lib_iconWorks::skinImg($this->doc->backPath, 'gfx/savedok.gif') . ' title="' . $LANG->sL('LLL:EXT:lang/locallang_core.php:rm.saveDoc', 1) . '" value="_savedok" />';
			// Save & Close
		$buttons['save_close'] = '<input type="image" class="c-inputButton" name="_saveandclosedok"' . t3lib_iconWorks::skinImg($this->doc->backPath, 'gfx/saveandclosedok.gif') . ' title="' . $LANG->sL('LLL:EXT:lang/locallang_core.php:rm.saveCloseDoc', 1) . '" value="_saveandclosedok" />';

		return $buttons;
	}








	/*************************
	 *
	 * PRIVATE FUNCTIONS
	 *
	 *************************/

	/**
	 * Initializes <code>t3lib_TCEform</code> class for use in this module.
	 *
	 * @return	void
	 */
	function initTCEForms() {
		$this->tceforms = t3lib_div::makeInstance('t3lib_TCEforms');
		$this->tceforms->initDefaultBEMode();
		$this->tceforms->backPath = $GLOBALS['BACK_PATH'];
		$this->tceforms->doSaveFieldName = 'doSave';
		$this->tceforms->localizationMode = t3lib_div::inList('text,media',$this->localizationMode) ? $this->localizationMode : '';	// text,media is keywords defined in TYPO3 Core API..., see "l10n_cat"
		$this->tceforms->returnUrl = $this->R_URI;
		$this->tceforms->palettesCollapsed = !$this->MOD_SETTINGS['showPalettes'];
		$this->tceforms->disableRTE = $this->MOD_SETTINGS['disableRTE'];
		$this->tceforms->enableClickMenu = true;
		$this->tceforms->enableTabMenu = true;

			// Setting external variables:
		if ($GLOBALS['BE_USER']->uc['edit_showFieldHelp']!='text' && $this->MOD_SETTINGS['showDescriptions'])	$this->tceforms->edit_showFieldHelp='text';
	}







	/**
	 * Retrieves module parameters from the <code>t3lib_div::_GP</code>. The following arguments are retrieved: <ul><li>action</li><li>workspace id (if action == 'edit')</li></ul>
	 *
	 * @return	void
	 */
	function getModuleParameters(){
		$this->isEditAction = (t3lib_div::_GP('action') == 'edit');
		if ($this->isEditAction) {
			$this->workspaceId = intval(t3lib_div::_GP('wkspId'));
		}
	}







	/**
	 * Retrieves a title of the module according to action.
	 *
	 * @return	string		A title for the module
	 */
	function getTitle() {
		$label = ($this->isEditAction ? 'edit_workspace_title_edit' : 'edit_workspace_title_new');
		return $GLOBALS['LANG']->getLL($label);
	}










	/**
	 * Creates form for workspace. This function is a wrapper around <code>buildEditForm()</code> and <code>buildNewForm()</code>.
	 *
	 * @return	string		Generated form
	 */
	function buildForm() {
		return $this->isEditAction ? $this->buildEditForm() : $this->buildNewForm();
	}

	/**
	 * Creates a form for editing workspace. Parts were adopted from <code>alt_doc.php</code>.
	 *
	 * @return	string		Generated form
	 */
	function buildEditForm() {
		$content = '';
		$table = 'sys_workspace';
		$prevPageID = '';
		$trData = t3lib_div::makeInstance('t3lib_transferData');
		$trData->addRawData = TRUE;
		$trData->defVals = $this->defVals;
		$trData->lockRecords=1;
		$trData->disableRTE = $this->MOD_SETTINGS['disableRTE'];
		$trData->prevPageID = $prevPageID;
		$trData->fetchRecord($table, $this->workspaceId, '');
		reset($trData->regTableItems_data);
		$rec = current($trData->regTableItems_data);

		// Setting variables in TCEforms object:
		$this->tceforms->hiddenFieldList = '';
		// Register default language labels, if any:
		$this->tceforms->registerDefaultLanguageData($table,$rec);

		$this->fixVariousTCAFields();
		if (!$GLOBALS['BE_USER']->isAdmin()) {
			// Non-admins cannot select users from the root. We "fix" it for them.
			$this->fixTCAUserField('adminusers');
			$this->fixTCAUserField('members');
			$this->fixTCAUserField('reviewers');
		}

		// Create form for the record (either specific list of fields or the whole record):
		$form = '';
		$form .= $this->tceforms->getMainFields($table,$rec);
		$form .= '<input type="hidden" name="data['.$table.']['.$rec['uid'].'][pid]" value="'.$rec['pid'].'" />';
		$form .= '<input type="hidden" name="workspace_form_submited" value="1" />';
		$form .= '<input type="hidden" name="returnUrl" value="index.php" />';
		$form .= '<input type="hidden" name="action" value="edit" />';
		$form .= '<input type="hidden" name="closeDoc" value="0" />';
		$form .= '<input type="hidden" name="doSave" value="0" />';
		$form .= '<input type="hidden" name="_serialNumber" value="'.md5(microtime()).'" />';
		$form .= '<input type="hidden" name="_disableRTE" value="'.$this->tceforms->disableRTE.'" />';
		$form .= '<input type="hidden" name="wkspId" value="' . htmlspecialchars($this->workspaceId) . '" />';
		$form = $this->tceforms->wrapTotal($form, $rec, $table);

		// Combine it all:
		$content .= $form;
		return $content;
	}












	/**
	 * Creates a form for new workspace. Parts are adopted from <code>alt_doc.php</code>.
	 *
	 * @return	string		Generated form
	 */
	function buildNewForm() {
		$content = '';
		$table = 'sys_workspace';
		$prevPageID = '';
		$trData = t3lib_div::makeInstance('t3lib_transferData');
		$trData->addRawData = TRUE;
		$trData->defVals = $this->defVals;
		$trData->lockRecords=1;
		$trData->disableRTE = $this->MOD_SETTINGS['disableRTE'];
		$trData->prevPageID = $prevPageID;
		$trData->fetchRecord($table, 0, 'new');
		reset($trData->regTableItems_data);
		$rec = current($trData->regTableItems_data);
		$rec['uid'] = uniqid('NEW');
		$rec['pid'] = 0;
		$rec['adminusers'] = $this->getOwnerUser($rec['uid']);

		// Setting variables in TCEforms object:
		$this->tceforms->hiddenFieldList = '';
		// Register default language labels, if any:
		$this->tceforms->registerDefaultLanguageData($table,$rec);

		$this->fixVariousTCAFields();
		if (!$GLOBALS['BE_USER']->isAdmin()) {
			// Non-admins cannot select users from the root. We "fix" it for them.
			$this->fixTCAUserField('adminusers');
			$this->fixTCAUserField('members');
			$this->fixTCAUserField('reviewers');
		}


		// Create form for the record (either specific list of fields or the whole record):
		$form = '';
		$form .= $this->doc->spacer(5);
		$form .= $this->tceforms->getMainFields($table,$rec);

		$form .= '<input type="hidden" name="workspace_form_submited" value="1" />';
		$form .= '<input type="hidden" name="data['.$table.']['.$rec['uid'].'][pid]" value="'.$rec['pid'].'" />';
		$form .= '<input type="hidden" name="returnUrl" value="index.php" />';
		$form .= '<input type="hidden" name="action" value="new" />';
		$form .= '<input type="hidden" name="closeDoc" value="0" />';
		$form .= '<input type="hidden" name="doSave" value="0" />';
		$form .= '<input type="hidden" name="_serialNumber" value="'.md5(microtime()).'" />';
		$form .= '<input type="hidden" name="_disableRTE" value="'.$this->tceforms->disableRTE.'" />';
		$form = $this->tceforms->wrapTotal($form, $rec, $table);

		// Combine it all:
		$content .= $form;
		return $content;
	}












	/**
	 * Returns owner user (i.e. current BE user) in the format suitable for TCE forms. This function uses <code>t3lib_loadDBGroup</code> to create value. Code is adopted from <code>t3lib_transferdata::renderRecord_groupProc()</code>.
	 *
	 * @param	string		$uid	UID of the record (as <code>NEW...</code>)
	 * @return	string		User record formatted for TCEForms
	 */
	function getOwnerUser($uid) {
		$loadDB = t3lib_div::makeInstance('t3lib_loadDBGroup');
		// Make sure that `sys_workspace` is in $TCA
		t3lib_div::loadTCA('sys_workspace');
		// shortcut to `config` of `adminusers` field -- shorter code and better PHP performance
		$config = &$GLOBALS['TCA']['sys_workspace']['columns']['adminusers']['config'];
		// Notice: $config['MM'] is not set in the current version of $TCA but
		// we still pass it to ensure compatibility with feature versions!
		$loadDB->start($GLOBALS['BE_USER']->user['uid'], $config['allowed'], $config['MM'], $uid, 'sys_workspace', $config);
		$loadDB->getFromDB();
		return $loadDB->readyForInterface();
	}









	/**
	 * Processes submitted data. This function uses <code>t3lib_TCEmain::process_datamap()</code> to create/update records in the <code>sys_workspace</code> table. It will print error messages just like any other Typo3 module with similar functionality. Function also changes workspace ID and module mode to 'edit' if new record was just created.
	 *
	 * @return	void
	 */
	function processData() {
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values = 0;

		$TCAdefaultOverride = $GLOBALS['BE_USER']->getTSConfigProp('TCAdefaults');
		if (is_array($TCAdefaultOverride))	{
			$tce->setDefaultsFromUserTS($TCAdefaultOverride);
		}
		$tce->stripslashes_values = 0;

			// The following is a security precaution; It makes sure that the input data array can ONLY contain data for the sys_workspace table and ONLY one record.
			// If this is not present it could be mis-used for nasty XSS attacks which can escalate rights to admin for even non-admin users.
		$inputData_tmp = t3lib_div::_GP('data');
		$inputData = array();
		if (is_array($inputData_tmp['sys_workspace']))	{
			reset($inputData_tmp['sys_workspace']);
			$inputData['sys_workspace'][key($inputData_tmp['sys_workspace'])] = current($inputData_tmp['sys_workspace']);
		}

		$tce->start($inputData, array(), $GLOBALS['BE_USER']);
		$tce->admin = 1;	// Bypass table restrictions
		$tce->bypassWorkspaceRestrictions = true;
		$tce->process_datamap();

			// print error messages (if any)
		$script = t3lib_div::getIndpEnv('TYPO3_REQUEST_SCRIPT');
		$tce->printLogErrorMessages($script . '?' .
			($this->isEditAction ? 'action=edit&wkspId=' . $this->workspaceId : 'action=new'));

		// If there was saved any new items, load them and update mode and workspace id
		if (count($tce->substNEWwithIDs_table))	{
			reset($tce->substNEWwithIDs_table);	// not really necessary but better be safe...
			$this->workspaceId = current($tce->substNEWwithIDs);
			$this->isEditAction = true;
		}
	}



	/**
	 * Fixes various <code>$TCA</code> fields for better visual representation of workspace editor.
	 *
	 * @return	void
	 */
	function fixVariousTCAFields() {
		// enable tabs
		$GLOBALS['TCA']['sys_workspace']['ctrl']['dividers2tabs'] = true;
	}


	/**
	 * "Fixes" <code>$TCA</code> to enable blinding for users/groups for non-admin users only.
	 *
	 * @param	string		$fieldName	Name of the field to change
	 * @return	void
	 */
	function fixTCAUserField($fieldName) {
		// fix fields for non-admin
		if (!$GLOBALS['BE_USER']->isAdmin()) {
			// make a shortcut to field
			t3lib_div::loadTCA('sys_workspace');
			$field = &$GLOBALS['TCA']['sys_workspace']['columns'][$fieldName];
			$newField = array (
				'label' => $field['label'],
				'config' => Array (
					'type' => 'select',
					'itemsProcFunc' => 'user_SC_mod_user_ws_workspaceForms->processUserAndGroups',
					//'iconsInOptionTags' => true,
					'size' => 10,
					'maxitems' => $field['config']['maxitems'],
					'autoSizeMax' => $field['config']['autoSizeMax'],
					'mod_ws_allowed' => $field['config']['allowed']	// let us know what we can use in itemProcFunc
				)
			);
			$field = $newField;
		}
	}

	/**
	 * Checks if use has editing access to the workspace.
	 *
	 * @return	boolean		Returns true if user can edit workspace
	 */
	function checkWorkspaceAccess() {
		$workspaces = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,title,adminusers,members,reviewers','sys_workspace','uid=' . intval($this->workspaceId) . ' AND pid=0'.t3lib_BEfunc::deleteClause('sys_workspace'));
		if (is_array($workspaces) && count($workspaces) != 0 && false !== ($rec = $GLOBALS['BE_USER']->checkWorkspace($workspaces[0])))	{
			return ($rec['_ACCESS'] == 'owner' || $rec['_ACCESS'] == 'admin');
		}
		return false;
	}
}

/**
 * This class contains Typo3 callback functions. Class name must start from <code>user_</code> thus we use a separate class.
 *
 */
class user_SC_mod_user_ws_workspaceForms {

	/**
	 * Callback function to blind user and group accounts. Used as <code>itemsProcFunc</code> in <code>$TCA</code>.
	 *
	 * @param	array		$conf	Configuration array. The following elements are set:<ul><li>items - initial set of items (empty in our case)</li><li>config - field config from <code>$TCA</code></li><li>TSconfig - this function name</li><li>table - table name</li><li>row - record row (???)</li><li>field - field name</li></ul>
	 * @param	object		$tceforms	<code>t3lib_div::TCEforms</code> object
	 * @return	void
	 */
	function processUserAndGroups($conf, $tceforms) {
			// Get usernames and groupnames
		$be_group_Array = t3lib_BEfunc::getListGroupNames('title,uid');
		$groupArray = array_keys($be_group_Array);

		$be_user_Array = t3lib_BEfunc::getUserNames();
		$be_user_Array = t3lib_BEfunc::blindUserNames($be_user_Array,$groupArray,1);

		// users
		$title = $GLOBALS['LANG']->sL($GLOBALS['TCA']['be_users']['ctrl']['title']);
		foreach ($be_user_Array as $uid => $user) {
			$conf['items'][] = array(
				$user['username'] . ' (' . $title . ')',
				'be_users_' . $user['uid'],
				t3lib_iconWorks::getIcon('be_users', $user)
			);
		}

		// Process groups only if necessary -- save time!
		if (strstr($conf['config']['mod_ws_allowed'], 'be_groups')) {
			// groups

			$be_group_Array = $be_group_Array_o = t3lib_BEfunc::getGroupNames();
			$be_group_Array = t3lib_BEfunc::blindGroupNames($be_group_Array_o,$groupArray,1);

			$title = $GLOBALS['LANG']->sL($GLOBALS['TCA']['be_groups']['ctrl']['title']);
			foreach ($be_group_Array as $uid => $group) {
				$conf['items'][] = array(
					$group['title'] . ' (' . $title . ')',
					'be_groups_' . $group['uid'],
					t3lib_iconWorks::getIcon('be_groups', $user)
				);
			}
		}
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/mod/user/ws/workspaceforms.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/mod/user/ws/workspaceforms.php']);
}

// Make instance:
$SOBE = t3lib_div::makeInstance('SC_mod_user_ws_workspaceForms');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

?>