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

require_once(dirname(__FILE__) . '/../Fixtures/TestViewHelper.php');

/**
 * Testcase for AbstractViewHelper
 *
 */
class Tx_Fluid_Tests_Unit_Core_ViewHelper_ViewHelperVariableContainerTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 *
	 * @var Tx_Fluid_Core_ViewHelper_ViewHelperVariableContainer
	 */
	protected $viewHelperVariableContainer;

	protected function setUp() {
		$this->viewHelperVariableContainer = new Tx_Fluid_Core_ViewHelper_ViewHelperVariableContainer();
	}
	/**
	 * @test
	 */
	public function storedDataCanBeReadOutAgain() {
		$variable = 'Hello world';
		$this->assertFalse($this->viewHelperVariableContainer->exists('Tx_Fluid_ViewHelpers_TestViewHelper', 'test'));
		$this->viewHelperVariableContainer->add('Tx_Fluid_ViewHelpers_TestViewHelper', 'test', $variable);
		$this->assertTrue($this->viewHelperVariableContainer->exists('Tx_Fluid_ViewHelpers_TestViewHelper', 'test'));

		$this->assertEquals($variable, $this->viewHelperVariableContainer->get('Tx_Fluid_ViewHelpers_TestViewHelper', 'test'));
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_ViewHelper_Exception_InvalidVariableException
	 */
	public function gettingNonNonExistentValueThrowsException() {
		$this->viewHelperVariableContainer->get('Tx_Fluid_ViewHelper_NonExistent', 'nonExistentKey');
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_ViewHelper_Exception_InvalidVariableException
	 */
	public function settingKeyWhichIsAlreadyStoredThrowsException() {
		$this->viewHelperVariableContainer->add('Tx_Fluid_ViewHelper_NonExistent', 'nonExistentKey', 'value1');
		$this->viewHelperVariableContainer->add('Tx_Fluid_ViewHelper_NonExistent', 'nonExistentKey', 'value2');
	}

	/**
	 * @test
	 */
	public function addOrUpdateWorks() {
		$this->viewHelperVariableContainer->add('Tx_Fluid_ViewHelper_NonExistent', 'nonExistentKey', 'value1');
		$this->viewHelperVariableContainer->addOrUpdate('Tx_Fluid_ViewHelper_NonExistent', 'nonExistentKey', 'value2');
		$this->assertEquals($this->viewHelperVariableContainer->get('Tx_Fluid_ViewHelper_NonExistent', 'nonExistentKey'), 'value2');
	}

	/**
	 * @test
	 */
	public function aSetValueCanBeRemovedAgain() {
		$this->viewHelperVariableContainer->add('Tx_Fluid_ViewHelper_NonExistent', 'nonExistentKey', 'value1');
		$this->viewHelperVariableContainer->remove('Tx_Fluid_ViewHelper_NonExistent', 'nonExistentKey');
		$this->assertFalse($this->viewHelperVariableContainer->exists('Tx_Fluid_ViewHelper_NonExistent', 'nonExistentKey'));
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_ViewHelper_Exception_InvalidVariableException
	 */
	public function removingNonExistentKeyThrowsException() {
		$this->viewHelperVariableContainer->remove('Tx_Fluid_ViewHelper_NonExistent', 'nonExistentKey');
	}

	/**
	 * @test
	 */
	public function viewCanBeReadOutAgain() {
		$view = $this->getMock('Tx_Fluid_View_AbstractTemplateView', array('getTemplateSource', 'getLayoutSource', 'getPartialSource', 'hasTemplate', 'canRender', 'getTemplateIdentifier', 'getLayoutIdentifier', 'getPartialIdentifier'));
		$this->viewHelperVariableContainer->setView($view);
		$this->assertSame($view, $this->viewHelperVariableContainer->getView());
	}
}
?>