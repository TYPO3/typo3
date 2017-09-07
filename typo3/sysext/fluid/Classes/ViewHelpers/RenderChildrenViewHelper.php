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

/**
 * Render the inner parts of a Widget.
 * This ViewHelper can only be used in a template which belongs to a Widget Controller.
 *
 * It renders everything inside the Widget ViewHelper, and you can pass additional
 * arguments.
 *
 * @api
 */
class RenderChildrenViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{
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
        parent::initializeArguments();
        $this->registerArgument('arguments', 'array', 'Arguments to assign as template variables', false, []);
    }

    /**
     * @return string
     */
    public function render()
    {
        $renderingContext = $this->getWidgetRenderingContext();
        $widgetChildNodes = $this->getWidgetChildNodes();
        $this->addArgumentsToTemplateVariableContainer($this->arguments['arguments']);
        $output = $widgetChildNodes->evaluate($renderingContext);
        $this->removeArgumentsFromTemplateVariableContainer($this->arguments['arguments']);
        return $output;
    }

    /**
     * Get the widget rendering context, or throw an exception if it cannot be found.
     *
     * @throws \TYPO3\CMS\Fluid\Core\Widget\Exception\RenderingContextNotFoundException
     * @return \TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface
     */
    protected function getWidgetRenderingContext()
    {
        $renderingContext = $this->getWidgetContext()->getViewHelperChildNodeRenderingContext();
        if (!$renderingContext instanceof \TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface) {
            throw new \TYPO3\CMS\Fluid\Core\Widget\Exception\RenderingContextNotFoundException('Rendering Context not found inside Widget. <f:renderChildren> has been used in an AJAX Request, but is only usable in non-ajax mode.', 1284986604);
        }
        return $renderingContext;
    }

    /**
     * @return \TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\RootNode
     */
    protected function getWidgetChildNodes()
    {
        return $this->getWidgetContext()->getViewHelperChildNodes();
    }

    /**
     * @throws \TYPO3\CMS\Fluid\Core\Widget\Exception\WidgetRequestNotFoundException
     * @return \TYPO3\CMS\Fluid\Core\Widget\WidgetContext
     */
    protected function getWidgetContext()
    {
        $request = $this->controllerContext->getRequest();
        if (!$request instanceof \TYPO3\CMS\Fluid\Core\Widget\WidgetRequest) {
            throw new \TYPO3\CMS\Fluid\Core\Widget\Exception\WidgetRequestNotFoundException('The Request is not a WidgetRequest! <f:renderChildren> must be called inside a Widget Template.', 1284986120);
        }
        return $request->getWidgetContext();
    }

    /**
     * Add the given arguments to the TemplateVariableContainer of the widget.
     *
     * @param array $arguments
     */
    protected function addArgumentsToTemplateVariableContainer(array $arguments)
    {
        $templateVariableContainer = $this->getWidgetRenderingContext()->getVariableProvider();
        foreach ($arguments as $identifier => $value) {
            $templateVariableContainer->add($identifier, $value);
        }
    }

    /**
     * Remove the given arguments from the TemplateVariableContainer of the widget.
     *
     * @param array $arguments
     */
    protected function removeArgumentsFromTemplateVariableContainer(array $arguments)
    {
        $templateVariableContainer = $this->getWidgetRenderingContext()->getVariableProvider();
        foreach ($arguments as $identifier => $value) {
            $templateVariableContainer->remove($identifier);
        }
    }
}
