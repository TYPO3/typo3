<?php
namespace TYPO3\CMS\Fluid\Core\ViewHelper;

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
 * This view helper is an abstract ViewHelper which implements an if/else condition.
 * @see TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode::convertArgumentValue() to find see how boolean arguments are evaluated
 *
 * = Usage =
 *
 * To create a custom Condition ViewHelper, you need to subclass this class, and
 * implement your own render() method. Inside there, you should call $this->renderThenChild()
 * if the condition evaluated to TRUE, and $this->renderElseChild() if the condition evaluated
 * to FALSE.
 *
 * Every Condition ViewHelper has a "then" and "else" argument, so it can be used like:
 * <[aConditionViewHelperName] .... then="condition true" else="condition false" />,
 * or as well use the "then" and "else" child nodes.
 *
 * @see TYPO3\CMS\Fluid\ViewHelpers\IfViewHelper for a more detailed explanation and a simple usage example.
 * Make sure to NOT OVERRIDE the constructor.
 *
 * @api
 */
abstract class AbstractConditionViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper implements \TYPO3\CMS\Fluid\Core\ViewHelper\Facets\ChildNodeAccessInterface, \TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface {

	/**
	 * An array containing child nodes
	 *
	 * @var array<\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode>
	 */
	private $childNodes = array();

	/**
	 * Setter for ChildNodes - as defined in ChildNodeAccessInterface
	 *
	 * @param array $childNodes Child nodes of this syntax tree node
	 * @return void
	 */
	public function setChildNodes(array $childNodes) {
		$this->childNodes = $childNodes;
	}

	/**
	 * Initializes the "then" and "else" arguments
	 */
	public function __construct() {
		$this->registerArgument('then', 'mixed', 'Value to be returned if the condition if met.', FALSE);
		$this->registerArgument('else', 'mixed', 'Value to be returned if the condition if not met.', FALSE);
	}

	/**
	 * Returns value of "then" attribute.
	 * If then attribute is not set, iterates through child nodes and renders ThenViewHelper.
	 * If then attribute is not set and no ThenViewHelper and no ElseViewHelper is found, all child nodes are rendered
	 *
	 * @return string rendered ThenViewHelper or contents of <f:if> if no ThenViewHelper was found
	 * @api
	 */
	protected function renderThenChild() {
		if ($this->hasArgument('then')) {
			return $this->arguments['then'];
		}
		if ($this->hasArgument('__thenClosure')) {
			$thenClosure = $this->arguments['__thenClosure'];
			return $thenClosure();
		} elseif ($this->hasArgument('__elseClosure') || $this->hasArgument('else')) {
			return '';
		}

		$elseViewHelperEncountered = FALSE;
		foreach ($this->childNodes as $childNode) {
			if ($childNode instanceof \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode
				&& $childNode->getViewHelperClassName() === 'TYPO3\\CMS\\Fluid\\ViewHelpers\\ThenViewHelper') {
				$data = $childNode->evaluate($this->renderingContext);
				return $data;
			}
			if ($childNode instanceof \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode
				&& $childNode->getViewHelperClassName() === 'TYPO3\\CMS\\Fluid\\ViewHelpers\\ElseViewHelper') {
				$elseViewHelperEncountered = TRUE;
			}
		}

		if ($elseViewHelperEncountered) {
			return '';
		} else {
			return $this->renderChildren();
		}
	}

	/**
	 * Returns value of "else" attribute.
	 * If else attribute is not set, iterates through child nodes and renders ElseViewHelper.
	 * If else attribute is not set and no ElseViewHelper is found, an empty string will be returned.
	 *
	 * @return string rendered ElseViewHelper or an empty string if no ThenViewHelper was found
	 * @api
	 */
	protected function renderElseChild() {
		if ($this->hasArgument('else')) {
			return $this->arguments['else'];
		}
		if ($this->hasArgument('__elseClosure')) {
			$elseClosure = $this->arguments['__elseClosure'];
			return $elseClosure();
		}
		foreach ($this->childNodes as $childNode) {
			if ($childNode instanceof \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode
				&& $childNode->getViewHelperClassName() === 'TYPO3\\CMS\\Fluid\\ViewHelpers\\ElseViewHelper') {
				return $childNode->evaluate($this->renderingContext);
			}
		}

		return '';
	}

	/**
	 * The compiled ViewHelper adds two new ViewHelper arguments: __thenClosure and __elseClosure.
	 * These contain closures which are be executed to render the then(), respectively else() case.
	 *
	 * @param string $argumentsVariableName
	 * @param string $renderChildrenClosureVariableName
	 * @param string $initializationPhpCode
	 * @param \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode $syntaxTreeNode
	 * @param \TYPO3\CMS\Fluid\Core\Compiler\TemplateCompiler $templateCompiler
	 * @return string
	 * @internal
	 */
	public function compile($argumentsVariableName, $renderChildrenClosureVariableName, &$initializationPhpCode, \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode $syntaxTreeNode, \TYPO3\CMS\Fluid\Core\Compiler\TemplateCompiler $templateCompiler) {
		foreach ($syntaxTreeNode->getChildNodes() as $childNode) {
			if ($childNode instanceof \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode
				&& $childNode->getViewHelperClassName() === 'TYPO3\\CMS\\Fluid\\ViewHelpers\\ThenViewHelper') {

				$childNodesAsClosure = $templateCompiler->wrapChildNodesInClosure($childNode);
				$initializationPhpCode .= sprintf('%s[\'__thenClosure\'] = %s;', $argumentsVariableName, $childNodesAsClosure) . chr(10);
			}
			if ($childNode instanceof \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode
				&& $childNode->getViewHelperClassName() === 'TYPO3\\CMS\\Fluid\\ViewHelpers\\ElseViewHelper') {

				$childNodesAsClosure = $templateCompiler->wrapChildNodesInClosure($childNode);
				$initializationPhpCode .= sprintf('%s[\'__elseClosure\'] = %s;', $argumentsVariableName, $childNodesAsClosure) . chr(10);
			}
		}
		return \TYPO3\CMS\Fluid\Core\Compiler\TemplateCompiler::SHOULD_GENERATE_VIEWHELPER_INVOCATION;
	}
}

?>