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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Type\File\ImageInfo;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Standard graphical functions
 *
 * Class contains a bunch of cool functions for manipulating graphics with GDlib/Freetype and ImageMagick.
 * VERY OFTEN used with gifbuilder that uses this class and provides a TypoScript API to using these functions
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
     * defines the RGB colorspace to use
     *
     * @var non-empty-string
     */
    protected string $colorspace = 'RGB';

    /**
     * colorspace names allowed
     *
     * @var list<non-empty-string>
     */
    protected array $allowedColorSpaceNames = [
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
     * Allowed file extensions perceived as images by TYPO3.
     * List should be set to `gif,png,jpeg,jpg` if IM is not available.
     * Due to 'avif' still missing support with GraphicsMagick (https://sourceforge.net/p/graphicsmagick/feature-requests/64/),
     * this is not enabled by default. But if availability is detected, it is automatically appended to $webImageExt.
     * Also, system maintainers can add this format to $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'].
     *
     * @var list<non-empty-string>
     */
    protected array $imageFileExt = ['gif', 'jpg', 'jpeg', 'png', 'tif', 'bmp', 'tga', 'pcx', 'ai', 'pdf', 'webp'];

    /**
     * Web image extensions (can be shown by a webbrowser)
     * Note that 'avif' support is checked on an individual condition, see method resize().
     *
     * @var list<non-empty-string>
     */
    protected array $webImageExt = ['gif', 'jpg', 'jpeg', 'png', 'webp'];

    /**
     * @var array{jpg: string, jpeg: string, gif: string, png: string, webp: string, avif: string}
     */
    public array $cmds = [
        'jpg' => '',
        'jpeg' => '',
        'gif' => '',
        'png' => '',
        'webp' => '',
        'avif' => '',
    ];

    /**
     * Whether ImageMagick/GraphicsMagick is enabled or not
     */
    protected bool $processorEnabled;
    protected bool $mayScaleUp = true;

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
     * @var list<array{0: string, 1: string}>
     */
    public $IM_commands = [];

    /**
     * ImageMagick scaling command; "-auto-orient -geometry" or "-auto-orient -sample". Used in makeText() and imageMagickConvert()
     *
     * @var non-empty-string
     */
    public $scalecmd = '-auto-orient -geometry';

    /**
     * Used by v5_blur() to simulate 10 continuous steps of blurring
     *
     * @var non-empty-string
     */
    protected string $im5fx_blurSteps = '1x2,2x2,3x2,4x3,5x3,5x4,6x4,7x5,8x5,9x5';

    /**
     * Used by v5_sharpen() to simulate 10 continuous steps of sharpening.
     *
     * @var non-empty-string
     */
    protected string $im5fx_sharpenSteps = '1x2,2x2,3x2,2x3,3x3,4x3,3x4,4x4,4x5,5x5';

    /**
     * This is the limit for the number of pixels in an image before it will be rendered as JPG instead of GIF/PNG
     *
     * @var int<1, max>
     */
    protected int $pixelLimitGif = 10000;

    /**
     * @var int<1, 100>
     */
    protected int $jpegQuality = 85;

    /**
     * @var int<1, 101>
     */
    protected int $webpQuality = 85;

    /**
     * @var int<1, 100>
     */
    protected int $avifQuality = 85;

    /**
     * Reads configuration information from $GLOBALS['TYPO3_CONF_VARS']['GFX']
     * and sets some values in internal variables.
     */
    public function __construct()
    {
        $gfxConf = $GLOBALS['TYPO3_CONF_VARS']['GFX'];
        $this->colorspace = $this->getColorspaceFromConfiguration();

        $this->processorEnabled = (bool)$gfxConf['processor_enabled'];
        $this->jpegQuality = MathUtility::forceIntegerInRange($gfxConf['jpg_quality'], 1, 100, 85);
        if (isset($gfxConf['webp_quality'])) {
            if ($gfxConf['webp_quality'] === 'lossless') {
                $this->webpQuality = 101;
            } else {
                $this->webpQuality = MathUtility::forceIntegerInRange($gfxConf['webp_quality'], 1, 101, $this->webpQuality);
            }
        }
        if (isset($gfxConf['avif_quality'])) {
            $this->avifQuality = MathUtility::forceIntegerInRange($gfxConf['avif_quality'], 1, 100, $this->avifQuality);
        }
        $this->addFrameSelection = (bool)$gfxConf['processor_allowFrameSelection'];
        $this->imageFileExt = GeneralUtility::trimExplode(',', $gfxConf['imagefile_ext']);

        // Processor Effects. This is necessary if using ImageMagick 5+.
        // Effects in Imagemagick 5+ tends to render very slowly!
        // Therefore, must be disabled in order not to perform sharpen, blurring and such.
        // but if 'processor_effects' is set, enable effects
        if ($gfxConf['processor_effects']) {
            $this->cmds['jpg'] = $this->v5_sharpen(10);
            $this->cmds['jpeg'] = $this->v5_sharpen(10);
            $this->cmds['webp'] = $this->v5_sharpen(10);
            $this->cmds['avif'] = $this->v5_sharpen(10);
        }
        // Secures that images are not scaled up.
        $this->mayScaleUp = (bool)$gfxConf['processor_allowUpscaling'];
    }

    /**
     * Returns the IM command for sharpening with ImageMagick 5
     * Uses $this->im5fx_sharpenSteps for translation of the factor to an actual command.
     *
     * @param int $factor The sharpening factor, 0-100 (effectively in 10 steps)
     * @return string The sharpening command, eg. " -sharpen 3x4"
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
     * @return string The blurring command, e.g. " -blur 3x4"
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

    /***********************************
     *
     * Scaling, Dimensions of images
     *
     ***********************************/

    /**
     * A simple call to migrate a file to a different web-based file format. Let's say you want to convert
     * a PDF to a PNG, use this method.
     * If you want to also resize it, try "resize" instead.
     *
     * @see resize()
     */
    public function convert(string $sourceFile, string $targetFileExtension = 'web'): ?ImageProcessingResult
    {
        return $this->resize($sourceFile, $targetFileExtension);
    }

    /**
     * Converts $sourceFile to another file in temp-dir of type $targetFileExtension.
     *
     * @param string $sourceFile The absolute image filepath
     * @param string $targetFileExtension New extension, eg. "gif", "png", "jpg", "tif". If $targetFileExtension is NOT set, the new imagefile will be of the original format. If $targetFileExtension = 'WEB' then one of the web-formats is applied.
     * @param int|string $width Width. $width / $height is optional. If only one is given the image is scaled proportionally. If an 'm' exists in the $width or $height and if both are present the $width and $height is regarded as the Maximum w/h and the proportions will be kept
     * @param int|string $height Height. See $width
     * @param string $additionalParameters Additional ImageMagick parameters.
     * @param array $options An array with options passed to getImageScale (see this function).
     * @param bool $forceCreation If set, then another image than the input imagefile MUST be returned. Otherwise you can risk that the input image is good enough regarding measures etc and is of course not rendered to a new, temporary file in typo3temp/. But this option will force it to.
     * @see \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::getImgResource()
     * @see maskImageOntoImage()
     * @see copyImageOntoImage()
     * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder::scale()
     * @internal until imageMagickConvert() is marked as deprecated.
     */
    public function resize(string $sourceFile, string $targetFileExtension, int|string $width = '', int|string $height = '', string $additionalParameters = '', array $options = [], bool $forceCreation = false): ?ImageProcessingResult
    {
        if (!$this->processorEnabled) {
            // Returning file info right away
            return $this->getImageDimensions($sourceFile, true);
        }
        $info = $this->getImageDimensions($sourceFile, true);
        if (!$info) {
            return null;
        }
        $originalFileExtension = $info->getExtension();

        // Determine the final target file extension
        $targetFileExtension = strtolower(trim($targetFileExtension));
        // If no extension is given the original extension is used
        $targetFileExtension = $targetFileExtension ?: $originalFileExtension;
        $useFallback = false;
        if ($targetFileExtension === 'web') {
            // This code path is not really triggered anymore. The targetFileExtension
            // is already pre-calculated via:
            // - TYPO3\CMS\Core\Resource\Processing\ImageCropScaleMaskTask->getTargetFileExtension()
            // - TYPO3\CMS\Core\Resource\Processing\ImagePreviewTask->getTargetFileExtension()
            // This place only acts as legacy for be:thumbnail helper and manual code calls to
            // the the convert() method without an argument.
            // This would be the "give me anything web-compatible" case. Ideally it will use the original format to do scaling operations.
            // If it's not a web-format, fallback to JPG/PNG will be applied.

            // Special case for AVIF format - only use this if supported (ImageMagick: YES, GraphicsMagick: NO)
            if (in_array($originalFileExtension, $this->webImageExt, true)) {
                $targetFileExtension = $originalFileExtension;
            } elseif ($originalFileExtension === 'avif' && $this->avifSupportAvailable()) {
                $targetFileExtension = $originalFileExtension;
            } else {
                $useFallback = true;
            }
        } elseif ($targetFileExtension === 'avif' && !$this->avifSupportAvailable()) {
            // Outside the "web-compatible" case above, we also need to check if a
            // specific output format can be written.
            // For now, only AVIF has special support check handling.
            $useFallback = true;
        }
        if ($useFallback) {
            // Note that this may change the expected targetFileExtension from something like ".avif" to ".jpg".
            // This is evaluated further on in LocalCropScaleMaskHelper->processWithLocalFile() and the
            // processed filename will be altered accordingly.
            $targetFileExtension = $this->gif_or_jpg($originalFileExtension, $info->getWidth(), $info->getHeight());
        }
        if (!in_array($targetFileExtension, $this->imageFileExt, true)) {
            return null;
        }
        // Clean up additional $params
        $additionalParameters = trim($additionalParameters);
        // Refers to which frame-number to select in the image. null or 0 will select the first frame, 1 will select the next and so on...
        $frame = $this->addFrameSelection && isset($options['frame']) ? (int)$options['frame'] : 0;

        $processingInstructions = ImageProcessingInstructions::fromCropScaleValues($info->getWidth(), $info->getHeight(), $width, $height, $options);

        $originalWidth = $info->getWidth() ?: $width;
        $originalHeight = $info->getHeight() ?: $height;

        // Check if conversion should be performed ($noScale - no processing needed).
        // $noScale flag is TRUE if the width / height does NOT dictate the image to be scaled. That is if no
        // width / height is given or if the destination w/h matches the original image dimensions, or if
        // the option to not scale the image is set.
        $noScale = !$originalWidth && !$originalHeight || $processingInstructions->width === $info->getWidth() && $processingInstructions->height === $info->getHeight() || !empty($options['noScale']);
        if ($noScale && !$processingInstructions->cropArea && !$additionalParameters && !$frame && $targetFileExtension === $info->getExtension() && !$forceCreation) {
            // Set the new width and height before returning,
            // if the noScale option is set, otherwise the incoming
            // values are calculated.
            if (!empty($options['noScale'])) {
                return new ImageProcessingResult(
                    $sourceFile,
                    $processingInstructions->width,
                    $processingInstructions->height
                );
            }
            return $info;
        }

        $command = '';
        if ($processingInstructions->cropArea) {
            $cropArea = $processingInstructions->cropArea;
            $command .= ' -crop ' . $cropArea->getWidth() . 'x' . $cropArea->getHeight() . '+' . $cropArea->getOffsetLeft() . '+' . $cropArea->getOffsetTop() . '! +repage ';
        }

        // Start with the default scale command
        // check if we should use -sample or -geometry
        if ($options['sample'] ?? false) {
            $command .= '-auto-orient -sample';
        } else {
            $command .= $this->scalecmd;
        }
        // from the IM docs -- https://imagemagick.org/script/command-line-processing.php
        // "We see that ImageMagick is very good about preserving aspect ratios of images, to prevent distortion
        // of your favorite photos and images. But you might really want the dimensions to be 100x200, thereby
        // stretching the image. In this case just tell ImageMagick you really mean it (!) by appending an exclamation
        // operator to the geometry. This will force the image size to exactly what you specify.
        // So, for example, if you specify 100x200! the dimensions will become exactly 100x200"
        $command .= ' ' . $processingInstructions->width . 'x' . $processingInstructions->height . '!';
        // Add params
        $additionalParameters = $this->modifyImageMagickStripProfileParameters($additionalParameters, $options);
        $command .= ($additionalParameters ? ' ' . $additionalParameters : $this->cmds[$targetFileExtension] ?? '');

        // Add quality parameter for jpg, jpeg or webp if not already set
        if (!str_contains($command, '-quality') && ($targetFileExtension === 'jpg' || $targetFileExtension === 'jpeg')) {
            $command .= ' -quality ' . $this->jpegQuality;
        }
        // Add quality parameter for webp if not already set
        if ($targetFileExtension === 'webp') {
            if (!str_contains($command, '-quality') && !str_contains($command, 'webp:lossless')) {
                if ($this->webpQuality === 101) {
                    $command .= ' -define webp:lossless=true';
                } else {
                    $command .= ' -quality ' . $this->webpQuality;
                }
            }
        }
        if ($targetFileExtension === 'avif' && !str_contains($command, '-quality')) {
            $command .= ' -quality ' . $this->avifQuality;
        }
        // re-apply colorspace-setting for the resulting image so colors don't appear to dark (sRGB instead of RGB)
        if (!str_contains($command, '-colorspace')) {
            $command .= ' -colorspace ' . CommandUtility::escapeShellArgument($this->colorspace);
        }
        if ($this->alternativeOutputKey) {
            $theOutputName = md5($command . $processingInstructions->cropArea . PathUtility::basename($sourceFile) . $this->alternativeOutputKey . '[' . $frame . ']');
        } else {
            $theOutputName = md5($command . $processingInstructions->cropArea . $sourceFile . filemtime($sourceFile) . '[' . $frame . ']');
        }
        if ($this->imageMagickConvert_forceFileNameBody) {
            $theOutputName = $this->imageMagickConvert_forceFileNameBody;
            $this->imageMagickConvert_forceFileNameBody = '';
        }
        // Making the temporary filename
        GeneralUtility::mkdir_deep(Environment::getPublicPath() . '/typo3temp/assets/images/');
        $output = Environment::getPublicPath() . '/typo3temp/assets/images/' . $this->filenamePrefix . $theOutputName . '.' . $targetFileExtension;
        if ($this->dontCheckForExistingTempFile || !file_exists($output)) {
            $this->imageMagickExec($sourceFile, $output, $command, $frame);
        }
        if (file_exists($output)) {
            // params might change some image data, so this should be calculated again
            if ($additionalParameters) {
                return $this->getImageDimensions($output, true);
            }
            return new ImageProcessingResult($output, $processingInstructions->width, $processingInstructions->height);
        }
        return null;
    }

    /**
     * Converts $imagefile to another file in temp-dir of type $targetFileExtension.
     *
     * @param string $imagefile The absolute image filepath
     * @param string $targetFileExtension New image file extension. If $targetFileExtension is NOT set, the new imagefile will be of the original format. If set to = 'WEB' then one of the web-formats is applied.
     * @param string $w Width. $w / $h is optional. If only one is given the image is scaled proportionally. If an 'm' exists in the $w or $h and if both are present the $w and $h is regarded as the Maximum w/h and the proportions will be kept
     * @param string $h Height. See $w
     * @param string $params Additional ImageMagick parameters.
     * @param string $frame Refers to which frame-number to select in the image. '' or 0 will select the first frame, 1 will select the next and so on...
     * @param array $options An array with options passed to getImageScale (see this function).
     * @param bool $mustCreate If set, then another image than the input imagefile MUST be returned. Otherwise, you can risk that the input image is good enough regarding measures etc and is of course not rendered to a new, temporary file in typo3temp/. But this option will force it to.
     * @return array|null [0]/[1] is w/h, [2] is file extension and [3] is the filename.
     * @see \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::getImgResource()
     * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder::maskImageOntoImage()
     * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder::copyImageOntoImage()
     * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder::scale()
     */
    public function imageMagickConvert($imagefile, $targetFileExtension = '', $w = '', $h = '', $params = '', $frame = '', $options = [], $mustCreate = false)
    {
        if ($frame !== '') {
            $options['frame'] = (int)$frame;
        }
        $result = $this->resize($imagefile, $targetFileExtension, $w, $h, $params, $options, $mustCreate);
        return $result?->toLegacyArray();
    }

    /**
     * This applies an image onto the $inputFile with an additional backgroundImage for the mask
     * @internal until API is finalized
     */
    public function mask(string $inputFile, string $outputFile, string $maskImage, string $maskBackgroundImage, string $params, array $options)
    {
        $params = $this->modifyImageMagickStripProfileParameters($params, $options);
        $tmpStr = $this->randomName();
        //	m_mask
        $intermediateMaskFile = $tmpStr . '_mask.png';
        $this->imageMagickExec($maskImage, $intermediateMaskFile, $params);
        //	m_bgImg
        $intermediateMaskBackgroundFile = $tmpStr . '_bgImg.miff';
        $this->imageMagickExec($maskBackgroundImage, $intermediateMaskBackgroundFile, $params);
        // The image onto the background
        $this->combineExec($intermediateMaskBackgroundFile, $inputFile, $intermediateMaskFile, $outputFile);
        // Unlink the temp-images...
        @unlink($intermediateMaskFile);
        @unlink($intermediateMaskBackgroundFile);
    }

    /**
     * Gets the input image dimensions.
     *
     * @param string $imageFile The absolute image filepath
     * @return ImageProcessingResult|array|null Returns an array where [0]/[1] is w/h, [2] is extension and [3] is the absolute filepath.
     * @see imageMagickConvert()
     * @see \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::getImgResource()
     */
    public function getImageDimensions(string $imageFile, bool $useResultObject = false): ImageProcessingResult|array|null
    {
        preg_match('/([^\\.]*)$/', $imageFile, $reg);
        if (!file_exists($imageFile)) {
            return null;
        }
        // @todo: check if we actually need this, as ImageInfo deals with this much more professionally
        if (!in_array(strtolower($reg[0]), $this->imageFileExt, true)) {
            return null;
        }
        $imageInfoObject = GeneralUtility::makeInstance(ImageInfo::class, $imageFile);
        if ($imageInfoObject->isFile() && $imageInfoObject->getWidth()) {
            $result = ImageProcessingResult::createFromImageInfo($imageInfoObject);
            return $useResultObject ? $result : $result->toLegacyArray();
        }
        return null;
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
        $theMask = $this->randomName() . '.png';
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
     * Modifies the parameters for ImageMagick for stripping of profile information.
     * Strips profile information of image to save some space ideally
     *
     * @param string $parameters The parameters to be modified (if required)
     */
    protected function modifyImageMagickStripProfileParameters(string $parameters, array $options): string
    {
        if (!isset($options['stripProfile'])) {
            return $parameters;
        }

        $gfxConf = $GLOBALS['TYPO3_CONF_VARS']['GFX'] ?? [];
        // Use legacy processor_stripColorProfileCommand setting if defined, otherwise
        // use the preferred configuration option processor_stripColorProfileParameters
        $stripColorProfileCommand = $gfxConf['processor_stripColorProfileCommand'] ??
            implode(' ', array_map(CommandUtility::escapeShellArgument(...), $gfxConf['processor_stripColorProfileParameters'] ?? []));
        if ($options['stripProfile'] && $stripColorProfileCommand !== '') {
            return $stripColorProfileCommand . ' ' . $parameters;
        }

        return $parameters . '###SkipStripProfile###';
    }

    /***********************************
     *
     * Various IO functions
     *
     ***********************************/

    /**
     * Returns an image extension for an output image based on the number of pixels of the output and the file extension of the original file.
     * For example: If the number of pixels exceeds $this->pixelLimitGif (normally 10000) then it will be a "jpg" string in return.
     *
     * @param string $type The file extension, lowercase.
     * @param int $w The width of the output image.
     * @param int $h The height of the output image.
     * @return string The filename, either "jpg" or "png"
     */
    public function gif_or_jpg($type, $w, $h)
    {
        if ($type === 'ai' || $type === 'gif' || $w * $h < $this->pixelLimitGif) {
            return 'png';
        }
        // @todo Change this to allow specific fallback formats instead of hard-coded.
        return 'jpg';
    }

    /**
     * @internal
     */
    public function isProcessingEnabled(): bool
    {
        return $this->processorEnabled;
    }

    /**
     * Check if a specific format is writable with image/graphicsmagick
     *
     * @internal
     */
    public function webpSupportAvailable(): bool
    {
        return $this->isConvertSupportAvailableForFormat('WEBP');
    }

    /**
     * Check if a specific format is writable with image/graphicsmagick
     *
     * @internal
     */
    public function avifSupportAvailable(): bool
    {
        return $this->isConvertSupportAvailableForFormat('AVIF');
    }

    /**
     * convert -list format returns all formats, ideally with a line like this:
     * "WEBP P rw- WebP Image Format (libwepb v1.3.2, ENCODER ABI 0x020F)"
     * "AVIF* HEIC      rw+   AV1 Image File Format (1.15.1)"
     * only if we have "rw" included, TYPO3 can fully support to read and write webp images.
     *
     * @internal
     */
    public function isConvertSupportAvailableForFormat(string $fileFormat): bool
    {
        $cmd = CommandUtility::imageMagickCommand('convert', '-list format');
        CommandUtility::exec($cmd, $output);
        $this->IM_commands[] = ['', $cmd];
        foreach ($output as $outputLine) {
            $outputLine = trim($outputLine);
            if (str_starts_with($outputLine, $fileFormat) && str_contains($outputLine, ' rw')) {
                return true;
            }
        }
        return false;
    }

    /**
     * @internal Only used for ext:install, not part of TYPO3 Core API.
     */
    public function setImageFileExt(array $imageFileExt): void
    {
        $this->imageFileExt = $imageFileExt;
    }

    /**
     * @internal Not part of TYPO3 Core API.
     */
    public function getImageFileExt(): array
    {
        return $this->imageFileExt;
    }

    /**
     * Returns the recommended colorspace for a processor or the one set
     * in the configuration
     */
    protected function getColorspaceFromConfiguration(): string
    {
        $gfxConf = $GLOBALS['TYPO3_CONF_VARS']['GFX'];

        if ($gfxConf['processor'] === 'ImageMagick' && $gfxConf['processor_colorspace'] === '') {
            return 'sRGB';
        }

        if ($gfxConf['processor'] === 'GraphicsMagick' && $gfxConf['processor_colorspace'] === '') {
            return 'RGB';
        }

        return in_array($gfxConf['processor_colorspace'], $this->allowedColorSpaceNames, true) ? $gfxConf['processor_colorspace'] : $this->colorspace;
    }
}
