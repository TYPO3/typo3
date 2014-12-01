<?php
namespace TYPO3\CMS\Core\Tests\Unit\Log\Writer;

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
 * @author Steffen Gebert <steffen.gebert@typo3.org>
 */
class DatabaseWriterTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function getTableReturnsPreviouslySetTable() {
		$logTable = uniqid('logtable_');
		/** @var \TYPO3\CMS\Core\Log\Writer\DatabaseWriter|\PHPUnit_Framework_MockObject_MockObject $subject */
		$subject = $this->getMock(\TYPO3\CMS\Core\Log\Writer\DatabaseWriter::class, array('dummy'), array(), '', FALSE);
		$subject->setLogTable($logTable);
		$this->assertSame($logTable, $subject->getLogTable());
	}

	/**
	 * @test
	 * @expectedException \RuntimeException
	 */
	public function writeLogThrowsExceptionIfDatabaseInsertFailed() {
		$GLOBALS['TYPO3_DB'] = $this->getMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, array(), array(), '', FALSE);
		$GLOBALS['TYPO3_DB']->expects($this->once())->method('exec_INSERTquery')->will($this->returnValue(FALSE));
		/** @var \TYPO3\CMS\Core\Log\LogRecord|\PHPUnit_Framework_MockObject_MockObject $subject */
		$logRecordMock = $this->getMock(\TYPO3\CMS\Core\Log\LogRecord::class, array(), array(), '', FALSE);
		/** @var \TYPO3\CMS\Core\Log\Writer\DatabaseWriter|\PHPUnit_Framework_MockObject_MockObject $subject */
		$subject = $this->getMock(\TYPO3\CMS\Core\Log\Writer\DatabaseWriter::class, array('dummy'), array(), '', FALSE);
		$subject->writeLog($logRecordMock);
	}

	/**
	 * @test
	 */
	public function writeLogInsertsToSpecifiedTable() {
		$logTable = uniqid('logtable_');
		$GLOBALS['TYPO3_DB'] = $this->getMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, array(), array(), '', FALSE);
		$GLOBALS['TYPO3_DB']->expects($this->once())->method('exec_INSERTquery')->with($logTable, $this->anything());
		/** @var \TYPO3\CMS\Core\Log\LogRecord|\PHPUnit_Framework_MockObject_MockObject $subject */
		$logRecordMock = $this->getMock(\TYPO3\CMS\Core\Log\LogRecord::class, array(), array(), '', FALSE);
		/** @var \TYPO3\CMS\Core\Log\Writer\DatabaseWriter|\PHPUnit_Framework_MockObject_MockObject $subject */
		$subject = $this->getMock(\TYPO3\CMS\Core\Log\Writer\DatabaseWriter::class, array('dummy'), array(), '', FALSE);
		$subject->setLogTable($logTable);
		$subject->writeLog($logRecordMock);
	}

	/**
	 * @test
	 */
	public function writeLogInsertsLogRecordWithGivenProperties() {
		$logRecordData = array(
			'request_id' => uniqid('request_id'),
			'time_micro' => uniqid('time_micro'),
			'component' => uniqid('component'),
			'level' => uniqid('level'),
			'message' => uniqid('message'),
			'data' => '',
		);
		/** @var \TYPO3\CMS\Core\Log\LogRecord|\PHPUnit_Framework_MockObject_MockObject $subject */
		$logRecordMock = $this->getMock(\TYPO3\CMS\Core\Log\LogRecord::class, array(), array(), '', FALSE);
		$logRecordMock->expects($this->at(0))->method('offsetGet')->with('requestId')->will($this->returnValue($logRecordData['request_id']));
		$logRecordMock->expects($this->at(1))->method('offsetGet')->with('created')->will($this->returnValue($logRecordData['time_micro']));
		$logRecordMock->expects($this->at(2))->method('offsetGet')->with('component')->will($this->returnValue($logRecordData['component']));
		$logRecordMock->expects($this->at(3))->method('offsetGet')->with('level')->will($this->returnValue($logRecordData['level']));
		$logRecordMock->expects($this->at(4))->method('offsetGet')->with('message')->will($this->returnValue($logRecordData['message']));

		/** @var \TYPO3\CMS\Core\Log\Writer\DatabaseWriter|\PHPUnit_Framework_MockObject_MockObject $subject */
		$subject = $this->getMock(\TYPO3\CMS\Core\Log\Writer\DatabaseWriter::class, array('dummy'), array(), '', FALSE);

		$GLOBALS['TYPO3_DB'] = $this->getMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, array(), array(), '', FALSE);
		$GLOBALS['TYPO3_DB']->expects($this->once())->method('exec_INSERTquery')->with($this->anything(), $logRecordData);

		$subject->writeLog($logRecordMock);
	}
}