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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ResourceStorageTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        mkdir($this->instancePath . '/resource-storage-test');
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->instancePath . '/resource-storage-test', true);
        parent::tearDown();
    }

    #[Test]
    public function addFileFailsIfFileDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1319552745);
        $localDriver = new LocalDriver(['basePath' => $this->instancePath . '/resource-storage-test']);
        $subject = new ResourceStorage($localDriver, ['uid' => 1, 'name' => 'testing'], new NoopEventDispatcher());
        $folder = new Folder($subject, 'someName', 'someName');
        $subject->addFile('/some/random/file', $folder);
    }

    #[Test]
    public function getPublicUrlReturnsNullIfStorageIsNotOnline(): void
    {
        $localDriver = new LocalDriver(['basePath' => $this->instancePath . '/resource-storage-test']);
        $subject = new ResourceStorage($localDriver, ['uid' => 1, 'name' => 'testing'], new NoopEventDispatcher());
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
        $mockedResourceFactory = $this->createMock(ResourceFactory::class);
        $mockedFolder = $this->createMock(Folder::class);

        // Let all other checks pass
        $subject = $this->getMockBuilder(ResourceStorage::class)
            ->onlyMethods(['isWritable', 'isBrowsable', 'checkUserActionPermission', 'getResourceFactoryInstance'])
            ->setConstructorArgs([$localDriver, ['uid' => 1], new NoopEventDispatcher()])
            ->getMock();
        $subject->method('isWritable')->willReturn(true);
        $subject->method('isBrowsable')->willReturn(true);
        $subject->method('checkUserActionPermission')->willReturn(true);
        $subject->method('getResourceFactoryInstance')->willReturn($mockedResourceFactory);
        $subject->setDriver($localDriver);
        self::assertSame($expectedResult, $subject->checkFolderActionPermission($action, $mockedFolder));
    }

    #[Test]
    public function checkUserActionPermissionsAlwaysReturnsTrueIfNoUserPermissionsAreSet(): void
    {
        $localDriver = new LocalDriver(['basePath' => $this->instancePath . '/resource-storage-test']);
        $subject = new ResourceStorage($localDriver, ['uid' => 1, 'name' => 'testing'], new NoopEventDispatcher());
        self::assertTrue($subject->checkUserActionPermission('read', 'folder'));
    }

    #[Test]
    public function checkUserActionPermissionReturnsFalseIfPermissionIsSetToZero(): void
    {
        $localDriver = new LocalDriver(['basePath' => $this->instancePath . '/resource-storage-test']);
        $subject = new ResourceStorage($localDriver, ['uid' => 1, 'name' => 'testing'], new NoopEventDispatcher());
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
        $subject = new ResourceStorage($localDriver, ['uid' => 1, 'name' => 'testing'], new NoopEventDispatcher());
        $subject->setUserPermissions($permissions);
        self::assertTrue($subject->checkUserActionPermission($action, $type));
    }

    #[Test]
    public function userActionIsDisallowedIfPermissionIsSetToFalse(): void
    {
        $localDriver = new LocalDriver(['basePath' => $this->instancePath . '/resource-storage-test']);
        $subject = new ResourceStorage($localDriver, ['uid' => 1, 'name' => 'testing'], new NoopEventDispatcher());
        $subject->setEvaluatePermissions(true);
        $subject->setUserPermissions(['readFolder' => false]);
        self::assertFalse($subject->checkUserActionPermission('read', 'folder'));
    }

    #[Test]
    public function userActionIsDisallowedIfPermissionIsNotSet(): void
    {
        $localDriver = new LocalDriver(['basePath' => $this->instancePath . '/resource-storage-test']);
        $subject = new ResourceStorage($localDriver, ['uid' => 1, 'name' => 'testing'], new NoopEventDispatcher());
        $subject->setEvaluatePermissions(true);
        $subject->setUserPermissions(['readFolder' => true]);
        self::assertFalse($subject->checkUserActionPermission('write', 'folder'));
    }

    #[Test]
    public function metaDataEditIsNotAllowedWhenWhenNoFileMountsAreSet(): void
    {
        $localDriver = new LocalDriver(['basePath' => $this->instancePath . '/resource-storage-test']);
        $subject = new ResourceStorage($localDriver, ['uid' => 1, 'name' => 'testing'], new NoopEventDispatcher());
        $subject->setEvaluatePermissions(true);
        self::assertFalse($subject->checkFileActionPermission('editMeta', new File(['identifier' => '/foo/bar.jpg'], $subject)));
    }

    #[Test]
    public function getEvaluatePermissionsWhenSetFalse(): void
    {
        $localDriver = new LocalDriver(['basePath' => $this->instancePath . '/resource-storage-test']);
        $subject = new ResourceStorage($localDriver, ['uid' => 1, 'name' => 'testing'], new NoopEventDispatcher());
        $subject->setEvaluatePermissions(false);
        self::assertFalse($subject->getEvaluatePermissions());
    }

    #[Test]
    public function getEvaluatePermissionsWhenSetTrue(): void
    {
        $localDriver = new LocalDriver(['basePath' => $this->instancePath . '/resource-storage-test']);
        $subject = new ResourceStorage($localDriver, ['uid' => 1, 'name' => 'testing'], new NoopEventDispatcher());
        $subject->setEvaluatePermissions(true);
        self::assertTrue($subject->getEvaluatePermissions());
    }

    #[Test]
    public function deleteFolderThrowsExceptionIfFolderIsNotEmptyAndRecursiveDeleteIsDisabled(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1325952534);
        $folderMock = $this->createMock(Folder::class);
        $mockedDriver = $this->createMock(DriverInterface::class);
        $mockedDriver->expects(self::once())->method('isFolderEmpty')->willReturn(false);
        $subject = $this->getAccessibleMock(ResourceStorage::class, ['checkFolderActionPermission'], [], '', false);
        $subject->method('checkFolderActionPermission')->willReturn(true);
        $subject->_set('driver', $mockedDriver);
        $subject->deleteFolder($folderMock);
    }

    #[Test]
    public function renameFileWillCallRenameFileIfUnsanitizedAndNoChangeInTargetFilename(): void
    {
        $driverMock = $this->getMockBuilder(LocalDriver::class)
            ->onlyMethods(['renameFile', 'sanitizeFileName'])
            ->getMock();
        $driverMock->method('sanitizeFileName')
            ->willReturn('a_b.jpg');
        $driverMock->expects(self::once())
            ->method('renameFile')
            ->with('/a b.jpg', 'a_b.jpg');
        $indexerMock = $this->getMockBuilder(Indexer::class)
            ->onlyMethods(['updateIndexEntry'])
            ->disableOriginalConstructor()
            ->getMock();

        $subject = $this->getMockBuilder(ResourceStorage::class)
            ->onlyMethods(['assureFileRenamePermissions', 'getIndexer'])
            ->setConstructorArgs([$driverMock, ['uid' => 1, 'name' => 'testing'], new NoopEventDispatcher()])
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
        $subject = new ResourceStorage($localDriver, ['uid' => 1, 'name' => 'testing'], new NoopEventDispatcher());

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
}
