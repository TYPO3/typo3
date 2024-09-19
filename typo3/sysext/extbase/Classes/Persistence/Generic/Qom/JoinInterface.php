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

namespace TYPO3\CMS\Extbase\Persistence\Generic\Qom;

/**
 * Performs a join between two node-tuple sources.
 *
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
interface JoinInterface
{
    public function getLeft(): SourceInterface&SelectorInterface;

    public function getRight(): SourceInterface&SelectorInterface;

    /**
     * @return string one of QueryObjectModelConstants.JCR_JOIN_TYPE_*
     */
    public function getJoinType(): string;

    public function getJoinCondition(): JoinConditionInterface;
}
