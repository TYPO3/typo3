<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2011-2012 Ingo Renner (ingo@typo3.org)
 * (c) 2011-2012 Steffen Gebert (steffen.gebert@typo3.org)
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

require_once('writer/class.t3lib_log_writer_test.php');
require_once('writer/class.t3lib_log_writer_failing.php');

/**
 * Testcase for t3lib_log_Logger.
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @author Steffen Gebert <steffen.gebert@typo3.org>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_log_LoggerTest extends tx_phpunit_testcase {

	/**
	 * @test
	 */
	public function getNameGetsLoggerName() {
		$logger = new t3lib_log_Logger('test.core.log');
		$this->assertEquals('test.core.log', $logger->getName());
	}

	/**
	 * @test
	 */
	public function loggerDoesNotLogRecordsLessCriticalThanLogLevel() {
		$logger = new t3lib_log_Logger('test.core.log');

		$writer = new t3lib_log_writer_Test();
		$logger->addWriter(t3lib_log_Level::ERROR, $writer);

			// warning < error, thus must not be logged
		$logger->log(t3lib_log_Level::WARNING, 'test message');

		$this->assertAttributeEmpty('records', $writer);
	}

	/**
	 * @test
	 */
	public function loggerReturnsItselfAfterLogging() {
		$logger = new t3lib_log_Logger('test.core.log');

		$writer = new t3lib_log_writer_Test();
		$logger->addWriter(t3lib_log_Level::DEBUG, $writer);

		$returnValue = $logger->log(t3lib_log_Level::WARNING, 'test message');

		$this->assertInstanceOf('t3lib_log_Logger', $returnValue);
	}

	/**
	 * @test
	 */
	public function loggerReturnsItselfAfterLoggingWithoutWriter() {
		$logger = new t3lib_log_Logger('test.core.log');

		$returnValue = $logger->log(t3lib_log_Level::WARNING, 'test message');

		$this->assertInstanceOf('t3lib_log_Logger', $returnValue);
	}

	/**
	 * @test
	 */
	public function loggerReturnsItselfAfterLoggingLessCritical() {
		$logger = new t3lib_log_Logger('test.core.log');

		$writer = new t3lib_log_writer_Test();
		$logger->addWriter(t3lib_log_Level::EMERGENCY, $writer);

		$returnValue = $logger->log(t3lib_log_Level::WARNING, 'test message');

		$this->assertInstanceOf('t3lib_log_Logger', $returnValue);
	}

	/**
	 * @test
	 */
	public function loggerCallsProcessor() {
		$component = 'test.core.log';
		$level = t3lib_log_Level::DEBUG;
		$message = 'test';

		$logger = new t3lib_log_Logger($component);

		/** @var $processor t3lib_log_processor_Processor */
		$processor = $this->getMock('t3lib_log_processor_Null', array('processLogRecord'));
		$processor->expects($this->once())
			->method('processLogRecord')
			->will($this->returnValue(new t3lib_log_Record($component, $level, $message)));

		$logger->addProcessor($level, $processor);

			// we need a writer, otherwise we will not process log records
		$logger->addWriter($level, new t3lib_log_writer_Null());

		$logger->warning($message);
	}

	/**
	 * @test
	 */
	public function loggerLogsRecord() {
		$logger = new t3lib_log_Logger('test.core.log');

		$writer = $this->getMock('t3lib_log_writer_Null', array('writeLog'));
		$writer->expects($this->once())
			->method('writeLog');

		$logger->addWriter(t3lib_log_Level::DEBUG, $writer);

		$logger->warning('test');
	}

	/**
	 * @test
	 */
	public function loggerLogsRecordsAtLeastAsCriticalAsLogLevel() {
		$logger = new t3lib_log_Logger('test.core.log');

		$writer = new t3lib_log_writer_Test();
		$logger->addWriter(t3lib_log_Level::NOTICE, $writer);

			// notice == notice, thus must be logged
		$logger->log(t3lib_log_Level::NOTICE, 'test message');

		$this->assertAttributeNotEmpty('records', $writer);
	}

	public function loggerLogsRecordsThroughShorthandMethodDataProvider() {
		return array(
			array('emergency'),
			array('alert'),
			array('critical'),
			array('error'),
			array('warning'),
			array('notice'),
			array('info'),
			array('debug'),
		);
	}

	/**
	 * @test
	 * @param string $shorthandMethod
	 * @dataProvider loggerLogsRecordsThroughShorthandMethodDataProvider
	 */
	public function loggerLogsRecordsThroughShorthandMethod($shorthandMethod) {
		$logger = new t3lib_log_Logger('test.core.log');

		$writer = new t3lib_log_writer_Test();
		$logger->addWriter(t3lib_log_Level::DEBUG, $writer);

		call_user_func(array($logger, $shorthandMethod), 'test message');

		$this->assertAttributeNotEmpty('records', $writer);
	}

	/**
	 * @test
	 */
	public function loggerLogsRecordsMoreCriticalThanLogLevel() {
		$logger = new t3lib_log_Logger('test.core.log');

		$writer = new t3lib_log_writer_Test();
		$logger->addWriter(t3lib_log_Level::NOTICE, $writer);

			// warning > notice, thus must be logged
		$logger->log(t3lib_log_Level::WARNING, 'test message');

		$this->assertAttributeNotEmpty('records', $writer);
	}

	/**
	 * @test
	 */
	public function loggerLogsTwoMessagesToFallbackWriterIfAllWritersFailWhileOneWriterIsRegistered()
	{
		$logger = new t3lib_log_Logger('test.core.log');

		$failingWriter = new t3lib_log_writer_Failing();
		$logger->addWriter(t3lib_log_Level::NOTICE, $failingWriter);

		/** @var $fallbackWriter t3lib_log_writer_Null */
		$fallbackWriter = $this->getMock('t3lib_log_writer_Null', array('writeLog'));

		$fallbackWriter->expects($this->exactly(2))
			->method('writeLog');
		$logger->setFallbackWriter($fallbackWriter);

		$logger->log(t3lib_log_Level::NOTICE, 'test message');
	}

	/**
	 * @test
	 */
	public function loggerLogsNotToFallbackWriterIfWritersSucceed()
	{
		$logger = new t3lib_log_Logger('test.core.log');

		$succeedingWriter = new t3lib_log_writer_Null();
		$logger->addWriter(t3lib_log_Level::NOTICE, $succeedingWriter);

		/** @var $fallbackWriter t3lib_log_writer_Null */
		$fallbackWriter = $this->getMock('t3lib_log_writer_Null', array('writeLog'));
		$fallbackWriter->expects($this->never())
			->method('writeLog');

		$logger->setFallbackWriter($fallbackWriter);

		$logger->log(t3lib_log_Level::NOTICE, 'test message');
	}

	/**
	 * @test
	 */
	public function addWriterAddsWriter() {
		$logger = new t3lib_log_Logger('test.core.log');

		$writer = new t3lib_log_writer_Test();
		$logger->addWriter(t3lib_log_Level::NOTICE, $writer);

		$this->markTestSkipped("writers attribute is multi-dimensional - how to test this?");
		$this->assertAttributeContains($writer, 'writers', $logger);
	}

	/**
	 * @test
	 */
	public function getFallbackWriterReturnsPhpErrorLogWriter() {
		$logger = new t3lib_log_Logger('test.core.log');
		$this->assertInstanceOf('t3lib_log_writer_PhpErrorLog', $logger->getFallbackWriter());
	}

	/**
	 * @test
	 */
	public function getFallbackWriterReturnsPreviouslySetFallbackWriter() {
		$logger = new t3lib_log_Logger('test.core.log');
		/** @var $writer t3lib_log_writer_Writer */
		$writer = $this->getMock('t3lib_log_writer_Null');

		$this->assertSame(
			$writer,
			$logger->setFallbackWriter($writer)->getFallbackWriter()
		);
	}
}

?>