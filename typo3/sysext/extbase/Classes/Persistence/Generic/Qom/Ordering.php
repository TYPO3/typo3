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

use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * Determines the relative order of two rows in the result set by evaluating operand for
 * each.
 *
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
final readonly class Ordering implements OrderingInterface
{
    /**
     * @param string $order One of QueryInterface::ORDER_*
     */
    public function __construct(
        protected DynamicOperandInterface $operand,
        protected string $order = QueryInterface::ORDER_ASCENDING
    ) {}

    public function getOperand(): DynamicOperandInterface
    {
        return $this->operand;
    }

    /**
     * @return string One of QueryInterface::ORDER_*
     */
    public function getOrder(): string
    {
        return $this->order;
    }
}
