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
 * Wizard to edit records to a table
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
 *   74: class SC_wizard_edit 
 *   80:     function init()	
 *   89:     function main()	
 *
 * TOTAL FUNCTIONS: 2
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

 

$BACK_PATH='';
require ('init.php');
require ('template.php');
include ('sysext/lang/locallang_wizards.php');
require_once (PATH_t3lib.'class.t3lib_loaddbgroup.php');












/**
 * Script Class
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_wizard_edit {
	var $P;
		
	/**
	 * @return	[type]		...
	 */
	function init()	{
		$this->P = t3lib_div::GPvar('P',1);
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;

		if (t3lib_div::GPvar("doClose"))	{
			echo '<script language="javascript" type="text/javascript">close();</script>';
		} else {
			$table = $this->P["table"];
			$field = $this->P["field"];
			t3lib_div::loadTCA($table);
			$config = $TCA[$table]["columns"][$field]["config"];
			$fTable = $this->P["currentValue"]<0 ? $config["neg_foreign_table"] : $config["foreign_table"];
	
			if (is_array($config) && $config["type"]=="select" && !$config["MM"] && t3lib_div::testInt($this->P["currentValue"]) && $this->P["currentValue"] && $fTable)	{
				header("Location: ".t3lib_div::locationHeaderUrl("alt_doc.php?returnUrl=".rawurlencode("wizard_edit.php?doClose=1")."&edit[".$fTable."][".$this->P["currentValue"]."]=edit"));
			} elseif (is_array($config) && $this->P["currentSelectedValues"] && (($config["type"]=="select" && $config["foreign_table"]) || ($config["type"]=="group" && $config["internal_type"]=="db")))	{
				$allowedTables = $config["type"]=="group" ? $config["allowed"] : $config["foreign_table"].",".$config["neg_foreign_table"];
				//$prependName = $config["type"]=="group" ? $config["prepend_tname"] : $config["neg_foreign_table"];
				$prependName=1;
				$dbAnalysis = t3lib_div::makeInstance("t3lib_loadDBGroup");
				$dbAnalysis->start($this->P["currentSelectedValues"],$allowedTables);
				$value = $dbAnalysis->getValueArray($prependName);
				reset($value);
				$params="";
				while(list(,$rec)=each($value))	{
					$recTableUidParts = t3lib_div::revExplode("_",$rec,2);
					$params.="&edit[".$recTableUidParts[0]."][".$recTableUidParts[1]."]=edit";
				}
				header("Location: ".t3lib_div::locationHeaderUrl("alt_doc.php?returnUrl=".rawurlencode("wizard_edit.php?doClose=1").$params));
			} else {
				echo '<script language="javascript" type="text/javascript">close();</script>';
			}
		}
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/wizard_edit.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/wizard_edit.php']);
}












// Make instance:
$SOBE = t3lib_div::makeInstance('SC_wizard_edit');
$SOBE->init();
$SOBE->main();
?>