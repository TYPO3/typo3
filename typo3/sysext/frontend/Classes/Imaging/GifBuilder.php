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

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\FileProcessingAspect;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\File\BasicFileUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Resource\FilePathSanitizer;

/**
 * GIFBUILDER
 *
 * Generating gif/png-files from TypoScript
 * Used by the menu-objects and imgResource in TypoScript.
 *
 * This class allows for advanced rendering of images with various layers of images, text and graphical primitives.
 * The concept is known from TypoScript as "GIFBUILDER" where you can define a "numerical array" (TypoScript term as well) of "GIFBUILDER OBJECTS" (like "TEXT", "IMAGE", etc.) and they will be rendered onto an image one by one.
 * The name "GIFBUILDER" comes from the time where GIF was the only file format supported. PNG is just as well to create today (configured with TYPO3_CONF_VARS[GFX])
 * Not all instances of this class is truly building gif/png files by layers; You may also see the class instantiated for the purpose of using the scaling functions in the parent class.
 *
 * Here is an example of how to use this class (from tslib_content.php, function getImgResource):
 *
 * $gifCreator = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Imaging\GifBuilder::class);
 * $gifCreator->init();
 * $theImage='';
 * if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib']) {
 * $gifCreator->start($fileArray, $this->data);
 * $theImage = $gifCreator->gifBuild();
 * }
 * return $gifCreator->getImageDimensions($theImage);
 */
class GifBuilder extends GraphicalFunctions
{
    /**
     * Contains all text strings used on this image
     *
     * @var array
     */
    public $combinedTextStrings = [];

    /**
     * Contains all filenames (basename without extension) used on this image
     *
     * @var array
     */
    public $combinedFileNames = [];

    /**
     * This is the array from which data->field: [key] is fetched. So this is the current record!
     *
     * @var array
     */
    public $data = [];

    /**
     * @var array
     */
    public $objBB = [];

    /**
     * @var string
     */
    public $myClassName = 'gifbuilder';

    /**
     * @var array
     */
    public $charRangeMap = [];

    /**
     * @var int[]
     */
    public $XY = [];

    /**
     * @var ContentObjectRenderer
     * @deprecated Set to protected in v12.
     * @todo: Signature in v12: protected ?ContentObjectRenderer $cObj = null;
     */
    public $cObj;

    /**
     * @var array
     */
    public $defaultWorkArea = [];

    /**
     * Initialization of the GIFBUILDER objects, in particular TEXT and IMAGE. This includes finding the bounding box, setting dimensions and offset values before the actual rendering is started.
     * Modifies the ->setup, ->objBB internal arrays
     * Should be called after the ->init() function which initializes the parent class functions/variables in general.
     *
     * @param array $conf TypoScript properties for the GIFBUILDER session. Stored internally in the variable ->setup
     * @param array $data The current data record from \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer. Stored internally in the variable ->data
     * @see ContentObjectRenderer::getImgResource()
     */
    public function start($conf, $data)
    {
        if (is_array($conf)) {
            $this->setup = $conf;
            $this->data = $data;
            $this->cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
            $this->cObj->start($this->data);
            // Hook preprocess gifbuilder conf
            // Added by Julle for 3.8.0
            //
            // Lets you pre-process the gifbuilder configuration. for
            // example you can split a string up into lines and render each
            // line as TEXT obj, see extension julle_gifbconf
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_gifbuilder.php']['gifbuilder-ConfPreProcess'] ?? [] as $_funcRef) {
                $_params = $this->setup;
                $ref = $this; // introduced for phpstan to not lose type information when passing $this into callUserFunction
                $this->setup = GeneralUtility::callUserFunction($_funcRef, $_params, $ref);
            }
            // Initializing global Char Range Map
            $this->charRangeMap = [];
            if (($GLOBALS['TSFE'] ?? null) instanceof TypoScriptFrontendController && is_array($GLOBALS['TSFE']->tmpl->setup['_GIFBUILDER.']['charRangeMap.'] ?? null)) {
                foreach ($GLOBALS['TSFE']->tmpl->setup['_GIFBUILDER.']['charRangeMap.'] as $cRMcfgkey => $cRMcfg) {
                    if (is_array($cRMcfg)) {
                        // Initializing:
                        $cRMkey = $GLOBALS['TSFE']->tmpl->setup['_GIFBUILDER.']['charRangeMap.'][substr($cRMcfgkey, 0, -1)];
                        $this->charRangeMap[$cRMkey] = [];
                        $this->charRangeMap[$cRMkey]['charMapConfig'] = $cRMcfg['charMapConfig.'] ?? [];
                        $this->charRangeMap[$cRMkey]['cfgKey'] = substr($cRMcfgkey, 0, -1);
                        $this->charRangeMap[$cRMkey]['multiplicator'] = (double)$cRMcfg['fontSizeMultiplicator'];
                        $this->charRangeMap[$cRMkey]['pixelSpace'] = (int)$cRMcfg['pixelSpaceFontSizeRef'];
                    }
                }
            }
            // Getting sorted list of TypoScript keys from setup.
            $sKeyArray = ArrayUtility::filterAndSortByNumericKeys($this->setup);
            // Setting the background color, passing it through stdWrap
            $this->setup['backColor'] = $this->cObj->stdWrapValue('backColor', $this->setup ?? []);
            if (!$this->setup['backColor']) {
                $this->setup['backColor'] = 'white';
            }
            $this->setup['transparentColor_array'] = explode('|', trim((string)$this->cObj->stdWrapValue('transparentColor', $this->setup ?? [])));
            $this->setup['transparentBackground'] = $this->cObj->stdWrapValue('transparentBackground', $this->setup ?? []);
            $this->setup['reduceColors'] = $this->cObj->stdWrapValue('reduceColors', $this->setup ?? []);
            // Set default dimensions
            $this->setup['XY'] = $this->cObj->stdWrapValue('XY', $this->setup ?? []);
            if (!$this->setup['XY']) {
                $this->setup['XY'] = '120,50';
            }
            // Checking TEXT and IMAGE objects for files. If any errors the objects are cleared.
            // The Bounding Box for the objects is stored in an array
            foreach ($sKeyArray as $theKey) {
                $theValue = $this->setup[$theKey];
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
                                $this->setup[$theKey . '.']['imgMap'] = 0;
                            }
                            break;
                        case 'IMAGE':
                            $fileInfo = $this->getResource($conf['file'] ?? '', $conf['file.'] ?? []);
                            if ($fileInfo) {
                                $this->combinedFileNames[] = preg_replace('/\\.[[:alnum:]]+$/', '', PathUtility::basename($fileInfo[3]));
                                if (($fileInfo['processedFile'] ?? null) instanceof ProcessedFile) {
                                    // Use processed file, if a FAL file has been processed by GIFBUILDER (e.g. scaled/cropped)
                                    $this->setup[$theKey . '.']['file'] = $fileInfo['processedFile']->getForLocalProcessing(false);
                                } elseif (!isset($fileInfo['origFile']) && ($fileInfo['originalFile'] ?? null) instanceof File) {
                                    // Use FAL file with getForLocalProcessing to circumvent problems with umlauts, if it is a FAL file (origFile not set)
                                    /** @var File $originalFile */
                                    $originalFile = $fileInfo['originalFile'];
                                    $this->setup[$theKey . '.']['file'] = $originalFile->getForLocalProcessing(false);
                                } else {
                                    // Use normal path from fileInfo if it is a non-FAL file (even non-FAL files have originalFile set, but only non-FAL files have origFile set)
                                    $this->setup[$theKey . '.']['file'] = $fileInfo[3];
                                }

                                // only pass necessary parts of fileInfo further down, to not incorporate facts as
                                // CropScaleMask runs in this request, that may not occur in subsequent calls and change
                                // the md5 of the generated file name
                                $essentialFileInfo = $fileInfo;
                                unset($essentialFileInfo['originalFile'], $essentialFileInfo['processedFile']);

                                $this->setup[$theKey . '.']['BBOX'] = $essentialFileInfo;
                                $this->objBB[$theKey] = $essentialFileInfo;
                                if ($conf['mask'] ?? false) {
                                    $maskInfo = $this->getResource($conf['mask'], $conf['mask.'] ?? []);
                                    if ($maskInfo) {
                                        // the same selection criteria as regarding fileInfo above apply here
                                        if (($maskInfo['processedFile'] ?? null) instanceof ProcessedFile) {
                                            $this->setup[$theKey . '.']['mask'] = $maskInfo['processedFile']->getForLocalProcessing(false);
                                        } elseif (!isset($maskInfo['origFile']) && $maskInfo['originalFile'] instanceof File) {
                                            $originalFile = $maskInfo['originalFile'];
                                            $this->setup[$theKey . '.']['mask'] = $originalFile->getForLocalProcessing(false);
                                        } else {
                                            $this->setup[$theKey . '.']['mask'] = $maskInfo[3];
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
                    // Checks if disabled is set... (this is also done in menu.php / imgmenu!!)
                    if ($conf['if.'] ?? false) {
                        $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
                        $cObj->start($this->data);
                        if (!$cObj->checkIf($conf['if.'])) {
                            unset($this->setup[$theKey]);
                            unset($this->setup[$theKey . '.']);
                            unset($this->objBB[$theKey]);
                        }
                    }
                }
            }
            // Calculate offsets on elements
            $this->setup['XY'] = $this->calcOffset($this->setup['XY']);
            $this->setup['offset'] = (string)$this->cObj->stdWrapValue('offset', $this->setup ?? []);
            $this->setup['offset'] = $this->calcOffset($this->setup['offset']);
            $this->setup['workArea'] = (string)$this->cObj->stdWrapValue('workArea', $this->setup ?? []);
            $this->setup['workArea'] = $this->calcOffset($this->setup['workArea']);
            foreach ($sKeyArray as $theKey) {
                $theValue = $this->setup[$theKey];
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
            $maxWidth = (int)$this->cObj->stdWrapValue('maxWidth', $this->setup ?? []);
            $maxHeight = (int)$this->cObj->stdWrapValue('maxHeight', $this->setup ?? []);
            $XY[0] = MathUtility::forceIntegerInRange($XY[0], 1, $maxWidth ?: 2000);
            $XY[1] = MathUtility::forceIntegerInRange($XY[1], 1, $maxHeight ?: 2000);
            $this->XY = $XY;
            $this->w = $XY[0];
            $this->h = $XY[1];
            $this->OFFSET = GeneralUtility::intExplode(',', $this->setup['offset']);
            // this sets the workArea
            $this->setWorkArea($this->setup['workArea']);
            // this sets the default to the current;
            $this->defaultWorkArea = $this->workArea;
        }
    }

    /**
     * Initiates the image file generation if ->setup is TRUE and if the file did not exist already.
     * Gets filename from fileName() and if file exists in typo3temp/assets/images/ dir it will - of course - not be rendered again.
     * Otherwise rendering means calling ->make(), then ->output(), then ->destroy()
     *
     * @return string The filename for the created GIF/PNG file. The filename will be prefixed "GB_
     * @see make()
     * @see fileName()
     */
    public function gifBuild()
    {
        if ($this->setup) {
            // Relative to Environment::getPublicPath()
            $gifFileName = $this->fileName('assets/images/');
            // File exists
            if (!file_exists($gifFileName)) {
                // Create temporary directory if not done:
                GeneralUtility::mkdir_deep(Environment::getPublicPath() . '/typo3temp/assets/images/');
                // Create file:
                $this->make();
                $this->output($gifFileName);
                $this->destroy();
            }
            return $gifFileName;
        }
        return '';
    }

    /**
     * The actual rendering of the image file.
     * Basically sets the dimensions, the background color, the traverses the array of GIFBUILDER objects and finally setting the transparent color if defined.
     * Creates a GDlib resource in $this->im and works on that
     * Called by gifBuild()
     *
     * @internal
     * @see gifBuild()
     */
    public function make()
    {
        // Get trivial data
        $XY = $this->XY;
        // Reset internal properties
        $this->saveAlphaLayer = false;
        // Gif-start
        $im = imagecreatetruecolor($XY[0], $XY[1]);
        if ($im === false) {
            throw new \RuntimeException('imagecreatetruecolor returned false', 1598350445);
        }
        $this->im = $im;
        $this->w = $XY[0];
        $this->h = $XY[1];
        // Transparent layer as background if set and requirements are met
        if (!empty($this->setup['backColor']) && $this->setup['backColor'] === 'transparent' && !$this->setup['reduceColors'] && (empty($this->setup['format']) || $this->setup['format'] === 'png')) {
            // Set transparency properties
            imagesavealpha($this->im, true);
            // Fill with a transparent background
            $transparentColor = imagecolorallocatealpha($this->im, 0, 0, 0, 127);
            imagefill($this->im, 0, 0, $transparentColor);
            // Set internal properties to keep the transparency over the rendering process
            $this->saveAlphaLayer = true;
            // Force PNG in case no format is set
            $this->setup['format'] = 'png';
            $BGcols = [];
        } else {
            // Fill the background with the given color
            $BGcols = $this->convertColor($this->setup['backColor']);
            $Bcolor = imagecolorallocate($this->im, $BGcols[0], $BGcols[1], $BGcols[2]);
            imagefilledrectangle($this->im, 0, 0, $XY[0], $XY[1], $Bcolor);
        }
        // Traverse the GIFBUILDER objects and render each one:
        if (is_array($this->setup)) {
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
                                $this->maskImageOntoImage($this->im, $conf, $this->workArea);
                            } else {
                                $this->copyImageOntoImage($this->im, $conf, $this->workArea);
                            }
                            break;
                        case 'TEXT':
                            if (!($conf['hide'] ?? false)) {
                                if (is_array($conf['shadow.'] ?? null)) {
                                    $isStdWrapped = [];
                                    foreach ($conf['shadow.'] as $key => $value) {
                                        $parameter = rtrim($key, '.');
                                        if (!$isStdWrapped[$parameter] && isset($conf[$parameter . '.'])) {
                                            $conf['shadow.'][$parameter] = $this->cObj->stdWrapValue($parameter, $conf);
                                            $isStdWrapped[$parameter] = 1;
                                        }
                                    }
                                    $this->makeShadow($this->im, $conf['shadow.'], $this->workArea, $conf);
                                }
                                if (is_array($conf['emboss.'] ?? null)) {
                                    $isStdWrapped = [];
                                    foreach ($conf['emboss.'] as $key => $value) {
                                        $parameter = rtrim($key, '.');
                                        if (!$isStdWrapped[$parameter] && isset($conf[$parameter . '.'])) {
                                            $conf['emboss.'][$parameter] = $this->cObj->stdWrapValue($parameter, $conf);
                                            $isStdWrapped[$parameter] = 1;
                                        }
                                    }
                                    $this->makeEmboss($this->im, $conf['emboss.'], $this->workArea, $conf);
                                }
                                if (is_array($conf['outline.'] ?? null)) {
                                    $isStdWrapped = [];
                                    foreach ($conf['outline.'] as $key => $value) {
                                        $parameter = rtrim($key, '.');
                                        if (!$isStdWrapped[$parameter] && isset($conf[$parameter . '.'])) {
                                            $conf['outline.'][$parameter] = $this->cObj->stdWrapValue($parameter, $conf);
                                            $isStdWrapped[$parameter] = 1;
                                        }
                                    }
                                    $this->makeOutline($this->im, $conf['outline.'], $this->workArea, $conf);
                                }
                                $conf['imgMap'] = 1;
                                $this->makeText($this->im, $conf, $this->workArea);
                            }
                            break;
                        case 'OUTLINE':
                            if ($this->setup[$conf['textObjNum']] === 'TEXT' && ($txtConf = $this->checkTextObj($this->setup[$conf['textObjNum'] . '.']))) {
                                $this->makeOutline($this->im, $conf, $this->workArea, $txtConf);
                            }
                            break;
                        case 'EMBOSS':
                            if ($this->setup[$conf['textObjNum']] === 'TEXT' && ($txtConf = $this->checkTextObj($this->setup[$conf['textObjNum'] . '.']))) {
                                $this->makeEmboss($this->im, $conf, $this->workArea, $txtConf);
                            }
                            break;
                        case 'SHADOW':
                            if ($this->setup[$conf['textObjNum']] === 'TEXT' && ($txtConf = $this->checkTextObj($this->setup[$conf['textObjNum'] . '.']))) {
                                $this->makeShadow($this->im, $conf, $this->workArea, $txtConf);
                            }
                            break;
                        case 'BOX':
                            $this->makeBox($this->im, $conf, $this->workArea);
                            break;
                        case 'EFFECT':
                            $this->makeEffect($this->im, $conf);
                            break;
                        case 'ADJUST':
                            $this->adjust($this->im, $conf);
                            break;
                        case 'CROP':
                            $this->crop($this->im, $conf);
                            break;
                        case 'SCALE':
                            $this->scale($this->im, $conf);
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
                            $this->makeEllipse($this->im, $conf, $this->workArea);
                            break;
                    }
                }
            }
        }
        // Preserve alpha transparency
        if (!$this->saveAlphaLayer) {
            if ($this->setup['transparentBackground']) {
                // Auto transparent background is set
                $Bcolor = imagecolorclosest($this->im, $BGcols[0], $BGcols[1], $BGcols[2]);
                imagecolortransparent($this->im, $Bcolor);
            } elseif (is_array($this->setup['transparentColor_array'])) {
                // Multiple transparent colors are set. This is done via the trick that all transparent colors get
                // converted to one color and then this one gets set as transparent as png/gif can just have one
                // transparent color.
                $Tcolor = $this->unifyColors($this->im, $this->setup['transparentColor_array'], (bool)($this->setup['transparentColor.']['closest'] ?? false));
                if ($Tcolor >= 0) {
                    imagecolortransparent($this->im, $Tcolor);
                }
            }
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
     * @internal
     */
    public function checkTextObj($conf)
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
    public function calcOffset($string)
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
     * @param string $file Filename value OR the string "GIFBUILDER", see documentation in TSref for the "datatype" called "imgResource
     * @param array $fileArray TypoScript properties passed to the function. Either GIFBUILDER properties or imgResource properties, depending on the value of $file (whether that is "GIFBUILDER" or a file reference)
     * @return array|null Returns an array with file information from ContentObjectRenderer::getImgResource()
     * @internal
     * @see ContentObjectRenderer::getImgResource()
     */
    public function getResource($file, $fileArray)
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $deferProcessing = !$context->hasAspect('fileProcessing') || $context->getPropertyFromAspect('fileProcessing', 'deferProcessing');
        $context->setAspect('fileProcessing', new FileProcessingAspect(false));
        try {
            if (!in_array($fileArray['ext'] ?? '', $this->imageFileExt, true)) {
                $fileArray['ext'] = $this->gifExtension;
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
     * @internal
     */
    public function checkFile($file)
    {
        try {
            return GeneralUtility::makeInstance(FilePathSanitizer::class)->sanitize($file, true);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Calculates the GIFBUILDER output filename/path based on a serialized, hashed value of this->setup
     * and prefixes the original filename
     * also, the filename gets an additional prefix (max 100 characters),
     * something like "GB_MD5HASH_myfilename_is_very_long_and_such.jpg"
     *
     * @param string $pre Filename prefix, eg. "GB_
     * @return string The filepath, relative to Environment::getPublicPath()
     * @internal
     */
    public function fileName($pre)
    {
        $basicFileFunctions = GeneralUtility::makeInstance(BasicFileUtility::class);
        $filePrefix = implode('_', array_merge($this->combinedTextStrings, $this->combinedFileNames));
        $filePrefix = $basicFileFunctions->cleanFileName(ltrim($filePrefix, '.'));

        // shorten prefix to avoid overly long file names
        $filePrefix = substr($filePrefix, 0, 100);

        // Only take relevant parameters to ease the pain for json_encode and make the final string short
        // so shortMD5 is not as slow. see https://forge.typo3.org/issues/64158
        $hashInputForFileName = [
            array_keys($this->setup),
            $filePrefix,
            $this->im,
            $this->w,
            $this->h,
            $this->map,
            $this->workArea,
            $this->combinedTextStrings,
            $this->combinedFileNames,
            $this->data,
        ];
        return 'typo3temp/' . $pre . $filePrefix . '_' . md5((string)json_encode($hashInputForFileName)) . '.' . $this->extension();
    }

    /**
     * Returns the file extension used in the filename
     *
     * @return string Extension; "jpg" or "gif"/"png
     * @internal
     */
    public function extension()
    {
        switch (strtolower($this->setup['format'] ?? '')) {
            case 'jpg':
            case 'jpeg':
                return 'jpg';
            case 'png':
                return 'png';
            case 'gif':
                return 'gif';
            default:
                return $this->gifExtension;
        }
    }

    /**
     * Calculates the value concerning the dimensions of objects.
     *
     * @param string $string The string to be calculated (e.g. "[20.h]+13")
     * @return int The calculated value (e.g. "23")
     * @see calcOffset()
     */
    protected function calculateValue($string)
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
                if (isset($this->objBB[$objParts[0]])) {
                    if ($objParts[1] === 'w') {
                        $theVal = $this->objBB[$objParts[0]][0];
                    } elseif ($objParts[1] === 'h') {
                        $theVal = $this->objBB[$objParts[0]][1];
                    } elseif ($objParts[1] === 'lineHeight') {
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
    protected function calculateFunctions($string)
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
     * @param string $string The string to be used to calculate the maximum (e.g. "[10.h],[20.h],1000")
     * @return int The maximum value of the given comma separated and calculated values
     */
    protected function calculateMaximum($string)
    {
        $parts = GeneralUtility::trimExplode(',', $this->calcOffset($string), true);
        $maximum = !empty($parts) ? max($parts) : 0;
        return $maximum;
    }
}
