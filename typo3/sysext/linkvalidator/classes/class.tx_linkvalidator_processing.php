<?php
/***************************************************************
 *  Copyright notice
 *
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
 * This class provides Processing plugin implementation.
 *
 * @author Michael Miousse <michael.miousse@infoglobe.ca>
 * @author Jochen Rieger <j.rieger@connecta.ag>
 * @package TYPO3
 * @subpackage linkvalidator
 */

$GLOBALS['LANG']->includeLLFile('EXT:linkvalidator/modfunc1/locallang.xml');

class tx_linkvalidator_processing {

	/**
	 * Array of tables and fields to search for broken links.
	 *
	 * @var array
	 */
	protected $searchFields = array();

	/**
	 * List of comma seperated page uids (rootline downwards).
	 *
	 * @var string
	 */
	protected $pidList = '';

	/**
	 * Array of tables and the number of external links they contain.
	 *
	 * @var array
	 */
	protected $linkCounts = array();

	/**
	 * Array of tables and the number of broken external links they contain.
	 *
	 * @var array
	 */
	protected $brokenLinkCounts = array();

	/**
	 * Array of tables and records containing broken links.
	 *
	 * @var array
	 */
	protected $recordsWithBrokenLinks = array();

	/**
	 * Array for hooks for own checks.
	 *
	 * @var array
	 */
	protected $hookObjectsArr = array();

	/**
	 * Array with information about the current page.
	 *
	 * @var array
	 */
	protected $extPageInTreeInfo = array();

	/**
	 * Fill hookObjectsArr with different link types and possible XClasses.
	 */
	public function __construct() {
			// Hook to handle own checks
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'] as $key => $classRef) {
				$this->hookObjectsArr[$key] = &t3lib_div::getUserObj($classRef);
			}
		}
	}

	/**
	 * Init Function: Here all the needed configuration values are stored in class variables.
	 *
	 * @param	array		$searchField: list of fields in which to search for links
	 * @param	string		$pid: list of comma separated page uids in which to search for links
	 * @return	void
	 */
	public function init($searchField, $pid) {
		$this->searchFields = $searchField;
		$this->pidList = $pid;
	}

	/**
	 * Find all supported broken links and store them in tx_linkvalidator_link.
	 *
	 * @param	array		$checkOptions: list of hook object to activate
	 * @param	boolean		$considerHidden: defines whether to look into hidden fields or not
	 * @return	void
	 */
	public function getLinkStatistics($checkOptions = array(), $considerHidden = FALSE) {
		$results = array();
		$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_linkvalidator_link', 'record_pid in (' . $this->pidList . ')');

			// let's traverse all configured tables
		foreach ($this->searchFields as $table => $fields) {
			$where = 'deleted = 0 AND pid IN (' . $this->pidList . ')';
			if (!$considerHidden) {
				$where .= t3lib_BEfunc::BEenableFields($table);
			}
				// if table is not configured, we assume the ext is not installed and therefore no need to check it
			if (!is_array($GLOBALS['TCA'][$table])) continue;

				// re-init selectFields for table
			$selectFields = 'uid, pid';
			$selectFields .= ', ' . $GLOBALS['TCA'][$table]['ctrl']['label'] . ', ' . implode(', ', $fields);
			
				// TODO: only select rows that have content in at least one of the relevant fields (via OR)
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($selectFields, $table, $where);
				// Get record rows of table
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				// Analyse each record
				$this->analyzeRecord($results, $table, $fields, $row);
			}
		}

		foreach ($this->hookObjectsArr as $key => $hookObj) {
			if ((is_array($results[$key])) && empty($checkOptions) || (is_array($results[$key]) && $checkOptions[$key])) {
					//  check'em!
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
							// page with anchor, e.g. 18#1580
						$url = $this->pageWithAnchor;
					} else {
						$url = $entryValue['substr']['tokenValue'];
					}

					$this->linkCounts[$table]++;
					$checkURL = $hookObj->checkLink($url, $entryValue, $this);
						// broken link found!
					if (!$checkURL) {
						$response = array();
						$response['valid'] = FALSE;
						$response['errorParams'] = $hookObj->getErrorParams();
						$this->brokenLinkCounts[$table]++;
						$record['link_type'] = $key;
						$record['url'] = $url;
						$record['url_response'] = serialize($response);
						$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_linkvalidator_link', $record);
					} elseif (t3lib_div::_GP('showalllinks')) {
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


	/**
	 * Find all supported broken links for a specific record.
	 *
	 * @param	array		$results: array of broken links
	 * @param	string		$table: table name of the record
	 * @param	array		$fields: array of fields to analyze
	 * @param	array		$record: record to analyse
	 * @return	void
	 */
	public function analyzeRecord(&$results, $table, $fields, $record) {
		
			// array to store urls from relevant field contents
		$urls = array();

		$referencedRecordType = '';
			// last-parsed link element was a page.
		$wasPage = TRUE;

			// flag whether row contains a broken link in some field or not
		$rowContainsBrokenLink = FALSE;
		
			// put together content of all relevant fields
		$haystack = '';
		$htmlParser = t3lib_div::makeInstance('t3lib_parsehtml');
		
		$idRecord = $record['uid'];
		
			// get all references
		foreach ($fields as $field) {
			$haystack .= $record[$field] . ' --- ';
			$conf = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
			
			$valueField = $record[$field];
			
				// Check if a TCA configured field has softreferences defined (see TYPO3 Core API document)
			if ($conf['softref'] && strlen($valueField)) {
					// Explode the list of softreferences/parameters
				$softRefs = t3lib_BEfunc::explodeSoftRefParserList($conf['softref']);
					// Traverse soft references
				foreach ($softRefs as $spKey => $spParams) {
						// create / get object
					$softRefObj = &t3lib_BEfunc::softRefParserObj($spKey);

						// If there was an object returned...:
					if (is_object($softRefObj)) {

							// Do processing
						$resultArray = $softRefObj->findRef($table, $field, $idRecord, $valueField, $spKey, $spParams);
						if (!empty($resultArray['elements'])) {

							if ($spKey == 'typolink_tag') {
								$linkTags = $htmlParser->splitIntoBlock('link', $resultArray['content']);

								for ($i = 1; $i < count($linkTags); $i += 2) {
									$referencedRecordType = '';
									foreach($resultArray['elements'] as $element) {
											$type = '';
											$r = $element['subst'];

											if (!empty($r['tokenID'])) {
												if (substr_count($linkTags[$i], $r['tokenID'])) {
														// Type of referenced record
													if (strpos($r['recordRef'], 'pages') !== FALSE) {
														$currentR = $r;
															// contains number of the page
														$referencedRecordType = $r['tokenValue'];
														$wasPage = TRUE;
													}
														// append number of content element to the page saved in the last loop
													elseif ((strpos($r['recordRef'], 'tt_content') !== FALSE) && ($wasPage === TRUE)) {
														$referencedRecordType = $referencedRecordType . '#c' . $r['tokenValue'];
														$wasPage = FALSE;
													} else {
														$currentR = $r;
													}
													$title = strip_tags($linkTags[$i]);
												}
											}
									}
									foreach ($this->hookObjectsArr as $keyArr => $hookObj) {
										$type = $hookObj->fetchType($currentR, $type, $keyArr);
									}

									$results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $currentR["tokenID"]]["substr"] = $currentR;
									$results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $currentR["tokenID"]]["row"] = $record;
									$results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $currentR["tokenID"]]["table"] = $table;
									$results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $currentR["tokenID"]]["field"] = $field;
									$results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $currentR["tokenID"]]["uid"] = $idRecord;
									$results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $currentR["tokenID"]]["link_title"] = $title;
									$results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $currentR["tokenID"]]["pageAndAnchor"] = $referencedRecordType;

								}
							} else {

								foreach ($resultArray['elements'] as $element) {
									$r = $element['subst'];
									$title = '';
									$type = '';

									if (!empty($r)) {
											// Parse string for special TYPO3 <link> tag:

										foreach ($this->hookObjectsArr as $keyArr => $hookObj) {
											$type = $hookObj->fetchType($r, $type, $keyArr);
										}
										$results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $r["tokenID"]]["substr"] = $r;
										$results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $r["tokenID"]]["row"] = $record;
										$results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $r["tokenID"]]["table"] = $table;
										$results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $r["tokenID"]]["field"] = $field;
										$results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $r["tokenID"]]["uid"] = $idRecord;

									}
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Fill a markerarray with the number of links found in a list of pages.
	 *
	 * @param   string	   $curPage: comma separated list of page uids
	 * @return  array	   markerarray with the number of links found
	 */
	public function getLinkCounts($curPage) {
		$markerArray = array();
		if (($res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'count(uid) as nbBrokenLinks,link_type',
				'tx_linkvalidator_link',
				'record_pid in (' . $this->pidList . ')',
				'link_type'
		))) {
			while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
				$markerArray[$row['link_type']] = $row['nbBrokenLinks'];
				$markerArray['brokenlinkCount'] += $row['nbBrokenLinks'];
			}
		}
		return $markerArray;
	}
	
	/**
	 * Calls t3lib_tsfeBeUserAuth::extGetTreeList.
	 * Although this duplicates the function t3lib_tsfeBeUserAuth::extGetTreeList
	 * this is necessary to create the object that is used recursively by the original function.
	 *
	 * Generates a list of page uids from $id. List does not include $id itself.
	 * The only pages excluded from the list are deleted pages.
	 *
	 *							  level in the tree to start collecting uids. Zero means
	 *							  'start right away', 1 = 'next level and out'
	 *
	 * @param	integer		Start page id
	 * @param	integer		Depth to traverse down the page tree.
	 * @param	integer		$begin is an optional integer that determines at which
	 * @param	string		Perms clause
	 * @return	string		Returns the list with a comma in the end (if any pages selected!)
	 */
	public function extGetTreeList($id, $depth, $begin = 0, $perms_clause) {
		$depth = intval($depth);
		$begin = intval($begin);
		$id = intval($id);
		$theList = '';

		if ($depth > 0) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'uid,title',
				'pages',
				'pid=' . $id . ' AND deleted=0 AND ' . $perms_clause
			);
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				if ($begin <= 0) {
					$theList .= $row['uid'] . ',';
					$this->extPageInTreeInfo[] = array($row['uid'], htmlspecialchars($row['title'], $depth));
				}
				if ($depth > 1) {
					$theList .= $this->extGetTreeList($row['uid'], $depth - 1, $begin - 1, $perms_clause);
				}
			}
		}
		return $theList;
	}
	

}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/linkvalidator/classes/class.tx_linkvalidator_processing.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/linkvalidator/classes/class.tx_linkvalidator_processing.php']);
}
?>