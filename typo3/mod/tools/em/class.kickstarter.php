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
 * Extension for Kickstarter extension used with extension repository...
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 */


require_once(t3lib_extMgm::extPath("extrep_wizard")."pi/class.tx_extrepwizard.php");

class em_kickstarter extends tx_extrepwizard {
	

	function getPIdata() {
		$this->piData = t3lib_div::GPvar($this->varPrefix,1);
	}
	function linkThisCmd($uPA=array())	{
			// Getting link to this page + extra parameters, we have specified
		$url = t3lib_div::linkThisScript($uPA);
		return $url;
	}
	function fw($str)	{
		return '<span style="font-family:verdana,arial,sans-serif; font-size:10px;">'.$str.'</span>';
	}
	function makeRepositoryUpdateArray()	{
		debug("not active in EM");
	}
	function userField($fN)	{
		switch($fN)	{
			case "name":
				return $GLOBALS["BE_USER"]->user["realName"];
			break;
			case "email":
				return $GLOBALS["BE_USER"]->user["email"];
			break;
			case "username":
				return $GLOBALS["BE_USER"]->user["username"];
			break;
		}
	}
}
	
	
// Include extension?
if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/mod/tools/em/class.kickstarter.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/mod/tools/em/class.kickstarter.php"]);
}

?>