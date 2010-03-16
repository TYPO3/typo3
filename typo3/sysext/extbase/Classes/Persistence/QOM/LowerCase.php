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
 * Evaluates to the lower-case string value (or values, if multi-valued) of
 * operand.
 *
 * If operand does not evaluate to a string value, its value is first converted
 * to a string.
 *
 * If operand evaluates to null, the LowerCase operand also evaluates to null.
 *
 * @package Extbase
 * @subpackage Persistence\QOM
 * @version $Id: LowerCase.php 1729 2009-11-25 21:37:20Z stucki $
 * @scope prototype
 */
class Tx_Extbase_Persistence_QOM_LowerCase implements Tx_Extbase_Persistence_QOM_LowerCaseInterface {

	/**
	 * @var Tx_Extbase_Persistence_QOM_DynamicOperandInterface
	 */
	protected $operand;

	/**
	 * Constructs this LowerCase instance
	 *
	 * @param Tx_Extbase_Persistence_QOM_DynamicOperandInterface $constraint
	 */
	public function __construct(Tx_Extbase_Persistence_QOM_DynamicOperandInterface $operand) {
		$this->operand = $operand;
	}

	/**
	 * Gets the operand whose value is converted to a lower-case string.
	 *
	 * @return Tx_Extbase_Persistence_QOM_DynamicOperandInterface the operand; non-null
	 */
	public function getOperand() {
		return $this->operand;
	}

}
?>