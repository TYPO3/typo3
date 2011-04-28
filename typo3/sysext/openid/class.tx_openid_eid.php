<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2011 Dmitry Dulepov <dmitry@typo3.org>
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   44: class tx_openid_eID
 *   50:     public function main()
 *
 * TOTAL FUNCTIONS: 1
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

/**
 * This class is the OpenID return script for the TYPO3 Frontend.
 *
 * @author	Dmitry Dulepov <dmitry@typo3.org>
 */
class tx_openid_eID {
	/**
	* Processes eID request.
	*
	* @return	void
	*/
	public function main() {
		// Due to the nature of OpenID (redrections, etc) we need to force user
		// session fetching if there is no session around. This ensures that
		// our service is called even if there is no login data in the request.
		// Inside the service we will process OpenID response and authenticate
		// the user.
		$GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['FE_fetchUserIfNoSession'] = TRUE;

		// Initialize Frontend user
		tslib_eidtools::connectDB();
		tslib_eidtools::initFeUser();

		// Redirect to the original location in any case (authenticated or not)
		@ob_end_clean();
		t3lib_utility_Http::redirect(t3lib_div::_GP('tx_openid_location'), t3lib_utility_Http::HTTP_STATUS_303);
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/openid/class.tx_openid_eid.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/openid/class.tx_openid_eid.php']);
}

$module = t3lib_div::makeInstance('tx_openid_eID');
/* @var tx_openid_eID $module */
$module->main();

?>