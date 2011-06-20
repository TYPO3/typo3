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

require_once(dirname(__FILE__) . '/../../ViewHelpers/ViewHelperBaseTestcase.php');

/**
 * Testcase for Condition ViewHelper
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Fluid_Tests_Unit_Core_ViewHelper_AbstractConditionViewHelperTest extends Tx_Fluid_ViewHelpers_ViewHelperBaseTestcase {

	/**
	 * var Tx_Fluid_Core_ViewHelper_AbstractConditionViewHelper
	 */
	protected $viewHelper;

	/**
	 * var Tx_Fluid_Core_ViewHelper_Arguments
	 */
	protected $mockArguments;

	public function setUp() {
		parent::setUp();
		$this->viewHelper = $this->getAccessibleMock('Tx_Fluid_Core_ViewHelper_AbstractConditionViewHelper', array('getRenderingContext', 'renderChildren'));
		$this->viewHelper->expects($this->any())->method('getRenderingContext')->will($this->returnValue($this->renderingContext));
		$this->injectDependenciesIntoViewHelper($this->viewHelper);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function renderThenChildReturnsAllChildrenIfNoThenViewHelperChildExists() {
		$this->viewHelper->expects($this->at(0))->method('renderChildren')->will($this->returnValue('foo'));

		$actualResult = $this->viewHelper->_call('renderThenChild');
		$this->assertEquals('foo', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function renderThenChildReturnsThenViewHelperChildIfConditionIsTrueAndThenViewHelperChildExists() {
		$mockThenViewHelperNode = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode', array('getViewHelperClassName', 'evaluate'), array(), '', FALSE);
		$mockThenViewHelperNode->expects($this->at(0))->method('getViewHelperClassName')->will($this->returnValue('Tx_Fluid_ViewHelpers_ThenViewHelper'));
		$mockThenViewHelperNode->expects($this->at(1))->method('evaluate')->with($this->renderingContext)->will($this->returnValue('ThenViewHelperResults'));

		$this->viewHelper->setChildNodes(array($mockThenViewHelperNode));
		$actualResult = $this->viewHelper->_call('renderThenChild');
		$this->assertEquals('ThenViewHelperResults', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderElseChildReturnsEmptyStringIfConditionIsFalseAndNoElseViewHelperChildExists() {
		$actualResult = $this->viewHelper->_call('renderElseChild');
		$this->assertEquals('', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderElseChildRendersElseViewHelperChildIfConditionIsFalseAndNoThenViewHelperChildExists() {
		$mockElseViewHelperNode = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode', array('getViewHelperClassName', 'evaluate', 'setRenderingContext'), array(), '', FALSE);
		$mockElseViewHelperNode->expects($this->at(0))->method('getViewHelperClassName')->will($this->returnValue('Tx_Fluid_ViewHelpers_ElseViewHelper'));
		$mockElseViewHelperNode->expects($this->at(1))->method('evaluate')->with($this->renderingContext)->will($this->returnValue('ElseViewHelperResults'));

		$this->viewHelper->setChildNodes(array($mockElseViewHelperNode));
		$actualResult = $this->viewHelper->_call('renderElseChild');
		$this->assertEquals('ElseViewHelperResults', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderThenChildReturnsValueOfThenArgumentIfConditionIsTrue() {
		$this->arguments->expects($this->atLeastOnce())->method('hasArgument')->with('then')->will($this->returnValue(TRUE));
		$this->arguments->expects($this->atLeastOnce())->method('offsetGet')->with('then')->will($this->returnValue('ThenArgument'));

		$actualResult = $this->viewHelper->_call('renderThenChild');
		$this->assertEquals('ThenArgument', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderThenChildReturnsEmptyStringIfChildNodesOnlyContainElseViewHelper() {
		$mockElseViewHelperNode = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode', array('getViewHelperClassName', 'evaluate'), array(), '', FALSE);
		$mockElseViewHelperNode->expects($this->any())->method('getViewHelperClassName')->will($this->returnValue('Tx_Fluid_ViewHelpers_ElseViewHelper'));
		$this->viewHelper->setChildNodes(array($mockElseViewHelperNode));
		$this->viewHelper->expects($this->never())->method('renderChildren')->will($this->returnValue('Child nodes'));

		$actualResult = $this->viewHelper->_call('renderThenChild');
		$this->assertEquals('', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function thenArgumentHasPriorityOverChildNodesIfConditionIsTrue() {
		$mockRenderingContext = $this->getMock('Tx_Fluid_Core_Rendering_RenderingContextInterface');

		$mockThenViewHelperNode = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode', array('getViewHelperClassName', 'evaluate', 'setRenderingContext'), array(), '', FALSE);
		$mockThenViewHelperNode->expects($this->never())->method('evaluate');

		$this->viewHelper->setChildNodes(array($mockThenViewHelperNode));
		$this->viewHelper->setRenderingContext($mockRenderingContext);

		$this->arguments->expects($this->atLeastOnce())->method('hasArgument')->with('then')->will($this->returnValue(TRUE));
		$this->arguments->expects($this->atLeastOnce())->method('offsetGet')->with('then')->will($this->returnValue('ThenArgument'));

		$actualResult = $this->viewHelper->_call('renderThenChild');
		$this->assertEquals('ThenArgument', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderReturnsValueOfElseArgumentIfConditionIsFalse() {
		$this->arguments->expects($this->atLeastOnce())->method('hasArgument')->with('else')->will($this->returnValue(TRUE));
		$this->arguments->expects($this->atLeastOnce())->method('offsetGet')->with('else')->will($this->returnValue('ElseArgument'));

		$actualResult = $this->viewHelper->_call('renderElseChild');
		$this->assertEquals('ElseArgument', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function elseArgumentHasPriorityOverChildNodesIfConditionIsFalse() {
		$mockRenderingContext = $this->getMock('Tx_Fluid_Core_Rendering_RenderingContextInterface');

		$mockElseViewHelperNode = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode', array('getViewHelperClassName', 'evaluate', 'setRenderingContext'), array(), '', FALSE);
		$mockElseViewHelperNode->expects($this->any())->method('getViewHelperClassName')->will($this->returnValue('Tx_Fluid_ViewHelpers_ElseViewHelper'));
		$mockElseViewHelperNode->expects($this->never())->method('evaluate');

		$this->viewHelper->setChildNodes(array($mockElseViewHelperNode));
		$this->viewHelper->setRenderingContext($mockRenderingContext);

		$this->arguments->expects($this->atLeastOnce())->method('hasArgument')->with('else')->will($this->returnValue(TRUE));
		$this->arguments->expects($this->atLeastOnce())->method('offsetGet')->with('else')->will($this->returnValue('ElseArgument'));

		$actualResult = $this->viewHelper->_call('renderElseChild');
		$this->assertEquals('ElseArgument', $actualResult);
	}

}

?>