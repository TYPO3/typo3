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
 * Testcase for class \TYPO3\CMS\Core\Utility\StringUtility
 *
 * @author Susanne Moog <typo3@susanne-moog.de>
 */
class StringUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * Data provider for isLastPartOfStrReturnsTrueForMatchingLastParts
	 *
	 * @return array
	 */
	public function isLastPartOfStringReturnsTrueForMatchingFirstPartDataProvider() {
		return array(
			'match last part of string' => array('hello world', 'world'),
			'match last char of string' => array('hellod world', 'd'),
			'match whole string' => array('hello', 'hello'),
			'integer is part of string with same number' => array('24', 24),
			'string is part of integer with same number' => array(24, '24'),
			'integer is part of string starting with same number' => array('please gimme beer, 24', 24)
		);
	}

	/**
	 * @test
	 * @dataProvider isLastPartOfStringReturnsTrueForMatchingFirstPartDataProvider
	 */
	public function isLastPartOfStringReturnsTrueForMatchingFirstPart($string, $part) {
		$this->assertTrue(\TYPO3\CMS\Core\Utility\StringUtility::isLastPartOfString($string, $part));
	}

	/**
	 * Data provider for checkisLastPartOfStringReturnsFalseForNotMatchingFirstParts
	 *
	 * @return array
	 */
	public function isLastPartOfStringReturnsFalseForNotMatchingFirstPartDataProvider() {
		return array(
			'no string match' => array('hello', 'bye'),
			'no case sensitive string match' => array('hello world', 'World'),
		);
	}

	/**
	 * @test
	 * @dataProvider isLastPartOfStringReturnsFalseForNotMatchingFirstPartDataProvider
	 */
	public function isLastPartOfStringReturnsFalseForNotMatchingFirstPart($string, $part) {
		$this->assertFalse(\TYPO3\CMS\Core\Utility\StringUtility::isLastPartOfString($string, $part));
	}

	/**
	 * Data provider for isLastPartOfStringReturnsThrowsExceptionWithInvalidArguments
	 *
	 * @return array
	 */
	public function isLastPartOfStringReturnsInvalidArgumentDataProvider() {
		return array(
			'array is not part of string' => array('string', array()),
			'string is not part of array' => array(array(), 'string'),
			'NULL is not part of string' => array('string', NULL),
			'null is not part of array' => array(NULL, 'string'),
			'NULL is not part of array' => array(array(), NULL),
			'array is not part of null' => array(NULL, array()),
			'NULL is not part of empty string' => array('', NULL),
			'false is not part of empty string' => array('', FALSE),
			'empty string is not part of NULL' => array(NULL, ''),
			'empty string is not part of false' => array(FALSE, ''),
			'empty string is not part of zero integer' => array(0, ''),
			'zero integer is not part of NULL' => array(NULL, 0),
			'zero integer is not part of empty string' => array('', 0),
			'string is not part of object' => array(new \stdClass(), 'foo'),
			'object is not part of string' => array('foo', new \stdClass()),
		);
	}

	/**
	 * @test
	 * @dataProvider isLastPartOfStringReturnsInvalidArgumentDataProvider
	 * @expectedException \InvalidArgumentException
	 */
	public function isLastPartOfStringReturnsThrowsExceptionWithInvalidArguments($string, $part) {
		$this->assertFalse(\TYPO3\CMS\Core\Utility\StringUtility::isLastPartOfString($string, $part));
	}
}

?>