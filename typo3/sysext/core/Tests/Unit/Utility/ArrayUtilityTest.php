<?php
namespace TYPO3\CMS\Core\Tests\Unit\Utility;

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

use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * Test case
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
    public function filterByValueRecursive()
    {
        return array(
            'empty search array' => array(
                'banana',
                array(),
                array()
            ),
            'empty string as needle' => array(
                '',
                array(
                    '',
                    'apple'
                ),
                array(
                    ''
                )
            ),
            'flat array searching for string' => array(
                'banana',
                array(
                    'apple',
                    'banana'
                ),
                array(
                    1 => 'banana'
                )
            ),
            'flat array searching for string with two matches' => array(
                'banana',
                array(
                    'foo' => 'apple',
                    'firstbanana' => 'banana',
                    'secondbanana' => 'banana'
                ),
                array(
                    'firstbanana' => 'banana',
                    'secondbanana' => 'banana'
                )
            ),
            'multi dimensional array searching for string with multiple matches' => array(
                'banana',
                array(
                    'foo' => 'apple',
                    'firstbanana' => 'banana',
                    'grape' => array(
                        'foo2' => 'apple2',
                        'secondbanana' => 'banana',
                        'foo3' => array()
                    ),
                    'bar' => 'orange'
                ),
                array(
                    'firstbanana' => 'banana',
                    'grape' => array(
                        'secondbanana' => 'banana'
                    )
                )
            ),
            'multi dimensional array searching for integer with multiple matches' => array(
                42,
                array(
                    'foo' => 23,
                    'bar' => 42,
                    array(
                        'foo' => 23,
                        'bar' => 42
                    )
                ),
                array(
                    'bar' => 42,
                    array(
                        'bar' => 42
                    )
                )
            ),
            'flat array searching for boolean TRUE' => array(
                true,
                array(
                    23 => false,
                    42 => true
                ),
                array(
                    42 => true
                )
            ),
            'multi dimensional array searching for boolean FALSE' => array(
                false,
                array(
                    23 => false,
                    42 => true,
                    'foo' => array(
                        23 => false,
                        42 => true
                    )
                ),
                array(
                    23 => false,
                    'foo' => array(
                        23 => false
                    )
                )
            ),
            'flat array searching for array' => array(
                array(
                    'foo' => 'bar'
                ),
                array(
                    'foo' => 'bar',
                    'foobar' => array(
                        'foo' => 'bar'
                    )
                ),
                array(
                    'foobar' => array(
                        'foo' => 'bar'
                    )
                )
            )
        );
    }

    /**
     * @test
     * @dataProvider filterByValueRecursive
     * @param array $needle
     * @param array $haystack
     * @param array $expectedResult
     */
    public function filterByValueRecursiveCorrectlyFiltersArray($needle, $haystack, $expectedResult)
    {
        $this->assertEquals(
            $expectedResult,
            ArrayUtility::filterByValueRecursive($needle, $haystack)
        );
    }

    /**
     * @test
     */
    public function filterByValueRecursiveMatchesReferencesToSameObject()
    {
        $instance = new \stdClass();
        $this->assertEquals(
            array($instance),
            ArrayUtility::filterByValueRecursive($instance, array($instance))
        );
    }

    /**
     * @test
     */
    public function filterByValueRecursiveDoesNotMatchDifferentInstancesOfSameClass()
    {
        $this->assertEquals(
            array(),
            ArrayUtility::filterByValueRecursive(new \stdClass(), array(new \stdClass()))
        );
    }

    ///////////////////////
    // Tests concerning isValidPath
    ///////////////////////
    /**
     * Mock the class under test, isValidPath() (method under test), calls
     * static getValuePath() internally, which is mocked here to return a specific
     * result. This works because of 'static' keyword'  instead of 'self'
     * for getValueByPath() call, using late static binding in PHP 5.3
     *
     * @test
     */
    public function isValidPathReturnsTrueIfPathExists()
    {
        $this->assertTrue(ArrayUtility::isValidPath(array('foo' => 'bar'), 'foo'));
    }

    /**
     * @test
     */
    public function isValidPathReturnsFalseIfPathDoesNotExist()
    {
        $this->assertFalse(ArrayUtility::isValidPath(array('foo' => 'bar'), 'bar'));
    }

    ///////////////////////
    // Tests concerning getValueByPath
    ///////////////////////
    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function getValueByPathThrowsExceptionIfPathIsEmpty()
    {
        ArrayUtility::getValueByPath(array(), '');
    }

    /**
     * Data provider for getValueByPathThrowsExceptionIfPathNotExists
     * Every array splits into:
     * - Array to get value from
     * - String path
     * - Expected result
     * @return array
     */
    public function getValueByPathInvalidPathDataProvider()
    {
        return array(
            'not existing path 1' => array(
                array(
                    'foo' => array()
                ),
                'foo/bar/baz',
                false
            ),
            'not existing path 2' => array(
                array(
                    'foo' => array(
                        'baz' => 42
                    ),
                    'bar' => array()
                ),
                'foo/bar/baz',
                false
            ),
            // Negative test: This could be improved and the test moved to
            // the valid data provider if the method supports this
            'doubletick encapsulated quoted doubletick does not work' => array(
                array(
                    '"foo"bar"' => array(
                        'baz' => 42
                    ),
                    'bar' => array()
                ),
                '"foo\\"bar"/baz',
                42
            ),
            // Negative test: Method could be improved here
            'path with doubletick does not work' => array(
                array(
                    'fo"o' => array(
                        'bar' => 42
                    )
                ),
                'fo"o/foobar',
                42
            )
        );
    }

    /**
     * @test
     * @dataProvider getValueByPathInvalidPathDataProvider
     * @expectedException \RuntimeException
     * @param array $array
     * @param string $path
     */
    public function getValueByPathThrowsExceptionIfPathNotExists(array $array, $path)
    {
        ArrayUtility::getValueByPath($array, $path);
    }

    /**
     * Data provider for getValueByPathReturnsCorrectValue
     * Every array splits into:
     * - Array to get value from
     * - String path
     * - Expected result
     */
    public function getValueByPathValidDataProvider()
    {
        $testObject = new \StdClass();
        $testObject->foo = 'foo';
        $testObject->bar = 'bar';
        return array(
            'integer in multi level array' => array(
                array(
                    'foo' => array(
                        'bar' => array(
                            'baz' => 42
                        ),
                        'bar2' => array()
                    )
                ),
                'foo/bar/baz',
                42
            ),
            'zero integer in multi level array' => array(
                array(
                    'foo' => array(
                        'bar' => array(
                            'baz' => 0
                        )
                    )
                ),
                'foo/bar/baz',
                0
            ),
            'NULL value in multi level array' => array(
                array(
                    'foo' => array(
                        'baz' => null
                    )
                ),
                'foo/baz',
                null
            ),
            'get string value' => array(
                array(
                    'foo' => array(
                        'baz' => 'this is a test string'
                    )
                ),
                'foo/baz',
                'this is a test string'
            ),
            'get boolean value: FALSE' => array(
                array(
                    'foo' => array(
                        'baz' => false
                    )
                ),
                'foo/baz',
                false
            ),
            'get boolean value: TRUE' => array(
                array(
                    'foo' => array(
                        'baz' => true
                    )
                ),
                'foo/baz',
                true
            ),
            'get object value' => array(
                array(
                    'foo' => array(
                        'baz' => $testObject
                    )
                ),
                'foo/baz',
                $testObject
            ),
            'enclosed path' => array(
                array(
                    'foo/bar' => array(
                        'foobar' => 42
                    )
                ),
                '"foo/bar"/foobar',
                42
            )
        );
    }

    /**
     * @test
     * @dataProvider getValueByPathValidDataProvider
     * @param array $array
     * @param string $path
     * @param mixed $expectedResult
     */
    public function getValueByPathGetsCorrectValue(array $array, $path, $expectedResult)
    {
        $this->assertEquals($expectedResult, ArrayUtility::getValueByPath($array, $path));
    }

    /**
     * @test
     */
    public function getValueByPathAcceptsDifferentDelimiter()
    {
        $input = array(
            'foo' => array(
                'bar' => array(
                    'baz' => 42
                ),
                'bar2' => array()
            )
        );
        $searchPath = 'foo%bar%baz';
        $expected = 42;
        $delimiter = '%';
        $this->assertEquals(
            $expected,
            ArrayUtility::getValueByPath($input, $searchPath, $delimiter)
        );
    }

    ///////////////////////
    // Tests concerning setValueByPath
    ///////////////////////
    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function setValueByPathThrowsExceptionIfPathIsEmpty()
    {
        ArrayUtility::setValueByPath(array(), '', null);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function setValueByPathThrowsExceptionIfPathIsNotAString()
    {
        ArrayUtility::setValueByPath(array(), array('foo'), null);
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
    public function setValueByPathSetsCorrectValueDataProvider()
    {
        $testObject = new \StdClass();
        $testObject->foo = 'foo';
        $testObject->bar = 'bar';
        return array(
            'set integer value: 42' => array(
                array(
                    'foo' => array(
                        'bar' => array(
                            'baz' => 0
                        )
                    )
                ),
                'foo/bar/baz',
                42,
                array(
                    'foo' => array(
                        'bar' => array(
                            'baz' => 42
                        )
                    )
                )
            ),
            'set integer value: 0' => array(
                array(
                    'foo' => array(
                        'bar' => array(
                            'baz' => 42
                        )
                    )
                ),
                'foo/bar/baz',
                0,
                array(
                    'foo' => array(
                        'bar' => array(
                            'baz' => 0
                        )
                    )
                )
            ),
            'set null value' => array(
                array(
                    'foo' => array(
                        'bar' => array(
                            'baz' => 42
                        )
                    )
                ),
                'foo/bar/baz',
                null,
                array(
                    'foo' => array(
                        'bar' => array(
                            'baz' => null
                        )
                    )
                )
            ),
            'set array value' => array(
                array(
                    'foo' => array(
                        'bar' => array(
                            'baz' => 42
                        )
                    )
                ),
                'foo/bar/baz',
                array(
                    'foo' => 123
                ),
                array(
                    'foo' => array(
                        'bar' => array(
                            'baz' => array(
                                'foo' => 123
                            )
                        )
                    )
                )
            ),
            'set boolean value: FALSE' => array(
                array(
                    'foo' => array(
                        'bar' => array(
                            'baz' => true
                        )
                    )
                ),
                'foo/bar/baz',
                false,
                array(
                    'foo' => array(
                        'bar' => array(
                            'baz' => false
                        )
                    )
                )
            ),
            'set boolean value: TRUE' => array(
                array(
                    'foo' => array(
                        'bar' => array(
                            'baz' => null
                        )
                    )
                ),
                'foo/bar/baz',
                true,
                array(
                    'foo' => array(
                        'bar' => array(
                            'baz' => true
                        )
                    )
                )
            ),
            'set object value' => array(
                array(
                    'foo' => array(
                        'bar' => array(
                            'baz' => null
                        )
                    )
                ),
                'foo/bar/baz',
                $testObject,
                array(
                    'foo' => array(
                        'bar' => array(
                            'baz' => $testObject
                        )
                    )
                )
            ),
            'multi keys in array' => array(
                array(
                    'foo' => array(
                        'bar' => array(
                            'baz' => 'value'
                        ),
                        'bar2' => array(
                            'baz' => 'value'
                        )
                    )
                ),
                'foo/bar2/baz',
                'newValue',
                array(
                    'foo' => array(
                        'bar' => array(
                            'baz' => 'value'
                        ),
                        'bar2' => array(
                            'baz' => 'newValue'
                        )
                    )
                )
            )
        );
    }

    /**
     * @test
     * @dataProvider setValueByPathSetsCorrectValueDataProvider
     * @param array $array
     * @param string $path
     * @param string $value
     * @param array $expectedResult
     */
    public function setValueByPathSetsCorrectValue(array $array, $path, $value, $expectedResult)
    {
        $this->assertEquals(
            $expectedResult,
            ArrayUtility::setValueByPath($array, $path, $value)
        );
    }

    /**********************
    /* Tests concerning removeByPath
     ***********************/

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function removeByPathThrowsExceptionIfPathIsEmpty()
    {
        ArrayUtility::removeByPath(array(), '');
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function removeByPathThrowsExceptionIfPathIsNotAString()
    {
        ArrayUtility::removeByPath(array(), array('foo'));
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function removeByPathThrowsExceptionWithEmptyPathSegment()
    {
        $inputArray = array(
            'foo' => array(
                'bar' => 42,
            ),
        );
        ArrayUtility::removeByPath($inputArray, 'foo//bar');
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function removeByPathThrowsExceptionIfPathDoesNotExistInArray()
    {
        $inputArray = array(
            'foo' => array(
                'bar' => 42,
            ),
        );
        ArrayUtility::removeByPath($inputArray, 'foo/baz');
    }

    /**
     * @test
     */
    public function removeByPathAcceptsGivenDelimiter()
    {
        $inputArray = array(
            'foo' => array(
                'toRemove' => 42,
                'keep' => 23
            ),
        );
        $path = 'foo.toRemove';
        $expected = array(
            'foo' => array(
                'keep' => 23,
            ),
        );
        $this->assertEquals(
            $expected,
            ArrayUtility::removeByPath($inputArray, $path, '.')
        );
    }

    /**
     * Data provider for removeByPathRemovesCorrectPath
     */
    public function removeByPathRemovesCorrectPathDataProvider()
    {
        return array(
            'single value' => array(
                array(
                    'foo' => array(
                        'toRemove' => 42,
                        'keep' => 23
                    ),
                ),
                'foo/toRemove',
                array(
                    'foo' => array(
                        'keep' => 23,
                    ),
                ),
            ),
            'whole array' => array(
                array(
                    'foo' => array(
                        'bar' => 42
                    ),
                ),
                'foo',
                array(),
            ),
            'sub array' => array(
                array(
                    'foo' => array(
                        'keep' => 23,
                        'toRemove' => array(
                            'foo' => 'bar',
                        ),
                    ),
                ),
                'foo/toRemove',
                array(
                    'foo' => array(
                        'keep' => 23,
                    ),
                ),
            ),
        );
    }

    /**
     * @test
     * @dataProvider removeByPathRemovesCorrectPathDataProvider
     * @param array $array
     * @param string $path
     * @param array $expectedResult
     */
    public function removeByPathRemovesCorrectPath(array $array, $path, $expectedResult)
    {
        $this->assertEquals(
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
    public function sortByKeyRecursiveCheckIfSortingIsCorrect()
    {
        $unsortedArray = array(
            'z' => null,
            'a' => null,
            'd' => array(
                'c' => null,
                'b' => null,
                'd' => null,
                'a' => null
            )
        );
        $expectedResult = array(
            'a' => null,
            'd' => array(
                'a' => null,
                'b' => null,
                'c' => null,
                'd' => null
            ),
            'z' => null
        );
        $this->assertSame($expectedResult, ArrayUtility::sortByKeyRecursive($unsortedArray));
    }

    ///////////////////////
    // Tests concerning sortArraysByKey
    ///////////////////////
    /**
     * Data provider for sortArraysByKeyCheckIfSortingIsCorrect
     */
    public function sortArraysByKeyCheckIfSortingIsCorrectDataProvider()
    {
        return array(
            'assoc array index' => array(
                array(
                    '22' => array(
                        'uid' => '22',
                        'title' => 'c',
                        'dummy' => 2
                    ),
                    '24' => array(
                        'uid' => '24',
                        'title' => 'a',
                        'dummy' => 3
                    ),
                    '23' => array(
                        'uid' => '23',
                        'title' => 'b',
                        'dummy' => 4
                    ),
                ),
                'title',
                true,
                array(
                    '24' => array(
                        'uid' => '24',
                        'title' => 'a',
                        'dummy' => 3
                    ),
                    '23' => array(
                        'uid' => '23',
                        'title' => 'b',
                        'dummy' => 4
                    ),
                    '22' => array(
                        'uid' => '22',
                        'title' => 'c',
                        'dummy' => 2
                    ),
                ),
            ),
            'numeric array index' => array(
                array(
                    22 => array(
                        'uid' => '22',
                        'title' => 'c',
                        'dummy' => 2
                    ),
                    24 => array(
                        'uid' => '24',
                        'title' => 'a',
                        'dummy' => 3
                    ),
                    23 => array(
                        'uid' => '23',
                        'title' => 'b',
                        'dummy' => 4
                    ),
                ),
                'title',
                true,
                array(
                    24 => array(
                        'uid' => '24',
                        'title' => 'a',
                        'dummy' => 3
                    ),
                    23 => array(
                        'uid' => '23',
                        'title' => 'b',
                        'dummy' => 4
                    ),
                    22 => array(
                        'uid' => '22',
                        'title' => 'c',
                        'dummy' => 2
                    ),
                ),
            ),
            'numeric array index DESC' => array(
                array(
                    23 => array(
                        'uid' => '23',
                        'title' => 'b',
                        'dummy' => 4
                    ),
                    22 => array(
                        'uid' => '22',
                        'title' => 'c',
                        'dummy' => 2
                    ),
                    24 => array(
                        'uid' => '24',
                        'title' => 'a',
                        'dummy' => 3
                    ),
                ),
                'title',
                false,
                array(
                    22 => array(
                        'uid' => '22',
                        'title' => 'c',
                        'dummy' => 2
                    ),
                    23 => array(
                        'uid' => '23',
                        'title' => 'b',
                        'dummy' => 4
                    ),
                    24 => array(
                        'uid' => '24',
                        'title' => 'a',
                        'dummy' => 3
                    ),
                ),
            ),
        );
    }

    /**
     * @test
     * @dataProvider sortArraysByKeyCheckIfSortingIsCorrectDataProvider
     * @param array $array
     * @param string $key
     * @param bool $ascending
     * @param array $expectedResult
     */
    public function sortArraysByKeyCheckIfSortingIsCorrect(array $array, $key, $ascending, $expectedResult)
    {
        $sortedArray = ArrayUtility::sortArraysByKey($array, $key, $ascending);
        $this->assertSame($expectedResult, $sortedArray);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function sortArraysByKeyThrowsExceptionForNonExistingKey()
    {
        ArrayUtility::sortArraysByKey(array(array('a'), array('a')), 'dummy');
    }

    ///////////////////////
    // Tests concerning arrayExport
    ///////////////////////
    /**
     * @test
     */
    public function arrayExportReturnsFormattedMultidimensionalArray()
    {
        $array = array(
            'foo' => array(
                'bar' => 42,
                'bar2' => array(
                    'baz' => 'val\'ue',
                    'baz2' => true,
                    'baz3' => false,
                    'baz4' => array()
                )
            ),
            'baz' => 23,
            'foobar' => null,
            'qux' => 0.1,
            'qux2' => 0.000000001,
        );
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
        $this->assertSame($expected, ArrayUtility::arrayExport($array));
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function arrayExportThrowsExceptionIfObjectShouldBeExported()
    {
        $array = array(
            'foo' => array(
                'bar' => new \stdClass()
            )
        );
        ArrayUtility::arrayExport($array);
    }

    /**
     * @test
     */
    public function arrayExportReturnsNumericArrayKeys()
    {
        $array = array(
            'foo' => 'string key',
            23 => 'integer key',
            '42' => 'string key representing integer'
        );
        $expected =
            '[' . LF .
                '    \'foo\' => \'string key\',' . LF .
                '    23 => \'integer key\',' . LF .
                '    42 => \'string key representing integer\',' . LF .
            ']';
        $this->assertSame($expected, ArrayUtility::arrayExport($array));
    }

    /**
     * @test
     */
    public function arrayExportReturnsNoKeyIndexForConsecutiveCountedArrays()
    {
        $array = array(
            0 => 'zero',
            1 => 'one',
            2 => 'two'
        );
        $expected =
            '[' . LF .
                '    \'zero\',' . LF .
                '    \'one\',' . LF .
                '    \'two\',' . LF .
            ']';
        $this->assertSame($expected, ArrayUtility::arrayExport($array));
    }

    /**
     * @test
     */
    public function arrayExportReturnsKeyIndexForNonConsecutiveCountedArrays()
    {
        $array = array(
            0 => 'zero',
            1 => 'one',
            3 => 'three',
            4 => 'four'
        );
        $expected =
            '[' . LF .
                '    0 => \'zero\',' . LF .
                '    1 => \'one\',' . LF .
                '    3 => \'three\',' . LF .
                '    4 => \'four\',' . LF .
            ']';
        $this->assertSame($expected, ArrayUtility::arrayExport($array));
    }

    ///////////////////////
    // Tests concerning flatten
    ///////////////////////

    /**
     * @return array
     */
    public function flattenCalculatesExpectedResultDataProvider()
    {
        return array(
            'plain array' => array(
                array(
                    'first' => 1,
                    'second' => 2
                ),
                array(
                    'first' => 1,
                    'second' => 2
                )
            ),
            'plain array with faulty dots' => array(
                array(
                    'first.' => 1,
                    'second.' => 2
                ),
                array(
                    'first' => 1,
                    'second' => 2
                )
            ),
            'nested array of 2 levels' => array(
                array(
                    'first.' => array(
                        'firstSub' => 1
                    ),
                    'second.' => array(
                        'secondSub' => 2
                    )
                ),
                array(
                    'first.firstSub' => 1,
                    'second.secondSub' => 2
                )
            ),
            'nested array of 2 levels with faulty dots' => array(
                array(
                    'first.' => array(
                        'firstSub.' => 1
                    ),
                    'second.' => array(
                        'secondSub.' => 2
                    )
                ),
                array(
                    'first.firstSub' => 1,
                    'second.secondSub' => 2
                )
            ),
            'nested array of 3 levels' => array(
                array(
                    'first.' => array(
                        'firstSub.' => array(
                            'firstSubSub' => 1
                        )
                    ),
                    'second.' => array(
                        'secondSub.' => array(
                            'secondSubSub' => 2
                        )
                    )
                ),
                array(
                    'first.firstSub.firstSubSub' => 1,
                    'second.secondSub.secondSubSub' => 2
                )
            ),
            'nested array of 3 levels with faulty dots' => array(
                array(
                    'first.' => array(
                        'firstSub.' => array(
                            'firstSubSub.' => 1
                        )
                    ),
                    'second.' => array(
                        'secondSub.' => array(
                            'secondSubSub.' => 2
                        )
                    )
                ),
                array(
                    'first.firstSub.firstSubSub' => 1,
                    'second.secondSub.secondSubSub' => 2
                )
            )
        );
    }

    /**
     * @test
     * @param array $array
     * @param array $expected
     * @dataProvider flattenCalculatesExpectedResultDataProvider
     */
    public function flattenCalculatesExpectedResult(array $array, array $expected)
    {
        $this->assertEquals($expected, ArrayUtility::flatten($array));
    }

    ///////////////////////
    // Tests concerning intersectRecursive
    ///////////////////////

    /**
     * @return array
     */
    public function intersectRecursiveCalculatesExpectedResultDataProvider()
    {
        $sameObject = new \stdClass();
        return array(
            // array($source, $mask, $expected)
            'empty array is returned if source is empty array' => array(
                array(),
                array(
                    'foo' => 'bar',
                ),
                array(),
            ),
            'empty array is returned if mask is empty' => array(
                array(
                    'foo' => 'bar',
                ),
                array(),
                array(),
            ),
            'key is kept on first level if exists in mask' => array(
                array(
                    'foo' => 42,
                ),
                array(
                    'foo' => 42,
                ),
                array(
                    'foo' => 42,
                ),
            ),
            'value of key in source is kept if mask has different value' => array(
                array(
                    'foo' => 42,
                ),
                array(
                    'foo' => new \stdClass(),
                ),
                array(
                    'foo' => 42,
                ),
            ),
            'key is kept on first level if according mask value is NULL' => array(
                array(
                    'foo' => 42,
                ),
                array(
                    'foo' => null,
                ),
                array(
                    'foo' => 42,
                ),
            ),
            'null in source value is kept' => array(
                array(
                    'foo' => null,
                ),
                array(
                    'foo' => 'bar',
                ),
                array(
                    'foo' => null,
                )
            ),
            'mask does not add new keys' => array(
                array(
                    'foo' => 42,
                ),
                array(
                    'foo' => 23,
                    'bar' => array(
                        4711
                    ),
                ),
                array(
                    'foo' => 42,
                ),
            ),
            'mask does not overwrite simple values with arrays' => array(
                array(
                    'foo' => 42,
                ),
                array(
                    'foo' => array(
                        'bar' => 23,
                    ),
                ),
                array(
                    'foo' => 42,
                ),
            ),
            'key is kept on first level if according mask value is array' => array(
                array(
                    'foo' => 42,
                ),
                array(
                    'foo' => array(
                        'bar' => 23
                    ),
                ),
                array(
                    'foo' => 42,
                ),
            ),
            'full array is kept if value is array and mask value is simple type' => array(
                array(
                    'foo' => array(
                        'bar' => 23
                    ),
                ),
                array(
                    'foo' => 42,
                ),
                array(
                    'foo' => array(
                        'bar' => 23
                    ),
                ),
            ),
            'key handling is type agnostic' => array(
                array(
                    42 => 'foo',
                ),
                array(
                    '42' => 'bar',
                ),
                array(
                    42 => 'foo',
                ),
            ),
            'value is same if value is object' => array(
                array(
                    'foo' => $sameObject,
                ),
                array(
                    'foo' => 'something',
                ),
                array(
                    'foo' => $sameObject,
                ),
            ),
            'mask does not add simple value to result if key does not exist in source' => array(
                array(
                    'foo' => '42',
                ),
                array(
                    'foo' => '42',
                    'bar' => 23
                ),
                array(
                    'foo' => '42',
                ),
            ),
            'array of source is kept if value of mask key exists but is no array' => array(
                array(
                    'foo' => '42',
                    'bar' => array(
                        'baz' => 23
                    ),
                ),
                array(
                    'foo' => 'value is not significant',
                    'bar' => null,
                ),
                array(
                    'foo' => '42',
                    'bar' => array(
                        'baz' => 23
                    ),
                ),
            ),
            'sub arrays are kept if mask has according sub array key and is similar array' => array(
                array(
                    'first1' => 42,
                    'first2' => array(
                        'second1' => 23,
                        'second2' => 4711,
                    ),
                ),
                array(
                    'first1' => 42,
                    'first2' => array(
                        'second1' => 'exists but different',
                    ),
                ),
                array(
                    'first1' => 42,
                    'first2' => array(
                        'second1' => 23,
                    ),
                ),
            ),
        );
    }

    /**
     * @test
     * @param array $source
     * @param array $mask
     * @param array $expected
     * @dataProvider intersectRecursiveCalculatesExpectedResultDataProvider
     */
    public function intersectRecursiveCalculatesExpectedResult(array $source, array $mask, array $expected)
    {
        $this->assertSame($expected, ArrayUtility::intersectRecursive($source, $mask));
    }

    ///////////////////////
    // Tests concerning renumberKeysToAvoidLeapsIfKeysAreAllNumeric
    ///////////////////////
    /**
     * @return array
     */
    public function renumberKeysToAvoidLeapsIfKeysAreAllNumericDataProvider()
    {
        return array(
            'empty array is returned if source is empty array' => array(
                array(),
                array()
            ),
            'returns self if array is already numerically keyed' => array(
                array(1,2,3),
                array(1,2,3)
            ),
            'returns correctly if keys are numeric, but contains a leap' => array(
                array(0 => 'One', 1 => 'Two', 3 => 'Three'),
                array(0 => 'One', 1 => 'Two', 2 => 'Three'),
            ),
            'returns correctly even though keys are strings but still numeric' => array(
                array('0' => 'One', '1' => 'Two', '3' => 'Three'),
                array(0 => 'One', 1 => 'Two', 2 => 'Three'),
            ),
            'returns correctly if just a single keys is not numeric' => array(
                array(0 => 'Zero', '1' => 'One', 'Two' => 'Two'),
                array(0 => 'Zero', '1' => 'One', 'Two' => 'Two'),
            ),
            'return self with nested numerically keyed array' => array(
                array(
                    'One',
                    'Two',
                    'Three',
                    array(
                        'sub.One',
                        'sub.Two',
                    )
                ),
                array(
                    'One',
                    'Two',
                    'Three',
                    array(
                        'sub.One',
                        'sub.Two',
                    )
                )
            ),
            'returns correctly with nested numerically keyed array with leaps' => array(
                array(
                    'One',
                    'Two',
                    'Three',
                    array(
                        0 => 'sub.One',
                        2 => 'sub.Two',
                    )
                ),
                array(
                    'One',
                    'Two',
                    'Three',
                    array(
                        'sub.One',
                        'sub.Two',
                    )
                )
            ),
            'returns correctly with nested string-keyed array' => array(
                array(
                    'One',
                    'Two',
                    'Three',
                    array(
                        'one' => 'sub.One',
                        'two' => 'sub.Two',
                    )
                ),
                array(
                    'One',
                    'Two',
                    'Three',
                    array(
                        'one' => 'sub.One',
                        'two' => 'sub.Two',
                    )
                )
            ),
            'returns correctly with deeply nested arrays' => array(
                array(
                    'One',
                    'Two',
                    array(
                        'one' => 1,
                        'two' => 2,
                        'three' => array(
                            2 => 'SubSubOne',
                            5 => 'SubSubTwo',
                            9 => array(0,1,2),
                            array()
                        )
                    )
                ),
                array(
                    'One',
                    'Two',
                    array(
                        'one' => 1,
                        'two' => 2,
                        'three' => array(
                            'SubSubOne',
                            'SubSubTwo',
                            array(0,1,2),
                            array()
                        )
                    )
                )
            )
        );
    }

    /**
     * @test
     * @param array $inputArray
     * @param array $expected
     * @dataProvider renumberKeysToAvoidLeapsIfKeysAreAllNumericDataProvider
     */
    public function renumberKeysToAvoidLeapsIfKeysAreAllNumericReturnsExpectedOrder(array $inputArray, array $expected)
    {
        $this->assertEquals($expected, ArrayUtility::renumberKeysToAvoidLeapsIfKeysAreAllNumeric($inputArray));
    }

    /**
     * @return array
     */
    public function mergeRecursiveWithOverruleCalculatesExpectedResultDataProvider()
    {
        return array(
            'Override array can reset string to array' => array(
                array(
                    'first' => array(
                        'second' => 'foo',
                    ),
                ),
                array(
                    'first' => array(
                        'second' => array('third' => 'bar'),
                    ),
                ),
                true,
                true,
                true,
                array(
                    'first' => array(
                        'second' => array('third' => 'bar'),
                    ),
                ),
            ),
            'Override array does not reset array to string (weird!)' => array(
                array(
                    'first' => array(),
                ),
                array(
                    'first' => 'foo',
                ),
                true,
                true,
                true,
                array(
                    'first' => array(), // This is rather unexpected, naive expectation: first => 'foo'
                ),
            ),
            'Override array does override string with null' => array(
                array(
                    'first' => 'foo',
                ),
                array(
                    'first' => null,
                ),
                true,
                true,
                true,
                array(
                    'first' => null,
                ),
            ),
            'Override array does override null with string' => array(
                array(
                    'first' => null,
                ),
                array(
                    'first' => 'foo',
                ),
                true,
                true,
                true,
                array(
                    'first' => 'foo',
                ),
            ),
            'Override array does override null with empty string' => array(
                array(
                    'first' => null,
                ),
                array(
                    'first' => '',
                ),
                true,
                true,
                true,
                array(
                    'first' => '',
                ),
            ),
            'Override array does not override string with NULL if requested' => array(
                array(
                    'first' => 'foo',
                ),
                array(
                    'first' => null,
                ),
                true,
                false, // no include empty values
                true,
                array(
                    'first' => 'foo',
                ),
            ),
            'Override array does override null with null' => array(
                array(
                    'first' => null,
                ),
                array(
                    'first' => null,
                ),
                true,
                true,
                true,
                array(
                    'first' => '',
                ),
            ),
            'Override array can __UNSET values' => array(
                array(
                    'first' => array(
                        'second' => 'second',
                        'third' => 'third',
                    ),
                    'fifth' => array(),
                ),
                array(
                    'first' => array(
                        'second' => 'overrule',
                        'third' => '__UNSET',
                        'fourth' => 'overrile',
                    ),
                    'fifth' => '__UNSET',
                ),
                true,
                true,
                true,
                array(
                    'first' => array(
                        'second' => 'overrule',
                        'fourth' => 'overrile',
                    ),
                ),
            ),
            'Override can add keys' => array(
                array(
                    'first' => 'foo',
                ),
                array(
                    'second' => 'bar',
                ),
                true,
                true,
                true,
                array(
                    'first' => 'foo',
                    'second' => 'bar',
                ),
            ),
            'Override does not add key if __UNSET' => array(
                array(
                    'first' => 'foo',
                ),
                array(
                    'second' => '__UNSET',
                ),
                true,
                true,
                true,
                array(
                    'first' => 'foo',
                ),
            ),
            'Override does not add key if not requested' => array(
                array(
                    'first' => 'foo',
                ),
                array(
                    'second' => 'bar',
                ),
                false, // no add keys
                true,
                true,
                array(
                    'first' => 'foo',
                ),
            ),
            'Override does not add key if not requested with add include empty values' => array(
                array(
                    'first' => 'foo',
                ),
                array(
                    'second' => 'bar',
                ),
                false, // no add keys
                false, // no include empty values
                true,
                array(
                    'first' => 'foo',
                ),
            ),
            'Override does not override string with empty string if requested' => array(
                array(
                    'first' => 'foo',
                ),
                array(
                    'first' => '',
                ),
                true,
                false, // no include empty values
                true,
                array(
                    'first' => 'foo',
                ),
            ),
            'Override array does merge instead of __UNSET if requested (weird!)' => array(
                array(
                    'first' => array(
                        'second' => 'second',
                        'third' => 'third',
                    ),
                    'fifth' => array(),
                ),
                array(
                    'first' => array(
                        'second' => 'overrule',
                        'third' => '__UNSET',
                        'fourth' => 'overrile',
                    ),
                    'fifth' => '__UNSET',
                ),
                true,
                true,
                false,
                array(
                    'first' => array(
                        'second' => 'overrule',
                        'third' => '__UNSET', // overruled
                        'fourth' => 'overrile',
                    ),
                    'fifth' => array(), // not overruled with string here, naive expectation: 'fifth' => '__UNSET'
                ),
            ),
        );
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
    public function mergeRecursiveWithOverruleCalculatesExpectedResult($input1, $input2, $addKeys, $includeEmptyValues, $enableUnsetFeature, $expected)
    {
        ArrayUtility::mergeRecursiveWithOverrule($input1, $input2, $addKeys, $includeEmptyValues, $enableUnsetFeature);
        $this->assertEquals($expected, $input1);
    }

    //////////////////////////////////
    // Tests concerning inArray
    //////////////////////////////////
    /**
     * @test
     * @dataProvider inArrayDataProvider
     * @param array $array target array
     * @param string $item search string
     * @param bool $expected expected value
     */
    public function inArrayChecksStringExistenceWithinArray($array, $item, $expected)
    {
        $this->assertEquals($expected, ArrayUtility::inArray($array, $item));
    }

    /**
     * Data provider for inArrayChecksStringExistenceWithinArray
     *
     * @return array
     */
    public function inArrayDataProvider()
    {
        return array(
            'Empty array' => array(array(), 'search', false),
            'One item array no match' => array(array('one'), 'two', false),
            'One item array match' => array(array('one'), 'one', true),
            'Multiple items array no match' => array(array('one', 2, 'three', 4), 'four', false),
            'Multiple items array match' => array(array('one', 2, 'three', 4), 'three', true),
            'Integer search items can match string values' => array(array('0', '1', '2'), 1, true),
            'Search item is not casted to integer for a match' => array(array(4), '4a', false),
            'Empty item won\'t match - in contrast to the php-builtin ' => array(array(0, 1, 2), '', false)
        );
    }

    //////////////////////////////////
    // Tests concerning removeArrayEntryByValue
    //////////////////////////////////
    /**
     * @test
     */
    public function checkRemoveArrayEntryByValueRemovesEntriesFromOneDimensionalArray()
    {
        $inputArray = array(
            '0' => 'test1',
            '1' => 'test2',
            '2' => 'test3',
            '3' => 'test2'
        );
        $compareValue = 'test2';
        $expectedResult = array(
            '0' => 'test1',
            '2' => 'test3'
        );
        $actualResult = ArrayUtility::removeArrayEntryByValue($inputArray, $compareValue);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function checkRemoveArrayEntryByValueRemovesEntriesFromMultiDimensionalArray()
    {
        $inputArray = array(
            '0' => 'foo',
            '1' => array(
                '10' => 'bar'
            ),
            '2' => 'bar'
        );
        $compareValue = 'bar';
        $expectedResult = array(
            '0' => 'foo',
            '1' => array()
        );
        $actualResult = ArrayUtility::removeArrayEntryByValue($inputArray, $compareValue);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function checkRemoveArrayEntryByValueRemovesEntryWithEmptyString()
    {
        $inputArray = array(
            '0' => 'foo',
            '1' => '',
            '2' => 'bar'
        );
        $compareValue = '';
        $expectedResult = array(
            '0' => 'foo',
            '2' => 'bar'
        );
        $actualResult = ArrayUtility::removeArrayEntryByValue($inputArray, $compareValue);
        $this->assertEquals($expectedResult, $actualResult);
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
    public function keepItemsInArrayWorksWithOneArgument($search, $array, $expected)
    {
        $this->assertEquals($expected, ArrayUtility::keepItemsInArray($array, $search));
    }

    /**
     * Data provider for keepItemsInArrayWorksWithOneArgument
     *
     * @return array
     */
    public function keepItemsInArrayWorksWithOneArgumentDataProvider()
    {
        $array = array(
            'one' => 'one',
            'two' => 'two',
            'three' => 'three'
        );
        return array(
            'Empty argument will match "all" elements' => array(null, $array, $array),
            'No match' => array('four', $array, array()),
            'One match' => array('two', $array, array('two' => 'two')),
            'Multiple matches' => array('two,one', $array, array('one' => 'one', 'two' => 'two')),
            'Argument can be an array' => array(array('three'), $array, array('three' => 'three'))
        );
    }

    /**
     * Shows the example from the doc comment where
     * a function is used to reduce the sub arrays to one item which
     * is then used for the matching.
     *
     * @test
     */
    public function keepItemsInArrayCanUseClosure()
    {
        $array = array(
            'aa' => array('first', 'second'),
            'bb' => array('third', 'fourth'),
            'cc' => array('fifth', 'sixth')
        );
        $expected = array('bb' => array('third', 'fourth'));
        $keepItems = 'third';
        $match = ArrayUtility::keepItemsInArray(
            $array,
            $keepItems,
            function ($value) {
                return $value[0];
            }
        );
        $this->assertEquals($expected, $match);
    }

    //////////////////////////////////
    // Tests concerning remapArrayKeys
    //////////////////////////////////
    /**
     * @test
     */
    public function remapArrayKeysExchangesKeysWithGivenMapping()
    {
        $array = array(
            'one' => 'one',
            'two' => 'two',
            'three' => 'three'
        );
        $keyMapping = array(
            'one' => '1',
            'two' => '2'
        );
        $expected = array(
            '1' => 'one',
            '2' => 'two',
            'three' => 'three'
        );
        ArrayUtility::remapArrayKeys($array, $keyMapping);
        $this->assertEquals($expected, $array);
    }

    //////////////////////////////////////
    // Tests concerning arrayDiffAssocRecursive
    //////////////////////////////////////
    /**
     * @test
     */
    public function arrayDiffAssocRecursiveHandlesOneDimensionalArrays()
    {
        $array1 = array(
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3'
        );
        $array2 = array(
            'key1' => 'value1',
            'key3' => 'value3'
        );
        $expectedResult = array(
            'key2' => 'value2'
        );
        $actualResult = ArrayUtility::arrayDiffAssocRecursive($array1, $array2);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function arrayDiffAssocRecursiveHandlesMultiDimensionalArrays()
    {
        $array1 = array(
            'key1' => 'value1',
            'key2' => array(
                'key21' => 'value21',
                'key22' => 'value22',
                'key23' => array(
                    'key231' => 'value231',
                    'key232' => 'value232'
                )
            )
        );
        $array2 = array(
            'key1' => 'value1',
            'key2' => array(
                'key21' => 'value21',
                'key23' => array(
                    'key231' => 'value231'
                )
            )
        );
        $expectedResult = array(
            'key2' => array(
                'key22' => 'value22',
                'key23' => array(
                    'key232' => 'value232'
                )
            )
        );
        $actualResult = ArrayUtility::arrayDiffAssocRecursive($array1, $array2);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function arrayDiffAssocRecursiveHandlesMixedArrays()
    {
        $array1 = array(
            'key1' => array(
                'key11' => 'value11',
                'key12' => 'value12'
            ),
            'key2' => 'value2',
            'key3' => 'value3'
        );
        $array2 = array(
            'key1' => 'value1',
            'key2' => array(
                'key21' => 'value21'
            )
        );
        $expectedResult = array(
            'key3' => 'value3'
        );
        $actualResult = ArrayUtility::arrayDiffAssocRecursive($array1, $array2);
        $this->assertEquals($expectedResult, $actualResult);
    }

    //////////////////////////////////////
    // Tests concerning naturalKeySortRecursive
    //////////////////////////////////////

    /**
     * @test
     */
    public function naturalKeySortRecursiveSortsOneDimensionalArrayByNaturalOrder()
    {
        $testArray = array(
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
            '210' => '210'
        );
        $expectedResult = array(
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
            'zap'
        );
        ArrayUtility::naturalKeySortRecursive($testArray);
        $this->assertEquals($expectedResult, array_values($testArray));
    }

    /**
     * @test
     */
    public function naturalKeySortRecursiveSortsMultiDimensionalArrayByNaturalOrder()
    {
        $testArray = array(
            '2' => '2',
            'bb' => 'bb',
            'ab' => 'ab',
            '23' => '23',
            'aaa' => array(
                'bb' => 'bb',
                'ab' => 'ab',
                '123' => '123',
                'aaa' => 'aaa',
                '2' => '2',
                'abc' => 'abc',
                'ba' => 'ba',
                '23' => '23',
                'bad' => array(
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
                    '210' => '210'
                ),
                '210' => '210',
                'zap' => 'zap'
            ),
            'abc' => 'abc',
            'ba' => 'ba',
            '210' => '210',
            'bad' => 'bad',
            '123' => '123',
            'zap' => 'zap'
        );
        $expectedResult = array(
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
            'zap'
        );
        ArrayUtility::naturalKeySortRecursive($testArray);
        $this->assertEquals($expectedResult, array_values(array_keys($testArray['aaa']['bad'])));
        $this->assertEquals($expectedResult, array_values(array_keys($testArray['aaa'])));
        $this->assertEquals($expectedResult, array_values(array_keys($testArray)));
    }
}
