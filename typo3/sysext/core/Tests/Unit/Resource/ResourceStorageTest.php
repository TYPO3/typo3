<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource;

/*
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

use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Resource\Driver\AbstractDriver;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Resource\Index\FileIndexRepository;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\ResourceStorageInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case for ResourceStorage class
 */
class ResourceStorageTest extends BaseTestCase
{
    /**
     * @var array A backup of registered singleton instances
     */
    protected $singletonInstances = [];

    /**
     * @var ResourceStorage|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subject;

    protected function setUp()
    {
        parent::setUp();
        $this->singletonInstances = GeneralUtility::getSingletonInstances();
        /** @var FileRepository|\PHPUnit_Framework_MockObject_MockObject $fileRepositoryMock */
        $fileRepositoryMock = $this->getMock(FileRepository::class);
        GeneralUtility::setSingletonInstance(
            FileRepository::class,
            $fileRepositoryMock
        );
        $databaseMock = $this->getMock(DatabaseConnection::class);
        $databaseMock->expects($this->any())->method('exec_SELECTgetRows')->with('*', 'sys_file_storage', '1=1', '', 'name', '', 'uid')->willReturn([]);
        $GLOBALS['TYPO3_DB'] = $databaseMock;
    }

    protected function tearDown()
    {
        GeneralUtility::resetSingletonInstances($this->singletonInstances);
        parent::tearDown();
    }

    /**
     * Prepare ResourceStorage
     *
     * @param array $configuration
     * @param bool $mockPermissionChecks
     * @param AbstractDriver|\PHPUnit_Framework_MockObject_MockObject $driverObject
     * @param array $storageRecord
     */
    protected function prepareSubject(array $configuration, $mockPermissionChecks = false, AbstractDriver $driverObject = null, array $storageRecord = [])
    {
        $permissionMethods = ['assureFileAddPermissions', 'checkFolderActionPermission', 'checkFileActionPermission', 'checkUserActionPermission', 'checkFileExtensionPermission', 'isWithinFileMountBoundaries'];
        $mockedMethods = [];
        $configuration = $this->convertConfigurationArrayToFlexformXml($configuration);
        $overruleArray = ['configuration' => $configuration];
        ArrayUtility::mergeRecursiveWithOverrule($storageRecord, $overruleArray);
        if ($driverObject == null) {
            $driverObject = $this->getMockForAbstractClass(AbstractDriver::class, [], '', false);
        }
        if ($mockPermissionChecks) {
            $mockedMethods = $permissionMethods;
        }
        $mockedMethods[] = 'getIndexer';

        $this->subject = $this->getMock(ResourceStorage::class, $mockedMethods, [$driverObject, $storageRecord]);
        $this->subject->expects($this->any())->method('getIndexer')->will($this->returnValue($this->getMock(\TYPO3\CMS\Core\Resource\Index\Indexer::class, [], [], '', false)));
        foreach ($permissionMethods as $method) {
            $this->subject->expects($this->any())->method($method)->will($this->returnValue(true));
        }
    }

    /**
     * Converts a simple configuration array into a FlexForm data structure serialized as XML
     *
     * @param array $configuration
     * @return string
     * @see GeneralUtility::array2xml()
     */
    protected function convertConfigurationArrayToFlexformXml(array $configuration)
    {
        $flexFormArray = ['data' => ['sDEF' => ['lDEF' => []]]];
        foreach ($configuration as $key => $value) {
            $flexFormArray['data']['sDEF']['lDEF'][$key] = ['vDEF' => $value];
        }
        $configuration = GeneralUtility::array2xml($flexFormArray);
        return $configuration;
    }

    /**
     * Creates a driver fixture object, optionally using a given mount object.
     *
     * IMPORTANT: Call this only after setting up the virtual file system (with the addTo* methods)!
     *
     * @param $driverConfiguration
     * @param ResourceStorage $storageObject
     * @param array $mockedDriverMethods
     * @return \TYPO3\CMS\Core\Resource\Driver\LocalDriver|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createDriverMock($driverConfiguration, ResourceStorage $storageObject = null, $mockedDriverMethods = [])
    {
        $this->initializeVfs();

        if (!isset($driverConfiguration['basePath'])) {
            $driverConfiguration['basePath'] = $this->getMountRootUrl();
        }

        if ($mockedDriverMethods === null) {
            $driver = new LocalDriver($driverConfiguration);
        } else {
            // We are using the LocalDriver here because PHPUnit can't mock concrete methods in abstract classes, so
                // when using the AbstractDriver we would be in trouble when wanting to mock away some concrete method
            $driver = $this->getMock(LocalDriver::class, $mockedDriverMethods, [$driverConfiguration]);
        }
        if ($storageObject !== null) {
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
    public function fileExtensionPermissionDataProvider()
    {
        return [
            'Permissions evaluated, extension not in allowed list' => [
                'fileName' => 'foo.txt',
                'configuration' => ['allow' => 'jpg'],
                'evaluatePermissions' => true,
                'isAllowed' => true,
            ],
            'Permissions evaluated, extension in deny list' => [
                'fileName' => 'foo.txt',
                'configuration' => ['deny' => 'txt'],
                'evaluatePermissions' => true,
                'isAllowed' => false,
            ],
            'Permissions not evaluated, extension is php' => [
                'fileName' => 'foo.php',
                'configuration' => [],
                'evaluatePermissions' => false,
                'isAllowed' => false,
            ],
            'Permissions evaluated, extension is php' => [
                'fileName' => 'foo.php',
                // It is not possible to allow php file extension through configuration
                'configuration' => ['allow' => 'php'],
                'evaluatePermissions' => true,
                'isAllowed' => false,
            ],
        ];
    }

    /**
     * @param string $fileName
     * @param array $configuration
     * @param bool $evaluatePermissions
     * @param bool $isAllowed
     * @test
     * @dataProvider fileExtensionPermissionDataProvider
     */
    public function fileExtensionPermissionIsWorkingCorrectly($fileName, array $configuration, $evaluatePermissions, $isAllowed)
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']['webspace'] = $configuration;
        $driverMock = $this->getMockForAbstractClass(AbstractDriver::class, [], '', false);
        $subject = $this->getAccessibleMock(ResourceStorage::class, ['dummy'], [$driverMock, []]);
        $subject->_set('evaluatePermissions', $evaluatePermissions);
        $this->assertSame($isAllowed, $subject->_call('checkFileExtensionPermission', $fileName));
    }

    /**
     * @return array
     */
    public function isWithinFileMountBoundariesDataProvider()
    {
        return [
            'Access to file in ro file mount denied for write request' => [
                '$fileIdentifier' => '/fooBaz/bar.txt',
                '$fileMountFolderIdentifier' => '/fooBaz/',
                '$isFileMountReadOnly' => true,
                '$checkWriteAccess' => true,
                '$expectedResult' => false,
            ],
            'Access to file in ro file mount allowed for read request' => [
                '$fileIdentifier' => '/fooBaz/bar.txt',
                '$fileMountFolderIdentifier' => '/fooBaz/',
                '$isFileMountReadOnly' => true,
                '$checkWriteAccess' => false,
                '$expectedResult' => true,
            ],
            'Access to file in rw file mount allowed for write request' => [
                '$fileIdentifier' => '/fooBaz/bar.txt',
                '$fileMountFolderIdentifier' => '/fooBaz/',
                '$isFileMountReadOnly' => false,
                '$checkWriteAccess' => true,
                '$expectedResult' => true,
            ],
            'Access to file in rw file mount allowed for read request' => [
                '$fileIdentifier' => '/fooBaz/bar.txt',
                '$fileMountFolderIdentifier' => '/fooBaz/',
                '$isFileMountReadOnly' => false,
                '$checkWriteAccess' => false,
                '$expectedResult' => true,
            ],
            'Access to file not in file mount denied for write request' => [
                '$fileIdentifier' => '/fooBaz/bar.txt',
                '$fileMountFolderIdentifier' => '/barBaz/',
                '$isFileMountReadOnly' => false,
                '$checkWriteAccess' => true,
                '$expectedResult' => false,
            ],
            'Access to file not in file mount denied for read request' => [
                '$fileIdentifier' => '/fooBaz/bar.txt',
                '$fileMountFolderIdentifier' => '/barBaz/',
                '$isFileMountReadOnly' => false,
                '$checkWriteAccess' => false,
                '$expectedResult' => false,
            ],
        ];
    }

    /**
     * @param string $fileIdentifier
     * @param string $fileMountFolderIdentifier
     * @param bool $isFileMountReadOnly
     * @param bool $checkWriteAccess
     * @param bool $expectedResult
     * @throws \TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException
     * @test
     * @dataProvider isWithinFileMountBoundariesDataProvider
     */
    public function isWithinFileMountBoundariesRespectsReadOnlyFileMounts($fileIdentifier, $fileMountFolderIdentifier, $isFileMountReadOnly, $checkWriteAccess, $expectedResult)
    {
        /** @var AbstractDriver|\PHPUnit_Framework_MockObject_MockObject $driverMock */
        $driverMock = $this->getMockForAbstractClass(AbstractDriver::class, [], '', false);
        $driverMock->expects($this->any())
            ->method('getFolderInfoByIdentifier')
            ->willReturnCallback(function ($identifier) use ($isFileMountReadOnly) {
                return [
                    'identifier' => $identifier,
                    'name' => trim($identifier, '/'),
                ];
            });
        $driverMock->expects($this->any())
            ->method('isWithin')
            ->willReturnCallback(function ($folderIdentifier, $fileIdentifier) {
                if ($fileIdentifier === ResourceStorageInterface::DEFAULT_ProcessingFolder . '/') {
                    return false;
                } else {
                    return strpos($fileIdentifier, $folderIdentifier) === 0;
                }
            });
        $this->prepareSubject([], false, $driverMock);
        $fileMock = $this->getSimpleFileMock($fileIdentifier);
        $this->subject->setEvaluatePermissions(true);
        $this->subject->addFileMount('/' . $this->getUniqueId('random') . '/', ['read_only' => false]);
        $this->subject->addFileMount($fileMountFolderIdentifier, ['read_only' => $isFileMountReadOnly]);
        $this->subject->addFileMount('/' . $this->getUniqueId('random') . '/', ['read_only' => false]);
        $this->assertSame($expectedResult, $this->subject->isWithinFileMountBoundaries($fileMock, $checkWriteAccess));
    }

    /**
     * @return array
     */
    public function capabilitiesDataProvider()
    {
        return [
            'only public' => [
                [
                    'public' => true,
                    'writable' => false,
                    'browsable' => false
                ]
            ],
            'only writable' => [
                [
                    'public' => false,
                    'writable' => true,
                    'browsable' => false
                ]
            ],
            'only browsable' => [
                [
                    'public' => false,
                    'writable' => false,
                    'browsable' => true
                ]
            ],
            'all capabilities' => [
                [
                    'public' => true,
                    'writable' => true,
                    'browsable' => true
                ]
            ],
            'none' => [
                [
                    'public' => false,
                    'writable' => false,
                    'browsable' => false
                ]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider capabilitiesDataProvider
     * @TODO: Rewrite or move to functional suite
     */
    public function capabilitiesOfStorageObjectAreCorrectlySet(array $capabilities)
    {
        $this->markTestSkipped('This test does way to much and is mocked incomplete. Skipped for now.');
        $storageRecord = [
            'is_public' => $capabilities['public'],
            'is_writable' => $capabilities['writable'],
            'is_browsable' => $capabilities['browsable'],
            'is_online' => true
        ];
        $mockedDriver = $this->createDriverMock(
            [
                'pathType' => 'relative',
                'basePath' => 'fileadmin/',
            ],
            $this->subject,
            null
        );
        $this->prepareSubject([], false, $mockedDriver, $storageRecord);
        $this->assertEquals($capabilities['public'], $this->subject->isPublic(), 'Capability "public" is not correctly set.');
        $this->assertEquals($capabilities['writable'], $this->subject->isWritable(), 'Capability "writable" is not correctly set.');
        $this->assertEquals($capabilities['browsable'], $this->subject->isBrowsable(), 'Capability "browsable" is not correctly set.');
    }

    /**
     * @test
     * @TODO: Rewrite or move to functional suite
     */
    public function fileAndFolderListFiltersAreInitializedWithDefaultFilters()
    {
        $this->markTestSkipped('This test does way to much and is mocked incomplete. Skipped for now.');
        $this->prepareSubject([]);
        $this->assertEquals($GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['defaultFilterCallbacks'], $this->subject->getFileAndFolderNameFilters());
    }

    /**
     * @test
     */
    public function addFileFailsIfFileDoesNotExist()
    {
        /** @var Folder|\PHPUnit_Framework_MockObject_MockObject $mockedFolder */
        $mockedFolder = $this->getMock(Folder::class, [], [], '', false);
        $this->setExpectedException('InvalidArgumentException', '', 1319552745);
        $this->prepareSubject([]);
        $this->subject->addFile('/some/random/file', $mockedFolder);
    }

    /**
     * @test
     */
    public function getPublicUrlReturnsNullIfStorageIsNotOnline()
    {
        /** @var $driver LocalDriver|\PHPUnit_Framework_MockObject_MockObject */
        $driver = $this->getMock(LocalDriver::class, [], [['basePath' => $this->getMountRootUrl()]]);
        /** @var $subject ResourceStorage|\PHPUnit_Framework_MockObject_MockObject */
        $subject = $this->getMock(ResourceStorage::class, ['isOnline'], [$driver, ['configuration' => []]]);
        $subject->expects($this->once())->method('isOnline')->will($this->returnValue(false));

        $sourceFileIdentifier = '/sourceFile.ext';
        $sourceFile = $this->getSimpleFileMock($sourceFileIdentifier);
        $result = $subject->getPublicUrl($sourceFile);
        $this->assertSame($result, null);
    }

    /**
     * Data provider for checkFolderPermissionsRespectsFilesystemPermissions
     *
     * @return array
     */
    public function checkFolderPermissionsFilesystemPermissionsDataProvider()
    {
        return [
            'read action on readable/writable folder' => [
                'read',
                ['r' => true, 'w' => true],
                true
            ],
            'read action on unreadable folder' => [
                'read',
                ['r' => false, 'w' => true],
                false
            ],
            'write action on read-only folder' => [
                'write',
                ['r' => true, 'w' => false],
                false
            ]
        ];
    }

    /**
     * @test
     * @dataProvider checkFolderPermissionsFilesystemPermissionsDataProvider
     * @param string $action 'read' or 'write'
     * @param array $permissionsFromDriver The permissions as returned from the driver
     * @param bool $expectedResult
     */
    public function checkFolderPermissionsRespectsFilesystemPermissions($action, $permissionsFromDriver, $expectedResult)
    {
        /** @var $mockedDriver LocalDriver|\PHPUnit_Framework_MockObject_MockObject */
        $mockedDriver = $this->getMock(LocalDriver::class);
        $mockedDriver->expects($this->any())->method('getPermissions')->will($this->returnValue($permissionsFromDriver));
        /** @var $mockedFolder Folder|\PHPUnit_Framework_MockObject_MockObject  */
        $mockedFolder = $this->getMock(Folder::class, [], [], '', false);
            // Let all other checks pass
        /** @var $subject ResourceStorage|\PHPUnit_Framework_MockObject_MockObject */
        $subject = $this->getMock(ResourceStorage::class, ['isWritable', 'isBrowsable', 'checkUserActionPermission'], [$mockedDriver, []], '', false);
        $subject->expects($this->any())->method('isWritable')->will($this->returnValue(true));
        $subject->expects($this->any())->method('isBrowsable')->will($this->returnValue(true));
        $subject->expects($this->any())->method('checkUserActionPermission')->will($this->returnValue(true));
        $subject->setDriver($mockedDriver);

        $this->assertSame($expectedResult, $subject->checkFolderActionPermission($action, $mockedFolder));
    }

    /**
     * @test
     */
    public function checkUserActionPermissionsAlwaysReturnsTrueIfNoUserPermissionsAreSet()
    {
        $this->prepareSubject([]);
        $this->assertTrue($this->subject->checkUserActionPermission('read', 'folder'));
    }

    /**
     * @test
     */
    public function checkUserActionPermissionReturnsFalseIfPermissionIsSetToZero()
    {
        $this->prepareSubject([]);
        $this->subject->setUserPermissions(['readFolder' => true, 'writeFile' => true]);
        $this->assertTrue($this->subject->checkUserActionPermission('read', 'folder'));
    }

    public function checkUserActionPermission_arbitraryPermissionDataProvider()
    {
        return [
            'all lower cased' => [
                ['readFolder' => true],
                'read',
                'folder'
            ],
            'all upper case' => [
                ['readFolder' => true],
                'READ',
                'FOLDER'
            ],
            'mixed case' => [
                ['readFolder' => true],
                'ReaD',
                'FoLdEr'
            ]
        ];
    }

    /**
     * @param array $permissions
     * @param string $action
     * @param string $type
     * @test
     * @dataProvider checkUserActionPermission_arbitraryPermissionDataProvider
     */
    public function checkUserActionPermissionAcceptsArbitrarilyCasedArguments(array $permissions, $action, $type)
    {
        $this->prepareSubject([]);
        $this->subject->setUserPermissions($permissions);
        $this->assertTrue($this->subject->checkUserActionPermission($action, $type));
    }

    /**
     * @test
     */
    public function userActionIsDisallowedIfPermissionIsSetToFalse()
    {
        $this->prepareSubject([]);
        $this->subject->setEvaluatePermissions(true);
        $this->subject->setUserPermissions(['readFolder' => false]);
        $this->assertFalse($this->subject->checkUserActionPermission('read', 'folder'));
    }

    /**
     * @test
     */
    public function userActionIsDisallowedIfPermissionIsNotSet()
    {
        $this->prepareSubject([]);
        $this->subject->setEvaluatePermissions(true);
        $this->subject->setUserPermissions(['readFolder' => true]);
        $this->assertFalse($this->subject->checkUserActionPermission('write', 'folder'));
    }

    /**
     * @test
     */
    public function getEvaluatePermissionsWhenSetFalse()
    {
        $this->prepareSubject([]);
        $this->subject->setEvaluatePermissions(false);
        $this->assertFalse($this->subject->getEvaluatePermissions());
    }

    /**
     * @test
     */
    public function getEvaluatePermissionsWhenSetTrue()
    {
        $this->prepareSubject([]);
        $this->subject->setEvaluatePermissions(true);
        $this->assertTrue($this->subject->getEvaluatePermissions());
    }

    /**
     * @test
     * @group integration
     * @TODO: Rewrite or move to functional suite
     */
    public function setFileContentsUpdatesObjectProperties()
    {
        $this->markTestSkipped('This test does way to much and is mocked incomplete. Skipped for now.');
        $this->initializeVfs();
        $driverObject = $this->getMockForAbstractClass(AbstractDriver::class, [], '', false);
        $this->subject = $this->getMock(ResourceStorage::class, ['getFileIndexRepository', 'checkFileActionPermission'], [$driverObject, []]);
        $this->subject->expects($this->any())->method('checkFileActionPermission')->will($this->returnValue(true));
        $fileInfo = [
            'storage' => 'A',
            'identifier' => 'B',
            'mtime' => 'C',
            'ctime' => 'D',
            'mimetype' => 'E',
            'size' => 'F',
            'name' => 'G',
        ];
        $newProperties = [
            'storage' => $fileInfo['storage'],
            'identifier' => $fileInfo['identifier'],
            'tstamp' => $fileInfo['mtime'],
            'crdate' => $fileInfo['ctime'],
            'mime_type' => $fileInfo['mimetype'],
            'size' => $fileInfo['size'],
            'name' => $fileInfo['name']
        ];
        $hash = 'asdfg';
        /** @var $mockedDriver LocalDriver|\PHPUnit_Framework_MockObject_MockObject */
        $mockedDriver = $this->getMock(LocalDriver::class, [], [['basePath' => $this->getMountRootUrl()]]);
        $mockedDriver->expects($this->once())->method('getFileInfoByIdentifier')->will($this->returnValue($fileInfo));
        $mockedDriver->expects($this->once())->method('hash')->will($this->returnValue($hash));
        $this->subject->setDriver($mockedDriver);
        $indexFileRepositoryMock = $this->getMock(FileIndexRepository::class);
        $this->subject->expects($this->any())->method('getFileIndexRepository')->will($this->returnValue($indexFileRepositoryMock));
        /** @var $mockedFile File|\PHPUnit_Framework_MockObject_MockObject */
        $mockedFile = $this->getMock(File::class, [], [], '', false);
        $mockedFile->expects($this->any())->method('getIdentifier')->will($this->returnValue($fileInfo['identifier']));
        // called by indexer because the properties are updated
        $this->subject->expects($this->any())->method('getFileInfoByIdentifier')->will($this->returnValue($newProperties));
        $mockedFile->expects($this->any())->method('getStorage')->will($this->returnValue($this->subject));
        $mockedFile->expects($this->any())->method('getProperties')->will($this->returnValue(array_keys($fileInfo)));
        $mockedFile->expects($this->any())->method('getUpdatedProperties')->will($this->returnValue(array_keys($newProperties)));
        // do not update directly; that's up to the indexer
        $indexFileRepositoryMock->expects($this->never())->method('update');
        $this->subject->setFileContents($mockedFile, $this->getUniqueId());
    }

    /**
     * @test
     * @group integration
     * @TODO: Rewrite or move to functional suite
     */
    public function moveFileCallsDriversMethodsWithCorrectArguments()
    {
        $this->markTestSkipped('This test does way to much and is mocked incomplete. Skipped for now.');
        $localFilePath = '/path/to/localFile';
        $sourceFileIdentifier = '/sourceFile.ext';
        $fileInfoDummy = [
            'storage' => 'A',
            'identifier' => 'B',
            'mtime' => 'C',
            'ctime' => 'D',
            'mimetype' => 'E',
            'size' => 'F',
            'name' => 'G',
        ];
        $this->addToMount([
            'targetFolder' => []
        ]);
        $this->initializeVfs();
        $targetFolder = $this->getSimpleFolderMock('/targetFolder/');
        /** @var $sourceDriver LocalDriver|\PHPUnit_Framework_MockObject_MockObject */
        $sourceDriver = $this->getMock(LocalDriver::class);
        $sourceDriver->expects($this->once())->method('deleteFile')->with($this->equalTo($sourceFileIdentifier));
        $configuration = $this->convertConfigurationArrayToFlexformXml([]);
        $sourceStorage = new ResourceStorage($sourceDriver, ['configuration' => $configuration]);
        $sourceFile = $this->getSimpleFileMock($sourceFileIdentifier);
        $sourceFile->expects($this->once())->method('getForLocalProcessing')->will($this->returnValue($localFilePath));
        $sourceFile->expects($this->any())->method('getStorage')->will($this->returnValue($sourceStorage));
        $sourceFile->expects($this->once())->method('getUpdatedProperties')->will($this->returnValue(array_keys($fileInfoDummy)));
        $sourceFile->expects($this->once())->method('getProperties')->will($this->returnValue($fileInfoDummy));
        /** @var $mockedDriver \TYPO3\CMS\Core\Resource\Driver\LocalDriver|\PHPUnit_Framework_MockObject_MockObject */
        $mockedDriver = $this->getMock(LocalDriver::class, [], [['basePath' => $this->getMountRootUrl()]]);
        $mockedDriver->expects($this->once())->method('getFileInfoByIdentifier')->will($this->returnValue($fileInfoDummy));
        $mockedDriver->expects($this->once())->method('addFile')->with($localFilePath, '/targetFolder/', $this->equalTo('file.ext'))->will($this->returnValue('/targetFolder/file.ext'));
        /** @var $subject ResourceStorage */
        $subject = $this->getMock(ResourceStorage::class, ['assureFileMovePermissions'], [$mockedDriver, ['configuration' => $configuration]]);
        $subject->moveFile($sourceFile, $targetFolder, 'file.ext');
    }

    /**
     * @test
     * @group integration
     * @TODO: Rewrite or move to functional suite
     */
    public function storageUsesInjectedFilemountsToCheckForMountBoundaries()
    {
        $this->markTestSkipped('This test does way to much and is mocked incomplete. Skipped for now.');
        $mockedFile = $this->getSimpleFileMock('/mountFolder/file');
        $this->addToMount([
            'mountFolder' => [
                'file' => 'asdfg'
            ]
        ]);
        $mockedDriver = $this->createDriverMock(['basePath' => $this->getMountRootUrl()], null, null);
        $this->initializeVfs();
        $this->prepareSubject([], null, $mockedDriver);
        $this->subject->addFileMount('/mountFolder');
        $this->assertEquals(1, count($this->subject->getFileMounts()));
        $this->subject->isWithinFileMountBoundaries($mockedFile);
    }

    /**
     * @test
     * @TODO: Rewrite or move to functional suite
     */
    public function createFolderChecksIfParentFolderExistsBeforeCreatingFolder()
    {
        $this->markTestSkipped('This test does way to much and is mocked incomplete. Skipped for now.');
        $mockedParentFolder = $this->getSimpleFolderMock('/someFolder/');
        $mockedDriver = $this->createDriverMock([]);
        $mockedDriver->expects($this->once())->method('folderExists')->with($this->equalTo('/someFolder/'))->will($this->returnValue(true));
        $mockedDriver->expects($this->once())->method('createFolder')->with($this->equalTo('newFolder'))->will($this->returnValue($mockedParentFolder));
        $this->prepareSubject([], true);
        $this->subject->setDriver($mockedDriver);
        $this->subject->createFolder('newFolder', $mockedParentFolder);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function deleteFolderThrowsExceptionIfFolderIsNotEmptyAndRecursiveDeleteIsDisabled()
    {
        /** @var \TYPO3\CMS\Core\Resource\Folder|\PHPUnit_Framework_MockObject_MockObject $folderMock */
        $folderMock = $this->getMock(Folder::class, [], [], '', false);
        /** @var $mockedDriver \TYPO3\CMS\Core\Resource\Driver\AbstractDriver|\PHPUnit_Framework_MockObject_MockObject */
        $mockedDriver = $this->getMockForAbstractClass(AbstractDriver::class);
        $mockedDriver->expects($this->once())->method('isFolderEmpty')->will($this->returnValue(false));
        /** @var $subject ResourceStorage|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $subject = $this->getAccessibleMock(ResourceStorage::class, ['checkFolderActionPermission'], [], '', false);
        $subject->expects($this->any())->method('checkFolderActionPermission')->will($this->returnValue(true));
        $subject->_set('driver', $mockedDriver);
        $subject->deleteFolder($folderMock, false);
    }

    /**
     * @test
     * @TODO: Rewrite or move to functional suite
     */
    public function createFolderCallsDriverForFolderCreation()
    {
        $this->markTestSkipped('This test does way to much and is mocked incomplete. Skipped for now.');
        $mockedParentFolder = $this->getSimpleFolderMock('/someFolder/');
        $this->prepareSubject([], true);
        $mockedDriver = $this->createDriverMock([], $this->subject);
        $mockedDriver->expects($this->once())->method('createFolder')->with($this->equalTo('newFolder'), $this->equalTo('/someFolder/'))->will($this->returnValue(true));
        $mockedDriver->expects($this->once())->method('folderExists')->with($this->equalTo('/someFolder/'))->will($this->returnValue(true));
        $this->subject->createFolder('newFolder', $mockedParentFolder);
    }

    /**
     * @test
     * @TODO: Rewrite or move to functional suite
     */
    public function createFolderCanRecursivelyCreateFolders()
    {
        $this->markTestSkipped('This test does way to much and is mocked incomplete. Skipped for now.');
        $this->addToMount(['someFolder' => []]);
        $mockedDriver = $this->createDriverMock(['basePath' => $this->getMountRootUrl()], null, null);
        $this->prepareSubject([], true, $mockedDriver);
        $parentFolder = $this->subject->getFolder('/someFolder/');
        $newFolder = $this->subject->createFolder('subFolder/secondSubfolder', $parentFolder);
        $this->assertEquals('secondSubfolder', $newFolder->getName());
        $this->assertFileExists($this->getUrlInMount('/someFolder/subFolder/'));
        $this->assertFileExists($this->getUrlInMount('/someFolder/subFolder/secondSubfolder/'));
    }

    /**
     * @test
     * @TODO: Rewrite or move to functional suite
     */
    public function createFolderUsesRootFolderAsParentFolderIfNotGiven()
    {
        $this->markTestSkipped('This test does way to much and is mocked incomplete. Skipped for now.');
        $this->prepareSubject([], true);
        $mockedDriver = $this->createDriverMock([], $this->subject);
        $mockedDriver->expects($this->once())->method('getRootLevelFolder')->with()->will($this->returnValue('/'));
        $mockedDriver->expects($this->once())->method('createFolder')->with($this->equalTo('someFolder'));
        $this->subject->createFolder('someFolder');
    }

    /**
     * @test
     * @TODO: Rewrite or move to functional suite
     */
    public function createFolderCreatesNestedStructureEvenIfPartsAlreadyExist()
    {
        $this->markTestSkipped('This test does way to much and is mocked incomplete. Skipped for now.');
        $this->addToMount([
            'existingFolder' => []
        ]);
        $this->initializeVfs();
        $mockedDriver = $this->createDriverMock(['basePath' => $this->getMountRootUrl()], null, null);
        $this->prepareSubject([], true, $mockedDriver);
        $rootFolder = $this->subject->getFolder('/');
        $newFolder = $this->subject->createFolder('existingFolder/someFolder', $rootFolder);
        $this->assertEquals('someFolder', $newFolder->getName());
        $this->assertFileExists($this->getUrlInMount('existingFolder/someFolder'));
    }

    /**
     * @test
     */
    public function createFolderThrowsExceptionIfParentFolderDoesNotExist()
    {
        $this->setExpectedException('InvalidArgumentException', '', 1325689164);
        $mockedParentFolder = $this->getSimpleFolderMock('/someFolder/');
        $this->prepareSubject([], true);
        $mockedDriver = $this->createDriverMock([], $this->subject);
        $mockedDriver->expects($this->once())->method('folderExists')->with($this->equalTo('/someFolder/'))->will($this->returnValue(false));
        $this->subject->createFolder('newFolder', $mockedParentFolder);
    }

    /**
     * @test
     */
    public function replaceFileFailsIfLocalFileDoesNotExist()
    {
        $this->setExpectedException('InvalidArgumentException', '', 1325842622);
        $this->prepareSubject([], true);
        $mockedFile = $this->getSimpleFileMock('/someFile');
        $this->subject->replaceFile($mockedFile, PATH_site . $this->getUniqueId());
    }

    /**
     * @test
     * @TODO: Rewrite or move to functional suite
     */
    public function getRoleReturnsDefaultForRegularFolders()
    {
        $this->markTestSkipped('This test does way to much and is mocked incomplete. Skipped for now.');
        $folderIdentifier = $this->getUniqueId();
        $this->addToMount([
            $folderIdentifier => []
        ]);
        $this->prepareSubject([]);

        $role = $this->subject->getRole($this->getSimpleFolderMock('/' . $folderIdentifier . '/'));

        $this->assertSame(FolderInterface::ROLE_DEFAULT, $role);
    }

    /**
     * @test
     */
    public function getProcessingRootFolderTest()
    {
        $this->prepareSubject([]);
        $processingFolder = $this->subject->getProcessingFolder();

        $this->assertInstanceOf(Folder::class, $processingFolder);
    }

    /**
     * @test
     */
    public function getNestedProcessingFolderTest()
    {
        $mockedDriver = $this->createDriverMock(['basePath' => $this->getMountRootUrl()], null, null);
        $this->prepareSubject([], true, $mockedDriver);
        $mockedFile = $this->getSimpleFileMock('/someFile');

        $rootProcessingFolder = $this->subject->getProcessingFolder();
        $processingFolder = $this->subject->getProcessingFolder($mockedFile);

        $this->assertInstanceOf(Folder::class, $processingFolder);
        $this->assertNotEquals($rootProcessingFolder, $processingFolder);

        for ($i = ResourceStorage::PROCESSING_FOLDER_LEVELS; $i>0; $i--) {
            $processingFolder = $processingFolder->getParentFolder();
        }
        $this->assertEquals($rootProcessingFolder, $processingFolder);
    }
}
