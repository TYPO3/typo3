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

use TYPO3\CMS\Core\Type\File\ImageInfo;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Imaging\GifBuilder;

/**
 * ImageMap based menus
 */
class ImageMenuContentObject extends AbstractMenuContentObject
{
    /**
     * Calls procesItemStates() so that the common configuration for the menu items are resolved into individual configuration per item.
     * Calls makeImageMap() to generate the image map image-file
     *
     * @see AbstractMenuContentObject::procesItemStates(), makeImageMap()
     */
    public function generate()
    {
        $NOconf = [];
        $splitCount = count($this->menuArr);
        if ($splitCount) {
            list($NOconf) = $this->procesItemStates($splitCount);
        }
        if (!empty($this->mconf['debugItemConf'])) {
            echo '<h3>$NOconf:</h3>';
            debug($NOconf);
        }
        $this->makeImageMap($NOconf);
    }

    /**
     * Will traverse input array with configuration per-item and create corresponding GIF files for the menu.
     * The data of the files are stored in $this->result
     *
     * @param array $conf Array with configuration for each item.
     * @access private
     * @see generate()
     */
    public function makeImageMap($conf)
    {
        if (!is_array($conf)) {
            $conf = [];
        }
        if (is_array($this->mconf['main.'])) {
            $gifCreator = GeneralUtility::makeInstance(GifBuilder::class);
            $gifCreator->init();
            $itemsConf = $conf;
            $conf = $this->mconf['main.'];
            if (is_array($conf)) {
                $sKeyArray = ArrayUtility::filterAndSortByNumericKeys($conf);
                $gifObjCount = (int)end($sKeyArray);
                // Now we add graphical objects to the gifbuilder-setup
                $waArr = [];
                foreach ($itemsConf as $key => $val) {
                    if (is_array($val)) {
                        $gifObjCount++;
                        $waArr[$key]['free'] = $gifObjCount;
                        $sKeyArray = ArrayUtility::filterAndSortByNumericKeys($val);
                        foreach ($sKeyArray as $theKey) {
                            $theValue = $val[$theKey];
                            if ((int)$theKey && ($theValArr = $val[$theKey . '.'])) {
                                $cObjData = $this->menuArr[$key] ?: [];
                                $gifObjCount++;
                                if ($theValue === 'TEXT') {
                                    $waArr[$key]['textNum'] = $gifObjCount;
                                    $gifCreator->data = $cObjData;
                                    $theValArr = $gifCreator->checkTextObj($theValArr);
                                    // if this is not done it seems that imageMaps will be rendered wrong!!
                                    unset($theValArr['text.']);
                                    // check links
                                    $LD = $this->menuTypoLink($this->menuArr[$key], $this->mconf['target'], '', '', [], '', $this->mconf['forceTypeValue']);
                                    // If access restricted pages should be shown in menus, change the link of such pages to link to a redirection page:
                                    $this->changeLinksForAccessRestrictedPages($LD, $this->menuArr[$key], $this->mconf['target'], $this->mconf['forceTypeValue']);
                                    // Overriding URL / Target if set to do so:
                                    if ($this->menuArr[$key]['_OVERRIDE_HREF']) {
                                        $LD['totalURL'] = $this->menuArr[$key]['_OVERRIDE_HREF'];
                                        if ($this->menuArr[$key]['_OVERRIDE_TARGET']) {
                                            $LD['target'] = $this->menuArr[$key]['_OVERRIDE_TARGET'];
                                        }
                                    }
                                    // Setting target/url for Image Map:
                                    if ($theValArr['imgMap.']['url'] === '') {
                                        $theValArr['imgMap.']['url'] = $LD['totalURL'];
                                    }
                                    if ($theValArr['imgMap.']['target'] === '') {
                                        $theValArr['imgMap.']['target'] = $LD['target'];
                                    }
                                    if (is_array($theValArr['imgMap.']['altText.'])) {
                                        $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
                                        $cObj->start($cObjData, 'pages');
                                        if (isset($theValArr['imgMap.']['altText.'])) {
                                            $theValArr['imgMap.']['altText'] = $cObj->stdWrap($theValArr['imgMap.']['altText'], $theValArr['imgMap.']['altText.']);
                                        }
                                        unset($theValArr['imgMap.']['altText.']);
                                    }
                                    if (is_array($theValArr['imgMap.']['titleText.'])) {
                                        $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
                                        $cObj->start($cObjData, 'pages');
                                        if (isset($theValArr['imgMap.']['titleText.'])) {
                                            $theValArr['imgMap.']['titleText'] = $cObj->stdWrap($theValArr['imgMap.']['titleText'], $theValArr['imgMap.']['titleText.']);
                                        }
                                        unset($theValArr['imgMap.']['titleText.']);
                                    }
                                }
                                // This code goes one level in if the object is an image. If 'file' and/or 'mask' appears to be GIFBUILDER-objects, they are both searched for TEXT objects, and if a textobj is found, it's checked with the currently loaded record!!
                                if ($theValue === 'IMAGE') {
                                    if ($theValArr['file'] === 'GIFBUILDER') {
                                        $temp_sKeyArray = ArrayUtility::filterAndSortByNumericKeys($theValArr['file.']);
                                        foreach ($temp_sKeyArray as $temp_theKey) {
                                            if ($theValArr['mask.'][$temp_theKey] === 'TEXT') {
                                                $gifCreator->data = $this->menuArr[$key] ?: [];
                                                $theValArr['mask.'][$temp_theKey . '.'] = $gifCreator->checkTextObj($theValArr['mask.'][$temp_theKey . '.']);
                                                // If this is not done it seems that imageMaps will be rendered wrong!!
                                                unset($theValArr['mask.'][$temp_theKey . '.']['text.']);
                                            }
                                        }
                                    }
                                    if ($theValArr['mask'] === 'GIFBUILDER') {
                                        $temp_sKeyArray = ArrayUtility::filterAndSortByNumericKeys($theValArr['mask.']);
                                        foreach ($temp_sKeyArray as $temp_theKey) {
                                            if ($theValArr['mask.'][$temp_theKey] === 'TEXT') {
                                                $gifCreator->data = $this->menuArr[$key] ?: [];
                                                $theValArr['mask.'][$temp_theKey . '.'] = $gifCreator->checkTextObj($theValArr['mask.'][$temp_theKey . '.']);
                                                // if this is not done it seems that imageMaps will be rendered wrong!!
                                                unset($theValArr['mask.'][$temp_theKey . '.']['text.']);
                                            }
                                        }
                                    }
                                }
                                // Checks if disabled is set...
                                $setObjFlag = 1;
                                if ($theValArr['if.']) {
                                    /** @var ContentObjectRenderer $cObj */
                                    $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
                                    $cObj->start($cObjData, 'pages');
                                    if (!empty($theValArr['if.']) && !$cObj->checkIf($theValArr['if.'])) {
                                        $setObjFlag = 0;
                                    }
                                    unset($theValArr['if.']);
                                }
                                // Set the object!
                                if ($setObjFlag) {
                                    $conf[$gifObjCount] = $theValue;
                                    $conf[$gifObjCount . '.'] = $theValArr;
                                }
                            }
                        }
                    }
                }
                $gifCreator->start($conf, $this->getTypoScriptFrontendController()->page);
                // calculations
                $dConf = [];
                foreach ($waArr as $key => $val) {
                    if ($dConf[$key] = $itemsConf[$key]['distrib']) {
                        $textBB = $gifCreator->objBB[$val['textNum']];
                        $dConf[$key] = str_replace(
                            ['textX', 'textY'],
                            [$textBB[0], $textBB[1]],
                            $dConf[$key]
                        );
                        $dConf[$key] = GeneralUtility::intExplode(',', $gifCreator->calcOffset($dConf[$key]));
                    }
                }
                $workArea = GeneralUtility::intExplode(',', $gifCreator->calcOffset($this->mconf['dWorkArea']));
                foreach ($waArr as $key => $val) {
                    $index = $val['free'];
                    $gifCreator->setup[$index] = 'WORKAREA';
                    $workArea[2] = $dConf[$key][2] ?: $dConf[$key][0];
                    $workArea[3] = $dConf[$key][3] ?: $dConf[$key][1];
                    $gifCreator->setup[$index . '.']['set'] = implode(',', $workArea);
                    $workArea[0] += $dConf[$key][0];
                    $workArea[1] += $dConf[$key][1];
                }
                if ($this->mconf['debugRenumberedObject']) {
                    echo '<h3>Renumbered GIFBUILDER object:</h3>';
                    debug($gifCreator->setup);
                }
                GeneralUtility::mkdir_deep(PATH_site . 'typo3temp/assets/menu/');
                $gifFileName = $gifCreator->fileName('assets/menu/');
                // Gets the ImageMap from the cache...
                $cache = $this->getCache();
                $imgHash = md5($gifFileName);
                $imgMap = $cache->get($imgHash);
                // File exists
                if ($imgMap && file_exists($gifFileName)) {
                    $imageInfo = GeneralUtility::makeInstance(ImageInfo::class, $gifFileName);
                    $w = $imageInfo->getWidth();
                    $h = $imageInfo->getHeight();
                } else {
                    // file is generated
                    $gifCreator->make();
                    $w = $gifCreator->w;
                    $h = $gifCreator->h;
                    $gifCreator->output($gifFileName);
                    $gifCreator->destroy();
                    $imgMap = $gifCreator->map;
                    $cache->set($imgHash, $imgMap, ['ident_MENUIMAGEMAP'], 0);
                }
                $imgMap .= $this->mconf['imgMapExtras'];
                $this->result = ['output_file' => $gifFileName, 'output_w' => $w, 'output_h' => $h, 'imgMap' => $imgMap];
            }
        }
    }

    /**
     * Returns the HTML for the image map menu.
     * If ->result is TRUE it will create the HTML for the image map menu.
     *
     * @return string The HTML for the menu
     */
    public function writeMenu()
    {
        if ($this->result) {
            $res = $this->result;
            // shortMD5 260900
            $menuName = 'menu_' . GeneralUtility::shortMD5($res['imgMap']);
            $result = '<img src="' . $this->getTypoScriptFrontendController()->absRefPrefix . $res['output_file'] . '" width="' . $res['output_w'] . '" height="' . $res['output_h'] . '" usemap="#' . $menuName . '" border="0" ' . $this->mconf['params'];
            // Adding alt attribute if not set.
            if (!strstr($result, 'alt="')) {
                $result .= ' alt="Menu Image Map"';
            }
            $result .= ' /><map name="' . $menuName . '" id="' . $menuName . '">' . $res['imgMap'] . '</map>';
            $this->getTypoScriptFrontendController()->imagesOnPage[] = $res['output_file'];
            return $this->WMcObj->wrap($result, $this->mconf['wrap']);
        }
        return '';
    }
}
