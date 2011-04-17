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
 *
 * @package TYPO3
 * @subpackage t3lib
 */

class t3lib_utility_ArrayTest extends tx_phpunit_testcase {

	/**
	 * @test
	 * @dataProvider recursiveArraySearchDataProvider
	 */
	public function recursiveArraySearchFindsFirstKey($haystack) {
		$this->assertEquals(1, t3lib_utility_Array::recursiveArraySearch($haystack, '321'));
	}

	/**
	 * @test
	 * @dataProvider recursiveArraySearchDataProvider
	 */
	public function recursiveArraySearchReturnsFalseIfNeedleNotFound($haystack) {
		$this->assertEquals(FALSE, t3lib_utility_Array::recursiveArraySearch($haystack, 'this is not in the array'));
	}

	/**
	 * @test
	 * @dataProvider recursiveArraySearchDataProvider
	 */
	public function recursiveArraySearchFindsCorrectKeyFromThirdLevelNestedArray(array $haystack) {
		$this->assertEquals(2, t3lib_utility_Array::recursiveArraySearch($haystack, 'test123'));
	}

	/**
	 * Data provider for recursiveArraySearch
	 *
	 * @return array Data sets
	 */
	public function recursiveArraySearchDataProvider() {
		$haystack = array(
			0 => '123',
			1 => array(
				'321',
				'654',
				'test'
			),
			2 => array(
				array(
					'321',
					'654',
					'test123'
				),
			)
		);
		return array(
			0 => array($haystack)
		);
	}

	/**
	 * @test
	 */
	public function insertIntoArrayAtSpecifiedPositionInsertCorrectlyAfterField() {
		$data = array(
			0 => '123',
			1 => '456',
			2 => '789',
			3 => '1011'
		);

		$result = t3lib_utility_Array::insertIntoArrayAtSpecifiedPosition($data, 'test', 'after:789');
		$this->assertEquals('test', $result[3]);
	}

	/**
	 * @test
	 */
	public function insertIntoArrayAtSpecifiedPositionInsertCorrectlyBeforeField() {
		$data = array(
			0 => '123',
			1 => '456',
			2 => '789',
			3 => '1011'
		);

		$result = t3lib_utility_Array::insertIntoArrayAtSpecifiedPosition($data, 'test', 'before:789');
		$this->assertEquals('test', $result[2]);
	}

	/**
	 * @test
	 */
	public function insertIntoArrayAtSpecifiedPositionReplacesFieldCorrectly() {
		$data = array(
			0 => '123',
			1 => '456',
			2 => '789',
			3 => '1011'
		);

		$result = t3lib_utility_Array::insertIntoArrayAtSpecifiedPosition($data, 'test', 'replace:789');
		$this->assertEquals('test', $result[2]);
	}

	/**
	 * @test
	 */
	public function insertIntoArrayAtSpecifiedPositionAppendsDataIfNoPositionGiven() {
		$data = array(
			0 => '123',
			1 => '456',
			2 => '789',
			3 => '1011'
		);
		$result = t3lib_utility_Array::insertIntoArrayAtSpecifiedPosition($data, 'test');
		$this->assertEquals('test', $result[4]);
	}
}

?>