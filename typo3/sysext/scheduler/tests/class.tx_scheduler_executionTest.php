<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Marcus Krause <marcus#exp2010@t3sec.info>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Testcase for class "tx_scheduler_Execution"
 *
 * @package TYPO3
 * @subpackage tx_scheduler
 *
 * @author Marcus Krause <marcus#exp2010@t3sec.info>
 */
class tx_scheduler_ExecutionTest extends tx_phpunit_testcase {

	/**
	 * @const	integer	timestamp of 1.1.2010 0:00 (Friday)
	 */
	const TIMESTAMP = 1262300400;

	/**
	 * Keeps instance of object to test.
	 *
	 * @var	tx_scheduler_Execution
	 */
	protected $fixture = NULL;

	public function setUp() {
		$this->fixture = t3lib_div::makeInstance('tx_scheduler_Execution');
	}

	/**
	 * Tests if constructor of "tx_scheduler_Execution" copies current timestamp
	 * from global EXEC_TIME.
	 *
	 * @test
	 */
	public function constructorCopiesCurrentTimestamp() {
		$actualTimestamp = $this->fixture->getCurrentTimestamp();

		$this->assertNotNull($actualTimestamp);
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_INT, $actualTimestamp);
		$this->assertEquals($GLOBALS['EXEC_TIME'], $actualTimestamp);
	}

	/**
	 * Tests if constructor of "tx_scheduler_Execution" injects a timestamp correctly
	 *
	 * @test
	 */
	public function constructorInjectionOfOwnTimestamp() {
		$this->fixture = t3lib_div::makeInstance('tx_scheduler_Execution', self::TIMESTAMP);
		$actualTimestamp = $this->fixture->getCurrentTimestamp();

		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_INT, $actualTimestamp);
		$this->assertEquals(self::TIMESTAMP, $actualTimestamp);
	}

	/**
	 * Tests if getter and setter work correctly on execution timestamp.
	 *
	 * @test
	 */
	public function methodsAllowTimestampModififcation() {
		$this->assertNotEquals(self::TIMESTAMP, $this->fixture->getCurrentTimestamp());
		$this->fixture->setCurrentTimestamp(self::TIMESTAMP);
		$this->assertEquals(self::TIMESTAMP, $this->fixture->getCurrentTimestamp());
	}
}
?>