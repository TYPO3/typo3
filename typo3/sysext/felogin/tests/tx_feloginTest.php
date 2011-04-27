<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2011 Helmut Hummel <helmut@typo3.org>
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


require_once t3lib_extMgm::extPath('felogin', 'pi1/class.tx_felogin_pi1.php');

/**
 * Testcase for URL validation in class tx_felogin_pi1
 *
 * @author Helmut Hummel <helmut@typo3.org>
 * @package TYPO3
 * @subpackage tests/typo3/sysext/felogin
 */
class tx_feloginTest extends tx_phpunit_testcase {

	/**
	 * @var	array
	 */
	private $backupGlobalVariables;

	/**
	 * @var	tx_felogin_pi1
	 */
	private $txFelogin;

	/**
	 * @var	string
	 */
	private $testHostName;

	/**
	 * @var	string
	 */
	private $testSitePath;

	/**
	 * @var	string
	 */
	private $testTableName;

	public function setUp() {
		$this->backupGlobalVariables = array(
			'_SERVER' => $_SERVER,
			'TYPO3_DB' => $GLOBALS['TYPO3_DB'],
			'TSFE' => $GLOBALS['TSFE'],
		);

		$this->testTableName = 'sys_domain';
		$this->testHostName = 'hostname.tld';
		$this->testSitePath = '/';

		// we need to subclass because the method we want to test is protected
		$className = uniqid('FeLogin_');
		eval('
			class ' . $className. ' extends tx_felogin_pi1 {
				public function validateRedirectUrl($url) {
					return parent::validateRedirectUrl($url);
				}
			}
		');

		$this->txFelogin = new $className();
		$this->txFelogin->cObj = $this->getMock('tslib_cObj');

		$this->setUpTSFE();
		$this->setUpFakeSitePathAndHost();
	}

	private function setUpTSFE() {
		$GLOBALS['TSFE'] = $this->getMock('tslib_fe', array(), array(), '', FALSE);
	}

	private function setUpFakeSitePathAndHost() {
		$_SERVER['ORIG_PATH_INFO'] =
		$_SERVER['PATH_INFO'] =
		$_SERVER['ORIG_SCRIPT_NAME'] =
		$_SERVER['SCRIPT_NAME'] = $this->testSitePath . TYPO3_mainDir;
		$_SERVER['HTTP_HOST'] = $this->testHostName;
	}

	private function setUpDatabaseMock() {
		$GLOBALS['TYPO3_DB'] = $this->getMock('t3lib_DB', array('exec_SELECTgetRows'));
		$GLOBALS['TYPO3_DB']->expects($this->any())->method('exec_SELECTgetRows')->will(
			$this->returnCallback(array($this, 'getDomainRecordsCallback'))
		);
	}

	/**
	 * Callback method for pageIdCanBeDetermined test cases.
	 * Simulates TYPO3_DB->exec_SELECTgetRows().
	 *
	 * @param	string		$fields
	 * @param	string		$table
	 * @param	string		$where
	 * @return	mixed
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

	public function tearDown() {
		$this->txFelogin = NULL;

		foreach ($this->backupGlobalVariables as $key => $data) {
			$GLOBALS[$key] = $data;
		}

		$this->backupGlobalVariables = NULL;

	}

	/**
	 * @test
	 */
	public function typo3SitePathEqualsStubSitePath() {
		$this->assertEquals(t3lib_div::getIndpEnv('TYPO3_SITE_PATH'), $this->testSitePath);
	}

	/**
	 * @test
	 */
	public function typo3SiteUrlEqualsStubSiteUrl() {
		$this->assertEquals(t3lib_div::getIndpEnv('TYPO3_SITE_URL'), 'http://' . $this->testHostName . $this->testSitePath);
	}

	/**
	 * @test
	 */
	public function typo3SitePathEqualsStubSitePathAfterChangingInTest() {
		$this->testHostName = 'somenewhostname.com';
		$this->testSitePath = '/somenewpath/';
		$this->setUpFakeSitePathAndHost();

		$this->assertEquals(t3lib_div::getIndpEnv('TYPO3_SITE_PATH'), $this->testSitePath);
	}

	/**
	 * @test
	 */
	public function typo3SiteUrlEqualsStubSiteUrlAfterChangingInTest() {
		$this->testHostName = 'somenewhostname.com';
		$this->testSitePath = '/somenewpath/';
		$this->setUpFakeSitePathAndHost();

		$this->assertEquals(t3lib_div::getIndpEnv('TYPO3_SITE_URL'), 'http://' . $this->testHostName . $this->testSitePath);
	}

	/**
	 * Data provider for malicousUrlsWillBeCleared
	 *
	 * @see malicousUrlsWillBeCleared
	 */
	public function variousUrlsDataProviderForMalicousUrlsWillBeCleared() {
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
			'invalid URL, linefeed in path' => array("http://domainhostname.tld/bla/blupp\n"),
			'invalid URL, only one slash after scheme' => array("http:/domainhostname.tld/bla/blupp"),
			'invalid URL, illegal chars' => array("http://(<>domainhostname).tld/bla/blupp"),

		);
	}

	/**
	 * @test
	 * @dataProvider variousUrlsDataProviderForMalicousUrlsWillBeCleared
	 */
	public function malicousUrlsWillBeCleared($url) {
		$this->setUpDatabaseMock();
		$this->assertEquals('', $this->txFelogin->validateRedirectUrl($url));
	}

	/**
	 * Data provider for cleanUrlsAreKept
	 *
	 * @see cleanUrlsAreKept
	 */
	public function variousUrlsDataProviderForCleanUrlsAreKept() {
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
	 * @dataProvider variousUrlsDataProviderForCleanUrlsAreKept
	 */
	public function cleanUrlsAreKept($url) {
		$this->setUpDatabaseMock();
		$this->assertEquals($url, $this->txFelogin->validateRedirectUrl($url));
	}

	/**
	 * Data provider for malicousUrlsWillBeClearedTypo3InSubdirectory
	 *
	 * @see malicousUrlsWillBeClearedTypo3InSubdirectory
	 */
	public function variousUrlsDataProviderForMalicousUrlsWillBeClearedTypo3InSubdirectory() {
		return array(
			'absolute URL, missing subdirectory' => array('http://hostname.tld/'),
			'absolute URL, wrong subdirectory' => array('http://hostname.tld/hacker/index.php'),
			'absolute URL, correct subdirectory, no trailing slash' => array('http://hostname.tld/subdir'),
			'absolute URL, correct subdirectory of sys_domain record, no trailing slash' => array('http://otherhostname.tld/path'),
			'absolute URL, correct subdirectory of sys_domain record, no trailing slash' => array('http://sub.domainhostname.tld/path'),

			'relative URL, leading slash, no path' => array('/index.php?id=1'),
			'relative URL, leading slash, wrong path' => array('/de/sub/site.html'),
			'relative URL, leading slash, slash only' => array('/'),
		);
	}

	/**
	 * @test
	 * @dataProvider variousUrlsDataProviderForMalicousUrlsWillBeClearedTypo3InSubdirectory
	 */
	public function malicousUrlsWillBeClearedTypo3InSubdirectory($url) {
		$this->testSitePath = '/subdir/';
		$this->setUpFakeSitePathAndHost();

		$this->setUpDatabaseMock();
		$this->assertEquals('', $this->txFelogin->validateRedirectUrl($url));
	}

	/**
	 * Data provider for cleanUrlsAreKeptTypo3InSubdirectory
	 *
	 * @see cleanUrlsAreKeptTypo3InSubdirectory
	 */
	public function variousUrlsDataProviderForCleanUrlsAreKeptTypo3InSubdirectory() {
		return array(
			'absolute URL, correct subdirectory' => array('http://hostname.tld/subdir/'),
			'absolute URL, correct subdirectory, realurl' => array('http://hostname.tld/subdir/de/imprint.html'),
			'absolute URL, correct subdirectory, no realurl' => array('http://hostname.tld/subdir/index.php?id=10'),

			'absolute URL, correct subdirectory of sys_domain record' => array('http://otherhostname.tld/path/'),
			'absolute URL, correct subdirectory of sys_domain record' => array('http://sub.domainhostname.tld/path/'),

			'relative URL, no leading slash, realurl' => array('de/service/imprint.html'),
			'relative URL, no leading slash, no realurl' => array('index.php?id=1'),
			'relative URL, no leading slash, no realurl' => array('foo/bar/index.php?id=2'),
		);
	}

	/**
	 * @test
	 * @dataProvider variousUrlsDataProviderForCleanUrlsAreKeptTypo3InSubdirectory
	 */
	public function cleanUrlsAreKeptTypo3InSubdirectory($url) {
		$this->testSitePath = '/subdir/';
		$this->setUpFakeSitePathAndHost();

		$this->setUpDatabaseMock();
		$this->assertEquals($url, $this->txFelogin->validateRedirectUrl($url));
	}



}
?>