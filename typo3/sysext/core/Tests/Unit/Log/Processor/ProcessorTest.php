<?php
namespace TYPO3\CMS\Core\Tests\Unit\Log\Processor;

/**
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

/**
 * Testcase for log processors.
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class ProcessorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase implements \TYPO3\CMS\Core\Log\Processor\ProcessorInterface {

	/**
	 * @var bool
	 */
	public $processorCalled = FALSE;

	/**
	 * Processes a log record and adds server data.
	 *
	 * @param \TYPO3\CMS\Core\Log\LogRecord $logRecord The log record to process
	 * @return \TYPO3\CMS\Core\Log\LogRecord The processed log record with addtional data
	 */
	public function processLogRecord(\TYPO3\CMS\Core\Log\LogRecord $logRecord) {
		$this->processorCalled = TRUE;
		return $logRecord;
	}

	/**
	 * @test
	 */
	public function loggerExecutesProcessors() {
		$logger = new \TYPO3\CMS\Core\Log\Logger('test.core.log');
		$writer = new \TYPO3\CMS\Core\Log\Writer\NullWriter();
		$level = \TYPO3\CMS\Core\Log\LogLevel::DEBUG;
		$logger->addWriter($level, $writer);
		$logger->addProcessor($level, $this);
		$logger->warning('test');
		$this->assertTrue($this->processorCalled);
	}

}
