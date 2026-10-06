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

namespace TYPO3\CMS\Scheduler\Tests\Functional\Task;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\RecyclerGarbageCollectionTask;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RecyclerGarbageCollectionTaskTest extends FunctionalTestCase
{
    private const DAY = 86400;

    protected array $coreExtensionsToLoad = ['scheduler'];

    private int $now;

    protected function setUp(): void
    {
        parent::setUp();
        $this->now = time();
        $GLOBALS['EXEC_TIME'] = $this->now;
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir(Environment::getPublicPath() . '/fileadmin', true);
        mkdir(Environment::getPublicPath() . '/fileadmin');
        GeneralUtility::rmdir(Environment::getPublicPath() . '/documents', true);
        parent::tearDown();
    }

    #[Test]
    public function removesExpiredFilesFromRecyclerFoldersAtAnyDepth(): void
    {
        $old = $this->now - 31 * self::DAY;
        $fresh = $this->now - 29 * self::DAY;
        $this->createFiles('fileadmin', [
            '_recycler_/old.txt' => $old,
            '_recycler_/fresh.txt' => $fresh,
            '_recycler_/emptied/old.txt' => $old,
            'folder/_recycler_/old.txt' => $old,
            'folder/_recycler_/fresh.txt' => $fresh,
            'folder/_recycler_/emptied/old.txt' => $old,
            'folder/_recycler_/kept/fresh.txt' => $fresh,
            'a/b/_recycler_/old.txt' => $old,
            'a/b/_recycler_/c/d/old.txt' => $old,
            'a/b/_recycler_/c/fresh.txt' => $fresh,
            'folder/old.txt' => $old,
            '_processed_/old.txt' => $old,
            '_processed_/_recycler_/old.txt' => $old,
        ]);

        $task = new RecyclerGarbageCollectionTask();
        $task->setTaskParameters(['number_of_days' => 30]);
        self::assertTrue($task->execute());

        $this->assertRemaining('fileadmin', [
            '_recycler_/fresh.txt',
            'folder/_recycler_/fresh.txt',
            'folder/_recycler_/kept/fresh.txt',
            'a/b/_recycler_/c/fresh.txt',
            'folder/old.txt',
            '_processed_/old.txt',
            '_processed_/_recycler_/old.txt',
        ]);
        $this->assertRemovedFolders('fileadmin', [
            '_recycler_/emptied',
            'folder/_recycler_/emptied',
            'a/b/_recycler_/c/d',
        ]);
        $this->assertKeptFolders('fileadmin', [
            '_recycler_',
            'folder/_recycler_',
            'a/b/_recycler_',
            'a/b/_recycler_/c',
        ]);
    }

    #[Test]
    public function removesExpiredFilesFromNestedRecyclerFoldersOfAllStorages(): void
    {
        $old = $this->now - 31 * self::DAY;
        $fresh = $this->now - 29 * self::DAY;
        $this->createFiles('fileadmin', [
            'folder/_recycler_/old.txt' => $old,
        ]);
        $this->createFiles('documents', [
            '_recycler_/old.txt' => $old,
            'nested/_recycler_/old.txt' => $old,
            'nested/_recycler_/fresh.txt' => $fresh,
        ]);
        // Resolve the default fileadmin storage first, so it gets uid 1
        $storageRepository = $this->get(StorageRepository::class);
        $storageRepository->findAll();
        $storageRepository->createLocalStorage('documents', 'documents/', 'relative');

        $task = new RecyclerGarbageCollectionTask();
        $task->setTaskParameters(['number_of_days' => 30]);
        self::assertTrue($task->execute());

        $this->assertRemaining('fileadmin', []);
        $this->assertRemaining('documents', ['nested/_recycler_/fresh.txt']);
    }

    #[Test]
    public function removesExpiredFilesFromRecyclerFolderNestedInRecyclerFolder(): void
    {
        $old = $this->now - 31 * self::DAY;
        $this->createFiles('fileadmin', [
            '_recycler_/old.txt' => $old,
            '_recycler_/sub/old.txt' => $old,
            '_recycler_/sub/_recycler_/old.txt' => $old,
            'folder/_recycler_/sub/_recycler_/old.txt' => $old,
        ]);

        $task = new RecyclerGarbageCollectionTask();
        $task->setTaskParameters(['number_of_days' => 30]);
        self::assertTrue($task->execute());

        $this->assertRemaining('fileadmin', []);
    }

    /**
     * @param array<string, int> $files relative path => modification time
     */
    private function createFiles(string $baseFolder, array $files): void
    {
        $basePath = Environment::getPublicPath() . '/' . $baseFolder . '/';
        foreach ($files as $path => $modificationTime) {
            GeneralUtility::mkdir_deep(dirname($basePath . $path));
            file_put_contents($basePath . $path, $path);
            touch($basePath . $path, $modificationTime);
        }
    }

    /**
     * @param list<string> $expected relative paths of all files expected to remain
     */
    private function assertRemaining(string $baseFolder, array $expected): void
    {
        $basePath = Environment::getPublicPath() . '/' . $baseFolder . '/';
        $remaining = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS)
        );
        foreach ($iterator as $file) {
            $relativePath = substr($file->getPathname(), strlen($basePath));
            // Ignore files created by the storage itself
            if (str_starts_with(basename($relativePath), '.') || $relativePath === 'user_upload/index.html') {
                continue;
            }
            $remaining[] = $relativePath;
        }
        sort($remaining);
        sort($expected);
        self::assertSame($expected, $remaining);
    }

    /**
     * @param list<string> $folders
     */
    private function assertRemovedFolders(string $baseFolder, array $folders): void
    {
        foreach ($folders as $folder) {
            self::assertDirectoryDoesNotExist(Environment::getPublicPath() . '/' . $baseFolder . '/' . $folder);
        }
    }

    /**
     * @param list<string> $folders
     */
    private function assertKeptFolders(string $baseFolder, array $folders): void
    {
        foreach ($folders as $folder) {
            self::assertDirectoryExists(Environment::getPublicPath() . '/' . $baseFolder . '/' . $folder);
        }
    }
}
