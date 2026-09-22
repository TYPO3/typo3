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

namespace TYPO3\CMS\Core\Tests\Functional\Resource\Configuration;

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Configuration\FileStorageConfiguration;
use TYPO3\CMS\Core\Resource\Configuration\FileStorageConfigurationWriter;
use TYPO3\CMS\Core\Resource\Exception\InvalidFileStorageConfigurationException;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FileStorageConfigurationWriterTest extends FunctionalTestCase
{
    private string $configFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configFile = Environment::getConfigPath() . '/system/' . FileStorageConfiguration::CONFIG_FILE_NAME;
        @mkdir(Environment::getPublicPath() . '/assets');
    }

    protected function tearDown(): void
    {
        @unlink($this->configFile);
        parent::tearDown();
    }

    #[Test]
    public function writeCreatesFileWithCompactEntry(): void
    {
        $this->get(FileStorageConfigurationWriter::class)->write(2, [
            'name' => 'Assets',
            'driver' => 'Local',
            'default' => false,
            'browsable' => true,
            'public' => false,
            'writable' => true,
            'online' => true,
            'autoExtractMetadata' => true,
            'processingFolder' => '',
            'configuration' => ['basePath' => 'assets/'],
        ]);
        self::assertSame(
            "storages:\n  1:\n    name: fileadmin\n    default: true\n    configuration:\n      basePath: fileadmin/\n  2:\n    name: Assets\n    public: false\n    configuration:\n      basePath: assets/\n",
            file_get_contents($this->configFile)
        );
    }

    #[Test]
    public function writePreservesPlaceholdersAndImportsOfUntouchedEntries(): void
    {
        file_put_contents($this->configFile, "imports:\n  - { resource: 'more-storages.yaml' }\nstorages:\n  1:\n    name: fileadmin\n    default: true\n    configuration:\n      basePath: '%env(TYPO3_TEST_STORAGE_PATH)%'\n");
        file_put_contents(dirname($this->configFile) . '/more-storages.yaml', "storages: []\n");
        $this->get(FileStorageConfigurationWriter::class)->write(2, ['name' => 'assets', 'configuration' => ['basePath' => 'assets/']]);
        self::assertSame(
            "storages:\n  1:\n    name: fileadmin\n    default: true\n    configuration:\n      basePath: '%env(TYPO3_TEST_STORAGE_PATH)%'\n  2:\n    name: assets\n    configuration:\n      basePath: assets/\nimports:\n  -\n    resource: more-storages.yaml\n",
            file_get_contents($this->configFile)
        );
        unlink(dirname($this->configFile) . '/more-storages.yaml');
    }

    #[Test]
    public function writeRejectsSecondDefault(): void
    {
        file_put_contents($this->configFile, "storages:\n  1:\n    name: fileadmin\n    default: true\n");
        $this->expectException(InvalidFileStorageConfigurationException::class);
        $this->expectExceptionCode(1789862408);
        $this->get(FileStorageConfigurationWriter::class)->write(2, ['name' => 'assets', 'default' => true]);
    }

    #[Test]
    public function writeReplacesExistingEntryAndSortsByIdentifier(): void
    {
        file_put_contents($this->configFile, "storages:\n  3:\n    name: three\n  1:\n    name: one\n    default: true\n");
        $this->get(FileStorageConfigurationWriter::class)->write(3, ['name' => 'Three']);
        self::assertSame(
            "storages:\n  1:\n    name: one\n    default: true\n  3:\n    name: Three\n",
            file_get_contents($this->configFile)
        );
    }

    #[Test]
    public function removeDeletesEntry(): void
    {
        file_put_contents($this->configFile, "storages:\n  1:\n    name: one\n    default: true\n  2:\n    name: two\n");
        $this->get(FileStorageConfigurationWriter::class)->remove(2);
        self::assertSame("storages:\n  1:\n    name: one\n    default: true\n", file_get_contents($this->configFile));
    }

    #[Test]
    public function removeOfUnknownIdentifierDoesNotCreateFile(): void
    {
        $this->get(FileStorageConfigurationWriter::class)->remove(2);
        self::assertFileDoesNotExist($this->configFile);
    }

    #[Test]
    public function writeAllRefusesToOverwriteExistingStorages(): void
    {
        file_put_contents($this->configFile, "storages:\n  1:\n    name: one\n");
        $this->expectException(InvalidFileStorageConfigurationException::class);
        $this->expectExceptionCode(1789862414);
        $this->get(FileStorageConfigurationWriter::class)->writeAll([2 => ['name' => 'two']]);
    }

    #[Test]
    public function writeAllWritesValidatedSet(): void
    {
        $this->get(FileStorageConfigurationWriter::class)->writeAll([
            2 => ['name' => 'two', 'configuration' => ['basePath' => 'two/']],
            1 => ['name' => 'one', 'default' => true, 'configuration' => ['basePath' => 'one/']],
        ]);
        self::assertSame(
            "storages:\n  1:\n    name: one\n    default: true\n    configuration:\n      basePath: one/\n  2:\n    name: two\n    configuration:\n      basePath: two/\n",
            file_get_contents($this->configFile)
        );
    }

    #[Test]
    public function writeIsVisibleToTheStorageRepository(): void
    {
        $storageRepository = $this->get(StorageRepository::class);
        self::assertCount(1, $storageRepository->findAll());
        self::assertNull($this->get(FileStorageConfiguration::class)->get(2));

        $this->get(FileStorageConfigurationWriter::class)->write(2, ['name' => 'assets', 'configuration' => ['basePath' => 'assets/']]);

        self::assertCount(2, $storageRepository->findAll());
        self::assertSame('assets', $storageRepository->findByUid(2)->getName());
        self::assertSame(1, $storageRepository->getDefaultStorageUid());
    }

    #[Test]
    public function addLocalStorageAppendsProjectRelativeEntry(): void
    {
        $storageRepository = $this->get(StorageRepository::class);
        $identifier = $storageRepository->addLocalStorage('Assets', Environment::getProjectPath() . '/assets/');
        self::assertSame(2, $identifier);
        self::assertSame(
            "storages:\n  1:\n    name: fileadmin\n    default: true\n    configuration:\n      basePath: fileadmin/\n  2:\n    name: Assets\n    configuration:\n      basePath: assets/\n",
            file_get_contents($this->configFile)
        );
        self::assertSame('Assets', $storageRepository->findByUid(2)->getName());
        self::assertTrue($storageRepository->findByUid(2)->isOnline());
    }

    #[Test]
    public function addLocalStorageAsDefaultUnsetsPreviousDefault(): void
    {
        $storageRepository = $this->get(StorageRepository::class);
        $storageRepository->addLocalStorage('Assets', 'assets/', true);
        self::assertSame(2, $storageRepository->getDefaultStorageUid());
        self::assertSame(
            "storages:\n  1:\n    name: fileadmin\n    configuration:\n      basePath: fileadmin/\n  2:\n    name: Assets\n    default: true\n    configuration:\n      basePath: assets/\n",
            file_get_contents($this->configFile)
        );
    }

    #[Test]
    public function writeKeepsStoragesOfImportedFilesAndDoesNotAddTheImplicitDefault(): void
    {
        $importFile = dirname($this->configFile) . '/imported-storages.yaml';
        file_put_contents($importFile, "storages:\n  2:\n    name: imported\n    default: true\n    configuration:\n      basePath: imported/\n");
        file_put_contents($this->configFile, "imports:\n  - { resource: 'imported-storages.yaml' }\n");
        try {
            $this->get(FileStorageConfigurationWriter::class)->write(5, ['name' => 'assets', 'configuration' => ['basePath' => 'assets/']]);
            self::assertSame(
                "storages:\n  5:\n    name: assets\n    configuration:\n      basePath: assets/\nimports:\n  -\n    resource: imported-storages.yaml\n",
                file_get_contents($this->configFile)
            );
            $storages = $this->get(FileStorageConfiguration::class)->getAll();
            self::assertSame([2, 5], array_keys($storages));
            self::assertTrue($storages[2]['default']);
        } finally {
            unlink($importFile);
        }
    }

    #[Test]
    public function writeRefusesToChangeAStorageOfAnImportedFile(): void
    {
        $importFile = dirname($this->configFile) . '/imported-storages.yaml';
        file_put_contents($importFile, "storages:\n  2:\n    name: imported\n");
        $content = "imports:\n  - { resource: 'imported-storages.yaml' }\n";
        file_put_contents($this->configFile, $content);
        try {
            $this->get(FileStorageConfigurationWriter::class)->write(2, ['name' => 'changed']);
            self::fail('Expected exception was not thrown');
        } catch (InvalidFileStorageConfigurationException $e) {
            self::assertSame(1791443851, $e->getCode());
            self::assertSame($content, file_get_contents($this->configFile));
        } finally {
            unlink($importFile);
        }
    }

    #[Test]
    public function removeRefusesToRemoveAStorageOfAnImportedFile(): void
    {
        $importFile = dirname($this->configFile) . '/imported-storages.yaml';
        file_put_contents($importFile, "storages:\n  2:\n    name: imported\n");
        file_put_contents($this->configFile, "imports:\n  - { resource: 'imported-storages.yaml' }\nstorages:\n  1:\n    name: one\n    default: true\n");
        try {
            $this->expectException(InvalidFileStorageConfigurationException::class);
            $this->expectExceptionCode(1791443851);
            $this->get(FileStorageConfigurationWriter::class)->remove(2);
        } finally {
            unlink($importFile);
        }
    }

    #[Test]
    public function writeRestoresThePreviousFileWhenAnImportOverridesTheWrittenEntry(): void
    {
        $importFile = dirname($this->configFile) . '/imported-storages.yaml';
        file_put_contents($importFile, "storages:\n  3:\n    driver: Remote\n");
        $content = "imports:\n  - { resource: 'imported-storages.yaml' }\nstorages:\n  1:\n    name: one\n    default: true\n  3:\n    name: three\n";
        file_put_contents($this->configFile, $content);
        try {
            $this->get(FileStorageConfigurationWriter::class)->write(3, ['name' => 'Three', 'driver' => 'Local', 'configuration' => ['basePath' => 'three/']]);
            self::fail('Expected exception was not thrown');
        } catch (InvalidFileStorageConfigurationException $e) {
            self::assertSame(1791443853, $e->getCode());
            self::assertSame($content, file_get_contents($this->configFile));
            self::assertSame('Remote', $this->get(FileStorageConfiguration::class)->get(3)['driver']);
        } finally {
            unlink($importFile);
        }
    }

    #[Test]
    public function writeLeavesNoTemporaryFileBehind(): void
    {
        $this->get(FileStorageConfigurationWriter::class)->write(2, ['name' => 'assets', 'configuration' => ['basePath' => 'assets/']]);
        self::assertSame([], glob($this->configFile . '.*.tmp'));
    }

    #[Test]
    public function applyMovesTheDefaultFlagInOneWrite(): void
    {
        file_put_contents($this->configFile, "storages:\n  1:\n    name: one\n    default: true\n  2:\n    name: two\n");
        $this->get(FileStorageConfigurationWriter::class)->apply([
            1 => ['name' => 'one'],
            2 => ['name' => 'two', 'default' => true],
        ]);
        self::assertSame("storages:\n  1:\n    name: one\n  2:\n    name: two\n    default: true\n", file_get_contents($this->configFile));
    }

    #[Test]
    public function addLocalStorageAsDefaultWorksWithSeveralExistingStorages(): void
    {
        file_put_contents($this->configFile, "storages:\n  1:\n    name: one\n    default: true\n    configuration:\n      basePath: fileadmin/\n  2:\n    name: two\n    configuration:\n      basePath: assets/\n");
        $storageRepository = $this->get(StorageRepository::class);
        $identifier = $storageRepository->addLocalStorage('Three', 'assets/three/', true);
        self::assertSame(3, $identifier);
        self::assertSame(3, $storageRepository->getDefaultStorageUid());
        self::assertFalse($this->get(FileStorageConfiguration::class)->get(1)['default']);
    }

    #[Test]
    public function addLocalStorageDoesNotReuseIdentifiersReferencedByFiles(): void
    {
        $this->getConnectionPool()->getConnectionForTable('sys_file')->insert('sys_file', ['storage' => 7, 'identifier' => '/orphan.txt', 'name' => 'orphan.txt']);
        $identifier = $this->get(StorageRepository::class)->addLocalStorage('Assets', 'assets/');
        self::assertSame(8, $identifier);
    }

    #[Test]
    public function addLocalStorageDoesNotReuseIdentifiersReferencedByProcessedFiles(): void
    {
        $this->getConnectionPool()->getConnectionForTable('sys_file_processedfile')->insert('sys_file_processedfile', ['storage' => 4, 'identifier' => '/_processed_/orphan.png']);
        $identifier = $this->get(StorageRepository::class)->addLocalStorage('Assets', 'assets/');
        self::assertSame(5, $identifier);
    }

    #[Test]
    public function addLocalStorageDoesNotReuseIdentifiersReferencedByFileMounts(): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('sys_filemounts');
        $connection->insert('sys_filemounts', ['title' => 'Removed storage', 'identifier' => '9:/folder/']);
        $connection->insert('sys_filemounts', ['title' => 'Broken identifier', 'identifier' => 'no-storage']);
        $identifier = $this->get(StorageRepository::class)->addLocalStorage('Assets', 'assets/');
        self::assertSame(10, $identifier);
    }

    #[Test]
    public function addLocalStorageDoesNotReuseIdentifiersReferencedByFileCollections(): void
    {
        $this->getConnectionPool()->getConnectionForTable('sys_file_collection')->insert('sys_file_collection', ['title' => 'Folder of removed storage', 'type' => 'folder', 'folder_identifier' => '6:/folder/']);
        $identifier = $this->get(StorageRepository::class)->addLocalStorage('Assets', 'assets/');
        self::assertSame(7, $identifier);
    }

    #[Test]
    #[IgnoreDeprecations]
    public function deprecatedCreateLocalStorageResolvesRelativePathsAgainstThePublicPath(): void
    {
        $storageRepository = $this->get(StorageRepository::class);
        $identifier = $storageRepository->createLocalStorage('Assets', 'assets/', 'relative', 'ignored description', false);
        $expected = Environment::getRelativePublicPath() . 'assets/';
        self::assertSame($expected, $this->get(FileStorageConfiguration::class)->get($identifier)['configuration']['basePath']);
        self::assertSame(1, $storageRepository->getDefaultStorageUid());
    }
}
