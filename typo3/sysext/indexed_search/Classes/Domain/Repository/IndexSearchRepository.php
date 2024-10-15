<?php

declare(strict_types=1);

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

use Doctrine\DBAL\Platforms\MariaDBPlatform as DoctrineMariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform as DoctrineMySQLPlatform;
use Doctrine\DBAL\Result;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\IndexedSearch\Event\BeforeFinalSearchQueryIsExecutedEvent;
use TYPO3\CMS\IndexedSearch\FileContentParser;
use TYPO3\CMS\IndexedSearch\Type\MediaType;
use TYPO3\CMS\IndexedSearch\Type\SearchType;
use TYPO3\CMS\IndexedSearch\Type\SectionType;
use TYPO3\CMS\IndexedSearch\Utility\LikeWildcard;

/**
 * Index search abstraction to search through the index
 * @internal This class is a specific repository implementation and is not considered part of the Public TYPO3 API.
 */
class IndexSearchRepository
{
    /**
     * External Parsers
     */
    protected array $externalParsers = [];

    /**
     * Frontend User Group List
     */
    protected string $frontendUserGroupList = '';

    /**
     * Sections
     * formally known as $this->piVars['sections']
     */
    protected string $sections = '';

    /**
     * Search type
     * formally known as $this->piVars['type']
     */
    protected SearchType $searchType = SearchType::DISTINCT;

    /**
     * Language uid
     * formally known as $this->piVars['lang']
     */
    protected int $languageUid = 0;

    /**
     * Media type
     * Can be either an ENUM backed value or a raw string
     * formally known as $this->piVars['media']
     */
    protected MediaType|string $mediaType = MediaType::INTERNAL_PAGES;

    /**
     * Sort order
     * formally known as $this->piVars['sort_order']
     */
    protected string $sortOrder = '';

    /**
     * Descending sort order flag
     * formally known as $this->piVars['desc']
     */
    protected bool $descendingSortOrderFlag = false;

    /**
     * Result page pointer
     * formally known as $this->piVars['pointer']
     */
    protected int $resultpagePointer = 0;

    /**
     * Number of results
     * formally known as $this->piVars['result']
     */
    protected int $numberOfResults = 10;

    /**
     * list of all root pages that will be used
     * If this value is set to less than zero (eg. -1) searching will happen
     * in ALL of the page tree with no regard to branches at all.
     */
    protected string $searchRootPageIdList = '';

    /**
     * Select clauses for individual words, will be filled during the search
     */
    protected array $wSelClauses = [];

    /**
     * Flag for exact search count
     * formally known as $conf['search.']['exactCount']
     *
     * Continue counting and checking of results even if we are sure
     * they are not displayed in this request. This will slow down your
     * page rendering, but it allows precise search result counters.
     * enabled through settings.exactCount
     */
    protected bool $useExactCount = false;

    /**
     * Display forbidden records
     * formally known as $this->conf['show.']['forbiddenRecords']
     *
     * enabled through settings.displayForbiddenRecords
     */
    protected bool $displayForbiddenRecords = false;

    public function __construct(
        private readonly Context $context,
        private readonly ExtensionConfiguration $extensionConfiguration,
        private readonly TimeTracker $timeTracker,
        private readonly ConnectionPool $connectionPool,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * initialize all options that are necessary for the search
     *
     * @param array $settings the extbase plugin settings
     * @param array $searchData the search data
     */
    public function initialize(array $settings, array $searchData, array $externalParsers, int|string $searchRootPageIdList): void
    {
        $this->externalParsers = $externalParsers;
        $this->searchRootPageIdList = (string)$searchRootPageIdList;
        $this->frontendUserGroupList = implode(',', $this->context->getPropertyFromAspect('frontend.user', 'groupIds', [0, -1]));
        if ($settings['exactCount'] ?? false) {
            $this->useExactCount = true;
        }
        if ($settings['displayForbiddenRecords'] ?? false) {
            $this->displayForbiddenRecords = true;
        }
        $this->sections = (string)($searchData['sections'] ?? '');
        $this->searchType = SearchType::tryFrom((int)($searchData['searchType'] ?? 0)) ?? SearchType::DISTINCT;
        $this->languageUid = (int)($searchData['languageUid'] ?? 0);

        // 'mediaType' can either be an INT in range (-1|-2|0), but also be a file extension string ('ppt').
        // Only when it's an integer, it can be mapped to the ENUM. Otherwise, the input 'mediaType' needs to be mapped here.
        if (isset($searchData['mediaType'])) {
            if (MathUtility::canBeInterpretedAsInteger($searchData['mediaType'])) {
                $this->mediaType = MediaType::tryFrom((int)$searchData['mediaType']) ?? MediaType::INTERNAL_PAGES;
            } elseif (is_string($searchData['mediaType']) && $searchData['mediaType'] !== '') {
                $this->mediaType = $searchData['mediaType'];
            }
        }

        $this->sortOrder = (string)($searchData['sortOrder'] ?? '');
        $this->descendingSortOrderFlag = (bool)($searchData['desc'] ?? false);
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
     * @return array|false FALSE if no result, otherwise an array with keys for first row, result rows and total number of results found.
     */
    public function doSearch(array $searchWords, int $freeIndexUid): array|false
    {
        $result = null;
        $useMysqlFulltext = (bool)$this->extensionConfiguration->get('indexed_search', 'useMysqlFulltext');
        $this->timeTracker->push('Searching result');
        if ($useMysqlFulltext) {
            $queryBuilder = $this->getPreparedQueryBuilder_SQLpointerMysqlFulltext($searchWords, $freeIndexUid);
        } else {
            $queryBuilder = $this->getPreparedQueryBuilder_SQLpointer($searchWords, $freeIndexUid);
        }
        if ($queryBuilder !== false) {
            $this->eventDispatcher->dispatch(
                new BeforeFinalSearchQueryIsExecutedEvent($queryBuilder, $searchWords, $freeIndexUid)
            );
            // Getting SQL result pointer:
            $this->timeTracker->push('execFinalQuery');
            $result = $queryBuilder->executeQuery();
            $this->timeTracker->pull();
        }
        $this->timeTracker->pull();
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
                        if (!$this->multiplePagesType((string)($row['item_type'] ?? ''))) {
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
                            // However, the result counter will not filter out grouped cHashes/pHashes that were not processed yet.
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
     * Write statistics information to database for the search operation if there was at least one search word.
     *
     * @param array $searchWords Search Word array
     */
    public function writeSearchStat(int $pageId, array $searchWords): void
    {
        if (empty($searchWords)) {
            return;
        }
        $entries = [];
        foreach ($searchWords as $val) {
            $entries[] = [
                mb_substr($val['sword'], 0, 50),
                $GLOBALS['EXEC_TIME'],
                $pageId,
            ];
        }
        $this->connectionPool->getConnectionForTable('index_stat_word')
            ->bulkInsert(
                'index_stat_word',
                $entries,
                ['word', 'tstamp', 'pageid'],
                [Connection::PARAM_STR, Connection::PARAM_INT, Connection::PARAM_INT]
            );
    }

    public function getFullTextRowByPhash(string $phash): ?array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('index_fulltext');
        return $queryBuilder
            ->select('*')
            ->from('index_fulltext')
            ->where(
                $queryBuilder->expr()->eq(
                    'phash',
                    $queryBuilder->createNamedParameter($phash)
                )
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative() ?: null;
    }

    public function getIndexConfigurationById(int $id): ?array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('index_config');
        return $queryBuilder
            ->select('uid', 'title')
            ->from('index_config')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($id, Connection::PARAM_INT)
                )
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative() ?: null;
    }

    /**
     * Gets the QueryBuilder instance prepared for the phash list.
     *
     * @param array $searchWords Search words
     * @param int $freeIndexUid Pointer to which indexing configuration you want to search in. -1 means no filtering. 0 means only regular indexed content.
     */
    protected function getPreparedQueryBuilder_SQLpointer(array $searchWords, int $freeIndexUid): QueryBuilder|false
    {
        // This SEARCHES for the searchwords in $searchWords AND returns a
        // COMPLETE list of phash-integers of the matches.
        $list = $this->getPhashList($searchWords);
        if ($list) {
            // Create the search:
            return $this->prepareFinalQuery($list, $freeIndexUid);
        }
        return false;
    }

    /**
     * Gets the QueryBuilder instance prepared for the search words.
     *
     * mysql fulltext specific version triggered by ext_conf_template setting 'useMysqlFulltext'
     *
     * @param array $searchWordsArray Search words
     * @param int $freeIndexUid Pointer to which indexing configuration you want to search in. -1 means no filtering. 0 means only regular indexed content.
     */
    protected function getPreparedQueryBuilder_SQLpointerMysqlFulltext(array $searchWordsArray, int $freeIndexUid): QueryBuilder|false
    {
        $connection = $this->connectionPool->getConnectionForTable('index_fulltext');
        $platform = $connection->getDatabasePlatform();
        if (!($platform instanceof DoctrineMariaDBPlatform || $platform instanceof DoctrineMySQLPlatform)) {
            throw new \RuntimeException(
                'Extension indexed_search is configured to use mysql fulltext, but table \'index_fulltext\''
                . ' is running on a different DBMS.',
                1472585525
            );
        }
        // Build the search string, detect which fulltext index to use, and decide whether boolean search is needed or not
        $searchData = $this->getSearchString($searchWordsArray);
        if ($searchData) {
            // Create the search:
            return $this->prepareFinalQuery_fulltext($searchData, $freeIndexUid);
        }
        return false;
    }

    /**
     * Returns a search string for use with MySQL FULLTEXT query
     *
     * mysql fulltext specific helper method
     *
     * @param array $searchWordArray Search word array
     * @return array Search string
     */
    protected function getSearchString(array $searchWordArray): array
    {
        // Change this to TRUE to force BOOLEAN SEARCH MODE (useful if fulltext index is still empty)
        $searchBoolean = false;
        // This holds the result if the search is natural (doesn't contain any boolean operators)
        $naturalSearchString = '';
        // This holds the result if the search is boolean (contains +/-/| operators)
        $booleanSearchString = '';
        $searchType = $this->searchType;

        // Traverse searchwords and prefix them with corresponding operator
        foreach ($searchWordArray as $searchWordData) {
            // Making the query for a single search word based on the search-type
            $searchWord = $searchWordData['sword'];
            $wildcard = '';
            if (str_contains($searchWord, ' ')) {
                $searchType = SearchType::SENTENCE;
            }
            switch ($searchType) {
                case SearchType::DISTINCT:
                    // Intended fall-thru
                    break;
                case SearchType::PART_OF_WORD:
                case SearchType::FIRST_PART_OF_WORD:
                case SearchType::LAST_PART_OF_WORD:
                    // First part of word
                    $wildcard = '*';
                    // Part-of-word search requires boolean mode!
                    $searchBoolean = true;
                    break;
                case SearchType::SENTENCE:
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
        if ($searchType === SearchType::SENTENCE) {
            $searchString = '"' . trim($naturalSearchString) . '"';
        } elseif ($searchBoolean) {
            $searchString = trim($booleanSearchString);
        } else {
            $searchString = trim($naturalSearchString);
        }
        return [
            'searchBoolean' => $searchBoolean,
            'searchString' => $searchString,
            'fulltextIndex' => 'index_fulltext.fulltextdata',
        ];
    }

    /**
     * Execute final query, based on search data. The main point is sorting the result in the right order.
     *
     * mysql fulltext specific helper method
     *
     * @param array $searchData Array with search string, boolean indicator, and fulltext index reference
     * @param int $freeIndexUid Pointer to which indexing configuration you want to search in. -1 means no filtering. 0 means only regular indexed content.
     */
    protected function prepareFinalQuery_fulltext(array $searchData, int $freeIndexUid): QueryBuilder
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('index_fulltext');
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

        $searchRootPageIdList = $this->getSearchRootPageIdList();
        if ($searchRootPageIdList[0] >= 0) {
            // Collecting all pages IDs in which to search
            // filtering out ALL pages that are not accessible due to restriction containers. Does NOT look for "no_search" field!
            $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
            $idList = $pageRepository->getPageIdsRecursive($searchRootPageIdList, 9999);
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in(
                    'ISEC.page_id',
                    $queryBuilder->quoteArrayBasedValueListToIntegerList($idList)
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

        return $queryBuilder;
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
    protected function getPhashList(array $searchWords): string
    {
        // Initialize variables:
        $c = 0;
        // This array accumulates the phash-values
        $totalHashList = [];
        $this->wSelClauses = [];
        // Traverse searchWords; for each, select all phash integers and merge/diff/intersect them with previous word (based on operator)
        foreach ($searchWords as $v) {
            // Making the query for a single search word based on the search-type
            $sWord = (string)($v['sword'] ?? '');
            $theType = $this->searchType;
            // If there are spaces in the search-word, make a full text search instead.
            if (str_contains($sWord, ' ')) {
                $theType = SearchType::SENTENCE;
            }
            $this->timeTracker->push('SearchWord "' . $sWord . '" - $theType=' . $theType->value);
            // Perform search for word:
            switch ($theType) {
                case SearchType::PART_OF_WORD:
                    $res = $this->searchWord($sWord, LikeWildcard::BOTH);
                    break;
                case SearchType::FIRST_PART_OF_WORD:
                    $res = $this->searchWord($sWord, LikeWildcard::RIGHT);
                    break;
                case SearchType::LAST_PART_OF_WORD:
                    $res = $this->searchWord($sWord, LikeWildcard::LEFT);
                    break;
                case SearchType::SENTENCE:
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
            // Get phash list by searching for it:
            $phashList = [];
            while ($row = $res->fetchAssociative()) {
                $phashList[] = $row['phash'];
            }
            // Here the phash list are merged with the existing result based on whether we are dealing with OR, NOT or AND operations.
            if ($c) {
                $totalHashList = match ($v['oper']) {
                    'OR' => array_unique(array_merge($phashList, $totalHashList)),
                    'AND NOT' => array_diff($totalHashList, $phashList),
                    default => array_intersect($totalHashList, $phashList),
                };
            } else {
                // First search
                $totalHashList = $phashList;
            }
            $this->timeTracker->pull();
            $c++;
        }
        return implode(',', $totalHashList);
    }

    /**
     * Returns a query which selects the search-word from the word/rel tables.
     *
     * @param string $wordSel WHERE clause selecting the word from phash
     * @param string $additionalWhereClause Additional AND clause in the end of the query.
     */
    protected function execPHashListQuery(string $wordSel, string $additionalWhereClause): Result
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('index_words');
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
     */
    protected function searchWord(string $sWord, LikeWildcard $likeWildcard): Result
    {
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
     */
    protected function searchDistinct(string $sWord): Result
    {
        $expressionBuilder = $this->connectionPool->getQueryBuilderForTable('index_words')->expr();
        $wSel = $expressionBuilder->eq('IW.wid', $expressionBuilder->literal(md5($sWord)));
        $this->wSelClauses[] = $wSel;
        return $this->execPHashListQuery($wSel, $expressionBuilder->eq('is_stopword', 0));
    }

    /**
     * Search for a sentence
     *
     * @param string $sWord the search word
     */
    protected function searchSentence(string $sWord): Result
    {
        $this->wSelClauses[] = '1=1';
        $likeWildcard = LikeWildcard::BOTH;
        $likePart = $likeWildcard->getLikeQueryPart(
            'index_fulltext',
            'IFT.fulltextdata',
            $sWord
        );

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('index_section');
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
     * Returns AND statement for selection of section in database. (rootlevel 0-2 + page_id)
     *
     * @return string AND clause for selection of section in database.
     */
    protected function sectionTableWhere(): string
    {
        $expressionBuilder = $this->connectionPool->getQueryBuilderForTable('index_section')->expr();

        $whereClause = $expressionBuilder->and();
        $match = false;
        if (!($this->searchRootPageIdList < 0)) {
            $whereClause = $whereClause->with(
                $expressionBuilder->in('ISEC.rl0', GeneralUtility::intExplode(',', $this->searchRootPageIdList, true))
            );
        }
        if (str_starts_with($this->sections, 'rl1_')) {
            $whereClause = $whereClause->with(
                $expressionBuilder->in('ISEC.rl1', GeneralUtility::intExplode(',', substr($this->sections, 4)))
            );
            $match = true;
        } elseif (str_starts_with($this->sections, 'rl2_')) {
            $whereClause = $whereClause->with(
                $expressionBuilder->in('ISEC.rl2', GeneralUtility::intExplode(',', substr($this->sections, 4)))
            );
            $match = true;
        }
        // If no match above, test the static types:
        if (!$match) {
            switch ($this->sections) {
                case (string)SectionType::ONLY_THIS_PAGE->value:
                    // @todo: This repository either needs to retrieve the request or page uid.
                    $pageId = $GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.page.information')->getId();
                    $whereClause = $whereClause->with(
                        $expressionBuilder->eq('ISEC.page_id', $pageId)
                    );
                    break;
                case (string)SectionType::TOP_AND_CHILDREN->value:
                    $whereClause = $whereClause->with($expressionBuilder->eq('ISEC.rl2', 0));
                    break;
                case (string)SectionType::LEVEL_TWO_AND_OUT->value:
                    $whereClause = $whereClause->with($expressionBuilder->gt('ISEC.rl2', 0));
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
    protected function mediaTypeWhere(): string
    {
        $expressionBuilder = $this->connectionPool->getQueryBuilderForTable('index_phash')->expr();
        if ($this->mediaType instanceof MediaType) {
            $whereClause = match ($this->mediaType) {
                MediaType::ALL_EXTERNAL => $expressionBuilder->neq('IP.item_type', $expressionBuilder->literal((string)MediaType::INTERNAL_PAGES->value)),
                MediaType::ALL_MEDIA => '', // include TYPO3 pages and external media
                default => $expressionBuilder->eq('IP.item_type', $expressionBuilder->literal((string)$this->mediaType->value)),
            };
        } else {
            $whereClause = $expressionBuilder->eq('IP.item_type', $expressionBuilder->literal($this->mediaType));
        }
        return $whereClause ? ' AND ' . $whereClause : '';
    }

    /**
     * Returns AND statement for selection of language
     *
     * @return string AND statement for selection of language
     */
    protected function languageWhere(): string
    {
        // -1 is the same as ALL language.
        if ($this->languageUid < 0) {
            return '';
        }

        $expressionBuilder = $this->connectionPool->getQueryBuilderForTable('index_phash')->expr();

        return ' AND ' . $expressionBuilder->eq('IP.sys_language_uid', $this->languageUid);
    }

    /**
     * Where-clause for free index-uid value.
     *
     * @param int $freeIndexUid Free Index UID value to limit search to.
     * @return string WHERE SQL clause part.
     */
    protected function freeIndexUidWhere(int $freeIndexUid): string
    {
        if ($freeIndexUid < 0) {
            return '';
        }
        // First, look if the freeIndexUid is a meta configuration:
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('index_config');
        $indexCfgRec = $queryBuilder->select('indexcfgs')
            ->from('index_config')
            ->where(
                $queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter(5, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($freeIndexUid, Connection::PARAM_INT)
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
                $queryBuilder = $this->connectionPool->getQueryBuilderForTable('index_config');
                $queryBuilder->select('uid')->from('index_config');
                switch ($table) {
                    case 'index_config':
                        $idxRec = $queryBuilder
                            ->where(
                                $queryBuilder->expr()->eq(
                                    'uid',
                                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
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
                                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
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

        $expressionBuilder = $this->connectionPool->getQueryBuilderForTable('index_phash')->expr();
        return ' AND ' . $expressionBuilder->in('IP.freeIndexUid', array_map('intval', $list));
    }

    /**
     * Prepare final query, based on phash integer list. The main point is sorting the result in the right order.
     *
     * @param string $list List of phash integers which match the search.
     * @param int $freeIndexUid Pointer to which indexing configuration you want to search in. -1 means no filtering. 0 means only regular indexed content.
     */
    protected function prepareFinalQuery(string $list, int $freeIndexUid): QueryBuilder
    {
        $phashList = GeneralUtility::trimExplode(',', $list, true);
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('index_words');
        $queryBuilder->select('ISEC.*', 'IP.*')
            ->from('index_phash', 'IP')
            ->from('index_section', 'ISEC')
            ->where(
                $queryBuilder->expr()->in(
                    'IP.phash',
                    $queryBuilder->quoteArrayBasedValueListToStringList($phashList)
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
        if ($this->searchRootPageIdList >= 0) {
            // Collecting all pages IDs in which to search,
            // filtering out ALL pages that are not accessible due to restriction containers.
            // Does NOT look for "no_search" field!
            $siteIdNumbers = GeneralUtility::intExplode(',', $this->searchRootPageIdList);
            $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
            $pageIdList = $pageRepository->getPageIdsRecursive($siteIdNumbers, 9999);
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in(
                    'ISEC.page_id',
                    $queryBuilder->quoteArrayBasedValueListToIntegerList($pageIdList)
                )
            );
        }
        // otherwise select all / disable everything
        // If any of the ranking sortings are selected, we must make a
        // join with the word/rel-table again, because we need to
        // calculate ranking based on all search-words found.
        if (str_starts_with($this->sortOrder, 'rank_')) {
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
                $wordSel = $queryBuilder->expr()->or();
                foreach ($this->wSelClauses as $wSelClause) {
                    $wordSel = $wordSel->with(QueryHelper::stripLogicalOperatorPrefix($wSelClause));
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

        return $queryBuilder;
    }

    /**
     * Checking if the resume can be shown for the search result
     * (depending on whether the rights are OK)
     * ? Should it also check for gr_list "0,-1"?
     *
     * @param array $row Result row array.
     * @return bool Returns TRUE if resume can safely be shown
     */
    protected function checkResume(array $row): bool
    {
        // If the record is indexed by an indexing configuration, just show it.
        // At least this is needed for external URLs and files.
        // For records, we might need to extend this - for instance block display if record is access restricted.
        if ($row['freeIndexUid']) {
            return true;
        }
        // Evaluate regularly indexed pages based on item_type:
        // External media:
        $connection = $this->connectionPool->getConnectionForTable('index_grlist');
        if ($row['item_type']) {
            // For external media we will check the access of the parent page on which the media was linked from.
            // "phash_t3" is the phash of the parent TYPO3 page row which initiated the indexing of the documents
            // in this section. So, selecting for the grlist records belonging to the parent phash-row where the
            // current users gr_list exists will help us to know. If this is NOT found, there is still a theoretical
            // possibility that another user accessible page would display a link, so maybe the resume of such a
            // document here may be unjustified hidden. But better safe than sorry.
            return (bool)$connection->count(
                'phash',
                'index_grlist',
                [
                    'phash' => $row['phash_t3'],
                    'gr_list' => $this->frontendUserGroupList,
                ]
            );
        }
        // Ordinary TYPO3 pages:
        if ((string)$row['gr_list'] !== $this->frontendUserGroupList) {
            // Selecting for the grlist records belonging to the phash-row where the current users gr_list exists.
            // If it is found it is proof that this user has direct access to the phash-rows content although
            // he did not himself initiate the indexing...
            return (bool)$connection->count(
                'phash',
                'index_grlist',
                [
                    'phash' => $row['phash'],
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
    protected function getDescendingSortOrderFlag(bool $inverse = false): string
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
    protected function multiplePagesType(string $itemType): bool
    {
        /** @var FileContentParser|null $fileContentParser */
        $fileContentParser = $this->externalParsers[$itemType] ?? null;
        return is_object($fileContentParser) && $fileContentParser->isMultiplePageExtension($itemType);
    }

    /**
     * A list of integer which should be root-pages to search from
     *
     * @return int[]
     */
    protected function getSearchRootPageIdList(): array
    {
        return GeneralUtility::intExplode(',', $this->searchRootPageIdList);
    }
}
