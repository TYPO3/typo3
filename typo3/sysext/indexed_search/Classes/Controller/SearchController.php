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
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Index search frontend
 *
 * Creates a search form for indexed search. Indexing must be enabled
 * for this to make sense.
 */
class SearchController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    /**
     * previously known as $this->piVars['sword']
     *
     * @var string
     */
    protected $sword = null;

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
     * Search repository
     *
     * @var \TYPO3\CMS\IndexedSearch\Domain\Repository\IndexSearchRepository
     */
    protected $searchRepository = null;

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
     * Indexer configuration, coming from $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['indexed_search']
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
     * @var \TYPO3\CMS\Extbase\Service\TypoScriptService
     */
    protected $typoScriptService;

    /**
     * @param \TYPO3\CMS\Extbase\Service\TypoScriptService $typoScriptService
     */
    public function injectTypoScriptService(\TYPO3\CMS\Extbase\Service\TypoScriptService $typoScriptService)
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

        $this->loadSettings();

        // setting default values
        if (is_array($this->settings['defaultOptions'])) {
            $searchData = array_merge($this->settings['defaultOptions'], $searchData);
        }
        // Indexer configuration from Extension Manager interface:
        $this->indexerConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['indexed_search']);
        $this->enableMetaphoneSearch = (bool)$this->indexerConfig['enableMetaphoneSearch'];
        $this->initializeExternalParsers();
        // If "_sections" is set, this value overrides any existing value.
        if ($searchData['_sections']) {
            $searchData['sections'] = $searchData['_sections'];
        }
        // If "_sections" is set, this value overrides any existing value.
        if ($searchData['_freeIndexUid'] !== '' && $searchData['_freeIndexUid'] !== '_') {
            $searchData['freeIndexUid'] = $searchData['_freeIndexUid'];
        }
        $searchData['numberOfResults'] = MathUtility::forceIntegerInRange($searchData['numberOfResults'], 1, 100, $this->defaultResultNumber);
        // This gets the search-words into the $searchWordArray
        $this->sword = $searchData['sword'];
        // Add previous search words to current
        if ($searchData['sword_prev_include'] && $searchData['sword_prev']) {
            $this->sword = trim($searchData['sword_prev']) . ' ' . $this->sword;
        }
        $this->searchWords = $this->getSearchWords($searchData['defaultOperand']);
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
        $this->searchRepository = GeneralUtility::makeInstance(\TYPO3\CMS\IndexedSearch\Domain\Repository\IndexSearchRepository::class);
        $this->searchRepository->initialize($this->settings, $searchData, $this->externalParsers, $this->searchRootPageIdList);
        $this->searchData = $searchData;
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
     * @return void
     * @ignorevalidation $search
     */
    public function searchAction($search = [])
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
        $indexCfgs = GeneralUtility::intExplode(',', $freeIndexUid);
        $resultsets = [];
        foreach ($indexCfgs as $freeIndexUid) {
            // Get result rows
            $tstamp1 = GeneralUtility::milliseconds();
            if ($hookObj = $this->hookRequest('getResultRows')) {
                $resultData = $hookObj->getResultRows($this->searchWords, $freeIndexUid);
            } else {
                $resultData = $this->searchRepository->doSearch($this->searchWords, $freeIndexUid);
            }
            // Display search results
            $tstamp2 = GeneralUtility::milliseconds();
            if ($hookObj = $this->hookRequest('getDisplayResults')) {
                $resultsets[$freeIndexUid] = $hookObj->getDisplayResults($this->searchWords, $resultData, $freeIndexUid);
            } else {
                $resultsets[$freeIndexUid] = $this->getDisplayResults($this->searchWords, $resultData, $freeIndexUid);
            }
            $tstamp3 = GeneralUtility::milliseconds();
            // Create header if we are searching more than one indexing configuration
            if (count($indexCfgs) > 1) {
                if ($freeIndexUid > 0) {
                    $indexCfgRec = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('title', 'index_config', 'uid=' . (int)$freeIndexUid . $GLOBALS['TSFE']->cObj->enableFields('index_config'));
                    $categoryTitle = $indexCfgRec['title'];
                } else {
                    $categoryTitle = LocalizationUtility::translate('indexingConfigurationHeader.' . $freeIndexUid, 'IndexedSearch');
                }
                $resultsets[$freeIndexUid]['categoryTitle'] = $categoryTitle;
            }
            // Write search statistics
            $this->writeSearchStat($searchData, $this->searchWords, $resultData['count'], [$tstamp1, $tstamp2, $tstamp3]);
        }
        $this->view->assign('resultsets', $resultsets);
        $this->view->assign('searchParams', $searchData);
        $this->view->assign('searchWords', $this->searchWords);
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
            'count' => $resultData['count'],
            'searchWords' => $searchWords
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
                if ($this->searchData['group'] == 'sections' && $freeIndexUid <= 0) {
                    $resultSectionsCount = count($this->resultSections);
                    $result['sectionText'] = sprintf(LocalizationUtility::translate('result.' . ($resultSectionsCount > 1 ? 'inNsections' : 'inNsection'), 'IndexedSearch'), $resultSectionsCount);
                }
            }
        }
        // Print a message telling which words in which sections we searched for
        if (substr($this->searchData['sections'], 0, 2) === 'rl') {
            $result['searchedInSectionInfo'] = LocalizationUtility::translate('result.inSection', 'IndexedSearch') . ' "' . $this->getPathFromPageId(substr($this->searchData['sections'], 4)) . '"';
        }
        return $result;
    }

    /**
     * Takes the array with resultrows as input and returns the result-HTML-code
     * Takes the "group" var into account: Makes a "section" or "flat" display.
     *
     * @param array $resultRows Result rows
     * @param int $freeIndexUid Pointing to which indexing configuration you want to search in. -1 means no filtering. 0 means only regular indexed content.
     * @return string HTML
     */
    protected function compileResultRows($resultRows, $freeIndexUid = -1)
    {
        $finalResultRows = [];
        // Transfer result rows to new variable,
        // performing some mapping of sub-results etc.
        $newResultRows = [];
        foreach ($resultRows as $row) {
            $id = md5($row['phash_grouping']);
            if (is_array($newResultRows[$id])) {
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
        if ($freeIndexUid <= 0 && $this->searchData['group'] == 'sections') {
            $rl2flag = substr($this->searchData['sections'], 0, 2) == 'rl';
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
                $sectionName = $this->getPathFromPageId($theId);
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
                    'sectionTitle' => $sectionTitleLinked
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
     * @return string HTML code
     */
    protected function compileSingleResultRow($row, $headerOnly = 0)
    {
        $specRowConf = $this->getSpecialConfigurationForResultRow($row);
        $resultData = $row;
        $resultData['headerOnly'] = $headerOnly;
        $resultData['CSSsuffix'] = $specRowConf['CSSsuffix'] ? '-' . $specRowConf['CSSsuffix'] : '';
        if ($this->multiplePagesType($row['item_type'])) {
            $dat = unserialize($row['cHashParams']);
            $pp = explode('-', $dat['key']);
            if ($pp[0] != $pp[1]) {
                $resultData['titleaddition'] = ', ' . LocalizationUtility::translate('result.page', 'IndexedSearch') . ' ' . $dat['key'];
            } else {
                $resultData['titleaddition'] = ', ' . LocalizationUtility::translate('result.pages', 'IndexedSearch') . ' ' . $pp[0];
            }
        }
        $title = $resultData['item_title'] . $resultData['titleaddition'];
        $title = $GLOBALS['TSFE']->csConvObj->crop('utf-8', $title, $this->settings['results.']['titleCropAfter'], $this->settings['results.']['titleCropSignifier']);
        // If external media, link to the media-file instead.
        if ($row['item_type']) {
            if ($row['show_resume']) {
                // Can link directly.
                $targetAttribute = '';
                if ($GLOBALS['TSFE']->config['config']['fileTarget']) {
                    $targetAttribute = ' target="' . htmlspecialchars($GLOBALS['TSFE']->config['config']['fileTarget']) . '"';
                }
                $title = '<a href="' . htmlspecialchars($row['data_filename']) . '"' . $targetAttribute . '>' . htmlspecialchars($title) . '</a>';
            } else {
                // Suspicious, so linking to page instead...
                $copiedRow = $row;
                unset($copiedRow['cHashParams']);
                $title = $this->linkPage($row['page_id'], htmlspecialchars($title), $copiedRow);
            }
        } else {
            // Else the page:
            // Prepare search words for markup in content:
            $markUpSwParams = [];
            if ($this->settings['forwardSearchWordsInResultLink']['_typoScriptNodeValue']) {
                if ($this->settings['forwardSearchWordsInResultLink']['no_cache']) {
                    $markUpSwParams = ['no_cache' => 1];
                }
                foreach ($this->searchWords as $d) {
                    $markUpSwParams['sword_list'][] = $d['sword'];
                }
            }
            $title = $this->linkPage($row['data_page_id'], htmlspecialchars($title), $row, $markUpSwParams);
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
        if ($pI['scheme']) {
            $targetAttribute = '';
            if ($GLOBALS['TSFE']->config['config']['fileTarget']) {
                $targetAttribute = ' target="' . htmlspecialchars($GLOBALS['TSFE']->config['config']['fileTarget']) . '"';
            }
            $resultData['path'] = '<a href="' . htmlspecialchars($row['data_filename']) . '"' . $targetAttribute . '>' . htmlspecialchars($row['data_filename']) . '</a>';
        } else {
            $pathId = $row['data_page_id'] ?: $row['page_id'];
            $pathMP = $row['data_page_id'] ? $row['data_page_mp'] : '';
            $pathStr = $this->getPathFromPageId($pathId, $pathMP);
            $resultData['path'] = $this->linkPage($pathId, $pathStr, [
                'cHashParams' => $row['cHashParams'],
                'data_page_type' => $row['data_page_type'],
                'data_page_mp' => $pathMP,
                'sys_language_uid' => $row['sys_language_uid']
            ]);
            // check if the access is restricted
            if (is_array($this->requiredFrontendUsergroups[$pathId]) && !empty($this->requiredFrontendUsergroups[$pathId])) {
                $resultData['access'] = '<img src="' . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('indexed_search')
                    . 'Resources/Public/Icons/FileTypes/locked.gif" width="12" height="15" vspace="5" title="'
                    . sprintf(LocalizationUtility::translate('result.memberGroups', 'IndexedSearch'), implode(',', array_unique($this->requiredFrontendUsergroups[$pathId])))
                    . '" alt="" />';
            }
        }
        // If there are subrows (eg. subpages in a PDF-file or if a duplicate page
        // is selected due to user-login (phash_grouping))
        if (is_array($row['_sub'])) {
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
        $rl = $GLOBALS['TSFE']->sys_page->getRootLine($pathId, $pathMP);
        $specConf = $this->settings['specialConfiguration']['0'];
        if (is_array($rl)) {
            foreach ($rl as $dat) {
                if (is_array($this->settings['specialConfiguration'][$dat['uid']])) {
                    $specConf = $this->settings['specialConfiguration'][$dat['uid']];
                    $specConf['_pid'] = $dat['uid'];
                    break;
                }
            }
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
        switch ((string)$this->searchData['sortOrder']) {
            case 'rank_count':
                return $row['order_val'] . ' ' . LocalizationUtility::translate('result.ratingMatches', 'IndexedSearch');
                break;
            case 'rank_first':
                return ceil(MathUtility::forceIntegerInRange((255 - $row['order_val']), 1, 255) / 255 * 100) . '%';
                break;
            case 'rank_flag':
                if ($this->firstRow['order_val2']) {
                    // (3 MSB bit, 224 is highest value of order_val1 currently)
                    $base = $row['order_val1'] * 256;
                    // 15-3 MSB = 12
                    $freqNumber = $row['order_val2'] / $this->firstRow['order_val2'] * pow(2, 12);
                    $total = MathUtility::forceIntegerInRange($base + $freqNumber, 0, 32767);
                    return ceil(log($total) / log(32767) * 100) . '%';
                }
                break;
            case 'rank_freq':
                $max = 10000;
                $total = MathUtility::forceIntegerInRange($row['order_val'], 0, $max);
                return ceil(log($total) / log($max) * 100) . '%';
                break;
            case 'crdate':
                return $GLOBALS['TSFE']->cObj->calcAge($GLOBALS['EXEC_TIME'] - $row['item_crdate'], 0);
                break;
            case 'mtime':
                return $GLOBALS['TSFE']->cObj->calcAge($GLOBALS['EXEC_TIME'] - $row['item_mtime'], 0);
                break;
            default:
                return ' ';
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
            if (is_array($this->settings['flagRendering'])) {
                /** @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObj */
                $cObj = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);
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
     * @return string <img> tag for icon
     */
    public function makeItemTypeIcon($imageType, $alt, $specRowConf)
    {
        // Build compound key if item type is 0, iconRendering is not used
        // and specialConfiguration.[pid].pageIcon was set in TS
        if ($imageType === '0' && $specRowConf['_pid'] && is_array($specRowConf['pageIcon']) && !is_array($this->settings['iconRendering'])) {
            $imageType .= ':' . $specRowConf['_pid'];
        }
        if (!isset($this->iconFileNameCache[$imageType])) {
            $this->iconFileNameCache[$imageType] = '';
            // If TypoScript is used to render the icon:
            if (is_array($this->settings['iconRendering'])) {
                /** @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObj */
                $cObj = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);
                $cObj->setCurrentVal($imageType);
                $typoScriptArray = $this->typoScriptService->convertPlainArrayToTypoScriptArray($this->settings['iconRendering']);
                $this->iconFileNameCache[$imageType] = $cObj->cObjGetSingle($this->settings['iconRendering']['_typoScriptNodeValue'], $typoScriptArray);
            } else {
                // Default creation / finding of icon:
                $icon = '';
                if ($imageType === '0' || substr($imageType, 0, 2) == '0:') {
                    if (is_array($specRowConf['pageIcon'])) {
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
                        $info = @getimagesize($fullPath);
                        $iconPath = \TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix($fullPath);
                        $this->iconFileNameCache[$imageType] = is_array($info) ? '<img src="' . $iconPath . '" ' . $info[3] . ' title="' . htmlspecialchars($alt) . '" alt="" />' : '';
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
        if ($row['show_resume']) {
            if (!$noMarkup) {
                $markedSW = '';
                $res = $this->getDatabaseConnection()->exec_SELECTquery('*', 'index_fulltext', 'phash=' . (int)$row['phash']);
                if ($ftdrow = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
                    // Cut HTTP references after some length
                    $content = preg_replace('/(http:\\/\\/[^ ]{' . $this->settings['results.']['hrefInSummaryCropAfter'] . '})([^ ]+)/i', '$1...', $ftdrow['fulltextdata']);
                    $markedSW = $this->markupSWpartsOfString($content);
                }
                $this->getDatabaseConnection()->sql_free_result($res);
            }
            if (!trim($markedSW)) {
                $outputStr = $GLOBALS['TSFE']->csConvObj->crop('utf-8', $row['item_description'], $length, $this->settings['results.']['summaryCropSignifier']);
                $outputStr = htmlspecialchars($outputStr);
            }
            $output = $outputStr ?: $markedSW;
            $output = $GLOBALS['TSFE']->csConv($output, 'utf-8');
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
        // Constants:
        $summaryMax = $this->settings['results.']['markupSW_summaryMax'];
        $postPreLgd = $this->settings['results.']['markupSW_postPreLgd'];
        $postPreLgd_offset = $this->settings['results.']['markupSW_postPreLgd_offset'];
        $divider = $this->settings['results.']['markupSW_divider'];
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
                $strLen = $GLOBALS['TSFE']->csConvObj->strlen('utf-8', $parts[$k]);
                $output[$k] = $parts[$k];
                // Possibly shorten string:
                if (!$k) {
                    // First entry at all (only cropped on the frontside)
                    if ($strLen > $postPreLgd) {
                        $output[$k] = $divider . preg_replace('/^[^[:space:]]+[[:space:]]/', '', $GLOBALS['TSFE']->csConvObj->crop('utf-8', $parts[$k], -($postPreLgd - $postPreLgd_offset)));
                    }
                } elseif ($summaryLgd > $summaryMax || !isset($parts[$k + 1])) {
                    // In case summary length is exceed OR if there are no more entries at all:
                    if ($strLen > $postPreLgd) {
                        $output[$k] = preg_replace('/[[:space:]][^[:space:]]+$/', '', $GLOBALS['TSFE']->csConvObj->crop('utf-8', $parts[$k], ($postPreLgd - $postPreLgd_offset))) . $divider;
                    }
                } else {
                    if ($strLen > $postPreLgd * 2) {
                        $output[$k] = preg_replace('/[[:space:]][^[:space:]]+$/', '', $GLOBALS['TSFE']->csConvObj->crop('utf-8', $parts[$k], ($postPreLgd - $postPreLgd_offset))) . $divider . preg_replace('/^[^[:space:]]+[[:space:]]/', '', $GLOBALS['TSFE']->csConvObj->crop('utf-8', $parts[$k], -($postPreLgd - $postPreLgd_offset)));
                    }
                }
                $summaryLgd += $GLOBALS['TSFE']->csConvObj->strlen('utf-8', $output[$k]);
                // Protect output:
                $output[$k] = htmlspecialchars($output[$k]);
                // If summary lgd is exceed, break the process:
                if ($summaryLgd > $summaryMax) {
                    break;
                }
            } else {
                $summaryLgd += $GLOBALS['TSFE']->csConvObj->strlen('utf-8', $strP);
                $output[$k] = '<strong class="tx-indexedsearch-redMarkup">' . htmlspecialchars($parts[$k]) . '</strong>';
            }
        }
        // Return result:
        return implode('', $output);
    }

    /**
     * Write statistics information to database for the search operation
     *
     * @param array $searchParams search params
     * @param array $searchWords Search Word array
     * @param int $count Number of hits
     * @param int $pt Milliseconds the search took
     * @return void
     */
    protected function writeSearchStat($searchParams, $searchWords, $count, $pt)
    {
        $insertFields = [
            'searchstring' => $this->sword,
            'searchoptions' => serialize([$searchParams, $searchWords, $pt]),
            'feuser_id' => (int)$GLOBALS['TSFE']->fe_user->user['uid'],
            // cookie as set or retrieved. If people has cookies disabled this will vary all the time
            'cookie' => $GLOBALS['TSFE']->fe_user->id,
            // Remote IP address
            'IP' => GeneralUtility::getIndpEnv('REMOTE_ADDR'),
            // Number of hits on the search
            'hits' => (int)$count,
            // Time stamp
            'tstamp' => $GLOBALS['EXEC_TIME']
        ];
        $this->getDatabaseConnection()->exec_INSERTquery('index_stat_search', $insertFields);
        $newId = $this->getDatabaseConnection()->sql_insert_id();
        if ($newId) {
            foreach ($searchWords as $val) {
                $insertFields = [
                    'word' => $val['sword'],
                    'index_stat_search_id' => $newId,
                    // Time stamp
                    'tstamp' => $GLOBALS['EXEC_TIME'],
                    // search page id for indexed search stats
                    'pageid' => $GLOBALS['TSFE']->id
                ];
                $this->getDatabaseConnection()->exec_INSERTquery('index_stat_word', $insertFields);
            }
        }
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
        // Shorten search-word string to max 200 bytes (does NOT take multibyte charsets into account - but never mind,
        // shortening the string here is only a run-away feature!)
        $searchWords = substr($this->sword, 0, 200);
        // Convert to UTF-8 + conv. entities (was also converted during indexing!)
        $searchWords = $GLOBALS['TSFE']->csConvObj->utf8_encode($searchWords, $GLOBALS['TSFE']->metaCharset);
        $searchWords = $GLOBALS['TSFE']->csConvObj->entities_to_utf8($searchWords, true);
        $sWordArray = false;
        if ($hookObj = $this->hookRequest('getSearchWords')) {
            $sWordArray = $hookObj->getSearchWords_splitSWords($searchWords, $defaultOperator);
        } else {
            // sentence
            if ($this->searchData['searchType'] == 20) {
                $sWordArray = [
                    [
                        'sword' => trim($searchWords),
                        'oper' => 'AND'
                    ]
                ];
            } else {
                // case-sensitive. Defines the words, which will be
                // operators between words
                $operatorTranslateTable = [
                    ['+', 'AND'],
                    ['|', 'OR'],
                    ['-', 'AND NOT'],
                    // Add operators for various languages
                    // Converts the operators to UTF-8 and lowercase
                    [$GLOBALS['TSFE']->csConvObj->conv_case('utf-8', $GLOBALS['TSFE']->csConvObj->utf8_encode(LocalizationUtility::translate('localizedOperandAnd', 'IndexedSearch'), $GLOBALS['TSFE']->renderCharset), 'toLower'), 'AND'],
                    [$GLOBALS['TSFE']->csConvObj->conv_case('utf-8', $GLOBALS['TSFE']->csConvObj->utf8_encode(LocalizationUtility::translate('localizedOperandOr', 'IndexedSearch'), $GLOBALS['TSFE']->renderCharset), 'toLower'), 'OR'],
                    [$GLOBALS['TSFE']->csConvObj->conv_case('utf-8', $GLOBALS['TSFE']->csConvObj->utf8_encode(LocalizationUtility::translate('localizedOperandNot', 'IndexedSearch'), $GLOBALS['TSFE']->renderCharset), 'toLower'), 'AND NOT']
                ];
                $swordArray = \TYPO3\CMS\IndexedSearch\Utility\IndexedSearchUtility::getExplodedSearchString($searchWords, $defaultOperator == 1 ? 'OR' : 'AND', $operatorTranslateTable);
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
        $lexerObjRef = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['lexer'] ?: \TYPO3\CMS\IndexedSearch\Lexer::class;
        $this->lexerObj = GeneralUtility::getUserObj($lexerObjRef);
        // Traverse the search word array
        foreach ($searchWords as $wordDef) {
            // No space in word (otherwise it might be a sentense in quotes like "there is").
            if (strpos($wordDef['sword'], ' ') === false) {
                // Split the search word by lexer:
                $res = $this->lexerObj->split2Words($wordDef['sword']);
                // Traverse lexer result and add all words again:
                foreach ($res as $word) {
                    $newSearchWords[] = [
                        'sword' => $word,
                        'oper' => $wordDef['oper']
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
     * @return void
     * @ignorevalidation $search
     */
    public function formAction($search = [])
    {
        $searchData = $this->initialize($search);
        // Adding search field value
        $this->view->assign('sword', $this->sword);
        // Additonal keyword => "Add to current search words"
        $showAdditionalKeywordSearch = $this->settings['clearSearchBox'] && $this->settings['clearSearchBox']['enableSubSearchCheckBox'];
        if ($showAdditionalKeywordSearch) {
            $this->view->assign('previousSearchWord', $this->settings['clearSearchBox'] ? '' : $this->sword);
        }
        $this->view->assign('showAdditionalKeywordSearch', $showAdditionalKeywordSearch);
        // Extended search
        if ($search['extendedSearch']) {
            // "Search for"
            $allSearchTypes = $this->getAllAvailableSearchTypeOptions();
            $this->view->assign('allSearchTypes', $allSearchTypes);
            $allDefaultOperands = $this->getAllAvailableOperandsOptions();
            $this->view->assign('allDefaultOperands', $allDefaultOperands);
            $showTypeSearch = !empty($allSearchTypes) || !empty($allDefaultOperands);
            $this->view->assign('showTypeSearch', $showTypeSearch);
            // "Search in"
            $allMediaTypes = $this->getAllAvailableMediaTypesOptions();
            $this->view->assign('allMediaTypes', $allMediaTypes);
            $allLanguageUids = $this->getAllAvailableLanguageOptions();
            $this->view->assign('allLanguageUids', $allLanguageUids);
            $showMediaAndLanguageSearch = !empty($allMediaTypes) || !empty($allLanguageUids);
            $this->view->assign('showMediaAndLanguageSearch', $showMediaAndLanguageSearch);
            // Sections
            $allSections = $this->getAllAvailableSectionsOptions();
            $this->view->assign('allSections', $allSections);
            // Free Indexing Configurations
            $allIndexConfigurations = $this->getAllAvailableIndexConfigurationsOptions();
            $this->view->assign('allIndexConfigurations', $allIndexConfigurations);
            // Sorting
            $allSortOrders = $this->getAllAvailableSortOrderOptions();
            $this->view->assign('allSortOrders', $allSortOrders);
            $allSortDescendings = $this->getAllAvailableSortDescendingOptions();
            $this->view->assign('allSortDescendings', $allSortDescendings);
            $showSortOrders = !empty($allSortOrders) || !empty($allSortDescendings);
            $this->view->assign('showSortOrders', $showSortOrders);
            // Limits
            $allNumberOfResults = $this->getAllAvailableNumberOfResultsOptions();
            $this->view->assign('allNumberOfResults', $allNumberOfResults);
            $allGroups = $this->getAllAvailableGroupOptions();
            $this->view->assign('allGroups', $allGroups);
        }
        $this->view->assign('searchParams', $searchData);
    }

    /**
     * TypoScript was not loaded
     */
    public function noTypoScriptAction()
    {
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
                1 => LocalizationUtility::translate('defaultOperands.1', 'IndexedSearch')
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
            '0' => LocalizationUtility::translate('languageUids.0', 'IndexedSearch')
        ];
        $blindSettings = $this->settings['blind'];
        if (!$blindSettings['languageUid']) {
            // Add search languages
            $res = $this->getDatabaseConnection()->exec_SELECTquery('*', 'sys_language', '1=1' . $GLOBALS['TSFE']->cObj->enableFields('sys_language'));
            if ($res) {
                while ($lang = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
                    $allOptions[$lang['uid']] = $lang['title'];
                }
            }
            // disable single entries by TypoScript
            $allOptions = $this->removeOptionsFromOptionList($allOptions, $blindSettings['languageUid']);
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
            $firstLevelMenu = $this->getMenuOfPages($this->searchRootPageIdList);
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
            '0' => LocalizationUtility::translate('indexingConfigurations.0', 'IndexedSearch')
        ];
        $blindSettings = $this->settings['blind'];
        if (!$blindSettings['indexingConfigurations']) {
            // add an additional index configuration
            if ($this->settings['defaultFreeIndexUidList']) {
                $uidList = GeneralUtility::intExplode(',', $this->settings['defaultFreeIndexUidList']);
                $indexCfgRecords = $this->getDatabaseConnection()->exec_SELECTgetRows('uid,title', 'index_config', 'uid IN (' . implode(',', $uidList) . ')' . $GLOBALS['TSFE']->cObj->enableFields('index_config'), '', '', '', 'uid');
                foreach ($uidList as $uidValue) {
                    if (is_array($indexCfgRecords[$uidValue])) {
                        $allOptions[$uidValue] = $indexCfgRecords[$uidValue]['title'];
                    }
                }
            }
            // disable single entries by TypoScript
            $allOptions = $this->removeOptionsFromOptionList($allOptions, $blindSettings['indexingConfigurations']);
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
        $allOptions = $this->removeOptionsFromOptionList($allOptions, $blindSettings['sortOrder.']);
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
        if (!$blindSettings['groupBy']) {
            $allOptions = [
                'sections' => LocalizationUtility::translate('groupBy.sections', 'IndexedSearch'),
                'flat' => LocalizationUtility::translate('groupBy.flat', 'IndexedSearch')
            ];
        }
        // disable single entries by TypoScript
        $allOptions = $this->removeOptionsFromOptionList($allOptions, $blindSettings['groupBy.']);
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
        if (!$blindSettings['descending']) {
            $allOptions = [
                0 => LocalizationUtility::translate('sortOrders.descending', 'IndexedSearch'),
                1 => LocalizationUtility::translate('sortOrders.ascending', 'IndexedSearch')
            ];
        }
        // disable single entries by TypoScript
        $allOptions = $this->removeOptionsFromOptionList($allOptions, $blindSettings['descending.']);
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
        $blindSettings = $this->settings['blind'];
        if (!$blindSettings['numberOfResults']) {
            $allOptions = [
                10 => 10,
                25 => 25,
                50 => 50,
                100 => 100
            ];
        }
        // disable single entries by TypoScript
        $allOptions = $this->removeOptionsFromOptionList($allOptions, $blindSettings['numberOfResults']);
        return $allOptions;
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
     * @param string $linkText Title to link (must already be escaped for HTML output)
     * @param array $row Result row
     * @param array $markUpSwParams Additional parameters for marking up seach words
     * @return string <A> tag wrapped title string.
     * @todo make use of the UriBuilder
     */
    protected function linkPage($pageUid, $linkText, $row = [], $markUpSwParams = [])
    {
        // Parameters for link
        $urlParameters = (array)unserialize($row['cHashParams']);
        // Add &type and &MP variable:
        if ($row['data_page_mp']) {
            $urlParameters['MP'] = $row['data_page_mp'];
        }
        $urlParameters['L'] = intval($row['sys_language_uid']);
        // markup-GET vars:
        $urlParameters = array_merge($urlParameters, $markUpSwParams);
        // This will make sure that the path is retrieved if it hasn't been
        // already. Used only for the sake of the domain_record thing.
        if (!is_array($this->domainRecords[$pageUid])) {
            $this->getPathFromPageId($pageUid);
        }
        $target = '';
        // If external domain, then link to that:
        if (!empty($this->domainRecords[$pageUid])) {
            $scheme = GeneralUtility::getIndpEnv('TYPO3_SSL') ? 'https://' : 'http://';
            $firstDomain = reset($this->domainRecords[$pageUid]);
            $additionalParams = '';
            if (is_array($urlParameters) && !empty($urlParameters)) {
                $additionalParams = GeneralUtility::implodeArrayForUrl('', $urlParameters);
            }
            $uri = $scheme . $firstDomain . '/index.php?id=' . $pageUid . $additionalParams;
            if ($target = $this->settings['detectDomainRecords.']['target']) {
                $target = ' target="' . $target . '"';
            }
        } else {
            $uriBuilder = $this->controllerContext->getUriBuilder();
            $uri = $uriBuilder->setTargetPageUid($pageUid)->setTargetPageType($row['data_page_type'])->setUseCacheHash(true)->setArguments($urlParameters)->build();
        }
        return '<a href="' . htmlspecialchars($uri) . '"' . $target . '>' . $linkText . '</a>';
    }

    /**
     * Return the menu of pages used for the selector.
     *
     * @param int $pageUid Page ID for which to return menu
     * @return array Menu items (for making the section selector box)
     */
    protected function getMenuOfPages($pageUid)
    {
        if ($this->settings['displayLevelxAllTypes']) {
            $menu = [];
            $res = $this->getDatabaseConnection()->exec_SELECTquery('title,uid', 'pages', 'pid=' . (int)$pageUid . $GLOBALS['TSFE']->cObj->enableFields('pages'), '', 'sorting');
            while ($row = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
                $menu[$row['uid']] = $GLOBALS['TSFE']->sys_page->getPageOverlay($row);
            }
            $this->getDatabaseConnection()->sql_free_result($res);
        } else {
            $menu = $GLOBALS['TSFE']->sys_page->getMenu($pageUid);
        }
        return $menu;
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
            $rl = $GLOBALS['TSFE']->sys_page->getRootLine($id, $pathMP);
            $path = '';
            $pageCount = count($rl);
            if (is_array($rl) && !empty($rl)) {
                $breadcrumbWrap = isset($this->settings['breadcrumbWrap']) ? $this->settings['breadcrumbWrap'] : '/';
                $breadcrumbWraps = $GLOBALS['TSFE']->tmpl->splitConfArray(['wrap' => $breadcrumbWrap], $pageCount);
                foreach ($rl as $k => $v) {
                    // Check fe_user
                    if ($v['fe_group'] && ($v['uid'] == $id || $v['extendToSubpages'])) {
                        $this->requiredFrontendUsergroups[$id][] = $v['fe_group'];
                    }
                    // Check sys_domain
                    if ($this->settings['detectDomainRcords']) {
                        $domainName = $this->getFirstSysDomainRecordForPage($v['uid']);
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
            $this->pathCache[$identStr] = $path;
        }
        return $this->pathCache[$identStr];
    }

    /**
     * Gets the first sys_domain record for the page, $id
     *
     * @param int $id Page id
     * @return string Domain name
     */
    protected function getFirstSysDomainRecordForPage($id)
    {
        $res = $this->getDatabaseConnection()->exec_SELECTquery('domainName', 'sys_domain', 'pid=' . (int)$id . $GLOBALS['TSFE']->cObj->enableFields('sys_domain'), '', 'sorting');
        $row = $this->getDatabaseConnection()->sql_fetch_assoc($res);
        return rtrim($row['domainName'], '/');
    }

    /**
     * simple function to initialize possible external parsers
     * feeds the $this->externalParsers array
     *
     * @return void
     */
    protected function initializeExternalParsers()
    {
        // Initialize external document parsers for icon display and other soft operations
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['external_parsers'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['external_parsers'] as $extension => $_objRef) {
                $this->externalParsers[$extension] = GeneralUtility::getUserObj($_objRef);
                // Init parser and if it returns FALSE, unset its entry again
                if (!$this->externalParsers[$extension]->softInit($extension)) {
                    unset($this->externalParsers[$extension]);
                }
            }
        }
    }

    /**
     * Returns an object reference to the hook object if any
     *
     * @param string $functionName Name of the function you want to call / hook key
     * @return object|NULL Hook object, if any. Otherwise NULL.
     */
    protected function hookRequest($functionName)
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
     * Returns if an item type is a multipage item type
     *
     * @param string $item_type Item type
     * @return bool TRUE if multipage capable
     */
    protected function multiplePagesType($item_type)
    {
        return is_object($this->externalParsers[$item_type]) && $this->externalParsers[$item_type]->isMultiplePageExtension($item_type);
    }

    /**
     * Getter for database connection
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * Load settings and apply stdWrap to them
     */
    protected function loadSettings()
    {
        if (!is_array($this->settings['results.'])) {
            $this->settings['results.'] = [];
        }
        $typoScriptArray = $this->typoScriptService->convertPlainArrayToTypoScriptArray($this->settings['results']);

        $this->settings['results.']['summaryCropAfter'] = MathUtility::forceIntegerInRange(
            $GLOBALS['TSFE']->cObj->stdWrap($typoScriptArray['summaryCropAfter'], $typoScriptArray['summaryCropAfter.']),
            10, 5000, 180
        );
        $this->settings['results.']['summaryCropSignifier'] = $GLOBALS['TSFE']->cObj->stdWrap($typoScriptArray['summaryCropSignifier'], $typoScriptArray['summaryCropSignifier.']);
        $this->settings['results.']['titleCropAfter'] = MathUtility::forceIntegerInRange(
            $GLOBALS['TSFE']->cObj->stdWrap($typoScriptArray['titleCropAfter'], $typoScriptArray['titleCropAfter.']),
            10, 500, 50
        );
        $this->settings['results.']['titleCropSignifier'] = $GLOBALS['TSFE']->cObj->stdWrap($typoScriptArray['titleCropSignifier'], $typoScriptArray['titleCropSignifier.']);
        $this->settings['results.']['markupSW_summaryMax'] = MathUtility::forceIntegerInRange(
            $GLOBALS['TSFE']->cObj->stdWrap($typoScriptArray['markupSW_summaryMax'], $typoScriptArray['markupSW_summaryMax.']),
            10, 5000, 300
        );
        $this->settings['results.']['markupSW_postPreLgd'] = MathUtility::forceIntegerInRange(
            $GLOBALS['TSFE']->cObj->stdWrap($typoScriptArray['markupSW_postPreLgd'], $typoScriptArray['markupSW_postPreLgd.']),
            1, 500, 60
        );
        $this->settings['results.']['markupSW_postPreLgd_offset'] = MathUtility::forceIntegerInRange(
            $GLOBALS['TSFE']->cObj->stdWrap($typoScriptArray['markupSW_postPreLgd_offset'], $typoScriptArray['markupSW_postPreLgd_offset.']),
            1, 50, 5
        );
        $this->settings['results.']['markupSW_divider'] = $GLOBALS['TSFE']->cObj->stdWrap($typoScriptArray['markupSW_divider'], $typoScriptArray['markupSW_divider.']);
        $this->settings['results.']['hrefInSummaryCropAfter'] = MathUtility::forceIntegerInRange(
            $GLOBALS['TSFE']->cObj->stdWrap($typoScriptArray['hrefInSummaryCropAfter'], $typoScriptArray['hrefInSummaryCropAfter.']),
            10, 400, 60
        );
        $this->settings['results.']['hrefInSummaryCropSignifier'] = $GLOBALS['TSFE']->cObj->stdWrap($typoScriptArray['hrefInSummaryCropSignifier'], $typoScriptArray['hrefInSummaryCropSignifier.']);
    }
}
