<?php
namespace TYPO3\CMS\Linkvalidator\Linktype;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005 - 2013 Jochen Rieger (j.rieger@connecta.ag)
 *  (c) 2010 - 2013 Michael Miousse (michael.miousse@infoglobe.ca)
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
	 * TSconfig of the module tx_linkhandler
	 *
	 * @var array
	 */
	protected $tsconfig;

	/**
	 * Get TSconfig when loading the class
	 */
	public function __construct() {
		$this->tsconfig = \TYPO3\CMS\Backend\Utility\BackendUtility::getModTSconfig(1, 'mod.tx_linkhandler');
	}

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
			$rowid = intval($parts[2]);
			$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', $tableName, 'uid = ' . intval($rowid));
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
		$title = $GLOBALS['LANG']->getLL('list.report.rowdeleted.default.title');
		if ($this->tsconfig['properties'][$tableName . '.']) {
			$title = $this->tsconfig['properties'][$tableName . '.']['label'];
		}
		switch ($errorType) {
		case self::DELETED:
			$response = $GLOBALS['LANG']->getLL('list.report.rowdeleted');
			$response = str_replace('###title###', $title, $response);
			$response = str_replace('###uid###', $errorParams['uid'], $response);
			break;
		default:
			$response = $GLOBALS['LANG']->getLL('list.report.rownotexisting');
			$response = str_replace('###uid###', $errorParams['uid'], $response);
			break;
		}
		return $response;
	}
}
?>