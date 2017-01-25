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

use TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3\CMS\Fluid\ViewHelpers\ElseViewHelper;
use TYPO3\CMS\Fluid\ViewHelpers\ThenViewHelper;

/**
 * This view helper is an abstract ViewHelper which implements an if/else condition.
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
abstract class AbstractConditionViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper implements \TYPO3\CMS\Fluid\Core\ViewHelper\Facets\ChildNodeAccessInterface
{
    /**
     * An array containing child nodes
     *
     * @var array<\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode>
     */
    private $childNodes = [];

    /**
     * Setter for ChildNodes - as defined in ChildNodeAccessInterface
     *
     * @param array $childNodes Child nodes of this syntax tree node
     * @return void
     */
    public function setChildNodes(array $childNodes)
    {
        $this->childNodes = $childNodes;
    }

    /**
     * Initializes the "then" and "else" arguments
     */
    public function __construct()
    {
        $this->registerArgument('then', 'mixed', 'Value to be returned if the condition if met.', false);
        $this->registerArgument('else', 'mixed', 'Value to be returned if the condition if not met.', false);
    }

    /**
     * renders <f:then> child if $condition is true, otherwise renders <f:else> child.
     *
     * @return string the rendered string
     * @api
     */
    public function render()
    {
        if (static::evaluateCondition($this->arguments)) {
            return $this->renderThenChild();
        } else {
            return $this->renderElseChild();
        }
    }

    /**
     * Returns value of "then" attribute.
     * If then attribute is not set, iterates through child nodes and renders ThenViewHelper.
     * If then attribute is not set and no ThenViewHelper and no ElseViewHelper is found, all child nodes are rendered
     *
     * @return string rendered ThenViewHelper or contents of <f:if> if no ThenViewHelper was found
     * @api
     */
    protected function renderThenChild()
    {
        $hasEvaluated = true;
        $result = static::renderStaticThenChild($this->arguments, $hasEvaluated);
        if ($hasEvaluated) {
            return $result;
        }

        $elseViewHelperEncountered = false;
        foreach ($this->childNodes as $childNode) {
            if ($childNode instanceof ViewHelperNode
                && $childNode->getViewHelperClassName() === ThenViewHelper::class) {
                $data = $childNode->evaluate($this->renderingContext);
                return $data;
            }
            if ($childNode instanceof ViewHelperNode
                && $childNode->getViewHelperClassName() === ElseViewHelper::class) {
                $elseViewHelperEncountered = true;
            }
        }

        if ($elseViewHelperEncountered) {
            return '';
        } else {
            return $this->renderChildren();
        }
    }

    /**
     * Statically evalute "then" children.
     * The "$hasEvaluated" argument is there to distinguish the case that "then" returned NULL or was not evaluated.
     *
     * @param array $arguments ViewHelper arguments
     * @param bool $hasEvaluated Can be used to check if the "then" child was actually evaluated by this method.
     * @return string
     */
    protected static function renderStaticThenChild($arguments, &$hasEvaluated)
    {
        if (isset($arguments['then'])) {
            return $arguments['then'];
        }
        if (isset($arguments['__thenClosure'])) {
            $thenClosure = $arguments['__thenClosure'];
            return $thenClosure();
        } elseif (isset($arguments['__elseClosure'])) {
            return '';
        }

        $hasEvaluated = false;
    }

    /**
     * Returns value of "else" attribute.
     * If else attribute is not set, iterates through child nodes and renders ElseViewHelper.
     * If else attribute is not set and no ElseViewHelper is found, an empty string will be returned.
     *
     * @return string rendered ElseViewHelper or an empty string if no ThenViewHelper was found
     * @api
     */
    protected function renderElseChild()
    {
        $hasEvaluated = true;
        $result = static::renderStaticElseChild($this->arguments, $hasEvaluated);
        if ($hasEvaluated) {
            return $result;
        }

        foreach ($this->childNodes as $childNode) {
            if ($childNode instanceof ViewHelperNode
                && $childNode->getViewHelperClassName() === ElseViewHelper::class) {
                return $childNode->evaluate($this->renderingContext);
            }
        }

        return '';
    }

    /**
     * Statically evalute "else" children.
     * The "$hasEvaluated" argument is there to distinguish the case that "else" returned NULL or was not evaluated.
     *
     * @param array $arguments ViewHelper arguments
     * @param bool $hasEvaluated Can be used to check if the "else" child was actually evaluated by this method.
     * @return string
     */
    protected static function renderStaticElseChild($arguments, &$hasEvaluated)
    {
        if (isset($arguments['else'])) {
            return $arguments['else'];
        }
        if (isset($arguments['__elseClosure'])) {
            $elseClosure = $arguments['__elseClosure'];
            return $elseClosure();
        }

        $hasEvaluated = false;
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
    public function compile($argumentsVariableName, $renderChildrenClosureVariableName, &$initializationPhpCode, \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode $syntaxTreeNode, \TYPO3\CMS\Fluid\Core\Compiler\TemplateCompiler $templateCompiler)
    {
        foreach ($syntaxTreeNode->getChildNodes() as $childNode) {
            if ($childNode instanceof ViewHelperNode
                && $childNode->getViewHelperClassName() === ThenViewHelper::class) {
                $childNodesAsClosure = $templateCompiler->wrapChildNodesInClosure($childNode);
                $initializationPhpCode .= sprintf('%s[\'__thenClosure\'] = %s;', $argumentsVariableName, $childNodesAsClosure) . LF;
            }
            if ($childNode instanceof ViewHelperNode
                && $childNode->getViewHelperClassName() === ElseViewHelper::class) {
                $childNodesAsClosure = $templateCompiler->wrapChildNodesInClosure($childNode);
                $initializationPhpCode .= sprintf('%s[\'__elseClosure\'] = %s;', $argumentsVariableName, $childNodesAsClosure) . LF;
            }
        }

        return sprintf('%s::renderStatic(%s, %s, $renderingContext)',
            get_class($this), $argumentsVariableName, $renderChildrenClosureVariableName);
    }

    /**
     * Default implementation for CompilableInterface. See CompilableInterface
     * for a detailed description of this method.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param \TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface $renderingContext
     * @return mixed
     * @see \TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, \TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface $renderingContext)
    {
        $hasEvaluated = true;
        if (static::evaluateCondition($arguments)) {
            $result = static::renderStaticThenChild($arguments, $hasEvaluated);
            if ($hasEvaluated) {
                return $result;
            }

            return $renderChildrenClosure();
        } else {
            $result = static::renderStaticElseChild($arguments, $hasEvaluated);
            if ($hasEvaluated) {
                return $result;
            }
        }

        return '';
    }

    /**
     * This method decides if the condition is TRUE or FALSE. It can be overriden in extending viewhelpers to adjust functionality.
     *
     * @param array $arguments ViewHelper arguments to evaluate the condition for this ViewHelper, allows for flexiblity in overriding this method.
     * @return bool
     */
    protected static function evaluateCondition($arguments = null)
    {
        return isset($arguments['condition']) && $arguments['condition'];
    }
}
