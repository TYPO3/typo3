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

namespace TYPO3\CMS\Core\Tests\Functional\Upgrades;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Configuration\FileStorageConfigurationWriter;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Upgrades\FallbackStorageMigration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FallbackStorageMigrationTest extends FunctionalTestCase
{
    private string $baseDataSet = __DIR__ . '/Fixtures/FallbackStorageMigrationBase.csv';
    private string $resultDataSet = __DIR__ . '/Fixtures/FallbackStorageMigrationMigrated.csv';

    protected function tearDown(): void
    {
        @unlink(Environment::getConfigPath() . '/system/file-storages.yaml');
        GeneralUtility::rmdir(Environment::getPublicPath() . '/fileadmin/legacy', true);
        GeneralUtility::rmdir(Environment::getPublicPath() . '/fileadmin/existing', true);
        parent::tearDown();
    }

    #[Test]
    public function fallbackStorageFileRecordsAreMigrated(): void
    {
        // A file physically residing in the fileadmin storage, but indexed in the fallback storage (uid 0)
        GeneralUtility::mkdir_deep(Environment::getPublicPath() . '/fileadmin/legacy');
        file_put_contents(Environment::getPublicPath() . '/fileadmin/legacy/file-one.txt', 'file-one');
        // A file indexed both in the fallback storage and in the fileadmin storage
        GeneralUtility::mkdir_deep(Environment::getPublicPath() . '/fileadmin/existing');
        file_put_contents(Environment::getPublicPath() . '/fileadmin/existing/duplicate.txt', 'duplicate');

        $this->importCSVDataSet($this->baseDataSet);
        $subject = $this->get(FallbackStorageMigration::class);
        self::assertTrue($subject->updateNecessary(), 'updateNecessary before update');

        $subject->executeUpdate();

        // - sys_file:10 has been moved into the fileadmin storage with recalculated identifier and hashes
        // - sys_file:11 was a duplicate of sys_file:20: its references now point to
        //   sys_file:20, its metadata and the sys_file record itself have been deleted
        // - sys_file:12 resides outside all storages: flagged as missing, attached to the default storage
        // - sys_file_processedfile:1 of the fallback storage has been deleted
        $this->assertCSVDataSet($this->resultDataSet);
        self::assertFalse($subject->updateNecessary(), 'updateNecessary after update');

        // Running again must not change anything
        $subject->executeUpdate();
        $this->assertCSVDataSet($this->resultDataSet);
    }

    #[Test]
    public function fileRecordsFlaggedAsMissingAreHashedForTheDefaultStorage(): void
    {
        $this->get(FileStorageConfigurationWriter::class)->writeAll([
            1 => ['name' => 'fileadmin', 'default' => true, 'configuration' => ['basePath' => 'fileadmin/', 'caseSensitive' => false]],
        ]);
        $this->get(StorageRepository::class)->flush();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FallbackStorageMigrationCaseInsensitive.csv');

        $this->get(FallbackStorageMigration::class)->executeUpdate();

        // The hashes are calculated the way the case-insensitive default storage does, so the
        // indexer of that storage finds the record again once the file is moved into it
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FallbackStorageMigrationCaseInsensitiveMigrated.csv');
    }
}
