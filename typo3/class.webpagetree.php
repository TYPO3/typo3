<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2010 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Page navigation tree for the Web module
 *
 * Revised for TYPO3 3.6 2/2003 by Kasper Skaarhoj
 * XHTML compliant
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @author	Benjamin Mack   <bmack@xnos.org>
 *
 *
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   71: class webPageTree extends t3lib_browseTree
 *   81:     function webPageTree()
 *   92:     function wrapIcon($icon,&$row)
 *  130:     function wrapStop($str,$row)
 *  146:     function wrapTitle($title,$row,$bank=0)
 *  165:     function printTree($treeArr = '')
 *  271:     function PMicon($row,$a,$c,$nextCount,$exp)
 *  292:     function PMiconATagWrap($icon, $cmd, $isExpand = true)
 *  309:     function getBrowsableTree()
 *  377:     function getTree($uid, $depth=999, $depthData='',$blankLineCode='',$subCSSclass='')
 *
 *
 * TOTAL FUNCTIONS: 9
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


/**
 * Extension class for the t3lib_browsetree class, specially made
 * for browsing pages in the Web module
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @author	Benjamin Mack   <bmack@xnos.org>
 * @package TYPO3
 * @subpackage core
 * @see class t3lib_browseTree
 */
class webPageTree extends t3lib_browseTree {

	var $ext_showPageId;
	var $ext_IconMode;
	var $ext_separateNotinmenuPages;
	var $ext_alphasortNotinmenuPages;
	var $ajaxStatus = false; // Indicates, whether the ajax call was successful, i.e. the requested page has been found

	/**
	 * Calls init functions
	 *
	 * @return	void
	 */
	function webPageTree() {
		$this->init();
	}

	/**
	 * Wrapping icon in browse tree
	 *
	 * @param	string		Icon IMG code
	 * @param	array		Data row for element.
	 * @return	string		Page icon
	 */
	function wrapIcon($thePageIcon, &$row)	{
			// If the record is locked, present a warning sign.
		if ($lockInfo=t3lib_BEfunc::isRecordLocked('pages',$row['uid']))	{
			$aOnClick = 'alert('.$GLOBALS['LANG']->JScharCode($lockInfo['msg']).');return false;';
			$lockIcon='<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.
				t3lib_iconWorks::getSpriteIcon('status-warning-in-use',array('title'=>htmlspecialchars($lockInfo['msg']))).
				'</a>';
		} else $lockIcon = '';

			// Wrap icon in click-menu link.
		if (!$this->ext_IconMode)	{
			$thePageIcon = $GLOBALS['TBE_TEMPLATE']->wrapClickMenuOnIcon($thePageIcon,'pages',$row['uid'],0,'&bank='.$this->bank);
		} elseif (!strcmp($this->ext_IconMode,'titlelink'))	{
			$aOnClick = 'return jumpTo(\''.$this->getJumpToParam($row).'\',this,\''.$this->treeName.'\');';
			$thePageIcon='<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.$thePageIcon.'</a>';
		}

			// Wrap icon in a drag/drop span.
		$dragDropIcon = '<span class="dragIcon" id="dragIconID_'.$row['uid'].'">'.$thePageIcon.'</span>';

			// Add Page ID:
		$pageIdStr = '';
		if ($this->ext_showPageId) {
			$pageIdStr = '<span class="dragId">[' . $row['uid'] . ']</span> ';
		}

			// Call stats information hook
		$stat = '';
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks']))	{
			$_params = array('pages',$row['uid']);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'] as $_funcRef)	{
				$stat.=t3lib_div::callUserFunction($_funcRef,$_params,$this);
			}
		}

		return $dragDropIcon.$lockIcon.$pageIdStr.$stat;
	}

	/**
	 * Adds a red "+" to the input string, $str, if the field "php_tree_stop" in the $row (pages) is set
	 *
	 * @param	string		Input string, like a page title for the tree
	 * @param	array		record row with "php_tree_stop" field
	 * @return	string		Modified string
	 * @access private
	 */
	function wrapStop($str,$row)	{
		if ($row['php_tree_stop'])	{
			$str.='<a href="'.htmlspecialchars(t3lib_div::linkThisScript(array('setTempDBmount' => $row['uid']))).'" class="typo3-red">+</a> ';
		}
		return $str;
	}

	/**
	 * Wrapping $title in a-tags.
	 *
	 * @param	string		Title string
	 * @param	string		Item record
	 * @param	integer		Bank pointer (which mount point number)
	 * @return	string
	 * @access	private
	 */
	function wrapTitle($title,$row,$bank=0)	{
			// Hook for overriding the page title
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.webpagetree.php']['pageTitleOverlay'])) {
			$_params = array('title' => &$title, 'row' => &$row);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.webpagetree.php']['pageTitleOverlay'] as $_funcRef) {
				t3lib_div::callUserFunction($_funcRef, $_params, $this);
			}
			unset($_params);
		}

		$aOnClick = 'return jumpTo(\''.$this->getJumpToParam($row).'\',this,\''.$this->domIdPrefix.$this->getId($row).'\','.$bank.');';
		$CSM = '';
		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['useOnContextMenuHandler'])	{
			$CSM = ' oncontextmenu="'.htmlspecialchars($GLOBALS['TBE_TEMPLATE']->wrapClickMenuOnIcon('','pages',$row['uid'],0,'&bank='.$this->bank,'',TRUE)).';"';
		}
		$thePageTitle='<a href="#" onclick="'.htmlspecialchars($aOnClick).'"'.$CSM.'>'.$title.'</a>';

			// Wrap title in a drag/drop span.
		return '<span class="dragTitle" id="dragTitleID_'.$row['uid'].'">'.$thePageTitle.'</span>';
	}


	/**
	 * Compiles the HTML code for displaying the structure found inside the ->tree array
	 *
	 * @param	array		"tree-array" - if blank string, the internal ->tree array is used.
	 * @return	string		The HTML code for the tree
	 */
	function printTree($treeArr = '')   {
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
		$PM = t3lib_div::_GP('PM');
		if(($PMpos = strpos($PM, '#')) !== false) { $PM = substr($PM, 0, $PMpos); }
		$PM = explode('_', $PM);
		if ((TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX) && is_array($PM) && count($PM) == 4 && $PM[2] != 0) {
			if($PM[1])	{
				$expandedPageUid = $PM[2];
				$ajaxOutput = '';
				$invertedDepthOfAjaxRequestedItem = 0; // We don't know yet. Will be set later.
				$doExpand = true;
			} else	{
				$collapsedPageUid = $PM[2];
				$doCollapse = true;
			}
		}

		// we need to count the opened <ul>'s every time we dig into another level,
		// so we know how many we have to close when all children are done rendering
		$closeDepth = array();

		foreach($treeArr as $k => $v)	{
			$classAttr = $v['row']['_CSSCLASS'];
			$uid	   = $v['row']['uid'];
			$idAttr	= htmlspecialchars($this->domIdPrefix.$this->getId($v['row']).'_'.$v['bank']);
			$itemHTML  = '';

			// if this item is the start of a new level,
			// then a new level <ul> is needed, but not in ajax mode
			if($v['isFirst'] && !($doCollapse) && !($doExpand && $expandedPageUid == $uid))	{
				$itemHTML = '<ul>';
			}

			// add CSS classes to the list item
			if($v['hasSub']) { $classAttr .= ($classAttr) ? ' expanded': 'expanded'; }
			if($v['isLast']) { $classAttr .= ($classAttr) ? ' last'	: 'last';	 }

			$itemHTML .='
				<li id="'.$idAttr.'"'.($classAttr ? ' class="'.$classAttr.'"' : '').'><div class="treeLinkItem">'.
					$v['HTML'].
					$this->wrapTitle($this->getTitleStr($v['row'],$titleLen),$v['row'],$v['bank'])."</div>\n";


			if(!$v['hasSub']) { $itemHTML .= '</li>'; }

			// we have to remember if this is the last one
			// on level X so the last child on level X+1 closes the <ul>-tag
			if($v['isLast'] && !($doExpand && $expandedPageUid == $uid)) { $closeDepth[$v['invertedDepth']] = 1; }


			// if this is the last one and does not have subitems, we need to close
			// the tree as long as the upper levels have last items too
			if($v['isLast'] && !$v['hasSub'] && !$doCollapse && !($doExpand && $expandedPageUid == $uid)) {
				for ($i = $v['invertedDepth']; $closeDepth[$i] == 1; $i++) {
					$closeDepth[$i] = 0;
					$itemHTML .= '</ul></li>';
				}
			}

			// ajax request: collapse
			if($doCollapse && $collapsedPageUid == $uid) {
				$this->ajaxStatus = true;
				return $itemHTML;
			}

			// ajax request: expand
			if($doExpand && $expandedPageUid == $uid) {
				$ajaxOutput .= $itemHTML;
				$invertedDepthOfAjaxRequestedItem = $v['invertedDepth'];
			} elseif($invertedDepthOfAjaxRequestedItem) {
				if($v['invertedDepth'] < $invertedDepthOfAjaxRequestedItem) {
					$ajaxOutput .= $itemHTML;
				} else {
					$this->ajaxStatus = true;
					return $ajaxOutput;
				}
			}

			$out .= $itemHTML;
		}

		if($ajaxOutput) {
			$this->ajaxStatus = true;
			return $ajaxOutput;
		}

		// finally close the first ul
		$out .= '</ul>';
		return $out;
	}


	/**
	 * Generate the plus/minus icon for the browsable tree.
	 *
	 * @param	array		record for the entry
	 * @param	integer		The current entry number
	 * @param	integer		The total number of entries. If equal to $a, a "bottom" element is returned.
	 * @param	integer		The number of sub-elements to the current element.
	 * @param	boolean		The element was expanded to render subelements if this flag is set.
	 * @return	string		Image tag with the plus/minus icon.
	 * @access private
	 * @see t3lib_pageTree::PMicon()
	 */
	function PMicon($row,$a,$c,$nextCount,$exp)	{
		$PM   = $nextCount ? ($exp ? 'minus' : 'plus') : 'join';
		$BTM  = ($a == $c) ? 'bottom' : '';
		$icon = '<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/ol/'.$PM.$BTM.'.gif','width="18" height="16"').' alt="" />';

		if ($nextCount) {
			$cmd = $this->bank.'_'.($exp?'0_':'1_').$row['uid'].'_'.$this->treeName;
			$icon = $this->PMiconATagWrap($icon,$cmd,!$exp);
		}
		return $icon;
	}


	/**
	 * Wrap the plus/minus icon in a link
	 *
	 * @param	string		HTML string to wrap, probably an image tag.
	 * @param	string		Command for 'PM' get var
	 * @return	string		Link-wrapped input string
	 * @access private
	 */
	function PMiconATagWrap($icon, $cmd, $isExpand = true)	{
		if ($this->thisScript) {
				// activate dynamic ajax-based tree
			$js = htmlspecialchars('Tree.load(\''.$cmd.'\', '.intval($isExpand).', this);');
			return '<a class="pm" onclick="'.$js.'">'.$icon.'</a>';
		} else {
			return $icon;
		}
	}


	/**
	 * Will create and return the HTML code for a browsable tree
	 * Is based on the mounts found in the internal array ->MOUNTS (set in the constructor)
	 *
	 * @return	string		HTML code for the browsable tree
	 */
	function getBrowsableTree() {

			// Get stored tree structure AND updating it if needed according to incoming PM GET var.
		$this->initializePositionSaving();

			// Init done:
		$titleLen = intval($this->BE_USER->uc['titleLen']);
		$treeArr = array();

			// Traverse mounts:
		foreach($this->MOUNTS as $idx => $uid)  {

				// Set first:
			$this->bank = $idx;
			$isOpen = $this->stored[$idx][$uid] || $this->expandFirst || $uid === '0';

				// Save ids while resetting everything else.
			$curIds = $this->ids;
			$this->reset();
			$this->ids = $curIds;

				// Set PM icon for root of mount:
			$cmd = $this->bank.'_'.($isOpen? "0_" : "1_").$uid.'_'.$this->treeName;
				// only, if not for uid 0
			if ($uid) {
				$icon = '<img' . t3lib_iconWorks::skinImg($this->backPath,'gfx/ol/' . ($isOpen ? 'minus' :'plus' ) . 'only.gif') . ' alt="" />';
				$firstHtml = $this->PMiconATagWrap($icon, $cmd, !$isOpen);
			}

				// Preparing rootRec for the mount
			if ($uid)   {
				$rootRec = $this->getRecord($uid);
				$firstHtml.=$this->getIcon($rootRec);
			} else {
				// Artificial record for the tree root, id=0
				$rootRec = $this->getRootRecord($uid);
				$firstHtml.=$this->getRootIcon($rootRec);
			}

			if (is_array($rootRec)) {
					// In case it was swapped inside getRecord due to workspaces.
				$uid = $rootRec['uid'];

					// Add the root of the mount to ->tree
				$this->tree[] = array('HTML'=>$firstHtml, 'row'=>$rootRec, 'bank'=>$this->bank, 'hasSub'=>true, 'invertedDepth'=>1000);

					// If the mount is expanded, go down:
				if ($isOpen)	{
						// Set depth:
					if ($this->addSelfId) { $this->ids[] = $uid; }
					$this->getTree($uid, 999, '', $rootRec['_SUBCSSCLASS']);
				}
					// Add tree:
				$treeArr=array_merge($treeArr,$this->tree);
			}
		}
		return $this->printTree($treeArr);
	}


	/**
	 * Fetches the data for the tree
	 *
	 * @param	integer		item id for which to select subitems (parent id)
	 * @param	integer		Max depth (recursivity limit)
	 * @param	string		? (internal)
	 * @return	integer		The count of items on the level
	 */
	function getTree($uid, $depth=999, $blankLineCode='', $subCSSclass='') {

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
		while ($crazyRecursionLimiter > 0 && $row = $this->getDataNext($res,$subCSSclass))	{
			$crazyRecursionLimiter--;

				// Not in menu:
				// @TODO: RFC #7370: doktype 2&5 are deprecated since TYPO3 4.2-beta1
			if ($this->ext_separateNotinmenuPages && (t3lib_div::inList('5,6',$row['doktype']) || $row['doktype']>=200 || $row['nav_hide']))	{
				$outOfMenuPages[] = $row;
				$outOfMenuPagesTextIndex[] = ($row['doktype']>=200 ? 'zzz'.$row['doktype'].'_' : '').$row['title'];
			} else {
				$inMenuPages[] = $row;
			}
		}

		$label_shownAlphabetically = "";
		if (count($outOfMenuPages))	{
				// Sort out-of-menu pages:
			$outOfMenuPages_alphabetic = array();
			if ($this->ext_alphasortNotinmenuPages)	{
				asort($outOfMenuPagesTextIndex);
				$label_shownAlphabetically = " (alphabetic)";
			}
			foreach($outOfMenuPagesTextIndex as $idx => $txt)	{
				$outOfMenuPages_alphabetic[] = $outOfMenuPages[$idx];
			}

				// Merge:
			$outOfMenuPages_alphabetic[0]['_FIRST_NOT_IN_MENU']=TRUE;
			$allRows = array_merge($inMenuPages,$outOfMenuPages_alphabetic);
		} else {
			$allRows = $inMenuPages;
		}

			// Traverse the records:
		foreach ($allRows as $row)	{
			$a++;

			$newID = $row['uid'];
			$this->tree[]=array();	  // Reserve space.
			end($this->tree);
			$treeKey = key($this->tree);	// Get the key for this space
			$LN = ($a==$c) ? 'blank' : 'line';

				// If records should be accumulated, do so
			if ($this->setRecs) { $this->recs[$row['uid']] = $row; }

				// Accumulate the id of the element in the internal arrays
			$this->ids[]=$idH[$row['uid']]['uid'] = $row['uid'];
			$this->ids_hierarchy[$depth][] = $row['uid'];

				// Make a recursive call to the next level
			if ($depth > 1 && $this->expandNext($newID) && !$row['php_tree_stop'])	{
				$nextCount=$this->getTree(
					$newID,
					$depth-1,
					$blankLineCode.','.$LN,
					$row['_SUBCSSCLASS']
				);
				if (count($this->buffer_idH)) { $idH[$row['uid']]['subrow']=$this->buffer_idH; }
				$exp = 1; // Set "did expand" flag
			} else {
				$nextCount = $this->getCount($newID);
				$exp = 0; // Clear "did expand" flag
			}

				// Set HTML-icons, if any:
			if ($this->makeHTML)	{
				if ($row['_FIRST_NOT_IN_MENU'])	{
					$HTML = '<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/ol/line.gif').' alt="" /><br/><img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/ol/line.gif').' alt="" /><i>Not shown in menu'.$label_shownAlphabetically.':</i><br>';
				} else {
					$HTML = '';
				}

				$HTML.= $this->PMicon($row,$a,$c,$nextCount,$exp);
				$HTML.= $this->wrapStop($this->getIcon($row),$row);
			}

				// Finally, add the row/HTML content to the ->tree array in the reserved key.
			$this->tree[$treeKey] = array(
				'row'    => $row,
				'HTML'   => $HTML,
				'hasSub' => $nextCount&&$this->expandNext($newID),
				'isFirst'=> $a==1,
				'isLast' => false,
				'invertedDepth'=> $depth,
				'blankLineCode'=> $blankLineCode,
				'bank' => $this->bank
			);
		}

		if($a) { $this->tree[$treeKey]['isLast'] = true; }

		$this->getDataFree($res);
		$this->buffer_idH = $idH;
		return $c;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/class.webpagetree.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/class.webpagetree.php']);
}

?>