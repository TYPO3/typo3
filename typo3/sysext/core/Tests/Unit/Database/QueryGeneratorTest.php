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

namespace TYPO3\CMS\Core\Tests\Unit\Database;

use TYPO3\CMS\Core\Database\QueryGenerator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class QueryGeneratorTest extends UnitTestCase
{
    /**
     * @return array
     */
    public function getSubscriptReturnsExpectedValuesDataProvider(): array
    {
        return [
            'multidimensional array input' => [
                [
                    'foo' => [
                        'bar' => 1,
                        'baz' => [
                            'jane' => 1,
                            'john' => 'doe',
                        ],
                        'fae' => 1,
                    ],
                    'don' => [
                        'dan' => 1,
                        'jim' => [
                            'jon' => 1,
                            'jin' => 'joh',
                        ],
                    ],
                    'one' => [
                        'two' => 1,
                        'three' => [
                            'four' => 1,
                            'five' => 'six',
                        ],
                    ]
                ],
                [
                    0 => 'foo',
                    1 => 'bar',
                ],
            ],
            'array with multiple entries input' => [
                [
                    'foo' => 1,
                    'bar' => 2,
                    'baz' => 3,
                    'don' => 4,
                ],
                [
                    0 => 'foo',
                ],
            ],
            'array with one entry input' => [
                [
                    'foo' => 'bar',
                ],
                [
                    0 => 'foo',
                ],
            ],
            'empty array input' => [
                [],
                [
                    0 => null,
                ],
            ],
            'empty multidimensional array input' => [
                [[[[]]], [[]], [[]]],
                [
                    0 => 0,
                    1 => 0,
                    2 => 0,
                    3 => null,
                ],
            ],
            'null input' => [
                null,
                [],
            ],
            'string input' => [
                'foo bar',
                [],
            ],
            'numeric input' => [
                3.14,
                [],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getSubscriptReturnsExpectedValuesDataProvider
     * @param $input
     * @param array $expectedArray
     */
    public function getSubscriptReturnsExpectedValues($input, array $expectedArray): void
    {
        $subject = new QueryGenerator();
        self::assertSame($expectedArray, $subject->getSubscript($input));
    }
}
