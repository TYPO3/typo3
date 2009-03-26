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
 * @version $Id: ChildNodeAccessInterface.php 1962 2009-03-03 12:10:41Z k-fish $
 */

/**
 * Child Node Access Facet. View Helpers should implement this interface Tx_Fluid_Core_Facets_if they need access to the direct children in the Syntax Tree at rendering-time.
 * This might happen if you only want to selectively render a part of the syntax tree depending on some conditions.
 *
 * In most cases, you will not need this view helper.
 *
 * See Tx_Fluid_ViewHelpers_IfViewHelper for an example how it is used.
 *
 * @package Fluid
 * @subpackage Core
 * @version $Id: ChildNodeAccessInterface.php 1962 2009-03-03 12:10:41Z k-fish $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface Tx_Fluid_Core_Facets_ChildNodeAccessInterface {

	/**
	 * Sets the direct child nodes of the current syntax tree node.
	 *
	 * @param array Tx_Fluid_Core_SyntaxTree_AbstractNode $childNodes
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setChildNodes(array $childNodes);

}

?>