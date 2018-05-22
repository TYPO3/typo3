<?php
namespace TYPO3\CMS\Core\Tree\TableConfiguration;

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

use TYPO3\CMS\Backend\Tree\TreeNodeCollection;

/**
 * Renders a tca tree array for the SelectElementTree
 */
class ArrayTreeRenderer extends \TYPO3\CMS\Backend\Tree\Renderer\AbstractTreeRenderer
{
    /**
     * recursion level
     *
     * @var int
     */
    protected $recursionLevel = 0;

    /**
     * Renders a node recursive or just a single instance
     *
     * @param \TYPO3\CMS\Backend\Tree\TreeRepresentationNode $node
     * @param bool $recursive
     * @return array
     */
    public function renderNode(\TYPO3\CMS\Backend\Tree\TreeRepresentationNode $node, $recursive = true)
    {
        $nodeArray = [];
        $nodeArray[] = $this->getNodeArray($node);
        if ($recursive && $node->hasChildNodes()) {
            $this->recursionLevel++;
            $children = $this->renderNodeCollection($node->getChildNodes());
            foreach ($children as $child) {
                $nodeArray[] = $child;
            }
            $this->recursionLevel--;
        }
        return $nodeArray;
    }

    /**
     * Get node array
     *
     * @param \TYPO3\CMS\Backend\Tree\TreeRepresentationNode|DatabaseTreeNode $node
     * @return array
     */
    protected function getNodeArray(\TYPO3\CMS\Backend\Tree\TreeRepresentationNode $node)
    {
        $overlayIconName = '';
        if (is_object($node->getIcon())) {
            $iconName = $node->getIcon()->getIdentifier();
            if (is_object($node->getIcon()->getOverlayIcon())) {
                $overlayIconName = $node->getIcon()->getOverlayIcon()->getIdentifier();
            }
        } else {
            $iconName = $node->getIcon();
        }
        $nodeArray = [
            'identifier' => htmlspecialchars($node->getId()),
            // No need for htmlspecialchars() here as d3 is using 'textContent' property of the HTML DOM node
            'name' => $node->getLabel(),
            'icon' => $iconName,
            'overlayIcon' => $overlayIconName,
            'depth' => $this->recursionLevel,
            'hasChildren' => (bool)$node->hasChildNodes(),
            'selectable' => true,
        ];
        if ($node instanceof DatabaseTreeNode) {
            $nodeArray['checked'] = (bool)$node->getSelected();
            if (!$node->getSelectable()) {
                $nodeArray['checked'] = false;
                $nodeArray['selectable'] = false;
            }
        }
        return $nodeArray;
    }

    /**
     * Renders a node collection recursive or just a single instance
     *
     * @param \TYPO3\CMS\Backend\Tree\AbstractTree $tree
     * @param bool $recursive
     * @return array
     */
    public function renderTree(\TYPO3\CMS\Backend\Tree\AbstractTree $tree, $recursive = true)
    {
        $this->recursionLevel = 0;
        return $this->renderNode($tree->getRoot(), $recursive);
    }

    /**
     * Renders an tree recursive or just a single instance
     *
     * @param TreeNodeCollection $collection
     * @param bool $recursive
     * @return array
     */
    public function renderNodeCollection(TreeNodeCollection $collection, $recursive = true)
    {
        $treeItems = [];
        foreach ($collection as $node) {
            $allNodes = $this->renderNode($node, $recursive);
            if ($allNodes[0]) {
                $treeItems[] = $allNodes[0];
            }
            $nodeCount = count($allNodes);
            if ($nodeCount > 1) {
                for ($i = 1; $i < $nodeCount; $i++) {
                    $treeItems[] = $allNodes[$i];
                }
            }
        }
        return $treeItems;
    }
}
