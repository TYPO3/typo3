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
 * Move element wizard
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 *
 */

 
$BACK_PATH="";
require ("init.php");
require ("template.php");
include ("sysext/lang/locallang_misc.php");
require_once (PATH_t3lib."class.t3lib_page.php");
require_once (PATH_t3lib."class.t3lib_positionmap.php");
require_once (PATH_t3lib."class.t3lib_pagetree.php");




// ***************************
// Script Classes
// ***************************
class localPageTree extends t3lib_pageTree {
	function wrapIcon($icon,$row)	{
		return substr($icon,0,-1).' title="id='.htmlspecialchars($row["uid"]).'">';
	}
}
class ext_posMap_pages extends t3lib_positionMap {
	var $l_insertNewPageHere = "movePageToHere";
	
	function onClickEvent($pid)	{
		return 'document.location=\'tce_db.php?cmd[pages]['.$GLOBALS["SOBE"]->moveUid.']['.$this->moveOrCopy.']='.$pid.'&redirect='.rawurlencode($this->R_URI).'&prErr=1&uPT=1&vC='.$GLOBALS["BE_USER"]->veriCode().'\';return false;';
	}
	function linkPageTitle($str,$rec)	{
		return '<a href="'.t3lib_div::linkThisScript(array("uid"=>intval($rec["uid"]),"moveUid"=>$GLOBALS["SOBE"]->moveUid)).'">'.$str.'</a>';
	}
	function boldTitle($t_code,$dat,$id)	{
		return parent::boldTitle($t_code,$dat,$GLOBALS["SOBE"]->moveUid);
	}
}
class ext_posMap_tt_content extends t3lib_positionMap {
	var $dontPrintPageInsertIcons = 1;
	
	function linkPageTitle($str,$rec)	{
		$str = '<a href="'.t3lib_div::linkThisScript(array("uid"=>intval($rec["uid"]),"moveUid"=>$GLOBALS["SOBE"]->moveUid)).'">'.$str.'</a>';
		return $str;
	}

	function wrapRecordTitle($str,$row)	{
		if ($GLOBALS["SOBE"]->moveUid==$row["uid"])	$str = '<b>'.$str.'</b>';
		return parent::wrapRecordTitle($str,$row);
	}
}
class SC_move_el {
	var $content;
	var $moveUid;
	var $content;
	var $perms_clause;
	var $page_id;
	var $table;
	var $R_URI;
	var $doc;	
	var $sys_language=0;
	
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;
//debug($HTTP_GET_VARS);
		$this->sys_language = intval(t3lib_div::GPvar("sys_language"));
		$this->perms_clause = $BE_USER->getPagePermsClause(1);
		
		
		$this->doc = t3lib_div::makeInstance("mediumDoc");
		$this->doc->backPath = $BACK_PATH;
		$this->doc->JScode='';
		
		
		// ***************************
		// Creating content
		// ***************************
		$this->content="";
		$this->content.=$this->doc->startPage($LANG->getLL("movingElement"));
		$this->content.=$this->doc->header($LANG->getLL("movingElement"));
		$this->content.=$this->doc->spacer(5);
		
		
		$this->page_id=intval(t3lib_div::GPvar("uid"));
		$this->table=t3lib_div::GPvar("table");
		$this->R_URI=t3lib_div::GPvar("returnUrl");
		$this->moveUid = t3lib_div::GPvar("moveUid") ? t3lib_div::GPvar("moveUid") : $this->page_id;
	}
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;

		if ($this->page_id)	{
			
			$elRow = t3lib_BEfunc::getRecord($this->table,$this->moveUid);
			$hline = t3lib_iconWorks::getIconImage($this->table,$elRow,$BACK_PATH,t3lib_BEfunc::titleAttrib(t3lib_BEfunc::getRecordIconAltText($elRow,$this->table),1).' align=top');
			$hline.= t3lib_BEfunc::getRecordTitle($this->table,$elRow,1);
			$hline.= '<BR><input type="hidden" name="makeCopy" value="0"><input type="checkbox" name="makeCopy" value="1"'.(t3lib_div::GPvar("makeCopy")?" CHECKED":"").' onClick="document.location=\''.t3lib_div::linkThisScript(array("makeCopy"=>!t3lib_div::GPvar("makeCopy"))).'\'">'.$LANG->getLL("makeCopy");
			
			$this->content.=$this->doc->section($LANG->getLL("moveElement").":",$hline,0,1);
			$this->content.=$this->doc->spacer(20);
		
		
			$code="";
			if ((string)$this->table=="pages")	{
				$pageinfo = t3lib_BEfunc::readPageAccess($this->page_id,$this->perms_clause);
				if (is_array($pageinfo))	{
				
					
					$posMap = t3lib_div::makeInstance("ext_posMap_pages");
					$posMap->moveOrCopy = t3lib_div::GPvar("makeCopy")?"copy":"move";
		
					$code="";
		//			$code.="<BR><strong>".$LANG->getLL("selectPositionOfElement").":</strong><BR><BR>";
					if ($pageinfo["pid"])	{
						$pidPageInfo = t3lib_BEfunc::readPageAccess($pageinfo["pid"],$this->perms_clause);
						if (is_array($pidPageInfo))	{
							$code.='<a href="'.t3lib_div::linkThisScript(array("uid"=>intval($pageinfo["pid"]),"moveUid"=>$this->moveUid)).'"><img src="gfx/i/pages_up.gif" width="18" height="16" border="0" align=top>'.t3lib_BEfunc::getRecordTitle("pages",$pidPageInfo).'</a><BR>';
						}
					}
					$code.= $posMap->positionTree($this->page_id,$pageinfo,$this->perms_clause,$this->R_URI);
				}
			}
		
			if ((string)$this->table=="tt_content")	{
				$tt_content_rec = t3lib_BEfunc::getRecord("tt_content",$this->moveUid);
				if (!t3lib_div::GPvar("moveUid"))	$this->page_id = $tt_content_rec["pid"];
		
		//		debug($tt_content_rec["uid"]);
				
		
				$pageinfo = t3lib_BEfunc::readPageAccess($this->page_id,$this->perms_clause);
				if (is_array($pageinfo))	{
					$posMap = t3lib_div::makeInstance("ext_posMap_tt_content");
					$posMap->moveOrCopy = t3lib_div::GPvar("makeCopy")?"copy":"move";
					$posMap->cur_sys_language = $this->sys_language;
		
					$code="";
		
					$hline = t3lib_iconWorks::getIconImage("pages",$pageinfo,$BACK_PATH,t3lib_BEfunc::titleAttrib(t3lib_BEfunc::getRecordIconAltText($pageinfo,"pages"),1).' align=top');
					$hline.= t3lib_BEfunc::getRecordTitle("pages",$pageinfo,1);
		
						// Find columns
					$modTSconfig_SHARED = t3lib_BEfunc::getModTSconfig($this->page_id,"mod.SHARED");		// SHARED page-TSconfig settings.
					$colPosList = strcmp(trim($modTSconfig_SHARED["properties"]["colPos_list"]),"") ? trim($modTSconfig_SHARED["properties"]["colPos_list"]) : "1,0,2,3";
		
					$code=$hline."<BR>";
					$code.=$posMap->printContentElementColumns($this->page_id,$this->moveUid,$colPosList,1,$this->R_URI);
		
					$code.= '<BR>';
					$code.= '<BR>';
					if ($pageinfo["pid"])	{
						$pidPageInfo = t3lib_BEfunc::readPageAccess($pageinfo["pid"],$this->perms_clause);
						if (is_array($pidPageInfo))	{
							$code.='<a href="'.t3lib_div::linkThisScript(array("uid"=>intval($pageinfo["pid"]),"moveUid"=>$this->moveUid)).'"><img src="gfx/i/pages_up.gif" width="18" height="16" border="0" align=top>'.t3lib_BEfunc::getRecordTitle("pages",$pidPageInfo).'</a><BR>';
						}
					}
					$code.= $posMap->positionTree($this->page_id,$pageinfo,$this->perms_clause,$this->R_URI);
				}
			}
		
			if ($this->R_URI)	{
				$code.='<BR><BR><a href="'.$this->R_URI.'" class="typo3-goBack"><img src="gfx/goback.gif" width="14" height="14" hspace="2" border="0" align="top"><strong>'.$LANG->getLL("goBack").'</strong></a>';
			}
			$this->content.=$this->doc->section($LANG->getLL("selectPositionOfElement").":",$code,0,1);
		}
	}
	function printContent()	{
		global $SOBE;
		$this->content.= $this->doc->middle();
		$this->content.= $this->doc->endPage();
		echo $this->content;
	}
}

// Include extension?
if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/move_el.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/move_el.php"]);
}












// Make instance:
$SOBE = t3lib_div::makeInstance("SC_move_el");
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>