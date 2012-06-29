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
 * Revised for TYPO3 3.6 July/2003 by Kasper Skårhøj
 *
 * @author Kasper Skårhøj	<kasperYYYY@typo3.com>
 */
/**
 * Class for generating a thumbnail from the input parameters given to the script
 *
 * Input GET var, &file: relative or absolute reference to an imagefile. WILL be validated against PATH_site / lockRootPath
 * Input GET var, &size: integer-values defining size of thumbnail, format '[int]' or '[int]x[int]'
 *
 * Relative paths MUST BE the first two characters ONLY: eg: '../dir/file.gif', otherwise it is expect to be absolute
 *
 * @author Kasper Skårhøj	<kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class SC_t3lib_thumbs {
	var $include_once = array();

		// The output directory of temporary files in PATH_site
	var $outdir = 'typo3temp/';
	var $output = '';
	var $sizeDefault = '64x64';

		// Coming from $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
	var $imageList;
	/**
	 * will hold the file Object
	 *
	 * @var t3lib_file_File $input
	 */
	var $image;

		// Internal, static: GPvar:
		// Holds the input filename (GET: file)
	var $file;
		// Holds the input size (GET: size)
	var $size;
		// Last modification time of the supplied file
	var $mTime = 0;


	/**
	 * Initialize; reading parameters with GPvar and checking file path
	 * Results in internal var, $this->file, being set to the file object
	 * which should be used to make a thumbnail.
	 *
	 * @return	void
	 */
	function init() {
			// Setting GPvars:
			// Only needed for MD5 sum calculation of backwards-compatibility uploads/ files thumbnails.
		$size = t3lib_div::_GP('size');
		$filePathOrCombinedFileIdentifier = rawurldecode(t3lib_div::_GP('file'));
		$md5sum = t3lib_div::_GP('md5sum');

			// Image extension list is set:
			// valid extensions. OBS: No spaces in the list, all lowercase...
		$this->imageList = $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'];

			// Check if we got a combined file identifier of the form storageUid:fileIdentifer.
			// We need to distinguish it from absolute Windows paths by cbecking for an integer as first part.
		$parts = t3lib_div::trimExplode(':', $filePathOrCombinedFileIdentifier);

			// Best case: we get a sys_file UID
		if (t3lib_utility_Math::canBeInterpretedAsInteger($filePathOrCombinedFileIdentifier)) {
			/** @var t3lib_file_File $filePathOrCombinedFileIdentifier */
			$fileObject = t3lib_file_Factory::getInstance()->getFileObject($filePathOrCombinedFileIdentifier);

		} elseif (count($parts) <= 1 || !t3lib_utility_Math::canBeInterpretedAsInteger($parts[0])) {
				// TODO: Historically, the input parameter could also be an absolute path. This should be supported again to stay compatible.
				// We assume the FilePath to be a relative file path (as in backwards compatibility mode)
			$relativeFilePath = $filePathOrCombinedFileIdentifier;

				// The incoming relative path is relative to the typo3/ directory, but we need it relative to PATH_site. This is corrected here:
			if (substr($relativeFilePath, 0, 3) == '../') {
				$relativeFilePath = substr($relativeFilePath, 3);
			} else {
				$relativeFilePath = 'typo3/' . $relativeFilePath;
			}

			$relativeFilePath = ltrim($relativeFilePath, '/');
			$mTime = 0;

				// Checking for backpath and double slashes + the thumbnail can be made from files which are in the PATH_site OR the lockRootPath only!
			if (t3lib_div::isAllowedAbsPath(PATH_site . $relativeFilePath)) {
				$mTime = filemtime(PATH_site . $relativeFilePath);
			}

			if (strstr($relativeFilePath, '../') !== FALSE) {
					// Maybe this could be relaxed to not throw an error as long as the path is still within PATH_site
				$this->errorGif('File path', 'must not contain', '"../"');
			}

			if ($relativeFilePath && file_exists(PATH_site . $relativeFilePath)) {
					// Check file extension:
				$reg = array();
				if (preg_match('/(.*)\.([^\.]*$)/', $relativeFilePath, $reg)) {
					$ext = strtolower($reg[2]);
					$ext = ($ext=='jpeg') ? 'jpg' : $ext;
					if (!t3lib_div::inList($this->imageList, $ext)) {
						$this->errorGif('Not imagefile!', $ext, basename($relativeFilePath));
					}
				} else {
					$this->errorGif('Not imagefile!', 'No ext!', basename($relativeFilePath));
				}
			} else{
				$this->errorGif('Input file not found.', 'not found in thumbs.php', basename($relativeFilePath));
			}

				// Do an MD5 check to prevent viewing of images without permission
			$OK = FALSE;
			if ($mTime) {
					// Always use the absolute path for this check!
				$check = basename($relativeFilePath) . ':' . $mTime . ':' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];
				$md5_real = t3lib_div::shortMD5($check);
				if (!strcmp($md5_real, $md5sum)) {
					$OK = TRUE;
				}
			}

			$combinedIdentifier = '0:' . $relativeFilePath;
		} else {
			$combinedIdentifier = $filePathOrCombinedFileIdentifier;
			$OK = FALSE;
		}

		if (empty($fileObject)) {
			$fileObject = t3lib_file_Factory::getInstance()->getFileObjectFromCombinedIdentifier($combinedIdentifier);
		}

		if (empty($OK)) {
			$OK = $fileObject !== NULL && $fileObject->checkActionPermission('read') && $fileObject->calculateChecksum() == $md5sum;
		}

		if ($OK) {
			$this->image = $fileObject;
			$this->size = $size;
		} else {
				// Hide the path to the document root;
			throw new RuntimeException('TYPO3 Fatal Error: The requested image does not exist and/or MD5 checksum did not match. If the target file exists and its file name contains special characters, the setting of $TYPO3_CONF_VARS[SYS][systemLocale] might be wrong.', 1270853950);
		}
	}

	/**
	 * Create the thumbnail
	 * Will exit before return if all is well.
	 *
	 * @return void
	 */
	function main() {
			// If file exists, we make a thumbnail of the file.
		if (is_object($this->image)) {

				// Check file extension:
			if ($this->image->getExtension() == 'ttf') {
					// Make font preview... (will not return)
				$this->fontGif($this->image);
			} elseif (($this->image->getType() != t3lib_file_File::FILETYPE_IMAGE) && !t3lib_div::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $this->image->getExtension())) {
				$this->errorGif('Not imagefile!', 'No ext!', $this->image->getName());
			}

				// ... so we passed the extension test meaning that we are going to make a thumbnail here:
				// default
			if (!$this->size) {
				$this->size = $this->sizeDefault;
			}

				// I added extra check, so that the size input option could not be fooled to pass other values.
				// That means the value is exploded, evaluated to an integer and the imploded to [value]x[value].
				// Furthermore you can specify: size=340 and it'll be translated to 340x340.
				// explodes the input size (and if no "x" is found this will add size again so it is the same for both dimensions)
			$sizeParts = explode('x', $this->size . 'x' . $this->size);
				// Cleaning it up, only two parameters now.
			$sizeParts = array(t3lib_utility_Math::forceIntegerInRange($sizeParts[0], 1, 1000), t3lib_utility_Math::forceIntegerInRange($sizeParts[1], 1, 1000));
				// Imploding the cleaned size-value back to the internal variable
			$this->size = implode('x', $sizeParts);
				// Getting max value
			$sizeMax = max($sizeParts);

				// Init
			$outpath = PATH_site . $this->outdir;

				// Should be - ? 'png' : 'gif' - , but doesn't work (ImageMagick prob.?)
				// René: png work for me
			$thmMode = t3lib_utility_Math::forceIntegerInRange($GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails_png'], 0);
			$outext = ($this->image->getExtension() != 'jpg' || ($thmMode & 2)) ? ($thmMode & 1 ? 'png' : 'gif') : 'jpg';

			$outfile = 'tmb_' . substr(md5($this->image->getName() . $this->mtime . $this->size), 0, 10) . '.' . $outext;
			$this->output = $outpath . $outfile;

			if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['im']) {
					// If thumbnail does not exist, we generate it
				if (!file_exists($this->output)) {
					$parameters = '-sample ' . $this->size . ' ' . $this->wrapFileName($this->image->getForLocalProcessing(FALSE)) . '[0] ' . $this->wrapFileName($this->output);
					$cmd = t3lib_div::imageMagickCommand('convert', $parameters);
					t3lib_utility_Command::exec($cmd);
					if (!file_exists($this->output)) {
						$this->errorGif('No thumb', 'generated!', $this->image->getName());
					} else {
						t3lib_div::fixPermissions($this->output);
					}
				}
					// The thumbnail is read and output to the browser
				if (($fd = @fopen($this->output, 'rb'))) {
					$fileModificationTime = filemtime($this->output);
					header('Content-type: image/' . $outext);
					header('Last-Modified: '. date('r', $fileModificationTime));
					header('Etag: ' . md5($this->output) . '-' . $fileModificationTime);
						// Expiration time is choosen arbitrary to 1 month
					header('Expires: ' . date('r', $fileModificationTime + 30*24*60*60));
					fpassthru($fd);
					fclose($fd);
				} else {
					$this->errorGif('Read problem!', '', $this->output);
				}
			} else {
				exit;
			}
		} else {
			$this->errorGif('No valid', 'inputfile!', basename($this->image));
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
	 * @param string $l1 Text line 1
	 * @param string $l2 Text line 2
	 * @param string $l3 Text line 3
	 * @return void
	 */
	function errorGif($l1, $l2, $l3) {
		if (!$GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib']) {
			throw new RuntimeException(
				'TYPO3 Fatal Error: No gdlib. ' . $l1 . ' ' . $l2 . ' ' . $l3,
				1270853952
			);
		}

			// Creates the basis for the error image
		if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_png']) {
			header('Content-type: image/png');
			$im = imagecreatefrompng(PATH_typo3 . 'gfx/notfound_thumb.png');
		} else {
			header('Content-type: image/gif');
			$im = imagecreatefromgif(PATH_typo3 . 'gfx/notfound_thumb.gif');
		}
			// Sets background color and print color.
		$white = imageColorAllocate($im, 255, 255, 255);
		$black = imageColorAllocate($im, 0, 0, 0);

			// Prints the text strings with the build-in font functions of GD
		$x = 0;
		$font = 0;
		if ($l1) {
			imagefilledrectangle($im, $x, 9, 56, 16, $white);
			imageString($im, $font, $x, 9, $l1, $black);
		}
		if ($l2) {
			imagefilledrectangle($im, $x, 19, 56, 26, $white);
			imageString($im, $font, $x, 19, $l2, $black);
		}
		if ($l3) {
			imagefilledrectangle($im, $x, 29, 56, 36, $white);
			imageString($im, $font, $x, 29, substr($l3, -14), $black);
		}

			// Outputting the image stream and exit
		if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_png']) {
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
	 * @param string $font The filepath to the font file (absolute, probably)
	 * @return void
	 */
	function fontGif($font) {
		if (!$GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib']) {
			throw new RuntimeException(
				'TYPO3 Fatal Error: No gdlib.',
				1270853953
			);
		}

			// Create image and set background color to white.
		$im = imageCreate(250, 76);
		$white = imageColorAllocate($im, 255, 255, 255);
		$col = imageColorAllocate($im, 0, 0, 0);

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

			// Output PNG or GIF based on $GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_png']
		if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_png']) {
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
	 * @param string $inputName Filename to safeguard, must not be empty
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

// Make instance:
$SOBE = t3lib_div::makeInstance('SC_t3lib_thumbs');
$SOBE->init();
$SOBE->main();
?>