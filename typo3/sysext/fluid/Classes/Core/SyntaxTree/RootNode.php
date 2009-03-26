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
 * @version $Id: RootNode.php 1962 2009-03-03 12:10:41Z k-fish $
 */

/**
 * Root node of every syntax tree.
 *
 * @package Fluid
 * @subpackage Core
 * @version $Id: RootNode.php 1962 2009-03-03 12:10:41Z k-fish $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class Tx_Fluid_Core_SyntaxTree_RootNode extends Tx_Fluid_Core_SyntaxTree_AbstractNode {

	/**
	 * Evaluate the root node, by evaluating the subtree.
	 *
	 * @param Tx_Fluid_Core_VariableContainer $variableContainer Variable Container to be used
	 * @return object Evaluated subtree
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function evaluate(Tx_Fluid_Core_VariableContainer $variableContainer) {
		$this->variableContainer = $variableContainer;
		$text = $this->evaluateChildNodes();
		return $text;
	}
}

?>