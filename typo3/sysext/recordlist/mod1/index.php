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
 * Module: Web>List
 *
 * Listing database records from the tables configured in $TCA as they are related to the current page or root.
 *
 * Notice: This module and Web>Page (db_layout.php) module has a special status since they
 * are NOT located in their actual module directories (fx. mod/web/list/) but in the
 * backend root directory. This has some historical and practical causes.
 *
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
 *   89: class SC_db_list
 *  125:     function init()
 *  160:     function menuConfig()
 *  181:     function clearCache()
 *  195:     function main()
 *  451:     function printContent()
 *
 * TOTAL FUNCTIONS: 5
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


$LANG->includeLLFile('EXT:lang/locallang_mod_web_list.xml');
require_once ($BACK_PATH.'class.db_list.inc');
require_once ($BACK_PATH.'class.db_list_extra.inc');
$BE_USER->modAccess($MCONF,1);

t3lib_BEfunc::lockRecords();








/**
 * Script Class for the Web > List module; rendering the listing of records on a page
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_db_list {

		// Internal, GPvars:
	var $id;					// Page Id for which to make the listing
	var $pointer;				// Pointer - for browsing list of records.
	var $imagemode;				// Thumbnails or not
	var $table;					// Which table to make extended listing for
	var $search_field;			// Search-fields
	var $search_levels;			// Search-levels
	var $showLimit;				// Show-limit
	var $returnUrl;				// Return URL

	var $clear_cache;			// Clear-cache flag - if set, clears page cache for current id.
	var $cmd;					// Command: Eg. "delete" or "setCB" (for TCEmain / clipboard operations)
	var $cmd_table;				// Table on which the cmd-action is performed.

		// Internal, static:
	var $perms_clause;			// Page select perms clause
	var $modTSconfig;			// Module TSconfig
	var $pageinfo;				// Current ids page record

	/**
	 * Document template object
	 *
	 * @var template
	 */
	var $doc;

	var $MCONF=array();			// Module configuration
	var $MOD_MENU=array();		// Menu configuration
	var $MOD_SETTINGS=array();	// Module settings (session variable)
	var $include_once=array();	// Array, where files to include is accumulated in the init() function

		// Internal, dynamic:
	var $content;				// Module output accumulation


	/**
	 * Initializing the module
	 *
	 * @return	void
	 */
	function init()	{
		global $BE_USER;

			// Setting module configuration / page select clause
		$this->MCONF = $GLOBALS['MCONF'];
		$this->perms_clause = $BE_USER->getPagePermsClause(1);

			// GPvars:
		$this->id = t3lib_div::_GP('id');
		$this->pointer = t3lib_div::_GP('pointer');
		$this->imagemode = t3lib_div::_GP('imagemode');
		$this->table = t3lib_div::_GP('table');
		$this->search_field = t3lib_div::_GP('search_field');
		$this->search_levels = t3lib_div::_GP('search_levels');
		$this->showLimit = t3lib_div::_GP('showLimit');
		$this->returnUrl = t3lib_div::sanitizeLocalUrl(t3lib_div::_GP('returnUrl'));

		$this->clear_cache = t3lib_div::_GP('clear_cache');
		$this->cmd = t3lib_div::_GP('cmd');
		$this->cmd_table = t3lib_div::_GP('cmd_table');

			// Initialize menu
		$this->menuConfig();

			// Inclusions?
		if ($this->clear_cache || $this->cmd=='delete')	{
			$this->include_once[]=PATH_t3lib.'class.t3lib_tcemain.php';
		}
	}

	/**
	 * Initialize function menu array
	 *
	 * @return	void
	 */
	function menuConfig()	{

			// MENU-ITEMS:
		$this->MOD_MENU = array(
			'bigControlPanel' => '',
			'clipBoard' => '',
			'localization' => ''
		);

			// Loading module configuration:
		$this->modTSconfig = t3lib_BEfunc::getModTSconfig($this->id,'mod.'.$this->MCONF['name']);

			// Clean up settings:
		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::_GP('SET'), $this->MCONF['name']);
	}

	/**
	 * Clears page cache for the current id, $this->id
	 *
	 * @return	void
	 */
	function clearCache()	{
		if ($this->clear_cache)	{
			$tce = t3lib_div::makeInstance('t3lib_TCEmain');
			$tce->stripslashes_values=0;
			$tce->start(Array(),Array());
			$tce->clear_cacheCmd($this->id);
		}
	}

	/**
	 * Main function, starting the rendering of the list.
	 *
	 * @return	void
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$CLIENT;

			// Start document template object:
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $BACK_PATH;
		$this->doc->setModuleTemplate('templates/db_list.html');

			// Loading current page record and checking access:
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;

			// Apply predefined values for hidden checkboxes
			// Set predefined value for DisplayBigControlPanel:
		if ($this->modTSconfig['properties']['enableDisplayBigControlPanel'] === 'activated') {
			$this->MOD_SETTINGS['bigControlPanel'] = TRUE;
		} elseif ($this->modTSconfig['properties']['enableDisplayBigControlPanel'] === 'deactivated') {
			$this->MOD_SETTINGS['bigControlPanel'] = FALSE;
		}

			// Set predefined value for Clipboard:
		if ($this->modTSconfig['properties']['enableClipBoard'] === 'activated') {
			$this->MOD_SETTINGS['clipBoard'] = TRUE;
		} elseif ($this->modTSconfig['properties']['enableClipBoard'] === 'deactivated') {
			$this->MOD_SETTINGS['clipBoard'] = FALSE;
		}

			// Set predefined value for LocalizationView:
		if ($this->modTSconfig['properties']['enableLocalizationView'] === 'activated') {
			$this->MOD_SETTINGS['localization'] = TRUE;
		} elseif ($this->modTSconfig['properties']['enableLocalizationView'] === 'deactivated') {
			$this->MOD_SETTINGS['localization'] = FALSE;
		}

			// Initialize the dblist object:
		$dblist = t3lib_div::makeInstance('localRecordList');
		$dblist->backPath = $BACK_PATH;
		$dblist->script = t3lib_BEfunc::getModuleUrl('web_list', array(), '');
		$dblist->calcPerms = $BE_USER->calcPerms($this->pageinfo);
		$dblist->thumbs = $BE_USER->uc['thumbnailsByDefault'];
		$dblist->returnUrl=$this->returnUrl;
		$dblist->allFields = ($this->MOD_SETTINGS['bigControlPanel'] || $this->table) ? 1 : 0;
		$dblist->localizationView = $this->MOD_SETTINGS['localization'];
		$dblist->showClipboard = 1;
		$dblist->disableSingleTableView = $this->modTSconfig['properties']['disableSingleTableView'];
		$dblist->listOnlyInSingleTableMode = $this->modTSconfig['properties']['listOnlyInSingleTableView'];
		$dblist->hideTables = $this->modTSconfig['properties']['hideTables'];
		$dblist->tableTSconfigOverTCA = $this->modTSconfig['properties']['table.'];
		$dblist->clickTitleMode = $this->modTSconfig['properties']['clickTitleMode'];
		$dblist->alternateBgColors=$this->modTSconfig['properties']['alternateBgColors']?1:0;
		$dblist->allowedNewTables = t3lib_div::trimExplode(',', $this->modTSconfig['properties']['allowedNewTables'], 1);
		$dblist->deniedNewTables = t3lib_div::trimExplode(',', $this->modTSconfig['properties']['deniedNewTables'], 1);
		$dblist->newWizards=$this->modTSconfig['properties']['newWizards']?1:0;
		$dblist->pageRow = $this->pageinfo;
		$dblist->counter++;
		$dblist->MOD_MENU = array('bigControlPanel' => '', 'clipBoard' => '', 'localization' => '');
		$dblist->modTSconfig = $this->modTSconfig;

			// Clipboard is initialized:
		$dblist->clipObj = t3lib_div::makeInstance('t3lib_clipboard');		// Start clipboard
		$dblist->clipObj->initializeClipboard();	// Initialize - reads the clipboard content from the user session

			// Clipboard actions are handled:
		$CB = t3lib_div::_GET('CB');	// CB is the clipboard command array
		if ($this->cmd=='setCB') {
				// CBH is all the fields selected for the clipboard, CBC is the checkbox fields which were checked. By merging we get a full array of checked/unchecked elements
				// This is set to the 'el' array of the CB after being parsed so only the table in question is registered.
			$CB['el'] = $dblist->clipObj->cleanUpCBC(array_merge((array)t3lib_div::_POST('CBH'),(array)t3lib_div::_POST('CBC')),$this->cmd_table);
		}
		if (!$this->MOD_SETTINGS['clipBoard'])	$CB['setP']='normal';	// If the clipboard is NOT shown, set the pad to 'normal'.
		$dblist->clipObj->setCmd($CB);		// Execute commands.
		$dblist->clipObj->cleanCurrent();	// Clean up pad
		$dblist->clipObj->endClipboard();	// Save the clipboard content

			// This flag will prevent the clipboard panel in being shown.
			// It is set, if the clickmenu-layer is active AND the extended view is not enabled.
		$dblist->dontShowClipControlPanels = $CLIENT['FORMSTYLE'] && !$this->MOD_SETTINGS['bigControlPanel'] && $dblist->clipObj->current=='normal' && !$BE_USER->uc['disableCMlayers'] && !$this->modTSconfig['properties']['showClipControlPanelsDespiteOfCMlayers'];



			// If there is access to the page, then render the list contents and set up the document template object:
		if ($access)	{

				// Deleting records...:
				// Has not to do with the clipboard but is simply the delete action. The clipboard object is used to clean up the submitted entries to only the selected table.
			if ($this->cmd=='delete')	{
				$items = $dblist->clipObj->cleanUpCBC(t3lib_div::_POST('CBC'),$this->cmd_table,1);
				if (count($items))	{
					$cmd=array();
					foreach ($items as $iK => $value) {
						$iKParts = explode('|',$iK);
						$cmd[$iKParts[0]][$iKParts[1]]['delete']=1;
					}
					$tce = t3lib_div::makeInstance('t3lib_TCEmain');
					$tce->stripslashes_values=0;
					$tce->start(array(),$cmd);
					$tce->process_cmdmap();

					if (isset($cmd['pages']))	{
						t3lib_BEfunc::setUpdateSignal('updatePageTree');
					}

					$tce->printLogErrorMessages(t3lib_div::getIndpEnv('REQUEST_URI'));
				}
			}

				// Initialize the listing object, dblist, for rendering the list:
			$this->pointer = t3lib_div::intInRange($this->pointer,0,100000);
			$dblist->start($this->id,$this->table,$this->pointer,$this->search_field,$this->search_levels,$this->showLimit);
			$dblist->setDispFields();

				// Render versioning selector:
			if (t3lib_extMgm::isLoaded('version')) {
				$dblist->HTMLcode .= $this->doc->getVersionSelector($this->id);
			}

				// Render the list of tables:
			$dblist->generateList();

				// Write the bottom of the page:
			$dblist->writeBottom();
			$listUrl = substr($dblist->listURL(), strlen($GLOBALS['BACK_PATH']));
				// Add JavaScript functions to the page:
			$this->doc->JScode=$this->doc->wrapScriptTags('
				function jumpToUrl(URL)	{	//
					window.location.href = URL;
					return false;
				}
				function jumpExt(URL,anchor)	{	//
					var anc = anchor?anchor:"";
					window.location.href = URL+(T3_THIS_LOCATION?"&returnUrl="+T3_THIS_LOCATION:"")+anc;
					return false;
				}
				function jumpSelf(URL)	{	//
					window.location.href = URL+(T3_RETURN_URL?"&returnUrl="+T3_RETURN_URL:"");
					return false;
				}

				function setHighlight(id)	{	//
					top.fsMod.recentIds["web"]=id;
					top.fsMod.navFrameHighlightedID["web"]="pages"+id+"_"+top.fsMod.currentBank;	// For highlighting

					if (top.content && top.content.nav_frame && top.content.nav_frame.refresh_nav)	{
						top.content.nav_frame.refresh_nav();
					}
				}
				' . $this->doc->redirectUrls($listUrl) . '
				'.$dblist->CBfunctions().'
				function editRecords(table,idList,addParams,CBflag)	{	//
					window.location.href="'.$BACK_PATH.'alt_doc.php?returnUrl='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI')).
						'&edit["+table+"]["+idList+"]=edit"+addParams;
				}
				function editList(table,idList)	{	//
					var list="";

						// Checking how many is checked, how many is not
					var pointer=0;
					var pos = idList.indexOf(",");
					while (pos!=-1)	{
						if (cbValue(table+"|"+idList.substr(pointer,pos-pointer))) {
							list+=idList.substr(pointer,pos-pointer)+",";
						}
						pointer=pos+1;
						pos = idList.indexOf(",",pointer);
					}
					if (cbValue(table+"|"+idList.substr(pointer))) {
						list+=idList.substr(pointer)+",";
					}

					return list ? list : idList;
				}

				if (top.fsMod) top.fsMod.recentIds["web"] = '.intval($this->id).';
			');

				// Setting up the context sensitive menu:
			$this->doc->getContextMenuCode();
		} // access

			// Begin to compile the whole page, starting out with page header:
		$this->body='';
		$this->body.= '<form action="'.htmlspecialchars($dblist->listURL()).'" method="post" name="dblistForm">';
		$this->body.= $dblist->HTMLcode;
		$this->body.= '<input type="hidden" name="cmd_table" /><input type="hidden" name="cmd" /></form>';

			// If a listing was produced, create the page footer with search form etc:
		if ($dblist->HTMLcode)	{

				// Making field select box (when extended view for a single table is enabled):
			if ($dblist->table)	{
				$this->body.=$dblist->fieldSelectBox($dblist->table);
			}

				// Adding checkbox options for extended listing and clipboard display:
			$this->body.='

					<!--
						Listing options for extended view, clipboard and localization view
					-->
					<div id="typo3-listOptions">
						<form action="" method="post">';

				// Add "display bigControlPanel" checkbox:
			if ($this->modTSconfig['properties']['enableDisplayBigControlPanel'] === 'selectable') {
				$this->body .= t3lib_BEfunc::getFuncCheck(
					$this->id,
					'SET[bigControlPanel]',
					$this->MOD_SETTINGS['bigControlPanel'],
					'',
					($this->table ? '&table=' . $this->table : ''),
					'id="checkLargeControl"'
				);
				$this->body .= '<label for="checkLargeControl">' .
					t3lib_BEfunc::wrapInHelp(
						'xMOD_csh_corebe',
						'list_options',
						$GLOBALS['LANG']->getLL('largeControl', TRUE)
					) . '</label><br />';
			}

				// Add "clipboard" checkbox:
			if ($this->modTSconfig['properties']['enableClipBoard'] === 'selectable') {
				if ($dblist->showClipboard)	{
					$this->body .= t3lib_BEfunc::getFuncCheck(
						$this->id,
						'SET[clipBoard]',
						$this->MOD_SETTINGS['clipBoard'],
						'',
						($this->table ? '&table=' . $this->table : ''),
						'id="checkShowClipBoard"'
					);
					$this->body .= '<label for="checkShowClipBoard">' .
						t3lib_BEfunc::wrapInHelp(
							'xMOD_csh_corebe',
							'list_options',
							$GLOBALS['LANG']->getLL('showClipBoard', TRUE)
						) . '</label><br />';
				}
			}

				// Add "localization view" checkbox:
			if ($this->modTSconfig['properties']['enableLocalizationView'] === 'selectable') {
				$this->body .= t3lib_BEfunc::getFuncCheck(
					$this->id,
					'SET[localization]',
					$this->MOD_SETTINGS['localization'],
					'',
					($this->table ? '&table=' . $this->table : ''),
					'id="checkLocalization"'
				);
				$this->body .= '<label for="checkLocalization">' .
					t3lib_BEfunc::wrapInHelp(
						'xMOD_csh_corebe',
						'list_options',
						$GLOBALS['LANG']->getLL('localization', TRUE)
					) . '</label><br />';
			}

			$this->body.='
						</form>
					</div>';

				// Printing clipboard if enabled:
			if ($this->MOD_SETTINGS['clipBoard'] && $dblist->showClipboard)	{
				$this->body.= $dblist->clipObj->printClipboard();
			}

				// Search box:
			if (!$this->modTSconfig['properties']['disableSearchBox']) {
				$sectionTitle = t3lib_BEfunc::wrapInHelp('xMOD_csh_corebe', 'list_searchbox', $LANG->sL('LLL:EXT:lang/locallang_core.php:labels.search', TRUE));
				$this->body .= $this->doc->section(
					$sectionTitle,
					$dblist->getSearchBox(),
					FALSE, TRUE, FALSE, TRUE
				);
			}

				// Display sys-notes, if any are found:
			$this->body.=$dblist->showSysNotesForPage();
		}

			// Setting up the buttons and markers for docheader
		$docHeaderButtons = $dblist->getButtons();
		$markers = array(
			'CSH' => $docHeaderButtons['csh'],
			'CONTENT' => $this->body
		);

			// Build the <body> for the module
		$this->content = $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
			// Renders the module page
		$this->content = $this->doc->render(
			'DB list',
			$this->content
		);
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return	void
	 */
	function printContent()	{
		echo $this->content;
	}

}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/db_list.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/db_list.php']);
}



// Make instance:
$SOBE = t3lib_div::makeInstance('SC_db_list');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->clearCache();
$SOBE->main();
$SOBE->printContent();

?>
