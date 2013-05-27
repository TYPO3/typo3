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
 * Root node of every syntax tree.
 *
 */
class Tx_Fluid_Core_Parser_SyntaxTree_RootNode extends Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode {

	/**
	 * Evaluate the root node, by evaluating the subtree.
	 *
	 * @param Tx_Fluid_Core_Rendering_RenderingContextInterface $renderingContext
	 * @return mixed Evaluated subtree
	 */
	public function evaluate(Tx_Fluid_Core_Rendering_RenderingContextInterface $renderingContext) {
		return $this->evaluateChildNodes($renderingContext);
	}
}

?>