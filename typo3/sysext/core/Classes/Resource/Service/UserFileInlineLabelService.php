<?php
namespace TYPO3\CMS\Core\Resource\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Tim Lochmueller <tim@fruit-lab.de>
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
 *  A copy is found in the text file GPL.txt and important notices to the license
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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * User file inline label service
 */
class UserFileInlineLabelService {

	/**
	 * Get the user function label for the file_reference table
	 *
	 * @param array $params
	 * @return void
	 */
	public function getInlineLabel(array &$params) {
		$sysFileFields = isset($params['options']['sys_file']) && is_array($params['options']['sys_file'])
			? $params['options']['sys_file']
			: array();

		if (!count($sysFileFields)) {
			// Nothing to do
			$params['title'] = $params['row']['uid'];
			return;
		}

		$fileInfo = BackendUtility::splitTable_Uid($params['row']['uid_local'], 2);
		$fileRecord = BackendUtility::getRecord($fileInfo[0], $fileInfo[1]);

		// Configuration
		$title = array();
		foreach ($sysFileFields as $field) {
			$value = '';
			if ($field === 'title') {
				if (isset($params['row']['title'])) {
					$fullTitle = $params['row']['title'];
				} else {
					$metaDataRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Index\\MetaDataRepository');
					$metaData = $metaDataRepository->findByFileUid($fileRecord['uid']);
					$fullTitle = $metaData['title'];
				}

				$value = BackendUtility::getRecordTitlePrep(htmlspecialchars($fullTitle));
			} else {
				if (isset($params['row'][$field])) {
					$value = htmlspecialchars($params['row'][$field]);
				} elseif (isset($fileRecord[$field])) {
					$value = BackendUtility::getRecordTitlePrep($fileRecord[$field]);
				}
			}
			if (!strlen($value)) {
				continue;
			}
			$labelText = LocalizationUtility::translate('LLL:EXT:lang/locallang_tca.xlf:sys_file.' . $field, 'lang');
			$title[] = '<dt>' . htmlspecialchars($labelText) . '</dt>' . '<dd>' . $value . '</dd>';
		}
		$params['title'] = '<dl>' . implode('', $title) . '</dl>';
	}
}
