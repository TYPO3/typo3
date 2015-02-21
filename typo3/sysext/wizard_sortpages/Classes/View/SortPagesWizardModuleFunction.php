<?php
namespace TYPO3\CMS\WizardSortpages\View;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Creates the "Sort pages" wizard
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class SortPagesWizardModuleFunction extends \TYPO3\CMS\Backend\Module\AbstractFunctionModule {

	/**
	 * Main function creating the content for the module.
	 *
	 * @return string HTML content for the module, actually a "section" made through the parent object in $this->pObj
	 */
	public function main() {
		$GLOBALS['LANG']->includeLLFile('EXT:wizard_sortpages/locallang.xlf');
		$out = $this->pObj->doc->header($GLOBALS['LANG']->getLL('wiz_sort'));
		if ($GLOBALS['BE_USER']->workspace === 0) {
			$theCode = '';
			// Check if user has modify permissions to
			$sys_pages = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Page\PageRepository::class);
			$sortByField = GeneralUtility::_GP('sortByField');
			if ($sortByField) {
				$menuItems = array();
				if (GeneralUtility::inList('title,subtitle,crdate,tstamp', $sortByField)) {
					$menuItems = $sys_pages->getMenu($this->pObj->id, 'uid,pid,title', $sortByField, '', FALSE);
				} elseif ($sortByField === 'REV') {
					$menuItems = $sys_pages->getMenu($this->pObj->id, 'uid,pid,title', 'sorting', '', FALSE);
					$menuItems = array_reverse($menuItems);
				}
				if (!empty($menuItems)) {
					$tce = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
					$tce->stripslashes_values = 0;
					$menuItems = array_reverse($menuItems);
					$cmd = array();
					foreach ($menuItems as $r) {
						$cmd['pages'][$r['uid']]['move'] = $this->pObj->id;
					}
					$tce->start(array(), $cmd);
					$tce->process_cmdmap();
					BackendUtility::setUpdateSignal('updatePageTree');
				}
			}
			$menuItems = $sys_pages->getMenu($this->pObj->id, '*', 'sorting', '', FALSE);

			if (!empty($menuItems)) {
				$lines = array();
				$lines[] = '<thead><tr>';
				$lines[] = '<th>' . $GLOBALS['LANG']->getLL('wiz_changeOrder_title') . '</th>';
				$lines[] = '<th>' . $GLOBALS['LANG']->getLL('wiz_changeOrder_subtitle') . '</th>';
				$lines[] = '<th>' . $GLOBALS['LANG']->getLL('wiz_changeOrder_tChange') . '</th>';
				$lines[] = '<th>' . $GLOBALS['LANG']->getLL('wiz_changeOrder_tCreate') . '</th>';
				$lines[] = '</tr></thead>';

				foreach ($menuItems as $rec) {
					$m_perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(2);
					// edit permissions for that page!
					$pRec = BackendUtility::getRecord('pages', $rec['uid'], 'uid', ' AND ' . $m_perms_clause);
					$lines[] = '<tr><td nowrap="nowrap">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('pages', $rec) . (!is_array($pRec) ? '<strong class="text-danger">' . $GLOBALS['LANG']->getLL('wiz_W', TRUE) . '</strong></span> ' : '') . htmlspecialchars(GeneralUtility::fixed_lgd_cs($rec['title'], $GLOBALS['BE_USER']->uc['titleLen'])) . '</td>
					<td nowrap="nowrap">' . htmlspecialchars(GeneralUtility::fixed_lgd_cs($rec['subtitle'], $GLOBALS['BE_USER']->uc['titleLen'])) . '</td>
					<td nowrap="nowrap">' . BackendUtility::datetime($rec['tstamp']) . '</td>
					<td nowrap="nowrap">' . BackendUtility::datetime($rec['crdate']) . '</td>
					</tr>';
				}
				$theCode .= '<h2>' . $GLOBALS['LANG']->getLL('wiz_currentPageOrder', TRUE) . '</h2>';
				$theCode .= '<div class="table-fit"><table class="table table-striped table-hover">' . implode('', $lines) . '</table></div>';

				// Menu:
				$lines = array();
				$lines[] = $this->wiz_linkOrder($GLOBALS['LANG']->getLL('wiz_changeOrder_title'), 'title');
				$lines[] = $this->wiz_linkOrder($GLOBALS['LANG']->getLL('wiz_changeOrder_subtitle'), 'subtitle');
				$lines[] = $this->wiz_linkOrder($GLOBALS['LANG']->getLL('wiz_changeOrder_tChange'), 'tstamp');
				$lines[] = $this->wiz_linkOrder($GLOBALS['LANG']->getLL('wiz_changeOrder_tCreate'), 'crdate');
				$lines[] = '';
				$lines[] = $this->wiz_linkOrder($GLOBALS['LANG']->getLL('wiz_changeOrder_REVERSE'), 'REV');
				$theCode .= '<h4>' . $GLOBALS['LANG']->getLL('wiz_changeOrder') . '</h4><p>' . implode(' ', $lines) . '</p>';
			} else {
				$flashMessage = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class, $GLOBALS['LANG']->getLL('no_subpages'), '', \TYPO3\CMS\Core\Messaging\FlashMessage::NOTICE);
				$theCode .= $flashMessage->render();
			}
			// CSH:
			$theCode .= BackendUtility::cshItem('_MOD_web_func', 'tx_wizardsortpages', NULL, '<br />|');
			$out .= $this->pObj->doc->section('', $theCode, FALSE, TRUE);
		} else {
			$out .= $this->pObj->doc->section('', 'Sorry, this function is not available in the current draft workspace!', FALSE, TRUE, 1);
		}
		return $out;
	}

	/**
	 * Creates a link for the sorting order
	 *
	 * @param string $title Title of the link
	 * @param string $order Field to sort by
	 * @return string HTML string
	 */
	protected function wiz_linkOrder($title, $order) {
		return '<a class="btn btn-default" href="' . htmlspecialchars(
			BackendUtility::getModuleUrl('web_func',
				array(
					'id' => $GLOBALS['SOBE']->id,
					'sortByField' => $order
				)
			)
		) . '" onclick="return confirm(' . GeneralUtility::quoteJSvalue($GLOBALS['LANG']->getLL('wiz_changeOrder_msg1')) . ')">' . htmlspecialchars($title) . '</a>';
	}

}
