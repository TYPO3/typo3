<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2001-2004 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @coauthor	Christian Jul Jensen <christian@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  118: class tx_indexedsearch_indexer
 *  200:     function hook_indexContent(&$pObj)
 *
 *              SECTION: Initialization
 *  242:     function init()
 *  271:     function initExternalReaders()
 *
 *              SECTION: Indexing
 *  325:     function indexTypo3PageContent()
 *  400:     function splitHTMLContent($content)
 *  446:     function splitRegularContent($content)
 *  459:     function procesWordsInArrays($contentArr)
 *  482:     function bodyDescription($contentArr)
 *  499:     function extractLinks($content)
 *  531:     function getJumpurl($query)
 *  544:     function splitPdfInfo($pdfInfoArray)
 *  564:     function indexRegularDocument($file)
 *  647:     function readFileContent($ext,$absFile,$cPKey)
 *  711:     function fileContentParts($ext,$absFile)
 *  754:     function embracingTags($string,$tagName,&$tagContent,&$stringAfter,&$paramList)
 *  780:     function indexAnalyze($content)
 *  801:     function analyzeHeaderinfo(&$retArr,$content,$key,$offset)
 *  820:     function analyzeBody(&$retArr,$content)
 *  840:     function typoSearchTags(&$body)
 *
 *              SECTION: Words
 *  891:     function split2words(&$string)
 *  924:     function wordOK($w)
 *  942:     function metaphone($word)
 *  954:     function strtolower_all($str)
 *
 *              SECTION: SQL Helper functions
 *  985:     function freqMap($freq)
 * 1003:     function getRootLineFields(&$fieldArr)
 *
 *              SECTION: SQL Helper functions
 * 1043:     function removeIndexedPhashRow($phashList,$clearPageCache=1)
 * 1083:     function checkMtimeTstamp($mtime,$maxAge,$minAge,$phash)
 * 1117:     function update_grlist($phash,$phash_x)
 * 1129:     function is_grlist_set($phash_x)
 * 1140:     function checkContentHash()
 * 1154:     function removeLoginpagesWithContentHash()
 * 1172:     function removeOldIndexedPages($phash)
 * 1190:     function checkExternalDocContentHash($hashGr,$content_md5h)
 * 1205:     function updateTstamp($phash,$mtime=0)
 * 1221:     function updateParsetime($phash,$parsetime)
 * 1234:     function updateRootline()
 *
 *              SECTION: SQL; Inserting in database
 * 1264:     function submitPage()
 * 1317:     function submit_grlist($hash,$phash_x)
 * 1335:     function submit_section($hash,$hash_t3)
 * 1361:     function submitFilePage($hash,$file,$subinfo,$ext,$mtime,$ctime,$size,$content_md5h,$contentParts)
 * 1402:     function submitFile_grlist($hash)
 * 1419:     function submitFile_section($hash)
 * 1436:     function checkWordList($wl)
 * 1473:     function submitWords($wl,$phash)
 *
 *              SECTION: Hashing
 * 1517:     function setT3Hashes()
 * 1540:     function setExtHashes($file,$subinfo=array())
 * 1563:     function md5inthash($str)
 *
 * TOTAL FUNCTIONS: 47
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */



require_once(PATH_t3lib.'class.t3lib_htmlmail.php');


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
	var $convChars=array(
		'ÁÉÚÍÄËÜÖÏÆØÅ',
		'áéúíâêûôîæøå'
	);

		// HTML code blocks to exclude from indexing:
	var $excludeSections = 'script,style';

		// Supported Extensions for external files:
	var $supportedExtensions = array(
			'pdf' => 1,
			'doc' => 1,
			'txt' => 1,
			'html' => 1,
			'htm' => 1
		);

		// This value is also overridden from config.
	var $pdf_mode = -20;	// zero: whole PDF file is indexed in one. positive value: Indicates number of pages at a time, eg. "5" would means 1-5,6-10,.... Negative integer would indicate (abs value) number of groups. Eg "3" groups of 10 pages would be 1-4,5-8,9-10

		// This array is reset and configured in initialization:
	var $app = array(
		'pdftotext' => '/usr/local/bin/pdftotext',
		'pdfinfo' => '/usr/local/bin/pdfinfo',
		'catdoc' => '/usr/local/bin/catdoc'
	);

		// Fe-group list (pages might be indexed separately for each usergroup combination to support search in access limited pages!)
	var $defaultGrList='0,-1';

		// Min/Max times:
	var $tstamp_maxAge = 0;		// If set, this tells a number of seconds that is the maximum age of an indexed document. Regardless of mtime the document will be re-indexed if this limit is exceeded.
	var $tstamp_minAge = 0;		// If set, this tells a minimum limit before a document can be indexed again. This is regardless of mtime.

		// INTERNALS:
	var $defaultContentArray=array(
		'title' => '',
		'description' => '',
		'keywords' => '',
		'body' => '',
	);
	var $wordcount = 0;
	var $Itypes = array(
		'html' => 1,
		'htm' => 1,
		'pdf' => 2,
		'doc' => 3,
		'txt' => 4
	);
	var $conf = array();	// Configuration set internally
	var $hash = array();	// Hash array, contains phash and phash_grouping
	var $contentParts = array();
	var $pObj = '';				// Parent object, reference to global TSFE
	var $content_md5h = '';

	var $cHashParams = array();	// cHashparams array
	var $mtime = 0;				// If set, then the mtime of the document must be different in order to be indexed.
	var $rootLine = array();	// Root line from TSFE

	var $freqRange = 65000;
	var $freqMax = 0.1;




	/**
	 * Parent Object (TSFE)
	 *
	 * @param	object		Parent Object (frontend TSFE object), passed by reference
	 * @return	void
	 */
	function hook_indexContent(&$pObj)	{

		if ($pObj->config['config']['index_enable'])	{
			if (!$pObj->no_cache)	{
				$GLOBALS['TT']->push('Index page','');

						// Setting parent object:
					$this->pObj = &$pObj;

						// Init and start indexing:
					$this->init();
					$this->indexTypo3PageContent();
				$GLOBALS['TT']->pull();
			} else {
				$GLOBALS['TT']->push('Index page','');
				$GLOBALS['TT']->setTSlogMessage('Index page? No, page was set to "no_cache" and so cannot be indexed.');
				$GLOBALS['TT']->pull();
			}
		}
	}











	/********************************
	 *
	 * Initialization
	 *
	 *******************************/

	/**
	 * Initializes the object
	 *
	 * @return	void
	 */
	function init()	{

			// Initializing:
		$this->cHashParams = $this->pObj->cHash_array;
		if (is_array($this->cHashParams) && count($this->cHashParams))	{
			$this->cHashParams['cHash'] = $this->pObj->cHash;	// Add this so that URL's come out right...
		}

			// Modification time of page and root line transferred:
		$this->mtime = $this->pObj->register['SYS_LASTCHANGED'];
		$this->rootLine = $this->pObj->config['rootLine'];

			// Setting up internal configuration from config array:
		$this->conf = array();
		$this->conf['index_externals'] = $this->pObj->config['config']['index_externals'];
		$this->conf['index_descrLgd'] = $this->pObj->config['config']['index_descrLgd'];

			// Setting phash / phash_grouping which identifies the indexed page based on some of these variables:
		$this->setT3Hashes();

			// Initialize tools for reading PDF and Word documents:
		$this->initExternalReaders();
	}

	/**
	 * Initializes external readers, if any
	 *
	 * @return	void
	 */
	function initExternalReaders()	{
			// PDF + WORD tools:
			// First reset the class default settings (disabling)
		$this->app = array();
		$this->supportedExtensions['pdf'] = 0;
		$this->supportedExtensions['doc'] = 0;

			// Then read indexer-config and set if appropriate:
		$indexerConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['indexed_search']);

			// PDF
		if ($indexerConfig['pdftools'])	{
			$pdfPath = ereg_replace("\/$",'',$indexerConfig['pdftools']).'/';
			if ((ini_get('safe_mode') && $pdfPath) || (@is_file($pdfPath.'pdftotext') && @is_file($pdfPath.'pdfinfo')))	{
				$this->app['pdfinfo'] = $pdfPath.'pdfinfo';
				$this->app['pdftotext'] = $pdfPath.'pdftotext';
				$this->supportedExtensions['pdf'] = 1;
			} else $GLOBALS['TT']->setTSlogMessage("PDF tools was not found in paths '".$pdfPath."pdftotext' and/or '".$pdfPath."pdfinfo'",3);
		} else $GLOBALS['TT']->setTSlogMessage('PDF tools disabled',1);

			// Catdoc
		if ($indexerConfig['catdoc'])	{
			$catdocPath = ereg_replace("\/$",'',$indexerConfig['catdoc']).'/';
			if (is_file($catdocPath.'catdoc'))	{
				$this->app['catdoc'] = $catdocPath.'catdoc';
				$this->supportedExtensions['doc'] = 1;
			} else $GLOBALS['TT']->setTSlogMessage("'catdoc' tool for reading Word-files was not found in paths '".$catdocPath."catdoc'",3);
		} else $GLOBALS['TT']->setTSlogMessage('catdoc tools (Word-files) disabled',1);

			// PDF mode:
		$this->pdf_mode = t3lib_div::intInRange($indexerConfig['pdf_mode'],-100,100);
	}











	/********************************
	 *
	 * Indexing
	 *
	 *******************************/

	/**
	 * Start indexing of the TYPO3 page
	 *
	 * @return	void
	 */
	function indexTypo3PageContent()	{

		$check = $this->checkMtimeTstamp($this->mtime, $this->tstamp_maxAge, $this->tstamp_minAge, $this->hash['phash']);
# WHAT IS THIS? Test that it works...		$is_grlist = $this->is_grlist_set($phash_x);	// Use $this->hash['phash']?

		if ($check > 0 || !$is_grlist)	{

				// Setting message:
			if ($check > 0)	{
				$GLOBALS['TT']->setTSlogMessage('Indexing needed, reason: '.$this->reasons[$check],1);
			} else {
				$GLOBALS['TT']->setTSlogMessage('Indexing needed, reason: Updates gr_list!',1);
			}

					// Divide into title,keywords,description and body:
			$GLOBALS['TT']->push('Split content','');
				$this->contentParts = $this->splitHTMLContent($this->pObj->content);
				if ($this->pObj->indexedDocTitle)	$this->contentParts['title'] = $this->pObj->indexedDocTitle;
			$GLOBALS['TT']->pull();

				// Calculating a hash over what is to be the actual page content. Maybe this hash should not include title,description and keywords? The bodytext is the primary concern. (on the other hand a changed page-title would make no difference then, so dont!)
			$this->content_md5h = $this->md5inthash(implode($this->contentParts,''));
				// This function checks if there is already a page (with gr_list = 0,-1) indexed and if that page has the very same contentHash.
				// If the contentHash is the same, then we can rest assured that this page is already indexed and regardless of mtime and origContent we don't need to do anything more.
				// This will also prevent pages from being indexed if a fe_users has logged in and it turns out that the page content is not changed anyway. fe_users logged in should always search with hash_gr_list = "0,-1" OR "[their_group_list]". This situation will be prevented only if the page has been indexed with no user login on before hand. Else the page will be indexed by users until that event. However that does not present a serious problem.
			$checkCHash = $this->checkContentHash();
			if (!is_array($checkCHash))	{
				$Pstart=t3lib_div::milliseconds();
						// Splitting words
				$GLOBALS['TT']->push('Extract words from content','');
					$splitInWords = $this->procesWordsInArrays($this->contentParts);
				$GLOBALS['TT']->pull();

						// Analyse the indexed words.
				$GLOBALS['TT']->push('Analyse the extracted words','');
					$indexArr = $this->indexAnalyze($splitInWords);
				$GLOBALS['TT']->pull();

						// Submitting page (phash) record
				$GLOBALS['TT']->push('Submitting page','');
					$this->submitPage();
				$GLOBALS['TT']->pull();

						// Check words and submit to word list if not there
				$GLOBALS['TT']->push('Check word list and submit words','');
					$this->checkWordList($indexArr);
					$this->submitWords($indexArr,$this->hash['phash']);
				$GLOBALS['TT']->pull();

						// Set parsetime
				$this->updateParsetime($this->hash['phash'],t3lib_div::milliseconds()-$Pstart);

						// Checking external files if configured for.
				$GLOBALS['TT']->push('Checking external files','');
				if ($this->conf['index_externals'])	{
					$this->extractLinks($this->pObj->content);
				}
				$GLOBALS['TT']->pull();
			} else {
				$this->updateTstamp($this->hash['phash'],$this->mtime);	// Update the timestatmp
				$this->update_grlist($checkCHash['phash'],$this->hash['phash']);
				$this->updateRootline();
				$GLOBALS['TT']->setTSlogMessage('Indexing not needed, the contentHash, '.$this->content_md5h.', has not changed. Timestamp, grlist and rootline updated if necessary.');
			}
		} else {
			$GLOBALS['TT']->setTSlogMessage('Indexing not needed, reason: '.$this->reasons[$check]);
		}
	}

	/**
	 * Splits HTML content and returns an associative array, with title, a list of metatags, and a list of words in the body.
	 *
	 * @param	[type]		$content: ...
	 * @return	[type]		...
	 */
	function splitHTMLContent($content) {

		# divide head from body ( u-ouh :) )

		$contentArr=$this->defaultContentArray;
		$contentArr['body'] = stristr($content,'<body');
		$headPart = substr($content,0,-strlen($contentArr['body']));

		# get title
		$this->embracingTags($headPart,'TITLE',$contentArr['title'],$dummy2,$dummy);
		$titleParts = explode(':',$contentArr['title'],2);
		$contentArr['title'] = trim(isset($titleParts[1]) ? $titleParts[1] : $titleParts[0]);

		# get keywords and description metatags
		for($i=0;$this->embracingTags($headPart,'meta',$dummy,$headPart,$meta[$i]);$i++) { /*nothing*/ }
		for($i=0;isset($meta[$i]);$i++) {
			$meta[$i] = t3lib_div::get_tag_attributes($meta[$i]);
			if(stristr($meta[$i]['name'],'keywords')) $contentArr['keywords'].=','.$meta[$i]['content'];
			if(stristr($meta[$i]['name'],'description')) $contentArr['description'].=','.$meta[$i]['content'];
		}

		$this->typoSearchTags($contentArr['body']);

		# get rid of unwanted sections (ie. scripting and style stuff) in body
		$tagList = explode(',',$this->excludeSections);
		reset($tagList);
		while(list(,$tag)=each($tagList)) {
			while($this->embracingTags($contentArr['body'],$tag,$dummy,$contentArr['body'],$dummy2));
		}

		# remove tags, but first make sure we don't concatenate words by doing it
		$contentArr['body'] = str_replace('<',' <',$contentArr['body']);
		$contentArr['body'] = trim(strip_tags($contentArr['body']));

		$contentArr['keywords'] = trim($contentArr['keywords']);
		$contentArr['description'] = trim($contentArr['description']);
		# ta-dah!
		return $contentArr;
	}

	/**
	 * Splits non-HTML content
	 *
	 * @param	[type]		$content: ...
	 * @return	[type]		...
	 */
	function splitRegularContent($content) {
		$contentArr = $this->defaultContentArray;
		$contentArr['body'] = $content;

		return $contentArr;
	}

	/**
	 * Processing words in the array from split*Content -functions
	 *
	 * @param	[type]		$contentArr: ...
	 * @return	[type]		...
	 */
	function procesWordsInArrays($contentArr)	{

		# split all parts to words
		reset($contentArr);
		while(list($key,)=each($contentArr)) {
			if (function_exists('html_entity_decode'))		$contentArr[$key] = html_entity_decode($contentArr[$key]);
			$contentArr[$key] = $this->strtolower_all($contentArr[$key]);
			$this->split2words($contentArr[$key]);
		}

		# for title, keywords, and description we don't want duplicates
		$contentArr['title'] = array_unique($contentArr['title']);
		$contentArr['keywords'] = array_unique($contentArr['keywords']);
		$contentArr['description'] = array_unique($contentArr['description']);
		return $contentArr;
	}

	/**
	 * Returns bodyDescription
	 *
	 * @param	[type]		$contentArr: ...
	 * @return	[type]		...
	 */
	function bodyDescription($contentArr)	{
		# Setting description
		$maxL = t3lib_div::intInRange($this->conf['index_descrLgd'],0,255,200);
		if ($maxL)	{
			if (function_exists('html_entity_decode'))		$bodyDescription = html_entity_decode(trim($contentArr['body']));
			$bodyDescription = implode(' ',split('[[:space:],]+',substr($bodyDescription,0,$maxL*2)));	// Takes the double lenght first, because whitespace may be removed and thus shorten the string more yet.
			$bodyDescription=substr($bodyDescription,0,$maxL);
		}
		return $bodyDescription;
	}

	/**
	 * extract links and if indexable media is found, it is indexed
	 *
	 * @param	[type]		$content: ...
	 * @return	[type]		...
	 */
	function extractLinks($content) {
		$extract = t3lib_div::makeInstance('t3lib_htmlmail');
		$extract->extractHtmlInit($content,'');
		$extract->extractHyperLinks();
#debug($extract->theParts['html']['hrefs']);
		if (is_array($extract->theParts['html']['hrefs']))	{
			reset($extract->theParts['html']['hrefs']);
			while(list(,$linkInfo)=each($extract->theParts['html']['hrefs']))	{
				$linkInfo['ref'] = t3lib_div::htmlspecialchars_decode($linkInfo['ref']);
#debug($linkInfo['ref'],1);
				if (strstr($linkInfo['ref'],'?') && strstr($linkInfo['ref'],'jumpurl='))	{
					$qParts = parse_url($linkInfo['ref']);
#debug($qParts);
					$theJumpurlFile = $this->getJumpurl($qParts['query']);
//					debug($theJumpurlFile);
					if ($theJumpurlFile && @is_file($theJumpurlFile))	{
	//					debug($theJumpurlFile);
						$this->indexRegularDocument($theJumpurlFile);
					}
				} elseif (@is_file($linkInfo['ref']))	{
					$this->indexRegularDocument($linkInfo['ref']);
				}
			}
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$query: ...
	 * @return	[type]		...
	 */
	function getJumpurl($query)	{
		$res = parse_str($query);
#		debug(array($res),'getJumpurl');

		return $jumpurl;
	}

	/**
	 * Splitting PDF info
	 *
	 * @param	[type]		$pdfInfoArray: ...
	 * @return	[type]		...
	 */
	function splitPdfInfo($pdfInfoArray)	{
		$res = array();
		if (is_array($pdfInfoArray))	{
			reset($pdfInfoArray);
			while(list(,$line)=each($pdfInfoArray))	{
				$parts = explode(':',$line,2);
				if (count($parts)>1 && trim($parts[0]))	{
					$res[strtolower(trim($parts[0]))] = trim($parts[1]);
				}
			}
		}
		return $res;
	}

	/**
	 * Indexing a regular document given as $file (relative to PATH_site, local file)
	 *
	 * @param	[type]		$file: ...
	 * @return	[type]		...
	 */
	function indexRegularDocument($file)	{
			// init
		$fI=pathinfo($file);
		$ext = strtolower($fI['extension']);
		$absFile = PATH_site.$file;
#debug($file);
			//
		if (@is_file($absFile) && $this->supportedExtensions[$ext])	{
			$mtime = filemtime($absFile);
			$cParts = $this->fileContentParts($ext,$absFile);
//			debug($cParts);
			reset($cParts);
			while(list(,$cPKey)=each($cParts))	{
				$GLOBALS['TT']->push('Index: '.str_replace('.','_',basename($file)).($cPKey?'#'.$cPKey:''),'');
				$Pstart = t3lib_div::milliseconds();
				$subinfo=array('key'=>$cPKey);
				$phash_arr = $this->setExtHashes($file,$subinfo);
//				debug($phash_arr);

				$check = $this->checkMtimeTstamp($mtime, $this->tstamp_maxAge, $this->tstamp_minAge, $phash_arr['phash']);
				if ($check > 0)	{
					$GLOBALS['TT']->setTSlogMessage('Indexing needed, reason: '.$this->reasons[$check],1);
							// Divide into title,keywords,description and body:
					$GLOBALS['TT']->push('Split content','');
						$contentParts = $this->readFileContent($ext,$absFile,$cPKey);
#debug($contentParts);
					$GLOBALS['TT']->pull();
					if (is_array($contentParts))	{
							// Calculating a hash over what is to be the actual content. (see indexTypo3PageContent())
						$content_md5h = $this->md5inthash(implode($contentParts,''));

						if ($this->checkExternalDocContentHash($phash_arr['phash_grouping'], $content_md5h))	{
									// Splitting words
							$GLOBALS['TT']->push('Extract words from content','');
								$splitInWords = $this->procesWordsInArrays($contentParts);
							$GLOBALS['TT']->pull();

									// Analyse the indexed words.
							$GLOBALS['TT']->push('Analyse the extracted words','');
								$indexArr = $this->indexAnalyze($splitInWords);
							$GLOBALS['TT']->pull();

									// Submitting page (phash) record
							$GLOBALS['TT']->push('Submitting page','');
								$size=filesize($absFile);
								$ctime=filemtime($absFile);	// Unfortunately I cannot determine WHEN a file is originally made - so I must return the modification time...
								$this->submitFilePage($phash_arr,$file,$subinfo,$ext,$mtime,$ctime,$size,$content_md5h,$contentParts);
							$GLOBALS['TT']->pull();

									// Check words and submit to word list if not there
							$GLOBALS['TT']->push('Check word list and submit words','');
								$this->checkWordList($indexArr);
								$this->submitWords($indexArr,$phash_arr['phash']);
							$GLOBALS['TT']->pull();

								// Set parsetime
							$this->updateParsetime($phash_arr['phash'],t3lib_div::milliseconds()-$Pstart);
						} else {
							$this->updateTstamp($phash_arr['phash'],$mtime);	// Update the timestamp
							$GLOBALS['TT']->setTSlogMessage('Indexing not needed, the contentHash, '.$content_md5h.', has not changed. Timestamp updated.');
						}
					} else {
						$GLOBALS['TT']->setTSlogMessage('Could not index file! Unsupported extension.');
					}
				} else {
					$GLOBALS['TT']->setTSlogMessage('Indexing not needed, reason: '.$this->reasons[$check]);
				}
					// Checking and setting sections:
	#			$this->submitFile_grlist($phash_arr['phash']);	// Setting a gr_list record if there is none already (set for default fe_group)
				$this->submitFile_section($phash_arr['phash']);		// Setting a section-record for the file. This is done also if the file is not indexed. Notice that section records are deleted when the page is indexed.
				$GLOBALS['TT']->pull();
			}
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$ext: ...
	 * @param	[type]		$absFile: ...
	 * @param	[type]		$cPKey: ...
	 * @return	[type]		...
	 */
	function readFileContent($ext,$absFile,$cPKey)	{
		switch ($ext)	{
			case 'pdf':
				if ($this->app['pdfinfo'])	{
#debug($this->app);
						// Getting pdf-info:
					$cmd = $this->app['pdfinfo'].' '.$absFile;
					exec($cmd,$res);
					$pdfInfo=$this->splitPdfInfo($res);

					if (intval($pdfInfo['pages']))	{
						list($low,$high) = explode('-',$cPKey);

							// Get pdf content:
						$tempFileName = t3lib_div::tempnam('Typo3_indexer');		// Create temporary name
						@unlink ($tempFileName);	// Delete if exists, just to be safe.
						$cmd = $this->app['pdftotext'].' -f '.$low.' -l '.$high.' -q '.$absFile.' '.$tempFileName;
	//					debug($cmd,1);
						exec($cmd,$res);
						if (@is_file($tempFileName))	{
							$content = t3lib_div::getUrl($tempFileName);
							unlink($tempFileName);
						} else {
							$GLOBALS['TT']->setTSlogMessage('PDFtoText Failed on this document: '.$absFile.". Maybe the PDF file is locked for printing or encrypted.",2);
						}
						$contentArr = $this->splitRegularContent($content);
					}
				}
			break;
			case 'doc':
				if ($this->app['catdoc'])	{
					$cmd = $this->app['catdoc'].' '.$absFile;
					exec($cmd,$res);
					$content = implode(chr(10),$res);
					$contentArr = $this->splitRegularContent($content);
				}
			break;
			case 'txt':
				$content = t3lib_div::getUrl($absFile);
				$contentArr = $this->splitRegularContent($content);
			break;
			case 'html':
			case 'htm':
				$fileContent = t3lib_div::getUrl($absFile);
				$contentArr = $this->splitHTMLContent($fileContent);
			break;
			default:
				return false;
			break;
		}
			// If no title (and why should there be...) then the file-name is set as title. This will raise the hits considerably if the search matches the document name.
		if (!$contentArr['title'])	{
			$contentArr['title']=str_replace('_',' ',basename($absFile));	// Substituting "_" for " " because many filenames may have this instead of a space char.
		}
		return $contentArr;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$ext: ...
	 * @param	[type]		$absFile: ...
	 * @return	[type]		...
	 */
	function fileContentParts($ext,$absFile)	{
		$cParts=array(0);
		switch ($ext)	{
			case 'pdf':
					// Getting pdf-info:
				$cmd = $this->app['pdfinfo'].' '.$absFile;
				exec($cmd,$res);
				$pdfInfo=$this->splitPdfInfo($res);
			//	debug($pdfInfo);

				if (intval($pdfInfo['pages']))	{
					$cParts=array();
						// Calculate mode
						// Calculate mode
					if ($this->pdf_mode>0)	{
						$iter=ceil($pdfInfo['pages']/$this->pdf_mode);
					} else {
						$iter=t3lib_div::intInRange(abs($this->pdf_mode),1,$pdfInfo['pages']);
					}
					for ($a=0;$a<$iter;$a++)	{
						$low=floor($a*($pdfInfo['pages']/$iter))+1;
						$high=floor(($a+1)*($pdfInfo['pages']/$iter));
						$cParts[]=$low.'-'.$high;
					}
				}
			break;
		}
		return $cParts;
	}


	/**
	 * Finds first occurence of embracing tags and returns the embraced content and the original string with
	 * the tag removed in the two passed variables. Returns false if no match found. ie. useful for finding
	 * <title> of document or removing <script>-sections
	 *
	 * @param	[type]		$string: ...
	 * @param	[type]		$tagName: ...
	 * @param	[type]		$tagContent: ...
	 * @param	[type]		$stringAfter: ...
	 * @param	[type]		$paramList: ...
	 * @return	[type]		...
	 */
	function embracingTags($string,$tagName,&$tagContent,&$stringAfter,&$paramList) {
		$endTag = '</'.$tagName.'>';
		$startTag = '<'.$tagName;
		$isTagInText = stristr($string,$startTag);		// stristr used because we want a case-insensitive search for the tag.
		if(!$isTagInText) return false;	// if the tag was not found, return false

		list($paramList,$isTagInText) = explode('>',substr($isTagInText,strlen($startTag)),2);
		$afterTagInText = stristr($isTagInText,$endTag);
		if ($afterTagInText)	{
			$tagContent = substr($isTagInText,0,-strlen($afterTagInText));
			$stringAfter = substr($afterTagInText,strlen($endTag));
		} else {	// If there was no ending tag, the tagContent is blank and anything after the tag it self is returned.
			$tagContent='';
			$stringAfter = $isTagInText;
		}
//		debug(array($tagContent,$stringAfter));
		return true;
	}

	/**
	 * Analyzes content to use for indexing,
	 * the parameter must be an array with the keys title,keywords,description and body, which all contain an array of words.
	 *
	 * @param	[type]		$content: ...
	 * @return	[type]		...
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
	 * @param	[type]		$$retArr: ...
	 * @param	[type]		$content: ...
	 * @param	[type]		$key: ...
	 * @param	[type]		$offset: ...
	 * @return	[type]		...
	 */
	function analyzeHeaderinfo(&$retArr,$content,$key,$offset) {
		reset($content[$key]);
		while(list(,$val)=each($content[$key]))  {
			$val = substr($val,0,30);	// Max 30 - because the baseword varchar IS 30. This MUST be the same.
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
	 * @param	[type]		$$retArr: ...
	 * @param	[type]		$content: ...
	 * @return	[type]		...
	 */
	function analyzeBody(&$retArr,$content) {
		reset($content['body']);
		while(list($key,$val)=each($content['body']))  {
			$val = substr($val,0,30);	// Max 30 - because the baseword varchar IS 30. This MUST be the same.
			if(!isset($retArr[$val])) {
				$retArr[$val]['first']=$key;
				$retArr[$val]['hash'] = hexdec(substr(md5($val),0,7));
				$retArr[$val]['metaphone'] = $this->metaphone($val);
			}
			$retArr[$val]['count'] = $retArr[$val]['count']+1;
			$this->wordcount++;
		}
	}

	/**
	 * Removes content that shouldn't be indexed according to TYPO3SEARCH-tags.
	 *
	 * @param	[type]		$$body: ...
	 * @return	[type]		...
	 */
	function typoSearchTags(&$body) {
		$expBody = explode('<!--TYPO3SEARCH_',$body);
#debug($expBody);
		if(count($expBody)>1) {
			$body = '';
			reset($expBody);
			while(list(,$val)=each($expBody)) {
				$part = explode('-->',$val,2);
				if(trim($part[0])=='begin') {
					$body .= $part[1];
					$prev = '';
				} elseif(trim($part[0])=='end') {
					$body .= $prev;
				} else {
					$prev = $val;
				}
#debug($part);
			}
#debug(array($body));
			return true;
		} else {
			return false;
		}
	}














	/**********************************
	 *
	 * Words
	 *
	 **********************************/

	/**
	 * Splits the incoming string into words
	 * The $string parameter is a reference and will be made into an array!
	 *
	 * @param	[type]		$$string: ...
	 * @return	[type]		...
	 */
	function split2words(&$string) {
		$words = split('[[:space:],]+',$string);
		$reg='['.quotemeta('().,_?!:-').']*';
		$reg='[^[:alnum:]'.$this->convChars[0].$this->convChars[1].']*';

#debug($words);
#debug(array($string));
		reset($words);
		$matches=array();
		while(list(,$w)=each($words))	{
			$w=trim($w);
			$w=ereg_replace('^'.$reg,'',$w);
			$w=ereg_replace($reg.'$','',$w);
			if ($this->wordOK($w))	{$matches[]=$w;}
		}
#		debug($matches);
		$string =$matches;


		/*
		preg_match_all("/\b(\w[\w']*\w+|\w+)\b/", $string ,$matches);
		$string = $matches[0];
		*/
	}

	/**
	 * Checks if a word is supposed to be indexed.
	 * This assessment includes that the word must be between 1 and 50 chars.
	 * The more exotic feature is that only 30 percent of the word must be non-alphanum characters. This is to exclude binary nonsense. This is done with a little trick it's counted how many chars are converted with a rawurlencode command. THis is not really an exact method, but I guess it's fast.
	 *
	 * @param	[type]		$w: ...
	 * @return	[type]		...
	 */
	function wordOK($w)	{
		if ($w && strlen($w)>1 && strlen($w)<50)	{
			if (rawurlencode($w)!=$w)	{
				$fChars = count(explode('%',rawurlencode($w)))-1;
				$rel = round($fChars/strlen($w)*100);
				return $rel<30 ? 1 : 0;		// Max 30% strange chars!
			} else {
				return 1;
			}
		}
	}

	/**
	 * metaphone
	 *
	 * @param	[type]		$word: ...
	 * @return	[type]		...
	 */
	function metaphone($word) {
		$tmp = metaphone($word);
		if($tmp=='') $ret=0; else $ret=hexdec(substr(md5($tmp),0,7));
		return $ret;
	}

	/**
	 * Converts string-to-lower including special characters.
	 *
	 * @param	[type]		$str: ...
	 * @return	[type]		...
	 */
	function strtolower_all($str)	{
		return strtolower(strtr($str, $this->convChars[0], $this->convChars[1]));
	}















	/********************************
	 *
	 * SQL Helper functions
	 *
	 *******************************/

	/**
	 * maps frequency from a real number in [0;1] to an integer in [0;$this->freqRange] with anything above $this->freqMax as 1
	 * and back.
	 *
	 * @param	[type]		$freq: ...
	 * @return	[type]		...
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

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$fieldArr: ...
	 * @return	[type]		...
	 */
	function getRootLineFields(&$fieldArr)	{
		$rl = $this->rootLine;

		$fieldArr['rl0'] = intval($rl[0]['uid']);
		$fieldArr['rl1'] = intval($rl[1]['uid']);
		$fieldArr['rl2'] = intval($rl[2]['uid']);

		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['addRootLineFields']))	{
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['addRootLineFields'] as $fieldName => $rootLineLevel)	{
				$fieldArr[$fieldName] = intval($rl[$rootLineLevel]['uid']);
			}
		}
	}














	/********************************
	 *
	 * SQL Helper functions
	 *
	 *******************************/

	/**
	 * Removes ALL data regarding a certain indexed phash-row
	 *
	 * @param	[type]		$phashList: ...
	 * @param	[type]		$clearPageCache: ...
	 * @return	[type]		...
	 */
	function removeIndexedPhashRow($phashList,$clearPageCache=1)	{
		$phashRows=t3lib_div::trimExplode(',',$phashList,1);
		while(list(,$phash)=each($phashRows))	{
			$phash = intval($phash);
			if ($phash>0)	{

				if ($clearPageCache)	{
						// Clearing page cache:
					$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('page_id', 'index_section', 'phash='.intval($phash));
					if ($GLOBALS['TYPO3_DB']->sql_num_rows($res))	{
						$idList = array();
						while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
							$idList[] = $row['page_id'];
						}
						$GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_pages', 'page_id IN ('.implode(',',$GLOBALS['TYPO3_DB']->cleanIntArray($idList)).')');
					}
				}

					// Removing old registrations for all tables.
				$tableArr = explode(',','index_phash,index_rel,index_section,index_fulltext,index_grlist');
				foreach($tableArr as $table)	{
					$GLOBALS['TYPO3_DB']->exec_DELETEquery($table, 'phash="'.$GLOBALS['TYPO3_DB']->quoteStr($phash, $table).'"');
				}

					// Did not remove any index_section records for external files where phash_t3 points to this hash!
#debug('DELETE: '.$phash,1);
			}
		}
	}

	/**
	 * Check the mtime / tstamp of the currently indexed page/file (based on phash)
	 * Return positive integer if the page needs to being indexed!
	 *
	 * @param	integer		mtime value to test against limits and indexed page.
	 * @param	integer		Maximum age in seconds.
	 * @param	integer		Minimum age in seconds.
	 * @param	integer		"phash" used to select any already indexed page to see what its mtime is.
	 * @return	integer		Result integer: Generally: <0 = No indexing, >0 = Do indexing (see $this->reasons): -2) Min age was NOT exceed and so indexing cannot occur.  -1) Mtimes matched so no need to reindex page. 0) N/A   1) Max age exceeded, page must be indexed again.   2) mtime of indexed page doesn't match mtime given for current content and we must index page.  3) No mtime was set, so we will index...  4) No indexed page found, so of course we will index.
	 */
	function checkMtimeTstamp($mtime,$maxAge,$minAge,$phash)	{

			// Select indexed page:
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('item_mtime,tstamp', 'index_phash', 'phash='.intval($phash));
		$out = 0;

			// If there was an indexing of the page...:
		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			if ($maxAge && ($row['tstamp']+$maxAge)<time())	{		// If min age is exceeded, index the page
				$out = 1;
			} else {
				if (!$minAge || ($row['tstamp']+$minAge)<time())	{	// if minAge is not set or if minAge is exceeded, consider at mtime
					if ($mtime)	{		// It mtime is set, then it's tested. If not, the page must clearly be indexed.
						if ($row['item_mtime'] != $mtime)	{	// And if mtime is different from the index_phash mtime, it's about time to re-index.
							$out = 2;
						} else {
							$out = -1;
							$this->updateTstamp($phash);	// Update the timestatmp
							$GLOBALS['TT']->setTSlogMessage('Mtime matched, timestamp updated.',1);
						}
					} else {$out = 3;	}
				} else {$out = -2;}
			}
		} else {$out = 4;}	// No indexing found.
		return $out;
	}

	/**
	 * Check if an grlist-entry for this hash exists and if not so, write one.
	 *
	 * @param	[type]		$phash: ...
	 * @param	[type]		$phash_x: ...
	 * @return	[type]		...
	 */
	function update_grlist($phash,$phash_x)	{
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('phash', 'index_grlist', 'phash='.intval($phash).' AND hash_gr_list='.$this->md5inthash($this->pObj->gr_list));
		if (!$GLOBALS['TYPO3_DB']->sql_num_rows($res))	{
			$this->submit_grlist($phash,$phash_x);
			$GLOBALS['TT']->setTSlogMessage("Inserted gr_list '".$this->pObj->gr_list."' for phash '".$phash."'",1);
		}
	}

	/**
	 * @param	[type]		$phash_x: ...
	 * @return	[type]		...
	 */
	function is_grlist_set($phash_x)	{
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('phash_x', 'index_grlist', 'phash_x='.intval($phash_x));
		return $GLOBALS['TYPO3_DB']->sql_num_rows($res);
	}

	/**
	 * Check content hash
	 * Returns true if the page needs to be indexed (that is, there was no result)
	 *
	 * @return	[type]		...
	 */
	function checkContentHash()	{
			// With this query the page will only be indexed if it's content is different from the same "phash_grouping" -page.
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('phash', 'index_phash AS A', 'A.phash_grouping='.intval($this->hash['phash_grouping']).' AND A.contentHash='.intval($this->content_md5h));
		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			return $row;
		}
		return 1;
	}

	/**
	 * Removes any indexed pages with userlogins which has the same contentHash
	 *
	 * @return	[type]		...
	 */
	function removeLoginpagesWithContentHash()	{
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'index_phash AS A,index_grlist AS B', '
					A.phash=B.phash
					AND A.phash_grouping='.intval($this->hash['phash_grouping']).'
					AND B.hash_gr_list!='.$this->md5inthash($this->defaultGrList).'
					AND A.contentHash='.intval($this->content_md5h));
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$GLOBALS['TT']->setTSlogMessage("The currently indexed page was indexed under no user-login and apparently this page has been indexed under login conditions earlier, but with the SAME content. Therefore the old similar page with phash='".$row['phash']."' are now removed.",1);
			$this->removeOldIndexedPages($row['phash']);
		}
	}

	/**
	 * Removes records for the indexed page, $phash
	 *
	 * @param	[type]		$phash: ...
	 * @return	[type]		...
	 */
	function removeOldIndexedPages($phash)	{
			// Removing old registrations for all tables. Because the pages are TYPO3 pages there can be nothing else than 1-1 relations here.
		$tableArr = explode(',','index_phash,index_section,index_grlist,index_fulltext');
		foreach($tableArr as $table)	{
			$GLOBALS['TYPO3_DB']->exec_DELETEquery($table, 'phash="'.$GLOBALS['TYPO3_DB']->quoteStr($phash, $table).'"');
		}
			// Removing all index_section records with hash_t3 set to this hash (this includes such records set for external media on the page as well!). The re-insert of these records are done in indexRegularDocument($file).
		$GLOBALS['TYPO3_DB']->exec_DELETEquery('index_section', 'phash_t3="'.$GLOBALS['TYPO3_DB']->quoteStr($phash, 'index_section').'"');
	}

	/**
	 * Check content hash for external documents
	 * Returns true if the document needs to be indexed (that is, there was no result)
	 *
	 * @param	[type]		$hashGr: ...
	 * @param	[type]		$content_md5h: ...
	 * @return	[type]		...
	 */
	function checkExternalDocContentHash($hashGr,$content_md5h)	{
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'index_phash AS A', 'A.phash_grouping='.intval($hashGr).' AND A.contentHash='.intval($content_md5h));
		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			return 0;
		}
		return 1;
	}

	/**
	 * Update tstamp
	 *
	 * @param	[type]		$phash: ...
	 * @param	[type]		$mtime: ...
	 * @return	[type]		...
	 */
	function updateTstamp($phash,$mtime=0)	{
		$updateFields = array(
			'tstamp' => time()
		);
		if ($mtime)	{ $updateFields['item_mtime'] = intval($mtime); }

		$GLOBALS['TYPO3_DB']->exec_UPDATEquery('index_phash', 'phash='.intval($phash), $updateFields);
	}

	/**
	 * Update parsetime
	 *
	 * @param	[type]		$phash: ...
	 * @param	[type]		$parsetime: ...
	 * @return	[type]		...
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
	 * @return	[type]		...
	 */
	function updateRootline()	{

		$updateFields = array();
		$this->getRootLineFields($updateFields);

		$GLOBALS['TYPO3_DB']->exec_UPDATEquery('index_section', 'page_id='.intval($this->pObj->id), $updateFields);
	}












	/********************************
	 *
	 * SQL; Inserting in database
	 *
	 *******************************/

	/**
	 * Updates db with information about the page
	 *
	 * @return	[type]		...
	 */
	function submitPage()	{
		$this->removeOldIndexedPages($this->hash['phash']);

			// setting new
		$fields = array(
			'phash' => $this->hash['phash'],
			'phash_grouping' => $this->hash['phash_grouping'],
			'cHashParams' => serialize($this->cHashParams),
			'contentHash' => $this->content_md5h,
			'data_page_id' => $this->pObj->id,
			'data_page_reg1' => $this->pObj->page_cache_reg1,
			'data_page_type' => $this->pObj->type,
			'data_page_mp' => $this->pObj->MP,
			'gr_list' => $this->pObj->gr_list,
			'item_type' => 0,	// TYPO3 page
			'item_title' => $this->contentParts['title'],
			'item_description' => $this->bodyDescription($this->contentParts),
			'item_mtime' => $this->mtime,
			'item_size' => strlen($this->pObj->content),
			'tstamp' => time(),
			'crdate' => time(),
			'item_crdate' => $this->pObj->page['crdate'],	// Creation date of page
			'sys_language_uid' => $this->pObj->sys_language_uid	// Sys language uid of the page. Should reflect which language it DOES actually display!
		);
		$GLOBALS['TYPO3_DB']->exec_INSERTquery('index_phash', $fields);

		// ************************
		// PROCESSING index_section
		// ************************
		$this->submit_section($this->hash['phash'],$this->hash['phash']);

		// ************************
		// PROCESSING index_grlist
		// ************************
		$this->submit_grlist($this->hash['phash'],$this->hash['phash']);

		// ************************
		// PROCESSING index_fulltext
		// ************************
		$fields = array(
			'phash' => $this->hash['phash'],
			'fulltextdata' => implode($this->contentParts,' ')
		);
		$GLOBALS['TYPO3_DB']->exec_INSERTquery('index_fulltext', $fields);
	}

	/**
	 * Stores gr_list
	 *
	 * @param	[type]		$hash: ...
	 * @param	[type]		$phash_x: ...
	 * @return	[type]		...
	 */
	function submit_grlist($hash,$phash_x)	{
			// Setting the gr_list record
		$fields = array(
			'phash' => $hash,
			'phash_x' => $phash_x,
			'hash_gr_list' => $this->md5inthash($this->pObj->gr_list),
			'gr_list' => $this->pObj->gr_list
		);
		$GLOBALS['TYPO3_DB']->exec_INSERTquery('index_grlist', $fields);
	}

	/**
	 * Stores section
	 *
	 * @param	[type]		$hash: ...
	 * @param	[type]		$hash_t3: ...
	 * @return	[type]		...
	 */
	function submit_section($hash,$hash_t3)	{
		$fields = array(
			'phash' => $hash,
			'phash_t3' => $hash_t3,
			'page_id' => intval($this->pObj->id)
		);

		$this->getRootLineFields($fields);

		$GLOBALS['TYPO3_DB']->exec_INSERTquery('index_section', $fields);
	}

	/**
	 * Updates db with information about the file
	 *
	 * @param	[type]		$hash: ...
	 * @param	[type]		$file: ...
	 * @param	[type]		$subinfo: ...
	 * @param	[type]		$ext: ...
	 * @param	[type]		$mtime: ...
	 * @param	[type]		$ctime: ...
	 * @param	[type]		$size: ...
	 * @param	[type]		$content_md5h: ...
	 * @param	[type]		$contentParts: ...
	 * @return	[type]		...
	 */
	function submitFilePage($hash,$file,$subinfo,$ext,$mtime,$ctime,$size,$content_md5h,$contentParts)	{
			// Removing old registrations for tables.
		$tableArr = explode(',','index_phash,index_fulltext,index_grlist');
		foreach($tableArr as $table)	{
			$GLOBALS['TYPO3_DB']->exec_DELETEquery($table, 'phash="'.$GLOBALS['TYPO3_DB']->quoteStr($hash['phash'], $table).'"');
		}
			// setting new
		$fields = array(
			'phash' => $hash['phash'],
			'phash_grouping' => $hash['phash_grouping'],
			'cHashParams' => serialize($subinfo),
			'contentHash' => $content_md5h,
			'data_filename' => $file,
			'item_type' => intval($this->Itypes[$ext]) ? intval($this->Itypes[$ext]) : -1,
			'item_title' => trim($contentParts['title']) ? $contentParts['title'] : basename($file),
			'item_description' => $this->bodyDescription($contentParts),
			'item_mtime' => $mtime,
			'item_size' => $size,
			'item_crdate' => $ctime,
			'tstamp' => time(),
			'crdate' => time(),
			'gr_list' => $this->pObj->gr_list
		);
		$GLOBALS['TYPO3_DB']->exec_INSERTquery('index_phash', $fields);

		// ************************
		// PROCESSING index_fulltext
		// ************************
		$fields = array(
			'phash' => $hash['phash'],
			'fulltextdata' => implode($contentParts,' ')
		);
		$GLOBALS['TYPO3_DB']->exec_INSERTquery('index_fulltext', $fields);
	}

	/**
	 * Stores file gr_list for a file IF it does not exist
	 *
	 * @param	[type]		$hash: ...
	 * @return	[type]		...
	 */
	function submitFile_grlist($hash)	{
		// ************************
		// PROCESSING index_grlist
		// ************************
			// Testing if there is a gr_list record for a non-logged in user and if so, there is no need to place another one.
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('phash', 'index_grlist', 'phash='.intval($hash).' AND (hash_gr_list='.$this->md5inthash($this->defaultGrList).' OR hash_gr_list='.$this->md5inthash($this->pObj->gr_list).')');
		if (!$GLOBALS['TYPO3_DB']->sql_num_rows($res))	{
			$this->submit_grlist($hash,$hash);
		}
	}

	/**
	 * Stores file section for a file IF it does not exist
	 *
	 * @param	[type]		$hash: ...
	 * @return	[type]		...
	 */
	function submitFile_section($hash)	{
		// ************************
		// PROCESSING index_grlist
		// ************************
			// Testing if there is a gr_list record for a non-logged in user and if so, there is no need to place another one.
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('phash', 'index_section', 'phash='.intval($hash).' AND page_id='.intval($this->pObj->id));
		if (!$GLOBALS['TYPO3_DB']->sql_num_rows($res))	{
			$this->submit_section($hash,$this->hash['phash']);
		}
	}

	/**
	 * Adds new words to db
	 *
	 * @param	[type]		$wl: ...
	 * @return	[type]		...
	 */
	function checkWordList($wl) {
		reset($wl);
		$phashArr=array();
		while(list($key,)=each($wl)) {
			$phashArr[] = $wl[$key]['hash'];
		}
		if (count($phashArr))	{
			$cwl = implode(',',$phashArr);
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('baseword', 'index_words', 'wid IN ('.$cwl.')');

			if($GLOBALS['TYPO3_DB']->sql_num_rows($res)!=count($wl)) {
				$GLOBALS['TT']->setTSlogMessage('Inserting words: '.(count($wl)-$GLOBALS['TYPO3_DB']->sql_num_rows($res)),1);
				while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					unset($wl[$row['baseword']]);
				}

				reset($wl);
				while(list($key,$val)=each($wl)) {
					$insertFields = array(
						'wid' => $val['hash'],
						'baseword' => $key,
						'metaphone' => $val['metaphone']
					);
						// A duplicate-key error will occur here if a word is NOT unset in the unset() line. However as long as the words in $wl are NOT longer as 30 chars (the baseword varchar is 30 characters...) this is not a problem.
					$GLOBALS['TYPO3_DB']->exec_INSERTquery('index_words', $insertFields);
				}
			}
		}
	}

	/**
	 * Submits information about words on the page to the db
	 *
	 * @param	[type]		$wl: ...
	 * @param	[type]		$phash: ...
	 * @return	[type]		...
	 */
	function submitWords($wl,$phash) {
		$GLOBALS['TYPO3_DB']->exec_DELETEquery('index_rel', 'phash="'.$GLOBALS['TYPO3_DB']->quoteStr($phash, 'index_rel').'"');

		foreach($wl as $val)	{
			$insertFields = array(
				'phash' => $phash,
				'wid' => $val['hash'],
				'count' => $val['count'],
				'first' => $val['first'],
				'freq' => $this->freqMap(($val['count']/$this->wordcount)),
				'flags' => $val['cmp']
			);

			$GLOBALS['TYPO3_DB']->exec_INSERTquery('index_rel', $insertFields);
		}
	}

















	/********************************
	 *
	 * Hashing
	 *
	 *******************************/

	/**
	 * Get search hash, T3 pages
	 *
	 * @return	[type]		...
	 */
	function setT3Hashes()	{
			//  Set main array:
		$hArray = array(
			'id' => $this->pObj->id,
			'type' => $this->pObj->type,
			'sys_lang' => $this->pObj->sys_language_uid,
			'MP' => $this->pObj->MP,
			'cHash' => $this->cHashParams
		);
			// Set grouping hash:
		$this->hash['phash_grouping'] = $this->md5inthash(serialize($hArray));
			// Add gr_list and set plain phash
		$hArray['gr_list']=$this->pObj->gr_list;
		$this->hash['phash'] = $this->md5inthash(serialize($hArray));
	}

	/**
	 * Get search hash, external files
	 *
	 * @param	[type]		$file: ...
	 * @param	[type]		$subinfo: ...
	 * @return	[type]		...
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
	 *
	 * @param	[type]		$str: ...
	 * @return	[type]		...
	 */
	function md5inthash($str)	{
			// Using 7 instead of 8 just because that makes the integers lower than 32 bit (28 bit) and so they does not interfere with UNSIGNED integers or PHP-versions which has varying output from the hexdec function.
			// NOTICE: This must be changed a number of other places as well!
		return hexdec(substr(md5($str),0,7));
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/indexed_search/class.indexer.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/indexed_search/class.indexer.php']);
}
?>
