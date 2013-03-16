<?php
namespace TYPO3\CMS\Impexp;

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
 * Adding Import/Export clickmenu item
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class Clickmenu {

	/**
	 * Processing of clickmenu items
	 *
	 * @param object $backRef Reference to parent
	 * @param array $menuItems Menu items array to modify
	 * @param string $table Table name
	 * @param integer $uid Uid of the record
	 * @return array Menu item array, returned after modification
	 * @todo Skinning for icons...
	 * @todo Define visibility
	 */
	public function main(&$backRef, $menuItems, $table, $uid) {
		$localItems = array();
		// Show import/export on second level menu OR root level.
		if ($backRef->cmLevel && \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('subname') == 'moreoptions' || $table === 'pages' && $uid == 0) {
			$LL = $this->includeLL();
			$modUrl = $backRef->backPath . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('impexp') . 'app/index.php';
			$url = $modUrl . '?tx_impexp[action]=export&id=' . ($table == 'pages' ? $uid : $backRef->rec['pid']);
			if ($table == 'pages') {
				$url .= '&tx_impexp[pagetree][id]=' . $uid;
				$url .= '&tx_impexp[pagetree][levels]=0';
				$url .= '&tx_impexp[pagetree][tables][]=_ALL';
			} else {
				$url .= '&tx_impexp[record][]=' . rawurlencode(($table . ':' . $uid));
				$url .= '&tx_impexp[external_ref][tables][]=_ALL';
			}
			$localItems[] = $backRef->linkItem($GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->getLLL('export', $LL)), $backRef->excludeIcon(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-export-t3d')), $backRef->urlRefForCM($url), 1);
			if ($table == 'pages') {
				$url = $modUrl . '?id=' . $uid . '&table=' . $table . '&tx_impexp[action]=import';
				$localItems[] = $backRef->linkItem($GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->getLLL('import', $LL)), $backRef->excludeIcon(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-import-t3d')), $backRef->urlRefForCM($url), 1);
			}
		}
		return array_merge($menuItems, $localItems);
	}

	/**
	 * Include local lang file and return $LOCAL_LANG array loaded.
	 *
	 * @return array Local lang array
	 * @todo Define visibility
	 */
	public function includeLL() {
		global $LANG;
		return $LANG->includeLLFile('EXT:impexp/app/locallang.xlf', FALSE);
	}

}

?>