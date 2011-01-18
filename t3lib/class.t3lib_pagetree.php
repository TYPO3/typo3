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
 * Generate a page-tree, non-browsable.
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
 *   78: class t3lib_pageTree extends t3lib_treeView
 *   90:	 function init($clause='')
 *  106:	 function expandNext($id)
 *  123:	 function PMicon($row,$a,$c,$nextCount,$exp)
 *  138:	 function initializePositionSaving()
 *
 * TOTAL FUNCTIONS: 4
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


/**
 * Class for generating a page tree.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @coauthor	René Fritz <r.fritz@colorcube.de>
 * @see t3lib_treeView, t3lib_browseTree
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_pageTree extends t3lib_treeView {
	var $fieldArray = array(
		'uid',
		'title',
		'doktype',
		'php_tree_stop',
		't3ver_id',
		't3ver_state',
		't3ver_swapmode'
	);
	var $defaultList = 'uid,pid,tstamp,sorting,deleted,perms_userid,perms_groupid,perms_user,perms_group,perms_everybody,crdate,cruser_id';
	var $setRecs = 0;

	/**
	 * Init function
	 * REMEMBER to feed a $clause which will filter out non-readable pages!
	 *
	 * @param	string		Part of where query which will filter out non-readable pages.
	 * @return	void
	 */
	function init($clause = '') {
		parent::init(' AND deleted=0 ' . $clause, 'sorting');

		if (t3lib_extMgm::isLoaded('cms')) {
			$this->fieldArray = array_merge(
				$this->fieldArray,
				array(
					 'hidden',
					 'starttime',
					 'endtime',
					 'fe_group',
					 'module',
					 'extendToSubpages',
					 'nav_hide')
			);
		}
		$this->table = 'pages';
		$this->treeName = 'pages';
	}

	/**
	 * Returns true/false if the next level for $id should be expanded - and all levels should, so we always return 1.
	 *
	 * @param	integer		ID (uid) to test for (see extending classes where this is checked againts session data)
	 * @return	boolean
	 */
	function expandNext($id) {
		return 1;
	}

	/**
	 * Generate the plus/minus icon for the browsable tree.
	 * In this case, there is no plus-minus icon displayed.
	 *
	 * @param	array		record for the entry
	 * @param	integer		The current entry number
	 * @param	integer		The total number of entries. If equal to $a, a 'bottom' element is returned.
	 * @param	integer		The number of sub-elements to the current element.
	 * @param	boolean		The element was expanded to render subelements if this flag is set.
	 * @return	string		Image tag with the plus/minus icon.
	 * @access private
	 * @see t3lib_treeView::PMicon()
	 */
	function PMicon($row, $a, $c, $nextCount, $exp) {
		$PM = 'join';
		$BTM = ($a == $c) ? 'bottom' : '';
		$icon = '<img' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/ol/' . $PM . $BTM . '.gif', 'width="18" height="16"') . ' alt="" />';

		return $icon;
	}


	/**
	 * Get stored tree structure AND updating it if needed according to incoming PM GET var.
	 * - Here we just set it to nothing since we want to just render the tree, nothing more.
	 *
	 * @return	void
	 * @access private
	 */
	function initializePositionSaving() {
		$this->stored = array();
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_pagetree.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_pagetree.php']);
}
?>