<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2010 Ingo Renner <ingo@typo3.org>
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
 * Testcase for class t3lib_div
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 *
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_divTest extends tx_phpunit_testcase {

	/**
	 * Enable backup of global and system variables
	 *
	 * @var boolean
	 */
	protected $backupGlobals = TRUE;

	/**
	 * Exclude TYPO3_DB from backup/ restore of $GLOBALS
	 * because resource types cannot be handled during serializing
	 *
	 * @var array
	 */
	protected $backupGlobalsBlacklist = array('TYPO3_DB');

	public function tearDown() {
		t3lib_div::purgeInstances();
	}


	///////////////////////////////
	// Tests concerning gif_compress
	///////////////////////////////

	/**
	 * @test
	 */
	public function gifCompressFixesPermissionOfConvertedFileIfUsingImagemagick() {
		if (TYPO3_OS == 'WIN') {
			$this->markTestSkipped('gifCompressFixesPermissionOfConvertedFileIfUsingImagemagick() test not available on Windows.');
		}

		if (!$GLOBALS['TYPO3_CONF_VARS']['GFX']['im'] || !$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path_lzw']) {
			$this->markTestSkipped('gifCompressFixesPermissionOfConvertedFileIfUsingImagemagick() test not available without imagemagick setup.');
		}

		$testFinder = t3lib_div::makeInstance('Tx_Phpunit_Service_TestFinder');
		$fixtureGifFile = $testFinder->getAbsoluteCoreTestsPath() . 't3lib/fixtures/clear.gif';

		$GLOBALS['TYPO3_CONF_VARS']['GFX']['gif_compress'] = TRUE;

			// Copy file to unique filename in typo3temp, set target permissions and run method
		$testFilename = PATH_site . 'typo3temp/' . uniqid('test_') . '.gif';
		@copy($fixtureGifFile, $testFilename);
		$GLOBALS['TYPO3_CONF_VARS']['BE']['fileCreateMask'] = '0777';
		t3lib_div::gif_compress($testFilename, 'IM');

			// Get actual permissions and clean up
		clearstatcache();
		$resultFilePermissions = substr(decoct(fileperms($testFilename)), 2);
		t3lib_div::unlink_tempfile($testFilename);

		$this->assertEquals($resultFilePermissions, '0777');
	}

	/**
	 * @test
	 */
	public function gifCompressFixesPermissionOfConvertedFileIfUsingGd() {
		if (TYPO3_OS == 'WIN') {
			$this->markTestSkipped('gifCompressFixesPermissionOfConvertedFileIfUsingImagemagick() test not available on Windows.');
		}

		$testFinder = t3lib_div::makeInstance('Tx_Phpunit_Service_TestFinder');
		$fixtureGifFile = $testFinder->getAbsoluteCoreTestsPath() . 't3lib/fixtures/clear.gif';

		$GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib'] = TRUE;
		$GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_png'] = FALSE;

			// Copy file to unique filename in typo3temp, set target permissions and run method
		$testFilename = PATH_site . 'typo3temp/' . uniqid('test_') . '.gif';
		@copy($fixtureGifFile, $testFilename);
		$GLOBALS['TYPO3_CONF_VARS']['BE']['fileCreateMask'] = '0777';
		t3lib_div::gif_compress($testFilename, 'GD');

			// Get actual permissions and clean up
		clearstatcache();
		$resultFilePermissions = substr(decoct(fileperms($testFilename)), 2);
		t3lib_div::unlink_tempfile($testFilename);

		$this->assertEquals($resultFilePermissions, '0777');
	}

	///////////////////////////////
	// Tests concerning png_to_gif_by_imagemagick
	///////////////////////////////

	/**
	 * @test
	 */
	public function pngToGifByImagemagickFixesPermissionsOfConvertedFile() {
		if (TYPO3_OS == 'WIN') {
			$this->markTestSkipped('pngToGifByImagemagickFixesPermissionsOfConvertedFile() test not available on Windows.');
		}

		if (!$GLOBALS['TYPO3_CONF_VARS']['GFX']['im'] || !$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path_lzw']) {
			$this->markTestSkipped('pngToGifByImagemagickFixesPermissionsOfConvertedFile() test not available without imagemagick setup.');
		}

		$testFinder = t3lib_div::makeInstance('Tx_Phpunit_Service_TestFinder');
		$fixturePngFile = $testFinder->getAbsoluteCoreTestsPath() . 't3lib/fixtures/clear.png';

		$GLOBALS['TYPO3_CONF_VARS']['FE']['png_to_gif'] = TRUE;

			// Copy file to unique filename in typo3temp, set target permissions and run method
		$testFilename = PATH_site . 'typo3temp/' . uniqid('test_') . '.png';
		@copy($fixturePngFile, $testFilename);
		$GLOBALS['TYPO3_CONF_VARS']['BE']['fileCreateMask'] = '0777';
		$newGifFile = t3lib_div::png_to_gif_by_imagemagick($testFilename);

			// Get actual permissions and clean up
		clearstatcache();
		$resultFilePermissions = substr(decoct(fileperms($newGifFile)), 2);
		t3lib_div::unlink_tempfile($newGifFile);

		$this->assertEquals($resultFilePermissions, '0777');
	}

	///////////////////////////////
	// Tests concerning read_png_gif
	///////////////////////////////

	/**
	 * @test
	 */
	public function readPngGifFixesPermissionsOfConvertedFile() {
		if (TYPO3_OS == 'WIN') {
			$this->markTestSkipped('readPngGifFixesPermissionsOfConvertedFile() test not available on Windows.');
		}

		if (!$GLOBALS['TYPO3_CONF_VARS']['GFX']['im']) {
			$this->markTestSkipped('readPngGifFixesPermissionsOfConvertedFile() test not available without imagemagick setup.');
		}

		$testFinder = t3lib_div::makeInstance('Tx_Phpunit_Service_TestFinder');
		$testGifFile = $testFinder->getAbsoluteCoreTestsPath() . 't3lib/fixtures/clear.gif';

			// Set target permissions and run method
		$GLOBALS['TYPO3_CONF_VARS']['BE']['fileCreateMask'] = '0777';
		$newPngFile = t3lib_div::read_png_gif($testGifFile, TRUE);

			// Get actual permissions and clean up
		clearstatcache();
		$resultFilePermissions = substr(decoct(fileperms($newPngFile)), 2);
		t3lib_div::unlink_tempfile($newPngFile);

		$this->assertEquals($resultFilePermissions, '0777');
	}

	///////////////////////////////
	// Tests concerning validIP
	///////////////////////////////

	/**
	 * Data provider for checkValidIpReturnsTrueForValidIp
	 *
	 * @return array Data sets
	 */
	public static function validIpDataProvider() {
		return array(
			'0.0.0.0' => array('0.0.0.0'),
			'private IPv4 class C' => array('192.168.0.1'),
			'private IPv4 class A' => array('10.0.13.1'),
			'private IPv6' => array('fe80::daa2:5eff:fe8b:7dfb'),
		);
	}

	/**
	 * @test
	 * @dataProvider validIpDataProvider
	 */
	public function validIpReturnsTrueForValidIp($ip) {
		$this->assertTrue(t3lib_div::validIP($ip));
	}

	/**
	 * Data provider for checkValidIpReturnsFalseForInvalidIp
	 *
	 * @return array Data sets
	 */
	public static function invalidIpDataProvider() {
		return array(
			'null' => array(null),
			'zero' => array(0),
			'string' => array('test'),
			'string empty' => array(''),
			'string null' => array('null'),
			'out of bounds IPv4' => array('300.300.300.300'),
			'dotted decimal notation with only two dots' => array('127.0.1'),
		);
	}

	/**
	 * @test
	 * @dataProvider invalidIpDataProvider
	 */
	public function validIpReturnsFalseForInvalidIp($ip) {
		$this->assertFalse(t3lib_div::validIP($ip));
	}


	///////////////////////////////
	// Tests concerning testInt
	///////////////////////////////

	/**
	 * Data provider for testIntReturnsTrue
	 *
	 * @return array Data sets
	 */
	public function functionTestIntValidDataProvider() {
		return array(
			'int' => array(32425),
			'negative int' => array(-32425),
			'largest int' => array(PHP_INT_MAX),
			'int as string' => array('32425'),
			'negative int as string' => array('-32425'),
			'zero' => array(0),
			'zero as string' => array('0'),
		);
	}

	/**
	 * @test
	 * @dataProvider functionTestIntValidDataProvider
	 */
	public function testIntReturnsTrue($int) {
		$this->assertTrue(t3lib_div::testInt($int));
	}

	/**
	 * Data provider for testIntReturnsFalse
	 *
	 * @return array Data sets
	 */
	public function functionTestIntInvalidDataProvider() {
		return array(
			'int as string with leading zero' => array('01234'),
			'positive int as string with plus modifier' => array('+1234'),
			'negative int as string with leading zero' => array('-01234'),
			'largest int plus one' => array(PHP_INT_MAX + 1),
			'string' => array('testInt'),
			'empty string' => array(''),
			'int in string' => array('5 times of testInt'),
			'int as string with space after' => array('5 '),
			'int as string with space before' => array(' 5'),
			'int as string with many spaces before' => array('     5'),
			'float' => array(3.14159),
			'float as string' => array('3.14159'),
			'float as string only a dot' => array('10.'),
			'float as string trailing zero would evaluate to int 10' => array('10.0'),
			'float as string trailing zeros	 would evaluate to int 10' => array('10.00'),
			'null' => array(NULL),
			'empty array' => array(array()),
			'int in array' => array(array(32425)),
			'int as string in array' => array(array('32425')),
		);
	}

	/**
	 * @test
	 * @dataProvider functionTestIntInvalidDataProvider
	 */
	public function testIntReturnsFalse($int) {
		$this->assertFalse(t3lib_div::testInt($int));
	}


	///////////////////////////////
	// Tests concerning isFirstPartOfStr
	///////////////////////////////

	/**
	 * Data provider for isFirstPartOfStrReturnsTrueForMatchingFirstParts
	 *
	 * @return array
	 */
	public function isFirstPartOfStrReturnsTrueForMatchingFirstPartDataProvider() {
		return array(
			'match first part of string' => array('hello world', 'hello'),
			'match whole string' => array('hello', 'hello'),
			'integer is part of string with same number' => array('24', 24),
			'string is part of integer with same number' => array(24, '24'),
			'integer is part of string starting with same number' => array('24 beer please', 24),
		);
	}

	/**
	 * @test
	 * @dataProvider isFirstPartOfStrReturnsTrueForMatchingFirstPartDataProvider
	 */
	public function isFirstPartOfStrReturnsTrueForMatchingFirstPart($string, $part) {
		$this->assertTrue(t3lib_div::isFirstPartOfStr($string, $part));
	}

	/**
	 * Data provider for checkIsFirstPartOfStrReturnsFalseForNotMatchingFirstParts
	 *
	 * @return array
	 */
	public function isFirstPartOfStrReturnsFalseForNotMatchingFirstPartDataProvider() {
		return array(
			'no string match' => array('hello', 'bye'),
			'no case sensitive string match' => array('hello world', 'Hello'),
			'array is not part of string' => array('string', array()),
			'string is not part of array' => array(array(), 'string'),
			'null is not part of string' => array('string', NULL),
			'string is not part of array' => array(NULL, 'string'),
			'null is not part of array' => array(array(), NULL),
			'array is not part of string' => array(NULL, array()),
			'empty string is not part of empty string' => array('', ''),
			'null is not part of empty string' => array('', NULL),
			'false is not part of empty string' => array('', FALSE),
			'empty string is not part of null' => array(NULL, ''),
			'empty string is not part of false' => array(FALSE, ''),
			'empty string is not part of zero integer' => array(0, ''),
			'zero integer is not part of null' => array(NULL, 0),
			'zero integer is not part of empty string' => array('', 0),
		);
	}

	/**
	 * @test
	 * @dataProvider isFirstPartOfStrReturnsFalseForNotMatchingFirstPartDataProvider
	 */
	public function isFirstPartOfStrReturnsFalseForNotMatchingFirstPart($string, $part) {
		$this->assertFalse(t3lib_div::isFirstPartOfStr($string, $part));
	}


	///////////////////////////////
	// Tests concerning splitCalc
	///////////////////////////////

	/**
	 * Data provider for splitCalc
	 *
	 * @return array expected values, arithmetic expression
	 */
	public function splitCalcDataProvider() {
		return array(
			'empty string returns empty array' => array(
				array(),
				'',
			),
			'number without operator returns array with plus and number' => array(
				array(array('+', 42)),
				'42',
			),
			'two numbers with asterisk return first number with plus and second number with asterisk' => array(
				array(array('+', 42), array('*', 31)),
				'42 * 31',
			),
		);
	}

	/**
	 * @test
	 * @dataProvider splitCalcDataProvider
	 */
	public function splitCalcCorrectlySplitsExpression($expected, $expression) {
		$this->assertEquals($expected, t3lib_div::splitCalc($expression, '+-*/'));
	}


	//////////////////////////////////
	// Tests concerning calcPriority
	//////////////////////////////////

	/**
	 * Data provider for calcPriority
	 *
	 * @return array expected values, arithmetic expression
	 */
	public function calcPriorityDataProvider() {
		return array(
			'add' => array(9, '6 + 3'),
			'substract with positive result' => array(3, '6 - 3'),
			'substract with negative result' => array(-3, '3 - 6'),
			'multiply' => array(6, '2 * 3'),
			'divide' => array(2.5, '5 / 2'),
			'modulus' => array(1, '5 % 2'),
			'power' => array(8, '2 ^ 3'),
			'three operands with non integer result' => array(6.5, '5 + 3 / 2'),
			'three operands with power' => array(14, '5 + 3 ^ 2'),
			'three operads with modulus' => array(4, '5 % 2 + 3'),
			'four operands' => array(3, '2 + 6 / 2 - 2'),
		);
	}

	/**
	 * @test
	 * @dataProvider calcPriorityDataProvider
	 */
	public function calcPriorityCorrectlyCalculatesExpression($expected, $expression) {
		$this->assertEquals($expected, t3lib_div::calcPriority($expression));
	}


	//////////////////////////////////
	// Tests concerning calcPriority
	//////////////////////////////////

	/**
	 * Data provider for valid validEmail's
	 *
	 * @return array Valid email addresses
	 */
	public function validEmailValidDataProvider() {
		return array(
			'short mail address' => array('a@b.c'),
			'simple mail address' => array('test@example.com'),
			'uppercase characters' => array('QWERTYUIOPASDFGHJKLZXCVBNM@QWERTYUIOPASDFGHJKLZXCVBNM.NET'),
				// Fix / change if TYPO3 php requirement changed: Address ok with 5.2.6 and 5.3.2 but fails with 5.3.0 on windows
			// 'equal sign in local part' => array('test=mail@example.com'),
			'dash in local part' => array('test-mail@example.com'),
			'plus in local part' => array('test+mail@example.com'),
				// Fix / change if TYPO3 php requirement changed: Address ok with 5.2.6 and 5.3.2 but fails with 5.3.0 on windows
			// 'question mark in local part' => array('test?mail@example.com'),
			'slash in local part' => array('foo/bar@example.com'),
			'hash in local part' => array('foo#bar@example.com'),
				// Fix / change if TYPO3 php requirement changed: Address ok with 5.2.6 and 5.3.2 but fails with 5.3.0 on windows
			// 'dot in local part' => array('firstname.lastname@employee.2something.com'),
				// Fix / change if TYPO3 php requirement changed: Address ok with 5.2.6, but not ok with 5.3.2
			// 'dash as local part' => array('-@foo.com'),
		);
	}

	/**
	 * @test
	 * @dataProvider validEmailValidDataProvider
	 */
	public function validEmailReturnsTrueForValidMailAddress($address) {
		$this->assertTrue(t3lib_div::validEmail($address));
	}

	/**
	 * Data provider for invalid validEmail's
	 *
	 * @return array Invalid email addresses
	 */
	public function validEmailInvalidDataProvider() {
		return array(
			'@ sign only' => array('@'),
			'duplicate @' => array('test@@example.com'),
			'duplicate @ combined with further special characters in local part' => array('test!.!@#$%^&*@example.com'),
			'opening parenthesis in local part' => array('foo(bar@example.com'),
			'closing parenthesis in local part' => array('foo)bar@example.com'),
			'opening square bracket in local part' => array('foo[bar@example.com'),
			'closing square bracket as local part' => array(']@example.com'),
				// Fix / change if TYPO3 php requirement changed: Address ok with 5.2.6, but not ok with 5.3.2
			// 'top level domain only' => array('test@com'),
			'dash as second level domain' => array('foo@-.com'),
			'domain part starting with dash' => array('foo@-foo.com'),
			'domain part ending with dash' => array('foo@foo-.com'),
			'number as top level domain' => array('foo@bar.123'),
				// Fix / change if TYPO3 php requirement changed: Address not ok with 5.2.6, but ok with 5.3.2 (?)
			// 'dash as top level domain' => array('foo@bar.-'),
			'dot at beginning of domain part' => array('test@.com'),
				// Fix / change if TYPO3 php requirement changed: Address ok with 5.2.6, but not ok with 5.3.2
			// 'local part ends with dot' => array('e.x.a.m.p.l.e.@example.com'),
			'trailing whitespace' => array('test@example.com '),
			'trailing carriage return' => array('test@example.com' . CR),
			'trailing linefeed' => array('test@example.com' . LF),
			'trailing carriage return linefeed' => array('test@example.com' . CRLF),
			'trailing tab' => array('test@example.com' . TAB),
		);
	}

	/**
	 * @test
	 * @dataProvider validEmailInvalidDataProvider
	 */
	public function validEmailReturnsFalseForInvalidMailAddress($address) {
		$this->assertFalse(t3lib_div::validEmail($address));
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
		$actualArray = t3lib_div::intExplode(',', $testString);

		$this->assertEquals($expectedArray, $actualArray);
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
		$actualArray = t3lib_div::revExplode(':', $testString, 2);

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
		$actualArray = t3lib_div::trimExplode(',', $testString);

		$this->assertEquals($expectedArray, $actualArray);
	}

	/**
	 * @test
	 */
	public function checkTrimExplodeRemovesNewLines() {
		$testString = ' a , b , ' . LF . ' ,d ,,  e,f,';
		$expectedArray = array('a', 'b', 'd', 'e', 'f');
		$actualArray = t3lib_div::trimExplode(',', $testString, TRUE);

		$this->assertEquals($expectedArray, $actualArray);
	}

	/**
	 * @test
	 */
	public function checkTrimExplodeRemovesEmptyElements() {
		$testString = 'a , b , c , ,d ,, ,e,f,';
		$expectedArray = array('a', 'b', 'c', 'd', 'e', 'f');
		$actualArray = t3lib_div::trimExplode(',', $testString, TRUE);

		$this->assertEquals($expectedArray, $actualArray);
	}

	/**
	 * @test
	 */
	public function checkTrimExplodeKeepsRemainingResultsWithEmptyItemsAfterReachingLimitWithPositiveParameter() {
		$testString = ' a , b , c , , d,, ,e ';
		$expectedArray = array('a', 'b', 'c,,d,,,e');
			// Limiting returns the rest of the string as the last element
		$actualArray = t3lib_div::trimExplode(',', $testString, FALSE, 3);

		$this->assertEquals($expectedArray, $actualArray);
	}

	/**
	 * @test
	 */
	public function checkTrimExplodeKeepsRemainingResultsWithoutEmptyItemsAfterReachingLimitWithPositiveParameter() {
		$testString = ' a , b , c , , d,, ,e ';
		$expectedArray = array('a', 'b', 'c,d,e');
			// Limiting returns the rest of the string as the last element
		$actualArray = t3lib_div::trimExplode(',', $testString, TRUE, 3);

		$this->assertEquals($expectedArray, $actualArray);
	}

	/**
	 * @test
	 */
	public function checkTrimExplodeKeepsRamainingResultsWithEmptyItemsAfterReachingLimitWithNegativeParameter() {
		$testString = ' a , b , c , d, ,e, f , , ';
		$expectedArray = array('a', 'b', 'c', 'd', '', 'e');
			// limiting returns the rest of the string as the last element
		$actualArray = t3lib_div::trimExplode(',', $testString, FALSE, -3);

		$this->assertEquals($expectedArray, $actualArray);
	}

	/**
	 * @test
	 */
	public function checkTrimExplodeKeepsRamainingResultsWithoutEmptyItemsAfterReachingLimitWithNegativeParameter() {
		$testString = ' a , b , c , d, ,e, f , , ';
		$expectedArray = array('a', 'b', 'c');
			// Limiting returns the rest of the string as the last element
		$actualArray = t3lib_div::trimExplode(',', $testString, TRUE, -3);

		$this->assertEquals($expectedArray, $actualArray);
	}

	/**
	 * @test
	 */
	public function checkTrimExplodeReturnsExactResultsWithoutReachingLimitWithPositiveParameter() {
		$testString = ' a , b , , c , , , ';
		$expectedArray = array('a', 'b', 'c');
			// Limiting returns the rest of the string as the last element
		$actualArray = t3lib_div::trimExplode(',', $testString, TRUE, 4);

		$this->assertEquals($expectedArray, $actualArray);
	}

	/**
	 * @test
	 */
	public function checkTrimExplodeKeepsZeroAsString() {
		$testString = 'a , b , c , ,d ,, ,e,f, 0 ,';
		$expectedArray = array('a', 'b', 'c', 'd', 'e', 'f', '0');
		$actualArray = t3lib_div::trimExplode(',', $testString, TRUE);

		$this->assertEquals($expectedArray, $actualArray);
	}


	//////////////////////////////////
	// Tests concerning removeArrayEntryByValue
	//////////////////////////////////

	/**
	 * @test
	 */
	public function checkRemoveArrayEntryByValueRemovesEntriesFromOneDimensionalArray() {
		$inputArray = array(
			'0' => 'test1',
			'1' => 'test2',
			'2' => 'test3',
			'3' => 'test2',
		);
		$compareValue = 'test2';
		$expectedResult = array(
			'0' => 'test1',
			'2' => 'test3',
		);
		$actualResult = t3lib_div::removeArrayEntryByValue($inputArray, $compareValue);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function checkRemoveArrayEntryByValueRemovesEntriesFromMultiDimensionalArray() {
		$inputArray = array(
			'0' => 'foo',
			'1' => array(
				'10' => 'bar',
			),
			'2' => 'bar',
		);
		$compareValue = 'bar';
		$expectedResult = array(
			'0' => 'foo',
			'1' => array(),
		);
		$actualResult = t3lib_div::removeArrayEntryByValue($inputArray, $compareValue);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function checkRemoveArrayEntryByValueRemovesEntryWithEmptyString() {
		$inputArray = array(
			'0' => 'foo',
			'1' => '',
			'2' => 'bar',
		);
		$compareValue = '';
		$expectedResult = array(
			'0' => 'foo',
			'2' => 'bar',
		);
		$actualResult = t3lib_div::removeArrayEntryByValue($inputArray, $compareValue);
		$this->assertEquals($expectedResult, $actualResult);
	}

	//////////////////////////////////
	// Tests concerning getBytesFromSizeMeasurement
	//////////////////////////////////

	/**
	 * Data provider for getBytesFromSizeMeasurement
	 *
	 * @return array expected value, input string
	 */
	public function getBytesFromSizeMeasurementDataProvider() {
		return array(
			'100 kilo Bytes' => array('102400', '100k'),
			'100 mega Bytes' => array('104857600', '100m'),
			'100 giga Bytes' => array('107374182400', '100g'),
		);
	}

	/**
	 * @test
	 * @dataProvider getBytesFromSizeMeasurementDataProvider
	 */
	public function getBytesFromSizeMeasurementCalculatesCorrectByteValue($expected, $byteString) {
		$this->assertEquals($expected, t3lib_div::getBytesFromSizeMeasurement($byteString));
	}


	//////////////////////////////////
	// Tests concerning getIndpEnv
	//////////////////////////////////

	/**
	 * @test
	 */
	public function getIndpEnvTypo3SitePathReturnNonEmptyString() {
		$this->assertTrue(strlen(t3lib_div::getIndpEnv('TYPO3_SITE_PATH')) >= 1);
	}

	/**
	 * @test
	 */
	public function getIndpEnvTypo3SitePathReturnsStringStartingWithSlash() {
		$result = t3lib_div::getIndpEnv('TYPO3_SITE_PATH');
		$this->assertEquals('/', $result[0]);
	}

	/**
	 * @test
	 */
	public function getIndpEnvTypo3SitePathReturnsStringEndingWithSlash() {
		$result = t3lib_div::getIndpEnv('TYPO3_SITE_PATH');
		$this->assertEquals('/', $result[strlen($result) - 1]);
	}


	//////////////////////////////////
	// Tests concerning underscoredToUpperCamelCase
	//////////////////////////////////

	/**
	 * Data provider for underscoredToUpperCamelCase
	 *
	 * @return array expected, input string
	 */
	public function underscoredToUpperCamelCaseDataProvider() {
		return array(
			'single word' => array('Blogexample', 'blogexample'),
			'multiple words' => array('BlogExample', 'blog_example'),
		);
	}

	/**
	 * @test
	 * @dataProvider underscoredToUpperCamelCaseDataProvider
	 */
	public function underscoredToUpperCamelCase($expected, $inputString) {
		$this->assertEquals($expected, t3lib_div::underscoredToUpperCamelCase($inputString));
	}


	//////////////////////////////////
	// Tests concerning underscoredToLowerCamelCase
	//////////////////////////////////

	/**
	 * Data provider for underscoredToLowerCamelCase
	 *
	 * @return array expected, input string
	 */
	public function underscoredToLowerCamelCaseDataProvider() {
		return array(
			'single word' => array('minimalvalue', 'minimalvalue'),
			'multiple words' => array('minimalValue', 'minimal_value'),
		);
	}

	/**
	 * @test
	 * @dataProvider underscoredToLowerCamelCaseDataProvider
	 */
	public function underscoredToLowerCamelCase($expected, $inputString) {
		$this->assertEquals($expected, t3lib_div::underscoredToLowerCamelCase($inputString));
	}

	//////////////////////////////////
	// Tests concerning camelCaseToLowerCaseUnderscored
	//////////////////////////////////

	/**
	 * Data provider for camelCaseToLowerCaseUnderscored
	 *
	 * @return array expected, input string
	 */
	public function camelCaseToLowerCaseUnderscoredDataProvider() {
		return array(
			'single word' => array('blogexample', 'blogexample'),
			'single word starting upper case' => array('blogexample', 'Blogexample'),
			'two words starting lower case' => array('minimal_value', 'minimalValue'),
			'two words starting upper case' => array('blog_example', 'BlogExample'),
		);
	}

	/**
	 * @test
	 * @dataProvider camelCaseToLowerCaseUnderscoredDataProvider
	 */
	public function camelCaseToLowerCaseUnderscored($expected, $inputString) {
		$this->assertEquals($expected, t3lib_div::camelCaseToLowerCaseUnderscored($inputString));
	}


	//////////////////////////////////
	// Tests concerning lcFirst
	//////////////////////////////////

	/**
	 * Data provider for lcFirst
	 *
	 * @return array expected, input string
	 */
	public function lcfirstDataProvider() {
		return array(
			'single word' => array('blogexample', 'blogexample'),
			'single Word starting upper case' => array('blogexample', 'Blogexample'),
			'two words' => array('blogExample', 'BlogExample'),
		);
	}

	/**
	 * @test
	 * @dataProvider lcfirstDataProvider
	 */
	public function lcFirst($expected, $inputString) {
		$this->assertEquals($expected, t3lib_div::lcfirst($inputString));
	}


	//////////////////////////////////
	// Tests concerning encodeHeader
	//////////////////////////////////

	/**
	 * @test
	 */
	public function encodeHeaderEncodesWhitespacesInQuotedPrintableMailHeader() {
		$this->assertEquals(
			'=?utf-8?Q?We_test_whether_the_copyright_character_=C2=A9_is_encoded_correctly?=',
			t3lib_div::encodeHeader(
				"We test whether the copyright character \xc2\xa9 is encoded correctly",
				'quoted-printable',
				'utf-8'
			)
		);
	}

	/**
	 * @test
	 */
	public function encodeHeaderEncodesQuestionmarksInQuotedPrintableMailHeader() {
		$this->assertEquals(
			'=?utf-8?Q?Is_the_copyright_character_=C2=A9_really_encoded_correctly=3F_Really=3F?=',
			t3lib_div::encodeHeader(
				"Is the copyright character \xc2\xa9 really encoded correctly? Really?",
				'quoted-printable',
				'utf-8'
			)
		);
	}


	//////////////////////////////////
	// Tests concerning isValidUrl
	//////////////////////////////////

	/**
	 * Data provider for valid isValidUrl's
	 *
	 * @return array Valid ressource
	 */
	public function validUrlValidRessourceDataProvider() {
		return array(
			'http' => array('http://www.example.org/'),
			'http without trailing slash' => array('http://qwe'),
			'http directory with trailing slash' => array('http://www.example/img/dir/'),
			'http directory without trailing slash' => array('http://www.example/img/dir'),
			'http index.html' => array('http://example.com/index.html'),
			'http index.php' => array('http://www.example.com/index.php'),
			'http test.png' => array('http://www.example/img/test.png'),
			'http username password querystring and ancher' => array('https://user:pw@www.example.org:80/path?arg=value#fragment'),
			'file' => array('file:///tmp/test.c'),
			'file directory' => array('file://foo/bar'),
			'ftp directory' => array('ftp://ftp.example.com/tmp/'),
			'mailto' => array('mailto:foo@bar.com'),
			'news' => array('news:news.php.net'),
			'telnet'=> array('telnet://192.0.2.16:80/'),
			'ldap' => array('ldap://[2001:db8::7]/c=GB?objectClass?one'),
		);
	}

	/**
	 * @test
	 * @dataProvider validUrlValidRessourceDataProvider
	 */
	public function validURLReturnsTrueForValidRessource($url) {
		$this->assertTrue(t3lib_div::isValidUrl($url));
	}

	/**
	 * Data provider for invalid isValidUrl's
	 *
	 * @return array Invalid ressource
	 */
	public function isValidUrlInvalidRessourceDataProvider() {
		return array(
			'http missing colon' => array('http//www.example/wrong/url/'),
			'http missing slash' => array('http:/www.example'),
			'hostname only' => array('www.example.org/'),
			'file missing protocol specification' => array('/tmp/test.c'),
			'slash only' => array('/'),
			'string http://' => array('http://'),
			'string http:/' => array('http:/'),
			'string http:' => array('http:'),
			'string http' => array('http'),
			'empty string' => array(''),
			'string -1' => array('-1'),
			'string array()' => array('array()'),
			'random string' => array('qwe'),
		);
	}

	/**
	 * @test
	 * @dataProvider isValidUrlInvalidRessourceDataProvider
	 */
	public function validURLReturnsFalseForInvalidRessoure($url) {
		$this->assertFalse(t3lib_div::isValidUrl($url));
	}


	//////////////////////////////////
	// Tests concerning isOnCurrentHost
	//////////////////////////////////

	/**
	 * @test
	 */
	public function isOnCurrentHostReturnsTrueWithCurrentHost() {
		$testUrl = t3lib_div::getIndpEnv('TYPO3_REQUEST_URL');
		$this->assertTrue(t3lib_div::isOnCurrentHost($testUrl));
	}

	/**
	 * Data provider for invalid isOnCurrentHost's
	 *
	 * @return array Invalid Hosts
	 */
	public function checkisOnCurrentHostInvalidHosts() {
		return array(
			'empty string' => array(''),
			'arbitrary string' => array('arbitrary string'),
			'localhost IP' => array('127.0.0.1'),
			'relative path' => array('./relpath/file.txt'),
			'absolute path' => array('/abspath/file.txt?arg=value'),
			'differnt host' => array(t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST') . '.example.org'),
		);
	}


	////////////////////////////////////////
	// Tests concerning sanitizeLocalUrl
	////////////////////////////////////////

	/**
	 * Data provider for valid sanitizeLocalUrl's
	 *
	 * @return array Valid url
	 */
	public function sanitizeLocalUrlValidUrlDataProvider() {
		$subDirectory = t3lib_div::getIndpEnv('TYPO3_SITE_PATH');
		$typo3SiteUrl = t3lib_div::getIndpEnv('TYPO3_SITE_URL');
		$typo3RequestHost = t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST');

		return array(
			'alt_intro.php' => array('alt_intro.php'),
			'alt_intro.php?foo=1&bar=2' => array('alt_intro.php?foo=1&bar=2'),
			$subDirectory . 'typo3/alt_intro.php' => array($subDirectory . 'typo3/alt_intro.php'),
			$subDirectory . 'index.php' => array($subDirectory . 'index.php'),
			'../index.php' => array('../index.php'),
			'../typo3/alt_intro.php' => array('../typo3/alt_intro.php'),
			'../~userDirectory/index.php' => array('../~userDirectory/index.php'),
			'../typo3/mod.php?var1=test-case&var2=~user' => array('../typo3/mod.php?var1=test-case&var2=~user'),
			PATH_site . 'typo3/alt_intro.php' => array(PATH_site . 'typo3/alt_intro.php'),
			$typo3SiteUrl . 'typo3/alt_intro.php' => array($typo3SiteUrl . 'typo3/alt_intro.php'),
			$typo3RequestHost . $subDirectory . '/index.php' => array($typo3RequestHost . $subDirectory . '/index.php'),
		);
	}

	/**
	 * @test
	 * @dataProvider sanitizeLocalUrlValidUrlDataProvider
	 */
	public function sanitizeLocalUrlAcceptsNotEncodedValidUrls($url) {
		$this->assertEquals($url, t3lib_div::sanitizeLocalUrl($url));
	}

	/**
	 * @test
	 * @dataProvider sanitizeLocalUrlValidUrlDataProvider
	 */
	public function sanitizeLocalUrlAcceptsEncodedValidUrls($url) {
		$this->assertEquals(rawurlencode($url), t3lib_div::sanitizeLocalUrl(rawurlencode($url)));
	}

	/**
	 * Data provider for invalid sanitizeLocalUrl's
	 *
	 * @return array Valid url
	 */
	public function sanitizeLocalUrlInvalidDataProvider() {
		return array(
			'empty string' => array(''),
			'http domain' => array('http://www.google.de/'),
			'https domain' => array('https://www.google.de/'),
			'relative path with XSS' => array('../typo3/whatever.php?argument=javascript:alert(0)'),
		);
	}

	/**
	 * @test
	 * @dataProvider sanitizeLocalUrlInvalidDataProvider
	 */
	public function sanitizeLocalUrlDeniesPlainInvalidUrls($url) {
		$this->assertEquals('', t3lib_div::sanitizeLocalUrl($url));
	}

	/**
	 * @test
	 * @dataProvider sanitizeLocalUrlInvalidDataProvider
	 */
	public function sanitizeLocalUrlDeniesEncodedInvalidUrls($url) {
		$this->assertEquals('', t3lib_div::sanitizeLocalUrl(rawurlencode($url)));
	}


	//////////////////////////////////////
	// Tests concerning arrayDiffAssocRecursive
	//////////////////////////////////////

	/**
	 * @test
	 */
	public function arrayDiffAssocRecursiveHandlesOneDimensionalArrays() {
		$array1 = array(
			'key1' => 'value1',
			'key2' => 'value2',
			'key3' => 'value3',
		);
		$array2 = array(
			'key1' => 'value1',
			'key3' => 'value3',
		);
		$expectedResult = array(
			'key2' => 'value2',
		);
		$actualResult = t3lib_div::arrayDiffAssocRecursive($array1, $array2);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function arrayDiffAssocRecursiveHandlesMultiDimensionalArrays() {
		$array1 = array(
			'key1' => 'value1',
			'key2' => array(
				'key21' => 'value21',
				'key22' => 'value22',
				'key23' => array(
					'key231' => 'value231',
					'key232' => 'value232',
				),
			),
		);
		$array2 = array(
			'key1' => 'value1',
			'key2' => array(
				'key21' => 'value21',
				'key23' => array(
					'key231' => 'value231',
				),
			),
		);
		$expectedResult = array(
			'key2' => array(
				'key22' => 'value22',
				'key23' => array(
					'key232' => 'value232',
				),
			),
		);
		$actualResult = t3lib_div::arrayDiffAssocRecursive($array1, $array2);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function arrayDiffAssocRecursiveHandlesMixedArrays() {
		$array1 = array(
			'key1' => array(
				'key11' => 'value11',
				'key12' => 'value12',
			),
			'key2' => 'value2',
			'key3' => 'value3',
		);
		$array2 = array(
			'key1' => 'value1',
			'key2' => array(
				'key21' => 'value21',
			),
		);
		$expectedResult = array(
			'key3' => 'value3',
		);
		$actualResult = t3lib_div::arrayDiffAssocRecursive($array1, $array2);
		$this->assertEquals($expectedResult, $actualResult);
	}


	//////////////////////////////////////
	// Tests concerning removeDotsFromTS
	//////////////////////////////////////

	/**
	 * @test
	 */
	public function removeDotsFromTypoScriptSucceedsWithDottedArray() {
		$typoScript = array(
			'propertyA.' => array(
				'keyA.' => array(
					'valueA' => 1,
				),
				'keyB' => 2,
			),
			'propertyB' => 3,
		);

		$expectedResult = array(
			'propertyA' => array(
				'keyA' => array(
					'valueA' => 1,
				),
				'keyB' => 2,
			),
			'propertyB' => 3,
		);

		$this->assertEquals($expectedResult, t3lib_div::removeDotsFromTS($typoScript));
	}

	/**
	 * @test
	 */
	public function removeDotsFromTypoScriptOverridesSubArray() {
		$typoScript = array(
			'propertyA.' => array(
				'keyA' => 'getsOverridden',
				'keyA.' => array(
					'valueA' => 1,
				),
				'keyB' => 2,
			),
			'propertyB' => 3,
		);

		$expectedResult = array(
			'propertyA' => array(
				'keyA' => array(
					'valueA' => 1,
				),
				'keyB' => 2,
			),
			'propertyB' => 3,
		);

		$this->assertEquals($expectedResult, t3lib_div::removeDotsFromTS($typoScript));
	}

	/**
	 * @test
	 */
	public function removeDotsFromTypoScriptOverridesWithScalar() {
		$typoScript = array(
			'propertyA.' => array(
				'keyA.' => array(
					'valueA' => 1,
				),
				'keyA' => 'willOverride',
				'keyB' => 2,
			),
			'propertyB' => 3,
		);

		$expectedResult = array(
			'propertyA' => array(
				'keyA' => 'willOverride',
				'keyB' => 2,
			),
			'propertyB' => 3,
		);

		$this->assertEquals($expectedResult, t3lib_div::removeDotsFromTS($typoScript));
	}


	//////////////////////////////////////
	// Tests concerning get_dirs
	//////////////////////////////////////

	/**
	 * @test
	 */
	public function getDirsReturnsArrayOfDirectoriesFromGivenDirectory() {
		$path = PATH_t3lib;
		$directories = t3lib_div::get_dirs($path);

		$this->assertType('array', $directories);
	}

	/**
	 * @test
	 */
	public function getDirsReturnsStringErrorOnPathFailure() {
		$path = 'foo';
		$result = t3lib_div::get_dirs($path);
		$expectedResult = 'error';

		$this->assertEquals($expectedResult, $result);
	}


	//////////////////////////////////
	// Tests concerning hmac
	//////////////////////////////////

	/**
	 * @test
	 */
	public function hmacReturnsHashOfProperLength() {
		$hmac = t3lib_div::hmac('message');
		$this->assertTrue(!empty($hmac) && is_string($hmac));
		$this->assertTrue(strlen($hmac) == 40);
	}

	/**
	 * @test
	 */
	public function hmacReturnsEqualHashesForEqualInput() {
		$msg0 = 'message';
		$msg1 = 'message';
		$this->assertEquals(t3lib_div::hmac($msg0), t3lib_div::hmac($msg1));
	}

	/**
	 * @test
	 */
	public function hmacReturnsNoEqualHashesForNonEqualInput() {
		$msg0 = 'message0';
		$msg1 = 'message1';
		$this->assertNotEquals(t3lib_div::hmac($msg0), t3lib_div::hmac($msg1));
	}


	//////////////////////////////////
	// Tests concerning quoteJSvalue
	//////////////////////////////////

	/**
	 * @test
	 */
	public function quoteJSvalueHtmlspecialcharsDataByDefault() {
		$this->assertContains(
			'&gt;',
			t3lib_div::quoteJSvalue('>')
		);
	}

	/**
	 * @test
	 */
	public function quoteJSvaluetHtmlspecialcharsDataWithinCDataSetToFalse() {
		$this->assertContains(
			'&gt;',
			t3lib_div::quoteJSvalue('>', false)
		);
	}

	/**
	 * @test
	 */
	public function quoteJSvaluetNotHtmlspecialcharsDataWithinCDataSetToTrue() {
		$this->assertContains(
			'>',
			t3lib_div::quoteJSvalue('>', true)
		);
	}

	/**
	 * @test
	 */
	public function quoteJSvalueReturnsEmptyStringQuotedInSingleQuotes() {
		$this->assertEquals(
			"''",
			t3lib_div::quoteJSvalue("", true)
		);
	}

	/**
	 * @test
	 */
	public function quoteJSvalueNotModifiesStringWithoutSpecialCharacters() {
		$this->assertEquals(
			"'Hello world!'",
			t3lib_div::quoteJSvalue("Hello world!", true)
		);
	}

	/**
	 * @test
	 */
	public function quoteJSvalueEscapesSingleQuote() {
		$this->assertEquals(
			"'\\''",
			t3lib_div::quoteJSvalue("'", true)
		);
	}

	/**
	 * @test
	 */
	public function quoteJSvalueEscapesDoubleQuoteWithinCDataSetToTrue() {
		$this->assertEquals(
			"'\\\"'",
			t3lib_div::quoteJSvalue('"', true)
		);
	}

	/**
	 * @test
	 */
	public function quoteJSvalueEscapesAndHtmlspecialcharsDoubleQuoteWithinCDataSetToFalse() {
		$this->assertEquals(
			"'\\&quot;'",
			t3lib_div::quoteJSvalue('"', false)
		);
	}

	/**
	 * @test
	 */
	public function quoteJSvalueEscapesTab() {
		$this->assertEquals(
			"'" . '\t' . "'",
			t3lib_div::quoteJSvalue(TAB)
		);
	}

	/**
	 * @test
	 */
	public function quoteJSvalueEscapesLinefeed() {
		$this->assertEquals(
			"'" . '\n' . "'",
			t3lib_div::quoteJSvalue(LF)
		);
	}

	/**
	 * @test
	 */
	public function quoteJSvalueEscapesCarriageReturn() {
		$this->assertEquals(
			"'" . '\r' . "'",
			t3lib_div::quoteJSvalue(CR)
		);
	}

	/**
	 * @test
	 */
	public function quoteJSvalueEscapesBackslah() {
		$this->assertEquals(
			"'\\\\'",
			t3lib_div::quoteJSvalue('\\')
		);
	}

	//////////////////////////////////
	// Tests concerning readLLfile
	//////////////////////////////////

	/**
	 * @test
	 */
	public function readLLfileHandlesLocallangXMLOverride() {
		$unique = uniqid('locallangXMLOverrideTest');

		$xml = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
			<T3locallang>
				<data type="array">
					<languageKey index="default" type="array">
						<label index="buttons.logout">EXIT</label>
					</languageKey>
				</data>
			</T3locallang>';

		$file = PATH_site . 'typo3temp/' . $unique . '.xml';
		t3lib_div::writeFileToTypo3tempDir($file, $xml);

			// Get default value
		$defaultLL = t3lib_div::readLLfile('EXT:lang/locallang_core.xml', 'default');

			// Set override file
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']['EXT:lang/locallang_core.xml'][$unique] = $file;

			// Get override value
		$overrideLL = t3lib_div::readLLfile('EXT:lang/locallang_core.xml', 'default');

			// Clean up again
		unlink($file);

		$this->assertNotEquals($overrideLL['default']['buttons.logout'], '');
		$this->assertNotEquals($defaultLL['default']['buttons.logout'], $overrideLL['default']['buttons.logout']);
		$this->assertEquals($overrideLL['default']['buttons.logout'], 'EXIT');
	}


	///////////////////////////////
	// Tests concerning _GETset()
	///////////////////////////////

	/**
	 * @test
	 */
	public function getSetWritesArrayToGetSystemVariable() {
		$_GET = array();
		$GLOBALS['HTTP_GET_VARS'] = array();

		$getParameters = array('foo' => 'bar');
		t3lib_div::_GETset($getParameters);
		$this->assertSame($getParameters, $_GET);
	}

	/**
	 * @test
	 */
	public function getSetWritesArrayToGlobalsHttpGetVars() {
		$_GET = array();
		$GLOBALS['HTTP_GET_VARS'] = array();

		$getParameters = array('foo' => 'bar');
		t3lib_div::_GETset($getParameters);
		$this->assertSame($getParameters, $GLOBALS['HTTP_GET_VARS']);
	}

	/**
	 * @test
	 */
	public function getSetForArrayDropsExistingValues() {
		$_GET = array();
		$GLOBALS['HTTP_GET_VARS'] = array();

		t3lib_div::_GETset(array('foo' => 'bar'));

		t3lib_div::_GETset(array('oneKey' => 'oneValue'));

		$this->assertEquals(
			array('oneKey' => 'oneValue'),
			$GLOBALS['HTTP_GET_VARS']
		);
	}

	/**
	 * @test
	 */
	public function getSetAssignsOneValueToOneKey() {
		$_GET = array();
		$GLOBALS['HTTP_GET_VARS'] = array();

		t3lib_div::_GETset('oneValue', 'oneKey');

		$this->assertEquals(
			'oneValue',
			$GLOBALS['HTTP_GET_VARS']['oneKey']
		);
	}

	/**
	 * @test
	 */
	public function getSetForOneValueDoesNotDropUnrelatedValues() {
		$_GET = array();
		$GLOBALS['HTTP_GET_VARS'] = array();

		t3lib_div::_GETset(array('foo' => 'bar'));
		t3lib_div::_GETset('oneValue', 'oneKey');

		$this->assertEquals(
			array('foo' => 'bar', 'oneKey' => 'oneValue'),
			$GLOBALS['HTTP_GET_VARS']
		);
	}

	/**
	 * @test
	 */
	public function getSetCanAssignsAnArrayToASpecificArrayElement() {
		$_GET = array();
		$GLOBALS['HTTP_GET_VARS'] = array();

		t3lib_div::_GETset(array('childKey' => 'oneValue'), 'parentKey');

		$this->assertEquals(
			array('parentKey' => array('childKey' => 'oneValue')),
			$GLOBALS['HTTP_GET_VARS']
		);
	}

	/**
	 * @test
	 */
	public function getSetCanAssignAStringValueToASpecificArrayChildElement() {
		$_GET = array();
		$GLOBALS['HTTP_GET_VARS'] = array();

		t3lib_div::_GETset('oneValue', 'parentKey|childKey');

		$this->assertEquals(
			array('parentKey' => array('childKey' => 'oneValue')),
			$GLOBALS['HTTP_GET_VARS']
		);
	}

	/**
	 * @test
	 */
	public function getSetCanAssignAnArrayToASpecificArrayChildElement() {
		$_GET = array();
		$GLOBALS['HTTP_GET_VARS'] = array();

		t3lib_div::_GETset(
			array('key1' => 'value1', 'key2' => 'value2'),
			'parentKey|childKey'
		);

		$this->assertEquals(
			array(
				'parentKey' => array(
					'childKey' => array('key1' => 'value1', 'key2' => 'value2')
				)
			),
			$GLOBALS['HTTP_GET_VARS']
		);
	}


	///////////////////////////////
	// Tests concerning fixPermissions
	///////////////////////////////

	/**
	 * @test
	 */
	public function fixPermissionsCorrectlySetsGroup() {
		if (TYPO3_OS == 'WIN') {
			$this->markTestSkipped('fixPermissionsCorrectlySetsGroupOwnerOfFile() tests not available on Windows');
		}
		if (!function_exists('posix_getegid')) {
			$this->markTestSkipped('Function posix_getegid() not available, fixPermissionsCorrectlySetsGroupOwnerOfFile() tests skipped');
		}

			// Create and prepare test file
		$filename = PATH_site . 'typo3temp/' . uniqid('test_');
		t3lib_div::writeFileToTypo3tempDir($filename, '42');

			// Set target group and run method
		$GLOBALS['TYPO3_CONF_VARS']['BE']['createGroup'] = posix_getegid();
		$fixPermissionsResult = t3lib_div::fixPermissions($filename);

		clearstatcache();
		$resultFileGroup = filegroup($filename);
		unlink($filename);

		$this->assertEquals($resultFileGroup, posix_getegid());
	}

	/**
	 * @test
	 */
	public function fixPermissionsCorrectlySetsPermissionsToFile() {
		if (TYPO3_OS == 'WIN') {
			$this->markTestSkipped('fixPermissions() tests not available on Windows');
		}

			// Create and prepare test file
		$filename = PATH_site . 'typo3temp/' . uniqid('test_');
		t3lib_div::writeFileToTypo3tempDir($filename, '42');
		chmod($filename, 0742);

			// Set target permissions and run method
		$GLOBALS['TYPO3_CONF_VARS']['BE']['fileCreateMask'] = '0660';
		$fixPermissionsResult = t3lib_div::fixPermissions($filename);

			// Get actual permissions and clean up
		clearstatcache();
		$resultFilePermissions = substr(decoct(fileperms($filename)), 2);
		unlink($filename);

			// Test if everything was ok
		$this->assertTrue($fixPermissionsResult);
		$this->assertEquals($resultFilePermissions, '0660');
	}

	/**
	 * @test
	 */
	public function fixPermissionsCorrectlySetsPermissionsToHiddenFile() {
		if (TYPO3_OS == 'WIN') {
			$this->markTestSkipped('fixPermissions() tests not available on Windows');
		}

			// Create and prepare test file
		$filename = PATH_site . 'typo3temp/' . uniqid('.test_');
		t3lib_div::writeFileToTypo3tempDir($filename, '42');
		chmod($filename, 0742);

			// Set target permissions and run method
		$GLOBALS['TYPO3_CONF_VARS']['BE']['fileCreateMask'] = '0660';
		$fixPermissionsResult = t3lib_div::fixPermissions($filename);

			// Get actual permissions and clean up
		clearstatcache();
		$resultFilePermissions = substr(decoct(fileperms($filename)), 2);
		unlink($filename);

			// Test if everything was ok
		$this->assertTrue($fixPermissionsResult);
		$this->assertEquals($resultFilePermissions, '0660');
	}

	/**
	 * @test
	 */
	public function fixPermissionsCorrectlySetsPermissionsToDirectory() {
		if (TYPO3_OS == 'WIN') {
			$this->markTestSkipped('fixPermissions() tests not available on Windows');
		}

			// Create and prepare test directory
		$directory = PATH_site . 'typo3temp/' . uniqid('test_');
		t3lib_div::mkdir($directory);
		chmod($directory, 1551);

			// Set target permissions and run method
		$GLOBALS['TYPO3_CONF_VARS']['BE']['folderCreateMask'] = '0770';
		$fixPermissionsResult = t3lib_div::fixPermissions($directory . '/');

			// Get actual permissions and clean up
		clearstatcache();
		$resultDirectoryPermissions = substr(decoct(fileperms($directory)), 1);
		t3lib_div::rmdir($directory);

			// Test if everything was ok
		$this->assertTrue($fixPermissionsResult);
		$this->assertEquals($resultDirectoryPermissions, '0770');
	}

	/**
	 * @test
	 */
	public function fixPermissionsCorrectlySetsPermissionsToHiddenDirectory() {
		if (TYPO3_OS == 'WIN') {
			$this->markTestSkipped('fixPermissions() tests not available on Windows');
		}

			// Create and prepare test directory
		$directory = PATH_site . 'typo3temp/' . uniqid('.test_');
		t3lib_div::mkdir($directory);
		chmod($directory, 1551);

			// Set target permissions and run method
		$GLOBALS['TYPO3_CONF_VARS']['BE']['folderCreateMask'] = '0770';
		$fixPermissionsResult = t3lib_div::fixPermissions($directory);

			// Get actual permissions and clean up
		clearstatcache();
		$resultDirectoryPermissions = substr(decoct(fileperms($directory)), 1);
		t3lib_div::rmdir($directory);

			// Test if everything was ok
		$this->assertTrue($fixPermissionsResult);
		$this->assertEquals($resultDirectoryPermissions, '0770');
	}

	/**
	 * @test
	 */
	public function fixPermissionsCorrectlySetsPermissionsRecursive() {
		if (TYPO3_OS == 'WIN') {
			$this->markTestSkipped('fixPermissions() tests not available on Windows');
		}

			// Create and prepare test directory and file structure
		$baseDirectory = PATH_site . 'typo3temp/' . uniqid('test_');
		t3lib_div::mkdir($baseDirectory);
		chmod($baseDirectory, 1751);
		t3lib_div::writeFileToTypo3tempDir($baseDirectory . '/file', '42');
		chmod($baseDirectory . '/file', 0742);
		t3lib_div::mkdir($baseDirectory . '/foo');
		chmod($baseDirectory . '/foo', 1751);
		t3lib_div::writeFileToTypo3tempDir($baseDirectory . '/foo/file', '42');
		chmod($baseDirectory . '/foo/file', 0742);
		t3lib_div::mkdir($baseDirectory . '/.bar');
		chmod($baseDirectory . '/.bar', 1751);
			// Use this if writeFileToTypo3tempDir is fixed to create hidden files in subdirectories
		// t3lib_div::writeFileToTypo3tempDir($baseDirectory . '/.bar/.file', '42');
		// t3lib_div::writeFileToTypo3tempDir($baseDirectory . '/.bar/..file2', '42');
		touch($baseDirectory . '/.bar/.file', '42');
		chmod($baseDirectory . '/.bar/.file', 0742);
		touch($baseDirectory . '/.bar/..file2', '42');
		chmod($baseDirectory . '/.bar/..file2', 0742);

			// Set target permissions and run method
		$GLOBALS['TYPO3_CONF_VARS']['BE']['fileCreateMask'] = '0660';
		$GLOBALS['TYPO3_CONF_VARS']['BE']['folderCreateMask'] = '0770';
		$fixPermissionsResult = t3lib_div::fixPermissions($baseDirectory, TRUE);

			// Get actual permissions
		clearstatcache();
		$resultBaseDirectoryPermissions = substr(decoct(fileperms($baseDirectory)), 1);
		$resultBaseFilePermissions = substr(decoct(fileperms($baseDirectory . '/file')), 2);
		$resultFooDirectoryPermissions = substr(decoct(fileperms($baseDirectory . '/foo')), 1);
		$resultFooFilePermissions = substr(decoct(fileperms($baseDirectory . '/foo/file')), 2);
		$resultBarDirectoryPermissions = substr(decoct(fileperms($baseDirectory . '/.bar')), 1);
		$resultBarFilePermissions = substr(decoct(fileperms($baseDirectory . '/.bar/.file')), 2);
		$resultBarFile2Permissions = substr(decoct(fileperms($baseDirectory . '/.bar/..file2')), 2);

			// Clean up
		unlink($baseDirectory . '/file');
		unlink($baseDirectory . '/foo/file');
		unlink($baseDirectory . '/.bar/.file');
		unlink($baseDirectory . '/.bar/..file2');
		t3lib_div::rmdir($baseDirectory . '/foo');
		t3lib_div::rmdir($baseDirectory . '/.bar');
		t3lib_div::rmdir($baseDirectory);

			// Test if everything was ok
		$this->assertTrue($fixPermissionsResult);
		$this->assertEquals($resultBaseDirectoryPermissions, '0770');
		$this->assertEquals($resultBaseFilePermissions, '0660');
		$this->assertEquals($resultFooDirectoryPermissions, '0770');
		$this->assertEquals($resultFooFilePermissions, '0660');
		$this->assertEquals($resultBarDirectoryPermissions, '0770');
		$this->assertEquals($resultBarFilePermissions, '0660');
		$this->assertEquals($resultBarFile2Permissions, '0660');
	}

	/**
	 * @test
	 */
	public function fixPermissionsDoesNotSetPermissionsToNotAllowedPath() {
		if (TYPO3_OS == 'WIN') {
			$this->markTestSkipped('fixPermissions() tests not available on Windows');
		}

			// Create and prepare test file
		$filename = PATH_site . 'typo3temp/../typo3temp/' . uniqid('test_');
		touch($filename);
		chmod($filename, 0742);

			// Set target permissions and run method
		$GLOBALS['TYPO3_CONF_VARS']['BE']['fileCreateMask'] = '0660';
		$fixPermissionsResult = t3lib_div::fixPermissions($filename);

			// Get actual permissions and clean up
		clearstatcache();
		$resultFilePermissions = substr(decoct(fileperms($filename)), 2);
		unlink($filename);

			// Test if everything was ok
		$this->assertFalse($fixPermissionsResult);
	}


	///////////////////////////////
	// Tests concerning mkdir
	///////////////////////////////

	/**
	 * @test
	 */
	public function mkdirCorrectlyCreatesDirectory() {
		$directory = PATH_site . 'typo3temp/' . uniqid('test_');
		$mkdirResult = t3lib_div::mkdir($directory);
		$directoryCreated = is_dir($directory);
		t3lib_div::rmdir($directory);
		$this->assertTrue($mkdirResult);
		$this->assertTrue($directoryCreated);
	}

	/**
	 * @test
	 */
	public function mkdirCorrectlyCreatesHiddenDirectory() {
		$directory = PATH_site . 'typo3temp/' . uniqid('.test_');
		$mkdirResult = t3lib_div::mkdir($directory);
		$directoryCreated = is_dir($directory);
		t3lib_div::rmdir($directory);
		$this->assertTrue($mkdirResult);
		$this->assertTrue($directoryCreated);
	}

	/**
	 * @test
	 */
	public function mkdirCorrectlyCreatesDirectoryWithTrailingSlash() {
		$directory = PATH_site . 'typo3temp/' . uniqid('test_');
		$mkdirResult = t3lib_div::mkdir($directory);
		$directoryCreated = is_dir($directory);
		t3lib_div::rmdir($directory);
		$this->assertTrue($mkdirResult);
		$this->assertTrue($directoryCreated);
	}

	/**
	 * Data provider for ImageMagick shell commands
	 * @see	explodeAndUnquoteImageMagickCommands
	 */
	public function imageMagickCommandsDataProvider() {
		return array(
			// Some theoretical tests first
			array(
				'aa bb "cc" "dd"',
				array('aa', 'bb', '"cc"', '"dd"'),
				array('aa', 'bb', 'cc', 'dd'),
			),
			array(
				'aa bb "cc dd"',
				array('aa', 'bb', '"cc dd"'),
				array('aa', 'bb', 'cc dd'),
			),
			array(
				'\'aa bb\' "cc dd"',
				array('\'aa bb\'', '"cc dd"'),
				array('aa bb', 'cc dd'),
			),
			array(
				'\'aa bb\' cc "dd"',
				array('\'aa bb\'', 'cc', '"dd"'),
				array('aa bb', 'cc', 'dd'),
			),
			// Now test against some real world examples
			array(
				'/opt/local/bin/gm.exe convert +profile \'*\' -geometry 170x136!  -negate "C:/Users/Someuser.Domain/Documents/Htdocs/typo3temp/temp/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif[0]" "C:/Users/Someuser.Domain/Documents/Htdocs/typo3temp/temp/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif"',
				array(
					'/opt/local/bin/gm.exe',
					'convert',
					'+profile',
					'\'*\'',
					'-geometry',
					'170x136!',
					'-negate',
					'"C:/Users/Someuser.Domain/Documents/Htdocs/typo3temp/temp/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif[0]"',
					'"C:/Users/Someuser.Domain/Documents/Htdocs/typo3temp/temp/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif"'
				),
				array(
					'/opt/local/bin/gm.exe',
					'convert',
					'+profile',
					'*',
					'-geometry',
					'170x136!',
					'-negate',
					'C:/Users/Someuser.Domain/Documents/Htdocs/typo3temp/temp/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif[0]',
					'C:/Users/Someuser.Domain/Documents/Htdocs/typo3temp/temp/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif'
				),
			),
			array(
				'C:/opt/local/bin/gm.exe convert +profile \'*\' -geometry 170x136!  -negate "C:/Program Files/Apache2/htdocs/typo3temp/temp/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif[0]" "C:/Program Files/Apache2/htdocs/typo3temp/temp/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif"',
				array(
					'C:/opt/local/bin/gm.exe',
					'convert',
					'+profile',
					'\'*\'',
					'-geometry',
					'170x136!',
					'-negate',
					'"C:/Program Files/Apache2/htdocs/typo3temp/temp/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif[0]"',
					'"C:/Program Files/Apache2/htdocs/typo3temp/temp/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif"'
				),
				array(
					'C:/opt/local/bin/gm.exe',
					'convert',
					'+profile',
					'*',
					'-geometry',
					'170x136!',
					'-negate',
					'C:/Program Files/Apache2/htdocs/typo3temp/temp/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif[0]',
					'C:/Program Files/Apache2/htdocs/typo3temp/temp/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif'
				),
			),
			array(
				'/usr/bin/gm convert +profile \'*\' -geometry 170x136!  -negate "/Shared Items/Data/Projects/typo3temp/temp/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif[0]" "/Shared Items/Data/Projects/typo3temp/temp/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif"',
				array(
					'/usr/bin/gm',
					'convert',
					'+profile',
					'\'*\'',
					'-geometry',
					'170x136!',
					'-negate',
					'"/Shared Items/Data/Projects/typo3temp/temp/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif[0]"',
					'"/Shared Items/Data/Projects/typo3temp/temp/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif"'
				),
				array(
					'/usr/bin/gm',
					'convert',
					'+profile',
					'*',
					'-geometry',
					'170x136!',
					'-negate',
					'/Shared Items/Data/Projects/typo3temp/temp/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif[0]',
					'/Shared Items/Data/Projects/typo3temp/temp/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif'
				),
			),
			array(
				'/usr/bin/gm convert +profile \'*\' -geometry 170x136!  -negate "/Network/Servers/server01.internal/Projects/typo3temp/temp/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif[0]" "/Network/Servers/server01.internal/Projects/typo3temp/temp/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif"',
				array(
					'/usr/bin/gm',
					'convert',
					'+profile',
					'\'*\'',
					'-geometry',
					'170x136!',
					'-negate',
					'"/Network/Servers/server01.internal/Projects/typo3temp/temp/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif[0]"',
					'"/Network/Servers/server01.internal/Projects/typo3temp/temp/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif"'
				),
				array(
					'/usr/bin/gm',
					'convert',
					'+profile',
					'*',
					'-geometry',
					'170x136!',
					'-negate',
					'/Network/Servers/server01.internal/Projects/typo3temp/temp/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif[0]',
					'/Network/Servers/server01.internal/Projects/typo3temp/temp/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif'
				),
			),
			array(
				'/usr/bin/gm convert +profile \'*\' -geometry 170x136!  -negate \'/Network/Servers/server01.internal/Projects/typo3temp/temp/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif[0]\' \'/Network/Servers/server01.internal/Projects/typo3temp/temp/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif\'',
				array(
					'/usr/bin/gm',
					'convert',
					'+profile',
					'\'*\'',
					'-geometry',
					'170x136!',
					'-negate',
					'\'/Network/Servers/server01.internal/Projects/typo3temp/temp/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif[0]\'',
					'\'/Network/Servers/server01.internal/Projects/typo3temp/temp/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif\''
				),
				array(
					'/usr/bin/gm',
					'convert',
					'+profile',
					'*',
					'-geometry',
					'170x136!',
					'-negate',
					'/Network/Servers/server01.internal/Projects/typo3temp/temp/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif[0]',
					'/Network/Servers/server01.internal/Projects/typo3temp/temp/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif'
				),
			),
		);
	}

	/**
	 * Tests if the commands are exploded and unquoted correctly
	 *
	 * @dataProvider	imageMagickCommandsDataProvider
	 * @test
	 */
	public function explodeAndUnquoteImageMagickCommands($source, $expectedQuoted, $expectedUnquoted) {
		$actualQuoted 	= t3lib_div::unQuoteFilenames($source);
		$acutalUnquoted = t3lib_div::unQuoteFilenames($source, TRUE);

		$this->assertEquals($expectedQuoted, $actualQuoted, 'The exploded command does not match the expected');
		$this->assertEquals($expectedUnquoted, $acutalUnquoted, 'The exploded and unquoted command does not match the expected');
	}


	///////////////////////////////
	// Tests concerning split_fileref
	///////////////////////////////

	/**
	 * @test
	 */
	public function splitFileRefReturnsFileTypeNotForFolders(){
		$directoryName = uniqid('test_') . '.com';
		$directoryPath = PATH_site . 'typo3temp/';
		$directory = $directoryPath . $directoryName;
		mkdir($directory, octdec($GLOBALS['TYPO3_CONF_VARS']['BE']['folderCreateMask']));

		$fileInfo = t3lib_div::split_fileref($directory);

		$directoryCreated = is_dir($directory);
		rmdir($directory);

		$this->assertTrue($directoryCreated);
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $fileInfo);
		$this->assertEquals($directoryPath, $fileInfo['path']);
		$this->assertEquals($directoryName, $fileInfo['file']);
		$this->assertEquals($directoryName, $fileInfo['filebody']);
		$this->assertEquals('', $fileInfo['fileext']);
		$this->assertArrayNotHasKey('realFileext', $fileInfo);
	}

	/**
	 * @test
	 */
	public function splitFileRefReturnsFileTypeForFilesWithoutPathSite() {
		$testFile = 'fileadmin/media/someFile.png';

		$fileInfo = t3lib_div::split_fileref($testFile);
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $fileInfo);
		$this->assertEquals('fileadmin/media/', $fileInfo['path']);
		$this->assertEquals('someFile.png', $fileInfo['file']);
		$this->assertEquals('someFile', $fileInfo['filebody']);
		$this->assertEquals('png', $fileInfo['fileext']);
	}


	/////////////////////////////
	// Tests concerning dirname
	/////////////////////////////

	/**
	 * @see dirnameWithDataProvider
	 *
	 * @return array<array>
	 */
	public function dirnameDataProvider() {
		return array(
			'absolute path with multiple part and file' => array('/dir1/dir2/script.php', '/dir1/dir2'),
			'absolute path with one part' => array('/dir1/', '/dir1'),
			'absolute path to file without extension' => array('/dir1/something', '/dir1'),
			'relative path with one part and file' => array('dir1/script.php', 'dir1'),
			'relative one-character path with one part and file' => array('d/script.php', 'd'),
			'absolute zero-part path with file' => array('/script.php', ''),
			'empty string' => array('', ''),
		);
	}

	/**
	 * @test
	 *
	 * @dataProvider dirnameDataProvider
	 *
	 * @param string $input the input for dirname
	 * @param string $expectedValue the expected return value expected from dirname
	 */
	public function dirnameWithDataProvider($input, $expectedValue) {
		$this->assertEquals(
			$expectedValue,
			t3lib_div::dirname($input)
		);
	}


	/////////////////////////////////////////////////////////////////////////////////////
	// Tests concerning makeInstance, setSingletonInstance, addInstance, purgeInstances
	/////////////////////////////////////////////////////////////////////////////////////

	/**
	 * @test
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function makeInstanceWithEmptyClassNameThrowsException() {
		t3lib_div::makeInstance('');
	}

	/**
	 * @test
	 */
	public function makeInstanceReturnsClassInstance() {
		$className = get_class($this->getMock('foo'));

		$this->assertTrue(
			t3lib_div::makeInstance($className) instanceof $className
		);
	}

	/**
	 * @test
	 */
	public function makeInstancePassesParametersToConstructor() {
		$className = 'testingClass' . uniqid();
		if (!class_exists($className, FALSE)) {
			eval(
				'class ' . $className . ' {' .
				'  public $constructorParameter1;' .
				'  public $constructorParameter2;' .
				'  public function __construct($parameter1, $parameter2) {' .
				'    $this->constructorParameter1 = $parameter1;' .
				'    $this->constructorParameter2 = $parameter2;' .
				'  }' .
				'}'
			);
		}

		$instance = t3lib_div::makeInstance($className, 'one parameter', 'another parameter');

		$this->assertEquals(
			'one parameter',
			$instance->constructorParameter1,
			'The first constructor parameter has not been set.'
		);
		$this->assertEquals(
			'another parameter',
			$instance->constructorParameter2,
			'The second constructor parameter has not been set.'
		);
	}

	/**
	 * @test
	 */
	public function makeInstanceCalledTwoTimesForNonSingletonClassReturnsDifferentInstances() {
		$className = get_class($this->getMock('foo'));

		$this->assertNotSame(
			t3lib_div::makeInstance($className),
			t3lib_div::makeInstance($className)
		);
	}

	/**
	 * @test
	 */
	public function makeInstanceCalledTwoTimesForSingletonClassReturnsSameInstance() {
		$className = get_class($this->getMock('t3lib_Singleton'));

		$this->assertSame(
			t3lib_div::makeInstance($className),
			t3lib_div::makeInstance($className)
		);
	}

	/**
	 * @test
	 */
	public function makeInstanceCalledTwoTimesForSingletonClassWithPurgeInstancesInbetweenReturnsDifferentInstances() {
		$className = get_class($this->getMock('t3lib_Singleton'));

		$instance = t3lib_div::makeInstance($className);
		t3lib_div::purgeInstances();

		$this->assertNotSame(
			$instance,
			t3lib_div::makeInstance($className)
		);
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function setSingletonInstanceForEmptyClassNameThrowsException() {
		$instance = $this->getMock('t3lib_Singleton');

		t3lib_div::setSingletonInstance('', $instance);
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function setSingletonInstanceForClassThatIsNoSubclassOfProvidedClassThrowsException() {
		$instance = $this->getMock('t3lib_Singleton', array('foo'));
		$singletonClassName = get_class($this->getMock('t3lib_Singleton'));

		t3lib_div::setSingletonInstance($singletonClassName, $instance);
	}

	/**
	 * @test
	 */
	public function setSingletonInstanceMakesMakeInstanceReturnThatInstance() {
		$instance = $this->getMock('t3lib_Singleton');
		$singletonClassName = get_class($instance);

		t3lib_div::setSingletonInstance($singletonClassName, $instance);

		$this->assertSame(
			$instance,
			t3lib_div::makeInstance($singletonClassName)
		);
	}

	/**
	 * @test
	 */
	public function setSingletonInstanceCalledTwoTimesMakesMakeInstanceReturnLastSetInstance() {
		$instance1 = $this->getMock('t3lib_Singleton');
		$singletonClassName = get_class($instance1);
		$instance2 = new $singletonClassName();

		t3lib_div::setSingletonInstance($singletonClassName, $instance1);
		t3lib_div::setSingletonInstance($singletonClassName, $instance2);

		$this->assertSame(
			$instance2,
			t3lib_div::makeInstance($singletonClassName)
		);
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function addInstanceForEmptyClassNameThrowsException() {
		$instance = $this->getMock('foo');

		t3lib_div::addInstance('', $instance);
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function addInstanceForClassThatIsNoSubclassOfProvidedClassThrowsException() {
		$instance = $this->getMock('foo', array('bar'));
		$singletonClassName = get_class($this->getMock('foo'));

		t3lib_div::addInstance($singletonClassName, $instance);
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function addInstanceWithSingletonInstanceThrowsException() {
		$instance = $this->getMock('t3lib_Singleton');

		t3lib_div::addInstance(get_class($instance), $instance);
	}

	/**
	 * @test
	 */
	public function addInstanceMakesMakeInstanceReturnThatInstance() {
		$instance = $this->getMock('foo');
		$className = get_class($instance);

		t3lib_div::addInstance($className, $instance);

		$this->assertSame(
			$instance,
			t3lib_div::makeInstance($className)
		);
	}

	/**
	 * @test
	 */
	public function makeInstanceCalledTwoTimesAfterAddInstanceReturnTwoDifferentInstances() {
		$instance = $this->getMock('foo');
		$className = get_class($instance);

		t3lib_div::addInstance($className, $instance);

		$this->assertNotSame(
			t3lib_div::makeInstance($className),
			t3lib_div::makeInstance($className)
		);
	}

	/**
	 * @test
	 */
	public function addInstanceCalledTwoTimesMakesMakeInstanceReturnBothInstancesInAddingOrder() {
		$instance1 = $this->getMock('foo');
		$className = get_class($instance1);
		t3lib_div::addInstance($className, $instance1);

		$instance2 = new $className();
		t3lib_div::addInstance($className, $instance2);

		$this->assertSame(
			$instance1,
			t3lib_div::makeInstance($className),
			'The first returned instance does not match the first added instance.'
		);
		$this->assertSame(
			$instance2,
			t3lib_div::makeInstance($className),
			'The second returned instance does not match the second added instance.'
		);
	}

	/**
	 * @test
	 */
	public function purgeInstancesDropsAddedInstance() {
		$instance = $this->getMock('foo');
		$className = get_class($instance);

		t3lib_div::addInstance($className, $instance);
		t3lib_div::purgeInstances();

		$this->assertNotSame(
			$instance,
			t3lib_div::makeInstance($className)
		);
	}

	/**
	 * Data provider for validPathStrDetectsInvalidCharacters.
	 *
	 * @return array
	 */
	public function validPathStrInvalidCharactersDataProvider() {
		return array(
			'double slash in path' => array('path//path'),
			'backslash in path' => array('path\\path'),
			'directory up in path' => array('path/../path'),
			'directory up at the beginning' => array('../path'),
			'NUL character in path' => array("path\x00path"),
			'BS character in path' => array("path\x08path"),
		);
	}

	/**
	 * Tests whether invalid characters are detected.
	 *
	 * @param string $path
	 * @dataProvider validPathStrInvalidCharactersDataProvider
	 * @test
	 */
	public function validPathStrDetectsInvalidCharacters($path) {
		$this->assertNull(t3lib_div::validPathStr($path));
	}

	/**
	 * Tests whether verifyFilenameAgainstDenyPattern detects the null character.
	 *
	 * @test
	 */
	public function verifyFilenameAgainstDenyPatternDetectsNullCharacter() {
		$this->assertFalse(t3lib_div::verifyFilenameAgainstDenyPattern("image\x00.gif"));
	}


	/////////////////////////////////////////////////////////////////////////////////////
	// Tests concerning sysLog
	/////////////////////////////////////////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function syslogFixesPermissionsOnFileIfUsingFileLogging() {
		if (TYPO3_OS == 'WIN') {
			$this->markTestSkipped('syslogFixesPermissionsOnFileIfUsingFileLogging() test not available on Windows.');
		}

			// Fake all required settings
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLogLevel'] = 0;
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['systemLogInit'] = TRUE;
		unset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['systemLog']);
		$testLogFilename = PATH_site . 'typo3temp/' . uniqid('test_') . '.txt';
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLog'] = 'file,' . $testLogFilename . ',0';
		$GLOBALS['TYPO3_CONF_VARS']['BE']['fileCreateMask'] = '0777';

			// Call method, get actual permissions and clean up
		t3lib_div::syslog('testLog', 'test', 1);
		clearstatcache();
		$resultFilePermissions = substr(decoct(fileperms($testLogFilename)), 2);
		t3lib_div::unlink_tempfile($testLogFilename);

		$this->assertEquals($resultFilePermissions, '0777');
	}

	/**
	 * @test
	 */
	public function deprecationLogFixesPermissionsOnLogFile() {
		if (TYPO3_OS == 'WIN') {
			$this->markTestSkipped('deprecationLogFixesPermissionsOnLogFile() test not available on Windows.');
		}

			// Fake all required settings and get an unique logfilename
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = uniqid('test_');
		$deprecationLogFilename = t3lib_div::getDeprecationLogFileName();
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['enableDeprecationLog'] = TRUE;
		$GLOBALS['TYPO3_CONF_VARS']['BE']['fileCreateMask'] = '0777';

			// Call method, get actual permissions and clean up
		t3lib_div::deprecationLog('foo');
		clearstatcache();
		$resultFilePermissions = substr(decoct(fileperms($deprecationLogFilename)), 2);
		@unlink($deprecationLogFilename);

		$this->assertEquals($resultFilePermissions, '0777');
	}
}
?>
