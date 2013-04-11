<?php
namespace TYPO3\CMS\Backend\View;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Browse pages in Web module
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author Benjamin Mack <bmack@xnos.org>
 */
class PageTreeView extends \TYPO3\CMS\Backend\Tree\View\BrowseTreeView {

	/**
	 * @todo Define visibility
	 */
	public $ext_showPageId;

	/**
	 * @todo Define visibility
	 */
	public $ext_IconMode;

	/**
	 * @todo Define visibility
	 */
	public $ext_separateNotinmenuPages;

	/**
	 * @todo Define visibility
	 */
	public $ext_alphasortNotinmenuPages;

	// Indicates, whether the ajax call was successful, i.e. the requested page has been found
	/**
	 * @todo Define visibility
	 */
	public $ajaxStatus = FALSE;

	/**
	 * Calls init functions
	 *
	 * @todo Define visibility
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Wrapping icon in browse tree
	 *
	 * @param string $thePageIcon Icon IMG code
	 * @param array $row Data row for element.
	 * @return string Page icon
	 * @todo Define visibility
	 */
	public function wrapIcon($thePageIcon, &$row) {
		// If the record is locked, present a warning sign.
		if ($lockInfo = \TYPO3\CMS\Backend\Utility\BackendUtility::isRecordLocked('pages', $row['uid'])) {
			$aOnClick = 'alert(' . $GLOBALS['LANG']->JScharCode($lockInfo['msg']) . ');return false;';
			$lockIcon = '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-warning-in-use', array('title' => htmlspecialchars($lockInfo['msg']))) . '</a>';
		} else {
			$lockIcon = '';
		}
		// Wrap icon in click-menu link.
		if (!$this->ext_IconMode) {
			$thePageIcon = $GLOBALS['TBE_TEMPLATE']->wrapClickMenuOnIcon($thePageIcon, 'pages', $row['uid'], 0, '&bank=' . $this->bank);
		} elseif (!strcmp($this->ext_IconMode, 'titlelink')) {
			$aOnClick = 'return jumpTo(\'' . $this->getJumpToParam($row) . '\',this,\'' . $this->treeName . '\');';
			$thePageIcon = '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' . $thePageIcon . '</a>';
		}
		// Wrap icon in a drag/drop span.
		$dragDropIcon = '<span class="dragIcon" id="dragIconID_' . $row['uid'] . '">' . $thePageIcon . '</span>';
		// Add Page ID:
		$pageIdStr = '';
		if ($this->ext_showPageId) {
			$pageIdStr = '<span class="dragId">[' . $row['uid'] . ']</span> ';
		}
		// Call stats information hook
		$stat = '';
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'])) {
			$_params = array('pages', $row['uid']);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'] as $_funcRef) {
				$stat .= \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $_params, $this);
			}
		}
		return $dragDropIcon . $lockIcon . $pageIdStr . $stat;
	}

	/**
	 * Adds a red "+" to the input string, $str, if the field "php_tree_stop" in the $row (pages) is set
	 *
	 * @param string $str Input string, like a page title for the tree
	 * @param array $row Record row with "php_tree_stop" field
	 * @return string Modified string
	 * @access private
	 * @todo Define visibility
	 */
	public function wrapStop($str, $row) {
		if ($row['php_tree_stop']) {
			$str .= '<a href="' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(array('setTempDBmount' => $row['uid']))) . '" class="typo3-red">+</a> ';
		}
		return $str;
	}

	/**
	 * Wrapping $title in a-tags.
	 *
	 * @param string $title Title string
	 * @param string $row Item record
	 * @param integer $bank Bank pointer (which mount point number)
	 * @return string
	 * @access private
	 * @todo Define visibility
	 */
	public function wrapTitle($title, $row, $bank = 0) {
		// Hook for overriding the page title
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.webpagetree.php']['pageTitleOverlay'])) {
			$_params = array('title' => &$title, 'row' => &$row);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.webpagetree.php']['pageTitleOverlay'] as $_funcRef) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $_params, $this);
			}
			unset($_params);
		}
		$aOnClick = 'return jumpTo(\'' . $this->getJumpToParam($row) . '\',this,\'' . $this->domIdPrefix . $this->getId($row) . '\',' . $bank . ');';
		$CSM = ' oncontextmenu="' . htmlspecialchars($GLOBALS['TBE_TEMPLATE']->wrapClickMenuOnIcon('', 'pages', $row['uid'], 0, ('&bank=' . $this->bank), '', TRUE)) . ';"';
		$thePageTitle = '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '"' . $CSM . '>' . $title . '</a>';
		// Wrap title in a drag/drop span.
		return '<span class="dragTitle" id="dragTitleID_' . $row['uid'] . '">' . $thePageTitle . '</span>';
	}

	/**
	 * Compiles the HTML code for displaying the structure found inside the ->tree array
	 *
	 * @param array $treeArr "tree-array" - if blank string, the internal ->tree array is used.
	 * @return string The HTML code for the tree
	 * @todo Define visibility
	 */
	public function printTree($treeArr = '') {
		$titleLen = intval($this->BE_USER->uc['titleLen']);
		if (!is_array($treeArr)) {
			$treeArr = $this->tree;
		}
		$out = '
			<!-- TYPO3 tree structure. -->
			<ul class="tree" id="treeRoot">
		';
		// -- evaluate AJAX request
		// IE takes anchor as parameter
		$PM = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('PM');
		if (($PMpos = strpos($PM, '#')) !== FALSE) {
			$PM = substr($PM, 0, $PMpos);
		}
		$PM = explode('_', $PM);
		if (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX && is_array($PM) && count($PM) == 4 && $PM[2] != 0) {
			if ($PM[1]) {
				$expandedPageUid = $PM[2];
				$ajaxOutput = '';
				// We don't know yet. Will be set later.
				$invertedDepthOfAjaxRequestedItem = 0;
				$doExpand = TRUE;
			} else {
				$collapsedPageUid = $PM[2];
				$doCollapse = TRUE;
			}
		}
		// We need to count the opened <ul>'s every time we dig into another level,
		// so we know how many we have to close when all children are done rendering
		$closeDepth = array();
		foreach ($treeArr as $k => $v) {
			$classAttr = $v['row']['_CSSCLASS'];
			$uid = $v['row']['uid'];
			$idAttr = htmlspecialchars($this->domIdPrefix . $this->getId($v['row']) . '_' . $v['bank']);
			$itemHTML = '';
			// If this item is the start of a new level,
			// then a new level <ul> is needed, but not in ajax mode
			if ($v['isFirst'] && !$doCollapse && !($doExpand && $expandedPageUid == $uid)) {
				$itemHTML = '<ul>';
			}
			// Add CSS classes to the list item
			if ($v['hasSub']) {
				$classAttr .= $classAttr ? ' expanded' : 'expanded';
			}
			if ($v['isLast']) {
				$classAttr .= $classAttr ? ' last' : 'last';
			}
			$itemHTML .= '
				<li id="' . $idAttr . '"' . ($classAttr ? ' class="' . $classAttr . '"' : '') . '><div class="treeLinkItem">' . $v['HTML'] . $this->wrapTitle($this->getTitleStr($v['row'], $titleLen), $v['row'], $v['bank']) . '</div>
';
			if (!$v['hasSub']) {
				$itemHTML .= '</li>';
			}
			// We have to remember if this is the last one
			// on level X so the last child on level X+1 closes the <ul>-tag
			if ($v['isLast'] && !($doExpand && $expandedPageUid == $uid)) {
				$closeDepth[$v['invertedDepth']] = 1;
			}
			// If this is the last one and does not have subitems, we need to close
			// the tree as long as the upper levels have last items too
			if ($v['isLast'] && !$v['hasSub'] && !$doCollapse && !($doExpand && $expandedPageUid == $uid)) {
				for ($i = $v['invertedDepth']; $closeDepth[$i] == 1; $i++) {
					$closeDepth[$i] = 0;
					$itemHTML .= '</ul></li>';
				}
			}
			// Ajax request: collapse
			if ($doCollapse && $collapsedPageUid == $uid) {
				$this->ajaxStatus = TRUE;
				return $itemHTML;
			}
			// ajax request: expand
			if ($doExpand && $expandedPageUid == $uid) {
				$ajaxOutput .= $itemHTML;
				$invertedDepthOfAjaxRequestedItem = $v['invertedDepth'];
			} elseif ($invertedDepthOfAjaxRequestedItem) {
				if ($v['invertedDepth'] < $invertedDepthOfAjaxRequestedItem) {
					$ajaxOutput .= $itemHTML;
				} else {
					$this->ajaxStatus = TRUE;
					return $ajaxOutput;
				}
			}
			$out .= $itemHTML;
		}
		if ($ajaxOutput) {
			$this->ajaxStatus = TRUE;
			return $ajaxOutput;
		}
		// Finally close the first ul
		$out .= '</ul>';
		return $out;
	}

	/**
	 * Generate the plus/minus icon for the browsable tree.
	 *
	 * @param array $row Record for the entry
	 * @param integer $a The current entry number
	 * @param integer $c The total number of entries. If equal to $a, a "bottom" element is returned.
	 * @param integer $nextCount The number of sub-elements to the current element.
	 * @param boolean $exp The element was expanded to render subelements if this flag is set.
	 * @return string Image tag with the plus/minus icon.
	 * @access private
	 * @see \TYPO3\CMS\Backend\Tree\View\PageTreeView::PMicon()
	 * @todo Define visibility
	 */
	public function PMicon($row, $a, $c, $nextCount, $exp) {
		$PM = $nextCount ? ($exp ? 'minus' : 'plus') : 'join';
		$BTM = $a == $c ? 'bottom' : '';
		$icon = '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->backPath, ('gfx/ol/' . $PM . $BTM . '.gif'), 'width="18" height="16"') . ' alt="" />';
		if ($nextCount) {
			$cmd = $this->bank . '_' . ($exp ? '0_' : '1_') . $row['uid'] . '_' . $this->treeName;
			$icon = $this->PMiconATagWrap($icon, $cmd, !$exp);
		}
		return $icon;
	}

	/**
	 * Wrap the plus/minus icon in a link
	 *
	 * @param string $icon HTML string to wrap, probably an image tag.
	 * @param string $cmd Command for 'PM' get var
	 * @return boolean $isExpand Link-wrapped input string
	 * @access private
	 * @todo Define visibility
	 */
	public function PMiconATagWrap($icon, $cmd, $isExpand = TRUE) {
		if ($this->thisScript) {
			// Activate dynamic ajax-based tree
			$js = htmlspecialchars('Tree.load(\'' . $cmd . '\', ' . intval($isExpand) . ', this);');
			return '<a class="pm" onclick="' . $js . '">' . $icon . '</a>';
		} else {
			return $icon;
		}
	}

	/**
	 * Will create and return the HTML code for a browsable tree
	 * Is based on the mounts found in the internal array ->MOUNTS (set in the constructor)
	 *
	 * @return string HTML code for the browsable tree
	 * @todo Define visibility
	 */
	public function getBrowsableTree() {
		// Get stored tree structure AND updating it if needed according to incoming PM GET var.
		$this->initializePositionSaving();
		// Init done:
		$titleLen = intval($this->BE_USER->uc['titleLen']);
		$treeArr = array();
		// Traverse mounts:
		foreach ($this->MOUNTS as $idx => $uid) {
			// Set first:
			$this->bank = $idx;
			$isOpen = $this->stored[$idx][$uid] || $this->expandFirst || $uid === '0';
			// Save ids while resetting everything else.
			$curIds = $this->ids;
			$this->reset();
			$this->ids = $curIds;
			// Set PM icon for root of mount:
			$cmd = $this->bank . '_' . ($isOpen ? '0_' : '1_') . $uid . '_' . $this->treeName;
			// Only, if not for uid 0
			if ($uid) {
				$icon = '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->backPath, ('gfx/ol/' . ($isOpen ? 'minus' : 'plus') . 'only.gif')) . ' alt="" />';
				$firstHtml = $this->PMiconATagWrap($icon, $cmd, !$isOpen);
			}
			// Preparing rootRec for the mount
			if ($uid) {
				$rootRec = $this->getRecord($uid);
				$firstHtml .= $this->getIcon($rootRec);
			} else {
				// Artificial record for the tree root, id=0
				$rootRec = $this->getRootRecord($uid);
				$firstHtml .= $this->getRootIcon($rootRec);
			}
			if (is_array($rootRec)) {
				// In case it was swapped inside getRecord due to workspaces.
				$uid = $rootRec['uid'];
				// Add the root of the mount to ->tree
				$this->tree[] = array('HTML' => $firstHtml, 'row' => $rootRec, 'bank' => $this->bank, 'hasSub' => TRUE, 'invertedDepth' => 1000);
				// If the mount is expanded, go down:
				if ($isOpen) {
					// Set depth:
					if ($this->addSelfId) {
						$this->ids[] = $uid;
					}
					$this->getTree($uid, 999, '', $rootRec['_SUBCSSCLASS']);
				}
				// Add tree:
				$treeArr = array_merge($treeArr, $this->tree);
			}
		}
		return $this->printTree($treeArr);
	}

	/**
	 * Fetches the data for the tree
	 *
	 * @param integer $uid Item id for which to select subitems (parent id)
	 * @param integer $depth Max depth (recursivity limit)
	 * @param string $blankLineCode ? (internal)
	 * @param string $subCSSclass
	 * @return integer The count of items on the level
	 * @todo Define visibility
	 */
	public function getTree($uid, $depth = 999, $blankLineCode = '', $subCSSclass = '') {
		// Buffer for id hierarchy is reset:
		$this->buffer_idH = array();
		// Init vars
		$depth = intval($depth);
		$HTML = '';
		$a = 0;
		$res = $this->getDataInit($uid, $subCSSclass);
		$c = $this->getDataCount($res);
		$crazyRecursionLimiter = 999;
		$inMenuPages = array();
		$outOfMenuPages = array();
		$outOfMenuPagesTextIndex = array();
		while ($crazyRecursionLimiter > 0 && ($row = $this->getDataNext($res, $subCSSclass))) {
			$crazyRecursionLimiter--;
			// Not in menu:
			if ($this->ext_separateNotinmenuPages && ($row['doktype'] == \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_BE_USER_SECTION || $row['doktype'] >= 200 || $row['nav_hide'])) {
				$outOfMenuPages[] = $row;
				$outOfMenuPagesTextIndex[] = ($row['doktype'] >= 200 ? 'zzz' . $row['doktype'] . '_' : '') . $row['title'];
			} else {
				$inMenuPages[] = $row;
			}
		}
		$label_shownAlphabetically = '';
		if (count($outOfMenuPages)) {
			// Sort out-of-menu pages:
			$outOfMenuPages_alphabetic = array();
			if ($this->ext_alphasortNotinmenuPages) {
				asort($outOfMenuPagesTextIndex);
				$label_shownAlphabetically = ' (alphabetic)';
			}
			foreach ($outOfMenuPagesTextIndex as $idx => $txt) {
				$outOfMenuPages_alphabetic[] = $outOfMenuPages[$idx];
			}
			// Merge:
			$outOfMenuPages_alphabetic[0]['_FIRST_NOT_IN_MENU'] = TRUE;
			$allRows = array_merge($inMenuPages, $outOfMenuPages_alphabetic);
		} else {
			$allRows = $inMenuPages;
		}
		// Traverse the records:
		foreach ($allRows as $row) {
			$a++;
			$newID = $row['uid'];
			// Reserve space.
			$this->tree[] = array();
			end($this->tree);
			// Get the key for this space
			$treeKey = key($this->tree);
			$LN = $a == $c ? 'blank' : 'line';
			// If records should be accumulated, do so
			if ($this->setRecs) {
				$this->recs[$row['uid']] = $row;
			}
			// Accumulate the id of the element in the internal arrays
			$this->ids[] = ($idH[$row['uid']]['uid'] = $row['uid']);
			$this->ids_hierarchy[$depth][] = $row['uid'];
			// Make a recursive call to the next level
			if ($depth > 1 && $this->expandNext($newID) && !$row['php_tree_stop']) {
				$nextCount = $this->getTree($newID, $depth - 1, $blankLineCode . ',' . $LN, $row['_SUBCSSCLASS']);
				if (count($this->buffer_idH)) {
					$idH[$row['uid']]['subrow'] = $this->buffer_idH;
				}
				// Set "did expand" flag
				$exp = 1;
			} else {
				$nextCount = $this->getCount($newID);
				// Clear "did expand" flag
				$exp = 0;
			}
			// Set HTML-icons, if any:
			if ($this->makeHTML) {
				if ($row['_FIRST_NOT_IN_MENU']) {
					$HTML = '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->backPath, 'gfx/ol/line.gif') . ' alt="" /><br/><img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->backPath, 'gfx/ol/line.gif') . ' alt="" /><i>Not shown in menu' . $label_shownAlphabetically . ':</i><br>';
				} else {
					$HTML = '';
				}
				$HTML .= $this->PMicon($row, $a, $c, $nextCount, $exp);
				$HTML .= $this->wrapStop($this->getIcon($row), $row);
			}
			// Finally, add the row/HTML content to the ->tree array in the reserved key.
			$this->tree[$treeKey] = array(
				'row' => $row,
				'HTML' => $HTML,
				'hasSub' => $nextCount && $this->expandNext($newID),
				'isFirst' => $a == 1,
				'isLast' => FALSE,
				'invertedDepth' => $depth,
				'blankLineCode' => $blankLineCode,
				'bank' => $this->bank
			);
		}
		if ($a) {
			$this->tree[$treeKey]['isLast'] = TRUE;
		}
		$this->getDataFree($res);
		$this->buffer_idH = $idH;
		return $c;
	}

}


?>