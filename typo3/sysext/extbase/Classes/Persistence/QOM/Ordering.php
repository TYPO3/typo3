<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
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
 *
 * @package Extbase
 * @subpackage Persistence\QOM
 * @version $Id: Ordering.php 1729 2009-11-25 21:37:20Z stucki $
 */
class Tx_Extbase_Persistence_QOM_Ordering implements Tx_Extbase_Persistence_QOM_OrderingInterface {

	/**
	 * @var Tx_Extbase_Persistence_QOM_DynamicOperandInterface
	 */
	protected $operand;

	/**
	 * @var string One of Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_ORDER_*
	 */
	protected $order;

	/**
	 * Constructs the Ordering instance
	 *
	 * @param Tx_Extbase_Persistence_QOM_DynamicOperandInterface $operand The operand; non-null
	 * @param string either Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_ORDER_ASCENDING or Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_ORDER_DESCENDING
	 */
	public function __construct(Tx_Extbase_Persistence_QOM_DynamicOperandInterface $operand, $order = Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_ORDER_ASCENDING) {
		$this->operand = $operand;
		$this->order = $order;
	}

	/**
	 * The operand by which to order.
	 *
	 * @return Tx_Extbase_Persistence_QOM_DynamicOperandInterface the operand; non-null
	 */
	public function getOperand() {
		return $this->operand;
	}

	/**
	 * Gets the order.
	 *
	 * @return string either Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_ORDER_ASCENDING or Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_ORDER_DESCENDING
	 */
	public function getOrder() {
		return $this->order;
	}

}

?>