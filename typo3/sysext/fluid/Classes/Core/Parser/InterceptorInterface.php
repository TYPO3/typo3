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
 * An interceptor interface. Interceptors are used in the parsing stage to change
 * the syntax tree of a template, e.g. by adding viewhelper nodes.
 *
 */
interface Tx_Fluid_Core_Parser_InterceptorInterface {

	const INTERCEPT_OPENING_VIEWHELPER = 1;
	const INTERCEPT_CLOSING_VIEWHELPER = 2;
	const INTERCEPT_TEXT = 3;
	const INTERCEPT_OBJECTACCESSOR = 4;

	/**
	 * The interceptor can process the given node at will and must return a node
	 * that will be used in place of the given node.
	 *
	 * @param Tx_Fluid_Core_Parser_SyntaxTree_NodeInterface $node
	 * @param integer $interceptorPosition One of the INTERCEPT_* constants for the current interception point
	 * @param Tx_Fluid_Core_Parser_ParsingState $parsingState the parsing state
	 * @return Tx_Fluid_Core_Parser_SyntaxTree_NodeInterface
	 */
	public function process(Tx_Fluid_Core_Parser_SyntaxTree_NodeInterface $node, $interceptorPosition, Tx_Fluid_Core_Parser_ParsingState $parsingState);

	/**
	 * The interceptor should define at which interception positions it wants to be called.
	 *
	 * @return array Array of INTERCEPT_* constants
	 */
	public function getInterceptionPoints();
}
?>