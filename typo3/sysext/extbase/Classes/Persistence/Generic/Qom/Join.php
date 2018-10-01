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
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class Join implements JoinInterface
{
    /**
     * @var SourceInterface
     */
    protected $left;

    /**
     * @var SourceInterface
     */
    protected $right;

    /**
     * @var int
     */
    protected $joinType;

    /**
     * @var JoinConditionInterface
     */
    protected $joinCondition;

    /**
     * Constructs the Join instance
     *
     * @param SourceInterface $left the left node-tuple source; non-null
     * @param SourceInterface $right the right node-tuple source; non-null
     * @param string $joinType One of Query::JCR_JOIN_TYPE_*
     * @param JoinConditionInterface $joinCondition
     */
    public function __construct(SourceInterface $left, SourceInterface $right, $joinType, JoinConditionInterface $joinCondition)
    {
        $this->left = $left;
        $this->right = $right;
        $this->joinType = $joinType;
        $this->joinCondition = $joinCondition;
    }

    /**
     * Gets the left node-tuple source.
     *
     * @return SourceInterface the left source; non-null
     */
    public function getLeft()
    {
        return $this->left;
    }

    /**
     * Gets the right node-tuple source.
     *
     * @return SourceInterface the right source; non-null
     */
    public function getRight()
    {
        return $this->right;
    }

    /**
     * Gets the join type.
     *
     * @return string one of QueryObjectModelConstants.JCR_JOIN_TYPE_*
     */
    public function getJoinType()
    {
        return $this->joinType;
    }

    /**
     * Gets the join condition.
     *
     * @return JoinConditionInterface the join condition; non-null
     */
    public function getJoinCondition()
    {
        return $this->joinCondition;
    }
}
