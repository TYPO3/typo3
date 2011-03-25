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
 *   59: class tx_openid_return
 *   65:     public function main()
 *
 * TOTAL FUNCTIONS: 1
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

// Fix _GET/_POST values for authentication
if (isset($_GET['login_status'])) {
	$_POST['login_status'] = $_GET['login_status'];
}

define('TYPO3_MOD_PATH', 'sysext/openid/');
require_once('../../init.php');

/**
 * This class is the OpenID return script for the TYPO3 Backend.
 *
 * @author	Dmitry Dulepov <dmitry@typo3.org>
 */
class tx_openid_return {
	/**
	* Processed Backend session creation and redirect to backend.php
	*
	* @return	void
	*/
	public function main() {
		if ($GLOBALS['BE_USER']->user['uid']) {
			t3lib_div::cleanOutputBuffers();
			$backendURL = t3lib_div::getIndpEnv('TYPO3_SITE_URL') . TYPO3_mainDir . 'backend.php';
			t3lib_utility_Http::redirect($backendURL);
		}
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/openid/class.tx_openid_return.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/openid/class.tx_openid_return.php']);
}

$module = t3lib_div::makeInstance('tx_openid_return');
/* @var tx_openid_return $module */
$module->main();

?>
