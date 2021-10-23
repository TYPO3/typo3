<?php

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

namespace TYPO3\CMS\Backend\Tree;

/**
 * Sorted Tree Node Collection
 *
 * Note: This collection works only with integers as offset keys and not
 * with much datasets. You have been warned!
 */
class SortedTreeNodeCollection extends TreeNodeCollection
{
    /**
     * Checks if a specific node is inside the collection
     *
     * @param \TYPO3\CMS\Backend\Tree\TreeNode $node
     * @return bool
     */
    public function contains(TreeNode $node)
    {
        return $this->offsetOf($node) !== -1;
    }

    /**
     * Returns the offset key of given node
     *
     * @param \TYPO3\CMS\Backend\Tree\TreeNode $node
     * @return int
     */
    protected function offsetOf(TreeNode $node)
    {
        return $this->binarySearch($node, 0, $this->count() - 1);
    }

    /**
     * Binary search that returns the offset of a given node
     *
     * @param \TYPO3\CMS\Backend\Tree\TreeNode $node
     * @param int $start
     * @param int $end
     * @return int
     */
    protected function binarySearch(TreeNode $node, $start, $end)
    {
        if (!$start && $end - $start >= 2 || $end - $start > 2) {
            $divider = (int)ceil(($end - $start) / 2);
            if ($this->offsetGet($divider)->equals($node)) {
                return $divider;
            }
            if ($this->offsetGet($divider)->compareTo($node) > 0) {
                return $this->binarySearch($node, $start, $divider - 1);
            }
            return $this->binarySearch($node, $divider + 1, $end);
        }
        if ($this->offsetGet($start)->equals($node)) {
            return $start;
        }
        if ($this->offsetGet($end)->equals($node)) {
            return $end;
        }
        return -1;
    }

    /**
     * Normalizes the array by reordering the keys
     */
    protected function normalize()
    {
        $nodes = [];
        foreach ($this as $node) {
            $nodes[] = $node;
        }
        $this->exchangeArray($nodes);
    }

    /**
     * Adds a node to the internal list in a sorted approach
     *
     * @param \TYPO3\CMS\Backend\Tree\TreeNode $node
     * @todo: Set return type to void in v12 as breaking patch and drop #[\ReturnTypeWillChange]
     */
    #[\ReturnTypeWillChange]
    public function append($node)
    {
        parent::append($node);
        $this->asort();
        $this->normalize();
    }
}
