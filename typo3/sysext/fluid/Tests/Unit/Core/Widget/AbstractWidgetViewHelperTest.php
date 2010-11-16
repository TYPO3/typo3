<?php

/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for AbstractWidgetViewHelper
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Fluid_Tests_Unit_Core_Widget_AbstractWidgetViewHelperTest extends Tx_Extbase_BaseTestCase {

	/**
	 * @var Tx_Fluid_Core_Widget_AbstractWidgetViewHelper
	 */
	protected $viewHelper;

	/**
	 * @var Tx_Fluid_Core_Widget_AjaxWidgetContextHolder
	 */
	protected $ajaxWidgetContextHolder;

	/**
	 * @var Tx_Fluid_Core_Widget_WidgetContext
	 */
	protected $widgetContext;

	/**
	 * @var Tx_Extbase_Object_ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var Tx_Extbase_MVC_Controller_ControllerContext
	 */
	protected $controllerContext;

	/**
	 * @var Tx_Extbase_MVC_Web_Request
	 */
	protected $request;

	/**
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setUp() {
		$this->viewHelper = $this->getAccessibleMock('Tx_Fluid_Core_Widget_AbstractWidgetViewHelper', array('validateArguments', 'initialize', 'callRenderMethod', 'getWidgetConfiguration', 'getRenderingContext'));

		$this->ajaxWidgetContextHolder = $this->getMock('Tx_Fluid_Core_Widget_AjaxWidgetContextHolder');
		$this->viewHelper->injectAjaxWidgetContextHolder($this->ajaxWidgetContextHolder);

		$this->widgetContext = $this->getMock('Tx_Fluid_Core_Widget_WidgetContext');
		$this->viewHelper->injectWidgetContext($this->widgetContext);

		$this->objectManager = $this->getMock('Tx_Extbase_Object_ObjectManagerInterface');
		$this->viewHelper->injectObjectManager($this->objectManager);

		$this->controllerContext = $this->getMock('Tx_Extbase_MVC_Controller_ControllerContext', array(), array(), '', FALSE);
		$this->viewHelper->_set('controllerContext', $this->controllerContext);

		$this->request = $this->getMock('Tx_Extbase_MVC_Web_Request');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function initializeArgumentsAndRenderCallsTheRightSequenceOfMethods() {
		$this->callViewHelper();
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function initializeArgumentsAndRenderStoresTheWidgetContextIfInAjaxMode() {
		$this->viewHelper->_set('ajaxWidget', TRUE);
		$this->ajaxWidgetContextHolder->expects($this->once())->method('store')->with($this->widgetContext);

		$this->callViewHelper();
	}

	/**
	 * Calls the ViewHelper, and emulates a rendering.
	 *
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function callViewHelper() {
		$viewHelperVariableContainer = $this->getMock('Tx_Fluid_Core_ViewHelper_ViewHelperVariableContainer');
		$this->viewHelper->setViewHelperVariableContainer($viewHelperVariableContainer);

		$this->viewHelper->expects($this->once())->method('getWidgetConfiguration')->will($this->returnValue('Some Widget Configuration'));
		$this->widgetContext->expects($this->once())->method('setWidgetConfiguration')->with('Some Widget Configuration');

		$this->widgetContext->expects($this->once())->method('setWidgetIdentifier')->with('__widget_0');

		$this->viewHelper->_set('controller', new stdClass());
		$this->widgetContext->expects($this->once())->method('setControllerObjectName')->with('stdClass');

		$this->viewHelper->expects($this->once())->method('validateArguments');
		$this->viewHelper->expects($this->once())->method('initialize');
		$this->viewHelper->expects($this->once())->method('callRenderMethod')->with(array('arg1' => 'val1'))->will($this->returnValue('renderedResult'));
		$output = $this->viewHelper->initializeArgumentsAndRender(array('arg1' => 'val1'));
		$this->assertEquals('renderedResult', $output);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setChildNodesAddsChildNodesToWidgetContext() {
		$node1 = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode');
		$node2 = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_TextNode', array(), array(), '', FALSE);
		$node3 = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode');

		$rootNode = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_RootNode');
		$rootNode->expects($this->at(0))->method('addChildNode')->with($node1);
		$rootNode->expects($this->at(1))->method('addChildNode')->with($node2);
		$rootNode->expects($this->at(2))->method('addChildNode')->with($node3);

		$this->objectManager->expects($this->once())->method('create')->with('Tx_Fluid_Core_Parser_SyntaxTree_RootNode')->will($this->returnValue($rootNode));

		$renderingContext = $this->getMock('Tx_Fluid_Core_Rendering_RenderingContextInterface');
		$this->viewHelper->expects($this->once())->method('getRenderingContext')->will($this->returnValue($renderingContext));

		$this->widgetContext->expects($this->once())->method('setViewHelperChildNodes')->with($rootNode, $renderingContext);
		$this->viewHelper->setChildNodes(array($node1, $node2, $node3));
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_Widget_Exception_MissingControllerException
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function initiateSubRequestThrowsExceptionIfControllerIsNoWidgetController() {
		$controller = $this->getMock('Tx_Fluid_MVC_Controller_ControllerInterface');
		$this->viewHelper->_set('controller', $controller);

		$this->viewHelper->_call('initiateSubRequest');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function initiateSubRequestBuildsRequestProperly() {
		$controller = $this->getMock('Tx_Fluid_Core_Widget_AbstractWidgetController', array(), array(), '', FALSE);
		$this->viewHelper->_set('controller', $controller);

		// Initial Setup
		$widgetRequest = $this->getMock('Tx_Fluid_Core_Widget_WidgetRequest');
		$response = $this->getMock('Tx_Fluid_MVC_Web_Response');
		$this->objectManager->expects($this->at(0))->method('create')->with('Tx_Fluid_Core_Widget_WidgetRequest')->will($this->returnValue($widgetRequest));
		$this->objectManager->expects($this->at(1))->method('create')->with('Tx_Fluid_MVC_Web_Response')->will($this->returnValue($response));

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
}
?>