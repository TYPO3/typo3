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

use TYPO3\CMS\Fluid\ViewHelpers\Format\PaddingViewHelper;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Test case
 */
class PaddingViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var PaddingViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = new PaddingViewHelper();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function stringsArePaddedWithBlanksByDefault()
    {
        $this->viewHelper->setRenderChildrenClosure(
            function () {
                return 'foo';
            }
        );
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'padLength' => 10
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('foo       ', $actualResult);
    }

    /**
     * @test
     */
    public function paddingStringCanBeSpecified()
    {
        $this->viewHelper->setRenderChildrenClosure(
            function () {
                return 'foo';
            }
        );
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'padLength' => 10,
                'padString' => '-='
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('foo-=-=-=-', $actualResult);
    }

    /**
     * @test
     */
    public function stringIsNotTruncatedIfPadLengthIsBelowStringLength()
    {
        $this->viewHelper->setRenderChildrenClosure(
            function () {
                return 'some long string';
            }
        );
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'padLength' => 5
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('some long string', $actualResult);
    }

    /**
     * @test
     */
    public function integersAreRespectedAsValue()
    {
        $this->viewHelper->setRenderChildrenClosure(
            function () {
                return 123;
            }
        );
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'padLength' => 5,
                'padString' => '0'
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('12300', $actualResult);
    }

    /**
     * @test
     */
    public function valueParameterIsRespected()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'value' => 'foo',
                'padLength' => 5,
                'padString' => '0',
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('foo00', $actualResult);
    }
}
