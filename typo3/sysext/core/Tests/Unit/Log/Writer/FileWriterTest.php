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

namespace TYPO3\CMS\Core\Tests\Unit\Log\Writer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Writer\FileWriter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FileWriterTest extends UnitTestCase
{
    protected string $logFileDirectory = 'Log';
    protected string $logFileName = 'test.log';
    protected string $testRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testRoot = Environment::getVarPath() . '/tests/';
        GeneralUtility::mkdir_deep($this->testRoot);
        $this->testFilesToDelete[] = $this->testRoot;
    }

    protected function createWriter(string $prependName = ''): FileWriter
    {
        $logFileName = $this->getDefaultFileName($prependName);
        if (file_exists($logFileName)) {
            unlink($logFileName);
        }
        return new FileWriter(['logFile' => $logFileName]);
    }

    /**
     * @return non-empty-string
     */
    protected function getDefaultFileName(string $prependName = ''): string
    {
        return $this->testRoot . $this->logFileDirectory . '/' . $prependName . $this->logFileName;
    }

    #[Test]
    public function setLogFileSetsLogFile(): void
    {
        $writer = new FileWriter();
        $writer->setLogFile($this->getDefaultFileName());
        self::assertEquals($this->getDefaultFileName(), $writer->getLogFile());
    }

    #[Test]
    public function setLogFileAcceptsAbsolutePath(): void
    {
        $writer = new FileWriter();
        $tempFile = rtrim(sys_get_temp_dir(), '/\\') . '/typo3.log';
        $writer->setLogFile($tempFile);
        self::assertEquals($tempFile, $writer->getLogFile());
    }

    #[Test]
    public function createsLogFileDirectory(): void
    {
        $this->createWriter();
        self::assertDirectoryExists($this->testRoot . $this->logFileDirectory);
    }

    #[Test]
    public function createsLogFile(): void
    {
        $this->createWriter();
        self::assertFileExists($this->getDefaultFileName());
    }

    public static function logsToFileDataProvider(): array
    {
        $simpleRecord = new LogRecord(StringUtility::getUniqueId('test.core.log.fileWriter.simpleRecord.'), LogLevel::INFO, 'test record');
        $recordWithData = new LogRecord(StringUtility::getUniqueId('test.core.log.fileWriter.recordWithData.'), LogLevel::ALERT, 'test record with data', ['foo' => ['bar' => 'baz']]);
        return [
            'simple record' => [$simpleRecord, trim((string)$simpleRecord)],
            'record with data' => [$recordWithData, trim((string)$recordWithData)],
        ];
    }

    #[DataProvider('logsToFileDataProvider')]
    #[Test]
    public function logsToFile(LogRecord $record, string $expectedResult): void
    {
        $this->createWriter()->writeLog($record);
        $logFileContents = trim(file_get_contents($this->getDefaultFileName()));
        self::assertEquals($expectedResult, $logFileContents);
    }

    #[DataProvider('logsToFileDataProvider')]
    #[Test]
    public function differentWritersLogToDifferentFiles(LogRecord $record, string $expectedResult): void
    {
        $firstWriter = $this->createWriter();
        $secondWriter = $this->createWriter('second-');

        $firstWriter->writeLog($record);
        $secondWriter->writeLog($record);

        $firstLogFileContents = trim(file_get_contents($this->getDefaultFileName()));
        $secondLogFileContents = trim(file_get_contents($this->getDefaultFileName('second-')));

        self::assertEquals($expectedResult, $firstLogFileContents);
        self::assertEquals($expectedResult, $secondLogFileContents);
    }

    #[Test]
    public function logsToFileWithUnescapedCharacters(): void
    {
        $recordWithData = new LogRecord(
            StringUtility::getUniqueId('test.core.log.fileWriter.recordWithData.'),
            LogLevel::INFO,
            'test record with unicode and slash in data to encode',
            ['foo' => ['bar' => 'I paid 0.00€ for open source projects/code']]
        );

        $expectedResult = '{"foo":{"bar":"I paid 0.00€ for open source projects/code"}}';

        $this->createWriter('encoded-data')->writeLog($recordWithData);
        $logFileContents = trim(file_get_contents($this->getDefaultFileName('encoded-data')));
        self::assertStringContainsString($expectedResult, $logFileContents);
    }

    #[Test]
    public function aSecondLogWriterToTheSameFileDoesNotOpenTheFileTwice(): void
    {
        $firstWriter = $this->getMockBuilder(FileWriter::class)
            ->onlyMethods([])
            ->getMock();
        $secondWriter = $this->getMockBuilder(FileWriter::class)
            ->onlyMethods(['createLogFile'])
            ->getMock();

        $secondWriter->expects($this->never())->method('createLogFile');

        $logFilePrefix = StringUtility::getUniqueId('unique');
        $firstWriter->setLogFile($this->getDefaultFileName($logFilePrefix));
        $secondWriter->setLogFile($this->getDefaultFileName($logFilePrefix));
    }

    #[Test]
    public function fileHandleIsNotClosedIfSecondFileWriterIsStillUsingSameFile(): void
    {
        $firstWriter = $this->getMockBuilder(FileWriter::class)
            ->onlyMethods(['closeLogFile'])
            ->getMock();
        $secondWriter = $this->getMockBuilder(FileWriter::class)
            ->onlyMethods(['closeLogFile'])
            ->getMock();

        $firstWriter->expects($this->never())->method('closeLogFile');
        $secondWriter->expects($this->once())->method('closeLogFile');

        $logFilePrefix = StringUtility::getUniqueId('unique');
        $firstWriter->setLogFile($this->getDefaultFileName($logFilePrefix));
        $secondWriter->setLogFile($this->getDefaultFileName($logFilePrefix));
        $firstWriter->__destruct();
        $secondWriter->__destruct();
    }
}
