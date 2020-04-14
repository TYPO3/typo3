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

use TYPO3\CMS\Fluid\ViewHelpers\Format\NumberViewHelper;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Test case
 */
class NumberViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var NumberViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = new NumberViewHelper();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->setRenderChildrenClosure(
            function () {
                return M_PI;
            }
        );
    }

    /**
     * @test
     */
    public function formatNumberDefaultsToEnglishNotationWithTwoDecimals()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            []
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('3.14', $actualResult);
    }

    /**
     * @test
     */
    public function formatNumberWithDecimalPoint()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'decimalSeparator' => ',',
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('3,14', $actualResult);
    }

    /**
     * @test
     */
    public function formatNumberWithDecimals()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'decimals' => 4,
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('3.1416', $actualResult);
    }

    /**
     * @test
     */
    public function formatNumberWithThousandsSeparator()
    {
        $this->viewHelper->setRenderChildrenClosure(function () {
            return M_PI * 1000;
        });
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'thousandsSeparator' => ',',
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('3,141.59', $actualResult);
    }

    /**
     * @test
     */
    public function formatNumberWithEmptyInput()
    {
        $this->viewHelper->setRenderChildrenClosure(function () {
            return '';
        });
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            []
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('0.00', $actualResult);
    }
}
