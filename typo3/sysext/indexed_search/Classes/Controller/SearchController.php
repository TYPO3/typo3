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

namespace TYPO3\CMS\IndexedSearch\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Exception\Page\RootLineException;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Html\HtmlParser;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\File\ImageInfo;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\IndexedSearch\Domain\Repository\IndexSearchRepository;
use TYPO3\CMS\IndexedSearch\Lexer;
use TYPO3\CMS\IndexedSearch\Utility\IndexedSearchUtility;

/**
 * Index search frontend
 *
 * Creates a search form for indexed search. Indexing must be enabled
 * for this to make sense.
 * @internal This class is a specific controller implementation and is not considered part of the Public TYPO3 API.
 */
class SearchController extends ActionController
{
    /**
     * previously known as $this->piVars['sword']
     *
     * @var string
     */
    protected $sword = '';

    /**
     * @var array
     */
    protected $searchWords = [];

    /**
     * @var array
     */
    protected $searchData;

    /**
     * This is the id of the site root.
     * This value may be a comma separated list of integer (prepared for this)
     * Root-page PIDs to search in (rl0 field where clause, see initialize() function)
     *
     * If this value is set to less than zero (eg. -1) searching will happen
     * in ALL of the page tree with no regard to branches at all.
     * @var int|string
     */
    protected $searchRootPageIdList = 0;

    /**
     * @var int
     */
    protected $defaultResultNumber = 10;

    /**
     * @var int[]
     */
    protected $availableResultsNumbers = [];

    /**
     * Search repository
     *
     * @var \TYPO3\CMS\IndexedSearch\Domain\Repository\IndexSearchRepository
     */
    protected $searchRepository;

    /**
     * Lexer object
     *
     * @var \TYPO3\CMS\IndexedSearch\Lexer
     */
    protected $lexerObj;

    /**
     * External parser objects
     * @var array
     */
    protected $externalParsers = [];

    /**
     * Will hold the first row in result - used to calculate relative hit-ratings.
     *
     * @var array
     */
    protected $firstRow = [];

    /**
     * @todo remove
     * sys_domain records
     *
     * @var array
     */
    protected $domainRecords = [];

    /**
     * Required fe_groups memberships for display of a result.
     *
     * @var array
     */
    protected $requiredFrontendUsergroups = [];

    /**
     * Page tree sections for search result.
     *
     * @var array
     */
    protected $resultSections = [];

    /**
     * Caching of page path
     *
     * @var array
     */
    protected $pathCache = [];

    /**
     * Storage of icons
     *
     * @var array
     */
    protected $iconFileNameCache = [];

    /**
     * Indexer configuration, coming from TYPO3's system configuration for EXT:indexed_search
     *
     * @var array
     */
    protected $indexerConfig = [];

    /**
     * Flag whether metaphone search should be enabled
     *
     * @var bool
     */
    protected $enableMetaphoneSearch = false;

    /**
     * @var \TYPO3\CMS\Core\TypoScript\TypoScriptService
     */
    protected $typoScriptService;

    /**
     * @param \TYPO3\CMS\Core\TypoScript\TypoScriptService $typoScriptService
     */
    public function injectTypoScriptService(TypoScriptService $typoScriptService)
    {
        $this->typoScriptService = $typoScriptService;
    }

    /**
     * sets up all necessary object for searching
     *
     * @param array $searchData The incoming search parameters
     * @return array Search parameters
     */
    public function initialize($searchData = [])
    {
        if (!is_array($searchData)) {
            $searchData = [];
        }

        // check if TypoScript is loaded
        if (!isset($this->settings['results'])) {
            $this->redirect('noTypoScript');
        }

        // Sets availableResultsNumbers - has to be called before request settings are read to avoid DoS attack
        $this->availableResultsNumbers = array_filter(GeneralUtility::intExplode(',', $this->settings['blind']['numberOfResults']));

        // Sets default result number if at least one availableResultsNumbers exists
        if (isset($this->availableResultsNumbers[0])) {
            $this->defaultResultNumber = $this->availableResultsNumbers[0];
        }

        $this->loadSettings();

        // setting default values
        if (is_array($this->settings['defaultOptions'])) {
            $searchData = array_merge($this->settings['defaultOptions'], $searchData);
        }
        // if "languageUid" was set to "current", take the current site language
        if (($searchData['languageUid'] ?? '') === 'current') {
            $searchData['languageUid'] = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('language', 'id', 0);
        }

        // Indexer configuration from Extension Manager interface:
        $this->indexerConfig = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('indexed_search');
        $this->enableMetaphoneSearch = (bool)$this->indexerConfig['enableMetaphoneSearch'];
        $this->initializeExternalParsers();
        // If "_sections" is set, this value overrides any existing value.
        if ($searchData['_sections'] ?? false) {
            $searchData['sections'] = $searchData['_sections'];
        }
        // If "_sections" is set, this value overrides any existing value.
        if (($searchData['_freeIndexUid'] ?? '') !== '' && ($searchData['_freeIndexUid'] ?? '') !== '_') {
            $searchData['freeIndexUid'] = $searchData['_freeIndexUid'];
        }
        $searchData['numberOfResults'] = $this->getNumberOfResults($searchData['numberOfResults'] ?? 0);
        // This gets the search-words into the $searchWordArray
        $this->setSword($searchData['sword'] ?? '');
        // Add previous search words to current
        if (($searchData['sword_prev_include'] ?? false) && ($searchData['sword_prev'] ?? false)) {
            $this->setSword(trim($searchData['sword_prev']) . ' ' . $this->getSword());
        }
        // This is the id of the site root.
        // This value may be a commalist of integer (prepared for this)
        $this->searchRootPageIdList = (int)$GLOBALS['TSFE']->config['rootLine'][0]['uid'];
        // Setting the list of root PIDs for the search. Notice, these page IDs MUST
        // have a TypoScript template with root flag on them! Basically this list is used
        // to select on the "rl0" field and page ids are registered as "rl0" only if
        // a TypoScript template record with root flag is there.
        // This happens AFTER the use of $this->searchRootPageIdList above because
        // the above will then fetch the menu for the CURRENT site - regardless
        // of this kind of searching here. Thus a general search will lookup in
        // the WHOLE database while a specific section search will take the current sections.
        if ($this->settings['rootPidList']) {
            $this->searchRootPageIdList = implode(',', GeneralUtility::intExplode(',', $this->settings['rootPidList']));
        }
        $this->searchRepository = GeneralUtility::makeInstance(IndexSearchRepository::class);
        $this->searchRepository->initialize($this->settings, $searchData, $this->externalParsers, $this->searchRootPageIdList);
        $this->searchData = $searchData;
        // $this->searchData is used in $this->getSearchWords
        $this->searchWords = $this->getSearchWords($searchData['defaultOperand']);
        // Calling hook for modification of initialized content
        if ($hookObj = $this->hookRequest('initialize_postProc')) {
            $hookObj->initialize_postProc();
        }
        return $searchData;
    }

    /**
     * Performs the search, the display and writing stats
     *
     * @param array $search the search parameters, an associative array
     * @Extbase\IgnoreValidation("search")
     */
    public function searchAction($search = []): ResponseInterface
    {
        $searchData = $this->initialize($search);
        // Find free index uid:
        $freeIndexUid = $searchData['freeIndexUid'];
        if ($freeIndexUid == -2) {
            $freeIndexUid = $this->settings['defaultFreeIndexUidList'];
        } elseif (!isset($searchData['freeIndexUid'])) {
            // index configuration is disabled
            $freeIndexUid = -1;
        }

        if (!empty($searchData['extendedSearch'])) {
            $this->view->assignMultiple($this->processExtendedSearchParameters());
        }

        $indexCfgs = GeneralUtility::intExplode(',', $freeIndexUid);
        $resultsets = [];
        foreach ($indexCfgs as $freeIndexUid) {
            // Get result rows
            if ($hookObj = $this->hookRequest('getResultRows')) {
                $resultData = $hookObj->getResultRows($this->searchWords, $freeIndexUid);
            } else {
                $resultData = $this->searchRepository->doSearch($this->searchWords, $freeIndexUid);
            }
            // Display search results
            if ($hookObj = $this->hookRequest('getDisplayResults')) {
                $resultsets[$freeIndexUid] = $hookObj->getDisplayResults($this->searchWords, $resultData, $freeIndexUid);
            } else {
                $resultsets[$freeIndexUid] = $this->getDisplayResults($this->searchWords, $resultData, $freeIndexUid);
            }
            // Create header if we are searching more than one indexing configuration
            if (count($indexCfgs) > 1) {
                if ($freeIndexUid > 0) {
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                        ->getQueryBuilderForTable('index_config');
                    $indexCfgRec = $queryBuilder
                        ->select('title')
                        ->from('index_config')
                        ->where(
                            $queryBuilder->expr()->eq(
                                'uid',
                                $queryBuilder->createNamedParameter($freeIndexUid, \PDO::PARAM_INT)
                            )
                        )
                        ->executeQuery()
                        ->fetchAssociative();
                    $categoryTitle = LocalizationUtility::translate('indexingConfigurationHeader.' . $freeIndexUid, 'IndexedSearch');
                    $categoryTitle = $categoryTitle ?: $indexCfgRec['title'];
                } else {
                    $categoryTitle = LocalizationUtility::translate('indexingConfigurationHeader.' . $freeIndexUid, 'IndexedSearch');
                }
                $resultsets[$freeIndexUid]['categoryTitle'] = $categoryTitle;
            }
            // Write search statistics
            $this->writeSearchStat($this->searchWords ?: []);
        }
        $this->view->assign('resultsets', $resultsets);
        $this->view->assign('searchParams', $searchData);
        $this->view->assign('searchWords', array_map([$this, 'addOperatorLabel'], $this->searchWords));

        return $this->htmlResponse();
    }

    /****************************************
     * functions to make the result rows and result sets
     * ready for the output
     ***************************************/
    /**
     * Compiles the HTML display of the incoming array of result rows.
     *
     * @param array $searchWords Search words array (for display of text describing what was searched for)
     * @param array $resultData Array with result rows, count, first row.
     * @param int $freeIndexUid Pointing to which indexing configuration you want to search in. -1 means no filtering. 0 means only regular indexed content.
     * @return array
     */
    protected function getDisplayResults($searchWords, $resultData, $freeIndexUid = -1)
    {
        $result = [
            'count' => $resultData['count'] ?? 0,
            'searchWords' => $searchWords,
        ];
        // Perform display of result rows array
        if ($resultData) {
            // Set first selected row (for calculation of ranking later)
            $this->firstRow = $resultData['firstRow'];
            // Result display here
            $result['rows'] = $this->compileResultRows($resultData['resultRows'], $freeIndexUid);
            $result['affectedSections'] = $this->resultSections;
            // Browsing box
            if ($resultData['count']) {
                // could we get this in the view?
                if ($this->searchData['group'] === 'sections' && $freeIndexUid <= 0) {
                    $resultSectionsCount = count($this->resultSections);
                    $result['sectionText'] = sprintf(LocalizationUtility::translate('result.' . ($resultSectionsCount > 1 ? 'inNsections' : 'inNsection'), 'IndexedSearch') ?? '', $resultSectionsCount);
                }
            }
        }
        // Print a message telling which words in which sections we searched for
        if (strpos($this->searchData['sections'], 'rl') === 0) {
            $result['searchedInSectionInfo'] = (LocalizationUtility::translate('result.inSection', 'IndexedSearch') ?? '') . ' "' . $this->getPathFromPageId((int)substr($this->searchData['sections'], 4)) . '"';
        }

        if ($hookObj = $this->hookRequest('getDisplayResults_postProc')) {
            $result = $hookObj->getDisplayResults_postProc($result);
        }

        return $result;
    }

    /**
     * Takes the array with resultrows as input and returns the result-HTML-code
     * Takes the "group" var into account: Makes a "section" or "flat" display.
     *
     * @param array $resultRows Result rows
     * @param int $freeIndexUid Pointing to which indexing configuration you want to search in. -1 means no filtering. 0 means only regular indexed content.
     * @return array the result rows with additional information
     */
    protected function compileResultRows($resultRows, $freeIndexUid = -1)
    {
        $finalResultRows = [];
        // Transfer result rows to new variable,
        // performing some mapping of sub-results etc.
        $newResultRows = [];
        foreach ($resultRows as $row) {
            $id = md5($row['phash_grouping']);
            if (is_array($newResultRows[$id] ?? null)) {
                // swapping:
                if (!$newResultRows[$id]['show_resume'] && $row['show_resume']) {
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
        if ($freeIndexUid <= 0 && $this->searchData['group'] === 'sections') {
            $rl2flag = strpos($this->searchData['sections'], 'rl') === 0;
            $sections = [];
            foreach ($resultRows as $row) {
                $id = $row['rl0'] . '-' . $row['rl1'] . ($rl2flag ? '-' . $row['rl2'] : '');
                $sections[$id][] = $row;
            }
            $this->resultSections = [];
            foreach ($sections as $id => $resultRows) {
                $rlParts = explode('-', $id);
                if ($rlParts[2]) {
                    $theId = $rlParts[2];
                    $theRLid = 'rl2_' . $rlParts[2];
                } elseif ($rlParts[1]) {
                    $theId = $rlParts[1];
                    $theRLid = 'rl1_' . $rlParts[1];
                } else {
                    $theId = $rlParts[0];
                    $theRLid = '0';
                }
                $sectionName = $this->getPathFromPageId((int)$theId);
                $sectionName = ltrim($sectionName, '/');
                if (!trim($sectionName)) {
                    $sectionTitleLinked = LocalizationUtility::translate('result.unnamedSection', 'IndexedSearch') . ':';
                } else {
                    $onclick = 'document.forms[\'tx_indexedsearch\'][\'tx_indexedsearch_pi2[search][_sections]\'].value=' . GeneralUtility::quoteJSvalue($theRLid) . ';document.forms[\'tx_indexedsearch\'].submit();return false;';
                    $sectionTitleLinked = '<a href="#" onclick="' . htmlspecialchars($onclick) . '">' . $sectionName . ':</a>';
                }
                $resultRowsCount = count($resultRows);
                $this->resultSections[$id] = [$sectionName, $resultRowsCount];
                // Add section header
                $finalResultRows[] = [
                    'isSectionHeader' => true,
                    'numResultRows' => $resultRowsCount,
                    'sectionId' => $id,
                    'sectionTitle' => $sectionTitleLinked,
                ];
                // Render result rows
                foreach ($resultRows as $row) {
                    $finalResultRows[] = $this->compileSingleResultRow($row);
                }
            }
        } else {
            // flat mode or no sections at all
            foreach ($resultRows as $row) {
                $finalResultRows[] = $this->compileSingleResultRow($row);
            }
        }
        return $finalResultRows;
    }

    /**
     * This prints a single result row, including a recursive call for subrows.
     *
     * @param array $row Search result row
     * @param int $headerOnly 1=Display only header (for sub-rows!), 2=nothing at all
     * @return array the result row with additional information
     */
    protected function compileSingleResultRow($row, $headerOnly = 0)
    {
        $specRowConf = $this->getSpecialConfigurationForResultRow($row);
        $resultData = $row;
        $resultData['headerOnly'] = $headerOnly;
        $resultData['CSSsuffix'] = ($specRowConf['CSSsuffix'] ?? false) ? '-' . $specRowConf['CSSsuffix'] : '';
        if ($this->multiplePagesType($row['item_type'])) {
            $dat = json_decode($row['static_page_arguments'], true);
            if (is_array($dat) && is_string($dat['key'] ?? null) && $dat['key'] !== '') {
                $pp = explode('-', $dat['key']);
                if ($pp[0] != $pp[1]) {
                    $resultData['titleaddition'] = ', ' . LocalizationUtility::translate('result.pages', 'IndexedSearch') . ' ' . $dat['key'];
                } else {
                    $resultData['titleaddition'] = ', ' . LocalizationUtility::translate('result.page', 'IndexedSearch') . ' ' . $pp[0];
                }
            }
        }
        $title = $resultData['item_title'] . ($resultData['titleaddition'] ?? '');
        $title = GeneralUtility::fixed_lgd_cs($title, $this->settings['results.']['titleCropAfter'], $this->settings['results.']['titleCropSignifier']);
        // If external media, link to the media-file instead.
        if ($row['item_type']) {
            if ($row['show_resume']) {
                $targetAttribute = '';
                if ($GLOBALS['TSFE']->config['config']['fileTarget'] ?? false) {
                    $targetAttribute = ' target="' . htmlspecialchars($GLOBALS['TSFE']->config['config']['fileTarget']) . '"';
                }
                $title = '<a href="' . htmlspecialchars($row['data_filename']) . '"' . $targetAttribute . '>' . htmlspecialchars($title) . '</a>';
            } else {
                // Suspicious, so linking to page instead...
                $copiedRow = $row;
                unset($copiedRow['static_page_arguments']);
                $title = $this->linkPageATagWrap(
                    htmlspecialchars($title),
                    $this->linkPage($row['page_id'], $copiedRow)
                );
            }
        } else {
            // Else the page:
            // Prepare search words for markup in content:
            $markUpSwParams = [];
            if ($this->settings['forwardSearchWordsInResultLink']['_typoScriptNodeValue']) {
                // @deprecated: this feature will have no effect anymore in TYPO3 v12, and will be disabled
                if ($this->settings['forwardSearchWordsInResultLink']['no_cache']) {
                    $markUpSwParams = ['no_cache' => 1];
                }
                foreach ($this->searchWords as $d) {
                    $markUpSwParams['sword_list'][] = $d['sword'];
                }
            }
            $title = $this->linkPageATagWrap(
                htmlspecialchars($title),
                $this->linkPage($row['data_page_id'], $row, $markUpSwParams)
            );
        }
        $resultData['title'] = $title;
        $resultData['icon'] = $this->makeItemTypeIcon($row['item_type'], '', $specRowConf);
        $resultData['rating'] = $this->makeRating($row);
        $resultData['description'] = $this->makeDescription(
            $row,
            (bool)!($this->searchData['extResume'] && !$headerOnly),
            $this->settings['results.']['summaryCropAfter']
        );
        $resultData['language'] = $this->makeLanguageIndication($row);
        $resultData['size'] = GeneralUtility::formatSize($row['item_size']);
        $resultData['created'] = $row['item_crdate'];
        $resultData['modified'] = $row['item_mtime'];
        $pI = parse_url($row['data_filename']);
        if ($pI['scheme'] ?? false) {
            $targetAttribute = '';
            if ($GLOBALS['TSFE']->config['config']['fileTarget'] ?? false) {
                $targetAttribute = ' target="' . htmlspecialchars($GLOBALS['TSFE']->config['config']['fileTarget']) . '"';
            }
            $resultData['pathTitle'] = $row['data_filename'];
            $resultData['pathUri'] = $row['data_filename'];
            $resultData['path'] = '<a href="' . htmlspecialchars($row['data_filename']) . '"' . $targetAttribute . '>' . htmlspecialchars($row['data_filename']) . '</a>';
        } else {
            $pathId = $row['data_page_id'] ?: $row['page_id'];
            $pathMP = $row['data_page_id'] ? $row['data_page_mp'] : '';
            $pathStr = $this->getPathFromPageId($pathId, $pathMP);
            $pathLinkData = $this->linkPage(
                $pathId,
                [
                    'data_page_type' => $row['data_page_type'],
                    'data_page_mp' => $pathMP,
                    'sys_language_uid' => $row['sys_language_uid'],
                    'static_page_arguments' => $row['static_page_arguments'],
                ]
            );

            $resultData['pathTitle'] = $pathStr;
            $resultData['pathUri'] = $pathLinkData['uri'];
            $resultData['path'] = $this->linkPageATagWrap($pathStr, $pathLinkData);

            // check if the access is restricted
            if (is_array($this->requiredFrontendUsergroups[$pathId]) && !empty($this->requiredFrontendUsergroups[$pathId])) {
                $lockedIcon = PathUtility::getPublicResourceWebPath('EXT:indexed_search/Resources/Public/Icons/FileTypes/locked.gif');
                $resultData['access'] = '<img src="' . htmlspecialchars($lockedIcon) . '"'
                    . ' width="12" height="15" vspace="5" title="'
                    . sprintf(LocalizationUtility::translate('result.memberGroups', 'IndexedSearch') ?? '', implode(',', array_unique($this->requiredFrontendUsergroups[$pathId])))
                    . '" alt="" />';
            }
        }
        // If there are subrows (eg. subpages in a PDF-file or if a duplicate page
        // is selected due to user-login (phash_grouping))
        if (is_array($row['_sub'] ?? false)) {
            $resultData['subresults'] = [];
            if ($this->multiplePagesType($row['item_type'])) {
                $resultData['subresults']['header'] = LocalizationUtility::translate('result.otherMatching', 'IndexedSearch');
                foreach ($row['_sub'] as $subRow) {
                    $resultData['subresults']['items'][] = $this->compileSingleResultRow($subRow, 1);
                }
            } else {
                $resultData['subresults']['header'] = LocalizationUtility::translate('result.otherMatching', 'IndexedSearch');
                $resultData['subresults']['info'] = LocalizationUtility::translate('result.otherPageAsWell', 'IndexedSearch');
            }
        }
        return $resultData;
    }

    /**
     * Returns configuration from TypoScript for result row based
     * on ID / location in page tree!
     *
     * @param array $row Result row
     * @return array Configuration array
     */
    protected function getSpecialConfigurationForResultRow($row)
    {
        $pathId = $row['data_page_id'] ?: $row['page_id'];
        $pathMP = $row['data_page_id'] ? $row['data_page_mp'] : '';
        $specConf = $this->settings['specialConfiguration']['0'] ?? [];
        try {
            $rl = GeneralUtility::makeInstance(RootlineUtility::class, $pathId, $pathMP)->get();
            foreach ($rl as $dat) {
                if (is_array($this->settings['specialConfiguration'][$dat['uid']] ?? false)) {
                    $specConf = $this->settings['specialConfiguration'][$dat['uid']];
                    $specConf['_pid'] = $dat['uid'];
                    break;
                }
            }
        } catch (RootLineException $e) {
            // do nothing
        }
        return $specConf;
    }

    /**
     * Return the rating-HTML code for the result row. This makes use of the $this->firstRow
     *
     * @param array $row Result row array
     * @return string String showing ranking value
     * @todo can this be a ViewHelper?
     */
    protected function makeRating($row)
    {
        $default = ' ';
        switch ((string)$this->searchData['sortOrder']) {
            case 'rank_count':
                return $row['order_val'] . ' ' . LocalizationUtility::translate('result.ratingMatches', 'IndexedSearch');
            case 'rank_first':
                return ceil(MathUtility::forceIntegerInRange(255 - $row['order_val'], 1, 255) / 255 * 100) . '%';
            case 'rank_flag':
                if ($this->firstRow['order_val2'] ?? 0) {
                    // (3 MSB bit, 224 is highest value of order_val1 currently)
                    $base = $row['order_val1'] * 256;
                    // 15-3 MSB = 12
                    $freqNumber = $row['order_val2'] / $this->firstRow['order_val2'] * 2 ** 12;
                    $total = MathUtility::forceIntegerInRange($base + $freqNumber, 0, 32767);
                    return ceil(log($total) / log(32767) * 100) . '%';
                }
                return $default;
            case 'rank_freq':
                $max = 10000;
                $total = MathUtility::forceIntegerInRange($row['order_val'], 0, $max);
                return ceil(log($total) / log($max) * 100) . '%';
            case 'crdate':
                return $GLOBALS['TSFE']->cObj->calcAge($GLOBALS['EXEC_TIME'] - $row['item_crdate'], 0);
            case 'mtime':
                return $GLOBALS['TSFE']->cObj->calcAge($GLOBALS['EXEC_TIME'] - $row['item_mtime'], 0);
            default:
                return $default;
        }
    }

    /**
     * Returns the HTML code for language indication.
     *
     * @param array $row Result row
     * @return string HTML code for result row.
     */
    protected function makeLanguageIndication($row)
    {
        $output = '&nbsp;';
        // If search result is a TYPO3 page:
        if ((string)$row['item_type'] === '0') {
            // If TypoScript is used to render the flag:
            if (is_array($this->settings['flagRendering'] ?? false)) {
                $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
                $cObj->setCurrentVal($row['sys_language_uid']);
                $typoScriptArray = $this->typoScriptService->convertPlainArrayToTypoScriptArray($this->settings['flagRendering']);
                $output = $cObj->cObjGetSingle($this->settings['flagRendering']['_typoScriptNodeValue'], $typoScriptArray);
            }
        }
        return $output;
    }

    /**
     * Return icon for file extension
     *
     * @param string $imageType File extension / item type
     * @param string $alt Title attribute value in icon.
     * @param array $specRowConf TypoScript configuration specifically for search result.
     * @return string HTML <img> tag for icon
     */
    public function makeItemTypeIcon($imageType, $alt, $specRowConf)
    {
        // Build compound key if item type is 0, iconRendering is not used
        // and specialConfiguration.[pid].pageIcon was set in TS
        if (
            $imageType === '0' && ($specRowConf['_pid'] ?? false)
            && is_array($specRowConf['pageIcon'] ?? false)
            && !is_array($this->settings['iconRendering'] ?? false)
        ) {
            $imageType .= ':' . $specRowConf['_pid'];
        }
        if (!isset($this->iconFileNameCache[$imageType])) {
            $this->iconFileNameCache[$imageType] = '';
            // If TypoScript is used to render the icon:
            if (is_array($this->settings['iconRendering'] ?? false)) {
                $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
                $cObj->setCurrentVal($imageType);
                $typoScriptArray = $this->typoScriptService->convertPlainArrayToTypoScriptArray($this->settings['iconRendering']);
                $this->iconFileNameCache[$imageType] = $cObj->cObjGetSingle($this->settings['iconRendering']['_typoScriptNodeValue'], $typoScriptArray);
            } else {
                // Default creation / finding of icon:
                $icon = '';
                if ($imageType === '0' || strpos($imageType, '0:') === 0) {
                    if (is_array($specRowConf['pageIcon'] ?? false)) {
                        $this->iconFileNameCache[$imageType] = $GLOBALS['TSFE']->cObj->cObjGetSingle('IMAGE', $specRowConf['pageIcon']);
                    } else {
                        $icon = 'EXT:indexed_search/Resources/Public/Icons/FileTypes/pages.gif';
                    }
                } elseif ($this->externalParsers[$imageType]) {
                    $icon = $this->externalParsers[$imageType]->getIcon($imageType);
                }
                if ($icon) {
                    $fullPath = GeneralUtility::getFileAbsFileName($icon);
                    if ($fullPath) {
                        $imageInfo = GeneralUtility::makeInstance(ImageInfo::class, $fullPath);
                        $iconPath = PathUtility::stripPathSitePrefix($fullPath);
                        $this->iconFileNameCache[$imageType] = $imageInfo->getWidth()
                            ? '<img src="' . $iconPath
                              . '" width="' . $imageInfo->getWidth()
                              . '" height="' . $imageInfo->getHeight()
                              . '" title="' . htmlspecialchars($alt) . '" alt="" />'
                            : '';
                    }
                }
            }
        }
        return $this->iconFileNameCache[$imageType];
    }

    /**
     * Returns the resume for the search-result.
     *
     * @param array $row Search result row
     * @param bool $noMarkup If noMarkup is FALSE, then the index_fulltext table is used to select the content of the page, split it with regex to display the search words in the text.
     * @param int $length String length
     * @return string HTML string
     * @todo overwork this
     */
    protected function makeDescription($row, $noMarkup = false, $length = 180)
    {
        $markedSW = '';
        $outputStr = '';
        if ($row['show_resume']) {
            if (!$noMarkup) {
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('index_fulltext');
                $ftdrow = $queryBuilder
                    ->select('*')
                    ->from('index_fulltext')
                    ->where(
                        $queryBuilder->expr()->eq(
                            'phash',
                            $queryBuilder->createNamedParameter($row['phash'], \PDO::PARAM_INT)
                        )
                    )
                    ->executeQuery()
                    ->fetchAssociative();
                if ($ftdrow !== false) {
                    // Cut HTTP references after some length
                    $content = preg_replace('/(http:\\/\\/[^ ]{' . $this->settings['results.']['hrefInSummaryCropAfter'] . '})([^ ]+)/i', '$1...', $ftdrow['fulltextdata']);
                    $markedSW = $this->markupSWpartsOfString($content);
                }
            }
            if (!trim($markedSW)) {
                $outputStr = GeneralUtility::fixed_lgd_cs($row['item_description'], $length, $this->settings['results.']['summaryCropSignifier']);
                $outputStr = htmlspecialchars($outputStr);
            }
            $output = $outputStr ?: $markedSW;
        } else {
            $output = '<span class="noResume">' . LocalizationUtility::translate('result.noResume', 'IndexedSearch') . '</span>';
        }
        return $output;
    }

    /**
     * Marks up the search words from $this->searchWords in the $str with a color.
     *
     * @param string $str Text in which to find and mark up search words. This text is assumed to be UTF-8 like the search words internally is.
     * @return string Processed content
     */
    protected function markupSWpartsOfString($str)
    {
        $htmlParser = GeneralUtility::makeInstance(HtmlParser::class);
        // Init:
        $str = str_replace('&nbsp;', ' ', $htmlParser->bidir_htmlspecialchars($str, -1));
        $str = preg_replace('/\\s\\s+/', ' ', $str);
        $swForReg = [];
        // Prepare search words for regex:
        foreach ($this->searchWords as $d) {
            $swForReg[] = preg_quote($d['sword'], '/');
        }
        $regExString = '(' . implode('|', $swForReg) . ')';
        // Split and combine:
        $parts = preg_split('/' . $regExString . '/i', ' ' . $str . ' ', 20000, PREG_SPLIT_DELIM_CAPTURE);
        $parts = $parts ?: [];
        // Constants:
        $summaryMax = $this->settings['results.']['markupSW_summaryMax'];
        $postPreLgd = (int)$this->settings['results.']['markupSW_postPreLgd'];
        $postPreLgd_offset = (int)$this->settings['results.']['markupSW_postPreLgd_offset'];
        $divider = $this->settings['results.']['markupSW_divider'];
        $occurrences = (count($parts) - 1) / 2;
        if ($occurrences) {
            $postPreLgd = MathUtility::forceIntegerInRange($summaryMax / $occurrences, $postPreLgd, $summaryMax / 2);
        }
        // Variable:
        $summaryLgd = 0;
        $output = [];
        // Shorten in-between strings:
        foreach ($parts as $k => $strP) {
            if ($k % 2 == 0) {
                // Find length of the summary part:
                $strLen = mb_strlen($parts[$k], 'utf-8');
                $output[$k] = $parts[$k];
                // Possibly shorten string:
                if (!$k) {
                    // First entry at all (only cropped on the frontside)
                    if ($strLen > $postPreLgd) {
                        $output[$k] = $divider . preg_replace('/^[^[:space:]]+[[:space:]]/', '', GeneralUtility::fixed_lgd_cs($parts[$k], -($postPreLgd - $postPreLgd_offset)));
                    }
                } elseif ($summaryLgd > $summaryMax || !isset($parts[$k + 1])) {
                    // In case summary length is exceed OR if there are no more entries at all:
                    if ($strLen > $postPreLgd) {
                        $output[$k] = preg_replace('/[[:space:]][^[:space:]]+$/', '', GeneralUtility::fixed_lgd_cs(
                            $parts[$k],
                            $postPreLgd - $postPreLgd_offset
                        )) . $divider;
                    }
                } else {
                    if ($strLen > $postPreLgd * 2) {
                        $output[$k] = preg_replace('/[[:space:]][^[:space:]]+$/', '', GeneralUtility::fixed_lgd_cs(
                            $parts[$k],
                            $postPreLgd - $postPreLgd_offset
                        )) . $divider . preg_replace('/^[^[:space:]]+[[:space:]]/', '', GeneralUtility::fixed_lgd_cs($parts[$k], -($postPreLgd - $postPreLgd_offset)));
                    }
                }
                $summaryLgd += mb_strlen($output[$k], 'utf-8');
                // Protect output:
                $output[$k] = htmlspecialchars($output[$k]);
                // If summary lgd is exceed, break the process:
                if ($summaryLgd > $summaryMax) {
                    break;
                }
            } else {
                $summaryLgd += mb_strlen($strP, 'utf-8');
                $output[$k] = '<strong class="tx-indexedsearch-redMarkup">' . htmlspecialchars($parts[$k]) . '</strong>';
            }
        }
        // Return result:
        return implode('', $output);
    }

    /**
     * Write statistics information to database for the search operation if there was at least one search word.
     *
     * @param array $searchWords Search Word array
     */
    protected function writeSearchStat(array $searchWords): void
    {
        if (empty($searchWords)) {
            return;
        }
        $entries = [];
        foreach ($searchWords as $val) {
            $entries[] = [
                mb_substr($val['sword'], 0, 50),
                // Time stamp
                $GLOBALS['EXEC_TIME'],
                // search page id for indexed search stats
                $GLOBALS['TSFE']->id,
            ];
        }
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('index_stat_word')
            ->bulkInsert(
                'index_stat_word',
                $entries,
                [ 'word', 'tstamp', 'pageid' ],
                [ \PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_INT ]
            );
    }

    /**
     * Splits the search word input into an array where each word is represented by an array with key "sword"
     * holding the search word and key "oper" holding the SQL operator (eg. AND, OR)
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
     * @param bool $defaultOperator If TRUE, the default operator will be OR, not AND
     * @return array Search words if any found
     */
    protected function getSearchWords($defaultOperator)
    {
        // Shorten search-word string to max 200 bytes - shortening the string here is only a run-away feature!
        $searchWords = mb_substr($this->getSword(), 0, 200);
        // Convert to UTF-8 + conv. entities (was also converted during indexing!)
        if ($GLOBALS['TSFE']->metaCharset && $GLOBALS['TSFE']->metaCharset !== 'utf-8') {
            $searchWords = mb_convert_encoding($searchWords, 'utf-8', $GLOBALS['TSFE']->metaCharset);
            $searchWords = html_entity_decode($searchWords);
        }
        $sWordArray = false;
        if ($hookObj = $this->hookRequest('getSearchWords')) {
            $sWordArray = $hookObj->getSearchWords_splitSWords($searchWords, $defaultOperator);
        } else {
            // sentence
            if ($this->searchData['searchType'] == 20) {
                $sWordArray = [
                    [
                        'sword' => trim($searchWords),
                        'oper' => 'AND',
                    ],
                ];
            } else {
                // case-sensitive. Defines the words, which will be
                // operators between words
                $operatorTranslateTable = [
                    ['+', 'AND'],
                    ['|', 'OR'],
                    ['-', 'AND NOT'],
                    // Add operators for various languages
                    // Converts the operators to lowercase
                    [mb_strtolower(LocalizationUtility::translate('localizedOperandAnd', 'IndexedSearch') ?? '', 'utf-8'), 'AND'],
                    [mb_strtolower(LocalizationUtility::translate('localizedOperandOr', 'IndexedSearch') ?? '', 'utf-8'), 'OR'],
                    [mb_strtolower(LocalizationUtility::translate('localizedOperandNot', 'IndexedSearch') ?? '', 'utf-8'), 'AND NOT'],
                ];
                $swordArray = IndexedSearchUtility::getExplodedSearchString($searchWords, $defaultOperator == 1 ? 'OR' : 'AND', $operatorTranslateTable);
                if (is_array($swordArray)) {
                    $sWordArray = $this->procSearchWordsByLexer($swordArray);
                }
            }
        }
        return $sWordArray;
    }

    /**
     * Post-process the search word array so it will match the words that was indexed (including case-folding if any)
     * If any words are splitted into multiple words (eg. CJK will be!) the operator of the main word will remain.
     *
     * @param array $searchWords Search word array
     * @return array Search word array, processed through lexer
     */
    protected function procSearchWordsByLexer($searchWords)
    {
        $newSearchWords = [];
        // Init lexer (used to post-processing of search words)
        $lexerObjectClassName = ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['lexer'] ?? false) ? $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['lexer'] : Lexer::class;

        /** @var Lexer $lexer */
        $lexer = GeneralUtility::makeInstance($lexerObjectClassName);
        $this->lexerObj = $lexer;
        // Traverse the search word array
        foreach ($searchWords as $wordDef) {
            // No space in word (otherwise it might be a sentence in quotes like "there is").
            if (!str_contains($wordDef['sword'], ' ')) {
                // Split the search word by lexer:
                $res = $this->lexerObj->split2Words($wordDef['sword']);
                // Traverse lexer result and add all words again:
                foreach ($res as $word) {
                    $newSearchWords[] = [
                        'sword' => $word,
                        'oper' => $wordDef['oper'],
                    ];
                }
            } else {
                $newSearchWords[] = $wordDef;
            }
        }
        return $newSearchWords;
    }

    /**
     * Sort options about the search form
     *
     * @param array $search The search data / params
     * @Extbase\IgnoreValidation("search")
     */
    public function formAction($search = []): ResponseInterface
    {
        $searchData = $this->initialize($search);
        // Adding search field value
        $this->view->assign('sword', $this->getSword());
        // Extended search
        if (!empty($searchData['extendedSearch'])) {
            $this->view->assignMultiple($this->processExtendedSearchParameters());
        }
        $this->view->assign('searchParams', $searchData);

        return $this->htmlResponse();
    }

    /**
     * TypoScript was not loaded
     */
    public function noTypoScriptAction(): ResponseInterface
    {
        return $this->htmlResponse();
    }

    /****************************************
     * building together the available options for every dropdown
     ***************************************/
    /**
     * get the values for the "type" selector
     *
     * @return array Associative array with options
     */
    protected function getAllAvailableSearchTypeOptions()
    {
        $allOptions = [];
        $types = [0, 1, 2, 3, 10, 20];
        $blindSettings = $this->settings['blind'];
        if (!$blindSettings['searchType']) {
            foreach ($types as $typeNum) {
                $allOptions[$typeNum] = LocalizationUtility::translate('searchTypes.' . $typeNum, 'IndexedSearch');
            }
        }
        // Remove this option if metaphone search is disabled)
        if (!$this->enableMetaphoneSearch) {
            unset($allOptions[10]);
        }
        // disable single entries by TypoScript
        $allOptions = $this->removeOptionsFromOptionList($allOptions, $blindSettings['searchType']);
        return $allOptions;
    }

    /**
     * get the values for the "defaultOperand" selector
     *
     * @return array Associative array with options
     */
    protected function getAllAvailableOperandsOptions()
    {
        $allOptions = [];
        $blindSettings = $this->settings['blind'];
        if (!$blindSettings['defaultOperand']) {
            $allOptions = [
                0 => LocalizationUtility::translate('defaultOperands.0', 'IndexedSearch'),
                1 => LocalizationUtility::translate('defaultOperands.1', 'IndexedSearch'),
            ];
        }
        // disable single entries by TypoScript
        $allOptions = $this->removeOptionsFromOptionList($allOptions, $blindSettings['defaultOperand']);
        return $allOptions;
    }

    /**
     * get the values for the "media type" selector
     *
     * @return array Associative array with options
     */
    protected function getAllAvailableMediaTypesOptions()
    {
        $allOptions = [];
        $mediaTypes = [-1, 0, -2];
        $blindSettings = $this->settings['blind'];
        if (!$blindSettings['mediaType']) {
            foreach ($mediaTypes as $mediaType) {
                $allOptions[$mediaType] = LocalizationUtility::translate('mediaTypes.' . $mediaType, 'IndexedSearch');
            }
            // Add media to search in:
            $additionalMedia = trim($this->settings['mediaList']);
            if ($additionalMedia !== '') {
                $additionalMedia = GeneralUtility::trimExplode(',', $additionalMedia, true);
            } else {
                $additionalMedia = [];
            }
            foreach ($this->externalParsers as $extension => $obj) {
                // Skip unwanted extensions
                if (!empty($additionalMedia) && !in_array($extension, $additionalMedia)) {
                    continue;
                }
                if ($name = $obj->searchTypeMediaTitle($extension)) {
                    $translatedName = LocalizationUtility::translate('mediaTypes.' . $extension, 'IndexedSearch');
                    $allOptions[$extension] = $translatedName ?: $name;
                }
            }
        }
        // disable single entries by TypoScript
        $allOptions = $this->removeOptionsFromOptionList($allOptions, $blindSettings['mediaType']);
        return $allOptions;
    }

    /**
     * get the values for the "language" selector
     *
     * @return array Associative array with options
     */
    protected function getAllAvailableLanguageOptions()
    {
        $allOptions = [
            '-1' => LocalizationUtility::translate('languageUids.-1', 'IndexedSearch'),
        ];
        $blindSettings = $this->settings['blind'];
        if (!$blindSettings['languageUid']) {
            try {
                $site = GeneralUtility::makeInstance(SiteFinder::class)
                    ->getSiteByPageId($GLOBALS['TSFE']->id);

                $languages = $site->getLanguages();
                foreach ($languages as $language) {
                    $allOptions[$language->getLanguageId()] = $language->getNavigationTitle();
                }
            } catch (SiteNotFoundException $e) {
                // No Site found, no options
                $allOptions = [];
            }

            // disable single entries by TypoScript
            $allOptions = $this->removeOptionsFromOptionList($allOptions, (array)$blindSettings['languageUid']);
        } else {
            $allOptions = [];
        }
        return $allOptions;
    }

    /**
     * get the values for the "section" selector
     * Here values like "rl1_" and "rl2_" + a rootlevel 1/2 id can be added
     * to perform searches in rootlevel 1+2 specifically. The id-values can even
     * be commaseparated. Eg. "rl1_1,2" would search for stuff inside pages on
     * menu-level 1 which has the uid's 1 and 2.
     *
     * @return array Associative array with options
     */
    protected function getAllAvailableSectionsOptions()
    {
        $allOptions = [];
        $sections = [0, -1, -2, -3];
        $blindSettings = $this->settings['blind'];
        if (!$blindSettings['sections']) {
            foreach ($sections as $section) {
                $allOptions[$section] = LocalizationUtility::translate('sections.' . $section, 'IndexedSearch');
            }
        }
        // Creating levels for section menu:
        // This selects the first and secondary menus for the "sections" selector - so we can search in sections and sub sections.
        if ($this->settings['displayLevel1Sections']) {
            $firstLevelMenu = $this->getMenuOfPages((int)$this->searchRootPageIdList);
            $labelLevel1 = LocalizationUtility::translate('sections.rootLevel1', 'IndexedSearch');
            $labelLevel2 = LocalizationUtility::translate('sections.rootLevel2', 'IndexedSearch');
            foreach ($firstLevelMenu as $firstLevelKey => $menuItem) {
                if (!$menuItem['nav_hide']) {
                    $allOptions['rl1_' . $menuItem['uid']] = trim($labelLevel1 . ' ' . $menuItem['title']);
                    if ($this->settings['displayLevel2Sections']) {
                        $secondLevelMenu = $this->getMenuOfPages($menuItem['uid']);
                        foreach ($secondLevelMenu as $secondLevelKey => $menuItemLevel2) {
                            if (!$menuItemLevel2['nav_hide']) {
                                $allOptions['rl2_' . $menuItemLevel2['uid']] = trim($labelLevel2 . ' ' . $menuItemLevel2['title']);
                            } else {
                                unset($secondLevelMenu[$secondLevelKey]);
                            }
                        }
                        $allOptions['rl2_' . implode(',', array_keys($secondLevelMenu))] = LocalizationUtility::translate('sections.rootLevel2All', 'IndexedSearch');
                    }
                } else {
                    unset($firstLevelMenu[$firstLevelKey]);
                }
            }
            $allOptions['rl1_' . implode(',', array_keys($firstLevelMenu))] = LocalizationUtility::translate('sections.rootLevel1All', 'IndexedSearch');
        }
        // disable single entries by TypoScript
        $allOptions = $this->removeOptionsFromOptionList($allOptions, $blindSettings['sections']);
        return $allOptions;
    }

    /**
     * get the values for the "freeIndexUid" selector
     *
     * @return array Associative array with options
     */
    protected function getAllAvailableIndexConfigurationsOptions()
    {
        $allOptions = [
            '-1' => LocalizationUtility::translate('indexingConfigurations.-1', 'IndexedSearch'),
            '-2' => LocalizationUtility::translate('indexingConfigurations.-2', 'IndexedSearch'),
            '0' => LocalizationUtility::translate('indexingConfigurations.0', 'IndexedSearch'),
        ];
        $blindSettings = $this->settings['blind'];
        if (!($blindSettings['indexingConfigurations'] ?? false)) {
            // add an additional index configuration
            if ($this->settings['defaultFreeIndexUidList']) {
                $uidList = GeneralUtility::intExplode(',', $this->settings['defaultFreeIndexUidList']);
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable('index_config');
                $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
                $result = $queryBuilder
                    ->select('uid', 'title')
                    ->from('index_config')
                    ->where(
                        $queryBuilder->expr()->in(
                            'uid',
                            $queryBuilder->createNamedParameter($uidList, Connection::PARAM_INT_ARRAY)
                        )
                    )
                    ->executeQuery();

                while ($row = $result->fetchAssociative()) {
                    $indexId = (int)$row['uid'];
                    $title = LocalizationUtility::translate('indexingConfigurations.' . $indexId, 'IndexedSearch');
                    $allOptions[$indexId] = $title ?: $row['title'];
                }
            }
            // disable single entries by TypoScript
            $allOptions = $this->removeOptionsFromOptionList($allOptions, (array)($blindSettings['indexingConfigurations'] ?? []));
        } else {
            $allOptions = [];
        }
        return $allOptions;
    }

    /**
     * get the values for the "section" selector
     * Here values like "rl1_" and "rl2_" + a rootlevel 1/2 id can be added
     * to perform searches in rootlevel 1+2 specifically. The id-values can even
     * be commaseparated. Eg. "rl1_1,2" would search for stuff inside pages on
     * menu-level 1 which has the uid's 1 and 2.
     *
     * @return array Associative array with options
     */
    protected function getAllAvailableSortOrderOptions()
    {
        $allOptions = [];
        $sortOrders = ['rank_flag', 'rank_freq', 'rank_first', 'rank_count', 'mtime', 'title', 'crdate'];
        $blindSettings = $this->settings['blind'];
        if (!$blindSettings['sortOrder']) {
            foreach ($sortOrders as $order) {
                $allOptions[$order] = LocalizationUtility::translate('sortOrders.' . $order, 'IndexedSearch');
            }
        }
        // disable single entries by TypoScript
        $allOptions = $this->removeOptionsFromOptionList($allOptions, ($blindSettings['sortOrder.'] ?? []));
        return $allOptions;
    }

    /**
     * get the values for the "group" selector
     *
     * @return array Associative array with options
     */
    protected function getAllAvailableGroupOptions()
    {
        $allOptions = [];
        $blindSettings = $this->settings['blind'];
        if (!($blindSettings['groupBy'] ?? false)) {
            $allOptions = [
                'sections' => LocalizationUtility::translate('groupBy.sections', 'IndexedSearch'),
                'flat' => LocalizationUtility::translate('groupBy.flat', 'IndexedSearch'),
            ];
        }
        // disable single entries by TypoScript
        $allOptions = $this->removeOptionsFromOptionList($allOptions, ($blindSettings['groupBy.'] ?? []));
        return $allOptions;
    }

    /**
     * get the values for the "sortDescending" selector
     *
     * @return array Associative array with options
     */
    protected function getAllAvailableSortDescendingOptions()
    {
        $allOptions = [];
        $blindSettings = $this->settings['blind'];
        if (!($blindSettings['descending'] ?? false)) {
            $allOptions = [
                0 => LocalizationUtility::translate('sortOrders.descending', 'IndexedSearch'),
                1 => LocalizationUtility::translate('sortOrders.ascending', 'IndexedSearch'),
            ];
        }
        // disable single entries by TypoScript
        $allOptions = $this->removeOptionsFromOptionList($allOptions, ($blindSettings['descending.'] ?? []));
        return $allOptions;
    }

    /**
     * get the values for the "results" selector
     *
     * @return array Associative array with options
     */
    protected function getAllAvailableNumberOfResultsOptions()
    {
        $allOptions = [];
        if (count($this->availableResultsNumbers) > 1) {
            $allOptions = array_combine($this->availableResultsNumbers, $this->availableResultsNumbers);
        }
        // disable single entries by TypoScript
        return $this->removeOptionsFromOptionList((array)$allOptions, $this->settings['blind']['numberOfResults']);
    }

    /**
     * removes blinding entries from the option list of a selector
     *
     * @param array $allOptions associative array containing all options
     * @param array $blindOptions associative array containing the optionkey as they key and the value = 1 if it should be removed
     * @return array Options from $allOptions with some options removed
     */
    protected function removeOptionsFromOptionList($allOptions, $blindOptions)
    {
        if (is_array($blindOptions)) {
            foreach ($blindOptions as $key => $val) {
                if ($val == 1) {
                    unset($allOptions[$key]);
                }
            }
        }
        return $allOptions;
    }

    /**
     * Links the $linkText to page $pageUid
     *
     * @param int $pageUid Page id
     * @param array $row Result row
     * @param array $markUpSwParams Additional parameters for marking up search words
     * @return array
     */
    protected function linkPage($pageUid, $row = [], $markUpSwParams = [])
    {
        $pageLanguage = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('language', 'contentId', 0);
        // Parameters for link
        $urlParameters = [];
        if ($row['static_page_arguments'] !== null) {
            $urlParameters = json_decode($row['static_page_arguments'], true);
        }
        // Add &type and &MP variable:
        if ($row['data_page_mp']) {
            $urlParameters['MP'] = $row['data_page_mp'];
        }
        if (($pageLanguage === 0 && $row['sys_language_uid'] > 0) || $pageLanguage > 0) {
            $urlParameters['L'] = (int)$row['sys_language_uid'];
        }
        // markup-GET vars:
        $urlParameters = array_merge($urlParameters, $markUpSwParams);
        // This will make sure that the path is retrieved if it hasn't been
        // already. Used only for the sake of the domain_record thing.
        $this->getPathFromPageId($pageUid);

        return $this->preparePageLink($pageUid, $row, $urlParameters);
    }

    /**
     * Return the menu of pages used for the selector.
     *
     * @param int $pageUid Page ID for which to return menu
     * @return array Menu items (for making the section selector box)
     */
    protected function getMenuOfPages($pageUid)
    {
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        if ($this->settings['displayLevelxAllTypes']) {
            return $pageRepository->getMenuForPages([$pageUid]);
        }
        return $pageRepository->getMenu($pageUid);
    }

    /**
     * Returns the path to the page $id
     *
     * @param int $id Page ID
     * @param string $pathMP Content of the MP (mount point) variable
     * @return string Path (HTML-escaped)
     */
    protected function getPathFromPageId($id, $pathMP = '')
    {
        $identStr = $id . '|' . $pathMP;
        if (!isset($this->pathCache[$identStr])) {
            $this->requiredFrontendUsergroups[$id] = [];
            $this->domainRecords[$id] = [];
            try {
                $rl = GeneralUtility::makeInstance(RootlineUtility::class, $id, $pathMP)->get();
                $path = '';
                $pageCount = count($rl);
                if (!empty($rl)) {
                    $excludeDoktypesFromPath = GeneralUtility::trimExplode(
                        ',',
                        $this->settings['results']['pathExcludeDoktypes'] ?? '',
                        true
                    );
                    $breadcrumbWrap = $this->settings['breadcrumbWrap'] ?? '/';
                    $breadcrumbWraps = GeneralUtility::makeInstance(TypoScriptService::class)
                        ->explodeConfigurationForOptionSplit(['wrap' => $breadcrumbWrap], $pageCount);
                    foreach ($rl as $k => $v) {
                        if (in_array($v['doktype'], $excludeDoktypesFromPath, false)) {
                            continue;
                        }
                        // Check fe_user
                        if ($v['fe_group'] && ($v['uid'] == $id || $v['extendToSubpages'])) {
                            $this->requiredFrontendUsergroups[$id][] = $v['fe_group'];
                        }
                        // Check sys_domain
                        if ($this->settings['detectDomainRecords']) {
                            $domainName = $this->getFirstDomainForPage((int)$v['uid']);
                            if ($domainName) {
                                $this->domainRecords[$id][] = $domainName;
                                // Set path accordingly
                                $path = $domainName . $path;
                                break;
                            }
                        }
                        // Stop, if we find that the current id is the current root page.
                        if ($v['uid'] == $GLOBALS['TSFE']->config['rootLine'][0]['uid']) {
                            array_pop($breadcrumbWraps);
                            break;
                        }
                        $path = $GLOBALS['TSFE']->cObj->wrap(htmlspecialchars($v['title']), array_pop($breadcrumbWraps)['wrap']) . $path;
                    }
                }
            } catch (RootLineException $e) {
                $path = '';
            }
            $this->pathCache[$identStr] = $path;
        }
        return $this->pathCache[$identStr];
    }

    /**
     * Gets the first domain for the page
     *
     * @param int $id Page id
     * @return string Domain name
     */
    protected function getFirstDomainForPage(int $id): string
    {
        $domain = '';
        try {
            $domain = GeneralUtility::makeInstance(SiteFinder::class)
                ->getSiteByRootPageId($id)
                ->getBase()
                ->getHost();
        } catch (SiteNotFoundException $e) {
            // site was not found, we return an empty string as default
        }
        return $domain;
    }

    /**
     * simple function to initialize possible external parsers
     * feeds the $this->externalParsers array
     */
    protected function initializeExternalParsers()
    {
        // Initialize external document parsers for icon display and other soft operations
        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['external_parsers'] ?? [] as $extension => $className) {
            $this->externalParsers[$extension] = GeneralUtility::makeInstance($className);
            // Init parser and if it returns FALSE, unset its entry again
            if (!$this->externalParsers[$extension]->softInit($extension)) {
                unset($this->externalParsers[$extension]);
            }
        }
    }

    /**
     * Returns an object reference to the hook object if any
     *
     * @param string $functionName Name of the function you want to call / hook key
     * @return object|null Hook object, if any. Otherwise NULL.
     */
    protected function hookRequest($functionName)
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
     * Returns if an item type is a multipage item type
     *
     * @param string $item_type Item type
     * @return bool TRUE if multipage capable
     */
    protected function multiplePagesType($item_type)
    {
        return is_object($this->externalParsers[$item_type] ?? false) && $this->externalParsers[$item_type]->isMultiplePageExtension($item_type);
    }

    /**
     * Process variables related to indexed_search extendedSearch needed by frontend view.
     * Populate select boxes and setting some flags.
     * The returned data can be passed directly into the view by assignMultiple()
     *
     * @return array Variables to pass into the view so they can be used in fluid template
     */
    protected function processExtendedSearchParameters()
    {
        $allSearchTypes = $this->getAllAvailableSearchTypeOptions();
        $allDefaultOperands = $this->getAllAvailableOperandsOptions();
        $allMediaTypes = $this->getAllAvailableMediaTypesOptions();
        $allLanguageUids = $this->getAllAvailableLanguageOptions();
        $allSortOrders = $this->getAllAvailableSortOrderOptions();
        $allSortDescendings = $this->getAllAvailableSortDescendingOptions();

        return [
            'allSearchTypes' => $allSearchTypes,
            'allDefaultOperands' => $allDefaultOperands,
            'showTypeSearch' => !empty($allSearchTypes) || !empty($allDefaultOperands),
            'allMediaTypes' => $allMediaTypes,
            'allLanguageUids' => $allLanguageUids,
            'showMediaAndLanguageSearch' => !empty($allMediaTypes) || !empty($allLanguageUids),
            'allSections' => $this->getAllAvailableSectionsOptions(),
            'allIndexConfigurations' => $this->getAllAvailableIndexConfigurationsOptions(),
            'allSortOrders' => $allSortOrders,
            'allSortDescendings' => $allSortDescendings,
            'showSortOrders' => !empty($allSortOrders) || !empty($allSortDescendings),
            'allNumberOfResults' => $this->getAllAvailableNumberOfResultsOptions(),
            'allGroups' => $this->getAllAvailableGroupOptions(),
        ];
    }

    /**
     * Load settings and apply stdWrap to them
     */
    protected function loadSettings()
    {
        if (!is_array($this->settings['results.'] ?? false)) {
            $this->settings['results.'] = [];
        }
        $fullTypoScriptArray = $this->typoScriptService->convertPlainArrayToTypoScriptArray($this->settings);
        $this->settings['detectDomainRecords'] = $fullTypoScriptArray['detectDomainRecords'] ?? 0;
        $this->settings['detectDomainRecords.'] = $fullTypoScriptArray['detectDomainRecords.'] ?? [];
        $typoScriptArray = $fullTypoScriptArray['results.'];

        $this->settings['results.']['summaryCropAfter'] = MathUtility::forceIntegerInRange(
            $GLOBALS['TSFE']->cObj->stdWrapValue('summaryCropAfter', $typoScriptArray ?? []),
            10,
            5000,
            180
        );
        $this->settings['results.']['summaryCropSignifier'] = $GLOBALS['TSFE']->cObj->stdWrapValue('summaryCropSignifier', $typoScriptArray ?? []);
        $this->settings['results.']['titleCropAfter'] = MathUtility::forceIntegerInRange(
            $GLOBALS['TSFE']->cObj->stdWrapValue('titleCropAfter', $typoScriptArray ?? []),
            10,
            500,
            50
        );
        $this->settings['results.']['titleCropSignifier'] = $GLOBALS['TSFE']->cObj->stdWrapValue('titleCropSignifier', $typoScriptArray ?? []);
        $this->settings['results.']['markupSW_summaryMax'] = MathUtility::forceIntegerInRange(
            $GLOBALS['TSFE']->cObj->stdWrapValue('markupSW_summaryMax', $typoScriptArray ?? []),
            10,
            5000,
            300
        );
        $this->settings['results.']['markupSW_postPreLgd'] = MathUtility::forceIntegerInRange(
            $GLOBALS['TSFE']->cObj->stdWrapValue('markupSW_postPreLgd', $typoScriptArray ?? []),
            1,
            500,
            60
        );
        $this->settings['results.']['markupSW_postPreLgd_offset'] = MathUtility::forceIntegerInRange(
            $GLOBALS['TSFE']->cObj->stdWrapValue('markupSW_postPreLgd_offset', $typoScriptArray ?? []),
            1,
            50,
            5
        );
        $this->settings['results.']['markupSW_divider'] = $GLOBALS['TSFE']->cObj->stdWrapValue('markupSW_divider', $typoScriptArray ?? []);
        $this->settings['results.']['hrefInSummaryCropAfter'] = MathUtility::forceIntegerInRange(
            $GLOBALS['TSFE']->cObj->stdWrapValue('hrefInSummaryCropAfter', $typoScriptArray ?? []),
            10,
            400,
            60
        );
        $this->settings['results.']['hrefInSummaryCropSignifier'] = $GLOBALS['TSFE']->cObj->stdWrapValue('hrefInSummaryCropSignifier', $typoScriptArray ?? []);
    }

    /**
     * Returns number of results to display
     *
     * @param int $numberOfResults Requested number of results
     * @return int
     */
    protected function getNumberOfResults($numberOfResults)
    {
        $numberOfResults = (int)$numberOfResults;

        return in_array($numberOfResults, $this->availableResultsNumbers) ?
            $numberOfResults : $this->defaultResultNumber;
    }

    /**
     * Internal method to build the page uri and link target.
     * @todo make use of the UriBuilder
     *
     * @param int $pageUid
     * @param array $row
     * @param array $urlParameters
     * @return array
     */
    protected function preparePageLink(int $pageUid, array $row, array $urlParameters): array
    {
        $target = '';
        $uri = $this->uriBuilder
                ->setTargetPageUid($pageUid)
                ->setTargetPageType($row['data_page_type'])
                ->setArguments($urlParameters)
                ->build();

        // If external domain, then link to that:
        if (!empty($this->domainRecords[$pageUid])) {
            $scheme = GeneralUtility::getIndpEnv('TYPO3_SSL') ? 'https://' : 'http://';
            $firstDomain = reset($this->domainRecords[$pageUid]);
            $uri = $scheme . $firstDomain . $uri;
            $target = $this->settings['detectDomainRecords.']['target'] ?? '';
        }

        return ['uri' => $uri, 'target' => $target];
    }

    /**
     * Create a tag for "path" key in search result
     *
     * @param string $linkText Link text (nodeValue) (should be hsc'ed already)
     * @param array $linkData
     * @return string HTML <A> tag wrapped title string.
     */
    protected function linkPageATagWrap(string $linkText, array $linkData): string
    {
        $attributes = [
            'href' => $linkData['uri'],
        ];
        if (!empty($linkData['target'])) {
            $attributes['target'] = $linkData['target'];
        }
        return sprintf(
            '<a %s>%s</a>',
            GeneralUtility::implodeAttributes($attributes, true),
            $linkText
        );
    }

    /**
     * Process the search word operator to be used in e.g. locallang keys
     */
    protected function addOperatorLabel(array $searchWord): array
    {
        if ($searchWord['oper'] ?? false) {
            $searchWord['operatorLabel'] = strtolower(str_replace(' ', '', (string)($searchWord['oper'])));
        }

        return $searchWord;
    }

    /**
     * Set the search word
     * @param string $sword
     */
    public function setSword($sword)
    {
        $this->sword = (string)$sword;
    }

    /**
     * Returns the search word
     * @return string
     */
    public function getSword()
    {
        return (string)$this->sword;
    }
}
