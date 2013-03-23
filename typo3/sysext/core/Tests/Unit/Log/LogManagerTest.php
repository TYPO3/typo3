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

/**
 * Testcase for \TYPO3\CMS\Core\Log\LogManager.
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @author Steffen Gebert <steffen.gebert@typo3.org>
 */
class LogManagerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Log\LogManager
	 */
	protected $logManagerInstance = NULL;

	public function setUp() {
		$this->logManagerInstance = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogManager');
	}

	public function tearDown() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogManager')->reset();
	}

	/**
	 * @test
	 */
	public function logManagerReturnsLoggerWhenRequestedWithGetLogger() {
		$this->assertInstanceOf('TYPO3\\CMS\\Core\\Log\\Logger', $this->logManagerInstance->getLogger('test'));
	}

	/**
	 * @test
	 */
	public function logManagerTurnsUnderScoreStyleLoggerNamesIntoDotStyleLoggerNames() {
		$this->assertSame('test.a.b', $this->logManagerInstance->getLogger('test_a_b')->getName());
	}

	/**
	 * @test
	 */
	public function logManagerTurnsNamespaceStyleLoggerNamesIntoDotStyleLoggerNames() {
		$this->assertSame('test.a.b', $this->logManagerInstance->getLogger('test\\a\\b')->getName());
	}

	/**
	 * @test
	 */
	public function managerReturnsSameLoggerOnRepeatedRequest() {
		$loggerName = uniqid('test.core.log');
		$this->logManagerInstance->registerLogger($loggerName);
		$logger1 = $this->logManagerInstance->getLogger($loggerName);
		$logger2 = $this->logManagerInstance->getLogger($loggerName);
		$this->assertSame($logger1, $logger2);
	}

	/**
	 * @test
	 */
	public function configuresLoggerWithConfiguredWriter() {
		$component = 'test';
		$writer = 'TYPO3\\CMS\\Core\\Log\\Writer\\NullWriter';
		$level = \TYPO3\CMS\Core\Log\LogLevel::DEBUG;
		$GLOBALS['TYPO3_CONF_VARS']['LOG'][$component]['writerConfiguration'] = array(
			$level => array(
				$writer => array()
			)
		);
		/** @var $logger \TYPO3\CMS\Core\Log\Logger */
		$logger = $this->logManagerInstance->getLogger($component);
		$writers = $logger->getWriters();
		$this->assertInstanceOf($writer, $writers[$level][0]);
	}

	/**
	 * @test
	 */
	public function configuresLoggerWithConfiguredProcessor() {
		$component = 'test';
		$processor = 'TYPO3\\CMS\\Core\\Log\\Processor\\NullProcessor';
		$level = \TYPO3\CMS\Core\Log\LogLevel::DEBUG;
		$GLOBALS['TYPO3_CONF_VARS']['LOG'][$component]['processorConfiguration'] = array(
			$level => array(
				$processor => array()
			)
		);
		/** @var $logger \TYPO3\CMS\Core\Log\Logger */
		$logger = $this->logManagerInstance->getLogger($component);
		$processors = $logger->getProcessors();
		$this->assertInstanceOf($processor, $processors[$level][0]);
	}

}

?>