<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource;

use TYPO3\CMS\Core\Resource\ResourceStorage;

/***************************************************************
 * Copyright notice
 *
 * (c) 2011-2013 Andreas Wolf <andreas.wolf@ikt-werk.de>
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

/**
 * Testcase for the VFS mount class
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 */
class ResourceStorageTest extends \TYPO3\CMS\Core\Tests\Unit\Resource\BaseTestCase {

	/**
	 * @var array A backup of registered singleton instances
	 */
	protected $singletonInstances = array();

	/**
	 * @var \TYPO3\CMS\Core\Resource\ResourceStorage
	 */
	private $fixture;

	public function setUp() {
		parent::setUp();
		$this->singletonInstances = \TYPO3\CMS\Core\Utility\GeneralUtility::getSingletonInstances();
		\TYPO3\CMS\Core\Utility\GeneralUtility::purgeInstances();
		\TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance(
			'TYPO3\\CMS\\Core\\Resource\\FileRepository',
			$this->getMock('TYPO3\\CMS\\Core\\Resource\\FileRepository')
		);

	}

	public function tearDown() {
		parent::tearDown();
		\TYPO3\CMS\Core\Utility\GeneralUtility::resetSingletonInstances($this->singletonInstances);
	}

	/**
	 * Prepare fixture
	 *
	 * @param array $configuration
	 * @param boolean $mockPermissionChecks
	 * @return void
	 */
	protected function prepareFixture($configuration, $mockPermissionChecks = FALSE, $driverObject = NULL, array $storageRecord = array()) {
		$permissionMethods = array('checkFolderActionPermission', 'checkFileActionPermission', 'checkUserActionPermission');
		$mockedMethods = NULL;
		$configuration = $this->convertConfigurationArrayToFlexformXml($configuration);
		$storageRecord = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($storageRecord, array(
			'configuration' => $configuration
		));
		if ($driverObject == NULL) {
			/** @var $mockedDriver \TYPO3\CMS\Core\Resource\Driver\AbstractDriver */
			$driverObject = $this->getMockForAbstractClass('TYPO3\\CMS\\Core\\Resource\\Driver\\AbstractDriver', array(), '', FALSE);
		}
		if ($mockPermissionChecks) {
			$mockedMethods = $permissionMethods;
		}
		if ($mockedMethods === NULL) {
			$this->fixture = new \TYPO3\CMS\Core\Resource\ResourceStorage($driverObject, $storageRecord);
		} else {
			$this->fixture = $this->getMock('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', $mockedMethods, array($driverObject, $storageRecord));
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
	 * @see \TYPO3\CMS\Core\Utility\GeneralUtility::array2xml()
	 */
	protected function convertConfigurationArrayToFlexformXml(array $configuration) {
		$flexformArray = array('data' => array('sDEF' => array('lDEF' => array())));
		foreach ($configuration as $key => $value) {
			$flexformArray['data']['sDEF']['lDEF'][$key] = array('vDEF' => $value);
		}
		$configuration = \TYPO3\CMS\Core\Utility\GeneralUtility::array2xml($flexformArray);
		return $configuration;
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
	protected function createDriverMock($driverConfiguration, \TYPO3\CMS\Core\Resource\ResourceStorage $storageObject = NULL, $mockedDriverMethods = array()) {
		$this->initializeVfs();
		if ($storageObject == NULL) {
			$storageObject = $this->getMock('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', array(), array(), '', FALSE);
		}

		if (!isset($driverConfiguration['basePath'])) {
			$driverConfiguration['basePath'] = $this->getMountRootUrl();
		}

		if ($mockedDriverMethods === NULL) {
			$driver = new \TYPO3\CMS\Core\Resource\Driver\LocalDriver($driverConfiguration);
		} else {
				// We are using the LocalDriver here because PHPUnit can't mock concrete methods in abstract classes, so
				// when using the AbstractDriver we would be in trouble when wanting to mock away some concrete method
			$driver = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Driver\\LocalDriver', $mockedDriverMethods, array($driverConfiguration));
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

	/**
	 * @return array
	 */
	public function capabilitiesDataProvider() {
		return array(
			'only public' => array(
				array(
					'public' => TRUE,
					'writable' => FALSE,
					'browsable' => FALSE
				)
			),
			'only writable' => array(
				array(
					'public' => FALSE,
					'writable' => TRUE,
					'browsable' => FALSE
				)
			),
			'only browsable' => array(
				array(
					'public' => FALSE,
					'writable' => FALSE,
					'browsable' => TRUE
				)
			),
			'all capabilities' => array(
				array(
					'public' => TRUE,
					'writable' => TRUE,
					'browsable' => TRUE
				)
			),
			'none' => array(
				array(
					'public' => FALSE,
					'writable' => FALSE,
					'browsable' => FALSE
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
		$this->assertEquals($GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['defaultFilterCallbacks'], $this->fixture->getFileAndFolderNameFilters());
	}

	/**
	 * @test
	 */
	public function addFileFailsIfFileDoesNotExist() {
		$mockedFolder = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Folder', array(), array(), '', FALSE);
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
			'targetFolder' => array(),
			'file.ext' => 'ajslkd'
		));
		$this->initializeVfs();
		$localFilePath = $this->getUrlInMount('file.ext');
		$this->prepareFixture(array());
		/** @var $driver \TYPO3\CMS\Core\Resource\Driver\LocalDriver */
		$driver = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Driver\\LocalDriver', array('addFile'), array(array('basePath' => $this->getUrlInMount('targetFolder/'))));
		$driver->expects($this->once())->method('addFile')->with($this->equalTo($localFilePath), $this->anything(), $this->equalTo('file.ext'));
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
		/** @var $driver \TYPO3\CMS\Core\Resource\Driver\LocalDriver */
		$driver = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Driver\\LocalDriver', array('addFile', 'fileExistsInFolder'), array(array('basePath' => $this->getUrlInMount('targetFolder/'))));
		$driver->expects($this->once())->method('addFile')->with($this->anything(), $this->anything(), $this->equalTo('file_02.ext'));
		$driver->expects($this->exactly(3))->method('fileExistsInFolder')->will($this->onConsecutiveCalls($this->returnValue(TRUE), $this->returnValue(TRUE), $this->returnValue(FALSE)));
		$this->fixture->setDriver($driver);
		$this->fixture->addFile($this->getUrlInMount('file.ext'), $mockedFolder);
	}

	/**
	 * Data provider for checkFolderPermissionsRespectsFilesystemPermissions
	 *
	 * @return array
	 */
	public function checkFolderPermissionsFilesystemPermissionsDataProvider() {
		return array(
			'read action on readable/writable folder' => array(
				'read',
				array('r' => TRUE, 'w' => TRUE),
			),
			'read action on unreadable folder' => array(
				'read',
				array('r' => FALSE, 'w' => TRUE),
				'TYPO3\\CMS\\Core\\Resource\\Exception\\InsufficientFolderReadPermissionsException'
			),
			'write action on read-only folder' => array(
				'write',
				array('r' => TRUE, 'w' => FALSE),
				'TYPO3\\CMS\\Core\\Resource\\Exception\\InsufficientFolderWritePermissionsException'
			)
		);
	}

	/**
	 * @test
	 * @dataProvider checkFolderPermissionsFilesystemPermissionsDataProvider
	 * @param string $action 'read' or 'write'
	 * @param array $permissionsFromDriver The permissions as returned from the driver
	 * @param boolean $expectedException
	 */
	public function checkFolderPermissionsRespectsFilesystemPermissions($action, $permissionsFromDriver, $expectedException = '') {
		$mockedDriver = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Driver\\LocalDriver');
		$mockedDriver->expects($this->any())->method('getFolderPermissions')->will($this->returnValue($permissionsFromDriver));
		$mockedFolder = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Folder', array(), array(), '', FALSE);
			// Let all other checks pass
		/** @var $fixture \TYPO3\CMS\Core\Resource\ResourceStorage */
		$fixture = $this->getMock('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', array('isWritable', 'isBrowsable', 'checkUserActionPermission'), array($mockedDriver, array()), '', FALSE);
		$fixture->expects($this->any())->method('isWritable')->will($this->returnValue(TRUE));
		$fixture->expects($this->any())->method('isBrowsable')->will($this->returnValue(TRUE));
		$fixture->expects($this->any())->method('checkUserActionPermission')->will($this->returnValue(TRUE));
		$fixture->setDriver($mockedDriver);
		if ($expectedException == '') {
			$this->assertTrue($fixture->checkFolderActionPermission($action, $mockedFolder));
		} else {
			$this->markTestSkipped('The exception has been disable in TYPO3\\CMS\\Core\\Resource\\ResourceStorage');
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
		$this->fixture->setUserPermissions(array('readFolder' => TRUE, 'writeFile' => TRUE));
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
		$this->fixture->setUserPermissions($permissions);
		$this->assertTrue($this->fixture->checkUserActionPermission($action, $type));
	}

	/**
	 * @test
	 */
	public function userActionIsDisallowedIfPermissionIsSetToFalse() {
		$this->prepareFixture(array());
		$this->fixture->setUserPermissions(array('readFolder' => FALSE));
		$this->assertFalse($this->fixture->checkUserActionPermission('read', 'folder'));
	}

	/**
	 * @test
	 */
	public function userActionIsDisallowedIfPermissionIsNotSet() {
		$this->prepareFixture(array());
		$this->fixture->setUserPermissions(array('readFolder' => TRUE));
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
		$driver = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Driver\\LocalDriver', array(), array(array('basePath' => $this->getMountRootUrl())));
		$driver->expects($this->once())->method('getFileInfo')->will($this->returnValue($fileProperties));
		$driver->expects($this->once())->method('hash')->will($this->returnValue($hash));
		$this->fixture->setDriver($driver);
		$mockedFile = $this->getMock('TYPO3\\CMS\\Core\\Resource\\File', array(), array(), '', FALSE);
		$mockedFile->expects($this->any())->method('getIdentifier')->will($this->returnValue('/file.ext'));
		$mockedFile->expects($this->once())->method('updateProperties')->with($this->equalTo(array_merge($fileProperties, array('sha1' => $hash))));
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
			'targetFolder' => array()
		));
		$this->initializeVfs();
		$targetFolder = $this->getSimpleFolderMock('/targetFolder/');
		$sourceDriver = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Driver\\LocalDriver');
		$sourceDriver->expects($this->once())->method('deleteFileRaw')->with($this->equalTo($sourceFileIdentifier));
		$configuration = $this->convertConfigurationArrayToFlexformXml(array());
		$sourceStorage = new \TYPO3\CMS\Core\Resource\ResourceStorage($sourceDriver, array('configuration' => $configuration));
		$sourceFile = $this->getSimpleFileMock($sourceFileIdentifier);
		$sourceFile->expects($this->once())->method('getForLocalProcessing')->will($this->returnValue($localFilePath));
		$sourceFile->expects($this->any())->method('getStorage')->will($this->returnValue($sourceStorage));
		/** @var $driver \TYPO3\CMS\Core\Resource\Driver\LocalDriver */
		$driver = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Driver\\LocalDriver', array(), array(array('basePath' => $this->getMountRootUrl())));
		$driver->expects($this->once())->method('addFileRaw')->with($localFilePath, $targetFolder, $this->equalTo('file.ext'))->will($this->returnValue('/targetFolder/file.ext'));
		/** @var $fixture \TYPO3\CMS\Core\Resource\ResourceStorage */
		$fixture = $this->getMock('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', array('checkFileMovePermissions', 'updateFile'), array($driver, array('configuration' => $configuration)));
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
			'targetFolder' => array()
		));
		$this->initializeVfs();
		$targetFolder = $this->getSimpleFolderMock('/targetFolder/');
		$storageConfiguration = $this->convertConfigurationArrayToFlexformXml(array());
		$sourceStorage = $this->getMock('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', array(), array(), '', FALSE);
		$sourceFile = $this->getSimpleFileMock($sourceFileIdentifier);
		$sourceFile->expects($this->once())->method('getForLocalProcessing')->will($this->returnValue($localFilePath));
		$sourceFile->expects($this->any())->method('getStorage')->will($this->returnValue($sourceStorage));
		/** @var $driver \TYPO3\CMS\Core\Resource\Driver\LocalDriver */
		$driver = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Driver\\LocalDriver', array(), array(array('basePath' => $this->getMountRootUrl())));
		$driver->expects($this->once())->method('addFile')->with($localFilePath, $targetFolder, $this->equalTo('file.ext'));
		/** @var $fixture \TYPO3\CMS\Core\Resource\ResourceStorage */
		$fixture = $this->getMock('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', array('checkFileCopyPermissions'), array($driver, array('configuration' => $storageConfiguration)));
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
		$this->fixture->addFileMount('/mountFolder');
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
		$mockedDriver->expects($this->once())->method('createFolder')->with($this->equalTo('newFolder'), $this->equalTo($mockedParentFolder))->will($this->returnValue(TRUE));
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

	/**
	 * @test
	 */
	public function getFileListHandsOverRecursiveFALSEifNotExplicitlySet() {
		$this->prepareFixture(array());
		$driver = $this->createDriverMock(array('basePath' => $this->getMountRootUrl()), $this->fixture, array('getFileList'));
		$driver->expects($this->once())
			->method('getFileList')
			->with($this->anything(), $this->anything(), $this->anything(), $this->anything(), $this->anything(), FALSE)
			->will($this->returnValue(array()));
		$this->fixture->getFileList('/');
	}

	/**
	 * @test
	 */
	public function getFileListHandsOverRecursiveTRUEifSet() {

		$this->prepareFixture(array());
		$driver = $this->createDriverMock(array('basePath' => $this->getMountRootUrl()), $this->fixture, array('getFileList'));
		$driver->expects($this->once())
			->method('getFileList')
			->with($this->anything(), $this->anything(), $this->anything(), $this->anything(), $this->anything(), TRUE)
			->will($this->returnValue(array()));
		$this->fixture->getFileList('/', 0, 0, TRUE, TRUE, TRUE);
	}

	/**
	 * @test
	 */
	public function getRoleReturnsDefaultForRegularFolders() {
		$folderIdentifier = uniqid();
		$this->addToMount(array(
			$folderIdentifier => array()
		));
		$this->prepareFixture(array());

		$role = $this->fixture->getRole($this->getSimpleFolderMock('/' . $folderIdentifier . '/'));

		$this->assertSame(\TYPO3\CMS\Core\Resource\FolderInterface::ROLE_DEFAULT, $role);
	}

	/**
	 * @test
	 */
	public function getRoleReturnsCorrectValueForDefaultProcessingFolder() {
		$this->prepareFixture(array());

		$role = $this->fixture->getRole($this->getSimpleFolderMock('/' . ResourceStorage::DEFAULT_ProcessingFolder . '/'));

		$this->assertSame(\TYPO3\CMS\Core\Resource\FolderInterface::ROLE_PROCESSING, $role);
	}

	/**
	 * @test
	 */
	public function getRoleReturnsCorrectValueForConfiguredProcessingFolder() {
		$folderIdentifier = uniqid();
		$this->addToMount(array(
			$folderIdentifier => array()
		));
		$this->prepareFixture(array(), FALSE, NULL, array('processingfolder' => '/' . $folderIdentifier . '/'));

		$role = $this->fixture->getRole($this->getSimpleFolderMock('/' . $folderIdentifier . '/'));

		$this->assertSame(\TYPO3\CMS\Core\Resource\FolderInterface::ROLE_PROCESSING, $role);
	}

	/**
	 * Data provider for fetchFolderListFromDriverReturnsFolderWithoutProcessedFolder function
	 */
	public function fetchFolderListFromDriverReturnsFolderWithoutProcessedFolderDataProvider() {
		return array(
			'Empty folderList returned' => array(
				'path' => '/',
				'processingFolder' => '_processed_',
				'folderList' => array(),
				'expectedItems' => array()
			),
			'Empty _processed_ folder' => array(
				'path' => '/',
				'processingFolder' => '',
				'folderList' => array(
					'_processed_' => array(),
					'_temp_' => array(),
					'user_upload' => array()
				),
				'expectedItems' => array(
					'user_upload' => array(),
					'_temp_' => array()
				)
			),
			'_processed_ folder not in folder list' => array(
				'path' => '/',
				'processingFolder' => '_processed_',
				'folderList' => array(
					'_temp_' => array()
				),
				'expectedItems' => array(
					'_temp_' => array()
				)
			),
			'_processed_ folder on root level' => array(
				'path' => '/',
				'processingFolder' => '_processed_',
				'folderList' => array(
					'_processed_' => array(),
					'_temp_' => array(),
					'user_upload' => array()
				),
				'expectedItems' => array(
					'user_upload' => array(),
					'_temp_' => array()
				)
			),
			'_processed_ folder on second level' => array(
				'path' => 'Public/',
				'processingFolder' => 'Public/_processed_',
				'folderList' => array(
					'_processed_' => array(),
					'_temp_' => array(),
					'user_upload' => array()
				),
				'expectedItems' => array(
					'user_upload' => array(),
					'_temp_' => array()
				)
			),
			'_processed_ folder on third level' => array(
				'path' => 'Public/Files/',
				'processingFolder' => 'Public/Files/_processed_',
				'folderList' => array(
					'_processed_' => array(),
					'_temp_' => array(),
					'user_upload' => array()
				),
				'expectedItems' => array(
					'user_upload' => array(),
					'_temp_' => array()
				)
			)
		);
	}

	/**
	 * @test
	 * @dataProvider fetchFolderListFromDriverReturnsFolderWithoutProcessedFolderDataProvider
	 */
	public function fetchFolderListFromDriverReturnsFolderWithoutProcessedFolder($path, $processingFolder, $folderList, $expectedItems) {
		$driverMock = $this->createDriverMock(array(), NULL, array('getFolderList', 'folderExists'));
		$driverMock->expects($this->once())->method('getFolderList')->will($this->returnValue($folderList));
		if (!empty($expectedItems)) {
			// This function is called only if there were any folders retrieved
			$driverMock->expects($this->once())->method('folderExists')->will($this->returnValue(TRUE));
		}

		$this->prepareFixture(array(), FALSE, $driverMock, array('processingfolder' => $processingFolder));

		$this->assertSame($expectedItems, $this->fixture->fetchFolderListFromDriver($path));
	}
}

?>
