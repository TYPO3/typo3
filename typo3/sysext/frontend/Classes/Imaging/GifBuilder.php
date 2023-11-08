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

namespace TYPO3\CMS\Frontend\Imaging;

use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\FileProcessingAspect;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Imaging\ImageResource;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\Service\ConfigurationService;
use TYPO3\CMS\Core\Type\File\ImageInfo;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\File\BasicFileUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Resource\FilePathSanitizer;

/**
 * GIFBUILDER
 *
 * Generating image files from TypoScript
 * Used by imgResource in TypoScript.
 *
 * This class allows for advanced rendering of images with various layers of images, text and graphical primitives.
 * The concept is known from TypoScript as "GIFBUILDER" where you can define a "numerical array" (TypoScript term as well)
 * of "GIFBUILDER OBJECTS" (like "TEXT", "IMAGE", etc.) and they will be rendered onto an image one by one.
 * The name "GIFBUILDER" comes from the time when GIF was the only file format supported.
 * .png, .jpg, .webp and .avif files are just as well to create today (configured with TYPO3_CONF_VARS[GFX])
 *
 * Here is an example of how to use this class:
 *
 * $imageCreator = GeneralUtility::makeInstance(GifBuilder::class);
 * $imageCreator->start($fileArray, $this->data);
 * $theImage = $imageCreator->gifBuild();
 * return GeneralUtility::makeInstance(GraphicalFunctions::class)->getImageDimensions($theImage);
 */
class GifBuilder
{
    /**
     * Contains all text strings used on this image
     *
     * @var list<string>
     */
    protected array $combinedTextStrings = [];

    /**
     * Contains all filenames (basename without extension) used on this image
     *
     * @var list<string>
     */
    protected array $combinedFileNames = [];

    /**
     * This is the array from which data->field: [key] is fetched. So this is the current record!
     */
    protected array $data = [];
    protected array $objBB = [];

    /**
     * @var array<string, array>
     */
    protected array $charRangeMap = [];

    /**
     * @var array{0?: int<0, max>, 1?: int<0, max>}
     */
    protected array $XY = [];
    protected ?ContentObjectRenderer $cObj = null;

    /**
     * @var list<int>
     */
    protected array $workArea = [];

    /**
     * @var list<int>
     */
    protected array $defaultWorkArea = [];

    /**
     * Preserve the alpha transparency layer of read PNG images
     */
    protected bool $saveAlphaLayer = false;

    /**
     * Array mapping HTML color names to RGB values.
     *
     * @var array<non-empty-string, array{0: int<0, 255>, 1: int<0, 255>, 2: int<0, 255>}>
     */
    protected array $colMap = [
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
     * This holds the operational setup.
     * Basically this is a TypoScript array with properties.
     *
     * @internal
     */
    public array $setup = [];

    /**
     * @var int<0, max>
     */
    protected int $w = 0;
    /**
     * @var int<0, max>
     */
    protected int $h = 0;

    /**
     * @var list<int>
     */
    protected array $offset;

    /**
     * File formats supported by gdlib. This variable gets filled in the constructor
     *
     * @var list<non-empty-string>
     */
    protected array $gdlibExtensions = [];

    protected CharsetConverter $csConvObj;
    protected GraphicalFunctions $imageService;

    /**
     * Enable ImageMagick effects, disabled by default as IM5+ effects slow down the image generation
     */
    protected bool $processorEffectsEnabled = false;

    /**
     * @var int<10, 100>
     */
    protected int $jpegQuality = 85;
    /**
     * @var int<10, 101>
     */
    protected int $webpQuality = 85;

    /**
     * @var int<-1, 100>
     */
    protected int $avifQuality = 85;

    public function __construct()
    {
        $gfxConf = $GLOBALS['TYPO3_CONF_VARS']['GFX'];
        if ($gfxConf['processor_effects'] ?? false) {
            $this->processorEffectsEnabled = true;
        }
        $this->jpegQuality = MathUtility::forceIntegerInRange($gfxConf['jpg_quality'], 10, 100, $this->jpegQuality);
        $this->avifQuality = MathUtility::forceIntegerInRange($gfxConf['avif_quality'] ?? 0, -1, 100, $this->avifQuality);
        if (isset($gfxConf['webp_quality'])) {
            // see IMG_WEBP_LOSSLESS // https://www.php.net/manual/en/image.constants.php
            if ($gfxConf['webp_quality'] === 'lossless') {
                $this->webpQuality = 101;
            } else {
                $this->webpQuality = MathUtility::forceIntegerInRange($gfxConf['webp_quality'], 10, 101, $this->webpQuality);
            }
        }
        if (function_exists('imagecreatefromjpeg') && function_exists('imagejpeg')) {
            $this->gdlibExtensions[] = 'jpg';
            $this->gdlibExtensions[] = 'jpeg';
        }
        if (function_exists('imagecreatefrompng') && function_exists('imagepng')) {
            $this->gdlibExtensions[] = 'png';
        }
        if (function_exists('imagecreatefromwebp') && function_exists('imagewebp')) {
            $this->gdlibExtensions[] = 'webp';
        }
        if (function_exists('imagecreatefromavif') && function_exists('imageavif')) {
            $this->gdlibExtensions[] = 'avif';
        }
        if (function_exists('imagecreatefromgif') && function_exists('imagegif')) {
            $this->gdlibExtensions[] = 'gif';
        }
        $this->imageService = GeneralUtility::makeInstance(GraphicalFunctions::class);
        $this->csConvObj = GeneralUtility::makeInstance(CharsetConverter::class);
    }

    /**
     * Initialization of the GIFBUILDER objects, in particular TEXT and IMAGE. This includes finding the bounding box, setting dimensions and offset values before the actual rendering is started.
     * Modifies the ->setup, ->objBB internal arrays
     *
     * @param array $conf TypoScript properties for the GIFBUILDER session. Stored internally in the variable ->setup
     * @param array $data The current data record from \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer. Stored internally in the variable ->data
     * @see ContentObjectRenderer::getImgResource()
     */
    public function start(array $conf, array $data): void
    {
        if (!class_exists(\GdImage::class)) {
            return;
        }
        $this->setup = $conf;
        $this->data = $data;
        $this->cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $this->cObj->start($this->data);
        // Initializing Char Range Map
        $this->charRangeMap = [];
        foreach ($conf['charRangeMap.'] ?? [] as $cRMcfgkey => $cRMcfg) {
            if (is_array($cRMcfg)) {
                $cRMkey = $conf['charRangeMap.'][substr($cRMcfgkey, 0, -1)];
                $this->charRangeMap[$cRMkey] = [];
                $this->charRangeMap[$cRMkey]['charMapConfig'] = $cRMcfg['charMapConfig.'] ?? [];
                $this->charRangeMap[$cRMkey]['cfgKey'] = substr($cRMcfgkey, 0, -1);
                $this->charRangeMap[$cRMkey]['multiplicator'] = (float)$cRMcfg['fontSizeMultiplicator'];
                $this->charRangeMap[$cRMkey]['pixelSpace'] = (int)$cRMcfg['pixelSpaceFontSizeRef'];
            }
        }
        // Getting sorted list of TypoScript keys from setup.
        $sKeyArray = ArrayUtility::filterAndSortByNumericKeys($this->setup);
        // Setting the background color, passing it through stdWrap
        $this->setup['backColor'] = $this->cObj->stdWrapValue('backColor', $this->setup, 'white');
        $this->setup['transparentColor_array'] = explode('|', trim((string)$this->cObj->stdWrapValue('transparentColor', $this->setup)));
        $this->setup['transparentBackground'] = $this->cObj->stdWrapValue('transparentBackground', $this->setup);
        // Set default dimensions
        $this->setup['XY'] = $this->cObj->stdWrapValue('XY', $this->setup);
        if (!$this->setup['XY']) {
            $this->setup['XY'] = '120,50';
        }
        // Checking TEXT and IMAGE objects for files. If any errors the objects are cleared.
        // The Bounding Box for the objects is stored in an array
        foreach ($sKeyArray as $index => $theKey) {
            if (!($theValue = $this->setup[$theKey] ?? false)) {
                continue;
            }
            if ((int)$theKey && ($conf = $this->setup[$theKey . '.'] ?? [])) {
                // Swipes through TEXT and IMAGE-objects
                switch ($theValue) {
                    case 'TEXT':
                        if ($this->setup[$theKey . '.'] = $this->checkTextObj($conf)) {
                            // Adjust font width if max size is set:
                            $maxWidth = $this->cObj->stdWrapValue('maxWidth', $this->setup[$theKey . '.'] ?? []);
                            if ($maxWidth) {
                                $this->setup[$theKey . '.']['fontSize'] = $this->fontResize($this->setup[$theKey . '.']);
                            }
                            // Calculate bounding box:
                            $txtInfo = $this->calcBBox($this->setup[$theKey . '.']);
                            $this->setup[$theKey . '.']['BBOX'] = $txtInfo;
                            $this->objBB[$theKey] = $txtInfo;
                        }
                        break;
                    case 'IMAGE':
                        $imageResource = $this->getResource($conf['file'] ?? '', $conf['file.'] ?? []);
                        if ($imageResource !== null) {
                            $this->combinedFileNames[] = preg_replace('/\\.[[:alnum:]]+$/', '', PathUtility::basename($imageResource->getFullPath()));
                            if ($imageResource->getProcessedFile() instanceof ProcessedFile) {
                                // Use processed file, if a FAL file has been processed by GIFBUILDER (e.g. scaled/cropped)
                                $this->setup[$theKey . '.']['file'] = $imageResource->getProcessedFile()->getForLocalProcessing(false);
                            } elseif ($imageResource->getOriginalFile() instanceof File) {
                                // Use FAL file with getForLocalProcessing to circumvent problems with umlauts, if it is a FAL file (origFile not set)
                                $this->setup[$theKey . '.']['file'] = $imageResource->getOriginalFile()->getForLocalProcessing(false);
                            } else {
                                // Use normal path from fileInfo if it is a non-FAL file (even non-FAL files have originalFile set, but only non-FAL files have origFile set)
                                $this->setup[$theKey . '.']['file'] = $imageResource->getFullPath();
                            }

                            // only pass necessary parts of ImageResource further down, to not incorporate facts as
                            // CropScaleMask runs in this request, that may not occur in subsequent calls and change
                            // the md5 of the generated file name
                            $this->setup[$theKey . '.']['BBOX'] = $imageResource->getLegacyImageResourceInformation();
                            $this->objBB[$theKey] = $imageResource->getLegacyImageResourceInformation();
                            if ($conf['mask'] ?? false) {
                                $maskResource = $this->getResource($conf['mask'], $conf['mask.'] ?? []);
                                if ($maskResource !== null) {
                                    // the same selection criteria as regarding fileInfo above apply here
                                    if ($maskResource->getProcessedFile() instanceof ProcessedFile) {
                                        $this->setup[$theKey . '.']['mask'] = $maskResource->getProcessedFile()->getForLocalProcessing(false);
                                    } elseif ($maskResource->getOriginalFile() instanceof File) {
                                        $this->setup[$theKey . '.']['mask'] = $maskResource->getOriginalFile()->getForLocalProcessing(false);
                                    } else {
                                        $this->setup[$theKey . '.']['mask'] = $maskResource->getFullPath();
                                    }
                                } else {
                                    $this->setup[$theKey . '.']['mask'] = '';
                                }
                            }
                        } else {
                            unset($this->setup[$theKey . '.']);
                        }
                        break;
                }
                // Checks if disabled is set
                if (($conf['if.'] ?? false) && !$this->cObj->checkIf($conf['if.'])) {
                    unset($sKeyArray[$index]);
                    unset($this->setup[$theKey]);
                    unset($this->setup[$theKey . '.']);
                    unset($this->objBB[$theKey]);
                }
            }
        }
        // Calculate offsets on elements
        $this->setup['XY'] = $this->calcOffset($this->setup['XY']);
        $this->setup['offset'] = (string)$this->cObj->stdWrapValue('offset', $this->setup);
        $this->setup['offset'] = $this->calcOffset($this->setup['offset']);
        $this->setup['workArea'] = (string)$this->cObj->stdWrapValue('workArea', $this->setup);
        $this->setup['workArea'] = $this->calcOffset($this->setup['workArea']);
        foreach ($sKeyArray as $theKey) {
            if (!($theValue = $this->setup[$theKey] ?? false)) {
                continue;
            }
            if ((int)$theKey && ($this->setup[$theKey . '.'] ?? false)) {
                switch ($theValue) {
                    case 'TEXT':

                    case 'IMAGE':
                        if (isset($this->setup[$theKey . '.']['offset.'])) {
                            $this->setup[$theKey . '.']['offset'] = $this->cObj->stdWrapValue('offset', $this->setup[$theKey . '.']);
                            unset($this->setup[$theKey . '.']['offset.']);
                        }
                        if ($this->setup[$theKey . '.']['offset'] ?? false) {
                            $this->setup[$theKey . '.']['offset'] = $this->calcOffset($this->setup[$theKey . '.']['offset']);
                        }
                        break;
                    case 'BOX':

                    case 'ELLIPSE':
                        if (isset($this->setup[$theKey . '.']['dimensions.'])) {
                            $this->setup[$theKey . '.']['dimensions'] = $this->cObj->stdWrapValue('dimensions', $this->setup[$theKey . '.']);
                            unset($this->setup[$theKey . '.']['dimensions.']);
                        }
                        if ($this->setup[$theKey . '.']['dimensions'] ?? false) {
                            $this->setup[$theKey . '.']['dimensions'] = $this->calcOffset($this->setup[$theKey . '.']['dimensions']);
                        }
                        break;
                    case 'WORKAREA':
                        if (isset($this->setup[$theKey . '.']['set.'])) {
                            $this->setup[$theKey . '.']['set'] = $this->cObj->stdWrapValue('set', $this->setup[$theKey . '.']);
                            unset($this->setup[$theKey . '.']['set.']);
                        }
                        if ($this->setup[$theKey . '.']['set'] ?? false) {
                            $this->setup[$theKey . '.']['set'] = $this->calcOffset($this->setup[$theKey . '.']['set']);
                        }
                        break;
                    case 'CROP':
                        if (isset($this->setup[$theKey . '.']['crop.'])) {
                            $this->setup[$theKey . '.']['crop'] = $this->cObj->stdWrapValue('crop', $this->setup[$theKey . '.']);
                            unset($this->setup[$theKey . '.']['crop.']);
                        }
                        if ($this->setup[$theKey . '.']['crop'] ?? false) {
                            $this->setup[$theKey . '.']['crop'] = $this->calcOffset($this->setup[$theKey . '.']['crop']);
                        }
                        break;
                    case 'SCALE':
                        if (isset($this->setup[$theKey . '.']['width.'])) {
                            $this->setup[$theKey . '.']['width'] = $this->cObj->stdWrapValue('width', $this->setup[$theKey . '.']);
                            unset($this->setup[$theKey . '.']['width.']);
                        }
                        if ($this->setup[$theKey . '.']['width'] ?? false) {
                            $this->setup[$theKey . '.']['width'] = $this->calcOffset($this->setup[$theKey . '.']['width']);
                        }
                        if (isset($this->setup[$theKey . '.']['height.'])) {
                            $this->setup[$theKey . '.']['height'] = $this->cObj->stdWrapValue('height', $this->setup[$theKey . '.']);
                            unset($this->setup[$theKey . '.']['height.']);
                        }
                        if ($this->setup[$theKey . '.']['height'] ?? false) {
                            $this->setup[$theKey . '.']['height'] = $this->calcOffset($this->setup[$theKey . '.']['height']);
                        }
                        break;
                }
            }
        }
        // Get trivial data
        $XY = GeneralUtility::intExplode(',', $this->setup['XY']);
        $maxWidth = (int)$this->cObj->stdWrapValue('maxWidth', $this->setup);
        $maxHeight = (int)$this->cObj->stdWrapValue('maxHeight', $this->setup);
        $XY[0] = MathUtility::forceIntegerInRange($XY[0], 1, $maxWidth ?: 2000);
        $XY[1] = MathUtility::forceIntegerInRange($XY[1], 1, $maxHeight ?: 2000);
        $this->XY = $XY;
        $this->w = $XY[0];
        $this->h = $XY[1];
        $this->offset = GeneralUtility::intExplode(',', $this->setup['offset']);
        // this sets the workArea
        $this->setWorkArea($this->setup['workArea']);
        // this sets the default to the current
        $this->defaultWorkArea = $this->workArea;
    }

    /**
     * Initiates the image file generation if ->setup is TRUE and if the file did not
     * exist already. Gets filename from fileName() and if file exists in typo3temp/assets/images/
     * dir it will- of course - not be rendered again. Otherwise rendering means calling ->make(),
     * then ->output(), then destroys the image and returns the ImageResource DTO.
     *
     * @return ImageResource|null Returns the ImageResource DTO with file information from ContentObjectRenderer::getImgResource() - or NULL
     * @see make()
     * @see fileName()
     */
    public function gifBuild(): ?ImageResource
    {
        if (!$this->setup || !class_exists(\GdImage::class)) {
            return null;
        }

        $fullFileName = Environment::getPublicPath() . '/typo3temp/assets/images/' . $this->fileName();
        if (!file_exists($fullFileName)) {
            // Create temporary directory if not done
            GeneralUtility::mkdir_deep(dirname($fullFileName));
            // Create file
            $gdImage = $this->make();
            $this->output($gdImage, $fullFileName);
            imagedestroy($gdImage);
        }

        $imageInfo = GeneralUtility::makeInstance(ImageInfo::class, $fullFileName);
        if ($imageInfo->getWidth() > 0) {
            return ImageResource::createFromImageInfo($imageInfo);
        }

        return null;
    }

    /**
     * Writing the image pointer, to file based on the extension of the input filename.
     * Uses $this->setup['quality'] for jpg images to reduce size/quality if needed.
     *
     * @param string $file The absolute filename to write to
     * @see gifBuild()
     */
    protected function output(\GdImage $gdImage, string $file): void
    {
        if ($file === '') {
            return;
        }

        $reg = [];
        preg_match('/([^\\.]*)$/', $file, $reg);
        $ext = strtolower($reg[0]);
        switch ($ext) {
            case 'gif':
            case 'png':
                $this->ImageWrite($gdImage, $file);
                break;
            case 'jpg':
            case 'jpeg':
                // Use the default
                $quality = isset($this->setup['quality']) ? MathUtility::forceIntegerInRange((int)$this->setup['quality'], 10, 100) : 0;
                $this->ImageWrite($gdImage, $file, $quality);
                break;
            case 'webp':
                // Quality can also be set to IMG_WEBP_LOSSLESS = 101
                $quality = isset($this->setup['quality']) ? MathUtility::forceIntegerInRange((int)$this->setup['quality'], 10, 101) : 0;
                $this->ImageWrite($gdImage, $file, $quality);
                break;
            case 'avif':
                $quality = isset($this->setup['quality']) ? MathUtility::forceIntegerInRange((int)$this->setup['quality'], -1, 100) : 0;
                $speed = isset($this->setup['speed']) ? MathUtility::forceIntegerInRange((int)$this->setup['speed'], -1, 10) : -1;
                $this->ImageWrite($gdImage, $file, $quality, $speed);
                break;
        }
    }

    /**
     * The actual rendering of the image file.
     *
     * Creates a GDlib resource, works on that and returns it.
     *
     * Basically sets the dimensions, the background color, the traverses the array of GIFBUILDER objects
     * and finally setting the transparent color if defined.
     *
     * Called by gifBuild()
     *
     * @see gifBuild()
     */
    protected function make(): \GdImage
    {
        // Get trivial data
        $XY = $this->XY;
        // Reset internal properties
        $this->saveAlphaLayer = false;
        // Gif-start
        $im = imagecreatetruecolor($XY[0], $XY[1]);
        if (!$im instanceof \GdImage) {
            throw new \RuntimeException('imagecreatetruecolor returned false', 1598350445);
        }
        $this->w = $XY[0];
        $this->h = $XY[1];
        // Transparent layer as background if set and requirements are met
        if (($this->setup['backColor'] ?? '') === 'transparent' && (empty($this->setup['format']) || $this->setup['format'] === 'png')) {
            // Set transparency properties
            imagesavealpha($im, true);
            // Fill with a transparent background
            $transparentColor = imagecolorallocatealpha($im, 0, 0, 0, 127);
            imagefill($im, 0, 0, $transparentColor);
            // Set internal properties to keep the transparency over the rendering process
            $this->saveAlphaLayer = true;
            // Force PNG in case no format is set
            $this->setup['format'] = 'png';
            $BGcols = [];
        } else {
            // Fill the background with the given color
            $BGcols = $this->convertColor($this->setup['backColor']);
            $Bcolor = imagecolorallocate($im, $BGcols[0], $BGcols[1], $BGcols[2]);
            imagefilledrectangle($im, 0, 0, $XY[0], $XY[1], $Bcolor);
        }
        // Traverse the GIFBUILDER objects and render each one:
        $sKeyArray = ArrayUtility::filterAndSortByNumericKeys($this->setup);
        foreach ($sKeyArray as $theKey) {
            $theValue = $this->setup[$theKey];
            if ((int)$theKey && ($conf = $this->setup[$theKey . '.'] ?? [])) {
                // apply stdWrap to all properties, except for TEXT objects
                // all properties of the TEXT sub-object have already been stdWrap-ped
                // before in ->checkTextObj()
                if ($theValue !== 'TEXT') {
                    $isStdWrapped = [];
                    foreach ($conf as $key => $value) {
                        $parameter = rtrim($key, '.');
                        if (!($isStdWrapped[$parameter] ?? false) && isset($conf[$parameter . '.'])) {
                            $conf[$parameter] = $this->cObj->stdWrapValue($parameter, $conf);
                            $isStdWrapped[$parameter] = 1;
                        }
                    }
                }

                switch ($theValue) {
                    case 'IMAGE':
                        if ($conf['mask'] ?? false) {
                            $this->maskImageOntoImage($im, $conf, $this->workArea);
                        } else {
                            $this->copyImageOntoImage($im, $conf, $this->workArea);
                        }
                        break;
                    case 'TEXT':
                        if (!($conf['hide'] ?? false)) {
                            if (is_array($conf['shadow.'] ?? null)) {
                                $isStdWrapped = [];
                                foreach ($conf['shadow.'] as $key => $value) {
                                    $parameter = rtrim($key, '.');
                                    if (!($isStdWrapped[$parameter] ?? false) && isset($conf[$parameter . '.'])) {
                                        $conf['shadow.'][$parameter] = $this->cObj->stdWrapValue($parameter, $conf);
                                        $isStdWrapped[$parameter] = 1;
                                    }
                                }
                                $this->makeShadow($im, $conf['shadow.'], $this->workArea, $conf);
                            }
                            if (is_array($conf['emboss.'] ?? null)) {
                                $isStdWrapped = [];
                                foreach ($conf['emboss.'] as $key => $value) {
                                    $parameter = rtrim($key, '.');
                                    if (!($isStdWrapped[$parameter] ?? false) && isset($conf[$parameter . '.'])) {
                                        $conf['emboss.'][$parameter] = $this->cObj->stdWrapValue($parameter, $conf);
                                        $isStdWrapped[$parameter] = 1;
                                    }
                                }
                                $this->makeEmboss($im, $conf['emboss.'], $this->workArea, $conf);
                            }
                            if (is_array($conf['outline.'] ?? null)) {
                                $isStdWrapped = [];
                                foreach ($conf['outline.'] as $key => $value) {
                                    $parameter = rtrim($key, '.');
                                    if (!($isStdWrapped[$parameter] ?? false) && isset($conf[$parameter . '.'])) {
                                        $conf['outline.'][$parameter] = $this->cObj->stdWrapValue($parameter, $conf);
                                        $isStdWrapped[$parameter] = 1;
                                    }
                                }
                                $this->makeOutline($im, $conf['outline.'], $this->workArea, $conf);
                            }
                            $this->makeText($im, $conf, $this->workArea);
                        }
                        break;
                    case 'OUTLINE':
                        if ($this->setup[$conf['textObjNum']] === 'TEXT' && ($txtConf = $this->checkTextObj($this->setup[$conf['textObjNum'] . '.']))) {
                            $this->makeOutline($im, $conf, $this->workArea, $txtConf);
                        }
                        break;
                    case 'EMBOSS':
                        if ($this->setup[$conf['textObjNum']] === 'TEXT' && ($txtConf = $this->checkTextObj($this->setup[$conf['textObjNum'] . '.']))) {
                            $this->makeEmboss($im, $conf, $this->workArea, $txtConf);
                        }
                        break;
                    case 'SHADOW':
                        if ($this->setup[$conf['textObjNum']] === 'TEXT' && ($txtConf = $this->checkTextObj($this->setup[$conf['textObjNum'] . '.']))) {
                            $this->makeShadow($im, $conf, $this->workArea, $txtConf);
                        }
                        break;
                    case 'BOX':
                        $this->makeBox($im, $conf, $this->workArea);
                        break;
                    case 'EFFECT':
                        $this->makeEffect($im, $conf);
                        break;
                    case 'ADJUST':
                        $this->adjust($im, $conf);
                        break;
                    case 'CROP':
                        $this->crop($im, $conf);
                        break;
                    case 'SCALE':
                        $this->scale($im, $conf);
                        break;
                    case 'WORKAREA':
                        if ($conf['set']) {
                            // this sets the workArea
                            $this->setWorkArea($conf['set']);
                        }
                        if (isset($conf['clear'])) {
                            // This sets the current to the default;
                            $this->workArea = $this->defaultWorkArea;
                        }
                        break;
                    case 'ELLIPSE':
                        $this->makeEllipse($im, $conf, $this->workArea);
                        break;
                }
            }
        }
        // Preserve alpha transparency
        if (!$this->saveAlphaLayer) {
            if ($this->setup['transparentBackground']) {
                // Auto transparent background is set
                $Bcolor = imagecolorclosest($im, $BGcols[0], $BGcols[1], $BGcols[2]);
                imagecolortransparent($im, $Bcolor);
            } elseif (is_array($this->setup['transparentColor_array'])) {
                // Multiple transparent colors are set. This is done via the trick that all transparent colors get
                // converted to one color and then this one gets set as transparent as png/gif can just have one
                // transparent color.
                $Tcolor = $this->unifyColors($im, $this->setup['transparentColor_array'], (bool)($this->setup['transparentColor.']['closest'] ?? false));
                if ($Tcolor >= 0) {
                    imagecolortransparent($im, $Tcolor);
                }
            }
        }
        return $im;
    }

    /**
     * Implements the "IMAGE" GIFBUILDER object, when the "mask" property is TRUE.
     * It reads the two images defined by $conf['file'] and $conf['mask'] and copies the $conf['file'] onto the input image pointer image using the $conf['mask'] as a grayscale mask
     * The operation involves ImageMagick for combining.
     *
     * @param \GdImage $im GDlib image pointer
     * @param array $conf TypoScript array with configuration for the GIFBUILDER object.
     * @param array $workArea The current working area coordinates.
     * @see make()
     */
    protected function maskImageOntoImage(\GdImage &$im, array $conf, array $workArea): void
    {
        if ($conf['file'] && $conf['mask']) {
            $imgInf = pathinfo($conf['file']);
            $imgExt = strtolower($imgInf['extension']);
            if (!in_array($imgExt, $this->gdlibExtensions, true)) {
                $BBimage = $this->imageService->convert($conf['file'], 'png');
            } else {
                $BBimage = $this->imageService->getImageDimensions($conf['file'], true);
            }
            $maskInf = pathinfo($conf['mask']);
            $maskExt = strtolower($maskInf['extension']);
            if (!in_array($maskExt, $this->gdlibExtensions, true)) {
                $BBmask = $this->imageService->convert($conf['mask'], 'png');
            } else {
                $BBmask = $this->imageService->getImageDimensions($conf['mask'], true);
            }
            if ($BBimage && $BBmask) {
                $w = imagesx($im);
                $h = imagesy($im);
                $tmpStr = $this->imageService->randomName();
                $theImage = $tmpStr . '_img.png';
                $theDest = $tmpStr . '_dest.png';
                $theMask = $tmpStr . '_mask.png';
                // Prepare overlay image
                $cpImg = $this->imageCreateFromFile($BBimage->getRealPath());
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
                $cpImg = $this->imageCreateFromFile($BBmask->getRealPath());
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
                $this->imageService->combineExec($theDest, $theImage, $theMask, $theDest);
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
     * @param \GdImage $im GDlib image pointer
     * @param array $conf TypoScript array with configuration for the GIFBUILDER object.
     * @param array $workArea The current working area coordinates.
     * @see make()
     * @see maskImageOntoImage()
     */
    protected function copyImageOntoImage(\GdImage &$im, array $conf, array $workArea): void
    {
        if ($conf['file']) {
            if (!in_array($conf['BBOX'][2], $this->gdlibExtensions, true)) {
                $conf['BBOX'] = $this->imageService->convert($conf['BBOX']->getRealPath(), 'png');
                $conf['file'] = $conf['BBOX'] ? $conf['BBOX']->getRealPath() : null;
            }
            $cpImg = $this->imageCreateFromFile($conf['file']);
            $this->copyGifOntoGif($im, $cpImg, $conf, $workArea);
            imagedestroy($cpImg);
        }
    }

    /**
     * Implements the "TEXT" GIFBUILDER object
     *
     * @param \GdImage $im GDlib image pointer
     * @param array $conf TypoScript array with configuration for the GIFBUILDER object.
     * @param array $workArea The current working area coordinates.
     * @see make()
     * @internal
     */
    public function makeText(\GdImage &$im, array $conf, array $workArea): void
    {
        // Spacing
        [$spacing, $wordSpacing] = $this->calcWordSpacing($conf);
        // Position
        $txtPos = $this->txtPosition($conf, $workArea, $conf['BBOX']);
        $theText = $conf['text'] ?? '';
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
            $tmpStr = $this->imageService->randomName();
            $fileMenu = $tmpStr . '_menuNT.png';
            $fileColor = $tmpStr . '_colorNT.png';
            $fileMask = $tmpStr . '_maskNT.png';
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
                $command = trim($this->imageService->scalecmd . ' ' . $w . 'x' . $h . '! -negate');
            } else {
                $command = trim(($conf['niceText.']['before'] ?? '') . ' ' . $this->imageService->scalecmd . ' ' . $w . 'x' . $h . '! ' . ($conf['niceText.']['after'] ?? '') . ' -negate');
                if (isset($conf['niceText.']['sharpen'])) {
                    $command .= $this->imageService->v5_sharpen($conf['niceText.']['sharpen']);
                }
            }
            $this->imageService->imageMagickExec($fileMask, $fileMask, $command);
            // Make the color-file
            $colorImg = imagecreatetruecolor($w, $h);
            $Ccolor = imagecolorallocate($colorImg, $cols[0], $cols[1], $cols[2]);
            imagefilledrectangle($colorImg, 0, 0, $w, $h, $Ccolor);
            $this->ImageWrite($colorImg, $fileColor);
            imagedestroy($colorImg);
            // The mask is applied
            // The main pictures is saved temporarily
            $this->ImageWrite($im, $fileMenu);
            $this->imageService->combineExec($fileMenu, $fileColor, $fileMask, $fileMenu);
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

    /**
     * Implements the "OUTLINE" GIFBUILDER object / property for the TEXT object
     *
     * @param \GdImage $im GDlib image pointer
     * @param array $conf TypoScript array with configuration for the GIFBUILDER object.
     * @param array $workArea The current working area coordinates.
     * @param array $txtConf TypoScript array with configuration for the associated TEXT GIFBUILDER object.
     * @see make()
     * @see makeText()
     */
    protected function makeOutline(\GdImage &$im, array $conf, array $workArea, array $txtConf): void
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
     * Implements the "EMBOSS" GIFBUILDER object / property for the TEXT object
     *
     * @param \GdImage $im GDlib image pointer
     * @param array $conf TypoScript array with configuration for the GIFBUILDER object.
     * @param array $workArea The current working area coordinates.
     * @param array $txtConf TypoScript array with configuration for the associated TEXT GIFBUILDER object.
     * @see make()
     * @see makeShadow()
     */
    protected function makeEmboss(\GdImage &$im, array $conf, array $workArea, array $txtConf): void
    {
        $conf['color'] = $conf['highColor'];
        $this->makeShadow($im, $conf, $workArea, $txtConf);
        $newOffset = GeneralUtility::intExplode(',', (string)($conf['offset'] ?? ''));
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
     * @param \GdImage $im GDlib image pointer
     * @param array $conf TypoScript array with configuration for the GIFBUILDER object.
     * @param array $workArea The current working area coordinates.
     * @param array $txtConf TypoScript array with configuration for the associated TEXT GIFBUILDER object.
     * @see make()
     * @see makeText()
     * @see makeEmboss()
     * @internal
     */
    public function makeShadow(\GdImage &$im, array $conf, array $workArea, array $txtConf): void
    {
        $workArea = $this->applyOffset($workArea, GeneralUtility::intExplode(',', (string)($conf['offset'])));
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
            $tmpStr = $this->imageService->randomName();
            $fileMenu = $tmpStr . '_menu.png';
            $fileColor = $tmpStr . '_color.png';
            $fileMask = $tmpStr . '_mask.png';
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
            $command = $this->imageService->v5_blur($blurRate + 1);
            $this->imageService->imageMagickExec($fileMask, $fileMask, $command . ' +matte');
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
                $this->imageService->combineExec($fileMenu, $fileColor, $fileMask, $fileMenu);
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

    /**
     * Implements the "BOX" GIFBUILDER object
     *
     * @param \GdImage $im GDlib image pointer
     * @param array $conf TypoScript array with configuration for the GIFBUILDER object.
     * @param array $workArea The current working area coordinates.
     * @see make()
     * @internal
     */
    public function makeBox(\GdImage &$im, array $conf, array $workArea): void
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
     * @param \GdImage $im GDlib image pointer
     * @param array $conf TypoScript array with configuration for the GIFBUILDER object.
     * @param array $workArea The current working area coordinates.
     * @see make()
     */
    public function makeEllipse(\GdImage &$im, array $conf, array $workArea): void
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
     * @param \GdImage $im GDlib image pointer
     * @param array $conf TypoScript array with configuration for the GIFBUILDER object.
     * @see make()
     * @see applyImageMagickToPHPGif()
     */
    protected function makeEffect(\GdImage &$im, array $conf): void
    {
        $commands = $this->IMparams($conf['value']);
        if ($commands) {
            $this->applyImageMagickToPHPGif($im, $commands);
        }
    }

    /**
     * Implements the "ADJUST" GIFBUILDER object
     *
     * @param \GdImage $im GDlib image pointer
     * @param array $conf TypoScript array with configuration for the GIFBUILDER object.
     * @see make()
     * @see autoLevels()
     * @see outputLevels()
     * @see inputLevels()
     */
    protected function adjust(\GdImage &$im, array $conf): void
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
     * @param \GdImage $im GDlib image pointer
     * @param array $conf TypoScript array with configuration for the GIFBUILDER object.
     * @see make()
     */
    protected function crop(\GdImage &$im, array $conf): void
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
     * @param \GdImage $im GDlib image pointer
     * @param array $conf TypoScript array with configuration for the GIFBUILDER object.
     * @see make()
     */
    protected function scale(\GdImage &$im, array $conf): void
    {
        // @todo: not covered with tests
        if (isset($conf['width']) || isset($conf['height']) || isset($conf['params'])) {
            $tmpStr = $this->imageService->randomName();
            $theFile = $tmpStr . '.png';
            $this->ImageWrite($im, $theFile);
            $theNewFile = $this->imageService->resize($theFile, 'png', $conf['width'] ?? '', $conf['height'] ?? '', $conf['params'] ?? '');
            if ($theNewFile->isFile()) {
                $tmpImg = $this->imageCreateFromFile($theNewFile->getRealPath());
                if ($tmpImg) {
                    imagedestroy($im);
                    $im = $tmpImg;
                    $this->w = imagesx($im);
                    $this->h = imagesy($im);
                    // Clears workArea to total image
                    $this->setWorkArea('');
                }
                unlink($theFile);
                if ($theNewFile->getRealPath() !== $theFile) {
                    unlink($theNewFile->getRealPath());
                }
            }
        }
    }

    /**
     * Implements the "WORKAREA" GIFBUILDER object when setting it
     * Setting internal working area boundaries (->workArea)
     *
     * @param string $workArea Working area dimensions, comma separated
     * @internal
     * @see make()
     */
    protected function setWorkArea(string $workArea): void
    {
        $this->workArea = GeneralUtility::intExplode(',', $workArea);
        $this->workArea = $this->applyOffset($this->workArea, $this->offset);
        if (!($this->workArea[2] ?? false)) {
            $this->workArea[2] = $this->w;
        }
        if (!($this->workArea[3] ?? false)) {
            $this->workArea[3] = $this->h;
        }
    }

    /*********************************************
     *
     * Various helper functions
     *
     ********************************************/
    /**
     * Initializing/Cleaning of TypoScript properties for TEXT GIFBUILDER objects
     *
     * 'cleans' TEXT-object; Checks fontfile and other vital setup
     * Finds the title if its a 'variable' (instantiates a cObj and loads it with the ->data record)
     * Performs caseshift if any.
     *
     * @param array $conf GIFBUILDER object TypoScript properties
     * @return array|null Modified $conf array IF the "text" property is not blank
     */
    protected function checkTextObj(array $conf): ?array
    {
        $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $cObj->start($this->data);
        $isStdWrapped = [];
        foreach ($conf as $key => $value) {
            $parameter = rtrim($key, '.');
            if (!($isStdWrapped[$parameter] ?? false) && isset($conf[$parameter . '.'])) {
                $conf[$parameter] = $cObj->stdWrapValue($parameter, $conf);
                $isStdWrapped[$parameter] = 1;
            }
        }

        if (!is_null($conf['fontFile'] ?? null)) {
            $conf['fontFile'] = $this->checkFile($conf['fontFile']);
        }
        if (!($conf['fontFile'] ?? false)) {
            $conf['fontFile'] = $this->checkFile('EXT:core/Resources/Private/Font/nimbus.ttf');
        }
        if (!($conf['iterations'] ?? false)) {
            $conf['iterations'] = 1;
        }
        if (!($conf['fontSize'] ?? false)) {
            $conf['fontSize'] = 12;
        }
        // If any kind of spacing applies, we cannot use angles!!
        if (($conf['spacing'] ?? false) || ($conf['wordSpacing'] ?? false)) {
            $conf['angle'] = 0;
        }
        if (!isset($conf['antiAlias'])) {
            $conf['antiAlias'] = 1;
        }
        $conf['fontColor'] = trim($conf['fontColor'] ?? '');
        // Strip HTML
        if (!($conf['doNotStripHTML'] ?? false)) {
            $conf['text'] = strip_tags($conf['text'] ?? '');
        }
        $this->combinedTextStrings[] = strip_tags($conf['text'] ?? '');
        // Max length = 100 if automatic line breaks are not defined:
        if (!isset($conf['breakWidth']) || !$conf['breakWidth']) {
            $tlen = (int)($conf['textMaxLength'] ?? 0) ?: 100;
            $conf['text'] = mb_substr($conf['text'], 0, $tlen, 'utf-8');
        }
        if ((string)$conf['text'] != '') {
            // Char range map thingie:
            $fontBaseName = PathUtility::basename($conf['fontFile']);
            if (is_array($this->charRangeMap[$fontBaseName] ?? null)) {
                // Initialize splitRendering array:
                if (!is_array($conf['splitRendering.'])) {
                    $conf['splitRendering.'] = [];
                }
                $cfgK = $this->charRangeMap[$fontBaseName]['cfgKey'];
                // Do not impose settings if a splitRendering object already exists:
                if (!isset($conf['splitRendering.'][$cfgK])) {
                    // Set configuration:
                    $conf['splitRendering.'][$cfgK] = 'charRange';
                    $conf['splitRendering.'][$cfgK . '.'] = $this->charRangeMap[$fontBaseName]['charMapConfig'];
                    // Multiplicator of fontsize:
                    if ($this->charRangeMap[$fontBaseName]['multiplicator']) {
                        $conf['splitRendering.'][$cfgK . '.']['fontSize'] = round($conf['fontSize'] * $this->charRangeMap[$fontBaseName]['multiplicator']);
                    }
                    // Multiplicator of pixelSpace:
                    if ($this->charRangeMap[$fontBaseName]['pixelSpace']) {
                        $travKeys = ['xSpaceBefore', 'xSpaceAfter', 'ySpaceBefore', 'ySpaceAfter'];
                        foreach ($travKeys as $pxKey) {
                            if (isset($conf['splitRendering.'][$cfgK . '.'][$pxKey])) {
                                $conf['splitRendering.'][$cfgK . '.'][$pxKey] = round($conf['splitRendering.'][$cfgK . '.'][$pxKey] * ($conf['fontSize'] / $this->charRangeMap[$fontBaseName]['pixelSpace']));
                            }
                        }
                    }
                }
            }
            if (is_array($conf['splitRendering.'] ?? null)) {
                foreach ($conf['splitRendering.'] as $key => $value) {
                    if (is_array($conf['splitRendering.'][$key])) {
                        if (isset($conf['splitRendering.'][$key]['fontFile'])) {
                            $conf['splitRendering.'][$key]['fontFile'] = $this->checkFile($conf['splitRendering.'][$key]['fontFile']);
                        }
                    }
                }
            }
            return $conf;
        }
        return null;
    }

    /**
     * Calculation of offset using "splitCalc" and insertion of dimensions from other GIFBUILDER objects.
     *
     * Example:
     * Input: 2+2, 2*3, 123, [10.w]
     * Output: 4,6,123,45  (provided that the width of object in position 10 was 45 pixels wide)
     *
     * @param string $string The string to resolve/calculate the result of. The string is divided by a comma first and each resulting part is calculated into an integer.
     * @return string The resolved string with each part (separated by comma) returned separated by comma
     * @internal
     */
    public function calcOffset(string $string): string
    {
        $value = [];
        $numbers = GeneralUtility::trimExplode(',', $this->calculateFunctions($string));
        foreach ($numbers as $key => $val) {
            if ((string)$val == (string)(int)$val) {
                $value[$key] = (int)$val;
            } else {
                $value[$key] = $this->calculateValue($val);
            }
        }
        $string = implode(',', $value);
        return $string;
    }

    /**
     * Returns an "imgResource" creating an instance of the ContentObjectRenderer class and calling ContentObjectRenderer::getImgResource
     *
     * @param string|File $file Filename value OR the string "GIFBUILDER", see documentation in TSref for the "datatype" called "imgResource" - can also be a FAL file
     * @param array $fileArray TypoScript properties passed to the function. Either GIFBUILDER properties or imgResource properties, depending on the value of $file (whether that is "GIFBUILDER" or a file reference)
     * @return ImageResource|null Returns the ImageResource DTO with file information from ContentObjectRenderer::getImgResource() - or NULL
     * @see ContentObjectRenderer::getImgResource()
     */
    protected function getResource(string|File $file, array $fileArray): ?ImageResource
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $deferProcessing = !$context->hasAspect('fileProcessing') || $context->getPropertyFromAspect('fileProcessing', 'deferProcessing');
        $context->setAspect('fileProcessing', new FileProcessingAspect(false));
        try {
            if (!in_array($fileArray['ext'] ?? '', $this->imageService->getImageFileExt(), true)) {
                $fileArray['ext'] = 'png';
            }
            $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
            $cObj->start($this->data);
            return $cObj->getImgResource($file, $fileArray);
        } finally {
            $context->setAspect('fileProcessing', new FileProcessingAspect($deferProcessing));
        }
    }

    /**
     * Returns the reference to a "resource" in TypoScript.
     *
     * @param string $file The resource value.
     * @return string|null Returns the relative filepath or null if it's invalid
     */
    protected function checkFile(string $file): ?string
    {
        try {
            return GeneralUtility::makeInstance(FilePathSanitizer::class)->sanitize($file, true);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Calculates the GIFBUILDER output filename based on a serialized, hashed
     * value of this->setup and prefixes the original filename.
     * The filename gets an additional prefix (max 100 characters),
     * something like "GB_MD5HASH_myfilename_is_very_long_and_such.jpg".
     */
    protected function fileName(): string
    {
        $basicFileFunctions = GeneralUtility::makeInstance(BasicFileUtility::class);
        $filePrefix = implode('_', array_merge($this->combinedTextStrings, $this->combinedFileNames));
        $filePrefix = $basicFileFunctions->cleanFileName(ltrim($filePrefix, '.'));

        // shorten prefix to avoid overly long file names
        $filePrefix = substr($filePrefix, 0, 100);

        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);

        // we use ConfigurationService::serialize here to use as much of $this->setup as possible,
        // but preventing inclusion of objects that could cause problems with json_encode
        $hashInputForFileName = [
            $configurationService->serialize($this->setup),
            $filePrefix,
            $this->XY,
            $this->w,
            $this->h,
            $this->offset,
            $this->workArea,
            $this->combinedTextStrings,
            $this->combinedFileNames,
            $this->data,
        ];
        return $filePrefix . '_' . md5((string)json_encode($hashInputForFileName)) . '.' . $this->extension();
    }

    /**
     * Returns the file extension used in the filename
     */
    protected function extension(): string
    {
        return match (strtolower($this->setup['format'] ?? '')) {
            'jpg', 'jpeg' => 'jpg',
            'gif' => 'gif',
            'webp' => 'webp',
            'avif' => 'avif',
            default => 'png',
        };
    }

    /**
     * Calculates the value concerning the dimensions of objects.
     *
     * @param string $string The string to be calculated (e.g. "[20.h]+13")
     * @return int The calculated value (e.g. "23")
     * @see calcOffset()
     */
    protected function calculateValue(string $string): int
    {
        $calculatedValue = 0;
        $parts = GeneralUtility::splitCalc($string, '+-*/%');
        foreach ($parts as $part) {
            $theVal = $part[1];
            $sign = $part[0];
            if (((string)(int)$theVal) == ((string)$theVal)) {
                $theVal = (int)$theVal;
            } elseif ('[' . substr($theVal, 1, -1) . ']' == $theVal) {
                $objParts = explode('.', substr($theVal, 1, -1));
                $theVal = 0;
                if (isset($this->objBB[$objParts[0]], $objParts[1])) {
                    if ($objParts[1] === 'w' && isset($this->objBB[$objParts[0]][0])) {
                        $theVal = $this->objBB[$objParts[0]][0];
                    } elseif ($objParts[1] === 'h' && isset($this->objBB[$objParts[0]][1])) {
                        $theVal = $this->objBB[$objParts[0]][1];
                    } elseif ($objParts[1] === 'lineHeight' && isset($this->objBB[$objParts[0]][2]['lineHeight'])) {
                        $theVal = $this->objBB[$objParts[0]][2]['lineHeight'];
                    }
                    $theVal = (int)$theVal;
                }
            } elseif ((float)$theVal) {
                $theVal = (float)$theVal;
            } else {
                $theVal = 0;
            }
            if ($sign === '-') {
                $calculatedValue -= $theVal;
            } elseif ($sign === '+') {
                $calculatedValue += $theVal;
            } elseif ($sign === '/' && $theVal) {
                $calculatedValue /= $theVal;
            } elseif ($sign === '*') {
                $calculatedValue *= $theVal;
            } elseif ($sign === '%' && $theVal) {
                $calculatedValue %= $theVal;
            }
        }
        return (int)round($calculatedValue);
    }

    /**
     * Calculates special functions:
     * + max([10.h], [20.h])	-> gets the maximum of the given values
     *
     * @param string $string The raw string with functions to be calculated
     * @return string The calculated values
     */
    protected function calculateFunctions(string $string): string
    {
        if (preg_match_all('#max\\(([^)]+)\\)#', $string, $matches)) {
            foreach ($matches[1] as $index => $maxExpression) {
                $string = str_replace($matches[0][$index], (string)$this->calculateMaximum($maxExpression), $string);
            }
        }
        return $string;
    }

    /**
     * Calculates the maximum of a set of values defined like "[10.h],[20.h],1000"
     *
     * @param string $value The string to be used to calculate the maximum (e.g. "[10.h],[20.h],1000")
     * @return int The maximum value of the given comma separated and calculated values
     */
    protected function calculateMaximum(string $value): int
    {
        $parts = GeneralUtility::trimExplode(',', $this->calcOffset($value), true);
        return $parts !== [] ? (int)max($parts) : 0;
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
    protected function objPosition(array $conf, array $workArea, array $BB): array
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
        $result = $this->applyOffset($result, GeneralUtility::intExplode(',', (string)($conf['offset'] ?? '')));
        $result = $this->applyOffset($result, $workArea);
        return $result;
    }

    /**
     * Applies offset value to coordinated in $cords.
     * Basically the value of key 0/1 of $OFFSET is added to keys 0/1 of $cords
     *
     * @param array $cords Integer coordinates in key 0/1
     * @param array $offset Offset values in key 0/1
     * @return array Modified $cords array
     */
    protected function applyOffset(array $cords, array $offset): array
    {
        $cords[0] = (int)$cords[0] + (int)$offset[0];
        $cords[1] = (int)($cords[1] ?? 0) + (int)($offset[1] ?? 0);
        return $cords;
    }

    /**
     * Copies two GDlib image pointers onto each other, using TypoScript configuration from $conf and the input $workArea definition.
     *
     * @param \GdImage $im GDlib image pointer, destination (bottom image)
     * @param \GdImage $cpImg GDlib image pointer, source (top image)
     * @param array $conf TypoScript array with the properties for the IMAGE GIFBUILDER object. Only used for the "tile" property value.
     * @param array $workArea Work area
     */
    protected function copyGifOntoGif(\GdImage &$im, \GdImage &$cpImg, array $conf, array $workArea): void
    {
        $cpW = imagesx($cpImg);
        $cpH = imagesy($cpImg);
        $tile = GeneralUtility::intExplode(',', (string)($conf['tile'] ?? ''));
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
     * @param \GdImage $dstImg Destination image
     * @param \GdImage $srcImg Source image
     * @param int $dstX Destination x-coordinate
     * @param int $dstY Destination y-coordinate
     * @param int $srcX Source x-coordinate
     * @param int $srcY Source y-coordinate
     * @param int $dstWidth Destination width
     * @param int $dstHeight Destination height
     * @param int $srcWidth Source width
     * @param int $srcHeight Source height
     */
    protected function imagecopyresized(\GdImage &$dstImg, \GdImage &$srcImg, int $dstX, int $dstY, int $srcX, int $srcY, int $dstWidth, int $dstHeight, int $srcWidth, int $srcHeight): void
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

    /**
     * Creates some offset values in an array used to simulate a circularly applied outline around TEXT
     *
     * @param int $distance Distance
     * @param int $iterations Iterations.
     * @see makeOutline()
     */
    protected function circleOffset(int $distance, int $iterations): array
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
     * Creating ImageMagick parameters from TypoScript property
     *
     * @param string $setup A string with effect keywords=value pairs separated by "|
     * @return string ImageMagick prepared parameters.
     * @see makeEffect()
     */
    protected function IMparams(string $setup): string
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
                        $commands .= $this->imageService->v5_blur((int)$value);
                    }
                    break;
                case 'sharpen':
                    if ($this->processorEffectsEnabled) {
                        $commands .= $this->imageService->v5_sharpen((int)$value);
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
     * Returns the HEX color value for an RGB color array
     *
     * @param array $color RGB color array
     * @return string HEX color value
     */
    protected function hexColor(array $color): string
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
     * @param \GdImage $img Image resource
     * @param array $colArr Array containing RGB color arrays
     * @return int The index of the unified color
     */
    protected function unifyColors(\GdImage &$img, array $colArr, bool $closest): int
    {
        $retCol = -1;
        if ($colArr !== [] && function_exists('imagepng') && function_exists('imagecreatefrompng')) {
            $firstCol = array_shift($colArr);
            $firstColArr = $this->convertColor($firstCol);
            $origName = $preName = $this->imageService->randomName() . '.png';
            $postName = $this->imageService->randomName() . '.png';
            $tmpImg = null;
            if (count($colArr) > 1) {
                $this->ImageWrite($img, $preName);
                $firstCol = $this->hexColor($firstColArr);
                foreach ($colArr as $transparentColor) {
                    $transparentColor = $this->convertColor($transparentColor);
                    $transparentColor = $this->hexColor($transparentColor);
                    $cmd = '-fill "' . $firstCol . '" -opaque "' . $transparentColor . '"';
                    $this->imageService->imageMagickExec($preName, $postName, $cmd);
                    $preName = $postName;
                }
                $this->imageService->imageMagickExec($postName, $origName, '');
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
     * Converts a "HTML-color" TypoScript datatype to RGB-values.
     * Default is 0,0,0
     *
     * @param string $string "HTML-color" data type string, eg. 'red', '#ffeedd' or '255,0,255'. You can also add a modifying operator afterwards. There are two options: "255,0,255 : 20" - will add 20 to values, result is "255,20,255". Or "255,0,255 : *1.23" which will multiply all RGB values with 1.23
     * @return array RGB values in key 0/1/2 of the array
     */
    protected function convertColor(string $string): array
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
     * Calculates text position for printing the text onto the image based on configuration like alignment and workarea.
     *
     * @param array $conf TypoScript array for the TEXT GIFBUILDER object
     * @param array $workArea Work area definition
     * @param array $BB Bounding box information, was set in start()
     * @return array [0]=x, [1]=y, [2]=w, [3]=h
     * @see makeText()
     */
    protected function txtPosition(array $conf, array $workArea, array $BB): array
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
        $result = $this->applyOffset($result, GeneralUtility::intExplode(',', (string)($conf['offset'] ?? '')));
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
     * @see start()
     */
    public function calcBBox(array $conf): array
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
     * Printing text onto an image like the PHP function imageTTFText does but in addition it offers options for spacing of letters and words.
     * Spacing is done by printing one char at a time and this means that the spacing is rather uneven and probably not very nice.
     * See
     *
     * @param \GdImage $im (See argument for PHP function imageTTFtext())
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
     */
    protected function SpacedImageTTFText(\GdImage &$im, int $fontSize, int $angle, int $x, int $y, int $Fcolor, string $fontFile, string $text, int $spacing, int $wordSpacing, array $splitRenderingConf, int $sF = 1): void
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
     * @see start()
     */
    protected function fontResize(array $conf): int
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
    protected function ImageTTFBBoxWrapper(int $fontSize, int $angle, string $fontFile, string $string, array $splitRendering, int $sF = 1): array
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
     * @param \GdImage $im (See argument for PHP function imageTTFtext())
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
    protected function ImageTTFTextWrapper(\GdImage &$im, int $fontSize, int $angle, int $x, int $y, int $color, string $fontFile, string $string, array $splitRendering, int $sF = 1): void
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
    protected function splitString(string $string, array $splitRendering, int $fontSize, string $fontFile): array
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
                            $ranges[$i] = GeneralUtility::intExplode('-', $rangeDef);
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
        return $result;
    }

    /**
     * Calculates the spacing and wordSpacing values
     *
     * @param array $conf TypoScript array for the TEXT GIFBUILDER object
     * @param int $scaleFactor TypoScript value from eg $conf['niceText.']['scaleFactor']
     * @return array Array with two keys [0]/[1] being array($spacing,$wordSpacing)
     * @see calcBBox()
     */
    protected function calcWordSpacing(array $conf, int $scaleFactor = 1): array
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
     */
    protected function getTextScalFactor(array $conf): int
    {
        if (!($conf['niceText'] ?? false)) {
            $sF = 1;
        } else {
            $sF = MathUtility::forceIntegerInRange(($conf['niceText.']['scaleFactor'] ?? 2), 2, 5);
        }
        return $sF;
    }

    /**
     * Renders a regular text and takes care of a possible line break automatically.
     *
     * @param \GdImage $im (See argument for PHP function imageTTFtext())
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
    protected function renderTTFText(\GdImage &$im, int $fontSize, int $angle, int $x, int $y, int $color, string $fontFile, string $string, array $splitRendering, array $conf, int $sF = 1): void
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
     */
    protected function getWordPairsForLineBreak(string $string): array
    {
        $wordPairs = [];
        $wordsArray = preg_split('#([- .,!:]+)#', $string, -1, PREG_SPLIT_DELIM_CAPTURE);
        $wordsArray = is_array($wordsArray) ? $wordsArray : [];
        $wordsCount = count($wordsArray);
        for ($index = 0; $index < $wordsCount; $index += 2) {
            $wordPairs[] = $wordsArray[$index] . ($wordsArray[$index + 1] ?? '');
        }
        return $wordPairs;
    }

    /**
     * Gets the rendered text width
     */
    protected function getRenderedTextWidth(string $text, array $conf): int
    {
        $bounds = $this->ImageTTFBBoxWrapper($conf['fontSize'], $conf['angle'], $conf['fontFile'], $text, $conf['splitRendering.']);
        if ($conf['angle'] < 0) {
            $pixelWidth = abs($bounds[4] - $bounds[0]);
        } elseif ($conf['angle'] > 0) {
            $pixelWidth = abs($bounds[2] - $bounds[6]);
        } else {
            $pixelWidth = abs($bounds[4] - $bounds[6]);
        }
        return (int)$pixelWidth;
    }

    /**
     * Gets the break space for each new line.
     *
     * @param array $conf TypoScript configuration for the currently rendered object
     * @param array $boundingBox The bounding box for the currently rendered object
     * @return int The break space
     */
    protected function getBreakSpace(array $conf, array $boundingBox = []): int
    {
        if ($boundingBox === []) {
            $boundingBox = $this->calcBBox($conf);
            $boundingBox = $boundingBox[2];
        }
        if (isset($conf['breakSpace']) && $conf['breakSpace']) {
            $breakSpace = $boundingBox['lineHeight'] * $conf['breakSpace'];
        } else {
            $breakSpace = $boundingBox['lineHeight'];
        }
        return (int)$breakSpace;
    }

    /**
     * Function to compensate for DPI resolution.
     * FreeType 2 always has 96 dpi, so it is hard-coded at this place.
     *
     * @param float $fontSize font size for freetype function call
     * @return float compensated font size based on 96 dpi
     */
    protected function compensateFontSizeiBasedOnFreetypeDpi(float $fontSize): float
    {
        return $fontSize / 96.0 * 72;
    }

    /*************************
     *
     * Adjustment functions
     *
     ************************/
    /**
     * Apply auto-levels to input image pointer
     *
     * @param \GdImage $im GDlib Image Pointer
     */
    protected function autolevels(\GdImage &$im): void
    {
        $totalCols = imagecolorstotal($im);
        $grayArr = [];
        for ($c = 0; $c < $totalCols; $c++) {
            $cols = imagecolorsforindex($im, $c);
            $grayArr[] = (int)round(($cols['red'] + $cols['green'] + $cols['blue']) / 3);
        }
        $min = min($grayArr);
        $max = max($grayArr);
        $delta = $max - $min;
        if ($delta) {
            for ($c = 0; $c < $totalCols; $c++) {
                $cols = imagecolorsforindex($im, $c);
                $cols['red'] = (int)floor(($cols['red'] - $min) / $delta * 255);
                $cols['green'] = (int)floor(($cols['green'] - $min) / $delta * 255);
                $cols['blue'] = (int)floor(($cols['blue'] - $min) / $delta * 255);
                imagecolorset($im, $c, $cols['red'], $cols['green'], $cols['blue']);
            }
        }
    }

    /**
     * Apply output levels to input image pointer (decreasing contrast)
     *
     * @param \GdImage $im GDlib Image Pointer
     * @param int $low The "low" value (close to 0)
     * @param int $high The "high" value (close to 255)
     */
    protected function outputLevels(\GdImage &$im, int $low, int $high): void
    {
        if ($low < $high) {
            $low = MathUtility::forceIntegerInRange($low, 0, 255);
            $high = MathUtility::forceIntegerInRange($high, 0, 255);
            $delta = $high - $low;
            $totalCols = imagecolorstotal($im);
            for ($c = 0; $c < $totalCols; $c++) {
                $cols = imagecolorsforindex($im, $c);
                $cols['red'] = $low + floor($cols['red'] / 255 * $delta);
                $cols['green'] = $low + floor($cols['green'] / 255 * $delta);
                $cols['blue'] = $low + floor($cols['blue'] / 255 * $delta);
                imagecolorset($im, $c, (int)$cols['red'], (int)$cols['green'], (int)$cols['blue']);
            }
        }
    }

    /**
     * Apply input levels to input image pointer (increasing contrast)
     *
     * @param \GdImage $im GDlib Image Pointer
     * @param int $low The "low" value (close to 0)
     * @param int $high The "high" value (close to 255)
     */
    protected function inputLevels(\GdImage &$im, int $low, int $high): void
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
     * Applies an ImageMagick parameter to a GDlib image pointer resource by writing the resource to file,
     * performing an IM operation upon it and reading back the result into the ImagePointer.
     *
     * @param \GdImage $im The image pointer (reference)
     * @param string $command The ImageMagick parameters. Like effects, scaling etc.
     */
    protected function applyImageMagickToPHPGif(\GdImage &$im, string $command): void
    {
        $tmpStr = $this->imageService->randomName();
        $theFile = $tmpStr . '.png';
        $this->ImageWrite($im, $theFile);
        $this->imageService->imageMagickExec($theFile, $theFile, $command);
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
     * Writes the input GDlib image pointer to file
     *
     * @param \GdImage $destImg The GDlib image resource pointer
     * @param string $theImage The absolute file path to write to
     * @param int $quality The image quality (for JPEG, WebP and AVIF files)
     * @param int<-1,10> $speed The image speed (for AVIFs), 0 (slow, smaller file) to 10 (fast, larger file), -1 for default (=6)
     * @return bool The output of either imageGif, imagePng, imageJpeg, imagewebp or imageavif based on the filename to write
     * @see maskImageOntoImage()
     * @see scale()
     * @see output()
     */
    public function ImageWrite(\GdImage &$destImg, string $theImage, int $quality = 0, int $speed = -1): bool
    {
        imageinterlace($destImg, false);
        $ext = strtolower(substr($theImage, (int)strrpos($theImage, '.') + 1));
        $result = false;
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                if (function_exists('imagejpeg')) {
                    $result = imagejpeg($destImg, $theImage, ($quality ?: $this->jpegQuality));
                }
                break;
            case 'webp':
                if (function_exists('imagewebp')) {
                    $result = imagewebp($destImg, $theImage, ($quality ?: $this->webpQuality));
                }
                break;
            case 'avif':
                if (function_exists('imageavif')) {
                    $result = imageavif($destImg, $theImage, ($quality ?: $this->avifQuality), $speed);
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
     * @return \GdImage Image Resource pointer
     */
    public function imageCreateFromFile(string $sourceImg): \GdImage
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
            case 'webp':
                if (function_exists('imagecreatefromwebp')) {
                    return imagecreatefromwebp($sourceImg);
                }
                break;
            case 'avif':
                if (function_exists('imagecreatefromavif')) {
                    return imagecreatefromavif($sourceImg);
                }
                break;
        }
        // If none of the above:
        $imageInfo = GeneralUtility::makeInstance(ImageInfo::class, $sourceImg);
        $im = imagecreatetruecolor($imageInfo->getWidth(), $imageInfo->getHeight());
        $Bcolor = imagecolorallocate($im, 128, 128, 128);
        imagefilledrectangle($im, 0, 0, $imageInfo->getWidth(), $imageInfo->getHeight(), $Bcolor);
        return $im;
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
     * @internal will soon be renamed
     */
    public function getTemporaryImageWithText(string $filename, string $textline1, string $textline2 = '', string $textline3 = ''): void
    {
        if (!class_exists(\GdImage::class)) {
            throw new \RuntimeException('TYPO3 Fatal Error: No gdlib. ' . $textline1 . ' ' . $textline2 . ' ' . $textline3, 1270853952);
        }
        // Creates the basis for the error image
        $basePath = ExtensionManagementUtility::extPath('core') . 'Resources/Public/Images/';
        $im = imagecreatefrompng($basePath . 'NotFound.png');
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
        imagepng($im, $filename);
    }

    /**
     * @internal Only used for ext:install, not part of TYPO3 Core API.
     */
    public function getGraphicalFunctions(): GraphicalFunctions
    {
        return $this->imageService;
    }
}
