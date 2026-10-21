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

namespace TYPO3\CMS\Lowlevel\Tests\Functional\Clean;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Configuration\FileStorageConfigurationWriter;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lowlevel\Command\CleanUpLocalProcessedFilesCommand;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3\TestingFramework\Core\Testbase;

final class CleanUpLocalProcessedFilesTest extends FunctionalTestCase
{
    private ?CommandTester $commandTester = null;

    protected array $coreExtensionsToLoad = ['lowlevel'];

    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/lowlevel/Tests/Functional/Fixtures/DataSet/image.png' => 'fileadmin/image.png',
        'typo3/sysext/lowlevel/Tests/Functional/Fixtures/DataSet/NotReferencedImage.png' => 'fileadmin/_processed_/0/a/NotReferencedImage.png',
        'typo3/sysext/lowlevel/Tests/Functional/Fixtures/DataSet/FileWithoutProcessedFileRecord.png' => 'fileadmin/_processed_/1/b/FileWithoutProcessedFileRecord.png',
        'typo3/sysext/lowlevel/Tests/Functional/Fixtures/DataSet/NotReferencedImage2.png' => 'local-storage/_processed_/0/a/NotReferencedImage2.png',
        'typo3/sysext/lowlevel/Tests/Functional/Fixtures/DataSet/FileWithoutProcessedFileRecord2.png' => 'local-storage/_processed_/1/b/FileWithoutProcessedFileRecord2.png',
        'typo3/sysext/lowlevel/Tests/Functional/Fixtures/DataSet/ReferencedImage.png' => 'local-storage/_processed_/1/a/ReferencedImage.png',
        'typo3/sysext/lowlevel/Tests/Functional/Fixtures/DataSet/ReferencedImage2.png' => 'local-storage/_processed_/1/a/ReferencedImage2.png',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);

        // fileadmin (1) is the implicit default storage, add an additional absolute local storage (2)
        $this->get(StorageRepository::class)->addLocalStorage(
            'another-storage',
            Environment::getPublicPath() . '/local-storage/'
        );

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DataSet/sys_file_processedfile.csv');
        $cleanUpLocalProcessedFilesCommand = $this->get(CleanUpLocalProcessedFilesCommand::class);

        $helperSet = new HelperSet();
        $helperSet->set(new QuestionHelper(), 'question');

        $cleanUpLocalProcessedFilesCommand->setHelperSet($helperSet);
        $this->commandTester = new CommandTester($cleanUpLocalProcessedFilesCommand);
        $this->setUpBackendUser(1);

        // check for existing files to ensure setup is complete for this test
        foreach ($this->pathsToProvideInTestInstance as $instanceFilePath) {
            self::assertFileExists(GeneralUtility::getFileAbsFileName($instanceFilePath), $instanceFilePath . ' must exists in testcase instance.');
        }
    }

    protected function tearDown(): void
    {
        // Some tests in this testcase deletes provided files. To avoid false-positive with changed orders we need to
        // ensure that they are re-provided. We are doing this on a test case basis, to avoid unneeded disk io if not
        // really needed.
        $testbase = new Testbase();
        $testbase->providePathsInTestInstance(Environment::getPublicPath(), $this->pathsToProvideInTestInstance);
        @unlink(Environment::getConfigPath() . '/system/file-storages.yaml');

        parent::tearDown();
    }

    #[Test]
    public function databaseRecordForMissingFileIsDeleted(): void
    {
        $this->commandTester->execute(['--force' => true]);

        $this->assertCSVDataSet(__DIR__ . '/../Fixtures/Modify/oneDeleted.csv');
    }

    #[Test]
    public function fileForMissingReferenceIsDeleted(): void
    {
        $this->commandTester->execute(['--force' => true]);

        self::assertFileDoesNotExist(GeneralUtility::getFileAbsFileName('fileadmin/_processed_/0/a/NotReferencedImage.png'));
        self::assertFileDoesNotExist(GeneralUtility::getFileAbsFileName('fileadmin/_processed_/1/b/FileWithoutProcessedFileRecord.png'));
        self::assertFileDoesNotExist(GeneralUtility::getFileAbsFileName('local-storage/_processed_/0/a/NotReferencedImage2.png'));
        self::assertFileDoesNotExist(GeneralUtility::getFileAbsFileName('local-storage/_processed_/1/b/FileWithoutProcessedFileRecord2.png'));
        self::assertFileExists(GeneralUtility::getFileAbsFileName('fileadmin/image.png'));
    }

    #[Test]
    public function dryRunReallyDoesNothing(): void
    {
        $this->commandTester->execute(
            [
                '--dry-run' => true,
            ]
        );
        $this->assertCSVDataSet(__DIR__ . '/../Fixtures/DataSet/sys_file_processedfile.csv');

        // `dry-run` should not remove files, therefore we need to test if  `_processed_`file still exists.
        self::assertFileExists(GeneralUtility::getFileAbsFileName('fileadmin/_processed_/0/a/NotReferencedImage.png'));
        self::assertFileExists(GeneralUtility::getFileAbsFileName('fileadmin/_processed_/1/b/FileWithoutProcessedFileRecord.png'));
        self::assertFileExists(GeneralUtility::getFileAbsFileName('local-storage/_processed_/0/a/NotReferencedImage2.png'));
        self::assertFileExists(GeneralUtility::getFileAbsFileName('local-storage/_processed_/1/b/FileWithoutProcessedFileRecord2.png'));
        self::assertFileExists(GeneralUtility::getFileAbsFileName('fileadmin/image.png'));
        self::assertFileExists(GeneralUtility::getFileAbsFileName('local-storage/_processed_/1/a/ReferencedImage.png'));
        self::assertFileExists(GeneralUtility::getFileAbsFileName('local-storage/_processed_/1/a/ReferencedImage2.png'));
    }

    #[Test]
    public function confirmDeleteYes(): void
    {
        $this->commandTester->setInputs(['yes']);
        // Set -v option, because the command does not need provide this option due to the use of isVerbose().
        $this->commandTester->execute([], [
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE,
        ]);

        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('[RECORD] Would delete /_processed_/a/SomeMissingFile.png', $output);
        self::assertStringContainsString('Are you sure you want to delete these processed files and records', $output);
        self::assertStringContainsString('Deleted 1 processed records', $output);
        self::assertStringContainsString('Deleted 4 processed files', $output);
    }

    #[Test]
    public function confirmDeleteYesForAll(): void
    {
        $this->commandTester->setInputs(['yes']);
        // Set -v option, because the command does not need provide this option due to the use of isVerbose().
        $this->commandTester->execute(['--all' => true], [
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE,
        ]);

        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('[RECORD] Would delete /_processed_/a/SomeMissingFile.png', $output);
        self::assertStringContainsString('Are you sure you want to delete these processed files and records', $output);
        self::assertStringContainsString('Deleted 5 processed records', $output);
        self::assertStringNotContainsString('Failed to delete', $output);
        self::assertStringContainsString('Deleted 6 processed files', $output);
    }

    #[Test]
    public function confirmDeleteNo(): void
    {
        $this->commandTester->setInputs(['no']);
        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('Are you sure you want to delete these processed files and records', $output);
        self::assertStringNotContainsString('Deleted', $output);
    }

    #[Test]
    public function confirmDeleteNoWithAllOption(): void
    {
        $this->commandTester->setInputs(['no']);
        $this->commandTester->execute(['--all' => true]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('Are you sure you want to delete these processed files and records', $output);
        self::assertStringNotContainsString('Deleted', $output);
    }

    #[Test]
    public function allFilesAreDeleted(): void
    {
        $this->commandTester->execute(['--force' => true, '--all' => true]);

        self::assertFileDoesNotExist(GeneralUtility::getFileAbsFileName('fileadmin/_processed_/0/a/NotReferencedImage.png'));
        self::assertFileDoesNotExist(GeneralUtility::getFileAbsFileName('fileadmin/_processed_/1/b/FileWithoutProcessedFileRecord.png'));
        self::assertFileDoesNotExist(GeneralUtility::getFileAbsFileName('local-storage/_processed_/0/a/NotReferencedImage2.png'));
        self::assertFileDoesNotExist(GeneralUtility::getFileAbsFileName('local-storage/_processed_/1/b/FileWithoutProcessedFileRecord2.png'));
        self::assertFileDoesNotExist(GeneralUtility::getFileAbsFileName('local-storage/_processed_/1/a/ReferencedImage.png'));
        self::assertFileDoesNotExist(GeneralUtility::getFileAbsFileName('local-storage/_processed_/1/a/ReferencedImage2.png'));
        self::assertFileExists(GeneralUtility::getFileAbsFileName('fileadmin/image.png'));
    }

    #[Test]
    public function allDatabaseRecordsAreDeleted(): void
    {
        $this->commandTester->execute(['--force' => true, '--all' => true]);

        $this->assertCSVDataSet(__DIR__ . '/../Fixtures/Modify/allDeleted.csv');
    }

    #[Test]
    public function databaseRecordsOfNonLocalStoragesAreKeptWithAllOption(): void
    {
        $this->get(FileStorageConfigurationWriter::class)->write(3, ['name' => 'remote-storage', 'driver' => 'Remote']);
        $this->getConnectionPool()->getConnectionForTable('sys_file_processedfile')->insert(
            'sys_file_processedfile',
            ['uid' => 6, 'storage' => 3, 'original' => 83, 'identifier' => '/_processed_/remote.png', 'name' => 'remote.png']
        );

        $this->commandTester->execute(['--force' => true, '--all' => true]);

        $remainingUids = $this->getConnectionPool()->getQueryBuilderForTable('sys_file_processedfile')
            ->select('uid')
            ->from('sys_file_processedfile')
            ->executeQuery()
            ->fetchFirstColumn();
        self::assertSame([6], array_map(intval(...), $remainingUids));
    }

    #[Test]
    public function localStorageWithMissingBasePathIsSkipped(): void
    {
        $missingBasePath = Environment::getPublicPath() . '/missing-storage/';
        mkdir($missingBasePath);
        $storageUid = $this->get(StorageRepository::class)->addLocalStorage('missing-storage', $missingBasePath);
        rmdir($missingBasePath);
        $this->getConnectionPool()->getConnectionForTable('sys_file_processedfile')->insert(
            'sys_file_processedfile',
            ['uid' => 6, 'storage' => $storageUid, 'original' => 83, 'identifier' => '/_processed_/missing.png', 'name' => 'missing.png']
        );

        $this->commandTester->execute(['--force' => true]);

        $output = $this->commandTester->getDisplay();
        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Skipped offline storage "missing-storage" (UID ' . $storageUid . ')', $output);
        // The base path is shown as configured, relative to the project path
        self::assertStringContainsString('with base path "' . substr($missingBasePath, strlen(Environment::getProjectPath() . '/')) . '"', $output);
        self::assertStringContainsString('Deleted 4 processed files', $output);
        $remainingUids = $this->getConnectionPool()->getQueryBuilderForTable('sys_file_processedfile')
            ->select('uid')
            ->from('sys_file_processedfile')
            ->executeQuery()
            ->fetchFirstColumn();
        self::assertContains(6, array_map(intval(...), $remainingUids));
    }
}
