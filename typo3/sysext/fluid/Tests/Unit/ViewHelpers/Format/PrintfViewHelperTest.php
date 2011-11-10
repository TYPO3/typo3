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
class Tx_Fluid_Tests_Unit_ViewHelpers_Format_PrintfViewHelperTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @test
	 */
	public function viewHelperCanUseArrayAsArgument() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_PrintfViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('%04d-%02d-%02d'));
		$actualResult = $viewHelper->render(array('year' => 2009, 'month' => 4, 'day' => 5));
		$this->assertEquals('2009-04-05', $actualResult);
	}

	/**
	 * @test
	 */
	public function viewHelperCanSwapMultipleArguments() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_PrintfViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('%2$s %1$d %3$s %2$s'));
		$actualResult = $viewHelper->render(array(123, 'foo', 'bar'));
		$this->assertEquals('foo 123 bar foo', $actualResult);
	}
}
?>