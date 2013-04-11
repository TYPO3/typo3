<?php
namespace TYPO3\CMS\Core\Tests\Unit\Utility;

/***************************************************************
 * Copyright notice
 *
 * (c) 2010-2013 Steffen Kamper (info@sk-typo3.de)
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
 * Testcase for the \TYPO3\CMS\Core\Utility\ClientUtility class.
 *
 * @author Steffen Kamper <info@sk-typo3.de>
 */
class ClientUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * backed-up TYPO3_CONF_VARS SC_OPTIONS
	 *
	 * @var array
	 */
	private $scOptionsBackup = array();

	/**
	 * backed-up T3_VAR callUserFunction
	 *
	 * @var array
	 */
	private $callUserFunctionBackup = array();

	public function setUp() {
		$this->scOptionsBackup = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'];
		$this->callUserFunctionBackup = $GLOBALS['T3_VAR']['callUserFunction'];
	}

	public function tearDown() {
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'] = $this->scOptionsBackup;
		$GLOBALS['T3_VAR']['callUserFunction'] = $this->callUserFunctionBackup;
	}

	//////////////////////////////////////////////////////////
	// Utility Functions
	//////////////////////////////////////////////////////////
	/**
	 * Compares array of UA strings with expected result array of browser/version pair
	 *
	 * @param array $browserStrings array with userAgent strings
	 * @param array $expectedMembers array with expected browser/version for given userAgent strings
	 */
	private function analyzeUserAgentStrings($browserStrings, $expectedMembers) {
		$compare = ($expected = array());
		foreach ($browserStrings as $browserString) {
			$infoArray = \TYPO3\CMS\Core\Utility\ClientUtility::getBrowserInfo($browserString);
			$expected[] = $expectedMembers;
			$compare[] = array(
				'browser' => $infoArray['browser'],
				'version' => substr($infoArray['version'], 0, 1)
			);
		}
		$this->assertEquals($expected, $compare);
	}

	//////////////////////////////////////////////////////////
	// Tests concerning getBrowserInfo
	//////////////////////////////////////////////////////////
	/**
	 * @test
	 */
	public function checkBrowserInfoIE6() {
		$browserStrings = array(
			'Mozilla/4.0 (compatible; MSIE 6.1; Windows XP; .NET CLR 1.1.4322; .NET CLR 2.0.50727)',
			'Mozilla/4.0 (compatible; MSIE 6.1; Windows XP)',
			'Mozilla/4.0 (compatible; MSIE 6.01; Windows NT 6.0)',
			'Mozilla/5.0 (Windows; U; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 2.0.50727)',
			'Mozilla/5.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 2.0.50727)',
			'Mozilla/5.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4325)',
			'Mozilla/5.0 (compatible; MSIE 6.0; Windows NT 5.1)',
			'Mozilla/45.0 (compatible; MSIE 6.0; Windows NT 5.1)',
			'Mozilla/4.08 (compatible; MSIE 6.0; Windows NT 5.1)',
			'Mozilla/4.01 (compatible; MSIE 6.0; Windows NT 5.1)',
			'Mozilla/4.0 (X11; MSIE 6.0; i686; .NET CLR 1.1.4322; .NET CLR 2.0.50727; FDM)',
			'Mozilla/4.0 (Windows; MSIE 6.0; Windows NT 6.0)',
			'Mozilla/4.0 (Windows; MSIE 6.0; Windows NT 5.2)',
			'Mozilla/4.0 (Windows; MSIE 6.0; Windows NT 5.0)',
			'Mozilla/4.0 (Windows; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 2.0.50727)',
			'Mozilla/4.0 (MSIE 6.0; Windows NT 5.1)',
			'Mozilla/4.0 (MSIE 6.0; Windows NT 5.0)',
			'Mozilla/4.0 (compatible;MSIE 6.0;Windows 98;Q312461)',
			'Mozilla/4.0 (Compatible; Windows NT 5.1; MSIE 6.0) (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322; .NET CLR 2.0.50727)',
			'Mozilla/4.0 (compatible; U; MSIE 6.0; Windows NT 5.1)',
			'Mozilla/4.0 (compatible; MSIE 6,0; Windows NT 5,1; SV1; Alexa Toolbar)'
		);
		$expectedMembers = array(
			'browser' => 'msie',
			'version' => '6'
		);
		$this->analyzeUserAgentStrings($browserStrings, $expectedMembers);
	}

	/**
	 * @test
	 */
	public function checkBrowserInfoIE7() {
		$browserStrings = array(
			'Mozilla/5.0 (Windows; U; MSIE 7.0; Windows NT 6.0; en-US)',
			'Mozilla/5.0 (Windows; U; MSIE 7.0; Windows NT 6.0; el-GR)',
			'Mozilla/5.0 (MSIE 7.0; Macintosh; U; SunOS; X11; gu; SV1; InfoPath.2; .NET CLR 3.0.04506.30; .NET CLR 3.0.04506.648)',
			'Mozilla/5.0 (compatible; MSIE 7.0; Windows NT 6.0; WOW64; SLCC1; .NET CLR 2.0.50727; Media Center PC 5.0; c .NET CLR 3.0.04506; .NET CLR 3.5.30707; InfoPath.1; el-GR)',
			'Mozilla/5.0 (compatible; MSIE 7.0; Windows NT 6.0; SLCC1; .NET CLR 2.0.50727; Media Center PC 5.0; c .NET CLR 3.0.04506; .NET CLR 3.5.30707; InfoPath.1; el-GR)',
			'Mozilla/5.0 (compatible; MSIE 7.0; Windows NT 6.0; fr-FR)',
			'Mozilla/5.0 (compatible; MSIE 7.0; Windows NT 6.0; en-US)',
			'Mozilla/5.0 (compatible; MSIE 7.0; Windows NT 5.2; WOW64; .NET CLR 2.0.50727)',
			'Mozilla/4.79 [en] (compatible; MSIE 7.0; Windows NT 5.0; .NET CLR 2.0.50727; InfoPath.2; .NET CLR 1.1.4322; .NET CLR 3.0.04506.30; .NET CLR 3.0.04506.648)',
			'Mozilla/4.0 (Windows; MSIE 7.0; Windows NT 5.1; SV1; .NET CLR 2.0.50727)',
			'Mozilla/4.0 (Mozilla/4.0; MSIE 7.0; Windows NT 5.1; FDM; SV1; .NET CLR 3.0.04506.30)',
			'Mozilla/4.0 (Mozilla/4.0; MSIE 7.0; Windows NT 5.1; FDM; SV1)',
			'Mozilla/4.0 (compatible;MSIE 7.0;Windows NT 6.0)',
			'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0)',
			'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0;)',
			'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; YPC 3.2.0; SLCC1; .NET CLR 2.0.50727; Media Center PC 5.0; InfoPath.2; .NET CLR 3.5.30729; .NET CLR 3.0.30618)',
			'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; YPC 3.2.0; SLCC1; .NET CLR 2.0.50727; .NET CLR 3.0.04506)',
			'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; WOW64; SLCC1; Media Center PC 5.0; .NET CLR 2.0.50727)',
			'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; WOW64; SLCC1; .NET CLR 3.0.04506)',
			'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; WOW64; SLCC1; .NET CLR 2.0.50727; Media Center PC 5.0; InfoPath.2; .NET CLR 3.5.30729; .NET CLR 3.0.30618; .NET CLR 1.1.4322)'
		);
		$expectedMembers = array(
			'browser' => 'msie',
			'version' => '7'
		);
		$this->analyzeUserAgentStrings($browserStrings, $expectedMembers);
	}

	/**
	 * @test
	 */
	public function checkBrowserInfoIE8() {
		$browserStrings = array(
			'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.2; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0)',
			'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; WOW64; Trident/4.0; SLCC2; Media Center PC 6.0; InfoPath.2; MS-RTC LM 8)',
			'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; WOW64; Trident/4.0; SLCC2; .NET CLR 2.0.50727; InfoPath.2)',
			'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; WOW64; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; Zune 3.0)',
			'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; WOW64; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; msn OptimizedIE8;ZHCN)',
			'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; WOW64; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; MS-RTC LM 8)',
			'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; WOW64; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; InfoPath.3; Zune 4.0)',
			'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; WOW64; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; InfoPath.3)',
			'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; WOW64; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; InfoPath.2; OfficeLiveConnector.1.4; OfficeLivePatch.1.3; yie8)',
			'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; WOW64; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; InfoPath.2; OfficeLiveConnector.1.3; OfficeLivePatch.0.0; Zune 3.0; MS-RTC LM 8)',
			'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; WOW64; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; InfoPath.2; OfficeLiveConnector.1.3; OfficeLivePatch.0.0; MS-RTC LM 8; Zune 4.0)',
			'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; WOW64; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; InfoPath.2; MS-RTC LM 8)',
			'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; WOW64; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; InfoPath.2; FDM; OfficeLiveConnector.1.4; OfficeLivePatch.1.3; .NET CLR 1.1.4322)',
			'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; WOW64; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; InfoPath.2; .NET4.0C; .NET4.0E; FDM)',
			'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; WOW64; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; .NET CLR 4.0.20402; MS-RTC LM 8)',
			'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; WOW64; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; .NET CLR 1.1.4322; InfoPath.2; MS-RTC LM 8)',
			'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; WOW64; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; .NET CLR 1.1.4322; InfoPath.2)',
			'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; WOW64; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; InfoPath.3; .NET CLR 4.0.20506)',
			'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; WOW64; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729)',
			'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; WOW64; Trident/4.0; MRA 5.5 (build 02842); SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; InfoPath.2)',
			'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0; Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1) ; SLCC1; .NET CLR 2.0.50727; .NET CLR 1.1.4322; .NET CLR 3.5.30729; .NET CLR 3.0.30729)'
		);
		$expectedMembers = array(
			'browser' => 'msie',
			'version' => '8'
		);
		$this->analyzeUserAgentStrings($browserStrings, $expectedMembers);
	}

	/**
	 * @test
	 */
	public function checkGeckoVersion() {
		$userAgentString = 'Mozilla/5.0 (Windows; U; Windows NT 6.0; de; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3';
		$infoArray = \TYPO3\CMS\Core\Utility\ClientUtility::getBrowserInfo($userAgentString);
		$this->assertEquals('1.9.2.3', $infoArray['all']['gecko']);
	}

	/**
	 * @test
	 */
	public function getBrowserInfoReturnsCorrectSystemValueForUserAgentStringOfWindows7() {
		$userAgentString = 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0)';
		$infoArray = \TYPO3\CMS\Core\Utility\ClientUtility::getBrowserInfo($userAgentString);
		$this->assertContains('win7', $infoArray['all_systems']);
	}

	/**
	 * @test
	 */
	public function getBrowserInfoReturnsCorrectSystemValueForUserAgentStringOfWindowsVista() {
		$userAgentString = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; SLCC1; .NET CLR 2.0.50727; .NET CLR 3.0.04506)';
		$infoArray = \TYPO3\CMS\Core\Utility\ClientUtility::getBrowserInfo($userAgentString);
		$this->assertContains('winVista', $infoArray['all_systems']);
	}

	/**
	 * @test
	 */
	public function getBrowserInfoReturnsCorrectSystemValueForUserAgentStringOfWindowsXp() {
		$userAgentString = 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0)';
		$infoArray = \TYPO3\CMS\Core\Utility\ClientUtility::getBrowserInfo($userAgentString);
		$this->assertContains('winXP', $infoArray['all_systems']);
	}

	/**
	 * @test
	 */
	public function getBrowserInfoReturnsCorrectSystemValueForUserAgentStringOfWindows2k() {
		$userAgentString = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0; SV1)';
		$infoArray = \TYPO3\CMS\Core\Utility\ClientUtility::getBrowserInfo($userAgentString);
		$this->assertContains('win2k', $infoArray['all_systems']);
	}

	/**
	 * @test
	 */
	public function getBrowserInfoReturnsCorrectSystemValueForUserAgentStringOfWindows2kServicePack1() {
		$userAgentString = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.01; SV1)';
		$infoArray = \TYPO3\CMS\Core\Utility\ClientUtility::getBrowserInfo($userAgentString);
		$this->assertContains('win2k', $infoArray['all_systems']);
	}

	/**
	 * @test
	 */
	public function getBrowserInfoReturnsCorrectSystemValueForUserAgentStringOfWindowsNt() {
		$userAgentString = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 4.0)';
		$infoArray = \TYPO3\CMS\Core\Utility\ClientUtility::getBrowserInfo($userAgentString);
		$this->assertContains('winNT', $infoArray['all_systems']);
	}

	/**
	 * @test
	 */
	public function getBrowserInfoReturnsCorrectSystemValueForUserAgentStringOfIpad() {
		$userAgentString = 'Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7W367a Safari/531.21.10';
		$infoArray = \TYPO3\CMS\Core\Utility\ClientUtility::getBrowserInfo($userAgentString);
		$this->assertContains('iOS', $infoArray['all_systems']);
	}

	/**
	 * @test
	 */
	public function getBrowserInfoReturnsCorrectSystemValueForUserAgentStringOfIphone() {
		$userAgentString = 'Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_0 like Mac OS X; en-us) AppleWebKit/532.9 (KHTML, like Gecko) Version/4.0.5 Mobile/8A293 Safari/6531.22.7';
		$infoArray = \TYPO3\CMS\Core\Utility\ClientUtility::getBrowserInfo($userAgentString);
		$this->assertContains('iOS', $infoArray['all_systems']);
	}

	/**
	 * @test
	 */
	public function getBrowserInfoReturnsCorrectSystemValueForUserAgentStringOfIpod() {
		$userAgentString = 'Mozilla/5.0 (iPod; U; CPU like Mac OS X; en) AppleWebKit/420.1 (KHTML, like Geckto) Version/3.0 Mobile/3A101a Safari/419.3';
		$infoArray = \TYPO3\CMS\Core\Utility\ClientUtility::getBrowserInfo($userAgentString);
		$this->assertContains('iOS', $infoArray['all_systems']);
	}

	/**
	 * @test
	 */
	public function getBrowserInfoReturnsCorrectSystemValueForUserAgentStringOfMacOsX() {
		$userAgentString = 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_5; en-us) AppleWebKit/534.15+ (KHTML, like Gecko) Version/5.0.3 Safari/533.19.4';
		$infoArray = \TYPO3\CMS\Core\Utility\ClientUtility::getBrowserInfo($userAgentString);
		$this->assertContains('mac', $infoArray['all_systems']);
	}

	/**
	 * @test
	 */
	public function getBrowserInfoReturnsCorrectSystemValueForUserAgentStringOfLinux() {
		$userAgentString = 'Mozilla/5.0 (X11; U; Linux x86_64; en-US; rv:1.9.2.8) Gecko/20100723 Ubuntu/10.04 (lucid) Firefox/3.6.8';
		$infoArray = \TYPO3\CMS\Core\Utility\ClientUtility::getBrowserInfo($userAgentString);
		$this->assertContains('linux', $infoArray['all_systems']);
	}

	/**
	 * @test
	 */
	public function getBrowserInfoReturnsCorrectSystemValueForUserAgentStringOfSolaris() {
		$userAgentString = 'Mozilla/5.0 (X11; U; SunOS i86pc; en-US; rv:1.9.1.9) Gecko/20100525 Firefox/3.5.9';
		$infoArray = \TYPO3\CMS\Core\Utility\ClientUtility::getBrowserInfo($userAgentString);
		$this->assertContains('unix_sun', $infoArray['all_systems']);
	}

	/**
	 * Provide various user agent strings for android devices
	 *
	 * @static
	 * @return array List of user agents
	 */
	static public function androidUserAgentsProvider() {
		$agents = array(
			'defaultBrowser' => array(
				'agent' => 'Mozilla/5.0 (Linux; U; Android 2.3; en-US; sdk Build/GRH55) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1'
			),
			'operaMini' => array(
				'agent' => 'Opera/9.80 (Android; Opera Mini/6.0.24556/24.816; U; en) Presto/2.5.25 Version/10.54'
			)
		);
		return $agents;
	}

	/**
	 * @test
	 * @dataProvider androidUserAgentsProvider
	 */
	public function getBrowserInfoReturnsCorrectSystemValueForUserAgentStringOfAndroid($userAgentString) {
		$infoArray = \TYPO3\CMS\Core\Utility\ClientUtility::getBrowserInfo($userAgentString);
		$this->assertContains('android', $infoArray['all_systems']);
	}

	/**
	 * @test
	 */
	public function getBrowserInfoReturnsCorrectSystemValueForUserAgentStringOfOpenbsd() {
		$userAgentString = 'Links (1.00pre20; OpenBSD 4.8 i386; 80x25)';
		$infoArray = \TYPO3\CMS\Core\Utility\ClientUtility::getBrowserInfo($userAgentString);
		$this->assertContains('unix_bsd', $infoArray['all_systems']);
	}

	/**
	 * @test
	 */
	public function getBrowserInfoReturnsCorrectSystemValueForUserAgentStringOfNetbsd() {
		$userAgentString = 'Links (2.2; NetBSD 5.1 amd64; 80x25)';
		$infoArray = \TYPO3\CMS\Core\Utility\ClientUtility::getBrowserInfo($userAgentString);
		$this->assertContains('unix_bsd', $infoArray['all_systems']);
	}

	/**
	 * @test
	 */
	public function getBrowserInfoReturnsCorrectSystemValueForUserAgentStringOfFreebsd() {
		$userAgentString = 'Mozilla/5.0 (X11; U; FreeBSD amd64; c) AppleWebKit/531.2+ (KHTML, like Gecko) Safari 531.2+ Epiphany/230.2';
		$infoArray = \TYPO3\CMS\Core\Utility\ClientUtility::getBrowserInfo($userAgentString);
		$this->assertContains('unix_bsd', $infoArray['all_systems']);
	}

	/**
	 * @test
	 */
	public function getBrowserInfoReturnsCorrectSystemValueForUserAgentStringOfChromeOs() {
		$userAgentString = 'Mozilla/5.0 (X11; U; CrOS i686  9.10.0; en-US) AppleWebKit/532.5 (KHTML, like Gecko) Chrome/4.0.253.0 Safari 532.5';
		$infoArray = \TYPO3\CMS\Core\Utility\ClientUtility::getBrowserInfo($userAgentString);
		$this->assertContains('chrome', $infoArray['all_systems']);
	}

	/**
	 * @test
	 */
	public function getBrowserInfoReturnsCorrectBrowserValueForUserAgentStringOfSafari() {
		$userAgentString = 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6; en-us) AppleWebKit/531.9 (KHTML, like Gecko) Version/4.0.3 Safari/531.9';
		$infoArray = \TYPO3\CMS\Core\Utility\ClientUtility::getBrowserInfo($userAgentString);
		$this->assertSame('safari', $infoArray['browser']);
	}

	/**
	 * @test
	 */
	public function getBrowserInfoReturnsCorrectBrowserValueForUserAgentStringOfFirefox() {
		$userAgentString = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.6; rv:2.0b8) Gecko/20100101 Firefox/4.0b8';
		$infoArray = \TYPO3\CMS\Core\Utility\ClientUtility::getBrowserInfo($userAgentString);
		$this->assertSame('firefox', $infoArray['browser']);
	}

	/**
	 * @test
	 */
	public function getBrowserInfoReturnsCorrectBrowserValueForUserAgentStringOfOpera() {
		$userAgentString = 'Opera/9.80 (X11; FreeBSD 8.1-RELEASE amd64; U; en) Presto/2.2.15 Version/10.10';
		$infoArray = \TYPO3\CMS\Core\Utility\ClientUtility::getBrowserInfo($userAgentString);
		$this->assertSame('opera', $infoArray['browser']);
	}

	/**
	 * @test
	 */
	public function getBrowserInfoReturnsCorrectBrowserValueForUserAgentStringOfMobileSafariOnAndroid() {
		$userAgentString = 'Mozilla/5.0 (Linux; U; Android WildPuzzleROM v8.0.7 froyo 2.2; de-de; HTC Wildfire Build/FRF91) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1';
		$infoArray = \TYPO3\CMS\Core\Utility\ClientUtility::getBrowserInfo($userAgentString);
		$this->assertSame('safari', $infoArray['browser']);
	}

	/**
	 * @test
	 */
	public function getBrowserInfoReturnsCorrectBrowserValueForUserAgentStringOfMobileSafariOnIphone() {
		$userAgentString = 'Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_2_1 like Mac OS X; en-us) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8C148 Safari/6533.18.5';
		$infoArray = \TYPO3\CMS\Core\Utility\ClientUtility::getBrowserInfo($userAgentString);
		$this->assertSame('safari', $infoArray['browser']);
	}

	/**
	 * @test
	 */
	public function getBrowserInfoReturnsCorrectBrowserValueForUserAgentStringOfKonqueror() {
		$userAgentString = 'Mozilla/5.0 (compatible; Konqueror/4.4; FreeBSD) KHTML/4.4.5 (like Gecko)';
		$infoArray = \TYPO3\CMS\Core\Utility\ClientUtility::getBrowserInfo($userAgentString);
		$this->assertSame('konqueror', $infoArray['browser']);
	}

}

?>