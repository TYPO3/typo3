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

namespace TYPO3\CMS\Core\Tests\Functional\Database\Query\QueryBuilder\CommonTableExpression;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SimpleSelectCommonTableExpressionTest extends FunctionalTestCase
{
    #[Test]
    public function twoSimpleValueCommonTableExpressionsJoinedReturnsExpectedResult(): void
    {
        // # --
        // # Builds following SQL (for MySQL - other databases varies)
        // # --
        //
        //    WITH
        //
        //        cte1 (a, b) AS (
        //            SELECT
        //                1 AS `a`,
        //                'value-a' AS `b`
        //        ),
        //
        //        cte2 (c, d) AS (
        //            SELECT
        //                1 AS `c`,
        //                'value-c' AS `d`
        //        )
        //
        //    SELECT
        //        `a` AS `id`,
        //        `b` AS `value1`,
        //        `d` AS `value2`
        //    FROM `cte1`
        //    INNER JOIN `cte2` `cte2` ON (cte1.a = cte2.c)
        $expectedRows = [
            ['id' => 1, 'value1' => 'value-a', 'value2' => 'value-c'],
        ];
        $connection = $this->getConnectionPool()->getConnectionByName('Default');
        $selectQueryBuilder = $connection->createQueryBuilder();
        $expr = $selectQueryBuilder->expr();
        $simpleValueListQueryBuilder1 = $connection->createQueryBuilder();
        $simpleValueListQueryBuilder1
            ->selectLiteral(
                $expr->as('1', $selectQueryBuilder->quoteIdentifier('a')),
                $expr->as($selectQueryBuilder->quote('value-a'), 'b'),
            );
        $simpleValueListQueryBuilder2 = $connection->createQueryBuilder();
        $simpleValueListQueryBuilder2
            ->selectLiteral(
                $expr->as('1', 'c'),
                $expr->as($selectQueryBuilder->quote('value-c'), 'd'),
            );
        $selectQueryBuilder
            ->typo3_with('cte1', $simpleValueListQueryBuilder1, ['a', 'b'])
            ->typo3_addWith('cte2', $simpleValueListQueryBuilder2, ['c', 'd'])
            ->selectLiteral(
                $expr->as($selectQueryBuilder->quoteIdentifier('a'), 'id'),
                $expr->as($selectQueryBuilder->quoteIdentifier('b'), 'value1'),
                $expr->as($selectQueryBuilder->quoteIdentifier('d'), 'value2'),
            )
            ->from('cte1')
            ->join('cte1', 'cte2', 'cte2', '(cte1.a = cte2.c)');
        //$sql = $selectQueryBuilder->getSQL();
        self::assertSame($expectedRows, $selectQueryBuilder->executeQuery()->fetchAllAssociative());
    }

    #[Test]
    public function simpleValueListCommonTableExpressionReturnsExpectedResult(): void
    {
        // # --
        // # Builds following SQL (for MySQL - other databases varies)
        // # --
        //    WITH
        //
        //        cte (col1, col2) AS (
        //            SELECT
        //                (CAST('1' AS SIGNED INTEGER)),
        //                (CAST('100' AS SIGNED INTEGER))
        //
        //            UNION ALL
        //
        //                SELECT
        //                    (CAST('2' AS SIGNED INTEGER)),
        //                    (CAST('50' AS SIGNED INTEGER))
        //        )
        //
        //    SELECT
        //        `col1`,
        //        `col2`
        //    FROM `cte`
        $expectedRows = [
            ['col1' => 1, 'col2' => 100],
            ['col1' => 2, 'col2' => 50],
        ];
        $connection = $this->getConnectionPool()->getConnectionByName('Default');
        $selectQueryBuilder = $connection->createQueryBuilder();
        $expr = $selectQueryBuilder->expr();
        $simpleValueListQueryBuilder1 = $connection->createQueryBuilder();
        $simpleValueListQueryBuilder1->selectLiteral(...array_values([
            $expr->castInt($expr->literal('1')),
            $expr->castInt($expr->literal('100')),
        ]));
        $simpleValueListQueryBuilder2 = $connection->createQueryBuilder();
        $simpleValueListQueryBuilder2->selectLiteral(...array_values([
            $expr->castInt($expr->literal('2')),
            $expr->castInt($expr->literal('50')),
        ]));
        $selectQueryBuilder
            ->typo3_with(
                'cte',
                // @todo Switch to QueryBuilder UNION API when available.
                sprintf(
                    '%s UNION ALL %s',
                    $simpleValueListQueryBuilder1,
                    $simpleValueListQueryBuilder2,
                ),
                [
                    'col1',
                    'col2',
                ]
            )
            ->select('col1', 'col2')
            ->from('cte');
        //$sql = $selectQueryBuilder->getSQL();
        self::assertSame($expectedRows, $selectQueryBuilder->executeQuery()->fetchAllAssociative());
    }

    #[Test]
    public function simpleValueListCommonTableExpressionWithOrderByListFieldReturnsExpectedResult(): void
    {
        // # --
        // # Builds following SQL (for MySQL - other databases varies)
        // # --
        //
        //    WITH
        //
        //        cte (col1, col2) AS (
        //                SELECT
        //                    (CAST('1' AS SIGNED INTEGER)),
        //                    (CAST('100' AS SIGNED INTEGER))
        //            UNION ALL
        //
        //                SELECT
        //                    (CAST('2' AS SIGNED INTEGER)),
        //                    (CAST('50' AS SIGNED INTEGER))
        //
        //        )
        //
        //    SELECT
        //      `col1`,
        //      `col2`
        //    FROM `cte`
        //    ORDER BY
        //      `col2` ASC,
        //      `col1` ASC
        $expectedRows = [
            ['col1' => 2, 'col2' => 50],
            ['col1' => 1, 'col2' => 100],
        ];
        $connection = $this->getConnectionPool()->getConnectionByName('Default');
        $selectQueryBuilder = $connection->createQueryBuilder();
        $expr = $selectQueryBuilder->expr();
        $simpleValueListQueryBuilder1 = $connection->createQueryBuilder();
        $simpleValueListQueryBuilder1->selectLiteral(...array_values([
            $expr->castInt($expr->literal('1')),
            $expr->castInt($expr->literal('100')),
        ]));
        $simpleValueListQueryBuilder2 = $connection->createQueryBuilder();
        $simpleValueListQueryBuilder2->selectLiteral(...array_values([
            $expr->castInt($expr->literal('2')),
            $expr->castInt($expr->literal('50')),
        ]));
        $selectQueryBuilder
            ->typo3_with(
                'cte',
                // @todo Switch to QueryBuilder UNION API when available.
                sprintf(
                    '%s UNION ALL %s',
                    $simpleValueListQueryBuilder1,
                    $simpleValueListQueryBuilder2,
                ),
                [
                    'col1',
                    'col2',
                ]
            )
            ->select('col1', 'col2')
            ->from('cte')
            ->orderBy('col2', 'ASC')
            ->addOrderBy('col1', 'ASC');
        //$sql = $selectQueryBuilder->getSQL();
        self::assertSame($expectedRows, $selectQueryBuilder->executeQuery()->fetchAllAssociative());
    }

    #[Test]
    public function recursiveSimpleValueCommonTableExpressionReturnsExpectedResult(): void
    {
        // # --
        // # Builds following SQL (for MySQL - other databases varies)
        // # --
        //
        //    WITH RECURSIVE
        //
        //        cte AS (
        //
        //                    SELECT
        //                        (CAST(1 AS SIGNED INTEGER)) AS `n`,
        //                        (CAST('abc' AS CHAR(255))) AS `str`
        //
        //            UNION ALL
        //
        //                SELECT
        //                    (CAST(n + 1 AS SIGNED INTEGER)),
        //                    (CAST((CONCAT(`str`, `str`)) AS CHAR(255)))
        //                FROM `cte`
        //                WHERE
        //                    `n` < (CAST(3 AS SIGNED INTEGER))
        //        )
        //
        //    SELECT
        //        *
        //    FROM `cte`
        //
        $expectedRows = [
            ['n' => 1, 'str' => 'abc'],
            ['n' => 2, 'str' => 'abcabc'],
            ['n' => 3, 'str' => 'abcabcabcabc'],
        ];
        $connection = $this->getConnectionPool()->getConnectionByName('Default');
        $selectQueryBuilder = $connection->createQueryBuilder();
        $expr = $selectQueryBuilder->expr();
        $concreteExpr = $selectQueryBuilder->getConcreteQueryBuilder()->expr();
        $initialQueryBuilder = $connection->createQueryBuilder()
            ->selectLiteral(...array_values([
                $expr->castInt('1', 'n'),
                $expr->castVarchar($selectQueryBuilder->quote('abc'), 255, 'str'),
            ]));
        $subQueryBuilder = $connection->createQueryBuilder()
            ->selectLiteral(...array_values([
                $expr->castInt('n + 1'),
                $expr->castVarchar(
                    sprintf('(%s)', $expr->concat(
                        $selectQueryBuilder->quoteIdentifier('str'),
                        $selectQueryBuilder->quoteIdentifier('str'),
                    )),
                    255,
                ),
            ]))
            ->from('cte')
            ->where(
                $concreteExpr->lt($selectQueryBuilder->quoteIdentifier('n'), $expr->castInt('3'))
            );
        $selectQueryBuilder
            ->typo3_withRecursive(
                'cte',
                false,
                $initialQueryBuilder,
                $subQueryBuilder,
            )
            ->select('*')->from('cte');
        //$sql = $selectQueryBuilder->getSQL();
        self::assertSame($expectedRows, $selectQueryBuilder->executeQuery()->fetchAllAssociative());
    }

    #[Test]
    public function fibonacciSeriesCommonTableExpression(): void
    {
        // # --
        // # Builds following SQL (for MySQL - other databases varies)
        // # --
        //
        //    WITH RECURSIVE
        //
        //        fibonacci (n, fib_n, next_fib_n) AS (
        //
        //                SELECT
        //                    (CAST(1 AS SIGNED INTEGER)),
        //                    (CAST(0 AS SIGNED INTEGER)),
        //                    (CAST(1 AS SIGNED INTEGER))
        //
        //            UNION ALL
        //
        //                SELECT
        //                    (CAST(`n` + 1 AS SIGNED INTEGER)),
        //                    `next_fib_n`,
        //                    `fib_n` + `next_fib_n`
        //                FROM `fibonacci`
        //                WHERE `n` < (CAST(10 AS SIGNED INTEGER))
        //        )
        //
        //    SELECT
        //        *
        //    FROM `fibonacci`
        //
        $expectedRows = [
            ['n' => 1, 'fib_n' => 0, 'next_fib_n' => 1],
            ['n' => 2, 'fib_n' => 1, 'next_fib_n' => 1],
            ['n' => 3, 'fib_n' => 1, 'next_fib_n' => 2],
            ['n' => 4, 'fib_n' => 2, 'next_fib_n' => 3],
            ['n' => 5, 'fib_n' => 3, 'next_fib_n' => 5],
            ['n' => 6, 'fib_n' => 5, 'next_fib_n' => 8],
            ['n' => 7, 'fib_n' => 8, 'next_fib_n' => 13],
            ['n' => 8, 'fib_n' => 13, 'next_fib_n' => 21],
            ['n' => 9, 'fib_n' => 21, 'next_fib_n' => 34],
            ['n' => 10, 'fib_n' => 34, 'next_fib_n' => 55],
        ];
        $connection = $this->getConnectionPool()->getConnectionByName('Default');
        $selectQueryBuilder = $connection->createQueryBuilder();
        $expr = $selectQueryBuilder->expr();
        $concreteExpr = $selectQueryBuilder->getConcreteQueryBuilder()->expr();
        $initialQueryBuilder = $connection->createQueryBuilder()
            ->selectLiteral(...array_values([
                $expr->castInt('1'),
                $expr->castInt('0'),
                $expr->castInt('1'),
            ]));
        $subQueryBuilder = $connection->createQueryBuilder()
            ->selectLiteral(...array_values([
                $expr->castInt(sprintf('%s + 1', $selectQueryBuilder->quoteIdentifier('n'))),
                $selectQueryBuilder->quoteIdentifier('next_fib_n'),
                sprintf(
                    '%s + %s',
                    $selectQueryBuilder->quoteIdentifier('fib_n'),
                    $selectQueryBuilder->quoteIdentifier('next_fib_n'),
                ),
            ]))
            ->from('fibonacci')
            ->where(
                $concreteExpr->lt($selectQueryBuilder->quoteIdentifier('n'), $expr->castInt('10'))
            );
        $selectQueryBuilder
            ->typo3_withRecursive(
                'fibonacci',
                false,
                $initialQueryBuilder,
                $subQueryBuilder,
                ['n', 'fib_n', 'next_fib_n'],
            )
            ->select('*')->from('fibonacci');
        //$sql = $selectQueryBuilder->getSQL();
        self::assertSame($expectedRows, $selectQueryBuilder->executeQuery()->fetchAllAssociative());
    }

    #[Test]
    public function simplifiedFibonacciSeriesCommonTableExpression(): void
    {
        // # --
        // # Builds following SQL (for MySQL - other databases varies)
        // # --
        //
        //    WITH RECURSIVE
        //
        //        fibonacci (n, fib_n, next_fib_n) AS (
        //
        //                SELECT
        //                    (CAST(1 AS SIGNED INTEGER)),
        //                    (CAST(0 AS SIGNED INTEGER)),
        //                    (CAST(1 AS SIGNED INTEGER))
        //
        //            UNION ALL
        //
        //                SELECT
        //                    (CAST(`n` + 1 AS SIGNED INTEGER)),
        //                    `next_fib_n`,
        //                    `fib_n` + `next_fib_n`
        //                FROM `fibonacci`
        //                WHERE `n` < (CAST(10 AS SIGNED INTEGER))
        //
        //        )
        //
        //    SELECT
        //        `fib_n`
        //    FROM `fibonacci`
        //
        $expectedRows = [
            ['fib_n' => 0],
            ['fib_n' => 1],
            ['fib_n' => 1],
            ['fib_n' => 2],
            ['fib_n' => 3],
            ['fib_n' => 5],
            ['fib_n' => 8],
            ['fib_n' => 13],
            ['fib_n' => 21],
            ['fib_n' => 34],
        ];
        $connection = $this->getConnectionPool()->getConnectionByName('Default');
        $selectQueryBuilder = $connection->createQueryBuilder();
        $expr = $selectQueryBuilder->expr();
        $concreteExpr = $selectQueryBuilder->getConcreteQueryBuilder()->expr();
        $initialQueryBuilder = $connection->createQueryBuilder()
            ->selectLiteral(...array_values([
                $expr->castInt('1'),
                $expr->castInt('0'),
                $expr->castInt('1'),
            ]));
        $subQueryBuilder = $connection->createQueryBuilder()
            ->selectLiteral(...array_values([
                $expr->castInt(sprintf('%s + 1', $selectQueryBuilder->quoteIdentifier('n'))),
                $selectQueryBuilder->quoteIdentifier('next_fib_n'),
                sprintf(
                    '%s + %s',
                    $selectQueryBuilder->quoteIdentifier('fib_n'),
                    $selectQueryBuilder->quoteIdentifier('next_fib_n'),
                ),
            ]))
            ->from('fibonacci')
            ->where(
                $concreteExpr->lt($selectQueryBuilder->quoteIdentifier('n'), $expr->castInt('10'))
            );
        $selectQueryBuilder
            ->typo3_withRecursive(
                'fibonacci',
                false,
                $initialQueryBuilder,
                $subQueryBuilder,
                ['n', 'fib_n', 'next_fib_n'],
            )
            ->select('fib_n')->from('fibonacci');
        //$sql = $selectQueryBuilder->getSQL();
        self::assertSame($expectedRows, $selectQueryBuilder->executeQuery()->fetchAllAssociative());
    }

    #[Test]
    public function fibonacciSeriesWithSpecifiedValueCommonTableExpression(): void
    {
        // # --
        // # Builds following SQL (for MySQL - other databases varies)
        // # --
        //
        //    WITH RECURSIVE
        //
        //        fibonacci (n, fib_n, next_fib_n) AS (
        //
        //                SELECT
        //                    (CAST(1 AS SIGNED INTEGER)),
        //                    (CAST(0 AS SIGNED INTEGER)),
        //                    (CAST(1 AS SIGNED INTEGER))
        //
        //            UNION ALL
        //
        //                SELECT
        //
        //                    (CAST(`n` + 1 AS SIGNED INTEGER)),
        //                    `next_fib_n`,
        //                    `fib_n` + `next_fib_n`
        //
        //                FROM `fibonacci` WHERE `n` < (CAST(10 AS SIGNED INTEGER))
        //        )
        //
        //    SELECT
        //        `fib_n`
        //    FROM `fibonacci`
        //    WHERE
        //        `n` = :dcValue1
        //
        $expectedRows = [
            ['fib_n' => 13],
        ];
        $connection = $this->getConnectionPool()->getConnectionByName('Default');
        $selectQueryBuilder = $connection->createQueryBuilder();
        $expr = $selectQueryBuilder->expr();
        $concreteExpr = $selectQueryBuilder->getConcreteQueryBuilder()->expr();
        $initialQueryBuilder = $connection->createQueryBuilder()
            ->selectLiteral(...array_values([
                $expr->castInt('1'),
                $expr->castInt('0'),
                $expr->castInt('1'),
            ]));
        $subQueryBuilder = $connection->createQueryBuilder()
            ->selectLiteral(...array_values([
                $expr->castInt(sprintf('%s + 1', $selectQueryBuilder->quoteIdentifier('n'))),
                $selectQueryBuilder->quoteIdentifier('next_fib_n'),
                sprintf(
                    '%s + %s',
                    $selectQueryBuilder->quoteIdentifier('fib_n'),
                    $selectQueryBuilder->quoteIdentifier('next_fib_n'),
                ),
            ]))
            ->from('fibonacci')
            ->where(
                $concreteExpr->lt($selectQueryBuilder->quoteIdentifier('n'), $expr->castInt('10'))
            );
        $selectQueryBuilder
            ->typo3_withRecursive(
                'fibonacci',
                false,
                $initialQueryBuilder,
                $subQueryBuilder,
                ['n', 'fib_n', 'next_fib_n'],
            )
            ->select('fib_n')
            ->from('fibonacci')
            ->where(
                $expr->eq('n', $selectQueryBuilder->createNamedParameter(8, Connection::PARAM_INT)),
            );
        //$sql = $selectQueryBuilder->getSQL();
        self::assertSame($expectedRows, $selectQueryBuilder->executeQuery()->fetchAllAssociative());
    }
}
