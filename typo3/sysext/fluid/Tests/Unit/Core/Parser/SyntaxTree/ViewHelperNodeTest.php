<?php

/*                                                                        *
 * This script is backported from the FLOW3 package "TYPO3.Fluid".        *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once(dirname(__FILE__) . '/../Fixtures/ChildNodeAccessFacetViewHelper.php');
require_once(dirname(__FILE__) . '/../../Fixtures/TestViewHelper.php');

/**
 * Testcase for [insert classname here]
 *
 */
class Tx_Fluid_Tests_Unit_Core_Parser_SyntaxTree_ViewHelperNodeTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * Rendering Context
	 * @var Tx_Fluid_Core_Rendering_RenderingContext
	 */
	protected $renderingContext;

	/**
	 * Object factory mock
	 * @var Tx_Extbase_Object_ObjectManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * Template Variable Container
	 * @var Tx_Fluid_Core_ViewHelper_TemplateVariableContainer
	 */
	protected $templateVariableContainer;

	/**
	 *
	 * @var Tx_Extbase_MVC_Controller_ControllerContext
	 */
	protected $controllerContext;

	/**
	 * @var Tx_Fluid_Core_ViewHelper_ViewHelperVariableContainer
	 */
	protected $viewHelperVariableContainer;

	/**
	 * Setup fixture
	 */
	public function setUp() {
		$this->renderingContext = new Tx_Fluid_Core_Rendering_RenderingContext();

		$this->mockObjectManager = $this->getMock('Tx_Extbase_Object_ObjectManagerInterface');
		$this->renderingContext->injectObjectManager($this->mockObjectManager);

		$this->templateVariableContainer = $this->getMock('Tx_Fluid_Core_ViewHelper_TemplateVariableContainer');
		$this->renderingContext->injectTemplateVariableContainer($this->templateVariableContainer);

		$this->controllerContext = $this->getMock('Tx_Extbase_MVC_Controller_ControllerContext', array(), array(), '', FALSE);
		$this->renderingContext->setControllerContext($this->controllerContext);

		$this->viewHelperVariableContainer = $this->getMock('Tx_Fluid_Core_ViewHelper_ViewHelperVariableContainer');
		$this->renderingContext->injectViewHelperVariableContainer($this->viewHelperVariableContainer);
	}

	/**
	 * @test
	 */
	public function constructorSetsViewHelperAndArguments() {
		$viewHelper = $this->getMock('Tx_Fluid_Core_ViewHelper_AbstractViewHelper');
		$arguments = array('foo' => 'bar');
		$viewHelperNode = $this->getAccessibleMock('Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode', array('dummy'), array($viewHelper, $arguments));

		$this->assertEquals(get_class($viewHelper), $viewHelperNode->getViewHelperClassName());
		$this->assertEquals($arguments, $viewHelperNode->_get('arguments'));
	}

	/**
	 * @test
	 */
	public function childNodeAccessFacetWorksAsExpected() {
		$childNode = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_TextNode', array(), array('foo'));

		$mockViewHelper = $this->getMock('Tx_Fluid_Core_Parser_Fixtures_ChildNodeAccessFacetViewHelper', array('setChildNodes', 'initializeArguments', 'render', 'prepareArguments'));

		$viewHelperNode = new Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode($mockViewHelper, array());
		$viewHelperNode->addChildNode($childNode);

		$mockViewHelper->expects($this->once())->method('setChildNodes')->with($this->equalTo(array($childNode)));

		$viewHelperNode->evaluate($this->renderingContext);
	}

	/**
	 * @test
	 */
	public function initializeArgumentsAndRenderIsCalledByViewHelperNode() {
		$mockViewHelper = $this->getMock('Tx_Fluid_Core_ViewHelper_AbstractViewHelper', array('initializeArgumentsAndRender', 'prepareArguments'));
		$mockViewHelper->expects($this->once())->method('initializeArgumentsAndRender');

		$viewHelperNode = new Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode($mockViewHelper, array());

		$viewHelperNode->evaluate($this->renderingContext);
	}

	/**
	 * @test
	 */
	public function initializeArgumentsAndRenderIsCalledWithCorrectArguments() {
		$arguments = array(
			'param0' => new Tx_Fluid_Core_ViewHelper_ArgumentDefinition('param1', 'string', 'Hallo', TRUE, null, FALSE),
			'param1' => new Tx_Fluid_Core_ViewHelper_ArgumentDefinition('param1', 'string', 'Hallo', TRUE, null, TRUE),
			'param2' => new Tx_Fluid_Core_ViewHelper_ArgumentDefinition('param2', 'string', 'Hallo', TRUE, null, TRUE)
		);

		$mockViewHelper = $this->getMock('Tx_Fluid_Core_ViewHelper_AbstractViewHelper', array('initializeArgumentsAndRender', 'prepareArguments'));
		$mockViewHelper->expects($this->any())->method('prepareArguments')->will($this->returnValue($arguments));
		$mockViewHelper->expects($this->once())->method('initializeArgumentsAndRender');

		$viewHelperNode = new Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode($mockViewHelper, array(
			'param2' => new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('b'),
			'param1' => new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('a'),
		));

		$viewHelperNode->evaluate($this->renderingContext);
	}

	/**
	 * @test
	 */
	public function evaluateMethodPassesRenderingContextToViewHelper() {
		$mockViewHelper = $this->getMock('Tx_Fluid_Core_ViewHelper_AbstractViewHelper', array('render', 'validateArguments', 'prepareArguments', 'setRenderingContext'));
		$mockViewHelper->expects($this->once())->method('setRenderingContext')->with($this->renderingContext);

		$viewHelperNode = new Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode($mockViewHelper, array());

		$viewHelperNode->evaluate($this->renderingContext);
	}

	/**
	 * @test
	 */
	public function multipleEvaluateCallsShareTheSameViewHelperInstance() {
		$mockViewHelper = $this->getMock('Tx_Fluid_Core_ViewHelper_AbstractViewHelper', array('render', 'validateArguments', 'prepareArguments', 'setViewHelperVariableContainer'));
		$mockViewHelper->expects($this->any())->method('render')->will($this->returnValue('String'));

		$viewHelperNode = new Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode($mockViewHelper, array());

		$viewHelperNode->evaluate($this->renderingContext);
		$viewHelperNode->evaluate($this->renderingContext);
	}
}

?>