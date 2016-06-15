<?php
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
use TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetViewHelper;
use TYPO3\CMS\Fluid\Core\Widget\Exception\MissingControllerException;
use TYPO3Fluid\Fluid\Core\Compiler\TemplateCompiler;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;

/**
 * Test case
 */
class AbstractWidgetViewHelperTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetViewHelper
     */
    protected $viewHelper;

    /**
     * @var \TYPO3\CMS\Fluid\Core\Widget\AjaxWidgetContextHolder
     */
    protected $ajaxWidgetContextHolder;

    /**
     * @var \TYPO3\CMS\Fluid\Core\Widget\WidgetContext
     */
    protected $widgetContext;

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext
     */
    protected $controllerContext;

    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Web\Request
     */
    protected $request;

    /**
     * @var \TYPO3\CMS\Extbase\Service\ExtensionService
     */
    protected $mockExtensionService;

    /**

     */
    protected function setUp()
    {
        $this->viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetViewHelper::class, array('validateArguments', 'initialize', 'callRenderMethod', 'getWidgetConfiguration', 'getRenderingContext'));
        $this->mockExtensionService = $this->createMock(\TYPO3\CMS\Extbase\Service\ExtensionService::class);
        $this->viewHelper->_set('extensionService', $this->mockExtensionService);
        $this->ajaxWidgetContextHolder = $this->createMock(\TYPO3\CMS\Fluid\Core\Widget\AjaxWidgetContextHolder::class);
        $this->viewHelper->injectAjaxWidgetContextHolder($this->ajaxWidgetContextHolder);
        $this->widgetContext = $this->createMock(\TYPO3\CMS\Fluid\Core\Widget\WidgetContext::class);
        $this->objectManager = $this->createMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $this->objectManager->expects($this->at(0))->method('get')->with(\TYPO3\CMS\Fluid\Core\Widget\WidgetContext::class)->will($this->returnValue($this->widgetContext));
        $this->viewHelper->injectObjectManager($this->objectManager);
        $this->request = $this->createMock(\TYPO3\CMS\Extbase\Mvc\Web\Request::class);
        $this->controllerContext = $this->createMock(\TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext::class);
        $this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->renderingContext = $this->getMockBuilder(\TYPO3\CMS\Fluid\Tests\Unit\Core\Rendering\RenderingContextFixture::class)
            ->setMethods(array('getControllerContext'))
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
     *
     * @return void
     */
    public function callViewHelper()
    {
        $mockViewHelperVariableContainer = $this->createMock(\TYPO3\CMS\Fluid\Core\ViewHelper\ViewHelperVariableContainer::class);
        $mockViewHelperVariableContainer->expects($this->any())->method('get')->willReturnArgument(2);
        $mockRenderingContext = $this->createMock(\TYPO3\CMS\Fluid\Tests\Unit\Core\Rendering\RenderingContextFixture::class);
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
        $node1 = $this->createMock(\TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\AbstractNode::class);
        $node2 = $this->createMock(\TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\TextNode::class);
        $node3 = $this->createMock(\TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\AbstractNode::class);
        $rootNode = $this->createMock(\TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\RootNode::class);
        $rootNode->expects($this->at(0))->method('addChildNode')->with($node1);
        $rootNode->expects($this->at(1))->method('addChildNode')->with($node2);
        $rootNode->expects($this->at(2))->method('addChildNode')->with($node3);
        $this->objectManager->expects($this->once())->method('get')->with(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\RootNode::class)->will($this->returnValue($rootNode));
        $renderingContext = $this->createMock(\TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface::class);
        $this->viewHelper->_set('renderingContext', $renderingContext);
        $this->widgetContext->expects($this->once())->method('setViewHelperChildNodes')->with($rootNode, $renderingContext);
        $this->viewHelper->setChildNodes(array($node1, $node2, $node3));
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
        $controller = $this->createMock(\TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetController::class);
        $this->viewHelper->_set('controller', $controller);
        // Initial Setup
        $widgetRequest = $this->createMock(\TYPO3\CMS\Fluid\Core\Widget\WidgetRequest::class);
        $response = $this->createMock(\TYPO3\CMS\Extbase\Mvc\Web\Response::class);
        $this->objectManager->expects($this->at(0))->method('get')->with(\TYPO3\CMS\Fluid\Core\Widget\WidgetRequest::class)->will($this->returnValue($widgetRequest));
        $this->objectManager->expects($this->at(1))->method('get')->with(\TYPO3\CMS\Extbase\Mvc\Web\Response::class)->will($this->returnValue($response));
        // Widget Context is set
        $widgetRequest->expects($this->once())->method('setWidgetContext')->with($this->widgetContext);
        // The namespaced arguments are passed to the sub-request
        // and the action name is exctracted from the namespace.
        $this->controllerContext->expects($this->once())->method('getRequest')->will($this->returnValue($this->request));
        $this->widgetContext->expects($this->once())->method('getWidgetIdentifier')->will($this->returnValue('widget-1'));
        $this->request->expects($this->once())->method('getArguments')->will($this->returnValue(array(
            'k1' => 'k2',
            'widget-1' => array(
                'arg1' => 'val1',
                'arg2' => 'val2',
                'action' => 'myAction'
            )
        )));
        $widgetRequest->expects($this->once())->method('setArguments')->with(array(
            'arg1' => 'val1',
            'arg2' => 'val2'
        ));
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
            ->setMethods(array('dummy'))
            ->getMock();
        $viewHelper->setArguments(array('foo' => 'bar'));
        $this->assertEquals(array('foo' => 'bar'), $this->callInaccessibleMethod($viewHelper, 'getWidgetConfiguration'));
    }

    /**
     * @test
     */
    public function compileDisablesTemplateCompiler()
    {
        $viewHelper = $this->getMockBuilder(AbstractWidgetViewHelper::class)
            ->setMethods(array('dummy'))
            ->getMock();
        $node = $this->getMockBuilder(ViewHelperNode::class)
            ->setMethods(array('dummy'))
            ->disableOriginalConstructor()
            ->getMock();
        $compiler = $this->getMockBuilder(TemplateCompiler::class)
            ->setMethods(array('disable'))
            ->getMock();
        $compiler->expects($this->once())->method('disable');
        $code = ''; // referenced
        $result = $viewHelper->compile('', '', $code, $node, $compiler);
        $this->assertEquals('\'\'', $result);
        $this->assertEquals('', $code);
    }
}
