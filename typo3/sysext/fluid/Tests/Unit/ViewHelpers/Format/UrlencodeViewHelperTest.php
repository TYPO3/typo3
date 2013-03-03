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
 * Test for \TYPO3\CMS\Fluid\ViewHelpers\Format\UrlencodeViewHelper
 */
class UrlencodeViewHelperTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Fluid\ViewHelpers\Format\UrlencodeViewHelper
	 */
	protected $viewHelper;

	public function setUp() {
		$this->viewHelper = $this->getMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\UrlencodeViewHelper', array('renderChildren'));
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
	public function renderUsesValueAsSourceIfSpecified() {
		$this->viewHelper->expects($this->never())->method('renderChildren');
		$actualResult = $this->viewHelper->render('Source');
		$this->assertEquals('Source', $actualResult);
	}

	/**
	 * @test
	 */
	public function renderUsesChildnodesAsSourceIfSpecified() {
		$this->viewHelper->expects($this->atLeastOnce())->method('renderChildren')->will($this->returnValue('Source'));
		$actualResult = $this->viewHelper->render();
		$this->assertEquals('Source', $actualResult);
	}

	/**
	 * @test
	 */
	public function renderDoesNotModifyValueIfItDoesNotContainSpecialCharacters() {
		$source = 'StringWithoutSpecialCharacters';
		$actualResult = $this->viewHelper->render($source);
		$this->assertSame($source, $actualResult);
	}

	/**
	 * @test
	 */
	public function renderEncodesString() {
		$source = 'Foo @+%/ "';
		$expectedResult = 'Foo%20%40%2B%25%2F%20%22';
		$actualResult = $this->viewHelper->render($source);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function renderReturnsUnmodifiedSourceIfItIsNoString() {
		$source = new \stdClass();
		$actualResult = $this->viewHelper->render($source);
		$this->assertSame($source, $actualResult);
	}
}

?>