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
 * Wizard to list records
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
class SC_wizard_list {
	var $P;
	var $pid;
	var $table;
	
	function init()	{
		$this->P = t3lib_div::GPvar("P",1);
	}
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;
		
		$this->table = t3lib_div::GPvar("table");

		// Get this record
		$origRow = t3lib_BEfunc::getRecord($this->P["table"],$this->P["uid"]);
		
		// Get TSconfig for it.
		$TSconfig = t3lib_BEfunc::getTCEFORM_TSconfig($this->table,is_array($origRow)?$origRow:array("pid"=>$this->P["pid"]));
		// Set [params][pid]
		if (substr($this->P["params"]["pid"],0,3)=="###" && substr($this->P["params"]["pid"],-3)=="###")	{
			$this->pid = intval($TSconfig["_".substr($this->P["params"]["pid"],3,-3)]);
		} else $this->pid = intval($this->P["params"]["pid"]);
		
		if (!strcmp($this->pid,"") || strcmp(t3lib_div::GPvar("id"),""))	{
			header("Location: ".t3lib_div::locationHeaderUrl($this->P["returnUrl"]));
		} else {
			header("Location: ".t3lib_div::locationHeaderUrl("db_list.php?id=".$this->pid."&table=".$this->P["params"]["table"]."&returnUrl=".rawurlencode(t3lib_div::getIndpEnv("REQUEST_URI"))));
		}
	}
}

// Include extension?
if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/wizard_list.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/wizard_list.php"]);
}












// Make instance:
$SOBE = t3lib_div::makeInstance("SC_wizard_list");
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>