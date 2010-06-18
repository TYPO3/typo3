<?php

/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Array Syntax Tree Node. Handles JSON-like arrays.
 *
 * @version $Id: ArrayNode.php 1734 2009-11-25 21:53:57Z stucki $
 * @package Fluid
 * @subpackage Core\Parser\SyntaxTree
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class Tx_Fluid_Core_Parser_SyntaxTree_ArrayNode extends Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode {

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
	 * @return array An associative array with literal values
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function evaluate() {
		if ($this->renderingContext === NULL) {
			throw new Tx_Fluid_Core_Parser_Exception('Rendering Context is null in ArrayNode, but necessary. If this error appears, please report a bug!', 1242668976);
		}
		$arrayToBuild = array();
		foreach ($this->internalArray as $key => $value) {
			if ($value instanceof Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode) {
				$value->setRenderingContext($this->renderingContext);
				$arrayToBuild[$key] = $value->evaluate();
			} else {
				// TODO - this case should not happen!
				$arrayToBuild[$key] = $value;
			}
		}
		return $arrayToBuild;
	}
}

?>