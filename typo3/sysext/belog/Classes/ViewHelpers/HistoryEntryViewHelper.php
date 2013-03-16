<?php
namespace TYPO3\CMS\Belog\ViewHelpers;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 */
class HistoryEntryViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @var \TYPO3\CMS\Belog\Domain\Repository\HistoryEntryRepository
	 * @inject
	 */
	protected $historyEntryRepository;

	/**
	 * Get system history record
	 *
	 * @param integer $uid Uid of the log entry
	 * @return string Formatted history entry if one exists, else empty string
	 */
	public function render($uid) {
		/** @var $historyEntry \TYPO3\CMS\Belog\Domain\Model\HistoryEntry */
		$historyEntry = $this->historyEntryRepository->findOneBySysLogUid($uid);
		if (!$historyEntry instanceof \TYPO3\CMS\Belog\Domain\Model\HistoryEntry) {
			return '';
		}
		$historyLabel = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
			'changesInFields',
			$this->controllerContext->getRequest()->getControllerExtensionName(),
			array($historyEntry->getFieldlist())
		);
		$historyIcon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-history-open', array(
			'title' => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('showHistory', $this->controllerContext->getRequest()->getControllerExtensionName())
		));
		$historyHref = 'show_rechis.php?sh_uid=' . $historyEntry->getUid() . '&returnUrl=' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI'));
		$historyLink = '<a href="' . htmlspecialchars($historyHref) . '">' . $historyIcon . '</a>';
		return $historyLabel . '&nbsp;' . $historyLink;
	}

}

?>