<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Extbase\Persistence\Generic\Qom;

/**
 * Performs a join between two node-tuple sources.
 *
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
final readonly class Join implements SourceInterface, JoinInterface
{
    /**
     * @param string $joinType One of Query::JCR_JOIN_TYPE_*
     */
    public function __construct(
        private SourceInterface&SelectorInterface $left,
        private SourceInterface&SelectorInterface $right,
        private string $joinType,
        private JoinConditionInterface $joinCondition
    ) {}

    public function getLeft(): SourceInterface&SelectorInterface
    {
        return $this->left;
    }

    public function getRight(): SourceInterface&SelectorInterface
    {
        return $this->right;
    }

    /**
     * @return string one of QueryObjectModelConstants.JCR_JOIN_TYPE_*
     */
    public function getJoinType(): string
    {
        return $this->joinType;
    }

    public function getJoinCondition(): JoinConditionInterface
    {
        return $this->joinCondition;
    }
}
