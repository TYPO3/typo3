<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Fluid\Tests\Unit\Core\Widget;

use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\Response;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetController;
use TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetViewHelper;
use TYPO3\CMS\Fluid\Core\Widget\AjaxWidgetContextHolder;
use TYPO3\CMS\Fluid\Core\Widget\Exception\MissingControllerException;
use TYPO3\CMS\Fluid\Core\Widget\WidgetContext;
use TYPO3\CMS\Fluid\Core\Widget\WidgetRequest;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use TYPO3Fluid\Fluid\Core\Compiler\TemplateCompiler;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\AbstractNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\RootNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\TextNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperVariableContainer;

/**
 * Test case
 */
class AbstractWidgetViewHelperTest extends UnitTestCase
{
    /**
     * @var AbstractWidgetViewHelper
     */
    protected $viewHelper;

    /**
     * @var AjaxWidgetContextHolder
     */
    protected $ajaxWidgetContextHolder;

    /**
     * @var WidgetContext
     */
    protected $widgetContext;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var ControllerContext
     */
    protected $controllerContext;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var ExtensionService
     */
    protected $mockExtensionService;

    /**
     * @var RenderingContext
     */
    protected $renderingContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(AbstractWidgetViewHelper::class, ['validateArguments', 'initialize', 'callRenderMethod', 'getWidgetConfiguration', 'getRenderingContext']);
        $this->mockExtensionService = $this->createMock(ExtensionService::class);
        $this->viewHelper->_set('extensionService', $this->mockExtensionService);
        $this->ajaxWidgetContextHolder = $this->createMock(AjaxWidgetContextHolder::class);
        $this->viewHelper->injectAjaxWidgetContextHolder($this->ajaxWidgetContextHolder);
        $this->widgetContext = $this->createMock(WidgetContext::class);
        $this->objectManager = $this->createMock(ObjectManagerInterface::class);
        $this->objectManager->expects(self::at(0))->method('get')->with(WidgetContext::class)->willReturn($this->widgetContext);
        $this->viewHelper->injectObjectManager($this->objectManager);
        $this->request = $this->createMock(Request::class);
        $this->controllerContext = $this->createMock(ControllerContext::class);
        $this->controllerContext->expects(self::any())->method('getRequest')->willReturn($this->request);
        $this->renderingContext = $this->getMockBuilder(RenderingContext::class)
            ->onlyMethods(['getControllerContext'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->renderingContext->expects(self::any())->method('getControllerContext')->willReturn($this->controllerContext);
        $this->viewHelper->_set('renderingContext', $this->renderingContext);
    }

    /**
     * @test
     */
    public function initializeArgumentsAndRenderCallsTheRightSequenceOfMethods()
    {
        $this->callViewHelper();
    }

    /**
     * @test
     */
    public function initializeArgumentsAndRenderStoresTheWidgetContextIfInAjaxMode()
    {
        $this->viewHelper->_set('ajaxWidget', true);
        $this->viewHelper->setArguments(['storeSession' => true]);
        $this->ajaxWidgetContextHolder->expects(self::once())->method('store')->with($this->widgetContext);
        $this->callViewHelper();
    }

    /**
     * @test
     */
    public function storeSessionSetToFalseDoesNotStoreTheWidgetContextIfInAjaxMode()
    {
        $this->viewHelper->_set('ajaxWidget', true);
        $this->viewHelper->setArguments(['storeSession' => false]);
        $this->ajaxWidgetContextHolder->expects(self::never())->method('store')->with($this->widgetContext);
        $this->callViewHelper();
    }

    /**
     * Calls the ViewHelper, and emulates a rendering.
     */
    public function callViewHelper()
    {
        $mockViewHelperVariableContainer = $this->createMock(ViewHelperVariableContainer::class);
        $mockViewHelperVariableContainer->expects(self::any())->method('get')->willReturnArgument(2);
        $mockRenderingContext = $this->getMockBuilder(RenderingContext::class)->disableOriginalConstructor()->getMock();
        $mockRenderingContext->expects(self::atLeastOnce())->method('getViewHelperVariableContainer')->willReturn($mockViewHelperVariableContainer);
        $mockRenderingContext->expects(self::any())->method('getControllerContext')->willReturn($this->controllerContext);
        $this->viewHelper->setRenderingContext($mockRenderingContext);
        $this->viewHelper->expects(self::once())->method('getWidgetConfiguration')->willReturn('Some Widget Configuration');
        $this->widgetContext->expects(self::once())->method('setWidgetConfiguration')->with('Some Widget Configuration');
        $this->widgetContext->expects(self::once())->method('setWidgetIdentifier')->with('@widget_0');
        $this->viewHelper->_set('controller', new \stdClass());
        $this->viewHelper->_set('renderingContext', $mockRenderingContext);
        $this->widgetContext->expects(self::once())->method('setControllerObjectName')->with('stdClass');
        $this->viewHelper->expects(self::once())->method('validateArguments');
        $this->viewHelper->expects(self::once())->method('initialize');
        $this->viewHelper->expects(self::once())->method('callRenderMethod')->willReturn('renderedResult');
        $output = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('renderedResult', $output);
    }

    /**
     * @test
     */
    public function setChildNodesAddsChildNodesToWidgetContext()
    {
        $node1 = $this->createMock(AbstractNode::class);
        $node2 = $this->createMock(TextNode::class);
        $node3 = $this->createMock(AbstractNode::class);
        $rootNode = $this->createMock(RootNode::class);
        $rootNode->expects(self::at(0))->method('addChildNode')->with($node1);
        $rootNode->expects(self::at(1))->method('addChildNode')->with($node2);
        $rootNode->expects(self::at(2))->method('addChildNode')->with($node3);
        $this->objectManager->expects(self::once())->method('get')->with(RootNode::class)->willReturn($rootNode);
        $renderingContext = $this->createMock(RenderingContext::class);
        $this->viewHelper->_set('renderingContext', $renderingContext);
        $this->widgetContext->expects(self::once())->method('setViewHelperChildNodes')->with($rootNode, $renderingContext);
        $this->viewHelper->setChildNodes([$node1, $node2, $node3]);
    }

    /**
     * @test
     */
    public function initiateSubRequestThrowsExceptionIfControllerIsNoWidgetController()
    {
        $controller = $this->createMock(AbstractWidgetViewHelper::class);

        $this->expectException(MissingControllerException::class);
        $this->expectExceptionCode(1289422564);

        $this->viewHelper->_set('controller', $controller);
        $this->viewHelper->_call('initiateSubRequest');
    }

    /**
     * @test
     */
    public function initiateSubRequestBuildsRequestProperly()
    {
        $controller = $this->createMock(AbstractWidgetController::class);
        $this->viewHelper->_set('controller', $controller);
        // Initial Setup
        $widgetRequest = $this->createMock(WidgetRequest::class);
        $response = $this->createMock(Response::class);
        $this->objectManager->expects(self::at(0))->method('get')->with(WidgetRequest::class)->willReturn($widgetRequest);
        $this->objectManager->expects(self::at(1))->method('get')->with(Response::class)->willReturn($response);
        // Widget Context is set
        $widgetRequest->expects(self::once())->method('setWidgetContext')->with($this->widgetContext);
        // The namespaced arguments are passed to the sub-request
        // and the action name is extracted from the namespace.
        $this->controllerContext->expects(self::once())->method('getRequest')->willReturn($this->request);
        $this->widgetContext->expects(self::once())->method('getWidgetIdentifier')->willReturn('widget-1');
        $this->request->expects(self::once())->method('getArguments')->willReturn([
            'k1' => 'k2',
            'widget-1' => [
                'arg1' => 'val1',
                'arg2' => 'val2',
                'action' => 'myAction'
            ]
        ]);
        $widgetRequest->expects(self::once())->method('setArguments')->with([
            'arg1' => 'val1',
            'arg2' => 'val2'
        ]);
        $widgetRequest->expects(self::once())->method('setControllerActionName')->with('myAction');
        // Controller is called
        $controller->expects(self::once())->method('processRequest')->with($widgetRequest, $response);
        $output = $this->viewHelper->_call('initiateSubRequest');
        // SubResponse is returned
        self::assertSame($response, $output);
    }

    /**
     * @test
     */
    public function getWidgetConfigurationReturnsArgumentsProperty()
    {
        $viewHelper = $this->getAccessibleMock(AbstractWidgetViewHelper::class, ['dummy'], [], '', false);
        $viewHelper->setArguments(['foo' => 'bar']);
        self::assertEquals(['foo' => 'bar'], $viewHelper->_call('getWidgetConfiguration'));
    }

    /**
     * @test
     */
    public function compileDisablesTemplateCompiler()
    {
        $viewHelper = $this->getMockBuilder(AbstractWidgetViewHelper::class)
            ->setMethods(['dummy'])
            ->getMock();
        $node = $this->getMockBuilder(ViewHelperNode::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $compiler = $this->getMockBuilder(TemplateCompiler::class)
            ->setMethods(['disable'])
            ->getMock();
        $compiler->expects(self::once())->method('disable');
        $code = ''; // referenced
        $result = $viewHelper->compile('', '', $code, $node, $compiler);
        self::assertEquals('\'\'', $result);
        self::assertEquals('', $code);
    }
}
