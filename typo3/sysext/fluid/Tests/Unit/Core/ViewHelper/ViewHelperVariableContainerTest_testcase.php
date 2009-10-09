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

include_once(dirname(__FILE__) . '/../Fixtures/TestViewHelper.php');

/**
 * Testcase for AbstractViewHelper
 *
 * @version $Id: ViewHelperVariableContainerTest.php 2813 2009-07-16 14:02:34Z k-fish $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
require_once(t3lib_extMgm::extPath('extbase', 'Tests/Base_testcase.php'));
class Tx_Fluid_Core_ViewHelper_ViewHelperVariableContainerTest_testcase extends Tx_Extbase_Base_testcase {

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
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
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
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @expectedException Tx_Fluid_Core_RuntimeException
	 */
	public function gettingNonNonExistentValueThrowsException() {
		$this->viewHelperVariableContainer->get('Tx_Fluid_ViewHelper_NonExistent', 'nonExistentKey');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @expectedException Tx_Fluid_Core_RuntimeException
	 */
	public function settingKeyWhichIsAlreadyStoredThrowsException() {
		$this->viewHelperVariableContainer->add('Tx_Fluid_ViewHelper_NonExistent', 'nonExistentKey', 'value1');
		$this->viewHelperVariableContainer->add('Tx_Fluid_ViewHelper_NonExistent', 'nonExistentKey', 'value2');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function aSetValueCanBeRemovedAgain() {
		$this->viewHelperVariableContainer->add('Tx_Fluid_ViewHelper_NonExistent', 'nonExistentKey', 'value1');
		$this->viewHelperVariableContainer->remove('Tx_Fluid_ViewHelper_NonExistent', 'nonExistentKey');
		$this->assertFalse($this->viewHelperVariableContainer->exists('Tx_Fluid_ViewHelper_NonExistent', 'nonExistentKey'));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @expectedException Tx_Fluid_Core_RuntimeException
	 */
	public function removingNonExistentKeyThrowsException() {
		$this->viewHelperVariableContainer->remove('Tx_Fluid_ViewHelper_NonExistent', 'nonExistentKey');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function viewCanBeReadOutAgain() {
		$view = $this->getMock('Tx_Extbase_MVC_View_ViewInterface');
		$this->viewHelperVariableContainer->setView($view);
		$this->assertSame($view, $this->viewHelperVariableContainer->getView());
	}
}
?>