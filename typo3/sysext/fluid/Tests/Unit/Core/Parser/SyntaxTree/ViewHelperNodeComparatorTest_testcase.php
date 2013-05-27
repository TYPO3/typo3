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
 * Testcase for [insert classname here]
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNodeComparatorTest_testcase extends Tx_Extbase_BaseTestCase {

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

		$this->controllerContext = $this->getMock('Tx_Extbase_MVC_Controller_ControllerContext', array(), array(), '', FALSE);
		$this->renderingContext->setControllerContext($this->controllerContext);

		$this->viewHelperVariableContainer = $this->getMock('Tx_Fluid_Core_ViewHelper_ViewHelperVariableContainer');
		$this->renderingContext->setViewHelperVariableContainer($this->viewHelperVariableContainer);

		$this->renderingConfiguration = $this->getMock('Tx_Fluid_Core_Rendering_RenderingConfiguration');
		$this->renderingContext->setRenderingConfiguration($this->renderingConfiguration);

		$this->templateParser = t3lib_div::makeInstance('Tx_Fluid_Core_Parser_TemplateParser');
		$this->templateParser->injectObjectFactory(new Tx_Fluid_Compatibility_ObjectFactory());

		$this->viewHelperNode = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode'), array('dummy'), array(), '', FALSE);
		$this->viewHelperNode->setRenderingContext($this->renderingContext);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function comparingEqualNumbersReturnsTrue() {
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
	public function comparingEqualNumbersWithSpacesReturnsTrue() {
		$expression = '   5 ==5';
		$expected = TRUE;

		$parsedTemplate = $this->templateParser->parse($expression);
		$result = $this->viewHelperNode->_call('evaluateBooleanExpression', $parsedTemplate->getRootNode());
		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function comparingUnequalNumbersReturnsFals() {
		$expression = '5==3';
		$expected = FALSE;

		$parsedTemplate = $this->templateParser->parse($expression);
		$result = $this->viewHelperNode->_call('evaluateBooleanExpression', $parsedTemplate->getRootNode());
		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function comparingEqualObjectsWithSpacesReturnsTrue() {
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
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function comparingUnequalObjectsWithSpacesReturnsFalse() {
		$expression = '{value1} =={value2}';
		$expected = FALSE;
		$this->templateVariableContainer->add('value1', 'Hello everybody');
		$this->templateVariableContainer->add('value2', 'Hello nobody');

		$parsedTemplate = $this->templateParser->parse($expression);
		$result = $this->viewHelperNode->_call('evaluateBooleanExpression', $parsedTemplate->getRootNode());
		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function comparingEqualNumberStoredInVariableWithNumberReturnsTrue() {
		$expression = '{value1} ==42';
		$expected = TRUE;
		$this->templateVariableContainer->add('value1', '42');

		$parsedTemplate = $this->templateParser->parse($expression);
		$result = $this->viewHelperNode->_call('evaluateBooleanExpression', $parsedTemplate->getRootNode());
		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function comparingUnequalNumberStoredInVariableWithNumberReturnsFalse() {
		$expression = '{value1} ==42';
		$expected = FALSE;
		$this->templateVariableContainer->add('value1', '41');

		$parsedTemplate = $this->templateParser->parse($expression);
		$result = $this->viewHelperNode->_call('evaluateBooleanExpression', $parsedTemplate->getRootNode());
		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function notEqualReturnsFalseIfNumbersAreEqual() {
		$expression = '5!=5';
		$expected = FALSE;

		$parsedTemplate = $this->templateParser->parse($expression);
		$result = $this->viewHelperNode->_call('evaluateBooleanExpression', $parsedTemplate->getRootNode());
		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function notEqualReturnsTrueIfNumbersAreNotEqual() {
		$expression = '5!=3';
		$expected = TRUE;

		$parsedTemplate = $this->templateParser->parse($expression);
		$result = $this->viewHelperNode->_call('evaluateBooleanExpression', $parsedTemplate->getRootNode());
		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function notEqualReturnsFalseForTwoObjectsWithEqualValues() {
		$expression = '{value1} !={value2}';
		$expected = FALSE;
		$this->templateVariableContainer->add('value1', 'Hello everybody');
		$this->templateVariableContainer->add('value2', 'Hello everybody');

		$parsedTemplate = $this->templateParser->parse($expression);
		$result = $this->viewHelperNode->_call('evaluateBooleanExpression', $parsedTemplate->getRootNode());
		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function notEqualReturnsTrueForTwoObjectsWithUnequalValues() {
		$expression = '{value1} !={value2}';
		$expected = TRUE;
		$this->templateVariableContainer->add('value1', 'Hello everybody');
		$this->templateVariableContainer->add('value2', 'Hello nobody');

		$parsedTemplate = $this->templateParser->parse($expression);
		$result = $this->viewHelperNode->_call('evaluateBooleanExpression', $parsedTemplate->getRootNode());
		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function notEqualReturnsFalseForOneObjectAndOneNumberWithEqualValues() {
		$expression = '{value1} !=42';
		$expected = FALSE;
		$this->templateVariableContainer->add('value1', '42');

		$parsedTemplate = $this->templateParser->parse($expression);
		$result = $this->viewHelperNode->_call('evaluateBooleanExpression', $parsedTemplate->getRootNode());
		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function notEqualReturnsTrueForOneObjectAndOneNumberWithUnequalValues() {
		$expression = '{value1} !=42';
		$expected = TRUE;
		$this->templateVariableContainer->add('value1', '41');

		$parsedTemplate = $this->templateParser->parse($expression);
		$result = $this->viewHelperNode->_call('evaluateBooleanExpression', $parsedTemplate->getRootNode());
		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function oddNumberModulo2ReturnsTrue() {
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
	public function evenNumberModulo2ReturnsFalse() {
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
	public function greaterThanReturnsTrueIfNumberIsReallyGreater() {
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
	public function greaterThanReturnsFalseIfNumberIsEqual() {
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
	public function greaterOrEqualsReturnsTrueIfNumberIsReallyGreater() {
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
	public function greaterOrEqualsReturnsTrueIfNumberIsEqual() {
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
	public function greaterOrEqualsReturnFalseIfNumberIsSmaller() {
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
	public function lessThanReturnsTrueIfNumberIsReallyless() {
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
	public function lessThanReturnsFalseIfNumberIsEqual() {
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
	public function lessOrEqualsReturnsTrueIfNumberIsReallyLess() {
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
	public function lessOrEqualsReturnsTrueIfNumberIsEqual() {
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
	public function lessOrEqualsReturnFalseIfNumberIsBigger() {
		$expression = '11 <= 10';
		$expected = FALSE;

		$parsedTemplate = $this->templateParser->parse($expression);
		$result = $this->viewHelperNode->_call('evaluateBooleanExpression', $parsedTemplate->getRootNode());
		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 * @expectedException \RuntimeException
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function havingMoreThanThreeElementsInTheSyntaxTreeThrowsException() {
		$expression = '   5 ==5 {blubb} {bla} {blu}';

		$parsedTemplate = $this->templateParser->parse($expression);
		$this->viewHelperNode->_call('evaluateBooleanExpression', $parsedTemplate->getRootNode());
	}

	/**
	 * @test
	 * @expectedException \RuntimeException
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function comparingStringsThrowsException() {
		$this->markTestIncomplete('Not sure what the intended behavior should be. See TODO inside ViewHelperNode.');
		$expression = '   blubb ==5 ';

		$parsedTemplate = $this->templateParser->parse($expression);
		$this->viewHelperNode->_call('evaluateBooleanExpression', $parsedTemplate->getRootNode());
	}
}

?>