<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2003 Kasper Skårhøj (kasper@typo3.com)
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
 * This module is in fact a part of the "cms" extension found in sysext/cms/
 *
 * Notice: This module and Web>List (db_list.php) module has a special status since they
 * are NOT located in their actual module directories (fx. sysext/cms/layout/) but in the 
 * backend root directory. This has some historical and practical causes.
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 *
 */


unset($MCONF);
require ("sysext/cms/layout/conf.php");
require ("init.php");
require ("template.php");
include (TYPO3_MOD_PATH."locallang.php");
require_once (PATH_t3lib."class.t3lib_pagetree.php");
require_once (PATH_t3lib."class.t3lib_page.php");
require_once (PATH_t3lib."class.t3lib_recordlist.php");
require_once ("class.db_list.inc");
require_once ("class.db_layout.inc");
require_once (PATH_t3lib."class.t3lib_positionmap.php");
$BE_USER->modAccess($MCONF,1);

// Will open up records locked by current user. It's assumed that the locking should end if this script is hit.
t3lib_BEfunc::lockRecords();

// Exits if "cms" extension is not loaded:
t3lib_extMgm::isLoaded("cms",1);


// ***************************
// Script Classes
// ***************************
class ext_posMap extends t3lib_positionMap {
	var $dontPrintPageInsertIcons = 1;
	var $l_insertNewRecordHere="newContentElement";
	
	function wrapRecordTitle($str,$row)	{
		return '<a href="#" onClick="jumpToUrl(\''.$GLOBALS["SOBE"]->local_linkThisScript(array("edit_record"=>"tt_content:".$row["uid"])).'\');return false;">'.$str.'</a>';
	}
	function wrapColumnHeader($str,$vv)	{
		return '<a href="#" onClick="jumpToUrl(\''.$GLOBALS["SOBE"]->local_linkThisScript(array("edit_record"=>"_EDIT_COL:".$vv)).'\');return false;">'.$str.'</a>';
	}
	function onClickInsertRecord($row,$vv,$moveUid,$pid) {
		$table="tt_content";
		if (is_array($row))	{
			$location=$GLOBALS["SOBE"]->local_linkThisScript(array("edit_record"=>"tt_content:new/-".$row["uid"]."/".$row["colPos"]));
		} else {
			$location=$GLOBALS["SOBE"]->local_linkThisScript(array("edit_record"=>"tt_content:new/".$pid."/".$vv));
		}
		return 'jumpToUrl(\''.$location.'\');return false;';
	}
	function wrapRecordHeader($str,$row)	{
		if ($row["uid"]==$this->moveUid)	{
//					return '<table border=0 cellpadding=0 cellspacing=0 width="100%"><tr bgColor="'.$GLOBALS["SOBE"]->doc->bgColor2.'"><td>'.$str.'</td></tr></table>';
			return '<img src="gfx/content_client.gif" width="7" height="10" vspace=2 border="0" alt="" align=top>'.$str;
		} else return $str;
	}
}
class SC_db_layout {
	var $MCONF=array();
	var $MOD_MENU=array();
	var $MOD_SETTINGS=array();

	var $include_once=array();
	
	var $content;
	var $perms_clause;
	var $pageinfo;
	var $descrTable;
	var $modTSconfig;
	var $topFuncMenu;
	var $editIcon;
	var $colPosList;
	var $EDIT_CONTENT;
	var $CALC_PERMS;
	var $pointer;
	var $imagemode;
	var $id;
	var $doc;	
	var $current_sys_language;

	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;
		$this->MCONF = $GLOBALS["MCONF"];

			// Init:
		$this->imagemode = t3lib_div::GPvar("imagemode");
		$this->pointer = t3lib_div::GPvar("pointer");
		$this->id = intval(t3lib_div::GPvar("id"));

		$this->perms_clause = $BE_USER->getPagePermsClause(1);
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);

			// Menu Configuration
		$this->menuConfig();

 		$this->current_sys_language=intval($this->MOD_SETTINGS["language"]);
 		
			// Include scripts:
		if ($this->MOD_SETTINGS["function"]==0)	{		// QuickEdit
			$this->include_once[]=PATH_t3lib."class.t3lib_tceforms.php";
			$this->include_once[]=PATH_t3lib."class.t3lib_loaddbgroup.php";
			$this->include_once[]=PATH_t3lib."class.t3lib_transferdata.php";
		}		
		if (t3lib_div::GPvar("clear_cache"))	{
			$this->include_once[]=PATH_t3lib."class.t3lib_tcemain.php";
		}

			// Descriptions:
		$this->descrTable = "_MOD_".$this->MCONF["name"];
		if ($BE_USER->uc["edit_showFieldHelp"])	{
			$LANG->loadSingleTableDescription($this->descrTable);
		}
	}
	function menuConfig()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;
			// MENU-ITEMS:
			// If array, then it's a selector box menu
			// If empty string it's just a variable, that'll be saved. 
			// Values NOT in this array will not be saved in the settings-array for the module.
		$this->MOD_MENU = array(
			"tt_board" => array(
				0 => $LANG->getLL("m_tt_board_0"),
				"expand" => $LANG->getLL("m_tt_board_expand")
			),
			"tt_address" => array(
				0 => $LANG->getLL("m_tt_address_0"),
				1 => $LANG->getLL("m_tt_address_1"),
				2 => $LANG->getLL("m_tt_address_2")
			),
			"tt_links" => array(
				0 => $LANG->getLL("m_default"),
				1 => $LANG->getLL("m_tt_links_1"),
				2 => $LANG->getLL("m_tt_links_2")
			),
			"tt_calender" => array (
				0 => $LANG->getLL("m_default"),
				"date" => $LANG->getLL("m_tt_calender_date"),
				"date_ext" => $LANG->getLL("m_tt_calender_date_ext"),
				"todo" => $LANG->getLL("m_tt_calender_todo"),
				"todo_ext" => $LANG->getLL("m_tt_calender_todo_ext")
			),
			"tt_products" => array (
				0 => $LANG->getLL("m_default"),
				"ext" => $LANG->getLL("m_tt_products_ext")
			),
			"tt_content_showHidden" => "",
			"showPalettes" => "",
			"showDescriptions" => "",
			"disableRTE" => "",
			"function" => array(
				1 => $LANG->getLL("m_function_1"),
				0 => $LANG->getLL("m_function_0"),
				2 => $LANG->getLL("m_function_2"),
				3 => $LANG->getLL("pageInformation")
			),
			"language" => array(
				0 => $LANG->getLL("m_default")
			)
		);
		
		
		 // First, select all pages_language_overlay records on the current page. Each represents a possibility for a language on the page.
		$query = $this->languageQuery($this->id);
		$res = mysql(TYPO3_db,$query);
		echo mysql_error();
		while($lrow=mysql_fetch_assoc($res))	{
			$this->MOD_MENU["language"][$lrow["uid"]]=($lrow["hidden"]?"(".$lrow["title"].")":$lrow["title"]);
		}
		
		// Find if there are ANY languages at all.
		$query = "SELECT uid FROM sys_language".($BE_USER->isAdmin()?"":" WHERE hidden=0");
		$res = mysql(TYPO3_db,$query);
		if (!mysql_num_rows($res))	{
			unset($this->MOD_MENU["function"]["2"]);
		}
		
			// page/be_user TSconfig settings and blinding of menu-items
		$this->modTSconfig = t3lib_BEfunc::getModTSconfig($this->id,"mod.".$this->MCONF["name"]);
		if ($this->modTSconfig["properties"]["QEisDefault"])	ksort($this->MOD_MENU["function"]);
		$this->MOD_MENU["function"] = t3lib_BEfunc::unsetMenuItems($this->modTSconfig["properties"],$this->MOD_MENU["function"],"menu.function");
		
			// Remove QuickEdit as option if page type is not...
		if (!t3lib_div::inList($GLOBALS["TYPO3_CONF_VARS"]["FE"]["content_doktypes"].",6",$this->pageinfo["doktype"]))	{
			unset($this->MOD_MENU["function"][0]);
		}
		
			// CLEANSE SETTINGS
		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::GPvar("SET"), $this->MCONF["name"]);
	}
	function clearCache()	{
		if (t3lib_div::GPvar("clear_cache"))	{
			$tce = t3lib_div::makeInstance("t3lib_TCEmain");
			$tce->start(Array(),Array());
			$tce->clear_cacheCmd($this->id);
		}
	}
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;

		// Access check...
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$access = is_array($this->pageinfo) ? 1 : 0;
		if ($this->id && $access)	{
			$this->CALC_PERMS = $BE_USER->calcPerms($this->pageinfo);
			$this->EDIT_CONTENT = ($this->CALC_PERMS&16) ? 1 : 0;
		
			
			$this->doc = t3lib_div::makeInstance("mediumDoc");
			$this->doc->backPath = $BACK_PATH;
		
					// JavaScript
			$this->doc->JScode = '
			<script language="javascript" type="text/javascript" src="'.$BACK_PATH.'t3lib/jsfunc.updateform.js"></script>
			<script language="javascript" type="text/javascript">
				if (top.fsMod) top.fsMod.recentIds["web"] = '.intval($this->id).';
				function jumpToUrl(URL,formEl)	{
					if (document.editform && document.TBE_EDITOR_isFormChanged)	{	// Check if the function exists... (works in all browsers?)
						if (!TBE_EDITOR_isFormChanged())	{
							document.location = URL;
						} else if (formEl) {
							if (formEl.type=="checkbox") formEl.checked = formEl.checked ? 0 : 1;
						}
					} else document.location = URL;
				}
			'.(t3lib_div::GPVar("popView") ? t3lib_BEfunc::viewOnClick($this->id,"",t3lib_BEfunc::BEgetRootLine($this->id)) : '').'
			
				function deleteRecord(table,id,url)	{
					if (confirm('.$GLOBALS['LANG']->JScharCode($LANG->getLL("deleteWarning")).'))	{	
						document.location = "'.$BACK_PATH.'tce_db.php?cmd["+table+"]["+id+"][delete]=1&redirect="+escape(url)+"&vC='.$BE_USER->veriCode().'&prErr=1&uPT=1";
					}
					return false;
				}
			</script>
			';
		
				// Setting doc-header
			$this->doc->form='<form action="db_layout.php?id='.$this->id.'&imagemode='.$this->imagemode.'" method="POST">';
		
			$this->topFuncMenu = t3lib_BEfunc::getFuncMenu($this->id,"SET[function]",$this->MOD_SETTINGS["function"],$this->MOD_MENU["function"],"db_layout.php","").
						(count($this->MOD_MENU["language"])>1 ? "<BR>".t3lib_BEfunc::getFuncMenu($this->id,"SET[language]",$this->current_sys_language,$this->MOD_MENU["language"],"db_layout.php","") : "");	
			
			
			
			
			
			
			if ($this->CALC_PERMS&2)	{
				$params="&edit[pages][".$this->id."]=edit";
				$this->editIcon='<A HREF="#" onClick="'.t3lib_BEfunc::editOnClick($params).'"><img src="'.$BACK_PATH.'gfx/edit2.gif" width=11 height=12 vspace=2 border=0'.t3lib_BEfunc::titleAttrib($GLOBALS["LANG"]->getLL("edit"),1).' align="top"></a>';
			} else {
		//		$this->editIcon=$dblist->noEditIcon("noEditPage");
				$this->editIcon="";
			}
			
			
				// Find columns
			$modTSconfig_SHARED = t3lib_BEfunc::getModTSconfig($this->id,"mod.SHARED");		// SHARED page-TSconfig settings.
			$this->colPosList = strcmp(trim($this->modTSconfig["properties"]["tt_content."]["colPos_list"]),"") ? trim($this->modTSconfig["properties"]["tt_content."]["colPos_list"]) : $modTSconfig_SHARED["properties"]["colPos_list"];
			$this->colPosList = strcmp($this->colPosList,"")?$this->colPosList:"1,0,2,3";
			
			if ($this->MOD_SETTINGS["function"]==0)	{		// QuickEdit
				$this->content.=$this->quickEdit();
			} else {
				// *******************
				// Make DB list
				// *******************
		//		$this->modTSconfig = t3lib_BEfunc::getModTSconfig($this->id,"mod.".$this->MCONF["name"]);		// page-TSconfig setting for this module.
			
				$dblist = t3lib_div::makeInstance("recordList_layout");
				$dblist->backPath = $BACK_PATH;
				$dblist->thumbs = $this->imagemode;
				$dblist->no_noWrap=1;
				
				$this->pointer = t3lib_div::intInRange($this->pointer,0,100000);
				$dblist->headLineCol = $this->doc->bgColor2;
				$dblist->script = "db_layout.php";
				$dblist->showIcon = 0;
				$dblist->setLMargin=0;
				$dblist->doEdit = $this->CALC_PERMS&16 ? 1 : 0;
				$dblist->agePrefixes=$GLOBALS["LANG"]->sL("LLL:EXT:lang/locallang_core.php:labels.minutesHoursDaysYears");
				$dblist->id=$this->id;
				$dblist->nextThree = t3lib_div::intInRange($this->modTSconfig["properties"]["editFieldsAtATime"],0,10);
				$dblist->option_showBigButtons = $this->modTSconfig["properties"]["disableBigButtons"] ? 0 : 1;
				$dblist->option_newWizard = $this->modTSconfig["properties"]["disableNewContentElementWizard"] ? 0 : 1;
				if (!$dblist->nextThree)	$dblist->nextThree= 1;
				

					// Preparing
				$h_menu=$dblist->getTableMenu($this->id);
				$h_func="";
				$optionArr=array();
		
				$tableOutput=array();
				$tableJSOutput=array();
				$CMcounter = 0;
				reset($dblist->activeTables);
				while(list($table)=each($dblist->activeTables))	{
					t3lib_div::loadTCA($table);
						// Creating special conditions for each table:	
					switch($table)	{
						case "tt_board":
							$h_func = t3lib_BEfunc::getFuncMenu($this->id,"SET[tt_board]",$this->MOD_SETTINGS["tt_board"],$this->MOD_MENU["tt_board"],"db_layout.php","");
						break;
						case "tt_address":
							$h_func = t3lib_BEfunc::getFuncMenu($this->id,"SET[tt_address]",$this->MOD_SETTINGS["tt_address"],$this->MOD_MENU["tt_address"],"db_layout.php","");
						break;
						case "tt_links":
							$h_func = t3lib_BEfunc::getFuncMenu($this->id,"SET[tt_links]",$this->MOD_SETTINGS["tt_links"],$this->MOD_MENU["tt_links"],"db_layout.php","");
						break;
						case "tt_calender":
							$h_func = t3lib_BEfunc::getFuncMenu($this->id,"SET[tt_calender]",$this->MOD_SETTINGS["tt_calender"],$this->MOD_MENU["tt_calender"],"db_layout.php","");
						break;
						case "tt_products":
							$h_func = t3lib_BEfunc::getFuncMenu($this->id,"SET[tt_products]",$this->MOD_SETTINGS["tt_products"],$this->MOD_MENU["tt_products"],"db_layout.php","");
						break;
						case "tt_guest":
						case "tt_news":
						case "fe_users":
							// Nothing
						break;
						case "tt_content":
							$q_count = $this->getNumberOfHiddenElements();
							$h_func_b= t3lib_BEfunc::getFuncCheck($this->id,"SET[tt_content_showHidden]",$this->MOD_SETTINGS["tt_content_showHidden"],"db_layout.php","").(!$q_count?$GLOBALS["TBE_TEMPLATE"]->dfw($LANG->getLL("hiddenCE")):$LANG->getLL("hiddenCE")." (".$q_count.")");
			
							$dblist->tt_contentConfig["showCommands"] = 1;	// Boolean: Display up/down arrows and edit icons for tt_content records 
							$dblist->tt_contentConfig["showInfo"] = 1;		// Boolean: Display info-marks or not
							$dblist->tt_contentConfig["single"] =0; 		// Boolean: If set, the content of column(s) $this->tt_contentConfig["showSingleCol"] is shown in the total width of the page
			
							if (is_array($TCA["tt_content"]["columns"]["colPos"]["config"]["items"]))	{
								$colList=array();
								reset($TCA["tt_content"]["columns"]["colPos"]["config"]["items"]);
								while(list(,$temp)=each($TCA["tt_content"]["columns"]["colPos"]["config"]["items"]))	{
									$colList[]=$temp[1];
								}
							} else {	// ... should be impossible that colPos has no array. But this is the fallback should it make any sense:
								$colList=array("1","0","2","3");
							}
							if (strcmp($this->colPosList,""))	{
								$colList=array_intersect(t3lib_div::intExplode(",",$this->colPosList),$colList);
							}
								// If only one column found, display the single-column view.
							if (count($colList)==1)	{
								$dblist->tt_contentConfig["single"] =1;	// Boolean: If set, the content of column(s) $this->tt_contentConfig["showSingleCol"] is shown in the total width of the page
								$dblist->tt_contentConfig["showSingleCol"]=current($colList);	// The column(s) to show if single mode (under each other)
							}
							$dblist->tt_contentConfig["cols"]= implode(",",$colList);		// The order of the rows: Default is left(1), Normal(0), right(2), margin(3)
							$dblist->tt_contentConfig["showHidden"]=$this->MOD_SETTINGS["tt_content_showHidden"];
							$dblist->tt_contentConfig["sys_language_uid"] = intval($this->current_sys_language);
							
							if ($this->MOD_SETTINGS["function"]==2)	{	// LANGUAGE 
								$dblist->tt_contentConfig["single"]=0;
								$dblist->tt_contentConfig["languageMode"]=1;
								$dblist->tt_contentConfig["languageCols"] = $this->MOD_MENU["language"];
								$dblist->tt_contentConfig["languageColsPointer"] = $this->current_sys_language;
							}
						break;
					}
			
					$dblist->start($this->id,$table,$this->pointer,t3lib_div::GPvar("search_field"),t3lib_div::GPvar("search_levels"),t3lib_div::GPvar("showLimit"));
					$dblist->counter=$CMcounter;
					$dblist->ext_function = $this->MOD_SETTINGS["function"];
					$dblist->generateList();
			
					$tableOutput[$table]=($h_func?$h_func."<BR><img src=clear.gif width=1 height=4><BR>":"").$dblist->HTMLcode.($h_func_b?"<img src=clear.gif width=1 height=10><BR>".$h_func_b:"");		//."<HR>".
					$tableJSOutput[$table]=$dblist->JScode;
					$CMcounter+=$dblist->counter;
													
					$dblist->HTMLcode="";
					$dblist->JScode="";
					$h_func="";
					$h_func_b="";
				}
		
		
		
				$CMparts=$this->doc->getContextMenuCode();
				$this->doc->bodyTagAdditions = $CMparts[1];
				$this->doc->JScode.=$CMparts[0];
				$this->doc->postCode.= $CMparts[2];
			
				// ******************
				// Draw the header.
				// ******************
				$headerSection = $this->doc->getHeader("pages",$this->pageinfo,$this->pageinfo["_thePath"]).'<br>'.$LANG->sL("LLL:EXT:lang/locallang_core.php:labels.path").': '.t3lib_div::fixed_lgd_pre($this->pageinfo["_thePath"],50);
		
					$toolBar='';		
					$toolBar.='<a href="#" onClick="jumpToUrl(\'show_rechis.php?element='.rawurlencode('pages:'.$this->id).'&returnUrl='.rawurlencode(t3lib_div::getIndpEnv("REQUEST_URI")).'#latest\');return false;"><img src="gfx/history2.gif" width="13" height="12" vspace=2 hspace=2 border="0"'.t3lib_BEfunc::titleAttrib($LANG->getLL("recordHistory"),1).' align=top></a>';
					$toolBar.='<A HREF="db_new_content_el.php?id='.$this->id.'&sys_language_uid='.$this->current_sys_language.'&returnUrl='.rawurlencode(t3lib_div::getIndpEnv("REQUEST_URI")).'"><img src="'.$BACK_PATH.'gfx/new_record.gif" vspace=2 hspace=1 width=16 height=12 border=0 align=top align="top"'.t3lib_BEfunc::titleAttrib($LANG->getLL("newContentElement")).'></a>';
					$toolBar.='<A HREF="move_el.php?table=pages&uid='.$this->id.'&returnUrl='.rawurlencode(t3lib_div::getIndpEnv("REQUEST_URI")).'"><img src="'.$BACK_PATH.'gfx/move_page.gif" vspace=2 hspace=2 width=11 height=12 border=0 align=top align="top"'.t3lib_BEfunc::titleAttrib($LANG->getLL("move_page")).'></a>';
					$toolBar.='<a href="#" onClick="jumpToUrl(\'db_new.php?id='.$this->id.'&pagesOnly=1&returnUrl='.rawurlencode(t3lib_div::getIndpEnv("REQUEST_URI")).'\');return false;"><img src="gfx/new_page.gif" width="13" height="12" hspace=0 vspace=2 border="0"'.t3lib_BEfunc::titleAttrib($LANG->getLL("newPage"),1).' align=top></a>';
		
					$params="&edit[pages][".$this->id."]=edit";
					$toolBar.='<a href="#" onClick="'.t3lib_BEfunc::editOnClick($params).'"><img src="gfx/edit2.gif" width="11" height="12" hspace=2 vspace=2 border="0"'.t3lib_BEfunc::titleAttrib($LANG->getLL("editPageHeader"),1).' align=top></a>';
		
					$hT = trim(t3lib_BEfunc::helpText($this->descrTable,"columns",$GLOBALS["BACK_PATH"]));
					$toolBar.=$hT?$hT."<BR>":t3lib_BEfunc::helpTextIcon($this->descrTable,"columns",$GLOBALS["BACK_PATH"]);
				$headerSection.='<table border=0 cellpadding=0 cellspacing=0 bgColor="'.$this->doc->bgColor4.'"><tr><td>'.$toolBar.'</TD></tr></table>';
			
				// ******************
				// Link menu, if more than one table.
				// ******************
				if ($this->MOD_SETTINGS["function"]!=3 && count($tableOutput)>1)	{
					$goToTable_menu = '<td valign=top width=1% nowrap>'.$h_menu.'</td>';
				} else {
					$goToTable_menu = '';
				}
				$hS2='<table border=0 cellpadding=0 cellspacing=0 width=100%>
					<tr>
						<td valign=top width=99%>'.$headerSection.'</td>
						'.$goToTable_menu.'
						<td valign=top width=1% valign=top>'.$this->topFuncMenu.'</td>
						<td valign=top align=right width=1%><img src=clear.gif width=1 height=3><BR>'.$this->editIcon.'</td>
					</tr>
				</table>';
			
				$this->content.=$this->doc->startPage($LANG->getLL("title"));
				$this->content.=$this->doc->section('',$hS2);
				
				
				if ($this->MOD_SETTINGS["function"]==3) {
					
						// ********************
						// Making page info:
						// ********************
						$this->content.=$this->doc->spacer(10);
						$this->content.=$this->doc->section($GLOBALS["LANG"]->getLL("pageInformation"),$dblist->getPageInfoBox($this->pageinfo,$this->CALC_PERMS&2),0,1);
				} else {
				
					// ******************
					// Draw Content
					// ******************
					reset($tableOutput);
					while(list($table,$output)=each($tableOutput))	{	
						$this->content.=$this->doc->section('<a name="'.$table.'"></a>'.$dblist->activeTables[$table],$output,1,1);
						$this->content.=$this->doc->spacer(15);
						$this->content.=$this->doc->sectionEnd();
					}
				
					// ********************
					// Making search form:
					// ********************
					if (!$this->modTSconfig["properties"]["disableSearchBox"] && count($tableOutput))	{	
					//	debug(array($dblist->getSearchBox(0)));
						$this->content.=$this->doc->section($GLOBALS["LANG"]->sL("LLL:EXT:lang/locallang_core.php:labels.search"),$dblist->getSearchBox(),0,1);
					}
					
					// ********************
					// Sys notes:
					// ********************
					$dblist->id=$this->id;
					$sysNotes = $dblist->showSysNotesForPage();
					if ($sysNotes)	{
						$this->content.=$this->doc->spacer(10);
						$this->content.=$this->doc->section($LANG->getLL("internalNotes"),$sysNotes,0,1);
					}
		
		
					// Advanced.
					if (!$this->modTSconfig["properties"]["disableAdvanced"])	{
						$af_content = $this->doc->clearCacheMenu($this->id);
			
						if (!$this->modTSconfig["properties"]["noCreateRecordsLink"]) {
							$af_content.='<BR><BR><a href="db_new.php?id='.$this->id.'&returnUrl='.rawurlencode(t3lib_div::getIndpEnv("REQUEST_URI")).'"><img src="gfx/new_el.gif" width="11" height="12" hspace=4 border="0" align=top><strong>'.$LANG->getLL("newRecordGeneral").'</strong></a><BR><BR>';
						}
			
						$this->content.=$this->doc->spacer(10);
						$this->content.=$this->doc->section($LANG->getLL("advancedFunctions"),$af_content,0,1);
					}
					$this->content.=$this->doc->spacer(10);
				}
			}
		
		
			// ShortCut
			if ($BE_USER->mayMakeShortcut())	{
				$this->content.=$this->doc->spacer(20).$this->doc->section('',$this->doc->makeShortcutIcon("id,edit_record,pointer,new_unique_uid,search_field,search_levels,showLimit",implode(",",array_keys($this->MOD_MENU)),$this->MCONF["name"]));
			}
		
			$this->content.=$this->doc->spacer(10);
			$this->content.=$this->doc->endPage();
		} else {
			$this->doc = t3lib_div::makeInstance("mediumDoc");
			$this->doc->backPath = $BACK_PATH;
			$this->doc->JScode = '
			<script language="javascript" type="text/javascript">
				if (top.fsMod) top.fsMod.recentIds["web"] = '.intval($this->id).';
			</script>
			';
			$this->content=$this->doc->startPage($LANG->getLL("title"));
			$this->content.=$this->doc->section($LANG->getLL("clickAPage_header"),$LANG->getLL("clickAPage_content"),0,1);
			$this->content.=$this->doc->endPage();
		}
	}
	function printContent()	{
		echo $this->content;
	}


	function getNumberOfHiddenElements()	{
		$q_hidden = "SELECT count(*) FROM tt_content WHERE pid=".intval($this->id)." AND sys_language_uid=".intval($this->current_sys_language).t3lib_BEfunc::BEenableFields("tt_content",1).t3lib_BEfunc::deleteClause("tt_content");
		$q_res = mysql(TYPO3_db,$q_hidden);
		list($q_count) = mysql_fetch_row($q_res);
		return $q_count;
	}
	function local_linkThisScript ($params)	{
		$params["popView"]="";
		$params["new_unique_uid"]="";
		return t3lib_div::linkThisScript($params);
	}
	function languageQuery($id)	{
		$exQ = $GLOBALS["BE_USER"]->isAdmin() ? '' : 'AND sys_language.hidden=0';
		if ($id)	{
			$query = "SELECT sys_language.* FROM pages_language_overlay,sys_language 
						WHERE pages_language_overlay.sys_language_uid=sys_language.uid 
						AND pages_language_overlay.pid=".intval($id)."
						".$exQ."
						GROUP BY pages_language_overlay.sys_language_uid
						ORDER BY sys_language.title";
		} else {
			$query = "SELECT sys_language.* FROM sys_language 
						WHERE sys_language.hidden=0
						ORDER BY sys_language.title";
		}
		return $query;
	}
	function quickEdit()	{
		global $SOBE,$LANG,$BE_USER;
		
		$this->doc->form='<form action="tce_db.php?&prErr=1&uPT=1" method="POST" enctype="'.$GLOBALS["TYPO3_CONF_VARS"]["SYS"]["form_enctype"].'" name="editform" onSubmit="return TBE_EDITOR_checkSubmit(1);">';
		$edit_record = t3lib_div::GPvar("edit_record");
		
		if (substr($edit_record,0,9)=="_EDIT_COL")	{
			$query = "SELECT * FROM tt_content WHERE pid=".intval($this->id).
				" AND colPos=".intval(substr($edit_record,10)).
				" AND sys_language_uid=".intval($this->current_sys_language).
				($this->MOD_SETTINGS["tt_content_showHidden"] ? "" : t3lib_BEfunc::BEenableFields("tt_content")).
				t3lib_Befunc::deleteClause("tt_content").
				" ORDER BY sorting";
			$res = mysql(TYPO3_db,$query);
			$idListA=array();
			while($cRow=mysql_fetch_assoc($res))	{
				$idListA[]=$cRow["uid"];
			}
	//		debug($idListA);
			$jumpUrl = 'alt_doc.php?edit[tt_content]['.implode(",",$idListA).']=edit&returnUrl='.rawurlencode($this->local_linkThisScript(array("edit_record"=>"")));
			header("Location: ".t3lib_div::locationHeaderUrl($jumpUrl));
			exit;
		}
		
		if (t3lib_div::GPvar("new_unique_uid"))	{
			$query = "SELECT * FROM sys_log WHERE userid='".$BE_USER->user["uid"]."' AND NEWid='".t3lib_div::GPvar("new_unique_uid")."'";
			$res = mysql(TYPO3_db,$query);
			$sys_log_row = mysql_fetch_assoc($res);
			if (is_array($sys_log_row))	{
				$edit_record=$sys_log_row["tablename"].":".$sys_log_row["recuid"];
			}
			unset($HTTP_GET_VARS["new_unique_uid"]);	// removing this for certain so 
		}
		
			// Creating tool bar
		$opt=array();
		$is_selected=0;
		$languageOverlayRecord="";
		if ($this->current_sys_language)	{
			list($languageOverlayRecord) = t3lib_BEfunc::getRecordsByField("pages_language_overlay","pid",$this->id,"AND sys_language_uid=".intval($this->current_sys_language));
		}
		if (is_array($languageOverlayRecord))	{
			$inValue = 'pages_language_overlay:'.$languageOverlayRecord["uid"];
			$is_selected+=intval($edit_record==$inValue);
			$opt[]='<option value="'.$inValue.'"'.($edit_record==$inValue?" selected":"").'>[ '.$LANG->getLL("editLanguageHeader").' ]</option>';
		} else {
			$inValue = 'pages:'.$this->id;
			$is_selected+=intval($edit_record==$inValue);
			$opt[]='<option value="'.$inValue.'"'.($edit_record==$inValue?" selected":"").'>[ '.$LANG->getLL("editPageHeader").' ]</option>';
		}
	
	//	$andH=" AND hidden=0";
		$andH="";
		$query = "SELECT * FROM tt_content WHERE pid=".intval($this->id).
			$andH.
			" AND sys_language_uid=".intval($this->current_sys_language).
			($this->MOD_SETTINGS["tt_content_showHidden"] ? "" : t3lib_BEfunc::BEenableFields("tt_content")).
			t3lib_Befunc::deleteClause("tt_content").
			" ORDER BY colPos,sorting";
		$res = mysql(TYPO3_db,$query);
		$colPos="";
		$first=1;
		$prev=$this->id;	// Page is the pid if no record to put this after.
		while($cRow=mysql_fetch_assoc($res))	{
			if ($first)	{
				if (!$edit_record)	{
					$edit_record="tt_content:".$cRow["uid"];
				}
				$first = 0;
			}
			if (strcmp($cRow["colPos"],$colPos))	{
				if (strcmp($colPos,""))	{
	//				$inValue = 'tt_content:new/'.$prev."/".$colPos;
	//		$is_selected+=intval($edit_record==$inValue);
	//				$opt[]='<option value="'.$inValue.'"'.($edit_record==$inValue?" selected":"").'>[ '.$LANG->getLL("newLabel").' ]</option>';
				}
				$colPos=$cRow["colPos"];
				$opt[]='<option value=""></option>';
				$opt[]='<option value="_EDIT_COL:'.$colPos.'">__'.$LANG->sL(t3lib_BEfunc::getLabelFromItemlist("tt_content","colPos",$colPos)).':__</option>';
			}
			$inValue = 'tt_content:'.$cRow["uid"];
			$is_selected+=intval($edit_record==$inValue);
			$opt[]='<option value="'.$inValue.'"'.($edit_record==$inValue?" selected":"").'>'.htmlspecialchars(t3lib_div::fixed_lgd($cRow["header"]?$cRow["header"]:"[".$GLOBALS["LANG"]->sL("LLL:EXT:lang/locallang_core.php:labels.no_title")."] ".strip_tags($cRow["bodytext"]),$GLOBALS["BE_USER"]->uc["titleLen"])).'</option>';
			$prev=-$cRow["uid"];
		}
	
		if (!$edit_record)	{
			$edit_record="tt_content:new/".$prev."/".$colPos;
	
				// Formerly outside this condition...
			$inValue = 'tt_content:new/'.$prev."/".$colPos;
			$is_selected+=intval($edit_record==$inValue);
			$opt[]='<option value="'.$inValue.'"'.($edit_record==$inValue?" selected":"").'>[ '.$LANG->getLL("newLabel").' ]</option>';
		}
	
		if (!$is_selected)	{	// If none is yet selected...
			$opt[]='<option value=""></option>';
			$opt[]='<option value="'.$edit_record.'" SELECTED>[ '.$LANG->getLL("newLabel").' ]</option>';
		}
		
		
		$eRParts = explode(":",$edit_record);
	//	debug($eRParts);
		$deleteButton = (t3lib_div::testInt($eRParts[1]) && $edit_record && (($eRParts[0]!="pages"&&$this->EDIT_CONTENT) || ($eRParts[0]=="pages"&&($this->CALC_PERMS&4))));
	
		$undoButton=0;
		$undoQuery="SELECT tstamp FROM sys_history WHERE tablename='".$eRParts[0]."' AND recuid='".$eRParts[1]."' ORDER BY tstamp DESC LIMIT 1";
		$undoRes = mysql(TYPO3_db,$undoQuery);
		if ($undoButtonR = mysql_fetch_assoc($undoRes))	{
			$undoButton=1;
		}
	
		$elementName="edit_record";
		$addparams="";
	
	
		$R_URL_parts = parse_url(t3lib_div::getIndpEnv("REQUEST_URI"));
		$R_URL_getvars = $GLOBALS["HTTP_GET_VARS"];
	
		unset($R_URL_getvars["popView"]);
		unset($R_URL_getvars["new_unique_uid"]);
		$R_URL_getvars["edit_record"]=$edit_record;
		$R_URI = $R_URL_parts["path"]."?".t3lib_div::implodeArrayForUrl("",$R_URL_getvars);

	
		$closeUrl = $this->local_linkThisScript(array("SET"=>array("function"=>1)));	// Goes to "Columns" view if close is pressed (default)
		
		if ($BE_USER->uc["condensedMode"])	{
			$uParts = parse_url(t3lib_div::getIndpEnv("REQUEST_URI"));
			$closeUrl="alt_db_navframe.php";
		}
		if (t3lib_div::GPvar("returnUrl"))	{
			$closeUrl = t3lib_div::GPvar("returnUrl");
		}
	
		$retUrlStr = t3lib_div::GPvar("returnUrl")?"+'&returnUrl='+'".rawurlencode(t3lib_div::GPvar("returnUrl"))."'":"";
		$toolBar='<select name="edit_record" onChange="jumpToUrl(\'db_layout.php?id='.$this->id.$addparams.'&'.$elementName.'=\'+escape(this.options[this.selectedIndex].value,this)'.$retUrlStr.');">'.implode("",$opt).'</select>'.
			'<input type="image" border=0 name="savedok" src="gfx/savedok.gif" hspace=2 width="21" height="16"'.t3lib_BEfunc::titleAttrib($LANG->sL("LLL:EXT:lang/locallang_core.php:rm.saveDoc"),1).' align=top>'.
			'<a href="#" onClick="document.editform.redirect.value+=\'&popView=1\'; TBE_EDITOR_checkAndDoSubmit(1); return false;"><img border=0 src="gfx/savedokshow.gif" hspace=2 width="21" height="16"'.t3lib_BEfunc::titleAttrib($LANG->sL("LLL:EXT:lang/locallang_core.php:rm.saveDocShow"),1).' align=top></a>'.
			'<a href="#" onClick="jumpToUrl(unescape(\''.rawurlencode($closeUrl).'\')); return false;"><img border=0 src="gfx/closedok.gif" hspace=2 width="21" height="16"'.t3lib_BEfunc::titleAttrib($LANG->sL("LLL:EXT:lang/locallang_core.php:rm.closeDoc"),1).' align=top></a>'.
			($deleteButton ? '<a href="#" onClick="return deleteRecord(\''.$eRParts[0].'\',\''.$eRParts[1].'\',\'db_layout.php?id='.$this->id.'\');"><img border=0 src="gfx/deletedok.gif" hspace=2 width="21" height="16"'.t3lib_BEfunc::titleAttrib($LANG->getLL("deleteItem"),1).' align=top></a>' : '').
			($undoButton ? '<a href="#" onClick="document.location=\'show_rechis.php?element='.rawurlencode($eRParts[0].':'.$eRParts[1]).'&revert=ALL_FIELDS&sumUp=-1&returnUrl='.rawurlencode($R_URI).'\'; return false;"><img border=0 src="gfx/undo.gif" hspace=2 width="21" height="16"'.t3lib_BEfunc::titleAttrib(sprintf($LANG->getLL("undoLastChange"),t3lib_BEfunc::calcAge(time()-$undoButtonR["tstamp"],$LANG->sL("LLL:EXT:lang/locallang_core.php:labels.minutesHoursDaysYears"))),1).'" align=top></a>' : '').
			'';
		$toolBar.='<img src=clear.gif width=15 height=1 align=top>';
		$toolBar.=$undoButton?'<a href="#" onClick="jumpToUrl(\'show_rechis.php?element='.rawurlencode($eRParts[0].':'.$eRParts[1]).'&returnUrl='.rawurlencode($R_URI).'#latest\');return false;"><img src="gfx/history2.gif" width="13" height="12" vspace=2 hspace=2 border="0"'.t3lib_BEfunc::titleAttrib($LANG->getLL("recordHistory"),1).' align=top></a>':'';
		$toolBar.='<A HREF="db_new_content_el.php?id='.$this->id.'&sys_language_uid='.$this->current_sys_language.'&returnUrl='.rawurlencode(t3lib_div::getIndpEnv("REQUEST_URI")).'"><img src="'.$BACK_PATH.'gfx/new_record.gif" vspace=2 hspace=1 width=16 height=12 border=0 align=top align="top"'.t3lib_BEfunc::titleAttrib($LANG->getLL("newContentElement")).'></a>';
		if (t3lib_div::testInt($eRParts[1])) $toolBar.='<A HREF="move_el.php?table='.$eRParts[0].'&uid='.$eRParts[1].'&returnUrl='.rawurlencode(t3lib_div::getIndpEnv("REQUEST_URI")).'"><img src="'.$BACK_PATH.'gfx/move_'.($eRParts[0]=="tt_content"?"record":"page").'.gif" vspace=2 hspace=2 width=11 height=12 border=0 align=top align="top"'.t3lib_BEfunc::titleAttrib($LANG->getLL("move_".($eRParts[0]=="tt_content"?"record":"page"))).'></a>';
		$toolBar.='<a href="#" onClick="jumpToUrl(\'db_new.php?id='.$this->id.'&pagesOnly=1&returnUrl='.rawurlencode($R_URI).'\');return false;"><img src="gfx/new_page.gif" width="13" height="12" hspace=0 vspace=2 border="0"'.t3lib_BEfunc::titleAttrib($LANG->getLL("newPage"),1).' align=top></a>';
	
	//	$params="&edit[pages][".$this->id."]=edit";
	//	$toolBar.='<a href="#" onClick="'.t3lib_BEfunc::editOnClick($params).'"><img src="gfx/edit_page.gif" width="12" height="12" hspace=2 vspace=2 border="0"'.t3lib_BEfunc::titleAttrib($LANG->getLL("editPageHeader"),1).' align=top></a>';
		$toolBar.='<a href="'.$this->local_linkThisScript(array("edit_record"=>"pages:".$this->id)).'"><img src="gfx/edit2.gif" width="11" height="12" hspace=2 vspace=2 border="0"'.t3lib_BEfunc::titleAttrib($LANG->getLL("editPageHeader"),1).' align=top></a>';
		$toolBar.='<img src=clear.gif width=15 height=1 align=top>';
		$toolBar.=t3lib_BEfunc::helpTextIcon($this->descrTable,"quickEdit",$GLOBALS["BACK_PATH"]);
		
			// Setting page header
		$hS2='<table border=0 cellpadding=0 cellspacing=0 width=460>
			<tr>
				<td valign=top width=99%>'.$this->doc->getHeader("pages",$this->pageinfo,$this->pageinfo["_thePath"],0,explode("|",'<a href="'.$this->local_linkThisScript(array("edit_record"=>"pages:".$this->id)).'">|</a>')).'</td>
				<td valign=top width=1% valign=top>'.$this->topFuncMenu.'</td>
				<td valign=top width=1%><img src=clear.gif width=1 height=3><BR>'.$this->editIcon.'</td>
			</tr>
			<tr>
				<td><img src=clear.gif width=300 height=1></td>
				<td></td>
				<td></td>
			</tr>
			<tr>
				<td colspan=3 bgColor="'.$this->doc->bgColor4.'">'.t3lib_BEfunc::helpText($this->descrTable,"quickEdit",$GLOBALS["BACK_PATH"]).$toolBar.'</td>
			</tr>
		</table>';
	
		$content.=$this->doc->startPage($LANG->getLL("title"));
		$content.=$this->doc->section('',$hS2);
		$content.=$this->doc->spacer(7);
	
	
		// EDIT FORM:
		if ($GLOBALS["BE_USER"]->check("tables_modify",$eRParts[0]) && $edit_record && (($eRParts[0]!="pages"&&$this->EDIT_CONTENT) || ($eRParts[0]=="pages"&&($this->CALC_PERMS&1))))	{
		
			list($uidVal,$ex_pid,$ex_colPos) = explode("/",$eRParts[1]);
			
			$trData = t3lib_div::makeInstance("t3lib_transferData");
			$trData->defVals[$eRParts[0]] = array(
				'colPos' => intval($ex_colPos),
				'sys_language_uid' => intval($this->current_sys_language)
			);
			$trData->disableRTE = $this->MOD_SETTINGS["disableRTE"];
			$trData->lockRecords=1;
			$trData->fetchRecord($eRParts[0],($uidVal=="new"?$this->id:$uidVal),$uidVal);	// "new"
	//			$rec = $trData->regTableItems_data[$eRParts[0]."_".$uidVal];
			reset($trData->regTableItems_data);
			$rec = current($trData->regTableItems_data);
			if ($uidVal=="new")	{
				$new_unique_uid = uniqid("NEW");
				$rec["uid"] = $new_unique_uid;
				$rec["pid"] = intval($ex_pid)?intval($ex_pid):$this->id;
			} else {
				$rec["uid"] = $uidVal;
			}
			
			if (is_array($rec))	{
				$tceforms = t3lib_div::makeInstance("t3lib_TCEforms");
				$tceforms->initDefaultBEMode();
				$tceforms->fieldOrder = $this->modTSconfig["properties"]["tt_content."]["fieldOrder"];
				$tceforms->palettesCollapsed = !$this->MOD_SETTINGS["showPalettes"];
				$tceforms->disableRTE = $this->MOD_SETTINGS["disableRTE"];
				if ($BE_USER->uc["edit_showFieldHelp"]!="text" && $this->MOD_SETTINGS["showDescriptions"])	$tceforms->edit_showFieldHelp="text";
	
				$theCode="";
				$panel="";
				$panel.=$tceforms->getMainFields($eRParts[0],$rec);
				$panel=$tceforms->wrapTotal($panel,$rec,$eRParts[0]);
	
				$theCode.=$panel;
				if ($uidVal=="new")	{
					$theCode.='<input type="hidden" name="data['.$eRParts[0].']['.$rec["uid"].'][pid]" value="'.$rec["pid"].'">';
				}
				$theCode.='
					<input type="hidden" name="_serialNumber" value="'.md5(microtime()).'">
					<input type="hidden" name="_disableRTE" value="'.$tceforms->disableRTE.'">
					<input type="hidden" name="edit_record" value="'.$edit_record.'">
					<input type="hidden" name="redirect" value="'.htmlspecialchars($uidVal=="new" ? "db_layout.php?id=".$this->id."&new_unique_uid=".$new_unique_uid."&returnUrl=".rawurlencode(t3lib_div::GPvar("returnUrl")) : $R_URI ).'">
					';
				$theCode=$tceforms->printNeededJSFunctions_top().$theCode.$tceforms->printNeededJSFunctions();
				
				if ($lockInfo=t3lib_BEfunc::isRecordLocked($eRParts[0],$rec["uid"]))	{
					$lockIcon='<BR><table align="center" border=0 cellpadding=4 cellspacing=0 bgcolor="yellow" style="border:solid 2px black;"><tr><td>
						<img src="gfx/recordlock_warning3.gif" width="17" height="12" vspace=2 hspace=10 border="0" align=top></td><td><strong>'.htmlspecialchars($lockInfo["msg"]).'</strong>
					</td></tr></table><BR><BR>
						';
				} else $lockIcon="";
				
				$content.=$this->doc->section('',$lockIcon.$theCode);
			}
		} else {
			$content.=$this->doc->section($LANG->getLL("noAccess"),$LANG->getLL("noAccess_msg")."<BR><BR>",0,1);
		}
		
	
			// Bottom controls:
		$q_count = $this->getNumberOfHiddenElements();
		$h_func_b= t3lib_BEfunc::getFuncCheck($this->id,"SET[tt_content_showHidden]",$this->MOD_SETTINGS["tt_content_showHidden"],"db_layout.php","").(!$q_count?$GLOBALS["TBE_TEMPLATE"]->dfw($LANG->getLL("hiddenCE")):$LANG->getLL("hiddenCE")." (".$q_count.")");
		$h_func_b.= "<BR>".t3lib_BEfunc::getFuncCheck($this->id,"SET[showPalettes]",$this->MOD_SETTINGS["showPalettes"],"db_layout.php","").$LANG->sL("LLL:EXT:lang/locallang_core.php:labels.showPalettes");
		if (t3lib_extMgm::isLoaded("context_help") && $BE_USER->uc["edit_showFieldHelp"]!="text") $h_func_b.= "<BR>".t3lib_BEfunc::getFuncCheck($this->id,"SET[showDescriptions]",$this->MOD_SETTINGS["showDescriptions"],"db_layout.php","").$LANG->sL("LLL:EXT:lang/locallang_core.php:labels.showDescriptions");
		if ($BE_USER->isRTE())	$h_func_b.= "<BR>".t3lib_BEfunc::getFuncCheck($this->id,"SET[disableRTE]",$this->MOD_SETTINGS["disableRTE"],"db_layout.php","").$LANG->sL("LLL:EXT:lang/locallang_core.php:labels.disableRTE");
		$content.=$this->doc->section("",$h_func_b,0,0);
	
		$content.=$this->doc->spacer(10);
	
			// Select element:
		if ($eRParts[0]=="tt_content" && t3lib_div::testInt($eRParts[1]))	{
			$posMap = t3lib_div::makeInstance("ext_posMap");
			$posMap->cur_sys_language=$this->current_sys_language;
			$HTMLcode="";
			$HTMLcode.=t3lib_BEfunc::helpTextIcon($this->descrTable,"quickEdit_selElement",$GLOBALS["BACK_PATH"]).
							t3lib_BEfunc::helpText($this->descrTable,"quickEdit_selElement",$GLOBALS["BACK_PATH"]).
							"<BR>";
		
			$HTMLcode.=$posMap->printContentElementColumns($this->id,$eRParts[1],$this->colPosList,$this->MOD_SETTINGS["tt_content_showHidden"],$R_URI);
			$HTMLcode.='<BR><BR><A HREF="move_el.php?table=tt_content&uid='.$eRParts[1].'&sys_language_uid='.$this->current_sys_language.'&returnUrl='.rawurlencode(t3lib_div::getIndpEnv("REQUEST_URI")).'"><img src="'.$BACK_PATH.'gfx/move_record.gif" vspace=0 hspace=5 width=11 height=12 border=0 align=top align="top"'.t3lib_BEfunc::titleAttrib($LANG->getLL("move_record")).'>'.htmlspecialchars($LANG->getLL("move_record")).'</a>';
			$HTMLcode.='<BR><img src=clear.gif width=1 height=5>';
			$HTMLcode.='<BR><A HREF="db_new_content_el.php?id='.$this->id.'&sys_language_uid='.$this->current_sys_language.'&returnUrl='.rawurlencode(t3lib_div::getIndpEnv("REQUEST_URI")).'"><img src="'.$BACK_PATH.'gfx/new_record.gif" vspace=0 hspace=2 width=16 height=12 border=0 align=top align="top"'.t3lib_BEfunc::titleAttrib($LANG->getLL("newContentElement")).'>'.htmlspecialchars($LANG->getLL("newContentElement")).'</a>';
	
			$content.=$this->doc->spacer(20);
			$content.=$this->doc->section($LANG->getLL("CEonThisPage"),$HTMLcode,0,1);
			$content.=$this->doc->spacer(20);
		}
	
	//debug($tceforms->commentMessages);
		if (count($tceforms->commentMessages))	{
			$content.='
	<!-- TCEFORM messages
	'.implode(chr(10),$tceforms->commentMessages).'
	-->
	';
		}
		
		return $content;
	}
}

// Include extension?
if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/db_layout.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/db_layout.php"]);
}












// Make instance:
$SOBE = t3lib_div::makeInstance("SC_db_layout");
$SOBE->init();

// Include files?
reset($SOBE->include_once);	
while(list(,$INC_FILE)=each($SOBE->include_once))	{include_once($INC_FILE);}

$SOBE->clearCache();
$SOBE->main();
$SOBE->printContent();
?>