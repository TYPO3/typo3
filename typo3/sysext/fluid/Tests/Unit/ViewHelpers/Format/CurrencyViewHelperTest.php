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
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Fluid\ViewHelpers\Format\CurrencyViewHelper;

/**
 * Test case
 */
class CurrencyViewHelperTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var CurrencyViewHelper
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = $this->getAccessibleMock(CurrencyViewHelper::class, ['renderChildren']);
        /** @var RenderingContext $renderingContext */
        $renderingContext = $this->getMock(RenderingContext::class);
        $this->subject->_set('renderingContext', $renderingContext);
    }

    /**
     * @test
     */
    public function viewHelperRoundsFloatCorrectly()
    {
        $this->subject->expects($this->once())->method('renderChildren')->will($this->returnValue(123.456));
        $actualResult = $this->subject->render();
        $this->assertEquals('123,46', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersCurrencySign()
    {
        $this->subject->expects($this->once())->method('renderChildren')->will($this->returnValue(123));
        $actualResult = $this->subject->render('foo');
        $this->assertEquals('123,00 foo', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersPrependedCurrencySign()
    {
        $this->subject->expects($this->once())->method('renderChildren')->will($this->returnValue(123));
        $actualResult = $this->subject->render('foo', ',', '.', true);
        $this->assertEquals('foo 123,00', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRespectsCurrencySeparator()
    {
        $this->subject->expects($this->once())->method('renderChildren')->will($this->returnValue(123));
        $actualResult = $this->subject->render('foo', ',', '.', true, false);
        $this->assertEquals('foo123,00', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRespectsDecimalSeparator()
    {
        $this->subject->expects($this->once())->method('renderChildren')->will($this->returnValue(12345));
        $actualResult = $this->subject->render('', '|');
        $this->assertEquals('12.345|00', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRespectsThousandsSeparator()
    {
        $this->subject->expects($this->once())->method('renderChildren')->will($this->returnValue(12345));
        $actualResult = $this->subject->render('', ',', '|');
        $this->assertEquals('12|345,00', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersNullValues()
    {
        $this->subject->expects($this->once())->method('renderChildren')->will($this->returnValue(null));
        $actualResult = $this->subject->render();
        $this->assertEquals('0,00', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersEmptyString()
    {
        $this->subject->expects($this->once())->method('renderChildren')->will($this->returnValue(''));
        $actualResult = $this->subject->render();
        $this->assertEquals('0,00', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersZeroValues()
    {
        $this->subject->expects($this->once())->method('renderChildren')->will($this->returnValue(0));
        $actualResult = $this->subject->render();
        $this->assertEquals('0,00', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersNegativeAmounts()
    {
        $this->subject->expects($this->once())->method('renderChildren')->will($this->returnValue(-123.456));
        $actualResult = $this->subject->render();
        $this->assertEquals('-123,46', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersStringsToZeroValueFloat()
    {
        $this->subject->expects($this->once())->method('renderChildren')->will($this->returnValue('TYPO3'));
        $actualResult = $this->subject->render();
        $this->assertEquals('0,00', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersCommaValuesToValueBeforeComma()
    {
        $this->subject->expects($this->once())->method('renderChildren')->will($this->returnValue('12,34.00'));
        $actualResult = $this->subject->render();
        $this->assertEquals('12,00', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersValuesWithoutDecimals()
    {
        $this->subject->expects($this->once())->method('renderChildren')->will($this->returnValue('54321'));
        $actualResult = $this->subject->render('', ',', '.', false, true, 0);
        $this->assertEquals('54.321', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersThreeDecimals()
    {
        $this->subject->expects($this->once())->method('renderChildren')->will($this->returnValue('54321'));
        $actualResult = $this->subject->render('', ',', '.', false, true, 3);
        $this->assertEquals('54.321,000', $actualResult);
    }
}
