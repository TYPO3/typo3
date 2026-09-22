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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Output\BufferedOutput;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Configuration\FileStorageConfiguration;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Upgrades\FileStorageConfigurationMigration;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FileStorageConfigurationMigrationTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_file_storage_table',
    ];

    private string $configFile;
    private BufferedOutput $output;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configFile = Environment::getConfigPath() . '/system/' . FileStorageConfiguration::CONFIG_FILE_NAME;
        $this->output = new BufferedOutput();
    }

    protected function tearDown(): void
    {
        @unlink($this->configFile);
        parent::tearDown();
    }

    private function getSubject(): FileStorageConfigurationMigration
    {
        $subject = $this->get(FileStorageConfigurationMigration::class);
        $subject->setOutput($this->output);
        return $subject;
    }

    private function insertStorage(int $uid, string $name, string $driver, array $configuration, array $flags = [], string $processingFolder = ''): void
    {
        $flexFormData = [];
        foreach ($configuration as $key => $value) {
            $flexFormData[$key] = ['vDEF' => $value];
        }
        $this->getConnectionPool()->getConnectionForTable('sys_file_storage')->insert('sys_file_storage', array_replace([
            'uid' => $uid,
            'pid' => 0,
            'name' => $name,
            'driver' => $driver,
            'configuration' => $this->get(FlexFormTools::class)->flexArray2Xml(['data' => ['sDEF' => ['lDEF' => $flexFormData]]]),
            'is_browsable' => 1,
            'is_default' => 0,
            'is_public' => 1,
            'is_writable' => 1,
            'is_online' => 1,
            'auto_extract_metadata' => 1,
            'processingfolder' => $processingFolder,
        ], $flags));
    }

    #[Test]
    public function updateIsNotNecessaryWithoutRecords(): void
    {
        self::assertFalse($this->getSubject()->updateNecessary());
    }

    #[Test]
    public function updateIsNotNecessaryWhenTheOnlyRecordEqualsTheImplicitDefault(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FileStorageConfigurationMigrationDefaultOnly.csv');
        self::assertFalse($this->getSubject()->updateNecessary());
        self::assertFileDoesNotExist($this->configFile);
    }

    #[Test]
    public function updateIsNotNecessaryWithoutTable(): void
    {
        $schemaManager = $this->getConnectionPool()->getConnectionForTable('sys_file_storage')->createSchemaManager();
        $schemaManager->renameTable('sys_file_storage', 'tx_test_moved_file_storage');
        try {
            $subject = $this->getSubject();
            self::assertFalse($subject->updateNecessary());
            self::assertTrue($subject->executeUpdate());
            self::assertFileDoesNotExist($this->configFile);
        } finally {
            $schemaManager->renameTable('tx_test_moved_file_storage', 'sys_file_storage');
        }
    }

    #[Test]
    public function updateIsNotNecessaryWithDeletedRecordsOnly(): void
    {
        $this->insertStorage(1, 'fileadmin', 'Local', ['basePath' => 'fileadmin/', 'pathType' => 'relative'], ['is_default' => 1, 'deleted' => 1]);
        $this->insertStorage(2, 'Media', 'Local', ['basePath' => 'media/', 'pathType' => 'relative'], ['deleted' => 1]);

        $subject = $this->getSubject();
        self::assertFalse($subject->updateNecessary());
        self::assertTrue($subject->executeUpdate());
        self::assertFileDoesNotExist($this->configFile);
    }

    #[Test]
    public function executingTheUpdateForTheImplicitDefaultWritesNoFile(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FileStorageConfigurationMigrationDefaultOnly.csv');

        self::assertTrue($this->getSubject()->executeUpdate());
        self::assertFileDoesNotExist($this->configFile);
    }

    public static function implicitDefaultWithChangedSettingIsMigratedDataProvider(): array
    {
        return [
            'not public' => [['is_public' => 0], []],
            'not writable' => [['is_writable' => 0], []],
            'not browsable' => [['is_browsable' => 0], []],
            'offline' => [['is_online' => 0], []],
            'no metadata extraction' => [['auto_extract_metadata' => 0], []],
            'processing folder' => [['processingfolder' => '1:/_processed_custom/'], []],
            'other base path' => [[], ['basePath' => 'media/']],
            'base URI' => [[], ['baseUri' => 'https://cdn.example.com/']],
            'other driver' => [['driver' => 'AmazonS3'], []],
        ];
    }

    #[DataProvider('implicitDefaultWithChangedSettingIsMigratedDataProvider')]
    #[Test]
    public function implicitDefaultWithChangedSettingIsMigrated(array $flags, array $configuration): void
    {
        $this->insertStorage(1, 'fileadmin', 'Local', array_replace(['basePath' => 'fileadmin/', 'pathType' => 'relative'], $configuration), array_replace(['is_default' => 1], $flags));

        $subject = $this->getSubject();
        self::assertTrue($subject->updateNecessary());
        self::assertTrue($subject->executeUpdate());
        self::assertFileExists($this->configFile);
        self::assertFalse($subject->updateNecessary());
    }

    #[Test]
    public function updateIsNotNecessaryWhenTheFileAlreadyDefinesStorages(): void
    {
        $this->insertStorage(2, 'Media', 'Local', ['basePath' => 'media/', 'pathType' => 'relative']);
        file_put_contents($this->configFile, "storages:\n  2:\n    name: media\n");
        self::assertFalse($this->getSubject()->updateNecessary());
    }

    #[Test]
    public function recordsAreWrittenToTheFile(): void
    {
        $projectPath = Environment::getProjectPath();
        $this->insertStorage(1, 'fileadmin/ (auto-created)', 'Local', ['basePath' => 'fileadmin/', 'pathType' => 'relative', 'caseSensitive' => '1'], ['is_default' => 1]);
        $this->insertStorage(3, 'Private Files', 'Local', ['basePath' => $projectPath . '/private-files/', 'pathType' => 'absolute', 'caseSensitive' => '0', 'baseUri' => ''], ['is_public' => 0, 'is_writable' => 0, 'auto_extract_metadata' => 0], '1:/processed/private/');
        $this->insertStorage(4, 'Old Uploads', 'Local', ['basePath' => 'uploads/', 'pathType' => 'relative'], ['is_online' => 0]);
        $this->insertStorage(5, 'Elsewhere', 'Local', ['basePath' => '/var/shared/files/', 'pathType' => 'absolute']);
        $this->insertStorage(7, 'Assets (S3)', 'AmazonS3', ['bucket' => 'assets', 'region' => 'eu-central-1']);

        $subject = $this->getSubject();
        self::assertTrue($subject->updateNecessary());
        self::assertTrue($subject->executeUpdate());
        self::assertFalse($subject->updateNecessary());

        self::assertSame(
            "storages:\n"
            . "  1:\n    name: 'fileadmin/ (auto-created)'\n    default: true\n    configuration:\n      basePath: fileadmin/\n      caseSensitive: true\n"
            . "  3:\n    name: 'Private Files'\n    public: false\n    writable: false\n    autoExtractMetadata: false\n    processingFolder: '1:/processed/private/'\n    configuration:\n      basePath: private-files/\n      caseSensitive: false\n"
            . "  4:\n    name: 'Old Uploads'\n    online: false\n    configuration:\n      basePath: uploads/\n"
            . "  5:\n    name: Elsewhere\n    configuration:\n      basePath: /var/shared/files/\n"
            . "  7:\n    name: 'Assets (S3)'\n    driver: AmazonS3\n    configuration:\n      bucket: assets\n      region: eu-central-1\n",
            file_get_contents($this->configFile)
        );
        self::assertStringContainsString('uses the driver "AmazonS3", which is not registered', $this->output->fetch());
        self::assertSame('private-files/', $this->get(FileStorageConfiguration::class)->get(3)['configuration']['basePath']);
    }

    #[Test]
    public function firstStorageBecomesDefaultWhenNoneIsMarked(): void
    {
        $this->insertStorage(2, 'Two', 'Local', ['basePath' => 'two/', 'pathType' => 'relative']);
        $this->insertStorage(3, 'Three', 'Local', ['basePath' => 'three/', 'pathType' => 'relative']);

        $subject = $this->getSubject();
        self::assertTrue($subject->executeUpdate());

        self::assertTrue($this->get(FileStorageConfiguration::class)->get(2)['default']);
        self::assertFalse($this->get(FileStorageConfiguration::class)->get(3)['default']);
        self::assertStringContainsString('storage 2 is now the default storage', $this->output->fetch());
    }

    #[Test]
    public function deletedRecordsAreNotMigrated(): void
    {
        $this->insertStorage(1, 'Old default', 'Local', ['basePath' => 'old/', 'pathType' => 'relative'], ['is_default' => 1, 'deleted' => 1]);
        $this->insertStorage(2, 'Main', 'Local', ['basePath' => 'main/', 'pathType' => 'relative'], ['is_default' => 1]);
        $this->insertStorage(3, 'Gone', 'Local', ['basePath' => 'gone/', 'pathType' => 'relative'], ['deleted' => 1]);
        $this->insertStorage(4, 'Media', 'Local', ['basePath' => 'media/', 'pathType' => 'relative']);

        $subject = $this->getSubject();
        self::assertTrue($subject->executeUpdate());

        $storages = $this->get(FileStorageConfiguration::class)->getAll();
        self::assertSame([2, 4], array_keys($storages));
        self::assertTrue($storages[2]['default']);
    }

    #[Test]
    public function singleStorageWithAnotherIdentifierThanOneIsMigrated(): void
    {
        $this->insertStorage(2, 'fileadmin', 'Local', ['basePath' => 'fileadmin/', 'pathType' => 'relative'], ['is_default' => 1]);

        $subject = $this->getSubject();
        self::assertTrue($subject->updateNecessary());
        self::assertTrue($subject->executeUpdate());

        self::assertSame([2], array_keys($this->get(FileStorageConfiguration::class)->getAll()));
    }

    #[Test]
    public function firstOfSeveralDefaultStoragesStaysDefault(): void
    {
        $this->insertStorage(2, 'Two', 'Local', ['basePath' => 'two/', 'pathType' => 'relative'], ['is_default' => 1]);
        $this->insertStorage(3, 'Three', 'Local', ['basePath' => 'three/', 'pathType' => 'relative'], ['is_default' => 1]);

        $subject = $this->getSubject();
        self::assertTrue($subject->executeUpdate());

        self::assertTrue($this->get(FileStorageConfiguration::class)->get(2)['default']);
        self::assertFalse($this->get(FileStorageConfiguration::class)->get(3)['default']);
        self::assertStringContainsString('Storage 2 stays the default storage', $this->output->fetch());
    }

    #[Test]
    public function recordsOfTheRenamedTableAreMigrated(): void
    {
        $this->insertStorage(1, 'fileadmin', 'Local', ['basePath' => 'fileadmin/', 'pathType' => 'relative'], ['is_default' => 1]);
        $this->insertStorage(2, 'Media', 'Local', ['basePath' => 'media/', 'pathType' => 'relative']);
        $schemaManager = $this->getConnectionPool()->getConnectionForTable('sys_file_storage')->createSchemaManager();
        $schemaManager->renameTable('sys_file_storage', 'zzz_deleted_sys_file_storage');
        try {
            $subject = $this->getSubject();
            self::assertTrue($subject->updateNecessary());
            self::assertTrue($subject->executeUpdate());
            self::assertSame([1, 2], array_keys($this->get(FileStorageConfiguration::class)->getAll()));
        } finally {
            $schemaManager->renameTable('zzz_deleted_sys_file_storage', 'sys_file_storage');
        }
    }

    #[Test]
    public function storagesMissingInAnExistingFileAreReportedAndTheFileIsKept(): void
    {
        $this->insertStorage(2, 'Media', 'Local', ['basePath' => 'media/', 'pathType' => 'relative'], ['is_default' => 1]);
        $this->insertStorage(3, 'Production only', 'Local', ['basePath' => 'production/', 'pathType' => 'relative']);
        $content = "storages:\n  2:\n    name: media\n";
        file_put_contents($this->configFile, $content);

        $subject = $this->getSubject();
        self::assertTrue($subject->updateNecessary());
        self::assertFalse($subject->executeUpdate());

        self::assertSame($content, file_get_contents($this->configFile));
        self::assertStringContainsString('Storage 3 "Production only" exists in the database but not in', $this->output->fetch());
    }

    #[Test]
    public function credentialsOfOtherDriversAreReported(): void
    {
        $this->insertStorage(1, 'fileadmin', 'Local', ['basePath' => 'fileadmin/', 'pathType' => 'relative'], ['is_default' => 1]);
        $this->insertStorage(2, 'Bucket', 'AmazonS3', ['bucket' => 'assets', 'secret' => 'plain']);

        self::assertTrue($this->getSubject()->executeUpdate());

        self::assertStringContainsString('Replace them with %env()% placeholders', $this->output->fetch());
    }

    #[Test]
    public function invalidFileIsReportedInsteadOfThrowing(): void
    {
        $this->insertStorage(2, 'Media', 'Local', ['basePath' => 'media/', 'pathType' => 'relative']);
        file_put_contents($this->configFile, "storages: [this is: not valid\n");

        $subject = $this->getSubject();
        self::assertTrue($subject->updateNecessary());
        self::assertFalse($subject->executeUpdate());
        self::assertStringContainsString('cannot be read', $this->output->fetch());
    }

    #[Test]
    public function implicitDefaultWithDifferingCaseSensitivityIsMigrated(): void
    {
        $basePath = Environment::getPublicPath() . '/fileadmin/';
        $driver = new LocalDriver(['basePath' => $basePath]);
        $driver->processConfiguration();
        $isCaseSensitive = $driver->isCaseSensitiveFileSystem();
        $this->insertStorage(1, 'fileadmin', 'Local', ['basePath' => 'fileadmin/', 'pathType' => 'relative', 'caseSensitive' => $isCaseSensitive ? '0' : '1'], ['is_default' => 1]);

        $subject = $this->getSubject();
        self::assertTrue($subject->updateNecessary());
        self::assertTrue($subject->executeUpdate());

        self::assertSame(!$isCaseSensitive, $this->get(FileStorageConfiguration::class)->get(1)['configuration']['caseSensitive']);
    }
}
