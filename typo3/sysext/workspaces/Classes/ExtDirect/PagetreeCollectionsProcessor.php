<?php
namespace TYPO3\CMS\Workspaces\ExtDirect;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
/**
 * Interface for classes which perform pre or post processing
 *
 * @author Tolleiv Nietsch <typo3@tolleiv.de>
 */
class PagetreeCollectionsProcessor implements \TYPO3\CMS\Backend\Tree\Pagetree\CollectionProcessorInterface {

	/**
	 * @abstract
	 * @param \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode $node
	 * @param integer $mountPoint
	 * @param integer $level
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
	 * @param integer $mountPoint
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
