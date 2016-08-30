<?php
namespace TYPO3\CMS\IndexedSearchMysql\Hook;

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

/**
 * Class that hooks into Indexed Search and replaces standard SQL queries with MySQL fulltext index queries.
 */
class MysqlFulltextIndexHook
{
    /**
     * @var \TYPO3\CMS\IndexedSearch\Controller\SearchFormController|\TYPO3\CMS\IndexedSearch\Domain\Repository\IndexSearchRepository
     */
    public $pObj;

    const ANY_PART_OF_THE_WORD = '1';
    const LAST_PART_OF_THE_WORD = '2';
    const FIRST_PART_OF_THE_WORD = '3';
    const SOUNDS_LIKE = '10';
    const SENTENCE = '20';
    /**
     * Gets a SQL result pointer to traverse for the search records.
     *
     * @param array $searchWordsArray Search words
     * @param int $freeIndexUid Pointer to which indexing configuration you want to search in. -1 means no filtering. 0 means only regular indexed content.
     * @return bool|\mysqli_result|object MySQLi result object / DBAL object
     */
    public function getResultRows_SQLpointer($searchWordsArray, $freeIndexUid = -1)
    {
        // Build the search string, detect which fulltext index to use, and decide whether boolean search is needed or not
        $searchData = $this->getSearchString($searchWordsArray);
        // Perform SQL Search / collection of result rows array:
        $resource = false;
        if ($searchData) {
            // Do the search:
            $GLOBALS['TT']->push('execFinalQuery');
            $resource = $this->execFinalQuery_fulltext($searchData, $freeIndexUid);
            $GLOBALS['TT']->pull();
        }
        return $resource;
    }

    /**
     * Returns a search string for use with MySQL FULLTEXT query
     *
     * @param array $searchWordArray Search word array
     * @return string Search string
     */
    public function getSearchString($searchWordArray)
    {
        // Initialize variables:
        $count = 0;
        // Change this to TRUE to force BOOLEAN SEARCH MODE (useful if fulltext index is still empty)
        $searchBoolean = false;
        $fulltextIndex = 'index_fulltext.fulltextdata';
        // This holds the result if the search is natural (doesn't contain any boolean operators)
        $naturalSearchString = '';
        // This holds the result if the search is boolen (contains +/-/| operators)
        $booleanSearchString = '';

        $searchType = (string)$this->pObj->getSearchType();

        // Traverse searchwords and prefix them with corresponding operator
        foreach ($searchWordArray as $searchWordData) {
            // Making the query for a single search word based on the search-type
            $searchWord = $searchWordData['sword'];
            $wildcard = '';
            if (strstr($searchWord, ' ')) {
                $searchType = self::SENTENCE;
            }
            switch ($searchType) {
                case self::ANY_PART_OF_THE_WORD:

                case self::LAST_PART_OF_THE_WORD:

                case self::FIRST_PART_OF_THE_WORD:
                    // First part of word
                    $wildcard = '*';
                    // Part-of-word search requires boolean mode!
                    $searchBoolean = true;
                    break;
                case self::SOUNDS_LIKE:
                    $indexerObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\IndexedSearch\Indexer::class);
                    // Initialize the indexer-class
                    /** @var \TYPO3\CMS\IndexedSearch\Indexer $indexerObj */
                    $searchWord = $indexerObj->metaphone($searchWord, $indexerObj->storeMetaphoneInfoAsWords);
                    unset($indexerObj);
                    $fulltextIndex = 'index_fulltext.metaphonedata';
                    break;
                case self::SENTENCE:
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
            $count++;
        }
        if ($searchType == self::SENTENCE) {
            $searchString = '"' . trim($naturalSearchString) . '"';
        } elseif ($searchBoolean) {
            $searchString = trim($booleanSearchString);
        } else {
            $searchString = trim($naturalSearchString);
        }
        return [
            'searchBoolean' => $searchBoolean,
            'searchString' => $searchString,
            'fulltextIndex' => $fulltextIndex
        ];
    }

    /**
     * Execute final query, based on phash integer list. The main point is sorting the result in the right order.
     *
     * @param array $searchData Array with search string, boolean indicator, and fulltext index reference
     * @param int $freeIndexUid Pointer to which indexing configuration you want to search in. -1 means no filtering. 0 means only regular indexed content.
     * @return bool|\mysqli_result|object MySQLi result object / DBAL object
     */
    protected function execFinalQuery_fulltext($searchData, $freeIndexUid = -1)
    {
        // Setting up methods of filtering results based on page types, access, etc.
        $pageJoin = '';
        // Indexing configuration clause:
        $freeIndexUidClause = $this->pObj->freeIndexUidWhere($freeIndexUid);
        // Calling hook for alternative creation of page ID list
        $searchRootPageIdList = $this->pObj->getSearchRootPageIdList();
        if ($hookObj = &$this->pObj->hookRequest('execFinalQuery_idList')) {
            $pageWhere = $hookObj->execFinalQuery_idList('');
        } elseif ($this->pObj->getJoinPagesForQuery()) {
            // Alternative to getting all page ids by ->getTreeList() where "excludeSubpages" is NOT respected.
            $pageJoin = ',
				pages';
            $pageWhere = 'pages.uid = ISEC.page_id
				' . $GLOBALS['TSFE']->cObj->enableFields('pages') . '
				AND pages.no_search=0
				AND pages.doktype<200
			';
        } elseif ($searchRootPageIdList[0] >= 0) {

            // Collecting all pages IDs in which to search;
            // filtering out ALL pages that are not accessible due to enableFields. Does NOT look for "no_search" field!
            $idList = [];
            foreach ($searchRootPageIdList as $rootId) {
                /** @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObj */
                $cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);
                $idList[] = $cObj->getTreeList(-1 * $rootId, 9999);
            }
            $pageWhere = ' ISEC.page_id IN (' . implode(',', $idList) . ')';
        } else {
            // Disable everything... (select all)
            $pageWhere = ' 1=1';
        }
        $searchBoolean = '';
        if ($searchData['searchBoolean']) {
            $searchBoolean = ' IN BOOLEAN MODE';
        }
        $resource = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'index_fulltext.*, ISEC.*, IP.*',
            'index_fulltext, index_section ISEC, index_phash IP' . $pageJoin,
            'MATCH (' . $searchData['fulltextIndex'] . ')
                AGAINST (' . $GLOBALS['TYPO3_DB']->fullQuoteStr($searchData['searchString'], 'index_fulltext') . $searchBoolean . ') ' .
                $this->pObj->mediaTypeWhere() . ' ' . $this->pObj->languageWhere() . $freeIndexUidClause . '
                AND index_fulltext.phash = IP.phash
                AND ISEC.phash = IP.phash
                AND ' . $pageWhere . $this->pObj->sectionTableWhere(),
            'IP.phash,ISEC.phash,ISEC.phash_t3,ISEC.rl0,ISEC.rl1,ISEC.rl2,ISEC.page_id,ISEC.uniqid,IP.phash_grouping,IP.data_filename ,IP.data_page_id ,IP.data_page_reg1,IP.data_page_type,IP.data_page_mp,IP.gr_list,IP.item_type,IP.item_title,IP.item_description,IP.item_mtime,IP.tstamp,IP.item_size,IP.contentHash,IP.crdate,IP.parsetime,IP.sys_language_uid,IP.item_crdate,IP.cHashParams,IP.externalUrl,IP.recordUid,IP.freeIndexUid,IP.freeIndexSetId'
        );
        return $resource;
    }
}
