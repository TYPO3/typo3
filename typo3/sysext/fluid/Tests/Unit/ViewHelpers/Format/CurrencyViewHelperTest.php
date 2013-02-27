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

class CurrencyViewHelperTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @test
	 */
	public function viewHelperRoundsFloatCorrectly() {
		$viewHelper = $this->getMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\CurrencyViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(123.456));
		$actualResult = $viewHelper->render();
		$this->assertEquals('123,46', $actualResult);
	}

	/**
	 * @test
	 */
	public function viewHelperRendersCurrencySign() {
		$viewHelper = $this->getMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\CurrencyViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(123));
		$actualResult = $viewHelper->render('foo');
		$this->assertEquals('123,00 foo', $actualResult);
	}

	/**
	 * @test
	 */
	public function viewHelperRendersPrependedCurrencySign() {
		$viewHelper = $this->getMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\CurrencyViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(123));
		$actualResult = $viewHelper->render('foo', ',', '.', TRUE);
		$this->assertEquals('foo 123,00', $actualResult);
	}

	/**
	 * @test
	 */
	public function viewHelperRespectsCurrencySeparator() {
		$viewHelper = $this->getMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\CurrencyViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(123));
		$actualResult = $viewHelper->render('foo', ',', '.', TRUE, FALSE);
		$this->assertEquals('foo123,00', $actualResult);
	}

	/**
	 * @test
	 */
	public function viewHelperRespectsDecimalSeparator() {
		$viewHelper = $this->getMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\CurrencyViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(12345));
		$actualResult = $viewHelper->render('', '|');
		$this->assertEquals('12.345|00', $actualResult);
	}

	/**
	 * @test
	 */
	public function viewHelperRespectsThousandsSeparator() {
		$viewHelper = $this->getMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\CurrencyViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(12345));
		$actualResult = $viewHelper->render('', ',', '|');
		$this->assertEquals('12|345,00', $actualResult);
	}

	/**
	 * @test
	 */
	public function viewHelperRendersNullValues() {
		$viewHelper = $this->getMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\CurrencyViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(NULL));
		$actualResult = $viewHelper->render();
		$this->assertEquals('0,00', $actualResult);
	}

	/**
	 * @test
	 */
	public function viewHelperRendersEmptyString() {
		$viewHelper = $this->getMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\CurrencyViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(''));
		$actualResult = $viewHelper->render();
		$this->assertEquals('0,00', $actualResult);
	}

	/**
	 * @test
	 */
	public function viewHelperRendersZeroValues() {
		$viewHelper = $this->getMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\CurrencyViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(0));
		$actualResult = $viewHelper->render();
		$this->assertEquals('0,00', $actualResult);
	}

	/**
	 * @test
	 */
	public function viewHelperRendersNegativeAmounts() {
		$viewHelper = $this->getMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\CurrencyViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(-123.456));
		$actualResult = $viewHelper->render();
		$this->assertEquals('-123,46', $actualResult);
	}

	/**
	 * @test
	 */
	public function viewHelperRendersStringsToZeroValueFloat() {
		$viewHelper = $this->getMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\CurrencyViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('TYPO3'));
		$actualResult = $viewHelper->render();
		$this->assertEquals('0,00', $actualResult);
	}

	/**
	 * @test
	 */
	public function viewHelperRendersCommaValuesToValueBeforeComma() {
		$viewHelper = $this->getMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\CurrencyViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('12,34.00'));
		$actualResult = $viewHelper->render();
		$this->assertEquals('12,00', $actualResult);
	}

	/**
	 * @test
	 */
	public function viewHelperRendersValuesWithoutDecimals() {
		$viewHelper = $this->getMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\CurrencyViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('54321'));
		$actualResult = $viewHelper->render('', ',', '.', FALSE, TRUE, 0);
		$this->assertEquals('54.321', $actualResult);
	}

	/**
	 * @test
	 */
	public function viewHelperRendersThreeDecimals() {
		$viewHelper = $this->getMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\CurrencyViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('54321'));
		$actualResult = $viewHelper->render('', ',', '.', FALSE, TRUE, 3);
		$this->assertEquals('54.321,000', $actualResult);
	}
}

?>