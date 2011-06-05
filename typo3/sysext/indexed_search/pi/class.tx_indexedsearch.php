<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2001-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Index search frontend
 *
 * Creates a searchform for indexed search. Indexing must be enabled
 * for this to make sense.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @co-author	Christian Jul Jensen <christian@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  123: class tx_indexedsearch extends tslib_pibase
 *  168:     function main($content, $conf)
 *  200:     function initialize()
 *  413:     function getSearchWords($defOp)
 *  447:     function procSearchWordsByLexer($SWArr)
 *
 *              SECTION: Main functions
 *  491:     function doSearch($sWArr)
 *  549:     function getResultRows($sWArr,$freeIndexUid=-1)
 *  623:     function getResultRows_SQLpointer($sWArr,$freeIndexUid=-1)
 *  647:     function getDisplayResults($sWArr, $resData, $freeIndexUid=-1)
 *  699:     function compileResult($resultRows, $freeIndexUid=-1)
 *
 *              SECTION: Searching functions (SQL)
 *  800:     function getPhashList($sWArr)
 *  901:     function execPHashListQuery($wordSel,$plusQ='')
 *  921:     function sectionTableWhere()
 *  968:     function mediaTypeWhere()
 *  993:     function languageWhere()
 * 1005:     function freeIndexUidWhere($freeIndexUid)
 * 1046:     function execFinalQuery($list,$freeIndexUid=-1)
 * 1189:     function checkResume($row)
 * 1236:     function isDescending($inverse=FALSE)
 * 1250:     function writeSearchStat($sWArr,$count,$pt)
 *
 *              SECTION: HTML output functions
 * 1302:     function makeSearchForm($optValues)
 * 1436:     function renderSelectBoxValues($value,$optValues)
 * 1455:     function printRules()
 * 1474:     function printResultSectionLinks()
 * 1508:     function makeSectionHeader($id, $sectionTitleLinked, $countResultRows)
 * 1529:     function printResultRow($row, $headerOnly=0)
 * 1598:     function pi_list_browseresults($showResultCount=1,$addString='',$addPart='',$freeIndexUid=-1)
 *
 *              SECTION: Support functions for HTML output (with a minimum of fixed markup)
 * 1686:     function prepareResultRowTemplateData($row, $headerOnly)
 * 1740:     function tellUsWhatIsSeachedFor($sWArr)
 * 1774:     function wrapSW($str)
 * 1786:     function renderSelectBox($name,$value,$optValues)
 * 1810:     function makePointerSelector_link($str,$p,$freeIndexUid)
 * 1825:     function makeItemTypeIcon($it,$alt='',$specRowConf)
 * 1867:     function makeRating($row)
 * 1911:     function makeDescription($row,$noMarkup=0,$lgd=180)
 * 1942:     function markupSWpartsOfString($str)
 * 2022:     function makeTitle($row)
 * 2046:     function makeInfo($row,$tmplArray)
 * 2075:     function getSpecialConfigForRow($row)
 * 2099:     function makeLanguageIndication($row)
 * 2142:     function makeAccessIndication($id)
 * 2157:     function linkPage($id,$str,$row=array(),$markUpSwParams=array())
 * 2201:     function getRootLine($id,$pathMP='')
 * 2216:     function getFirstSysDomainRecordForPage($id)
 * 2229:     function getPathFromPageId($id,$pathMP='')
 * 2281:     function getMenu($id)
 * 2300:     function multiplePagesType($item_type)
 * 2310:     function utf8_to_currentCharset($str)
 * 2320:     function &hookRequest($functionName)
 *
 * TOTAL FUNCTIONS: 48
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require_once(t3lib_extMgm::extPath('indexed_search').'class.indexer.php');


/**
 * Index search frontend
 *
 * Creates a searchform for indexed search. Indexing must be enabled
 * for this to make sense.
 *
 * @package TYPO3
 * @subpackage tx_indexedsearch
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class tx_indexedsearch extends tslib_pibase {
	var $prefixId = 'tx_indexedsearch';        // Same as class name
	var $scriptRelPath = 'pi/class.tx_indexedsearch.php';    // Path to this script relative to the extension dir.
	var $extKey = 'indexed_search';    // The extension key.

	var $join_pages = 0;	// See document for info about this flag...
	var $defaultResultNumber = 10;

	var $operator_translate_table = Array (		// case-sensitive. Defines the words, which will be operators between words
		Array ('+' , 'AND'),
		Array ('|' , 'OR'),
		Array ('-' , 'AND NOT'),
			// english
#		Array ('AND' , 'AND'),
#		Array ('OR' , 'OR'),
#		Array ('NOT' , 'AND NOT'),
	);

		// Internal variable
	var $wholeSiteIdList = 0;		// Root-page PIDs to search in (rl0 field where clause, see initialize() function)

		// Internals:
	var $sWArr = array();			// Search Words and operators
	var $optValues = array();		// Selector box values for search configuration form
	var $firstRow = Array();		// Will hold the first row in result - used to calculate relative hit-ratings.

	var $cache_path = array();		// Caching of page path
	var $cache_rl = array();		// Caching of root line data
	var $fe_groups_required = array();	// Required fe_groups memberships for display of a result.
	var $domain_records = array();		// Domain records (?)
	var $wSelClauses = array();		// Select clauses for individual words
	var $resultSections = array();		// Page tree sections for search result.
	var $external_parsers = array();	// External parser objects
	var $iconFileNameCache = array();	// Storage of icons....

	/**
	 * Lexer object
	 *
	 * @var tx_indexedsearch_lexer
	 */
	var $lexerObj;

	/**
	 * Indexer object
	 *
	 * @var tx_indexedsearch_indexer
	 */
	var $indexerObj;
	var $templateCode;			// Will hold the content of $conf['templateFile']
	var $hiddenFieldList = 'ext, type, defOp, media, order, group, lang, desc, results';


	/**
	 * Main function, called from TypoScript as a USER_INT object.
	 *
	 * @param	string		Content input, ignore (just put blank string)
	 * @param	array		TypoScript configuration of the plugin!
	 * @return	string		HTML code for the search form / result display.
	 */
	function main($content, $conf)    {

			// Initialize:
		$this->conf = $conf;
		$this->pi_loadLL();
		$this->pi_setPiVarDefaults();

			// Initialize the indexer-class - just to use a few function (for making hashes)
		$this->indexerObj = t3lib_div::makeInstance('tx_indexedsearch_indexer');

			// Initialize:
		$this->initialize();

			// Do search:
			// If there were any search words entered...
		if (is_array($this->sWArr))	{
			$content = $this->doSearch($this->sWArr);
		}

			// Finally compile all the content, form, messages and results:
		$content = $this->makeSearchForm($this->optValues).
			$this->printRules().
			$content;

        return $this->pi_wrapInBaseClass($content);
    }

	/**
	 * Initialize internal variables, especially selector box values for the search form and search words
	 *
	 * @return	void
	 */
	function initialize()	{
		global $TYPO3_CONF_VARS;

			// Initialize external document parsers for icon display and other soft operations
		if (is_array($TYPO3_CONF_VARS['EXTCONF']['indexed_search']['external_parsers']))	{
			foreach ($TYPO3_CONF_VARS['EXTCONF']['indexed_search']['external_parsers'] as $extension => $_objRef)	{
				$this->external_parsers[$extension] = t3lib_div::getUserObj($_objRef);

					// Init parser and if it returns FALSE, unset its entry again:
				if (!$this->external_parsers[$extension]->softInit($extension))	{
					unset($this->external_parsers[$extension]);
				}
			}
		}

			// Init lexer (used to post-processing of search words)
		$lexerObjRef = $TYPO3_CONF_VARS['EXTCONF']['indexed_search']['lexer'] ?
						$TYPO3_CONF_VARS['EXTCONF']['indexed_search']['lexer'] :
						'EXT:indexed_search/class.lexer.php:&tx_indexedsearch_lexer';
		$this->lexerObj = t3lib_div::getUserObj($lexerObjRef);

			// If "_sections" is set, this value overrides any existing value.
		if ($this->piVars['_sections'])		$this->piVars['sections'] = $this->piVars['_sections'];

			// If "_sections" is set, this value overrides any existing value.
		if ($this->piVars['_freeIndexUid']!=='_')		$this->piVars['freeIndexUid'] = $this->piVars['_freeIndexUid'];

			// Add previous search words to current
		if ($this->piVars['sword_prev_include'] && $this->piVars['sword_prev'])	{
			$this->piVars['sword'] = trim($this->piVars['sword_prev']).' '.$this->piVars['sword'];
		}

		$this->piVars['results'] = t3lib_div::intInRange($this->piVars['results'],1,100000,$this->defaultResultNumber);

			// Selector-box values defined here:
		$this->optValues = Array(
			'type' => Array(
				'0' => $this->pi_getLL('opt_type_0'),
				'1' => $this->pi_getLL('opt_type_1'),
				'2' => $this->pi_getLL('opt_type_2'),
				'3' => $this->pi_getLL('opt_type_3'),
				'10' => $this->pi_getLL('opt_type_10'),
				'20' => $this->pi_getLL('opt_type_20'),
			),
			'defOp' => Array(
				'0' => $this->pi_getLL('opt_defOp_0'),
				'1' => $this->pi_getLL('opt_defOp_1'),
			),
			'sections' => Array(
				'0' => $this->pi_getLL('opt_sections_0'),
				'-1' => $this->pi_getLL('opt_sections_-1'),
				'-2' => $this->pi_getLL('opt_sections_-2'),
				'-3' => $this->pi_getLL('opt_sections_-3'),
				// Here values like "rl1_" and "rl2_" + a rootlevel 1/2 id can be added to perform searches in rootlevel 1+2 specifically. The id-values can even be commaseparated. Eg. "rl1_1,2" would search for stuff inside pages on menu-level 1 which has the uid's 1 and 2.
			),
			'freeIndexUid' => Array(
				'-1' => $this->pi_getLL('opt_freeIndexUid_-1'),
				'-2' => $this->pi_getLL('opt_freeIndexUid_-2'),
				'0' => $this->pi_getLL('opt_freeIndexUid_0'),
			),
			'media' => Array(
				'-1' => $this->pi_getLL('opt_media_-1'),
				'0' => $this->pi_getLL('opt_media_0'),
				'-2' => $this->pi_getLL('opt_media_-2'),
			),
			'order' => Array(
				'rank_flag' => $this->pi_getLL('opt_order_rank_flag'),
				'rank_freq' => $this->pi_getLL('opt_order_rank_freq'),
				'rank_first' => $this->pi_getLL('opt_order_rank_first'),
				'rank_count' => $this->pi_getLL('opt_order_rank_count'),
				'mtime' => $this->pi_getLL('opt_order_mtime'),
				'title' => $this->pi_getLL('opt_order_title'),
				'crdate' => $this->pi_getLL('opt_order_crdate'),
			),
			'group' => Array (
				'sections' => $this->pi_getLL('opt_group_sections'),
				'flat' => $this->pi_getLL('opt_group_flat'),
			),
			'lang' => Array (
				-1 => $this->pi_getLL('opt_lang_-1'),
				0 => $this->pi_getLL('opt_lang_0'),
			),
			'desc' => Array (
				'0' => $this->pi_getLL('opt_desc_0'),
				'1' => $this->pi_getLL('opt_desc_1'),
			),
			'results' => Array (
				'10' => '10',
				'20' => '20',
				'50' => '50',
				'100' => '100',
			)
		);

			// Free Index Uid:
		if ($this->conf['search.']['defaultFreeIndexUidList'])	{
			$uidList = t3lib_div::intExplode(',', $this->conf['search.']['defaultFreeIndexUidList']);
			$indexCfgRecords = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,title','index_config','uid IN ('.implode(',',$uidList).')'.$this->cObj->enableFields('index_config'),'','','','uid');

			foreach ($uidList as $uidValue)	{
				if (is_array($indexCfgRecords[$uidValue]))	{
					$this->optValues['freeIndexUid'][$uidValue] = $indexCfgRecords[$uidValue]['title'];
				}
			}
		}

			// Should we use join_pages instead of long lists of uids?
		if ($this->conf['search.']['skipExtendToSubpagesChecking'])	{
			$this->join_pages = 1;
		}

			// Add media to search in:
		if (strlen(trim($this->conf['search.']['mediaList'])))	{
			$mediaList = implode(',', t3lib_div::trimExplode(',', $this->conf['search.']['mediaList'], 1));
		}
		foreach ($this->external_parsers as $extension => $obj)	{
				// Skip unwanted extensions
			if ($mediaList && !t3lib_div::inList($mediaList, $extension))	{ continue; }

			if ($name = $obj->searchTypeMediaTitle($extension))	{
				$this->optValues['media'][$extension] = $this->pi_getLL('opt_sections_'.$extension,$name);
			}
		}

			// Add operators for various languages
			// Converts the operators to UTF-8 and lowercase
		$this->operator_translate_table[] = Array($GLOBALS['TSFE']->csConvObj->conv_case('utf-8',$GLOBALS['TSFE']->csConvObj->utf8_encode($this->pi_getLL('local_operator_AND'), $GLOBALS['TSFE']->renderCharset),'toLower') , 'AND');
		$this->operator_translate_table[] = Array($GLOBALS['TSFE']->csConvObj->conv_case('utf-8',$GLOBALS['TSFE']->csConvObj->utf8_encode($this->pi_getLL('local_operator_OR'), $GLOBALS['TSFE']->renderCharset),'toLower') , 'OR');
		$this->operator_translate_table[] = Array($GLOBALS['TSFE']->csConvObj->conv_case('utf-8',$GLOBALS['TSFE']->csConvObj->utf8_encode($this->pi_getLL('local_operator_NOT'), $GLOBALS['TSFE']->renderCharset),'toLower') , 'AND NOT');

			// This is the id of the site root. This value may be a commalist of integer (prepared for this)
		$this->wholeSiteIdList = intval($GLOBALS['TSFE']->config['rootLine'][0]['uid']);

			// Creating levels for section menu:
			// This selects the first and secondary menus for the "sections" selector - so we can search in sections and sub sections.
		if ($this->conf['show.']['L1sections'])	{
			$firstLevelMenu = $this->getMenu($this->wholeSiteIdList);
			foreach ($firstLevelMenu as $kk => $mR) {
					// @TODO: RFC #7370: doktype 2&5 are deprecated since TYPO3 4.2-beta1
				if ($mR['doktype'] != t3lib_pageSelect::DOKTYPE_HIDE_IN_MENU && !$mR['nav_hide']) {
					$this->optValues['sections']['rl1_'.$mR['uid']] = trim($this->pi_getLL('opt_RL1').' '.$mR['title']);
					if ($this->conf['show.']['L2sections'])	{
						$secondLevelMenu = $this->getMenu($mR['uid']);
						foreach ($secondLevelMenu as $kk2 => $mR2) {
								// @TODO: RFC #7370: doktype 2&5 are deprecated since TYPO3 4.2-beta1
							if ($mR2['doktype'] != t3lib_pageSelect::DOKTYPE_HIDE_IN_MENU && !$mR2['nav_hide']) {
								$this->optValues['sections']['rl2_'.$mR2['uid']] = trim($this->pi_getLL('opt_RL2').' '.$mR2['title']);
							} else unset($secondLevelMenu[$kk2]);
						}
						$this->optValues['sections']['rl2_'.implode(',',array_keys($secondLevelMenu))] = $this->pi_getLL('opt_RL2ALL');
					}
				} else unset($firstLevelMenu[$kk]);
			}
			$this->optValues['sections']['rl1_'.implode(',',array_keys($firstLevelMenu))] = $this->pi_getLL('opt_RL1ALL');
		}

			// Setting the list of root PIDs for the search. Notice, these page IDs MUST have a TypoScript template with root flag on them! Basically this list is used to select on the "rl0" field and page ids are registered as "rl0" only if a TypoScript template record with root flag is there.
			// This happens AFTER the use of $this->wholeSiteIdList above because the above will then fetch the menu for the CURRENT site - regardless of this kind of searching here. Thus a general search will lookup in the WHOLE database while a specific section search will take the current sections...
		if ($this->conf['search.']['rootPidList'])	{
			$this->wholeSiteIdList = implode(',',t3lib_div::intExplode(',',$this->conf['search.']['rootPidList']));
		}

			// Load the template
		$this->templateCode = $this->cObj->fileResource($this->conf['templateFile']);

			// Add search languages:
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_language', '1=1'.$this->cObj->enableFields('sys_language'));
		while($lR = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$this->optValues['lang'][$lR['uid']] = $lR['title'];
		}

			// Calling hook for modification of initialized content
		if ($hookObj = $this->hookRequest('initialize_postProc')) {
			$hookObj->initialize_postProc();
		}

			// Default values set:
			// Setting first values in optValues as default values IF there is not corresponding piVar value set already.
		foreach ($this->optValues as $kk => $vv)	{
			if (!isset($this->piVars[$kk]))	{
				reset($vv);
				$this->piVars[$kk] = key($vv);
			}
		}

			// Blind selectors:
		if (is_array($this->conf['blind.']))	{
			foreach ($this->conf['blind.'] as $kk => $vv)	{
				if (is_array($vv))	{
					foreach ($vv as $kkk => $vvv)	{
						if (!is_array($vvv) && $vvv && is_array($this->optValues[substr($kk,0,-1)]))	{
							unset($this->optValues[substr($kk,0,-1)][$kkk]);
						}
					}
				} elseif ($vv) {	// If value is not set, unset the option array.
					unset($this->optValues[$kk]);
				}
			}
		}

			// This gets the search-words into the $sWArr:
		$this->sWArr = $this->getSearchWords($this->piVars['defOp']);
	}

	/**
	 * Splits the search word input into an array where each word is represented by an array with key "sword" holding the search word and key "oper" holds the SQL operator (eg. AND, OR)
	 *
	 * Only words with 2 or more characters are accepted
	 * Max 200 chars total
	 * Space is used to split words, "" can be used search for a whole string (not indexed search then)
	 * AND, OR and NOT are prefix words, overruling the default operator
	 * +/|/- equals AND, OR and NOT as operators.
	 * All search words are converted to lowercase.
	 *
	 * $defOp is the default operator. 1=OR, 0=AND
	 *
	 * @param	boolean		If TRUE, the default operator will be OR, not AND
	 * @return	array		Returns array with search words if any found
	 */
	function getSearchWords($defOp)	{
			// Shorten search-word string to max 200 bytes (does NOT take multibyte charsets into account - but never mind, shortening the string here is only a run-away feature!)
		$inSW = substr($this->piVars['sword'],0,200);

			// Convert to UTF-8 + conv. entities (was also converted during indexing!)
		$inSW = $GLOBALS['TSFE']->csConvObj->utf8_encode($inSW, $GLOBALS['TSFE']->metaCharset);
		$inSW = $GLOBALS['TSFE']->csConvObj->entities_to_utf8($inSW,TRUE);

		if ($hookObj = $this->hookRequest('getSearchWords')) {
			return $hookObj->getSearchWords_splitSWords($inSW, $defOp);
		} else {

			if ($this->piVars['type']==20)	{
				return array(array('sword'=>trim($inSW), 'oper'=>'AND'));
			} else {
				$search = t3lib_div::makeInstance('tslib_search');
				$search->default_operator = $defOp==1 ? 'OR' : 'AND';
				$search->operator_translate_table = $this->operator_translate_table;
				$search->register_and_explode_search_string($inSW);

				if (is_array($search->sword_array))	{
					return $this->procSearchWordsByLexer($search->sword_array);
				}
			}
		}
	}

	/**
	 * Post-process the search word array so it will match the words that was indexed (including case-folding if any)
	 * If any words are splitted into multiple words (eg. CJK will be!) the operator of the main word will remain.
	 *
	 * @param	array		Search word array
	 * @return	array		Search word array, processed through lexer
	 */
	function procSearchWordsByLexer($SWArr)	{

			// Init output variable:
		$newSWArr = array();

			// Traverse the search word array:
		foreach ($SWArr as $wordDef)	{
			if (!strstr($wordDef['sword'],' '))	{	// No space in word (otherwise it might be a sentense in quotes like "there is").
					// Split the search word by lexer:
				$res = $this->lexerObj->split2Words($wordDef['sword']);

					// Traverse lexer result and add all words again:
				foreach ($res as $word)	{
					$newSWArr[] = array('sword'=>$word, 'oper'=>$wordDef['oper']);
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
	 * @param	array		Search words in array, see ->getSearchWords() for details
	 * @return	string		HTML for result display.
	 */
	function doSearch($sWArr)	{

			// Find free index uid:
		$freeIndexUid = $this->piVars['freeIndexUid'];
		if ($freeIndexUid==-2)	{
			$freeIndexUid = $this->conf['search.']['defaultFreeIndexUidList'];
		}

		$indexCfgs = t3lib_div::intExplode(',',$freeIndexUid);
		$accumulatedContent = '';

		foreach ($indexCfgs as $freeIndexUid)	{
				// Get result rows:
			$pt1 = t3lib_div::milliseconds();
			if ($hookObj = $this->hookRequest('getResultRows')) {
				$resData = $hookObj->getResultRows($sWArr,$freeIndexUid);
			} else {
				$resData = $this->getResultRows($sWArr,$freeIndexUid);
			}

				// Display search results:
			$pt2 = t3lib_div::milliseconds();
			if ($hookObj = $this->hookRequest('getDisplayResults')) {
				$content = $hookObj->getDisplayResults($sWArr, $resData, $freeIndexUid);
			} else {
				$content = $this->getDisplayResults($sWArr, $resData, $freeIndexUid);
			}

			$pt3 = t3lib_div::milliseconds();

				// Create header if we are searching more than one indexing configuration:
			if (count($indexCfgs)>1)	{
				if ($freeIndexUid>0)	{
					$indexCfgRec = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('title', 'index_config', 'uid=' . intval($freeIndexUid) . $this->cObj->enableFields('index_config'));
					$titleString = $indexCfgRec['title'];
				} else {
					$titleString = $this->pi_getLL('opt_freeIndexUid_header_'.$freeIndexUid);
				}
				$content = '<h1 class="tx-indexedsearch-category">'.htmlspecialchars($titleString).'</h1>'.$content;
			}

			$accumulatedContent.=$content;
		}

			// Write search statistics
		$this->writeSearchStat($sWArr,$resData['count'],array($pt1,$pt2,$pt3));

			// Return content:
		return $accumulatedContent;
	}

	/**
	 * Get search result rows / data from database. Returned as data in array.
	 *
	 * @param	array		Search word array
	 * @param	integer		Pointer to which indexing configuration you want to search in. -1 means no filtering. 0 means only regular indexed content.
	 * @return	array		False if no result, otherwise an array with keys for first row, result rows and total number of results found.
	 */
	function getResultRows($sWArr,$freeIndexUid=-1)	{

			// Getting SQL result pointer:
			$GLOBALS['TT']->push('Searching result');
		$res = $this->getResultRows_SQLpointer($sWArr,$freeIndexUid);
			$GLOBALS['TT']->pull();

			// Organize and process result:
		if ($res)	{

			$count = $GLOBALS['TYPO3_DB']->sql_num_rows($res);	// Total search-result count
			$pointer = t3lib_div::intInRange($this->piVars['pointer'], 0, floor($count/$this->piVars['results']));	// The pointer is set to the result page that is currently being viewed

				// Initialize result accumulation variables:
			$c = 0;	// Result pointer: Counts up the position in the current search-result
			$grouping_phashes = array();	// Used to filter out duplicates.
			$grouping_chashes = array();	// Used to filter out duplicates BASED ON cHash.
			$firstRow = array();	// Will hold the first row in result - used to calculate relative hit-ratings.
			$resultRows = array();	// Will hold the results rows for display.

			$exactCount = $this->conf['search.']['exactCount'];	// Continue counting and checking of results even if we are sure they are not displayed in this request. This will slow down your page rendering, but it allows precise search result counters.

				// Now, traverse result and put the rows to be displayed into an array
				// Each row should contain the fields from 'ISEC.*, IP.*' combined + artificial fields "show_resume" (boolean) and "result_number" (counter)
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{

					// Set first row:
				if (!$c)	{
					$firstRow = $row;
				}

				$row['show_resume'] = $this->checkResume($row);	// Tells whether we can link directly to a document or not (depends on possible right problems)

				$phashGr = !in_array($row['phash_grouping'], $grouping_phashes);
				$chashGr = !in_array($row['contentHash'].'.'.$row['data_page_id'], $grouping_chashes);
				if ($phashGr && $chashGr)	{
					if ($row['show_resume'] || $this->conf['show.']['forbiddenRecords'])	{	// Only if the resume may be shown are we going to filter out duplicates...
						if (!$this->multiplePagesType($row['item_type']))	{	// Only on documents which are not multiple pages documents
							$grouping_phashes[] = $row['phash_grouping'];
						}
						$grouping_chashes[] = $row['contentHash'].'.'.$row['data_page_id'];

						$c++;	// Increase the result pointer

							// All rows for display is put into resultRows[]
						if ($c > $pointer * $this->piVars['results'] && $c <= ($pointer * $this->piVars['results'] + $this->piVars['results']))	{
							$row['result_number'] = $c;
							$resultRows[] = $row;
								// This may lead to a problem: If the result check is not stopped here, the search will take longer. However the result counter will not filter out grouped cHashes/pHashes that were not processed yet. You can change this behavior using the "search.exactCount" property (see above).
							if (!$exactCount && (($c+1) > ($pointer+1)*$this->piVars['results']))	{ break; }
						}
					} else {
						$count--;	// Skip this row if the user cannot view it (missing permission)
					}
				} else {
					$count--;	// For each time a phash_grouping document is found (which is thus not displayed) the search-result count is reduced, so that it matches the number of rows displayed.
				}
			}

			return array(
						'resultRows' => $resultRows,
						'firstRow' => $firstRow,
						'count' => $count
					);
		} else {	// No results found:
			return FALSE;
		}
	}

	/**
	 * Gets a SQL result pointer to traverse for the search records.
	 *
	 * @param	array		Search words
	 * @param	integer		Pointer to which indexing configuration you want to search in. -1 means no filtering. 0 means only regular indexed content.
	 * @return	pointer
	 */
	function getResultRows_SQLpointer($sWArr,$freeIndexUid=-1)	{
				// This SEARCHES for the searchwords in $sWArr AND returns a COMPLETE list of phash-integers of the matches.
		$list = $this->getPhashList($sWArr);

			// Perform SQL Search / collection of result rows array:
		if ($list)	{
				// Do the search:
			$GLOBALS['TT']->push('execFinalQuery');
			$res = $this->execFinalQuery($list,$freeIndexUid);
			$GLOBALS['TT']->pull();
			return $res;
		} else {
			return FALSE;
		}
	}

	/**
	 * Compiles the HTML display of the incoming array of result rows.
	 *
	 * @param	array		Search words array (for display of text describing what was searched for)
	 * @param	array		Array with result rows, count, first row.
	 * @param	integer		Pointer to which indexing configuration you want to search in. -1 means no filtering. 0 means only regular indexed content.
	 * @return	string		HTML content to display result.
	 */
	function getDisplayResults($sWArr, $resData, $freeIndexUid=-1)	{
			// Perform display of result rows array:
		if ($resData)	{
			$GLOBALS['TT']->push('Display Final result');

				// Set first selected row (for calculation of ranking later)
			$this->firstRow = $resData['firstRow'];

				// Result display here:
			$rowcontent = '';
			$rowcontent.= $this->compileResult($resData['resultRows'], $freeIndexUid);

				// Browsing box:
			if ($resData['count'])	{
				$this->internal['res_count'] = $resData['count'];
				$this->internal['results_at_a_time'] = $this->piVars['results'];
				$this->internal['maxPages'] = t3lib_div::intInRange($this->conf['search.']['page_links'],1,100,10);
				$addString = ($resData['count']&&$this->piVars['group']=='sections'&&$freeIndexUid<=0 ? ' '.sprintf($this->pi_getLL(count($this->resultSections)>1?'inNsections':'inNsection'),count($this->resultSections)):'');
				$browseBox1 = $this->pi_list_browseresults(1,$addString,$this->printResultSectionLinks(),$freeIndexUid);
				$browseBox2 = $this->pi_list_browseresults(0,'','',$freeIndexUid);
			}

				// Browsing nav, bottom.
			if ($resData['count'])	{
				$content = $browseBox1.$rowcontent.$browseBox2;
			} else {
				$content = '<p'.$this->pi_classParam('noresults').'>'.$this->pi_getLL('noResults','',1).'</p>';
			}

			$GLOBALS['TT']->pull();
		} else {
			$content.='<p'.$this->pi_classParam('noresults').'>'.$this->pi_getLL('noResults','',1).'</p>';
		}

			// Print a message telling which words we searched for, and in which sections etc.
		$what = $this->tellUsWhatIsSeachedFor($sWArr).
				(substr($this->piVars['sections'],0,2)=='rl'?' '.$this->pi_getLL('inSection','',1).' "'.substr($this->getPathFromPageId(substr($this->piVars['sections'],4)),1).'"':'');
		$what = '<div'.$this->pi_classParam('whatis').'>'.$this->cObj->stdWrap($what, $this->conf['whatis_stdWrap.']).'</div>';
		$content = $what.$content;

			// Return content:
		return $content;
	}

	/**
	 * Takes the array with resultrows as input and returns the result-HTML-code
	 * Takes the "group" var into account: Makes a "section" or "flat" display.
	 *
	 * @param	array		Result rows
	 * @param	integer		Pointer to which indexing configuration you want to search in. -1 means no filtering. 0 means only regular indexed content.
	 * @return	string		HTML
	 */
	function compileResult($resultRows, $freeIndexUid=-1)	{
		$content = '';

			// Transfer result rows to new variable, performing some mapping of sub-results etc.
		$newResultRows = array();
		foreach ($resultRows as $row)	{
			$id = md5($row['phash_grouping']);
			if (is_array($newResultRows[$id]))	{
				if (!$newResultRows[$id]['show_resume'] && $row['show_resume'])	{	// swapping:

						// Remove old
					$subrows = $newResultRows[$id]['_sub'];
					unset($newResultRows[$id]['_sub']);
					$subrows[] = $newResultRows[$id];

						// Insert new:
					$newResultRows[$id] = $row;
					$newResultRows[$id]['_sub'] = $subrows;
				} else $newResultRows[$id]['_sub'][] = $row;
			} else {
				$newResultRows[$id] = $row;
			}
		}
		$resultRows = $newResultRows;
		$this->resultSections = array();

		if ($freeIndexUid<=0)	{
			switch($this->piVars['group'])	{
				case 'sections':

					$rl2flag = substr($this->piVars['sections'],0,2)=='rl';
					$sections = array();
					foreach ($resultRows as $row)	{
						$id = $row['rl0'].'-'.$row['rl1'].($rl2flag?'-'.$row['rl2']:'');
						$sections[$id][] = $row;
					}

					$this->resultSections = array();

					foreach ($sections as $id => $resultRows)	{
						$rlParts = explode('-',$id);
						$theId = $rlParts[2] ? $rlParts[2] : ($rlParts[1]?$rlParts[1]:$rlParts[0]);
						$theRLid = $rlParts[2] ? 'rl2_'.$rlParts[2]:($rlParts[1]?'rl1_'.$rlParts[1]:'0');

						$sectionName = $this->getPathFromPageId($theId);
						if ($sectionName{0} == '/') $sectionName = substr($sectionName,1);

						if (!trim($sectionName))	{
							$sectionTitleLinked = $this->pi_getLL('unnamedSection','',1).':';
						} else {
							$onclick = 'document.'.$this->prefixId.'[\''.$this->prefixId.'[_sections]\'].value=\''.$theRLid.'\';document.'.$this->prefixId.'.submit();return false;';
							$sectionTitleLinked = '<a href="#" onclick="'.htmlspecialchars($onclick).'">'.htmlspecialchars($sectionName).':</a>';
						}
						$this->resultSections[$id] = array($sectionName,count($resultRows));

							// Add content header:
						$content.= $this->makeSectionHeader($id,$sectionTitleLinked,count($resultRows));

							// Render result rows:
						foreach ($resultRows as $row)	{
							$content.= $this->printResultRow($row);
						}
					}
				break;
				default:	// flat:
					foreach ($resultRows as $row)	{
						$content.= $this->printResultRow($row);
					}
				break;
			}
		} else {
			foreach ($resultRows as $row)	{
				$content.= $this->printResultRow($row);
			}
		}

		return '<div'.$this->pi_classParam('res').'>'.$content.'</div>';
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
	 * @param	array		Search word array
	 * @return	string		List of integers
	 */
	function getPhashList($sWArr)	{

			// Initialize variables:
		$c=0;
		$totalHashList = array();	// This array accumulates the phash-values
		$this->wSelClauses = array();

			// Traverse searchwords; for each, select all phash integers and merge/diff/intersect them with previous word (based on operator)
		foreach ($sWArr as $k => $v)	{
				// Making the query for a single search word based on the search-type
			$sWord = $v['sword'];	// $GLOBALS['TSFE']->csConvObj->conv_case('utf-8',$v['sword'],'toLower');	// lower-case all of them...
			$theType = (string)$this->piVars['type'];
			if (strstr($sWord,' '))	$theType = 20;	// If there are spaces in the search-word, make a full text search instead.

			$GLOBALS['TT']->push('SearchWord "'.$sWord.'" - $theType='.$theType);

			$res = '';
			$wSel='';

				// Perform search for word:
			switch($theType)	{
				case '1':	// Part of word
					$wSel = "IW.baseword LIKE '%".$GLOBALS['TYPO3_DB']->quoteStr($sWord, 'index_words')."%'";
					$res = $this->execPHashListQuery($wSel,' AND is_stopword=0');
				break;
				case '2':	// First part of word
					$wSel = "IW.baseword LIKE '".$GLOBALS['TYPO3_DB']->quoteStr($sWord, 'index_words')."%'";
					$res = $this->execPHashListQuery($wSel,' AND is_stopword=0');
				break;
				case '3':	// Last part of word
					$wSel = "IW.baseword LIKE '%".$GLOBALS['TYPO3_DB']->quoteStr($sWord, 'index_words')."'";
					$res = $this->execPHashListQuery($wSel,' AND is_stopword=0');
				break;
				case '10':	// Sounds like
					$wSel = 'IW.metaphone = '.$this->indexerObj->metaphone($sWord);
					$res = $this->execPHashListQuery($wSel,' AND is_stopword=0');
				break;
				case '20':	// Sentence
					$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
								'ISEC.phash',
								'index_section ISEC, index_fulltext IFT',
								'IFT.fulltextdata LIKE \'%'.$GLOBALS['TYPO3_DB']->quoteStr($sWord, 'index_fulltext').'%\' AND
									ISEC.phash = IFT.phash
									'.$this->sectionTableWhere(),
								'ISEC.phash'
							);
					$wSel = '1=1';

					if ($this->piVars['type']==20)	$this->piVars['order'] = 'mtime';		// If there is a fulltext search for a sentence there is a likeliness that sorting cannot be done by the rankings from the rel-table (because no relations will exist for the sentence in the word-table). So therefore mtime is used instaed. It is not required, but otherwise some hits may be left out.
				break;
				default:	// Distinct word
					$wSel = 'IW.wid = '.$hash = $this->indexerObj->md5inthash($sWord);
					$res = $this->execPHashListQuery($wSel,' AND is_stopword=0');
				break;
			}

				// Accumulate the word-select clauses
			$this->wSelClauses[] = $wSel;

				// If there was a query to do, then select all phash-integers which resulted from this.
			if ($res)	{

					// Get phash list by searching for it:
				$phashList = array();
				while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
					$phashList[] = $row['phash'];
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);

					// Here the phash list are merged with the existing result based on whether we are dealing with OR, NOT or AND operations.
				if ($c) {
					switch($v['oper'])	{
						case 'OR':
							$totalHashList = array_unique(array_merge($phashList,$totalHashList));
						break;
						case 'AND NOT':
							$totalHashList = array_diff($totalHashList,$phashList);
						break;
						default:	// AND...
							$totalHashList = array_intersect($totalHashList,$phashList);
						break;
					}
				} else {
					$totalHashList = $phashList;	// First search
				}
			}

			$GLOBALS['TT']->pull();
			$c++;
		}

		return implode(',',$totalHashList);
	}

	/**
	 * Returns a query which selects the search-word from the word/rel tables.
	 *
	 * @param	string		WHERE clause selecting the word from phash
	 * @param	string		Additional AND clause in the end of the query.
	 * @return	pointer		SQL result pointer
	 */
	function execPHashListQuery($wordSel,$plusQ='')	{
		return $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'IR.phash',
					'index_words IW,
						index_rel IR,
						index_section ISEC',
					$wordSel.'
						AND IW.wid=IR.wid
						AND ISEC.phash = IR.phash
						'.$this->sectionTableWhere().'
						'.$plusQ,
					'IR.phash'
				);
	}

	/**
	 * Returns AND statement for selection of section in database. (rootlevel 0-2 + page_id)
	 *
	 * @return	string		AND clause for selection of section in database.
	 */
	function sectionTableWhere()	{
		$out = $this->wholeSiteIdList<0 ? '' : 'AND ISEC.rl0 IN ('.$this->wholeSiteIdList.')';

		$match = '';
		if (substr($this->piVars['sections'],0,4)=='rl1_')	{
			$list = implode(',',t3lib_div::intExplode(',',substr($this->piVars['sections'],4)));
			$out.= 'AND ISEC.rl1 IN ('.$list.')';
			$match = TRUE;
		} elseif (substr($this->piVars['sections'],0,4)=='rl2_')	{
			$list = implode(',',t3lib_div::intExplode(',',substr($this->piVars['sections'],4)));
			$out.= 'AND ISEC.rl2 IN ('.$list.')';
			$match = TRUE;
		} elseif (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['addRootLineFields']))	{
				// Traversing user configured fields to see if any of those are used to limit search to a section:
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['addRootLineFields'] as $fieldName => $rootLineLevel)	{
				if (substr($this->piVars['sections'],0,strlen($fieldName)+1)==$fieldName.'_')	{
					$list = implode(',',t3lib_div::intExplode(',',substr($this->piVars['sections'],strlen($fieldName)+1)));
					$out.= 'AND ISEC.'.$fieldName.' IN ('.$list.')';
					$match = TRUE;
					break;
				}
			}
		}

			// If no match above, test the static types:
		if (!$match)	{
			switch((string)$this->piVars['sections'])	{
				case '-1':		// '-1' => 'Only this page',
					$out.= ' AND ISEC.page_id='.$GLOBALS['TSFE']->id;
				break;
				case '-2':		// '-2' => 'Top + level 1',
					$out.= ' AND ISEC.rl2=0';
				break;
				case '-3':		// '-3' => 'Level 2 and out',
					$out.= ' AND ISEC.rl2>0';
				break;
			}
		}

		return $out;
	}

	/**
	 * Returns AND statement for selection of media type
	 *
	 * @return	string		AND statement for selection of media type
	 */
	function mediaTypeWhere()	{

		switch((string)$this->piVars['media'])	{
			case '0':		// '0' => 'Kun TYPO3 sider',
				$out = 'AND IP.item_type=' . $GLOBALS['TYPO3_DB']->fullQuoteStr('0', 'index_phash');
				break;
			case '-2':		// All external documents
				$out = 'AND IP.item_type!=' . $GLOBALS['TYPO3_DB']->fullQuoteStr('0', 'index_phash');
				break;
			case '-1':	// All content
				$out = '';
				break;
			default:
				$out = 'AND IP.item_type=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($this->piVars['media'], 'index_phash');
		}

		return $out;
	}

	/**
	 * Returns AND statement for selection of langauge
	 *
	 * @return	string		AND statement for selection of langauge
	 */
	function languageWhere()	{
		if ($this->piVars['lang']>=0)	{	// -1 is the same as ALL language.
			return 'AND IP.sys_language_uid='.intval($this->piVars['lang']);
		}
	}

	/**
	 * Where-clause for free index-uid value.
	 *
	 * @param	integer		Free Index UID value to limit search to.
	 * @return	string		WHERE SQL clause part.
	 */
	function freeIndexUidWhere($freeIndexUid)	{

		if ($freeIndexUid>=0)	{

				// First, look if the freeIndexUid is a meta configuration:
			$indexCfgRec = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('indexcfgs', 'index_config', 'type=5 AND uid=' . intval($freeIndexUid) . $this->cObj->enableFields('index_config'));
			if (is_array($indexCfgRec))	{
				$refs = t3lib_div::trimExplode(',',$indexCfgRec['indexcfgs']);
				$list = array(-99);	// Default value to protect against empty array.
				foreach ($refs as $ref)	{
					list($table,$uid) = t3lib_div::revExplode('_',$ref,2);
					switch ($table)	{
						case 'index_config':
							$idxRec = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('uid', 'index_config', 'uid=' . intval($uid) . $this->cObj->enableFields('index_config'));
							if ($idxRec)	$list[] = $uid;
						break;
						case 'pages':
							$indexCfgRecordsFromPid = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid','index_config','pid='.intval($uid).$this->cObj->enableFields('index_config'));
							foreach ($indexCfgRecordsFromPid as $idxRec)	{
								$list[] = $idxRec['uid'];
							}
						break;
					}
				}

				$list = array_unique($list);
			} else {
				$list = array(intval($freeIndexUid));
			}

			return ' AND IP.freeIndexUid IN ('.implode(',',$list).')';
		}
	}

	/**
	 * Execute final query, based on phash integer list. The main point is sorting the result in the right order.
	 *
	 * @param	string		List of phash integers which match the search.
	 * @param	integer		Pointer to which indexing configuration you want to search in. -1 means no filtering. 0 means only regular indexed content.
	 * @return	pointer		Query result pointer
	 */
	function execFinalQuery($list,$freeIndexUid=-1)	{

			// Setting up methods of filtering results based on page types, access, etc.
		$page_join = '';
		$page_where = '';

			// Indexing configuration clause:
		$freeIndexUidClause = $this->freeIndexUidWhere($freeIndexUid);

			// Calling hook for alternative creation of page ID list
		if ($hookObj = $this->hookRequest('execFinalQuery_idList')) {
			$page_where = $hookObj->execFinalQuery_idList($list);
		} elseif ($this->join_pages)	{	// Alternative to getting all page ids by ->getTreeList() where "excludeSubpages" is NOT respected.
			$page_join = ',
				pages';
			$page_where = 'pages.uid = ISEC.page_id
				'.$this->cObj->enableFields('pages').'
				AND pages.no_search=0
				AND pages.doktype<200
			';
		} elseif ($this->wholeSiteIdList>=0) {	// Collecting all pages IDs in which to search; filtering out ALL pages that are not accessible due to enableFields. Does NOT look for "no_search" field!
			$siteIdNumbers = t3lib_div::intExplode(',',$this->wholeSiteIdList);
			$id_list=array();
			foreach ($siteIdNumbers as $rootId) {
				$id_list[] = $this->cObj->getTreeList($rootId,9999,0,0,'','').$rootId;
			}
			$page_where = 'ISEC.page_id IN ('.implode(',',$id_list).')';
		} else {	// Disable everything... (select all)
			$page_where = ' 1=1 ';
		}


			// If any of the ranking sortings are selected, we must make a join with the word/rel-table again, because we need to calculate ranking based on all search-words found.
		if (substr($this->piVars['order'],0,5)=='rank_')	{
				/*
					 OK there were some fancy calculations promoted by Graeme Merrall:

					"However, regarding relevance you probably want to look at something like
					Salton's formula which is a good easy way to measure relevance.
					Oracle Intermedia uses this and it's pretty simple:
					Score can be between 0 and 100, but the top-scoring document in the query
					will not necessarily have a score of 100 -- scoring is relative, not
					absolute. This means that scores are not comparable across indexes, or even
					across different queries on the same index. Score for each document is
					computed using the standard Salton formula:

					    3f(1+log(N/n))

					Where f is the frequency of the search term in the document, N is the total
					number of rows in the table, and n is the number of rows which contain the
					search term. This is converted into an integer in the range 0 - 100.

					There's a good doc on it at
					http://ls6-www.informatik.uni-dortmund.de/bib/fulltext/ir/Pfeifer:97/
					although it may be a little complex for what you require so just pick the
					relevant parts out.
					"

					However I chose not to go with this for several reasons.
					I do not claim that my ways of calculating importance here is the best.
					ANY (better) suggestion for ranking calculation is accepted! (as long as they are shipped with tested code in exchange for this.)
				*/

			switch($this->piVars['order'])	{
				case 'rank_flag':	// This gives priority to word-position (max-value) so that words in title, keywords, description counts more than in content.
									// The ordering is refined with the frequency sum as well.
					$grsel = 'MAX(IR.flags) AS order_val1, SUM(IR.freq) AS order_val2';
					$orderBy = 'order_val1'.$this->isDescending().',order_val2'.$this->isDescending();
				break;
				case 'rank_first':	// Results in average position of search words on page. Must be inversely sorted (low numbers are closer to top)
					$grsel = 'AVG(IR.first) AS order_val';
					$orderBy = 'order_val'.$this->isDescending(1);
				break;
				case 'rank_count':	// Number of words found
					$grsel = 'SUM(IR.count) AS order_val';
					$orderBy = 'order_val'.$this->isDescending();
				break;
				default:	// Frequency sum. I'm not sure if this is the best way to do it (make a sum...). Or should it be the average?
					$grsel = 'SUM(IR.freq) AS order_val';
					$orderBy = 'order_val'.$this->isDescending();
				break;
			}

				// So, words are imploded into an OR statement (no "sentence search" should be done here - may deselect results)
			$wordSel='('.implode(' OR ',$this->wSelClauses).') AND ';

			return $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'ISEC.*, IP.*, '
						.$grsel,
						'index_words IW,
							index_rel IR,
							index_section ISEC,
							index_phash IP'.
							$page_join,
						$wordSel.'
							IP.phash IN ('.$list.') '.
							$this->mediaTypeWhere().' '.
							$this->languageWhere().
							$freeIndexUidClause.'
							AND IW.wid=IR.wid
							AND ISEC.phash = IR.phash
							AND IP.phash = IR.phash
							AND	'.$page_where,
						'IP.phash,ISEC.phash,ISEC.phash_t3,ISEC.rl0,ISEC.rl1,ISEC.rl2 ,ISEC.page_id,ISEC.uniqid,IP.phash_grouping,IP.data_filename ,IP.data_page_id ,IP.data_page_reg1,IP.data_page_type,IP.data_page_mp,IP.gr_list,IP.item_type,IP.item_title,IP.item_description,IP.item_mtime,IP.tstamp,IP.item_size,IP.contentHash,IP.crdate,IP.parsetime,IP.sys_language_uid,IP.item_crdate,IP.cHashParams,IP.externalUrl,IP.recordUid,IP.freeIndexUid,IP.freeIndexSetId',
						$orderBy
					);
		} else {	// Otherwise, if sorting are done with the pages table or other fields, there is no need for joining with the rel/word tables:

			$orderBy = '';
			switch((string)$this->piVars['order'])	{
				case 'title':
					$orderBy = 'IP.item_title'.$this->isDescending();
				break;
				case 'crdate':
					$orderBy = 'IP.item_crdate'.$this->isDescending();
				break;
				case 'mtime':
					$orderBy = 'IP.item_mtime'.$this->isDescending();
				break;
			}

			return $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'ISEC.*, IP.*',
						'index_phash IP,index_section ISEC'.$page_join,
						'IP.phash IN ('.$list.') '.
							$this->mediaTypeWhere().' '.
							$this->languageWhere().
							$freeIndexUidClause.'
							AND IP.phash = ISEC.phash
							AND '.$page_where,
						'IP.phash,ISEC.phash,ISEC.phash_t3,ISEC.rl0,ISEC.rl1,ISEC.rl2 ,ISEC.page_id,ISEC.uniqid,IP.phash_grouping,IP.data_filename ,IP.data_page_id ,IP.data_page_reg1,IP.data_page_type,IP.data_page_mp,IP.gr_list,IP.item_type,IP.item_title,IP.item_description,IP.item_mtime,IP.tstamp,IP.item_size,IP.contentHash,IP.crdate,IP.parsetime,IP.sys_language_uid,IP.item_crdate,IP.cHashParams,IP.externalUrl,IP.recordUid,IP.freeIndexUid,IP.freeIndexSetId',
						$orderBy
					);
		}
	}

	/**
	 * Checking if the resume can be shown for the search result (depending on whether the rights are OK)
	 * ? Should it also check for gr_list "0,-1"?
	 *
	 * @param	array		Result row array.
	 * @return	boolean		Returns TRUE if resume can safely be shown
	 */
	function checkResume($row)	{

			// If the record is indexed by an indexing configuration, just show it.
			// At least this is needed for external URLs and files.
			// For records we might need to extend this - for instance block display if record is access restricted.
		if ($row['freeIndexUid'])	{
			return TRUE;
		}

			// Evaluate regularly indexed pages based on item_type:
		if ($row['item_type'])	{	// External media:
				// For external media we will check the access of the parent page on which the media was linked from.
				// "phash_t3" is the phash of the parent TYPO3 page row which initiated the indexing of the documents in this section.
				// So, selecting for the grlist records belonging to the parent phash-row where the current users gr_list exists will help us to know.
				// If this is NOT found, there is still a theoretical possibility that another user accessible page would display a link, so maybe the resume of such a document here may be unjustified hidden. But better safe than sorry.
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('phash', 'index_grlist', 'phash='.intval($row['phash_t3']).' AND gr_list='.$GLOBALS['TYPO3_DB']->fullQuoteStr($GLOBALS['TSFE']->gr_list, 'index_grlist'));
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($res))	{
				#debug("Look up for external media '".$row['data_filename']."': phash:".$row['phash_t3'].' YES - ('.$GLOBALS['TSFE']->gr_list.")!");
				return TRUE;
			} else {
				#debug("Look up for external media '".$row['data_filename']."': phash:".$row['phash_t3'].' NO - ('.$GLOBALS['TSFE']->gr_list.")!");
				return FALSE;
			}
		} else {	// Ordinary TYPO3 pages:
			if (strcmp($row['gr_list'],$GLOBALS['TSFE']->gr_list))	{
					// Selecting for the grlist records belonging to the phash-row where the current users gr_list exists. If it is found it is proof that this user has direct access to the phash-rows content although he did not himself initiate the indexing...
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('phash', 'index_grlist', 'phash='.intval($row['phash']).' AND gr_list='.$GLOBALS['TYPO3_DB']->fullQuoteStr($GLOBALS['TSFE']->gr_list, 'index_grlist'));
				if ($GLOBALS['TYPO3_DB']->sql_num_rows($res))	{
					#debug('Checking on it ...'.$row['item_title'].'/'.$row['phash'].' - YES ('.$GLOBALS['TSFE']->gr_list.")");
					return TRUE;
				} else {
					#debug('Checking on it ...'.$row['item_title'].'/'.$row['phash']." - NOPE");
					return FALSE;
				}
			} else {
					#debug('Resume can be shown, because the document was in fact indexed by this combination of groups!'.$GLOBALS['TSFE']->gr_list.' - '.$row['item_title'].'/'.$row['phash']);
				return TRUE;
			}
		}
	}

	/**
	 * Returns "DESC" or "" depending on the settings of the incoming highest/lowest result order (piVars['desc']
	 *
	 * @param	boolean		If TRUE, inverse the order which is defined by piVars['desc']
	 * @return	string		" DESC" or ""
	 */
	function isDescending($inverse=FALSE)	{
		$desc = $this->piVars['desc'];
		if ($inverse)	$desc=!$desc;
		return !$desc ? ' DESC':'';
	}

	/**
	 * Write statistics information to database for the search operation
	 *
	 * @param	array		Search Word array
	 * @param	integer		Number of hits
	 * @param	integer		Milliseconds the search took
	 * @return	void
	 */
	function writeSearchStat($sWArr,$count,$pt)	{
		$insertFields = array(
			'searchstring' => $this->piVars['sword'],
			'searchoptions' => serialize(array($this->piVars,$sWArr,$pt)),
			'feuser_id' => intval($this->fe_user->user['uid']),			// fe_user id, integer
			'cookie' => $this->fe_user->id,						// cookie as set or retrieve. If people has cookies disabled this will vary all the time...
			'IP' => t3lib_div::getIndpEnv('REMOTE_ADDR'),		// Remote IP address
			'hits' => intval($count),							// Number of hits on the search.
			'tstamp' => $GLOBALS['EXEC_TIME']					// Time stamp
		);

		$GLOBALS['TYPO3_DB']->exec_INSERTquery('index_stat_search', $insertFields);
		$newId = $GLOBALS['TYPO3_DB']->sql_insert_id();

		if ($newId)	{
			foreach ($sWArr as $val)	{
				$insertFields = array(
					'word' => $val['sword'],		// $GLOBALS['TSFE']->csConvObj->conv_case('utf-8', $val['sword'], 'toLower'),
					'index_stat_search_id' => $newId,
					'tstamp' => $GLOBALS['EXEC_TIME'],		// Time stamp
					'pageid' => $GLOBALS['TSFE']->id	//search page id for indexed search stats
				);

				$GLOBALS['TYPO3_DB']->exec_INSERTquery('index_stat_word', $insertFields);
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
	 * @param	array		Value/Labels pairs for search form selector boxes.
	 * @return	string		Search form HTML
	 */
	function makeSearchForm($optValues)	{
		$html = $this->cObj->getSubpart($this->templateCode, '###SEARCH_FORM###');

			// Multilangual text
		$substituteArray = array('legend', 'searchFor', 'extResume', 'atATime', 'orderBy', 'fromSection', 'searchIn', 'match', 'style', 'freeIndexUid');
		foreach ($substituteArray as $marker)	{
			$markerArray['###FORM_'.t3lib_div::strtoupper($marker).'###'] = $this->pi_getLL('form_'.$marker,'',1);
		}

		$markerArray['###FORM_SUBMIT###'] = $this->pi_getLL('submit_button_label','',1);

			// Adding search field value
		if (isset($this->piVars['sword']) && $this->piVars['sword'] != '') {
			$markerArray['###SWORD_VALUE###'] = htmlspecialchars($this->piVars['sword']);
		} else {
			$markerArray['###SWORD_VALUE###'] = $this->pi_getLL('default_search_word_entry');
		}


			// Additonal keyword => "Add to current search words"
		if ($this->conf['show.']['clearSearchBox'] && $this->conf['show.']['clearSearchBox.']['enableSubSearchCheckBox'])	{
			$markerArray['###SWORD_PREV_VALUE###'] = htmlspecialchars($this->conf['show.']['clearSearchBox'] ? '' : $this->piVars['sword']);
			$markerArray['###SWORD_PREV_INCLUDE_CHECKED###'] = $this->piVars['sword_prev_include'] ? ' checked="checked"':'';
			$markerArray['###ADD_TO_CURRENT_SEARCH###'] = $this->pi_getLL('makerating_addToCurrentSearch','',1);
		} else {
			$html = $this->cObj->substituteSubpart($html, '###ADDITONAL_KEYWORD###', '');
		}

		$markerArray['###ACTION_URL###'] = htmlspecialchars($this->getSearchFormActionURL());

		$hiddenFieldCode = $this->cObj->getSubpart($this->templateCode, '###HIDDEN_FIELDS###');
		$hiddenFieldCode = preg_replace('/^\n\t(.+)/ms', '$1', $hiddenFieldCode);		// Remove first newline and tab (cosmetical issue)
		$hiddenFieldArr = array();

		foreach (t3lib_div::trimExplode(',',$this->hiddenFieldList) as $fieldName)	{
			$hiddenFieldMarkerArray = array();
			$hiddenFieldMarkerArray['###HIDDEN_FIELDNAME###'] = $this->prefixId.'['.$fieldName.']';
			$hiddenFieldMarkerArray['###HIDDEN_VALUE###'] = htmlspecialchars((string)$this->piVars[$fieldName]);

			$hiddenFieldArr[$fieldName] = $this->cObj->substituteMarkerArrayCached($hiddenFieldCode, $hiddenFieldMarkerArray, array(), array());
		}

			// Extended search
		if ($this->piVars['ext'])	{

				// Search for
			if ((!is_array($optValues['type']) && !is_array($optValues['defOp'])) || ($this->conf['blind.']['type'] && $this->conf['blind.']['defOp']))	{
				$html = $this->cObj->substituteSubpart($html, '###SELECT_SEARCH_FOR###', '');
			} else {
				if (is_array($optValues['type']) && !$this->conf['blind.']['type'])	{
					unset($hiddenFieldArr['type']);
					$markerArray['###SELECTBOX_TYPE_VALUES###'] = $this->renderSelectBoxValues($this->piVars['type'],$optValues['type']);
				} else {
					$html = $this->cObj->substituteSubpart($html, '###SELECT_SEARCH_TYPE###', '');
				}

				if (is_array($optValues['defOp']) || !$this->conf['blind.']['defOp'])	{
					$markerArray['###SELECTBOX_DEFOP_VALUES###'] = $this->renderSelectBoxValues($this->piVars['defOp'],$optValues['defOp']);
				} else {
					$html = $this->cObj->substituteSubpart($html, '###SELECT_SEARCH_DEFOP###', '');
				}
			}

				// Search in
			if ((!is_array($optValues['media']) && !is_array($optValues['lang'])) || ($this->conf['blind.']['media'] && $this->conf['blind.']['lang']))	{
				$html = $this->cObj->substituteSubpart($html, '###SELECT_SEARCH_IN###', '');
			} else {
				if (is_array($optValues['media']) && !$this->conf['blind.']['media'])	{
					unset($hiddenFieldArr['media']);
					$markerArray['###SELECTBOX_MEDIA_VALUES###'] = $this->renderSelectBoxValues($this->piVars['media'],$optValues['media']);
				} else {
					$html = $this->cObj->substituteSubpart($html, '###SELECT_SEARCH_MEDIA###', '');
				}

				if (is_array($optValues['lang']) || !$this->conf['blind.']['lang'])	{
					unset($hiddenFieldArr['lang']);
					$markerArray['###SELECTBOX_LANG_VALUES###'] = $this->renderSelectBoxValues($this->piVars['lang'],$optValues['lang']);
				} else {
					$html = $this->cObj->substituteSubpart($html, '###SELECT_SEARCH_LANG###', '');
				}
			}

				// Sections
			if (!is_array($optValues['sections']) || $this->conf['blind.']['sections'])	{
				$html = $this->cObj->substituteSubpart($html, '###SELECT_SECTION###', '');
			} else {
				$markerArray['###SELECTBOX_SECTIONS_VALUES###'] = $this->renderSelectBoxValues($this->piVars['sections'],$optValues['sections']);
			}

				// Free Indexing Configurations:
			if (!is_array($optValues['freeIndexUid']) || $this->conf['blind.']['freeIndexUid'])	{
				$html = $this->cObj->substituteSubpart($html, '###SELECT_FREEINDEXUID###', '');
			} else {
				$markerArray['###SELECTBOX_FREEINDEXUIDS_VALUES###'] = $this->renderSelectBoxValues($this->piVars['freeIndexUid'],$optValues['freeIndexUid']);
			}

				// Sorting
			if (!is_array($optValues['order']) || !is_array($optValues['desc']) || $this->conf['blind.']['order'])	{
				$html = $this->cObj->substituteSubpart($html, '###SELECT_ORDER###', '');
			} else {
				unset($hiddenFieldArr['order']);
				unset($hiddenFieldArr['desc']);
				unset($hiddenFieldArr['results']);
				$markerArray['###SELECTBOX_ORDER_VALUES###'] = $this->renderSelectBoxValues($this->piVars['order'],$optValues['order']);
				$markerArray['###SELECTBOX_DESC_VALUES###'] = $this->renderSelectBoxValues($this->piVars['desc'],$optValues['desc']);
				$markerArray['###SELECTBOX_RESULTS_VALUES###'] = $this->renderSelectBoxValues($this->piVars['results'],$optValues['results']);
			}

				// Limits
			if (!is_array($optValues['results']) || !is_array($optValues['results']) || $this->conf['blind.']['results'])	{
				$html = $this->cObj->substituteSubpart($html, '###SELECT_RESULTS###', '');
			} else {
				$markerArray['###SELECTBOX_RESULTS_VALUES###'] = $this->renderSelectBoxValues($this->piVars['results'],$optValues['results']);
			}

				// Grouping
			if (!is_array($optValues['group']) || $this->conf['blind.']['group'])	{
				$html = $this->cObj->substituteSubpart($html, '###SELECT_GROUP###', '');
			} else {
				unset($hiddenFieldArr['group']);
				$markerArray['###SELECTBOX_GROUP_VALUES###'] = $this->renderSelectBoxValues($this->piVars['group'],$optValues['group']);
			}

			if ($this->conf['blind.']['extResume'])	{
				$html = $this->cObj->substituteSubpart($html, '###SELECT_EXTRESUME###', '');
			} else {
				$markerArray['###EXT_RESUME_CHECKED###'] = $this->piVars['extResume'] ? ' checked="checked"' : '';
			}

		} else {	// Extended search
			$html = $this->cObj->substituteSubpart($html, '###SEARCH_FORM_EXTENDED###', '');
		}

		if($this->conf['show.']['advancedSearchLink'])	{
			$linkToOtherMode = ($this->piVars['ext'] ?
				$this->pi_getPageLink($GLOBALS['TSFE']->id,$GLOBALS['TSFE']->sPre,array($this->prefixId.'[ext]'=>0)) :
				$this->pi_getPageLink($GLOBALS['TSFE']->id,$GLOBALS['TSFE']->sPre,array($this->prefixId.'[ext]'=>1))
			);

			$markerArray['###LINKTOOTHERMODE###'] = '<a href="'.htmlspecialchars($linkToOtherMode).'">'.$this->pi_getLL($this->piVars['ext']?'link_regularSearch':'link_advancedSearch', '', 1).'</a>';
		} else {
			$markerArray['###LINKTOOTHERMODE###'] = '';
		}

			// Write all hidden fields
		$html = $this->cObj->substituteSubpart($html, '###HIDDEN_FIELDS###', implode('',$hiddenFieldArr));

		$substitutedContent = $this->cObj->substituteMarkerArrayCached($html, $markerArray, array(), array());

		return $substitutedContent;
	}

	/**
	 * Function, rendering selector box values.
	 *
	 * @param	string		Current value
	 * @param	array		Array with the options as key=>value pairs
	 * @return	string		<options> imploded.
	 */
	function renderSelectBoxValues($value,$optValues)	{
		if (is_array($optValues))	{
			$opt=array();
			$isSelFlag=0;
			foreach ($optValues as $k=>$v)	{
				$sel = (!strcmp($k,$value) ? ' selected="selected"' : '');
				if ($sel)	{ $isSelFlag++; }
				$opt[]='<option value="'.htmlspecialchars($k).'"'.$sel.'>'.htmlspecialchars($v).'</option>';
			}

			return implode('',$opt);
		}
	}

	/**
	 * Print the searching rules
	 *
	 * @return	string		Rules for the search
	 */
	function printRules()	{
		if ($this->conf['show.']['rules'])	{

			$html = $this->cObj->getSubpart($this->templateCode, '###RULES###');

			$markerArray['###RULES_HEADER###'] = $this->pi_getLL('rules_header','',1);
			$markerArray['###RULES_TEXT###'] = nl2br(trim($this->pi_getLL('rules_text','',1)));

			$substitutedContent = $this->cObj->substituteMarkerArrayCached($html, $markerArray, array(), array());

			return $this->cObj->stdWrap($substitutedContent, $this->conf['rules_stdWrap.']);
		}
	}

	/**
	 * Returns the anchor-links to the sections inside the displayed result rows.
	 *
	 * @return	string
	 */
	function printResultSectionLinks()	{
		if (count($this->resultSections))	{
			$lines = array();

			$html = $this->cObj->getSubpart($this->templateCode, '###RESULT_SECTION_LINKS###');
			$item = $this->cObj->getSubpart($this->templateCode, '###RESULT_SECTION_LINKS_LINK###');

			foreach ($this->resultSections as $id => $dat)	{
				$markerArray = array();

				$aBegin = '<a href="'.htmlspecialchars($GLOBALS['TSFE']->anchorPrefix.'#anchor_'.md5($id)).'">';
				$aContent = htmlspecialchars(trim($dat[0]) ? trim($dat[0]) : $this->pi_getLL('unnamedSection')).
						' ('.$dat[1].' '.$this->pi_getLL($dat[1]>1 ? 'word_pages' : 'word_page','',1).')';
				$aEnd = '</a>';

				$markerArray['###LINK###'] = $aBegin . $aContent . $aEnd;

				$links[] = $this->cObj->substituteMarkerArrayCached($item, $markerArray, array(), array());
			}

			$html = $this->cObj->substituteMarkerArrayCached($html, array('###LINKS###' => implode('',$links)), array(), array());

			return '<div'.$this->pi_classParam('sectionlinks').'>'.$this->cObj->stdWrap($html, $this->conf['sectionlinks_stdWrap.']).'</div>';
		}
	}

	/**
	 * Returns the section header of the search result.
	 *
	 * @param	string		ID for the section (used for anchor link)
	 * @param	string		Section title with linked wrapped around
	 * @param	integer		Number of results in section
	 * @return	string		HTML output
	 */
	function makeSectionHeader($id, $sectionTitleLinked, $countResultRows)	{

		$html = $this->cObj->getSubpart($this->templateCode, '###SECTION_HEADER###');

		$markerArray['###ANCHOR_URL###'] = 'anchor_'.md5($id);
		$markerArray['###SECTION_TITLE###'] = $sectionTitleLinked;
		$markerArray['###RESULT_COUNT###'] = $countResultRows;
		$markerArray['###RESULT_NAME###'] = $this->pi_getLL('word_page'.($countResultRows>1 ? 's' : ''));

		$substitutedContent = $this->cObj->substituteMarkerArrayCached($html, $markerArray, array(), array());

		return $substitutedContent;
	}

	/**
	 * This prints a single result row, including a recursive call for subrows.
	 *
	 * @param	array		Search result row
	 * @param	integer		1=Display only header (for sub-rows!), 2=nothing at all
	 * @return	string		HTML code
	 */
	function printResultRow($row, $headerOnly=0)	{

			// Get template content:
		$tmplContent = $this->prepareResultRowTemplateData($row, $headerOnly);

		if ($hookObj = $this->hookRequest('printResultRow')) {
			return $hookObj->printResultRow($row, $headerOnly, $tmplContent);
		} else {

			$html = $this->cObj->getSubpart($this->templateCode, '###RESULT_OUTPUT###');

			if (!is_array($row['_sub']))	{
				$html = $this->cObj->substituteSubpart($html, '###ROW_SUB###', '');
			}

			if (!$headerOnly)	{
				$html = $this->cObj->substituteSubpart($html, '###ROW_SHORT###', '');

			} elseif ($headerOnly==1) {
				$html = $this->cObj->substituteSubpart($html, '###ROW_LONG###', '');
			} elseif ($headerOnly==2) {
				$html = $this->cObj->substituteSubpart($html, '###ROW_SHORT###', '');
				$html = $this->cObj->substituteSubpart($html, '###ROW_LONG###', '');
			}

			if (is_array($tmplContent))	{
				foreach ($tmplContent AS $k => $v)	{
					$markerArray['###'.t3lib_div::strtoupper($k).'###'] = $v;
				}
			}

				// Description text
			$markerArray['###TEXT_ITEM_SIZE###'] = $this->pi_getLL('res_size','',1);
			$markerArray['###TEXT_ITEM_CRDATE###'] = $this->pi_getLL('res_created','',1);
			$markerArray['###TEXT_ITEM_MTIME###'] = $this->pi_getLL('res_modified','',1);
			$markerArray['###TEXT_ITEM_PATH###'] = $this->pi_getLL('res_path','',1);

			$html = $this->cObj->substituteMarkerArrayCached($html, $markerArray, array(), array());

				// If there are subrows (eg. subpages in a PDF-file or if a duplicate page is selected due to user-login (phash_grouping))
			if (is_array($row['_sub']))	{
				if ($this->multiplePagesType($row['item_type']))	{

					$html = str_replace('###TEXT_ROW_SUB###', $this->pi_getLL('res_otherMatching','',1), $html);

					foreach ($row['_sub'] as $subRow)	{
						$html .= $this->printResultRow($subRow,1);
					}
				} else {

					$markerArray['###TEXT_ROW_SUB###'] = $this->pi_getLL('res_otherMatching','',1);

					$html = str_replace('###TEXT_ROW_SUB###', $this->pi_getLL('res_otherPageAsWell','',1), $html);
				}
			}

			return $html;
		}
	}

	/**
	 * Returns a results browser
	 *
	 * @param	boolean		Show result count
	 * @param	string		String appended to "displaying results..." notice.
	 * @param	string		String appended after section "displaying results..."
	 * @param	string		List of integers pointing to free indexing configurations to search. -1 represents no filtering, 0 represents TYPO3 pages only, any number above zero is a uid of an indexing configuration!
	 * @return	string		HTML output
	 */
	function pi_list_browseresults($showResultCount=1,$addString='',$addPart='',$freeIndexUid=-1)	{

			// Initializing variables:
		$pointer=$this->piVars['pointer'];
		$count=$this->internal['res_count'];
		$results_at_a_time = t3lib_div::intInRange($this->internal['results_at_a_time'],1,1000);
		$maxPages = t3lib_div::intInRange($this->internal['maxPages'],1,100);
		$pageCount = ceil($count/$results_at_a_time);
		$sTables = '';

		if ($pageCount > 1)	{	// only show the result browser if more than one page is needed
			$pointer=intval($pointer);
			$links=array();

				// Make browse-table/links:
			if ($pointer>0)	{	// all pages after the 1st one
				$links[]='<li>'.$this->makePointerSelector_link($this->pi_getLL('pi_list_browseresults_prev','< Previous',1),$pointer-1,$freeIndexUid).'</li>';
			}

			for($a=0;$a<$pageCount;$a++)	{
				$min = max(0, $pointer+1-ceil($maxPages/2));
				$max = $min+$maxPages;
				if($max>$pageCount)	{
					$min = $min - ($max-$pageCount);
				}

				if($a >= $min && $a < $max)	{
					if($a==$pointer)	{
						$links[]='<li'.$this->pi_classParam('browselist-currentPage').'><strong>'.$this->makePointerSelector_link(trim($this->pi_getLL('pi_list_browseresults_page','Page',1).' '.($a+1)),$a,$freeIndexUid).'</strong></li>';
					} else {
						$links[]='<li>'.$this->makePointerSelector_link(trim($this->pi_getLL('pi_list_browseresults_page','Page',1).' '.($a+1)),$a,$freeIndexUid).'</li>';
					}
				}
			}
			if ($pointer+1 < $pageCount)	{
				$links[]='<li>'.$this->makePointerSelector_link($this->pi_getLL('pi_list_browseresults_next','Next >',1),$pointer+1,$freeIndexUid).'</li>';
			}
		}

		$pR1 = $pointer*$results_at_a_time+1;
		$pR2 = $pointer*$results_at_a_time+$results_at_a_time;
		if(is_array($links))	{
			$addPart .= '
		<ul class="browsebox">
			'.implode('',$links).'
		</ul>';
		}

		$label = $this->pi_getLL('pi_list_browseresults_display','Displaying results ###TAG_BEGIN###%s to %s###TAG_END### out of ###TAG_BEGIN###%s###TAG_END###');
		$label = str_replace('###TAG_BEGIN###','<strong>',$label);
		$label = str_replace('###TAG_END###','</strong>',$label);

		$sTables = '<div'.$this->pi_classParam('browsebox').'>'.
			($showResultCount ? '<p>'.sprintf(
				$label,
				$pR1,
				min(array($this->internal['res_count'],$pR2)),
				$this->internal['res_count']
				).$addString.'</p>':''
			).$addPart.'</div>';

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
	 * @param	array		Result row
	 * @param	boolean		If set, display only header of result (for sub-results)
	 * @return	array		Array with data to insert in result row template
	 */
	function prepareResultRowTemplateData($row, $headerOnly)	{

			// Initialize:
		$specRowConf = $this->getSpecialConfigForRow($row);
		$CSSsuffix = $specRowConf['CSSsuffix']?'-'.$specRowConf['CSSsuffix']:'';

			// If external media, link to the media-file instead.
		if ($row['item_type'])	{		// External media
			if ($row['show_resume'])	{	// Can link directly.
				$targetAttribute = '';
				if ($GLOBALS['TSFE']->config['config']['fileTarget']) {
					$targetAttribute = ' target="' . htmlspecialchars($GLOBALS['TSFE']->config['config']['fileTarget']) . '"';
				}
				$title = '<a href="' . htmlspecialchars($row['data_filename']) . '"' . $targetAttribute . '>' .
					htmlspecialchars($this->makeTitle($row)) .
					'</a>';
			} else {	// Suspicious, so linking to page instead...
				$copy_row = $row;
				unset($copy_row['cHashParams']);
				$title = $this->linkPage($row['page_id'],htmlspecialchars($this->makeTitle($row)),$copy_row);
			}
		} else {	// Else the page:

				// Prepare search words for markup in content:
			if ($this->conf['forwardSearchWordsInResultLink'])	{
				$markUpSwParams = array('no_cache' => 1);
				foreach ($this->sWArr as $d)	{
					$markUpSwParams['sword_list'][] = $d['sword'];
				}
			} else {
				$markUpSwParams = array();
			}
			$title = $this->linkPage($row['data_page_id'],htmlspecialchars($this->makeTitle($row)),$row,$markUpSwParams);
		}

		$tmplContent = array();
		$tmplContent['title'] = $title;
		$tmplContent['result_number'] = $this->conf['show.']['resultNumber'] ? $row['result_number'].': ' : '&nbsp;';
		$tmplContent['icon'] = $this->makeItemTypeIcon($row['item_type'],'',$specRowConf);
		$tmplContent['rating'] = $this->makeRating($row);
		$tmplContent['description'] = $this->makeDescription($row,$this->piVars['extResume'] && !$headerOnly?0:1);
		$tmplContent = $this->makeInfo($row,$tmplContent);
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
	 * @param	array		Array of search words
	 * @return	string		HTML telling what is searched for.
	 */
	function tellUsWhatIsSeachedFor($sWArr)	{

			// Init:
		$searchingFor = '';
		$c=0;

			// Traverse search words:
		foreach ($sWArr as $k => $v)	{
			if ($c)	{
				switch($v['oper'])	{
					case 'OR':
						$searchingFor.= ' '.$this->pi_getLL('searchFor_or','',1).' '.$this->wrapSW($this->utf8_to_currentCharset($v['sword']));
					break;
					case 'AND NOT':
						$searchingFor.= ' '.$this->pi_getLL('searchFor_butNot','',1).' '.$this->wrapSW($this->utf8_to_currentCharset($v['sword']));
					break;
					default:	// AND...
						$searchingFor.= ' '.$this->pi_getLL('searchFor_and','',1).' '.$this->wrapSW($this->utf8_to_currentCharset($v['sword']));
					break;
				}
			} else {
				$searchingFor = $this->pi_getLL('searchFor','',1).' '.$this->wrapSW($this->utf8_to_currentCharset($v['sword']));
			}
			$c++;
		}
		return $searchingFor;
	}

	/**
	 * Wraps the search words in the search-word list display (from ->tellUsWhatIsSeachedFor())
	 *
	 * @param	string		search word to wrap (in local charset!)
	 * @return	string		Search word wrapped in <span> tag.
	 */
	function wrapSW($str)	{
		return '"<span'.$this->pi_classParam('sw').'>'.htmlspecialchars($str).'</span>"';
	}

	/**
	 * Makes a selector box
	 *
	 * @param	string		Name of selector box
	 * @param	string		Current value
	 * @param	array		Array of options in the selector box (value => label pairs)
	 * @return	string		HTML of selector box
	 */
	function renderSelectBox($name,$value,$optValues)	{
		if (is_array($optValues))	{
			$opt = array();
			$isSelFlag = 0;

			foreach ($optValues as $k => $v)	{
				$sel = (!strcmp($k,$value) ? ' selected="selected"' : '');
				if ($sel)	$isSelFlag++;
				$opt[] = '<option value="'.htmlspecialchars($k).'"'.$sel.'>'.htmlspecialchars($v).'</option>';
			}

			return '<select name="'.$name.'">'.implode('',$opt).'</select>';
		}
	}

	/**
	 * Used to make the link for the result-browser.
	 * Notice how the links must resubmit the form after setting the new pointer-value in a hidden formfield.
	 *
	 * @param	string		String to wrap in <a> tag
	 * @param	integer		Pointer value
	 * @param	string		List of integers pointing to free indexing configurations to search. -1 represents no filtering, 0 represents TYPO3 pages only, any number above zero is a uid of an indexing configuration!
	 * @return	string		Input string wrapped in <a> tag with onclick event attribute set.
	 */
	function makePointerSelector_link($str,$p,$freeIndexUid)	{
		$onclick = 'document.getElementById(\'' . $this->prefixId . '_pointer\').value=\'' . $p . '\';' .
					'document.getElementById(\'' . $this->prefixId . '_freeIndexUid\').value=\'' . rawurlencode($freeIndexUid) . '\';' .
					'document.getElementById(\'' . $this->prefixId . '\').submit();return false;';
		return '<a href="#" onclick="'.htmlspecialchars($onclick).'">'.$str.'</a>';
	}

	/**
	 * Return icon for file extension
	 *
	 * @param	string		File extension / item type
	 * @param	string		Title attribute value in icon.
	 * @param	array		TypoScript configuration specifically for search result.
	 * @return	string		<img> tag for icon
	 */
	function makeItemTypeIcon($it, $alt='', $specRowConf) {

			// Build compound key if item type is 0, iconRendering is not used
			// and specConfs.[pid].pageIcon was set in TS
		if ($it === '0' && $specRowConf['_pid'] && is_array($specRowConf['pageIcon.']) && !is_array($this->conf['iconRendering.'])) {
			$it .= ':' . $specRowConf['_pid'];
		}
		if (!isset($this->iconFileNameCache[$it]))	{
			$this->iconFileNameCache[$it] = '';

				// If TypoScript is used to render the icon:
			if (is_array($this->conf['iconRendering.']))	{
				$this->cObj->setCurrentVal($it);
				$this->iconFileNameCache[$it] = $this->cObj->cObjGetSingle($this->conf['iconRendering'], $this->conf['iconRendering.']);
				// ... otherwise, get flag from sys_language record:
			} else {

					// Default creation / finding of icon:
				$icon = '';
				if ($it === '0' || substr($it, 0, 2) == '0:')	{
					if (is_array($specRowConf['pageIcon.']))	{
						$this->iconFileNameCache[$it] = $this->cObj->IMAGE($specRowConf['pageIcon.']);
					} else {
						$icon = 'EXT:indexed_search/pi/res/pages.gif';
					}
				} elseif ($this->external_parsers[$it]) {
					$icon = $this->external_parsers[$it]->getIcon($it);
				}

				if ($icon)	{
					$fullPath = t3lib_div::getFileAbsFileName($icon);

					if ($fullPath)	{
						$info = @getimagesize($fullPath);
						$iconPath = substr($fullPath, strlen(PATH_site));
						$this->iconFileNameCache[$it] = (is_array($info)) ? '<img src="' . $iconPath . '" ' . $info[3] . ' title="' . htmlspecialchars($alt) . '" alt="" />' : '';
					}
				}
			}
		}
		return $this->iconFileNameCache[$it];
	}

	/**
	 * Return the rating-HTML code for the result row. This makes use of the $this->firstRow
	 *
	 * @param	array		Result row array
	 * @return	string		String showing ranking value
	 */
	function makeRating($row)	{

		switch((string)$this->piVars['order'])	{
			case 'rank_count':		// Number of occurencies on page
				return $row['order_val'].' '.$this->pi_getLL('maketitle_matches');
			break;
			case 'rank_first':	// Close to top of page
				return ceil(t3lib_div::intInRange(255-$row['order_val'],1,255)/255*100).'%';
			break;
			case 'rank_flag':	// Based on priority assigned to <title> / <meta-keywords> / <meta-description> / <body>
				if ($this->firstRow['order_val2'])	{
					$base = $row['order_val1']*256; // (3 MSB bit, 224 is highest value of order_val1 currently)
					$freqNumber = $row['order_val2']/$this->firstRow['order_val2']*pow(2,12);	// 15-3 MSB = 12
					$total = t3lib_div::intInRange($base+$freqNumber,0,32767);
					#debug($total);
					return ceil(log($total)/log(32767)*100).'%';
				}
			break;
			case 'rank_freq':	// Based on frequency
				$max = 10000;
				$total = t3lib_div::intInRange($row['order_val'],0,$max);
#				debug($total);
				return ceil(log($total)/log($max)*100).'%';
			break;
			case 'crdate':	// Based on creation date
				return $this->cObj->calcAge($GLOBALS['EXEC_TIME'] - $row['item_crdate'],0); // ,$conf['age']
			break;
			case 'mtime':	// Based on modification time
				return $this->cObj->calcAge($GLOBALS['EXEC_TIME'] - $row['item_mtime'],0); // ,$conf['age']
			break;
			default:	// fx. title
				return '&nbsp;';
			break;
		}
	}

	/**
	 * Returns the resume for the search-result.
	 *
	 * @param	array		Search result row
	 * @param	boolean		If noMarkup is FALSE, then the index_fulltext table is used to select the content of the page, split it with regex to display the search words in the text.
	 * @param	integer		String length
	 * @return	string		HTML string		...
	 */
	function makeDescription($row,$noMarkup=0,$lgd=180)	{
		if ($row['show_resume'])	{
			if (!$noMarkup)	{
				$markedSW = '';
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'index_fulltext', 'phash='.intval($row['phash']));
				if ($ftdrow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
						// Cut HTTP references after some length
					$content = preg_replace('/(http:\/\/[^ ]{60})([^ ]+)/i', '$1...', $ftdrow['fulltextdata']);
					$markedSW = $this->markupSWpartsOfString($content);
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
			}

			if (!trim($markedSW))	{
				$outputStr = $GLOBALS['TSFE']->csConvObj->crop('utf-8',$row['item_description'],$lgd);
				$outputStr = htmlspecialchars($outputStr);
			}
			$output = $this->utf8_to_currentCharset($outputStr ? $outputStr : $markedSW);
		} else {
			$output = '<span class="noResume">'.$this->pi_getLL('res_noResume','',1).'</span>';
		}

		return $output;
	}

	/**
	 * Marks up the search words from $this->sWarr in the $str with a color.
	 *
	 * @param	string		Text in which to find and mark up search words. This text is assumed to be UTF-8 like the search words internally is.
	 * @return	string		Processed content.
	 */
	function markupSWpartsOfString($str)	{

			// Init:
		$str = str_replace('&nbsp;',' ',t3lib_parsehtml::bidir_htmlspecialchars($str,-1));
		$str = preg_replace('/\s\s+/',' ',$str);
		$swForReg = array();

			// Prepare search words for regex:
		foreach ($this->sWArr as $d)	{
			$swForReg[] = preg_quote($d['sword'],'/');
		}
		$regExString = '('.implode('|',$swForReg).')';

			// Split and combine:
		$parts = preg_split('/'.$regExString.'/i', ' '.$str.' ', 20000, PREG_SPLIT_DELIM_CAPTURE);
// debug($parts,$regExString);
			// Constants:
		$summaryMax = 300;
		$postPreLgd = 60;
		$postPreLgd_offset = 5;
		$divider = ' ... ';

		$occurencies = (count($parts)-1)/2;
		if ($occurencies)	{
			$postPreLgd = t3lib_div::intInRange($summaryMax/$occurencies,$postPreLgd,$summaryMax/2);
		}

			// Variable:
		$summaryLgd = 0;
		$output = array();

			// Shorten in-between strings:
		foreach ($parts as $k => $strP)	{
			if (($k%2)==0)	{

					// Find length of the summary part:
				$strLen = $GLOBALS['TSFE']->csConvObj->strlen('utf-8', $parts[$k]);
				$output[$k] = $parts[$k];

					// Possibly shorten string:
				if (!$k)	{	// First entry at all (only cropped on the frontside)
					if ($strLen > $postPreLgd)	{
						$output[$k] = $divider.preg_replace('/^[^[:space:]]+[[:space:]]/','',$GLOBALS['TSFE']->csConvObj->crop('utf-8',$parts[$k],-($postPreLgd-$postPreLgd_offset)));
					}
				} elseif ($summaryLgd > $summaryMax || !isset($parts[$k+1])) {	// In case summary length is exceed OR if there are no more entries at all:
					if ($strLen > $postPreLgd)	{
						$output[$k] = preg_replace('/[[:space:]][^[:space:]]+$/','',$GLOBALS['TSFE']->csConvObj->crop('utf-8',$parts[$k],$postPreLgd-$postPreLgd_offset)).$divider;
					}
				} else {	// In-between search words:
					if ($strLen > $postPreLgd*2)	{
						$output[$k] = preg_replace('/[[:space:]][^[:space:]]+$/','',$GLOBALS['TSFE']->csConvObj->crop('utf-8',$parts[$k],$postPreLgd-$postPreLgd_offset)).
										$divider.
										preg_replace('/^[^[:space:]]+[[:space:]]/','',$GLOBALS['TSFE']->csConvObj->crop('utf-8',$parts[$k],-($postPreLgd-$postPreLgd_offset)));
					}
				}
				$summaryLgd+= $GLOBALS['TSFE']->csConvObj->strlen('utf-8', $output[$k]);

					// Protect output:
				$output[$k] = htmlspecialchars($output[$k]);

					// If summary lgd is exceed, break the process:
				if ($summaryLgd > $summaryMax)	{
					break;
				}
			} else {
				$summaryLgd+= $GLOBALS['TSFE']->csConvObj->strlen('utf-8',$strP);
				$output[$k] = '<strong class="tx-indexedsearch-redMarkup">'.htmlspecialchars($parts[$k]).'</strong>';
			}
		}

			// Return result:
		return implode('',$output);
	}

	/**
	 * Returns the title of the search result row
	 *
	 * @param	array		Result row
	 * @return	string		Title from row
	 */
	function makeTitle($row)	{
		$add = '';

		if ($this->multiplePagesType($row['item_type']))	{
			$dat = unserialize($row['cHashParams']);

			$pp = explode('-',$dat['key']);
			if ($pp[0]!=$pp[1])	{
				$add=', '.$this->pi_getLL('word_pages').' '.$dat['key'];
			} else $add=', '.$this->pi_getLL('word_page').' '.$pp[0];
		}

		$outputString = $GLOBALS['TSFE']->csConvObj->crop('utf-8',$row['item_title'],50,'...');

		return $this->utf8_to_currentCharset($outputString).$add;
	}

	/**
	 * Returns the info-string in the bottom of the result-row display (size, dates, path)
	 *
	 * @param	array		Result row
	 * @param	array		Template array to modify
	 * @return	array		Modified template array
	 */
	function makeInfo($row,$tmplArray)	{
		$tmplArray['size'] = t3lib_div::formatSize($row['item_size']);
		$tmplArray['created'] = $this->formatCreatedDate($row['item_crdate']);
		$tmplArray['modified'] = $this->formatModifiedDate($row['item_mtime']);

		$pathId = $row['data_page_id']?$row['data_page_id']:$row['page_id'];
		$pathMP = $row['data_page_id']?$row['data_page_mp']:'';

		$pI = parse_url($row['data_filename']);
		if ($pI['scheme'])	{
			$targetAttribute = '';
			if ($GLOBALS['TSFE']->config['config']['fileTarget']) {
				$targetAttribute = ' target="' . htmlspecialchars($GLOBALS['TSFE']->config['config']['fileTarget']) . '"';
			}
			$tmplArray['path'] = '<a href="' . htmlspecialchars($row['data_filename']) . '"' . $targetAttribute . '>' .
				htmlspecialchars($row['data_filename']) .
				'</a>';
		} else {
			$pathStr = htmlspecialchars($this->getPathFromPageId($pathId,$pathMP));
			$tmplArray['path'] = $this->linkPage($pathId,$pathStr,array(
				'cHashParams' => $row['cHashParams'],
				'data_page_type' => $row['data_page_type'],
				'data_page_mp' => $pathMP,
				'sys_language_uid' => $row['sys_language_uid'],
			));
		}

		return $tmplArray;
	}

	/**
	 * Returns configuration from TypoScript for result row based on ID / location in page tree!
	 *
	 * @param	array		Result row
	 * @return	array		Configuration array
	 */
	function getSpecialConfigForRow($row)	{
		$pathId = $row['data_page_id'] ? $row['data_page_id'] : $row['page_id'];
		$pathMP = $row['data_page_id'] ? $row['data_page_mp'] : '';

		$rl = $this->getRootLine($pathId,$pathMP);
		$specConf = $this->conf['specConfs.']['0.'];
		if (is_array($rl))	{
			foreach ($rl as $dat)	{
				if (is_array($this->conf['specConfs.'][$dat['uid'].'.']))	{
					$specConf = $this->conf['specConfs.'][$dat['uid'].'.'];
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
	 * @param	array		Result row
	 * @return	string		HTML code for result row.
	 */
	function makeLanguageIndication($row)	{

			// If search result is a TYPO3 page:
		if ((string)$row['item_type']==='0')	{

				// If TypoScript is used to render the flag:
			if (is_array($this->conf['flagRendering.']))	{
				$this->cObj->setCurrentVal($row['sys_language_uid']);
				return $this->cObj->cObjGetSingle($this->conf['flagRendering'],$this->conf['flagRendering.']);
			} else { // ... otherwise, get flag from sys_language record:

					// Get sys_language record
				$rowDat = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'sys_language', 'uid=' . intval($row['sys_language_uid']) . ' ' . $this->cObj->enableFields('sys_language'));

					// Flag code:
				$flag = $rowDat['flag'];
				if ($flag)	{

// FIXME not all flags from typo3/gfx/flags are available in media/flags/
					$file = substr(PATH_tslib,strlen(PATH_site)).'media/flags/flag_'.$flag;
					$imgInfo = @getimagesize(PATH_site.$file);

// original
#					$file = TYPO3_mainDir.'gfx/flags/'.$flag;
#					$imgInfo = @getimagesize(PATH_site.$file);

					if (is_array($imgInfo))	{
						$output = '<img src="' . $file . '" ' . $imgInfo[3] . ' title="' . htmlspecialchars($rowDat['title']) . '" alt="' . htmlspecialchars($rowDat['title']) . '" />';
						return $output;
					}
				}
			}
		}
		return '&nbsp;';
	}

	/**
	 * Returns the HTML code for the locking symbol.
	 * NOTICE: Requires a call to ->getPathFromPageId() first in order to work (done in ->makeInfo() by calling that first)
	 *
	 * @param	integer		Page id for which to find answer
	 * @return	string		<img> tag if access is limited.
	 */
	function makeAccessIndication($id)	{
		if (is_array($this->fe_groups_required[$id]) && count($this->fe_groups_required[$id]))	{
			return '<img src="'.t3lib_extMgm::siteRelPath('indexed_search').'pi/res/locked.gif" width="12" height="15" vspace="5" title="'.sprintf($this->pi_getLL('res_memberGroups','',1),implode(',',array_unique($this->fe_groups_required[$id]))).'" alt="" />';
		}
	}

	/**
	 * Links the $str to page $id
	 *
	 * @param	integer		Page id
	 * @param	string		Title String to link
	 * @param	array		Result row
	 * @param	array		Additional parameters for marking up seach words
	 * @return	string		<A> tag wrapped title string.
	 */
	function linkPage($id,$str,$row=array(),$markUpSwParams=array())	{

			// Parameters for link:
		$urlParameters = (array)unserialize($row['cHashParams']);

			// Add &type and &MP variable:
		if ($row['data_page_type']) $urlParameters['type'] = $row['data_page_type'];
		if ($row['data_page_mp']) $urlParameters['MP'] = $row['data_page_mp'];
		if ($row['sys_language_uid']) $urlParameters['L'] = $row['sys_language_uid'];

			// markup-GET vars:
		$urlParameters = array_merge($urlParameters, $markUpSwParams);

			// This will make sure that the path is retrieved if it hasn't been already. Used only for the sake of the domain_record thing...
		if (!is_array($this->domain_records[$id]))	{
			$this->getPathFromPageId($id);
		}

			// If external domain, then link to that:
		if (count($this->domain_records[$id]))	{
			reset($this->domain_records[$id]);
			$firstDom = current($this->domain_records[$id]);
			$scheme = t3lib_div::getIndpEnv('TYPO3_SSL') ? 'https://' : 'http://';

			$addParams = '';
			if (is_array($urlParameters))	{
				if (count($urlParameters))	{
					$addParams.= t3lib_div::implodeArrayForUrl('',$urlParameters);
				}
			}

			if ($target=$this->conf['search.']['detect_sys_domain_records.']['target'])	{
				$target = ' target="'.$target.'"';
			}
			return '<a href="'.htmlspecialchars($scheme.$firstDom.'/index.php?id='.$id.$addParams).'"'.$target.'>'.htmlspecialchars($str).'</a>';
		} else {
			return $this->pi_linkToPage($str,$id,$this->conf['result_link_target'],$urlParameters);
		}
	}

	/**
	 * Returns the path to the page $id
	 *
	 * @param	integer		Page ID
	 * @param	string		MP variable content.
	 * @return	string		Root line for result.
	 */
	function getRootLine($id,$pathMP='')	{
		$identStr = $id.'|'.$pathMP;

		if (!isset($this->cache_path[$identStr]))	{
			$this->cache_rl[$identStr] = $GLOBALS['TSFE']->sys_page->getRootLine($id,$pathMP);
		}
		return $this->cache_rl[$identStr];
	}

	/**
	 * Gets the first sys_domain record for the page, $id
	 *
	 * @param	integer		Page id
	 * @return	string		Domain name
	 */
	function getFirstSysDomainRecordForPage($id)	{
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('domainName', 'sys_domain', 'pid='.intval($id).$this->cObj->enableFields('sys_domain'), '', 'sorting');
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		return rtrim($row['domainName'], '/');
	}

	/**
	 * Returns the path to the page $id
	 *
	 * @param	integer		Page ID
	 * @param	string		MP variable content
	 * @return	string		Path
	 */
	function getPathFromPageId($id,$pathMP='')	{

		$identStr = $id.'|'.$pathMP;

		if (!isset($this->cache_path[$identStr]))	{
			$this->fe_groups_required[$id] = array();
			$this->domain_records[$id] = array();
			$rl = $this->getRootLine($id,$pathMP);
			$hitRoot = 0;
			$path = '';
			if (is_array($rl) && count($rl))	{
				foreach ($rl as $k => $v) {
						// Check fe_user
					if ($v['fe_group'] && ($v['uid']==$id || $v['extendToSubpages']))	{
						$this->fe_groups_required[$id][]=$v['fe_group'];
					}
						// Check sys_domain.
					if ($this->conf['search.']['detect_sys_domain_records'])	{
						$sysDName = $this->getFirstSysDomainRecordForPage($v['uid']);
						if ($sysDName)	{
							$this->domain_records[$id][] = $sysDName;
								// Set path accordingly:
							$path = $sysDName.$path;
							break;
						}
					}

						// Stop, if we find that the current id is the current root page.
					if ($v['uid']==$GLOBALS['TSFE']->config['rootLine'][0]['uid'])		{
						break;
					}
					$path = '/'.$v['title'].$path;
				}
			}

			$this->cache_path[$identStr] = $path;

			if (is_array($this->conf['path_stdWrap.']))	{
				$this->cache_path[$identStr] = $this->cObj->stdWrap($this->cache_path[$identStr], $this->conf['path_stdWrap.']);
			}
		}

		return $this->cache_path[$identStr];
	}

	/**
	 * Return the menu of pages used for the selector.
	 *
	 * @param	integer		Page ID for which to return menu
	 * @return	array		Menu items (for making the section selector box)
	 */
	function getMenu($id)	{
		if ($this->conf['show.']['LxALLtypes'])	{
			$output = Array();
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('title,uid', 'pages', 'pid='.intval($id).$this->cObj->enableFields('pages'), '', 'sorting');
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				$output[$row['uid']] = $GLOBALS['TSFE']->sys_page->getPageOverlay($row);
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
			return $output;
		} else {
			return $GLOBALS['TSFE']->sys_page->getMenu($id);
		}
	}

	/**
	 * Returns if an item type is a multipage item type
	 *
	 * @param	string		Item type
	 * @return	boolean		TRUE if multipage capable
	 */
	function multiplePagesType($item_type)	{
		return is_object($this->external_parsers[$item_type]) && $this->external_parsers[$item_type]->isMultiplePageExtension($item_type);
	}

	/**
	 * Converts the input string from utf-8 to the backend charset.
	 *
	 * @param	string		String to convert (utf-8)
	 * @return	string		Converted string (backend charset if different from utf-8)
	 */
	function utf8_to_currentCharset($str)	{
		return $GLOBALS['TSFE']->csConv($str,'utf-8');
	}

	/**
	 * Returns an object reference to the hook object if any
	 *
	 * @param	string		Name of the function you want to call / hook key
	 * @return	object		Hook object, if any. Otherwise NULL.
	 */
	function hookRequest($functionName) {
		global $TYPO3_CONF_VARS;

			// Hook: menuConfig_preProcessModMenu
		if ($TYPO3_CONF_VARS['EXTCONF']['indexed_search']['pi1_hooks'][$functionName]) {
			$hookObj = t3lib_div::getUserObj($TYPO3_CONF_VARS['EXTCONF']['indexed_search']['pi1_hooks'][$functionName]);
			if (method_exists ($hookObj, $functionName)) {
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
	protected function getSearchFormActionURL() {
		$targetUrlPid = $this->getSearchFormActionPidFromTS();
		if ($targetUrlPid == 0) {
			$targetUrlPid = $GLOBALS['TSFE']->id;
		}
		return $this->pi_getPageLink($targetUrlPid, $GLOBALS['TSFE']->sPre);
	}

	/**
	 * Obtains search form target pid from the TypoScript configuration
	 *
	 * @return int
	 */
	protected function getSearchFormActionPidFromTS() {
		$result = 0;
		if (isset($this->conf['search.']['targetPid']) || isset($this->conf['search.']['targetPid.'])) {
			if (is_array($this->conf['search.']['targetPid.'])) {
				$result = $this->cObj->stdWrap($this->conf['search.']['targetPid'], $this->conf['search.']['targetPid.']);
			}
			else {
				$result = $this->conf['search.']['targetPid'];
			}
			$result = intval($result);
		}
		return $result;
	}

	/**
	 * Formats date as 'created' date
	 *
	 * @param int $date
	 * @param string $defaultFormat
	 * @return string
	 */
	protected function formatCreatedDate($date) {
		$defaultFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'];
		return $this->formatDate($date, 'created', $defaultFormat);
	}

	/**
	 * Formats date as 'modified' date
	 *
	 * @param int $date
	 * @param string $defaultFormat
	 * @return string
	 */
	protected function formatModifiedDate($date) {
		$defaultFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] . ' ' .
			$GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'];
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
	protected function formatDate($date, $tsKey, $defaultFormat) {
		$strftimeFormat = $this->conf['dateFormat.'][$tsKey];
		if ($strftimeFormat) {
			$result = strftime($strftimeFormat, $date);
		}
		else {
			$result = date($defaultFormat, $date);
		}
		return $result;
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/indexed_search/pi/class.tx_indexedsearch.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/indexed_search/pi/class.tx_indexedsearch.php']);
}

?>