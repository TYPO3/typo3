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
class Tx_Fluid_Tests_Unit_ViewHelpers_Format_NumberViewHelperTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @test
	 */
	public function formatNumberDefaultsToEnglishNotationWithTwoDecimals() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_NumberViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(10000.0 / 3.0));
		$actualResult = $viewHelper->render();
		$this->assertEquals('3,333.33', $actualResult);
	}

	/**
	 * @test
	 */
	public function formatNumberWithDecimalsDecimalPointAndSeparator() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_NumberViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(10000.0 / 3.0));
		$actualResult = $viewHelper->render(3, ',', '.');
		$this->assertEquals('3.333,333', $actualResult);
	}
}
?>