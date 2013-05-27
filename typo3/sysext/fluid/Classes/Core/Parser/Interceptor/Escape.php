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
 * An interceptor adding the escape viewhelper to the suitable places.
 *
 */
class Tx_Fluid_Core_Parser_Interceptor_Escape implements Tx_Fluid_Core_Parser_InterceptorInterface {

	/**
	 * Is the interceptor enabled right now?
	 * @var boolean
	 */
	protected $interceptorEnabled = TRUE;

	/**
	 * A stack of ViewHelperNodes which currently disable the interceptor.
	 * Needed to enable the interceptor again.
	 *
	 * @var array<Tx_Fluid_Core_Parser_SyntaxTree_NodeInterface>
	 */
	protected $viewHelperNodesWhichDisableTheInterceptor = array();

	/**
	 * @var Tx_Extbase_Object_ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Inject object manager
	 *
	 * @param Tx_Extbase_Object_ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(Tx_Extbase_Object_ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Adds a ViewHelper node using the Format\HtmlspecialcharsViewHelper to the given node.
	 * If "escapingInterceptorEnabled" in the ViewHelper is FALSE, will disable itself inside the ViewHelpers body.
	 *
	 * @param Tx_Fluid_Core_Parser_SyntaxTree_NodeInterface $node
	 * @param integer $interceptorPosition One of the INTERCEPT_* constants for the current interception point
	 * @param Tx_Fluid_Core_Parser_ParsingState $parsingState the current parsing state. Not needed in this interceptor.
	 * @return Tx_Fluid_Core_Parser_SyntaxTree_NodeInterface
	 */
	public function process(Tx_Fluid_Core_Parser_SyntaxTree_NodeInterface $node, $interceptorPosition, Tx_Fluid_Core_Parser_ParsingState $parsingState) {
		if ($interceptorPosition === Tx_Fluid_Core_Parser_InterceptorInterface::INTERCEPT_OPENING_VIEWHELPER) {
			if (!$node->getUninitializedViewHelper()->isEscapingInterceptorEnabled()) {
				$this->interceptorEnabled = FALSE;
				$this->viewHelperNodesWhichDisableTheInterceptor[] = $node;
			}
		} elseif ($interceptorPosition === Tx_Fluid_Core_Parser_InterceptorInterface::INTERCEPT_CLOSING_VIEWHELPER) {
			if (end($this->viewHelperNodesWhichDisableTheInterceptor) === $node) {
				array_pop($this->viewHelperNodesWhichDisableTheInterceptor);
				if (count($this->viewHelperNodesWhichDisableTheInterceptor) === 0) {
					$this->interceptorEnabled = TRUE;
				}
			}
		} elseif ($this->interceptorEnabled && $node instanceof Tx_Fluid_Core_Parser_SyntaxTree_ObjectAccessorNode) {
			$escapeViewHelper = $this->objectManager->get('Tx_Fluid_ViewHelpers_Format_HtmlspecialcharsViewHelper');
			$node = $this->objectManager->create(
				'Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode',
				$escapeViewHelper,
				array('value' => $node)
			);
		}
		return $node;
	}

	/**
	 * This interceptor wants to hook into object accessor creation, and opening / closing ViewHelpers.
	 *
	 * @return array Array of INTERCEPT_* constants
	 */
	public function getInterceptionPoints() {
		return array(
			Tx_Fluid_Core_Parser_InterceptorInterface::INTERCEPT_OPENING_VIEWHELPER,
			Tx_Fluid_Core_Parser_InterceptorInterface::INTERCEPT_CLOSING_VIEWHELPER,
			Tx_Fluid_Core_Parser_InterceptorInterface::INTERCEPT_OBJECTACCESSOR
		);
	}
}
?>