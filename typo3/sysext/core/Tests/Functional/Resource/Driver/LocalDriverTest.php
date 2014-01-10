<?php
namespace TYPO3\CMS\Core\Tests\Functional\Resource\Driver;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Andreas Wolf <andreas.wolf@ikt-werk.de>
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
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;

require_once dirname(dirname(__FILE__)) . '/BaseTestCase.php';

/**
 * Test case
 */
class LocalDriverTest extends \TYPO3\CMS\Core\Tests\Functional\Resource\BaseTestCase {

	/**
	 * Set up
	 */
	public function setUp() {
		$this->markTestIncomplete('needs to be fixed');
	}

	/**
	 * @test
	 */
	public function foldersCanBeCopiedWithinSameStorage() {
		$fileContents1 = uniqid();
		$fileContents2 = uniqid();
		$this->addToMount(array(
			'targetFolder' => array(),
			'sourceFolder' => array(
				'subFolder' => array(
					'file' => $fileContents1
				),
				'file' => $fileContents2
			)
		));
		$fixture = $this->createDriverFixture(
			array(
				'basePath' => $this->getMountRootUrl()
			)
		);
		$sourceFolder = $this->getSimpleFolderMock('/sourceFolder/');
		$targetFolder = $this->getSimpleFolderMock('/targetFolder/');
		$fixture->copyFolderWithinStorage($sourceFolder, $targetFolder, 'sourceFolder');
		$this->assertTrue($fixture->folderExists('/targetFolder/sourceFolder/'));
		$this->assertTrue($fixture->fileExists('/targetFolder/sourceFolder/file'));
		$this->assertTrue($fixture->folderExists('/targetFolder/sourceFolder/subFolder/'));
		$this->assertTrue($fixture->fileExists('/targetFolder/sourceFolder/subFolder/file'));
	}

	/**
	 * @test
	 */
	public function folderNameCanBeChangedWhileCopying() {
		$fileContents1 = uniqid();
		$fileContents2 = uniqid();
		$this->addToMount(array(
			'targetFolder' => array(),
			'sourceFolder' => array(
				'subFolder' => array(
					'file' => $fileContents1
				),
				'file' => $fileContents2
			)
		));
		$fixture = $this->createDriverFixture(
			array(
				'basePath' => $this->getMountRootUrl()
			)
		);
		$sourceFolder = $this->getSimpleFolderMock('/sourceFolder/');
		$targetFolder = $this->getSimpleFolderMock('/targetFolder/');
		$fixture->copyFolderWithinStorage($sourceFolder, $targetFolder, 'newFolder');
		$this->assertTrue($fixture->folderExists('/targetFolder/newFolder/'));
		$this->assertTrue($fixture->fileExists('/targetFolder/newFolder/file'));
		$this->assertFalse($fixture->folderExists('/targetFolder/sourceFolder/'));
	}

	/**
	 * Creates a driver fixture object, optionally using a given mount object.
	 *
	 * IMPORTANT: Call this only after setting up the virtual file system (with the addTo* methods)!
	 *
	 * @param $driverConfiguration
	 * @param \TYPO3\CMS\Core\Resource\ResourceStorage $storageObject
	 * @param array $mockedDriverMethods
	 * @return \TYPO3\CMS\Core\Resource\Driver\LocalDriver
	 */
	protected function createDriverFixture($driverConfiguration, \TYPO3\CMS\Core\Resource\ResourceStorage $storageObject = NULL, $mockedDriverMethods = array()) {
		$this->initializeVfs();
		if ($storageObject == NULL) {
			$storageObject = $this->getMock('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', array(), array(), '', FALSE);
		}
		if (count($mockedDriverMethods) == 0) {
			$driver = new \TYPO3\CMS\Core\Resource\Driver\LocalDriver($driverConfiguration);
		} else {
			$driver = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Driver\\LocalDriver', $mockedDriverMethods, array($driverConfiguration));
		}
		$storageObject->setDriver($driver);
		$driver->setStorage($storageObject);
		$driver->processConfiguration();
		$driver->initialize();
		return $driver;
	}
}
