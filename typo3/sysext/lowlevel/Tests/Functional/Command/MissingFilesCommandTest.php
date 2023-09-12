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

namespace TYPO3\CMS\Lowlevel\Tests\Functional\Command;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Lowlevel\Command\MissingFilesCommand;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class MissingFilesCommandTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['lowlevel'];

    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/lowlevel/Tests/Functional/Fixtures/DataSet/image.png' => 'fileadmin/existing.png',
        'typo3/sysext/lowlevel/Tests/Functional/Fixtures/DataSet/ReferencedImage.png' => 'fileadmin/_processed_/2/missing-referenced_abc.png',
        'typo3/sysext/lowlevel/Tests/Functional/Fixtures/DataSet/NotReferencedImage.png' => 'fileadmin/_processed_/4/missing-unreferenced_def.png',
    ];

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $this->importCSVDataSet(__DIR__ . '/Fixtures/MissingFilesCommandImport.csv');
        $this->commandTester = new CommandTester($this->get(MissingFilesCommand::class));
    }

    #[Test]
    public function unreferencedMissingFilesAreRemoved(): void
    {
        $exitCode = $this->commandTester->execute(['--update-refindex' => true], ['interactive' => false]);

        self::assertSame(Command::SUCCESS, $exitCode);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/MissingFilesCommandResult.csv');
        $referenceIndexConnection = $this->get(ConnectionPool::class)->getConnectionForTable('sys_refindex');
        self::assertSame(0, $referenceIndexConnection->count('*', 'sys_refindex', ['ref_table' => 'sys_file', 'ref_uid' => 4]));
        self::assertSame(0, $referenceIndexConnection->count('*', 'sys_refindex', ['tablename' => 'sys_file_metadata', 'recuid' => 4]));
        self::assertSame(1, $referenceIndexConnection->count('*', 'sys_refindex', ['tablename' => 'sys_file_metadata', 'recuid' => 2]));
        self::assertFileDoesNotExist(Environment::getPublicPath() . '/fileadmin/_processed_/4/missing-unreferenced_def.png');
        self::assertFileExists(Environment::getPublicPath() . '/fileadmin/_processed_/2/missing-referenced_abc.png');
    }

    #[Test]
    public function dryRunKeepsAllRecords(): void
    {
        $exitCode = $this->commandTester->execute(['--update-refindex' => true, '--dry-run' => true], ['interactive' => false]);

        self::assertSame(Command::SUCCESS, $exitCode);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/MissingFilesCommandDryRunResult.csv');
    }

    #[Test]
    public function referencedMissingFilesAreListedAsKept(): void
    {
        $this->commandTester->execute(
            ['--update-refindex' => true, '--dry-run' => true],
            ['interactive' => false, 'verbosity' => OutputInterface::VERBOSITY_VERBOSE]
        );

        $display = $this->commandTester->getDisplay();
        self::assertStringContainsString('2 missing file(s) are still referenced', $display);
        self::assertStringContainsString('sys_file:2 (1:/missing-referenced.png)', $display);
        self::assertStringContainsString('sys_file:3 (1:/missing-softref.png)', $display);
        self::assertStringContainsString('1 missing file record(s) without references would be removed', $display);
        self::assertStringContainsString('sys_file:4 (1:/missing-unreferenced.png)', $display);
    }

    #[Test]
    public function storageIndexUpdateFlagsDeletedFilesBeforeCleanup(): void
    {
        $exitCode = $this->commandTester->execute(['--update-refindex' => true, '--update-storage' => ['1']], ['interactive' => false]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertSame([1, 2, 3], $this->getRemainingFixtureFileUids());
        self::assertSame(0, (int)$this->getConnectionPool()->getConnectionForTable('sys_file')
            ->count('*', 'sys_file', ['identifier' => '/existing.png', 'missing' => 1]));
    }

    #[Test]
    public function unknownStorageUidIsRejected(): void
    {
        $exitCode = $this->commandTester->execute(['--update-storage' => ['99']], ['interactive' => false]);

        self::assertSame(Command::INVALID, $exitCode);
        self::assertStringContainsString('File storage with uid "99" does not exist', $this->commandTester->getDisplay());
        self::assertSame([1, 2, 3, 4, 5], $this->getRemainingFixtureFileUids());
    }

    /**
     * @return int[]
     */
    private function getRemainingFixtureFileUids(): array
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('sys_file');
        $uids = $queryBuilder
            ->select('uid')
            ->from('sys_file')
            ->where($queryBuilder->expr()->lte('uid', 5))
            ->orderBy('uid')
            ->executeQuery()
            ->fetchFirstColumn();
        return array_map(intval(...), $uids);
    }
}
