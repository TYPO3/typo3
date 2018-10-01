<?php
namespace TYPO3\CMS\Fluid\ViewHelpers;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Render the inner parts of a Widget.
 * This ViewHelper can only be used in a template which belongs to a Widget Controller.
 *
 * It renders everything inside the Widget ViewHelper, and you can pass additional
 * arguments.
 */
class RenderChildrenViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * As this ViewHelper might render HTML, the output must not be escaped
     *
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Initialize arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('arguments', 'array', 'Arguments to assign as template variables', false, []);
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return mixed
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $subRenderingContext = static::getWidgetRenderingContext($renderingContext);
        $widgetChildNodes = static::getWidgetChildNodes($renderingContext);
        static::addArgumentsToTemplateVariableContainer($subRenderingContext, $arguments['arguments']);
        $output = $widgetChildNodes->evaluate($subRenderingContext);
        static::removeArgumentsFromTemplateVariableContainer($subRenderingContext, $arguments['arguments']);
        return $output;
    }

    /**
     * Get the widget rendering context, or throw an exception if it cannot be found.
     *
     * @param RenderingContextInterface $renderingContext
     * @throws \TYPO3\CMS\Fluid\Core\Widget\Exception\RenderingContextNotFoundException
     * @return \TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface
     */
    protected static function getWidgetRenderingContext(RenderingContextInterface $renderingContext)
    {
        $subRenderingContext = static::getWidgetContext($renderingContext)->getViewHelperChildNodeRenderingContext();
        if (!$subRenderingContext instanceof \TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface) {
            throw new \TYPO3\CMS\Fluid\Core\Widget\Exception\RenderingContextNotFoundException('Rendering Context not found inside Widget. <f:renderChildren> has been used in an AJAX Request, but is only usable in non-ajax mode.', 1284986604);
        }
        return $subRenderingContext;
    }

    /**
     * @param RenderingContextInterface $renderingContext
     * @return \TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\RootNode
     */
    protected static function getWidgetChildNodes(RenderingContextInterface $renderingContext)
    {
        return static::getWidgetContext($renderingContext)->getViewHelperChildNodes();
    }

    /**
     * @param RenderingContextInterface $renderingContext
     * @throws \TYPO3\CMS\Fluid\Core\Widget\Exception\WidgetRequestNotFoundException
     * @return \TYPO3\CMS\Fluid\Core\Widget\WidgetContext
     */
    protected static function getWidgetContext(RenderingContextInterface $renderingContext)
    {
        $request = $renderingContext->getControllerContext()->getRequest();
        if (!$request instanceof \TYPO3\CMS\Fluid\Core\Widget\WidgetRequest) {
            throw new \TYPO3\CMS\Fluid\Core\Widget\Exception\WidgetRequestNotFoundException('The Request is not a WidgetRequest! <f:renderChildren> must be called inside a Widget Template.', 1284986120);
        }
        return $request->getWidgetContext();
    }

    /**
     * Add the given arguments to the TemplateVariableContainer of the widget.
     *
     * @param RenderingContextInterface $renderingContext
     * @param array $arguments
     */
    protected static function addArgumentsToTemplateVariableContainer(RenderingContextInterface $renderingContext, array $arguments)
    {
        $templateVariableContainer = $renderingContext->getVariableProvider();
        foreach ($arguments as $identifier => $value) {
            $templateVariableContainer->add($identifier, $value);
        }
    }

    /**
     * Remove the given arguments from the TemplateVariableContainer of the widget.
     *
     * @param RenderingContextInterface $renderingContext
     * @param array $arguments
     */
    protected static function removeArgumentsFromTemplateVariableContainer(RenderingContextInterface $renderingContext, array $arguments)
    {
        $templateVariableContainer = $renderingContext->getVariableProvider();
        foreach ($arguments as $identifier => $value) {
            $templateVariableContainer->remove($identifier);
        }
    }
}
