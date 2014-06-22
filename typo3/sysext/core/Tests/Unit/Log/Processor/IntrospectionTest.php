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
 * Testcase for the web introspection processor.
 *
 * @author Steffen Müller <typo3@t3node.com>
 */
class IntrospectionTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * Tests if debug_backtrace values are added to LogRecord
	 * The debug_backtrace in \TYPO3\CMS\Core\Log\Processor\IntrospectionProcessor will always process a phpunit class.
	 * The result is unpredictable values. So we only test if values are not empty/0
	 *
	 * @test
	 */
	public function introspectionProcessorAddsIntrospectionDataToLogRecord() {
		$logRecord = new \TYPO3\CMS\Core\Log\LogRecord('test.core.log', \TYPO3\CMS\Core\Log\LogLevel::DEBUG, 'test');
		$processor = new \TYPO3\CMS\Core\Log\Processor\IntrospectionProcessor();
		$logRecord = $processor->processLogRecord($logRecord);
		$this->assertNotEmpty($logRecord['data']['file'], 'Asserting that file name in debug_backtrace() is not empty');
		$this->assertGreaterThan(0, $logRecord['data']['line'], 'Asserting that line numer in debug_backtrace() is greater than 0');
		$this->assertNotEmpty($logRecord['data']['class'], 'Asserting that class in debug_backtrace() is not empty');
		$this->assertNotEmpty($logRecord['data']['function'], 'Asserting that function in debug_backtrace() is not empty');
	}

}
