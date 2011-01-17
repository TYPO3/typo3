<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005 - 2010 Michael Miousse (michael.miousse@infoglobe.ca)
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
 * This class provides Check Internal Links plugin implementation.
 *
 * @author Dimitri König <dk@cabag.ch>
 * @author Michael Miousse <michael.miousse@infoglobe.ca>
 * @package TYPO3
 * @subpackage linkvalidator
 */
class tx_linkvalidator_linkTypes_Internal extends tx_linkvalidator_linkTypes_Abstract implements tx_linkvalidator_linkTypes_Interface {

	const DELETED = 'deleted';
	const HIDDEN = 'hidden';

	/**
	 * Checks a given URL + /path/filename.ext for validity
	 *
	 * @param   string	  $url: url to check
	 * @param	 array	   $softRefEntry: the softref entry which builds the context of that url
	 * @param   object	  $reference:  parent instance of tx_linkvalidator_processing
	 * @return  string	  TRUE on success or FALSE on error
	 */
	public function checkLink($url, $softRefEntry, $reference) {
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'uid, title, deleted, hidden, starttime, endtime',
			'pages',
			'uid = ' . intval($url)
		);

		$response = TRUE;
		$errorParams = array();
		if ($rows[0]) {
			if ($rows[0]['deleted'] == '1') {
				$errorParams['errorType'] = DELETED;
				$errorParams['title'] = $rows[0]['title'];
				$errorParams['uid'] = $rows[0]['uid'];
				$response = FALSE;
			} elseif ($rows[0]['hidden'] == '1'
				|| $GLOBALS['EXEC_TIME'] < intval($rows[0]['starttime'])
				|| ($rows[0]['endtime'] && intval($rows[0]['endtime']) < $GLOBALS['EXEC_TIME'])) {
				$errorParams['errorType'] = HIDDEN;
				$errorParams['title'] = $rows[0]['title'];
				$errorParams['uid'] = $rows[0]['uid'];
				$response = FALSE;
			}
		} else {
			$errorParams['errorType'] = 'notExisting';
			$errorParams['uid'] = intval($url);
			$response = FALSE;
		}

		if (is_array($errorParams) && !$response) {
			$this->setErrorParams($errorParams);
		}

		return $response;
	}

	/**
	 * Generate the localized error message from the error params saved from the parsing. 
	 *
	 * @param   array    all parameters needed for the rendering of the error message
	 * @return  string    validation error message
	 */
	public function getErrorMessage($errorParams) {
		$errorType = $errorParams['errorType'];
		switch ($errorType) {
			case DELETED:
				$response = $GLOBALS['LANG']->getLL('list.report.pagedeleted');
				$response = str_replace('###title###', $errorParams['title'], $response);
				$response = str_replace('###uid###', $errorParams['uid'], $response);
				break;

			case HIDDEN:
				$response = $GLOBALS['LANG']->getLL('list.report.pagenotvisible');
				$response = str_replace('###title###', $errorParams['title'], $response);
				$response = str_replace('###uid###', $errorParams['uid'], $response);
				break;
            
			default:
				$response = $GLOBALS['LANG']->getLL('list.report.pagenotexisting');
				$response = str_replace('###uid###', $errorParams['uid'], $response);
				break;
		}

		return $response;
	}

	/**
	 * Url parsing
	 *
	 * @param   array	   $row: broken link record
	 * @return  string	  parsed broken url
	 */
	public function getBrokenUrl($row) {
		$domain = rtrim(t3lib_div::getIndpEnv('TYPO3_SITE_URL'), '/');
		$rootLine = t3lib_BEfunc::BEgetRootLine($row['recpid']);
			// checks alternate domains
		if (count($rootLine) > 0) {
				$protocol = t3lib_div::getIndpEnv('TYPO3_SSL') ? 'https://' : 'http://';
				$domain = $protocol . t3lib_BEfunc::firstDomainRecord($rootLine);
		}
		return $domain . '/index.php?id=' . $row['url'];
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/linkvalidator/classes/linktypes/class.tx_linkvalidator_linktypes_internal.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/linkvalidator/classes/linktypes/class.tx_linkvalidator_linktypes_internal.php']);
}

?>
