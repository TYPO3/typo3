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

/**
 * Context Menu of the Page Tree
 */
class ContextMenuConfiguration extends \TYPO3\CMS\Backend\ContextMenu\Extdirect\AbstractExtdirectContextMenu
{
    /**
     * Sets the data provider
     *
     * @return void
     */
    protected function initDataProvider()
    {
        /** @var $dataProvider \TYPO3\CMS\Backend\ContextMenu\Pagetree\ContextMenuDataProvider */
        $dataProvider = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Backend\ContextMenu\Pagetree\ContextMenuDataProvider::class);
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
        /** @var $node \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
        $node = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode::class, (array)$nodeData);
        $node->setRecord(\TYPO3\CMS\Backend\Tree\Pagetree\Commands::getNodeRecord($node->getId()));
        $this->initDataProvider();
        $this->dataProvider->setContextMenuType('table.' . $node->getType());
        $actionCollection = $this->dataProvider->getActionsForNode($node);
        $actions = [];
        if ($actionCollection instanceof \TYPO3\CMS\Backend\ContextMenu\ContextMenuActionCollection) {
            $actions = $actionCollection->toArray();
        }
        return $actions;
    }
}
