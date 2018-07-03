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

use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Extension class creating text based menus
 */
class TextMenuContentObject extends AbstractMenuContentObject
{
    /**
     * Calls procesItemStates() so that the common configuration for the menu items are resolved into individual configuration per item.
     * Sets the result for the new "normal state" in $this->result
     *
     * @see AbstractMenuContentObject::procesItemStates()
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
        $this->result = $NOconf;
    }

    /**
     * Traverses the ->result array of menu items configuration (made by ->generate()) and renders each item.
     * During the execution of this function many internal methods prefixed "extProc_" from this class is called and
     * many of these are for now dummy functions.
     * An instance of ContentObjectRenderer is also made and for each menu item rendered it is loaded with
     * the record for that page so that any stdWrap properties that applies will have the current menu items record available.
     *
     * @return string The HTML for the menu (returns result through $this->extProc_finish(); )
     */
    public function writeMenu()
    {
        if (empty($this->result)) {
            return '';
        }

        $this->WMresult = '';
        $this->INPfixMD5 = substr(md5(microtime() . 'tmenu'), 0, 4);
        $this->WMmenuItems = count($this->result);
        $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
        $this->WMsubmenuObjSuffixes = $typoScriptService->explodeConfigurationForOptionSplit(['sOSuffix' => $this->mconf['submenuObjSuffixes']], $this->WMmenuItems);
        $this->extProc_init();
        foreach ($this->result as $key => $val) {
            $GLOBALS['TSFE']->register['count_HMENU_MENUOBJ']++;
            $GLOBALS['TSFE']->register['count_MENUOBJ']++;
            // Initialize the cObj with the page record of the menu item
            $this->WMcObj->start($this->menuArr[$key], 'pages');
            $this->I = [];
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
                $this->I['accessKey'] = [];
            }
            // Make link tag
            $this->I['val']['ATagParams'] = $this->WMcObj->getATagParams($this->I['val']);
            if (isset($this->I['val']['additionalParams.'])) {
                $this->I['val']['additionalParams'] = $this->WMcObj->stdWrap($this->I['val']['additionalParams'], $this->I['val']['additionalParams.']);
            }
            $this->I['linkHREF'] = $this->link($key, $this->I['val']['altTarget'], $this->mconf['forceTypeValue']);
            if (empty($this->I['linkHREF'])) {
                $this->I['val']['doNotLinkIt'] = 1;
            }
            // Title attribute of links:
            $titleAttrValue = isset($this->I['val']['ATagTitle.']) ? $this->WMcObj->stdWrap($this->I['val']['ATagTitle'], $this->I['val']['ATagTitle.']) . $this->I['accessKey']['alt'] : $this->I['val']['ATagTitle'] . $this->I['accessKey']['alt'];
            if ($titleAttrValue !== '') {
                $this->I['linkHREF']['title'] = $titleAttrValue;
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
                $wrapPartsAfter = ['', ''];
            } else {
                $wrapPartsBefore = ['', ''];
                $wrapPartsAfter = explode('|', $this->I['val']['linkWrap']);
            }
            if ($this->I['val']['stdWrap2'] || isset($this->I['val']['stdWrap2.'])) {
                $stdWrap2 = isset($this->I['val']['stdWrap2.']) ? $this->WMcObj->stdWrap('|', $this->I['val']['stdWrap2.']) : '|';
                $wrapPartsStdWrap = explode($this->I['val']['stdWrap2'] ? $this->I['val']['stdWrap2'] : '|', $stdWrap2);
            } else {
                $wrapPartsStdWrap = ['', ''];
            }
            // Make before, middle and after parts
            $this->I['parts'] = [];
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
            $this->I['theItem'] = $this->WMcObj->wrap($this->I['theItem'], $allWrap);
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

    /**
     * Generates the before* and after* images for TMENUs
     *
     * @param string $pref Can be "before" or "after" and determines which kind of image to create (basically this is the prefix of the TypoScript properties that are read from the ->I['val'] array
     * @return string The resulting HTML of the image, if any.
     */
    protected function getBeforeAfter($pref)
    {
        $res = '';
        if ($imgInfo = $this->WMcObj->getImgResource($this->I['val'][$pref . 'Img'], $this->I['val'][$pref . 'Img.'])) {
            $theName = $this->imgNamePrefix . $this->I['uid'] . $this->I['INPfix'] . $pref;
            $name = ' ' . $this->nameAttribute . '="' . $theName . '"';
            $GLOBALS['TSFE']->imagesOnPage[] = $imgInfo[3];
            $res = '<img' . ' src="' . $GLOBALS['TSFE']->absRefPrefix . $imgInfo[3] . '"' . ' width="' . $imgInfo[0] . '"' . ' height="' . $imgInfo[1] . '"' . $name . ($this->I['val'][$pref . 'ImgTagParams'] ? ' ' . $this->I['val'][$pref . 'ImgTagParams'] : '') . $this->parent_cObj->getBorderAttr(' border="0"');
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
            return $this->WMcObj->wrap($res . $processedPref, $this->I['val'][$pref . 'Wrap']);
        }
        return $res . $processedPref;
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
     * Called right before the creation of the link for the menu item
     *
     * @param int $key Pointer to $this->menuArr[$key] where the current menu element record is found
     * @see writeMenu()
     */
    protected function extProc_beforeLinking($key)
    {
    }

    /**
     * Called right after the creation of links for the menu item. This is also the last function call before the while-loop traversing menu items goes to the next item.
     * This function MUST set $this->WMresult.=[HTML for menu item] to add the generated menu item to the internal accumulation of items.
     *
     * @param int $key Pointer to $this->menuArr[$key] where the current menu element record is found
     * @see writeMenu()
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
     * Called before the "allWrap" happens on the menu item.
     *
     * @param string $item The current content of the menu item, $this->I['theItem'], passed along.
     * @param int $key Pointer to $this->menuArr[$key] where the current menu element record is found
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
}
