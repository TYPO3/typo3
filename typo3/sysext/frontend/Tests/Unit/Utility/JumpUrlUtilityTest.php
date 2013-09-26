<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Alexander Stehlik <alexander.stehlik (at) gmail.com>
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
 * Testcase for \TYPO3\CMS\Frontend\Utility\JumpUrlUtility
 *
 * @author Alexander Stehlik <alexander.stehlik (at) gmail.com>
 */
class JumpUrlUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * Used to store the current encryption key
	 *
	 * @var string
	 */
	protected $encryptionKeyBackup;

	/**
	 * @var array
	 */
	protected $getVariablesBackup;

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Frontend\Utility\JumpUrlUtility
	 */
	protected $jumpUrlUtility;

	/**
	 * @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
	 */
	protected $tsfe;

	public function setUp() {

		$this->getVariablesBackup = $_GET;
		$_GET = array();

		$this->encryptionKeyBackup = $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '12345';

		$this->tsfe = $this->getAccessibleMock('TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController', array('getPagesTSconfig', 'locDataCheck'), array(), '', FALSE);
		$GLOBALS['TSFE'] = $this->tsfe;
		$this->jumpUrlUtility = $this->getAccessibleMock('TYPO3\\CMS\\Frontend\\Utility\\JumpUrlUtility', array('redirect', 'readFileAndExit'));
	}

	public function tearDown() {
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = $this->encryptionKeyBackup;
		$_GET = $this->getVariablesBackup;
	}

	/**
	 * Provides a valid jump URL hash and a target URL
	 *
	 * @return array
	 */
	public function validJumpUrlDataProvider() {

		return array(
			array(
				'691dbf63a21181e2d69bf78e61f1c9fd023aef2c',
				str_replace('%2F', '/', rawurlencode('typo3temp/phpunitJumpUrlTestFile with spaces & amps.txt')),
			),
			array(
				'7d2261b12682a4b73402ae67415e09f294b29a55',
				'http://www.mytesturl.tld',
			),
			array(
				'cfc95f583da7689238e98bbc8930ebd820f0d20f',
				'http://external.domain.tld?parameter1=' . rawurlencode('parameter[data]with&a lot-of-special/chars'),
			),
			array(
				'8591c573601d17f37e06aff4ac14c78f107dd49e',
				'http://external.domain.tld',
			),
			array(
				'bd82328dc40755f5d0411e2e16e7c0cbf33b51b7',
				'mailto:mail@ddress.tld',
			)
		);
	}

	/**
	 * @test
	 * @dataProvider validJumpUrlDataProvider
	 */
	public function jumpUrlAcceptsValidUrls($hash, $jumpUrl) {

		$_GET['juHash'] = $hash;

		$this->jumpUrlUtility
			->expects($this->once())
			->method('redirect')
			->with($jumpUrl, \TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_303);

		$this->jumpUrlUtility->handleJumpUrl($jumpUrl);
	}

	/**
	 * @test
	 * @dataProvider validJumpUrlDataProvider
	 */
	public function jumpUrlFailsOnInvalidHash($hash, $jumpUrl) {

		$_GET['juHash'] .= '1';

		try {
			$this->jumpUrlUtility->handleJumpUrl($jumpUrl);
			$this->fail('Invalid hash did not throw an Exception.');
		} catch (\Exception $ex) {
			$this->assertEquals(1359987599, $ex->getCode());
		}
	}

	/**
	 * Provides a valid jump secure URL hash, a file path and related
	 * record data
	 *
	 * @return array
	 */
	public function validJumpUrlSecureDataProvider() {

		return array(
			array(
				'1933f3c181db8940acfcd4d16c74643947179948',
				'typo3temp/phpunitJumpUrlTestFile.txt',
				'1234:tt_content:999',
			),
			array(
				'304b8c8e022e92e6f4d34e97395da77705830818',
				str_replace('%2F', '/', rawurlencode('typo3temp/phpunitJumpUrlTestFile with spaces & amps.txt')),
				'1234:tt_content:999',
			),
			array(
				'304b8c8e022e92e6f4d34e97395da77705830818',
				str_replace('%2F', '/', rawurlencode('typo3temp/phpunitJumpUrlTestFile with spaces & amps.txt')),
				'1234:tt_content:999',
			),
		);
	}

	/**
	 * @test
	 * @dataProvider validJumpUrlSecureDataProvider
	 */
	public function jumpUrlSecureAcceptsValidUrls($hash, $jumpUrl, $locationData) {

		$absoluteFilename = $this->prepareJumpUrlSecureTest($hash, $jumpUrl, $locationData);

		$this->tsfe->expects($this->once())
				->method('locDataCheck')
				->with($locationData)
				->will($this->returnValue(TRUE));

		$this->jumpUrlUtility
				->expects($this->once())
				->method('readFileAndExit')
				->with($absoluteFilename);

		$this->jumpUrlUtility->handleJumpUrl($jumpUrl);

		\TYPO3\CMS\Core\Utility\GeneralUtility::unlink_tempfile($absoluteFilename);
	}

	/**
	 * @test
	 */
	public function jumpUrlSecureFailsOnForbiddenFileLocation() {

		$_GET['juSecure'] = '1';
		$_GET['juHash'] = 'eecf7a92af78892c005bc404847be84fdeb60a61';
		$_GET['locationData'] = '';

		$this->tsfe->expects($this->once())
				->method('locDataCheck')
				->with('')
				->will($this->returnValue(TRUE));

		try {
			$this->jumpUrlUtility->handleJumpUrl('/a/totally/forbidden/path');
			$this->fail('Forbidden jump URL file path did not throw an Exception.');
		} catch(\Exception $ex) {
			$this->assertEquals(1294585194, $ex->getCode());
		}
	}

	/**
	 * @test
	 * @dataProvider validJumpUrlSecureDataProvider
	 */
	public function jumpUrlSecureFailsOnInvalidHash($hash, $jumpUrl, $locationData) {
		$absoluteFilename = $this->prepareJumpUrlSecureTest($hash, $jumpUrl, $locationData);
		$_GET['juHash'] .= '1';
		try {
			$this->jumpUrlUtility->handleJumpUrl($jumpUrl);
			$this->fail('Invalid jump URL hash did not throw an Exception.');
		} catch(\Exception $ex) {
			$this->assertEquals(1294585196, $ex->getCode());
		}
		\TYPO3\CMS\Core\Utility\GeneralUtility::unlink_tempfile($absoluteFilename);
	}

	/**
	 * @test
	 * @dataProvider validJumpUrlSecureDataProvider
	 */
	public function jumpUrlSecureFailsOnDeniedAccess($hash, $jumpUrl, $locationData) {

		$absoluteFilename = $this->prepareJumpUrlSecureTest($hash, $jumpUrl, $locationData);

		$this->tsfe->expects($this->once())
				->method('locDataCheck')
				->with($locationData)
				->will($this->returnValue(FALSE));

		try {
			$this->jumpUrlUtility->handleJumpUrl($jumpUrl);
			$this->fail('Denied access did not throw an Exception.');
		} catch(\Exception $ex) {
			$this->assertEquals(1294585195, $ex->getCode());
		}

		\TYPO3\CMS\Core\Utility\GeneralUtility::unlink_tempfile($absoluteFilename);
	}

	/**
	 * @test
	 * @dataProvider validJumpUrlSecureDataProvider
	 */
	public function jumpUrlSecureFailsIfFileDoesNotExist($hash, $jumpUrl, $locationData) {

		$absoluteFilename = $this->prepareJumpUrlSecureTest($hash, $jumpUrl, $locationData);
		\TYPO3\CMS\Core\Utility\GeneralUtility::unlink_tempfile($absoluteFilename);

		$this->tsfe->expects($this->once())
				->method('locDataCheck')
				->with($locationData)
				->will($this->returnValue(TRUE));

		try {
			$this->jumpUrlUtility->handleJumpUrl($jumpUrl);
			$this->fail('Non existing file did not throw an Exeption.');
		} catch(\Exception $ex) {
			$this->assertEquals(1294585193, $ex->getCode());
		}
	}

	/**
	 * @param $hash
	 * @param $jumpUrl
	 * @param $locationData
	 * @return string
	 */
	protected function prepareJumpUrlSecureTest($hash, $jumpUrl, $locationData) {

		$_GET['juSecure'] = '1';
		$_GET['juHash'] = $hash;
		$_GET['locationData'] = $locationData;
		$absoluteFilename = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(rawurldecode($jumpUrl));
		file_put_contents($absoluteFilename, 'testcontent');

		return $absoluteFilename;
	}
}

?>