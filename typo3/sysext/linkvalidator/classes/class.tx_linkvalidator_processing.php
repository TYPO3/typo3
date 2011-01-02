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
 * This class provides Processing plugin implementation.
 *
 * @author Michael Miousse <michael.miousse@infoglobe.ca>
 * @author Jochen Rieger <j.rieger@connecta.ag>
 */

$GLOBALS['LANG']->includeLLFile('EXT:linkvalidator/modfunc1/locallang.xml');

class tx_linkvalidator_processing {

	protected $searchFields = array(); // array of tables and fields to search for broken links
	protected $pidList = ''; // list of pidlist (rootline downwards)
	protected $linkCounts = array(); // array of tables containing number of external link
	protected $brokenLinkCounts = array(); // array of tables containing number of broken external link
	protected $recordsWithBrokenLinks = array(); // array of tables and records containing broken links
	protected $hookObjectsArr = array(); // array for hooks for own checks

	/**
	 * Fill hookObjectsArr with different link types and possible XClasses.
	 */
	function __construct() {
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
	 * Find all supported broken links and store them in tx_linkvalidator_links.
	 *
	 * @param	array		$checkOptions: list of hook object to activate
	 * @param	int			$hidden: defines whether to look into hidden fields or not
	 * @return	void
	 */
	public function getLinkStatistics($checkOptions = array(), $hidden = 0) {
		$results = array();
		$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_linkvalidator_links', 'recpid in (' . $this->pidList . ')');

			// let's traverse all configured tables
		foreach ($this->searchFields as $table => $fields) {
			$where = 'deleted = 0 AND pid IN (' . $this->pidList . ')';
			if (!$hidden) {
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
				$this->analyseRecord($results, $table, $fields, $row);

			}
		}

		foreach ($this->hookObjectsArr as $key => $hookObj) {
			if ((is_array($results[$key])) && empty($checkOptions) || (is_array($results[$key]) && $checkOptions[$key])) {
					//  check'em!
				foreach ($results[$key] as $entryKey => $entryValue) {
					$table = $entryValue['table'];
					$record = array();
					$record['headline'] = $entryValue['row'][$GLOBALS['TCA'][$table]['ctrl']['label']];
					$record['recpid'] = $entryValue['row']['pid'];
					$record['recuid'] = $entryValue['uid'];
					$record['tablename'] = $table;
					$record['linktitle'] = $entryValue['linktitle'];
					$record['field'] = $entryValue['field'];
					$record['lastcheck'] = time();
					$url = $entryValue['substr']['tokenValue'];
					$this->linkCounts[$table]++;
					$checkURL = $hookObj->checkLink($url, $entryValue, $this);
						// broken link found!
					if ($checkURL != 1) {
						$this->brokenLinkCounts[$table]++;
						$record['typelinks'] = $key;
						$record['url'] = $url;
						$record['urlresponse'] = '<span style="color:red">' . $checkURL . '</span>';
						$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_linkvalidator_links', $record);
					} elseif (t3lib_div::_GP('showalllinks')) {
						$this->brokenLinkCounts[$table]++;
						$record['url'] = $url;
						$record['typelinks'] = $key;
						$record['urlresponse'] = '<span style="color:green">OK</span>';
						$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_linkvalidator_links', $record);
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
	public function analyseRecord(&$results, $table, $fields, $record) {
		
			// array to store urls from relevant field contents
		$urls = array();

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

					if (is_object($softRefObj)) { // If there was an object returned...:
							// Do processing
						$resultArray = $softRefObj->findRef($table, $field, $idRecord, $valueField, $spKey, $spParams);
						if (!empty($resultArray['elements'])) {

							$tagAttr = array();
							if ($spKey == 'typolink_tag') {
								$linkTags = $htmlParser->splitIntoBlock('link', $resultArray['content']);
								foreach ($linkTags as $tag) {
									$attr = $htmlParser->split_tag_attributes($tag);
									$tagAttr[$tag] = $attr[0];
								}
							}
							
							foreach ($resultArray['elements'] as $element) {
								$r = $element['subst'];
								$title = '';
									// Parse string for special TYPO3 <link> tag:

								if ($spKey == 'typolink_tag') {
									foreach ($tagAttr as $tag => $attr) {
										if (in_array('{softref:' . $r['tokenID'] . '}', $attr)) {
											$title = strip_tags($tag);
										}
									}
								}
								$type = '';
								if (!empty($r)) {
									foreach ($this->hookObjectsArr as $keyArr => $hookObj) {
										$type = $hookObj->fetchType($r, $type, $keyArr);
									}
									$results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $r["tokenID"]]["substr"] = $r;
									$results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $r["tokenID"]]["row"] = $record;
									$results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $r["tokenID"]]["table"] = $table;
									$results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $r["tokenID"]]["field"] = $field;
									$results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $r["tokenID"]]["uid"] = $idRecord;
									$results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $r["tokenID"]]["linktitle"] = $title;
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
				'count(uid) as nbBrokenLinks,typelinks',
				'tx_linkvalidator_links',
				'recpid in (' . $this->pidList . ')',
				'typelinks'
		))) {
			while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
				$markerArray[$row['typelinks']] = $row['nbBrokenLinks'];
				$markerArray['brokenlinkCount'] += $row['nbBrokenLinks'];
			}
		}
		return $markerArray;
	}

}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/linkvalidator/classes/class.tx_linkvalidator_processing.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/linkvalidator/classes/class.tx_linkvalidator_processing.php']);
}
?>