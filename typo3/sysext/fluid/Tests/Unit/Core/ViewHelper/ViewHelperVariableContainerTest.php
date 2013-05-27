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

require_once(dirname(__FILE__) . '/../Fixtures/TestViewHelper.php');

/**
 * Testcase for AbstractViewHelper
 *
 * @version $Id: ViewHelperVariableContainerTest.php 4483 2010-06-10 13:57:32Z k-fish $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Fluid_Core_ViewHelper_ViewHelperVariableContainerTest extends Tx_Extbase_BaseTestCase {

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
	 * @expectedException Tx_Fluid_Core_ViewHelper_Exception_InvalidVariableException
	 */
	public function gettingNonNonExistentValueThrowsException() {
		$this->viewHelperVariableContainer->get('Tx_Fluid_ViewHelper_NonExistent', 'nonExistentKey');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @expectedException Tx_Fluid_Core_ViewHelper_Exception_InvalidVariableException
	 */
	public function settingKeyWhichIsAlreadyStoredThrowsException() {
		$this->viewHelperVariableContainer->add('Tx_Fluid_ViewHelper_NonExistent', 'nonExistentKey', 'value1');
		$this->viewHelperVariableContainer->add('Tx_Fluid_ViewHelper_NonExistent', 'nonExistentKey', 'value2');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function addOrUpdateWorks() {
		$this->viewHelperVariableContainer->add('Tx_Fluid_ViewHelper_NonExistent', 'nonExistentKey', 'value1');
		$this->viewHelperVariableContainer->addOrUpdate('Tx_Fluid_ViewHelper_NonExistent', 'nonExistentKey', 'value2');
		$this->assertEquals($this->viewHelperVariableContainer->get('Tx_Fluid_ViewHelper_NonExistent', 'nonExistentKey'), 'value2');
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
	 * @expectedException Tx_Fluid_Core_ViewHelper_Exception_InvalidVariableException
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