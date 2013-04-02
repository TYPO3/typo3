<?php
namespace TYPO3\CMS\Core\Tests\Unit\Log\Writer;

/***************************************************************
 * Copyright notice
 *
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
 * Testcase for \TYPO3\CMS\Core\Log\Writer\DatabaseWriter
 *
 * @author Steffen Gebert <steffen.gebert@typo3.org>
 */
class DatabaseTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	private $databaseStub;

	/**
	 * Create a new database mock object for every test
	 * and backup the original global database object.
	 *
	 * @return void
	 */
	public function setUp() {
		$this->databaseStub = $this->setUpAndReturnDatabaseStub();
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
		$databaseLink = $GLOBALS['TYPO3_DB']->getDatabaseHandle();
		$GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array('exec_INSERTquery'), array(), '', FALSE, FALSE);
		$GLOBALS['TYPO3_DB']->setDatabaseHandle($databaseLink);
		return $GLOBALS['TYPO3_DB'];
	}

	/**
	 * Creates a test logger
	 *
	 * @return \TYPO3\CMS\Core\Log\Logger
	 */
	protected function createLogger() {
		$loggerName = uniqid('test.core.datbaseWriter');
		/** @var \TYPO3\CMS\Core\Log\Logger $logger */
		$logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogManager')->getLogger($loggerName);
		return $logger;
	}

	/**
	 * Creates a database writer
	 *
	 * @return \TYPO3\CMS\Core\Log\Writer\DatabaseWriter
	 */
	protected function createWriter() {
		/** @var \TYPO3\CMS\Core\Log\Writer\DatabaseWriter $writer */
		$writer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\Writer\\DatabaseWriter');
		return $writer;
	}

	/**
	 * @test
	 */
	public function setLogTableSetsLogTable() {
		$logTable = uniqid('logtable_');
		$this->assertSame($logTable, $this->createWriter()->setLogTable($logTable)->getLogTable());
	}

	/**
	 * @return array
	 */
	public function writerLogsToDatabaseDataProvider() {
		$simpleRecord = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogRecord', 'test.core.databaseWriter.simpleRecord', \TYPO3\CMS\Core\Log\LogLevel::ALERT, 'test entry');
		$recordWithData = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogRecord', 'test.core.databaseWriter.recordWithData', \TYPO3\CMS\Core\Log\LogLevel::ALERT, 'test entry with data', array('foo' => array('bar' => 'baz')));
		return array(
			'simple record' => array($simpleRecord),
			'record with data' => array($recordWithData)
		);
	}

	/**
	 * @test
	 * @param \TYPO3\CMS\Core\Log\LogRecord $record Record Test Data
	 * @dataProvider writerLogsToDatabaseDataProvider
	 */
	public function writerLogsToDatabase(\TYPO3\CMS\Core\Log\LogRecord $record) {
		$logger = $this->createLogger();
		$databaseWriter = $this->createWriter();
		$logger->addWriter(\TYPO3\CMS\Core\Log\LogLevel::NOTICE, $databaseWriter);
		$this->databaseStub->expects($this->once())->method('exec_INSERTquery');
		$logger->log($record->getLevel(), $record->getMessage());
	}

}

?>