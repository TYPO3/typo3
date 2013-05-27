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
 * Testcase for Interceptor\Escape
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Fluid_Tests_Unit_Core_Parser_Interceptor_EscapeTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @var Tx_Fluid_Core_Parser_Interceptor_Escape
	 */
	protected $escapeInterceptor;

	/**
	 * @var Tx_Fluid_Core_ViewHelper_AbstractViewHelper
	 */
	protected $mockViewHelper;

	/**
	 * @var Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode
	 */
	protected $mockNode;

	/**
	 * @var Tx_Fluid_Core_Parser_ParsingState
	 */
	protected $mockParsingState;

	public function setUp() {
		$this->escapeInterceptor = $this->getAccessibleMock('Tx_Fluid_Core_Parser_Interceptor_Escape', array('dummy'));
		$this->mockViewHelper = $this->getMock('Tx_Fluid_Core_ViewHelper_AbstractViewHelper');
		$this->mockNode = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode', array(), array(), '', FALSE);
		$this->mockParsingState = $this->getMock('Tx_Fluid_Core_Parser_ParsingState');
	}

	/**
	 * @test
	 */
	public function processDoesNotDisableEscapingInterceptorByDefault() {
		$interceptorPosition = Tx_Fluid_Core_Parser_InterceptorInterface::INTERCEPT_OPENING_VIEWHELPER;
		$this->mockViewHelper->expects($this->once())->method('isEscapingInterceptorEnabled')->will($this->returnValue(TRUE));
		$this->mockNode->expects($this->once())->method('getUninitializedViewHelper')->will($this->returnValue($this->mockViewHelper));

		$this->assertTrue($this->escapeInterceptor->_get('interceptorEnabled'));
		$this->escapeInterceptor->process($this->mockNode, $interceptorPosition, $this->mockParsingState);
		$this->assertTrue($this->escapeInterceptor->_get('interceptorEnabled'));
	}

	/**
	 * @test
	 */
	public function processDisablesEscapingInterceptorIfViewHelperDisablesIt() {
		$interceptorPosition = Tx_Fluid_Core_Parser_InterceptorInterface::INTERCEPT_OPENING_VIEWHELPER;
		$this->mockViewHelper->expects($this->once())->method('isEscapingInterceptorEnabled')->will($this->returnValue(FALSE));
		$this->mockNode->expects($this->once())->method('getUninitializedViewHelper')->will($this->returnValue($this->mockViewHelper));

		$this->assertTrue($this->escapeInterceptor->_get('interceptorEnabled'));
		$this->escapeInterceptor->process($this->mockNode, $interceptorPosition, $this->mockParsingState);
		$this->assertFalse($this->escapeInterceptor->_get('interceptorEnabled'));
	}

	/**
	 * @test
	 */
	public function processReenablesEscapingInterceptorOnClosingViewHelperTagIfItWasDisabledBefore() {
		$interceptorPosition = Tx_Fluid_Core_Parser_InterceptorInterface::INTERCEPT_CLOSING_VIEWHELPER;

		$this->escapeInterceptor->_set('interceptorEnabled', FALSE);
		$this->escapeInterceptor->_set('viewHelperNodesWhichDisableTheInterceptor', array($this->mockNode));

		$this->escapeInterceptor->process($this->mockNode, $interceptorPosition, $this->mockParsingState);
		$this->assertTrue($this->escapeInterceptor->_get('interceptorEnabled'));
	}

		/**
	 * @test
	 */
	public function processWrapsCurrentViewHelperInHtmlentitiesViewHelperOnObjectAccessor() {
		$interceptorPosition = Tx_Fluid_Core_Parser_InterceptorInterface::INTERCEPT_OBJECTACCESSOR;
		$mockNode = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_ObjectAccessorNode', array(), array(), '', FALSE);
		$mockEscapeViewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_HtmlspecialcharsViewHelper');
		$mockObjectManager = $this->getMock('Tx_Extbase_Object_ObjectManagerInterface');
		$mockObjectManager->expects($this->at(0))->method('get')->with('Tx_Fluid_ViewHelpers_Format_HtmlspecialcharsViewHelper')->will($this->returnValue($mockEscapeViewHelper));
		$mockObjectManager->expects($this->at(1))->method('create')->with('Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode', $mockEscapeViewHelper, array('value' => $mockNode))->will($this->returnValue($this->mockNode));
		$this->escapeInterceptor->injectObjectManager($mockObjectManager);

		$actualResult = $this->escapeInterceptor->process($mockNode, $interceptorPosition, $this->mockParsingState);
		$this->assertSame($this->mockNode, $actualResult);
	}

}

?>