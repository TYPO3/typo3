<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2010 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Module: Web>Page
 *
 * This module lets you view a page in a more Content Management like style than the ordinary record-list
 *
 * $Id$
 * Revised for TYPO3 3.6 November/2003 by Kasper Skaarhoj
 * XHTML compliant
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  106: class ext_posMap extends t3lib_positionMap
 *  117:     function wrapRecordTitle($str,$row)
 *  130:     function wrapColumnHeader($str,$vv)
 *  144:     function onClickInsertRecord($row,$vv,$moveUid,$pid)
 *  160:     function wrapRecordHeader($str,$row)
 *
 *
 *  181: class SC_db_layout
 *  230:     function init()
 *  283:     function menuConfig()
 *  372:     function clearCache()
 *  387:     function main()
 *  489:     function renderQuickEdit()
 *  886:     function renderListContent()
 * 1165:     function printContent()
 *
 *              SECTION: Other functions
 * 1192:     function getNumberOfHiddenElements()
 * 1205:     function local_linkThisScript($params)
 * 1217:     function exec_languageQuery($id)
 *
 * TOTAL FUNCTIONS: 14
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


unset($MCONF);
require('conf.php');
require($BACK_PATH.'init.php');
require($BACK_PATH.'template.php');
$LANG->includeLLFile('EXT:cms/layout/locallang.xml');
require_once(PATH_typo3.'class.db_list.inc');
require_once('class.tx_cms_layout.php');
$BE_USER->modAccess($MCONF,1);

// Will open up records locked by current user. It's assumed that the locking should end if this script is hit.
t3lib_BEfunc::lockRecords();

// Exits if 'cms' extension is not loaded:
t3lib_extMgm::isLoaded('cms',1);











/**
 * Local extension of position map class
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class ext_posMap extends t3lib_positionMap {
	var $dontPrintPageInsertIcons = 1;
	var $l_insertNewRecordHere='newContentElement';

	/**
	 * Wrapping the title of the record.
	 *
	 * @param	string		The title value.
	 * @param	array		The record row.
	 * @return	string		Wrapped title string.
	 */
	function wrapRecordTitle($str,$row)	{
		$aOnClick = 'jumpToUrl(\''.$GLOBALS['SOBE']->local_linkThisScript(array('edit_record'=>'tt_content:'.$row['uid'])).'\');return false;';
		return '<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.$str.'</a>';
	}

	/**
	 * Wrapping the column header
	 *
	 * @param	string		Header value
	 * @param	string		Column info.
	 * @return	string
	 * @see printRecordMap()
	 */
	function wrapColumnHeader($str,$vv)	{
		$aOnClick = 'jumpToUrl(\''.$GLOBALS['SOBE']->local_linkThisScript(array('edit_record'=>'_EDIT_COL:'.$vv)).'\');return false;';
		return '<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.$str.'</a>';
	}

	/**
	 * Create on-click event value.
	 *
	 * @param	array		The record.
	 * @param	string		Column position value.
	 * @param	integer		Move uid
	 * @param	integer		PID value.
	 * @return	string
	 */
	function onClickInsertRecord($row,$vv,$moveUid,$pid) {
		if (is_array($row))	{
			$location=$GLOBALS['SOBE']->local_linkThisScript(array('edit_record'=>'tt_content:new/-'.$row['uid'].'/'.$row['colPos']));
		} else {
			$location=$GLOBALS['SOBE']->local_linkThisScript(array('edit_record'=>'tt_content:new/'.$pid.'/'.$vv));
		}
		return 'jumpToUrl(\''.$location.'\');return false;';
	}

	/**
	 * Wrapping the record header  (from getRecordHeader())
	 *
	 * @param	string		HTML content
	 * @param	array		Record array.
	 * @return	string		HTML content
	 */
	function wrapRecordHeader($str,$row)	{
		if ($row['uid']==$this->moveUid)	{
			return '<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/content_client.gif','width="7" height="10"').' alt="" />'.$str;
		} else return $str;
	}
}








/**
 * Script Class for Web > Layout module
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_db_layout {

		// Internal, GPvars:
	var $id;					// Page Id for which to make the listing
	var $pointer;				// Pointer - for browsing list of records.
	var $imagemode;				// Thumbnails or not

	var $search_field;			// Search-fields
	var $search_levels;			// Search-levels
	var $showLimit;				// Show-limit
	var $returnUrl;				// Return URL

	var $clear_cache;			// Clear-cache flag - if set, clears page cache for current id.
	var $popView;				// PopView id - for opening a window with the page
	var $edit_record;			// QuickEdit: Variable, that tells quick edit what to show/edit etc. Format is [tablename]:[uid] with some exceptional values for both parameters (with special meanings).
	var $new_unique_uid;		// QuickEdit: If set, this variable tells quick edit that the last edited record had this value as UID and we should look up the new, real uid value in sys_log.

		// Internal, static:
	var $perms_clause;			// Page select perms clause
	var $modTSconfig;			// Module TSconfig
	var $pageinfo;				// Current ids page record

	/**
	 * Document template object
	 *
	 * @var mediumDoc
	 */
	var $doc;
	var $backPath;				// Back path of the module

	var $descrTable;			// "Pseudo" Description -table name
	var $colPosList;			// List of column-integers to edit. Is set from TSconfig, default is "1,0,2,3"
	var $EDIT_CONTENT;			// Flag: If content can be edited or not.
	var $CALC_PERMS;			// Users permissions integer for this page.
	var $current_sys_language;	// Currently selected language for editing content elements

	var $MCONF=array();			// Module configuration
	var $MOD_MENU=array();		// Menu configuration
	var $MOD_SETTINGS=array();	// Module settings (session variable)
	var $include_once=array();	// Array, where files to include is accumulated in the init() function
	var $externalTables = array();	// Array of tables to be listed by the Web > Page module in addition to the default tables

		// Internal, dynamic:
	var $content;				// Module output accumulation
	var $topFuncMenu;			// Function menu temporary storage
	var $editIcon;				// Temporary storage for page edit icon





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
		$this->backPath = $GLOBALS['BACK_PATH'];

			// GPvars:
		$this->id = intval(t3lib_div::_GP('id'));
		$this->pointer = t3lib_div::_GP('pointer');
		$this->imagemode = t3lib_div::_GP('imagemode');

		$this->clear_cache = t3lib_div::_GP('clear_cache');
		$this->popView = t3lib_div::_GP('popView');
		$this->edit_record = t3lib_div::_GP('edit_record');
		$this->new_unique_uid = t3lib_div::_GP('new_unique_uid');
		$this->search_field = t3lib_div::_GP('search_field');
		$this->search_levels = t3lib_div::_GP('search_levels');
		$this->showLimit = t3lib_div::_GP('showLimit');
		$this->returnUrl = t3lib_div::sanitizeLocalUrl(t3lib_div::_GP('returnUrl'));
		$this->externalTables = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables'];

			// Load page info array:
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);

			// Initialize menu
		$this->menuConfig();

			// Setting sys language from session var:
 		$this->current_sys_language=intval($this->MOD_SETTINGS['language']);

			// Include scripts: QuickEdit
		if ($this->MOD_SETTINGS['function']==0)	{
			$this->include_once[]=PATH_t3lib.'class.t3lib_tceforms.php';
			$this->include_once[]=PATH_t3lib.'class.t3lib_clipboard.php';
			$this->include_once[]=PATH_t3lib.'class.t3lib_loaddbgroup.php';
			$this->include_once[]=PATH_t3lib.'class.t3lib_transferdata.php';
		}

			// Include scripts: Clear-cache cmd.
		if ($this->clear_cache)	{
			$this->include_once[]=PATH_t3lib.'class.t3lib_tcemain.php';
		}

			// CSH / Descriptions:
		$this->descrTable = '_MOD_'.$this->MCONF['name'];
	}

	/**
	 * Initialize menu array
	 *
	 * @return	void
	 */
	function menuConfig()	{
		global $BE_USER,$LANG,$TYPO3_CONF_VARS;

			// MENU-ITEMS:
		$this->MOD_MENU = array(
			'tt_board' => array(
				0 => $LANG->getLL('m_tt_board_0'),
				'expand' => $LANG->getLL('m_tt_board_expand')
			),
			'tt_address' => array(
				0 => $LANG->getLL('m_tt_address_0'),
				1 => $LANG->getLL('m_tt_address_1'),
				2 => $LANG->getLL('m_tt_address_2')
			),
			'tt_links' => array(
				0 => $LANG->getLL('m_default'),
				1 => $LANG->getLL('m_tt_links_1'),
				2 => $LANG->getLL('m_tt_links_2')
			),
			'tt_calender' => array (
				0 => $LANG->getLL('m_default'),
				'date' => $LANG->getLL('m_tt_calender_date'),
				'date_ext' => $LANG->getLL('m_tt_calender_date_ext'),
				'todo' => $LANG->getLL('m_tt_calender_todo'),
				'todo_ext' => $LANG->getLL('m_tt_calender_todo_ext')
			),
			'tt_products' => array (
				0 => $LANG->getLL('m_default'),
				'ext' => $LANG->getLL('m_tt_products_ext')
			),
			'tt_content_showHidden' => '',
			'showPalettes' => '',
			'showDescriptions' => '',
			'disableRTE' => '',
			'function' => array(
				0 => $LANG->getLL('m_function_0'),
				1 => $LANG->getLL('m_function_1'),
				2 => $LANG->getLL('m_function_2'),
				3 => $LANG->getLL('pageInformation')
			),
			'language' => array(
				0 => $LANG->getLL('m_default')
			)
		);

		// example settings:
		// 	$TYPO3_CONF_VARS['EXTCONF']['cms']['db_layout']['addTables']['tx_myext'] =
		//		array ('default' => array(
		//				'MENU' => 'LLL:EXT:tx_myext/locallang_db.xml:menuDefault',
		//				'fList' =>  'title,description,image',
		//				'icon' => TRUE),
		if (is_array($this->externalTables)) {
			foreach ($this->externalTables as $table => $tableSettings) {
				// delete the default settings from above
				if (is_array($this->MOD_MENU[$table])) {
					unset ($this->MOD_MENU[$table]);
				}
				if (is_array($tableSettings) && count($tableSettings) > 1) {
					foreach ($tableSettings as $key => $settings) {
						$this->MOD_MENU[$table][$key] = $LANG->sL($settings['MENU']);
					}
				}
			}
		}

			 // First, select all pages_language_overlay records on the current page. Each represents a possibility for a language on the page. Add these to language selector.
		$res = $this->exec_languageQuery($this->id);
		while($lrow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			if ($GLOBALS['BE_USER']->checkLanguageAccess($lrow['uid']))	{
				$this->MOD_MENU['language'][$lrow['uid']]=($lrow['hidden']?'('.$lrow['title'].')':$lrow['title']);
			}
		}

			// Find if there are ANY languages at all (and if not, remove the language option from function menu).
		$count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('uid', 'sys_language', ($BE_USER->isAdmin() ? '' : 'hidden=0'));
		if (!$count) {
			unset($this->MOD_MENU['function']['2']);
		}

			// page/be_user TSconfig settings and blinding of menu-items
		$this->modSharedTSconfig = t3lib_BEfunc::getModTSconfig($this->id, 'mod.SHARED');
		$this->modTSconfig = t3lib_BEfunc::getModTSconfig($this->id,'mod.'.$this->MCONF['name']);
		if ($this->modTSconfig['properties']['QEisDefault'])	ksort($this->MOD_MENU['function']);
		$this->MOD_MENU['function'] = t3lib_BEfunc::unsetMenuItems($this->modTSconfig['properties'],$this->MOD_MENU['function'],'menu.function');

			// Remove QuickEdit as option if page type is not...
		if (!t3lib_div::inList($TYPO3_CONF_VARS['FE']['content_doktypes'].',6',$this->pageinfo['doktype']))	{
			unset($this->MOD_MENU['function'][0]);
		}

			// Setting alternative default label:
		if (($this->modSharedTSconfig['properties']['defaultLanguageLabel'] || $this->modTSconfig['properties']['defaultLanguageLabel']) && isset($this->MOD_MENU['language'][0]))	{
			$this->MOD_MENU['language'][0] = $this->modTSconfig['properties']['defaultLanguageLabel'] ? $this->modSharedTSconfig['properties']['defaultLanguageLabel'] : $this->modSharedTSconfig['properties']['defaultLanguageLabel'];
		}

			// Clean up settings
		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::_GP('SET'), $this->MCONF['name']);

			// For all elements to be shown in draft workspaces:
		if ($GLOBALS['BE_USER']->workspace!=0)	{
			$this->MOD_SETTINGS['tt_content_showHidden'] = 1;
		}
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
	 * Main function.
	 * Creates some general objects and calls other functions for the main rendering of module content.
	 *
	 * @return	void
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH;

		// Access check...
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$access = is_array($this->pageinfo) ? 1 : 0;
		if ($this->id && $access)	{

				// Initialize permission settings:
			$this->CALC_PERMS = $BE_USER->calcPerms($this->pageinfo);
			$this->EDIT_CONTENT = ($this->CALC_PERMS&16) ? 1 : 0;

				// Start document template object:
			$this->doc = t3lib_div::makeInstance('template');
			$this->doc->backPath = $BACK_PATH;
			$this->doc->setModuleTemplate('templates/db_layout.html');

				// JavaScript:
			$this->doc->JScode = '<script type="text/javascript" ' .
				'src="' . t3lib_div::createVersionNumberedFilename($BACK_PATH . '../t3lib/jsfunc.updateform.js') . '">' .
				'</script>';
			$this->doc->JScode.= $this->doc->wrapScriptTags('
				if (top.fsMod) top.fsMod.recentIds["web"] = '.intval($this->id).';
				if (top.fsMod) top.fsMod.navFrameHighlightedID["web"] = "pages'.intval($this->id).'_"+top.fsMod.currentBank; '.intval($this->id).';
				function jumpToUrl(URL,formEl)	{	//
					if (document.editform && TBE_EDITOR.isFormChanged)	{	// Check if the function exists... (works in all browsers?)
						if (!TBE_EDITOR.isFormChanged())	{	//
							window.location.href = URL;
						} else if (formEl) {
							if (formEl.type=="checkbox") formEl.checked = formEl.checked ? 0 : 1;
						}
					} else window.location.href = URL;
				}
			'.($this->popView ? t3lib_BEfunc::viewOnClick($this->id,$BACK_PATH,t3lib_BEfunc::BEgetRootLine($this->id)) : '').'

				function deleteRecord(table,id,url)	{	//
					if (confirm('.$LANG->JScharCode($LANG->getLL('deleteWarning')).'))	{
						window.location.href = "'.$BACK_PATH.'tce_db.php?cmd["+table+"]["+id+"][delete]=1&redirect="+escape(url)+"&vC='.$BE_USER->veriCode().'&prErr=1&uPT=1";
					}
					return false;
				}
			');
			$this->doc->JScode.= $this->doc->wrapScriptTags('
				var DTM_array = new Array();
				var DTM_origClass = new String();

					// if tabs are used in a popup window the array might not exists
				if(!top.DTM_currentTabs) {
					top.DTM_currentTabs = new Array();
				}

				function DTM_activate(idBase,index,doToogle)	{	//
						// Hiding all:
					if (DTM_array[idBase])	{
						for(cnt = 0; cnt < DTM_array[idBase].length ; cnt++)	{
							if (DTM_array[idBase][cnt] != idBase+"-"+index)	{
								document.getElementById(DTM_array[idBase][cnt]+"-DIV").style.display = "none";
								document.getElementById(DTM_array[idBase][cnt]+"-MENU").attributes.getNamedItem("class").nodeValue = "tab";
							}
						}
					}

						// Showing one:
					if (document.getElementById(idBase+"-"+index+"-DIV"))	{
						if (doToogle && document.getElementById(idBase+"-"+index+"-DIV").style.display == "block")	{
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
				function DTM_toggle(idBase,index,isInit)	{	//
						// Showing one:
					if (document.getElementById(idBase+"-"+index+"-DIV"))	{
						if (document.getElementById(idBase+"-"+index+"-DIV").style.display == "block")	{
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
			$this->doc->form='<form action="'.htmlspecialchars('db_layout.php?id='.$this->id.'&imagemode='.$this->imagemode).'" method="post">';

				// Creating the top function menu:
			$this->topFuncMenu = t3lib_BEfunc::getFuncMenu($this->id,'SET[function]',$this->MOD_SETTINGS['function'],$this->MOD_MENU['function'],'db_layout.php','');
			$this->languageMenu = (count($this->MOD_MENU['language'])>1 ? $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_general.xml:LGL.language',1) . t3lib_BEfunc::getFuncMenu($this->id,'SET[language]',$this->current_sys_language,$this->MOD_MENU['language'],'db_layout.php','') : '');

				// Find columns
			$modTSconfig_SHARED = t3lib_BEfunc::getModTSconfig($this->id,'mod.SHARED');		// SHARED page-TSconfig settings.
			$this->colPosList = strcmp(trim($this->modTSconfig['properties']['tt_content.']['colPos_list']),'') ? trim($this->modTSconfig['properties']['tt_content.']['colPos_list']) : $modTSconfig_SHARED['properties']['colPos_list'];
			$this->colPosList = strcmp($this->colPosList,'')?$this->colPosList:'1,0,2,3';
			$this->colPosList = implode(',',array_unique(t3lib_div::intExplode(',',$this->colPosList)));		// Removing duplicates, if any


				// Render the primary module content:
			if ($this->MOD_SETTINGS['function']==0)	{
				$body = $this->renderQuickEdit();	// QuickEdit
			} else {
				$body = $this->renderListContent();	// All other listings
			}


			if ($this->pageinfo['content_from_pid']) {
				$contentPage = t3lib_BEfunc::getRecord('pages', intval($this->pageinfo['content_from_pid']));
				$title = t3lib_BEfunc::getRecordTitle('pages', $contentPage);
				$linkToPid = $this->local_linkThisScript(array('id' => $this->pageinfo['content_from_pid']));
				$link = '<a href="' . $linkToPid . '">' . htmlspecialchars($title) . ' (PID ' . intval($this->pageinfo['content_from_pid']) . ')</a>';
				$flashMessage = t3lib_div::makeInstance(
					't3lib_FlashMessage',
					'',
					sprintf($GLOBALS['LANG']->getLL('content_from_pid_title'), $link),
					t3lib_FlashMessage::INFO
				);
				$body = $flashMessage->render() . $body;
			}

				// Setting up the buttons and markers for docheader
			$docHeaderButtons = $this->getButtons($this->MOD_SETTINGS['function']==0 ? 'quickEdit' : '');
			$markers = array(
				'CSH' => $docHeaderButtons['csh'],
				'TOP_FUNCTION_MENU' => $this->editSelect . $this->topFuncMenu,
				'LANGSELECTOR' => $this->languageMenu,
				'CONTENT' => $body
			);

				// Build the <body> for the module
			$this->content = $this->doc->startPage($LANG->getLL('title'));
			$this->content.= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
			$this->content.= $this->doc->endPage();
			$this->content = $this->doc->insertStylesAndJS($this->content);

		} else {

				// If no access or id value, create empty document:
			$this->doc = t3lib_div::makeInstance('template');
			$this->doc->backPath = $BACK_PATH;
			$this->doc->setModuleTemplate('templates/db_layout.html');

			$this->doc->JScode = $this->doc->wrapScriptTags('
				if (top.fsMod) top.fsMod.recentIds["web"] = '.intval($this->id).';
			');

			$flashMessage = t3lib_div::makeInstance(
				't3lib_FlashMessage',
				$LANG->getLL('clickAPage_content'),
				$LANG->getLL('clickAPage_header'),
				t3lib_FlashMessage::INFO
			);
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
				'record_list' => '',
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
				'CSH' => t3lib_BEfunc::cshItem($this->descrTable, '', $BACK_PATH, '', TRUE),
				'TOP_FUNCTION_MENU' => '',
				'LANGSELECTOR' => '',
				'CONTENT' => $body
			);

			$this->content=$this->doc->startPage($LANG->getLL('title'));
			$this->content.= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
			$this->content.=$this->doc->endPage();
			$this->content = $this->doc->insertStylesAndJS($this->content);
		}
	}

	/**
	 * Rendering the quick-edit view.
	 *
	 * @return	void
	 */
	function renderQuickEdit()	{
		global $LANG,$BE_USER,$BACK_PATH;
			// Alternative template
		$this->doc->setModuleTemplate('templates/db_layout_quickedit.html');

			// Alternative form tag; Quick Edit submits its content to tce_db.php.
		$this->doc->form='<form action="'.htmlspecialchars($BACK_PATH.'tce_db.php?&prErr=1&uPT=1').'" method="post" enctype="'.$GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'].'" name="editform" onsubmit="return TBE_EDITOR.checkSubmit(1);">';

			// Setting up the context sensitive menu:
		$this->doc->getContextMenuCode();

			// Set the edit_record value for internal use in this function:
		$edit_record = $this->edit_record;

			// If a command to edit all records in a column is issue, then select all those elements, and redirect to alt_doc.php:
		if (substr($edit_record,0,9)=='_EDIT_COL')	{
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'*',
						'tt_content',
						'pid='.intval($this->id).' AND colPos='.intval(substr($edit_record,10)).' AND sys_language_uid='.intval($this->current_sys_language).
								($this->MOD_SETTINGS['tt_content_showHidden'] ? '' : t3lib_BEfunc::BEenableFields('tt_content')).
								t3lib_BEfunc::deleteClause('tt_content').
								t3lib_BEfunc::versioningPlaceholderClause('tt_content'),
						'',
						'sorting'
					);
			$idListA = array();
			while($cRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				$idListA[] = $cRow['uid'];
			}

			$url = $BACK_PATH.'alt_doc.php?edit[tt_content]['.implode(',',$idListA).']=edit&returnUrl='.rawurlencode($this->local_linkThisScript(array('edit_record'=>'')));
			t3lib_utility_Http::redirect($url);
		}

			// If the former record edited was the creation of a NEW record, this will look up the created records uid:
		if ($this->new_unique_uid)	{
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_log', 'userid='.intval($BE_USER->user['uid']).' AND NEWid='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->new_unique_uid, 'sys_log'));
			$sys_log_row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			if (is_array($sys_log_row))	{
				$edit_record=$sys_log_row['tablename'].':'.$sys_log_row['recuid'];
			}
		}


			// Creating the selector box, allowing the user to select which element to edit:
		$opt=array();
		$is_selected=0;
		$languageOverlayRecord='';
		if ($this->current_sys_language)	{
			list($languageOverlayRecord) = t3lib_BEfunc::getRecordsByField('pages_language_overlay','pid',$this->id,'AND sys_language_uid='.intval($this->current_sys_language));
		}
		if (is_array($languageOverlayRecord))	{
			$inValue = 'pages_language_overlay:'.$languageOverlayRecord['uid'];
			$is_selected+=intval($edit_record==$inValue);
			$opt[]='<option value="'.$inValue.'"'.($edit_record==$inValue?' selected="selected"':'').'>[ '.$LANG->getLL('editLanguageHeader',1).' ]</option>';
		} else {
			$inValue = 'pages:'.$this->id;
			$is_selected+=intval($edit_record==$inValue);
			$opt[]='<option value="'.$inValue.'"'.($edit_record==$inValue?' selected="selected"':'').'>[ '.$LANG->getLL('editPageProperties',1).' ]</option>';
		}

			// Selecting all content elements from this language and allowed colPos:
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'*',
					'tt_content',
					'pid='.intval($this->id).' AND sys_language_uid='.intval($this->current_sys_language).' AND colPos IN ('.$this->colPosList.')'.
							($this->MOD_SETTINGS['tt_content_showHidden'] ? '' : t3lib_BEfunc::BEenableFields('tt_content')).
							t3lib_Befunc::deleteClause('tt_content').
							t3lib_BEfunc::versioningPlaceholderClause('tt_content'),
					'',
					'colPos,sorting'
				);
		$colPos='';
		$first=1;
		$prev=$this->id;	// Page is the pid if no record to put this after.
		while($cRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			t3lib_BEfunc::workspaceOL('tt_content', $cRow);

			if (is_array($cRow)) 	{
				if ($first)	{
					if (!$edit_record)	{
						$edit_record='tt_content:'.$cRow['uid'];
					}
					$first = 0;
				}
				if (strcmp($cRow['colPos'],$colPos))	{
					$colPos=$cRow['colPos'];
					$opt[]='<option value=""></option>';
					$opt[]='<option value="_EDIT_COL:'.$colPos.'">__'.$LANG->sL(t3lib_BEfunc::getLabelFromItemlist('tt_content','colPos',$colPos),1).':__</option>';
				}
				$inValue = 'tt_content:'.$cRow['uid'];
				$is_selected+=intval($edit_record==$inValue);
				$opt[]='<option value="'.$inValue.'"'.($edit_record==$inValue?' selected="selected"':'').'>'.htmlspecialchars(t3lib_div::fixed_lgd_cs($cRow['header']?$cRow['header']:'['.$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.no_title').'] '.strip_tags($cRow['bodytext']),$BE_USER->uc['titleLen'])).'</option>';
				$prev=-$cRow['uid'];
			}
		}

			// If edit_record is not set (meaning, no content elements was found for this language) we simply set it to create a new element:
		if (!$edit_record)	{
			$edit_record='tt_content:new/'.$prev.'/'.$colPos;

			$inValue = 'tt_content:new/'.$prev.'/'.$colPos;
			$is_selected+=intval($edit_record==$inValue);
			$opt[]='<option value="'.$inValue.'"'.($edit_record==$inValue?' selected="selected"':'').'>[ '.$LANG->getLL('newLabel',1).' ]</option>';
		}

			// If none is yet selected...
		if (!$is_selected)	{
			$opt[]='<option value=""></option>';
			$opt[]='<option value="'.$edit_record.'"  selected="selected">[ '.$LANG->getLL('newLabel',1).' ]</option>';
		}


			// Splitting the edit-record cmd value into table/uid:
		$this->eRParts = explode(':',$edit_record);



			// Delete-button flag?
		$this->deleteButton = (t3lib_div::testInt($this->eRParts[1]) && $edit_record && (($this->eRParts[0]!='pages'&&$this->EDIT_CONTENT) || ($this->eRParts[0]=='pages'&&($this->CALC_PERMS&4))));

			// If undo-button should be rendered (depends on available items in sys_history)
		$this->undoButton=0;
		$undoRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tstamp', 'sys_history', 'tablename='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->eRParts[0], 'sys_history').' AND recuid='.intval($this->eRParts[1]), '', 'tstamp DESC', '1');
		if ($this->undoButtonR = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($undoRes))	{
			$this->undoButton=1;
		}

			// Setting up the Return URL for coming back to THIS script (if links take the user to another script)
		$R_URL_parts = parse_url(t3lib_div::getIndpEnv('REQUEST_URI'));
		$R_URL_getvars = t3lib_div::_GET();

		unset($R_URL_getvars['popView']);
		unset($R_URL_getvars['new_unique_uid']);
		$R_URL_getvars['edit_record']=$edit_record;
		$this->R_URI = $R_URL_parts['path'].'?'.t3lib_div::implodeArrayForUrl('',$R_URL_getvars);

			// Setting close url/return url for exiting this script:
		$this->closeUrl = $this->local_linkThisScript(array('SET'=>array('function'=>1)));	// Goes to 'Columns' view if close is pressed (default)

		if ($BE_USER->uc['condensedMode'])	{
			$this->closeUrl = $BACK_PATH.'alt_db_navframe.php';
		}
		if ($this->returnUrl)	{
			$this->closeUrl = $this->returnUrl;
		}
			// Return-url for JavaScript:
		$retUrlStr = $this->returnUrl?"+'&returnUrl='+'".rawurlencode($this->returnUrl)."'":'';

			// Drawing the edit record selectbox
		$this->editSelect = '<select name="edit_record" onchange="' . htmlspecialchars('jumpToUrl(\'db_layout.php?id=' . $this->id . '&edit_record=\'+escape(this.options[this.selectedIndex].value)' . $retUrlStr . ',this);') . '">' . implode('', $opt) . '</select>';

			// Creating editing form:
		if ($BE_USER->check('tables_modify',$this->eRParts[0]) && $edit_record && (($this->eRParts[0]!='pages'&&$this->EDIT_CONTENT) || ($this->eRParts[0]=='pages'&&($this->CALC_PERMS&1))))	{

				// Splitting uid parts for special features, if new:
			list($uidVal,$ex_pid,$ex_colPos) = explode('/',$this->eRParts[1]);

				// Convert $uidVal to workspace version if any:
			if ($uidVal!='new')	{
				if ($draftRecord = t3lib_BEfunc::getWorkspaceVersionOfRecord($GLOBALS['BE_USER']->workspace, $this->eRParts[0], $uidVal, 'uid'))	{
					$uidVal = $draftRecord['uid'];
				}
			}

				// Initializing transfer-data object:
			$trData = t3lib_div::makeInstance('t3lib_transferData');
			$trData->addRawData = TRUE;
			$trData->defVals[$this->eRParts[0]] = array (
				'colPos' => intval($ex_colPos),
				'sys_language_uid' => intval($this->current_sys_language)
			);
			$trData->disableRTE = $this->MOD_SETTINGS['disableRTE'];
			$trData->lockRecords=1;
			$trData->fetchRecord($this->eRParts[0],($uidVal=='new'?$this->id:$uidVal),$uidVal);	// 'new'

				// Getting/Making the record:
			reset($trData->regTableItems_data);
			$rec = current($trData->regTableItems_data);
			if ($uidVal=='new')	{
				$new_unique_uid = uniqid('NEW');
				$rec['uid'] = $new_unique_uid;
				$rec['pid'] = intval($ex_pid)?intval($ex_pid):$this->id;
				$recordAccess = TRUE;
			} else {
				$rec['uid'] = $uidVal;

					// Checking internals access:
				$recordAccess = $BE_USER->recordEditAccessInternals($this->eRParts[0],$uidVal);
			}

			if (!$recordAccess)	{
					// If no edit access, print error message:
				$content.=$this->doc->section($LANG->getLL('noAccess'),$LANG->getLL('noAccess_msg').'<br /><br />'.
							($BE_USER->errorMsg ? 'Reason: ' . $BE_USER->errorMsg . '<br /><br />' : ''), 0, 1);
			} elseif (is_array($rec))	{	// If the record is an array (which it will always be... :-)

					// Create instance of TCEforms, setting defaults:
				$tceforms = t3lib_div::makeInstance('t3lib_TCEforms');
				$tceforms->backPath = $BACK_PATH;
				$tceforms->initDefaultBEMode();
				$tceforms->fieldOrder = $this->modTSconfig['properties']['tt_content.']['fieldOrder'];
				$tceforms->palettesCollapsed = !$this->MOD_SETTINGS['showPalettes'];
				$tceforms->disableRTE = $this->MOD_SETTINGS['disableRTE'];
				$tceforms->enableClickMenu = TRUE;

					// Clipboard is initialized:
				$tceforms->clipObj = t3lib_div::makeInstance('t3lib_clipboard');		// Start clipboard
				$tceforms->clipObj->initializeClipboard();	// Initialize - reads the clipboard content from the user session


				if ($BE_USER->uc['edit_showFieldHelp']!='text' && $this->MOD_SETTINGS['showDescriptions'])	$tceforms->edit_showFieldHelp='text';

					// Render form, wrap it:
				$panel='';
				$panel.=$tceforms->getMainFields($this->eRParts[0],$rec);
				$panel=$tceforms->wrapTotal($panel,$rec,$this->eRParts[0]);

					// Add hidden fields:
				$theCode=$panel;
				if ($uidVal=='new')	{
					$theCode.='<input type="hidden" name="data['.$this->eRParts[0].']['.$rec['uid'].'][pid]" value="'.$rec['pid'].'" />';
				}
				$theCode.='
					<input type="hidden" name="_serialNumber" value="'.md5(microtime()).'" />
					<input type="hidden" name="_disableRTE" value="'.$tceforms->disableRTE.'" />
					<input type="hidden" name="edit_record" value="'.$edit_record.'" />
					<input type="hidden" name="redirect" value="'.htmlspecialchars($uidVal=='new' ? t3lib_extMgm::extRelPath('cms').'layout/db_layout.php?id='.$this->id.'&new_unique_uid='.$new_unique_uid.'&returnUrl='.rawurlencode($this->returnUrl) : $this->R_URI ).'" />
					';

					// Add JavaScript as needed around the form:
				$theCode=$tceforms->printNeededJSFunctions_top().$theCode.$tceforms->printNeededJSFunctions();

					// Add warning sign if record was "locked":
				if ($lockInfo = t3lib_BEfunc::isRecordLocked($this->eRParts[0], $rec['uid'])) {
					$lockedMessage = t3lib_div::makeInstance(
						't3lib_FlashMessage',
						htmlspecialchars($lockInfo['msg']),
						'',
						t3lib_FlashMessage::WARNING
					);
					t3lib_FlashMessageQueue::addMessage($lockedMessage);
				}

					// Add whole form as a document section:
				$content .= $this->doc->section('', $theCode);
			}
		} else {
				// If no edit access, print error message:
			$content.=$this->doc->section($LANG->getLL('noAccess'),$LANG->getLL('noAccess_msg').'<br /><br />',0,1);
		}


			// Bottom controls (function menus):
		$q_count = $this->getNumberOfHiddenElements();
		$h_func_b= t3lib_BEfunc::getFuncCheck($this->id,'SET[tt_content_showHidden]',$this->MOD_SETTINGS['tt_content_showHidden'],'db_layout.php','','id="checkTt_content_showHidden"').
					'<label for="checkTt_content_showHidden">'.(!$q_count?$GLOBALS['TBE_TEMPLATE']->dfw($LANG->getLL('hiddenCE',1)):$LANG->getLL('hiddenCE',1).' ('.$q_count.')').'</label>';

		$h_func_b.= '<br />'.
					t3lib_BEfunc::getFuncCheck($this->id,'SET[showPalettes]',$this->MOD_SETTINGS['showPalettes'],'db_layout.php','','id="checkShowPalettes"').
					'<label for="checkShowPalettes">'.$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.showPalettes',1).'</label>';

		if (t3lib_extMgm::isLoaded('context_help') && $BE_USER->uc['edit_showFieldHelp']!='text') {
			$h_func_b.= '<br />'.
						t3lib_BEfunc::getFuncCheck($this->id,'SET[showDescriptions]',$this->MOD_SETTINGS['showDescriptions'],'db_layout.php','','id="checkShowDescriptions"').
						'<label for="checkShowDescriptions">'.$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.showDescriptions',1).'</label>';
		}

		if ($BE_USER->isRTE())	{
			$h_func_b.= '<br />'.
						t3lib_BEfunc::getFuncCheck($this->id,'SET[disableRTE]',$this->MOD_SETTINGS['disableRTE'],'db_layout.php','','id="checkDisableRTE"').
						'<label for="checkDisableRTE">'.$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.disableRTE',1).'</label>';
		}

			// Add the function menus to bottom:
		$content.=$this->doc->section('',$h_func_b,0,0);
		$content.=$this->doc->spacer(10);


			// Select element matrix:
		if ($this->eRParts[0]=='tt_content' && t3lib_div::testInt($this->eRParts[1]))	{
			$posMap = t3lib_div::makeInstance('ext_posMap');
			$posMap->backPath = $BACK_PATH;
			$posMap->cur_sys_language=$this->current_sys_language;

			$HTMLcode = '';

				// CSH:
			$HTMLcode.= t3lib_BEfunc::cshItem($this->descrTable, 'quickEdit_selElement', $BACK_PATH, '|<br />');

			$HTMLcode.=$posMap->printContentElementColumns($this->id,$this->eRParts[1],$this->colPosList,$this->MOD_SETTINGS['tt_content_showHidden'],$this->R_URI);

			$content.=$this->doc->spacer(20);
			$content.=$this->doc->section($LANG->getLL('CEonThisPage'),$HTMLcode,0,1);
			$content.=$this->doc->spacer(20);
		}

			// Finally, if comments were generated in TCEforms object, print these as a HTML comment:
		if (count($tceforms->commentMessages))	{
			$content.='
	<!-- TCEFORM messages
	'.htmlspecialchars(implode(LF,$tceforms->commentMessages)).'
	-->
	';
		}
		return $content;
	}

	/**
	 * Rendering all other listings than QuickEdit
	 *
	 * @return	void
	 */
	function renderListContent()	{
		global $LANG,$BACK_PATH,$TCA;

			// Initialize list object (see "class.db_layout.inc"):
		$dblist = t3lib_div::makeInstance('tx_cms_layout');
		$dblist->backPath = $BACK_PATH;
		$dblist->thumbs = $this->imagemode;
		$dblist->no_noWrap = 1;
		$dblist->descrTable = $this->descrTable;

		$this->pointer = t3lib_div::intInRange($this->pointer,0,100000);
		$dblist->script = 'db_layout.php';
		$dblist->showIcon = 0;
		$dblist->setLMargin=0;
		$dblist->doEdit = $this->EDIT_CONTENT;
		$dblist->ext_CALC_PERMS = $this->CALC_PERMS;

		$dblist->agePrefixes = $LANG->sL('LLL:EXT:lang/locallang_core.php:labels.minutesHoursDaysYears');
		$dblist->id = $this->id;
		$dblist->nextThree = t3lib_div::intInRange($this->modTSconfig['properties']['editFieldsAtATime'],0,10);
		$dblist->option_showBigButtons = ($this->modTSconfig['properties']['disableBigButtons'] === '0');
		$dblist->option_newWizard = $this->modTSconfig['properties']['disableNewContentElementWizard'] ? 0 : 1;
		$dblist->defLangBinding = $this->modTSconfig['properties']['defLangBinding'] ? 1 : 0;
		if (!$dblist->nextThree)	$dblist->nextThree = 1;

		$dblist->externalTables = $this->externalTables;

			// Create menu for selecting a table to jump to (this is, if more than just pages/tt_content elements are found on the page!)
		$h_menu = $dblist->getTableMenu($this->id);

			// Initialize other variables:
		$h_func='';
		$tableOutput=array();
		$tableJSOutput=array();
		$CMcounter = 0;

			// Traverse the list of table names which has records on this page (that array is populated by the $dblist object during the function getTableMenu()):
		foreach ($dblist->activeTables as $table => $value) {

				// Load full table definitions:
			t3lib_div::loadTCA($table);

			if (!isset($dblist->externalTables[$table]))	{
					// Creating special conditions for each table:
				switch($table)	{
					case 'tt_board':
						$h_func = t3lib_BEfunc::getFuncMenu($this->id,'SET[tt_board]',$this->MOD_SETTINGS['tt_board'],$this->MOD_MENU['tt_board'],'db_layout.php','');
					break;
					case 'tt_address':
						$h_func = t3lib_BEfunc::getFuncMenu($this->id,'SET[tt_address]',$this->MOD_SETTINGS['tt_address'],$this->MOD_MENU['tt_address'],'db_layout.php','');
					break;
					case 'tt_links':
						$h_func = t3lib_BEfunc::getFuncMenu($this->id,'SET[tt_links]',$this->MOD_SETTINGS['tt_links'],$this->MOD_MENU['tt_links'],'db_layout.php','');
					break;
					case 'tt_calender':
						$h_func = t3lib_BEfunc::getFuncMenu($this->id,'SET[tt_calender]',$this->MOD_SETTINGS['tt_calender'],$this->MOD_MENU['tt_calender'],'db_layout.php','');
					break;
					case 'tt_products':
						$h_func = t3lib_BEfunc::getFuncMenu($this->id,'SET[tt_products]',$this->MOD_SETTINGS['tt_products'],$this->MOD_MENU['tt_products'],'db_layout.php','');
					break;
					case 'tt_guest':
					case 'tt_news':
					case 'fe_users':
						// Nothing
					break;
					case 'tt_content':
						$q_count = $this->getNumberOfHiddenElements();
						$h_func_b= t3lib_BEfunc::getFuncCheck($this->id,'SET[tt_content_showHidden]',$this->MOD_SETTINGS['tt_content_showHidden'],'db_layout.php','','id="checkTt_content_showHidden"').'<label for="checkTt_content_showHidden">'.(!$q_count?$GLOBALS['TBE_TEMPLATE']->dfw($LANG->getLL('hiddenCE')):$LANG->getLL('hiddenCE').' ('.$q_count.')').'</label>';

						$dblist->tt_contentConfig['showCommands'] = 1;	// Boolean: Display up/down arrows and edit icons for tt_content records
						$dblist->tt_contentConfig['showInfo'] = 1;		// Boolean: Display info-marks or not
						$dblist->tt_contentConfig['single'] = 0; 		// Boolean: If set, the content of column(s) $this->tt_contentConfig['showSingleCol'] is shown in the total width of the page

							// Setting up the tt_content columns to show:
						if (is_array($TCA['tt_content']['columns']['colPos']['config']['items']))	{
							$colList = array();
							foreach($TCA['tt_content']['columns']['colPos']['config']['items'] as $temp)	{
								$colList[] = $temp[1];
							}
						} else {	// ... should be impossible that colPos has no array. But this is the fallback should it make any sense:
							$colList = array('1','0','2','3');
						}
						if (strcmp($this->colPosList,''))	{
							$colList = array_intersect(t3lib_div::intExplode(',',$this->colPosList),$colList);
						}

							// If only one column found, display the single-column view.
						if (count($colList)==1)	{
							$dblist->tt_contentConfig['single'] = 1;	// Boolean: If set, the content of column(s) $this->tt_contentConfig['showSingleCol'] is shown in the total width of the page
							$dblist->tt_contentConfig['showSingleCol'] = current($colList);	// The column(s) to show if single mode (under each other)
						}
						$dblist->tt_contentConfig['cols'] = implode(',',$colList);		// The order of the rows: Default is left(1), Normal(0), right(2), margin(3)
						$dblist->tt_contentConfig['showHidden'] = $this->MOD_SETTINGS['tt_content_showHidden'];
						$dblist->tt_contentConfig['sys_language_uid'] = intval($this->current_sys_language);

							// If the function menu is set to "Language":
						if ($this->MOD_SETTINGS['function']==2)	{
							$dblist->tt_contentConfig['single'] = 0;
							$dblist->tt_contentConfig['languageMode'] = 1;
							$dblist->tt_contentConfig['languageCols'] = $this->MOD_MENU['language'];
							$dblist->tt_contentConfig['languageColsPointer'] = $this->current_sys_language;
						}
					break;
				}
			} else {
				if (isset($this->MOD_SETTINGS) && isset($this->MOD_MENU)) {
					$h_func = t3lib_BEfunc::getFuncMenu($this->id, 'SET[' . $table . ']', $this->MOD_SETTINGS[$table], $this->MOD_MENU[$table], 'db_layout.php', '');
				} else {
				$h_func = '';
			}
 			}

				// Start the dblist object:
			$dblist->itemsLimitSingleTable = 1000;
			$dblist->start($this->id,$table,$this->pointer,$this->search_field,$this->search_levels,$this->showLimit);
			$dblist->counter = $CMcounter;
			$dblist->ext_function = $this->MOD_SETTINGS['function'];

				// Render versioning selector:
			$dblist->HTMLcode.= $this->doc->getVersionSelector($this->id);

				// Generate the list of elements here:
			$dblist->generateList();

				// Adding the list content to the tableOutput variable:
			$tableOutput[$table]=
							($h_func?$h_func.'<br /><img src="clear.gif" width="1" height="4" alt="" /><br />':'').
							$dblist->HTMLcode.
							($h_func_b?'<img src="clear.gif" width="1" height="10" alt="" /><br />'.$h_func_b:'');

				// ... and any accumulated JavaScript goes the same way!
			$tableJSOutput[$table] = $dblist->JScode;

				// Increase global counter:
			$CMcounter+= $dblist->counter;

				// Reset variables after operation:
			$dblist->HTMLcode='';
			$dblist->JScode='';
			$h_func = '';
			$h_func_b = '';
		}	// END: traverse tables


			// For Context Sensitive Menus:
		$this->doc->getContextMenuCode();

			// Now, create listing based on which element is selected in the function menu:

		if ($this->MOD_SETTINGS['function']==3) {

				// Making page info:
			$content.=$this->doc->spacer(10);
			$content.=$this->doc->section($LANG->getLL('pageInformation'),$dblist->getPageInfoBox($this->pageinfo,$this->CALC_PERMS&2),0,1);
		} else {

				// Add the content for each table we have rendered (traversing $tableOutput variable)
			foreach($tableOutput as $table => $output)	{
				$content.=$this->doc->section('<a name="'.$table.'"></a>'.$dblist->activeTables[$table],$output,TRUE,TRUE,0,TRUE);
				$content.=$this->doc->spacer(15);
				$content.=$this->doc->sectionEnd();
			}

				// Making search form:
			if (!$this->modTSconfig['properties']['disableSearchBox'] && count($tableOutput))	{
				$content .= $this->doc->section($LANG->sL('LLL:EXT:lang/locallang_core.php:labels.search'), $dblist->getSearchBox(0), 0, 1);
			}

				// Making display of Sys-notes (from extension "sys_note")
			$dblist->id=$this->id;
			$sysNotes = $dblist->showSysNotesForPage();
			if ($sysNotes)	{
				$content.=$this->doc->spacer(10);
				$content.=$this->doc->section($LANG->getLL('internalNotes'),$sysNotes,0,1);
			}

				// Add spacer in bottom of page:
			$content.=$this->doc->spacer(10);
		}

			// Ending page:
		$content.=$this->doc->spacer(10);

		return $content;
	}

	/**
	 * Print accumulated content of module
	 *
	 * @return	void
	 */
	function printContent()	{
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
	 * @param	string	Identifier for function of module
	 * @return	array	all available buttons as an assoc. array
	 */
	protected function getButtons($function = '')	{
		global $TCA, $LANG, $BACK_PATH, $BE_USER;

		$buttons = array(
			'view' => '',
			'history_page' => '',
			'new_content' => '',
			'move_page' => '',
			'move_record' => '',
			'new_page' => '',
			'edit_page' => '',
			'record_list' => '',
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

			// View page
		$buttons['view'] = '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::viewOnClick($this->pageinfo['uid'], $BACK_PATH, t3lib_BEfunc::BEgetRootLine($this->pageinfo['uid']))) . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.showPage', TRUE) . '">' .
					t3lib_iconWorks::getSpriteIcon('actions-document-view') .
				'</a>';

			// Shortcut
		if ($BE_USER->mayMakeShortcut())	{
			$buttons['shortcut'] = $this->doc->makeShortcutIcon('id, edit_record, pointer, new_unique_uid, search_field, search_levels, showLimit', implode(',', array_keys($this->MOD_MENU)), $this->MCONF['name']);
		}

			// Cache
		if (!$this->modTSconfig['properties']['disableAdvanced'])	{
			$buttons['cache'] = '<a href="' . htmlspecialchars('db_layout.php?id=' . $this->pageinfo['uid'] . '&clear_cache=1') . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.clear_cache', TRUE) . '">' .
					t3lib_iconWorks::getSpriteIcon('actions-system-cache-clear') .
				'</a>';
		}

			// If access to Web>List for user, then link to that module.
		if ($BE_USER->check('modules','web_list'))	{
			$href = $BACK_PATH . 'db_list.php?id=' . $this->pageinfo['uid'] . '&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'));
			$buttons['record_list'] = '<a href="' . htmlspecialchars($href) . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.showList', TRUE) . '">' .
					t3lib_iconWorks::getSpriteIcon('actions-system-list-open') .
				'</a>';
		}

		if (!$this->modTSconfig['properties']['disableIconToolbar'])	{

				// Page history
			$buttons['history_page'] = '<a href="#" onclick="' . htmlspecialchars('jumpToUrl(\'' . $BACK_PATH . 'show_rechis.php?element=' . rawurlencode('pages:' . $this->id) . '&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI')) . '#latest\');return false;') . '" title="' . $LANG->getLL('recordHistory', TRUE) . '">' .
						t3lib_iconWorks::getSpriteIcon('actions-document-history-open') .
					'</a>';
				// New content element
			$buttons['new_content'] = '<a href="' . htmlspecialchars('db_new_content_el.php?id=' . $this->id . '&sys_language_uid=' . $this->current_sys_language . '&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'))) . '" title="' . $LANG->getLL('newContentElement', TRUE) . '">' .
						t3lib_iconWorks::getSpriteIcon('actions-document-new') .
					'</a>';
				// Move page
			$buttons['move_page'] = '<a href="' . htmlspecialchars($BACK_PATH . 'move_el.php?table=pages&uid=' . $this->id . '&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'))) . '" title="' . $LANG->getLL('move_page', TRUE) . '">' .
						t3lib_iconWorks::getSpriteIcon('actions-page-move') .
					'</a>';
				// Move record
			if (t3lib_div::testInt($this->eRParts[1])) {
				$buttons['move_record'] = '<a href="' . htmlspecialchars($BACK_PATH . 'move_el.php?table=' . $this->eRParts[0] . '&uid=' . $this->eRParts[1] . '&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'))) . '">' .
						t3lib_iconWorks::getSpriteIcon('actions-' . ($this->eRParts[0] == 'tt_content' ? 'document' : 'page') . '-move',array('class'=>'c-inputButton','title' => $LANG->getLL('move_' . ($this->eRParts[0] == 'tt_content' ? 'record' : 'page'), 1))) .
						'</a>';
			}
				// Create new page (wizard)
			$buttons['new_page'] = '<a href="#" onclick="' . htmlspecialchars('jumpToUrl(\'' . $BACK_PATH . 'db_new.php?id=' . $this->id . '&pagesOnly=1&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI')) . '\');return false;') . '" title="' . $LANG->getLL('newPage', TRUE) . '">' .
						t3lib_iconWorks::getSpriteIcon('actions-page-new') .
					'</a>';
				// Edit page properties
			if ($this->CALC_PERMS&2)	{
				$params='&edit[pages][' . $this->id . ']=edit';
				$buttons['edit_page'] = '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::editOnClick($params, $BACK_PATH)) . '" title="' . $LANG->getLL('editPageProperties', TRUE) . '">' .
							t3lib_iconWorks::getSpriteIcon('actions-page-open') .
						'</a>';
			}

				// Add CSH (Context Sensitive Help) icon to tool bar
			if($function == 'quickEdit') {
				$buttons['csh'] = t3lib_BEfunc::cshItem($this->descrTable, 'quickEdit', $BACK_PATH, '', TRUE, 'margin-top: 0px; margin-bottom: 0px;');
			} else {
				$buttons['csh'] = t3lib_BEfunc::cshItem($this->descrTable, 'columns_' . $this->MOD_SETTINGS['function'], $BACK_PATH, '', TRUE, 'margin-top: 0px; margin-bottom: 0px;');
			}

			if($function == 'quickEdit') {
					// Save record
				$buttons['savedok'] = '<input class="c-inputButton" type="image" name="savedok"' . t3lib_iconWorks::skinImg($BACK_PATH, 'gfx/savedok.gif','') . ' title="' . $LANG->sL('LLL:EXT:lang/locallang_core.php:rm.saveDoc', 1) . '" alt="" />';

					// Save record and show page
				$buttons['savedokshow'] = '<a href="#" onclick="' . htmlspecialchars('document.editform.redirect.value+=\'&popView=1\'; TBE_EDITOR.checkAndDoSubmit(1); return false;') . '" title="' . $LANG->sL('LLL:EXT:lang/locallang_core.php:rm.saveDocShow', TRUE) . '">' .
						t3lib_iconWorks::getSpriteIcon('actions-document-save-view') .
					'</a>';

					// Close record
				$buttons['closedok'] = '<a href="#" onclick="' . htmlspecialchars('jumpToUrl(unescape(\'' . rawurlencode($this->closeUrl) . '\')); return false;') . '" title="' . $LANG->sL('LLL:EXT:lang/locallang_core.php:rm.closeDoc', TRUE) . '">' .
						t3lib_iconWorks::getSpriteIcon('actions-document-close') .
					'</a>';

					// Delete record
				if($this->deleteButton) {
					$buttons['deletedok'] = '<a href="#" onclick="' . htmlspecialchars('return deleteRecord(\'' . $this->eRParts[0] . '\',\'' . $this->eRParts[1] . '\',\'' . t3lib_div::getIndpEnv('SCRIPT_NAME') . '?id=' . $this->id . '\');') . '" title="' . $LANG->getLL('deleteItem', TRUE) . '">' .
							t3lib_iconWorks::getSpriteIcon('actions-edit-delete') .
						'</a>';
				}

				if($this->undoButton) {
						// Undo button
					$buttons['undo'] = '<a href="#"
						onclick="' . htmlspecialchars('window.location.href=\'' . $BACK_PATH . 'show_rechis.php?element=' . rawurlencode($this->eRParts[0] . ':' . $this->eRParts[1]) . '&revert=ALL_FIELDS&sumUp=-1&returnUrl=' . rawurlencode($this->R_URI) . '\'; return false;') . '"
						title="' . htmlspecialchars(sprintf($LANG->getLL('undoLastChange'), t3lib_BEfunc::calcAge($GLOBALS['EXEC_TIME'] - $this->undoButtonR['tstamp'], $LANG->sL('LLL:EXT:lang/locallang_core.php:labels.minutesHoursDaysYears')))) . '">' .
							t3lib_iconWorks::getSpriteIcon('actions-edit-undo') .
						'</a>';

						// History button
					$buttons['history_record'] = '<a href="#" onclick="' . htmlspecialchars('jumpToUrl(\'' . $BACK_PATH . 'show_rechis.php?element=' . rawurlencode($this->eRParts[0] . ':' . $this->eRParts[1]) . '&returnUrl=' . rawurlencode($this->R_URI) . '#latest\');return false;') . '" title="' . $LANG->getLL('recordHistory', TRUE) . '">' .
							t3lib_iconWorks::getSpriteIcon('actions-document-history-open') .
						'</a>';
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
	 * Returns the number of hidden elements (including those hidden by start/end times) on the current page (for the current sys_language)
	 *
	 * @return	void
	 */
	function getNumberOfHiddenElements()	{
		return $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(
			'uid',
			'tt_content',
			'pid=' . intval($this->id) .
				' AND sys_language_uid=' . intval($this->current_sys_language) .
				t3lib_BEfunc::BEenableFields('tt_content', 1) .
				t3lib_BEfunc::deleteClause('tt_content') .
				t3lib_BEfunc::versioningPlaceholderClause('tt_content')
		);
	}

	/**
	 * Returns URL to the current script.
	 * In particular the "popView" and "new_unique_uid" Get vars are unset.
	 *
	 * @param	array		Parameters array, merged with global GET vars.
	 * @return	string		URL
	 */
	function local_linkThisScript($params)	{
		$params['popView']='';
		$params['new_unique_uid']='';
		return t3lib_div::linkThisScript($params);
	}

	/**
	 * Returns a SQL query for selecting sys_language records.
	 *
	 * @param	integer		Page id: If zero, the query will select all sys_language records from root level which are NOT hidden. If set to another value, the query will select all sys_language records that has a pages_language_overlay record on that page (and is not hidden, unless you are admin user)
	 * @return	string		Return query string.
	 */
	function exec_languageQuery($id)	{
		if ($id)	{
			$exQ = t3lib_BEfunc::deleteClause('pages_language_overlay') . ($GLOBALS['BE_USER']->isAdmin()?'':' AND sys_language.hidden=0');
			return $GLOBALS['TYPO3_DB']->exec_SELECTquery(
							'sys_language.*',
							'pages_language_overlay,sys_language',
							'pages_language_overlay.sys_language_uid=sys_language.uid AND pages_language_overlay.pid='.intval($id).$exQ,
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


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cms/layout/db_layout.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cms/layout/db_layout.php']);
}


// Make instance:
$SOBE = t3lib_div::makeInstance('SC_db_layout');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->clearCache();
$SOBE->main();
$SOBE->printContent();

?>