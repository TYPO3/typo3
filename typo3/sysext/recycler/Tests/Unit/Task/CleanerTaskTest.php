<?php
namespace TYPO3\CMS\Recycler\Tests\Unit\Task;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Philipp Bergsmann <p.bergsmann@opendo.at>
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
 * Testcase
 */
class CleanerTaskTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\\Recycler\Task\CleanerTask
	 */
	protected $fixture = NULL;

	/**
	 * sets up an instance of \TYPO3\CMS\Recycler\Task\CleanerTask
	 */
	public function setUp() {
		$this->fixture = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Recycler\\Task\\CleanerTask');
	}

	public function tearDown() {
		unset($this->fixture);
	}

	/**
	 * Tests if the period is settable and getable by external functions
	 *
	 * @test
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
		$tables = array('pages', 'tt_content');

		$this->fixture->setTcaTables($tables);

		$this->assertEquals($tables, $this->fixture->getTcaTables());
	}

	/**
	 * @test
	 */
	public function taskBuildsCorrectQuery() {
		$tables = array('pages');
		$this->fixture->setTcaTables($tables);

		$period = 14;
		$this->fixture->setPeriod($period);

		$dbMock = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection');
		$dbMock->expects($this->once())
			   ->method('exec_DELETEquery')
			   ->with($this->equalTo('pages'), $this->equalTo('deleted = 1 AND tstamp < ' . strtotime('-' . $period . ' days')));

		$dbMock->expects($this->once())
			   ->method('sql_error')
			   ->will($this->returnValue(''));

		$this->fixture->setDatabaseConnection($dbMock);

		$this->assertTrue($this->fixture->execute());
	}

	/**
	 * @test
	 */
	public function taskFailsOnError() {
		$tables = array('pages');
		$this->fixture->setTcaTables($tables);

		$period = 14;
		$this->fixture->setPeriod($period);

		$dbMock = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection');
		$dbMock->expects($this->once())
			   ->method('sql_error')
			   ->will($this->returnValue(1049));

		$this->fixture->setDatabaseConnection($dbMock);

		$this->assertFalse($this->fixture->execute());
	}
}

?>