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
 * Displays the secondary-options palette for the TCEFORMs wherever they are shown.
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 *
 */



require ("init.php");
require ("template.php");
require_once (PATH_t3lib."class.t3lib_tceforms.php");
require_once (PATH_t3lib."class.t3lib_transferdata.php");
require_once (PATH_t3lib."class.t3lib_loaddbgroup.php");
include ("sysext/lang/locallang_alt_doc.php");




// ***************************
// Script Classes
// ***************************
class formRender extends t3lib_TCEforms {
	function printPalette($palArr)	{
		$out="";
		reset($palArr);
		while(list(,$content)=each($palArr))	{
			$iRow[]='<td valign=top nowrap><img name="req_'.$content["TABLE"].'_'.$content["ID"].'_'.$content["FIELD"].'" src="clear.gif" width=10 height=10 vspace=4><img name="cm_'.$content["TABLE"].'_'.$content["ID"].'_'.$content["FIELD"].'" src="clear.gif" width=7 height=10 vspace=4></td>
			<td valign=top nowrap><img src=clear.gif width=1 height=3><BR>'.$content["NAME"].'&nbsp;</td>
			<td nowrap valign=top>'.$content["ITEM"].$content["HELP_ICON"].'</td>';
		}
		$out='<table border=0 cellpadding=0 cellspacing=0 width=1>
		<tr>
			<td nowrap valign=top><img src=clear.gif width=5 height=1><a href="#" onClick="closePal();return false;"><img src="gfx/close_12h.gif" width="11" height="12" vspace=4 border="0"'.t3lib_BEfunc::titleAttrib($GLOBALS["LANG"]->sL("LLL:EXT:lang/locallang_core.php:labels.close")).'></a></td>'.
//			'<td><img src=clear.gif width='.intval($this->paletteMargin).' height=1></td>'.
			implode("",$iRow).'
		</tr>
		</table>';
		return $out;
	}
}
class formRender_vert extends t3lib_TCEforms {
	function printPalette($palArr)	{
		$out="";
		reset($palArr);
		$bgColor=' bgColor="'.$this->colorScheme[2].'"';
		while(list(,$content)=each($palArr))	{
			$iRow[]='<tr><td><img src=clear.gif width='.intval($this->paletteMargin).' height=1></td><td'.$bgColor.'>&nbsp;</td><td nowrap'.$bgColor.'><font color="'.$this->colorScheme[4].'">'.$content["NAME"].'</font></td></tr>';
			$iRow[]='<tr><td></td><td valign=top><img name="req_'.$content["TABLE"].'_'.$content["ID"].'_'.$content["FIELD"].'" src="clear.gif" width=10 height=10 vspace=4><img name="cm_'.$content["TABLE"].'_'.$content["ID"].'_'.$content["FIELD"].'" src="clear.gif" width=7 height=10 vspace=4></td><td nowrap valign=top>'.$content["ITEM"].$content["HELP_ICON"].'</td></tr>';
		}

		$iRow[]='<tr><td></td><td valign=top></td><td nowrap valign=top>
		<BR>
		<input type="submit" value="'.$GLOBALS["LANG"]->sL("LLL:EXT:lang/locallang_core.php:labels.close").'" onClick="closePal();return false;">
		</td></tr>';

		$out='<table border=0 cellpadding=0 cellspacing=0>
		'.implode("",$iRow).'
		</table>';
		
		return $out;
	}
}
class alt_palette_CMtemplate extends template {
	function docBodyTagBegin()	{
		return '<BODY bgColor="'.$this->bgColor2.'" LINK="#000000" ALINK="#000000" VLINK="#000000" marginwidth="0" marginheight="8" topmargin=8 leftmargin=0 background="gfx/alt_topmenu_back_full.gif">'.$this->form;
	}
}
class SC_alt_palette {
	var $content;
	var $backRef;
	var $formName;
	var $formRef;
	var $doc;	
	
		// Constructor:
	function init()	{
		global $SOBE;

		$this->doc = t3lib_div::makeInstance(t3lib_div::GPvar("backRef")?"alt_palette_CMtemplate":"template");
		$this->doc->bodyTagMargins["x"]=0;
		$this->doc->bodyTagMargins["y"]=0;
		$this->doc->form='<form action="#" method="POST" name="'.t3lib_div::GPvar("formName").'" onSubmit="return false;" autocomplete="off">';
		$this->doc->backPath = '';
		$this->backRef = t3lib_div::GPvar("backRef") ? t3lib_div::GPvar("backRef") : "window.opener";
		$this->formName = t3lib_div::GPvar("formName");
		$this->formRef = $this->backRef.'.document.'.$this->formName;
		$this->doc->JScode = '
		<script language="javascript" type="text/javascript">
			var serialNumber = "";
			function timeout_func()	{
				if ('.$this->backRef.' && '.$this->backRef.'.document && '.$this->formRef.')	{
					if ('.$this->formRef.'["_serialNumber"])	{
						if (serialNumber) {
							if ('.$this->formRef.'["_serialNumber"].value != serialNumber) {closePal(); return false;}
						} else {
							serialNumber = '.$this->formRef.'["_serialNumber"].value;
						}
					}
					window.setTimeout("timeout_func();",1*1000);
				} else closePal();
			}
			function closePal()	{
				'.(t3lib_div::GPvar("backRef")?'document.location="alt_topmenu_dummy.php";':'close();').'
			}
			timeout_func();
			onBlur="alert();";
		</script>
		';		
	}
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;

		$this->content="";
		$this->content.=$this->doc->startPage("TYPO3 Edit Palette");
		
		$inData = explode(":",t3lib_div::GPvar("inData"));
		
		// Begin edit:
		if (is_array($inData) && count($inData)==3)	{
			$tceforms = t3lib_div::GPvar("backRef") ? new formRender() : new formRender_vert();
			$tceforms->initDefaultBEMode();
			$tceforms->palFieldTemplate='###FIELD_PALETTE###';
			$tceforms->palettesCollapsed=0;
			$tceforms->isPalettedoc=$this->backRef;
		
			$tceforms->formName = $this->formName;
			$tceforms->prependFormFieldNames = t3lib_div::GPvar("prependFormFieldNames");
			
			$table=$inData[0];
			$theUid=$inData[1];
			$thePalNum = $inData[2];
			$rec = t3lib_div::GPvar("rec",1);
			$rec["uid"]=$theUid;
			
		//debug($HTTP_GET_VARS);
		
			$panel.=$tceforms->getPaletteFields($table,$rec,$thePalNum,"",implode(",",array_keys($rec)));
			$formContent='<table border=0 cellpadding=0 cellspacing=0>'.$panel.'</table>';
		
			$this->content.=$tceforms->printNeededJSFunctions_top().$formContent.$tceforms->printNeededJSFunctions();
		}
	}
	function printContent()	{
		global $SOBE;
		echo $this->content.$this->doc->endPage();
	}
}

// Include extension?
if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/alt_palette.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/alt_palette.php"]);
}












// Make instance:
$SOBE = t3lib_div::makeInstance("SC_alt_palette");
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>