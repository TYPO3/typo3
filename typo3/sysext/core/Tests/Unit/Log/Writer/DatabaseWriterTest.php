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

use TYPO3\CMS\Core\Log\Writer\DatabaseWriter;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case
 *
 * @author Steffen Gebert <steffen.gebert@typo3.org>
 */
class DatabaseWriterTest extends UnitTestCase {

	/**
	 * @test
	 */
	public function getTableReturnsPreviouslySetTable() {
		$logTable = $this->getUniqueId('logtable_');
		/** @var DatabaseWriter|\PHPUnit_Framework_MockObject_MockObject $subject */
		$subject = $this->getMock('TYPO3\\CMS\Core\Log\\Writer\\DatabaseWriter', array('dummy'), array(), '', FALSE);
		$subject->setLogTable($logTable);
		$this->assertSame($logTable, $subject->getLogTable());
	}

	/**
	 * @test
	 * @expectedException \RuntimeException
	 */
	public function writeLogThrowsExceptionIfDatabaseInsertFailed() {
		$GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array(), array(), '', FALSE);
		$GLOBALS['TYPO3_DB']->expects($this->once())->method('exec_INSERTquery')->will($this->returnValue(FALSE));
		/** @var \TYPO3\CMS\Core\Log\LogRecord|\PHPUnit_Framework_MockObject_MockObject $logRecordMock */
		$logRecordMock = $this->getMock('TYPO3\\CMS\\Core\\Log\\LogRecord', array(), array(), '', FALSE);
		/** @var DatabaseWriter|\PHPUnit_Framework_MockObject_MockObject $subject */
		$subject = $this->getMock('TYPO3\\CMS\Core\Log\\Writer\\DatabaseWriter', array('dummy'), array(), '', FALSE);
		$subject->writeLog($logRecordMock);
	}

	/**
	 * @test
	 */
	public function writeLogInsertsToSpecifiedTable() {
		$logTable = $this->getUniqueId('logtable_');
		$GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array(), array(), '', FALSE);
		$GLOBALS['TYPO3_DB']->expects($this->once())->method('exec_INSERTquery')->with($logTable, $this->anything());
		/** @var \TYPO3\CMS\Core\Log\LogRecord|\PHPUnit_Framework_MockObject_MockObject $logRecordMock */
		$logRecordMock = $this->getMock('TYPO3\\CMS\\Core\\Log\\LogRecord', array(), array(), '', FALSE);
		/** @var DatabaseWriter|\PHPUnit_Framework_MockObject_MockObject $subject */
		$subject = $this->getMock('TYPO3\\CMS\Core\Log\\Writer\\DatabaseWriter', array('dummy'), array(), '', FALSE);
		$subject->setLogTable($logTable);
		$subject->writeLog($logRecordMock);
	}

	/**
	 * @test
	 */
	public function writeLogInsertsLogRecordWithGivenProperties() {
		$logRecordData = array(
			'request_id' => $this->getUniqueId('request_id'),
			'time_micro' => $this->getUniqueId('time_micro'),
			'component' => $this->getUniqueId('component'),
			'level' => $this->getUniqueId('level'),
			'message' => $this->getUniqueId('message'),
			'data' => '',
		);
		/** @var \TYPO3\CMS\Core\Log\LogRecord|\PHPUnit_Framework_MockObject_MockObject $logRecordFixture */
		$logRecordFixture = $this->getMock('TYPO3\\CMS\\Core\\Log\\LogRecord', array(), array(), '', FALSE);
		$logRecordFixture->expects($this->any())->method('getRequestId')->will($this->returnValue($logRecordData['request_id']));
		$logRecordFixture->expects($this->any())->method('getCreated')->will($this->returnValue($logRecordData['time_micro']));
		$logRecordFixture->expects($this->any())->method('getComponent')->will($this->returnValue($logRecordData['component']));
		$logRecordFixture->expects($this->any())->method('getLevel')->will($this->returnValue($logRecordData['level']));
		$logRecordFixture->expects($this->any())->method('getMessage')->will($this->returnValue($logRecordData['message']));
		$logRecordFixture->expects($this->any())->method('getData')->will($this->returnValue(array()));

		/** @var DatabaseWriter|\PHPUnit_Framework_MockObject_MockObject $subject */
		$subject = new DatabaseWriter();

		$GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array(), array(), '', FALSE);
		$GLOBALS['TYPO3_DB']->expects($this->once())->method('exec_INSERTquery')->with($this->anything(), $logRecordData);

		$subject->writeLog($logRecordFixture);
	}
}
