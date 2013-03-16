<?php
namespace TYPO3\CMS\Linkvalidator;

/***************************************************************
 *  Copyright notice
 *
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
 * This class provides Processing plugin implementation
 *
 * @author Michael Miousse <michael.miousse@infoglobe.ca>
 * @author Jochen Rieger <j.rieger@connecta.ag>
 */
class LinkAnalyzer {

	/**
	 * Array of tables and fields to search for broken links
	 *
	 * @var array
	 */
	protected $searchFields = array();

	/**
	 * List of comma separated page uids (rootline downwards)
	 *
	 * @var string
	 */
	protected $pidList = '';

	/**
	 * Array of tables and the number of external links they contain
	 *
	 * @var array
	 */
	protected $linkCounts = array();

	/**
	 * Array of tables and the number of broken external links they contain
	 *
	 * @var array
	 */
	protected $brokenLinkCounts = array();

	/**
	 * Array of tables and records containing broken links
	 *
	 * @var array
	 */
	protected $recordsWithBrokenLinks = array();

	/**
	 * Array for hooks for own checks
	 *
	 * @var \TYPO3\CMS\Linkvalidator\Linktype\AbstractLinktype[]
	 */
	protected $hookObjectsArr = array();

	/**
	 * Array with information about the current page
	 *
	 * @var array
	 */
	protected $extPageInTreeInfo = array();

	/**
	 * Reference to the current element with table:uid, e.g. pages:85
	 *
	 * @var string
	 */
	protected $recordReference = '';

	/**
	 * Linked page together with a possible anchor, e.g. 85#c105
	 *
	 * @var string
	 */
	protected $pageWithAnchor = '';

	/**
	 * Fill hookObjectsArr with different link types and possible XClasses.
	 */
	public function __construct() {
		$GLOBALS['LANG']->includeLLFile('EXT:linkvalidator/Resources/Private/Language/Module/locallang.xml');
		// Hook to handle own checks
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'] as $key => $classRef) {
				$this->hookObjectsArr[$key] = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
			}
		}
	}

	/**
	 * Store all the needed configuration values in class variables
	 *
	 * @param array $searchField List of fields in which to search for links
	 * @param string $pid List of comma separated page uids in which to search for links
	 * @return 	void
	 */
	public function init(array $searchField, $pid) {
		$this->searchFields = $searchField;
		$this->pidList = $pid;
	}

	/**
	 * Find all supported broken links and store them in tx_linkvalidator_link
	 *
	 * @param array $checkOptions List of hook object to activate
	 * @param boolean $considerHidden Defines whether to look into hidden fields
	 * @return void
	 */
	public function getLinkStatistics($checkOptions = array(), $considerHidden = FALSE) {
		$results = array();
		if (count($checkOptions) > 0) {
			$checkKeys = array_keys($checkOptions);
			$checkLinkTypeCondition = ' AND link_type IN (\'' . implode('\',\'', $checkKeys) . '\')';
			$GLOBALS['TYPO3_DB']->exec_DELETEquery(
				'tx_linkvalidator_link',
				'(record_pid IN (' . $this->pidList . ')' .
					' OR ( record_uid IN (' . $this->pidList . ') AND table_name like \'pages\'))' .
					$checkLinkTypeCondition
			);
			// Traverse all configured tables
			foreach ($this->searchFields as $table => $fields) {
				if ($table === 'pages') {
					$where = 'deleted = 0 AND uid IN (' . $this->pidList . ')';
				} else {
					$where = 'deleted = 0 AND pid IN (' . $this->pidList . ')';
				}
				if (!$considerHidden) {
					$where .= \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields($table);
				}
				// If table is not configured, assume the extension is not installed
				// and therefore no need to check it
				if (!is_array($GLOBALS['TCA'][$table])) {
					continue;
				}
				// Re-init selectFields for table
				$selectFields = 'uid, pid';
				$selectFields .= ', ' . $GLOBALS['TCA'][$table]['ctrl']['label'] . ', ' . implode(', ', $fields);
				// TODO: only select rows that have content in at least one of the relevant fields (via OR)
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($selectFields, $table, $where);
				// Get record rows of table
				while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) !== FALSE) {
					// Analyse each record
					$this->analyzeRecord($results, $table, $fields, $row);
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
			}
			foreach ($this->hookObjectsArr as $key => $hookObj) {
				if (is_array($results[$key]) && empty($checkOptions) || is_array($results[$key]) && $checkOptions[$key]) {
					//  Check them
					foreach ($results[$key] as $entryKey => $entryValue) {
						$table = $entryValue['table'];
						$record = array();
						$record['headline'] = $entryValue['row'][$GLOBALS['TCA'][$table]['ctrl']['label']];
						$record['record_pid'] = $entryValue['row']['pid'];
						$record['record_uid'] = $entryValue['uid'];
						$record['table_name'] = $table;
						$record['link_title'] = $entryValue['link_title'];
						$record['field'] = $entryValue['field'];
						$record['last_check'] = time();
						$this->recordReference = $entryValue['substr']['recordRef'];
						$this->pageWithAnchor = $entryValue['pageAndAnchor'];
						if (!empty($this->pageWithAnchor)) {
							// Page with anchor, e.g. 18#1580
							$url = $this->pageWithAnchor;
						} else {
							$url = $entryValue['substr']['tokenValue'];
						}
						$this->linkCounts[$table]++;
						$checkUrl = $hookObj->checkLink($url, $entryValue, $this);
						// Broken link found
						if (!$checkUrl) {
							$response = array();
							$response['valid'] = FALSE;
							$response['errorParams'] = $hookObj->getErrorParams();
							$this->brokenLinkCounts[$table]++;
							$record['link_type'] = $key;
							$record['url'] = $url;
							$record['url_response'] = serialize($response);
							$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_linkvalidator_link', $record);
						} elseif (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('showalllinks')) {
							$response = array();
							$response['valid'] = TRUE;
							$this->brokenLinkCounts[$table]++;
							$record['url'] = $url;
							$record['link_type'] = $key;
							$record['url_response'] = serialize($response);
							$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_linkvalidator_link', $record);
						}
					}
				}
			}
		}
	}

	/**
	 * Find all supported broken links for a specific record
	 *
	 * @param array $results Array of broken links
	 * @param string $table Table name of the record
	 * @param array $fields Array of fields to analyze
	 * @param array $record Record to analyse
	 * @return void
	 */
	public function analyzeRecord(array &$results, $table, array $fields, array $record) {
		// Put together content of all relevant fields
		$haystack = '';
		/** @var $htmlParser \TYPO3\CMS\Core\Html\HtmlParser */
		$htmlParser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Html\\HtmlParser');
		$idRecord = $record['uid'];
		// Get all references
		foreach ($fields as $field) {
			$haystack .= $record[$field] . ' --- ';
			$conf = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
			$valueField = $record[$field];
			// Check if a TCA configured field has soft references defined (see TYPO3 Core API document)
			if ($conf['softref'] && strlen($valueField)) {
				// Explode the list of soft references/parameters
				$softRefs = \TYPO3\CMS\Backend\Utility\BackendUtility::explodeSoftRefParserList($conf['softref']);
				// Traverse soft references
				foreach ($softRefs as $spKey => $spParams) {
					/** @var $softRefObj \TYPO3\CMS\Core\Database\SoftReferenceIndex */
					$softRefObj = \TYPO3\CMS\Backend\Utility\BackendUtility::softRefParserObj($spKey);
					// If there is an object returned...
					if (is_object($softRefObj)) {
						// Do processing
						$resultArray = $softRefObj->findRef($table, $field, $idRecord, $valueField, $spKey, $spParams);
						if (!empty($resultArray['elements'])) {
							if ($spKey == 'typolink_tag') {
								$this->analyseTypoLinks($resultArray, $results, $htmlParser, $record, $field, $table);
							} else {
								$this->analyseLinks($resultArray, $results, $record, $field, $table);
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Find all supported broken links for a specific link list
	 *
	 * @param array $resultArray findRef parsed records
	 * @param array $results Array of broken links
	 * @param array $record UID of the current record
	 * @param string $field The current field
	 * @param string $table The current table
	 * @return void
	 */
	protected function analyseLinks(array $resultArray, array &$results, array $record, $field, $table) {
		foreach ($resultArray['elements'] as $element) {
			$r = $element['subst'];
			$type = '';
			$idRecord = $record['uid'];
			if (!empty($r)) {
				/** @var $hookObj \TYPO3\CMS\Linkvalidator\Linktype\AbstractLinktype */
				foreach ($this->hookObjectsArr as $keyArr => $hookObj) {
					$type = $hookObj->fetchType($r, $type, $keyArr);
					// Store the type that was found
					// This prevents overriding by internal validator
					if (!empty($type)) {
						$r['type'] = $type;
					}
				}
				$results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $r['tokenID']]['substr'] = $r;
				$results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $r['tokenID']]['row'] = $record;
				$results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $r['tokenID']]['table'] = $table;
				$results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $r['tokenID']]['field'] = $field;
				$results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $r['tokenID']]['uid'] = $idRecord;
			}
		}
	}

	/**
	 * Find all supported broken links for a specific typoLink
	 *
	 * @param array $resultArray findRef parsed records
	 * @param array $results Array of broken links
	 * @param \TYPO3\CMS\Core\Html\HtmlParser $htmlParser Instance of html parser
	 * @param array $record The current record
	 * @param string $field The current field
	 * @param string $table The current table
	 * @return void
	 */
	protected function analyseTypoLinks(array $resultArray, array &$results, $htmlParser, array $record, $field, $table) {
		$currentR = array();
		$linkTags = $htmlParser->splitIntoBlock('link', $resultArray['content']);
		$idRecord = $record['uid'];
		$type = '';
		$title = '';
		for ($i = 1; $i < count($linkTags); $i += 2) {
			$referencedRecordType = '';
			foreach ($resultArray['elements'] as $element) {
				$type = '';
				$r = $element['subst'];
				if (!empty($r['tokenID'])) {
					if (substr_count($linkTags[$i], $r['tokenID'])) {
						// Type of referenced record
						if (strpos($r['recordRef'], 'pages') !== FALSE) {
							$currentR = $r;
							// Contains number of the page
							$referencedRecordType = $r['tokenValue'];
							$wasPage = TRUE;
						} elseif (strpos($r['recordRef'], 'tt_content') !== FALSE && (isset($wasPage) && $wasPage === TRUE)) {
							$referencedRecordType = $referencedRecordType . '#c' . $r['tokenValue'];
							$wasPage = FALSE;
						} else {
							$currentR = $r;
						}
						$title = strip_tags($linkTags[$i]);
					}
				}
			}
			/** @var $hookObj \TYPO3\CMS\Linkvalidator\Linktype\AbstractLinktype */
			foreach ($this->hookObjectsArr as $keyArr => $hookObj) {
				$type = $hookObj->fetchType($currentR, $type, $keyArr);
				// Store the type that was found
				// This prevents overriding by internal validator
				if (!empty($type)) {
					$currentR['type'] = $type;
				}
			}
			$results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $currentR['tokenID']]['substr'] = $currentR;
			$results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $currentR['tokenID']]['row'] = $record;
			$results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $currentR['tokenID']]['table'] = $table;
			$results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $currentR['tokenID']]['field'] = $field;
			$results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $currentR['tokenID']]['uid'] = $idRecord;
			$results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $currentR['tokenID']]['link_title'] = $title;
			$results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $currentR['tokenID']]['pageAndAnchor'] = $referencedRecordType;
		}
	}

	/**
	 * Fill a marker array with the number of links found in a list of pages
	 *
	 * @param string $curPage Comma separated list of page uids
	 * @return array Marker array with the number of links found
	 */
	public function getLinkCounts($curPage) {
		$markerArray = array();
		if (empty($this->pidList)) {
			$this->pidList = $curPage;
		}
		$this->pidList = rtrim($this->pidList, ',');
		if (($res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'count(uid) as nbBrokenLinks,link_type',
			'tx_linkvalidator_link',
			'record_pid in (' . $this->pidList . ')', 'link_type')
		)) {
			while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) !== FALSE) {
				$markerArray[$row['link_type']] = $row['nbBrokenLinks'];
				$markerArray['brokenlinkCount'] += $row['nbBrokenLinks'];
			}
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		return $markerArray;
	}

	/**
	 * Calls TYPO3\CMS\Backend\FrontendBackendUserAuthentication::extGetTreeList.
	 * Although this duplicates the function TYPO3\CMS\Backend\FrontendBackendUserAuthentication::extGetTreeList
	 * this is necessary to create the object that is used recursively by the original function.
	 *
	 * Generates a list of page uids from $id. List does not include $id itself.
	 * The only pages excluded from the list are deleted pages.
	 *
	 * @param integer $id Start page id
	 * @param integer $depth Depth to traverse down the page tree.
	 * @param integer $begin is an optional integer that determines at which
	 * @param string $permsClause Perms clause
	 * @param boolean $considerHidden Whether to consider hidden pages or not
	 * @return string Returns the list with a comma in the end (if any pages selected!)
	 */
	public function extGetTreeList($id, $depth, $begin = 0, $permsClause, $considerHidden = FALSE) {
		$depth = intval($depth);
		$begin = intval($begin);
		$id = intval($id);
		$theList = '';
		if ($depth > 0) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'uid,title,hidden,extendToSubpages',
				'pages', 'pid=' . $id . ' AND deleted=0 AND ' . $permsClause
			);
			while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) !== FALSE) {
				if ($begin <= 0 && ($row['hidden'] == 0 || $considerHidden == 1)) {
					$theList .= $row['uid'] . ',';
					$this->extPageInTreeInfo[] = array($row['uid'], htmlspecialchars($row['title'], $depth));
				}
				if ($depth > 1 && (!($row['hidden'] == 1 && $row['extendToSubpages'] == 1) || $considerHidden == 1)) {
					$theList .= $this->extGetTreeList($row['uid'], $depth - 1, $begin - 1, $permsClause, $considerHidden);
				}
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}
		return $theList;
	}

	/**
	 * Check if rootline contains a hidden page
	 *
	 * @param array $pageInfo Array with uid, title, hidden, extendToSubpages from pages table
	 * @return boolean TRUE if rootline contains a hidden page, FALSE if not
	 */
	public function getRootLineIsHidden(array $pageInfo) {
		$hidden = FALSE;
		if ($pageInfo['extendToSubpages'] == 1 && $pageInfo['hidden'] == 1) {
			$hidden = TRUE;
		} else {
			if ($pageInfo['pid'] > 0) {
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,title,hidden,extendToSubpages', 'pages', 'uid=' . $pageInfo['pid']);
				while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) !== FALSE) {
					$hidden = $this->getRootLineIsHidden($row);
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
			} else {
				$hidden = FALSE;
			}
		}
		return $hidden;
	}

}
?>