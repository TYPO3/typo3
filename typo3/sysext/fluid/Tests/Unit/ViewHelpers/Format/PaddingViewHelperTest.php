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
class Tx_Fluid_Tests_Unit_ViewHelpers_Format_PaddingViewHelperTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @test
	 */
	public function stringsArePaddedWithBlanksByDefault() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_PaddingViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('foo'));
		$actualResult = $viewHelper->render(10);
		$this->assertEquals('foo       ', $actualResult);
	}

	/**
	 * @test
	 */
	public function paddingStringCanBeSpecified() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_PaddingViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('foo'));
		$actualResult = $viewHelper->render(10, '-=');
		$this->assertEquals('foo-=-=-=-', $actualResult);
	}

	/**
	 * @test
	 */
	public function stringIsNotTruncatedIfPadLengthIsBelowStringLength() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_PaddingViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('some long string'));
		$actualResult = $viewHelper->render(5);
		$this->assertEquals('some long string', $actualResult);
	}

	/**
	 * @test
	 */
	public function integersArePaddedCorrectly() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_PaddingViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(123));
		$actualResult = $viewHelper->render(5, '0');
		$this->assertEquals('12300', $actualResult);
	}
}
?>