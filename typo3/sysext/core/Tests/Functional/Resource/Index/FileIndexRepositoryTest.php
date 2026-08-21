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

namespace TYPO3\CMS\Core\Tests\Functional\Resource\Index;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\FileIndexRepository;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FileIndexRepositoryTest extends FunctionalTestCase
{
    #[Test]
    public function hasIndexRecordUsesIdentifierHashForFilesWithoutUid(): void
    {
        $identifier = '/file.txt';
        $identifierHash = sha1($identifier);
        $this->get(ConnectionPool::class)->getConnectionForTable('sys_file')->insert('sys_file', [
            'storage' => 1,
            'identifier' => $identifier,
            'identifier_hash' => $identifierHash,
        ]);
        $storage = $this->createMock(ResourceStorage::class);
        $storage->method('getUid')->willReturn(1);
        $storage->expects($this->once())->method('hashFileIdentifier')->willReturn($identifierHash);

        self::assertTrue($this->get(FileIndexRepository::class)->hasIndexRecord(new File(['uid' => 0, 'identifier' => $identifier], $storage)));
    }

    #[Test]
    public function findInStorageWithIndexOutstandingExcludesMissingFiles(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FilesForIndexOutstanding.csv');

        $storageStub = self::createStub(ResourceStorage::class);
        $storageStub->method('getUid')->willReturn(1);

        $result = $this->get(FileIndexRepository::class)->findInStorageWithIndexOutstanding($storageStub);

        self::assertCount(1, $result);
        self::assertSame(1, $result[0]['uid']);
    }

    #[Test]
    public function findInStorageWithIndexOutstandingExcludesAlreadyIndexedFiles(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FilesForIndexOutstanding.csv');

        $storageStub = self::createStub(ResourceStorage::class);
        $storageStub->method('getUid')->willReturn(2);

        $result = $this->get(FileIndexRepository::class)->findInStorageWithIndexOutstanding($storageStub);

        self::assertCount(1, $result);
        self::assertSame(3, $result[0]['uid']);
    }
}
