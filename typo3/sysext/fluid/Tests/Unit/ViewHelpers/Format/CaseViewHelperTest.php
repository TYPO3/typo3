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

use TYPO3\CMS\Fluid\ViewHelpers\Format\CaseViewHelper;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;

/**
 * Test case
 */
class CaseViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var CaseViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = new CaseViewHelper();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function viewHelperRendersChildrenIfGivenValueIsNull()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'value' => ''
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperDoesNotRenderChildrenIfGivenValueIsNotNull()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'value' => 'Some string'
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('SOME STRING', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperThrowsExceptionIfIncorrectModeIsGiven()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1358349150);
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'value' => 'Foo',
                'mode' => 'incorrectMode'
            ]
        );
        $this->viewHelper->initializeArgumentsAndRender();
    }

    /**
     * @test
     */
    public function viewHelperConvertsUppercasePerDefault()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'value' => 'FooB4r'
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertSame('FOOB4R', $actualResult);
    }

    /**
     * Signature: $input, $mode, $expected
     */
    public function conversionTestingDataProvider()
    {
        return [
            ['FooB4r', CaseViewHelper::CASE_LOWER, 'foob4r'],
            ['FooB4r', CaseViewHelper::CASE_UPPER, 'FOOB4R'],
            ['foo bar', CaseViewHelper::CASE_CAPITAL, 'Foo bar'],
            ['FOO Bar', CaseViewHelper::CASE_UNCAPITAL, 'fOO Bar'],
            ['smørrebrød', CaseViewHelper::CASE_UPPER, 'SMØRREBRØD'],
            ['smørrebrød', CaseViewHelper::CASE_CAPITAL, 'Smørrebrød'],
            ['römtömtömtöm', CaseViewHelper::CASE_UPPER, 'RÖMTÖMTÖMTÖM'],
            ['Ἕλλάς α ω', CaseViewHelper::CASE_UPPER, 'ἝΛΛΆΣ Α Ω'],
        ];
    }

    /**
     * @test
     * @dataProvider conversionTestingDataProvider
     */
    public function viewHelperConvertsCorrectly($input, $mode, $expected)
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'value' => $input,
                'mode' => $mode
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertSame($expected, $actualResult, sprintf('The conversion with mode "%s" did not perform as expected.', $mode));
    }
}
