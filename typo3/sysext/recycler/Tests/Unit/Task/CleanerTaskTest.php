<?php

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

namespace TYPO3\CMS\Recycler\Tests\Unit\Task;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Statement;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DefaultRestrictionContainer;
use TYPO3\CMS\Core\Tests\Unit\Database\Mocks\MockPlatform;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Recycler\Task\CleanerTask;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase
 */
class CleanerTaskTest extends UnitTestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|CleanerTask
     */
    protected $subject;

    /**
     * sets up an instance of \TYPO3\CMS\Recycler\Task\CleanerTask
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->getMockBuilder(CleanerTask::class)
            ->setMethods(['dummy'])
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

        self::assertEquals($period, $this->subject->getPeriod());
    }

    /**
     * @test
     */
    public function getTcaTablesCanBeSet()
    {
        $tables = ['pages', 'tt_content'];
        $this->subject->setTcaTables($tables);

        self::assertEquals($tables, $this->subject->getTcaTables());
    }

    /**
     * @test
     */
    public function taskBuildsCorrectQuery()
    {
        $GLOBALS['TCA']['pages']['ctrl']['delete'] = 'deleted';
        $GLOBALS['TCA']['pages']['ctrl']['tstamp'] = 'tstamp';

        /** @var \PHPUnit\Framework\MockObject\MockObject|CleanerTask $subject */
        $subject = $this->getMockBuilder(CleanerTask::class)
            ->setMethods(['getPeriodAsTimestamp'])
            ->disableOriginalConstructor()
            ->getMock();
        $subject->setTcaTables(['pages']);
        $subject->expects(self::once())->method('getPeriodAsTimestamp')->willReturn(400);

        /** @var Connection|ObjectProphecy $connection */
        $connection = $this->prophesize(Connection::class);
        $connection->getDatabasePlatform()->willReturn(new MockPlatform());
        $connection->getExpressionBuilder()->willReturn(new ExpressionBuilder($connection->reveal()));
        $connection->quoteIdentifier(Argument::cetera())->willReturnArgument(0);

        // TODO: This should rather be a functional test if we need a query builder
        // or we should clean up the code itself to not need to mock internal behavior here

        $statementProphet = $this->prophesize(Statement::class);

        $restrictionProphet = $this->prophesize(DefaultRestrictionContainer::class);
        $restrictionProphet->removeAll()->willReturn($restrictionProphet->reveal());

        $queryBuilderProphet = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphet->expr()->willReturn(
            GeneralUtility::makeInstance(ExpressionBuilder::class, $connection->reveal())
        );
        $queryBuilderProphet->getRestrictions()->willReturn($restrictionProphet->reveal());
        $queryBuilderProphet->createNamedParameter(Argument::cetera())->willReturnArgument(0);
        $queryBuilderProphet->delete(Argument::cetera())->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->where(Argument::cetera())->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->execute()->willReturn($statementProphet->reveal());

        $connectionPool = $this->prophesize(ConnectionPool::class);
        $connectionPool->getQueryBuilderForTable('pages')->willReturn($queryBuilderProphet->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPool->reveal());

        self::assertTrue($subject->execute());
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
            ->willThrow(new DBALException('testing', 1476122315));

        self::assertFalse($this->subject->execute());
    }
}
