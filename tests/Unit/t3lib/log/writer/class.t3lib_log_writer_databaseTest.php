<?php
/***************************************************************
 * Copyright notice
 *
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
 * Testcase for t3lib_log_writer_Database
 *
 * @author Steffen Gebert <steffen.gebert@typo3.org>
 */
class t3lib_log_writer_DatabaseTest extends tx_phpunit_testcase {

	/**
	 * Backup and restore of the $GLOBALS array.
	 *
	 * @var boolean
	 */
	protected $backupGlobalsArray = array();

	/**
	 * Mock object of t3lib_db
	 *
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	private $databaseStub;

	/**
	 * Create a new database mock object for every test
	 * and backup the original global database object.
	 *
	 * @return void
	 */
	public function setUp() {
		$this->backupGlobalsArray['TYPO3_DB'] = $GLOBALS['TYPO3_DB'];
		$this->databaseStub = $this->setUpAndReturnDatabaseStub();
	}

	/**
	 * Restore global database object.
	 *
	 * @return void
	 */
	protected function tearDown() {
		$GLOBALS['TYPO3_DB'] = $this->backupGlobalsArray['TYPO3_DB'];
	}

	//////////////////////
	// Utility functions
	//////////////////////

	/**
	 * Set up the stub to be able to get the result of the prepared statement.
	 *
	 * @return PHPUnit_Framework_MockObject_MockObject
	 */
	private function setUpAndReturnDatabaseStub() {
		$databaseLink = $GLOBALS['TYPO3_DB']->link;

		$GLOBALS['TYPO3_DB'] = $this->getMock('t3lib_DB', array('exec_INSERTquery'), array(), '', FALSE, FALSE);
		$GLOBALS['TYPO3_DB']->link = $databaseLink;

		return $GLOBALS['TYPO3_DB'];
	}

	/**
	 * Creates a test logger
	 *
	 * @return t3lib_log_Logger
	 */
	protected function createLogger() {
		$loggerName = uniqid('test.core.datbaseWriter');

		/** @var t3lib_log_Logger $logger */
		$logger = t3lib_div::makeInstance('t3lib_log_LogManager')->getLogger($loggerName);

		return $logger;
	}

	/**
	 * Creates a database writer
	 *
	 * @return t3lib_log_writer_Database
	 */
	protected function createWriter() {
		/** @var t3lib_log_writer_Database $writer */
		$writer = t3lib_div::makeInstance('t3lib_log_writer_Database');

		return $writer;
	}

	/**
	 * @test
	 */
	public function setLogTableSetsLogTable() {
		$logTable = uniqid('logtable_');

		$this->assertSame(
			$logTable,
			$this->createWriter()->setLogTable($logTable)->getLogTable()
		);
	}

	/**
	 * @return array
	 */
	public function writerLogsToDatabaseDataProvider() {
		$simpleRecord = t3lib_div::makeInstance('t3lib_log_Record',
			'test.core.databaseWriter.simpleRecord',
			t3lib_log_Level::ALERT,
			'test entry'
		);

		$recordWithData = t3lib_div::makeInstance('t3lib_log_Record',
			'test.core.databaseWriter.recordWithData',
			t3lib_log_Level::ALERT,
			'test entry with data',
			array('foo' => array('bar' => 'baz'))
		);

		return array(
			'simple record'    => array($simpleRecord),
			'record with data' => array($recordWithData),
		);
	}

	/**
	 * @test
	 * @param t3lib_log_Record $record Record Test Data
	 * @dataProvider writerLogsToDatabaseDataProvider
	 */
	public function writerLogsToDatabase(t3lib_log_Record $record) {
		$logger = $this->createLogger();
		$databaseWriter = $this->createWriter();
		$logger->addWriter(t3lib_log_Level::NOTICE, $databaseWriter);

		$this->databaseStub
			->expects($this->once())
			->method('exec_INSERTquery');

		$logger->log($record->getLevel(), $record->getMessage());

	}
}

?>