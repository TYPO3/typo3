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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SimpleRootLineTraversalTest extends FunctionalTestCase
{
    public static function recursiveCommonTableExpression(): \Generator
    {
        yield 'rootline from level 3' => [
            'pageId' => 5,
            'maxLevel' => 10,
            'expectedRows' => [
                [
                    'uid' => 5,
                    'pid' => 2,
                    'title' => '1-3',
                    '__CTE_LEVEL__' => 1,
                ],
                [
                    'uid' => 2,
                    'pid' => 1,
                    'title' => '1',
                    '__CTE_LEVEL__' => 2,
                ],
                [
                    'uid' => 1,
                    'pid' => 0,
                    'title' => 'root',
                    '__CTE_LEVEL__' => 3,
                ],
            ],
        ];
        yield 'rootline from level 3 without max level' => [
            'pageId' => 5,
            'maxLevel' => 0,
            'expectedRows' => [
                [
                    'uid' => 5,
                    'pid' => 2,
                    'title' => '1-3',
                    '__CTE_LEVEL__' => 1,
                ],
                [
                    'uid' => 2,
                    'pid' => 1,
                    'title' => '1',
                    '__CTE_LEVEL__' => 2,
                ],
                [
                    'uid' => 1,
                    'pid' => 0,
                    'title' => 'root',
                    '__CTE_LEVEL__' => 3,
                ],
            ],
        ];
        yield 'incomplete rootline due to insufficient max level' => [
            'pageId' => 5,
            'maxLevel' => 2,
            'expectedRows' => [
                [
                    'uid' => 5,
                    'pid' => 2,
                    'title' => '1-3',
                    '__CTE_LEVEL__' => 1,
                ],
                [
                    'uid' => 2,
                    'pid' => 1,
                    'title' => '1',
                    '__CTE_LEVEL__' => 2,
                ],
            ],
        ];
    }

    #[DataProvider('recursiveCommonTableExpression')]
    #[Test]
    public function recursiveCommonTableExpressionReturnsExpectedResult(int $pageId, int $maxLevel, array $expectedRows): void
    {
        // # --
        // # Builds following SQL (for MySQL - other databases varies)
        // # --
        //
        //    WITH RECURSIVE
        //
        //        cte (`uid`, `pid`, `title`, `__CTE_LEVEL__`) AS (
        //            SELECT
        //                `uid`,
        //                `pid`,
        //                `title`,
        //                (CAST(1 AS SIGNED INTEGER)) AS `__CTE_LEVEL__`
        //             FROM `pages`
        //             WHERE `uid` = :dcValue1
        //
        //        UNION ALL
        //
        //            SELECT
        //                `p`.`uid`,
        //                `p`.`pid`,
        //                `p`.`title`,
        //                (CAST(((CAST(`c`.`__CTE_LEVEL__` AS SIGNED INTEGER)) + 1) AS SIGNED INTEGER)) AS `__CTE_LEVEL__`
        //            FROM `pages` `p`
        //            INNER JOIN `cte` `c` ON (`p`.`uid` = `c`.`pid`)
        //            WHERE
        //                (`c`.`pid` <> :dcValue2)
        //            AND (`c`.`__CTE_LEVEL__` < :dcValue3)
        //        )
        //
        //    SELECT * FROM `cte` ORDER BY `cte`.`__CTE_LEVEL__` ASC, `cte`.`uid` ASC
        //
        $this->importCSVDataSet(__DIR__ . '/Fixtures/CTE/recursiveCommonTableExpression_pageTreeOne.csv');
        $connection = $this->getConnectionPool()->getConnectionByName('Default');
        $selectQueryBuilder = $connection->createQueryBuilder();
        $expr = $selectQueryBuilder->expr();
        $initialQueryBuilder = $connection->createQueryBuilder()
            ->selectLiteral(...array_values([
                // pages fields
                $selectQueryBuilder->quoteIdentifier('uid'),
                $selectQueryBuilder->quoteIdentifier('pid'),
                $selectQueryBuilder->quoteIdentifier('title'),
                // CTE recursive handling fields
                $expr->castInt('1', '__CTE_LEVEL__'),
            ]))
            ->from('pages')
            ->where(...array_values([
                $expr->eq('uid', $selectQueryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)),
            ]));
        $initialQueryBuilder->getRestrictions()->removeAll();
        $subQueryBuilder = $connection->createQueryBuilder()
            ->selectLiteral(...array_values([
                // pages fields
                $selectQueryBuilder->quoteIdentifier('p.uid'),
                $selectQueryBuilder->quoteIdentifier('p.pid'),
                $selectQueryBuilder->quoteIdentifier('p.title'),
                // CTE recursive handling fields
                $expr->castInt(sprintf('(%s + 1)', $expr->castInt($selectQueryBuilder->quoteIdentifier('c.__CTE_LEVEL__'))), '__CTE_LEVEL__'),
            ]))
            ->from('pages', 'p')
            ->innerJoin('p', 'cte', 'c', sprintf('(%s)', $expr->eq('p.uid', $selectQueryBuilder->quoteIdentifier('c.pid'))));
        $subQueryBuilder->getRestrictions()->removeAll();
        if ($maxLevel > 0) {
            $subQueryBuilder->andWhere(...array_values([
                $expr->neq('c.pid', $selectQueryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $expr->lt('c.__CTE_LEVEL__', $selectQueryBuilder->createNamedParameter($maxLevel, Connection::PARAM_INT)),
            ]));
        }
        $selectQueryBuilder
            ->typo3_withRecursive(
                'cte',
                false,
                $initialQueryBuilder,
                $subQueryBuilder,
                [
                    $selectQueryBuilder->quoteIdentifier('uid'),
                    $selectQueryBuilder->quoteIdentifier('pid'),
                    $selectQueryBuilder->quoteIdentifier('title'),
                    $selectQueryBuilder->quoteIdentifier('__CTE_LEVEL__'),
                ]
            )
            ->select('*')
            ->from('cte')
            ->orderBy('cte.__CTE_LEVEL__', 'ASC')
            ->addOrderBy('cte.uid', 'ASC');

        // $sql = $selectQueryBuilder->getSQL();
        self::assertSame($expectedRows, $selectQueryBuilder->executeQuery()->fetchAllAssociative());
    }
}
