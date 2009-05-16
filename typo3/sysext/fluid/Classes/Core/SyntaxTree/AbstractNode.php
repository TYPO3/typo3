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
 * @version $Id: AbstractNode.php 2213 2009-05-15 11:19:13Z bwaidelich $
 */

/**
 * Abstract node in the syntax tree which has been built.
 *
 * @package Fluid
 * @subpackage Core
 * @version $Id: AbstractNode.php 2213 2009-05-15 11:19:13Z bwaidelich $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
abstract class Tx_Fluid_Core_SyntaxTree_AbstractNode {

	/**
	 * List of Child Nodes.
	 * @var array Tx_Fluid_Core_SyntaxTree_AbstractNode
	 */
	protected $childNodes = array();

	/**
	 * The variable container
	 * @var Tx_Fluid_Core_VariableContainer
	 */
	protected $variableContainer;

	/**
	 * @param Tx_Fluid_Core_VariableContainer Variable Container to be used for the evaluation
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @internal
	 */
	public function setVariableContainer(Tx_Fluid_Core_VariableContainer $variableContainer) {
		$this->variableContainer = $variableContainer;
	}

	/**
	 * Evaluate all child nodes and return the evaluated results.
	 *
	 * @return object Normally, an object is returned - in case it is concatenated with a string, a string is returned.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function evaluateChildNodes() {
		$output = NULL;
		foreach ($this->childNodes as $subNode) {
			$subNode->setVariableContainer($this->variableContainer);
			if ($output === NULL) {
				$output = $subNode->evaluate();
			} else {
				$output = (string)$output;
				$output .= $subNode->render();
			}
		}
		return $output;
	}

	/**
	 * Appends a subnode to this node. Is used inside the parser to append children
	 *
	 * @param Tx_Fluid_Core_SyntaxTree_AbstractNode $subnode The subnode to add
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function addChildNode(Tx_Fluid_Core_SyntaxTree_AbstractNode $subNode) {
		$this->childNodes[] = $subNode;
	}

	/**
	 * Renders the node.
	 *
	 * @return string Rendered node as string
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function render() {
		return (string)$this->evaluate();
	}

	/**
	 * Evaluates the node - can return not only strings, but arbitary objects.
	 *
	 * @return object Evaluated node
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	abstract public function evaluate();
}

?>