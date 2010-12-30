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

	/**
	 * @return array
	 */
	public static function hostnameAndPortDataProvider() {
		return array(
			'localhost ipv4 without port' => array('127.0.0.1', '127.0.0.1', ''),
			'localhost ipv4 with port' => array('127.0.0.1:81', '127.0.0.1', '81'),
			'localhost ipv6 without port' => array('[::1]', '[::1]', ''),
			'localhost ipv6 with port' => array('[::1]:81', '[::1]', '81'),
			'ipv6 without port' => array('[2001:DB8::1]', '[2001:DB8::1]', ''),
			'ipv6 with port' => array('[2001:DB8::1]:81', '[2001:DB8::1]', '81'),
			'hostname without port' => array('lolli.did.this', 'lolli.did.this', ''),
			'hostname with port' => array('lolli.did.this:42', 'lolli.did.this', '42'),
		);
	}

	/**
	 * @test
	 * @dataProvider hostnameAndPortDataProvider
	 */
	public function getIndpEnvTypo3HostOnlyParsesHostnamesAndIpAdresses($httpHost, $expectedIp) {
		$_SERVER['HTTP_HOST'] = $httpHost;
		$this->assertEquals($expectedIp, t3lib_div::getIndpEnv('TYPO3_HOST_ONLY'));
	}

	/**
	 * @test
	 * @dataProvider hostnameAndPortDataProvider
	 */
	public function getIndpEnvTypo3PortParsesHostnamesAndIpAdresses($httpHost, $dummy, $expectedPort) {
		$_SERVER['HTTP_HOST'] = $httpHost;
		$this->assertEquals($expectedPort, t3lib_div::getIndpEnv('TYPO3_PORT'));
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
			t3lib_div::quoteJSvalue(chr(9))
		);
	}

	/**
	 * @test
	 */
	public function quoteJSvalueEscapesLinefeed() {
		$this->assertEquals(
			"'" . '\n' . "'",
			t3lib_div::quoteJSvalue(chr(10))
		);
	}

	/**
	 * @test
	 */
	public function quoteJSvalueEscapesCarriageReturn() {
		$this->assertEquals(
			"'" . '\r' . "'",
			t3lib_div::quoteJSvalue(chr(13))
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

	/**
	 * Checks if t3lib_div::split_fileref() return NO file extension if incomming $fileref is a folder
	 * This test avoid bug #0014845: Filelist module reports "type" of files also for directories
	 * This test assumes directory 'PATH_site'/typo3temp exists
	 *
	 * @test
	 * @see	t3lib_div::split_fileref()
	 */
	public function checkIfSplitFileRefReturnsFileTypeNotForFolders(){
		$directoryName = uniqid('test_') . '.com';
		$directoryPath = PATH_site . 'typo3temp/';
		$directory = $directoryPath . $directoryName;
		mkdir($directory, octdec($GLOBALS['TYPO3_CONF_VARS']['BE']['folderCreateMask']));

		$fileInfo = t3lib_div::split_fileref($directory);

		$directoryCreated = is_dir($directory);
		$this->assertTrue($directoryCreated);

		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $fileInfo);
		$this->assertEquals($directoryPath, $fileInfo['path']);
		$this->assertEquals($directoryName, $fileInfo['file']);
		$this->assertEquals($directoryName, $fileInfo['filebody']);
		$this->assertEquals('', $fileInfo['fileext']);
		$this->assertArrayNotHasKey('realFileext', $fileInfo);

		rmdir($directory);
	}

	/**
	 * @test
	 * @see t3lib_div::split_fileref()
	 */
	public function checkIfSplitFileRefReturnsFileTypeForFilesWithoutPathSite() {
		$testFile = 'fileadmin/media/someFile.png';

		$fileInfo = t3lib_div::split_fileref($testFile);
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $fileInfo);
		$this->assertEquals('fileadmin/media/', $fileInfo['path']);
		$this->assertEquals('someFile.png', $fileInfo['file']);
		$this->assertEquals('someFile', $fileInfo['filebody']);
		$this->assertEquals('png', $fileInfo['fileext']);
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
}

?>