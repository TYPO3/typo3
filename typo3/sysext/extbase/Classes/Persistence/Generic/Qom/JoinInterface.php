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
 * Performs a join between two node-tuple sources.
 */
interface JoinInterface extends SourceInterface
{
    /**
     * Gets the left node-tuple source.
     *
     * @return SelectorInterface the left source; non-null
     */
    public function getLeft();

    /**
     * Gets the right node-tuple source.
     *
     * @return SelectorInterface the right source; non-null
     */
    public function getRight();

    /**
     * Gets the join type.
     *
     * @return string one of QueryObjectModelConstants.JCR_JOIN_TYPE_*
     */
    public function getJoinType();

    /**
     * Gets the join condition.
     *
     * @return JoinConditionInterface the join condition; non-null
     */
    public function getJoinCondition();
}
