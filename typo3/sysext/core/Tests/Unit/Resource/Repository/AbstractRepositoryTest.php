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

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Tests\Unit\Resource\Repository\Fixtures\TestingRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AbstractRepositoryTest extends UnitTestCase
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

    #[Test]
    public function findByUidFailsIfUidIsString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1316779798);
        $subject = new TestingRepository();
        $subject->findByUid('asdf');
    }
}
