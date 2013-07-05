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

use TYPO3\CMS\Backend\Utility\BackendUtility;
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
		// Configuration
		$title = array();
		$sysFileFields = isset($params['options']['sys_file']) && is_array($params['options']['sys_file'])
			? $params['options']['sys_file']
			: array();
		$fileInfo = BackendUtility::splitTable_Uid($params['row']['uid_local'], 2);
		$fileRecord = BackendUtility::getRecord($fileInfo[0], $fileInfo[1]);

		// By table sys_file
		foreach ($sysFileFields as $field) {
			if ($field === 'title') {
				if (strlen($params['row']['title'])) {
					$value = $params['row']['title'];
				} else {
					$value = BackendUtility::getRecordTitle('sys_file', $fileRecord, TRUE);
				}
			} else {
				$value = BackendUtility::getRecordTitlePrep($fileRecord[$field]);
			}
			$labelText = LocalizationUtility::translate('LLL:EXT:lang/locallang_tca.xlf:sys_file.' . $field, 'lang');
			$title[] = '<dt>' . $labelText . '</dt>' . '<dd>' . $value . '</dd>';
		}

		$params['title'] = '<dl>' . implode('', $title) . '</dl>';
	}

}

?>