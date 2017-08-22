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

use Prophecy\Argument;
use TYPO3\CMS\Core\Resource\Driver\AbstractDriver;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\Index\FileIndexRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
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
        $fileRepositoryMock = $this->createMock(FileRepository::class);
        GeneralUtility::setSingletonInstance(
            FileRepository::class,
            $fileRepositoryMock
        );
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
     * @param array $mockedMethods
     */
    protected function prepareSubject(array $configuration, $mockPermissionChecks = false, AbstractDriver $driverObject = null, array $storageRecord = [], array $mockedMethods = [])
    {
        $permissionMethods = ['assureFileAddPermissions', 'checkFolderActionPermission', 'checkFileActionPermission', 'checkUserActionPermission', 'checkFileExtensionPermission', 'isWithinFileMountBoundaries', 'assureFileRenamePermissions'];
        $configuration = $this->convertConfigurationArrayToFlexformXml($configuration);
        $overruleArray = ['configuration' => $configuration];
        ArrayUtility::mergeRecursiveWithOverrule($storageRecord, $overruleArray);
        if ($driverObject == null) {
            $driverObject = $this->getMockForAbstractClass(AbstractDriver::class, [], '', false);
        }
        if ($mockPermissionChecks) {
            $mockedMethods = array_merge($mockedMethods, $permissionMethods);
        }
        $mockedMethods[] = 'getIndexer';

        $this->subject = $this->getMockBuilder(ResourceStorage::class)
            ->setMethods(array_unique($mockedMethods))
            ->setConstructorArgs([$driverObject, $storageRecord])
            ->getMock();
        $this->subject->expects($this->any())->method('getIndexer')->will($this->returnValue($this->createMock(\TYPO3\CMS\Core\Resource\Index\Indexer::class)));
        if ($mockPermissionChecks) {
            foreach ($permissionMethods as $method) {
                $this->subject->expects($this->any())->method($method)->will($this->returnValue(true));
            }
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
            $driver = $this->getMockBuilder(LocalDriver::class)
                ->setMethods($mockedDriverMethods)
                ->setConstructorArgs([$driverConfiguration])
                ->getMock();
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
        $mockedFolder = $this->createMock(Folder::class);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1319552745);
        $this->prepareSubject([]);
        $this->subject->addFile('/some/random/file', $mockedFolder);
    }

    /**
     * @test
     */
    public function getPublicUrlReturnsNullIfStorageIsNotOnline()
    {
        /** @var $driver LocalDriver|\PHPUnit_Framework_MockObject_MockObject */
        $driver = $this->getMockBuilder(LocalDriver::class)
            ->setConstructorArgs([['basePath' => $this->getMountRootUrl()]])
            ->getMock();
        /** @var $subject ResourceStorage|\PHPUnit_Framework_MockObject_MockObject */
        $subject = $this->getMockBuilder(ResourceStorage::class)
            ->setMethods(['isOnline'])
            ->setConstructorArgs([$driver, ['configuration' => []]])
            ->getMock();
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
        $mockedDriver = $this->createMock(LocalDriver::class);
        $mockedDriver->expects($this->any())->method('getPermissions')->will($this->returnValue($permissionsFromDriver));
        /** @var $mockedFolder Folder|\PHPUnit_Framework_MockObject_MockObject  */
        $mockedFolder = $this->createMock(Folder::class);
        // Let all other checks pass
        /** @var $subject ResourceStorage|\PHPUnit_Framework_MockObject_MockObject */
        $subject = $this->getMockBuilder(ResourceStorage::class)
            ->setMethods(['isWritable', 'isBrowsable', 'checkUserActionPermission'])
            ->setConstructorArgs([$mockedDriver, []])
            ->getMock();
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
        $this->subject = $this->getMockBuilder(ResourceStorage::class)
            ->setMethods(['getFileIndexRepository', 'checkFileActionPermission'])
            ->setConstructorArgs([$driverObject, []])
            ->getMock();
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
        $mockedDriver = $this->getMockBuilder(LocalDriver::class)
            ->setConstructorArgs([['basePath' => $this->getMountRootUrl()]])
            ->getMock();
        $mockedDriver->expects($this->once())->method('getFileInfoByIdentifier')->will($this->returnValue($fileInfo));
        $mockedDriver->expects($this->once())->method('hash')->will($this->returnValue($hash));
        $this->subject->setDriver($mockedDriver);
        $indexFileRepositoryMock = $this->createMock(FileIndexRepository::class);
        $this->subject->expects($this->any())->method('getFileIndexRepository')->will($this->returnValue($indexFileRepositoryMock));
        /** @var $mockedFile File|\PHPUnit_Framework_MockObject_MockObject */
        $mockedFile = $this->createMock(File::class);
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
        $sourceDriver = $this->createMock(LocalDriver::class);
        $sourceDriver->expects($this->once())->method('deleteFile')->with($this->equalTo($sourceFileIdentifier));
        $configuration = $this->convertConfigurationArrayToFlexformXml([]);
        $sourceStorage = new ResourceStorage($sourceDriver, ['configuration' => $configuration]);
        $sourceFile = $this->getSimpleFileMock($sourceFileIdentifier);
        $sourceFile->expects($this->once())->method('getForLocalProcessing')->will($this->returnValue($localFilePath));
        $sourceFile->expects($this->any())->method('getStorage')->will($this->returnValue($sourceStorage));
        $sourceFile->expects($this->once())->method('getUpdatedProperties')->will($this->returnValue(array_keys($fileInfoDummy)));
        $sourceFile->expects($this->once())->method('getProperties')->will($this->returnValue($fileInfoDummy));
        /** @var $mockedDriver \TYPO3\CMS\Core\Resource\Driver\LocalDriver|\PHPUnit_Framework_MockObject_MockObject */
        $mockedDriver = $this->getMockBuilder(LocalDriver::class)
            ->setConstructorArgs([['basePath' => $this->getMountRootUrl()]])
            ->getMock();
        $mockedDriver->expects($this->once())->method('getFileInfoByIdentifier')->will($this->returnValue($fileInfoDummy));
        $mockedDriver->expects($this->once())->method('addFile')->with($localFilePath, '/targetFolder/', $this->equalTo('file.ext'))->will($this->returnValue('/targetFolder/file.ext'));
        /** @var $subject ResourceStorage */
        $subject = $this->getMockBuilder(ResourceStorage::class)
            ->setMethods(['assureFileMovePermissions'])
            ->setConstructorArgs([$mockedDriver, ['configuration' => $configuration]])
            ->getMock();
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
     */
    public function deleteFolderThrowsExceptionIfFolderIsNotEmptyAndRecursiveDeleteIsDisabled()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1325952534);

        /** @var \TYPO3\CMS\Core\Resource\Folder|\PHPUnit_Framework_MockObject_MockObject $folderMock */
        $folderMock = $this->createMock(Folder::class);
        /** @var $mockedDriver \TYPO3\CMS\Core\Resource\Driver\AbstractDriver|\PHPUnit_Framework_MockObject_MockObject */
        $mockedDriver = $this->getMockForAbstractClass(AbstractDriver::class);
        $mockedDriver->expects($this->once())->method('isFolderEmpty')->will($this->returnValue(false));
        /** @var $subject ResourceStorage|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
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
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1325689164);
        $mockedParentFolder = $this->getSimpleFolderMock('/someFolder/');
        $this->prepareSubject([], true);
        $mockedDriver = $this->createDriverMock([], $this->subject);
        $mockedDriver->expects($this->once())->method('folderExists')->with($this->equalTo('/someFolder/'))->will($this->returnValue(false));
        $this->subject->createFolder('newFolder', $mockedParentFolder);
    }

    /**
     * @test
     */
    public function renameFileRenamesFileAsRequested()
    {
        $mockedDriver = $this->createDriverMock([], $this->subject);
        $mockedDriver->expects($this->once())->method('renameFile')->will($this->returnValue('bar'));
        $this->prepareSubject([], true, $mockedDriver, [], ['emitPreFileRenameSignal', 'emitPostFileRenameSignal']);
        /** @var File $file */
        $file = new File(['identifier' => 'foo', 'name' => 'foo'], $this->subject);
        $result = $this->subject->renameFile($file, 'bar');
        // fake what the indexer does in updateIndexEntry
        $result->updateProperties(['name' => $result->getIdentifier()]);
        $this->assertSame('bar', $result->getName());
    }

    /**
     * @test
     */
    public function renameFileRenamesWithUniqueNameIfConflictAndConflictModeIsRename()
    {
        $mockedDriver = $this->createDriverMock([], $this->subject);
        $mockedDriver->expects($this->any())->method('renameFile')->will($this->onConsecutiveCalls($this->throwException(new ExistingTargetFileNameException('foo', 1489593090)), 'bar_01'));
        //$mockedDriver->expects($this->at(1))->method('renameFile')->will($this->returnValue('bar_01'));
        $mockedDriver->expects($this->any())->method('sanitizeFileName')->will($this->onConsecutiveCalls('bar', 'bar_01'));
        $this->prepareSubject([], true, $mockedDriver, [], ['emitPreFileRenameSignal', 'emitPostFileRenameSignal', 'getUniqueName']);
        /** @var File $file */
        $file = new File(['identifier' => 'foo', 'name' => 'foo'], $this->subject);
        $this->subject->expects($this->once())->method('getUniqueName')->will($this->returnValue('bar_01'));
        $result = $this->subject->renameFile($file, 'bar');
        // fake what the indexer does in updateIndexEntry
        $result->updateProperties(['name' => $result->getIdentifier()]);
        $this->assertSame('bar_01', $result->getName());
    }

    /**
     * @test
     */
    public function renameFileThrowsExceptionIfConflictAndConflictModeIsCancel()
    {
        $mockedDriver = $this->createDriverMock([], $this->subject);
        $mockedDriver->expects($this->once())->method('renameFile')->will($this->throwException(new ExistingTargetFileNameException('foo', 1489593099)));
        $this->prepareSubject([], true, $mockedDriver, [], ['emitPreFileRenameSignal', 'emitPostFileRenameSignal']);
        /** @var File $file */
        $file = new File(['identifier' => 'foo', 'name' => 'foo'], $this->subject);
        $this->expectException(ExistingTargetFileNameException::class);
        $this->subject->renameFile($file, 'bar', DuplicationBehavior::CANCEL);
    }

    /**
     * @test
     */
    public function renameFileReplacesIfConflictAndConflictModeIsReplace()
    {
        $mockedDriver = $this->createDriverMock([], $this->subject);
        $mockedDriver->expects($this->once())->method('renameFile')->will($this->throwException(new ExistingTargetFileNameException('foo', 1489593098)));
        $mockedDriver->expects($this->any())->method('sanitizeFileName')->will($this->returnValue('bar'));
        $this->prepareSubject([], true, $mockedDriver, [], ['emitPreFileRenameSignal', 'emitPostFileRenameSignal', 'replaceFile', 'getPublicUrl', 'getResourceFactoryInstance']);
        $this->subject->expects($this->once())->method('getPublicUrl')->will($this->returnValue('somePath'));
        $resourceFactory = $this->prophesize(ResourceFactory::class);
        $file = $this->prophesize(FileInterface::class);
        $resourceFactory->getFileObjectFromCombinedIdentifier(Argument::any())->willReturn($file->reveal());
        $this->subject->expects($this->once())->method('replaceFile')->will($this->returnValue($file->reveal()));
        $this->subject->expects($this->any())->method('getResourceFactoryInstance')->will(self::returnValue($resourceFactory->reveal()));
        /** @var File $file */
        $file = new File(['identifier' => 'foo', 'name' => 'foo', 'missing' => false], $this->subject);
        $this->subject->renameFile($file, 'bar', DuplicationBehavior::REPLACE);
    }
}
