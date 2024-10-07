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

namespace TYPO3\CMS\IndexedSearch\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Exception\Page\RootLineException;
use TYPO3\CMS\Core\Html\HtmlParser;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Typolink\LinkFactory;
use TYPO3\CMS\Frontend\Typolink\LinkResult;
use TYPO3\CMS\Frontend\Typolink\LinkResultInterface;
use TYPO3\CMS\IndexedSearch\Domain\Repository\IndexSearchRepository;
use TYPO3\CMS\IndexedSearch\Lexer;
use TYPO3\CMS\IndexedSearch\Pagination\SlicePaginator;
use TYPO3\CMS\IndexedSearch\Type\DefaultOperand;
use TYPO3\CMS\IndexedSearch\Type\GroupOption;
use TYPO3\CMS\IndexedSearch\Type\IndexingConfiguration;
use TYPO3\CMS\IndexedSearch\Type\MediaType;
use TYPO3\CMS\IndexedSearch\Type\SearchType;
use TYPO3\CMS\IndexedSearch\Type\SectionType;
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
    protected string $sword = '';
    protected array $searchWords = [];

    /**
     * This is the id of the site root.
     * This value may be a comma separated list of integer (prepared for this)
     * Root-page PIDs to search in (rl0 field where clause, see initialize() function)
     *
     * If this value is set to less than zero (e.g. -1) searching will happen
     * in ALL of the page tree with no regard to branches at all.
     */
    protected string|int $searchRootPageIdList = 0;

    protected int $defaultResultNumber = 10;
    protected array $availableResultsNumbers = [];
    protected array $externalParsers = [];

    /**
     * Will hold the first row in result - used to calculate relative hit-ratings.
     */
    protected array $firstRow = [];

    /**
     * Required fe_groups memberships for display of a result.
     */
    protected array $requiredFrontendUsergroups = [];

    /**
     * Page tree sections for search result.
     */
    protected array $resultSections = [];

    protected array $pathCache = [];

    public function __construct(
        private readonly Context $context,
        private readonly IndexSearchRepository $searchRepository,
        private readonly TypoScriptService $typoScriptService,
        private readonly Lexer $lexer,
        private readonly LinkFactory $linkFactory,
    ) {}

    /**
     * sets up all necessary object for searching
     */
    protected function initialize(array $searchData = []): array
    {
        if (!is_array($searchData)) {
            $searchData = [];
        }

        // Sets availableResultsNumbers - has to be called before request settings are read to avoid DoS attack
        $this->availableResultsNumbers = array_filter(GeneralUtility::intExplode(',', (string)($this->settings['blind']['numberOfResults'] ?? '')));

        // Sets default result number if at least one availableResultsNumbers exists
        if (isset($this->availableResultsNumbers[0])) {
            $this->defaultResultNumber = $this->availableResultsNumbers[0];
        }

        $this->loadSettings();

        // setting default values
        if (is_array($this->settings['defaultOptions'])) {
            $searchData = array_merge($this->settings['defaultOptions'], $searchData);
        }
        // Hand in the current site language as languageUid
        $searchData['languageUid'] = $this->context->getPropertyFromAspect('language', 'id', 0);

        $this->initializeExternalParsers();
        // If "_sections" is set, this value overrides any existing value.
        if ($searchData['_sections'] ?? false) {
            $searchData['sections'] = $searchData['_sections'];
        }
        // If "_sections" is set, this value overrides any existing value.
        if (($searchData['_freeIndexUid'] ?? '') !== '' && ($searchData['_freeIndexUid'] ?? '') !== '_') {
            $searchData['freeIndexUid'] = $searchData['_freeIndexUid'];
        }
        $searchData['numberOfResults'] = $this->getNumberOfResults((int)($searchData['numberOfResults'] ?? 0));
        // This gets the search-words into the $searchWordArray
        $this->sword = $searchData['sword'] ?? '';
        // Add previous search words to current
        if (($searchData['sword_prev_include'] ?? false) && ($searchData['sword_prev'] ?? false)) {
            $this->sword = trim($searchData['sword_prev']) . ' ' . $this->sword;
        }
        // This is the id of the site root.
        // This value may be a commalist of integer (prepared for this)
        $localRootLine = $this->request->getAttribute('frontend.page.information')->getLocalRootLine();
        $this->searchRootPageIdList = (int)$localRootLine[0]['uid'];
        // Setting the list of root PIDs for the search. Notice, these page IDs MUST
        // have a TypoScript with root flag on them! Basically this list is used
        // to select on the "rl0" field and page ids are registered as "rl0" only if
        // a TypoScript record with root flag is there.
        // This happens AFTER the use of $this->searchRootPageIdList above because
        // the above will then fetch the menu for the CURRENT site - regardless
        // of this kind of searching here. Thus a general search will lookup in
        // the WHOLE database while a specific section search will take the current sections.
        $rootPidListFromSettings = (string)($this->settings['rootPidList'] ?? '');
        if ($rootPidListFromSettings) {
            $this->searchRootPageIdList = implode(',', GeneralUtility::intExplode(',', $rootPidListFromSettings));
        }
        $this->searchRepository->initialize($this->settings, $searchData, $this->externalParsers, $this->searchRootPageIdList);
        // $this->searchData is used in $this->getSearchWords
        $this->searchWords = $this->getSearchWords($searchData, (bool)$searchData['defaultOperand']);

        return $searchData;
    }

    /**
     * Performs the search, the display and writing stats
     *
     * @Extbase\IgnoreValidation("search")
     */
    public function searchAction(array $search = []): ResponseInterface
    {
        // check if TypoScript is loaded
        if (!isset($this->settings['results'])) {
            return $this->redirect('noTypoScript');
        }

        $searchData = $this->initialize($search);
        // Find free index uid:
        // @todo: what exactly is this? Apparently, it can either be an int or a string (╯°□°）╯︵ ┻━┻
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

        $indexCfgs = GeneralUtility::intExplode(',', (string)$freeIndexUid);
        $resultsets = [];
        foreach ($indexCfgs as $freeIndexUid) {
            // Get result rows
            $resultData = $this->searchRepository->doSearch($this->searchWords, $freeIndexUid);

            // Display search results
            $resultsets[$freeIndexUid] = $this->getDisplayResults($searchData, $this->searchWords, $resultData, $freeIndexUid);

            // Create header if we are searching more than one indexing configuration
            if (count($indexCfgs) > 1) {
                if ($freeIndexUid > 0) {
                    $indexCfgRec = $this->searchRepository->getIndexConfigurationById($freeIndexUid);
                    if (is_array($indexCfgRec)) {
                        $categoryTitle = LocalizationUtility::translate('indexingConfigurationHeader.' . $freeIndexUid, 'IndexedSearch');
                        $categoryTitle = $categoryTitle ?: $indexCfgRec['title'];
                        $resultsets[$freeIndexUid]['categoryTitle'] = $categoryTitle;
                    }
                } else {
                    $categoryTitle = LocalizationUtility::translate('indexingConfigurationHeader.' . $freeIndexUid, 'IndexedSearch');
                    $resultsets[$freeIndexUid]['categoryTitle'] = $categoryTitle;
                }
            }
            // Write search statistics
            $pageId = $this->request->getAttribute('frontend.page.information')->getId();
            $this->searchRepository->writeSearchStat($pageId, $this->searchWords ?: []);
        }
        $this->view->assign('resultsets', $resultsets);
        $this->view->assign('searchParams', $searchData);
        $this->view->assign('firstRow', $this->firstRow);
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
     * @param array|bool $resultData Array with result rows, count, first row.
     * @param int $freeIndexUid Pointing to which indexing configuration you want to search in. -1 means no filtering. 0 means only regular indexed content.
     */
    protected function getDisplayResults(array $searchData, array $searchWords, array|bool $resultData, int $freeIndexUid = -1): array
    {
        $result = [
            'count' => $resultData['count'] ?? 0,
            'searchWords' => $searchWords,
            'pagination' => null,
        ];
        // Perform display of result rows array
        if ($resultData) {
            // Set first selected row (for calculation of ranking later)
            $this->firstRow = $resultData['firstRow'];
            // Result display here
            $result['rows'] = $this->compileResultRows($searchData, $resultData['resultRows'], $freeIndexUid);
            $result['affectedSections'] = $this->resultSections;
            // Browsing box
            if ($resultData['count']) {
                // could we get this in the view?
                if (($searchData['group'] ?? '') === GroupOption::SECTIONS->value && $freeIndexUid <= 0) {
                    $resultSectionsCount = count($this->resultSections);
                    $result['sectionText'] = sprintf(LocalizationUtility::translate('result.' . ($resultSectionsCount > 1 ? 'inNsections' : 'inNsection'), 'IndexedSearch') ?? '', $resultSectionsCount);
                }
            }

            $pointer = (int)($searchData['pointer'] ?? 0);
            $paginator = new SlicePaginator(
                $result['rows'],
                $pointer + 1,
                $resultData['count'],
                $searchData['numberOfResults'],
            );
            $result['pagination'] = new SimplePagination($paginator);
        }
        // Print a message telling which words in which sections we searched for
        if (str_starts_with($searchData['sections'], 'rl')) {
            $result['searchedInSectionInfo'] = (LocalizationUtility::translate('result.inSection', 'IndexedSearch') ?? '') . ' "' . $this->getPathFromPageId((int)substr($searchData['sections'], 4)) . '"';
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
    protected function compileResultRows(array $searchData, array $resultRows, int $freeIndexUid = -1): array
    {
        $finalResultRows = [];
        // Transfer result rows to new variable,
        // performing some mapping of sub-results etc.
        $newResultRows = [];
        foreach ($resultRows as $row) {
            $id = md5((string)$row['phash_grouping']);
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
        if ($freeIndexUid <= 0 && ($searchData['group'] ?? '') === GroupOption::SECTIONS->value) {
            $rl2flag = str_starts_with($searchData['sections'], 'rl');
            $sections = [];
            foreach ($resultRows as $row) {
                $id = $row['rl0'] . '-' . $row['rl1'] . ($rl2flag ? '-' . $row['rl2'] : '');
                $sections[$id][] = $row;
            }
            $this->resultSections = [];
            foreach ($sections as $id => $resultRows) {
                $rlParts = explode('-', $id);
                if ($rlParts[2] ?? null) {
                    $theId = $rlParts[2];
                    $theRLid = 'rl2_' . $rlParts[2];
                } elseif ($rlParts[1] ?? null) {
                    $theId = $rlParts[1];
                    $theRLid = 'rl1_' . $rlParts[1];
                } else {
                    $theId = $rlParts[0] ?? '0';
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
                    $finalResultRows[] = $this->compileSingleResultRow($searchData, $row);
                }
            }
        } else {
            // flat mode or no sections at all
            foreach ($resultRows as $row) {
                $finalResultRows[] = $this->compileSingleResultRow($searchData, $row);
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
    protected function compileSingleResultRow(array $searchData, array $row, int $headerOnly = 0): array
    {
        $typoScriptConfigArray = $this->request->getAttribute('frontend.typoscript')->getConfigArray();
        $resultData = $row;
        $resultData['headerOnly'] = $headerOnly;
        if (isset($row['static_page_arguments']) && $this->multiplePagesType($row['item_type'])) {
            try {
                $dat = json_decode($row['static_page_arguments'], true, 512, JSON_THROW_ON_ERROR);
                if (is_string($dat['key'] ?? null) && $dat['key'] !== '') {
                    $pp = explode('-', $dat['key']);
                    if ($pp[0] !== $pp[1]) {
                        $resultData['titleaddition'] = ', ' . LocalizationUtility::translate('result.pages', 'IndexedSearch') . ' ' . $dat['key'];
                    } else {
                        $resultData['titleaddition'] = ', ' . LocalizationUtility::translate('result.page', 'IndexedSearch') . ' ' . $pp[0];
                    }
                }
            } catch (\JsonException) {
            }
        }
        $title = $resultData['item_title'] . ($resultData['titleaddition'] ?? '');
        $title = GeneralUtility::fixed_lgd_cs($title, (int)$this->settings['results.']['titleCropAfter'], $this->settings['results.']['titleCropSignifier']);
        // If external media, link to the media-file instead.
        if ($row['item_type']) {
            if ($row['show_resume']) {
                $targetAttribute = '';
                if ($typoScriptConfigArray['fileTarget'] ?? false) {
                    $targetAttribute = ' target="' . htmlspecialchars($typoScriptConfigArray['fileTarget']) . '"';
                }
                $title = '<a href="' . htmlspecialchars($row['data_filename']) . '"' . $targetAttribute . '>' . htmlspecialchars($title) . '</a>';
            } else {
                // Suspicious, so linking to page instead...
                $copiedRow = $row;
                unset($copiedRow['static_page_arguments']);
                $title = LinkResult::adapt($this->linkPage((int)$row['page_id'], $copiedRow, $title))->getHtml();
            }
        } else {
            // Else the page
            $title = LinkResult::adapt($this->linkPage((int)$row['data_page_id'], $row, $title))->getHtml();
        }
        $resultData['title'] = $title;
        $resultData['description'] = $this->makeDescription(
            $row,
            !($searchData['extResume'] && !$headerOnly),
            $this->settings['results.']['summaryCropAfter']
        );
        $resultData['size'] = GeneralUtility::formatSize($row['item_size']);
        $resultData['created'] = $row['item_crdate'];
        $resultData['modified'] = $row['item_mtime'];
        $pI = parse_url($row['data_filename']);
        if ($pI['scheme'] ?? false) {
            $targetAttribute = '';
            if ($typoScriptConfigArray['fileTarget'] ?? false) {
                $targetAttribute = ' target="' . htmlspecialchars($typoScriptConfigArray['fileTarget']) . '"';
            }
            $resultData['pathTitle'] = $row['data_filename'];
            $resultData['pathUri'] = $row['data_filename'];
            $resultData['path'] = '<a href="' . htmlspecialchars($row['data_filename']) . '"' . $targetAttribute . '>' . htmlspecialchars($row['data_filename']) . '</a>';
        } else {
            $pathId = $row['data_page_id'] ?: $row['page_id'];
            $pathMP = $row['data_page_id'] ? $row['data_page_mp'] : '';
            $pathStr = $this->getPathFromPageId($pathId, $pathMP);
            $pathLinkResult = $this->linkPage((int)$pathId, $row, $pathStr);

            $resultData['pathTitle'] = $pathStr;
            $resultData['pathUri'] = $pathLinkResult->getUrl();
            $resultData['path'] = LinkResult::adapt($pathLinkResult)->getHtml();

            // check if the access is restricted
            if (is_array($this->requiredFrontendUsergroups[$pathId]) && !empty($this->requiredFrontendUsergroups[$pathId])) {
                $lockedIcon = PathUtility::getPublicResourceWebPath('EXT:indexed_search/Resources/Public/Icons/FileTypes/locked.gif');
                $resultData['access'] = '<img src="' . htmlspecialchars($lockedIcon) . '"'
                    . ' width="12" height="15" vspace="5" title="'
                    . sprintf(LocalizationUtility::translate('result.memberGroups', 'IndexedSearch') ?? '', implode(',', array_unique($this->requiredFrontendUsergroups[$pathId])))
                    . '" alt="" />';
            }
        }
        // If there are subrows (e.g. subpages in a PDF-file or if a duplicate page
        // is selected due to user-login (phash_grouping))
        if (is_array($row['_sub'] ?? false)) {
            $resultData['subresults'] = [];
            if ($this->multiplePagesType($row['item_type'])) {
                $resultData['subresults']['header'] = LocalizationUtility::translate('result.otherMatching', 'IndexedSearch');
                foreach ($row['_sub'] as $subRow) {
                    $resultData['subresults']['items'][] = $this->compileSingleResultRow($searchData, $subRow, 1);
                }
            } else {
                $resultData['subresults']['header'] = LocalizationUtility::translate('result.otherMatching', 'IndexedSearch');
                $resultData['subresults']['info'] = LocalizationUtility::translate('result.otherPageAsWell', 'IndexedSearch');
            }
        }
        return $resultData;
    }

    /**
     * Returns the resume for the search-result.
     *
     * @param bool $noMarkup If noMarkup is FALSE, then the index_fulltext table is used to select the content of the page, split it with regex to display the search words in the text.
     * @todo overwork this
     */
    protected function makeDescription(array $row, bool $noMarkup = false, int $length = 180): string
    {
        $markedSW = '';
        $outputStr = '';
        if ($row['show_resume']) {
            if (!$noMarkup) {
                $ftdrow = $this->searchRepository->getFullTextRowByPhash($row['phash']);
                if (is_array($ftdrow)) {
                    // Cut HTTP references after some length
                    $content = preg_replace('/(http:\\/\\/[^ ]{' . $this->settings['results.']['hrefInSummaryCropAfter'] . '})([^ ]+)/i', '$1...', $ftdrow['fulltextdata']);
                    $markedSW = $this->markupSWpartsOfString($content);
                }
            }
            if (!trim($markedSW)) {
                $outputStr = GeneralUtility::fixed_lgd_cs($row['item_description'], (int)$length, $this->settings['results.']['summaryCropSignifier']);
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
     */
    protected function markupSWpartsOfString(string $str): string
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
        $parts = preg_split('/' . $regExString . '/iu', ' ' . $str . ' ', 20000, PREG_SPLIT_DELIM_CAPTURE);
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
            if ($k % 2 === 0) {
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
     * Splits the search word input into an array where each word is represented by an array with key "sword"
     * holding the search word and key "oper" holding the SQL operator (e.g. AND, OR)
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
     * @param bool $useDefaultOperator If TRUE, the default operator will be OR, not AND
     */
    protected function getSearchWords(array $searchData, bool $useDefaultOperator): array
    {
        // Shorten search-word string to max 200 bytes - shortening the string here is only a run-away feature!
        $searchWords = mb_substr($this->sword, 0, 200);
        if ((int)$searchData['searchType'] === SearchType::SENTENCE->value) {
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
            $defaultOperator = $useDefaultOperator ? 'OR' : 'AND';
            $swordArray = IndexedSearchUtility::getExplodedSearchString($searchWords, $defaultOperator, $operatorTranslateTable);
            $sWordArray = $this->procSearchWordsByLexer($swordArray);
        }
        return $sWordArray;
    }

    /**
     * Post-process the search word array, so it will match the words that was indexed (including case-folding if any).
     * If any words are split into multiple words (e.g. CJK will be!) the operator of the main word will remain.
     *
     * @param array $searchWords Search word array
     * @return array Search word array, processed through lexer
     */
    protected function procSearchWordsByLexer(array $searchWords): array
    {
        // Traverse the search word array
        $newSearchWords = [];
        foreach ($searchWords as $wordDef) {
            // No space in word (otherwise it might be a sentence in quotes like "there is").
            if (!str_contains($wordDef['sword'], ' ')) {
                // Split the search word by lexer:
                $res = $this->lexer->split2Words($wordDef['sword']);
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
     * @Extbase\IgnoreValidation("search")
     */
    public function formAction(array $search = []): ResponseInterface
    {
        // check if TypoScript is loaded
        if (!isset($this->settings['results'])) {
            return $this->redirect('noTypoScript');
        }

        $searchData = $this->initialize($search);
        // Adding search field value
        $this->view->assign('sword', $this->sword);
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
    protected function getAllAvailableSearchTypeOptions(): array
    {
        $allOptions = [];
        $blindSettings = $this->settings['blind'];
        if (!$blindSettings['searchType']) {
            foreach (SearchType::cases() as $searchType) {
                $typeNum = $searchType->value;
                $allOptions[$typeNum] = LocalizationUtility::translate('searchTypes.' . $typeNum, 'IndexedSearch');
            }
        }
        // disable single entries by TypoScript
        return $this->removeOptionsFromOptionList($allOptions, $blindSettings['searchType']);
    }

    /**
     * get the values for the "defaultOperand" selector
     *
     * @return array Associative array with options
     */
    protected function getAllAvailableOperandsOptions(): array
    {
        $allOptions = [];
        $blindSettings = $this->settings['blind'];
        if (!$blindSettings['defaultOperand']) {
            foreach (DefaultOperand::cases() as $defaultOperand) {
                $operand = $defaultOperand->value;
                $allOptions[$operand] = LocalizationUtility::translate('defaultOperands.' . $operand, 'IndexedSearch');
            }
        }
        // disable single entries by TypoScript
        return $this->removeOptionsFromOptionList($allOptions, $blindSettings['defaultOperand']);
    }

    /**
     * get the values for the "media type" selector
     *
     * @return array Associative array with options
     */
    protected function getAllAvailableMediaTypesOptions(): array
    {
        $allOptions = [];
        $blindSettings = $this->settings['blind'];
        if (!$blindSettings['mediaType']) {
            foreach ([MediaType::ALL_MEDIA, MediaType::INTERNAL_PAGES, MediaType::ALL_EXTERNAL] as $mediaTypeCase) {
                $mediaType = $mediaTypeCase->value;
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
                if (!empty($additionalMedia) && !in_array($extension, $additionalMedia, true)) {
                    continue;
                }
                if ($name = $obj->searchTypeMediaTitle($extension)) {
                    $translatedName = LocalizationUtility::translate('mediaTypes.' . $extension, 'IndexedSearch');
                    $allOptions[$extension] = $translatedName ?: $name;
                }
            }
        }
        // disable single entries by TypoScript
        return $this->removeOptionsFromOptionList($allOptions, $blindSettings['mediaType']);
    }

    /**
     * get the values for the "section" selector
     * Here values like "rl1_" and "rl2_" + a root level 1/2 id can be added
     * to perform searches in root level 1+2 specifically. The id-values can even
     * be comma-separated. e.g. "rl1_1,2" would search for stuff inside pages on
     * menu-level 1 which has the uids 1 and 2.
     *
     * @return array Associative array with options
     */
    protected function getAllAvailableSectionsOptions(): array
    {
        $allOptions = [];
        $blindSettings = $this->settings['blind'];
        if (!$blindSettings['sections']) {
            foreach (SectionType::cases() as $sectionType) {
                $section = $sectionType->value;
                $allOptions[$section] = LocalizationUtility::translate('sections.' . $section, 'IndexedSearch');
            }
        }
        // Creating levels for section menu:
        // This selects the first and secondary menus for the "sections" selector - so we can search in sections and sub-sections.
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
        return $this->removeOptionsFromOptionList($allOptions, $blindSettings['sections']);
    }

    /**
     * get the values for the "freeIndexUid" selector
     *
     * @return array Associative array with options
     */
    protected function getAllAvailableIndexConfigurationsOptions(): array
    {
        foreach ([IndexingConfiguration::ALL_MIXED, IndexingConfiguration::ALL_CATEGORIZED, IndexingConfiguration::PAGES] as $indexingConfiguration) {
            $value = $indexingConfiguration->value;
            $allOptions[$value] = LocalizationUtility::translate('indexingConfigurations.' . $value, 'IndexedSearch');
        }
        $blindSettings = $this->settings['blind'];
        if (!($blindSettings['indexingConfigurations'] ?? false)) {
            // add an additional index configuration
            $defaultFreeIndexUidList = (string)($this->settings['defaultFreeIndexUidList'] ?? '');
            if ($defaultFreeIndexUidList) {
                $uidList = GeneralUtility::intExplode(',', $defaultFreeIndexUidList);
                foreach ($uidList as $uid) {
                    $row = $this->searchRepository->getIndexConfigurationById($uid);
                    if (is_array($row)) {
                        $indexId = (int)$row['uid'];
                        $title = LocalizationUtility::translate('indexingConfigurations.' . $indexId, 'IndexedSearch');
                        $allOptions[$indexId] = $title ?: $row['title'];
                    }
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
     * Here values like "rl1_" and "rl2_" + a root level 1/2 id can be added
     * to perform searches in root level 1+2 specifically. The id-values can even
     * be comma-separated. e.g. "rl1_1,2" would search for stuff inside pages on
     * menu-level 1 which has the uids 1 and 2.
     *
     * @return array Associative array with options
     */
    protected function getAllAvailableSortOrderOptions(): array
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
        return $this->removeOptionsFromOptionList($allOptions, $blindSettings['sortOrder.'] ?? []);
    }

    /**
     * get the values for the "group" selector
     *
     * @return array Associative array with options
     */
    protected function getAllAvailableGroupOptions(): array
    {
        $allOptions = [];
        $blindSettings = $this->settings['blind'];
        if (!($blindSettings['groupBy'] ?? false)) {
            foreach (GroupOption::cases() as $groupOption) {
                $value = $groupOption->value;
                $allOptions[$value] = LocalizationUtility::translate('groupBy.' . $value, 'IndexedSearch');
            }
        }
        // disable single entries by TypoScript
        return $this->removeOptionsFromOptionList($allOptions, ($blindSettings['groupBy.'] ?? []));
    }

    /**
     * get the values for the "sortDescending" selector
     *
     * @return array Associative array with options
     */
    protected function getAllAvailableSortDescendingOptions(): array
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
        return $this->removeOptionsFromOptionList($allOptions, $blindSettings['descending.'] ?? []);
    }

    /**
     * get the values for the "results" selector
     *
     * @return array Associative array with options
     */
    protected function getAllAvailableNumberOfResultsOptions(): array
    {
        $allOptions = [];
        if (count($this->availableResultsNumbers) > 1) {
            $allOptions = array_combine($this->availableResultsNumbers, $this->availableResultsNumbers);
        }
        // disable single entries by TypoScript
        return $this->removeOptionsFromOptionList($allOptions, $this->settings['blind']['numberOfResults'] ?? []);
    }

    /**
     * removes blinding entries from the option list of a selector
     *
     * @param array $allOptions associative array containing all options
     * @param mixed $blindOptions Either associative array containing the option key to be removed, or anything else (= not configured)
     * @return array Options from $allOptions with some options removed
     */
    protected function removeOptionsFromOptionList(array $allOptions, mixed $blindOptions): array
    {
        if (is_array($blindOptions)) {
            foreach ($blindOptions as $key => $val) {
                if ((int)$val === 1) {
                    unset($allOptions[$key]);
                }
            }
        }
        return $allOptions;
    }

    /**
     * Links the $linkText to page $pageUid
     */
    protected function linkPage(int $pageUid, array $row, string $linkText): LinkResultInterface
    {
        $pageLanguage = $this->context->getPropertyFromAspect('language', 'contentId', 0);

        $linkConfiguration = [
            'parameter' => $pageUid . ',' . $row['data_page_type'],
        ];

        // Parameters for link
        $urlParameters = [];
        if (isset($row['static_page_arguments'])) {
            $urlParameters = json_decode($row['static_page_arguments'], true);
        }
        if (!empty($row['data_page_mp'] ?? false)) {
            $urlParameters['MP'] = $row['data_page_mp'];
        }
        if (($pageLanguage === 0 && $row['sys_language_uid'] > 0) || $pageLanguage > 0) {
            $linkConfiguration['_language'] = (int)$row['sys_language_uid'];
        }

        if ($urlParameters !== []) {
            $linkConfiguration['additionalParams'] = GeneralUtility::implodeArrayForUrl('', $urlParameters);
        }

        $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $cObj->setRequest($this->request);
        $cObj->start($row, 'pages');
        return $this->linkFactory->create($linkText, $linkConfiguration, $cObj);
    }

    /**
     * Return the menu of pages used for the selector.
     *
     * @param int $pageUid Page ID for which to return menu
     * @return array Menu items (for making the section selector box)
     */
    protected function getMenuOfPages(int $pageUid): array
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
    protected function getPathFromPageId(int $id, string $pathMP = ''): string
    {
        $identStr = $id . '|' . $pathMP;
        if (!isset($this->pathCache[$identStr])) {
            $this->requiredFrontendUsergroups[$id] = [];
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
                    $breadcrumbWraps = $this->typoScriptService->explodeConfigurationForOptionSplit(['wrap' => $breadcrumbWrap], $pageCount);
                    foreach ($rl as $v) {
                        $uid = (int)$v['uid'];

                        if (in_array($v['doktype'], $excludeDoktypesFromPath, false)) {
                            continue;
                        }
                        // Check fe_user
                        if ($v['fe_group'] && ($uid === $id || $v['extendToSubpages'])) {
                            $this->requiredFrontendUsergroups[$id][] = $v['fe_group'];
                        }
                        // Stop, if we find that the current id is the current root page.
                        $localRootLine = $this->request->getAttribute('frontend.page.information')->getLocalRootLine();
                        if ($uid === (int)$localRootLine[0]['uid']) {
                            array_pop($breadcrumbWraps);
                            break;
                        }
                        $path = $this->getTypoScriptFrontendController()->cObj->wrap(htmlspecialchars($v['title']), array_pop($breadcrumbWraps)['wrap']) . $path;
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
     * simple function to initialize possible external parsers
     * feeds the $this->externalParsers array
     */
    protected function initializeExternalParsers(): void
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
     * Returns if an item type is a multipage item type
     *
     * @param string $item_type Item type
     * @return bool TRUE if multipage capable
     */
    protected function multiplePagesType(string $item_type): bool
    {
        return is_object($this->externalParsers[$item_type] ?? false) && $this->externalParsers[$item_type]->isMultiplePageExtension($item_type);
    }

    /**
     * Process variables related to indexed_search extendedSearch needed by frontend view.
     * Populate select boxes and setting some flags.
     * The returned data can be passed directly into the view by assignMultiple()
     *
     * @return array Variables to pass into the view, so they can be used in fluid template
     */
    protected function processExtendedSearchParameters(): array
    {
        $allSearchTypes = $this->getAllAvailableSearchTypeOptions();
        $allDefaultOperands = $this->getAllAvailableOperandsOptions();
        $allMediaTypes = $this->getAllAvailableMediaTypesOptions();
        $allSortOrders = $this->getAllAvailableSortOrderOptions();
        $allSortDescendings = $this->getAllAvailableSortDescendingOptions();
        return [
            'allSearchTypes' => $allSearchTypes,
            'allDefaultOperands' => $allDefaultOperands,
            'showTypeSearch' => !empty($allSearchTypes) || !empty($allDefaultOperands),
            'allMediaTypes' => $allMediaTypes,
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
    protected function loadSettings(): void
    {
        if (!is_array($this->settings['results.'] ?? false)) {
            $this->settings['results.'] = [];
        }
        $fullTypoScriptArray = $this->typoScriptService->convertPlainArrayToTypoScriptArray($this->settings);
        $typoScriptArray = $fullTypoScriptArray['results.'];

        $typoScriptFrontendController = $this->getTypoScriptFrontendController();
        $this->settings['results.']['summaryCropAfter'] = MathUtility::forceIntegerInRange(
            $typoScriptFrontendController->cObj->stdWrapValue('summaryCropAfter', $typoScriptArray ?? []),
            10,
            5000,
            180
        );
        $this->settings['results.']['summaryCropSignifier'] = $typoScriptFrontendController->cObj->stdWrapValue('summaryCropSignifier', $typoScriptArray ?? []);
        $this->settings['results.']['titleCropAfter'] = MathUtility::forceIntegerInRange(
            $typoScriptFrontendController->cObj->stdWrapValue('titleCropAfter', $typoScriptArray ?? []),
            10,
            500,
            50
        );
        $this->settings['results.']['titleCropSignifier'] = $typoScriptFrontendController->cObj->stdWrapValue('titleCropSignifier', $typoScriptArray ?? []);
        $this->settings['results.']['markupSW_summaryMax'] = MathUtility::forceIntegerInRange(
            $typoScriptFrontendController->cObj->stdWrapValue('markupSW_summaryMax', $typoScriptArray ?? []),
            10,
            5000,
            300
        );
        $this->settings['results.']['markupSW_postPreLgd'] = MathUtility::forceIntegerInRange(
            $typoScriptFrontendController->cObj->stdWrapValue('markupSW_postPreLgd', $typoScriptArray ?? []),
            1,
            500,
            60
        );
        $this->settings['results.']['markupSW_postPreLgd_offset'] = MathUtility::forceIntegerInRange(
            $typoScriptFrontendController->cObj->stdWrapValue('markupSW_postPreLgd_offset', $typoScriptArray ?? []),
            1,
            50,
            5
        );
        $this->settings['results.']['markupSW_divider'] = $typoScriptFrontendController->cObj->stdWrapValue('markupSW_divider', $typoScriptArray ?? []);
        $this->settings['results.']['hrefInSummaryCropAfter'] = MathUtility::forceIntegerInRange(
            $typoScriptFrontendController->cObj->stdWrapValue('hrefInSummaryCropAfter', $typoScriptArray ?? []),
            10,
            400,
            60
        );
        $this->settings['results.']['hrefInSummaryCropSignifier'] = $typoScriptFrontendController->cObj->stdWrapValue('hrefInSummaryCropSignifier', $typoScriptArray ?? []);
    }

    /**
     * Returns number of results to display
     */
    protected function getNumberOfResults(int $numberOfResults): int
    {
        return in_array($numberOfResults, $this->availableResultsNumbers, true) ?
            $numberOfResults : $this->defaultResultNumber;
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

    private function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return $this->request->getAttribute('frontend.controller');
    }
}
