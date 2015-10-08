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
 * Interface that defines the comparison of nodes
 */
interface ComparableNodeInterface
{
    /**
     * Compare Node against another one
     *
     * Returns:
     * 1 if the current node is greater than the $other,
     * -1 if $other is greater than the current node and
     * 0 if the nodes are equal
     *
     * <strong>Example</strong>
     * <pre>
     * if ($this->sortValue > $other->sortValue) {
     * return 1;
     * } elseif ($this->sortValue < $other->sortValue) {
     * return -1;
     * } else {
     * return 0;
     * }
     * </pre>
     *
     * @param \TYPO3\CMS\Backend\Tree\TreeNode $other
     * @return int see description
     */
    public function compareTo($other);
}
