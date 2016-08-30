<?php
namespace TYPO3\CMS\Fluid\Core\Parser;

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
 * Stores all information relevant for one parsing pass - that is, the root node,
 * and the current stack of open nodes (nodeStack) and a variable container used
 * for PostParseFacets.
 */
class ParsingState implements \TYPO3\CMS\Fluid\Core\Parser\ParsedTemplateInterface
{
    /**
     * Root node reference
     *
     * @var \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\RootNode
     */
    protected $rootNode;

    /**
     * Array of node references currently open.
     *
     * @var array
     */
    protected $nodeStack = [];

    /**
     * Variable container where ViewHelpers implementing the PostParseFacet can
     * store things in.
     *
     * @var \TYPO3\CMS\Fluid\Core\ViewHelper\TemplateVariableContainer
     * @inject
     */
    protected $variableContainer;

    /**
     * The layout name of the current template or NULL if the template does not contain a layout definition
     *
     * @var \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode
     */
    protected $layoutNameNode;

    /**
     * @var bool
     */
    protected $compilable = true;

    /**
     * Set root node of this parsing state
     *
     * @param \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode $rootNode
     * @return void
     */
    public function setRootNode(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode $rootNode)
    {
        $this->rootNode = $rootNode;
    }

    /**
     * Get root node of this parsing state.
     *
     * @return \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode The root node
     */
    public function getRootNode()
    {
        return $this->rootNode;
    }

    /**
     * Render the parsed template with rendering context
     *
     * @param \TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface $renderingContext The rendering context to use
     * @return string Rendered string
     */
    public function render(\TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface $renderingContext)
    {
        return $this->rootNode->evaluate($renderingContext);
    }

    /**
     * Push a node to the node stack. The node stack holds all currently open
     * templating tags.
     *
     * @param \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode $node Node to push to node stack
     * @return void
     */
    public function pushNodeToStack(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode $node)
    {
        array_push($this->nodeStack, $node);
    }

    /**
     * Get the top stack element, without removing it.
     *
     * @return \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode the top stack element.
     */
    public function getNodeFromStack()
    {
        return $this->nodeStack[count($this->nodeStack) - 1];
    }

    /**
     * Pop the top stack element (=remove it) and return it back.
     *
     * @return \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode the top stack element, which was removed.
     */
    public function popNodeFromStack()
    {
        return array_pop($this->nodeStack);
    }

    /**
     * Count the size of the node stack
     *
     * @return int Number of elements on the node stack (i.e. number of currently open Fluid tags)
     */
    public function countNodeStack()
    {
        return count($this->nodeStack);
    }

    /**
     * Returns a variable container which will be then passed to the postParseFacet.
     *
     * @return \TYPO3\CMS\Fluid\Core\ViewHelper\TemplateVariableContainer The variable container or NULL if none has been set yet
     * @todo Rename to getPostParseVariableContainer
     */
    public function getVariableContainer()
    {
        return $this->variableContainer;
    }

    /**
     * @param \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode $layoutNameNode name of the layout that is defined in this template via <f:layout name="..." />
     * @return void
     */
    public function setLayoutNameNode(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode $layoutNameNode)
    {
        $this->layoutNameNode = $layoutNameNode;
    }

    /**
     * @return \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode
     */
    public function getLayoutNameNode()
    {
        return $this->layoutNameNode;
    }

    /**
     * Returns TRUE if the current template has a template defined via <f:layout name="..." />
     *
     * @see getLayoutName()
     * @return bool
     */
    public function hasLayout()
    {
        return $this->layoutNameNode !== null;
    }

    /**
     * Returns the name of the layout that is defined within the current template via <f:layout name="..." />
     * If no layout is defined, this returns NULL
     * This requires the current rendering context in order to be able to evaluate the layout name
     *
     * @param \TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface $renderingContext
     * @return string
     * @throws \TYPO3\CMS\Fluid\View\Exception
     */
    public function getLayoutName(\TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface $renderingContext)
    {
        if (!$this->hasLayout()) {
            return null;
        }
        $layoutName = $this->layoutNameNode->evaluate($renderingContext);
        if (!empty($layoutName)) {
            return $layoutName;
        }
        throw new \TYPO3\CMS\Fluid\View\Exception('The layoutName could not be evaluated to a string', 1296805368);
    }

    /**
     * @return bool
     */
    public function isCompilable()
    {
        return $this->compilable;
    }

    /**
     * @param bool $compilable
     */
    public function setCompilable($compilable)
    {
        $this->compilable = $compilable;
    }

    /**
     * @return bool
     */
    public function isCompiled()
    {
        return false;
    }
}
