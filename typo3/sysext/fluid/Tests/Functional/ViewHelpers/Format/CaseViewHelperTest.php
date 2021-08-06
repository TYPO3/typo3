<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Format;

use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;

class CaseViewHelperTest extends FunctionalTestCase
{
    /**
     * @var bool Speed up this test case, it needs no database
     */
    protected $initializeDatabase = false;

    public function renderConvertsAValueDataProvider(): array
    {
        return [
            'empty value' => [
                '<f:format.case value="" />',
                ''
            ],
            'value from child, uppercase default' => [
                '<f:format.case>foob4r</f:format.case>',
                'FOOB4R'
            ],
            'simple value' => [
                '<f:format.case value="foo" />',
                'FOO'
            ],
            'mode lower' => [
                '<f:format.case value="FooB4r" mode="lower" />',
                'foob4r'
            ],
            'mode upper' => [
                '<f:format.case value="FooB4r" mode="upper" />',
                'FOOB4R'
            ],
            'mode capital' => [
                '<f:format.case value="foo bar" mode="capital" />',
                'Foo bar'
            ],
            'mode uncapital' => [
                '<f:format.case value="FOO Bar" mode="uncapital" />',
                'fOO Bar'
            ],
            'special chars 1' => [
                '<f:format.case value="smørrebrød" mode="upper" />',
                'SMØRREBRØD'
            ],
            'special chars 2' => [
                '<f:format.case value="smørrebrød" mode="capital" />',
                'Smørrebrød'
            ],
            'special chars 3' => [
                '<f:format.case value="römtömtömtöm" mode="upper" />',
                'RÖMTÖMTÖMTÖM'
            ],
            'special chars 4' => [
                '<f:format.case value="Ἕλλάς α ω" mode="upper" />',
                'ἝΛΛΆΣ Α Ω'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider renderConvertsAValueDataProvider
     */
    public function renderConvertsAValue(string $src, string $expected): void
    {
        $view = new StandaloneView();
        $view->setTemplateSource($src);
        self::assertSame($expected, $view->render());
    }

    /**
     * @test
     */
    public function viewHelperThrowsExceptionIfIncorrectModeIsGiven(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1358349150);
        $view = new StandaloneView();
        $view->setTemplateSource('<f:format.case value="foo" mode="invalid" />');
        $view->render();
    }
}
