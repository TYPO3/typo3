<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Philipp Bergsmann <p.bergsmann@opendo.at>
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
 * Testcase for class "tx_recycler_tasks_CleanerTask"
 *
 * @author Philipp Bergsmann <p.bergsmann@opendo.at>
 * @package TYPO3
 * @subpackage tx_recycler
 */
class tx_recycler_tasks_CleanerTaskTest extends Tx_Phpunit_TestCase {

	/**
	 * @var tx_recycler_tasks_CleanerTask
	 */
	protected $fixture = NULL;

	/**
	 * sets up an instance of tx_recycler_tasks_CleanerTask
	 */
	public function setUp() {
		$this->fixture = t3lib_div::makeInstance('tx_recycler_tasks_CleanerTask');
	}

	public function tearDown() {
		unset($this->fixture);
	}

	/**
	 * Tests if the period is settable and getable by external functions
	 *
	 * @test
	 * @return void
	 */
	public function periodIsSetAndGetable() {
		$period = 14;

		$this->fixture->setPeriod($period);

		$this->assertEquals($period, $this->fixture->getPeriod());
	}

	/**
	 * @test
	 */
	public function tcaTablesAreSetAndGetable() {
		$tables = array('pages','tt_content');

		$this->fixture->setTCATables($tables);

		$this->assertEquals($tables, $this->fixture->getTCATables());
	}

	/**
	 * @test
	 */
	public function dbIsInjectable() {
		$dbMock = $this->getMock('t3lib_db');

		$this->fixture->setDB($dbMock);

		$this->assertEquals($dbMock, $this->fixture->getDB());
	}

	/**
	 * @test
	 */
	public function taskBuildsCorrectQuery() {
		$tables = array('pages');
		$this->fixture->setTCATables($tables);

		$period = 14;
		$this->fixture->setPeriod($period);

		$dbMock = $this->getMock('t3lib_db');
		$dbMock->expects($this->once())
			   ->method('exec_DELETEquery')
			   ->with($this->equalTo('pages'),$this->equalTo('deleted = 1 AND tstamp < ' . strtotime('-' . $period . ' days')));

		$dbMock->expects($this->once())
			   ->method('sql_error')
			   ->will($this->returnValue(''));

		$this->fixture->setDB($dbMock);

		$this->assertTrue($this->fixture->execute());
	}

	/**
	 * @test
	 */
	public function taskFailsOnError() {
		$tables = array('pages');
		$this->fixture->setTCATables($tables);

		$period = 14;
		$this->fixture->setPeriod($period);

		$dbMock = $this->getMock('t3lib_db');

		$dbMock->expects($this->once())
			   ->method('sql_error')
			   ->will($this->returnValue(1049));

		$this->fixture->setDB($dbMock);

		$this->assertFalse($this->fixture->execute());
	}
}

?>