<?php
namespace TYPO3\CMS\Belog\ViewHelpers;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
		$historyHref = GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . 'typo3/' .
			\TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl(
				'record_history',
				array(
					'sh_uid' => $historyEntry->getUid(),
					'returnUrl' => \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI'),
				)
			);
		$historyLink = '<a href="' . htmlspecialchars($historyHref) . '">' . $historyIcon . '</a>';
		return $historyLabel . '&nbsp;' . $historyLink;
	}

}
