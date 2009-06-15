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
	public function viewHelperRendersChildrenIfConditionIsTrueAndNoThenViewHelperChildExists() {
		$mockViewHelper = $this->getMock('Tx_Fluid_ViewHelpers_IfViewHelper', array('renderChildren'));
		$mockViewHelper->expects($this->at(0))->method('renderChildren')->will($this->returnValue('foo'));

		$actualResult = $mockViewHelper->render(TRUE);
		$this->assertEquals('foo', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function viewHelperRendersThenViewHelperChildIfConditionIsTrueAndThenViewHelperChildExists() {
		$renderingContext = $this->getMock('Tx_Fluid_Core_Rendering_RenderingContext');

		$mockThenViewHelperNode = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode', array('getViewHelperClassName', 'evaluate', 'setRenderingContext'), array(), '', FALSE);
		$mockThenViewHelperNode->expects($this->at(0))->method('getViewHelperClassName')->will($this->returnValue('Tx_Fluid_ViewHelpers_ThenViewHelper'));
		$mockThenViewHelperNode->expects($this->at(1))->method('setRenderingContext')->with($renderingContext);
		$mockThenViewHelperNode->expects($this->at(2))->method('evaluate')->will($this->returnValue('ThenViewHelperResults'));

		$viewHelper = new Tx_Fluid_ViewHelpers_IfViewHelper();
		$viewHelper->setChildNodes(array($mockThenViewHelperNode));
		$viewHelper->setRenderingContext($renderingContext);
		$actualResult = $viewHelper->render(TRUE);
		$this->assertEquals('ThenViewHelperResults', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderReturnsEmptyStringIfConditionIsFalseAndNoThenViewHelperChildExists() {
		$viewHelper = new Tx_Fluid_ViewHelpers_IfViewHelper();

		$actualResult = $viewHelper->render(FALSE);
		$this->assertEquals('', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function viewHelperRendersElseViewHelperChildIfConditionIsFalseAndNoThenViewHelperChildExists() {
		$renderingContext = $this->getMock('Tx_Fluid_Core_Rendering_RenderingContext');

		$mockElseViewHelperNode = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode', array('getViewHelperClassName', 'evaluate', 'setRenderingContext'), array(), '', FALSE);
		$mockElseViewHelperNode->expects($this->at(0))->method('getViewHelperClassName')->will($this->returnValue('Tx_Fluid_ViewHelpers_ElseViewHelper'));
		$mockElseViewHelperNode->expects($this->at(1))->method('setRenderingContext')->with($renderingContext);
		$mockElseViewHelperNode->expects($this->at(2))->method('evaluate')->will($this->returnValue('ElseViewHelperResults'));

		$viewHelper = new Tx_Fluid_ViewHelpers_IfViewHelper();
		$viewHelper->setChildNodes(array($mockElseViewHelperNode));
		$viewHelper->setRenderingContext($this->getMock('Tx_Fluid_Core_Rendering_RenderingContext'));

		$actualResult = $viewHelper->render(FALSE);
		$this->assertEquals('ElseViewHelperResults', $actualResult);
	}
}

?>
