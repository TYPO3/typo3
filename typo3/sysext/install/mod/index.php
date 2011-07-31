<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Steffen Gebert <steffen.gebert@typo3.org>
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
 * Hint: use extdeveval to insert/update function index above.
 */


require_once(PATH_t3lib . 'class.t3lib_scbase.php');

	// This checks permissions and exits if the users has no permission for entry.
$BE_USER->modAccess($MCONF, 1);


/**
 * Module 'Install Tool' for the 'install' extension.
 *
 * @author	Steffen Gebert <steffen.gebert@typo3.org>
 * @package	TYPO3
 * @subpackage	install
 */
class tx_install_mod1 extends t3lib_SCbase {

	function main() {
		if (!$GLOBALS['BE_USER']->user['admin']) {
			throw new t3lib_error_Exception('Access denied', 1306866845);
		}

		if (!Tx_Install_Service_BasicService::checkInstallToolEnableFile()) {
			Tx_Install_Service_BasicService::createInstallToolEnableFile();
		}
		t3lib_utility_Http::redirect('install/');
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/install/mod/index.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/install/mod/index.php']);
}


// Make instance:
$SOBE = t3lib_div::makeInstance('tx_install_mod1');
$SOBE->init();

$SOBE->main();

?>