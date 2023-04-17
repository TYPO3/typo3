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

namespace TYPO3\CMS\Frontend\ContentObject\Menu;

use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Extension class creating text based menus
 */
class TextMenuContentObject extends AbstractMenuContentObject
{
    /**
     * Traverses the ->result array of menu items configuration (made by ->generate()) and renders each item.
     * An instance of ContentObjectRenderer is also made and for each menu item rendered it is loaded with
     * the record for that page so that any stdWrap properties that applies will have the current menu items record available.
     *
     * @return string The HTML for the menu including submenus
     */
    public function writeMenu()
    {
        if (empty($this->result)) {
            return '';
        }

        $frontendController = $this->getTypoScriptFrontendController();
        $cObjectForCurrentMenu = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $menuContent = '';
        $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
        $subMenuObjSuffixes = $typoScriptService->explodeConfigurationForOptionSplit(['sOSuffix' => $this->mconf['submenuObjSuffixes'] ?? null], count($this->result));
        $explicitSpacerRenderingEnabled = ($this->mconf['SPC'] ?? false);
        foreach ($this->result as $key => $val) {
            $frontendController->register['count_HMENU_MENUOBJ']++;
            $frontendController->register['count_MENUOBJ']++;

            // Initialize the cObj with the page record of the menu item
            $cObjectForCurrentMenu->setRequest($this->request);
            $cObjectForCurrentMenu->start($this->menuArr[$key], 'pages');
            $this->I = [];
            $this->I['key'] = $key;
            $this->I['val'] = $val;
            $this->I['title'] = $this->getPageTitle($this->menuArr[$key]['title'] ?? '', $this->menuArr[$key]['nav_title'] ?? '');
            $this->I['title.'] = $this->I['val']['stdWrap.'] ?? [];
            $this->I['title'] = $cObjectForCurrentMenu->stdWrapValue('title', $this->I ?? []);
            $this->I['uid'] = $this->menuArr[$key]['uid'] ?? 0;
            $this->I['mount_pid'] = $this->menuArr[$key]['mount_pid'] ?? 0;
            $this->I['pid'] = $this->menuArr[$key]['pid'] ?? 0;
            $this->I['spacer'] = $this->menuArr[$key]['isSpacer'] ?? false;
            // Make link tag
            $this->I['val']['additionalParams'] = $cObjectForCurrentMenu->stdWrapValue('additionalParams', $this->I['val']);
            $linkResult = $this->link((int)$key, (string)($this->I['val']['altTarget'] ?? ''), ($this->mconf['forceTypeValue'] ?? ''));
            if ($linkResult === null) {
                $this->I['val']['doNotLinkIt'] = 1;
            }
            // Title attribute of links
            $titleAttrValue = $cObjectForCurrentMenu->stdWrapValue('ATagTitle', $this->I['val']);
            if ($linkResult && $titleAttrValue !== '') {
                $linkResult = $linkResult->withAttribute('title', $titleAttrValue);
            }
            $this->I['linkHREF'] = $linkResult;
            $this->I['val']['doNotLinkIt'] = (bool)$cObjectForCurrentMenu->stdWrapValue('doNotLinkIt', $this->I['val']);
            // Compile link tag
            if (!$this->I['spacer'] && !$this->I['val']['doNotLinkIt']) {
                $this->setATagParts($linkResult);
            } else {
                $this->I['A1'] = '';
                $this->I['A2'] = '';
            }
            // ATagBeforeWrap processing:
            if ($this->I['val']['ATagBeforeWrap'] ?? false) {
                $wrapPartsBefore = explode('|', $this->I['val']['linkWrap'] ?? '');
                $wrapPartsAfter = ['', ''];
            } else {
                $wrapPartsBefore = ['', ''];
                $wrapPartsAfter = explode('|', $this->I['val']['linkWrap'] ?? '');
            }
            if (($this->I['val']['stdWrap2'] ?? false) || isset($this->I['val']['stdWrap2.'])) {
                $stdWrap2 = (string)(isset($this->I['val']['stdWrap2.']) ? $cObjectForCurrentMenu->stdWrap('|', $this->I['val']['stdWrap2.']) : '|');
                $stdWrap2Value = (string)($this->I['val']['stdWrap2'] ?? '|');
                $stdWrap2Value = $stdWrap2Value !== '' ? $stdWrap2Value : '|';
                $wrapPartsStdWrap = explode($stdWrap2Value, $stdWrap2);
            } else {
                $wrapPartsStdWrap = ['', ''];
            }
            // Make before, middle and after parts
            $this->I['parts'] = [];
            $this->I['parts']['before'] = $this->getBeforeAfter('before', $cObjectForCurrentMenu);
            $this->I['parts']['stdWrap2_begin'] = $wrapPartsStdWrap[0] ?? '';
            // stdWrap for doNotShowLink
            $this->I['val']['doNotShowLink'] = $cObjectForCurrentMenu->stdWrapValue('doNotShowLink', $this->I['val']);
            if (!$this->I['val']['doNotShowLink']) {
                $this->I['parts']['notATagBeforeWrap_begin'] = $wrapPartsAfter[0] ?? '';
                $this->I['parts']['ATag_begin'] = $this->I['A1'];
                $this->I['parts']['ATagBeforeWrap_begin'] = $wrapPartsBefore[0] ?? '';
                $this->I['parts']['title'] = $this->I['title'];
                $this->I['parts']['ATagBeforeWrap_end'] = $wrapPartsBefore[1] ?? '';
                $this->I['parts']['ATag_end'] = $this->I['A2'];
                $this->I['parts']['notATagBeforeWrap_end'] = $wrapPartsAfter[1] ?? '';
            }
            $this->I['parts']['stdWrap2_end'] = $wrapPartsStdWrap[1] ?? '';
            $this->I['parts']['after'] = $this->getBeforeAfter('after', $cObjectForCurrentMenu);
            // Passing I to a user function
            if ($this->mconf['IProcFunc'] ?? false) {
                $this->I = $this->userProcess('IProcFunc', $this->I);
            }
            // Merge parts + beforeAllWrap
            $this->I['theItem'] = implode('', $this->I['parts']);
            $allWrap = $cObjectForCurrentMenu->stdWrapValue('allWrap', $this->I['val']);
            $this->I['theItem'] = $cObjectForCurrentMenu->wrap($this->I['theItem'], $allWrap);
            if ($this->I['val']['subst_elementUid'] ?? false) {
                $this->I['theItem'] = str_replace('{elementUid}', (string)$this->I['uid'], $this->I['theItem']);
            }
            if (is_array($this->I['val']['allStdWrap.'] ?? null)) {
                $this->I['theItem'] = $cObjectForCurrentMenu->stdWrap($this->I['theItem'], $this->I['val']['allStdWrap.']);
            }
            $isSpacerPage = $this->I['spacer'] ?? false;
            // If rendering of SPACERs is enabled, also allow rendering submenus with Spacers
            if (!$isSpacerPage || $explicitSpacerRenderingEnabled) {
                // Add part to the accumulated result + fetch submenus
                $this->I['theItem'] .= $this->subMenu($this->I['uid'], $subMenuObjSuffixes[$key]['sOSuffix'] ?? '', $key);
            }
            $part = $cObjectForCurrentMenu->stdWrapValue('wrapItemAndSub', $this->I['val']);
            $menuContent .= $part ? $cObjectForCurrentMenu->wrap($this->I['theItem'], $part) : $this->I['theItem'];
        }
        if (is_array($this->mconf['stdWrap.'] ?? null)) {
            $menuContent = (string)$cObjectForCurrentMenu->stdWrap($menuContent, $this->mconf['stdWrap.']);
        }
        return $cObjectForCurrentMenu->wrap($menuContent, $this->mconf['wrap'] ?? '');
    }

    /**
     * Generates the before* and after* stdWrap for TMENUs
     * Evaluates:
     * - before.stdWrap*
     * - beforeWrap
     * - after.stdWrap*
     * - afterWrap
     *
     * @param string $pref Can be "before" or "after" and determines which kind of stdWrap to process (basically this is the prefix of the TypoScript properties that are read from the ->I['val'] array
     * @return string The resulting HTML
     */
    protected function getBeforeAfter(string $pref, ContentObjectRenderer $cObjectForCurrentMenu): string
    {
        $processedPref = $cObjectForCurrentMenu->stdWrapValue($pref, $this->I['val']);
        if (isset($this->I['val'][$pref . 'Wrap'])) {
            return $cObjectForCurrentMenu->wrap($processedPref, $this->I['val'][$pref . 'Wrap']);
        }
        return $processedPref;
    }
}
