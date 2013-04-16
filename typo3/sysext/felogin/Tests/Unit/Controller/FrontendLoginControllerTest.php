<?php
namespace TYPO3\CMS\Felogin\Tests\Unit\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Helmut Hummel <helmut@typo3.org>
 *
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
 *
 *  The code was adapted from newloginbox, see manual for detailed description
 ***************************************************************/

/**
 * Testcase for URL validation in class FrontendLoginController
 *
 * @author Helmut Hummel <helmut@typo3.org>
 */
class FrontendLoginTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Felogin\Controller\FrontendLoginController|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected $accessibleFixture;

	/**
	 * @var string
	 */
	protected $testHostName;

	/**
	 * @var string
	 */
	protected $testSitePath;

	/**
	 * @var string
	 */
	private $testTableName;

	/**
	 * Set up
	 */
	public function setUp() {
		$this->testTableName = 'sys_domain';
		$this->testHostName = 'hostname.tld';
		$this->testSitePath = '/';
		$this->accessibleFixture = $this->getAccessibleMock('TYPO3\\CMS\\Felogin\\Controller\\FrontendLoginController', array('dummy'));
		$this->accessibleFixture->cObj = $this->getMock('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
		$GLOBALS['TSFE'] = $this->getMock('TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController', array(), array(), '', FALSE);
		$this->setUpFakeSitePathAndHost();
	}

	/**
	 * Tear down
	 */
	public function tearDown() {
		$this->accessibleFixture = NULL;
	}

	/**
	 * Set up a fake site path and host
	 */
	protected function setUpFakeSitePathAndHost() {
		$_SERVER['ORIG_PATH_INFO'] = $_SERVER['PATH_INFO'] = $_SERVER['ORIG_SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] = $this->testSitePath . TYPO3_mainDir;
		$_SERVER['HTTP_HOST'] = $this->testHostName;
	}

	/**
	 * Mock database
	 */
	protected function setUpDatabaseMock() {
		$GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array('exec_SELECTgetRows'));
		$GLOBALS['TYPO3_DB']
			->expects($this->any())
			->method('exec_SELECTgetRows')
			->will($this->returnCallback(array($this, 'getDomainRecordsCallback')));
	}

	/**
	 * Callback method for pageIdCanBeDetermined test cases.
	 * Simulates TYPO3_DB->exec_SELECTgetRows().
	 *
	 * @param string $fields
	 * @param string $table
	 * @param string $where
	 * @return mixed
	 * @see setUpDatabaseMock
	 */
	public function getDomainRecordsCallback($fields, $table, $where) {
		if ($table !== $this->testTableName) {
			return FALSE;
		}
		return array(
			array('domainName' => 'domainhostname.tld'),
			array('domainName' => 'otherhostname.tld/path'),
			array('domainName' => 'sub.domainhostname.tld/path/')
		);
	}

	/**
	 * @test
	 */
	public function typo3SitePathEqualsStubSitePath() {
		$this->assertEquals(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_PATH'), $this->testSitePath);
	}

	/**
	 * @test
	 */
	public function typo3SiteUrlEqualsStubSiteUrl() {
		$this->assertEquals(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL'), ('http://' . $this->testHostName) . $this->testSitePath);
	}

	/**
	 * @test
	 */
	public function typo3SitePathEqualsStubSitePathAfterChangingInTest() {
		$this->testHostName = 'somenewhostname.com';
		$this->testSitePath = '/somenewpath/';
		$this->setUpFakeSitePathAndHost();
		$this->assertEquals(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_PATH'), $this->testSitePath);
	}

	/**
	 * @test
	 */
	public function typo3SiteUrlEqualsStubSiteUrlAfterChangingInTest() {
		$this->testHostName = 'somenewhostname.com';
		$this->testSitePath = '/somenewpath/';
		$this->setUpFakeSitePathAndHost();
		$this->assertEquals(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL'), ('http://' . $this->testHostName) . $this->testSitePath);
	}

	/**
	 * Data provider for validateRedirectUrlClearsUrl
	 *
	 * @return array
	 */
	public function validateRedirectUrlClearsUrlDataProvider() {
		return array(
			'absolute URL, hostname not in sys_domain, trailing slash' => array('http://badhost.tld/'),
			'absolute URL, hostname not in sys_domain, no trailing slash' => array('http://badhost.tld'),
			'absolute URL, subdomain in sys_domain, but main domain not, trailing slash' => array('http://domainhostname.tld.badhost.tld/'),
			'absolute URL, subdomain in sys_domain, but main domain not, no trailing slash' => array('http://domainhostname.tld.badhost.tld'),
			'non http absolute URL 1' => array('its://domainhostname.tld/itunes/'),
			'non http absolute URL 2' => array('ftp://domainhostname.tld/download/'),
			'XSS attempt 1' => array('javascript:alert(123)'),
			'XSS attempt 2' => array('" onmouseover="alert(123)"'),
			'invalid URL, HTML break out attempt' => array('" >blabuubb'),
			'invalid URL, UNC path' => array('\\\\foo\\bar\\'),
			'invalid URL, backslashes in path' => array('http://domainhostname.tld\\bla\\blupp'),
			'invalid URL, linefeed in path' => array('http://domainhostname.tld/bla/blupp' . LF),
			'invalid URL, only one slash after scheme' => array('http:/domainhostname.tld/bla/blupp'),
			'invalid URL, illegal chars' => array('http://(<>domainhostname).tld/bla/blupp'),
		);
	}

	/**
	 * @test
	 * @dataProvider validateRedirectUrlClearsUrlDataProvider
	 * @param string $url Invalid Url
	 */
	public function validateRedirectUrlClearsUrl($url) {
		$this->setUpDatabaseMock();
		$this->assertEquals('', $this->accessibleFixture->_call('validateRedirectUrl', $url));
	}

	/**
	 * Data provider for validateRedirectUrlKeepsCleanUrl
	 *
	 * @return array
	 */
	public function validateRedirectUrlKeepsCleanUrlDataProvider() {
		return array(
			'sane absolute URL' => array('http://domainhostname.tld/'),
			'sane absolute URL with script' => array('http://domainhostname.tld/index.php?id=1'),
			'sane absolute URL with realurl' => array('http://domainhostname.tld/foo/bar/foo.html'),
			'sane absolute URL with homedir' => array('http://domainhostname.tld/~user/'),
			'sane absolute URL with some strange chars encoded' => array('http://domainhostname.tld/~user/a%cc%88o%cc%88%c3%9fa%cc%82/foo.html'),
			'sane absolute URL (domain record with path)' => array('http://otherhostname.tld/path/'),
			'sane absolute URL with script (domain record with path)' => array('http://otherhostname.tld/path/index.php?id=1'),
			'sane absolute URL with realurl (domain record with path)' => array('http://otherhostname.tld/path/foo/bar/foo.html'),
			'sane absolute URL (domain record with path and slash)' => array('http://sub.domainhostname.tld/path/'),
			'sane absolute URL with script (domain record with path slash)' => array('http://sub.domainhostname.tld/path/index.php?id=1'),
			'sane absolute URL with realurl (domain record with path slash)' => array('http://sub.domainhostname.tld/path/foo/bar/foo.html'),
			'relative URL, no leading slash 1' => array('index.php?id=1'),
			'relative URL, no leading slash 2' => array('foo/bar/index.php?id=2'),
			'relative URL, leading slash, no realurl' => array('/index.php?id=1'),
			'relative URL, leading slash, realurl' => array('/de/service/imprint.html'),
		);
	}

	/**
	 * @test
	 * @dataProvider validateRedirectUrlKeepsCleanUrlDataProvider
	 * @param string $url Clean URL to test
	 */
	public function validateRedirectUrlKeepsCleanUrl($url) {
		$this->setUpDatabaseMock();
		$this->assertEquals($url, $this->accessibleFixture->_call('validateRedirectUrl', $url));
	}

	/**
	 * Data provider for validateRedirectUrlClearsInvalidUrlInSubdirectory
	 *
	 * @return array
	 */
	public function validateRedirectUrlClearsInvalidUrlInSubdirectoryDataProvider() {
		return array(
			'absolute URL, missing subdirectory' => array('http://hostname.tld/'),
			'absolute URL, wrong subdirectory' => array('http://hostname.tld/hacker/index.php'),
			'absolute URL, correct subdirectory, no trailing slash' => array('http://hostname.tld/subdir'),
			'absolute URL, correct subdirectory of sys_domain record, no trailing slash' => array('http://otherhostname.tld/path'),
			'absolute URL, correct subdirectory of sys_domain record, no trailing slash, subdomain' => array('http://sub.domainhostname.tld/path'),
			'relative URL, leading slash, no path' => array('/index.php?id=1'),
			'relative URL, leading slash, wrong path' => array('/de/sub/site.html'),
			'relative URL, leading slash, slash only' => array('/'),
		);
	}

	/**
	 * @test
	 * @dataProvider validateRedirectUrlClearsInvalidUrlInSubdirectoryDataProvider
	 * @param string $url Invalid Url
	 */
	public function validateRedirectUrlClearsInvalidUrlInSubdirectory($url) {
		$this->testSitePath = '/subdir/';
		$this->setUpFakeSitePathAndHost();
		$this->setUpDatabaseMock();
		$this->assertEquals('', $this->accessibleFixture->_call('validateRedirectUrl', $url));
	}

	/**
	 * Data provider for validateRedirectUrlKeepsCleanUrlInSubdirectory
	 *
	 * @return array
	 */
	public function validateRedirectUrlKeepsCleanUrlInSubdirectoryDataProvider() {
		return array(
			'absolute URL, correct subdirectory' => array('http://hostname.tld/subdir/'),
			'absolute URL, correct subdirectory, realurl' => array('http://hostname.tld/subdir/de/imprint.html'),
			'absolute URL, correct subdirectory, no realurl' => array('http://hostname.tld/subdir/index.php?id=10'),
			'absolute URL, correct subdirectory of sys_domain record' => array('http://otherhostname.tld/path/'),
			'absolute URL, correct subdirectory of sys_domain record, subdomain' => array('http://sub.domainhostname.tld/path/'),
			'relative URL, no leading slash, realurl' => array('de/service/imprint.html'),
			'relative URL, no leading slash, no realurl' => array('index.php?id=1'),
			'relative nested URL, no leading slash, no realurl' => array('foo/bar/index.php?id=2')
		);
	}

	/**
	 * @test
	 * @dataProvider validateRedirectUrlKeepsCleanUrlInSubdirectoryDataProvider
	 * @param string $url Invalid Url
	 */
	public function validateRedirectUrlKeepsCleanUrlInSubdirectory($url) {
		$this->testSitePath = '/subdir/';
		$this->setUpFakeSitePathAndHost();
		$this->setUpDatabaseMock();
		$this->assertEquals($url, $this->accessibleFixture->_call('validateRedirectUrl', $url));
	}


	/*************************
	 * Test concerning getPreverveGetVars
	 *************************/

	/**
	 * @return array
	 */
	public function getPreserveGetVarsReturnsCorrectResultDataProvider() {
		return array(
			'special get var id is not preserved' => array(
				array(
					'id' => 42,
				),
				'',
				'',
			),
			'simple additional parameter is not preserved if not specified in preservedGETvars' => array(
				array(
					'id' => 42,
					'special' => 23,
				),
				'',
				'',
			),
			'all params except ignored ones are preserved if preservedGETvars is set to "all"' => array(
				array(
					'id' => 42,
					'special1' => 23,
					'special2' => array(
						'foo' => 'bar',
					),
					'tx_felogin_pi1' => array(
						'forgot' => 1,
					),
				),
				'all',
				'&special1=23&special2[foo]=bar',
			),
			'preserve single parameter' => array(
				array(
					'L' => 42,
				),
				'L',
				'&L=42'
			),
			'preserve whole parameter array' => array(
				array(
					'L' => 3,
					'tx_someext' => array(
						'foo' => 'simple',
						'bar' => array(
							'baz' => 'simple',
						),
					),
				),
				'L,tx_someext',
				'&L=3&tx_someext[foo]=simple&tx_someext[bar][baz]=simple',
			),
			'preserve part of sub array' => array(
				array(
					'L' => 3,
					'tx_someext' => array(
						'foo' => 'simple',
						'bar' => array(
							'baz' => 'simple',
						),
					),
				),
				'L,tx_someext[bar]',
				'&L=3&tx_someext[bar][baz]=simple',
			),
			'preserve keys on different levels' => array(
				array(
					'L' => 3,
					'no-preserve' => 'whatever',
					'tx_ext2' => array(
						'foo' => 'simple',
					),
					'tx_ext3' => array(
						'bar' => array(
							'baz' => 'simple',
						),
						'go-away' => '',
					),
				),
				'L,tx_ext2,tx_ext3[bar]',
				'&L=3&tx_ext2[foo]=simple&tx_ext3[bar][baz]=simple',
			),
			'preserved value that does not exist in get' => array(
				array(),
				'L,foo[bar]',
				''
			),
			'url params are encoded' => array(
				array('tx_ext1' => 'param with spaces and \\ %<>& /'),
				'L,tx_ext1',
				'&tx_ext1=param%20with%20spaces%20and%20%20%25%3C%3E%26%20%2F'
			),
		);
	}

	/**
	 * @test
	 * @dataProvider getPreserveGetVarsReturnsCorrectResultDataProvider
	 * @param array $getArray
	 * @param string $preserveVars
	 * @param string $expected
	 * @return void
	 */
	public function getPreserveGetVarsReturnsCorrectResult(array $getArray, $preserveVars, $expected) {
		$_GET = $getArray;
		$this->accessibleFixture->conf['preserveGETvars'] = $preserveVars;
		$this->assertSame($expected, $this->accessibleFixture->_call('getPreserveGetVars'));
	}
}

?>