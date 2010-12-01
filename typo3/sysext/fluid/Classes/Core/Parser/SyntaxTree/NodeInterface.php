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
 * Node in the syntax tree.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
interface Tx_Fluid_Core_Parser_SyntaxTree_NodeInterface {

	/**
	 * Evaluate all child nodes and return the evaluated results.
	 *
	 * @param Tx_Fluid_Core_Rendering_RenderingContextInterface $renderingContext
	 * @return mixed Normally, an object is returned - in case it is concatenated with a string, a string is returned.
	 */
	public function evaluateChildNodes(Tx_Fluid_Core_Rendering_RenderingContextInterface $renderingContext);

	/**
	 * Returns all child nodes for a given node.
	 *
	 * @return array<Tx_Fluid_Core_Parser_SyntaxTree_NodeInterface> A list of nodes
	 */
	public function getChildNodes();

	/**
	 * Appends a subnode to this node. Is used inside the parser to append children
	 *
	 * @param Tx_Fluid_Core_Parser_SyntaxTree_NodeInterface $childNode The subnode to add
	 * @return void
	 */
	public function addChildNode(Tx_Fluid_Core_Parser_SyntaxTree_NodeInterface $childNode);

	/**
	 * Evaluates the node - can return not only strings, but arbitary objects.
	 *
	 * @param Tx_Fluid_Core_Rendering_RenderingContextInterface $renderingContext
	 * @return mixed Evaluated node
	 */
	public function evaluate(Tx_Fluid_Core_Rendering_RenderingContextInterface $renderingContext);
}

?>