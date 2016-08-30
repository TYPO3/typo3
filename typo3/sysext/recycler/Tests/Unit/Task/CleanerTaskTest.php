<?php
namespace TYPO3\CMS\Recycler\Tests\Unit\Task;

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

use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Recycler\Task\CleanerTask;

/**
 * Testcase
 */
class CleanerTaskTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CleanerTask
     */
    protected $subject = null;

    /**
     * sets up an instance of \TYPO3\CMS\Recycler\Task\CleanerTask
     */
    protected function setUp()
    {
        $this->subject = $this->getMock(CleanerTask::class, ['dummy'], [], '', false);
    }

    /**
     * @test
     */
    public function getPeriodCanBeSet()
    {
        $period = 14;
        $this->subject->setPeriod($period);

        $this->assertEquals($period, $this->subject->getPeriod());
    }

    /**
     * @test
     */
    public function getTcaTablesCanBeSet()
    {
        $tables = ['pages', 'tt_content'];
        $this->subject->setTcaTables($tables);

        $this->assertEquals($tables, $this->subject->getTcaTables());
    }

    /**
     * @test
     */
    public function taskBuildsCorrectQuery()
    {
        $GLOBALS['TCA']['pages']['ctrl']['delete'] = 'deleted';
        $GLOBALS['TCA']['pages']['ctrl']['tstamp'] = 'tstamp';

        /** @var \PHPUnit_Framework_MockObject_MockObject|CleanerTask $subject */
        $subject = $this->getMock(CleanerTask::class, ['getPeriodAsTimestamp'], [], '', false);

        $tables = ['pages'];
        $subject->setTcaTables($tables);

        $period = 14;
        $subject->setPeriod($period);
        $periodAsTimestamp = strtotime('-' . $period . ' days');
        $subject->expects($this->once())->method('getPeriodAsTimestamp')->willReturn($periodAsTimestamp);

        $dbMock = $this->getMock(DatabaseConnection::class);
        $dbMock->expects($this->once())
            ->method('exec_DELETEquery')
            ->with($this->equalTo('pages'), $this->equalTo('deleted = 1 AND tstamp < ' . $periodAsTimestamp));

        $dbMock->expects($this->once())
            ->method('sql_error')
            ->will($this->returnValue(''));

        $subject->setDatabaseConnection($dbMock);

        $this->assertTrue($subject->execute());
    }

    /**
     * @test
     */
    public function taskFailsOnError()
    {
        $GLOBALS['TCA']['pages']['ctrl']['delete'] = 'deleted';
        $GLOBALS['TCA']['pages']['ctrl']['tstamp'] = 'tstamp';

        $tables = ['pages'];
        $this->subject->setTcaTables($tables);

        $period = 14;
        $this->subject->setPeriod($period);

        $dbMock = $this->getMock(DatabaseConnection::class);
        $dbMock->expects($this->once())
            ->method('sql_error')
            ->willReturn(1049);

        $this->subject->setDatabaseConnection($dbMock);

        $this->assertFalse($this->subject->execute());
    }
}
