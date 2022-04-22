<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Core\Imaging;

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Type\File\ImageInfo;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Standard graphical functions
 *
 * Class contains a bunch of cool functions for manipulating graphics with GDlib/Freetype and ImageMagick.
 * VERY OFTEN used with gifbuilder that extends this class and provides a TypoScript API to using these functions
 */
class GraphicalFunctions
{
    /**
     * If set, the frame pointer is appended to the filenames.
     *
     * @var bool
     */
    public $addFrameSelection = true;

    /**
     * This should be changed to 'png' if you want this class to read/make PNG-files instead!
     *
     * @var string
     */
    public $gifExtension = 'gif';

    /**
     * File formats supported by gdlib. This variable get's filled in "init" method
     *
     * @var array
     */
    protected $gdlibExtensions = [];

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
    protected $allowedColorSpaceNames = [
        'CMY',
        'CMYK',
        'Gray',
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
        'YUV',
    ];

    /**
     * 16777216 Colors is the maximum value for PNG, JPEG truecolor images (24-bit, 8-bit / Channel)
     *
     * @var int
     */
    public $truecolorColors = 16777215;

    /**
     * Allowed file extensions perceived as images by TYPO3.
     * List should be set to 'gif,png,jpeg,jpg' if IM is not available.
     *
     * @var array
     */
    protected $imageFileExt = ['gif', 'jpg', 'jpeg', 'png', 'tif', 'bmp', 'tga', 'pcx', 'ai', 'pdf', 'webp'];

    /**
     * Web image extensions (can be shown by a webbrowser)
     *
     * @var array
     */
    protected $webImageExt = ['gif', 'jpg', 'jpeg', 'png'];

    /**
     * Enable ImageMagick effects, disabled by default as IM5+ effects slow down the image generation
     *
     * @var bool
     */
    protected $processorEffectsEnabled = false;

    /**
     * @var array
     */
    public $cmds = [
        'jpg' => '',
        'jpeg' => '',
        'gif' => '',
        'png' => '',
    ];

    /**
     * Whether ImageMagick/GraphicsMagick is enabled or not
     * @var bool
     */
    protected $processorEnabled;

    /**
     * @var bool
     */
    protected $mayScaleUp = true;

    /**
     * Filename prefix for images scaled in imageMagickConvert()
     *
     * @var string
     */
    public $filenamePrefix = '';

    /**
     * Forcing the output filename of imageMagickConvert() to this value. However after calling imageMagickConvert() it will be set blank again.
     *
     * @var string
     */
    public $imageMagickConvert_forceFileNameBody = '';

    /**
     * This flag should always be FALSE. If set TRUE, imageMagickConvert will always write a new file to the tempdir! Used for debugging.
     *
     * @var bool
     */
    public $dontCheckForExistingTempFile = false;

    /**
     * Prevents imageMagickConvert() from compressing the gif-files with self::gifCompress()
     *
     * @var bool
     */
    public $dontCompress = false;

    /**
     * For debugging only.
     * Filenames will not be based on mtime and only filename (not path) will be used.
     * This key is also included in the hash of the filename...
     *
     * @var string
     */
    public $alternativeOutputKey = '';

    /**
     * All ImageMagick commands executed is stored in this array for tracking. Used by the Install Tools Image section
     *
     * @var array
     */
    public $IM_commands = [];

    /**
     * @var array
     */
    public $workArea = [];

    /**
     * Preserve the alpha transparency layer of read PNG images
     *
     * @var bool
     */
    protected $saveAlphaLayer = false;

    /**
     * ImageMagick scaling command; "-auto-orient -geometry" or "-auto-orient -sample". Used in makeText() and imageMagickConvert()
     *
     * @var string
     */
    public $scalecmd = '-auto-orient -geometry';

    /**
     * Used by v5_blur() to simulate 10 continuous steps of blurring
     *
     * @var string
     */
    protected $im5fx_blurSteps = '1x2,2x2,3x2,4x3,5x3,5x4,6x4,7x5,8x5,9x5';

    /**
     * Used by v5_sharpen() to simulate 10 continuous steps of sharpening.
     *
     * @var string
     */
    protected $im5fx_sharpenSteps = '1x2,2x2,3x2,2x3,3x3,4x3,3x4,4x4,4x5,5x5';

    /**
     * This is the limit for the number of pixels in an image before it will be rendered as JPG instead of GIF/PNG
     *
     * @var int
     */
    protected $pixelLimitGif = 10000;

    /**
     * Array mapping HTML color names to RGB values.
     *
     * @var array
     */
    protected $colMap = [
        'aqua' => [0, 255, 255],
        'black' => [0, 0, 0],
        'blue' => [0, 0, 255],
        'fuchsia' => [255, 0, 255],
        'gray' => [128, 128, 128],
        'green' => [0, 128, 0],
        'lime' => [0, 255, 0],
        'maroon' => [128, 0, 0],
        'navy' => [0, 0, 128],
        'olive' => [128, 128, 0],
        'purple' => [128, 0, 128],
        'red' => [255, 0, 0],
        'silver' => [192, 192, 192],
        'teal' => [0, 128, 128],
        'yellow' => [255, 255, 0],
        'white' => [255, 255, 255],
    ];

    /**
     * Charset conversion object:
     *
     * @var CharsetConverter
     */
    protected $csConvObj;

    /**
     * @var int
     */
    protected $jpegQuality = 85;

    /**
     * @var string
     */
    public $map = '';

    /**
     * This holds the operational setup.
     * Basically this is a TypoScript array with properties.
     *
     * @var array
     */
    public $setup = [];

    /**
     * @var int
     */
    public $w = 0;

    /**
     * @var int
     */
    public $h = 0;

    /**
     * @var array
     */
    protected $OFFSET;

    /**
     * @var resource|\GdImage
     */
    protected $im;

    /**
     * Reads configuration information from $GLOBALS['TYPO3_CONF_VARS']['GFX']
     * and sets some values in internal variables.
     */
    public function __construct()
    {
        $gfxConf = $GLOBALS['TYPO3_CONF_VARS']['GFX'];
        if (function_exists('imagecreatefromjpeg') && function_exists('imagejpeg')) {
            $this->gdlibExtensions[] = 'jpg';
            $this->gdlibExtensions[] = 'jpeg';
        }
        if (function_exists('imagecreatefrompng') && function_exists('imagepng')) {
            $this->gdlibExtensions[] = 'png';
        }
        if (function_exists('imagecreatefromgif') && function_exists('imagegif')) {
            $this->gdlibExtensions[] = 'gif';
        }

        if ($gfxConf['processor_colorspace'] && in_array($gfxConf['processor_colorspace'], $this->allowedColorSpaceNames, true)) {
            $this->colorspace = $gfxConf['processor_colorspace'];
        }

        $this->processorEnabled = (bool)$gfxConf['processor_enabled'];
        // Setting default JPG parameters:
        $this->jpegQuality = MathUtility::forceIntegerInRange($gfxConf['jpg_quality'], 10, 100, 85);
        $this->addFrameSelection = (bool)$gfxConf['processor_allowFrameSelection'];
        if ($gfxConf['gdlib_png']) {
            $this->gifExtension = 'png';
        }
        $this->imageFileExt = GeneralUtility::trimExplode(',', $gfxConf['imagefile_ext']);

        // Boolean. This is necessary if using ImageMagick 5+.
        // Effects in Imagemagick 5+ tends to render very slowly!!
        // - therefore must be disabled in order not to perform sharpen, blurring and such.
        $this->cmds['jpg'] = $this->cmds['jpeg'] = '-colorspace ' . $this->colorspace . ' -quality ' . $this->jpegQuality;

        // ... but if 'processor_effects' is set, enable effects
        if ($gfxConf['processor_effects']) {
            $this->processorEffectsEnabled = true;
            $this->cmds['jpg'] .= $this->v5_sharpen(10);
            $this->cmds['jpeg'] .= $this->v5_sharpen(10);
        }
        // Secures that images are not scaled up.
        $this->mayScaleUp = (bool)$gfxConf['processor_allowUpscaling'];
        $this->csConvObj = GeneralUtility::makeInstance(CharsetConverter::class);
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
     * @param resource $im GDlib image pointer
     * @param array $conf TypoScript array with configuration for the GIFBUILDER object.
     * @param array $workArea The current working area coordinates.
     * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder::make()
     */
    public function maskImageOntoImage(&$im, $conf, $workArea)
    {
        if ($conf['file'] && $conf['mask']) {
            $imgInf = pathinfo($conf['file']);
            $imgExt = strtolower($imgInf['extension']);
            if (!in_array($imgExt, $this->gdlibExtensions, true)) {
                $BBimage = $this->imageMagickConvert($conf['file'], $this->gifExtension);
            } else {
                $BBimage = $this->getImageDimensions($conf['file']);
            }
            $maskInf = pathinfo($conf['mask']);
            $maskExt = strtolower($maskInf['extension']);
            if (!in_array($maskExt, $this->gdlibExtensions, true)) {
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
                    imagesavealpha($destImg, true);
                    $Bcolor = imagecolorallocatealpha($destImg, 0, 0, 0, 127);
                    imagefill($destImg, 0, 0, $Bcolor);
                } else {
                    $Bcolor = imagecolorallocate($destImg, 0, 0, 0);
                    imagefilledrectangle($destImg, 0, 0, $w, $h, $Bcolor);
                }
                $this->copyGifOntoGif($destImg, $cpImg, $conf, $workArea);
                $this->ImageWrite($destImg, $theImage);
                imagedestroy($cpImg);
                imagedestroy($destImg);
                // Prepare mask image
                $cpImg = $this->imageCreateFromFile($BBmask[3]);
                $destImg = imagecreatetruecolor($w, $h);
                if ($this->saveAlphaLayer) {
                    imagesavealpha($destImg, true);
                    $Bcolor = imagecolorallocatealpha($destImg, 0, 0, 0, 127);
                    imagefill($destImg, 0, 0, $Bcolor);
                } else {
                    $Bcolor = imagecolorallocate($destImg, 0, 0, 0);
                    imagefilledrectangle($destImg, 0, 0, $w, $h, $Bcolor);
                }
                $this->copyGifOntoGif($destImg, $cpImg, $conf, $workArea);
                $this->ImageWrite($destImg, $theMask);
                imagedestroy($cpImg);
                imagedestroy($destImg);
                // Mask the images
                $this->ImageWrite($im, $theDest);
                // Let combineExec handle maskNegation
                $this->combineExec($theDest, $theImage, $theMask, $theDest);
                // The main image is loaded again...
                $backIm = $this->imageCreateFromFile($theDest);
                // ... and if nothing went wrong we load it onto the old one.
                if ($backIm) {
                    if (!$this->saveAlphaLayer) {
                        imagecolortransparent($backIm, -1);
                    }
                    $im = $backIm;
                }
                // Unlink files from process
                unlink($theDest);
                unlink($theImage);
                unlink($theMask);
            }
        }
    }

    /**
     * Implements the "IMAGE" GIFBUILDER object, when the "mask" property is FALSE (using only $conf['file'])
     *
     * @param resource $im GDlib image pointer
     * @param array $conf TypoScript array with configuration for the GIFBUILDER object.
     * @param array $workArea The current working area coordinates.
     * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder::make()
     * @see maskImageOntoImage()
     */
    public function copyImageOntoImage(&$im, $conf, $workArea)
    {
        if ($conf['file']) {
            if (!in_array($conf['BBOX'][2], $this->gdlibExtensions, true)) {
                $conf['BBOX'] = $this->imageMagickConvert($conf['BBOX'][3], $this->gifExtension);
                $conf['file'] = $conf['BBOX'][3];
            }
            $cpImg = $this->imageCreateFromFile($conf['file']);
            $this->copyGifOntoGif($im, $cpImg, $conf, $workArea);
            imagedestroy($cpImg);
        }
    }

    /**
     * Copies two GDlib image pointers onto each other, using TypoScript configuration from $conf and the input $workArea definition.
     *
     * @param resource $im GDlib image pointer, destination (bottom image)
     * @param resource $cpImg GDlib image pointer, source (top image)
     * @param array $conf TypoScript array with the properties for the IMAGE GIFBUILDER object. Only used for the "tile" property value.
     * @param array $workArea Work area
     * @internal
     */
    public function copyGifOntoGif(&$im, $cpImg, $conf, $workArea)
    {
        $cpW = imagesx($cpImg);
        $cpH = imagesy($cpImg);
        $tile = GeneralUtility::intExplode(',', $conf['tile'] ?? '');
        $tile[0] = MathUtility::forceIntegerInRange($tile[0], 1, 20);
        $tile[1] = MathUtility::forceIntegerInRange($tile[1] ?? 0, 1, 20);
        $cpOff = $this->objPosition($conf, $workArea, [$cpW * $tile[0], $cpH * $tile[1]]);
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
     * @param int $dstX Destination x-coordinate
     * @param int $dstY Destination y-coordinate
     * @param int $srcX Source x-coordinate
     * @param int $srcY Source y-coordinate
     * @param int $dstWidth Destination width
     * @param int $dstHeight Destination height
     * @param int $srcWidth Source width
     * @param int $srcHeight Source height
     * @internal
     */
    public function imagecopyresized(&$dstImg, $srcImg, $dstX, $dstY, $srcX, $srcY, $dstWidth, $dstHeight, $srcWidth, $srcHeight)
    {
        if (!$this->saveAlphaLayer) {
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
     * @param resource $im GDlib image pointer
     * @param array $conf TypoScript array with configuration for the GIFBUILDER object.
     * @param array $workArea The current working area coordinates.
     * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder::make()
     */
    public function makeText(&$im, $conf, $workArea)
    {
        // Spacing
        [$spacing, $wordSpacing] = $this->calcWordSpacing($conf);
        // Position
        $txtPos = $this->txtPosition($conf, $workArea, $conf['BBOX']);
        $theText = $conf['text'] ?? '';
        if (($conf['imgMap'] ?? false) && is_array($conf['imgMap.'] ?? null)) {
            $this->addToMap($this->calcTextCordsForMap($conf['BBOX'][2], $txtPos, $conf['imgMap.']), $conf['imgMap.']);
        }
        if (!($conf['hideButCreateMap'] ?? false)) {
            // Font Color:
            $cols = $this->convertColor($conf['fontColor']);
            // NiceText is calculated
            if (!($conf['niceText'] ?? false)) {
                $Fcolor = imagecolorallocate($im, $cols[0], $cols[1], $cols[2]);
                // antiAliasing is setup:
                $Fcolor = $conf['antiAlias'] ? $Fcolor : -$Fcolor;
                for ($a = 0; $a < $conf['iterations']; $a++) {
                    // If any kind of spacing applies, we use this function:
                    if ($spacing || $wordSpacing) {
                        $this->SpacedImageTTFText($im, $conf['fontSize'], $conf['angle'] ?? 0, $txtPos[0], $txtPos[1], $Fcolor, GeneralUtility::getFileAbsFileName($conf['fontFile']), $theText, $spacing, $wordSpacing, $conf['splitRendering.']);
                    } else {
                        $this->renderTTFText($im, $conf['fontSize'], $conf['angle'] ?? 0, $txtPos[0], $txtPos[1], $Fcolor, $conf['fontFile'], $theText, $conf['splitRendering.'] ?? [], $conf);
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
                $sF = MathUtility::forceIntegerInRange(($conf['niceText.']['scaleFactor'] ?? 2), 2, 5);
                $newW = (int)ceil($sF * imagesx($im));
                $newH = (int)ceil($sF * imagesy($im));
                // Make mask
                $maskImg = imagecreatetruecolor($newW, $newH);
                $Bcolor = imagecolorallocate($maskImg, 255, 255, 255);
                imagefilledrectangle($maskImg, 0, 0, $newW, $newH, $Bcolor);
                $Fcolor = imagecolorallocate($maskImg, 0, 0, 0);
                // If any kind of spacing applies, we use this function:
                if ($spacing || $wordSpacing) {
                    $this->SpacedImageTTFText($maskImg, $conf['fontSize'], $conf['angle'] ?? 0, $txtPos[0], $txtPos[1], $Fcolor, GeneralUtility::getFileAbsFileName($conf['fontFile']), $theText, $spacing, $wordSpacing, $conf['splitRendering.'], $sF);
                } else {
                    $this->renderTTFText($maskImg, $conf['fontSize'], $conf['angle'] ?? 0, $txtPos[0], $txtPos[1], $Fcolor, $conf['fontFile'], $theText, $conf['splitRendering.'] ?? [], $conf, $sF);
                }
                $this->ImageWrite($maskImg, $fileMask);
                imagedestroy($maskImg);
                // Downscales the mask
                if (!$this->processorEffectsEnabled) {
                    $command = trim($this->scalecmd . ' ' . $w . 'x' . $h . '! -negate');
                } else {
                    $command = trim(($conf['niceText.']['before'] ?? '') . ' ' . $this->scalecmd . ' ' . $w . 'x' . $h . '! ' . ($conf['niceText.']['after'] ?? '') . ' -negate');
                    if (isset($conf['niceText.']['sharpen'])) {
                        $command .= $this->v5_sharpen($conf['niceText.']['sharpen']);
                    }
                }
                $this->imageMagickExec($fileMask, $fileMask, $command);
                // Make the color-file
                $colorImg = imagecreatetruecolor($w, $h);
                $Ccolor = imagecolorallocate($colorImg, $cols[0], $cols[1], $cols[2]);
                imagefilledrectangle($colorImg, 0, 0, $w, $h, $Ccolor);
                $this->ImageWrite($colorImg, $fileColor);
                imagedestroy($colorImg);
                // The mask is applied
                // The main pictures is saved temporarily
                $this->ImageWrite($im, $fileMenu);
                $this->combineExec($fileMenu, $fileColor, $fileMask, $fileMenu);
                // The main image is loaded again...
                $backIm = $this->imageCreateFromFile($fileMenu);
                // ... and if nothing went wrong we load it onto the old one.
                if ($backIm) {
                    if (!$this->saveAlphaLayer) {
                        imagecolortransparent($backIm, -1);
                    }
                    $im = $backIm;
                }
                // Deleting temporary files;
                unlink($fileMenu);
                unlink($fileColor);
                unlink($fileMask);
            }
        }
    }

    /**
     * Calculates text position for printing the text onto the image based on configuration like alignment and workarea.
     *
     * @param array $conf TypoScript array for the TEXT GIFBUILDER object
     * @param array $workArea Work area definition
     * @param array $BB Bounding box information, was set in \TYPO3\CMS\Frontend\Imaging\GifBuilder::start()
     * @return array [0]=x, [1]=y, [2]=w, [3]=h
     * @internal
     * @see makeText()
     */
    public function txtPosition($conf, $workArea, $BB)
    {
        $angle = (int)($conf['angle'] ?? 0) / 180 * M_PI;
        $conf['angle'] = 0;
        $straightBB = $this->calcBBox($conf);
        // offset, align, valign, workarea
        // [0]=x, [1]=y, [2]=w, [3]=h
        $result = [];
        $result[2] = $BB[0];
        $result[3] = $BB[1];
        $w = $workArea[2];
        $alignment = $conf['align'] ?? '';
        switch ($alignment) {
            case 'right':

            case 'center':
                $factor = abs(cos($angle));
                $sign = cos($angle) < 0 ? -1 : 1;
                $len1 = $sign * $factor * $straightBB[0];
                $len2 = $sign * $BB[0];
                $result[0] = $w - ceil($len2 * $factor + (1 - $factor) * $len1);
                $factor = abs(sin($angle));
                $sign = sin($angle) < 0 ? -1 : 1;
                $len1 = $sign * $factor * $straightBB[0];
                $len2 = $sign * $BB[1];
                $result[1] = ceil($len2 * $factor + (1 - $factor) * $len1);
                break;
        }
        switch ($alignment) {
            case 'right':
                break;
            case 'center':
                $result[0] = round($result[0] / 2);
                $result[1] = round($result[1] / 2);
                break;
            default:
                $result[0] = 0;
                $result[1] = 0;
        }
        $result = $this->applyOffset($result, GeneralUtility::intExplode(',', $conf['offset'] ?? ''));
        $result = $this->applyOffset($result, $workArea);
        return $result;
    }

    /**
     * Calculates bounding box information for the TEXT GIFBUILDER object.
     *
     * @param array $conf TypoScript array for the TEXT GIFBUILDER object
     * @return array Array with three keys [0]/[1] being x/y and [2] being the bounding box array
     * @internal
     * @see txtPosition()
     * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder::start()
     */
    public function calcBBox($conf)
    {
        $sF = $this->getTextScalFactor($conf);
        [$spacing, $wordSpacing] = $this->calcWordSpacing($conf, $sF);
        $theText = $conf['text'];
        $charInf = $this->ImageTTFBBoxWrapper($conf['fontSize'], $conf['angle'] ?? 0, $conf['fontFile'], $theText, ($conf['splitRendering.'] ?? []), $sF);
        $theBBoxInfo = $charInf;
        if ($conf['angle'] ?? false) {
            $xArr = [$charInf[0], $charInf[2], $charInf[4], $charInf[6]];
            $yArr = [$charInf[1], $charInf[3], $charInf[5], $charInf[7]];
            $x = max($xArr) - min($xArr);
            $y = max($yArr) - min($yArr);
        } else {
            $x = $charInf[2] - $charInf[0];
            $y = $charInf[1] - $charInf[7];
        }
        // Set original lineHeight (used by line breaks):
        $theBBoxInfo['lineHeight'] = $y;
        if (!empty($conf['lineHeight'])) {
            $theBBoxInfo['lineHeight'] = (int)$conf['lineHeight'];
        }

        if ($spacing) {
            $x = 0;
            $utf8Chars = $this->csConvObj->utf8_to_numberarray($theText);
            // For each UTF-8 char, do:
            foreach ($utf8Chars as $char) {
                $charInf = $this->ImageTTFBBoxWrapper($conf['fontSize'], $conf['angle'], $conf['fontFile'], $char, $conf['splitRendering.'], $sF);
                $charW = $charInf[2] - $charInf[0];
                $x += $charW + ($char === ' ' ? $wordSpacing : $spacing);
            }
        } elseif ($wordSpacing) {
            $x = 0;
            $bits = explode(' ', $theText);
            foreach ($bits as $word) {
                $word .= ' ';
                $wordInf = $this->ImageTTFBBoxWrapper($conf['fontSize'], $conf['angle'], $conf['fontFile'], $word, $conf['splitRendering.'], $sF);
                $wordW = $wordInf[2] - $wordInf[0];
                $x += $wordW + $wordSpacing;
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
        return [$x, $y, $theBBoxInfo];
    }

    /**
     * Adds an <area> tag to the internal variable $this->map which is used to accumulate the content for an ImageMap
     *
     * @param array $cords Coordinates for a polygon image map as created by ->calcTextCordsForMap()
     * @param array $conf Configuration for "imgMap." property of a TEXT GIFBUILDER object.
     * @internal
     * @see makeText()
     * @see calcTextCordsForMap()
     */
    public function addToMap($cords, $conf)
    {
        $this->map .= '<area shape="poly" coords="' . implode(',', $cords) . '"'
            . ' href="' . htmlspecialchars($conf['url']) . '"'
            . ($conf['target'] ? ' target="' . htmlspecialchars($conf['target']) . '"' : '')
            . ((string)$conf['titleText'] !== '' ? ' title="' . htmlspecialchars($conf['titleText']) . '"' : '')
            . ' alt="' . htmlspecialchars($conf['altText']) . '" />';
    }

    /**
     * Calculating the coordinates for a TEXT string on an image map. Used in an <area> tag
     *
     * @param array $cords Coordinates (from BBOX array)
     * @param array $offset Offset array
     * @param array $conf Configuration for "imgMap." property of a TEXT GIFBUILDER object.
     * @return array
     * @internal
     * @see makeText()
     * @see calcTextCordsForMap()
     */
    public function calcTextCordsForMap($cords, $offset, $conf)
    {
        $newCords = [];
        $pars = GeneralUtility::intExplode(',', $conf['explode'] . ',');
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
     * @param resource $im (See argument for PHP function imageTTFtext())
     * @param int $fontSize (See argument for PHP function imageTTFtext())
     * @param int $angle (See argument for PHP function imageTTFtext())
     * @param int $x (See argument for PHP function imageTTFtext())
     * @param int $y (See argument for PHP function imageTTFtext())
     * @param int $Fcolor (See argument for PHP function imageTTFtext())
     * @param string $fontFile (See argument for PHP function imageTTFtext())
     * @param string $text (See argument for PHP function imageTTFtext()). UTF-8 string, possibly with entities in.
     * @param int $spacing The spacing of letters in pixels
     * @param int $wordSpacing The spacing of words in pixels
     * @param array $splitRenderingConf Array
     * @param int $sF Scale factor
     * @internal
     */
    public function SpacedImageTTFText(&$im, $fontSize, $angle, $x, $y, $Fcolor, $fontFile, $text, $spacing, $wordSpacing, $splitRenderingConf, $sF = 1)
    {
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
            $utf8Chars = $this->csConvObj->utf8_to_numberarray($text);
            // For each UTF-8 char, do:
            foreach ($utf8Chars as $char) {
                $charInf = $this->ImageTTFBBoxWrapper($fontSize, $angle, $fontFile, $char, $splitRenderingConf, $sF);
                $charW = $charInf[2] - $charInf[0];
                $this->ImageTTFTextWrapper($im, $fontSize, $angle, $x, $y, $Fcolor, $fontFile, $char, $splitRenderingConf, $sF);
                $x += $charW + ($char === ' ' ? $wordSpacing : $spacing);
            }
        }
    }

    /**
     * Function that finds the right fontsize that will render the textstring within a certain width
     *
     * @param array $conf The TypoScript properties of the TEXT GIFBUILDER object
     * @return int The new fontSize
     * @internal
     * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder::start()
     */
    public function fontResize($conf)
    {
        // You have to use +calc options like [10.h] in 'offset' to get the right position of your text-image, if you use +calc in XY height!!!!
        $maxWidth = (int)$conf['maxWidth'];
        [$spacing, $wordSpacing] = $this->calcWordSpacing($conf);
        if ($maxWidth) {
            // If any kind of spacing applys, we use this function:
            if ($spacing || $wordSpacing) {
                return $conf['fontSize'];
            }
            do {
                // Determine bounding box.
                $bounds = $this->ImageTTFBBoxWrapper($conf['fontSize'], $conf['angle'], $conf['fontFile'], $conf['text'], $conf['splitRendering.']);
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
                }
                $conf['fontSize']--;
            } while ($conf['fontSize'] > 1);
        }
        return $conf['fontSize'];
    }

    /**
     * Wrapper for ImageTTFBBox
     *
     * @param int $fontSize (See argument for PHP function ImageTTFBBox())
     * @param int $angle (See argument for PHP function ImageTTFBBox())
     * @param string $fontFile (See argument for PHP function ImageTTFBBox())
     * @param string $string (See argument for PHP function ImageTTFBBox())
     * @param array $splitRendering Split-rendering configuration
     * @param int $sF Scale factor
     * @return array Information array.
     */
    public function ImageTTFBBoxWrapper($fontSize, $angle, $fontFile, $string, $splitRendering, $sF = 1)
    {
        // Initialize:
        $offsetInfo = [];
        $stringParts = $this->splitString($string, $splitRendering, $fontSize, $fontFile);
        // Traverse string parts:
        foreach ($stringParts as $strCfg) {
            $fontFile = GeneralUtility::getFileAbsFileName($strCfg['fontFile']);
            if (is_readable($fontFile)) {
                // Calculate Bounding Box for part.
                $calc = imagettfbbox($this->compensateFontSizeiBasedOnFreetypeDpi($sF * $strCfg['fontSize']), $angle, $fontFile, $strCfg['str']);
                // Calculate offsets:
                if (empty($offsetInfo)) {
                    // First run, just copy over.
                    $offsetInfo = $calc;
                } else {
                    $offsetInfo[2] += $calc[2] - $calc[0] + (int)$splitRendering['compX'] + (int)$strCfg['xSpaceBefore'] + (int)$strCfg['xSpaceAfter'];
                    $offsetInfo[3] += $calc[3] - $calc[1] - (int)$splitRendering['compY'] - (int)$strCfg['ySpaceBefore'] - (int)$strCfg['ySpaceAfter'];
                    $offsetInfo[4] += $calc[4] - $calc[6] + (int)$splitRendering['compX'] + (int)$strCfg['xSpaceBefore'] + (int)$strCfg['xSpaceAfter'];
                    $offsetInfo[5] += $calc[5] - $calc[7] - (int)$splitRendering['compY'] - (int)$strCfg['ySpaceBefore'] - (int)$strCfg['ySpaceAfter'];
                }
            } else {
                debug('cannot read file: ' . $fontFile, self::class . '::ImageTTFBBoxWrapper()');
            }
        }
        return $offsetInfo;
    }

    /**
     * Wrapper for ImageTTFText
     *
     * @param resource $im (See argument for PHP function imageTTFtext())
     * @param int $fontSize (See argument for PHP function imageTTFtext())
     * @param int $angle (See argument for PHP function imageTTFtext())
     * @param int $x (See argument for PHP function imageTTFtext())
     * @param int $y (See argument for PHP function imageTTFtext())
     * @param int $color (See argument for PHP function imageTTFtext())
     * @param string $fontFile (See argument for PHP function imageTTFtext())
     * @param string $string (See argument for PHP function imageTTFtext()). UTF-8 string, possibly with entities in.
     * @param array $splitRendering Split-rendering configuration
     * @param int $sF Scale factor
     */
    public function ImageTTFTextWrapper($im, $fontSize, $angle, $x, $y, $color, $fontFile, $string, $splitRendering, $sF = 1)
    {
        // Initialize:
        $stringParts = $this->splitString($string, $splitRendering, $fontSize, $fontFile);
        $x = (int)ceil($sF * $x);
        $y = (int)ceil($sF * $y);
        // Traverse string parts:
        foreach ($stringParts as $i => $strCfg) {
            // Initialize:
            $colorIndex = $color;
            // Set custom color if any (only when niceText is off):
            if (($strCfg['color'] ?? false) && $sF == 1) {
                $cols = $this->convertColor($strCfg['color']);
                $colorIndex = imagecolorallocate($im, $cols[0], $cols[1], $cols[2]);
                $colorIndex = $color >= 0 ? $colorIndex : -$colorIndex;
            }
            // Setting xSpaceBefore
            if ($i) {
                $x += (int)$strCfg['xSpaceBefore'];
                $y -= (int)$strCfg['ySpaceBefore'];
            }
            $fontFile = GeneralUtility::getFileAbsFileName($strCfg['fontFile']);
            if (is_readable($fontFile)) {
                // Render part:
                imagettftext($im, $this->compensateFontSizeiBasedOnFreetypeDpi($sF * $strCfg['fontSize']), $angle, $x, $y, $colorIndex, $fontFile, $strCfg['str']);
                // Calculate offset to apply:
                $wordInf = imagettfbbox($this->compensateFontSizeiBasedOnFreetypeDpi($sF * $strCfg['fontSize']), $angle, GeneralUtility::getFileAbsFileName($strCfg['fontFile']), $strCfg['str']);
                $x += $wordInf[2] - $wordInf[0] + (int)($splitRendering['compX'] ?? 0) + (int)($strCfg['xSpaceAfter'] ?? 0);
                $y += $wordInf[5] - $wordInf[7] - (int)($splitRendering['compY'] ?? 0) - (int)($strCfg['ySpaceAfter'] ?? 0);
            } else {
                debug('cannot read file: ' . $fontFile, self::class . '::ImageTTFTextWrapper()');
            }
        }
    }

    /**
     * Splitting a string for ImageTTFBBox up into an array where each part has its own configuration options.
     *
     * @param string $string UTF-8 string
     * @param array $splitRendering Split-rendering configuration from GIFBUILDER TEXT object.
     * @param int $fontSize Current fontsize
     * @param string $fontFile Current font file
     * @return array Array with input string splitted according to configuration
     */
    public function splitString($string, $splitRendering, $fontSize, $fontFile)
    {
        // Initialize by setting the whole string and default configuration as the first entry.
        $result = [];
        $result[] = [
            'str' => $string,
            'fontSize' => $fontSize,
            'fontFile' => $fontFile,
        ];
        // Traverse the split-rendering configuration:
        // Splitting will create more entries in $result with individual configurations.
        if (is_array($splitRendering)) {
            $sKeyArray = ArrayUtility::filterAndSortByNumericKeys($splitRendering);
            // Traverse configured options:
            foreach ($sKeyArray as $key) {
                $cfg = $splitRendering[$key . '.'];
                // Process each type of split rendering keyword:
                switch ((string)$splitRendering[$key]) {
                    case 'highlightWord':
                        if ((string)$cfg['value'] !== '') {
                            $newResult = [];
                            // Traverse the current parts of the result array:
                            foreach ($result as $part) {
                                // Explode the string value by the word value to highlight:
                                $explodedParts = explode($cfg['value'], $part['str']);
                                foreach ($explodedParts as $c => $expValue) {
                                    if ((string)$expValue !== '') {
                                        $newResult[] = array_merge($part, ['str' => $expValue]);
                                    }
                                    if ($c + 1 < count($explodedParts)) {
                                        $newResult[] = [
                                            'str' => $cfg['value'],
                                            'fontSize' => $cfg['fontSize'] ?: $part['fontSize'],
                                            'fontFile' => $cfg['fontFile'] ?: $part['fontFile'],
                                            'color' => $cfg['color'],
                                            'xSpaceBefore' => $cfg['xSpaceBefore'],
                                            'xSpaceAfter' => $cfg['xSpaceAfter'],
                                            'ySpaceBefore' => $cfg['ySpaceBefore'],
                                            'ySpaceAfter' => $cfg['ySpaceAfter'],
                                        ];
                                    }
                                }
                            }
                            // Set the new result as result array:
                            if (!empty($newResult)) {
                                $result = $newResult;
                            }
                        }
                        break;
                    case 'charRange':
                        if ((string)$cfg['value'] !== '') {
                            // Initialize range:
                            $ranges = GeneralUtility::trimExplode(',', $cfg['value'], true);
                            foreach ($ranges as $i => $rangeDef) {
                                $ranges[$i] = GeneralUtility::intExplode('-', (string)$ranges[$i]);
                                if (!isset($ranges[$i][1])) {
                                    $ranges[$i][1] = $ranges[$i][0];
                                }
                            }
                            $newResult = [];
                            // Traverse the current parts of the result array:
                            foreach ($result as $part) {
                                // Initialize:
                                $currentState = -1;
                                $bankAccum = '';
                                // Explode the string value by the word value to highlight:
                                $utf8Chars = $this->csConvObj->utf8_to_numberarray($part['str']);
                                foreach ($utf8Chars as $utfChar) {
                                    // Find number and evaluate position:
                                    $uNumber = (int)$this->csConvObj->utf8CharToUnumber($utfChar);
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
                                    if ($inRange != $currentState && $uNumber !== 9 && $uNumber !== 10 && $uNumber !== 13 && $uNumber !== 32) {
                                        // Set result:
                                        if ($bankAccum !== '') {
                                            $newResult[] = [
                                                'str' => $bankAccum,
                                                'fontSize' => $currentState && $cfg['fontSize'] ? $cfg['fontSize'] : $part['fontSize'],
                                                'fontFile' => $currentState && $cfg['fontFile'] ? $cfg['fontFile'] : $part['fontFile'],
                                                'color' => $currentState ? $cfg['color'] : '',
                                                'xSpaceBefore' => $currentState ? $cfg['xSpaceBefore'] : '',
                                                'xSpaceAfter' => $currentState ? $cfg['xSpaceAfter'] : '',
                                                'ySpaceBefore' => $currentState ? $cfg['ySpaceBefore'] : '',
                                                'ySpaceAfter' => $currentState ? $cfg['ySpaceAfter'] : '',
                                            ];
                                        }
                                        // Initialize new settings:
                                        $currentState = $inRange;
                                        $bankAccum = '';
                                    }
                                    // Add char to bank:
                                    $bankAccum .= $utfChar;
                                }
                                // Set result for FINAL part:
                                if ($bankAccum !== '') {
                                    $newResult[] = [
                                        'str' => $bankAccum,
                                        'fontSize' => $currentState && $cfg['fontSize'] ? $cfg['fontSize'] : $part['fontSize'],
                                        'fontFile' => $currentState && $cfg['fontFile'] ? $cfg['fontFile'] : $part['fontFile'],
                                        'color' => $currentState ? $cfg['color'] : '',
                                        'xSpaceBefore' => $currentState ? $cfg['xSpaceBefore'] : '',
                                        'xSpaceAfter' => $currentState ? $cfg['xSpaceAfter'] : '',
                                        'ySpaceBefore' => $currentState ? $cfg['ySpaceBefore'] : '',
                                        'ySpaceAfter' => $currentState ? $cfg['ySpaceAfter'] : '',
                                    ];
                                }
                            }
                            // Set the new result as result array:
                            if (!empty($newResult)) {
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
     * @param int $scaleFactor TypoScript value from eg $conf['niceText.']['scaleFactor']
     * @return array Array with two keys [0]/[1] being array($spacing,$wordSpacing)
     * @internal
     * @see calcBBox()
     */
    public function calcWordSpacing($conf, $scaleFactor = 1)
    {
        $spacing = (int)($conf['spacing'] ?? 0);
        $wordSpacing = (int)($conf['wordSpacing'] ?? 0);
        $wordSpacing = $wordSpacing ?: $spacing * 2;
        $spacing *= $scaleFactor;
        $wordSpacing *= $scaleFactor;
        return [$spacing, $wordSpacing];
    }

    /**
     * Calculates and returns the niceText.scaleFactor
     *
     * @param array $conf TypoScript array for the TEXT GIFBUILDER object
     * @return int TypoScript value from eg $conf['niceText.']['scaleFactor']
     * @internal
     */
    public function getTextScalFactor($conf)
    {
        if (!($conf['niceText'] ?? false)) {
            $sF = 1;
        } else {
            // NICETEXT::
            $sF = MathUtility::forceIntegerInRange(($conf['niceText.']['scaleFactor'] ?? 2), 2, 5);
        }
        return $sF;
    }

    /**
     * @param array $imageFileExt
     * @internal Only used for ext:install, not part of TYPO3 Core API.
     */
    public function setImageFileExt(array $imageFileExt): void
    {
        $this->imageFileExt = $imageFileExt;
    }

    /**
     * Renders a regular text and takes care of a possible line break automatically.
     *
     * @param resource $im (See argument for PHP function imageTTFtext())
     * @param int $fontSize (See argument for PHP function imageTTFtext())
     * @param int $angle (See argument for PHP function imageTTFtext())
     * @param int $x (See argument for PHP function imageTTFtext())
     * @param int $y (See argument for PHP function imageTTFtext())
     * @param int $color (See argument for PHP function imageTTFtext())
     * @param string $fontFile (See argument for PHP function imageTTFtext())
     * @param string $string (See argument for PHP function imageTTFtext()). UTF-8 string, possibly with entities in.
     * @param array $splitRendering Split-rendering configuration
     * @param array $conf The configuration
     * @param int $sF Scale factor
     */
    protected function renderTTFText(&$im, $fontSize, $angle, $x, $y, $color, $fontFile, $string, $splitRendering, $conf, $sF = 1)
    {
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
    protected function getWordPairsForLineBreak($string)
    {
        $wordPairs = [];
        $wordsArray = preg_split('#([- .,!:]+)#', $string, -1, PREG_SPLIT_DELIM_CAPTURE);
        $wordsArray = is_array($wordsArray) ? $wordsArray : [];
        $wordsCount = count($wordsArray);
        for ($index = 0; $index < $wordsCount; $index += 2) {
            $wordPairs[] = $wordsArray[$index] . $wordsArray[$index + 1];
        }
        return $wordPairs;
    }

    /**
     * Gets the rendered text width
     *
     * @param string $text
     * @param array $conf
     * @return int
     */
    protected function getRenderedTextWidth($text, $conf)
    {
        $bounds = $this->ImageTTFBBoxWrapper($conf['fontSize'], $conf['angle'], $conf['fontFile'], $text, $conf['splitRendering.']);
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
     * @return int The break space
     */
    protected function getBreakSpace($conf, array $boundingBox = null)
    {
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
     * @param resource $im GDlib image pointer
     * @param array $conf TypoScript array with configuration for the GIFBUILDER object.
     * @param array $workArea The current working area coordinates.
     * @param array $txtConf TypoScript array with configuration for the associated TEXT GIFBUILDER object.
     * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder::make()
     * @see makeText()
     */
    public function makeOutline(&$im, $conf, $workArea, $txtConf)
    {
        $thickness = (int)$conf['thickness'];
        if ($thickness) {
            $txtConf['fontColor'] = $conf['color'];
            $outLineDist = MathUtility::forceIntegerInRange($thickness, 1, 2);
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
     * @param int $distance Distance
     * @param int $iterations Iterations.
     * @return array
     * @see makeOutline()
     */
    public function circleOffset($distance, $iterations)
    {
        $res = [];
        if ($distance && $iterations) {
            for ($a = 0; $a < $iterations; $a++) {
                $yOff = round(sin(2 * M_PI / $iterations * ($a + 1)) * 100 * $distance);
                if ($yOff) {
                    $yOff = (int)(ceil(abs($yOff / 100)) * ($yOff / abs($yOff)));
                }
                $xOff = round(cos(2 * M_PI / $iterations * ($a + 1)) * 100 * $distance);
                if ($xOff) {
                    $xOff = (int)(ceil(abs($xOff / 100)) * ($xOff / abs($xOff)));
                }
                $res[$a] = [$xOff, $yOff];
            }
        }
        return $res;
    }

    /**
     * Implements the "EMBOSS" GIFBUILDER object / property for the TEXT object
     *
     * @param resource $im GDlib image pointer
     * @param array $conf TypoScript array with configuration for the GIFBUILDER object.
     * @param array $workArea The current working area coordinates.
     * @param array $txtConf TypoScript array with configuration for the associated TEXT GIFBUILDER object.
     * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder::make()
     * @see makeShadow()
     */
    public function makeEmboss(&$im, $conf, $workArea, $txtConf)
    {
        $conf['color'] = $conf['highColor'];
        $this->makeShadow($im, $conf, $workArea, $txtConf);
        $newOffset = GeneralUtility::intExplode(',', $conf['offset']);
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
     * @param resource $im GDlib image pointer
     * @param array $conf TypoScript array with configuration for the GIFBUILDER object.
     * @param array $workArea The current working area coordinates.
     * @param array $txtConf TypoScript array with configuration for the associated TEXT GIFBUILDER object.
     * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder::make()
     * @see makeText()
     * @see makeEmboss()
     */
    public function makeShadow(&$im, $conf, $workArea, $txtConf)
    {
        $workArea = $this->applyOffset($workArea, GeneralUtility::intExplode(',', $conf['offset']));
        $blurRate = MathUtility::forceIntegerInRange((int)$conf['blur'], 0, 99);
        // No effects if ImageMagick ver. 5+
        if (!$blurRate || !$this->processorEffectsEnabled) {
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
            $Bcolor = imagecolorallocate($blurColImg, $bcols[0], $bcols[1], $bcols[2]);
            imagefilledrectangle($blurColImg, 0, 0, $w, $h, $Bcolor);
            $this->ImageWrite($blurColImg, $fileColor);
            imagedestroy($blurColImg);
            // The mask is made: BlurTextImage
            $blurTextImg = imagecreatetruecolor($w + $blurBorder * 2, $h + $blurBorder * 2);
            // Black background
            $Bcolor = imagecolorallocate($blurTextImg, 0, 0, 0);
            imagefilledrectangle($blurTextImg, 0, 0, $w + $blurBorder * 2, $h + $blurBorder * 2, $Bcolor);
            $txtConf['fontColor'] = 'white';
            $blurBordArr = [$blurBorder, $blurBorder];
            $this->makeText($blurTextImg, $txtConf, $this->applyOffset($workArea, $blurBordArr));
            // Dump to temporary file
            $this->ImageWrite($blurTextImg, $fileMask);
            // Destroy
            imagedestroy($blurTextImg);
            $command = $this->v5_blur($blurRate + 1);
            $this->imageMagickExec($fileMask, $fileMask, $command . ' +matte');
            // The mask is loaded again
            $blurTextImg_tmp = $this->imageCreateFromFile($fileMask);
            // If nothing went wrong we continue with the blurred mask
            if ($blurTextImg_tmp) {
                // Cropping the border from the mask
                $blurTextImg = imagecreatetruecolor($w, $h);
                $this->imagecopyresized($blurTextImg, $blurTextImg_tmp, 0, 0, $blurBorder, $blurBorder, $w, $h, $w, $h);
                // Destroy the temporary mask
                imagedestroy($blurTextImg_tmp);
                // Adjust the mask
                $intensity = 40;
                if ($conf['intensity'] ?? false) {
                    $intensity = MathUtility::forceIntegerInRange($conf['intensity'], 0, 100);
                }
                $intensity = (int)ceil(255 - $intensity / 100 * 255);
                $this->inputLevels($blurTextImg, 0, $intensity);
                $opacity = MathUtility::forceIntegerInRange((int)$conf['opacity'], 0, 100);
                if ($opacity && $opacity < 100) {
                    $high = (int)ceil(255 * $opacity / 100);
                    // Reducing levels as the opacity demands
                    $this->outputLevels($blurTextImg, 0, $high);
                }
                // Dump the mask again
                $this->ImageWrite($blurTextImg, $fileMask);
                // Destroy the mask
                imagedestroy($blurTextImg);
                // The pictures are combined
                // The main pictures is saved temporarily
                $this->ImageWrite($im, $fileMenu);
                $this->combineExec($fileMenu, $fileColor, $fileMask, $fileMenu);
                // The main image is loaded again...
                $backIm = $this->imageCreateFromFile($fileMenu);
                // ... and if nothing went wrong we load it onto the old one.
                if ($backIm) {
                    if (!$this->saveAlphaLayer) {
                        imagecolortransparent($backIm, -1);
                    }
                    $im = $backIm;
                }
            }
            // Deleting temporary files;
            unlink($fileMenu);
            unlink($fileColor);
            unlink($fileMask);
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
     * @param resource $im GDlib image pointer
     * @param array $conf TypoScript array with configuration for the GIFBUILDER object.
     * @param array $workArea The current working area coordinates.
     * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder::make()
     */
    public function makeBox(&$im, $conf, $workArea)
    {
        $cords = GeneralUtility::intExplode(',', $conf['dimensions'] . ',,,');
        $conf['offset'] = $cords[0] . ',' . $cords[1];
        $cords = $this->objPosition($conf, $workArea, [$cords[2], $cords[3]]);
        $cols = $this->convertColor($conf['color'] ?? '');
        $opacity = 0;
        if (isset($conf['opacity'])) {
            // conversion:
            // PHP 0 = opaque, 127 = transparent
            // TYPO3 100 = opaque, 0 = transparent
            $opacity = MathUtility::forceIntegerInRange((int)$conf['opacity'], 1, 100, 1);
            $opacity = (int)abs($opacity - 100);
            $opacity = (int)round(127 * $opacity / 100);
        }
        $tmpColor = imagecolorallocatealpha($im, $cols[0], $cols[1], $cols[2], $opacity);
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
     * @param resource $im GDlib image pointer
     * @param array $conf TypoScript array with configuration for the GIFBUILDER object.
     * @param array $workArea The current working area coordinates.
     * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder::make()
     */
    public function makeEllipse(&$im, array $conf, array $workArea)
    {
        $ellipseConfiguration = GeneralUtility::intExplode(',', $conf['dimensions'] . ',,,');
        // Ellipse offset inside workArea (x/y)
        $conf['offset'] = $ellipseConfiguration[0] . ',' . $ellipseConfiguration[1];
        // @see objPosition
        $imageCoordinates = $this->objPosition($conf, $workArea, [$ellipseConfiguration[2], $ellipseConfiguration[3]]);
        $color = $this->convertColor($conf['color'] ?? '');
        $fillingColor = imagecolorallocate($im, $color[0], $color[1], $color[2]);
        imagefilledellipse($im, $imageCoordinates[0], $imageCoordinates[1], $imageCoordinates[2], $imageCoordinates[3], $fillingColor);
    }

    /**
     * Implements the "EFFECT" GIFBUILDER object
     * The operation involves ImageMagick for applying effects
     *
     * @param resource $im GDlib image pointer
     * @param array $conf TypoScript array with configuration for the GIFBUILDER object.
     * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder::make()
     * @see applyImageMagickToPHPGif()
     */
    public function makeEffect(&$im, $conf)
    {
        $commands = $this->IMparams($conf['value']);
        if ($commands) {
            $this->applyImageMagickToPHPGif($im, $commands);
        }
    }

    /**
     * Creating ImageMagick parameters from TypoScript property
     *
     * @param string $setup A string with effect keywords=value pairs separated by "|
     * @return string ImageMagick prepared parameters.
     * @internal
     * @see makeEffect()
     */
    public function IMparams($setup)
    {
        if (!trim($setup)) {
            return '';
        }
        $effects = explode('|', $setup);
        $commands = '';
        foreach ($effects as $val) {
            $pairs = explode('=', $val, 2);
            $value = trim($pairs[1] ?? '');
            $effect = strtolower(trim($pairs[0]));
            switch ($effect) {
                case 'gamma':
                    $commands .= ' -gamma ' . (float)$value;
                    break;
                case 'blur':
                    if ($this->processorEffectsEnabled) {
                        $commands .= $this->v5_blur((int)$value);
                    }
                    break;
                case 'sharpen':
                    if ($this->processorEffectsEnabled) {
                        $commands .= $this->v5_sharpen((int)$value);
                    }
                    break;
                case 'rotate':
                    $commands .= ' -rotate ' . MathUtility::forceIntegerInRange((int)$value, 0, 360);
                    break;
                case 'solarize':
                    $commands .= ' -solarize ' . MathUtility::forceIntegerInRange((int)$value, 0, 99);
                    break;
                case 'swirl':
                    $commands .= ' -swirl ' . MathUtility::forceIntegerInRange((int)$value, 0, 1000);
                    break;
                case 'wave':
                    $params = GeneralUtility::intExplode(',', $value);
                    $commands .= ' -wave ' . MathUtility::forceIntegerInRange($params[0], 0, 99) . 'x' . MathUtility::forceIntegerInRange($params[1], 0, 99);
                    break;
                case 'charcoal':
                    $commands .= ' -charcoal ' . MathUtility::forceIntegerInRange((int)$value, 0, 100);
                    break;
                case 'gray':
                    $commands .= ' -colorspace GRAY';
                    break;
                case 'edge':
                    $commands .= ' -edge ' . MathUtility::forceIntegerInRange((int)$value, 0, 99);
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
                    $commands .= ' -colors ' . MathUtility::forceIntegerInRange((int)$value, 2, 255);
                    break;
                case 'shear':
                    $commands .= ' -shear ' . MathUtility::forceIntegerInRange((int)$value, -90, 90);
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
     * @param resource $im GDlib image pointer
     * @param array $conf TypoScript array with configuration for the GIFBUILDER object.
     * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder::make()
     * @see autoLevels()
     * @see outputLevels()
     * @see inputLevels()
     */
    public function adjust(&$im, $conf)
    {
        $setup = $conf['value'];
        if (!trim($setup)) {
            return;
        }
        $effects = explode('|', $setup);
        foreach ($effects as $val) {
            $pairs = explode('=', $val, 2);
            $value = trim($pairs[1]);
            $effect = strtolower(trim($pairs[0]));
            switch ($effect) {
                case 'inputlevels':
                    // low,high
                    $params = GeneralUtility::intExplode(',', $value);
                    $this->inputLevels($im, $params[0], $params[1]);
                    break;
                case 'outputlevels':
                    $params = GeneralUtility::intExplode(',', $value);
                    $this->outputLevels($im, $params[0], $params[1]);
                    break;
                case 'autolevels':
                    $this->autolevels($im);
                    break;
            }
        }
    }

    /**
     * Implements the "CROP" GIFBUILDER object
     *
     * @param resource $im GDlib image pointer
     * @param array $conf TypoScript array with configuration for the GIFBUILDER object.
     * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder::make()
     */
    public function crop(&$im, $conf)
    {
        // Clears workArea to total image
        $this->setWorkArea('');
        $cords = GeneralUtility::intExplode(',', $conf['crop'] . ',,,');
        $conf['offset'] = $cords[0] . ',' . $cords[1];
        $cords = $this->objPosition($conf, $this->workArea, [$cords[2], $cords[3]]);
        $newIm = imagecreatetruecolor($cords[2], $cords[3]);
        $cols = $this->convertColor(!empty($conf['backColor']) ? $conf['backColor'] : $this->setup['backColor']);
        $Bcolor = imagecolorallocate($newIm, $cols[0], $cols[1], $cols[2]);
        imagefilledrectangle($newIm, 0, 0, $cords[2], $cords[3], $Bcolor);
        $newConf = [];
        $workArea = [0, 0, $cords[2], $cords[3]];
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
     * @param resource $im GDlib image pointer
     * @param array $conf TypoScript array with configuration for the GIFBUILDER object.
     * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder::make()
     */
    public function scale(&$im, $conf)
    {
        if ($conf['width'] || $conf['height'] || $conf['params']) {
            $tmpStr = $this->randomName();
            $theFile = $tmpStr . '.' . $this->gifExtension;
            $this->ImageWrite($im, $theFile);
            $theNewFile = $this->imageMagickConvert($theFile, $this->gifExtension, $conf['width'] ?? '', $conf['height'] ?? '', $conf['params'] ?? '');
            $tmpImg = $this->imageCreateFromFile($theNewFile[3]);
            if ($tmpImg) {
                imagedestroy($im);
                $im = $tmpImg;
                $this->w = imagesx($im);
                $this->h = imagesy($im);
                // Clears workArea to total image
                $this->setWorkArea('');
            }
            unlink($theFile);
            if ($theNewFile[3] && $theNewFile[3] != $theFile) {
                unlink($theNewFile[3]);
            }
        }
    }

    /**
     * Implements the "WORKAREA" GIFBUILDER object when setting it
     * Setting internal working area boundaries (->workArea)
     *
     * @param string $workArea Working area dimensions, comma separated
     * @internal
     * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder::make()
     */
    public function setWorkArea($workArea)
    {
        $this->workArea = GeneralUtility::intExplode(',', $workArea);
        $this->workArea = $this->applyOffset($this->workArea, $this->OFFSET);
        if (!($this->workArea[2] ?? false)) {
            $this->workArea[2] = $this->w;
        }
        if (!($this->workArea[3] ?? false)) {
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
     * @param resource $im GDlib Image Pointer
     */
    public function autolevels(&$im)
    {
        $totalCols = imagecolorstotal($im);
        $grayArr = [];
        for ($c = 0; $c < $totalCols; $c++) {
            $cols = imagecolorsforindex($im, $c);
            $grayArr[] = round(($cols['red'] + $cols['green'] + $cols['blue']) / 3);
        }
        $min = min($grayArr);
        $max = max($grayArr);
        $delta = $max - $min;
        if ($delta) {
            for ($c = 0; $c < $totalCols; $c++) {
                $cols = imagecolorsforindex($im, $c);
                $cols['red'] = floor(($cols['red'] - $min) / $delta * 255);
                $cols['green'] = floor(($cols['green'] - $min) / $delta * 255);
                $cols['blue'] = floor(($cols['blue'] - $min) / $delta * 255);
                imagecolorset($im, $c, $cols['red'], $cols['green'], $cols['blue']);
            }
        }
    }

    /**
     * Apply output levels to input image pointer (decreasing contrast)
     *
     * @param resource $im GDlib Image Pointer
     * @param int $low The "low" value (close to 0)
     * @param int $high The "high" value (close to 255)
     * @param bool $swap If swap, then low and high are swapped. (Useful for negated masks...)
     */
    public function outputLevels(&$im, $low, $high, $swap = false)
    {
        if ($low < $high) {
            $low = MathUtility::forceIntegerInRange($low, 0, 255);
            $high = MathUtility::forceIntegerInRange($high, 0, 255);
            if ($swap) {
                $temp = $low;
                $low = 255 - $high;
                $high = 255 - $temp;
            }
            $delta = $high - $low;
            $totalCols = imagecolorstotal($im);
            for ($c = 0; $c < $totalCols; $c++) {
                $cols = imagecolorsforindex($im, $c);
                $cols['red'] = $low + floor($cols['red'] / 255 * $delta);
                $cols['green'] = $low + floor($cols['green'] / 255 * $delta);
                $cols['blue'] = $low + floor($cols['blue'] / 255 * $delta);
                imagecolorset($im, $c, $cols['red'], $cols['green'], $cols['blue']);
            }
        }
    }

    /**
     * Apply input levels to input image pointer (increasing contrast)
     *
     * @param resource $im GDlib Image Pointer
     * @param int $low The "low" value (close to 0)
     * @param int $high The "high" value (close to 255)
     */
    public function inputLevels(&$im, $low, $high)
    {
        if ($low < $high) {
            $low = MathUtility::forceIntegerInRange($low, 0, 255);
            $high = MathUtility::forceIntegerInRange($high, 0, 255);
            $delta = $high - $low;
            $totalCols = imagecolorstotal($im);
            for ($c = 0; $c < $totalCols; $c++) {
                $cols = imagecolorsforindex($im, $c);
                $cols['red'] = MathUtility::forceIntegerInRange((int)(($cols['red'] - $low) / $delta * 255), 0, 255);
                $cols['green'] = MathUtility::forceIntegerInRange((int)(($cols['green'] - $low) / $delta * 255), 0, 255);
                $cols['blue'] = MathUtility::forceIntegerInRange((int)(($cols['blue'] - $low) / $delta * 255), 0, 255);
                imagecolorset($im, $c, $cols['red'], $cols['green'], $cols['blue']);
            }
        }
    }

    /**
     * Reduce colors in image using IM and create a palette based image if possible (<=256 colors)
     *
     * @param string $file Image file to reduce
     * @param int $cols Number of colors to reduce the image to.
     * @return string Reduced file
     */
    public function IMreduceColors($file, $cols)
    {
        $fI = GeneralUtility::split_fileref($file);
        $ext = strtolower($fI['fileext']);
        $result = $this->randomName() . '.' . $ext;
        $reduce = MathUtility::forceIntegerInRange($cols, 0, $ext === 'gif' ? 256 : $this->truecolorColors, 0);
        if ($reduce > 0) {
            $params = ' -colors ' . $reduce;
            if ($reduce <= 256) {
                $params .= ' -type Palette';
            }
            $prefix = $ext === 'png' && $reduce <= 256 ? 'png8:' : '';
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
     * Returns the IM command for sharpening with ImageMagick 5
     * Uses $this->im5fx_sharpenSteps for translation of the factor to an actual command.
     *
     * @param int $factor The sharpening factor, 0-100 (effectively in 10 steps)
     * @return string The sharpening command, eg. " -sharpen 3x4
     * @see makeText()
     * @see IMparams()
     * @see v5_blur()
     */
    public function v5_sharpen($factor)
    {
        $factor = MathUtility::forceIntegerInRange((int)ceil($factor / 10), 0, 10);
        $sharpenArr = explode(',', ',' . $this->im5fx_sharpenSteps);
        $sharpenF = trim($sharpenArr[$factor]);
        if ($sharpenF) {
            return ' -sharpen ' . $sharpenF;
        }
        return '';
    }

    /**
     * Returns the IM command for blurring with ImageMagick 5.
     * Uses $this->im5fx_blurSteps for translation of the factor to an actual command.
     *
     * @param int $factor The blurring factor, 0-100 (effectively in 10 steps)
     * @return string The blurring command, eg. " -blur 3x4
     * @see makeText()
     * @see IMparams()
     * @see v5_sharpen()
     */
    public function v5_blur($factor)
    {
        $factor = MathUtility::forceIntegerInRange((int)ceil($factor / 10), 0, 10);
        $blurArr = explode(',', ',' . $this->im5fx_blurSteps);
        $blurF = trim($blurArr[$factor]);
        if ($blurF) {
            return ' -blur ' . $blurF;
        }
        return '';
    }

    /**
     * Returns a random filename prefixed with "temp_" and then 32 char md5 hash (without extension).
     * Used by functions in this class to create truly temporary files for the on-the-fly processing. These files will most likely be deleted right away.
     *
     * @return string
     */
    public function randomName()
    {
        GeneralUtility::mkdir_deep(Environment::getVarPath() . '/transient/');
        return Environment::getVarPath() . '/transient/' . md5(StringUtility::getUniqueId());
    }

    /**
     * Applies offset value to coordinated in $cords.
     * Basically the value of key 0/1 of $OFFSET is added to keys 0/1 of $cords
     *
     * @param array $cords Integer coordinates in key 0/1
     * @param array $OFFSET Offset values in key 0/1
     * @return array Modified $cords array
     */
    public function applyOffset($cords, $OFFSET)
    {
        $cords[0] = (int)$cords[0] + (int)$OFFSET[0];
        $cords[1] = (int)($cords[1] ?? 0) + (int)($OFFSET[1] ?? 0);
        return $cords;
    }

    /**
     * Converts a "HTML-color" TypoScript datatype to RGB-values.
     * Default is 0,0,0
     *
     * @param string $string "HTML-color" data type string, eg. 'red', '#ffeedd' or '255,0,255'. You can also add a modifying operator afterwards. There are two options: "255,0,255 : 20" - will add 20 to values, result is "255,20,255". Or "255,0,255 : *1.23" which will multiply all RGB values with 1.23
     * @return array RGB values in key 0/1/2 of the array
     */
    public function convertColor($string)
    {
        $col = [];
        $cParts = explode(':', $string, 2);
        // Finding the RGB definitions of the color:
        $string = $cParts[0];
        if (str_contains($string, '#')) {
            $string = preg_replace('/[^A-Fa-f0-9]*/', '', $string) ?? '';
            $col[] = hexdec(substr($string, 0, 2));
            $col[] = hexdec(substr($string, 2, 2));
            $col[] = hexdec(substr($string, 4, 2));
        } elseif (str_contains($string, ',')) {
            $string = preg_replace('/[^,0-9]*/', '', $string) ?? '';
            $strArr = explode(',', $string);
            $col[] = (int)$strArr[0];
            $col[] = (int)$strArr[1];
            $col[] = (int)$strArr[2];
        } else {
            $string = strtolower(trim($string));
            if ($this->colMap[$string] ?? false) {
                $col = $this->colMap[$string];
            } else {
                $col = [0, 0, 0];
            }
        }
        // ... and possibly recalculating the value
        if (trim($cParts[1] ?? '')) {
            $cParts[1] = trim($cParts[1]);
            if ($cParts[1][0] === '*') {
                $val = (float)substr($cParts[1], 1);
                $col[0] = MathUtility::forceIntegerInRange((int)($col[0] * $val), 0, 255);
                $col[1] = MathUtility::forceIntegerInRange((int)($col[1] * $val), 0, 255);
                $col[2] = MathUtility::forceIntegerInRange((int)($col[2] * $val), 0, 255);
            } else {
                $val = (int)$cParts[1];
                $col[0] = MathUtility::forceIntegerInRange((int)($col[0] + $val), 0, 255);
                $col[1] = MathUtility::forceIntegerInRange((int)($col[1] + $val), 0, 255);
                $col[2] = MathUtility::forceIntegerInRange((int)($col[2] + $val), 0, 255);
            }
        }
        return $col;
    }

    /**
     * Create an array with object position/boundaries based on input TypoScript configuration (such as the "align" property is used), the work area definition and $BB array
     *
     * @param array $conf TypoScript configuration for a GIFBUILDER object
     * @param array $workArea Workarea definition
     * @param array $BB BB (Bounding box) array. Not just used for TEXT objects but also for others
     * @return array [0]=x, [1]=y, [2]=w, [3]=h
     * @internal
     * @see copyGifOntoGif()
     * @see makeBox()
     * @see crop()
     */
    public function objPosition($conf, $workArea, $BB)
    {
        // offset, align, valign, workarea
        $result = [];
        $result[2] = $BB[0];
        $result[3] = $BB[1];
        $w = $workArea[2];
        $h = $workArea[3];
        $align = explode(',', $conf['align'] ?? ',');
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
        }
        $result = $this->applyOffset($result, GeneralUtility::intExplode(',', $conf['offset'] ?? ''));
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
     * @param bool $mustCreate If set, then another image than the input imagefile MUST be returned. Otherwise you can risk that the input image is good enough regarding measures etc and is of course not rendered to a new, temporary file in typo3temp/. But this option will force it to.
     * @return array|null [0]/[1] is w/h, [2] is file extension and [3] is the filename.
     * @see getImageScale()
     * @see \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::getImgResource()
     * @see maskImageOntoImage()
     * @see copyImageOntoImage()
     * @see scale()
     */
    public function imageMagickConvert($imagefile, $newExt = '', $w = '', $h = '', $params = '', $frame = '', $options = [], $mustCreate = false)
    {
        if (!$this->processorEnabled) {
            // Returning file info right away
            return $this->getImageDimensions($imagefile);
        }
        $info = $this->getImageDimensions($imagefile);
        if (!$info) {
            return null;
        }

        $newExt = strtolower(trim($newExt));
        // If no extension is given the original extension is used
        if (!$newExt) {
            $newExt = $info[2];
        }
        if ($newExt === 'web') {
            if (in_array($info[2], $this->webImageExt, true)) {
                $newExt = $info[2];
            } else {
                $newExt = $this->gif_or_jpg($info[2], $info[0], $info[1]);
                if (!$params) {
                    $params = $this->cmds[$newExt];
                }
            }
        }
        if (!in_array($newExt, $this->imageFileExt, true)) {
            return null;
        }

        $data = $this->getImageScale($info, $w, $h, $options);
        $w = $data['origW'];
        $h = $data['origH'];
        // If no conversion should be performed
        // this flag is TRUE if the width / height does NOT dictate
        // the image to be scaled!! (that is if no width / height is
        // given or if the destination w/h matches the original image
        // dimensions or if the option to not scale the image is set)
        $noScale = !$w && !$h || $data[0] == $info[0] && $data[1] == $info[1] || !empty($options['noScale']);
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
        $frame = $this->addFrameSelection ? (int)$frame : 0;
        if (!$params) {
            $params = $this->cmds[$newExt] ?? '';
        }
        // Cropscaling:
        if ($data['crs']) {
            if (!$data['origW']) {
                $data['origW'] = $data[0];
            }
            if (!$data['origH']) {
                $data['origH'] = $data[1];
            }
            $offsetX = (int)(($data[0] - $data['origW']) * ($data['cropH'] + 100) / 200);
            $offsetY = (int)(($data[1] - $data['origH']) * ($data['cropV'] + 100) / 200);
            $params .= ' -crop ' . $data['origW'] . 'x' . $data['origH'] . '+' . $offsetX . '+' . $offsetY . '! +repage';
        }
        $command = $this->scalecmd . ' ' . $info[0] . 'x' . $info[1] . '! ' . $params . ' ';
        // re-apply colorspace-setting for the resulting image so colors don't appear to dark (sRGB instead of RGB)
        $command .= ' -colorspace ' . $this->colorspace;
        $cropscale = $data['crs'] ? 'crs-V' . $data['cropV'] . 'H' . $data['cropH'] : '';
        if ($this->alternativeOutputKey) {
            $theOutputName = md5($command . $cropscale . PathUtility::basename($imagefile) . $this->alternativeOutputKey . '[' . $frame . ']');
        } else {
            $theOutputName = md5($command . $cropscale . $imagefile . filemtime($imagefile) . '[' . $frame . ']');
        }
        if ($this->imageMagickConvert_forceFileNameBody) {
            $theOutputName = $this->imageMagickConvert_forceFileNameBody;
            $this->imageMagickConvert_forceFileNameBody = '';
        }
        // Making the temporary filename
        GeneralUtility::mkdir_deep(Environment::getPublicPath() . '/typo3temp/assets/images/');
        $output = Environment::getPublicPath() . '/typo3temp/assets/images/' . $this->filenamePrefix . $theOutputName . '.' . $newExt;
        if ($this->dontCheckForExistingTempFile || !file_exists($output)) {
            $this->imageMagickExec($imagefile, $output, $command, $frame);
        }
        if (file_exists($output)) {
            $info[3] = $output;
            $info[2] = $newExt;
            // params might change some image data!
            if ($params) {
                $info = $this->getImageDimensions($info[3]);
            }
            if ($info[2] == $this->gifExtension && !$this->dontCompress) {
                // Compress with IM (lzw) or GD (rle)  (Workaround for the absence of lzw-compression in GD)
                self::gifCompress($info[3], '');
            }
            return $info;
        }
        return null;
    }

    /**
     * Gets the input image dimensions.
     *
     * @param string $imageFile The image filepath
     * @return array|null Returns an array where [0]/[1] is w/h, [2] is extension and [3] is the filename.
     * @see imageMagickConvert()
     * @see \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::getImgResource()
     */
    public function getImageDimensions($imageFile)
    {
        $returnArr = null;
        preg_match('/([^\\.]*)$/', $imageFile, $reg);
        if (file_exists($imageFile) && in_array(strtolower($reg[0]), $this->imageFileExt, true)) {
            $returnArr = $this->getCachedImageDimensions($imageFile);
            if (!$returnArr) {
                $imageInfoObject = GeneralUtility::makeInstance(ImageInfo::class, $imageFile);
                if ($imageInfoObject->getWidth()) {
                    $returnArr = [
                        $imageInfoObject->getWidth(),
                        $imageInfoObject->getHeight(),
                        strtolower($reg[0]),
                        $imageFile,
                    ];
                    $this->cacheImageDimensions($returnArr);
                }
            }
        }
        return $returnArr;
    }

    /**
     * Caches the result of the getImageDimensions function into the database. Does not check if the file exists.
     *
     * @param array $identifyResult Result of the getImageDimensions function
     *
     * @return bool always TRUE
     */
    public function cacheImageDimensions(array $identifyResult)
    {
        $filePath = $identifyResult[3];
        $statusHash = $this->generateStatusHashForImageFile($filePath);
        $identifier = $this->generateCacheKeyForImageFile($filePath);

        $cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('imagesizes');
        $imageDimensions = [
            'hash'        => $statusHash,
            'imagewidth'  => $identifyResult[0],
            'imageheight' => $identifyResult[1],
        ];
        $cache->set($identifier, $imageDimensions);

        return true;
    }

    /**
     * Fetches the cached image dimensions from the cache. Does not check if the image file exists.
     *
     * @param string $filePath Image file path, relative to public web path
     *
     * @return array|bool an array where [0]/[1] is w/h, [2] is extension and [3] is the file name,
     *                    or FALSE for a cache miss
     */
    public function getCachedImageDimensions($filePath)
    {
        $statusHash = $this->generateStatusHashForImageFile($filePath);
        $identifier = $this->generateCacheKeyForImageFile($filePath);
        $cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('imagesizes');
        $cachedImageDimensions = $cache->get($identifier);
        if (!isset($cachedImageDimensions['hash'])) {
            return false;
        }

        if ($cachedImageDimensions['hash'] !== $statusHash) {
            // The file has changed. Delete the cache entry.
            $cache->remove($identifier);
            $result = false;
        } else {
            preg_match('/([^\\.]*)$/', $filePath, $imageExtension);
            $result = [
                (int)$cachedImageDimensions['imagewidth'],
                (int)$cachedImageDimensions['imageheight'],
                strtolower($imageExtension[0]),
                $filePath,
            ];
        }

        return $result;
    }

    /**
     * Creates the key for the image dimensions cache for an image file.
     *
     * This method does not check if the image file actually exists.
     *
     * @param string $filePath Image file path, relative to public web path
     *
     * @return string the hash key (an SHA1 hash), will not be empty
     */
    protected function generateCacheKeyForImageFile($filePath)
    {
        return sha1($filePath);
    }

    /**
     * Creates the status hash to check whether a file has been changed.
     *
     * @param string $filePath Image file path, relative to public web path
     *
     * @return string the status hash (an SHA1 hash)
     */
    protected function generateStatusHashForImageFile($filePath)
    {
        $fileStatus = stat($filePath);

        return sha1($fileStatus['mtime'] . $fileStatus['size']);
    }

    /**
     * Get numbers for scaling the image based on input
     *
     * @param array $info Current image information: Width, Height etc.
     * @param string $w "required" width
     * @param string $h "required" height
     * @param array $options Options: Keys are like "maxW", "maxH", "minW", "minH
     * @return array
     * @internal
     * @see imageMagickConvert()
     */
    public function getImageScale($info, $w, $h, $options)
    {
        $out = [];
        $max = str_contains($w . $h, 'm') ? 1 : 0;
        if (str_contains($w . $h, 'c')) {
            $out['cropH'] = (int)substr((string)strstr($w, 'c'), 1);
            $out['cropV'] = (int)substr((string)strstr($h, 'c'), 1);
            $crs = true;
        } else {
            $crs = false;
        }
        $out['crs'] = $crs;
        $w = (int)$w;
        $h = (int)$h;
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
        // If scaling should be performed. Check that input "info" array will not cause division-by-zero
        if (($w || $h) && $info[0] && $info[1]) {
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

    /***********************************
     *
     * ImageMagick API functions
     *
     ***********************************/
    /**
     * Call the identify command
     *
     * @param string $imagefile The relative to public web path image filepath
     * @return array|null Returns an array where [0]/[1] is w/h, [2] is extension, [3] is the filename and [4] the real image type identified by ImageMagick.
     */
    public function imageMagickIdentify($imagefile)
    {
        if (!$this->processorEnabled) {
            return null;
        }

        $result = $this->executeIdentifyCommandForImageFile($imagefile);
        if ($result) {
            [$width, $height, $fileExtension, $fileType] = explode(' ', $result);
            if ((int)$width && (int)$height) {
                return [$width, $height, strtolower($fileExtension), $imagefile, strtolower($fileType)];
            }
        }
        return null;
    }

    /**
     * Internal function to execute an IM command fetching information on an image
     *
     * @param string $imageFile the absolute path to the image
     * @return string|null the raw result of the identify command.
     */
    protected function executeIdentifyCommandForImageFile(string $imageFile): ?string
    {
        $frame = $this->addFrameSelection ? 0 : null;
        $cmd = CommandUtility::imageMagickCommand(
            'identify',
            '-format "%w %h %e %m" ' . ImageMagickFile::fromFilePath($imageFile, $frame)
        );
        $returnVal = [];
        CommandUtility::exec($cmd, $returnVal);
        $result = array_pop($returnVal);
        $this->IM_commands[] = ['identify', $cmd, $result];
        return $result;
    }

    /**
     * Executes an ImageMagick "convert" on two filenames, $input and $output using $params before them.
     * Can be used for many things, mostly scaling and effects.
     *
     * @param string $input The relative to public web path image filepath, input file (read from)
     * @param string $output The relative to public web path image filepath, output filename (written to)
     * @param string $params ImageMagick parameters
     * @param int $frame Optional, refers to which frame-number to select in the image. '' or 0
     * @return string The result of a call to PHP function "exec()
     */
    public function imageMagickExec($input, $output, $params, $frame = 0)
    {
        if (!$this->processorEnabled) {
            return '';
        }
        // If addFrameSelection is set in the Install Tool, a frame number is added to
        // select a specific page of the image (by default this will be the first page)
        $frame = $this->addFrameSelection ? (int)$frame : null;
        $cmd = CommandUtility::imageMagickCommand(
            'convert',
            $params
                . ' ' . ImageMagickFile::fromFilePath($input, $frame)
                . ' ' . CommandUtility::escapeShellArgument($output)
        );
        $this->IM_commands[] = [$output, $cmd];
        $ret = CommandUtility::exec($cmd);
        // Change the permissions of the file
        GeneralUtility::fixPermissions($output);
        return $ret;
    }

    /**
     * Executes an ImageMagick "combine" (or composite in newer times) on four filenames - $input, $overlay and $mask as input files and $output as the output filename (written to)
     * Can be used for many things, mostly scaling and effects.
     *
     * @param string $input The relative to public web path image filepath, bottom file
     * @param string $overlay The relative to public web path image filepath, overlay file (top)
     * @param string $mask The relative to public web path image filepath, the mask file (grayscale)
     * @param string $output The relative to public web path image filepath, output filename (written to)
     * @return string
     */
    public function combineExec($input, $overlay, $mask, $output)
    {
        if (!$this->processorEnabled) {
            return '';
        }
        $theMask = $this->randomName() . '.' . $this->gifExtension;
        // +matte = no alpha layer in output
        $this->imageMagickExec($mask, $theMask, '-colorspace GRAY +matte');

        $parameters = '-compose over'
            . ' -quality ' . $this->jpegQuality
            . ' +matte '
            . ImageMagickFile::fromFilePath($input) . ' '
            . ImageMagickFile::fromFilePath($overlay) . ' '
            . ImageMagickFile::fromFilePath($theMask) . ' '
            . CommandUtility::escapeShellArgument($output);
        $cmd = CommandUtility::imageMagickCommand('combine', $parameters);
        $this->IM_commands[] = [$output, $cmd];
        $ret = CommandUtility::exec($cmd);
        // Change the permissions of the file
        GeneralUtility::fixPermissions($output);
        if (is_file($theMask)) {
            @unlink($theMask);
        }
        return $ret;
    }

    /**
     * Compressing a GIF file if not already LZW compressed.
     * This function is a workaround for the fact that ImageMagick and/or GD does not compress GIF-files to their minimum size (that is RLE or no compression used)
     *
     * The function takes a file-reference, $theFile, and saves it again through GD or ImageMagick in order to compress the file
     * GIF:
     * If $type is not set, the compression is done with ImageMagick (provided that $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_path_lzw'] is pointing to the path of a lzw-enabled version of 'convert') else with GD (should be RLE-enabled!)
     * If $type is set to either 'IM' or 'GD' the compression is done with ImageMagick and GD respectively
     * PNG:
     * No changes.
     *
     * $theFile is expected to be a valid GIF-file!
     * The function returns a code for the operation.
     *
     * @param string $theFile Filepath
     * @param string $type See description of function
     * @return string Returns "GD" if GD was used, otherwise "IM" if ImageMagick was used. If nothing done at all, it returns empty string.
     */
    public static function gifCompress($theFile, $type)
    {
        $gfxConf = $GLOBALS['TYPO3_CONF_VARS']['GFX'];
        if (!$gfxConf['gif_compress'] || strtolower(substr($theFile, -4, 4)) !== '.gif') {
            return '';
        }

        if (($type === 'IM' || !$type) && $gfxConf['processor_enabled'] && $gfxConf['processor_path_lzw']) {
            // Use temporary file to prevent problems with read and write lock on same file on network file systems
            $temporaryName = PathUtility::dirname($theFile) . '/' . md5(StringUtility::getUniqueId()) . '.gif';
            // Rename could fail, if a simultaneous thread is currently working on the same thing
            if (@rename($theFile, $temporaryName)) {
                $cmd = CommandUtility::imageMagickCommand(
                    'convert',
                    ImageMagickFile::fromFilePath($temporaryName) . ' ' . CommandUtility::escapeShellArgument($theFile),
                    $gfxConf['processor_path_lzw']
                );
                CommandUtility::exec($cmd);
                unlink($temporaryName);
            }
            $returnCode = 'IM';
            if (@is_file($theFile)) {
                GeneralUtility::fixPermissions($theFile);
            }
        } elseif (($type === 'GD' || !$type) && $gfxConf['gdlib'] && !$gfxConf['gdlib_png']) {
            $tempImage = imagecreatefromgif($theFile);
            imagegif($tempImage, $theFile);
            imagedestroy($tempImage);
            $returnCode = 'GD';
            if (@is_file($theFile)) {
                GeneralUtility::fixPermissions($theFile);
            }
        } else {
            $returnCode = '';
        }

        return $returnCode;
    }

    /**
     * Returns filename of the png/gif version of the input file (which can be png or gif).
     * If input file type does not match the wanted output type a conversion is made and temp-filename returned.
     *
     * @param string $theFile Filepath of image file
     * @param bool $output_png If TRUE, then input file is converted to PNG, otherwise to GIF
     * @return string|null If the new image file exists, its filepath is returned
     */
    public static function readPngGif($theFile, $output_png = false)
    {
        if (!$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_enabled'] || !@is_file($theFile)) {
            return null;
        }

        $ext = strtolower(substr($theFile, -4, 4));
        if ((string)$ext === '.png' && $output_png || (string)$ext === '.gif' && !$output_png) {
            return $theFile;
        }

        if (!@is_dir(Environment::getPublicPath() . '/typo3temp/assets/images/')) {
            GeneralUtility::mkdir_deep(Environment::getPublicPath() . '/typo3temp/assets/images/');
        }
        $newFile = Environment::getPublicPath() . '/typo3temp/assets/images/' . md5($theFile . '|' . filemtime($theFile)) . ($output_png ? '.png' : '.gif');
        $cmd = CommandUtility::imageMagickCommand(
            'convert',
            ImageMagickFile::fromFilePath($theFile)
                . ' ' . CommandUtility::escapeShellArgument($newFile),
            $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_path']
        );
        CommandUtility::exec($cmd);
        if (@is_file($newFile)) {
            GeneralUtility::fixPermissions($newFile);
            return $newFile;
        }
        return null;
    }

    /***********************************
     *
     * Various IO functions
     *
     ***********************************/

    /**
     * Applies an ImageMagick parameter to a GDlib image pointer resource by writing the resource to file, performing an IM operation upon it and reading back the result into the ImagePointer.
     *
     * @param resource $im The image pointer (reference)
     * @param string $command The ImageMagick parameters. Like effects, scaling etc.
     */
    public function applyImageMagickToPHPGif(&$im, $command)
    {
        $tmpStr = $this->randomName();
        $theFile = $tmpStr . '.' . $this->gifExtension;
        $this->ImageWrite($im, $theFile);
        $this->imageMagickExec($theFile, $theFile, $command);
        $tmpImg = $this->imageCreateFromFile($theFile);
        if ($tmpImg) {
            imagedestroy($im);
            $im = $tmpImg;
            $this->w = imagesx($im);
            $this->h = imagesy($im);
        }
        unlink($theFile);
    }

    /**
     * Returns an image extension for an output image based on the number of pixels of the output and the file extension of the original file.
     * For example: If the number of pixels exceeds $this->pixelLimitGif (normally 10000) then it will be a "jpg" string in return.
     *
     * @param string $type The file extension, lowercase.
     * @param int $w The width of the output image.
     * @param int $h The height of the output image.
     * @return string The filename, either "jpg" or "gif"/"png" (whatever $this->gifExtension is set to.)
     */
    public function gif_or_jpg($type, $w, $h)
    {
        if ($type === 'ai' || $w * $h < $this->pixelLimitGif) {
            return $this->gifExtension;
        }
        return 'jpg';
    }

    /**
     * Writing the internal image pointer, $this->im, to file based on the extension of the input filename
     * Used in GIFBUILDER
     * Uses $this->setup['reduceColors'] for gif/png images and $this->setup['quality'] for jpg images to reduce size/quality if needed.
     *
     * @param string $file The filename to write to.
     * @return string Returns input filename
     * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder::gifBuild()
     */
    public function output($file)
    {
        if ($file) {
            $reg = [];
            preg_match('/([^\\.]*)$/', $file, $reg);
            $ext = strtolower($reg[0]);
            switch ($ext) {
                case 'gif':
                case 'png':
                    if ($this->ImageWrite($this->im, $file)) {
                        // ImageMagick operations
                        if ($this->setup['reduceColors'] ?? false) {
                            $reduced = $this->IMreduceColors($file, MathUtility::forceIntegerInRange($this->setup['reduceColors'], 256, $this->truecolorColors, 256));
                            if ($reduced) {
                                @copy($reduced, $file);
                                @unlink($reduced);
                            }
                        }
                        // Compress with IM! (adds extra compression, LZW from ImageMagick)
                        // (Workaround for the absence of lzw-compression in GD)
                        self::gifCompress($file, 'IM');
                    }
                    break;
                case 'jpg':
                case 'jpeg':
                    // Use the default
                    $quality = 0;
                    if ($this->setup['quality'] ?? false) {
                        $quality = MathUtility::forceIntegerInRange($this->setup['quality'], 10, 100);
                    }
                    $this->ImageWrite($this->im, $file, $quality);
                    break;
            }
        }
        return $file;
    }

    /**
     * Destroy internal image pointer, $this->im
     *
     * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder::gifBuild()
     */
    public function destroy()
    {
        imagedestroy($this->im);
    }

    /**
     * Returns Image Tag for input image information array.
     *
     * @param array $imgInfo Image information array, key 0/1 is width/height and key 3 is the src value
     * @return string Image tag for the input image information array.
     */
    public function imgTag($imgInfo)
    {
        return '<img src="' . $imgInfo[3] . '" width="' . $imgInfo[0] . '" height="' . $imgInfo[1] . '" border="0" alt="" />';
    }

    /**
     * Writes the input GDlib image pointer to file
     *
     * @param resource $destImg The GDlib image resource pointer
     * @param string $theImage The filename to write to
     * @param int $quality The image quality (for JPEGs)
     * @return bool The output of either imageGif, imagePng or imageJpeg based on the filename to write
     * @see maskImageOntoImage()
     * @see scale()
     * @see output()
     */
    public function ImageWrite($destImg, $theImage, $quality = 0)
    {
        imageinterlace($destImg, 0);
        $ext = strtolower(substr($theImage, (int)strrpos($theImage, '.') + 1));
        $result = false;
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                if (function_exists('imagejpeg')) {
                    if ($quality === 0) {
                        $quality = $this->jpegQuality;
                    }
                    $result = imagejpeg($destImg, $theImage, $quality);
                }
                break;
            case 'gif':
                if (function_exists('imagegif')) {
                    imagetruecolortopalette($destImg, true, 256);
                    $result = imagegif($destImg, $theImage);
                }
                break;
            case 'png':
                if (function_exists('imagepng')) {
                    $result = imagepng($destImg, $theImage);
                }
                break;
        }
        if ($result) {
            GeneralUtility::fixPermissions($theImage);
        }
        return $result;
    }

    /**
     * Creates a new GDlib image resource based on the input image filename.
     * If it fails creating an image from the input file a blank gray image with the dimensions of the input image will be created instead.
     *
     * @param string $sourceImg Image filename
     * @return resource Image Resource pointer
     */
    public function imageCreateFromFile($sourceImg)
    {
        $imgInf = pathinfo($sourceImg);
        $ext = strtolower($imgInf['extension']);
        switch ($ext) {
            case 'gif':
                if (function_exists('imagecreatefromgif')) {
                    return imagecreatefromgif($sourceImg);
                }
                break;
            case 'png':
                if (function_exists('imagecreatefrompng')) {
                    $imageHandle = imagecreatefrompng($sourceImg);
                    if ($this->saveAlphaLayer) {
                        imagesavealpha($imageHandle, true);
                    }
                    return $imageHandle;
                }
                break;
            case 'jpg':
            case 'jpeg':
                if (function_exists('imagecreatefromjpeg')) {
                    return imagecreatefromjpeg($sourceImg);
                }
                break;
        }
        // If non of the above:
        $imageInfo = GeneralUtility::makeInstance(ImageInfo::class, $sourceImg);
        $im = imagecreatetruecolor($imageInfo->getWidth(), $imageInfo->getHeight());
        $Bcolor = imagecolorallocate($im, 128, 128, 128);
        imagefilledrectangle($im, 0, 0, $imageInfo->getWidth(), $imageInfo->getHeight(), $Bcolor);
        return $im;
    }

    /**
     * Returns the HEX color value for an RGB color array
     *
     * @param array $color RGB color array
     * @return string HEX color value
     */
    public function hexColor($color)
    {
        $r = dechex($color[0]);
        if (strlen($r) < 2) {
            $r = '0' . $r;
        }
        $g = dechex($color[1]);
        if (strlen($g) < 2) {
            $g = '0' . $g;
        }
        $b = dechex($color[2]);
        if (strlen($b) < 2) {
            $b = '0' . $b;
        }
        return '#' . $r . $g . $b;
    }

    /**
     * Unifies all colors given in the colArr color array to the first color in the array.
     *
     * @param resource $img Image resource
     * @param array $colArr Array containing RGB color arrays
     * @param bool $closest
     * @return int The index of the unified color
     */
    public function unifyColors(&$img, $colArr, $closest = false)
    {
        $retCol = -1;
        if (is_array($colArr) && !empty($colArr) && function_exists('imagepng') && function_exists('imagecreatefrompng')) {
            $firstCol = array_shift($colArr);
            $firstColArr = $this->convertColor($firstCol);
            $origName = $preName = $this->randomName() . '.png';
            $postName = $this->randomName() . '.png';
            $tmpImg = null;
            if (count($colArr) > 1) {
                $this->ImageWrite($img, $preName);
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
                    $retCol = imagecolorclosest($img, $firstColArr[0], $firstColArr[1], $firstColArr[2]);
                } else {
                    $retCol = imagecolorexact($img, $firstColArr[0], $firstColArr[1], $firstColArr[2]);
                }
            }
            // Unlink files from process
            if ($origName) {
                @unlink($origName);
            }
            if ($postName) {
                @unlink($postName);
            }
        }
        return $retCol;
    }

    /**
     * Creates error image based on gfx/notfound_thumb.png
     * Requires GD lib enabled, otherwise it will exit with the three
     * textstrings outputted as text. Outputs the image stream to browser and exits!
     *
     * @param string $filename Name of the file
     * @param string $textline1 Text line 1
     * @param string $textline2 Text line 2
     * @param string $textline3 Text line 3
     * @throws \RuntimeException
     */
    public function getTemporaryImageWithText($filename, $textline1, $textline2, $textline3)
    {
        if (empty($GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib'])) {
            throw new \RuntimeException('TYPO3 Fatal Error: No gdlib. ' . $textline1 . ' ' . $textline2 . ' ' . $textline3, 1270853952);
        }
        // Creates the basis for the error image
        $basePath = ExtensionManagementUtility::extPath('core') . 'Resources/Public/Images/';
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_png'])) {
            $im = imagecreatefrompng($basePath . 'NotFound.png');
        } else {
            $im = imagecreatefromgif($basePath . 'NotFound.gif');
        }
        // Sets background color and print color.
        $white = imagecolorallocate($im, 255, 255, 255);
        $black = imagecolorallocate($im, 0, 0, 0);
        // Prints the text strings with the build-in font functions of GD
        $x = 0;
        $font = 0;
        if ($textline1) {
            imagefilledrectangle($im, $x, 9, 56, 16, $white);
            imagestring($im, $font, $x, 9, $textline1, $black);
        }
        if ($textline2) {
            imagefilledrectangle($im, $x, 19, 56, 26, $white);
            imagestring($im, $font, $x, 19, $textline2, $black);
        }
        if ($textline3) {
            imagefilledrectangle($im, $x, 29, 56, 36, $white);
            imagestring($im, $font, $x, 29, substr($textline3, -14), $black);
        }
        // Outputting the image stream and exit
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_png'])) {
            imagepng($im, $filename);
        } else {
            imagegif($im, $filename);
        }
    }

    /**
     * Function to compensate for DPI resolution.
     * FreeType 2 always has 96 dpi, so it is hard-coded at this place.
     *
     * @param float $fontSize font size for freetype function call
     * @return float compensated font size based on 96 dpi
     */
    protected function compensateFontSizeiBasedOnFreetypeDpi($fontSize)
    {
        return $fontSize / 96.0 * 72;
    }
}
