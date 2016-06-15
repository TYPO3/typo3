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

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Tests\Unit\Database\Mocks\MockPlatform;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
        $this->subject = $this->getMockBuilder(CleanerTask::class)
            ->setMethods(array('dummy'))
            ->disableOriginalConstructor()
            ->getMock();
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
        $tables = array('pages', 'tt_content');
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
        $subject = $this->getMockBuilder(CleanerTask::class)
            ->setMethods(array('getPeriodAsTimestamp'))
            ->disableOriginalConstructor()
            ->getMock();
        $subject->setTcaTables(['pages']);
        $subject->expects($this->once())->method('getPeriodAsTimestamp')->willReturn(400);

        /** @var Connection|ObjectProphecy $connection */
        $connection = $this->prophesize(Connection::class);
        $connection->getDatabasePlatform()->willReturn(new MockPlatform());
        $connection->getExpressionBuilder()->willReturn(new ExpressionBuilder($connection->reveal()));
        $connection->quoteIdentifier(Argument::cetera())->willReturnArgument(0);

        // TODO: This should rather be a functional test if we need a query builder
        // or we should clean up the code itself to not need to mock internal behavior here
        $queryBuilder = new QueryBuilder(
            $connection->reveal(),
            null,
            new \Doctrine\DBAL\Query\QueryBuilder($connection->reveal())
        );

        $connectionPool = $this->prophesize(ConnectionPool::class);
        $connectionPool->getQueryBuilderForTable('pages')->willReturn($queryBuilder);
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPool->reveal());

        $connection->executeUpdate('DELETE FROM pages WHERE (deleted = 1) AND (tstamp < 400)', Argument::cetera())
            ->shouldBeCalled()
            ->willReturn(1);
        $this->assertTrue($subject->execute());
    }

    /**
     * @test
     */
    public function taskFailsOnError()
    {
        $GLOBALS['TCA']['pages']['ctrl']['delete'] = 'deleted';
        $GLOBALS['TCA']['pages']['ctrl']['tstamp'] = 'tstamp';

        $this->subject->setTcaTables(['pages']);

        /** @var Connection|ObjectProphecy $connection */
        $connection = $this->prophesize(Connection::class);
        $connection->getDatabasePlatform()->willReturn(new MockPlatform());
        $connection->getExpressionBuilder()->willReturn(new ExpressionBuilder($connection->reveal()));
        $connection->quoteIdentifier(Argument::cetera())->willReturnArgument(0);

        // TODO: This should rather be a functional test if we need a query builder
        // or we should clean up the code itself to not need to mock internal behavior here
        $queryBuilder = new QueryBuilder(
            $connection->reveal(),
            null,
            new \Doctrine\DBAL\Query\QueryBuilder($connection->reveal())
        );

        $connectionPool = $this->prophesize(ConnectionPool::class);
        $connectionPool->getQueryBuilderForTable('pages')->willReturn($queryBuilder);
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPool->reveal());

        $connection->executeUpdate(Argument::cetera())
            ->shouldBeCalled()
            ->willThrow(new \Doctrine\DBAL\DBALException());

        $this->assertFalse($this->subject->execute());
    }
}
