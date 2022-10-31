<?php

declare(strict_types=1);

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

use Doctrine\DBAL\Result;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Resource\AbstractRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class AbstractRepositoryTest extends UnitTestCase
{
    protected function createDatabaseMock(): QueryBuilder&MockObject
    {
        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->method('quoteIdentifier')->with(self::anything())->willReturnArgument(0);

        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $queryBuilderMock->method('expr')->willReturn(
            GeneralUtility::makeInstance(ExpressionBuilder::class, $connectionMock)
        );

        $connectionPoolMock = $this->createMock(ConnectionPool::class);
        $connectionPoolMock->method('getQueryBuilderForTable')->with(self::anything())->willReturn($queryBuilderMock);
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolMock);

        return $queryBuilderMock;
    }

    /**
     * @test
     */
    public function findByUidFailsIfUidIsString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1316779798);
        $subject = $this->getMockForAbstractClass(AbstractRepository::class, [], '', false);
        $subject->findByUid('asdf');
    }

    /**
     * @test
     */
    public function findByUidAcceptsNumericUidInString(): void
    {
        $statementMock = $this->createMock(Result::class);
        $statementMock->expects(self::once())->method('fetchAssociative')->willReturn(['uid' => 123]);

        $queryBuilderMock = $this->createDatabaseMock();
        $queryBuilderMock->expects(self::once())->method('select')->with('*')->willReturn($queryBuilderMock);
        $queryBuilderMock->expects(self::once())->method('from')->with('')->willReturn($queryBuilderMock);
        $queryBuilderMock->expects(self::once())->method('where')->with(self::anything())->willReturn($queryBuilderMock);
        $queryBuilderMock->method('createNamedParameter')->with(self::anything())->willReturnArgument(0);
        $queryBuilderMock->expects(self::once())->method('executeQuery')->willReturn($statementMock);

        $subject = $this->getMockForAbstractClass(AbstractRepository::class, [], '', false);
        $subject->findByUid('123');
    }
}
