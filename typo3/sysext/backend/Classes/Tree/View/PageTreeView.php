<?php
namespace TYPO3\CMS\Backend\Tree\View;

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
 * Generate a page-tree, non-browsable.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @coauthor René Fritz <r.fritz@colorcube.de>
 */
class PageTreeView extends \TYPO3\CMS\Backend\Tree\View\AbstractTreeView {

	/**
	 * @todo Define visibility
	 */
	public $fieldArray = array(
		'uid',
		'title',
		'doktype',
		'php_tree_stop',
		't3ver_id',
		't3ver_state'
	);

	/**
	 * @todo Define visibility
	 */
	public $defaultList = 'uid,pid,tstamp,sorting,deleted,perms_userid,perms_groupid,perms_user,perms_group,perms_everybody,crdate,cruser_id';

	/**
	 * @todo Define visibility
	 */
	public $setRecs = 0;

	/**
	 * Init function
	 * REMEMBER to feed a $clause which will filter out non-readable pages!
	 *
	 * @param string $clause Part of where query which will filter out non-readable pages.
	 * @param string $orderByFields Record ORDER BY field
	 * @return void
	 * @todo Define visibility
	 */
	public function init($clause = '', $orderByFields = '') {
		parent::init(' AND deleted=0 ' . $clause, 'sorting');
		if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('cms')) {
			$this->fieldArray = array_merge($this->fieldArray, array(
				'hidden',
				'starttime',
				'endtime',
				'fe_group',
				'module',
				'extendToSubpages',
				'nav_hide'
			));
		}
		$this->table = 'pages';
		$this->treeName = 'pages';
	}

	/**
	 * Returns TRUE/FALSE if the next level for $id should be expanded - and all levels should, so we always return 1.
	 *
	 * @param integer $id ID (uid) to test for (see extending classes where this is checked againts session data)
	 * @return boolean
	 * @todo Define visibility
	 */
	public function expandNext($id) {
		return 1;
	}

	/**
	 * Generate the plus/minus icon for the browsable tree.
	 * In this case, there is no plus-minus icon displayed.
	 *
	 * @param array $row Record for the entry
	 * @param integer $a The current entry number
	 * @param integer $c The total number of entries. If equal to $a, a 'bottom' element is returned.
	 * @param integer $nextCount The number of sub-elements to the current element.
	 * @param boolean $exp The element was expanded to render subelements if this flag is set.
	 * @return string Image tag with the plus/minus icon.
	 * @access private
	 * @see AbstarctTreeView::PMicon()
	 * @todo Define visibility
	 */
	public function PMicon($row, $a, $c, $nextCount, $exp) {
		$PM = 'join';
		$BTM = $a == $c ? 'bottom' : '';
		$icon = '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->backPath, ('gfx/ol/' . $PM . $BTM . '.gif'), 'width="18" height="16"') . ' alt="" />';
		return $icon;
	}

	/**
	 * Get stored tree structure AND updating it if needed according to incoming PM GET var.
	 * - Here we just set it to nothing since we want to just render the tree, nothing more.
	 *
	 * @return void
	 * @access private
	 * @todo Define visibility
	 */
	public function initializePositionSaving() {
		$this->stored = array();
	}

}


?>