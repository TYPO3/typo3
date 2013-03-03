<?php
namespace TYPO3\CMS\Fluid\Core\Parser\SyntaxTree;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Array Syntax Tree Node. Handles JSON-like arrays.
 */
class ArrayNode extends \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode {

	/**
	 * An associative array. Each key is a string. Each value is either a literal, or an AbstractNode.
	 *
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
	 * @param \TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface $renderingContext
	 * @return array An associative array with literal values
	 */
	public function evaluate(\TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface $renderingContext) {
		$arrayToBuild = array();
		foreach ($this->internalArray as $key => $value) {
			if ($value instanceof \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode) {
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