<?php
namespace TYPO3\CMS\Linkvalidator\Linktype;

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
 * This class provides Check Link Handler plugin implementation
 *
 * @author Dimitri König <dk@cabag.ch>
 * @author Michael Miousse <michael.miousse@infoglobe.ca>
 */
class LinkHandler extends \TYPO3\CMS\Linkvalidator\Linktype\AbstractLinktype {

	/**
	 * @var string
	 */
	const DELETED = 'deleted';

	/**
	 * Checks a given URL for validity
	 *
	 * @param string $url Url to check
	 * @param array $softRefEntry The soft reference entry which builds the context of that url
	 * @param \TYPO3\CMS\Linkvalidator\LinkAnalyzer $reference Parent instance
	 * @return boolean TRUE on success or FALSE on error
	 */
	public function checkLink($url, $softRefEntry, $reference) {
		$response = TRUE;
		$errorParams = array();
		$parts = explode(':', $url);
		if (count($parts) == 3) {
			$tableName = htmlspecialchars($parts[1]);
			$rowid = (int)$parts[2];
			$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', $tableName, 'uid = ' . (int)$rowid);
			if ($row) {
				if ($row['deleted'] == '1') {
					$errorParams['errorType'] = self::DELETED;
					$errorParams['tablename'] = $tableName;
					$errorParams['uid'] = $rowid;
					$response = FALSE;
				}
			} else {
				$errorParams['tablename'] = $tableName;
				$errorParams['uid'] = $rowid;
				$response = FALSE;
			}
		}

		if (!$response) {
			$this->setErrorParams($errorParams);
		}
		return $response;
	}

	/**
	 * Type fetching method, based on the type that softRefParserObj returns
	 *
	 * @param array $value Reference properties
	 * @param string $type Current type
	 * @param string $key Validator hook name
	 * @return string fetched type
	 */
	public function fetchType($value, $type, $key) {
		if ($value['type'] === 'string' && GeneralUtility::isFirstPartOfStr(strtolower($value['tokenValue']), 'record:')) {
			$type = 'linkhandler';
		}
		return $type;
	}

	/**
	 * Generate the localized error message from the error params saved from the parsing
	 *
	 * @param array $errorParams All parameters needed for the rendering of the error message
	 * @return string Validation error message
	 */
	public function getErrorMessage($errorParams) {
		$errorType = $errorParams['errorType'];
		$tableName = $errorParams['tablename'];
		if (!empty($GLOBALS['TCA'][$tableName]['ctrl']['title'])) {
			$title = $GLOBALS['LANG']->sL($GLOBALS['TCA'][$tableName]['ctrl']['title'], TRUE);
		} else {
			$title = $tableName;
		}
		switch ($errorType) {
			case self::DELETED:
				$response = str_replace(
					array(
						'###title###',
						'###uid###'
					),
					array(
						$title,
						$errorParams['uid']
					),
					$GLOBALS['LANG']->getLL('list.report.rowdeleted')
				);
				break;
			default:
				$response = str_replace('###uid###', $errorParams['uid'], $GLOBALS['LANG']->getLL('list.report.rownotexisting'));
		}
		return $response;
	}
}
