<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2012 Ingo Renner (ingo@typo3.org)
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
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_log_processor_ProcessorTest extends tx_phpunit_testcase implements t3lib_log_processor_Processor {

	public $processorCalled = FALSE;

	/**
	 * Processes a log record and adds server data.
	 *
	 * @param t3lib_log_Record $logRecord The log record to process
	 * @return t3lib_log_Record The processed log record with addtional data
	 */
	public function processLogRecord(t3lib_log_Record $logRecord) {
		$this->processorCalled = TRUE;

		return $logRecord;
	}

	/**
	 * @test
	 */
	public function loggerExecutesProcessors() {
		$logger = new t3lib_log_Logger('test.core.log');
		$writer = new t3lib_log_writer_Null();
		$level = t3lib_log_Level::DEBUG;

		$logger->addWriter($level, $writer);
		$logger->addProcessor($level, $this);

		$logger->warning('test');

		$this->assertTrue($this->processorCalled);
	}

}

?>