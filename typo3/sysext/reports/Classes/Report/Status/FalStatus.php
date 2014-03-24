<?php
namespace TYPO3\CMS\Reports\Report\Status;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Frans Saris <franssaris@gmail.com>
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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Performs several checks about the FAL status
 *
 * @author Frans Saris <franssaris@gmail.com>
 */
class FalStatus implements \TYPO3\CMS\Reports\StatusProviderInterface {

	/**
	 * Determines the status of the FAL index.
	 *
	 * @return array List of statuses
	 */
	public function getStatus() {
		$statuses = array(
			'MissingFiles' => $this->getMissingFilesStatus(),
			'ContentAdapter' => $this->getContentAdapterStatus(),
		);
		return $statuses;
	}

	/**
	 * Checks if there are files marked as missed.
	 *
	 * @return \TYPO3\CMS\Reports\Status An object representing whether there are files marked as missed or not
	 */
	protected function getMissingFilesStatus() {
		$value = $GLOBALS['LANG']->getLL('status_none');
		$count = 0;
		$maxFilesToShow = 100;
		$message = '';
		$severity = \TYPO3\CMS\Reports\Status::OK;

		/** @var $storageRepository \TYPO3\CMS\Core\Resource\StorageRepository */
		$storageRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\StorageRepository');
		$storageObjects = $storageRepository->findAll();
		$storages = array();

		/** @var $storageObject \TYPO3\CMS\Core\Resource\ResourceStorage */
		foreach ($storageObjects as $storageObject) {

			// We only check missing files for storages that are online
			if ($storageObject->isOnline()) {
				$storages[$storageObject->getUid()] = $storageObject;
			}
		}

		if (count($storages)) {
			$count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(
				'*',
				'sys_file',
				'missing=1 AND storage IN (' . implode(',', array_keys($storages)) . ')'
			);
		}

		if ($count) {
			$value = sprintf($GLOBALS['LANG']->getLL('status_missingFilesCount'), $count);
			$severity = \TYPO3\CMS\Reports\Status::WARNING;

			$files = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'identifier,storage',
				'sys_file',
				'missing=1 AND storage IN (' . implode(',', array_keys($storages)) . ')',
				'',
				'',
				$maxFilesToShow
			);

			$message = '<p>' . $GLOBALS['LANG']->getLL('status_missingFilesMessage') . '</p>';
			foreach ($files as $file) {
				$message .= $storages[$file['storage']]->getName() . ' ' . $file['identifier'] . '<br />';
			}

			if ($count > $maxFilesToShow) {
				$message .= '...<br />';
			}
		}

		return GeneralUtility::makeInstance('TYPO3\\CMS\\Reports\\Status', $GLOBALS['LANG']->getLL('status_missingFiles'), $value, $message, $severity);
	}

	/**
	 * Checks if content adapter is active
	 *
	 * @return \TYPO3\CMS\Reports\Status An object representing whether the content adapter is active or not
	 */
	protected function getContentAdapterStatus() {
		$value = $GLOBALS['LANG']->getLL('status_disabled');
		$message = '';
		$severity = \TYPO3\CMS\Reports\Status::OK;
		if ($GLOBALS['TYPO3_CONF_VARS']['FE']['activateContentAdapter']) {
			$value = $GLOBALS['LANG']->getLL('status_enabled');
			$message = '<p>' . $GLOBALS['LANG']->getLL('status_contentAdapterActiveMessage') . '</p>';
			$severity = \TYPO3\CMS\Reports\Status::WARNING;
		}
		return GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Reports\\Status',
			$GLOBALS['LANG']->getLL('status_contentAdapterActive'),
			$value,
			$message,
			$severity
		);

	}
}
