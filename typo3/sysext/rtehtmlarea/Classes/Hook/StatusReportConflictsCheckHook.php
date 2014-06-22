<?php
namespace TYPO3\CMS\Rtehtmlarea\Hook;

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
 * Hook into the backend module "Reports" checking whether there are extensions installed that conflicting with htmlArea RTE
 */
class StatusReportConflictsCheckHook implements \TYPO3\CMS\Reports\StatusProviderInterface {

	/**
	 * Compiles a collection of system status checks as a status report.
	 *
	 * @return array List of statuses
	 */
	public function getStatus() {
		$reports = array(
			'noConflictingExtensionISInstalled' => $this->checkIfNoConflictingExtensionIsInstalled()
		);
		return $reports;
	}

	/**
	 * Check whether any conflicting extension has been installed
	 *
	 * @return \TYPO3\CMS\Reports\Status
	 */
	protected function checkIfNoConflictingExtensionIsInstalled() {
		$title = $GLOBALS['LANG']->sL('LLL:EXT:rtehtmlarea/hooks/statusreport/locallang.xlf:title');
		$conflictingExtensions = array();
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['conflicts'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['conflicts'] as $extensionKey => $version) {
				if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($extensionKey)) {
					$conflictingExtensions[] = $extensionKey;
				}
			}
		}
		if (count($conflictingExtensions)) {
			$value = $GLOBALS['LANG']->sL('LLL:EXT:rtehtmlarea/hooks/statusreport/locallang.xlf:keys') . ' ' . implode(', ', $conflictingExtensions);
			$message = $GLOBALS['LANG']->sL('LLL:EXT:rtehtmlarea/hooks/statusreport/locallang.xlf:uninstall');
			$status = \TYPO3\CMS\Reports\Status::ERROR;
		} else {
			$value = $GLOBALS['LANG']->sL('LLL:EXT:rtehtmlarea/hooks/statusreport/locallang.xlf:none');
			$message = '';
			$status = \TYPO3\CMS\Reports\Status::OK;
		}
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Reports\\Status', $title, $value, $message, $status);
	}

}
