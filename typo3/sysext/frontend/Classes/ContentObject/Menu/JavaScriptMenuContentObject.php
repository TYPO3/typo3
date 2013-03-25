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
 * JavaScript/Selectorbox based menus
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class JavaScriptMenuContentObject extends \TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject {

	/**
	 * Dummy. Should do nothing, because we don't use the result-array here!
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function generate() {

	}

	/**
	 * Creates the HTML (mixture of a <form> and a JavaScript section) for the JavaScript menu (basically an array of selector boxes with onchange handlers)
	 *
	 * @return string The HTML code for the menu
	 * @todo Define visibility
	 */
	public function writeMenu() {
		if ($this->id) {
			// Making levels:
			$levels = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->mconf['levels'], 1, 5);
			$this->levels = $levels;
			$uniqueParam = \TYPO3\CMS\Core\Utility\GeneralUtility::shortMD5(microtime(), 5);
			$this->JSVarName = 'eid' . $uniqueParam;
			$this->JSMenuName = $this->mconf['menuName'] ? $this->mconf['menuName'] : 'JSmenu' . $uniqueParam;
			$JScode = '
 var ' . $this->JSMenuName . ' = new JSmenu(' . $levels . ', \'' . $this->JSMenuName . 'Form\');';
			for ($a = 1; $a <= $levels; $a++) {
				$JScode .= '
 var ' . $this->JSVarName . $a . '=0;';
			}
			$JScode .= $this->generate_level($levels, 1, $this->id, $this->menuArr, $this->MP_array) . LF;
			$GLOBALS['TSFE']->additionalHeaderData['JSMenuCode'] = '<script type="text/javascript" src="' . $GLOBALS['TSFE']->absRefPrefix . 't3lib/jsfunc.menu.js"></script>';
			$GLOBALS['TSFE']->additionalJavaScript['JSCode'] .= $JScode;
			// Printing:
			$allFormCode = '';
			for ($a = 1; $a <= $this->levels; $a++) {
				$formCode = '';
				$levelConf = $this->mconf[$a . '.'];
				$length = $levelConf['width'] ? $levelConf['width'] : 14;
				$lenghtStr = '';
				for ($b = 0; $b < $length; $b++) {
					$lenghtStr .= '_';
				}
				$height = $levelConf['elements'] ? $levelConf['elements'] : 5;
				$formCode .= '<select name="selector' . $a . '" onchange="' . $this->JSMenuName . '.act(' . $a . ');"' . ($levelConf['additionalParams'] ? ' ' . $levelConf['additionalParams'] : '') . '>';
				for ($b = 0; $b < $height; $b++) {
					$formCode .= '<option value="0">';
					if ($b == 0) {
						$formCode .= $lenghtStr;
					}
					$formCode .= '</option>';
				}
				$formCode .= '</select>';
				$allFormCode .= $this->tmpl->wrap($formCode, $levelConf['wrap']);
			}
			$formCode = $this->tmpl->wrap($allFormCode, $this->mconf['wrap']);
			$formCode = '<form action="" method="post" style="margin: 0 0 0 0;" name="' . $this->JSMenuName . 'Form">' . $formCode . '</form>';
			$formCode .= '<script type="text/javascript"> /*<![CDATA[*/ ' . $this->JSMenuName . '.writeOut(1,' . $this->JSMenuName . '.openID,1); /*]]>*/ </script>';
			return $this->tmpl->wrap($formCode, $this->mconf['wrapAfterTags']);
		}
	}

	/**
	 * Generates a number of lines of JavaScript code for a menu level.
	 * Calls itself recursively for additional levels.
	 *
	 * @param integer $levels Number of levels to generate
	 * @param integer $count Current level being generated - and if this number is less than $levels it will call itself recursively with $count incremented
	 * @param integer $pid Page id of the starting point.
	 * @param array $menuItemArray $this->menuArr passed along
	 * @param array $MP_array Previous MP vars
	 * @return string JavaScript code lines.
	 * @access private
	 * @todo Define visibility
	 */
	public function generate_level($levels, $count, $pid, $menuItemArray = '', $MP_array = array()) {
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
		$parent = $count == 1 ? 0 : $var . ($count - 1);
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
			$spacer = \TYPO3\CMS\Core\Utility\GeneralUtility::inList($this->spacerIDList, $data['doktype']) ? 1 : 0;
			// If the spacer-function is not enabled, spacers will not enter the $menuArr
			if ($this->mconf['SPC'] || !$spacer) {
				// Page may not be 'not_in_menu' or 'Backend User Section' + not in banned uid's
				if (!\TYPO3\CMS\Core\Utility\GeneralUtility::inList($this->doktypeExcludeList, $data['doktype']) && (!$data['nav_hide'] || $this->conf['includeNotInMenu']) && !\TYPO3\CMS\Core\Utility\GeneralUtility::inArray($banUidArray, $uid)) {
					if ($count < $levels) {
						$addLines = $this->generate_level($levels, $count + 1, $data['uid'], '', $MP_array_sub);
					} else {
						$addLines = '';
					}
					$title = $data['title'];
					$url = '';
					$target = '';
					if (!$addLines && !$levelConf['noLink'] || $levelConf['alwaysLink']) {
						$LD = $this->menuTypoLink($data, $this->mconf['target'], '', '', array(), $MP_params, $this->mconf['forceTypeValue']);
						// If access restricted pages should be shown in menus, change the link of such pages to link to a redirection page:
						$this->changeLinksForAccessRestrictedPages($LD, $data, $this->mconf['target'], $this->mconf['forceTypeValue']);
						$url = $GLOBALS['TSFE']->baseUrlWrap($LD['totalURL']);
						$target = $LD['target'];
					}
					$codeLines .= LF . $var . $count . '=' . $menuName . '.add(' . $parent . ',' . $prev . ',0,' . \TYPO3\CMS\Core\Utility\GeneralUtility::quoteJSvalue($title, TRUE) . ',' . \TYPO3\CMS\Core\Utility\GeneralUtility::quoteJSvalue($url, TRUE) . ',' . \TYPO3\CMS\Core\Utility\GeneralUtility::quoteJSvalue($target, TRUE) . ');';
					// If the active one should be chosen...
					$active = $levelConf['showActive'] && $this->isActive($data['uid'], $MP_var);
					// If the first item should be shown
					$first = !$c && $levelConf['showFirst'];
					// do it...
					if ($active || $first) {
						if ($count == 1) {
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
			$codeLines .= LF . $menuName . '.defTopTitle[' . $count . '] = ' . \TYPO3\CMS\Core\Utility\GeneralUtility::quoteJSvalue($levelConf['firstLabel'], TRUE) . ';';
		}
		return $codeLines;
	}

}


?>