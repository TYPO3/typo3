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
 * Stores all information relevant for one parsing pass - that is, the root node,
 * and the current stack of open nodes (nodeStack) and a variable container used
 * for PostParseFacets.
 *
 */
class Tx_Fluid_Core_Parser_ParsingState implements Tx_Fluid_Core_Parser_ParsedTemplateInterface {

	/**
	 * Root node reference
	 * @var Tx_Fluid_Core_Parser_SyntaxTree_RootNode
	 */
	protected $rootNode;

	/**
	 * Array of node references currently open.
	 * @var array
	 */
	protected $nodeStack = array();

	/**
	 * Variable container where ViewHelpers implementing the PostParseFacet can
	 * store things in.
	 * @var Tx_Fluid_Core_ViewHelper_TemplateVariableContainer
	 */
	protected $variableContainer;

	/**
	 * The layout name of the current template or NULL if the template does not contain a layout definition
	 *
	 * @var Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode
	 */
	protected $layoutNameNode;

	/**
	 * @var boolean
	 */
	protected $compilable = TRUE;

	/**
	 * Injects a variable container. ViewHelpers implementing the PostParse
	 * Facet can store information inside this variableContainer.
	 *
	 * @param Tx_Fluid_Core_ViewHelper_TemplateVariableContainer $variableContainer
	 * @return void
	 */
	public function injectVariableContainer(Tx_Fluid_Core_ViewHelper_TemplateVariableContainer $variableContainer) {
		$this->variableContainer = $variableContainer;
	}

	/**
	 * Set root node of this parsing state
	 *
	 * @param Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode $rootNode
	 * @return void
	 */
	public function setRootNode(Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode $rootNode) {
		$this->rootNode = $rootNode;
	}

	/**
	 * Get root node of this parsing state.
	 *
	 * @return Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode The root node
	 */
	public function getRootNode() {
		return $this->rootNode;
	}

	/**
	 * Render the parsed template with rendering context
	 *
	 * @param Tx_Fluid_Core_Rendering_RenderingContextInterface $renderingContext The rendering context to use
	 * @return Rendered string
	 */
	public function render(Tx_Fluid_Core_Rendering_RenderingContextInterface $renderingContext) {
		return $this->rootNode->evaluate($renderingContext);
	}

	/**
	 * Push a node to the node stack. The node stack holds all currently open
	 * templating tags.
	 *
	 * @param Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode $node Node to push to node stack
	 * @return void
	 */
	public function pushNodeToStack(Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode $node) {
		array_push($this->nodeStack, $node);
	}

	/**
	 * Get the top stack element, without removing it.
	 *
	 * @return Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode the top stack element.
	 */
	public function getNodeFromStack() {
		return $this->nodeStack[count($this->nodeStack)-1];
	}

	/**
	 * Pop the top stack element (=remove it) and return it back.
	 *
	 * @return Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode the top stack element, which was removed.
	 */
	public function popNodeFromStack() {
		return array_pop($this->nodeStack);
	}

	/**
	 * Count the size of the node stack
	 *
	 * @return integer Number of elements on the node stack (i.e. number of currently open Fluid tags)
	 */
	public function countNodeStack() {
		return count($this->nodeStack);
	}

	/**
	 * Returns a variable container which will be then passed to the postParseFacet.
	 *
	 * @return Tx_Fluid_Core_ViewHelper_TemplateVariableContainer The variable container or NULL if none has been set yet
	 * @todo Rename to getPostParseVariableContainer
	 */
	public function getVariableContainer() {
		return $this->variableContainer;
	}

	/**
	 * @param Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode $layoutNameNode name of the layout that is defined in this template via <f:layout name="..." />
	 * @return void
	 */
	public function setLayoutNameNode(Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode $layoutNameNode) {
		$this->layoutNameNode = $layoutNameNode;
	}

	/**
	 * @return Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode
	 */
	public function getLayoutNameNode() {
		return $this->layoutNameNode;
	}

	/**
	 * Returns TRUE if the current template has a template defined via <f:layout name="..." />
	 * @see getLayoutName()
	 *
	 * @return boolean
	 */
	public function hasLayout() {
		return $this->layoutNameNode !== NULL;
	}

	/**
	 * Returns the name of the layout that is defined within the current template via <f:layout name="..." />
	 * If no layout is defined, this returns NULL
	 * This requires the current rendering context in order to be able to evaluate the layout name
	 *
	 * @param Tx_Fluid_Core_Rendering_RenderingContextInterface $renderingContext
	 * @return string
	 */
	public function getLayoutName(Tx_Fluid_Core_Rendering_RenderingContextInterface $renderingContext) {
		if (!$this->hasLayout()) {
			return NULL;
		}
		$layoutName = $this->layoutNameNode->evaluate($renderingContext);
		if (!empty($layoutName)) {
			return $layoutName;
		}
		throw new Tx_Fluid_View_Exception('The layoutName could not be evaluated to a string', 1296805368);
	}

	/**
	 * @return boolean
	 */
	public function isCompilable() {
		return $this->compilable;
	}

	/**
	 * @param boolean $compilable
	 */
	public function setCompilable($compilable) {
		$this->compilable = $compilable;
	}

	/**
	 * @return boolean
	 */
	public function isCompiled() {
		return FALSE;
	}
}
?>