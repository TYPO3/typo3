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

namespace TYPO3\CMS\Core\Tests\Functional\Resource;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\CMS\Core\Resource\Configuration\FileStorageConfiguration;
use TYPO3\CMS\Core\Resource\Configuration\FileStorageConfigurationWriter;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\IllegalFileExtensionException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\InaccessibleFolder;
use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\ResourceStorageInterface;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

#[AllowMockObjectsWithoutExpectations]
final class ResourceStorageTest extends FunctionalTestCase
{
    /**
     * The testing framework replaces the "hash" cache with a NullBackend,
     * the offline state of a storage needs a persisting one.
     */
    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'caching' => [
                'cacheConfigurations' => [
                    'hash' => [
                        'backend' => Typo3DatabaseBackend::class,
                    ],
                ],
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        mkdir($this->instancePath . '/resource-storage-test');
        $this->get(CacheManager::class)->getCache('hash')->flush();
    }

    protected function tearDown(): void
    {
        @unlink(Environment::getConfigPath() . '/system/file-storages.yaml');
        GeneralUtility::rmdir($this->instancePath . '/resource-storage-test', true);
        parent::tearDown();
    }

    #[Test]
    public function addFileFailsIfFileDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1319552745);
        $localDriver = new LocalDriver(['basePath' => $this->instancePath . '/resource-storage-test']);
        $subject = new ResourceStorage($localDriver, ['identifier' => 1, 'name' => 'testing'], new NoopEventDispatcher());
        $folder = new Folder($subject, 'someName', 'someName');
        $subject->addFile('/some/random/file', $folder);
    }

    #[Test]
    public function getPublicUrlReturnsNullIfStorageIsNotOnline(): void
    {
        $localDriver = new LocalDriver(['basePath' => $this->instancePath . '/resource-storage-test']);
        $subject = new ResourceStorage($localDriver, ['identifier' => 1, 'name' => 'testing'], new NoopEventDispatcher());
        $file = new File(
            [
                'identifier' => 'myIdentifier',
                'name' => 'myName',
            ],
            $subject,
        );
        $subject->markAsPermanentlyOffline();
        self::assertNull($subject->getPublicUrl($file));
    }

    #[Test]
    public function getPublicUrlReturnsNullForNonPublicStorageWithoutRequest(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);
        $localDriver = new LocalDriver(['basePath' => $this->instancePath . '/resource-storage-test']);
        $subject = new ResourceStorage($localDriver, ['identifier' => 1, 'name' => 'testing', 'online' => true, 'public' => false], new NoopEventDispatcher());
        $file = new File(['uid' => 5, 'identifier' => '/private/foo.jpg', 'name' => 'foo.jpg'], $subject);

        self::assertNull($subject->getPublicUrl($file));
    }

    #[Test]
    public function getPublicUrlReturnsAbsoluteDumpFileUrlForNonPublicStorageWithRequest(): void
    {
        $request = new ServerRequest('https://example.com/', 'GET', null, [], ['HTTP_HOST' => 'example.com', 'HTTPS' => 'on']);
        $GLOBALS['TYPO3_REQUEST'] = $request
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $localDriver = new LocalDriver(['basePath' => $this->instancePath . '/resource-storage-test']);
        $subject = new ResourceStorage($localDriver, ['identifier' => 1, 'name' => 'testing', 'online' => true, 'public' => false], new NoopEventDispatcher());
        $file = new File(['uid' => 5, 'identifier' => '/private/foo.jpg', 'name' => 'foo.jpg'], $subject);

        $publicUrl = $subject->getPublicUrl($file);

        self::assertNotNull($publicUrl);
        self::assertStringStartsWith('https://example.com/', $publicUrl);
        parse_str((string)parse_url($publicUrl, PHP_URL_QUERY), $query);
        self::assertSame('dumpFile', $query['eID']);
        self::assertSame('f', $query['t']);
        self::assertSame('5', $query['f']);
        self::assertNotEmpty($query['token']);
    }

    #[Test]
    public function markAsTemporaryOfflineDoesNotWriteTheStorageRecord(): void
    {
        $storageRepository = $this->get(StorageRepository::class);
        $uid = $storageRepository->addLocalStorage('testing', $this->instancePath . '/resource-storage-test');
        $subject = $storageRepository->findByUid($uid);
        self::assertTrue($subject->isOnline());

        $subject->markAsTemporaryOffline();

        self::assertFalse($subject->isOnline());
        self::assertTrue($this->get(FileStorageConfiguration::class)->get($uid)['online']);
    }

    #[Test]
    public function temporaryOfflineStateIsSharedAcrossInstancesUntilCachesAreFlushed(): void
    {
        $storageRepository = $this->get(StorageRepository::class);
        $uid = $storageRepository->addLocalStorage('testing', $this->instancePath . '/resource-storage-test');
        $storageRepository->findByUid($uid)->markAsTemporaryOffline();

        $storageRepository->flush();
        self::assertFalse($storageRepository->findByUid($uid)->isOnline());

        $this->get(CacheManager::class)->getCache('hash')->flush();
        $storageRepository->flush();
        self::assertTrue($storageRepository->findByUid($uid)->isOnline());
    }

    #[Test]
    public function temporaryOfflineStateExpiresAfterFiveMinutes(): void
    {
        $storageRepository = $this->get(StorageRepository::class);
        $uid = $storageRepository->addLocalStorage('testing', $this->instancePath . '/resource-storage-test');
        $storageRepository->findByUid($uid)->markAsTemporaryOffline();

        $storageRepository->flush();
        self::assertFalse($storageRepository->findByUid($uid)->isOnline());

        $originalExecTime = $GLOBALS['EXEC_TIME'];
        try {
            $GLOBALS['EXEC_TIME'] = $originalExecTime + 299;
            $storageRepository->flush();
            self::assertFalse($storageRepository->findByUid($uid)->isOnline());

            $GLOBALS['EXEC_TIME'] = $originalExecTime + 301;
            $storageRepository->flush();
            self::assertTrue($storageRepository->findByUid($uid)->isOnline());
        } finally {
            $GLOBALS['EXEC_TIME'] = $originalExecTime;
        }
    }

    #[Test]
    public function offlineStateIsNotPersistedInRegistry(): void
    {
        $storageRepository = $this->get(StorageRepository::class);
        $uid = $storageRepository->addLocalStorage('testing', $this->instancePath . '/resource-storage-test');
        $storageRepository->findByUid($uid)->markAsTemporaryOffline();

        $registryEntries = $this->getConnectionPool()
            ->getConnectionForTable('sys_registry')
            ->count('*', 'sys_registry', ['entry_namespace' => 'core']);
        self::assertSame(0, $registryEntries);
    }

    /**
     * @return array<string, array{0: 'read'|'write', 1: array<string, bool>, 2: bool}>
     */
    public static function checkFolderPermissionsFilesystemPermissionsDataProvider(): array
    {
        return [
            'read action on readable/writable folder' => [
                'read',
                ['r' => true, 'w' => true],
                true,
            ],
            'read action on unreadable folder' => [
                'read',
                ['r' => false, 'w' => true],
                false,
            ],
            'write action on read-only folder' => [
                'write',
                ['r' => true, 'w' => false],
                false,
            ],
        ];
    }

    /**
     * @param 'read'|'write' $action
     */
    #[DataProvider('checkFolderPermissionsFilesystemPermissionsDataProvider')]
    #[Test]
    public function checkFolderPermissionsRespectsFilesystemPermissions(string $action, array $permissionsFromDriver, bool $expectedResult): void
    {
        $localDriver = $this->getMockBuilder(LocalDriver::class)
            ->setConstructorArgs([['basePath' => $this->instancePath . '/resource-storage-test']])
            ->onlyMethods(['getPermissions'])
            ->getMock();
        $localDriver->method('getPermissions')->willReturn($permissionsFromDriver);
        $resourceFactoryStub = self::createStub(ResourceFactory::class);
        $folderStub = self::createStub(Folder::class);

        // Let all other checks pass
        $subject = $this->getMockBuilder(ResourceStorage::class)
            ->onlyMethods(['isWritable', 'isBrowsable', 'checkUserActionPermission', 'getResourceFactoryInstance'])
            ->setConstructorArgs([$localDriver, ['identifier' => 1], new NoopEventDispatcher()])
            ->getMock();
        $subject->method('isWritable')->willReturn(true);
        $subject->method('isBrowsable')->willReturn(true);
        $subject->method('checkUserActionPermission')->willReturn(true);
        $subject->method('getResourceFactoryInstance')->willReturn($resourceFactoryStub);
        $subject->setDriver($localDriver);
        self::assertSame($expectedResult, $subject->checkFolderActionPermission($action, $folderStub));
    }

    #[Test]
    public function checkUserActionPermissionsAlwaysReturnsTrueIfNoUserPermissionsAreSet(): void
    {
        $localDriver = new LocalDriver(['basePath' => $this->instancePath . '/resource-storage-test']);
        $subject = new ResourceStorage($localDriver, ['identifier' => 1, 'name' => 'testing'], new NoopEventDispatcher());
        self::assertTrue($subject->checkUserActionPermission('read', 'folder'));
    }

    #[Test]
    public function checkUserActionPermissionReturnsFalseIfPermissionIsSetToZero(): void
    {
        $localDriver = new LocalDriver(['basePath' => $this->instancePath . '/resource-storage-test']);
        $subject = new ResourceStorage($localDriver, ['identifier' => 1, 'name' => 'testing'], new NoopEventDispatcher());
        $subject->setUserPermissions(['readFolder' => true, 'writeFile' => true]);
        self::assertTrue($subject->checkUserActionPermission('read', 'folder'));
    }

    /**
     * @return array<string, array{0: array<string, bool>, 1: string, string}>
     */
    public static function checkUserActionPermission_arbitraryPermissionDataProvider(): array
    {
        return [
            'all lower cased' => [
                ['readFolder' => true],
                'read',
                'folder',
            ],
            'all upper case' => [
                ['readFolder' => true],
                'READ',
                'FOLDER',
            ],
            'mixed case' => [
                ['readFolder' => true],
                'ReaD',
                'FoLdEr',
            ],
        ];
    }

    #[DataProvider('checkUserActionPermission_arbitraryPermissionDataProvider')]
    #[Test]
    public function checkUserActionPermissionAcceptsArbitrarilyCasedArguments(array $permissions, string $action, string $type): void
    {
        $localDriver = new LocalDriver(['basePath' => $this->instancePath . '/resource-storage-test']);
        $subject = new ResourceStorage($localDriver, ['identifier' => 1, 'name' => 'testing'], new NoopEventDispatcher());
        $subject->setUserPermissions($permissions);
        self::assertTrue($subject->checkUserActionPermission($action, $type));
    }

    #[Test]
    public function userActionIsDisallowedIfPermissionIsSetToFalse(): void
    {
        $localDriver = new LocalDriver(['basePath' => $this->instancePath . '/resource-storage-test']);
        $subject = new ResourceStorage($localDriver, ['identifier' => 1, 'name' => 'testing'], new NoopEventDispatcher());
        $subject->setEvaluatePermissions(true);
        $subject->setUserPermissions(['readFolder' => false]);
        self::assertFalse($subject->checkUserActionPermission('read', 'folder'));
    }

    #[Test]
    public function userActionIsDisallowedIfPermissionIsNotSet(): void
    {
        $localDriver = new LocalDriver(['basePath' => $this->instancePath . '/resource-storage-test']);
        $subject = new ResourceStorage($localDriver, ['identifier' => 1, 'name' => 'testing'], new NoopEventDispatcher());
        $subject->setEvaluatePermissions(true);
        $subject->setUserPermissions(['readFolder' => true]);
        self::assertFalse($subject->checkUserActionPermission('write', 'folder'));
    }

    #[Test]
    public function metaDataEditIsNotAllowedWhenWhenNoFileMountsAreSet(): void
    {
        $localDriver = new LocalDriver(['basePath' => $this->instancePath . '/resource-storage-test']);
        $subject = new ResourceStorage($localDriver, ['identifier' => 1, 'name' => 'testing'], new NoopEventDispatcher());
        $subject->setEvaluatePermissions(true);
        self::assertFalse($subject->checkFileActionPermission('editMeta', new File(['identifier' => '/foo/bar.jpg'], $subject)));
    }

    #[Test]
    public function getEvaluatePermissionsWhenSetFalse(): void
    {
        $localDriver = new LocalDriver(['basePath' => $this->instancePath . '/resource-storage-test']);
        $subject = new ResourceStorage($localDriver, ['identifier' => 1, 'name' => 'testing'], new NoopEventDispatcher());
        $subject->setEvaluatePermissions(false);
        self::assertFalse($subject->getEvaluatePermissions());
    }

    #[Test]
    public function getEvaluatePermissionsWhenSetTrue(): void
    {
        $localDriver = new LocalDriver(['basePath' => $this->instancePath . '/resource-storage-test']);
        $subject = new ResourceStorage($localDriver, ['identifier' => 1, 'name' => 'testing'], new NoopEventDispatcher());
        $subject->setEvaluatePermissions(true);
        self::assertTrue($subject->getEvaluatePermissions());
    }

    #[Test]
    public function getProcessingFolderRestoresPermissionEvaluationWhenFolderCreationFails(): void
    {
        $localDriver = new LocalDriver(['basePath' => $this->instancePath . '/resource-storage-test']);
        $subject = $this->getMockBuilder(ResourceStorage::class)
            ->onlyMethods(['createFolder'])
            ->setConstructorArgs([$localDriver, ['identifier' => 1, 'name' => 'testing'], new NoopEventDispatcher()])
            ->getMock();
        $subject->method('createFolder')->willThrowException(new \InvalidArgumentException());
        $subject->setEvaluatePermissions(true);
        $subject->setUserPermissions(['readFolder' => false]);
        self::assertFalse($subject->checkUserActionPermission('read', 'folder'));

        $subject->getProcessingFolder();

        self::assertFalse($subject->checkUserActionPermission('read', 'folder'));
    }

    #[Test]
    public function getProcessingFolderRestoresPermissionEvaluationOnConfiguredStorageWhenFolderCreationFails(): void
    {
        $localDriver = new LocalDriver(['basePath' => $this->instancePath . '/resource-storage-test']);
        $configuredStorage = $this->getMockBuilder(ResourceStorage::class)
            ->onlyMethods(['createFolder'])
            ->setConstructorArgs([$localDriver, ['identifier' => 2, 'name' => 'configured'], new NoopEventDispatcher()])
            ->getMock();
        $configuredStorage->method('createFolder')->willThrowException(new \InvalidArgumentException());
        $configuredStorage->setEvaluatePermissions(true);
        $configuredStorage->setUserPermissions(['readFolder' => true]);
        $storageRepository = $this->createMock(StorageRepository::class);
        $storageRepository->expects($this->once())->method('findByUid')->with(2)->willReturn($configuredStorage);
        GeneralUtility::addInstance(StorageRepository::class, $storageRepository);
        $subject = new ResourceStorage(
            $localDriver,
            ['identifier' => 1, 'name' => 'testing', 'processingFolder' => '2:/_processed_/'],
            new NoopEventDispatcher(),
        );
        self::assertTrue($configuredStorage->getEvaluatePermissions());

        try {
            $subject->getProcessingFolder();
        } catch (\InvalidArgumentException) {
        }

        self::assertTrue($configuredStorage->getEvaluatePermissions());
    }

    #[Test]
    public function getProcessingFolderRestoresPermissionEvaluationWhenNestedFolderCreationFails(): void
    {
        $subject = $this->getAccessibleMock(ResourceStorage::class, null, [], '', false);
        $subject->setEvaluatePermissions(true);
        $file = self::createStub(File::class);
        $file->method('getIdentifier')->willReturn('/file.txt');
        $processingFolder = $this->createMock(Folder::class);
        $processingFolder->method('getSubfolder')->willThrowException(new FolderDoesNotExistException());
        $processingFolder->method('getStorage')->willReturn($subject);
        $processingFolder->method('createFolder')->willThrowException(new \InvalidArgumentException());

        try {
            $subject->_call('getNestedProcessingFolder', $file, $processingFolder);
        } catch (\InvalidArgumentException) {
        }

        self::assertTrue($subject->getEvaluatePermissions());
    }

    #[Test]
    public function deleteFolderThrowsExceptionIfFolderIsNotEmptyAndRecursiveDeleteIsDisabled(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1325952534);
        $folderStub = self::createStub(Folder::class);
        $mockedDriver = $this->createMock(DriverInterface::class);
        $mockedDriver->expects($this->once())->method('isFolderEmpty')->willReturn(false);
        $subject = $this->getAccessibleMock(ResourceStorage::class, ['checkFolderActionPermission'], [], '', false);
        $subject->method('checkFolderActionPermission')->willReturn(true);
        $subject->_set('driver', $mockedDriver);
        $subject->deleteFolder($folderStub);
    }

    #[Test]
    public function renameFileWillCallRenameFileIfUnsanitizedAndNoChangeInTargetFilename(): void
    {
        $driverMock = $this->getMockBuilder(LocalDriver::class)
            ->onlyMethods(['renameFile', 'sanitizeFileName'])
            ->getMock();
        $driverMock->method('sanitizeFileName')
            ->willReturn('a_b.jpg');
        $driverMock->expects($this->once())
            ->method('renameFile')
            ->with('/a b.jpg', 'a_b.jpg');
        $indexerMock = $this->getMockBuilder(Indexer::class)
            ->onlyMethods(['updateIndexEntry'])
            ->disableOriginalConstructor()
            ->getMock();

        $subject = $this->getMockBuilder(ResourceStorage::class)
            ->onlyMethods(['assureFileRenamePermissions', 'getIndexer'])
            ->setConstructorArgs([$driverMock, ['identifier' => 1, 'name' => 'testing'], new NoopEventDispatcher()])
            ->getMock();
        $subject->method('getIndexer')
            ->willReturn($indexerMock);
        $file = new File(
            [
                'identifier' => '/a b.jpg',
                'name' => 'a b.jpg',
                'size' => 1024,
                'mime_type' => 'image/jpeg',
            ],
            $subject,
        );
        $subject->renameFile($file, 'a b.jpg');
    }

    #[Test]
    public function pathAndNameOfUploadedFileIsResolved(): void
    {
        $localDriver = new LocalDriver(['basePath' => $this->instancePath . '/resource-storage-test']);
        $subject = new ResourceStorage($localDriver, ['identifier' => 1, 'name' => 'testing'], new NoopEventDispatcher());

        // the file is not written to the test file-system
        $uploadedFilePath = $this->instancePath . '/resource-storage-test/source.txt';

        $uploadedFile = new UploadedFile(
            $uploadedFilePath,
            0,
            UPLOAD_ERR_OK,
            "directory//up\x00loaded.txt",
            'text/plain'
        );

        self::assertSame($uploadedFilePath, $subject->getUploadedLocalFilePath($uploadedFile));
        self::assertSame('directory__up_loaded.txt', $subject->getUploadedTargetFileName($uploadedFile));
    }

    #[Test]
    public function addFileThrowsIfFileExtensionIsNotAllowedInStorage(): void
    {
        $subject = $this->createStorageWithAllowedFileExtensions('images', 'jpg,png');

        $this->expectException(IllegalFileExtensionException::class);
        $this->expectExceptionCode(1791966674);
        $subject->addFile($this->createLocalFile('document.pdf'), $subject->getRootLevelFolder(false));
    }

    #[Test]
    public function addFileThrowsForFileWithoutExtensionIfAllowedFileExtensionsAreSet(): void
    {
        $subject = $this->createStorageWithAllowedFileExtensions('images', 'jpg');

        $this->expectException(IllegalFileExtensionException::class);
        $this->expectExceptionCode(1791966674);
        $subject->addFile($this->createLocalFile('README'), $subject->getRootLevelFolder(false));
    }

    #[Test]
    public function addFileAcceptsAllowedFileExtensionRegardlessOfCase(): void
    {
        $subject = $this->createStorageWithAllowedFileExtensions('images', ' JPG , png');

        $file = $subject->addFile($this->createLocalFile('photo.Jpg'), $subject->getRootLevelFolder(false));

        self::assertSame('photo.Jpg', $file->getName());
    }

    #[Test]
    public function addFileAcceptsAnyFileExtensionIfAllowedFileExtensionsAreEmpty(): void
    {
        $subject = $this->createStorageWithAllowedFileExtensions('images', '');

        $file = $subject->addFile($this->createLocalFile('document.pdf'), $subject->getRootLevelFolder(false));

        self::assertSame('document.pdf', $file->getName());
    }

    /**
     * The file deny pattern is a security measure, an allowed file
     * extension of a storage must never be able to lift it.
     */
    #[Test]
    public function fileDenyPatternTakesPrecedenceOverAllowedFileExtensions(): void
    {
        $subject = $this->createStorageWithAllowedFileExtensions('images', 'php');

        $this->expectException(IllegalFileExtensionException::class);
        $this->expectExceptionCode(1322120271);
        $subject->addFile($this->createLocalFile('script.php'), $subject->getRootLevelFolder(false));
    }

    #[Test]
    public function renameFileThrowsIfNewFileExtensionIsNotAllowedInStorage(): void
    {
        $subject = $this->createStorageWithAllowedFileExtensions('images', 'jpg');
        $file = $this->createFileInStorage($subject, 'photo.jpg');

        $this->expectException(IllegalFileExtensionException::class);
        $this->expectExceptionCode(1791966676);
        $subject->renameFile($file, 'photo.pdf');
    }

    /**
     * Files that were in the storage before its allowed file extensions
     * were restricted keep their extension and must stay manageable.
     */
    #[Test]
    public function renameFileKeepingAFileExtensionThatIsNotAllowedInStorageIsPossible(): void
    {
        $subject = $this->createStorageWithAllowedFileExtensions('images', 'jpg');
        $file = $this->createFileInStorage($subject, 'document.pdf');

        $renamedFile = $subject->renameFile($file, 'renamed.pdf');

        self::assertSame('renamed.pdf', $renamedFile->getName());
    }

    #[Test]
    public function copyFileThrowsIfFileExtensionIsNotAllowedInTargetStorage(): void
    {
        $sourceStorage = $this->createStorageWithAllowedFileExtensions('documents', '');
        $file = $this->createFileInStorage($sourceStorage, 'document.pdf');
        $subject = $this->createStorageWithAllowedFileExtensions('images', 'jpg');

        $this->expectException(IllegalFileExtensionException::class);
        $this->expectExceptionCode(1791966677);
        $subject->copyFile($file, $subject->getRootLevelFolder(false));
    }

    #[Test]
    public function moveFileThrowsIfFileExtensionIsNotAllowedInTargetStorage(): void
    {
        $sourceStorage = $this->createStorageWithAllowedFileExtensions('documents', '');
        $file = $this->createFileInStorage($sourceStorage, 'document.pdf');
        $subject = $this->createStorageWithAllowedFileExtensions('images', 'jpg');

        $this->expectException(IllegalFileExtensionException::class);
        $this->expectExceptionCode(1791966675);
        $subject->moveFile($file, $subject->getRootLevelFolder(false));
    }

    /**
     * Deleting moves a file into the recycler folder of its storage,
     * which must not be prevented by the allowed file extensions.
     */
    #[Test]
    public function deleteFileMovesFileExtensionThatIsNotAllowedInStorageToRecycler(): void
    {
        $subject = $this->createStorageWithAllowedFileExtensions('images', 'jpg');
        mkdir($this->instancePath . '/resource-storage-test/images/_recycler_');
        $file = $this->createFileInStorage($subject, 'document.pdf');

        $subject->deleteFile($file);

        self::assertFileExists($this->instancePath . '/resource-storage-test/images/_recycler_/document.pdf');
    }

    #[Test]
    public function fileAddedBesidesTypo3WithFileExtensionNotAllowedInStorageIsIndexed(): void
    {
        $subject = $this->createStorageWithAllowedFileExtensions('images', 'jpg');
        touch($this->instancePath . '/resource-storage-test/images/document.pdf');

        new Indexer($subject)->processChangesInStorages();

        $files = $subject->getFilesInFolder($subject->getRootLevelFolder(false));
        self::assertSame(['document.pdf'], array_values(array_map(static fn(File $file): string => $file->getName(), $files)));
        self::assertTrue($subject->getFile('/document.pdf')->isIndexed());
    }

    #[Test]
    public function addFileThrowsIfFileExtensionIsNotAllowedInFileMount(): void
    {
        $subject = $this->createStorageWithFileMounts(['/images/' => 'jpg']);

        $this->expectException(IllegalFileExtensionException::class);
        $this->expectExceptionCode(1791967798);
        $subject->addFile($this->createLocalFile('document.pdf'), $subject->getFolder('/images/'));
    }

    #[Test]
    public function addFileAcceptsFileExtensionAllowedByAnotherFileMountOfTheTargetFolder(): void
    {
        $subject = $this->createStorageWithFileMounts(['/' => '', '/images/' => 'jpg']);

        $file = $subject->addFile($this->createLocalFile('document.pdf'), $subject->getFolder('/images/'));

        self::assertSame('/images/document.pdf', $file->getIdentifier());
    }

    #[Test]
    public function addFileIgnoresAllowedFileExtensionsOfFileMountsWithoutPermissionEvaluation(): void
    {
        $subject = $this->createStorageWithFileMounts(['/images/' => 'jpg']);
        $subject->setEvaluatePermissions(false);

        $file = $subject->addFile($this->createLocalFile('document.pdf'), $subject->getFolder('/images/'));

        self::assertSame('/images/document.pdf', $file->getIdentifier());
    }

    public static function addFileMountMergesAllowedFileExtensionsOfTheSameFolderDataProvider(): \Generator
    {
        yield 'both restricted' => ['jpg', 'PNG, jpg', 'jpg,png'];
        yield 'later one unrestricted' => ['jpg', '', ''];
        yield 'earlier one unrestricted' => ['', 'jpg', ''];
    }

    #[DataProvider('addFileMountMergesAllowedFileExtensionsOfTheSameFolderDataProvider')]
    #[Test]
    public function addFileMountMergesAllowedFileExtensionsOfTheSameFolder(string $allowedFileExtensions, string $otherAllowedFileExtensions, string $expected): void
    {
        $subject = $this->createStorageWithFileMounts(['/images/' => $allowedFileExtensions]);

        $subject->addFileMount('/images/', ['allowed_file_extensions' => $otherAllowedFileExtensions]);

        self::assertSame($expected, $subject->getFileMounts()['/images/']['allowed_file_extensions']);
    }

    #[Test]
    public function moveFileThrowsIfFileExtensionIsNotAllowedInTargetFileMount(): void
    {
        $subject = $this->createStorageWithFileMounts(['/documents/' => '', '/images/' => 'jpg']);
        $file = $this->createFileInStorage($subject, 'documents/document.pdf');

        $this->expectException(IllegalFileExtensionException::class);
        $this->expectExceptionCode(1791967799);
        $subject->moveFile($file, $subject->getFolder('/images/'));
    }

    #[Test]
    public function moveFileWithinFileMountThatDoesNotAllowItsFileExtensionIsPossible(): void
    {
        $subject = $this->createStorageWithFileMounts(['/images/' => 'jpg']);
        mkdir($this->instancePath . '/resource-storage-test/images/images/sub');
        $file = $this->createFileInStorage($subject, 'images/document.pdf');

        $movedFile = $subject->moveFile($file, $subject->getFolder('/images/sub/'));

        self::assertSame('/images/sub/document.pdf', $movedFile->getIdentifier());
    }

    #[Test]
    public function renameFileThrowsIfNewFileExtensionIsNotAllowedInFileMount(): void
    {
        $subject = $this->createStorageWithFileMounts(['/images/' => 'jpg']);
        $file = $this->createFileInStorage($subject, 'images/photo.jpg');

        $this->expectException(IllegalFileExtensionException::class);
        $this->expectExceptionCode(1791967800);
        $subject->renameFile($file, 'photo.pdf');
    }

    #[Test]
    public function copyFileThrowsIfFileExtensionIsNotAllowedInTargetFileMount(): void
    {
        $subject = $this->createStorageWithFileMounts(['/documents/' => '', '/images/' => 'jpg']);
        $file = $this->createFileInStorage($subject, 'documents/document.pdf');

        $this->expectException(IllegalFileExtensionException::class);
        $this->expectExceptionCode(1791967801);
        $subject->copyFile($file, $subject->getFolder('/images/'));
    }

    #[Test]
    public function moveFolderThrowsIfContainedFileExtensionIsNotAllowedInTargetFileMount(): void
    {
        $subject = $this->createStorageWithFileMounts(['/documents/' => '', '/images/' => 'jpg']);
        mkdir($this->instancePath . '/resource-storage-test/images/documents/folder');
        $this->createFileInStorage($subject, 'documents/folder/photo.jpg');
        mkdir($this->instancePath . '/resource-storage-test/images/documents/folder/sub');
        $this->createFileInStorage($subject, 'documents/folder/sub/document.pdf');

        $this->expectException(IllegalFileExtensionException::class);
        $this->expectExceptionCode(1791969207);
        $subject->moveFolder($subject->getFolder('/documents/folder/'), $subject->getFolder('/images/'));
    }

    #[Test]
    public function moveFolderAcceptsContainedFileExtensionsAllowedInTargetFileMount(): void
    {
        $subject = $this->createStorageWithFileMounts(['/documents/' => '', '/images/' => 'jpg']);
        mkdir($this->instancePath . '/resource-storage-test/images/documents/folder');
        $this->createFileInStorage($subject, 'documents/folder/photo.jpg');

        $movedFolder = $subject->moveFolder($subject->getFolder('/documents/folder/'), $subject->getFolder('/images/'));

        self::assertSame('/images/folder/', $movedFolder->getIdentifier());
    }

    #[Test]
    public function moveFolderWithinFileMountThatDoesNotAllowAContainedFileExtensionIsPossible(): void
    {
        $subject = $this->createStorageWithFileMounts(['/images/' => 'jpg']);
        mkdir($this->instancePath . '/resource-storage-test/images/images/folder');
        mkdir($this->instancePath . '/resource-storage-test/images/images/archive');
        $this->createFileInStorage($subject, 'images/folder/document.pdf');

        $movedFolder = $subject->moveFolder($subject->getFolder('/images/folder/'), $subject->getFolder('/images/archive/'));

        self::assertSame('/images/archive/folder/', $movedFolder->getIdentifier());
    }

    #[Test]
    public function copyFolderThrowsIfContainedFileExtensionIsNotAllowedInTargetFileMount(): void
    {
        $subject = $this->createStorageWithFileMounts(['/documents/' => '', '/images/' => 'jpg']);
        mkdir($this->instancePath . '/resource-storage-test/images/documents/folder');
        $this->createFileInStorage($subject, 'documents/folder/document.pdf');

        $this->expectException(IllegalFileExtensionException::class);
        $this->expectExceptionCode(1791969208);
        $subject->copyFolder($subject->getFolder('/documents/folder/'), $subject->getFolder('/images/'));
    }

    #[Test]
    public function moveFileThrowsIfNewFileExtensionIsNotAllowedInStorage(): void
    {
        $subject = $this->createStorageWithAllowedFileExtensions('images', 'jpg');
        mkdir($this->instancePath . '/resource-storage-test/images/sub');
        $file = $this->createFileInStorage($subject, 'photo.jpg');

        $this->expectException(IllegalFileExtensionException::class);
        $this->expectExceptionCode(1791966675);
        $subject->moveFile($file, $subject->getFolder('/sub/'), 'photo.pdf');
    }

    #[Test]
    public function moveFileThrowsIfNewFileExtensionIsNotAllowedInFileMount(): void
    {
        $subject = $this->createStorageWithFileMounts(['/images/' => 'jpg']);
        mkdir($this->instancePath . '/resource-storage-test/images/images/sub');
        $file = $this->createFileInStorage($subject, 'images/photo.jpg');

        $this->expectException(IllegalFileExtensionException::class);
        $this->expectExceptionCode(1791967799);
        $subject->moveFile($file, $subject->getFolder('/images/sub/'), 'photo.pdf');
    }

    #[Test]
    public function addFileIgnoresAllowedFileExtensionsOfReadOnlyFileMounts(): void
    {
        $subject = $this->createStorageWithFileMounts(['/' => 'jpg']);
        mkdir($this->instancePath . '/resource-storage-test/images/images');
        $subject->addFileMount('/images/', ['read_only' => true, 'allowed_file_extensions' => '']);

        $this->expectException(IllegalFileExtensionException::class);
        $this->expectExceptionCode(1791967798);
        $subject->addFile($this->createLocalFile('document.pdf'), $subject->getFolder('/images/'));
    }

    #[Test]
    public function copyFolderThrowsIfContainedFileExtensionIsNotAllowedInStorage(): void
    {
        $subject = $this->createStorageWithAllowedFileExtensions('images', 'jpg');
        mkdir($this->instancePath . '/resource-storage-test/images/folder');
        $this->createFileInStorage($subject, 'folder/document.pdf');

        $this->expectException(IllegalFileExtensionException::class);
        $this->expectExceptionCode(1791970433);
        $subject->copyFolder($subject->getFolder('/folder/'), $subject->getRootLevelFolder(false), 'copy');
    }

    #[Test]
    public function moveFolderIgnoresHiddenFilesForFileMounts(): void
    {
        $subject = $this->createStorageWithFileMounts(['/documents/' => '', '/images/' => 'jpg']);
        mkdir($this->instancePath . '/resource-storage-test/images/documents/folder');
        $this->createFileInStorage($subject, 'documents/folder/photo.jpg');
        touch($this->instancePath . '/resource-storage-test/images/documents/folder/.DS_Store');

        $movedFolder = $subject->moveFolder($subject->getFolder('/documents/folder/'), $subject->getFolder('/images/'));

        self::assertSame('/images/folder/', $movedFolder->getIdentifier());
    }

    #[Test]
    public function getAllowedFileExtensionsInFolderCombinesStorageAndFileMounts(): void
    {
        $subject = $this->createStorageWithFileMounts(['/images/' => 'jpg,png,gif'], 'pdf,jpg,png');

        self::assertSame(['jpg', 'png'], $subject->getAllowedFileExtensionsInFolder($subject->getFolder('/images/'))->toList());
    }

    #[Test]
    public function getAllowedFileExtensionsInFolderIsUnrestrictedWithoutRestrictions(): void
    {
        $subject = $this->createStorageWithFileMounts(['/images/' => '']);

        self::assertFalse($subject->getAllowedFileExtensionsInFolder($subject->getFolder('/images/'))->isRestricted());
    }

    #[Test]
    public function getAllowedFileExtensionsInFolderIgnoresFileMountsWithoutPermissionEvaluation(): void
    {
        $subject = $this->createStorageWithFileMounts(['/images/' => 'jpg'], 'jpg,pdf');
        $subject->setEvaluatePermissions(false);

        self::assertSame(['jpg', 'pdf'], $subject->getAllowedFileExtensionsInFolder($subject->getFolder('/images/'))->toList());
    }

    /**
     * @param array<string, string> $allowedFileExtensionsByFileMount
     */
    private function createStorageWithFileMounts(array $allowedFileExtensionsByFileMount, string $allowedFileExtensionsOfStorage = ''): ResourceStorage
    {
        $storage = $this->createStorageWithAllowedFileExtensions('images', $allowedFileExtensionsOfStorage);
        foreach (array_keys($allowedFileExtensionsByFileMount) as $folderIdentifier) {
            GeneralUtility::mkdir_deep($this->instancePath . '/resource-storage-test/images' . $folderIdentifier);
        }
        $storage->setEvaluatePermissions(true);
        $storage->setUserPermissions(array_fill_keys(
            ['readFolder', 'writeFolder', 'addFolder', 'copyFolder', 'moveFolder', 'addFile', 'readFile', 'writeFile', 'copyFile', 'moveFile', 'renameFile'],
            true
        ));
        foreach ($allowedFileExtensionsByFileMount as $folderIdentifier => $allowedFileExtensions) {
            $storage->addFileMount($folderIdentifier, ['allowed_file_extensions' => $allowedFileExtensions]);
        }
        return $storage;
    }

    private function createStorageWithAllowedFileExtensions(string $name, string $allowedFileExtensions): ResourceStorage
    {
        $basePath = $this->instancePath . '/resource-storage-test/' . $name;
        mkdir($basePath);
        $storageRepository = $this->get(StorageRepository::class);
        $uid = $storageRepository->addLocalStorage($name, $basePath);
        $this->get(FileStorageConfigurationWriter::class)->write($uid, [
            'name' => $name,
            'allowedFileExtensions' => $allowedFileExtensions,
            'configuration' => ['basePath' => $basePath . '/'],
        ]);
        $storageRepository->flush();
        return $storageRepository->findByUid($uid);
    }

    private function createLocalFile(string $fileName): string
    {
        $directory = $this->instancePath . '/resource-storage-test/local';
        if (!is_dir($directory)) {
            mkdir($directory);
        }
        touch($directory . '/' . $fileName);
        return $directory . '/' . $fileName;
    }

    private function createFileInStorage(ResourceStorage $storage, string $fileName): File
    {
        touch($storage->getConfiguration()['basePath'] . $fileName);
        return $storage->getFileByIdentifier('/' . $fileName);
    }

    #[Test]
    public function getProcessingFolderReturnsFolderCreatedByAParallelRequest(): void
    {
        mkdir($this->instancePath . '/resource-storage-test/_processed_');
        $subject = new ResourceStorage(
            $this->createDriverReportingTheProcessingFolderAsMissingOnce($this->instancePath . '/resource-storage-test'),
            ['identifier' => 1, 'name' => 'testing', 'writable' => true, 'browsable' => true, 'online' => true],
            new NoopEventDispatcher()
        );

        $processingFolder = $subject->getProcessingFolder();

        self::assertNotInstanceOf(InaccessibleFolder::class, $processingFolder);
        self::assertSame('/_processed_/', $processingFolder->getIdentifier());
    }

    #[Test]
    public function getProcessingFolderReturnsFolderOfAnotherStorageCreatedByAParallelRequest(): void
    {
        mkdir($this->instancePath . '/resource-storage-test/processing');
        mkdir($this->instancePath . '/resource-storage-test/processing/_processed_');
        $processingStorage = new ResourceStorage(
            $this->createDriverReportingTheProcessingFolderAsMissingOnce($this->instancePath . '/resource-storage-test/processing'),
            ['identifier' => 2, 'name' => 'processing', 'writable' => true, 'browsable' => true, 'online' => true],
            new NoopEventDispatcher()
        );
        $storageRepository = $this->createMock(StorageRepository::class);
        $storageRepository->method('findByUid')->willReturn($processingStorage);
        GeneralUtility::addInstance(StorageRepository::class, $storageRepository);
        $subject = new ResourceStorage(
            new LocalDriver(['basePath' => $this->instancePath . '/resource-storage-test']),
            ['identifier' => 1, 'name' => 'testing', 'writable' => true, 'browsable' => true, 'online' => true, 'processingFolder' => '2:/_processed_/'],
            new NoopEventDispatcher()
        );

        $processingFolder = $subject->getProcessingFolder();

        self::assertNotInstanceOf(InaccessibleFolder::class, $processingFolder);
        self::assertSame('/_processed_/', $processingFolder->getIdentifier());
        self::assertSame(2, $processingFolder->getStorage()->getUid());
    }

    /**
     * The returned driver reports the processing folder as missing on the very first check only,
     * which mimics a parallel request creating that folder right after the check.
     */
    private function createDriverReportingTheProcessingFolderAsMissingOnce(string $basePath): LocalDriver
    {
        return new class (['basePath' => $basePath]) extends LocalDriver {
            private bool $processingFolderReportedAsMissing = false;

            public function folderExists(string $folderIdentifier): bool
            {
                if (!$this->processingFolderReportedAsMissing
                    && trim($folderIdentifier, '/') === ResourceStorageInterface::DEFAULT_ProcessingFolder
                ) {
                    $this->processingFolderReportedAsMissing = true;
                    return false;
                }
                return parent::folderExists($folderIdentifier);
            }
        };
    }
}
