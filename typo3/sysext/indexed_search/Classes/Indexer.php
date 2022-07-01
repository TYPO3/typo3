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

namespace TYPO3\CMS\IndexedSearch;

use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Html\HtmlParser;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\IndexedSearch\Utility\DoubleMetaPhoneUtility;
use TYPO3\CMS\IndexedSearch\Utility\IndexedSearchUtility;

/**
 * Indexing class for TYPO3 frontend
 *
 * @internal
 */
class Indexer
{

    /**
     * @var array
     */
    public $reasons = [
        -1 => 'mtime matched the document, so no changes detected and no content updated',
        -2 => 'The minimum age was not exceeded',
        1 => 'The configured max-age was exceeded for the document and thus it\'s indexed.',
        2 => 'The minimum age was exceed and mtime was set and the mtime was different, so the page was indexed.',
        3 => 'The minimum age was exceed, but mtime was not set, so the page was indexed.',
        4 => 'Page has never been indexed (is not represented in the index_phash table).',
    ];

    /**
     * HTML code blocks to exclude from indexing
     *
     * @var string
     */
    public $excludeSections = 'script,style';

    /**
     * Supported Extensions for external files
     *
     * @var array
     */
    public $external_parsers = [];

    /**
     * External parser objects, keys are file extension names. Values are objects with certain methods.
     * Fe-group list (pages might be indexed separately for each usergroup combination to support search
     * in access limited pages!)
     *
     * @var string
     */
    public $defaultGrList = '0,-1';

    /**
     * Min/Max times
     *
     * @var int
     */
    public $tstamp_maxAge = 0;

    /**
     * If set, this tells a number of seconds that is the maximum age of an indexed document.
     * Regardless of mtime the document will be re-indexed if this limit is exceeded.
     *
     * @var int
     */
    public $tstamp_minAge = 0;

    /**
     * If set, this tells a minimum limit before a document can be indexed again. This is regardless of mtime.
     *
     * @var int
     */
    public $maxExternalFiles = 0;

    /**
     * Max number of external files to index.
     *
     * @var bool
     */
    public $forceIndexing = false;

    /**
     * Set when crawler is detected (internal)
     *
     * @var array
     */
    public $defaultContentArray = [
        'title' => '',
        'description' => '',
        'keywords' => '',
        'body' => '',
    ];

    /**
     * @var int
     */
    public $wordcount = 0;

    /**
     * @var int
     */
    public $externalFileCounter = 0;

    /**
     * @var array
     */
    public $conf = [];

    /**
     * Configuration set internally (see init functions for required keys and their meaning)
     *
     * @var array
     */
    public $indexerConfig = [];

    /**
     * Indexer configuration, coming from TYPO3's system configuration for EXT:indexed_search
     *
     * @var array
     */
    public $hash = [];

    /**
     * Hash array, contains phash and phash_grouping
     *
     * @var array
     */
    public $file_phash_arr = [];

    /**
     * Hash array for files
     *
     * @var array
     */
    public $contentParts = [];

    /**
     * Content of TYPO3 page
     *
     * @var int
     */
    public $content_md5h;

    /**
     * @var array
     */
    public $internal_log = [];

    /**
     * Internal log
     *
     * @var string
     */
    public $indexExternalUrl_content = '';

    /**
     * @var int
     */
    public $freqRange = 32000;

    /**
     * @var float
     */
    public $freqMax = 0.1;

    /**
     * @var bool
     */
    public $enableMetaphoneSearch = false;

    /**
     * @var bool
     */
    public $storeMetaphoneInfoAsWords;

    /**
     * @var string
     */
    public $metaphoneContent = '';

    /**
     * Metaphone object, if any
     *
     * @var \TYPO3\CMS\IndexedSearch\Utility\DoubleMetaPhoneUtility
     */
    public $metaphoneObj;

    /**
     * Lexer object for word splitting
     *
     * @var \TYPO3\CMS\IndexedSearch\Lexer
     */
    public $lexerObj;

    /**
     * @var int
     */
    public $flagBitMask;

    /**
     * @var TimeTracker
     */
    protected $timeTracker;

    /**
     * Indexer constructor.
     */
    public function __construct()
    {
        $this->timeTracker = GeneralUtility::makeInstance(TimeTracker::class);
        // Indexer configuration from Extension Manager interface
        $this->indexerConfig = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('indexed_search');
        $this->tstamp_minAge = MathUtility::forceIntegerInRange((int)($this->indexerConfig['minAge'] ?? 0) * 3600, 0);
        $this->tstamp_maxAge = MathUtility::forceIntegerInRange((int)($this->indexerConfig['maxAge'] ?? 0) * 3600, 0);
        $this->maxExternalFiles = MathUtility::forceIntegerInRange($this->indexerConfig['maxExternalFiles'], 0, 1000, 5);
        $this->flagBitMask = MathUtility::forceIntegerInRange($this->indexerConfig['flagBitMask'], 0, 255);
        // Workaround: If the extension configuration was not updated yet, the value is not existing
        $this->enableMetaphoneSearch = !isset($this->indexerConfig['enableMetaphoneSearch']) || $this->indexerConfig['enableMetaphoneSearch'];
        $this->storeMetaphoneInfoAsWords = !IndexedSearchUtility::isTableUsed('index_words') && $this->enableMetaphoneSearch;
    }

    /********************************
     *
     * Initialization
     *
     *******************************/

    /**
     * Initializes the object.
     * @param array|null $configuration will be used to set $this->conf, otherwise $this->conf MUST be set with proper values prior to this call
     */
    public function init(array $configuration = null)
    {
        if (is_array($configuration)) {
            $this->conf = $configuration;
        }
        // Setting phash / phash_grouping which identifies the indexed page based on some of these variables:
        $this->setT3Hashes();
        // Initialize external document parsers:
        // Example configuration, see ext_localconf.php of this file!
        if ($this->conf['index_externals']) {
            $this->initializeExternalParsers();
        }
        // Initialize lexer (class that deconstructs the text into words):
        $lexerObjectClassName = ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['lexer'] ?? false) ? $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['lexer'] : Lexer::class;
        /** @var Lexer $lexer */
        $lexer = GeneralUtility::makeInstance($lexerObjectClassName);
        $this->lexerObj = $lexer;
        $this->lexerObj->debug = $this->indexerConfig['debugMode'];
        // Initialize metaphone hook:
        // Make sure that the hook is loaded _after_ indexed_search as this may overwrite the hook depending on the configuration.
        if ($this->enableMetaphoneSearch && ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['metaphone'] ?? false)) {
            /** @var DoubleMetaPhoneUtility $metaphoneObj */
            $metaphoneObj = GeneralUtility::makeInstance($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['metaphone']);
            $this->metaphoneObj = $metaphoneObj;
            $this->metaphoneObj->pObj = $this;
        }
    }

    /**
     * Initialize external parsers
     *
     * @internal
     * @see init()
     */
    public function initializeExternalParsers()
    {
        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['external_parsers'] ?? [] as $extension => $className) {
            $this->external_parsers[$extension] = GeneralUtility::makeInstance($className);
            $this->external_parsers[$extension]->pObj = $this;
            // Init parser and if it returns FALSE, unset its entry again:
            if (!$this->external_parsers[$extension]->initParser($extension)) {
                unset($this->external_parsers[$extension]);
            }
        }
    }

    /********************************
     *
     * Indexing; TYPO3 pages (HTML content)
     *
     *******************************/
    /**
     * Start indexing of the TYPO3 page
     */
    public function indexTypo3PageContent()
    {
        $check = $this->checkMtimeTstamp($this->conf['mtime'], $this->hash['phash']);
        $is_grlist = $this->is_grlist_set($this->hash['phash']);
        if ($check > 0 || !$is_grlist || $this->forceIndexing) {
            // Setting message:
            if ($this->forceIndexing) {
                $this->log_setTSlogMessage('Indexing needed, reason: Forced', LogLevel::NOTICE);
            } elseif ($check > 0) {
                $this->log_setTSlogMessage('Indexing needed, reason: ' . $this->reasons[$check], LogLevel::NOTICE);
            } else {
                $this->log_setTSlogMessage('Indexing needed, reason: Updates gr_list!', LogLevel::NOTICE);
            }
            // Divide into title,keywords,description and body:
            $this->log_push('Split content', '');
            $this->contentParts = $this->splitHTMLContent($this->conf['content']);
            if ($this->conf['indexedDocTitle']) {
                $this->contentParts['title'] = $this->conf['indexedDocTitle'];
            }
            $this->log_pull();
            // Calculating a hash over what is to be the actual page content. Maybe this hash should not include title,description and keywords? The bodytext is the primary concern. (on the other hand a changed page-title would make no difference then, so don't!)
            $this->content_md5h = IndexedSearchUtility::md5inthash(implode('', $this->contentParts));
            // This function checks if there is already a page (with gr_list = 0,-1) indexed and if that page has the very same contentHash.
            // If the contentHash is the same, then we can rest assured that this page is already indexed and regardless of mtime and origContent we don't need to do anything more.
            // This will also prevent pages from being indexed if a fe_users has logged in and it turns out that the page content is not changed anyway. fe_users logged in should always search with hash_gr_list = "0,-1" OR "[their_group_list]". This situation will be prevented only if the page has been indexed with no user login on before hand. Else the page will be indexed by users until that event. However that does not present a serious problem.
            $checkCHash = $this->checkContentHash();
            if (!is_array($checkCHash) || $check === 1) {
                $Pstart = IndexedSearchUtility::milliseconds();
                $this->log_push('Converting charset of content (' . $this->conf['metaCharset'] . ') to utf-8', '');
                $this->charsetEntity2utf8($this->contentParts, $this->conf['metaCharset']);
                $this->log_pull();
                // Splitting words
                $this->log_push('Extract words from content', '');
                $splitInWords = $this->processWordsInArrays($this->contentParts);
                $this->log_pull();
                // Analyze the indexed words.
                $this->log_push('Analyze the extracted words', '');
                $indexArr = $this->indexAnalyze($splitInWords);
                $this->log_pull();
                // Submitting page (phash) record
                $this->log_push('Submitting page', '');
                $this->submitPage();
                $this->log_pull();
                // Check words and submit to word list if not there
                $this->log_push('Check word list and submit words', '');
                if (IndexedSearchUtility::isTableUsed('index_words')) {
                    $this->checkWordList($indexArr);
                    $this->submitWords($indexArr, $this->hash['phash']);
                }
                $this->log_pull();
                // Set parsetime
                $this->updateParsetime($this->hash['phash'], IndexedSearchUtility::milliseconds() - $Pstart);
                // Checking external files if configured for.
                $this->log_push('Checking external files', '');
                if ($this->conf['index_externals']) {
                    $this->extractLinks($this->conf['content']);
                }
                $this->log_pull();
            } else {
                // Update the timestamp
                $this->updateTstamp($this->hash['phash'], $this->conf['mtime']);
                $this->updateSetId($this->hash['phash']);
                // $checkCHash['phash'] is the phash of the result row that is similar to the current phash regarding the content hash.
                $this->update_grlist($checkCHash['phash'], $this->hash['phash']);
                $this->updateRootline();
                $this->log_setTSlogMessage('Indexing not needed, the contentHash, ' . $this->content_md5h . ', has not changed. Timestamp, grlist and rootline updated if necessary.');
            }
        } else {
            $this->log_setTSlogMessage('Indexing not needed, reason: ' . $this->reasons[$check]);
        }
    }

    /**
     * Splits HTML content and returns an associative array, with title, a list of metatags, and a list of words in the body.
     *
     * @param string $content HTML content to index. To some degree expected to be made by TYPO3 (ei. splitting the header by ":")
     * @return array Array of content, having keys "title", "body", "keywords" and "description" set.
     * @see splitRegularContent()
     */
    public function splitHTMLContent($content)
    {
        // divide head from body ( u-ouh :) )
        $contentArr = $this->defaultContentArray;
        $contentArr['body'] = stristr($content, '<body') ?: '';
        $headPart = substr($content, 0, -strlen($contentArr['body']));
        // get title
        $this->embracingTags($headPart, 'TITLE', $contentArr['title'], $dummy2, $dummy);
        $titleParts = explode(':', $contentArr['title'], 2);
        $contentArr['title'] = trim($titleParts[1] ?? $titleParts[0]);
        // get keywords and description metatags
        if ($this->conf['index_metatags']) {
            $meta = [];
            $i = 0;
            while ($this->embracingTags($headPart, 'meta', $dummy, $headPart, $meta[$i])) {
                $i++;
            }
            // @todo The code below stops at first unset tag. Is that correct?
            for ($i = 0; isset($meta[$i]); $i++) {
                // decode HTML entities, meta tag content needs to be encoded later
                $meta[$i] = GeneralUtility::get_tag_attributes($meta[$i], true);
                if (stripos(($meta[$i]['name'] ?? ''), 'keywords') !== false) {
                    $contentArr['keywords'] .= ',' . $this->addSpacesToKeywordList($meta[$i]['content']);
                }
                if (stripos(($meta[$i]['name'] ?? ''), 'description') !== false) {
                    $contentArr['description'] .= ',' . $meta[$i]['content'];
                }
            }
        }
        // Process <!--TYPO3SEARCH_begin--> or <!--TYPO3SEARCH_end--> tags:
        $this->typoSearchTags($contentArr['body']);
        // Get rid of unwanted sections (ie. scripting and style stuff) in body
        $tagList = explode(',', $this->excludeSections);
        foreach ($tagList as $tag) {
            while ($this->embracingTags($contentArr['body'], $tag, $dummy, $contentArr['body'], $dummy2)) {
            }
        }
        // remove tags, but first make sure we don't concatenate words by doing it
        $contentArr['body'] = str_replace('<', ' <', $contentArr['body']);
        $contentArr['body'] = trim(strip_tags($contentArr['body']));
        $contentArr['keywords'] = trim($contentArr['keywords']);
        $contentArr['description'] = trim($contentArr['description']);
        // Return array
        return $contentArr;
    }

    /**
     * Extract the charset value from HTML meta tag.
     *
     * @param string $content HTML content
     * @return string The charset value if found.
     */
    public function getHTMLcharset($content)
    {
        if (preg_match('/<meta[[:space:]]+[^>]*http-equiv[[:space:]]*=[[:space:]]*["\']CONTENT-TYPE["\'][^>]*>/i', $content, $reg)) {
            if (preg_match('/charset[[:space:]]*=[[:space:]]*([[:alnum:]-]+)/i', $reg[0], $reg2)) {
                return $reg2[1];
            }
        }

        return '';
    }

    /**
     * Converts a HTML document to utf-8
     *
     * @param string $content HTML content, any charset
     * @param string $charset Optional charset (otherwise extracted from HTML)
     * @return string Converted HTML
     */
    public function convertHTMLToUtf8($content, $charset = '')
    {
        // Find charset:
        $charset = $charset ?: $this->getHTMLcharset($content);
        $charset = trim(strtolower($charset));
        // Convert charset:
        if ($charset && $charset !== 'utf-8') {
            $content = mb_convert_encoding($content, 'utf-8', $charset);
        }
        // Convert entities, assuming document is now UTF-8:
        return html_entity_decode($content);
    }

    /**
     * Finds first occurrence of embracing tags and returns the embraced content and the original string with
     * the tag removed in the two passed variables. Returns FALSE if no match found. ie. useful for finding
     * <title> of document or removing <script>-sections
     *
     * @param string $string String to search in
     * @param string $tagName Tag name, eg. "script
     * @param string $tagContent Passed by reference: Content inside found tag
     * @param string $stringAfter Passed by reference: Content after found tag
     * @param string $paramList Passed by reference: Attributes of the found tag.
     * @return bool Returns FALSE if tag was not found, otherwise TRUE.
     */
    public function embracingTags($string, $tagName, &$tagContent, &$stringAfter, &$paramList)
    {
        $endTag = '</' . $tagName . '>';
        $startTag = '<' . $tagName;
        // stristr used because we want a case-insensitive search for the tag.
        $isTagInText = stristr($string, $startTag);
        // if the tag was not found, return FALSE
        if (!$isTagInText) {
            return false;
        }
        [$paramList, $isTagInText] = explode('>', substr($isTagInText, strlen($startTag)), 2);
        $afterTagInText = stristr($isTagInText, $endTag);
        if ($afterTagInText) {
            $stringBefore = substr($string, 0, (int)strpos(strtolower($string), strtolower($startTag)));
            $tagContent = substr($isTagInText, 0, strlen($isTagInText) - strlen($afterTagInText));
            $stringAfter = $stringBefore . substr($afterTagInText, strlen($endTag));
        } else {
            $tagContent = '';
            $stringAfter = $isTagInText;
        }
        return true;
    }

    /**
     * Removes content that shouldn't be indexed according to TYPO3SEARCH-tags.
     *
     * @param string $body HTML Content, passed by reference
     * @return bool Returns TRUE if a TYPOSEARCH_ tag was found, otherwise FALSE.
     */
    public function typoSearchTags(&$body)
    {
        $expBody = preg_split('/\\<\\!\\-\\-[\\s]?TYPO3SEARCH_/', $body);
        $expBody = $expBody ?: [];
        if (count($expBody) > 1) {
            $body = '';
            $prev = '';
            foreach ($expBody as $val) {
                $part = explode('-->', $val, 2);
                if (trim($part[0]) === 'begin') {
                    $body .= $part[1];
                    $prev = '';
                } elseif (trim($part[0]) === 'end') {
                    $body .= $prev;
                } else {
                    $prev = $val;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Extract links (hrefs) from HTML content and if indexable media is found, it is indexed.
     *
     * @param string $content HTML content
     */
    public function extractLinks($content)
    {
        // Get links:
        $list = $this->extractHyperLinks($content);
        // Traverse links:
        foreach ($list as $linkInfo) {
            // Decode entities:
            if ($linkInfo['localPath']) {
                // localPath means: This file is sent by a download script. While the indexed URL has to point to $linkInfo['href'], the absolute path to the file is specified here!
                $linkSource = htmlspecialchars_decode($linkInfo['localPath']);
            } else {
                $linkSource = htmlspecialchars_decode($linkInfo['href']);
            }
            // Parse URL:
            $qParts = parse_url($linkSource);
            // Check for jumpurl (TYPO3 specific thing...)
            if (($qParts['query'] ?? false) && str_contains($qParts['query'] ?? '', 'jumpurl=')) {
                parse_str($qParts['query'], $getP);
                $linkSource = $getP['jumpurl'];
                $qParts = parse_url($linkSource);
            }
            if (!$linkInfo['localPath'] && ($qParts['scheme'] ?? false)) {
                if ($this->indexerConfig['indexExternalURLs']) {
                    // Index external URL (http or otherwise)
                    $this->indexExternalUrl($linkSource);
                }
            } elseif (!($qParts['query'] ?? false)) {
                $linkSource = urldecode($linkSource);
                if (GeneralUtility::isAllowedAbsPath($linkSource)) {
                    $localFile = $linkSource;
                } else {
                    $localFile = GeneralUtility::getFileAbsFileName(Environment::getPublicPath() . '/' . $linkSource);
                }
                if ($localFile && @is_file($localFile)) {
                    // Index local file:
                    if ($linkInfo['localPath']) {
                        $fI = pathinfo($linkSource);
                        $ext = strtolower($fI['extension']);
                        $this->indexRegularDocument($linkInfo['href'], false, $linkSource, $ext);
                    } else {
                        $this->indexRegularDocument($linkSource);
                    }
                }
            }
        }
    }

    /**
     * Extracts all links to external documents from the HTML content string
     *
     * @param string $html
     * @return array Array of hyperlinks (keys: tag, href, localPath (empty if not local))
     * @see extractLinks()
     */
    public function extractHyperLinks($html)
    {
        $htmlParser = GeneralUtility::makeInstance(HtmlParser::class);
        $htmlParts = $htmlParser->splitTags('a', $html);
        $hyperLinksData = [];
        foreach ($htmlParts as $index => $tagData) {
            if ($index % 2 !== 0) {
                $tagAttributes = $htmlParser->get_tag_attributes($tagData, true);
                $firstTagName = $htmlParser->getFirstTagName($tagData);
                if (strtolower($firstTagName) === 'a') {
                    if (!empty($tagAttributes[0]['href']) && substr($tagAttributes[0]['href'], 0, 1) !== '#') {
                        $hyperLinksData[] = [
                            'tag' => $tagData,
                            'href' => $tagAttributes[0]['href'],
                            'localPath' => $this->createLocalPath(urldecode($tagAttributes[0]['href'])),
                        ];
                    }
                }
            }
        }
        return $hyperLinksData;
    }

    /**
     * Extracts the "base href" from content string.
     *
     * @param string $html Content to analyze
     * @return string The base href or an empty string if not found
     */
    public function extractBaseHref($html)
    {
        $href = '';
        $htmlParser = GeneralUtility::makeInstance(HtmlParser::class);
        $htmlParts = $htmlParser->splitTags('base', $html);
        foreach ($htmlParts as $index => $tagData) {
            if ($index % 2 !== 0) {
                $tagAttributes = $htmlParser->get_tag_attributes($tagData, true);
                $firstTagName = $htmlParser->getFirstTagName($tagData);
                if (strtolower($firstTagName) === 'base') {
                    $href = $tagAttributes[0]['href'];
                    if ($href) {
                        break;
                    }
                }
            }
        }
        return $href;
    }

    /******************************************
     *
     * Indexing; external URL
     *
     ******************************************/
    /**
     * Index External URLs HTML content
     *
     * @param string $externalUrl URL, eg. "https://typo3.org/
     * @see indexRegularDocument()
     */
    public function indexExternalUrl($externalUrl)
    {
        // Get headers:
        $urlHeaders = $this->getUrlHeaders($externalUrl);
        if (is_array($urlHeaders) && stripos($urlHeaders['Content-Type'], 'text/html') !== false) {
            $content = ($this->indexExternalUrl_content = GeneralUtility::getUrl($externalUrl));
            if ((string)$content !== '') {
                // Create temporary file:
                $tmpFile = GeneralUtility::tempnam('EXTERNAL_URL');
                GeneralUtility::writeFile($tmpFile, $content);
                // Index that file:
                $this->indexRegularDocument($externalUrl, true, $tmpFile, 'html');
                // Using "TRUE" for second parameter to force indexing of external URLs (mtime doesn't make sense, does it?)
                unlink($tmpFile);
            }
        }
    }

    /**
     * Getting HTTP request headers of URL
     *
     * @param string $url The URL
     * @return mixed If no answer, returns FALSE. Otherwise an array where HTTP headers are keys
     */
    public function getUrlHeaders($url)
    {
        try {
            $response = GeneralUtility::makeInstance(RequestFactory::class)->request($url, 'HEAD');
            $headers = $response->getHeaders();
            $retVal = [];
            foreach ($headers as $key => $value) {
                $retVal[$key] = implode('', $value);
            }
            return $retVal;
        } catch (\Exception $e) {
            // fail silently if the HTTP request failed
            return false;
        }
    }

    /**
     * Checks if the file is local
     *
     * @param string $sourcePath
     * @return string Absolute path to file if file is local, else empty string
     */
    protected function createLocalPath($sourcePath)
    {
        $pathFunctions = [
            'createLocalPathUsingAbsRefPrefix',
            'createLocalPathUsingDomainURL',
            'createLocalPathFromAbsoluteURL',
            'createLocalPathFromRelativeURL',
        ];
        foreach ($pathFunctions as $functionName) {
            $localPath = $this->{$functionName}($sourcePath);
            if ($localPath != '') {
                break;
            }
        }
        return $localPath;
    }

    /**
     * Attempts to create a local file path by matching a current request URL.
     *
     * @param string $sourcePath
     * @return string
     */
    protected function createLocalPathUsingDomainURL($sourcePath)
    {
        $localPath = '';
        $baseURL = $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getSiteUrl();
        $baseURLLength = strlen($baseURL);
        if (strpos($sourcePath, $baseURL) === 0) {
            $sourcePath = substr($sourcePath, $baseURLLength);
            $localPath = Environment::getPublicPath() . '/' . $sourcePath;
            if (!self::isAllowedLocalFile($localPath)) {
                $localPath = '';
            }
        }
        return $localPath;
    }

    /**
     * Attempts to create a local file path by matching absRefPrefix. This
     * requires TSFE. If TSFE is missing, this function does nothing.
     *
     * @param string $sourcePath
     * @return string
     */
    protected function createLocalPathUsingAbsRefPrefix($sourcePath)
    {
        $localPath = '';
        if (isset($GLOBALS['TSFE']) && $GLOBALS['TSFE'] instanceof TypoScriptFrontendController) {
            $absRefPrefix = $GLOBALS['TSFE']->config['config']['absRefPrefix'];
            $absRefPrefixLength = strlen($absRefPrefix);
            if ($absRefPrefixLength > 0 && strpos($sourcePath, $absRefPrefix) === 0) {
                $sourcePath = substr($sourcePath, $absRefPrefixLength);
                $localPath = Environment::getPublicPath() . '/' . $sourcePath;
                if (!self::isAllowedLocalFile($localPath)) {
                    $localPath = '';
                }
            }
        }
        return $localPath;
    }

    /**
     * Attempts to create a local file path from the absolute URL without
     * schema.
     *
     * @param string $sourcePath
     * @return string
     */
    protected function createLocalPathFromAbsoluteURL($sourcePath)
    {
        $localPath = '';
        if (substr(($sourcePath[0] ?? ''), 0, 1) === '/') {
            $sourcePath = substr($sourcePath, 1);
            $localPath = Environment::getPublicPath() . '/' . $sourcePath;
            if (!self::isAllowedLocalFile($localPath)) {
                $localPath = '';
            }
        }
        return $localPath;
    }

    /**
     * Attempts to create a local file path from the relative URL.
     *
     * @param string $sourcePath
     * @return string
     */
    protected function createLocalPathFromRelativeURL($sourcePath)
    {
        $localPath = '';
        if (self::isRelativeURL($sourcePath)) {
            $localPath = Environment::getPublicPath() . '/' . $sourcePath;
            if (!self::isAllowedLocalFile($localPath)) {
                $localPath = '';
            }
        }
        return $localPath;
    }

    /**
     * Checks if URL is relative.
     *
     * @param string $url
     * @return bool
     */
    protected static function isRelativeURL($url)
    {
        $urlParts = @parse_url($url);
        return (!isset($urlParts['scheme']) || $urlParts['scheme'] === '') && substr(($urlParts['path'][0] ?? ''), 0, 1) !== '/';
    }

    /**
     * Checks if the path points to the file inside the web site
     *
     * @param string $filePath
     * @return bool
     */
    protected static function isAllowedLocalFile($filePath)
    {
        $filePath = GeneralUtility::resolveBackPath($filePath);
        $insideWebPath = strpos($filePath, Environment::getPublicPath()) === 0;
        $isFile = is_file($filePath);
        return $insideWebPath && $isFile;
    }

    /******************************************
     *
     * Indexing; external files (PDF, DOC, etc)
     *
     ******************************************/
    /**
     * Indexing a regular document given as $file (relative to public web path, local file)
     *
     * @param string $file Relative Filename, relative to public web path. It can also be an absolute path as long as it is inside the lockRootPath (validated with \TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath()). Finally, if $contentTmpFile is set, this value can be anything, most likely a URL
     * @param bool $force If set, indexing is forced (despite content hashes, mtime etc).
     * @param string $contentTmpFile Temporary file with the content to read it from (instead of $file). Used when the $file is a URL.
     * @param string $altExtension File extension for temporary file.
     */
    public function indexRegularDocument($file, $force = false, $contentTmpFile = '', $altExtension = '')
    {
        // Init
        $fI = pathinfo($file);
        $ext = $altExtension ?: strtolower($fI['extension']);
        // Create abs-path:
        if (!$contentTmpFile) {
            if (!PathUtility::isAbsolutePath($file)) {
                // Relative, prepend public web path:
                $absFile = GeneralUtility::getFileAbsFileName(Environment::getPublicPath() . '/' . $file);
            } else {
                // Absolute, pass-through:
                $absFile = $file;
            }
            $absFile = GeneralUtility::isAllowedAbsPath($absFile) ? $absFile : '';
        } else {
            $absFile = $contentTmpFile;
        }
        // Indexing the document:
        if ($absFile && @is_file($absFile)) {
            if ($this->external_parsers[$ext] ?? false) {
                $fileInfo = stat($absFile);
                $cParts = $this->fileContentParts($ext, $absFile);
                foreach ($cParts as $cPKey) {
                    $this->internal_log = [];
                    $this->log_push('Index: ' . str_replace('.', '_', PathUtility::basename($file)) . ($cPKey ? '#' . $cPKey : ''), '');
                    $Pstart = IndexedSearchUtility::milliseconds();
                    $subinfo = ['key' => $cPKey];
                    // Setting page range. This is "0" (zero) when no division is made, otherwise a range like "1-3"
                    $phash_arr = ($this->file_phash_arr = $this->setExtHashes($file, $subinfo));
                    $check = $this->checkMtimeTstamp($fileInfo['mtime'], $phash_arr['phash']);
                    if ($check > 0 || $force) {
                        if ($check > 0) {
                            $this->log_setTSlogMessage('Indexing needed, reason: ' . $this->reasons[$check], LogLevel::NOTICE);
                        } else {
                            $this->log_setTSlogMessage('Indexing forced by flag', LogLevel::NOTICE);
                        }
                        // Check external file counter:
                        if ($this->externalFileCounter < $this->maxExternalFiles || $force) {
                            // Divide into title,keywords,description and body:
                            $this->log_push('Split content', '');
                            $contentParts = $this->readFileContent($ext, $absFile, $cPKey);
                            $this->log_pull();
                            if (is_array($contentParts)) {
                                // Calculating a hash over what is to be the actual content. (see indexTypo3PageContent())
                                $content_md5h = IndexedSearchUtility::md5inthash(implode('', $contentParts));
                                if ($this->checkExternalDocContentHash($phash_arr['phash_grouping'], $content_md5h) || $force) {
                                    // Increment counter:
                                    $this->externalFileCounter++;
                                    // Splitting words
                                    $this->log_push('Extract words from content', '');
                                    $splitInWords = $this->processWordsInArrays($contentParts);
                                    $this->log_pull();
                                    // Analyze the indexed words.
                                    $this->log_push('Analyze the extracted words', '');
                                    $indexArr = $this->indexAnalyze($splitInWords);
                                    $this->log_pull();
                                    // Submitting page (phash) record
                                    $this->log_push('Submitting page', '');
                                    // Unfortunately I cannot determine WHEN a file is originally made - so I must return the modification time...
                                    $this->submitFilePage($phash_arr, $file, $subinfo, $ext, $fileInfo['mtime'], $fileInfo['ctime'], $fileInfo['size'], $content_md5h, $contentParts);
                                    $this->log_pull();
                                    // Check words and submit to word list if not there
                                    $this->log_push('Check word list and submit words', '');
                                    if (IndexedSearchUtility::isTableUsed('index_words')) {
                                        $this->checkWordList($indexArr);
                                        $this->submitWords($indexArr, $phash_arr['phash']);
                                    }
                                    $this->log_pull();
                                    // Set parsetime
                                    $this->updateParsetime($phash_arr['phash'], IndexedSearchUtility::milliseconds() - $Pstart);
                                } else {
                                    // Update the timestamp
                                    $this->updateTstamp($phash_arr['phash'], $fileInfo['mtime']);
                                    $this->log_setTSlogMessage('Indexing not needed, the contentHash, ' . $content_md5h . ', has not changed. Timestamp updated.');
                                }
                            } else {
                                $this->log_setTSlogMessage('Could not index file! Unsupported extension.');
                            }
                        } else {
                            $this->log_setTSlogMessage('The limit of ' . $this->maxExternalFiles . ' has already been exceeded, so no indexing will take place this time.');
                        }
                    } else {
                        $this->log_setTSlogMessage('Indexing not needed, reason: ' . $this->reasons[$check]);
                    }
                    // Checking and setting sections:
                    $this->submitFile_section($phash_arr['phash']);
                    // Setting a section-record for the file. This is done also if the file is not indexed. Notice that section records are deleted when the page is indexed.
                    $this->log_pull();
                }
            } else {
                $this->log_setTSlogMessage('Indexing not possible; The extension "' . $ext . '" was not supported.');
            }
        } else {
            $this->log_setTSlogMessage('Indexing not possible; File "' . $absFile . '" not found or valid.');
        }
    }

    /**
     * Reads the content of an external file being indexed.
     * The content from the external parser MUST be returned in utf-8!
     *
     * @param string $fileExtension File extension, eg. "pdf", "doc" etc.
     * @param string $absoluteFileName Absolute filename of file (must exist and be validated OK before calling function)
     * @param string $sectionPointer Pointer to section (zero for all other than PDF which will have an indication of pages into which the document should be splitted.)
     * @return array Standard content array (title, description, keywords, body keys)
     */
    public function readFileContent($fileExtension, $absoluteFileName, $sectionPointer)
    {
        $contentArray = null;
        // Consult relevant external document parser:
        if (is_object($this->external_parsers[$fileExtension])) {
            $contentArray = $this->external_parsers[$fileExtension]->readFileContent($fileExtension, $absoluteFileName, $sectionPointer);
        }
        return $contentArray;
    }

    /**
     * Creates an array with pointers to divisions of document.
     *
     * @param string $ext File extension
     * @param string $absFile Absolute filename (must exist and be validated OK before calling function)
     * @return array Array of pointers to sections that the document should be divided into
     */
    public function fileContentParts($ext, $absFile)
    {
        $cParts = [0];
        // Consult relevant external document parser:
        if (is_object($this->external_parsers[$ext])) {
            $cParts = $this->external_parsers[$ext]->fileContentParts($ext, $absFile);
        }
        return $cParts;
    }

    /**
     * Splits non-HTML content (from external files for instance)
     *
     * @param string $content Input content (non-HTML) to index.
     * @return array Array of content, having the key "body" set (plus "title", "description" and "keywords", but empty)
     * @see splitHTMLContent()
     */
    public function splitRegularContent($content)
    {
        $contentArr = $this->defaultContentArray;
        $contentArr['body'] = $content;
        return $contentArr;
    }

    /**********************************
     *
     * Analysing content, Extracting words
     *
     **********************************/
    /**
     * Convert character set and HTML entities in the value of input content array keys
     *
     * @param array $contentArr Standard content array
     * @param string $charset Charset of the input content (converted to utf-8)
     */
    public function charsetEntity2utf8(&$contentArr, $charset)
    {
        // Convert charset if necessary
        foreach ($contentArr as $key => $value) {
            if ((string)$contentArr[$key] !== '') {
                if ($charset !== 'utf-8') {
                    $contentArr[$key] = mb_convert_encoding($contentArr[$key], 'utf-8', $charset);
                }
                // decode all numeric / html-entities in the string to real characters:
                $contentArr[$key] = html_entity_decode($contentArr[$key]);
            }
        }
    }

    /**
     * Processing words in the array from split*Content -functions
     *
     * @param array $contentArr Array of content to index, see splitHTMLContent() and splitRegularContent()
     * @return array Content input array modified so each key is not a unique array of words
     */
    public function processWordsInArrays($contentArr)
    {
        // split all parts to words
        foreach ($contentArr as $key => $value) {
            $contentArr[$key] = $this->lexerObj->split2Words($contentArr[$key]);
        }
        // For title, keywords, and description we don't want duplicates:
        $contentArr['title'] = array_unique($contentArr['title']);
        $contentArr['keywords'] = array_unique($contentArr['keywords']);
        $contentArr['description'] = array_unique($contentArr['description']);
        // Return modified array:
        return $contentArr;
    }

    /**
     * Extracts the sample description text from the content array.
     *
     * @param array $contentArr Content array
     * @return string Description string
     */
    public function bodyDescription($contentArr)
    {
        $bodyDescription = '';
        // Setting description
        $maxL = MathUtility::forceIntegerInRange($this->conf['index_descrLgd'], 0, 255, 200);
        if ($maxL) {
            $bodyDescription = preg_replace('/\s+/u', ' ', $contentArr['body']);
            // Shorten the string. If the database has the wrong character set
            // set the string is probably truncated again. mb_strcut can not be
            // used here because it's not part of the fallback package
            // symfony/polyfill-mbstring in case of the missing ext:mbstring.
            $bodyDescription = \mb_substr($bodyDescription, 0, $maxL, 'utf-8');
        }
        return $bodyDescription;
    }

    /**
     * Analyzes content to use for indexing,
     *
     * @param array $content Standard content array: an array with the keys title,keywords,description and body, which all contain an array of words.
     * @return array Index Array (whatever that is...)
     */
    public function indexAnalyze($content)
    {
        $indexArr = [];
        $this->analyzeHeaderinfo($indexArr, $content, 'title', 7);
        $this->analyzeHeaderinfo($indexArr, $content, 'keywords', 6);
        $this->analyzeHeaderinfo($indexArr, $content, 'description', 5);
        $this->analyzeBody($indexArr, $content);
        return $indexArr;
    }

    /**
     * Calculates relevant information for headercontent
     *
     * @param array $retArr Index array, passed by reference
     * @param array $content Standard content array
     * @param string $key Key from standard content array
     * @param int $offset Bit-wise priority to type
     */
    public function analyzeHeaderinfo(&$retArr, $content, $key, $offset)
    {
        foreach ($content[$key] as $val) {
            $val = mb_substr($val, 0, 60);
            // Cut after 60 chars because the index_words.baseword varchar field has this length. This MUST be the same.
            if (!isset($retArr[$val])) {
                // Word ID (wid)
                $retArr[$val]['hash'] = IndexedSearchUtility::md5inthash($val);
                // Metaphone value is also 60 only chars long
                $metaphone = $this->enableMetaphoneSearch ? substr($this->metaphone($val, $this->storeMetaphoneInfoAsWords), 0, 60) : '';
                $retArr[$val]['metaphone'] = $metaphone;
            }
            // Build metaphone fulltext string (can be used for fulltext indexing)
            if ($this->storeMetaphoneInfoAsWords) {
                $this->metaphoneContent .= ' ' . $retArr[$val]['metaphone'];
            }
            // Priority used for flagBitMask feature (see extension configuration)
            $retArr[$val]['cmp'] = ($retArr[$val]['cmp'] ?? 0) | 2 ** $offset;
            if (!($retArr[$val]['count'] ?? false)) {
                $retArr[$val]['count'] = 0;
            }

            // Increase number of occurrences
            $retArr[$val]['count']++;
            $this->wordcount++;
        }
    }

    /**
     * Calculates relevant information for bodycontent
     *
     * @param array $retArr Index array, passed by reference
     * @param array $content Standard content array
     */
    public function analyzeBody(&$retArr, $content)
    {
        foreach ($content['body'] as $key => $val) {
            $val = substr($val, 0, 60);
            // Cut after 60 chars because the index_words.baseword varchar field has this length. This MUST be the same.
            if (!isset($retArr[$val])) {
                // First occurrence (used for ranking results)
                $retArr[$val]['first'] = $key;
                // Word ID (wid)
                $retArr[$val]['hash'] = IndexedSearchUtility::md5inthash($val);
                // Metaphone value is also only 60 chars long
                $metaphone = $this->enableMetaphoneSearch ? substr($this->metaphone($val, $this->storeMetaphoneInfoAsWords), 0, 60) : '';
                $retArr[$val]['metaphone'] = $metaphone;
            }
            // Build metaphone fulltext string (can be used for fulltext indexing)
            if ($this->storeMetaphoneInfoAsWords) {
                $this->metaphoneContent .= ' ' . $retArr[$val]['metaphone'];
            }
            if (!($retArr[$val]['count'] ?? false)) {
                $retArr[$val]['count'] = 0;
            }

            // Increase number of occurrences
            $retArr[$val]['count']++;
            $this->wordcount++;
        }
    }

    /**
     * Creating metaphone based hash from input word
     *
     * @param string $word Word to convert
     * @param bool $returnRawMetaphoneValue If set, returns the raw metaphone value (not hashed)
     * @return mixed Metaphone hash integer (or raw value, string)
     */
    public function metaphone($word, $returnRawMetaphoneValue = false)
    {
        if (is_object($this->metaphoneObj)) {
            $metaphoneRawValue = $this->metaphoneObj->metaphone($word, $this->conf['sys_language_uid']);
        } else {
            // Use native PHP function instead of advanced doubleMetaphone class
            $metaphoneRawValue = metaphone($word);
        }
        if ($returnRawMetaphoneValue) {
            $result = $metaphoneRawValue;
        } elseif ($metaphoneRawValue !== '') {
            // Create hash and return integer
            $result = IndexedSearchUtility::md5inthash($metaphoneRawValue);
        } else {
            $result = 0;
        }
        return $result;
    }

    /********************************
     *
     * SQL; TYPO3 Pages
     *
     *******************************/
    /**
     * Updates db with information about the page (TYPO3 page, not external media)
     */
    public function submitPage()
    {
        // Remove any current data for this phash:
        $this->removeOldIndexedPages($this->hash['phash']);
        // setting new phash_row
        $fields = [
            'phash' => $this->hash['phash'],
            'phash_grouping' => $this->hash['phash_grouping'],
            'static_page_arguments' => is_array($this->conf['staticPageArguments']) ? json_encode($this->conf['staticPageArguments']) : null,
            'contentHash' => $this->content_md5h,
            'data_page_id' => $this->conf['id'],
            'data_page_type' => $this->conf['type'],
            'data_page_mp' => $this->conf['MP'],
            'gr_list' => $this->conf['gr_list'],
            'item_type' => 0,
            // TYPO3 page
            'item_title' => $this->contentParts['title'],
            'item_description' => $this->bodyDescription($this->contentParts),
            'item_mtime' => (int)$this->conf['mtime'],
            'item_size' => strlen($this->conf['content']),
            'tstamp' => $GLOBALS['EXEC_TIME'],
            'crdate' => $GLOBALS['EXEC_TIME'],
            'item_crdate' => $this->conf['crdate'],
            // Creation date of page
            'sys_language_uid' => $this->conf['sys_language_uid'],
            // Sys language uid of the page. Should reflect which language it DOES actually display!
            'externalUrl' => 0,
            'recordUid' => (int)$this->conf['recordUid'],
            'freeIndexUid' => (int)$this->conf['freeIndexUid'],
            'freeIndexSetId' => (int)$this->conf['freeIndexSetId'],
        ];
        if (IndexedSearchUtility::isTableUsed('index_phash')) {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('index_phash');
            $connection->insert(
                'index_phash',
                $fields
            );
        }
        // PROCESSING index_section
        $this->submit_section($this->hash['phash'], $this->hash['phash']);
        // PROCESSING index_grlist
        $this->submit_grlist($this->hash['phash'], $this->hash['phash']);
        // PROCESSING index_fulltext
        $fields = [
            'phash' => $this->hash['phash'],
            'fulltextdata' => implode(' ', $this->contentParts),
            'metaphonedata' => $this->metaphoneContent,
        ];
        if ($this->indexerConfig['fullTextDataLength'] > 0) {
            $fields['fulltextdata'] = substr($fields['fulltextdata'], 0, $this->indexerConfig['fullTextDataLength']);
        }
        if (IndexedSearchUtility::isTableUsed('index_fulltext')) {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('index_fulltext');
            $connection->insert('index_fulltext', $fields);
        }
        // PROCESSING index_debug
        if ($this->indexerConfig['debugMode']) {
            $fields = [
                'phash' => $this->hash['phash'],
                'debuginfo' => json_encode([
                    'external_parsers initialized' => array_keys($this->external_parsers),
                    'conf' => array_merge($this->conf, ['content' => substr($this->conf['content'], 0, 1000)]),
                    'contentParts' => array_merge($this->contentParts, ['body' => substr($this->contentParts['body'], 0, 1000)]),
                    'logs' => $this->internal_log,
                    'lexer' => $this->lexerObj->debugString,
                ]),
            ];
            if (IndexedSearchUtility::isTableUsed('index_debug')) {
                $connection = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getConnectionForTable('index_debug');
                $connection->insert('index_debug', $fields);
            }
        }
    }

    /**
     * Stores gr_list in the database.
     *
     * @param int $hash Search result record phash
     * @param int $phash_x Actual phash of current content
     * @see update_grlist()
     */
    public function submit_grlist($hash, $phash_x)
    {
        // Setting the gr_list record
        $fields = [
            'phash' => $hash,
            'phash_x' => $phash_x,
            'hash_gr_list' => IndexedSearchUtility::md5inthash($this->conf['gr_list']),
            'gr_list' => $this->conf['gr_list'],
        ];
        if (IndexedSearchUtility::isTableUsed('index_grlist')) {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('index_grlist');
            $connection->insert('index_grlist', $fields);
        }
    }

    /**
     * Stores section
     * $hash and $hash_t3 are the same for TYPO3 pages, but different when it is external files.
     *
     * @param int $hash phash of TYPO3 parent search result record
     * @param int $hash_t3 phash of the file indexation search record
     */
    public function submit_section($hash, $hash_t3)
    {
        $fields = [
            'phash' => $hash,
            'phash_t3' => $hash_t3,
            'page_id' => (int)$this->conf['id'],
        ];
        $this->getRootLineFields($fields);
        if (IndexedSearchUtility::isTableUsed('index_section')) {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('index_section');
            $connection->insert('index_section', $fields);
        }
    }

    /**
     * Removes records for the indexed page, $phash
     *
     * @param int $phash phash value to flush
     */
    public function removeOldIndexedPages($phash)
    {
        // Removing old registrations for all tables. Because the pages are TYPO3 pages
        // there can be nothing else than 1-1 relations here.
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $tableArray = ['index_phash', 'index_section', 'index_grlist', 'index_fulltext', 'index_debug'];
        foreach ($tableArray as $table) {
            if (IndexedSearchUtility::isTableUsed($table)) {
                $connectionPool->getConnectionForTable($table)->delete($table, ['phash' => (int)$phash]);
            }
        }

        // Removing all index_section records with hash_t3 set to this hash (this includes such
        // records set for external media on the page as well!). The re-insert of these records
        // are done in indexRegularDocument($file).
        if (IndexedSearchUtility::isTableUsed('index_section')) {
            $connectionPool->getConnectionForTable('index_section')
                ->delete('index_section', ['phash_t3' => (int)$phash]);
        }
    }

    /********************************
     *
     * SQL; External media
     *
     *******************************/
    /**
     * Updates db with information about the file
     *
     * @param array $hash Array with phash and phash_grouping keys for file
     * @param string $file File name
     * @param array $subinfo Array of "static_page_arguments" for files: This is for instance the page index for a PDF file (other document types it will be a zero)
     * @param string $ext File extension determining the type of media.
     * @param int $mtime Modification time of file.
     * @param int $ctime Creation time of file.
     * @param int $size Size of file in bytes
     * @param int $content_md5h Content HASH value.
     * @param array $contentParts Standard content array (using only title and body for a file)
     */
    public function submitFilePage($hash, $file, $subinfo, $ext, $mtime, $ctime, $size, $content_md5h, $contentParts)
    {
        // Find item Type:
        $storeItemType = $this->external_parsers[$ext]->ext2itemtype_map[$ext];
        $storeItemType = $storeItemType ?: $ext;
        // Remove any current data for this phash:
        $this->removeOldIndexedFiles($hash['phash']);
        // Split filename:
        $fileParts = parse_url($file);
        // Setting new
        $fields = [
            'phash' => $hash['phash'],
            'phash_grouping' => $hash['phash_grouping'],
            'static_page_arguments' => json_encode($subinfo),
            'contentHash' => $content_md5h,
            'data_filename' => $file,
            'item_type' => $storeItemType,
            'item_title' => trim($contentParts['title']) ?: PathUtility::basename($file),
            'item_description' => $this->bodyDescription($contentParts),
            'item_mtime' => $mtime,
            'item_size' => $size,
            'item_crdate' => $ctime,
            'tstamp' => $GLOBALS['EXEC_TIME'],
            'crdate' => $GLOBALS['EXEC_TIME'],
            'gr_list' => $this->conf['gr_list'],
            'externalUrl' => ($fileParts['scheme'] ?? false) ? 1 : 0,
            'recordUid' => (int)$this->conf['recordUid'],
            'freeIndexUid' => (int)$this->conf['freeIndexUid'],
            'freeIndexSetId' => (int)$this->conf['freeIndexSetId'],
            'sys_language_uid' => (int)$this->conf['sys_language_uid'],
        ];
        if (IndexedSearchUtility::isTableUsed('index_phash')) {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('index_phash');
            $connection->insert(
                'index_phash',
                $fields
            );
        }
        // PROCESSING index_fulltext
        $fields = [
            'phash' => $hash['phash'],
            'fulltextdata' => implode(' ', $contentParts),
            'metaphonedata' => $this->metaphoneContent,
        ];
        if ($this->indexerConfig['fullTextDataLength'] > 0) {
            $fields['fulltextdata'] = substr($fields['fulltextdata'], 0, $this->indexerConfig['fullTextDataLength']);
        }
        if (IndexedSearchUtility::isTableUsed('index_fulltext')) {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('index_fulltext');
            $connection->insert('index_fulltext', $fields);
        }
        // PROCESSING index_debug
        if ($this->indexerConfig['debugMode']) {
            $fields = [
                'phash' => $hash['phash'],
                'debuginfo' => json_encode([
                    'static_page_arguments' => $subinfo,
                    'contentParts' => array_merge($contentParts, ['body' => substr($contentParts['body'], 0, 1000)]),
                    'logs' => $this->internal_log,
                    'lexer' => $this->lexerObj->debugString,
                ]),
            ];
            if (IndexedSearchUtility::isTableUsed('index_debug')) {
                $connection = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getConnectionForTable('index_debug');
                $connection->insert('index_debug', $fields);
            }
        }
    }

    /**
     * Stores file gr_list for a file IF it does not exist already
     *
     * @param int $hash phash value of file
     */
    public function submitFile_grlist($hash)
    {
        // Testing if there is a gr_list record for a non-logged in user and if so, there is no need to place another one.
        if (!IndexedSearchUtility::isTableUsed('index_grlist')) {
            return;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('index_grlist');
        $count = (int)$queryBuilder->count('*')
            ->from('index_grlist')
            ->where(
                $queryBuilder->expr()->eq(
                    'phash',
                    $queryBuilder->createNamedParameter($hash, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq(
                        'hash_gr_list',
                        $queryBuilder->createNamedParameter(
                            IndexedSearchUtility::md5inthash($this->defaultGrList),
                            \PDO::PARAM_INT
                        )
                    ),
                    $queryBuilder->expr()->eq(
                        'hash_gr_list',
                        $queryBuilder->createNamedParameter(
                            IndexedSearchUtility::md5inthash($this->conf['gr_list']),
                            \PDO::PARAM_INT
                        )
                    )
                )
            )
            ->executeQuery()
            ->fetchOne();

        if ($count === 0) {
            $this->submit_grlist($hash, $hash);
        }
    }

    /**
     * Stores file section for a file IF it does not exist
     *
     * @param int $hash phash value of file
     */
    public function submitFile_section($hash)
    {
        // Testing if there is already a section
        if (!IndexedSearchUtility::isTableUsed('index_section')) {
            return;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('index_section');
        $count = (int)$queryBuilder->count('phash')
            ->from('index_section')
            ->where(
                $queryBuilder->expr()->eq(
                    'phash',
                    $queryBuilder->createNamedParameter($hash, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'page_id',
                    $queryBuilder->createNamedParameter($this->conf['id'], \PDO::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchOne();

        if ($count === 0) {
            $this->submit_section($hash, $this->hash['phash']);
        }
    }

    /**
     * Removes records for the indexed page, $phash
     *
     * @param int $phash phash value to flush
     */
    public function removeOldIndexedFiles($phash)
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        // Removing old registrations for tables.
        $tableArray = ['index_phash', 'index_grlist', 'index_fulltext', 'index_debug'];
        foreach ($tableArray as $table) {
            if (!IndexedSearchUtility::isTableUsed($table)) {
                continue;
            }
            $connectionPool->getConnectionForTable($table)->delete($table, ['phash' => (int)$phash]);
        }
    }

    /********************************
     *
     * SQL Helper functions
     *
     *******************************/
    /**
     * Check the mtime / tstamp of the currently indexed page/file (based on phash)
     * Return positive integer if the page needs to be indexed
     *
     * @param int $mtime mtime value to test against limits and indexed page (usually this is the mtime of the cached document)
     * @param int $phash "phash" used to select any already indexed page to see what its mtime is.
     * @return int Result integer: Generally: <0 = No indexing, >0 = Do indexing (see $this->reasons): -2) Min age was NOT exceeded and so indexing cannot occur.  -1) mtime matched so no need to reindex page. 0) N/A   1) Max age exceeded, page must be indexed again.   2) mtime of indexed page doesn't match mtime given for current content and we must index page.  3) No mtime was set, so we will index...  4) No indexed page found, so of course we will index.
     */
    public function checkMtimeTstamp($mtime, $phash)
    {
        if (!IndexedSearchUtility::isTableUsed('index_phash')) {
            // Not indexed (not in index_phash)
            $result = 4;
        } else {
            $row = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('index_phash')
                ->select(
                    ['item_mtime', 'tstamp'],
                    'index_phash',
                    ['phash' => (int)$phash],
                    [],
                    [],
                    1
                )
                ->fetchAssociative();
            // If there was an indexing of the page...:
            if (!empty($row)) {
                if ($this->tstamp_maxAge && $GLOBALS['EXEC_TIME'] > $row['tstamp'] + $this->tstamp_maxAge) {
                    // If max age is exceeded, index the page
                    // The configured max-age was exceeded for the document and thus it's indexed.
                    $result = 1;
                } else {
                    if (!$this->tstamp_minAge || $GLOBALS['EXEC_TIME'] > $row['tstamp'] + $this->tstamp_minAge) {
                        // if minAge is not set or if minAge is exceeded, consider at mtime
                        if ($mtime) {
                            // It mtime is set, then it's tested. If not, the page must clearly be indexed.
                            if ($row['item_mtime'] != $mtime) {
                                // And if mtime is different from the index_phash mtime, it's about time to re-index.
                                // The minimum age was exceed and mtime was set and the mtime was different, so the page was indexed.
                                $result = 2;
                            } else {
                                // mtime matched the document, so no changes detected and no content updated
                                $result = -1;
                                if ($this->tstamp_maxAge) {
                                    $this->log_setTSlogMessage('mtime matched, timestamp NOT updated because a maxAge is set (' . ($row['tstamp'] + $this->tstamp_maxAge - $GLOBALS['EXEC_TIME']) . ' seconds to expire time).', LogLevel::WARNING);
                                } else {
                                    $this->updateTstamp($phash);
                                    $this->log_setTSlogMessage('mtime matched, timestamp updated.', LogLevel::NOTICE);
                                }
                            }
                        } else {
                            // The minimum age was exceed, but mtime was not set, so the page was indexed.
                            $result = 3;
                        }
                    } else {
                        // The minimum age was not exceeded
                        $result = -2;
                    }
                }
            } else {
                // Page has never been indexed (is not represented in the index_phash table).
                $result = 4;
            }
        }
        return $result;
    }

    /**
     * Check content hash in phash table
     *
     * @return mixed Returns TRUE if the page needs to be indexed (that is, there was no result), otherwise the phash value (in an array) of the phash record to which the grlist_record should be related!
     */
    public function checkContentHash()
    {
        // With this query the page will only be indexed if it's content is different from the same "phash_grouping" -page.
        $result = true;
        if (IndexedSearchUtility::isTableUsed('index_phash')) {
            $row = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('index_phash')
                ->select(
                    ['phash'],
                    'index_phash',
                    [
                        'phash_grouping' => (int)$this->hash['phash_grouping'],
                        'contentHash' => (int)$this->content_md5h,
                    ],
                    [],
                    [],
                    1
                )
                ->fetchAssociative();

            if (!empty($row)) {
                $result = $row;
            }
        }
        return $result;
    }

    /**
     * Check content hash for external documents
     * Returns TRUE if the document needs to be indexed (that is, there was no result)
     *
     * @param int $hashGr phash value to check (phash_grouping)
     * @param int $content_md5h Content hash to check
     * @return bool Returns TRUE if the document needs to be indexed (that is, there was no result)
     */
    public function checkExternalDocContentHash($hashGr, $content_md5h)
    {
        $result = true;
        if (IndexedSearchUtility::isTableUsed('index_phash')) {
            $count = (int)GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('index_phash')
                ->count(
                    '*',
                    'index_phash',
                    [
                        'phash_grouping' => (int)$hashGr,
                        'contentHash' => (int)$content_md5h,
                    ]
                );

            $result = $count === 0;
        }
        return $result;
    }

    /**
     * Checks if a grlist record has been set for the phash value input (looking at the "real" phash of the current content, not the linked-to phash of the common search result page)
     *
     * @param int $phash_x Phash integer to test.
     * @return bool
     */
    public function is_grlist_set($phash_x)
    {
        $result = false;
        if (IndexedSearchUtility::isTableUsed('index_grlist')) {
            $count = (int)GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('index_grlist')
                ->count(
                    'phash_x',
                    'index_grlist',
                    ['phash_x' => (int)$phash_x]
                );

            $result = $count > 0;
        }
        return $result;
    }

    /**
     * Check if a grlist-entry for this hash exists and if not so, write one.
     *
     * @param int $phash phash of the search result that should be found
     * @param int $phash_x The real phash of the current content. The two values are different when a page with userlogin turns out to contain the exact same content as another already indexed version of the page; This is the whole reason for the grlist table in fact...
     * @see submit_grlist()
     */
    public function update_grlist($phash, $phash_x)
    {
        if (IndexedSearchUtility::isTableUsed('index_grlist')) {
            $count = (int)GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('index_grlist')
                ->count(
                    'phash',
                    'index_grlist',
                    [
                        'phash' => (int)$phash,
                        'hash_gr_list' => IndexedSearchUtility::md5inthash($this->conf['gr_list']),
                    ]
                );

            if ($count === 0) {
                $this->submit_grlist($phash, $phash_x);
                $this->log_setTSlogMessage('Inserted gr_list \'' . $this->conf['gr_list'] . '\' for phash \'' . $phash . '\'', LogLevel::NOTICE);
            }
        }
    }

    /**
     * Update tstamp for a phash row.
     *
     * @param int $phash phash value
     * @param int $mtime If set, update the mtime field to this value.
     */
    public function updateTstamp($phash, $mtime = 0)
    {
        if (!IndexedSearchUtility::isTableUsed('index_phash')) {
            return;
        }

        $updateFields = [
            'tstamp' => $GLOBALS['EXEC_TIME'],
        ];

        if ($mtime) {
            $updateFields['item_mtime'] = (int)$mtime;
        }

        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('index_phash')
            ->update(
                'index_phash',
                $updateFields,
                [
                    'phash' => (int)$phash,
                ]
            );
    }

    /**
     * Update SetID of the index_phash record.
     *
     * @param int $phash phash value
     */
    public function updateSetId($phash)
    {
        if (!IndexedSearchUtility::isTableUsed('index_phash')) {
            return;
        }

        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('index_phash')
            ->update(
                'index_phash',
                [
                    'freeIndexSetId' => (int)$this->conf['freeIndexSetId'],
                ],
                [
                    'phash' => (int)$phash,
                ]
            );
    }

    /**
     * Update parsetime for phash row.
     *
     * @param int $phash phash value.
     * @param int $parsetime Parsetime value to set.
     */
    public function updateParsetime($phash, $parsetime)
    {
        if (!IndexedSearchUtility::isTableUsed('index_phash')) {
            return;
        }

        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('index_phash')
            ->update(
                'index_phash',
                [
                    'parsetime' => (int)$parsetime,
                ],
                [
                    'phash' => (int)$phash,
                ]
            );
    }

    /**
     * Update section rootline for the page
     */
    public function updateRootline()
    {
        if (!IndexedSearchUtility::isTableUsed('index_section')) {
            return;
        }

        $updateFields = [];
        $this->getRootLineFields($updateFields);

        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('index_section')
            ->update(
                'index_section',
                $updateFields,
                [
                    'page_id' => (int)$this->conf['id'],
                ]
            );
    }

    /**
     * Adding values for root-line fields.
     * rl0, rl1 and rl2 are standard. A hook might add more.
     *
     * @param array $fieldArray Field array, passed by reference
     */
    public function getRootLineFields(array &$fieldArray)
    {
        $fieldArray['rl0'] = (int)($this->conf['rootline_uids'][0] ?? 0);
        $fieldArray['rl1'] = (int)($this->conf['rootline_uids'][1] ?? 0);
        $fieldArray['rl2'] = (int)($this->conf['rootline_uids'][2] ?? 0);
        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['addRootLineFields'] ?? [] as $fieldName => $rootLineLevel) {
            $fieldArray[$fieldName] = (int)$this->conf['rootline_uids'][$rootLineLevel];
        }
    }

    /********************************
     *
     * SQL; Submitting words
     *
     *******************************/
    /**
     * Adds new words to db
     *
     * @param array $wordListArray Word List array (where each word has information about position etc).
     */
    public function checkWordList($wordListArray)
    {
        if (!IndexedSearchUtility::isTableUsed('index_words') || empty($wordListArray)) {
            return;
        }

        $wordListArrayCount = count($wordListArray);
        $phashArray = array_map('intval', array_column($wordListArray, 'hash'));

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('index_words');
        $count = (int)$queryBuilder->count('baseword')
            ->from('index_words')
            ->where(
                $queryBuilder->expr()->in(
                    'wid',
                    $queryBuilder->createNamedParameter($phashArray, Connection::PARAM_INT_ARRAY)
                )
            )
            ->executeQuery()
            ->fetchOne();

        if ($count !== $wordListArrayCount) {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('index_words');
            $queryBuilder = $connection->createQueryBuilder();

            $result = $queryBuilder->select('baseword')
                ->from('index_words')
                ->where(
                    $queryBuilder->expr()->in(
                        'wid',
                        $queryBuilder->createNamedParameter($phashArray, Connection::PARAM_INT_ARRAY)
                    )
                )
                ->executeQuery();

            $this->log_setTSlogMessage('Inserting words: ' . ($wordListArrayCount - $count), LogLevel::NOTICE);
            while ($row = $result->fetchAssociative()) {
                unset($wordListArray[$row['baseword']]);
            }

            foreach ($wordListArray as $key => $val) {
                // A duplicate-key error will occur here if a word is NOT unset in the unset() line. However as
                // long as the words in $wl are NOT longer as 60 chars (the baseword varchar is 60 characters...)
                // this is not a problem.
                $connection->insert(
                    'index_words',
                    [
                        'wid' => $val['hash'],
                        'baseword' => $key,
                        'metaphone' => $val['metaphone'],
                    ]
                );
            }
        }
    }

    /**
     * Submits RELATIONS between words and phash
     *
     * @param array $wordList Word list array
     * @param int $phash phash value
     */
    public function submitWords($wordList, $phash)
    {
        if (!IndexedSearchUtility::isTableUsed('index_rel')) {
            return;
        }
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('index_words');
        $result = $queryBuilder->select('wid')
            ->from('index_words')
            ->where(
                $queryBuilder->expr()->neq('is_stopword', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
            )
            ->groupBy('wid')
            ->executeQuery();

        $stopWords = [];
        while ($row = $result->fetchAssociative()) {
            $stopWords[$row['wid']] = $row;
        }

        $connectionPool->getConnectionForTable('index_rel')->delete('index_rel', ['phash' => (int)$phash]);

        $fields = ['phash', 'wid', 'count', 'first', 'freq', 'flags'];
        $rows = [];
        foreach ($wordList as $val) {
            if (isset($stopWords[$val['hash']])) {
                continue;
            }
            $rows[] = [
                (int)$phash,
                (int)$val['hash'],
                (int)$val['count'],
                (int)($val['first'] ?? 0),
                $this->freqMap($val['count'] / $this->wordcount),
                ($val['cmp'] ?? 0) & $this->flagBitMask,
            ];
        }

        if (!empty($rows)) {
            $connectionPool->getConnectionForTable('index_rel')->bulkInsert('index_rel', $rows, $fields);
        }
    }

    /**
     * maps frequency from a real number in [0;1] to an integer in [0;$this->freqRange] with anything above $this->freqMax as 1
     * and back.
     *
     * @param float $freq Frequency
     * @return int Frequency in range.
     */
    public function freqMap($freq)
    {
        $mapFactor = $this->freqMax * 100 * $this->freqRange;
        if ($freq <= 1) {
            $newFreq = $freq * $mapFactor;
            $newFreq = $newFreq > $this->freqRange ? $this->freqRange : $newFreq;
        } else {
            $newFreq = $freq / $mapFactor;
        }
        return (int)$newFreq;
    }

    /********************************
     *
     * Hashing
     *
     *******************************/
    /**
     * Get search hash, T3 pages
     */
    public function setT3Hashes()
    {
        //  Set main array:
        $hArray = [
            'id' => (int)$this->conf['id'],
            'type' => (int)$this->conf['type'],
            'sys_lang' => (int)$this->conf['sys_language_uid'],
            'MP' => (string)$this->conf['MP'],
            'staticPageArguments' => is_array($this->conf['staticPageArguments']) ? json_encode($this->conf['staticPageArguments']) : null,
        ];
        // Set grouping hash (Identifies a "page" combined of id, type, language, mountpoint and cHash parameters):
        $this->hash['phash_grouping'] = IndexedSearchUtility::md5inthash(serialize($hArray));
        // Add gr_list and set plain phash (Subdivision where special page composition based on login is taken into account as well. It is expected that such pages are normally similar regardless of the login.)
        $hArray['gr_list'] = (string)$this->conf['gr_list'];
        $this->hash['phash'] = IndexedSearchUtility::md5inthash(serialize($hArray));
    }

    /**
     * Get search hash, external files
     *
     * @param string $file File name / path which identifies it on the server
     * @param array $subinfo Additional content identifying the (subpart of) content. For instance; PDF files are divided into groups of pages for indexing.
     * @return array Array with "phash_grouping" and "phash" inside.
     */
    public function setExtHashes($file, $subinfo = [])
    {
        //  Set main array:
        $hash = [];
        $hArray = [
            'file' => $file,
        ];
        // Set grouping hash:
        $hash['phash_grouping'] = IndexedSearchUtility::md5inthash(serialize($hArray));
        // Add subinfo
        $hArray['subinfo'] = $subinfo;
        $hash['phash'] = IndexedSearchUtility::md5inthash(serialize($hArray));
        return $hash;
    }

    /*********************************
     *
     * Internal logging functions
     *
     *********************************/
    /**
     * Push function wrapper for TT logging
     *
     * @param string $msg Title to set
     * @param string $key Key (?)
     */
    public function log_push($msg, $key)
    {
        $this->timeTracker->push($msg, $key);
    }

    /**
     * Pull function wrapper for TT logging
     */
    public function log_pull()
    {
        $this->timeTracker->pull();
    }

    /**
     * Set log message function wrapper for TT logging
     *
     * @param string $msg Message to set
     * @param int|string $logLevel
     */
    public function log_setTSlogMessage($msg, $logLevel = LogLevel::INFO)
    {
        $this->timeTracker->setTSlogMessage($msg, $logLevel);
        $this->internal_log[] = $msg;
    }

    /**
     * Makes sure that keywords are space-separated. This is important for their
     * proper displaying as a part of fulltext index.
     *
     * @param string $keywordList
     * @return string
     * @see https://forge.typo3.org/issues/14959
     */
    protected function addSpacesToKeywordList($keywordList)
    {
        $keywords = GeneralUtility::trimExplode(',', $keywordList);
        return ' ' . implode(', ', $keywords) . ' ';
    }
}
