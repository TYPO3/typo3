<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2002
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
 *
 * 
 *
 * @author	
 */


class existingHTMLimport {
	function main()	{
		global $SOBE,$SOBE;
		
		$theOutput.=$SOBE->doc->section("Header",'PID: '.$SOBE->id);
		$theOutput.=$SOBE->doc->divider(5);
		
		$message="
		This module is not published yet.
		
		The module will not be able to import HTML code into nice editable content types like 'text', 'image', ...!!!
		
		It imports the HTML pages into Typo3 as pages with a content record type 'HTML'. The module try to preserve the website structure. Additionally you can put template markers into the HTML code during import.
		
		If you need a solution to import more that 20 HTML pages and you like to test the module then ask:
		
		René Fritz r.fritz@colorcube.de";
		
		
			$theOutput.=$SOBE->doc->section("NOTICE",nl2br($message));
			$theOutput.=$SOBE->doc->divider(5);
		return $theOutput;
	}
}

// Include extension?
if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/mod/web/func/class.existingHTMLimport.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/mod/web/func/class.existingHTMLimport.php"]);
}

?>