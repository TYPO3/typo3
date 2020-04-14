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

namespace TYPO3\CMS\Core\Tests\Unit\Resource\Repository;

use Doctrine\DBAL\Driver\Statement;
use Prophecy\Argument;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Resource\AbstractRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class AbstractRepositoryTest extends UnitTestCase
{
    /**
     * @var AbstractRepository
     */
    protected $subject;

    protected function createDatabaseMock()
    {
        $connectionProphet = $this->prophesize(Connection::class);
        $connectionProphet->quoteIdentifier(Argument::cetera())->willReturnArgument(0);

        $queryBuilderProphet = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphet->expr()->willReturn(
            GeneralUtility::makeInstance(ExpressionBuilder::class, $connectionProphet->reveal())
        );

        $connectionPoolProphet = $this->prophesize(ConnectionPool::class);
        $connectionPoolProphet->getQueryBuilderForTable(Argument::cetera())->willReturn($queryBuilderProphet->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphet->reveal());

        return $queryBuilderProphet;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->getMockForAbstractClass(AbstractRepository::class, [], '', false);
    }

    /**
     * @test
     */
    public function findByUidFailsIfUidIsString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1316779798);
        $this->subject->findByUid('asdf');
    }

    /**
     * @test
     */
    public function findByUidAcceptsNumericUidInString()
    {
        $statementProphet = $this->prophesize(Statement::class);
        $statementProphet->fetch()->shouldBeCalled()->willReturn(['uid' => 123]);

        $queryBuilderProphet = $this->createDatabaseMock();
        $queryBuilderProphet->select('*')->shouldBeCalled()->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->from('')->shouldBeCalled()->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->where(Argument::cetera())->shouldBeCalled()->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->createNamedParameter(Argument::cetera())->willReturnArgument(0);
        $queryBuilderProphet->execute()->shouldBeCalled()->willReturn($statementProphet->reveal());

        $this->subject->findByUid('123');
    }
}
