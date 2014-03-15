<?php
namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Controller;

/***************************************************************
 * Copyright notice
 *
 * (c) 2013 Philipp Gampe <philipp.gampe@typo3.org>
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
 * Update from TER controller test
 *
 */
class UploadExtensionFileControllerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @return array The test data for getExtensionFromZipFileExtractsExtensionKey
	 */
	public function getExtensionFromZipFileExtractsExtensionKeyDataProvider() {
		return array(
			'simple' => array(
				'extension_0.0.0.zip',
				'extension'
			),
			'underscore in extension name' => array(
				'extension_key_10.100.356.zip',
				'extension_key'
			),
			'camel case file name' => array(
				'extensionName_1.1.1.zip',
				'extensionname'
			),
			'version with dashes' => array(
				'extension_1-2-3.zip',
				'extension'
			),
			'characters after version' => array(
				'extension_1-2-3(1).zip',
				'extension'
			),
			'characters after version with extra space' => array(
				'extension_1-2-3 (1).zip',
				'extension'
			),
			'no version' => array(
				'extension.zip',
				'extension'
			)
		);
	}
	/**
	 * @test
	 * @dataProvider getExtensionFromZipFileExtractsExtensionKeyDataProvider
	 * @param string $filename The file name to test
	 * @param string $expectedKey The expected extension key
	 * @return void
	 */
	public function getExtensionFromZipFileExtractsExtensionKey($filename, $expectedKey) {
		$fixture = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Controller\\UploadExtensionFileController', array('dummy'));
		$installUtilityMock = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Utility\\InstallUtility', array(), array(), '', FALSE);
		$installUtilityMock->expects($this->once())->method('install');
		$fixture->_set('installUtility', $installUtilityMock);
		$fileHandlingUtilityMock = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Utility\\FileHandlingUtility');
		$fileHandlingUtilityMock->expects($this->once())->method('unzipExtensionFromFile');
		$fixture->_set('fileHandlingUtility', $fileHandlingUtilityMock);

		$extensionDetails = $fixture->_call('getExtensionFromZipFile', '', $filename);
		$this->assertEquals($expectedKey, $extensionDetails['extKey']);
	}

}
