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

namespace TYPO3\CMS\Core\Tests\Unit\Utility;

use TYPO3\CMS\Core\Tests\Unit\Utility\Fixtures\StringValue;
use TYPO3\CMS\Core\Utility\PermutationUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for class \TYPO3\CMS\Core\Utility\PermutationUtility
 */
class PermutationUtilityTest extends UnitTestCase
{
    public function meltStringItemsDataProvider(): array
    {
        return [
            'string items' => [
                [
                    ['a', 'b'],
                    ['c', 'd'],
                    ['e', 'f'],
                ],
                ['ace', 'acf', 'ade', 'adf', 'bce', 'bcf', 'bde', 'bdf'],
            ],
            'string & empty value items' => [
                [
                    ['a', ''],
                    ['b', ''],
                    ['c', ''],
                ],
                ['abc', 'ab', 'ac', 'a', 'bc', 'b', 'c', '']
            ],
            'object::__toString() items' => [
                [
                    [new StringValue('a'), new StringValue('b')],
                    [new StringValue('c'), new StringValue('d')],
                    [new StringValue('e'), new StringValue('f')],
                ],
                ['ace', 'acf', 'ade', 'adf', 'bce', 'bcf', 'bde', 'bdf'],
            ],
            'string & object::__toString() items' => [
                [
                    ['a', new StringValue('b')],
                    ['c', new StringValue('d')],
                    ['e', new StringValue('f')],
                ],
                ['ace', 'acf', 'ade', 'adf', 'bce', 'bcf', 'bde', 'bdf'],
            ],
            'string items with invalid object' => [
                [
                    ['a', 'b'],
                    ['c', 'd'],
                    ['e', 'f', new \stdClass()],
                ],
                1578164102,
            ],
            'string items with invalid integer' => [
                [
                    ['a', 'b'],
                    ['c', 'd'],
                    ['e', 'f', 123],
                ],
                1578164102,
            ],
            'string items with invalid boolean' => [
                [
                    ['a', 'b'],
                    ['c', 'd'],
                    ['e', 'f', true],
                ],
                1578164102,
            ],
            'string items with invalid array' => [
                [
                    ['a', 'b'],
                    ['c', 'd'],
                    ['e', 'f', []],
                ],
                1578164102,
            ],
            'string items with invalid invocation' => [
                [
                    'a', 'b',
                ],
                1578164101,
            ],
        ];
    }

    /**
     * @param array $payload
     * @param array|int $expectation
     *
     * @test
     * @dataProvider meltStringItemsDataProvider
     */
    public function meltStringItemsIsExecuted(array $payload, $expectation): void
    {
        if (is_int($expectation)) {
            $this->expectException(\LogicException::class);
            $this->expectExceptionCode($expectation);
        }
        self::assertSame($expectation, PermutationUtility::meltStringItems($payload));
    }

    public function meltArrayItemsDataProvider(): array
    {
        $aStringValue = new StringValue('a');
        $bStringValue = new StringValue('b');
        $cStringValue = new StringValue('c');
        $dStringValue = new StringValue('d');

        return [
            'string items' => [
                [
                    ['a', 'b'],
                    ['c', 'd'],
                    ['e', 'f'],
                ],
                [
                    ['a', 'c', 'e'], ['a', 'c', 'f'], ['a', 'd', 'e'], ['a', 'd', 'f'],
                    ['b', 'c', 'e'], ['b', 'c', 'f'], ['b', 'd', 'e'], ['b', 'd', 'f'],
                ],
            ],
            'object::__toString() items' => [
                [
                    [$aStringValue, $bStringValue],
                    [$cStringValue, $dStringValue],
                ],
                [
                    [$aStringValue, $cStringValue], [$aStringValue, $dStringValue],
                    [$bStringValue, $cStringValue], [$bStringValue, $dStringValue],
                ],
            ],
            'mixed items' => [
                [
                    [$aStringValue, 'a', 1],
                    [$bStringValue, 'b', 2],
                ],
                [
                    [$aStringValue, $bStringValue], [$aStringValue, 'b'], [$aStringValue, 2],
                    ['a', $bStringValue], ['a', 'b'], ['a', 2],
                    [1, $bStringValue], [1, 'b'], [1, 2],
                ],
            ],
            'string items in ArrayObject' => [
                [
                    new \ArrayObject(['a', 'b']),
                    new \ArrayObject(['c', 'd']),
                    new \ArrayObject(['e', 'f']),
                ],
                [
                    ['a', 'c', 'e'], ['a', 'c', 'f'], ['a', 'd', 'e'], ['a', 'd', 'f'],
                    ['b', 'c', 'e'], ['b', 'c', 'f'], ['b', 'd', 'e'], ['b', 'd', 'f'],
                ],
            ],
            'string items with invalid invocation' => [
                [
                    'a', 'b',
                ],
                1578164101,
            ],
            'object::__toString() items with invalid invocation' => [
                [
                    new StringValue('b'),
                    new StringValue('c'),
                    new StringValue('d'),
                ],
                1578164101,
            ],
        ];
    }

    /**
     * @param array $payload
     * @param array|int $expectation
     *
     * @test
     * @dataProvider meltArrayItemsDataProvider
     */
    public function meltArrayItemsIsExecuted(array $payload, $expectation): void
    {
        if (is_int($expectation)) {
            $this->expectException(\LogicException::class);
            $this->expectExceptionCode($expectation);
        }
        self::assertSame($expectation, PermutationUtility::meltArrayItems($payload));
    }
}
