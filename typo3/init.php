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
 * $Id$
 * Revised for TYPO3 3.6 2/2003 by Kasper Skårhøj
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */

// *******************************
// Checking PHP version
// *******************************
if (version_compare(phpversion(), '5.2', '<'))	die ('TYPO3 requires PHP 5.2.0 or higher.');


// *******************************
// Set error reporting
// *******************************
if (defined('E_DEPRECATED')) {
	error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED);
} else {
	error_reporting(E_ALL ^ E_NOTICE);
}

// *******************************
// Prevent any unwanted output that may corrupt AJAX/compression. Note: this does
// not interfeer with "die()" or "echo"+"exit()" messages!
// *******************************
ob_start();

define('TYPO3_MODE','BE');

// *********************
// Mandatory initialisation (paths, t3lib_div, t3lib_extmgmt)
// *********************
require_once('init/init_base.php');


// ****************************************************
// Include configuration (localconf + ext_localconf)
// ****************************************************
require(PATH_t3lib.'config_default.php');
if (!defined ('TYPO3_db')) 	die ('The configuration file was not included.');




// *********************
// Error & Exception handling
// *********************
if ($TYPO3_CONF_VARS['SC_OPTIONS']['errors']['exceptionHandler'] !== '') {
	if ($TYPO3_CONF_VARS['SYS']['errorHandler'] !== '') {
			// 	register an error handler for the given errorHandlerErrors
		$errorHandler = t3lib_div::makeInstance($TYPO3_CONF_VARS['SYS']['errorHandler'], $TYPO3_CONF_VARS['SYS']['errorHandlerErrors']);
			// set errors which will be converted in an exception
		$errorHandler->setExceptionalErrors($TYPO3_CONF_VARS['SC_OPTIONS']['errors']['exceptionalErrors']);
	}
	$exceptionHandler = t3lib_div::makeInstance($TYPO3_CONF_VARS['SC_OPTIONS']['errors']['exceptionHandler']);
}

/** @var TYPO3_DB t3lib_db */
$TYPO3_DB = t3lib_div::makeInstance('t3lib_DB');
$TYPO3_DB->debugOutput = $TYPO3_CONF_VARS['SYS']['sqlDebug'];

$CLIENT = t3lib_div::clientInfo();					// $CLIENT includes information about the browser/user-agent
$PARSETIME_START = t3lib_div::milliseconds();		// Is set to the system time in milliseconds. This could be used to output script parsetime in the end of the script

// ***********************************
// Initializing the Caching System
// ***********************************

if (TYPO3_UseCachingFramework) {
	$typo3CacheManager = t3lib_div::makeInstance('t3lib_cache_Manager');
	$typo3CacheFactory = t3lib_div::makeInstance('t3lib_cache_Factory');
	$typo3CacheFactory->setCacheManager($typo3CacheManager);

	t3lib_cache::initPageCache();
	t3lib_cache::initPageSectionCache();
	t3lib_cache::initContentHashCache();
}

// *************************
// CLI dispatch processing
// *************************
if ((TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI) && basename(PATH_thisScript) == 'cli_dispatch.phpsh') {
		// First, take out the first argument (cli-key)
	$temp_cliScriptPath = array_shift($_SERVER['argv']);
	$temp_cliKey = array_shift($_SERVER['argv']);
	array_unshift($_SERVER['argv'],$temp_cliScriptPath);

		// If cli_key was found in configuration, then set up the cliInclude path and module name:
	if ($temp_cliKey)	{
		if (is_array($TYPO3_CONF_VARS['SC_OPTIONS']['GLOBAL']['cliKeys'][$temp_cliKey]))	{
			define('TYPO3_cliInclude', t3lib_div::getFileAbsFileName($TYPO3_CONF_VARS['SC_OPTIONS']['GLOBAL']['cliKeys'][$temp_cliKey][0]));
			$MCONF['name'] = $TYPO3_CONF_VARS['SC_OPTIONS']['GLOBAL']['cliKeys'][$temp_cliKey][1];
		} else {
			echo "The supplied 'cliKey' was not valid. Please use one of the available from this list:\n\n";
			print_r(array_keys($TYPO3_CONF_VARS['SC_OPTIONS']['GLOBAL']['cliKeys']));
			echo LF;
			exit;
		}
	} else {
		echo "Please supply a 'cliKey' as first argument. The following are available:\n\n";
		print_r($TYPO3_CONF_VARS['SC_OPTIONS']['GLOBAL']['cliKeys']);
		echo LF;
		exit;
	}
}


// **********************
// Check Hardcoded lock on BE:
// **********************
if ($TYPO3_CONF_VARS['BE']['adminOnly'] < 0) {
	throw new RuntimeException('TYPO3 Backend locked: Backend and Install Tool are locked for maintenance. [BE][adminOnly] is set to "' . intval($TYPO3_CONF_VARS['BE']['adminOnly']) . '".');
}
if (!(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI) && @is_file(PATH_typo3conf . 'LOCK_BACKEND')) {
	if (TYPO3_PROCEED_IF_NO_USER == 2) {
		// ajax poll for login, let him pass
	} else {
		$fContent = t3lib_div::getUrl(PATH_typo3conf.'LOCK_BACKEND');
		if ($fContent)	{
			header('Location: '.$fContent);	// Redirect
		} else {
			throw new RuntimeException('TYPO3 Backend locked: Browser backend is locked for maintenance. Remove lock by removing the file "typo3conf/LOCK_BACKEND" or use CLI-scripts.');
		}
		exit;
	}

}

// **********************
// Check IP
// **********************
if (trim($TYPO3_CONF_VARS['BE']['IPmaskList']) && !(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI)) {
	if (!t3lib_div::cmpIP(t3lib_div::getIndpEnv('REMOTE_ADDR'), $TYPO3_CONF_VARS['BE']['IPmaskList']))	{
		header('Status: 404 Not Found');	// Send Not Found header - if the webserver can make use of it...
		header('Location: http://');	// Just point us away from here...
		exit;	// ... and exit good!
	}
}


// **********************
// Check SSL (https)
// **********************
if (intval($TYPO3_CONF_VARS['BE']['lockSSL']) && !(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI)) {
	if(intval($TYPO3_CONF_VARS['BE']['lockSSLPort'])) {
		$sslPortSuffix = ':'.intval($TYPO3_CONF_VARS['BE']['lockSSLPort']);
	} else {
		$sslPortSuffix = '';
	}
	if ($TYPO3_CONF_VARS['BE']['lockSSL'] == 3)	{
		$requestStr = substr(t3lib_div::getIndpEnv('TYPO3_REQUEST_SCRIPT'), strlen(t3lib_div::getIndpEnv('TYPO3_SITE_URL').TYPO3_mainDir));
		if($requestStr == 'index.php' && !t3lib_div::getIndpEnv('TYPO3_SSL'))	{
			list(,$url) = explode('://',t3lib_div::getIndpEnv('TYPO3_REQUEST_URL'),2);
			list($server,$address) = explode('/',$url,2);
			header('Location: https://'.$server.$sslPortSuffix.'/'.$address);
			exit;
		}
	} elseif (!t3lib_div::getIndpEnv('TYPO3_SSL') )	{
		if ($TYPO3_CONF_VARS['BE']['lockSSL'] == 2)	{
			list(,$url) = explode('://',t3lib_div::getIndpEnv('TYPO3_SITE_URL').TYPO3_mainDir,2);
			list($server,$address) = explode('/',$url,2);
			header('Location: https://'.$server.$sslPortSuffix.'/'.$address);
		} else {
			header('Status: 404 Not Found');	// Send Not Found header - if the webserver can make use of it...
			header('Location: http://');	// Just point us away from here...
		}
		exit;	// ... and exit good!
	}
}


// *******************************
// Checking environment
// *******************************
if (isset($_POST['GLOBALS']) || isset($_GET['GLOBALS']))	die('You cannot set the GLOBALS-array from outside the script.');
if (!get_magic_quotes_gpc())	{
	t3lib_div::addSlashesOnArray($_GET);
	t3lib_div::addSlashesOnArray($_POST);
	$HTTP_GET_VARS = $_GET;
	$HTTP_POST_VARS = $_POST;
}


// ********************************************
// Check if the install script should be run:
// ********************************************
if (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_INSTALL) {
	if(!t3lib_extMgm::isLoaded('install')) {
		die('Install Tool is not loaded as an extension.<br />You must add the key "install" to the list of installed extensions in typo3conf/localconf.php, $TYPO3_CONF_VARS[\'EXT\'][\'extList\'].');
	}

	require_once(t3lib_extMgm::extPath('install').'mod/class.tx_install.php');
	$install_check = t3lib_div::makeInstance('tx_install');
	$install_check->allowUpdateLocalConf = 1;
	$install_check->init();
	exit;
}


// *************************
// Connect to the database
// *************************
	// Redirect to install tool if database host and database are not defined
if (!TYPO3_db_host && !TYPO3_db) {
	t3lib_utility_Http::redirect('install/index.php?mode=123&step=1&password=joh316');
} elseif ($TYPO3_DB->sql_pconnect(TYPO3_db_host, TYPO3_db_username, TYPO3_db_password)) {
	if (!TYPO3_db)	{
		throw new RuntimeException('Database Error: No database selected', time());
	} elseif (!$TYPO3_DB->sql_select_db(TYPO3_db))	{
		throw new RuntimeException('Database Error: Cannot connect to the current database, "' . TYPO3_db . '"', time());
	}
} else {
	throw new RuntimeException('Database Error: The current username, password or host was not accepted when the connection to the database was attempted to be established!', time());
}


// *******************************
// Checks for proper browser
// *******************************
if (!$CLIENT['BROWSER'] && !(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI)) {
	throw new RuntimeException('Browser Error: Your browser version looks incompatible with this TYPO3 version!', time());
}


// ****************************************************
// Include tables customization (tables + ext_tables)
// ****************************************************
include (TYPO3_tables_script ? PATH_typo3conf.TYPO3_tables_script : PATH_t3lib.'stddb/tables.php');
	// Extension additions
if ($TYPO3_LOADED_EXT['_CACHEFILE'])	{
	include (PATH_typo3conf.$TYPO3_LOADED_EXT['_CACHEFILE'].'_ext_tables.php');
} else {
	include (PATH_t3lib.'stddb/load_ext_tables.php');
}
	// extScript
if (TYPO3_extTableDef_script)	{
	include (PATH_typo3conf.TYPO3_extTableDef_script);
}

	// load TYPO3 SpriteGenerating API
$spriteManager = t3lib_div::makeInstance('t3lib_SpriteManager', TRUE);
$spriteManager->loadCacheFile();


// *******************************
// BackEnd User authentication
// *******************************
/*
	NOTICE:
	if constant TYPO3_PROCEED_IF_NO_USER is defined true (in the mainscript), this script will return even though a user did not log in!
*/
$BE_USER = t3lib_div::makeInstance('t3lib_beUserAuth');	// New backend user object
$BE_USER->warningEmail = $TYPO3_CONF_VARS['BE']['warning_email_addr'];
$BE_USER->lockIP = $TYPO3_CONF_VARS['BE']['lockIP'];
$BE_USER->auth_timeout_field = intval($TYPO3_CONF_VARS['BE']['sessionTimeout']);
$BE_USER->OS = TYPO3_OS;
if (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI) {
	$BE_USER->dontSetCookie = TRUE;
}
$BE_USER->start();			// Object is initialized
$BE_USER->checkCLIuser();
$BE_USER->backendCheckLogin();	// Checking if there's a user logged in

	// Setting the web- and filemount global vars:
$WEBMOUNTS = $BE_USER->returnWebmounts();		// ! WILL INCLUDE deleted mount pages as well!
$FILEMOUNTS = $BE_USER->returnFilemounts();

// *******************************
// $GLOBALS['LANG'] initialisation
// *******************************
$GLOBALS['LANG'] = t3lib_div::makeInstance('language');
$GLOBALS['LANG']->init($BE_USER->uc['lang']);


// ****************
// CLI processing
// ****************
if (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI) {
		// Status output:
	if (!strcmp($_SERVER['argv'][1],'status'))	{
		echo "Status of TYPO3 CLI script:\n\n";
		echo "Username [uid]: ".$BE_USER->user['username']." [".$BE_USER->user['uid']."]\n";
		echo "Database: ".TYPO3_db.LF;
		echo "PATH_site: ".PATH_site.LF;
		echo LF;
		exit;
	}
}

// ****************
// compression
// ****************
ob_clean();
if (extension_loaded('zlib') && $TYPO3_CONF_VARS['BE']['compressionLevel'])	{
	if (t3lib_div::testInt($TYPO3_CONF_VARS['BE']['compressionLevel'])) {
		@ini_set('zlib.output_compression_level', $TYPO3_CONF_VARS['BE']['compressionLevel']);
	}
	ob_start('ob_gzhandler');
}

?>