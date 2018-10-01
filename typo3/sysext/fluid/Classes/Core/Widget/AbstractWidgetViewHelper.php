<?php
namespace TYPO3\CMS\Fluid\Core\Widget;

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

use TYPO3Fluid\Fluid\Core\Compiler\TemplateCompiler;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 */
abstract class AbstractWidgetViewHelper extends AbstractViewHelper
{
    /**
     * The Controller associated to this widget.
     * This needs to be filled by the individual subclass by an inject method.
     *
     * @var \TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetController
     */
    protected $controller;

    /**
     * If set to TRUE, it is an AJAX widget.
     *
     * @var bool
     */
    protected $ajaxWidget = false;

    /**
     * @var \TYPO3\CMS\Fluid\Core\Widget\AjaxWidgetContextHolder
     */
    private $ajaxWidgetContextHolder;

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \TYPO3\CMS\Extbase\Service\ExtensionService
     */
    protected $extensionService;

    /**
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * @var \TYPO3\CMS\Fluid\Core\Widget\WidgetContext
     */
    private $widgetContext;

    /**
     * @param \TYPO3\CMS\Fluid\Core\Widget\AjaxWidgetContextHolder $ajaxWidgetContextHolder
     * @internal
     */
    public function injectAjaxWidgetContextHolder(\TYPO3\CMS\Fluid\Core\Widget\AjaxWidgetContextHolder $ajaxWidgetContextHolder)
    {
        $this->ajaxWidgetContextHolder = $ajaxWidgetContextHolder;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
     * @internal
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
        $this->widgetContext = $this->objectManager->get(\TYPO3\CMS\Fluid\Core\Widget\WidgetContext::class);
    }

    /**
     * @param \TYPO3\CMS\Extbase\Service\ExtensionService $extensionService
     * @internal
     */
    public function injectExtensionService(\TYPO3\CMS\Extbase\Service\ExtensionService $extensionService)
    {
        $this->extensionService = $extensionService;
    }

    /**
     * Initialize arguments.
     * @internal
     */
    public function initializeArguments()
    {
        $this->registerArgument(
            'customWidgetId',
            'string',
            'extend the widget identifier with a custom widget id',
            false,
            null
        );
    }

    /**
     * Initialize the arguments of the ViewHelper, and call the render() method of the ViewHelper.
     *
     * @return string the rendered ViewHelper.
     * @internal
     */
    public function initializeArgumentsAndRender()
    {
        $this->validateArguments();
        $this->initialize();
        $this->initializeWidgetContext();
        return $this->callRenderMethod();
    }

    /**
     * Initialize the Widget Context, before the Render method is called.
     */
    private function initializeWidgetContext()
    {
        $this->widgetContext->setWidgetConfiguration($this->getWidgetConfiguration());
        $this->initializeWidgetIdentifier();
        $this->widgetContext->setControllerObjectName(get_class($this->controller));
        $extensionName = $this->renderingContext->getControllerContext()->getRequest()->getControllerExtensionName();
        $pluginName = $this->renderingContext->getControllerContext()->getRequest()->getPluginName();
        $this->widgetContext->setParentExtensionName($extensionName);
        $this->widgetContext->setParentPluginName($pluginName);
        $pluginNamespace = $this->extensionService->getPluginNamespace($extensionName, $pluginName);
        $this->widgetContext->setParentPluginNamespace($pluginNamespace);
        $this->widgetContext->setWidgetViewHelperClassName(static::class);
        if ($this->ajaxWidget === true) {
            $this->ajaxWidgetContextHolder->store($this->widgetContext);
        }
    }

    /**
     * Stores the syntax tree child nodes in the Widget Context, so they can be
     * rendered with <f:widget.renderChildren> lateron.
     *
     * @param array $childNodes The SyntaxTree Child nodes of this ViewHelper.
     * @internal
     */
    public function setChildNodes(array $childNodes)
    {
        $rootNode = $this->objectManager->get(\TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\RootNode::class);
        foreach ($childNodes as $childNode) {
            $rootNode->addChildNode($childNode);
        }
        $this->widgetContext->setViewHelperChildNodes($rootNode, $this->renderingContext);
    }

    /**
     * Generate the configuration for this widget. Override to adjust.
     *
     * @return array
     */
    protected function getWidgetConfiguration()
    {
        return $this->arguments;
    }

    /**
     * Initiate a sub request to $this->controller. Make sure to fill $this->controller
     * via Dependency Injection.
     *
     * @return \TYPO3\CMS\Extbase\Mvc\ResponseInterface the response of this request.
     * @throws \TYPO3\CMS\Fluid\Core\Widget\Exception\MissingControllerException
     */
    protected function initiateSubRequest()
    {
        if (!isset($this->controller) || !$this->controller instanceof \TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetController) {
            throw new \TYPO3\CMS\Fluid\Core\Widget\Exception\MissingControllerException(
                'initiateSubRequest() can not be called if there is no valid controller extending ' .
                'TYPO3\\CMS\\Fluid\\Core\\Widget\\AbstractWidgetController' .
                ' Got "' . ($this->controller ? get_class($this->controller) : gettype($this->controller)) .
                '" in class "' . static::class . '".',
                1289422564
            );
        }
        $subRequest = $this->objectManager->get(\TYPO3\CMS\Fluid\Core\Widget\WidgetRequest::class);
        $subRequest->setWidgetContext($this->widgetContext);
        $this->passArgumentsToSubRequest($subRequest);
        $subResponse = $this->objectManager->get(\TYPO3\CMS\Extbase\Mvc\Web\Response::class);
        $this->controller->processRequest($subRequest, $subResponse);
        return $subResponse;
    }

    /**
     * Pass the arguments of the widget to the subrequest.
     *
     * @param \TYPO3\CMS\Fluid\Core\Widget\WidgetRequest $subRequest
     */
    private function passArgumentsToSubRequest(\TYPO3\CMS\Fluid\Core\Widget\WidgetRequest $subRequest)
    {
        $arguments = $this->renderingContext->getControllerContext()->getRequest()->getArguments();
        $widgetIdentifier = $this->widgetContext->getWidgetIdentifier();
        if (isset($arguments[$widgetIdentifier])) {
            if (isset($arguments[$widgetIdentifier]['action'])) {
                $subRequest->setControllerActionName($arguments[$widgetIdentifier]['action']);
                unset($arguments[$widgetIdentifier]['action']);
            }
            $subRequest->setArguments($arguments[$widgetIdentifier]);
        }
    }

    /**
     * The widget identifier is unique on the current page, and is used
     * in the URI as a namespace for the widget's arguments.
     *
     * @return string the widget identifier for this widget
     * @todo clean up, and make it somehow more routing compatible.
     */
    private function initializeWidgetIdentifier()
    {
        $widgetCounter = $this->viewHelperVariableContainer->get(\TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetViewHelper::class, 'nextWidgetNumber', 0);
        $widgetIdentifier = '@widget_' . ((isset($this->arguments['customWidgetId']) && $this->arguments['customWidgetId'] !== null) ? $this->arguments['customWidgetId'] . '_' : '') . $widgetCounter;
        $this->viewHelperVariableContainer->addOrUpdate(\TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetViewHelper::class, 'nextWidgetNumber', $widgetCounter + 1);
        $this->widgetContext->setWidgetIdentifier($widgetIdentifier);
    }

    /**
     * @param string $argumentsName
     * @param string $closureName
     * @param string $initializationPhpCode
     * @param ViewHelperNode $node
     * @param TemplateCompiler $compiler
     * @return string
     */
    public function compile($argumentsName, $closureName, &$initializationPhpCode, ViewHelperNode $node, TemplateCompiler $compiler)
    {
        $compiler->disable();
        return '\'\'';
    }
}
