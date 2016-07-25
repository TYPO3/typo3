<?php
namespace TYPO3\CMS\IndexedSearch\Controller;

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

use TYPO3\CMS\Core\Html\HtmlParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\IndexedSearch\Utility;

/**
 * Index search frontend
 *
 * Creates a searchform for indexed search. Indexing must be enabled
 * for this to make sense.
 */
class SearchFormController extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin
{
    /**
     * Used as fieldname prefix
     *
     * @var string
     */
    public $prefixId = 'tx_indexedsearch';

    /**
     * Extension key.
     *
     * @var string
     */
    public $extKey = 'indexed_search';

    /**
     * See document for info about this flag...
     *
     * @var int
     */
    public $join_pages = 0;

    public $defaultResultNumber = 10;

    /**
     * Internal variable
     *
     * @var array
     */
    public $operator_translate_table = [['+', 'AND'], ['|', 'OR'], ['-', 'AND NOT']];

    /**
     * Root-page ids to search in (rl0 field where clause, see initialize() function)
     *
     * @var int|string id or comma separated list of ids
     */
    public $wholeSiteIdList = 0;

    /**
     * Search Words and operators
     *
     * @var array
     */
    public $sWArr = [];

    /**
     * Selector box values for search configuration form
     *
     * @var array
     */
    public $optValues = [];

    /**
     * Will hold the first row in result - used to calculate relative hit-ratings.
     *
     * @var array
     */
    public $firstRow = [];

    /**
     * Caching of page path
     *
     * @var array
     */
    public $cache_path = [];

    /**
     * Caching of root line data
     *
     * @var array
     */
    public $cache_rl = [];

    /**
     * Required fe_groups memberships for display of a result.
     *
     * @var array
     */
    public $fe_groups_required = [];

    /**
     * sys_domain records
     *
     * @var array
     */
    public $domain_records = [];

    /**
     * Select clauses for individual words
     *
     * @var array
     */
    public $wSelClauses = [];

    /**
     * Page tree sections for search result.
     *
     * @var array
     */
    public $resultSections = [];

    /**
     * External parser objects
     * @var array
     */
    public $external_parsers = [];

    /**
     * Storage of icons....
     *
     * @var array
     */
    public $iconFileNameCache = [];

    /**
     * Will hold the content of $conf['templateFile']
     *
     * @var string
     */
    public $templateCode = '';

    public $hiddenFieldList = 'ext, type, defOp, media, order, group, lang, desc, results';

    /**
     * Indexer configuration, coming from $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['indexed_search']
     *
     * @var array
     */
    public $indexerConfig = [];

    /**
     * @var bool
     */
    public $enableMetaphoneSearch = false;

    public $storeMetaphoneInfoAsWords;

    /**
     * Lexer object
     *
     * @var \TYPO3\CMS\IndexedSearch\Lexer
     */
    public $lexerObj;

    /**
     * Main function, called from TypoScript as a USER_INT object.
     *
     * @param string $content Content input, ignore (just put blank string)
     * @param array $conf TypoScript configuration of the plugin!
     * @return string HTML code for the search form / result display.
     */
    public function main($content, $conf)
    {
        // Initialize:
        $this->conf = $conf;
        $this->pi_loadLL('EXT:indexed_search/Resources/Private/Language/locallang_pi.xlf');
        $this->pi_setPiVarDefaults();
        // Initialize:
        $this->initialize();
        // Do search:
        // If there were any search words entered...
        if (is_array($this->sWArr) && !empty($this->sWArr)) {
            $content = $this->doSearch($this->sWArr);
        }
        // Finally compile all the content, form, messages and results:
        $content = $this->makeSearchForm($this->optValues) . $this->printRules() . $content;
        return $this->pi_wrapInBaseClass($content);
    }

    /**
     * Initialize internal variables, especially selector box values for the search form and search words
     *
     * @return void
     */
    public function initialize()
    {
        // Indexer configuration from Extension Manager interface:
        $this->indexerConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['indexed_search']);
        $this->enableMetaphoneSearch = (bool)$this->indexerConfig['enableMetaphoneSearch'];
        $this->storeMetaphoneInfoAsWords = !\TYPO3\CMS\IndexedSearch\Utility\IndexedSearchUtility::isTableUsed('index_words');
        // Initialize external document parsers for icon display and other soft operations
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['external_parsers'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['external_parsers'] as $extension => $_objRef) {
                $this->external_parsers[$extension] = GeneralUtility::getUserObj($_objRef);
                // Init parser and if it returns FALSE, unset its entry again:
                if (!$this->external_parsers[$extension]->softInit($extension)) {
                    unset($this->external_parsers[$extension]);
                }
            }
        }
        // Init lexer (used to post-processing of search words)
        $lexerObjRef = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['lexer'] ?: \TYPO3\CMS\IndexedSearch\Lexer::class;
        $this->lexerObj = GeneralUtility::getUserObj($lexerObjRef);
        // If "_sections" is set, this value overrides any existing value.
        if ($this->piVars['_sections']) {
            $this->piVars['sections'] = $this->piVars['_sections'];
        }
        // If "_sections" is set, this value overrides any existing value.
        if ($this->piVars['_freeIndexUid'] !== '_') {
            $this->piVars['freeIndexUid'] = $this->piVars['_freeIndexUid'];
        }
        // Add previous search words to current
        if ($this->piVars['sword_prev_include'] && $this->piVars['sword_prev']) {
            $this->piVars['sword'] = trim($this->piVars['sword_prev']) . ' ' . $this->piVars['sword'];
        }
        $this->piVars['results'] = MathUtility::forceIntegerInRange($this->piVars['results'], 1, 100, $this->defaultResultNumber);
        // Make sure that some cropping and markup constants used later are defined
        $this->loadSettings();

        // Selector-box values defined here:
        $this->optValues = [
            'type' => [
                '0' => $this->pi_getLL('opt_type_0'),
                '1' => $this->pi_getLL('opt_type_1'),
                '2' => $this->pi_getLL('opt_type_2'),
                '3' => $this->pi_getLL('opt_type_3'),
                '10' => $this->pi_getLL('opt_type_10'),
                '20' => $this->pi_getLL('opt_type_20')
            ],
            'defOp' => [
                '0' => $this->pi_getLL('opt_defOp_0'),
                '1' => $this->pi_getLL('opt_defOp_1')
            ],
            'sections' => [
                '0' => $this->pi_getLL('opt_sections_0'),
                '-1' => $this->pi_getLL('opt_sections_-1'),
                '-2' => $this->pi_getLL('opt_sections_-2'),
                '-3' => $this->pi_getLL('opt_sections_-3')
            ],
            'freeIndexUid' => [
                '-1' => $this->pi_getLL('opt_freeIndexUid_-1'),
                '-2' => $this->pi_getLL('opt_freeIndexUid_-2'),
                '0' => $this->pi_getLL('opt_freeIndexUid_0')
            ],
            'media' => [
                '-1' => $this->pi_getLL('opt_media_-1'),
                '0' => $this->pi_getLL('opt_media_0'),
                '-2' => $this->pi_getLL('opt_media_-2')
            ],
            'order' => [
                'rank_flag' => $this->pi_getLL('opt_order_rank_flag'),
                'rank_freq' => $this->pi_getLL('opt_order_rank_freq'),
                'rank_first' => $this->pi_getLL('opt_order_rank_first'),
                'rank_count' => $this->pi_getLL('opt_order_rank_count'),
                'mtime' => $this->pi_getLL('opt_order_mtime'),
                'title' => $this->pi_getLL('opt_order_title'),
                'crdate' => $this->pi_getLL('opt_order_crdate')
            ],
            'group' => [
                'sections' => $this->pi_getLL('opt_group_sections'),
                'flat' => $this->pi_getLL('opt_group_flat')
            ],
            'lang' => [
                -1 => $this->pi_getLL('opt_lang_-1'),
                0 => $this->pi_getLL('opt_lang_0')
            ],
            'desc' => [
                '0' => $this->pi_getLL('opt_desc_0'),
                '1' => $this->pi_getLL('opt_desc_1')
            ],
            'results' => [
                '10' => '10',
                '20' => '20',
                '50' => '50',
                '100' => '100'
            ]
        ];
        // Remove this option if metaphone search is disabled)
        if (!$this->enableMetaphoneSearch) {
            unset($this->optValues['type']['10']);
        }
        // Free Index Uid:
        if ($this->conf['search.']['defaultFreeIndexUidList']) {
            $uidList = GeneralUtility::intExplode(',', $this->conf['search.']['defaultFreeIndexUidList']);
            $indexCfgRecords = $this->databaseConnection->exec_SELECTgetRows('uid,title', 'index_config', 'uid IN (' . implode(',', $uidList) . ')' . $this->cObj->enableFields('index_config'), '', '', '', 'uid');
            foreach ($uidList as $uidValue) {
                if (is_array($indexCfgRecords[$uidValue])) {
                    $this->optValues['freeIndexUid'][$uidValue] = $indexCfgRecords[$uidValue]['title'];
                }
            }
        }
        // Should we use join_pages instead of long lists of uids?
        if ($this->conf['search.']['skipExtendToSubpagesChecking']) {
            $this->join_pages = 1;
        }
        // Add media to search in:
        if (trim($this->conf['search.']['mediaList']) !== '') {
            $mediaList = implode(',', GeneralUtility::trimExplode(',', $this->conf['search.']['mediaList'], true));
        }
        foreach ($this->external_parsers as $extension => $obj) {
            // Skip unwanted extensions
            if ($mediaList && !GeneralUtility::inList($mediaList, $extension)) {
                continue;
            }
            if ($name = $obj->searchTypeMediaTitle($extension)) {
                $this->optValues['media'][$extension] = $this->pi_getLL('opt_sections_' . $extension, $name);
            }
        }
        // Add operators for various languages
        // Converts the operators to UTF-8 and lowercase
        $this->operator_translate_table[] = [$this->frontendController->csConvObj->conv_case('utf-8', $this->frontendController->csConvObj->utf8_encode($this->pi_getLL('local_operator_AND'), $this->frontendController->renderCharset), 'toLower'), 'AND'];
        $this->operator_translate_table[] = [$this->frontendController->csConvObj->conv_case('utf-8', $this->frontendController->csConvObj->utf8_encode($this->pi_getLL('local_operator_OR'), $this->frontendController->renderCharset), 'toLower'), 'OR'];
        $this->operator_translate_table[] = [$this->frontendController->csConvObj->conv_case('utf-8', $this->frontendController->csConvObj->utf8_encode($this->pi_getLL('local_operator_NOT'), $this->frontendController->renderCharset), 'toLower'), 'AND NOT'];
        // This is the id of the site root. This value may be a commalist of integer (prepared for this)
        $this->wholeSiteIdList = (int)$this->frontendController->config['rootLine'][0]['uid'];
        // Creating levels for section menu:
        // This selects the first and secondary menus for the "sections" selector - so we can search in sections and sub sections.
        if ($this->conf['show.']['L1sections']) {
            $firstLevelMenu = $this->getMenu($this->wholeSiteIdList);
            foreach ($firstLevelMenu as $optionName => $mR) {
                if (!$mR['nav_hide']) {
                    $this->optValues['sections']['rl1_' . $mR['uid']] = trim($this->pi_getLL('opt_RL1') . ' ' . $mR['title']);
                    if ($this->conf['show.']['L2sections']) {
                        $secondLevelMenu = $this->getMenu($mR['uid']);
                        foreach ($secondLevelMenu as $kk2 => $mR2) {
                            if (!$mR2['nav_hide']) {
                                $this->optValues['sections']['rl2_' . $mR2['uid']] = trim($this->pi_getLL('opt_RL2') . ' ' . $mR2['title']);
                            } else {
                                unset($secondLevelMenu[$kk2]);
                            }
                        }
                        $this->optValues['sections']['rl2_' . implode(',', array_keys($secondLevelMenu))] = $this->pi_getLL('opt_RL2ALL');
                    }
                } else {
                    unset($firstLevelMenu[$optionName]);
                }
            }
            $this->optValues['sections']['rl1_' . implode(',', array_keys($firstLevelMenu))] = $this->pi_getLL('opt_RL1ALL');
        }
        // Setting the list of root IDs for the search. Notice, these page IDs MUST have a TypoScript template with root flag on them! Basically this list is used to select on the "rl0" field and page ids are registered as "rl0" only if a TypoScript template record with root flag is there.
        // This happens AFTER the use of $this->wholeSiteIdList above because the above will then fetch the menu for the CURRENT site - regardless of this kind of searching here. Thus a general search will lookup in the WHOLE database while a specific section search will take the current sections...
        if ($this->conf['search.']['rootPidList']) {
            $this->wholeSiteIdList = implode(',', GeneralUtility::intExplode(',', $this->conf['search.']['rootPidList']));
        }
        // Load the template
        $this->templateCode = $this->cObj->fileResource($this->conf['templateFile']);
        // Add search languages:
        $res = $this->databaseConnection->exec_SELECTquery('*', 'sys_language', '1=1' . $this->cObj->enableFields('sys_language'));
        while (false !== ($data = $this->databaseConnection->sql_fetch_assoc($res))) {
            $this->optValues['lang'][$data['uid']] = $data['title'];
        }
        $this->databaseConnection->sql_free_result($res);
        // Calling hook for modification of initialized content
        if ($hookObj = $this->hookRequest('initialize_postProc')) {
            $hookObj->initialize_postProc();
        }
        // Default values set:
        // Setting first values in optValues as default values IF there is not corresponding piVar value set already.
        foreach ($this->optValues as $optionName => $optionValue) {
            if (!isset($this->piVars[$optionName])) {
                reset($optionValue);
                $this->piVars[$optionName] = key($optionValue);
            }
        }
        // Blind selectors:
        if (is_array($this->conf['blind.'])) {
            foreach ($this->conf['blind.'] as $optionName => $optionValue) {
                if (is_array($optionValue)) {
                    foreach ($optionValue as $optionValueSubKey => $optionValueSubValue) {
                        if (!is_array($optionValueSubValue) && $optionValueSubValue && is_array($this->optValues[substr($optionName, 0, -1)])) {
                            unset($this->optValues[substr($optionName, 0, -1)][$optionValueSubKey]);
                        }
                    }
                } elseif ($optionValue) {
                    // If value is not set, unset the option array
                    unset($this->optValues[$optionName]);
                }
            }
        }
        // This gets the search-words into the $sWArr:
        $this->sWArr = $this->getSearchWords($this->piVars['defOp']);
    }

    /**
     * Splits the search word input into an array where each word is represented by an array with key "sword" holding the search word and key "oper" holding the SQL operator (eg. AND, OR)
     *
     * Only words with 2 or more characters are accepted
     * Max 200 chars total
     * Space is used to split words, "" can be used search for a whole string
     * AND, OR and NOT are prefix words, overruling the default operator
     * +/|/- equals AND, OR and NOT as operators.
     * All search words are converted to lowercase.
     *
     * $defOp is the default operator. 1=OR, 0=AND
     *
     * @param bool $defOp If TRUE, the default operator will be OR, not AND
     * @return array Returns array with search words if any found
     */
    public function getSearchWords($defOp)
    {
        // Shorten search-word string to max 200 bytes (does NOT take multibyte charsets into account - but never mind, shortening the string here is only a run-away feature!)
        $inSW = substr($this->piVars['sword'], 0, 200);
        // Convert to UTF-8 + conv. entities (was also converted during indexing!)
        $inSW = $this->frontendController->csConvObj->utf8_encode($inSW, $this->frontendController->metaCharset);
        $inSW = $this->frontendController->csConvObj->entities_to_utf8($inSW, true);
        $sWordArray = false;
        if ($hookObj = $this->hookRequest('getSearchWords')) {
            $sWordArray = $hookObj->getSearchWords_splitSWords($inSW, $defOp);
        } else {
            if ($this->piVars['type'] == 20) {
                // type = Sentence
                $sWordArray = [['sword' => trim($inSW), 'oper' => 'AND']];
            } else {
                $searchWords = \TYPO3\CMS\IndexedSearch\Utility\IndexedSearchUtility::getExplodedSearchString($inSW, $defOp == 1 ? 'OR' : 'AND', $this->operator_translate_table);
                if (is_array($searchWords)) {
                    $sWordArray = $this->procSearchWordsByLexer($searchWords);
                }
            }
        }
        return $sWordArray;
    }

    /**
     * Post-process the search word array so it will match the words that was indexed (including case-folding if any)
     * If any words are split into multiple words (eg. CJK will be!) the operator of the main word will remain.
     *
     * @param array $SWArr Search word array
     * @return array Search word array, processed through lexer
     */
    public function procSearchWordsByLexer($SWArr)
    {
        // Init output variable:
        $newSWArr = [];
        // Traverse the search word array:
        foreach ($SWArr as $wordDef) {
            if (!strstr($wordDef['sword'], ' ')) {
                // No space in word (otherwise it might be a sentense in quotes like "there is").
                // Split the search word by lexer:
                $res = $this->lexerObj->split2Words($wordDef['sword']);
                // Traverse lexer result and add all words again:
                foreach ($res as $word) {
                    $newSWArr[] = ['sword' => $word, 'oper' => $wordDef['oper']];
                }
            } else {
                $newSWArr[] = $wordDef;
            }
        }
        // Return result:
        return $newSWArr;
    }

    /*****************************
     *
     * Main functions
     *
     *****************************/
    /**
     * Performs the search, the display and writing stats
     *
     * @param array $sWArr Search words in array, see ->getSearchWords() for details
     * @return string HTML for result display.
     */
    public function doSearch($sWArr)
    {
        // Find free index uid:
        $freeIndexUid = $this->piVars['freeIndexUid'];
        if ($freeIndexUid == -2) {
            $freeIndexUid = $this->conf['search.']['defaultFreeIndexUidList'];
        }
        $indexCfgs = GeneralUtility::intExplode(',', $freeIndexUid);
        $accumulatedContent = '';
        foreach ($indexCfgs as $freeIndexUid) {
            // Get result rows:
            $pt1 = GeneralUtility::milliseconds();
            if ($hookObj = $this->hookRequest('getResultRows')) {
                $resData = $hookObj->getResultRows($sWArr, $freeIndexUid);
            } else {
                $resData = $this->getResultRows($sWArr, $freeIndexUid);
            }
            // Display search results:
            $pt2 = GeneralUtility::milliseconds();
            if ($hookObj = $this->hookRequest('getDisplayResults')) {
                $content = $hookObj->getDisplayResults($sWArr, $resData, $freeIndexUid);
            } else {
                $content = $this->getDisplayResults($sWArr, $resData, $freeIndexUid);
            }
            $pt3 = GeneralUtility::milliseconds();
            // Create header if we are searching more than one indexing configuration:
            if (count($indexCfgs) > 1) {
                if ($freeIndexUid > 0) {
                    $indexCfgRec = $this->databaseConnection->exec_SELECTgetSingleRow('title', 'index_config', 'uid=' . (int)$freeIndexUid . $this->cObj->enableFields('index_config'));
                    $titleString = $indexCfgRec['title'];
                } else {
                    $titleString = $this->pi_getLL('opt_freeIndexUid_header_' . $freeIndexUid);
                }
                $content = '<h1 class="tx-indexedsearch-category">' . htmlspecialchars($titleString) . '</h1>' . $content;
            }
            $accumulatedContent .= $content;
        }
        // Write search statistics
        $this->writeSearchStat($sWArr, $resData['count'], [$pt1, $pt2, $pt3]);
        // Return content:
        return $accumulatedContent;
    }

    /**
     * Get search result rows / data from database. Returned as data in array.
     *
     * @param array $searchWordArray Search word array
     * @param int $freeIndexUid Pointer to which indexing configuration you want to search in. -1 means no filtering. 0 means only regular indexed content.
     * @return array False if no result, otherwise an array with keys for first row, result rows and total number of results found.
     */
    public function getResultRows($searchWordArray, $freeIndexUid = -1)
    {
        // Getting SQL result pointer. This fetches ALL results (1,000,000 if found)
        $GLOBALS['TT']->push('Searching result');
        if ($hookObj = &$this->hookRequest('getResultRows_SQLpointer')) {
            $res = $hookObj->getResultRows_SQLpointer($searchWordArray, $freeIndexUid);
        } else {
            $res = $this->getResultRows_SQLpointer($searchWordArray, $freeIndexUid);
        }
        $GLOBALS['TT']->pull();
        // Organize and process result:
        $result = false;
        if ($res) {
            $totalSearchResultCount = $this->databaseConnection->sql_num_rows($res);
            // Total search-result count
            $currentPageNumber = MathUtility::forceIntegerInRange($this->piVars['pointer'], 0, floor($totalSearchResultCount / $this->piVars['results']));
            // The pointer is set to the result page that is currently being viewed
            // Initialize result accumulation variables:
            $positionInSearchResults = 0;
            $groupingPhashes = [];
            // Used for filtering out duplicates
            $groupingChashes = [];
            // Used for filtering out duplicates BASED ON cHash
            $firstRow = [];
            // Will hold the first row in result - used to calculate relative hit-ratings.
            $resultRows = [];
            // Will hold the results rows for display.
            // Should we continue counting and checking of results even if
            // we are sure they are not displayed in this request?
            // This will slow down your page rendering, but it allows
            // precise search result counters.
            $calculateExactCount = (bool)$this->conf['search.']['exactCount'];
            $lastResultNumberOnPreviousPage = $currentPageNumber * $this->piVars['results'];
            $firstResultNumberOnNextPage = ($currentPageNumber + 1) * $this->piVars['results'];
            $lastResultNumberToAnalyze = ($currentPageNumber + 1) * $this->piVars['results'] + $this->piVars['results'];
            // Now, traverse result and put the rows to be displayed into an array
            // Each row should contain the fields from 'ISEC.*, IP.*' combined + artificial fields "show_resume" (bool) and "result_number" (counter)
            while (false !== ($row = $this->databaseConnection->sql_fetch_assoc($res))) {
                // Set first row:
                if ($positionInSearchResults === 0) {
                    $firstRow = $row;
                }
                $row['show_resume'] = $this->checkResume($row);
                // Tells whether we can link directly to a document or not (depends on possible right problems)
                $phashGr = !in_array($row['phash_grouping'], $groupingPhashes);
                $chashGr = !in_array(($row['contentHash'] . '.' . $row['data_page_id']), $groupingChashes);
                if ($phashGr && $chashGr) {
                    if ($row['show_resume'] || $this->conf['show.']['forbiddenRecords']) {
                        // Only if the resume may be shown are we going to filter out duplicates...
                        if (!$this->multiplePagesType($row['item_type'])) {
                            // Only on documents which are not multiple pages documents
                            $groupingPhashes[] = $row['phash_grouping'];
                        }
                        $groupingChashes[] = $row['contentHash'] . '.' . $row['data_page_id'];
                        $positionInSearchResults++;
                        // Check if we are inside result range for current page
                        if ($positionInSearchResults > $lastResultNumberOnPreviousPage && $positionInSearchResults <= $lastResultNumberToAnalyze) {
                            // Collect results to display
                            $row['result_number'] = $positionInSearchResults;
                            $resultRows[] = $row;
                            // This may lead to a problem: If the result
                            // check is not stopped here, the search will
                            // take longer. However the result counter
                            // will not filter out grouped cHashes/pHashes
                            // that were not processed yet. You can change
                            // this behavior using the "search.exactCount"
                            // property (see above).
                            $nextResultPosition = $positionInSearchResults + 1;
                            if (!$calculateExactCount && $nextResultPosition > $firstResultNumberOnNextPage) {
                                break;
                            }
                        }
                    } else {
                        // Skip this row if the user cannot view it (missing permission)
                        $totalSearchResultCount--;
                    }
                } else {
                    // For each time a phash_grouping document is found
                    // (which is thus not displayed) the search-result count
                    // is reduced, so that it matches the number of rows displayed.
                    $totalSearchResultCount--;
                }
            }
            $this->databaseConnection->sql_free_result($res);
            $result = [
                'resultRows' => $resultRows,
                'firstRow' => $firstRow,
                'count' => $totalSearchResultCount
            ];
        }
        return $result;
    }

    /**
     * Gets a SQL result pointer to traverse for the search records.
     *
     * @param array $sWArr Search words
     * @param int $freeIndexUid Pointer to which indexing configuration you want to search in. -1 means no filtering. 0 means only regular indexed content.
     * @return bool|\mysqli_result Query result pointer
     */
    public function getResultRows_SQLpointer($sWArr, $freeIndexUid = -1)
    {
        // This SEARCHES for the searchwords in $sWArr AND returns a COMPLETE list of phash-integers of the matches.
        $list = $this->getPhashList($sWArr);
        // Perform SQL Search / collection of result rows array:
        if ($list) {
            // Do the search:
            $GLOBALS['TT']->push('execFinalQuery');
            $res = $this->execFinalQuery($list, $freeIndexUid);
            $GLOBALS['TT']->pull();
            return $res;
        } else {
            return false;
        }
    }

    /**
     * Compiles the HTML display of the incoming array of result rows.
     *
     * @param array $sWArr Search words array (for display of text describing what was searched for)
     * @param array $resData Array with result rows, count, first row.
     * @param int $freeIndexUid Pointer to which indexing configuration you want to search in. -1 means no filtering. 0 means only regular indexed content.
     * @return string HTML content to display result.
     */
    public function getDisplayResults($sWArr, $resData, $freeIndexUid = -1)
    {
        $content = '';
        // Perform display of result rows array:
        if ($resData) {
            $GLOBALS['TT']->push('Display Final result');
            // Set first selected row (for calculation of ranking later)
            $this->firstRow = $resData['firstRow'];
            // Result display here:
            $rowcontent = '';
            $rowcontent .= $this->compileResult($resData['resultRows'], $freeIndexUid);
            // Browsing box:
            if ($resData['count']) {
                $this->internal['res_count'] = $resData['count'];
                $this->internal['results_at_a_time'] = $this->piVars['results'];
                $this->internal['maxPages'] = MathUtility::forceIntegerInRange($this->conf['search.']['page_links'], 1, 100, 10);
                $resultSectionsCount = count($this->resultSections);
                $addString = $resData['count'] && $this->piVars['group'] == 'sections' && $freeIndexUid <= 0 ? ' ' . sprintf($this->pi_getLL(($resultSectionsCount > 1 ? 'inNsections' : 'inNsection')), $resultSectionsCount) : '';
                $browseBox1 = $this->renderPagination(1, $addString, $this->printResultSectionLinks(), $freeIndexUid);
                $browseBox2 = $this->renderPagination(0, '', '', $freeIndexUid);
                // Browsing nav, bottom.
                $content = $browseBox1 . $rowcontent . $browseBox2;
            } else {
                $content = '<p' . $this->pi_classParam('noresults') . '>' . $this->pi_getLL('noResults', '', true) . '</p>';
            }
            $GLOBALS['TT']->pull();
        } else {
            $content .= '<p' . $this->pi_classParam('noresults') . '>' . $this->pi_getLL('noResults', '', true) . '</p>';
        }
        // Print a message telling which words we searched for, and in which sections etc.
        $what = $this->tellUsWhatIsSeachedFor($sWArr) . (substr($this->piVars['sections'], 0, 2) == 'rl' ? ' ' . $this->pi_getLL('inSection', '', true) . ' "' . $this->getPathFromPageId(substr($this->piVars['sections'], 4)) . '"' : '');
        $what = '<div' . $this->pi_classParam('whatis') . '>' . $this->cObj->stdWrap($what, $this->conf['whatis_stdWrap.']) . '</div>';
        $content = $what . $content;
        // Return content:
        return $content;
    }

    /**
     * Takes the array with resultrows as input and returns the result-HTML-code
     * Takes the "group" var into account: Makes a "section" or "flat" display.
     *
     * @param array $resultRows Result rows
     * @param int $freeIndexUid Pointer to which indexing configuration you want to search in. -1 means no filtering. 0 means only regular indexed content.
     * @return string HTML
     */
    public function compileResult($resultRows, $freeIndexUid = -1)
    {
        $content = '';
        // Transfer result rows to new variable, performing some mapping of sub-results etc.
        $newResultRows = [];
        foreach ($resultRows as $row) {
            $id = md5($row['phash_grouping']);
            if (is_array($newResultRows[$id])) {
                if (!$newResultRows[$id]['show_resume'] && $row['show_resume']) {
                    // swapping:
                    // Remove old
                    $subrows = $newResultRows[$id]['_sub'];
                    unset($newResultRows[$id]['_sub']);
                    $subrows[] = $newResultRows[$id];
                    // Insert new:
                    $newResultRows[$id] = $row;
                    $newResultRows[$id]['_sub'] = $subrows;
                } else {
                    $newResultRows[$id]['_sub'][] = $row;
                }
            } else {
                $newResultRows[$id] = $row;
            }
        }
        $resultRows = $newResultRows;
        $this->resultSections = [];
        if ($freeIndexUid <= 0) {
            switch ($this->piVars['group']) {
                case 'sections':
                    $rl2flag = substr($this->piVars['sections'], 0, 2) == 'rl';
                    $sections = [];
                    foreach ($resultRows as $row) {
                        $id = $row['rl0'] . '-' . $row['rl1'] . ($rl2flag ? '-' . $row['rl2'] : '');
                        $sections[$id][] = $row;
                    }
                    $this->resultSections = [];
                    foreach ($sections as $id => $resultRows) {
                        $rlParts = explode('-', $id);
                        $theId = $rlParts[2] ? $rlParts[2] : ($rlParts[1] ? $rlParts[1] : $rlParts[0]);
                        $theRLid = $rlParts[2] ? 'rl2_' . $rlParts[2] : ($rlParts[1] ? 'rl1_' . $rlParts[1] : '0');
                        $sectionName = $this->getPathFromPageId($theId);
                        if ($sectionName[0] == '/') {
                            $sectionName = substr($sectionName, 1);
                        }
                        if (!trim($sectionName)) {
                            $sectionTitleLinked = $this->pi_getLL('unnamedSection', '', true) . ':';
                        } elseif ($this->conf['linkSectionTitles']) {
                            $quotedPrefix = GeneralUtility::quoteJSvalue($this->prefixId);
                            $onclick = 'document.forms[' . $quotedPrefix . '][' . GeneralUtility::quoteJSvalue($this->prefixId . '[_sections]') . '].value=' . GeneralUtility::quoteJSvalue($theRLid) . ';document.forms[' . $quotedPrefix . '].submit();return false;';
                            $sectionTitleLinked = '<a href="#" onclick="' . htmlspecialchars($onclick) . '">' . $sectionName . ':</a>';
                        } else {
                            $sectionTitleLinked = $sectionName;
                        }
                        $resultRowsCount = count($resultRows);
                        $this->resultSections[$id] = [$sectionName, $resultRowsCount];
                        // Add content header:
                        $content .= $this->makeSectionHeader($id, $sectionTitleLinked, $resultRowsCount);
                        // Render result rows:
                        $resultlist = '';
                        foreach ($resultRows as $row) {
                            $resultlist .= $this->printResultRow($row);
                        }
                        $content .= $this->cObj->stdWrap($resultlist, $this->conf['resultlist_stdWrap.']);
                    }
                    break;
                default:
                    // flat:
                    $resultlist = '';
                    foreach ($resultRows as $row) {
                        $resultlist .= $this->printResultRow($row);
                    }
                    $content .= $this->cObj->stdWrap($resultlist, $this->conf['resultlist_stdWrap.']);
            }
        } else {
            $resultlist = '';
            foreach ($resultRows as $row) {
                $resultlist .= $this->printResultRow($row);
            }
            $content .= $this->cObj->stdWrap($resultlist, $this->conf['resultlist_stdWrap.']);
        }
        return '<div' . $this->pi_classParam('res') . '>' . $content . '</div>';
    }

    /***********************************
     *
     *	Searching functions (SQL)
     *
     ***********************************/
    /**
     * Returns a COMPLETE list of phash-integers matching the search-result composed of the search-words in the sWArr array.
     * The list of phash integers are unsorted and should be used for subsequent selection of index_phash records for display of the result.
     *
     * @param array $sWArr Search word array
     * @return string List of integers
     */
    public function getPhashList($sWArr)
    {
        // Initialize variables:
        $c = 0;
        $totalHashList = [];
        // This array accumulates the phash-values
        // Traverse searchwords; for each, select all phash integers and merge/diff/intersect them with previous word (based on operator)
        foreach ($sWArr as $k => $v) {
            // Making the query for a single search word based on the search-type
            $sWord = $v['sword'];
            $theType = (string)$this->piVars['type'];
            if (strstr($sWord, ' ')) {
                // If there are spaces in the search-word, make a full text search instead.
                $theType = 20;
            }
            $GLOBALS['TT']->push('SearchWord "' . $sWord . '" - $theType=' . $theType);
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
                    * @var \TYPO3\CMS\IndexedSearch\Indexer
                    */
                    // Initialize the indexer-class
                    $indexerObj = GeneralUtility::makeInstance(\TYPO3\CMS\IndexedSearch\Indexer::class);
                    // Perform metaphone search
                    $res = $this->searchMetaphone($indexerObj->metaphone($sWord, $this->storeMetaphoneInfoAsWords));
                    unset($indexerObj);
                    break;
                case '20':
                    // Sentence
                    $res = $this->searchSentence($sWord);
                    $this->piVars['order'] = 'mtime';
                    // If there is a fulltext search for a sentence there is a likeliness that sorting cannot be done by the rankings from the rel-table (because no relations will exist for the sentence in the word-table). So therefore mtime is used instead. It is not required, but otherwise some hits may be left out.
                    break;
                default:
                    // Distinct word
                    $res = $this->searchDistinct($sWord);
            }
            // If there was a query to do, then select all phash-integers which resulted from this.
            if ($res) {
                // Get phash list by searching for it:
                $phashList = [];
                while ($row = $this->databaseConnection->sql_fetch_assoc($res)) {
                    $phashList[] = $row['phash'];
                }
                $this->databaseConnection->sql_free_result($res);
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
                    $totalHashList = $phashList;
                }
            }
            $GLOBALS['TT']->pull();
            $c++;
        }
        return implode(',', $totalHashList);
    }

    /**
     * Returns a query which selects the search-word from the word/rel tables.
     *
     * @param string $wordSel WHERE clause selecting the word from phash
     * @param string $plusQ Additional AND clause in the end of the query.
     * @return bool|\mysqli_result SQL result pointer
     */
    public function execPHashListQuery($wordSel, $plusQ = '')
    {
        return $this->databaseConnection->exec_SELECTquery('IR.phash', 'index_words IW,
						index_rel IR,
						index_section ISEC', $wordSel . '
						AND IW.wid=IR.wid
						AND ISEC.phash = IR.phash
						' . $this->sectionTableWhere() . '
						' . $plusQ, 'IR.phash');
    }

    /**
     * Search for a word
     *
     * @param string $sWord Word to search for
     * @param int $wildcard Bit-field of Utility\LikeWildcard
     * @return bool|\mysqli_result SQL result pointer
     */
    public function searchWord($sWord, $wildcard)
    {
        $likeWildcard = Utility\LikeWildcard::cast($wildcard);
        $wSel = $likeWildcard->getLikeQueryPart(
            'index_words',
            'IW.baseword',
            $sWord
        );

        $this->wSelClauses[] = $wSel;
        $res = $this->execPHashListQuery($wSel, ' AND is_stopword=0');
        return $res;
    }

    /**
     * Search for one distinct word
     *
     * @param string $sWord Word to search for
     * @return bool|\mysqli_result SQL result pointer
     */
    public function searchDistinct($sWord)
    {
        $wSel = 'IW.wid=' . \TYPO3\CMS\IndexedSearch\Utility\IndexedSearchUtility::md5inthash($sWord);
        $this->wSelClauses[] = $wSel;
        $res = $this->execPHashListQuery($wSel, ' AND is_stopword=0');
        return $res;
    }

    /**
     * Search for a sentence
     *
     * @param string $sSentence Sentence to search for
     * @return bool|\mysqli_result SQL result pointer
     */
    public function searchSentence($sSentence)
    {
        $this->wSelClauses[] = '1=1';
        $likeWildcard = Utility\LikeWildcard::cast(Utility\LikeWildcard::BOTH);
        $likePart = $likeWildcard->getLikeQueryPart(
            'index_fulltext',
            'IFT.fulltextdata',
            $sSentence
        );

        return $this->databaseConnection->exec_SELECTquery('ISEC.phash',
            'index_section ISEC, index_fulltext IFT',
            $likePart . ' AND ISEC.phash = IFT.phash' . $this->sectionTableWhere(), 'ISEC.phash'
        );
    }

    /**
     * Search for a metaphone word
     *
     * @param string $sWord Word to search for
     * @return \mysqli_result SQL result pointer
     */
    public function searchMetaphone($sWord)
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
        $out = $this->wholeSiteIdList < 0 ? '' : ' AND ISEC.rl0 IN (' . $this->wholeSiteIdList . ')';
        $match = '';
        if (substr($this->piVars['sections'], 0, 4) == 'rl1_') {
            $list = implode(',', GeneralUtility::intExplode(',', substr($this->piVars['sections'], 4)));
            $out .= ' AND ISEC.rl1 IN (' . $list . ')';
            $match = true;
        } elseif (substr($this->piVars['sections'], 0, 4) == 'rl2_') {
            $list = implode(',', GeneralUtility::intExplode(',', substr($this->piVars['sections'], 4)));
            $out .= ' AND ISEC.rl2 IN (' . $list . ')';
            $match = true;
        } elseif (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['addRootLineFields'])) {
            // Traversing user configured fields to see if any of those are used to limit search to a section:
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['addRootLineFields'] as $fieldName => $rootLineLevel) {
                if (substr($this->piVars['sections'], 0, strlen($fieldName) + 1) == $fieldName . '_') {
                    $list = implode(',', GeneralUtility::intExplode(',', substr($this->piVars['sections'], strlen($fieldName) + 1)));
                    $out .= ' AND ISEC.' . $fieldName . ' IN (' . $list . ')';
                    $match = true;
                    break;
                }
            }
        }
        // If no match above, test the static types:
        if (!$match) {
            switch ((string)$this->piVars['sections']) {
                case '-1':
                    // '-1' => 'Only this page',
                    $out .= ' AND ISEC.page_id=' . $this->frontendController->id;
                    break;
                case '-2':
                    // '-2' => 'Top + level 1',
                    $out .= ' AND ISEC.rl2=0';
                    break;
                case '-3':
                    // '-3' => 'Level 2 and out',
                    $out .= ' AND ISEC.rl2>0';
                    break;
            }
        }
        return $out;
    }

    /**
     * Returns AND statement for selection of media type
     *
     * @return string AND statement for selection of media type
     */
    public function mediaTypeWhere()
    {
        switch ((string)$this->piVars['media']) {
            case '0':
                // '0' => 'Kun TYPO3 sider',
                $out = ' AND IP.item_type=' . $this->databaseConnection->fullQuoteStr('0', 'index_phash');
                break;
            case '-2':
                // All external documents
                $out = ' AND IP.item_type<>' . $this->databaseConnection->fullQuoteStr('0', 'index_phash');
                break;
            case '-1':
                // All content
                $out = '';
                break;
            default:
                $out = ' AND IP.item_type=' . $this->databaseConnection->fullQuoteStr($this->piVars['media'], 'index_phash');
        }
        return $out;
    }

    /**
     * Returns AND statement for selection of language
     *
     * @return string AND statement for selection of language
     */
    public function languageWhere()
    {
        $languageWhere = '';
        if ($this->piVars['lang'] >= 0) {
            // -1 is the same as ALL language.
            $languageWhere = 'AND IP.sys_language_uid=' . (int)$this->piVars['lang'];
        }
        return $languageWhere;
    }

    /**
     * Where-clause for free index-uid value.
     *
     * @param int $freeIndexUid Free Index UID value to limit search to.
     * @return string WHERE SQL clause part.
     */
    public function freeIndexUidWhere($freeIndexUid)
    {
        if ($freeIndexUid < 0) {
            return '';
        }
        // First, look if the freeIndexUid is a meta configuration:
        $indexCfgRec = $this->databaseConnection->exec_SELECTgetSingleRow('indexcfgs', 'index_config', 'type=5 AND uid=' . (int)$freeIndexUid . $this->cObj->enableFields('index_config'));
        if (is_array($indexCfgRec)) {
            $refs = GeneralUtility::trimExplode(',', $indexCfgRec['indexcfgs']);
            $list = [-99];
            // Default value to protect against empty array.
            foreach ($refs as $ref) {
                list($table, $uid) = GeneralUtility::revExplode('_', $ref, 2);
                switch ($table) {
                    case 'index_config':
                        $idxRec = $this->databaseConnection->exec_SELECTgetSingleRow('uid', 'index_config', 'uid=' . (int)$uid . $this->cObj->enableFields('index_config'));
                        if ($idxRec) {
                            $list[] = $uid;
                        }
                        break;
                    case 'pages':
                        $indexCfgRecordsFromPid = $this->databaseConnection->exec_SELECTgetRows('uid', 'index_config', 'pid=' . (int)$uid . $this->cObj->enableFields('index_config'));
                        foreach ($indexCfgRecordsFromPid as $idxRec) {
                            $list[] = $idxRec['uid'];
                        }
                        break;
                }
            }
            $list = array_unique($list);
        } else {
            $list = [(int)$freeIndexUid];
        }
        return ' AND IP.freeIndexUid IN (' . implode(',', $list) . ')';
    }

    /**
     * Execute final query, based on phash integer list. The main point is sorting the result in the right order.
     *
     * @param string $list List of phash integers which match the search.
     * @param int $freeIndexUid Pointer to which indexing configuration you want to search in. -1 means no filtering. 0 means only regular indexed content.
     * @return bool|\mysqli_result Query result pointer
     */
    public function execFinalQuery($list, $freeIndexUid = -1)
    {
        // Setting up methods of filtering results based on page types, access, etc.
        $page_join = '';
        $page_where = '';
        // Calling hook for alternative creation of page ID list
        if ($hookObj = $this->hookRequest('execFinalQuery_idList')) {
            $page_where = $hookObj->execFinalQuery_idList($list);
        } elseif ($this->join_pages) {
            // Alternative to getting all page ids by ->getTreeList() where "excludeSubpages" is NOT respected.
            $page_join = ',
				pages';
            $page_where = 'pages.uid = ISEC.page_id
				' . $this->cObj->enableFields('pages') . '
				AND pages.no_search=0
				AND pages.doktype<200
			';
        } elseif ($this->wholeSiteIdList >= 0) {
            // Collecting all pages IDs in which to search; filtering out ALL pages that are not accessible due to enableFields. Does NOT look for "no_search" field!
            $siteIdNumbers = GeneralUtility::intExplode(',', $this->wholeSiteIdList);
            $id_list = [];
            foreach ($siteIdNumbers as $rootId) {
                $id_list[] = $this->cObj->getTreeList(-1 * $rootId, 9999);
            }
            $page_where = 'ISEC.page_id IN (' . implode(',', $id_list) . ')';
        } else {
            // Disable everything... (select all)
            $page_where = '1=1';
        }
        // Indexing configuration clause:
        $freeIndexUidClause = $this->freeIndexUidWhere($freeIndexUid);
        // If any of the ranking sortings are selected, we must make a join with the word/rel-table again, because we need to calculate ranking based on all search-words found.
        if (substr($this->piVars['order'], 0, 5) == 'rank_') {
            switch ($this->piVars['order']) {
                case 'rank_flag':
                    // This gives priority to word-position (max-value) so that words in title, keywords, description counts more than in content.
                    // The ordering is refined with the frequency sum as well.
                    $grsel = 'MAX(IR.flags) AS order_val1, SUM(IR.freq) AS order_val2';
                    $orderBy = 'order_val1' . $this->isDescending() . ',order_val2' . $this->isDescending();
                    break;
                case 'rank_first':
                    // Results in average position of search words on page. Must be inversely sorted (low numbers are closer to top)
                    $grsel = 'AVG(IR.first) AS order_val';
                    $orderBy = 'order_val' . $this->isDescending(1);
                    break;
                case 'rank_count':
                    // Number of words found
                    $grsel = 'SUM(IR.count) AS order_val';
                    $orderBy = 'order_val' . $this->isDescending();
                    break;
                default:
                    // Frequency sum. I'm not sure if this is the best way to do it (make a sum...). Or should it be the average?
                    $grsel = 'SUM(IR.freq) AS order_val';
                    $orderBy = 'order_val' . $this->isDescending();
            }

            // So, words are imploded into an OR statement (no "sentence search" should be done here - may deselect results)
            $wordSel = '(' . implode(' OR ', $this->wSelClauses) . ') AND ';

            $res = $this->databaseConnection->exec_SELECTquery(
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
            // Otherwise, if sorting are done with the pages table or other fields, there is no need for joining with the rel/word tables:
            $orderBy = '';
            switch ((string)$this->piVars['order']) {
                case 'title':
                    $orderBy = 'IP.item_title' . $this->isDescending();
                    break;
                case 'crdate':
                    $orderBy = 'IP.item_crdate' . $this->isDescending();
                    break;
                case 'mtime':
                    $orderBy = 'IP.item_mtime' . $this->isDescending();
                    break;
            }
            $res = $this->databaseConnection->exec_SELECTquery('ISEC.*, IP.*', 'index_phash IP,index_section ISEC' . $page_join, 'IP.phash IN (' . $list . ') ' . $this->mediaTypeWhere() . ' ' . $this->languageWhere() . $freeIndexUidClause . '
							AND IP.phash = ISEC.phash
							AND ' . $page_where, 'IP.phash,ISEC.phash,ISEC.phash_t3,ISEC.rl0,ISEC.rl1,ISEC.rl2 ,ISEC.page_id,ISEC.uniqid,IP.phash_grouping,IP.data_filename ,IP.data_page_id ,IP.data_page_reg1,IP.data_page_type,IP.data_page_mp,IP.gr_list,IP.item_type,IP.item_title,IP.item_description,IP.item_mtime,IP.tstamp,IP.item_size,IP.contentHash,IP.crdate,IP.parsetime,IP.sys_language_uid,IP.item_crdate,IP.cHashParams,IP.externalUrl,IP.recordUid,IP.freeIndexUid,IP.freeIndexSetId', $orderBy);
        }
        return $res;
    }

    /**
     * Checking if the resume can be shown for the search result (depending on whether the rights are OK)
     * ? Should it also check for gr_list "0,-1"?
     *
     * @param array $row Result row array.
     * @return bool Returns TRUE if resume can safely be shown
     */
    public function checkResume($row)
    {
        // If the record is indexed by an indexing configuration, just show it.
        // At least this is needed for external URLs and files.
        // For records we might need to extend this - for instance block display if record is access restricted.
        if ($row['freeIndexUid']) {
            return true;
        }
        // Evaluate regularly indexed pages based on item_type:
        if ($row['item_type']) {
            // External media:
            // For external media we will check the access of the parent page on which the media was linked from.
            // "phash_t3" is the phash of the parent TYPO3 page row which initiated the indexing of the documents in this section.
            // So, selecting for the grlist records belonging to the parent phash-row where the current users gr_list exists will help us to know.
            // If this is NOT found, there is still a theoretical possibility that another user accessible page would display a link, so maybe the resume of such a document here may be unjustified hidden. But better safe than sorry.
            if (\TYPO3\CMS\IndexedSearch\Utility\IndexedSearchUtility::isTableUsed('index_grlist')) {
                $res = $this->databaseConnection->exec_SELECTquery('phash', 'index_grlist', 'phash=' . (int)$row['phash_t3'] . ' AND gr_list=' . $this->databaseConnection->fullQuoteStr($this->frontendController->gr_list, 'index_grlist'));
            } else {
                $res = false;
            }
            if ($res && $this->databaseConnection->sql_num_rows($res)) {
                return true;
            } else {
                return false;
            }
        } else {
            // Ordinary TYPO3 pages:
            if ((string)$row['gr_list'] !== (string)$this->frontendController->gr_list) {
                // Selecting for the grlist records belonging to the phash-row where the current users gr_list exists. If it is found it is proof that this user has direct access to the phash-rows content although he did not himself initiate the indexing...
                if (\TYPO3\CMS\IndexedSearch\Utility\IndexedSearchUtility::isTableUsed('index_grlist')) {
                    $res = $this->databaseConnection->exec_SELECTquery('phash', 'index_grlist', 'phash=' . (int)$row['phash'] . ' AND gr_list=' . $this->databaseConnection->fullQuoteStr($this->frontendController->gr_list, 'index_grlist'));
                } else {
                    $res = false;
                }
                if ($res && $this->databaseConnection->sql_num_rows($res)) {
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
     * Check if the record is still available or if it has been deleted meanwhile.
     * Currently this works for files only, since extending it to page content would cause a lot of overhead.
     *
     * @param array $row Result row array
     * @return bool Returns TRUE if record is still available
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public function checkExistance($row)
    {
        return $this->checkExistence($row);
    }

    /**
     * Check if the record is still available or if it has been deleted meanwhile.
     * Currently this works for files only, since extending it to page content would cause a lot of overhead.
     *
     * @param array $row Result row array
     * @return bool Returns TRUE if record is still available
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8 (it is not used in the core any more, see #44381)
     */
    protected function checkExistence($row)
    {
        GeneralUtility::logDeprecatedFunction();
        $recordExists = true;
        // Always expect that page content exists
        if ($row['item_type']) {
            // External media:
            if (!is_file($row['data_filename']) || !file_exists($row['data_filename'])) {
                $recordExists = false;
            }
        }
        return $recordExists;
    }

    /**
     * Returns "DESC" or "" depending on the settings of the incoming highest/lowest result order (piVars['desc']
     *
     * @param bool $inverse If TRUE, inverse the order which is defined by piVars['desc']
     * @return string " DESC" or
     */
    public function isDescending($inverse = false)
    {
        $desc = $this->piVars['desc'];
        if ($inverse) {
            $desc = !$desc;
        }
        return !$desc ? ' DESC' : '';
    }

    /**
     * Write statistics information to database for the search operation
     *
     * @param array $sWArr Search Word array
     * @param int $count Number of hits
     * @param int $pt Milliseconds the search took
     * @return void
     */
    public function writeSearchStat($sWArr, $count, $pt)
    {
        $insertFields = [
            'searchstring' => $this->piVars['sword'],
            'searchoptions' => serialize([$this->piVars, $sWArr, $pt]),
            'feuser_id' => (int)$GLOBALS['TSFE']->user['uid'],
            // fe_user id, integer
            'cookie' => (string)$GLOBALS['TSFE']->id,
            // cookie as set or retrieve. If people has cookies disabled this will vary all the time...
            'IP' => GeneralUtility::getIndpEnv('REMOTE_ADDR'),
            // Remote IP address
            'hits' => (int)$count,
            // Number of hits on the search.
            'tstamp' => $GLOBALS['EXEC_TIME']
        ];
        $this->databaseConnection->exec_INSERTquery('index_stat_search', $insertFields);
        $newId = $this->databaseConnection->sql_insert_id();
        if ($newId) {
            foreach ($sWArr as $val) {
                $insertFields = [
                    'word' => $val['sword'],
                    'index_stat_search_id' => $newId,
                    'tstamp' => $GLOBALS['EXEC_TIME'],
                    // Time stamp
                    'pageid' => $this->frontendController->id
                ];
                $this->databaseConnection->exec_INSERTquery('index_stat_word', $insertFields);
            }
        }
    }

    /***********************************
     *
     * HTML output functions
     *
     ***********************************/
    /**
     * Make search form HTML
     *
     * @param array $optValues Value/Labels pairs for search form selector boxes.
     * @return string Search form HTML
     */
    public function makeSearchForm($optValues)
    {
        $html = $this->cObj->getSubpart($this->templateCode, '###SEARCH_FORM###');
        // Multilangual text
        $substituteArray = ['legend', 'searchFor', 'extResume', 'atATime', 'orderBy', 'fromSection', 'searchIn', 'match', 'style', 'freeIndexUid'];
        foreach ($substituteArray as $marker) {
            $markerArray['###FORM_' . GeneralUtility::strtoupper($marker) . '###'] = $this->pi_getLL('form_' . $marker, '', true);
        }
        $markerArray['###FORM_SUBMIT###'] = $this->pi_getLL('submit_button_label', '', true);
        // Adding search field value
        $markerArray['###SWORD_VALUE###'] = '';
        $markerArray['###PLACEHOLDER###'] = '';
        $isHTML5 = $GLOBALS['TSFE']->config['config']['doctype'] === 'html5';
        if (!empty($this->piVars['sword'])) {
            $markerArray['###SWORD_VALUE###'] = htmlspecialchars($this->piVars['sword']);
        } elseif (!$isHTML5) {
            // only use placeholder as default value if we cannot use
            // HTML5 placeholder attribute
            $markerArray['###SWORD_VALUE###'] = htmlspecialchars($this->pi_getLL('default_search_word_entry'));
        }
        // Add a HTML5 placeholder attribute if the configured doctype allows it
        if ($isHTML5) {
            $markerArray['###PLACEHOLDER###'] = 'placeholder="' . htmlspecialchars($this->pi_getLL('default_search_word_entry')) . '"';
        }
        // Additonal keyword => "Add to current search words"
        if ($this->conf['show.']['clearSearchBox'] && $this->conf['show.']['clearSearchBox.']['enableSubSearchCheckBox']) {
            $markerArray['###SWORD_PREV_VALUE###'] = htmlspecialchars($this->conf['show.']['clearSearchBox'] ? '' : $this->piVars['sword']);
            $markerArray['###SWORD_PREV_INCLUDE_CHECKED###'] = $this->piVars['sword_prev_include'] ? ' checked="checked"' : '';
            $markerArray['###ADD_TO_CURRENT_SEARCH###'] = $this->pi_getLL('makerating_addToCurrentSearch', '', true);
        } else {
            $html = $this->cObj->substituteSubpart($html, '###ADDITONAL_KEYWORD###', '');
        }
        $markerArray['###ACTION_URL###'] = htmlspecialchars($this->getSearchFormActionURL());
        $hiddenFieldCode = $this->cObj->getSubpart($this->templateCode, '###HIDDEN_FIELDS###');
        $hiddenFieldCode = preg_replace('/^\\n\\t(.+)/ms', '$1', $hiddenFieldCode);
        // Remove first newline and tab (cosmetical issue)
        $hiddenFieldArr = [];
        foreach (GeneralUtility::trimExplode(',', $this->hiddenFieldList) as $fieldName) {
            $hiddenFieldMarkerArray = [];
            $hiddenFieldMarkerArray['###HIDDEN_FIELDNAME###'] = $this->prefixId . '[' . $fieldName . ']';
            $hiddenFieldMarkerArray['###HIDDEN_VALUE###'] = htmlspecialchars((string)$this->piVars[$fieldName]);
            $hiddenFieldArr[$fieldName] = $this->cObj->substituteMarkerArrayCached($hiddenFieldCode, $hiddenFieldMarkerArray, [], []);
        }
        // Extended search
        if ($this->piVars['ext']) {
            // Search for
            if (!is_array($optValues['type']) && !is_array($optValues['defOp']) || $this->conf['blind.']['type'] && $this->conf['blind.']['defOp']) {
                $html = $this->cObj->substituteSubpart($html, '###SELECT_SEARCH_FOR###', '');
            } else {
                if (is_array($optValues['type']) && !$this->conf['blind.']['type']) {
                    unset($hiddenFieldArr['type']);
                    $markerArray['###SELECTBOX_TYPE_VALUES###'] = $this->renderSelectBoxValues($this->piVars['type'], $optValues['type']);
                } else {
                    $html = $this->cObj->substituteSubpart($html, '###SELECT_SEARCH_TYPE###', '');
                }
                if (is_array($optValues['defOp']) || !$this->conf['blind.']['defOp']) {
                    $markerArray['###SELECTBOX_DEFOP_VALUES###'] = $this->renderSelectBoxValues($this->piVars['defOp'], $optValues['defOp']);
                } else {
                    $html = $this->cObj->substituteSubpart($html, '###SELECT_SEARCH_DEFOP###', '');
                }
            }
            // Search in
            if (!is_array($optValues['media']) && !is_array($optValues['lang']) || $this->conf['blind.']['media'] && $this->conf['blind.']['lang']) {
                $html = $this->cObj->substituteSubpart($html, '###SELECT_SEARCH_IN###', '');
            } else {
                if (is_array($optValues['media']) && !$this->conf['blind.']['media']) {
                    unset($hiddenFieldArr['media']);
                    $markerArray['###SELECTBOX_MEDIA_VALUES###'] = $this->renderSelectBoxValues($this->piVars['media'], $optValues['media']);
                } else {
                    $html = $this->cObj->substituteSubpart($html, '###SELECT_SEARCH_MEDIA###', '');
                }
                if (is_array($optValues['lang']) || !$this->conf['blind.']['lang']) {
                    unset($hiddenFieldArr['lang']);
                    $markerArray['###SELECTBOX_LANG_VALUES###'] = $this->renderSelectBoxValues($this->piVars['lang'], $optValues['lang']);
                } else {
                    $html = $this->cObj->substituteSubpart($html, '###SELECT_SEARCH_LANG###', '');
                }
            }
            // Sections
            if (!is_array($optValues['sections']) || $this->conf['blind.']['sections']) {
                $html = $this->cObj->substituteSubpart($html, '###SELECT_SECTION###', '');
            } else {
                $markerArray['###SELECTBOX_SECTIONS_VALUES###'] = $this->renderSelectBoxValues($this->piVars['sections'], $optValues['sections']);
            }
            // Free Indexing Configurations:
            if (!is_array($optValues['freeIndexUid']) || $this->conf['blind.']['freeIndexUid']) {
                $html = $this->cObj->substituteSubpart($html, '###SELECT_FREEINDEXUID###', '');
            } else {
                $markerArray['###SELECTBOX_FREEINDEXUIDS_VALUES###'] = $this->renderSelectBoxValues($this->piVars['freeIndexUid'], $optValues['freeIndexUid']);
            }
            // Sorting
            if (!is_array($optValues['order']) || !is_array($optValues['desc']) || $this->conf['blind.']['order']) {
                $html = $this->cObj->substituteSubpart($html, '###SELECT_ORDER###', '');
            } else {
                unset($hiddenFieldArr['order']);
                unset($hiddenFieldArr['desc']);
                unset($hiddenFieldArr['results']);
                $markerArray['###SELECTBOX_ORDER_VALUES###'] = $this->renderSelectBoxValues($this->piVars['order'], $optValues['order']);
                $markerArray['###SELECTBOX_DESC_VALUES###'] = $this->renderSelectBoxValues($this->piVars['desc'], $optValues['desc']);
                $markerArray['###SELECTBOX_RESULTS_VALUES###'] = $this->renderSelectBoxValues($this->piVars['results'], $optValues['results']);
            }
            // Limits
            if (!is_array($optValues['results']) || !is_array($optValues['results']) || $this->conf['blind.']['results']) {
                $html = $this->cObj->substituteSubpart($html, '###SELECT_RESULTS###', '');
            } else {
                $markerArray['###SELECTBOX_RESULTS_VALUES###'] = $this->renderSelectBoxValues($this->piVars['results'], $optValues['results']);
            }
            // Grouping
            if (!is_array($optValues['group']) || $this->conf['blind.']['group']) {
                $html = $this->cObj->substituteSubpart($html, '###SELECT_GROUP###', '');
            } else {
                unset($hiddenFieldArr['group']);
                $markerArray['###SELECTBOX_GROUP_VALUES###'] = $this->renderSelectBoxValues($this->piVars['group'], $optValues['group']);
            }
            if ($this->conf['blind.']['extResume']) {
                $html = $this->cObj->substituteSubpart($html, '###SELECT_EXTRESUME###', '');
            } else {
                $markerArray['###EXT_RESUME_CHECKED###'] = $this->piVars['extResume'] ? ' checked="checked"' : '';
            }
        } else {
            // Extended search
            $html = $this->cObj->substituteSubpart($html, '###SEARCH_FORM_EXTENDED###', '');
        }
        if ($this->conf['show.']['advancedSearchLink']) {
            $linkToOtherMode = $this->piVars['ext'] ? $this->pi_getPageLink($this->frontendController->id, $this->frontendController->sPre) : $this->pi_getPageLink($this->frontendController->id, $this->frontendController->sPre, [$this->prefixId . '[ext]' => 1]);
            $markerArray['###LINKTOOTHERMODE###'] = '<a href="' . htmlspecialchars($linkToOtherMode) . '">' . $this->pi_getLL(($this->piVars['ext'] ? 'link_regularSearch' : 'link_advancedSearch'), '', true) . '</a>';
        } else {
            $markerArray['###LINKTOOTHERMODE###'] = '';
        }
        // Write all hidden fields
        $html = $this->cObj->substituteSubpart($html, '###HIDDEN_FIELDS###', implode('', $hiddenFieldArr));
        $substitutedContent = $this->cObj->substituteMarkerArrayCached($html, $markerArray, [], []);
        return $substitutedContent;
    }

    /**
     * Function, rendering selector box values.
     *
     * @param string $value Current value
     * @param array $optValues Array with the options as key=>value pairs
     * @return string <options> imploded.
     */
    public function renderSelectBoxValues($value, $optValues)
    {
        if (!is_array($optValues)) {
            return '';
        }
        $opt = [];
        $isSelFlag = 0;
        foreach ($optValues as $k => $v) {
            $sel = (string)$k === (string)$value ? ' selected="selected"' : '';
            if ($sel) {
                $isSelFlag++;
            }
            $opt[] = '<option value="' . htmlspecialchars($k) . '"' . $sel . '>' . htmlspecialchars($v) . '</option>';
        }
        return implode('', $opt);
    }

    /**
     * Print the searching rules
     *
     * @return string Rules for the search
     */
    public function printRules()
    {
        if (!$this->conf['show.']['rules']) {
            return '';
        }
        $html = $this->cObj->getSubpart($this->templateCode, '###RULES###');
        $markerArray['###RULES_HEADER###'] = $this->pi_getLL('rules_header', '', true);
        $markerArray['###RULES_TEXT###'] = nl2br(trim($this->pi_getLL('rules_text', '', true)));
        $substitutedContent = $this->cObj->substituteMarkerArrayCached($html, $markerArray, [], []);
        return $this->cObj->stdWrap($substitutedContent, $this->conf['rules_stdWrap.']);
    }

    /**
     * Returns the anchor-links to the sections inside the displayed result rows.
     *
     * @return string
     */
    public function printResultSectionLinks()
    {
        if (empty($this->resultSections)) {
            return '';
        }
        $links = [];
        $html = $this->cObj->getSubpart($this->templateCode, '###RESULT_SECTION_LINKS###');
        $item = $this->cObj->getSubpart($this->templateCode, '###RESULT_SECTION_LINKS_LINK###');
        $anchorPrefix = $GLOBALS['TSFE']->baseUrl ? substr(GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'), strlen(GeneralUtility::getIndpEnv('TYPO3_SITE_URL'))) : '';
        foreach ($this->resultSections as $id => $dat) {
            $markerArray = [];
            $aBegin = '<a href="' . htmlspecialchars($anchorPrefix . '#anchor_' . md5($id)) . '">';
            $aContent = (trim($dat[0]) ? trim($dat[0]) : htmlspecialchars($this->pi_getLL('unnamedSection'))) . ' (' . $dat[1] . ' ' . $this->pi_getLL(($dat[1] > 1 ? 'word_pages' : 'word_page'), '', true) . ')';
            $aEnd = '</a>';
            $markerArray['###LINK###'] = $aBegin . $aContent . $aEnd;
            $links[] = $this->cObj->substituteMarkerArrayCached($item, $markerArray, [], []);
        }
        $html = $this->cObj->substituteMarkerArrayCached($html, ['###LINKS###' => implode('', $links)], [], []);
        return '<div' . $this->pi_classParam('sectionlinks') . '>' . $this->cObj->stdWrap($html, $this->conf['sectionlinks_stdWrap.']) . '</div>';
    }

    /**
     * Returns the section header of the search result.
     *
     * @param string $id ID for the section (used for anchor link)
     * @param string $sectionTitleLinked Section title with linked wrapped around
     * @param int $countResultRows Number of results in section
     * @return string HTML output
     */
    public function makeSectionHeader($id, $sectionTitleLinked, $countResultRows)
    {
        $html = $this->cObj->getSubpart($this->templateCode, '###SECTION_HEADER###');
        $markerArray['###ANCHOR_URL###'] = 'anchor_' . md5($id);
        $markerArray['###SECTION_TITLE###'] = $sectionTitleLinked;
        $markerArray['###RESULT_COUNT###'] = $countResultRows;
        $markerArray['###RESULT_NAME###'] = $this->pi_getLL('word_page' . ($countResultRows > 1 ? 's' : ''));
        $substitutedContent = $this->cObj->substituteMarkerArrayCached($html, $markerArray, [], []);
        return $substitutedContent;
    }

    /**
     * This prints a single result row, including a recursive call for subrows.
     *
     * @param array $row Search result row
     * @param int $headerOnly 1=Display only header (for sub-rows!), 2=nothing at all
     * @return string HTML code
     */
    public function printResultRow($row, $headerOnly = 0)
    {
        // Get template content:
        $tmplContent = $this->prepareResultRowTemplateData($row, $headerOnly);
        if ($hookObj = $this->hookRequest('printResultRow')) {
            return $hookObj->printResultRow($row, $headerOnly, $tmplContent);
        } else {
            $html = $this->cObj->getSubpart($this->templateCode, '###RESULT_OUTPUT###');
            if (!is_array($row['_sub'])) {
                $html = $this->cObj->substituteSubpart($html, '###ROW_SUB###', '');
            }
            if (!$headerOnly) {
                $html = $this->cObj->substituteSubpart($html, '###ROW_SHORT###', '');
            } elseif ($headerOnly == 1) {
                $html = $this->cObj->substituteSubpart($html, '###ROW_LONG###', '');
            } elseif ($headerOnly == 2) {
                $html = $this->cObj->substituteSubpart($html, '###ROW_SHORT###', '');
                $html = $this->cObj->substituteSubpart($html, '###ROW_LONG###', '');
            }
            if (is_array($tmplContent)) {
                foreach ($tmplContent as $k => $v) {
                    $markerArray['###' . GeneralUtility::strtoupper($k) . '###'] = $v;
                }
            }
            // Description text
            $markerArray['###TEXT_ITEM_SIZE###'] = $this->pi_getLL('res_size', '', true);
            $markerArray['###TEXT_ITEM_CRDATE###'] = $this->pi_getLL('res_created', '', true);
            $markerArray['###TEXT_ITEM_MTIME###'] = $this->pi_getLL('res_modified', '', true);
            $markerArray['###TEXT_ITEM_PATH###'] = $this->pi_getLL('res_path', '', true);
            $html = $this->cObj->substituteMarkerArrayCached($html, $markerArray, [], []);
            // If there are subrows (eg. subpages in a PDF-file or if a duplicate page is selected due to user-login (phash_grouping))
            if (is_array($row['_sub'])) {
                if ($this->multiplePagesType($row['item_type'])) {
                    $html = str_replace('###TEXT_ROW_SUB###', $this->pi_getLL('res_otherMatching', '', true), $html);
                    foreach ($row['_sub'] as $subRow) {
                        $html .= $this->printResultRow($subRow, 1);
                    }
                } else {
                    $markerArray['###TEXT_ROW_SUB###'] = $this->pi_getLL('res_otherMatching', '', true);
                    $html = str_replace('###TEXT_ROW_SUB###', $this->pi_getLL('res_otherPageAsWell', '', true), $html);
                }
            }
            return $html;
        }
    }

    /**
     * Returns a results browser, former method name: pi_list_browseresults
     *
     * @param bool $showResultCount Show result count
     * @param string $addString String appended to "displaying results..." notice.
     * @param string $addPart String appended after section "displaying results...
     * @param string $freeIndexUid List of integers pointing to free indexing configurations to search. -1 represents no filtering, 0 represents TYPO3 pages only, any number above zero is a uid of an indexing configuration!
     * @return string HTML output
     */
    protected function renderPagination($showResultCount = true, $addString = '', $addPart = '', $freeIndexUid = -1)
    {
        // Initializing variables:
        $pointer = (int)$this->piVars['pointer'];
        $count = (int)$this->internal['res_count'];
        $results_at_a_time = MathUtility::forceIntegerInRange($this->internal['results_at_a_time'], 1, 1000);
        $pageCount = (int)ceil($count / $results_at_a_time);

        $links = [];
        // only show the result browser if more than one page is needed
        if ($pageCount > 1) {
            $maxPages = MathUtility::forceIntegerInRange($this->internal['maxPages'], 1, $pageCount);

            // Make browse-table/links:
            if ($pointer > 0) {
                // all pages after the 1st one
                $links[] = '<li>' . $this->makePointerSelector_link($this->pi_getLL('pi_list_browseresults_prev', '< Previous', true), $pointer - 1, $freeIndexUid) . '</li>';
            }
            $minPage = $pointer - (int)floor($maxPages / 2);
            $maxPage = $minPage + $maxPages - 1;
            // Check if the indexes are within the page limits
            if ($minPage < 0) {
                $maxPage -= $minPage;
                $minPage = 0;
            } elseif ($maxPage >= $pageCount) {
                $minPage -= $maxPage - $pageCount + 1;
                $maxPage = $pageCount - 1;
            }
            $pageLabel = $this->pi_getLL('pi_list_browseresults_page', 'Page', true);
            for ($a = $minPage; $a <= $maxPage; $a++) {
                $label = trim($pageLabel . ' ' . ($a + 1));
                $link = $this->makePointerSelector_link($label, $a, $freeIndexUid);
                if ($a === $pointer) {
                    $links[] = '<li' . $this->pi_classParam('browselist-currentPage') . '><strong>' . $link . '</strong></li>';
                } else {
                    $links[] = '<li>' . $link . '</li>';
                }
            }
            if ($pointer + 1 < $pageCount) {
                $links[] = '<li>' . $this->makePointerSelector_link($this->pi_getLL('pi_list_browseresults_next', 'Next >', true), $pointer + 1, $freeIndexUid) . '</li>';
            }
        }
        if (!empty($links)) {
            $addPart .= '
		<ul class="browsebox">
			' . implode('', $links) . '
		</ul>';
        }
        $label = str_replace(
            ['###TAG_BEGIN###', '###TAG_END###'],
            ['<strong>', '</strong>'],
            $this->pi_getLL('pi_list_browseresults_display', 'Displaying results ###TAG_BEGIN###%1$s to %2$s###TAG_END### out of ###TAG_BEGIN###%3$s###TAG_END###')
        );
        $resultsFrom = $pointer * $results_at_a_time + 1;
        $resultsTo = min($resultsFrom + $results_at_a_time - 1, $count);
        $resultCountText = '';
        if ($showResultCount) {
            $resultCountText = '<p>' . sprintf($label, $resultsFrom, $resultsTo, $count) . $addString . '</p>';
        }
        $sTables = '<div' . $this->pi_classParam('browsebox') . '>'
            . $resultCountText
            . $addPart . '</div>';
        return $sTables;
    }

    /***********************************
     *
     * Support functions for HTML output (with a minimum of fixed markup)
     *
     ***********************************/
    /**
     * Preparing template data for the result row output
     *
     * @param array $row Result row
     * @param bool $headerOnly If set, display only header of result (for sub-results)
     * @return array Array with data to insert in result row template
     */
    public function prepareResultRowTemplateData($row, $headerOnly)
    {
        // Initialize:
        $specRowConf = $this->getSpecialConfigForRow($row);
        $CSSsuffix = $specRowConf['CSSsuffix'] ? '-' . $specRowConf['CSSsuffix'] : '';
        // If external media, link to the media-file instead.
        if ($row['item_type']) {
            // External media
            if ($row['show_resume']) {
                // Can link directly.
                $targetAttribute = '';
                if ($this->frontendController->config['config']['fileTarget']) {
                    $targetAttribute = ' target="' . htmlspecialchars($this->frontendController->config['config']['fileTarget']) . '"';
                }
                $title = '<a href="' . htmlspecialchars($row['data_filename']) . '"' . $targetAttribute . '>' . htmlspecialchars($this->makeTitle($row)) . '</a>';
            } else {
                // Suspicious, so linking to page instead...
                $copy_row = $row;
                unset($copy_row['cHashParams']);
                $title = $this->linkPage($row['page_id'], htmlspecialchars($this->makeTitle($row)), $copy_row);
            }
        } else {
            // Else the page:
            // Prepare search words for markup in content:
            if ($this->conf['forwardSearchWordsInResultLink']) {
                if ($this->conf['forwardSearchWordsInResultLink.']['no_cache']) {
                    $markUpSwParams = ['no_cache' => 1];
                } else {
                    $markUpSwParams = [];
                }
                foreach ($this->sWArr as $d) {
                    $markUpSwParams['sword_list'][] = $d['sword'];
                }
            } else {
                $markUpSwParams = [];
            }
            $title = $this->linkPage($row['data_page_id'], htmlspecialchars($this->makeTitle($row)), $row, $markUpSwParams);
        }
        $tmplContent = [];
        $tmplContent['title'] = $title;
        $tmplContent['result_number'] = $this->conf['show.']['resultNumber'] ? $row['result_number'] . ': ' : '&nbsp;';
        $tmplContent['icon'] = $this->makeItemTypeIcon($row['item_type'], '', $specRowConf);
        $tmplContent['rating'] = $this->makeRating($row);
        $tmplContent['description'] = $this->makeDescription(
            $row,
            !($this->piVars['extResume'] && !$headerOnly),
            $this->conf['results.']['summaryCropAfter']
        );
        $tmplContent = $this->makeInfo($row, $tmplContent);
        $tmplContent['access'] = $this->makeAccessIndication($row['page_id']);
        $tmplContent['language'] = $this->makeLanguageIndication($row);
        $tmplContent['CSSsuffix'] = $CSSsuffix;
        // Post processing with hook.
        if ($hookObj = $this->hookRequest('prepareResultRowTemplateData_postProc')) {
            $tmplContent = $hookObj->prepareResultRowTemplateData_postProc($tmplContent, $row, $headerOnly);
        }
        return $tmplContent;
    }

    /**
     * Returns a string that tells which search words are searched for.
     *
     * @param array Array of search words
     * @return string HTML telling what is searched for.
     */
    public function tellUsWhatIsSeachedFor($sWArr)
    {
        // Init:
        $searchingFor = '';
        $c = 0;
        // Traverse search words:
        foreach ($sWArr as $k => $v) {
            if ($c) {
                switch ($v['oper']) {
                    case 'OR':
                        $searchingFor .= ' ' . $this->pi_getLL('searchFor_or', '', true) . ' ' . $this->wrapSW($this->utf8_to_currentCharset($v['sword']));
                        break;
                    case 'AND NOT':
                        $searchingFor .= ' ' . $this->pi_getLL('searchFor_butNot', '', true) . ' ' . $this->wrapSW($this->utf8_to_currentCharset($v['sword']));
                        break;
                    default:
                        // AND...
                        $searchingFor .= ' ' . $this->pi_getLL('searchFor_and', '', true) . ' ' . $this->wrapSW($this->utf8_to_currentCharset($v['sword']));
                }
            } else {
                $searchingFor = $this->pi_getLL('searchFor', '', true) . ' ' . $this->wrapSW($this->utf8_to_currentCharset($v['sword']));
            }
            $c++;
        }
        return $searchingFor;
    }

    /**
     * Wraps the search words in the search-word list display (from ->tellUsWhatIsSeachedFor())
     *
     * @param string $str search word to wrap (in local charset!)
     * @return string Search word wrapped in <span> tag.
     */
    public function wrapSW($str)
    {
        return '"<span' . $this->pi_classParam('sw') . '>' . htmlspecialchars($str) . '</span>"';
    }

    /**
     * Makes a selector box
     *
     * @param string $name Name of selector box
     * @param string $value Current value
     * @param array $optValues Array of options in the selector box (value => label pairs)
     * @return string HTML of selector box
     */
    public function renderSelectBox($name, $value, $optValues)
    {
        if (is_array($optValues)) {
            $opt = [];
            $isSelFlag = 0;
            foreach ($optValues as $k => $v) {
                $sel = (string)$k === (string)$value ? ' selected="selected"' : '';
                if ($sel) {
                    $isSelFlag++;
                }
                $opt[] = '<option value="' . htmlspecialchars($k) . '"' . $sel . '>' . htmlspecialchars($v) . '</option>';
            }
            return '<select name="' . $name . '">' . implode('', $opt) . '</select>';
        }
    }

    /**
     * Used to make the link for the result-browser.
     * Notice how the links must resubmit the form after setting the new pointer-value in a hidden formfield.
     *
     * @param string $str String to wrap in <a> tag
     * @param int $p Pointer value
     * @param string $freeIndexUid List of integers pointing to free indexing configurations to search. -1 represents no filtering, 0 represents TYPO3 pages only, any number above zero is a uid of an indexing configuration!
     * @return string Input string wrapped in <a> tag with onclick event attribute set.
     */
    public function makePointerSelector_link($str, $p, $freeIndexUid)
    {
        $onclick = 'document.getElementById(' . GeneralUtility::quoteJSvalue($this->prefixId . '_pointer') . ').value=' . GeneralUtility::quoteJSvalue($p) . ';' . 'document.getElementById(' . GeneralUtility::quoteJSvalue($this->prefixId . '_freeIndexUid') . ').value=' . GeneralUtility::quoteJSvalue($freeIndexUid) . ';' . 'document.getElementById(' . GeneralUtility::quoteJSvalue($this->prefixId) . ').submit();return false;';
        return '<a href="#" onclick="' . htmlspecialchars($onclick) . '">' . $str . '</a>';
    }

    /**
     * Return icon for file extension
     *
     * @param string $it File extension / item type
     * @param string $alt Title attribute value in icon.
     * @param array $specRowConf TypoScript configuration specifically for search result.
     * @return string <img> tag for icon
     */
    public function makeItemTypeIcon($it, $alt = '', $specRowConf)
    {
        // Build compound key if item type is 0, iconRendering is not used
        // and specConfs.[pid].pageIcon was set in TS
        if ($it === '0' && $specRowConf['_pid'] && is_array($specRowConf['pageIcon.']) && !is_array($this->conf['iconRendering.'])) {
            $it .= ':' . $specRowConf['_pid'];
        }
        if (!isset($this->iconFileNameCache[$it])) {
            $this->iconFileNameCache[$it] = '';
            // If TypoScript is used to render the icon:
            if (is_array($this->conf['iconRendering.'])) {
                $this->cObj->setCurrentVal($it);
                $this->iconFileNameCache[$it] = $this->cObj->cObjGetSingle($this->conf['iconRendering'], $this->conf['iconRendering.']);
            } else {
                // Default creation / finding of icon:
                $icon = '';
                if ($it === '0' || substr($it, 0, 2) == '0:') {
                    if (is_array($specRowConf['pageIcon.'])) {
                        $this->iconFileNameCache[$it] = $this->cObj->cObjGetSingle('IMAGE', $specRowConf['pageIcon.']);
                    } else {
                        $icon = 'EXT:indexed_search/Resources/Public/Icons/FileTypes/pages.gif';
                    }
                } elseif ($this->external_parsers[$it]) {
                    $icon = $this->external_parsers[$it]->getIcon($it);
                }
                if ($icon) {
                    $fullPath = GeneralUtility::getFileAbsFileName($icon);
                    if ($fullPath) {
                        $info = @getimagesize($fullPath);
                        $iconPath = \TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix($fullPath);
                        $this->iconFileNameCache[$it] = is_array($info) ? '<img src="' . $iconPath . '" ' . $info[3] . ' title="' . htmlspecialchars($alt) . '" alt="" />' : '';
                    }
                }
            }
        }
        return $this->iconFileNameCache[$it];
    }

    /**
     * Return the rating-HTML code for the result row. This makes use of the $this->firstRow
     *
     * @param array $row Result row array
     * @return string String showing ranking value
     */
    public function makeRating($row)
    {
        switch ((string)$this->piVars['order']) {
            case 'rank_count':
                // Number of occurencies on page
                return $row['order_val'] . ' ' . $this->pi_getLL('maketitle_matches');
                break;
            case 'rank_first':
                // Close to top of page
                return ceil(MathUtility::forceIntegerInRange((255 - $row['order_val']), 1, 255) / 255 * 100) . '%';
                break;
            case 'rank_flag':
                // Based on priority assigned to <title> / <meta-keywords> / <meta-description> / <body>
                if ($this->firstRow['order_val2']) {
                    $base = $row['order_val1'] * 256;
                    // (3 MSB bit, 224 is highest value of order_val1 currently)
                    $freqNumber = $row['order_val2'] / $this->firstRow['order_val2'] * pow(2, 12);
                    // 15-3 MSB = 12
                    $total = MathUtility::forceIntegerInRange($base + $freqNumber, 0, 32767);
                    return ceil(log($total) / log(32767) * 100) . '%';
                }
                break;
            case 'rank_freq':
                // Based on frequency
                $max = 10000;
                $total = MathUtility::forceIntegerInRange($row['order_val'], 0, $max);
                return ceil(log($total) / log($max) * 100) . '%';
                break;
            case 'crdate':
                // Based on creation date
                return $this->cObj->calcAge($GLOBALS['EXEC_TIME'] - $row['item_crdate'], 0);
                break;
            case 'mtime':
                // Based on modification time
                return $this->cObj->calcAge($GLOBALS['EXEC_TIME'] - $row['item_mtime'], 0);
                break;
            default:
                // fx. title
                return '&nbsp;';
        }
    }

    /**
     * Returns the resume for the search-result.
     *
     * @param array $row Search result row
     * @param bool $noMarkup If noMarkup is FALSE, then the index_fulltext table is used to select the content of the page, split it with regex to display the search words in the text.
     * @param int $lgd String length
     * @return string HTML string
     */
    public function makeDescription($row, $noMarkup = false, $lgd = 180)
    {
        if ($row['show_resume']) {
            $markedSW = '';
            $outputStr = '';
            if (!$noMarkup) {
                if (\TYPO3\CMS\IndexedSearch\Utility\IndexedSearchUtility::isTableUsed('index_fulltext')) {
                    $res = $this->databaseConnection->exec_SELECTquery('*', 'index_fulltext', 'phash=' . (int)$row['phash']);
                } else {
                    $res = false;
                }
                if ($res) {
                    if ($ftdrow = $this->databaseConnection->sql_fetch_assoc($res)) {
                        // Cut HTTP references after some length
                        $content = preg_replace('/(http:\\/\\/[^ ]{' . $this->conf['results.']['hrefInSummaryCropAfter'] . '})([^ ]+)/i', '$1...', $ftdrow['fulltextdata']);
                        $markedSW = $this->markupSWpartsOfString($content);
                    }
                    $this->databaseConnection->sql_free_result($res);
                }
            }
            if (!trim($markedSW)) {
                $outputStr = $this->frontendController->csConvObj->crop('utf-8', $row['item_description'], $lgd, $this->conf['results.']['summaryCropSignifier']);
                $outputStr = htmlspecialchars($outputStr);
            }
            $output = $this->utf8_to_currentCharset($outputStr ?: $markedSW);
        } else {
            $output = '<span class="noResume">' . $this->pi_getLL('res_noResume', '', true) . '</span>';
        }
        return $output;
    }

    /**
     * Marks up the search words from $this->sWarr in the $str with a color.
     *
     * @param string $str Text in which to find and mark up search words. This text is assumed to be UTF-8 like the search words internally is.
     * @return string Processed content.
     */
    public function markupSWpartsOfString($str)
    {
        $htmlParser = GeneralUtility::makeInstance(HtmlParser::class);
        // Init:
        $str = str_replace('&nbsp;', ' ', $htmlParser->bidir_htmlspecialchars($str, -1));
        $str = preg_replace('/\\s\\s+/', ' ', $str);
        $swForReg = [];
        // Prepare search words for regex:
        foreach ($this->sWArr as $d) {
            $swForReg[] = preg_quote($d['sword'], '/');
        }
        $regExString = '(' . implode('|', $swForReg) . ')';
        // Split and combine:
        $parts = preg_split('/' . $regExString . '/ui', ' ' . $str . ' ', 20000, PREG_SPLIT_DELIM_CAPTURE);
        // Constants:
        $summaryMax = $this->conf['results.']['markupSW_summaryMax'];
        $postPreLgd = $this->conf['results.']['markupSW_postPreLgd'];
        $postPreLgd_offset = $this->conf['results.']['markupSW_postPreLgd_offset'];
        $divider = $this->conf['results.']['markupSW_divider'];
        $occurencies = (count($parts) - 1) / 2;
        if ($occurencies) {
            $postPreLgd = MathUtility::forceIntegerInRange($summaryMax / $occurencies, $postPreLgd, $summaryMax / 2);
        }
        // Variable:
        $summaryLgd = 0;
        $output = [];
        // Shorten in-between strings:
        foreach ($parts as $k => $strP) {
            if ($k % 2 == 0) {
                // Find length of the summary part:
                $strLen = $this->frontendController->csConvObj->strlen('utf-8', $parts[$k]);
                $output[$k] = $parts[$k];
                // Possibly shorten string:
                if (!$k) {
                    // First entry at all (only cropped on the frontside)
                    if ($strLen > $postPreLgd) {
                        $output[$k] = $divider . preg_replace('/^[^[:space:]]+[[:space:]]/', '', $this->frontendController->csConvObj->crop('utf-8', $parts[$k], -($postPreLgd - $postPreLgd_offset)));
                    }
                } elseif ($summaryLgd > $summaryMax || !isset($parts[$k + 1])) {
                    // In case summary length is exceed OR if there are no more entries at all:
                    if ($strLen > $postPreLgd) {
                        $output[$k] = preg_replace('/[[:space:]][^[:space:]]+$/', '', $this->frontendController->csConvObj->crop('utf-8', $parts[$k], ($postPreLgd - $postPreLgd_offset))) . $divider;
                    }
                } else {
                    // In-between search words:
                    if ($strLen > $postPreLgd * 2) {
                        $output[$k] = preg_replace('/[[:space:]][^[:space:]]+$/', '', $this->frontendController->csConvObj->crop('utf-8', $parts[$k], ($postPreLgd - $postPreLgd_offset))) . $divider . preg_replace('/^[^[:space:]]+[[:space:]]/', '', $this->frontendController->csConvObj->crop('utf-8', $parts[$k], -($postPreLgd - $postPreLgd_offset)));
                    }
                }
                $summaryLgd += $this->frontendController->csConvObj->strlen('utf-8', $output[$k]);
                // Protect output:
                $output[$k] = htmlspecialchars($output[$k]);
                // If summary lgd is exceed, break the process:
                if ($summaryLgd > $summaryMax) {
                    break;
                }
            } else {
                $summaryLgd += $this->frontendController->csConvObj->strlen('utf-8', $strP);
                $output[$k] = '<strong class="tx-indexedsearch-redMarkup">' . htmlspecialchars($parts[$k]) . '</strong>';
            }
        }
        // Return result:
        return implode('', $output);
    }

    /**
     * Returns the title of the search result row
     *
     * @param array $row Result row
     * @return string Title from row
     */
    public function makeTitle($row)
    {
        $add = '';
        if ($this->multiplePagesType($row['item_type'])) {
            $dat = unserialize($row['cHashParams']);
            $pp = explode('-', $dat['key']);
            if ($pp[0] != $pp[1]) {
                $add = ', ' . $this->pi_getLL('word_pages') . ' ' . $dat['key'];
            } else {
                $add = ', ' . $this->pi_getLL('word_page') . ' ' . $pp[0];
            }
        }
        $outputString = $this->frontendController->csConvObj->crop('utf-8', $row['item_title'], $this->conf['results.']['titleCropAfter'], $this->conf['results.']['titleCropSignifier']);
        return $this->utf8_to_currentCharset($outputString) . $add;
    }

    /**
     * Returns the info-string in the bottom of the result-row display (size, dates, path)
     *
     * @param array $row Result row
     * @param array $tmplArray Template array to modify
     * @return array Modified template array
     */
    public function makeInfo($row, $tmplArray)
    {
        $tmplArray['size'] = GeneralUtility::formatSize($row['item_size']);
        $tmplArray['created'] = $this->formatCreatedDate($row['item_crdate']);
        $tmplArray['modified'] = $this->formatModifiedDate($row['item_mtime']);
        $pathId = $row['data_page_id'] ?: $row['page_id'];
        $pathMP = $row['data_page_id'] ? $row['data_page_mp'] : '';
        $pI = parse_url($row['data_filename']);
        if ($pI['scheme']) {
            $targetAttribute = '';
            if ($this->frontendController->config['config']['fileTarget']) {
                $targetAttribute = ' target="' . htmlspecialchars($this->frontendController->config['config']['fileTarget']) . '"';
            }
            $tmplArray['path'] = '<a href="' . htmlspecialchars($row['data_filename']) . '"' . $targetAttribute . '>' . htmlspecialchars($row['data_filename']) . '</a>';
        } else {
            $pathStr = $this->getPathFromPageId($pathId, $pathMP);
            $tmplArray['path'] = $this->linkPage($pathId, $pathStr, [
                'cHashParams' => $row['cHashParams'],
                'data_page_type' => $row['data_page_type'],
                'data_page_mp' => $pathMP,
                'sys_language_uid' => $row['sys_language_uid']
            ]);
        }
        return $tmplArray;
    }

    /**
     * Returns configuration from TypoScript for result row based on ID / location in page tree!
     *
     * @param array $row Result row
     * @return array Configuration array
     */
    public function getSpecialConfigForRow($row)
    {
        $pathId = $row['data_page_id'] ?: $row['page_id'];
        $pathMP = $row['data_page_id'] ? $row['data_page_mp'] : '';
        $rl = $this->getRootLine($pathId, $pathMP);
        $specConf = $this->conf['specConfs.']['0.'];
        if (is_array($rl)) {
            foreach ($rl as $dat) {
                if (is_array($this->conf['specConfs.'][$dat['uid'] . '.'])) {
                    $specConf = $this->conf['specConfs.'][$dat['uid'] . '.'];
                    $specConf['_pid'] = $dat['uid'];
                    break;
                }
            }
        }
        return $specConf;
    }

    /**
     * Returns the HTML code for language indication.
     *
     * @param array $row Result row
     * @return string HTML code for result row.
     */
    public function makeLanguageIndication($row)
    {
        // If search result is a TYPO3 page:
        if ((string)$row['item_type'] === '0') {
            // If TypoScript is used to render the flag:
            if (is_array($this->conf['flagRendering.'])) {
                $this->cObj->setCurrentVal($row['sys_language_uid']);
                return $this->cObj->cObjGetSingle($this->conf['flagRendering'], $this->conf['flagRendering.']);
            }
        }
        return '&nbsp;';
    }

    /**
     * Returns the HTML code for the locking symbol.
     * NOTICE: Requires a call to ->getPathFromPageId() first in order to work (done in ->makeInfo() by calling that first)
     *
     * @param int $id Page id for which to find answer
     * @return string <img> tag if access is limited.
     */
    public function makeAccessIndication($id)
    {
        if (is_array($this->fe_groups_required[$id]) && !empty($this->fe_groups_required[$id])) {
            return '<img src="' . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('indexed_search') . 'Resources/Public/Icons/FileTypes/locked.gif" width="12" height="15" vspace="5" title="' . sprintf($this->pi_getLL('res_memberGroups', '', true), implode(',', array_unique($this->fe_groups_required[$id]))) . '" alt="" />';
        }

        return '';
    }

    /**
     * Links the $str to page $id
     *
     * @param int $id Page id
     * @param string $str Title String to link
     * @param array $row Result row
     * @param array $markUpSwParams Additional parameters for marking up seach words
     * @return string <A> tag wrapped title string.
     */
    public function linkPage($id, $str, $row = [], $markUpSwParams = [])
    {
        // Parameters for link:
        $urlParameters = (array)unserialize($row['cHashParams']);
        // Add &type and &MP variable:
        if ($row['data_page_type']) {
            $urlParameters['type'] = $row['data_page_type'];
        }
        if ($row['data_page_mp']) {
            $urlParameters['MP'] = $row['data_page_mp'];
        }
        $urlParameters['L'] = intval($row['sys_language_uid']);
        // markup-GET vars:
        $urlParameters = array_merge($urlParameters, $markUpSwParams);
        // This will make sure that the path is retrieved if it hasn't been already. Used only for the sake of the domain_record thing...
        if (!is_array($this->domain_records[$id])) {
            $this->getPathFromPageId($id);
        }
        // If external domain, then link to that:
        if (!empty($this->domain_records[$id])) {
            reset($this->domain_records[$id]);
            $firstDom = current($this->domain_records[$id]);
            $scheme = GeneralUtility::getIndpEnv('TYPO3_SSL') ? 'https://' : 'http://';
            $addParams = '';
            if (is_array($urlParameters) && !empty($urlParameters)) {
                $addParams .= GeneralUtility::implodeArrayForUrl('', $urlParameters);
            }
            if ($target = $this->conf['search.']['detect_sys_domain_records.']['target']) {
                $target = ' target="' . $target . '"';
            }
            return '<a href="' . htmlspecialchars(($scheme . $firstDom . '/index.php?id=' . $id . $addParams)) . '"' . $target . '>' . htmlspecialchars($str) . '</a>';
        } else {
            return $this->pi_linkToPage($str, $id, $this->conf['result_link_target'], $urlParameters);
        }
    }

    /**
     * Returns the path to the page $id
     *
     * @param int $id Page ID
     * @param string $pathMP MP variable content.
     * @return string Root line for result.
     */
    public function getRootLine($id, $pathMP = '')
    {
        $identStr = $id . '|' . $pathMP;
        if (!isset($this->cache_path[$identStr])) {
            $this->cache_rl[$identStr] = $this->frontendController->sys_page->getRootLine($id, $pathMP);
        }
        return $this->cache_rl[$identStr];
    }

    /**
     * Gets the first sys_domain record for the page, $id
     *
     * @param int $id Page id
     * @return string Domain name
     */
    public function getFirstSysDomainRecordForPage($id)
    {
        $res = $this->databaseConnection->exec_SELECTquery('domainName', 'sys_domain', 'pid=' . (int)$id . $this->cObj->enableFields('sys_domain'), '', 'sorting');
        $row = $this->databaseConnection->sql_fetch_assoc($res);
        return rtrim($row['domainName'], '/');
    }

    /**
     * Returns the path to the page $id
     *
     * @param int $id Page ID
     * @param string $pathMP MP variable content
     * @return string Path (HTML-escaped)
     */
    public function getPathFromPageId($id, $pathMP = '')
    {
        $identStr = $id . '|' . $pathMP;
        if (!isset($this->cache_path[$identStr])) {
            $this->fe_groups_required[$id] = [];
            $this->domain_records[$id] = [];
            $rl = $this->getRootLine($id, $pathMP);
            $path = '';
            $pageCount = count($rl);
            if (is_array($rl) && !empty($rl)) {
                $index = 0;
                $breadcrumbWrap = isset($this->conf['breadcrumbWrap']) ? $this->conf['breadcrumbWrap'] : '/';
                $breadcrumbWraps = $GLOBALS['TSFE']->tmpl->splitConfArray(['wrap' => $breadcrumbWrap], $pageCount);
                foreach ($rl as $k => $v) {
                    // Check fe_user
                    if ($v['fe_group'] && ($v['uid'] == $id || $v['extendToSubpages'])) {
                        $this->fe_groups_required[$id][] = $v['fe_group'];
                    }
                    // Check sys_domain.
                    if ($this->conf['search.']['detect_sys_domain_records']) {
                        $sysDName = $this->getFirstSysDomainRecordForPage($v['uid']);
                        if ($sysDName) {
                            $this->domain_records[$id][] = $sysDName;
                            // Set path accordingly:
                            $path = $sysDName . $path;
                            break;
                        }
                    }
                    // Stop, if we find that the current id is the current root page.
                    if ($v['uid'] == $this->frontendController->config['rootLine'][0]['uid']) {
                        array_pop($breadcrumbWraps);
                        break;
                    }
                    $path = $this->cObj->wrap(htmlspecialchars($v['title']), array_pop($breadcrumbWraps)['wrap']) . $path;
                }
            }
            $this->cache_path[$identStr] = $path;
            if (is_array($this->conf['path_stdWrap.'])) {
                $this->cache_path[$identStr] = $this->cObj->stdWrap($this->cache_path[$identStr], $this->conf['path_stdWrap.']);
            }
        }
        return $this->cache_path[$identStr];
    }

    /**
     * Return the menu of pages used for the selector.
     *
     * @param int $id Page ID for which to return menu
     * @return array Menu items (for making the section selector box)
     */
    public function getMenu($id)
    {
        if ($this->conf['show.']['LxALLtypes']) {
            $output = [];
            $res = $this->databaseConnection->exec_SELECTquery('title,uid', 'pages', 'pid=' . (int)$id . $this->cObj->enableFields('pages'), '', 'sorting');
            while ($row = $this->databaseConnection->sql_fetch_assoc($res)) {
                $output[$row['uid']] = $this->frontendController->sys_page->getPageOverlay($row);
            }
            $this->databaseConnection->sql_free_result($res);
            return $output;
        } else {
            return $this->frontendController->sys_page->getMenu($id);
        }
    }

    /**
     * Returns if an item type is a multipage item type
     *
     * @param string $item_type Item type
     * @return bool TRUE if multipage capable
     */
    public function multiplePagesType($item_type)
    {
        return is_object($this->external_parsers[$item_type]) && $this->external_parsers[$item_type]->isMultiplePageExtension($item_type);
    }

    /**
     * Converts the input string from utf-8 to the backend charset.
     *
     * @param string String to convert (utf-8)
     * @return string Converted string (backend charset if different from utf-8)
     */
    public function utf8_to_currentCharset($str)
    {
        return $this->frontendController->csConv($str, 'utf-8');
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
    }

    /**
     * Obtains the URL of the search target page
     *
     * @return string
     */
    protected function getSearchFormActionURL()
    {
        $targetUrlPid = $this->getSearchFormActionPidFromTS();
        if ($targetUrlPid == 0) {
            $targetUrlPid = $this->frontendController->id;
        }
        return $this->pi_getPageLink($targetUrlPid, $this->frontendController->sPre);
    }

    /**
     * Obtains search form target pid from the TypoScript configuration
     *
     * @return int
     */
    protected function getSearchFormActionPidFromTS()
    {
        $result = 0;
        if (isset($this->conf['search.']['targetPid']) || isset($this->conf['search.']['targetPid.'])) {
            if (is_array($this->conf['search.']['targetPid.'])) {
                $result = $this->cObj->stdWrap($this->conf['search.']['targetPid'], $this->conf['search.']['targetPid.']);
            } else {
                $result = $this->conf['search.']['targetPid'];
            }
            $result = (int)$result;
        }
        return $result;
    }

    /**
     * Formats date as 'created' date
     *
     * @param int $date
     * @return string
     */
    protected function formatCreatedDate($date)
    {
        $defaultFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'];
        return $this->formatDate($date, 'created', $defaultFormat);
    }

    /**
     * Formats date as 'modified' date
     *
     * @param int $date
     * @return string
     */
    protected function formatModifiedDate($date)
    {
        $defaultFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] . ' ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'];
        return $this->formatDate($date, 'modified', $defaultFormat);
    }

    /**
     * Formats the date using format string from TypoScript or default format
     * if TypoScript format is not set
     *
     * @param int $date
     * @param string $tsKey
     * @param string $defaultFormat
     * @return string
     */
    protected function formatDate($date, $tsKey, $defaultFormat)
    {
        $strftimeFormat = $this->conf['dateFormat.'][$tsKey];
        if ($strftimeFormat) {
            $result = strftime($strftimeFormat, $date);
        } else {
            $result = date($defaultFormat, $date);
        }
        return $result;
    }

    /**
     * Search type
     * e.g. sentence (20), any part of the word (1)
     *
     * @return int
     */
    public function getSearchType()
    {
        return (int)$this->piVars['type'];
    }

    /**
     * A list of integer which should be root-pages to search from
     *
     * @return int[]
     */
    public function getSearchRootPageIdList()
    {
        return \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $this->wholeSiteIdList);
    }

    /**
     * Getter for join_pages flag
     * enabled through $this->conf['search.']['skipExtendToSubpagesChecking']
     *
     * @return bool
     */
    public function getJoinPagesForQuery()
    {
        return (bool)$this->join_pages;
    }

    /**
     * Load settings and apply stdWrap to them
     */
    protected function loadSettings()
    {
        if (!is_array($this->conf['results.'])) {
            $this->conf['results.'] = [];
        }
        $this->conf['results.']['summaryCropAfter'] = MathUtility::forceIntegerInRange(
            $this->cObj->stdWrap($this->conf['results.']['summaryCropAfter'], $this->conf['results.']['summaryCropAfter.']),
            10, 5000, 180
        );
        $this->conf['results.']['summaryCropSignifier'] = $this->cObj->stdWrap($this->conf['results.']['summaryCropSignifier'], $this->conf['results.']['summaryCropSignifier.']);
        $this->conf['results.']['titleCropAfter'] = MathUtility::forceIntegerInRange(
            $this->cObj->stdWrap($this->conf['results.']['titleCropAfter'], $this->conf['results.']['titleCropAfter.']),
            10, 500, 50
        );
        $this->conf['results.']['titleCropSignifier'] = $this->cObj->stdWrap($this->conf['results.']['titleCropSignifier'], $this->conf['results.']['titleCropSignifier.']);
        $this->conf['results.']['markupSW_summaryMax'] = MathUtility::forceIntegerInRange(
            $this->cObj->stdWrap($this->conf['results.']['markupSW_summaryMax'], $this->conf['results.']['markupSW_summaryMax.']),
            10, 5000, 300
        );
        $this->conf['results.']['markupSW_postPreLgd'] = MathUtility::forceIntegerInRange(
            $this->cObj->stdWrap($this->conf['results.']['markupSW_postPreLgd'], $this->conf['results.']['markupSW_postPreLgd.']),
            1, 500, 60
        );
        $this->conf['results.']['markupSW_postPreLgd_offset'] = MathUtility::forceIntegerInRange(
            $this->cObj->stdWrap($this->conf['results.']['markupSW_postPreLgd_offset'], $this->conf['results.']['markupSW_postPreLgd_offset.']),
            1, 50, 5
        );
        $this->conf['results.']['markupSW_divider'] = $this->cObj->stdWrap($this->conf['results.']['markupSW_divider'], $this->conf['results.']['markupSW_divider.']);
        $this->conf['results.']['hrefInSummaryCropAfter'] = MathUtility::forceIntegerInRange(
            $this->cObj->stdWrap($this->conf['results.']['hrefInSummaryCropAfter'], $this->conf['results.']['hrefInSummaryCropAfter.']),
            10, 400, 60
        );
        $this->conf['results.']['hrefInSummaryCropSignifier'] = $this->cObj->stdWrap($this->conf['results.']['hrefInSummaryCropSignifier'], $this->conf['results.']['hrefInSummaryCropSignifier.']);
    }
}
