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
 * Module: Func / Export
 *
 * Include-file
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 */

 
 
require_once (PATH_t3lib."class.t3lib_loaddbgroup.php");
require_once (PATH_t3lib."class.t3lib_exportdata.php");


class export {
	function main ()	{
		global $SOBE,$AB,$BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;
		
		$export = t3lib_div::makeInstance("t3lib_exportData");
		$export->script="index.php";
		$export->backPath=$BACK_PATH;
		$export->id=$SOBE->id;
		
	
	// **************************
	// Main
	// **************************
		$extension = $GLOBALS["SOBE"]->MOD_SETTINGS["export_function"];
		$destination="";
		if (trim($GLOBALS["SOBE"]->MOD_SETTINGS["export_filename"]))	{
			$theFile = trim($GLOBALS["SOBE"]->MOD_SETTINGS["export_filename"]).".".$extension;
			$destination = $GLOBALS["SOBE"]->fileProcessor->findTempFolder()."/".$theFile;
		}
	
		// FILE exists:
		// Export command:
		if ($GLOBALS["SOBE"]->CMD=="export" && $destination && !@file_exists($destination)) 	{
			$export->startExport($HTTP_POST_VARS["records"]);
			$export->write(1,$destination);
			debug("export!: ".$destination);
		}
		
	
		if ($extension)	{
			$Nmenu[]= array(fw("Destination:"),t3lib_BEfunc::getFuncMenu($SOBE->id,"SET[export_destination]",$GLOBALS["SOBE"]->MOD_SETTINGS["export_destination"],$GLOBALS["SOBE"]->MOD_MENU["export_destination"]));
			$Nmenu[]= array(fw("Filename:"),t3lib_BEfunc::getFuncInput($SOBE->id,"SET[export_filename]",$GLOBALS["SOBE"]->MOD_SETTINGS["export_filename"],30).fw("&nbsp;.".$extension));
			
			$info="";
			if (trim($GLOBALS["SOBE"]->MOD_SETTINGS["export_filename"]))	{
				if (@file_exists($destination))	{
					$info="<b>The file '".$theFile."' exists already! Enter another name.</b>";
				} else {
					// ...OK
				}
			} else {
				$info="<b>Enter a filename!</b>";
			}
			
	
	
	
			$theOutput.=$SOBE->doc->section("Select file destination:",$SOBE->doc->menuTable($Nmenu).fw($info));
			$theOutput.=$SOBE->doc->divider(5);
	
			if (isset($HTTP_GET_VARS["exportRecords"]))	{
				$theOutput.=$SOBE->doc->section("ExPORT:", $HTTP_GET_VARS["exportRecords"]);
			} else {
				switch($extension)	{
					case "trd":
					break;
					case "html":
					break;
					case "csv":
					break;
					default:
						$extension = "";
					break;
				}
				if ($extension)	{
					$Nmenu= "Records: ".t3lib_BEfunc::getFuncMenu($SOBE->id,"SET[export_source]",$GLOBALS["SOBE"]->MOD_SETTINGS["export_source"],$GLOBALS["SOBE"]->MOD_MENU["export_source"]);
					$theOutput.=$SOBE->doc->section("Select record source:",'<NOBR>'.$Nmenu.'</NOBR>');
					
					if ($GLOBALS["SOBE"]->MOD_SETTINGS["export_source"])	{
						$theOutput.=$SOBE->doc->section("",$export->getSource($GLOBALS["SOBE"]->MOD_SETTINGS["export_source"]));
						if (count($export->recordsToLoad_fields))	{		// If there was records...
							$msg="";
							$msg.= '<BR><BR><input type="hidden" name="id" value="'.$GLOBALS["SOBE"]->id.'">';
							$msg.= '<input type="hidden" name="CMD" value="export">';
							$msg.= '<input type="hidden" name="records" value="'.implode($export->recordsToLoad_fields,",").'">';
							$msg.= '<input type="Submit" value="Save records to file">';
							$theOutput.=$SOBE->doc->section("EXPORT RECORDS",$msg);
						}
					}
				}
			}
		}

		return $theOutput;
	}
}

// Include extension?
if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/mod/web/func/class.export.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/mod/web/func/class.export.php"]);
}

?>