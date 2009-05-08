<?php

/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id$
 */
require_once(t3lib_extMgm::extPath('extbase', 'Tests/Base_testcase.php'));
class Tx_Fluid_ViewHelpers_Format_DateViewHelperTest_testcase extends Tx_Extbase_Base_testcase {

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function test_viewHelperFormatsDateCorrectly() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_DateViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(new DateTime('1980-12-13')));
		$actualResult = $viewHelper->render();
		$this->assertEquals('1980-12-13', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function test_viewHelperFormatsDateStringCorrectly() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_DateViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('1980-12-13'));
		$actualResult = $viewHelper->render();
		$this->assertEquals('1980-12-13', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function test_viewHelperRespectsCustomFormat() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_DateViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(new DateTime('1980-02-01')));
		$actualResult = $viewHelper->render('d.m.Y');
		$this->assertEquals('01.02.1980', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function test_viewHelperReturnsEmptyStringIfNULLIsGiven() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_DateViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(NULL));
		$actualResult = $viewHelper->render();
		$this->assertEquals('', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function test_viewHelperThrowsExceptionIfDateStringCantBeParsed() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_DateViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('foo'));
		try {
			$viewHelper->render();
			$this->fail('render() did not throw an exception although the specified date could not be parsed.');
		} catch (Tx_Fluid_Core_ViewHelperException $exception) {
		}
	}
}
?>
