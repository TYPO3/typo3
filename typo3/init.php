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
 * TYPO3 Backend initialization
 *
 * This script is called by every backend script.
 * The script authenticates the backend user.
 * In addition this script also initializes the database and other stuff by including the script localconf.php
 *
 * IMPORTANT:
 * This script exits if no user is logged in!
 * If you want the script to return even if no user is logged in,
 * you must define the constant TYPO3_PROCEED_IF_NO_USER=1
 * before you include this script.
 *
 *
 * This script does the following:
 * - extracts and defines path's
 * - includes certain libraries
 * - authenticates the user
 * - sets the configuration values (localconf.php)
 * - includes tables.php that sets more values and possibly overrides others
 * - load the groupdata for the user and set filemounts / webmounts
 *
 * For a detailed description of this script, the scope of constants and variables in it,
 * please refer to the document "Inside TYPO3"
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */

	// Prevent any unwanted output that may corrupt AJAX/compression. Note: this does
	// not interfeer with "die()" or "echo"+"exit()" messages!
ob_start();

	// Define constants
define('TYPO3_MODE', 'BE');

	// We use require instead of require_once here so we get a fatal error if
	// Bootstrap.php is accidentally included twice (which would indicate a clear bug).
require('Bootstrap.php');
Typo3_Bootstrap::checkEnvironmentOrDie();
Typo3_Bootstrap::defineBaseConstants();
Typo3_Bootstrap::defineAndCheckPaths('typo3/');
Typo3_Bootstrap::requireBaseClasses();
Typo3_Bootstrap::setUpEnvironment();

require(PATH_t3lib . 'config_default.php');

Typo3_Bootstrap::initializeTypo3DbGlobal(FALSE);
Typo3_Bootstrap::checkLockedBackendAndRedirectOrDie();
Typo3_Bootstrap::checkBackendIpOrDie();
Typo3_Bootstrap::checkSslBackendAndRedirectIfNeeded();

	// Connect to the database
	// Redirect to install tool if database host and database are not defined
if (!TYPO3_db_host && !TYPO3_db) {
	t3lib_utility_Http::redirect('install/index.php?mode=123&step=1&password=joh316');
} else {
	$TYPO3_DB->connectDB();
}

	// Checks for proper browser
if (!$CLIENT['BROWSER']) {
	throw new RuntimeException('Browser Error: Your browser version looks incompatible with this TYPO3 version!', 1294587023);
}

	// Include standard tables.php or own file
if (TYPO3_tables_script) {
	include(PATH_typo3conf . TYPO3_tables_script);
} else {
	include(PATH_t3lib . 'stddb/tables.php');
}
	// Load temp_CACHED file of ext_tables or each ext_tables.php of loaded extensions
if ($TYPO3_LOADED_EXT['_CACHEFILE']) {
	include(PATH_typo3conf . $TYPO3_LOADED_EXT['_CACHEFILE'] . '_ext_tables.php');
} else {
	include(PATH_t3lib . 'stddb/load_ext_tables.php');
}
	// Load additional ext tables script
if (TYPO3_extTableDef_script) {
	include(PATH_typo3conf . TYPO3_extTableDef_script);
}

Typo3_Bootstrap::runExtTablesPostProcessingHooks();
Typo3_Bootstrap::initializeSpriteManager(TRUE);
Typo3_Bootstrap::initializeBackendUser();
Typo3_Bootstrap::initializeBackendUserMounts();
Typo3_Bootstrap::initializeLanguageObject();

	// Compression
ob_clean();
if (extension_loaded('zlib') && $TYPO3_CONF_VARS['BE']['compressionLevel']) {
	if (t3lib_utility_Math::canBeInterpretedAsInteger($TYPO3_CONF_VARS['BE']['compressionLevel'])) {
		@ini_set('zlib.output_compression_level', $TYPO3_CONF_VARS['BE']['compressionLevel']);
	}
	ob_start('ob_gzhandler');
}

?>