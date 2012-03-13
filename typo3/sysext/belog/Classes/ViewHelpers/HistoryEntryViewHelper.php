<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Christian Kuhn <lolli@schwarzbu.ch>
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
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Get history entry from for log entry
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 * @package TYPO3
 * @subpackage belog
 */
class Tx_Belog_ViewHelpers_HistoryEntryViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {

	/**
	 * @var Tx_Belog_Domain_Repository_HistoryEntryRepository
	 */
	protected $historyEntryRepository;

	/**
	 * Inject the system history entry repository.
	 *
	 * @param Tx_Belog_Domain_Repository_HistoryEntryRepository $historyEntryRepository
	 * @return void
	 */
	public function injectHistoryEntryRepository(Tx_Belog_Domain_Repository_HistoryEntryRepository $historyEntryRepository) {
		$this->historyEntryRepository = $historyEntryRepository;
	}

	/**
	 * Get system history record
	 *
	 * @param integer $uid Uid of the log entry
	 * @return string Formatted history entry if one exists, else empty string
	 */
	public function render($uid) {
		/** @var $historyEntry Tx_Belog_Domain_Model_HistoryEntry */
		$historyEntry = $this->historyEntryRepository->findOneBySysLogUid($uid);

		if (!($historyEntry instanceof Tx_Belog_Domain_Model_HistoryEntry)) {
			return '';
		}

		$historyLabel = Tx_Extbase_Utility_Localization::translate(
			'changesInFields',
			$this->controllerContext->getRequest()->getControllerExtensionName(),
			array($historyEntry->getFieldlist())
		);
		$historyIcon = t3lib_iconWorks::getSpriteIcon(
			'actions-document-history-open',
			array(
				'title' => Tx_Extbase_Utility_Localization::translate(
					'showHistory',
					$this->controllerContext->getRequest()->getControllerExtensionName()
				)
			)
		);
		$historyHref = 'show_rechis.php?sh_uid=' . $historyEntry->getUid() . '&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'));
		$historyLink = '<a href="' . htmlspecialchars($historyHref) . '">' . $historyIcon . '</a>';

		return $historyLabel . '&nbsp;' . $historyLink;
	}
}
?>