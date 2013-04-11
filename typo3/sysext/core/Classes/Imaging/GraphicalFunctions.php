<?php
namespace TYPO3\CMS\Core\Imaging;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Standard graphical functions
 *
 * Revised for TYPO3 3.6 July/2003 by Kasper Skårhøj
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * Class contains a bunch of cool functions for manipulating graphics with GDlib/Freetype and ImageMagick
 * VERY OFTEN used with gifbuilder that extends this class and provides a TypoScript API to using these functions
 *
 * With TYPO3 4.4 GDlib 1.x support was dropped, also an option from $TYPO3_CONF_VARS
 * $TYPO3_CONF_VARS['GFX']['gdlib_2'] = 0,	// String/Boolean. Set this if you are using the new GDlib 2.0.1+. If you don't set this flag and still use GDlib2, you might encounter strange behaviours like black images etc. This feature might take effect only if ImageMagick is installed and working as well! You can also use the value "no_imagecopyresized_fix" - in that case it will NOT try to fix a known issue where "imagecopyresized" does not work correctly.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder
 */
class GraphicalFunctions {

	// Internal configuration, set in init()
	// The ImageMagick filename used for combining two images. This name changed during the versions.
	/**
	 * @todo Define visibility
	 */
	public $combineScript = 'combine';

	// If set, there is no frame pointer prepended to the filenames.
	/**
	 * @todo Define visibility
	 */
	public $noFramePrepended = 0;

	// If set, imagecopyresized will not be called directly. For GD2 (some PHP installs?)
	/**
	 * @todo Define visibility
	 */
	public $imagecopyresized_fix = 0;

	// This should be changed to 'png' if you want this class to read/make PNG-files instead!
	/**
	 * @todo Define visibility
	 */
	public $gifExtension = 'gif';

	// File formats supported by gdlib. This variable get's filled in "init" method
	/**
	 * @todo Define visibility
	 */
	public $gdlibExtensions = '';

	// Set to TRUE if generated png's should be truecolor by default
	/**
	 * @todo Define visibility
	 */
	public $png_truecolor = FALSE;

	/**
	 * defines the RGB colorspace to use
	 *
	 * @var string
	 */
	protected $colorspace = 'RGB';

	/**
	 * colorspace names allowed
	 *
	 * @var array
	 */
	protected $allowedColorSpaceNames = array(
		'CMY',
		'CMYK',
		'Grey',
		'HCL',
		'HSB',
		'HSL',
		'HWB',
		'Lab',
		'LCH',
		'LMS',
		'Log',
		'Luv',
		'OHTA',
		'Rec601Luma',
		'Rec601YCbCr',
		'Rec709Luma',
		'Rec709YCbCr',
		'RGB',
		'sRGB',
		'Transparent',
		'XYZ',
		'YCbCr',
		'YCC',
		'YIQ',
		'YCbCr',
		'YUV'
	);

	// 16777216 Colors is the maximum value for PNG, JPEG truecolor images (24-bit, 8-bit / Channel)
	/**
	 * @todo Define visibility
	 */
	public $truecolorColors = 16777215;

	// If set, then all files in typo3temp will be logged in a database table. In addition to being a log of the files with original filenames, it also serves to secure that the same image is not rendered simultaneously by two different processes.
	/**
	 * @todo Define visibility
	 */
	public $enable_typo3temp_db_tracking = 0;

	// Commalist of file extensions perceived as images by TYPO3. List should be set to 'gif,png,jpeg,jpg' if IM is not available. Lowercase and no spaces between!
	/**
	 * @todo Define visibility
	 */
	public $imageFileExt = 'gif,jpg,jpeg,png,tif,bmp,tga,pcx,ai,pdf';

	// Commalist of web image extensions (can be shown by a webbrowser)
	/**
	 * @todo Define visibility
	 */
	public $webImageExt = 'gif,jpg,jpeg,png';

	// Will be ' -negate' if ImageMagick ver 5.2+. See init();
	/**
	 * @todo Define visibility
	 */
	public $maskNegate = '';

	/**
	 * @todo Define visibility
	 */
	public $NO_IM_EFFECTS = '';

	/**
	 * @todo Define visibility
	 */
	public $cmds = array(
		'jpg' => '',
		'jpeg' => '',
		'gif' => '',
		'png' => '-colors 64'
	);

	/**
	 * @todo Define visibility
	 */
	public $NO_IMAGE_MAGICK = '';

	/**
	 * @todo Define visibility
	 */
	public $V5_EFFECTS = 0;

	/**
	 * @todo Define visibility
	 */
	public $mayScaleUp = 1;

	// Variables for testing, alternative usage etc.
	// Filename prefix for images scaled in imageMagickConvert()
	/**
	 * @todo Define visibility
	 */
	public $filenamePrefix = '';

	// Forcing the output filename of imageMagickConvert() to this value. However after calling imageMagickConvert() it will be set blank again.
	/**
	 * @todo Define visibility
	 */
	public $imageMagickConvert_forceFileNameBody = '';

	// This flag should always be FALSE. If set TRUE, imageMagickConvert will always write a new file to the tempdir! Used for debugging.
	/**
	 * @todo Define visibility
	 */
	public $dontCheckForExistingTempFile = 0;

	// Prevents imageMagickConvert() from compressing the gif-files with \TYPO3\CMS\Core\Utility\GeneralUtility::gif_compress()
	/**
	 * @todo Define visibility
	 */
	public $dontCompress = 0;

	// For debugging ONLY!
	/**
	 * @todo Define visibility
	 */
	public $dontUnlinkTempFiles = 0;

	// For debugging only. Filenames will not be based on mtime and only filename (not path) will be used. This key is also included in the hash of the filename...
	/**
	 * @todo Define visibility
	 */
	public $alternativeOutputKey = '';

	// Internal:
	// All ImageMagick commands executed is stored in this array for tracking. Used by the Install Tools Image section
	/**
	 * @todo Define visibility
	 */
	public $IM_commands = array();

	/**
	 * @todo Define visibility
	 */
	public $workArea = array();

	/**
	 * Preserve the alpha transparency layer of read PNG images
	 *
	 * @var boolean
	 */
	protected $saveAlphaLayer = FALSE;

	// Constants:
	// The temp-directory where to store the files. Normally relative to PATH_site but is allowed to be the absolute path AS LONG AS it is a subdir to PATH_site.
	/**
	 * @todo Define visibility
	 */
	public $tempPath = 'typo3temp/';

	// Prefix for relative paths. Used in "show_item.php" script. Is prefixed the output file name IN imageMagickConvert()
	/**
	 * @todo Define visibility
	 */
	public $absPrefix = '';

	// ImageMagick scaling command; "-geometry" eller "-sample". Used in makeText() and imageMagickConvert()
	/**
	 * @todo Define visibility
	 */
	public $scalecmd = '-geometry';

	// Used by v5_blur() to simulate 10 continuous steps of blurring
	/**
	 * @todo Define visibility
	 */
	public $im5fx_blurSteps = '1x2,2x2,3x2,4x3,5x3,5x4,6x4,7x5,8x5,9x5';

	// Used by v5_sharpen() to simulate 10 continuous steps of sharpening.
	/**
	 * @todo Define visibility
	 */
	public $im5fx_sharpenSteps = '1x2,2x2,3x2,2x3,3x3,4x3,3x4,4x4,4x5,5x5';

	// This is the limit for the number of pixels in an image before it will be rendered as JPG instead of GIF/PNG
	/**
	 * @todo Define visibility
	 */
	public $pixelLimitGif = 10000;

	// Array mapping HTML color names to RGB values.
	/**
	 * @todo Define visibility
	 */
	public $colMap = array(
		'aqua' => array(0, 255, 255),
		'black' => array(0, 0, 0),
		'blue' => array(0, 0, 255),
		'fuchsia' => array(255, 0, 255),
		'gray' => array(128, 128, 128),
		'green' => array(0, 128, 0),
		'lime' => array(0, 255, 0),
		'maroon' => array(128, 0, 0),
		'navy' => array(0, 0, 128),
		'olive' => array(128, 128, 0),
		'purple' => array(128, 0, 128),
		'red' => array(255, 0, 0),
		'silver' => array(192, 192, 192),
		'teal' => array(0, 128, 128),
		'yellow' => array(255, 255, 0),
		'white' => array(255, 255, 255)
	);

	/**
	 * Charset conversion object:
	 *
	 * @var \TYPO3\CMS\Core\Charset\CharsetConverter
	 * @todo Define visibility
	 */
	public $csConvObj;

	// Is set to the native character set of the input strings.
	/**
	 * @todo Define visibility
	 */
	public $nativeCharset = '';

	/**
	 * Init function. Must always call this when using the class.
	 * This function will read the configuration information from $GLOBALS['TYPO3_CONF_VARS']['GFX'] can set some values in internal variables.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function init() {
		$gfxConf = $GLOBALS['TYPO3_CONF_VARS']['GFX'];
		if (function_exists('imagecreatefromjpeg') && function_exists('imagejpeg')) {
			$this->gdlibExtensions .= ',jpg,jpeg';
		}
		if (function_exists('imagecreatefrompng') && function_exists('imagepng')) {
			$this->gdlibExtensions .= ',png';
		}
		if (function_exists('imagecreatefromgif') && function_exists('imagegif')) {
			$this->gdlibExtensions .= ',gif';
		}
		if ($gfxConf['png_truecolor']) {
			$this->png_truecolor = TRUE;
		}

		if ($gfxConf['colorspace'] && in_array($gfxConf['colorspace'], $this->allowedColorSpaceNames, TRUE)) {
			$this->colorspace = $gfxConf['colorspace'];
		}

		if (!$gfxConf['im']) {
			$this->NO_IMAGE_MAGICK = 1;
		}
		if (!$this->NO_IMAGE_MAGICK && (!$gfxConf['im_version_5'] || $gfxConf['im_version_5'] === 'im4' || $gfxConf['im_version_5'] === 'im5')) {
			throw new \RuntimeException('Your TYPO3 installation is configured to use an old version of ImageMagick, which is not supported anymore. ' . 'Please upgrade to ImageMagick version 6 or GraphicksMagick and set $TYPO3_CONF_VARS[\'GFX\'][\'im_version_5\'] appropriately.', 1305059666);
		}
		// When GIFBUILDER gets used in truecolor mode
		// No colors parameter if we generate truecolor images.
		if ($this->png_truecolor) {
			$this->cmds['png'] = '';
		}
		// Setting default JPG parameters:
		$this->jpegQuality = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($gfxConf['jpg_quality'], 10, 100, 75);
		$this->cmds['jpg'] = ($this->cmds['jpeg'] = '-colorspace ' . $this->colorspace . ' -sharpen 50 -quality ' . $this->jpegQuality);
		if ($gfxConf['im_combine_filename']) {
			$this->combineScript = $gfxConf['im_combine_filename'];
		}
		if ($gfxConf['im_noFramePrepended']) {
			$this->noFramePrepended = 1;
		}
		// Kept for backwards compatibility, can be turned on manually through localconf.php,
		// but not through the installer anymore
		$this->imagecopyresized_fix = $gfxConf['gdlib_2'] === 'no_imagecopyresized_fix' ? 0 : 1;
		if ($gfxConf['gdlib_png']) {
			$this->gifExtension = 'png';
		}
		if ($gfxConf['enable_typo3temp_db_tracking']) {
			$this->enable_typo3temp_db_tracking = $gfxConf['enable_typo3temp_db_tracking'];
		}
		$this->imageFileExt = $gfxConf['imagefile_ext'];
		// This should be set if ImageMagick ver. 5+ is used.
		if ($gfxConf['im_negate_mask']) {
			// Boolean. Indicates if the mask images should be inverted first.
			// This depends of the ImageMagick version. Below ver. 5.1 this should be FALSE.
			// Above ImageMagick version 5.2+ it should be TRUE.
			// Just set the flag if the masks works opposite the intension!
			$this->maskNegate = ' -negate';
		}
		if ($gfxConf['im_no_effects']) {
			// Boolean. This is necessary if using ImageMagick 5+.
			// Effects in Imagemagick 5+ tends to render very slowly!!
			// - therefore must be disabled in order not to perform sharpen, blurring and such.
			$this->NO_IM_EFFECTS = 1;
			$this->cmds['jpg'] = ($this->cmds['jpeg'] = '-colorspace ' . $this->colorspace . ' -quality ' . $this->jpegQuality);
		}
		// ... but if 'im_v5effects' is set, don't care about 'im_no_effects'
		if ($gfxConf['im_v5effects']) {
			$this->NO_IM_EFFECTS = 0;
			$this->V5_EFFECTS = 1;
			if ($gfxConf['im_v5effects'] > 0) {
				$this->cmds['jpg'] = ($this->cmds['jpeg'] = '-colorspace ' . $this->colorspace . ' -quality ' . intval($gfxConf['jpg_quality']) . $this->v5_sharpen(10));
			}
		}
		// Secures that images are not scaled up.
		if ($gfxConf['im_noScaleUp']) {
			$this->mayScaleUp = 0;
		}
		if (TYPO3_MODE == 'FE') {
			$this->csConvObj = $GLOBALS['TSFE']->csConvObj;
		} elseif (is_object($GLOBALS['LANG'])) {
			// BE assumed:
			$this->csConvObj = $GLOBALS['LANG']->csConvObj;
		} else {
			// The object may not exist yet, so we need to create it now. Happens in the Install Tool for example.
			$this->csConvObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Charset\\CharsetConverter');
		}
		$this->nativeCharset = 'utf-8';
	}

	/*************************************************
	 *
	 * Layering images / "IMAGE" GIFBUILDER object
	 *
	 *************************************************/
	/**
	 * Implements the "IMAGE" GIFBUILDER object, when the "mask" property is TRUE.
	 * It reads the two images defined by $conf['file'] and $conf['mask'] and copies the $conf['file'] onto the input image pointer image using the $conf['mask'] as a grayscale mask
	 * The operation involves ImageMagick for combining.
	 *
	 * @param pointer $im GDlib image pointer
	 * @param array $conf TypoScript array with configuration for the GIFBUILDER object.
	 * @param array $workArea The current working area coordinates.
	 * @return void
	 * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder::make()
	 * @todo Define visibility
	 */
	public function maskImageOntoImage(&$im, $conf, $workArea) {
		if ($conf['file'] && $conf['mask']) {
			$imgInf = pathinfo($conf['file']);
			$imgExt = strtolower($imgInf['extension']);
			if (!\TYPO3\CMS\Core\Utility\GeneralUtility::inList($this->gdlibExtensions, $imgExt)) {
				$BBimage = $this->imageMagickConvert($conf['file'], $this->gifExtension);
			} else {
				$BBimage = $this->getImageDimensions($conf['file']);
			}
			$maskInf = pathinfo($conf['mask']);
			$maskExt = strtolower($maskInf['extension']);
			if (!\TYPO3\CMS\Core\Utility\GeneralUtility::inList($this->gdlibExtensions, $maskExt)) {
				$BBmask = $this->imageMagickConvert($conf['mask'], $this->gifExtension);
			} else {
				$BBmask = $this->getImageDimensions($conf['mask']);
			}
			if ($BBimage && $BBmask) {
				$w = imagesx($im);
				$h = imagesy($im);
				$tmpStr = $this->randomName();
				$theImage = $tmpStr . '_img.' . $this->gifExtension;
				$theDest = $tmpStr . '_dest.' . $this->gifExtension;
				$theMask = $tmpStr . '_mask.' . $this->gifExtension;
				// Prepare overlay image
				$cpImg = $this->imageCreateFromFile($BBimage[3]);
				$destImg = imagecreatetruecolor($w, $h);
				// Preserve alpha transparency
				if ($this->saveAlphaLayer) {
					imagesavealpha($destImg, TRUE);
					$Bcolor = imagecolorallocatealpha($destImg, 0, 0, 0, 127);
					imagefill($destImg, 0, 0, $Bcolor);
				} else {
					$Bcolor = ImageColorAllocate($destImg, 0, 0, 0);
					ImageFilledRectangle($destImg, 0, 0, $w, $h, $Bcolor);
				}
				$this->copyGifOntoGif($destImg, $cpImg, $conf, $workArea);
				$this->ImageWrite($destImg, $theImage);
				imageDestroy($cpImg);
				imageDestroy($destImg);
				// Prepare mask image
				$cpImg = $this->imageCreateFromFile($BBmask[3]);
				$destImg = imagecreatetruecolor($w, $h);
				if ($this->saveAlphaLayer) {
					imagesavealpha($destImg, TRUE);
					$Bcolor = imagecolorallocatealpha($destImg, 0, 0, 0, 127);
					imagefill($destImg, 0, 0, $Bcolor);
				} else {
					$Bcolor = ImageColorAllocate($destImg, 0, 0, 0);
					ImageFilledRectangle($destImg, 0, 0, $w, $h, $Bcolor);
				}
				$this->copyGifOntoGif($destImg, $cpImg, $conf, $workArea);
				$this->ImageWrite($destImg, $theMask);
				imageDestroy($cpImg);
				imageDestroy($destImg);
				// Mask the images
				$this->ImageWrite($im, $theDest);
				// Let combineExec handle maskNegation
				$this->combineExec($theDest, $theImage, $theMask, $theDest, TRUE);
				// The main image is loaded again...
				$backIm = $this->imageCreateFromFile($theDest);
				// ... and if nothing went wrong we load it onto the old one.
				if ($backIm) {
					if (!$this->saveAlphaLayer) {
						ImageColorTransparent($backIm, -1);
					}
					$im = $backIm;
				}
				// Unlink files from process
				if (!$this->dontUnlinkTempFiles) {
					unlink($theDest);
					unlink($theImage);
					unlink($theMask);
				}
			}
		}
	}

	/**
	 * Implements the "IMAGE" GIFBUILDER object, when the "mask" property is FALSE (using only $conf['file'])
	 *
	 * @param pointer $im GDlib image pointer
	 * @param array $conf TypoScript array with configuration for the GIFBUILDER object.
	 * @param array $workArea The current working area coordinates.
	 * @return void
	 * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder::make(), maskImageOntoImage()
	 * @todo Define visibility
	 */
	public function copyImageOntoImage(&$im, $conf, $workArea) {
		if ($conf['file']) {
			if (!\TYPO3\CMS\Core\Utility\GeneralUtility::inList($this->gdlibExtensions, $conf['BBOX'][2])) {
				$conf['BBOX'] = $this->imageMagickConvert($conf['BBOX'][3], $this->gifExtension);
				$conf['file'] = $conf['BBOX'][3];
			}
			$cpImg = $this->imageCreateFromFile($conf['file']);
			$this->copyGifOntoGif($im, $cpImg, $conf, $workArea);
			imageDestroy($cpImg);
		}
	}

	/**
	 * Copies two GDlib image pointers onto each other, using TypoScript configuration from $conf and the input $workArea definition.
	 *
	 * @param pointer $im GDlib image pointer, destination (bottom image)
	 * @param pointer $cpImg GDlib image pointer, source (top image)
	 * @param array $conf TypoScript array with the properties for the IMAGE GIFBUILDER object. Only used for the "tile" property value.
	 * @param array $workArea Work area
	 * @return void Works on the $im image pointer
	 * @access private
	 * @todo Define visibility
	 */
	public function copyGifOntoGif(&$im, $cpImg, $conf, $workArea) {
		$cpW = imagesx($cpImg);
		$cpH = imagesy($cpImg);
		$tile = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $conf['tile']);
		$tile[0] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($tile[0], 1, 20);
		$tile[1] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($tile[1], 1, 20);
		$cpOff = $this->objPosition($conf, $workArea, array($cpW * $tile[0], $cpH * $tile[1]));
		for ($xt = 0; $xt < $tile[0]; $xt++) {
			$Xstart = $cpOff[0] + $cpW * $xt;
			// If this image is inside of the workArea, then go on
			if ($Xstart + $cpW > $workArea[0]) {
				// X:
				if ($Xstart < $workArea[0]) {
					$cpImgCutX = $workArea[0] - $Xstart;
					$Xstart = $workArea[0];
				} else {
					$cpImgCutX = 0;
				}
				$w = $cpW - $cpImgCutX;
				if ($Xstart > $workArea[0] + $workArea[2] - $w) {
					$w = $workArea[0] + $workArea[2] - $Xstart;
				}
				// If this image is inside of the workArea, then go on
				if ($Xstart < $workArea[0] + $workArea[2]) {
					// Y:
					for ($yt = 0; $yt < $tile[1]; $yt++) {
						$Ystart = $cpOff[1] + $cpH * $yt;
						// If this image is inside of the workArea, then go on
						if ($Ystart + $cpH > $workArea[1]) {
							if ($Ystart < $workArea[1]) {
								$cpImgCutY = $workArea[1] - $Ystart;
								$Ystart = $workArea[1];
							} else {
								$cpImgCutY = 0;
							}
							$h = $cpH - $cpImgCutY;
							if ($Ystart > $workArea[1] + $workArea[3] - $h) {
								$h = $workArea[1] + $workArea[3] - $Ystart;
							}
							// If this image is inside of the workArea, then go on
							if ($Ystart < $workArea[1] + $workArea[3]) {
								$this->imagecopyresized($im, $cpImg, $Xstart, $Ystart, $cpImgCutX, $cpImgCutY, $w, $h, $w, $h);
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Alternative function for using the similar PHP function imagecopyresized(). Used for GD2 only.
	 *
	 * OK, the reason for this stupid fix is the following story:
	 * GD1.x was capable of copying two images together and combining their palettes! GD2 is apparently not.
	 * With GD2 only the palette of the dest-image is used which mostly results in totally black images when trying to
	 * copy a color-ful image onto the destination.
	 * The GD2-fix is to
	 * 1) Create a blank TRUE-COLOR image
	 * 2) Copy the destination image onto that one
	 * 3) Then do the actual operation; Copying the source (top image) onto that
	 * 4) ... and return the result pointer.
	 * 5) Reduce colors (if we do not, the result may become strange!)
	 * It works, but the resulting images is now a true-color PNG which may be very large.
	 * So, why not use 'imagetruecolortopalette ($im, TRUE, 256)' - well because it does NOT WORK! So simple is that.
	 *
	 * @param resource $dstImg Destination image
	 * @param resource $srcImg Source image
	 * @param integer $dstX Destination x-coordinate
	 * @param integer $dstY Destination y-coordinate
	 * @param integer $srcX Source x-coordinate
	 * @param integer $srcY Source y-coordinate
	 * @param integer $dstWidth Destination width
	 * @param integer $dstHeight Destination height
	 * @param integer $srcWidth Source width
	 * @param integer $srcHeight Source height
	 * @return void
	 * @access private
	 * @see \TYPO3\CMS\Backend\Utility\IconUtility::imagecopyresized()
	 * @todo Define visibility
	 */
	public function imagecopyresized(&$dstImg, $srcImg, $dstX, $dstY, $srcX, $srcY, $dstWidth, $dstHeight, $srcWidth, $srcHeight) {
		if ($this->imagecopyresized_fix && !$this->saveAlphaLayer) {
			// Make true color image
			$tmpImg = imagecreatetruecolor(imagesx($dstImg), imagesy($dstImg));
			// Copy the source image onto that
			imagecopyresized($tmpImg, $dstImg, 0, 0, 0, 0, imagesx($dstImg), imagesy($dstImg), imagesx($dstImg), imagesy($dstImg));
			// Then copy the source image onto that (the actual operation!)
			imagecopyresized($tmpImg, $srcImg, $dstX, $dstY, $srcX, $srcY, $dstWidth, $dstHeight, $srcWidth, $srcHeight);
			// Set the destination image
			$dstImg = $tmpImg;
		} else {
			imagecopyresized($dstImg, $srcImg, $dstX, $dstY, $srcX, $srcY, $dstWidth, $dstHeight, $srcWidth, $srcHeight);
		}
	}

	/********************************
	 *
	 * Text / "TEXT" GIFBUILDER object
	 *
	 ********************************/
	/**
	 * Implements the "TEXT" GIFBUILDER object
	 *
	 * @param pointer $im GDlib image pointer
	 * @param array $conf TypoScript array with configuration for the GIFBUILDER object.
	 * @param array $workArea The current working area coordinates.
	 * @return void
	 * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder::make()
	 * @todo Define visibility
	 */
	public function makeText(&$im, $conf, $workArea) {
		// Spacing
		list($spacing, $wordSpacing) = $this->calcWordSpacing($conf);
		// Position
		$txtPos = $this->txtPosition($conf, $workArea, $conf['BBOX']);
		$theText = $this->recodeString($conf['text']);
		if ($conf['imgMap'] && is_array($conf['imgMap.'])) {
			$this->addToMap($this->calcTextCordsForMap($conf['BBOX'][2], $txtPos, $conf['imgMap.']), $conf['imgMap.']);
		}
		if (!$conf['hideButCreateMap']) {
			// Font Color:
			$cols = $this->convertColor($conf['fontColor']);
			// NiceText is calculated
			if (!$conf['niceText']) {
				$Fcolor = ImageColorAllocate($im, $cols[0], $cols[1], $cols[2]);
				// antiAliasing is setup:
				$Fcolor = $conf['antiAlias'] ? $Fcolor : -$Fcolor;
				for ($a = 0; $a < $conf['iterations']; $a++) {
					// If any kind of spacing applys, we use this function:
					if ($spacing || $wordSpacing) {
						$this->SpacedImageTTFText($im, $conf['fontSize'], $conf['angle'], $txtPos[0], $txtPos[1], $Fcolor, self::prependAbsolutePath($conf['fontFile']), $theText, $spacing, $wordSpacing, $conf['splitRendering.']);
					} else {
						$this->renderTTFText($im, $conf['fontSize'], $conf['angle'], $txtPos[0], $txtPos[1], $Fcolor, $conf['fontFile'], $theText, $conf['splitRendering.'], $conf);
					}
				}
			} else {
				// NICETEXT::
				// options anti_aliased and iterations is NOT available when doing this!!
				$w = imagesx($im);
				$h = imagesy($im);
				$tmpStr = $this->randomName();
				$fileMenu = $tmpStr . '_menuNT.' . $this->gifExtension;
				$fileColor = $tmpStr . '_colorNT.' . $this->gifExtension;
				$fileMask = $tmpStr . '_maskNT.' . $this->gifExtension;
				// Scalefactor
				$sF = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($conf['niceText.']['scaleFactor'], 2, 5);
				$newW = ceil($sF * imagesx($im));
				$newH = ceil($sF * imagesy($im));
				// Make mask
				$maskImg = imagecreatetruecolor($newW, $newH);
				$Bcolor = ImageColorAllocate($maskImg, 255, 255, 255);
				ImageFilledRectangle($maskImg, 0, 0, $newW, $newH, $Bcolor);
				$Fcolor = ImageColorAllocate($maskImg, 0, 0, 0);
				// If any kind of spacing applies, we use this function:
				if ($spacing || $wordSpacing) {
					$this->SpacedImageTTFText($maskImg, $conf['fontSize'], $conf['angle'], $txtPos[0], $txtPos[1], $Fcolor, self::prependAbsolutePath($conf['fontFile']), $theText, $spacing, $wordSpacing, $conf['splitRendering.'], $sF);
				} else {
					$this->renderTTFText($maskImg, $conf['fontSize'], $conf['angle'], $txtPos[0], $txtPos[1], $Fcolor, $conf['fontFile'], $theText, $conf['splitRendering.'], $conf, $sF);
				}
				$this->ImageWrite($maskImg, $fileMask);
				ImageDestroy($maskImg);
				// Downscales the mask
				if ($this->NO_IM_EFFECTS) {
					if ($this->maskNegate) {
						// Negate 2 times makes no negate...
						$command = trim($this->scalecmd . ' ' . $w . 'x' . $h . '!');
					} else {
						$command = trim($this->scalecmd . ' ' . $w . 'x' . $h . '! -negate');
					}
				} else {
					if ($this->maskNegate) {
						$command = trim($conf['niceText.']['before'] . ' ' . $this->scalecmd . ' ' . $w . 'x' . $h . '! ' . $conf['niceText.']['after']);
					} else {
						$command = trim($conf['niceText.']['before'] . ' ' . $this->scalecmd . ' ' . $w . 'x' . $h . '! ' . $conf['niceText.']['after'] . ' -negate');
					}
					if ($conf['niceText.']['sharpen']) {
						if ($this->V5_EFFECTS) {
							$command .= $this->v5_sharpen($conf['niceText.']['sharpen']);
						} else {
							$command .= ' -sharpen ' . \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($conf['niceText.']['sharpen'], 1, 99);
						}
					}
				}
				$this->imageMagickExec($fileMask, $fileMask, $command);
				// Make the color-file
				$colorImg = imagecreatetruecolor($w, $h);
				$Ccolor = ImageColorAllocate($colorImg, $cols[0], $cols[1], $cols[2]);
				ImageFilledRectangle($colorImg, 0, 0, $w, $h, $Ccolor);
				$this->ImageWrite($colorImg, $fileColor);
				ImageDestroy($colorImg);
				// The mask is applied
				// The main pictures is saved temporarily
				$this->ImageWrite($im, $fileMenu);
				$this->combineExec($fileMenu, $fileColor, $fileMask, $fileMenu);
				// The main image is loaded again...
				$backIm = $this->imageCreateFromFile($fileMenu);
				// ... and if nothing went wrong we load it onto the old one.
				if ($backIm) {
					if (!$this->saveAlphaLayer) {
						ImageColorTransparent($backIm, -1);
					}
					$im = $backIm;
				}
				// Deleting temporary files;
				if (!$this->dontUnlinkTempFiles) {
					unlink($fileMenu);
					unlink($fileColor);
					unlink($fileMask);
				}
			}
		}
	}

	/**
	 * Calculates text position for printing the text onto the image based on configuration like alignment and workarea.
	 *
	 * @param array $conf TypoScript array for the TEXT GIFBUILDER object
	 * @param array $workArea Workarea definition
	 * @param array $BB Bounding box information, was set in \TYPO3\CMS\Frontend\Imaging\GifBuilder::start()
	 * @return array [0]=x, [1]=y, [2]=w, [3]=h
	 * @access private
	 * @see makeText()
	 * @todo Define visibility
	 */
	public function txtPosition($conf, $workArea, $BB) {
		$bbox = $BB[2];
		$angle = intval($conf['angle']) / 180 * pi();
		$conf['angle'] = 0;
		$straightBB = $this->calcBBox($conf);
		// offset, align, valign, workarea
		// [0]=x, [1]=y, [2]=w, [3]=h
		$result = array();
		$result[2] = $BB[0];
		$result[3] = $BB[1];
		$w = $workArea[2];
		$h = $workArea[3];
		switch ($conf['align']) {
		case 'right':

		case 'center':
			$factor = abs(cos($angle));
			$sign = cos($angle) < 0 ? -1 : 1;
			$len1 = $sign * $factor * $straightBB[0];
			$len2 = $sign * $BB[0];
			$result[0] = $w - ceil(($len2 * $factor + (1 - $factor) * $len1));
			$factor = abs(sin($angle));
			$sign = sin($angle) < 0 ? -1 : 1;
			$len1 = $sign * $factor * $straightBB[0];
			$len2 = $sign * $BB[1];
			$result[1] = ceil($len2 * $factor + (1 - $factor) * $len1);
			break;
		}
		switch ($conf['align']) {
		case 'right':
			break;
		case 'center':
			$result[0] = round($result[0] / 2);
			$result[1] = round($result[1] / 2);
			break;
		default:
			$result[0] = 0;
			$result[1] = 0;
			break;
		}
		$result = $this->applyOffset($result, \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $conf['offset']));
		$result = $this->applyOffset($result, $workArea);
		return $result;
	}

	/**
	 * Calculates bounding box information for the TEXT GIFBUILDER object.
	 *
	 * @param array $conf TypoScript array for the TEXT GIFBUILDER object
	 * @return array Array with three keys [0]/[1] being x/y and [2] being the bounding box array
	 * @access private
	 * @see txtPosition(), \TYPO3\CMS\Frontend\Imaging\GifBuilder::start()
	 * @todo Define visibility
	 */
	public function calcBBox($conf) {
		$sF = $this->getTextScalFactor($conf);
		list($spacing, $wordSpacing) = $this->calcWordSpacing($conf, $sF);
		$theText = $this->recodeString($conf['text']);
		$charInf = $this->ImageTTFBBoxWrapper($conf['fontSize'], $conf['angle'], $conf['fontFile'], $theText, $conf['splitRendering.'], $sF);
		$theBBoxInfo = $charInf;
		if ($conf['angle']) {
			$xArr = array($charInf[0], $charInf[2], $charInf[4], $charInf[6]);
			$yArr = array($charInf[1], $charInf[3], $charInf[5], $charInf[7]);
			$x = max($xArr) - min($xArr);
			$y = max($yArr) - min($yArr);
		} else {
			$x = $charInf[2] - $charInf[0];
			$y = $charInf[1] - $charInf[7];
		}
		// Set original lineHeight (used by line breaks):
		$theBBoxInfo['lineHeight'] = $y;
		// If any kind of spacing applys, we use this function:
		if ($spacing || $wordSpacing) {
			$x = 0;
			if (!$spacing && $wordSpacing) {
				$bits = explode(' ', $theText);
				foreach ($bits as $word) {
					$word .= ' ';
					$wordInf = $this->ImageTTFBBoxWrapper($conf['fontSize'], $conf['angle'], $conf['fontFile'], $word, $conf['splitRendering.'], $sF);
					$wordW = $wordInf[2] - $wordInf[0];
					$x += $wordW + $wordSpacing;
				}
			} else {
				$utf8Chars = $this->singleChars($theText);
				// For each UTF-8 char, do:
				foreach ($utf8Chars as $char) {
					$charInf = $this->ImageTTFBBoxWrapper($conf['fontSize'], $conf['angle'], $conf['fontFile'], $char, $conf['splitRendering.'], $sF);
					$charW = $charInf[2] - $charInf[0];
					$x += $charW + ($char == ' ' ? $wordSpacing : $spacing);
				}
			}
		} elseif (isset($conf['breakWidth']) && $conf['breakWidth'] && $this->getRenderedTextWidth($conf['text'], $conf) > $conf['breakWidth']) {
			$maxWidth = 0;
			$currentWidth = 0;
			$breakWidth = $conf['breakWidth'];
			$breakSpace = $this->getBreakSpace($conf, $theBBoxInfo);
			$wordPairs = $this->getWordPairsForLineBreak($conf['text']);
			// Iterate through all word pairs:
			foreach ($wordPairs as $index => $wordPair) {
				$wordWidth = $this->getRenderedTextWidth($wordPair, $conf);
				if ($index == 0 || $currentWidth + $wordWidth <= $breakWidth) {
					$currentWidth += $wordWidth;
				} else {
					$maxWidth = max($maxWidth, $currentWidth);
					$y += $breakSpace;
					// Restart:
					$currentWidth = $wordWidth;
				}
			}
			$x = max($maxWidth, $currentWidth) * $sF;
		}
		if ($sF > 1) {
			$x = ceil($x / $sF);
			$y = ceil($y / $sF);
			if (is_array($theBBoxInfo)) {
				foreach ($theBBoxInfo as &$value) {
					$value = ceil($value / $sF);
				}
				unset($value);
			}
		}
		return array($x, $y, $theBBoxInfo);
	}

	/**
	 * Adds an <area> tag to the internal variable $this->map which is used to accumulate the content for an ImageMap
	 *
	 * @param array $cords Coordinates for a polygon image map as created by ->calcTextCordsForMap()
	 * @param array $conf Configuration for "imgMap." property of a TEXT GIFBUILDER object.
	 * @return void
	 * @access private
	 * @see makeText(), calcTextCordsForMap()
	 * @todo Define visibility
	 */
	public function addToMap($cords, $conf) {
		$this->map .= '<area' . ' shape="poly"' . ' coords="' . implode(',', $cords) . '"' . ' href="' . htmlspecialchars($conf['url']) . '"' . ($conf['target'] ? ' target="' . htmlspecialchars($conf['target']) . '"' : '') . $JS . (strlen($conf['titleText']) ? ' title="' . htmlspecialchars($conf['titleText']) . '"' : '') . ' alt="' . htmlspecialchars($conf['altText']) . '" />';
	}

	/**
	 * Calculating the coordinates for a TEXT string on an image map. Used in an <area> tag
	 *
	 * @param array $cords Coordinates (from BBOX array)
	 * @param array $offset Offset array
	 * @param array $conf Configuration for "imgMap." property of a TEXT GIFBUILDER object.
	 * @return array
	 * @access private
	 * @see makeText(), calcTextCordsForMap()
	 * @todo Define visibility
	 */
	public function calcTextCordsForMap($cords, $offset, $conf) {
		$pars = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $conf['explode'] . ',');
		$newCords[0] = $cords[0] + $offset[0] - $pars[0];
		$newCords[1] = $cords[1] + $offset[1] + $pars[1];
		$newCords[2] = $cords[2] + $offset[0] + $pars[0];
		$newCords[3] = $cords[3] + $offset[1] + $pars[1];
		$newCords[4] = $cords[4] + $offset[0] + $pars[0];
		$newCords[5] = $cords[5] + $offset[1] - $pars[1];
		$newCords[6] = $cords[6] + $offset[0] - $pars[0];
		$newCords[7] = $cords[7] + $offset[1] - $pars[1];
		return $newCords;
	}

	/**
	 * Printing text onto an image like the PHP function imageTTFText does but in addition it offers options for spacing of letters and words.
	 * Spacing is done by printing one char at a time and this means that the spacing is rather uneven and probably not very nice.
	 * See
	 *
	 * @param pointer $im (See argument for PHP function imageTTFtext())
	 * @param integer $fontSize (See argument for PHP function imageTTFtext())
	 * @param integer $angle (See argument for PHP function imageTTFtext())
	 * @param integer $x (See argument for PHP function imageTTFtext())
	 * @param integer $y (See argument for PHP function imageTTFtext())
	 * @param integer $Fcolor (See argument for PHP function imageTTFtext())
	 * @param string $fontFile (See argument for PHP function imageTTFtext())
	 * @param string $text (See argument for PHP function imageTTFtext()). UTF-8 string, possibly with entities in.
	 * @param integer $spacing The spacing of letters in pixels
	 * @param integer $wordSpacing The spacing of words in pixels
	 * @param array $splitRenderingConf Array
	 * @param integer $sF Scale factor
	 * @return void
	 * @access private
	 * @todo Define visibility
	 */
	public function SpacedImageTTFText(&$im, $fontSize, $angle, $x, $y, $Fcolor, $fontFile, $text, $spacing, $wordSpacing, $splitRenderingConf, $sF = 1) {
		$spacing *= $sF;
		$wordSpacing *= $sF;
		if (!$spacing && $wordSpacing) {
			$bits = explode(' ', $text);
			foreach ($bits as $word) {
				$word .= ' ';
				$wordInf = $this->ImageTTFBBoxWrapper($fontSize, $angle, $fontFile, $word, $splitRenderingConf, $sF);
				$wordW = $wordInf[2] - $wordInf[0];
				$this->ImageTTFTextWrapper($im, $fontSize, $angle, $x, $y, $Fcolor, $fontFile, $word, $splitRenderingConf, $sF);
				$x += $wordW + $wordSpacing;
			}
		} else {
			$utf8Chars = $this->singleChars($text);
			// For each UTF-8 char, do:
			foreach ($utf8Chars as $char) {
				$charInf = $this->ImageTTFBBoxWrapper($fontSize, $angle, $fontFile, $char, $splitRenderingConf, $sF);
				$charW = $charInf[2] - $charInf[0];
				$this->ImageTTFTextWrapper($im, $fontSize, $angle, $x, $y, $Fcolor, $fontFile, $char, $splitRenderingConf, $sF);
				$x += $charW + ($char == ' ' ? $wordSpacing : $spacing);
			}
		}
	}

	/**
	 * Function that finds the right fontsize that will render the textstring within a certain width
	 *
	 * @param array $conf The TypoScript properties of the TEXT GIFBUILDER object
	 * @return integer The new fontSize
	 * @access private
	 * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder::start()
	 * @todo Define visibility
	 */
	public function fontResize($conf) {
		// You have to use +calc options like [10.h] in 'offset' to get the right position of your text-image, if you use +calc in XY height!!!!
		$maxWidth = intval($conf['maxWidth']);
		list($spacing, $wordSpacing) = $this->calcWordSpacing($conf);
		if ($maxWidth) {
			// If any kind of spacing applys, we use this function:
			if ($spacing || $wordSpacing) {
				return $conf['fontSize'];
			} else {
				do {
					// Determine bounding box.
					$bounds = $this->ImageTTFBBoxWrapper($conf['fontSize'], $conf['angle'], $conf['fontFile'], $this->recodeString($conf['text']), $conf['splitRendering.']);
					if ($conf['angle'] < 0) {
						$pixelWidth = abs($bounds[4] - $bounds[0]);
					} elseif ($conf['angle'] > 0) {
						$pixelWidth = abs($bounds[2] - $bounds[6]);
					} else {
						$pixelWidth = abs($bounds[4] - $bounds[6]);
					}
					// Size is fine, exit:
					if ($pixelWidth <= $maxWidth) {
						break;
					} else {
						$conf['fontSize']--;
					}
				} while ($conf['fontSize'] > 1);
			}
		}
		return $conf['fontSize'];
	}

	/**
	 * Wrapper for ImageTTFBBox
	 *
	 * @param integer $fontSize (See argument for PHP function ImageTTFBBox())
	 * @param integer $angle (See argument for PHP function ImageTTFBBox())
	 * @param string $fontFile (See argument for PHP function ImageTTFBBox())
	 * @param string $string (See argument for PHP function ImageTTFBBox())
	 * @param array $splitRendering Split-rendering configuration
	 * @param integer $sF Scale factor
	 * @return array Information array.
	 * @todo Define visibility
	 */
	public function ImageTTFBBoxWrapper($fontSize, $angle, $fontFile, $string, $splitRendering, $sF = 1) {
		// Initialize:
		$offsetInfo = array();
		$stringParts = $this->splitString($string, $splitRendering, $fontSize, $fontFile);
		// Traverse string parts:
		foreach ($stringParts as $strCfg) {
			$fontFile = self::prependAbsolutePath($strCfg['fontFile']);
			if (is_readable($fontFile)) {
				/**
				 * Calculate Bounding Box for part.
				 * Due to a PHP bug, we must retry if $calc[2] is negative.
				 *
				 * @see https://bugs.php.net/bug.php?id=51315
				 * @see https://bugs.php.net/bug.php?id=22513
				 */
				$try = 0;
				do {
					$calc = ImageTTFBBox(\TYPO3\CMS\Core\Utility\GeneralUtility::freetypeDpiComp($sF * $strCfg['fontSize']), $angle, $fontFile, $strCfg['str']);
				} while ($calc[2] < 0 && $try++ < 10);
				// Calculate offsets:
				if (!count($offsetInfo)) {
					// First run, just copy over.
					$offsetInfo = $calc;
				} else {
					$offsetInfo[2] += $calc[2] - $calc[0] + intval($splitRendering['compX']) + intval($strCfg['xSpaceBefore']) + intval($strCfg['xSpaceAfter']);
					$offsetInfo[3] += $calc[3] - $calc[1] - intval($splitRendering['compY']) - intval($strCfg['ySpaceBefore']) - intval($strCfg['ySpaceAfter']);
					$offsetInfo[4] += $calc[4] - $calc[6] + intval($splitRendering['compX']) + intval($strCfg['xSpaceBefore']) + intval($strCfg['xSpaceAfter']);
					$offsetInfo[5] += $calc[5] - $calc[7] - intval($splitRendering['compY']) - intval($strCfg['ySpaceBefore']) - intval($strCfg['ySpaceAfter']);
				}
			} else {
				debug('cannot read file: ' . $fontFile, 'TYPO3\\CMS\\Core\\Imaging\\GraphicalFunctions::ImageTTFBBoxWrapper()');
			}
		}
		return $offsetInfo;
	}

	/**
	 * Wrapper for ImageTTFText
	 *
	 * @param pointer $im (See argument for PHP function imageTTFtext())
	 * @param integer $fontSize (See argument for PHP function imageTTFtext())
	 * @param integer $angle (See argument for PHP function imageTTFtext())
	 * @param integer $x (See argument for PHP function imageTTFtext())
	 * @param integer $y (See argument for PHP function imageTTFtext())
	 * @param integer $color (See argument for PHP function imageTTFtext())
	 * @param string $fontFile (See argument for PHP function imageTTFtext())
	 * @param string $string (See argument for PHP function imageTTFtext()). UTF-8 string, possibly with entities in.
	 * @param array $splitRendering Split-rendering configuration
	 * @param integer $sF Scale factor
	 * @return void
	 * @todo Define visibility
	 */
	public function ImageTTFTextWrapper($im, $fontSize, $angle, $x, $y, $color, $fontFile, $string, $splitRendering, $sF = 1) {
		// Initialize:
		$stringParts = $this->splitString($string, $splitRendering, $fontSize, $fontFile);
		$x = ceil($sF * $x);
		$y = ceil($sF * $y);
		// Traverse string parts:
		foreach ($stringParts as $i => $strCfg) {
			// Initialize:
			$colorIndex = $color;
			// Set custom color if any (only when niceText is off):
			if ($strCfg['color'] && $sF == 1) {
				$cols = $this->convertColor($strCfg['color']);
				$colorIndex = ImageColorAllocate($im, $cols[0], $cols[1], $cols[2]);
				$colorIndex = $color >= 0 ? $colorIndex : -$colorIndex;
			}
			// Setting xSpaceBefore
			if ($i) {
				$x += intval($strCfg['xSpaceBefore']);
				$y -= intval($strCfg['ySpaceBefore']);
			}
			$fontFile = self::prependAbsolutePath($strCfg['fontFile']);
			if (is_readable($fontFile)) {
				// Render part:
				ImageTTFText($im, \TYPO3\CMS\Core\Utility\GeneralUtility::freetypeDpiComp($sF * $strCfg['fontSize']), $angle, $x, $y, $colorIndex, $fontFile, $strCfg['str']);
				// Calculate offset to apply:
				$wordInf = ImageTTFBBox(\TYPO3\CMS\Core\Utility\GeneralUtility::freetypeDpiComp($sF * $strCfg['fontSize']), $angle, self::prependAbsolutePath($strCfg['fontFile']), $strCfg['str']);
				$x += $wordInf[2] - $wordInf[0] + intval($splitRendering['compX']) + intval($strCfg['xSpaceAfter']);
				$y += $wordInf[5] - $wordInf[7] - intval($splitRendering['compY']) - intval($strCfg['ySpaceAfter']);
			} else {
				debug('cannot read file: ' . $fontFile, 'TYPO3\\CMS\\Core\\Imaging\\GraphicalFunctions::ImageTTFTextWrapper()');
			}
		}
	}

	/**
	 * Splitting a string for ImageTTFBBox up into an array where each part has its own configuration options.
	 *
	 * @param string $string UTF-8 string
	 * @param array $splitRendering Split-rendering configuration from GIFBUILDER TEXT object.
	 * @param integer $fontSize Current fontsize
	 * @param string $fontFile Current font file
	 * @return array Array with input string splitted according to configuration
	 * @todo Define visibility
	 */
	public function splitString($string, $splitRendering, $fontSize, $fontFile) {
		// Initialize by setting the whole string and default configuration as the first entry.
		$result = array();
		$result[] = array(
			'str' => $string,
			'fontSize' => $fontSize,
			'fontFile' => $fontFile
		);
		// Traverse the split-rendering configuration:
		// Splitting will create more entries in $result with individual configurations.
		if (is_array($splitRendering)) {
			$sKeyArray = \TYPO3\CMS\Core\TypoScript\TemplateService::sortedKeyList($splitRendering);
			// Traverse configured options:
			foreach ($sKeyArray as $key) {
				$cfg = $splitRendering[$key . '.'];
				// Process each type of split rendering keyword:
				switch ((string) $splitRendering[$key]) {
				case 'highlightWord':
					if (strlen($cfg['value'])) {
						$newResult = array();
						// Traverse the current parts of the result array:
						foreach ($result as $part) {
							// Explode the string value by the word value to highlight:
							$explodedParts = explode($cfg['value'], $part['str']);
							foreach ($explodedParts as $c => $expValue) {
								if (strlen($expValue)) {
									$newResult[] = array_merge($part, array('str' => $expValue));
								}
								if ($c + 1 < count($explodedParts)) {
									$newResult[] = array(
										'str' => $cfg['value'],
										'fontSize' => $cfg['fontSize'] ? $cfg['fontSize'] : $part['fontSize'],
										'fontFile' => $cfg['fontFile'] ? $cfg['fontFile'] : $part['fontFile'],
										'color' => $cfg['color'],
										'xSpaceBefore' => $cfg['xSpaceBefore'],
										'xSpaceAfter' => $cfg['xSpaceAfter'],
										'ySpaceBefore' => $cfg['ySpaceBefore'],
										'ySpaceAfter' => $cfg['ySpaceAfter']
									);
								}
							}
						}
						// Set the new result as result array:
						if (count($newResult)) {
							$result = $newResult;
						}
					}
					break;
				case 'charRange':
					if (strlen($cfg['value'])) {
						// Initialize range:
						$ranges = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $cfg['value'], 1);
						foreach ($ranges as $i => $rangeDef) {
							$ranges[$i] = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode('-', $ranges[$i]);
							if (!isset($ranges[$i][1])) {
								$ranges[$i][1] = $ranges[$i][0];
							}
						}
						$newResult = array();
						// Traverse the current parts of the result array:
						foreach ($result as $part) {
							// Initialize:
							$currentState = -1;
							$bankAccum = '';
							// Explode the string value by the word value to highlight:
							$utf8Chars = $this->singleChars($part['str']);
							foreach ($utf8Chars as $utfChar) {
								// Find number and evaluate position:
								$uNumber = $this->csConvObj->utf8CharToUnumber($utfChar);
								$inRange = 0;
								foreach ($ranges as $rangeDef) {
									if ($uNumber >= $rangeDef[0] && (!$rangeDef[1] || $uNumber <= $rangeDef[1])) {
										$inRange = 1;
										break;
									}
								}
								if ($currentState == -1) {
									$currentState = $inRange;
								}
								// Initialize first char
								// Switch bank:
								if ($inRange != $currentState && !\TYPO3\CMS\Core\Utility\GeneralUtility::inList('32,10,13,9', $uNumber)) {
									// Set result:
									if (strlen($bankAccum)) {
										$newResult[] = array(
											'str' => $bankAccum,
											'fontSize' => $currentState && $cfg['fontSize'] ? $cfg['fontSize'] : $part['fontSize'],
											'fontFile' => $currentState && $cfg['fontFile'] ? $cfg['fontFile'] : $part['fontFile'],
											'color' => $currentState ? $cfg['color'] : '',
											'xSpaceBefore' => $currentState ? $cfg['xSpaceBefore'] : '',
											'xSpaceAfter' => $currentState ? $cfg['xSpaceAfter'] : '',
											'ySpaceBefore' => $currentState ? $cfg['ySpaceBefore'] : '',
											'ySpaceAfter' => $currentState ? $cfg['ySpaceAfter'] : ''
										);
									}
									// Initialize new settings:
									$currentState = $inRange;
									$bankAccum = '';
								}
								// Add char to bank:
								$bankAccum .= $utfChar;
							}
							// Set result for FINAL part:
							if (strlen($bankAccum)) {
								$newResult[] = array(
									'str' => $bankAccum,
									'fontSize' => $currentState && $cfg['fontSize'] ? $cfg['fontSize'] : $part['fontSize'],
									'fontFile' => $currentState && $cfg['fontFile'] ? $cfg['fontFile'] : $part['fontFile'],
									'color' => $currentState ? $cfg['color'] : '',
									'xSpaceBefore' => $currentState ? $cfg['xSpaceBefore'] : '',
									'xSpaceAfter' => $currentState ? $cfg['xSpaceAfter'] : '',
									'ySpaceBefore' => $currentState ? $cfg['ySpaceBefore'] : '',
									'ySpaceAfter' => $currentState ? $cfg['ySpaceAfter'] : ''
								);
							}
						}
						// Set the new result as result array:
						if (count($newResult)) {
							$result = $newResult;
						}
					}
					break;
				}
			}
		}
		return $result;
	}

	/**
	 * Calculates the spacing and wordSpacing values
	 *
	 * @param array $conf TypoScript array for the TEXT GIFBUILDER object
	 * @param integer $scaleFactor TypoScript value from eg $conf['niceText.']['scaleFactor']
	 * @return array Array with two keys [0]/[1] being array($spacing,$wordSpacing)
	 * @access private
	 * @see calcBBox()
	 * @todo Define visibility
	 */
	public function calcWordSpacing($conf, $scaleFactor = 1) {
		$spacing = intval($conf['spacing']);
		$wordSpacing = intval($conf['wordSpacing']);
		$wordSpacing = $wordSpacing ? $wordSpacing : $spacing * 2;
		$spacing *= $scaleFactor;
		$wordSpacing *= $scaleFactor;
		return array($spacing, $wordSpacing);
	}

	/**
	 * Calculates and returns the niceText.scaleFactor
	 *
	 * @param array $conf TypoScript array for the TEXT GIFBUILDER object
	 * @return integer TypoScript value from eg $conf['niceText.']['scaleFactor']
	 * @access private
	 * @todo Define visibility
	 */
	public function getTextScalFactor($conf) {
		if (!$conf['niceText']) {
			$sF = 1;
		} else {
			// NICETEXT::
			$sF = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($conf['niceText.']['scaleFactor'], 2, 5);
		}
		return $sF;
	}

	/**
	 * Renders a regular text and takes care of a possible line break automatically.
	 *
	 * @param pointer $im (See argument for PHP function imageTTFtext())
	 * @param integer $fontSize (See argument for PHP function imageTTFtext())
	 * @param integer $angle (See argument for PHP function imageTTFtext())
	 * @param integer $x (See argument for PHP function imageTTFtext())
	 * @param integer $y (See argument for PHP function imageTTFtext())
	 * @param integer $color (See argument for PHP function imageTTFtext())
	 * @param string $fontFile (See argument for PHP function imageTTFtext())
	 * @param string $string (See argument for PHP function imageTTFtext()). UTF-8 string, possibly with entities in.
	 * @param array $splitRendering Split-rendering configuration
	 * @param array $conf The configuration
	 * @param integer $sF Scale factor
	 * @return void
	 */
	protected function renderTTFText(&$im, $fontSize, $angle, $x, $y, $color, $fontFile, $string, $splitRendering, $conf, $sF = 1) {
		if (isset($conf['breakWidth']) && $conf['breakWidth'] && $this->getRenderedTextWidth($string, $conf) > $conf['breakWidth']) {
			$phrase = '';
			$currentWidth = 0;
			$breakWidth = $conf['breakWidth'];
			$breakSpace = $this->getBreakSpace($conf);
			$wordPairs = $this->getWordPairsForLineBreak($string);
			// Iterate through all word pairs:
			foreach ($wordPairs as $index => $wordPair) {
				$wordWidth = $this->getRenderedTextWidth($wordPair, $conf);
				if ($index == 0 || $currentWidth + $wordWidth <= $breakWidth) {
					$currentWidth += $wordWidth;
					$phrase .= $wordPair;
				} else {
					// Render the current phrase that is below breakWidth:
					$this->ImageTTFTextWrapper($im, $fontSize, $angle, $x, $y, $color, $fontFile, $phrase, $splitRendering, $sF);
					// Calculate the news height offset:
					$y += $breakSpace;
					// Restart the phrase:
					$currentWidth = $wordWidth;
					$phrase = $wordPair;
				}
			}
			// Render the remaining phrase:
			if ($currentWidth) {
				$this->ImageTTFTextWrapper($im, $fontSize, $angle, $x, $y, $color, $fontFile, $phrase, $splitRendering, $sF);
			}
		} else {
			$this->ImageTTFTextWrapper($im, $fontSize, $angle, $x, $y, $color, $fontFile, $string, $splitRendering, $sF);
		}
	}

	/**
	 * Gets the word pairs used for automatic line breaks.
	 *
	 * @param string $string
	 * @return array
	 */
	protected function getWordPairsForLineBreak($string) {
		$wordPairs = array();
		$wordsArray = preg_split('#([- .,!:]+)#', $string, -1, PREG_SPLIT_DELIM_CAPTURE);
		$wordsCount = count($wordsArray);
		for ($index = 0; $index < $wordsCount; $index += 2) {
			$wordPairs[] = $wordsArray[$index] . $wordsArray[($index + 1)];
		}
		return $wordPairs;
	}

	/**
	 * Gets the rendered text width.
	 *
	 * @param string $text
	 * @param array $conf
	 * @param integer
	 */
	protected function getRenderedTextWidth($text, $conf) {
		$bounds = $this->ImageTTFBBoxWrapper($conf['fontSize'], $conf['angle'], $conf['fontFile'], $this->recodeString($text), $conf['splitRendering.']);
		if ($conf['angle'] < 0) {
			$pixelWidth = abs($bounds[4] - $bounds[0]);
		} elseif ($conf['angle'] > 0) {
			$pixelWidth = abs($bounds[2] - $bounds[6]);
		} else {
			$pixelWidth = abs($bounds[4] - $bounds[6]);
		}
		return $pixelWidth;
	}

	/**
	 * Gets the break space for each new line.
	 *
	 * @param array $conf TypoScript configuration for the currently rendered object
	 * @param array $boundingBox The bounding box the the currently rendered object
	 * @return integer The break space
	 */
	protected function getBreakSpace($conf, array $boundingBox = NULL) {
		if (!isset($boundingBox)) {
			$boundingBox = $this->calcBBox($conf);
			$boundingBox = $boundingBox[2];
		}
		if (isset($conf['breakSpace']) && $conf['breakSpace']) {
			$breakSpace = $boundingBox['lineHeight'] * $conf['breakSpace'];
		} else {
			$breakSpace = $boundingBox['lineHeight'];
		}
		return $breakSpace;
	}

	/*********************************************
	 *
	 * Other GIFBUILDER objects related to TEXT
	 *
	 *********************************************/
	/**
	 * Implements the "OUTLINE" GIFBUILDER object / property for the TEXT object
	 *
	 * @param pointer $im GDlib image pointer
	 * @param array $conf TypoScript array with configuration for the GIFBUILDER object.
	 * @param array $workArea The current working area coordinates.
	 * @param array $txtConf TypoScript array with configuration for the associated TEXT GIFBUILDER object.
	 * @return void
	 * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder::make(), makeText()
	 * @todo Define visibility
	 */
	public function makeOutline(&$im, $conf, $workArea, $txtConf) {
		$thickness = intval($conf['thickness']);
		if ($thickness) {
			$txtConf['fontColor'] = $conf['color'];
			$outLineDist = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($thickness, 1, 2);
			for ($b = 1; $b <= $outLineDist; $b++) {
				if ($b == 1) {
					$it = 8;
				} else {
					$it = 16;
				}
				$outL = $this->circleOffset($b, $it);
				for ($a = 0; $a < $it; $a++) {
					$this->makeText($im, $txtConf, $this->applyOffset($workArea, $outL[$a]));
				}
			}
		}
	}

	/**
	 * Creates some offset values in an array used to simulate a circularly applied outline around TEXT
	 *
	 * access private
	 *
	 * @param integer $distance Distance
	 * @param integer $iterations Iterations.
	 * @return array
	 * @see makeOutline()
	 * @todo Define visibility
	 */
	public function circleOffset($distance, $iterations) {
		$res = array();
		if ($distance && $iterations) {
			for ($a = 0; $a < $iterations; $a++) {
				$yOff = round(sin((2 * pi() / $iterations * ($a + 1))) * 100 * $distance);
				if ($yOff) {
					$yOff = intval(ceil(abs(($yOff / 100))) * ($yOff / abs($yOff)));
				}
				$xOff = round(cos((2 * pi() / $iterations * ($a + 1))) * 100 * $distance);
				if ($xOff) {
					$xOff = intval(ceil(abs(($xOff / 100))) * ($xOff / abs($xOff)));
				}
				$res[$a] = array($xOff, $yOff);
			}
		}
		return $res;
	}

	/**
	 * Implements the "EMBOSS" GIFBUILDER object / property for the TEXT object
	 *
	 * @param pointer $im GDlib image pointer
	 * @param array $conf TypoScript array with configuration for the GIFBUILDER object.
	 * @param array $workArea The current working area coordinates.
	 * @param array $txtConf TypoScript array with configuration for the associated TEXT GIFBUILDER object.
	 * @return void
	 * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder::make(), makeShadow()
	 * @todo Define visibility
	 */
	public function makeEmboss(&$im, $conf, $workArea, $txtConf) {
		$conf['color'] = $conf['highColor'];
		$this->makeShadow($im, $conf, $workArea, $txtConf);
		$newOffset = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $conf['offset']);
		$newOffset[0] *= -1;
		$newOffset[1] *= -1;
		$conf['offset'] = implode(',', $newOffset);
		$conf['color'] = $conf['lowColor'];
		$this->makeShadow($im, $conf, $workArea, $txtConf);
	}

	/**
	 * Implements the "SHADOW" GIFBUILDER object / property for the TEXT object
	 * The operation involves ImageMagick for combining.
	 *
	 * @param pointer $im GDlib image pointer
	 * @param array $conf TypoScript array with configuration for the GIFBUILDER object.
	 * @param array $workArea The current working area coordinates.
	 * @param array $txtConf TypoScript array with configuration for the associated TEXT GIFBUILDER object.
	 * @retur void
	 * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder::make(), makeText(), makeEmboss()
	 * @todo Define visibility
	 */
	public function makeShadow(&$im, $conf, $workArea, $txtConf) {
		$workArea = $this->applyOffset($workArea, \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $conf['offset']));
		$blurRate = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange(intval($conf['blur']), 0, 99);
		// No effects if ImageMagick ver. 5+
		if (!$blurRate || $this->NO_IM_EFFECTS) {
			$txtConf['fontColor'] = $conf['color'];
			$this->makeText($im, $txtConf, $workArea);
		} else {
			$w = imagesx($im);
			$h = imagesy($im);
			// Area around the blur used for cropping something
			$blurBorder = 3;
			$tmpStr = $this->randomName();
			$fileMenu = $tmpStr . '_menu.' . $this->gifExtension;
			$fileColor = $tmpStr . '_color.' . $this->gifExtension;
			$fileMask = $tmpStr . '_mask.' . $this->gifExtension;
			// BlurColor Image laves
			$blurColImg = imagecreatetruecolor($w, $h);
			$bcols = $this->convertColor($conf['color']);
			$Bcolor = ImageColorAllocate($blurColImg, $bcols[0], $bcols[1], $bcols[2]);
			ImageFilledRectangle($blurColImg, 0, 0, $w, $h, $Bcolor);
			$this->ImageWrite($blurColImg, $fileColor);
			ImageDestroy($blurColImg);
			// The mask is made: BlurTextImage
			$blurTextImg = imagecreatetruecolor($w + $blurBorder * 2, $h + $blurBorder * 2);
			// Black background
			$Bcolor = ImageColorAllocate($blurTextImg, 0, 0, 0);
			ImageFilledRectangle($blurTextImg, 0, 0, $w + $blurBorder * 2, $h + $blurBorder * 2, $Bcolor);
			$txtConf['fontColor'] = 'white';
			$blurBordArr = array($blurBorder, $blurBorder);
			$this->makeText($blurTextImg, $txtConf, $this->applyOffset($workArea, $blurBordArr));
			// Dump to temporary file
			$this->ImageWrite($blurTextImg, $fileMask);
			// Destroy
			ImageDestroy($blurTextImg);
			$command = '';
			$command .= $this->maskNegate;
			if ($this->V5_EFFECTS) {
				$command .= $this->v5_blur($blurRate + 1);
			} else {
				// Blurring of the mask
				// How many blur-commands that is executed. Min = 1;
				$times = ceil($blurRate / 10);
				// Here I boost the blur-rate so that it is 100 already at 25. The rest is done by up to 99 iterations of the blur-command.
				$newBlurRate = $blurRate * 4;
				$newBlurRate = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($newBlurRate, 1, 99);
				// Building blur-command
				for ($a = 0; $a < $times; $a++) {
					$command .= ' -blur ' . $blurRate;
				}
			}
			$this->imageMagickExec($fileMask, $fileMask, $command . ' +matte');
			// The mask is loaded again
			$blurTextImg_tmp = $this->imageCreateFromFile($fileMask);
			// If nothing went wrong we continue with the blurred mask
			if ($blurTextImg_tmp) {
				// Cropping the border from the mask
				$blurTextImg = imagecreatetruecolor($w, $h);
				$this->imagecopyresized($blurTextImg, $blurTextImg_tmp, 0, 0, $blurBorder, $blurBorder, $w, $h, $w, $h);
				// Destroy the temporary mask
				ImageDestroy($blurTextImg_tmp);
				// Adjust the mask
				$intensity = 40;
				if ($conf['intensity']) {
					$intensity = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($conf['intensity'], 0, 100);
				}
				$intensity = ceil(255 - $intensity / 100 * 255);
				$this->inputLevels($blurTextImg, 0, $intensity, $this->maskNegate);
				$opacity = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange(intval($conf['opacity']), 0, 100);
				if ($opacity && $opacity < 100) {
					$high = ceil(255 * $opacity / 100);
					// Reducing levels as the opacity demands
					$this->outputLevels($blurTextImg, 0, $high, $this->maskNegate);
				}
				// Dump the mask again
				$this->ImageWrite($blurTextImg, $fileMask);
				// Destroy the mask
				ImageDestroy($blurTextImg);
				// The pictures are combined
				// The main pictures is saved temporarily
				$this->ImageWrite($im, $fileMenu);
				$this->combineExec($fileMenu, $fileColor, $fileMask, $fileMenu);
				// The main image is loaded again...
				$backIm = $this->imageCreateFromFile($fileMenu);
				// ... and if nothing went wrong we load it onto the old one.
				if ($backIm) {
					if (!$this->saveAlphaLayer) {
						ImageColorTransparent($backIm, -1);
					}
					$im = $backIm;
				}
			}
			// Deleting temporary files;
			if (!$this->dontUnlinkTempFiles) {
				unlink($fileMenu);
				unlink($fileColor);
				unlink($fileMask);
			}
		}
	}

	/****************************
	 *
	 * Other GIFBUILDER objects
	 *
	 ****************************/
	/**
	 * Implements the "BOX" GIFBUILDER object
	 *
	 * @param pointer $im GDlib image pointer
	 * @param array $conf TypoScript array with configuration for the GIFBUILDER object.
	 * @param array $workArea The current working area coordinates.
	 * @return void
	 * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder::make()
	 * @todo Define visibility
	 */
	public function makeBox(&$im, $conf, $workArea) {
		$cords = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $conf['dimensions'] . ',,,');
		$conf['offset'] = $cords[0] . ',' . $cords[1];
		$cords = $this->objPosition($conf, $workArea, array($cords[2], $cords[3]));
		$cols = $this->convertColor($conf['color']);
		$opacity = 0;
		if (isset($conf['opacity'])) {
			// conversion:
			// PHP 0 = opaque, 127 = transparent
			// TYPO3 100 = opaque, 0 = transparent
			$opacity = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange(intval($conf['opacity']), 1, 100, 1);
			$opacity = abs($opacity - 100);
			$opacity = round(127 * $opacity / 100);
		}
		$tmpColor = ImageColorAllocateAlpha($im, $cols[0], $cols[1], $cols[2], $opacity);
		imagefilledrectangle($im, $cords[0], $cords[1], $cords[0] + $cords[2] - 1, $cords[1] + $cords[3] - 1, $tmpColor);
	}

	/**
	 * Implements the "Ellipse" GIFBUILDER object
	 * Example Typoscript:
	 * file  =  GIFBUILDER
	 * file  {
	 * XY  =  200,200
	 * format  =  jpg
	 * quality  =  100
	 * 10  =  ELLIPSE
	 * 10.dimensions  =  100,100,50,50
	 * 10.color  =  red
	 *
	 * $workArea = X,Y
	 * $conf['dimensions'] = offset x, offset y, width of ellipse, height of ellipse
	 *
	 * @param pointer $im GDlib image pointer
	 * @param array $conf TypoScript array with configuration for the GIFBUILDER object.
	 * @param array $workArea The current working area coordinates.
	 * @return void
	 * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder::make()
	 */
	public function makeEllipse(&$im, array $conf, array $workArea) {
		$ellipseConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $conf['dimensions'] . ',,,');
		// Ellipse offset inside workArea (x/y)
		$conf['offset'] = $ellipseConfiguration[0] . ',' . $ellipseConfiguration[1];
		// @see objPosition
		$imageCoordinates = $this->objPosition($conf, $workArea, array($ellipseConfiguration[2], $ellipseConfiguration[3]));
		$color = $this->convertColor($conf['color']);
		$fillingColor = imagecolorallocate($im, $color[0], $color[1], $color[2]);
		imagefilledellipse($im, $imageCoordinates[0], $imageCoordinates[1], $imageCoordinates[2], $imageCoordinates[3], $fillingColor);
	}

	/**
	 * Implements the "EFFECT" GIFBUILDER object
	 * The operation involves ImageMagick for applying effects
	 *
	 * @param pointer $im GDlib image pointer
	 * @param array $conf TypoScript array with configuration for the GIFBUILDER object.
	 * @return void
	 * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder::make(), applyImageMagickToPHPGif()
	 * @todo Define visibility
	 */
	public function makeEffect(&$im, $conf) {
		$commands = $this->IMparams($conf['value']);
		if ($commands) {
			$this->applyImageMagickToPHPGif($im, $commands);
		}
	}

	/**
	 * Creating ImageMagick paramters from TypoScript property
	 *
	 * @param string $setup A string with effect keywords=value pairs separated by "|
	 * @return string ImageMagick prepared parameters.
	 * @access private
	 * @see makeEffect()
	 * @todo Define visibility
	 */
	public function IMparams($setup) {
		if (!trim($setup)) {
			return '';
		}
		$effects = explode('|', $setup);
		$commands = '';
		foreach ($effects as $val) {
			$pairs = explode('=', $val, 2);
			$value = trim($pairs[1]);
			$effect = strtolower(trim($pairs[0]));
			switch ($effect) {
			case 'gamma':
				$commands .= ' -gamma ' . doubleval($value);
				break;
			case 'blur':
				if (!$this->NO_IM_EFFECTS) {
					if ($this->V5_EFFECTS) {
						$commands .= $this->v5_blur($value);
					} else {
						$commands .= ' -blur ' . \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($value, 1, 99);
					}
				}
				break;
			case 'sharpen':
				if (!$this->NO_IM_EFFECTS) {
					if ($this->V5_EFFECTS) {
						$commands .= $this->v5_sharpen($value);
					} else {
						$commands .= ' -sharpen ' . \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($value, 1, 99);
					}
				}
				break;
			case 'rotate':
				$commands .= ' -rotate ' . \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($value, 0, 360);
				break;
			case 'solarize':
				$commands .= ' -solarize ' . \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($value, 0, 99);
				break;
			case 'swirl':
				$commands .= ' -swirl ' . \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($value, 0, 1000);
				break;
			case 'wave':
				$params = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $value);
				$commands .= ' -wave ' . \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($params[0], 0, 99) . 'x' . \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($params[1], 0, 99);
				break;
			case 'charcoal':
				$commands .= ' -charcoal ' . \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($value, 0, 100);
				break;
			case 'gray':
				$commands .= ' -colorspace GRAY';
				break;
			case 'edge':
				$commands .= ' -edge ' . \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($value, 0, 99);
				break;
			case 'emboss':
				$commands .= ' -emboss';
				break;
			case 'flip':
				$commands .= ' -flip';
				break;
			case 'flop':
				$commands .= ' -flop';
				break;
			case 'colors':
				$commands .= ' -colors ' . \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($value, 2, 255);
				break;
			case 'shear':
				$commands .= ' -shear ' . \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($value, -90, 90);
				break;
			case 'invert':
				$commands .= ' -negate';
				break;
			}
		}
		return $commands;
	}

	/**
	 * Implements the "ADJUST" GIFBUILDER object
	 *
	 * @param pointer $im GDlib image pointer
	 * @param array $conf TypoScript array with configuration for the GIFBUILDER object.
	 * @return void
	 * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder::make(), autoLevels(), outputLevels(), inputLevels()
	 * @todo Define visibility
	 */
	public function adjust(&$im, $conf) {
		$setup = $conf['value'];
		if (!trim($setup)) {
			return '';
		}
		$effects = explode('|', $setup);
		foreach ($effects as $val) {
			$pairs = explode('=', $val, 2);
			$value = trim($pairs[1]);
			$effect = strtolower(trim($pairs[0]));
			switch ($effect) {
			case 'inputlevels':
				// low,high
				$params = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $value);
				$this->inputLevels($im, $params[0], $params[1]);
				break;
			case 'outputlevels':
				$params = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $value);
				$this->outputLevels($im, $params[0], $params[1]);
				break;
			case 'autolevels':
				$this->autoLevels($im);
				break;
			}
		}
	}

	/**
	 * Implements the "CROP" GIFBUILDER object
	 *
	 * @param pointer $im GDlib image pointer
	 * @param array $conf TypoScript array with configuration for the GIFBUILDER object.
	 * @return void
	 * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder::make()
	 * @todo Define visibility
	 */
	public function crop(&$im, $conf) {
		// Clears workArea to total image
		$this->setWorkArea('');
		$cords = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $conf['crop'] . ',,,');
		$conf['offset'] = $cords[0] . ',' . $cords[1];
		$cords = $this->objPosition($conf, $this->workArea, array($cords[2], $cords[3]));
		$newIm = imagecreatetruecolor($cords[2], $cords[3]);
		$cols = $this->convertColor($conf['backColor'] ? $conf['backColor'] : $this->setup['backColor']);
		$Bcolor = ImageColorAllocate($newIm, $cols[0], $cols[1], $cols[2]);
		ImageFilledRectangle($newIm, 0, 0, $cords[2], $cords[3], $Bcolor);
		$newConf = array();
		$workArea = array(0, 0, $cords[2], $cords[3]);
		if ($cords[0] < 0) {
			$workArea[0] = abs($cords[0]);
		} else {
			$newConf['offset'] = -$cords[0];
		}
		if ($cords[1] < 0) {
			$workArea[1] = abs($cords[1]);
		} else {
			$newConf['offset'] .= ',' . -$cords[1];
		}
		$this->copyGifOntoGif($newIm, $im, $newConf, $workArea);
		$im = $newIm;
		$this->w = imagesx($im);
		$this->h = imagesy($im);
		// Clears workArea to total image
		$this->setWorkArea('');
	}

	/**
	 * Implements the "SCALE" GIFBUILDER object
	 *
	 * @param pointer $im GDlib image pointer
	 * @param array $conf TypoScript array with configuration for the GIFBUILDER object.
	 * @return void
	 * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder::make()
	 * @todo Define visibility
	 */
	public function scale(&$im, $conf) {
		if ($conf['width'] || $conf['height'] || $conf['params']) {
			$tmpStr = $this->randomName();
			$theFile = $tmpStr . '.' . $this->gifExtension;
			$this->ImageWrite($im, $theFile);
			$theNewFile = $this->imageMagickConvert($theFile, $this->gifExtension, $conf['width'], $conf['height'], $conf['params']);
			$tmpImg = $this->imageCreateFromFile($theNewFile[3]);
			if ($tmpImg) {
				ImageDestroy($im);
				$im = $tmpImg;
				$this->w = imagesx($im);
				$this->h = imagesy($im);
				// Clears workArea to total image
				$this->setWorkArea('');
			}
			if (!$this->dontUnlinkTempFiles) {
				unlink($theFile);
				if ($theNewFile[3] && $theNewFile[3] != $theFile) {
					unlink($theNewFile[3]);
				}
			}
		}
	}

	/**
	 * Implements the "WORKAREA" GIFBUILDER object when setting it
	 * Setting internal working area boundaries (->workArea)
	 *
	 * @param string $workArea Working area dimensions, comma separated
	 * @return void
	 * @access private
	 * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder::make()
	 * @todo Define visibility
	 */
	public function setWorkArea($workArea) {
		$this->workArea = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $workArea);
		$this->workArea = $this->applyOffset($this->workArea, $this->OFFSET);
		if (!$this->workArea[2]) {
			$this->workArea[2] = $this->w;
		}
		if (!$this->workArea[3]) {
			$this->workArea[3] = $this->h;
		}
	}

	/*************************
	 *
	 * Adjustment functions
	 *
	 ************************/
	/**
	 * Apply auto-levels to input image pointer
	 *
	 * @param integer $im GDlib Image Pointer
	 * @return void
	 * @todo Define visibility
	 */
	public function autolevels(&$im) {
		$totalCols = ImageColorsTotal($im);
		$min = 255;
		$max = 0;
		for ($c = 0; $c < $totalCols; $c++) {
			$cols = ImageColorsForIndex($im, $c);
			$grayArr[] = round(($cols['red'] + $cols['green'] + $cols['blue']) / 3);
		}
		$min = min($grayArr);
		$max = max($grayArr);
		$delta = $max - $min;
		if ($delta) {
			for ($c = 0; $c < $totalCols; $c++) {
				$cols = ImageColorsForIndex($im, $c);
				$cols['red'] = floor(($cols['red'] - $min) / $delta * 255);
				$cols['green'] = floor(($cols['green'] - $min) / $delta * 255);
				$cols['blue'] = floor(($cols['blue'] - $min) / $delta * 255);
				ImageColorSet($im, $c, $cols['red'], $cols['green'], $cols['blue']);
			}
		}
	}

	/**
	 * Apply output levels to input image pointer (decreasing contrast)
	 *
	 * @param integer $im GDlib Image Pointer
	 * @param integer $low The "low" value (close to 0)
	 * @param integer $high The "high" value (close to 255)
	 * @param boolean $swap If swap, then low and high are swapped. (Useful for negated masks...)
	 * @return void
	 * @todo Define visibility
	 */
	public function outputLevels(&$im, $low, $high, $swap = '') {
		if ($low < $high) {
			$low = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($low, 0, 255);
			$high = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($high, 0, 255);
			if ($swap) {
				$temp = $low;
				$low = 255 - $high;
				$high = 255 - $temp;
			}
			$delta = $high - $low;
			$totalCols = ImageColorsTotal($im);
			for ($c = 0; $c < $totalCols; $c++) {
				$cols = ImageColorsForIndex($im, $c);
				$cols['red'] = $low + floor($cols['red'] / 255 * $delta);
				$cols['green'] = $low + floor($cols['green'] / 255 * $delta);
				$cols['blue'] = $low + floor($cols['blue'] / 255 * $delta);
				ImageColorSet($im, $c, $cols['red'], $cols['green'], $cols['blue']);
			}
		}
	}

	/**
	 * Apply input levels to input image pointer (increasing contrast)
	 *
	 * @param integer $im GDlib Image Pointer
	 * @param integer $low The "low" value (close to 0)
	 * @param integer $high The "high" value (close to 255)
	 * @param boolean $swap If swap, then low and high are swapped. (Useful for negated masks...)
	 * @return void
	 * @todo Define visibility
	 */
	public function inputLevels(&$im, $low, $high, $swap = '') {
		if ($low < $high) {
			$low = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($low, 0, 255);
			$high = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($high, 0, 255);
			if ($swap) {
				$temp = $low;
				$low = 255 - $high;
				$high = 255 - $temp;
			}
			$delta = $high - $low;
			$totalCols = ImageColorsTotal($im);
			for ($c = 0; $c < $totalCols; $c++) {
				$cols = ImageColorsForIndex($im, $c);
				$cols['red'] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange(($cols['red'] - $low) / $delta * 255, 0, 255);
				$cols['green'] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange(($cols['green'] - $low) / $delta * 255, 0, 255);
				$cols['blue'] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange(($cols['blue'] - $low) / $delta * 255, 0, 255);
				ImageColorSet($im, $c, $cols['red'], $cols['green'], $cols['blue']);
			}
		}
	}

	/**
	 * Reduce colors in image using IM and create a palette based image if possible (<=256 colors)
	 *
	 * @param string $file Image file to reduce
	 * @param integer $cols Number of colors to reduce the image to.
	 * @return string Reduced file
	 * @todo Define visibility
	 */
	public function IMreduceColors($file, $cols) {
		$fI = \TYPO3\CMS\Core\Utility\GeneralUtility::split_fileref($file);
		$ext = strtolower($fI['fileext']);
		$result = $this->randomName() . '.' . $ext;
		if (($reduce = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($cols, 0, $ext == 'gif' ? 256 : $this->truecolorColors, 0)) > 0) {
			$params = ' -colors ' . $reduce;
			if ($reduce <= 256) {
				$params .= ' -type Palette';
			}
			if ($ext == 'png' && $reduce <= 256) {
				$prefix = 'png8:';
			}
			$this->imageMagickExec($file, $prefix . $result, $params);
			if ($result) {
				return $result;
			}
		}
		return '';
	}

	/*********************************
	 *
	 * GIFBUILDER Helper functions
	 *
	 *********************************/
	/**
	 * Checks if the $fontFile is already at an absolute path and if not, prepends the correct path.
	 * Use PATH_site unless we are in the backend.
	 * Call it by \TYPO3\CMS\Core\Imaging\GraphicalFunctions::prependAbsolutePath()
	 *
	 * @param string $fontFile The font file
	 * @return string The font file with absolute path.
	 * @todo Define visibility
	 */
	public function prependAbsolutePath($fontFile) {
		$absPath = defined('PATH_typo3') ? dirname(PATH_thisScript) . '/' : PATH_site;
		$fontFile = \TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($fontFile) ? $fontFile : \TYPO3\CMS\Core\Utility\GeneralUtility::resolveBackPath($absPath . $fontFile);
		return $fontFile;
	}

	/**
	 * Returns the IM command for sharpening with ImageMagick 5 (when $this->V5_EFFECTS is set).
	 * Uses $this->im5fx_sharpenSteps for translation of the factor to an actual command.
	 *
	 * @param integer $factor The sharpening factor, 0-100 (effectively in 10 steps)
	 * @return string The sharpening command, eg. " -sharpen 3x4
	 * @see makeText(), IMparams(), v5_blur()
	 * @todo Define visibility
	 */
	public function v5_sharpen($factor) {
		$factor = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange(ceil($factor / 10), 0, 10);
		$sharpenArr = explode(',', ',' . $this->im5fx_sharpenSteps);
		$sharpenF = trim($sharpenArr[$factor]);
		if ($sharpenF) {
			$cmd = ' -sharpen ' . $sharpenF;
			return $cmd;
		}
	}

	/**
	 * Returns the IM command for blurring with ImageMagick 5 (when $this->V5_EFFECTS is set).
	 * Uses $this->im5fx_blurSteps for translation of the factor to an actual command.
	 *
	 * @param integer $factor The blurring factor, 0-100 (effectively in 10 steps)
	 * @return string The blurring command, eg. " -blur 3x4
	 * @see makeText(), IMparams(), v5_sharpen()
	 * @todo Define visibility
	 */
	public function v5_blur($factor) {
		$factor = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange(ceil($factor / 10), 0, 10);
		$blurArr = explode(',', ',' . $this->im5fx_blurSteps);
		$blurF = trim($blurArr[$factor]);
		if ($blurF) {
			$cmd = ' -blur ' . $blurF;
			return $cmd;
		}
	}

	/**
	 * Returns a random filename prefixed with "temp_" and then 32 char md5 hash (without extension) from $this->tempPath.
	 * Used by functions in this class to create truely temporary files for the on-the-fly processing. These files will most likely be deleted right away.
	 *
	 * @return string
	 * @todo Define visibility
	 */
	public function randomName() {
		$this->createTempSubDir('temp/');
		return $this->tempPath . 'temp/' . md5(uniqid(''));
	}

	/**
	 * Applies offset value to coordinated in $cords.
	 * Basically the value of key 0/1 of $OFFSET is added to keys 0/1 of $cords
	 *
	 * @param array $cords Integer coordinates in key 0/1
	 * @param array $OFFSET Offset values in key 0/1
	 * @return array Modified $cords array
	 * @todo Define visibility
	 */
	public function applyOffset($cords, $OFFSET) {
		$cords[0] = intval($cords[0]) + intval($OFFSET[0]);
		$cords[1] = intval($cords[1]) + intval($OFFSET[1]);
		return $cords;
	}

	/**
	 * Converts a "HTML-color" TypoScript datatype to RGB-values.
	 * Default is 0,0,0
	 *
	 * @param string $string "HTML-color" data type string, eg. 'red', '#ffeedd' or '255,0,255'. You can also add a modifying operator afterwards. There are two options: "255,0,255 : 20" - will add 20 to values, result is "255,20,255". Or "255,0,255 : *1.23" which will multiply all RGB values with 1.23
	 * @return array RGB values in key 0/1/2 of the array
	 * @todo Define visibility
	 */
	public function convertColor($string) {
		$col = array();
		$cParts = explode(':', $string, 2);
		// Finding the RGB definitions of the color:
		$string = $cParts[0];
		if (strstr($string, '#')) {
			$string = preg_replace('/[^A-Fa-f0-9]*/', '', $string);
			$col[] = HexDec(substr($string, 0, 2));
			$col[] = HexDec(substr($string, 2, 2));
			$col[] = HexDec(substr($string, 4, 2));
		} elseif (strstr($string, ',')) {
			$string = preg_replace('/[^,0-9]*/', '', $string);
			$strArr = explode(',', $string);
			$col[] = intval($strArr[0]);
			$col[] = intval($strArr[1]);
			$col[] = intval($strArr[2]);
		} else {
			$string = strtolower(trim($string));
			if ($this->colMap[$string]) {
				$col = $this->colMap[$string];
			} else {
				$col = array(0, 0, 0);
			}
		}
		// ... and possibly recalculating the value
		if (trim($cParts[1])) {
			$cParts[1] = trim($cParts[1]);
			if (substr($cParts[1], 0, 1) == '*') {
				$val = doubleval(substr($cParts[1], 1));
				$col[0] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($col[0] * $val, 0, 255);
				$col[1] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($col[1] * $val, 0, 255);
				$col[2] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($col[2] * $val, 0, 255);
			} else {
				$val = intval($cParts[1]);
				$col[0] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($col[0] + $val, 0, 255);
				$col[1] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($col[1] + $val, 0, 255);
				$col[2] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($col[2] + $val, 0, 255);
			}
		}
		return $col;
	}

	/**
	 * Recode string
	 * Used with text strings for fonts when languages has other character sets.
	 *
	 * @param string The text to recode
	 * @return string The recoded string. Should be UTF-8 output. MAY contain entities (eg. &#123; or &#quot; which should render as real chars).
	 * @todo Define visibility
	 */
	public function recodeString($string) {
		// Recode string to UTF-8 from $this->nativeCharset:
		if ($this->nativeCharset && $this->nativeCharset != 'utf-8') {
			// Convert to UTF-8
			$string = $this->csConvObj->utf8_encode($string, $this->nativeCharset);
		}
		return $string;
	}

	/**
	 * Split a string into an array of individual characters
	 * The function will look at $this->nativeCharset and if that is set, the input string is expected to be UTF-8 encoded, possibly with entities in it. Otherwise the string is supposed to be a single-byte charset which is just splitted by a for-loop.
	 *
	 * @param string $theText The text string to split
	 * @param boolean $returnUnicodeNumber Return Unicode numbers instead of chars.
	 * @return array Numerical array with a char as each value.
	 * @todo Define visibility
	 */
	public function singleChars($theText, $returnUnicodeNumber = FALSE) {
		if ($this->nativeCharset) {
			// Get an array of separated UTF-8 chars
			return $this->csConvObj->utf8_to_numberarray($theText, 1, $returnUnicodeNumber ? 0 : 1);
		} else {
			$output = array();
			$c = strlen($theText);
			for ($a = 0; $a < $c; $a++) {
				$output[] = substr($theText, $a, 1);
			}
			return $output;
		}
	}

	/**
	 * Create an array with object position/boundaries based on input TypoScript configuration (such as the "align" property is used), the work area definition and $BB array
	 *
	 * @param array $conf TypoScript configuration for a GIFBUILDER object
	 * @param array makeBox Workarea definition
	 * @param array $BB BB (Bounding box) array. Not just used for TEXT objects but also for others
	 * @return array [0]=x, [1]=y, [2]=w, [3]=h
	 * @access private
	 * @see copyGifOntoGif(), makeBox(), crop()
	 * @todo Define visibility
	 */
	public function objPosition($conf, $workArea, $BB) {
		// offset, align, valign, workarea
		$result = array();
		$result[2] = $BB[0];
		$result[3] = $BB[1];
		$w = $workArea[2];
		$h = $workArea[3];
		$align = explode(',', $conf['align']);
		$align[0] = strtolower(substr(trim($align[0]), 0, 1));
		$align[1] = strtolower(substr(trim($align[1]), 0, 1));
		switch ($align[0]) {
		case 'r':
			$result[0] = $w - $result[2];
			break;
		case 'c':
			$result[0] = round(($w - $result[2]) / 2);
			break;
		default:
			$result[0] = 0;
			break;
		}
		switch ($align[1]) {
		case 'b':
			// y pos
			$result[1] = $h - $result[3];
			break;
		case 'c':
			$result[1] = round(($h - $result[3]) / 2);
			break;
		default:
			$result[1] = 0;
			break;
		}
		$result = $this->applyOffset($result, \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $conf['offset']));
		$result = $this->applyOffset($result, $workArea);
		return $result;
	}

	/***********************************
	 *
	 * Scaling, Dimensions of images
	 *
	 ***********************************/
	/**
	 * Converts $imagefile to another file in temp-dir of type $newExt (extension).
	 *
	 * @param string $imagefile The image filepath
	 * @param string $newExt New extension, eg. "gif", "png", "jpg", "tif". If $newExt is NOT set, the new imagefile will be of the original format. If newExt = 'WEB' then one of the web-formats is applied.
	 * @param string $w Width. $w / $h is optional. If only one is given the image is scaled proportionally. If an 'm' exists in the $w or $h and if both are present the $w and $h is regarded as the Maximum w/h and the proportions will be kept
	 * @param string $h Height. See $w
	 * @param string $params Additional ImageMagick parameters.
	 * @param string $frame Refers to which frame-number to select in the image. '' or 0 will select the first frame, 1 will select the next and so on...
	 * @param array $options An array with options passed to getImageScale (see this function).
	 * @param boolean $mustCreate If set, then another image than the input imagefile MUST be returned. Otherwise you can risk that the input image is good enough regarding messures etc and is of course not rendered to a new, temporary file in typo3temp/. But this option will force it to.
	 * @return array [0]/[1] is w/h, [2] is file extension and [3] is the filename.
	 * @see getImageScale(), typo3/show_item.php, fileList_ext::renderImage(), tslib_cObj::getImgResource(), SC_tslib_showpic::show(), maskImageOntoImage(), copyImageOntoImage(), scale()
	 * @todo Define visibility
	 */
	public function imageMagickConvert($imagefile, $newExt = '', $w = '', $h = '', $params = '', $frame = '', $options = array(), $mustCreate = FALSE) {
		if ($this->NO_IMAGE_MAGICK) {
			// Returning file info right away
			return $this->getImageDimensions($imagefile);
		}
		if ($info = $this->getImageDimensions($imagefile)) {
			$newExt = strtolower(trim($newExt));
			// If no extension is given the original extension is used
			if (!$newExt) {
				$newExt = $info[2];
			}
			if ($newExt == 'web') {
				if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList($this->webImageExt, $info[2])) {
					$newExt = $info[2];
				} else {
					$newExt = $this->gif_or_jpg($info[2], $info[0], $info[1]);
					if (!$params) {
						$params = $this->cmds[$newExt];
					}
				}
			}
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList($this->imageFileExt, $newExt)) {
				if (strstr($w . $h, 'm')) {
					$max = 1;
				} else {
					$max = 0;
				}
				$data = $this->getImageScale($info, $w, $h, $options);
				$w = $data['origW'];
				$h = $data['origH'];
				// If no conversion should be performed
				// this flag is TRUE if the width / height does NOT dictate
				// the image to be scaled!! (that is if no width / height is
				// given or if the destination w/h matches the original image
				// dimensions or if the option to not scale the image is set)
				$noScale = !$w && !$h || $data[0] == $info[0] && $data[1] == $info[1] || $options['noScale'];
				if ($noScale && !$data['crs'] && !$params && !$frame && $newExt == $info[2] && !$mustCreate) {
					// Set the new width and height before returning,
					// if the noScale option is set
					if (!empty($options['noScale'])) {
						$info[0] = $data[0];
						$info[1] = $data[1];
					}
					$info[3] = $imagefile;
					return $info;
				}
				$info[0] = $data[0];
				$info[1] = $data[1];
				$frame = $this->noFramePrepended ? '' : intval($frame);
				if (!$params) {
					$params = $this->cmds[$newExt];
				}
				// Cropscaling:
				if ($data['crs']) {
					if (!$data['origW']) {
						$data['origW'] = $data[0];
					}
					if (!$data['origH']) {
						$data['origH'] = $data[1];
					}
					$offsetX = intval(($data[0] - $data['origW']) * ($data['cropH'] + 100) / 200);
					$offsetY = intval(($data[1] - $data['origH']) * ($data['cropV'] + 100) / 200);
					$params .= ' -crop ' . $data['origW'] . 'x' . $data['origH'] . '+' . $offsetX . '+' . $offsetY . ' ';
				}
				$command = $this->scalecmd . ' ' . $info[0] . 'x' . $info[1] . '! ' . $params . ' ';
				$cropscale = $data['crs'] ? 'crs-V' . $data['cropV'] . 'H' . $data['cropH'] : '';
				if ($this->alternativeOutputKey) {
					$theOutputName = \TYPO3\CMS\Core\Utility\GeneralUtility::shortMD5($command . $cropscale . basename($imagefile) . $this->alternativeOutputKey . '[' . $frame . ']');
				} else {
					$theOutputName = \TYPO3\CMS\Core\Utility\GeneralUtility::shortMD5($command . $cropscale . $imagefile . filemtime($imagefile) . '[' . $frame . ']');
				}
				if ($this->imageMagickConvert_forceFileNameBody) {
					$theOutputName = $this->imageMagickConvert_forceFileNameBody;
					$this->imageMagickConvert_forceFileNameBody = '';
				}
				// Making the temporary filename:
				$this->createTempSubDir('pics/');
				$output = $this->absPrefix . $this->tempPath . 'pics/' . $this->filenamePrefix . $theOutputName . '.' . $newExt;
				// Register temporary filename:
				$GLOBALS['TEMP_IMAGES_ON_PAGE'][] = $output;
				if ($this->dontCheckForExistingTempFile || !$this->file_exists_typo3temp_file($output, $imagefile)) {
					$this->imageMagickExec($imagefile, $output, $command, $frame);
				}
				if (file_exists($output)) {
					$info[3] = $output;
					$info[2] = $newExt;
					// params could realisticly change some imagedata!
					if ($params) {
						$info = $this->getImageDimensions($info[3]);
					}
					if ($info[2] == $this->gifExtension && !$this->dontCompress) {
						// Compress with IM (lzw) or GD (rle)  (Workaround for the absence of lzw-compression in GD)
						\TYPO3\CMS\Core\Utility\GeneralUtility::gif_compress($info[3], '');
					}
					return $info;
				}
			}
		}
	}

	/**
	 * Gets the input image dimensions.
	 *
	 * @param string $imageFile The image filepath
	 * @return array Returns an array where [0]/[1] is w/h, [2] is extension and [3] is the filename.
	 * @see imageMagickConvert(), tslib_cObj::getImgResource()
	 * @todo Define visibility
	 */
	public function getImageDimensions($imageFile) {
		preg_match('/([^\\.]*)$/', $imageFile, $reg);
		if (file_exists($imageFile) && \TYPO3\CMS\Core\Utility\GeneralUtility::inList($this->imageFileExt, strtolower($reg[0]))) {
			if ($returnArr = $this->getCachedImageDimensions($imageFile)) {
				return $returnArr;
			} else {
				if ($temp = @getImageSize($imageFile)) {
					$returnArr = array($temp[0], $temp[1], strtolower($reg[0]), $imageFile);
				} else {
					$returnArr = $this->imageMagickIdentify($imageFile);
				}
				if ($returnArr) {
					$this->cacheImageDimensions($returnArr);
					return $returnArr;
				}
			}
		}
		return FALSE;
	}

	/**
	 * Cache the result of the getImageDimensions function into the database. Does not check if the
	 * file exists!
	 *
	 * @param array $identifyResult Result of the getImageDimensions function
	 * @return boolean TRUE if operation was successful
	 * @todo Define visibility
	 */
	public function cacheImageDimensions($identifyResult) {
		// Create md5 hash of filemtime and filesize
		$md5Hash = md5(filemtime($identifyResult[3]) . filesize($identifyResult[3]));
		$result = FALSE;
		if ($md5Hash) {
			$fieldArray = array(
				'md5hash' => $md5Hash,
				'md5filename' => md5($identifyResult[3]),
				'tstamp' => $GLOBALS['EXEC_TIME'],
				'filename' => $identifyResult[3],
				'imagewidth' => $identifyResult[0],
				'imageheight' => $identifyResult[1]
			);
			$GLOBALS['TYPO3_DB']->exec_INSERTquery('cache_imagesizes', $fieldArray);
			if (!($err = $GLOBALS['TYPO3_DB']->sql_error())) {
				$result = TRUE;
			}
		}
		return $result;
	}

	/**
	 * Fetch the cached imageDimensions from the MySQL database. Does not check if the image file exists!
	 *
	 * @param string $imageFile The image filepath
	 * @return array Returns an array where [0]/[1] is w/h, [2] is extension and [3] is the filename.
	 * @todo Define visibility
	 */
	public function getCachedImageDimensions($imageFile) {
		// Create md5 hash of filemtime and filesize
		$md5Hash = md5(filemtime($imageFile) . filesize($imageFile));
		$cachedImageDimensions = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('md5hash, md5filename, imagewidth, imageheight', 'cache_imagesizes', 'md5filename=' . $GLOBALS['TYPO3_DB']->fullQuoteStr(md5($imageFile), 'cache_imagesizes'));
		$result = FALSE;
		if (is_array($cachedImageDimensions)) {
			if ($cachedImageDimensions['md5hash'] != $md5Hash) {
				// File has changed, delete the row
				$GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_imagesizes', 'md5filename=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($cachedImageDimensions['md5filename'], 'cache_imagesizes'));
			} else {
				preg_match('/([^\\.]*)$/', $imageFile, $imageExtension);
				$result = array(
					(int) $cachedImageDimensions['imagewidth'],
					(int) $cachedImageDimensions['imageheight'],
					strtolower($imageExtension[0]),
					$imageFile
				);
			}
		}
		return $result;
	}

	/**
	 * Get numbers for scaling the image based on input
	 *
	 * @param array $info Current image information: Width, Height etc.
	 * @param integer $w "required" width
	 * @param integer $h "required" height
	 * @param array $options Options: Keys are like "maxW", "maxH", "minW", "minH
	 * @return array
	 * @access private
	 * @see imageMagickConvert()
	 * @todo Define visibility
	 */
	public function getImageScale($info, $w, $h, $options) {
		if (strstr($w . $h, 'm')) {
			$max = 1;
		} else {
			$max = 0;
		}
		if (strstr($w . $h, 'c')) {
			$out['cropH'] = intval(substr(strstr($w, 'c'), 1));
			$out['cropV'] = intval(substr(strstr($h, 'c'), 1));
			$crs = TRUE;
		} else {
			$crs = FALSE;
		}
		$out['crs'] = $crs;
		$w = intval($w);
		$h = intval($h);
		// If there are max-values...
		if (!empty($options['maxW'])) {
			// If width is given...
			if ($w) {
				if ($w > $options['maxW']) {
					$w = $options['maxW'];
					// Height should follow
					$max = 1;
				}
			} else {
				if ($info[0] > $options['maxW']) {
					$w = $options['maxW'];
					// Height should follow
					$max = 1;
				}
			}
		}
		if (!empty($options['maxH'])) {
			// If height is given...
			if ($h) {
				if ($h > $options['maxH']) {
					$h = $options['maxH'];
					// Height should follow
					$max = 1;
				}
			} else {
				// Changed [0] to [1] 290801
				if ($info[1] > $options['maxH']) {
					$h = $options['maxH'];
					// Height should follow
					$max = 1;
				}
			}
		}
		$out['origW'] = $w;
		$out['origH'] = $h;
		$out['max'] = $max;
		if (!$this->mayScaleUp) {
			if ($w > $info[0]) {
				$w = $info[0];
			}
			if ($h > $info[1]) {
				$h = $info[1];
			}
		}
		// If scaling should be performed
		if ($w || $h) {
			if ($w && !$h) {
				$info[1] = ceil($info[1] * ($w / $info[0]));
				$info[0] = $w;
			}
			if (!$w && $h) {
				$info[0] = ceil($info[0] * ($h / $info[1]));
				$info[1] = $h;
			}
			if ($w && $h) {
				if ($max) {
					$ratio = $info[0] / $info[1];
					if ($h * $ratio > $w) {
						$h = round($w / $ratio);
					} else {
						$w = round($h * $ratio);
					}
				}
				if ($crs) {
					$ratio = $info[0] / $info[1];
					if ($h * $ratio < $w) {
						$h = round($w / $ratio);
					} else {
						$w = round($h * $ratio);
					}
				}
				$info[0] = $w;
				$info[1] = $h;
			}
		}
		$out[0] = $info[0];
		$out[1] = $info[1];
		// Set minimum-measures!
		if (isset($options['minW']) && $out[0] < $options['minW']) {
			if (($max || $crs) && $out[0]) {
				$out[1] = round($out[1] * $options['minW'] / $out[0]);
			}
			$out[0] = $options['minW'];
		}
		if (isset($options['minH']) && $out[1] < $options['minH']) {
			if (($max || $crs) && $out[1]) {
				$out[0] = round($out[0] * $options['minH'] / $out[1]);
			}
			$out[1] = $options['minH'];
		}
		return $out;
	}

	/**
	 * Used to check if a certain process of scaling an image is already being carried out (can be logged in the SQL database)
	 *
	 * @param string $output Output imagefile
	 * @param string $orig Original basis file
	 * @return boolean Returns TRUE if the file is already being made; thus "TRUE" means "Don't render the image again
	 * @access private
	 * @todo Define visibility
	 */
	public function file_exists_typo3temp_file($output, $orig = '') {
		if ($this->enable_typo3temp_db_tracking) {
			// If file exists, then we return immediately
			if (file_exists($output)) {
				return 1;
			} else {
				// If not, we look up in the cache_typo3temp_log table to see if there is a image being rendered right now.
				$md5Hash = md5($output);
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('md5hash', 'cache_typo3temp_log', 'md5hash=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($md5Hash, 'cache_typo3temp_log') . ' AND tstamp>' . ($GLOBALS['EXEC_TIME'] - 30));
				// If there was a record, the image is being generated by another process (we assume)
				if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					$GLOBALS['TYPO3_DB']->sql_free_result($res);
					if (is_object($GLOBALS['TSFE'])) {
						$GLOBALS['TSFE']->set_no_cache('Another process renders this file now: ' . $output);
					}
					// ...so we set no_cache, because we dont want this page (which will NOT display an image...!) to be cached! (Only a page with the correct image on...)
					if (is_object($GLOBALS['TT'])) {
						$GLOBALS['TT']->setTSlogMessage('typo3temp_log: Assume this file is being rendered now: ' . $output);
					}
					// Return 'success - 2'
					return 2;
				} else {
					// If the current time is more than 30 seconds since this record was written, we clear the record, write a new and render the image.
					$insertFields = array(
						'md5hash' => $md5Hash,
						'tstamp' => $GLOBALS['EXEC_TIME'],
						'filename' => $output,
						'orig_filename' => $orig
					);
					$GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_typo3temp_log', 'md5hash=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($md5Hash, 'cache_typo3temp_log'));
					$GLOBALS['TYPO3_DB']->exec_INSERTquery('cache_typo3temp_log', $insertFields);
					if (is_object($GLOBALS['TT'])) {
						$GLOBALS['TT']->setTSlogMessage('typo3temp_log: The row did not exist, so a new is written and file is being processed: ' . $output);
					}
					return 0;
				}
			}
		} else {
			return file_exists($output);
		}
	}

	/***********************************
	 *
	 * ImageMagick API functions
	 *
	 ***********************************/
	/**
	 * Returns an array where [0]/[1] is w/h, [2] is extension and [3] is the filename.
	 * Using ImageMagick
	 *
	 * @param string $imagefile The relative (to PATH_site) image filepath
	 * @return array
	 * @todo Define visibility
	 */
	public function imageMagickIdentify($imagefile) {
		if (!$this->NO_IMAGE_MAGICK) {
			$frame = $this->noFramePrepended ? '' : '[0]';
			$cmd = \TYPO3\CMS\Core\Utility\GeneralUtility::imageMagickCommand('identify', $this->wrapFileName($imagefile) . $frame);
			$returnVal = array();
			\TYPO3\CMS\Core\Utility\CommandUtility::exec($cmd, $returnVal);
			$splitstring = $returnVal[0];
			$this->IM_commands[] = array('identify', $cmd, $returnVal[0]);
			if ($splitstring) {
				preg_match('/([^\\.]*)$/', $imagefile, $reg);
				$splitinfo = explode(' ', $splitstring);
				foreach ($splitinfo as $key => $val) {
					$temp = '';
					if ($val) {
						$temp = explode('x', $val);
					}
					if (intval($temp[0]) && intval($temp[1])) {
						$dim = $temp;
						break;
					}
				}
				if ($dim[0] && $dim[1]) {
					return array($dim[0], $dim[1], strtolower($reg[0]), $imagefile);
				}
			}
		}
	}

	/**
	 * Executes a ImageMagick "convert" on two filenames, $input and $output using $params before them.
	 * Can be used for many things, mostly scaling and effects.
	 *
	 * @param string $input The relative (to PATH_site) image filepath, input file (read from)
	 * @param string $output The relative (to PATH_site) image filepath, output filename (written to)
	 * @param string $params ImageMagick parameters
	 * @param integer $frame Optional, refers to which frame-number to select in the image. '' or 0
	 * @return string The result of a call to PHP function "exec()
	 * @todo Define visibility
	 */
	public function imageMagickExec($input, $output, $params, $frame = 0) {
		if (!$this->NO_IMAGE_MAGICK) {
			// Unless noFramePrepended is set in the Install Tool, a frame number is added to
			// select a specific page of the image (by default this will be the first page)
			if (!$this->noFramePrepended) {
				$frame = '[' . intval($frame) . ']';
			} else {
				$frame = '';
			}
			$cmd = \TYPO3\CMS\Core\Utility\GeneralUtility::imageMagickCommand('convert', $params . ' ' . $this->wrapFileName($input) . $frame . ' ' . $this->wrapFileName($output));
			$this->IM_commands[] = array($output, $cmd);
			$ret = \TYPO3\CMS\Core\Utility\CommandUtility::exec($cmd);
			// Change the permissions of the file
			\TYPO3\CMS\Core\Utility\GeneralUtility::fixPermissions($output);
			return $ret;
		}
	}

	/**
	 * Executes a ImageMagick "combine" (or composite in newer times) on four filenames - $input, $overlay and $mask as input files and $output as the output filename (written to)
	 * Can be used for many things, mostly scaling and effects.
	 *
	 * @param string $input The relative (to PATH_site) image filepath, bottom file
	 * @param string $overlay The relative (to PATH_site) image filepath, overlay file (top)
	 * @param string $mask The relative (to PATH_site) image filepath, the mask file (grayscale)
	 * @param string $output The relative (to PATH_site) image filepath, output filename (written to)
	 * @param boolean $handleNegation
	 * @return 	void
	 * @todo Define visibility
	 */
	public function combineExec($input, $overlay, $mask, $output, $handleNegation = FALSE) {
		if (!$this->NO_IMAGE_MAGICK) {
			$params = '-colorspace GRAY +matte';
			if ($handleNegation) {
				if ($this->maskNegate) {
					$params .= ' ' . $this->maskNegate;
				}
			}
			$theMask = $this->randomName() . '.' . $this->gifExtension;
			$this->imageMagickExec($mask, $theMask, $params);
			$cmd = \TYPO3\CMS\Core\Utility\GeneralUtility::imageMagickCommand('combine', '-compose over +matte ' . $this->wrapFileName($input) . ' ' . $this->wrapFileName($overlay) . ' ' . $this->wrapFileName($theMask) . ' ' . $this->wrapFileName($output));
			// +matte = no alpha layer in output
			$this->IM_commands[] = array($output, $cmd);
			$ret = \TYPO3\CMS\Core\Utility\CommandUtility::exec($cmd);
			// Change the permissions of the file
			\TYPO3\CMS\Core\Utility\GeneralUtility::fixPermissions($output);
			if (is_file($theMask)) {
				@unlink($theMask);
			}
			return $ret;
		}
	}

	/**
	 * Escapes a file name so it can safely be used on the command line.
	 *
	 * @param string $inputName filename to safeguard, must not be empty
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

	/***********************************
	 *
	 * Various IO functions
	 *
	 ***********************************/
	/**
	 * Returns TRUE if the input file existed
	 *
	 * @param string $file Input file to check
	 * @return string Returns the filename if the file existed, otherwise empty.
	 * @todo Define visibility
	 */
	public function checkFile($file) {
		if (@is_file($file)) {
			return $file;
		} else {
			return '';
		}
	}

	/**
	 * Creates subdirectory in typo3temp/ if not already found.
	 *
	 * @param string $dirName Name of sub directory
	 * @return boolean Result of \TYPO3\CMS\Core\Utility\GeneralUtility::mkdir(), TRUE if it went well.
	 * @todo Define visibility
	 */
	public function createTempSubDir($dirName) {
		// Checking if the this->tempPath is already prefixed with PATH_site and if not, prefix it with that constant.
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($this->tempPath, PATH_site)) {
			$tmpPath = $this->tempPath;
		} else {
			$tmpPath = PATH_site . $this->tempPath;
		}
		// Making the temporary filename:
		if (!@is_dir(($tmpPath . $dirName))) {
			return \TYPO3\CMS\Core\Utility\GeneralUtility::mkdir($tmpPath . $dirName);
		}
	}

	/**
	 * Applies an ImageMagick parameter to a GDlib image pointer resource by writing the resource to file, performing an IM operation upon it and reading back the result into the ImagePointer.
	 *
	 * @param pointer $im The image pointer (reference)
	 * @param string $command The ImageMagick parameters. Like effects, scaling etc.
	 * @return void
	 * @todo Define visibility
	 */
	public function applyImageMagickToPHPGif(&$im, $command) {
		$tmpStr = $this->randomName();
		$theFile = $tmpStr . '.' . $this->gifExtension;
		$this->ImageWrite($im, $theFile);
		$this->imageMagickExec($theFile, $theFile, $command);
		$tmpImg = $this->imageCreateFromFile($theFile);
		if ($tmpImg) {
			ImageDestroy($im);
			$im = $tmpImg;
			$this->w = imagesx($im);
			$this->h = imagesy($im);
		}
		if (!$this->dontUnlinkTempFiles) {
			unlink($theFile);
		}
	}

	/**
	 * Returns an image extension for an output image based on the number of pixels of the output and the file extension of the original file.
	 * For example: If the number of pixels exceeds $this->pixelLimitGif (normally 10000) then it will be a "jpg" string in return.
	 *
	 * @param string $type The file extension, lowercase.
	 * @param integer $w The width of the output image.
	 * @param integer $h The height of the output image.
	 * @return string The filename, either "jpg" or "gif"/"png" (whatever $this->gifExtension is set to.)
	 * @todo Define visibility
	 */
	public function gif_or_jpg($type, $w, $h) {
		if ($type == 'ai' || $w * $h < $this->pixelLimitGif) {
			return $this->gifExtension;
		} else {
			return 'jpg';
		}
	}

	/**
	 * Writing the internal image pointer, $this->im, to file based on the extension of the input filename
	 * Used in GIFBUILDER
	 * Uses $this->setup['reduceColors'] for gif/png images and $this->setup['quality'] for jpg images to reduce size/quality if needed.
	 *
	 * @param string $file The filename to write to.
	 * @return string Returns input filename
	 * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder::gifBuild()
	 * @todo Define visibility
	 */
	public function output($file) {
		if ($file) {
			$reg = array();
			preg_match('/([^\\.]*)$/', $file, $reg);
			$ext = strtolower($reg[0]);
			switch ($ext) {
			case 'gif':

			case 'png':
				if ($this->ImageWrite($this->im, $file)) {
					// ImageMagick operations
					if ($this->setup['reduceColors'] || !$this->png_truecolor) {
						$reduced = $this->IMreduceColors($file, \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->setup['reduceColors'], 256, $this->truecolorColors, 256));
						if ($reduced) {
							@copy($reduced, $file);
							@unlink($reduced);
						}
					}
					// Compress with IM! (adds extra compression, LZW from ImageMagick)
					// (Workaround for the absence of lzw-compression in GD)
					\TYPO3\CMS\Core\Utility\GeneralUtility::gif_compress($file, 'IM');
				}
				break;
			case 'jpg':

			case 'jpeg':
				// Use the default
				$quality = 0;
				if ($this->setup['quality']) {
					$quality = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->setup['quality'], 10, 100);
				}
				if ($this->ImageWrite($this->im, $file, $quality)) {

				}
				break;
			}
			$GLOBALS['TEMP_IMAGES_ON_PAGE'][] = $file;
		}
		return $file;
	}

	/**
	 * Destroy internal image pointer, $this->im
	 *
	 * @return void
	 * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder::gifBuild()
	 * @todo Define visibility
	 */
	public function destroy() {
		ImageDestroy($this->im);
	}

	/**
	 * Returns Image Tag for input image information array.
	 *
	 * @param array $imgInfo Image information array, key 0/1 is width/height and key 3 is the src value
	 * @return string Image tag for the input image information array.
	 * @todo Define visibility
	 */
	public function imgTag($imgInfo) {
		return '<img src="' . $imgInfo[3] . '" width="' . $imgInfo[0] . '" height="' . $imgInfo[1] . '" border="0" alt="" />';
	}

	/**
	 * Writes the input GDlib image pointer to file
	 *
	 * @param pointer $destImg The GDlib image resource pointer
	 * @param string $theImage The filename to write to
	 * @param integer $quality The image quality (for JPEGs)
	 * @return boolean The output of either imageGif, imagePng or imageJpeg based on the filename to write
	 * @see maskImageOntoImage(), scale(), output()
	 * @todo Define visibility
	 */
	public function ImageWrite($destImg, $theImage, $quality = 0) {
		imageinterlace($destImg, 0);
		$ext = strtolower(substr($theImage, strrpos($theImage, '.') + 1));
		$result = FALSE;
		switch ($ext) {
		case 'jpg':

		case 'jpeg':
			if (function_exists('imageJpeg')) {
				if ($quality == 0) {
					$quality = $this->jpegQuality;
				}
				$result = imageJpeg($destImg, $theImage, $quality);
			}
			break;
		case 'gif':
			if (function_exists('imageGif')) {
				imagetruecolortopalette($destImg, TRUE, 256);
				$result = imageGif($destImg, $theImage);
			}
			break;
		case 'png':
			if (function_exists('imagePng')) {
				$result = ImagePng($destImg, $theImage);
			}
			break;
		}
		if ($result) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::fixPermissions($theImage);
		}
		return $result;
	}

	/**
	 * Creates a new GDlib image resource based on the input image filename.
	 * If it fails creating a image from the input file a blank gray image with the dimensions of the input image will be created instead.
	 *
	 * @param string $sourceImg Image filename
	 * @return pointer Image Resource pointer
	 * @todo Define visibility
	 */
	public function imageCreateFromFile($sourceImg) {
		$imgInf = pathinfo($sourceImg);
		$ext = strtolower($imgInf['extension']);
		switch ($ext) {
		case 'gif':
			if (function_exists('imagecreatefromgif')) {
				return imageCreateFromGif($sourceImg);
			}
			break;
		case 'png':
			if (function_exists('imagecreatefrompng')) {
				$imageHandle = imageCreateFromPng($sourceImg);
				if ($this->saveAlphaLayer) {
					imagesavealpha($imageHandle, TRUE);
				}
				return $imageHandle;
			}
			break;
		case 'jpg':

		case 'jpeg':
			if (function_exists('imagecreatefromjpeg')) {
				return imageCreateFromJpeg($sourceImg);
			}
			break;
		}
		// If non of the above:
		$i = @getimagesize($sourceImg);
		$im = imagecreatetruecolor($i[0], $i[1]);
		$Bcolor = ImageColorAllocate($im, 128, 128, 128);
		ImageFilledRectangle($im, 0, 0, $i[0], $i[1], $Bcolor);
		return $im;
	}

	/**
	 * Returns the HEX color value for an RGB color array
	 *
	 * @param array RGB color array
	 * @return string HEX color value
	 * @todo Define visibility
	 */
	public function hexColor($col) {
		$r = dechex($col[0]);
		if (strlen($r) < 2) {
			$r = '0' . $r;
		}
		$g = dechex($col[1]);
		if (strlen($g) < 2) {
			$g = '0' . $g;
		}
		$b = dechex($col[2]);
		if (strlen($b) < 2) {
			$b = '0' . $b;
		}
		return '#' . $r . $g . $b;
	}

	/**
	 * Unifies all colors given in the colArr color array to the first color in the array.
	 *
	 * @param pointer $img Image resource
	 * @param array $colArr Array containing RGB color arrays
	 * @param boolean $closest
	 * @return integer The index of the unified color
	 * @todo Define visibility
	 */
	public function unifyColors(&$img, $colArr, $closest = FALSE) {
		$retCol = -1;
		if (is_array($colArr) && count($colArr) && function_exists('imagepng') && function_exists('imagecreatefrompng')) {
			$firstCol = array_shift($colArr);
			$firstColArr = $this->convertColor($firstCol);
			if (count($colArr) > 1) {
				$origName = ($preName = $this->randomName() . '.png');
				$postName = $this->randomName() . '.png';
				$this->imageWrite($img, $preName);
				$firstCol = $this->hexColor($firstColArr);
				foreach ($colArr as $transparentColor) {
					$transparentColor = $this->convertColor($transparentColor);
					$transparentColor = $this->hexColor($transparentColor);
					$cmd = '-fill "' . $firstCol . '" -opaque "' . $transparentColor . '"';
					$this->imageMagickExec($preName, $postName, $cmd);
					$preName = $postName;
				}
				$this->imageMagickExec($postName, $origName, '');
				if (@is_file($origName)) {
					$tmpImg = $this->imageCreateFromFile($origName);
				}
			} else {
				$tmpImg = $img;
			}
			if ($tmpImg) {
				$img = $tmpImg;
				if ($closest) {
					$retCol = ImageColorClosest($img, $firstColArr[0], $firstColArr[1], $firstColArr[2]);
				} else {
					$retCol = ImageColorExact($img, $firstColArr[0], $firstColArr[1], $firstColArr[2]);
				}
			}
			// Unlink files from process
			if (!$this->dontUnlinkTempFiles) {
				if ($origName) {
					@unlink($origName);
				}
				if ($postName) {
					@unlink($postName);
				}
			}
		}
		return $retCol;
	}

}

?>