<?php
namespace TYPO3\CMS\Core\Tests\Unit\TypoScript;

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
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Testcase for \TYPO3\CMS\Core\TypoScript\TemplateService
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class TemplateServiceTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * Enable backup of global and system variables
	 *
	 * @var boolean
	 */
	protected $backupGlobals = TRUE;

	/**
	 * @var \TYPO3\CMS\Core\TypoScript\TemplateService
	 */
	protected $templateService;

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\TYPO3\CMS\Core\TypoScript\TemplateService
	 */
	protected $templateServiceMock;

	/**
	 * Sets up this test case.
	 *
	 * @return void
	 */
	protected function setUp() {
		$GLOBALS['TYPO3_LOADED_EXT'] = array();
		$this->templateService = new \TYPO3\CMS\Core\TypoScript\TemplateService();
		$this->templateService->tt_track = FALSE;
		$this->templateServiceMock = $this->getAccessibleMock('\\TYPO3\\CMS\\Core\\TypoScript\\TemplateService', array('dummy'));
		$this->templateServiceMock->tt_track = FALSE;
	}

	/**
	 * @test
	 */
	public function versionOlCallsVersionOlOfPageSelectClassWithGivenRow() {
		$row = array('foo');
		$GLOBALS['TSFE'] = new \stdClass();
		$sysPageMock = $this->getMock('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
		$sysPageMock->expects($this->once())->method('versionOL')->with('sys_template', $row);
		$GLOBALS['TSFE']->sys_page = $sysPageMock;
		$this->templateService->versionOL($row);
	}

	/**
	 * @test
	 */
	public function extensionStaticFilesAreNotProcessedIfNotExplicitlyRequested() {
		$identifier = $this->getUniqueId('test');
		$GLOBALS['TYPO3_LOADED_EXT'] = array(
			$identifier => array(
				'ext_typoscript_setup.txt' => ExtensionManagementUtility::extPath(
					'core', 'Tests/Unit/TypoScript/Fixtures/ext_typoscript_setup.txt'
				),
			),
		);

		$this->templateService->runThroughTemplates(array(), 0);
		$this->assertFalse(
			in_array('test.Core.TypoScript = 1', $this->templateService->config)
		);
	}

	/**
	 * @test
	 */
	public function extensionStaticsAreProcessedIfExplicitlyRequested() {
		$identifier = $this->getUniqueId('test');
		$GLOBALS['TYPO3_LOADED_EXT'] = array(
			$identifier => array(
				'ext_typoscript_setup.txt' => ExtensionManagementUtility::extPath(
						'core', 'Tests/Unit/TypoScript/Fixtures/ext_typoscript_setup.txt'
					),
				'ext_typoscript_constants.txt' => ''
			),
		);

		$mockPackage = $this->getMock('TYPO3\\CMS\\Core\\Package\\Package', array('getPackagePath'), array(), '', FALSE);
		$mockPackage->expects($this->any())->method('getPackagePath')->will($this->returnValue(''));

		$mockPackageManager = $this->getMock('TYPO3\\CMS\\Core\\Package\\PackageManager', array('isPackageActive' , 'getPackage'));
		$mockPackageManager->expects($this->any())->method('isPackageActive')->will($this->returnValue(TRUE));
		$mockPackageManager->expects($this->any())->method('getPackage')->will($this->returnValue($mockPackage));
		ExtensionManagementUtility::setPackageManager($mockPackageManager);

		$this->templateService->setProcessExtensionStatics(TRUE);
		$this->templateService->runThroughTemplates(array(), 0);

		$this->assertTrue(
			in_array('test.Core.TypoScript = 1', $this->templateService->config)
		);

		ExtensionManagementUtility::setPackageManager(GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Package\\PackageManager'));
	}

	/**
	 * @test
	 */
	public function updateRootlineDataOverwritesOwnArrayData() {
		$originalRootline = array(
			0 => array('uid' => 2, 'title' => 'originalTitle'),
			1 => array('uid' => 3, 'title' => 'originalTitle2'),
		);

		$updatedRootline = array(
			0 => array('uid' => 1, 'title' => 'newTitle'),
			1 => array('uid' => 2, 'title' => 'newTitle2'),
			2 => array('uid' => 3, 'title' => 'newTitle3'),
		);

		$expectedRootline = array(
			0 => array('uid' => 2, 'title' => 'newTitle2'),
			1 => array('uid' => 3, 'title' => 'newTitle3'),
		);

		$this->templateServiceMock->_set('rootLine', $originalRootline);
		$this->templateServiceMock->updateRootlineData($updatedRootline);
		$this->assertEquals($expectedRootline, $this->templateServiceMock->_get('rootLine'));
	}

	/**
	 * @test
	 * @expectedException \RuntimeException
	 */
	public function updateRootlineDataWithInvalidNewRootlineThrowsException() {
		$originalRootline = array(
			0 => array('uid' => 2, 'title' => 'originalTitle'),
			1 => array('uid' => 3, 'title' => 'originalTitle2'),
		);

		$newInvalidRootline = array(
			0 => array('uid' => 1, 'title' => 'newTitle'),
			1 => array('uid' => 2, 'title' => 'newTitle2'),
		);

		$this->templateServiceMock->_set('rootLine', $originalRootline);
		$this->templateServiceMock->updateRootlineData($newInvalidRootline);
	}

	/**
	 * @test
	 */
	public function getFileNameReturnsUrlCorrectly() {
		$this->assertSame('http://example.com', $this->templateService->getFileName('http://example.com'));
		$this->assertSame('https://example.com', $this->templateService->getFileName('https://example.com'));
	}

	/**
	 * @test
	 */
	public function getFileNameReturnsFileCorrectly() {
		$this->assertSame('typo3/index.php', $this->templateService->getFileName('typo3/index.php'));
	}

	/**
	 * @test
	 */
	public function getFileNameReturnsNullIfDirectory() {
		$this->assertNull($this->templateService->getFileName(__DIR__));
	}

	/**
	 * @test
	 */
	public function getFileNameReturnsNullWithInvalidFileName() {
		$this->assertNull($this->templateService->getFileName('  '));
		$this->assertNull($this->templateService->getFileName('something/../else'));
	}

}
