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
 * Wrapped for Kickstarter extension used with extension repository...
 *
 * $Id$
 * Revised for TYPO3 3.6 November/2003 by Kasper Skaarhoj
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */


require_once(t3lib_extMgm::extPath('extrep_wizard').'pi/class.tx_extrepwizard.php');

/**
 * Wrapped for Kickstarter extension used with extension repository...
 * (Originally the Kickstarter was designed to run from the frontend of the typo3.org website rather than from the backend of TYPO3! This is why we have to wrap the frontend plugin class in this way to fit it into the backend!)
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class em_kickstarter extends tx_extrepwizard {
	
	/**
	 * Setting internal PI input data.
	 *
	 * @return	void
	 */
	function getPIdata() {
		$this->piData = t3lib_div::_GP($this->varPrefix);
	}

	/**
	 * Getting link to this page + extra parameters, we have specified
	 *
	 * @param	array		Additional parameters specified.
	 * @return	string		The URL
	 */
	function linkThisCmd($uPA=array())	{
		$url = t3lib_div::linkThisScript($uPA);
		return $url;
	}

	/**
	 * Font wrap function; Wrapping input string in a <span> tag with font family and font size set
	 *
	 * @param	string		Input value
	 * @return	string		Wrapped input value.
	 */
	function fw($str)	{
		return '<span style="font-family:verdana,arial,sans-serif; font-size:10px;">'.$str.'</span>';
	}

	/**
	 * [Not active... - do not use]
	 *
	 * @return	void
	 */
	function makeRepositoryUpdateArray()	{
		debug('not active in EM');
	}

	/**
	 * Returns value from the fe_users field (faking that data...)
	 *
	 * @param	string		Field name
	 * @return	string		The faked field value
	 */
	function userField($fN)	{
		switch($fN)	{
			case 'name':
				return $GLOBALS['BE_USER']->user['realName'];
			break;
			case 'email':
				return $GLOBALS['BE_USER']->user['email'];
			break;
			case 'username':
				return $GLOBALS['BE_USER']->user['username'];
			break;
		}
	}
}
	
	
// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/mod/tools/em/class.kickstarter.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/mod/tools/em/class.kickstarter.php']);
}
?>
