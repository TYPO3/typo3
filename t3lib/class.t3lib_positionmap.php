<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Contains class for creating a position map.
 *
 * Revised for TYPO3 3.6 November/2003 by Kasper Skårhøj
 * XHTML compliant (should be)
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */

/**
 * Position map class - generating a page tree / content element list which links for inserting (copy/move) of records.
 * Used for pages / tt_content element wizards of various kinds.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_positionMap {

		// EXTERNAL, static:
	var $moveOrCopy = 'move';
	var $dontPrintPageInsertIcons = 0;
	var $backPath = '';
		// How deep the position page tree will go.
	var $depth = 2;
		// Can be set to the sys_language uid to select content elements for.
	var $cur_sys_language;


		// INTERNAL, dynamic:
		// Request uri
	var $R_URI = '';
		// Element id.
	var $elUid = '';
		// tt_content element uid to move.
	var $moveUid = '';

		// Caching arrays:
	var $getModConfigCache = array();
	var $checkNewPageCache = Array();

		// Label keys:
	var $l_insertNewPageHere = 'insertNewPageHere';
	var $l_insertNewRecordHere = 'insertNewRecordHere';

	var $modConfigStr = 'mod.web_list.newPageWiz';

	/*************************************
	 *
	 * Page position map:
	 *
	 **************************************/

	/**
	 * Creates a "position tree" based on the page tree.
	 * Notice: A class, "localPageTree" must exist and probably it is an extension class of the t3lib_pageTree class. See "db_new.php" in the core for an example.
	 *
	 * @param integer $id Current page id
	 * @param array $pageinfo Current page record.
	 * @param string $perms_clause Page selection permission clause.
	 * @param string $R_URI Current REQUEST_URI
	 * @return string HTML code for the tree.
	 */
	function positionTree($id, $pageinfo, $perms_clause, $R_URI) {
		$code = '';
			// Make page tree object:
		/** @var $t3lib_pageTree localPageTree */
		$t3lib_pageTree = t3lib_div::makeInstance('localPageTree');
		$t3lib_pageTree->init(' AND ' . $perms_clause);
		$t3lib_pageTree->addField('pid');

			// Initialize variables:
		$this->R_URI = $R_URI;
		$this->elUid = $id;

			// Create page tree, in $this->depth levels.
		$t3lib_pageTree->getTree($pageinfo['pid'], $this->depth);
		if (!$this->dontPrintPageInsertIcons) {
			$code .= $this->JSimgFunc();
		}

			// Initialize variables:
		$saveBlankLineState = array();
		$saveLatestUid = array();
		$latestInvDepth = $this->depth;

			// Traverse the tree:
		foreach ($t3lib_pageTree->tree as $cc => $dat) {

				// Make link + parameters.
			$latestInvDepth = $dat['invertedDepth'];
			$saveLatestUid[$latestInvDepth] = $dat;
			if (isset($t3lib_pageTree->tree[$cc - 1])) {
				$prev_dat = $t3lib_pageTree->tree[$cc - 1];

					// If current page, subpage?
				if ($prev_dat['row']['uid'] == $id) {
						// 1) It must be allowed to create a new page and 2) If there are subpages there is no need to render a subpage icon here - it'll be done over the subpages...
					if (!$this->dontPrintPageInsertIcons && $this->checkNewPageInPid($id) && !($prev_dat['invertedDepth'] > $t3lib_pageTree->tree[$cc]['invertedDepth'])) {
						$code .= '<span class="nobr">' .
								$this->insertQuadLines($dat['blankLineCode']) .
								'<img src="clear.gif" width="18" height="8" align="top" alt="" />' .
								'<a href="#" onclick="' . htmlspecialchars($this->onClickEvent($id, $id, 1)) . '" onmouseover="' . htmlspecialchars('changeImg(\'mImgSubpage' . $cc . '\',0);') . '" onmouseout="' . htmlspecialchars('changeImg(\'mImgSubpage' . $cc . '\',1);') . '">' .
								'<img' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/newrecord_marker_d.gif', 'width="281" height="8"') . ' name="mImgSubpage' . $cc . '" border="0" align="top" title="' . $this->insertlabel() . '" alt="" />' .
								'</a></span><br />';
					}
				}

					// If going down
				if ($prev_dat['invertedDepth'] > $t3lib_pageTree->tree[$cc]['invertedDepth']) {
					$prevPid = $t3lib_pageTree->tree[$cc]['row']['pid'];
				} elseif ($prev_dat['invertedDepth'] < $t3lib_pageTree->tree[$cc]['invertedDepth']) { // If going up
					// First of all the previous level should have an icon:
					if (!$this->dontPrintPageInsertIcons && $this->checkNewPageInPid($prev_dat['row']['pid'])) {
						$prevPid = (-$prev_dat['row']['uid']);
						$code .= '<span class="nobr">' .
								$this->insertQuadLines($dat['blankLineCode']) .
								'<img src="clear.gif" width="18" height="1" align="top" alt="" />' .
								'<a href="#" onclick="' . htmlspecialchars($this->onClickEvent($prevPid, $prev_dat['row']['pid'], 2)) . '" onmouseover="' . htmlspecialchars('changeImg(\'mImgAfter' . $cc . '\',0);') . '" onmouseout="' . htmlspecialchars('changeImg(\'mImgAfter' . $cc . '\',1);') . '">' .
								'<img' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/newrecord_marker_d.gif', 'width="281" height="8"') . ' name="mImgAfter' . $cc . '" border="0" align="top" title="' . $this->insertlabel() . '" alt="" />' .
								'</a></span><br />';
					}

						// Then set the current prevPid
					$prevPid = -$prev_dat['row']['pid'];
				} else {
						// In on the same level
					$prevPid = -$prev_dat['row']['uid'];
				}
			} else {
					// First in the tree
				$prevPid = $dat['row']['pid'];
			}
			if (!$this->dontPrintPageInsertIcons && $this->checkNewPageInPid($dat['row']['pid'])) {
				$code .= '<span class="nobr">' .
						$this->insertQuadLines($dat['blankLineCode']) .
						'<a href="#" onclick="' . htmlspecialchars($this->onClickEvent($prevPid, $dat['row']['pid'], 3)) . '" onmouseover="' . htmlspecialchars('changeImg(\'mImg' . $cc . '\',0);') . '" onmouseout="' . htmlspecialchars('changeImg(\'mImg' . $cc . '\',1);') . '">' .
						'<img' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/newrecord_marker_d.gif', 'width="281" height="8"') . ' name="mImg' . $cc . '" border="0" align="top" title="' . $this->insertlabel() . '" alt="" />' .
						'</a></span><br />';
			}

				// The line with the icon and title:
			$t_code = '<span class="nobr">' .
				$dat['HTML'] .
				$this->linkPageTitle(
					$this->boldTitle(
						htmlspecialchars(t3lib_div::fixed_lgd_cs($dat['row']['title'], $GLOBALS['BE_USER']->uc['titleLen'])),
						$dat,
						$id),
					$dat['row']
				) . '</span><br />';
			$code .= $t_code;
		}

			// If the current page was the last in the tree:
		$prev_dat = end($t3lib_pageTree->tree);
		if ($prev_dat['row']['uid'] == $id) {
			if (!$this->dontPrintPageInsertIcons && $this->checkNewPageInPid($id)) {
				$code .= '<span class="nobr">' .
						$this->insertQuadLines($saveLatestUid[$latestInvDepth]['blankLineCode'], 1) .
						'<img src="clear.gif" width="18" height="8" align="top" alt="" />' .
						'<a href="#" onclick="' . $this->onClickEvent($id, $id, 4) . '" onmouseover="' . htmlspecialchars('changeImg(\'mImgSubpage' . $cc . '\',0);') . '" onmouseout="' . htmlspecialchars('changeImg(\'mImgSubpage' . $cc . '\',1);') . '">' .
						'<img' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/newrecord_marker_d.gif', 'width="281" height="8"') . ' name="mImgSubpage' . $cc . '" border="0" align="top" title="' . $this->insertlabel() . '" alt="" />' .
						'</a></span><br />';
			}
		}

		for ($a = $latestInvDepth; $a <= $this->depth; $a++) {
			$dat = $saveLatestUid[$a];
			$prevPid = (-$dat['row']['uid']);
			if (!$this->dontPrintPageInsertIcons && $this->checkNewPageInPid($dat['row']['pid'])) {
				$code .= '<span class="nobr">' .
						$this->insertQuadLines($dat['blankLineCode'], 1) .
						'<a href="#" onclick="' . htmlspecialchars($this->onClickEvent($prevPid, $dat['row']['pid'], 5)) . '" onmouseover="' . htmlspecialchars('changeImg(\'mImgEnd' . $a . '\',0);') . '" onmouseout="' . htmlspecialchars('changeImg(\'mImgEnd' . $a . '\',1);') . '">' .
						'<img' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/newrecord_marker_d.gif', 'width="281" height="8"') . ' name="mImgEnd' . $a . '" border="0" align="top" title="' . $this->insertlabel() . '" alt="" />' .
						'</a></span><br />';
			}
		}

		return $code;
	}

	/**
	 * Creates the JavaScritp for insert new-record rollover image
	 *
	 * @param string $prefix Insert record image prefix.
	 * @return string <script> section
	 */
	function JSimgFunc($prefix = '') {
		$code = $GLOBALS['TBE_TEMPLATE']->wrapScriptTags('

			var img_newrecord_marker=new Image();
			img_newrecord_marker.src = "' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/newrecord' . $prefix . '_marker.gif', '', 1) . '";

			var img_newrecord_marker_d=new Image();
			img_newrecord_marker_d.src = "' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/newrecord' . $prefix . '_marker_d.gif', '', 1) . '";

			function changeImg(name,d)	{	//
				if (document[name]) {
					if (d) {
						document[name].src = img_newrecord_marker_d.src;
					} else {
						document[name].src = img_newrecord_marker.src;
					}
				}
			}
		');
		return $code;
	}

	/**
	 * Wrap $t_code in bold IF the $dat uid matches $id
	 *
	 * @param string $t_code Title string
	 * @param array $dat Infomation array with record array inside.
	 * @param integer $id The current id.
	 * @return string The title string.
	 */
	function boldTitle($t_code, $dat, $id) {
		if ($dat['row']['uid'] == $id) {
			$t_code = '<strong>' . $t_code . '</strong>';
		}
		return $t_code;
	}

	/**
	 * Creates the onclick event for the insert-icons.
	 *
	 * TSconfig mod.web_list.newPageWiz.overrideWithExtension may contain an extension which provides a module
	 * to be used instead of the normal create new page wizard.
	 *
	 * @param integer $pid The pid.
	 * @param integer $newPagePID New page id.
	 * @return string Onclick attribute content
	 */
	function onClickEvent($pid, $newPagePID) {
		$TSconfigProp = $this->getModConfig($newPagePID);

		if ($TSconfigProp['overrideWithExtension']) {
			if (t3lib_extMgm::isLoaded($TSconfigProp['overrideWithExtension'])) {
				$onclick = "window.location.href='" . t3lib_extMgm::extRelPath($TSconfigProp['overrideWithExtension']) . 'mod1/index.php?cmd=crPage&positionPid=' . $pid . "';";
				return $onclick;
			}
		}

		$params = '&edit[pages][' . $pid . ']=new&returnNewPageId=1';
		return t3lib_BEfunc::editOnClick($params, '', $this->R_URI);
	}

	/**
	 * Get label, htmlspecialchars()'ed
	 *
	 * @return string The localized label for "insert new page here"
	 */
	function insertlabel() {
		return $GLOBALS['LANG']->getLL($this->l_insertNewPageHere, 1);
	}

	/**
	 * Wrapping page title.
	 *
	 * @param string $str Page title.
	 * @param array $rec Page record (?)
	 * @return string Wrapped title.
	 */
	function linkPageTitle($str, $rec) {
		return $str;
	}

	/**
	 * Checks if the user has permission to created pages inside of the $pid page.
	 * Uses caching so only one regular lookup is made - hence you can call the function multiple times without worrying about performance.
	 *
	 * @param integer $pid Page id for which to test.
	 * @return boolean
	 */
	function checkNewPageInPid($pid) {
		if (!isset($this->checkNewPageCache[$pid])) {
			$pidInfo = t3lib_BEfunc::getRecord('pages', $pid);
			$this->checkNewPageCache[$pid] = ($GLOBALS['BE_USER']->isAdmin() || $GLOBALS['BE_USER']->doesUserHaveAccess($pidInfo, 8));
		}
		return $this->checkNewPageCache[$pid];
	}

	/**
	 * Returns module configuration for a pid.
	 *
	 * @param integer $pid Page id for which to get the module configuration.
	 * @return array The properties of teh module configuration for the page id.
	 * @see onClickEvent()
	 */
	function getModConfig($pid) {
		if (!isset($this->getModConfigCache[$pid])) {
				// Acquiring TSconfig for this PID:
			$this->getModConfigCache[$pid] = t3lib_BEfunc::getModTSconfig($pid, $this->modConfigStr);
		}
		return $this->getModConfigCache[$pid]['properties'];
	}

	/**
	 * Insert half/quad lines.
	 *
	 * @param string $codes Keywords for which lines to insert.
	 * @param boolean $allBlank If TRUE all lines are just blank clear.gifs
	 * @return string HTML content.
	 */
	function insertQuadLines($codes, $allBlank = FALSE) {
		$codeA = t3lib_div::trimExplode(',', $codes . ",line", 1);

		$lines = array();
		foreach ($codeA as $code) {
			if ($code == "blank" || $allBlank) {
				$lines[] = '<img src="clear.gif" width="18" height="8" align="top" alt="" />';
			} else {
				$lines[] = '<img' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/ol/halfline.gif', 'width="18" height="8"') . ' align="top" alt="" />';
			}
		}
		return implode('', $lines);
	}

	/*************************************
	 *
	 * Content element positioning:
	 *
	 **************************************/

	/**
	 * Creates HTML for inserting/moving content elements.
	 *
	 * @param integer $pid page id onto which to insert content element.
	 * @param integer $moveUid Move-uid (tt_content element uid?)
	 * @param string $colPosList List of columns to show
	 * @param boolean $showHidden If not set, then hidden/starttime/endtime records are filtered out.
	 * @param string $R_URI Request URI
	 * @return string HTML
	 */
	function printContentElementColumns($pid, $moveUid, $colPosList, $showHidden, $R_URI) {
		$this->R_URI = $R_URI;
		$this->moveUid = $moveUid;
		$colPosArray = t3lib_div::trimExplode(',', $colPosList, 1);

		$lines = array();
		foreach ($colPosArray as $kk => $vv) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*',
				'tt_content',
				'pid=' . intval($pid) .
				($showHidden ? '' : t3lib_BEfunc::BEenableFields('tt_content')) .
				' AND colPos=' . intval($vv) .
				(strcmp($this->cur_sys_language, '') ? ' AND sys_language_uid=' . intval($this->cur_sys_language) : '') .
				t3lib_BEfunc::deleteClause('tt_content') .
				t3lib_BEfunc::versioningPlaceholderClause('tt_content'),
				'',
				'sorting'
			);
			$lines[$vv] = array();
			$lines[$vv][] = $this->insertPositionIcon('', $vv, $kk, $moveUid, $pid);
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				t3lib_BEfunc::workspaceOL('tt_content', $row);
				if (is_array($row)) {
					$lines[$vv][] = $this->wrapRecordHeader($this->getRecordHeader($row), $row);
					$lines[$vv][] = $this->insertPositionIcon($row, $vv, $kk, $moveUid, $pid);
				}
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}
		return $this->printRecordMap($lines, $colPosArray, $pid);
	}

	/**
	 * Creates the table with the content columns
	 *
	 * @param array $lines Array with arrays of lines for each column
	 * @param array $colPosArray Column position array
	 * @param integer $pid The id of the page
	 * @return string HTML
	 */
	function printRecordMap($lines, $colPosArray, $pid = 0) {

		$row1 = '';
		$row2 = '';
		$count = t3lib_utility_Math::forceIntegerInRange(count($colPosArray), 1);

		$backendLayout = t3lib_div::callUserFunction('EXT:cms/classes/class.tx_cms_backendlayout.php:tx_cms_BackendLayout->getSelectedBackendLayout', $pid, $this);

		if (isset($backendLayout['__config']['backend_layout.'])) {

			$table = '<div class="t3-gridContainer"><table border="0" cellspacing="0" cellpadding="0" id="typo3-ttContentList">';

			$colCount = intval($backendLayout['__config']['backend_layout.']['colCount']);
			$rowCount = intval($backendLayout['__config']['backend_layout.']['rowCount']);

			$table .= '<colgroup>';
			for ($i = 0; $i < $colCount; $i++) {
				$table .= '<col style="width:' . (100 / $colCount) . '%"></col>';
			}
			$table .= '</colgroup>';

			$tcaItems = t3lib_div::callUserFunction('EXT:cms/classes/class.tx_cms_backendlayout.php:tx_cms_BackendLayout->getColPosListItemsParsed', $pid, $this);

				// Cycle through rows
			for ($row = 1; $row <= $rowCount; $row++) {
				$rowConfig = $backendLayout['__config']['backend_layout.']['rows.'][$row . '.'];
				if (!isset($rowConfig)) {
					continue;
				}

				$table .= '<tr>';

				for ($col = 1; $col <= $colCount; $col++) {
					$columnConfig = $rowConfig['columns.'][$col . '.'];
					if (!isset($columnConfig)) {
						continue;
					}

						// Which tt_content colPos should be displayed inside this cell
					$columnKey = intval($columnConfig['colPos']);
					$head = '';

					$params = array();
					$params['pid'] = $pid;

					foreach ($tcaItems as $item) {
						if ($item[1] == $columnKey) {
							$head = $GLOBALS['LANG']->sL(t3lib_BEfunc::getLabelFromItemlist('tt_content', 'colPos', $columnKey, $params), 1);
						}
					}

						// Render the grid cell
					$table .= '<td valign="top"' .
							(isset($columnConfig['colspan']) ? ' colspan="' . $columnConfig['colspan'] . '"' : '') .
							(isset($columnConfig['rowspan']) ? ' rowspan="' . $columnConfig['rowspan'] . '"' : '') .
							' class="t3-gridCell t3-page-column t3-page-column-' . $columnKey .
							(!isset($columnConfig['colPos']) ? ' t3-gridCell-unassigned' : '') .
							((isset($columnConfig['colPos']) && ! $head) ? ' t3-gridCell-restricted' : '') .
							(isset($columnConfig['colspan']) ? ' t3-gridCell-width' . $columnConfig['colspan'] : '') .
							(isset($columnConfig['rowspan']) ? ' t3-gridCell-height' . $columnConfig['rowspan'] : '') . '">';

					$table .= '<div class="t3-page-colHeader t3-row-header">';

					if (isset($columnConfig['colPos']) && $head) {
						$table .= $this->wrapColumnHeader($head, '', '') . '</div>' . implode('<br />', $lines[$columnKey]);
					} elseif ($columnConfig['colPos']) {
						$table .= $this->wrapColumnHeader($GLOBALS['LANG']->getLL('noAccess'), '', '') . '</div>';
					} elseif ($columnConfig['name']) {
						$table .= $this->wrapColumnHeader($columnConfig['name'], '', '') . '</div>';
					} else {
						$table .= $this->wrapColumnHeader($GLOBALS['LANG']->getLL('notAssigned'), '', '') . '</div>';
					}
					$table .= '</td>';
				}
				$table .= '</tr>';
			}
			$table .= '</table></div>';
		} else {
				// Traverse the columns here:
			foreach ($colPosArray as $kk => $vv) {
				$row1 .= '<td align="center" width="' . round(100 / $count) . '%"><div class="t3-page-colHeader t3-row-header">' .
						$this->wrapColumnHeader($GLOBALS['LANG']->sL(t3lib_BEfunc::getLabelFromItemlist('tt_content', 'colPos', $vv, $pid), 1), $vv) .
						'</div></td>';
				$row2 .= '<td valign="top" nowrap="nowrap">' .
						implode('<br />', $lines[$vv]) .
						'</td>';
			}

			$table = '

			<!--
				Map of records in columns:
			-->
			<table border="0" cellpadding="0" cellspacing="0" id="typo3-ttContentList">
				<tr>' . $row1 . '</tr>
				<tr>' . $row2 . '</tr>
			</table>

			';
		}
		return $this->JSimgFunc('2') . $table;
	}

	/**
	 * Wrapping the column header
	 *
	 * @param string $str Header value
	 * @param string $vv Column info.
	 * @return string
	 * @see printRecordMap()
	 */
	function wrapColumnHeader($str, $vv) {
		return $str;
	}

	/**
	 * Creates a linked position icon.
	 *
	 * @param array $row Element row.
	 * @param string $vv Column position value.
	 * @param integer $kk Column key.
	 * @param integer $moveUid Move uid
	 * @param integer $pid PID value.
	 * @return string
	 */
	function insertPositionIcon($row, $vv, $kk, $moveUid, $pid) {
		$cc = hexdec(substr(md5($row['uid'] . '-' . $vv . '-' . $kk), 0, 4));
		return '<a href="#" onclick="' . htmlspecialchars($this->onClickInsertRecord($row, $vv, $moveUid, $pid, $this->cur_sys_language)) . '" onmouseover="' . htmlspecialchars('changeImg(\'mImg' . $cc . '\',0);') . '" onmouseout="' . htmlspecialchars('changeImg(\'mImg' . $cc . '\',1);') . '">' .
			'<img' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/newrecord2_marker_d.gif', 'width="100" height="8"') . ' name="mImg' . $cc . '" border="0" align="top" title="' . $GLOBALS['LANG']->getLL($this->l_insertNewRecordHere, 1) . '" alt="" />' .
			'</a>';
	}

	/**
	 * Create on-click event value.
	 *
	 * @param array $row The record.
	 * @param string $vv Column position value.
	 * @param integer $moveUid Move uid
	 * @param integer $pid PID value.
	 * @param integer $sys_lang System language (not used currently)
	 * @return string
	 */
	function onClickInsertRecord($row, $vv, $moveUid, $pid, $sys_lang = 0) {
		$table = 'tt_content';
		if (is_array($row)) {
			$location = 'tce_db.php?cmd[' . $table . '][' . $moveUid . '][' . $this->moveOrCopy . ']=-' . $row['uid'] . '&prErr=1&uPT=1&vC=' . $GLOBALS['BE_USER']->veriCode() . t3lib_BEfunc::getUrlToken('tceAction');
		} else {
			$location = 'tce_db.php?cmd[' . $table . '][' . $moveUid . '][' . $this->moveOrCopy . ']=' . $pid . '&data[' . $table . '][' . $moveUid . '][colPos]=' . $vv . '&prErr=1&vC=' . $GLOBALS['BE_USER']->veriCode() . t3lib_BEfunc::getUrlToken('tceAction');
		}

		$location .= '&redirect=' . rawurlencode($this->R_URI);		// returns to prev. page

		return 'window.location.href=\'' . $location . '\';return false;';
	}

	/**
	 * Wrapping the record header  (from getRecordHeader())
	 *
	 * @param string $str HTML content
	 * @param array $row Record array.
	 * @return string HTML content
	 */
	function wrapRecordHeader($str, $row) {
		return $str;
	}

	/**
	 * Create record header (includes teh record icon, record title etc.)
	 *
	 * @param array $row Record row.
	 * @return string HTML
	 */
	function getRecordHeader($row) {
		$line = t3lib_iconWorks::getSpriteIconForRecord('tt_content', $row, array('title' => htmlspecialchars(t3lib_BEfunc::getRecordIconAltText($row, 'tt_content'))));
		$line .= t3lib_BEfunc::getRecordTitle('tt_content', $row, TRUE);
		return $this->wrapRecordTitle($line, $row);
	}

	/**
	 * Wrapping the title of the record.
	 *
	 * @param string $str The title value.
	 * @param array $row The record row.
	 * @return string Wrapped title string.
	 */
	function wrapRecordTitle($str, $row) {
		return '<a href="' . htmlspecialchars(t3lib_div::linkThisScript(array('uid' => intval($row['uid']), 'moveUid' => ''))) . '">' . $str . '</a>';
	}
}

?>