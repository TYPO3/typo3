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

final class SimpleTreeTraversalTest extends FunctionalTestCase
{
    public static function recursiveCommonTableExpression(): \Generator
    {
        yield 'tree root' => [
            'startPageId' => 0,
            'maxLevel' => 10,
            'expectedRows' => [
                [
                    'uid' => 1,
                    'pid' => 0,
                    'title' => 'root',
                    '__CTE_LEVEL__' => 1,
                    '__CTE_PATH__' => '1',
                    '__CTE_PATH_SORTING__' => '0000002',
                ],
                [
                    'uid' => 2,
                    'pid' => 1,
                    'title' => '1',
                    '__CTE_LEVEL__' => 2,
                    '__CTE_PATH__' => '1/2',
                    '__CTE_PATH_SORTING__' => '0000002/0000002',
                ],
                [
                    'uid' => 3,
                    'pid' => 2,
                    'title' => '1-1',
                    '__CTE_LEVEL__' => 3,
                    '__CTE_PATH__' => '1/2/3',
                    '__CTE_PATH_SORTING__' => '0000002/0000002/0000002',
                ],
                [
                    'uid' => 4,
                    'pid' => 2,
                    'title' => '1-2',
                    '__CTE_LEVEL__' => 3,
                    '__CTE_PATH__' => '1/2/4',
                    '__CTE_PATH_SORTING__' => '0000002/0000002/0000004',
                ],
                [
                    'uid' => 5,
                    'pid' => 2,
                    'title' => '1-3',
                    '__CTE_LEVEL__' => 3,
                    '__CTE_PATH__' => '1/2/5',
                    '__CTE_PATH_SORTING__' => '0000002/0000002/0000008',
                ],
                [
                    'uid' => 6,
                    'pid' => 1,
                    'title' => '2',
                    '__CTE_LEVEL__' => 2,
                    '__CTE_PATH__' => '1/6',
                    '__CTE_PATH_SORTING__' => '0000002/0000004',
                ],
                [
                    'uid' => 8,
                    'pid' => 6,
                    'title' => '2-1',
                    '__CTE_LEVEL__' => 3,
                    '__CTE_PATH__' => '1/6/8',
                    '__CTE_PATH_SORTING__' => '0000002/0000004/0000002',
                ],
                [
                    'uid' => 7,
                    'pid' => 6,
                    'title' => '2-2',
                    '__CTE_LEVEL__' => 3,
                    '__CTE_PATH__' => '1/6/7',
                    '__CTE_PATH_SORTING__' => '0000002/0000004/0000004',
                ],
                [
                    'uid' => 9,
                    'pid' => 6,
                    'title' => '2-3',
                    '__CTE_LEVEL__' => 3,
                    '__CTE_PATH__' => '1/6/9',
                    '__CTE_PATH_SORTING__' => '0000002/0000004/0000008',
                ],
            ],
        ];
        yield 'tree root max 2 levels' => [
            'startPageId' => 0,
            'maxLevel' => 2,
            'expectedRows' => [
                [
                    'uid' => 1,
                    'pid' => 0,
                    'title' => 'root',
                    '__CTE_LEVEL__' => 1,
                    '__CTE_PATH__' => '1',
                    '__CTE_PATH_SORTING__' => '0000002',
                ],
                [
                    'uid' => 2,
                    'pid' => 1,
                    'title' => '1',
                    '__CTE_LEVEL__' => 2,
                    '__CTE_PATH__' => '1/2',
                    '__CTE_PATH_SORTING__' => '0000002/0000002',
                ],
                [
                    'uid' => 6,
                    'pid' => 1,
                    'title' => '2',
                    '__CTE_LEVEL__' => 2,
                    '__CTE_PATH__' => '1/6',
                    '__CTE_PATH_SORTING__' => '0000002/0000004',
                ],
            ],
        ];
    }

    #[DataProvider('recursiveCommonTableExpression')]
    #[Test]
    public function recursiveCommonTableExpressionReturnsExpectedResult(int $startPageId, int $maxLevel, array $expectedRows): void
    {
        // # --
        // # Builds following SQL (for MySQL - other databases varies)
        // # --
        //
        //    WITH RECURSIVE
        //        cte (`uid`, `pid`, `title`, `__CTE_LEVEL__`, `__CTE_PATH__`, `__CTE_PATH_SORTING__`) AS (
        //                SELECT
        //                    `uid`,
        //                    `pid`,
        //                    `title`,
        //                    (CAST(1 AS SIGNED INTEGER)) AS `__CTE_LEVEL__`,
        //                    (CAST(`uid` AS CHAR(2000))) AS `__CTE_PATH__`,
        //                    (CAST(LPAD((CAST((CAST(`sorting` AS CHAR(2000))) AS CHAR(255))), (CAST(7 AS SIGNED INTEGER)), '0') AS CHAR(2000))) AS `__CTE_PATH_SORTING__`
        //
        //                FROM `pages`
        //                WHERE
        //                        (`pid` = :dcValue1)
        //                    AND (
        //                                (
        //                                        (`pages`.`deleted` = 0)
        //                                    AND (`pages`.`hidden` = 0)
        //                                    AND (`pages`.`starttime` <= 1718040420)
        //                                    AND (
        //                                                (
        //                                                        (`pages`.`endtime` = 0)
        //                                                    OR  (`pages`.`endtime` > 1718040420)
        //                                                )
        //                                        )
        //                                )
        //                        )
        //
        //            UNION ALL
        //
        //                SELECT
        //                    `p`.`uid`,
        //                    `p`.`pid`,
        //                    `p`.`title`,
        //                    (CAST(`cte`.`__CTE_LEVEL__` + 1 AS SIGNED INTEGER)) AS `__CTE_LEVEL__`,
        //                    (CAST((CONCAT(`cte`.`__CTE_PATH__`, '/', `p`.`uid`)) AS CHAR(2000))) AS `__CTE_PATH__`,
        //                    (CAST((CONCAT(`cte`.`__CTE_PATH_SORTING__`, '/', LPAD((CAST(`p`.`sorting` AS CHAR(255))), (CAST(7 AS SIGNED INTEGER)), '0'))) AS CHAR(2000))) AS `__CTE_PATH_SORTING__`
        //                FROM
        //                    `pages` `p`,
        //                    `cte`
        //                WHERE
        //                        (`p`.`pid` = `cte`.`uid`)
        //                    AND (`cte`.`__CTE_LEVEL__` < :dcValue2)
        //                    AND (
        //                                (
        //                                        (`p`.`deleted` = 0)
        //                                    AND (`p`.`hidden` = 0)
        //                                    AND (`p`.`starttime` <= 1718040420)
        //                                    AND (
        //                                                (
        //                                                        (`p`.`endtime` = 0)
        //                                                    OR  (`p`.`endtime` > 1718040420)
        //                                                )
        //                                        )
        //                                )
        //                        )
        //        )
        //    SELECT * FROM `cte` ORDER BY `cte`.`__CTE_PATH_SORTING__` ASC, `cte`.`uid` ASC
        $this->importCSVDataSet(__DIR__ . '/Fixtures/CTE/recursiveCommonTableExpression_pageTreeOne.csv');
        $connection = $this->getConnectionPool()->getConnectionByName('Default');
        $selectQueryBuilder = $connection->createQueryBuilder();
        $expr = $selectQueryBuilder->expr();
        $initialQueryBuilder = $connection->createQueryBuilder();
        $initialQueryBuilder
            ->selectLiteral(...array_values([
                // pages fields
                $selectQueryBuilder->quoteIdentifier('uid'),
                $selectQueryBuilder->quoteIdentifier('pid'),
                $selectQueryBuilder->quoteIdentifier('title'),
                // CTE recursive handling fields
                $expr->castInt('1', '__CTE_LEVEL__'),
                $expr->castVarchar($selectQueryBuilder->quoteIdentifier('uid'), 2000, '__CTE_PATH__'),
                $expr->castVarchar(
                    $expr->leftPad($expr->castVarchar($selectQueryBuilder->quoteIdentifier('sorting'), 2000), 7, '0'),
                    2000,
                    '__CTE_PATH_SORTING__',
                ),
            ]))
            ->from('pages')
            ->where(...array_values([
                $expr->eq('pid', $selectQueryBuilder->createNamedParameter($startPageId, Connection::PARAM_INT)),
            ]));
        $subQueryBuilder = $connection->createQueryBuilder();
        $subQueryBuilder
            ->selectLiteral(...array_values([
                // pages fields
                $selectQueryBuilder->quoteIdentifier('p.uid'),
                $selectQueryBuilder->quoteIdentifier('p.pid'),
                $selectQueryBuilder->quoteIdentifier('p.title'),
                // CTE recursive handling fields
                $expr->castInt(sprintf('%s + 1', $selectQueryBuilder->quoteIdentifier('cte.__CTE_LEVEL__')), '__CTE_LEVEL__'),
                $expr->castVarchar(
                    sprintf('(%s)', $expr->concat(
                        $selectQueryBuilder->quoteIdentifier('cte.__CTE_PATH__'),
                        $selectQueryBuilder->quote('/'),
                        $selectQueryBuilder->quoteIdentifier('p.uid'),
                    )),
                    2000,
                    '__CTE_PATH__'
                ),
                $expr->castVarchar(
                    sprintf('(%s)', $expr->concat(
                        $selectQueryBuilder->quoteIdentifier('cte.__CTE_PATH_SORTING__'),
                        $selectQueryBuilder->quote('/'),
                        $expr->leftPad($selectQueryBuilder->quoteIdentifier('p.sorting'), 7, '0'),
                    )),
                    2000,
                    '__CTE_PATH_SORTING__'
                ),
            ]))
            ->from('pages', 'p')
            ->from('cte')
            ->where(...array_values([
                $expr->eq('p.pid', $selectQueryBuilder->quoteIdentifier('cte.uid')),
            ]));
        if ($maxLevel > 0) {
            $subQueryBuilder->andWhere(...array_values([
                $expr->lt('cte.__CTE_LEVEL__', $selectQueryBuilder->createNamedParameter($maxLevel, Connection::PARAM_INT)),
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
                    $selectQueryBuilder->quoteIdentifier('__CTE_PATH__'),
                    $selectQueryBuilder->quoteIdentifier('__CTE_PATH_SORTING__'),
                ]
            )
            ->select('*')
            ->from('cte')
            ->orderBy('cte.__CTE_PATH_SORTING__', 'ASC')
            ->addOrderBy('cte.uid', 'ASC');
        //$sql = $selectQueryBuilder->getSQL();
        self::assertSame($expectedRows, $selectQueryBuilder->executeQuery()->fetchAllAssociative());
    }
}
