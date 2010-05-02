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
 * Evaluates to the value of a bind variable.
 *
 * @package Extbase
 * @subpackage Persistence\QOM
 * @version $Id: BindVariableValue.php 1729 2009-11-25 21:37:20Z stucki $
 * @scope prototype
 */
class Tx_Extbase_Persistence_QOM_BindVariableValue extends Tx_Extbase_Persistence_QOM_StaticOperand implements Tx_Extbase_Persistence_QOM_BindVariableValueInterface {

	/**
	 * @var string
	 */
	protected $variableName;

	/**
	 * Constructs this BindVariableValue instance
	 *
	 * @param string $variableName
	 */
	public function __construct($variableName) {
		$this->variableName = $variableName;
	}

	/**
	 * Fills an array with the names of all bound variables in the operand
	 *
	 * @param array &$boundVariables
	 * @return void
	 */
	public function collectBoundVariableNames(&$boundVariables) {
		$boundVariables[$this->variableName] = NULL;
	}


	/**
	 * Gets the name of the bind variable.
	 *
	 * @return string the bind variable name; non-null
	 */
	public function getBindVariableName() {
		return $this->variableName;
	}

}

?>