<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2003-2004 Robert Lemke (robert@typo3.org)
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Plugin 'ADOdb' for the 'adodb' extension.
 *
 * @author	Robert Lemke <robert@typo3.org>
 */


require_once(PATH_tslib.'class.tslib_pibase.php');
include_once(t3lib_extMgm::extPath('adodb').'adodb/adodb.inc.php');

class tx_adodb_pi1 extends tslib_pibase {
	var $prefixId = 'tx_adodb_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_adodb_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey = 'adodb';	// The extension key.

	/**
	 * Void main function
	 *
	 * @param	string		$content: Some content
	 * @param	array		$conf: Some configuration
	 * @return	void
	 */
	function main($content,$conf)	{
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/adodb/pi1/class.tx_adodb_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/adodb/pi1/class.tx_adodb_pi1.php']);
}

?>