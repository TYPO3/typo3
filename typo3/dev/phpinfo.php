<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2003 Kasper Skårhøj (kasper@typo3.com)
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
 * Dev-script: Update of TSoptions
 *
 * $Id$
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 */

 
die("<strong>This script is for typo3 development and maintenance only. You'll probably find it useless for what you do.</strong><br><br>MUST remove this line in this script before it'll work for you. This is a security precaution. Anyways, you must be logged in as admin as well.");




$BACK_PATH="../";
if (isset($HTTP_GET_VARS["noInit"]) && $HTTP_GET_VARS["noInit"])	{
	include_once($BACK_PATH."t3lib/class.t3lib_div.php");
} else {
	define("TYPO3_PROCEED_IF_NO_USER", 1);
	define("TYPO3_MOD_PATH", "dev/");
	require ($BACK_PATH."init.php");
	require ($BACK_PATH."template.php");
}


phpinfo();



$getEnvArray = array();
$gE_keys = explode(",","QUERY_STRING,HTTP_ACCEPT,HTTP_ACCEPT_ENCODING,HTTP_ACCEPT_LANGUAGE,HTTP_CONNECTION,HTTP_COOKIE,HTTP_HOST,HTTP_USER_AGENT,REMOTE_ADDR,REMOTE_HOST,REMOTE_PORT,SERVER_ADDR,SERVER_ADMIN,SERVER_NAME,SERVER_PORT,SERVER_SIGNATURE,SERVER_SOFTWARE,GATEWAY_INTERFACE,SERVER_PROTOCOL,REQUEST_METHOD,SCRIPT_NAME,PATH_TRANSLATED,HTTP_REFERER,PATH_INFO");
while(list(,$k)=each($gE_keys))	{
	$getEnvArray[$k] = getenv($k);
}
echo "<h3>getenv()</h3>";
t3lib_div::print_array($getEnvArray);

echo '<h3>$GLOBALS["HTTP_ENV_VARS"]</h3>';
t3lib_div::print_array($GLOBALS["HTTP_ENV_VARS"]);

echo '<h3>$GLOBALS["HTTP_SERVER_VARS"]</h3>';
t3lib_div::print_array($GLOBALS["HTTP_SERVER_VARS"]);

echo '<h3>$GLOBALS["HTTP_COOKIE_VARS"]</h3>';
t3lib_div::print_array($GLOBALS["HTTP_COOKIE_VARS"]);

echo '<h3>$GLOBALS["HTTP_GET_VARS"]</h3>';
t3lib_div::print_array($GLOBALS["HTTP_GET_VARS"]);
		

$constants=array();
$constants["TYPO3_OS"] = array(TYPO3_OS,defined("TYPO3_OS")?"":"NOT DEFINED!");
$constants["PATH_thisScript"] = array(PATH_thisScript,defined("PATH_thisScript")?"":"NOT DEFINED!");
$constants["TYPO3_mainDir"] = array(TYPO3_mainDir,defined("TYPO3_mainDir")?"":"NOT DEFINED!");
$constants["PATH_typo3"] = array(PATH_typo3,defined("PATH_typo3")?"":"NOT DEFINED!");
$constants["PATH_typo3_mod"] = array(PATH_typo3_mod,defined("PATH_typo3_mod")?"":"NOT DEFINED!");
$constants["PATH_site"] = array(PATH_site,defined("PATH_site")?"":"NOT DEFINED!");
$constants["PATH_t3lib"] = array(PATH_t3lib,defined("PATH_t3lib")?"":"NOT DEFINED!");
$constants["PATH_typo3conf"] = array(PATH_typo3conf,defined("PATH_typo3conf")?"":"NOT DEFINED!");

echo '<h3>Constants</h3>';
t3lib_div::print_array($constants);
		
?>