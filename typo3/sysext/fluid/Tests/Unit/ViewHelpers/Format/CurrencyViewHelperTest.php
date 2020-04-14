<?php

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

namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Format;

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

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = new CurrencyViewHelper();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @return array
     */
    public function valueDataProvider()
    {
        return [
            'rounds float correctly' => [
                'value' => 123.456,
                'arguments' =>
                    [
                    ],
                'expected' => '123,46',
            ],
            'currency sign' => [
                'value' => 123,
                'arguments' =>
                    [
                        'currencySign' => 'foo'
                    ],
                'expected' => '123,00 foo',
            ],
            'prepended currency sign' => [
                'value' => 123,
                'arguments' =>
                    [
                        'currencySign' => 'foo',
                        'decimalSeparator' => ',',
                        'thousandsSeparator' => '.',
                        'prependCurrency' => true
                    ],
                'expected' => 'foo 123,00',
            ],
            'respects currency separator' =>[
                'value' => 123,
                'arguments' =>
                    [
                        'currencySign' => 'foo',
                        'decimalSeparator' => ',',
                        'thousandsSeparator' => '.',
                        'prependCurrency' => true,
                        'separateCurrency' => false
                    ],
                'expected' => 'foo123,00',
            ],
            'respects decimal separator' => [
                'value' => 12345,
                'arguments' =>
                    [
                        'currencySign' => '',
                        'decimalSeparator' => '|'
                    ],
                'expected' => '12.345|00',
            ],
            'respects thousands separator' => [
                'value' => 12345,
                'arguments' =>
                    [
                        'currencySign' => '',
                        'decimalSeparator' => ',',
                        'thousandsSeparator' => '|'
                    ],
                'expected' => '12|345,00',
            ],
            'null values' => [
                'value' => null,
                'arguments' =>
                    [
                    ],
                'expected' => '0,00',
            ],
            'empty string' => [
                'value' => '',
                'arguments' =>
                    [
                    ],
                'expected' => '0,00',
            ],
            'zero values' => [
                'value' => 0,
                'arguments' =>
                    [
                    ],
                'expected' => '0,00',
            ],
            'negative amounts' => [
                'value' => '-123.456',
                'arguments' =>
                    [
                    ],
                'expected' => '-123,46',
            ],
            'strings to zero value float' => [
                'value' => 'TYPO3',
                'arguments' =>
                    [
                    ],
                'expected' => '0,00',
            ],
            'comma values to value before comma' => [
                'value' => '12,34.00',
                'arguments' =>
                    [
                    ],
                'expected' => '12,00',
            ],
            'without decimals' => [
                'value' => '54321',
                'arguments' =>
                    [
                        'currencySign' => '',
                        'decimalSeparator' => ',',
                        'thousandsSeparator' => '.',
                        'prependCurrency' => false,
                        'separateCurrency' => true,
                        'decimals' => 0
                    ],
                'expected' => '54.321',
            ],
            'three decimals' => [
                'value' => '54321',
                'arguments' =>
                    [
                        'currencySign' => '',
                        'decimalSeparator' => ',',
                        'thousandsSeparator' => '.',
                        'prependCurrency' => false,
                        'separateCurrency' => true,
                        'decimals' => 3
                    ],
                'expected' => '54.321,000',
            ],
            'with dash' => [
                'value' => '54321.00',
                'arguments' =>
                    [
                        'decimalSeparator' => ',',
                        'thousandsSeparator' => '.',
                        'prependCurrency' => false,
                        'separateCurrency' => true,
                        'useDash' => true,
                    ],
                'expected' => '54.321,—',
            ],
            'without dash' => [
                'value' => '54321.45',
                'arguments' =>
                    [
                        'decimalSeparator' => ',',
                        'thousandsSeparator' => '.',
                        'prependCurrency' => false,
                        'separateCurrency' => true,
                        'useDash' => true,
                    ],
                'expected' => '54.321,45',
            ],
        ];
    }

    /**
     * @param $value
     * @param array $arguments
     * @param string $expected
     * @test
     * @dataProvider valueDataProvider
     */
    public function viewHelperRender($value, array $arguments, string $expected)
    {
        $this->viewHelper->setRenderChildrenClosure(
            function () use ($value) {
                return $value;
            }
        );
        $this->setArgumentsUnderTest($this->viewHelper, $arguments);
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals($expected, $actualResult);
    }
}
