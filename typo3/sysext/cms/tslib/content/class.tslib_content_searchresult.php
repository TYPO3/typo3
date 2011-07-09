<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 Xavier Perseguers <typo3@perseguers.ch>
 *  (c) 2010-2011 Steffen Kamper <steffen@typo3.org>
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
 * Contains SEARCHRESULT class object.
 *
 * @author Xavier Perseguers <typo3@perseguers.ch>
 * @author Steffen Kamper <steffen@typo3.org>
 */
class tslib_content_SearchResult extends tslib_content_Abstract {

	/**
	 * Rendering the cObject, SEARCHRESULT
	 *
	 * @param	array		Array of TypoScript properties
	 * @return	string		Output
	 */
	public function render($conf = array()) {
		if (t3lib_div::_GP('sword') && t3lib_div::_GP('scols')) {
			$search = t3lib_div::makeInstance('tslib_search');
			$search->register_and_explode_search_string(t3lib_div::_GP('sword'));
			$search->register_tables_and_columns(t3lib_div::_GP('scols'), $conf['allowedCols']);
				// depth
			$depth = 100;
				// the startId is found
			$theStartId = 0;
			if (t3lib_div::testInt(t3lib_div::_GP('stype'))) {
				$temp_theStartId = t3lib_div::_GP('stype');
				$rootLine = $GLOBALS['TSFE']->sys_page->getRootLine($temp_theStartId);
					// The page MUST have a rootline with the Level0-page of the current site inside!!
				foreach ($rootLine as $val) {
					if ($val['uid'] == $GLOBALS['TSFE']->tmpl->rootLine[0]['uid']) {
						$theStartId = $temp_theStartId;
					}
				}
			} elseif (t3lib_div::_GP('stype')) {
				if (substr(t3lib_div::_GP('stype'), 0, 1) == 'L') {
					$pointer = intval(substr(t3lib_div::_GP('stype'), 1));
					$theRootLine = $GLOBALS['TSFE']->tmpl->rootLine;
						// location Data:
					$locDat_arr = explode(':', t3lib_div::_POST('locationData'));
					$pId = intval($locDat_arr[0]);
					if ($pId) {
						$altRootLine = $GLOBALS['TSFE']->sys_page->getRootLine($pId);
						ksort($altRootLine);
						if (count($altRootLine)) {
								// check if the rootline has the real Level0 in it!!
							$hitRoot = 0;
							$theNewRoot = array();
							foreach ($altRootLine as $val) {
								if ($hitRoot || $val['uid'] == $GLOBALS['TSFE']->tmpl->rootLine[0]['uid']) {
									$hitRoot = 1;
									$theNewRoot[] = $val;
								}
							}
							if ($hitRoot) {
								$theRootLine = $theNewRoot; // Override the real rootline if any thing
							}
						}
					}
					$key = $this->cObj->getKey($pointer, $theRootLine);
					$theStartId = $theRootLine[$key]['uid'];
				}
			}
			if (!$theStartId) {
					// If not set, we use current page
				$theStartId = $GLOBALS['TSFE']->id;
			}
				// generate page-tree
			$search->pageIdList .= $this->cObj->getTreeList(-1 * $theStartId, $depth);

			$endClause = 'pages.uid IN (' . $search->pageIdList . ')
				AND pages.doktype in (' .
					$GLOBALS['TYPO3_CONF_VARS']['FE']['content_doktypes'] . ($conf['addExtUrlsAndShortCuts'] ? ',3,4' : '') .
				')
				AND pages.no_search=0' . $this->cObj->enableFields($search->fTable) . $this->cObj->enableFields('pages');

			if ($conf['languageField.'][$search->fTable]) {
					// (using sys_language_uid which is the ACTUAL language of the page.
					// sys_language_content is only for selecting DISPLAY content!)
				$endClause .= ' AND ' . $search->fTable . '.' . $conf['languageField.'][$search->fTable] .
					' = ' .
				intval($GLOBALS['TSFE']->sys_language_uid);
			}

				// build query
			$search->build_search_query($endClause);

				// count...
			if (t3lib_div::testInt(t3lib_div::_GP('scount'))) {
				$search->res_count = t3lib_div::_GP('scount');
			} else {
				$search->count_query();
			}

				// range
			$spointer = intval(t3lib_div::_GP('spointer'));
			$range = isset($conf['range.'])
				? $this->cObj->stdWrap($conf['range'], $conf['range.'])
				: $conf['range'];
			if ($range) {
				$theRange = intval($range);
			} else {
				$theRange = 20;
			}

				// Order By:
			$noOrderBy = isset($conf['noOrderBy.'])
				? $this->cObj->stdWrap($conf['noOrderBy'], $conf['noOrderBy.'])
				: $conf['noOrderBy'];
			if (!$noOrderBy) {
				$search->queryParts['ORDERBY'] = 'pages.lastUpdated, pages.tstamp';
			}

			$search->queryParts['LIMIT'] = $spointer . ',' . $theRange;

				// search...
			$search->execute_query();
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($search->result)) {
				$GLOBALS['TSFE']->register['SWORD_PARAMS'] = $search->get_searchwords();

				$total = $search->res_count;
				$rangeLow = t3lib_utility_Math::forceIntegerInRange($spointer + 1, 1, $total);
				$rangeHigh = t3lib_utility_Math::forceIntegerInRange($spointer + $theRange, 1, $total);
					// prev/next url:

				$target = isset($conf['target.'])
					? $this->cObj->stdWrap($conf['target'], $conf['target.'])
					: $conf['target'];

				$LD = $GLOBALS['TSFE']->tmpl->linkData(
					$GLOBALS['TSFE']->page,
					$target,
					1,
					'',
					'',
					$this->cObj->getClosestMPvalueForPage($GLOBALS['TSFE']->page['uid'])
				);
				$targetPart = $LD['target'] ? ' target="' . htmlspecialchars($LD['target']) . '"' : '';
				$urlParams = $this->cObj->URLqMark(
					$LD['totalURL'],
					'&sword=' . rawurlencode(t3lib_div::_GP('sword')) .
					'&scols=' . rawurlencode(t3lib_div::_GP('scols')) .
					'&stype=' . rawurlencode(t3lib_div::_GP('stype')) .
					'&scount=' . $total
				);
					// substitution:
				$result = $this->cObj->cObjGetSingle($conf['layout'], $conf['layout.'], 'layout');
				$result = str_replace('###RANGELOW###', $rangeLow, $result);
				$result = str_replace('###RANGEHIGH###', $rangeHigh, $result);
				$result = str_replace('###TOTAL###', $total, $result);

				if ($rangeHigh < $total) {
					$next = $this->cObj->cObjGetSingle($conf['next'], $conf['next.'], 'next');
					$next = '<a href="' . htmlspecialchars($urlParams .
						'&spointer=' . ($spointer + $theRange)) . '"' .
						$targetPart . $GLOBALS['TSFE']->ATagParams . '>' . $next . '</a>';
				} else
					$next = '';
				$result = str_replace('###NEXT###', $next, $result);

				if ($rangeLow > 1) {
					$prev = $this->cObj->cObjGetSingle($conf['prev'], $conf['prev.'], 'prev');
					$prev = '<a href="' . htmlspecialchars($urlParams .
						'&spointer=' . ($spointer - $theRange)) . '"' .
						$targetPart . $GLOBALS['TSFE']->ATagParams . '>' . $prev . '</a>';
				} else
					$prev = '';
				$result = str_replace('###PREV###', $prev, $result);

					// searching result
				$theValue = $this->cObj->cObjGetSingle($conf['resultObj'], $conf['resultObj.'], 'resultObj');
				$cObj = t3lib_div::makeInstance('tslib_cObj');
				$cObj->setParent($this->cObj->data, $this->cObj->currentRecord);
				$renderCode = '';
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($search->result)) {
						// versionOL() here? This is search result displays, is that possible to preview anyway?
						// Or are records selected here already future versions?
					$cObj->start($row);
					$renderCode .= $cObj->cObjGetSingle($conf['renderObj'], $conf['renderObj.'], 'renderObj');
				}
				$renderWrap = isset($conf['renderWrap.'])
					? $this->cObj->stdWrap($conf['renderWrap'], $conf['renderWrap.'])
					: $conf['renderWrap'];
				$theValue .= $this->cObj->wrap($renderCode, $renderWrap);
				$theValue = str_replace('###RESULT###', $theValue, $result);
			} else {
				$theValue = $this->cObj->cObjGetSingle($conf['noResultObj'], $conf['noResultObj.'], 'noResultObj');
			}

			$GLOBALS['TT']->setTSlogMessage('Search in fields:   ' . $search->listOfSearchFields);

				// wrapping
			$content = $theValue;

			$wrap = isset($conf['wrap.'])
				? $this->cObj->stdWrap( $conf['wrap'], $conf['wrap.'])
				:  $conf['wrap'];
			if ($wrap) {
				$content = $this->cObj->wrap($content, $wrap);
			}

			if (isset($conf['stdWrap.'])) {
				$content = $this->cObj->stdWrap($content, $conf['stdWrap.']);
			}
				// returning
			$GLOBALS['TSFE']->set_no_cache();

			return $content;
		}
	}

}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/content/class.tslib_content_searchresult.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/content/class.tslib_content_searchresult.php']);
}

?>