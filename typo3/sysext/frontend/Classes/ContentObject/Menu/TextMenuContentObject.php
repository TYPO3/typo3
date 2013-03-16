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
 * Extension class creating text based menus
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class TextMenuContentObject extends \TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject {

	/**
	 * Calls procesItemStates() so that the common configuration for the menu items are resolved into individual configuration per item.
	 * Sets the result for the new "normal state" in $this->result
	 *
	 * @return void
	 * @see tslib_menu::procesItemStates()
	 * @todo Define visibility
	 */
	public function generate() {
		$splitCount = count($this->menuArr);
		if ($splitCount) {
			list($NOconf) = $this->procesItemStates($splitCount);
		}
		if ($this->mconf['debugItemConf']) {
			echo '<h3>$NOconf:</h3>';
			debug($NOconf);
		}
		$this->result = $NOconf;
	}

	/**
	 * Traverses the ->result array of menu items configuration (made by ->generate()) and renders each item.
	 * During the execution of this function many internal methods prefixed "extProc_" from this class is called and many of these are for now dummy functions. But they can be used for processing as they are used by the TMENU_LAYERS
	 * An instance of tslib_cObj is also made and for each menu item rendered it is loaded with the record for that page so that any stdWrap properties that applies will have the current menu items record available.
	 *
	 * @return string The HTML for the menu (returns result through $this->extProc_finish(); )
	 * @todo Define visibility
	 */
	public function writeMenu() {
		if (is_array($this->result) && count($this->result)) {
			// Create new tslib_cObj for our use
			$this->WMcObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
			$this->WMresult = '';
			$this->INPfixMD5 = substr(md5(microtime() . 'tmenu'), 0, 4);
			$this->WMmenuItems = count($this->result);
			$this->WMsubmenuObjSuffixes = $this->tmpl->splitConfArray(array('sOSuffix' => $this->mconf['submenuObjSuffixes']), $this->WMmenuItems);
			$this->extProc_init();
			foreach ($this->result as $key => $val) {
				$GLOBALS['TSFE']->register['count_HMENU_MENUOBJ']++;
				$GLOBALS['TSFE']->register['count_MENUOBJ']++;
				// Initialize the cObj with the page record of the menu item
				$this->WMcObj->start($this->menuArr[$key], 'pages');
				$this->I = array();
				$this->I['key'] = $key;
				$this->I['INPfix'] = ($this->imgNameNotRandom ? '' : '_' . $this->INPfixMD5) . '_' . $key;
				$this->I['val'] = $val;
				$this->I['title'] = isset($this->I['val']['stdWrap.']) ? $this->WMcObj->stdWrap($this->getPageTitle($this->menuArr[$key]['title'], $this->menuArr[$key]['nav_title']), $this->I['val']['stdWrap.']) : $this->getPageTitle($this->menuArr[$key]['title'], $this->menuArr[$key]['nav_title']);
				$this->I['uid'] = $this->menuArr[$key]['uid'];
				$this->I['mount_pid'] = $this->menuArr[$key]['mount_pid'];
				$this->I['pid'] = $this->menuArr[$key]['pid'];
				$this->I['spacer'] = $this->menuArr[$key]['isSpacer'];
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
				// Make link:
				if ($this->I['val']['RO']) {
					$this->I['theName'] = $this->imgNamePrefix . $this->I['uid'] . $this->I['INPfix'];
					$over = '';
					$out = '';
					if ($this->I['val']['beforeROImg']) {
						$over .= $this->WMfreezePrefix . 'over(\'' . $this->I['theName'] . 'before\');';
						$out .= $this->WMfreezePrefix . 'out(\'' . $this->I['theName'] . 'before\');';
					}
					if ($this->I['val']['afterROImg']) {
						$over .= $this->WMfreezePrefix . 'over(\'' . $this->I['theName'] . 'after\');';
						$out .= $this->WMfreezePrefix . 'out(\'' . $this->I['theName'] . 'after\');';
					}
					$this->I['linkHREF']['onMouseover'] = $over;
					$this->I['linkHREF']['onMouseout'] = $out;
					if ($over || $out) {
						$GLOBALS['TSFE']->setJS('mouseOver');
					}
					// Change background color:
					if ($this->I['val']['RO_chBgColor']) {
						$this->addJScolorShiftFunction();
						$chBgP = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('|', $this->I['val']['RO_chBgColor']);
						$this->I['linkHREF']['onMouseover'] .= 'changeBGcolor(\'' . $chBgP[2] . $this->I['uid'] . '\', \'' . $chBgP[0] . '\');';
						$this->I['linkHREF']['onMouseout'] .= 'changeBGcolor(\'' . $chBgP[2] . $this->I['uid'] . '\', \'' . $chBgP[1] . '\');';
					}
					$this->extProc_RO($key);
				}
				// Calling extra processing function
				$this->extProc_beforeLinking($key);
				// stdWrap for doNotLinkIt
				if (isset($this->I['val']['doNotLinkIt.'])) {
					$this->I['val']['doNotLinkIt'] = $this->WMcObj->stdWrap($this->I['val']['doNotLinkIt'], $this->I['val']['doNotLinkIt.']);
				}
				// Compile link tag
				if (!$this->I['val']['doNotLinkIt']) {
					$this->I['val']['doNotLinkIt'] = 0;
				}
				if (!$this->I['spacer'] && $this->I['val']['doNotLinkIt'] != 1) {
					$this->setATagParts();
				} else {
					$this->I['A1'] = '';
					$this->I['A2'] = '';
				}
				// ATagBeforeWrap processing:
				if ($this->I['val']['ATagBeforeWrap']) {
					$wrapPartsBefore = explode('|', $this->I['val']['linkWrap']);
					$wrapPartsAfter = array('', '');
				} else {
					$wrapPartsBefore = array('', '');
					$wrapPartsAfter = explode('|', $this->I['val']['linkWrap']);
				}
				if ($this->I['val']['stdWrap2'] || isset($this->I['val']['stdWrap2.'])) {
					$stdWrap2 = isset($this->I['val']['stdWrap2.']) ? $this->WMcObj->stdWrap('|', $this->I['val']['stdWrap2.']) : '|';
					$wrapPartsStdWrap = explode($this->I['val']['stdWrap2'] ? $this->I['val']['stdWrap2'] : '|', $stdWrap2);
				} else {
					$wrapPartsStdWrap = array('', '');
				}
				// Make before, middle and after parts
				$this->I['parts'] = array();
				$this->I['parts']['before'] = $this->getBeforeAfter('before');
				$this->I['parts']['stdWrap2_begin'] = $wrapPartsStdWrap[0];
				// stdWrap for doNotShowLink
				if (isset($this->I['val']['doNotShowLink.'])) {
					$this->I['val']['doNotShowLink'] = $this->WMcObj->stdWrap($this->I['val']['doNotShowLink'], $this->I['val']['doNotShowLink.']);
				}
				if (!$this->I['val']['doNotShowLink']) {
					$this->I['parts']['notATagBeforeWrap_begin'] = $wrapPartsAfter[0];
					$this->I['parts']['ATag_begin'] = $this->I['A1'];
					$this->I['parts']['ATagBeforeWrap_begin'] = $wrapPartsBefore[0];
					$this->I['parts']['title'] = $this->I['title'];
					$this->I['parts']['ATagBeforeWrap_end'] = $wrapPartsBefore[1];
					$this->I['parts']['ATag_end'] = $this->I['A2'];
					$this->I['parts']['notATagBeforeWrap_end'] = $wrapPartsAfter[1];
				}
				$this->I['parts']['stdWrap2_end'] = $wrapPartsStdWrap[1];
				$this->I['parts']['after'] = $this->getBeforeAfter('after');
				// Passing I to a user function
				if ($this->mconf['IProcFunc']) {
					$this->I = $this->userProcess('IProcFunc', $this->I);
				}
				// Merge parts + beforeAllWrap
				$this->I['theItem'] = implode('', $this->I['parts']);
				$this->I['theItem'] = $this->extProc_beforeAllWrap($this->I['theItem'], $key);
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
				// Calling extra processing function
				$this->extProc_afterLinking($key);
			}
			return $this->extProc_finish();
		}
	}

	/**
	 * Generates the before* and after* images for TMENUs
	 *
	 * @param string $pref Can be "before" or "after" and determines which kind of image to create (basically this is the prefix of the TypoScript properties that are read from the ->I['val'] array
	 * @return string The resulting HTML of the image, if any.
	 * @todo Define visibility
	 */
	public function getBeforeAfter($pref) {
		$res = '';
		if ($imgInfo = $this->WMcObj->getImgResource($this->I['val'][$pref . 'Img'], $this->I['val'][$pref . 'Img.'])) {
			$imgInfo[3] = \TYPO3\CMS\Core\Utility\GeneralUtility::png_to_gif_by_imagemagick($imgInfo[3]);
			if ($this->I['val']['RO'] && $this->I['val'][$pref . 'ROImg'] && !$this->I['spacer']) {
				$imgROInfo = $this->WMcObj->getImgResource($this->I['val'][$pref . 'ROImg'], $this->I['val'][$pref . 'ROImg.']);
				$imgROInfo[3] = \TYPO3\CMS\Core\Utility\GeneralUtility::png_to_gif_by_imagemagick($imgROInfo[3]);
				if ($imgROInfo) {
					$theName = $this->imgNamePrefix . $this->I['uid'] . $this->I['INPfix'] . $pref;
					$name = ' ' . $this->nameAttribute . '="' . $theName . '"';
					$GLOBALS['TSFE']->JSImgCode .= LF . $theName . '_n=new Image(); ' . $theName . '_n.src = "' . $GLOBALS['TSFE']->absRefPrefix . $imgInfo[3] . '"; ';
					$GLOBALS['TSFE']->JSImgCode .= LF . $theName . '_h=new Image(); ' . $theName . '_h.src = "' . $GLOBALS['TSFE']->absRefPrefix . $imgROInfo[3] . '"; ';
				}
			}
			$GLOBALS['TSFE']->imagesOnPage[] = $imgInfo[3];
			$res = '<img' . ' src="' . $GLOBALS['TSFE']->absRefPrefix . $imgInfo[3] . '"' . ' width="' . $imgInfo[0] . '"' . ' height="' . $imgInfo[1] . '"' . $name . ($this->I['val'][$pref . 'ImgTagParams'] ? ' ' . $this->I['val'][($pref . 'ImgTagParams')] : '') . \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::getBorderAttr(' border="0"');
			if (!strstr($res, 'alt="')) {
				// Adding alt attribute if not set.
				$res .= ' alt=""';
			}
			$res .= ' />';
			if ($this->I['val'][$pref . 'ImgLink']) {
				$res = $this->I['A1'] . $res . $this->I['A2'];
			}
		}
		$processedPref = isset($this->I['val'][$pref . '.']) ? $this->WMcObj->stdWrap($this->I['val'][$pref], $this->I['val'][$pref . '.']) : $this->I['val'][$pref];
		if (isset($this->I['val'][$pref . 'Wrap'])) {
			return $this->tmpl->wrap($res . $processedPref, $this->I['val'][$pref . 'Wrap']);
		} else {
			return $res . $processedPref;
		}
	}

	/**
	 * Adds a JavaScript function to the $GLOBALS['TSFE']->additionalJavaScript array
	 *
	 * @return void
	 * @access private
	 * @see writeMenu()
	 * @todo Define visibility
	 */
	public function addJScolorShiftFunction() {
		$GLOBALS['TSFE']->additionalJavaScript['TMENU:changeBGcolor()'] = '
			function changeBGcolor(id,color) {	//
				if (document.getElementById && document.getElementById(id)) {
					document.getElementById(id).style.background = color;
					return true;
				} else if (document.layers && document.layers[id]) {
					document.layers[id].bgColor = color;
					return true;
				}
			}
		';
	}

	/**
	 * Called right before the traversing of $this->result begins.
	 * Can be used for various initialization
	 *
	 * @return void
	 * @access private
	 * @see writeMenu(), tslib_tmenu_layers::extProc_init()
	 * @todo Define visibility
	 */
	public function extProc_init() {

	}

	/**
	 * Called after all processing for RollOver of an element has been done.
	 *
	 * @param integer Pointer to $this->menuArr[$key] where the current menu element record is found
	 * @return void
	 * @access private
	 * @see writeMenu(), tslib_tmenu_layers::extProc_RO()
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
	 * @see writeMenu(), tslib_tmenu_layers::extProc_beforeLinking()
	 * @todo Define visibility
	 */
	public function extProc_beforeLinking($key) {

	}

	/**
	 * Called right after the creation of links for the menu item. This is also the last function call before the while-loop traversing menu items goes to the next item.
	 * This function MUST set $this->WMresult.=[HTML for menu item] to add the generated menu item to the internal accumulation of items.
	 *
	 * @param integer Pointer to $this->menuArr[$key] where the current menu element record is found
	 * @return void
	 * @access private
	 * @see writeMenu(), tslib_tmenu_layers::extProc_afterLinking()
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
	 * Called before the "allWrap" happens on the menu item.
	 *
	 * @param string $item The current content of the menu item, $this->I['theItem'], passed along.
	 * @param integer $key Pointer to $this->menuArr[$key] where the current menu element record is found
	 * @return string The modified version of $item, going back into $this->I['theItem']
	 * @access private
	 * @see writeMenu(), tslib_tmenu_layers::extProc_beforeAllWrap()
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
	 * @see writeMenu(), tslib_tmenu_layers::extProc_finish()
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