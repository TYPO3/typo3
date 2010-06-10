<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Ingo Renner <ingo@typo3.org>
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
	 * backup of the global variables _GET, _POST, _SERVER
	 *
	 * @var array
	 */
	private $backupGlobalVariables;

	public function setUp() {
		$this->backupGlobalVariables = array(
			'_GET' => $_GET,
			'_POST' => $_POST,
			'_SERVER' => $_SERVER,
			'TYPO3_CONF_VARS' =>  $GLOBALS['TYPO3_CONF_VARS'],
		);
	}

	public function tearDown() {
		foreach ($this->backupGlobalVariables as $key => $data) {
			$GLOBALS[$key] = $data;
		}
	}


	/**
	 * @test
	 */
	public function calcPriorityCalculatesBasicArithmeticOperation() {
		$this->assertEquals(9, t3lib_div::calcPriority('6 + 3'));
		$this->assertEquals(3, t3lib_div::calcPriority('6 - 3'));
		$this->assertEquals(-3, t3lib_div::calcPriority('3 - 6'));
		$this->assertEquals(6, t3lib_div::calcPriority('2 * 3'));
		$this->assertEquals(2.5, t3lib_div::calcPriority('5 / 2'));
		$this->assertEquals(1, t3lib_div::calcPriority('5 % 2'));
		$this->assertEquals(8, t3lib_div::calcPriority('2 ^ 3'));
	}

	/**
	 * @test
	 */
	public function calcPriorityCalculatesArithmeticOperationWithMultipleOperands() {
		$this->assertEquals(6.5, t3lib_div::calcPriority('5 + 3 / 2'));
		$this->assertEquals(14, t3lib_div::calcPriority('5 + 3 ^ 2'));
		$this->assertEquals(4, t3lib_div::calcPriority('5 % 2 + 3'));
		$this->assertEquals(3, t3lib_div::calcPriority('2 + 6 / 2 - 2'));
	}

	/**
	 * @test
	 */
	public function checkIntExplodeConvertsStringsToInteger() {
		$testString = '1,foo,2';
		$expectedArray = array(1, 0, 2);
		$actualArray = t3lib_div::intExplode(',', $testString);

		$this->assertEquals($expectedArray, $actualArray);
	}

	/**
	 * @test
	 */
	public function checkRevExplodeCorrectlyExplodesString() {
		$testString = 'my:words:here';
		$expectedArray = array('my:words', 'here');
		$actualArray = t3lib_div::revExplode(':', $testString, 2);

		$this->assertEquals($expectedArray, $actualArray);
	}

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
		$actualArray = t3lib_div::trimExplode(',', $testString, true);

		$this->assertEquals($expectedArray, $actualArray);
	}

	/**
	 * @test
	 */
	public function checkTrimExplodeRemovesEmptyElements() {
		$testString = 'a , b , c , ,d ,, ,e,f,';
		$expectedArray = array('a', 'b', 'c', 'd', 'e', 'f');
		$actualArray = t3lib_div::trimExplode(',', $testString, true);

		$this->assertEquals($expectedArray, $actualArray);
	}

	/**
	 * @test
	 */
	public function checkTrimExplodeKeepsRemainingResultsWithEmptyItemsAfterReachingLimitWithPositiveParameter() {
		$testString = ' a , b , c , , d,, ,e ';
		$expectedArray = array('a', 'b', 'c,,d,,,e'); // limiting returns the rest of the string as the last element
		$actualArray = t3lib_div::trimExplode(',', $testString, false, 3);

		$this->assertEquals($expectedArray, $actualArray);
	}

	/**
	 * @test
	 */
	public function checkTrimExplodeKeepsRemainingResultsWithoutEmptyItemsAfterReachingLimitWithPositiveParameter() {
		$testString = ' a , b , c , , d,, ,e ';
		$expectedArray = array('a', 'b', 'c,d,e'); // limiting returns the rest of the string as the last element
		$actualArray = t3lib_div::trimExplode(',', $testString, true, 3);

		$this->assertEquals($expectedArray, $actualArray);
	}

	/**
	 * @test
	 */
	public function checkTrimExplodeKeepsRamainingResultsWithEmptyItemsAfterReachingLimitWithNegativeParameter() {
		$testString = ' a , b , c , d, ,e, f , , ';
		$expectedArray = array('a', 'b', 'c', 'd', '', 'e'); // limiting returns the rest of the string as the last element
		$actualArray = t3lib_div::trimExplode(',', $testString, false, -3);

		$this->assertEquals($expectedArray, $actualArray);
	}

	/**
	 * @test
	 */
	public function checkTrimExplodeKeepsRamainingResultsWithoutEmptyItemsAfterReachingLimitWithNegativeParameter() {
		$testString = ' a , b , c , d, ,e, f , , ';
		$expectedArray = array('a', 'b', 'c'); // limiting returns the rest of the string as the last element
		$actualArray = t3lib_div::trimExplode(',', $testString, true, -3);

		$this->assertEquals($expectedArray, $actualArray);
	}

	/**
	 * @test
	 */
	public function checkTrimExplodeReturnsExactResultsWithoutReachingLimitWithPositiveParameter() {
		$testString = ' a , b , , c , , , ';
		$expectedArray = array('a', 'b', 'c'); // limiting returns the rest of the string as the last element
		$actualArray = t3lib_div::trimExplode(',', $testString, true, 4);

		$this->assertEquals($expectedArray, $actualArray);
	}

	/**
	 * @test
	 */
	public function checkTrimExplodeKeepsZeroAsString() {
		$testString = 'a , b , c , ,d ,, ,e,f, 0 ,';
		$expectedArray = array('a', 'b', 'c', 'd', 'e', 'f', '0');
		$actualArray = t3lib_div::trimExplode(',', $testString, true);

		$this->assertEquals($expectedArray, $actualArray);
	}

	/**
	 * Checks whether measurement strings like "100k" return the accordant
	 * byte representation like 102400 in this case.
	 *
	 * @test
	 */
	public function checkGetBytesFromSizeMeasurement() {
		$this->assertEquals(
			'102400',
			t3lib_div::getBytesFromSizeMeasurement('100k')
		);

		$this->assertEquals(
			'104857600',
			t3lib_div::getBytesFromSizeMeasurement('100m')
		);

		$this->assertEquals(
			'107374182400',
			t3lib_div::getBytesFromSizeMeasurement('100g')
		);
	}

	/**
	 * @test
	 */
	public function checkIndpEnvTypo3SitePathNotEmpty() {
		$actualEnv = t3lib_div::getIndpEnv('TYPO3_SITE_PATH');
		$this->assertTrue(strlen($actualEnv) >= 1);
		$this->assertEquals('/', $actualEnv{0});
		$this->assertEquals('/', $actualEnv{strlen($actualEnv) - 1});
	}

	/**
	 * @test
	 * @see t3lib_div::underscoredToUpperCamelCase
	 */
	public function canConvertFromUnderscoredToUpperCamelCase() {
		$this->assertEquals('BlogExample', t3lib_div::underscoredToUpperCamelCase('blog_example'));
		$this->assertEquals('Blogexample', t3lib_div::underscoredToUpperCamelCase('blogexample'));
	}

	/**
	 * @test
	 * @see t3lib_div::underscoredToLowerCamelCase
	 */
	public function canConvertFromUnderscoredToLowerCamelCase() {
		$this->assertEquals('minimalValue', t3lib_div::underscoredToLowerCamelCase('minimal_value'));
		$this->assertEquals('minimalvalue', t3lib_div::underscoredToLowerCamelCase('minimalvalue'));
	}

	/**
	 * @test
	 * @see t3lib_div::camelCaseToLowerCaseUnderscored
	 */
	public function canConvertFromCamelCaseToLowerCaseUnderscored() {
		$this->assertEquals('blog_example', t3lib_div::camelCaseToLowerCaseUnderscored('BlogExample'));
		$this->assertEquals('blogexample', t3lib_div::camelCaseToLowerCaseUnderscored('Blogexample'));
		$this->assertEquals('blogexample', t3lib_div::camelCaseToLowerCaseUnderscored('blogexample'));

		$this->assertEquals('minimal_value', t3lib_div::camelCaseToLowerCaseUnderscored('minimalValue'));
	}

	/**
	 * @test
	 * @see t3lib_div::lcfirst
	 */
	public function canConvertFirstCharacterToBeLowerCase() {
		$this->assertEquals('blogexample', t3lib_div::lcfirst('Blogexample'));
		$this->assertEquals('blogExample', t3lib_div::lcfirst('BlogExample'));
		$this->assertEquals('blogexample', t3lib_div::lcfirst('blogexample'));
	}

	/**
	 * Tests whether whitespaces are encoded correctly in a quoted-printable mail header.
	 * @test
	 */
	public function areWhitespacesEncodedInQuotedPrintableMailHeader() {
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
	 * Tests whether question marks are encoded correctly in a quoted-printable mail header.
	 * @test
	 */
	public function areQuestionMarksEncodedInQuotedPrintableMailHeader() {
		$this->assertEquals(
			'=?utf-8?Q?Is_the_copyright_character_=C2=A9_really_encoded_correctly=3F_Really=3F?=',
			t3lib_div::encodeHeader(
				"Is the copyright character \xc2\xa9 really encoded correctly? Really?",
				'quoted-printable',
				'utf-8'
			)
		);
	}

	/**
	 * Data provider for valid URLs, like PHP's source code test cases
	 */
	public function validUrlDataProvider() {
		return array(
			array('http://example.com/index.html'),
			array('http://www.example.com/index.php'),
			array('http://www.example/img/test.png'),
			array('http://www.example/img/dir/'),
			array('http://www.example/img/dir'),
			array('file:///tmp/test.c'),
			array('ftp://ftp.example.com/tmp/'),
			array('mailto:foo@bar.com'),
			array('news:news.php.net'),
			array('file://foo/bar'),
			array('http://qwe'),
		);
	}

	/**
	 * Data provider for invalid URLs, like PHP's source code test cases
	 */
	public function invalidUrlDataProvider() {
		return array(
			array('http//www.example/wrong/url/'),
			array('http:/www.example'),
			array('/tmp/test.c'),
			array('/'),
			array('http://'),
			array('http:/'),
			array('http:'),
			array('http'),
			array(''),
			array('-1'),
			array('array()'),
			array('qwe'),
		);
	}

	/**
	 * @test
	 * @dataProvider validUrlDataProvider
	 * @see	t3lib_div::isValidUrl()
	 */
	public function checkisValidURL($url) {
		$this->assertTrue(t3lib_div::isValidUrl($url));
	}

	/**
	 * @test
	 * @dataProvider invalidUrlDataProvider
	 * @see	t3lib_div::isValidUrl()
	 */
	public function checkisInValidURL($url) {
		$this->assertFalse(t3lib_div::isValidUrl($url));
	}

	/**
	 * @test
	 * @see t3lib_div::isValidUrl()
	 */
	public function checkisValidURLSucceedsWithWebRessource() {
		$testUrl = 'http://www.example.org/';
		$this->assertTrue(t3lib_div::isValidUrl($testUrl));
	}

	/**
	 * @test
	 * @see t3lib_div::isValidUrl()
	 */
	public function checkisValidURLSucceedsWithExtentedWebRessource() {
		$testUrl = 'https://user:pw@www.example.org:80/path?arg=value#fragment';
		$this->assertTrue(t3lib_div::isValidUrl($testUrl));
	}

	/**
	 * @test
	 * @see t3lib_div::isValidUrl()
	 */
	public function checkisValidURLSucceedsWithTelnetRessource() {
		$testUrl = 'telnet://192.0.2.16:80/';
		$this->assertTrue(t3lib_div::isValidUrl($testUrl));
	}

	/**
	 * @test
	 */
	public function checkisValidURLSucceedsWithLdapRessource() {
		$testUrl = 'ldap://[2001:db8::7]/c=GB?objectClass?one';
		$this->assertTrue(t3lib_div::isValidUrl($testUrl));
	}

	/**
	 * @test
	 * @see t3lib_div::isValidUrl()
	 */
	public function checkisValidURLSucceedsWithFileRessource() {
		$testUrl = 'file:///etc/passwd';
		$this->assertTrue(t3lib_div::isValidUrl($testUrl));
	}

	/**
	 * @test
	 * @see t3lib_div::isValidUrl()
	 */
	public function checkisValidURLFailsWithHostnameOnly() {
		$testUrl = 'www.example.org/';
		$this->assertFalse(t3lib_div::isValidUrl($testUrl));
	}

	/**
	 * @test
	 * @see t3lib_div::isOnCurrentHost()
	 */
	public function checkisOnCurrentHostFailsWithLocalhostIPOnly() {
		$testUrl = '127.0.0.1';
		$this->assertFalse(t3lib_div::isOnCurrentHost($testUrl));
	}

	/**
	 * @test
	 * @see t3lib_div::isOnCurrentHost()
	 */
	public function checkisOnCurrentHostFailsWithPathsOnly() {
		$testUrl = './relpath/file.txt';
		$this->assertFalse(t3lib_div::isOnCurrentHost($testUrl));
		$testUrl = '/abspath/file.txt?arg=value';
		$this->assertFalse(t3lib_div::isOnCurrentHost($testUrl));
	}

	/**
	 * @test
	 * @see t3lib_div::isOnCurrentHost()
	 */
	public function checkisOnCurrentHostFailsWithArbitraryString() {
		$testUrl = 'arbitrary string';
		$this->assertFalse(t3lib_div::isOnCurrentHost($testUrl));
	}

	/**
	 * @test
	 * @see t3lib_div::isOnCurrentHost()
	 */
	public function checkisOnCurrentHostFailsWithEmptyUrl() {
		$testUrl = '';
		$this->assertFalse(t3lib_div::isOnCurrentHost($testUrl));
	}

	/**
	 * @test
	 * @see t3lib_div::isOnCurrentHost()
	 */
	public function checkisOnCurrentHostFailsWithDifferentHost() {
		$testUrl = t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST') . '.example.org';
		$this->assertFalse(t3lib_div::isOnCurrentHost($testUrl));
	}

	/**
	 * @test
	 * @see t3lib_div::isOnCurrentHost()
	 */
	public function checkisOnCurrentHostSucceedsWithCurrentHost() {
		$testUrl = t3lib_div::getIndpEnv('TYPO3_REQUEST_URL');
		$this->assertTrue(t3lib_div::isOnCurrentHost($testUrl));
	}


	////////////////////////////////////////
	// Tests concerning sanitizeLocalUrl
	////////////////////////////////////////

	/**
	 * Data provider for valid URLs.
	 * @see	sanitizeLocalUrlAcceptsValidUrls
	 */
	public function validLocalUrlDataProvider() {
		return array(
			array('alt_intro.php'),
			array('alt_intro.php?foo=1&bar=2'),
			array('/typo3/alt_intro.php'),
			array('/index.php'),
			array('../index.php'),
			array('../typo3/alt_intro.php'),
			array('../~userDirectory/index.php'),
			array('../typo3/mod.php?var1=test-case&var2=~user'),
			array(PATH_site . 'typo3/alt_intro.php'),
			array(t3lib_div::getIndpEnv('TYPO3_SITE_URL') . 'typo3/alt_intro.php'),
			array(t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST') . '/index.php'),
		);
	}

	/**
	 * Data provider for invalid URLs.
	 * @see	sanitizeLocalUrlDeniesInvalidUrls
	 */
	public function invalidLocalUrlDataProvider() {
		return array(
			array(''),
			array('http://www.google.de/'),
			array('https://www.google.de/'),
			array('../typo3/whatever.php?argument=javascript:alert(0)'),
		);
	}

	/**
	 * Tests whether valid local URLs are handled correctly.
	 * @dataProvider	validLocalUrlDataProvider
	 * @test
	 */
	public function sanitizeLocalUrlAcceptsPlainValidUrls($url) {
		$this->assertEquals($url, t3lib_div::sanitizeLocalUrl($url));
	}

	/**
	 * Tests whether valid local URLs are handled correctly.
	 * @dataProvider	validLocalUrlDataProvider
	 * @test
	 */
	public function sanitizeLocalUrlAcceptsEncodedValidUrls($url) {
		$this->assertEquals(rawurlencode($url), t3lib_div::sanitizeLocalUrl(rawurlencode($url)));
	}

	/**
	 * Tests whether valid local URLs are handled correctly.
	 * @dataProvider	invalidLocalUrlDataProvider
	 * @test
	 */
	public function sanitizeLocalUrlDeniesPlainInvalidUrls($url) {
		$this->assertEquals('', t3lib_div::sanitizeLocalUrl($url));
	}

	/**
	 * Tests whether valid local URLs are handled correctly.
	 * @dataProvider	invalidLocalUrlDataProvider
	 * @test
	 */
	public function sanitizeLocalUrlDeniesEncodedInvalidUrls($url) {
		$this->assertEquals('', t3lib_div::sanitizeLocalUrl(rawurlencode($url)));
	}

	//////////////////////////////////////
	// Tests concerning arrayDiffAssocRecursive
	//////////////////////////////////////

	/**
	 * Test if a one dimensional array is correctly diffed.
	 *
	 * @test
	 * @see t3lib_div::arrayDiffAssocRecursive
	 */
	public function doesArrayDiffAssocRecursiveCorrectlyHandleOneDimensionalArrays() {
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
	 * Test if a three dimensional array is correctly diffed.
	 *
	 * @test
	 * @see t3lib_div::arrayDiffAssocRecursive
	 */
	public function doesArrayDiffAssocRecursiveCorrectlyHandleMultiDimensionalArrays() {
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
	 * Test if arrays are correctly diffed if types are different.
	 *
	 * @test
	 * @see t3lib_div::arrayDiffAssocRecursive
	 */
	public function doesArrayDiffAssocRecursiveCorrectlyHandleMixedArrays() {
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
	 * Tests whether removeDotsFromTS() behaves correctly.
	 * @test
	 * @see t3lib_div::removeDotsFromTS()
	 */
	public function doesRemoveDotsFromTypoScriptSucceed() {
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
	 * Tests whether removeDotsFromTS() behaves correctly.
	 * @test
	 * @see t3lib_div::removeDotsFromTS()
	 */
	public function doesRemoveDotsFromTypoScriptCorrectlyOverrideWithArray() {
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
	 * Tests whether removeDotsFromTS() behaves correctly.
	 * @test
	 * @see t3lib_div::removeDotsFromTS()
	 */
	public function doesRemoveDotsFromTypoScriptCorrectlyOverrideWithScalar() {
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

	/**
	 * Tests whether getDirs() returns an array of diretories from a given path
	 * @test
	 * @see t3lib_div::getDirs($path)
	 */
	public function checkGetDirsReturnsArrayOfDirectoriesFromGivenDirectory() {
		$path = PATH_t3lib;
		$directories = t3lib_div::get_dirs($path);

		$this->assertType('array', $directories);
	}

	/**
	 * Tests whether getDirs() returns the string 'error' in case of problems reading from the given path
	 * @test
	 * @see t3lib_div::getDirs($path)
	 */
	public function checkGetDirsReturnsStringErrorOnPathFailure() {
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
	public function hmacReturnsNotEqualHashesForNotEqualInput() {
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

	/**
	 * Tests the locallangXMLOverride feature of readLLfile()
	 * @test
	 */
	public function readLLfileLocallangXMLOverride() {
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

			// get default value
		$defaultLL = t3lib_div::readLLfile('EXT:lang/locallang_core.xml', 'default');

			// set override file
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']['EXT:lang/locallang_core.xml'][$unique] = $file;

			// get override value
		$overrideLL = t3lib_div::readLLfile('EXT:lang/locallang_core.xml', 'default');

		$this->assertNotEquals($overrideLL['default']['buttons.logout'], '');
		$this->assertNotEquals($defaultLL['default']['buttons.logout'], $overrideLL['default']['buttons.logout']);
		$this->assertEquals($overrideLL['default']['buttons.logout'], 'EXIT');

		unlink($file);
	}


	///////////////////////////////
	// Tests concerning _GETset()
	///////////////////////////////

	/**
	 * @test
	 */
	public function getSetCanSetWholeArray() {
		$_GET = array();
		$GLOBALS['HTTP_GET_VARS'] = array();
		t3lib_div::_GETset(array('oneKey' => 'oneValue'));

		$this->assertEquals(
			array('oneKey' => 'oneValue'),
			$_GET
		);
		$this->assertEquals(
			array('oneKey' => 'oneValue'),
			$GLOBALS['HTTP_GET_VARS']
		);
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
			$_GET
		);
		$this->assertEquals(
			array('oneKey' => 'oneValue'),
			$GLOBALS['HTTP_GET_VARS']
		);
	}

	/**
	 * @test
	 */
	public function getSetCanAssignOneValueToOneKey() {
		$_GET = array();
		$GLOBALS['HTTP_GET_VARS'] = array();

		t3lib_div::_GETset('oneValue', 'oneKey');

		$this->assertEquals(
			'oneValue',
			$_GET['oneKey']
		);
		$this->assertEquals(
			'oneValue',
			$GLOBALS['HTTP_GET_VARS']['oneKey']
		);
	}

	/**
	 * @test
	 */
	public function getSetForOneValueNotDropsExistingValues() {
		$_GET = array();
		$GLOBALS['HTTP_GET_VARS'] = array();

		t3lib_div::_GETset(array('foo' => 'bar'));
		t3lib_div::_GETset('oneValue', 'oneKey');

		$this->assertEquals(
			array('foo' => 'bar', 'oneKey' => 'oneValue'),
			$_GET
		);
		$this->assertEquals(
			array('foo' => 'bar', 'oneKey' => 'oneValue'),
			$GLOBALS['HTTP_GET_VARS']
		);
	}

	/**
	 * @test
	 */
	public function getSetCanAssignAnArrayToSpecificArrayElement() {
		$_GET = array();
		$GLOBALS['HTTP_GET_VARS'] = array();

		t3lib_div::_GETset(array('childKey' => 'oneValue'), 'parentKey');

		$this->assertEquals(
			array('parentKey' => array('childKey' => 'oneValue')),
			$_GET
		);
		$this->assertEquals(
			array('parentKey' => array('childKey' => 'oneValue')),
			$GLOBALS['HTTP_GET_VARS']
		);
	}

	/**
	 * @test
	 */
	public function getSetCanAssignAValueToSpecificArrayChildElement() {
		$_GET = array();
		$GLOBALS['HTTP_GET_VARS'] = array();

		t3lib_div::_GETset('oneValue', 'parentKey|childKey');

		$this->assertEquals(
			array('parentKey' => array('childKey' => 'oneValue')),
			$_GET
		);
		$this->assertEquals(
			array('parentKey' => array('childKey' => 'oneValue')),
			$GLOBALS['HTTP_GET_VARS']
		);
	}

	/**
	 * @test
	 */
	public function getSetCanAssignAnArrayToSpecificArrayChildElement() {
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
			$_GET
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

	/**
	 * Checks if t3lib_div::fixPermissions() correctly sets permissions to single file
	 * This test assumes directory 'PATH_site'/typo3temp exists
	 * This test is not available on windows OS
	 *
	 * @test
	 * @see t3lib_div::fixPermissions()
	 */
	public function checkFixPermissionsCorrectlySetsPermissionsToFile() {
		if (TYPO3_OS == 'WIN') {
			$this->markTestSkipped('fixPermissions() tests not available on Windows');
		}

			// Create and prepare test file
		$filename = PATH_site . 'typo3temp/' . uniqid('test_');
		t3lib_div::writeFileToTypo3tempDir($filename, '42');
		chmod($filename, 0742);

			// Set target permissions and run method
		$GLOBALS['TYPO3_CONF_VARS']['BE']['fileCreateMask'] = '0660';
		$GLOBALS['TYPO3_CONF_VARS']['BE']['createGroup'] = posix_getegid();
		$fixPermissionsResult = t3lib_div::fixPermissions($filename);

			// Get actual permissions and clean up
		clearstatcache();
		$resultFilePermissions = substr(decoct(fileperms($filename)), 2);
		$resultFileGroup = filegroup($filename);
		unlink($filename);

			// Test if everything was ok
		$this->assertTrue($fixPermissionsResult);
		$this->assertEquals($resultFilePermissions, '0660');
		$this->assertEquals($resultFileGroup, posix_getegid());
	}

	/**
	 * Checks if t3lib_div::fixPermissions() correctly sets permissions to hidden file
	 * This test assumes directory 'PATH_site'/typo3temp exists
	 * This test is not available on windows OS
	 *
	 * @test
	 * @see t3lib_div::fixPermissions()
	 */
	public function checkFixPermissionsCorrectlySetsPermissionsToHiddenFile() {
		if (TYPO3_OS == 'WIN') {
			$this->markTestSkipped('fixPermissions() tests not available on Windows');
		}

			// Create and prepare test file
		$filename = PATH_site . 'typo3temp/' . uniqid('.test_');
		t3lib_div::writeFileToTypo3tempDir($filename, '42');
		chmod($filename, 0742);

			// Set target permissions and run method
		$GLOBALS['TYPO3_CONF_VARS']['BE']['fileCreateMask'] = '0660';
		$GLOBALS['TYPO3_CONF_VARS']['BE']['createGroup'] = posix_getegid();
		$fixPermissionsResult = t3lib_div::fixPermissions($filename);

			// Get actual permissions and clean up
		clearstatcache();
		$resultFilePermissions = substr(decoct(fileperms($filename)), 2);
		$resultFileGroup = filegroup($filename);
		unlink($filename);

			// Test if everything was ok
		$this->assertTrue($fixPermissionsResult);
		$this->assertEquals($resultFilePermissions, '0660');
		$this->assertEquals($resultFileGroup, posix_getegid());
	}

	/**
	 * Checks if t3lib_div::fixPermissions() correctly sets permissions to directory with trailing slash
	 * This test assumes directory 'PATH_site'/typo3temp exists
	 * This test is not available on windows OS
	 *
	 * @test
	 * @see t3lib_div::fixPermissions()
	 */
	public function checkFixPermissionsCorrectlySetsPermissionsToDirectory() {
		if (TYPO3_OS == 'WIN') {
			$this->markTestSkipped('fixPermissions() tests not available on Windows');
		}

			// Create and prepare test directory
		$directory = PATH_site . 'typo3temp/' . uniqid('test_');
		t3lib_div::mkdir($directory);
		chmod($directory, 1551);

			// Set target permissions and run method
		$GLOBALS['TYPO3_CONF_VARS']['BE']['folderCreateMask'] = '0770';
		$GLOBALS['TYPO3_CONF_VARS']['BE']['createGroup'] = posix_getegid();
		$fixPermissionsResult = t3lib_div::fixPermissions($directory . '/');

			// Get actual permissions and clean up
		clearstatcache();
		$resultDirectoryPermissions = substr(decoct(fileperms($directory)), 1);
		$resultDirectoryGroup = filegroup($directory);
		t3lib_div::rmdir($directory);

			// Test if everything was ok
		$this->assertTrue($fixPermissionsResult);
		$this->assertEquals($resultDirectoryPermissions, '0770');
		$this->assertEquals($resultDirectoryGroup, posix_getegid());
	}

	/**
	 * Checks if t3lib_div::fixPermissions() correctly sets permissions to hidden directory
	 * This test assumes directory 'PATH_site'/typo3temp exists
	 * This test is not available on windows OS
	 *
	 * @test
	 * @see t3lib_div::fixPermissions()
	 */
	public function checkFixPermissionsCorrectlySetsPermissionsToHiddenDirectory() {
		if (TYPO3_OS == 'WIN') {
			$this->markTestSkipped('fixPermissions() tests not available on Windows');
		}

			// Create and prepare test directory
		$directory = PATH_site . 'typo3temp/' . uniqid('.test_');
		t3lib_div::mkdir($directory);
		chmod($directory, 1551);

			// Set target permissions and run method
		$GLOBALS['TYPO3_CONF_VARS']['BE']['folderCreateMask'] = '0770';
		$GLOBALS['TYPO3_CONF_VARS']['BE']['createGroup'] = posix_getegid();
		$fixPermissionsResult = t3lib_div::fixPermissions($directory);

			// Get actual permissions and clean up
		clearstatcache();
		$resultDirectoryPermissions = substr(decoct(fileperms($directory)), 1);
		$resultDirectoryGroup = filegroup($directory);
		t3lib_div::rmdir($directory);

			// Test if everything was ok
		$this->assertTrue($fixPermissionsResult);
		$this->assertEquals($resultDirectoryPermissions, '0770');
		$this->assertEquals($resultDirectoryGroup, posix_getegid());
	}

	/**
	 * Checks if t3lib_div::fixPermissions() correctly sets permissions recursivly
	 * This test assumes directory 'PATH_site'/typo3temp exists
	 * This test is not available on windows OS
	 *
	 * @test
	 * @see t3lib_div::fixPermissions()
	 */
	public function checkFixPermissionsCorrectlySetsPermissionsRecursive() {
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
		$GLOBALS['TYPO3_CONF_VARS']['BE']['createGroup'] = posix_getegid();
		$fixPermissionsResult = t3lib_div::fixPermissions($baseDirectory, TRUE);

			// Get actual permissions
		clearstatcache();
		$resultBaseDirectoryPermissions = substr(decoct(fileperms($baseDirectory)), 1);
		$resultBaseDirectoryGroup = filegroup($baseDirectory);
		$resultBaseFilePermissions = substr(decoct(fileperms($baseDirectory . '/file')), 2);
		$resultBaseFileGroup = filegroup($baseDirectory . '/file');
		$resultFooDirectoryPermissions = substr(decoct(fileperms($baseDirectory . '/foo')), 1);
		$resultFooDirectoryGroup = filegroup($baseDirectory . '/foo');
		$resultFooFilePermissions = substr(decoct(fileperms($baseDirectory . '/foo/file')), 2);
		$resultFooFileGroup = filegroup($baseDirectory . '/foo/file');
		$resultBarDirectoryPermissions = substr(decoct(fileperms($baseDirectory . '/.bar')), 1);
		$resultBarDirectoryGroup = filegroup($baseDirectory . '/.bar');
		$resultBarFilePermissions = substr(decoct(fileperms($baseDirectory . '/.bar/.file')), 2);
		$resultBarFileGroup = filegroup($baseDirectory . '/.bar/.file');
		$resultBarFile2Permissions = substr(decoct(fileperms($baseDirectory . '/.bar/..file2')), 2);
		$resultBarFile2Group = filegroup($baseDirectory . '/.bar/..file2');

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
		$this->assertEquals($resultBaseDirectoryGroup, posix_getegid());
		$this->assertEquals($resultBaseFilePermissions, '0660');
		$this->assertEquals($resultBaseFileGroup, posix_getegid());
		$this->assertEquals($resultFooDirectoryPermissions, '0770');
		$this->assertEquals($resultFooDirectoryGroup, posix_getegid());
		$this->assertEquals($resultFooFilePermissions, '0660');
		$this->assertEquals($resultFooFileGroup, posix_getegid());
		$this->assertEquals($resultBarDirectoryPermissions, '0770');
		$this->assertEquals($resultBarDirectoryGroup, posix_getegid());
		$this->assertEquals($resultBarFilePermissions, '0660');
		$this->assertEquals($resultBarFileGroup, posix_getegid());
		$this->assertEquals($resultBarFile2Permissions, '0660');
		$this->assertEquals($resultBarFile2Group, posix_getegid());
	}

	/**
	 * Checks if t3lib_div::fixPermissions() does not fix permissions on not allowed path
	 * This test assumes directory 'PATH_site'/typo3temp exists
	 * This test is not available on windows OS
	 *
	 * @test
	 * @see t3lib_div::fixPermissions()
	 */
	public function checkFixPermissionsDoesNotSetPermissionsToNotAllowedPath() {
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
		$this->assertEquals($resultFilePermissions, '0742');
	}

	/**
	 * Checks if t3lib_div::mkdir() correctly creates a directory
	 * This test assumes directory 'PATH_site'/typo3temp exists
	 *
	 * @test
	 * @see t3lib_div::mkdir()
	 */
	public function checkMkdirCorrectlyCreatesDirectory() {
		$directory = PATH_site . 'typo3temp/' . uniqid('test_');
		$mkdirResult = t3lib_div::mkdir($directory);
		$directoryCreated = is_dir($directory);
		t3lib_div::rmdir($directory);
		$this->assertTrue($mkdirResult);
		$this->assertTrue($directoryCreated);
	}

	/**
	 * Checks if t3lib_div::mkdir() correctly creates a hidden directory
	 * This test assumes directory 'PATH_site'/typo3temp exists
	 *
	 * @test
	 * @see t3lib_div::mkdir()
	 */
	public function checkMkdirCorrectlyCreatesHiddenDirectory() {
		$directory = PATH_site . 'typo3temp/' . uniqid('.test_');
		$mkdirResult = t3lib_div::mkdir($directory);
		$directoryCreated = is_dir($directory);
		t3lib_div::rmdir($directory);
		$this->assertTrue($mkdirResult);
		$this->assertTrue($directoryCreated);
	}

	/**
	 * Checks if t3lib_div::mkdir() correctly creates a directory with trailing slash
	 * This test assumes directory 'PATH_site'/typo3temp exists
	 *
	 * @test
	 * @see t3lib_div::mkdir()
	 */
	public function checkMkdirCorrectlyCreatesDirectoryWithTrailingSlash() {
		$directory = PATH_site . 'typo3temp/' . uniqid('test_');
		$mkdirResult = t3lib_div::mkdir($directory);
		$directoryCreated = is_dir($directory);
		t3lib_div::rmdir($directory);
		$this->assertTrue($mkdirResult);
		$this->assertTrue($directoryCreated);
	}
}
?>