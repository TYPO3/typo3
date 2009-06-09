<?php

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package
 * @subpackage
 * @version $Id: ViewHelperNodeTest.php 2411 2009-05-26 22:00:04Z sebastian $
 */
/**
 * Testcase for [insert classname here]
 *
 * @package
 * @subpackage Tests
 * @version $Id: ViewHelperNodeTest.php 2411 2009-05-26 22:00:04Z sebastian $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
require_once(t3lib_extMgm::extPath('extbase', 'Tests/Base_testcase.php'));
class Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNodeComparatorTest_testcase extends Tx_Extbase_Base_testcase {

	/**
	 * Rendering Context
	 * @var Tx_Fluid_Core_Rendering_RenderingContext
	 */
	protected $renderingContext;

	/**
	 * Object factory mock
	 * @var Tx_Fluid_Compatibility_ObjectFactory
	 */
	protected $mockObjectFactory;

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
	 * @var Tx_Fluid_Core_Parser_TemplateParser
	 */
	protected $templateParser;

	/**
	 * @var Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode
	 */
	protected $viewHelperNode;

	/**
	 * @var Tx_Fluid_Core_Rendering_RenderingConfiguration
	 */
	protected $renderingConfiguration;

	/**
	 * Setup fixture
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setUp() {
		$this->renderingContext = new Tx_Fluid_Core_Rendering_RenderingContext();

		$this->mockObjectFactory = $this->getMock('Tx_Fluid_Compatibility_ObjectFactory');
		$this->renderingContext->injectObjectFactory($this->mockObjectFactory);

		$this->templateVariableContainer = $this->getMock('Tx_Fluid_Core_ViewHelper_TemplateVariableContainer', array('dummy'));
		$this->renderingContext->setTemplateVariableContainer($this->templateVariableContainer);

		$this->controllerContext = $this->getMock('Tx_Extbase_MVC_Controller_ControllerContext');
		$this->renderingContext->setControllerContext($this->controllerContext);

		$this->viewHelperVariableContainer = $this->getMock('Tx_Fluid_Core_ViewHelper_ViewHelperVariableContainer');
		$this->renderingContext->setViewHelperVariableContainer($this->viewHelperVariableContainer);

		$this->renderingConfiguration = $this->getMock('Tx_Fluid_Core_Rendering_RenderingConfiguration');
		$this->renderingContext->setRenderingConfiguration($this->renderingConfiguration);

		$this->templateParser = t3lib_div::makeInstance('Tx_Fluid_Core_Parser_TemplateParser');

		$this->viewHelperNode = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode'), array('dummy'), array(), '', FALSE);
		$this->viewHelperNode->setRenderingContext($this->renderingContext);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_comparingEqualNumbersReturnsTrue() {
		$expression = '5==5';
		$expected = TRUE;

		$parsedTemplate = $this->templateParser->parse($expression);
		$result = $this->viewHelperNode->_call('evaluateBooleanExpression', $parsedTemplate->getRootNode());
		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_comparingEqualNumbersWithSpacesReturnsTrue() {
		$expression = '   5 ==5';
		$expected = TRUE;

		$parsedTemplate = $this->templateParser->parse($expression);
		$result = $this->viewHelperNode->_call('evaluateBooleanExpression', $parsedTemplate->getRootNode());
		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_comparingEqualObjectsNumbersWithSpacesReturnsTrue() {
		$expression = '{value1} =={value2}';
		$expected = TRUE;
		$this->templateVariableContainer->add('value1', 'Hello everybody');
		$this->templateVariableContainer->add('value2', 'Hello everybody');

		$parsedTemplate = $this->templateParser->parse($expression);
		$result = $this->viewHelperNode->_call('evaluateBooleanExpression', $parsedTemplate->getRootNode());
		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_comparingEqualNumberStoredInVariableWithNumberReturnsTrue() {
		$expression = '{value1} ==42';
		$expected = TRUE;
		$this->templateVariableContainer->add('value1', '42');

		$parsedTemplate = $this->templateParser->parse($expression);
		$result = $this->viewHelperNode->_call('evaluateBooleanExpression', $parsedTemplate->getRootNode());
		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_oddNumberModulo2ReturnsTrue() {
		$expression = '43 % 2';
		$expected = TRUE;

		$parsedTemplate = $this->templateParser->parse($expression);
		$result = $this->viewHelperNode->_call('evaluateBooleanExpression', $parsedTemplate->getRootNode());
		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_evenNumberModulo2ReturnsFalse() {
		$expression = '42 % 2';
		$expected = FALSE;

		$parsedTemplate = $this->templateParser->parse($expression);
		$result = $this->viewHelperNode->_call('evaluateBooleanExpression', $parsedTemplate->getRootNode());
		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_greaterThanReturnsTrueIfNumberIsReallyGreater() {
		$expression = '10 > 9';
		$expected = TRUE;

		$parsedTemplate = $this->templateParser->parse($expression);
		$result = $this->viewHelperNode->_call('evaluateBooleanExpression', $parsedTemplate->getRootNode());
		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_greaterThanReturnsFalseIfNumberIsEqual() {
		$expression = '10 > 10';
		$expected = FALSE;

		$parsedTemplate = $this->templateParser->parse($expression);
		$result = $this->viewHelperNode->_call('evaluateBooleanExpression', $parsedTemplate->getRootNode());
		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_greaterOrEqualsReturnsTrueIfNumberIsReallyGreater() {
		$expression = '10 >= 9';
		$expected = TRUE;

		$parsedTemplate = $this->templateParser->parse($expression);
		$result = $this->viewHelperNode->_call('evaluateBooleanExpression', $parsedTemplate->getRootNode());
		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_greaterOrEqualsReturnsTrueIfNumberIsEqual() {
		$expression = '10 >= 10';
		$expected = TRUE;

		$parsedTemplate = $this->templateParser->parse($expression);
		$result = $this->viewHelperNode->_call('evaluateBooleanExpression', $parsedTemplate->getRootNode());
		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_greaterOrEqualsReturnFalseIfNumberIsSmaller() {
		$expression = '10 >= 11';
		$expected = FALSE;

		$parsedTemplate = $this->templateParser->parse($expression);
		$result = $this->viewHelperNode->_call('evaluateBooleanExpression', $parsedTemplate->getRootNode());
		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_lessThanReturnsTrueIfNumberIsReallyless() {
		$expression = '9 < 10';
		$expected = TRUE;

		$parsedTemplate = $this->templateParser->parse($expression);
		$result = $this->viewHelperNode->_call('evaluateBooleanExpression', $parsedTemplate->getRootNode());
		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_lessThanReturnsFalseIfNumberIsEqual() {
		$expression = '10 < 10';
		$expected = FALSE;

		$parsedTemplate = $this->templateParser->parse($expression);
		$result = $this->viewHelperNode->_call('evaluateBooleanExpression', $parsedTemplate->getRootNode());
		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_lessOrEqualsReturnsTrueIfNumberIsReallyLess() {
		$expression = '9 <= 10';
		$expected = TRUE;

		$parsedTemplate = $this->templateParser->parse($expression);
		$result = $this->viewHelperNode->_call('evaluateBooleanExpression', $parsedTemplate->getRootNode());
		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_lessOrEqualsReturnsTrueIfNumberIsEqual() {
		$expression = '10 <= 10';
		$expected = TRUE;

		$parsedTemplate = $this->templateParser->parse($expression);
		$result = $this->viewHelperNode->_call('evaluateBooleanExpression', $parsedTemplate->getRootNode());
		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_lessOrEqualsReturnFalseIfNumberIsBigger() {
		$expression = '11 <= 10';
		$expected = FALSE;

		$parsedTemplate = $this->templateParser->parse($expression);
		$result = $this->viewHelperNode->_call('evaluateBooleanExpression', $parsedTemplate->getRootNode());
		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_RuntimeException
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_havingMoreThanThreeElementsInTheSyntaxTreeThrowsException() {
		$expression = '   5 ==5 {blubb} {bla} {blu}';
		$expected = TRUE;

		$parsedTemplate = $this->templateParser->parse($expression);
		$result = $this->viewHelperNode->_call('evaluateBooleanExpression', $parsedTemplate->getRootNode());
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_RuntimeException
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_comparingStringsThrowsException() {
		$this->markTestIncomplete('Not sure what the intended behavior should be. See TODO inside ViewHelperNode.');
		$expression = '   blubb ==5 ';
		$expected = TRUE;

		$parsedTemplate = $this->templateParser->parse($expression);
		$result = $this->viewHelperNode->_call('evaluateBooleanExpression', $parsedTemplate->getRootNode());
	}
}

?>