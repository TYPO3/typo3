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

use \TYPO3\CMS\Backend\Utility\BackendUtility;

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
	 * @var string
	 */
	const DISABLED = 'disabled';

	/**
	 * If this is TRUE an error will also be reported if the linked record
	 * is disabled. Otherwise the error will only be reported if the
	 * record is deleted or does not exist.
	 *
	 * @var boolean
	 */
	protected $reportHiddenRecords;

	/**
	 * Checks a given URL for validity
	 *
	 * @param string $url Url to check
	 * @param array $softRefEntry The soft reference entry which builds the context of that url
	 * @param \TYPO3\CMS\Linkvalidator\LinkAnalyzer $reference Parent instance
	 * @return bool TRUE on success or FALSE on error
	 */
	public function checkLink($url, $softRefEntry, $reference) {
		$response = TRUE;
		$errorType = '';
		$errorParams = array();
		$parts = explode(':', $url);
		if (count($parts) !== 3) {
			return $response;
		}

		$tableName = htmlspecialchars($parts[1]);
		$rowid = (int)$parts[2];
		$row = NULL;
		$tsConfig = $reference->getTSConfig();
		$reportHiddenRecords = (bool)$tsConfig['linkhandler.']['reportHiddenRecords'];

		// First check, if we find a non disabled record if the check
		// for hidden records is enabled.
		if ($reportHiddenRecords) {
			$row = $this->getRecordRow($tableName, $rowid, 'disabled');
			if ($row === NULL) {
				$response = FALSE;
				$errorType = self::DISABLED;
			}
		}

		// If no enabled record was found or we did not check that see
		// if we can find a non deleted record.
		if ($row === NULL) {
			$row = $this->getRecordRow($tableName, $rowid, 'deleted');
			if ($row === NULL) {
				$response = FALSE;
				$errorType = self::DELETED;
			}
		}

		// If we did not find a non deleted record, check if we find a
		// deleted one.
		if ($row === NULL) {
			$row = $this->getRecordRow($tableName, $rowid, 'all');
			if ($row === NULL) {
				$response = FALSE;
				$errorType = '';
			}
		}

		if (!$response) {
			$errorParams['errorType'] = $errorType;
			$errorParams['tablename'] = $tableName;
			$errorParams['uid'] = $rowid;
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
		if ($type == 'string' && strtolower(substr($value['tokenValue'], 0, 7)) == 'record:') {
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
			case self::DISABLED:
				$response = $GLOBALS['LANG']->getLL('list.report.rownotvisible');
				$response = str_replace('###title###', $title, $response);
				$response = str_replace('###uid###', $errorParams['uid'], $response);
				break;
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

	/**
	 * Fetches the record with the given UID from the given table.
	 *
	 * The filter option accepts two values:
	 *
	 * "disabled" will filter out disabled and deleted records.
	 * "deleted" filters out deleted records but will return disabled records.
	 * If nothing is specified all records will be returned (including deleted).
	 *
	 * @param string $tableName The name of the table from which the record should be fetched.
	 * @param string $uid The UID of the record that should be fetched.
	 * @param string $filter A filter setting, can be empty or "disabled" or "deleted".
	 * @return array|NULL The result row as associative array or NULL if nothing is found.
	 */
	protected function getRecordRow($tableName, $uid, $filter = '') {

		$whereStatement = 'uid = ' . (int)$uid;

		switch ($filter) {
			case 'disabled':
				$whereStatement .= BackendUtility::BEenableFields($tableName) . BackendUtility::deleteClause($tableName);
				break;
			case 'deleted':
				$whereStatement .= BackendUtility::deleteClause($tableName);
				break;
		}

		$row = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
			'*',
			$tableName,
			$whereStatement
		);

		// Since exec_SELECTgetSingleRow can return NULL or FALSE we
		// make sure we always return NULL if no row was found.
		if ($row === FALSE) {
			$row = NULL;
		}

		return $row;
	}

	/**
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}
}
