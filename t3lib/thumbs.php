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
 * Generates a thumbnail and returns an image stream, either GIF/PNG or JPG
 *
 * $Id$
 * Revised for TYPO3 3.6 July/2003 by Kasper Skårhøj
 *
 * @author		Kasper Skårhøj	<kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  113: class SC_t3lib_thumbs
 *  134:     function init()
 *  164:     function main()
 *
 *              SECTION: OTHER FUNCTIONS:
 *  267:     function errorGif($l1,$l2,$l3)
 *  319:     function fontGif($font)
 *  366:     function wrapFileName($inputName)
 *
 * TOTAL FUNCTIONS: 5
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


// *******************************
// Set error reporting
// *******************************
if (defined('E_DEPRECATED')) {
	error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED);
} else {
	error_reporting(E_ALL ^ E_NOTICE);
}



// ******************
// Constants defined
// ******************
define('TYPO3_OS', stristr(PHP_OS,'win')&&!stristr(PHP_OS,'darwin')?'WIN':'');
define('TYPO3_MODE','BE');

if(!defined('PATH_thisScript')) {
	define('PATH_thisScript', str_replace('//', '/', str_replace('\\', '/',
		(PHP_SAPI == 'fpm-fcgi' || PHP_SAPI == 'cgi' || PHP_SAPI == 'isapi' || PHP_SAPI == 'cgi-fcgi') &&
		($_SERVER['ORIG_PATH_TRANSLATED'] ? $_SERVER['ORIG_PATH_TRANSLATED'] : $_SERVER['PATH_TRANSLATED']) ?
		($_SERVER['ORIG_PATH_TRANSLATED'] ? $_SERVER['ORIG_PATH_TRANSLATED'] : $_SERVER['PATH_TRANSLATED']) :
		($_SERVER['ORIG_SCRIPT_FILENAME'] ? $_SERVER['ORIG_SCRIPT_FILENAME'] : $_SERVER['SCRIPT_FILENAME']))));
}

if(!defined('PATH_site'))  		define('PATH_site', preg_replace('/[^\/]*.[^\/]*$/','',PATH_thisScript));		// the path to the website folder (see init.php)
if(!defined('PATH_t3lib')) 		define('PATH_t3lib', PATH_site.'t3lib/');
define('PATH_typo3conf', PATH_site.'typo3conf/');
define('TYPO3_mainDir', 'typo3/');		// This is the directory of the backend administration for the sites of this TYPO3 installation.
define('PATH_typo3', PATH_site.TYPO3_mainDir);


// ******************
// Including config
// ******************
require_once(PATH_t3lib.'class.t3lib_div.php');
require_once(PATH_t3lib.'class.t3lib_extmgm.php');

require(PATH_t3lib.'config_default.php');
if (!defined ('TYPO3_db')) 	die ('The configuration file was not included.');
if (!$TYPO3_CONF_VARS['GFX']['image_processing'])	die ('ImageProcessing was disabled!');

















/**
 * Class for generating a thumbnail from the input parameters given to the script
 *
 * Input GET var, &file: 		relative or absolute reference to an imagefile. WILL be validated against PATH_site / lockRootPath
 * Input GET var, &size: 		integer-values defining size of thumbnail, format '[int]' or '[int]x[int]'
 *
 * Relative paths MUST BE the first two characters ONLY: eg: '../dir/file.gif', otherwise it is expect to be absolute
 *
 * @author		Kasper Skårhøj	<kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class SC_t3lib_thumbs {
	var $include_once = array();

	var $outdir = 'typo3temp/';		// The output directory of temporary files in PATH_site
	var $output = '';
	var $sizeDefault='56x56';

	var $imageList;		// Coming from $TYPO3_CONF_VARS['GFX']['imagefile_ext']
	var $input;		// Contains the absolute path to the file for which to make a thumbnail (after init())

		// Internal, static: GPvar:
	var $file;		// Holds the input filename (GET: file)
	var $size;		// Holds the input size (GET: size)
	var $mtime = 0;		// Last modification time of the supplied file


	/**
	 * Initialize; reading parameters with GPvar and checking file path
	 * Results in internal var, $this->input, being set to the absolute path of the file for which to make the thumbnail.
	 *
	 * @return	void
	 */
	function init()	{
		global $TYPO3_CONF_VARS;

			// Setting GPvars:
		$file = t3lib_div::_GP('file');
		$size = t3lib_div::_GP('size');
		$md5sum = t3lib_div::_GP('md5sum');

			// Image extension list is set:
		$this->imageList = $TYPO3_CONF_VARS['GFX']['imagefile_ext'];			// valid extensions. OBS: No spaces in the list, all lowercase...

			// If the filereference $this->file is relative, we correct the path
		if (substr($file,0,3)=='../')	{
			$file = PATH_site.substr($file,3);
		}

		$mtime = 0;
			// Now the path is absolute.
			// Checking for backpath and double slashes + the thumbnail can be made from files which are in the PATH_site OR the lockRootPath only!
		if (t3lib_div::isAllowedAbsPath($file))	{
			$mtime = filemtime($file);
		}

			// Do an MD5 check to prevent viewing of images without permission
		$OK = FALSE;
		if ($mtime)	{
				// Always use the absolute path for this check!
			$check = basename($file).':'.$mtime.':'.$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];
			$md5_real = t3lib_div::shortMD5($check);
			if (!strcmp($md5_real,$md5sum))	{
				$OK = TRUE;
			}
		}

		if ($OK)	{
			$this->input = $file;
			$this->size = $size;
			$this->mtime = $mtime;
		} else {
				// hide the path to the document root;
			$publicFilename = substr($file, strlen(PATH_site));
			throw new RuntimeException(
				'TYPO3 Fatal Error: Image \'' . $publicFilename . '\' does not exist and/or MD5 checksum did not match.',
				1270853950
			);
		}
	}

	/**
	 * Create the thumbnail
	 * Will exit before return if all is well.
	 *
	 * @return	void
	 */
	function main()	{
		global $TYPO3_CONF_VARS;

			// If file exists, we make a thumbsnail of the file.
		if ($this->input && file_exists($this->input))	{

				// Check file extension:
			$reg = array();
			if (preg_match('/(.*)\.([^\.]*$)/',$this->input,$reg))	{
				$ext=strtolower($reg[2]);
				$ext=($ext=='jpeg')?'jpg':$ext;
				if ($ext=='ttf')	{
					$this->fontGif($this->input);	// Make font preview... (will not return)
				} elseif (!t3lib_div::inList($this->imageList, $ext))	{
					$this->errorGif('Not imagefile!',$ext,basename($this->input));
				}
			} else {
				$this->errorGif('Not imagefile!','No ext!',basename($this->input));
			}

				// ... so we passed the extension test meaning that we are going to make a thumbnail here:
			if (!$this->size) 	$this->size = $this->sizeDefault;	// default

				// I added extra check, so that the size input option could not be fooled to pass other values. That means the value is exploded, evaluated to an integer and the imploded to [value]x[value]. Furthermore you can specify: size=340 and it'll be translated to 340x340.
			$sizeParts = explode('x', $this->size.'x'.$this->size);	// explodes the input size (and if no "x" is found this will add size again so it is the same for both dimensions)
			$sizeParts = array(t3lib_div::intInRange($sizeParts[0],1,1000),t3lib_div::intInRange($sizeParts[1],1,1000));	// Cleaning it up, only two parameters now.
			$this->size = implode('x',$sizeParts);		// Imploding the cleaned size-value back to the internal variable
			$sizeMax = max($sizeParts);	// Getting max value

				// Init
			$outpath = PATH_site.$this->outdir;

				// Should be - ? 'png' : 'gif' - , but doesn't work (ImageMagick prob.?)
				// René: png work for me
			$thmMode = t3lib_div::intInRange($TYPO3_CONF_VARS['GFX']['thumbnails_png'],0);
			$outext = ($ext!='jpg' || ($thmMode & 2)) ? ($thmMode & 1 ? 'png' : 'gif') : 'jpg';

			$outfile = 'tmb_'.substr(md5($this->input.$this->mtime.$this->size),0,10).'.'.$outext;
			$this->output = $outpath.$outfile;

			if ($TYPO3_CONF_VARS['GFX']['im'])	{
					// If thumbnail does not exist, we generate it
				if (!file_exists($this->output))	{
					$parameters = '-sample ' . $this->size . ' ' . $this->wrapFileName($this->input) . '[0] ' . $this->wrapFileName($this->output);
					$cmd = t3lib_div::imageMagickCommand('convert', $parameters);
					t3lib_utility_Command::exec($cmd);
					if (!file_exists($this->output))	{
						$this->errorGif('No thumb','generated!',basename($this->input));
					} else {
						t3lib_div::fixPermissions($this->output);
					}
				}
					// The thumbnail is read and output to the browser
				if($fd = @fopen($this->output,'rb'))	{
					header('Content-type: image/'.$outext);
					fpassthru($fd);
					fclose($fd);
				} else {
					$this->errorGif('Read problem!','',$this->output);
				}
			} else exit;
		} else {
			$this->errorGif('No valid','inputfile!',basename($this->input));
		}
	}











	/***************************
	 *
	 * OTHER FUNCTIONS:
	 *
	 ***************************/

	/**
	 * Creates error image based on gfx/notfound_thumb.png
	 * Requires GD lib enabled, otherwise it will exit with the three textstrings outputted as text.
	 * Outputs the image stream to browser and exits!
	 *
	 * @param	string		Text line 1
	 * @param	string		Text line 2
	 * @param	string		Text line 3
	 * @return	void
	 */
	function errorGif($l1,$l2,$l3)	{
		global $TYPO3_CONF_VARS;

		if (!$TYPO3_CONF_VARS['GFX']['gdlib']) {
			throw new RuntimeException(
				'TYPO3 Fatal Error: No gdlib. ' . $l1 . ' ' . $l2 . ' ' . $l3,
				1270853952
			);
		}

			// Creates the basis for the error image
		if ($TYPO3_CONF_VARS['GFX']['gdlib_png'])	{
			header('Content-type: image/png');
			$im = imagecreatefrompng(PATH_typo3.'gfx/notfound_thumb.png');
		} else {
			header('Content-type: image/gif');
			$im = imagecreatefromgif(PATH_typo3.'gfx/notfound_thumb.gif');
		}
			// Sets background color and print color.
		$white = imageColorAllocate($im, 0,0,0);
		$black = imageColorAllocate($im, 255,255,0);

			// Prints the text strings with the build-in font functions of GD
		$x=0;
		$font=0;
		if ($l1)	{
			imagefilledrectangle($im, $x, 9, 56, 16, $black);
			imageString($im,$font,$x,9,$l1,$white);
		}
		if ($l2)	{
			imagefilledrectangle($im, $x, 19, 56, 26, $black);
			imageString($im,$font,$x,19,$l2,$white);
		}
		if ($l3)	{
			imagefilledrectangle($im, $x, 29, 56, 36, $black);
			imageString($im,$font,$x,29,substr($l3,-14),$white);
		}

			// Outputting the image stream and exit
		if ($TYPO3_CONF_VARS['GFX']['gdlib_png'])	{
			imagePng($im);
		} else {
			imageGif($im);
		}
		imagedestroy($im);
		exit;
	}

	/**
	 * Creates a font-preview thumbnail.
	 * This means a PNG/GIF file with the text "AaBbCc...." set with the font-file given as input and in various sizes to show how the font looks
	 * Requires GD lib enabled.
	 * Outputs the image stream to browser and exits!
	 *
	 * @param	string		The filepath to the font file (absolute, probably)
	 * @return	void
	 */
	function fontGif($font)	{
		global $TYPO3_CONF_VARS;

		if (!$TYPO3_CONF_VARS['GFX']['gdlib']) {
			throw new RuntimeException(
				'TYPO3 Fatal Error: No gdlib.',
				1270853953
			);
		}

			// Create image and set background color to white.
		$im = imageCreate(250,76);
		$white = imageColorAllocate($im, 255,255,255);
		$col = imageColorAllocate($im, 0,0,0);

			// The test string and offset in x-axis.
		$string = 'AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZzÆæØøÅåÄäÖöÜüß';
		$x=13;

			// Print (with non-ttf font) the size displayed
		imagestring ($im, 1, 0, 2, '10', $col);
		imagestring ($im, 1, 0, 15, '12', $col);
		imagestring ($im, 1, 0, 30, '14', $col);
		imagestring ($im, 1, 0, 47, '18', $col);
		imagestring ($im, 1, 0, 68, '24', $col);

			// Print with ttf-font the test string
		imagettftext ($im, t3lib_div::freetypeDpiComp(10), 0, $x, 8, $col, $font, $string);
		imagettftext ($im, t3lib_div::freetypeDpiComp(12), 0, $x, 21, $col, $font, $string);
		imagettftext ($im, t3lib_div::freetypeDpiComp(14), 0, $x, 36, $col, $font, $string);
		imagettftext ($im, t3lib_div::freetypeDpiComp(18), 0, $x, 53, $col, $font, $string);
		imagettftext ($im, t3lib_div::freetypeDpiComp(24), 0, $x, 74, $col, $font, $string);

			// Output PNG or GIF based on $TYPO3_CONF_VARS['GFX']['gdlib_png']
		if ($TYPO3_CONF_VARS['GFX']['gdlib_png'])	{
			header('Content-type: image/png');
			imagePng($im);
		} else {
			header('Content-type: image/gif');
			imageGif($im);
		}
		imagedestroy($im);
		exit;
	}

	/**
	 * Escapes a file name so it can safely be used on the command line.
	 *
	 * @param string $inputName filename to safeguard, must not be empty
	 *
	 * @return string $inputName escaped as needed
	 */
	protected function wrapFileName($inputName) {
		if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem']) {
			$currentLocale = setlocale(LC_CTYPE, 0);
			setlocale(LC_CTYPE, $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLocale']);
		}
		$escapedInputName = escapeshellarg($inputName);
		if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem']) {
			setlocale(LC_CTYPE, $currentLocale);
		}
		return $escapedInputName;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/thumbs.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/thumbs.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('SC_t3lib_thumbs');
$SOBE->init();
$SOBE->main();

?>
