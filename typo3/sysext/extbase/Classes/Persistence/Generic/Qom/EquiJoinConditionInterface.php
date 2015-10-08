<?php
namespace TYPO3\CMS\Extbase\Persistence\Generic\Qom;

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
 * Tests whether the childSelector node is a child of the parentSelector node. A
 * node-tuple satisfies the constraint only if:
 * childSelectorNode.getParent().isSame(parentSelectorNode)
 * would return true, where childSelectorNode is the node for childSelector and
 * parentSelectorNode is the node for parentSelector.
 */
interface EquiJoinConditionInterface extends JoinConditionInterface
{
    /**
     * Gets the name of the child selector.
     *
     * @return string the selector name; non-null
     */
    public function getChildSelectorName();

    /**
     * Gets the name of the parent selector.
     *
     * @return string the selector name; non-null
     */
    public function getParentSelectorName();
}
