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
use TYPO3\CMS\IndexedSearch\Dto\IndexingDataAsArray;
use TYPO3\CMS\IndexedSearch\Dto\IndexingDataAsString;
use TYPO3\CMS\IndexedSearch\Type\IndexStatus;
use TYPO3\CMS\IndexedSearch\Utility\IndexedSearchUtility;

/**
 * Indexing class for TYPO3 frontend
 *
 * @internal
 */
class Indexer
{
    /**
     * HTML code blocks to exclude from indexing
     */
    public string $excludeSections = 'script,style';

    /**
     * Supported Extensions for external files
     */
    public array $external_parsers = [];

    /**
     * If set, this tells a number of seconds that is the maximum age of an indexed document.
     * Regardless of mtime the document will be re-indexed if this limit is exceeded.
     */
    public int $tstamp_minAge = 0;

    /**
     * If set, this tells a minimum limit before a document can be indexed again. This is regardless of mtime.
     */
    public int $maxExternalFiles = 0;

    /**
     * Max number of external files to index.
     */
    public bool $forceIndexing = false;

    public array $defaultIndexingDataPayload = [
        'title' => '',
        'description' => '',
        'keywords' => '',
        'body' => '',
    ];

    public int $wordcount = 0;
    public int $externalFileCounter = 0;
    public array $conf = [];

    /**
     * Configuration set internally (see init functions for required keys and their meaning)
     */
    public array $indexerConfig = [];

    /**
     * Indexer configuration, coming from TYPO3's system configuration for EXT:indexed_search
     */
    public array $hash = [];

    /**
     * Hash array, contains phash and phash_grouping
     */
    public array $file_phash_arr = [];

    public IndexingDataAsString $indexingDataStringDto;

    /**
     * Content of TYPO3 page
     */
    public string $content_md5h = '';
    public string $indexExternalUrl_content = '';
    public int $freqRange = 32000;
    public float $freqMax = 0.1;
    public int $flagBitMask;

    public function __construct(
        private readonly TimeTracker $timeTracker,
        private readonly Lexer $lexer,
        private readonly RequestFactory $requestFactory,
        private readonly ConnectionPool $connectionPool,
        ExtensionConfiguration $extensionConfiguration,
    ) {
        // Indexer configuration from Extension Manager interface
        $this->indexerConfig = $extensionConfiguration->get('indexed_search');
        $this->tstamp_minAge = MathUtility::forceIntegerInRange((int)($this->indexerConfig['minAge'] ?? 0) * 3600, 0);
        $this->maxExternalFiles = MathUtility::forceIntegerInRange((int)($this->indexerConfig['maxExternalFiles'] ?? 5), 0, 1000);
        $this->flagBitMask = MathUtility::forceIntegerInRange((int)($this->indexerConfig['flagBitMask'] ?? 0), 0, 255);
    }

    /**
     * @param array|null $configuration will be used to set $this->conf, otherwise $this->conf MUST be set with proper values prior to this call
     */
    public function init(?array $configuration = null): void
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
    }

    public function initializeExternalParsers(): void
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
    public function indexTypo3PageContent(): void
    {
        $indexStatus = $this->getIndexStatus($this->conf['mtime'], $this->hash['phash']);
        $reindexingRequired = $indexStatus->reindexRequired();
        $is_grlist = $this->is_grlist_set($this->hash['phash']);
        if ($reindexingRequired || !$is_grlist || $this->forceIndexing) {
            // Setting message:
            if ($this->forceIndexing) {
                $this->log_setTSlogMessage('Indexing needed, reason: Forced', LogLevel::NOTICE);
            } elseif ($reindexingRequired) {
                $this->log_setTSlogMessage('Indexing needed, reason: ' . $indexStatus->reason(), LogLevel::NOTICE);
            } else {
                $this->log_setTSlogMessage('Indexing needed, reason: Updates gr_list!', LogLevel::NOTICE);
            }
            // Divide into title,keywords,description and body:
            $this->timeTracker->push('Split content');
            $this->indexingDataStringDto = $this->splitHTMLContent($this->conf['content']);
            if ($this->conf['indexedDocTitle']) {
                $this->indexingDataStringDto->title = $this->conf['indexedDocTitle'];
            }
            $this->timeTracker->pull();
            // Calculating a hash over what is to be the actual page content. Maybe this hash should not include title,description and keywords? The bodytext is the primary concern. (on the other hand a changed page-title would make no difference then, so don't!)
            $this->content_md5h = md5(implode('', $this->indexingDataStringDto->toArray()));
            // This function checks if there is already a page (with gr_list = 0,-1) indexed and if that page has the very same contentHash.
            // If the contentHash is the same, then we can rest assured that this page is already indexed and regardless of mtime and origContent we don't need to do anything more.
            // This will also prevent pages from being indexed if a fe_users has logged in, and it turns out that the page content is not changed anyway. fe_users logged in should always search with hash_gr_list = "0,-1" OR "[their_group_list]". This situation will be prevented only if the page has been indexed with no user login on before hand. Else the page will be indexed by users until that event. However that does not present a serious problem.
            $checkCHash = $this->checkContentHash();
            if (!is_array($checkCHash) || $reindexingRequired) {
                $Pstart = $this->milliseconds();
                $this->timeTracker->push('Converting entities of content');
                $this->charsetEntity2utf8($this->indexingDataStringDto);
                $this->timeTracker->pull();

                // Splitting words
                $this->timeTracker->push('Extract words from content');
                $splitInWords = $this->processWordsInArrays($this->indexingDataStringDto);
                $this->timeTracker->pull();

                // Analyze the indexed words.
                $this->timeTracker->push('Analyze the extracted words');
                $indexArr = $this->indexAnalyze($splitInWords);
                $this->timeTracker->pull();

                // Submitting page (phash) record
                $this->timeTracker->push('Submitting page');
                $this->submitPage();
                $this->timeTracker->pull();

                // Check words and submit to word list if not there
                $this->timeTracker->push('Check word list and submit words');
                if (!IndexedSearchUtility::isMysqlFullTextEnabled()) {
                    $indexArr = $this->removePhashCollisions($indexArr);
                    $this->checkWordList($indexArr);
                    $this->submitWords($indexArr, $this->hash['phash']);
                }
                $this->timeTracker->pull();

                // Set parse time
                $this->updateParsetime($this->hash['phash'], $this->milliseconds() - $Pstart);

                // Checking external files if configured for.
                if ($this->conf['index_externals']) {
                    $this->timeTracker->push('Checking external files', '');
                    $this->extractLinks($this->conf['content']);
                    $this->timeTracker->pull();
                }
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
            $this->log_setTSlogMessage('Indexing not needed, reason: ' . $indexStatus->reason());
        }
    }

    /**
     * Splits HTML content and returns an associative array, with title, a list of meta tags, and a list of words in the body.
     *
     * @param string $content HTML content to index. To some degree expected to be made by TYPO3 (i.e. splitting the header by ":")
     */
    public function splitHTMLContent(string $content): IndexingDataAsString
    {
        $indexingDataDto = IndexingDataAsString::fromArray($this->defaultIndexingDataPayload);
        $indexingDataDto->body = stristr($content, '<body') ?: '';
        $headPart = substr($content, 0, -strlen($indexingDataDto->body));
        // get title
        $this->embracingTags($headPart, 'TITLE', $indexingDataDto->title, $dummy2, $dummy);
        $titleParts = explode(':', $indexingDataDto->title, 2);
        $indexingDataDto->title = trim($titleParts[1] ?? $titleParts[0]);
        // get keywords and description meta tags
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
                    $indexingDataDto->keywords .= ',' . $this->addSpacesToKeywordList($meta[$i]['content']);
                }
                if (stripos(($meta[$i]['name'] ?? ''), 'description') !== false) {
                    $indexingDataDto->description .= ',' . $meta[$i]['content'];
                }
            }
        }
        // Process <!--TYPO3SEARCH_begin--> or <!--TYPO3SEARCH_end--> tags:
        $this->typoSearchTags($indexingDataDto->body);
        // Get rid of unwanted sections (i.e. scripting and style stuff) in body
        $tagList = explode(',', $this->excludeSections);
        foreach ($tagList as $tag) {
            while ($this->embracingTags($indexingDataDto->body, $tag, $dummy, $indexingDataDto->body, $dummy2)) {
            }
        }
        // remove tags, but first make sure we don't concatenate words by doing it
        $indexingDataDto->body = str_replace('<', ' <', $indexingDataDto->body);
        $indexingDataDto->body = trim(strip_tags($indexingDataDto->body));
        $indexingDataDto->keywords = trim($indexingDataDto->keywords);
        $indexingDataDto->description = trim($indexingDataDto->description);

        return $indexingDataDto;
    }

    /**
     * Extract the charset value from HTML meta tag.
     */
    public function getHTMLcharset(string $content): string
    {
        // @todo: Use \DOMDocument and DOMXpath
        if (preg_match('/<meta[[:space:]]+[^>]*http-equiv[[:space:]]*=[[:space:]]*["\']CONTENT-TYPE["\'][^>]*>/i', $content, $reg)
            && preg_match('/charset[[:space:]]*=[[:space:]]*([[:alnum:]-]+)/i', $reg[0], $reg2)
        ) {
            return $reg2[1];
        }

        return '';
    }

    /**
     * Converts a HTML document to utf-8
     */
    public function convertHTMLToUtf8(string $content, string $charset = ''): string
    {
        // Find charset
        $charset = $charset ?: $this->getHTMLcharset($content);
        $charset = strtolower(trim($charset));
        // Convert charset
        if ($charset && $charset !== 'utf-8') {
            $content = mb_convert_encoding($content, 'utf-8', $charset);
        }
        // Convert entities, assuming document is now UTF-8
        return html_entity_decode($content);
    }

    /**
     * Finds first occurrence of embracing tags and returns the embraced content and the original string with
     * the tag removed in the two passed variables. Returns FALSE if no match found. i.e. useful for finding
     * <title> of document or removing <script>-sections
     *
     * @param string $string String to search in
     * @param string $tagName Tag name, eg. "script
     * @param string|null $tagContent Passed by reference: Content inside found tag
     * @param string|null $stringAfter Passed by reference: Content after found tag
     * @param string|null $paramList Passed by reference: Attributes of the found tag.
     */
    public function embracingTags(string $string, string $tagName, ?string &$tagContent, ?string &$stringAfter, ?string &$paramList): bool
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
            $stringBefore = substr($string, 0, (int)stripos($string, $startTag));
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
    public function typoSearchTags(string &$body): bool
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
     */
    public function extractLinks(string $content): void
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
            if (($qParts['query'] ?? false) && str_contains($qParts['query'], 'jumpurl=')) {
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
     * @return array<int, array{tag: string, href: string, localPath: string}>
     */
    public function extractHyperLinks(string $html): array
    {
        $htmlParser = GeneralUtility::makeInstance(HtmlParser::class);
        $htmlParts = $htmlParser->splitTags('a', $html);
        $hyperLinksData = [];
        foreach ($htmlParts as $index => $tagData) {
            if ($index % 2 !== 0) {
                $tagAttributes = $htmlParser->get_tag_attributes($tagData, true);
                $firstTagName = $htmlParser->getFirstTagName($tagData);
                if (strtolower($firstTagName) === 'a') {
                    if (!empty($tagAttributes[0]['href']) && !str_starts_with($tagAttributes[0]['href'], '#')) {
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
     */
    public function extractBaseHref(string $html): string
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
     */
    public function indexExternalUrl(string $externalUrl): void
    {
        // Get headers:
        $urlHeaders = $this->getUrlHeaders($externalUrl);
        if (is_array($urlHeaders) && stripos($urlHeaders['Content-Type'], 'text/html') !== false) {
            $content = ($this->indexExternalUrl_content = GeneralUtility::getUrl($externalUrl));
            if ((string)$content !== '') {
                // Create temporary file:
                $tmpFile = GeneralUtility::tempnam('EXTERNAL_URL');
                GeneralUtility::writeFile($tmpFile, $content, true);
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
     * @return array<string, string>|false If no answer, returns FALSE. Otherwise, an array where HTTP headers are keys
     */
    public function getUrlHeaders(string $url): array|false
    {
        try {
            $response = $this->requestFactory->request($url, 'HEAD');
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
     * @return string Absolute path to file if file is local, else empty string
     */
    protected function createLocalPath(string $sourcePath): string
    {
        $localPath = $this->createLocalPathUsingAbsRefPrefix($sourcePath);
        if ($localPath !== '') {
            return $localPath;
        }
        $localPath = $this->createLocalPathUsingDomainURL($sourcePath);
        if ($localPath !== '') {
            return $localPath;
        }
        $localPath = $this->createLocalPathFromAbsoluteURL($sourcePath);
        if ($localPath !== '') {
            return $localPath;
        }
        return $this->createLocalPathFromRelativeURL($sourcePath);
    }

    /**
     * Attempts to create a local file path by matching a current request URL.
     */
    protected function createLocalPathUsingDomainURL(string $sourcePath): string
    {
        $localPath = '';
        $baseURL = $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getSiteUrl();
        $baseURLLength = strlen($baseURL);
        if (str_starts_with($sourcePath, $baseURL)) {
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
     */
    protected function createLocalPathUsingAbsRefPrefix(string $sourcePath): string
    {
        $localPath = '';
        $request = $GLOBALS['TYPO3_REQUEST'];
        $frontendTypoScriptConfigArray = $request->getAttribute('frontend.typoscript')?->getConfigArray();
        if ($frontendTypoScriptConfigArray) {
            $absRefPrefix = $frontendTypoScriptConfigArray['absRefPrefix'] ?? '';
            $absRefPrefixLength = strlen($absRefPrefix);
            if ($absRefPrefixLength > 0 && str_starts_with($sourcePath, $absRefPrefix)) {
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
     * Attempts to create a local file path from the absolute URL without schema.
     */
    protected function createLocalPathFromAbsoluteURL(string $sourcePath): string
    {
        $localPath = '';
        if (str_starts_with($sourcePath, '/')) {
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
     */
    protected function createLocalPathFromRelativeURL(string $sourcePath): string
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
     */
    protected static function isRelativeURL(string $url): bool
    {
        $urlParts = @parse_url($url);
        return (!isset($urlParts['scheme']) || $urlParts['scheme'] === '') && !str_starts_with(($urlParts['path'][0] ?? ''), '/');
    }

    /**
     * Checks if the path points to the file inside the website
     */
    protected static function isAllowedLocalFile(string $filePath): bool
    {
        $filePath = GeneralUtility::resolveBackPath($filePath);
        $insideWebPath = str_starts_with($filePath, Environment::getPublicPath());
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
     * @param string $file Relative Filename, relative to public web path. It can also be an absolute path as long as it is inside the lockRootPath. Finally, if $contentTmpFile is set, this value can be anything, most likely a URL
     * @param bool $force If set, indexing is forced (despite content hashes, mtime etc).
     * @param string $contentTmpFile Temporary file with the content to read it from (instead of $file). Used when the $file is a URL.
     * @param string $altExtension File extension for temporary file.
     */
    public function indexRegularDocument(string $file, bool $force = false, string $contentTmpFile = '', string $altExtension = ''): void
    {
        $fI = pathinfo($file);
        $ext = $altExtension ?: strtolower($fI['extension']);
        // Create abs-path
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
                    $this->timeTracker->push('Index: ' . str_replace('.', '_', PathUtility::basename($file)) . ($cPKey ? '#' . $cPKey : ''));
                    $Pstart = $this->milliseconds();
                    $subinfo = ['key' => $cPKey];
                    // Setting page range. This is "0" (zero) when no division is made, otherwise a range like "1-3"
                    $phash_arr = ($this->file_phash_arr = $this->setExtHashes($file, $subinfo));
                    $indexStatus = $this->getIndexStatus($fileInfo['mtime'], $phash_arr['phash']);
                    $reindexingRequired = $indexStatus->reindexRequired();
                    if ($reindexingRequired || $force) {
                        if ($reindexingRequired) {
                            $this->log_setTSlogMessage('Indexing needed, reason: ' . $indexStatus->reason(), LogLevel::NOTICE);
                        } else {
                            $this->log_setTSlogMessage('Indexing forced by flag', LogLevel::NOTICE);
                        }
                        // Check external file counter:
                        if ($this->externalFileCounter < $this->maxExternalFiles || $force) {
                            // Divide into title,keywords,description and body:
                            $this->timeTracker->push('Split content');
                            $indexingDataDtoAsString = $this->readFileContent($ext, $absFile, $cPKey);
                            $this->timeTracker->pull();
                            if ($indexingDataDtoAsString !== null) {
                                // Calculating a hash over what is to be the actual content. (see indexTypo3PageContent())
                                $content_md5h = md5(implode('', $indexingDataDtoAsString->toArray()));
                                if ($this->checkExternalDocContentHash($phash_arr['phash_grouping'], $content_md5h) || $force) {
                                    // Increment counter:
                                    $this->externalFileCounter++;

                                    // Splitting words
                                    $this->timeTracker->push('Extract words from content');
                                    $splitInWords = $this->processWordsInArrays($indexingDataDtoAsString);
                                    $this->timeTracker->pull();

                                    // Analyze the indexed words.
                                    $this->timeTracker->push('Analyze the extracted words');
                                    $indexArr = $this->indexAnalyze($splitInWords);
                                    $this->timeTracker->pull();

                                    // Submitting page (phash) record
                                    $this->timeTracker->push('Submitting page');

                                    // Unfortunately the original creation time cannot be determined, therefore we fall back to the modification date
                                    $this->submitFilePage($phash_arr, $file, $subinfo, $ext, $fileInfo['mtime'], $fileInfo['ctime'], $fileInfo['size'], $content_md5h, $indexingDataDtoAsString);
                                    $this->timeTracker->pull();

                                    // Check words and submit to word list if not there
                                    $this->timeTracker->push('Check word list and submit words');
                                    if (!IndexedSearchUtility::isMysqlFullTextEnabled()) {
                                        $indexArr = $this->removePhashCollisions($indexArr);
                                        $this->checkWordList($indexArr);
                                        $this->submitWords($indexArr, $phash_arr['phash']);
                                    }
                                    $this->timeTracker->pull();

                                    // Set parsetime
                                    $this->updateParsetime($phash_arr['phash'], $this->milliseconds() - $Pstart);
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
                        $this->log_setTSlogMessage('Indexing not needed, reason: ' . $indexStatus->reason());
                    }
                    // Checking and setting sections:
                    $this->submitFile_section($phash_arr['phash']);
                    // Setting a section-record for the file. This is done also if the file is not indexed. Notice that section records are deleted when the page is indexed.
                    $this->timeTracker->pull();
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
     * @param string|int $sectionPointer Pointer to section (zero for all other than PDF which will have an indication of pages into which the document should be splitted.)
     */
    public function readFileContent(string $fileExtension, string $absoluteFileName, string|int $sectionPointer): ?IndexingDataAsString
    {
        $indexingDataDto = null;
        // Consult relevant external document parser
        if (is_object($this->external_parsers[$fileExtension])) {
            $indexingDataDto = $this->external_parsers[$fileExtension]->readFileContent($fileExtension, $absoluteFileName, $sectionPointer);
        }

        if ($indexingDataDto instanceof IndexingDataAsString) {
            return $indexingDataDto;
        }

        return null;
    }

    /**
     * Creates an array with pointers to divisions of document.
     *
     * @param string $ext File extension
     * @param string $absFile Absolute filename (must exist and be validated OK before calling function)
     * @return array Array of pointers to sections that the document should be divided into
     */
    public function fileContentParts(string $ext, string $absFile): array
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
     */
    public function splitRegularContent(string $content): IndexingDataAsString
    {
        $indexingDataDto = IndexingDataAsString::fromArray($this->defaultIndexingDataPayload);
        $indexingDataDto->body = $content;

        return $indexingDataDto;
    }

    /**********************************
     *
     * Analysing content, Extracting words
     *
     **********************************/
    /**
     * Convert character set and HTML entities in the value of input content array keys
     */
    public function charsetEntity2utf8(IndexingDataAsString $indexingDataDto): void
    {
        // Convert charset if necessary
        foreach ($indexingDataDto->toArray() as $key => $value) {
            if ((string)$value !== '') {
                // decode all numeric / html-entities in the string to real characters:
                $indexingDataDto->{$key} = html_entity_decode($value);
            }
        }
    }

    /**
     * Processing words in the array from split*Content -functions. Values are ensured to be unique.
     */
    public function processWordsInArrays(IndexingDataAsString $input): IndexingDataAsArray
    {
        $contentArr = [];

        // split all parts to words
        foreach ($input->toArray() as $key => $value) {
            $contentArr[$key] = $this->lexer->split2Words($value);
        }

        $indexingDataDto = IndexingDataAsArray::fromArray($contentArr);

        // For title, keywords, and description we don't want duplicates
        $indexingDataDto->title = array_unique($indexingDataDto->title);
        $indexingDataDto->keywords = array_unique($indexingDataDto->keywords);
        $indexingDataDto->description = array_unique($indexingDataDto->description);

        return $indexingDataDto;
    }

    /**
     * Extracts the sample description text from the content array.
     */
    public function bodyDescription(IndexingDataAsString $indexingDataDto): string
    {
        $bodyDescription = '';
        // Setting description
        $maxL = MathUtility::forceIntegerInRange($this->conf['index_descrLgd'], 0, 255, 200);
        if ($maxL) {
            $bodyDescription = preg_replace('/\s+/u', ' ', $indexingDataDto->body);
            // Shorten the string. If the database has the wrong character set,
            // the string is probably truncated again.
            $bodyDescription = \mb_strcut($bodyDescription, 0, $maxL, 'utf-8');
        }
        return $bodyDescription;
    }

    /**
     * Analyzes content to use for indexing,
     *
     * @return array Index Array (whatever that is...)
     */
    public function indexAnalyze(IndexingDataAsArray $indexingDataDto): array
    {
        $indexArr = [];
        $this->analyzeHeaderinfo($indexArr, $indexingDataDto->title, 7);
        $this->analyzeHeaderinfo($indexArr, $indexingDataDto->keywords, 6);
        $this->analyzeHeaderinfo($indexArr, $indexingDataDto->description, 5);
        $this->analyzeBody($indexArr, $indexingDataDto);
        return $indexArr;
    }

    /**
     * Calculates relevant information for headercontent
     *
     * @param array $retArr Index array, passed by reference
     * @param array $content Standard content array
     * @param int $offset Bit-wise priority to type
     */
    public function analyzeHeaderinfo(array &$retArr, array $content, int $offset): void
    {
        foreach ($content as $val) {
            $val = mb_substr($val, 0, 60);
            // Cut after 60 chars because the index_words.baseword varchar field has this length. This MUST be the same.
            if (!isset($retArr[$val])) {
                // Word ID (wid)
                $retArr[$val]['hash'] = md5($val);
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
     * Calculates relevant information for body content
     *
     * @param array $retArr Index array, passed by reference
     */
    public function analyzeBody(array &$retArr, IndexingDataAsArray $indexingDataDto): void
    {
        foreach ($indexingDataDto->body as $key => $val) {
            $val = substr($val, 0, 60);
            // Cut after 60 chars because the index_words.baseword varchar field has this length. This MUST be the same.
            if (!isset($retArr[$val])) {
                // First occurrence (used for ranking results)
                $retArr[$val]['first'] = $key;
                // Word ID (wid)
                $retArr[$val]['hash'] = md5($val);
            }
            if (!($retArr[$val]['count'] ?? false)) {
                $retArr[$val]['count'] = 0;
            }

            // Increase number of occurrences
            $retArr[$val]['count']++;
            $this->wordcount++;
        }
    }

    /********************************
     *
     * SQL; TYPO3 Pages
     *
     *******************************/
    /**
     * Updates db with information about the page (TYPO3 page, not external media)
     */
    public function submitPage(): void
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
            'item_title' => $this->indexingDataStringDto->title,
            'item_description' => $this->bodyDescription($this->indexingDataStringDto),
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
        $connection = $this->connectionPool->getConnectionForTable('index_phash');
        $connection->insert(
            'index_phash',
            $fields
        );
        // PROCESSING index_section
        $this->submit_section($this->hash['phash'], $this->hash['phash']);
        // PROCESSING index_grlist
        $this->submit_grlist($this->hash['phash'], $this->hash['phash']);
        // PROCESSING index_fulltext
        $fields = [
            'phash' => $this->hash['phash'],
            'fulltextdata' => implode(' ', $this->indexingDataStringDto->toArray()),
        ];
        if ($this->indexerConfig['fullTextDataLength'] > 0) {
            $fields['fulltextdata'] = substr($fields['fulltextdata'], 0, $this->indexerConfig['fullTextDataLength']);
        }
        $connection = $this->connectionPool->getConnectionForTable('index_fulltext');
        $connection->insert('index_fulltext', $fields);
    }

    /**
     * Stores gr_list in the database.
     *
     * @param string $hash Search result record phash
     * @param string $phash_x Actual phash of current content
     */
    public function submit_grlist(string $hash, string $phash_x): void
    {
        // Setting the gr_list record
        $fields = [
            'phash' => $hash,
            'phash_x' => $phash_x,
            'hash_gr_list' => md5($this->conf['gr_list']),
            'gr_list' => $this->conf['gr_list'],
        ];
        $connection = $this->connectionPool->getConnectionForTable('index_grlist');
        $connection->insert('index_grlist', $fields);
    }

    /**
     * Stores section
     * $hash and $hash_t3 are the same for TYPO3 pages, but different when it is external files.
     *
     * @param string $hash phash of TYPO3 parent search result record
     * @param string $hash_t3 phash of the file indexation search record
     */
    public function submit_section(string $hash, string $hash_t3): void
    {
        $fields = [
            'phash' => $hash,
            'phash_t3' => $hash_t3,
            'page_id' => (int)$this->conf['id'],
        ];
        $this->getRootLineFields($fields);
        $connection = $this->connectionPool->getConnectionForTable('index_section');
        $connection->insert('index_section', $fields);
    }

    /**
     * Removes records for the indexed page, $phash
     *
     * @param string $phash phash value to flush
     */
    public function removeOldIndexedPages(string $phash): void
    {
        // Removing old registrations for all tables. Because the pages are TYPO3 pages
        // there can be nothing else than 1-1 relations here.
        $tableArray = ['index_phash', 'index_section', 'index_grlist', 'index_fulltext'];
        foreach ($tableArray as $table) {
            $this->connectionPool->getConnectionForTable($table)->delete($table, ['phash' => $phash]);
        }

        // Removing all index_section records with hash_t3 set to this hash (this includes such
        // records set for external media on the page as well!). The re-insert of these records
        // are done in indexRegularDocument($file).
        $this->connectionPool->getConnectionForTable('index_section')->delete('index_section', ['phash_t3' => $phash]);
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
     * @param string $content_md5h Content HASH value.
     */
    public function submitFilePage(array $hash, string $file, array $subinfo, string $ext, int $mtime, int $ctime, int $size, string $content_md5h, IndexingDataAsString $indexingDataDto): void
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
            'item_title' => trim($indexingDataDto->title) ?: PathUtility::basename($file),
            'item_description' => $this->bodyDescription($indexingDataDto),
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
        $connection = $this->connectionPool->getConnectionForTable('index_phash');
        $connection->insert(
            'index_phash',
            $fields
        );
        // PROCESSING index_fulltext
        $fields = [
            'phash' => $hash['phash'],
            'fulltextdata' => implode(' ', $indexingDataDto->toArray()),
        ];
        if ($this->indexerConfig['fullTextDataLength'] > 0) {
            $fields['fulltextdata'] = substr($fields['fulltextdata'], 0, $this->indexerConfig['fullTextDataLength']);
        }
        $connection = $this->connectionPool->getConnectionForTable('index_fulltext');
        $connection->insert('index_fulltext', $fields);
    }

    /**
     * Stores file section for a file IF it does not exist
     *
     * @param string $hash phash value of file
     */
    public function submitFile_section(string $hash): void
    {
        // Testing if there is already a section
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('index_section');
        $count = (int)$queryBuilder->count('phash')
            ->from('index_section')
            ->where(
                $queryBuilder->expr()->eq(
                    'phash',
                    $queryBuilder->createNamedParameter($hash)
                ),
                $queryBuilder->expr()->eq(
                    'page_id',
                    $queryBuilder->createNamedParameter($this->conf['id'], Connection::PARAM_INT)
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
     * @param string $phash phash value to flush
     */
    public function removeOldIndexedFiles(string $phash): void
    {
        // Removing old registrations for tables.
        $tableArray = ['index_phash', 'index_grlist', 'index_fulltext'];
        foreach ($tableArray as $table) {
            $this->connectionPool->getConnectionForTable($table)->delete($table, ['phash' => $phash]);
        }
    }

    /**
     * Check the mtime / tstamp of the currently indexed page/file (based on phash)
     *
     * @param int $mtime mtime value to test against limits and indexed page (usually this is the mtime of the cached document)
     * @param string $phash "phash" used to select any already indexed page to see what its mtime is.
     */
    public function getIndexStatus(int $mtime, string $phash): IndexStatus
    {
        $row = $this->connectionPool->getConnectionForTable('index_phash')
            ->select(
                ['item_mtime', 'tstamp'],
                'index_phash',
                ['phash' => $phash],
                [],
                [],
                1
            )
            ->fetchAssociative();

        if (empty($row)) {
            // Page has never been indexed (is not represented in the index_phash table).
            return IndexStatus::NEW_DOCUMENT;
        }

        if (!$this->tstamp_minAge || $GLOBALS['EXEC_TIME'] > $row['tstamp'] + $this->tstamp_minAge) {
            // if minAge is not set or if minAge is exceeded, consider at mtime
            if ($mtime) {
                // It mtime is set, then it's tested. If not, the page must clearly be indexed.
                if ((int)$row['item_mtime'] !== $mtime) {
                    // And if mtime is different from the index_phash mtime, it's about time to re-index.
                    // The minimum age has exceeded and mtime was set and the mtime was different, so the page was indexed.
                    return IndexStatus::MODIFICATION_TIME_DIFFERS;
                }

                // mtime matched the document, so no changes detected and no content updated
                $this->updateTstamp($phash);
                $this->log_setTSlogMessage('mtime matched, timestamp updated.', LogLevel::NOTICE);
                return IndexStatus::MTIME_MATCHED;
            }

            // The minimum age has exceeded, but mtime was not set, so the page was indexed.
            return IndexStatus::MODIFICATION_TIME_NOT_SET;
        }

        // The minimum age was not exceeded
        return IndexStatus::MINIMUM_AGE_NOT_EXCEEDED;
    }

    /**
     * Check content hash in phash table
     *
     * @return array|true Returns TRUE if the page needs to be indexed (that is, there was no result), otherwise the phash value (in an array) of the phash record to which the grlist_record should be related!
     */
    public function checkContentHash(): array|true
    {
        // With this query the page will only be indexed if it's content is different from the same "phash_grouping" -page.
        $row = $this->connectionPool->getConnectionForTable('index_phash')
            ->select(
                ['phash'],
                'index_phash',
                [
                    'phash_grouping' => $this->hash['phash_grouping'],
                    'contentHash' => $this->content_md5h,
                ],
                [],
                [],
                1
            )
            ->fetchAssociative();

        return $row ?: true;
    }

    /**
     * Check content hash for external documents
     * Returns TRUE if the document needs to be indexed (that is, there was no result)
     *
     * @param string $hashGr phash value to check (phash_grouping)
     * @param string $content_md5h Content hash to check
     */
    public function checkExternalDocContentHash(string $hashGr, string $content_md5h): bool
    {
        $count = $this->connectionPool->getConnectionForTable('index_phash')
            ->count(
                '*',
                'index_phash',
                [
                    'phash_grouping' => $hashGr,
                    'contentHash' => $content_md5h,
                ]
            );
        return $count === 0;
    }

    /**
     * Checks if a grlist record has been set for the phash value input (looking at the "real" phash of the current content, not the linked-to phash of the common search result page)
     */
    public function is_grlist_set(string $phash_x): bool
    {
        $count = $this->connectionPool->getConnectionForTable('index_grlist')
            ->count(
                'phash_x',
                'index_grlist',
                ['phash_x' => $phash_x]
            );
        return $count > 0;
    }

    /**
     * Check if a grlist-entry for this hash exists and if not so, write one.
     *
     * @param string $phash phash of the search result that should be found
     * @param string $phash_x The real phash of the current content. The two values are different when a page with userlogin turns out to contain the exact same content as another already indexed version of the page; This is the whole reason for the grlist table in fact...
     */
    public function update_grlist(string $phash, string $phash_x): void
    {
        $count = $this->connectionPool->getConnectionForTable('index_grlist')
            ->count(
                'phash',
                'index_grlist',
                [
                    'phash' => $phash,
                    'hash_gr_list' => md5($this->conf['gr_list']),
                ]
            );

        if ($count === 0) {
            $this->submit_grlist($phash, $phash_x);
            $this->log_setTSlogMessage('Inserted gr_list \'' . $this->conf['gr_list'] . '\' for phash \'' . $phash . '\'', LogLevel::NOTICE);
        }
    }

    /**
     * Update tstamp for a phash row.
     */
    public function updateTstamp(string $phash, int $mtime = 0): void
    {
        $updateFields = [
            'tstamp' => $GLOBALS['EXEC_TIME'],
        ];

        if ($mtime) {
            $updateFields['item_mtime'] = $mtime;
        }

        $this->connectionPool->getConnectionForTable('index_phash')
            ->update(
                'index_phash',
                $updateFields,
                [
                    'phash' => $phash,
                ]
            );
    }

    /**
     * Update SetID of the index_phash record.
     */
    public function updateSetId(string $phash): void
    {
        $this->connectionPool->getConnectionForTable('index_phash')
            ->update(
                'index_phash',
                [
                    'freeIndexSetId' => (int)$this->conf['freeIndexSetId'],
                ],
                [
                    'phash' => $phash,
                ]
            );
    }

    /**
     * Update parse time for phash row.
     */
    public function updateParsetime(string $phash, int $parsetime): void
    {
        $this->connectionPool->getConnectionForTable('index_phash')
            ->update(
                'index_phash',
                [
                    'parsetime' => $parsetime,
                ],
                [
                    'phash' => $phash,
                ]
            );
    }

    /**
     * Update section rootline for the page
     */
    public function updateRootline(): void
    {
        $updateFields = [];
        $this->getRootLineFields($updateFields);

        $this->connectionPool->getConnectionForTable('index_section')
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
    public function getRootLineFields(array &$fieldArray): void
    {
        $fieldArray['rl0'] = (int)($this->conf['rootline_uids'][0] ?? 0);
        $fieldArray['rl1'] = (int)($this->conf['rootline_uids'][1] ?? 0);
        $fieldArray['rl2'] = (int)($this->conf['rootline_uids'][2] ?? 0);
    }

    /********************************
     *
     * SQL; Submitting words
     *
     *******************************/
    /**
     * Adds new words to db
     *
     * @param array $wordListArray Word List array (where each word has information about position, etc.).
     */
    public function checkWordList(array $wordListArray): void
    {
        if ($wordListArray === [] || IndexedSearchUtility::isMysqlFullTextEnabled()) {
            return;
        }

        $wordListArrayCount = count($wordListArray);
        $phashArray = array_column($wordListArray, 'hash');

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('index_words');
        $count = (int)$queryBuilder->count('baseword')
            ->from('index_words')
            ->where(
                $queryBuilder->expr()->in(
                    'wid',
                    $queryBuilder->quoteArrayBasedValueListToStringList($phashArray)
                )
            )
            ->executeQuery()
            ->fetchOne();

        if ($count !== $wordListArrayCount) {
            $connection = $this->connectionPool->getConnectionForTable('index_words');
            $queryBuilder = $connection->createQueryBuilder();

            $result = $queryBuilder->select('wid')
                ->from('index_words')
                ->where(
                    $queryBuilder->expr()->in(
                        'wid',
                        $queryBuilder->quoteArrayBasedValueListToStringList($phashArray)
                    )
                )
                ->executeQuery();

            $this->log_setTSlogMessage('Inserting words: ' . ($wordListArrayCount - $count), LogLevel::NOTICE);
            while ($row = $result->fetchAssociative()) {
                foreach ($wordListArray as $baseword => $wordData) {
                    if ($wordData['hash'] === $row['wid']) {
                        unset($wordListArray[$baseword]);
                    }
                }
            }

            foreach ($wordListArray as $key => $val) {
                // A duplicate-key error will occur here if a word is NOT unset in the unset() line. However as
                // long as the words in $wl are NO longer as 60 chars (the baseword varchar is 60 characters...)
                // this is not a problem.
                $connection->insert(
                    'index_words',
                    [
                        'wid' => $val['hash'],
                        'baseword' => $key,
                    ]
                );
            }
        }
    }

    /**
     * Submits RELATIONS between words and phash
     */
    public function submitWords(array $wordList, string $phash): void
    {
        if (IndexedSearchUtility::isMysqlFullTextEnabled()) {
            return;
        }
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('index_words');
        $result = $queryBuilder->select('wid')
            ->from('index_words')
            ->where(
                $queryBuilder->expr()->neq('is_stopword', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            )
            ->groupBy('wid')
            ->executeQuery();

        $stopWords = [];
        while ($row = $result->fetchAssociative()) {
            $stopWords[$row['wid']] = $row;
        }

        $this->connectionPool->getConnectionForTable('index_rel')->delete('index_rel', ['phash' => $phash]);

        $fields = ['phash', 'wid', 'count', 'first', 'freq', 'flags'];
        $rows = [];
        foreach ($wordList as $val) {
            if (isset($stopWords[$val['hash']])) {
                continue;
            }
            $rows[] = [
                $phash,
                $val['hash'],
                (int)$val['count'],
                (int)($val['first'] ?? 0),
                $this->freqMap($val['count'] / $this->wordcount),
                ($val['cmp'] ?? 0) & $this->flagBitMask,
            ];
        }

        if (!empty($rows)) {
            $this->connectionPool->getConnectionForTable('index_rel')->bulkInsert('index_rel', $rows, $fields);
        }
    }

    /**
     * maps frequency from a real number in [0;1] to an integer in [0;$this->freqRange] with anything above $this->freqMax as 1
     * and back.
     *
     * @param float $freq Frequency
     * @return int Frequency in range.
     */
    public function freqMap(float $freq): int
    {
        $mapFactor = $this->freqMax * 100 * $this->freqRange;
        if ($freq <= 1) {
            $newFreq = $freq * $mapFactor;
            $newFreq = min($newFreq, $this->freqRange);
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
    public function setT3Hashes(): void
    {
        //  Set main array:
        $hArray = [
            'id' => (int)$this->conf['id'],
            'type' => (int)$this->conf['type'],
            'sys_lang' => (int)$this->conf['sys_language_uid'],
            'MP' => (string)$this->conf['MP'],
            'staticPageArguments' => is_array($this->conf['staticPageArguments']) ? json_encode($this->conf['staticPageArguments']) : null,
        ];
        // Set grouping hash (Identifies a "page" combined of id, type, language, mount point and cHash parameters):
        $this->hash['phash_grouping'] = md5(serialize($hArray));
        // Add gr_list and set plain phash (Subdivision where special page composition based on login is taken into account as well. It is expected that such pages are normally similar regardless of the login.)
        $hArray['gr_list'] = (string)$this->conf['gr_list'];
        $this->hash['phash'] = md5(serialize($hArray));
    }

    /**
     * Get search hash, external files
     *
     * @param string $file File name / path which identifies it on the server
     * @param array $subinfo Additional content identifying the (subpart of) content. For instance; PDF files are divided into groups of pages for indexing.
     * @return array{phash_grouping: string, phash: string}
     */
    public function setExtHashes(string $file, array $subinfo = []): array
    {
        //  Set main array:
        $hash = [];
        $hArray = [
            'file' => $file,
        ];
        // Set grouping hash:
        $hash['phash_grouping'] = md5(serialize($hArray));
        // Add subinfo
        $hArray['subinfo'] = $subinfo;
        $hash['phash'] = md5(serialize($hArray));
        return $hash;
    }

    public function log_setTSlogMessage(string $msg, string $logLevel = LogLevel::INFO): void
    {
        $this->timeTracker->setTSlogMessage($msg, $logLevel);
    }

    /**
     * Makes sure that keywords are space-separated. This is important for their
     * proper displaying as a part of fulltext index.
     *
     * @param string $keywordList
     * @return string
     * @see https://forge.typo3.org/issues/14959
     */
    protected function addSpacesToKeywordList(string $keywordList): string
    {
        $keywords = GeneralUtility::trimExplode(',', $keywordList);
        return ' ' . implode(', ', $keywords) . ' ';
    }

    /**
     * Make sure that the word list only contains words with unique phash values.
     * All words with phash collisions are filtered from the list.
     *
     * @param array $wordList the input word list
     * @return array the filtered word list
     */
    private function removePhashCollisions(array $wordList): array
    {
        $uniquePhashes = [];
        foreach ($wordList as $baseword => $wordData) {
            if (in_array($wordData['hash'], $uniquePhashes, true)) {
                unset($wordList[$baseword]);
                continue;
            }
            $uniquePhashes[] = $wordData['hash'];
        }
        return $wordList;
    }

    /**
     * Gets the unixtime as milliseconds.
     */
    protected function milliseconds(): int
    {
        return (int)round(microtime(true) * 1000);
    }
}
