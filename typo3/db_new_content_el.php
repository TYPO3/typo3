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
 * New content elements wizard
 * (Part of the "cms" extension)
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 *
 */

 
$BACK_PATH="";
require ("init.php");
require ("template.php");
include ("sysext/lang/locallang_misc.php");
$LOCAL_LANG_orig = $LOCAL_LANG;
include ("sysext/lang/locallang_db_new_content_el.php");
$LOCAL_LANG = t3lib_div::array_merge_recursive_overrule($LOCAL_LANG_orig,$LOCAL_LANG);

// Exits if "cms" extension is not loaded:
t3lib_extMgm::isLoaded("cms",1);


// ***************************
// Functions
// ***************************
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
class ext_posMap extends t3lib_positionMap {
	var $dontPrintPageInsertIcons = 1;
	
	function wrapRecordTitle($str,$row)	{
		return $str;
	}
	function wrapColumnHeader($str,$vv)	{
		return $str;
	}
	function wrapRecordHeader($str,$row)	{
		return $str;
	}
	function onClickInsertRecord($row,$vv,$moveUid,$pid,$sys_lang=0) {
		$table="tt_content";
		
		$location="alt_doc.php?edit[tt_content][".(is_array($row)?-$row["uid"]:$pid)."]=new&defVals[tt_content][colPos]=".$vv."&defVals[tt_content][sys_language_uid]=".$sys_lang."&returnUrl=".rawurlencode($GLOBALS["R_URI"]);

		return 'document.location=\''.$location.'\'+document.editForm.defValues.value; return false;';
	}
}
class SC_db_new_content_el {
	var $modTSconfig=array();
	var $access;
	var $content;
	var $id;
	var $doc;	
	var $sys_language=0;
	
	var $include_once = array();

		// Constructor:
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;
		global $R_URI,$TBE_MODULES_EXT;
		
		if (is_array($TBE_MODULES_EXT["xMOD_db_new_content_el"]["addElClasses"]))	{
			$this->include_once = array_merge($this->include_once,$TBE_MODULES_EXT["xMOD_db_new_content_el"]["addElClasses"]);
		}
		
//debug($HTTP_GET_VARS);
		
		$this->id = intval(t3lib_div::GPvar("id"));
		$this->sys_language = intval(t3lib_div::GPvar("sys_language_uid"));
//debug($this->sys_language);
		$perms_clause = $BE_USER->getPagePermsClause(1);
		
		$this->MCONF["name"] = "xMOD_db_new_content_el";
		$this->modTSconfig = t3lib_BEfunc::getModTSconfig($this->id,"mod.".$this->MCONF["name"]);
		
		$this->doc = t3lib_div::makeInstance("mediumDoc");
		$this->doc->backPath = $BACK_PATH;
		$this->doc->JScode='';
		$this->doc->form='<form action="" name="editForm"><input type="hidden" name="defValues" value="">';
		
		$R_URI=t3lib_div::GPvar("returnUrl");
		$pageinfo = t3lib_BEfunc::readPageAccess($this->id,$perms_clause);
		$this->access = is_array($pageinfo) ? 1 : 0;
	}
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;
		global $R_URI;

		if ($this->id && $this->access)	{
		// ***************************
		// Setting template object
		// ***************************
			
			$posMap = t3lib_div::makeInstance("ext_posMap");
			$posMap->cur_sys_language = $this->sys_language;
		
			if (isset($HTTP_GET_VARS["colPos"]))	{
				$colPos = t3lib_div::GPvar("colPos");
				$sys_lang = $this->sys_language;
				$uid_pid = intval(t3lib_div::GPvar("uid_pid"));
				if ($uid_pid<0)	{
					$row=array();
					$row["uid"]=abs($uid_pid);
				} else {
					$row="";
				}
				$onClickEvent = $posMap->onClickInsertRecord($row,$colPos,"",$uid_pid,$sys_lang);
			} else {
				$onClickEvent="";
			}
		
				$this->doc->JScode='
		<script language="javascript" type="text/javascript">
			function goToalt_doc()	{
				'.$onClickEvent.'
			}	
		</script>
				';
		//	debug($onClickEvent);
		
		
			// ***************************
			// Creating content
			// ***************************
			$this->content="";
			$this->content.=$this->doc->startPage($LANG->getLL("newContentElement"));
			$this->content.=$this->doc->header($LANG->getLL("newContentElement"));
			$this->content.=$this->doc->spacer(5);
		
			$elRow = t3lib_BEfunc::getRecord("pages",$this->id);
			$hline = t3lib_iconWorks::getIconImage("pages",$elRow,$BACK_PATH,t3lib_BEfunc::titleAttrib(t3lib_BEfunc::getRecordIconAltText($elRow,"pages"),1).' align=top');
			$hline.= t3lib_BEfunc::getRecordTitle("pages",$elRow,1);
			$this->content.=$this->doc->section("",$hline,0,1);
			$this->content.=$this->doc->spacer(10);
		
		
				// Wizard
			$code="";
			$lines=array();
			$wizardItems = $this->getWizardItems();
			reset($wizardItems);
			$cc=0;
			while(list($k,$wInfo)=each($wizardItems))	{
				if ($wInfo["header"])	{
					if ($cc>0) $lines[]='<tr><td colspan=3><BR></td></tr>';
					$lines[]='<tr bgcolor="'.$this->doc->bgColor5.'"><td colspan=3><strong>'.htmlspecialchars($wInfo["header"]).'</strong></td></tr>';
				} else {
					$tL=array();
					
					$oC = "document.editForm.defValues.value=unescape('".rawurlencode($wInfo["params"])."');goToalt_doc();".(!$onClickEvent?"document.location='#sel2';":"");
					
					$tL[]='<input type="radio" name="tempB" value="'.$k.'" onClick="'.$this->doc->thisBlur().$oC.'">';
			
					$iInfo = @getimagesize($wInfo["icon"]);
			//		debug($iInfo);
					$tL[]='<a href="#" onClick="document.editForm.tempB['.$cc.'].checked=1;'.$this->doc->thisBlur().$oC.'return false;"><img border=0 src="'.$wInfo["icon"].'" '.$iInfo[3].'></a>';
			
					$tL[]='<a href="#" onClick="document.editForm.tempB['.$cc.'].checked=1;'.$this->doc->thisBlur().$oC.'return false;"><strong>'.htmlspecialchars($wInfo["title"]).'</strong><BR>'.nl2br(htmlspecialchars($wInfo["description"])).'</a>';
			
					$bgC=' bgcolor="'.$this->doc->bgColor4.'"';
					$bgC='';
					$lines[]='<tr'.$bgC.'><td valign=top>'.implode('</td><td valign=top>',$tL).'</td></tr>';
					$cc++;
				}
			}
			$code.=$LANG->getLL("sel1").'<BR><BR><table border=0 cellpadding=1 cellspacing=2>'.implode("",$lines).'</table>';
			$this->content.=$this->doc->section(!$onClickEvent?$LANG->getLL("1_selectType"):"",$code,0,1);
		
			if (!$onClickEvent)	{
				$this->content.=$this->doc->section("",'<a name="sel2"></a>');
				$this->content.=$this->doc->spacer(20);
					// Select position
				$code=$LANG->getLL("sel2")."<BR><BR>";
			
				$modTSconfig_SHARED = t3lib_BEfunc::getModTSconfig($this->id,"mod.SHARED");		// SHARED page-TSconfig settings.
				$colPosList = strcmp(trim($modTSconfig_SHARED["properties"]["colPos_list"]),"") ? trim($modTSconfig_SHARED["properties"]["colPos_list"]) : "1,0,2,3";
			
				$code.=$posMap->printContentElementColumns($this->id,0,$colPosList,1,$R_URI);
				$this->content.=$this->doc->section($LANG->getLL("2_selectPosition"),$code,0,1);
			}
		
			if ($R_URI)	{
				$code='<BR><BR><a href="'.$R_URI.'" class="typo3-goBack"><img src="gfx/goback.gif" width="14" height="14" hspace="2" border="0" align="top"><strong>'.$LANG->getLL("goBack").'</strong></a>';
				$this->content.=$this->doc->section("",$code,0,1);
			}
		
			$this->content.=$this->doc->section("",'<img src=clear.gif width=1 height=700>',0,1);
		
			// ***************************
			// Ending / Outputting
			// ***************************
		
		} else {
			$this->content="";
			$this->content.=$this->doc->startPage($LANG->getLL("newContentElement"));
			$this->content.=$this->doc->header($LANG->getLL("newContentElement"));
			$this->content.=$this->doc->spacer(5);
		}
	}
	function printContent()	{
		global $SOBE;

		$this->content.= $this->doc->middle();
		$this->content.= $this->doc->endPage();
		echo $this->content;
	}
	
	// ***************************
	// OTHER FUNCTIONS:	
	// ***************************

	function wizardArray()	{
		global $LANG,$TBE_MODULES_EXT;
		
		$wizardItems = array(
			"common" => array("header"=>$LANG->getLL("common")),
			"common_1" => array(
				"icon"=>"gfx/c_wiz/regular_text.gif",
				"title"=>$LANG->getLL("common_1_title"),
				"description"=>$LANG->getLL("common_1_description"),
				"params"=>"&defVals[tt_content][CType]=text"
			),
			"common_2" => array(
				"icon"=>"gfx/c_wiz/text_image_below.gif",
				"title"=>$LANG->getLL("common_2_title"),
				"description"=>$LANG->getLL("common_2_description"),
				"params"=>"&defVals[tt_content][CType]=textpic&defVals[tt_content][imageorient]=8"
			),
			"common_3" => array(
				"icon"=>"gfx/c_wiz/text_image_right.gif",
				"title"=>$LANG->getLL("common_3_title"),
				"description"=>$LANG->getLL("common_3_description"),
				"params"=>"&defVals[tt_content][CType]=textpic&defVals[tt_content][imageorient]=17"
			),
			"common_4" => array(
				"icon"=>"gfx/c_wiz/images_only.gif",
				"title"=>$LANG->getLL("common_4_title"),
				"description"=>$LANG->getLL("common_4_description"),
				"params"=>"&defVals[tt_content][CType]=image&defVals[tt_content][imagecols]=2"
			),
			"common_5" => array(
				"icon"=>"gfx/c_wiz/bullet_list.gif",
				"title"=>$LANG->getLL("common_5_title"),
				"description"=>$LANG->getLL("common_5_description"),
				"params"=>"&defVals[tt_content][CType]=bullets"
			),
			"common_6" => array(
				"icon"=>"gfx/c_wiz/table.gif",
				"title"=>$LANG->getLL("common_6_title"),
				"description"=>$LANG->getLL("common_6_description"),
				"params"=>"&defVals[tt_content][CType]=table"
			),
			"special" => array("header"=>$LANG->getLL("special")),
			"special_1" => array(
				"icon"=>"gfx/c_wiz/filelinks.gif",
				"title"=>$LANG->getLL("special_1_title"),
				"description"=>$LANG->getLL("special_1_description"),
				"params"=>"&defVals[tt_content][CType]=uploads"
			),
			"special_2" => array(
				"icon"=>"gfx/c_wiz/multimedia.gif",
				"title"=>$LANG->getLL("special_2_title"),
				"description"=>$LANG->getLL("special_2_description"),
				"params"=>"&defVals[tt_content][CType]=multimedia"
			),
			"special_3" => array(
				"icon"=>"gfx/c_wiz/sitemap2.gif",
				"title"=>$LANG->getLL("special_3_title"),
				"description"=>$LANG->getLL("special_3_description"),
				"params"=>"&defVals[tt_content][CType]=menu&defVals[tt_content][menu_type]=2"
			),
			"special_4" => array(
				"icon"=>"gfx/c_wiz/html.gif",
				"title"=>$LANG->getLL("special_4_title"),
				"description"=>$LANG->getLL("special_4_description"),
				"params"=>"&defVals[tt_content][CType]=html"
			),
		
		
			"forms" => array("header"=>$LANG->getLL("forms")),
			"forms_1" => array(
				"icon"=>"gfx/c_wiz/mailform.gif",
				"title"=>$LANG->getLL("forms_1_title"),
				"description"=>$LANG->getLL("forms_1_description"),
				"params"=>"&defVals[tt_content][CType]=mailform&defVals[tt_content][bodytext]=".rawurlencode(trim('
# Example content:
Name: | *name = input,40 | Enter your name here
Email: | *email=input,40 |
Address: | address=textarea,40,5 |
Contact me: | tv=check | 1

|formtype_mail = submit | Send form!
|html_enabled=hidden | 1
|subject=hidden| This is the subject
				'))
			),
			"forms_2" => array(
				"icon"=>"gfx/c_wiz/searchform.gif",
				"title"=>$LANG->getLL("forms_2_title"),
				"description"=>$LANG->getLL("forms_2_description"),
				"params"=>"&defVals[tt_content][CType]=search"
			),
			"forms_3" => array(
				"icon"=>"gfx/c_wiz/login_form.gif",
				"title"=>$LANG->getLL("forms_3_title"),
				"description"=>$LANG->getLL("forms_3_description"),
				"params"=>"&defVals[tt_content][CType]=login"
			),
			"plugins" => array("header"=>$LANG->getLL("plugins")),
		);


			// PLUG-INS:
		if (is_array($TBE_MODULES_EXT["xMOD_db_new_content_el"]["addElClasses"]))	{
			reset($TBE_MODULES_EXT["xMOD_db_new_content_el"]["addElClasses"]);
			while(list($class,$path)=each($TBE_MODULES_EXT["xMOD_db_new_content_el"]["addElClasses"]))	{
				$modObj = t3lib_div::makeInstance($class);
				$wizardItems = $modObj->proc($wizardItems);
			}
		}

		return $wizardItems;
	}
	function getWizardItems()	{
		return $this->wizardArray();
	}
}

// Include extension?
if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/db_new_content_el.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/db_new_content_el.php"]);
}












// Make instance:
$SOBE = t3lib_div::makeInstance("SC_db_new_content_el");
$SOBE->init();

// Include files?
reset($SOBE->include_once);	
while(list(,$INC_FILE)=each($SOBE->include_once))	{include_once($INC_FILE);}

$SOBE->main();
$SOBE->printContent();
?>