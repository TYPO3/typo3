<?php
namespace TYPO3\CMS\Core\Tests\Unit\Log\Processor;

/***************************************************************
 * Copyright notice
 *
 * (c) 2012-2013 Ingo Renner (ingo@typo3.org)
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

?>