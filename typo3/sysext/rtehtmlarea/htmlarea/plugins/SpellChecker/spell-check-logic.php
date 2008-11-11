<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2003-2008 Stanislas Rolland (typo3(arobas)sjbr.ca)
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
 * This is the script to invoke the spell checker for TYPO3 htmlArea RTE (rtehtmlarea)
 *
 * @author	Stanislas Rolland <typo3(arobas)sjbr.ca>
 *
 * TYPO3 SVN ID: $Id$
 *
 */
	error_reporting (E_ALL ^ E_NOTICE);
	define('TYPO3_OS', (stristr(PHP_OS,'win') && !stristr(PHP_OS,'darwin')) ? 'WIN' : '');
	if (!defined('PATH_thisScript')) define('PATH_thisScript',str_replace('//','/', str_replace('\\','/', (php_sapi_name()=='cgi'||php_sapi_name()=='xcgi'||php_sapi_name()=='isapi' ||php_sapi_name()=='cgi-fcgi')&&((!empty($_SERVER['ORIG_PATH_TRANSLATED'])&&isset($_SERVER['ORIG_PATH_TRANSLATED']))?$_SERVER['ORIG_PATH_TRANSLATED']:$_SERVER['PATH_TRANSLATED'])? ((!empty($_SERVER['ORIG_PATH_TRANSLATED'])&&isset($_SERVER['ORIG_PATH_TRANSLATED']))?$_SERVER['ORIG_PATH_TRANSLATED']:$_SERVER['PATH_TRANSLATED']):((!empty($_SERVER['ORIG_SCRIPT_FILENAME'])&&isset($_SERVER['ORIG_SCRIPT_FILENAME']))?$_SERVER['ORIG_SCRIPT_FILENAME']:$_SERVER['SCRIPT_FILENAME']))));
	if (!defined('PATH_site')) define('PATH_site', dirname(dirname(dirname(dirname(dirname(dirname(dirname(PATH_thisScript))))))).'/');
	if (!defined('PATH_t3lib')) define('PATH_t3lib', PATH_site.'t3lib/');
	define('PATH_typo3conf', PATH_site.'typo3conf/');
	define('TYPO3_mainDir', 'typo3/');
	if (!defined('PATH_typo3')) define('PATH_typo3', PATH_site.TYPO3_mainDir);
	if (!defined('PATH_tslib')) {
		if (@is_dir(PATH_site.'typo3/sysext/cms/tslib/')) {
			define('PATH_tslib', PATH_site.'typo3/sysext/cms/tslib/');
		} elseif (@is_dir(PATH_site.'tslib/')) {
			define('PATH_tslib', PATH_site.'tslib/');
		}
	}
	define('TYPO3_MODE','FE');

	require_once(PATH_t3lib.'class.t3lib_div.php');
	require_once(PATH_t3lib.'class.t3lib_extmgm.php');
	require_once(PATH_t3lib.'config_default.php');
	require_once(PATH_typo3conf.'localconf.php');
	require_once(PATH_tslib.'class.tslib_fe.php');
	require_once(PATH_t3lib.'class.t3lib_tstemplate.php');
	require_once(PATH_t3lib.'class.t3lib_page.php');
	require_once(PATH_tslib.'class.tslib_content.php');
	require_once(t3lib_extMgm::extPath('rtehtmlarea').'pi1/class.tx_rtehtmlarea_pi1.php');
	require_once(PATH_t3lib.'class.t3lib_userauth.php');
	require_once(PATH_tslib.'class.tslib_feuserauth.php');

	$typoVersion = t3lib_div::int_from_ver($GLOBALS['TYPO_VERSION']);
	require_once(PATH_t3lib.'class.t3lib_cs.php');

	if (!defined ('TYPO3_db'))  die ('The configuration file was not included.');
	if (isset($HTTP_POST_VARS['GLOBALS']) || isset($HTTP_GET_VARS['GLOBALS']))      die('You cannot set the GLOBALS-array from outside this script.');

	require_once(PATH_t3lib.'class.t3lib_db.php');
	$TYPO3_DB = t3lib_div::makeInstance('t3lib_DB');

	require_once(PATH_t3lib.'class.t3lib_timetrack.php');
	$GLOBALS['TT'] = new t3lib_timeTrack;

// ***********************************
// Initializing the Caching System
// ***********************************

$GLOBALS['TT']->push('Initializing the Caching System','');
	require_once(PATH_t3lib . 'class.t3lib_cache.php');

	require_once(PATH_t3lib . 'cache/class.t3lib_cache_abstractbackend.php');
	require_once(PATH_t3lib . 'cache/class.t3lib_cache_abstractcache.php');
	require_once(PATH_t3lib . 'cache/class.t3lib_cache_exception.php');
	require_once(PATH_t3lib . 'cache/class.t3lib_cache_factory.php');
	require_once(PATH_t3lib . 'cache/class.t3lib_cache_manager.php');
	require_once(PATH_t3lib . 'cache/class.t3lib_cache_variablecache.php');

	require_once(PATH_t3lib . 'cache/exception/class.t3lib_cache_exception_classalreadyloaded.php');
	require_once(PATH_t3lib . 'cache/exception/class.t3lib_cache_exception_duplicateidentifier.php');
	require_once(PATH_t3lib . 'cache/exception/class.t3lib_cache_exception_invalidbackend.php');
	require_once(PATH_t3lib . 'cache/exception/class.t3lib_cache_exception_invalidcache.php');
	require_once(PATH_t3lib . 'cache/exception/class.t3lib_cache_exception_invaliddata.php');
	require_once(PATH_t3lib . 'cache/exception/class.t3lib_cache_exception_nosuchcache.php');

	$typo3CacheManager = t3lib_div::makeInstance('t3lib_cache_Manager');
	$cacheFactoryClass = t3lib_div::makeInstanceClassName('t3lib_cache_Factory');
	$typo3CacheFactory = new $cacheFactoryClass($typo3CacheManager);

	unset($cacheFactoryClass);
$GLOBALS['TT']->pull();

	// ***********************************
	// Creating a fake $TSFE object
	// ***********************************
	$TSFEclassName = t3lib_div::makeInstanceClassName('tslib_fe');
	$id = isset($HTTP_GET_VARS['id'])?$HTTP_GET_VARS['id']:0;
	$GLOBALS['TSFE'] = new $TSFEclassName($TYPO3_CONF_VARS, $id, '0', 1, '', '','','');
	$GLOBALS['TSFE']->initCaches();
	$GLOBALS['TSFE']->set_no_cache();
	$GLOBALS['TSFE']->connectToMySQL();
	$GLOBALS['TSFE']->initFEuser();
	$GLOBALS['TSFE']->fetch_the_id();
	$GLOBALS['TSFE']->getPageAndRootline();
	$GLOBALS['TSFE']->initTemplate();
	$GLOBALS['TSFE']->tmpl->getFileName_backPath = PATH_site;
	$GLOBALS['TSFE']->forceTemplateParsing = 1;
	$GLOBALS['TSFE']->getConfigArray();

	// *********
	// initialize a BE_USER if applicable
	// *********
	$BE_USER='';
	if ($_COOKIE['be_typo_user'])	{	// If the backend cookie is set, we proceed and checks if a backend user is logged in.
		$TYPO3_MISC['microtime_BE_USER_start'] = microtime();
		$TT->push('Back End user initialized','');
		require_once (PATH_t3lib.'class.t3lib_befunc.php');
		require_once (PATH_t3lib.'class.t3lib_userauthgroup.php');
		require_once (PATH_t3lib.'class.t3lib_beuserauth.php');
		require_once (PATH_t3lib.'class.t3lib_tsfebeuserauth.php');

			// the value this->formfield_status is set to empty in order to disable login-attempts to the backend account through this script
		$BE_USER = t3lib_div::makeInstance('t3lib_tsfeBeUserAuth');	// New backend user object
		$BE_USER->OS = TYPO3_OS;
		$BE_USER->lockIP = $TYPO3_CONF_VARS['BE']['lockIP'];
		$BE_USER->start();	// Object is initialized
		$BE_USER->unpack_uc('');
		if ($BE_USER->user['uid'])	{
			$BE_USER->fetchGroupData();
			$TSFE->beUserLogin = 1;
		}
			// Now we need to do some additional checks for IP/SSL
		if (!$BE_USER->checkLockToIP() || !$BE_USER->checkBackendAccessSettingsFromInitPhp())	{
				// Unset the user initialization.
			$BE_USER='';
			$TSFE->beUserLogin=0;
		}
	}

	$spellChecker = t3lib_div::makeInstance('tx_rtehtmlarea_pi1');
	$spellChecker->cObj = t3lib_div::makeInstance('tslib_cObj');
	$conf = $GLOBALS['TSFE']->tmpl->setup['plugin.'][$spellChecker->prefixId.'.'];
	$spellChecker->main($conf);
?>