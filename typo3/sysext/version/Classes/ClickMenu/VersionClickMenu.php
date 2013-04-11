<?php
namespace TYPO3\CMS\Version\ClickMenu;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2004-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * "Versioning" item added to click menu of elements.
 *
 * @author 	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class VersionClickMenu {

	/**
	 * Main function, adding the item to input menuItems array
	 *
	 * @param 	object		References to parent clickmenu objects.
	 * @param 	array		Array of existing menu items accumulated. New element added to this.
	 * @param 	string		Table name of the element
	 * @param 	integer		Record UID of the element
	 * @return 	array		Modified menuItems array
	 * @todo Define visibility
	 */
	public function main(&$backRef, $menuItems, $table, $uid) {
		$localItems = array();
		if (!$backRef->cmLevel && $uid > 0 && $GLOBALS['BE_USER']->check('modules', 'web_txversionM1')) {
			// Returns directly, because the clicked item was not from the pages table
			if (in_array('versioning', $backRef->disabledItems) || !$GLOBALS['TCA'][$table] || !$GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
				return $menuItems;
			}
			// Adds the regular item
			$LL = $this->includeLL();
			// "Versioning" element added:
			$url = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('version') . 'cm1/index.php?table=' . rawurlencode($table) . '&uid=' . $uid;
			$localItems[] = $backRef->linkItem($GLOBALS['LANG']->getLLL('title', $LL), $backRef->excludeIcon('<img src="' . $backRef->backPath . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('version') . 'cm1/cm_icon.gif" width="15" height="12" border="0" align="top" alt="" />'), $backRef->urlRefForCM($url), 1);
			// Find position of "delete" element:
			$c = 0;
			foreach ($menuItems as $k => $value) {
				$c++;
				if (!strcmp($k, 'delete')) {
					break;
				}
			}
			// .. subtract two (delete item + divider line)
			$c -= 2;
			// ... and insert the items just before the delete element.
			array_splice($menuItems, $c, 0, $localItems);
		}
		return $menuItems;
	}

	/**
	 * Includes the [extDir]/locallang.php and returns the $LOCAL_LANG array found in that file.
	 *
	 * @return 	array		Local lang array
	 * @todo Define visibility
	 */
	public function includeLL() {
		return $GLOBALS['LANG']->includeLLFile('EXT:version/locallang.xml', FALSE);
	}

}


?>