<?php
namespace TYPO3\CMS\Backend\Tree;

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
 * Describes necessary methods if the nodes are draggable and dropable
 * within the tree.
 */
interface DraggableAndDropableNodeInterface
{
    /**
     * Moves given node inside a destination node
     *
     * @param \TYPO3\CMS\Backend\Tree\TreeNode $node
     * @param \TYPO3\CMS\Backend\Tree\TreeNode $destination
     */
    public function moveNodeInDestinationNode($node, $destination);

    /**
     * Moves given node after a destination node
     *
     * @param \TYPO3\CMS\Backend\Tree\TreeNode $node
     * @param \TYPO3\CMS\Backend\Tree\TreeNode $destination
     */
    public function moveNodeAfterDestinationNode($node, $destination);

    /**
     * Copies given node inside a destination node
     *
     * @param \TYPO3\CMS\Backend\Tree\TreeNode $node
     * @param \TYPO3\CMS\Backend\Tree\TreeNode $destination
     */
    public function copyNodeInDestinationNode($node, $destination);

    /**
     * Copies given node after a destination node
     *
     * @param \TYPO3\CMS\Backend\Tree\TreeNode $node
     * @param \TYPO3\CMS\Backend\Tree\TreeNode $destination
     */
    public function copyNodeAfterDestinationNode($node, $destination);
}
