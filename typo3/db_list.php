<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2003 Kasper Skaarhoj (kasper@typo3.com)
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
 * $Id$
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   87: class SC_db_list 
 *  107:     function init()	
 *  128:     function menuConfig()	
 *  151:     function clearCache()	
 *  164:     function main()	
 *  342:     function printContent()	
 *
 * TOTAL FUNCTIONS: 5
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
 

unset($MCONF);
require ('mod/web/list/conf.php');
require ('init.php');
require ('template.php');
$LANG->includeLLFile('EXT:lang/locallang_mod_web_list.php');
require_once (PATH_t3lib.'class.t3lib_page.php');
require_once (PATH_t3lib.'class.t3lib_pagetree.php');
require_once (PATH_t3lib.'class.t3lib_recordlist.php');
require_once (PATH_t3lib.'class.t3lib_clipboard.php');
require_once ('class.db_list.inc');
require_once ('class.db_list_extra.inc');
$BE_USER->modAccess($MCONF,1);

t3lib_BEfunc::lockRecords();








/**
 * Script Class
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_db_list {
	var $MCONF=array();
	var $MOD_MENU=array();
	var $MOD_SETTINGS=array();

	var $include_once=array();
	var $content;
	
	var $perms_clause;
	var $modTSconfig;
	var $pointer;
	var $pageinfo;
	var $imagemode;
	var $table;
	var $id;
	var $doc;	

	/**
	 * @return	[type]		...
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;
		$this->MCONF = $GLOBALS['MCONF'];
		$this->id = t3lib_div::GPvar('id');

		$this->perms_clause = $BE_USER->getPagePermsClause(1);
		$this->pointer = t3lib_div::GPvar('pointer');
		$this->imagemode = t3lib_div::GPvar('imagemode');
		$this->table = t3lib_div::GPvar('table');
		$this->menuConfig();

		if (t3lib_div::GPvar('clear_cache') || t3lib_div::GPvar('cmd')=='delete')	{
			$this->include_once[]=PATH_t3lib.'class.t3lib_tcemain.php';
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function menuConfig()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;

			// MENU-ITEMS:
			// If array, then it's a selector box menu
			// If empty string it's just a variable, that'll be saved. 
			// Values NOT in this array will not be saved in the settings-array for the module.
		$this->MOD_MENU = array(
			"bigControlPanel" => "",
			"clipBoard" => ""
		);
			
		$this->modTSconfig = t3lib_BEfunc::getModTSconfig($this->id,"mod.".$this->MCONF["name"]);
		
			// CLEANSE SETTINGS
		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::GPvar("SET"), $this->MCONF["name"]);
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function clearCache()	{
		if (t3lib_div::GPvar("clear_cache"))	{
			$tce = t3lib_div::makeInstance("t3lib_TCEmain");
			$tce->start(Array(),Array());
			$tce->clear_cacheCmd($this->id);
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;

		$this->doc = t3lib_div::makeInstance("template");
		$this->doc->backPath = $BACK_PATH;

		
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;
		
		$dblist = t3lib_div::makeInstance("localRecordList");
		$dblist->backPath = $BACK_PATH;
		$dblist->calcPerms = $BE_USER->calcPerms($this->pageinfo);
		$dblist->thumbs = $BE_USER->uc["thumbnailsByDefault"];
		$dblist->returnUrl=t3lib_div::GPvar("returnUrl");
		$dblist->allFields = ($this->MOD_SETTINGS["bigControlPanel"] || $this->table) ? 1 : 0;
		$dblist->showClipboard = 1;
		$dblist->disableSingleTableView = $this->modTSconfig["properties"]["disableSingleTableView"];
		$dblist->alternateBgColors=$this->modTSconfig["properties"]["alternateBgColors"]?1:0;
		$dblist->allowedNewTables = t3lib_div::trimExplode(",",$this->modTSconfig["properties"]["allowedNewTables"],1);
		$dblist->newWizards=$this->modTSconfig["properties"]["newWizards"]?1:0;

			// ***********************
			// CLipboard things...	
			// ***********************
		$dblist->clipObj = t3lib_div::makeInstance("t3lib_clipboard");		// Start clipboard
		$dblist->clipObj->initializeClipboard();	// Initialize - reads the clipboard content from the user session

			$CB = $HTTP_GET_VARS["CB"];	// CB is the clipboard command array
			if (t3lib_div::GPvar("cmd")=="setCB") {
					// CBH is all the fields selected for the clipboard, CBC is the checkbox fields which were checked. By merging we get a full array of checked/unchecked elements
					// This is set to the "el" array of the CB after being parsed so only the table in question is registered.
				$CB["el"] = $dblist->clipObj->cleanUpCBC(array_merge($HTTP_POST_VARS["CBH"],$HTTP_POST_VARS["CBC"]),t3lib_div::GPvar("cmd_table"));
			}
			if (!$this->MOD_SETTINGS["clipBoard"])	$CB["setP"]="normal";	// If the clipboard is NOT shown, set the pad to "normal".
			$dblist->clipObj->setCmd($CB);		// Execute commands.
			$dblist->clipObj->cleanCurrent();	// Clean up pad
			$dblist->clipObj->endClipboard();	// Save the clipboard content
		
		
			// This flag will prevent the 
			// It is set, if the clickmenu-layer is active AND the extended view is not enabled.
		$dblist->dontShowClipControlPanels = $CLIENT["FORMSTYLE"] && !$this->MOD_SETTINGS["bigControlPanel"] && $dblist->clipObj->current=="normal" && !$BE_USER->uc["disableCMlayers"] && !$this->modTSconfig["properties"]["showClipControlPanelsDespiteOfCMlayers"];
		
		
		
		if ($access)	{
		
				// Has not to do with the clipboard but is simply the delete action. The clipboard object is used to clean up the submitted entries to only the selected table.
			if (t3lib_div::GPvar("cmd")=="delete")	{
				$items = $dblist->clipObj->cleanUpCBC($HTTP_POST_VARS["CBC"],t3lib_div::GPvar("cmd_table"),1);
				if (count($items))	{
					$cmd=array();
					reset($items);
					while(list($iK)=each($items))	{
						$iKParts = explode("|",$iK);
						$cmd[$iKParts[0]][$iKParts[1]]["delete"]=1;
					}
					$tce = t3lib_div::makeInstance("t3lib_TCEmain");
					$tce->start(array(),$cmd);
					$tce->process_cmdmap();
		
					if (isset($cmd["pages"]))	{
						t3lib_BEfunc::getSetUpdateSignal("updatePageTree");
					}
		
					$tce->printLogErrorMessages(t3lib_div::getIndpEnv("REQUEST_URI"));
				}
			}
		
			$this->pointer = t3lib_div::intInRange($this->pointer,0,100000);
			$dblist->start($this->id,$this->table,$this->pointer,
				t3lib_div::GPvar("search_field"),
				t3lib_div::GPvar("search_levels"),
				t3lib_div::GPvar("showLimit")
			);
			$dblist->setDispFields();
			$dblist->writeTop($this->pageinfo,t3lib_BEfunc::getRecordPath (intval($this->pageinfo["uid"]),$this->perms_clause,15));
			$dblist->generateList($this->id,$this->table);
			$dblist->writeBottom();
		
		$this->doc->JScode='
		<script language="javascript" type="text/javascript">
			function jumpToUrl(URL)	{	//
//	alert("jumpToUrl: "+URL);
				document.location = URL;
				return false;
			}
			function jumpExt(URL,anchor)	{	//
				var anc = anchor?anchor:"";
//	alert("jumpExt: "+URL+(T3_THIS_LOCATION?"&returnUrl="+T3_THIS_LOCATION:"")+anc);
				document.location = URL+(T3_THIS_LOCATION?"&returnUrl="+T3_THIS_LOCATION:"")+anc;
				return false;
			}
			function jumpSelf(URL)	{	//
//	alert("jumpSelf: "+URL+(T3_RETURN_URL?"&returnUrl="+T3_RETURN_URL:""));
				document.location = URL+(T3_RETURN_URL?"&returnUrl="+T3_RETURN_URL:"");
				return false;
			}
			'.$this->doc->redirectUrls($dblist->listURL()).'
			'.$dblist->CBfunctions().'
			function editRecords(table,idList,addParams,CBflag)	{	//
				document.location="'.$backPath.'alt_doc.php?returnUrl='.rawurlencode(t3lib_div::getIndpEnv("REQUEST_URI")).
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
		</script>
		';

			$CMparts=$this->doc->getContextMenuCode();
			$this->doc->bodyTagAdditions = $CMparts[1];
			$this->doc->JScode.=$CMparts[0];
			$this->doc->postCode.= $CMparts[2];
		} // access
		
		
		
		$this->content="";
		$this->content.=$this->doc->startPage("DB list");
		$this->content.= '<form action="'.$dblist->listURL().'" method="POST" name="dblistForm">';
		$this->content.= $dblist->HTMLcode;
		$this->content.= '<input type="hidden" name="cmd_table"><input type="hidden" name="cmd"></form>';
		
		if ($dblist->HTMLcode)	{	// Making search form:
			if ($dblist->table)	{	// Making search form:
				$sBoxPre = $dblist->spaceSearchBoxFromLeft;
				$dblist->spaceSearchBoxFromLeft=$sBoxPre;
				$this->content.=$dblist->fieldSelectBox($dblist->table);
				$dblist->spaceSearchBoxFromLeft=$sBoxPre;
				$this->content.="";
			}
		
			$this->content.='<form action="" method="POST">';
			$this->content.=t3lib_BEfunc::getFuncCheck($this->id,"SET[bigControlPanel]",$this->MOD_SETTINGS["bigControlPanel"],"db_list.php","")." ".$LANG->getLL("largeControl")."<BR>";
			if ($dblist->showClipboard)	$this->content.=t3lib_BEfunc::getFuncCheck($this->id,"SET[clipBoard]",$this->MOD_SETTINGS["clipBoard"],"db_list.php","").' '.$LANG->getLL("showClipBoard");
			$this->content.='</form>';
		
				// Printing clipboard if selected for.
			if ($this->MOD_SETTINGS["clipBoard"] && $dblist->showClipboard)	$this->content.=$dblist->clipObj->printClipboard();
		
			
			if (!$this->modTSconfig["properties"]["noCreateRecordsLink"]) 	$this->content.='<a href="db_new.php?id='.$this->id.'&returnUrl='.rawurlencode(t3lib_div::getIndpEnv("REQUEST_URI")).'"><img src="gfx/new_el.gif" width="11" height="12" hspace=4 border="0" align=top><strong>'.$LANG->getLL("newRecordGeneral").'</strong></a><BR><BR>';
			$this->content.=$dblist->getSearchBox();
			$this->content.="<BR>".$dblist->showSysNotesForPage();
		
		
			// ShortCut
			if ($BE_USER->mayMakeShortcut())	{
				$this->content.=$this->doc->makeShortcutIcon("id,imagemode,pointer,table,search_field,search_levels,showLimit,sortField,sortRev",implode(",",array_keys($this->MOD_MENU)),$this->MCONF["name"]);
			}
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function printContent()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;

		$this->content.= $this->doc->endPage();
		echo $this->content;
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/db_list.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/db_list.php']);
}












// Make instance:
$SOBE = t3lib_div::makeInstance('SC_db_list');
$SOBE->init();

// Include files?
reset($SOBE->include_once);	
while(list(,$INC_FILE)=each($SOBE->include_once))	{include_once($INC_FILE);}

$SOBE->clearCache();
$SOBE->main();
$SOBE->printContent();
?>