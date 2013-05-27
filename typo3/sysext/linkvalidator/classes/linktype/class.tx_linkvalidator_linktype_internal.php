<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005 - 2010 Jochen Rieger (j.rieger@connecta.ag)
 *  (c) 2010 - 2011 Michael Miousse (michael.miousse@infoglobe.ca)
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
 * This class provides Check Internal Links plugin implementation
 *
 * @author Dimitri König <dk@cabag.ch>
 * @author Michael Miousse <michael.miousse@infoglobe.ca>
 * @package TYPO3
 * @subpackage linkvalidator
 */
class tx_linkvalidator_linktype_Internal extends tx_linkvalidator_linktype_Abstract {

	const DELETED = 'deleted';
	const HIDDEN = 'hidden';
	const MOVED = 'moved';
	const NOTEXISTING = 'notExisting';

	/**
	 * All parameters needed for rendering the error message
	 *
	 * @var array
	 */
	protected $errorParams = array();

	/**
	 * Result of the check, if the current page uid is valid or not
	 *
	 * @var boolean
	 */
	protected $responsePage = TRUE;

	/**
	 * Result of the check, if the current content uid is valid or not
	 *
	 * @var boolean
	 */
	protected $responseContent = TRUE;

	/**
	 * Checks a given URL + /path/filename.ext for validity
	 *
	 * @param string $url Url to check as page-id or page-id#anchor (if anchor is present)
	 * @param array $softRefEntry: The soft reference entry which builds the context of that url
	 * @param tx_linkvalidator_Processor $reference Parent instance of tx_linkvalidator_Processor
	 * @return boolean TRUE on success or FALSE on error
	 */
	public function checkLink($url, $softRefEntry, $reference) {
		$anchor = '';
		$this->responseContent = TRUE;

			// Might already contain values - empty it
		unset($this->errorParams);

			// defines the linked page and anchor (if any).
		if (strpos($url, '#c') !== FALSE) {
			$parts = explode('#c', $url);
			$page = $parts[0];
			$anchor = $parts[1];
		} else {
			$page = $url;
		}

			// Check if the linked page is OK
		$this->responsePage = $this->checkPage($page, $softRefEntry, $reference);

			// Check if the linked content element is OK
		if ($anchor) {

				// Check if the content element is OK
			$this->responseContent = $this->checkContent($page, $anchor, $softRefEntry, $reference);

		}

		if ((is_array($this->errorParams['page']) && !$this->responsePage)
			|| (is_array($this->errorParams['content']) && !$this->responseContent)) {
			$this->setErrorParams($this->errorParams);
		}

		if (($this->responsePage === TRUE) && ($this->responseContent === TRUE)) {
			$response = TRUE;
		} else {
			$response = FALSE;
		}

		return $response;
	}

	/**
	 * Checks a given page uid for validity
	 *
	 * @param string $page Page uid to check
	 * @param array $softRefEntry The soft reference entry which builds the context of that url
	 * @param tx_linkvalidator_Processor $reference Parent instance of tx_linkvalidator_Processor
	 * @return boolean TRUE on success or FALSE on error
	 */
	protected function checkPage($page, $softRefEntry, $reference) {
		$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
			'uid, title, deleted, hidden, starttime, endtime',
			'pages',
			'uid = ' . intval($page)
		);
		$this->responsePage = TRUE;

		if ($row) {
			if ($row['deleted'] == '1') {
				$this->errorParams['errorType']['page'] = self::DELETED;
				$this->errorParams['page']['title'] = $row['title'];
				$this->errorParams['page']['uid'] = $row['uid'];
				$this->responsePage = FALSE;
			} elseif ($row['hidden'] == '1'
				|| $GLOBALS['EXEC_TIME'] < intval($row['starttime'])
				|| ($row['endtime'] && intval($row['endtime']) < $GLOBALS['EXEC_TIME'])) {
				$this->errorParams['errorType']['page'] = self::HIDDEN;
				$this->errorParams['page']['title'] = $row['title'];
				$this->errorParams['page']['uid'] = $row['uid'];
				$this->responsePage = FALSE;
			}
		} else {
			$this->errorParams['errorType']['page'] = self::NOTEXISTING;
			$this->errorParams['page']['uid'] = intval($page);
			$this->responsePage = FALSE;
		}

		return $this->responsePage;
	}

	/**
	 * Checks a given content uid for validity
	 *
	 * @param string $page Uid of the page to which the link is pointing
	 * @param string $anchor Uid of the content element to check
	 * @param array $softRefEntry The soft reference entry which builds the context of that url
	 * @param tx_linkvalidator_Processor $reference Parent instance of tx_linkvalidator_Processor
	 * @return boolean TRUE on success or FALSE on error
	 */
	protected function checkContent($page, $anchor, $softRefEntry, $reference) {
			// Get page ID on which the content element in fact is located
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
			'uid, pid, header, deleted, hidden, starttime, endtime',
			'tt_content',
			'uid = ' . intval($anchor)
		);
		$this->responseContent = TRUE;

			// this content element exists
		if ($res) {
				// page ID on which this CE is in fact located.
			$correctPageID = $res['pid'];

				// Check if the element is on the linked page
				// (The element might have been moved to another page)
			if (!($correctPageID === $page)) {
				$this->errorParams['errorType']['content'] = self::MOVED;
				$this->errorParams['content']['uid'] = intval($anchor);
				$this->errorParams['content']['wrongPage'] = intval($page);
				$this->errorParams['content']['rightPage'] = intval($correctPageID);
				$this->responseContent = FALSE;

			} else {
					// The element is located on the page to which the link is pointing
				if ($res['deleted'] == '1') {
					$this->errorParams['errorType']['content'] = self::DELETED;
					$this->errorParams['content']['title'] = $res['header'];
					$this->errorParams['content']['uid'] = $res['uid'];
					$this->responseContent = FALSE;
				} elseif ($res['hidden'] == '1'
					|| $GLOBALS['EXEC_TIME'] < intval($res['starttime'])
					|| ($res['endtime'] && intval($res['endtime']) < $GLOBALS['EXEC_TIME'])) {
					$this->errorParams['errorType']['content'] = self::HIDDEN;
					$this->errorParams['content']['title'] = $res['header'];
					$this->errorParams['content']['uid'] = $res['uid'];
					$this->responseContent = FALSE;
				}
			}

		} else {
				// The content element does not exist
			$this->errorParams['errorType']['content'] = self::NOTEXISTING;
			$this->errorParams['content']['uid'] = intval($anchor);
			$this->responseContent = FALSE;
		}

		return $this->responseContent;
	}

	/**
	 * Generate the localized error message from the error params saved from the parsing
	 *
	 * @param array $errorParams All parameters needed for the rendering of the error message
	 * @return string Validation error message
	 */
	public function getErrorMessage($errorParams) {
		$errorType = $errorParams['errorType'];

		if (is_array($errorParams['page'])) {
			switch ($errorType['page']) {
				case self::DELETED:
					$errorPage = $GLOBALS['LANG']->getLL('list.report.pagedeleted');
					$errorPage = str_replace('###title###', $errorParams['page']['title'], $errorPage);
					$errorPage = str_replace('###uid###', $errorParams['page']['uid'], $errorPage);
					break;

				case self::HIDDEN:
					$errorPage = $GLOBALS['LANG']->getLL('list.report.pagenotvisible');
					$errorPage = str_replace('###title###', $errorParams['page']['title'], $errorPage);
					$errorPage = str_replace('###uid###', $errorParams['page']['uid'], $errorPage);
					break;

				default:
					$errorPage = $GLOBALS['LANG']->getLL('list.report.pagenotexisting');
					$errorPage = str_replace('###uid###', $errorParams['page']['uid'], $errorPage);
			}
		}

		if (is_array($errorParams['content'])) {
			switch ($errorType['content']) {
				case self::DELETED:
					$errorContent = $GLOBALS['LANG']->getLL('list.report.contentdeleted');
					$errorContent = str_replace('###title###', $errorParams['content']['title'], $errorContent);
					$errorContent = str_replace('###uid###', $errorParams['content']['uid'], $errorContent);
					break;

				case self::HIDDEN:
					$errorContent = $GLOBALS['LANG']->getLL('list.report.contentnotvisible');
					$errorContent = str_replace('###title###', $errorParams['content']['title'], $errorContent);
					$errorContent = str_replace('###uid###', $errorParams['content']['uid'], $errorContent);
					break;

				case self::MOVED:
					$errorContent = $GLOBALS['LANG']->getLL('list.report.contentmoved');
					$errorContent = str_replace('###title###', $errorParams['content']['title'], $errorContent);
					$errorContent = str_replace('###uid###', $errorParams['content']['uid'], $errorContent);
					$errorContent = str_replace('###wrongpage###', $errorParams['content']['wrongPage'], $errorContent);
					$errorContent = str_replace('###rightpage###', $errorParams['content']['rightPage'], $errorContent);
					break;

				default:
					$errorContent = $GLOBALS['LANG']->getLL('list.report.contentnotexisting');
					$errorContent = str_replace('###uid###', $errorParams['content']['uid'], $errorContent);
			}
		}

		if (isset($errorPage) && isset($errorContent)) {
			$response = $errorPage . '<br />' . $errorContent;
		} elseif (isset($errorPage)) {
			$response = $errorPage;
		} elseif (isset($errorContent)) {
			$response = $errorContent;
		} else {
				// This should not happen
			$response = $GLOBALS['LANG']->getLL('list.report.noinformation');
		}

		return $response;
	}

	/**
	 * Construct a valid Url for browser output
	 *
	 * @param array $row Broken link record
	 * @return string Parsed broken url
	 */
	public function getBrokenUrl($row) {
		$domain = rtrim(t3lib_div::getIndpEnv('TYPO3_SITE_URL'), '/');
		$rootLine = t3lib_BEfunc::BEgetRootLine($row['record_pid']);
			// checks alternate domains
		if (count($rootLine) > 0) {
				$protocol = t3lib_div::getIndpEnv('TYPO3_SSL') ? 'https://' : 'http://';
				$domainRecord = t3lib_BEfunc::firstDomainRecord($rootLine);
				if(!empty($domainRecord)) {
					$domain = $protocol . $domainRecord;
				}
		}
		return $domain . '/index.php?id=' . $row['url'];
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/linkvalidator/classes/linktypes/class.tx_linkvalidator_linktypes_internal.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/linkvalidator/classes/linktypes/class.tx_linkvalidator_linktypes_internal.php']);
}

?>