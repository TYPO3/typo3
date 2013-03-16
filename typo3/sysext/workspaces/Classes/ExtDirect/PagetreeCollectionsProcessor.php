<?php
namespace TYPO3\CMS\Workspaces\ExtDirect;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Tolleiv Nietsch <typo3@tolleiv.de>
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
 * Interface for classes which perform pre or post processing
 *
 * @author Tolleiv Nietsch <typo3@tolleiv.de>
 */
class PagetreeCollectionsProcessor implements \TYPO3\CMS\Backend\Tree\Pagetree\CollectionProcessorInterface {

	/**
	 * @abstract
	 * @param \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode $node
	 * @param int $mountPoint
	 * @param int $level
	 * @param \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNodeCollection $nodeCollection
	 * @return void
	 */
	public function postProcessGetNodes($node, $mountPoint, $level, $nodeCollection) {
		foreach ($nodeCollection as $node) {
			/** @var $node \TYPO3\CMS\Backend\Tree\TreeNode */
			$this->highlightVersionizedElements($node);
		}
	}

	/**
	 * @abstract
	 * @param \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode $node
	 * @param string $searchFilter
	 * @param int $mountPoint
	 * @param \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNodeCollection $nodeCollection
	 * @return void
	 */
	public function postProcessFilteredNodes($node, $searchFilter, $mountPoint, $nodeCollection) {
		foreach ($nodeCollection as $node) {
			/** @var $node \TYPO3\CMS\Backend\Tree\TreeNode */
			$this->highlightVersionizedElements($node);
		}
	}

	/**
	 * @abstract
	 * @param string $searchFilter
	 * @param \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNodeCollection $nodeCollection
	 * @return void
	 */
	public function postProcessGetTreeMounts($searchFilter, $nodeCollection) {
		foreach ($nodeCollection as $node) {
			/** @var $node \TYPO3\CMS\Backend\Tree\TreeNode */
			$this->highlightVersionizedElements($node);
		}
	}

	/**
	 * Sets the CSS Class on all pages which have versioned records
	 * in the current workspace
	 *
	 * @param \TYPO3\CMS\Backend\Tree\TreeNode $node
	 * @return void
	 */
	protected function highlightVersionizedElements(\TYPO3\CMS\Backend\Tree\TreeNode $node) {
		if (!$node->getCls() && count(\TYPO3\CMS\Backend\Utility\BackendUtility::countVersionsOfRecordsOnPage($GLOBALS['BE_USER']->workspace, $node->getId(), TRUE))) {
			$node->setCls('ver-versions');
		}
	}

}


?>