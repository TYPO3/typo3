<?php
namespace TYPO3\CMS\Core\Tests\Unit\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Susanne Moog <typo3@susanne-moog.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Testcase for class \TYPO3\CMS\Core\Utility\ArrayUtility
 *
 * @author Susanne Moog <typo3@susanne-moog.de>
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class ArrayUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

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
	public function filterByValueRecursive() {
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
				TRUE,
				array(
					23 => FALSE,
					42 => TRUE
				),
				array(
					42 => TRUE
				)
			),
			'multi dimensional array searching for boolean FALSE' => array(
				FALSE,
				array(
					23 => FALSE,
					42 => TRUE,
					'foo' => array(
						23 => FALSE,
						42 => TRUE
					)
				),
				array(
					23 => FALSE,
					'foo' => array(
						23 => FALSE
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
	 */
	public function filterByValueRecursiveCorrectlyFiltersArray($needle, $haystack, $expectedResult) {
		$this->assertEquals(
			$expectedResult,
			\TYPO3\CMS\Core\Utility\ArrayUtility::filterByValueRecursive($needle, $haystack)
		);
	}

	/**
	 * @test
	 */
	public function filterByValueRecursiveMatchesReferencesToSameObject() {
		$instance = new \stdClass();
		$this->assertEquals(
			array($instance),
			\TYPO3\CMS\Core\Utility\ArrayUtility::filterByValueRecursive($instance, array($instance))
		);
	}

	/**
	 * @test
	 */
	public function filterByValueRecursiveDoesNotMatchDifferentInstancesOfSameClass() {
		$this->assertEquals(
			array(),
			\TYPO3\CMS\Core\Utility\ArrayUtility::filterByValueRecursive(new \stdClass(), array(new \stdClass()))
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
	public function isValidPathReturnsTrueIfPathExists() {
		$namespace = 'TYPO3\\CMS\\Core\\Utility';
		$className = uniqid('ArrayUtility');
		eval(
			'namespace ' . $namespace . ';' .
			'class ' . $className . ' extends \\TYPO3\\CMS\\Core\\Utility\\ArrayUtility {' .
			'  public static function getValueByPath() {' .
			'    return 42;' .
			'  }' .
			'}'
		);
		$className = $namespace . '\\' . $className;
		$this->assertTrue($className::isValidPath(array('foo'), 'foo'));
	}

	/**
	 * @test
	 */
	public function isValidPathReturnsFalseIfPathDoesNotExist() {
		$namespace = 'TYPO3\\CMS\\Core\\Utility';
		$className = uniqid('ArrayUtility');
		eval(
			'namespace ' . $namespace . ';' .
			'class ' . $className . ' extends \\TYPO3\\CMS\\Core\\Utility\\ArrayUtility {' .
			'  public static function getValueByPath() {' .
			'    throw new \RuntimeException(\'foo\', 123);' .
			'  }' .
			'}'
		);
		$className = $namespace . '\\' . $className;
		$this->assertFalse($className::isValidPath(array('foo'), 'foo'));
	}

	///////////////////////
	// Tests concerning getValueByPath
	///////////////////////
	/**
	 * @test
	 * @expectedException \RuntimeException
	 */
	public function getValueByPathThrowsExceptionIfPathIsEmpty() {
		\TYPO3\CMS\Core\Utility\ArrayUtility::getValueByPath(array(), '');
	}

	/**
	 * Data provider for getValueByPathThrowsExceptionIfPathNotExists
	 * Every array splits into:
	 * - Array to get value from
	 * - String path
	 * - Expected result
	 */
	public function getValueByPathInvalidPathDataProvider() {
		return array(
			'not existing path 1' => array(
				array(
					'foo' => array()
				),
				'foo/bar/baz',
				FALSE
			),
			'not existing path 2' => array(
				array(
					'foo' => array(
						'baz' => 42
					),
					'bar' => array()
				),
				'foo/bar/baz',
				FALSE
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
	 */
	public function getValueByPathThrowsExceptionIfPathNotExists(array $array, $path) {
		\TYPO3\CMS\Core\Utility\ArrayUtility::getValueByPath($array, $path);
	}

	/**
	 * Data provider for getValueByPathReturnsCorrectValue
	 * Every array splits into:
	 * - Array to get value from
	 * - String path
	 * - Expected result
	 */
	public function getValueByPathValidDataProvider() {
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
						'baz' => NULL
					)
				),
				'foo/baz',
				NULL
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
						'baz' => FALSE
					)
				),
				'foo/baz',
				FALSE
			),
			'get boolean value: TRUE' => array(
				array(
					'foo' => array(
						'baz' => TRUE
					)
				),
				'foo/baz',
				TRUE
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
	 */
	public function getValueByPathGetsCorrectValue(array $array, $path, $expectedResult) {
		$this->assertEquals($expectedResult, \TYPO3\CMS\Core\Utility\ArrayUtility::getValueByPath($array, $path));
	}

	/**
	 * @test
	 */
	public function getValueByPathAccpetsDifferentDelimeter() {
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
		$delimeter = '%';
		$this->assertEquals(
			$expected,
			\TYPO3\CMS\Core\Utility\ArrayUtility::getValueByPath($input, $searchPath, $delimeter)
		);
	}

	///////////////////////
	// Tests concerning setValueByPath
	///////////////////////
	/**
	 * @test
	 * @expectedException \RuntimeException
	 */
	public function setValueByPathThrowsExceptionIfPathIsEmpty() {
		\TYPO3\CMS\Core\Utility\ArrayUtility::setValueByPath(array(), '', NULL);
	}

	/**
	 * @test
	 * @expectedException \RuntimeException
	 */
	public function setValueByPathThrowsExceptionIfPathIsNotAString() {
		\TYPO3\CMS\Core\Utility\ArrayUtility::setValueByPath(array(), array('foo'), NULL);
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
	public function setValueByPathSetsCorrectValueDataProvider() {
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
				NULL,
				array(
					'foo' => array(
						'bar' => array(
							'baz' => NULL
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
							'baz' => TRUE
						)
					)
				),
				'foo/bar/baz',
				FALSE,
				array(
					'foo' => array(
						'bar' => array(
							'baz' => FALSE
						)
					)
				)
			),
			'set boolean value: TRUE' => array(
				array(
					'foo' => array(
						'bar' => array(
							'baz' => NULL
						)
					)
				),
				'foo/bar/baz',
				TRUE,
				array(
					'foo' => array(
						'bar' => array(
							'baz' => TRUE
						)
					)
				)
			),
			'set object value' => array(
				array(
					'foo' => array(
						'bar' => array(
							'baz' => NULL
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
	 */
	public function setValueByPathSetsCorrectValue(array $array, $path, $value, $expectedResult) {
		$this->assertEquals(
			$expectedResult,
			\TYPO3\CMS\Core\Utility\ArrayUtility::setValueByPath($array, $path, $value)
		);
	}

	///////////////////////
	// Tests concerning sortByKeyRecursive
	///////////////////////
	/**
	 * @test
	 */
	public function sortByKeyRecursiveCheckIfSortingIsCorrect() {
		$unsortedArray = array(
			'z' => NULL,
			'a' => NULL,
			'd' => array(
				'c' => NULL,
				'b' => NULL,
				'd' => NULL,
				'a' => NULL
			)
		);
		$expectedResult = array(
			'a' => NULL,
			'd' => array(
				'a' => NULL,
				'b' => NULL,
				'c' => NULL,
				'd' => NULL
			),
			'z' => NULL
		);
		$this->assertSame($expectedResult, \TYPO3\CMS\Core\Utility\ArrayUtility::sortByKeyRecursive($unsortedArray));
	}

	///////////////////////
	// Tests concerning arrayExport
	///////////////////////
	/**
	 * @test
	 */
	public function arrayExportReturnsFormattedMultidimensionalArray() {
		$array = array(
			'foo' => array(
				'bar' => 42,
				'bar2' => array(
					'baz' => 'val\'ue',
					'baz2' => TRUE,
					'baz3' => FALSE,
					'baz4' => array()
				)
			),
			'baz' => 23,
			'foobar' => NULL,
			'qux' => 0.1,
			'qux2' => 0.000000001,
		);
		$expected =
			'array(' . LF .
				TAB . '\'foo\' => array(' . LF .
					TAB . TAB . '\'bar\' => 42,' . LF .
					TAB . TAB . '\'bar2\' => array(' . LF .
						TAB . TAB . TAB . '\'baz\' => \'val\\\'ue\',' . LF .
						TAB . TAB . TAB . '\'baz2\' => TRUE,' . LF .
						TAB . TAB . TAB . '\'baz3\' => FALSE,' . LF .
						TAB . TAB . TAB . '\'baz4\' => array(),' . LF .
					TAB . TAB . '),' . LF .
				TAB . '),' . LF .
				TAB . '\'baz\' => 23,' . LF .
				TAB . '\'foobar\' => NULL,' . LF .
				TAB . '\'qux\' => 0.1,' . LF .
				TAB . '\'qux2\' => 1.0E-9,' . LF .
			')';
		$this->assertSame($expected, \TYPO3\CMS\Core\Utility\ArrayUtility::arrayExport($array));
	}

	/**
	 * @test
	 * @expectedException \RuntimeException
	 */
	public function arrayExportThrowsExceptionIfObjectShouldBeExported() {
		$array = array(
			'foo' => array(
				'bar' => new \stdClass()
			)
		);
		\TYPO3\CMS\Core\Utility\ArrayUtility::arrayExport($array);
	}

	/**
	 * @test
	 */
	public function arrayExportReturnsNumericArrayKeys() {
		$array = array(
			'foo' => 'string key',
			23 => 'integer key',
			'42' => 'string key representing integer'
		);
		$expected =
			'array(' . LF .
				TAB . '\'foo\' => \'string key\',' . LF .
				TAB . '23 => \'integer key\',' . LF .
				TAB . '42 => \'string key representing integer\',' . LF .
			')';
		$this->assertSame($expected, \TYPO3\CMS\Core\Utility\ArrayUtility::arrayExport($array));
	}

	/**
	 * @test
	 */
	public function arrayExportReturnsNoKeyIndexForConsecutiveCountedArrays() {
		$array = array(
			0 => 'zero',
			1 => 'one',
			2 => 'two'
		);
		$expected =
			'array(' . LF .
				TAB . '\'zero\',' . LF .
				TAB . '\'one\',' . LF .
				TAB . '\'two\',' . LF .
			')';
		$this->assertSame($expected, \TYPO3\CMS\Core\Utility\ArrayUtility::arrayExport($array));
	}

	/**
	 * @test
	 */
	public function arrayExportReturnsKeyIndexForNonConsecutiveCountedArrays() {
		$array = array(
			0 => 'zero',
			1 => 'one',
			3 => 'three',
			4 => 'four'
		);
		$expected =
			'array(' . LF .
				TAB . '0 => \'zero\',' . LF .
				TAB . '1 => \'one\',' . LF .
				TAB . '3 => \'three\',' . LF .
				TAB . '4 => \'four\',' . LF .
			')';
		$this->assertSame($expected, \TYPO3\CMS\Core\Utility\ArrayUtility::arrayExport($array));
	}


	///////////////////////
	// Tests concerning flatten
	///////////////////////

	/**
	 * @return array
	 */
	public function flattenCalculatesExpectedResultDataProvider() {
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
	public function flattenCalculatesExpectedResult(array $array, array $expected) {
		$this->assertEquals($expected, \TYPO3\CMS\Core\Utility\ArrayUtility::flatten($array));
	}


	///////////////////////
	// Tests concerning intersectRecursive
	///////////////////////

	/**
	 * @return array
	 */
	public function intersectRecursiveCalculatesExpectedResultDataProvider() {
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
					'foo' => NULL,
				),
				array(
					'foo' => 42,
				),
			),
			'null in source value is kept' => array(
				array(
					'foo' => NULL,
				),
				array(
					'foo' => 'bar',
				),
				array(
					'foo' => NULL,
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
					'bar' => NULL,
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
	public function intersectRecursiveCalculatesExpectedResult(array $source, array $mask, array $expected) {
		$this->assertSame($expected, \TYPO3\CMS\Core\Utility\ArrayUtility::intersectRecursive($source, $mask));
	}


	///////////////////////
	// Tests concerning renumberKeysToAvoidLeapsIfKeysAreAllNumeric
	///////////////////////
	/**
	 * @return array
	 */
	public function renumberKeysToAvoidLeapsIfKeysAreAllNumericDataProvider() {
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
	public function renumberKeysToAvoidLeapsIfKeysAreAllNumeric(array $inputArray, array $expected) {
		$this->assertEquals($expected, \TYPO3\CMS\Core\Utility\ArrayUtility::renumberKeysToAvoidLeapsIfKeysAreAllNumeric($inputArray));
	}

}

?>