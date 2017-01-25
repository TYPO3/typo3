<?php
namespace TYPO3\CMS\Core\Tests\Unit\Log\Writer;

/*
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
 */
class DatabaseWriterTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function getTableReturnsPreviouslySetTable()
    {
        $logTable = $this->getUniqueId('logtable_');
        /** @var \TYPO3\CMS\Core\Log\Writer\DatabaseWriter|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMock(\TYPO3\CMS\Core\Log\Writer\DatabaseWriter::class, ['dummy'], [], '', false);
        $subject->setLogTable($logTable);
        $this->assertSame($logTable, $subject->getLogTable());
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function writeLogThrowsExceptionIfDatabaseInsertFailed()
    {
        $GLOBALS['TYPO3_DB'] = $this->getMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, [], [], '', false);
        $GLOBALS['TYPO3_DB']->expects($this->once())->method('exec_INSERTquery')->will($this->returnValue(false));
        /** @var \TYPO3\CMS\Core\Log\LogRecord|\PHPUnit_Framework_MockObject_MockObject $subject */
        $logRecordMock = $this->getMock(\TYPO3\CMS\Core\Log\LogRecord::class, [], [], '', false);
        /** @var \TYPO3\CMS\Core\Log\Writer\DatabaseWriter|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMock(\TYPO3\CMS\Core\Log\Writer\DatabaseWriter::class, ['dummy'], [], '', false);
        $subject->writeLog($logRecordMock);
    }

    /**
     * @test
     */
    public function writeLogInsertsToSpecifiedTable()
    {
        $logTable = $this->getUniqueId('logtable_');
        $GLOBALS['TYPO3_DB'] = $this->getMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, [], [], '', false);
        $GLOBALS['TYPO3_DB']->expects($this->once())->method('exec_INSERTquery')->with($logTable, $this->anything());
        /** @var \TYPO3\CMS\Core\Log\LogRecord|\PHPUnit_Framework_MockObject_MockObject $subject */
        $logRecordMock = $this->getMock(\TYPO3\CMS\Core\Log\LogRecord::class, [], [], '', false);
        /** @var \TYPO3\CMS\Core\Log\Writer\DatabaseWriter|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMock(\TYPO3\CMS\Core\Log\Writer\DatabaseWriter::class, ['dummy'], [], '', false);
        $subject->setLogTable($logTable);
        $subject->writeLog($logRecordMock);
    }

    /**
     * @test
     */
    public function writeLogInsertsLogRecordWithGivenProperties()
    {
        $logRecordData = [
            'request_id' => $this->getUniqueId('request_id'),
            'time_micro' => $this->getUniqueId('time_micro'),
            'component' => $this->getUniqueId('component'),
            'level' => $this->getUniqueId('level'),
            'message' => $this->getUniqueId('message'),
            'data' => '',
        ];
        /** @var \TYPO3\CMS\Core\Log\LogRecord|\PHPUnit_Framework_MockObject_MockObject $subject */
        $logRecordFixture = $this->getMock(\TYPO3\CMS\Core\Log\LogRecord::class, [], [], '', false);
        $logRecordFixture->expects($this->any())->method('getRequestId')->will($this->returnValue($logRecordData['request_id']));
        $logRecordFixture->expects($this->any())->method('getCreated')->will($this->returnValue($logRecordData['time_micro']));
        $logRecordFixture->expects($this->any())->method('getComponent')->will($this->returnValue($logRecordData['component']));
        $logRecordFixture->expects($this->any())->method('getLevel')->will($this->returnValue($logRecordData['level']));
        $logRecordFixture->expects($this->any())->method('getMessage')->will($this->returnValue($logRecordData['message']));
        $logRecordFixture->expects($this->any())->method('getData')->will($this->returnValue([]));

        /** @var \TYPO3\CMS\Core\Log\Writer\DatabaseWriter|\PHPUnit_Framework_MockObject_MockObject $subject */
        //$subject = $this->getMock(\TYPO3\CMS\Core\Log\Writer\DatabaseWriter::class, array('dummy'), array(), '', FALSE);
        $subject = new \TYPO3\CMS\Core\Log\Writer\DatabaseWriter();

        $GLOBALS['TYPO3_DB'] = $this->getMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, [], [], '', false);
        $GLOBALS['TYPO3_DB']->expects($this->once())->method('exec_INSERTquery')->with($this->anything(), $logRecordData);

        $subject->writeLog($logRecordFixture);
    }
}
