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
use TYPO3\CMS\Core\Imaging\GraphicsCanvas;
use TYPO3\CMS\Core\Imaging\ImageProcessingInstructions;
use TYPO3\CMS\Core\Imaging\ImageResource;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\Service\ConfigurationService;
use TYPO3\CMS\Core\Type\File\ImageInfo;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\File\BasicFileUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

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

    protected CharsetConverter $csConvObj;

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
        $gfxConf = $GLOBALS['TYPO3_CONF_VARS']['GFX'] ?? [];
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
        $transparentColor = trim((string)$this->cObj->stdWrapValue('transparentColor', $this->setup));
        $this->setup['transparentColor_array'] = $transparentColor !== '' ? explode('|', $transparentColor) : [];
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
     * @param \GdImage $gdImage The GDlib image resource pointer
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
        $this->w = $XY[0];
        $this->h = $XY[1];
        // Transparent layer as background if set and requirements are met
        if (($this->setup['backColor'] ?? '') === 'transparent' && (empty($this->setup['format']) || $this->setup['format'] === 'png')) {
            // Set internal properties to keep the transparency over the rendering process
            $this->saveAlphaLayer = true;
            // Force PNG in case no format is set
            $this->setup['format'] = 'png';
            $im = GraphicsCanvas::create($XY[0], $XY[1], 255, 255, 255, 127)->resource();
            $BGcols = [];
        } else {
            // Fill the background with the given color
            $BGcols = $this->convertColor($this->setup['backColor']);
            $im = GraphicsCanvas::create($XY[0], $XY[1], $BGcols[0], $BGcols[1], $BGcols[2])->resource();
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
                $canvas = GraphicsCanvas::load($im);
                $canvas->setTransparentColor($canvas->findClosestColor($BGcols[0], $BGcols[1], $BGcols[2]));
            } elseif (is_array($this->setup['transparentColor_array'])) {
                // Multiple transparent colors are set. This is done via the trick that all transparent colors get
                // converted to one color and then this one gets set as transparent as png/gif can just have one
                // transparent color.
                $Tcolor = $this->unifyColors($im, $this->setup['transparentColor_array'], (bool)($this->setup['transparentColor.']['closest'] ?? false));
                if ($Tcolor >= 0) {
                    GraphicsCanvas::load($im)->setTransparentColor($Tcolor);
                }
            }
        }
        return $im;
    }

    /**
     * Implements the "IMAGE" GIFBUILDER object, when the "mask" property is TRUE.
     * It reads the two images defined by $conf['file'] and $conf['mask'] and copies the $conf['file'] onto the input image pointer image using the $conf['mask'] as a grayscale mask
     *
     * @param \GdImage $im GDlib image pointer
     * @param array $conf TypoScript array with configuration for the GIFBUILDER object.
     * @param array $workArea The current working area coordinates.
     * @see make()
     */
    protected function maskImageOntoImage(\GdImage &$im, array $conf, array $workArea): void
    {
        if (!$conf['file'] || !$conf['mask']) {
            return;
        }
        $imgExt = strtolower(pathinfo($conf['file'], PATHINFO_EXTENSION));
        $maskExt = strtolower(pathinfo($conf['mask'], PATHINFO_EXTENSION));
        if (!GraphicsCanvas::canRead($imgExt) || !GraphicsCanvas::canRead($maskExt)) {
            return;
        }
        $imCanvas = GraphicsCanvas::load($im);
        $w = $imCanvas->width();
        $h = $imCanvas->height();
        // Build the overlay image
        $cpImg = $this->imageCreateFromFile($conf['file']);
        $overlayImg = $this->saveAlphaLayer
            ? GraphicsCanvas::create($w, $h, 255, 255, 255, 127)->resource()
            : GraphicsCanvas::create($w, $h, 0, 0, 0)->resource();
        $this->copyGifOntoGif($overlayImg, $cpImg, $conf, $workArea);
        // Build the mask image
        $cpImg = $this->imageCreateFromFile($conf['mask']);
        $maskImg = $this->saveAlphaLayer
            ? GraphicsCanvas::create($w, $h, 255, 255, 255, 127)->resource()
            : GraphicsCanvas::create($w, $h, 0, 0, 0)->resource();
        $this->copyGifOntoGif($maskImg, $cpImg, $conf, $workArea);
        // Composite in-memory — no temp files
        $imCanvas->compositeMasked(GraphicsCanvas::load($overlayImg), GraphicsCanvas::load($maskImg));
        if (!$this->saveAlphaLayer) {
            $imCanvas->setTransparentColor(-1);
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
        if (!$conf['file']) {
            return;
        }
        $ext = strtolower(pathinfo($conf['file'], PATHINFO_EXTENSION));
        if (!GraphicsCanvas::canRead($ext)) {
            return;
        }
        $cpImg = $this->imageCreateFromFile($conf['file']);
        $this->copyGifOntoGif($im, $cpImg, $conf, $workArea);
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
            $Fcolor = GraphicsCanvas::load($im)->allocateColor($cols[0], $cols[1], $cols[2]);
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
            $imCanvas = GraphicsCanvas::load($im);
            $w = $imCanvas->width();
            $h = $imCanvas->height();
            // Scalefactor
            $sF = MathUtility::forceIntegerInRange(($conf['niceText.']['scaleFactor'] ?? 2), 2, 5);
            $newW = (int)ceil($sF * $w);
            $newH = (int)ceil($sF * $h);
            // Make mask: white background, black foreground for text rendering
            $maskCanvas = GraphicsCanvas::create($newW, $newH, 255, 255, 255);
            $maskImg = $maskCanvas->resource();
            $Fcolor = $maskCanvas->allocateColor(0, 0, 0);
            // If any kind of spacing applies, we use this function:
            if ($spacing || $wordSpacing) {
                $this->SpacedImageTTFText($maskImg, $conf['fontSize'], $conf['angle'] ?? 0, $txtPos[0], $txtPos[1], $Fcolor, GeneralUtility::getFileAbsFileName($conf['fontFile']), $theText, $spacing, $wordSpacing, $conf['splitRendering.'], $sF);
            } else {
                $this->renderTTFText($maskImg, $conf['fontSize'], $conf['angle'] ?? 0, $txtPos[0], $txtPos[1], $Fcolor, $conf['fontFile'], $theText, $conf['splitRendering.'] ?? [], $conf, $sF);
            }
            // Downscale, negate and optionally sharpen the mask — all in-memory
            $maskCanvas->resizeTo($w, $h)->invert();
            if (isset($conf['niceText.']['sharpen'])) {
                $maskCanvas->sharpen((int)$conf['niceText.']['sharpen']);
            }
            // Build a solid color canvas and composite onto the main image
            $colorCanvas = GraphicsCanvas::create($w, $h, $cols[0], $cols[1], $cols[2]);
            $imCanvas->compositeMasked($colorCanvas, $maskCanvas);
            if (!$this->saveAlphaLayer) {
                $imCanvas->setTransparentColor(-1);
            }
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
     * The blurred shadow is composited through an in-memory mask.
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
        if (!$blurRate) {
            $txtConf['fontColor'] = $conf['color'];
            $this->makeText($im, $txtConf, $workArea);
        } else {
            $imCanvas = GraphicsCanvas::load($im);
            $w = $imCanvas->width();
            $h = $imCanvas->height();
            // Area around the blur used for cropping something
            $blurBorder = 3;
            // Solid color canvas for the shadow colour
            $bcols = $this->convertColor($conf['color']);
            $blurColCanvas = GraphicsCanvas::create($w, $h, $bcols[0], $bcols[1], $bcols[2]);
            // Render text white-on-black into a slightly larger canvas for the blur border
            $blurTextImg = GraphicsCanvas::create($w + $blurBorder * 2, $h + $blurBorder * 2, 0, 0, 0)->resource();
            $txtConf['fontColor'] = 'white';
            $blurBordArr = [$blurBorder, $blurBorder];
            $this->makeText($blurTextImg, $txtConf, $this->applyOffset($workArea, $blurBordArr));
            // Blur, crop the border, adjust levels/opacity — all in-memory
            $maskCanvas = GraphicsCanvas::load($blurTextImg)
                ->blur(max(1, (int)round($blurRate / 4) + 1))
                ->crop($blurBorder, $blurBorder, $w, $h);
            $intensity = 40;
            if ($conf['intensity'] ?? false) {
                $intensity = MathUtility::forceIntegerInRange($conf['intensity'], 0, 100);
            }
            $intensity = (int)ceil(255 - $intensity / 100 * 255);
            $maskCanvas->inputLevels(0, $intensity);
            $opacity = MathUtility::forceIntegerInRange((int)$conf['opacity'], 0, 100);
            if ($opacity && $opacity < 100) {
                $high = (int)ceil(255 * $opacity / 100);
                $maskCanvas->outputLevels(0, $high);
            }
            // Composite onto the main image in-memory
            $imCanvas->compositeMasked($blurColCanvas, $maskCanvas);
            if (!$this->saveAlphaLayer) {
                $imCanvas->setTransparentColor(-1);
            }
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
        $canvas = GraphicsCanvas::load($im);
        $tmpColor = $canvas->allocateColorAlpha($cols[0], $cols[1], $cols[2], $opacity);
        $canvas->fillRect($cords[0], $cords[1], $cords[2], $cords[3], $tmpColor);
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
        $canvas = GraphicsCanvas::load($im);
        $fillingColor = $canvas->allocateColor($color[0], $color[1], $color[2]);
        $canvas->fillEllipse($imageCoordinates[0], $imageCoordinates[1], $imageCoordinates[2], $imageCoordinates[3], $fillingColor);
    }

    /**
     * Implements the "EFFECT" GIFBUILDER object.
     *
     * @param \GdImage $im GDlib image pointer
     * @param array $conf TypoScript array with configuration for the GIFBUILDER object.
     * @see make()
     */
    protected function makeEffect(\GdImage &$im, array $conf): void
    {
        $setup = $conf['value'] ?? '';
        if (!trim($setup)) {
            return;
        }
        $canvas = GraphicsCanvas::load($im);
        $effects = explode('|', $setup);
        foreach ($effects as $val) {
            $pairs = explode('=', $val, 2);
            $value = trim($pairs[1] ?? '');
            $effect = strtolower(trim($pairs[0]));
            switch ($effect) {
                case 'blur':
                    $canvas->blur(max(1, (int)round(((int)$value + 1) / 4)));
                    break;
                case 'charcoal':
                    $canvas->charcoal(MathUtility::forceIntegerInRange((int)$value, 1, 5));
                    break;
                case 'colors':
                    $canvas->colors(MathUtility::forceIntegerInRange((int)$value, 2, 255));
                    break;
                case 'edge':
                    $canvas->edge();
                    break;
                case 'emboss':
                    $canvas->emboss();
                    break;
                case 'sharpen':
                    $canvas->sharpen((int)$value);
                    break;
                case 'gray':
                    $canvas->grayscale();
                    break;
                case 'invert':
                    $canvas->invert();
                    break;
                case 'gamma':
                    $canvas->gamma(1.0, max(0.1, min(10.0, (float)$value)));
                    break;
                case 'solarize':
                    $canvas->solarize(MathUtility::forceIntegerInRange((int)$value, 0, 99));
                    break;
                case 'swirl':
                    $canvas->swirl(MathUtility::forceIntegerInRange((int)$value, -720, 720));
                    break;
                case 'flip':
                    $canvas->flip();
                    break;
                case 'flop':
                    $canvas->flop();
                    break;
                case 'rotate':
                    $canvas->rotate(-(float)$value);
                    break;
                case 'shear':
                    $canvas->shear((int)$value);
                    break;
                case 'wave':
                    $params = GeneralUtility::intExplode(',', $value);
                    $canvas->wave(
                        MathUtility::forceIntegerInRange($params[0] ?? 0, 0, 99),
                        MathUtility::forceIntegerInRange($params[1] ?? 30, 1, 99),
                    );
                    break;
            }
        }
        $im = $canvas->resource();
        // rotate, shear and wave change the canvas size, so the current dimensions
        // have to be published for a following WORKAREA reset or CROP object.
        $this->w = $canvas->width();
        $this->h = $canvas->height();
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
        $canvas = GraphicsCanvas::load($im);
        $effects = explode('|', $setup);
        foreach ($effects as $val) {
            $pairs = explode('=', $val, 2);
            $value = trim($pairs[1] ?? '');
            $effect = strtolower(trim($pairs[0]));
            switch ($effect) {
                case 'inputlevels':
                    // low,high
                    $params = GeneralUtility::intExplode(',', $value);
                    $canvas->inputLevels($params[0], $params[1]);
                    break;
                case 'outputlevels':
                    $params = GeneralUtility::intExplode(',', $value);
                    $canvas->outputLevels($params[0], $params[1]);
                    break;
                case 'autolevels':
                    $canvas->autolevels();
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
        $cols = $this->convertColor(!empty($conf['backColor']) ? $conf['backColor'] : $this->setup['backColor']);
        $newIm = GraphicsCanvas::create($cords[2], $cords[3], $cols[0], $cols[1], $cols[2])->resource();
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
        $this->w = $cords[2];
        $this->h = $cords[3];
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
        if (!isset($conf['width']) && !isset($conf['height'])) {
            return;
        }
        $canvas = GraphicsCanvas::load($im);
        $instructions = ImageProcessingInstructions::fromCropScaleValues(
            $canvas->width(),
            $canvas->height(),
            $conf['width'] ?? '',
            $conf['height'] ?? '',
            [],
        );
        if ($instructions->width === $canvas->width() && $instructions->height === $canvas->height()) {
            return;
        }
        $im = $canvas->resizeTo($instructions->width, $instructions->height)->resource();
        $this->w = $instructions->width;
        $this->h = $instructions->height;
        $this->setWorkArea('');
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

        if (!($conf['fontFile'] ?? false)) {
            $conf['fontFile'] = 'EXT:core/Resources/Private/Font/nimbus.ttf';
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
            if (!GraphicsCanvas::canWrite($fileArray['ext'] ?? '')) {
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
        $cpCanvas = GraphicsCanvas::load($cpImg);
        $cpW = $cpCanvas->width();
        $cpH = $cpCanvas->height();
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
                                $im = $this->imagecopyresized($im, $cpImg, $Xstart, $Ystart, $cpImgCutX, $cpImgCutY, $w, $h, $w, $h);
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
    protected function imagecopyresized(\GdImage $dstImg, \GdImage &$srcImg, int $dstX, int $dstY, int $srcX, int $srcY, int $dstWidth, int $dstHeight, int $srcWidth, int $srcHeight): \GdImage
    {
        $dst = GraphicsCanvas::load($dstImg);
        $src = GraphicsCanvas::load($srcImg);
        if (!$this->saveAlphaLayer) {
            $tmp = GraphicsCanvas::create($dst->width(), $dst->height(), 0, 0, 0);
            $tmp->copyResized($dst, 0, 0, 0, 0, $dst->width(), $dst->height(), $dst->width(), $dst->height());
            $tmp->copyResized($src, $dstX, $dstY, $srcX, $srcY, $dstWidth, $dstHeight, $srcWidth, $srcHeight);
            return $tmp->resource();
        }
        $dst->copyResized($src, $dstX, $dstY, $srcX, $srcY, $dstWidth, $dstHeight, $srcWidth, $srcHeight);
        return $dstImg;
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
        if ($colArr === []) {
            return -1;
        }
        $canvas = GraphicsCanvas::load($img);
        $primary = $this->convertColor((string)array_shift($colArr));
        if (count($colArr) > 1) {
            $secondaries = [];
            foreach ($colArr as $colorString) {
                $secondaries[] = $this->convertColor((string)$colorString);
            }
            $canvas->remapColors($secondaries, $primary);
        }
        return $closest
            ? $canvas->findClosestColor($primary[0], $primary[1], $primary[2])
            : $canvas->findExactColor($primary[0], $primary[1], $primary[2]);
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
            foreach ($theBBoxInfo as &$value) {
                $value = ceil($value / $sF);
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
                $calc = GraphicsCanvas::measureText($this->compensateFontSizeiBasedOnFreetypeDpi($sF * $strCfg['fontSize']), $angle, $fontFile, $strCfg['str']);
                if ($calc === null) {
                    continue;
                }
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
                $colorIndex = GraphicsCanvas::load($im)->allocateColor($cols[0], $cols[1], $cols[2]);
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
                GraphicsCanvas::load($im)->renderText($this->compensateFontSizeiBasedOnFreetypeDpi($sF * $strCfg['fontSize']), $angle, $x, $y, $colorIndex, $fontFile, $strCfg['str']);
                // Calculate offset to apply:
                $wordInf = GraphicsCanvas::measureText($this->compensateFontSizeiBasedOnFreetypeDpi($sF * $strCfg['fontSize']), $angle, GeneralUtility::getFileAbsFileName($strCfg['fontFile']), $strCfg['str']);
                if ($wordInf !== null) {
                    $x += $wordInf[2] - $wordInf[0] + (int)($splitRendering['compX'] ?? 0) + (int)($strCfg['xSpaceAfter'] ?? 0);
                    $y += $wordInf[5] - $wordInf[7] - (int)($splitRendering['compY'] ?? 0) - (int)($strCfg['ySpaceAfter'] ?? 0);
                }
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
        $ext = strtolower(substr($theImage, (int)strrpos($theImage, '.') + 1));
        if (!GraphicsCanvas::canWrite($ext)) {
            return false;
        }
        $resolvedQuality = match ($ext) {
            'jpg', 'jpeg' => $quality ?: $this->jpegQuality,
            'webp' => $quality ?: $this->webpQuality,
            'avif' => $quality ?: $this->avifQuality,
            default => $quality,
        };
        // saveToFile() fixes the file permissions itself.
        return GraphicsCanvas::load($destImg)
            ->setInterlace(false)
            ->saveToFile($theImage, $ext, $resolvedQuality, $speed);
    }

    /**
     * Creates a new GDlib image resource based on the input image filename.
     * If it fails creating an image from the input file a blank gray image with the dimensions of the input image will be created instead.
     *
     * @param string $sourceImg Image filename
     */
    public function imageCreateFromFile(string $sourceImg): \GdImage|false
    {
        $canvas = GraphicsCanvas::loadFile($sourceImg, $this->saveAlphaLayer);
        return $canvas?->resource() ?? $this->createFallbackImage($sourceImg);
    }

    /**
     * Create a blank gray fallback image with the dimensions of the source file.
     */
    private function createFallbackImage(string $sourceImg): \GdImage|false
    {
        $size = @getimagesize($sourceImg);
        if ($size === false || $size[0] <= 0 || $size[1] <= 0) {
            return false;
        }
        return GraphicsCanvas::create($size[0], $size[1], 128, 128, 128)->resource();
    }
}
