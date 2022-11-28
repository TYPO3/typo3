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

namespace TYPO3\CMS\Frontend\Tests\Unit\Typolink;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Frontend\Typolink\LinkVarsCalculator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class LinkVarsCalculatorTest extends UnitTestCase
{
    public function calculateLinkVarsDataProvider(): array
    {
        return [
            'simple variable' => [
                'L',
                [
                    'L' => 1,
                ],
                '&L=1',
            ],
            'missing variable' => [
                'L',
                [
                ],
                '',
            ],
            'restricted variables' => [
                'L(1-3),bar(3),foo(array),blub(array)',
                [
                    'L' => 1,
                    'bar' => 2,
                    'foo' => [ 1, 2, 'f' => [ 4, 5 ] ],
                    'blub' => 123,
                ],
                '&L=1&foo%5B0%5D=1&foo%5B1%5D=2&foo%5Bf%5D%5B0%5D=4&foo%5Bf%5D%5B1%5D=5',
            ],
            'nested variables' => [
                'bar|foo(1-2)',
                [
                    'bar' => [
                        'foo' => 1,
                        'unused' => 'never',
                    ],
                ],
                '&bar[foo]=1',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider calculateLinkVarsDataProvider
     */
    public function calculateLinkVarsConsidersCorrectVariables(string $linkVars, array $getVars, string $expected): void
    {
        $subject = new LinkVarsCalculator();
        $result = $subject->getAllowedLinkVarsFromRequest($linkVars, $getVars, new Context());
        self::assertEquals($expected, $result);
    }

    public function splitLinkVarsDataProvider(): array
    {
        return [
            [
                'L',
                ['L'],
            ],
            [
                'L,a',
                [
                    'L',
                    'a',
                ],
            ],
            [
                'L, a',
                [
                    'L',
                    'a',
                ],
            ],
            [
                'L , a',
                [
                    'L',
                    'a',
                ],
            ],
            [
                ' L, a ',
                [
                    'L',
                    'a',
                ],
            ],
            [
                'L(1)',
                [
                    'L(1)',
                ],
            ],
            [
                'L(1),a',
                [
                    'L(1)',
                    'a',
                ],
            ],
            [
                'L(1) ,  a',
                [
                    'L(1)',
                    'a',
                ],
            ],
            [
                'a,L(1)',
                [
                    'a',
                    'L(1)',
                ],
            ],
            [
                'L(1),a(2-3)',
                [
                    'L(1)',
                    'a(2-3)',
                ],
            ],
            [
                'L(1),a((2-3))',
                [
                    'L(1)',
                    'a((2-3))',
                ],
            ],
            [
                'L(1),a(a{2,4})',
                [
                    'L(1)',
                    'a(a{2,4})',
                ],
            ],
            [
                'L(1),a(/a{2,4}\,()/)',
                [
                    'L(1)',
                    'a(/a{2,4}\,()/)',
                ],
            ],
            [
                'L,a , b(c) , dd(/g{1,2}/), eee(, ()f) , 2',
                [
                    'L',
                    'a',
                    'b(c)',
                    'dd(/g{1,2}/)',
                    'eee(, ()f)',
                    '2',
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider splitLinkVarsDataProvider
     */
    public function splitLinkVarsStringSplitsStringByComma(string $string, array $expected): void
    {
        $subject = $this->getAccessibleMock(LinkVarsCalculator::class, null);
        self::assertEquals($expected, $subject->_call('splitLinkVarsString', $string));
    }
}
