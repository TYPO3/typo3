<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Susanne Moog <typo3@susanne-moog.de>
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
 * Testcase for class t3lib_utility_array
 *
 * @author Susanne Moog <typo3@susanne-moog.de>
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 *
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_utility_ArrayTest extends tx_phpunit_testcase {

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
				array(),
			),
			'empty string as needle' => array(
				'',
				array(
					'',
					'apple',
				),
				array(
					'',
				),
			),
			'flat array searching for string' => array(
				'banana',
				array(
					'apple',
					'banana',
				),
				array(
					1 => 'banana',
				),
			),
			'flat array searching for string with two matches' => array(
				'banana',
				array(
					'foo' => 'apple',
					'firstbanana' => 'banana',
					'secondbanana' => 'banana',
				),
				array(
					'firstbanana' => 'banana',
					'secondbanana' => 'banana',
				),
			),
			'multi dimensional array searching for string with multiple matches' => array(
				'banana',
				array(
					'foo' => 'apple',
					'firstbanana' => 'banana',
					'grape' => array(
						'foo2' => 'apple2',
						'secondbanana' => 'banana',
						'foo3' => array(),
					),
					'bar' => 'orange',
				),
				array(
					'firstbanana' => 'banana',
					'grape' => array(
						'secondbanana' => 'banana',
					),
				),
			),
			'multi dimensional array searching for integer with multiple matches' => array(
				42,
				array(
					'foo' => 23,
					'bar' => 42,
					array(
						'foo' => 23,
						'bar' => 42,
					),
				),
				array(
					'bar' => 42,
					array(
						'bar' => 42,
					),
				),
			),
			'flat array searching for boolean TRUE' => array(
				TRUE,
				array(
					23 => FALSE,
					42 => TRUE,
				),
				array(
					42 => TRUE,
				),
			),
			'multi dimensional array searching for boolean FALSE' => array(
				FALSE,
				array(
					23 => FALSE,
					42 => TRUE,
					'foo' => array(
						23 => FALSE,
						42 => TRUE,
					),
				),
				array(
					23 => FALSE,
					'foo' => array(
						23 => FALSE,
					),
				),
			),
			'flat array searching for array' => array(
				array(
					'foo' => 'bar',
				),
				array(
					'foo' => 'bar',
					'foobar' => array(
						'foo' => 'bar',
					),
				),
				array(
					'foobar' => array(
						'foo' => 'bar',
					),
				),
			),
		);
	}

	/**
	 * @test
	 * @dataProvider filterByValueRecursive
	 */
	public function filterByValueRecursiveCorrectlyFiltersArray($needle, $haystack, $expectedResult) {
		$this->assertEquals($expectedResult, t3lib_utility_Array::filterByValueRecursive($needle, $haystack));
	}

	/**
	 * @test
	 */
	public function filterByValueRecursiveMatchesReferencesToSameObject() {
		$instance = new stdClass();
		$this->assertEquals(array($instance), t3lib_utility_Array::filterByValueRecursive($instance, array($instance)));
	}

	/**
	 * @test
	 */
	public function filterByValueRecursiveDoesNotMatchDifferentInstancesOfSameClass() {
		$this->assertEquals(array(), t3lib_utility_Array::filterByValueRecursive(new stdClass(), array(new stdClass())));
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
		$className = uniqid('t3lib_utility_Array');
		eval(
			'class ' . $className . ' extends t3lib_utility_Array {' .
			'  public static function getValueByPath() {' .
			'    return 42;' .
			'  }' .
			'}'
		);
		$this->assertTrue($className::isValidPath(array('foo'), 'foo'));
	}

	/**
	 * @test
	 */
	public function isValidPathReturnsFalseIfPathDoesNotExist() {
		$className = uniqid('t3lib_utility_Array');
		eval(
			'class ' . $className . ' extends t3lib_utility_Array {' .
			'  public static function getValueByPath() {' .
			'    throw new RuntimeException(\'foo\', 123);' .
			'  }' .
			'}'
		);
		$this->assertFalse($className::isValidPath(array('foo'), 'foo'));
	}

	///////////////////////
	// Tests concerning getValueByPath
	///////////////////////

	/**
	 * @test
	 * @expectedException RuntimeException
	 */
	public function getValueByPathThrowsExceptionIfPathIsEmpty() {
		t3lib_utility_Array::getValueByPath(array(), '');
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
					'bar' => array(),
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
					'bar' => array(),
				),
				'"foo\"bar"/baz',
				42
			),
				// Negative test: Method could be improved here
			'path with doubletick does not work' => array(
				array(
					'fo"o' => array(
						'bar' => 42,
					),
				),
				'fo"o/foobar',
				42,
			),
		);
	}

	/**
	 * @test
	 * @dataProvider getValueByPathInvalidPathDataProvider
 	 * @expectedException RuntimeException
	 */
	public function getValueByPathThrowsExceptionIfPathNotExists(array $array, $path) {
		t3lib_utility_Array::getValueByPath($array, $path);
	}

	/**
	 * Data provider for getValueByPathReturnsCorrectValue
	 * Every array splits into:
	 * - Array to get value from
	 * - String path
	 * - Expected result
	 */
	public function getValueByPathValidDataProvider() {
		$testObject = new StdClass();
		$testObject->foo = 'foo';
		$testObject->bar = 'bar';

		return array(
			'integer in multi level array' => array(
				array(
					'foo' => array(
						'bar' => array(
							'baz' => 42,
						),
						'bar2' => array(),
					),
				),
				'foo/bar/baz',
				42,
			),
			'zero integer in multi level array' => array(
				array(
					'foo' => array(
						'bar' => array(
							'baz' => 0,
						),
					),
				),
				'foo/bar/baz',
				0,
			),
			'NULL value in multi level array' => array(
				array(
					'foo' => array(
						'baz' => NULL,
					),
				),
				'foo/baz',
				NULL,
			),
			'get string value' => array(
				array(
					'foo' => array(
						'baz' => 'this is a test string',
					),
				),
				'foo/baz',
				'this is a test string',
			),
			'get boolean value: FALSE' => array(
				array(
					'foo' => array(
						'baz' => FALSE,
					),
				),
				'foo/baz',
				FALSE,
			),
			'get boolean value: TRUE' => array(
				array(
					'foo' => array(
						'baz' => TRUE,
					),
				),
				'foo/baz',
				TRUE,
			),
			'get object value' => array(
				array(
					'foo' => array(
						'baz' => $testObject,
					),
				),
				'foo/baz',
				$testObject,
			),
			'enclosed path' => array(
				array(
					'foo/bar' => array(
						'foobar' => 42,
					),
				),
				'"foo/bar"/foobar',
				42,
			),
		);
	}

	/**
	 * @test
	 * @dataProvider getValueByPathValidDataProvider
	 */
	public function getValueByPathGetsCorrectValue(array $array, $path, $expectedResult) {
		$this->assertEquals(
			$expectedResult,
			t3lib_utility_Array::getValueByPath($array, $path)
		);
	}

	/**
	 * @test
	 */
	public function getValueByPathAccpetsDifferentDelimeter() {
		$input = array(
			'foo' => array(
				'bar' => array(
					'baz' => 42,
				),
				'bar2' => array(),
			),
		);
		$searchPath = 'foo%bar%baz';
		$expected = 42;
		$delimeter = '%';
		$this->assertEquals($expected, t3lib_utility_Array::getValueByPath($input, $searchPath, $delimeter));
	}

	///////////////////////
	// Tests concerning setValueByPath
	///////////////////////

	/**
	 * @test
	 * @expectedException RuntimeException
	 */
	public function setValueByPathThrowsExceptionIfPathIsEmpty() {
		t3lib_utility_Array::setValueByPath(array(), '', NULL);
	}

	/**
	 * @test
	 * @expectedException RuntimeException
	 */
	public function setValueByPathThrowsExceptionIfPathIsNotAString() {
		t3lib_utility_Array::setValueByPath(array(), array('foo'), NULL);
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
		$testObject = new StdClass();
		$testObject->foo = 'foo';
		$testObject->bar = 'bar';

		return array(
			'set integer value: 42' => array(
				array(
					'foo' => array(
						'bar' => array(
							'baz' => 0,
						),
					),
				),
				'foo/bar/baz',
				42,
				array(
					'foo' => array(
						'bar' => array(
							'baz' => 42,
						),
					),
				),
			),
			'set integer value: 0' => array(
				array(
					'foo' => array(
						'bar' => array(
							'baz' => 42,
						),
					),
				),
				'foo/bar/baz',
				0,
				array(
					'foo' => array(
						'bar' => array(
							'baz' => 0,
						),
					),
				),
			),
			'set null value' => array(
				array(
					'foo' => array(
						'bar' => array(
							'baz' => 42,
						),
					),
				),
				'foo/bar/baz',
				NULL,
				array(
					'foo' => array(
						'bar' => array(
							'baz' => NULL,
						),
					),
				),
			),
			'set array value' => array(
				array(
					'foo' => array(
						'bar' => array(
							'baz' => 42,
						),
					),
				),
				'foo/bar/baz',
				array(
					'foo' => 123,
				),
				array(
					'foo' => array(
						'bar' => array(
							'baz' => array(
								'foo' => 123,
							),
						),
					),
				),
			),
			'set boolean value: FALSE' => array(
				array(
					'foo' => array(
						'bar' => array(
							'baz' => TRUE,
						),
					),
				),
				'foo/bar/baz',
				FALSE,
				array(
					'foo' => array(
						'bar' => array(
							'baz' => FALSE,
						),
					),
				),
			),
			'set boolean value: TRUE' => array(
				array(
					'foo' => array(
						'bar' => array(
							'baz' => NULL,
						),
					),
				),
				'foo/bar/baz',
				TRUE,
				array(
					'foo' => array(
						'bar' => array(
							'baz' => TRUE,
						),
					),
				),
			),
			'set object value' => array(
				array(
					'foo' => array(
						'bar' => array(
							'baz' => NULL,
						),
					),
				),
				'foo/bar/baz',
				$testObject,
				array(
					'foo' => array(
						'bar' => array(
							'baz' => $testObject,
						),
					),
				),
			),
			'multi keys in array' => array(
				array(
					'foo' => array(
						'bar' => array(
							'baz' => 'value',
						),
						'bar2' => array(
							'baz' => 'value',
						),
					),
				),
				'foo/bar2/baz',
				'newValue',
				array(
					'foo' => array(
						'bar' => array(
							'baz' => 'value',
						),
						'bar2' => array(
							'baz' => 'newValue',
						),
					),
				),
			),
		);
	}

	/**
	 * @test
	 * @dataProvider setValueByPathSetsCorrectValueDataProvider
	 */
	public function setValueByPathSetsCorrectValue(array $array, $path, $value, $expectedResult) {
		$this->assertEquals(
			$expectedResult,
			t3lib_utility_Array::setValueByPath($array, $path, $value)
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
				'a' => NULL,
			),
		);
		$expectedResult = array(
			'a' => NULL,
			'd' => array(
				'a' => NULL,
				'b' => NULL,
				'c' => NULL,
				'd' => NULL,
			),
			'z' => NULL,
		);
		$this->assertSame($expectedResult, t3lib_utility_Array::sortByKeyRecursive($unsortedArray));
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
					'baz4' => array(),
				),
			),
			'baz' => 23,
			'foobar' => NULL,
		);
		$expected = 'array(' . LF .
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
		')';

		$this->assertSame($expected, t3lib_utility_Array::arrayExport($array));
	}

	/**
	 * @test
	 * @expectedException RuntimeException
	 */
	public function arrayExportThrowsExceptionIfObjectShouldBeExported() {
		$array = array(
			'foo' => array(
				'bar' => new stdClass(),
			),
		);
		t3lib_utility_Array::arrayExport($array);
	}
}

?>