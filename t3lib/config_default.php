<?php
/**
 * TYPO3 default configuration
 *
 * TYPO3_CONF_VARS is a global array with configuration for the TYPO3 libraries
 * THESE VARIABLES MAY BE OVERRIDDEN FROM WITHIN localconf.php
 *
 * 'IM' is short for 'ImageMagick', which is an external image manipulation package available from www.imagemagick.org. Version is ABSOLUTELY preferred to be 4.2.9, but may be 5+. See the install notes for TYPO3!!
 * 'GD' is short for 'GDLib/FreeType', which are libraries that should be compiled into PHP4. GDLib <=1.3 supports GIF, while the latest version 1.8.x and 2.x supports only PNG. GDLib is available from www.boutell.com/gd/. Freetype has a link from there.
 * Revised for TYPO3 3.6 2/2003 by Kasper Skårhøj
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */

if (!defined ('PATH_typo3conf')) 	die ('The configuration path was not properly defined!');

$TYPO3_CONF_VARS = require(PATH_t3lib . 'stddb/DefaultSettings.php');

if (TYPO3_MODE === 'BE') {
	t3lib_extMgm::registerExtDirectComponent(
		'TYPO3.Components.PageTree.DataProvider',
		PATH_t3lib . 'tree/pagetree/extdirect/class.t3lib_tree_pagetree_extdirect_tree.php:t3lib_tree_pagetree_extdirect_Tree',
		'web',
		'user,group'
	);

	t3lib_extMgm::registerExtDirectComponent(
		'TYPO3.Components.PageTree.Commands',
		PATH_t3lib . 'tree/pagetree/extdirect/class.t3lib_tree_pagetree_extdirect_tree.php:t3lib_tree_pagetree_extdirect_Commands',
		'web',
		'user,group'
	);

	t3lib_extMgm::registerExtDirectComponent(
		'TYPO3.Components.PageTree.ContextMenuDataProvider',
		PATH_t3lib . 'contextmenu/pagetree/extdirect/class.t3lib_contextmenu_pagetree_extdirect_contextmenu.php:t3lib_contextmenu_pagetree_extdirect_ContextMenu',
		'web',
		'user,group'
	);

	t3lib_extMgm::registerExtDirectComponent(
		'TYPO3.LiveSearchActions.ExtDirect',
		PATH_t3lib . 'extjs/dataprovider/class.extdirect_dataprovider_backendlivesearch.php:extDirect_DataProvider_BackendLiveSearch',
		'web_list',
		'user,group'
	);

	t3lib_extMgm::registerExtDirectComponent(
		'TYPO3.BackendUserSettings.ExtDirect',
		PATH_t3lib . 'extjs/dataprovider/class.extdirect_dataprovider_beusersettings.php:extDirect_DataProvider_BackendUserSettings'
	);

	t3lib_extMgm::registerExtDirectComponent(
		'TYPO3.CSH.ExtDirect',
		PATH_t3lib . 'extjs/dataprovider/class.extdirect_dataprovider_contexthelp.php:extDirect_DataProvider_ContextHelp'
	);

	t3lib_extMgm::registerExtDirectComponent(
		'TYPO3.ExtDirectStateProvider.ExtDirect',
		PATH_t3lib . 'extjs/dataprovider/class.extdirect_dataprovider_state.php:extDirect_DataProvider_State'
	);
}

$T3_VAR = array();	// Initialize.

	// Handle $GLOBALS['TYPO3_CONF_VARS']['HTTP']['userAgent']. We can not set the default above
	// because TYPO3_version is not yet defined.
if (empty($GLOBALS['TYPO3_CONF_VARS']['HTTP']['userAgent'])) {
	$GLOBALS['TYPO3_CONF_VARS']['HTTP']['userAgent'] = 'TYPO3/' . TYPO3_version;
}
define('TYPO3_user_agent', 'User-Agent: '. $GLOBALS['TYPO3_CONF_VARS']['HTTP']['userAgent']);

// Database-variables are cleared!
$typo_db = '';					// The database name
$typo_db_username = '';			// The database username
$typo_db_password = '';			// The database password
$typo_db_host = '';				// The database host
$typo_db_extTableDef_script = '';	// The filename of an additional script in typo3conf/-folder which is included after tables.php. Code in this script should modify the tables.php-configuration only, and this provides a good way to extend the standard-distributed tables.php file.

	// Include localconf.php. Use this file to configure TYPO3 for your needs and database
if (!@is_file(PATH_typo3conf . 'localconf.php')) {
	throw new RuntimeException('localconf.php is not found!', 1333754332);
}
require(PATH_typo3conf.'localconf.php');


function initializeCachingFramework() {
	require_once (PATH_t3lib . 'class.t3lib_cache.php');
	require_once (PATH_t3lib . 'cache/class.t3lib_cache_exception.php');
	require_once (PATH_t3lib . 'cache/exception/class.t3lib_cache_exception_nosuchcache.php');
	require_once (PATH_t3lib . 'cache/exception/class.t3lib_cache_exception_invaliddata.php');
	require_once (PATH_t3lib . 'interfaces/interface.t3lib_singleton.php');
	require_once (PATH_t3lib . 'cache/class.t3lib_cache_factory.php');
	require_once (PATH_t3lib . 'cache/class.t3lib_cache_manager.php');
	require_once (PATH_t3lib . 'cache/frontend/interfaces/interface.t3lib_cache_frontend_frontend.php');
	require_once (PATH_t3lib . 'cache/frontend/class.t3lib_cache_frontend_abstractfrontend.php');
	require_once (PATH_t3lib . 'cache/frontend/class.t3lib_cache_frontend_stringfrontend.php');
	require_once (PATH_t3lib . 'cache/frontend/class.t3lib_cache_frontend_phpfrontend.php');
	require_once (PATH_t3lib . 'cache/backend/interfaces/interface.t3lib_cache_backend_backend.php');
	require_once (PATH_t3lib . 'cache/backend/class.t3lib_cache_backend_abstractbackend.php');
	require_once (PATH_t3lib . 'cache/backend/interfaces/interface.t3lib_cache_backend_phpcapablebackend.php');
	require_once (PATH_t3lib . 'cache/backend/class.t3lib_cache_backend_filebackend.php');
	require_once (PATH_t3lib . 'cache/backend/class.t3lib_cache_backend_nullbackend.php');
	t3lib_cache::initializeCachingFramework();
}

	// The autoloader must be required BEFORE the initialization of the caching framework,
	// because we need one method from the autoloader in t3lib_div::getClassName
	// which will be used during the caching framework startup
require_once(PATH_t3lib . 'class.t3lib_autoloader.php');

initializeCachingFramework();


// *********************
// Autoloader
// *********************
t3lib_autoloader::registerAutoloader();

/**
 * Add typo3/contrib/pear/ as first include folder in
 * include path, because the shipped PEAR packages use
 * relative paths to include their files.
 *
 * This is required for t3lib_http_Request to work.
 *
 * Having the TYPO3 folder first will make sure that the
 * shipped version is loaded before any local PEAR package,
 * thus avoiding any incompatibilities with newer or older
 * versions.
 */
set_include_path(PATH_typo3 . 'contrib/pear/' . PATH_SEPARATOR . get_include_path());

/**
 * Checking for UTF-8 in the settings since TYPO3 4.5
 *
 * Since TYPO3 4.5, everything other than UTF-8 is deprecated.
 *
 *   [BE][forceCharset] is set to the charset that TYPO3 is using
 *   [SYS][setDBinit] is used to set the DB connection
 * and both settings need to be adjusted for UTF-8 in order to work properly
 */
	// Check if [BE][forceCharset] has been set in localconf.php
if (isset($TYPO3_CONF_VARS['BE']['forceCharset'])) {
		// die() unless we're already on UTF-8
	if ($TYPO3_CONF_VARS['BE']['forceCharset'] != 'utf-8' && $TYPO3_CONF_VARS['BE']['forceCharset'] && TYPO3_enterInstallScript !== '1') {
		die('This installation was just upgraded to a new TYPO3 version. Since TYPO3 4.7, utf-8 is always enforced.<br />' .
			'The configuration option $TYPO3_CONF_VARS[BE][forceCharset] was marked as deprecated in TYPO3 4.5 and is now ignored.<br />' .
			'You have configured the value to something different, which is not supported anymore.<br />' .
			'Please proceed to the Update Wizard in the TYPO3 Install Tool to update your configuration.'
		);
	} else {
		unset($TYPO3_CONF_VARS['BE']['forceCharset']);
	}
}

if (isset($TYPO3_CONF_VARS['SYS']['setDBinit']) &&
	$TYPO3_CONF_VARS['SYS']['setDBinit'] !== '-1' &&
	preg_match('/SET NAMES utf8/', $TYPO3_CONF_VARS['SYS']['setDBinit']) === FALSE &&
	TYPO3_enterInstallScript !== '1'
) {
		// Only accept "SET NAMES utf8" for this setting, otherwise die with a nice error
	die('This TYPO3 installation is using the $TYPO3_CONF_VARS[\'SYS\'][\'setDBinit\'] property with the following value:' . chr(10) .
		$TYPO3_CONF_VARS['SYS']['setDBinit'] . chr(10) . chr(10) .
		'It looks like UTF-8 is not used for this connection.' . chr(10) . chr(10) .
		'Everything other than UTF-8 is unsupported since TYPO3 4.7.' . chr(10) .
		'The DB, its connection and TYPO3 should be migrated to UTF-8 therefore. Please check your setup.');
} else {
	$TYPO3_CONF_VARS['SYS']['setDBinit'] = 'SET NAMES utf8;';
}



/**
 * Parse old curl options and set new http ones instead
 *
 * @deprecated Deprecated since 4.6 - will be removed in 4.8.
 */
if (!empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyServer'])) {
	$proxyParts = explode(':', $GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyServer'], 2);
	$GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_host'] = $proxyParts[0];
	$GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_port'] = $proxyParts[1];
	/* TODO: uncomment after refactoring getUrl()
	t3lib_div::deprecationLog(
		'This TYPO3 installation is using the $TYPO3_CONF_VARS[\'SYS\'][\'curlProxyServer\'] property with the following value: ' .
		$TYPO3_CONF_VARS['SYS']['curlProxyServer'] . LF . 'Please make sure to set $TYPO3_CONF_VARS[\'HTTP\'][\'proxy_host\']' .
		' and $TYPO3_CONF_VARS[\'HTTP\'][\'proxy_port\'] instead.' . LF . 'Remove this line from your localconf.php.'
	);*/
}
if (!empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyUserPass'])) {
	$userPassParts = explode(':', $GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyUserPass'], 2);
	$GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_user'] = $userPassParts[0];
	$GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_password'] = $userPassParts[1];
	/* TODO: uncomment after refactoring getUrl()
	t3lib_div::deprecationLog(
		'This TYPO3 installation is using the $TYPO3_CONF_VARS[\'SYS\'][\'curlProxyUserPass\'] property with the following value: ' .
		$TYPO3_CONF_VARS['SYS']['curlProxyUserPass'] . LF . 'Please make sure to set $TYPO3_CONF_VARS[\'HTTP\'][\'proxy_user\']' .
		' and $TYPO3_CONF_VARS[\'HTTP\'][\'proxy_password\'] instead.' . LF . 'Remove this line from your localconf.php.'
	);*/
}

/**
 * Set cacheHash options
 */
$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash'] = array(
	'cachedParametersWhiteList' => t3lib_div::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['FE']['cHashOnlyForParameters'], TRUE),
	'excludedParameters' => t3lib_div::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['FE']['cHashExcludedParameters'], TRUE),
	'requireCacheHashPresenceParameters' => t3lib_div::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['FE']['cHashRequiredParameters'], TRUE),
);
if (trim($GLOBALS['TYPO3_CONF_VARS']['FE']['cHashExcludedParametersIfEmpty']) === '*') {
	$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludeAllEmptyParameters'] = TRUE;
} else {
	$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParametersIfEmpty'] = t3lib_div::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['FE']['cHashExcludedParametersIfEmpty'], TRUE);
}


	// ['HTTP']['proxy_auth_scheme'] can only be 'digest' or 'basic'
$GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_auth_scheme'] === 'digest' ?
	$GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_auth_scheme'] = 'digest' :
	$GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_auth_scheme'] = 'basic';


$timeZone = $GLOBALS['TYPO3_CONF_VARS']['SYS']['phpTimeZone'];
if (empty($timeZone)) {
		// time zone from the server environment (TZ env or OS query)
	$defaultTimeZone = @date_default_timezone_get();
	if ($defaultTimeZone !== '') {
		$timeZone = $defaultTimeZone;
	} else {
		$timeZone = 'UTC';
	}
}
	// sets the default to avoid E_WARNINGs under PHP 5.3
date_default_timezone_set($timeZone);

	// Defining the database setup as constants
define('TYPO3_db', $typo_db);
define('TYPO3_db_username', $typo_db_username);
define('TYPO3_db_password', $typo_db_password);
define('TYPO3_db_host', $typo_db_host);
define('TYPO3_extTableDef_script', $typo_db_extTableDef_script);

	// Initialize the locales handled by TYPO3
t3lib_l10n_Locales::initialize();

	// Unsetting the configured values. Use of these are deprecated.
unset($typo_db);
unset($typo_db_username);
unset($typo_db_password);
unset($typo_db_host);
unset($typo_db_extTableDef_script);

	// Based on the configuration of the image processing some options may be forced:
if (!$GLOBALS['TYPO3_CONF_VARS']['GFX']['image_processing']) {
	$GLOBALS['TYPO3_CONF_VARS']['GFX']['im']=0;
	$GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib']=0;
}
if (!$GLOBALS['TYPO3_CONF_VARS']['GFX']['im']) {
	$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path']='';
	$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path_lzw']='';
	$GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']='gif,jpg,jpeg,png';
	$GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails'] = 0;
}
if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_version_5']) {
	$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_negate_mask'] = 1;
	$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_no_effects'] = 1;
	$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_mask_temp_ext_gif'] = 1;

	if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_version_5']==='gm') {
		$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_negate_mask'] = 0;
		$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_imvMaskState'] = 0;
		$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_no_effects'] = 1;
		$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_v5effects'] = -1;
	}
}
if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_imvMaskState']) {
	$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_negate_mask']=$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_negate_mask']?0:1;
}


	// Convert type of "pageNotFound_handling" setting in case it was written as a string (e.g. if edited in Install Tool)
	// TODO: Once the Install Tool handles such data types correctly, this workaround should be removed again...
if (!strcasecmp($TYPO3_CONF_VARS['FE']['pageNotFound_handling'], 'TRUE')) {
	$TYPO3_CONF_VARS['FE']['pageNotFound_handling'] = TRUE;
}


	// simple debug function which prints output immediately
function xdebug($var = '', $debugTitle = 'xdebug') {
		// If you wish to use the debug()-function, and it does not output something, please edit the IP mask in TYPO3_CONF_VARS
	if (!t3lib_div::cmpIP(t3lib_div::getIndpEnv('REMOTE_ADDR'), $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask']))	return;
	t3lib_utility_Debug::debug($var, $debugTitle);
}
	// Debug function which calls $GLOBALS['error'] error handler if available
function debug($variable='', $name='*variable*', $line='*line*', $file='*file*', $recursiveDepth=3, $debugLevel=E_DEBUG) {
		// If you wish to use the debug()-function, and it does not output something, please edit the IP mask in TYPO3_CONF_VARS
	if (!t3lib_div::cmpIP(t3lib_div::getIndpEnv('REMOTE_ADDR'), $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask']))	return;

	if (is_object($GLOBALS['error']) && @is_callable(array($GLOBALS['error'],'debug'))) {
		$GLOBALS['error']->debug($variable, $name, $line, $file, $recursiveDepth, $debugLevel);
	} else {
		$title = ($name === '*variable*') ? '' : $name;
		$group = ($line === '*line*') ? NULL : $line;
		t3lib_utility_Debug::debug($variable, $title, $group);
	}
}
function debugBegin() {
	if (is_object($GLOBALS['error']) && @is_callable(array($GLOBALS['error'],'debugBegin'))) {
		$GLOBALS['error']->debugBegin();
	}
}
function debugEnd() {
	if (is_object($GLOBALS['error']) && @is_callable(array($GLOBALS['error'],'debugEnd'))) {
		$GLOBALS['error']->debugEnd();
	}
}


	// Init services array:
$T3_SERVICES = array();

	// Error & exception handling
$TYPO3_CONF_VARS['SC_OPTIONS']['errors']['exceptionHandler'] = $TYPO3_CONF_VARS['SYS']['productionExceptionHandler'];
$TYPO3_CONF_VARS['SC_OPTIONS']['errors']['exceptionalErrors'] = $TYPO3_CONF_VARS['SYS']['exceptionalErrors'];

	// Mail sending via Swift Mailer
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/utility/class.t3lib_utility_mail.php']['substituteMailDelivery'][] = 't3lib_mail_SwiftMailerAdapter';

	// Turn error logging on/off.
if (($displayErrors = intval($TYPO3_CONF_VARS['SYS']['displayErrors'])) != '-1') {
	if ($displayErrors == 2)	{	// Special value "2" enables this feature only if $TYPO3_CONF_VARS[SYS][devIPmask] matches
		if (t3lib_div::cmpIP(t3lib_div::getIndpEnv('REMOTE_ADDR'), $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'])) {
			$displayErrors = 1;
		} else {
			$displayErrors = 0;
		}
	}
	if ($displayErrors == 0) {
		$TYPO3_CONF_VARS['SC_OPTIONS']['errors']['exceptionalErrors'] = 0;
	}
	if ($displayErrors == 1) {
		$TYPO3_CONF_VARS['SC_OPTIONS']['errors']['exceptionHandler'] = $TYPO3_CONF_VARS['SYS']['debugExceptionHandler'];
		define('TYPO3_ERRORHANDLER_MODE', 'debug');
	}

	@ini_set('display_errors', $displayErrors);
} elseif (t3lib_div::cmpIP(t3lib_div::getIndpEnv('REMOTE_ADDR'), $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'])) {
		// with displayErrors = -1 (default), turn on debugging if devIPmask matches:
	$TYPO3_CONF_VARS['SC_OPTIONS']['errors']['exceptionHandler'] = $TYPO3_CONF_VARS['SYS']['debugExceptionHandler'];
}



	// Set PHP memory limit depending on value of $TYPO3_CONF_VARS["SYS"]["setMemoryLimit"]
if (intval($TYPO3_CONF_VARS['SYS']['setMemoryLimit']) > 16) {
	@ini_set('memory_limit', intval($TYPO3_CONF_VARS['SYS']['setMemoryLimit']) . 'm');
}


// setting the request type so devs exactly know what type of request it is
define('TYPO3_REQUESTTYPE_FE', 1);
define('TYPO3_REQUESTTYPE_BE', 2);
define('TYPO3_REQUESTTYPE_CLI', 4);
define('TYPO3_REQUESTTYPE_AJAX', 8);
define('TYPO3_REQUESTTYPE_INSTALL', 16);
define('TYPO3_REQUESTTYPE',
	(TYPO3_MODE == 'FE' ? TYPO3_REQUESTTYPE_FE : 0) |
	(TYPO3_MODE == 'BE' ? TYPO3_REQUESTTYPE_BE : 0) |
	((defined('TYPO3_cliMode') && TYPO3_cliMode) ? TYPO3_REQUESTTYPE_CLI : 0) |
	((defined('TYPO3_enterInstallScript') && TYPO3_enterInstallScript) ? TYPO3_REQUESTTYPE_INSTALL : 0) |
	($TYPO3_AJAX ? TYPO3_REQUESTTYPE_AJAX : 0)
);


// Load extensions:
$TYPO3_LOADED_EXT = t3lib_extMgm::typo3_loadExtensions();
if ($TYPO3_LOADED_EXT['_CACHEFILE']) {
	require(PATH_typo3conf.$TYPO3_LOADED_EXT['_CACHEFILE'].'_ext_localconf.php');
} else {
	$temp_TYPO3_LOADED_EXT = $TYPO3_LOADED_EXT;
	foreach ($temp_TYPO3_LOADED_EXT as $_EXTKEY => $temp_lEDat) {
		if (is_array($temp_lEDat) && $temp_lEDat['ext_localconf.php']) {
			$_EXTCONF = $TYPO3_CONF_VARS['EXT']['extConf'][$_EXTKEY];
			require($temp_lEDat['ext_localconf.php']);
		}
	}
}

	// Write deprecation log if the TYPO3 instance uses deprecated XCLASS
	// registrations via $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']
if (count($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']) > 0) {
	t3lib_div::deprecationLog('This installation runs with extensions that use XCLASSing by setting the XCLASS path in ext_localconf.php. This is deprecated and will be removed in TYPO3 6.2 and later. It is preferred to define XCLASSes in ext_autoload.php instead. See http://wiki.typo3.org/Autoload for more information.');
}


	// Error & Exception handling
if ($TYPO3_CONF_VARS['SC_OPTIONS']['errors']['exceptionHandler'] !== '') {
	if ($TYPO3_CONF_VARS['SYS']['errorHandler'] !== '') {
			// Register an error handler for the given errorHandlerErrors
		$errorHandler = t3lib_div::makeInstance($TYPO3_CONF_VARS['SYS']['errorHandler'], $TYPO3_CONF_VARS['SYS']['errorHandlerErrors']);
			// Set errors which will be converted in an exception
		$errorHandler->setExceptionalErrors($TYPO3_CONF_VARS['SC_OPTIONS']['errors']['exceptionalErrors']);
	}
	$exceptionHandler = t3lib_div::makeInstance($TYPO3_CONF_VARS['SC_OPTIONS']['errors']['exceptionHandler']);
}

	// Extensions may register new caches, so we set the
	// global cache array to the manager again at this point
$GLOBALS['typo3CacheManager']->setCacheConfigurations($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']);

require_once(t3lib_extMgm::extPath('lang') . 'lang.php');

	// Define "TYPO3_DLOG" constant
define('TYPO3_DLOG', $GLOBALS['TYPO3_CONF_VARS']['SYS']['enable_DLOG']);

define('TYPO3_ERROR_DLOG', $GLOBALS['TYPO3_CONF_VARS']['SYS']['enable_errorDLOG']);
define('TYPO3_EXCEPTION_DLOG', $GLOBALS['TYPO3_CONF_VARS']['SYS']['enable_exceptionDLOG']);

	// Unsetting other reserved global variables:
	// Those which are/can be set in "stddb/tables.php" files:
unset($PAGES_TYPES);
unset($TCA);
unset($TBE_MODULES);
unset($TBE_STYLES);
unset($FILEICONS);

	// Those set in init.php:
unset($WEBMOUNTS);
unset($FILEMOUNTS);
unset($BE_USER);

	// Those set otherwise:
unset($TBE_MODULES_EXT);
unset($TCA_DESCR);
unset($LOCAL_LANG);
unset($TYPO3_AJAX);


	// Setting some global vars:
$EXEC_TIME = time();					// $EXEC_TIME is set so that the rest of the script has a common value for the script execution time
$SIM_EXEC_TIME = $EXEC_TIME;			// $SIM_EXEC_TIME is set to $EXEC_TIME but can be altered later in the script if we want to simulate another execution-time when selecting from eg. a database
$ACCESS_TIME = $EXEC_TIME - ($EXEC_TIME % 60);		// $ACCESS_TIME is a common time in minutes for access control
$SIM_ACCESS_TIME = $ACCESS_TIME;		// if $SIM_EXEC_TIME is changed this value must be set accordingly

?>