<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2010 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Generate a page-tree, browsable.
 *
 * $Id$
 * Revised for TYPO3 3.6 November/2003 by Kasper Skårhøj
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @coauthor	René Fritz <r.fritz@colorcube.de>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   74: class t3lib_browseTree extends t3lib_treeView
 *   83:	 function init($clause='')
 *  116:	 function getTitleAttrib($row)
 *  128:	 function wrapIcon($icon,$row)
 *  150:	 function getTitleStr($row,$titleLen=30)
 *
 * TOTAL FUNCTIONS: 4
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


/**
 * Extension class for the t3lib_treeView class, specially made for browsing pages
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @coauthor	René Fritz <r.fritz@colorcube.de>
 * @see t3lib_treeView, t3lib_pageTree
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_browseTree extends t3lib_treeView {

	/**
	 * Initialize, setting what is necessary for browsing pages.
	 * Using the current user.
	 *
	 * @param	string		Additional clause for selecting pages.
	 * @return	void
	 */
	function init($clause = '') {

			// this will hide records from display - it has nothing todo with user rights!!
		$clauseExludePidList = '';
		if ($pidList = $GLOBALS['BE_USER']->getTSConfigVal('options.hideRecords.pages')) {
			if ($pidList = $GLOBALS['TYPO3_DB']->cleanIntList($pidList)) {
				$clauseExludePidList = ' AND pages.uid NOT IN (' . $pidList . ')';
			}
		}

			// This is very important for making trees of pages: Filtering out deleted pages, pages with no access to and sorting them correctly:
		parent::init(' AND ' . $GLOBALS['BE_USER']->getPagePermsClause(1) . ' ' . $clause . $clauseExludePidList, 'sorting');

		$this->table = 'pages';
		$this->setTreeName('browsePages');
		$this->domIdPrefix = 'pages';
		$this->iconName = '';
		$this->title = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
		$this->MOUNTS = $GLOBALS['WEBMOUNTS'];

		if ($pidList) {
				// Remove mountpoint if explicitely set in options.hideRecords.pages (see above)
			$hideList = explode(',', $pidList);
			$this->MOUNTS = array_diff($this->MOUNTS, $hideList);
		}

		$this->fieldArray = array_merge(
			$this->fieldArray,
			array('doktype', 'php_tree_stop', 't3ver_id', 't3ver_state', 't3ver_wsid', 't3ver_swapmode', 't3ver_state', 't3ver_move_id')
		);
		if (t3lib_extMgm::isLoaded('cms')) {
			$this->fieldArray = array_merge(
				$this->fieldArray,
				array('hidden', 'starttime', 'endtime', 'fe_group', 'module', 'extendToSubpages', 'is_siteroot', 'nav_hide')
			);
		}
	}

	/**
	 * Creates title attribute content for pages.
	 * Uses API function in t3lib_BEfunc which will retrieve lots of useful information for pages.
	 *
	 * @param	array		The table row.
	 * @return	string
	 */
	function getTitleAttrib($row) {
		return t3lib_BEfunc::titleAttribForPages($row, '1=1 ' . $this->clause, 0);
	}

	/**
	 * Wrapping the image tag, $icon, for the row, $row (except for mount points)
	 *
	 * @param	string		The image tag for the icon
	 * @param	array		The row for the current element
	 * @return	string		The processed icon input value.
	 * @access private
	 */
	function wrapIcon($icon, $row) {
			// Add title attribute to input icon tag
		$theIcon = $this->addTagAttributes($icon, ($this->titleAttrib ? $this->titleAttrib . '="' . $this->getTitleAttrib($row) . '"' : ''));

			// Wrap icon in click-menu link.
		if (!$this->ext_IconMode) {
			$theIcon = $GLOBALS['TBE_TEMPLATE']->wrapClickMenuOnIcon($theIcon, $this->treeName, $this->getId($row), 0);
		} elseif (!strcmp($this->ext_IconMode, 'titlelink')) {
			$aOnClick = 'return jumpTo(\'' . $this->getJumpToParam($row) . '\',this,\'' . $this->domIdPrefix . $this->getId($row) . '\',' . $this->bank . ');';
			$theIcon = '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' . $theIcon . '</a>';
		}
		return $theIcon;
	}

	/**
	 * Returns the title for the input record. If blank, a "no title" label (localized) will be returned.
	 * Do NOT htmlspecialchar the string from this function - has already been done.
	 *
	 * @param	array		The input row array (where the key "title" is used for the title)
	 * @param	integer		Title length (30)
	 * @return	string		The title.
	 */
	function getTitleStr($row, $titleLen = 30) {
			// get the basic title from the parent implementation in t3lib_treeview
		$title = parent::getTitleStr($row, $titleLen);
		if (isset($row['is_siteroot']) && $row['is_siteroot'] != 0 && $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.showDomainNameWithTitle')) {
			$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('domainName,sorting', 'sys_domain',
					'pid=' . $GLOBALS['TYPO3_DB']->quoteStr($row['uid'] . t3lib_BEfunc::deleteClause('sys_domain') . t3lib_BEfunc::BEenableFields('sys_domain'), 'sys_domain'), '', 'sorting', 1);
			if (is_array($rows) && count($rows) > 0) {
				$title = sprintf('%s [%s]', $title, htmlspecialchars($rows[0]['domainName']));
			}
		}
		return $title;
	}

	/**
	 * Adds a red "+" to the input string, $str, if the field "php_tree_stop" in the $row (pages) is set
	 *
	 * @param	string		Input string, like a page title for the tree
	 * @param	array		record row with "php_tree_stop" field
	 * @return	string		Modified string
	 * @access private
	 */
	function wrapStop($str, $row) {
		if ($row['php_tree_stop']) {
			$str .= '<span class="typo3-red">
								<a href="' . htmlspecialchars(t3lib_div::linkThisScript(array('setTempDBmount' => $row['uid']))) . '" class="typo3-red">+</a>
							</span>';
		}
		return $str;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_browsetree.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_browsetree.php']);
}

?>