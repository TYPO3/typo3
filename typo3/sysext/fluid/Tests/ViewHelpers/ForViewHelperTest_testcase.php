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
 * @package Fluid
 * @subpackage Tests
 * @version $Id$
 */
/**
 * Testcase for ForViewHelper
 *
 * @package Fluid
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */

include_once(dirname(__FILE__) . '/Fixtures/ConstraintSyntaxTreeNode.php');
require_once(dirname(__FILE__) . '/ViewHelperBaseTestcase.php');
require_once(t3lib_extMgm::extPath('extbase', 'Tests/Base_testcase.php'));
class Tx_Fluid_ViewHelpers_ForViewHelperTest_testcase extends Tx_Fluid_ViewHelpers_ViewHelperBaseTestcase {

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderExecutesTheLoopCorrectly() {
		$viewHelper = new Tx_Fluid_ViewHelpers_ForViewHelper();

		$variableContainer = new Tx_Fluid_Core_ViewHelper_TemplateVariableContainer(array());

		$viewHelperNode = new Tx_Fluid_ViewHelpers_Fixtures_ConstraintSyntaxTreeNode($variableContainer);		
		$viewHelper->setTemplateVariableContainer($variableContainer);
		$viewHelper->setViewHelperNode($viewHelperNode);
		$viewHelper->render(array(0,1,2,3), 'innerVariable');

		$expectedCallProtocol = array(
			array('innerVariable' => 0),
			array('innerVariable' => 1),
			array('innerVariable' => 2),
			array('innerVariable' => 3)
		);
		$this->assertEquals($expectedCallProtocol, $viewHelperNode->callProtocol, 'The call protocol differs -> The for loop does not work as it should!');	
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderReturnsEmptyStringIfObjectIsNull() {
		$viewHelper = new Tx_Fluid_ViewHelpers_ForViewHelper();

		$this->assertEquals('', $viewHelper->render(NULL, 'foo'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderReturnsEmptyStringIfObjectIsEmtyArray() {
		$viewHelper = new Tx_Fluid_ViewHelpers_ForViewHelper();

		$this->assertEquals('', $viewHelper->render(array(), 'foo'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderAddsCurrentValueToTemplateVariableContainerAndRemovesItAfterRendering() {
		$viewHelper = new Tx_Fluid_ViewHelpers_ForViewHelper();

		$mockViewHelperNode = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode', array('evaluateChildNodes'), array(), '', FALSE);
		$mockViewHelperNode->expects($this->any())->method('evaluateChildNodes')->will($this->returnValue('foo'));

		$this->templateVariableContainer->expects($this->at(0))->method('add')->with('innerVariable', 'bar');
		$this->templateVariableContainer->expects($this->at(1))->method('remove')->with('innerVariable');
		$this->templateVariableContainer->expects($this->at(2))->method('add')->with('innerVariable', 'Fluid');
		$this->templateVariableContainer->expects($this->at(3))->method('remove')->with('innerVariable');

		$viewHelper->setTemplateVariableContainer($this->templateVariableContainer);
		$viewHelper->setViewHelperNode($mockViewHelperNode);
		$viewHelper->render(array('foo' => 'bar', 'FLOW3' => 'Fluid'), 'innerVariable');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderAddsCurrentKeyToTemplateVariableContainerAndRemovesItAfterRendering() {
		$viewHelper = new Tx_Fluid_ViewHelpers_ForViewHelper();

		$mockViewHelperNode = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode', array('evaluateChildNodes'), array(), '', FALSE);
		$mockViewHelperNode->expects($this->any())->method('evaluateChildNodes')->will($this->returnValue('foo'));

		$this->templateVariableContainer->expects($this->at(0))->method('add')->with('innerVariable', 'bar');
		$this->templateVariableContainer->expects($this->at(1))->method('add')->with('someKey', 'foo');
		$this->templateVariableContainer->expects($this->at(2))->method('remove')->with('innerVariable');
		$this->templateVariableContainer->expects($this->at(3))->method('remove')->with('someKey');
		$this->templateVariableContainer->expects($this->at(4))->method('add')->with('innerVariable', 'Fluid');
		$this->templateVariableContainer->expects($this->at(5))->method('add')->with('someKey', 'FLOW3');
		$this->templateVariableContainer->expects($this->at(6))->method('remove')->with('innerVariable');
		$this->templateVariableContainer->expects($this->at(7))->method('remove')->with('someKey');

		$viewHelper->setTemplateVariableContainer($this->templateVariableContainer);
		$viewHelper->setViewHelperNode($mockViewHelperNode);
		$viewHelper->render(array('foo' => 'bar', 'FLOW3' => 'Fluid'), 'innerVariable', 'someKey');
	}
}



?>
