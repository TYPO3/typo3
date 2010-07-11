<?php
/***************************************************************
* Copyright notice
*
* (c) 2010 Steffen Kamper (info@sk-typo3.de)
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
 * Testcase for the t3lib_utility_Client class.
 *
 * @package TYPO3
 * @subpackage t3lib
 *
 * @author Steffen Kamper <info@sk-typo3.de>
 */
class t3lib_utility_clientTest extends tx_phpunit_testcase {
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
		$compare = $expected = array();
		foreach ($browserStrings as $browserString) {
			$infoArray = t3lib_utility_Client::getBrowserInfo($browserString);
			$expected[] = $expectedMembers;
			$compare[] = array(
				'browser' => $infoArray['browser'],
				'version' => substr($infoArray['version'], 0, 1)
			);
		}
		$this->assertEquals(
		 	$expected,
		 	$compare
		);
	}


	//////////////////////////////////////////////////////////
	// Tests concerning t3lib_utility_Client::getBrowserInfo
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
			'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0; Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1) ; SLCC1; .NET CLR 2.0.50727; .NET CLR 1.1.4322; .NET CLR 3.5.30729; .NET CLR 3.0.30729)',
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
		$infoArray = t3lib_utility_Client::getBrowserInfo($userAgentString);

		$this->assertEquals(
		 	'1.9.2.3',
		 	$infoArray['all']['gecko']
		);
	}
}
?>