<?php
namespace TYPO3\CMS\Backend\ContextMenu\Extdirect;

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
 * Abstract Context Menu for ExtDirect
 *
 * This is a concrete implementation that should stay here to be shared
 * between the different ExtDirect implementation. Just create a subclass
 * for adding specific purposes.
 */
abstract class AbstractExtdirectContextMenu extends \TYPO3\CMS\Backend\ContextMenu\AbstractContextMenu
{
    /**
     * Returns the actions for the given node information
     *
     * Note: This method should be overriden to fit your specific needs.
     *
     * The informations should contain the basic informations of a
     * \TYPO3\CMS\Backend\Tree\TreeNode for further processing. Also the classname
     * (property type) of the node should be given, because we need this information
     * to create the node.
     *
     * @param \stdClass $nodeData
     * @return array
     */
    public function getActionsForNodeArray($nodeData)
    {
        if ($this->dataProvider === null) {
            $dataProvider = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Backend\ContextMenu\AbstractContextMenuDataProvider::class);
            $this->setDataProvider($dataProvider);
        }
        /** @var $node \TYPO3\CMS\Backend\Tree\TreeNode */
        $node = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\TreeNode::class, (array)$nodeData);
        $actions = $this->dataProvider->getActionsForNode($node);
        return $actions;
    }

    /**
     * Unused for this implementation
     *
     * @see getActionsForNodeArray()
     * @param \TYPO3\CMS\Backend\Tree\TreeNode $node
     * @return array
     */
    public function getActionsForNode(\TYPO3\CMS\Backend\Tree\TreeNode $node)
    {
    }
}
