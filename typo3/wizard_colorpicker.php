<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2004 Kasper Skaarhoj (kasper@typo3.com)
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
 * Color picker wizard
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 *
 */

 

$BACK_PATH="";
require ("init.php");
require ("template.php");



// ***************************
// Script Classes
// ***************************
class SC_wizard_colorpicker {
	var $content;
	var $P;
	var $doc;	
	
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		$this->P = t3lib_div::_GP('P');
		
		unset($this->P["fieldChangeFunc"]["alert"]);
		reset($this->P["fieldChangeFunc"]);
		$update="";
		while(list($k,$v)=each($this->P["fieldChangeFunc"]))	{
			$update.= "
			window.opener.".$v;
		}
		
		
		$this->doc = t3lib_div::makeInstance("template");
		$this->doc->backPath = $BACK_PATH;
		$this->doc->JScode='
		<script language="javascript" type="text/javascript">
			function checkReference()	{
				if (window.opener && window.opener.document && window.opener.document.'.$this->P["formName"].' && window.opener.document.'.$this->P["formName"].'["'.$this->P["itemName"].'"] )	{
					return window.opener.document.'.$this->P["formName"].'["'.$this->P["itemName"].'"];
				} else {
					close();
				}
			}
			function changeBGcolor(color) {
			    if (window.opener.document.layers)
			        window.opener.document.layers["'.$this->P["md5ID"].'"].bgColor = color;
			    else if (window.opener.document.all)
			        window.opener.document.all["'.$this->P["md5ID"].'"].style.background = color;
			}	
			function setValue(input)	{
				var field = checkReference();
				if (field)	{
					field.value = input;
					'.$update.'
					changeBGcolor(input);
				}
			}
			function getValue()	{
				var field = checkReference();
				return field.value;
			}
		</script>
		';
		
		
		$this->content.=$this->doc->startPage("Color Picker");
	}
	function main()	{
		global $SOBE;

		$this->content.='Color picker
		<form action="">
		<input type="text" name="test" onChange="setValue(this.value);">
		</form>
		
		<script language="javascript" type="text/javascript">
			document.forms[0].test.value = getValue();
		</script>
		
		';
		$this->content.=$this->doc->endPage();
	}
	function printContent()	{
		echo $this->content;
	}
}

// Include extension?
if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/wizard_colorpicker.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/wizard_colorpicker.php"]);
}












// Make instance:
$SOBE = t3lib_div::makeInstance("SC_wizard_colorpicker");
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>