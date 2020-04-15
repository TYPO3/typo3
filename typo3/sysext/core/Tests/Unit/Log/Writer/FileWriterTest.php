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

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Writer\FileWriter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class FileWriterTest extends UnitTestCase
{
    /**
     * @var string
     */
    protected $logFileDirectory = 'Log';

    /**
     * @var string
     */
    protected $logFileName = 'test.log';

    protected function setUpVfsStream(): void
    {
        vfsStream::setup('LogRoot');
    }

    /**
     * Creates a test logger
     *
     * @param string $name
     * @internal param string $component Component key
     * @return Logger
     */
    protected function createLogger($name = ''): Logger
    {
        if (empty($name)) {
            $name = StringUtility::getUniqueId('test.core.log.');
        }
        GeneralUtility::makeInstance(LogManager::class)->registerLogger($name);
        /** @var Logger $logger */
        $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger($name);
        return $logger;
    }

    /**
     * Creates a file writer
     *
     * @param string $prependName
     * @return FileWriter
     */
    protected function createWriter($prependName = ''): FileWriter
    {
        /** @var FileWriter $writer */
        $writer = GeneralUtility::makeInstance(FileWriter::class, [
            'logFile' => $this->getDefaultFileName($prependName)
        ]);
        return $writer;
    }

    protected function getDefaultFileName($prependName = ''): string
    {
        return 'vfs://LogRoot/' . $this->logFileDirectory . '/' . $prependName . $this->logFileName;
    }

    /**
     * @test
     */
    public function setLogFileSetsLogFile(): void
    {
        $this->setUpVfsStream();
        vfsStream::newFile($this->logFileName)->at(vfsStreamWrapper::getRoot());
        $writer = GeneralUtility::makeInstance(FileWriter::class);
        $writer->setLogFile($this->getDefaultFileName());
        self::assertEquals($this->getDefaultFileName(), $writer->getLogFile());
    }

    /**
     * @test
     */
    public function setLogFileAcceptsAbsolutePath(): void
    {
        $writer = GeneralUtility::makeInstance(FileWriter::class);
        $tempFile = rtrim(sys_get_temp_dir(), '/\\') . '/typo3.log';
        $writer->setLogFile($tempFile);
        self::assertEquals($tempFile, $writer->getLogFile());
    }

    /**
     * @test
     */
    public function createsLogFileDirectory(): void
    {
        $this->setUpVfsStream();
        $this->createWriter();
        self::assertTrue(vfsStreamWrapper::getRoot()->hasChild($this->logFileDirectory));
    }

    /**
     * @test
     */
    public function createsLogFile(): void
    {
        $this->setUpVfsStream();
        $this->createWriter();
        self::assertTrue(vfsStreamWrapper::getRoot()->getChild($this->logFileDirectory)->hasChild($this->logFileName));
    }

    /**
     * @return array
     */
    public function logsToFileDataProvider(): array
    {
        $simpleRecord = GeneralUtility::makeInstance(LogRecord::class, StringUtility::getUniqueId('test.core.log.fileWriter.simpleRecord.'), LogLevel::INFO, 'test record');
        $recordWithData = GeneralUtility::makeInstance(LogRecord::class, StringUtility::getUniqueId('test.core.log.fileWriter.recordWithData.'), LogLevel::ALERT, 'test record with data', ['foo' => ['bar' => 'baz']]);
        return [
            'simple record' => [$simpleRecord, trim((string)$simpleRecord)],
            'record with data' => [$recordWithData, trim((string)$recordWithData)]
        ];
    }

    /**
     * @test
     * @param LogRecord $record Record Test Data
     * @param string $expectedResult Needle
     * @dataProvider logsToFileDataProvider
     */
    public function logsToFile(LogRecord $record, $expectedResult): void
    {
        $this->setUpVfsStream();
        $this->createWriter()->writeLog($record);
        $logFileContents = trim(file_get_contents($this->getDefaultFileName()));
        self::assertEquals($expectedResult, $logFileContents);
    }

    /**
     * @test
     */
    public function logsToFileWithUnescapedCharacters(): void
    {
        $this->setUpVfsStream();

        $recordWithData = GeneralUtility::makeInstance(
            LogRecord::class,
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

    /**
     * @test
     * @param LogRecord $record Record Test Data
     * @param string $expectedResult Needle
     * @dataProvider logsToFileDataProvider
     */
    public function differentWritersLogToDifferentFiles(LogRecord $record, $expectedResult): void
    {
        $this->setUpVfsStream();
        $firstWriter = $this->createWriter();
        $secondWriter = $this->createWriter('second-');

        $firstWriter->writeLog($record);
        $secondWriter->writeLog($record);

        $firstLogFileContents = trim(file_get_contents($this->getDefaultFileName()));
        $secondLogFileContents = trim(file_get_contents($this->getDefaultFileName('second-')));

        self::assertEquals($expectedResult, $firstLogFileContents);
        self::assertEquals($expectedResult, $secondLogFileContents);
    }

    /**
     * @test
     */
    public function aSecondLogWriterToTheSameFileDoesNotOpenTheFileTwice()
    {
        $this->setUpVfsStream();

        $firstWriter = $this->getMockBuilder(FileWriter::class)
            ->setMethods(['dummy'])
            ->getMock();
        $secondWriter = $this->getMockBuilder(FileWriter::class)
            ->setMethods(['createLogFile'])
            ->getMock();

        $secondWriter->expects(self::never())->method('createLogFile');

        $logFilePrefix = StringUtility::getUniqueId('unique');
        $firstWriter->setLogFile($this->getDefaultFileName($logFilePrefix));
        $secondWriter->setLogFile($this->getDefaultFileName($logFilePrefix));
    }

    /**
     * @test
     */
    public function fileHandleIsNotClosedIfSecondFileWriterIsStillUsingSameFile()
    {
        $this->setUpVfsStream();

        $firstWriter = $this->getMockBuilder(FileWriter::class)
            ->setMethods(['closeLogFile'])
            ->getMock();
        $secondWriter = $this->getMockBuilder(FileWriter::class)
            ->setMethods(['closeLogFile'])
            ->getMock();

        $firstWriter->expects(self::never())->method('closeLogFile');
        $secondWriter->expects(self::once())->method('closeLogFile');

        $logFilePrefix = StringUtility::getUniqueId('unique');
        $firstWriter->setLogFile($this->getDefaultFileName($logFilePrefix));
        $secondWriter->setLogFile($this->getDefaultFileName($logFilePrefix));
        $firstWriter->__destruct();
        $secondWriter->__destruct();
    }
}
