<?php
namespace TYPO3\CMS\Core\Tests\Unit\Log;

/***************************************************************
 * Copyright notice
 *
 * (c) 2011-2013 Ingo Renner (ingo@typo3.org)
 * (c) 2011-2013 Steffen Gebert (steffen.gebert@typo3.org)
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

require_once 'Fixtures/WriterFixture.php';
require_once 'Fixtures/WriterFailing.php';

/**
 * Testcase for \TYPO3\CMS\Core\Log\Logger.
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @author Steffen Gebert <steffen.gebert@typo3.org>
 */
class LoggerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function getNameGetsLoggerName() {
		$logger = new \TYPO3\CMS\Core\Log\Logger('test.core.log');
		$this->assertSame('test.core.log', $logger->getName());
	}

	/**
	 * @test
	 */
	public function loggerDoesNotLogRecordsLessCriticalThanLogLevel() {
		$logger = new \TYPO3\CMS\Core\Log\Logger('test.core.log');
		$writer = new \TYPO3\CMS\Core\Tests\Unit\Log\Fixtures\WriterFixture();
		$logger->addWriter(\TYPO3\CMS\Core\Log\LogLevel::ERROR, $writer);
			// warning < error, thus must not be logged
		$logger->log(\TYPO3\CMS\Core\Log\LogLevel::WARNING, 'test message');
		$this->assertAttributeEmpty('records', $writer);
	}

	/**
	 * @test
	 */
	public function loggerReturnsItselfAfterLogging() {
		$logger = new \TYPO3\CMS\Core\Log\Logger('test.core.log');
		$writer = new \TYPO3\CMS\Core\Tests\Unit\Log\Fixtures\WriterFixture();
		$logger->addWriter(\TYPO3\CMS\Core\Log\LogLevel::DEBUG, $writer);
		$returnValue = $logger->log(\TYPO3\CMS\Core\Log\LogLevel::WARNING, 'test message');
		$this->assertInstanceOf('TYPO3\\CMS\\Core\\Log\\Logger', $returnValue);
	}

	/**
	 * @test
	 */
	public function loggerReturnsItselfAfterLoggingWithoutWriter() {
		$logger = new \TYPO3\CMS\Core\Log\Logger('test.core.log');
		$returnValue = $logger->log(\TYPO3\CMS\Core\Log\LogLevel::WARNING, 'test message');
		$this->assertInstanceOf('TYPO3\\CMS\\Core\\Log\\Logger', $returnValue);
	}

	/**
	 * @test
	 */
	public function loggerReturnsItselfAfterLoggingLessCritical() {
		$logger = new \TYPO3\CMS\Core\Log\Logger('test.core.log');
		$writer = new \TYPO3\CMS\Core\Tests\Unit\Log\Fixtures\WriterFixture();
		$logger->addWriter(\TYPO3\CMS\Core\Log\LogLevel::EMERGENCY, $writer);
		$returnValue = $logger->log(\TYPO3\CMS\Core\Log\LogLevel::WARNING, 'test message');
		$this->assertInstanceOf('TYPO3\\CMS\\Core\\Log\\Logger', $returnValue);
	}

	/**
	 * @test
	 */
	public function loggerCallsProcessor() {
		$component = 'test.core.log';
		$level = \TYPO3\CMS\Core\Log\LogLevel::DEBUG;
		$message = 'test';
		$logger = new \TYPO3\CMS\Core\Log\Logger($component);
		/** @var $processor \TYPO3\CMS\Core\Log\Processor\ProcessorInterface */
		$processor = $this->getMock('TYPO3\\CMS\\Core\\Log\\Processor\\NullProcessor', array('processLogRecord'));
		$processor->expects($this->once())->method('processLogRecord')->will($this->returnValue(new \TYPO3\CMS\Core\Log\LogRecord($component, $level, $message)));
		$logger->addProcessor($level, $processor);
			// we need a writer, otherwise we will not process log records
		$logger->addWriter($level, new \TYPO3\CMS\Core\Log\Writer\NullWriter());
		$logger->warning($message);
	}

	/**
	 * @test
	 */
	public function loggerLogsRecord() {
		$logger = new \TYPO3\CMS\Core\Log\Logger('test.core.log');
		$writer = $this->getMock('TYPO3\\CMS\\Core\\Log\\Writer\\NullWriter', array('writeLog'));
		$writer->expects($this->once())->method('writeLog');
		$logger->addWriter(\TYPO3\CMS\Core\Log\LogLevel::DEBUG, $writer);
		$logger->warning('test');
	}

	/**
	 * @test
	 */
	public function loggerLogsRecordsAtLeastAsCriticalAsLogLevel() {
		$logger = new \TYPO3\CMS\Core\Log\Logger('test.core.log');
		$writer = new \TYPO3\CMS\Core\Tests\Unit\Log\Fixtures\WriterFixture();
		$logger->addWriter(\TYPO3\CMS\Core\Log\LogLevel::NOTICE, $writer);
			// notice == notice, thus must be logged
		$logger->log(\TYPO3\CMS\Core\Log\LogLevel::NOTICE, 'test message');
		$this->assertAttributeNotEmpty('records', $writer);
	}

	/**
	 * @test
	 */
	public function loggerLogsRecordsThroughShorthandMethodDataProvider() {
		return array(
			array('emergency'),
			array('alert'),
			array('critical'),
			array('error'),
			array('warning'),
			array('notice'),
			array('info'),
			array('debug')
		);
	}

	/**
	 * @test
	 * @param string $shorthandMethod
	 * @dataProvider loggerLogsRecordsThroughShorthandMethodDataProvider
	 */
	public function loggerLogsRecordsThroughShorthandMethod($shorthandMethod) {
		$logger = new \TYPO3\CMS\Core\Log\Logger('test.core.log');
		$writer = new \TYPO3\CMS\Core\Tests\Unit\Log\Fixtures\WriterFixture();
		$logger->addWriter(\TYPO3\CMS\Core\Log\LogLevel::DEBUG, $writer);
		call_user_func(array($logger, $shorthandMethod), 'test message');
		$this->assertAttributeNotEmpty('records', $writer);
	}

	/**
	 * @test
	 */
	public function loggerLogsRecordsMoreCriticalThanLogLevel() {
		$logger = new \TYPO3\CMS\Core\Log\Logger('test.core.log');
		$writer = new \TYPO3\CMS\Core\Tests\Unit\Log\Fixtures\WriterFixture();
		$logger->addWriter(\TYPO3\CMS\Core\Log\LogLevel::NOTICE, $writer);
			// warning > notice, thus must be logged
		$logger->log(\TYPO3\CMS\Core\Log\LogLevel::WARNING, 'test message');
		$this->assertAttributeNotEmpty('records', $writer);
	}

	/**
	 * @test
	 */
	public function addWriterAddsWriterToTheSpecifiedLevel() {
		$logger = new \TYPO3\CMS\Core\Log\Logger('test.core.log');
		$writer = new \TYPO3\CMS\Core\Tests\Unit\Log\Fixtures\WriterFixture();
		$logger->addWriter(\TYPO3\CMS\Core\Log\LogLevel::NOTICE, $writer);
		$writers = $logger->getWriters();
		$this->assertContains($writer, $writers[\TYPO3\CMS\Core\Log\LogLevel::NOTICE]);
	}

	/**
	 * @test
	 */
	public function addWriterAddsWriterAlsoToHigherLevelsThanSpecified() {
		$logger = new \TYPO3\CMS\Core\Log\Logger('test.core.log');
		$writer = new \TYPO3\CMS\Core\Tests\Unit\Log\Fixtures\WriterFixture();
		$logger->addWriter(\TYPO3\CMS\Core\Log\LogLevel::NOTICE, $writer);
		$writers = $logger->getWriters();
		$this->assertContains($writer, $writers[\TYPO3\CMS\Core\Log\LogLevel::EMERGENCY]);
	}

}

?>