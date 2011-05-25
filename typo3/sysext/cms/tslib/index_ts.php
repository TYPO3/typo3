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
 * This is the MAIN DOCUMENT of the TypoScript driven standard front-end (from the "cms" extension)
 * Basically put this is the "index.php" script which all requests for TYPO3 delivered pages goes to in the frontend (the website)
 * The script configures constants, includes libraries and does a little logic here and there in order to instantiate the right classes to create the webpage.
 * All the real data processing goes on in the "tslib/" classes which this script will include and use as needed.
 *
 * Revised for TYPO3 3.6 June/2003 by Kasper Skårhøj
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tslib
 */

// *******************************
// Checking PHP version
// *******************************
if (version_compare(phpversion(), '5.2', '<'))	die ('TYPO3 requires PHP 5.2.0 or higher.');

// *******************************
// Set error reporting
// *******************************
error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED);


// ******************
// Constants defined
// ******************
$TYPO3_MISC['microtime_start'] = microtime(TRUE);
define('TYPO3_OS', stristr(PHP_OS,'win')&&!stristr(PHP_OS,'darwin')?'WIN':'');
define('TYPO3_MODE','FE');

if(!defined('PATH_thisScript')) {
	define('PATH_thisScript', str_replace('//', '/', str_replace('\\', '/',
		(PHP_SAPI == 'fpm-fcgi' || PHP_SAPI == 'cgi' || PHP_SAPI == 'isapi' || PHP_SAPI == 'cgi-fcgi') &&
		($_SERVER['ORIG_PATH_TRANSLATED'] ? $_SERVER['ORIG_PATH_TRANSLATED'] : $_SERVER['PATH_TRANSLATED']) ?
		($_SERVER['ORIG_PATH_TRANSLATED'] ? $_SERVER['ORIG_PATH_TRANSLATED'] : $_SERVER['PATH_TRANSLATED']) :
		($_SERVER['ORIG_SCRIPT_FILENAME'] ? $_SERVER['ORIG_SCRIPT_FILENAME'] : $_SERVER['SCRIPT_FILENAME']))));
}

if (!defined('PATH_site')) 			define('PATH_site', dirname(PATH_thisScript).'/');
if (!defined('PATH_t3lib')) 		define('PATH_t3lib', PATH_site.'t3lib/');

define('TYPO3_mainDir', 'typo3/');		// This is the directory of the backend administration for the sites of this TYPO3 installation.
define('PATH_typo3', PATH_site.TYPO3_mainDir);
define('PATH_typo3conf', PATH_site.'typo3conf/');

if (!defined('PATH_tslib')) {
	if (@is_dir(PATH_site.TYPO3_mainDir.'sysext/cms/tslib/')) {
		define('PATH_tslib', PATH_site.TYPO3_mainDir.'sysext/cms/tslib/');
	} elseif (@is_dir(PATH_site.'tslib/')) {
		define('PATH_tslib', PATH_site.'tslib/');
	}
}

if (!@is_dir(PATH_typo3conf))	die('Cannot find configuration. This file is probably executed from the wrong location.');

// *********************
// Unset variable(s) in global scope (fixes #13959)
// *********************
unset($error);

// *********************
// Prevent any output until AJAX/compression is initialized to stop
// AJAX/compression data corruption
// *********************
ob_start();

// *********************
// Timetracking started
// *********************
if ($_COOKIE['TYPO3-AdminPanel']]) {
	require_once(PATH_t3lib.'class.t3lib_timetrack.php');
	$TT = new t3lib_timeTrack;
} else {
	require_once(PATH_t3lib.'class.t3lib_timetracknull.php');
	$TT = new t3lib_timeTrackNull;
}

$TT->start();
$TT->push('','Script start');


// *********************
// Mandatory libraries included
// *********************
$TT->push('Include class t3lib_db, t3lib_div, t3lib_extmgm','');
	require_once(PATH_t3lib.'class.t3lib_div.php');
	require_once(PATH_t3lib.'class.t3lib_extmgm.php');
$TT->pull();



// **********************
// Include configuration
// **********************
$TT->push('Include config files','');
require(PATH_t3lib.'config_default.php');
if (!defined ('TYPO3_db')) 	die ('The configuration file was not included.');	// the name of the TYPO3 database is stored in this constant. Here the inclusion of the config-file is verified by checking if this var is set.
if (!t3lib_extMgm::isLoaded('cms'))	die('<strong>Error:</strong> The main frontend extension "cms" was not loaded. Enable it in the extension manager in the backend.');

if (!defined('PATH_tslib')) {
	define('PATH_tslib', t3lib_extMgm::extPath('cms').'tslib/');
}




// *********************
// Error & Exception handling
// *********************
if ($TYPO3_CONF_VARS['SC_OPTIONS']['errors']['exceptionHandler'] !== '') {
	$TT->push('Register Exceptionhandler', '');
	if ($TYPO3_CONF_VARS['SYS']['errorHandler'] !== '') {
			// register an error handler for the given errorHandlerErrors
		$errorHandler = t3lib_div::makeInstance($TYPO3_CONF_VARS['SYS']['errorHandler'], $TYPO3_CONF_VARS['SYS']['errorHandlerErrors']);
			// set errors which will be converted in an exception
		$errorHandler->setExceptionalErrors($TYPO3_CONF_VARS['SC_OPTIONS']['errors']['exceptionalErrors']);
	}
	$exceptionHandler = t3lib_div::makeInstance($TYPO3_CONF_VARS['SC_OPTIONS']['errors']['exceptionHandler']);
	$TT->pull();
}

$TYPO3_DB = t3lib_div::makeInstance('t3lib_DB');
$TYPO3_DB->debugOutput = $TYPO3_CONF_VARS['SYS']['sqlDebug'];

$CLIENT = t3lib_div::clientInfo();				// Set to the browser: net / msie if 4+ browsers
$TT->pull();


// *******************************
// Checking environment
// *******************************
if (isset($_POST['GLOBALS']) || isset($_GET['GLOBALS'])) {
	throw new Exception('You cannot set the GLOBALS-array from outside the script.', 1294585200);
}
if (!get_magic_quotes_gpc())	{
	$TT->push('Add slashes to GET/POST arrays','');
	t3lib_div::addSlashesOnArray($_GET);
	t3lib_div::addSlashesOnArray($_POST);
	$HTTP_GET_VARS = $_GET;
	$HTTP_POST_VARS = $_POST;
	$TT->pull();
}


// Hook to preprocess the current request:
if (is_array($TYPO3_CONF_VARS['SC_OPTIONS']['tslib/index_ts.php']['preprocessRequest'])) {
	foreach ($TYPO3_CONF_VARS['SC_OPTIONS']['tslib/index_ts.php']['preprocessRequest'] as $hookFunction) {
		$hookParameters = array();
		t3lib_div::callUserFunction($hookFunction, $hookParameters, $hookParameters);
	}
	unset($hookFunction);
	unset($hookParameters);
}


// *********************
// Look for extension ID which will launch alternative output engine
// *********************
if ($temp_extId = t3lib_div::_GP('eID'))	{
	if ($classPath = t3lib_div::getFileAbsFileName($TYPO3_CONF_VARS['FE']['eID_include'][$temp_extId]))	{
		// Remove any output produced until now
		ob_clean();

		require($classPath);
	}
	exit;
}


// ***********************************
// Create $TSFE object (TSFE = TypoScript Front End)
// Connecting to database
// ***********************************
$TSFE = t3lib_div::makeInstance('tslib_fe',
	$TYPO3_CONF_VARS,
	t3lib_div::_GP('id'),
	t3lib_div::_GP('type'),
	t3lib_div::_GP('no_cache'),
	t3lib_div::_GP('cHash'),
	t3lib_div::_GP('jumpurl'),
	t3lib_div::_GP('MP'),
	t3lib_div::_GP('RDCT')
);
/** @var $TSFE tslib_fe */

if($TYPO3_CONF_VARS['FE']['pageUnavailable_force'] &&
	!t3lib_div::cmpIP(t3lib_div::getIndpEnv('REMOTE_ADDR'), $TYPO3_CONF_VARS['SYS']['devIPmask'])) {
	$TSFE->pageUnavailableAndExit('This page is temporarily unavailable.');
}


$TSFE->connectToDB();

	// In case of a keyword-authenticated preview, re-initialize the TSFE object:
if ($temp_previewConfig = $TSFE->ADMCMD_preview())	{
	$TSFE = t3lib_div::makeInstance('tslib_fe',
		$TYPO3_CONF_VARS,
		t3lib_div::_GP('id'),
		t3lib_div::_GP('type'),
		t3lib_div::_GP('no_cache'),
		t3lib_div::_GP('cHash'),
		t3lib_div::_GP('jumpurl'),
		t3lib_div::_GP('MP'),
		t3lib_div::_GP('RDCT')
	);
	$TSFE->ADMCMD_preview_postInit($temp_previewConfig);
}

if ($TSFE->RDCT)	{$TSFE->sendRedirect();}


// *******************
// Output compression
// *******************
// Remove any output produced until now
ob_clean();
if ($TYPO3_CONF_VARS['FE']['compressionLevel'] && extension_loaded('zlib'))	{
	if (t3lib_div::testInt($TYPO3_CONF_VARS['FE']['compressionLevel'])) {
		// Prevent errors if ini_set() is unavailable (safe mode)
		@ini_set('zlib.output_compression_level', $TYPO3_CONF_VARS['FE']['compressionLevel']);
	}
	ob_start(array(t3lib_div::makeInstance('tslib_fecompression'), 'compressionOutputHandler'));
}

// *********
// FE_USER
// *********
$TT->push('Front End user initialized','');
	/* @var $TSFE tslib_fe */
	$TSFE->initFEuser();
$TT->pull();

// ****************
// PRE BE_USER HOOK
// ****************
if (is_array($TYPO3_CONF_VARS['SC_OPTIONS']['tslib/index_ts.php']['preBeUser'])) {
	foreach($TYPO3_CONF_VARS['SC_OPTIONS']['tslib/index_ts.php']['preBeUser'] as $_funcRef) {
		$_params = array();
		t3lib_div::callUserFunction($_funcRef, $_params , $_params);
	}
}


// *********
// BE_USER
// *********
$BE_USER = NULL;
/** @var $BE_USER t3lib_tsfeBeUserAuth */
if ($_COOKIE[t3lib_beUserAuth::getCookieName()]) {		// If the backend cookie is set, we proceed and checks if a backend user is logged in.
	$TYPO3_MISC['microtime_BE_USER_start'] = microtime(TRUE);
	$TT->push('Back End user initialized','');

			// the value this->formfield_status is set to empty in order to disable login-attempts to the backend account through this script
		$BE_USER = t3lib_div::makeInstance('t3lib_tsfeBeUserAuth');	// New backend user object
		$BE_USER->OS = TYPO3_OS;
		$BE_USER->lockIP = $TYPO3_CONF_VARS['BE']['lockIP'];
		$BE_USER->start();			// Object is initialized
		$BE_USER->unpack_uc('');
		if ($BE_USER->user['uid'])	{
			$BE_USER->fetchGroupData();
			$TSFE->beUserLogin = 1;
		}
			// Unset the user initialization.
		if (!$BE_USER->checkLockToIP() || !$BE_USER->checkBackendAccessSettingsFromInitPhp() || !$BE_USER->user['uid']) {
			$BE_USER = NULL;
			$TSFE->beUserLogin=0;
		}
	$TT->pull();
	$TYPO3_MISC['microtime_BE_USER_end'] = microtime(TRUE);
} elseif ($TSFE->ADMCMD_preview_BEUSER_uid)	{

		// the value this->formfield_status is set to empty in order to disable login-attempts to the backend account through this script
	$BE_USER = t3lib_div::makeInstance('t3lib_tsfeBeUserAuth');	// New backend user object
	$BE_USER->userTS_dontGetCached = 1;
	$BE_USER->OS = TYPO3_OS;
	$BE_USER->setBeUserByUid($TSFE->ADMCMD_preview_BEUSER_uid);
	$BE_USER->unpack_uc('');
	if ($BE_USER->user['uid'])	{
		$BE_USER->fetchGroupData();
		$TSFE->beUserLogin = 1;
	} else {
		$BE_USER = NULL;
		$TSFE->beUserLogin = 0;
	}
}

// ********************
// Workspace preview:
// ********************
$TSFE->workspacePreviewInit();


// *****************************************
// Process the ID, type and other parameters
// After this point we have an array, $page in TSFE, which is the page-record of the current page, $id
// *****************************************
$TT->push('Process ID','');
		// Initialize admin panel since simulation settings are required here:
	if ($TSFE->beUserLogin) {
		$BE_USER->initializeAdminPanel();
	}

	$TSFE->checkAlternativeIdMethods();
	$TSFE->clear_preview();
	$TSFE->determineId();

		// Now, if there is a backend user logged in and he has NO access to this page, then re-evaluate the id shown!
	if ($TSFE->beUserLogin && (!$BE_USER->extPageReadAccess($TSFE->page) || t3lib_div::_GP('ADMCMD_noBeUser')))	{	// t3lib_div::_GP('ADMCMD_noBeUser') is placed here because workspacePreviewInit() might need to know if a backend user is logged in!

			// Remove user
		unset($BE_USER);
		$TSFE->beUserLogin = 0;

			// Re-evaluate the page-id.
		$TSFE->checkAlternativeIdMethods();
		$TSFE->clear_preview();
		$TSFE->determineId();
	}
	$TSFE->makeCacheHash();
$TT->pull();

// *****************************************
// Admin Panel & Frontend editing
// *****************************************
if ($TSFE->beUserLogin) {
		// if a BE User is present load, the sprite manager for frontend-editing
	$spriteManager = t3lib_div::makeInstance('t3lib_SpriteManager', FALSE);
	$spriteManager->loadCacheFile();

	$BE_USER->initializeFrontendEdit();
 	if ($BE_USER->adminPanel instanceof tslib_AdminPanel) {
		$LANG = t3lib_div::makeInstance('language');
		$LANG->init($BE_USER->uc['lang']);
 	}
	if ($BE_USER->frontendEdit instanceof t3lib_frontendedit) {
		$BE_USER->frontendEdit->initConfigOptions();
	}
}

// *******************************************
// Get compressed $TCA-Array();
// After this, we should now have a valid $TCA, though minimized
// *******************************************
$TSFE->getCompressedTCarray();


// ********************************
// Starts the template
// *******************************
$TT->push('Start Template','');
	$TSFE->initTemplate();
$TT->pull();


// ********************************
// Get from cache
// *******************************
$TT->push('Get Page from cache','');
	$TSFE->getFromCache();
$TT->pull();


// ******************************************************
// Get config if not already gotten
// After this, we should have a valid config-array ready
// ******************************************************
$TSFE->getConfigArray();

// ********************************
// Convert POST data to internal "renderCharset" if different from the metaCharset
// *******************************
$TSFE->convPOSTCharset();


// *******************************************
// Setting language and locale
// *******************************************
$TT->push('Setting language and locale','');
	$TSFE->settingLanguage();
	$TSFE->settingLocale();
$TT->pull();


// ********************************
// Check JumpUrl
// *******************************
$TSFE->setExternalJumpUrl();
$TSFE->checkJumpUrlReferer();


// ********************************
// Check Submission of data.
// This is done at this point, because we need the config values
// *******************************
switch($TSFE->checkDataSubmission())	{
	case 'email':
		$TSFE->sendFormmail();
	break;
	case 'fe_tce':
		$TSFE->includeTCA();
		$TT->push('fe_tce','');
		$TSFE->fe_tce();
		$TT->pull();
	break;
}


// ********************************
// Generate page
// *******************************
$TSFE->setUrlIdToken();

$TT->push('Page generation','');
	if ($TSFE->isGeneratePage()) {
		$TSFE->generatePage_preProcessing();
		$temp_theScript=$TSFE->generatePage_whichScript();

		if ($temp_theScript) {
			include($temp_theScript);
		} else {
			include(PATH_tslib.'pagegen.php');
		}
		$TSFE->generatePage_postProcessing();
	} elseif ($TSFE->isINTincScript()) {
		include(PATH_tslib.'pagegen.php');
	}
$TT->pull();


// ********************************
// $TSFE->config['INTincScript']
// *******************************
if ($TSFE->isINTincScript())		{
	$TT->push('Non-cached objects','');
		$TSFE->INTincScript();
	$TT->pull();
}

// ***************
// Output content
// ***************
$sendTSFEContent = FALSE;
if ($TSFE->isOutputting())	{
	$TT->push('Print Content','');
	$TSFE->processOutput();

	// ***************************************
	// Outputs content / Includes EXT scripts
	// ***************************************
	if ($TSFE->isEXTincScript())	{
		$TT->push('External PHP-script','');
				// Important global variables here are $EXTiS_*, they must not be overridden in include-scripts!!!
			$EXTiS_config = $TSFE->config['EXTincScript'];
			$EXTiS_splitC = explode('<!--EXT_SCRIPT.',$TSFE->content);	// Splits content with the key

				// Special feature: Include libraries
			foreach ($EXTiS_config as $EXTiS_cPart) {
				if (isset($EXTiS_cPart['conf']['includeLibs']) && $EXTiS_cPart['conf']['includeLibs']) {
					$EXTiS_resourceList = t3lib_div::trimExplode(',',$EXTiS_cPart['conf']['includeLibs'], TRUE);
					$TSFE->includeLibraries($EXTiS_resourceList);
				}
			}

			foreach ($EXTiS_splitC as $EXTiS_c => $EXTiS_cPart) {
				if (substr($EXTiS_cPart,32,3)=='-->')	{	// If the split had a comment-end after 32 characters it's probably a split-string
					$EXTiS_key = 'EXT_SCRIPT.'.substr($EXTiS_cPart,0,32);
					if (is_array($EXTiS_config[$EXTiS_key]))	{
						$REC = $EXTiS_config[$EXTiS_key]['data'];
						$CONF = $EXTiS_config[$EXTiS_key]['conf'];
						$content = '';
						include($EXTiS_config[$EXTiS_key]['file']);
						echo $content;	// The script MAY return content in $content or the script may just output the result directly!
					}
					echo substr($EXTiS_cPart,35);
				} else {
					echo ($c?'<!--EXT_SCRIPT.':'').$EXTiS_cPart;
				}
			}

		$TT->pull();
	} else {
		$sendTSFEContent = TRUE;
	}
	$TT->pull();
}


// ********************************
// Store session data for fe_users
// ********************************
$TSFE->storeSessionData();


// ***********
// Statistics
// ***********
$TYPO3_MISC['microtime_end'] = microtime(TRUE);
$TSFE->setParseTime();
if ($TSFE->isOutputting() && (!empty($TSFE->TYPO3_CONF_VARS['FE']['debug']) || !empty($TSFE->config['config']['debug']))) {
	$TSFE->content .=  LF . '<!-- Parsetime: ' . $TSFE->scriptParseTime . 'ms -->';
}
$TSFE->statistics();


// ***************
// Check JumpUrl
// ***************
$TSFE->jumpurl();


// *************
// Preview info
// *************
$TSFE->previewInfo();


// ******************
// Publishing static
// ******************
if (is_object($BE_USER) && ($BE_USER->adminPanel instanceof tslib_AdminPanel)) {
	if ($BE_USER->adminPanel->isAdminModuleEnabled('publish') && $BE_USER->adminPanel->getExtPublishList()) {
		include_once(PATH_tslib.'publish.php');
	}
}


// ******************
// Hook for end-of-frontend
// ******************
$TSFE->hook_eofe();


// ********************
// Finish timetracking
// ********************
$TT->pull();


// ******************
// beLoginLinkIPList
// ******************
echo $TSFE->beLoginLinkIPList();


// *************
// Admin panel
// *************
if (is_object($BE_USER) && $BE_USER->isAdminPanelVisible() && $TSFE->beUserLogin) {
	$TSFE->content = str_ireplace('</head>',  $BE_USER->adminPanel->getAdminPanelHeaderData() . '</head>', $TSFE->content);
	$TSFE->content = str_ireplace('</body>',  $BE_USER->displayAdminPanel() . '</body>', $TSFE->content);
}

if ($sendTSFEContent) {
	echo $TSFE->content;
}

// *************
// Debugging Output
// *************
if(isset($error) && is_object($error) && @is_callable(array($error,'debugOutput'))) {
	$error->debugOutput();
}
if (TYPO3_DLOG) {
	t3lib_div::devLog('END of FRONTEND session', 'cms', 0, array('_FLUSH' => TRUE));
}

?>