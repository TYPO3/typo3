<?php
namespace TYPO3\CMS\Fluid\Core\Parser\Interceptor;

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
 * An interceptor adding the escape viewhelper to the suitable places.
 */
class Escape implements \TYPO3\CMS\Fluid\Core\Parser\InterceptorInterface
{
    /**
     * Is the interceptor enabled right now?
     *
     * @var bool
     */
    protected $interceptorEnabled = true;

    /**
     * A stack of ViewHelperNodes which currently disable the interceptor.
     * Needed to enable the interceptor again.
     *
     * @var array<\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\NodeInterface>
     */
    protected $viewHelperNodesWhichDisableTheInterceptor = [];

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     * @inject
     */
    protected $objectManager;

    /**
     * Adds a ViewHelper node using the Format\HtmlspecialcharsViewHelper to the given node.
     * If "escapingInterceptorEnabled" in the ViewHelper is FALSE, will disable itself inside the ViewHelpers body.
     *
     * @param \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\NodeInterface $node
     * @param int $interceptorPosition One of the INTERCEPT_* constants for the current interception point
     * @param \TYPO3\CMS\Fluid\Core\Parser\ParsingState $parsingState the current parsing state. Not needed in this interceptor.
     * @return \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\NodeInterface
     */
    public function process(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\NodeInterface $node, $interceptorPosition, \TYPO3\CMS\Fluid\Core\Parser\ParsingState $parsingState)
    {
        if ($interceptorPosition === \TYPO3\CMS\Fluid\Core\Parser\InterceptorInterface::INTERCEPT_OPENING_VIEWHELPER) {
            if (!$node->getUninitializedViewHelper()->isEscapingInterceptorEnabled()) {
                $this->interceptorEnabled = false;
                $this->viewHelperNodesWhichDisableTheInterceptor[] = $node;
            }
        } elseif ($interceptorPosition === \TYPO3\CMS\Fluid\Core\Parser\InterceptorInterface::INTERCEPT_CLOSING_VIEWHELPER) {
            if (end($this->viewHelperNodesWhichDisableTheInterceptor) === $node) {
                array_pop($this->viewHelperNodesWhichDisableTheInterceptor);
                if (count($this->viewHelperNodesWhichDisableTheInterceptor) === 0) {
                    $this->interceptorEnabled = true;
                }
            }
        } elseif ($this->interceptorEnabled && $node instanceof \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode) {
            $escapeViewHelper = $this->objectManager->get(\TYPO3\CMS\Fluid\ViewHelpers\Format\HtmlspecialcharsViewHelper::class);
            $node = $this->objectManager->get(
                \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode::class,
                $escapeViewHelper,
                ['value' => $node]
            );
        }
        return $node;
    }

    /**
     * This interceptor wants to hook into object accessor creation, and opening / closing ViewHelpers.
     *
     * @return array Array of INTERCEPT_* constants
     */
    public function getInterceptionPoints()
    {
        return [
            \TYPO3\CMS\Fluid\Core\Parser\InterceptorInterface::INTERCEPT_OPENING_VIEWHELPER,
            \TYPO3\CMS\Fluid\Core\Parser\InterceptorInterface::INTERCEPT_CLOSING_VIEWHELPER,
            \TYPO3\CMS\Fluid\Core\Parser\InterceptorInterface::INTERCEPT_OBJECTACCESSOR
        ];
    }
}
