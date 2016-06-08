<?php
namespace TYPO3\CMS\Backend\ContextMenu\Pagetree\Extdirect;

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
use TYPO3\CMS\Backend\ContextMenu\ContextMenuActionCollection;
use TYPO3\CMS\Backend\ContextMenu\Pagetree\ContextMenuDataProvider;
use TYPO3\CMS\Backend\Tree\Pagetree\Commands;
use TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode;
use TYPO3\CMS\Backend\Tree\TreeNode;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Context Menu of the Page Tree
 */
class ContextMenuConfiguration
{
    /**
     * Data Provider
     *
     * @var ContextMenuDataProvider
     */
    protected $dataProvider = null;

    /**
     * @param ContextMenuDataProvider $dataProvider
     * @return void
     */
    public function setDataProvider(ContextMenuDataProvider $dataProvider)
    {
        $this->dataProvider = $dataProvider;
    }

    /**
     * @return ContextMenuDataProvider
     */
    public function getDataProvider()
    {
        return $this->dataProvider;
    }
    /**
     * Sets the data provider
     *
     * @return void
     */
    protected function initDataProvider()
    {
        $dataProvider = GeneralUtility::makeInstance(ContextMenuDataProvider::class);
        $this->setDataProvider($dataProvider);
    }

    /**
     * Returns the actions for the given node information's
     *
     * @param \stdClass $nodeData
     * @return array
     */
    public function getActionsForNodeArray($nodeData)
    {
        $node = GeneralUtility::makeInstance(PagetreeNode::class, (array)$nodeData);
        $node->setRecord(Commands::getNodeRecord($node->getId()));
        $this->initDataProvider();
        $this->dataProvider->setContextMenuType('table.' . $node->getType());
        $actionCollection = $this->dataProvider->getActionsForNode($node);
        $actions = array();
        if ($actionCollection instanceof ContextMenuActionCollection) {
            $actions = $actionCollection->toArray();
        }
        return $actions;
    }

    /**
     * Unused for this implementation
     *
     * @see getActionsForNodeArray()
     * @param TreeNode $node
     * @return array
     */
    public function getActionsForNode(TreeNode $node)
    {
    }
}
