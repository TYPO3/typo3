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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Driver\DriverRegistry;
use TYPO3\CMS\Core\Resource\Driver\FlysystemDriver;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FlysystemStorageTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_file_storage_driver',
    ];

    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/core/Tests/Functional/Resource/Fixtures/FileStorages/flysystem.yaml' => 'typo3conf/system/file-storages.yaml',
    ];

    private string $storagePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storagePath = Environment::getProjectPath() . '/flysystem-storage';
        GeneralUtility::mkdir_deep($this->storagePath);
        GeneralUtility::mkdir_deep(Environment::getVarPath() . '/transient');
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->storagePath, true);
        parent::tearDown();
    }

    private function getStorage(): ResourceStorage
    {
        return $this->get(StorageRepository::class)->findByUid(1);
    }

    #[Test]
    public function filesAreManagedThroughTheStorage(): void
    {
        $storage = $this->getStorage();
        self::assertSame(FlysystemDriver::class, $this->get(DriverRegistry::class)->getDriverClass($storage->getDriverType()));
        self::assertTrue($storage->isOnline());
        self::assertTrue($storage->isPublic());
        $localFile = Environment::getVarPath() . '/transient/upload.txt';
        file_put_contents($localFile, 'content');

        $file = $storage->addFile($localFile, $storage->getRootLevelFolder(), 'Upload File.txt');
        self::assertSame('/Upload_File.txt', $file->getIdentifier());
        self::assertSame(7, $file->getSize());
        self::assertSame(sha1('content'), $file->getSha1());
        self::assertSame('https://cdn.example.com/Upload_File.txt', $file->getPublicUrl());

        $file = $storage->renameFile($file, 'renamed.txt');
        self::assertSame('/renamed.txt', $file->getIdentifier());

        $targetFolder = $storage->createFolder('target');
        $copy = $storage->copyFile($file, $targetFolder);
        self::assertSame('/target/renamed.txt', $copy->getIdentifier());
        $copy = $storage->copyFile($file, $targetFolder, null, DuplicationBehavior::RENAME);
        self::assertSame('/target/renamed_01.txt', $copy->getIdentifier());

        $file = $storage->moveFile($file, $storage->createFolder('moved'));
        self::assertSame('/moved/renamed.txt', $file->getIdentifier());
        self::assertSame('content', $file->getContents());
        self::assertSame(2, $storage->countFilesInFolder($targetFolder));

        $storage->deleteFile($file);
        self::assertFileDoesNotExist($this->storagePath . '/moved/renamed.txt');
    }

    #[Test]
    public function foldersAreManagedThroughTheStorage(): void
    {
        mkdir($this->storagePath . '/source/sub', 0777, true);
        file_put_contents($this->storagePath . '/source/sub/file.txt', 'content');
        $storage = $this->getStorage();
        $file = $storage->getFile('/source/sub/file.txt');

        $folder = $storage->renameFolder($storage->getFolder('/source/'), 'renamed');
        self::assertSame('/renamed/', $folder->getIdentifier());
        self::assertSame('/renamed/sub/file.txt', $storage->getFileByIdentifier('/renamed/sub/file.txt')->getIdentifier());
        self::assertSame($file->getUid(), $storage->getFileByIdentifier('/renamed/sub/file.txt')->getUid());

        $storage->copyFolder($folder, $storage->getRootLevelFolder(), 'copy');
        self::assertStringEqualsFile($this->storagePath . '/copy/sub/file.txt', 'content');

        $storage->deleteFolder($storage->getFolder('/copy/'), true);
        self::assertDirectoryDoesNotExist($this->storagePath . '/copy');
    }

    #[Test]
    public function localDriverIsRegisteredWithAttribute(): void
    {
        self::assertSame([], $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers']);
        self::assertSame(LocalDriver::class, $this->get(DriverRegistry::class)->getDriverClass('Local'));
    }

    #[Test]
    public function driverFactoryCreatesTheDriverWithInjectedDependencies(): void
    {
        file_put_contents($this->storagePath . '/file.txt', 'content');
        $driver = $this->get(DriverRegistry::class)->createDriver('LocalFactory', ['directory' => 'flysystem-storage']);
        self::assertInstanceOf(LocalDriver::class, $driver);
        $driver->processConfiguration();
        self::assertSame('content', $driver->getFileContents('/file.txt'));
    }
}
