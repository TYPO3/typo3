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
 * Testcase for CycleViewHelper
 *
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */

require_once(dirname(__FILE__) . '/ViewHelperBaseTestcase.php');
require_once(t3lib_extMgm::extPath('extbase', 'Tests/Base_testcase.php'));
class Tx_Fluid_ViewHelpers_CycleViewHelperTest_testcase extends Tx_Fluid_ViewHelpers_ViewHelperBaseTestcase {

	/**
	 * var Tx_Fluid_ViewHelpers_CycleViewHelper
	 */
	protected $viewHelper;

	public function setUp() {
		parent::setUp();
		$this->viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_CycleViewHelper', array('renderChildren'));
		$this->injectDependenciesIntoViewHelper($this->viewHelper);
		$this->viewHelper->initializeArguments();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderAddsCurrentValueToTemplateVariableContainerAndRemovesItAfterRendering() {
		$this->templateVariableContainer->expects($this->at(0))->method('add')->with('innerVariable', 'bar');
		$this->templateVariableContainer->expects($this->at(1))->method('remove')->with('innerVariable');

		$values = array('bar', 'Fluid');
		$this->viewHelper->render($values, 'innerVariable');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderAddsFirstValueToTemplateVariableContainerAfterLastValue() {
		$this->templateVariableContainer->expects($this->at(0))->method('add')->with('innerVariable', 'bar');
		$this->templateVariableContainer->expects($this->at(1))->method('remove')->with('innerVariable');
		$this->templateVariableContainer->expects($this->at(2))->method('add')->with('innerVariable', 'Fluid');
		$this->templateVariableContainer->expects($this->at(3))->method('remove')->with('innerVariable');
		$this->templateVariableContainer->expects($this->at(4))->method('add')->with('innerVariable', 'bar');
		$this->templateVariableContainer->expects($this->at(5))->method('remove')->with('innerVariable');

		$values = array('bar', 'Fluid');
		$this->viewHelper->render($values, 'innerVariable');
		$this->viewHelper->render($values, 'innerVariable');
		$this->viewHelper->render($values, 'innerVariable');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function viewHelperSupportsAssociativeArrays() {
		$this->templateVariableContainer->expects($this->at(0))->method('add')->with('innerVariable', 'FLOW3');
		$this->templateVariableContainer->expects($this->at(1))->method('remove')->with('innerVariable');
		$this->templateVariableContainer->expects($this->at(2))->method('add')->with('innerVariable', 'Fluid');
		$this->templateVariableContainer->expects($this->at(3))->method('remove')->with('innerVariable');
		$this->templateVariableContainer->expects($this->at(4))->method('add')->with('innerVariable', 'FLOW3');
		$this->templateVariableContainer->expects($this->at(5))->method('remove')->with('innerVariable');

		$values = array('foo' => 'FLOW3', 'bar' => 'Fluid');
		$this->viewHelper->render($values, 'innerVariable');
		$this->viewHelper->render($values, 'innerVariable');
		$this->viewHelper->render($values, 'innerVariable');
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_ViewHelper_Exception
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderThrowsExceptionWhenPassingObjectsToValuesThatAreNotTraversable() {
		$object = new stdClass();

		$this->viewHelper->render($object, 'innerVariable');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderReturnsChildNodesIfValuesIsNull() {
		$this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('Child nodes'));

		$this->assertEquals('Child nodes', $this->viewHelper->render(NULL, 'foo'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderReturnsChildNodesIfValuesIsAnEmptyArray() {
		$this->templateVariableContainer->expects($this->at(0))->method('add')->with('foo', NULL);
		$this->templateVariableContainer->expects($this->at(1))->method('remove')->with('foo');

		$this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('Child nodes'));

		$this->assertEquals('Child nodes', $this->viewHelper->render(array(), 'foo'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderIteratesThroughElementsOfTraversableObjects() {
		$this->templateVariableContainer->expects($this->at(0))->method('add')->with('innerVariable', 'value1');
		$this->templateVariableContainer->expects($this->at(1))->method('remove')->with('innerVariable');
		$this->templateVariableContainer->expects($this->at(2))->method('add')->with('innerVariable', 'value2');
		$this->templateVariableContainer->expects($this->at(3))->method('remove')->with('innerVariable');
		$this->templateVariableContainer->expects($this->at(4))->method('add')->with('innerVariable', 'value1');
		$this->templateVariableContainer->expects($this->at(5))->method('remove')->with('innerVariable');

		$traversableObject = new ArrayObject(array('key1' => 'value1', 'key2' => 'value2'));
		$this->viewHelper->render($traversableObject, 'innerVariable');
		$this->viewHelper->render($traversableObject, 'innerVariable');
		$this->viewHelper->render($traversableObject, 'innerVariable');
	}
}

?>
