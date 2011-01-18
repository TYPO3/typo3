<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 Xavier Perseguers <typo3@perseguers.ch>
 *  (c) 2010-2011 Steffen Kamper <steffen@typo3.org>
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
 * Contains HMENU class object.
 *
 * $Id: class.tslib_content.php 7905 2010-06-13 14:42:33Z ohader $
 * @author Xavier Perseguers <typo3@perseguers.ch>
 * @author Steffen Kamper <steffen@typo3.org>
 */
class tslib_content_HierarchicalMenu extends tslib_content_Abstract {

	/**
	 * Rendering the cObject, HMENU
	 *
	 * @param	array		Array of TypoScript properties
	 * @return	string		Output
	 */
	public function render($conf = array()) {

		$theValue = '';

		if ($this->cObj->checkIf($conf['if.'])) {

			$cls = strtolower($conf[1]);

			if (t3lib_div::inList($GLOBALS['TSFE']->tmpl->menuclasses, $cls)) {

				if (isset($conf['special.']['value.'])) {
					$conf['special.']['value'] = $this->cObj->stdWrap($conf['special.']['value'], $conf['special.']['value.']);
				}

				$GLOBALS['TSFE']->register['count_HMENU']++;
				$GLOBALS['TSFE']->register['count_HMENU_MENUOBJ'] = 0;
				$GLOBALS['TSFE']->register['count_MENUOBJ'] = 0;
				$GLOBALS['TSFE']->applicationData['GMENU_LAYERS']['WMid'] = array();
				$GLOBALS['TSFE']->applicationData['GMENU_LAYERS']['WMparentId'] = array();

				$menu = t3lib_div::makeInstance('tslib_' . $cls);
				$menu->parent_cObj = $this->cObj;
				$menu->start($GLOBALS['TSFE']->tmpl, $GLOBALS['TSFE']->sys_page, '', $conf, 1);
				$menu->makeMenu();

				$theValue .= $menu->writeMenu();

			}

			$wrap =  isset($conf['wrap.'])
				? $this->cObj->stdWrap($conf['wrap'], $conf['wrap.'])
				: $conf['wrap'];
			if ($wrap) {
				$theValue = $this->cObj->wrap($theValue, $wrap);
			}

			if (isset($conf['stdWrap.'])) {
				$theValue = $this->cObj->stdWrap($theValue, $conf['stdWrap.']);
			}

		}

		return $theValue;

	}

}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/content/class.tslib_content_hierarchicalmenu.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/content/class.tslib_content_hierarchicalmenu.php']);
}

?>
