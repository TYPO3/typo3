<?php
namespace TYPO3\CMS\Extbase\Persistence\Generic\Qom;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Determines the relative order of two rows in the result set by evaluating operand for
 * each.
 */
class Ordering implements OrderingInterface {

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
	public function __construct(DynamicOperandInterface $operand, $order = \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING) {
		$this->operand = $operand;
		$this->order = $order;
	}

	/**
	 * The operand by which to order.
	 *
	 * @return DynamicOperandInterface the operand; non-null
	 */
	public function getOperand() {
		return $this->operand;
	}

	/**
	 * Gets the order.
	 *
	 * @return string One of \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_*
	 */
	public function getOrder() {
		return $this->order;
	}
}
