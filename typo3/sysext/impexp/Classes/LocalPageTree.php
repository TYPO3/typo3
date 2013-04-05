<?php
namespace TYPO3\CMS\Impexp;

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
 * Extension of the page tree class. Used to get the tree of pages to export.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class LocalPageTree extends \TYPO3\CMS\Backend\Tree\View\BrowseTreeView {

	/**
	 * Initialization
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Wrapping title from page tree.
	 *
	 * @param string $title Title to wrap
	 * @param mixed $v (See parent class)
	 * @return string Wrapped title
	 * @todo Define visibility
	 */
	public function wrapTitle($title, $v) {
		$title = !strcmp(trim($title), '') ? '<em>[' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.no_title', 1) . ']</em>' : htmlspecialchars($title);
		return $title;
	}

	/**
	 * Wrapping Plus/Minus icon
	 *
	 * @param string $icon Icon HTML
	 * @param mixed $cmd (See parent class)
	 * @param mixed $bMark (See parent class)
	 * @return string Icon HTML
	 * @todo Define visibility
	 */
	public function PM_ATagWrap($icon, $cmd, $bMark = '') {
		return $icon;
	}

	/**
	 * Wrapping Icon
	 *
	 * @param string $icon Icon HTML
	 * @param array $row Record row (page)
	 * @return string Icon HTML
	 * @todo Define visibility
	 */
	public function wrapIcon($icon, $row) {
		return $icon;
	}

	/**
	 * Select permissions
	 *
	 * @return string SQL where clause
	 * @todo Define visibility
	 */
	public function permsC() {
		return $this->BE_USER->getPagePermsClause(1);
	}

	/**
	 * Tree rendering
	 *
	 * @param integer $pid PID value
	 * @param string $clause Additional where clause
	 * @return array Array of tree elements
	 * @todo Define visibility
	 */
	public function ext_tree($pid, $clause = '') {
		// Initialize:
		$this->init(' AND ' . $this->permsC() . $clause);
		// Get stored tree structure:
		$this->stored = unserialize($this->BE_USER->uc['browseTrees']['browsePages']);
		// PM action:
		$PM = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode('_', \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('PM'));
		// traverse mounts:
		$titleLen = intval($this->BE_USER->uc['titleLen']);
		$treeArr = array();
		$idx = 0;
		// Set first:
		$this->bank = $idx;
		$isOpen = $this->stored[$idx][$pid] || $this->expandFirst;
		// save ids
		$curIds = $this->ids;
		$this->reset();
		$this->ids = $curIds;
		// Set PM icon:
		$cmd = $this->bank . '_' . ($isOpen ? '0_' : '1_') . $pid;
		$icon = '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->backPath, ('gfx/ol/' . ($isOpen ? 'minus' : 'plus') . 'only.gif'), 'width="18" height="16"') . ' align="top" alt="" />';
		$firstHtml = $this->PM_ATagWrap($icon, $cmd);
		if ($pid > 0) {
			$rootRec = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL('pages', $pid);
			$firstHtml .= $this->wrapIcon(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('pages', $rootRec), $rootRec);
		} else {
			$rootRec = array(
				'title' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],
				'uid' => 0
			);
			$firstHtml .= $this->wrapIcon('<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->backPath, 'gfx/i/_icon_website.gif', 'width="18" height="16"') . ' align="top" alt="" />', $rootRec);
		}
		$this->tree[] = array('HTML' => $firstHtml, 'row' => $rootRec);
		if ($isOpen) {
			// Set depth:
			$depthD = '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->backPath, 'gfx/ol/blank.gif', 'width="18" height="16"') . ' align="top" alt="" />';
			if ($this->addSelfId) {
				$this->ids[] = $pid;
			}
			$this->getTree($pid, 999, $depthD);
			$idH = array();
			$idH[$pid]['uid'] = $pid;
			if (count($this->buffer_idH)) {
				$idH[$pid]['subrow'] = $this->buffer_idH;
			}
			$this->buffer_idH = $idH;
		}
		// Add tree:
		$treeArr = array_merge($treeArr, $this->tree);
		return $treeArr;
	}

}

?>