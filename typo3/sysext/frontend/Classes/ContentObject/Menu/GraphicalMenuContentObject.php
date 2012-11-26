<?php
namespace TYPO3\CMS\Frontend\ContentObject\Menu;

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
 * Extension class creating graphic based menus (PNG or GIF files)
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class GraphicalMenuContentObject extends \TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject {

	/**
	 * Calls procesItemStates() so that the common configuration for the menu items are resolved into individual configuration per item.
	 * Calls makeGifs() for all "normal" items and if configured for, also the "rollover" items.
	 *
	 * @return void
	 * @see tslib_menu::procesItemStates(), makeGifs()
	 * @todo Define visibility
	 */
	public function generate() {
		$splitCount = count($this->menuArr);
		if ($splitCount) {
			list($NOconf, $ROconf) = $this->procesItemStates($splitCount);
			//store initial count value
			$temp_HMENU_MENUOBJ = $GLOBALS['TSFE']->register['count_HMENU_MENUOBJ'];
			$temp_MENUOBJ = $GLOBALS['TSFE']->register['count_MENUOBJ'];
			// Now we generate the giffiles:
			$this->makeGifs($NOconf, 'NO');
			// store count from NO obj
			$tempcnt_HMENU_MENUOBJ = $GLOBALS['TSFE']->register['count_HMENU_MENUOBJ'];
			$tempcnt_MENUOBJ = $GLOBALS['TSFE']->register['count_MENUOBJ'];
			if ($this->mconf['debugItemConf']) {
				echo '<h3>$NOconf:</h3>';
				debug($NOconf);
			}
			// RollOver
			if ($ROconf) {
				// Start recount for rollover with initial values
				$GLOBALS['TSFE']->register['count_HMENU_MENUOBJ'] = $temp_HMENU_MENUOBJ;
				$GLOBALS['TSFE']->register['count_MENUOBJ'] = $temp_MENUOBJ;
				$this->makeGifs($ROconf, 'RO');
				if ($this->mconf['debugItemConf']) {
					echo '<h3>$ROconf:</h3>';
					debug($ROconf);
				}
			}
			// Use count from NO obj
			$GLOBALS['TSFE']->register['count_HMENU_MENUOBJ'] = $tempcnt_HMENU_MENUOBJ;
			$GLOBALS['TSFE']->register['count_MENUOBJ'] = $tempcnt_MENUOBJ;
		}
	}

	/**
	 * Will traverse input array with configuratoin per-item and create corresponding GIF files for the menu.
	 * The data of the files are stored in $this->result
	 *
	 * @param array $conf Array with configuration for each item.
	 * @param string $resKey Type of images: normal ("NO") or rollover ("RO"). Valid values are "NO" and "RO
	 * @return void
	 * @access private
	 * @see generate()
	 * @todo Define visibility
	 */
	public function makeGifs($conf, $resKey) {
		$isGD = $GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib'];
		if (!is_array($conf)) {
			$conf = array();
		}
		$totalWH = array();
		$items = count($conf);
		if ($isGD) {
			// Generate the gif-files. the $menuArr is filled with some values like output_w, output_h, output_file
			$Hcounter = 0;
			$Wcounter = 0;
			$Hobjs = $this->mconf['applyTotalH'];
			if ($Hobjs) {
				$Hobjs = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $Hobjs);
			}
			$Wobjs = $this->mconf['applyTotalW'];
			if ($Wobjs) {
				$Wobjs = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $Wobjs);
			}
			$minDim = $this->mconf['min'];
			if ($minDim) {
				$minDim = $this->parent_cObj->calcIntExplode(',', $minDim . ',');
			}
			$maxDim = $this->mconf['max'];
			if ($maxDim) {
				$maxDim = $this->parent_cObj->calcIntExplode(',', $maxDim . ',');
			}
			if ($minDim) {
				$conf[$items] = $conf[$items - 1];
				$this->menuArr[$items] = array();
				$items = count($conf);
			}
			// TOTAL width
			if ($this->mconf['useLargestItemX'] || $this->mconf['useLargestItemY'] || $this->mconf['distributeX'] || $this->mconf['distributeY']) {
				$totalWH = $this->findLargestDims($conf, $items, $Hobjs, $Wobjs, $minDim, $maxDim);
			}
		}
		$c = 0;
		$maxFlag = 0;
		$distributeAccu = array('H' => 0, 'W' => 0);
		foreach ($conf as $key => $val) {
			$GLOBALS['TSFE']->register['count_HMENU_MENUOBJ']++;
			$GLOBALS['TSFE']->register['count_MENUOBJ']++;
			if ($items == $c + 1 && $minDim) {
				$Lobjs = $this->mconf['removeObjectsOfDummy'];
				if ($Lobjs) {
					$Lobjs = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $Lobjs);
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
			if ($isGD) {
				// Pre-working the item
				$gifCreator = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Imaging\\GifBuilder');
				$gifCreator->init();
				$gifCreator->start($val, $this->menuArr[$key]);
				// If useLargestItemH/W is specified
				if (count($totalWH) && ($this->mconf['useLargestItemX'] || $this->mconf['useLargestItemY'])) {
					$tempXY = explode(',', $gifCreator->setup['XY']);
					if ($this->mconf['useLargestItemX']) {
						$tempXY[0] = max($totalWH['W']);
					}
					if ($this->mconf['useLargestItemY']) {
						$tempXY[1] = max($totalWH['H']);
					}
					// Regenerate the new values...
					$val['XY'] = implode(',', $tempXY);
					$gifCreator = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Imaging\\GifBuilder');
					$gifCreator->init();
					$gifCreator->start($val, $this->menuArr[$key]);
				}
				// If distributeH/W is specified
				if (count($totalWH) && ($this->mconf['distributeX'] || $this->mconf['distributeY'])) {
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
					$gifCreator = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Imaging\\GifBuilder');
					$gifCreator->init();
					$gifCreator->start($val, $this->menuArr[$key]);
				}
				// If max dimensions are specified
				if ($maxDim) {
					$tempXY = explode(',', $val['XY']);
					if ($maxDim[0] && $Wcounter + $gifCreator->XY[0] >= $maxDim[0]) {
						$tempXY[0] == $maxDim[0] - $Wcounter;
						$maxFlag = 1;
					}
					if ($maxDim[1] && $Hcounter + $gifCreator->XY[1] >= $maxDim[1]) {
						$tempXY[1] = $maxDim[1] - $Hcounter;
						$maxFlag = 1;
					}
					if ($maxFlag) {
						$val['XY'] = implode(',', $tempXY);
						$gifCreator = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Imaging\\GifBuilder');
						$gifCreator->init();
						$gifCreator->start($val, $this->menuArr[$key]);
					}
				}
				// displace
				if ($Hobjs) {
					foreach ($Hobjs as $index) {
						if ($gifCreator->setup[$index] && $gifCreator->setup[$index . '.']) {
							$oldOffset = explode(',', $gifCreator->setup[$index . '.']['offset']);
							$gifCreator->setup[$index . '.']['offset'] = implode(',', $gifCreator->applyOffset($oldOffset, array(0, -$Hcounter)));
						}
					}
				}
				if ($Wobjs) {
					foreach ($Wobjs as $index) {
						if ($gifCreator->setup[$index] && $gifCreator->setup[$index . '.']) {
							$oldOffset = explode(',', $gifCreator->setup[$index . '.']['offset']);
							$gifCreator->setup[$index . '.']['offset'] = implode(',', $gifCreator->applyOffset($oldOffset, array(-$Wcounter, 0)));
						}
					}
				}
			}
			// Finding alternative GIF names if any (by altImgResource)
			$gifFileName = '';
			if ($conf[$key]['altImgResource'] || is_array($conf[$key]['altImgResource.'])) {
				if (!is_object($cObj)) {
					$cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
				}
				$cObj->start($this->menuArr[$key], 'pages');
				$altImgInfo = $cObj->getImgResource($conf[$key]['altImgResource'], $conf[$key]['altImgResource.']);
				$gifFileName = $altImgInfo[3];
			}
			// If an alternative name was NOT given, find the GIFBUILDER name.
			if (!$gifFileName && $isGD) {
				$gifCreator->createTempSubDir('menu/');
				$gifFileName = $gifCreator->fileName('menu/');
			}
			$this->result[$resKey][$key] = $conf[$key];
			// Generation of image file:
			// File exists
			if (file_exists($gifFileName)) {
				$info = @getimagesize($gifFileName);
				$this->result[$resKey][$key]['output_w'] = intval($info[0]);
				$this->result[$resKey][$key]['output_h'] = intval($info[1]);
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
			$this->result[$resKey][$key]['output_file'] = \TYPO3\CMS\Core\Utility\GeneralUtility::png_to_gif_by_imagemagick($this->result[$resKey][$key]['output_file']);
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
	 * @param integer $items The number of menu items
	 * @param array $Hobjs Array with "applyTotalH" numbers
	 * @param array $Wobjs Array with "applyTotalW" numbers
	 * @param array $minDim Array with "min" x/y
	 * @param array $maxDim Array with "max" x/y
	 * @return array Array with keys "H" and "W" which are in themselves arrays with the heights and widths of menu items inside. This can be used to find the max/min size of the menu items.
	 * @access private
	 * @see makeGifs()
	 * @todo Define visibility
	 */
	public function findLargestDims($conf, $items, $Hobjs, $Wobjs, $minDim, $maxDim) {
		$totalWH = array(
			'W' => array(),
			'H' => array(),
			'W_total' => 0,
			'H_total' => 0
		);
		$Hcounter = 0;
		$Wcounter = 0;
		$c = 0;
		$maxFlag = 0;
		foreach ($conf as $key => $val) {
			// SAME CODE AS makeGifs()! BEGIN
			if ($items == $c + 1 && $minDim) {
				$Lobjs = $this->mconf['removeObjectsOfDummy'];
				if ($Lobjs) {
					$Lobjs = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $Lobjs);
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
			$gifCreator = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Imaging\\GifBuilder');
			$gifCreator->init();
			$gifCreator->start($val, $this->menuArr[$key]);
			if ($maxDim) {
				$tempXY = explode(',', $val['XY']);
				if ($maxDim[0] && $Wcounter + $gifCreator->XY[0] >= $maxDim[0]) {
					$tempXY[0] == $maxDim[0] - $Wcounter;
					$maxFlag = 1;
				}
				if ($maxDim[1] && $Hcounter + $gifCreator->XY[1] >= $maxDim[1]) {
					$tempXY[1] = $maxDim[1] - $Hcounter;
					$maxFlag = 1;
				}
				if ($maxFlag) {
					$val['XY'] = implode(',', $tempXY);
					$gifCreator = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Imaging\\GifBuilder');
					$gifCreator->init();
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
	 * @todo Define visibility
	 */
	public function writeMenu() {
		if (is_array($this->menuArr) && is_array($this->result) && count($this->result) && is_array($this->result['NO'])) {
			// Create new tslib_cObj for our use
			$this->WMcObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
			$this->WMresult = '';
			$this->INPfixMD5 = substr(md5(microtime() . $this->GMENU_fixKey), 0, 4);
			$this->WMmenuItems = count($this->result['NO']);
			$this->WMsubmenuObjSuffixes = $this->tmpl->splitConfArray(array('sOSuffix' => $this->mconf['submenuObjSuffixes']), $this->WMmenuItems);
			$this->extProc_init();
			for ($key = 0; $key < $this->WMmenuItems; $key++) {
				if ($this->result['NO'][$key]['output_file']) {
					// Initialize the cObj with the page record of the menu item
					$this->WMcObj->start($this->menuArr[$key], 'pages');
					$this->I = array();
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
					$this->I['noLink'] = $this->I['spacer'] || $this->I['val']['noLink'] || !count($this->menuArr[$key]);
					// !count($this->menuArr[$key]) means that this item is a dummyItem
					$this->I['name'] = '';
					// Set access key
					if ($this->mconf['accessKey']) {
						$this->I['accessKey'] = $this->accessKey($this->I['title']);
					} else {
						$this->I['accessKey'] = array();
					}
					// Make link tag
					$this->I['val']['ATagParams'] = $this->WMcObj->getATagParams($this->I['val']);
					if (isset($this->I['val']['additionalParams.'])) {
						$this->I['val']['additionalParams'] = $this->WMcObj->stdWrap($this->I['val']['additionalParams'], $this->I['val']['additionalParams.']);
					}
					$this->I['linkHREF'] = $this->link($key, $this->I['val']['altTarget'], $this->mconf['forceTypeValue']);
					// Title attribute of links:
					$titleAttrValue = isset($this->I['val']['ATagTitle.']) ? $this->WMcObj->stdWrap($this->I['val']['ATagTitle'], $this->I['val']['ATagTitle.']) . $this->I['accessKey']['alt'] : $this->I['val']['ATagTitle'] . $this->I['accessKey']['alt'];
					if (strlen($titleAttrValue)) {
						$this->I['linkHREF']['title'] = $titleAttrValue;
					}
					// Set rollover
					if ($this->result['RO'][$key] && !$this->I['noLink']) {
						$this->I['theName'] = $this->imgNamePrefix . $this->I['uid'] . $this->I['INPfix'];
						$this->I['name'] = ' ' . $this->nameAttribute . '="' . $this->I['theName'] . '"';
						$this->I['linkHREF']['onMouseover'] = $this->WMfreezePrefix . 'over(\'' . $this->I['theName'] . '\');';
						$this->I['linkHREF']['onMouseout'] = $this->WMfreezePrefix . 'out(\'' . $this->I['theName'] . '\');';
						$GLOBALS['TSFE']->JSImgCode .= LF . $this->I['theName'] . '_n=new Image(); ' . $this->I['theName'] . '_n.src = "' . $GLOBALS['TSFE']->absRefPrefix . $this->I['val']['output_file'] . '"; ';
						$GLOBALS['TSFE']->JSImgCode .= LF . $this->I['theName'] . '_h=new Image(); ' . $this->I['theName'] . '_h.src = "' . $GLOBALS['TSFE']->absRefPrefix . $this->result['RO'][$key]['output_file'] . '"; ';
						$GLOBALS['TSFE']->imagesOnPage[] = $this->result['RO'][$key]['output_file'];
						$GLOBALS['TSFE']->setJS('mouseOver');
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
					$this->I['IMG'] = '<img src="' . $GLOBALS['TSFE']->absRefPrefix . $this->I['val']['output_file'] . '" width="' . $this->I['val']['output_w'] . '" height="' . $this->I['val']['output_h'] . '" ' . \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::getBorderAttr('border="0"') . ($this->mconf['disableAltText'] ? '' : ' alt="' . htmlspecialchars($this->I['altText']) . '"') . $this->I['name'] . ($this->I['val']['imgParams'] ? ' ' . $this->I['val']['imgParams'] : '') . ' />';
					// Make before, middle and after parts
					$this->I['parts'] = array();
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
					$this->I['theItem'] = $this->tmpl->wrap($this->I['theItem'], $this->I['val']['wrap']);
					// allWrap:
					$allWrap = isset($this->I['val']['allWrap.']) ? $this->WMcObj->stdWrap($this->I['val']['allWrap'], $this->I['val']['allWrap.']) : $this->I['val']['allWrap'];
					$this->I['theItem'] = $this->tmpl->wrap($this->I['theItem'], $allWrap);
					if ($this->I['val']['subst_elementUid']) {
						$this->I['theItem'] = str_replace('{elementUid}', $this->I['uid'], $this->I['theItem']);
					}
					// allStdWrap:
					if (is_array($this->I['val']['allStdWrap.'])) {
						$this->I['theItem'] = $this->WMcObj->stdWrap($this->I['theItem'], $this->I['val']['allStdWrap.']);
					}
					$GLOBALS['TSFE']->imagesOnPage[] = $this->I['val']['output_file'];
					$this->extProc_afterLinking($key);
				}
			}
			return $this->extProc_finish();
		}
	}

	/**
	 * Called right before the traversing of $this->result begins.
	 * Can be used for various initialization
	 *
	 * @return void
	 * @access private
	 * @see writeMenu(), tslib_gmenu_layers::extProc_init()
	 * @todo Define visibility
	 */
	public function extProc_init() {

	}

	/**
	 * Called after all processing for RollOver of an element has been done.
	 *
	 * @param integer Pointer to $this->menuArr[$key] where the current menu element record is found OR $this->result['RO'][$key] where the configuration for that elements RO version is found!
	 * @return void
	 * @access private
	 * @see writeMenu(), tslib_gmenu_layers::extProc_RO()
	 * @todo Define visibility
	 */
	public function extProc_RO($key) {

	}

	/**
	 * Called right before the creation of the link for the menu item
	 *
	 * @param integer Pointer to $this->menuArr[$key] where the current menu element record is found
	 * @return void
	 * @access private
	 * @see writeMenu(), tslib_gmenu_layers::extProc_beforeLinking()
	 * @todo Define visibility
	 */
	public function extProc_beforeLinking($key) {

	}

	/**
	 * Called right after the creation of links for the menu item. This is also the last function call before the for-loop traversing menu items goes to the next item.
	 * This function MUST set $this->WMresult.=[HTML for menu item] to add the generated menu item to the internal accumulation of items.
	 * Further this calls the subMenu function in the parent class to create any submenu there might be.
	 *
	 * @param integer Pointer to $this->menuArr[$key] where the current menu element record is found
	 * @return void
	 * @access private
	 * @see writeMenu(), tslib_gmenu_layers::extProc_afterLinking(), tslib_menu::subMenu()
	 * @todo Define visibility
	 */
	public function extProc_afterLinking($key) {
		// Add part to the accumulated result + fetch submenus
		if (!$this->I['spacer']) {
			$this->I['theItem'] .= $this->subMenu($this->I['uid'], $this->WMsubmenuObjSuffixes[$key]['sOSuffix']);
		}
		$part = isset($this->I['val']['wrapItemAndSub.']) ? $this->WMcObj->stdWrap($this->I['val']['wrapItemAndSub'], $this->I['val']['wrapItemAndSub.']) : $this->I['val']['wrapItemAndSub'];
		$this->WMresult .= $part ? $this->tmpl->wrap($this->I['theItem'], $part) : $this->I['theItem'];
	}

	/**
	 * Called before the "wrap" happens on the menu item.
	 *
	 * @param string The current content of the menu item, $this->I['theItem'], passed along.
	 * @param integer Pointer to $this->menuArr[$key] where the current menu element record is found
	 * @return string The modified version of $item, going back into $this->I['theItem']
	 * @access private
	 * @see writeMenu(), tslib_gmenu_layers::extProc_beforeAllWrap()
	 * @todo Define visibility
	 */
	public function extProc_beforeAllWrap($item, $key) {
		return $item;
	}

	/**
	 * Called before the writeMenu() function returns (only if a menu was generated)
	 *
	 * @return string The total menu content should be returned by this function
	 * @access private
	 * @see writeMenu(), tslib_gmenu_layers::extProc_finish()
	 * @todo Define visibility
	 */
	public function extProc_finish() {
		// stdWrap:
		if (is_array($this->mconf['stdWrap.'])) {
			$this->WMresult = $this->WMcObj->stdWrap($this->WMresult, $this->mconf['stdWrap.']);
		}
		return $this->tmpl->wrap($this->WMresult, $this->mconf['wrap']) . $this->WMextraScript;
	}

}


?>