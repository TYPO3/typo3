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
 * Testcase for class t3lib_utility_Math
 *
 * @author Susanne Moog <typo3@susanne-moog.de>
 *
 * @package TYPO3
 * @subpackage t3lib
 */

class t3lib_utility_MathTest extends tx_phpunit_testcase {

	//////////////////////////////////
	// Tests concerning forceIntegerInRange
	//////////////////////////////////
	/**
	 * Data provider for forceIntegerInRangeForcesIntegerIntoBoundaries
	 *
	 * @return array expected values, arithmetic expression
	 */
	public function forceIntegerInRangeForcesIntegerIntoDefaultBoundariesDataProvider() {
		return array(
			'negativeValue' => array(0, -10),
			'normalValue' => array(30, 30),
			'veryHighValue' => array(2000000000, 3000000001),
			'zeroValue' => array(0, 0),
			'anotherNormalValue' => array(12309, 12309)
		);
	}

	/**
	 * @test
	 * @dataProvider forceIntegerInRangeForcesIntegerIntoDefaultBoundariesDataProvider
	 */
	public function forceIntegerInRangeForcesIntegerIntoDefaultBoundaries($expected, $value) {
		 $this->assertEquals($expected, t3lib_utility_Math::forceIntegerInRange($value, 0));
	}

	/**
	 * @test
	 */
	public function forceIntegerInRangeSetsDefaultValueIfZeroValueIsGiven() {
		 $this->assertEquals(42, t3lib_utility_Math::forceIntegerInRange('', 0, 2000000000, 42));
	}

	//////////////////////////////////
	// Tests concerning convertToPositiveInteger
	//////////////////////////////////
	/**
	 * @test
	 */
	public function convertToPositiveIntegerReturnsZeroForNegativeValues() {
		$this->assertEquals(0, t3lib_utility_Math::convertToPositiveInteger(-123));
	}

	/**
	 * @test
	 */
	public function convertToPositiveIntegerReturnsTheInputValueForPositiveValues() {
		$this->assertEquals(123, t3lib_utility_Math::convertToPositiveInteger(123));
	}

}

?>