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

namespace TYPO3\CMS\Core\Tests\Unit\Log;

use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Processor\NullProcessor;
use TYPO3\CMS\Core\Log\Writer\NullWriter;
use TYPO3\CMS\Core\Tests\Unit\Log\Fixtures\WriterFixture;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class LoggerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getNameGetsLoggerName(): void
    {
        $logger = new Logger('test.core.log');
        self::assertSame('test.core.log', $logger->getName());
    }

    /**
     * @test
     */
    public function loggerDoesNotLogRecordsLessCriticalThanLogLevel(): void
    {
        $logger = new Logger('test.core.log');
        $writer = new WriterFixture();
        $logger->addWriter(LogLevel::ERROR, $writer);
        // warning < error, thus must not be logged
        $logger->log(LogLevel::WARNING, 'test message');
        self::assertEmpty($writer->getRecords());
    }

    /**
     * @test
     */
    public function loggerCallsProcessor(): void
    {
        $component = 'test.core.log';
        $level = LogLevel::DEBUG;
        $message = 'test';
        $logger = new Logger($component);
        $processor = $this->getMockBuilder(NullProcessor::class)
            ->onlyMethods(['processLogRecord'])
            ->getMock();
        $processor->expects(self::once())->method('processLogRecord')->willReturn(new LogRecord($component, $level, $message));
        $logger->addProcessor($level, $processor);
        // we need a writer, otherwise we will not process log records
        $logger->addWriter($level, new NullWriter());
        $logger->warning($message);
    }

    /**
     * @test
     */
    public function loggerLogsRecord(): void
    {
        $logger = new Logger('test.core.log');
        $writer = $this->getMockBuilder(NullWriter::class)
            ->onlyMethods(['writeLog'])
            ->getMock();
        $writer->expects(self::once())->method('writeLog');
        $logger->addWriter(LogLevel::DEBUG, $writer);
        $logger->warning('test');
    }

    /**
     * @test
     */
    public function loggerLogsRecordsAtLeastAsCriticalAsLogLevel(): void
    {
        $logger = new Logger('test.core.log');
        $writer = new WriterFixture();
        $logger->addWriter(LogLevel::NOTICE, $writer);
        // notice == notice, thus must be logged
        $logger->log(LogLevel::NOTICE, 'test message');
        self::assertNotEmpty($writer->getRecords());
    }

    public static function loggerLogsRecordsThroughShorthandMethodDataProvider(): array
    {
        return [
            ['emergency'],
            ['alert'],
            ['critical'],
            ['error'],
            ['warning'],
            ['notice'],
            ['info'],
            ['debug'],
        ];
    }

    /**
     * @test
     * @dataProvider loggerLogsRecordsThroughShorthandMethodDataProvider
     */
    public function loggerLogsRecordsThroughShorthandMethod(string $shorthandMethod): void
    {
        $logger = new Logger('test.core.log');
        $writer = new WriterFixture();
        $logger->addWriter(LogLevel::DEBUG, $writer);
        $logger->$shorthandMethod('test message');
        self::assertNotEmpty($writer->getRecords());
    }

    /**
     * @test
     */
    public function loggerLogsRecordsMoreCriticalThanLogLevel(): void
    {
        $logger = new Logger('test.core.log');
        $writer = new WriterFixture();
        $logger->addWriter(LogLevel::NOTICE, $writer);
        // warning > notice, thus must be logged
        $logger->log(LogLevel::WARNING, 'test message');
        self::assertNotEmpty($writer->getRecords());
    }

    /**
     * @test
     */
    public function addWriterAddsWriterToTheSpecifiedLevel(): void
    {
        $logger = new Logger('test.core.log');
        $writer = new WriterFixture();
        $logger->addWriter(LogLevel::NOTICE, $writer);
        $writers = $logger->getWriters();
        self::assertContains($writer, $writers[LogLevel::NOTICE]);
    }

    /**
     * @test
     */
    public function addWriterAddsWriterAlsoToHigherLevelsThanSpecified(): void
    {
        $logger = new Logger('test.core.log');
        $writer = new WriterFixture();
        $logger->addWriter(LogLevel::NOTICE, $writer);
        $writers = $logger->getWriters();
        self::assertContains($writer, $writers[LogLevel::EMERGENCY]);
    }
}
