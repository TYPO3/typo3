<?php

/*                                                                        *
 * This script is backported from the FLOW3 package "TYPO3.Fluid".        *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */


/**
 * Array Syntax Tree Node. Handles JSON-like arrays.
 *
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
	 */
	public function __construct($internalArray) {
		$this->internalArray = $internalArray;
	}

	/**
	 * Evaluate the array and return an evaluated array
	 *
	 * @param Tx_Fluid_Core_Rendering_RenderingContextInterface $renderingContext
	 * @return array An associative array with literal values
	 */
	public function evaluate(Tx_Fluid_Core_Rendering_RenderingContextInterface $renderingContext) {
		$arrayToBuild = array();
		foreach ($this->internalArray as $key => $value) {
			if ($value instanceof Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode) {
				$arrayToBuild[$key] = $value->evaluate($renderingContext);
			} else {
				// TODO - this case should not happen!
				$arrayToBuild[$key] = $value;
			}
		}
		return $arrayToBuild;
	}

	/**
	 * INTERNAL; DO NOT CALL DIRECTLY!
	 *
	 * @return array
	 */
	public function getInternalArray() {
		return $this->internalArray;
	}
}

?>