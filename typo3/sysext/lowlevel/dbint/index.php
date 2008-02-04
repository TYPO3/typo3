<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2008 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
require_once (PATH_t3lib.'class.t3lib_admin.php');
require_once (PATH_t3lib.'class.t3lib_loaddbgroup.php');
require_once (PATH_t3lib.'class.t3lib_querygenerator.php');
require_once (PATH_t3lib.'class.t3lib_xml.php');
require_once (PATH_t3lib.'class.t3lib_fullsearch.php');
require_once (PATH_t3lib.'class.t3lib_refindex.php');

$LANG->includeLLFile('EXT:lowlevel/dbint/locallang.xml');
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
	var $doc;

	var $content;
	var $menu;

	var $formName = 'queryform';


	/**
	 * Initialization
	 *
	 * @return	void
	 */
	function init()	{
		global $LANG,$BACK_PATH;
		$this->MCONF = $GLOBALS['MCONF'];

		$this->menuConfig();

		$this->doc = t3lib_div::makeInstance('mediumDoc');
		$this->doc->form='<form action="" method="post" name="'.$this->formName.'">';
		$this->doc->backPath = $BACK_PATH;

				// JavaScript
		$this->doc->JScode = '
		<script language="javascript" type="text/javascript">
			script_ended = 0;
			function jumpToUrl(URL)	{
				window.location.href = URL;
			}
		</script>
		';

		$this->doc->tableLayout = Array (
			'defRow' => Array (
				'0' => Array('<td valign="top">','</td>'),
				'1' => Array('<td valign="top">','</td>'),
				'defCol' => Array('<td><img src="'.$this->doc->backPath.'clear.gif" width="15" height="1" alt="" /></td><td valign="top">','</td>')
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
				0 => '[ MENU ]',
				'records' => 'Record Statistics',
				'tree' => 'Total Page Tree',
				'relations' => 'Database Relations',
				'search' => 'Full search',
				'filesearch' => 'Find filename',
				'refindex' => 'Manage Reference Index',
			),
			'search' => array(
				'raw' => 'Raw search in all fields',
				'query' => 'Advanced query'
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
				'all' => 'Select records',
				'count' => 'Count results',
				'explain' => 'Explain query',
				'csv' => 'CSV Export',
				'xml' => 'XML Export'
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
		$this->content.= $this->doc->startPage($LANG->getLL('title'));
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

		if ($BE_USER->mayMakeShortcut())	{
			$this->content.=$this->doc->spacer(20).
						$this->doc->section('',$this->doc->makeShortcutIcon('','function,search,search_query_makeQuery',$this->MCONF['name']));
		}
	}

	/**
	 * Print content
	 *
	 * @return	void
	 */
	function printContent()	{

		$this->content.= $this->doc->endPage();
		echo $this->content;
	}

	/**
	 * Menu
	 *
	 * @return	void
	 */
	function func_default()	{
		global $LANG;

		$this->content.=$this->doc->header($LANG->getLL('title'));
		$this->content.=$this->doc->spacer(5);
		$this->content.=$this->doc->section('',$this->menu);
		$this->content.=$this->doc->section('<a href="index.php?SET[function]=records">'.$LANG->getLL('records').'</a>',$LANG->getLL('records_description'),1,1,0,1);
		$this->content.=$this->doc->section('<a href="index.php?SET[function]=tree">'.$LANG->getLL('tree').'</a>',$LANG->getLL('tree_description'),1,1,0,1);
		$this->content.=$this->doc->section('<a href="index.php?SET[function]=relations">'.$LANG->getLL('relations').'</a>',$LANG->getLL('relations_description'),1,1,0,1);
		$this->content.=$this->doc->section('<a href="index.php?SET[function]=search">'.$LANG->getLL('search').'</a>',$LANG->getLL('search_description'),1,1,0,1);
		$this->content.=$this->doc->section('<a href="index.php?SET[function]=filesearch">'.$LANG->getLL('filesearch').'</a>',$LANG->getLL('filesearch_description'),1,1,0,1);
		$this->content.=$this->doc->section('<a href="index.php?SET[function]=refindex">'.$LANG->getLL('refindex').'</a>',$LANG->getLL('refindex_description'),1,1,0,1);
		$this->content.=$this->doc->spacer(50);
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

		$this->content.=$this->doc->section('',$this->menu);
		$this->content.=$this->doc->section('',$menu2).$this->doc->spacer(10);

		if (t3lib_div::_GP('_update') || t3lib_div::_GP('_check'))	{
			$testOnly = t3lib_div::_GP('_check')?TRUE:FALSE;

				// Call the functionality
			$refIndexObj = t3lib_div::makeInstance('t3lib_refindex');
			list($headerContent,$bodyContent) = $refIndexObj->updateIndex($testOnly);

				// Output content:
			$this->content.=$this->doc->section($headerContent,str_replace(chr(10),'<br/>',$bodyContent),0,1);
		}

			// Output content:
		$content = 'Click here to update reference index: <input type="submit" name="_update" value="Update now!" /><br/>';
		$content.= 'Click here to test reference index: <input type="submit" name="_check" value="Check now!" /><br/>';
		$content.= 'You can also run the check as a shell script if the processing takes longer than the PHP max_execution_time allows:<br/>'.
					t3lib_extMgm::extPath('lowlevel').'dbint/cli/refindex_cli.phpsh';
		$this->content.=$this->doc->section('Update reference index',$content,0,1);
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
		$this->content.= $this->doc->header($LANG->getLL('search'));
		$this->content.= $this->doc->spacer(5);

		$menu2='';
		if (!$GLOBALS['BE_USER']->userTS['mod.']['dbint.']['disableTopMenu'])	{
			$menu2 = t3lib_BEfunc::getFuncMenu(0, 'SET[search]', $this->MOD_SETTINGS['search'], $this->MOD_MENU['search']);
		}
		if ($this->MOD_SETTINGS['search']=='query' && !$GLOBALS['BE_USER']->userTS['mod.']['dbint.']['disableTopMenu'])	{
			$menu2 .= t3lib_BEfunc::getFuncMenu(0, 'SET[search_query_makeQuery]', $this->MOD_SETTINGS['search_query_makeQuery'], $this->MOD_MENU['search_query_makeQuery']). '<br />';
		}
		if (!$GLOBALS['BE_USER']->userTS['mod.']['dbint.']['disableTopCheckboxes'] && $this->MOD_SETTINGS['search']=='query')	{
			$menu2 .= t3lib_BEfunc::getFuncCheck($GLOBALS['SOBE']->id, 'SET[search_query_smallparts]', $this->MOD_SETTINGS['search_query_smallparts'],'','','id="checkSearch_query_smallparts"').'&nbsp;<label for="checkSearch_query_smallparts">Show SQL parts</label><br />';
			$menu2 .= t3lib_BEfunc::getFuncCheck($GLOBALS['SOBE']->id, 'SET[search_result_labels]', $this->MOD_SETTINGS['search_result_labels'],'','','id="checkSearch_result_labels"').'&nbsp;<label for="checkSearch_result_labels">Use formatted strings, labels and dates instead of original values for results</label><br />';
			$menu2 .= t3lib_BEfunc::getFuncCheck($GLOBALS['SOBE']->id, 'SET[labels_noprefix]', $this->MOD_SETTINGS['labels_noprefix'],'','','id="checkLabels_noprefix"').'&nbsp;<label for="checkLabels_noprefix">Don\'t use original values in brackets as prefix for labelled results</label><br />';
			$menu2 .= t3lib_BEfunc::getFuncCheck($GLOBALS['SOBE']->id, 'SET[options_sortlabel]', $this->MOD_SETTINGS['options_sortlabel'],'','','id="checkOptions_sortlabel"').'&nbsp;<label for="checkOptions_sortlabel">Sort selectbox options for relations by label and not by value</label><br />';
			$menu2 .= t3lib_BEfunc::getFuncCheck($GLOBALS['SOBE']->id, 'SET[show_deleted]', $this->MOD_SETTINGS['show_deleted'],'','','id="checkShow_deleted"').'&nbsp;<label for="checkShow_deleted">Show even deleted entries (with undelete buttons)</label>';
		}

		$this->content.= $this->doc->section('',$this->menu);//$this->doc->divider(5);
		$this->content.= $this->doc->section('',$menu2).$this->doc->spacer(10);

		switch($this->MOD_SETTINGS['search'])		{
			case 'query':
				$this->content.=$fullsearch->queryMaker();
			break;
			case 'raw':
			default:
				$this->content.=$this->doc->section('Search options:',$fullsearch->form(),0,1);
				$this->content.=$this->doc->section('Result:',$fullsearch->search(),0,1);
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
		$admin->genTree(intval($startID),'<img src="'.$BACK_PATH.'clear.gif" width="1" height="1" align="top" alt="" />');

		$this->content.= $this->doc->header($LANG->getLL('tree'));
		$this->content.= $this->doc->spacer(5);
		$this->content.= $this->doc->section('',$this->menu).$this->doc->divider(5);
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

		$this->content.= $this->doc->header($LANG->getLL('records'));
		$this->content.= $this->doc->spacer(5);
		$this->content.= $this->doc->section('',$this->menu);

			// Pages stat
		$codeArr=Array();
		$i++;
		$codeArr[$i][]='<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/i/pages.gif','width="18" height="16"').' hspace="4" align="top" alt="" />';
		$codeArr[$i][]=$LANG->getLL('total_pages');
		$codeArr[$i][]=count($admin->page_idArray);
		$i++;
		if (t3lib_extMgm::isLoaded('cms'))	{
			$codeArr[$i][]='<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/hidden_page.gif','width="18" height="16"').' hspace="4" align="top">';
			$codeArr[$i][]=$LANG->getLL('hidden_pages');
			$codeArr[$i][]=$admin->recStat['hidden'];
			$i++;
		}
		$codeArr[$i][]='<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/deleted_page.gif','width="18" height="16"').' hspace="4" align="top">';
		$codeArr[$i][]=$LANG->getLL('deleted_pages');
		$codeArr[$i][]=$admin->recStat['deleted'];

		$this->content.=$this->doc->section($LANG->getLL('pages'),$this->doc->table($codeArr),0,1);

			// Doktype
		$codeArr=Array();
		$doktype= $TCA['pages']['columns']['doktype']['config']['items'];
		if (is_array($doktype))	{
			reset($doktype);
			while(list($n,$setup) = each($doktype))	{
				if ($setup[1]!='--div--')	{
					$codeArr[$n][] = '<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/i/'.($PAGES_TYPES[$setup[1]]['icon'] ? $PAGES_TYPES[$setup[1]]['icon'] : $PAGES_TYPES['default']['icon']),'width="18" height="16"').' hspace="4" align="top">';
					$codeArr[$n][] = $LANG->sL($setup[0]).' ('.$setup[1].')';
					$codeArr[$n][] = intval($admin->recStat[doktype][$setup[1]]);
				}
			}
			$this->content.=$this->doc->section($LANG->getLL('doktype'),$this->doc->table($codeArr),0,1);
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

		$codeArr = Array();
		$countArr = $admin->countRecords($id_list);
		if (is_array($TCA))	{
			reset($TCA);
			while(list($t)=each($TCA))	{
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
					reset($admin->lRecords[$t]);
					while(list(,$data)=each($admin->lRecords[$t]))	{
						if (!t3lib_div::inList($admin->lostPagesList,$data[pid]))	{
							$lr.='<nobr><b><a href="index.php?SET[function]=records&fixLostRecords_table='.$t.'&fixLostRecords_uid='.$data[uid].'"><img src="'.$BACK_PATH.'gfx/required_h.gif" width="10" hspace="3" height="10" border="0" align="top" title="'.$LANG->getLL('fixLostRecord').'"></a>uid:'.$data[uid].', pid:'.$data[pid].', '.t3lib_div::fixed_lgd(strip_tags($data[title]),20).'</b></nobr><br>';
						} else {
							$lr.='<nobr><img src="'.$BACK_PATH.'clear.gif" width="16" height="1" border="0"><font color="Gray">uid:'.$data[uid].', pid:'.$data[pid].', '.t3lib_div::fixed_lgd(strip_tags($data[title]),20).'</font></nobr><br>';
						}
					}
				}
				$codeArr[$t][]=$lr;
			}
			$this->content.=$this->doc->section($LANG->getLL('tables'),$this->doc->table($codeArr),0,1);
		}
	}

	/**
	 * Show list references
	 *
	 * @return	void
	 */
	function func_relations()	{
		global $LANG,$BACK_PATH;

		$this->content.= $this->doc->header($LANG->getLL('relations'));
		$this->content.= $this->doc->spacer(5);
		$this->content.= $this->doc->section('',$this->menu);

		$admin = t3lib_div::makeInstance('t3lib_admin');
		$admin->genTree_makeHTML=0;
		$admin->backPath = $BACK_PATH;

		$fkey_arrays = $admin->getGroupFields('');
		$admin->selectNonEmptyRecordsWithFkeys($fkey_arrays);


		$fileTest = $admin->testFileRefs();

		$code='';
		if (is_array($fileTest['noReferences']))	{
			while(list(,$val)=each($fileTest['noReferences']))	{
				$code.='<nobr>'.$val[0].'/<b>'.$val[1].'</b></nobr><br>';
			}
		}
		$this->content.=$this->doc->section($LANG->getLL('files_no_ref'),$code,1,1);

		$code='';
		if (is_array($fileTest['moreReferences']))	{
			while(list(,$val)=each($fileTest['moreReferences']))	{
				$code.='<nobr>'.$val[0].'/<b>'.$val[1].'</b>: '.$val[2].' references:</nobr><br>'.$val[3].'<br><br>';
			}
		}
		$this->content.=$this->doc->section($LANG->getLL('files_many_ref'),$code,1,1);

		$code='';
		if (is_array($fileTest['noFile']))	{
			ksort($fileTest['noFile']);
			reset($fileTest['noFile']);
			while(list(,$val)=each($fileTest['noFile']))	{
				$code.='<nobr>'.$val[0].'/<b>'.$val[1].'</b> is missing! </nobr><br>Referenced from: '.$val[2].'<br><br>';
			}
		}
		$this->content.=$this->doc->section($LANG->getLL('files_no_file'),$code,1,1);
		$this->content.=$this->doc->section($LANG->getLL('select_db'),$admin->testDBRefs($admin->checkSelectDBRefs),1,1);
		$this->content.=$this->doc->section($LANG->getLL('group_db'),$admin->testDBRefs($admin->checkGroupDBRefs),1,1);
	}

	/**
	 * Searching for files with a specific pattern
	 *
	 * @return	Searching		for files
	 */
	function func_filesearch()	{
		global $LANG;

		$this->content.= $this->doc->header($LANG->getLL('relations'));
		$this->content.= $this->doc->spacer(5);
		$this->content.= $this->doc->section('',$this->menu);


		$pattern = t3lib_div::_GP('pattern');
		$pcontent = 'Enter regex pattern: <input type="text" name="pattern" value="'.htmlspecialchars($pattern?$pattern:$GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern']).'"> <input type="submit" name="Search">';
		$this->content.= $this->doc->section('Pattern',$pcontent,0,1);

		if (strcmp($pattern,''))	{
			$dirs = t3lib_div::get_dirs(PATH_site);
	#		debug($dirs);
			$lines=array();
			$depth=10;

			foreach ($dirs as $key => $value) {
				$matching_files=array();
				$info='';
				if (!t3lib_div::inList('typo3,typo3conf,tslib,media,t3lib',$value))	{
					$info = $this->findFile(PATH_site.$value.'/',$pattern,$matching_files,$depth);
				}
				if (is_array($info))	{
					$lines[]='<hr><b>'.$value.'/</b> being checked...';
					$lines[]='Dirs: '.$info[0];
					if ($info[2])	$lines[]='<span class="typo3-red">ERROR: Directories deeper than '.$depth.' levels</span>';
					$lines[]='Files: '.$info[1];
					$lines[]='Matching files:<br><nobr><span class="typo3-red">'.implode('<br>',$matching_files).'</span></nobr>';
				} else {
					$lines[]=$GLOBALS['TBE_TEMPLATE']->dfw('<hr><b>'.$value.'/</b> not checked.');
				}
			}

			$this->content.=$this->doc->section('Searching for filenames:',implode('<br>',$lines),0,1);
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
				if (eregi($pattern,basename($value)))	$matching_files[]=substr($value,strlen(PATH_site));
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

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/lowlevel/dbint/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/lowlevel/dbint/index.php']);
}









// Make instance:
$SOBE = t3lib_div::makeInstance('SC_mod_tools_dbint_index');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>