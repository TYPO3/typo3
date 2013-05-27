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
 * Node in the syntax tree.
 */
interface NodeInterface {

	/**
	 * Evaluate all child nodes and return the evaluated results.
	 *
	 * @param \TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface $renderingContext
	 * @return mixed Normally, an object is returned - in case it is concatenated with a string, a string is returned.
	 */
	public function evaluateChildNodes(\TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface $renderingContext);

	/**
	 * Returns all child nodes for a given node.
	 *
	 * @return array<\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\NodeInterface> A list of nodes
	 */
	public function getChildNodes();

	/**
	 * Appends a subnode to this node. Is used inside the parser to append children
	 *
	 * @param \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\NodeInterface $childNode The subnode to add
	 * @return void
	 */
	public function addChildNode(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\NodeInterface $childNode);

	/**
	 * Evaluates the node - can return not only strings, but arbitary objects.
	 *
	 * @param \TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface $renderingContext
	 * @return mixed Evaluated node
	 */
	public function evaluate(\TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface $renderingContext);
}

?>