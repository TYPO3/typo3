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
 * Test for \TYPO3\CMS\Fluid\ViewHelpers\Format\HtmlspecialcharsViewHelper
 */
class HtmlspecialcharsViewHelperTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Fluid\ViewHelpers\Format\HtmlspecialcharsViewHelper
	 */
	protected $viewHelper;

	public function setUp() {
		$this->viewHelper = $this->getMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\HtmlspecialcharsViewHelper', array('renderChildren'));
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
		$actualResult = $this->viewHelper->render('Some string');
		$this->assertEquals('Some string', $actualResult);
	}

	/**
	 * @test
	 */
	public function renderUsesChildnodesAsSourceIfSpecified() {
		$this->viewHelper->expects($this->atLeastOnce())->method('renderChildren')->will($this->returnValue('Some string'));
		$actualResult = $this->viewHelper->render();
		$this->assertEquals('Some string', $actualResult);
	}

	/**
	 * @test
	 */
	public function renderDoesNotModifyValueIfItDoesNotContainSpecialCharacters() {
		$source = 'This is a sample text without special characters.';
		$actualResult = $this->viewHelper->render($source);
		$this->assertSame($source, $actualResult);
	}

	/**
	 * @test
	 */
	public function renderDecodesSimpleString() {
		$source = 'Some special characters: &©"\'';
		$expectedResult = 'Some special characters: &amp;©&quot;\'';
		$actualResult = $this->viewHelper->render($source);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function renderRespectsKeepQuoteArgument() {
		$source = 'Some special characters: &©"\'';
		$expectedResult = 'Some special characters: &amp;©"\'';
		$actualResult = $this->viewHelper->render($source, TRUE);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function renderRespectsEncodingArgument() {
		$source = utf8_decode('Some special characters: &"\'');
		$expectedResult = 'Some special characters: &amp;&quot;\'';
		$actualResult = $this->viewHelper->render($source, FALSE, 'ISO-8859-1');
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function renderConvertsAlreadyConvertedEntitiesByDefault() {
		$source = 'already &quot;encoded&quot;';
		$expectedResult = 'already &amp;quot;encoded&amp;quot;';
		$actualResult = $this->viewHelper->render($source);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function renderDoesNotConvertAlreadyConvertedEntitiesIfDoubleQuoteIsFalse() {
		$source = 'already &quot;encoded&quot;';
		$expectedResult = 'already &quot;encoded&quot;';
		$actualResult = $this->viewHelper->render($source, FALSE, 'UTF-8', FALSE);
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