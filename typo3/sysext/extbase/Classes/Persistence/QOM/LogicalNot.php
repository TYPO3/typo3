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
 * Performs a logical negation of another constraint.
 *
 * To satisfy the Not constraint, the node-tuple must not satisfy constraint.
 *
 * @package Extbase
 * @subpackage Persistence\QOM
 * @version $Id: LogicalNot.php 1729 2009-11-25 21:37:20Z stucki $
 * @scope prototype
 */
class Tx_Extbase_Persistence_QOM_LogicalNot implements Tx_Extbase_Persistence_QOM_NotInterface {

	/**
	 * @var Tx_Extbase_Persistence_QOM_ConstraintInterface
	 */
	protected $constraint;

	/**
	 *
	 * @param Tx_Extbase_Persistence_QOM_ConstraintInterface $constraint
	 */
	public function __construct(Tx_Extbase_Persistence_QOM_ConstraintInterface $constraint) {
		$this->constraint = $constraint;
	}

	/**
	 * Fills an array with the names of all bound variables in the constraint
	 *
	 * @param array &$boundVariables
	 * @return void
	 */
	public function collectBoundVariableNames(&$boundVariables) {
		$this->constraint->collectBoundVariableNames($boundVariables);
	}

	/**
	 * Gets the constraint negated by this Not constraint.
	 *
	 * @return Tx_Extbase_Persistence_QOM_ConstraintInterface the constraint; non-null
	 */
	public function getConstraint() {
		return $this->constraint;
	}

}
?>