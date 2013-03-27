<?php
namespace TYPO3\CMS\IndexedSearch\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Benjamin Mack (benni@typo3.org)
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
 * @author 	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author 	Christian Jul Jensen <christian@typo3.com>
 * @author 	Benjamin Mack <benni@typo3.org>
 */
class SearchController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	// previously known as $this->piVars['sword']
	protected $sword = NULL;

	protected $searchWords = array();

	protected $searchData;

	// This is the id of the site root.
	// This value may be a commalist of integer (prepared for this)
	// Root-page PIDs to search in (rl0 field where clause, see initialize() function)
	protected $searchRootPageIdList = 0;

	protected $defaultResultNumber = 10;

	/**
	 * Lexer object
	 *
	 * @var \TYPO3\CMS\IndexedSearch\Domain\Repository\IndexSearchRepository
	 */
	protected $searchRepository = NULL;

	/**
	 * Lexer object
	 *
	 * @var \TYPO3\CMS\IndexedSearch\Lexer
	 */
	protected $lexerObj;

	// External parser objects
	protected $externalParsers = array();

	// Will hold the first row in result - used to calculate relative hit-ratings.
	protected $firstRow = array();

	// Domain records (needed ?)
	protected $domainRecords = array();

	// Required fe_groups memberships for display of a result.
	protected $requiredFrontendUsergroups = array();

	// Page tree sections for search result.
	protected $resultSections = array();

	// Caching of page path
	protected $pathCache = array();

	// Storage of icons
	protected $iconFileNameCache = array();

	// Indexer configuration, coming from $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['indexed_search']
	protected $indexerConfig = array();

	/**
	 * sets up all necessary object for searching
	 *
	 * @param array $searchData The incoming search parameters
	 * @return array Search parameters
	 */
	public function initialize($searchData = array()) {
		if (!is_array($searchData)) {
			$searchData = array();
		}
		// setting default values
		if (is_array($this->settings['defaultOptions'])) {
			$searchData = array_merge($this->settings['defaultOptions'], $searchData);
		}
		// Indexer configuration from Extension Manager interface:
		$this->indexerConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['indexed_search']);
		$this->enableMetaphoneSearch = $this->indexerConfig['enableMetaphoneSearch'] ? TRUE : FALSE;
		$this->initializeExternalParsers();
		// If "_sections" is set, this value overrides any existing value.
		if ($searchData['_sections']) {
			$searchData['sections'] = $searchData['_sections'];
		}
		// If "_sections" is set, this value overrides any existing value.
		if ($searchData['_freeIndexUid'] !== '' && $searchData['_freeIndexUid'] !== '_') {
			$searchData['freeIndexUid'] = $searchData['_freeIndexUid'];
		}
		$searchData['results'] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($searchData['results'], 1, 100000, $this->defaultResultNumber);
		// This gets the search-words into the $searchWordArray
		$this->sword = $searchData['sword'];
		// Add previous search words to current
		if ($searchData['sword_prev_include'] && $searchData['sword_prev']) {
			$this->sword = trim($searchData['sword_prev']) . ' ' . $this->sword;
		}
		$this->searchWords = $this->getSearchWords($searchData['defaultOperand']);
		// This is the id of the site root.
		// This value may be a commalist of integer (prepared for this)
		$this->searchRootPageIdList = intval($GLOBALS['TSFE']->config['rootLine'][0]['uid']);
		// Setting the list of root PIDs for the search. Notice, these page IDs MUST
		// have a TypoScript template with root flag on them! Basically this list is used
		// to select on the "rl0" field and page ids are registered as "rl0" only if
		// a TypoScript template record with root flag is there.
		// This happens AFTER the use of $this->searchRootPageIdList above because
		// the above will then fetch the menu for the CURRENT site - regardless
		// of this kind of searching here. Thus a general search will lookup in
		// the WHOLE database while a specific section search will take the current sections.
		if ($this->settings['rootPidList']) {
			$this->searchRootPageIdList = implode(',', \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $this->settings['rootPidList']));
		}
		$this->searchRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\IndexedSearch\\Domain\\Repository\\IndexSearchRepository');
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
	 * @dontvalidate $search
	 */
	public function searchAction($search = array()) {
		$searchData = $this->initialize($search);
		// Find free index uid:
		$freeIndexUid = $searchData['freeIndexUid'];
		if ($freeIndexUid == -2) {
			$freeIndexUid = $this->settings['defaultFreeIndexUidList'];
		} elseif (!isset($searchData['freeIndexUid'])) {
			// index configuration is disabled
			$freeIndexUid = -1;
		}
		$indexCfgs = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $freeIndexUid);
		$resultsets = array();
		foreach ($indexCfgs as $freeIndexUid) {
			// Get result rows
			$tstamp1 = \TYPO3\CMS\Core\Utility\GeneralUtility::milliseconds();
			if ($hookObj = $this->hookRequest('getResultRows')) {
				$resultData = $hookObj->getResultRows($this->searchWords, $freeIndexUid);
			} else {
				$resultData = $this->searchRepository->doSearch($this->searchWords, $freeIndexUid);
			}
			// Display search results
			$tstamp2 = \TYPO3\CMS\Core\Utility\GeneralUtility::milliseconds();
			if ($hookObj = $this->hookRequest('getDisplayResults')) {
				$resultsets[$freeIndexUid] = $hookObj->getDisplayResults($this->searchWords, $resultData, $freeIndexUid);
			} else {
				$resultsets[$freeIndexUid] = $this->getDisplayResults($this->searchWords, $resultData, $freeIndexUid);
			}
			$tstamp3 = \TYPO3\CMS\Core\Utility\GeneralUtility::milliseconds();
			// Create header if we are searching more than one indexing configuration
			if (count($indexCfgs) > 1) {
				if ($freeIndexUid > 0) {
					$indexCfgRec = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('title', 'index_config', 'uid=' . intval($freeIndexUid) . $GLOBALS['TSFE']->cObj->enableFields('index_config'));
					$categoryTitle = $indexCfgRec['title'];
				} else {
					$categoryTitle = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('indexingConfigurationHeader.' . $freeIndexUid, 'indexed_search');
				}
				$resultsets[$freeIndexUid]['categoryTitle'] = $categoryTitle;
			}
			// Write search statistics
			$this->writeSearchStat($searchData, $this->searchWords, $resultData['count'], array($tstamp1, $tstamp2, $tstamp3));
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
	 * @param integer $freeIndexUid Pointing to which indexing configuration you want to search in. -1 means no filtering. 0 means only regular indexed content.
	 * @return array
	 */
	protected function getDisplayResults($searchWords, $resultData, $freeIndexUid = -1) {
		$result = array(
			'count' => $resultData['count'],
			'searchWords' => $searchWords
		);
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
					$result['sectionText'] = sprintf(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('result.' . (count($this->resultSections) > 1 ? 'inNsections' : 'inNsection'), 'indexed_search'), count($this->resultSections));
				}
			}
		}
		// Print a message telling which words in which sections we searched for
		if (substr($this->searchData['sections'], 0, 2) == 'rl') {
			$result['searchedInSectionInfo'] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('result.inSection', 'indexed_search') . ' "' . substr($this->getPathFromPageId(substr($this->searchData['sections'], 4)), 1) . '"';
		}
		return $result;
	}

	/**
	 * Takes the array with resultrows as input and returns the result-HTML-code
	 * Takes the "group" var into account: Makes a "section" or "flat" display.
	 *
	 * @param array $resultRows Result rows
	 * @param integer $freeIndexUid Pointing to which indexing configuration you want to search in. -1 means no filtering. 0 means only regular indexed content.
	 * @return string HTML
	 */
	protected function compileResultRows($resultRows, $freeIndexUid = -1) {
		$finalResultRows = array();
		// Transfer result rows to new variable,
		// performing some mapping of sub-results etc.
		$newResultRows = array();
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
		$this->resultSections = array();
		if ($freeIndexUid <= 0 && $this->searchData['group'] == 'sections') {
			$rl2flag = substr($this->searchData['sections'], 0, 2) == 'rl';
			$sections = array();
			foreach ($resultRows as $row) {
				$id = $row['rl0'] . '-' . $row['rl1'] . ($rl2flag ? '-' . $row['rl2'] : '');
				$sections[$id][] = $row;
			}
			$this->resultSections = array();
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
					$sectionTitleLinked = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('result.unnamedSection', 'indexed_search') . ':';
				} else {
					$onclick = 'document.' . $this->prefixId . '[\'' . $this->prefixId . '[_sections]\'].value=\'' . $theRLid . '\';document.' . $this->prefixId . '.submit();return false;';
					$sectionTitleLinked = '<a href="#" onclick="' . htmlspecialchars($onclick) . '">' . htmlspecialchars($sectionName) . ':</a>';
				}
				$this->resultSections[$id] = array($sectionName, count($resultRows));
				// Add section header
				$finalResultRows[] = array(
					'isSectionHeader' => TRUE,
					'numResultRows' => count($resultRows),
					'anchorName' => 'anchor_' . md5($id),
					'sectionTitle' => $sectionTitleLinked
				);
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
	 * @param integer $headerOnly 1=Display only header (for sub-rows!), 2=nothing at all
	 * @return string HTML code
	 */
	protected function compileSingleResultRow($row, $headerOnly = 0) {
		$specRowConf = $this->getSpecialConfigForResultRow($row);
		$resultData = $row;
		$resultData['headerOnly'] = $headerOnly;
		$resultData['CSSsuffix'] = $specRowConf['CSSsuffix'] ? '-' . $specRowConf['CSSsuffix'] : '';
		if ($this->multiplePagesType($row['item_type'])) {
			$dat = unserialize($row['cHashParams']);
			$pp = explode('-', $dat['key']);
			if ($pp[0] != $pp[1]) {
				$resultData['titleaddition'] = ', ' . \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('result.page', 'indexed_search') . ' ' . $dat['key'];
			} else {
				$resultData['titleaddition'] = ', ' . \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('result.pages', 'indexed_search') . ' ' . $pp[0];
			}
		}
		$title = $resultData['item_title'] . $resultData['titleaddition'];
		$title = htmlspecialchars($title);
		// If external media, link to the media-file instead.
		if ($row['item_type']) {
			if ($row['show_resume']) {
				// Can link directly.
				$targetAttribute = '';
				if ($GLOBALS['TSFE']->config['config']['fileTarget']) {
					$targetAttribute = ' target="' . htmlspecialchars($GLOBALS['TSFE']->config['config']['fileTarget']) . '"';
				}
				$title = '<a href="' . htmlspecialchars($row['data_filename']) . '"' . $targetAttribute . '>' . $title . '</a>';
			} else {
				// Suspicious, so linking to page instead...
				$copiedRow = $row;
				unset($copiedRow['cHashParams']);
				$title = $this->linkPage($row['page_id'], $title, $copiedRow);
			}
		} else {
			// Else the page:
			// Prepare search words for markup in content:
			if ($this->settings['forwardSearchWordsInResultLink']) {
				$markUpSwParams = array('no_cache' => 1);
				foreach ($this->sWArr as $d) {
					$markUpSwParams['sword_list'][] = $d['sword'];
				}
			} else {
				$markUpSwParams = array();
			}
			$title = $this->linkPage($row['data_page_id'], $title, $row, $markUpSwParams);
		}
		$resultData['title'] = $title;
		$resultData['icon'] = $this->makeItemTypeIcon($row['item_type'], '', $specRowConf);
		$resultData['rating'] = $this->makeRating($row);
		$resultData['description'] = $this->makeDescription($row, $this->searchData['extResume'] && !$headerOnly ? 0 : 1);
		$resultData['language'] = $this->makeLanguageIndication($row);
		$resultData['size'] = \TYPO3\CMS\Core\Utility\GeneralUtility::formatSize($row['item_size']);
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
			$pathId = $row['data_page_id'] ? $row['data_page_id'] : $row['page_id'];
			$pathMP = $row['data_page_id'] ? $row['data_page_mp'] : '';
			$pathStr = htmlspecialchars($this->getPathFromPageId($pathId, $pathMP));
			$resultData['path'] = $this->linkPage($pathId, $pathStr, array(
				'cHashParams' => $row['cHashParams'],
				'data_page_type' => $row['data_page_type'],
				'data_page_mp' => $pathMP,
				'sys_language_uid' => $row['sys_language_uid']
			));
			// check if the access is restricted
			if (is_array($this->requiredFrontendUsergroups[$id]) && count($this->requiredFrontendUsergroups[$id])) {
				$resultData['access'] = '<img src="' . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('indexed_search') . 'pi/res/locked.gif" width="12" height="15" vspace="5" title="' . sprintf(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('result.memberGroups', 'indexed_search'), implode(',', array_unique($this->requiredFrontendUsergroups[$id]))) . '" alt="" />';
			}
		}
		// If there are subrows (eg. subpages in a PDF-file or if a duplicate page
		// is selected due to user-login (phash_grouping))
		if (is_array($row['_sub'])) {
			$resultData['subresults'] = array();
			if ($this->multiplePagesType($row['item_type'])) {
				$resultData['subresults']['header'] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('result.otherMatching', 'indexed_search');
				foreach ($row['_sub'] as $subRow) {
					$resultData['subresults']['items'][] = $this->compileSingleResultRow($subRow, 1);
				}
			} else {
				$resultData['subresults']['header'] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('result.otherMatching', 'indexed_search');
				$resultData['subresults']['info'] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('result.otherPageAsWell', 'indexed_search');
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
	protected function getSpecialConfigForResultRow($row) {
		$pathId = $row['data_page_id'] ? $row['data_page_id'] : $row['page_id'];
		$pathMP = $row['data_page_id'] ? $row['data_page_mp'] : '';
		$rl = $GLOBALS['TSFE']->sys_page->getRootLine($pathId, $pathMP);
		$specConf = $this->settings['specialConfiguration.']['0.'];
		if (is_array($rl)) {
			foreach ($rl as $dat) {
				if (is_array($this->conf['specialConfiguration.'][$dat['uid'] . '.'])) {
					$specConf = $this->conf['specialConfiguration.'][$dat['uid'] . '.'];
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
	protected function makeRating($row) {
		switch ((string) $this->searchData['sortOrder']) {
		case 'rank_count':
			return $row['order_val'] . ' ' . \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('result.ratingMatches', 'indexed_search');
			break;
		case 'rank_first':
			return ceil(\TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange((255 - $row['order_val']), 1, 255) / 255 * 100) . '%';
			break;
		case 'rank_flag':
			if ($this->firstRow['order_val2']) {
				// (3 MSB bit, 224 is highest value of order_val1 currently)
				$base = $row['order_val1'] * 256;
				// 15-3 MSB = 12
				$freqNumber = $row['order_val2'] / $this->firstRow['order_val2'] * pow(2, 12);
				$total = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($base + $freqNumber, 0, 32767);
				return ceil(log($total) / log(32767) * 100) . '%';
			}
			break;
		case 'rank_freq':
			$max = 10000;
			$total = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($row['order_val'], 0, $max);
			return ceil(log($total) / log($max) * 100) . '%';
			break;
		case 'crdate':
			return $this->cObj->calcAge($GLOBALS['EXEC_TIME'] - $row['item_crdate'], 0);
			break;
		case 'mtime':
			return $this->cObj->calcAge($GLOBALS['EXEC_TIME'] - $row['item_mtime'], 0);
			break;
		default:
			return ' ';
			break;
		}
	}

	/**
	 * Returns the HTML code for language indication.
	 *
	 * @param array $row Result row
	 * @return string HTML code for result row.
	 */
	protected function makeLanguageIndication($row) {
		$output = '&nbsp;';
		// If search result is a TYPO3 page:
		if ((string) $row['item_type'] === '0') {
			// If TypoScript is used to render the flag:
			if (is_array($this->settings['flagRendering.'])) {
				$cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
				$cObj->setCurrentVal($row['sys_language_uid']);
				$output = $cObj->cObjGetSingle($this->settings['flagRendering'], $this->settings['flagRendering.']);
			} else {
				// ... otherwise, get flag from sys_language record:
				$languageRow = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('flag, title', 'sys_language', 'uid=' . intval($row['sys_language_uid']) . $GLOBALS['TSFE']->cObj->enableFields('sys_language'));
				// Flag code:
				$flag = $languageRow['flag'];
				if ($flag) {
					// FIXME not all flags from typo3/gfx/flags
					// are available in media/flags/
					$file = substr(PATH_tslib, strlen(PATH_site)) . 'media/flags/flag_' . $flag;
					$imgInfo = @getimagesize((PATH_site . $file));
					if (is_array($imgInfo)) {
						$output = '<img src="' . $file . '" ' . $imgInfo[3] . ' title="' . htmlspecialchars($languageRow['title']) . '" alt="' . htmlspecialchars($languageRow['title']) . '" />';
					}
				}
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
	 * @todo Define visibility
	 */
	public function makeItemTypeIcon($imageType, $alt, $specRowConf) {
		// Build compound key if item type is 0, iconRendering is not used
		// and specConfs.[pid].pageIcon was set in TS
		if ($imageType === '0' && $specRowConf['_pid'] && is_array($specRowConf['pageIcon.']) && !is_array($this->settings['iconRendering.'])) {
			$imageType .= ':' . $specRowConf['_pid'];
		}
		if (!isset($this->iconFileNameCache[$imageType])) {
			$this->iconFileNameCache[$imageType] = '';
			// If TypoScript is used to render the icon:
			if (is_array($this->settings['iconRendering.'])) {
				$this->cObj->setCurrentVal($imageType);
				$this->iconFileNameCache[$imageType] = $this->cObj->cObjGetSingle($this->settings['iconRendering'], $this->settings['iconRendering.']);
			} else {
				// Default creation / finding of icon:
				$icon = '';
				if ($imageType === '0' || substr($imageType, 0, 2) == '0:') {
					if (is_array($specRowConf['pageIcon.'])) {
						$this->iconFileNameCache[$imageType] = $this->cObj->IMAGE($specRowConf['pageIcon.']);
					} else {
						$icon = 'EXT:indexed_search/pi/res/pages.gif';
					}
				} elseif ($this->externalParsers[$imageType]) {
					$icon = $this->externalParsers[$imageType]->getIcon($imageType);
				}
				if ($icon) {
					$fullPath = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($icon);
					if ($fullPath) {
						$info = @getimagesize($fullPath);
						$iconPath = substr($fullPath, strlen(PATH_site));
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
	 * @param boolean $noMarkup If noMarkup is FALSE, then the index_fulltext table is used to select the content of the page, split it with regex to display the search words in the text.
	 * @param integer $length String length
	 * @return string HTML string
	 * @todo overwork this
	 */
	protected function makeDescription($row, $noMarkup = FALSE, $length = 180) {
		if ($row['show_resume']) {
			if (!$noMarkup) {
				$markedSW = '';
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'index_fulltext', 'phash=' . intval($row['phash']));
				if ($ftdrow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					// Cut HTTP references after some length
					$content = preg_replace('/(http:\\/\\/[^ ]{60})([^ ]+)/i', '$1...', $ftdrow['fulltextdata']);
					$markedSW = $this->markupSWpartsOfString($content);
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
			}
			if (!trim($markedSW)) {
				$outputStr = $GLOBALS['TSFE']->csConvObj->crop('utf-8', $row['item_description'], $length);
				$outputStr = htmlspecialchars($outputStr);
			}
			$output = $outputStr ? $outputStr : $markedSW;
			$output = $GLOBALS['TSFE']->csConv($output, 'utf-8');
		} else {
			$output = '<span class="noResume">' . \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('result.noResume', 'indexed_search') . '</span>';
		}
		return $output;
	}

	/**
	 * Marks up the search words from $this->sWarr in the $str with a color.
	 *
	 * @param string $str Text in which to find and mark up search words. This text is assumed to be UTF-8 like the search words internally is.
	 * @return string Processed content
	 */
	protected function markupSWpartsOfString($str) {
		// Init:
		$str = str_replace('&nbsp;', ' ', \TYPO3\CMS\Core\Html\HtmlParser::bidir_htmlspecialchars($str, -1));
		$str = preg_replace('/\\s\\s+/', ' ', $str);
		$swForReg = array();
		// Prepare search words for regex:
		foreach ($this->sWArr as $d) {
			$swForReg[] = preg_quote($d['sword'], '/');
		}
		$regExString = '(' . implode('|', $swForReg) . ')';
		// Split and combine:
		$parts = preg_split('/' . $regExString . '/i', ' ' . $str . ' ', 20000, PREG_SPLIT_DELIM_CAPTURE);
		// Constants:
		$summaryMax = 300;
		$postPreLgd = 60;
		$postPreLgd_offset = 5;
		$divider = ' ... ';
		$occurencies = (count($parts) - 1) / 2;
		if ($occurencies) {
			$postPreLgd = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($summaryMax / $occurencies, $postPreLgd, $summaryMax / 2);
		}
		// Variable:
		$summaryLgd = 0;
		$output = array();
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
				} elseif ($summaryLgd > $summaryMax || !isset($parts[($k + 1)])) {
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
	 * @param 	array		search params
	 * @param 	array		Search Word array
	 * @param 	integer		Number of hits
	 * @param 	integer		Milliseconds the search took
	 * @return 	void
	 */
	protected function writeSearchStat($searchParams, $searchWords, $count, $pt) {
		$insertFields = array(
			'searchstring' => $this->sword,
			'searchoptions' => serialize(array($searchParams, $searchWords, $pt)),
			'feuser_id' => intval($GLOBALS['TSFE']->fe_user->user['uid']),
			// cookie as set or retrieved. If people has cookies disabled this will vary all the time
			'cookie' => $GLOBALS['TSFE']->fe_user->id,
			// Remote IP address
			'IP' => \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR'),
			// Number of hits on the search
			'hits' => intval($count),
			// Time stamp
			'tstamp' => $GLOBALS['EXEC_TIME']
		);
		$GLOBALS['TYPO3_DB']->exec_INSERTquery('index_stat_search', $insertFields);
		$newId = $GLOBALS['TYPO3_DB']->sql_insert_id();
		if ($newId) {
			foreach ($searchWords as $val) {
				$insertFields = array(
					'word' => $val['sword'],
					'index_stat_search_id' => $newId,
					// Time stamp
					'tstamp' => $GLOBALS['EXEC_TIME'],
					// search page id for indexed search stats
					'pageid' => $GLOBALS['TSFE']->id
				);
				$GLOBALS['TYPO3_DB']->exec_INSERTquery('index_stat_word', $insertFields);
			}
		}
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
	 * @param boolean $defaultOperator If TRUE, the default operator will be OR, not AND
	 * @return array Search words if any found
	 */
	protected function getSearchWords($defaultOperator) {
		// Shorten search-word string to max 200 bytes (does NOT take multibyte charsets into account - but never mind, shortening the string here is only a run-away feature!)
		$searchWords = substr($this->sword, 0, 200);
		// Convert to UTF-8 + conv. entities (was also converted during indexing!)
		$searchWords = $GLOBALS['TSFE']->csConvObj->utf8_encode($searchWords, $GLOBALS['TSFE']->metaCharset);
		$searchWords = $GLOBALS['TSFE']->csConvObj->entities_to_utf8($searchWords, TRUE);
		$sWordArray = FALSE;
		if ($hookObj = $this->hookRequest('getSearchWords')) {
			$sWordArray = $hookObj->getSearchWords_splitSWords($searchWords, $defaultOperator);
		} else {
			// sentence
			if ($this->searchDat['searchType'] == 20) {
				$sWordArray = array(
					array(
						'sword' => trim($searchWords),
						'oper' => 'AND'
					)
				);
			} else {
				// case-sensitive. Defines the words, which will be
				// operators between words
				$operatorTranslateTable = array(
					array('+', 'AND'),
					array('|', 'OR'),
					array('-', 'AND NOT'),
					// Add operators for various languages
					// Converts the operators to UTF-8 and lowercase
					array($GLOBALS['TSFE']->csConvObj->conv_case('utf-8', $GLOBALS['TSFE']->csConvObj->utf8_encode(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('localizedOperandAnd', 'indexed_search'), $GLOBALS['TSFE']->renderCharset), 'toLower'), 'AND'),
					array($GLOBALS['TSFE']->csConvObj->conv_case('utf-8', $GLOBALS['TSFE']->csConvObj->utf8_encode(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('localizedOperandOr', 'indexed_search'), $GLOBALS['TSFE']->renderCharset), 'toLower'), 'OR'),
					array($GLOBALS['TSFE']->csConvObj->conv_case('utf-8', $GLOBALS['TSFE']->csConvObj->utf8_encode(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('localizedOperandNot', 'indexed_search'), $GLOBALS['TSFE']->renderCharset), 'toLower'), 'AND NOT')
				);
				$search = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\SearchResultContentObject');
				$search->default_operator = $defaultOperator == 1 ? 'OR' : 'AND';
				$search->operator_translate_table = $operatorTranslateTable;
				$search->register_and_explode_search_string($searchWords);
				if (is_array($search->sword_array)) {
					$sWordArray = $this->procSearchWordsByLexer($search->sword_array);
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
	protected function procSearchWordsByLexer($searchWords) {
		$newSearchWords = array();
		// Init lexer (used to post-processing of search words)
		$lexerObjRef = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['lexer'] ? $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['lexer'] : 'EXT:indexed_search/Classes/Lexer.php:&TYPO3\\CMS\\IndexedSearch\\Lexer';
		$this->lexerObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($lexerObjRef);
		// Traverse the search word array
		foreach ($searchWords as $wordDef) {
			// No space in word (otherwise it might be a sentense in quotes like "there is").
			if (strpos($wordDef['sword'], ' ') === FALSE) {
				// Split the search word by lexer:
				$res = $this->lexerObj->split2Words($wordDef['sword']);
				// Traverse lexer result and add all words again:
				foreach ($res as $word) {
					$newSearchWords[] = array(
						'sword' => $word,
						'oper' => $wordDef['oper']
					);
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
	 * @dontvalidate $search
	 */
	public function formAction($search = array()) {
		$this->initialize($search);
		// Adding search field value
		$this->view->assign('sword', $this->sword);
		// Additonal keyword => "Add to current search words"
		$showAdditionalKeywordSearch = $this->settings['clearSearchBox'] && $this->settings['clearSearchBox.']['enableSubSearchCheckBox'];
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
			$showTypeSearch = count($allSearchTypes) || count($allDefaultOperands);
			$this->view->assign('showTypeSearch', $showTypeSearch);
			// "Search in"
			$allMediaTypes = $this->getAllAvailableMediaTypesOptions();
			$this->view->assign('allMediaTypes', $allMediaTypes);
			$allLanguageUids = $this->getAllAvailableLanguageOptions();
			$this->view->assign('allLanguageUids', $allLanguageUids);
			$showMediaAndLanguageSearch = count($allMediaTypes) || count($allLanguageUids);
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
			$showSortOrders = count($allSortOrders) || count($allSortDescendings);
			$this->view->assign('showSortOrders', $showSortOrders);
			// Limits
			$allNumberOfResults = $this->getAllAvailableNumberOfResultsOptions();
			$this->view->assign('allNumberOfResults', $allNumberOfResults);
			$allGroups = $this->getAllAvailableGroupOptions();
			$this->view->assign('allGroups', $allGroups);
		}
	}

	/****************************************
	 * building together the available options for every dropdown
	 ***************************************/
	/**
	 * get the values for the "type" selector
	 *
	 * @return array Associative array with options
	 */
	protected function getAllAvailableSearchTypeOptions() {
		$allOptions = array();
		$types = array(0, 1, 2, 3, 10, 20);
		$blindSettings = $this->settings['blind.'];
		if (!$blindSettings['searchType']) {
			foreach ($types as $typeNum) {
				$allOptions[$typeNum] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('searchTypes.' . $typeNum, 'indexed_search');
			}
		}
		// Remove this option if metaphone search is disabled)
		if (!$this->enableMetaphoneSearch) {
			unset($allOptions[10]);
		}
		// disable single entries by TypoScript
		$allOptions = $this->removeOptionsFromOptionList($allOptions, $blindSettings['searchType.']);
		return $allOptions;
	}

	/**
	 * get the values for the "defaultOperand" selector
	 *
	 * @return array Associative array with options
	 */
	protected function getAllAvailableOperandsOptions() {
		$allOptions = array();
		$blindSettings = $this->settings['blind.'];
		if (!$blindSettings['defaultOperand']) {
			$allOptions = array(
				0 => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('defaultOperands.0', 'indexed_search'),
				1 => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('defaultOperands.1', 'indexed_search')
			);
		}
		// disable single entries by TypoScript
		$allOptions = $this->removeOptionsFromOptionList($allOptions, $blindSettings['defaultOperand.']);
		return $allOptions;
	}

	/**
	 * get the values for the "media type" selector
	 *
	 * @return array Associative array with options
	 */
	protected function getAllAvailableMediaTypesOptions() {
		$allOptions = array();
		$mediaTypes = array(-1, 0, -2);
		$blindSettings = $this->settings['blind.'];
		if (!$blindSettings['mediaType']) {
			foreach ($mediaTypes as $mediaType) {
				$allOptions[$mediaType] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('mediaTypes.' . $mediaType, 'indexed_search');
			}
			// Add media to search in:
			$additionalMedia = trim($this->settings['mediaList']);
			if (strlen($additionalMedia) > 0) {
				$additionalMedia = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $additionalMedia, TRUE);
			}
			foreach ($this->externalParsers as $extension => $obj) {
				// Skip unwanted extensions
				if (count($additionalMedia) && !in_array($extension, $additionalMedia)) {
					continue;
				}
				if ($name = $obj->searchTypeMediaTitle($extension)) {
					$allOptions[$extension] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('mediaTypes.' . $extension, $name);
				}
			}
		}
		// disable single entries by TypoScript
		$allOptions = $this->removeOptionsFromOptionList($allOptions, $blindSettings['mediaType.']);
		return $allOptions;
	}

	/**
	 * get the values for the "language" selector
	 *
	 * @return array Associative array with options
	 */
	protected function getAllAvailableLanguageOptions() {
		$allOptions = array(
			'-1' => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('languageUids.-1', 'indexed_search'),
			'0' => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('languageUids.0', 'indexed_search')
		);
		$blindSettings = $this->settings['blind.'];
		if (!$blindSettings['languageUid']) {
			// Add search languages
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_language', '1=1' . $GLOBALS['TSFE']->cObj->enableFields('sys_language'));
			if ($res) {
				while ($lang = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					$allOptions[$lang['uid']] = $lang['title'];
				}
			}
			// disable single entries by TypoScript
			$allOptions = $this->removeOptionsFromOptionList($allOptions, $blindSettings['languageUid.']);
		} else {
			$allOptions = array();
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
	protected function getAllAvailableSectionsOptions() {
		$allOptions = array();
		$sections = array(0, -1, -2, -3);
		$blindSettings = $this->settings['blind.'];
		if (!$blindSettings['sections']) {
			foreach ($sections as $section) {
				$allOptions[$section] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('sections.' . $section, 'indexed_search');
			}
		}
		// Creating levels for section menu:
		// This selects the first and secondary menus for the "sections" selector - so we can search in sections and sub sections.
		if ($this->settings['displayLevel1Sections']) {
			$firstLevelMenu = $this->getMenuOfPages($this->searchRootPageIdList);
			$labelLevel1 = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('sections.Rl1', 'indexed_search');
			$labelLevel2 = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('sections.Rl2', 'indexed_search');
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
						$allOptions['rl2_' . implode(',', array_keys($secondLevelMenu))] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('sections.Rl2All', 'indexed_search');
					}
				} else {
					unset($firstLevelMenu[$firstLevelKey]);
				}
			}
			$allOptions['rl1_' . implode(',', array_keys($firstLevelMenu))] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('sections.Rl1All', 'indexed_search');
		}
		// disable single entries by TypoScript
		$allOptions = $this->removeOptionsFromOptionList($allOptions, $blindSettings['sections.']);
		return $allOptions;
	}

	/**
	 * get the values for the "freeIndexUid" selector
	 *
	 * @return array Associative array with options
	 */
	protected function getAllAvailableIndexConfigurationsOptions() {
		$allOptions = array(
			'-1' => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('indexingConfigurations.-1', 'indexed_search'),
			'-2' => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('indexingConfigurations.-2', 'indexed_search'),
			'0' => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('indexingConfigurations.0', 'indexed_search')
		);
		$blindSettings = $this->settings['blind.'];
		if (!$blindSettings['indexingConfigurations']) {
			// add an additional index configuration
			if ($this->settings['defaultFreeIndexUidList']) {
				$uidList = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $this->settings['defaultFreeIndexUidList']);
				$indexCfgRecords = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,title', 'index_config', 'uid IN (' . implode(',', $uidList) . ')' . $GLOBALS['TSFE']->cObj->enableFields('index_config'), '', '', '', 'uid');
				foreach ($uidList as $uidValue) {
					if (is_array($indexCfgRecords[$uidValue])) {
						$allOptions[$uidValue] = $indexCfgRecords[$uidValue]['title'];
					}
				}
			}
			// disable single entries by TypoScript
			$allOptions = $this->removeOptionsFromOptionList($allOptions, $blindSettings['indexingConfigurations.']);
		} else {
			$allOptions = array();
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
	protected function getAllAvailableSortOrderOptions() {
		$allOptions = array();
		$sortOrders = array('rank_flag', 'rank_freq', 'rank_first', 'rank_count', 'mtime', 'title', 'crdate');
		$blindSettings = $this->settings['blind.'];
		if (!$blindSettings['sortOrder']) {
			foreach ($sortOrders as $order) {
				$allOptions[$order] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('sortOrders.' . $order, 'indexed_search');
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
	protected function getAllAvailableGroupOptions() {
		$allOptions = array();
		$blindSettings = $this->settings['blind.'];
		if (!$blindSettings['groupBy']) {
			$allOptions = array(
				'sections' => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('groupBy.sections', 'indexed_search'),
				'flat' => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('groupBy.flat', 'indexed_search')
			);
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
	protected function getAllAvailableSortDescendingOptions() {
		$allOptions = array();
		$blindSettings = $this->settings['blind.'];
		if (!$blindSettings['descending']) {
			$allOptions = array(
				0 => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('sortOrders.descending', 'indexed_search'),
				1 => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('sortOrders.ascending', 'indexed_search')
			);
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
	protected function getAllAvailableNumberOfResultsOptions() {
		$allOptions = array();
		$blindSettings = $this->settings['blind.'];
		if (!$blindSettings['numberOfResults']) {
			$allOptions = array(
				10 => 10,
				25 => 25,
				50 => 50,
				100 => 100
			);
		}
		// disable single entries by TypoScript
		$allOptions = $this->removeOptionsFromOptionList($allOptions, $blindSettings['numberOfResults.']);
		return $allOptions;
	}

	/**
	 * removes blinding entries from the option list of a selector
	 *
	 * @param array $allOptions associative array containing all options
	 * @param array $blindOptions associative array containing the optionkey as they key and the value = 1 if it should be removed
	 * @return array Options from $allOptions with some options removed
	 */
	protected function removeOptionsFromOptionList($allOptions, $blindOptions) {
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
	 * @param integer $pageUid Page id
	 * @param string $linkText Title String to link
	 * @param array $row Result row
	 * @param array $markUpSwParams Additional parameters for marking up seach words
	 * @return string <A> tag wrapped title string.
	 * @todo make use of the UriBuilder
	 */
	protected function linkPage($pageUid, $linkText, $row = array(), $markUpSwParams = array()) {
		// Parameters for link
		$urlParameters = (array) unserialize($row['cHashParams']);
		// Add &type and &MP variable:
		if ($row['data_page_mp']) {
			$urlParameters['MP'] = $row['data_page_mp'];
		}
		if ($row['sys_language_uid']) {
			$urlParameters['L'] = $row['sys_language_uid'];
		}
		// markup-GET vars:
		$urlParameters = array_merge($urlParameters, $markUpSwParams);
		// This will make sure that the path is retrieved if it hasn't been
		// already. Used only for the sake of the domain_record thing.
		if (!is_array($this->domainRecords[$pageUid])) {
			$this->getPathFromPageId($pageUid);
		}
		// If external domain, then link to that:
		if (count($this->domainRecords[$pageUid])) {
			$scheme = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SSL') ? 'https://' : 'http://';
			$firstDomain = reset($this->domainRecords[$pageUid]);
			$additionalParams = '';
			if (is_array($urlParameters)) {
				if (count($urlParameters)) {
					$additionalParams = \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl('', $urlParameters);
				}
			}
			$uri = $scheme . $firstDomain . '/index.php?id=' . $pageUid . $additionalParams;
			if ($target = $this->settings['detectDomainRecords.']['target']) {
				$target = ' target="' . $target . '"';
			}
		} else {
			$uriBuilder = $this->controllerContext->getUriBuilder();
			$uri = $uriBuilder->setTargetPageUid($pageUid)->setTargetPageType($row['data_page_type'])->setUseCacheHash(TRUE)->setArguments($urlParameters)->build();
		}
		return '<a href="' . htmlspecialchars($uri) . '"' . $target . '>' . htmlspecialchars($linkText) . '</a>';
	}

	/**
	 * Return the menu of pages used for the selector.
	 *
	 * @param integer $pageUid Page ID for which to return menu
	 * @return array Menu items (for making the section selector box)
	 */
	protected function getMenuOfPages($pageUid) {
		if ($this->settings['displayLevelxAllTypes']) {
			$menu = array();
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('title,uid', 'pages', 'pid=' . intval($pageUid) . $GLOBALS['TSFE']->cObj->enableFields('pages'), '', 'sorting');
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$menu[$row['uid']] = $GLOBALS['TSFE']->sys_page->getPageOverlay($row);
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		} else {
			$menu = $GLOBALS['TSFE']->sys_page->getMenu($pageUid);
		}
		return $menu;
	}

	/**
	 * Returns the path to the page $id
	 *
	 * @param integer $id Page ID
	 * @param string MP variable content
	 * @return string Path
	 */
	protected function getPathFromPageId($id, $pathMP = '') {
		$identStr = $id . '|' . $pathMP;
		if (!isset($this->pathCache[$identStr])) {
			$this->requiredFrontendUsergroups[$id] = array();
			$this->domainRecords[$id] = array();
			$rl = $GLOBALS['TSFE']->sys_page->getRootLine($id, $pathMP);
			$path = '';
			if (is_array($rl) && count($rl)) {
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
						break;
					}
					$path = '/' . $v['title'] . $path;
				}
			}
			$this->pathCache[$identStr] = $path;
		}
		return $this->pathCache[$identStr];
	}

	/**
	 * Gets the first sys_domain record for the page, $id
	 *
	 * @param integer $id Page id
	 * @return string Domain name
	 */
	protected function getFirstSysDomainRecordForPage($id) {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('domainName', 'sys_domain', 'pid=' . intval($id) . $GLOBALS['TSFE']->cObj->enableFields('sys_domain'), '', 'sorting');
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		return rtrim($row['domainName'], '/');
	}

	/**
	 * simple function to initialize possible external parsers
	 * feeds the $this->externalParsers array
	 *
	 * @return void
	 */
	protected function initializeExternalParsers() {
		// Initialize external document parsers for icon display and other soft operations
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['external_parsers'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['external_parsers'] as $extension => $_objRef) {
				$this->externalParsers[$extension] = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_objRef);
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
	 * @return object Hook object, if any. Otherwise NULL.
	 */
	protected function hookRequest($functionName) {
		// Hook: menuConfig_preProcessModMenu
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['pi1_hooks'][$functionName]) {
			$hookObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['pi1_hooks'][$functionName]);
			if (method_exists($hookObj, $functionName)) {
				$hookObj->pObj = $this;
				return $hookObj;
			}
		}
		return NULL;
	}

	/**
	 * Returns if an item type is a multipage item type
	 *
	 * @param string $item_type Item type
	 * @return boolean TRUE if multipage capable
	 */
	protected function multiplePagesType($item_type) {
		return is_object($this->externalParsers[$item_type]) && $this->externalParsers[$item_type]->isMultiplePageExtension($item_type);
	}

}


?>