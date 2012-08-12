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


/**
 * Testcase for t3lib_log_LogManager.
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @author Steffen Gebert <steffen.gebert@typo3.org>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_log_LogManagerTest extends tx_phpunit_testcase {

	protected $logConfig = array();

	/**
	 * @var t3lib_log_LogManager
	*/
	protected $logManagerInstance = NULL;

	public function setUp() {
		$this->logConfig = $GLOBALS['TYPO3_CONF_VARS']['LOG'];
		$GLOBALS['TYPO3_CONF_VARS']['LOG'] = array();
		$this->logManagerInstance = t3lib_div::makeInstance('t3lib_log_LogManager');
	}

	public function tearDown() {
		$GLOBALS['TYPO3_CONF_VARS']['LOG'] = $this->logConfig;
		t3lib_div::makeInstance('t3lib_log_LogManager')->reset();
	}

	/**
	 * @test
	 */
	public function turnsUnderScoreStyleLoggerNamesIntoDotStyleLoggerNames() {
		$this->assertEquals('t3lib.log.LogManager', $this->logManagerInstance->getLogger('t3lib_log_LogManager')->getName());
	}

	/**
	 * @test
	 */
	public function managerReturnsSameLoggerOnRepeatedRequest() {
		$loggerName = uniqid('test.core.log');
		$this->logManagerInstance->registerLogger($loggerName);

		$logger1 = $this->logManagerInstance->getLogger($loggerName);
		$logger2 = $this->logManagerInstance->getLogger($loggerName);

		$this->assertInstanceOf('t3lib_log_Logger', $logger1);
		$this->assertInstanceOf('t3lib_log_Logger', $logger2);

		$this->assertSame($logger1, $logger2);
	}

	/**
	 * @test
	 */
	public function configuresLoggerWithCorrectWriter() {
		$component = 'test';
		$writer = 't3lib_log_writer_Null';
		$level = t3lib_log_Level::DEBUG;

		$GLOBALS['TYPO3_CONF_VARS']['LOG'][$component]['writerConfiguration'] = array(
			$level => array(
				$writer => array()
			)
		);

		/** @var $logger t3lib_log_Logger */
		$logger = $this->logManagerInstance->getLogger($component);
		$writers = $logger->getWriters();

		$this->assertInstanceOf($writer, $writers[$level][0]);
	}

	/**
	 * @test
	 */
	public function configuresLoggerWithCorrectProcessor() {
		$component = 'test';
		$processor = 't3lib_log_processor_Null';
		$level = t3lib_log_Level::DEBUG;

		$GLOBALS['TYPO3_CONF_VARS']['LOG'][$component]['processorConfiguration'] = array(
			$level => array(
				$processor => array()
			)
		);

		/** @var $logger t3lib_log_Logger */
		$logger = $this->logManagerInstance->getLogger($component);
		$processors = $logger->getProcessors();

		$this->assertInstanceOf($processor, $processors[$level][0]);
	}
}

?>