<?php
namespace TYPO3\CMS\Workspaces\ExtDirect;

/*
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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Workspaces\Service\WorkspaceService;

/**
 * Interface for classes which perform pre or post processing
 */
class PagetreeCollectionsProcessor implements \TYPO3\CMS\Backend\Tree\Pagetree\CollectionProcessorInterface
{
    /**
     * @var WorkspaceService
     */
    protected $workspaceService = null;

    /**
     * @abstract
     * @param \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode $node
     * @param int $mountPoint
     * @param int $level
     * @param \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNodeCollection $nodeCollection
     * @return void
     */
    public function postProcessGetNodes($node, $mountPoint, $level, $nodeCollection)
    {
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
    public function postProcessFilteredNodes($node, $searchFilter, $mountPoint, $nodeCollection)
    {
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
    public function postProcessGetTreeMounts($searchFilter, $nodeCollection)
    {
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
    protected function highlightVersionizedElements(\TYPO3\CMS\Backend\Tree\TreeNode $node)
    {
        if (!$node->getCls() && $this->getWorkspaceService()->hasPageRecordVersions($GLOBALS['BE_USER']->workspace, $node->getId())) {
            $node->setCls('ver-versions');
        }
    }

    /**
     * @return WorkspaceService
     */
    protected function getWorkspaceService()
    {
        if ($this->workspaceService === null) {
            $this->workspaceService = GeneralUtility::makeInstance(WorkspaceService::class);
        }

        return $this->workspaceService;
    }
}
