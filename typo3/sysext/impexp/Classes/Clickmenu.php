<?php
namespace TYPO3\CMS\Impexp;

/**
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
			$urlParameters = array(
				'tx_impexp' => array(
					'action' => 'export'
				),
				'id' => ($table == 'pages' ? $uid : $backRef->rec['pid'])
			);
			if ($table == 'pages') {
				$urlParameters['tx_impexp']['pagetree']['id'] = $uid;
				$urlParameters['tx_impexp']['pagetree']['levels'] = 0;
				$urlParameters['tx_impexp']['pagetree']['tables'][] = '_ALL';
			} else {
				$urlParameters['tx_impexp']['record'][] = $table . ':' . $uid;
				$urlParameters['tx_impexp']['external_ref']['tables'][] = '_ALL';
			}
			$url = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('xMOD_tximpexp', $urlParameters);
			$localItems[] = $backRef->linkItem($GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->getLLL('export', $LL)), $backRef->excludeIcon(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-export-t3d')), $backRef->urlRefForCM($url), 1);
			if ($table == 'pages') {
				$urlParameters = array(
					'id' => $uid,
					'table' => $table,
					'tx_impexp' => array(
						'action' => 'import'
					),
				);
				$url = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('xMOD_tximpexp', $urlParameters);
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
