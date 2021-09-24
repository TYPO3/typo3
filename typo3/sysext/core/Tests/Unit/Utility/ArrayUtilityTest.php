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

use TYPO3\CMS\Core\Tests\Unit\Utility\Fixtures\ArrayUtilityFilterRecursiveCallbackFixture;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 * @todo When further defining the method parameter types test bench errors occur
 */
class ArrayUtilityTest extends UnitTestCase
{
    ///////////////////////
    // Tests concerning filterByValueRecursive
    ///////////////////////
    /**
     * Data provider for filterByValueRecursiveCorrectlyFiltersArray
     *
     * Every array splits into:
     * - String value to search for
     * - Input array
     * - Expected result array
     */
    public function filterByValueRecursive(): array
    {
        return [
            'empty search array' => [
                'banana',
                [],
                [],
            ],
            'empty string as needle' => [
                '',
                [
                    '',
                    'apple',
                ],
                [
                    '',
                ],
            ],
            'flat array searching for string' => [
                'banana',
                [
                    'apple',
                    'banana',
                ],
                [
                    1 => 'banana',
                ],
            ],
            'flat array searching for string with two matches' => [
                'banana',
                [
                    'foo' => 'apple',
                    'firstbanana' => 'banana',
                    'secondbanana' => 'banana',
                ],
                [
                    'firstbanana' => 'banana',
                    'secondbanana' => 'banana',
                ],
            ],
            'multi dimensional array searching for string with multiple matches' => [
                'banana',
                [
                    'foo' => 'apple',
                    'firstbanana' => 'banana',
                    'grape' => [
                        'foo2' => 'apple2',
                        'secondbanana' => 'banana',
                        'foo3' => [],
                    ],
                    'bar' => 'orange',
                ],
                [
                    'firstbanana' => 'banana',
                    'grape' => [
                        'secondbanana' => 'banana',
                    ],
                ],
            ],
            'multi dimensional array searching for integer with multiple matches' => [
                42,
                [
                    'foo' => 23,
                    'bar' => 42,
                    [
                        'foo' => 23,
                        'bar' => 42,
                    ],
                ],
                [
                    'bar' => 42,
                    [
                        'bar' => 42,
                    ],
                ],
            ],
            'flat array searching for boolean TRUE' => [
                true,
                [
                    23 => false,
                    42 => true,
                ],
                [
                    42 => true,
                ],
            ],
            'multi dimensional array searching for boolean FALSE' => [
                false,
                [
                    23 => false,
                    42 => true,
                    'foo' => [
                        23 => false,
                        42 => true,
                    ],
                ],
                [
                    23 => false,
                    'foo' => [
                        23 => false,
                    ],
                ],
            ],
            'flat array searching for array' => [
                [
                    'foo' => 'bar',
                ],
                [
                    'foo' => 'bar',
                    'foobar' => [
                        'foo' => 'bar',
                    ],
                ],
                [
                    'foobar' => [
                        'foo' => 'bar',
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider filterByValueRecursive
     * @param array $needle
     * @param array $haystack
     * @param array $expectedResult
     */
    public function filterByValueRecursiveCorrectlyFiltersArray($needle, $haystack, $expectedResult): void
    {
        self::assertEquals(
            $expectedResult,
            ArrayUtility::filterByValueRecursive($needle, $haystack)
        );
    }

    /**
     * @test
     */
    public function filterByValueRecursiveMatchesReferencesToSameObject(): void
    {
        $instance = new \stdClass();
        self::assertEquals(
            [$instance],
            ArrayUtility::filterByValueRecursive($instance, [$instance])
        );
    }

    /**
     * @test
     */
    public function filterByValueRecursiveDoesNotMatchDifferentInstancesOfSameClass(): void
    {
        self::assertEquals(
            [],
            ArrayUtility::filterByValueRecursive(new \stdClass(), [new \stdClass()])
        );
    }

    ///////////////////////
    // Tests concerning isValidPath
    ///////////////////////
    /**
     * @test
     */
    public function isValidPathReturnsTrueIfPathExists(): void
    {
        self::assertTrue(ArrayUtility::isValidPath(['foo' => 'bar'], 'foo'));
    }

    /**
     * @test
     */
    public function isValidPathReturnsFalseIfPathDoesNotExist(): void
    {
        self::assertFalse(ArrayUtility::isValidPath(['foo' => 'bar'], 'bar'));
    }

    ///////////////////////
    // Tests concerning getValueByPath
    ///////////////////////
    /**
     * @test
     */
    public function getValueByPathThrowsExceptionIfPathIsNotString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1476557628);

        ArrayUtility::getValueByPath([], 123);
    }

    /**
     * @test
     */
    public function getValueByPathThrowsExceptionIfPathIsEmpty(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1341397767);

        ArrayUtility::getValueByPath([], '');
    }

    /**
     * @test
     */
    public function getValueByPathReturnsFirstIndexIfPathIsZero(): void
    {
        self::assertSame('foo', ArrayUtility::getValueByPath(['foo'], '0'));
    }

    /**
     * @test
     */
    public function getValueByPathReturnsFirstIndexIfPathSegmentIsZero(): void
    {
        self::assertSame('bar', ArrayUtility::getValueByPath(['foo' => ['bar']], 'foo/0'));
    }

    /**
     * Data provider for getValueByPathThrowsExceptionIfPathNotExists
     * Every array splits into:
     * - Array to get value from
     * - String path
     * - Expected result
     * @return array
     */
    public function getValueByPathInvalidPathDataProvider(): array
    {
        return [
            'not existing index' => [
                [
                    'foo' => ['foo'],
                ],
                'foo/1',
                false,
            ],
            'not existing path 1' => [
                [
                    'foo' => [],
                ],
                'foo/bar/baz',
                false,
            ],
            'not existing path 2' => [
                [
                    'foo' => [
                        'baz' => 42,
                    ],
                    'bar' => [],
                ],
                'foo/bar/baz',
                false,
            ],
            'last segment is not an array' => [
                [
                    'foo' => [
                        'baz' => 42,
                    ],
                ],
                'foo/baz/baz',
                false,
            ],
            // Negative test: This could be improved and the test moved to
            // the valid data provider if the method supports this
            'doubletick encapsulated quoted doubletick does not work' => [
                [
                    '"foo"bar"' => [
                        'baz' => 42,
                    ],
                    'bar' => [],
                ],
                '"foo\\"bar"/baz',
                42,
            ],
            // Negative test: Method could be improved here
            'path with doubletick does not work' => [
                [
                    'fo"o' => [
                        'bar' => 42,
                    ],
                ],
                'fo"o/foobar',
                42,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getValueByPathInvalidPathDataProvider
     * @param array $array
     * @param string $path
     */
    public function getValueByPathThrowsExceptionIfPathNotExists(array $array, $path): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1341397869);
        ArrayUtility::getValueByPath($array, $path);
    }

    /**
     * @test
     * @dataProvider getValueByPathInvalidPathDataProvider
     * @param array $array
     * @param string $path
     */
    public function getValueByPathThrowsSpecificExceptionIfPathNotExists(array $array, string $path): void
    {
        $this->expectException(MissingArrayPathException::class);
        $this->expectExceptionCode(1341397869);
        ArrayUtility::getValueByPath($array, $path);
    }

    /**
     * Data provider for getValueByPathReturnsCorrectValue
     * Every array splits into:
     * - Array to get value from
     * - String path
     * - Expected result
     */
    public function getValueByPathValidDataProvider(): array
    {
        $testObject = new \stdClass();
        $testObject->foo = 'foo';
        $testObject->bar = 'bar';
        return [
            'integer in multi level array' => [
                [
                    'foo' => [
                        'bar' => [
                            'baz' => 42,
                        ],
                        'bar2' => [],
                    ],
                ],
                'foo/bar/baz',
                42,
            ],
            'zero integer in multi level array' => [
                [
                    'foo' => [
                        'bar' => [
                            'baz' => 0,
                        ],
                    ],
                ],
                'foo/bar/baz',
                0,
            ],
            'NULL value in multi level array' => [
                [
                    'foo' => [
                        'baz' => null,
                    ],
                ],
                'foo/baz',
                null,
            ],
            'get string value' => [
                [
                    'foo' => [
                        'baz' => 'this is a test string',
                    ],
                ],
                'foo/baz',
                'this is a test string',
            ],
            'get boolean value: FALSE' => [
                [
                    'foo' => [
                        'baz' => false,
                    ],
                ],
                'foo/baz',
                false,
            ],
            'get boolean value: TRUE' => [
                [
                    'foo' => [
                        'baz' => true,
                    ],
                ],
                'foo/baz',
                true,
            ],
            'get object value' => [
                [
                    'foo' => [
                        'baz' => $testObject,
                    ],
                ],
                'foo/baz',
                $testObject,
            ],
            'sub array' => [
                [
                    'foo' => [
                        'bar' => [
                            'baz' => 42,
                        ],
                    ],
                ],
                'foo/bar',
                [
                    'baz' => 42,
                ],
            ],
            'enclosed path' => [
                [
                    'foo/bar' => [
                        'foobar' => 42,
                    ],
                ],
                '"foo/bar"/foobar',
                42,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getValueByPathValidDataProvider
     * @param array $array
     * @param string $path
     * @param mixed $expectedResult
     */
    public function getValueByPathGetsCorrectValue(array $array, $path, $expectedResult): void
    {
        self::assertEquals($expectedResult, ArrayUtility::getValueByPath($array, $path));
    }

    /**
     * @test
     */
    public function getValueByPathAcceptsDifferentDelimiter(): void
    {
        $input = [
            'foo' => [
                'bar' => [
                    'baz' => 42,
                ],
                'bar2' => [],
            ],
        ];
        $searchPath = 'foo%bar%baz';
        $expected = 42;
        $delimiter = '%';
        self::assertEquals(
            $expected,
            ArrayUtility::getValueByPath($input, $searchPath, $delimiter)
        );
    }

    ///////////////////////
    // Tests concerning setValueByPath
    ///////////////////////
    /**
     * @test
     */
    public function setValueByPathThrowsExceptionIfPathIsEmpty(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1341406194);

        ArrayUtility::setValueByPath([], '', null);
    }

    /**
     * @test
     */
    public function setValueByPathThrowsExceptionIfPathIsNotAString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1478781081);

        ArrayUtility::setValueByPath([], 123, null);
    }

    /**
     * @test
     */
    public function setValueByPathThrowsExceptionIfPathSegmentIsEmpty(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1341406846);

        ArrayUtility::setValueByPath(['foo' => 'bar'], '/foo', 'value');
    }

    /**
     * @test
     */
    public function setValueByPathCanUseZeroAsPathSegment(): void
    {
        self::assertSame(['foo' => ['value']], ArrayUtility::setValueByPath(['foo' => []], 'foo/0', 'value'));
    }

    /**
     * @test
     */
    public function setValueByPathCanUseZeroAsPath(): void
    {
        self::assertSame(['value', 'bar'], ArrayUtility::setValueByPath(['foo', 'bar'], '0', 'value'));
    }

    /**
     * Data provider for setValueByPathSetsCorrectValueDataProvider
     *
     * Every array splits into:
     * - Array to set value in
     * - String path
     * - Value to set
     * - Expected result
     */
    public function setValueByPathSetsCorrectValueDataProvider(): array
    {
        $testObject = new \stdClass();
        $testObject->foo = 'foo';
        $testObject->bar = 'bar';
        return [
            'set integer value: 42' => [
                [
                    'foo' => [
                        'bar' => [
                            'baz' => 0,
                        ],
                    ],
                ],
                'foo/bar/baz',
                42,
                [
                    'foo' => [
                        'bar' => [
                            'baz' => 42,
                        ],
                    ],
                ],
            ],
            'set integer value: 0' => [
                [
                    'foo' => [
                        'bar' => [
                            'baz' => 42,
                        ],
                    ],
                ],
                'foo/bar/baz',
                0,
                [
                    'foo' => [
                        'bar' => [
                            'baz' => 0,
                        ],
                    ],
                ],
            ],
            'set null value' => [
                [
                    'foo' => [
                        'bar' => [
                            'baz' => 42,
                        ],
                    ],
                ],
                'foo/bar/baz',
                null,
                [
                    'foo' => [
                        'bar' => [
                            'baz' => null,
                        ],
                    ],
                ],
            ],
            'set array value' => [
                [
                    'foo' => [
                        'bar' => [
                            'baz' => 42,
                        ],
                    ],
                ],
                'foo/bar/baz',
                [
                    'foo' => 123,
                ],
                [
                    'foo' => [
                        'bar' => [
                            'baz' => [
                                'foo' => 123,
                            ],
                        ],
                    ],
                ],
            ],
            'set boolean value: FALSE' => [
                [
                    'foo' => [
                        'bar' => [
                            'baz' => true,
                        ],
                    ],
                ],
                'foo/bar/baz',
                false,
                [
                    'foo' => [
                        'bar' => [
                            'baz' => false,
                        ],
                    ],
                ],
            ],
            'set boolean value: TRUE' => [
                [
                    'foo' => [
                        'bar' => [
                            'baz' => null,
                        ],
                    ],
                ],
                'foo/bar/baz',
                true,
                [
                    'foo' => [
                        'bar' => [
                            'baz' => true,
                        ],
                    ],
                ],
            ],
            'set object value' => [
                [
                    'foo' => [
                        'bar' => [
                            'baz' => null,
                        ],
                    ],
                ],
                'foo/bar/baz',
                $testObject,
                [
                    'foo' => [
                        'bar' => [
                            'baz' => $testObject,
                        ],
                    ],
                ],
            ],
            'multi keys in array' => [
                [
                    'foo' => [
                        'bar' => [
                            'baz' => 'value',
                        ],
                        'bar2' => [
                            'baz' => 'value',
                        ],
                    ],
                ],
                'foo/bar2/baz',
                'newValue',
                [
                    'foo' => [
                        'bar' => [
                            'baz' => 'value',
                        ],
                        'bar2' => [
                            'baz' => 'newValue',
                        ],
                    ],
                ],
            ],
            'setting longer path' => [
                [
                    'foo' => [
                        'bar' => 'value',
                    ],
                ],
                'foo/bar/baz/foobar',
                'newValue',
                [
                    'foo' => [
                        'bar' => [
                            'baz' => [
                                'foobar' => 'newValue',
                            ],
                        ],
                    ],
                ],
            ],
            'setting longer path in existing array' => [
                [
                    'foo' => [
                        'bar' => [
                            'existingKey' => 'lolli.did.this',
                        ],
                    ],
                ],
                'foo/bar/baz/foobar',
                'newValue',
                [
                    'foo' => [
                        'bar' => [
                            'existingKey' => 'lolli.did.this',
                            'baz' => [
                                'foobar' => 'newValue',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider setValueByPathSetsCorrectValueDataProvider
     * @param array $array
     * @param string $path
     * @param string $value
     * @param array $expectedResult
     */
    public function setValueByPathSetsCorrectValue(array $array, $path, $value, $expectedResult): void
    {
        self::assertEquals(
            $expectedResult,
            ArrayUtility::setValueByPath($array, $path, $value)
        );
    }

    /**********************
    /* Tests concerning removeByPath
     ***********************/

    /**
     * @test
     */
    public function removeByPathThrowsExceptionIfPathIsEmpty(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1371757718);

        ArrayUtility::removeByPath([], '');
    }

    /**
     * @test
     */
    public function removeByPathThrowsExceptionIfPathIsNotAString(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1371757719);

        ArrayUtility::removeByPath([], ['foo']);
    }

    /**
     * @test
     */
    public function removeByPathThrowsExceptionWithEmptyPathSegment(): void
    {
        $inputArray = [
            'foo' => [
                'bar' => 42,
            ],
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1371757720);

        ArrayUtility::removeByPath($inputArray, 'foo//bar');
    }

    /**
     * @test
     */
    public function removeByPathRemovesFirstIndexWithZeroAsPathSegment(): void
    {
        $inputArray = [
            'foo' => ['bar'],
        ];

        self::assertSame(['foo' => []], ArrayUtility::removeByPath($inputArray, 'foo/0'));
    }

    /**
     * @test
     */
    public function removeByPathRemovesFirstIndexWithZeroAsPath(): void
    {
        $inputArray = ['bar'];

        self::assertSame([], ArrayUtility::removeByPath($inputArray, '0'));
    }

    /**
     * @test
     */
    public function removeByPathThrowsExceptionIfPathDoesNotExistInArray(): void
    {
        $inputArray = [
            'foo' => [
                'bar' => 42,
            ],
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1371758436);

        ArrayUtility::removeByPath($inputArray, 'foo/baz');
    }

    /**
     * @test
     */
    public function removeByPathThrowsSpecificExceptionIfPathDoesNotExistInArray(): void
    {
        $inputArray = [
            'foo' => [
                'bar' => 42,
            ],
        ];

        $this->expectException(MissingArrayPathException::class);
        $this->expectExceptionCode(1371758436);

        ArrayUtility::removeByPath($inputArray, 'foo/baz');
    }

    /**
     * @test
     */
    public function removeByPathAcceptsGivenDelimiter(): void
    {
        $inputArray = [
            'foo' => [
                'toRemove' => 42,
                'keep' => 23,
            ],
        ];
        $path = 'foo.toRemove';
        $expected = [
            'foo' => [
                'keep' => 23,
            ],
        ];
        self::assertEquals(
            $expected,
            ArrayUtility::removeByPath($inputArray, $path, '.')
        );
    }

    /**
     * Data provider for removeByPathRemovesCorrectPath
     */
    public function removeByPathRemovesCorrectPathDataProvider(): array
    {
        return [
            'single value' => [
                [
                    'foo' => [
                        'toRemove' => 42,
                        'keep' => 23,
                    ],
                ],
                'foo/toRemove',
                [
                    'foo' => [
                        'keep' => 23,
                    ],
                ],
            ],
            'whole array' => [
                [
                    'foo' => [
                        'bar' => 42,
                    ],
                ],
                'foo',
                [],
            ],
            'sub array' => [
                [
                    'foo' => [
                        'keep' => 23,
                        'toRemove' => [
                            'foo' => 'bar',
                        ],
                    ],
                ],
                'foo/toRemove',
                [
                    'foo' => [
                        'keep' => 23,
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider removeByPathRemovesCorrectPathDataProvider
     * @param array $array
     * @param string $path
     * @param array $expectedResult
     */
    public function removeByPathRemovesCorrectPath(array $array, $path, $expectedResult): void
    {
        self::assertEquals(
            $expectedResult,
            ArrayUtility::removeByPath($array, $path)
        );
    }

    ///////////////////////
    // Tests concerning sortByKeyRecursive
    ///////////////////////
    /**
     * @test
     */
    public function sortByKeyRecursiveCheckIfSortingIsCorrect(): void
    {
        $unsortedArray = [
            'z' => null,
            'a' => null,
            'd' => [
                'c' => null,
                'b' => null,
                'd' => null,
                'a' => null,
            ],
        ];
        $expectedResult = [
            'a' => null,
            'd' => [
                'a' => null,
                'b' => null,
                'c' => null,
                'd' => null,
            ],
            'z' => null,
        ];
        self::assertSame($expectedResult, ArrayUtility::sortByKeyRecursive($unsortedArray));
    }

    ///////////////////////
    // Tests concerning sortArraysByKey
    ///////////////////////
    /**
     * Data provider for sortArraysByKeyCheckIfSortingIsCorrect
     */
    public function sortArraysByKeyCheckIfSortingIsCorrectDataProvider(): array
    {
        return [
            'assoc array index' => [
                [
                    '22' => [
                        'uid' => '22',
                        'title' => 'c',
                        'dummy' => 2,
                    ],
                    '24' => [
                        'uid' => '24',
                        'title' => 'a',
                        'dummy' => 3,
                    ],
                    '23' => [
                        'uid' => '23',
                        'title' => 'b',
                        'dummy' => 4,
                    ],
                ],
                'title',
                true,
                [
                    '24' => [
                        'uid' => '24',
                        'title' => 'a',
                        'dummy' => 3,
                    ],
                    '23' => [
                        'uid' => '23',
                        'title' => 'b',
                        'dummy' => 4,
                    ],
                    '22' => [
                        'uid' => '22',
                        'title' => 'c',
                        'dummy' => 2,
                    ],
                ],
            ],
            'numeric array index' => [
                [
                    22 => [
                        'uid' => '22',
                        'title' => 'c',
                        'dummy' => 2,
                    ],
                    24 => [
                        'uid' => '24',
                        'title' => 'a',
                        'dummy' => 3,
                    ],
                    23 => [
                        'uid' => '23',
                        'title' => 'b',
                        'dummy' => 4,
                    ],
                ],
                'title',
                true,
                [
                    24 => [
                        'uid' => '24',
                        'title' => 'a',
                        'dummy' => 3,
                    ],
                    23 => [
                        'uid' => '23',
                        'title' => 'b',
                        'dummy' => 4,
                    ],
                    22 => [
                        'uid' => '22',
                        'title' => 'c',
                        'dummy' => 2,
                    ],
                ],
            ],
            'numeric array index DESC' => [
                [
                    23 => [
                        'uid' => '23',
                        'title' => 'b',
                        'dummy' => 4,
                    ],
                    22 => [
                        'uid' => '22',
                        'title' => 'c',
                        'dummy' => 2,
                    ],
                    24 => [
                        'uid' => '24',
                        'title' => 'a',
                        'dummy' => 3,
                    ],
                ],
                'title',
                false,
                [
                    22 => [
                        'uid' => '22',
                        'title' => 'c',
                        'dummy' => 2,
                    ],
                    23 => [
                        'uid' => '23',
                        'title' => 'b',
                        'dummy' => 4,
                    ],
                    24 => [
                        'uid' => '24',
                        'title' => 'a',
                        'dummy' => 3,
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider sortArraysByKeyCheckIfSortingIsCorrectDataProvider
     * @param array $array
     * @param string $key
     * @param bool $ascending
     * @param array $expectedResult
     */
    public function sortArraysByKeyCheckIfSortingIsCorrect(array $array, $key, $ascending, $expectedResult): void
    {
        $sortedArray = ArrayUtility::sortArraysByKey($array, $key, $ascending);
        self::assertSame($expectedResult, $sortedArray);
    }

    /**
     * @test
     */
    public function sortArraysByKeyThrowsExceptionForNonExistingKey(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1373727309);

        ArrayUtility::sortArraysByKey([['a'], ['a']], 'dummy');
    }

    ///////////////////////
    // Tests concerning arrayExport
    ///////////////////////
    /**
     * @test
     */
    public function arrayExportReturnsFormattedMultidimensionalArray(): void
    {
        $array = [
            'foo' => [
                'bar' => 42,
                'bar2' => [
                    'baz' => 'val\'ue',
                    'baz2' => true,
                    'baz3' => false,
                    'baz4' => [],
                ],
            ],
            'baz' => 23,
            'foobar' => null,
            'qux' => 0.1,
            'qux2' => 0.000000001,
        ];
        $expected =
            '[' . LF .
                '    \'foo\' => [' . LF .
                    '        \'bar\' => 42,' . LF .
                    '        \'bar2\' => [' . LF .
                        '            \'baz\' => \'val\\\'ue\',' . LF .
                        '            \'baz2\' => true,' . LF .
                        '            \'baz3\' => false,' . LF .
                        '            \'baz4\' => [],' . LF .
                    '        ],' . LF .
                '    ],' . LF .
                '    \'baz\' => 23,' . LF .
                '    \'foobar\' => null,' . LF .
                '    \'qux\' => 0.1,' . LF .
                '    \'qux2\' => 1.0E-9,' . LF .
            ']';
        self::assertSame($expected, ArrayUtility::arrayExport($array));
    }

    /**
     * @test
     */
    public function arrayExportThrowsExceptionIfObjectShouldBeExported(): void
    {
        $array = [
            'foo' => [
                'bar' => new \stdClass(),
            ],
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1342294987);

        ArrayUtility::arrayExport($array);
    }

    /**
     * @test
     */
    public function arrayExportReturnsNumericArrayKeys(): void
    {
        $array = [
            'foo' => 'string key',
            23 => 'integer key',
            '42' => 'string key representing integer',
        ];
        $expected =
            '[' . LF .
                '    \'foo\' => \'string key\',' . LF .
                '    23 => \'integer key\',' . LF .
                '    42 => \'string key representing integer\',' . LF .
            ']';
        self::assertSame($expected, ArrayUtility::arrayExport($array));
    }

    /**
     * @test
     */
    public function arrayExportReturnsNoKeyIndexForConsecutiveCountedArrays(): void
    {
        $array = [
            0 => 'zero',
            1 => 'one',
            2 => 'two',
        ];
        $expected =
            '[' . LF .
                '    \'zero\',' . LF .
                '    \'one\',' . LF .
                '    \'two\',' . LF .
            ']';
        self::assertSame($expected, ArrayUtility::arrayExport($array));
    }

    /**
     * @test
     */
    public function arrayExportReturnsKeyIndexForNonConsecutiveCountedArrays(): void
    {
        $array = [
            0 => 'zero',
            1 => 'one',
            3 => 'three',
            4 => 'four',
        ];
        $expected =
            '[' . LF .
                '    0 => \'zero\',' . LF .
                '    1 => \'one\',' . LF .
                '    3 => \'three\',' . LF .
                '    4 => \'four\',' . LF .
            ']';
        self::assertSame($expected, ArrayUtility::arrayExport($array));
    }

    ///////////////////////
    // Tests concerning flatten
    ///////////////////////

    /**
     * @return array
     */
    public function flattenCalculatesExpectedResultDataProvider(): array
    {
        return [
            'plain array' => [
                [
                    'first' => 1,
                    'second' => 2,
                ],
                [
                    'first' => 1,
                    'second' => 2,
                ],
            ],
            'plain array with faulty dots' => [
                [
                    'first.' => 1,
                    'second.' => 2,
                ],
                [
                    'first' => 1,
                    'second' => 2,
                ],
            ],
            'nested array of 2 levels' => [
                [
                    'first.' => [
                        'firstSub' => 1,
                    ],
                    'second.' => [
                        'secondSub' => 2,
                    ],
                ],
                [
                    'first.firstSub' => 1,
                    'second.secondSub' => 2,
                ],
            ],
            'nested array of 2 levels with faulty dots' => [
                [
                    'first.' => [
                        'firstSub.' => 1,
                    ],
                    'second.' => [
                        'secondSub.' => 2,
                    ],
                ],
                [
                    'first.firstSub' => 1,
                    'second.secondSub' => 2,
                ],
            ],
            'nested array of 3 levels' => [
                [
                    'first.' => [
                        'firstSub.' => [
                            'firstSubSub' => 1,
                        ],
                    ],
                    'second.' => [
                        'secondSub.' => [
                            'secondSubSub' => 2,
                        ],
                    ],
                ],
                [
                    'first.firstSub.firstSubSub' => 1,
                    'second.secondSub.secondSubSub' => 2,
                ],
            ],
            'nested array of 3 levels with faulty dots' => [
                [
                    'first.' => [
                        'firstSub.' => [
                            'firstSubSub.' => 1,
                        ],
                    ],
                    'second.' => [
                        'secondSub.' => [
                            'secondSubSub.' => 2,
                        ],
                    ],
                ],
                [
                    'first.firstSub.firstSubSub' => 1,
                    'second.secondSub.secondSubSub' => 2,
                ],
            ],
        ];
    }

    /**
     * @test
     * @param array $array
     * @param array $expected
     * @dataProvider flattenCalculatesExpectedResultDataProvider
     */
    public function flattenCalculatesExpectedResult(array $array, array $expected): void
    {
        self::assertEquals($expected, ArrayUtility::flatten($array));
    }

    /**
     * @return array
     */
    public function flattenWithKeepDotsCalculatesExpectedResultDataProvider(): array
    {
        return [
            'plain array' => [
                [
                    'first' => 1,
                    'second' => 2,
                ],
                [
                    'first' => 1,
                    'second' => 2,
                ],
            ],
            'plain array with dots' => [
                [
                    'first.' => 1,
                    'second.' => 2,
                ],
                [
                    'first.' => 1,
                    'second.' => 2,
                ],
            ],
            'nested array of 2 levels' => [
                [
                    'first.' => [
                        'firstSub' => 1,
                    ],
                    'second.' => [
                        'secondSub' => 2,
                    ],
                ],
                [
                    'first.firstSub' => 1,
                    'second.secondSub' => 2,
                ],
            ],
            'nested array of 2 levels with dots' => [
                [
                    'first.' => [
                        'firstSub.' => 1,
                    ],
                    'second.' => [
                        'secondSub.' => 2,
                    ],
                ],
                [
                    'first.firstSub.' => 1,
                    'second.secondSub.' => 2,
                ],
            ],
            'nested array of 3 levels' => [
                [
                    'first.' => [
                        'firstSub.' => [
                            'firstSubSub' => 1,
                        ],
                    ],
                    'second.' => [
                        'secondSub.' => [
                            'secondSubSub' => 2,
                        ],
                    ],
                ],
                [
                    'first.firstSub.firstSubSub' => 1,
                    'second.secondSub.secondSubSub' => 2,
                ],
            ],
            'nested array of 3 levels with dots' => [
                [
                    'first.' => [
                        'firstSub.' => [
                            'firstSubSub.' => 1,
                        ],
                    ],
                    'second.' => [
                        'secondSub.' => [
                            'secondSubSub.' => 2,
                        ],
                    ],
                ],
                [
                    'first.firstSub.firstSubSub.' => 1,
                    'second.secondSub.secondSubSub.' => 2,
                ],
            ],
            'nested array of 3 levels with multi dots' => [
                [
                    'first.' => [
                        'firstSub..' => [
                            'firstSubSub..' => 1,
                        ],
                    ],
                    'second.' => [
                        'secondSub..' => [
                            'secondSubSub.' => 2,
                        ],
                    ],
                ],
                [
                    'first.firstSub..firstSubSub..' => 1,
                    'second.secondSub..secondSubSub.' => 2,
                ],
            ],
        ];
    }

    /**
     * @test
     * @param array $array
     * @param array $expected
     * @dataProvider flattenWithKeepDotsCalculatesExpectedResultDataProvider
     */
    public function flattenWithKeepDotsCalculatesExpectedResult(array $array, array $expected): void
    {
        self::assertEquals($expected, ArrayUtility::flatten($array, '', true));
    }

    ///////////////////////
    // Tests concerning intersectRecursive
    ///////////////////////

    /**
     * @return array
     */
    public function intersectRecursiveCalculatesExpectedResultDataProvider(): array
    {
        $sameObject = new \stdClass();
        return [
            // array($source, $mask, $expected)
            'empty array is returned if source is empty array' => [
                [],
                [
                    'foo' => 'bar',
                ],
                [],
            ],
            'empty array is returned if mask is empty' => [
                [
                    'foo' => 'bar',
                ],
                [],
                [],
            ],
            'key is kept on first level if exists in mask' => [
                [
                    'foo' => 42,
                ],
                [
                    'foo' => 42,
                ],
                [
                    'foo' => 42,
                ],
            ],
            'value of key in source is kept if mask has different value' => [
                [
                    'foo' => 42,
                ],
                [
                    'foo' => new \stdClass(),
                ],
                [
                    'foo' => 42,
                ],
            ],
            'key is kept on first level if according mask value is NULL' => [
                [
                    'foo' => 42,
                ],
                [
                    'foo' => null,
                ],
                [
                    'foo' => 42,
                ],
            ],
            'null in source value is kept' => [
                [
                    'foo' => null,
                ],
                [
                    'foo' => 'bar',
                ],
                [
                    'foo' => null,
                ],
            ],
            'mask does not add new keys' => [
                [
                    'foo' => 42,
                ],
                [
                    'foo' => 23,
                    'bar' => [
                        4711,
                    ],
                ],
                [
                    'foo' => 42,
                ],
            ],
            'mask does not overwrite simple values with arrays' => [
                [
                    'foo' => 42,
                ],
                [
                    'foo' => [
                        'bar' => 23,
                    ],
                ],
                [
                    'foo' => 42,
                ],
            ],
            'key is kept on first level if according mask value is array' => [
                [
                    'foo' => 42,
                ],
                [
                    'foo' => [
                        'bar' => 23,
                    ],
                ],
                [
                    'foo' => 42,
                ],
            ],
            'full array is kept if value is array and mask value is simple type' => [
                [
                    'foo' => [
                        'bar' => 23,
                    ],
                ],
                [
                    'foo' => 42,
                ],
                [
                    'foo' => [
                        'bar' => 23,
                    ],
                ],
            ],
            'key handling is type agnostic' => [
                [
                    42 => 'foo',
                ],
                [
                    '42' => 'bar',
                ],
                [
                    42 => 'foo',
                ],
            ],
            'value is same if value is object' => [
                [
                    'foo' => $sameObject,
                ],
                [
                    'foo' => 'something',
                ],
                [
                    'foo' => $sameObject,
                ],
            ],
            'mask does not add simple value to result if key does not exist in source' => [
                [
                    'foo' => '42',
                ],
                [
                    'foo' => '42',
                    'bar' => 23,
                ],
                [
                    'foo' => '42',
                ],
            ],
            'array of source is kept if value of mask key exists but is no array' => [
                [
                    'foo' => '42',
                    'bar' => [
                        'baz' => 23,
                    ],
                ],
                [
                    'foo' => 'value is not significant',
                    'bar' => null,
                ],
                [
                    'foo' => '42',
                    'bar' => [
                        'baz' => 23,
                    ],
                ],
            ],
            'sub arrays are kept if mask has according sub array key and is similar array' => [
                [
                    'first1' => 42,
                    'first2' => [
                        'second1' => 23,
                        'second2' => 4711,
                    ],
                ],
                [
                    'first1' => 42,
                    'first2' => [
                        'second1' => 'exists but different',
                    ],
                ],
                [
                    'first1' => 42,
                    'first2' => [
                        'second1' => 23,
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @param array $source
     * @param array $mask
     * @param array $expected
     * @dataProvider intersectRecursiveCalculatesExpectedResultDataProvider
     */
    public function intersectRecursiveCalculatesExpectedResult(array $source, array $mask, array $expected): void
    {
        self::assertSame($expected, ArrayUtility::intersectRecursive($source, $mask));
    }

    ///////////////////////
    // Tests concerning renumberKeysToAvoidLeapsIfKeysAreAllNumeric
    ///////////////////////
    /**
     * @return array
     */
    public function renumberKeysToAvoidLeapsIfKeysAreAllNumericDataProvider(): array
    {
        return [
            'empty array is returned if source is empty array' => [
                [],
                [],
            ],
            'returns self if array is already numerically keyed' => [
                [1, 2, 3],
                [1, 2, 3],
            ],
            'returns correctly if keys are numeric, but contains a leap' => [
                [0 => 'One', 1 => 'Two', 3 => 'Three'],
                [0 => 'One', 1 => 'Two', 2 => 'Three'],
            ],
            'returns correctly even though keys are strings but still numeric' => [
                ['0' => 'One', '1' => 'Two', '3' => 'Three'],
                [0 => 'One', 1 => 'Two', 2 => 'Three'],
            ],
            'returns correctly if just a single keys is not numeric' => [
                [0 => 'Zero', '1' => 'One', 'Two' => 'Two'],
                [0 => 'Zero', '1' => 'One', 'Two' => 'Two'],
            ],
            'returns unchanged if keys end with a dot' => [
                ['2.' => 'Two', '1.' => 'One', '0.' => 'Zero'],
                ['2.' => 'Two', '1.' => 'One', '0.' => 'Zero'],
            ],
            'return self with nested numerically keyed array' => [
                [
                    'One',
                    'Two',
                    'Three',
                    [
                        'sub.One',
                        'sub.Two',
                    ],
                ],
                [
                    'One',
                    'Two',
                    'Three',
                    [
                        'sub.One',
                        'sub.Two',
                    ],
                ],
            ],
            'returns correctly with nested numerically keyed array with leaps' => [
                [
                    'One',
                    'Two',
                    'Three',
                    [
                        0 => 'sub.One',
                        2 => 'sub.Two',
                    ],
                ],
                [
                    'One',
                    'Two',
                    'Three',
                    [
                        'sub.One',
                        'sub.Two',
                    ],
                ],
            ],
            'returns correctly with nested string-keyed array' => [
                [
                    'One',
                    'Two',
                    'Three',
                    [
                        'one' => 'sub.One',
                        'two' => 'sub.Two',
                    ],
                ],
                [
                    'One',
                    'Two',
                    'Three',
                    [
                        'one' => 'sub.One',
                        'two' => 'sub.Two',
                    ],
                ],
            ],
            'returns correctly with deeply nested arrays' => [
                [
                    'One',
                    'Two',
                    [
                        'one' => 1,
                        'two' => 2,
                        'three' => [
                            2 => 'SubSubOne',
                            5 => 'SubSubTwo',
                            9 => [0, 1, 2],
                            [],
                        ],
                    ],
                ],
                [
                    'One',
                    'Two',
                    [
                        'one' => 1,
                        'two' => 2,
                        'three' => [
                            'SubSubOne',
                            'SubSubTwo',
                            [0, 1, 2],
                            [],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @param array $inputArray
     * @param array $expected
     * @dataProvider renumberKeysToAvoidLeapsIfKeysAreAllNumericDataProvider
     */
    public function renumberKeysToAvoidLeapsIfKeysAreAllNumericReturnsExpectedOrder(array $inputArray, array $expected): void
    {
        self::assertEquals($expected, ArrayUtility::renumberKeysToAvoidLeapsIfKeysAreAllNumeric($inputArray));
    }

    /**
     * @return array
     */
    public function mergeRecursiveWithOverruleCalculatesExpectedResultDataProvider(): array
    {
        return [
            'Override array can reset string to array' => [
                [
                    'first' => [
                        'second' => 'foo',
                    ],
                ],
                [
                    'first' => [
                        'second' => ['third' => 'bar'],
                    ],
                ],
                true,
                true,
                true,
                [
                    'first' => [
                        'second' => ['third' => 'bar'],
                    ],
                ],
            ],
            'Override array does not reset array to string (weird!)' => [
                [
                    'first' => [],
                ],
                [
                    'first' => 'foo',
                ],
                true,
                true,
                true,
                [
                    'first' => [], // This is rather unexpected, naive expectation: first => 'foo'
                ],
            ],
            'Override array does override string with null' => [
                [
                    'first' => 'foo',
                ],
                [
                    'first' => null,
                ],
                true,
                true,
                true,
                [
                    'first' => null,
                ],
            ],
            'Override array does override null with string' => [
                [
                    'first' => null,
                ],
                [
                    'first' => 'foo',
                ],
                true,
                true,
                true,
                [
                    'first' => 'foo',
                ],
            ],
            'Override array does override null with empty string' => [
                [
                    'first' => null,
                ],
                [
                    'first' => '',
                ],
                true,
                true,
                true,
                [
                    'first' => '',
                ],
            ],
            'Override array does not override string with NULL if requested' => [
                [
                    'first' => 'foo',
                ],
                [
                    'first' => null,
                ],
                true,
                false, // no include empty values
                true,
                [
                    'first' => 'foo',
                ],
            ],
            'Override array does override null with null' => [
                [
                    'first' => null,
                ],
                [
                    'first' => null,
                ],
                true,
                true,
                true,
                [
                    'first' => '',
                ],
            ],
            'Override array can __UNSET values' => [
                [
                    'first' => [
                        'second' => 'second',
                        'third' => 'third',
                    ],
                    'fifth' => [],
                ],
                [
                    'first' => [
                        'second' => 'overrule',
                        'third' => '__UNSET',
                        'fourth' => 'overrile',
                    ],
                    'fifth' => '__UNSET',
                ],
                true,
                true,
                true,
                [
                    'first' => [
                        'second' => 'overrule',
                        'fourth' => 'overrile',
                    ],
                ],
            ],
            'Override can add keys' => [
                [
                    'first' => 'foo',
                ],
                [
                    'second' => 'bar',
                ],
                true,
                true,
                true,
                [
                    'first' => 'foo',
                    'second' => 'bar',
                ],
            ],
            'Override does not add key if __UNSET' => [
                [
                    'first' => 'foo',
                ],
                [
                    'second' => '__UNSET',
                ],
                true,
                true,
                true,
                [
                    'first' => 'foo',
                ],
            ],
            'Override does not add key if not requested' => [
                [
                    'first' => 'foo',
                ],
                [
                    'second' => 'bar',
                ],
                false, // no add keys
                true,
                true,
                [
                    'first' => 'foo',
                ],
            ],
            'Override does not add key if not requested with add include empty values' => [
                [
                    'first' => 'foo',
                ],
                [
                    'second' => 'bar',
                ],
                false, // no add keys
                false, // no include empty values
                true,
                [
                    'first' => 'foo',
                ],
            ],
            'Override does not override string with empty string if requested' => [
                [
                    'first' => 'foo',
                ],
                [
                    'first' => '',
                ],
                true,
                false, // no include empty values
                true,
                [
                    'first' => 'foo',
                ],
            ],
            'Override array does merge instead of __UNSET if requested (weird!)' => [
                [
                    'first' => [
                        'second' => 'second',
                        'third' => 'third',
                    ],
                    'fifth' => [],
                ],
                [
                    'first' => [
                        'second' => 'overrule',
                        'third' => '__UNSET',
                        'fourth' => 'overrile',
                    ],
                    'fifth' => '__UNSET',
                ],
                true,
                true,
                false,
                [
                    'first' => [
                        'second' => 'overrule',
                        'third' => '__UNSET', // overruled
                        'fourth' => 'overrile',
                    ],
                    'fifth' => [], // not overruled with string here, naive expectation: 'fifth' => '__UNSET'
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider mergeRecursiveWithOverruleCalculatesExpectedResultDataProvider
     * @param array $input1 Input 1
     * @param array $input2 Input 2
     * @param bool $addKeys TRUE if should add keys, else FALSE
     * @param bool $includeEmptyValues TRUE if should include empty values, else FALSE
     * @param bool $enableUnsetFeature TRUE if should enable unset feature, else FALSE
     * @param array $expected expected array
     */
    public function mergeRecursiveWithOverruleCalculatesExpectedResult($input1, $input2, $addKeys, $includeEmptyValues, $enableUnsetFeature, $expected): void
    {
        ArrayUtility::mergeRecursiveWithOverrule($input1, $input2, $addKeys, $includeEmptyValues, $enableUnsetFeature);
        self::assertEquals($expected, $input1);
    }

    //////////////////////////////////
    // Tests concerning removeArrayEntryByValue
    //////////////////////////////////
    /**
     * @test
     */
    public function checkRemoveArrayEntryByValueRemovesEntriesFromOneDimensionalArray(): void
    {
        $inputArray = [
            '0' => 'test1',
            '1' => 'test2',
            '2' => 'test3',
            '3' => 'test2',
        ];
        $compareValue = 'test2';
        $expectedResult = [
            '0' => 'test1',
            '2' => 'test3',
        ];
        $actualResult = ArrayUtility::removeArrayEntryByValue($inputArray, $compareValue);
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function checkRemoveArrayEntryByValueRemovesEntriesFromMultiDimensionalArray(): void
    {
        $inputArray = [
            '0' => 'foo',
            '1' => [
                '10' => 'bar',
            ],
            '2' => 'bar',
        ];
        $compareValue = 'bar';
        $expectedResult = [
            '0' => 'foo',
            '1' => [],
        ];
        $actualResult = ArrayUtility::removeArrayEntryByValue($inputArray, $compareValue);
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function checkRemoveArrayEntryByValueRemovesEntryWithEmptyString(): void
    {
        $inputArray = [
            '0' => 'foo',
            '1' => '',
            '2' => 'bar',
        ];
        $compareValue = '';
        $expectedResult = [
            '0' => 'foo',
            '2' => 'bar',
        ];
        $actualResult = ArrayUtility::removeArrayEntryByValue($inputArray, $compareValue);
        self::assertEquals($expectedResult, $actualResult);
    }

    //////////////////////////////////
    // Tests concerning keepItemsInArray
    //////////////////////////////////
    /**
     * @test
     * @dataProvider keepItemsInArrayWorksWithOneArgumentDataProvider
     * @param mixed $search The items which are allowed/kept in the array
     * @param array $array target array
     * @param array $expected expected array
     */
    public function keepItemsInArrayWorksWithOneArgument($search, $array, $expected): void
    {
        self::assertEquals($expected, ArrayUtility::keepItemsInArray($array, $search));
    }

    /**
     * Data provider for keepItemsInArrayWorksWithOneArgument
     *
     * @return array
     */
    public function keepItemsInArrayWorksWithOneArgumentDataProvider(): array
    {
        $array = [
            0 => 0,
            'one' => 'one',
            'two' => 'two',
            'three' => 'three',
        ];
        return [
            'Empty argument will match "all" elements' => [null, $array, $array],
            'No match' => ['four', $array, []],
            'One match' => ['two', $array, ['two' => 'two']],
            'Multiple matches' => ['two,one', $array, ['one' => 'one', 'two' => 'two']],
            'Argument can be an array' => [['three'], $array, ['three' => 'three']],
        ];
    }

    /**
     * Shows the example from the doc comment where
     * a function is used to reduce the sub arrays to one item which
     * is then used for the matching.
     *
     * @test
     */
    public function keepItemsInArrayCanUseClosure(): void
    {
        $array = [
            'aa' => ['first', 'second'],
            'bb' => ['third', 'fourth'],
            'cc' => ['fifth', 'sixth'],
        ];
        $expected = ['bb' => ['third', 'fourth']];
        $keepItems = 'third';
        $match = ArrayUtility::keepItemsInArray(
            $array,
            $keepItems,
            static function ($value) {
                return $value[0];
            }
        );
        self::assertEquals($expected, $match);
    }

    //////////////////////////////////
    // Tests concerning remapArrayKeys
    //////////////////////////////////
    /**
     * @test
     */
    public function remapArrayKeysExchangesKeysWithGivenMapping(): void
    {
        $array = [
            'one' => 'one',
            'two' => 'two',
            'three' => 'three',
        ];
        $keyMapping = [
            'one' => '1',
            'two' => '2',
        ];
        $expected = [
            '1' => 'one',
            '2' => 'two',
            'three' => 'three',
        ];
        ArrayUtility::remapArrayKeys($array, $keyMapping);
        self::assertEquals($expected, $array);
    }

    //////////////////////////////////////
    // Tests concerning arrayDiffKeyRecursive
    //////////////////////////////////////
    /**
     * @test
     */
    public function arrayDiffKeyRecursiveHandlesOneDimensionalArrays(): void
    {
        $array1 = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];
        $array2 = [
            'key1' => 'value1',
            'key3' => 'value3',
        ];
        $expectedResult = [
            'key2' => 'value2',
        ];
        $actualResult = ArrayUtility::arrayDiffKeyRecursive($array1, $array2);
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function arrayDiffKeyRecursiveHandlesMultiDimensionalArrays(): void
    {
        $array1 = [
            'key1' => 'value1',
            'key2' => [
                'key21' => 'value21',
                'key22' => 'value22',
                'key23' => [
                    'key231' => 'value231',
                    'key232' => 'value232',
                ],
            ],
        ];
        $array2 = [
            'key1' => 'valueDoesNotMatter',
            'key2' => [
                'key21' => 'value21',
                'key23' => [
                    'key231' => 'value231',
                ],
            ],
        ];
        $expectedResult = [
            'key2' => [
                'key22' => 'value22',
                'key23' => [
                    'key232' => 'value232',
                ],
            ],
        ];
        $actualResult = ArrayUtility::arrayDiffKeyRecursive($array1, $array2);
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function arrayDiffKeyRecursiveHandlesMixedArrays(): void
    {
        $array1 = [
            'key1' => [
                'key11' => 'value11',
                'key12' => 'value12',
            ],
            'key2' => 'value2',
            'key3' => 'value3',
        ];
        $array2 = [
            'key1' => 'value1',
            'key2' => [
                'key21' => 'valueDoesNotMatter',
            ],
        ];
        $expectedResult = [
            'key3' => 'value3',
        ];
        $actualResult = ArrayUtility::arrayDiffKeyRecursive($array1, $array2);
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function arrayDiffKeyRecursiveReturnsEmptyIfEqual(): void
    {
        $array1 = [
            'key1' => [
                'key11' => 'value11',
                'key12' => 'value12',
            ],
            'key2' => 'value2',
            'key3' => 'value3',
        ];
        $array2 = [
            'key1' => [
                'key11' => 'valueDoesNotMatter',
                'key12' => 'value12',
            ],
            'key2' => 'value2',
            'key3' => 'value3',
        ];
        $expectedResult = [];
        $actualResult = ArrayUtility::arrayDiffKeyRecursive($array1, $array2);
        self::assertEquals($expectedResult, $actualResult);
    }

    //////////////////////////////////////
    // Tests concerning arrayDiffAssocRecursive
    //////////////////////////////////////
    /**
     * @test
     */
    public function arrayDiffAssocRecursiveHandlesOneDimensionalArrays(): void
    {
        $array1 = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];
        $array2 = [
            'key1' => 'value1',
            'key3' => 'value3',
        ];
        $expectedResult = [
            'key2' => 'value2',
        ];
        $actualResult = ArrayUtility::arrayDiffAssocRecursive($array1, $array2, true);
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function arrayDiffAssocRecursiveHandlesMultiDimensionalArrays(): void
    {
        $array1 = [
            'key1' => 'value1',
            'key2' => [
                'key21' => 'value21',
                'key22' => 'value22',
                'key23' => [
                    'key231' => 'value231',
                    'key232' => 'value232',
                ],
            ],
        ];
        $array2 = [
            'key1' => 'value2',
            'key2' => [
                'key21' => 'value21',
                'key23' => [
                    'key231' => 'value231',
                ],
            ],
        ];
        $expectedResult = [
            'key1' => 'value1',
            'key2' => [
                'key22' => 'value22',
                'key23' => [
                    'key232' => 'value232',
                ],
            ],
        ];
        $actualResult = ArrayUtility::arrayDiffAssocRecursive($array1, $array2, true);
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function arrayDiffAssocRecursiveHandlesMixedArrays(): void
    {
        $array1 = [
            'key1' => [
                'key11' => 'value11',
                'key12' => 'value12',
            ],
            'key2' => 'value2',
            'key3' => 'value3',
        ];
        $array2 = [
            'key1' => 'value1',
            'key2' => [
                'key21' => 'valueDoesNotMatter',
            ],
        ];
        $expectedResult = [
            'key2' => 'value2',
            'key3' => 'value3',
        ];
        $actualResult = ArrayUtility::arrayDiffAssocRecursive($array1, $array2, true);
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function arrayDiffAssocRecursiveReturnsEmptyIfEqual(): void
    {
        $array1 = [
            'key1' => [
                'key11' => 'value11',
                'key12' => 'value12',
            ],
            'key2' => 'value2',
            'key3' => 'value3',
        ];
        $array2 = [
            'key1' => [
                'key11' => 'value11',
                'key12' => 'value12',
            ],
            'key2' => 'value2',
            'key3' => 'value3',
        ];
        $expectedResult = [];
        $actualResult = ArrayUtility::arrayDiffAssocRecursive($array1, $array2, true);
        self::assertEquals($expectedResult, $actualResult);
    }

    //////////////////////////////////////
    // Tests concerning naturalKeySortRecursive
    //////////////////////////////////////

    /**
     * @test
     */
    public function naturalKeySortRecursiveSortsOneDimensionalArrayByNaturalOrder(): void
    {
        $testArray = [
            'bb' => 'bb',
            'ab' => 'ab',
            '123' => '123',
            'aaa' => 'aaa',
            'abc' => 'abc',
            '23' => '23',
            'ba' => 'ba',
            'bad' => 'bad',
            '2' => '2',
            'zap' => 'zap',
            '210' => '210',
        ];
        $expectedResult = [
            '2',
            '23',
            '123',
            '210',
            'aaa',
            'ab',
            'abc',
            'ba',
            'bad',
            'bb',
            'zap',
        ];
        ArrayUtility::naturalKeySortRecursive($testArray);
        self::assertEquals($expectedResult, array_values($testArray));
    }

    /**
     * @test
     */
    public function naturalKeySortRecursiveSortsMultiDimensionalArrayByNaturalOrder(): void
    {
        $testArray = [
            '2' => '2',
            'bb' => 'bb',
            'ab' => 'ab',
            '23' => '23',
            'aaa' => [
                'bb' => 'bb',
                'ab' => 'ab',
                '123' => '123',
                'aaa' => 'aaa',
                '2' => '2',
                'abc' => 'abc',
                'ba' => 'ba',
                '23' => '23',
                'bad' => [
                    'bb' => 'bb',
                    'ab' => 'ab',
                    '123' => '123',
                    'aaa' => 'aaa',
                    'abc' => 'abc',
                    '23' => '23',
                    'ba' => 'ba',
                    'bad' => 'bad',
                    '2' => '2',
                    'zap' => 'zap',
                    '210' => '210',
                ],
                '210' => '210',
                'zap' => 'zap',
            ],
            'abc' => 'abc',
            'ba' => 'ba',
            '210' => '210',
            'bad' => 'bad',
            '123' => '123',
            'zap' => 'zap',
        ];
        $expectedResult = [
            '2',
            '23',
            '123',
            '210',
            'aaa',
            'ab',
            'abc',
            'ba',
            'bad',
            'bb',
            'zap',
        ];
        ArrayUtility::naturalKeySortRecursive($testArray);
        self::assertEquals($expectedResult, array_values(array_keys($testArray['aaa']['bad'])));
        self::assertEquals($expectedResult, array_values(array_keys($testArray['aaa'])));
        self::assertEquals($expectedResult, array_values(array_keys($testArray)));
    }

    /**
     * Data provider for filterAndSortByNumericKeysBehavesCorrectlyForAcceptAnyKeysIsTrue
     *
     * @return array
     */
    public function filterAndSortByNumericKeysWithAcceptAnyKey(): array
    {
        return [
            'ordered list of plain numeric keys' => [
                'input' => [
                    '10' => 'foo',
                    '20' => 'bar',
                ],
                'expected' => [
                    10,
                    20,
                ],
            ],
            'unordered list of plain numeric keys' => [
                'input' => [
                    '20' => 'bar',
                    '10' => 'foo',
                ],
                'expected' => [
                    10,
                    20,
                ],
            ],
            'list of string keys' => [
                'input' => [
                    '10.' => [
                        'wrap' => 'foo',
                        ],
                    '20.' => [
                        'wrap' => 'bar',
                    ],
                ],
                'expected' => [
                    10,
                    20,
                ],
            ],
            'list of mixed keys' => [
                'input' => [
                    '10' => 'foo',
                    '20.' => [
                        'wrap' => 'bar',
                    ],
                ],
                'expected' => [
                    10,
                    20,
                ],
            ],
            'list of mixed keys with one not interpreted as integer' => [
                'input' => [
                    '10' => 'foo',
                    'bla20.' => [
                        'wrap' => 'bar',
                    ],
                ],
                'expected' => [
                    0,
                    10,
                ],
            ],
            'list of mixed keys with more than one not interpreted as integer' => [
                'input' => [
                    '10' => 'foo',
                    'bla20.' => [
                        'wrap' => 'bar',
                    ],
                    'bla21.' => [
                        'wrap' => 'foobar',
                    ],
                ],
                'expected' => [
                    0,
                    10,
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider filterAndSortByNumericKeysWithAcceptAnyKey
     *
     * @param array $input
     * @param array $expected
     */
    public function filterAndSortByNumericKeysBehavesCorrectlyForAcceptAnyKeysIsTrue($input, $expected): void
    {
        $result = ArrayUtility::filterAndSortByNumericKeys($input, true);
        self::assertEquals($result, $expected);
    }

    /**
     * Data provider for filterAndSortByNumericKeysBehavesCorrectlyForAcceptAnyKeysIsFalse
     *
     * @return array
     */
    public function filterAndSortByNumericKeysWithoutAcceptAnyKey(): array
    {
        return [
            'ordered list of plain numeric keys' => [
                'input' => [
                    '10' => 'foo',
                    '20' => 'bar',
                ],
                'expected' => [
                    10,
                    20,
                ],
            ],
            'unordered list of plain numeric keys' => [
                'input' => [
                    '20' => 'bar',
                    '10' => 'foo',
                ],
                'expected' => [
                    10,
                    20,
                ],
            ],
            'list of string keys' => [
                'input' => [
                    '10.' => [
                        'wrap' => 'foo',
                    ],
                    '20.' => [
                        'wrap' => 'bar',
                    ],
                ],
                'expected' => [],
            ],
            'list of mixed keys' => [
                'input' => [
                    '10' => 'foo',
                    '20.' => [
                        'wrap' => 'bar',
                    ],
                ],
                'expected' => [
                    10,
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider filterAndSortByNumericKeysWithoutAcceptAnyKey
     *
     * @param array $input
     * @param array $expected
     */
    public function filterAndSortByNumericKeysBehavesCorrectlyForAcceptAnyKeysIsFalse($input, $expected): void
    {
        $result = ArrayUtility::filterAndSortByNumericKeys($input);
        self::assertEquals($result, $expected);
    }

    /**
     * dataProvider for sortArrayWithIntegerKeys
     *
     * @return array
     */
    public function sortArrayWithIntegerKeysDataProvider(): array
    {
        return [
            [
                [
                    '20' => 'test1',
                    '11' => 'test2',
                    '16' => 'test3',
                ],
                [
                    '11' => 'test2',
                    '16' => 'test3',
                    '20' => 'test1',
                ],
            ],
            [
                [
                    '20' => 'test1',
                    '16.5' => 'test2',
                    '16' => 'test3',
                ],
                [
                    '20' => 'test1',
                    '16.5' => 'test2',
                    '16' => 'test3',
                ],
            ],
            [
                [
                    '20' => 'test20',
                    'somestring' => 'teststring',
                    '16' => 'test16',
                ],
                [
                    '20' => 'test20',
                    'somestring' => 'teststring',
                    '16' => 'test16',
                ],
            ],
        ];
    }

    /**
     * @test
     *
     * @param array $arrayToSort
     * @param array $expectedArray
     *
     * @dataProvider sortArrayWithIntegerKeysDataProvider
     */
    public function sortArrayWithIntegerKeysSortsNumericArrays(array $arrayToSort, array $expectedArray): void
    {
        $sortedArray = ArrayUtility::sortArrayWithIntegerKeys($arrayToSort);
        self::assertSame($sortedArray, $expectedArray);
    }

    /**
     * @test
     */
    public function assertAllArrayKeysAreValidThrowsExceptionOnNotAllowedArrayKeys(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1325697085);

        $arrayToTest = [
            'roger' => '',
            'francine' => '',
            'stan' => '',
        ];

        $allowedArrayKeys = [
            'roger',
            'francine',
        ];

        ArrayUtility::assertAllArrayKeysAreValid($arrayToTest, $allowedArrayKeys);
    }

    /**
     * @test
     */
    public function assertAllArrayKeysAreValidReturnsNullOnAllowedArrayKeys(): void
    {
        $arrayToTest = [
            'roger' => '',
            'francine' => '',
            'stan' => '',
        ];

        $allowedArrayKeys = [
            'roger',
            'francine',
            'stan',
        ];

        ArrayUtility::assertAllArrayKeysAreValid($arrayToTest, $allowedArrayKeys);
    }

    /**
     * @test
     */
    public function sortArrayWithIntegerKeysRecursiveExpectSorting(): void
    {
        $input = [
            20 => 'b',
            10 => 'a',
            40 => 'd',
            30 => 'c',
            50 => [
                20 => 'a',
                10 => 'b',
            ],
        ];

        $expected = [
            10 => 'a',
            20 => 'b',
            30 => 'c',
            40 => 'd',
            50 => [
                10 => 'b',
                20 => 'a',
            ],
        ];

        self::assertSame($expected, ArrayUtility::sortArrayWithIntegerKeysRecursive($input));
    }

    /**
     * @test
     */
    public function sortArrayWithIntegerKeysRecursiveExpectNoSorting(): void
    {
        $input = [
            'b' => 'b',
            10 => 'a',
            40 => 'd',
            30 => 'c',
        ];

        $expected = [
            'b' => 'b',
            10 => 'a',
            40 => 'd',
            30 => 'c',
        ];

        self::assertSame($expected, ArrayUtility::sortArrayWithIntegerKeysRecursive($input));
    }

    /**
     * @test
     */
    public function reIndexNumericArrayKeysRecursiveExpectReindexing(): void
    {
        $input = [
            20 => 'b',
            10 => 'a',
            40 => 'd',
            30 => 'c',
            50 => [
                20 => 'a',
                10 => 'b',
            ],
        ];

        $expected = [
            0 => 'b',
            1 => 'a',
            2 => 'd',
            3 => 'c',
            4 => [
                0 => 'a',
                1 => 'b',
            ],
        ];

        self::assertSame($expected, ArrayUtility::reIndexNumericArrayKeysRecursive($input));
    }

    /**
     * @test
     */
    public function reIndexNumericArrayKeysRecursiveExpectNoReindexing(): void
    {
        $input = [
            'a' => 'b',
            10 => 'a',
            40 => 'd',
            30 => 'c',
            50 => [
                20 => 'a',
                10 => 'b',
            ],
        ];

        $expected = [
            'a' => 'b',
            10 => 'a',
            40 => 'd',
            30 => 'c',
            50 => [
                0 => 'a',
                1 => 'b',
            ],
        ];

        self::assertSame($expected, ArrayUtility::reIndexNumericArrayKeysRecursive($input));
    }

    /**
     * @test
     */
    public function removeNullValuesRecursiveExpectRemoval(): void
    {
        $input = [
            'a' => 'a',
            'b' => [
                'c' => null,
                'd' => 'd',
            ],
        ];

        $expected = [
            'a' => 'a',
            'b' => [
                'd' => 'd',
            ],
        ];

        self::assertSame($expected, ArrayUtility::removeNullValuesRecursive($input));
    }

    /**
     * @test
     */
    public function stripTagsFromValuesRecursiveExpectRemoval(): void
    {
        $input = [
            'a' => 'a',
            'b' => [
                'c' => '<b>i am evil</b>',
                'd' => 'd',
            ],
        ];

        $expected = [
            'a' => 'a',
            'b' => [
                'c' => 'i am evil',
                'd' => 'd',
            ],
        ];

        self::assertSame($expected, ArrayUtility::stripTagsFromValuesRecursive($input));
    }

    /**
     * @test
     */
    public function stripTagsFromValuesRecursiveExpectNoTypeCast(): void
    {
        $testObject = new \stdClass();

        $input = [
            'stringWithTags' => '<b>i am evil</b>',
            'boolean' => true,
            'integer' => 1,
            'float' => 1.9,
            'object' => $testObject,
            'objectWithStringConversion' => new class() {
                /**
                 * @return string
                 */
                public function __toString()
                {
                    return 'i am evil <b>too</b>';
                }
            },
        ];

        $expected = [
            'stringWithTags' => 'i am evil',
            'boolean' => true,
            'integer' => 1,
            'float' => 1.9,
            'object' => $testObject,
            'objectWithStringConversion' => 'i am evil too',
        ];

        self::assertSame($expected, ArrayUtility::stripTagsFromValuesRecursive($input));
    }

    /**
     * @test
     */
    public function convertBooleanStringsToBooleanRecursiveExpectConverting(): void
    {
        $input = [
            'a' => 'a',
            'b' => [
                'c' => 'true',
                'd' => 'd',
            ],
        ];

        $expected = [
            'a' => 'a',
            'b' => [
                'c' => true,
                'd' => 'd',
            ],
        ];

        self::assertSame($expected, ArrayUtility::convertBooleanStringsToBooleanRecursive($input));
    }

    /**
     * Data provider for arrayFilterRecursiveFiltersFalseElements
     * @return array
     */
    public function filterRecursiveFiltersFalseElementsDataProvider(): array
    {
        return [
            'filter all values which will be false when converted to boolean' => [
                // input
                [
                    true,
                    false,
                    'foo1' => [
                        'bar' => [
                            'baz' => [
                                '1',
                                null,
                                '',
                            ],
                            '' => 1,
                            'bbd' => 0,
                        ],
                    ],
                    'foo2' => 'foo',
                    'foo3' => '',
                    'foo4' => [
                        'z' => 'bar',
                        'bar' => 0,
                        'baz' => [
                            'foo' => [
                                'bar' => '',
                                'boo' => [],
                                'bamboo' => 5,
                                'fooAndBoo' => [0],
                            ],
                        ],
                    ],
                ],
                // expected
                [
                    true,
                    'foo1' => [
                        'bar' => [
                            'baz' => [
                                '1',
                            ],
                            '' => 1,
                        ],
                    ],
                    'foo2' => 'foo',
                    'foo4' => [
                        'z' => 'bar',
                        'baz' => [
                            'foo' => [
                                'bamboo' => 5,
                                'fooAndBoo' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider filterRecursiveFiltersFalseElementsDataProvider
     * @param array $input
     * @param array $expectedResult
     */
    public function filterRecursiveFiltersFalseElements(array $input, array $expectedResult): void
    {
        // If no callback is supplied, all entries of array equal to FALSE (see converting to boolean) will be removed.
        $result = ArrayUtility::filterRecursive($input);
        self::assertEquals($expectedResult, $result);
    }

    /**
     * Data provider for filterRecursiveCallbackFiltersEmptyElementsWithoutIntegerByCallback
     * @return array
     */
    public function filterRecursiveCallbackFiltersEmptyElementsWithoutIntegerZeroByCallbackDataProvider(): array
    {
        return [
            'filter empty values, keep zero integers' => [
                // input
                [
                    true,
                    false,
                    'foo1' => [
                        'bar' => [
                            'baz' => [
                                '1',
                                null,
                                '',
                            ],
                            '' => 1,
                            'bbd' => 0,
                        ],
                    ],
                    'foo2' => 'foo',
                    'foo3' => '',
                    'foo4' => [
                        'z' => 'bar',
                        'bar' => 0,
                        'baz' => [
                            'foo' => [
                                'bar' => '',
                                'boo' => [],
                                'bamboo' => 5,
                                'fooAndBoo' => [0],
                            ],
                        ],
                    ],
                ],
                // expected
                [
                    true,
                    false,
                    'foo1' => [
                        'bar' => [
                            'baz' => [
                                '1',
                            ],
                            '' => 1,
                            'bbd' => 0,
                        ],
                    ],
                    'foo2' => 'foo',
                    'foo4' => [
                        'z' => 'bar',
                        'bar' => 0,
                        'baz' => [
                            'foo' => [
                                'bamboo' => 5,
                                'fooAndBoo' => [0],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider filterRecursiveCallbackFiltersEmptyElementsWithoutIntegerZeroByCallbackDataProvider
     * @param array $input
     * @param array $expectedResult
     */
    public function filterRecursiveCallbackFiltersEmptyElementsWithoutIntegerByCallback(array $input, array $expectedResult): void
    {
        // callback filters empty strings, array and null but keeps zero integers
        $result = ArrayUtility::filterRecursive(
            $input,
            static function ($item) {
                return $item !== '' && $item !== [] && $item !== null;
            }
        );
        self::assertEquals($expectedResult, $result);
    }

    /**
     * Data provider for filterRecursiveSupportsCallableCallback
     * @return array
     */
    public function filterRecursiveSupportsCallableCallbackDataProvider(): array
    {
        $input = [
            'foo' => 'remove',
            'bar' => [
                'baz' => 'remove',
                'keep1' => 'keep',
            ],
            'keep2' => 'keep',
        ];
        $expectedResult = [
            'bar' => [
                'keep1' => 'keep',
            ],
            'keep2' => 'keep',
        ];

        return [
            'filter using a closure' => [
                $input,
                $expectedResult,
                static function ($value): bool {
                    return is_array($value) || $value === 'keep';
                },
            ],
            'filter using a callable "static class-method call" as string' => [
                $input,
                $expectedResult,
                ArrayUtilityFilterRecursiveCallbackFixture::class . '::callbackViaStaticMethod',
            ],
            'filter using a callable "static class-method call" as array' => [
                $input,
                $expectedResult,
                [ArrayUtilityFilterRecursiveCallbackFixture::class, 'callbackViaStaticMethod'],
            ],
            'filter using a callable "instance-method call" as array' => [
                $input,
                $expectedResult,
                [new ArrayUtilityFilterRecursiveCallbackFixture(), 'callbackViaInstanceMethod'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider filterRecursiveSupportsCallableCallbackDataProvider
     * @param array    $input
     * @param array    $expectedResult
     * @param callable $callback
     * @see https://forge.typo3.org/issues/84485
     */
    public function filterRecursiveSupportsCallableCallback(array $input, array $expectedResult, callable $callback): void
    {
        $result = ArrayUtility::filterRecursive($input, $callback);
        self::assertEquals($expectedResult, $result);
    }

    /**
     * Data provider for isAssociativeCorrectlyFindsStringKeys
     * @return array
     */
    public function isAssociativeCorrectlyFindsStringKeysDataProvider(): array
    {
        return [
            'array without string keys' => [
                [
                    0 => 'value 0',
                    1 => 'value 1',
                ],
                false,
            ],
            'array with only string keys' => [
                [
                    'key 0' => 'value 0',
                    'key 1' => 'value 1',
                ],
                true,
            ],
            'array with mixed keys' => [
                [
                    0 => 'value 0',
                    1 => 'value 1',
                    'key 2' => 'value 2',
                    'key 3' => 'value 3',
                ],
                true,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider isAssociativeCorrectlyFindsStringKeysDataProvider
     * @param array $array
     * @param bool  $expectedResult
     */
    public function isAssociativeCorrectlyFindsStringKeys(array $array, bool $expectedResult): void
    {
        $result = ArrayUtility::isAssociative($array);
        self::assertEquals($expectedResult, $result);
    }

    /**
     * Data provider for replaceAndAppendScalarValuesRecursiveCorrectlyMergesArrays
     * @return array
     */
    public function replaceAndAppendScalarValuesRecursiveCorrectlyMergesArraysDataProvider(): array
    {
        return [
            'merge simple lists' => [
                [
                    0 => 'keep',
                ],
                [
                    0 => 'keep',
                ],
                [
                    0 => 'keep',
                    1 => 'keep',
                ],
            ],
            'merge simple list arrays' => [
                [
                    'foo' => [
                        0 => 'keep',
                    ],
                ],
                [
                    'foo' => [
                        0 => 'keep',
                    ],
                ],
                [
                    'foo' => [
                        0 => 'keep',
                        1 => 'keep',
                    ],
                ],
            ],
            'merge array and simple value' => [
                [
                    'foo' => [
                        0 => 'override',
                    ],
                ],
                [
                    'foo' => 'keep',
                ],
                [
                    'foo' => 'keep',
                ],
            ],
            'merge simple values' => [
                [
                    'foo' => 'override',
                ],
                [
                    'foo' => 'keep',
                ],
                [
                    'foo' => 'keep',
                ],
            ],
            'merge new keys' => [
                [
                    'foo' => 'keep',
                ],
                [
                    'bar' => 'keep',
                ],
                [
                    'foo' => 'keep',
                    'bar' => 'keep',
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider replaceAndAppendScalarValuesRecursiveCorrectlyMergesArraysDataProvider
     * @param array $array1
     * @param array $array2
     * @param array $expectedResult
     */
    public function replaceAndAppendScalarValuesRecursiveCorrectlyMergesArrays(array $array1, array $array2, array $expectedResult): void
    {
        $result = ArrayUtility::replaceAndAppendScalarValuesRecursive($array1, $array2);
        self::assertEquals($expectedResult, $result);
    }
}
