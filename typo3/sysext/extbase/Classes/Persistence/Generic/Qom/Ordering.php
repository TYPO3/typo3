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
 * Determines the relative order of two rows in the result set by evaluating operand for
 * each.
 */
class Ordering implements OrderingInterface
{
    /**
     * @var DynamicOperandInterface
     */
    protected $operand;

    /**
     * @var string One of \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_*
     */
    protected $order;

    /**
     * Constructs the Ordering instance
     *
     * @param DynamicOperandInterface $operand The operand; non-null
     * @param string $order One of \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_*
     */
    public function __construct(DynamicOperandInterface $operand, $order = \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING)
    {
        $this->operand = $operand;
        $this->order = $order;
    }

    /**
     * The operand by which to order.
     *
     * @return DynamicOperandInterface the operand; non-null
     */
    public function getOperand()
    {
        return $this->operand;
    }

    /**
     * Gets the order.
     *
     * @return string One of \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_*
     */
    public function getOrder()
    {
        return $this->order;
    }
}
