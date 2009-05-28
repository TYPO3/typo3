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
 * @package Fluid
 * @subpackage Core
 * @version $Id: RootNode.php 2279 2009-05-19 21:16:46Z k-fish $
 */

/**
 * Root node of every syntax tree.
 *
 * @package Fluid
 * @subpackage Core
 * @version $Id: RootNode.php 2279 2009-05-19 21:16:46Z k-fish $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 * @internal
 */
class Tx_Fluid_Core_Parser_SyntaxTree_RootNode extends Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode {

	/**
	 * Evaluate the root node, by evaluating the subtree.
	 *
	 * @return object Evaluated subtree
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @internal
	 */
	public function evaluate() {
		if ($this->renderingContext === NULL) {
			throw new Tx_Fluid_Core_RuntimeException('Rendering Context is null in RootNode, but necessary. If this error appears, please report a bug!', 1242669004);
		}
		$text = $this->evaluateChildNodes();
		return $text;
	}
}

?>