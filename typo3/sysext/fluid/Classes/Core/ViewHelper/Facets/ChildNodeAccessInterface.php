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
 * Child Node Access Facet. View Helpers should implement this interface if they
 * need access to the direct children in the Syntax Tree at rendering-time.
 * This might happen if you only want to selectively render a part of the syntax
 * tree depending on some conditions.
 * To render subnodes, you can fetch the RenderingContext via $this->renderingContext.
 *
 * In most cases, you will not need this facet, and it is NO PUBLIC API!
 * Right now it is only used internally for conditions, so by subclassing Tx_Fluid_Core_ViewHelpers_AbstractConditionViewHelper, this should be all you need.
 *
 * See Tx_Fluid_ViewHelpers_IfViewHelper for an example how it is used.
 *
 */
interface Tx_Fluid_Core_ViewHelper_Facets_ChildNodeAccessInterface {
	/**
	 * Sets the direct child nodes of the current syntax tree node.
	 *
	 * @param array<Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode> $childNodes
	 * @return void
	 */
	public function setChildNodes(array $childNodes);
}

?>