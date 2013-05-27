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
class Tx_Fluid_Tests_Unit_ViewHelpers_Format_Nl2brViewHelperTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @test
	 */
	public function viewHelperDoesNotModifyTextWithoutLineBreaks() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_Nl2brViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('<p class="bodytext">Some Text without line breaks</p>'));
		$actualResult = $viewHelper->render();
		$this->assertEquals('<p class="bodytext">Some Text without line breaks</p>', $actualResult);
	}

	/**
	 * @test
	 */
	public function viewHelperConvertsLineBreaksToBRTags() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_Nl2brViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('Line 1' . chr(10) . 'Line 2'));
		$actualResult = $viewHelper->render();
		$this->assertEquals('Line 1<br />' . chr(10) . 'Line 2', $actualResult);
	}

	/**
	 * @test
	 */
	public function viewHelperConvertsWindowsLineBreaksToBRTags() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_Nl2brViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('Line 1' . chr(13) . chr(10) . 'Line 2'));
		$actualResult = $viewHelper->render();
		$this->assertEquals('Line 1<br />' . chr(13) . chr(10) . 'Line 2', $actualResult);
	}
}
?>
