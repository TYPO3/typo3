<?php

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

namespace TYPO3\CMS\IndexedSearch\Domain\Repository;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\IndexedSearch\FileContentParser;
use TYPO3\CMS\IndexedSearch\Indexer;
use TYPO3\CMS\IndexedSearch\Utility\IndexedSearchUtility;
use TYPO3\CMS\IndexedSearch\Utility\LikeWildcard;

/**
 * Index search abstraction to search through the index
 * @internal This class is a specific repository implementation and is not considered part of the Public TYPO3 API.
 */
class IndexSearchRepository
{
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
    protected $sections;

    /**
     * Search type
     * formally known as $this->piVars['type']
     *
     * @var int
     */
    protected int $searchType = 0;

    /**
     * Language uid
     * formally known as $this->piVars['lang']
     *
     * @var int
     */
    protected $languageUid;

    /**
     * Media type
     * formally known as $this->piVars['media']
     *
     * @var int
     */
    protected $mediaType;

    /**
     * Sort order
     * formally known as $this->piVars['sort_order']
     *
     * @var string
     */
    protected $sortOrder = '';

    /**
     * Descending sort order flag
     * formally known as $this->piVars['desc']
     *
     * @var bool
     */
    protected $descendingSortOrderFlag;

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
    protected $searchRootPageIdList = '';

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
     */
    public function initialize($settings, $searchData, $externalParsers, $searchRootPageIdList)
    {
        $this->externalParsers = $externalParsers;
        $this->searchRootPageIdList = (string)$searchRootPageIdList;
        $this->frontendUserGroupList = implode(',', GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('frontend.user', 'groupIds', [0, -1]));
        // Should we use joinPagesForQuery instead of long lists of uids?
        if ($settings['searchSkipExtendToSubpagesChecking'] ?? false) {
            $this->joinPagesForQuery = true;
        }
        if ($settings['exactCount'] ?? false) {
            $this->useExactCount = true;
        }
        if ($settings['displayForbiddenRecords'] ?? false) {
            $this->displayForbiddenRecords = true;
        }
        $this->sections = (string)($searchData['sections'] ?? '');
        $this->searchType = (int)($searchData['searchType'] ?? 0);
        $this->languageUid = (int)($searchData['languageUid'] ?? 0);
        $this->mediaType = $searchData['mediaType'] ?? 0;
        $this->sortOrder = (string)($searchData['sortOrder'] ?? '');
        $this->descendingSortOrderFlag = $searchData['desc'] ?? false;
        $this->resultpagePointer = (int)($searchData['pointer'] ?? 0);
        if (is_numeric($searchData['numberOfResults'] ?? null)) {
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
        $useMysqlFulltext = (bool)GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('indexed_search', 'useMysqlFulltext');
        // Getting SQL result pointer:
        $this->getTimeTracker()->push('Searching result');
        // @todo Change hook and method signatures to return the QueryBuilder instead the Result. Consider to move
        //       from hook to a proper PSR-14 event.
        if ($hookObj = $this->hookRequest('getResultRows_SQLpointer')) {
            $result = $hookObj->getResultRows_SQLpointer($searchWords, $freeIndexUid);
        } elseif ($useMysqlFulltext) {
            $result = $this->getResultRows_SQLpointerMysqlFulltext($searchWords, $freeIndexUid);
        } else {
            $result = $this->getResultRows_SQLpointer($searchWords, $freeIndexUid);
        }
        $this->getTimeTracker()->pull();
        // Organize and process result:
        if ($result) {
            // We need the result row count beforehand for the pointer calculation. Using $result->rowCount() for
            // select queries is not reliable across dbms systems, and in case of sqlite this will return 0 here,
            // even if there is a result set, we need to retrieve all rows and doing a count on the array.
            // @todo Change this to second count() query call, after getResultRows_SQLpointer() signatures/hook has
            //       been changed to return QueryBuilder instead of the Result.
            $rows = $result->fetchAllAssociative();
            // Total search-result count
            $count = count($rows);
            // The pointer is set to the result page that is currently being viewed
            $pointer = MathUtility::forceIntegerInRange($this->resultpagePointer, 0, (int)floor($count / $this->numberOfResults));
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
            // @todo Change this back to while($row = $result->fetchAssociative()) after changing
            //       getResultRows_SQLpointer() returning QueryBuilder instead of a Result.
            foreach ($rows as $row) {
                // Set first row
                if (!$c) {
                    $firstRow = $row;
                }
                // Tells whether we can link directly to a document
                // or not (depends on possible right problems)
                $row['show_resume'] = $this->checkResume($row);
                $phashGr = !in_array($row['phash_grouping'], $grouping_phashes);
                $chashGr = !in_array($row['contentHash'] . '.' . $row['data_page_id'], $grouping_chashes);
                if ($phashGr && $chashGr) {
                    // Only if the resume may be shown are we going to filter out duplicates...
                    if ($row['show_resume'] || $this->displayForbiddenRecords) {
                        // Only on documents which are not multiple pages documents
                        if (!$this->multiplePagesType($row['item_type'] ?? '')) {
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
                            // You can change this behavior using the "settings.exactCount" property (see above).
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

            return [
                'resultRows' => $resultRows,
                'firstRow' => $firstRow,
                'count' => $count,
            ];
        }
        // No results found
        return false;
    }

    /**
     * Gets a SQL result pointer to traverse for the search records.
     *
     * @param array $searchWords Search words
     * @param int $freeIndexUid Pointer to which indexing configuration you want to search in. -1 means no filtering. 0 means only regular indexed content.
     * @return \Doctrine\DBAL\Result|int|bool
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
        }
        return false;
    }

    /**
     * Gets a SQL result pointer to traverse for the search records.
     *
     * mysql fulltext specific version triggered by ext_conf_template setting 'useMysqlFulltext'
     *
     * @param array $searchWordsArray Search words
     * @param int $freeIndexUid Pointer to which indexing configuration you want to search in. -1 means no filtering. 0 means only regular indexed content.
     * @return \Doctrine\DBAL\Result|int|bool DBAL result statement
     */
    protected function getResultRows_SQLpointerMysqlFulltext($searchWordsArray, $freeIndexUid = -1)
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('index_fulltext');
        if (strpos($connection->getServerVersion(), 'MySQL') !== 0) {
            throw new \RuntimeException(
                'Extension indexed_search is configured to use mysql fulltext, but table \'index_fulltext\''
                . ' is running on a different DBMS.',
                1472585525
            );
        }
        // Build the search string, detect which fulltext index to use, and decide whether boolean search is needed or not
        $searchData = $this->getSearchString($searchWordsArray);
        // Perform SQL Search / collection of result rows array:
        $resource = false;
        if ($searchData) {
            $timeTracker = GeneralUtility::makeInstance(TimeTracker::class);
            // Do the search:
            $timeTracker->push('execFinalQuery');
            $resource = $this->execFinalQuery_fulltext($searchData, $freeIndexUid);
            $timeTracker->pull();
        }
        return $resource;
    }

    /**
     * Returns a search string for use with MySQL FULLTEXT query
     *
     * mysql fulltext specific helper method
     *
     * @param array $searchWordArray Search word array
     * @return array Search string
     */
    protected function getSearchString($searchWordArray)
    {
        // Change this to TRUE to force BOOLEAN SEARCH MODE (useful if fulltext index is still empty)
        $searchBoolean = false;
        $fulltextIndex = 'index_fulltext.fulltextdata';
        // This holds the result if the search is natural (doesn't contain any boolean operators)
        $naturalSearchString = '';
        // This holds the result if the search is boolean (contains +/-/| operators)
        $booleanSearchString = '';

        $searchType = $this->getSearchType();

        // Traverse searchwords and prefix them with corresponding operator
        foreach ($searchWordArray as $searchWordData) {
            // Making the query for a single search word based on the search-type
            $searchWord = $searchWordData['sword'];
            $wildcard = '';
            if (str_contains($searchWord, ' ')) {
                $searchType = 20;
            }
            switch ($searchType) {
                case 1:
                case 2:
                case 3:
                    // First part of word
                    $wildcard = '*';
                    // Part-of-word search requires boolean mode!
                    $searchBoolean = true;
                    break;
                case 10:
                    $indexerObj = GeneralUtility::makeInstance(Indexer::class);
                    $searchWord = $indexerObj->metaphone($searchWord, $indexerObj->storeMetaphoneInfoAsWords);
                    $fulltextIndex = 'index_fulltext.metaphonedata';
                    break;
                case 20:
                    $searchBoolean = true;
                    // Remove existing quotes and fix misplaced quotes.
                    $searchWord = trim(str_replace('"', ' ', $searchWord));
                    break;
            }
            // Perform search for word:
            switch ($searchWordData['oper']) {
                case 'AND NOT':
                    $booleanSearchString .= ' -' . $searchWord . $wildcard;
                    $searchBoolean = true;
                    break;
                case 'OR':
                    $booleanSearchString .= ' ' . $searchWord . $wildcard;
                    $searchBoolean = true;
                    break;
                default:
                    $booleanSearchString .= ' +' . $searchWord . $wildcard;
                    $naturalSearchString .= ' ' . $searchWord;
            }
        }
        if ($searchType === 20) {
            $searchString = '"' . trim($naturalSearchString) . '"';
        } elseif ($searchBoolean) {
            $searchString = trim($booleanSearchString);
        } else {
            $searchString = trim($naturalSearchString);
        }
        return [
            'searchBoolean' => $searchBoolean,
            'searchString' => $searchString,
            'fulltextIndex' => $fulltextIndex,
        ];
    }

    /**
     * Execute final query, based on phash integer list. The main point is sorting the result in the right order.
     *
     * mysql fulltext specific helper method
     *
     * @param array $searchData Array with search string, boolean indicator, and fulltext index reference
     * @param int $freeIndexUid Pointer to which indexing configuration you want to search in. -1 means no filtering. 0 means only regular indexed content.
     * @return \Doctrine\DBAL\Result
     */
    protected function execFinalQuery_fulltext($searchData, $freeIndexUid = -1)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('index_fulltext');
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->select('index_fulltext.*', 'ISEC.*', 'IP.*')
            ->from('index_fulltext')
            ->join(
                'index_fulltext',
                'index_phash',
                'IP',
                $queryBuilder->expr()->eq('index_fulltext.phash', $queryBuilder->quoteIdentifier('IP.phash'))
            )
            ->join(
                'IP',
                'index_section',
                'ISEC',
                $queryBuilder->expr()->eq('IP.phash', $queryBuilder->quoteIdentifier('ISEC.phash'))
            );

        // Calling hook for alternative creation of page ID list
        $searchRootPageIdList = $this->getSearchRootPageIdList();
        if ($hookObj = $this->hookRequest('execFinalQuery_idList')) {
            $pageWhere = $hookObj->execFinalQuery_idList('');
            $queryBuilder->andWhere(QueryHelper::stripLogicalOperatorPrefix($pageWhere));
        } elseif ($this->joinPagesForQuery) {
            // Alternative to getting all page ids by ->getTreeList() where "excludeSubpages" is NOT respected.
            $queryBuilder
                ->join(
                    'ISEC',
                    'pages',
                    'pages',
                    $queryBuilder->expr()->eq('ISEC.page_id', $queryBuilder->quoteIdentifier('pages.uid'))
                )
                ->andWhere(
                    $queryBuilder->expr()->eq(
                        'pages.no_search',
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    )
                )
                ->andWhere(
                    $queryBuilder->expr()->lt(
                        'pages.doktype',
                        $queryBuilder->createNamedParameter(200, \PDO::PARAM_INT)
                    )
                );
            $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
        } elseif ($searchRootPageIdList[0] >= 0) {
            // Collecting all pages IDs in which to search;
            // filtering out ALL pages that are not accessible due to restriction containers. Does NOT look for "no_search" field!
            $idList = [];
            foreach ($searchRootPageIdList as $rootId) {
                $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
                $idList[] = $cObj->getTreeList(-1 * $rootId, 9999);
            }
            $idList = GeneralUtility::intExplode(',', implode(',', $idList));
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in(
                    'ISEC.page_id',
                    $queryBuilder->createNamedParameter($idList, Connection::PARAM_INT_ARRAY)
                )
            );
        }

        $searchBoolean = '';
        if ($searchData['searchBoolean']) {
            $searchBoolean = ' IN BOOLEAN MODE';
        }
        $queryBuilder->andWhere(
            'MATCH (' . $queryBuilder->quoteIdentifier($searchData['fulltextIndex']) . ')'
            . ' AGAINST (' . $queryBuilder->createNamedParameter($searchData['searchString'])
            . $searchBoolean
            . ')'
        );

        $queryBuilder->andWhere(
            QueryHelper::stripLogicalOperatorPrefix($this->mediaTypeWhere()),
            QueryHelper::stripLogicalOperatorPrefix($this->languageWhere()),
            QueryHelper::stripLogicalOperatorPrefix($this->freeIndexUidWhere($freeIndexUid)),
            QueryHelper::stripLogicalOperatorPrefix($this->sectionTableWhere())
        );

        $queryBuilder->groupBy(
            'IP.phash',
            'ISEC.phash',
            'ISEC.phash_t3',
            'ISEC.rl0',
            'ISEC.rl1',
            'ISEC.rl2',
            'ISEC.page_id',
            'ISEC.uniqid',
            'IP.phash_grouping',
            'IP.data_filename',
            'IP.data_page_id',
            'IP.data_page_type',
            'IP.data_page_mp',
            'IP.gr_list',
            'IP.item_type',
            'IP.item_title',
            'IP.item_description',
            'IP.item_mtime',
            'IP.tstamp',
            'IP.item_size',
            'IP.contentHash',
            'IP.crdate',
            'IP.parsetime',
            'IP.sys_language_uid',
            'IP.item_crdate',
            'IP.externalUrl',
            'IP.recordUid',
            'IP.freeIndexUid',
            'IP.freeIndexSetId'
        );

        return $queryBuilder->executeQuery();
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
        foreach ($searchWords as $v) {
            // Making the query for a single search word based on the search-type
            $sWord = $v['sword'] ?? '';
            $theType = $this->searchType;
            // If there are spaces in the search-word, make a full text search instead.
            if (str_contains($sWord, ' ')) {
                $theType = 20;
            }
            $this->getTimeTracker()->push('SearchWord "' . $sWord . '" - $theType=' . (string)$theType);
            // Perform search for word:
            switch ($theType) {
                case 1:
                    // Part of word
                    $res = $this->searchWord($sWord, LikeWildcard::BOTH);
                    break;
                case 2:
                    // First part of word
                    $res = $this->searchWord($sWord, LikeWildcard::RIGHT);
                    break;
                case 3:
                    // Last part of word
                    $res = $this->searchWord($sWord, LikeWildcard::LEFT);
                    break;
                case 10:
                    // Sounds like
                    $indexerObj = GeneralUtility::makeInstance(Indexer::class);
                    // Perform metaphone search
                    $storeMetaphoneInfoAsWords = !$this->isTableUsed('index_words');
                    $res = $this->searchMetaphone($indexerObj->metaphone($sWord, $storeMetaphoneInfoAsWords));
                    break;
                case 20:
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
                while ($row = $res->fetchAssociative()) {
                    $phashList[] = $row['phash'];
                }
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
     * @return \Doctrine\DBAL\Result|int
     */
    protected function execPHashListQuery($wordSel, $additionalWhereClause = '')
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('index_words');
        $queryBuilder->select('IR.phash')
            ->from('index_words', 'IW')
            ->from('index_rel', 'IR')
            ->from('index_section', 'ISEC')
            ->where(
                QueryHelper::stripLogicalOperatorPrefix($wordSel),
                $queryBuilder->expr()->eq('IW.wid', $queryBuilder->quoteIdentifier('IR.wid')),
                $queryBuilder->expr()->eq('ISEC.phash', $queryBuilder->quoteIdentifier('IR.phash')),
                QueryHelper::stripLogicalOperatorPrefix($this->sectionTableWhere()),
                QueryHelper::stripLogicalOperatorPrefix($additionalWhereClause)
            )
            ->groupBy('IR.phash');

        return $queryBuilder->executeQuery();
    }

    /**
     * Search for a word
     *
     * @param string $sWord the search word
     * @param int $wildcard Bit-field of Utility\LikeWildcard
     * @return \Doctrine\DBAL\Result
     */
    protected function searchWord($sWord, $wildcard)
    {
        $likeWildcard = LikeWildcard::cast($wildcard);
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
     * @return \Doctrine\DBAL\Result
     */
    protected function searchDistinct($sWord)
    {
        $expressionBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('index_words')
            ->expr();
        $wSel = $expressionBuilder->eq('IW.wid', $this->md5inthash($sWord));
        $this->wSelClauses[] = $wSel;
        return $this->execPHashListQuery($wSel, $expressionBuilder->eq('is_stopword', 0));
    }

    /**
     * Search for a sentence
     *
     * @param string $sWord the search word
     * @return \Doctrine\DBAL\Result
     */
    protected function searchSentence($sWord)
    {
        $this->wSelClauses[] = '1=1';
        $likeWildcard = LikeWildcard::cast(LikeWildcard::BOTH);
        $likePart = $likeWildcard->getLikeQueryPart(
            'index_fulltext',
            'IFT.fulltextdata',
            $sWord
        );

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('index_section');
        return $queryBuilder->select('ISEC.phash')
            ->from('index_section', 'ISEC')
            ->from('index_fulltext', 'IFT')
            ->where(
                QueryHelper::stripLogicalOperatorPrefix($likePart),
                $queryBuilder->expr()->eq('ISEC.phash', $queryBuilder->quoteIdentifier('IFT.phash')),
                QueryHelper::stripLogicalOperatorPrefix($this->sectionTableWhere())
            )
            ->groupBy('ISEC.phash')
            ->executeQuery();
    }

    /**
     * Search for a metaphone word
     *
     * @param string $sWord the search word
     * @return \Doctrine\DBAL\Result
     */
    protected function searchMetaphone($sWord)
    {
        $expressionBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('index_words')
            ->expr();
        $wSel = $expressionBuilder->eq('IW.metaphone', $expressionBuilder->literal($sWord));
        $this->wSelClauses[] = $wSel;
        return $this->execPHashListQuery($wSel, $expressionBuilder->eq('is_stopword', 0));
    }

    /**
     * Returns AND statement for selection of section in database. (rootlevel 0-2 + page_id)
     *
     * @return string AND clause for selection of section in database.
     */
    public function sectionTableWhere()
    {
        $expressionBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('index_section')
            ->expr();

        $whereClause = $expressionBuilder->andX();
        $match = false;
        if (!($this->searchRootPageIdList < 0)) {
            $whereClause->add(
                $expressionBuilder->in('ISEC.rl0', GeneralUtility::intExplode(',', $this->searchRootPageIdList, true))
            );
        }
        if (strpos($this->sections, 'rl1_') === 0) {
            $whereClause->add(
                $expressionBuilder->in('ISEC.rl1', GeneralUtility::intExplode(',', substr($this->sections, 4)))
            );
            $match = true;
        } elseif (strpos($this->sections, 'rl2_') === 0) {
            $whereClause->add(
                $expressionBuilder->in('ISEC.rl2', GeneralUtility::intExplode(',', substr($this->sections, 4)))
            );
            $match = true;
        } else {
            // Traversing user configured fields to see if any of those are used to limit search to a section:
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['addRootLineFields'] ?? [] as $fieldName => $rootLineLevel) {
                if (strpos($this->sections, $fieldName . '_') === 0) {
                    $whereClause->add(
                        $expressionBuilder->in(
                            'ISEC.' . $fieldName,
                            GeneralUtility::intExplode(',', substr($this->sections, strlen($fieldName) + 1))
                        )
                    );
                    $match = true;
                    break;
                }
            }
        }
        // If no match above, test the static types:
        if (!$match) {
            switch ((string)$this->sections) {
                case '-1':
                    $whereClause->add(
                        $expressionBuilder->eq('ISEC.page_id', (int)$this->getTypoScriptFrontendController()->id)
                    );
                    break;
                case '-2':
                    $whereClause->add($expressionBuilder->eq('ISEC.rl2', 0));
                    break;
                case '-3':
                    $whereClause->add($expressionBuilder->gt('ISEC.rl2', 0));
                    break;
            }
        }

        return $whereClause->count() ? ' AND ' . $whereClause : '';
    }

    /**
     * Returns AND statement for selection of media type
     *
     * @return string AND statement for selection of media type
     */
    public function mediaTypeWhere()
    {
        $expressionBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('index_phash')
            ->expr();
        switch ($this->mediaType) {
            case '0':
                // '0' => 'only TYPO3 pages',
                $whereClause = $expressionBuilder->eq('IP.item_type', $expressionBuilder->literal('0'));
                break;
            case '-2':
                // All external documents
                $whereClause = $expressionBuilder->neq('IP.item_type', $expressionBuilder->literal('0'));
                break;
            case false:
                // Intentional fall-through
            case '-1':
                // All content
                $whereClause = '';
                break;
            default:
                $whereClause = $expressionBuilder->eq('IP.item_type', $expressionBuilder->literal($this->mediaType));
        }
        return $whereClause ? ' AND ' . $whereClause : '';
    }

    /**
     * Returns AND statement for selection of language
     *
     * @return string AND statement for selection of language
     */
    public function languageWhere()
    {
        // -1 is the same as ALL language.
        if ($this->languageUid < 0) {
            return '';
        }

        $expressionBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('index_phash')
            ->expr();

        return ' AND ' . $expressionBuilder->eq('IP.sys_language_uid', $this->languageUid);
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
        if ($freeIndexUid < 0) {
            return '';
        }
        // First, look if the freeIndexUid is a meta configuration:
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('index_config');
        $indexCfgRec = $queryBuilder->select('indexcfgs')
            ->from('index_config')
            ->where(
                $queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter(5, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($freeIndexUid, \PDO::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchAssociative();

        if (is_array($indexCfgRec)) {
            $refs = GeneralUtility::trimExplode(',', $indexCfgRec['indexcfgs']);
            // Default value to protect against empty array.
            $list = [-99];
            foreach ($refs as $ref) {
                [$table, $uid] = GeneralUtility::revExplode('_', $ref, 2);
                $uid = (int)$uid;
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable('index_config');
                $queryBuilder->select('uid')
                    ->from('index_config');
                switch ($table) {
                    case 'index_config':
                        $idxRec = $queryBuilder
                            ->where(
                                $queryBuilder->expr()->eq(
                                    'uid',
                                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                                )
                            )
                            ->executeQuery()
                            ->fetchAssociative();
                        if ($idxRec) {
                            $list[] = $uid;
                        }
                        break;
                    case 'pages':
                        $indexCfgRecordsFromPid = $queryBuilder
                            ->where(
                                $queryBuilder->expr()->eq(
                                    'pid',
                                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                                )
                            )
                            ->executeQuery();
                        while ($idxRec = $indexCfgRecordsFromPid->fetchAssociative()) {
                            $list[] = $idxRec['uid'];
                        }
                        break;
                }
            }
            $list = array_unique($list);
        } else {
            $list = [$freeIndexUid];
        }

        $expressionBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('index_phash')
            ->expr();
        return ' AND ' . $expressionBuilder->in('IP.freeIndexUid', array_map('intval', $list));
    }

    /**
     * Execute final query, based on phash integer list. The main point is sorting the result in the right order.
     *
     * @param string $list List of phash integers which match the search.
     * @param int $freeIndexUid Pointer to which indexing configuration you want to search in. -1 means no filtering. 0 means only regular indexed content.
     * @return \Doctrine\DBAL\Result
     */
    protected function execFinalQuery($list, $freeIndexUid = -1)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('index_words');
        $queryBuilder->select('ISEC.*', 'IP.*')
            ->from('index_phash', 'IP')
            ->from('index_section', 'ISEC')
            ->where(
                $queryBuilder->expr()->in(
                    'IP.phash',
                    $queryBuilder->createNamedParameter(
                        GeneralUtility::intExplode(',', $list, true),
                        Connection::PARAM_INT_ARRAY
                    )
                ),
                QueryHelper::stripLogicalOperatorPrefix($this->mediaTypeWhere()),
                QueryHelper::stripLogicalOperatorPrefix($this->languageWhere()),
                QueryHelper::stripLogicalOperatorPrefix($this->freeIndexUidWhere($freeIndexUid)),
                $queryBuilder->expr()->eq('ISEC.phash', $queryBuilder->quoteIdentifier('IP.phash'))
            )
            ->groupBy(
                'IP.phash',
                'ISEC.phash',
                'ISEC.phash_t3',
                'ISEC.rl0',
                'ISEC.rl1',
                'ISEC.rl2',
                'ISEC.page_id',
                'ISEC.uniqid',
                'IP.phash_grouping',
                'IP.data_filename',
                'IP.data_page_id',
                'IP.data_page_type',
                'IP.data_page_mp',
                'IP.gr_list',
                'IP.item_type',
                'IP.item_title',
                'IP.item_description',
                'IP.item_mtime',
                'IP.tstamp',
                'IP.item_size',
                'IP.contentHash',
                'IP.crdate',
                'IP.parsetime',
                'IP.sys_language_uid',
                'IP.item_crdate',
                'IP.externalUrl',
                'IP.recordUid',
                'IP.freeIndexUid',
                'IP.freeIndexSetId',
                'IP.static_page_arguments'
            );

        // Setting up methods of filtering results
        // based on page types, access, etc.
        if ($hookObj = $this->hookRequest('execFinalQuery_idList')) {
            // Calling hook for alternative creation of page ID list
            $hookWhere = QueryHelper::stripLogicalOperatorPrefix($hookObj->execFinalQuery_idList($list));
            if (!empty($hookWhere)) {
                $queryBuilder->andWhere($hookWhere);
            }
        } elseif ($this->joinPagesForQuery) {
            // Alternative to getting all page ids by ->getTreeList() where
            // "excludeSubpages" is NOT respected.
            $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
            $queryBuilder->from('pages');
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq('pages.uid', $queryBuilder->quoteIdentifier('ISEC.page_id')),
                $queryBuilder->expr()->eq(
                    'pages.no_search',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->lt(
                    'pages.doktype',
                    $queryBuilder->createNamedParameter(200, \PDO::PARAM_INT)
                )
            );
        } elseif ($this->searchRootPageIdList >= 0) {
            // Collecting all pages IDs in which to search;
            // filtering out ALL pages that are not accessible due to restriction containers.
            // Does NOT look for "no_search" field!
            $siteIdNumbers = GeneralUtility::intExplode(',', $this->searchRootPageIdList);
            $pageIdList = [];
            foreach ($siteIdNumbers as $rootId) {
                $pageIdList[] = $this->getTypoScriptFrontendController()->cObj->getTreeList(-1 * $rootId, 9999);
            }
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in(
                    'ISEC.page_id',
                    $queryBuilder->createNamedParameter(
                        array_unique(GeneralUtility::intExplode(',', implode(',', $pageIdList), true)),
                        Connection::PARAM_INT_ARRAY
                    )
                )
            );
        }
        // otherwise select all / disable everything
        // If any of the ranking sortings are selected, we must make a
        // join with the word/rel-table again, because we need to
        // calculate ranking based on all search-words found.
        if (strpos($this->sortOrder, 'rank_') === 0) {
            $queryBuilder
                ->from('index_words', 'IW')
                ->from('index_rel', 'IR')
                ->andWhere(
                    $queryBuilder->expr()->eq('IW.wid', $queryBuilder->quoteIdentifier('IR.wid')),
                    $queryBuilder->expr()->eq('ISEC.phash', $queryBuilder->quoteIdentifier('IR.phash'))
                );
            switch ($this->sortOrder) {
                case 'rank_flag':
                    // This gives priority to word-position (max-value) so that words in title, keywords, description counts more than in content.
                    // The ordering is refined with the frequency sum as well.
                    $queryBuilder
                        ->addSelectLiteral(
                            $queryBuilder->expr()->max('IR.flags', 'order_val1'),
                            $queryBuilder->expr()->sum('IR.freq', 'order_val2')
                        )
                        ->orderBy('order_val1', $this->getDescendingSortOrderFlag())
                        ->addOrderBy('order_val2', $this->getDescendingSortOrderFlag());
                    break;
                case 'rank_first':
                    // Results in average position of search words on page.
                    // Must be inversely sorted (low numbers are closer to top)
                    $queryBuilder
                        ->addSelectLiteral($queryBuilder->expr()->avg('IR.first', 'order_val'))
                        ->orderBy('order_val', $this->getDescendingSortOrderFlag(true));
                    break;
                case 'rank_count':
                    // Number of words found
                    $queryBuilder
                        ->addSelectLiteral($queryBuilder->expr()->sum('IR.count', 'order_val'))
                        ->orderBy('order_val', $this->getDescendingSortOrderFlag());
                    break;
                default:
                    // Frequency sum. I'm not sure if this is the best way to do
                    // it (make a sum...). Or should it be the average?
                    $queryBuilder
                        ->addSelectLiteral($queryBuilder->expr()->sum('IR.freq', 'order_val'))
                        ->orderBy('order_val', $this->getDescendingSortOrderFlag());
            }

            if (!empty($this->wSelClauses)) {
                // So, words are combined in an OR statement
                // (no "sentence search" should be done here - may deselect results)
                $wordSel = $queryBuilder->expr()->orX();
                foreach ($this->wSelClauses as $wSelClause) {
                    $wordSel->add(QueryHelper::stripLogicalOperatorPrefix($wSelClause));
                }
                $queryBuilder->andWhere($wordSel);
            }
        } else {
            // Otherwise, if sorting are done with the pages table or other fields,
            // there is no need for joining with the rel/word tables:
            switch ($this->sortOrder) {
                case 'title':
                    $queryBuilder->orderBy('IP.item_title', $this->getDescendingSortOrderFlag());
                    break;
                case 'crdate':
                    $queryBuilder->orderBy('IP.item_crdate', $this->getDescendingSortOrderFlag());
                    break;
                case 'mtime':
                    $queryBuilder->orderBy('IP.item_mtime', $this->getDescendingSortOrderFlag());
                    break;
            }
        }

        return $queryBuilder->executeQuery();
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
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('index_grlist');
        if ($row['item_type']) {
            // For external media we will check the access of the parent page on which the media was linked from.
            // "phash_t3" is the phash of the parent TYPO3 page row which initiated the indexing of the documents
            // in this section. So, selecting for the grlist records belonging to the parent phash-row where the
            // current users gr_list exists will help us to know. If this is NOT found, there is still a theoretical
            // possibility that another user accessible page would display a link, so maybe the resume of such a
            // document here may be unjustified hidden. But better safe than sorry.
            if (!$this->isTableUsed('index_grlist')) {
                return false;
            }

            return (bool)$connection->count(
                'phash',
                'index_grlist',
                [
                    'phash' => (int)$row['phash_t3'],
                    'gr_list' => $this->frontendUserGroupList,
                ]
            );
        }
        // Ordinary TYPO3 pages:
        if ((string)$row['gr_list'] !== (string)$this->frontendUserGroupList) {
            // Selecting for the grlist records belonging to the phash-row where the current users gr_list exists.
            // If it is found it is proof that this user has direct access to the phash-rows content although
            // he did not himself initiate the indexing...
            if (!$this->isTableUsed('index_grlist')) {
                return false;
            }

            return (bool)$connection->count(
                'phash',
                'index_grlist',
                [
                        'phash' => (int)$row['phash'],
                        'gr_list' => $this->frontendUserGroupList,
                    ]
            );
        }
        return true;
    }

    /**
     * Returns "DESC" or "" depending on the settings of the incoming
     * highest/lowest result order (piVars['desc'])
     *
     * @param bool $inverse If TRUE, inverse the order which is defined by piVars['desc']
     * @return string " DESC" or formerly known as tx_indexedsearch_pi->isDescending
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
     * Returns if an item type is a multipage item type
     *
     * @param string $itemType Item type
     * @return bool TRUE if multipage capable
     */
    protected function multiplePagesType($itemType)
    {
        /** @var FileContentParser|null $fileContentParser */
        $fileContentParser = $this->externalParsers[$itemType] ?? null;
        return is_object($fileContentParser) && $fileContentParser->isMultiplePageExtension($itemType);
    }

    /**
     * md5 integer hash
     * Using 7 instead of 8 just because that makes the integers lower than
     * 32 bit (28 bit) and so they do not interfere with UNSIGNED integers
     * or PHP-versions which has varying output from the hexdec function.
     *
     * @param string $str String to hash
     * @return int Integer interpretation of the md5 hash of input string.
     */
    protected function md5inthash($str)
    {
        return IndexedSearchUtility::md5inthash($str);
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
        return IndexedSearchUtility::isTableUsed($table_list);
    }

    /**
     * Returns an object reference to the hook object if any
     *
     * @param string $functionName Name of the function you want to call / hook key
     * @return object|null Hook object, if any. Otherwise NULL.
     */
    public function hookRequest($functionName)
    {
        // Hook: menuConfig_preProcessModMenu
        if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['pi1_hooks'][$functionName] ?? false) {
            $hookObj = GeneralUtility::makeInstance($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['pi1_hooks'][$functionName]);
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
    public function getSearchType(): int
    {
        return $this->searchType;
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
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * @return TimeTracker
     */
    protected function getTimeTracker()
    {
        return GeneralUtility::makeInstance(TimeTracker::class);
    }
}
