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

namespace TYPO3\CMS\Core\Tests\Functional\Database\Query\QueryBuilder;

use Doctrine\DBAL\Query\UnionType;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class UnionClauseTest extends FunctionalTestCase
{
    #[Test]
    public function unionDistinctQueryWithAscendingOrderByUidReturnsExpectedResultSet(): void
    {
        $expectedRows = [
            ['uid' => 1, 'pid' => 0, 'doktype' => PageRepository::DOKTYPE_DEFAULT, 'title' => 'page 1'],
            ['uid' => 3, 'pid' => 0, 'doktype' => PageRepository::DOKTYPE_DEFAULT, 'title' => 'page 3'],
            ['uid' => 2, 'pid' => 0, 'doktype' => PageRepository::DOKTYPE_SYSFOLDER, 'title' => 'sysfolder 2'],
            ['uid' => 4, 'pid' => 0, 'doktype' => PageRepository::DOKTYPE_SYSFOLDER, 'title' => 'sysfolder 4'],
        ];
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/union-clause-simple.csv');
        $unionQueryBuilder = $this->getDefaultConnection()->createQueryBuilder();
        $standardPagesQueryBuilder = $this->getDefaultConnection()->createQueryBuilder();
        $standardPagesQueryBuilder
            ->select('uid', 'pid', 'doktype', 'title')
            ->from('pages')
            ->where(
                $unionQueryBuilder->expr()->eq(
                    'doktype',
                    $unionQueryBuilder->createNamedParameter(
                        PageRepository::DOKTYPE_DEFAULT,
                        Connection::PARAM_INT
                    )
                )
            );
        $sysFolderPagesQueryBuilder = $this->getDefaultConnection()->createQueryBuilder();
        $sysFolderPagesQueryBuilder
            ->select('uid', 'pid', 'doktype', 'title')
            ->from('pages')
            ->where(
                $unionQueryBuilder->expr()->eq(
                    'doktype',
                    $unionQueryBuilder->createNamedParameter(
                        PageRepository::DOKTYPE_SYSFOLDER,
                        Connection::PARAM_INT
                    )
                )
            );
        $unionQueryBuilder
            ->union($standardPagesQueryBuilder)
            ->addUnion($sysFolderPagesQueryBuilder, UnionType::DISTINCT)
            ->orderBy('doktype', 'ASC')
            ->addOrderBy('uid', 'ASC');
        self::assertSame($expectedRows, $unionQueryBuilder->executeQuery()->fetchAllAssociative());
    }

    #[Test]
    public function unionAllQueryWithAscendingOrderByUidReturnsExpectedResultSet(): void
    {
        $expectedRows = [
            ['uid' => 1, 'pid' => 0, 'doktype' => PageRepository::DOKTYPE_DEFAULT, 'title' => 'page 1'],
            ['uid' => 3, 'pid' => 0, 'doktype' => PageRepository::DOKTYPE_DEFAULT, 'title' => 'page 3'],
            ['uid' => 2, 'pid' => 0, 'doktype' => PageRepository::DOKTYPE_SYSFOLDER, 'title' => 'sysfolder 2'],
            ['uid' => 4, 'pid' => 0, 'doktype' => PageRepository::DOKTYPE_SYSFOLDER, 'title' => 'sysfolder 4'],
        ];
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/union-clause-simple.csv');
        $unionQueryBuilder = $this->getDefaultConnection()->createQueryBuilder();
        $standardPagesQueryBuilder = $this->getDefaultConnection()->createQueryBuilder();
        $standardPagesQueryBuilder
            ->select('uid', 'pid', 'doktype', 'title')
            ->from('pages')
            ->where(
                $unionQueryBuilder->expr()->eq(
                    'doktype',
                    $unionQueryBuilder->createNamedParameter(
                        PageRepository::DOKTYPE_DEFAULT,
                        Connection::PARAM_INT
                    )
                )
            );
        $sysFolderPagesQueryBuilder = $this->getDefaultConnection()->createQueryBuilder();
        $sysFolderPagesQueryBuilder
            ->select('uid', 'pid', 'doktype', 'title')
            ->from('pages')
            ->where(
                $unionQueryBuilder->expr()->eq(
                    'doktype',
                    $unionQueryBuilder->createNamedParameter(
                        PageRepository::DOKTYPE_SYSFOLDER,
                        Connection::PARAM_INT
                    )
                )
            );
        $unionQueryBuilder
            ->union($standardPagesQueryBuilder)
            ->addUnion($sysFolderPagesQueryBuilder, UnionType::ALL)
            ->orderBy('doktype', 'ASC')
            ->addOrderBy('uid', 'ASC');
        self::assertSame($expectedRows, $unionQueryBuilder->executeQuery()->fetchAllAssociative());
    }

    #[Test]
    public function unionDistinctQueryWithDescendingOrderByUidReturnsExpectedResult(): void
    {
        $expectedRows = [
            ['uid' => 4, 'pid' => 0, 'doktype' => PageRepository::DOKTYPE_SYSFOLDER, 'title' => 'sysfolder 4'],
            ['uid' => 3, 'pid' => 0, 'doktype' => PageRepository::DOKTYPE_DEFAULT, 'title' => 'page 3'],
            ['uid' => 2, 'pid' => 0, 'doktype' => PageRepository::DOKTYPE_SYSFOLDER, 'title' => 'sysfolder 2'],
            ['uid' => 1, 'pid' => 0, 'doktype' => PageRepository::DOKTYPE_DEFAULT, 'title' => 'page 1'],
        ];
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/union-clause-simple.csv');
        $unionQueryBuilder = $this->getDefaultConnection()->createQueryBuilder();
        $standardPagesQueryBuilder = $this->getDefaultConnection()->createQueryBuilder();
        $standardPagesQueryBuilder
            ->select('uid', 'pid', 'doktype', 'title')
            ->from('pages')
            ->where(
                $unionQueryBuilder->expr()->eq(
                    'doktype',
                    $unionQueryBuilder->createNamedParameter(
                        PageRepository::DOKTYPE_DEFAULT,
                        Connection::PARAM_INT
                    )
                )
            );
        $sysFolderPagesQueryBuilder = $this->getDefaultConnection()->createQueryBuilder();
        $sysFolderPagesQueryBuilder
            ->select('uid', 'pid', 'doktype', 'title')
            ->from('pages')
            ->where(
                $unionQueryBuilder->expr()->eq(
                    'doktype',
                    $unionQueryBuilder->createNamedParameter(
                        PageRepository::DOKTYPE_SYSFOLDER,
                        Connection::PARAM_INT
                    )
                )
            );
        $unionQueryBuilder
            ->union($standardPagesQueryBuilder)
            ->addUnion($sysFolderPagesQueryBuilder, UnionType::DISTINCT)
            ->orderBy('uid', 'desc');
        self::assertSame($expectedRows, $unionQueryBuilder->executeQuery()->fetchAllAssociative());
    }

    #[Test]
    public function unionAllQueryWithDescendingOrderByUidReturnsExpectedResult(): void
    {
        $expectedRows = [
            ['uid' => 4, 'pid' => 0, 'doktype' => PageRepository::DOKTYPE_SYSFOLDER, 'title' => 'sysfolder 4'],
            ['uid' => 3, 'pid' => 0, 'doktype' => PageRepository::DOKTYPE_DEFAULT, 'title' => 'page 3'],
            ['uid' => 2, 'pid' => 0, 'doktype' => PageRepository::DOKTYPE_SYSFOLDER, 'title' => 'sysfolder 2'],
            ['uid' => 1, 'pid' => 0, 'doktype' => PageRepository::DOKTYPE_DEFAULT, 'title' => 'page 1'],
        ];
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/union-clause-simple.csv');
        $unionQueryBuilder = $this->getDefaultConnection()->createQueryBuilder();
        $standardPagesQueryBuilder = $this->getDefaultConnection()->createQueryBuilder();
        $standardPagesQueryBuilder
            ->select('uid', 'pid', 'doktype', 'title')
            ->from('pages')
            ->where(
                $unionQueryBuilder->expr()->eq(
                    'doktype',
                    $unionQueryBuilder->createNamedParameter(
                        PageRepository::DOKTYPE_DEFAULT,
                        Connection::PARAM_INT
                    )
                )
            );
        $sysFolderPagesQueryBuilder = $this->getDefaultConnection()->createQueryBuilder();
        $sysFolderPagesQueryBuilder
            ->select('uid', 'pid', 'doktype', 'title')
            ->from('pages')
            ->where(
                $unionQueryBuilder->expr()->eq(
                    'doktype',
                    $unionQueryBuilder->createNamedParameter(
                        PageRepository::DOKTYPE_SYSFOLDER,
                        Connection::PARAM_INT
                    )
                )
            );
        $unionQueryBuilder
            ->union($standardPagesQueryBuilder)
            ->addUnion($sysFolderPagesQueryBuilder, UnionType::ALL)
            ->orderBy('uid', 'desc');
        self::assertSame($expectedRows, $unionQueryBuilder->executeQuery()->fetchAllAssociative());
    }

    #[Test]
    public function mixedStringAndQueryBuilderInstancesReturnsExpectedResultSet(): void
    {
        $expectedRows = [
            ['uid' => 1, 'pid' => 0, 'doktype' => PageRepository::DOKTYPE_DEFAULT, 'title' => 'page 1'],
            ['uid' => 3, 'pid' => 0, 'doktype' => PageRepository::DOKTYPE_DEFAULT, 'title' => 'page 3'],
            ['uid' => 2, 'pid' => 0, 'doktype' => PageRepository::DOKTYPE_SYSFOLDER, 'title' => 'sysfolder 2'],
            ['uid' => 4, 'pid' => 0, 'doktype' => PageRepository::DOKTYPE_SYSFOLDER, 'title' => 'sysfolder 4'],
        ];
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/union-clause-simple.csv');
        $unionQueryBuilder = $this->getDefaultConnection()->createQueryBuilder();
        $standardPagesQueryBuilder = $this->getDefaultConnection()->createQueryBuilder();
        $standardPagesQueryBuilder
            ->select('uid', 'pid', 'doktype', 'title')
            ->from('pages')
            ->where(
                $unionQueryBuilder->expr()->eq(
                    'doktype',
                    $unionQueryBuilder->createNamedParameter(
                        PageRepository::DOKTYPE_DEFAULT,
                        Connection::PARAM_INT
                    )
                )
            );
        $sysFolderPagesPlainSql = 'SELECT uid, pid, doktype, title FROM pages WHERE doktype = ' . PageRepository::DOKTYPE_SYSFOLDER;
        $unionQueryBuilder
            ->union($standardPagesQueryBuilder)
            ->addUnion($sysFolderPagesPlainSql, UnionType::DISTINCT)
            ->orderBy('doktype', 'ASC')
            ->addOrderBy('uid', 'ASC');
        self::assertSame($expectedRows, $unionQueryBuilder->executeQuery()->fetchAllAssociative());
    }

    #[Test]
    public function simpleValueListReturnsExpectedResultSet(): void
    {
        $expectedRows = [
            ['id' => 4, 'sorting' => 1],
            ['id' => 3, 'sorting' => 2],
            ['id' => 2, 'sorting' => 3],
            ['id' => 1, 'sorting' => 4],
        ];
        $platform = $this->getDefaultConnection()->getDatabasePlatform();
        $plainSelect1 = $platform->getDummySelectSQL('1 as id, 4 as sorting');
        $plainSelect2 = $platform->getDummySelectSQL('2 as id, 3 as sorting');
        $plainSelect3 = $platform->getDummySelectSQL('3 as id, 2 as sorting');
        $plainSelect4 = $platform->getDummySelectSQL('4 as id, 1 as sorting');
        $unionQueryBuilder = $this->getDefaultConnection()->createQueryBuilder();
        $unionQueryBuilder
            ->union($plainSelect1)
            ->addUnion($plainSelect3, UnionType::DISTINCT)
            ->addUnion($plainSelect2, UnionType::DISTINCT)
            ->addUnion($plainSelect4, UnionType::DISTINCT)
            ->orderBy('sorting', 'ASC')
            ->addOrderBy('id', 'ASC');
        self::assertSame($expectedRows, $unionQueryBuilder->executeQuery()->fetchAllAssociative());
    }

    #[Test]
    public function useSimpleValueListAsFromTableToEnsureSorting(): void
    {
        $expectedRows = [
            ['uid' => 2, 'pid' => 0, 'doktype' => PageRepository::DOKTYPE_SYSFOLDER, 'title' => 'sysfolder 2'],
            ['uid' => 3, 'pid' => 0, 'doktype' => PageRepository::DOKTYPE_DEFAULT, 'title' => 'page 3'],
            ['uid' => 1, 'pid' => 0, 'doktype' => PageRepository::DOKTYPE_DEFAULT, 'title' => 'page 1'],
        ];
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/union-clause-simple.csv');
        $platform = $this->getDefaultConnection()->getDatabasePlatform();
        $plainSelect1 = $platform->getDummySelectSQL('1 as id, 3 as sorting');
        $plainSelect2 = $platform->getDummySelectSQL('2 as id, 1 as sorting');
        $plainSelect3 = $platform->getDummySelectSQL('3 as id, 2 as sorting');
        $unionQueryBuilder = $this->getDefaultConnection()->createQueryBuilder();
        $unionQueryBuilder
            ->union($plainSelect1)
            ->addUnion($plainSelect3, UnionType::DISTINCT)
            ->addUnion($plainSelect2, UnionType::ALL);
        $pagesQueryBuilder = $this->getDefaultConnection()->createQueryBuilder();
        $pagesQueryBuilder->select('pages.uid', 'pages.pid', 'pages.doktype', 'pages.title')->from('pages')
            ->getConcreteQueryBuilder()
            ->innerJoin(
                $pagesQueryBuilder->quoteIdentifier('pages'),
                sprintf('(%s)', $unionQueryBuilder->getSQL()),
                $pagesQueryBuilder->quoteIdentifier('value_list'),
                sprintf(
                    '%s = %s',
                    $pagesQueryBuilder->quoteIdentifier('value_list.id'),
                    $pagesQueryBuilder->quoteIdentifier('pages.uid'),
                )
            );
        $pagesQueryBuilder->orderBy('value_list.sorting', 'ASC')->addOrderBy('pages.uid');
        self::assertSame($expectedRows, $pagesQueryBuilder->executeQuery()->fetchAllAssociative());
    }

    private function getDefaultConnection(): Connection
    {
        return $this->getConnectionPool()->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
    }
}
