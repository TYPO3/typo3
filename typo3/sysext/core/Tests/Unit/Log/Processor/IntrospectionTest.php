<?php
namespace TYPO3\CMS\Core\Tests\Unit\Log\Processor;

/***************************************************************
 * Copyright notice
 *
 * (c) 2012-2013 Steffen Müller (typo3@t3node.com)
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

?>