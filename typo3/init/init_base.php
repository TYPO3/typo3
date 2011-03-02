<?php

if (!defined('TYPO3_MODE')) die('Error');

// *******************************
// Define constants
// *******************************
define('TYPO3_OS', stristr(PHP_OS,'win')&&!stristr(PHP_OS,'darwin')?'WIN':'');
define('PATH_thisScript', str_replace('//', '/', str_replace('\\', '/',
	(PHP_SAPI == 'fpm-fcgi' || PHP_SAPI == 'cgi' || PHP_SAPI == 'isapi' || PHP_SAPI == 'cgi-fcgi') &&
	($_SERVER['ORIG_PATH_TRANSLATED'] ? $_SERVER['ORIG_PATH_TRANSLATED'] : $_SERVER['PATH_TRANSLATED']) ?
	($_SERVER['ORIG_PATH_TRANSLATED'] ? $_SERVER['ORIG_PATH_TRANSLATED'] : $_SERVER['PATH_TRANSLATED']) :
	($_SERVER['ORIG_SCRIPT_FILENAME'] ? $_SERVER['ORIG_SCRIPT_FILENAME'] : $_SERVER['SCRIPT_FILENAME']))));



define('TYPO3_mainDir', 'typo3/');		// This is the directory of the backend administration for the sites of this TYPO3 installation.


// *******************************
// Fix BACK_PATH, if the TYPO3_mainDir is set to something else than
// typo3/, this is a workaround because the conf.php of the old modules
// still have "typo3/" hardcoded. Can be removed once we don't have to worry about
// legacy modules (with conf.php and $BACK_PATH) anymore. See RFC / Bug #13262 for more details.
// *******************************
if (isset($BACK_PATH) && strlen($BACK_PATH) > 0 && TYPO3_mainDir != 'typo3/' && substr($BACK_PATH, -7) == '/typo3/') {
	$BACK_PATH = substr($BACK_PATH, 0, -6) . TYPO3_mainDir;
}

// *******************************
// Checking path
// *******************************
$temp_path = str_replace('\\','/',dirname(PATH_thisScript).'/');
$temp_modPath='';
	// If TYPO3_MOD_PATH is defined we must calculate the modPath since init.php must be included by a module
if (substr($temp_path,-strlen(TYPO3_mainDir))!=TYPO3_mainDir)	{
	if (defined('TYPO3_MOD_PATH'))	{
		if (substr($temp_path,-strlen(TYPO3_MOD_PATH))==TYPO3_MOD_PATH)	{
			$temp_path=substr($temp_path,0,-strlen(TYPO3_MOD_PATH));
			$temp_modPath=TYPO3_MOD_PATH;
		} elseif (substr(TYPO3_MOD_PATH,0,13)=='../typo3conf/' && (substr(TYPO3_MOD_PATH,3)==substr($temp_path,-strlen(substr(TYPO3_MOD_PATH,3))))) {
			$temp_path = substr($temp_path,0,-strlen(substr(TYPO3_MOD_PATH,3))).TYPO3_mainDir;
			$temp_modPath=TYPO3_MOD_PATH;
		}
		if (!@is_dir($temp_path))	{
			$temp_path='';
		}
	}
}

// OUTPUT error message and exit if there are problems with the path. Otherwise define constants and continue.
if (!$temp_path || substr($temp_path,-strlen(TYPO3_mainDir))!=TYPO3_mainDir)	{	// This must be the case in order to proceed
	if (TYPO3_OS=='WIN')	{
		$thisPath_base = basename(substr($temp_path,-strlen(TYPO3_mainDir)));
		$mainPath_base = basename(TYPO3_mainDir);
		if (!strcasecmp($thisPath, $mainPath))	{	// Seems like the requested URL is not case-specific. This may happen on Windows only. -case. Otherwise, redirect to the correct URL. TYPO3_mainDir must be lower-case!!
			$script_name = (PHP_SAPI=='fpm-fcgi' || PHP_SAPI=='cgi' || PHP_SAPI=='cgi-fcgi') &&
				($_SERVER['ORIG_PATH_INFO'] ? $_SERVER['ORIG_PATH_INFO'] : $_SERVER['PATH_INFO']) ?
				($_SERVER['ORIG_PATH_INFO'] ? $_SERVER['ORIG_PATH_INFO'] : $_SERVER['PATH_INFO']) :
				($_SERVER['ORIG_SCRIPT_NAME']?$_SERVER['ORIG_SCRIPT_NAME']:$_SERVER['SCRIPT_NAME']);	// Copied from t3lib_div::getIndpEnv()

			header('Location: '.str_replace($thisPath_base, $mainPath_base, $script_name));
			exit;
		}
	}

	echo 'Error in init.php: Path to TYPO3 main dir could not be resolved correctly. <br /><br />';

	echo '<font color="red"><strong>';
	if (strstr($temp_path,'typo3_src')) {
		echo 'It seems you are trying to run the TYPO3 source libraries DIRECTLY! You cannot do that.<br />
		Please read the installation documents for more information.';
	} else {
		$temp_path_parts = explode('/', $temp_path);
		$temp_path_parts = array_slice($temp_path_parts, count($temp_path_parts) - 3);
		$temp_path = '..../' . implode('/', $temp_path_parts);
		echo 'This happens if the last ' . strlen(TYPO3_mainDir) . ' characters of this path, ' . $temp_path . ' (end of $temp_path), is NOT "' . TYPO3_mainDir . '" for some reason.<br />
		You may have a strange server configuration.
		Or maybe you didn\'t set constant TYPO3_MOD_PATH in your module?';
	}
	echo '</strong></font>';

	echo '<br /><br />If you want to debug this issue, please edit typo3/init.php of your TYPO3 source and search for the die() call right after this line (search for this text to find)...';

// Remove this line if you want to debug this problem a little more...
die();
	echo '<br /><br /><strong>If you expect any help from anybody on this issue, you should save this page as an html document and send it along with your request for help!</strong>';
	echo '<hr /><pre>';
	print_r(array(
		'TYPO3_OS'=>TYPO3_OS,
		'PATH_thisScript'=>PATH_thisScript,
		'php_sapi_name()'=>PHP_SAPI,
		'TYPO3_MOD_PATH'=>TYPO3_MOD_PATH,
		'PATH_TRANSLATED'=>$_SERVER['PATH_TRANSLATED'],
		'SCRIPT_FILENAME'=>$_SERVER['SCRIPT_FILENAME']
	));
	echo '</pre><hr />';
	phpinfo();
	exit;
} else {
	define('PATH_typo3', $temp_path);			// Abs. path of the TYPO3 admin dir (PATH_site + TYPO3_mainDir).
	define('PATH_typo3_mod', $temp_modPath);	// Relative path (from the PATH_typo3) to a properly configured module
	define('PATH_site', substr(PATH_typo3,0,-strlen(TYPO3_mainDir)));	// Abs. path to directory with the frontend (one above the admin-dir)
	$temp_path_t3lib = @is_dir(PATH_site.'t3lib/') ? PATH_site.'t3lib/' : PATH_typo3.'t3lib/';
	define('PATH_t3lib', $temp_path_t3lib);			// Abs. path to t3lib/ (general TYPO3 library) within the TYPO3 admin dir
	define('PATH_typo3conf', PATH_site.'typo3conf/');	// Abs. TYPO3 configuration path (local, not part of source)

	if (!defined('PATH_tslib')) {
		if (@is_dir(PATH_site . TYPO3_mainDir . 'sysext/cms/tslib/')) {
			define('PATH_tslib', PATH_site . TYPO3_mainDir . 'sysext/cms/tslib/');
		} elseif (@is_dir(PATH_site . 'tslib/')) {
			define('PATH_tslib', PATH_site . 'tslib/');
		}
	}
}

// *********************
// Unset variable(s) in global scope (fixes #13959)
// *********************
unset($error);

// *************************************************
// t3lib_div + extention management class included
// *************************************************
require_once(PATH_t3lib.'class.t3lib_div.php');		// The standard-library is included
require_once(PATH_t3lib.'class.t3lib_extmgm.php');	// Extension API Management library included
