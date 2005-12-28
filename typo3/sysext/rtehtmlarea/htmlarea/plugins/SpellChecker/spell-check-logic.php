<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2003-2005 Stanislas Rolland (stanislas.rolland@fructifor.ca)
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
* This is the script to invoke the spell checker for htmlArea RTE (rtehtmlarea)
*
*/
	error_reporting (E_ALL ^ E_NOTICE);
	define('TYPO3_OS', (stristr(PHP_OS,'win') && !stristr(PHP_OS,'darwin')) ? 'WIN' : '');
	define('PATH_thisScript',str_replace('//','/', str_replace('\\','/', (php_sapi_name()=='cgi'||php_sapi_name()=='xcgi'||php_sapi_name()=='isapi' ||php_sapi_name()=='cgi-fcgi')&&((!empty($_SERVER['ORIG_PATH_TRANSLATED'])&&isset($_SERVER['ORIG_PATH_TRANSLATED']))?$_SERVER['ORIG_PATH_TRANSLATED']:$_SERVER['PATH_TRANSLATED'])? ((!empty($_SERVER['ORIG_PATH_TRANSLATED'])&&isset($_SERVER['ORIG_PATH_TRANSLATED']))?$_SERVER['ORIG_PATH_TRANSLATED']:$_SERVER['PATH_TRANSLATED']):((!empty($_SERVER['ORIG_SCRIPT_FILENAME'])&&isset($_SERVER['ORIG_SCRIPT_FILENAME']))?$_SERVER['ORIG_SCRIPT_FILENAME']:$_SERVER['SCRIPT_FILENAME']))));
	
	define('PATH_typo3', dirname(dirname(dirname(dirname(dirname(dirname(dirname(PATH_thisScript))))))).'/typo3/');
	define('PATH_site', dirname(PATH_typo3).'/');
	define('PATH_tslib', PATH_site.'tslib/');
	define('PATH_t3lib', PATH_typo3.'t3lib/');
	define('PATH_typo3conf', PATH_site.'typo3conf/');
	define('TYPO3_mainDir', 'typo3/');
	define('TYPO3_MODE','FE');
	
	require(PATH_t3lib.'class.t3lib_div.php');
	require(PATH_t3lib.'class.t3lib_extmgm.php');
	require(PATH_t3lib.'config_default.php');
	require(PATH_typo3conf.'localconf.php');
	require_once(PATH_tslib.'class.tslib_fe.php');
	require_once(PATH_t3lib.'class.t3lib_tstemplate.php');
	require_once(PATH_t3lib.'class.t3lib_page.php');
	require_once(PATH_tslib.'class.tslib_content.php');
	require_once(t3lib_extMgm::extPath('rtehtmlarea').'pi1/class.tx_rtehtmlarea_pi1.php');
	require_once(PATH_t3lib.'class.t3lib_userauth.php');
	require_once(PATH_tslib.'class.tslib_feuserauth.php');
	
	$typoVersion = t3lib_div::int_from_ver($GLOBALS['TYPO_VERSION']);
	if($typoVersion >= 3006000) require_once(PATH_t3lib.'class.t3lib_cs.php');   // are we are with Typo3 3.6.0?
	
	if (!defined ('TYPO3_db'))  die ('The configuration file was not included.');
	if (isset($HTTP_POST_VARS['GLOBALS']) || isset($HTTP_GET_VARS['GLOBALS']))      die('You cannot set the GLOBALS-array from outside this script.');
	
	if($typoVersion >= 3006000) {   // are we are with Typo3 3.6.0?
		require_once(PATH_t3lib.'class.t3lib_db.php');
		$TYPO3_DB = t3lib_div::makeInstance('t3lib_DB');
	}
	
	require_once(PATH_t3lib.'class.t3lib_timetrack.php');
	$GLOBALS['TT'] = new t3lib_timeTrack;
	
	// ***********************************
	// Creating a fake $TSFE object
	// ***********************************
	$TSFEclassName = t3lib_div::makeInstanceClassName('tslib_fe');
	$id = isset($HTTP_GET_VARS['id'])?$HTTP_GET_VARS['id']:0;
	$GLOBALS['TSFE'] = new $TSFEclassName($TYPO3_CONF_VARS, $id, '0', 1, '', '','','');
	$GLOBALS['TSFE']->connectToMySQL();
	$GLOBALS['TSFE']->initFEuser();
	$GLOBALS['TSFE']->fetch_the_id();
	$GLOBALS['TSFE']->getPageAndRootline();
	$GLOBALS['TSFE']->initTemplate();
	$GLOBALS['TSFE']->tmpl->getFileName_backPath = PATH_site;
	$GLOBALS['TSFE']->forceTemplateParsing = 1;
	$GLOBALS['TSFE']->getConfigArray();
	$spellChecker = t3lib_div::makeInstance('tx_rtehtmlarea_pi1');
	$spellChecker->cObj = t3lib_div::makeInstance('tslib_cObj');
	$conf = $GLOBALS['TSFE']->tmpl->setup['plugin.'][$spellChecker->prefixId.'.'];
	$spellChecker->main($conf);
?>