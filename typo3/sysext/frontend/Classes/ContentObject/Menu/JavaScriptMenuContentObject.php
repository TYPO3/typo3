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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * JavaScript/Selectorbox based menus
 */
class JavaScriptMenuContentObject extends AbstractMenuContentObject
{
    /**
     * @var int
     */
    public $levels = 0;

    /**
     * @var string
     */
    public $JSVarName = '';

    /**
     * @var string
     */
    public $JSMenuName = '';

    /**
     * Creates the HTML (mixture of a <form> and a JavaScript section) for the JavaScript menu (basically an array of selector boxes with onchange handlers)
     *
     * @return string The HTML code for the menu
     */
    public function writeMenu()
    {
        if (!$this->id) {
            return '';
        }

        $levels = MathUtility::forceIntegerInRange($this->mconf['levels'], 1, 5);
        $this->levels = $levels;
        $uniqueParam = GeneralUtility::shortMD5(microtime(), 5);
        $this->JSVarName = 'eid' . $uniqueParam;
        $this->JSMenuName = $this->mconf['menuName'] ?: 'JSmenu' . $uniqueParam;
        $JScode = '
var ' . $this->JSMenuName . ' = new JSmenu(' . $levels . ', ' . GeneralUtility::quoteJSvalue($this->JSMenuName . 'Form') . ');';
        for ($a = 1; $a <= $levels; $a++) {
            $JScode .= '
var ' . $this->JSVarName . $a . '=0;';
        }
        $JScode .= $this->generate_level($levels, 1, $this->id, $this->menuArr, $this->MP_array) . LF;
        $GLOBALS['TSFE']->additionalHeaderData['JSMenuCode'] = '<script type="text/javascript" src="' . $GLOBALS['TSFE']->absRefPrefix . ExtensionManagementUtility::siteRelPath('frontend') . 'Resources/Public/JavaScript/jsfunc.menu.js"></script>';
        $GLOBALS['TSFE']->additionalJavaScript['JSCode'] .= $JScode;
        // Printing:
        $allFormCode = '';
        for ($a = 1; $a <= $this->levels; $a++) {
            $formCode = '';
            $levelConf = $this->mconf[$a . '.'];
            $length = $levelConf['width'] ?: 14;
            $lengthStr = '';
            for ($b = 0; $b < $length; $b++) {
                $lengthStr .= '_';
            }
            $height = $levelConf['elements'] ?: 5;
            $formCode .= '<select name="selector' . $a . '" onchange="' . $this->JSMenuName . '.act(' . $a . ');"' . ($levelConf['additionalParams'] ? ' ' . $levelConf['additionalParams'] : '') . '>';
            for ($b = 0; $b < $height; $b++) {
                $formCode .= '<option value="0">';
                if ($b === 0) {
                    $formCode .= $lengthStr;
                }
                $formCode .= '</option>';
            }
            $formCode .= '</select>';
            $allFormCode .= $this->WMcObj->wrap($formCode, $levelConf['wrap']);
        }
        $formContent = $this->WMcObj->wrap($allFormCode, $this->mconf['wrap']);
        $formCode = '<form action="" method="post" style="margin: 0 0 0 0;" name="' . $this->JSMenuName . 'Form">' . $formContent . '</form>';
        $formCode .= '<script type="text/javascript"> /*<![CDATA[*/ ' . $this->JSMenuName . '.writeOut(1,' . $this->JSMenuName . '.openID,1); /*]]>*/ </script>';
        return $this->WMcObj->wrap($formCode, $this->mconf['wrapAfterTags']);
    }

    /**
     * Generates a number of lines of JavaScript code for a menu level.
     * Calls itself recursively for additional levels.
     *
     * @param int $levels Number of levels to generate
     * @param int $count Current level being generated - and if this number is less than $levels it will call itself recursively with $count incremented
     * @param int $pid Page id of the starting point.
     * @param array|string $menuItemArray $this->menuArr passed along
     * @param array $MP_array Previous MP vars
     * @return string JavaScript code lines.
     * @access private
     */
    public function generate_level($levels, $count, $pid, $menuItemArray = '', $MP_array = [])
    {
        $count = (int)$count;
        $levelConf = $this->mconf[$count . '.'];
        // Translate PID to a mount page, if any:
        $mount_info = $this->sys_page->getMountPointInfo($pid);
        if (is_array($mount_info)) {
            $MP_array[] = $mount_info['MPvar'];
            $pid = $mount_info['mount_pid'];
        }
        // UIDs to ban:
        $banUidArray = $this->getBannedUids();
        // Initializing variables:
        $var = $this->JSVarName;
        $menuName = $this->JSMenuName;
        $parent = $count === 1 ? 0 : $var . ($count - 1);
        $prev = 0;
        $c = 0;
        $codeLines = '';
        $menuItems = is_array($menuItemArray) ? $menuItemArray : $this->sys_page->getMenu($pid);
        foreach ($menuItems as $uid => $data) {
            // $data['_MP_PARAM'] contains MP param for overlay mount points (MPs with "substitute this page" set)
            // if present: add param to copy of MP array (copy used for that submenu branch only)
            $MP_array_sub = $MP_array;
            if (array_key_exists('_MP_PARAM', $data) && $data['_MP_PARAM']) {
                $MP_array_sub[] = $data['_MP_PARAM'];
            }
            // Set "&MP=" var:
            $MP_var = implode(',', $MP_array_sub);
            $MP_params = $MP_var ? '&MP=' . rawurlencode($MP_var) : '';
            // If item is a spacer, $spacer is set
            $spacer = GeneralUtility::inList($this->spacerIDList, $data['doktype']);
            // If the spacer-function is not enabled, spacers will not enter the $menuArr
            if ($this->mconf['SPC'] || !$spacer) {
                // Page may not be 'not_in_menu' or 'Backend User Section' + not in banned uid's
                if (!GeneralUtility::inList($this->doktypeExcludeList, $data['doktype']) && (!$data['nav_hide'] || $this->conf['includeNotInMenu']) && !in_array($uid, $banUidArray, false)) {
                    if ($count < $levels) {
                        $addLines = $this->generate_level($levels, $count + 1, $data['uid'], '', $MP_array_sub);
                    } else {
                        $addLines = '';
                    }
                    $title = $data['title'];
                    $url = '';
                    $target = '';
                    if (!$addLines && !$levelConf['noLink'] || $levelConf['alwaysLink']) {
                        $LD = $this->menuTypoLink($data, $this->mconf['target'], '', '', [], $MP_params, $this->mconf['forceTypeValue']);
                        // If access restricted pages should be shown in menus, change the link of such pages to link to a redirection page:
                        $this->changeLinksForAccessRestrictedPages($LD, $data, $this->mconf['target'], $this->mconf['forceTypeValue']);
                        $url = $this->getTypoScriptFrontendController()->baseUrlWrap($LD['totalURL']);
                        $target = $LD['target'];
                    }
                    $codeLines .= LF . $var . $count . '=' . $menuName . '.add(' . $parent . ',' . $prev . ',0,' . GeneralUtility::quoteJSvalue($title) . ',' . GeneralUtility::quoteJSvalue($url) . ',' . GeneralUtility::quoteJSvalue($target) . ');';
                    // If the active one should be chosen...
                    $active = $levelConf['showActive'] && $this->isActive($data['uid'], $MP_var);
                    // If the first item should be shown
                    $first = !$c && $levelConf['showFirst'];
                    // do it...
                    if ($active || $first) {
                        if ($count === 1) {
                            $codeLines .= LF . $menuName . '.openID = ' . $var . $count . ';';
                        } else {
                            $codeLines .= LF . $menuName . '.entry[' . $parent . '].openID = ' . $var . $count . ';';
                        }
                    }
                    // Add submenu...
                    $codeLines .= $addLines;
                    $prev = $var . $count;
                    $c++;
                }
            }
        }
        if ($this->mconf['firstLabelGeneral'] && !$levelConf['firstLabel']) {
            $levelConf['firstLabel'] = $this->mconf['firstLabelGeneral'];
        }
        if ($levelConf['firstLabel'] && $codeLines) {
            $codeLines .= LF . $menuName . '.defTopTitle[' . $count . '] = ' . GeneralUtility::quoteJSvalue($levelConf['firstLabel']) . ';';
        }
        return $codeLines;
    }
}
