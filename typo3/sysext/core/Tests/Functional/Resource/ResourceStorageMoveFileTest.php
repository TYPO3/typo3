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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ResourceStorageMoveFileTest extends FunctionalTestCase
{
    private const CREATION_DATE = 1262304000;

    private string $storagePath;
    private ResourceStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storagePath = $this->instancePath . '/resource-storage-move-file-test';
        mkdir($this->storagePath . '/source', 0777, true);
        mkdir($this->storagePath . '/target');
        $storageRepository = $this->get(StorageRepository::class);
        $storageUid = $storageRepository->addLocalStorage('move-file-test', $this->storagePath);
        $this->storage = $storageRepository->findByUid($storageUid);
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->storagePath, true);
        parent::tearDown();
    }

    public static function moveAndRenameDataProvider(): array
    {
        return [
            'move' => ['move'],
            'rename' => ['rename'],
        ];
    }

    #[Test]
    #[DataProvider('moveAndRenameDataProvider')]
    public function moveAndRenameFileKeepCreationDateOfIndexedFile(string $action): void
    {
        file_put_contents($this->storagePath . '/source/file.txt', 'content');
        $file = $this->storage->getFileInFolder('file.txt', $this->storage->getFolder('/source/'));
        self::assertInstanceOf(File::class, $file);
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('sys_file');
        $connection->update('sys_file', ['creation_date' => self::CREATION_DATE], ['uid' => $file->getUid()]);
        $row = $connection->select(['*'], 'sys_file', ['uid' => $file->getUid()])->fetchAssociative();
        $file = new File($row, $this->storage);

        if ($action === 'move') {
            $this->storage->moveFile($file, $this->storage->getFolder('/target/'));
        } else {
            $this->storage->renameFile($file, 'renamed.txt');
        }

        $creationDate = $connection->select(['creation_date'], 'sys_file', ['uid' => $file->getUid()])->fetchOne();
        self::assertSame(self::CREATION_DATE, (int)$creationDate);
    }

    #[Test]
    public function moveFileToOtherStorageKeepsCreationDateOfIndexedFile(): void
    {
        $targetStoragePath = $this->instancePath . '/resource-storage-move-file-test-target';
        mkdir($targetStoragePath);
        try {
            $storageRepository = $this->get(StorageRepository::class);
            $targetStorage = $storageRepository->findByUid($storageRepository->addLocalStorage('move-file-test-target', $targetStoragePath));
            file_put_contents($this->storagePath . '/source/file.txt', 'content');
            $file = $this->storage->getFileInFolder('file.txt', $this->storage->getFolder('/source/'));
            self::assertInstanceOf(File::class, $file);
            $connection = $this->get(ConnectionPool::class)->getConnectionForTable('sys_file');
            $connection->update('sys_file', ['creation_date' => self::CREATION_DATE], ['uid' => $file->getUid()]);
            $row = $connection->select(['*'], 'sys_file', ['uid' => $file->getUid()])->fetchAssociative();
            $file = new File($row, $this->storage);

            $targetStorage->moveFile($file, $targetStorage->getRootLevelFolder());

            $row = $connection->select(['storage', 'creation_date'], 'sys_file', ['uid' => $file->getUid()])->fetchAssociative();
            self::assertSame($targetStorage->getUid(), (int)$row['storage']);
            self::assertSame(self::CREATION_DATE, (int)$row['creation_date']);
        } finally {
            GeneralUtility::rmdir($targetStoragePath, true);
        }
    }
}
