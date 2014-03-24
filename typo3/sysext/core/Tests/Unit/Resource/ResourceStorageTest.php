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
	protected $fixture;

	public function setUp() {
		parent::setUp();
		$this->singletonInstances = \TYPO3\CMS\Core\Utility\GeneralUtility::getSingletonInstances();
		\TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance(
			'TYPO3\\CMS\\Core\\Resource\\FileRepository',
			$this->getMock('TYPO3\\CMS\\Core\\Resource\\FileRepository')
		);

	}

	public function tearDown() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::resetSingletonInstances($this->singletonInstances);
		parent::tearDown();
	}

	/**
	 * Prepare fixture
	 *
	 * @param array $configuration
	 * @param boolean $mockPermissionChecks
	 * @return void
	 */
	protected function prepareFixture($configuration, $mockPermissionChecks = FALSE, $driverObject = NULL, array $storageRecord = array()) {
		$permissionMethods = array('assureFileAddPermissions', 'checkFolderActionPermission', 'checkFileActionPermission', 'checkUserActionPermission', 'checkFileExtensionPermission', 'isWithinFileMountBoundaries');
		$mockedMethods = array();
		$configuration = $this->convertConfigurationArrayToFlexformXml($configuration);
		$overruleArray = array('configuration' => $configuration);
		\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($storageRecord, $overruleArray);
		if ($driverObject == NULL) {
			/** @var $mockedDriver \TYPO3\CMS\Core\Resource\Driver\AbstractDriver */
			$driverObject = $this->getMockForAbstractClass('TYPO3\\CMS\\Core\\Resource\\Driver\\AbstractDriver', array(), '', FALSE);
		}
		if ($mockPermissionChecks) {
			$mockedMethods = $permissionMethods;
		}
		$mockedMethods[] = 'getIndexer';

		$this->fixture = $this->getMock('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', $mockedMethods, array($driverObject, $storageRecord), '', FALSE);
		$this->fixture->expects($this->any())->method('getIndexer')->will($this->returnValue($this->getMock('TYPO3\CMS\Core\Resource\Index\Indexer', array(), array(), '', FALSE)));
		foreach ($permissionMethods as $method) {
			$this->fixture->expects($this->any())->method($method)->will($this->returnValue(TRUE));
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
		if ($storageObject !== NULL) {
			$storageObject->setDriver($driver);
		}
		$driver->setStorageUid(6);
		$driver->processConfiguration();
		$driver->initialize();
		return $driver;
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
	 * @TODO: Rewrite or move to functional suite
	 */
	public function capabilitiesOfStorageObjectAreCorrectlySet(array $capabilities) {
		$this->markTestSkipped('This test does way to much and is mocked incomplete. Skipped for now.');
		$storageRecord = array(
			'is_public' => $capabilities['public'],
			'is_writable' => $capabilities['writable'],
			'is_browsable' => $capabilities['browsable'],
			'is_online' => TRUE
		);
		$mockedDriver = $this->createDriverMock(
			array(
				'pathType' => 'relative',
				'basePath' => 'fileadmin/',
			),
			$this->fixture,
			NULL
		);
		$this->prepareFixture(array(), FALSE, $mockedDriver, $storageRecord);
		$this->assertEquals($capabilities['public'], $this->fixture->isPublic(), 'Capability "public" is not correctly set.');
		$this->assertEquals($capabilities['writable'], $this->fixture->isWritable(), 'Capability "writable" is not correctly set.');
		$this->assertEquals($capabilities['browsable'], $this->fixture->isBrowsable(), 'Capability "browsable" is not correctly set.');
	}

	/**
	 * @test
	 * @TODO: Rewrite or move to functional suite
	 */
	public function fileAndFolderListFiltersAreInitializedWithDefaultFilters() {
		$this->markTestSkipped('This test does way to much and is mocked incomplete. Skipped for now.');
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
	public function getPublicUrlReturnsNullIfStorageIsNotOnline() {
		/** @var $driver \TYPO3\CMS\Core\Resource\Driver\LocalDriver */
		$driver = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Driver\\LocalDriver', array(), array(array('basePath' => $this->getMountRootUrl())));
		/** @var $fixture \TYPO3\CMS\Core\Resource\ResourceStorage */
		$fixture = $this->getMock('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', array('isOnline'), array($driver, array('configuration' => array())));
		$fixture->expects($this->once())->method('isOnline')->will($this->returnValue(FALSE));

		$sourceFileIdentifier = '/sourceFile.ext';
		$sourceFile = $this->getSimpleFileMock($sourceFileIdentifier);
		$result = $fixture->getPublicUrl($sourceFile);
		$this->assertSame($result, NULL);
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
				TRUE
			),
			'read action on unreadable folder' => array(
				'read',
				array('r' => FALSE, 'w' => TRUE),
				FALSE
			),
			'write action on read-only folder' => array(
				'write',
				array('r' => TRUE, 'w' => FALSE),
				FALSE
			)
		);
	}

	/**
	 * @test
	 * @dataProvider checkFolderPermissionsFilesystemPermissionsDataProvider
	 * @param string $action 'read' or 'write'
	 * @param array $permissionsFromDriver The permissions as returned from the driver
	 * @param boolean $expectedResult
	 */
	public function checkFolderPermissionsRespectsFilesystemPermissions($action, $permissionsFromDriver, $expectedResult) {
		$mockedDriver = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Driver\\LocalDriver');
		$mockedDriver->expects($this->any())->method('getPermissions')->will($this->returnValue($permissionsFromDriver));
		$mockedFolder = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Folder', array(), array(), '', FALSE);
			// Let all other checks pass
		/** @var $fixture \TYPO3\CMS\Core\Resource\ResourceStorage */
		$fixture = $this->getMock('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', array('isWritable', 'isBrowsable', 'checkUserActionPermission'), array($mockedDriver, array()), '', FALSE);
		$fixture->expects($this->any())->method('isWritable')->will($this->returnValue(TRUE));
		$fixture->expects($this->any())->method('isBrowsable')->will($this->returnValue(TRUE));
		$fixture->expects($this->any())->method('checkUserActionPermission')->will($this->returnValue(TRUE));
		$fixture->setDriver($mockedDriver);

		$this->assertSame($expectedResult, $fixture->checkFolderActionPermission($action, $mockedFolder));
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
		$this->fixture->setEvaluatePermissions(TRUE);
		$this->fixture->setUserPermissions(array('readFolder' => FALSE));
		$this->assertFalse($this->fixture->checkUserActionPermission('read', 'folder'));
	}

	/**
	 * @test
	 */
	public function userActionIsDisallowedIfPermissionIsNotSet() {
		$this->prepareFixture(array());
		$this->fixture->setEvaluatePermissions(TRUE);
		$this->fixture->setUserPermissions(array('readFolder' => TRUE));
		$this->assertFalse($this->fixture->checkUserActionPermission('write', 'folder'));
	}

	/**
	 * @test
	 */
	public function getEvaluatePermissionsWhenSetFalse() {
		$this->prepareFixture(array());
		$this->fixture->setEvaluatePermissions(FALSE);
		$this->assertFalse($this->fixture->getEvaluatePermissions());
	}

	/**
	 * @test
	 */
	public function getEvaluatePermissionsWhenSetTrue() {
		$this->prepareFixture(array());
		$this->fixture->setEvaluatePermissions(TRUE);
		$this->assertTrue($this->fixture->getEvaluatePermissions());
	}

	/**
	 * @test
	 * @group integration
	 * @TODO: Rewrite or move to functional suite
	 */
	public function setFileContentsUpdatesObjectProperties() {
		$this->markTestSkipped('This test does way to much and is mocked incomplete. Skipped for now.');
		$this->initializeVfs();
		$driverObject = $this->getMockForAbstractClass('TYPO3\\CMS\\Core\\Resource\\Driver\\AbstractDriver', array(), '', FALSE);
		$this->fixture = $this->getMock('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', array('getFileIndexRepository', 'checkFileActionPermission'), array($driverObject, array()));
		$this->fixture->expects($this->any())->method('checkFileActionPermission')->will($this->returnValue(TRUE));
		$fileInfo = array(
			'storage' => 'A',
			'identifier' => 'B',
			'mtime' => 'C',
			'ctime' => 'D',
			'mimetype' => 'E',
			'size' => 'F',
			'name' => 'G',
		);
		$newProperties = array(
			'storage' => $fileInfo['storage'],
			'identifier' => $fileInfo['identifier'],
			'tstamp' => $fileInfo['mtime'],
			'crdate' => $fileInfo['ctime'],
			'mime_type' => $fileInfo['mimetype'],
			'size' => $fileInfo['size'],
			'name' => $fileInfo['name']
		);
		$hash = 'asdfg';
		$driver = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Driver\\LocalDriver', array(), array(array('basePath' => $this->getMountRootUrl())));
		$driver->expects($this->once())->method('getFileInfoByIdentifier')->will($this->returnValue($fileInfo));
		$driver->expects($this->once())->method('hash')->will($this->returnValue($hash));
		$this->fixture->setDriver($driver);
		$indexFileRepositoryMock = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Index\\FileIndexRepository');
		$this->fixture->expects($this->any())->method('getFileIndexRepository')->will($this->returnValue($indexFileRepositoryMock));
		$mockedFile = $this->getMock('TYPO3\\CMS\\Core\\Resource\\File', array(), array(), '', FALSE);
		$mockedFile->expects($this->any())->method('getIdentifier')->will($this->returnValue($fileInfo['identifier']));
		// called by indexer because the properties are updated
		$this->fixture->expects($this->any())->method('getFileInfoByIdentifier')->will($this->returnValue($newProperties));
		$mockedFile->expects($this->any())->method('getStorage')->will($this->returnValue($this->fixture));
		$mockedFile->expects($this->any())->method('getProperties')->will($this->returnValue(array_keys($fileInfo)));
		$mockedFile->expects($this->any())->method('getUpdatedProperties')->will($this->returnValue(array_keys($newProperties)));
		// do not update directly; that's up to the indexer
		$indexFileRepositoryMock->expects($this->never())->method('update');
		$this->fixture->setFileContents($mockedFile, uniqid());
	}

	/**
	 * @test
	 * @group integration
	 * @TODO: Rewrite or move to functional suite
	 */
	public function moveFileCallsDriversMethodsWithCorrectArguments() {
		$this->markTestSkipped('This test does way to much and is mocked incomplete. Skipped for now.');
		$localFilePath = '/path/to/localFile';
		$sourceFileIdentifier = '/sourceFile.ext';
		$fileInfoDummy = array(
			'storage' => 'A',
			'identifier' => 'B',
			'mtime' => 'C',
			'ctime' => 'D',
			'mimetype' => 'E',
			'size' => 'F',
			'name' => 'G',
		);
		$this->addToMount(array(
			'targetFolder' => array()
		));
		$this->initializeVfs();
		$targetFolder = $this->getSimpleFolderMock('/targetFolder/');
		$sourceDriver = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Driver\\LocalDriver');
		$sourceDriver->expects($this->once())->method('deleteFile')->with($this->equalTo($sourceFileIdentifier));
		$configuration = $this->convertConfigurationArrayToFlexformXml(array());
		$sourceStorage = new \TYPO3\CMS\Core\Resource\ResourceStorage($sourceDriver, array('configuration' => $configuration));
		$sourceFile = $this->getSimpleFileMock($sourceFileIdentifier);
		$sourceFile->expects($this->once())->method('getForLocalProcessing')->will($this->returnValue($localFilePath));
		$sourceFile->expects($this->any())->method('getStorage')->will($this->returnValue($sourceStorage));
		$sourceFile->expects($this->once())->method('getUpdatedProperties')->will($this->returnValue(array_keys($fileInfoDummy)));
		$sourceFile->expects($this->once())->method('getProperties')->will($this->returnValue($fileInfoDummy));
		/** @var $driver \TYPO3\CMS\Core\Resource\Driver\LocalDriver */
		$driver = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Driver\\LocalDriver', array(), array(array('basePath' => $this->getMountRootUrl())));
		$driver->expects($this->once())->method('getFileInfoByIdentifier')->will($this->returnValue($fileInfoDummy));
		$driver->expects($this->once())->method('addFile')->with($localFilePath, '/targetFolder/', $this->equalTo('file.ext'))->will($this->returnValue('/targetFolder/file.ext'));
		/** @var $fixture \TYPO3\CMS\Core\Resource\ResourceStorage */
		$fixture = $this->getMock('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', array('assureFileMovePermissions'), array($driver, array('configuration' => $configuration)));
		$fixture->moveFile($sourceFile, $targetFolder, 'file.ext');
	}

	/**
	 * @test
	 * @group integration
	 * @TODO: Rewrite or move to functional suite
	 */
	public function storageUsesInjectedFilemountsToCheckForMountBoundaries() {
		$this->markTestSkipped('This test does way to much and is mocked incomplete. Skipped for now.');
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
	 * @test
	 * @TODO: Rewrite or move to functional suite
	 */
	public function createFolderChecksIfParentFolderExistsBeforeCreatingFolder() {
		$this->markTestSkipped('This test does way to much and is mocked incomplete. Skipped for now.');
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
	 * @expectedException \RuntimeException
	 */
	public function deleteFolderThrowsExceptionIfFolderIsNotEmptyAndRecursiveDeleteIsDisabled() {
		/** @var \TYPO3\CMS\Core\Resource\Folder|\PHPUnit_Framework_MockObject_MockObject $folderMock */
		$folderMock = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Folder', array(), array(), '', FALSE);
		/** @var \TYPO3\CMS\Core\Resource\Driver\AbstractDriver|\PHPUnit_Framework_MockObject_MockObject $driverMock */
		$driverMock = $this->getMockForAbstractClass('TYPO3\\CMS\\Core\\Resource\\Driver\\AbstractDriver');
		$driverMock->expects($this->once())->method('isFolderEmpty')->will($this->returnValue(FALSE));
		/** @var \TYPO3\CMS\Core\Resource\ResourceStorage|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $fixture */
		$fixture = $this->getAccessibleMock('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', array('checkFolderActionPermission'), array(), '', FALSE);
		$fixture->expects($this->any())->method('checkFolderActionPermission')->will($this->returnValue(TRUE));
		$fixture->_set('driver', $driverMock);
		$fixture->deleteFolder($folderMock, FALSE);
	}

	/**
	 * @test
	 * @TODO: Rewrite or move to functional suite
	 */
	public function createFolderCallsDriverForFolderCreation() {
		$this->markTestSkipped('This test does way to much and is mocked incomplete. Skipped for now.');
		$mockedParentFolder = $this->getSimpleFolderMock('/someFolder/');
		$this->prepareFixture(array(), TRUE);
		$mockedDriver = $this->createDriverMock(array(), $this->fixture);
		$mockedDriver->expects($this->once())->method('createFolder')->with($this->equalTo('newFolder'), $this->equalTo('/someFolder/'))->will($this->returnValue(TRUE));
		$mockedDriver->expects($this->once())->method('folderExists')->with($this->equalTo('/someFolder/'))->will($this->returnValue(TRUE));
		$this->fixture->createFolder('newFolder', $mockedParentFolder);
	}

	/**
	 * @test
	 * @TODO: Rewrite or move to functional suite
	 */
	public function createFolderCanRecursivelyCreateFolders() {
		$this->markTestSkipped('This test does way to much and is mocked incomplete. Skipped for now.');
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
	 * @TODO: Rewrite or move to functional suite
	 */
	public function createFolderUsesRootFolderAsParentFolderIfNotGiven() {
		$this->markTestSkipped('This test does way to much and is mocked incomplete. Skipped for now.');
		$this->prepareFixture(array(), TRUE);
		$mockedDriver = $this->createDriverMock(array(), $this->fixture);
		$mockedDriver->expects($this->once())->method('getRootLevelFolder')->with()->will($this->returnValue('/'));
		$mockedDriver->expects($this->once())->method('createFolder')->with($this->equalTo('someFolder'));
		$this->fixture->createFolder('someFolder');
	}

	/**
	 * @test
	 * @TODO: Rewrite or move to functional suite
	 */
	public function createFolderCreatesNestedStructureEvenIfPartsAlreadyExist() {
		$this->markTestSkipped('This test does way to much and is mocked incomplete. Skipped for now.');
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
	 * @TODO: Rewrite or move to functional suite
	 */
	public function getRoleReturnsDefaultForRegularFolders() {
		$this->markTestSkipped('This test does way to much and is mocked incomplete. Skipped for now.');
		$folderIdentifier = uniqid();
		$this->addToMount(array(
			$folderIdentifier => array()
		));
		$this->prepareFixture(array());

		$role = $this->fixture->getRole($this->getSimpleFolderMock('/' . $folderIdentifier . '/'));

		$this->assertSame(\TYPO3\CMS\Core\Resource\FolderInterface::ROLE_DEFAULT, $role);
	}
}