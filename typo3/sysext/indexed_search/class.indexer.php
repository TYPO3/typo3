<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2001-2010 Kasper Skaarhoj (kasperYYYY@typo3.com)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * This class is a search indexer for TYPO3
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * Originally Christian Jul Jensen <christian@jul.net> helped as well.
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  141: class tx_indexedsearch_indexer
 *  207:     function hook_indexContent(&$pObj)
 *
 *              SECTION: Backend API
 *  308:     function backend_initIndexer($id, $type, $sys_language_uid, $MP, $uidRL, $cHash_array=array(), $createCHash=FALSE)
 *  347:     function backend_setFreeIndexUid($freeIndexUid, $freeIndexSetId=0)
 *  365:     function backend_indexAsTYPO3Page($title, $keywords, $description, $content, $charset, $mtime, $crdate=0, $recordUid=0)
 *
 *              SECTION: Initialization
 *  416:     function init()
 *  468:     function initializeExternalParsers()
 *
 *              SECTION: Indexing; TYPO3 pages (HTML content)
 *  509:     function indexTypo3PageContent()
 *  596:     function splitHTMLContent($content)
 *  642:     function getHTMLcharset($content)
 *  657:     function convertHTMLToUtf8($content,$charset='')
 *  685:     function embracingTags($string,$tagName,&$tagContent,&$stringAfter,&$paramList)
 *  712:     function typoSearchTags(&$body)
 *  741:     function extractLinks($content)
 *  812:     function extractHyperLinks($string)
 *
 *              SECTION: Indexing; external URL
 *  871:     function indexExternalUrl($externalUrl)
 *  902:     function getUrlHeaders($url)
 *
 *              SECTION: Indexing; external files (PDF, DOC, etc)
 *  948:     function indexRegularDocument($file, $force=FALSE, $contentTmpFile='', $altExtension='')
 * 1054:     function readFileContent($ext,$absFile,$cPKey)
 * 1071:     function fileContentParts($ext,$absFile)
 * 1089:     function splitRegularContent($content)
 *
 *              SECTION: Analysing content, Extracting words
 * 1122:     function charsetEntity2utf8(&$contentArr, $charset)
 * 1145:     function processWordsInArrays($contentArr)
 * 1170:     function procesWordsInArrays($contentArr)
 * 1180:     function bodyDescription($contentArr)
 * 1202:     function indexAnalyze($content)
 * 1223:     function analyzeHeaderinfo(&$retArr,$content,$key,$offset)
 * 1242:     function analyzeBody(&$retArr,$content)
 * 1262:     function metaphone($word,$retRaw=FALSE)
 *
 *              SECTION: SQL; TYPO3 Pages
 * 1304:     function submitPage()
 * 1378:     function submit_grlist($hash,$phash_x)
 * 1398:     function submit_section($hash,$hash_t3)
 * 1416:     function removeOldIndexedPages($phash)
 *
 *              SECTION: SQL; External media
 * 1459:     function submitFilePage($hash,$file,$subinfo,$ext,$mtime,$ctime,$size,$content_md5h,$contentParts)
 * 1525:     function submitFile_grlist($hash)
 * 1539:     function submitFile_section($hash)
 * 1553:     function removeOldIndexedFiles($phash)
 *
 *              SECTION: SQL Helper functions
 * 1589:     function checkMtimeTstamp($mtime,$phash)
 * 1625:     function checkContentHash()
 * 1642:     function checkExternalDocContentHash($hashGr,$content_md5h)
 * 1656:     function is_grlist_set($phash_x)
 * 1669:     function update_grlist($phash,$phash_x)
 * 1684:     function updateTstamp($phash,$mtime=0)
 * 1699:     function updateSetId($phash)
 * 1714:     function updateParsetime($phash,$parsetime)
 * 1727:     function updateRootline()
 * 1742:     function getRootLineFields(&$fieldArr)
 * 1761:     function removeLoginpagesWithContentHash()
 * 1778:     function includeCrawlerClass()
 *
 *              SECTION: SQL; Submitting words
 * 1805:     function checkWordList($wl)
 * 1842:     function submitWords($wl,$phash)
 * 1866:     function freqMap($freq)
 *
 *              SECTION: Hashing
 * 1899:     function setT3Hashes()
 * 1925:     function setExtHashes($file,$subinfo=array())
 * 1949:     function md5inthash($str)
 * 1959:     function makeCHash($paramArray)
 *
 *              SECTION: Internal logging functions
 * 1991:     function log_push($msg,$key)
 * 2000:     function log_pull()
 * 2011:     function log_setTSlogMessage($msg, $errorNum=0)
 *
 *              SECTION: tslib_fe hooks:
 * 2036:     function fe_headerNoCache(&$params, $ref)
 *
 * TOTAL FUNCTIONS: 59
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
/**
 * Indexing class for TYPO3 frontend
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_indexedsearch
 */
class tx_indexedsearch_indexer {

		// Messages:
	var $reasons = array(
		-1 => 'mtime matched the document, so no changes detected and no content updated',
		-2 => 'The minimum age was not exceeded',
		1 => "The configured max-age was exceeded for the document and thus it's indexed.",
		2 => 'The minimum age was exceed and mtime was set and the mtime was different, so the page was indexed.',
		3 => 'The minimum age was exceed, but mtime was not set, so the page was indexed.',
		4 => 'Page has never been indexed (is not represented in the index_phash table).'
	);

		// HTML code blocks to exclude from indexing:
	var $excludeSections = 'script,style';

		// Supported Extensions for external files:
	var $external_parsers = array();		// External parser objects, keys are file extension names. Values are objects with certain methods.

		// Fe-group list (pages might be indexed separately for each usergroup combination to support search in access limited pages!)
	var $defaultGrList = '0,-1';

		// Min/Max times:
	var $tstamp_maxAge = 0;		// If set, this tells a number of seconds that is the maximum age of an indexed document. Regardless of mtime the document will be re-indexed if this limit is exceeded.
	var $tstamp_minAge = 0;		// If set, this tells a minimum limit before a document can be indexed again. This is regardless of mtime.
	var $maxExternalFiles = 0;	// Max number of external files to index.

	var $forceIndexing = FALSE;		// If true, indexing is forced despite of hashes etc.
	var $crawlerActive = FALSE;		// Set when crawler is detected (internal)

		// INTERNALS:
	var $defaultContentArray=array(
		'title' => '',
		'description' => '',
		'keywords' => '',
		'body' => '',
	);
	var $wordcount = 0;
	var $externalFileCounter = 0;

	var $conf = array();		// Configuration set internally (see init functions for required keys and their meaning)
	var $indexerConfig = array();	// Indexer configuration, coming from $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['indexed_search']
	var $hash = array();		// Hash array, contains phash and phash_grouping
	var $file_phash_arr = array();	// Hash array for files
	var $contentParts = array();	// Content of TYPO3 page
	var $content_md5h = '';
	var $internal_log = array();	// Internal log
	var $indexExternalUrl_content = '';

	var $cHashParams = array();	// cHashparams array

	var $freqRange = 32000;
	var $freqMax = 0.1;

		// Objects:
	/**
	 * Charset class object
	 *
	 * @var t3lib_cs
	 */
	var $csObj;

	/**
	 * Metaphone object, if any
	 *
	 * @var user_DoubleMetaPhone
	 */
	var $metaphoneObj;

	/**
	 * Lexer object for word splitting
	 *
	 * @var tx_indexedsearch_lexer
	 */
	var $lexerObj;



	/**
	 * Parent Object (TSFE) Initialization
	 *
	 * @param	object		Parent Object (frontend TSFE object), passed by reference
	 * @return	void
	 */
	function hook_indexContent(&$pObj)	{

			// Indexer configuration from Extension Manager interface:
		$indexerConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['indexed_search']);

			// Crawler activation:
			// Requirements are that the crawler is loaded, a crawler session is running and re-indexing requested as processing instruction:
		if (t3lib_extMgm::isLoaded('crawler')
				&& $pObj->applicationData['tx_crawler']['running']
				&& in_array('tx_indexedsearch_reindex', $pObj->applicationData['tx_crawler']['parameters']['procInstructions']))	{

				// Setting simple log message:
			$pObj->applicationData['tx_crawler']['log'][] = 'Forced Re-indexing enabled';

				// Setting variables:
			$this->crawlerActive = TRUE;	// Crawler active flag
			$this->forceIndexing = TRUE;	// Force indexing despite timestamps etc.
		}

			// Determine if page should be indexed, and if so, configure and initialize indexer
		if ($pObj->config['config']['index_enable'])	{
			$this->log_push('Index page','');

			if (!$indexerConfig['disableFrontendIndexing'] || $this->crawlerActive)	{
				if (!$pObj->page['no_search'])	{
					if (!$pObj->no_cache)	{
						if (!strcmp($pObj->sys_language_uid,$pObj->sys_language_content))	{

								// Setting up internal configuration from config array:
							$this->conf = array();

								// Information about page for which the indexing takes place
							$this->conf['id'] = $pObj->id;				// Page id
							$this->conf['type'] = $pObj->type;			// Page type
							$this->conf['sys_language_uid'] = $pObj->sys_language_uid;	// sys_language UID of the language of the indexing.
							$this->conf['MP'] = $pObj->MP;				// MP variable, if any (Mount Points)
							$this->conf['gr_list'] = $pObj->gr_list;	// Group list

							$this->conf['cHash'] = $pObj->cHash;					// cHash string for additional parameters
							$this->conf['cHash_array'] = $pObj->cHash_array;		// Array of the additional parameters

							$this->conf['crdate'] = $pObj->page['crdate'];			// The creation date of the TYPO3 page
							$this->conf['page_cache_reg1'] = $pObj->page_cache_reg1;	// reg1 of the caching table. Not known what practical use this has.

								// Root line uids
							$this->conf['rootline_uids'] = array();
							foreach($pObj->config['rootLine'] as $rlkey => $rldat)	{
								$this->conf['rootline_uids'][$rlkey] = $rldat['uid'];
							}

								// Content of page:
							$this->conf['content'] = $pObj->content;					// Content string (HTML of TYPO3 page)
							$this->conf['indexedDocTitle'] = $pObj->convOutputCharset($pObj->indexedDocTitle);	// Alternative title for indexing
							$this->conf['metaCharset'] = $pObj->metaCharset;			// Character set of content (will be converted to utf-8 during indexing)
							$this->conf['mtime'] = $pObj->register['SYS_LASTCHANGED'];	// Most recent modification time (seconds) of the content on the page. Used to evaluate whether it should be re-indexed.

								// Configuration of behavior:
							$this->conf['index_externals'] = $pObj->config['config']['index_externals'];	// Whether to index external documents like PDF, DOC etc. (if possible)
							$this->conf['index_descrLgd'] = $pObj->config['config']['index_descrLgd'];		// Length of description text (max 250, default 200)
							$this->conf['index_metatags'] = isset($pObj->config['config']['index_metatags']) ? $pObj->config['config']['index_metatags'] : true;

								// Set to zero:
							$this->conf['recordUid'] = 0;
							$this->conf['freeIndexUid'] = 0;
							$this->conf['freeIndexSetId'] = 0;

								// Init and start indexing:
							$this->init();
							$this->indexTypo3PageContent();
						} else $this->log_setTSlogMessage('Index page? No, ->sys_language_uid was different from sys_language_content which indicates that the page contains fall-back content and that would be falsely indexed as localized content.');
					} else $this->log_setTSlogMessage('Index page? No, page was set to "no_cache" and so cannot be indexed.');
				} else $this->log_setTSlogMessage('Index page? No, The "No Search" flag has been set in the page properties!');
			} else $this->log_setTSlogMessage('Index page? No, Ordinary Frontend indexing during rendering is disabled.');
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
	 * @param	integer		The page uid, &id=
	 * @param	integer		The page type, &type=
	 * @param	integer		sys_language uid, typically &L=
	 * @param	string		The MP variable (Mount Points), &MP=
	 * @param	array		Rootline array of only UIDs.
	 * @param	array		Array of GET variables to register with this indexing
	 * @param	boolean		If set, calculates a cHash value from the $cHash_array. Probably you will not do that since such cases are indexed through the frontend and the idea of this interface is to index non-cachable pages from the backend!
	 * @return	void
	 */
	function backend_initIndexer($id, $type, $sys_language_uid, $MP, $uidRL, $cHash_array=array(), $createCHash=FALSE)	{

			// Setting up internal configuration from config array:
		$this->conf = array();

			// Information about page for which the indexing takes place
		$this->conf['id'] = $id;				// Page id	(integer)
		$this->conf['type'] = $type;			// Page type (integer)
		$this->conf['sys_language_uid'] = $sys_language_uid;	// sys_language UID of the language of the indexing (integer)
		$this->conf['MP'] = $MP;				// MP variable, if any (Mount Points) (string)
		$this->conf['gr_list'] = '0,-1';	// Group list (hardcoded for now...)

			// cHash values:
		$this->conf['cHash'] = $createCHash ? t3lib_div::generateCHash(t3lib_div::implodeArrayForUrl('', $cHash_array)) : '';	// cHash string for additional parameters
		$this->conf['cHash_array'] = $cHash_array;		// Array of the additional parameters

			// Set to defaults
		$this->conf['freeIndexUid'] = 0;
		$this->conf['freeIndexSetId'] = 0;
		$this->conf['page_cache_reg1'] = '';

			// Root line uids
		$this->conf['rootline_uids'] = $uidRL;

			// Configuration of behavior:
		$this->conf['index_externals'] = 1;	// Whether to index external documents like PDF, DOC etc. (if possible)
		$this->conf['index_descrLgd'] = 200;		// Length of description text (max 250, default 200)
		$this->conf['index_metatags'] = true;	// Whether to index document keywords and description (if present)

			// Init and start indexing:
		$this->init();
	}

	/**
	 * Sets the free-index uid. Can be called right after backend_initIndexer()
	 *
	 * @param	integer		Free index UID
	 * @param	integer		Set id - an integer identifying the "set" of indexing operations.
	 * @return	void
	 */
	function backend_setFreeIndexUid($freeIndexUid, $freeIndexSetId=0)	{
		$this->conf['freeIndexUid'] = $freeIndexUid;
		$this->conf['freeIndexSetId'] = $freeIndexSetId;
	}

	/**
	 * Indexing records as the content of a TYPO3 page.
	 *
	 * @param	string		Title equivalent
	 * @param	string		Keywords equivalent
	 * @param	string		Description equivalent
	 * @param	string		The main content to index
	 * @param	string		The charset of the title, keyword, description and body-content. MUST BE VALID, otherwise nothing is indexed!
	 * @param	integer		Last modification time, in seconds
	 * @param	integer		The creation date of the content, in seconds
	 * @param	integer		The record UID that the content comes from (for registration with the indexed rows)
	 * @return	void
	 */
	function backend_indexAsTYPO3Page($title, $keywords, $description, $content, $charset, $mtime, $crdate=0, $recordUid=0)	{

			// Content of page:
		$this->conf['mtime'] = $mtime;			// Most recent modification time (seconds) of the content
		$this->conf['crdate'] = $crdate;		// The creation date of the TYPO3 content
		$this->conf['recordUid'] = $recordUid;	// UID of the record, if applicable

			// Construct fake HTML for parsing:
		$this->conf['content'] = '
		<html>
			<head>
				<title>'.htmlspecialchars($title).'</title>
				<meta name="keywords" content="'.htmlspecialchars($keywords).'" />
				<meta name="description" content="'.htmlspecialchars($description).'" />
			</head>
			<body>
				'.htmlspecialchars($content).'
			</body>
		</html>';					// Content string (HTML of TYPO3 page)

			// Initializing charset:
		$this->conf['metaCharset'] = $charset;			// Character set of content (will be converted to utf-8 during indexing)
		$this->conf['indexedDocTitle'] = '';	// Alternative title for indexing

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
	 * @return	void
	 */
	function init()	{
		global $TYPO3_CONF_VARS;

			// Initializing:
		$this->cHashParams = $this->conf['cHash_array'];
		if (is_array($this->cHashParams) && count($this->cHashParams))	{
			if ($this->conf['cHash'])	$this->cHashParams['cHash'] = $this->conf['cHash'];	// Add this so that URL's come out right...
			unset($this->cHashParams['encryptionKey']);		// encryptionKey is added inside TSFE in order to calculate the cHash value and it should NOT be a part of this array!!! If it is it will be exposed in links!!!
		}

			// Setting phash / phash_grouping which identifies the indexed page based on some of these variables:
		$this->setT3Hashes();

			// Indexer configuration from Extension Manager interface:
		$this->indexerConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['indexed_search']);
		$this->tstamp_minAge = t3lib_div::intInRange($this->indexerConfig['minAge']*3600,0);
		$this->tstamp_maxAge = t3lib_div::intInRange($this->indexerConfig['maxAge']*3600,0);
		$this->maxExternalFiles = t3lib_div::intInRange($this->indexerConfig['maxExternalFiles'],0,1000,5);
		$this->flagBitMask = t3lib_div::intInRange($this->indexerConfig['flagBitMask'],0,255);

			// Initialize external document parsers:
			// Example configuration, see ext_localconf.php of this file!
		if ($this->conf['index_externals'])	{
			$this->initializeExternalParsers();
		}

			// Initialize lexer (class that deconstructs the text into words):
			// Example configuration (localconf.php) for this hook: $TYPO3_CONF_VARS['EXTCONF']['indexed_search']['lexer'] = 'EXT:indexed_search/class.lexer.php:&tx_indexedsearch_lexer';
		$lexerObjRef = $TYPO3_CONF_VARS['EXTCONF']['indexed_search']['lexer'] ?
						$TYPO3_CONF_VARS['EXTCONF']['indexed_search']['lexer'] :
						'EXT:indexed_search/class.lexer.php:&tx_indexedsearch_lexer';
		$this->lexerObj = t3lib_div::getUserObj($lexerObjRef);
		$this->lexerObj->debug = $this->indexerConfig['debugMode'];

			// Initialize metaphone hook:
			// Example configuration (localconf.php) for this hook: $TYPO3_CONF_VARS['EXTCONF']['indexed_search']['metaphone'] = 'EXT:indexed_search/class.doublemetaphone.php:&user_DoubleMetaPhone';
		if ($TYPO3_CONF_VARS['EXTCONF']['indexed_search']['metaphone'])	{
			$this->metaphoneObj = t3lib_div::getUserObj($TYPO3_CONF_VARS['EXTCONF']['indexed_search']['metaphone']);
			$this->metaphoneObj->pObj = $this;
		}

			// Init charset class:
		$this->csObj = t3lib_div::makeInstance('t3lib_cs');
	}

	/**
	 * Initialize external parsers
	 *
	 * @return	void
	 * @access private
	 * @see init()
	 */
	function initializeExternalParsers()	{
		global $TYPO3_CONF_VARS;

		if (is_array($TYPO3_CONF_VARS['EXTCONF']['indexed_search']['external_parsers']))	{
			foreach($TYPO3_CONF_VARS['EXTCONF']['indexed_search']['external_parsers'] as $extension => $_objRef)	{
				$this->external_parsers[$extension] = t3lib_div::getUserObj($_objRef);
				$this->external_parsers[$extension]->pObj = $this;

					// Init parser and if it returns false, unset its entry again:
				if (!$this->external_parsers[$extension]->initParser($extension))	{
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
	 * @return	void
	 */
	function indexTypo3PageContent()	{

		$check = $this->checkMtimeTstamp($this->conf['mtime'], $this->hash['phash']);
		$is_grlist = $this->is_grlist_set($this->hash['phash']);

		if ($check > 0 || !$is_grlist || $this->forceIndexing)	{

				// Setting message:
			if ($this->forceIndexing)	{
				$this->log_setTSlogMessage('Indexing needed, reason: Forced',1);
			} elseif ($check > 0)	{
				$this->log_setTSlogMessage('Indexing needed, reason: '.$this->reasons[$check],1);
			} else {
				$this->log_setTSlogMessage('Indexing needed, reason: Updates gr_list!',1);
			}

					// Divide into title,keywords,description and body:
			$this->log_push('Split content','');
				$this->contentParts = $this->splitHTMLContent($this->conf['content']);
				if ($this->conf['indexedDocTitle'])	{
					$this->contentParts['title'] = $this->conf['indexedDocTitle'];
				}
			$this->log_pull();

				// Calculating a hash over what is to be the actual page content. Maybe this hash should not include title,description and keywords? The bodytext is the primary concern. (on the other hand a changed page-title would make no difference then, so dont!)
			$this->content_md5h = $this->md5inthash(implode($this->contentParts,''));

				// This function checks if there is already a page (with gr_list = 0,-1) indexed and if that page has the very same contentHash.
				// If the contentHash is the same, then we can rest assured that this page is already indexed and regardless of mtime and origContent we don't need to do anything more.
				// This will also prevent pages from being indexed if a fe_users has logged in and it turns out that the page content is not changed anyway. fe_users logged in should always search with hash_gr_list = "0,-1" OR "[their_group_list]". This situation will be prevented only if the page has been indexed with no user login on before hand. Else the page will be indexed by users until that event. However that does not present a serious problem.
			$checkCHash = $this->checkContentHash();
			if (!is_array($checkCHash) || $check===1)	{
				$Pstart=t3lib_div::milliseconds();

				$this->log_push('Converting charset of content ('.$this->conf['metaCharset'].') to utf-8','');
					$this->charsetEntity2utf8($this->contentParts,$this->conf['metaCharset']);
				$this->log_pull();

						// Splitting words
				$this->log_push('Extract words from content','');
					$splitInWords = $this->processWordsInArrays($this->contentParts);
				$this->log_pull();

						// Analyse the indexed words.
				$this->log_push('Analyse the extracted words','');
					$indexArr = $this->indexAnalyze($splitInWords);
				$this->log_pull();

						// Submitting page (phash) record
				$this->log_push('Submitting page','');
					$this->submitPage();
				$this->log_pull();

						// Check words and submit to word list if not there
				$this->log_push('Check word list and submit words','');
					$this->checkWordList($indexArr);
					$this->submitWords($indexArr,$this->hash['phash']);
				$this->log_pull();

						// Set parsetime
				$this->updateParsetime($this->hash['phash'],t3lib_div::milliseconds()-$Pstart);

						// Checking external files if configured for.
				$this->log_push('Checking external files','');
				if ($this->conf['index_externals'])	{
					$this->extractLinks($this->conf['content']);
				}
				$this->log_pull();
			} else {
				$this->updateTstamp($this->hash['phash'],$this->conf['mtime']);	// Update the timestatmp
				$this->updateSetId($this->hash['phash']);
				$this->update_grlist($checkCHash['phash'],$this->hash['phash']);	// $checkCHash['phash'] is the phash of the result row that is similar to the current phash regarding the content hash.
				$this->updateRootline();
				$this->log_setTSlogMessage('Indexing not needed, the contentHash, '.$this->content_md5h.', has not changed. Timestamp, grlist and rootline updated if necessary.');
			}
		} else {
			$this->log_setTSlogMessage('Indexing not needed, reason: '.$this->reasons[$check]);
		}
	}

	/**
	 * Splits HTML content and returns an associative array, with title, a list of metatags, and a list of words in the body.
	 *
	 * @param	string		HTML content to index. To some degree expected to be made by TYPO3 (ei. splitting the header by ":")
	 * @return	array		Array of content, having keys "title", "body", "keywords" and "description" set.
	 * @see splitRegularContent()
	 */
	function splitHTMLContent($content) {

			// divide head from body ( u-ouh :) )
		$contentArr = $this->defaultContentArray;
		$contentArr['body'] = stristr($content,'<body');
		$headPart = substr($content,0,-strlen($contentArr['body']));

			// get title
		$this->embracingTags($headPart,'TITLE',$contentArr['title'],$dummy2,$dummy);
		$titleParts = explode(':',$contentArr['title'],2);
		$contentArr['title'] = trim(isset($titleParts[1]) ? $titleParts[1] : $titleParts[0]);

			// get keywords and description metatags
		if($this->conf['index_metatags']) {
			for($i=0;$this->embracingTags($headPart,'meta',$dummy,$headPart,$meta[$i]);$i++) { /*nothing*/ }
			for($i=0;isset($meta[$i]);$i++) {
				$meta[$i] = t3lib_div::get_tag_attributes($meta[$i]);
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
		$tagList = explode(',',$this->excludeSections);
		foreach($tagList as $tag)	{
			while($this->embracingTags($contentArr['body'],$tag,$dummy,$contentArr['body'],$dummy2));
		}

			// remove tags, but first make sure we don't concatenate words by doing it
		$contentArr['body'] = str_replace('<',' <',$contentArr['body']);
		$contentArr['body'] = trim(strip_tags($contentArr['body']));

		$contentArr['keywords'] = trim($contentArr['keywords']);
		$contentArr['description'] = trim($contentArr['description']);

			// Return array
		return $contentArr;
	}

	/**
	 * Extract the charset value from HTML meta tag.
	 *
	 * @param	string		HTML content
	 * @return	string		The charset value if found.
	 */
	function getHTMLcharset($content)	{
		if (preg_match('/<meta[[:space:]]+[^>]*http-equiv[[:space:]]*=[[:space:]]*["\']CONTENT-TYPE["\'][^>]*>/i',$content,$reg))	{
			if (preg_match('/charset[[:space:]]*=[[:space:]]*([[:alnum:]-]+)/i',$reg[0],$reg2))	{
				return $reg2[1];
			}
		}
	}

	/**
	 * Converts a HTML document to utf-8
	 *
	 * @param	string		HTML content, any charset
	 * @param	string		Optional charset (otherwise extracted from HTML)
	 * @return	string		Converted HTML
	 */
	function convertHTMLToUtf8($content,$charset='')	{

			// Find charset:
		$charset = $charset ? $charset : $this->getHTMLcharset($content);
		$charset = $this->csObj->parse_charset($charset);

			// Convert charset:
		if ($charset && $charset!=='utf-8')	{
			$content = $this->csObj->utf8_encode($content, $charset);
		}
			// Convert entities, assuming document is now UTF-8:
		$content = $this->csObj->entities_to_utf8($content, TRUE);

		return $content;
	}

	/**
	 * Finds first occurence of embracing tags and returns the embraced content and the original string with
	 * the tag removed in the two passed variables. Returns false if no match found. ie. useful for finding
	 * <title> of document or removing <script>-sections
	 *
	 * @param	string		String to search in
	 * @param	string		Tag name, eg. "script"
	 * @param	string		Passed by reference: Content inside found tag
	 * @param	string		Passed by reference: Content after found tag
	 * @param	string		Passed by reference: Attributes of the found tag.
	 * @return	boolean		Returns false if tag was not found, otherwise true.
	 */
	function embracingTags($string,$tagName,&$tagContent,&$stringAfter,&$paramList) {
		$endTag = '</'.$tagName.'>';
		$startTag = '<'.$tagName;

		$isTagInText = stristr($string,$startTag);		// stristr used because we want a case-insensitive search for the tag.
		if(!$isTagInText) return false;	// if the tag was not found, return false

		list($paramList,$isTagInText) = explode('>',substr($isTagInText,strlen($startTag)),2);
		$afterTagInText = stristr($isTagInText,$endTag);
		if ($afterTagInText)	{
			$stringBefore = substr($string, 0, strpos(strtolower($string), strtolower($startTag)));
			$tagContent = substr($isTagInText,0,strlen($isTagInText)-strlen($afterTagInText));
			$stringAfter = $stringBefore.substr($afterTagInText,strlen($endTag));
		} else {	// If there was no ending tag, the tagContent is blank and anything after the tag it self is returned.
			$tagContent='';
			$stringAfter = $isTagInText;
		}

		return true;
	}

	/**
	 * Removes content that shouldn't be indexed according to TYPO3SEARCH-tags.
	 *
	 * @param	string		HTML Content, passed by reference
	 * @return	boolean		Returns true if a TYPOSEARCH_ tag was found, otherwise false.
	 */
	function typoSearchTags(&$body) {
		$expBody = preg_split('/\<\!\-\-[\s]?TYPO3SEARCH_/',$body);

		if(count($expBody)>1) {
			$body = '';

			foreach($expBody as $val)	{
				$part = explode('-->',$val,2);
				if(trim($part[0])=='begin') {
					$body.= $part[1];
					$prev = '';
				} elseif(trim($part[0])=='end') {
					$body.= $prev;
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
	 * @param	string		HTML content
	 * @return	void
	 */
	function extractLinks($content) {

			// Get links:
		$list = $this->extractHyperLinks($content);

		if ($this->indexerConfig['useCrawlerForExternalFiles'] && t3lib_extMgm::isLoaded('crawler'))	{
			$this->includeCrawlerClass();
			$crawler = t3lib_div::makeInstance('tx_crawler_lib');
		}

			// Traverse links:
		foreach($list as $linkInfo)	{

				// Decode entities:
			if ($linkInfo['localPath'])	{	// localPath means: This file is sent by a download script. While the indexed URL has to point to $linkInfo['href'], the absolute path to the file is specified here!
				$linkSource = t3lib_div::htmlspecialchars_decode($linkInfo['localPath']);
			} else {
				$linkSource = t3lib_div::htmlspecialchars_decode($linkInfo['href']);
			}

				// Parse URL:
			$qParts = parse_url($linkSource);

				// Check for jumpurl (TYPO3 specific thing...)
			if ($qParts['query'] && strstr($qParts['query'],'jumpurl='))	{
				parse_str($qParts['query'],$getP);
				$linkSource = $getP['jumpurl'];
				$qParts = parse_url($linkSource);	// parse again due to new linkSource!
			}

			if (!$linkInfo['localPath'] && $qParts['scheme']) {
				if ($this->indexerConfig['indexExternalURLs'])	{
						// Index external URL (http or otherwise)
					$this->indexExternalUrl($linkSource);
				}
			} elseif (!$qParts['query']) {
				$linkSource = urldecode($linkSource);
				if (t3lib_div::isAllowedAbsPath($linkSource))	{
					$localFile = $linkSource;
				} else {
					$localFile = t3lib_div::getFileAbsFileName(PATH_site.$linkSource);
				}
				if ($localFile && @is_file($localFile))	{

						// Index local file:
					if ($linkInfo['localPath'])	{

						$fI = pathinfo($linkSource);
						$ext = strtolower($fI['extension']);
						if (is_object($crawler))	{
							$params = array(
								'document' => $linkSource,
								'alturl' => $linkInfo['href'],
								'conf' => $this->conf
							);
							unset($params['conf']['content']);

							$crawler->addQueueEntry_callBack(0,$params,'EXT:indexed_search/class.crawler.php:&tx_indexedsearch_files',$this->conf['id']);
							$this->log_setTSlogMessage('media "'.$params['document'].'" added to "crawler" queue.',1);
						} else {
							$this->indexRegularDocument($linkInfo['href'], false, $linkSource, $ext);
						}
					} else {
						if (is_object($crawler))	{
							$params = array(
								'document' => $linkSource,
								'conf' => $this->conf
							);
							unset($params['conf']['content']);
							$crawler->addQueueEntry_callBack(0,$params,'EXT:indexed_search/class.crawler.php:&tx_indexedsearch_files',$this->conf['id']);
							$this->log_setTSlogMessage('media "'.$params['document'].'" added to "crawler" queue.',1);
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
	function extractHyperLinks($html)	{
		$htmlParser = t3lib_div::makeInstance('t3lib_parseHtml');
		$htmlParts = $htmlParser->splitTags('a', $html);
		$hyperLinksData = array();
		foreach ($htmlParts as $index => $tagData) {
			if (($index % 2) !== 0)	{
				$tagAttributes = $htmlParser->get_tag_attributes($tagData, TRUE);
				$firstTagName = $htmlParser->getFirstTagName($tagData);

				if (strtolower($firstTagName) == 'a') {
					if ($tagAttributes[0]['href'] && $tagAttributes[0]['href']{0} != '#') {
						$hyperLinksData[] = array(
							'tag' => $tagData,
							'href' => $tagAttributes[0]['href'],
							'localPath' => $this->createLocalPath($tagAttributes[0]['href'])
							);
						}
				}
			}
		}

		return $hyperLinksData;
	}

	/**
	 * Extracts the "base href" from content string.
	 *
	 * @param	string		Content to analyze
	 * @return	string		The base href or an empty string if not found
	 */
	public function extractBaseHref($html) {
		$href = '';
		$htmlParser = t3lib_div::makeInstance('t3lib_parseHtml');
		$htmlParts = $htmlParser->splitTags('base', $html);
		foreach ($htmlParts as $index => $tagData) {
			if (($index % 2) !== 0) {
				$tagAttributes = $htmlParser->get_tag_attributes($tagData, true);
				$firstTagName = $htmlParser->getFirstTagName($tagData);
				if (strtolower($firstTagName) == 'base') {
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
	 * @param	string		URL, eg. "http://typo3.org/"
	 * @return	void
	 * @see indexRegularDocument()
	 */
	function indexExternalUrl($externalUrl)	{

			// Parse External URL:
		$qParts = parse_url($externalUrl);
		$fI = pathinfo($qParts['path']);
		$ext = strtolower($fI['extension']);

			// Get headers:
		$urlHeaders = $this->getUrlHeaders($externalUrl);
		if (stristr($urlHeaders['Content-Type'],'text/html'))	{
			$content = $this->indexExternalUrl_content = t3lib_div::getUrl($externalUrl);
			if (strlen($content))	{

					// Create temporary file:
				$tmpFile = t3lib_div::tempnam('EXTERNAL_URL');
				if ($tmpFile) {
					t3lib_div::writeFile($tmpFile, $content);

						// Index that file:
					$this->indexRegularDocument($externalUrl, TRUE, $tmpFile, 'html');	// Using "TRUE" for second parameter to force indexing of external URLs (mtime doesn't make sense, does it?)
					unlink($tmpFile);
				}
			}
		}
	}

	/**
	 * Getting HTTP request headers of URL
	 *
	 * @param	string		The URL
	 * @param	integer		Timeout (seconds?)
	 * @return	mixed		If no answer, returns false. Otherwise an array where HTTP headers are keys
	 */
	function getUrlHeaders($url)	{
		$content = t3lib_div::getURL($url,2);	// Try to get the headers only

		if (strlen($content))	{
				// Compile headers:
			$headers = t3lib_div::trimExplode(LF,$content,1);
			$retVal = array();
			foreach($headers as $line)	{
				if (!strlen(trim($line)))	{
					break;	// Stop at the first empty line (= end of header)
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
	 * @param $sourcePath
	 * @return string Absolute path to file if file is local, else empty string
	 */
	protected function createLocalPath($sourcePath) {
		$localPath = '';
		static $pathFunctions = array(
			'createLocalPathFromT3vars',
			'createLocalPathUsingAbsRefPrefix',
			'createLocalPathUsingDomainURL',
			'createLocalPathFromAbsoluteURL',
			'createLocalPathFromRelativeURL'
			);
		foreach ($pathFunctions as $functionName) {
			$localPath = $this->$functionName($sourcePath);
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
	protected function createLocalPathFromT3vars($sourcePath) {
		$localPath = '';
		$indexLocalFiles = $GLOBALS['T3_VAR']['ext']['indexed_search']['indexLocalFiles'];
		if (is_array($indexLocalFiles)) {
			$md5 = t3lib_div::shortMD5($sourcePath);
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
	protected function createLocalPathUsingDomainURL($sourcePath) {
		$localPath = '';
		$baseURL = t3lib_div::getIndpEnv('TYPO3_SITE_URL');
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
	protected function createLocalPathUsingAbsRefPrefix($sourcePath) {
		$localPath = '';
		if ($GLOBALS['TSFE'] instanceof tslib_fe) {
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
	protected function createLocalPathFromAbsoluteURL($sourcePath) {
		$localPath = '';
		if ($sourcePath{0} == '/') {
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
	protected function createLocalPathFromRelativeURL($sourcePath) {
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
	 * @return boolean
	 */
	static protected function isRelativeURL($url) {
		$urlParts = @parse_url($url);
		return ($urlParts['scheme'] == '' && $urlParts['path']{0} != '/');
	}

	/**
	 * Checks if the path points to the file inside the web site
	 *
	 * @param string $filePath
	 * @return boolean
	 */
	static protected function isAllowedLocalFile($filePath) {
		$filePath = t3lib_div::resolveBackPath($filePath);
		$insideWebPath = (substr($filePath, 0, strlen(PATH_site)) == PATH_site);
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
	 * @param	string		Relative Filename, relative to PATH_site. It can also be an absolute path as long as it is inside the lockRootPath (validated with t3lib_div::isAbsPath()). Finally, if $contentTmpFile is set, this value can be anything, most likely a URL
	 * @param	boolean		If set, indexing is forced (despite content hashes, mtime etc).
	 * @param	string		Temporary file with the content to read it from (instead of $file). Used when the $file is a URL.
	 * @param	string		File extension for temporary file.
	 * @return	void
	 */
	function indexRegularDocument($file, $force=FALSE, $contentTmpFile='', $altExtension='')	{

			// Init
		$fI = pathinfo($file);
		$ext = $altExtension ? $altExtension : strtolower($fI['extension']);

			// Create abs-path:
		if (!$contentTmpFile)	{
			if (!t3lib_div::isAbsPath($file))	{	// Relative, prepend PATH_site:
				$absFile = t3lib_div::getFileAbsFileName(PATH_site.$file);
			} else {	// Absolute, pass-through:
				$absFile = $file;
			}
			$absFile = t3lib_div::isAllowedAbsPath($absFile) ? $absFile : '';
		} else {
			$absFile = $contentTmpFile;
		}

			// Indexing the document:
		if ($absFile && @is_file($absFile))	{
			if ($this->external_parsers[$ext])	{
				$mtime = filemtime($absFile);
				$cParts = $this->fileContentParts($ext,$absFile);

				foreach($cParts as $cPKey)	{
					$this->internal_log = array();
					$this->log_push('Index: '.str_replace('.','_',basename($file)).($cPKey?'#'.$cPKey:''),'');
					$Pstart = t3lib_div::milliseconds();
					$subinfo = array('key' => $cPKey);	// Setting page range. This is "0" (zero) when no division is made, otherwise a range like "1-3"
					$phash_arr = $this->file_phash_arr = $this->setExtHashes($file,$subinfo);
					$check = $this->checkMtimeTstamp($mtime, $phash_arr['phash']);
					if ($check > 0 || $force)	{
						if ($check > 0)	{
							$this->log_setTSlogMessage('Indexing needed, reason: '.$this->reasons[$check],1);
						} else {
							$this->log_setTSlogMessage('Indexing forced by flag',1);
						}

							// Check external file counter:
						if ($this->externalFileCounter < $this->maxExternalFiles || $force)	{

									// Divide into title,keywords,description and body:
							$this->log_push('Split content','');
								$contentParts = $this->readFileContent($ext,$absFile,$cPKey);
							$this->log_pull();

							if (is_array($contentParts))	{
									// Calculating a hash over what is to be the actual content. (see indexTypo3PageContent())
								$content_md5h = $this->md5inthash(implode($contentParts,''));

								if ($this->checkExternalDocContentHash($phash_arr['phash_grouping'], $content_md5h) || $force)	{

										// Increment counter:
									$this->externalFileCounter++;

										// Splitting words
									$this->log_push('Extract words from content','');
										$splitInWords = $this->processWordsInArrays($contentParts);
									$this->log_pull();

										// Analyse the indexed words.
									$this->log_push('Analyse the extracted words','');
										$indexArr = $this->indexAnalyze($splitInWords);
									$this->log_pull();

										// Submitting page (phash) record
									$this->log_push('Submitting page','');
										$size = filesize($absFile);
										$ctime = filemtime($absFile);	// Unfortunately I cannot determine WHEN a file is originally made - so I must return the modification time...
										$this->submitFilePage($phash_arr,$file,$subinfo,$ext,$mtime,$ctime,$size,$content_md5h,$contentParts);
									$this->log_pull();

										// Check words and submit to word list if not there
									$this->log_push('Check word list and submit words','');
										$this->checkWordList($indexArr);
										$this->submitWords($indexArr,$phash_arr['phash']);
									$this->log_pull();

										// Set parsetime
									$this->updateParsetime($phash_arr['phash'],t3lib_div::milliseconds()-$Pstart);
								} else {
									$this->updateTstamp($phash_arr['phash'],$mtime);	// Update the timestamp
									$this->log_setTSlogMessage('Indexing not needed, the contentHash, '.$content_md5h.', has not changed. Timestamp updated.');
								}
							} else $this->log_setTSlogMessage('Could not index file! Unsupported extension.');
						} else $this->log_setTSlogMessage('The limit of '.$this->maxExternalFiles.' has already been exceeded, so no indexing will take place this time.');
					} else $this->log_setTSlogMessage('Indexing not needed, reason: '.$this->reasons[$check]);

						// Checking and setting sections:
		#			$this->submitFile_grlist($phash_arr['phash']);	// Setting a gr_list record if there is none already (set for default fe_group)
					$this->submitFile_section($phash_arr['phash']);		// Setting a section-record for the file. This is done also if the file is not indexed. Notice that section records are deleted when the page is indexed.
					$this->log_pull();
				}
			} else $this->log_setTSlogMessage('Indexing not possible; The extension "'.$ext.'" was not supported.');
		} else $this->log_setTSlogMessage('Indexing not possible; File "'.$absFile.'" not found or valid.');
	}

	/**
	 * Reads the content of an external file being indexed.
	 * The content from the external parser MUST be returned in utf-8!
	 *
	 * @param	string		File extension, eg. "pdf", "doc" etc.
	 * @param	string		Absolute filename of file (must exist and be validated OK before calling function)
	 * @param	string		Pointer to section (zero for all other than PDF which will have an indication of pages into which the document should be splitted.)
	 * @return	array		Standard content array (title, description, keywords, body keys)
	 */
	function readFileContent($ext,$absFile,$cPKey)	{

			// Consult relevant external document parser:
		if (is_object($this->external_parsers[$ext]))	{
			$contentArr = $this->external_parsers[$ext]->readFileContent($ext,$absFile,$cPKey);
		}

		return $contentArr;
	}

	/**
	 * Creates an array with pointers to divisions of document.
	 *
	 * @param	string		File extension
	 * @param	string		Absolute filename (must exist and be validated OK before calling function)
	 * @return	array		Array of pointers to sections that the document should be divided into
	 */
	function fileContentParts($ext,$absFile)	{
		$cParts = array(0);

			// Consult relevant external document parser:
		if (is_object($this->external_parsers[$ext]))	{
			$cParts = $this->external_parsers[$ext]->fileContentParts($ext,$absFile);
		}

		return $cParts;
	}

	/**
	 * Splits non-HTML content (from external files for instance)
	 *
	 * @param	string		Input content (non-HTML) to index.
	 * @return	array		Array of content, having the key "body" set (plus "title", "description" and "keywords", but empty)
	 * @see splitHTMLContent()
	 */
	function splitRegularContent($content) {
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
	 * @param	array		Standard content array
	 * @param	string		Charset of the input content (converted to utf-8)
	 * @return	void
	 */
	function charsetEntity2utf8(&$contentArr, $charset)	{

			// Convert charset if necessary
		foreach ($contentArr as $key => $value) {
			if (strlen($contentArr[$key]))	{

				if ($charset!=='utf-8')	{
					$contentArr[$key] = $this->csObj->utf8_encode($contentArr[$key], $charset);
				}

					// decode all numeric / html-entities in the string to real characters:
				$contentArr[$key] = $this->csObj->entities_to_utf8($contentArr[$key],TRUE);
			}
		}
	}

	/**
	 * Processing words in the array from split*Content -functions
	 *
	 * @param	array		Array of content to index, see splitHTMLContent() and splitRegularContent()
	 * @return	array		Content input array modified so each key is not a unique array of words
	 */
	function processWordsInArrays($contentArr)	{

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
	 * Processing words in the array from split*Content -functions
	 * This function is only a wrapper because the function has been removed (see above).
	 *
	 * @param	array		Array of content to index, see splitHTMLContent() and splitRegularContent()
	 * @return	array		Content input array modified so each key is not a unique array of words
	 * @deprecated since TYPO3 4.0, this function will be removed in TYPO3 4.5.
	 */
	function procesWordsInArrays($contentArr)	{
		t3lib_div::logDeprecatedFunction();

		return $this->processWordsInArrays($contentArr);
	}

	/**
	 * Extracts the sample description text from the content array.
	 *
	 * @param	array		Content array
	 * @return	string		Description string
	 */
	function bodyDescription($contentArr)	{

			// Setting description
		$maxL = t3lib_div::intInRange($this->conf['index_descrLgd'],0,255,200);
		if ($maxL)	{
				// Takes the quadruple lenght first, because whitespace and entities may be removed and thus shorten the string more yet.
	#		$bodyDescription = implode(' ',split('[[:space:],]+',substr(trim($contentArr['body']),0,$maxL*4)));
			$bodyDescription = str_replace(array(' ',TAB,CR,LF),' ',$contentArr['body']);

				// Shorten the string:
			$bodyDescription = $this->csObj->strtrunc('utf-8', $bodyDescription, $maxL);
		}

		return $bodyDescription;
	}

	/**
	 * Analyzes content to use for indexing,
	 *
	 * @param	array		Standard content array: an array with the keys title,keywords,description and body, which all contain an array of words.
	 * @return	array		Index Array (whatever that is...)
	 */
	function indexAnalyze($content) {
		$indexArr = Array();
		$counter = 0;

		$this->analyzeHeaderinfo($indexArr,$content,'title',7);
		$this->analyzeHeaderinfo($indexArr,$content,'keywords',6);
		$this->analyzeHeaderinfo($indexArr,$content,'description',5);
		$this->analyzeBody($indexArr,$content);

		return ($indexArr);
	}

	/**
	 * Calculates relevant information for headercontent
	 *
	 * @param	array		Index array, passed by reference
	 * @param	array		Standard content array
	 * @param	string		Key from standard content array
	 * @param	integer		Bit-wise priority to type
	 * @return	void
	 */
	function analyzeHeaderinfo(&$retArr,$content,$key,$offset) {
		foreach ($content[$key] as $val) {
			$val = substr($val,0,60);	// Max 60 - because the baseword varchar IS 60. This MUST be the same.
			$retArr[$val]['cmp'] = $retArr[$val]['cmp']|pow(2,$offset);
			$retArr[$val]['count'] = $retArr[$val]['count']+1;
			$retArr[$val]['hash'] = hexdec(substr(md5($val),0,7));
			$retArr[$val]['metaphone'] = $this->metaphone($val);
			$this->wordcount++;
		}
	}

	/**
	 * Calculates relevant information for bodycontent
	 *
	 * @param	array		Index array, passed by reference
	 * @param	array		Standard content array
	 * @return	void
	 */
	function analyzeBody(&$retArr,$content) {
		foreach($content['body'] as $key => $val)	{
			$val = substr($val,0,60);	// Max 60 - because the baseword varchar IS 60. This MUST be the same.
			if(!isset($retArr[$val])) {
				$retArr[$val]['first'] = $key;
				$retArr[$val]['hash'] = hexdec(substr(md5($val),0,7));
				$retArr[$val]['metaphone'] = $this->metaphone($val);
			}
			$retArr[$val]['count'] = $retArr[$val]['count']+1;
			$this->wordcount++;
		}
	}

	/**
	 * Creating metaphone based hash from input word
	 *
	 * @param	string		Word to convert
	 * @param	boolean		If set, returns the raw metaphone value (not hashed)
	 * @return	mixed		Metaphone hash integer (or raw value, string)
	 */
	function metaphone($word,$retRaw=FALSE) {

		if (is_object($this->metaphoneObj))	{
			$tmp = $this->metaphoneObj->metaphone($word, $this->conf['sys_language_uid']);
		} else {
			$tmp = metaphone($word);
		}

			// Return raw value?
		if ($retRaw)	return $tmp;

			// Otherwise create hash and return integer
		if($tmp=='') $ret=0; else $ret=hexdec(substr(md5($tmp),0,7));
		return $ret;
	}
















	/********************************
	 *
	 * SQL; TYPO3 Pages
	 *
	 *******************************/

	/**
	 * Updates db with information about the page (TYPO3 page, not external media)
	 *
	 * @return	void
	 */
	function submitPage()	{

			// Remove any current data for this phash:
		$this->removeOldIndexedPages($this->hash['phash']);

			// setting new phash_row
		$fields = array(
			'phash' => $this->hash['phash'],
			'phash_grouping' => $this->hash['phash_grouping'],
			'cHashParams' => serialize($this->cHashParams),
			'contentHash' => $this->content_md5h,
			'data_page_id' => $this->conf['id'],
			'data_page_reg1' => $this->conf['page_cache_reg1'],
			'data_page_type' => $this->conf['type'],
			'data_page_mp' => $this->conf['MP'],
			'gr_list' => $this->conf['gr_list'],
			'item_type' => 0,	// TYPO3 page
			'item_title' => $this->contentParts['title'],
			'item_description' => $this->bodyDescription($this->contentParts),
			'item_mtime' => $this->conf['mtime'],
			'item_size' => strlen($this->conf['content']),
			'tstamp' => $GLOBALS['EXEC_TIME'],
			'crdate' => $GLOBALS['EXEC_TIME'],
			'item_crdate' => $this->conf['crdate'],	// Creation date of page
			'sys_language_uid' => $this->conf['sys_language_uid'],	// Sys language uid of the page. Should reflect which language it DOES actually display!
 			'externalUrl' => 0,
 			'recordUid' => intval($this->conf['recordUid']),
 			'freeIndexUid' => intval($this->conf['freeIndexUid']),
 			'freeIndexSetId' => intval($this->conf['freeIndexSetId']),
		);

		$GLOBALS['TYPO3_DB']->exec_INSERTquery('index_phash', $fields);

			// PROCESSING index_section
		$this->submit_section($this->hash['phash'],$this->hash['phash']);

			// PROCESSING index_grlist
		$this->submit_grlist($this->hash['phash'],$this->hash['phash']);

			// PROCESSING index_fulltext
		$fields = array(
			'phash' => $this->hash['phash'],
			'fulltextdata' => implode(' ', $this->contentParts)
		);
		if ($this->indexerConfig['fullTextDataLength']>0)	{
			$fields['fulltextdata'] = substr($fields['fulltextdata'],0,$this->indexerConfig['fullTextDataLength']);
		}
		$GLOBALS['TYPO3_DB']->exec_INSERTquery('index_fulltext', $fields);

			// PROCESSING index_debug
		if ($this->indexerConfig['debugMode'])	{
			$fields = array(
				'phash' => $this->hash['phash'],
				'debuginfo' => serialize(array(
						'cHashParams' => $this->cHashParams,
						'external_parsers initialized' => array_keys($this->external_parsers),
						'conf' => array_merge($this->conf,array('content'=>substr($this->conf['content'],0,1000))),
						'contentParts' => array_merge($this->contentParts,array('body' => substr($this->contentParts['body'],0,1000))),
						'logs' => $this->internal_log,
						'lexer' => $this->lexerObj->debugString,
					))
			);
			$GLOBALS['TYPO3_DB']->exec_INSERTquery('index_debug', $fields);
		}
	}

	/**
	 * Stores gr_list in the database.
	 *
	 * @param	integer		Search result record phash
	 * @param	integer		Actual phash of current content
	 * @return	void
	 * @see update_grlist()
	 */
	function submit_grlist($hash,$phash_x)	{

			// Setting the gr_list record
		$fields = array(
			'phash' => $hash,
			'phash_x' => $phash_x,
			'hash_gr_list' => $this->md5inthash($this->conf['gr_list']),
			'gr_list' => $this->conf['gr_list']
		);
		$GLOBALS['TYPO3_DB']->exec_INSERTquery('index_grlist', $fields);
	}

	/**
	 * Stores section
	 * $hash and $hash_t3 are the same for TYPO3 pages, but different when it is external files.
	 *
	 * @param	integer		phash of TYPO3 parent search result record
	 * @param	integer		phash of the file indexation search record
	 * @return	void
	 */
	function submit_section($hash,$hash_t3)	{
		$fields = array(
			'phash' => $hash,
			'phash_t3' => $hash_t3,
			'page_id' => intval($this->conf['id'])
		);

		$this->getRootLineFields($fields);

		$GLOBALS['TYPO3_DB']->exec_INSERTquery('index_section', $fields);
	}

	/**
	 * Removes records for the indexed page, $phash
	 *
	 * @param	integer		phash value to flush
	 * @return	void
	 */
	function removeOldIndexedPages($phash)	{
			// Removing old registrations for all tables. Because the pages are TYPO3 pages there can be nothing else than 1-1 relations here.
		$tableArr = explode(',','index_phash,index_section,index_grlist,index_fulltext,index_debug');
		foreach($tableArr as $table)	{
			$GLOBALS['TYPO3_DB']->exec_DELETEquery($table, 'phash='.intval($phash));
		}
			// Removing all index_section records with hash_t3 set to this hash (this includes such records set for external media on the page as well!). The re-insert of these records are done in indexRegularDocument($file).
		$GLOBALS['TYPO3_DB']->exec_DELETEquery('index_section', 'phash_t3='.intval($phash));
	}













	/********************************
	 *
	 * SQL; External media
	 *
	 *******************************/


	/**
	 * Updates db with information about the file
	 *
	 * @param	array		Array with phash and phash_grouping keys for file
	 * @param	string		File name
	 * @param	array		Array of "cHashParams" for files: This is for instance the page index for a PDF file (other document types it will be a zero)
	 * @param	string		File extension determining the type of media.
	 * @param	integer		Modification time of file.
	 * @param	integer		Creation time of file.
	 * @param	integer		Size of file in bytes
	 * @param	integer		Content HASH value.
	 * @param	array		Standard content array (using only title and body for a file)
	 * @return	void
	 */
	function submitFilePage($hash,$file,$subinfo,$ext,$mtime,$ctime,$size,$content_md5h,$contentParts)	{

			// Find item Type:
		$storeItemType = $this->external_parsers[$ext]->ext2itemtype_map[$ext];
		$storeItemType = $storeItemType ? $storeItemType : $ext;

			// Remove any current data for this phash:
		$this->removeOldIndexedFiles($hash['phash']);

			// Split filename:
		$fileParts = parse_url($file);

			// Setting new
		$fields = array(
			'phash' => $hash['phash'],
			'phash_grouping' => $hash['phash_grouping'],
			'cHashParams' => serialize($subinfo),
			'contentHash' => $content_md5h,
			'data_filename' => $file,
			'item_type' => $storeItemType,
			'item_title' => trim($contentParts['title']) ? $contentParts['title'] : basename($file),
			'item_description' => $this->bodyDescription($contentParts),
			'item_mtime' => $mtime,
			'item_size' => $size,
			'item_crdate' => $ctime,
			'tstamp' => $GLOBALS['EXEC_TIME'],
			'crdate' => $GLOBALS['EXEC_TIME'],
			'gr_list' => $this->conf['gr_list'],
 			'externalUrl' => $fileParts['scheme'] ? 1 : 0,
 			'recordUid' => intval($this->conf['recordUid']),
 			'freeIndexUid' => intval($this->conf['freeIndexUid']),
 			'freeIndexSetId' => intval($this->conf['freeIndexSetId']),
		);
		$GLOBALS['TYPO3_DB']->exec_INSERTquery('index_phash', $fields);

			// PROCESSING index_fulltext
		$fields = array(
			'phash' => $hash['phash'],
			'fulltextdata' => implode(' ', $contentParts)
		);
		if ($this->indexerConfig['fullTextDataLength']>0)	{
			$fields['fulltextdata'] = substr($fields['fulltextdata'],0,$this->indexerConfig['fullTextDataLength']);
		}
		$GLOBALS['TYPO3_DB']->exec_INSERTquery('index_fulltext', $fields);

			// PROCESSING index_debug
		if ($this->indexerConfig['debugMode'])	{
			$fields = array(
				'phash' => $hash['phash'],
				'debuginfo' => serialize(array(
						'cHashParams' => $subinfo,
						'contentParts' => array_merge($contentParts,array('body' => substr($contentParts['body'],0,1000))),
						'logs' => $this->internal_log,
						'lexer' => $this->lexerObj->debugString,
					))
			);
			$GLOBALS['TYPO3_DB']->exec_INSERTquery('index_debug', $fields);
		}
	}

	/**
	 * Stores file gr_list for a file IF it does not exist already
	 *
	 * @param	integer		phash value of file
	 * @return	void
	 */
	function submitFile_grlist($hash)	{
			// Testing if there is a gr_list record for a non-logged in user and if so, there is no need to place another one.
		$count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(
			'phash',
			'index_grlist',
			'phash=' . intval($hash) .
				' AND (hash_gr_list=' . $this->md5inthash($this->defaultGrList) .
				' OR hash_gr_list=' . $this->md5inthash($this->conf['gr_list']) . ')'
		);
		if (!$count) {
			$this->submit_grlist($hash,$hash);
		}
	}

	/**
	 * Stores file section for a file IF it does not exist
	 *
	 * @param	integer		phash value of file
	 * @return	void
	 */
	function submitFile_section($hash)	{
			// Testing if there is a section
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('phash', 'index_section', 'phash='.intval($hash).' AND page_id='.intval($this->conf['id']));
		if (!$GLOBALS['TYPO3_DB']->sql_num_rows($res))	{
			$this->submit_section($hash,$this->hash['phash']);
		}
	}

	/**
	 * Removes records for the indexed page, $phash
	 *
	 * @param	integer		phash value to flush
	 * @return	void
	 */
	function removeOldIndexedFiles($phash)	{

			// Removing old registrations for tables.
		$tableArr = explode(',','index_phash,index_grlist,index_fulltext,index_debug');
		foreach($tableArr as $table)	{
			$GLOBALS['TYPO3_DB']->exec_DELETEquery($table, 'phash='.intval($phash));
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
	 * @param	integer		mtime value to test against limits and indexed page (usually this is the mtime of the cached document)
	 * @param	integer		"phash" used to select any already indexed page to see what its mtime is.
	 * @return	integer		Result integer: Generally: <0 = No indexing, >0 = Do indexing (see $this->reasons): -2) Min age was NOT exceeded and so indexing cannot occur.  -1) mtime matched so no need to reindex page. 0) N/A   1) Max age exceeded, page must be indexed again.   2) mtime of indexed page doesn't match mtime given for current content and we must index page.  3) No mtime was set, so we will index...  4) No indexed page found, so of course we will index.
	 */
	function checkMtimeTstamp($mtime,$phash)	{

			// Select indexed page:
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('item_mtime,tstamp', 'index_phash', 'phash='.intval($phash));
		$out = 0;

			// If there was an indexing of the page...:
		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			if ($this->tstamp_maxAge && ($row['tstamp'] + $this->tstamp_maxAge) < $GLOBALS['EXEC_TIME']) {	// If max age is exceeded, index the page
				$out = 1;		// The configured max-age was exceeded for the document and thus it's indexed.
			} else {
				if (!$this->tstamp_minAge || ($row['tstamp'] + $this->tstamp_minAge) < $GLOBALS['EXEC_TIME']) {	// if minAge is not set or if minAge is exceeded, consider at mtime
					if ($mtime)	{		// It mtime is set, then it's tested. If not, the page must clearly be indexed.
						if ($row['item_mtime'] != $mtime)	{	// And if mtime is different from the index_phash mtime, it's about time to re-index.
							$out = 2;		// The minimum age was exceed and mtime was set and the mtime was different, so the page was indexed.
						} else {
							$out = -1;		// mtime matched the document, so no changes detected and no content updated
							if ($this->tstamp_maxAge)	{
								$this->log_setTSlogMessage('mtime matched, timestamp NOT updated because a maxAge is set (' . ($row['tstamp'] + $this->tstamp_maxAge - $GLOBALS['EXEC_TIME']) . ' seconds to expire time).', 1);
							} else {
								$this->updateTstamp($phash);	// Update the timestatmp
								$this->log_setTSlogMessage('mtime matched, timestamp updated.',1);
							}
						}
					} else {$out = 3;	}	// The minimum age was exceed, but mtime was not set, so the page was indexed.
				} else {$out = -2;}			// The minimum age was not exceeded
			}
		} else {$out = 4;}	// Page has never been indexed (is not represented in the index_phash table).
		return $out;
	}

	/**
	 * Check content hash in phash table
	 *
	 * @return	mixed		Returns true if the page needs to be indexed (that is, there was no result), otherwise the phash value (in an array) of the phash record to which the grlist_record should be related!
	 */
	function checkContentHash()	{
			// With this query the page will only be indexed if it's content is different from the same "phash_grouping" -page.
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('phash', 'index_phash A', 'A.phash_grouping='.intval($this->hash['phash_grouping']).' AND A.contentHash='.intval($this->content_md5h));
		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			return $row;
		}
		return 1;
	}

	/**
	 * Check content hash for external documents
	 * Returns true if the document needs to be indexed (that is, there was no result)
	 *
	 * @param	integer		phash value to check (phash_grouping)
	 * @param	integer		Content hash to check
	 * @return	boolean		Returns true if the document needs to be indexed (that is, there was no result)
	 */
	function checkExternalDocContentHash($hashGr,$content_md5h)	{
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'index_phash A', 'A.phash_grouping='.intval($hashGr).' AND A.contentHash='.intval($content_md5h));
		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			return 0;
		}
		return 1;
	}

	/**
	 * Checks if a grlist record has been set for the phash value input (looking at the "real" phash of the current content, not the linked-to phash of the common search result page)
	 *
	 * @param	integer		Phash integer to test.
	 * @return	void
	 */
	function is_grlist_set($phash_x)	{
		return $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(
			'phash_x',
			'index_grlist',
			'phash_x=' . intval($phash_x)
		);
	}

	/**
	 * Check if an grlist-entry for this hash exists and if not so, write one.
	 *
	 * @param	integer		phash of the search result that should be found
	 * @param	integer		The real phash of the current content. The two values are different when a page with userlogin turns out to contain the exact same content as another already indexed version of the page; This is the whole reason for the grlist table in fact...
	 * @return	void
	 * @see submit_grlist()
	 */
	function update_grlist($phash,$phash_x)	{
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('phash', 'index_grlist', 'phash='.intval($phash).' AND hash_gr_list='.$this->md5inthash($this->conf['gr_list']));
		if (!$GLOBALS['TYPO3_DB']->sql_num_rows($res))	{
			$this->submit_grlist($phash,$phash_x);
			$this->log_setTSlogMessage("Inserted gr_list '".$this->conf['gr_list']."' for phash '".$phash."'",1);
		}
	}

	/**
	 * Update tstamp for a phash row.
	 *
	 * @param	integer		phash value
	 * @param	integer		If set, update the mtime field to this value.
	 * @return	void
	 */
	function updateTstamp($phash,$mtime=0)	{
		$updateFields = array(
			'tstamp' => $GLOBALS['EXEC_TIME']
		);
		if ($mtime)	{ $updateFields['item_mtime'] = intval($mtime); }

		$GLOBALS['TYPO3_DB']->exec_UPDATEquery('index_phash', 'phash='.intval($phash), $updateFields);
	}

	/**
	 * Update SetID of the index_phash record.
	 *
	 * @param	integer		phash value
	 * @return	void
	 */
	function updateSetId($phash)	{
		$updateFields = array(
			'freeIndexSetId' => intval($this->conf['freeIndexSetId'])
		);

		$GLOBALS['TYPO3_DB']->exec_UPDATEquery('index_phash', 'phash='.intval($phash), $updateFields);
	}

	/**
	 * Update parsetime for phash row.
	 *
	 * @param	integer		phash value.
	 * @param	integer		Parsetime value to set.
	 * @return	void
	 */
	function updateParsetime($phash,$parsetime)	{
		$updateFields = array(
			'parsetime' => intval($parsetime)
		);

		$GLOBALS['TYPO3_DB']->exec_UPDATEquery('index_phash', 'phash='.intval($phash), $updateFields);
	}

	/**
	 * Update section rootline for the page
	 *
	 * @return	void
	 */
	function updateRootline()	{

		$updateFields = array();
		$this->getRootLineFields($updateFields);

		$GLOBALS['TYPO3_DB']->exec_UPDATEquery('index_section', 'page_id='.intval($this->conf['id']), $updateFields);
	}

	/**
	 * Adding values for root-line fields.
	 * rl0, rl1 and rl2 are standard. A hook might add more.
	 *
	 * @param	array		Field array, passed by reference
	 * @return	void
	 */
	function getRootLineFields(&$fieldArr)	{

		$fieldArr['rl0'] = intval($this->conf['rootline_uids'][0]);
		$fieldArr['rl1'] = intval($this->conf['rootline_uids'][1]);
		$fieldArr['rl2'] = intval($this->conf['rootline_uids'][2]);

		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['addRootLineFields']))	{
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['addRootLineFields'] as $fieldName => $rootLineLevel)	{
				$fieldArr[$fieldName] = intval($this->conf['rootline_uids'][$rootLineLevel]);
			}
		}
	}

	/**
	 * Removes any indexed pages with userlogins which has the same contentHash
	 * NOT USED anywhere inside this class!
	 *
	 * @return	void
	 */
	function removeLoginpagesWithContentHash()	{
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'index_phash A,index_grlist B', '
					A.phash=B.phash
					AND A.phash_grouping='.intval($this->hash['phash_grouping']).'
					AND B.hash_gr_list!='.$this->md5inthash($this->defaultGrList).'
					AND A.contentHash='.intval($this->content_md5h));
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$this->log_setTSlogMessage("The currently indexed page was indexed under no user-login and apparently this page has been indexed under login conditions earlier, but with the SAME content. Therefore the old similar page with phash='".$row['phash']."' are now removed.",1);
			$this->removeOldIndexedPages($row['phash']);
		}
	}

	/**
	 * Includes the crawler class
	 *
	 * @return	void
	 */
	function includeCrawlerClass()	{
		global $TYPO3_CONF_VARS;

		require_once(t3lib_extMgm::extPath('crawler').'class.tx_crawler_lib.php');
	}










	/********************************
	 *
	 * SQL; Submitting words
	 *
	 *******************************/

	/**
	 * Adds new words to db
	 *
	 * @param	array		Word List array (where each word has information about position etc).
	 * @return	void
	 */
	function checkWordList($wl) {
		$phashArr = array();
		foreach ($wl as $key => $value) {
			$phashArr[] = $wl[$key]['hash'];
		}
		if (count($phashArr))	{
			$cwl = implode(',',$phashArr);
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('baseword', 'index_words', 'wid IN ('.$cwl.')');

			if($GLOBALS['TYPO3_DB']->sql_num_rows($res)!=count($wl)) {
				$this->log_setTSlogMessage('Inserting words: '.(count($wl)-$GLOBALS['TYPO3_DB']->sql_num_rows($res)),1);
				while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					unset($wl[$row['baseword']]);
				}

				foreach ($wl as $key => $val) {
					$insertFields = array(
						'wid' => $val['hash'],
						'baseword' => $key,
						'metaphone' => $val['metaphone']
					);
						// A duplicate-key error will occur here if a word is NOT unset in the unset() line. However as long as the words in $wl are NOT longer as 60 chars (the baseword varchar is 60 characters...) this is not a problem.
					$GLOBALS['TYPO3_DB']->exec_INSERTquery('index_words', $insertFields);
				}
			}
		}
	}

	/**
	 * Submits RELATIONS between words and phash
	 *
	 * @param	array		Word list array
	 * @param	integer		phash value
	 * @return	void
	 */
	function submitWords($wl,$phash) {
		$GLOBALS['TYPO3_DB']->exec_DELETEquery('index_rel', 'phash='.intval($phash));

		foreach($wl as $val)	{
			$insertFields = array(
				'phash' => $phash,
				'wid' => $val['hash'],
				'count' => $val['count'],
				'first' => $val['first'],
				'freq' => $this->freqMap(($val['count']/$this->wordcount)),
				'flags' => ($val['cmp'] & $this->flagBitMask)
			);

			$GLOBALS['TYPO3_DB']->exec_INSERTquery('index_rel', $insertFields);
		}
	}

	/**
	 * maps frequency from a real number in [0;1] to an integer in [0;$this->freqRange] with anything above $this->freqMax as 1
	 * and back.
	 *
	 * @param	double		Frequency
	 * @return	integer		Frequency in range.
	 */
	function freqMap($freq) {
		$mapFactor = $this->freqMax*100*$this->freqRange;
		if($freq<1) {
			$newFreq = $freq*$mapFactor;
			$newFreq = $newFreq>$this->freqRange?$this->freqRange:$newFreq;
		} else {
			$newFreq = $freq/$mapFactor;
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
	 * @return	void
	 */
	function setT3Hashes()	{

			//  Set main array:
		$hArray = array(
			'id' => (integer)$this->conf['id'],
			'type' => (integer)$this->conf['type'],
			'sys_lang' => (integer)$this->conf['sys_language_uid'],
			'MP' => (string)$this->conf['MP'],
			'cHash' => $this->cHashParams
		);

			// Set grouping hash (Identifies a "page" combined of id, type, language, mountpoint and cHash parameters):
		$this->hash['phash_grouping'] = $this->md5inthash(serialize($hArray));

			// Add gr_list and set plain phash (Subdivision where special page composition based on login is taken into account as well. It is expected that such pages are normally similar regardless of the login.)
		$hArray['gr_list'] = (string)$this->conf['gr_list'];
		$this->hash['phash'] = $this->md5inthash(serialize($hArray));
	}

	/**
	 * Get search hash, external files
	 *
	 * @param	string		File name / path which identifies it on the server
	 * @param	array		Additional content identifying the (subpart of) content. For instance; PDF files are divided into groups of pages for indexing.
	 * @return	array		Array with "phash_grouping" and "phash" inside.
	 */
	function setExtHashes($file,$subinfo=array())	{
			//  Set main array:
		$hash = array();
		$hArray = array(
			'file' => $file,
		);

			// Set grouping hash:
		$hash['phash_grouping'] = $this->md5inthash(serialize($hArray));

			// Add subinfo
		$hArray['subinfo'] = $subinfo;
		$hash['phash'] = $this->md5inthash(serialize($hArray));

		return $hash;
	}

	/**
	 * md5 integer hash
	 * Using 7 instead of 8 just because that makes the integers lower than 32 bit (28 bit) and so they do not interfere with UNSIGNED integers or PHP-versions which has varying output from the hexdec function.
	 *
	 * @param	string		String to hash
	 * @return	integer		Integer intepretation of the md5 hash of input string.
	 */
	function md5inthash($str)	{
		return hexdec(substr(md5($str),0,7));
	}

	/**
	 * Calculates the cHash value of input GET array (for constructing cHash values if needed)
	 *
	 * @param	array		Array of GET parameters to encode
	 * @return	void
	 * @deprecated since TYPO3 4.3, this function will be removed in TYPO3 4.5, use directly t3lib_div::calculateCHash()
	 */
	function makeCHash($paramArray)	{
		t3lib_div::logDeprecatedFunction();

		$addQueryParams = t3lib_div::implodeArrayForUrl('', $paramArray);

		$pA = t3lib_div::cHashParams($addQueryParams);

		return t3lib_div::shortMD5(serialize($pA));
	}












	/*********************************
	 *
	 * Internal logging functions
	 *
	 *********************************/

	/**
	 * Push function wrapper for TT logging
	 *
	 * @param	string		Title to set
	 * @param	string		Key (?)
	 * @return	void
	 */
	function log_push($msg,$key)	{
		if (is_object($GLOBALS['TT']))		$GLOBALS['TT']->push($msg,$key);
	}

	/**
	 * Pull function wrapper for TT logging
	 *
	 * @return	void
	 */
	function log_pull()	{
		if (is_object($GLOBALS['TT']))		$GLOBALS['TT']->pull();
	}

	/**
	 * Set log message function wrapper for TT logging
	 *
	 * @param	string		Message to set
	 * @param	integer		Error number
	 * @return	void
	 */
	function log_setTSlogMessage($msg, $errorNum=0)	{
		if (is_object($GLOBALS['TT']))		$GLOBALS['TT']->setTSlogMessage($msg,$errorNum);
		$this->internal_log[] = $msg;
	}








	/**************************
	 *
	 * tslib_fe hooks:
	 *
	 **************************/

	/**
	 * Frontend hook: If the page is not being re-generated this is our chance to force it to be (because re-generation of the page is required in order to have the indexer called!)
	 *
	 * @param	array		Parameters from frontend
	 * @param	object		TSFE object (reference under PHP5)
	 * @return	void
	 * @deprecated since TYPO3 4.3, this function will be removed in TYPO3 4.5, the method was extracted to hooks/class.tx_indexedsearch_tslib_fe_hook.php
	 */
	function fe_headerNoCache(&$params, $ref)	{
		t3lib_div::logDeprecatedFunction();

		require_once t3lib_extMgm::extPath('indexed_search') . 'hooks/class.tx_indexedsearch_tslib_fe_hook.php';
		t3lib_div::makeInstance('tx_indexedsearch_tslib_fe_hook')->headerNoCache($params, $ref);
	}

	/**
	 * Makes sure that keywords are space-separated. This is impotant for their
	 * proper displaying as a part of fulltext index.
	 *
	 * @param string $keywordList
	 * @return string
	 * @see http://bugs.typo3.org/view.php?id=1436
	 */
	protected function addSpacesToKeywordList($keywordList) {
		$keywords = t3lib_div::trimExplode(',', $keywordList);
		return ' ' . implode(', ', $keywords) . ' ';
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/indexed_search/class.indexer.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/indexed_search/class.indexer.php']);
}
?>