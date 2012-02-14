<?php
/***************************************************************
* Copyright notice
*
* (c) 2009-2011 Oliver Klee (typo3-coding@oliverklee.de)
* All rights reserved
*
* This script is part of the TYPO3 project. The TYPO3 project is
* free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* The GNU General Public License can be found at
* http://www.gnu.org/copyleft/gpl.html.
*
* This script is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Testcase for the "tslib_fe" class in the TYPO3 Core.
 *
 * @package TYPO3
 * @subpackage tslib
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tslib_feTest extends tx_phpunit_testcase {
	/**
	 * @var tslib_fe
	 */
	private $fixture;

	public function setUp() {
			// This creates an instance of the class without calling the
			// original constructor.
		$tslib_feClassName = uniqid('tslib_fe');
		$t3lib_pageSelectClassName = uniqid('t3lib_pageSelect');
		eval(
			'class ' . $tslib_feClassName . ' extends tslib_fe {' .
				'public function ' . $tslib_feClassName . '() {}' .

				'public function roundTripCryptString($string) {' .
					'return parent::roundTripCryptString($string);' .
				'}' .

				'public function stripIPv4($strIP) {' .
					'return parent::stripIPv4($strIP);' .
				'}' .

				'public function stripIPv6($strIP) {' .
					'return parent::stripIPv6($strIP);' .
				'}' .

				'protected function getSysDomainCache() {' .
					'return array(' .
						'1 => array(' .
							'"pid" => 1,' .
							'"domainName" => "localhost",' .
							'"forced" => 0' .
						')' .
					');' .
				'}' .

				'public function getDomainDataForPid($targetPid) {' .
					'return array(' .
						'"pid" => 1,' .
						'"domainName" => "localhost",' .
						'"forced" => 0' .
					');' .
				'}' .
			'}' .

			'class ' . $t3lib_pageSelectClassName . ' extends t3lib_pageSelect {' .
				'public function ' . $t3lib_pageSelectClassName . '() {}' .

				'public function getRootLine($uid, $MP = "", $ignoreMPerrors = FALSE) {' .
					'return array(' .
						'1 => array(' .
							'"pid" => 1,' .
							'"uid" => 2,' .
							'"t3ver_oid" => 0,' .
							'"t3ver_wsid" => 0,' .
							'"t3ver_state" => 0,' .
							'"t3ver_swapmode" => 0,' .
							'"title" => "Index",' .
							'"alias" => "",' .
							'"nav_title" => "",' .
							'"media" => "",' .
							'"layout" => 0,' .
							'"hidden" => 0,' .
							'"starttime" => 0,' .
							'"endtime" => 0,' .
							'"fe_group" => 0,' .
							'"extendToSubpages" => 0,' .
							'"doktype" => 1,' .
							'"TSconfig" => "",' .
							'"storage_pid" => 0,' .
							'"is_siteroot" => 0,' .
							'"mount_pid" => 0,' .
							'"mount_pid_ol" => 0,' .
							'"fe_login_mode" => 0,' .
							'"backend_layout_next_level" => 0' .
						'),' .
						'0 => array(' .
							'"pid" => 0,' .
							'"uid" => 1,' .
							'"t3ver_oid" => 0,' .
							'"t3ver_wsid" => 0,' .
							'"t3ver_state" => 0,' .
							'"t3ver_swapmode" => 0,' .
							'"title" => "Root",' .
							'"alias" => "",' .
							'"nav_title" => "",' .
							'"media" => "",' .
							'"layout" => 0,' .
							'"hidden" => 0,' .
							'"starttime" => 0,' .
							'"endtime" => 0,' .
							'"fe_group" => "",' .
							'"extendToSubpages" => 0,' .
							'"doktype" => 1,' .
							'"TSconfig" => "",' .
							'"storage_pid" => 2,' .
							'"is_siteroot" => 1,' .
							'"mount_pid" => 0,' .
							'"mount_pid_ol" => 0,' .
							'"fe_login_mode" => 0,' .
							'"backend_layout_next_level" => 0' .
						')' .
					');' .
				'}' .
			'}'
		);

		$this->fixture = new $tslib_feClassName();
		$this->fixture->sys_page = new $t3lib_pageSelectClassName();
		$this->fixture->TYPO3_CONF_VARS = $GLOBALS['TYPO3_CONF_VARS'];
		$this->fixture->TYPO3_CONF_VARS['SYS']['encryptionKey']
			= '170928423746123078941623042360abceb12341234231';
	}

	public function tearDown() {
		unset($this->fixture);
	}


	////////////////////////////////
	// Tests concerning codeString
	////////////////////////////////

	/**
	 * @test
	 */
	public function codeStringForNonEmptyStringReturns10CharacterHashAndCodedString() {
		$this->assertRegExp(
			'/^[0-9a-f]{10}:[a-zA-Z0-9+=\/]+$/',
			$this->fixture->codeString('Hello world!')
		);
	}

	/**
	 * @test
	 */
	public function decodingCodedStringReturnsOriginalString() {
		$clearText = 'Hello world!';

		$this->assertEquals(
			$clearText,
			$this->fixture->codeString(
				$this->fixture->codeString($clearText), TRUE
			)
		);
	}


	//////////////////////
	// Tests concerning sL
	//////////////////////

	/**
	 * @test
	 */
	public function localizationReturnsUnchangedStringIfNotLocallangLabel() {
		$string = uniqid();
		$this->assertEquals($string, $this->fixture->sL($string));
	}


	//////////////////////////////////////////
	// Tests concerning roundTripCryptString
	//////////////////////////////////////////

	/**
	 * @test
	 */
	public function roundTripCryptStringCreatesStringWithSameLengthAsInputString() {
		$clearText = 'Hello world!';

		$this->assertEquals(
			strlen($clearText),
			strlen($this->fixture->roundTripCryptString($clearText))
		);
	}

	/**
	 * @test
	 */
	public function roundTripCryptStringCreatesResultDifferentFromInputString() {
		$clearText = 'Hello world!';

		$this->assertNotEquals(
			$clearText,
			$this->fixture->roundTripCryptString($clearText)
		);
	}

	/**
	 * @test
	 */
	public function roundTripCryptStringAppliedTwoTimesReturnsOriginalString() {
		$clearText = 'Hello world!';

		$this->assertEquals(
			$clearText,
			$this->fixture->roundTripCryptString(
				$this->fixture->roundTripCryptString($clearText)
			)
		);
	}

	//////////////////////////////////////
	// Tests concerning stat-anonymization
	//////////////////////////////////////

	/**
	 * Data provider for stripIPv6Correct
	 *
	 * @return array Data sets
	 */
	public static function stripIPv4DataProviderCorrect() {
		return array(
			'empty address, prefix-length 24' => array('0.0.0.0', '24', '0.0.0.0'),
			'normal address 1, prefix-length 1' => array('1.2.3.4', '1', '0.0.0.0'),
			'normal address 2, prefix-length 24' => array('192.168.5.79', '24', '192.168.5.0'),
			'normal address 2, prefix-length 30' => array('192.168.5.79', '30', '192.168.5.76'),
				// test for no anonymization; full prefix-length
			'normal address 2, prefix-length 32' => array('192.168.5.79', '32', '192.168.5.79'),
				// test for full anonymization; full prefix-length
			'normal address 2, prefix-length 0' => array('192.168.5.79', '0', '0.0.0.0'),
		);
	}

	/**
	 * @test
	 * @dataProvider stripIPv4DataProviderCorrect
	 */
	public function stripIPv4Correct($address, $prefixLength, $anonymized) {
		$oldConfig = $this->fixture->config;

		$this->fixture->config = array('config' =>
			array('stat_IP_anonymize' => '1',
				'stat_IP_anonymize_mask_ipv4' => $prefixLength
			)
		);

		$this->assertEquals(
			$this->fixture->stripIPv4($address),
			$anonymized
		);
		$this->fixture->config = $oldConfig;
	}

	/**
	 * Data provider for stripIPv6Correct
	 *
	 * @return array Data sets
	 */
	public static function stripIPv6DataProviderCorrect() {
		return array(
			'empty address, prefix-length 96' => array('::', '96', '::'),
			'normal address 1, prefix-length 1' => array('1:2:3::4', '1', '::'),
			'normal address 2, prefix-length 4' => array('ffff::9876', '4', 'f000::'),
			'normal address 2, prefix-length 1' => array('ffff::9876', '1', '8000::'),
			'normal address 3, prefix-length 96' => array('abc:def::9876', '96', 'abc:def::'),
			'normal address 3, prefix-length 120' => array('abc:def::9876', '120', 'abc:def::9800'),
				// test for no anonymization; full prefix-length
			'normal address 3, prefix-length 128' => array('abc:def::9876', '128', 'abc:def::9876'),
				// test for full anonymization
			'normal address 3, prefix-length 0' => array('abc:def::9876', '0', '::'),
		);
	}

	/**
	 * @test
	 * @dataProvider stripIPv6DataProviderCorrect
	 */
	public function stripIPv6Correct($address, $prefixLength, $anonymized) {
		$oldConfig = $this->fixture->config;

		$this->fixture->config = array('config' =>
			array('stat_IP_anonymize' => '1',
				'stat_IP_anonymize_mask_ipv6' => $prefixLength
			)
		);

		$this->assertEquals(
			$this->fixture->stripIPv6($address),
			$anonymized
		);
		$this->fixture->config = $oldConfig;
	}

	/**
	 * @test
	 */
	public function getDomainDataForPageId() {
		$this->assertEquals(
			$this->fixture->getDomainDataForPid(1),
			array(
				'pid' => 1,
				'domainName' => 'localhost',
				'forced' => 0
			)
		);
	}

	/**
	 * @test
	 */
	public function getDomainNameForPageId() {
		$this->assertEquals(
			$this->fixture->getDomainNameForPid(1),
			'localhost'
		);
	}
}
?>