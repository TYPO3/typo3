<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2004 Kasper Skaarhoj (kasper@typo3.com)
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
 * Revised for TYPO3 3.6 July/2003 by Kasper Skaarhoj
 *
 * @author		Kasper Skaarhoj	<kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  114: class SC_t3lib_thumbs 
 *  135:     function init()	
 *  165:     function main()	
 *
 *              SECTION: OTHER FUNCTIONS:
 *  268:     function errorGif($l1,$l2,$l3)	
 *  320:     function fontGif($font)	
 *  367:     function wrapFileName($inputName)	
 *
 * TOTAL FUNCTIONS: 5
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


// *******************************
// Set error reporting 
// *******************************
error_reporting (E_ALL ^ E_NOTICE); 


 
// ******************
// Constants defined
// ******************
define('TYPO3_OS', stristr(PHP_OS,'win')&&!stristr(PHP_OS,'darwin')?'WIN':'');
define('TYPO3_MODE','BE');
define('PATH_thisScript',str_replace('//','/', str_replace('\\','/', php_sapi_name()=='cgi'||php_sapi_name()=='isapi'||php_sapi_name()=='cgi-fcgi' ? $HTTP_SERVER_VARS['PATH_TRANSLATED']:$HTTP_SERVER_VARS['SCRIPT_FILENAME'])));

define('PATH_site', ereg_replace('[^/]*.[^/]*$','',PATH_thisScript));		// the path to the website folder (see init.php)
define('PATH_typo3conf', PATH_site.'typo3conf/');
define('PATH_t3lib', PATH_site.'t3lib/');
define('TYPO3_mainDir', 'typo3/');		// This is the directory of the backend administration for the sites of this TYPO3 installation. 

// ******************
// Including config
// ******************
require(PATH_t3lib.'class.t3lib_div.php');
require(PATH_t3lib.'class.t3lib_extmgm.php');

require(PATH_t3lib.'config_default.php');
if (!defined ('TYPO3_db')) 	die ('The configuration file was not included.');
if (!$TYPO3_CONF_VARS['GFX']['image_processing'])	die ('ImageProcessing was disabled!');

require(PATH_t3lib.'class.t3lib_db.php');		// The database library
$TYPO3_DB = t3lib_div::makeInstance('t3lib_DB');
















/**
 * Class for generating a thumbnail from the input parameters given to the script
 *
 * Input GET var, &file: 		relative or absolute reference to an imagefile. WILL be validated against PATH_site / lockRootPath
 * Input GET var, &size: 		integer-values defining size of thumbnail, format '[int]' or '[int]x[int]'
 *
 * Relative paths MUST BE the first two characters ONLY: eg: '../dir/file.gif', otherwise it is expect to be absolute
 *
 * @author		Kasper Skaarhoj	<kasper@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class SC_t3lib_thumbs {
	var $include_once=array();

	var $outdir = 'typo3temp/';		// The output directory of temporary files in PATH_site
	var $output = '';
	var $sizeDefault='56x56';

	var $imageList;	// Coming from $TYPO3_CONF_VARS['GFX']['imagefile_ext']
	var $input;		// Contains the absolute path to the file for which to make a thumbnail (after init())

		// Internal, static: GPvar:
	var $file;		// Holds the input filename (GET: file)
	var $size;		// Holds the input size (GET: size)
	
	
	/**
	 * Initialize; reading parameters with GPvar and checking file path
	 * Results in internal var, $this->input, being set to the absolute path of the file for which to make the thumbnail.
	 *
	 * @return	void
	 */
	function init()	{
		global $TYPO3_CONF_VARS;

			// Setting GPvars:
		$this->file = t3lib_div::_GP('file');
		$this->size = t3lib_div::_GP('size');

			// Image extension list is set:
		$this->imageList = $TYPO3_CONF_VARS['GFX']['imagefile_ext'];			// valid extensions. OBS: No spaces in the list, all lowercase...
		
			// if the filereference $this->file is relative, we correct the path
		if (substr($this->file,0,3)=='../')	{
			$this->input = PATH_site.ereg_replace('^\.\./','',$this->file);
		} else {
			$this->input = $this->file;
		}
		
			// Now the path is absolute.
			// Checking for backpath and double slashes + the thumbnail can be made from files which are in the PATH_site OR the lockRootPath only!
		if (!t3lib_div::isAllowedAbsPath($this->input))	{
			$this->input='';
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
		if ($this->input && @file_exists($this->input))	{

				// Check file extension:
			if (ereg('(.*)\.([^\.]*$)',$this->input,$reg))	{
				$ext=strtolower($reg[2]);
				$ext=($ext=='jpeg')?'jpg':$ext;
				if ($ext=='ttf')	{
					$this->fontGif($this->input);	// Make font preview... (will not return)
				} elseif (!t3lib_div::inList($this->imageList, $ext))	{
					$this->errorGif('Not imagefile!',$ext,$this->input);
				}
			} else {
				$this->errorGif('Not imagefile!','No ext!',$this->input);	
			}
				
				// ... so we passed the extension test meaning that we are going to make a thumbnail here:
			$this->size = $this->size ? $this->size : $this->sizeDefault;	// default

				//I added extra check, so that the size input option could not be fooled to pass other values. That means the value is exploded, evaluated to an integer and the imploded to [value]x[value]. Furthermore you can specify: size=340 and it'll be translated to 340x340.
			$sizeParts = explode('x', $this->size.'x'.$this->size);	// explodes the input size (and if no "x" is found this will add size again so it is the same for both dimensions)
			$sizeParts = array(t3lib_div::intInRange($sizeParts[0],1,1000),t3lib_div::intInRange($sizeParts[1],1,1000));	// Cleaning it up, only two parameters now.
			$this->size = implode('x',$sizeParts);		// Imploding the cleaned size-value back to the internal variable
			$sizeMax = max($sizeParts);	// Getting max value

				// Init
			$mtime = filemtime($this->input);
			$outpath = PATH_site.$this->outdir;

				// Should be - ? 'png' : 'gif' - , but doesn't work (ImageMagick prob.?)
				// René: png work for me
			$thmMode = t3lib_div::intInRange($TYPO3_CONF_VARS['GFX']['thumbnails_png'],0);
			$outext = ($ext!='jpg' || ($thmMode & 2)) ? ($thmMode & 1 ? 'png' : 'gif') : 'jpg';

			$outfile = 'tmb_'.substr(md5($this->input.$mtime.$this->size),0,10).'.'.$outext;
			$this->output = $outpath.$outfile;
		
			if ($TYPO3_CONF_VARS['GFX']['im'])	{
					// If thumbnail does not exist, we generate it
				if (!@file_exists($this->output))	{
/*					if (strstr($this->input,' ') || strstr($this->output,' '))	{
						$this->errorGif('Spaces in','filepath',$this->input);
					}
*/						// 16 colors for small (56) thumbs, 64 for bigger and all for jpegs
					if ($outext=='jpg')	{
						$colors = '';
					} else {
						$colors = ($sizeMax>56)?'-colors 64':'-colors 16';
					}
					$cmd = ($TYPO3_CONF_VARS['GFX']['im_path_lzw'] ? $TYPO3_CONF_VARS['GFX']['im_path_lzw'] : $TYPO3_CONF_VARS['GFX']['im_path']).
								'convert -sample '.$this->size.' '.$colors.' '.$this->wrapFileName($this->input.'[0]').' '.$this->wrapFileName($this->output);

		//			echo $cmd;
					exec($cmd);
					if (!@file_exists($this->output))	{
						$this->errorGif('No thumb','generated!',$this->input);
					}
				}
					// The thumbnail is read and output to the browser
				if($fd = @fopen($this->output,'rb'))	{
					Header('Content-type: image/'.$outext);
					while (!feof($fd))	{
						echo fread( $fd, 10000 );
					}
					fclose( $fd );
				} else {
					$this->errorGif('Read problem!','',$this->output);
				}
			} else exit;
		} else {
			$this->errorGif('No valid','inputfile!',$this->input);
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

		if (!$TYPO3_CONF_VARS['GFX']['gdlib'])	die($l1.' '.$l2.' '.$l3);
		
			// Creates the basis for the error image
		if ($TYPO3_CONF_VARS['GFX']['gdlib_png'])	{
			Header('Content-type: image/png');
			$im = imagecreatefrompng(PATH_t3lib.'gfx/notfound_thumb.png');
		} else {
			Header('Content-type: image/gif');
			$im = imagecreatefromgif(PATH_t3lib.'gfx/notfound_thumb.gif');
		}
			// Sets background color and print color.
	    $white = ImageColorAllocate($im, 0,0,0);
	    $black = ImageColorAllocate($im, 255,255,0);
		
			// Prints the text strings with the build-in font functions of GD
		$x=0; 
		$font=0;
		if ($l1)	{
			imagefilledrectangle($im, $x, 9, 56, 16, $black);
	    	ImageString($im,$font,$x,9,$l1,$white);	
		}
		if ($l2)	{
			imagefilledrectangle($im, $x, 19, 56, 26, $black);
	    	ImageString($im,$font,$x,19,$l2,$white);	
		}
		if ($l3)	{
			imagefilledrectangle($im, $x, 29, 56, 36, $black);
	    	ImageString($im,$font,$x,29,substr($l3,-14),$white);	
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

		if (!$TYPO3_CONF_VARS['GFX']['gdlib'])	die('');
	
			// Create image and set background color to white.
		$im = ImageCreate(250,76);
	    $white = ImageColorAllocate($im, 255,255,255);
	    $col = ImageColorAllocate($im, 0,0,0);
		
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
			Header('Content-type: image/png');
			imagePng($im);
		} else {
			Header('Content-type: image/gif');
			imageGif($im);
		}
		imagedestroy($im);
		exit;
	}

	/**
	 * Wrapping the input filename in double-quotes
	 *
	 * @param	string		Input filename
	 * @return	string		The output wrapped in "" (if there are spaces in the filepath)
	 * @access private
	 */
	function wrapFileName($inputName)	{
		if (strstr($inputName,' '))	{
			$inputName='"'.$inputName.'"';
		}
		return $inputName;
	}
}

// Include extension class?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/thumbs.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/thumbs.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('SC_t3lib_thumbs');
$SOBE->init();
$SOBE->main();
?>