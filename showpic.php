<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2005 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Shows a picture from uploads/* in enlarged format in a separate window.
 * Picture file and settings is supplied by GET-parameters: file, width, height, sample, alternativeTempPath, effects, frame, bodyTag, title, wrap, md5
 *
 * $Id$
 *
 * @author	René Fritz <r.fritz@colorcube.de>
 * @package TYPO3
 * @subpackage tslib
 */

// *******************************
// Set error reporting
// *******************************

error_reporting (E_ALL ^ E_NOTICE);


// ******************
// Constants defined
// ******************

define('PATH_thisScript',str_replace('//','/', str_replace('\\','/', (php_sapi_name()=='cgi'||php_sapi_name()=='isapi' ||php_sapi_name()=='cgi-fcgi')&&($_SERVER['ORIG_PATH_TRANSLATED']?$_SERVER['ORIG_PATH_TRANSLATED']:$_SERVER['PATH_TRANSLATED'])? ($_SERVER['ORIG_PATH_TRANSLATED']?$_SERVER['ORIG_PATH_TRANSLATED']:$_SERVER['PATH_TRANSLATED']):($_SERVER['ORIG_SCRIPT_FILENAME']?$_SERVER['ORIG_SCRIPT_FILENAME']:$_SERVER['SCRIPT_FILENAME']))));

define('PATH_site', dirname(PATH_thisScript).'/');

if (@is_dir(PATH_site.'typo3/sysext/cms/tslib/')) {
	define('PATH_tslib', PATH_site.'typo3/sysext/cms/tslib/');
} elseif (@is_dir(PATH_site.'tslib/')) {
	define('PATH_tslib', PATH_site.'tslib/');
} else {

	// define path to tslib/ here:
	$configured_tslib_path = '';

	// example:
	// $configured_tslib_path = '/var/www/mysite/typo3/sysext/cms/tslib/';

	define('PATH_tslib', $configured_tslib_path);
}

if (PATH_tslib=='') {
	die('Cannot find tslib/. Please set path by defining $configured_tslib_path in '.basename(PATH_thisScript).'.');
}

// ******************
// include showpic script
// ******************

require (PATH_tslib.'showpic.php');

?>