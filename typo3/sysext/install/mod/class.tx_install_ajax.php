<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2010 Marcus Krause, Helmut Hummel (security@typo3.org)
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



// *******************************
// Set error reporting
// *******************************
if (defined('E_DEPRECATED')) {
	error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED);
} else {
	error_reporting(E_ALL ^ E_NOTICE);
}


// ***********************
// Paths are setup
// ***********************
define('TYPO3_OS', stristr(PHP_OS,'win')&&!stristr(PHP_OS,'darwin')?'WIN':'');
define('TYPO3_MODE','FE');
if (!defined('PATH_thisScript')) 	define('PATH_thisScript',str_replace('//','/', str_replace('\\','/', (PHP_SAPI=='cgi'||PHP_SAPI=='isapi' ||PHP_SAPI=='cgi-fcgi')&&($_SERVER['ORIG_PATH_TRANSLATED']?$_SERVER['ORIG_PATH_TRANSLATED']:$_SERVER['PATH_TRANSLATED'])? ($_SERVER['ORIG_PATH_TRANSLATED']?$_SERVER['ORIG_PATH_TRANSLATED']:$_SERVER['PATH_TRANSLATED']):($_SERVER['ORIG_SCRIPT_FILENAME']?$_SERVER['ORIG_SCRIPT_FILENAME']:$_SERVER['SCRIPT_FILENAME']))));

if (!defined('PATH_site')) 			define('PATH_site', dirname(PATH_thisScript).'/');
if (!defined('PATH_t3lib')) 		define('PATH_t3lib', PATH_site.'t3lib/');
define('PATH_tslib', PATH_site.'tslib/');
define('PATH_typo3conf', PATH_site.'typo3conf/');
define('TYPO3_mainDir', 'typo3/');		// This is the directory of the backend administration for the sites of this TYPO3 installation.

if (!@is_dir(PATH_typo3conf))	die('Cannot find configuration. This file is probably executed from the wrong location.');


require_once(PATH_t3lib.'class.t3lib_div.php');

/**
 * This is the eID handler for install tool AJAX calls.
 *
 * @author	Marcus Krause <security@typo3.org>
 */
class tx_install_ajax {


	/**
	 * Keeps content to be printed.
	 *
	 * @var string
	 */
	var $content;

	/**
	 * Keeps command to process.
	 *
	 * @var string
	 */
	var $cmd = '';


	/**
	 * Init function, setting the input vars in the class scope.
	 *
	 * @return	void
	 */
	function init()	{
		$this->cmd = t3lib_div::_GP('cmd');
	}

	/**
	 * Main function which creates the AJAX call return string.
	 * It is stored in $this->content.
	 *
	 * @return	void
	 */
	function main()	{
			// Create output:
		switch ($this->cmd) {
			case 'encryptionKey':
			default:
				$this->content = $this->createEncryptionKey();
				$this->addTempContentHttpHeaders();
				break;
		}
	}

	/**
	 * Outputs the content from $this->content
	 *
	 * @return	void
	 */
	function printContent()	{
		if (!headers_sent()) {
			header('Content-Length: ' . strlen($this->content));
		}
		echo $this->content;
	}

	/**
	 * Returns a newly created TYPO3 encryption key with a given length.
	 *
	 * @param  integer  $keyLength  desired key length
	 * @return string
	 */
	function createEncryptionKey($keyLength = 96) {
		if (!headers_sent()) {
			header("Content-type: text/plain");
		}

		$bytes = t3lib_div::generateRandomBytes($keyLength);
		return substr(bin2hex($bytes), -96);
	}

	/**
	 * Sends cache control headers that prevent caching in user agents.
	 *
	 */
	function addTempContentHttpHeaders() {
		if (!headers_sent()) {
				// see RFC 2616
				// see Microsoft Knowledge Base #234067
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
			header('Cache-Control: no-cache, must-revalidate');
			header('Pragma: no-cache');
			header('Expires: -1');
		}
	}
}

// Make instance:
$SOBE = t3lib_div::makeInstance('tx_install_ajax');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/sysext/install/mod/class.tx_install_ajax.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/sysext/install/mod/class.tx_install_ajax.php']);
}
?>