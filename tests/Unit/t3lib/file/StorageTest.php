<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2011 Andreas Wolf <andreas.wolf@ikt-werk.de>
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


require_once 'vfsStream/vfsStream.php';
require_once dirname(__FILE__) . '/BaseTestCase.php';


/**
 * Testcase for the VFS mount class
 *
 * @package TYPO3
 * @subpackage t3lib
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 */
class t3lib_file_StorageTest extends t3lib_file_BaseTestCase {
	/**
	 * @var t3lib_file_Storage
	 */
	private $fixture;

	/**
	 * @param array $configuration
	 * @param bool $mockPermissionChecks
	 */
	protected function prepareFixture($configuration, $mockPermissionChecks = FALSE, $driverObject = NULL, array $storageRecord = array()) {
		$permissionMethods = array('isFileActionAllowed', 'isFolderActionAllowed', 'checkFileActionPermission', 'checkUserActionPermission');

		$mockedMethods = NULL;

		$configuration = $this->convertConfigurationArrayToFlexformXml($configuration);
		$storageRecord = t3lib_div::array_merge_recursive_overrule($storageRecord, array(
			'configuration' => $configuration
		));

		if ($driverObject == NULL) {
			/** @var $mockedDriver t3lib_file_Driver_AbstractDriver */
			$driverObject = $this->getMockForAbstractClass('t3lib_file_Driver_AbstractDriver', array(), '', FALSE);
		}

		if ($mockPermissionChecks) {
			$mockedMethods = $permissionMethods;
		}

		if ($mockedMethods === NULL) {
			$this->fixture = new t3lib_file_Storage($driverObject, $storageRecord);
		} else {
			$this->fixture = $this->getMock('t3lib_file_Storage', $mockedMethods, array($driverObject, $storageRecord));
			foreach ($permissionMethods as $method) {
				$this->fixture->expects($this->any())->method($method)->will($this->returnValue(TRUE));
			}
		}
	}

	/**
	 * Converts a simple configuration array into a FlexForm data structure serialized as XML
	 *
	 * @param array $configuration
	 * @return string
	 *
	 * @see t3lib_div::array2xml()
	 */
	protected function convertConfigurationArrayToFlexformXml(array $configuration) {
		$flexformArray = array('data' => array('sDEF' => array('lDEF' => array())));
		foreach ($configuration as $key => $value) {
			$flexformArray['data']['sDEF']['lDEF'][$key] = array('vDEF' => $value);
		}
		$configuration = t3lib_div::array2xml($flexformArray);
		return $configuration;
	}

	/**
	 * Creates a driver fixture object, optionally using a given mount object.
	 *
	 * IMPORTANT: Call this only after setting up the virtual file system (with the addTo* methods)!
	 *
	 * @param $driverConfiguration
	 * @param t3lib_file_Storage $storageObject
	 * @param array $mockedDriverMethods
	 * @return t3lib_file_Driver_LocalDriver
	 */
	protected function createDriverMock($driverConfiguration, t3lib_file_Storage $storageObject = NULL, $mockedDriverMethods = array()) {
		$this->initializeVfs();

		if ($storageObject == NULL) {
			$storageObject = $this->getMock('t3lib_file_Storage', array(), array(), '', FALSE);
		}

		if ($mockedDriverMethods === NULL) {
			$driver = new t3lib_file_Driver_LocalDriver($driverConfiguration);
		} else {
				// we are using the LocalDriver here because PHPUnit can't mock concrete methods in abstract classes, so
				// when using the AbstractDriver we would be in trouble when wanting to mock away some concrete method
			$driver = $this->getMock('t3lib_file_Driver_LocalDriver', $mockedDriverMethods, array($driverConfiguration));
		}
		$storageObject->setDriver($driver);
		$driver->setStorage($storageObject);
		$driver->processConfiguration();
		$driver->initialize();
		return $driver;
	}



	/**
	 * @test
	 */
	public function baseUriGetsSlashAppended() {
		$uri = 'http://example.org/somewhere/else';
		$this->prepareFixture(array('baseUri' => $uri));

		$this->assertEquals($uri . '/', $this->fixture->getBaseUri());
	}

	public function capabilitiesDataProvider() {
		return array(
			'only public' => array(
				array(
					'public' => TRUE,
					'writable' => FALSE,
					'browsable' => FALSE,
				)
			),
			'only writable' => array(
				array(
					'public' => FALSE,
					'writable' => TRUE,
					'browsable' => FALSE,
				)
			),
			'only browsable' => array(
				array(
					'public' => FALSE,
					'writable' => FALSE,
					'browsable' => TRUE,
				)
			),
			'all capabilities' => array(
				array(
					'public' => TRUE,
					'writable' => TRUE,
					'browsable' => TRUE,
				)
			),
			'none' => array(
				array(
					'public' => FALSE,
					'writable' => FALSE,
					'browsable' => FALSE,
				)
			)
		);
	}

	/**
	 * @test
	 * @dataProvider capabilitiesDataProvider
	 */
	public function capabilitiesOfStorageObjectAreCorrectlySet(array $capabilites) {
		$storageRecord = array(
			'is_public' => $capabilites['public'],
			'is_writable' => $capabilites['writable'],
			'is_browsable' => $capabilites['browsable'],
			'is_online' => TRUE
		);
		$mockedDriver = $this->createDriverMock(array(), $this->fixture, array('hasCapability'));
		$mockedDriver->expects($this->any())->method('hasCapability')->will($this->returnValue(TRUE));
		$this->prepareFixture(array(), FALSE, $mockedDriver, $storageRecord);

		$this->assertEquals($capabilites['public'], $this->fixture->isPublic(), 'Capability "public" is not correctly set.');
		$this->assertEquals($capabilites['writable'], $this->fixture->isWritable(), 'Capability "writable" is not correctly set.');
		$this->assertEquals($capabilites['browsable'], $this->fixture->isBrowsable(), 'Capability "browsable" is not correctly set.');
	}

	/**
	 * @test
	 */
	public function fileAndFolderListFiltersAreInitializedWithDefaultFilters() {
		$this->prepareFixture(array());

		$this->assertEquals($GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['callbackFilterMethods'], $this->fixture->getFileAndFolderNameFilters());
	}

	/**
	 * @test
	 */
	public function addFileFailsIfFileDoesNotExist() {
		$mockedFolder = $this->getMock('t3lib_file_Folder', array(), array(), '', FALSE);

		$this->setExpectedException('InvalidArgumentException', '', 1319552745);

		$this->prepareFixture(array());
		$this->fixture->addFile('/some/random/file', $mockedFolder);
	}

	/**
	 * @test
	 */
	public function addFileCallsDriverWithCorrectArguments() {
		$mockedFolder = $this->getSimpleFolderMock('/targetFolder/');

		$this->addToMount(array(
			'targetFolder' => array(
			),
			'file.ext' => 'ajslkd'
		));
		$this->initializeVfs();
		$localFilePath = $this->getUrlInMount('file.ext');

		$this->prepareFixture(array());
		/** @var $driver t3lib_file_Driver_LocalDriver */
		$driver = $this->getMock('t3lib_file_Driver_LocalDriver', array('addFile'),
			array(array('basePath' => $this->getUrlInMount('targetFolder/')))
		);
		$driver->expects($this->once())->method('addFile')->with(
			$this->equalTo($localFilePath), $this->anything(), $this->equalTo('file.ext')
		);
		$this->fixture->setDriver($driver);

		$this->fixture->addFile($localFilePath, $mockedFolder);
	}

	/**
	 * @test
	 */
	public function addFileChangesFilenameIfFileExists() {
		$mockedFolder = $this->getSimpleFolderMock('/');

		$this->addToMount(array(
			'targetFolder' => array(
				'file.ext' => 'asdf',
				'file_01.ext' => 'asjdlkajs'
			),
			'file.ext' => 'ajslkd'
		));
		$this->initializeVfs();

		$this->prepareFixture(array());
		/** @var $driver t3lib_file_Driver_LocalDriver */
		$driver = $this->getMock('t3lib_file_Driver_LocalDriver', array('addFile', 'fileExistsInFolder'),
			array(array('basePath' => $this->getUrlInMount('targetFolder/')))
		);
		$driver->expects($this->once())->method('addFile')->with(
			$this->anything(), $this->anything(), $this->equalTo('file_02.ext')
		);
		$driver->expects($this->exactly(3))->method('fileExistsInFolder')->will($this->onConsecutiveCalls(
			$this->returnValue(TRUE),
			$this->returnValue(TRUE),
			$this->returnValue(FALSE)
		));
		$this->fixture->setDriver($driver);

		$this->fixture->addFile($this->getUrlInMount('file.ext'), $mockedFolder);
		// all required checks are done by expectations in $driver, so we don't need any assertions here
	}

	public function checkFolderPermissionsFilesystemPermissions_dataProvider() {
		return array(
			'read action on readable/writable folder' => array(
				'read',
				array('r' => TRUE, 'w' => TRUE)
			),
			'read action on unreadable folder' => array(
				'read',
				array('r' => FALSE, 'w' => TRUE),
				't3lib_file_exception_InsufficientFolderReadPermissionsException'
			),
			'write action on read-only folder' => array(
				'write',
				array('r' => TRUE, 'w' => FALSE),
				't3lib_file_exception_InsufficientFolderWritePermissionsException'
			)
		);
	}

	/**
	 * @test
	 * @dataProvider checkFolderPermissionsFilesystemPermissions_dataProvider
	 * @param string $action 'read' or 'write'
	 * @param array $permissionsFromDriver The permissions as returned from the driver
	 * @param bool $expectedException
	 */
	public function checkFolderPermissionsRespectsFilesystemPermissions($action, $permissionsFromDriver, $expectedException) {
		$mockedDriver = $this->getMock('t3lib_file_Driver_LocalDriver');
		$mockedDriver->expects($this->any())->method('getFolderPermissions')->will($this->returnValue($permissionsFromDriver));
		$mockedFolder = $this->getMock('t3lib_file_Folder', array(), array(), '', FALSE);

			// let all other checks pass
		/** @var $fixture t3lib_file_Storage */
		$fixture = $this->getMock('t3lib_file_Storage', array('isWritable', 'isBrowsable', 'checkUserActionPermission'),
			array($mockedDriver, array()), '', FALSE);
		$fixture->expects($this->any())->method('isWritable')->will($this->returnValue(TRUE));
		$fixture->expects($this->any())->method('isBrowsable')->will($this->returnValue(TRUE));
		$fixture->expects($this->any())->method('checkUserActionPermission')->will($this->returnValue(TRUE));
		$fixture->setDriver($mockedDriver);

		if ($expectedException == '') {
			$this->assertTrue($fixture->checkFolderActionPermission($action, $mockedFolder));
		} else {
			$this->markTestSkipped('The exception has been disable in t3lib_file_Storage');
			$this->setExpectedException($expectedException);
			$fixture->checkFolderActionPermission($action, $mockedFolder);
		}
	}

	/**
	 * @test
	 */
	public function checkUserActionPermissionsAlwaysReturnsTrueIfNoUserPermissionsAreSet() {
		$this->prepareFixture(array());

		$this->assertTrue($this->fixture->checkUserActionPermission('read', 'folder'));
	}

	/**
	 * @test
	 */
	public function checkUserActionPermissionReturnsFalseIfPermissionIsSetToZero() {
		$this->prepareFixture(array());
		$this->fixture->injectUserPermissions(array('readFolder' => TRUE, 'writeFile' => TRUE));

		$this->assertTrue($this->fixture->checkUserActionPermission('read', 'folder'));
	}

	public function checkUserActionPermission_arbitraryPermissionDataProvider() {
		return array(
			'all lower cased' => array(
				array('readFolder' => TRUE),
				'read',
				'folder'
			),
			'all upper case' => array(
				array('readFolder' => TRUE),
				'READ',
				'FOLDER'
			),
			'mixed case' => array(
				array('readFolder' => TRUE),
				'ReaD',
				'FoLdEr'
			)
		);
	}

	/**
	 * @test
	 * @dataProvider checkUserActionPermission_arbitraryPermissionDataProvider
	 */
	public function checkUserActionPermissionAcceptsArbitrarilyCasedArguments($permissions, $action, $type) {
		$this->prepareFixture(array());
		$this->fixture->injectUserPermissions($permissions);

		$this->assertTrue($this->fixture->checkUserActionPermission($action, $type));
	}

	/**
	 * @test
	 */
	public function userActionIsDisallowedIfPermissionIsSetToFalse() {
		$this->prepareFixture(array());
		$this->fixture->injectUserPermissions(array('readFolder' => FALSE));

		$this->assertFalse($this->fixture->checkUserActionPermission('read', 'folder'));
	}

	/**
	 * @test
	 */
	public function userActionIsDisallowedIfPermissionIsNotSet() {
		$this->prepareFixture(array());
		$this->fixture->injectUserPermissions(array('readFolder' => TRUE));

		$this->assertFalse($this->fixture->checkUserActionPermission('write', 'folder'));
	}

	/**
	 * @test
	 * @group integration
	 */
	public function setFileContentsUpdatesObjectProperties() {
		$fileContents = 'asdf';
		$this->initializeVfs();
		$this->prepareFixture(array(), TRUE);

		$fileProperties = array(
			'someProperty' => 'value',
			'someOtherProperty' => 42
		);
		$hash = 'asdfg';
		$driver = $this->getMock('t3lib_file_Driver_LocalDriver', array(),
			array(array('basePath' => $this->getMountRootUrl()))
		);
		$driver->expects($this->once())->method('getFileInfo')->will($this->returnValue($fileProperties));
		$driver->expects($this->once())->method('hash')->will($this->returnValue($hash));
		$this->fixture->setDriver($driver);

		$mockedFile = $this->getMock('t3lib_file_File', array(), array(), '', FALSE);
		$mockedFile->expects($this->any())->method('getIdentifier')->will($this->returnValue('/file.ext'));
		$mockedFile->expects($this->once())->method('updateProperties')->with(
			$this->equalTo(array_merge($fileProperties, array('sha1' => $hash)))
		);

		$this->fixture->setFileContents($mockedFile, uniqid());
	}

	/**
	 * @test
	 * @group integration
	 */
	public function moveFileCallsDriversRawMethodsWithCorrectArguments() {
		$localFilePath = '/path/to/localFile';
		$sourceFileIdentifier = '/sourceFile.ext';
		$this->addToMount(array(
			'targetFolder' => array(),
		));
		$this->initializeVfs();

		$targetFolder = $this->getSimpleFolderMock('/targetFolder/');
		$sourceDriver = $this->getMock('t3lib_file_Driver_LocalDriver');
		$sourceDriver->expects($this->once())->method('deleteFileRaw')->with($this->equalTo($sourceFileIdentifier));

		$configuration = $this->convertConfigurationArrayToFlexformXml(array());
		$sourceStorage = new t3lib_file_Storage($sourceDriver, array('configuration' => $configuration));

		$sourceFile = $this->getSimpleFileMock($sourceFileIdentifier);
		$sourceFile->expects($this->once())->method('getForLocalProcessing')->will($this->returnValue($localFilePath));
		$sourceFile->expects($this->any())->method('getStorage')->will($this->returnValue($sourceStorage));

		/** @var $driver t3lib_file_Driver_LocalDriver */
		$driver = $this->getMock('t3lib_file_Driver_LocalDriver', array(),
			array(array('basePath' => $this->getMountRootUrl()))
		);
		$driver->expects($this->once())->method('addFileRaw')->with(
			$localFilePath, $targetFolder, $this->equalTo('file.ext')
		)->will($this->returnValue('/targetFolder/file.ext'));
		/** @var $fixture t3lib_file_Storage */
		$fixture = $this->getMock('t3lib_file_Storage', array('checkFileMovePermissions', 'updateFile'),
			array($driver, array('configuration' => $configuration)));
		$fixture->expects($this->once())->method('updateFile')->with($this->equalTo($sourceFile), $this->equalTo('/targetFolder/file.ext'));

		$fixture->moveFile($sourceFile, $targetFolder, 'file.ext');
	}

	/**
	 * @test
	 * @group integration
	 */
	public function copyFileCallsDriversRawMethodsWithCorrectArguments() {
		$localFilePath = '/path/to/localFile';
		$sourceFileIdentifier = '/sourceFile.ext';
		$this->addToMount(array(
			'targetFolder' => array(),
		));
		$this->initializeVfs();

		$targetFolder = $this->getSimpleFolderMock('/targetFolder/');
		$storageConfiguration = $this->convertConfigurationArrayToFlexformXml(array());

		$sourceStorage = $this->getMock('t3lib_file_Storage', array(), array(), '', FALSE);

		$sourceFile = $this->getSimpleFileMock($sourceFileIdentifier);
		$sourceFile->expects($this->once())->method('getForLocalProcessing')->will($this->returnValue($localFilePath));
		$sourceFile->expects($this->any())->method('getStorage')->will($this->returnValue($sourceStorage));

		/** @var $driver t3lib_file_Driver_LocalDriver */
		$driver = $this->getMock('t3lib_file_Driver_LocalDriver', array(),
			array(array('basePath' => $this->getMountRootUrl()))
		);
		$driver->expects($this->once())->method('addFile')->with(
			$localFilePath, $targetFolder, $this->equalTo('file.ext')
		);
		/** @var $fixture t3lib_file_Storage */
		$fixture = $this->getMock('t3lib_file_Storage', array('checkFileCopyPermissions'),
			array($driver, array('configuration' => $storageConfiguration)));

		$fixture->copyFile($sourceFile, $targetFolder, 'file.ext');
	}

	/**
	 * @test
	 * @group integration
	 */
	public function storageUsesInjectedFilemountsToCheckForMountBoundaries() {
		$mockedFile = $this->getSimpleFileMock('/mountFolder/file');
		$this->addToMount(array(
			'mountFolder' => array(
				'file' => 'asdfg'
			)
		));
		$mockedDriver = $this->createDriverMock(array('basePath' => $this->getMountRootUrl()), NULL, NULL);

		$this->initializeVfs();
		$this->prepareFixture(array(), NULL, $mockedDriver);

		$this->fixture->injectFileMount('/mountFolder');
		$this->assertEquals(1, count($this->fixture->getFileMounts()));
		$this->fixture->isWithinFileMountBoundaries($mockedFile);
	}

	/**
	 * This test is also valid for folders
	 *
	 * @test
	 */
	public function getFileListReturnsFilesInCorrectOrder() {
		$fileList = array(
			'file10' => '',
			'file2' => '',
			'File' => '',
			'fail' => ''
		);

		$this->prepareFixture(array());
		$driver = $this->createDriverMock(array('basePath' => $this->getMountRootUrl()), $this->fixture, array('getFileList'));
		$driver->expects($this->once())->method('getFileList')->will($this->returnValue($fileList));

		$fileList = $this->fixture->getFileList('/');

		$this->assertEquals(array('fail', 'File', 'file2', 'file10'), array_keys($fileList));
	}

	/**
	 * @test
	 */
	public function getFileListIgnoresCasingWhenSortingFilenames() {
		$fileList = array(
			'aFile' => 'dfsdg',
			'zFile' => 'werw',
			'BFile' => 'asd',
			'12345' => 'fdsa',
			'IMG_1234.jpg' => 'asdf'
		);

		$this->prepareFixture(array());
		$driver = $this->createDriverMock(array(), $this->fixture, array('getFileList'));
		$driver->expects($this->once())->method('getFileList')->will($this->returnValue($fileList));

		$fileList = $this->fixture->getFileList('/');

		$this->assertEquals(array('12345', 'aFile', 'BFile', 'IMG_1234.jpg', 'zFile'), array_keys($fileList));
	}

	/**
	 * @test
	 */
	public function createFolderChecksIfParentFolderExistsBeforeCreatingFolder() {
		$mockedParentFolder = $this->getSimpleFolderMock('/someFolder/');
		$mockedDriver = $this->createDriverMock(array());
		$mockedDriver->expects($this->once())->method('folderExists')->with($this->equalTo('/someFolder/'))->will($this->returnValue(TRUE));
		$mockedDriver->expects($this->once())->method('createFolder')->with($this->equalTo('newFolder'))->will($this->returnValue($mockedParentFolder));

		$this->prepareFixture(array(), TRUE);
		$this->fixture->setDriver($mockedDriver);

		$this->fixture->createFolder('newFolder', $mockedParentFolder);
	}

	/**
	 * @test
	 */
	public function createFolderCallsDriverForFolderCreation() {
		$mockedParentFolder = $this->getSimpleFolderMock('/someFolder/');
		$this->prepareFixture(array(), TRUE);
		$mockedDriver = $this->createDriverMock(array(), $this->fixture);
		$mockedDriver->expects($this->once())->method('createFolder')->with($this->equalTo('newFolder'), $this->equalTo($mockedParentFolder))
			->will($this->returnValue(TRUE));
		$mockedDriver->expects($this->once())->method('folderExists')->with($this->equalTo('/someFolder/'))->will($this->returnValue(TRUE));

		$this->fixture->createFolder('newFolder', $mockedParentFolder);
	}

	/**
	 * @test
	 */
	public function createFolderCanRecursivelyCreateFolders() {
		$this->addToMount(array('someFolder' => array()));
		$mockedDriver = $this->createDriverMock(array('basePath' => $this->getMountRootUrl()), NULL, NULL);
		$this->prepareFixture(array(), TRUE, $mockedDriver);

		$parentFolder = $this->fixture->getFolder('/someFolder/');
		$newFolder = $this->fixture->createFolder('subFolder/secondSubfolder', $parentFolder);

		$this->assertEquals('secondSubfolder', $newFolder->getName());
		$this->assertFileExists($this->getUrlInMount('/someFolder/subFolder/'));
		$this->assertFileExists($this->getUrlInMount('/someFolder/subFolder/secondSubfolder/'));
	}

	/**
	 * @test
	 */
	public function createFolderIgnoresLeadingAndTrailingSlashesWithFoldername() {
		$mockedParentFolder = $this->getSimpleFolderMock('/someFolder/');
		$this->prepareFixture(array(), TRUE);
		$mockedDriver = $this->createDriverMock(array(), $this->fixture);

		$mockedDriver->expects($this->once())->method('folderExists')->with($this->equalTo('/someFolder/'))->will($this->returnValue(TRUE));
		$mockedDriver->expects($this->once())->method('createFolder')->with($this->equalTo('subFolder'));

		$this->fixture->createFolder('/subFolder/', $mockedParentFolder);
	}

	/**
	 * @test
	 */
	public function createFolderUsesRootFolderAsParentFolderIfNotGiven() {
		$mockedRootFolder = $this->getSimpleFolderMock('/');
		$this->prepareFixture(array(), TRUE);
		$mockedDriver = $this->createDriverMock(array(), $this->fixture);

		$mockedDriver->expects($this->once())->method('getRootLevelFolder')->with()->will($this->returnValue($mockedRootFolder));
		$mockedDriver->expects($this->once())->method('folderExists')->with($this->equalTo('/'))->will($this->returnValue(TRUE));
		$mockedDriver->expects($this->once())->method('createFolder')->with($this->equalTo('someFolder'));

		$this->fixture->createFolder('someFolder');
	}

	/**
	 * @test
	 */
	public function createFolderCreatesNestedStructureEvenIfPartsAlreadyExist() {
		$this->addToMount(array(
			'existingFolder' => array()
		));
		$this->initializeVfs();
		$mockedDriver = $this->createDriverMock(array('basePath' => $this->getMountRootUrl()), NULL, NULL);
		$this->prepareFixture(array(), TRUE, $mockedDriver);

		$rootFolder = $this->fixture->getFolder('/');
		$newFolder = $this->fixture->createFolder('existingFolder/someFolder', $rootFolder);

		$this->assertEquals('someFolder', $newFolder->getName());
		$this->assertFileExists($this->getUrlInMount('existingFolder/someFolder'));
	}

	/**
	 * @test
	 */
	public function createFolderThrowsExceptionIfParentFolderDoesNotExist() {
		$this->setExpectedException('InvalidArgumentException', '', 1325689164);

		$mockedParentFolder = $this->getSimpleFolderMock('/someFolder/');
		$this->prepareFixture(array(), TRUE);
		$mockedDriver = $this->createDriverMock(array(), $this->fixture);
		$mockedDriver->expects($this->once())->method('folderExists')->with($this->equalTo('/someFolder/'))->will($this->returnValue(FALSE));

		$this->fixture->createFolder('newFolder', $mockedParentFolder);
	}

	/**
	 * @test
	 */
	public function replaceFileFailsIfLocalFileDoesNotExist() {
		$this->setExpectedException('InvalidArgumentException', '', 1325842622);
		$this->prepareFixture(array(), TRUE);

		$mockedFile = $this->getSimpleFileMock('/someFile');

		$this->fixture->replaceFile($mockedFile, PATH_site . uniqid());
	}
}
?>