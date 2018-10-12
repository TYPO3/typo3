<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Fluid\Tests\Unit\Core\Widget;

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
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Extbase\Mvc\Web\Request;
use TYPO3\CMS\Extbase\Mvc\Web\Response;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetController;
use TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetViewHelper;
use TYPO3\CMS\Fluid\Core\Widget\AjaxWidgetContextHolder;
use TYPO3\CMS\Fluid\Core\Widget\Exception\MissingControllerException;
use TYPO3\CMS\Fluid\Core\Widget\WidgetContext;
use TYPO3\CMS\Fluid\Core\Widget\WidgetRequest;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use TYPO3\TestingFramework\Fluid\Unit\Core\Rendering\RenderingContextFixture;
use TYPO3Fluid\Fluid\Core\Compiler\TemplateCompiler;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\AbstractNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\RootNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\TextNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
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
     * @var RenderingContextFixture
     */
    protected $renderingContext;

    /**

     */
    protected function setUp()
    {
        $this->viewHelper = $this->getAccessibleMock(AbstractWidgetViewHelper::class, ['validateArguments', 'initialize', 'callRenderMethod', 'getWidgetConfiguration', 'getRenderingContext']);
        $this->mockExtensionService = $this->createMock(ExtensionService::class);
        $this->viewHelper->_set('extensionService', $this->mockExtensionService);
        $this->ajaxWidgetContextHolder = $this->createMock(AjaxWidgetContextHolder::class);
        $this->viewHelper->injectAjaxWidgetContextHolder($this->ajaxWidgetContextHolder);
        $this->widgetContext = $this->createMock(WidgetContext::class);
        $this->objectManager = $this->createMock(ObjectManagerInterface::class);
        $this->objectManager->expects($this->at(0))->method('get')->with(WidgetContext::class)->will($this->returnValue($this->widgetContext));
        $this->viewHelper->injectObjectManager($this->objectManager);
        $this->request = $this->createMock(Request::class);
        $this->controllerContext = $this->createMock(ControllerContext::class);
        $this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->renderingContext = $this->getMockBuilder(RenderingContextFixture::class)
            ->setMethods(['getControllerContext'])
            ->getMock();
        $this->renderingContext->expects($this->any())->method('getControllerContext')->willReturn($this->controllerContext);
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
        $this->ajaxWidgetContextHolder->expects($this->once())->method('store')->with($this->widgetContext);
        $this->callViewHelper();
    }

    /**
     * Calls the ViewHelper, and emulates a rendering.
     */
    public function callViewHelper()
    {
        $mockViewHelperVariableContainer = $this->createMock(ViewHelperVariableContainer::class);
        $mockViewHelperVariableContainer->expects($this->any())->method('get')->willReturnArgument(2);
        $mockRenderingContext = $this->createMock(RenderingContextFixture::class);
        $mockRenderingContext->expects($this->atLeastOnce())->method('getViewHelperVariableContainer')->will($this->returnValue($mockViewHelperVariableContainer));
        $mockRenderingContext->expects($this->any())->method('getControllerContext')->willReturn($this->controllerContext);
        $this->viewHelper->setRenderingContext($mockRenderingContext);
        $this->viewHelper->expects($this->once())->method('getWidgetConfiguration')->will($this->returnValue('Some Widget Configuration'));
        $this->widgetContext->expects($this->once())->method('setWidgetConfiguration')->with('Some Widget Configuration');
        $this->widgetContext->expects($this->once())->method('setWidgetIdentifier')->with('@widget_0');
        $this->viewHelper->_set('controller', new \stdClass());
        $this->viewHelper->_set('renderingContext', $mockRenderingContext);
        $this->widgetContext->expects($this->once())->method('setControllerObjectName')->with('stdClass');
        $this->viewHelper->expects($this->once())->method('validateArguments');
        $this->viewHelper->expects($this->once())->method('initialize');
        $this->viewHelper->expects($this->once())->method('callRenderMethod')->will($this->returnValue('renderedResult'));
        $output = $this->viewHelper->initializeArgumentsAndRender();
        $this->assertEquals('renderedResult', $output);
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
        $rootNode->expects($this->at(0))->method('addChildNode')->with($node1);
        $rootNode->expects($this->at(1))->method('addChildNode')->with($node2);
        $rootNode->expects($this->at(2))->method('addChildNode')->with($node3);
        $this->objectManager->expects($this->once())->method('get')->with(RootNode::class)->will($this->returnValue($rootNode));
        $renderingContext = $this->createMock(RenderingContextInterface::class);
        $this->viewHelper->_set('renderingContext', $renderingContext);
        $this->widgetContext->expects($this->once())->method('setViewHelperChildNodes')->with($rootNode, $renderingContext);
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
        $this->objectManager->expects($this->at(0))->method('get')->with(WidgetRequest::class)->will($this->returnValue($widgetRequest));
        $this->objectManager->expects($this->at(1))->method('get')->with(Response::class)->will($this->returnValue($response));
        // Widget Context is set
        $widgetRequest->expects($this->once())->method('setWidgetContext')->with($this->widgetContext);
        // The namespaced arguments are passed to the sub-request
        // and the action name is exctracted from the namespace.
        $this->controllerContext->expects($this->once())->method('getRequest')->will($this->returnValue($this->request));
        $this->widgetContext->expects($this->once())->method('getWidgetIdentifier')->will($this->returnValue('widget-1'));
        $this->request->expects($this->once())->method('getArguments')->will($this->returnValue([
            'k1' => 'k2',
            'widget-1' => [
                'arg1' => 'val1',
                'arg2' => 'val2',
                'action' => 'myAction'
            ]
        ]));
        $widgetRequest->expects($this->once())->method('setArguments')->with([
            'arg1' => 'val1',
            'arg2' => 'val2'
        ]);
        $widgetRequest->expects($this->once())->method('setControllerActionName')->with('myAction');
        // Controller is called
        $controller->expects($this->once())->method('processRequest')->with($widgetRequest, $response);
        $output = $this->viewHelper->_call('initiateSubRequest');
        // SubResponse is returned
        $this->assertSame($response, $output);
    }

    /**
     * @test
     */
    public function getWidgetConfigurationReturnsArgumentsProperty()
    {
        $viewHelper = $this->getMockBuilder(AbstractWidgetViewHelper::class)
            ->setMethods(['dummy'])
            ->getMock();
        $viewHelper->setArguments(['foo' => 'bar']);
        $this->assertEquals(['foo' => 'bar'], $this->callInaccessibleMethod($viewHelper, 'getWidgetConfiguration'));
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
        $compiler->expects($this->once())->method('disable');
        $code = ''; // referenced
        $result = $viewHelper->compile('', '', $code, $node, $compiler);
        $this->assertEquals('\'\'', $result);
        $this->assertEquals('', $code);
    }
}
