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

	/**
	 * Data provider for filterByValueRecursiveCorrectlyFiltersArray
	 */
	public function filterByValueRecursive() {
			// Every array splits into:
			// - String value to search for
			// - Input array
			// - Expected result array
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

	//////////////////////////////////
	// Tests concerning inArray
	//////////////////////////////////
	/**
	 * Data Provider for inArrayReturnsTrueForFoundNeedle
	 *
	 * return array
	 */
	public function inArrayReturnsTrueForFoundNeedleDataProvider() {
		return array(
			array(
				array(0, 1, 2, 3),
				'0'
			),
			array(
				array(0, 1, 2, 3),
				2
			),
			array(
				array('0', 'one', '2', 'three'),
				'0'
			),
			array(
				array('0', 'one', '2', 'three'),
				0
			),
			array(
				array('0', 'one', '2', 'three'),
				'three'
			),
			array(
				array(0, 1, 2, 3),
				array()
			),
		);
	}

	/**
	 * @test
	 * @dataProvider inArrayReturnsTrueForFoundNeedleDataProvider
	 */
	public function inArrayReturnsTrueForFoundNeedle($haystack, $needle) {
		$this->assertTrue(t3lib_utility_Array::inArray($haystack, $needle));
	}

	/**
	 * Data Provider for inArrayReturnsFalseWhenNeedleIsNotFound
	 *
	 * return array
	 */
	public function inArrayReturnsFalseWhenNeedleIsNotFoundProvider() {
		return array(
			array(
				array(0, 1, 2, 3),
				'one'
			),
			array(
				array('0', 'one', '2', 'three'),
				'ads'
			),
			array(
				array('0', 'one', '2', 'three'),
				NULL
			),
			array(
				array('0', 'one', '2', 'three'),
				13
			),
		);
	}

	/**
	 * @test
	 * @dataProvider inArrayReturnsFalseWhenNeedleIsNotFoundProvider
	 */
	public function inArrayReturnsFalseWhenNeedleIsNotFound($haystack, $needle) {
		$this->assertFalse(t3lib_utility_Array::inArray($haystack, $needle));
	}

	//////////////////////////////////
	// Tests concerning revExplode
	//////////////////////////////////

	/**
	 * @test
	 */
	public function revExplodeExplodesString() {
		$testString = 'my:words:here';
		$expectedArray = array('my:words', 'here');
		$actualArray = t3lib_utility_Array::reverseExplode(':', $testString, 2);

		$this->assertEquals($expectedArray, $actualArray);
	}


	//////////////////////////////////
	// Tests concerning trimExplode
	//////////////////////////////////

	/**
	 * @test
	 */
	public function checkTrimExplodeTrimsSpacesAtElementStartAndEnd() {
		$testString = ' a , b , c ,d ,,  e,f,';
		$expectedArray = array('a', 'b', 'c', 'd', '', 'e', 'f', '');
		$actualArray = t3lib_utility_Array::trimExplode(',', $testString);

		$this->assertEquals($expectedArray, $actualArray);
	}

	/**
	 * @test
	 */
	public function checkTrimExplodeRemovesNewLines() {
		$testString = ' a , b , ' . LF . ' ,d ,,  e,f,';
		$expectedArray = array('a', 'b', 'd', 'e', 'f');
		$actualArray = t3lib_utility_Array::trimExplode(',', $testString, TRUE);

		$this->assertEquals($expectedArray, $actualArray);
	}

	/**
	 * @test
	 */
	public function checkTrimExplodeRemovesEmptyElements() {
		$testString = 'a , b , c , ,d ,, ,e,f,';
		$expectedArray = array('a', 'b', 'c', 'd', 'e', 'f');
		$actualArray = t3lib_utility_Array::trimExplode(',', $testString, TRUE);

		$this->assertEquals($expectedArray, $actualArray);
	}

	/**
	 * @test
	 */
	public function checkTrimExplodeKeepsRemainingResultsWithEmptyItemsAfterReachingLimitWithPositiveParameter() {
		$testString = ' a , b , c , , d,, ,e ';
		$expectedArray = array('a', 'b', 'c,,d,,,e');
			// Limiting returns the rest of the string as the last element
		$actualArray = t3lib_utility_Array::trimExplode(',', $testString, FALSE, 3);

		$this->assertEquals($expectedArray, $actualArray);
	}

	/**
	 * @test
	 */
	public function checkTrimExplodeKeepsRemainingResultsWithoutEmptyItemsAfterReachingLimitWithPositiveParameter() {
		$testString = ' a , b , c , , d,, ,e ';
		$expectedArray = array('a', 'b', 'c,d,e');
			// Limiting returns the rest of the string as the last element
		$actualArray = t3lib_utility_Array::trimExplode(',', $testString, TRUE, 3);

		$this->assertEquals($expectedArray, $actualArray);
	}

	/**
	 * @test
	 */
	public function checkTrimExplodeKeepsRamainingResultsWithEmptyItemsAfterReachingLimitWithNegativeParameter() {
		$testString = ' a , b , c , d, ,e, f , , ';
		$expectedArray = array('a', 'b', 'c', 'd', '', 'e');
			// limiting returns the rest of the string as the last element
		$actualArray = t3lib_utility_Array::trimExplode(',', $testString, FALSE, -3);

		$this->assertEquals($expectedArray, $actualArray);
	}

	/**
	 * @test
	 */
	public function checkTrimExplodeKeepsRamainingResultsWithoutEmptyItemsAfterReachingLimitWithNegativeParameter() {
		$testString = ' a , b , c , d, ,e, f , , ';
		$expectedArray = array('a', 'b', 'c');
			// Limiting returns the rest of the string as the last element
		$actualArray = t3lib_utility_Array::trimExplode(',', $testString, TRUE, -3);

		$this->assertEquals($expectedArray, $actualArray);
	}

	/**
	 * @test
	 */
	public function checkTrimExplodeReturnsExactResultsWithoutReachingLimitWithPositiveParameter() {
		$testString = ' a , b , , c , , , ';
		$expectedArray = array('a', 'b', 'c');
			// Limiting returns the rest of the string as the last element
		$actualArray = t3lib_utility_Array::trimExplode(',', $testString, TRUE, 4);

		$this->assertEquals($expectedArray, $actualArray);
	}

	/**
	 * @test
	 */
	public function checkTrimExplodeKeepsZeroAsString() {
		$testString = 'a , b , c , ,d ,, ,e,f, 0 ,';
		$expectedArray = array('a', 'b', 'c', 'd', 'e', 'f', '0');
		$actualArray = t3lib_utility_Array::trimExplode(',', $testString, TRUE);

		$this->assertEquals($expectedArray, $actualArray);
	}

	//////////////////////////////////
	// Tests concerning intExplode
	//////////////////////////////////

	/**
	 * @test
	 */
	public function intExplodeConvertsStringsToInteger() {
		$testString = '1,foo,2';
		$expectedArray = array(1, 0, 2);
		$actualArray = t3lib_utility_Array::integerExplode(',', $testString);

		$this->assertEquals($expectedArray, $actualArray);
	}

}

?>