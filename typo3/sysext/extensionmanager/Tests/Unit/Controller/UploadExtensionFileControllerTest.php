<?php
namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Controller;

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
		$fixture = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Controller\UploadExtensionFileController::class, array('dummy'));
		$installUtilityMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Utility\InstallUtility::class, array(), array(), '', FALSE);
		$installUtilityMock->expects($this->once())
			->method('isAvailable')
			->with($expectedKey)
			->will($this->returnValue(FALSE));
		$fixture->_set('installUtility', $installUtilityMock);
		$fileHandlingUtilityMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility::class, array(), array(), '', FALSE);
		$fileHandlingUtilityMock->expects($this->once())->method('unzipExtensionFromFile');
		$fixture->_set('fileHandlingUtility', $fileHandlingUtilityMock);

		$extensionDetails = $fixture->_call('getExtensionFromZipFile', '', $filename);
		$this->assertEquals($expectedKey, $extensionDetails['extKey']);
	}

}
