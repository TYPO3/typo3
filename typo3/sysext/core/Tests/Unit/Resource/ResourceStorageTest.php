<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\Tests\Unit\Resource;

use Prophecy\Argument;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Resource\Driver\AbstractDriver;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\Index\FileIndexRepository;
use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Test case for ResourceStorage class
 */
class ResourceStorageTest extends BaseTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @var ResourceStorage|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $subject;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        /** @var FileRepository|\PHPUnit\Framework\MockObject\MockObject $fileRepositoryMock */
        $fileRepositoryMock = $this->createMock(FileRepository::class);
        GeneralUtility::setSingletonInstance(
            FileRepository::class,
            $fileRepositoryMock
        );
        $cacheManagerProphecy = $this->prophesize(CacheManager::class);
        $cacheProphecy = $this->prophesize(FrontendInterface::class);
        $cacheManagerProphecy->getCache('runtime')->willReturn($cacheProphecy->reveal());
        $cacheProphecy->get(Argument::cetera())->willReturn(false);
        $cacheProphecy->set(Argument::cetera())->willReturn(false);
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerProphecy->reveal());
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(Argument::cetera())->willReturnArgument(0);
        $this->eventDispatcher = $eventDispatcher->reveal();
    }

    /**
     * Prepare ResourceStorage
     *
     * @param array $configuration
     * @param bool $mockPermissionChecks
     * @param AbstractDriver|\PHPUnit\Framework\MockObject\MockObject $driverObject
     * @param ResourceFactory $resourceFactory
     * @param array $storageRecord
     * @param array $mockedMethods
     */
    protected function prepareSubject(
        array $configuration,
        bool $mockPermissionChecks = false,
        AbstractDriver $driverObject = null,
        ResourceFactory $resourceFactory = null,
        array $storageRecord = [],
        array $mockedMethods = []
    ): void {
        $permissionMethods = [
            'assureFileAddPermissions',
            'checkFolderActionPermission',
            'checkFileActionPermission',
            'checkUserActionPermission',
            'checkFileExtensionPermission',
            'isWithinFileMountBoundaries',
            'assureFileRenamePermissions'
        ];
        $configuration = $this->convertConfigurationArrayToFlexformXml($configuration);
        $overruleArray = ['configuration' => $configuration];
        ArrayUtility::mergeRecursiveWithOverrule($storageRecord, $overruleArray);
        if ($driverObject === null) {
            $driverObject = $this->getMockForAbstractClass(AbstractDriver::class, [], '', false);
        }
        if ($resourceFactory === null) {
            $resourceFactory = $this->createMock(ResourceFactory::class);
        }
        $mockedMethods[] = 'getResourceFactoryInstance';
        if ($mockPermissionChecks) {
            $mockedMethods = array_merge($mockedMethods, $permissionMethods);
        }
        $mockedMethods[] = 'getIndexer';

        $this->subject = $this->getMockBuilder(ResourceStorage::class)
            ->setMethods(array_unique($mockedMethods))
            ->setConstructorArgs([$driverObject, $storageRecord, $this->eventDispatcher])
            ->getMock();
        $this->subject->expects(self::any())->method('getResourceFactoryInstance')->willReturn($resourceFactory);
        $this->subject->expects(self::any())->method('getIndexer')->willReturn($this->createMock(Indexer::class));
        if ($mockPermissionChecks) {
            foreach ($permissionMethods as $method) {
                $this->subject->expects(self::any())->method($method)->willReturn(true);
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
    protected function convertConfigurationArrayToFlexformXml(array $configuration): string
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
     * @return \TYPO3\CMS\Core\Resource\Driver\LocalDriver|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createDriverMock(
        $driverConfiguration,
        ResourceStorage $storageObject = null,
        array $mockedDriverMethods = []
    ) {
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
    public function capabilitiesDataProvider(): array
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
     * @param array $capabilities
     */
    public function capabilitiesOfStorageObjectAreCorrectlySet(array $capabilities): void
    {
        self::markTestSkipped('This test does way to much and is mocked incomplete. Skipped for now.');
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
        $this->prepareSubject([], false, $mockedDriver, null, $storageRecord);
        self::assertEquals(
            $capabilities['public'],
            $this->subject->isPublic(),
            'Capability "public" is not correctly set.'
        );
        self::assertEquals(
            $capabilities['writable'],
            $this->subject->isWritable(),
            'Capability "writable" is not correctly set.'
        );
        self::assertEquals(
            $capabilities['browsable'],
            $this->subject->isBrowsable(),
            'Capability "browsable" is not correctly set.'
        );
    }

    /**
     * @test
     * @TODO: Rewrite or move to functional suite
     */
    public function fileAndFolderListFiltersAreInitializedWithDefaultFilters(): void
    {
        self::markTestSkipped('This test does way to much and is mocked incomplete. Skipped for now.');
        $this->prepareSubject([]);
        self::assertEquals(
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['defaultFilterCallbacks'],
            $this->subject->getFileAndFolderNameFilters()
        );
    }

    /**
     * @test
     */
    public function addFileFailsIfFileDoesNotExist(): void
    {
        /** @var Folder|\PHPUnit\Framework\MockObject\MockObject $mockedFolder */
        $mockedFolder = $this->createMock(Folder::class);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1319552745);
        $this->prepareSubject([]);
        $this->subject->addFile('/some/random/file', $mockedFolder);
    }

    /**
     * @test
     */
    public function getPublicUrlReturnsNullIfStorageIsNotOnline(): void
    {
        /** @var $driver LocalDriver|\PHPUnit\Framework\MockObject\MockObject */
        $driver = $this->getMockBuilder(LocalDriver::class)
            ->setConstructorArgs([['basePath' => $this->getMountRootUrl()]])
            ->getMock();
        $mockedResourceFactory = $this->createMock(ResourceFactory::class);
        /** @var $subject ResourceStorage|\PHPUnit\Framework\MockObject\MockObject */
        $subject = $this->getMockBuilder(ResourceStorage::class)
            ->setMethods(['isOnline', 'getResourceFactoryInstance'])
            ->setConstructorArgs([$driver, ['configuration' => []], $this->eventDispatcher])
            ->getMock();
        $subject->expects(self::once())->method('isOnline')->willReturn(false);
        $subject->expects(self::any())->method('getResourceFactoryInstance')->willReturn($mockedResourceFactory);

        $sourceFileIdentifier = '/sourceFile.ext';
        $sourceFile = $this->getSimpleFileMock($sourceFileIdentifier);
        $result = $subject->getPublicUrl($sourceFile);
        self::assertSame($result, null);
    }

    /**
     * Data provider for checkFolderPermissionsRespectsFilesystemPermissions
     *
     * @return array
     */
    public function checkFolderPermissionsFilesystemPermissionsDataProvider(): array
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
    public function checkFolderPermissionsRespectsFilesystemPermissions(
        string $action,
        array $permissionsFromDriver,
        bool $expectedResult
    ): void {
        /** @var $mockedDriver LocalDriver|\PHPUnit\Framework\MockObject\MockObject */
        $mockedDriver = $this->createMock(LocalDriver::class);
        $mockedDriver->expects(self::any())->method('getPermissions')->willReturn($permissionsFromDriver);
        $mockedResourceFactory = $this->createMock(ResourceFactory::class);
        /** @var $mockedFolder Folder|\PHPUnit\Framework\MockObject\MockObject */
        $mockedFolder = $this->createMock(Folder::class);
        // Let all other checks pass
        /** @var $subject ResourceStorage|\PHPUnit\Framework\MockObject\MockObject */
        $subject = $this->getMockBuilder(ResourceStorage::class)
            ->setMethods(['isWritable', 'isBrowsable', 'checkUserActionPermission', 'getResourceFactoryInstance'])
            ->setConstructorArgs([$mockedDriver, [], $this->eventDispatcher])
            ->getMock();
        $subject->expects(self::any())->method('isWritable')->willReturn(true);
        $subject->expects(self::any())->method('isBrowsable')->willReturn(true);
        $subject->expects(self::any())->method('checkUserActionPermission')->willReturn(true);
        $subject->expects(self::any())->method('getResourceFactoryInstance')->willReturn($mockedResourceFactory);
        $subject->setDriver($mockedDriver);

        self::assertSame($expectedResult, $subject->checkFolderActionPermission($action, $mockedFolder));
    }

    /**
     * @test
     */
    public function checkUserActionPermissionsAlwaysReturnsTrueIfNoUserPermissionsAreSet(): void
    {
        $this->prepareSubject([]);
        self::assertTrue($this->subject->checkUserActionPermission('read', 'folder'));
    }

    /**
     * @test
     */
    public function checkUserActionPermissionReturnsFalseIfPermissionIsSetToZero(): void
    {
        $this->prepareSubject([]);
        $this->subject->setUserPermissions(['readFolder' => true, 'writeFile' => true]);
        self::assertTrue($this->subject->checkUserActionPermission('read', 'folder'));
    }

    /**
     * @return array
     */
    public function checkUserActionPermission_arbitraryPermissionDataProvider(): array
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
    public function checkUserActionPermissionAcceptsArbitrarilyCasedArguments(array $permissions, string $action, string $type): void
    {
        $this->prepareSubject([]);
        $this->subject->setUserPermissions($permissions);
        self::assertTrue($this->subject->checkUserActionPermission($action, $type));
    }

    /**
     * @test
     */
    public function userActionIsDisallowedIfPermissionIsSetToFalse(): void
    {
        $this->prepareSubject([]);
        $this->subject->setEvaluatePermissions(true);
        $this->subject->setUserPermissions(['readFolder' => false]);
        self::assertFalse($this->subject->checkUserActionPermission('read', 'folder'));
    }

    /**
     * @test
     */
    public function userActionIsDisallowedIfPermissionIsNotSet(): void
    {
        $this->prepareSubject([]);
        $this->subject->setEvaluatePermissions(true);
        $this->subject->setUserPermissions(['readFolder' => true]);
        self::assertFalse($this->subject->checkUserActionPermission('write', 'folder'));
    }

    /**
     * @test
     */
    public function metaDataEditIsNotAllowedWhenWhenNoFileMountsAreSet(): void
    {
        $this->prepareSubject([], false, null, null, [], ['isWithinProcessingFolder']);
        $this->subject->setEvaluatePermissions(true);
        self::assertFalse($this->subject->checkFileActionPermission('editMeta', new File(['identifier' => '/foo/bar.jpg'], $this->subject)));
    }

    /**
     * @test
     */
    public function metaDataEditIsAllowedWhenWhenInFileMount(): void
    {
        $driverMock = $this->getMockForAbstractClass(AbstractDriver::class, [], '', false);
        $resourceFactory = $this->prophesize(ResourceFactory::class);
        $this->prepareSubject([], false, $driverMock, $resourceFactory->reveal(), [], ['isWithinProcessingFolder']);

        $folderStub = new Folder($this->subject, '/foo/', 'foo');
        $resourceFactory->createFolderObject(Argument::cetera())->willReturn($folderStub);
        $fileStub = new File(['identifier' => '/foo/bar.jpg', 'name' => 'bar.jpg'], $this->subject);
        $driverMock->expects(self::once())
            ->method('isWithin')
            ->with($folderStub->getIdentifier(), $fileStub->getIdentifier())
            ->willReturn(true);
        $driverMock->expects(self::once())
            ->method('getFolderInfoByIdentifier')
            ->with($folderStub->getIdentifier())
            ->willReturn(['identifier' => $folderStub->getIdentifier(), 'name' => $folderStub->getName()]);

        $this->subject->setEvaluatePermissions(true);
        $this->subject->addFileMount('/foo/', [
            'path' => '/foo/',
            'title' => 'Foo',
            'folder' => $folderStub,
        ]);
        self::assertTrue($this->subject->checkFileActionPermission('editMeta', $fileStub));
    }

    /**
     * @test
     */
    public function metaDataEditIsNotAllowedWhenWhenInReadOnlyFileMount(): void
    {
        $driverMock = $this->getMockForAbstractClass(AbstractDriver::class, [], '', false);
        $resourceFactory = $this->prophesize(ResourceFactory::class);
        $this->prepareSubject([], false, $driverMock, $resourceFactory->reveal(), [], ['isWithinProcessingFolder']);

        $fileStub = new File(['identifier' => '/foo/bar.jpg', 'name' => 'bar.jpg'], $this->subject);
        $folderStub = new Folder($this->subject, '/foo/', 'foo');
        $resourceFactory->createFolderObject(Argument::cetera())->willReturn($folderStub);
        $driverMock->expects(self::once())
            ->method('isWithin')
            ->with($folderStub->getIdentifier(), $fileStub->getIdentifier())
            ->willReturn(true);
        $driverMock->expects(self::once())
            ->method('getFolderInfoByIdentifier')
            ->with($folderStub->getIdentifier())
            ->willReturn(['identifier' => $folderStub->getIdentifier(), 'name' => $folderStub->getName()]);

        $this->subject->setEvaluatePermissions(true);
        $this->subject->addFileMount('/foo/', [
            'path' => '/foo/',
            'title' => 'Foo',
            'folder' => $folderStub,
            'read_only' => true,
        ]);
        self::assertFalse($this->subject->checkFileActionPermission('editMeta', $fileStub));
    }

    /**
     * @test
     */
    public function getEvaluatePermissionsWhenSetFalse(): void
    {
        $this->prepareSubject([]);
        $this->subject->setEvaluatePermissions(false);
        self::assertFalse($this->subject->getEvaluatePermissions());
    }

    /**
     * @test
     */
    public function getEvaluatePermissionsWhenSetTrue(): void
    {
        $this->prepareSubject([]);
        $this->subject->setEvaluatePermissions(true);
        self::assertTrue($this->subject->getEvaluatePermissions());
    }

    /**
     * @test
     * @group integration
     * @TODO: Rewrite or move to functional suite
     */
    public function setFileContentsUpdatesObjectProperties(): void
    {
        self::markTestSkipped('This test does way to much and is mocked incomplete. Skipped for now.');
        $this->initializeVfs();
        $driverObject = $this->getMockForAbstractClass(AbstractDriver::class, [], '', false);
        $this->subject = $this->getMockBuilder(ResourceStorage::class)
            ->setMethods(['getFileIndexRepository', 'checkFileActionPermission'])
            ->setConstructorArgs([$driverObject, [], $this->eventDispatcher])
            ->getMock();
        $this->subject->expects(self::any())->method('checkFileActionPermission')->willReturn(true);
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
        /** @var $mockedDriver LocalDriver|\PHPUnit\Framework\MockObject\MockObject */
        $mockedDriver = $this->getMockBuilder(LocalDriver::class)
            ->setConstructorArgs([['basePath' => $this->getMountRootUrl()]])
            ->getMock();
        $mockedDriver->expects(self::once())->method('getFileInfoByIdentifier')->willReturn($fileInfo);
        $mockedDriver->expects(self::once())->method('hash')->willReturn($hash);
        $this->subject->setDriver($mockedDriver);
        $indexFileRepositoryMock = $this->createMock(FileIndexRepository::class);
        $this->subject->expects(self::any())->method('getFileIndexRepository')->willReturn($indexFileRepositoryMock);
        /** @var $mockedFile File|\PHPUnit\Framework\MockObject\MockObject */
        $mockedFile = $this->createMock(File::class);
        $mockedFile->expects(self::any())->method('getIdentifier')->willReturn($fileInfo['identifier']);
        // called by indexer because the properties are updated
        $this->subject->expects(self::any())->method('getFileInfoByIdentifier')->willReturn($newProperties);
        $mockedFile->expects(self::any())->method('getStorage')->willReturn($this->subject);
        $mockedFile->expects(self::any())->method('getProperties')->willReturn(array_keys($fileInfo));
        $mockedFile->expects(self::any())->method('getUpdatedProperties')->willReturn(array_keys($newProperties));
        // do not update directly; that's up to the indexer
        $indexFileRepositoryMock->expects(self::never())->method('update');
        $this->subject->setFileContents($mockedFile, StringUtility::getUniqueId('content_'));
    }

    /**
     * @test
     * @group integration
     * @TODO: Rewrite or move to functional suite
     */
    public function moveFileCallsDriversMethodsWithCorrectArguments(): void
    {
        self::markTestSkipped('This test does way to much and is mocked incomplete. Skipped for now.');
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
        /** @var $sourceDriver LocalDriver|\PHPUnit\Framework\MockObject\MockObject */
        $sourceDriver = $this->createMock(LocalDriver::class);
        $sourceDriver->expects(self::once())->method('deleteFile')->with(self::equalTo($sourceFileIdentifier));
        $configuration = $this->convertConfigurationArrayToFlexformXml([]);
        $sourceStorage = new ResourceStorage($sourceDriver, ['configuration' => $configuration]);
        $sourceFile = $this->getSimpleFileMock($sourceFileIdentifier);
        $sourceFile->expects(self::once())->method('getForLocalProcessing')->willReturn($localFilePath);
        $sourceFile->expects(self::any())->method('getStorage')->willReturn($sourceStorage);
        $sourceFile->expects(self::once())->method('getUpdatedProperties')->willReturn(array_keys($fileInfoDummy));
        $sourceFile->expects(self::once())->method('getProperties')->willReturn($fileInfoDummy);
        /** @var $mockedDriver \TYPO3\CMS\Core\Resource\Driver\LocalDriver|\PHPUnit\Framework\MockObject\MockObject */
        $mockedDriver = $this->getMockBuilder(LocalDriver::class)
            ->setConstructorArgs([['basePath' => $this->getMountRootUrl()]])
            ->getMock();
        $mockedDriver->expects(self::once())->method('getFileInfoByIdentifier')->willReturn($fileInfoDummy);
        $mockedDriver->expects(self::once())->method('addFile')->with(
            $localFilePath,
            '/targetFolder/',
            self::equalTo('file.ext')
        )->willReturn('/targetFolder/file.ext');
        /** @var $subject ResourceStorage */
        $subject = $this->getMockBuilder(ResourceStorage::class)
            ->setMethods(['assureFileMovePermissions'])
            ->setConstructorArgs([$mockedDriver, ['configuration' => $configuration], $this->eventDispatcher])
            ->getMock();
        $subject->moveFile($sourceFile, $targetFolder, 'file.ext');
    }

    /**
     * @test
     * @group integration
     * @TODO: Rewrite or move to functional suite
     */
    public function storageUsesInjectedFilemountsToCheckForMountBoundaries(): void
    {
        self::markTestSkipped('This test does way to much and is mocked incomplete. Skipped for now.');
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
        self::assertEquals(1, count($this->subject->getFileMounts()));
        $this->subject->isWithinFileMountBoundaries($mockedFile);
    }

    /**
     * @test
     * @TODO: Rewrite or move to functional suite
     */
    public function createFolderChecksIfParentFolderExistsBeforeCreatingFolder(): void
    {
        self::markTestSkipped('This test does way to much and is mocked incomplete. Skipped for now.');
        $mockedParentFolder = $this->getSimpleFolderMock('/someFolder/');
        $mockedDriver = $this->createDriverMock([]);
        $mockedDriver->expects(self::once())->method('folderExists')->with(self::equalTo('/someFolder/'))->willReturn(true);
        $mockedDriver->expects(self::once())->method('createFolder')->with(self::equalTo('newFolder'))->willReturn($mockedParentFolder);
        $this->prepareSubject([], true);
        $this->subject->setDriver($mockedDriver);
        $this->subject->createFolder('newFolder', $mockedParentFolder);
    }

    /**
     * @test
     */
    public function deleteFolderThrowsExceptionIfFolderIsNotEmptyAndRecursiveDeleteIsDisabled(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1325952534);

        /** @var \TYPO3\CMS\Core\Resource\Folder|\PHPUnit\Framework\MockObject\MockObject $folderMock */
        $folderMock = $this->createMock(Folder::class);
        /** @var $mockedDriver \TYPO3\CMS\Core\Resource\Driver\AbstractDriver|\PHPUnit\Framework\MockObject\MockObject */
        $mockedDriver = $this->getMockForAbstractClass(AbstractDriver::class);
        $mockedDriver->expects(self::once())->method('isFolderEmpty')->willReturn(false);
        /** @var $subject ResourceStorage|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $subject = $this->getAccessibleMock(ResourceStorage::class, ['checkFolderActionPermission'], [], '', false);
        $subject->expects(self::any())->method('checkFolderActionPermission')->willReturn(true);
        $subject->_set('driver', $mockedDriver);
        $subject->deleteFolder($folderMock, false);
    }

    /**
     * @test
     * @TODO: Rewrite or move to functional suite
     */
    public function createFolderCallsDriverForFolderCreation(): void
    {
        self::markTestSkipped('This test does way to much and is mocked incomplete. Skipped for now.');
        $mockedParentFolder = $this->getSimpleFolderMock('/someFolder/');
        $this->prepareSubject([], true);
        $mockedDriver = $this->createDriverMock([], $this->subject);
        $mockedDriver->expects(self::once())->method('createFolder')->with(
            self::equalTo('newFolder'),
            self::equalTo('/someFolder/')
        )->willReturn(true);
        $mockedDriver->expects(self::once())->method('folderExists')->with(self::equalTo('/someFolder/'))->willReturn(true);
        $this->subject->createFolder('newFolder', $mockedParentFolder);
    }

    /**
     * @test
     * @TODO: Rewrite or move to functional suite
     */
    public function createFolderCanRecursivelyCreateFolders(): void
    {
        self::markTestSkipped('This test does way to much and is mocked incomplete. Skipped for now.');
        $this->addToMount(['someFolder' => []]);
        $mockedDriver = $this->createDriverMock(['basePath' => $this->getMountRootUrl()], null, null);
        $this->prepareSubject([], true, $mockedDriver);
        $parentFolder = $this->subject->getFolder('/someFolder/');
        $newFolder = $this->subject->createFolder('subFolder/secondSubfolder', $parentFolder);
        self::assertEquals('secondSubfolder', $newFolder->getName());
        self::assertFileExists($this->getUrlInMount('/someFolder/subFolder/'));
        self::assertFileExists($this->getUrlInMount('/someFolder/subFolder/secondSubfolder/'));
    }

    /**
     * @test
     * @TODO: Rewrite or move to functional suite
     */
    public function createFolderUsesRootFolderAsParentFolderIfNotGiven(): void
    {
        self::markTestSkipped('This test does way to much and is mocked incomplete. Skipped for now.');
        $this->prepareSubject([], true);
        $mockedDriver = $this->createDriverMock([], $this->subject);
        $mockedDriver->expects(self::once())->method('getRootLevelFolder')->with()->willReturn('/');
        $mockedDriver->expects(self::once())->method('createFolder')->with(self::equalTo('someFolder'));
        $this->subject->createFolder('someFolder');
    }

    /**
     * @test
     * @TODO: Rewrite or move to functional suite
     */
    public function createFolderCreatesNestedStructureEvenIfPartsAlreadyExist(): void
    {
        self::markTestSkipped('This test does way to much and is mocked incomplete. Skipped for now.');
        $this->addToMount([
            'existingFolder' => []
        ]);
        $this->initializeVfs();
        $mockedDriver = $this->createDriverMock(['basePath' => $this->getMountRootUrl()], null, null);
        $this->prepareSubject([], true, $mockedDriver);
        $rootFolder = $this->subject->getFolder('/');
        $newFolder = $this->subject->createFolder('existingFolder/someFolder', $rootFolder);
        self::assertEquals('someFolder', $newFolder->getName());
        self::assertFileExists($this->getUrlInMount('existingFolder/someFolder'));
    }

    /**
     * @test
     */
    public function createFolderThrowsExceptionIfParentFolderDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1325689164);
        $mockedParentFolder = $this->getSimpleFolderMock('/someFolder/');
        $this->prepareSubject([], true);
        $mockedDriver = $this->createDriverMock([], $this->subject);
        $mockedDriver->expects(self::once())->method('folderExists')->with(self::equalTo('/someFolder/'))->willReturn(false);
        $this->subject->createFolder('newFolder', $mockedParentFolder);
    }

    /**
     * @test
     */
    public function renameFileRenamesFileAsRequested(): void
    {
        $mockedDriver = $this->createDriverMock([], $this->subject);
        $mockedDriver->expects(self::once())->method('renameFile')->willReturn('bar');
        $this->prepareSubject([], true, $mockedDriver, null);
        /** @var File $file */
        $file = new File(['identifier' => 'foo', 'name' => 'foo'], $this->subject);
        $result = $this->subject->renameFile($file, 'bar');
        // fake what the indexer does in updateIndexEntry
        $result->updateProperties(['name' => $result->getIdentifier()]);
        self::assertSame('bar', $result->getName());
    }

    /**
     * @test
     */
    public function renameFileRenamesWithUniqueNameIfConflictAndConflictModeIsRename(): void
    {
        $mockedDriver = $this->createDriverMock([], $this->subject);
        $mockedDriver->expects(self::any())->method('renameFile')->will(self::onConsecutiveCalls(self::throwException(new ExistingTargetFileNameException(
            'foo',
            1489593090
        )), 'bar_01'));
        //$mockedDriver->expects($this->at(1))->method('renameFile')->will($this->returnValue('bar_01'));
        $mockedDriver->expects(self::any())->method('sanitizeFileName')->will(self::onConsecutiveCalls(
            'bar',
            'bar_01'
        ));
        $resourceFactory = $this->createMock(ResourceFactory::class);
        $this->prepareSubject(
            [],
            true,
            $mockedDriver,
            $resourceFactory,
            [],
            ['getUniqueName']
        );
        $resourceFactory->expects(self::once())->method('createFolderObject')->willReturn(new Folder($this->subject, '', ''));
        /** @var File $file */
        $file = new File(['identifier' => 'foo', 'name' => 'foo'], $this->subject);
        $this->subject->expects(self::any())->method('getUniqueName')->willReturn('bar_01');
        $result = $this->subject->renameFile($file, 'bar');
        // fake what the indexer does in updateIndexEntry
        $result->updateProperties(['name' => $result->getIdentifier()]);
        self::assertSame('bar_01', $result->getName());
    }

    /**
     * @test
     */
    public function renameFileThrowsExceptionIfConflictAndConflictModeIsCancel(): void
    {
        $mockedDriver = $this->createDriverMock([], $this->subject);
        $mockedDriver->expects(self::once())->method('renameFile')->will(self::throwException(new ExistingTargetFileNameException(
            'foo',
            1489593099
        )));
        $this->prepareSubject([], true, $mockedDriver);
        /** @var File $file */
        $file = new File(['identifier' => 'foo', 'name' => 'foo'], $this->subject);
        $this->expectException(ExistingTargetFileNameException::class);
        $this->subject->renameFile($file, 'bar', DuplicationBehavior::CANCEL);
    }

    /**
     * @test
     */
    public function renameFileReplacesIfConflictAndConflictModeIsReplace(): void
    {
        $mockedDriver = $this->createDriverMock([], $this->subject);
        $mockedDriver->expects(self::once())->method('renameFile')->will(self::throwException(new ExistingTargetFileNameException(
            'foo',
            1489593098
        )));
        $mockedDriver->expects(self::any())->method('sanitizeFileName')->willReturn('bar');
        $resourceFactory = $this->prophesize(ResourceFactory::class);
        $this->prepareSubject([], true, $mockedDriver, $resourceFactory->reveal(), [], [
            'replaceFile',
            'getPublicUrl',
        ]);
        $this->subject->expects(self::once())->method('getPublicUrl')->willReturn('somePath');
        $file = $this->prophesize(FileInterface::class);
        $resourceFactory->getFileObjectFromCombinedIdentifier(Argument::any())->willReturn($file->reveal());
        $this->subject->expects(self::once())->method('replaceFile')->willReturn($file->reveal());
        /** @var File $file */
        $file = new File(['identifier' => 'foo', 'name' => 'foo', 'missing' => false], $this->subject);
        $this->subject->renameFile($file, 'bar', DuplicationBehavior::REPLACE);
    }
}
