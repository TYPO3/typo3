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
 * Abstract node in the syntax tree which has been built.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
abstract class Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode implements Tx_Fluid_Core_Parser_SyntaxTree_NodeInterface {

	/**
	 * List of Child Nodes.
	 * @var array<Tx_Fluid_Core_Parser_SyntaxTree_NodeInterface>
	 */
	protected $childNodes = array();

	/**
	 * Evaluate all child nodes and return the evaluated results.
	 *
	 * @param Tx_Fluid_Core_Rendering_RenderingContextInterface $renderingContext
	 * @return mixed Normally, an object is returned - in case it is concatenated with a string, a string is returned.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function evaluateChildNodes(Tx_Fluid_Core_Rendering_RenderingContextInterface $renderingContext) {
		$output = NULL;
		foreach ($this->childNodes as $subNode) {
			if ($output === NULL) {
				$output = $subNode->evaluate($renderingContext);
			} else {
				if (is_object($output)) {
					if (!method_exists($output, '__toString')) {
						throw new Tx_Fluid_Core_Parser_Exception('Cannot cast object of type "' . get_class($output) . '" to string.', 1248356140);
					}
					$output = $output->__toString();
				} else {
					$output = (string)$output;
				}
				$subNodeOutput = $subNode->evaluate($renderingContext);

				if (is_object($subNodeOutput)) {
					if (!method_exists($subNodeOutput, '__toString')) {
						throw new Tx_Fluid_Core_Parser_Exception('Cannot cast object of type "' . get_class($subNodeOutput) . '" to string.', 1273753083);
					}
					$output .= $subNodeOutput->__toString();
				} else {
					$output .= (string)$subNodeOutput;
				}
			}
		}
		return $output;
	}

	/**
	 * Returns all child nodes for a given node.
	 * This is especially needed to implement the boolean expression language.
	 *
	 * @return array<Tx_Fluid_Core_Parser_SyntaxTree_NodeInterface> A list of nodes
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getChildNodes() {
		return $this->childNodes;
	}

	/**
	 * Appends a subnode to this node. Is used inside the parser to append children
	 *
	 * @param Tx_Fluid_Core_Parser_SyntaxTree_NodeInterface $childNode The subnode to add
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function addChildNode(Tx_Fluid_Core_Parser_SyntaxTree_NodeInterface $childNode) {
		$this->childNodes[] = $childNode;
	}

}

?>