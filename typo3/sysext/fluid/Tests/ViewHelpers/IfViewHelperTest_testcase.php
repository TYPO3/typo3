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
 * @version $Id$
 */
/**
 * Testcase for [insert classname here]
 *
 * @package
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
require_once(t3lib_extMgm::extPath('extbase', 'Tests/Base_testcase.php'));
class Tx_Fluid_ViewHelpers_IfViewHelperTest_testcase extends Tx_Extbase_Base_testcase {

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function test_viewHelperRendersChildrenIfConditionIsTrueAndNoThenViewHelperChildExists() {
		$mockViewHelper = $this->getMock('Tx_Fluid_ViewHelpers_IfViewHelper', array('renderChildren'));
		$mockViewHelper->expects($this->at(0))->method('renderChildren')->will($this->returnValue('foo'));

		$actualResult = $mockViewHelper->render(TRUE);
		$this->assertEquals('foo', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function test_viewHelperRendersThenViewHelperChildIfConditionIsTrueAndThenViewHelperChildExists() {
		$mockVariableContainer = $this->getMock('Tx_Fluid_Core_VariableContainer');

		$mockThenViewHelperNode = $this->getMock('Tx_Fluid_Core_SyntaxTree_ViewHelperNode', array('getViewHelperClassName', 'evaluate', 'setVariableContainer', 'render'), array(), '', FALSE);
		$mockThenViewHelperNode->expects($this->at(0))->method('getViewHelperClassName')->will($this->returnValue('Tx_Fluid_ViewHelpers_ThenViewHelper'));
		$mockThenViewHelperNode->expects($this->at(1))->method('setVariableContainer')->with($mockVariableContainer);
		$mockThenViewHelperNode->expects($this->at(2))->method('render')->will($this->returnValue('ThenViewHelperResults'));

		$viewHelper = new Tx_Fluid_ViewHelpers_IfViewHelper();
		$viewHelper->setVariableContainer($mockVariableContainer);
		$viewHelper->setChildNodes(array($mockThenViewHelperNode));

		$actualResult = $viewHelper->render(TRUE);
		$this->assertEquals('ThenViewHelperResults', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function test_renderReturnsEmptyStringIfConditionIsFalseAndNoThenViewHelperChildExists() {
		$viewHelper = new Tx_Fluid_ViewHelpers_IfViewHelper();

		$actualResult = $viewHelper->render(FALSE);
		$this->assertEquals('', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function test_viewHelperRendersElseViewHelperChildIfConditionIsFalseAndNoThenViewHelperChildExists() {
		$mockVariableContainer = $this->getMock('Tx_Fluid_Core_VariableContainer');

		$mockElseViewHelperNode = $this->getMock('Tx_Fluid_Core_SyntaxTree_ViewHelperNode', array('getViewHelperClassName', 'evaluate', 'setVariableContainer', 'render'), array(), '', FALSE);
		$mockElseViewHelperNode->expects($this->at(0))->method('getViewHelperClassName')->will($this->returnValue('Tx_Fluid_ViewHelpers_ElseViewHelper'));
		$mockElseViewHelperNode->expects($this->at(1))->method('setVariableContainer')->with($mockVariableContainer);
		$mockElseViewHelperNode->expects($this->at(2))->method('render')->will($this->returnValue('ElseViewHelperResults'));

		$viewHelper = new Tx_Fluid_ViewHelpers_IfViewHelper();
		$viewHelper->setVariableContainer($mockVariableContainer);
		$viewHelper->setChildNodes(array($mockElseViewHelperNode));

		$actualResult = $viewHelper->render(FALSE);
		$this->assertEquals('ElseViewHelperResults', $actualResult);
	}
}

?>
