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
 * Wizard to add new records to a table
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 *
 */

 

$BACK_PATH="";
require ("init.php");
require ("template.php");
include ("sysext/lang/locallang_wizards.php");


// ***************************
// Script Classes
// ***************************
class SC_wizard_add {
	var $include_once=array();
	var $content;

	var $returnEditConf;
	var $processDataFlag=0;
	var $P;
	var $pid;
	var $table;
	var $id;
	
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;

		$this->P = t3lib_div::GPvar("P",1);
		//debug($this->P);

		// Get this record
		$origRow = t3lib_BEfunc::getRecord($this->P["table"],$this->P["uid"]);
		
		// Get TSconfig for it.
		$TSconfig = t3lib_BEfunc::getTCEFORM_TSconfig($this->table,is_array($origRow)?$origRow:array("pid"=>$this->P["pid"]));
		// Set [params][pid]
		if (substr($this->P["params"]["pid"],0,3)=="###" && substr($this->P["params"]["pid"],-3)=="###")	{
			$this->pid = intval($TSconfig["_".substr($this->P["params"]["pid"],3,-3)]);
		} else $this->pid = intval($this->P["params"]["pid"]);

			// Return if new record
		if (!strcmp($this->pid,""))	{
			header("Location: ".t3lib_div::locationHeaderUrl($this->P["returnUrl"])); 
			exit;
		}	

			// Else proceed.
		$this->returnEditConf = t3lib_div::GPvar("returnEditConf");
		if ($this->returnEditConf)	{
			$eC = unserialize(t3lib_div::GPvar("returnEditConf"));
			$this->table = $this->P["params"]["table"];
			if (is_array($eC[$this->table]) && t3lib_div::testInt($this->P["uid"]))	{
				reset($eC[$this->table]);
				$this->id = intval(key($eC[$this->table]));
				$cmd = current($eC[$this->table]);
				if ($this->P["params"]["setValue"] && $cmd=="edit" && $this->id && $this->P["table"] && $this->P["field"] && $this->P["uid"])	{
					$this->include_once[]=PATH_t3lib."class.t3lib_loaddbgroup.php";
					$this->include_once[]=PATH_t3lib."class.t3lib_transferdata.php";
					$this->include_once[]=PATH_t3lib."class.t3lib_tcemain.php";
					$this->processDataFlag=1;
				}
			}
		}
	}
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;



			// Else proceed.
		if ($this->returnEditConf)	{
			if ($this->processDataFlag)	{
				$trData = t3lib_div::makeInstance("t3lib_transferData");
				$trData->fetchRecord($this->P["table"],$this->P["uid"],"");	// "new"
				reset($trData->regTableItems_data);
				$current = current($trData->regTableItems_data);
				
				if (is_array($current))	{
					$tce = t3lib_div::makeInstance("t3lib_TCEmain");
					$data=array();
					$addEl = $this->table."_".$this->id;
					switch((string)$this->P["params"]["setValue"])	{
						case "set":
							$data[$this->P["table"]][$this->P["uid"]][$this->P["field"]]=$addEl;
						break;
						case "prepend":
							$data[$this->P["table"]][$this->P["uid"]][$this->P["field"]] = $current[$this->P["field"]].",".$addEl;
						break;
						case "append":
							$data[$this->P["table"]][$this->P["uid"]][$this->P["field"]] = $addEl.",".$current[$this->P["field"]];
						break;
					}
					$data[$this->P["table"]][$this->P["uid"]][$this->P["field"]] = implode(",",t3lib_div::trimExplode(",",$data[$this->P["table"]][$this->P["uid"]][$this->P["field"]],1));
					
					$tce->start($data,array());
					$tce->stripslashes_values=0;
					$tce->process_datamap();
				}
			}
			header("Location: ".t3lib_div::locationHeaderUrl($this->P["returnUrl"]));
		} else {
			header("Location: ".t3lib_div::locationHeaderUrl("alt_doc.php?returnUrl=".rawurlencode(t3lib_div::getIndpEnv("REQUEST_URI"))."&returnEditConf=1&edit[".$this->P["params"]["table"]."][".$this->pid."]=new"));
		}
	}
}

// Include extension?
if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/wizard_add.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/wizard_add.php"]);
}












// Make instance:
$SOBE = t3lib_div::makeInstance("SC_wizard_add");
$SOBE->init();

// Include files?
reset($SOBE->include_once);	
while(list(,$INC_FILE)=each($SOBE->include_once))	{include_once($INC_FILE);}

$SOBE->main();
$SOBE->printContent();
?>