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

use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Resource\Driver\AbstractDriver;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ResourceStorageTest extends FunctionalTestCase
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

    /**
     * @test
     */
    public function addFileFailsIfFileDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1319552745);
        $localDriver = new LocalDriver(['basePath' => $this->instancePath . '/resource-storage-test']);
        $subject = new ResourceStorage($localDriver, ['name' => 'testing'], new NoopEventDispatcher());
        $folder = new Folder($subject, 'someName', 'someName');
        $subject->addFile('/some/random/file', $folder);
    }

    /**
     * @test
     */
    public function getPublicUrlReturnsNullIfStorageIsNotOnline(): void
    {
        $localDriver = new LocalDriver(['basePath' => $this->instancePath . '/resource-storage-test']);
        $subject = new ResourceStorage($localDriver, ['name' => 'testing'], new NoopEventDispatcher());
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
     * @test
     * @dataProvider checkFolderPermissionsFilesystemPermissionsDataProvider
     * @param 'read'|'write' $action
     */
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
            ->setConstructorArgs([$localDriver, [], new NoopEventDispatcher()])
            ->getMock();
        $subject->method('isWritable')->willReturn(true);
        $subject->method('isBrowsable')->willReturn(true);
        $subject->method('checkUserActionPermission')->willReturn(true);
        $subject->method('getResourceFactoryInstance')->willReturn($mockedResourceFactory);
        $subject->setDriver($localDriver);
        self::assertSame($expectedResult, $subject->checkFolderActionPermission($action, $mockedFolder));
    }

    /**
     * @test
     */
    public function checkUserActionPermissionsAlwaysReturnsTrueIfNoUserPermissionsAreSet(): void
    {
        $localDriver = new LocalDriver(['basePath' => $this->instancePath . '/resource-storage-test']);
        $subject = new ResourceStorage($localDriver, ['name' => 'testing'], new NoopEventDispatcher());
        self::assertTrue($subject->checkUserActionPermission('read', 'folder'));
    }

    /**
     * @test
     */
    public function checkUserActionPermissionReturnsFalseIfPermissionIsSetToZero(): void
    {
        $localDriver = new LocalDriver(['basePath' => $this->instancePath . '/resource-storage-test']);
        $subject = new ResourceStorage($localDriver, ['name' => 'testing'], new NoopEventDispatcher());
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

    /**
     * @test
     * @dataProvider checkUserActionPermission_arbitraryPermissionDataProvider
     */
    public function checkUserActionPermissionAcceptsArbitrarilyCasedArguments(array $permissions, string $action, string $type): void
    {
        $localDriver = new LocalDriver(['basePath' => $this->instancePath . '/resource-storage-test']);
        $subject = new ResourceStorage($localDriver, ['name' => 'testing'], new NoopEventDispatcher());
        $subject->setUserPermissions($permissions);
        self::assertTrue($subject->checkUserActionPermission($action, $type));
    }

    /**
     * @test
     */
    public function userActionIsDisallowedIfPermissionIsSetToFalse(): void
    {
        $localDriver = new LocalDriver(['basePath' => $this->instancePath . '/resource-storage-test']);
        $subject = new ResourceStorage($localDriver, ['name' => 'testing'], new NoopEventDispatcher());
        $subject->setEvaluatePermissions(true);
        $subject->setUserPermissions(['readFolder' => false]);
        self::assertFalse($subject->checkUserActionPermission('read', 'folder'));
    }

    /**
     * @test
     */
    public function userActionIsDisallowedIfPermissionIsNotSet(): void
    {
        $localDriver = new LocalDriver(['basePath' => $this->instancePath . '/resource-storage-test']);
        $subject = new ResourceStorage($localDriver, ['name' => 'testing'], new NoopEventDispatcher());
        $subject->setEvaluatePermissions(true);
        $subject->setUserPermissions(['readFolder' => true]);
        self::assertFalse($subject->checkUserActionPermission('write', 'folder'));
    }

    /**
     * @test
     */
    public function metaDataEditIsNotAllowedWhenWhenNoFileMountsAreSet(): void
    {
        $localDriver = new LocalDriver(['basePath' => $this->instancePath . '/resource-storage-test']);
        $subject = new ResourceStorage($localDriver, ['name' => 'testing'], new NoopEventDispatcher());
        $subject->setEvaluatePermissions(true);
        self::assertFalse($subject->checkFileActionPermission('editMeta', new File(['identifier' => '/foo/bar.jpg'], $subject)));
    }

    /**
     * @test
     */
    public function getEvaluatePermissionsWhenSetFalse(): void
    {
        $localDriver = new LocalDriver(['basePath' => $this->instancePath . '/resource-storage-test']);
        $subject = new ResourceStorage($localDriver, ['name' => 'testing'], new NoopEventDispatcher());
        $subject->setEvaluatePermissions(false);
        self::assertFalse($subject->getEvaluatePermissions());
    }

    /**
     * @test
     */
    public function getEvaluatePermissionsWhenSetTrue(): void
    {
        $localDriver = new LocalDriver(['basePath' => $this->instancePath . '/resource-storage-test']);
        $subject = new ResourceStorage($localDriver, ['name' => 'testing'], new NoopEventDispatcher());
        $subject->setEvaluatePermissions(true);
        self::assertTrue($subject->getEvaluatePermissions());
    }

    /**
     * @test
     */
    public function deleteFolderThrowsExceptionIfFolderIsNotEmptyAndRecursiveDeleteIsDisabled(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1325952534);
        $folderMock = $this->createMock(Folder::class);
        $mockedDriver = $this->getMockForAbstractClass(AbstractDriver::class);
        $mockedDriver->expects(self::once())->method('isFolderEmpty')->willReturn(false);
        $subject = $this->getAccessibleMock(ResourceStorage::class, ['checkFolderActionPermission'], [], '', false);
        $subject->method('checkFolderActionPermission')->willReturn(true);
        $subject->_set('driver', $mockedDriver);
        $subject->deleteFolder($folderMock);
    }
}
