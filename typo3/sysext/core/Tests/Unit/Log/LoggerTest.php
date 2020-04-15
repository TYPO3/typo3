<?php

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

use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Processor\NullProcessor;
use TYPO3\CMS\Core\Log\Writer\NullWriter;
use TYPO3\CMS\Core\Tests\Unit\Log\Fixtures\WriterFixture;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class LoggerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getNameGetsLoggerName()
    {
        $logger = new Logger('test.core.log');
        self::assertSame('test.core.log', $logger->getName());
    }

    /**
     * @test
     */
    public function loggerDoesNotLogRecordsLessCriticalThanLogLevel()
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
    public function loggerReturnsItselfAfterLogging()
    {
        $logger = new Logger('test.core.log');
        $writer = new WriterFixture();
        $logger->addWriter(LogLevel::DEBUG, $writer);
        $returnValue = $logger->log(LogLevel::WARNING, 'test message');
        self::assertInstanceOf(Logger::class, $returnValue);
    }

    /**
     * @test
     */
    public function loggerReturnsItselfAfterLoggingWithoutWriter()
    {
        $logger = new Logger('test.core.log');
        $returnValue = $logger->log(LogLevel::WARNING, 'test message');
        self::assertInstanceOf(Logger::class, $returnValue);
    }

    /**
     * @test
     */
    public function loggerReturnsItselfAfterLoggingLessCritical()
    {
        $logger = new Logger('test.core.log');
        $writer = new WriterFixture();
        $logger->addWriter(LogLevel::EMERGENCY, $writer);
        $returnValue = $logger->log(LogLevel::WARNING, 'test message');
        self::assertInstanceOf(Logger::class, $returnValue);
    }

    /**
     * @test
     */
    public function loggerCallsProcessor()
    {
        $component = 'test.core.log';
        $level = LogLevel::DEBUG;
        $message = 'test';
        $logger = new Logger($component);
        /** @var $processor \TYPO3\CMS\Core\Log\Processor\ProcessorInterface|\PHPUnit\Framework\MockObject\MockObject */
        $processor = $this->getMockBuilder(NullProcessor::class)
            ->setMethods(['processLogRecord'])
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
    public function loggerLogsRecord()
    {
        $logger = new Logger('test.core.log');
        /** @var NullWriter|\PHPUnit\Framework\MockObject\MockObject $writer */
        $writer = $this->getMockBuilder(NullWriter::class)
            ->setMethods(['writeLog'])
            ->getMock();
        $writer->expects(self::once())->method('writeLog');
        $logger->addWriter(LogLevel::DEBUG, $writer);
        $logger->warning('test');
    }

    /**
     * @test
     */
    public function loggerLogsRecordsAtLeastAsCriticalAsLogLevel()
    {
        $logger = new Logger('test.core.log');
        $writer = new WriterFixture();
        $logger->addWriter(LogLevel::NOTICE, $writer);
        // notice == notice, thus must be logged
        $logger->log(LogLevel::NOTICE, 'test message');
        self::assertNotEmpty($writer->getRecords());
    }

    /**
     * @return array
     */
    public function loggerLogsRecordsThroughShorthandMethodDataProvider()
    {
        return [
            ['emergency'],
            ['alert'],
            ['critical'],
            ['error'],
            ['warning'],
            ['notice'],
            ['info'],
            ['debug']
        ];
    }

    /**
     * @test
     * @param string $shorthandMethod
     * @dataProvider loggerLogsRecordsThroughShorthandMethodDataProvider
     */
    public function loggerLogsRecordsThroughShorthandMethod($shorthandMethod)
    {
        $logger = new Logger('test.core.log');
        $writer = new WriterFixture();
        $logger->addWriter(LogLevel::DEBUG, $writer);
        call_user_func([$logger, $shorthandMethod], 'test message');
        self::assertNotEmpty($writer->getRecords());
    }

    /**
     * @test
     */
    public function loggerLogsRecordsMoreCriticalThanLogLevel()
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
    public function addWriterAddsWriterToTheSpecifiedLevel()
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
    public function addWriterAddsWriterAlsoToHigherLevelsThanSpecified()
    {
        $logger = new Logger('test.core.log');
        $writer = new WriterFixture();
        $logger->addWriter(LogLevel::NOTICE, $writer);
        $writers = $logger->getWriters();
        self::assertContains($writer, $writers[LogLevel::EMERGENCY]);
    }
}
