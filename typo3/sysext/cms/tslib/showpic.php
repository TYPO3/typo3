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
 * Shows a picture from uploads/* in enlarged format in a separate window.
 * Picture file and settings is supplied by GET-parameters: file, width, height, sample, alternativeTempPath, effects, frame, bodyTag, title, wrap, md5
 *
 * Revised for TYPO3 3.6 June/2003 by Kasper Skårhøj
 *
 * @author		Kasper Skårhøj	<kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  112: class SC_tslib_showpic
 *  133:     function init()
 *  190:     function main()
 *  237:     function printContent()
 *
 * TOTAL FUNCTIONS: 3
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */




// *******************************
// Set error reporting
// *******************************
error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED);


// ***********************
// Paths are setup
// ***********************
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
define('PATH_tslib', PATH_site.'tslib/');
define('PATH_typo3conf', PATH_site.'typo3conf/');
define('TYPO3_mainDir', 'typo3/');		// This is the directory of the backend administration for the sites of this TYPO3 installation.

if (!@is_dir(PATH_typo3conf))	die('Cannot find configuration. This file is probably executed from the wrong location.');

require_once(PATH_t3lib.'class.t3lib_div.php');
require_once(PATH_t3lib.'class.t3lib_extmgm.php');

// ******************
// Including config
// ******************
require_once(PATH_t3lib.'config_default.php');
if (!defined ('TYPO3_db')) 	die ('The configuration file was not included.');

require_once(PATH_t3lib.'class.t3lib_db.php');
$TYPO3_DB = t3lib_div::makeInstance('t3lib_DB');


// *********************
// Error & Exception handling
// *********************
if ($TYPO3_CONF_VARS['SC_OPTIONS']['errors']['exceptionHandler'] !== '') {
	if ($TYPO3_CONF_VARS['SYS']['errorHandler'] !== '') {
			// register an error handler for the given errorHandlerErrors
		$errorHandler = t3lib_div::makeInstance($TYPO3_CONF_VARS['SYS']['errorHandler'], $TYPO3_CONF_VARS['SYS']['errorHandlerErrors']);
			// set errors which will be converted in an exception
		$errorHandler->setExceptionalErrors($TYPO3_CONF_VARS['SC_OPTIONS']['errors']['exceptionalErrors']);
	}
	$exceptionHandler = t3lib_div::makeInstance($TYPO3_CONF_VARS['SC_OPTIONS']['errors']['exceptionHandler']);
}






# NOTICE: ALL LINES above can be commented out since this script is now used via the ?eID=tx_cms_showpic parameter passed to index.php!
# For backwards compatibility in extensions using showpic.php directly this is kept for the version 4.0 until 4.5 where it is planned removed!

if (!defined ('PATH_typo3conf')) 	die ('The configuration path was not properly defined!');
require_once(PATH_t3lib.'class.t3lib_stdgraphic.php');





/**
 * Script Class, generating the page output.
 * Instantiated in the bottom of this script.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tslib
 */
class SC_tslib_showpic {
	var $content;		// Page content accumulated here.

		// Parameters loaded into these internal variables:
	var $file;
	var $width;
	var $height;
	var $sample;
	var $alternativeTempPath;
	var $effects;
	var $frame;
	var $bodyTag;
	var $title;
	var $wrap;
	var $md5;

	/**
	 * @var string
	 */
	protected $parametersEncoded;

	/**
	 * Init function, setting the input vars in the global space.
	 *
	 * @return	void
	 */
	function init()	{
			// Loading internal vars with the GET/POST parameters from outside:
		$this->file = t3lib_div::_GP('file');
		$parametersArray = t3lib_div::_GP('parameters');
		$this->frame = t3lib_div::_GP('frame');
		$this->md5 = t3lib_div::_GP('md5');

		// ***********************
		// Check parameters
		// ***********************
			// If no file-param or parameters are given, we must exit
		if (!$this->file || !isset($parametersArray) || !is_array($parametersArray)) {
			throw new UnexpectedValueException('Parameter Error: No file or no parameters given.', 1299514081);
		}

		$this->parametersEncoded = implode($parametersArray);

			// Chech md5-checksum: If this md5-value does not match the one submitted, then we fail... (this is a kind of security that somebody don't just hit the script with a lot of different parameters
		$md5_value = t3lib_div::hmac(
			implode(
				'|',
				array($this->file, $this->parametersEncoded)
			)
		);

		if ($md5_value!=$this->md5) {
			throw new UnexpectedValueException('Parameter Error: Wrong parameters sent.', 1299514082);
		}

		$parameters = unserialize(base64_decode($this->parametersEncoded));
		foreach ($parameters as $parameterName => $parameterValue) {
			$this->$parameterName = $parameterValue;
		}

		// ***********************
		// Check the file. If must be in a directory beneath the dir of this script...
		// $this->file remains unchanged, because of the code in stdgraphic, but we do check if the file exists within the current path
		// ***********************

		$test_file=PATH_site.$this->file;
		if (!t3lib_div::validPathStr($test_file))	{
			throw new UnexpectedValueException('Parameter Error: No valid filepath', 1299514083);
		}
		if (!@is_file($test_file))	{
			throw new UnexpectedValueException('The given file was not found', 1299514084);
		}
	}

	/**
	 * Main function which creates the image if needed and outputs the HTML code for the page displaying the image.
	 * Accumulates the content in $this->content
	 *
	 * @return	void
	 */
	function main()	{

			// Creating stdGraphic object, initialize it and make image:
		$img = t3lib_div::makeInstance('t3lib_stdGraphic');
		$img->mayScaleUp = 0;
		$img->init();
		if ($this->sample)	{$img->scalecmd = '-sample';}
		if ($this->alternativeTempPath && t3lib_div::inList($GLOBALS['TYPO3_CONF_VARS']['FE']['allowedTempPaths'],$this->alternativeTempPath))	{
			$img->tempPath = $this->alternativeTempPath;
		}

		// Need to connect to database, because this is used (typo3temp_db_tracking, cached image dimensions).
		$GLOBALS['TYPO3_DB']->connectDB();

		if (strstr($this->width . $this->height, 'm')) {
			$max = 'm';
		} else {
			$max = '';
		}

		$this->height = t3lib_div::intInRange($this->height,0);
		$this->width = t3lib_div::intInRange($this->width,0);
		if ($this->frame) {
			$this->frame = intval($this->frame);
		}
		$imgInfo = $img->imageMagickConvert($this->file,'web',$this->width.$max,$this->height,$img->IMparams($this->effects),$this->frame,'');

			// Create HTML output:
		$this->content='';
		$this->content.='
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<html>
<head>
	<title>'.htmlspecialchars($this->title ? $this->title : "Image").'</title>
	' . ($this->title ? '' : '<meta name="robots" content="noindex,follow" />') . '
</head>
		'.($this->bodyTag ? $this->bodyTag : '<body>');

		if (is_array($imgInfo))	{
			$wrapParts = explode('|',$this->wrap);
			$this->content.=trim($wrapParts[0]).$img->imgTag($imgInfo).trim($wrapParts[1]);
		}
		$this->content.='
		</body>
		</html>';
	}

	/**
	 * Outputs the content from $this->content
	 *
	 * @return	void
	 */
	function printContent()	{
		echo $this->content;
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/showpic.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/showpic.php']);
}



// Make instance:
$SOBE = t3lib_div::makeInstance('SC_tslib_showpic');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>