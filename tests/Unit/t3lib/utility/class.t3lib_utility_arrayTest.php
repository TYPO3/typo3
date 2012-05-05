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
}

?>