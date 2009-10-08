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
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_div_testcase extends tx_phpunit_testcase {

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
		$testString = ' a , b , ' . chr(10) . ' ,d ,,  e,f,';
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
	public function checkTrimExplodeLimitsResultsToFirstXElementsWithPositiveParameter() {
		$testString = ' a , b , c , , d,, ,e ';
		$expectedArray = array('a', 'b', 'c'); // limiting returns the rest of the string as the last element
		$actualArray = t3lib_div::trimExplode(',', $testString, false, 3);

		$this->assertEquals($expectedArray, $actualArray);
	}

	/**
	 * @test
	 */
	public function checkTrimExplodeLimitsResultsToLastXElementsWithNegativeParameter() {
		$testString = ' a , b , c , d, ,e, f , , ';
		$expectedArray = array('a', 'b', 'c'); // limiting returns the rest of the string as the last element
		$actualArray = t3lib_div::trimExplode(',', $testString, true, -3);

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
}

?>