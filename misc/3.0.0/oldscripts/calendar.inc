<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2003 Kasper Skårhøj (kasper@typo3.com)
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is 
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
 * calendar.inc
 *
 * Creates a calendar
 * 
 * NOTICE: This script does not represent the most effective way to insert custom functionality in Typo3 page. Please use the USER object with a class instead.
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 */

 
$content="";

// *************************************
// *** getting configuration values:
// *************************************

		// getting configuration values:
	$config["pid_list"] = trim($this->stdWrap($conf["pid_list"],$conf["pid_list."]));
	$config["pid_list"] = $config[pid_list] ? $config["pid_list"] : $GLOBALS["TSFE"]->id;

	$config["recursive"] = $this->stdWrap($conf["recursive"],$conf["recursive."]);

	$config["font"] = $this->stdWrap($conf["fontFace"],$conf["fontFace."]);
	$config["font"] = $config["font"] ? $config["font"] : "verdana";

		// If the current record should be displayed.
	$config["displayCurrentRecord"] = $conf["displayCurrentRecord"];

	
	$templateCode = $this->fileResource($conf["templateFile"]);

		// Fetching catagories:
	$categories = Array();
 	$query = "select * from tt_calender_cat where 1=1".$this->enableFields("tt_calender_cat");
	$res = mysql(TYPO3_db,$query);
	echo mysql_error();
	while($row = mysql_fetch_assoc($res))	{
		$categories[$row[uid]] = $row[title];
	}	

	
		// making query:
	$selectConf = Array();
	if ($config[recursive])	{		// get pid-list if recursivity is enabled
		$pid_list_arr = explode(",",$config[pid_list]);
		$orig_pids = $config[pid_list];
		$config[pid_list]="";
		while(list(,$val)=each($pid_list_arr))	{
			$config[pid_list].=$this->getTreeList($val,intval($config["recursive"]));
		}
		$config[pid_list].=$orig_pids;
	}
	$selectConf["pidInList"] = $config[pid_list];
	$selectConf["orderBy"] = "date,time";
	$selectConf["where"] = "type=0";
	if (intval($conf["maxAge"]))	{
		$selectConf["where"].= " AND date>".(time()-(3600*24*intval($conf["maxAge"])));
	}

		// performing query:
 	$query = $this->getQuery("tt_calender",$selectConf);
	$res = mysql(TYPO3_db,$query);
	echo mysql_error();
	
		// traversing the data.
	$tableRows = "";
	$day_base = mktime (0,0,0,date("m"),date("d"),date("Y"));

		// config af template:
	$tConf["template"] ="HTML";
	$tConf["template."]["value"] = $templateCode;
	
	while($config["displayCurrentRecord"] || $row = mysql_fetch_assoc($res))	{
		if ($config["displayCurrentRecord"])	{$row = $this->data;}
			// Insering date header:			
		$tConf["workOnSubpart"] = "DATE_HEADER";
		$tConf["marks."] = Array ();
		$tConf["marks."]["DATE"]="HTML";
		$tConf["marks."]["DATE."]["value"]=date("d/m Y",$row[date]);
		$tConf["marks."]["HEADER"]="HTML";
		$tConf["marks."]["HEADER."]["value"]=$row[title];
		$tConf["marks."]["FONT"]="HTML";
		$tConf["marks."]["FONT."]["value"]=$config[font];
		$tableRows.=$this->TEMPLATE($tConf);

		
		$parts = explode(chr(10)."---".chr(10), str_replace(chr(13),"",$row[note]));
		while(list(,$pcon)=each($parts))	{
			$pcon=trim($pcon);
			$pcon_arr=split(chr(10),$pcon,2);
			$theTime = "";

			$hP=explode(" ",$pcon_arr[0],2);
			if (ereg("[^0-9,.:;-]",$hP[0]))	{
				$title = trim($pcon_arr[0]);
			} else {
				$title = trim($hP[1]);
				$theTime = trim($hP[0]);
			}
			if ($conf["parseFunc."]) {
				$title = $this->parseFunc($title,$conf["parseFunc."]);
				$pcon_arr[1] = $this->parseFunc($pcon_arr[1],$conf["parseFunc."]);
			}
			$theContent = "<b>".$title."</b><BR>".nl2br($pcon_arr[1]);

				// Insering date header:			
			$tConf["workOnSubpart"] = "DATE_CONTENT";
			$tConf["marks."] = Array ();
			$tConf["marks."]["TIME"]="HTML";
			$tConf["marks."]["TIME."]["value"]= $theTime;
			$tConf["marks."]["CONTENT"]="HTML";
			$tConf["marks."]["CONTENT."]["value"]=$theContent;
			$tConf["marks."]["FONT"]="HTML";
			$tConf["marks."]["FONT."]["value"]=$config[font];
			$tableRows.=$this->TEMPLATE($tConf);
	
		}

		$this->lastChanged($row[tstamp]);

		if ($config["displayCurrentRecord"])	{break;}	// Must exit forcibly or we'll have an eternal loop.
	}

	$tConf["workOnSubpart"] = "TEMPLATE_CALENDAR";
	$tConf["marks."] = Array ();
	$tConf["subparts."] = Array ();
	$tConf["subparts."]["TABLE_CONTENT"]="HTML";
	$tConf["subparts."]["TABLE_CONTENT."]["value"]= $tableRows;


	$content = $this->TEMPLATE($tConf);


$RESTORE_OLD_DATA = 1;		// secures a restore of the original ->data-array

?>