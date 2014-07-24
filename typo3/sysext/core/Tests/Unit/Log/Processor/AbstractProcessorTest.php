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
 * Test case
 *
 * @author Steffen Müller <typo3@t3node.com>
 */
class AbstractProcessorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function processorRefusesInvalidConfigurationOptions() {
		$invalidConfiguration = array(
			'foo' => 'bar'
		);
		$processor = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Tests\\Unit\\Log\\Fixtures\\ProcessorFixture', $invalidConfiguration);
	}

	/**
	 * @test
	 */
	public function loggerExecutesProcessors() {
		$logger = new \TYPO3\CMS\Core\Log\Logger('test.core.log');
		$writer = new \TYPO3\CMS\Core\Log\Writer\NullWriter();
		$level = \TYPO3\CMS\Core\Log\LogLevel::DEBUG;
		$logRecord = new \TYPO3\CMS\Core\Log\LogRecord('dummy', $level, 'message');
		$processor = $this->getMock('\\TYPO3\\CMS\\Core\\Log\\Processor\\ProcessorInterface', array('processLogRecord'));
		$processor->expects($this->once())->method('processLogRecord')->willReturn($logRecord);

		$logger->addWriter($level, $writer);
		$logger->addProcessor($level, $processor);
		$logger->warning('test');
	}
}
