<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Format;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Test for \TYPO3\CMS\Fluid\ViewHelpers\Format\Nl2brViewHelper
 */
class Nl2brViewHelperTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @test
	 */
	public function viewHelperDoesNotModifyTextWithoutLineBreaks() {
		$viewHelper = $this->getMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\Nl2brViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('<p class="bodytext">Some Text without line breaks</p>'));
		$actualResult = $viewHelper->render();
		$this->assertEquals('<p class="bodytext">Some Text without line breaks</p>', $actualResult);
	}

	/**
	 * @test
	 */
	public function viewHelperConvertsLineBreaksToBRTags() {
		$viewHelper = $this->getMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\Nl2brViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('Line 1' . chr(10) . 'Line 2'));
		$actualResult = $viewHelper->render();
		$this->assertEquals('Line 1<br />' . chr(10) . 'Line 2', $actualResult);
	}

	/**
	 * @test
	 */
	public function viewHelperConvertsWindowsLineBreaksToBRTags() {
		$viewHelper = $this->getMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\Nl2brViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('Line 1' . chr(13) . chr(10) . 'Line 2'));
		$actualResult = $viewHelper->render();
		$this->assertEquals('Line 1<br />' . chr(13) . chr(10) . 'Line 2', $actualResult);
	}
}

?>