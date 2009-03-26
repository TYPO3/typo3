<?php

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package Fluid
 * @subpackage Core
 * @version $Id: ArrayNode.php 1962 2009-03-03 12:10:41Z k-fish $
 */

/**
 * Array Syntax Tree Node. Handles JSON-like arrays.
 *
 * @package Fluid
 * @subpackage Core
 * @version $Id: ArrayNode.php 1962 2009-03-03 12:10:41Z k-fish $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class Tx_Fluid_Core_SyntaxTree_ArrayNode extends Tx_Fluid_Core_SyntaxTree_AbstractNode {

	/**
	 * An associative array. Each key is a string. Each value is either a literal, or an AbstractNode.
	 * @var array
	 */
	protected $internalArray = array();

	/**
	 * Constructor.
	 *
	 * @param array $internalArray Array to store
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function __construct($internalArray) {
		$this->internalArray = $internalArray;
	}

	/**
	 * Evaluate the array and return an evaluated array
	 *
	 * @param Tx_Fluid_VariableContainer $variableContainer Variable Container for the scope variables
	 * @return array An associative array with literal values
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function evaluate(Tx_Fluid_Core_VariableContainer $variableContainer) {
		$arrayToBuild = array();
		foreach ($this->internalArray as $key => $value) {
			if ($value instanceof Tx_Fluid_Core_SyntaxTree_AbstractNode) {
				$arrayToBuild[$key] = $value->evaluate($variableContainer);
			} else {
				$arrayToBuild[$key] = $value;
			}
		}
		return $arrayToBuild;
	}
}

?>