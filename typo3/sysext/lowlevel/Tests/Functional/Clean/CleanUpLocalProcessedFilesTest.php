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

use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lowlevel\Command\CleanUpLocalProcessedFilesCommand;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3\TestingFramework\Core\Testbase;

class CleanUpLocalProcessedFilesTest extends FunctionalTestCase
{
    protected ?CleanUpLocalProcessedFilesCommand $subject = null;

    protected ?CommandTester $commandTester = null;

    protected array $coreExtensionsToLoad = ['lowlevel'];

    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/lowlevel/Tests/Functional/Fixtures/DataSet/image.png' => 'fileadmin/image.png',
        'typo3/sysext/lowlevel/Tests/Functional/Fixtures/DataSet/NotReferencedImage.png' => 'fileadmin/_processed_/0/a/NotReferencedImage.png',
        'typo3/sysext/lowlevel/Tests/Functional/Fixtures/DataSet/FileWithoutProcessedFileRecord.png' => 'fileadmin/_processed_/1/b/FileWithoutProcessedFileRecord.png',
        'typo3/sysext/lowlevel/Tests/Functional/Fixtures/DataSet/NotReferencedImage2.png' => 'local-storage/_processed_/0/a/NotReferencedImage2.png',
        'typo3/sysext/lowlevel/Tests/Functional/Fixtures/DataSet/FileWithoutProcessedFileRecord2.png' => 'local-storage/_processed_/1/b/FileWithoutProcessedFileRecord2.png',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);

        $this->importCSVDataSet(ORIGINAL_ROOT . 'typo3/sysext/lowlevel/Tests/Functional/Fixtures/DataSet/sys_file_processedfile.csv');
        $this->subject = GeneralUtility::makeInstance(CleanUpLocalProcessedFilesCommand::class);

        $helperSet = new HelperSet();
        $helperSet->set(new QuestionHelper(), 'question');

        $this->subject->setHelperSet($helperSet);
        $this->commandTester = new CommandTester($this->subject);
        $this->setUpBackendUser(1);

        // create filadmin (1) and a additional absolute local storage (2)
        $subject = GeneralUtility::makeInstance(StorageRepository::class);
        $subject->createLocalStorage(
            'fileadmin',
            'fileadmin/',
            'relative'
        );
        $subject->createLocalStorage(
            'another-storage',
            $this->instancePath . '/local-storage/',
            'absolute'
        );

        // check for existing files to ensure setup is complete for this test
        foreach ($this->pathsToProvideInTestInstance as $instanceFilePath) {
            self::assertFileExists(GeneralUtility::getFileAbsFileName($instanceFilePath), $instanceFilePath . ' must exists in testcase instance.');
        }
    }

    protected function tearDown(): void
    {
        // Some tests in this testcase deletes provided files. To avoid false-positive with changed orders we need to
        // ensure that they are re-provided. We are doing this on a test case basis, to avoid unneded disk io if not
        // really needed.
        $testbase = new Testbase();
        $testbase->providePathsInTestInstance($this->instancePath, $this->pathsToProvideInTestInstance);

        parent::tearDown();
    }

    /**
     * @test
     */
    public function databaseRecordForMissingFileIsDeleted(): void
    {
        $this->commandTester->execute(['--force' => true]);

        $this->assertCSVDataSet('typo3/sysext/lowlevel/Tests/Functional/Fixtures/Modify/oneDeleted.csv');
    }

    /**
     * @test
     */
    public function fileForMissingReferenceIsDeleted(): void
    {
        $this->commandTester->execute(['--force' => true]);

        self::assertFileDoesNotExist(GeneralUtility::getFileAbsFileName('fileadmin/_processed_/0/a/NotReferencedImage.png'));
        self::assertFileDoesNotExist(GeneralUtility::getFileAbsFileName('fileadmin/_processed_/1/b/FileWithoutProcessedFileRecord.png'));
        self::assertFileDoesNotExist(GeneralUtility::getFileAbsFileName('local-storage/_processed_/0/a/NotReferencedImage2.png'));
        self::assertFileDoesNotExist(GeneralUtility::getFileAbsFileName('local-storage/_processed_/1/b/FileWithoutProcessedFileRecord2.png'));
        self::assertFileExists(GeneralUtility::getFileAbsFileName('fileadmin/image.png'));
    }

    /**
     * @test
     */
    public function dryRunReallyDoesNothing(): void
    {
        $this->commandTester->execute(
            [
                '--dry-run' => true,
            ]
        );
        $this->assertCSVDataSet('typo3/sysext/lowlevel/Tests/Functional/Fixtures/DataSet/sys_file_processedfile.csv');

        // `dry-run` should not remove files, therefore we need to test if  `_processed_`file still exists.
        self::assertFileExists(GeneralUtility::getFileAbsFileName('fileadmin/_processed_/0/a/NotReferencedImage.png'));
        self::assertFileExists(GeneralUtility::getFileAbsFileName('fileadmin/_processed_/1/b/FileWithoutProcessedFileRecord.png'));
        self::assertFileExists(GeneralUtility::getFileAbsFileName('local-storage/_processed_/0/a/NotReferencedImage2.png'));
        self::assertFileExists(GeneralUtility::getFileAbsFileName('local-storage/_processed_/1/b/FileWithoutProcessedFileRecord2.png'));
        self::assertFileExists(GeneralUtility::getFileAbsFileName('fileadmin/image.png'));
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function confirmDeleteNo(): void
    {
        $this->commandTester->setInputs(['no']);
        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('Are you sure you want to delete these processed files and records', $output);
        self::assertStringNotContainsString('Deleted', $output);
    }
}
