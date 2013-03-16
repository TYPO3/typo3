<?php
namespace TYPO3\CMS\Belog\Tests\Unit\Domain\Model;

/***************************************************************
 * Copyright notice
 *
 * (c) 2012-2013 Oliver Klee <typo3-coding@oliverklee.de>
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
 * Testcase
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class LogEntryTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Belog\Domain\Model\LogEntry
	 */
	protected $fixture = NULL;

	public function setUp() {
		$this->fixture = new \TYPO3\CMS\Belog\Domain\Model\LogEntry();
	}

	public function tearDown() {
		unset($this->fixture);
	}

	/**
	 * @test
	 */
	public function getLogDataInitiallyReturnsEmptyArray() {
		$this->assertSame(array(), $this->fixture->getLogData());
	}

	/**
	 * @test
	 */
	public function getLogDataForEmptyStringLogDataReturnsEmptyArray() {
		$this->fixture->setLogData('');
		$this->assertSame(array(), $this->fixture->getLogData());
	}

	/**
	 * @test
	 */
	public function getLogDataForGarbageStringLogDataReturnsEmptyArray() {
		$this->fixture->setLogData('foo bar');
		$this->assertSame(array(), $this->fixture->getLogData());
	}

	/**
	 * @test
	 */
	public function getLogDataForSerializedArrayReturnsThatArray() {
		$logData = array('foo', 'bar');
		$this->fixture->setLogData(serialize($logData));
		$this->assertSame($logData, $this->fixture->getLogData());
	}

	/**
	 * @test
	 */
	public function getLogDataForSerializedObjectReturnsEmptyArray() {
		$this->fixture->setLogData(new \stdClass());
		$this->assertSame(array(), $this->fixture->getLogData());
	}

}

?>