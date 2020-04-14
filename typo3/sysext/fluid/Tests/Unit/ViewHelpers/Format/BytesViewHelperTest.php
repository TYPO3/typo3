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

use TYPO3\CMS\Fluid\ViewHelpers\Format\BytesViewHelper;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Test case
 */
class BytesViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var BytesViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = new BytesViewHelper();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @return array
     */
    public function valueDataProvider()
    {
        return [

                // invalid values
            [
                'value' => 'invalid',
                'decimals' => null,
                'decimalSeparator' => null,
                'thousandsSeparator' => null,
                'expected' => '0 B'
            ],
            [
                'value' => '',
                'decimals' => 2,
                'decimalSeparator' => null,
                'thousandsSeparator' => null,
                'expected' => '0.00 B'
            ],
            [
                'value' => [],
                'decimals' => 2,
                'decimalSeparator' => ',',
                'thousandsSeparator' => null,
                'expected' => '0,00 B'
            ],
                // valid values
            [
                'value' => 123,
                'decimals' => null,
                'decimalSeparator' => null,
                'thousandsSeparator' => null,
                'expected' => '123 B'
            ],
            [
                'value' => '43008',
                'decimals' => 1,
                'decimalSeparator' => null,
                'thousandsSeparator' => null,
                'expected' => '42.0 KB'
            ],
            [
                'value' => 1024,
                'decimals' => 1,
                'decimalSeparator' => null,
                'thousandsSeparator' => null,
                'expected' => '1.0 KB'
            ],
            [
                'value' => 1023,
                'decimals' => 2,
                'decimalSeparator' => null,
                'thousandsSeparator' => null,
                'expected' => '1,023.00 B'
            ],
            [
                'value' => 1073741823,
                'decimals' => 1,
                'decimalSeparator' => null,
                'thousandsSeparator' => '.',
                'expected' => '1.024.0 MB'
            ],
            [
                'value' => 1024 ** 5,
                'decimals' => 1,
                'decimalSeparator' => null,
                'thousandsSeparator' => null,
                'expected' => '1.0 PB'
            ],
            [
                'value' => 1024 ** 8,
                'decimals' => 1,
                'decimalSeparator' => null,
                'thousandsSeparator' => null,
                'expected' => '1.0 YB'
            ]
        ];
    }

    /**
     * @param mixed $value
     * @param int $decimals
     * @param string $decimalSeparator
     * @param string $thousandsSeparator
     * @param string $expected
     * @test
     * @dataProvider valueDataProvider
     */
    public function renderConvertsAValue($value, $decimals, $decimalSeparator, $thousandsSeparator, $expected)
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'value' => $value,
                'decimals' => $decimals,
                'decimalSeparator' => $decimalSeparator,
                'thousandsSeparator' => $thousandsSeparator,
                'units' => 'B,KB,MB,GB,TB,PB,EB,ZB,YB',
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals($expected, $actualResult);
    }

    /**
     * @test
     */
    public function renderUsesChildNodesIfValueArgumentIsOmitted()
    {
        $this->viewHelper->setRenderChildrenClosure(
            function () {
                return 12345;
            }
        );
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'units' => 'B,KB,MB,GB,TB,PB,EB,ZB,YB',
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('12 KB', $actualResult);
    }
}
