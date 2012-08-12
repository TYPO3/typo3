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
class t3lib_utility_StringTest extends tx_phpunit_testcase {
	

	/**
	 * Data provider for isLastPartOfStrReturnsTrueForMatchingLastParts
	 *
	 * @return array
	 */
	public function isLastPartOfStrReturnsTrueForMatchingFirstPartDataProvider() {
		return array(
			'match last part of string' => array('hello world', 'world'),
			'match last char of string' => array('hellod world', 'd'),
			'match whole string' => array('hello', 'hello'),
			'integer is part of string with same number' => array('24', 24),
			'string is part of integer with same number' => array(24, '24'),
			'integer is part of string starting with same number' => array('please gimme beer, 24', 24),
		);
	}

	/**
	 * @test
	 * @dataProvider isLastPartOfStrReturnsTrueForMatchingFirstPartDataProvider
	 */
	public function isLastPartOfStrReturnsTrueForMatchingFirstPart($string, $part) {
		$this->assertTrue(t3lib_utility_String::isLastPartOfStr($string, $part));
	}

	/**
	 * Data provider for checkisLastPartOfStrReturnsFalseForNotMatchingFirstParts
	 *
	 * @return array
	 */
	public function isLastPartOfStrReturnsFalseForNotMatchingFirstPartDataProvider() {
		return array(
			'no string match' => array('hello', 'bye'),
			'no case sensitive string match' => array('hello world', 'World'),
			'array is not part of string' => array('string', array()),
			'string is not part of array' => array(array(), 'string'),
			'NULL is not part of string' => array('string', NULL),
			'string is not part of array' => array(NULL, 'string'),
			'NULL is not part of array' => array(array(), NULL),
			'array is not part of string' => array(NULL, array()),
			'empty string is not part of empty string' => array('', ''),
			'NULL is not part of empty string' => array('', NULL),
			'false is not part of empty string' => array('', FALSE),
			'empty string is not part of NULL' => array(NULL, ''),
			'empty string is not part of false' => array(FALSE, ''),
			'empty string is not part of zero integer' => array(0, ''),
			'zero integer is not part of NULL' => array(NULL, 0),
			'zero integer is not part of empty string' => array('', 0),
		);
	}

	/**
	 * @test
	 * @dataProvider isLastPartOfStrReturnsFalseForNotMatchingFirstPartDataProvider
	 */
	public function isLastPartOfStrReturnsFalseForNotMatchingFirstPart($string, $part) {
		$this->assertFalse(t3lib_utility_String::isLastPartOfStr($string, $part));
	}

}

?>