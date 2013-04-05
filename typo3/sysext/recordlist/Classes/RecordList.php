<?php
namespace TYPO3\CMS\Recordlist;

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
 * Script Class for the Web > List module; rendering the listing of records on a page
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class RecordList {

	// Internal, GPvars:
	// Page Id for which to make the listing
	/**
	 * @var integer
	 * @todo Define visibility
	 */
	public $id;

	// Pointer - for browsing list of records.
	/**
	 * @todo Define visibility
	 */
	public $pointer;

	// Thumbnails or not
	/**
	 * @todo Define visibility
	 */
	public $imagemode;

	// Which table to make extended listing for
	/**
	 * @todo Define visibility
	 */
	public $table;

	// Search-fields
	/**
	 * @todo Define visibility
	 */
	public $search_field;

	// Search-levels
	/**
	 * @todo Define visibility
	 */
	public $search_levels;

	// Show-limit
	/**
	 * @todo Define visibility
	 */
	public $showLimit;

	// Return URL
	/**
	 * @todo Define visibility
	 */
	public $returnUrl;

	// Clear-cache flag - if set, clears page cache for current id.
	/**
	 * @todo Define visibility
	 */
	public $clear_cache;

	// Command: Eg. "delete" or "setCB" (for TCEmain / clipboard operations)
	/**
	 * @todo Define visibility
	 */
	public $cmd;

	// Table on which the cmd-action is performed.
	/**
	 * @todo Define visibility
	 */
	public $cmd_table;

	// Internal, static:
	// Page select perms clause
	/**
	 * @todo Define visibility
	 */
	public $perms_clause;

	// Module TSconfig
	/**
	 * @todo Define visibility
	 */
	public $modTSconfig;

	// Current ids page record
	/**
	 * @todo Define visibility
	 */
	public $pageinfo;

	/**
	 * Document template object
	 *
	 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
	 * @todo Define visibility
	 */
	public $doc;

	// Module configuration
	/**
	 * @todo Define visibility
	 */
	public $MCONF = array();

	// Menu configuration
	/**
	 * @todo Define visibility
	 */
	public $MOD_MENU = array();

	// Module settings (session variable)
	/**
	 * @todo Define visibility
	 */
	public $MOD_SETTINGS = array();

	// Array, where files to include is accumulated in the init() function
	/**
	 * @todo Define visibility
	 */
	public $include_once = array();

	// Internal, dynamic:
	// Module output accumulation
	/**
	 * @todo Define visibility
	 */
	public $content;

	/**
	 * Initializing the module
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function init() {
		// Setting module configuration / page select clause
		$this->MCONF = $GLOBALS['MCONF'];
		$this->perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
		// GPvars:
		$this->id = (int) \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id');
		$this->pointer = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('pointer');
		$this->imagemode = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('imagemode');
		$this->table = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('table');
		$this->search_field = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('search_field');
		$this->search_levels = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('search_levels');
		$this->showLimit = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('showLimit');
		$this->returnUrl = \TYPO3\CMS\Core\Utility\GeneralUtility::sanitizeLocalUrl(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('returnUrl'));
		$this->clear_cache = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('clear_cache');
		$this->cmd = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('cmd');
		$this->cmd_table = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('cmd_table');
		// Initialize menu
		$this->menuConfig();
	}

	/**
	 * Initialize function menu array
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function menuConfig() {
		// MENU-ITEMS:
		$this->MOD_MENU = array(
			'bigControlPanel' => '',
			'clipBoard' => '',
			'localization' => ''
		);
		// Loading module configuration:
		$this->modTSconfig = \TYPO3\CMS\Backend\Utility\BackendUtility::getModTSconfig($this->id, 'mod.' . $this->MCONF['name']);
		// Clean up settings:
		$this->MOD_SETTINGS = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleData($this->MOD_MENU, \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('SET'), $this->MCONF['name']);
	}

	/**
	 * Clears page cache for the current id, $this->id
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function clearCache() {
		if ($this->clear_cache) {
			$tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
			$tce->stripslashes_values = 0;
			$tce->start(array(), array());
			$tce->clear_cacheCmd($this->id);
		}
	}

	/**
	 * Main function, starting the rendering of the list.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function main() {
		// Start document template object:
		$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setModuleTemplate('templates/db_list.html');
		// Loading current page record and checking access:
		$this->pageinfo = \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess($this->id, $this->perms_clause);
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
		/** @var $dblist \TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList */
		$dblist = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Recordlist\\RecordList\\DatabaseRecordList');
		$dblist->backPath = $GLOBALS['BACK_PATH'];
		$dblist->script = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('web_list', array(), '');
		$dblist->calcPerms = $GLOBALS['BE_USER']->calcPerms($this->pageinfo);
		$dblist->thumbs = $GLOBALS['BE_USER']->uc['thumbnailsByDefault'];
		$dblist->returnUrl = $this->returnUrl;
		$dblist->allFields = $this->MOD_SETTINGS['bigControlPanel'] || $this->table ? 1 : 0;
		$dblist->localizationView = $this->MOD_SETTINGS['localization'];
		$dblist->showClipboard = 1;
		$dblist->disableSingleTableView = $this->modTSconfig['properties']['disableSingleTableView'];
		$dblist->listOnlyInSingleTableMode = $this->modTSconfig['properties']['listOnlyInSingleTableView'];
		$dblist->hideTables = $this->modTSconfig['properties']['hideTables'];
		$dblist->hideTranslations = $this->modTSconfig['properties']['hideTranslations'];
		$dblist->tableTSconfigOverTCA = $this->modTSconfig['properties']['table.'];
		$dblist->alternateBgColors = $this->modTSconfig['properties']['alternateBgColors'] ? 1 : 0;
		$dblist->allowedNewTables = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->modTSconfig['properties']['allowedNewTables'], 1);
		$dblist->deniedNewTables = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->modTSconfig['properties']['deniedNewTables'], 1);
		$dblist->newWizards = $this->modTSconfig['properties']['newWizards'] ? 1 : 0;
		$dblist->pageRow = $this->pageinfo;
		$dblist->counter++;
		$dblist->MOD_MENU = array('bigControlPanel' => '', 'clipBoard' => '', 'localization' => '');
		$dblist->modTSconfig = $this->modTSconfig;
		$clickTitleMode = trim($this->modTSconfig['properties']['clickTitleMode']);
		$dblist->clickTitleMode = $clickTitleMode === '' ? 'edit' : $clickTitleMode;
		// Clipboard is initialized:
		// Start clipboard
		$dblist->clipObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Clipboard\\Clipboard');
		// Initialize - reads the clipboard content from the user session
		$dblist->clipObj->initializeClipboard();
		// Clipboard actions are handled:
		// CB is the clipboard command array
		$CB = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('CB');
		if ($this->cmd == 'setCB') {
			// CBH is all the fields selected for the clipboard, CBC is the checkbox fields which were checked.
			// By merging we get a full array of checked/unchecked elements
			// This is set to the 'el' array of the CB after being parsed so only the table in question is registered.
			$CB['el'] = $dblist->clipObj->cleanUpCBC(array_merge((array) \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('CBH'), (array) \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('CBC')), $this->cmd_table);
		}
		if (!$this->MOD_SETTINGS['clipBoard']) {
			// If the clipboard is NOT shown, set the pad to 'normal'.
			$CB['setP'] = 'normal';
		}
		// Execute commands.
		$dblist->clipObj->setCmd($CB);
		// Clean up pad
		$dblist->clipObj->cleanCurrent();
		// Save the clipboard content
		$dblist->clipObj->endClipboard();
		// This flag will prevent the clipboard panel in being shown.
		// It is set, if the clickmenu-layer is active AND the extended view is not enabled.
		$dblist->dontShowClipControlPanels = $GLOBALS['CLIENT']['FORMSTYLE'] && !$this->MOD_SETTINGS['bigControlPanel'] && $dblist->clipObj->current == 'normal' && !$this->modTSconfig['properties']['showClipControlPanelsDespiteOfCMlayers'];
		// If there is access to the page, then render the list contents and set up the document template object:
		if ($access) {
			// Deleting records...:
			// Has not to do with the clipboard but is simply the delete action. The clipboard object is used to clean up the submitted entries to only the selected table.
			if ($this->cmd == 'delete') {
				$items = $dblist->clipObj->cleanUpCBC(\TYPO3\CMS\Core\Utility\GeneralUtility::_POST('CBC'), $this->cmd_table, 1);
				if (count($items)) {
					$cmd = array();
					foreach ($items as $iK => $value) {
						$iKParts = explode('|', $iK);
						$cmd[$iKParts[0]][$iKParts[1]]['delete'] = 1;
					}
					$tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
					$tce->stripslashes_values = 0;
					$tce->start(array(), $cmd);
					$tce->process_cmdmap();
					if (isset($cmd['pages'])) {
						\TYPO3\CMS\Backend\Utility\BackendUtility::setUpdateSignal('updatePageTree');
					}
					$tce->printLogErrorMessages(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI'));
				}
			}
			// Initialize the listing object, dblist, for rendering the list:
			$this->pointer = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->pointer, 0, 100000);
			$dblist->start($this->id, $this->table, $this->pointer, $this->search_field, $this->search_levels, $this->showLimit);
			$dblist->setDispFields();
			// Render versioning selector:
			if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('version')) {
				$dblist->HTMLcode .= $this->doc->getVersionSelector($this->id);
			}
			// Render the list of tables:
			$dblist->generateList();
			// Write the bottom of the page:
			$dblist->writeBottom();
			$listUrl = substr($dblist->listURL(), strlen($GLOBALS['BACK_PATH']));
			// Add JavaScript functions to the page:
			$this->doc->JScode = $this->doc->wrapScriptTags('
				function jumpToUrl(URL) {	//
					window.location.href = URL;
					return false;
				}
				function jumpExt(URL,anchor) {	//
					var anc = anchor?anchor:"";
					window.location.href = URL+(T3_THIS_LOCATION?"&returnUrl="+T3_THIS_LOCATION:"")+anc;
					return false;
				}
				function jumpSelf(URL) {	//
					window.location.href = URL+(T3_RETURN_URL?"&returnUrl="+T3_RETURN_URL:"");
					return false;
				}

				function setHighlight(id) {	//
					top.fsMod.recentIds["web"]=id;
					top.fsMod.navFrameHighlightedID["web"]="pages"+id+"_"+top.fsMod.currentBank;	// For highlighting

					if (top.content && top.content.nav_frame && top.content.nav_frame.refresh_nav) {
						top.content.nav_frame.refresh_nav();
					}
				}
				' . $this->doc->redirectUrls($listUrl) . '
				' . $dblist->CBfunctions() . '
				function editRecords(table,idList,addParams,CBflag) {	//
					window.location.href="' . $GLOBALS['BACK_PATH'] . 'alt_doc.php?returnUrl=' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI')) . '&edit["+table+"]["+idList+"]=edit"+addParams;
				}
				function editList(table,idList) {	//
					var list="";

						// Checking how many is checked, how many is not
					var pointer=0;
					var pos = idList.indexOf(",");
					while (pos!=-1) {
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

				if (top.fsMod) top.fsMod.recentIds["web"] = ' . intval($this->id) . ';
			');
			// Setting up the context sensitive menu:
			$this->doc->getContextMenuCode();
		}
		// access
		// Begin to compile the whole page, starting out with page header:
		$this->body = $this->doc->header($this->pageinfo['title']);
		$this->body .= '<form action="' . htmlspecialchars($dblist->listURL()) . '" method="post" name="dblistForm">';
		$this->body .= $dblist->HTMLcode;
		$this->body .= '<input type="hidden" name="cmd_table" /><input type="hidden" name="cmd" /></form>';
		// If a listing was produced, create the page footer with search form etc:
		if ($dblist->HTMLcode) {
			// Making field select box (when extended view for a single table is enabled):
			if ($dblist->table) {
				$this->body .= $dblist->fieldSelectBox($dblist->table);
			}
			// Adding checkbox options for extended listing and clipboard display:
			$this->body .= '

					<!--
						Listing options for extended view, clipboard and localization view
					-->
					<div id="typo3-listOptions">
						<form action="" method="post">';
			// Add "display bigControlPanel" checkbox:
			if ($this->modTSconfig['properties']['enableDisplayBigControlPanel'] === 'selectable') {
				$this->body .= \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncCheck($this->id, 'SET[bigControlPanel]', $this->MOD_SETTINGS['bigControlPanel'], '', $this->table ? '&table=' . $this->table : '', 'id="checkLargeControl"');
				$this->body .= '<label for="checkLargeControl">' . \TYPO3\CMS\Backend\Utility\BackendUtility::wrapInHelp('xMOD_csh_corebe', 'list_options', $GLOBALS['LANG']->getLL('largeControl', TRUE)) . '</label><br />';
			}
			// Add "clipboard" checkbox:
			if ($this->modTSconfig['properties']['enableClipBoard'] === 'selectable') {
				if ($dblist->showClipboard) {
					$this->body .= \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncCheck($this->id, 'SET[clipBoard]', $this->MOD_SETTINGS['clipBoard'], '', $this->table ? '&table=' . $this->table : '', 'id="checkShowClipBoard"');
					$this->body .= '<label for="checkShowClipBoard">' . \TYPO3\CMS\Backend\Utility\BackendUtility::wrapInHelp('xMOD_csh_corebe', 'list_options', $GLOBALS['LANG']->getLL('showClipBoard', TRUE)) . '</label><br />';
				}
			}
			// Add "localization view" checkbox:
			if ($this->modTSconfig['properties']['enableLocalizationView'] === 'selectable') {
				$this->body .= \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncCheck($this->id, 'SET[localization]', $this->MOD_SETTINGS['localization'], '', $this->table ? '&table=' . $this->table : '', 'id="checkLocalization"');
				$this->body .= '<label for="checkLocalization">' . \TYPO3\CMS\Backend\Utility\BackendUtility::wrapInHelp('xMOD_csh_corebe', 'list_options', $GLOBALS['LANG']->getLL('localization', TRUE)) . '</label><br />';
			}
			$this->body .= '
						</form>
					</div>';
			// Printing clipboard if enabled:
			if ($this->MOD_SETTINGS['clipBoard'] && $dblist->showClipboard) {
				$this->body .= '<div class="db_list-dashboard">' . $dblist->clipObj->printClipboard() . '</div>';
			}
			// Search box:
			if (!$this->modTSconfig['properties']['disableSearchBox']) {
				$sectionTitle = \TYPO3\CMS\Backend\Utility\BackendUtility::wrapInHelp('xMOD_csh_corebe', 'list_searchbox', $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.search', TRUE));
				$this->body .= '<div class="db_list-searchbox">' . $this->doc->section($sectionTitle, $dblist->getSearchBox(), FALSE, TRUE, FALSE, TRUE) . '</div>';
			}
			// Additional footer content
			$footerContentHook = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['recordlist/mod1/index.php']['drawFooterHook'];
			if (is_array($footerContentHook)) {
				foreach ($footerContentHook as $hook) {
					$params = array();
					$this->body .= \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($hook, $params, $this);
				}
			}
		}
		// Setting up the buttons and markers for docheader
		$docHeaderButtons = $dblist->getButtons();
		$markers = array(
			'CSH' => $docHeaderButtons['csh'],
			'CONTENT' => $this->body,
			'EXTRACONTAINERCLASS' => $this->table ? 'singletable' : ''
		);
		// Build the <body> for the module
		$this->content = $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		// Renders the module page
		$this->content = $this->doc->render('DB list', $this->content);
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function printContent() {
		echo $this->content;
	}

}


?>