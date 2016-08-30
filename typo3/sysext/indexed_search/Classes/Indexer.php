<?php
namespace TYPO3\CMS\IndexedSearch;

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
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\IndexedSearch\Utility\IndexedSearchUtility;

/**
 * Indexing class for TYPO3 frontend
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
        4 => 'Page has never been indexed (is not represented in the index_phash table).'
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
     * If TRUE, indexing is forced despite of hashes etc.
     *
     * @var bool
     */
    public $crawlerActive = false;

    /**
     * Set when crawler is detected (internal)
     *
     * @var array
     */
    public $defaultContentArray = [
        'title' => '',
        'description' => '',
        'keywords' => '',
        'body' => ''
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
     * Indexer configuration, coming from $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['indexed_search']
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
     * @var string
     */
    public $content_md5h = '';

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
     * @var array
     */
    public $cHashParams = [];

    /**
     * cHashparams array
     *
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
     * Charset class object
     *
     * @var \TYPO3\CMS\Core\Charset\CharsetConverter
     */
    public $csObj;

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
     * @var bool
     */
    public $flagBitMask;

    /**
     * Parent Object (TSFE) Initialization
     *
     * @param TypoScriptFrontendController $pObj Parent Object, passed by reference
     * @return void
     */
    public function hook_indexContent(&$pObj)
    {
        // Indexer configuration from Extension Manager interface:
        $indexerConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['indexed_search']);
        // Crawler activation:
        // Requirements are that the crawler is loaded, a crawler session is running and re-indexing requested as processing instruction:
        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('crawler') && $pObj->applicationData['tx_crawler']['running'] && in_array('tx_indexedsearch_reindex', $pObj->applicationData['tx_crawler']['parameters']['procInstructions'])) {
            // Setting simple log message:
            $pObj->applicationData['tx_crawler']['log'][] = 'Forced Re-indexing enabled';
            // Setting variables:
            $this->crawlerActive = true;
            // Crawler active flag
            $this->forceIndexing = true;
        }
        // Determine if page should be indexed, and if so, configure and initialize indexer
        if ($pObj->config['config']['index_enable']) {
            $this->log_push('Index page', '');
            if (!$indexerConfig['disableFrontendIndexing'] || $this->crawlerActive) {
                if (!$pObj->page['no_search']) {
                    if (!$pObj->no_cache) {
                        if ((int)$pObj->sys_language_uid === (int)$pObj->sys_language_content) {
                            // Setting up internal configuration from config array:
                            $this->conf = [];
                            // Information about page for which the indexing takes place
                            $this->conf['id'] = $pObj->id;
                            // Page id
                            $this->conf['type'] = $pObj->type;
                            // Page type
                            $this->conf['sys_language_uid'] = $pObj->sys_language_uid;
                            // sys_language UID of the language of the indexing.
                            $this->conf['MP'] = $pObj->MP;
                            // MP variable, if any (Mount Points)
                            $this->conf['gr_list'] = $pObj->gr_list;
                            // Group list
                            $this->conf['cHash'] = $pObj->cHash;
                            // cHash string for additional parameters
                            $this->conf['cHash_array'] = $pObj->cHash_array;
                            // Array of the additional parameters
                            $this->conf['crdate'] = $pObj->page['crdate'];
                            // The creation date of the TYPO3 page
                            $this->conf['page_cache_reg1'] = $pObj->page_cache_reg1;
                            // reg1 of the caching table. Not known what practical use this has.
                            // Root line uids
                            $this->conf['rootline_uids'] = [];
                            foreach ($pObj->config['rootLine'] as $rlkey => $rldat) {
                                $this->conf['rootline_uids'][$rlkey] = $rldat['uid'];
                            }
                            // Content of page:
                            $this->conf['content'] = $pObj->content;
                            // Content string (HTML of TYPO3 page)
                            $this->conf['indexedDocTitle'] = $pObj->convOutputCharset($pObj->indexedDocTitle);
                            // Alternative title for indexing
                            $this->conf['metaCharset'] = $pObj->metaCharset;
                            // Character set of content (will be converted to utf-8 during indexing)
                            $this->conf['mtime'] = isset($pObj->register['SYS_LASTCHANGED']) ? $pObj->register['SYS_LASTCHANGED'] : $pObj->page['SYS_LASTCHANGED'];
                            // Most recent modification time (seconds) of the content on the page. Used to evaluate whether it should be re-indexed.
                            // Configuration of behavior:
                            $this->conf['index_externals'] = $pObj->config['config']['index_externals'];
                            // Whether to index external documents like PDF, DOC etc. (if possible)
                            $this->conf['index_descrLgd'] = $pObj->config['config']['index_descrLgd'];
                            // Length of description text (max 250, default 200)
                            $this->conf['index_metatags'] = isset($pObj->config['config']['index_metatags']) ? $pObj->config['config']['index_metatags'] : true;
                            // Set to zero:
                            $this->conf['recordUid'] = 0;
                            $this->conf['freeIndexUid'] = 0;
                            $this->conf['freeIndexSetId'] = 0;
                            // Init and start indexing:
                            $this->init();
                            $this->indexTypo3PageContent();
                        } else {
                            $this->log_setTSlogMessage('Index page? No, ->sys_language_uid was different from sys_language_content which indicates that the page contains fall-back content and that would be falsely indexed as localized content.');
                        }
                    } else {
                        $this->log_setTSlogMessage('Index page? No, page was set to "no_cache" and so cannot be indexed.');
                    }
                } else {
                    $this->log_setTSlogMessage('Index page? No, The "No Search" flag has been set in the page properties!');
                }
            } else {
                $this->log_setTSlogMessage('Index page? No, Ordinary Frontend indexing during rendering is disabled.');
            }
            $this->log_pull();
        }
    }

    /****************************
     *
     * Backend API
     *
     ****************************/
    /**
     * Initializing the "combined ID" of the page (phash) being indexed (or for which external media is attached)
     *
     * @param int $id The page uid, &id=
     * @param int $type The page type, &type=
     * @param int $sys_language_uid sys_language uid, typically &L=
     * @param string $MP The MP variable (Mount Points), &MP=
     * @param array $uidRL Rootline array of only UIDs.
     * @param array $cHash_array Array of GET variables to register with this indexing
     * @param bool $createCHash If set, calculates a cHash value from the $cHash_array. Probably you will not do that since such cases are indexed through the frontend and the idea of this interface is to index non-cacheable pages from the backend!
     * @return void
     */
    public function backend_initIndexer($id, $type, $sys_language_uid, $MP, $uidRL, $cHash_array = [], $createCHash = false)
    {
        // Setting up internal configuration from config array:
        $this->conf = [];
        // Information about page for which the indexing takes place
        $this->conf['id'] = $id;
        // Page id	(int)
        $this->conf['type'] = $type;
        // Page type (int)
        $this->conf['sys_language_uid'] = $sys_language_uid;
        // sys_language UID of the language of the indexing (int)
        $this->conf['MP'] = $MP;
        // MP variable, if any (Mount Points) (string)
        $this->conf['gr_list'] = '0,-1';
        // Group list (hardcoded for now...)
        // cHash values:
        if ($createCHash) {
            /* @var $cacheHash \TYPO3\CMS\Frontend\Page\CacheHashCalculator */
            $cacheHash = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Page\CacheHashCalculator::class);
            $this->conf['cHash'] = $cacheHash->generateForParameters(GeneralUtility::implodeArrayForUrl('', $cHash_array));
        } else {
            $this->conf['cHash'] = '';
        }
        // cHash string for additional parameters
        $this->conf['cHash_array'] = $cHash_array;
        // Array of the additional parameters
        // Set to defaults
        $this->conf['freeIndexUid'] = 0;
        $this->conf['freeIndexSetId'] = 0;
        $this->conf['page_cache_reg1'] = '';
        // Root line uids
        $this->conf['rootline_uids'] = $uidRL;
        // Configuration of behavior:
        $this->conf['index_externals'] = 1;
        // Whether to index external documents like PDF, DOC etc. (if possible)
        $this->conf['index_descrLgd'] = 200;
        // Length of description text (max 250, default 200)
        $this->conf['index_metatags'] = true;
        // Whether to index document keywords and description (if present)
        // Init and start indexing:
        $this->init();
    }

    /**
     * Sets the free-index uid. Can be called right after backend_initIndexer()
     *
     * @param int $freeIndexUid Free index UID
     * @param int $freeIndexSetId Set id - an integer identifying the "set" of indexing operations.
     * @return void
     */
    public function backend_setFreeIndexUid($freeIndexUid, $freeIndexSetId = 0)
    {
        $this->conf['freeIndexUid'] = $freeIndexUid;
        $this->conf['freeIndexSetId'] = $freeIndexSetId;
    }

    /**
     * Indexing records as the content of a TYPO3 page.
     *
     * @param string $title Title equivalent
     * @param string $keywords Keywords equivalent
     * @param string $description Description equivalent
     * @param string $content The main content to index
     * @param string $charset The charset of the title, keyword, description and body-content. MUST BE VALID, otherwise nothing is indexed!
     * @param int $mtime Last modification time, in seconds
     * @param int $crdate The creation date of the content, in seconds
     * @param int $recordUid The record UID that the content comes from (for registration with the indexed rows)
     * @return void
     */
    public function backend_indexAsTYPO3Page($title, $keywords, $description, $content, $charset, $mtime, $crdate = 0, $recordUid = 0)
    {
        // Content of page:
        $this->conf['mtime'] = $mtime;
        // Most recent modification time (seconds) of the content
        $this->conf['crdate'] = $crdate;
        // The creation date of the TYPO3 content
        $this->conf['recordUid'] = $recordUid;
        // UID of the record, if applicable
        // Construct fake HTML for parsing:
        $this->conf['content'] = '
		<html>
			<head>
				<title>' . htmlspecialchars($title) . '</title>
				<meta name="keywords" content="' . htmlspecialchars($keywords) . '" />
				<meta name="description" content="' . htmlspecialchars($description) . '" />
			</head>
			<body>
				' . htmlspecialchars($content) . '
			</body>
		</html>';
        // Content string (HTML of TYPO3 page)
        // Initializing charset:
        $this->conf['metaCharset'] = $charset;
        // Character set of content (will be converted to utf-8 during indexing)
        $this->conf['indexedDocTitle'] = '';
        // Alternative title for indexing
        // Index content as if it was a TYPO3 page:
        $this->indexTypo3PageContent();
    }

    /********************************
     *
     * Initialization
     *
     *******************************/
    /**
     * Initializes the object. $this->conf MUST be set with proper values prior to this call!!!
     *
     * @return void
     */
    public function init()
    {
        // Initializing:
        $this->cHashParams = $this->conf['cHash_array'];
        if (is_array($this->cHashParams) && !empty($this->cHashParams)) {
            if ($this->conf['cHash']) {
                // Add this so that URL's come out right...
                $this->cHashParams['cHash'] = $this->conf['cHash'];
            }
            unset($this->cHashParams['encryptionKey']);
        }
        // Setting phash / phash_grouping which identifies the indexed page based on some of these variables:
        $this->setT3Hashes();
        // Indexer configuration from Extension Manager interface:
        $this->indexerConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['indexed_search']);
        $this->tstamp_minAge = MathUtility::forceIntegerInRange($this->indexerConfig['minAge'] * 3600, 0);
        $this->tstamp_maxAge = MathUtility::forceIntegerInRange($this->indexerConfig['maxAge'] * 3600, 0);
        $this->maxExternalFiles = MathUtility::forceIntegerInRange($this->indexerConfig['maxExternalFiles'], 0, 1000, 5);
        $this->flagBitMask = MathUtility::forceIntegerInRange($this->indexerConfig['flagBitMask'], 0, 255);
        // Workaround: If the extension configuration was not updated yet, the value is not existing
        $this->enableMetaphoneSearch = !isset($this->indexerConfig['enableMetaphoneSearch']) || $this->indexerConfig['enableMetaphoneSearch'];
        $this->storeMetaphoneInfoAsWords = !IndexedSearchUtility::isTableUsed('index_words') && $this->enableMetaphoneSearch;
        // Initialize external document parsers:
        // Example configuration, see ext_localconf.php of this file!
        if ($this->conf['index_externals']) {
            $this->initializeExternalParsers();
        }
        // Initialize lexer (class that deconstructs the text into words):
        $lexerObjRef = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['lexer'] ? $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['lexer'] : 'TYPO3\\CMS\\IndexedSearch\\Lexer';
        $this->lexerObj = GeneralUtility::getUserObj($lexerObjRef);
        $this->lexerObj->debug = $this->indexerConfig['debugMode'];
        // Initialize metaphone hook:
        // Make sure that the hook is loaded _after_ indexed_search as this may overwrite the hook depending on the configuration.
        if ($this->enableMetaphoneSearch && $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['metaphone']) {
            $this->metaphoneObj = GeneralUtility::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['metaphone']);
            $this->metaphoneObj->pObj = $this;
        }
        // Init charset class:
        $this->csObj = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Charset\CharsetConverter::class);
    }

    /**
     * Initialize external parsers
     *
     * @return void
     * @access private
     * @see init()
     */
    public function initializeExternalParsers()
    {
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['external_parsers'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['external_parsers'] as $extension => $_objRef) {
                $this->external_parsers[$extension] = GeneralUtility::getUserObj($_objRef);
                $this->external_parsers[$extension]->pObj = $this;
                // Init parser and if it returns FALSE, unset its entry again:
                if (!$this->external_parsers[$extension]->initParser($extension)) {
                    unset($this->external_parsers[$extension]);
                }
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
     *
     * @return void
     */
    public function indexTypo3PageContent()
    {
        $check = $this->checkMtimeTstamp($this->conf['mtime'], $this->hash['phash']);
        $is_grlist = $this->is_grlist_set($this->hash['phash']);
        if ($check > 0 || !$is_grlist || $this->forceIndexing) {
            // Setting message:
            if ($this->forceIndexing) {
                $this->log_setTSlogMessage('Indexing needed, reason: Forced', 1);
            } elseif ($check > 0) {
                $this->log_setTSlogMessage('Indexing needed, reason: ' . $this->reasons[$check], 1);
            } else {
                $this->log_setTSlogMessage('Indexing needed, reason: Updates gr_list!', 1);
            }
            // Divide into title,keywords,description and body:
            $this->log_push('Split content', '');
            $this->contentParts = $this->splitHTMLContent($this->conf['content']);
            if ($this->conf['indexedDocTitle']) {
                $this->contentParts['title'] = $this->conf['indexedDocTitle'];
            }
            $this->log_pull();
            // Calculating a hash over what is to be the actual page content. Maybe this hash should not include title,description and keywords? The bodytext is the primary concern. (on the other hand a changed page-title would make no difference then, so dont!)
            $this->content_md5h = IndexedSearchUtility::md5inthash(implode('', $this->contentParts));
            // This function checks if there is already a page (with gr_list = 0,-1) indexed and if that page has the very same contentHash.
            // If the contentHash is the same, then we can rest assured that this page is already indexed and regardless of mtime and origContent we don't need to do anything more.
            // This will also prevent pages from being indexed if a fe_users has logged in and it turns out that the page content is not changed anyway. fe_users logged in should always search with hash_gr_list = "0,-1" OR "[their_group_list]". This situation will be prevented only if the page has been indexed with no user login on before hand. Else the page will be indexed by users until that event. However that does not present a serious problem.
            $checkCHash = $this->checkContentHash();
            if (!is_array($checkCHash) || $check === 1) {
                $Pstart = GeneralUtility::milliseconds();
                $this->log_push('Converting charset of content (' . $this->conf['metaCharset'] . ') to utf-8', '');
                $this->charsetEntity2utf8($this->contentParts, $this->conf['metaCharset']);
                $this->log_pull();
                // Splitting words
                $this->log_push('Extract words from content', '');
                $splitInWords = $this->processWordsInArrays($this->contentParts);
                $this->log_pull();
                // Analyse the indexed words.
                $this->log_push('Analyse the extracted words', '');
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
                $this->updateParsetime($this->hash['phash'], GeneralUtility::milliseconds() - $Pstart);
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
        $contentArr['body'] = stristr($content, '<body');
        $headPart = substr($content, 0, -strlen($contentArr['body']));
        // get title
        $this->embracingTags($headPart, 'TITLE', $contentArr['title'], $dummy2, $dummy);
        $titleParts = explode(':', $contentArr['title'], 2);
        $contentArr['title'] = trim(isset($titleParts[1]) ? $titleParts[1] : $titleParts[0]);
        // get keywords and description metatags
        if ($this->conf['index_metatags']) {
            $meta = [];
            $i = 0;
            while ($this->embracingTags($headPart, 'meta', $dummy, $headPart, $meta[$i])) {
                $i++;
            }
            // @todo The code below stops at first unset tag. Is that correct?
            for ($i = 0; isset($meta[$i]); $i++) {
                $meta[$i] = GeneralUtility::get_tag_attributes($meta[$i]);
                if (stristr($meta[$i]['name'], 'keywords')) {
                    $contentArr['keywords'] .= ',' . $this->addSpacesToKeywordList($meta[$i]['content']);
                }
                if (stristr($meta[$i]['name'], 'description')) {
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
        $charset = $this->csObj->parse_charset($charset);
        // Convert charset:
        if ($charset && $charset !== 'utf-8') {
            $content = $this->csObj->utf8_encode($content, $charset);
        }
        // Convert entities, assuming document is now UTF-8:
        return $this->csObj->entities_to_utf8($content, true);
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
        list($paramList, $isTagInText) = explode('>', substr($isTagInText, strlen($startTag)), 2);
        $afterTagInText = stristr($isTagInText, $endTag);
        if ($afterTagInText) {
            $stringBefore = substr($string, 0, strpos(strtolower($string), strtolower($startTag)));
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
        if (count($expBody) > 1) {
            $body = '';
            foreach ($expBody as $val) {
                $part = explode('-->', $val, 2);
                if (trim($part[0]) == 'begin') {
                    $body .= $part[1];
                    $prev = '';
                } elseif (trim($part[0]) == 'end') {
                    $body .= $prev;
                } else {
                    $prev = $val;
                }
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Extract links (hrefs) from HTML content and if indexable media is found, it is indexed.
     *
     * @param string $content HTML content
     * @return void
     */
    public function extractLinks($content)
    {
        // Get links:
        $list = $this->extractHyperLinks($content);
        if ($this->indexerConfig['useCrawlerForExternalFiles'] && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('crawler')) {
            $this->includeCrawlerClass();
            $crawler = GeneralUtility::makeInstance(\tx_crawler_lib::class);
        }
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
            if ($qParts['query'] && strstr($qParts['query'], 'jumpurl=')) {
                parse_str($qParts['query'], $getP);
                $linkSource = $getP['jumpurl'];
                $qParts = parse_url($linkSource);
            }
            if (!$linkInfo['localPath'] && $qParts['scheme']) {
                if ($this->indexerConfig['indexExternalURLs']) {
                    // Index external URL (http or otherwise)
                    $this->indexExternalUrl($linkSource);
                }
            } elseif (!$qParts['query']) {
                $linkSource = urldecode($linkSource);
                if (GeneralUtility::isAllowedAbsPath($linkSource)) {
                    $localFile = $linkSource;
                } else {
                    $localFile = GeneralUtility::getFileAbsFileName(PATH_site . $linkSource);
                }
                if ($localFile && @is_file($localFile)) {
                    // Index local file:
                    if ($linkInfo['localPath']) {
                        $fI = pathinfo($linkSource);
                        $ext = strtolower($fI['extension']);
                        if (is_object($crawler)) {
                            $params = [
                                'document' => $linkSource,
                                'alturl' => $linkInfo['href'],
                                'conf' => $this->conf
                            ];
                            unset($params['conf']['content']);
                            $crawler->addQueueEntry_callBack(0, $params, Hook\CrawlerFilesHook::class, $this->conf['id']);
                            $this->log_setTSlogMessage('media "' . $params['document'] . '" added to "crawler" queue.', 1);
                        } else {
                            $this->indexRegularDocument($linkInfo['href'], false, $linkSource, $ext);
                        }
                    } else {
                        if (is_object($crawler)) {
                            $params = [
                                'document' => $linkSource,
                                'conf' => $this->conf
                            ];
                            unset($params['conf']['content']);
                            $crawler->addQueueEntry_callBack(0, $params, Hook\CrawlerFilesHook::class, $this->conf['id']);
                            $this->log_setTSlogMessage('media "' . $params['document'] . '" added to "crawler" queue.', 1);
                        } else {
                            $this->indexRegularDocument($linkSource);
                        }
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
        $htmlParser = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Html\HtmlParser::class);
        $htmlParts = $htmlParser->splitTags('a', $html);
        $hyperLinksData = [];
        foreach ($htmlParts as $index => $tagData) {
            if ($index % 2 !== 0) {
                $tagAttributes = $htmlParser->get_tag_attributes($tagData, true);
                $firstTagName = $htmlParser->getFirstTagName($tagData);
                if (strtolower($firstTagName) === 'a') {
                    if ($tagAttributes[0]['href'] && $tagAttributes[0]['href'][0] != '#') {
                        $hyperLinksData[] = [
                            'tag' => $tagData,
                            'href' => $tagAttributes[0]['href'],
                            'localPath' => $this->createLocalPath($tagAttributes[0]['href'])
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
        $htmlParser = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Html\HtmlParser::class);
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
     * @param string $externalUrl URL, eg. "http://typo3.org/
     * @return void
     * @see indexRegularDocument()
     */
    public function indexExternalUrl($externalUrl)
    {
        // Parse External URL:
        $qParts = parse_url($externalUrl);
        $fI = pathinfo($qParts['path']);
        $ext = strtolower($fI['extension']);
        // Get headers:
        $urlHeaders = $this->getUrlHeaders($externalUrl);
        if (stristr($urlHeaders['Content-Type'], 'text/html')) {
            $content = ($this->indexExternalUrl_content = GeneralUtility::getUrl($externalUrl));
            if ((string)$content !== '') {
                // Create temporary file:
                $tmpFile = GeneralUtility::tempnam('EXTERNAL_URL');
                if ($tmpFile) {
                    GeneralUtility::writeFile($tmpFile, $content);
                    // Index that file:
                    $this->indexRegularDocument($externalUrl, true, $tmpFile, 'html');
                    // Using "TRUE" for second parameter to force indexing of external URLs (mtime doesn't make sense, does it?)
                    unlink($tmpFile);
                }
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
        // Try to get the headers only
        $content = GeneralUtility::getUrl($url, 2);
        if ((string)$content !== '') {
            // Compile headers:
            $headers = GeneralUtility::trimExplode(LF, $content, true);
            $retVal = [];
            foreach ($headers as $line) {
                if (trim($line) === '') {
                    break;
                }
                list($headKey, $headValue) = explode(':', $line, 2);
                $retVal[$headKey] = $headValue;
            }
            return $retVal;
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
        $localPath = '';
        static $pathFunctions = [
            'createLocalPathFromT3vars',
            'createLocalPathUsingAbsRefPrefix',
            'createLocalPathUsingDomainURL',
            'createLocalPathFromAbsoluteURL',
            'createLocalPathFromRelativeURL'
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
     * Attempts to create a local file path from T3VARs. This is useful for
     * various download extensions that hide actual file name but still want the
     * file to be indexed.
     *
     * @param string $sourcePath
     * @return string
     */
    protected function createLocalPathFromT3vars($sourcePath)
    {
        $localPath = '';
        $indexLocalFiles = $GLOBALS['T3_VAR']['ext']['indexed_search']['indexLocalFiles'];
        if (is_array($indexLocalFiles)) {
            $md5 = GeneralUtility::shortMD5($sourcePath);
            // Note: not using self::isAllowedLocalFile here because this method
            // is allowed to index files outside of the web site (for example,
            // protected downloads)
            if (isset($indexLocalFiles[$md5]) && is_file($indexLocalFiles[$md5])) {
                $localPath = $indexLocalFiles[$md5];
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
        $baseURL = GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
        $baseURLLength = strlen($baseURL);
        if (substr($sourcePath, 0, $baseURLLength) == $baseURL) {
            $sourcePath = substr($sourcePath, $baseURLLength);
            $localPath = PATH_site . $sourcePath;
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
        if ($GLOBALS['TSFE'] instanceof \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController) {
            $absRefPrefix = $GLOBALS['TSFE']->config['config']['absRefPrefix'];
            $absRefPrefixLength = strlen($absRefPrefix);
            if ($absRefPrefixLength > 0 && substr($sourcePath, 0, $absRefPrefixLength) == $absRefPrefix) {
                $sourcePath = substr($sourcePath, $absRefPrefixLength);
                $localPath = PATH_site . $sourcePath;
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
        if ($sourcePath[0] == '/') {
            $sourcePath = substr($sourcePath, 1);
            $localPath = PATH_site . $sourcePath;
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
            $localPath = PATH_site . $sourcePath;
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
        return $urlParts['scheme'] == '' && $urlParts['path'][0] != '/';
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
        $insideWebPath = substr($filePath, 0, strlen(PATH_site)) == PATH_site;
        $isFile = is_file($filePath);
        return $insideWebPath && $isFile;
    }

    /******************************************
     *
     * Indexing; external files (PDF, DOC, etc)
     *
     ******************************************/
    /**
     * Indexing a regular document given as $file (relative to PATH_site, local file)
     *
     * @param string $file Relative Filename, relative to PATH_site. It can also be an absolute path as long as it is inside the lockRootPath (validated with \TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath()). Finally, if $contentTmpFile is set, this value can be anything, most likely a URL
     * @param bool $force If set, indexing is forced (despite content hashes, mtime etc).
     * @param string $contentTmpFile Temporary file with the content to read it from (instead of $file). Used when the $file is a URL.
     * @param string $altExtension File extension for temporary file.
     * @return void
     */
    public function indexRegularDocument($file, $force = false, $contentTmpFile = '', $altExtension = '')
    {
        // Init
        $fI = pathinfo($file);
        $ext = $altExtension ?: strtolower($fI['extension']);
        // Create abs-path:
        if (!$contentTmpFile) {
            if (!GeneralUtility::isAbsPath($file)) {
                // Relative, prepend PATH_site:
                $absFile = GeneralUtility::getFileAbsFileName(PATH_site . $file);
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
            if ($this->external_parsers[$ext]) {
                $fileInfo = stat($absFile);
                $cParts = $this->fileContentParts($ext, $absFile);
                foreach ($cParts as $cPKey) {
                    $this->internal_log = [];
                    $this->log_push('Index: ' . str_replace('.', '_', basename($file)) . ($cPKey ? '#' . $cPKey : ''), '');
                    $Pstart = GeneralUtility::milliseconds();
                    $subinfo = ['key' => $cPKey];
                    // Setting page range. This is "0" (zero) when no division is made, otherwise a range like "1-3"
                    $phash_arr = ($this->file_phash_arr = $this->setExtHashes($file, $subinfo));
                    $check = $this->checkMtimeTstamp($fileInfo['mtime'], $phash_arr['phash']);
                    if ($check > 0 || $force) {
                        if ($check > 0) {
                            $this->log_setTSlogMessage('Indexing needed, reason: ' . $this->reasons[$check], 1);
                        } else {
                            $this->log_setTSlogMessage('Indexing forced by flag', 1);
                        }
                        // Check external file counter:
                        if ($this->externalFileCounter < $this->maxExternalFiles || $force) {
                            // Divide into title,keywords,description and body:
                            $this->log_push('Split content', '');
                            $contentParts = $this->readFileContent($ext, $absFile, $cPKey);
                            $this->log_pull();
                            if (is_array($contentParts)) {
                                // Calculating a hash over what is to be the actual content. (see indexTypo3PageContent())
                                $content_md5h = IndexedSearchUtility::md5inthash(implode($contentParts, ''));
                                if ($this->checkExternalDocContentHash($phash_arr['phash_grouping'], $content_md5h) || $force) {
                                    // Increment counter:
                                    $this->externalFileCounter++;
                                    // Splitting words
                                    $this->log_push('Extract words from content', '');
                                    $splitInWords = $this->processWordsInArrays($contentParts);
                                    $this->log_pull();
                                    // Analyse the indexed words.
                                    $this->log_push('Analyse the extracted words', '');
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
                                    $this->updateParsetime($phash_arr['phash'], GeneralUtility::milliseconds() - $Pstart);
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
     * @return void
     */
    public function charsetEntity2utf8(&$contentArr, $charset)
    {
        // Convert charset if necessary
        foreach ($contentArr as $key => $value) {
            if ((string)$contentArr[$key] !== '') {
                if ($charset !== 'utf-8') {
                    $contentArr[$key] = $this->csObj->utf8_encode($contentArr[$key], $charset);
                }
                // decode all numeric / html-entities in the string to real characters:
                $contentArr[$key] = $this->csObj->entities_to_utf8($contentArr[$key], true);
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
        // Setting description
        $maxL = MathUtility::forceIntegerInRange($this->conf['index_descrLgd'], 0, 255, 200);
        if ($maxL) {
            $bodyDescription = preg_replace('/\s+/u', ' ', $contentArr['body']);
            // Shorten the string:
            $bodyDescription = $this->csObj->strtrunc('utf-8', $bodyDescription, $maxL);
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
        $counter = 0;
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
     * @return void
     */
    public function analyzeHeaderinfo(&$retArr, $content, $key, $offset)
    {
        foreach ($content[$key] as $val) {
            $val = substr($val, 0, 60);
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
            $retArr[$val]['cmp'] = $retArr[$val]['cmp'] | pow(2, $offset);
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
     * @return void
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
     *
     * @return void
     */
    public function submitPage()
    {
        // Remove any current data for this phash:
        $this->removeOldIndexedPages($this->hash['phash']);
        // setting new phash_row
        $fields = [
            'phash' => $this->hash['phash'],
            'phash_grouping' => $this->hash['phash_grouping'],
            'cHashParams' => serialize($this->cHashParams),
            'contentHash' => $this->content_md5h,
            'data_page_id' => $this->conf['id'],
            'data_page_reg1' => $this->conf['page_cache_reg1'],
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
            'freeIndexSetId' => (int)$this->conf['freeIndexSetId']
        ];
        if (IndexedSearchUtility::isTableUsed('index_phash')) {
            $GLOBALS['TYPO3_DB']->exec_INSERTquery('index_phash', $fields);
        }
        // PROCESSING index_section
        $this->submit_section($this->hash['phash'], $this->hash['phash']);
        // PROCESSING index_grlist
        $this->submit_grlist($this->hash['phash'], $this->hash['phash']);
        // PROCESSING index_fulltext
        $fields = [
            'phash' => $this->hash['phash'],
            'fulltextdata' => implode(' ', $this->contentParts),
            'metaphonedata' => $this->metaphoneContent
        ];
        if ($this->indexerConfig['fullTextDataLength'] > 0) {
            $fields['fulltextdata'] = substr($fields['fulltextdata'], 0, $this->indexerConfig['fullTextDataLength']);
        }
        if (IndexedSearchUtility::isTableUsed('index_fulltext')) {
            $GLOBALS['TYPO3_DB']->exec_INSERTquery('index_fulltext', $fields);
        }
        // PROCESSING index_debug
        if ($this->indexerConfig['debugMode']) {
            $fields = [
                'phash' => $this->hash['phash'],
                'debuginfo' => serialize([
                    'cHashParams' => $this->cHashParams,
                    'external_parsers initialized' => array_keys($this->external_parsers),
                    'conf' => array_merge($this->conf, ['content' => substr($this->conf['content'], 0, 1000)]),
                    'contentParts' => array_merge($this->contentParts, ['body' => substr($this->contentParts['body'], 0, 1000)]),
                    'logs' => $this->internal_log,
                    'lexer' => $this->lexerObj->debugString
                ])
            ];
            if (IndexedSearchUtility::isTableUsed('index_debug')) {
                $GLOBALS['TYPO3_DB']->exec_INSERTquery('index_debug', $fields);
            }
        }
    }

    /**
     * Stores gr_list in the database.
     *
     * @param int $hash Search result record phash
     * @param int $phash_x Actual phash of current content
     * @return void
     * @see update_grlist()
     */
    public function submit_grlist($hash, $phash_x)
    {
        // Setting the gr_list record
        $fields = [
            'phash' => $hash,
            'phash_x' => $phash_x,
            'hash_gr_list' => IndexedSearchUtility::md5inthash($this->conf['gr_list']),
            'gr_list' => $this->conf['gr_list']
        ];
        if (IndexedSearchUtility::isTableUsed('index_grlist')) {
            $GLOBALS['TYPO3_DB']->exec_INSERTquery('index_grlist', $fields);
        }
    }

    /**
     * Stores section
     * $hash and $hash_t3 are the same for TYPO3 pages, but different when it is external files.
     *
     * @param int $hash phash of TYPO3 parent search result record
     * @param int $hash_t3 phash of the file indexation search record
     * @return void
     */
    public function submit_section($hash, $hash_t3)
    {
        $fields = [
            'phash' => $hash,
            'phash_t3' => $hash_t3,
            'page_id' => (int)$this->conf['id']
        ];
        $this->getRootLineFields($fields);
        if (IndexedSearchUtility::isTableUsed('index_section')) {
            $GLOBALS['TYPO3_DB']->exec_INSERTquery('index_section', $fields);
        }
    }

    /**
     * Removes records for the indexed page, $phash
     *
     * @param int $phash phash value to flush
     * @return void
     */
    public function removeOldIndexedPages($phash)
    {
        // Removing old registrations for all tables. Because the pages are TYPO3 pages there can be nothing else than 1-1 relations here.
        $tableArray = explode(',', 'index_phash,index_section,index_grlist,index_fulltext,index_debug');
        foreach ($tableArray as $table) {
            if (IndexedSearchUtility::isTableUsed($table)) {
                $GLOBALS['TYPO3_DB']->exec_DELETEquery($table, 'phash=' . (int)$phash);
            }
        }
        // Removing all index_section records with hash_t3 set to this hash (this includes such records set for external media on the page as well!). The re-insert of these records are done in indexRegularDocument($file).
        if (IndexedSearchUtility::isTableUsed('index_section')) {
            $GLOBALS['TYPO3_DB']->exec_DELETEquery('index_section', 'phash_t3=' . (int)$phash);
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
     * @param array $subinfo Array of "cHashParams" for files: This is for instance the page index for a PDF file (other document types it will be a zero)
     * @param string $ext File extension determining the type of media.
     * @param int $mtime Modification time of file.
     * @param int $ctime Creation time of file.
     * @param int $size Size of file in bytes
     * @param int $content_md5h Content HASH value.
     * @param array $contentParts Standard content array (using only title and body for a file)
     * @return void
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
            'cHashParams' => serialize($subinfo),
            'contentHash' => $content_md5h,
            'data_filename' => $file,
            'item_type' => $storeItemType,
            'item_title' => trim($contentParts['title']) ?: basename($file),
            'item_description' => $this->bodyDescription($contentParts),
            'item_mtime' => $mtime,
            'item_size' => $size,
            'item_crdate' => $ctime,
            'tstamp' => $GLOBALS['EXEC_TIME'],
            'crdate' => $GLOBALS['EXEC_TIME'],
            'gr_list' => $this->conf['gr_list'],
            'externalUrl' => $fileParts['scheme'] ? 1 : 0,
            'recordUid' => (int)$this->conf['recordUid'],
            'freeIndexUid' => (int)$this->conf['freeIndexUid'],
            'freeIndexSetId' => (int)$this->conf['freeIndexSetId'],
            'sys_language_uid' => (int)$this->conf['sys_language_uid']
        ];
        if (IndexedSearchUtility::isTableUsed('index_phash')) {
            $GLOBALS['TYPO3_DB']->exec_INSERTquery('index_phash', $fields);
        }
        // PROCESSING index_fulltext
        $fields = [
            'phash' => $hash['phash'],
            'fulltextdata' => implode(' ', $contentParts),
            'metaphonedata' => $this->metaphoneContent
        ];
        if ($this->indexerConfig['fullTextDataLength'] > 0) {
            $fields['fulltextdata'] = substr($fields['fulltextdata'], 0, $this->indexerConfig['fullTextDataLength']);
        }
        if (IndexedSearchUtility::isTableUsed('index_fulltext')) {
            $GLOBALS['TYPO3_DB']->exec_INSERTquery('index_fulltext', $fields);
        }
        // PROCESSING index_debug
        if ($this->indexerConfig['debugMode']) {
            $fields = [
                'phash' => $hash['phash'],
                'debuginfo' => serialize([
                    'cHashParams' => $subinfo,
                    'contentParts' => array_merge($contentParts, ['body' => substr($contentParts['body'], 0, 1000)]),
                    'logs' => $this->internal_log,
                    'lexer' => $this->lexerObj->debugString
                ])
            ];
            if (IndexedSearchUtility::isTableUsed('index_debug')) {
                $GLOBALS['TYPO3_DB']->exec_INSERTquery('index_debug', $fields);
            }
        }
    }

    /**
     * Stores file gr_list for a file IF it does not exist already
     *
     * @param int $hash phash value of file
     * @return void
     */
    public function submitFile_grlist($hash)
    {
        // Testing if there is a gr_list record for a non-logged in user and if so, there is no need to place another one.
        if (IndexedSearchUtility::isTableUsed('index_grlist')) {
            $count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('phash', 'index_grlist', 'phash=' . (int)$hash . ' AND (hash_gr_list=' . IndexedSearchUtility::md5inthash($this->defaultGrList) . ' OR hash_gr_list=' . IndexedSearchUtility::md5inthash($this->conf['gr_list']) . ')');
            if ($count == 0) {
                $this->submit_grlist($hash, $hash);
            }
        }
    }

    /**
     * Stores file section for a file IF it does not exist
     *
     * @param int $hash phash value of file
     * @return void
     */
    public function submitFile_section($hash)
    {
        // Testing if there is already a section
        if (IndexedSearchUtility::isTableUsed('index_section')) {
            $count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('phash', 'index_section', 'phash=' . (int)$hash . ' AND page_id=' . (int)$this->conf['id']);
            if ($count == 0) {
                $this->submit_section($hash, $this->hash['phash']);
            }
        }
    }

    /**
     * Removes records for the indexed page, $phash
     *
     * @param int $phash phash value to flush
     * @return void
     */
    public function removeOldIndexedFiles($phash)
    {
        // Removing old registrations for tables.
        $tableArray = explode(',', 'index_phash,index_grlist,index_fulltext,index_debug');
        foreach ($tableArray as $table) {
            if (IndexedSearchUtility::isTableUsed($table)) {
                $GLOBALS['TYPO3_DB']->exec_DELETEquery($table, 'phash=' . (int)$phash);
            }
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
            $row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('item_mtime,tstamp', 'index_phash', 'phash=' . (int)$phash);
            // If there was an indexing of the page...:
            if ($row) {
                if ($this->tstamp_maxAge && $row['tstamp'] + $this->tstamp_maxAge < $GLOBALS['EXEC_TIME']) {
                    // If max age is exceeded, index the page
                    // The configured max-age was exceeded for the document and thus it's indexed.
                    $result = 1;
                } else {
                    if (!$this->tstamp_minAge || $row['tstamp'] + $this->tstamp_minAge < $GLOBALS['EXEC_TIME']) {
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
                                    $this->log_setTSlogMessage('mtime matched, timestamp NOT updated because a maxAge is set (' . ($row['tstamp'] + $this->tstamp_maxAge - $GLOBALS['EXEC_TIME']) . ' seconds to expire time).', 1);
                                } else {
                                    $this->updateTstamp($phash);
                                    $this->log_setTSlogMessage('mtime matched, timestamp updated.', 1);
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
            $row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('phash', 'index_phash', 'phash_grouping=' . (int)$this->hash['phash_grouping'] . ' AND contentHash=' . (int)$this->content_md5h);
            if ($row) {
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
            $count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'index_phash', 'phash_grouping=' . (int)$hashGr . ' AND contentHash=' . (int)$content_md5h);
            $result = $count == 0;
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
            $count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('phash_x', 'index_grlist', 'phash_x=' . (int)$phash_x);
            $result = $count > 0;
        }
        return $result;
    }

    /**
     * Check if an grlist-entry for this hash exists and if not so, write one.
     *
     * @param int $phash phash of the search result that should be found
     * @param int $phash_x The real phash of the current content. The two values are different when a page with userlogin turns out to contain the exact same content as another already indexed version of the page; This is the whole reason for the grlist table in fact...
     * @return void
     * @see submit_grlist()
     */
    public function update_grlist($phash, $phash_x)
    {
        if (IndexedSearchUtility::isTableUsed('index_grlist')) {
            $count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('phash', 'index_grlist', 'phash=' . (int)$phash . ' AND hash_gr_list=' . IndexedSearchUtility::md5inthash($this->conf['gr_list']));
            if ($count == 0) {
                $this->submit_grlist($phash, $phash_x);
                $this->log_setTSlogMessage('Inserted gr_list \'' . $this->conf['gr_list'] . '\' for phash \'' . $phash . '\'', 1);
            }
        }
    }

    /**
     * Update tstamp for a phash row.
     *
     * @param int $phash phash value
     * @param int $mtime If set, update the mtime field to this value.
     * @return void
     */
    public function updateTstamp($phash, $mtime = 0)
    {
        if (IndexedSearchUtility::isTableUsed('index_phash')) {
            $updateFields = [
                'tstamp' => $GLOBALS['EXEC_TIME']
            ];
            if ($mtime) {
                $updateFields['item_mtime'] = (int)$mtime;
            }
            $GLOBALS['TYPO3_DB']->exec_UPDATEquery('index_phash', 'phash=' . (int)$phash, $updateFields);
        }
    }

    /**
     * Update SetID of the index_phash record.
     *
     * @param int $phash phash value
     * @return void
     */
    public function updateSetId($phash)
    {
        if (IndexedSearchUtility::isTableUsed('index_phash')) {
            $updateFields = [
                'freeIndexSetId' => (int)$this->conf['freeIndexSetId']
            ];
            $GLOBALS['TYPO3_DB']->exec_UPDATEquery('index_phash', 'phash=' . (int)$phash, $updateFields);
        }
    }

    /**
     * Update parsetime for phash row.
     *
     * @param int $phash phash value.
     * @param int $parsetime Parsetime value to set.
     * @return void
     */
    public function updateParsetime($phash, $parsetime)
    {
        if (IndexedSearchUtility::isTableUsed('index_phash')) {
            $updateFields = [
                'parsetime' => (int)$parsetime
            ];
            $GLOBALS['TYPO3_DB']->exec_UPDATEquery('index_phash', 'phash=' . (int)$phash, $updateFields);
        }
    }

    /**
     * Update section rootline for the page
     *
     * @return void
     */
    public function updateRootline()
    {
        if (IndexedSearchUtility::isTableUsed('index_section')) {
            $updateFields = [];
            $this->getRootLineFields($updateFields);
            $GLOBALS['TYPO3_DB']->exec_UPDATEquery('index_section', 'page_id=' . (int)$this->conf['id'], $updateFields);
        }
    }

    /**
     * Adding values for root-line fields.
     * rl0, rl1 and rl2 are standard. A hook might add more.
     *
     * @param array $fieldArray Field array, passed by reference
     * @return void
     */
    public function getRootLineFields(array &$fieldArray)
    {
        $fieldArray['rl0'] = (int)$this->conf['rootline_uids'][0];
        $fieldArray['rl1'] = (int)$this->conf['rootline_uids'][1];
        $fieldArray['rl2'] = (int)$this->conf['rootline_uids'][2];
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['addRootLineFields'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['addRootLineFields'] as $fieldName => $rootLineLevel) {
                $fieldArray[$fieldName] = (int)$this->conf['rootline_uids'][$rootLineLevel];
            }
        }
    }

    /**
     * Includes the crawler class
     *
     * @return void
     */
    public function includeCrawlerClass()
    {
        GeneralUtility::requireOnce(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('crawler') . 'class.tx_crawler_lib.php');
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
     * @return void
     */
    public function checkWordList($wordListArray)
    {
        if (IndexedSearchUtility::isTableUsed('index_words')) {
            if (!empty($wordListArray)) {
                $phashArray = [];
                foreach ($wordListArray as $value) {
                    $phashArray[] = (int)$value['hash'];
                }
                $cwl = implode(',', $phashArray);
                $count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('baseword', 'index_words', 'wid IN (' . $cwl . ')');
                $wordListArrayCount = count($wordListArray);
                if ($count !== $wordListArrayCount) {
                    $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('baseword', 'index_words', 'wid IN (' . $cwl . ')');
                    $this->log_setTSlogMessage('Inserting words: ' . ($wordListArrayCount - $count), 1);
                    while (false != ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
                        unset($wordListArray[$row['baseword']]);
                    }
                    $GLOBALS['TYPO3_DB']->sql_free_result($res);
                    foreach ($wordListArray as $key => $val) {
                        $insertFields = [
                            'wid' => $val['hash'],
                            'baseword' => $key,
                            'metaphone' => $val['metaphone']
                        ];
                        // A duplicate-key error will occur here if a word is NOT unset in the unset() line. However as long as the words in $wl are NOT longer as 60 chars (the baseword varchar is 60 characters...) this is not a problem.
                        $GLOBALS['TYPO3_DB']->exec_INSERTquery('index_words', $insertFields);
                    }
                }
            }
        }
    }

    /**
     * Submits RELATIONS between words and phash
     *
     * @param array $wordList Word list array
     * @param int $phash phash value
     * @return void
     */
    public function submitWords($wordList, $phash)
    {
        if (IndexedSearchUtility::isTableUsed('index_rel')) {
            $stopWords = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('wid', 'index_words', 'is_stopword != 0', '', '', '', 'wid');

            $GLOBALS['TYPO3_DB']->exec_DELETEquery('index_rel', 'phash=' . (int)$phash);
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
                    (int)$val['first'],
                    $this->freqMap($val['count'] / $this->wordcount),
                    $val['cmp'] & $this->flagBitMask
                ];
            }
            $GLOBALS['TYPO3_DB']->exec_INSERTmultipleRows('index_rel', $fields, $rows);
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
        return $newFreq;
    }

    /********************************
     *
     * Hashing
     *
     *******************************/
    /**
     * Get search hash, T3 pages
     *
     * @return void
     */
    public function setT3Hashes()
    {
        //  Set main array:
        $hArray = [
            'id' => (int)$this->conf['id'],
            'type' => (int)$this->conf['type'],
            'sys_lang' => (int)$this->conf['sys_language_uid'],
            'MP' => (string)$this->conf['MP'],
            'cHash' => $this->cHashParams
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
            'file' => $file
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
     * @return void
     */
    public function log_push($msg, $key)
    {
        if (is_object($GLOBALS['TT'])) {
            $GLOBALS['TT']->push($msg, $key);
        }
    }

    /**
     * Pull function wrapper for TT logging
     *
     * @return void
     */
    public function log_pull()
    {
        if (is_object($GLOBALS['TT'])) {
            $GLOBALS['TT']->pull();
        }
    }

    /**
     * Set log message function wrapper for TT logging
     *
     * @param string $msg Message to set
     * @param int $errorNum Error number
     * @return void
     */
    public function log_setTSlogMessage($msg, $errorNum = 0)
    {
        if (is_object($GLOBALS['TT'])) {
            $GLOBALS['TT']->setTSlogMessage($msg, $errorNum);
        }
        $this->internal_log[] = $msg;
    }

    /**
     * Makes sure that keywords are space-separated. This is impotant for their
     * proper displaying as a part of fulltext index.
     *
     * @param string $keywordList
     * @return string
     * @see http://forge.typo3.org/issues/14959
     */
    protected function addSpacesToKeywordList($keywordList)
    {
        $keywords = GeneralUtility::trimExplode(',', $keywordList);
        return ' ' . implode(', ', $keywords) . ' ';
    }
}
