<?php

/*                                                                        *
 * This script is backported from the FLOW3 package "TYPO3.Fluid".        *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 */
class Tx_Fluid_Tests_Unit_ViewHelpers_Format_DateViewHelperTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @test
	 */
	public function viewHelperFormatsDateCorrectly() {
		$viewHelper = new Tx_Fluid_ViewHelpers_Format_DateViewHelper();
		$actualResult = $viewHelper->render(new DateTime('1980-12-13'));
		$this->assertEquals('1980-12-13', $actualResult);
	}

	/**
	 * @test
	 */
	public function viewHelperFormatsDateStringCorrectly() {
		$viewHelper = new Tx_Fluid_ViewHelpers_Format_DateViewHelper();
		$actualResult = $viewHelper->render('1980-12-13');
		$this->assertEquals('1980-12-13', $actualResult);
	}

	/**
	 * @test
	 */
	public function viewHelperRespectsCustomFormat() {
		$viewHelper = new Tx_Fluid_ViewHelpers_Format_DateViewHelper();
		$actualResult = $viewHelper->render(new DateTime('1980-02-01'), 'd.m.Y');
		$this->assertEquals('01.02.1980', $actualResult);
	}

	/**
	 * @test
	 */
	public function viewHelperReturnsEmptyStringIfNULLIsGiven() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_DateViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(NULL));
		$actualResult = $viewHelper->render();
		$this->assertEquals('', $actualResult);
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_ViewHelper_Exception
	 */
	public function viewHelperThrowsExceptionIfDateStringCantBeParsed() {
		$viewHelper = new Tx_Fluid_ViewHelpers_Format_DateViewHelper();
		$viewHelper->render('foo');
	}

	/**
	 * @test
	 */
	public function viewHelperUsesChildNodesIfDateAttributeIsNotSpecified() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_DateViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(new DateTime('1980-12-13')));
		$actualResult = $viewHelper->render();
		$this->assertEquals('1980-12-13', $actualResult);
	}

	/**
	 * @test
	 */
	public function dateArgumentHasPriorityOverChildNodes() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_DateViewHelper', array('renderChildren'));
		$viewHelper->expects($this->never())->method('renderChildren');
		$actualResult = $viewHelper->render('1980-12-12');
		$this->assertEquals('1980-12-12', $actualResult);
	}
}
?>
