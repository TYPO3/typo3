<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\Controller;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Testcase for TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class TypoScriptFrontendControllerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
	 */
	protected $fixture;

	public function setUp() {
		$this->fixture = $this->getAccessibleMock('\\TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController', array('dummy'), array(), '', FALSE);
		$this->fixture->TYPO3_CONF_VARS = $GLOBALS['TYPO3_CONF_VARS'];
		$this->fixture->TYPO3_CONF_VARS['SYS']['encryptionKey'] = '170928423746123078941623042360abceb12341234231';

		$pageRepository = $this->getMock('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
		$this->fixture->sys_page = $pageRepository;
	}

	////////////////////////////////
	// Tests concerning rendering content
	////////////////////////////////
	/**
	 * @test
	 */
	public function headerAndFooterMarkersAreReplacedDuringIntProcessing() {
		$GLOBALS['TSFE'] = $this->setupTsfeMockForHeaderFooterReplacementCheck();
		$GLOBALS['TSFE']->INTincScript();
		$this->assertContains('headerData', $GLOBALS['TSFE']->content);
		$this->assertContains('footerData', $GLOBALS['TSFE']->content);
	}

	/**
	 * This is the callback that mimics a USER_INT extension
	 */
	public function INTincScript_processCallback() {
		$GLOBALS['TSFE']->additionalHeaderData[] = 'headerData';
		$GLOBALS['TSFE']->additionalFooterData[] = 'footerData';
	}

	/**
	 * Setup a \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController object only for testing the header and footer
	 * replacement during USER_INT rendering
	 *
	 * @return \PHPUnit_Framework_MockObject_MockObject
	 */
	protected function setupTsfeMockForHeaderFooterReplacementCheck() {
		/** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $tsfe */
		$tsfe = $this->getMock('TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController', array(
			'INTincScript_process',
			'INTincScript_includeLibs',
			'INTincScript_loadJSCode',
			'setAbsRefPrefix',
		    'regeneratePageTitle'
		), array(), '', FALSE);
		$tsfe->expects($this->exactly(2))->method('INTincScript_process')->will($this->returnCallback(array($this, 'INTincScript_processCallback')));
		$tsfe->content = file_get_contents(__DIR__ . '/Fixtures/renderedPage.html');
		$tsfe->config['INTincScript_ext']['divKey'] = '679b52796e75d474ccbbed486b6837ab';
		$tsfe->config['INTincScript'] = array('INT_SCRIPT.679b52796e75d474ccbbed486b6837ab' => array());
		$GLOBALS['TT'] = new \TYPO3\CMS\Core\TimeTracker\NullTimeTracker();
		return $tsfe;
	}

	////////////////////////////////
	// Tests concerning codeString
	////////////////////////////////
	/**
	 * @test
	 */
	public function codeStringForNonEmptyStringReturns10CharacterHashAndCodedString() {
		$this->assertRegExp('/^[0-9a-f]{10}:[a-zA-Z0-9+=\\/]+$/', $this->fixture->codeString('Hello world!'));
	}

	/**
	 * @test
	 */
	public function decodingCodedStringReturnsOriginalString() {
		$clearText = 'Hello world!';
		$this->assertEquals($clearText, $this->fixture->codeString($this->fixture->codeString($clearText), TRUE));
	}

	//////////////////////
	// Tests concerning sL
	//////////////////////
	/**
	 * @test
	 */
	public function localizationReturnsUnchangedStringIfNotLocallangLabel() {
		$string = $this->getUniqueId();
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
		$this->assertEquals(strlen($clearText), strlen($this->fixture->_callRef('roundTripCryptString', $clearText)));
	}

	/**
	 * @test
	 */
	public function roundTripCryptStringCreatesResultDifferentFromInputString() {
		$clearText = 'Hello world!';
		$this->assertNotEquals($clearText, $this->fixture->_callRef('roundTripCryptString', $clearText));
	}

	/**
	 * @test
	 */
	public function roundTripCryptStringAppliedTwoTimesReturnsOriginalString() {
		$clearText = 'Hello world!';
		$refValue = $this->fixture->_callRef('roundTripCryptString', $clearText);
		$this->assertEquals($clearText, $this->fixture->_callRef('roundTripCryptString', $refValue));
	}

	/**
	 * Tests concerning getSysDomainCache
	 */

	/**
	 * @return array
	 */
	public function getSysDomainCacheDataProvider() {
		return array(
			'typo3.org' => array(
				'typo3.org',
			),
			'foo.bar' => array(
				'foo.bar',
			),
			'example.com' => array(
				'example.com',
			),
		);
	}

	/**
	 * @param string $currentDomain
	 * @test
	 * @dataProvider getSysDomainCacheDataProvider
	 */
	public function getSysDomainCacheReturnsCurrentDomainRecord($currentDomain) {
		$_SERVER['HTTP_HOST'] = $currentDomain;
		$domainRecords = array(
			'typo3.org' => array(
				'pid' => '1',
				'domainName' => 'typo3.org',
				'forced' => 0,
			),
			'foo.bar' => array(
				'pid' => '1',
				'domainName' => 'foo.bar',
				'forced' => 0,
			),
			'example.com' => array(
				'pid' => '1',
				'domainName' => 'example.com',
				'forced' => 0,
			),
		);
		$GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array('exec_SELECTgetRows'));
		$GLOBALS['TYPO3_DB']->expects($this->any())->method('exec_SELECTgetRows')->willReturn($domainRecords);
		GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Cache\\CacheManager')->getCache('cache_runtime')->flush();
		$expectedResult = array(
			$domainRecords[$currentDomain]['pid'] => $domainRecords[$currentDomain],
		);
		$this->assertEquals($expectedResult, $this->fixture->_call('getSysDomainCache'));
	}

	/**
	 * @param string $currentDomain
	 * @test
	 * @dataProvider getSysDomainCacheDataProvider
	 */
	public function getSysDomainCacheReturnsForcedDomainRecord($currentDomain) {
		$_SERVER['HTTP_HOST'] = $currentDomain;
		$domainRecords = array(
			'typo3.org' => array(
				'pid' => '1',
				'domainName' => 'typo3.org',
				'forced' => 0,
			),
			'foo.bar' => array(
				'pid' => '1',
				'domainName' => 'foo.bar',
				'forced' => 1,
			),
			'example.com' => array(
				'pid' => '1',
				'domainName' => 'example.com',
				'forced' => 0,
			),
		);
		$GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array('exec_SELECTgetRows'));
		$GLOBALS['TYPO3_DB']->expects($this->any())->method('exec_SELECTgetRows')->willReturn($domainRecords);
		GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Cache\\CacheManager')->getCache('cache_runtime')->flush();
		$expectedResult = array(
			$domainRecords[$currentDomain]['pid'] => $domainRecords['foo.bar'],
		);
		$this->assertEquals($expectedResult, $this->fixture->_call('getSysDomainCache'));
	}

	/**
	 * Tests concerning domainNameMatchesCurrentRequest
	 */

	/**
	 * @return array
	 */
	public function domainNameMatchesCurrentRequestDataProvider() {
		return array(
			'same domains' => array(
				'typo3.org',
				'typo3.org',
				'/index.php',
				TRUE,
			),
			'same domains with subdomain' => array(
				'www.typo3.org',
				'www.typo3.org',
				'/index.php',
				TRUE,
			),
			'different domains' => array(
				'foo.bar',
				'typo3.org',
				'/index.php',
				FALSE,
			),
			'domain record with script name' => array(
				'typo3.org',
				'typo3.org/foo/bar',
				'/foo/bar/index.php',
				TRUE,
			),
			'domain record with wrong script name' => array(
				'typo3.org',
				'typo3.org/foo/bar',
				'/bar/foo/index.php',
				FALSE,
			),
		);
	}

	/**
	 * @param string $currentDomain
	 * @param string $domainRecord
	 * @param string $scriptName
	 * @param bool $expectedResult
	 * @test
	 * @dataProvider domainNameMatchesCurrentRequestDataProvider
	 */
	public function domainNameMatchesCurrentRequest($currentDomain, $domainRecord, $scriptName, $expectedResult) {
		$_SERVER['HTTP_HOST'] = $currentDomain;
		$_SERVER['SCRIPT_NAME'] = $scriptName;
		$this->assertEquals($expectedResult, $this->fixture->domainNameMatchesCurrentRequest($domainRecord));
	}

}
