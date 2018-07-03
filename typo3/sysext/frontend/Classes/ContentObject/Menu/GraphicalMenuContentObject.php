<?php
namespace TYPO3\CMS\Frontend\ContentObject\Menu;

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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Type\File\ImageInfo;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Imaging\GifBuilder;

/**
 * Extension class creating graphic based menus (PNG or GIF files)
 *
 * @deprecated since TYPO3 v9.4, will be removed in TYPO3 v10.0
 */
class GraphicalMenuContentObject extends AbstractMenuContentObject
{
    public function __construct()
    {
        trigger_error('GMENU and GraphicalMenuContentObject will be removed in TYPO3 v10.0, you should build accessible websites with TMENU/Text, and optional images on top, which can be achieved with TypoScript.', E_USER_DEPRECATED);
    }

    /**
     * Calls procesItemStates() so that the common configuration for the menu items are resolved into individual configuration per item.
     * Calls makeGifs() for all "normal" items and if configured for, also the "rollover" items.
     *
     * @see AbstractMenuContentObject::procesItemStates(), makeGifs()
     */
    public function generate()
    {
        $splitCount = count($this->menuArr);
        if ($splitCount) {
            list($NOconf, $ROconf) = $this->procesItemStates($splitCount);
            //store initial count value
            $tsfe = $this->getTypoScriptFrontendController();
            $temp_HMENU_MENUOBJ = $tsfe->register['count_HMENU_MENUOBJ'];
            $temp_MENUOBJ = $tsfe->register['count_MENUOBJ'];
            // Now we generate the giffiles:
            $this->makeGifs($NOconf, 'NO');
            // store count from NO obj
            $tempcnt_HMENU_MENUOBJ = $tsfe->register['count_HMENU_MENUOBJ'];
            $tempcnt_MENUOBJ = $tsfe->register['count_MENUOBJ'];
            if ($this->mconf['debugItemConf']) {
                echo '<h3>$NOconf:</h3>';
                debug($NOconf);
            }
            // RollOver
            if ($ROconf) {
                // Start recount for rollover with initial values
                $tsfe->register['count_HMENU_MENUOBJ'] = $temp_HMENU_MENUOBJ;
                $tsfe->register['count_MENUOBJ'] = $temp_MENUOBJ;
                $this->makeGifs($ROconf, 'RO');
                if ($this->mconf['debugItemConf']) {
                    echo '<h3>$ROconf:</h3>';
                    debug($ROconf);
                }
            }
            // Use count from NO obj
            $tsfe->register['count_HMENU_MENUOBJ'] = $tempcnt_HMENU_MENUOBJ;
            $tsfe->register['count_MENUOBJ'] = $tempcnt_MENUOBJ;
        }
    }

    /**
     * Will traverse input array with configuration per-item and create corresponding GIF files for the menu.
     * The data of the files are stored in $this->result
     *
     * @param array $conf Array with configuration for each item.
     * @param string $resKey Type of images: normal ("NO") or rollover ("RO"). Valid values are "NO" and "RO
     * @internal
     * @see generate()
     */
    public function makeGifs($conf, $resKey)
    {
        $isGD = $GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib'];
        if (!is_array($conf)) {
            $conf = [];
        }
        $totalWH = [];
        $items = count($conf);
        $minDim = 0;
        $maxDim = 0;
        $Hcounter = 0;
        $Wcounter = 0;
        $Hobjs = [];
        $Wobjs = [];
        if ($isGD) {
            // Generate the gif-files. the $menuArr is filled with some values like output_w, output_h, output_file
            $Hobjs = $this->mconf['applyTotalH'];
            if ($Hobjs) {
                $Hobjs = GeneralUtility::intExplode(',', $Hobjs);
            }
            $Wobjs = $this->mconf['applyTotalW'];
            if ($Wobjs) {
                $Wobjs = GeneralUtility::intExplode(',', $Wobjs);
            }
            $minDim = $this->mconf['min'];
            if ($minDim) {
                $minDim = $this->calcIntExplode($minDim . ',');
            }
            $maxDim = $this->mconf['max'];
            if ($maxDim) {
                $maxDim = $this->calcIntExplode($maxDim . ',');
            }
            if ($minDim) {
                $conf[$items] = $conf[$items - 1];
                $this->menuArr[$items] = [];
                $items = count($conf);
            }
            // TOTAL width
            if ($this->mconf['useLargestItemX'] || $this->mconf['useLargestItemY'] || $this->mconf['distributeX'] || $this->mconf['distributeY']) {
                $totalWH = $this->findLargestDims($conf, $items, $Hobjs, $Wobjs, $minDim, $maxDim);
            }
        }
        $c = 0;
        $maxFlag = 0;
        $distributeAccu = ['H' => 0, 'W' => 0];
        foreach ($conf as $key => $val) {
            $this->getTypoScriptFrontendController()->register['count_HMENU_MENUOBJ']++;
            $this->getTypoScriptFrontendController()->register['count_MENUOBJ']++;
            if ($items === $c + 1 && $minDim) {
                $Lobjs = $this->mconf['removeObjectsOfDummy'];
                if ($Lobjs) {
                    $Lobjs = GeneralUtility::intExplode(',', $Lobjs);
                    foreach ($Lobjs as $remItem) {
                        unset($val[$remItem]);
                        unset($val[$remItem . '.']);
                    }
                }
                $flag = 0;
                $tempXY = explode(',', $val['XY']);
                if ($Wcounter < $minDim[0]) {
                    $tempXY[0] = $minDim[0] - $Wcounter;
                    $flag = 1;
                }
                if ($Hcounter < $minDim[1]) {
                    $tempXY[1] = $minDim[1] - $Hcounter;
                    $flag = 1;
                }
                $val['XY'] = implode(',', $tempXY);
                if (!$flag) {
                    break;
                }
            }
            $c++;
            $gifCreator = null;
            if ($isGD) {
                // Pre-working the item
                $gifCreator = GeneralUtility::makeInstance(GifBuilder::class);
                $gifCreator->start($val, $this->menuArr[$key]);
                // If useLargestItemH/W is specified
                if (!empty($totalWH) && ($this->mconf['useLargestItemX'] || $this->mconf['useLargestItemY'])) {
                    $tempXY = explode(',', $gifCreator->setup['XY']);
                    if ($this->mconf['useLargestItemX']) {
                        $tempXY[0] = max($totalWH['W']);
                    }
                    if ($this->mconf['useLargestItemY']) {
                        $tempXY[1] = max($totalWH['H']);
                    }
                    // Regenerate the new values...
                    $val['XY'] = implode(',', $tempXY);
                    $gifCreator = GeneralUtility::makeInstance(GifBuilder::class);
                    $gifCreator->start($val, $this->menuArr[$key]);
                }
                // If distributeH/W is specified
                if (!empty($totalWH) && ($this->mconf['distributeX'] || $this->mconf['distributeY'])) {
                    $tempXY = explode(',', $gifCreator->setup['XY']);
                    if ($this->mconf['distributeX']) {
                        $diff = $this->mconf['distributeX'] - $totalWH['W_total'] - $distributeAccu['W'];
                        $compensate = round($diff / ($items - $c + 1));
                        $distributeAccu['W'] += $compensate;
                        $tempXY[0] = $totalWH['W'][$key] + $compensate;
                    }
                    if ($this->mconf['distributeY']) {
                        $diff = $this->mconf['distributeY'] - $totalWH['H_total'] - $distributeAccu['H'];
                        $compensate = round($diff / ($items - $c + 1));
                        $distributeAccu['H'] += $compensate;
                        $tempXY[1] = $totalWH['H'][$key] + $compensate;
                    }
                    // Regenerate the new values...
                    $val['XY'] = implode(',', $tempXY);
                    $gifCreator = GeneralUtility::makeInstance(GifBuilder::class);
                    $gifCreator->start($val, $this->menuArr[$key]);
                }
                // If max dimensions are specified
                if ($maxDim) {
                    $tempXY = explode(',', $val['XY']);
                    if ($maxDim[0] && $Wcounter + $gifCreator->XY[0] >= $maxDim[0]) {
                        $tempXY[0] = $maxDim[0] - $Wcounter;
                        $maxFlag = 1;
                    }
                    if ($maxDim[1] && $Hcounter + $gifCreator->XY[1] >= $maxDim[1]) {
                        $tempXY[1] = $maxDim[1] - $Hcounter;
                        $maxFlag = 1;
                    }
                    if ($maxFlag) {
                        $val['XY'] = implode(',', $tempXY);
                        $gifCreator = GeneralUtility::makeInstance(GifBuilder::class);
                        $gifCreator->start($val, $this->menuArr[$key]);
                    }
                }
                // displace
                if ($Hobjs) {
                    foreach ($Hobjs as $index) {
                        if ($gifCreator->setup[$index] && $gifCreator->setup[$index . '.']) {
                            $oldOffset = explode(',', $gifCreator->setup[$index . '.']['offset']);
                            $gifCreator->setup[$index . '.']['offset'] = implode(',', $gifCreator->applyOffset($oldOffset, [0, -$Hcounter]));
                        }
                    }
                }
                if ($Wobjs) {
                    foreach ($Wobjs as $index) {
                        if ($gifCreator->setup[$index] && $gifCreator->setup[$index . '.']) {
                            $oldOffset = explode(',', $gifCreator->setup[$index . '.']['offset']);
                            $gifCreator->setup[$index . '.']['offset'] = implode(',', $gifCreator->applyOffset($oldOffset, [-$Wcounter, 0]));
                        }
                    }
                }
            }
            // Finding alternative GIF names if any (by altImgResource)
            $gifFileName = '';
            if ($conf[$key]['altImgResource'] || is_array($conf[$key]['altImgResource.'])) {
                $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
                $cObj->start($this->menuArr[$key], 'pages');
                $altImgInfo = $cObj->getImgResource($conf[$key]['altImgResource'], $conf[$key]['altImgResource.']);
                $gifFileName = $altImgInfo[3];
            }
            // If an alternative name was NOT given, find the GIFBUILDER name.
            if (!$gifFileName && $isGD) {
                GeneralUtility::mkdir_deep(Environment::getPublicPath() . '/typo3temp/assets/menu/');
                $gifFileName = $gifCreator->fileName('assets/menu/');
            }
            $this->result[$resKey][$key] = $conf[$key];
            // Generation of image file:
            // File exists
            if (file_exists($gifFileName)) {
                $imageInfo = GeneralUtility::makeInstance(ImageInfo::class, $gifFileName);
                $this->result[$resKey][$key]['output_w'] = (int)$imageInfo->getWidth();
                $this->result[$resKey][$key]['output_h'] = (int)$imageInfo->getHeight();
                $this->result[$resKey][$key]['output_file'] = $gifFileName;
            } elseif ($isGD) {
                // file is generated
                $gifCreator->make();
                $this->result[$resKey][$key]['output_w'] = $gifCreator->w;
                $this->result[$resKey][$key]['output_h'] = $gifCreator->h;
                $this->result[$resKey][$key]['output_file'] = $gifFileName;
                $gifCreator->output($this->result[$resKey][$key]['output_file']);
                $gifCreator->destroy();
            }
            // counter is increased
            $Hcounter += $this->result[$resKey][$key]['output_h'];
            // counter is increased
            $Wcounter += $this->result[$resKey][$key]['output_w'];
            if ($maxFlag) {
                break;
            }
        }
    }

    /**
     * Function searching for the largest width and height of the menu items to be generated.
     * Uses some of the same code as makeGifs and even instantiates some gifbuilder objects BUT does not render the images - only reading out which width they would have.
     * Remember to upgrade the code in here if the makeGifs function is updated.
     *
     * @param array $conf Same configuration array as passed to makeGifs()
     * @param int $items The number of menu items
     * @param array $Hobjs Array with "applyTotalH" numbers (unused)
     * @param array $Wobjs Array with "applyTotalW" numbers (unused)
     * @param array $minDim Array with "min" x/y
     * @param array $maxDim Array with "max" x/y
     * @return array Array with keys "H" and "W" which are in themselves arrays with the heights and widths of menu items inside. This can be used to find the max/min size of the menu items.
     * @internal
     * @see makeGifs()
     */
    public function findLargestDims($conf, $items, $Hobjs, $Wobjs, $minDim, $maxDim)
    {
        $items = (int)$items;
        $totalWH = [
            'W' => [],
            'H' => [],
            'W_total' => 0,
            'H_total' => 0
        ];
        $Hcounter = 0;
        $Wcounter = 0;
        $c = 0;
        $maxFlag = 0;
        foreach ($conf as $key => $val) {
            // SAME CODE AS makeGifs()! BEGIN
            if ($items === $c + 1 && $minDim) {
                $Lobjs = $this->mconf['removeObjectsOfDummy'];
                if ($Lobjs) {
                    $Lobjs = GeneralUtility::intExplode(',', $Lobjs);
                    foreach ($Lobjs as $remItem) {
                        unset($val[$remItem]);
                        unset($val[$remItem . '.']);
                    }
                }
                $flag = 0;
                $tempXY = explode(',', $val['XY']);
                if ($Wcounter < $minDim[0]) {
                    $tempXY[0] = $minDim[0] - $Wcounter;
                    $flag = 1;
                }
                if ($Hcounter < $minDim[1]) {
                    $tempXY[1] = $minDim[1] - $Hcounter;
                    $flag = 1;
                }
                $val['XY'] = implode(',', $tempXY);
                if (!$flag) {
                    break;
                }
            }
            $c++;
            $gifCreator = GeneralUtility::makeInstance(GifBuilder::class);
            $gifCreator->start($val, $this->menuArr[$key]);
            if ($maxDim) {
                $tempXY = explode(',', $val['XY']);
                if ($maxDim[0] && $Wcounter + $gifCreator->XY[0] >= $maxDim[0]) {
                    $tempXY[0] = $maxDim[0] - $Wcounter;
                    $maxFlag = 1;
                }
                if ($maxDim[1] && $Hcounter + $gifCreator->XY[1] >= $maxDim[1]) {
                    $tempXY[1] = $maxDim[1] - $Hcounter;
                    $maxFlag = 1;
                }
                if ($maxFlag) {
                    $val['XY'] = implode(',', $tempXY);
                    $gifCreator = GeneralUtility::makeInstance(GifBuilder::class);
                    $gifCreator->start($val, $this->menuArr[$key]);
                }
            }
            // SAME CODE AS makeGifs()! END
            // Setting the width/height
            $totalWH['W'][$key] = $gifCreator->XY[0];
            $totalWH['H'][$key] = $gifCreator->XY[1];
            $totalWH['W_total'] += $gifCreator->XY[0];
            $totalWH['H_total'] += $gifCreator->XY[1];
            // counter is increased
            $Hcounter += $gifCreator->XY[1];
            // counter is increased
            $Wcounter += $gifCreator->XY[0];
            if ($maxFlag) {
                break;
            }
        }
        return $totalWH;
    }

    /**
     * Traverses the ->result['NO'] array of menu items configuration (made by ->generate()) and renders the HTML of each item (the images themselves was made with makeGifs() before this. See ->generate())
     * During the execution of this function many internal methods prefixed "extProc_" from this class is called and many of these are for now dummy functions. But they can be used for processing as they are used by the GMENU_LAYERS
     *
     * @return string The HTML for the menu (returns result through $this->extProc_finish(); )
     */
    public function writeMenu()
    {
        if (!is_array($this->menuArr) || empty($this->result) || !is_array($this->result['NO'])) {
            return '';
        }
        $this->WMresult = '';
        $this->INPfixMD5 = substr(md5(microtime() . $this->GMENU_fixKey), 0, 4);
        $this->WMmenuItems = count($this->result['NO']);
        $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
        $this->WMsubmenuObjSuffixes = $typoScriptService->explodeConfigurationForOptionSplit(['sOSuffix' => $this->mconf['submenuObjSuffixes']], $this->WMmenuItems);
        $this->extProc_init();
        $tsfe = $this->getTypoScriptFrontendController();
        if (!isset($tsfe->additionalJavaScript['JSImgCode'])) {
            $tsfe->additionalJavaScript['JSImgCode'] = '';
        }
        for ($key = 0; $key < $this->WMmenuItems; $key++) {
            if ($this->result['NO'][$key]['output_file']) {
                // Initialize the cObj with the page record of the menu item
                $this->WMcObj->start($this->menuArr[$key], 'pages');
                $this->I = [];
                $this->I['key'] = $key;
                $this->I['INPfix'] = ($this->imgNameNotRandom ? '' : '_' . $this->INPfixMD5) . '_' . $key;
                $this->I['val'] = $this->result['NO'][$key];
                $this->I['title'] = $this->getPageTitle($this->menuArr[$key]['title'], $this->menuArr[$key]['nav_title']);
                $this->I['uid'] = $this->menuArr[$key]['uid'];
                $this->I['mount_pid'] = $this->menuArr[$key]['mount_pid'];
                $this->I['pid'] = $this->menuArr[$key]['pid'];
                $this->I['spacer'] = $this->menuArr[$key]['isSpacer'];
                if (!$this->I['uid'] && !$this->menuArr[$key]['_OVERRIDE_HREF']) {
                    $this->I['spacer'] = 1;
                }
                $this->I['noLink'] = $this->I['spacer'] || $this->I['val']['noLink'] || empty($this->menuArr[$key]);
                // !count($this->menuArr[$key]) means that this item is a dummyItem
                $this->I['name'] = '';
                // Set access key
                if ($this->mconf['accessKey']) {
                    $this->I['accessKey'] = $this->accessKey($this->I['title']);
                } else {
                    $this->I['accessKey'] = [];
                }
                // Make link tag
                $this->I['val']['ATagParams'] = $this->WMcObj->getATagParams($this->I['val']);
                if (isset($this->I['val']['additionalParams.'])) {
                    $this->I['val']['additionalParams'] = $this->WMcObj->stdWrap($this->I['val']['additionalParams'], $this->I['val']['additionalParams.']);
                }
                $this->I['linkHREF'] = $this->link($key, $this->I['val']['altTarget'], $this->mconf['forceTypeValue']);
                // Title attribute of links:
                $titleAttrValue = isset($this->I['val']['ATagTitle.']) ? $this->WMcObj->stdWrap($this->I['val']['ATagTitle'], $this->I['val']['ATagTitle.']) . $this->I['accessKey']['alt'] : $this->I['val']['ATagTitle'] . $this->I['accessKey']['alt'];
                if ($titleAttrValue !== '') {
                    $this->I['linkHREF']['title'] = $titleAttrValue;
                }
                // Set rollover
                if ($this->result['RO'][$key] && !$this->I['noLink']) {
                    $this->I['theName'] = $this->imgNamePrefix . $this->I['uid'] . $this->I['INPfix'];
                    $this->I['name'] = ' ' . $this->nameAttribute . '="' . $this->I['theName'] . '"';
                    $this->I['linkHREF']['onMouseover'] = $this->WMfreezePrefix . 'over(' . GeneralUtility::quoteJSvalue($this->I['theName']) . ');';
                    $this->I['linkHREF']['onMouseout'] = $this->WMfreezePrefix . 'out(' . GeneralUtility::quoteJSvalue($this->I['theName']) . ');';
                    $tsfe->additionalJavaScript['JSImgCode'] .= LF . $this->I['theName'] . '_n=new Image(); ' . $this->I['theName'] . '_n.src = ' . GeneralUtility::quoteJSvalue($tsfe->absRefPrefix . $this->I['val']['output_file']) . '; ';
                    $tsfe->additionalJavaScript['JSImgCode'] .= LF . $this->I['theName'] . '_h=new Image(); ' . $this->I['theName'] . '_h.src = ' . GeneralUtility::quoteJSvalue($tsfe->absRefPrefix . $this->result['RO'][$key]['output_file']) . '; ';
                    $tsfe->imagesOnPage[] = $this->result['RO'][$key]['output_file'];
                    $tsfe->setJS('mouseOver');
                    $this->extProc_RO($key);
                }
                // Set altText
                $this->I['altText'] = $this->I['title'] . $this->I['accessKey']['alt'];
                // Calling extra processing function
                $this->extProc_beforeLinking($key);
                // Set linking
                if (!$this->I['noLink']) {
                    $this->setATagParts();
                } else {
                    $this->I['A1'] = '';
                    $this->I['A2'] = '';
                }
                $this->I['IMG'] = '<img src="' . $tsfe->absRefPrefix . $this->I['val']['output_file'] . '" width="' . $this->I['val']['output_w'] . '" height="' . $this->I['val']['output_h'] . '" ' . $this->parent_cObj->getBorderAttr('border="0"') . ($this->mconf['disableAltText'] ? '' : ' alt="' . htmlspecialchars($this->I['altText']) . '"') . $this->I['name'] . ($this->I['val']['imgParams'] ? ' ' . $this->I['val']['imgParams'] : '') . ' />';
                // Make before, middle and after parts
                $this->I['parts'] = [];
                $this->I['parts']['ATag_begin'] = $this->I['A1'];
                $this->I['parts']['image'] = $this->I['IMG'];
                $this->I['parts']['ATag_end'] = $this->I['A2'];
                // Passing I to a user function
                if ($this->mconf['IProcFunc']) {
                    $this->I = $this->userProcess('IProcFunc', $this->I);
                }
                // Putting the item together.
                // Merge parts + beforeAllWrap
                $this->I['theItem'] = implode('', $this->I['parts']);
                $this->I['theItem'] = $this->extProc_beforeAllWrap($this->I['theItem'], $key);
                // wrap:
                $this->I['theItem'] = $this->WMcObj->wrap($this->I['theItem'], $this->I['val']['wrap']);
                // allWrap:
                $allWrap = isset($this->I['val']['allWrap.']) ? $this->WMcObj->stdWrap($this->I['val']['allWrap'], $this->I['val']['allWrap.']) : $this->I['val']['allWrap'];
                $this->I['theItem'] = $this->WMcObj->wrap($this->I['theItem'], $allWrap);
                if ($this->I['val']['subst_elementUid']) {
                    $this->I['theItem'] = str_replace('{elementUid}', $this->I['uid'], $this->I['theItem']);
                }
                // allStdWrap:
                if (is_array($this->I['val']['allStdWrap.'])) {
                    $this->I['theItem'] = $this->WMcObj->stdWrap($this->I['theItem'], $this->I['val']['allStdWrap.']);
                }
                $tsfe->imagesOnPage[] = $this->I['val']['output_file'];
                $this->extProc_afterLinking($key);
            }
        }
        return $this->extProc_finish();
    }

    /**
     * Called right before the traversing of $this->result begins.
     * Can be used for various initialization
     *
     * @see writeMenu()
     */
    protected function extProc_init()
    {
    }

    /**
     * Called after all processing for RollOver of an element has been done.
     *
     * @param int $key Pointer to $this->menuArr[$key] where the current menu element record is found OR $this->result['RO'][$key] where the configuration for that elements RO version is found!
     * @see writeMenu()
     */
    protected function extProc_RO($key)
    {
    }

    /**
     * Called right before the creation of the link for the menu item
     *
     * @param int $key Pointer to $this->menuArr[$key] where the current menu element record is found
     * @see writeMenu()
     */
    protected function extProc_beforeLinking($key)
    {
    }

    /**
     * Called right after the creation of links for the menu item. This is also the last function call before the
     * for-loop traversing menu items goes to the next item.
     * This function MUST set $this->WMresult.=[HTML for menu item] to add the generated menu item to the internal accumulation of items.
     * Further this calls the subMenu function in the parent class to create any submenu there might be.
     *
     * @param int $key Pointer to $this->menuArr[$key] where the current menu element record is found
     * @see writeMenu(), AbstractMenuContentObject::subMenu()
     */
    protected function extProc_afterLinking($key)
    {
        // Add part to the accumulated result + fetch submenus
        if (!$this->I['spacer']) {
            $this->I['theItem'] .= $this->subMenu($this->I['uid'], $this->WMsubmenuObjSuffixes[$key]['sOSuffix']);
        }
        $part = isset($this->I['val']['wrapItemAndSub.']) ? $this->WMcObj->stdWrap($this->I['val']['wrapItemAndSub'], $this->I['val']['wrapItemAndSub.']) : $this->I['val']['wrapItemAndSub'];
        $this->WMresult .= $part ? $this->WMcObj->wrap($this->I['theItem'], $part) : $this->I['theItem'];
    }

    /**
     * Called before the "wrap" happens on the menu item.
     *
     * @param string $item The current content of the menu item, $this->I['theItem'], passed along.
     * @param int $key Pointer to $this->menuArr[$key] where the current menu element record is found (unused)
     * @return string The modified version of $item, going back into $this->I['theItem']
     * @see writeMenu()
     */
    protected function extProc_beforeAllWrap($item, $key)
    {
        return $item;
    }

    /**
     * Called before the writeMenu() function returns (only if a menu was generated)
     *
     * @return string The total menu content should be returned by this function
     * @see writeMenu()
     */
    protected function extProc_finish()
    {
        // stdWrap:
        if (is_array($this->mconf['stdWrap.'])) {
            $this->WMresult = $this->WMcObj->stdWrap($this->WMresult, $this->mconf['stdWrap.']);
        }
        return $this->WMcObj->wrap($this->WMresult, $this->mconf['wrap']) . $this->WMextraScript;
    }

    /**
     * This explodes a comma-list into an array where the values are parsed through ContentObjectRender::calc() and cast to (int)(so you are sure to have integers in the output array)
     * Used to split and calculate min and max values for GMENUs.
     *
     * @param string $string The string with parts in (where each part is evaluated by ->calc())
     * @return array And array with evaluated values.
     * @see ContentObjectRenderer::calc(), makeGifs()
     */
    protected function calcIntExplode($string)
    {
        $temp = explode(',', $string);
        foreach ($temp as $key => $val) {
            $temp[$key] = (int)$this->parent_cObj->calc($val);
        }
        return $temp;
    }
}
