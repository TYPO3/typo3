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

use Prophecy\Argument;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case
 */
class DatabaseWriterTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @test
     */
    public function getTableReturnsPreviouslySetTable()
    {
        $logTable = $this->getUniqueId('logtable_');
        /** @var \TYPO3\CMS\Core\Log\Writer\DatabaseWriter|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMockBuilder(\TYPO3\CMS\Core\Log\Writer\DatabaseWriter::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $subject->setLogTable($logTable);
        $this->assertSame($logTable, $subject->getLogTable());
    }

    /**
     * @test
     */
    public function writeLogInsertsToSpecifiedTable()
    {
        $logTable = $this->getUniqueId('logtable_');

        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionPoolProphecy = $this->prophesize(ConnectionPool::class);
        $connectionPoolProphecy->getConnectionForTable(Argument::cetera())->willReturn($connectionProphecy->reveal());

        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphecy->reveal());
        $logRecordMock = $this->createMock(\TYPO3\CMS\Core\Log\LogRecord::class);
        $subject = $this->getMockBuilder(\TYPO3\CMS\Core\Log\Writer\DatabaseWriter::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $subject->setLogTable($logTable);

        // $logTable should end up as first insert argument
        $connectionProphecy->insert($logTable, Argument::cetera())->willReturn(1);

        $subject->writeLog($logRecordMock);
    }
}
