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
 * Standard graphical functions
 *
 * $Id$
 * Revised for TYPO3 3.6 July/2003 by Kasper Skaarhoj
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  143: class t3lib_stdGraphic
 *  218:     function init()
 *
 *              SECTION: Layering images / "IMAGE" GIFBUILDER object
 *  317:     function maskImageOntoImage(&$im,$conf,$workArea)
 *  377:     function copyImageOntoImage(&$im,$conf,$workArea)
 *  399:     function copyGifOntoGif(&$im,$cpImg,$conf,$workArea)
 *  478:     function imagecopyresized(&$im, $cpImg, $Xstart, $Ystart, $cpImgCutX, $cpImgCutY, $w, $h, $w, $h)
 *
 *              SECTION: Text / "TEXT" GIFBUILDER object
 *  527:     function makeText(&$im,$conf,$workArea)
 *  643:     function txtPosition($conf,$workArea,$BB)
 *  697:     function calcBBox($conf)
 *  766:     function addToMap($cords,$conf)
 *  787:     function calcTextCordsForMap($cords,$offset, $conf)
 *  820:     function SpacedImageTTFText(&$im, $fontSize, $angle, $x, $y, $Fcolor, $fontFile, $text, $spacing, $wordSpacing)
 *  853:     function fontResize($conf)
 *
 *              SECTION: Other GIFBUILDER objects related to TEXT
 *  915:     function makeOutline(&$im,$conf,$workArea,$txtConf)
 *  944:     function circleOffset($distance, $iterations)
 *  968:     function makeEmboss(&$im,$conf,$workArea,$txtConf)
 *  990:     function makeShadow(&$im,$conf,$workArea,$txtConf)
 *
 *              SECTION: Other GIFBUILDER objects
 * 1120:     function makeBox(&$im,$conf,$workArea)
 * 1139:     function makeEffect(&$im, $conf)
 * 1154:     function IMparams($setup)
 * 1237:     function adjust(&$im, $conf)
 * 1269:     function crop(&$im,$conf)
 * 1299:     function scale(&$im,$conf)
 * 1331:     function setWorkArea($workArea)
 *
 *              SECTION: Adjustment functions
 * 1372:     function autolevels(&$im)
 * 1403:     function outputLevels(&$im,$low,$high,$swap='')
 * 1435:     function inputLevels(&$im,$low,$high,$swap='')
 * 1466:     function reduceColors(&$im,$limit, $cols)
 *
 *              SECTION: GIFBUILDER Helper functions
 * 1504:     function prependAbsolutePath($fontFile)
 * 1518:     function v5_sharpen($factor)
 * 1537:     function v5_blur($factor)
 * 1554:     function randomName()
 * 1566:     function applyOffset($cords,$OFFSET)
 * 1579:     function convertColor($string)
 * 1629:     function recodeString($string)
 * 1650:     function singleChars($theText)
 * 1673:     function objPosition($conf,$workArea,$BB)
 *
 *              SECTION: Scaling, Dimensions of images
 * 1752:     function imageMagickConvert($imagefile,$newExt='',$w='',$h='',$params='',$frame='',$options='',$mustCreate=0)
 * 1835:     function getImageDimensions($imageFile)
 * 1863:     function cacheImageDimensions($identifyResult)
 * 1895:     function getCachedImageDimensions($imageFile)
 * 1930:     function getImageScale($info,$w,$h,$options)
 * 2018:     function file_exists_typo3temp_file($output,$orig='')
 *
 *              SECTION: ImageMagick API functions
 * 2079:     function imageMagickIdentify($imagefile)
 * 2112:     function imageMagickExec($input,$output,$params)
 * 2130:     function combineExec($input,$overlay,$mask,$output)
 * 2145:     function wrapFileName($inputName)
 *
 *              SECTION: Various IO functions
 * 2186:     function checkFile($file)
 * 2201:     function applyImageMagickToPHPGif(&$im, $command)
 * 2227:     function gif_or_jpg($type,$w,$h)
 * 2244:     function output($file)
 * 2288:     function destroy()
 * 2298:     function imgTag ($imgInfo)
 * 2310:     function ImageGif($destImg, $theImage)
 * 2327:     function imageCreateFromGif($sourceImg)
 *
 * TOTAL FUNCTIONS: 54
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
















/**
 * Class contains a bunch of cool functions for manipulating graphics with GDlib/Freetype and ImageMagick
 * VERY OFTEN used with gifbuilder that extends this class and provides a TypoScript API to using these functions
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 * @see tslib_gifBuilder
 */
class t3lib_stdGraphic	{

		// Internal configuration, set in init()
	var $combineScript = 'combine';				// The ImageMagick filename used for combining two images. This name changed during the versions.
	var $noFramePrepended=0;					// If set, there is no frame pointer prepended to the filenames.
	var $GD2=0;									// Set, if the GDlib used is version 2.
	var $imagecopyresized_fix=0;				// If set, imagecopyresized will not be called directly. For GD2 (some PHP installs?)
	var $gifExtension = 'gif';					// This should be changed to 'png' if you want this class to read/make PNG-files instead!
	var $TTFLocaleConv = '';					// Used to recode input to TTF-functions for other charsets.
	var $enable_typo3temp_db_tracking = 0;		// If set, then all files in typo3temp will be logged in a database table. In addition to being a log of the files with original filenames, it also serves to secure that the same image is not rendered simultaneously by two different processes.
	var $imageMagickPath = '';					// path to imageMagick, eg: /usr/lib/
	var $imageFileExt = 'gif,jpg,jpeg,png,tif,bmp,tga,pcx,ai,pdf';	// Commalist of file extensions perceived as images by TYPO3. List should be set to 'gif,png,jpeg,jpg' if IM is not available. Lowercase and no spaces between!
	var $webImageExt = 'gif,jpg,jpeg,png';		// Commalist of web image extensions (can be shown by a webbrowser)
	var $maskNegate = '';						// Will be ' -negate' if ImageMagick ver 5.2+. See init();
	var $NO_IM_EFFECTS = '';
	var $cmds = Array (
		'jpg' => '-colorspace RGB -sharpen 50 -quality 60',
		'jpeg' => '-colorspace RGB -sharpen 50 -quality 60',
		'gif' => '-colors 64',
		'png' => '-colors 64'
	);
	var $NO_IMAGE_MAGICK = '';
	var $V5_EFFECTS = 0;
	var $mayScaleUp = 1;

		// Variables for testing, alternative usage etc.
	var $filenamePrefix='';								// Filename prefix for images scaled in imageMagickConvert()
	var $imageMagickConvert_forceFileNameBody='';		// Forcing the output filename of imageMagickConvert() to this value. However after calling imageMagickConvert() it will be set blank again.
	var $dontCheckForExistingTempFile = 0;				// This flag should always be false. If set true, imageMagickConvert will always write a new file to the tempdir! Used for debugging.
	var $dontCompress=0;								// Prevents imageMagickConvert() from compressing the gif-files with t3lib_div::gif_compress()
	var $dontUnlinkTempFiles=0;							// For debugging ONLY!
	var $alternativeOutputKey='';						// For debugging only. Filenames will not be based on mtime and only filename (not path) will be used. This key is also included in the hash of the filename...

		// Internal:
	var $IM_commands = Array();							// All ImageMagick commands executed is stored in this array for tracking. Used by the Install Tools Image section
	var $workArea = Array();

		// Constants:
	var $tempPath = 'typo3temp/';						// The temp-directory where to store the files. Relative to PATH_site.
	var $scalecmd = '-geometry';						// ImageMagick scaling command; "-geometry" eller "-sample". Used in makeText() and imageMagickConvert()
	var $im5fx_blurSteps='1x2,2x2,3x2,4x3,5x3,5x4,6x4,7x5,8x5,9x5';			// Used by v5_blur() to simulate 10 continuous steps of blurring
	var $im5fx_sharpenSteps='1x2,2x2,3x2,2x3,3x3,4x3,3x4,4x4,4x5,5x5';		// Used by v5_sharpen() to simulate 10 continuous steps of sharpening.
	var $pixelLimitGif = 10000;							// This is the limit for the number of pixels in an image before it will be rendered as JPG instead of GIF/PNG
	var $colMap = Array (								// Array mapping HTML color names to RGB values.
		'aqua' => Array(0,255,255),
		'black' => Array(0,0,0),
		'blue' => Array(0,0,255),
		'fuchsia' => Array(255,0,255),
		'gray' => Array(128,128,128),
		'green' => Array(0,128,0),
		'lime' => Array(0,255,0),
		'maroon' => Array(128,0,0),
		'navy' => Array(0,0,128),
		'olive' => Array(128,128,0),
		'purple' => Array(128,0,128),
		'red' => Array(255,0,0),
		'silver' => Array(192,192,192),
		'teal' => Array(0,128,128),
		'yellow' => Array(255,255,0),
		'white' => Array(255,255,255)
	);

		// Charset conversion object:
	var $csConvObj;
	var $nativeCharset='';		// Is set to the native character set of the input strings.





	/**
	 * Init function. Must always call this when using the class.
	 * This function will read the configuration information from $GLOBALS['TYPO3_CONF_VARS']['GFX'] can set some values in internal variables.
	 *
	 * @return	void
	 */
	function init()	{
		$gfxConf = $GLOBALS['TYPO3_CONF_VARS']['GFX'];

		if ($gfxConf['im_combine_filename'])	$this->combineScript=$gfxConf['im_combine_filename'];
		if ($gfxConf['im_noFramePrepended'])	$this->noFramePrepended=1;

		if ($gfxConf['gdlib_2'])	{
			$this->GD2 = 1;
			$this->imagecopyresized_fix = $gfxConf['gdlib_2']==='no_imagecopyresized_fix' ? 0 : 1;
		}
		if ($gfxConf['gdlib_png'])	{
			$this->gifExtension='png';
		}
		if ($gfxConf['TTFLocaleConv']) {
			$this->TTFLocaleConv = $gfxConf['TTFLocaleConv'];
		}
		if ($gfxConf['enable_typo3temp_db_tracking']) {
			$this->enable_typo3temp_db_tracking = $gfxConf['enable_typo3temp_db_tracking'];
		}

		$this->imageMagickPath = $gfxConf['im_path'];
		$this->imageFileExt = $gfxConf['imagefile_ext'];

			// This should be set if ImageMagick ver. 5+ is used.
		if ($gfxConf['im_negate_mask'])	{
				// Boolean. Indicates if the mask images should be inverted first.
				// This depends of the ImageMagick version. Below ver. 5.1 this should be false.
				// Above ImageMagick version 5.2+ it should be true.
				// Just set the flag if the masks works opposite the intension!
			$this->maskNegate = ' -negate';
		}
		if ($gfxConf['im_no_effects'])	{
				// Boolean. This is necessary if using ImageMagick 5+.
				// Approved version for using effects is version 4.2.9.
				// Effects in Imagemagick 5+ tends to render very slowly!!
				// - therefore must be disabled in order not to perform sharpen, blurring and such.
			$this->NO_IM_EFFECTS = 1;

			$this->cmds['jpg']  = '-colorspace RGB -quality 60';
			$this->cmds['jpeg'] = '-colorspace RGB -quality 60';
		}
			// ... but if 'im_v5effects' is set, dont care about 'im_no_effects'
		if ($gfxConf['im_v5effects'])	{
			$this->NO_IM_EFFECTS = 0;
			$this->V5_EFFECTS = 1;

			if ($gfxConf['im_v5effects']>0)	{
				$this->cmds['jpg']  = '-colorspace RGB -quality 60'.$this->v5_sharpen(10);
				$this->cmds['jpeg'] = '-colorspace RGB -quality 60'.$this->v5_sharpen(10);
			}
		}

		if (!$gfxConf['im'])	{
			$this->NO_IMAGE_MAGICK = 1;
		}
			// Secures that images are not scaled up.
		if ($gfxConf['im_noScaleUp']) {
			$this->mayScaleUp=0;
		}

		if (TYPO3_MODE=='FE')	{
			$this->csConvObj = &$GLOBALS['TSFE']->csConvObj;
		} else {	// BE assumed:
			$this->csConvObj = &$GLOBALS['LANG']->csConvObj;
		}
		$this->nativeCharset = $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'];
	}
















	/*************************************************
	 *
	 * Layering images / "IMAGE" GIFBUILDER object
	 *
	 *************************************************/

	/**
	 * Implements the "IMAGE" GIFBUILDER object, when the "mask" property is true.
	 * It reads the two images defined by $conf['file'] and $conf['mask'] and copies the $conf['file'] onto the input image pointer image using the $conf['mask'] as a grayscale mask
	 * The operation involves ImageMagick for combining.
	 *
	 * @param	pointer		GDlib image pointer
	 * @param	array		TypoScript array with configuration for the GIFBUILDER object.
	 * @param	array		The current working area coordinates.
	 * @return	void
	 * @see tslib_gifBuilder::make()
	 */
	function maskImageOntoImage(&$im,$conf,$workArea)	{
		if ($conf['file'] && $conf['mask'])	{
			$BBimage = $this->imageMagickConvert($conf['file'],$this->gifExtension,'','','','','');
			$BBmask = $this->imageMagickConvert($conf['mask'],$this->gifExtension,'','','','','');
			if ($BBimage && $BBmask)	{
				$w = imagesx($im);
				$h = imagesy($im);
				$tmpStr = $this->randomName();
				$theImage = $tmpStr.'_img.'.$this->gifExtension;
				$theDest = $tmpStr.'_dest.'.$this->gifExtension;
				$theMask = $tmpStr.'_mask.'.$this->gifExtension;
				$theMask2 = $tmpStr.'_mask2.'.trim($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_mask_temp_ext_noloss']);
						// prepare overlay image
				$cpImg = $this->imageCreateFromGif($BBimage[3]);
				$destImg = imagecreate($w,$h);
				ImageColorAllocate($destImg, 0,0,0);
				$this->copyGifOntoGif($destImg,$cpImg,$conf,$workArea);
				$this->ImageGif($destImg, $theImage);
				imageDestroy($cpImg);
				imageDestroy($destImg);
						// prepare mask image
				$cpImg = $this->imageCreateFromGif($BBmask[3]);
				$destImg = imagecreate($w,$h);
				ImageColorAllocate($destImg, 0,0,0);
				$this->copyGifOntoGif($destImg,$cpImg,$conf,$workArea);
				$this->ImageGif($destImg, $theMask);
				imageDestroy($cpImg);
				imageDestroy($destImg);
					// treat the mask
				$this->imageMagickExec($theMask,$theMask2,'-colorspace GRAY'.$this->maskNegate);
					// mask the images
				$this->ImageGif($im, $theDest);

				$this->combineExec($theDest,$theImage,$theMask2,$theDest);

				$backIm = $this->imageCreateFromGif($theDest);	// The main image is loaded again...
				if ($backIm)	{	// ... and if nothing went wrong we load it onto the old one.
					ImageColorTransparent($backIm,-1);
					$im = $backIm;
				}
					// unlink files from process
				if (!$this->dontUnlinkTempFiles)	{
					unlink($theDest);
					unlink($theImage);
					unlink($theMask);
					unlink($theMask2);
				}
			}
		}
	}

	/**
	 * Implements the "IMAGE" GIFBUILDER object, when the "mask" property is false (using only $conf['file'])
	 *
	 * @param	pointer		GDlib image pointer
	 * @param	array		TypoScript array with configuration for the GIFBUILDER object.
	 * @param	array		The current working area coordinates.
	 * @return	void
	 * @see tslib_gifBuilder::make(), maskImageOntoImage()
	 */
	function copyImageOntoImage(&$im,$conf,$workArea)	{
		if ($conf['file'])	{
			if ($conf['BBOX'][2]!=$this->gifExtension)	{
				$conf['BBOX']=$this->imageMagickConvert($conf['BBOX'][3],$this->gifExtension,'','','','','');
				$conf['file']=$conf['BBOX'][3];
			}
			$cpImg = $this->imageCreateFromGif($conf['file']);
			$this->copyGifOntoGif($im,$cpImg,$conf,$workArea);
			imageDestroy($cpImg);
		}
	}

	/**
	 * Copies two GDlib image pointers onto each other, using TypoScript configuration from $conf and the input $workArea definition.
	 *
	 * @param	pointer		GDlib image pointer, destination (bottom image)
	 * @param	pointer		GDlib image pointer, source (top image)
	 * @param	array		TypoScript array with the properties for the IMAGE GIFBUILDER object. Only used for the "tile" property value.
	 * @param	array		Work area
	 * @return	void		Works on the $im image pointer
	 * @access private
	 */
	function copyGifOntoGif(&$im,$cpImg,$conf,$workArea)	{
		$cpW = imagesx($cpImg);
		$cpH = imagesy($cpImg);
		$tile = t3lib_div::intExplode(',',$conf['tile']);
		$tile[0] = t3lib_div::intInRange($tile[0],1,20);
		$tile[1] = t3lib_div::intInRange($tile[1],1,20);
		$cpOff = $this->objPosition($conf,$workArea,Array($cpW*$tile[0],$cpH*$tile[1]));

		for ($xt=0;$xt<$tile[0];$xt++)	{
			$Xstart=$cpOff[0]+$cpW*$xt;
			if ($Xstart+$cpW > $workArea[0])	{	// if this image is inside of the workArea, then go on
					// X:
				if ($Xstart < $workArea[0])	{
					$cpImgCutX = $workArea[0]-$Xstart;
					$Xstart = $workArea[0];
				} else {
					$cpImgCutX = 0;
				}
				$w = $cpW-$cpImgCutX;
				if ($Xstart > $workArea[0]+$workArea[2]-$w)	{
					$w = $workArea[0]+$workArea[2]-$Xstart;
				}
				if ($Xstart < $workArea[0]+$workArea[2])	{	// if this image is inside of the workArea, then go on
						// Y:
					for ($yt=0;$yt<$tile[1];$yt++)	{
						$Ystart=$cpOff[1]+$cpH*$yt;
						if ($Ystart+$cpH > $workArea[1])	{	// if this image is inside of the workArea, then go on
							if ($Ystart < $workArea[1])	{
								$cpImgCutY = $workArea[1]-$Ystart;
								$Ystart = $workArea[1];
							} else {
								$cpImgCutY = 0;
							}
							$h = $cpH-$cpImgCutY;
							if ($Ystart > $workArea[1]+$workArea[3]-$h)	{
								$h = $workArea[1]+$workArea[3]-$Ystart;
							}
							if ($Ystart < $workArea[1]+$workArea[3])	{	// if this image is inside of the workArea, then go on
								$this->imagecopyresized($im, $cpImg, $Xstart, $Ystart, $cpImgCutX, $cpImgCutY, $w, $h, $w, $h);
							}
						}
					}  // Y:
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
	 * 		1) Create a blank TRUE-COLOR image
	 * 		2) Copy the destination image onto that one
	 * 		3) Then do the actual operation; Copying the source (top image) onto that
	 * 		4) ... and return the result pointer.
	 * 		5) Reduce colors (if we do not, the result may become strange!)
	 * It works, but the resulting images is now a true-color PNG which may be very large.
	 * So, why not use 'imagetruecolortopalette ($im, TRUE, 256)' - well because it does NOT WORK! So simple is that.
	 *
	 * For parameters, see PHP function "imagecopyresized()"
	 *
	 * @param	pointer		see PHP function "imagecopyresized()"
	 * @param	pointer		see PHP function "imagecopyresized()"
	 * @param	integer		see PHP function "imagecopyresized()"
	 * @param	integer		see PHP function "imagecopyresized()"
	 * @param	integer		see PHP function "imagecopyresized()"
	 * @param	integer		see PHP function "imagecopyresized()"
	 * @param	integer		see PHP function "imagecopyresized()"
	 * @param	integer		see PHP function "imagecopyresized()"
	 * @param	integer		see PHP function "imagecopyresized()"
	 * @param	integer		see PHP function "imagecopyresized()"
	 * @return	void
	 * @access private
	 * @see t3lib_iconWorks::imagecopyresized()
	 */
	function imagecopyresized(&$im, $cpImg, $Xstart, $Ystart, $cpImgCutX, $cpImgCutY, $w, $h, $w, $h)	{
		if ($this->imagecopyresized_fix)	{
			$im_base = imagecreatetruecolor(imagesx($im), imagesy($im));	// Make true color image
			imagecopyresized($im_base, $im, 0,0,0,0, imagesx($im),imagesy($im),imagesx($im),imagesy($im));	// Copy the source image onto that
			imagecopyresized($im_base, $cpImg, $Xstart, $Ystart, $cpImgCutX, $cpImgCutY, $w, $h, $w, $h);	// Then copy the $cpImg onto that (the actual operation!)
			$im = $im_base;	// Set pointer
			$this->makeEffect($im, Array('value'=>'colors=256'));	// Reduce colors to 256 - make SURE that IM is working then!
		} else {
			imagecopyresized($im, $cpImg, $Xstart, $Ystart, $cpImgCutX, $cpImgCutY, $w, $h, $w, $h);
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
	 * @param	pointer		GDlib image pointer
	 * @param	array		TypoScript array with configuration for the GIFBUILDER object.
	 * @param	array		The current working area coordinates.
	 * @return	void
	 * @see tslib_gifBuilder::make()
	 */
	function makeText(&$im,$conf,$workArea)	{
			// Spacing
		$spacing = intval($conf['spacing']);
		$wordSpacing = intval($conf['wordSpacing']);
		$wordSpacing = $wordSpacing?$wordSpacing:$spacing*2;
			// Position
		$txtPos = $this->txtPosition($conf,$workArea,$conf['BBOX']);
		$theText = $this->recodeString($conf['text']);

		if ($conf['imgMap'] && is_array($conf['imgMap.']))	{
			$this->addToMap($this->calcTextCordsForMap($conf['BBOX'][2],$txtPos, $conf['imgMap.']), $conf['imgMap.']);
		}
		if (!$conf['hideButCreateMap'])	{
				// Font Color:
			$cols=$this->convertColor($conf['fontColor']);
				// NiceText is calculated
			if (!$conf['niceText']) {
					// Font Color is reserved:
				$this->reduceColors($im,256, 200);
				$Fcolor = ImageColorAllocate($im, $cols[0],$cols[1],$cols[2]);
					// antiAliasing is setup:
				$Fcolor = ($conf['antiAlias']) ? $Fcolor : -$Fcolor;

				for ($a=0; $a<$conf['iterations']; $a++)	{
					if ($spacing || $wordSpacing)	{		// If any kind of spacing applys, we use this function:
						$this->SpacedImageTTFText($im, $conf['fontSize'], $conf['angle'], $txtPos[0], $txtPos[1], $Fcolor, t3lib_stdGraphic::prependAbsolutePath($conf['fontFile']), $theText, $spacing, $wordSpacing);
					} else {
						ImageTTFText($im, t3lib_div::freetypeDpiComp($conf['fontSize']), $conf['angle'], $txtPos[0], $txtPos[1], $Fcolor, t3lib_stdGraphic::prependAbsolutePath($conf['fontFile']), $theText);
					}
				}
			} else {		// NICETEXT::
					// options anti_aliased and iterations is NOT available when doing this!!
				$w = imagesx($im);
				$h = imagesy($im);
				$tmpStr = $this->randomName();

				$fileMenu = $tmpStr.'_menuNT.'.$this->gifExtension;
				$fileColor = $tmpStr.'_colorNT.'.$this->gifExtension;
				$fileMask = $tmpStr.'_maskNT.'.$this->gifExtension;
					// Scalefactor
				$sF = t3lib_div::intInRange($conf['niceText.']['scaleFactor'],2,5);
				$newW = ceil($sF*imagesx($im));
				$newH = ceil($sF*imagesy($im));

					// Make mask
				$maskImg = imagecreate($newW, $newH);
				ImageColorAllocate($maskImg, 255,255,255);
				$Fcolor = ImageColorAllocate($maskImg, 0,0,0);
				if ($spacing || $wordSpacing)	{		// If any kind of spacing applys, we use this function:
					$this->SpacedImageTTFText($maskImg, ceil($sF*$conf['fontSize']), $conf['angle'], ceil($sF*$txtPos[0]), ceil($sF*$txtPos[1]), $Fcolor, t3lib_stdGraphic::prependAbsolutePath($conf['fontFile']), $theText, ceil($sF*$spacing), ceil($sF*$wordSpacing));
				} else {
					ImageTTFText($maskImg, t3lib_div::freetypeDpiComp(ceil($sF*$conf['fontSize'])), $conf['angle'], ceil($sF*$txtPos[0]), ceil($sF*$txtPos[1]), $Fcolor, t3lib_stdGraphic::prependAbsolutePath($conf['fontFile']), $theText);
				}
				$this->ImageGif($maskImg, $fileMask);
				ImageDestroy($maskImg);

					// Downscales the mask
				if ($this->NO_IM_EFFECTS)	{
					if ($this->maskNegate)	{
						$command = trim($this->scalecmd.' '.$w.'x'.$h.'!');		// Negate 2 times makes no negate...
					} else {
						$command = trim($this->scalecmd.' '.$w.'x'.$h.'! -negate');
					}
				} else {
					if ($this->maskNegate)	{
						$command = trim($conf['niceText.']['before'].' '.$this->scalecmd.' '.$w.'x'.$h.'! '.$conf['niceText.']['after']);
					} else {
						$command = trim($conf['niceText.']['before'].' '.$this->scalecmd.' '.$w.'x'.$h.'! '.$conf['niceText.']['after'].' -negate');
					}
					if ($conf['niceText.']['sharpen']) {
						if ($this->V5_EFFECTS)	{
							$command.=$this->v5_sharpen($conf['niceText.']['sharpen']);
						} else {
							$command.=' -sharpen '.t3lib_div::intInRange($conf['niceText.']['sharpen'],1,99);
						}
					}
				}
				$this->imageMagickExec($fileMask,$fileMask,$command);

					// Make the color-file
				$colorImg = imagecreate($w,$h);
				ImageColorAllocate($colorImg, $cols[0],$cols[1],$cols[2]);
				$this->ImageGif($colorImg, $fileColor);
				ImageDestroy($colorImg);

					// The mask is applied
				$this->ImageGif($im, $fileMenu);	// The main pictures is saved temporarily

				$this->combineExec($fileMenu,$fileColor,$fileMask,$fileMenu);

				$backIm = $this->imageCreateFromGif($fileMenu);	// The main image is loaded again...
				if ($backIm)	{	// ... and if nothing went wrong we load it onto the old one.
					ImageColorTransparent($backIm,-1);
					$im = $backIm;
				}

					// Deleting temporary files;
				if (!$this->dontUnlinkTempFiles)	{
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
	 * @param	array		TypoScript array for the TEXT GIFBUILDER object
	 * @param	array		Workarea definition
	 * @param	array		Bounding box information, was set in tslib_gifBuilder::start()
	 * @return	array		[0]=x, [1]=y, [2]=w, [3]=h
	 * @access private
	 * @see makeText()
	 */
	function txtPosition($conf,$workArea,$BB) {
		$bbox = $BB[2];
		$angle=intval($conf['angle'])/180*pi();
		$conf['angle']=0;
		$straightBB = $this->calcBBox($conf);

			// offset, align, valign, workarea
		$result=Array();	// [0]=x, [1]=y, [2]=w, [3]=h
		$result[2] = $BB[0];
		$result[3] = $BB[1];
		$w=$workArea[2];
		$h=$workArea[3];

		switch($conf['align'])	{
			case 'right':
			case 'center':
				$factor=abs(cos($angle));
				$sign=(cos($angle)<0)?-1:1;
				$len1 = $sign*$factor*$straightBB[0];
				$len2= $sign*$BB[0];
				$result[0] = $w-ceil($len2*$factor+(1-$factor)*$len1);

				$factor=abs(sin($angle));
				$sign=(sin($angle)<0)?-1:1;
				$len1= $sign*$factor*$straightBB[0];
				$len2= $sign*$BB[1];
				$result[1]=ceil($len2*$factor+(1-$factor)*$len1);
			break;
		}
		switch($conf['align'])	{
			case 'right':
			break;
			case 'center':
				$result[0] = round(($result[0])/2);
				$result[1] = round(($result[1])/2);
			break;
			default:
				$result[0]=0;
				$result[1]=0;
			break;
		}
		$result = $this->applyOffset($result,t3lib_div::intExplode(',',$conf['offset']));
		$result = $this->applyOffset($result,$workArea);
		return $result;
	}

	/**
	 * Calculates bounding box information for the TEXT GIFBUILDER object.
	 *
	 * @param	array		TypoScript array for the TEXT GIFBUILDER object
	 * @return	array		Array with three keys [0]/[1] being x/y and [2] being the bounding box array
	 * @access private
	 * @see txtPosition(), tslib_gifBuilder::start()
	 */
	function calcBBox($conf)	{
		if (!$conf['niceText']) {
			$sF = 1;
		} else {		// NICETEXT::
			$sF = t3lib_div::intInRange($conf['niceText.']['scaleFactor'],2,5);
		}

		$spacing = intval($conf['spacing']);
		$wordSpacing = intval($conf['wordSpacing']);
		$wordSpacing = $wordSpacing?$wordSpacing:$spacing*2;

		$spacing*=$sF;
		$wordSpacing*=$sF;
		$theText = $this->recodeString($conf['text']);

		$charInf = ImageTTFBBox(t3lib_div::freetypeDpiComp($sF*$conf['fontSize']), $conf['angle'], t3lib_stdGraphic::prependAbsolutePath($conf['fontFile']), $theText);
		$theBBoxInfo = $charInf;
		if ($conf['angle'])	{
			$xArr = Array($charInf[0],$charInf[2],$charInf[4],$charInf[6]);
			$yArr = Array($charInf[1],$charInf[3],$charInf[5],$charInf[7]);
			$x=max($xArr)-min($xArr);
			$y=max($yArr)-min($yArr);
		} else {
			$x = ($charInf[2]-$charInf[0]);
			$y = ($charInf[1]-$charInf[7]);
		}
		if ($spacing || $wordSpacing)	{		// If any kind of spacing applys, we use this function:
			$x=0;
			if (!$spacing && $wordSpacing)	{
				$bits = explode(' ',$theText);
				while(list(,$word)=each($bits))	{
					$word.=' ';
					$wordInf = ImageTTFBBox(t3lib_div::freetypeDpiComp($sF*$conf['fontSize']), $conf['angle'], t3lib_stdGraphic::prependAbsolutePath($conf['fontFile']), $word);
					$wordW = ($wordInf[2]-$wordInf[0]);
					$x+=$wordW+$wordSpacing;
				}
			} else {
				$utf8Chars = $this->singleChars($theText);
					// For each UTF-8 char, do:
				foreach($utf8Chars as $char)	{
					$charInf = ImageTTFBBox(t3lib_div::freetypeDpiComp($sF*$conf['fontSize']), $conf['angle'], t3lib_stdGraphic::prependAbsolutePath($conf['fontFile']), $char);
					$charW = ($charInf[2]-$charInf[0]);
					$x+=$charW+(($char==' ')?$wordSpacing:$spacing);
				}
			}
		}

		if ($sF>1) {
			$x = ceil($x/$sF);
			$y = ceil($y/$sF);
			if (is_array($theBBoxInfo))	{
				reset($theBBoxInfo);
				while(list($key,$val)=each($theBBoxInfo))	{
					$theBBoxInfo[$key]=ceil($theBBoxInfo[$key]/$sF);
				}
			}
		}
		return Array($x,$y,$theBBoxInfo);
	}

	/**
	 * Adds an <area> tag to the internal variable $this->map which is used to accumulate the content for an ImageMap
	 *
	 * @param	array		Coordinates for a polygon image map as created by ->calcTextCordsForMap()
	 * @param	array		Configuration for "imgMap." property of a TEXT GIFBUILDER object.
	 * @return	void
	 * @access private
	 * @see makeText(), calcTextCordsForMap()
	 */
	function addToMap($cords,$conf)	{
		$JS = $conf['noBlur'] ? '' : ' onfocus="blurLink(this);"';

		$this->map.='<area'.
				' shape="poly"'.
				' coords="'.implode($cords,',').'"'.
				' href="'.htmlspecialchars($conf['url']).'"'.
				($conf['target'] ? ' target="'.htmlspecialchars($conf['target']).'"' : '').
				$JS.
				(strlen($conf['titleText']) ? ' title="'.htmlspecialchars($conf['titleText']).'"' : '').
				' alt="'.htmlspecialchars($conf['altText']).'" />';
	}

	/**
	 * Calculating the coordinates for a TEXT string on an image map. Used in an <area> tag
	 *
	 * @param	array		Coordinates (from BBOX array)
	 * @param	array		Offset array
	 * @param	array		Configuration for "imgMap." property of a TEXT GIFBUILDER object.
	 * @return	array
	 * @access private
	 * @see makeText(), calcTextCordsForMap()
	 */
	function calcTextCordsForMap($cords,$offset, $conf)	{
		$pars = t3lib_div::intExplode(',',$conf['explode'].',');

		$newCords[0] = $cords[0]+$offset[0]-$pars[0];
		$newCords[1] = $cords[1]+$offset[1]+$pars[1];
		$newCords[2] = $cords[2]+$offset[0]+$pars[0];
		$newCords[3] = $cords[3]+$offset[1]+$pars[1];
		$newCords[4] = $cords[4]+$offset[0]+$pars[0];
		$newCords[5] = $cords[5]+$offset[1]-$pars[1];
		$newCords[6] = $cords[6]+$offset[0]-$pars[0];
		$newCords[7] = $cords[7]+$offset[1]-$pars[1];

		return $newCords;
	}

	/**
	 * Printing text onto an image like the PHP function imageTTFText does but in addition it offers options for spacing of letters and words.
	 * Spacing is done by printing one char at a time and this means that the spacing is rather uneven and probably not very nice.
	 * See
	 *
	 * @param	pointer		(See argument for PHP function imageTTFtext())
	 * @param	integer		(See argument for PHP function imageTTFtext())
	 * @param	integer		(See argument for PHP function imageTTFtext())
	 * @param	integer		(See argument for PHP function imageTTFtext())
	 * @param	integer		(See argument for PHP function imageTTFtext())
	 * @param	integer		(See argument for PHP function imageTTFtext())
	 * @param	string		(See argument for PHP function imageTTFtext())
	 * @param	string		(See argument for PHP function imageTTFtext()). UTF-8 string, possibly with entities in.
	 * @param	integer		The spacing of letters in pixels
	 * @param	integer		The spacing of words in pixels
	 * @return	void
	 * @access private
	 */
	function SpacedImageTTFText(&$im, $fontSize, $angle, $x, $y, $Fcolor, $fontFile, $text, $spacing, $wordSpacing)	{
		if (!$spacing && $wordSpacing)	{
			$bits = explode(' ',$text);
			reset($bits);
			while(list(,$word)=each($bits))	{
				$word.=' ';
				$word = $word;
				$wordInf = ImageTTFBBox(t3lib_div::freetypeDpiComp($fontSize), $angle, t3lib_stdGraphic::prependAbsolutePath($fontFile), $word);
				$wordW = ($wordInf[2]-$wordInf[0]);
				ImageTTFText($im, t3lib_div::freetypeDpiComp($fontSize), $angle, $x, $y, $Fcolor, t3lib_stdGraphic::prependAbsolutePath($fontFile), $word);
				$x+=$wordW+$wordSpacing;
			}
		} else {
			$utf8Chars = $this->singleChars($text);
				// For each UTF-8 char, do:
			foreach($utf8Chars as $char)	{
				$charInf = ImageTTFBBox(t3lib_div::freetypeDpiComp($fontSize), $angle, t3lib_stdGraphic::prependAbsolutePath($fontFile), $char);
				$charW = ($charInf[2]-$charInf[0]);
				ImageTTFText($im, t3lib_div::freetypeDpiComp($fontSize), $angle, $x, $y, $Fcolor, t3lib_stdGraphic::prependAbsolutePath($fontFile), $char);
				$x+=$charW+(($char==' ')?$wordSpacing:$spacing);
			}
		}
	}

	/**
	 * Function that finds the right fontsize that will render the textstring within a certain width
	 *
	 * @param	array		The TypoScript properties of the TEXT GIFBUILDER object
	 * @return	integer		The new fontSize
	 * @access private
	 * @author	Rene Fritz <r.fritz@colorcube.de>
	 * @see tslib_gifBuilder::start()
	 */
	function fontResize($conf) {
		// you have to use +calc options like [10.h] in 'offset' to get the right position of your text-image, if you use +calc in XY height!!!!
		$maxWidth = intval($conf['maxWidth']);
		if ($maxWidth)	{
			if ($spacing || $wordSpacing)	{		// If any kind of spacing applys, we use this function:
				return $conf['fontSize'];
				//  ################ no calc for spacing yet !!!!!!
			} else {
				$fontSize = $conf['fontSize'];
				do {
					$conf['fontSize'] = $fontSize;
						// determine bounding box.
					$bounds = ImageTTFBBox(t3lib_div::freetypeDpiComp($conf['fontSize']), $conf['angle'], t3lib_stdGraphic::prependAbsolutePath($conf['fontFile']), $this->recodeString($conf['text']));
					if ($conf['angle']< 0) {
						$pixelWidth = abs($bounds[4]-$bounds[0]);
					} elseif ($conf['angle'] > 0) {
						$pixelWidth = abs($bounds[2]-$bounds[6]);
					} else {
						$pixelWidth = abs($bounds[4]-$bounds[6]);
					}
						// This is a very raw calculation but it makes it in one step
					$fontSize=(int)($maxWidth*$fontSize/$pixelWidth);
					if ($fontSize >= $conf['fontSize'])	{
						$fontSize = $conf['fontSize']-1;
					}
				} while ($pixelWidth > $maxWidth);
			}//if spacing
		}
		return $conf['fontSize'];
	}
















	/*********************************************
	 *
	 * Other GIFBUILDER objects related to TEXT
	 *
	 *********************************************/

	/**
	 * Implements the "OUTLINE" GIFBUILDER object / property for the TEXT object
	 *
	 * @param	pointer		GDlib image pointer
	 * @param	array		TypoScript array with configuration for the GIFBUILDER object.
	 * @param	array		The current working area coordinates.
	 * @param	array		TypoScript array with configuration for the associated TEXT GIFBUILDER object.
	 * @return	void
	 * @see tslib_gifBuilder::make(), makeText()
	 */
	function makeOutline(&$im,$conf,$workArea,$txtConf)	{
		$thickness = intval($conf['thickness']);
		if ($thickness)	{
			$txtConf['fontColor'] = $conf['color'];
			$outLineDist = t3lib_div::intInRange($thickness,1,2);
			for ($b=1;$b<=$outLineDist;$b++)	{
				if ($b==1)	{
					$it = 8;
				} else {
					$it = 16;
				}
				$outL = $this->circleOffset($b, $it);
				for ($a=0;$a<$it;$a++)	{
					$this->makeText($im,$txtConf,$this->applyOffset($workArea,$outL[$a]));
				}
			}
		}
	}

	/**
	 * Creates some offset values in an array used to simulate a circularly applied outline around TEXT
	 *
	 * access private
	 *
	 * @param	integer		Distance
	 * @param	integer		Iterations.
	 * @return	array
	 * @see makeOutline()
	 */
	function circleOffset($distance, $iterations)	{
		$res = Array();
		if ($distance && $iterations)	{
			for ($a=0;$a<$iterations;$a++)	{
				$yOff = round(sin(2*pi()/$iterations*($a+1))*100*$distance);
				if ($yOff)	{$yOff = intval(ceil(abs($yOff/100))*($yOff/abs($yOff)));}
				$xOff = round(cos(2*pi()/$iterations*($a+1))*100*$distance);
				if ($xOff)	{$xOff = intval(ceil(abs($xOff/100))*($xOff/abs($xOff)));}
				$res[$a] = Array($xOff,$yOff);
			}
		}
		return $res;
	}

	/**
	 * Implements the "EMBOSS" GIFBUILDER object / property for the TEXT object
	 *
	 * @param	pointer		GDlib image pointer
	 * @param	array		TypoScript array with configuration for the GIFBUILDER object.
	 * @param	array		The current working area coordinates.
	 * @param	array		TypoScript array with configuration for the associated TEXT GIFBUILDER object.
	 * @return	void
	 * @see tslib_gifBuilder::make(), makeShadow()
	 */
	function makeEmboss(&$im,$conf,$workArea,$txtConf)	{
		$conf['color']=$conf['highColor'];
		$this->makeShadow($im,$conf,$workArea,$txtConf);
		$newOffset = t3lib_div::intExplode(',',$conf['offset']);
		$newOffset[0]*=-1;
		$newOffset[1]*=-1;
		$conf['offset']=implode($newOffset,',');
		$conf['color']=$conf['lowColor'];
		$this->makeShadow($im,$conf,$workArea,$txtConf);
	}

	/**
	 * Implements the "SHADOW" GIFBUILDER object / property for the TEXT object
	 * The operation involves ImageMagick for combining.
	 *
	 * @param	pointer		GDlib image pointer
	 * @param	array		TypoScript array with configuration for the GIFBUILDER object.
	 * @param	array		The current working area coordinates.
	 * @param	array		TypoScript array with configuration for the associated TEXT GIFBUILDER object.
	 * @return	void
	 * @see tslib_gifBuilder::make(), makeText(), makeEmboss()
	 */
	function makeShadow(&$im,$conf,$workArea,$txtConf)	{
		$workArea = $this->applyOffset($workArea,t3lib_div::intExplode(',',$conf['offset']));
		$blurRate = t3lib_div::intInRange(intval($conf['blur']),0,99);

		if (!$blurRate || $this->NO_IM_EFFECTS)	{		// No effects if ImageMagick ver. 5+
			$txtConf['fontColor'] = $conf['color'];
			$this->makeText($im,$txtConf,$workArea);
		} else {
			$w = imagesx($im);
			$h = imagesy($im);
			$blurBorder= 3;	// area around the blur used for cropping something
			$tmpStr = $this->randomName();
			$fileMenu = $tmpStr.'_menu.'.$this->gifExtension;
			$fileColor = $tmpStr.'_color.'.$this->gifExtension;
			$fileMask = $tmpStr.'_mask.'.$this->gifExtension;

				// BlurColor Image laves
			$blurColImg = imagecreate($w,$h);
			$bcols=$this->convertColor($conf['color']);
			ImageColorAllocate($blurColImg, $bcols[0],$bcols[1],$bcols[2]);
			$this->ImageGif($blurColImg, $fileColor);
			ImageDestroy($blurColImg);

				// The mask is made: BlurTextImage
			$blurTextImg = imagecreate($w+$blurBorder*2,$h+$blurBorder*2);
			ImageColorAllocate($blurTextImg, 0,0,0);		// black background
			$txtConf['fontColor'] = 'white';
			$blurBordArr = Array($blurBorder,$blurBorder);
			$this->makeText($blurTextImg,$txtConf,  $this->applyOffset($workArea,$blurBordArr));
			$this->ImageGif($blurTextImg, $fileMask);	// dump to temporary file
			ImageDestroy($blurTextImg);	// destroy


			$command='';
			$command.=$this->maskNegate;

			if ($this->V5_EFFECTS)	{
				$command.=$this->v5_blur($blurRate+1);
			} else {
					// Blurring of the mask
				$times = ceil($blurRate/10);	// How many blur-commands that is executed. Min = 1;
				$newBlurRate = $blurRate*4;		// Here I boost the blur-rate so that it is 100 already at 25. The rest is done by up to 99 iterations of the blur-command.
				$newBlurRate = t3lib_div::intInRange($newBlurRate,1,99);
				for ($a=0;$a<$times;$a++)	{		// Building blur-command
					$command.=' -blur '.$blurRate;
				}
			}

			$this->imageMagickExec($fileMask,$fileMask,$command);

			$blurTextImg_tmp = $this->imageCreateFromGif($fileMask);	// the mask is loaded again
			if ($blurTextImg_tmp)	{	// if nothing went wrong we continue with the blurred mask

					// cropping the border from the mask
				$blurTextImg = imagecreate($w,$h);
				$this->imagecopyresized($blurTextImg, $blurTextImg_tmp, 0, 0, $blurBorder, $blurBorder, $w, $h, $w, $h);
				ImageDestroy($blurTextImg_tmp);	// Destroy the temporary mask

					// adjust the mask
				$intensity = 40;
				if ($conf['intensity'])	{
					$intensity = t3lib_div::intInRange($conf['intensity'],0,100);
				}
				$intensity = ceil(255-($intensity/100*255));
				$this->inputLevels($blurTextImg,0,$intensity,$this->maskNegate);

				$opacity = t3lib_div::intInRange(intval($conf['opacity']),0,100);
				if ($opacity && $opacity<100)	{
					$high = ceil(255*$opacity/100);
					$this->outputLevels($blurTextImg,0,$high,$this->maskNegate);	// reducing levels as the opacity demands
				}

				$this->ImageGif($blurTextImg, $fileMask);	// Dump the mask again
				ImageDestroy($blurTextImg);	// Destroy the mask

					// The pictures are combined
				$this->ImageGif($im, $fileMenu);	// The main pictures is saved temporarily

				$this->combineExec($fileMenu,$fileColor,$fileMask,$fileMenu);

				$backIm = $this->imageCreateFromGif($fileMenu);	// The main image is loaded again...
				if ($backIm)	{	// ... and if nothing went wrong we load it onto the old one.
					ImageColorTransparent($backIm,-1);
					$im = $backIm;
				}
			}
				// Deleting temporary files;
			if (!$this->dontUnlinkTempFiles)	{
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
	 * @param	pointer		GDlib image pointer
	 * @param	array		TypoScript array with configuration for the GIFBUILDER object.
	 * @param	array		The current working area coordinates.
	 * @return	void
	 * @see tslib_gifBuilder::make()
	 */
	function makeBox(&$im,$conf,$workArea)	{
		$cords = t3lib_div::intExplode(',',$conf['dimensions'].',,,');
		$conf['offset']=$cords[0].','.$cords[1];
		$cords = $this->objPosition($conf,$workArea,Array($cords[2],$cords[3]));
		$cols=$this->convertColor($conf['color']);
		$this->reduceColors($im,256, 255);
		$tmpColor = ImageColorAllocate($im, $cols[0],$cols[1],$cols[2]);
		imagefilledrectangle($im, $cords[0], $cords[1], $cords[0]+$cords[2]-1, $cords[1]+$cords[3]-1, $tmpColor);
	}

	/**
	 * Implements the "EFFECT" GIFBUILDER object
	 * The operation involves ImageMagick for applying effects
	 *
	 * @param	pointer		GDlib image pointer
	 * @param	array		TypoScript array with configuration for the GIFBUILDER object.
	 * @return	void
	 * @see tslib_gifBuilder::make(), applyImageMagickToPHPGif()
	 */
	function makeEffect(&$im, $conf)	{
		$commands = $this->IMparams($conf['value']);
		if ($commands)	{
			$this->applyImageMagickToPHPGif($im, $commands);
		}
	}

	/**
	 * Creating ImageMagick paramters from TypoScript property
	 *
	 * @param	string		A string with effect keywords=value pairs separated by "|"
	 * @return	string		ImageMagick prepared parameters.
	 * @access private
	 * @see makeEffect()
	 */
	function IMparams($setup)	{
		if (!trim($setup)){return '';}
		$effects = explode('|', $setup);
		$commands = '';
		while(list(,$val)=each($effects))	{
			$pairs=explode('=',$val,2);
			$value = trim($pairs[1]);
			$effect = strtolower(trim($pairs[0]));
			switch($effect)	{
				case 'gamma':
					$commands.=' -gamma '.doubleval($value);
				break;
				case 'blur':
					if (!$this->NO_IM_EFFECTS)	{
						if ($this->V5_EFFECTS)	{
							$commands.=$this->v5_blur($value);
						} else {
							$commands.=' -blur '.t3lib_div::intInRange($value,1,99);
						}
					}
				break;
				case 'sharpen':
					if (!$this->NO_IM_EFFECTS)	{
						if ($this->V5_EFFECTS)	{
							$commands.=$this->v5_sharpen($value);
						} else {
							$commands.=' -sharpen '.t3lib_div::intInRange($value,1,99);
						}
					}
				break;
				case 'rotate':
					$commands.=' -rotate '.t3lib_div::intInRange($value,0,360);
				break;
				case 'solarize':
					$commands.=' -solarize '.t3lib_div::intInRange($value,0,99);
				break;
				case 'swirl':
					$commands.=' -swirl '.t3lib_div::intInRange($value,0,1000);
				break;
				case 'wave':
					$params = t3lib_div::intExplode(',',$value);
					$commands.=' -wave '.t3lib_div::intInRange($params[0],0,99).'x'.t3lib_div::intInRange($params[1],0,99);
				break;
				case 'charcoal':
					$commands.=' -charcoal '.t3lib_div::intInRange($value,0,100);
				break;
				case 'gray':
					$commands.=' -colorspace GRAY';
				break;
				case 'edge':
					$commands.=' -edge '.t3lib_div::intInRange($value,0,99);
				break;
				case 'emboss':
					$commands.=' -emboss';
				break;
				case 'flip':
					$commands.=' -flip';
				break;
				case 'flop':
					$commands.=' -flop';
				break;
				case 'colors':
					$commands.=' -colors '.t3lib_div::intInRange($value,2,255);
				break;
				case 'shear':
					$commands.=' -shear '.t3lib_div::intInRange($value,-90,90);
				break;
				case 'invert':
					$commands.=' -negate';
				break;
			}
		}
		return $commands;
	}

	/**
	 * Implements the "ADJUST" GIFBUILDER object
	 *
	 * @param	pointer		GDlib image pointer
	 * @param	array		TypoScript array with configuration for the GIFBUILDER object.
	 * @return	void
	 * @see tslib_gifBuilder::make(), autoLevels(), outputLevels(), inputLevels()
	 */
	function adjust(&$im, $conf)	{
		$setup = $conf['value'];
		if (!trim($setup)){return '';}
		$effects = explode('|', $setup);
		while(list(,$val)=each($effects))	{
			$pairs=explode('=',$val,2);
			$value = trim($pairs[1]);
			$effect = strtolower(trim($pairs[0]));
			switch($effect)	{
				case 'inputlevels':	// low,high
					$params = t3lib_div::intExplode(',',$value);
					$this->inputLevels($im,$params[0],$params[1]);
				break;
				case 'outputlevels':
					$params = t3lib_div::intExplode(',',$value);
					$this->outputLevels($im,$params[0],$params[1]);
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
	 * @param	pointer		GDlib image pointer
	 * @param	array		TypoScript array with configuration for the GIFBUILDER object.
	 * @return	void
	 * @see tslib_gifBuilder::make()
	 */
	function crop(&$im,$conf)	{
		$this->setWorkArea('');	// clears workArea to total image
		$cords = t3lib_div::intExplode(',',$conf['crop'].',,,');
		$conf['offset']=$cords[0].','.$cords[1];
		$cords = $this->objPosition($conf,$this->workArea,Array($cords[2],$cords[3]));

		$newIm = imagecreate($cords[2],$cords[3]);
		$cols=$this->convertColor($conf['backColor']?$conf['backColor']:$this->setup['backColor']);
		ImageColorAllocate($newIm, $cols[0],$cols[1],$cols[2]);

		$newConf = Array();
		$workArea = Array(0,0,$cords[2],$cords[3]);
		if ($cords[0]<0) {$workArea[0]=abs($cords[0]);} else {$newConf['offset']=-$cords[0];}
		if ($cords[1]<0) {$workArea[1]=abs($cords[1]);} else {$newConf['offset'].=','.-$cords[1];}

		$this->copyGifOntoGif($newIm,$im,$newConf,$workArea);
		$im = $newIm;
		$this->w = imagesx($im);
		$this->h = imagesy($im);
		$this->setWorkArea('');	// clears workArea to total image
	}

	/**
	 * Implements the "SCALE" GIFBUILDER object
	 *
	 * @param	pointer		GDlib image pointer
	 * @param	array		TypoScript array with configuration for the GIFBUILDER object.
	 * @return	void
	 * @see tslib_gifBuilder::make()
	 */
	function scale(&$im,$conf)	{
		if ($conf['width'] || $conf['height'] || $conf['params'])	{
			$tmpStr = $this->randomName();
			$theFile = $tmpStr.'.'.$this->gifExtension;
			$this->ImageGif($im, $theFile);
			$theNewFile = $this->imageMagickConvert($theFile,$this->gifExtension,$conf['width'],$conf['height'],$conf['params'],'','');
			$tmpImg = $this->imageCreateFromGif($theNewFile[3]);
			if ($tmpImg)	{
				ImageDestroy($im);
				$im = $tmpImg;
				$this->w = imagesx($im);
				$this->h = imagesy($im);
				$this->setWorkArea('');	// clears workArea to total image
			}
			if (!$this->dontUnlinkTempFiles)	{
				unlink($theFile);
				if ($theNewFile[3] && $theNewFile[3]!=$theFile)	{
					unlink($theNewFile[3]);
				}
			}
		}
	}

	/**
	 * Implements the "WORKAREA" GIFBUILDER object when setting it
	 * Setting internal working area boundaries (->workArea)
	 *
	 * @param	string		Working area dimensions, comma separated
	 * @return	void
	 * @access private
	 * @see tslib_gifBuilder::make()
	 */
	function setWorkArea($workArea)	{
		$this->workArea = t3lib_div::intExplode(',',$workArea);
		$this->workArea = $this->applyOffset($this->workArea,$this->OFFSET);
		if (!$this->workArea[2])	{$this->workArea[2]=$this->w;}
		if (!$this->workArea[3])	{$this->workArea[3]=$this->h;}
	}























	/*************************
	 *
	 * Adjustment functions
	 *
	 ************************/

	/**
	 * Apply auto-levels to input image pointer
	 *
	 * @param	integer		GDlib Image Pointer
	 * @return	void
	 */
	function autolevels(&$im)	{
		$totalCols = ImageColorsTotal($im);
		$min=255;
		$max=0;
		for ($c=0; $c<$totalCols; $c++)	{
			$cols = ImageColorsForIndex($im,$c);
			$grayArr[] = round(($cols['red']+$cols['green']+$cols['blue'])/3);
		}
		$min=min($grayArr);
		$max=max($grayArr);
		$delta = $max-$min;
		if ($delta)	{
			for ($c=0; $c<$totalCols; $c++)	{
				$cols = ImageColorsForIndex($im,$c);
				$cols['red'] = floor(($cols['red']-$min)/$delta*255);
				$cols['green'] = floor(($cols['green']-$min)/$delta*255);
				$cols['blue'] = floor(($cols['blue']-$min)/$delta*255);
				ImageColorSet($im,$c,$cols['red'],$cols['green'],$cols['blue']);
			}
		}
	}

	/**
	 * Apply output levels to input image pointer (decreasing contrast)
	 *
	 * @param	integer		GDlib Image Pointer
	 * @param	integer		The "low" value (close to 0)
	 * @param	integer		The "high" value (close to 255)
	 * @param	boolean		If swap, then low and high are swapped. (Useful for negated masks...)
	 * @return	void
	 */
	function outputLevels(&$im,$low,$high,$swap='')	{
		if ($low<$high){
			$low = t3lib_div::intInRange($low,0,255);
			$high = t3lib_div::intInRange($high,0,255);

			if ($swap)	{
				$temp = $low;
				$low = 255-$high;
				$high = 255-$temp;
			}

			$delta = $high-$low;
			$totalCols = ImageColorsTotal($im);
			for ($c=0; $c<$totalCols; $c++)	{
				$cols = ImageColorsForIndex($im,$c);
				$cols['red'] = $low+floor($cols['red']/255*$delta);
				$cols['green'] = $low+floor($cols['green']/255*$delta);
				$cols['blue'] = $low+floor($cols['blue']/255*$delta);
				ImageColorSet($im,$c,$cols['red'],$cols['green'],$cols['blue']);
			}
		}
	}

	/**
	 * Apply input levels to input image pointer (increasing contrast)
	 *
	 * @param	integer		GDlib Image Pointer
	 * @param	integer		The "low" value (close to 0)
	 * @param	integer		The "high" value (close to 255)
	 * @param	boolean		If swap, then low and high are swapped. (Useful for negated masks...)
	 * @return	void
	 */
	function inputLevels(&$im,$low,$high,$swap='')	{
		if ($low<$high){
			$low = t3lib_div::intInRange($low,0,255);
			$high = t3lib_div::intInRange($high,0,255);

			if ($swap)	{
				$temp = $low;
				$low = 255-$high;
				$high = 255-$temp;
			}

			$delta = $high-$low;
			$totalCols = ImageColorsTotal($im);
			for ($c=0; $c<$totalCols; $c++)	{
				$cols = ImageColorsForIndex($im,$c);
				$cols['red'] = t3lib_div::intInRange(($cols['red']-$low)/$delta*255, 0,255);
				$cols['green'] = t3lib_div::intInRange(($cols['green']-$low)/$delta*255, 0,255);
				$cols['blue'] = t3lib_div::intInRange(($cols['blue']-$low)/$delta*255, 0,255);
				ImageColorSet($im,$c,$cols['red'],$cols['green'],$cols['blue']);
			}
		}
	}

	/**
	 * Reduce colors in image
	 *
	 * @param	integer		GDlib Image Pointer
	 * @param	integer		The max number of colors in the image before a reduction will happen; basically this means that IF the GD image current has the same amount or more colors than $limit define, THEN a reduction is performed.
	 * @param	integer		Number of colors to reduce the image to.
	 * @return	void
	 */
	function reduceColors(&$im,$limit, $cols)	{
		if (ImageColorsTotal($im)>=$limit)	{
			$this->makeEffect($im, Array('value'=>'colors='.$cols) );
		}
	}



















	/*********************************
	 *
	 * GIFBUILDER Helper functions
	 *
	 *********************************/

	/**
	 * Checks if the $fontFile is already at an absolute path and if not, prepends the correct path.
	 * Use PATH_site unless we are in the backend.
	 * Call it by t3lib_stdGraphic::prependAbsolutePath()
	 *
	 * @param	string		The font file
	 * @return	string		The font file with absolute path.
	 */
	function prependAbsolutePath($fontFile)	{
		$absPath = defined('PATH_typo3') ? dirname(PATH_thisScript).'/' :PATH_site;
		$fontFile = t3lib_div::isAbsPath($fontFile) ? $fontFile : $absPath.$fontFile;
		return $fontFile;
	}

	/**
	 * Returns the IM command for sharpening with ImageMagick 5 (when $this->V5_EFFECTS is set).
	 * Uses $this->im5fx_sharpenSteps for translation of the factor to an actual command.
	 *
	 * @param	integer		The sharpening factor, 0-100 (effectively in 10 steps)
	 * @return	string		The sharpening command, eg. " -sharpen 3x4"
	 * @see makeText(), IMparams(), v5_blur()
	 */
	function v5_sharpen($factor)	{
		$factor = t3lib_div::intInRange(ceil($factor/10),0,10);

		$sharpenArr=explode(',',','.$this->im5fx_sharpenSteps);
		$sharpenF= trim($sharpenArr[$factor]);
		if ($sharpenF)	{
			$cmd = ' -sharpen '.$sharpenF;
			return $cmd;
		}
	}

	/**
	 * Returns the IM command for blurring with ImageMagick 5 (when $this->V5_EFFECTS is set).
	 * Uses $this->im5fx_blurSteps for translation of the factor to an actual command.
	 *
	 * @param	integer		The blurring factor, 0-100 (effectively in 10 steps)
	 * @return	string		The blurring command, eg. " -blur 3x4"
	 * @see makeText(), IMparams(), v5_sharpen()
	 */
	function v5_blur($factor)	{
		$factor = t3lib_div::intInRange(ceil($factor/10),0,10);

		$blurArr=explode(',',','.$this->im5fx_blurSteps);
		$blurF= trim($blurArr[$factor]);
		if ($blurF)	{
			$cmd=' -blur '.$blurF;
			return $cmd;
		}
	}

	/**
	 * Returns a random filename prefixed with "temp_" and then 32 char md5 hash (without extension) from $this->tempPath.
	 * Used by functions in this class to create truely temporary files for the on-the-fly processing. These files will most likely be deleted right away.
	 *
	 * @return	string
	 */
	function randomName()	{
		$this->createTempSubDir('temp/');
		return $this->tempPath.'temp/'.md5(uniqid(''));
	}

	/**
	 * Applies offset value to coordinated in $cords.
	 * Basically the value of key 0/1 of $OFFSET is added to keys 0/1 of $cords
	 *
	 * @param	array		Integer coordinates in key 0/1
	 * @param	array		Offset values in key 0/1
	 * @return	array		Modified $cords array
	 */
	function applyOffset($cords,$OFFSET)	{
		$cords[0] = intval($cords[0])+intval($OFFSET[0]);
		$cords[1] = intval($cords[1])+intval($OFFSET[1]);
		return $cords;
	}

	/**
	 * Converts a "HTML-color" TypoScript datatype to RGB-values.
	 * Default is 0,0,0
	 *
	 * @param	string		"HTML-color" data type string, eg. 'red', '#ffeedd' or '255,0,255'. You can also add a modifying operator afterwards. There are two options: "255,0,255 : 20" - will add 20 to values, result is "255,20,255". Or "255,0,255 : *1.23" which will multiply all RGB values with 1.23
	 * @return	array		RGB values in key 0/1/2 of the array
	 */
	function convertColor($string)	{
		$col=array();
		$cParts = explode(':',$string,2);

			// Finding the RGB definitions of the color:
		$string=$cParts[0];
		if (strstr($string,'#'))	{
			$string = ereg_replace('[^A-Fa-f0-9]*','',$string);
			$col[]=HexDec(substr($string,0,2));
			$col[]=HexDec(substr($string,2,2));
			$col[]=HexDec(substr($string,4,2));
		} elseif (strstr($string,','))	{
			$string = ereg_replace('[^,0-9]*','',$string);
			$strArr = explode(',',$string);
			$col[]=intval($strArr[0]);
			$col[]=intval($strArr[1]);
			$col[]=intval($strArr[2]);
		} else {
			$string = strtolower(trim($string));
			if ($this->colMap[$string])	{
				$col = $this->colMap[$string];
			} else {
				$col = Array(0,0,0);
			}
		}
			// ... and possibly recalculating the value
		if (trim($cParts[1]))	{
			$cParts[1]=trim($cParts[1]);
			if (substr($cParts[1],0,1)=='*')	{
				$val=doubleval(substr($cParts[1],1));
				$col[0]=t3lib_div::intInRange($col[0]*$val,0,255);
				$col[1]=t3lib_div::intInRange($col[1]*$val,0,255);
				$col[2]=t3lib_div::intInRange($col[2]*$val,0,255);
			} else {
				$val=intval($cParts[1]);
				$col[0]=t3lib_div::intInRange($col[0]+$val,0,255);
				$col[1]=t3lib_div::intInRange($col[1]+$val,0,255);
				$col[2]=t3lib_div::intInRange($col[2]+$val,0,255);
			}
		}
		return $col;
	}

	/**
	 * Recode string
	 * Used with text strings for fonts when languages has other character sets.
	 *
	 * @param	string		The text to recode
	 * @return	string		The recoded string. Should be UTF-8 output. MAY contain entities (eg. &#123; or &#quot; which should render as real chars).
	 */
	function recodeString($string)	{
			// Recode string to UTF-8 from $this->nativeCharset:
		if ($this->nativeCharset && $this->nativeCharset!='utf-8')	{
			$string = $this->csConvObj->utf8_encode($string,$this->nativeCharset);	// Convert to UTF-8
		}

			// Recode string accoding to TTFLocaleConv. Depreciated.
		if ($this->TTFLocaleConv)	{
			$string = recode_string($this->TTFLocaleConv,$string);
		}

		return $string;
	}

	/**
	 * Split a string into an array of individual characters
	 * The function will look at  $this->nativeCharset and if that is set, the input string is expected to be UTF-8 encoded, possibly with entities in it. Otherwise the string is supposed to be a single-byte charset which is just splitted by a for-loop.
	 *
	 * @param	string		The text string to spli
	 * @return	array		Numerical array with a char as each value.
	 */
	function singleChars($theText)	{
		if ($this->nativeCharset)	{
			return $this->csConvObj->utf8_to_numberarray($theText,1,1);	// Get an array of separated UTF-8 chars
		} else {
			$output=array();
			$c=strlen($theText);
			for($a=0;$a<$c;$a++)	{
				$output[]=substr($theText,$a,1);
			}
			return $output;
		}
	}

	/**
	 * Create an array with object position/boundaries based on input TypoScript configuration (such as the "align" property is used), the work area definition and $BB array
	 *
	 * @param	array		TypoScript configuration for a GIFBUILDER object
	 * @param	array		Workarea definition
	 * @param	array		BB (Bounding box) array. Not just used for TEXT objects but also for others
	 * @return	array		[0]=x, [1]=y, [2]=w, [3]=h
	 * @access private
	 * @see copyGifOntoGif(), makeBox(), crop()
	 */
	function objPosition($conf,$workArea,$BB) {
			// offset, align, valign, workarea
		$result=Array();
		$result[2] = $BB[0];
		$result[3] = $BB[1];
		$w=$workArea[2];
		$h=$workArea[3];

		$align = explode(',',$conf['align']);
		$align[0] = strtolower(substr(trim($align[0]),0,1));
		$align[1] = strtolower(substr(trim($align[1]),0,1));

		switch($align[0])	{
			case 'r':
				$result[0]=$w-$result[2];
			break;
			case 'c':
				$result[0] = round(($w-$result[2])/2);
			break;
			default:
				$result[0] = 0;
			break;
		}
		switch($align[1])	{
			case 'b':
				$result[1] = $h-$result[3];	// y pos
			break;
			case 'c':
				$result[1] = round(($h-$result[3])/2);
			break;
			default:
				$result[1]=0;
			break;
		}
		$result = $this->applyOffset($result,t3lib_div::intExplode(',',$conf['offset']));
		$result = $this->applyOffset($result,$workArea);
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
	 * @param	string		The image filepath
	 * @param	string		New extension, eg. "gif", "png", "jpg", "tif". If $newExt is NOT set, the new imagefile will be of the original format. If newExt = 'WEB' then one of the web-formats is applied.
	 * @param	string		Width. $w / $h is optional. If only one is given the image is scaled proportionally. If an 'm' exists in the $w or $h and if both are present the $w and $h is regarded as the Maximum w/h and the proportions will be kept
	 * @param	string		Height. See $w
	 * @param	string		Additional ImageMagick parameters.
	 * @param	string		Refers to which frame-number to select in the image. '' or 0 will select the first frame, 1 will select the next and so on...
	 * @param	array		An array with options passed to getImageScale (see this function).
	 * @param	boolean		If set, then another image than the input imagefile MUST be returned. Otherwise you can risk that the input image is good enough regarding messures etc and is of course not rendered to a new, temporary file in typo3temp/. But this option will force it to.
	 * @return	array		[0]/[1] is w/h, [2] is file extension and [3] is the filename.
	 * @see getImageScale(), typo3/show_item.php, fileList_ext::renderImage(), tslib_cObj::getImgResource(), SC_tslib_showpic::show(), maskImageOntoImage(), copyImageOntoImage(), scale()
	 */
	function imageMagickConvert($imagefile,$newExt='',$w='',$h='',$params='',$frame='',$options='',$mustCreate=0)	{
		if ($this->NO_IMAGE_MAGICK)	{
				// Returning file info right away
			return $this->getImageDimensions($imagefile);
		}

		if($info=$this->getImageDimensions($imagefile))	{
			$newExt=strtolower(trim($newExt));
			if (!$newExt)	{	// If no extension is given the original extension is used
				$newExt = $info[2];
			}
			if ($newExt=='web')	{
				if (t3lib_div::inList($this->webImageExt,$info[2]))	{
					$newExt = $info[2];
				} else {
					$newExt = $this->gif_or_jpg($info[2],$info[0],$info[1]);
					if (!$params)	{
						$params = $this->cmds[$newExt];
					}
				}
			}
			if (t3lib_div::inList($this->imageFileExt,$newExt))	{
				if (strstr($w.$h, 'm')) {$max=1;} else {$max=0;}

				$data = $this->getImageScale($info,$w,$h,$options);
				$w=$data['origW'];
				$h=$data['origH'];

					// if no convertion should be performed
				$wh_noscale = (!$w && !$h) || ($data[0]==$info[0] && $data[1]==$info[1]);		// this flag is true if the width / height does NOT dictate the image to be scaled!! (that is if no w/h is given or if the destination w/h matches the original image-dimensions....

				if ($wh_noscale && !$params && !$frame && $newExt==$info[2] && !$mustCreate) {
					$info[3] = $imagefile;
					return $info;
				}
				$info[0]=$data[0];
				$info[1]=$data[1];

				$frame = $this->noFramePrepended ? '' : '['.intval($frame).']';

				if (!$params)	{
					$params = $this->cmds[$newExt];
				}

				$command = $this->scalecmd.' '.$info[0].'x'.$info[1].'! '.$params.' ';

				if ($this->alternativeOutputKey)	{
					$theOutputName = t3lib_div::shortMD5($command.basename($imagefile).$this->alternativeOutputKey.$frame);
				} else {
					$theOutputName = t3lib_div::shortMD5($command.$imagefile.filemtime($imagefile).$frame);
				}
				if ($this->imageMagickConvert_forceFileNameBody)	{
					$theOutputName = $this->imageMagickConvert_forceFileNameBody;
					$this->imageMagickConvert_forceFileNameBody='';
				}

					// Making the temporary filename:
				$this->createTempSubDir('pics/');
				$output = $this->tempPath.'pics/'.$this->filenamePrefix.$theOutputName.'.'.$newExt;

					// Register temporary filename:
				$GLOBALS['TEMP_IMAGES_ON_PAGE'][] = $output;

				if (!$this->file_exists_typo3temp_file($output,$imagefile) || $this->dontCheckForExistingTempFile)	{
					$this->imageMagickExec($imagefile.$frame,$output,$command);
				}
				if (@file_exists($output))	{
					$info[3] = $output;
					$info[2] = $newExt;
					if ($params)	{	// params could realisticly change som imagedata!
						$info=$this->getImageDimensions($info[3]);
					}
					if ($info[2]==$this->gifExtension && !$this->dontCompress)	{
						t3lib_div::gif_compress($info[3],'');		// Compress with IM (lzw) or GD (rle)  (Workaround for the absence of lzw-compression in GD)
					}
					return $info;
				}
			}
		}
	}

	/**
	 * Gets the input image dimensions.
	 *
	 * @param	string		The image filepath
	 * @return	array		Returns an array where [0]/[1] is w/h, [2] is extension and [3] is the filename.
	 * @see imageMagickConvert(), tslib_cObj::getImgResource()
	 */
	function getImageDimensions($imageFile)	{
		ereg('([^\.]*)$',$imageFile,$reg);
		if (@file_exists($imageFile) && t3lib_div::inList($this->imageFileExt,strtolower($reg[0])))	{
			if ($returnArr = $this->getCachedImageDimensions($imageFile))	{
				return $returnArr;
			} else {
				if ($temp = @getImageSize($imageFile))	{
					$returnArr = Array($temp[0], $temp[1], strtolower($reg[0]), $imageFile);
				} else {
					$returnArr = $this->imageMagickIdentify($imageFile);
				}
				if ($returnArr) {
					$this->cacheImageDimensions($returnArr);
					return $returnArr;
				}
			}
		}
		return false;
	}

	/**
	 * Cache the result of the getImageDimensions function into the database. Does not check if the
	 * file exists!
	 *
	 * @param	array		$identifyResult: Result of the getImageDimensions function
	 * @return	boolean		True if operation was successful
	 * @author	Michael Stucki <mundaun@gmx.ch> / Robert Lemke <rl@robertlemke.de>
	 */
	function cacheImageDimensions($identifyResult)	{
		global $TYPO3_DB;
			// Create a md5 hash of the filename
		if (function_exists('md5_file')) {
			$md5Hash = md5_file($identifyResult[3]);
		} else {
			$md5Hash = md5 (t3lib_div::getURL($identifyResult[3]));
		}
		if ($md5Hash) {
			$fieldArr = array (
				'md5hash' => $md5Hash,
				'md5filename' => md5($identifyResult[3]),
				'tstamp' => time(),
				'filename' => $identifyResult[3],
				'imagewidth' => $identifyResult[0],
				'imageheight' => $identifyResult[1],
			);
			$TYPO3_DB->exec_INSERTquery('cache_imagesizes', $fieldArr);
			if (!$err = $TYPO3_DB->sql_error())	{
				return true;
			}
		}
		return false;
	}

	/**
	 * Fetch the cached imageDimensions from the MySQL database. Does not check if the image file exists!
	 *
	 * @param	string		The image filepath
	 * @return	array		Returns an array where [0]/[1] is w/h, [2] is extension and [3] is the filename.
	 * @author	Michael Stucki <mundaun@gmx.ch> / Robert Lemke <rl@robertlemke.de>
	 */
	function getCachedImageDimensions($imageFile)	{
		global $TYPO3_DB;
			// Create a md5 hash of the filename
		if(function_exists('md5_file')) {
			$md5Hash = md5_file($imageFile);
		} else {
			$md5Hash = md5(t3lib_div::getURL ($imageFile));
		}
		ereg('([^\.]*)$',$imageFile,$reg);
		$res = $TYPO3_DB->exec_SELECTquery ('md5hash, imagewidth, imageheight', 'cache_imagesizes', 'md5filename="'.md5($imageFile).'"');
		if ($res) {
			if ($row = $TYPO3_DB->sql_fetch_assoc($res)) {
				if ($row['md5hash']!=$md5Hash) {
						// file has changed, delete the row
					$TYPO3_DB->exec_DELETEquery ('cache_imagesizes', 'md5hash="'.$TYPO3_DB->quoteStr($row['md5hash'], 'cache_imagesizes').'"');
				} else {
					return (array($row['imagewidth'], $row['imageheight'], strtolower($reg[0]), $imageFile));
				}
			}
		}
		return false;
	}

	/**
	 * Get numbers for scaling the image based on input
	 *
	 * @param	array		Current image information: Width, Height etc.
	 * @param	integer		"required" width
	 * @param	integer		"required" height
	 * @param	array		Options: Keys are like "maxW", "maxH", "minW", "minH"
	 * @return	array
	 * @access private
	 * @see imageMagickConvert()
	 */
	function getImageScale($info,$w,$h,$options) {
		if (strstr($w.$h, 'm')) {$max=1;} else {$max=0;}
		$w=intval($w);
		$h=intval($h);
			// if there are max-values...
		if ($options['maxW'])	{
			if ($w) {	// if width is given...
				if ($w>$options['maxW']) {
					$w=$options['maxW'];
					$max=1;	// height should follow
				}
			} else {
				if ($info[0]>$options['maxW']) {
					$w=$options['maxW'];
					$max=1; // height should follow
				}
			}
		}
		if ($options['maxH'])	{
			if ($h) {	// if height is given...
				if ($h>$options['maxH']) {
					$h=$options['maxH'];
					$max=1;	// height should follow
				}
			} else {
				if ($info[1]>$options['maxH']) {	// Changed [0] to [1] 290801
					$h=$options['maxH'];
					$max=1; // height should follow
				}
			}
		}
		$out['origW']=$w;
		$out['origH']=$h;
		$out['max'] = $max;

		if (!$this->mayScaleUp) {
			if ($w>$info[0]){$w=$info[0];}
			if ($h>$info[1]){$h=$info[1];}
		}
		if ($w || $h)	{	// if scaling should be performed
			if ($w && !$h)	{
				$info[1] = ceil($info[1]*($w/$info[0]));
				$info[0] = $w;
			}
			if (!$w && $h)	{
				$info[0] = ceil($info[0]*($h/$info[1]));
				$info[1] = $h;
			}
			if ($w && $h)	{
				if ($max)	{
					$ratio = $info[0]/$info[1];
					if ($h*$ratio > $w) {
						$h = round($w/$ratio);
					} else {
						$w = round($h*$ratio);
					}
				}
				$info[0] = $w;
				$info[1] = $h;
			}
		}
		$out[0]=$info[0];
		$out[1]=$info[1];
			// Set minimum-measures!
		if ($options['minW'] && $out[0]<$options['minW'])	{
			if ($max && $out[0])	{
				$out[1]= round($out[1]*$options['minW']/$out[0]);
			}
			$out[0]=$options['minW'];
		}
		if ($options['minH'] && $out[1]<$options['minH'])	{
			if ($max && $out[1])	{
				$out[0]= round($out[0]*$options['minH']/$out[1]);
			}
			$out[1]=$options['minH'];
		}

		return $out;
	}

	/**
	 * Used to check if a certain process of scaling an image is already being carried out (can be logged in the SQL database)
	 *
	 * @param	string		Output imagefile
	 * @param	string		Original basis file
	 * @return	boolean		Returns true if the file is already being made; thus "true" means "Don't render the image again"
	 * @access private
	 */
	function file_exists_typo3temp_file($output,$orig='')	{
		if ($this->enable_typo3temp_db_tracking)	{
			if (@file_exists($output))	{	// If file exists, then we return immediately
				return 1;
			} else {	// If not, we look up in the cache_typo3temp_log table to see if there is a image being rendered right now.
				$md5Hash=md5($output);
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('md5hash', 'cache_typo3temp_log', 'md5hash="'.$GLOBALS['TYPO3_DB']->quoteStr($md5Hash, 'cache_typo3temp_log').'" AND tstamp>'.(time()-30));
				if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{	// If there was a record, the image is being generated by another proces (we assume)
					if (is_object($GLOBALS['TSFE']))	$GLOBALS['TSFE']->set_no_cache();	// ...so we set no_cache, because we dont want this page (which will NOT display an image...!) to be cached! (Only a page with the correct image on...)
					if (is_object($GLOBALS['TT']))	$GLOBALS['TT']->setTSlogMessage('typo3temp_log: Assume this file is being rendered now: '.$output);
					return 2;	// Return 'success - 2'
				} else {		// If the current time is more than 30 seconds since this record was written, we clear the record, write a new and render the image.

					$insertFields = array(
						'md5hash' => $md5Hash,
						'tstamp' => time(),
						'filename' => $output,
						'orig_filename' => $orig
					);
					$GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_typo3temp_log', 'md5hash="'.$GLOBALS['TYPO3_DB']->quoteStr($md5Hash, 'cache_typo3temp_log').'"');
					$GLOBALS['TYPO3_DB']->exec_INSERTquery('cache_typo3temp_log', $insertFields);

					if (is_object($GLOBALS['TT']))	$GLOBALS['TT']->setTSlogMessage('typo3temp_log: The row did not exist, so a new is written and file is being processed: '.$output);
					return 0;
				}
			}
		} else {
			return @file_exists($output);
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
	 * @param	string		The absolute image filepath
	 * @return	array
	 */
	function imageMagickIdentify($imagefile)	{
		if (!$this->NO_IMAGE_MAGICK)	{
			$frame = $this->noFramePrepended?'':'[0]';
			$cmd = $this->imageMagickPath.'identify '.$this->wrapFileName($imagefile).$frame;
			exec($cmd, $returnVal);
			$splitstring=$returnVal[0];
			$this->IM_commands[] = Array ('identify',$cmd,$returnVal[0]);
			if ($splitstring)	{
				ereg('([^\.]*)$',$imagefile,$reg);
				$splitinfo = explode(' ', $splitstring);
				while (list($key,$val) = each($splitinfo))	{
					$temp = '';
					if ($val) {$temp = explode('x', $val);}
					if (intval($temp[0]) && intval($temp[1]))	{
						$dim=$temp;
						break;
					}
				}
				if ($dim[0] && $dim[1])	{
					return Array($dim[0], $dim[1], strtolower($reg[0]), $imagefile);
				}
			}
		}
	}

	/**
	 * Executes a ImageMagick "convert" on two filenames, $input and $output using $params before them.
	 * Can be used for many things, mostly scaling and effects.
	 *
	 * @param	string		The absolute image filepath, input file (read from)
	 * @param	string		The absolute image filepath, output filename (written to)
	 * @param	string		ImageMagick parameters
	 * @return	string		The result of a call to PHP function "exec()"
	 */
	function imageMagickExec($input,$output,$params)	{
		if (!$this->NO_IMAGE_MAGICK)	{
			$cmd = $this->imageMagickPath.'convert '.$params.' '.$this->wrapFileName($input).' '.$this->wrapFileName($output);
			$this->IM_commands[] = Array ($output,$cmd);
			return exec($cmd);
		}
	}

	/**
	 * Executes a ImageMagick "combine" (or composite in newer times) on four filenames - $input, $overlay and $mask as input files and $output as the output filename (written to)
	 * Can be used for many things, mostly scaling and effects.
	 *
	 * @param	string		The absolute image filepath, bottom file
	 * @param	string		The absolute image filepath, overlay file (top)
	 * @param	string		The absolute image filepath, the mask file (grayscale)
	 * @param	string		The absolute image filepath, output filename (written to)
	 * @return	void
	 */
	function combineExec($input,$overlay,$mask,$output)	{
		if (!$this->NO_IMAGE_MAGICK)	{
			$cmd = $this->imageMagickPath.$this->combineScript.' -compose over '.$this->wrapFileName($input).' '.$this->wrapFileName($overlay).' '.$this->wrapFileName($mask).' '.$this->wrapFileName($output);
			$this->IM_commands[] = Array ($output,$cmd);
			exec($cmd);
		}
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























	/***********************************
	 *
	 * Various IO functions
	 *
	 ***********************************/

	/**
	 * Returns true if the input file existed
	 *
	 * @param	string		Input file to check
	 * @return	string		Returns the filename if the file existed, otherwise empty.
	 */
	function checkFile($file)	{
		if (@is_file($file))	{
			return $file;
		} else {
			return '';
		}
	}

	/**
	 * Creates subdirectory in typo3temp/ if not already found.
	 */
	function createTempSubDir($dirName)	{
			// Making the temporary filename:
		if (!@is_dir(PATH_site.$this->tempPath.$dirName))	 {
			return t3lib_div::mkdir(PATH_site.$this->tempPath.$dirName);
		}
	}

	/**
	 * Applies an ImageMagick parameter to a GDlib image pointer resource by writing the resource to file, performing an IM operation upon it and reading back the result into the ImagePointer.
	 *
	 * @param	pointer		The image pointer (reference)
	 * @param	string		The ImageMagick parameters. Like effects, scaling etc.
	 * @return	void
	 */
	function applyImageMagickToPHPGif(&$im, $command)	{
		$tmpStr = $this->randomName();
		$theFile = $tmpStr.'.'.$this->gifExtension;
		$this->ImageGif($im, $theFile);
		$this->imageMagickExec($theFile,$theFile,$command);
		$tmpImg = $this->imageCreateFromGif($theFile);
		if ($tmpImg)	{
			ImageDestroy($im);
			$im = $tmpImg;
			$this->w = imagesx($im);
			$this->h = imagesy($im);
		}
		if (!$this->dontUnlinkTempFiles)	{
			unlink($theFile);
		}
	}

	/**
	 * Returns an image extension for an output image based on the number of pixels of the output and the file extension of the original file.
	 * For example: If the number of pixels exceeds $this->pixelLimitGif (normally 10000) then it will be a "jpg" string in return.
	 *
	 * @param	string		The file extension, lowercase.
	 * @param	integer		The width of the output image.
	 * @param	integer		The height of the output image.
	 * @return	string		The filename, either "jpg" or "gif"/"png" (whatever $this->gifExtension is set to.)
	 */
	function gif_or_jpg($type,$w,$h)	{
		if ($type=='ai' || $w*$h < $this->pixelLimitGif)	{
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
	 * @param	string		The filename to write to.
	 * @return	string		Returns input filename
	 * @see tslib_gifBuilder::gifBuild()
	 */
	function output($file)	{
		if ($file)	{
			ereg('([^\.]*)$',$file,$reg);
			$ext=strtolower($reg[0]);
			switch($ext)	{
				case $this->gifExtension:
					if ($this->ImageGif($this->im, $file))	{
						// ImageMagick operations
						if($this->setup['reduceColors'])	{
							$this->imageMagickExec($file,$file,' -colors '.t3lib_div::intInRange($this->setup['reduceColors'],2,255));
							t3lib_div::gif_compress($file,'');	// Compress with IM (lzw) or GD (rle)      (Workaround for the absence of lzw-compression in GD)
						} else {
							t3lib_div::gif_compress($file, 'IM');		// Compress with IM! (adds extra compression, LZW from ImageMagick)     (Workaround for the absence of lzw-compression in GD)
						}
					}
				break;
				case 'jpg':
				case 'jpeg':
					$tmpStr = $this->randomName();
					$theFile = $tmpStr.'.'.$this->gifExtension;
					if ($this->ImageGif($this->im, $theFile))	{
						// ImageMagick operations
						$operations='';
						if($this->setup['quality'])	{
							$operations.=' -quality '.t3lib_div::intInRange($this->setup['quality'],10,100);
						}
						$this->imageMagickExec($theFile,$file,$operations);
						if (!$this->dontUnlinkTempFiles)	{
							unlink($theFile);
						}
					}
				break;
			}
			$GLOBALS['TEMP_IMAGES_ON_PAGE'][]=$file;
		}
		return $file;
	}

	/**
	 * Destroy internal image pointer, $this->im
	 *
	 * @return	void
	 * @see tslib_gifBuilder::gifBuild()
	 */
	function destroy()	{
		ImageDestroy($this->im);
	}

	/**
	 * Returns Image Tag for input image information array.
	 *
	 * @param	array		Image information array, key 0/1 is width/height and key 3 is the src value
	 * @return	string		Image tag for the input image information array.
	 */
	function imgTag ($imgInfo) {
		return '<img src="'.$imgInfo[3].'" width="'.$imgInfo[0].'" height="'.$imgInfo[1].'" border="0" alt="" />';
	}

	/**
	 * Writes the input GDlib image pointer to file
	 *
	 * @param	pointer		The GDlib image resource pointer
	 * @param	string		The filename to write to
	 * @return	mixed		The output of either imageGif or imagePng based on whether the $this->gifExtension was set to "gif" or "png"
	 * @see maskImageOntoImage(), scale(), output()
	 */
	function ImageGif($destImg, $theImage)	{
		imageinterlace ($destImg,0);
		if ($this->gifExtension=='gif')	{
			return ImageGif($destImg, $theImage);
		}
		if ($this->gifExtension=='png')	{
			return ImagePng($destImg, $theImage);
		}
	}

	/**
	 * Creates a new GDlib image resource based on the input image filename.
	 * If it fails creating a image from the input file a blank gray image with the dimensions of the input image will be created instead.
	 *
	 * @param	string		Image filename
	 * @return	pointer		Image Resource pointer
	 */
	function imageCreateFromGif($sourceImg)	{
		$imgInf = pathinfo($sourceImg);
		$ext = strtolower($imgInf['extension']);

//		if ($ext=='gif' && (ImageTypes() & IMG_GIF))	{
		if ($ext=='gif' && function_exists('imagecreatefromgif'))	{
			return imageCreateFromGif($sourceImg);
		}
//		if ($ext=='png' && (ImageTypes() & IMG_PNG))	{
		if ($ext=='png' && function_exists('imagecreatefrompng'))	{
			return imageCreateFromPng($sourceImg);
		}
//		if (($ext=='jpg' || $ext=='jpeg') && (ImageTypes() & IMG_JPG))	{
		if (($ext=='jpg' || $ext=='jpeg') && function_exists('imagecreatefromjpeg'))	{
			return imageCreateFromJpeg($sourceImg);
		}

		// If non of the above:
		$i = @getimagesize($sourceImg);
		$im = imagecreate($i[0],$i[1]);
		ImageColorAllocate($im, 128,128,128);
		return $im;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_stdgraphic.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_stdgraphic.php']);
}
?>