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

namespace TYPO3\CMS\Core\Tests\Unit\Localization;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Localization\LanguagePackStatesStore;
use TYPO3\CMS\Core\Locking\LockFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class LanguagePackStatesStoreTest extends UnitTestCase
{
    protected bool $backupEnvironment = true;

    private string $projectPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projectPath = GeneralUtility::tempnam('language-pack-states-store-test-');
        unlink($this->projectPath);
        $varPath = $this->projectPath . '/var';
        GeneralUtility::mkdir_deep($varPath);
        Environment::initialize(
            Environment::getContext(),
            Environment::isCli(),
            true,
            $this->projectPath,
            $this->projectPath . '/public',
            $varPath,
            $this->projectPath . '/config',
            Environment::getCurrentScript(),
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->projectPath, true);
        parent::tearDown();
    }

    private function createSubject(): LanguagePackStatesStore
    {
        return new LanguagePackStatesStore(new LockFactory());
    }

    private function readRawStorageFile(): array
    {
        $storageFile = Environment::getLabelsPath() . '/language_pack_states.json';
        if (!is_readable($storageFile)) {
            return [];
        }
        $contents = file_get_contents($storageFile);
        return $contents !== '' ? json_decode($contents, true) : [];
    }

    #[Test]
    public function setPersistsValueWithoutRelyingOnDestruct(): void
    {
        $writer = $this->createSubject();
        $writer->set('de/lastUpdate', 1700000000);
        unset($writer);

        // A freshly instantiated store must be able to read the value back:
        // persisting must not depend on the writer instance's __destruct().
        $reader = $this->createSubject();
        self::assertSame(1700000000, $reader->get('de/lastUpdate'));
    }

    #[Test]
    public function setDoesNotLoseConcurrentlyWrittenDataFromAnotherInstance(): void
    {
        // Simulates two overlapping requests/processes each holding their
        // own store instance against the same storage file.
        $storeA = $this->createSubject();
        $storeB = $this->createSubject();

        // storeA reads first, caching an empty state internally.
        self::assertNull($storeA->get('de/lastUpdate'));

        // storeB persists a change in the meantime.
        $storeB->set('fr/lastUpdate', 1700000001);

        // storeA now persists its own change. With a naive
        // "load once, keep in memory, overwrite whole file" approach this
        // would drop storeB's change; the fix re-reads the current file
        // state under lock before merging in the new value.
        $storeA->set('de/lastUpdate', 1700000002);

        $verifier = $this->createSubject();
        self::assertSame(1700000001, $verifier->get('fr/lastUpdate'));
        self::assertSame(1700000002, $verifier->get('de/lastUpdate'));
    }

    #[Test]
    public function withBatchDefersWritesUntilItCompletes(): void
    {
        $subject = $this->createSubject();
        $subject->set('de/lastUpdate', 1700000000);

        $subject->withBatch(function () use ($subject) {
            $subject->set('fr/lastUpdate', 1700000001);
            $subject->set('nl/lastUpdate', 1700000002);

            // Both set() calls inside the batch must not have hit disk yet:
            // that is the whole point of batching several writes into one.
            $onDiskWhileBatching = $this->readRawStorageFile();
            self::assertArrayNotHasKey('fr', $onDiskWhileBatching);
            self::assertArrayNotHasKey('nl', $onDiskWhileBatching);
            // The pre-existing entry is still readable through the store though.
            self::assertSame(1700000000, $subject->get('de/lastUpdate'));
        });

        $onDiskAfterBatch = $this->readRawStorageFile();
        self::assertSame(1700000000, $onDiskAfterBatch['de']['lastUpdate']);
        self::assertSame(1700000001, $onDiskAfterBatch['fr']['lastUpdate']);
        self::assertSame(1700000002, $onDiskAfterBatch['nl']['lastUpdate']);
    }

    #[Test]
    public function withBatchPersistsProgressMadeBeforeAnExceptionIsThrown(): void
    {
        $subject = $this->createSubject();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1787822400);
        try {
            $subject->withBatch(function () use ($subject): void {
                $subject->set('de/lastUpdate', 1700000000);
                throw new \RuntimeException('aborted mid-batch', 1787822400);
            });
        } finally {
            // The change made before the exception must still be persisted,
            // matching the pre-batch behavior where every set() call
            // persisted immediately on its own.
            $verifier = $this->createSubject();
            self::assertSame(1700000000, $verifier->get('de/lastUpdate'));
        }
    }

    #[Test]
    public function withBatchCanBeNestedWithoutDeadlocking(): void
    {
        $subject = $this->createSubject();

        $subject->withBatch(function () use ($subject): void {
            $subject->set('de/lastUpdate', 1700000000);
            // A nested call, e.g. LanguagePackService::setLastUpdatedIsoCode()
            // invoked from within an outer batch, must join the running
            // batch instead of acquiring the lock a second time.
            $subject->withBatch(function () use ($subject): void {
                $subject->set('fr/lastUpdate', 1700000001);
            });
        });

        $verifier = $this->createSubject();
        self::assertSame(1700000000, $verifier->get('de/lastUpdate'));
        self::assertSame(1700000001, $verifier->get('fr/lastUpdate'));
    }
}
