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
 * Web>File: Create new folders in the filemounts
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 *
 */


$BACK_PATH="";
require ("init.php");
require ("template.php");
require_once (PATH_t3lib."class.t3lib_basicfilefunc.php");



// ***************************
// Script Classes
// ***************************
class SC_file_newfolder {
	var $content;
	var $number;
	var $basicff;
	var $shortPath;
	var $title;
	var $icon;
	var $folderNumber=10;
	var $target;
	var $doc;	

		// Constructor:
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;

		$this->number = t3lib_div::GPvar("number");
		$this->target = t3lib_div::GPvar("target");
		$this->basicff = t3lib_div::makeInstance("t3lib_basicFileFunctions");
		$this->basicff->init($GLOBALS["FILEMOUNTS"],$TYPO3_CONF_VARS["BE"]["fileExtensions"]);
		
		$this->target=$this->basicff->is_directory($this->target);		// Cleaning and checking target
		$key=$this->basicff->checkPathAgainstMounts($this->target."/");
		if (!$this->target || !$key)	{
			t3lib_BEfunc::typo3PrintError ("Parameter Error","Target was not a directory!","");
			exit;
		}
			// Finding the icon
		switch($GLOBALS["FILEMOUNTS"][$key]["type"])	{
			case "user":	$this->icon = "gfx/i/_icon_ftp_user.gif";	break;
			case "group":	$this->icon = "gfx/i/_icon_ftp_group.gif";	break;
			default:		$this->icon = "gfx/i/_icon_ftp.gif";	break;
		}
		$this->shortPath = substr($this->target,strlen($GLOBALS["FILEMOUNTS"][$key]["path"]));
		$this->title = $GLOBALS["FILEMOUNTS"][$key]["name"].": ".$this->shortPath;
		
		// ***************************
		// Setting template object
		// ***************************
		$this->doc = t3lib_div::makeInstance("smallDoc");
		$this->doc->backPath = $BACK_PATH;
		$this->doc->JScode='
		<script language="javascript" type="text/javascript">
			var path = "'.$this->target.'";
		
			function reload(a)	{
				if (!changed || (changed && confirm("'.$LANG->sL("LLL:EXT:lang/locallang_core.php:mess.redraw").'")))	{
					var params = "&target="+escape(path)+"&number="+a; 
					document.location = "file_newfolder.php?"+params;
				}
			}
			function backToList()	{
				top.goToModule("file_list");
			}
			
			var changed = 0;
			
		</script>
		';
		$this->doc->form='<form action="tce_file.php" method="POST" name="editform">';
	}
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;

		$this->content="";
		$this->content.=$this->doc->startPage($LANG->sL("LLL:EXT:lang/locallang_core.php:file_newfolder.php.pagetitle"));
		$this->content.=$this->doc->header($LANG->sL("LLL:EXT:lang/locallang_core.php:file_newfolder.php.pagetitle"));
		$this->content.=$this->doc->spacer(5);
		$this->content.=$this->doc->section('',$this->doc->getFileheader($this->title,$this->shortPath,$this->icon));
		$this->content.=$this->doc->divider(5);
		
		
			// making the selector box
		$this->number = t3lib_div::intInRange($this->number,1,10);
		$code='<select name="number" onChange="reload(this.options[this.selectedIndex].value);">';
		for ($a=1;$a<=$this->folderNumber;$a++)	{
			$code.='<option value="'.$a.'"'.($this->number==$a?' selected':'').'>'.$a.' '.$LANG->sL("LLL:EXT:lang/locallang_core.php:file_newfolder.php.folders").'</option>';
		}
		$code.='</select><BR><BR>';
		
		for ($a=0;$a<$this->number;$a++)	{
			$code.='<input'.$GLOBALS["TBE_TEMPLATE"]->formWidth(20).' type="Text" name="file[newfolder]['.$a.'][data]" onChange="changed=true;"><input type="Hidden" name="file[newfolder]['.$a.'][target]" value="'.$this->target.'"><BR>';
		}
		$code.='<BR><input type="Submit" value="'.$LANG->sL("LLL:EXT:lang/locallang_core.php:file_newfolder.php.submit").'">&nbsp;&nbsp;<input type="Submit" value="'.$LANG->sL("LLL:EXT:lang/locallang_core.php:labels.cancel").'" onClick="backToList(); return false;">';
		$this->content.= $this->doc->section("",$code);
		
		
		$this->content.= $this->doc->spacer(10);
		$this->content.= $this->doc->middle();

		// new file:
		$code='</form><form action="tce_file.php" method="POST" name="editform2">';
		$code.='<input'.$GLOBALS["TBE_TEMPLATE"]->formWidth(20).' type="Text" name="file[newfile][0][data]" onChange="changed=true;"><input type="Hidden" name="file[newfile][0][target]" value="'.$this->target.'"><BR>';
		$code.='<BR><input type="Submit" value="'.$LANG->sL("LLL:EXT:lang/locallang_core.php:file_newfolder.php.newfile_submit").'">&nbsp;&nbsp;<input type="Submit" value="'.$LANG->sL("LLL:EXT:lang/locallang_core.php:labels.cancel").'" onClick="backToList(); return false;">';
		$this->content.= $this->doc->section($LANG->sL("LLL:EXT:lang/locallang_core.php:file_newfolder.php.newfile"),$code);
	}
	function printContent()	{
		global $SOBE;

		$this->content.= $this->doc->middle();
		$this->content.= $this->doc->endPage();
		echo $this->content;
	}
}

// Include extension?
if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/file_newfolder.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/file_newfolder.php"]);
}












// Make instance:
$SOBE = t3lib_div::makeInstance("SC_file_newfolder");
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>