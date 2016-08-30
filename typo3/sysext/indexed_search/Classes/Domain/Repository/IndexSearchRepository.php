<?php
namespace TYPO3\CMS\IndexedSearch\Domain\Repository;

/*
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
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\IndexedSearch\Indexer;
use TYPO3\CMS\IndexedSearch\Utility;

/**
 * Index search abstraction to search through the index
 */
class IndexSearchRepository
{
    /**
     * Indexer object
     *
     * @var Indexer
     */
    protected $indexerObj;

    /**
     * External Parsers
     *
     * @var array
     */
    protected $externalParsers = [];

    /**
     * Frontend User Group List
     *
     * @var string
     */
    protected $frontendUserGroupList = '';

    /**
     * Sections
     * formally known as $this->piVars['sections']
     *
     * @var string
     */
    protected $sections = null;

    /**
     * Search type
     * formally known as $this->piVars['type']
     *
     * @var string
     */
    protected $searchType = null;

    /**
     * Language uid
     * formally known as $this->piVars['lang']
     *
     * @var int
     */
    protected $languageUid = null;

    /**
     * Media type
     * formally known as $this->piVars['media']
     *
     * @var int
     */
    protected $mediaType = null;

    /**
     * Sort order
     * formally known as $this->piVars['sort_order']
     *
     * @var string
     */
    protected $sortOrder = null;

    /**
     * Descending sort order flag
     * formally known as $this->piVars['desc']
     *
     * @var bool
     */
    protected $descendingSortOrderFlag = null;

    /**
     * Result page pointer
     * formally known as $this->piVars['pointer']
     *
     * @var int
     */
    protected $resultpagePointer = 0;

    /**
     * Number of results
     * formally known as $this->piVars['result']
     *
     * @var int
     */
    protected $numberOfResults = 10;

    /**
     * list of all root pages that will be used
     * If this value is set to less than zero (eg. -1) searching will happen
     * in ALL of the page tree with no regard to branches at all.
     *
     * @var string
     */
    protected $searchRootPageIdList;

    /**
     * formally known as $conf['search.']['searchSkipExtendToSubpagesChecking']
     * enabled through settings.searchSkipExtendToSubpagesChecking
     *
     * @var bool
     */
    protected $joinPagesForQuery = false;

    /**
     * Select clauses for individual words, will be filled during the search
     *
     * @var array
     */
    protected $wSelClauses = [];

    /**
     * Flag for exact search count
     * formally known as $conf['search.']['exactCount']
     *
     * Continue counting and checking of results even if we are sure
     * they are not displayed in this request. This will slow down your
     * page rendering, but it allows precise search result counters.
     * enabled through settings.exactCount
     *
     * @var bool
     */
    protected $useExactCount = false;

    /**
     * Display forbidden records
     * formally known as $this->conf['show.']['forbiddenRecords']
     *
     * enabled through settings.displayForbiddenRecords
     *
     * @var bool
     */
    protected $displayForbiddenRecords = false;

    /**
     * initialize all options that are necessary for the search
     *
     * @param array $settings the extbase plugin settings
     * @param array $searchData the search data
     * @param array $externalParsers
     * @param string $searchRootPageIdList
     * @return void
     */
    public function initialize($settings, $searchData, $externalParsers, $searchRootPageIdList)
    {
        // Initialize the indexer-class - just to use a few function (for making hashes)
        $this->indexerObj = GeneralUtility::makeInstance(Indexer::class);
        $this->externalParsers = $externalParsers;
        $this->searchRootPageIdList = $searchRootPageIdList;
        $this->frontendUserGroupList = $this->getTypoScriptFrontendController()->gr_list;
        // Should we use joinPagesForQuery instead of long lists of uids?
        if ($settings['searchSkipExtendToSubpagesChecking']) {
            $this->joinPagesForQuery = 1;
        }
        if ($settings['exactCount']) {
            $this->useExactCount = true;
        }
        if ($settings['displayForbiddenRecords']) {
            $this->displayForbiddenRecords = true;
        }
        $this->sections = $searchData['sections'];
        $this->searchType = $searchData['searchType'];
        $this->languageUid = $searchData['languageUid'];
        $this->mediaType = isset($searchData['mediaType']) ? $searchData['mediaType'] : false;
        $this->sortOrder = $searchData['sortOrder'];
        $this->descendingSortOrderFlag = $searchData['desc'];
        $this->resultpagePointer = $searchData['pointer'];
        if (isset($searchData['numberOfResults']) && is_numeric($searchData['numberOfResults'])) {
            $this->numberOfResults = (int)$searchData['numberOfResults'];
        }
    }

    /**
     * Get search result rows / data from database. Returned as data in array.
     *
     * @param array $searchWords Search word array
     * @param int $freeIndexUid Pointer to which indexing configuration you want to search in. -1 means no filtering. 0 means only regular indexed content.
     * @return bool|array FALSE if no result, otherwise an array with keys for first row, result rows and total number of results found.
     */
    public function doSearch($searchWords, $freeIndexUid = -1)
    {
        // Getting SQL result pointer:
        $this->getTimeTracker()->push('Searching result');
        if ($hookObj = &$this->hookRequest('getResultRows_SQLpointer')) {
            $res = $hookObj->getResultRows_SQLpointer($searchWords, $freeIndexUid);
        } else {
            $res = $this->getResultRows_SQLpointer($searchWords, $freeIndexUid);
        }
        $this->getTimeTracker()->pull();
        // Organize and process result:
        if ($res) {
            // Total search-result count
            $count = $this->getDatabaseConnection()->sql_num_rows($res);
            // The pointer is set to the result page that is currently being viewed
            $pointer = MathUtility::forceIntegerInRange($this->resultpagePointer, 0, floor($count / $this->numberOfResults));
            // Initialize result accumulation variables:
            $c = 0;
            // Result pointer: Counts up the position in the current search-result
            $grouping_phashes = [];
            // Used to filter out duplicates.
            $grouping_chashes = [];
            // Used to filter out duplicates BASED ON cHash.
            $firstRow = [];
            // Will hold the first row in result - used to calculate relative hit-ratings.
            $resultRows = [];
            // Will hold the results rows for display.
            // Now, traverse result and put the rows to be displayed into an array
            // Each row should contain the fields from 'ISEC.*, IP.*' combined
            // + artificial fields "show_resume" (bool) and "result_number" (counter)
            while ($row = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
                // Set first row
                if (!$c) {
                    $firstRow = $row;
                }
                // Tells whether we can link directly to a document
                // or not (depends on possible right problems)
                $row['show_resume'] = $this->checkResume($row);
                $phashGr = !in_array($row['phash_grouping'], $grouping_phashes);
                $chashGr = !in_array(($row['contentHash'] . '.' . $row['data_page_id']), $grouping_chashes);
                if ($phashGr && $chashGr) {
                    // Only if the resume may be shown are we going to filter out duplicates...
                    if ($row['show_resume'] || $this->displayForbiddenRecords) {
                        // Only on documents which are not multiple pages documents
                        if (!$this->multiplePagesType($row['item_type'])) {
                            $grouping_phashes[] = $row['phash_grouping'];
                        }
                        $grouping_chashes[] = $row['contentHash'] . '.' . $row['data_page_id'];
                        // Increase the result pointer
                        $c++;
                        // All rows for display is put into resultRows[]
                        if ($c > $pointer * $this->numberOfResults && $c <= $pointer * $this->numberOfResults + $this->numberOfResults) {
                            $row['result_number'] = $c;
                            $resultRows[] = $row;
                            // This may lead to a problem: If the result check is not stopped here, the search will take longer.
                            // However the result counter will not filter out grouped cHashes/pHashes that were not processed yet.
                            // You can change this behavior using the "search.exactCount" property (see above).
                            if (!$this->useExactCount && $c + 1 > ($pointer + 1) * $this->numberOfResults) {
                                break;
                            }
                        }
                    } else {
                        // Skip this row if the user cannot
                        // view it (missing permission)
                        $count--;
                    }
                } else {
                    // For each time a phash_grouping document is found
                    // (which is thus not displayed) the search-result count is reduced,
                    // so that it matches the number of rows displayed.
                    $count--;
                }
            }

            $this->getDatabaseConnection()->sql_free_result($res);

            return [
                'resultRows' => $resultRows,
                'firstRow' => $firstRow,
                'count' => $count
            ];
        } else {
            // No results found
            return false;
        }
    }

    /**
     * Gets a SQL result pointer to traverse for the search records.
     *
     * @param array $searchWords Search words
     * @param int $freeIndexUid Pointer to which indexing configuration you want to search in. -1 means no filtering. 0 means only regular indexed content.
     * @return bool|\mysqli_result
     */
    protected function getResultRows_SQLpointer($searchWords, $freeIndexUid = -1)
    {
        // This SEARCHES for the searchwords in $searchWords AND returns a
        // COMPLETE list of phash-integers of the matches.
        $list = $this->getPhashList($searchWords);
        // Perform SQL Search / collection of result rows array:
        if ($list) {
            // Do the search:
            $this->getTimeTracker()->push('execFinalQuery');
            $res = $this->execFinalQuery($list, $freeIndexUid);
            $this->getTimeTracker()->pull();
            return $res;
        } else {
            return false;
        }
    }

    /***********************************
     *
     *	Helper functions on searching (SQL)
     *
     ***********************************/
    /**
     * Returns a COMPLETE list of phash-integers matching the search-result composed of the search-words in the $searchWords array.
     * The list of phash integers are unsorted and should be used for subsequent selection of index_phash records for display of the result.
     *
     * @param array $searchWords Search word array
     * @return string List of integers
     */
    protected function getPhashList($searchWords)
    {
        // Initialize variables:
        $c = 0;
        // This array accumulates the phash-values
        $totalHashList = [];
        $this->wSelClauses = [];
        // Traverse searchwords; for each, select all phash integers and merge/diff/intersect them with previous word (based on operator)
        foreach ($searchWords as $k => $v) {
            // Making the query for a single search word based on the search-type
            $sWord = $v['sword'];
            $theType = (string)$this->searchType;
            // If there are spaces in the search-word, make a full text search instead.
            if (strstr($sWord, ' ')) {
                $theType = 20;
            }
            $this->getTimeTracker()->push('SearchWord "' . $sWord . '" - $theType=' . $theType);
            // Perform search for word:
            switch ($theType) {
                case '1':
                    // Part of word
                    $res = $this->searchWord($sWord, Utility\LikeWildcard::BOTH);
                    break;
                case '2':
                    // First part of word
                    $res = $this->searchWord($sWord, Utility\LikeWildcard::RIGHT);
                    break;
                case '3':
                    // Last part of word
                    $res = $this->searchWord($sWord, Utility\LikeWildcard::LEFT);
                    break;
                case '10':
                    // Sounds like
                    /**
                    * Indexer object
                    *
                    * @var Indexer
                    */
                    $indexerObj = GeneralUtility::makeInstance(Indexer::class);
                    // Perform metaphone search
                    $storeMetaphoneInfoAsWords = !$this->isTableUsed('index_words');
                    $res = $this->searchMetaphone($indexerObj->metaphone($sWord, $storeMetaphoneInfoAsWords));
                    unset($indexerObj);
                    break;
                case '20':
                    // Sentence
                    $res = $this->searchSentence($sWord);
                    // If there is a fulltext search for a sentence there is
                    // a likeliness that sorting cannot be done by the rankings
                    // from the rel-table (because no relations will exist for the
                    // sentence in the word-table). So therefore mtime is used instead.
                    // It is not required, but otherwise some hits may be left out.
                    $this->sortOrder = 'mtime';
                    break;
                default:
                    // Distinct word
                    $res = $this->searchDistinct($sWord);
            }
            // If there was a query to do, then select all phash-integers which resulted from this.
            if ($res) {
                // Get phash list by searching for it:
                $phashList = [];
                while ($row = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
                    $phashList[] = $row['phash'];
                }
                $this->getDatabaseConnection()->sql_free_result($res);
                // Here the phash list are merged with the existing result based on whether we are dealing with OR, NOT or AND operations.
                if ($c) {
                    switch ($v['oper']) {
                        case 'OR':
                            $totalHashList = array_unique(array_merge($phashList, $totalHashList));
                            break;
                        case 'AND NOT':
                            $totalHashList = array_diff($totalHashList, $phashList);
                            break;
                        default:
                            // AND...
                            $totalHashList = array_intersect($totalHashList, $phashList);
                    }
                } else {
                    // First search
                    $totalHashList = $phashList;
                }
            }
            $this->getTimeTracker()->pull();
            $c++;
        }
        return implode(',', $totalHashList);
    }

    /**
     * Returns a query which selects the search-word from the word/rel tables.
     *
     * @param string $wordSel WHERE clause selecting the word from phash
     * @param string $additionalWhereClause Additional AND clause in the end of the query.
     * @return bool|\mysqli_result SQL result pointer
     */
    protected function execPHashListQuery($wordSel, $additionalWhereClause = '')
    {
        return $this->getDatabaseConnection()->exec_SELECTquery(
            'IR.phash',
            'index_words IW, index_rel IR, index_section ISEC',
            $wordSel . ' AND IW.wid=IR.wid AND ISEC.phash=IR.phash' . $this->sectionTableWhere() . $additionalWhereClause,
            'IR.phash'
        );
    }

    /**
     * Search for a word
     *
     * @param string $sWord the search word
     * @param int $wildcard Bit-field of Utility\LikeWildcard
     * @return bool|\mysqli_result SQL result pointer
     */
    protected function searchWord($sWord, $wildcard)
    {
        $likeWildcard = Utility\LikeWildcard::cast($wildcard);
        $wSel = $likeWildcard->getLikeQueryPart(
            'index_words',
            'IW.baseword',
            $sWord
        );
        $this->wSelClauses[] = $wSel;
        return $this->execPHashListQuery($wSel, ' AND is_stopword=0');
    }

    /**
     * Search for one distinct word
     *
     * @param string $sWord the search word
     * @return bool|\mysqli_result SQL result pointer
     */
    protected function searchDistinct($sWord)
    {
        $wSel = 'IW.wid=' . $this->md5inthash($sWord);
        $this->wSelClauses[] = $wSel;
        return $this->execPHashListQuery($wSel, ' AND is_stopword=0');
    }

    /**
     * Search for a sentence
     *
     * @param string $sWord the search word
     * @return bool|\mysqli_result SQL result pointer
     */
    protected function searchSentence($sWord)
    {
        $this->wSelClauses[] = '1=1';
        $likeWildcard = Utility\LikeWildcard::cast(Utility\LikeWildcard::BOTH);
        $likePart = $likeWildcard->getLikeQueryPart(
            'index_fulltext',
            'IFT.fulltextdata',
            $sWord
        );

        return $this->getDatabaseConnection()->exec_SELECTquery(
            'ISEC.phash',
            'index_section ISEC, index_fulltext IFT',
            $likePart . ' AND ISEC.phash = IFT.phash' . $this->sectionTableWhere(),
            'ISEC.phash'
        );
    }

    /**
     * Search for a metaphone word
     *
     * @param string $sWord the search word
     * @return bool|\mysqli_result SQL result pointer
     */
    protected function searchMetaphone($sWord)
    {
        $wSel = 'IW.metaphone=' . $sWord;
        $this->wSelClauses[] = $wSel;
        return $this->execPHashListQuery($wSel, ' AND is_stopword=0');
    }

    /**
     * Returns AND statement for selection of section in database. (rootlevel 0-2 + page_id)
     *
     * @return string AND clause for selection of section in database.
     */
    public function sectionTableWhere()
    {
        $whereClause = '';
        $match = false;
        if (!($this->searchRootPageIdList < 0)) {
            $whereClause = ' AND ISEC.rl0 IN (' . $this->searchRootPageIdList . ') ';
        }
        if (substr($this->sections, 0, 4) == 'rl1_') {
            $list = implode(',', GeneralUtility::intExplode(',', substr($this->sections, 4)));
            $whereClause .= ' AND ISEC.rl1 IN (' . $list . ')';
            $match = true;
        } elseif (substr($this->sections, 0, 4) == 'rl2_') {
            $list = implode(',', GeneralUtility::intExplode(',', substr($this->sections, 4)));
            $whereClause .= ' AND ISEC.rl2 IN (' . $list . ')';
            $match = true;
        } elseif (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['addRootLineFields'])) {
            // Traversing user configured fields to see if any of those are used to limit search to a section:
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['addRootLineFields'] as $fieldName => $rootLineLevel) {
                if (substr($this->sections, 0, strlen($fieldName) + 1) == $fieldName . '_') {
                    $list = implode(',', GeneralUtility::intExplode(',', substr($this->sections, strlen($fieldName) + 1)));
                    $whereClause .= ' AND ISEC.' . $fieldName . ' IN (' . $list . ')';
                    $match = true;
                    break;
                }
            }
        }
        // If no match above, test the static types:
        if (!$match) {
            switch ((string)$this->sections) {
                case '-1':
                    $whereClause .= ' AND ISEC.page_id=' . $this->getTypoScriptFrontendController()->id;
                    break;
                case '-2':
                    $whereClause .= ' AND ISEC.rl2=0';
                    break;
                case '-3':
                    $whereClause .= ' AND ISEC.rl2>0';
                    break;
            }
        }
        return $whereClause;
    }

    /**
     * Returns AND statement for selection of media type
     *
     * @return string AND statement for selection of media type
     */
    public function mediaTypeWhere()
    {
        switch ($this->mediaType) {
            case '0':
                // '0' => 'Kun TYPO3 sider',
                $whereClause = ' AND IP.item_type=' . $this->getDatabaseConnection()->fullQuoteStr('0', 'index_phash');
                break;
            case '-2':
                // All external documents
                $whereClause = ' AND IP.item_type!=' . $this->getDatabaseConnection()->fullQuoteStr('0', 'index_phash');
                break;
            case false:

            case '-1':
                // All content
                $whereClause = '';
                break;
            default:
                $whereClause = ' AND IP.item_type=' . $this->getDatabaseConnection()->fullQuoteStr($this->mediaType, 'index_phash');
        }
        return $whereClause;
    }

    /**
     * Returns AND statement for selection of language
     *
     * @return string AND statement for selection of language
     */
    public function languageWhere()
    {
        // -1 is the same as ALL language.
        if ($this->languageUid >= 0) {
            return ' AND IP.sys_language_uid=' . (int)$this->languageUid;
        }
        return '';
    }

    /**
     * Where-clause for free index-uid value.
     *
     * @param int $freeIndexUid Free Index UID value to limit search to.
     * @return string WHERE SQL clause part.
     */
    public function freeIndexUidWhere($freeIndexUid)
    {
        $freeIndexUid = (int)$freeIndexUid;
        if ($freeIndexUid >= 0) {
            // First, look if the freeIndexUid is a meta configuration:
            $indexCfgRec = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('indexcfgs', 'index_config', 'type=5 AND uid=' . $freeIndexUid . $this->enableFields('index_config'));
            if (is_array($indexCfgRec)) {
                $refs = GeneralUtility::trimExplode(',', $indexCfgRec['indexcfgs']);
                // Default value to protect against empty array.
                $list = [-99];
                foreach ($refs as $ref) {
                    list($table, $uid) = GeneralUtility::revExplode('_', $ref, 2);
                    $uid = (int)$uid;
                    switch ($table) {
                        case 'index_config':
                            $idxRec = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('uid', 'index_config', 'uid=' . $uid . $this->enableFields('index_config'));
                            if ($idxRec) {
                                $list[] = $uid;
                            }
                            break;
                        case 'pages':
                            $indexCfgRecordsFromPid = $this->getDatabaseConnection()->exec_SELECTgetRows('uid', 'index_config', 'pid=' . $uid . $this->enableFields('index_config'));
                            foreach ($indexCfgRecordsFromPid as $idxRec) {
                                $list[] = $idxRec['uid'];
                            }
                            break;
                    }
                }
                $list = array_unique($list);
            } else {
                $list = [$freeIndexUid];
            }
            return ' AND IP.freeIndexUid IN (' . implode(',', $list) . ')';
        }
        return '';
    }

    /**
     * Execute final query, based on phash integer list. The main point is sorting the result in the right order.
     *
     * @param string $list List of phash integers which match the search.
     * @param int $freeIndexUid Pointer to which indexing configuration you want to search in. -1 means no filtering. 0 means only regular indexed content.
     * @return bool|\mysqli_result Query result pointer
     */
    protected function execFinalQuery($list, $freeIndexUid = -1)
    {
        // Setting up methods of filtering results
        // based on page types, access, etc.
        $page_join = '';
        // Indexing configuration clause:
        $freeIndexUidClause = $this->freeIndexUidWhere($freeIndexUid);
        // Calling hook for alternative creation of page ID list
        if ($hookObj = $this->hookRequest('execFinalQuery_idList')) {
            $page_where = $hookObj->execFinalQuery_idList($list);
        } elseif ($this->joinPagesForQuery) {
            // Alternative to getting all page ids by ->getTreeList() where
            // "excludeSubpages" is NOT respected.
            $page_join = ',
				pages';
            $page_where = 'pages.uid = ISEC.page_id
				' . $this->enableFields('pages') . '
				AND pages.no_search=0
				AND pages.doktype<200
			';
        } elseif ($this->searchRootPageIdList >= 0) {
            // Collecting all pages IDs in which to search;
            // filtering out ALL pages that are not accessible due to enableFields.
            // Does NOT look for "no_search" field!
            $siteIdNumbers = GeneralUtility::intExplode(',', $this->searchRootPageIdList);
            $pageIdList = [];
            foreach ($siteIdNumbers as $rootId) {
                $pageIdList[] = $this->getTypoScriptFrontendController()->cObj->getTreeList(-1 * $rootId, 9999);
            }
            $page_where = 'ISEC.page_id IN (' . implode(',', $pageIdList) . ')';
        } else {
            // Disable everything... (select all)
            $page_where = '1=1';
        }
        // otherwise select all / disable everything
        // If any of the ranking sortings are selected, we must make a
        // join with the word/rel-table again, because we need to
        // calculate ranking based on all search-words found.
        if (substr($this->sortOrder, 0, 5) === 'rank_') {
            switch ($this->sortOrder) {
                case 'rank_flag':
                    // This gives priority to word-position (max-value) so that words in title, keywords, description counts more than in content.
                    // The ordering is refined with the frequency sum as well.
                    $grsel = 'MAX(IR.flags) AS order_val1, SUM(IR.freq) AS order_val2';
                    $orderBy = 'order_val1' . $this->getDescendingSortOrderFlag() . ', order_val2' . $this->getDescendingSortOrderFlag();
                    break;
                case 'rank_first':
                    // Results in average position of search words on page.
                    // Must be inversely sorted (low numbers are closer to top)
                    $grsel = 'AVG(IR.first) AS order_val';
                    $orderBy = 'order_val' . $this->getDescendingSortOrderFlag(true);
                    break;
                case 'rank_count':
                    // Number of words found
                    $grsel = 'SUM(IR.count) AS order_val';
                    $orderBy = 'order_val' . $this->getDescendingSortOrderFlag();
                    break;
                default:
                    // Frequency sum. I'm not sure if this is the best way to do
                    // it (make a sum...). Or should it be the average?
                    $grsel = 'SUM(IR.freq) AS order_val';
                    $orderBy = 'order_val' . $this->getDescendingSortOrderFlag();
            }
            $wordSel = '';
            if (!empty($this->wSelClauses)) {
                // So, words are imploded into an OR statement (no "sentence search" should be done here - may deselect results)
                $wordSel = '(' . implode(' OR ', $this->wSelClauses) . ') AND ';
            }
            $res = $this->getDatabaseConnection()->exec_SELECTquery(
                'ISEC.*, IP.*, ' . $grsel,
                'index_words IW,
					index_rel IR,
					index_section ISEC,
					index_phash IP' . $page_join,
                $wordSel .
                'IP.phash IN (' . $list . ') ' .
                    $this->mediaTypeWhere() . ' ' . $this->languageWhere() . $freeIndexUidClause . '
					AND IW.wid=IR.wid
					AND ISEC.phash = IR.phash
					AND IP.phash = IR.phash
					AND ' . $page_where,
                'IP.phash,ISEC.phash,ISEC.phash_t3,ISEC.rl0,ISEC.rl1,ISEC.rl2 ,ISEC.page_id,ISEC.uniqid,IP.phash_grouping,IP.data_filename ,IP.data_page_id ,IP.data_page_reg1,IP.data_page_type,IP.data_page_mp,IP.gr_list,IP.item_type,IP.item_title,IP.item_description,IP.item_mtime,IP.tstamp,IP.item_size,IP.contentHash,IP.crdate,IP.parsetime,IP.sys_language_uid,IP.item_crdate,IP.cHashParams,IP.externalUrl,IP.recordUid,IP.freeIndexUid,IP.freeIndexSetId',
                $orderBy
            );
        } else {
            // Otherwise, if sorting are done with the pages table or other fields,
            // there is no need for joining with the rel/word tables:
            $orderBy = '';
            switch ((string)$this->sortOrder) {
                case 'title':
                    $orderBy = 'IP.item_title' . $this->getDescendingSortOrderFlag();
                    break;
                case 'crdate':
                    $orderBy = 'IP.item_crdate' . $this->getDescendingSortOrderFlag();
                    break;
                case 'mtime':
                    $orderBy = 'IP.item_mtime' . $this->getDescendingSortOrderFlag();
                    break;
            }
            $res = $this->getDatabaseConnection()->exec_SELECTquery('ISEC.*, IP.*', 'index_phash IP,index_section ISEC' . $page_join, 'IP.phash IN (' . $list . ') ' . $this->mediaTypeWhere() . $this->languageWhere() . $freeIndexUidClause . '
							AND IP.phash = ISEC.phash AND ' . $page_where, 'IP.phash,ISEC.phash,ISEC.phash_t3,ISEC.rl0,ISEC.rl1,ISEC.rl2 ,ISEC.page_id,ISEC.uniqid,IP.phash_grouping,IP.data_filename ,IP.data_page_id ,IP.data_page_reg1,IP.data_page_type,IP.data_page_mp,IP.gr_list,IP.item_type,IP.item_title,IP.item_description,IP.item_mtime,IP.tstamp,IP.item_size,IP.contentHash,IP.crdate,IP.parsetime,IP.sys_language_uid,IP.item_crdate,IP.cHashParams,IP.externalUrl,IP.recordUid,IP.freeIndexUid,IP.freeIndexSetId', $orderBy);
        }
        return $res;
    }

    /**
     * Checking if the resume can be shown for the search result
     * (depending on whether the rights are OK)
     * ? Should it also check for gr_list "0,-1"?
     *
     * @param array $row Result row array.
     * @return bool Returns TRUE if resume can safely be shown
     */
    protected function checkResume($row)
    {
        // If the record is indexed by an indexing configuration, just show it.
        // At least this is needed for external URLs and files.
        // For records we might need to extend this - for instance block display if record is access restricted.
        if ($row['freeIndexUid']) {
            return true;
        }
        // Evaluate regularly indexed pages based on item_type:
        // External media:
        if ($row['item_type']) {
            // For external media we will check the access of the parent page on which the media was linked from.
            // "phash_t3" is the phash of the parent TYPO3 page row which initiated the indexing of the documents in this section.
            // So, selecting for the grlist records belonging to the parent phash-row where the current users gr_list exists will help us to know.
            // If this is NOT found, there is still a theoretical possibility that another user accessible page would display a link, so maybe the resume of such a document here may be unjustified hidden. But better safe than sorry.
            if ($this->isTableUsed('index_grlist')) {
                $res = $this->getDatabaseConnection()->exec_SELECTquery('phash', 'index_grlist', 'phash=' . (int)$row['phash_t3'] . ' AND gr_list=' . $this->getDatabaseConnection()->fullQuoteStr($this->frontendUserGroupList, 'index_grlist'));
            } else {
                $res = false;
            }
            if ($res && $this->getDatabaseConnection()->sql_num_rows($res)) {
                return true;
            } else {
                return false;
            }
        } else {
            // Ordinary TYPO3 pages:
            if ((string)$row['gr_list'] !== (string)$this->frontendUserGroupList) {
                // Selecting for the grlist records belonging to the phash-row where the current users gr_list exists. If it is found it is proof that this user has direct access to the phash-rows content although he did not himself initiate the indexing...
                if ($this->isTableUsed('index_grlist')) {
                    $res = $this->getDatabaseConnection()->exec_SELECTquery('phash', 'index_grlist', 'phash=' . (int)$row['phash'] . ' AND gr_list=' . $this->getDatabaseConnection()->fullQuoteStr($this->frontendUserGroupList, 'index_grlist'));
                } else {
                    $res = false;
                }
                if ($res && $this->getDatabaseConnection()->sql_num_rows($res)) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return true;
            }
        }
    }

    /**
     * Returns "DESC" or "" depending on the settings of the incoming
     * highest/lowest result order (piVars['desc'])
     *
     * @param bool $inverse If TRUE, inverse the order which is defined by piVars['desc']
     * @return string " DESC" or
     * @formallyknownas tx_indexedsearch_pi->isDescending
     */
    protected function getDescendingSortOrderFlag($inverse = false)
    {
        $desc = $this->descendingSortOrderFlag;
        if ($inverse) {
            $desc = !$desc;
        }
        return !$desc ? ' DESC' : '';
    }

    /**
     * Returns a part of a WHERE clause which will filter out records with start/end times or hidden/fe_groups fields
     * set to values that should de-select them according to the current time, preview settings or user login.
     * Definitely a frontend function.
     * THIS IS A VERY IMPORTANT FUNCTION: Basically you must add the output from this function for EVERY select query you create
     * for selecting records of tables in your own applications - thus they will always be filtered according to the "enablefields"
     * configured in TCA
     * Simply calls \TYPO3\CMS\Frontend\Page\PageRepository::enableFields() BUT will send the show_hidden flag along!
     * This means this function will work in conjunction with the preview facilities of the frontend engine/Admin Panel.
     *
     * @param string $table The table for which to get the where clause
     * @return string The part of the where clause on the form " AND [fieldname]=0 AND ...". Eg. " AND hidden=0 AND starttime < 123345567
     * @see \TYPO3\CMS\Frontend\Page\PageRepository::enableFields()
     */
    protected function enableFields($table)
    {
        return $this->getTypoScriptFrontendController()->sys_page->enableFields($table, $table === 'pages' ? $this->getTypoScriptFrontendController()->showHiddenPage : $this->getTypoScriptFrontendController()->showHiddenRecords);
    }

    /**
     * Returns if an item type is a multipage item type
     *
     * @param string $itemType Item type
     * @return bool TRUE if multipage capable
     */
    protected function multiplePagesType($itemType)
    {
        /** @var \TYPO3\CMS\IndexedSearch\FileContentParser $fileContentParser */
        $fileContentParser = $this->externalParsers[$itemType];
        return is_object($fileContentParser) && $fileContentParser->isMultiplePageExtension($itemType);
    }

    /**
     * md5 integer hash
     * Using 7 instead of 8 just because that makes the integers lower than
     * 32 bit (28 bit) and so they do not interfere with UNSIGNED integers
     * or PHP-versions which has varying output from the hexdec function.
     *
     * @param string $str String to hash
     * @return int Integer intepretation of the md5 hash of input string.
     */
    protected function md5inthash($str)
    {
        return Utility\IndexedSearchUtility::md5inthash($str);
    }

    /**
     * Check if the tables provided are configured for usage.
     * This becomes necessary for extensions that provide additional database
     * functionality like indexed_search_mysql.
     *
     * @param string $table_list Comma-separated list of tables
     * @return bool TRUE if given tables are enabled
     */
    protected function isTableUsed($table_list)
    {
        return Utility\IndexedSearchUtility::isTableUsed($table_list);
    }

    /**
     * Returns an object reference to the hook object if any
     *
     * @param string $functionName Name of the function you want to call / hook key
     * @return object|NULL Hook object, if any. Otherwise NULL.
     */
    public function hookRequest($functionName)
    {
        // Hook: menuConfig_preProcessModMenu
        if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['pi1_hooks'][$functionName]) {
            $hookObj = GeneralUtility::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['pi1_hooks'][$functionName]);
            if (method_exists($hookObj, $functionName)) {
                $hookObj->pObj = $this;
                return $hookObj;
            }
        }
        return null;
    }

    /**
     * Search type
     * e.g. sentence (20), any part of the word (1)
     *
     * @return int
     */
    public function getSearchType()
    {
        return (int)$this->searchType;
    }

    /**
     * A list of integer which should be root-pages to search from
     *
     * @return int[]
     */
    public function getSearchRootPageIdList()
    {
        return GeneralUtility::intExplode(',', $this->searchRootPageIdList);
    }

    /**
     * Getter for joinPagesForQuery flag
     * enabled through TypoScript 'settings.skipExtendToSubpagesChecking'
     *
     * @return bool
     */
    public function getJoinPagesForQuery()
    {
        return $this->joinPagesForQuery;
    }

    /**
     * Returns the database connection
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * @return \TYPO3\CMS\Core\TimeTracker\TimeTracker
     */
    protected function getTimeTracker()
    {
        return $GLOBALS['TT'];
    }
}
