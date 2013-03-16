<?php
namespace TYPO3\CMS\WizardSortpages\View;

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
 * Creates the "Sort pages" wizard
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class SortPagesWizardModuleFunction extends \TYPO3\CMS\Backend\Module\AbstractFunctionModule {

	/**
	 * Adds menu items... but I think this is not used at all. Looks very much like some testing code. If anyone cares to check it we can remove it some day...
	 *
	 * @return array
	 * @ignore
	 * @todo Define visibility
	 */
	public function modMenu() {
		global $LANG;
		$modMenuAdd = array();
		return $modMenuAdd;
	}

	/**
	 * Main function creating the content for the module.
	 *
	 * @return string HTML content for the module, actually a "section" made through the parent object in $this->pObj
	 * @todo Define visibility
	 */
	public function main() {
		$GLOBALS['LANG']->includeLLFile('EXT:wizard_sortpages/locallang.xlf');
		$out = $this->pObj->doc->header($GLOBALS['LANG']->getLL('wiz_sort'));
		if ($GLOBALS['BE_USER']->workspace === 0) {
			$theCode = '';
			// Check if user has modify permissions to
			$sys_pages = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
			$sortByField = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('sortByField');
			if ($sortByField) {
				$menuItems = array();
				if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList('title,subtitle,crdate,tstamp', $sortByField)) {
					$menuItems = $sys_pages->getMenu($this->pObj->id, 'uid,pid,title', $sortByField, '', FALSE);
				} elseif ($sortByField == 'REV') {
					$menuItems = $sys_pages->getMenu($this->pObj->id, 'uid,pid,title', 'sorting', '', FALSE);
					$menuItems = array_reverse($menuItems);
				}
				if (count($menuItems)) {
					$tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
					$tce->stripslashes_values = 0;
					$menuItems = array_reverse($menuItems);
					$cmd = array();
					foreach ($menuItems as $r) {
						$cmd['pages'][$r['uid']]['move'] = $this->pObj->id;
					}
					$tce->start(array(), $cmd);
					$tce->process_cmdmap();
					\TYPO3\CMS\Backend\Utility\BackendUtility::setUpdateSignal('updatePageTree');
				}
			}
			$menuItems = $sys_pages->getMenu($this->pObj->id, '*', 'sorting', '', FALSE);

			if (count($menuItems)) {
				$lines = array();
				$lines[] = '<tr class="t3-row-header">
				<td>' . $this->wiz_linkOrder($GLOBALS['LANG']->getLL('wiz_changeOrder_title'), 'title') . '</td>
				' . (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('cms') ? '<td> ' . $this->wiz_linkOrder($GLOBALS['LANG']->getLL('wiz_changeOrder_subtitle'), 'subtitle') . '</td>' : '') . '
				<td>' . $this->wiz_linkOrder($GLOBALS['LANG']->getLL('wiz_changeOrder_tChange'), 'tstamp') . '</td>
				<td>' . $this->wiz_linkOrder($GLOBALS['LANG']->getLL('wiz_changeOrder_tCreate'), 'crdate') . '</td>
				</tr>';
				foreach ($menuItems as $rec) {
					$m_perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(2);
					// edit permissions for that page!
					$pRec = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages', $rec['uid'], 'uid', ' AND ' . $m_perms_clause);
					$lines[] = '<tr><td nowrap="nowrap">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('pages', $rec) . (!is_array($pRec) ? $GLOBALS['TBE_TEMPLATE']->rfw('<strong>' . $GLOBALS['LANG']->getLL('wiz_W', 1) . '</strong> ') : '') . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($rec['title'], $GLOBALS['BE_USER']->uc['titleLen'])) . '&nbsp;</td>
					' . (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('cms') ? '<td nowrap="nowrap">' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($rec['subtitle'], $GLOBALS['BE_USER']->uc['titleLen'])) . '&nbsp;</td>' : '') . '
					<td nowrap="nowrap">' . \TYPO3\CMS\Backend\Utility\BackendUtility::datetime($rec['tstamp']) . '&nbsp;&nbsp;</td>
					<td nowrap="nowrap">' . \TYPO3\CMS\Backend\Utility\BackendUtility::datetime($rec['crdate']) . '&nbsp;&nbsp;</td>
					</tr>';
				}
				$theCode .= '<h4>' . $GLOBALS['LANG']->getLL('wiz_currentPageOrder', TRUE) . '</h4>
			<table border="0" cellpadding="0" cellspacing="0" class="typo3-dblist">' . implode('', $lines) . '</table><br />';
				// Menu:
				$lines = array();
				$lines[] = $this->wiz_linkOrder($GLOBALS['LANG']->getLL('wiz_changeOrder_title'), 'title');
				if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('cms')) {
					$lines[] = $this->wiz_linkOrder($GLOBALS['LANG']->getLL('wiz_changeOrder_subtitle'), 'subtitle');
				}
				$lines[] = $this->wiz_linkOrder($GLOBALS['LANG']->getLL('wiz_changeOrder_tChange'), 'tstamp');
				$lines[] = $this->wiz_linkOrder($GLOBALS['LANG']->getLL('wiz_changeOrder_tCreate'), 'crdate');
				$lines[] = '';
				$lines[] = $this->wiz_linkOrder($GLOBALS['LANG']->getLL('wiz_changeOrder_REVERSE'), 'REV');
				$theCode .= '<h4>' . $GLOBALS['LANG']->getLL('wiz_changeOrder') . '</h4>' . implode('<br />', $lines);
			} else {
				$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $GLOBALS['LANG']->getLL('no_subpages'), '', \TYPO3\CMS\Core\Messaging\FlashMessage::NOTICE);
				$theCode .= $flashMessage->render();
			}
			// CSH:
			$theCode .= \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('_MOD_web_func', 'tx_wizardsortpages', $GLOBALS['BACK_PATH'], '<br />|');
			$out .= $this->pObj->doc->section('', $theCode, 0, 1);
		} else {
			$out .= $this->pObj->doc->section('', 'Sorry, this function is not available in the current draft workspace!', 0, 1, 1);
		}
		return $out;
	}

	/**
	 * Creates a link for the sorting order
	 *
	 * @param string $title Title of the link
	 * @param string $order Field to sort by
	 * @return string HTML string
	 * @todo Define visibility
	 */
	public function wiz_linkOrder($title, $order) {
		return '&nbsp; &nbsp;<a class="t3-link" href="' . htmlspecialchars(('index.php?id=' . $GLOBALS['SOBE']->id . '&sortByField=' . $order)) . '" onclick="return confirm(' . $GLOBALS['LANG']->JScharCode($GLOBALS['LANG']->getLL('wiz_changeOrder_msg1')) . ')">' . htmlspecialchars($title) . '</a>';
	}

}

?>