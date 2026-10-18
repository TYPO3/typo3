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
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class IndexerTest extends FunctionalTestCase
{
    private string $basePath;
    private ResourceStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->basePath = $this->instancePath . '/indexer-test';
        mkdir($this->basePath);
        $storageRepository = $this->get(StorageRepository::class);
        $this->storage = $storageRepository->findByUid($storageRepository->createLocalStorage('indexer-test', $this->basePath, 'absolute'));
        $this->storage->setEvaluatePermissions(false);
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->basePath, true);
        parent::tearDown();
    }

    #[Test]
    public function processChangesInStoragesIndexesNewFiles(): void
    {
        file_put_contents($this->basePath . '/a.txt', 'a');
        mkdir($this->basePath . '/sub');
        file_put_contents($this->basePath . '/sub/b.txt', 'bb');

        $this->runIndexer();

        $records = $this->getFileRecordsByIdentifier();
        self::assertSame(['/a.txt', '/sub/b.txt'], array_keys($records));
        self::assertSame(1, (int)$records['/a.txt']['size']);
        self::assertSame(2, (int)$records['/sub/b.txt']['size']);
        self::assertSame(0, (int)$records['/a.txt']['missing']);
    }

    #[Test]
    public function processChangesInStoragesUpdatesFilesWithChangedModificationTime(): void
    {
        file_put_contents($this->basePath . '/a.txt', 'a');
        touch($this->basePath . '/a.txt', time() - 100);
        $this->runIndexer();
        $uid = (int)$this->getFileRecordsByIdentifier()['/a.txt']['uid'];

        file_put_contents($this->basePath . '/a.txt', 'changed');
        touch($this->basePath . '/a.txt', time());
        $this->runIndexer();

        $record = $this->getFileRecordsByIdentifier()['/a.txt'];
        self::assertSame($uid, (int)$record['uid']);
        self::assertSame(7, (int)$record['size']);
        self::assertSame(sha1('changed'), $record['sha1']);
    }

    #[Test]
    public function processChangesInStoragesMarksDeletedFilesAsMissing(): void
    {
        file_put_contents($this->basePath . '/a.txt', 'a');
        file_put_contents($this->basePath . '/b.txt', 'b');
        $this->runIndexer();

        unlink($this->basePath . '/a.txt');
        $this->runIndexer();

        $records = $this->getFileRecordsByIdentifier();
        self::assertSame(1, (int)$records['/a.txt']['missing']);
        self::assertSame(0, (int)$records['/b.txt']['missing']);
    }

    #[Test]
    public function processChangesInStoragesUnmarksReappearingFilesAsMissing(): void
    {
        file_put_contents($this->basePath . '/a.txt', 'a');
        $this->runIndexer();
        unlink($this->basePath . '/a.txt');
        $this->runIndexer();

        file_put_contents($this->basePath . '/a.txt', 'a');
        $this->runIndexer();

        self::assertSame(0, (int)$this->getFileRecordsByIdentifier()['/a.txt']['missing']);
    }

    #[Test]
    public function processChangesInStoragesKeepsRecordOfMovedFile(): void
    {
        file_put_contents($this->basePath . '/a.txt', 'moved content');
        $this->runIndexer();
        $uid = (int)$this->getFileRecordsByIdentifier()['/a.txt']['uid'];

        mkdir($this->basePath . '/sub');
        rename($this->basePath . '/a.txt', $this->basePath . '/sub/a.txt');
        $this->runIndexer();

        $records = $this->getFileRecordsByIdentifier();
        self::assertSame(['/sub/a.txt'], array_keys($records));
        self::assertSame($uid, (int)$records['/sub/a.txt']['uid']);
        self::assertSame(0, (int)$records['/sub/a.txt']['missing']);
    }

    #[Test]
    public function processChangesInStoragesDoesNotTouchRecordsOfOtherStorages(): void
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('sys_file');
        $connection->insert('sys_file', [
            'storage' => $this->storage->getUid() + 1,
            'identifier' => '/a.txt',
            'identifier_hash' => sha1('/a.txt'),
            'missing' => 0,
        ]);
        file_put_contents($this->basePath . '/b.txt', 'b');

        $this->runIndexer();

        $missing = $connection->select(['missing'], 'sys_file', ['storage' => $this->storage->getUid() + 1])->fetchOne();
        self::assertSame(0, (int)$missing);
    }

    private function runIndexer(): void
    {
        $this->get(CacheManager::class)->getCache('runtime')->flush();
        (new Indexer($this->storage))->processChangesInStorages();
    }

    private function getFileRecordsByIdentifier(): array
    {
        $rows = $this->get(ConnectionPool::class)->getConnectionForTable('sys_file')
            ->select(['*'], 'sys_file', ['storage' => $this->storage->getUid()], [], ['identifier' => 'ASC'])
            ->fetchAllAssociative();
        return array_column($rows, null, 'identifier');
    }
}
