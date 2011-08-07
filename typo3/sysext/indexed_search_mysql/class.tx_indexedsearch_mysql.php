<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Michael Stucki (michael@typo3.org)
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
/**
 * Database handler class
 *
 * @author	Michael Stucki <michael@typo3.org>
 */










/**
 * Class that hooks into Indexed Search and replaces standard SQL queries with MySQL fulltext index queries.
 *
 * @author	Michael Stucki <michael@typo3.org>
 * @package TYPO3
 * @subpackage tx_indexedsearch_mysql
 */
class tx_indexedsearch_mysql {
	var $pObj;

	/**
	 * Gets a SQL result pointer to traverse for the search records.
	 *
	 * @param	array		Search words
	 * @param	integer		Pointer to which indexing configuration you want to search in. -1 means no filtering. 0 means only regular indexed content.
	 * @return	pointer
	 */
	function getResultRows_SQLpointer($sWArr, $freeIndexUid = -1)	{
			// Build the search string, detect which fulltext index to use, and decide whether boolean search is needed or not
		$searchData = $this->getSearchString($sWArr);

			// Perform SQL Search / collection of result rows array:
		if ($searchData) {
				// Do the search:
			$GLOBALS['TT']->push('execFinalQuery');
			$res = $this->execFinalQuery_fulltext($searchData ,$freeIndexUid);
			$GLOBALS['TT']->pull();
			return $res;
		} else {
			return FALSE;
		}
	}

	/**
	 * Returns a search string for use with MySQL FULLTEXT query
	 *
	 * @param	array		Search word array
	 * @return	string		Search string
	 */
	function getSearchString($sWArr)	{

			// Initialize variables:
		$count = 0;

		$searchBoolean = FALSE;	// Change this to TRUE to force BOOLEAN SEARCH MODE (useful if fulltext index is still empty)
		$fulltextIndex = 'index_fulltext.fulltextdata';

		$naturalSearchString = '';	// This holds the result if the search is natural (doesn't contain any boolean operators)
		$booleanSearchString = '';	// This holds the result if the search is boolen (contains +/-/| operators)

			// Traverse searchwords and prefix them with corresponding operator
		foreach ($sWArr as $k => $v) {
				// Making the query for a single search word based on the search-type
			$sWord = $v['sword'];	// $GLOBALS['TSFE']->csConvObj->conv_case('utf-8', $v['sword'], 'toLower');	// lower-case all of them...
			$trail_natural = '';
			$trail_boolean = '';

			$theType = (string)$this->pObj->piVars['type'];
			if (strstr($sWord,' ')) {
				$theType = 20;	// If there are spaces in the search-word, make a full text search instead.
			}

			switch ($theType) {
				case '1':	// Part of word
				case '3':	// Last part of word
					// These options are both not possible with fulltext indexing! Therefore, fallback to first-part-of-word search
				case '2':	// First part of word
					$trail_boolean = '*';
						// Part-of-word search requires boolean mode!
					$searchBoolean = TRUE;
				break;
				case '10':	// Sounds like

					/**
					 * Indexer object
					 *
					 * @var tx_indexedsearch_indexer
					 */
					$indexerObj = t3lib_div::makeInstance('tx_indexedsearch_indexer');	// Initialize the indexer-class

						// Perform metaphone search
					$sWord = $indexerObj->metaphone($sWord,$this->pObj->storeMetaphoneInfoAsWords);

					unset($indexerObj);
					$fulltextIndex = 'index_fulltext.metaphonedata';
				break;
				case '20':	// Sentence
					$searchBoolean = TRUE;
					$sWord = preg_replace('/^"(.*)"$/', '$1', $sWord);	// Remove existing quotes as they will be added later anyway...
				break;
				default:	// Distinct word
			}

				// Perform search for word:
			switch ($v['oper']) {
				case 'AND NOT':
					$booleanSearchString.= ' -' . $sWord . $trail_boolean;
					$searchBoolean = TRUE;
				break;
				case 'OR':
					$booleanSearchString.= ' ' . $sWord . $trail_boolean;
					$searchBoolean = TRUE;
				break;
				default:
					$booleanSearchString.= ' +' . $sWord . $trail_boolean;
					$naturalSearchString.= ' ' . $sWord . $trail_natural;
			}

			$count++;
		}

		if ($theType=='20') {	// Sentence
			$searchString = '"' . trim($naturalSearchString) . '"';

		} elseif ($searchBoolean) {
			$searchString = trim($booleanSearchString);

		} else {
			$searchString = trim($naturalSearchString);
		}

		return array(
				'searchBoolean' => $searchBoolean,
				'searchString' => $searchString,
				'fulltextIndex' => $fulltextIndex
			);
	}

	/**
	 * Execute final query, based on phash integer list. The main point is sorting the result in the right order.
	 *
	 * @param	array		Array with search string, boolean indicator, and fulltext index reference
	 * @param	integer		Pointer to which indexing configuration you want to search in. -1 means no filtering. 0 means only regular indexed content.
	 * @return	pointer		Query result pointer
	 */
	function execFinalQuery_fulltext($searchData, $freeIndexUid = -1)	{

			// Setting up methods of filtering results based on page types, access, etc.
		$page_join = '';
		$page_where = '';

			// Indexing configuration clause:
		$freeIndexUidClause = $this->pObj->freeIndexUidWhere($freeIndexUid);

			// Calling hook for alternative creation of page ID list
		if ($hookObj = &$this->pObj->hookRequest('execFinalQuery_idList')) {
			$page_where = $hookObj->execFinalQuery_idList('');	// Originally this hook expects a list of page IDs, so since we don't know them yet, just send an empty string. Users of this hook need to adjust their hook to this!
		} elseif ($this->pObj->join_pages) {	// Alternative to getting all page ids by ->getTreeList() where "excludeSubpages" is NOT respected.
			$page_join = ',
				pages';
			$page_where = 'pages.uid = ISEC.page_id
				'.$this->pObj->cObj->enableFields('pages').'
				AND pages.no_search=0
				AND pages.doktype<200
			';
		} elseif ($this->pObj->wholeSiteIdList >= 0) {	// Collecting all pages IDs in which to search; filtering out ALL pages that are not accessible due to enableFields. Does NOT look for "no_search" field!
			$siteIdNumbers = t3lib_div::intExplode(',', $this->pObj->wholeSiteIdList);
			$id_list = array();
			while(list(,$rootId) = each($siteIdNumbers)) {
				$cObj = t3lib_div::makeInstance('tslib_cObj');
				$id_list[] = $cObj->getTreeList($rootId, 9999, 0, 0, '', '') . $rootId;
			}
			$page_where = ' ISEC.page_id IN (' . implode(',', $id_list) . ')';
		} else {	// Disable everything... (select all)
			$page_where = ' 1=1';
		}

		$searchBoolean = '';
		if ($searchData['searchBoolean']) {
			$searchBoolean = ' IN BOOLEAN MODE';
		}

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'index_fulltext.*, ISEC.*, IP.*',
				'index_fulltext, index_section ISEC, index_phash IP' . $page_join,
				'MATCH ('.$searchData['fulltextIndex'].') AGAINST ('.$GLOBALS['TYPO3_DB']->fullQuoteStr($searchData['searchString'],'index_fulltext').$searchBoolean.') '.
					$this->pObj->mediaTypeWhere().' '.
					$this->pObj->languageWhere().
					$freeIndexUidClause.'
					AND index_fulltext.phash = IP.phash
					AND ISEC.phash = IP.phash
					AND '.$page_where,
				'IP.phash,ISEC.phash,ISEC.phash_t3,ISEC.rl0,ISEC.rl1,ISEC.rl2 ,ISEC.page_id,ISEC.uniqid,IP.phash_grouping,IP.data_filename ,IP.data_page_id ,IP.data_page_reg1,IP.data_page_type,IP.data_page_mp,IP.gr_list,IP.item_type,IP.item_title,IP.item_description,IP.item_mtime,IP.tstamp,IP.item_size,IP.contentHash,IP.crdate,IP.parsetime,IP.sys_language_uid,IP.item_crdate,IP.cHashParams,IP.externalUrl,IP.recordUid,IP.freeIndexUid,IP.freeIndexSetId'
			);

		return $res;
	}
}

?>