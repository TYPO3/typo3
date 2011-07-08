<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Logout script for the backend
 * This script saves the interface positions and calls the closeTypo3Windows in the frameset
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */

require ('init.php');





/**
 * Script Class for logging a user out.
 * Does not display any content, just calls the logout-function for the current user and then makes a redirect.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_logout {

	/**
	 * Performs the logout processing
	 *
	 * @return	void
	 */
	function logout()	{
			// Logout written to log
		$GLOBALS['BE_USER']->writelog(255, 2, 0, 1, 'User %s logged out from TYPO3 Backend', array($GLOBALS['BE_USER']->user['username']));
		t3lib_formProtection_Factory::get()->removeSessionTokenFromRegistry();
		$GLOBALS['BE_USER']->logoff();
		$redirect = t3lib_div::sanitizeLocalUrl(t3lib_div::_GP('redirect'));
		$redirectUrl = $redirect ? $redirect : 'index.php';
		t3lib_utility_Http::redirect($redirectUrl);
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/logout.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/logout.php']);
}



// Make instance:
$SOBE = t3lib_div::makeInstance('SC_logout');
$SOBE->logout();

?>