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
 * Module: Database integrity check
 *
 * This module lets you check if all pages and the records relate properly to each other
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @coauthor	Jo Hasenau <info@cybercraft.de>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   89: class SC_mod_tools_dbint_index
 *  105:     function init()
 *  119:     function jumpToUrl(URL)
 *  139:     function menuConfig()
 *  226:     function main()
 *  270:     function printContent()
 *  281:     function func_default()
 *
 *              SECTION: Functionality implementation
 *  314:     function func_refindex()
 *  344:     function func_search()
 *  386:     function func_tree()
 *  409:     function func_records()
 *  507:     function func_relations()
 *  558:     function func_filesearch()
 *  607:     function findFile($basedir,$pattern,&$matching_files,$depth)
 *
 * TOTAL FUNCTIONS: 13
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


unset($MCONF);
require ('conf.php');
require ($BACK_PATH.'init.php');
require ($BACK_PATH.'template.php');

$GLOBALS['LANG']->includeLLFile('EXT:lowlevel/dbint/locallang.xml');
$BE_USER->modAccess($MCONF,1);






/**
 * Script class for the DB int module
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_lowlevel
 */
class SC_mod_tools_dbint_index {

	var $MCONF = array();
	var $MOD_MENU = array();
	var $MOD_SETTINGS = array();

	/**
	 * document template object
	 *
	 * @var mediumDoc
	 */
	var $doc;

	var $content;
	var $menu;

	protected $formName = 'queryform';


	/**
	 * Initialization
	 *
	 * @return	void
	 */
	function init()	{
		global $LANG,$BACK_PATH;
		$this->MCONF = $GLOBALS['MCONF'];

		$this->menuConfig();

		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $BACK_PATH;
		$this->doc->setModuleTemplate('templates/dbint.html');
		$this->doc->form='<form action="" method="post" name="'.$this->formName.'">';

				// JavaScript
		$this->doc->JScode = '
		<script language="javascript" type="text/javascript">
			script_ended = 0;
			function jumpToUrl(URL)	{
				window.location.href = URL;
			}
		</script>
		';
		$this->doc->table_TABLE = '<table border="0" cellspacing="0" cellpadding="0" class="typo3-dblist" style="width:400px!important;">
			<colgroup><col width="24"><col width="300"><col width="76"></colgroup>';

		$this->doc->tableLayout = array (
			'0' => array (
				'defCol' => array('<td class="t3-row-header"><img src="' . $this->doc->backPath . 'clear.gif" width="10" height="1" alt="" /></td><td valign="top" class="t3-row-header"><strong>', '</strong></td>')
			),
			'defRow' => array (
				'0' => array('<td valign="top">','</td>'),
				'1' => array('<td valign="top">','</td>'),
				'defCol' => array('<td><img src="' . $this->doc->backPath . 'clear.gif" width="15" height="1" alt="" /></td><td valign="top">', '</td>')
			)
		);
	}

	/**
	 * Configure menu
	 *
	 * @return	void
	 */
	function menuConfig()	{
		global $LANG;

		// MENU-ITEMS:
			// If array, then it's a selector box menu
			// If empty string it's just a variable, that'll be saved.
			// Values NOT in this array will not be saved in the settings-array for the module.
		$this->MOD_MENU = array(
			'function' => array(
				0 => $GLOBALS['LANG']->getLL('menu', true),
				'records' => $GLOBALS['LANG']->getLL('recordStatistics', true),
				'tree' => $GLOBALS['LANG']->getLL('totalPageTree', true),
				'relations' => $GLOBALS['LANG']->getLL('databaseRelations', true),
				'search' => $GLOBALS['LANG']->getLL('fullSearch', true),
				'filesearch' => $GLOBALS['LANG']->getLL('findFilename', true),
				'refindex' => $GLOBALS['LANG']->getLL('manageRefIndex', true),
			),
			'search' => array(
				'raw' => $GLOBALS['LANG']->getLL('rawSearch', true),
				'query' => $GLOBALS['LANG']->getLL('advancedQuery', true)
			),

			'search_query_smallparts' => '',
			'search_result_labels' => '',
			'labels_noprefix' => '',
			'options_sortlabel' => '',
			'show_deleted' => '',

			'queryConfig' => '',	// Current query
			'queryTable' => '',	// Current table
			'queryFields' => '',	// Current tableFields
			'queryLimit' => '',	// Current limit
			'queryOrder' => '',	// Current Order field
			'queryOrderDesc' => '',	// Current Order field descending flag
			'queryOrder2' => '',	// Current Order2 field
			'queryOrder2Desc' => '',	// Current Order2 field descending flag
			'queryGroup' => '',	// Current Group field

			'storeArray' => '',	// Used to store the available Query config memory banks
			'storeQueryConfigs' => '',	// Used to store the available Query configs in memory

			'search_query_makeQuery' => array(
				'all' => $GLOBALS['LANG']->getLL('selectRecords', true),
				'count' => $GLOBALS['LANG']->getLL('countResults', true),
				'explain' => $GLOBALS['LANG']->getLL('explainQuery', true),
				'csv' => $GLOBALS['LANG']->getLL('csvExport', true),
				'xml' => $GLOBALS['LANG']->getLL('xmlExport', true)
			),

			'sword' => ''
		);
			// CLEAN SETTINGS
		$OLD_MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU,'', $this->MCONF['name'], 'ses');
		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::_GP('SET'), $this->MCONF['name'], 'ses');

		if (t3lib_div::_GP('queryConfig'))	{
			$qA = t3lib_div::_GP('queryConfig');
			$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, array('queryConfig'=>serialize($qA)), $this->MCONF['name'], 'ses');
		}
		$addConditionCheck = t3lib_div::_GP('qG_ins');
		foreach ($OLD_MOD_SETTINGS as $key=>$val)	{
			if (substr($key, 0, 5)=='query' && $this->MOD_SETTINGS[$key]!=$val && $key!='queryLimit' && $key!='use_listview')	{
				$setLimitToStart = 1;
				if ($key == 'queryTable' && !$addConditionCheck) {
					$this->MOD_SETTINGS['queryConfig'] = '';
				}
			}
			if ($key=='queryTable' && $this->MOD_SETTINGS[$key]!=$val)	{
				$this->MOD_SETTINGS['queryFields'] = '';
			}
		}
		if ($setLimitToStart)	{
			$currentLimit = explode(',',$this->MOD_SETTINGS['queryLimit']);
			if ($currentLimit[1])	{
				$this->MOD_SETTINGS['queryLimit']='0,'.$currentLimit[1];
			} else {
				$this->MOD_SETTINGS['queryLimit']='0';
			}
			$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, $this->MOD_SETTINGS, $this->MCONF['name'], 'ses');
		}
	}

	/**
	 * Main
	 *
	 * @return	void
	 */
	function main()	{
		global $BE_USER,$LANG;

			// Content creation
		if (!$GLOBALS['BE_USER']->userTS['mod.']['dbint.']['disableTopMenu'])	{
			$this->menu = t3lib_BEfunc::getFuncMenu(0,'SET[function]',$this->MOD_SETTINGS['function'],$this->MOD_MENU['function']);
		}

		switch($this->MOD_SETTINGS['function'])	{
			case 'search':
				$this->func_search();
			break;
			case 'tree':
				$this->func_tree();
			break;
			case 'records':
				$this->func_records();
			break;
			case 'relations':
				$this->func_relations();
			break;
			case 'filesearch':
				$this->func_filesearch();
			break;
			case 'refindex':
				$this->func_refindex();
			break;
			default:
				$this->func_default();
			break;
		}

			// Setting up the buttons and markers for docheader
		$docHeaderButtons = $this->getButtons();
		$markers = array(
			'CSH' => $docHeaderButtons['csh'],
			'FUNC_MENU' => $this->getFuncMenu(),
			'CONTENT' => $this->content
		);

			// Build the <body> for the module
		$this->content = $this->doc->startPage($GLOBALS['LANG']->getLL('title'));
		$this->content.= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		$this->content.= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
	}

	/**
	 * Print content
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

		$buttons = array(
			'csh' => '',
			'shortcut' => ''
		);
			// CSH
		//$buttons['csh'] = t3lib_BEfunc::cshItem('_MOD_web_func', '', $GLOBALS['BACK_PATH']);

			// Shortcut
		if ($GLOBALS['BE_USER']->mayMakeShortcut())	{
			$buttons['shortcut'] = $this->doc->makeShortcutIcon('','function,search,search_query_makeQuery',$this->MCONF['name']);
		}
		return $buttons;
	}

	/**
	 * Create the function menu
	 *
	 * @return	string	HTML of the function menu
	 */
	protected function getFuncMenu() {
		if (!$GLOBALS['BE_USER']->userTS['mod.']['dbint.']['disableTopMenu']) {
			$funcMenu = t3lib_BEfunc::getFuncMenu(0, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function']);
		}
		return $funcMenu;
	}

	/**
	 * Menu
	 *
	 * @return	void
	 */
	function func_default()	{
		$this->content .= $this->doc->header($GLOBALS['LANG']->getLL('title'));

		$content = '<dl class="t3-overview-list">';

		$availableModFuncs = array('records', 'tree', 'relations', 'search', 'filesearch', 'refindex');
		foreach ($availableModFuncs as $modFunc) {
			$link = 'index.php?SET[function]=' . $modFunc;
			$title = $GLOBALS['LANG']->getLL($modFunc);
			$description = $GLOBALS['LANG']->getLL($modFunc . '_description');
			$content .= '
				<dt><a href="' . $link . '">' . $title . '</a></dt>
				<dd>' . $description . '</dd>
			';
		}

		$content .= '</dl>';
		$this->content .= $content;
	}








	/****************************
	 *
	 * Functionality implementation
	 *
	 ****************************/

	/**
	 * Check and update reference index!
	 *
	 * @return	void
	 */
	function func_refindex()	{
		global $TYPO3_DB,$TCA;

		if (t3lib_div::_GP('_update') || t3lib_div::_GP('_check'))	{
			$testOnly = t3lib_div::_GP('_check')?TRUE:FALSE;

				// Call the functionality
			$refIndexObj = t3lib_div::makeInstance('t3lib_refindex');
			list($headerContent,$bodyContent) = $refIndexObj->updateIndex($testOnly);

				// Output content:
			$this->content.=$this->doc->section($headerContent,str_replace(LF,'<br/>',$bodyContent),0,1);
		}

			// Output content:
		$content = '<p>' . $GLOBALS['LANG']->getLL('referenceIndex_description') . '</p><br />';
		$content .= '<input type="submit" name="_check" value="' . $GLOBALS['LANG']->getLL('referenceIndex_buttonCheck') . '" /> <input type="submit" name="_update" value="' . $GLOBALS['LANG']->getLL('referenceIndex_buttonUpdate') . '" /><br /><br />';
		$content .= '<h3>' . $GLOBALS['LANG']->getLL('checkScript_headline') . '</h3>';
		$content.= '<p>' . $GLOBALS['LANG']->getLL('checkScript') . '</p>';
		$content.= '<h4>' . $GLOBALS['LANG']->getLL('checkScript_check_description') . '</h4>' .
					'<code>php ' . PATH_typo3 . 'cli_dispatch.phpsh lowlevel_refindex -c</code><br />';
		$content.= '<h4>' . $GLOBALS['LANG']->getLL('checkScript_update_description') . '</h4>' .
					'<code>php ' . PATH_typo3 . 'cli_dispatch.phpsh lowlevel_refindex -e</code><br /><br />';
		$content.= '<div class="typo3-message message-information"><div class="message-body">' . $GLOBALS['LANG']->getLL('checkScript_information') . '</div></div>';
		$content.= '<p>' . $GLOBALS['LANG']->getLL('checkScript_moreDetails') . '<br /><a href="' . $GLOBALS['BACK_PATH'] . 'sysext/lowlevel/HOWTO_clean_up_TYPO3_installations.txt" target="_new">' . PATH_typo3 . 'sysext/lowlevel/HOWTO_clean_up_TYPO3_installations.txt</a></p>';

		$this->content.= $this->doc->section($GLOBALS['LANG']->getLL('updateRefIndex'), $content, false, true);
	}

	/**
	 * Search (Full / Advanced)
	 *
	 * @return	void
	 */
	function func_search()	{
		global $LANG;

		$fullsearch = t3lib_div::makeInstance('t3lib_fullsearch');
		$fullsearch->setFormName($this->formName);
		$this->content.= $this->doc->header($GLOBALS['LANG']->getLL('search'));
		$this->content.= $this->doc->spacer(5);

		$menu2='';
		if (!$GLOBALS['BE_USER']->userTS['mod.']['dbint.']['disableTopMenu'])	{
			$menu2 = t3lib_BEfunc::getFuncMenu(0, 'SET[search]', $this->MOD_SETTINGS['search'], $this->MOD_MENU['search']);
		}
		if ($this->MOD_SETTINGS['search']=='query' && !$GLOBALS['BE_USER']->userTS['mod.']['dbint.']['disableTopMenu'])	{
			$menu2 .= t3lib_BEfunc::getFuncMenu(0, 'SET[search_query_makeQuery]', $this->MOD_SETTINGS['search_query_makeQuery'], $this->MOD_MENU['search_query_makeQuery']) . '<br />';
		}
		if (!$GLOBALS['BE_USER']->userTS['mod.']['dbint.']['disableTopCheckboxes'] && $this->MOD_SETTINGS['search']=='query')	{
			$menu2 .= t3lib_BEfunc::getFuncCheck($GLOBALS['SOBE']->id, 'SET[search_query_smallparts]', $this->MOD_SETTINGS['search_query_smallparts'], '', '', 'id="checkSearch_query_smallparts"') . '&nbsp;<label for="checkSearch_query_smallparts">' . $GLOBALS['LANG']->getLL('showSQL') . '</label><br />';
			$menu2 .= t3lib_BEfunc::getFuncCheck($GLOBALS['SOBE']->id, 'SET[search_result_labels]', $this->MOD_SETTINGS['search_result_labels'], '', '', 'id="checkSearch_result_labels"') . '&nbsp;<label for="checkSearch_result_labels">' . $GLOBALS['LANG']->getLL('useFormattedStrings') . '</label><br />';
			$menu2 .= t3lib_BEfunc::getFuncCheck($GLOBALS['SOBE']->id, 'SET[labels_noprefix]', $this->MOD_SETTINGS['labels_noprefix'], '', '', 'id="checkLabels_noprefix"') . '&nbsp;<label for="checkLabels_noprefix">' . $GLOBALS['LANG']->getLL('dontUseOrigValues') . '</label><br />';
			$menu2 .= t3lib_BEfunc::getFuncCheck($GLOBALS['SOBE']->id, 'SET[options_sortlabel]', $this->MOD_SETTINGS['options_sortlabel'], '', '', 'id="checkOptions_sortlabel"') . '&nbsp;<label for="checkOptions_sortlabel">' . $GLOBALS['LANG']->getLL('sortOptions') . '</label><br />';
			$menu2 .= t3lib_BEfunc::getFuncCheck($GLOBALS['SOBE']->id, 'SET[show_deleted]', $this->MOD_SETTINGS['show_deleted'], '', '', 'id="checkShow_deleted"') . '&nbsp;<label for="checkShow_deleted">' . $GLOBALS['LANG']->getLL('showDeleted') . '</label>';
		}

		$this->content.= $this->doc->section('',$menu2).$this->doc->spacer(10);

		switch($this->MOD_SETTINGS['search'])		{
			case 'query':
				$this->content.=$fullsearch->queryMaker();
			break;
			case 'raw':
			default:
				$this->content.=$this->doc->section($GLOBALS['LANG']->getLL('searchOptions'), $fullsearch->form(), false, true);
				$this->content.=$this->doc->section($GLOBALS['LANG']->getLL('result'), $fullsearch->search(), false, true);
			break;
		}
	}

	/**
	 * Display page tree
	 *
	 * @return	void
	 */
	function func_tree()	{
		global $LANG,$BACK_PATH;

		$startID = 0;
		$admin = t3lib_div::makeInstance('t3lib_admin');
		$admin->genTree_makeHTML=1;
		$admin->backPath = $BACK_PATH;
		$admin->genTree(intval($startID),'<img src="' . $BACK_PATH . 'clear.gif" width="1" height="1" align="top" alt="" />');

		$this->content.= $this->doc->header($GLOBALS['LANG']->getLL('tree'));
		$this->content.= $this->doc->spacer(5);
		$this->content.= $this->doc->sectionEnd();

		$this->content.= $admin->genTree_HTML;
		$this->content.= $admin->lostRecords($admin->genTree_idlist.'0');
	}

	/**
	 * Records overview
	 *
	 * @return	void
	 */
	function func_records()	{
		global $LANG,$TCA,$BACK_PATH,$PAGES_TYPES;

		$admin = t3lib_div::makeInstance('t3lib_admin');
		$admin->genTree_makeHTML = 0;
		$admin->backPath = $BACK_PATH;
		$admin->genTree(0,'');

		$this->content.= $this->doc->header($GLOBALS['LANG']->getLL('records'));
		$this->content.= $this->doc->spacer(5);

			// Pages stat
		$codeArr=array();
		$codeArr['tableheader'] = array('', $GLOBALS['LANG']->getLL('count'));
		$i++;
		$codeArr[$i][]='<img' . t3lib_iconWorks::skinImg($BACK_PATH,'gfx/i/pages.gif','width="18" height="16"') . ' hspace="4" align="top" alt="" />';
		$codeArr[$i][]=$GLOBALS['LANG']->getLL('total_pages');
		$codeArr[$i][]=count($admin->page_idArray);
		$i++;
		if (t3lib_extMgm::isLoaded('cms'))	{
			$codeArr[$i][]='<img' . t3lib_iconWorks::skinImg($BACK_PATH,'gfx/hidden_page.gif','width="18" height="16"') . ' hspace="4" align="top">';
			$codeArr[$i][]=$GLOBALS['LANG']->getLL('hidden_pages');
			$codeArr[$i][]=$admin->recStat['hidden'];
			$i++;
		}
		$codeArr[$i][]='<img' . t3lib_iconWorks::skinImg($BACK_PATH,'gfx/deleted_page.gif','width="18" height="16"') . ' hspace="4" align="top">';
		$codeArr[$i][]=$GLOBALS['LANG']->getLL('deleted_pages');
		$codeArr[$i][]=$admin->recStat['deleted'];

		$this->content.=$this->doc->section($GLOBALS['LANG']->getLL('pages'), $this->doc->table($codeArr), false, true);

			// Doktype
		$codeArr=array();
		$codeArr['tableheader'] = array($GLOBALS['LANG']->getLL('doktype_value'), $GLOBALS['LANG']->getLL('count'));
		$doktype= $TCA['pages']['columns']['doktype']['config']['items'];
		if (is_array($doktype))	{
			foreach ($doktype as $n => $setup) {
				if ($setup[1]!='--div--')	{
					$codeArr[$n][] = '<img' . t3lib_iconWorks::skinImg($BACK_PATH,'gfx/i/' . ($PAGES_TYPES[$setup[1]]['icon'] ? $PAGES_TYPES[$setup[1]]['icon'] : $PAGES_TYPES['default']['icon']), 'width="18" height="16"') . ' hspace="4" align="top">';
					$codeArr[$n][] = $GLOBALS['LANG']->sL($setup[0]) . ' (' . $setup[1] . ')';
					$codeArr[$n][] = intval($admin->recStat[doktype][$setup[1]]);
				}
			}
			$this->content.=$this->doc->section($GLOBALS['LANG']->getLL('doktype'), $this->doc->table($codeArr), false, true);
		}

			// Tables and lost records
		$id_list = '-1,0,'.implode(array_keys($admin->page_idArray),',');
		$id_list = t3lib_div::rm_endcomma($id_list);
		$admin->lostRecords($id_list);

		if ($admin->fixLostRecord(t3lib_div::_GET('fixLostRecords_table'),t3lib_div::_GET('fixLostRecords_uid')))	{
			$admin = t3lib_div::makeInstance('t3lib_admin');
			$admin->backPath = $BACK_PATH;
			$admin->genTree(0,'');
			$id_list = '-1,0,'.implode(array_keys($admin->page_idArray),',');
			$id_list = t3lib_div::rm_endcomma($id_list);
			$admin->lostRecords($id_list);
		}

		$this->doc->table_TABLE = '<table border="0" cellspacing="0" cellpadding="0" class="typo3-dblist" style="width:700px!important;">';

		$codeArr = array();
		$codeArr['tableheader'] = array(
			$GLOBALS['LANG']->getLL('label'),
			$GLOBALS['LANG']->getLL('tablename'),
			$GLOBALS['LANG']->getLL('total_lost'),
			''
		);

		$countArr = $admin->countRecords($id_list);
		if (is_array($TCA))	{

			foreach ($TCA as $t => $value) {
				if ($TCA[$t]['ctrl']['hideTable']) {
					continue;
				}
				$codeArr[$t][]=t3lib_iconWorks::getIconImage($t,array(),$BACK_PATH,'hspace="4" align="top"');
				$codeArr[$t][]=$LANG->sL($TCA[$t]['ctrl']['title']);
				$codeArr[$t][]=$t;

				if ($countArr['all'][$t])	{
					$theNumberOfRe = intval($countArr['non_deleted'][$t]).'/'.(intval($countArr['all'][$t])-intval($countArr['non_deleted'][$t]));
				} else {
					$theNumberOfRe ='';
				}
				$codeArr[$t][]=$theNumberOfRe;

				$lr='';
				if (is_array($admin->lRecords[$t]))	{
					foreach ($admin->lRecords[$t] as $data) {
						if (!t3lib_div::inList($admin->lostPagesList,$data[pid]))	{
							$lr.= '<nobr><strong><a href="index.php?SET[function]=records&fixLostRecords_table=' . $t . '&fixLostRecords_uid=' . $data[uid] . '"><img src="' . $BACK_PATH . 'gfx/required_h.gif" width="10" hspace="3" height="10" border="0" align="top" title="' . $GLOBALS['LANG']->getLL('fixLostRecord') . '"></a>uid:' . $data[uid] . ', pid:' . $data[pid] . ', ' . t3lib_div::fixed_lgd_cs(strip_tags($data[title]), 20) . '</strong></nobr><br>';
						} else {
							$lr.= '<nobr><img src="' . $BACK_PATH . 'clear.gif" width="16" height="1" border="0"><font color="Gray">uid:' . $data[uid] . ', pid:' . $data[pid] . ', ' . t3lib_div::fixed_lgd_cs(strip_tags($data[title]), 20) . '</font></nobr><br>';
						}
					}
				}
				$codeArr[$t][]=$lr;
			}
			$this->content.=$this->doc->section($GLOBALS['LANG']->getLL('tables'), $this->doc->table($codeArr), false, true);
		}
	}

	/**
	 * Show list references
	 *
	 * @return	void
	 */
	function func_relations()	{
		global $LANG,$BACK_PATH;

		$this->content.= $this->doc->header($GLOBALS['LANG']->getLL('relations'));
		$this->content.= $this->doc->spacer(5);

		$admin = t3lib_div::makeInstance('t3lib_admin');
		$admin->genTree_makeHTML=0;
		$admin->backPath = $BACK_PATH;

		$fkey_arrays = $admin->getGroupFields('');
		$admin->selectNonEmptyRecordsWithFkeys($fkey_arrays);


		$fileTest = $admin->testFileRefs();

		$code='';
		if (is_array($fileTest['noReferences']))	{
			foreach ($fileTest['noReferences'] as $val) {
				$code.='<nobr>' . $val[0] . '/<strong>' . $val[1] . '</strong></nobr><br>';
			}
		}
		$this->content.=$this->doc->section($GLOBALS['LANG']->getLL('files_no_ref'), $code, true, true);

		$code='';
		if (is_array($fileTest['moreReferences']))	{
			foreach ($fileTest['moreReferences'] as $val) {
				$code.='<nobr>' . $val[0] . '/<strong>' . $val[1] . '</strong>: ' . $val[2] . ' ' . $GLOBALS['LANG']->getLL('references') . '</nobr><br>' . $val[3] . '<br><br>';
			}
		}
		$this->content.=$this->doc->section($GLOBALS['LANG']->getLL('files_many_ref'),$code, true, true);

		$code='';
		if (is_array($fileTest['noFile']))	{
			ksort($fileTest['noFile']);
			foreach ($fileTest['noFile'] as $val) {
				$code.='<nobr>' . $val[0] . '/<strong>' . $val[1] . '</strong> ' . $GLOBALS['LANG']->getLL('isMissing') . ' </nobr><br>' . $GLOBALS['LANG']->getLL('referencedFrom') . $val[2] . '<br><br>';
			}
		}
		$this->content.= $this->doc->section($GLOBALS['LANG']->getLL('files_no_file'), $code, true, true);
		$this->content.= $this->doc->section($GLOBALS['LANG']->getLL('select_db'), $admin->testDBRefs($admin->checkSelectDBRefs), true, true);
		$this->content.= $this->doc->section($GLOBALS['LANG']->getLL('group_db'), $admin->testDBRefs($admin->checkGroupDBRefs), true, true);
	}

	/**
	 * Searching for files with a specific pattern
	 *
	 * @return	void
	 */
	function func_filesearch()	{
		$pattern = t3lib_div::_GP('pattern');
		$pcontent = $GLOBALS['LANG']->getLL('enterRegexPattern') . ' <input type="text" name="pattern" value="' . htmlspecialchars($pattern ? $pattern : $GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern']) . '"> <input type="submit" name="' . $GLOBALS['LANG']->getLL('SearchButton') . '">';
		$this->content.= $this->doc->section($GLOBALS['LANG']->getLL('pattern'), $pcontent, false, true);

		if (strcmp($pattern,''))	{
			$dirs = t3lib_div::get_dirs(PATH_site);
			$lines=array();
			$depth=10;

			foreach ($dirs as $key => $value) {
				$matching_files=array();
				$info='';
				if (!t3lib_div::inList('typo3,typo3conf,tslib,media,t3lib',$value))	{
					$info = $this->findFile(PATH_site.$value.'/',$pattern,$matching_files,$depth);
				}
				if (is_array($info))	{
					$lines[]='<hr><strong>' . $value . '/</strong> ' . $GLOBALS['LANG']->getLL('beingChecked');
					$lines[]=$GLOBALS['LANG']->getLL('directories') . ' ' . $info[0];
					if ($info[2])	$lines[]='<span class="typo3-red">' . $GLOBALS['LANG']->getLL('directoriesTooDeep') . ' ' . $depth . '</span>';
					$lines[]=$GLOBALS['LANG']->getLL('files') . ' ' . $info[1];
					$lines[]=$GLOBALS['LANG']->getLL('matchingFiles') . '<br><nobr><span class="typo3-red">' . implode('<br>', $matching_files) . '</span></nobr>';
				} else {
					$lines[]=$GLOBALS['TBE_TEMPLATE']->dfw('<hr><strong>' . $value . '/</strong> ' . $GLOBALS['LANG']->getLL('notChecked'));
				}
			}

			$this->content.=$this->doc->section($GLOBALS['LANG']->getLL('searchingForFilenames'), implode('<br>', $lines), false, true);
		}
	}

	/**
	 * Searching for filename pattern recursively in the specified dir.
	 *
	 * @param	string		Base directory
	 * @param	string		Match pattern
	 * @param	array		Array of matching files, passed by reference
	 * @param	integer		Depth to recurse
	 * @return	array		Array with various information about the search result
	 * @see func_filesearch()
	 */
	function findFile($basedir,$pattern,&$matching_files,$depth)	{
		$files_searched=0;
		$dirs_searched=0;
		$dirs_error=0;

			// Traverse files:
		$files = t3lib_div::getFilesInDir($basedir,'',1);
		if (is_array($files))	{
			$files_searched+=count($files);
			foreach ($files as $value) {
				if (preg_match('/'.$pattern.'/i',basename($value)))	$matching_files[]=substr($value,strlen(PATH_site));
			}
		}


			// Traverse subdirs
		if ($depth>0)	{
			$dirs = t3lib_div::get_dirs($basedir);
			if (is_array($dirs))	{
				$dirs_searched+=count($dirs);

				foreach ($dirs as $value) {
					$inf= $this->findFile($basedir.$value.'/',$pattern,$matching_files,$depth-1);
					$dirs_searched+=$inf[0];
					$files_searched+=$inf[1];
					$dirs_error=$inf[2];
				}
			}
		} else {
			$dirs = t3lib_div::get_dirs($basedir);
			if (is_array($dirs) && count($dirs))	{
				$dirs_error=1;	// Means error - there were further subdirs!
			}
		}

		return array($dirs_searched,$files_searched,$dirs_error);
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/lowlevel/dbint/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/lowlevel/dbint/index.php']);
}



// Make instance:
$SOBE = t3lib_div::makeInstance('SC_mod_tools_dbint_index');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

?>
