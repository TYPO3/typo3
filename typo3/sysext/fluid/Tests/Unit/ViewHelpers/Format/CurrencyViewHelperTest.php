<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Format;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Fluid\ViewHelpers\Format\CurrencyViewHelper;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Test case
 */
class CurrencyViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var CurrencyViewHelper
     */
    protected $viewHelper;

    protected function setUp()
    {
        parent::setUp();
        $this->viewHelper = new CurrencyViewHelper();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function viewHelperRoundsFloatCorrectly()
    {
        $this->viewHelper->setRenderChildrenClosure(
            function () {
                return 123.456;
            }
        );
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        $this->assertEquals('123,46', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersCurrencySign()
    {
        $this->viewHelper->setRenderChildrenClosure(
            function () {
                return 123;
            }
        );
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'currencySign' => 'foo'
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        $this->assertEquals('123,00 foo', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersPrependedCurrencySign()
    {
        $this->viewHelper->setRenderChildrenClosure(
            function () {
                return 123;
            }
        );
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'currencySign' => 'foo',
                'decimalSeparator' => ',',
                'thousandsSeparator' => '.',
                'prependCurrency' => true
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        $this->assertEquals('foo 123,00', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRespectsCurrencySeparator()
    {
        $this->viewHelper->setRenderChildrenClosure(
            function () {
                return 123;
            }
        );
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'currencySign' => 'foo',
                'decimalSeparator' => ',',
                'thousandsSeparator' => '.',
                'prependCurrency' => true,
                'separateCurrency' => false
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        $this->assertEquals('foo123,00', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRespectsDecimalSeparator()
    {
        $this->viewHelper->setRenderChildrenClosure(
            function () {
                return 12345;
            }
        );
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'currencySign' => '',
                'decimalSeparator' => '|'
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        $this->assertEquals('12.345|00', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRespectsThousandsSeparator()
    {
        $this->viewHelper->setRenderChildrenClosure(
            function () {
                return 12345;
            }
        );
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'currencySign' => '',
                'decimalSeparator' => ',',
                'thousandsSeparator' => '|'
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        $this->assertEquals('12|345,00', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersNullValues()
    {
        $this->viewHelper->setRenderChildrenClosure(
            function () {
                return null;
            }
        );
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        $this->assertEquals('0,00', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersEmptyString()
    {
        $this->viewHelper->setRenderChildrenClosure(
            function () {
                return '';
            }
        );
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        $this->assertEquals('0,00', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersZeroValues()
    {
        $this->viewHelper->setRenderChildrenClosure(
            function () {
                return 0;
            }
        );
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        $this->assertEquals('0,00', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersNegativeAmounts()
    {
        $this->viewHelper->setRenderChildrenClosure(
            function () {
                return -123.456;
            }
        );
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        $this->assertEquals('-123,46', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersStringsToZeroValueFloat()
    {
        $this->viewHelper->setRenderChildrenClosure(
            function () {
                return 'TYPO3';
            }
        );
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        $this->assertEquals('0,00', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersCommaValuesToValueBeforeComma()
    {
        $this->viewHelper->setRenderChildrenClosure(
            function () {
                return '12,34.00';
            }
        );
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        $this->assertEquals('12,00', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersValuesWithoutDecimals()
    {
        $this->viewHelper->setRenderChildrenClosure(
            function () {
                return '54321';
            }
        );
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'currencySign' => '',
                'decimalSeparator' => ',',
                'thousandsSeparator' => '.',
                'prependCurrency' => false,
                'separateCurrency' => true,
                'decimals' => 0
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        $this->assertEquals('54.321', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersThreeDecimals()
    {
        $this->viewHelper->setRenderChildrenClosure(
            function () {
                return '54321';
            }
        );
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'currencySign' => '',
                'decimalSeparator' => ',',
                'thousandsSeparator' => '.',
                'prependCurrency' => false,
                'separateCurrency' => true,
                'decimals' => 3
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        $this->assertEquals('54.321,000', $actualResult);
    }
}
