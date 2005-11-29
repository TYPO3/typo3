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
 * Module: Workspace manager
 *
 * $Id$
 *
 * @author	Dmitry Dulepov <typo3@fm-world.ru>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   86: class SC_mod_user_ws_workspaceForms extends t3lib_SCbase
 *
 *              SECTION: PUBLIC MODULE METHODS
 *  115:     function init()
 *  151:     function main()
 *  200:     function printContent()
 *
 *              SECTION: PRIVATE FUNCTIONS
 *  224:     function initTCEForms()
 *  251:     function getModuleParameters()
 *  269:     function getTitle()
 *  288:     function buildForm()
 *  297:     function buildEditForm()
 *  349:     function buildNewForm()
 *  394:     function createButtons()
 *  421:     function getOwnerUser($uid)
 *  447:     function processData()
 *
 * TOTAL FUNCTIONS: 12
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
require_once(PATH_t3lib.'class.t3lib_scbase.php');
//require_once(PATH_typo3.'mod/user/ws/class.wslib.php');
require_once(PATH_t3lib.'class.t3lib_tcemain.php');
require_once(PATH_t3lib.'class.t3lib_tceforms.php');
require_once (PATH_t3lib.'class.t3lib_transferdata.php');
require_once (PATH_t3lib.'class.t3lib_loaddbgroup.php');

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
	var $doc;							// Document Template Object
	var $content;						// Accumulated content

	// internal variables
	var	$isEditAction = false;			// true if about to edit workspace
	var $workspaceId;					// ID of the workspace that we will edit. Set only if $isEditAction is true.
	var $tceforms;						// An instance of t3lib_TCEForms






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
		$this->doc = t3lib_div::makeInstance('noDoc');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->docType = 'xhtml_trans';
		$this->doc->form = '<form action="'.htmlspecialchars($this->R_URI).'" method="post" enctype="'.$GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'].'" name="editform" onsubmit="return TBE_EDITOR_checkSubmit(1);">';

		$CMparts = $this->doc->getContextMenuCode();
		$this->doc->JScode.= $CMparts[0];
		$this->doc->bodyTagAdditions = $CMparts[1];
		$this->doc->postCode.= $CMparts[2];

		$this->initTCEForms();

		// Parent initialization:
		t3lib_SCbase::init();
	}











	/**
	 * Creates module content.
	 *
	 * @return	void
	 */
	function main()	{
		// see what we have to do and get parameters (call before processing data!!!)
		$this->getModuleParameters();

		// process submission (this may override action and workspace ID!)
		if (t3lib_div::_GP('submit') != '') {
			$this->processData();
			// if 'Save&Close' was pressed, redirect to main module script
			if (t3lib_div::_GP('_saveandclosedok_x')) {
				// `n` below is to prevent caching
				header('Location: ' . t3lib_div::locationHeaderUrl('index.php?n=' . uniqid('')));
				exit();
			}
		}

		//
		// start page
		//
		$title = $this->getTitle();
		$this->content .= $this->doc->startPage($title);
		$this->content .= $this->doc->header($title);
		$this->content .= $this->doc->spacer(5);

		//
		// page content
		//
		$this->content .= $this->tceforms->printNeededJSFunctions_top();
		$this->content .= $this->buildForm();
		$this->content .= $this->tceforms->printNeededJSFunctions();

		//
		// end page
		//
		$this->content .= $this->doc->endPage();
	}









	/**
	 * Outputs module content to the browser.
	 *
	 * @return	void
	 */
	function printContent()	{
		echo $this->content;
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
		if ($this->isEditAction) {
			// TODO Localize this
			return 'Edit workspace';
		}
		// TODO Localize this
		return 'Create new workspace';
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

		// Create form for the record (either specific list of fields or the whole record):
		$form = '';
		$form .= $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.path', 1) . ': ' . $this->tceforms->getRecordPath($table,$rec);
		$form .= $this->doc->spacer(5);
		$form .= $this->tceforms->getMainFields($table,$rec);
		$form .= '<input type="hidden" name="data['.$table.']['.$rec['uid'].'][pid]" value="'.$rec['pid'].'" />';
		$form .= '<input type="hidden" name="submit" value="1" />';
		$form .= '<input type="hidden" name="action" value="edit" />';
		$form .= '<input type="hidden" name="workspaceId" value="' . $this->workspaceId . '" />';
		$form = $this->tceforms->wrapTotal($form, $rec, $table);

		$buttons = $this->createButtons() . $this->doc->spacer(5);

		// Combine it all:
		$content .= $buttons . $form . $buttons;
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

		// Create form for the record (either specific list of fields or the whole record):
		$form = '';
		$fields = array_keys($GLOBALS['TCA'][$table]['columns']);
		unset($fields[array_search('freeze', $fields)]);
		$form .= $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.path', 1) . ': ' . $this->tceforms->getRecordPath($table,$rec);
		$form .= $this->doc->spacer(5);
		$form .= $this->tceforms->getListedFields($table,$rec,implode(',', $fields));
		$form .= '<input type="hidden" name="data['.$table.']['.$rec['uid'].'][pid]" value="'.$rec['pid'].'" />';
		$form .= '<input type="hidden" name="submit" value="1" />';
		$form = $this->tceforms->wrapTotal($form, $rec, $table);

		$buttons = $this->createButtons() . $this->doc->spacer(5);

		// Combine it all:
		$content .= $buttons . $form . $buttons;
		return $content;
	}

	/**
	 * Creates standard buttons for form. Adopted from <code>alt_doc.php</code>.
	 *
	 * @return	string		Generated buttons code
	 */
	function createButtons() {
		global	$LANG;

		$content = '';
		$content .= '<input type="image" class="c-inputButton" name="_savedok"' . t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/savedok.gif','').' title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:rm.saveDoc',1).'" />';
		$content .= '<input type="image" class="c-inputButton" name="_saveandclosedok"'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/saveandclosedok.gif','').' title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:rm.saveCloseDoc',1).'" />';
		// `n` below is simply to prevent caching
		$content .= '<a href="index.php?n=' . uniqid('wksp') . '"><img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/closedok.gif','width="21" height="16"').' class="c-inputButton" title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:rm.closeDoc',1).'" alt="" /></a>';
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
		$loadDB->start($GLOBALS['BE_USER']->user['uid'], $config['allowed'], $config['MM'], $uid);
		$loadDB->getFromDB();
		return $loadDB->readyForInterface();
	}









	/**
	 * Processes submited data. This function uses <code>t3lib_TCEmain::process_datamap()</code> to create/update records in the <code>sys_workspace</code> table. It will print error messages just like any other Typo3 module with similar functionality. Function also changes workspace ID and module mode to 'edit' if new record was just created.
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
		$tce->start(t3lib_div::_GP('data'), array(), $GLOBALS['BE_USER']);
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
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/mod/user/ws/class.mod_user_ws_workspaceForms.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/mod/user/ws/class.mod_user_ws_workspaceForms.php']);
}

// Make instance:
$SOBE = t3lib_div::makeInstance('SC_mod_user_ws_workspaceForms');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>