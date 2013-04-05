<?php
namespace TYPO3\CMS\Backend\Controller;

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
 * Script Class for Web > Layout module
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class PageLayoutController {

	// Internal, GPvars:
	// Page Id for which to make the listing
	/**
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

	// PopView id - for opening a window with the page
	/**
	 * @todo Define visibility
	 */
	public $popView;

	// QuickEdit: Variable, that tells quick edit what to show/edit etc. Format is [tablename]:[uid] with some exceptional values for both parameters (with special meanings).
	/**
	 * @todo Define visibility
	 */
	public $edit_record;

	// QuickEdit: If set, this variable tells quick edit that the last edited record had this value as UID and we should look up the new, real uid value in sys_log.
	/**
	 * @todo Define visibility
	 */
	public $new_unique_uid;

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

	// Back path of the module
	/**
	 * @todo Define visibility
	 */
	public $backPath;

	// "Pseudo" Description -table name
	/**
	 * @todo Define visibility
	 */
	public $descrTable;

	// List of column-integers to edit. Is set from TSconfig, default is "1,0,2,3"
	/**
	 * @todo Define visibility
	 */
	public $colPosList;

	// Flag: If content can be edited or not.
	/**
	 * @todo Define visibility
	 */
	public $EDIT_CONTENT;

	// Users permissions integer for this page.
	/**
	 * @todo Define visibility
	 */
	public $CALC_PERMS;

	// Currently selected language for editing content elements
	/**
	 * @todo Define visibility
	 */
	public $current_sys_language;

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

	// Array of tables to be listed by the Web > Page module in addition to the default tables
	/**
	 * @todo Define visibility
	 */
	public $externalTables = array();

	// Internal, dynamic:
	// Module output accumulation
	/**
	 * @todo Define visibility
	 */
	public $content;

	// Function menu temporary storage
	/**
	 * @todo Define visibility
	 */
	public $topFuncMenu;

	// Temporary storage for page edit icon
	/**
	 * @todo Define visibility
	 */
	public $editIcon;

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
		$this->backPath = $GLOBALS['BACK_PATH'];
		// GPvars:
		$this->id = intval(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id'));
		$this->pointer = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('pointer');
		$this->imagemode = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('imagemode');
		$this->clear_cache = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('clear_cache');
		$this->popView = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('popView');
		$this->edit_record = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('edit_record');
		$this->new_unique_uid = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('new_unique_uid');
		$this->search_field = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('search_field');
		$this->search_levels = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('search_levels');
		$this->showLimit = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('showLimit');
		$this->returnUrl = \TYPO3\CMS\Core\Utility\GeneralUtility::sanitizeLocalUrl(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('returnUrl'));
		$this->externalTables = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables'];
		// Load page info array:
		$this->pageinfo = \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess($this->id, $this->perms_clause);
		// Initialize menu
		$this->menuConfig();
		// Setting sys language from session var:
		$this->current_sys_language = intval($this->MOD_SETTINGS['language']);
		// CSH / Descriptions:
		$this->descrTable = '_MOD_' . $this->MCONF['name'];
	}

	/**
	 * Initialize menu array
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function menuConfig() {
		// MENU-ITEMS:
		$this->MOD_MENU = array(
			'tt_content_showHidden' => '',
			'showPalettes' => '',
			'showDescriptions' => '',
			'disableRTE' => '',
			'function' => array(
				0 => $GLOBALS['LANG']->getLL('m_function_0'),
				1 => $GLOBALS['LANG']->getLL('m_function_1'),
				2 => $GLOBALS['LANG']->getLL('m_function_2')
			),
			'language' => array(
				0 => $GLOBALS['LANG']->getLL('m_default')
			)
		);
		// example settings:
		// 	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables']['tx_myext'] =
		//		array ('default' => array(
		//				'MENU' => 'LLL:EXT:tx_myext/locallang_db.xml:menuDefault',
		//				'fList' =>  'title,description,image',
		//				'icon' => TRUE));
		if (is_array($this->externalTables)) {
			foreach ($this->externalTables as $table => $tableSettings) {
				// delete the default settings from above
				if (is_array($this->MOD_MENU[$table])) {
					unset($this->MOD_MENU[$table]);
				}
				if (is_array($tableSettings) && count($tableSettings) > 1) {
					foreach ($tableSettings as $key => $settings) {
						$this->MOD_MENU[$table][$key] = $GLOBALS['LANG']->sL($settings['MENU']);
					}
				}
			}
		}
		// First, select all pages_language_overlay records on the current page. Each represents a possibility for a language on the page. Add these to language selector.
		$res = $this->exec_languageQuery($this->id);
		while ($lrow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			if ($GLOBALS['BE_USER']->checkLanguageAccess($lrow['uid'])) {
				$this->MOD_MENU['language'][$lrow['uid']] = $lrow['hidden'] ? '(' . $lrow['title'] . ')' : $lrow['title'];
			}
		}
		// Find if there are ANY languages at all (and if not, remove the language option from function menu).
		$count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('uid', 'sys_language', $GLOBALS['BE_USER']->isAdmin() ? '' : 'hidden=0');
		if (!$count) {
			unset($this->MOD_MENU['function']['2']);
		}
		// page/be_user TSconfig settings and blinding of menu-items
		$this->modSharedTSconfig = \TYPO3\CMS\Backend\Utility\BackendUtility::getModTSconfig($this->id, 'mod.SHARED');
		$this->modTSconfig = \TYPO3\CMS\Backend\Utility\BackendUtility::getModTSconfig($this->id, 'mod.' . $this->MCONF['name']);
		if ($this->modTSconfig['properties']['QEisDefault']) {
			ksort($this->MOD_MENU['function']);
		}
		$this->MOD_MENU['function'] = \TYPO3\CMS\Backend\Utility\BackendUtility::unsetMenuItems($this->modTSconfig['properties'], $this->MOD_MENU['function'], 'menu.function');
		// Remove QuickEdit as option if page type is not...
		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::inList(($GLOBALS['TYPO3_CONF_VARS']['FE']['content_doktypes'] . ',6'), $this->pageinfo['doktype'])) {
			unset($this->MOD_MENU['function'][0]);
		}
		// Setting alternative default label:
		if (($this->modSharedTSconfig['properties']['defaultLanguageLabel'] || $this->modTSconfig['properties']['defaultLanguageLabel']) && isset($this->MOD_MENU['language'][0])) {
			$this->MOD_MENU['language'][0] = $this->modTSconfig['properties']['defaultLanguageLabel'] ? $this->modSharedTSconfig['properties']['defaultLanguageLabel'] : $this->modSharedTSconfig['properties']['defaultLanguageLabel'];
		}
		// Clean up settings
		$this->MOD_SETTINGS = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleData($this->MOD_MENU, \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('SET'), $this->MCONF['name']);
		// For all elements to be shown in draft workspaces & to also show hidden elements by default if user hasn't disabled the option
		if ($GLOBALS['BE_USER']->workspace != 0 || $this->MOD_SETTINGS['tt_content_showHidden'] !== '0') {
			$this->MOD_SETTINGS['tt_content_showHidden'] = 1;
		}
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
	 * Generate the flashmessages for current pid
	 *
	 * @return string HTML content with flashmessages
	 */
	protected function getHeaderFlashMessagesForCurrentPid() {
		$content = '';
		// If page is a folder
		if ($this->pageinfo['doktype'] == \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_SYSFOLDER) {
			// Access to list module
			$moduleLoader = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Module\\ModuleLoader');
			$moduleLoader->load($GLOBALS['TBE_MODULES']);
			$modules = $moduleLoader->modules;
			if (is_array($modules['web']['sub']['list'])) {
				$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', '<p>' . $GLOBALS['LANG']->getLL('goToListModuleMessage') . '</p>
					<br />
					<p>' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-system-list-open') . '<a href="javascript:top.goToModule( \'web_list\',1);">' . $GLOBALS['LANG']->getLL('goToListModule') . '
						</a>
					</p>', '', \TYPO3\CMS\Core\Messaging\FlashMessage::INFO);
				$content .= $flashMessage->render();
			}
		}
		// If content from different pid is displayed
		if ($this->pageinfo['content_from_pid']) {
			$contentPage = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages', intval($this->pageinfo['content_from_pid']));
			$title = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle('pages', $contentPage);
			$linkToPid = $this->local_linkThisScript(array('id' => $this->pageinfo['content_from_pid']));
			$link = '<a href="' . $linkToPid . '">' . htmlspecialchars($title) . ' (PID ' . intval($this->pageinfo['content_from_pid']) . ')</a>';
			$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', sprintf($GLOBALS['LANG']->getLL('content_from_pid_title'), $link), '', \TYPO3\CMS\Core\Messaging\FlashMessage::INFO);
			$content .= $flashMessage->render();
		}
		return $content;
	}

	/**
	 *
	 * @return string $title
	 */
	protected function getLocalizedPageTitle() {
		if ($this->current_sys_language > 0) {
			$overlayRecord = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
				'title',
				'pages_language_overlay',
				'pid = ' . intval($this->id) .
						' AND sys_language_uid = ' . intval($this->current_sys_language) .
						\TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('pages_language_overlay') .
						\TYPO3\CMS\Backend\Utility\BackendUtility::versioningPlaceholderClause('pages_language_overlay'),
				'',
				'',
				'',
				'sys_language_uid'
			);
			return $overlayRecord['title'];
		} else {
			return $this->pageinfo['title'];
		}
	}

	/**
	 * Main function.
	 * Creates some general objects and calls other functions for the main rendering of module content.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function main() {
		// Access check...
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$access = is_array($this->pageinfo) ? 1 : 0;
		if ($this->id && $access) {
			// Initialize permission settings:
			$this->CALC_PERMS = $GLOBALS['BE_USER']->calcPerms($this->pageinfo);
			$this->EDIT_CONTENT = $this->CALC_PERMS & 16 ? 1 : 0;
			// Start document template object:
			$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
			$this->doc->backPath = $GLOBALS['BACK_PATH'];
			$this->doc->setModuleTemplate('templates/db_layout.html');
			// JavaScript:
			$this->doc->JScode = '<script type="text/javascript" ' . 'src="' . \TYPO3\CMS\Core\Utility\GeneralUtility::createVersionNumberedFilename(($GLOBALS['BACK_PATH'] . '../t3lib/jsfunc.updateform.js')) . '">' . '</script>';
			$this->doc->JScode .= $this->doc->wrapScriptTags('
				if (top.fsMod) top.fsMod.recentIds["web"] = ' . intval($this->id) . ';
				if (top.fsMod) top.fsMod.navFrameHighlightedID["web"] = "pages' . intval($this->id) . '_"+top.fsMod.currentBank; ' . intval($this->id) . ';
				function jumpToUrl(URL,formEl) {	//
					if (document.editform && TBE_EDITOR.isFormChanged)	{	// Check if the function exists... (works in all browsers?)
						if (!TBE_EDITOR.isFormChanged())	{	//
							window.location.href = URL;
						} else if (formEl) {
							if (formEl.type=="checkbox") formEl.checked = formEl.checked ? 0 : 1;
						}
					} else window.location.href = URL;
				}
			' . ($this->popView ? \TYPO3\CMS\Backend\Utility\BackendUtility::viewOnClick($this->id, $GLOBALS['BACK_PATH'], \TYPO3\CMS\Backend\Utility\BackendUtility::BEgetRootLine($this->id)) : '') . '

				function deleteRecord(table,id,url) {	//
					if (confirm(' . $GLOBALS['LANG']->JScharCode($GLOBALS['LANG']->getLL('deleteWarning')) . ')) {
						window.location.href = "' . $GLOBALS['BACK_PATH'] . 'tce_db.php?cmd["+table+"]["+id+"][delete]=1&redirect="+escape(url)+"&vC=' . $GLOBALS['BE_USER']->veriCode() . \TYPO3\CMS\Backend\Utility\BackendUtility::getUrlToken('tceAction') . '&prErr=1&uPT=1";
					}
					return false;
				}
			');
			$this->doc->JScode .= $this->doc->wrapScriptTags('
				var DTM_array = new Array();
				var DTM_origClass = new String();

					// if tabs are used in a popup window the array might not exists
				if(!top.DTM_currentTabs) {
					top.DTM_currentTabs = new Array();
				}

				function DTM_activate(idBase,index,doToogle) {	//
						// Hiding all:
					if (DTM_array[idBase]) {
						for(cnt = 0; cnt < DTM_array[idBase].length ; cnt++) {
							if (DTM_array[idBase][cnt] != idBase+"-"+index) {
								document.getElementById(DTM_array[idBase][cnt]+"-DIV").style.display = "none";
								document.getElementById(DTM_array[idBase][cnt]+"-MENU").attributes.getNamedItem("class").nodeValue = "tab";
							}
						}
					}

						// Showing one:
					if (document.getElementById(idBase+"-"+index+"-DIV")) {
						if (doToogle && document.getElementById(idBase+"-"+index+"-DIV").style.display == "block") {
							document.getElementById(idBase+"-"+index+"-DIV").style.display = "none";
							if(DTM_origClass=="") {
								document.getElementById(idBase+"-"+index+"-MENU").attributes.getNamedItem("class").nodeValue = "tab";
							} else {
								DTM_origClass = "tab";
							}
							top.DTM_currentTabs[idBase] = -1;
						} else {
							document.getElementById(idBase+"-"+index+"-DIV").style.display = "block";
							if(DTM_origClass=="") {
								document.getElementById(idBase+"-"+index+"-MENU").attributes.getNamedItem("class").nodeValue = "tabact";
							} else {
								DTM_origClass = "tabact";
							}
							top.DTM_currentTabs[idBase] = index;
						}
					}
				}
				function DTM_toggle(idBase,index,isInit) {	//
						// Showing one:
					if (document.getElementById(idBase+"-"+index+"-DIV")) {
						if (document.getElementById(idBase+"-"+index+"-DIV").style.display == "block") {
							document.getElementById(idBase+"-"+index+"-DIV").style.display = "none";
							if(isInit) {
								document.getElementById(idBase+"-"+index+"-MENU").attributes.getNamedItem("class").nodeValue = "tab";
							} else {
								DTM_origClass = "tab";
							}
							top.DTM_currentTabs[idBase+"-"+index] = 0;
						} else {
							document.getElementById(idBase+"-"+index+"-DIV").style.display = "block";
							if(isInit) {
								document.getElementById(idBase+"-"+index+"-MENU").attributes.getNamedItem("class").nodeValue = "tabact";
							} else {
								DTM_origClass = "tabact";
							}
							top.DTM_currentTabs[idBase+"-"+index] = 1;
						}
					}
				}

				function DTM_mouseOver(obj) {	//
						DTM_origClass = obj.attributes.getNamedItem(\'class\').nodeValue;
						obj.attributes.getNamedItem(\'class\').nodeValue += "_over";
				}

				function DTM_mouseOut(obj) {	//
						obj.attributes.getNamedItem(\'class\').nodeValue = DTM_origClass;
						DTM_origClass = "";
				}
			');
			// Setting doc-header
			$this->doc->form = '<form action="' . htmlspecialchars(('db_layout.php?id=' . $this->id . '&imagemode=' . $this->imagemode)) . '" method="post">';
			// Creating the top function menu:
			$this->topFuncMenu = \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncMenu($this->id, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function'], 'db_layout.php', '');
			$this->languageMenu = count($this->MOD_MENU['language']) > 1 ? $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_general.xlf:LGL.language', 1) . \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncMenu($this->id, 'SET[language]', $this->current_sys_language, $this->MOD_MENU['language'], 'db_layout.php', '') : '';
			// Find backend layout / coumns
			$backendLayout = \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction('TYPO3\\CMS\\Backend\\View\\BackendLayoutView->getSelectedBackendLayout', $this->id, $this);
			if (count($backendLayout['__colPosList'])) {
				$this->colPosList = implode(',', $backendLayout['__colPosList']);
			}
			// Removing duplicates, if any
			$this->colPosList = implode(',', array_unique(\TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $this->colPosList)));
			// Page title
			$body = $this->doc->header($this->getLocalizedPageTitle());
			$body .= $this->getHeaderFlashMessagesForCurrentPid();
			// Render the primary module content:
			if ($this->MOD_SETTINGS['function'] == 0) {
				// QuickEdit
				$body .= $this->renderQuickEdit();
			} else {
				// All other listings
				$body .= $this->renderListContent();
			}
			// Setting up the buttons and markers for docheader
			$docHeaderButtons = $this->getButtons($this->MOD_SETTINGS['function'] == 0 ? 'quickEdit' : '');
			$markers = array(
				'CSH' => $docHeaderButtons['csh'],
				'TOP_FUNCTION_MENU' => $this->topFuncMenu . $this->editSelect,
				'LANGSELECTOR' => $this->languageMenu,
				'CONTENT' => $body
			);
			// Build the <body> for the module
			$this->content .= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
			// Renders the module page
			$this->content = $this->doc->render($GLOBALS['LANG']->getLL('title'), $this->content);
		} else {
			// If no access or id value, create empty document:
			$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
			$this->doc->backPath = $GLOBALS['BACK_PATH'];
			$this->doc->setModuleTemplate('templates/db_layout.html');
			$this->doc->JScode = $this->doc->wrapScriptTags('
				if (top.fsMod) top.fsMod.recentIds["web"] = ' . intval($this->id) . ';
			');
			$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $GLOBALS['LANG']->getLL('clickAPage_content'), $GLOBALS['LANG']->getLL('clickAPage_header'), \TYPO3\CMS\Core\Messaging\FlashMessage::INFO);
			$body = $flashMessage->render();
			// Setting up the buttons and markers for docheader
			$docHeaderButtons = array(
				'view' => '',
				'history_page' => '',
				'new_content' => '',
				'move_page' => '',
				'move_record' => '',
				'new_page' => '',
				'edit_page' => '',
				'csh' => '',
				'shortcut' => '',
				'cache' => '',
				'savedok' => '',
				'savedokshow' => '',
				'closedok' => '',
				'deletedok' => '',
				'undo' => '',
				'history_record' => ''
			);
			$markers = array(
				'CSH' => \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem($this->descrTable, '', $GLOBALS['BACK_PATH'], '', TRUE),
				'TOP_FUNCTION_MENU' => '',
				'LANGSELECTOR' => '',
				'CONTENT' => $body
			);
			$this->content .= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
			// Renders the module page
			$this->content = $this->doc->render($GLOBALS['LANG']->getLL('title'), $this->content);
		}
	}

	/**
	 * Rendering the quick-edit view.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function renderQuickEdit() {
		// Alternative template
		$this->doc->setModuleTemplate('templates/db_layout_quickedit.html');
		// Alternative form tag; Quick Edit submits its content to tce_db.php.
		$this->doc->form = '<form action="' . htmlspecialchars(($GLOBALS['BACK_PATH'] . 'tce_db.php?&prErr=1&uPT=1')) . '" method="post" enctype="' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'] . '" name="editform" onsubmit="return TBE_EDITOR.checkSubmit(1);">';
		// Setting up the context sensitive menu:
		$this->doc->getContextMenuCode();
		// Set the edit_record value for internal use in this function:
		$edit_record = $this->edit_record;
		// If a command to edit all records in a column is issue, then select all those elements, and redirect to alt_doc.php:
		if (substr($edit_record, 0, 9) == '_EDIT_COL') {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_content', 'pid=' . intval($this->id) . ' AND colPos=' . intval(substr($edit_record, 10)) . ' AND sys_language_uid=' . intval($this->current_sys_language) . ($this->MOD_SETTINGS['tt_content_showHidden'] ? '' : \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('tt_content')) . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tt_content') . \TYPO3\CMS\Backend\Utility\BackendUtility::versioningPlaceholderClause('tt_content'), '', 'sorting');
			$idListA = array();
			while ($cRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$idListA[] = $cRow['uid'];
			}
			$url = $GLOBALS['BACK_PATH'] . 'alt_doc.php?edit[tt_content][' . implode(',', $idListA) . ']=edit&returnUrl=' . rawurlencode($this->local_linkThisScript(array('edit_record' => '')));
			\TYPO3\CMS\Core\Utility\HttpUtility::redirect($url);
		}
		// If the former record edited was the creation of a NEW record, this will look up the created records uid:
		if ($this->new_unique_uid) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_log', 'userid=' . intval($GLOBALS['BE_USER']->user['uid']) . ' AND NEWid=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($this->new_unique_uid, 'sys_log'));
			$sys_log_row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			if (is_array($sys_log_row)) {
				$edit_record = $sys_log_row['tablename'] . ':' . $sys_log_row['recuid'];
			}
		}
		// Creating the selector box, allowing the user to select which element to edit:
		$opt = array();
		$is_selected = 0;
		$languageOverlayRecord = '';
		if ($this->current_sys_language) {
			list($languageOverlayRecord) = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordsByField('pages_language_overlay', 'pid', $this->id, 'AND sys_language_uid=' . intval($this->current_sys_language));
		}
		if (is_array($languageOverlayRecord)) {
			$inValue = 'pages_language_overlay:' . $languageOverlayRecord['uid'];
			$is_selected += intval($edit_record == $inValue);
			$opt[] = '<option value="' . $inValue . '"' . ($edit_record == $inValue ? ' selected="selected"' : '') . '>[ ' . $GLOBALS['LANG']->getLL('editLanguageHeader', 1) . ' ]</option>';
		} else {
			$inValue = 'pages:' . $this->id;
			$is_selected += intval($edit_record == $inValue);
			$opt[] = '<option value="' . $inValue . '"' . ($edit_record == $inValue ? ' selected="selected"' : '') . '>[ ' . $GLOBALS['LANG']->getLL('editPageProperties', 1) . ' ]</option>';
		}
		// Selecting all content elements from this language and allowed colPos:
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_content', 'pid=' . intval($this->id) . ' AND sys_language_uid=' . intval($this->current_sys_language) . ' AND colPos IN (' . $this->colPosList . ')' . ($this->MOD_SETTINGS['tt_content_showHidden'] ? '' : \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('tt_content')) . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tt_content') . \TYPO3\CMS\Backend\Utility\BackendUtility::versioningPlaceholderClause('tt_content'), '', 'colPos,sorting');
		$colPos = '';
		$first = 1;
		// Page is the pid if no record to put this after.
		$prev = $this->id;
		while ($cRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			\TYPO3\CMS\Backend\Utility\BackendUtility::workspaceOL('tt_content', $cRow);
			if (is_array($cRow)) {
				if ($first) {
					if (!$edit_record) {
						$edit_record = 'tt_content:' . $cRow['uid'];
					}
					$first = 0;
				}
				if (strcmp($cRow['colPos'], $colPos)) {
					$colPos = $cRow['colPos'];
					$opt[] = '<option value=""></option>';
					$opt[] = '<option value="_EDIT_COL:' . $colPos . '">__' . $GLOBALS['LANG']->sL(\TYPO3\CMS\Backend\Utility\BackendUtility::getLabelFromItemlist('tt_content', 'colPos', $colPos), 1) . ':__</option>';
				}
				$inValue = 'tt_content:' . $cRow['uid'];
				$is_selected += intval($edit_record == $inValue);
				$opt[] = '<option value="' . $inValue . '"' . ($edit_record == $inValue ? ' selected="selected"' : '') . '>' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs(($cRow['header'] ? $cRow['header'] : '[' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.no_title') . '] ' . strip_tags($cRow['bodytext'])), $GLOBALS['BE_USER']->uc['titleLen'])) . '</option>';
				$prev = -$cRow['uid'];
			}
		}
		// If edit_record is not set (meaning, no content elements was found for this language) we simply set it to create a new element:
		if (!$edit_record) {
			$edit_record = 'tt_content:new/' . $prev . '/' . $colPos;
			$inValue = 'tt_content:new/' . $prev . '/' . $colPos;
			$is_selected += intval($edit_record == $inValue);
			$opt[] = '<option value="' . $inValue . '"' . ($edit_record == $inValue ? ' selected="selected"' : '') . '>[ ' . $GLOBALS['LANG']->getLL('newLabel', 1) . ' ]</option>';
		}
		// If none is yet selected...
		if (!$is_selected) {
			$opt[] = '<option value=""></option>';
			$opt[] = '<option value="' . $edit_record . '"  selected="selected">[ ' . $GLOBALS['LANG']->getLL('newLabel', 1) . ' ]</option>';
		}
		// Splitting the edit-record cmd value into table/uid:
		$this->eRParts = explode(':', $edit_record);
		// Delete-button flag?
		$this->deleteButton = \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($this->eRParts[1]) && $edit_record && ($this->eRParts[0] != 'pages' && $this->EDIT_CONTENT || $this->eRParts[0] == 'pages' && $this->CALC_PERMS & 4);
		// If undo-button should be rendered (depends on available items in sys_history)
		$this->undoButton = 0;
		$undoRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tstamp', 'sys_history', 'tablename=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($this->eRParts[0], 'sys_history') . ' AND recuid=' . intval($this->eRParts[1]), '', 'tstamp DESC', '1');
		if ($this->undoButtonR = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($undoRes)) {
			$this->undoButton = 1;
		}
		// Setting up the Return URL for coming back to THIS script (if links take the user to another script)
		$R_URL_parts = parse_url(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI'));
		$R_URL_getvars = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET();
		unset($R_URL_getvars['popView']);
		unset($R_URL_getvars['new_unique_uid']);
		$R_URL_getvars['edit_record'] = $edit_record;
		$this->R_URI = $R_URL_parts['path'] . '?' . \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl('', $R_URL_getvars);
		// Setting close url/return url for exiting this script:
		// Goes to 'Columns' view if close is pressed (default)
		$this->closeUrl = $this->local_linkThisScript(array('SET' => array('function' => 1)));
		if ($GLOBALS['BE_USER']->uc['condensedMode']) {
			$this->closeUrl = $GLOBALS['BACK_PATH'] . 'alt_db_navframe.php';
		}
		if ($this->returnUrl) {
			$this->closeUrl = $this->returnUrl;
		}
		// Return-url for JavaScript:
		$retUrlStr = $this->returnUrl ? '+\'&returnUrl=\'+\'' . rawurlencode($this->returnUrl) . '\'' : '';
		// Drawing the edit record selectbox
		$this->editSelect = '<select name="edit_record" onchange="' . htmlspecialchars(('jumpToUrl(\'db_layout.php?id=' . $this->id . '&edit_record=\'+escape(this.options[this.selectedIndex].value)' . $retUrlStr . ',this);')) . '">' . implode('', $opt) . '</select>';
		// Creating editing form:
		if ($GLOBALS['BE_USER']->check('tables_modify', $this->eRParts[0]) && $edit_record && ($this->eRParts[0] !== 'pages' && $this->EDIT_CONTENT || $this->eRParts[0] === 'pages' && $this->CALC_PERMS & 1)) {
			// Splitting uid parts for special features, if new:
			list($uidVal, $ex_pid, $ex_colPos) = explode('/', $this->eRParts[1]);
			// Convert $uidVal to workspace version if any:
			if ($uidVal != 'new') {
				if ($draftRecord = \TYPO3\CMS\Backend\Utility\BackendUtility::getWorkspaceVersionOfRecord($GLOBALS['BE_USER']->workspace, $this->eRParts[0], $uidVal, 'uid')) {
					$uidVal = $draftRecord['uid'];
				}
			}
			// Initializing transfer-data object:
			$trData = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Form\\DataPreprocessor');
			$trData->addRawData = TRUE;
			$trData->defVals[$this->eRParts[0]] = array(
				'colPos' => intval($ex_colPos),
				'sys_language_uid' => intval($this->current_sys_language)
			);
			$trData->disableRTE = $this->MOD_SETTINGS['disableRTE'];
			$trData->lockRecords = 1;
			// 'new'
			$trData->fetchRecord($this->eRParts[0], $uidVal == 'new' ? $this->id : $uidVal, $uidVal);
			// Getting/Making the record:
			reset($trData->regTableItems_data);
			$rec = current($trData->regTableItems_data);
			if ($uidVal == 'new') {
				$new_unique_uid = uniqid('NEW');
				$rec['uid'] = $new_unique_uid;
				$rec['pid'] = intval($ex_pid) ? intval($ex_pid) : $this->id;
				$recordAccess = TRUE;
			} else {
				$rec['uid'] = $uidVal;
				// Checking internals access:
				$recordAccess = $GLOBALS['BE_USER']->recordEditAccessInternals($this->eRParts[0], $uidVal);
			}
			if (!$recordAccess) {
				// If no edit access, print error message:
				$content .= $this->doc->section($GLOBALS['LANG']->getLL('noAccess'), $GLOBALS['LANG']->getLL('noAccess_msg') . '<br /><br />' . ($GLOBALS['BE_USER']->errorMsg ? 'Reason: ' . $GLOBALS['BE_USER']->errorMsg . '<br /><br />' : ''), 0, 1);
			} elseif (is_array($rec)) {
				// If the record is an array (which it will always be... :-)
				// Create instance of TCEforms, setting defaults:
				$tceforms = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Form\\FormEngine');
				$tceforms->backPath = $GLOBALS['BACK_PATH'];
				$tceforms->initDefaultBEMode();
				$tceforms->fieldOrder = $this->modTSconfig['properties']['tt_content.']['fieldOrder'];
				$tceforms->palettesCollapsed = !$this->MOD_SETTINGS['showPalettes'];
				$tceforms->disableRTE = $this->MOD_SETTINGS['disableRTE'];
				$tceforms->enableClickMenu = TRUE;
				// Clipboard is initialized:
				// Start clipboard
				$tceforms->clipObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Clipboard\\Clipboard');
				// Initialize - reads the clipboard content from the user session
				$tceforms->clipObj->initializeClipboard();
				// Render form, wrap it:
				$panel = '';
				$panel .= $tceforms->getMainFields($this->eRParts[0], $rec);
				$panel = $tceforms->wrapTotal($panel, $rec, $this->eRParts[0]);
				// Add hidden fields:
				$theCode = $panel;
				if ($uidVal == 'new') {
					$theCode .= '<input type="hidden" name="data[' . $this->eRParts[0] . '][' . $rec['uid'] . '][pid]" value="' . $rec['pid'] . '" />';
				}
				$theCode .= '
					<input type="hidden" name="_serialNumber" value="' . md5(microtime()) . '" />
					<input type="hidden" name="_disableRTE" value="' . $tceforms->disableRTE . '" />
					<input type="hidden" name="edit_record" value="' . $edit_record . '" />
					<input type="hidden" name="redirect" value="' . htmlspecialchars(($uidVal == 'new' ? \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('cms') . 'layout/db_layout.php?id=' . $this->id . '&new_unique_uid=' . $new_unique_uid . '&returnUrl=' . rawurlencode($this->returnUrl) : $this->R_URI)) . '" />
					' . \TYPO3\CMS\Backend\Form\FormEngine::getHiddenTokenField('tceAction');
				// Add JavaScript as needed around the form:
				$theCode = $tceforms->printNeededJSFunctions_top() . $theCode . $tceforms->printNeededJSFunctions();
				// Add warning sign if record was "locked":
				if ($lockInfo = \TYPO3\CMS\Backend\Utility\BackendUtility::isRecordLocked($this->eRParts[0], $rec['uid'])) {
					$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', htmlspecialchars($lockInfo['msg']), '', \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING);
					/** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
					$flashMessageService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessageService');
					/** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
					$defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
					$defaultFlashMessageQueue->enqueue($flashMessage);
				}
				// Add whole form as a document section:
				$content .= $this->doc->section('', $theCode);
			}
		} else {
			// If no edit access, print error message:
			$content .= $this->doc->section($GLOBALS['LANG']->getLL('noAccess'), $GLOBALS['LANG']->getLL('noAccess_msg') . '<br /><br />', 0, 1);
		}
		// Bottom controls (function menus):
		$q_count = $this->getNumberOfHiddenElements();
		$h_func_b = \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncCheck($this->id, 'SET[tt_content_showHidden]', $this->MOD_SETTINGS['tt_content_showHidden'], 'db_layout.php', '', 'id="checkTt_content_showHidden"') . '<label for="checkTt_content_showHidden">' . (!$q_count ? $GLOBALS['TBE_TEMPLATE']->dfw($GLOBALS['LANG']->getLL('hiddenCE', 1)) : $GLOBALS['LANG']->getLL('hiddenCE', 1) . ' (' . $q_count . ')') . '</label>';
		$h_func_b .= '<br />' . \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncCheck($this->id, 'SET[showPalettes]', $this->MOD_SETTINGS['showPalettes'], 'db_layout.php', '', 'id="checkShowPalettes"') . '<label for="checkShowPalettes">' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.showPalettes', 1) . '</label>';
		if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('context_help')) {
			$h_func_b .= '<br />' . \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncCheck($this->id, 'SET[showDescriptions]', $this->MOD_SETTINGS['showDescriptions'], 'db_layout.php', '', 'id="checkShowDescriptions"') . '<label for="checkShowDescriptions">' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.showDescriptions', 1) . '</label>';
		}
		if ($GLOBALS['BE_USER']->isRTE()) {
			$h_func_b .= '<br />' . \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncCheck($this->id, 'SET[disableRTE]', $this->MOD_SETTINGS['disableRTE'], 'db_layout.php', '', 'id="checkDisableRTE"') . '<label for="checkDisableRTE">' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.disableRTE', 1) . '</label>';
		}
		// Add the function menus to bottom:
		$content .= $this->doc->section('', $h_func_b, 0, 0);
		$content .= $this->doc->spacer(10);
		// Select element matrix:
		if ($this->eRParts[0] == 'tt_content' && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($this->eRParts[1])) {
			$posMap = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('ext_posMap');
			$posMap->backPath = $GLOBALS['BACK_PATH'];
			$posMap->cur_sys_language = $this->current_sys_language;
			$HTMLcode = '';
			// CSH:
			$HTMLcode .= \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem($this->descrTable, 'quickEdit_selElement', $GLOBALS['BACK_PATH'], '|<br />');
			$HTMLcode .= $posMap->printContentElementColumns($this->id, $this->eRParts[1], $this->colPosList, $this->MOD_SETTINGS['tt_content_showHidden'], $this->R_URI);
			$content .= $this->doc->spacer(20);
			$content .= $this->doc->section($GLOBALS['LANG']->getLL('CEonThisPage'), $HTMLcode, 0, 1);
			$content .= $this->doc->spacer(20);
		}
		// Finally, if comments were generated in TCEforms object, print these as a HTML comment:
		if (count($tceforms->commentMessages)) {
			$content .= '
	<!-- TCEFORM messages
	' . htmlspecialchars(implode(LF, $tceforms->commentMessages)) . '
	-->
	';
		}
		return $content;
	}

	/**
	 * Rendering all other listings than QuickEdit
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function renderListContent() {
		// Initialize list object (see "class.db_layout.inc"):
		/** @var $dblist \TYPO3\CMS\Backend\View\PageLayoutView */
		$dblist = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\View\\PageLayoutView');
		$dblist->backPath = $GLOBALS['BACK_PATH'];
		$dblist->thumbs = $this->imagemode;
		$dblist->no_noWrap = 1;
		$dblist->descrTable = $this->descrTable;
		$this->pointer = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->pointer, 0, 100000);
		$dblist->script = 'db_layout.php';
		$dblist->showIcon = 0;
		$dblist->setLMargin = 0;
		$dblist->doEdit = $this->EDIT_CONTENT;
		$dblist->ext_CALC_PERMS = $this->CALC_PERMS;
		$dblist->agePrefixes = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.minutesHoursDaysYears');
		$dblist->id = $this->id;
		$dblist->nextThree = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->modTSconfig['properties']['editFieldsAtATime'], 0, 10);
		$dblist->option_showBigButtons = $this->modTSconfig['properties']['disableBigButtons'] === '0';
		$dblist->option_newWizard = $this->modTSconfig['properties']['disableNewContentElementWizard'] ? 0 : 1;
		$dblist->defLangBinding = $this->modTSconfig['properties']['defLangBinding'] ? 1 : 0;
		if (!$dblist->nextThree) {
			$dblist->nextThree = 1;
		}
		$dblist->externalTables = $this->externalTables;
		// Create menu for selecting a table to jump to (this is, if more than just pages/tt_content elements are found on the page!)
		$h_menu = $dblist->getTableMenu($this->id);
		// Initialize other variables:
		$h_func = '';
		$tableOutput = array();
		$tableJSOutput = array();
		$CMcounter = 0;
		// Traverse the list of table names which has records on this page (that array is populated
		// by the $dblist object during the function getTableMenu()):
		foreach ($dblist->activeTables as $table => $value) {
			if (!isset($dblist->externalTables[$table])) {
				$q_count = $this->getNumberOfHiddenElements();
				$h_func_b = \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncCheck($this->id, 'SET[tt_content_showHidden]', $this->MOD_SETTINGS['tt_content_showHidden'], 'db_layout.php', '', 'id="checkTt_content_showHidden"') . '<label for="checkTt_content_showHidden">' . (!$q_count ? $GLOBALS['TBE_TEMPLATE']->dfw($GLOBALS['LANG']->getLL('hiddenCE')) : $GLOBALS['LANG']->getLL('hiddenCE') . ' (' . $q_count . ')') . '</label>';
				// Boolean: Display up/down arrows and edit icons for tt_content records
				$dblist->tt_contentConfig['showCommands'] = 1;
				// Boolean: Display info-marks or not
				$dblist->tt_contentConfig['showInfo'] = 1;
				// Boolean: If set, the content of column(s) $this->tt_contentConfig['showSingleCol'] is shown
				// in the total width of the page
				$dblist->tt_contentConfig['single'] = 0;
				if ($this->MOD_SETTINGS['function'] == 4) {
					// Grid view
					$dblist->tt_contentConfig['showAsGrid'] = 1;
				}
				// Setting up the tt_content columns to show:
				if (is_array($GLOBALS['TCA']['tt_content']['columns']['colPos']['config']['items'])) {
					$colList = array();
					$tcaItems = \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction('EXT:cms/classes/class.tx_cms_backendlayout.php:TYPO3\\CMS\\Backend\\View\\BackendLayoutView->getColPosListItemsParsed', $this->id, $this);
					foreach ($tcaItems as $temp) {
						$colList[] = $temp[1];
					}
				} else {
					// ... should be impossible that colPos has no array. But this is the fallback should it make any sense:
					$colList = array('1', '0', '2', '3');
				}
				if (strcmp($this->colPosList, '')) {
					$colList = array_intersect(\TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $this->colPosList), $colList);
				}
				// If only one column found, display the single-column view.
				if (count($colList) === 1 && !$this->MOD_SETTINGS['function'] === 4) {
					// Boolean: If set, the content of column(s) $this->tt_contentConfig['showSingleCol']
					// is shown in the total width of the page
					$dblist->tt_contentConfig['single'] = 1;
					// The column(s) to show if single mode (under each other)
					$dblist->tt_contentConfig['showSingleCol'] = current($colList);
				}
				// The order of the rows: Default is left(1), Normal(0), right(2), margin(3)
				$dblist->tt_contentConfig['cols'] = implode(',', $colList);
				$dblist->tt_contentConfig['showHidden'] = $this->MOD_SETTINGS['tt_content_showHidden'];
				$dblist->tt_contentConfig['sys_language_uid'] = intval($this->current_sys_language);
				// If the function menu is set to "Language":
				if ($this->MOD_SETTINGS['function'] == 2) {
					$dblist->tt_contentConfig['single'] = 0;
					$dblist->tt_contentConfig['languageMode'] = 1;
					$dblist->tt_contentConfig['languageCols'] = $this->MOD_MENU['language'];
					$dblist->tt_contentConfig['languageColsPointer'] = $this->current_sys_language;
				}
			} else {
				if (isset($this->MOD_SETTINGS) && isset($this->MOD_MENU)) {
					$h_func = \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncMenu($this->id, 'SET[' . $table . ']', $this->MOD_SETTINGS[$table], $this->MOD_MENU[$table], 'db_layout.php', '');
				} else {
					$h_func = '';
				}
			}
			// Start the dblist object:
			$dblist->itemsLimitSingleTable = 1000;
			$dblist->start($this->id, $table, $this->pointer, $this->search_field, $this->search_levels, $this->showLimit);
			$dblist->counter = $CMcounter;
			$dblist->ext_function = $this->MOD_SETTINGS['function'];
			// Render versioning selector:
			$dblist->HTMLcode .= $this->doc->getVersionSelector($this->id);
			// Generate the list of elements here:
			$dblist->generateList();
			// Adding the list content to the tableOutput variable:
			$tableOutput[$table] = ($h_func ? $h_func . '<br /><img src="clear.gif" width="1" height="4" alt="" /><br />' : '') . $dblist->HTMLcode . ($h_func_b ? '<img src="clear.gif" width="1" height="10" alt="" /><br />' . $h_func_b : '');
			// ... and any accumulated JavaScript goes the same way!
			$tableJSOutput[$table] = $dblist->JScode;
			// Increase global counter:
			$CMcounter += $dblist->counter;
			// Reset variables after operation:
			$dblist->HTMLcode = '';
			$dblist->JScode = '';
			$h_func = '';
			$h_func_b = '';
		}
		// END: traverse tables
		// For Context Sensitive Menus:
		$this->doc->getContextMenuCode();
		// Add the content for each table we have rendered (traversing $tableOutput variable)
		foreach ($tableOutput as $table => $output) {
			$content .= $this->doc->section('', $output, TRUE, TRUE, 0, TRUE);
			$content .= $this->doc->spacer(15);
			$content .= $this->doc->sectionEnd();
		}
		// Making search form:
		if (!$this->modTSconfig['properties']['disableSearchBox'] && count($tableOutput)) {
			$sectionTitle = \TYPO3\CMS\Backend\Utility\BackendUtility::wrapInHelp('xMOD_csh_corebe', 'list_searchbox', $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.search', TRUE));
			$content .= $this->doc->section($sectionTitle, $dblist->getSearchBox(0), FALSE, TRUE, FALSE, TRUE);
		}
		// Additional footer content
		$footerContentHook = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/db_layout.php']['drawFooterHook'];
		if (is_array($footerContentHook)) {
			foreach ($footerContentHook as $hook) {
				$params = array();
				$content .= \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($hook, $params, $this);
			}
		}
		return $content;
	}

	/**
	 * Print accumulated content of module
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function printContent() {
		echo $this->content;
	}

	/***************************
	 *
	 * Sub-content functions, rendering specific parts of the module content.
	 *
	 ***************************/
	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @param string $function Identifier for function of module
	 * @return array all available buttons as an assoc. array
	 */
	protected function getButtons($function = '') {
		$buttons = array(
			'view' => '',
			'history_page' => '',
			'new_content' => '',
			'move_page' => '',
			'move_record' => '',
			'new_page' => '',
			'edit_page' => '',
			'csh' => '',
			'shortcut' => '',
			'cache' => '',
			'savedok' => '',
			'save_close' => '',
			'savedokshow' => '',
			'closedok' => '',
			'deletedok' => '',
			'undo' => '',
			'history_record' => ''
		);
		// View page
		$buttons['view'] = '<a href="#" onclick="' . htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::viewOnClick($this->pageinfo['uid'], $GLOBALS['BACK_PATH'], \TYPO3\CMS\Backend\Utility\BackendUtility::BEgetRootLine($this->pageinfo['uid']))) . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.showPage', TRUE) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-view') . '</a>';
		// Shortcut
		if ($GLOBALS['BE_USER']->mayMakeShortcut()) {
			$buttons['shortcut'] = $this->doc->makeShortcutIcon('id, edit_record, pointer, new_unique_uid, search_field, search_levels, showLimit', implode(',', array_keys($this->MOD_MENU)), $this->MCONF['name']);
		}
		// Cache
		if (!$this->modTSconfig['properties']['disableAdvanced']) {
			$buttons['cache'] = '<a href="' . htmlspecialchars(('db_layout.php?id=' . $this->pageinfo['uid'] . '&clear_cache=1')) . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.clear_cache', TRUE) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-system-cache-clear') . '</a>';
		}
		if (!$this->modTSconfig['properties']['disableIconToolbar']) {
			// Move record
			if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($this->eRParts[1])) {
				$buttons['move_record'] = '<a href="' . htmlspecialchars(($GLOBALS['BACK_PATH'] . 'move_el.php?table=' . $this->eRParts[0] . '&uid=' . $this->eRParts[1] . '&returnUrl=' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI')))) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon(('actions-' . ($this->eRParts[0] == 'tt_content' ? 'document' : 'page') . '-move'), array('class' => 'c-inputButton', 'title' => $GLOBALS['LANG']->getLL(('move_' . ($this->eRParts[0] == 'tt_content' ? 'record' : 'page')), 1))) . '</a>';
			}
			// Edit page properties
			if ($this->CALC_PERMS & 2) {
				$params = '&edit[pages][' . $this->id . ']=edit';
				$buttons['edit_page'] = '<a href="#" onclick="' . htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick($params, $GLOBALS['BACK_PATH'])) . '" title="' . $GLOBALS['LANG']->getLL('editPageProperties', TRUE) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-page-open') . '</a>';
			}
			// Add CSH (Context Sensitive Help) icon to tool bar
			if ($function == 'quickEdit') {
				$buttons['csh'] = \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem($this->descrTable, 'quickEdit', $GLOBALS['BACK_PATH'], '', TRUE, 'margin-top: 0px; margin-bottom: 0px;');
			} else {
				$buttons['csh'] = \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem($this->descrTable, 'columns_' . $this->MOD_SETTINGS['function'], $GLOBALS['BACK_PATH'], '', TRUE, 'margin-top: 0px; margin-bottom: 0px;');
			}
			if ($function == 'quickEdit') {
				// Save record
				$buttons['savedok'] = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-save', array('html' => '<input type="image" name="_savedok" class="c-inputButton" src="clear.gif" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveDoc', TRUE) . '" />'));
				// Save and close
				$buttons['save_close'] = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-save-close', array('html' => '<input type="image" class="c-inputButton" name="_saveandclosedok" src="clear.gif" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveCloseDoc', 1) . '" />'));
				// Save record and show page
				$buttons['savedokshow'] = '<a href="#" onclick="' . htmlspecialchars('document.editform.redirect.value+=\'&popView=1\'; TBE_EDITOR.checkAndDoSubmit(1); return false;') . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveDocShow', TRUE) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-save-view') . '</a>';
				// Close record
				$buttons['closedok'] = '<a href="#" onclick="' . htmlspecialchars(('jumpToUrl(unescape(\'' . rawurlencode($this->closeUrl) . '\')); return false;')) . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:rm.closeDoc', TRUE) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-close') . '</a>';
				// Delete record
				if ($this->deleteButton) {
					$buttons['deletedok'] = '<a href="#" onclick="' . htmlspecialchars(('return deleteRecord(\'' . $this->eRParts[0] . '\',\'' . $this->eRParts[1] . '\',\'' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('SCRIPT_NAME') . '?id=' . $this->id . '\');')) . '" title="' . $GLOBALS['LANG']->getLL('deleteItem', TRUE) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-edit-delete') . '</a>';
				}
				if ($this->undoButton) {
					// Undo button
					$buttons['undo'] = '<a href="#"
						onclick="' . htmlspecialchars(('window.location.href=\'' . $GLOBALS['BACK_PATH'] . 'show_rechis.php?element=' . rawurlencode(($this->eRParts[0] . ':' . $this->eRParts[1])) . '&revert=ALL_FIELDS&sumUp=-1&returnUrl=' . rawurlencode($this->R_URI) . '\'; return false;')) . '"
						title="' . htmlspecialchars(sprintf($GLOBALS['LANG']->getLL('undoLastChange'), \TYPO3\CMS\Backend\Utility\BackendUtility::calcAge(($GLOBALS['EXEC_TIME'] - $this->undoButtonR['tstamp']), $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.minutesHoursDaysYears')))) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-edit-undo') . '</a>';
					// History button
					$buttons['history_record'] = '<a href="#" onclick="' . htmlspecialchars(('jumpToUrl(\'' . $GLOBALS['BACK_PATH'] . 'show_rechis.php?element=' . rawurlencode(($this->eRParts[0] . ':' . $this->eRParts[1])) . '&returnUrl=' . rawurlencode($this->R_URI) . '#latest\');return false;')) . '" title="' . $GLOBALS['LANG']->getLL('recordHistory', TRUE) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-history-open') . '</a>';
				}
			}
		}
		return $buttons;
	}

	/*******************************
	 *
	 * Other functions
	 *
	 ******************************/
	/**
	 * Returns the number of hidden elements (including those hidden by start/end times)
	 * on the current page (for the current sys_language)
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function getNumberOfHiddenElements() {
		return $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('uid', 'tt_content', 'pid=' . intval($this->id) . ' AND sys_language_uid=' . intval($this->current_sys_language) . \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('tt_content', 1) . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tt_content') . \TYPO3\CMS\Backend\Utility\BackendUtility::versioningPlaceholderClause('tt_content'));
	}

	/**
	 * Returns URL to the current script.
	 * In particular the "popView" and "new_unique_uid" Get vars are unset.
	 *
	 * @param array $params Parameters array, merged with global GET vars.
	 * @return string URL
	 * @todo Define visibility
	 */
	public function local_linkThisScript($params) {
		$params['popView'] = '';
		$params['new_unique_uid'] = '';
		return \TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript($params);
	}

	/**
	 * Returns a SQL query for selecting sys_language records.
	 *
	 * @param integer $id Page id: If zero, the query will select all sys_language records from root level which are NOT hidden. If set to another value, the query will select all sys_language records that has a pages_language_overlay record on that page (and is not hidden, unless you are admin user)
	 * @return string Return query string.
	 * @todo Define visibility
	 */
	public function exec_languageQuery($id) {
		if ($id) {
			$exQ = \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('pages_language_overlay') .
				($GLOBALS['BE_USER']->isAdmin() ? '' : ' AND sys_language.hidden=0');
			return $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'sys_language.*',
				'pages_language_overlay,sys_language',
				'pages_language_overlay.sys_language_uid=sys_language.uid AND pages_language_overlay.pid=' . intval($id) . $exQ .
					\TYPO3\CMS\Backend\Utility\BackendUtility::versioningPlaceholderClause('pages_language_overlay'),
				'pages_language_overlay.sys_language_uid,sys_language.uid,sys_language.pid,sys_language.tstamp,sys_language.hidden,sys_language.title,sys_language.static_lang_isocode,sys_language.flag',
				'sys_language.title'
			);
		} else {
			return $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'sys_language.*',
				'sys_language',
				'sys_language.hidden=0',
				'',
				'sys_language.title'
			);
		}
	}

}


?>
