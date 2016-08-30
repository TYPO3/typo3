<?php
namespace TYPO3\CMS\Core\Tests\Unit\Log;

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

use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Processor\NullProcessor;
use TYPO3\CMS\Core\Log\Writer\NullWriter;

/**
 * Test case
 */
class LoggerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function getNameGetsLoggerName()
    {
        $logger = new Logger('test.core.log');
        $this->assertSame('test.core.log', $logger->getName());
    }

    /**
     * @test
     */
    public function loggerDoesNotLogRecordsLessCriticalThanLogLevel()
    {
        $logger = new Logger('test.core.log');
        $writer = new Fixtures\WriterFixture();
        $logger->addWriter(LogLevel::ERROR, $writer);
            // warning < error, thus must not be logged
        $logger->log(LogLevel::WARNING, 'test message');
        $this->assertAttributeEmpty('records', $writer);
    }

    /**
     * @test
     */
    public function loggerReturnsItselfAfterLogging()
    {
        $logger = new Logger('test.core.log');
        $writer = new Fixtures\WriterFixture();
        $logger->addWriter(LogLevel::DEBUG, $writer);
        $returnValue = $logger->log(LogLevel::WARNING, 'test message');
        $this->assertInstanceOf(Logger::class, $returnValue);
    }

    /**
     * @test
     */
    public function loggerReturnsItselfAfterLoggingWithoutWriter()
    {
        $logger = new Logger('test.core.log');
        $returnValue = $logger->log(LogLevel::WARNING, 'test message');
        $this->assertInstanceOf(Logger::class, $returnValue);
    }

    /**
     * @test
     */
    public function loggerReturnsItselfAfterLoggingLessCritical()
    {
        $logger = new Logger('test.core.log');
        $writer = new Fixtures\WriterFixture();
        $logger->addWriter(LogLevel::EMERGENCY, $writer);
        $returnValue = $logger->log(LogLevel::WARNING, 'test message');
        $this->assertInstanceOf(Logger::class, $returnValue);
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
        /** @var $processor \TYPO3\CMS\Core\Log\Processor\ProcessorInterface|\PHPUnit_Framework_MockObject_MockObject */
        $processor = $this->getMock(NullProcessor::class, ['processLogRecord']);
        $processor->expects($this->once())->method('processLogRecord')->will($this->returnValue(new LogRecord($component, $level, $message)));
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
        /** @var NullWriter|\PHPUnit_Framework_MockObject_MockObject $writer */
        $writer = $this->getMock(NullWriter::class, ['writeLog']);
        $writer->expects($this->once())->method('writeLog');
        $logger->addWriter(LogLevel::DEBUG, $writer);
        $logger->warning('test');
    }

    /**
     * @test
     */
    public function loggerLogsRecordsAtLeastAsCriticalAsLogLevel()
    {
        $logger = new Logger('test.core.log');
        $writer = new Fixtures\WriterFixture();
        $logger->addWriter(LogLevel::NOTICE, $writer);
            // notice == notice, thus must be logged
        $logger->log(LogLevel::NOTICE, 'test message');
        $this->assertAttributeNotEmpty('records', $writer);
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
        $writer = new Fixtures\WriterFixture();
        $logger->addWriter(LogLevel::DEBUG, $writer);
        call_user_func([$logger, $shorthandMethod], 'test message');
        $this->assertAttributeNotEmpty('records', $writer);
    }

    /**
     * @test
     */
    public function loggerLogsRecordsMoreCriticalThanLogLevel()
    {
        $logger = new Logger('test.core.log');
        $writer = new Fixtures\WriterFixture();
        $logger->addWriter(LogLevel::NOTICE, $writer);
            // warning > notice, thus must be logged
        $logger->log(LogLevel::WARNING, 'test message');
        $this->assertAttributeNotEmpty('records', $writer);
    }

    /**
     * @test
     */
    public function addWriterAddsWriterToTheSpecifiedLevel()
    {
        $logger = new Logger('test.core.log');
        $writer = new Fixtures\WriterFixture();
        $logger->addWriter(LogLevel::NOTICE, $writer);
        $writers = $logger->getWriters();
        $this->assertContains($writer, $writers[LogLevel::NOTICE]);
    }

    /**
     * @test
     */
    public function addWriterAddsWriterAlsoToHigherLevelsThanSpecified()
    {
        $logger = new Logger('test.core.log');
        $writer = new Fixtures\WriterFixture();
        $logger->addWriter(LogLevel::NOTICE, $writer);
        $writers = $logger->getWriters();
        $this->assertContains($writer, $writers[LogLevel::EMERGENCY]);
    }
}
