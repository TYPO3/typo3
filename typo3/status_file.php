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
 * TCE status transfer, file handling
 *
 * This script reads the last fileop. entry in the sys_log and updates the interface if necessary
 * If any fileoperation errors was found in the last entry regarding fileoperations (type=2) they are output in an alert-box.
 * if you set the GET-var $clipboard true, then JavaScript-code updating the clipboard will be generated...
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 *
 */

 
 

$BACK_PATH="";
require ("init.php");
require ('template.php');
require_once (PATH_t3lib."class.t3lib_bedisplaylog.php");




// ***************************
// Script Classes
// ***************************
class SC_status_file {
	var $content;
	
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;
		global $editwin,$clipboard;

		// ***************************
		// Registering Incoming data
		// ***************************
		t3lib_div::setGPvars("editwin,clipboard");
		
		// ***************************
		// Classes and functions
		// ***************************
		$lF = t3lib_div::makeInstance("t3lib_BEDisplayLog");
		$lF->stripPath=1;	// This strips the path from any value in the data-array when the data-array is parsed through stripPath()
		
		
		// ***************************
		// Beginning status
		// ***************************
		$res=mysql(TYPO3_db,"SELECT tstamp FROM sys_log WHERE userid=".$BE_USER->user["uid"]." AND type=2 ORDER BY uid DESC LIMIT 1");		// Get latest timestamp
		$row=mysql_fetch_assoc($res);
		$lastTime=$row["tstamp"];	// this is the timestamp for the last entry in the log by this user.
		
		$clipJS='';
		$JS='';
		$errorJS='';
		$RT="";
		$RL="";
		
		if ($lastTime+10 > $GLOBALS["EXEC_TIME"])	{	// This comparison is a simple security that we're not dealing with an old log-entry. If the entry is more than 10 seconds old, it's regarded too old...
			// Fetching all entries for this user with this timestamp, $lastTime
			$res=mysql(TYPO3_db,"SELECT * FROM sys_log WHERE tstamp=".$lastTime." AND type=2 AND userid=".$BE_USER->user["uid"]." ORDER BY uid");
			while($row=mysql_fetch_assoc($res))	{
				if ($row["error"])	{
					$errorJS.=$row["type"]."-".$row["action"]."-".$row["details_nr"].": ".$lF->getDetails(0,$row["details"],$lF->stripPath(unserialize($row["log_data"])))."\n";
				}
				if ($clipboard)	{
					if ($row["action"]."-".$row["details_nr"] == "1-1")		{	// If the entry describes an upload
						$data = unserialize($row["log_data"]);
						$theFile = t3lib_div::split_fileref($data[1]);	// The ref. to the uploaded file is stored here.
						$ext = $theFile[fileext];
						$icon = t3lib_BEfunc::getFileIcon($ext);
						$clipJS.='
						clipBrd.aI(pad, "", "'.t3lib_div::shortMD5($theFile[path].$theFile[file]).'", "file", "'.$theFile[file].'", "'.$theFile[path].$theFile[file].'", "'.$theFile[fileext].'", "'.$icon.'");';		// shortMD5 260900  
					}
				} elseif ($editwin) {
					if ($row["action"]."-".$row["details_nr"] == "1-1")		{	// If the entry describes an upload
						$data = unserialize($row["log_data"]);
						$theFile = t3lib_div::split_fileref($data[1]);	// The ref. to the uploaded file is stored here.
						list($table, $id, $field) = explode("__",$data[2]);
						t3lib_div::loadTCA($table);
						$extList = $TCA[$table]["columns"][$field]["config"]["allowed"];
						$disList = $TCA[$table]["columns"][$field]["config"]["disallowed"];
						if (
							($extList && ($extList=="*" || t3lib_div::inList($extList,$theFile["fileext"]))) || 
							($disList && $disList!="*" && !t3lib_div::inList($disList,$theFile["fileext"]))
							)	{
							$editJS.='
					parent.data.update("'.$table.'", "'.$id.'", "'.$field.'", parent.data.get("'.$table.'", "'.$id.'", "'.$field.'")+unescape("'.rawurlencode($theFile[path].$theFile[file]."|".$theFile[file]).'")+",", "client");';
						} else {
							$editJS.='
							alert("The extension ('.$theFile["fileext"].') of the uploaded file was not in this list:\n'.$extList.'");';
						
						}
					}
				} else {
					// Depending of the combination of action/detail-nr, we decide if the tree / list is updated upon return...
					$theKey=$row["action"]."-".$row["details_nr"];
					switch($theKey)	{
						case "1-1":
						case "2-1":
						case "3-1":
						case "4-1":
						case "5-1":
						case "7-1":
						case "8-1":
						case "9-1":
							$RL='refreshList(); ';
						break;
					}
					switch($theKey)	{
						case "2-2":
						case "3-2":
						case "4-2":
						case "4-3":
						case "5-2":
						case "6-1":
						case "7-1":
							$RT='refreshTree(); ';
						break;
					}
				}
			}
		}
		
		
		if ($clipboard) {
			$JS.='
			if (parent.typoWin)	{
				var clipBrd = parent.typoWin.clipBrd;
				var pad = clipBrd.pad;
			
				if (clipBrd.bankType[pad]!="file") {
					clipBrd.clr(pad);
				}
				'.$clipJS.'
				document.location = "clipboard_content.html";
			}
			';	
		} elseif ($editwin) {
			$JS.='
			if (parent.data)	{
				'.$editJS.'
				 document.location = "editwin_content.html";
			}
			';	
		}
		$JS.=$RT.$RL;
		
		
		// ***************************
		// Outputting update code
		// ***************************
		if ($errorJS)	{
			$JS.="alert(".$GLOBALS['LANG']->JScharCode($errorJS).");";
		}
		
		$this->content="";
		$this->content.='
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>File Status script</title>
</head>
<body bgcolor="#F7F3EF">

<script language="javascript" type="text/javascript">
if (top.busy)	{
	top.busy.loginRefreshed();
}

function refreshList()	{
	top.goToModule("file_list");
}
function refreshTree()	{
	top.goToModule("file_list");
}
'.$JS.'
</script>
</body>
</html>';

		t3lib_BEfunc::getSetUpdateSignal("updatePageTree");
	}
	function printContent()	{
		echo $this->content;
	}
}

// Include extension?
if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/status_file.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/status_file.php"]);
}












// Make instance:
$SOBE = t3lib_div::makeInstance("SC_status_file");
$SOBE->main();
$SOBE->printContent();
?>