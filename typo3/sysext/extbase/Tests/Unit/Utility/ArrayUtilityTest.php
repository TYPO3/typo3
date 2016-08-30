<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Utility;

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

/**
 * Test case
 */
class ArrayUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function containsMultipleTypesReturnsFalseOnEmptyArray()
    {
        $this->assertFalse(\TYPO3\CMS\Extbase\Utility\ArrayUtility::containsMultipleTypes([]));
    }

    /**
     * @test
     */
    public function containsMultipleTypesReturnsFalseOnArrayWithIntegers()
    {
        $this->assertFalse(\TYPO3\CMS\Extbase\Utility\ArrayUtility::containsMultipleTypes([1, 2, 3]));
    }

    /**
     * @test
     */
    public function containsMultipleTypesReturnsFalseOnArrayWithObjects()
    {
        $this->assertFalse(\TYPO3\CMS\Extbase\Utility\ArrayUtility::containsMultipleTypes([new \stdClass(), new \stdClass(), new \stdClass()]));
    }

    /**
     * @test
     */
    public function containsMultipleTypesReturnsTrueOnMixedArray()
    {
        $this->assertTrue(\TYPO3\CMS\Extbase\Utility\ArrayUtility::containsMultipleTypes([1, 'string', 1.25, new \stdClass()]));
    }

    /**
     * @test
     */
    public function getValueByPathReturnsTheValueOfANestedArrayByFollowingTheGivenSimplePath()
    {
        $array = ['Foo' => 'the value'];
        $this->assertSame('the value', \TYPO3\CMS\Extbase\Utility\ArrayUtility::getValueByPath($array, ['Foo']));
    }

    /**
     * @test
     */
    public function getValueByPathReturnsTheValueOfANestedArrayByFollowingTheGivenPath()
    {
        $array = ['Foo' => ['Bar' => ['Baz' => [2 => 'the value']]]];
        $this->assertSame('the value', \TYPO3\CMS\Extbase\Utility\ArrayUtility::getValueByPath($array, ['Foo', 'Bar', 'Baz', 2]));
    }

    /**
     * @test
     */
    public function getValueByPathReturnsTheValueOfANestedArrayByFollowingTheGivenPathIfPathIsString()
    {
        $path = 'Foo.Bar.Baz.2';
        $array = ['Foo' => ['Bar' => ['Baz' => [2 => 'the value']]]];
        $expectedResult = 'the value';
        $actualResult = \TYPO3\CMS\Extbase\Utility\ArrayUtility::getValueByPath($array, $path);
        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function getValueByPathThrowsExceptionIfPathIsNoArrayOrString()
    {
        $array = ['Foo' => ['Bar' => ['Baz' => [2 => 'the value']]]];
        \TYPO3\CMS\Extbase\Utility\ArrayUtility::getValueByPath($array, null);
    }

    /**
     * @test
     */
    public function getValueByPathReturnsNullIfTheSegementsOfThePathDontExist()
    {
        $array = ['Foo' => ['Bar' => ['Baz' => [2 => 'the value']]]];
        $this->assertNull(\TYPO3\CMS\Extbase\Utility\ArrayUtility::getValueByPath($array, ['Foo', 'Bar', 'Bax', 2]));
    }

    /**
     * @test
     */
    public function getValueByPathReturnsNullIfThePathHasMoreSegmentsThanTheGivenArray()
    {
        $array = ['Foo' => ['Bar' => ['Baz' => 'the value']]];
        $this->assertNull(\TYPO3\CMS\Extbase\Utility\ArrayUtility::getValueByPath($array, ['Foo', 'Bar', 'Baz', 'Bux']));
    }

    /**
     * @test
     */
    public function convertObjectToArrayConvertsNestedObjectsToArray()
    {
        $object = new \stdClass();
        $object->a = 'v';
        $object->b = new \stdClass();
        $object->b->c = 'w';
        $object->d = ['i' => 'foo', 'j' => 12, 'k' => true, 'l' => new \stdClass()];
        $array = \TYPO3\CMS\Extbase\Utility\ArrayUtility::convertObjectToArray($object);
        $expected = [
            'a' => 'v',
            'b' => [
                'c' => 'w'
            ],
            'd' => [
                'i' => 'foo',
                'j' => 12,
                'k' => true,
                'l' => []
            ]
        ];
        $this->assertSame($expected, $array);
    }

    /**
     * @test
     */
    public function setValueByPathSetsValueRecursivelyIfPathIsArray()
    {
        $array = [];
        $path = ['foo', 'bar', 'baz'];
        $expectedValue = ['foo' => ['bar' => ['baz' => 'The Value']]];
        $actualValue = \TYPO3\CMS\Extbase\Utility\ArrayUtility::setValueByPath($array, $path, 'The Value');
        $this->assertSame($expectedValue, $actualValue);
    }

    /**
     * @test
     */
    public function setValueByPathSetsValueRecursivelyIfPathIsString()
    {
        $array = [];
        $path = 'foo.bar.baz';
        $expectedValue = ['foo' => ['bar' => ['baz' => 'The Value']]];
        $actualValue = \TYPO3\CMS\Extbase\Utility\ArrayUtility::setValueByPath($array, $path, 'The Value');
        $this->assertSame($expectedValue, $actualValue);
    }

    /**
     * @test
     */
    public function setValueByPathRecursivelyMergesAnArray()
    {
        $array = ['foo' => ['bar' => 'should be overriden'], 'bar' => 'Baz'];
        $path = ['foo', 'bar', 'baz'];
        $expectedValue = ['foo' => ['bar' => ['baz' => 'The Value']], 'bar' => 'Baz'];
        $actualValue = \TYPO3\CMS\Extbase\Utility\ArrayUtility::setValueByPath($array, $path, 'The Value');
        $this->assertSame($expectedValue, $actualValue);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function setValueByPathThrowsExceptionIfPathIsNoArrayOrString()
    {
        $array = ['Foo' => ['Bar' => ['Baz' => [2 => 'the value']]]];
        \TYPO3\CMS\Extbase\Utility\ArrayUtility::setValueByPath($array, null, 'Some Value');
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function setValueByPathThrowsExceptionIfSubjectIsNoArray()
    {
        $subject = 'foobar';
        \TYPO3\CMS\Extbase\Utility\ArrayUtility::setValueByPath($subject, 'foo', 'bar');
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function setValueByPathThrowsExceptionIfSubjectIsNoArrayAccess()
    {
        $subject = new \stdClass();
        \TYPO3\CMS\Extbase\Utility\ArrayUtility::setValueByPath($subject, 'foo', 'bar');
    }

    /**
     * @test
     */
    public function setValueByLeavesInputArrayUnchanged()
    {
        $subject = ($subjectBackup = ['foo' => 'bar']);
        \TYPO3\CMS\Extbase\Utility\ArrayUtility::setValueByPath($subject, 'foo', 'baz');
        $this->assertSame($subject, $subjectBackup);
    }

    /**
     * @test
     */
    public function unsetValueByPathDoesNotModifyAnArrayIfThePathWasNotFound()
    {
        $array = ['foo' => ['bar' => ['baz' => 'Some Value']], 'bar' => 'Baz'];
        $path = ['foo', 'bar', 'nonExistingKey'];
        $expectedValue = $array;
        $actualValue = \TYPO3\CMS\Extbase\Utility\ArrayUtility::unsetValueByPath($array, $path);
        $this->assertSame($expectedValue, $actualValue);
    }

    /**
     * @test
     */
    public function unsetValueByPathRemovesSpecifiedKey()
    {
        $array = ['foo' => ['bar' => ['baz' => 'Some Value']], 'bar' => 'Baz'];
        $path = ['foo', 'bar', 'baz'];
        $expectedValue = ['foo' => ['bar' => []], 'bar' => 'Baz'];
        $actualValue = \TYPO3\CMS\Extbase\Utility\ArrayUtility::unsetValueByPath($array, $path);
        $this->assertSame($expectedValue, $actualValue);
    }

    /**
     * @test
     */
    public function unsetValueByPathRemovesSpecifiedKeyIfPathIsString()
    {
        $array = ['foo' => ['bar' => ['baz' => 'Some Value']], 'bar' => 'Baz'];
        $path = 'foo.bar.baz';
        $expectedValue = ['foo' => ['bar' => []], 'bar' => 'Baz'];
        $actualValue = \TYPO3\CMS\Extbase\Utility\ArrayUtility::unsetValueByPath($array, $path);
        $this->assertSame($expectedValue, $actualValue);
    }

    /**
     * @test
     */
    public function unsetValueByPathRemovesSpecifiedBranch()
    {
        $array = ['foo' => ['bar' => ['baz' => 'Some Value']], 'bar' => 'Baz'];
        $path = ['foo'];
        $expectedValue = ['bar' => 'Baz'];
        $actualValue = \TYPO3\CMS\Extbase\Utility\ArrayUtility::unsetValueByPath($array, $path);
        $this->assertSame($expectedValue, $actualValue);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function unsetValueByPathThrowsExceptionIfPathIsNoArrayOrString()
    {
        $array = ['Foo' => ['Bar' => ['Baz' => [2 => 'the value']]]];
        \TYPO3\CMS\Extbase\Utility\ArrayUtility::unsetValueByPath($array, null);
    }

    /**
     * @test
     */
    public function removeEmptyElementsRecursivelyRemovesNullValues()
    {
        $array = ['EmptyElement' => null, 'Foo' => ['Bar' => ['Baz' => ['NotNull' => '', 'AnotherEmptyElement' => null]]]];
        $expectedResult = ['Foo' => ['Bar' => ['Baz' => ['NotNull' => '']]]];
        $actualResult = \TYPO3\CMS\Extbase\Utility\ArrayUtility::removeEmptyElementsRecursively($array);
        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function removeEmptyElementsRecursivelyRemovesEmptySubArrays()
    {
        $array = ['EmptyElement' => [], 'Foo' => ['Bar' => ['Baz' => ['AnotherEmptyElement' => null]]], 'NotNull' => 123];
        $expectedResult = ['NotNull' => 123];
        $actualResult = \TYPO3\CMS\Extbase\Utility\ArrayUtility::removeEmptyElementsRecursively($array);
        $this->assertSame($expectedResult, $actualResult);
    }

    public function arrayMergeRecursiveOverruleData()
    {
        return [
            'simple usage' => [
                'inputArray1' => [
                    'k1' => 'v1',
                    'k2' => 'v2'
                ],
                'inputArray2' => [
                    'k2' => 'v2a',
                    'k3' => 'v3'
                ],
                'dontAddNewKeys' => false,
                // default
                'emptyValuesOverride' => true,
                // default
                'expected' => [
                    'k1' => 'v1',
                    'k2' => 'v2a',
                    'k3' => 'v3'
                ]
            ],
            'simple usage with recursion' => [
                'inputArray1' => [
                    'k1' => 'v1',
                    'k2' => [
                        'k2.1' => 'v2.1',
                        'k2.2' => 'v2.2'
                    ]
                ],
                'inputArray2' => [
                    'k2' => [
                        'k2.2' => 'v2.2a',
                        'k2.3' => 'v2.3'
                    ],
                    'k3' => 'v3'
                ],
                'dontAddNewKeys' => false,
                // default
                'emptyValuesOverride' => true,
                // default
                'expected' => [
                    'k1' => 'v1',
                    'k2' => [
                        'k2.1' => 'v2.1',
                        'k2.2' => 'v2.2a',
                        'k2.3' => 'v2.3'
                    ],
                    'k3' => 'v3'
                ]
            ],
            'simple type should override array (k2)' => [
                'inputArray1' => [
                    'k1' => 'v1',
                    'k2' => [
                        'k2.1' => 'v2.1'
                    ]
                ],
                'inputArray2' => [
                    'k2' => 'v2a',
                    'k3' => 'v3'
                ],
                'dontAddNewKeys' => false,
                // default
                'emptyValuesOverride' => true,
                // default
                'expected' => [
                    'k1' => 'v1',
                    'k2' => 'v2a',
                    'k3' => 'v3'
                ]
            ],
            'null should override array (k2)' => [
                'inputArray1' => [
                    'k1' => 'v1',
                    'k2' => [
                        'k2.1' => 'v2.1'
                    ]
                ],
                'inputArray2' => [
                    'k2' => null,
                    'k3' => 'v3'
                ],
                'dontAddNewKeys' => false,
                // default
                'emptyValuesOverride' => true,
                // default
                'expected' => [
                    'k1' => 'v1',
                    'k2' => null,
                    'k3' => 'v3'
                ]
            ]
        ];
    }

    /**
     * @test
     *
     * @param array $inputArray1
     * @param array $inputArray2
     * @param bool $dontAddNewKeys
     * @param bool $emptyValuesOverride
     * @param array $expected
     *
     * @dataProvider arrayMergeRecursiveOverruleData
     */
    public function arrayMergeRecursiveOverruleMergesSimpleArrays(array $inputArray1, array $inputArray2, $dontAddNewKeys, $emptyValuesOverride, array $expected)
    {
        $this->assertSame($expected, \TYPO3\CMS\Extbase\Utility\ArrayUtility::arrayMergeRecursiveOverrule($inputArray1, $inputArray2, $dontAddNewKeys, $emptyValuesOverride));
    }

    /**
     * @test
     */
    public function integerExplodeReturnsArrayOfIntegers()
    {
        $inputString = '1,2,3,4,5,6';
        $expected = [1, 2, 3, 4, 5, 6];
        $this->assertSame($expected, \TYPO3\CMS\Extbase\Utility\ArrayUtility::integerExplode(',', $inputString));
    }

    /**
     * @test
     */
    public function integerExplodeReturnsZeroForStringValues()
    {
        $inputString = '1,abc,3,,5';
        $expected = [1, 0, 3, 0, 5];
        $this->assertSame($expected, \TYPO3\CMS\Extbase\Utility\ArrayUtility::integerExplode(',', $inputString));
    }

    /**
     * dataProvider for sortArrayWithIntegerKeys
     *
     * @return array
     */
    public function sortArrayWithIntegerKeysDataProvider()
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
                ]
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
                ]
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
                ]
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
    public function sortArrayWithIntegerKeysSortsNumericArrays(array $arrayToSort, array $expectedArray)
    {
        $sortedArray = \TYPO3\CMS\Extbase\Utility\ArrayUtility::sortArrayWithIntegerKeys($arrayToSort);
        $this->assertSame($sortedArray, $expectedArray);
    }
}
