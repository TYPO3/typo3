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
class Tx_Fluid_Tests_Unit_ViewHelpers_Format_RawViewHelperTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @var Tx_Fluid_ViewHelpers_Format_RawViewHelper
	 */
	protected $viewHelper;

	public function setUp() {
		$this->viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_RawViewHelper', array('renderChildren'));
	}

	/**
	 * @test
	 */
	public function viewHelperDeactivatesEscapingInterceptor() {
		$this->assertFalse($this->viewHelper->isEscapingInterceptorEnabled());
	}

	/**
	 * @test
	 */
	public function renderReturnsUnmodifiedValueIfSpecified() {
		$value = 'input value " & äöüß@';
		$this->viewHelper->expects($this->never())->method('renderChildren');
		$actualResult = $this->viewHelper->render($value);
		$this->assertEquals($value, $actualResult);
	}

	/**
	 * @test
	 */
	public function renderReturnsUnmodifiedChildNodesIfNoValueIsSpecified() {
		$childNodes = 'input value " & äöüß@';
		$this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue($childNodes));
		$actualResult = $this->viewHelper->render();
		$this->assertEquals($childNodes, $actualResult);
	}
}
?>
