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

namespace TYPO3\CMS\Core\Tests\Functional\Database\Query\Expression;

use Doctrine\DBAL\Platforms\SQLitePlatform as DoctrineSQLitePlatform;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ExpressionBuilderTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Database/Fixtures/Extensions/test_expressionbuilder',
    ];

    #[Test]
    public function inSetReturnsExpectedDataSetsWithColumn(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/DataSet/TestExpressionBuilderInSet.csv');
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_expressionbuildertest');
        $result = $queryBuilder
            ->select('uid', 'aCsvField')
            ->from('tx_expressionbuildertest')
            ->where(
                $queryBuilder->expr()->inSet('aCsvField', $queryBuilder->quoteIdentifier('aField'), true)
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
        $expected = [
            0 => [
                'uid' => 1,
                'aCsvField' => 'match',
            ],
            1 => [
                'uid' => 2,
                'aCsvField' => 'match,nomatch',
            ],
            2 => [
                'uid' => 3,
                'aCsvField' => 'nomatch,match',
            ],
            3 => [
                'uid' => 4,
                'aCsvField' => 'nomatch1,match,nomatch2',
            ],
            // uid 5 missing here!
            4 => [
                'uid' => 6,
                'aCsvField' => '2',
            ],
            5 => [
                'uid' => 7,
                'aCsvField' => '2,3',
            ],
            6 => [
                'uid' => 8,
                'aCsvField' => '1,2',
            ],
            7 => [
                'uid' => 9,
                'aCsvField' => '1,2,3',
            ],
            // uid 10 missing here!
            8 => [
                'uid' => 11,
                'aCsvField' => 'wild%card',
            ],
            9 => [
                'uid' => 12,
                'aCsvField' => 'wild%card,nowild%card',
            ],
            10 => [
                'uid' => 13,
                'aCsvField' => 'nowild%card,wild%card',
            ],
            11 => [
                'uid' => 14,
                'aCsvField' => 'nowild%card1,wild%card,nowild%card2',
            ],
        ];
        self::assertEquals($expected, $result);
    }

    #[Test]
    public function inSetReturnsExpectedDataSets(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/DataSet/TestExpressionBuilderInSet.csv');
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_expressionbuildertest');
        $result = $queryBuilder
            ->select('uid', 'aCsvField')
            ->from('tx_expressionbuildertest')
            ->where(
                $queryBuilder->expr()->inSet('aCsvField', $queryBuilder->expr()->literal('match'))
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
        $expected = [
            0 => [
                'uid' => 1,
                'aCsvField' => 'match',
            ],
            1 => [
                'uid' => 2,
                'aCsvField' => 'match,nomatch',
            ],
            2 => [
                'uid' => 3,
                'aCsvField' => 'nomatch,match',
            ],
            3 => [
                'uid' => 4,
                'aCsvField' => 'nomatch1,match,nomatch2',
            ],
        ];
        self::assertEquals($expected, $result);
    }

    #[Test]
    public function inSetReturnsExpectedDataSetsWithInts(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/DataSet/TestExpressionBuilderInSet.csv');
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_expressionbuildertest');
        $result = $queryBuilder
            ->select('uid', 'aCsvField')
            ->from('tx_expressionbuildertest')
            ->where(
                $queryBuilder->expr()->inSet('aCsvField', (string)2)
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
        $expected = [
            0 => [
                'uid' => 6,
                'aCsvField' => '2',
            ],
            1 => [
                'uid' => 7,
                'aCsvField' => '2,3',
            ],
            2 => [
                'uid' => 8,
                'aCsvField' => '1,2',
            ],
            3 => [
                'uid' => 9,
                'aCsvField' => '1,2,3',
            ],
        ];
        self::assertEquals($expected, $result);
    }

    #[Test]
    public function inSetReturnsExpectedDataSetsIfValueContainsLikeWildcard(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/DataSet/TestExpressionBuilderInSet.csv');
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_expressionbuildertest');
        $result = $queryBuilder
            ->select('uid', 'aCsvField')
            ->from('tx_expressionbuildertest')
            ->where(
                $queryBuilder->expr()->inSet('aCsvField', $queryBuilder->expr()->literal('wild%card'))
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
        $expected = [
            0 => [
                'uid' => 11,
                'aCsvField' => 'wild%card',
            ],
            1 => [
                'uid' => 12,
                'aCsvField' => 'wild%card,nowild%card',
            ],
            2 => [
                'uid' => 13,
                'aCsvField' => 'nowild%card,wild%card',
            ],
            3 => [
                'uid' => 14,
                'aCsvField' => 'nowild%card1,wild%card,nowild%card2',
            ],
        ];
        self::assertEquals($expected, $result);
    }

    #[Test]
    public function inSetReturnsExpectedDataSetsIfValueContainsBracket(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/DataSet/TestExpressionBuilderInSet.csv');
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_expressionbuildertest');
        $result = $queryBuilder
            ->select('uid', 'aCsvField')
            ->from('tx_expressionbuildertest')
            ->where(
                $queryBuilder->expr()->inSet('aCsvField', $queryBuilder->expr()->literal('wild[card'))
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
        $expected = [
            0 => [
                'uid' => 16,
                'aCsvField' => 'wild[card',
            ],
            1 => [
                'uid' => 17,
                'aCsvField' => 'wild[card,nowild[card',
            ],
            2 => [
                'uid' => 18,
                'aCsvField' => 'nowild[card,wild[card',
            ],
            3 => [
                'uid' => 19,
                'aCsvField' => 'nowild[card1,wild[card,nowild[card2',
            ],
        ];
        self::assertEquals($expected, $result);
    }

    #[Test]
    public function inSetReturnsExpectedDataSetsIfValueContainsClosingBracket(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/DataSet/TestExpressionBuilderInSet.csv');
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_expressionbuildertest');
        $result = $queryBuilder
            ->select('uid', 'aCsvField')
            ->from('tx_expressionbuildertest')
            ->where(
                $queryBuilder->expr()->inSet('aCsvField', $queryBuilder->expr()->literal('wild]card'))
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
        $expected = [
            0 => [
                'uid' => 21,
                'aCsvField' => 'wild]card',
            ],
            1 => [
                'uid' => 22,
                'aCsvField' => 'wild]card,nowild]card',
            ],
            2 => [
                'uid' => 23,
                'aCsvField' => 'nowild]card,wild]card',
            ],
            3 => [
                'uid' => 24,
                'aCsvField' => 'nowild]card1,wild]card,nowild]card2',
            ],
        ];
        self::assertEquals($expected, $result);
    }

    #[Test]
    public function inSetReturnsExpectedDataSetsIfValueContainsOpeningAndClosingBracket(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/DataSet/TestExpressionBuilderInSet.csv');
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_expressionbuildertest');
        $result = $queryBuilder
            ->select('uid', 'aCsvField')
            ->from('tx_expressionbuildertest')
            ->where(
                $queryBuilder->expr()->inSet('aCsvField', $queryBuilder->expr()->literal('wild[]card'))
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
        $expected = [
            0 => [
                'uid' => 26,
                'aCsvField' => 'wild[]card',
            ],
            1 => [
                'uid' => 27,
                'aCsvField' => 'wild[]card,nowild[]card',
            ],
            2 => [
                'uid' => 28,
                'aCsvField' => 'nowild[]card,wild[]card',
            ],
            3 => [
                'uid' => 29,
                'aCsvField' => 'nowild[]card1,wild[]card,nowild[]card2',
            ],
        ];
        self::assertEquals($expected, $result);
    }

    #[Test]
    public function inSetReturnsExpectedDataSetsIfValueContainsBracketsAroundWord(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/DataSet/TestExpressionBuilderInSet.csv');
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_expressionbuildertest');
        $result = $queryBuilder
            ->select('uid', 'aCsvField')
            ->from('tx_expressionbuildertest')
            ->where(
                $queryBuilder->expr()->inSet('aCsvField', $queryBuilder->expr()->literal('wild[foo]card'))
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
        $expected = [
            0 => [
                'uid' => 31,
                'aCsvField' => 'wild[foo]card',
            ],
            1 => [
                'uid' => 32,
                'aCsvField' => 'wild[foo]card,nowild[foo]card',
            ],
            2 => [
                'uid' => 33,
                'aCsvField' => 'nowild[foo]card,wild[foo]card',
            ],
            3 => [
                'uid' => 34,
                'aCsvField' => 'nowild[foo]card1,wild[foo]card,nowild[foo]card2',
            ],
        ];
        self::assertEquals($expected, $result);
    }

    #[Test]
    public function inSetReturnsExpectedDataSetsIfValueContainsBracketsAroundLikeWildcard(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/DataSet/TestExpressionBuilderInSet.csv');
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_expressionbuildertest');
        $result = $queryBuilder
            ->select('uid', 'aCsvField')
            ->from('tx_expressionbuildertest')
            ->where(
                $queryBuilder->expr()->inSet('aCsvField', $queryBuilder->expr()->literal('wild[%]card'))
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
        $expected = [
            0 => [
                'uid' => 36,
                'aCsvField' => 'wild[%]card',
            ],
            1 => [
                'uid' => 37,
                'aCsvField' => 'wild[%]card,nowild[%]card',
            ],
            2 => [
                'uid' => 38,
                'aCsvField' => 'nowild[%]card,wild[%]card',
            ],
            3 => [
                'uid' => 39,
                'aCsvField' => 'nowild[%]card1,wild[%]card,nowild[%]card2',
            ],
        ];
        self::assertEquals($expected, $result);
    }

    #[Test]
    public function notInSetReturnsExpectedDataSetsWithColumn(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/DataSet/TestExpressionBuilderInSet.csv');
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_expressionbuildertest');
        $result = $queryBuilder
            ->select('uid', 'aCsvField')
            ->from('tx_expressionbuildertest')
            ->where(
                $queryBuilder->expr()->notInSet('aCsvField', $queryBuilder->quoteIdentifier('aField'), true)
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
        $expected = [
            0 => [
                'uid' => 5,
                'aCsvField' => 'nomatch',
            ],
            1 => [
                'uid' => 10,
                'aCsvField' => '4',
            ],
            2 => [
                'uid' => 15,
                'aCsvField' => 'nowild%card',
            ],
            3 => [
                'uid' => 16,
                'aCsvField' => 'wild[card',
            ],
            4 => [
                'uid' => 17,
                'aCsvField' => 'wild[card,nowild[card',
            ],
            5 => [
                'uid' => 18,
                'aCsvField' => 'nowild[card,wild[card',
            ],
            6 => [
                'uid' => 19,
                'aCsvField' => 'nowild[card1,wild[card,nowild[card2',
            ],
            7 => [
                'uid' => 20,
                'aCsvField' => 'nowild[card',
            ],
            8 => [
                'uid' => 21,
                'aCsvField' => 'wild]card',
            ],
            9 => [
                'uid' => 22,
                'aCsvField' => 'wild]card,nowild]card',
            ],
            10 => [
                'uid' => 23,
                'aCsvField' => 'nowild]card,wild]card',
            ],
            11 => [
                'uid' => 24,
                'aCsvField' => 'nowild]card1,wild]card,nowild]card2',
            ],
            12 => [
                'uid' => 25,
                'aCsvField' => 'nowild]card',
            ],
            13 => [
                'uid' => 26,
                'aCsvField' => 'wild[]card',
            ],
            14 => [
                'uid' => 27,
                'aCsvField' => 'wild[]card,nowild[]card',
            ],
            15 => [
                'uid' => 28,
                'aCsvField' => 'nowild[]card,wild[]card',
            ],
            16 => [
                'uid' => 29,
                'aCsvField' => 'nowild[]card1,wild[]card,nowild[]card2',
            ],
            17 => [
                'uid' => 30,
                'aCsvField' => 'nowild[]card',
            ],
            18 => [
                'uid' => 31,
                'aCsvField' => 'wild[foo]card',
            ],
            19 => [
                'uid' => 32,
                'aCsvField' => 'wild[foo]card,nowild[foo]card',
            ],
            20 => [
                'uid' => 33,
                'aCsvField' => 'nowild[foo]card,wild[foo]card',
            ],
            21 => [
                'uid' => 34,
                'aCsvField' => 'nowild[foo]card1,wild[foo]card,nowild[foo]card2',
            ],
            22 => [
                'uid' => 35,
                'aCsvField' => 'nowild[foo]card',
            ],
            23 => [
                'uid' => 36,
                'aCsvField' => 'wild[%]card',
            ],
            24 => [
                'uid' => 37,
                'aCsvField' => 'wild[%]card,nowild[%]card',
            ],
            25 => [
                'uid' => 38,
                'aCsvField' => 'nowild[%]card,wild[%]card',
            ],
            26 => [
                'uid' => 39,
                'aCsvField' => 'nowild[%]card1,wild[%]card,nowild[%]card2',
            ],
            27 => [
                'uid' => 40,
                'aCsvField' => 'nowild[%]card',
            ],
        ];
        self::assertEquals($expected, $result);
    }

    #[Test]
    public function notInSetReturnsExpectedDataSets(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/DataSet/TestExpressionBuilderInSet.csv');
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_expressionbuildertest');
        $queryBuilder
            ->count('uid')
            ->from('tx_expressionbuildertest');
        // Count all rows
        self::assertEquals(40, $queryBuilder->executeQuery()->fetchOne());

        // Count the ones not in set
        $queryBuilder->where(
            $queryBuilder->expr()->notInSet('aCsvField', $queryBuilder->expr()->literal('match')),
        );
        self::assertEquals(36, $queryBuilder->executeQuery()->fetchOne());

        // Count the ones in set
        $queryBuilder->where(
            $queryBuilder->expr()->inSet('aCsvField', $queryBuilder->expr()->literal('match')),
        );
        self::assertEquals(4, $queryBuilder->executeQuery()->fetchOne());
    }

    #[Test]
    public function notInSetReturnsExpectedDataSetsWithInts(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/DataSet/TestExpressionBuilderInSet.csv');
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_expressionbuildertest');
        $queryBuilder
            ->count('uid')
            ->from('tx_expressionbuildertest')
            ->where(
                $queryBuilder->expr()->notInSet('aCsvField', (string)2)
            );
        self::assertEquals(36, $queryBuilder->executeQuery()->fetchOne());
    }

    #[Test]
    public function notInSetReturnsExpectedDataSetsIfValueContainsLikeWildcard(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/DataSet/TestExpressionBuilderInSet.csv');
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_expressionbuildertest');
        $queryBuilder
            ->count('uid')
            ->from('tx_expressionbuildertest')
            ->where(
                $queryBuilder->expr()->notInSet('aCsvField', $queryBuilder->expr()->literal('wild%card'))
            );
        self::assertEquals(36, $queryBuilder->executeQuery()->fetchOne());
    }

    #[Test]
    public function notInSetReturnsExpectedDataSetsIfValueContainsBracket(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/DataSet/TestExpressionBuilderInSet.csv');
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_expressionbuildertest');
        $queryBuilder
            ->count('uid')
            ->from('tx_expressionbuildertest')
            ->where(
                $queryBuilder->expr()->notInSet('aCsvField', $queryBuilder->expr()->literal('wild[card'))
            );
        self::assertEquals(36, $queryBuilder->executeQuery()->fetchOne());
    }

    #[Test]
    public function notInSetReturnsExpectedDataSetsIfValueContainsClosingBracket(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/DataSet/TestExpressionBuilderInSet.csv');
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_expressionbuildertest');
        $queryBuilder
            ->count('uid')
            ->from('tx_expressionbuildertest')
            ->where(
                $queryBuilder->expr()->notInSet('aCsvField', $queryBuilder->expr()->literal('wild]card'))
            );
        self::assertEquals(36, $queryBuilder->executeQuery()->fetchOne());
    }

    #[Test]
    public function notInSetReturnsExpectedDataSetsIfValueContainsOpeningAndClosingBracket(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/DataSet/TestExpressionBuilderInSet.csv');
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_expressionbuildertest');
        $queryBuilder
            ->count('uid')
            ->from('tx_expressionbuildertest')
            ->where(
                $queryBuilder->expr()->notInSet('aCsvField', $queryBuilder->expr()->literal('wild[]card'))
            );
        self::assertEquals(36, $queryBuilder->executeQuery()->fetchOne());
    }

    #[Test]
    public function notInSetReturnsExpectedDataSetsIfValueContainsBracketsAroundWord(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/DataSet/TestExpressionBuilderInSet.csv');
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_expressionbuildertest');
        $queryBuilder
            ->count('uid')
            ->from('tx_expressionbuildertest')
            ->where(
                $queryBuilder->expr()->notInSet('aCsvField', $queryBuilder->expr()->literal('wild[foo]card'))
            );
        self::assertEquals(36, $queryBuilder->executeQuery()->fetchOne());
    }

    #[Test]
    public function notInSetReturnsExpectedDataSetsIfValueContainsBracketsAroundLikeWildcard(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/DataSet/TestExpressionBuilderInSet.csv');
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_expressionbuildertest');
        $queryBuilder
            ->count('uid')
            ->from('tx_expressionbuildertest')
            ->where(
                $queryBuilder->expr()->notInSet('aCsvField', $queryBuilder->expr()->literal('wild[%]card'))
            );
        self::assertEquals(36, $queryBuilder->executeQuery()->fetchOne());
    }

    public static function likeReturnsExpectedDataSetsDataProvider(): \Generator
    {
        yield 'lowercase search word matches german umlauts in upper and lower casing #1' => [
            'searchWord' => '%über%',
            'expectedRows' => [
                0 => [
                    'uid' => 1,
                    'aCsvField' => 'Fächer, Überraschungen sind Äußerungen',
                ],
                1 => [
                    'uid' => 2,
                    'aCsvField' => 'Kleingeschriebenes überlebt halt ältere',
                ],
            ],
            'excludePlatforms' => [
                // Exclude sqlite due to german umlauts.
                DoctrineSQLitePlatform::class,
            ],
        ];

        yield 'lowercase search word matches german umlauts in upper and lower casing #2' => [
            'searchWord' => '%ältere%',
            'expectedRows' => [
                0 => [
                    'uid' => 2,
                    'aCsvField' => 'Kleingeschriebenes überlebt halt ältere',
                ],
            ],
            'excludePlatforms' => [
                // Exclude sqlite due to german umlauts.
                DoctrineSQLitePlatform::class,
            ],
        ];

        yield 'uppercase search word matches german umlauts in upper and lower casing #1' => [
            'searchWord' => '%Ältere%',
            'expectedRows' => [
                0 => [
                    'uid' => 2,
                    'aCsvField' => 'Kleingeschriebenes überlebt halt ältere',
                ],
            ],
            'excludePlatforms' => [
                // Exclude sqlite due to german umlauts.
                DoctrineSQLitePlatform::class,
            ],
        ];

        yield 'lowercase ascii search word matches properly case-insensitive' => [
            'searchWord' => '%klein%',
            'expectedRows' => [
                0 => [
                    'uid' => 2,
                    'aCsvField' => 'Kleingeschriebenes überlebt halt ältere',
                ],
            ],
            'excludePlatforms' => [],
        ];

        yield 'uppercase ascii search word matches properly case-insensitive' => [
            'searchWord' => '%KLEIN%',
            'expectedRows' => [
                0 => [
                    'uid' => 2,
                    'aCsvField' => 'Kleingeschriebenes überlebt halt ältere',
                ],
            ],
            'excludePlatforms' => [],
        ];
    }

    #[DataProvider('likeReturnsExpectedDataSetsDataProvider')]
    #[Test]
    /**
     * Note: SQLite does not properly work with non-ascii letters as search word for case-insensitive
     *       matching, UPPER() and LOWER() have the same issue, it only works with ascii letters.
     *       See: https://www.sqlite.org/src/doc/trunk/ext/icu/README.txt')]
     */
    public function likeReturnsExpectedDataSets(string $searchWord, array $expectedRows, array $excludePlatforms): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/DataSet/TestExpressionBuilderLikeAndNotLike.csv');
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_expressionbuildertest');
        if ($excludePlatforms !== []) {
            $platform = $queryBuilder->getConnection()->getDatabasePlatform();
            foreach ($excludePlatforms as $excludePlatform) {
                if ($platform instanceof $excludePlatform) {
                    self::markTestSkipped('Excluded platform ' . $excludePlatform);
                }
            }
        }
        $rows = $queryBuilder
            ->select('uid', 'aCsvField')
            ->from('tx_expressionbuildertest')
            ->where(
                // narrow down result set
                $queryBuilder->expr()->eq('aField', $queryBuilder->createNamedParameter('likecasing')),
                // this is what we are testing
                $queryBuilder->expr()->like('aCsvField', $queryBuilder->createNamedParameter($searchWord))
            )
            ->executeQuery()
            ->fetchAllAssociative();
        self::assertSame($expectedRows, $rows);
    }

    public static function notLikeReturnsExpectedDataSetsDataProvider(): \Generator
    {
        yield 'lowercase search word filters german umlauts in upper and lower casing #1' => [
            'searchWord' => '%Überraschungen%',
            'expectedRows' => [
                0 => [
                    'uid' => 2,
                    'aCsvField' => 'Kleingeschriebenes überlebt halt ältere',
                ],
            ],
            'excludePlatforms' => [
                // Exclude sqlite due to german umlauts.
                DoctrineSQLitePlatform::class,
            ],
        ];

        yield 'lowercase search word filters german umlauts in upper and lower casing #2' => [
            'searchWord' => '%überraschungen%',
            'expectedRows' => [
                0 => [
                    'uid' => 2,
                    'aCsvField' => 'Kleingeschriebenes überlebt halt ältere',
                ],
            ],
            'excludePlatforms' => [
                // Exclude sqlite due to german umlauts.
                DoctrineSQLitePlatform::class,
            ],
        ];

        yield 'lowercase ascii search word filters properly case-insensitive' => [
            'searchWord' => '%klein%',
            'expectedRows' => [
                0 => [
                    'uid' => 1,
                    'aCsvField' => 'Fächer, Überraschungen sind Äußerungen',
                ],
            ],
            'excludePlatforms' => [],
        ];
    }

    public static function likeWithWildcardValueCanBeMatchedDataProvider(): \Generator
    {
        yield 'escaped value with underscore matches properly' => [
            // addcslashes() is used in escapeLikeWildcards()
            'searchWord' => '%' . addcslashes('underscore_escape_can_be_matched', '%_') . '%',
            'expectedRows' => [
                0 => [
                    'uid' => 3,
                    'aCsvField' => 'underscore_escape_can_be_matched,second_value',
                ],
            ],
            'excludePlatforms' => [],
        ];

        yield 'escaped value with % matches properly' => [
            // addcslashes() is used in escapeLikeWildcards()
            'searchWord' => '%' . addcslashes('a % in', '%_') . '%',
            'expectedRows' => [
                0 => [
                    'uid' => 5,
                    'aCsvField' => 'Some value with a % in it',
                ],
            ],
            'excludePlatforms' => [],
        ];
    }

    #[DataProvider('likeWithWildcardValueCanBeMatchedDataProvider')]
    #[Test]
    public function likeWithWildcardValueCanBeMatched(string $searchWord, array $expectedRows, array $excludePlatforms): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/DataSet/TestExpressionBuilderLikeAndNotLike.csv');
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_expressionbuildertest');
        if ($excludePlatforms !== []) {
            $platform = $queryBuilder->getConnection()->getDatabasePlatform();
            foreach ($excludePlatforms as $excludePlatform) {
                if ($platform instanceof $excludePlatform) {
                    self::markTestSkipped('Excluded platform ' . $excludePlatform);
                }
            }
        }
        $rows = $queryBuilder
            ->select('uid', 'aCsvField')
            ->from('tx_expressionbuildertest')
            ->where(
                // narrow down result set
                $queryBuilder->expr()->eq('aField', $queryBuilder->createNamedParameter('likeescape')),
                // this is what we are testing
                $queryBuilder->expr()->like('aCsvField', $queryBuilder->createNamedParameter($searchWord))
            )
            ->executeQuery()
            ->fetchAllAssociative();
        self::assertSame($expectedRows, $rows);
    }

    public static function notLikeWithWildcardValueCanBeMatchedDataProvider(): \Generator
    {
        yield 'escaped value with underscore matches properly' => [
            // addcslashes() is used in escapeLikeWildcards()
            'searchWord' => '%' . addcslashes('underscore_escape_can_be_matched', '%_') . '%',
            'expectedRows' => [
                0 => [
                    'uid' => 4,
                    'aCsvField' => 'not_underscore_value',
                ],
                1 => [
                    'uid' => 5,
                    'aCsvField' => 'Some value with a % in it',
                ],
            ],
            'excludePlatforms' => [],
        ];

        yield 'escaped value with wildcard search word matches properly' => [
            // addcslashes() is used in escapeLikeWildcards()
            'searchWord' => '%' . addcslashes('a % in', '%_') . '%',
            'expectedRows' => [
                0 => [
                    'uid' => 3,
                    'aCsvField' => 'underscore_escape_can_be_matched,second_value',
                ],
                1 => [
                    'uid' => 4,
                    'aCsvField' => 'not_underscore_value',
                ],
            ],
            'excludePlatforms' => [],
        ];
    }

    #[DataProvider('notLikeWithWildcardValueCanBeMatchedDataProvider')]
    #[Test]
    public function notLikeWithWildcardValueCanBeMatched(string $searchWord, array $expectedRows, array $excludePlatforms): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/DataSet/TestExpressionBuilderLikeAndNotLike.csv');
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_expressionbuildertest');
        if ($excludePlatforms !== []) {
            $platform = $queryBuilder->getConnection()->getDatabasePlatform();
            foreach ($excludePlatforms as $excludePlatform) {
                if ($platform instanceof $excludePlatform) {
                    self::markTestSkipped('Excluded platform ' . $excludePlatform);
                }
            }
        }
        $rows = $queryBuilder
            ->select('uid', 'aCsvField')
            ->from('tx_expressionbuildertest')
            ->where(
                // narrow down result set
                $queryBuilder->expr()->eq('aField', $queryBuilder->createNamedParameter('likeescape')),
                // this is what we are testing
                $queryBuilder->expr()->notLike('aCsvField', $queryBuilder->createNamedParameter($searchWord))
            )
            ->executeQuery()
            ->fetchAllAssociative();
        self::assertSame($expectedRows, $rows);
    }

    #[DataProvider('notLikeReturnsExpectedDataSetsDataProvider')]
    #[Test]
    /**
     * Note: SQLite does not properly work with non-ascii letters as search word for case-insensitive
     *       matching, UPPER() and LOWER() have the same issue, it only works with ascii letters.
     *       See: https://www.sqlite.org/src/doc/trunk/ext/icu/README.txt')]
     */
    public function notLikeReturnsExpectedDataSets(string $searchWord, array $expectedRows, array $excludePlatforms): void
    {
        self::assertTrue(true);
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/DataSet/TestExpressionBuilderLikeAndNotLike.csv');
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_expressionbuildertest');
        if ($excludePlatforms !== []) {
            $platform = $queryBuilder->getConnection()->getDatabasePlatform();
            foreach ($excludePlatforms as $excludePlatform) {
                if ($platform instanceof $excludePlatform) {
                    self::markTestSkipped('Excluded platform ' . $excludePlatform);
                }
            }
        }
        $rows = $queryBuilder
            ->select('uid', 'aCsvField')
            ->from('tx_expressionbuildertest')
            ->where(
                // narrow down result set
                $queryBuilder->expr()->eq('aField', $queryBuilder->createNamedParameter('likecasing')),
                // this is what we are testing
                $queryBuilder->expr()->notLike('aCsvField', $queryBuilder->createNamedParameter($searchWord))
            )
            ->executeQuery()
            ->fetchAllAssociative();
        self::assertSame($expectedRows, $rows);
    }

    #[Test]
    public function ensureThatExpectedQuoteCharUsedInUnquoteIsValid(): void
    {
        $connection = $this->getConnectionPool()->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);

        $quoteChar = substr($connection->quote('__FAKE__'), 0, 1);
        self::assertSame("'", $quoteChar, $connection->getDatabasePlatform()::class);
    }

    public static function concatReturnsExpectedResultDataProvider(): \Generator
    {
        yield 'only title' => [
            'pageId' => 1,
            'expectedRow' => [
                'uid' => 1,
                'pid' => 0,
                'title' => 'only-title',
                'subtitle' => '',
                'combined_title' => 'only-title ',
            ],
        ];

        yield 'only subtitle' => [
            'pageId' => 2,
            'expectedRow' => [
                'uid' => 2,
                'pid' => 0,
                'title' => '',
                'subtitle' => 'only-subtitle',
                'combined_title' => ' only-subtitle',
            ],
        ];

        yield 'title and subtitle' => [
            'pageId' => 3,
            'expectedRow' => [
                'uid' => 3,
                'pid' => 0,
                'title' => 'title',
                'subtitle' => 'subtitle',
                'combined_title' => 'title subtitle',
            ],
        ];

        yield '123 and single space' => [
            'pageId' => 4,
            'expectedRow' => [
                'uid' => 4,
                'pid' => 0,
                'title' => '123',
                'subtitle' => ' ',
                // Note: Two space is intended, one space from record subtitle and the space as concetenation separator
                'combined_title' => '123  ',
            ],
        ];
    }

    #[DataProvider('concatReturnsExpectedResultDataProvider')]
    #[Test]
    public function concatReturnsExpectedResult(int $pageId, array $expectedRow): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/DataSet/TestExpressionBuilderConcat.csv');
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('pages');
        $row = $queryBuilder
            ->select('uid', 'pid', 'title', 'subtitle')
            ->addSelectLiteral(
                $queryBuilder->expr()->concat(
                    $queryBuilder->quoteIdentifier('title'),
                    $queryBuilder->quote(' '),
                    $queryBuilder->quoteIdentifier('subtitle'),
                ) . ' AS ' . $queryBuilder->quoteIdentifier('combined_title')
            )
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT))
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        self::assertIsArray($row);
        self::assertSame($expectedRow, $row);
    }

    public static function castVarcharReturnsExpectedResultDataProvider(): \Generator
    {
        yield 'uid as string #1' => [
            'pageId' => 1,
            'field' => 'uid',
            'fields' => ['uid', 'pid', 'title', 'subtitle'],
            'length' => 11,
            'asIdentifier' => 'uidAsString',
            'expectedLength' => 1,
            'expectedRow' => [
                'uid' => 1,
                'pid' => 0,
                'title' => 'only-title',
                'subtitle' => '',
                'uidAsString' => '1',
            ],
        ];

        yield 'uid as string #2' => [
            'pageId' => 123456789,
            'field' => 'uid',
            'fields' => ['uid', 'pid', 'title', 'subtitle'],
            'length' => 11,
            'asIdentifier' => 'uidAsString',
            'expectedLength' => 9,
            'expectedRow' => [
                'uid' => 123456789,
                'pid' => 0,
                'title' => '123',
                'subtitle' => ' ',
                'uidAsString' => '123456789',
            ],
        ];
    }

    #[DataProvider('castVarcharReturnsExpectedResultDataProvider')]
    #[Test]
    public function castVarcharReturnsExpectedResult(int $pageId, string $field, array $fields, int $length, string $asIdentifier, int $expectedLength, array $expectedRow): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/DataSet/TestExpressionBuilderCastVarchar.csv');
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('pages');
        $row = $queryBuilder
            ->select('uid', 'pid', 'title', 'subtitle')
            ->addSelectLiteral(
                $queryBuilder->expr()->castVarchar($queryBuilder->quoteIdentifier($field), $length, $asIdentifier)
            )
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT))
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        self::assertIsArray($row);
        self::assertArrayHasKey($asIdentifier, $row);
        self::assertIsString($row[$asIdentifier]);
        self::assertSame($expectedLength, strlen($row[$asIdentifier]));
        self::assertSame($expectedRow, $row);
    }

    #[Test]
    public function castIntReturnsExpectedResult(): void
    {
        $expectedRow = [
            'uid' => 1,
            'pid' => 0,
            'title' => '123',
            'subtitle' => '',
            'titleAsInteger' => 123,
        ];
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/DataSet/TestExpressionBuilderCastInt.csv');
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('pages');
        $row = $queryBuilder
            ->select('uid', 'pid', 'title', 'subtitle')
            ->addSelectLiteral(
                $queryBuilder->expr()->castInt($queryBuilder->quoteIdentifier('title'), 'titleAsInteger')
            )
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT))
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        self::assertIsArray($row);
        self::assertArrayHasKey('titleAsInteger', $row);
        self::assertIsInt($row['titleAsInteger']);
        self::assertSame(123, $row['titleAsInteger']);
        self::assertSame($expectedRow, $row);
    }

    public static function repeatReturnsExpectedResultDataProvider(): \Generator
    {
        // colon as repeat value
        yield 'Repeat ":" for 1 time with integer repeats number' => [
            'numberOfRepeats' => 1,
            'repeatValue' => ':',
            'expectedRepeatAsString' => ':',
        ];
        yield 'Repeat ":" for 5 times with integer repeats number' => [
            'numberOfRepeats' => 5,
            'repeatValue' => ':',
            'expectedRepeatAsString' => ':::::',
        ];
        yield 'Repeat ":" for 10 times with integer repeats number' => [
            'numberOfRepeats' => 10,
            'repeatValue' => ':',
            'expectedRepeatAsString' => '::::::::::',
        ];
        yield 'Repeat ":" for 20 times with integer repeats number' => [
            'numberOfRepeats' => 20,
            'repeatValue' => ':',
            'expectedRepeatAsString' => '::::::::::::::::::::',
        ];
        yield 'Repeat ":" for 1 times with string repeats number' => [
            'numberOfRepeats' => '1',
            'repeatValue' => ':',
            'expectedRepeatAsString' => ':',
        ];
        yield 'Repeat ":" for 5 times with string repeats number' => [
            'numberOfRepeats' => '5',
            'repeatValue' => ':',
            'expectedRepeatAsString' => ':::::',
        ];
        yield 'Repeat ":" for 10 times with string repeats number' => [
            'numberOfRepeats' => '10',
            'repeatValue' => ':',
            'expectedRepeatAsString' => '::::::::::',
        ];
        yield 'Repeat ":" for 20 times with string repeats number' => [
            'numberOfRepeats' => '20',
            'repeatValue' => ':',
            'expectedRepeatAsString' => '::::::::::::::::::::',
        ];
        yield 'Repeat ":" for 1 times with expression repeats number' => [
            'numberOfRepeats' => '0 + 1',
            'repeatValue' => ':',
            'expectedRepeatAsString' => ':',
        ];
        yield 'Repeat ":" for 5 times with expression repeats number' => [
            'numberOfRepeats' => '0 + 5',
            'repeatValue' => ':',
            'expectedRepeatAsString' => ':::::',
        ];
        yield 'Repeat ":" for 10 times with expression repeats number' => [
            'numberOfRepeats' => '0 + 10',
            'repeatValue' => ':',
            'expectedRepeatAsString' => '::::::::::',
        ];
        yield 'Repeat ":" for 20 times with expression repeats number' => [
            'numberOfRepeats' => '0 + 20',
            'repeatValue' => ':',
            'expectedRepeatAsString' => '::::::::::::::::::::',
        ];
        // space as repeat value
        yield 'Repeat " " for 1 times with integer repeats number' => [
            'numberOfRepeats' => 1,
            'repeatValue' => ' ',
            'expectedRepeatAsString' => ' ',
        ];
        yield 'Repeat " " for 5 times with integer repeats number' => [
            'numberOfRepeats' => 5,
            'repeatValue' => ' ',
            'expectedRepeatAsString' => '     ',
        ];
        yield 'Repeat " " for 10 times with integer repeats number' => [
            'numberOfRepeats' => 10,
            'repeatValue' => ' ',
            'expectedRepeatAsString' => '          ',
        ];
        yield 'Repeat " " for 20 times with integer repeats number' => [
            'numberOfRepeats' => 20,
            'repeatValue' => ' ',
            'expectedRepeatAsString' => '                    ',
        ];
        yield 'Repeat " " for 1 times with string repeats number' => [
            'numberOfRepeats' => '1',
            'repeatValue' => ' ',
            'expectedRepeatAsString' => ' ',
        ];
        yield 'Repeat " " for 5 times with string repeats number' => [
            'numberOfRepeats' => '5',
            'repeatValue' => ' ',
            'expectedRepeatAsString' => '     ',
        ];
        yield 'Repeat " " for 10 times with string repeats number' => [
            'numberOfRepeats' => '10',
            'repeatValue' => ' ',
            'expectedRepeatAsString' => '          ',
        ];
        yield 'Repeat " " for 20 times with string repeats number' => [
            'numberOfRepeats' => '20',
            'repeatValue' => ' ',
            'expectedRepeatAsString' => '                    ',
        ];
        yield 'Repeat " " for 1 times with expression repeats number' => [
            'numberOfRepeats' => '0 + 1',
            'repeatValue' => ' ',
            'expectedRepeatAsString' => ' ',
        ];
        yield 'Repeat " " for 5 times with expression repeats number' => [
            'numberOfRepeats' => '0 + 5',
            'repeatValue' => ' ',
            'expectedRepeatAsString' => '     ',
        ];
        yield 'Repeat " " for 10 times with expression repeats number' => [
            'numberOfRepeats' => '0 + 10',
            'repeatValue' => ' ',
            'expectedRepeatAsString' => '          ',
        ];
        yield 'Repeat " " for 20 times with expression repeats number' => [
            'numberOfRepeats' => '0 + 20',
            'repeatValue' => ' ',
            'expectedRepeatAsString' => '                    ',
        ];
    }

    #[DataProvider('repeatReturnsExpectedResultDataProvider')]
    #[Test]
    public function repeatReturnsExpectedResult(int|string $numberOfRepeats, string $repeatValue, string $expectedRepeatAsString): void
    {
        $queryBuilder = (new ConnectionPool())->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME)->createQueryBuilder();
        $repeatValue = $queryBuilder->quote($repeatValue);
        $row = $queryBuilder
            ->selectLiteral(
                $queryBuilder->expr()->repeat($numberOfRepeats, $repeatValue, $queryBuilder->quoteIdentifier('repeatAsString'))
            )
            ->executeQuery()
            ->fetchAssociative();

        self::assertIsArray($row);
        self::assertSame($expectedRepeatAsString, $row['repeatAsString'] ?? null);
    }

    #[DataProvider('repeatReturnsExpectedResultDataProvider')]
    #[Test]
    public function repeatWithValueExpressionReturnsExpectedResult(int|string $numberOfRepeats, string $repeatValue, string $expectedRepeatAsString): void
    {
        $queryBuilder = (new ConnectionPool())->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME)->createQueryBuilder();
        $repeatValue = $queryBuilder->expr()->concat(
            $queryBuilder->quote(''),
            $queryBuilder->quote($repeatValue),
            $queryBuilder->quote(''),
        );
        $row = $queryBuilder
            ->selectLiteral(
                $queryBuilder->expr()->repeat($numberOfRepeats, $repeatValue, $queryBuilder->quoteIdentifier('repeatAsString'))
            )
            ->executeQuery()
            ->fetchAssociative();

        self::assertIsArray($row);
        self::assertSame($expectedRepeatAsString, $row['repeatAsString'] ?? null);
    }

    public static function spaceReturnsCorrectNumberOfSpacesDataProvider(): \Generator
    {
        // integer repeat value
        yield 'one space with integer repeat' => ['numberOfSpaces' => 1, 'expectedSpacesString' => ' '];
        yield 'five spaces with integer repeat' => ['numberOfSpaces' => 5, 'expectedSpacesString' => '     '];
        yield 'ten spaces with integer repeat' => ['numberOfSpaces' => 10, 'expectedSpacesString' => '          '];
        yield 'twenty spaces with integer repeat' => ['numberOfSpaces' => 20, 'expectedSpacesString' => '                    '];
        // string repeat value
        yield 'one space with string repeat' => ['numberOfSpaces' => '1', 'expectedSpacesString' => ' '];
        yield 'five spaces with string repeat' => ['numberOfSpaces' => '5', 'expectedSpacesString' => '     '];
        yield 'ten spaces with string repeat' => ['numberOfSpaces' => '10', 'expectedSpacesString' => '          '];
        yield 'twenty spaces with string repeat' => ['numberOfSpaces' => '20', 'expectedSpacesString' => '                    '];
        // expression repeat value
        yield 'one space with expression repeat' => ['numberOfSpaces' => '0 + 1', 'expectedSpacesString' => ' '];
        yield 'five spaces with expression repeat' => ['numberOfSpaces' => '0 + 5', 'expectedSpacesString' => '     '];
        yield 'ten spaces with expression repeat' => ['numberOfSpaces' => '0 + 10', 'expectedSpacesString' => '          '];
        yield 'twenty spaces with expression repeat' => ['numberOfSpaces' => '0 + 20', 'expectedSpacesString' => '                    '];
    }

    #[DataProvider('spaceReturnsCorrectNumberOfSpacesDataProvider')]
    #[Test]
    public function spaceReturnsCorrectNumberOfSpaces(int|string $numberOfSpaces, string $expectedSpacesString): void
    {
        $queryBuilder = (new ConnectionPool())->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME)->createQueryBuilder();
        $row = $queryBuilder
            ->selectLiteral(
                $queryBuilder->expr()->space($numberOfSpaces, 'spacesString')
            )
            ->executeQuery()
            ->fetchAssociative();

        self::assertIsArray($row);
        self::assertSame($expectedSpacesString, $row['spacesString'] ?? null);
    }

    public static function leftReturnsExpectedResultDataProvider(): \Generator
    {
        yield 'string value with string length covered by full length' => [
            'length' => '4',
            'value' => 'some-string',
            'expectedValue' => 'some',
        ];
        yield 'int value with string length covered by full length' => [
            'length' => 4,
            'value' => 'some-string',
            'expectedValue' => 'some',
        ];
        yield 'string length exceeding full-value string value length returns full value' => [
            'length' => '100',
            'value' => 'some-string',
            'expectedValue' => 'some-string',
        ];
        yield 'int length exceeding full-value string value length returns full value' => [
            'length' => 100,
            'value' => 'some-string',
            'expectedValue' => 'some-string',
        ];
        yield 'Length sub-expression returns expected substring' => [
            'length' => '(1 + 3)',
            'value' => 'some-string',
            'expectedValue' => 'some',
        ];
        yield 'Length sub-expression exceeding full-value length returns full-value' => [
            'length' => '(2 * 2)',
            'value' => 'some-string',
            'expectedValue' => 'some',
        ];
    }

    #[DataProvider('leftReturnsExpectedResultDataProvider')]
    #[Test]
    public function leftReturnsExpectedResult(int|string $length, string $value, string $expectedValue): void
    {
        $queryBuilder = (new ConnectionPool())->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME)->createQueryBuilder();
        $row = $queryBuilder
            ->selectLiteral(
                $queryBuilder->expr()->left($length, $queryBuilder->quote($value)) . ' AS ' . $queryBuilder->quoteIdentifier('expectedValue'),
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        self::assertIsArray($row);
        self::assertArrayHasKey('expectedValue', $row);
        self::assertIsString($row['expectedValue']);
        self::assertSame($expectedValue, $row['expectedValue']);
    }

    public static function rightReturnsExpectedResultDataProvider(): \Generator
    {
        yield 'string value with string length covered by full length' => [
            'length' => '6',
            'value' => 'some-string',
            'expectedValue' => 'string',
        ];
        yield 'int value with string length covered by full length' => [
            'length' => 6,
            'value' => 'some-string',
            'expectedValue' => 'string',
        ];
        yield 'string length exceeding full-value string value length returns full value' => [
            'length' => '100',
            'value' => 'some-string',
            'expectedValue' => 'some-string',
        ];
        yield 'int length exceeding full-value string value length returns full value' => [
            'length' => 100,
            'value' => 'some-string',
            'expectedValue' => 'some-string',
        ];
        yield 'Length sub-expression returns expected substring' => [
            'length' => '(1 + 5)',
            'value' => 'some-string',
            'expectedValue' => 'string',
        ];
        yield 'Length sub-expression exceeding full-value length returns full-value' => [
            'length' => '(5 * 10)',
            'value' => 'some-string',
            'expectedValue' => 'some-string',
        ];
    }

    #[DataProvider('rightReturnsExpectedResultDataProvider')]
    #[Test]
    public function rightReturnsExpectedResult(int|string $length, string $value, string $expectedValue): void
    {
        $queryBuilder = (new ConnectionPool())->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME)->createQueryBuilder();
        $row = $queryBuilder
            ->selectLiteral(
                $queryBuilder->expr()->right($length, $queryBuilder->quote($value)) . ' AS ' . $queryBuilder->quoteIdentifier('expectedValue'),
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        self::assertIsArray($row);
        self::assertArrayHasKey('expectedValue', $row);
        self::assertIsString($row['expectedValue']);
        self::assertSame($expectedValue, $row['expectedValue']);
    }

    public static function leftPadReturnsExpectedResultDataProvider(): \Generator
    {
        yield 'value and int length returns expected left padded value if value is too short #1' => [
            'value' => '123',
            'length' => 5,
            'paddingValue' => '0',
            'expectedValue' => '00123',
        ];
        yield 'value and int length returns expected left padded value if value is too short #2' => [
            'value' => '123',
            'length' => 10,
            'paddingValue' => '.',
            'expectedValue' => '.......123',
        ];
        yield 'returns value cut to length if value length exceeds padding length' => [
            'value' => '1234567890',
            'length' => 5,
            'paddingValue' => '.',
            'expectedValue' => '12345',
        ];
    }

    #[DataProvider('leftPadReturnsExpectedResultDataProvider')]
    #[Test]
    public function leftPadReturnsExpectedResult(string $value, int|string $length, string $paddingValue, string $expectedValue): void
    {
        $queryBuilder = (new ConnectionPool())->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME)->createQueryBuilder();
        $row = $queryBuilder
            ->selectLiteral(
                $queryBuilder->expr()->leftPad($queryBuilder->quote($value), $length, $paddingValue, 'expectedValue'),
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        self::assertIsArray($row);
        self::assertArrayHasKey('expectedValue', $row);
        self::assertIsString($row['expectedValue']);
        self::assertSame($expectedValue, $row['expectedValue']);
    }

    public static function leftPadWithValueSubexpressionReturnsExpectedResultDataProvider(): \Generator
    {
        yield 'value and int length returns expected left padded value if value is too short #1' => [
            'value' => ['1', '2', '3'],
            'length' => 5,
            'paddingValue' => '0',
            'expectedValue' => '00123',
        ];
        yield 'value and int length returns expected left padded value if value is too short #2' => [
            'value' => ['1', '2', '3'],
            'length' => 10,
            'paddingValue' => '.',
            'expectedValue' => '.......123',
        ];
        yield 'returns cutted value to length if value length exceeds padding length' => [
            'value' => ['1', '2', '3', '4', '5', '7', '8', '9', '0'],
            'length' => 5,
            'paddingValue' => '.',
            'expectedValue' => '12345',
        ];
    }

    #[DataProvider('leftPadWithValueSubexpressionReturnsExpectedResultDataProvider')]
    #[Test]
    public function leftPadWithValueSubexpressionReturnsExpectedResult(array $value, int|string $length, string $paddingValue, string $expectedValue): void
    {
        $queryBuilder = (new ConnectionPool())->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME)->createQueryBuilder();
        $value = array_map(fn($value) => $queryBuilder->quote($value), array_values($value));
        $row = $queryBuilder
            ->selectLiteral(
                $queryBuilder->expr()->leftPad($queryBuilder->expr()->concat(...$value), $length, $paddingValue, 'expectedValue'),
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        self::assertIsArray($row);
        self::assertArrayHasKey('expectedValue', $row);
        self::assertIsString($row['expectedValue']);
        self::assertSame($expectedValue, $row['expectedValue']);
    }

    public static function rightPadReturnsExpectedResultDataProvider(): \Generator
    {
        yield 'value and int length returns expected left padded value if value is too short #1' => [
            'value' => '123',
            'length' => 5,
            'paddingValue' => '0',
            'expectedValue' => '12300',
        ];
        yield 'value and int length returns expected left padded value if value is too short #2' => [
            'value' => '123',
            'length' => 10,
            'paddingValue' => '.',
            'expectedValue' => '123.......',
        ];
        yield 'returns cutted value to length if value length exceeds padding length' => [
            'value' => '1234567890',
            'length' => 5,
            'paddingValue' => '.',
            // Note: `RPAD` cuts the value from the left like LPAD, which is basically brain melting. Therefore,
            // this is adopted here to be concise with this behaviour.
            'expectedValue' => '12345',
        ];
    }

    #[DataProvider('rightPadReturnsExpectedResultDataProvider')]
    #[Test]
    public function rightPadReturnsExpectedResult(string $value, int|string $length, string $paddingValue, string $expectedValue): void
    {
        $queryBuilder = (new ConnectionPool())->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME)->createQueryBuilder();
        $row = $queryBuilder
            ->selectLiteral(
                $queryBuilder->expr()->rightPad($queryBuilder->quote($value), $length, $paddingValue, 'expectedValue')
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        self::assertIsArray($row);
        self::assertArrayHasKey('expectedValue', $row);
        self::assertIsString($row['expectedValue']);
        self::assertSame($expectedValue, $row['expectedValue']);
    }

    #[DataProvider('rightPadReturnsExpectedResultDataProvider')]
    #[Test]
    public function rightPadReturnsWithQuotedAliasExpectedResult(string $value, int|string $length, string $paddingValue, string $expectedValue): void
    {
        $queryBuilder = (new ConnectionPool())->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME)->createQueryBuilder();
        $row = $queryBuilder
            ->selectLiteral(
                $queryBuilder->expr()->rightPad($queryBuilder->quote($value), $length, $paddingValue, $queryBuilder->quoteIdentifier('expectedValue'))
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        self::assertIsArray($row);
        self::assertArrayHasKey('expectedValue', $row);
        self::assertIsString($row['expectedValue']);
        self::assertSame($expectedValue, $row['expectedValue']);
    }

    public static function rightPadWithValueSubexpressionReturnsExpectedResultDataProvider(): \Generator
    {
        yield 'value and int length returns expected left padded value if value is too short #1' => [
            'value' => ['1', '2', '3'],
            'length' => 5,
            'paddingValue' => '0',
            'expectedValue' => '12300',
        ];
        yield 'value and int length returns expected left padded value if value is too short #2' => [
            'value' => ['1', '2', '3'],
            'length' => 10,
            'paddingValue' => '.',
            'expectedValue' => '123.......',
        ];
        yield 'returns cutted value to length if value length exceeds padding length' => [
            'value' => ['1', '2', '3', '4', '5', '7', '8', '9', '0'],
            'length' => 5,
            'paddingValue' => '.',
            'expectedValue' => '12345',
        ];
    }

    #[DataProvider('rightPadWithValueSubexpressionReturnsExpectedResultDataProvider')]
    #[Test]
    public function rightPadWithValueSubexpressionReturnsExpectedResult(array $value, int|string $length, string $paddingValue, string $expectedValue): void
    {
        $queryBuilder = (new ConnectionPool())->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME)->createQueryBuilder();
        $value = array_map(fn($value) => $queryBuilder->quote($value), array_values($value));
        $row = $queryBuilder
            ->selectLiteral(
                $queryBuilder->expr()->rightPad($queryBuilder->expr()->concat(...$value), $length, $paddingValue, 'expectedValue'),
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        self::assertIsArray($row);
        self::assertArrayHasKey('expectedValue', $row);
        self::assertIsString($row['expectedValue']);
        self::assertSame($expectedValue, $row['expectedValue']);
    }

    #[DataProvider('rightPadWithValueSubexpressionReturnsExpectedResultDataProvider')]
    #[Test]
    public function rightPadWithValueSubexpressionWithQuotedAliasReturnsExpectedResult(array $value, int|string $length, string $paddingValue, string $expectedValue): void
    {
        $queryBuilder = (new ConnectionPool())->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME)->createQueryBuilder();
        $value = array_map(fn($value) => $queryBuilder->quote($value), array_values($value));
        $row = $queryBuilder
            ->selectLiteral(
                $queryBuilder->expr()->rightPad($queryBuilder->expr()->concat(...$value), $length, $paddingValue, $queryBuilder->quoteIdentifier('expectedValue')),
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        self::assertIsArray($row);
        self::assertArrayHasKey('expectedValue', $row);
        self::assertIsString($row['expectedValue']);
        self::assertSame($expectedValue, $row['expectedValue']);
    }

    #[Test]
    public function leftPadWithEmptyStringPaddingValueThrowsInvalidArgumentException(): void
    {
        self::expectExceptionCode(1709658914);
        self::expectException(\InvalidArgumentException::class);

        $queryBuilder = (new ConnectionPool())->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME)->createQueryBuilder();
        $queryBuilder->expr()->leftPad('uid', 10, '');
    }

    #[Test]
    public function leftPadWithMultiCharacterPaddingValueThrowsInvalidArgumentException(): void
    {
        self::expectExceptionCode(1709659006);
        self::expectException(\InvalidArgumentException::class);

        $queryBuilder = (new ConnectionPool())->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME)->createQueryBuilder();
        $queryBuilder->expr()->leftPad('uid', 10, '..');
    }

    #[Test]
    public function rightPadWithEmptyStringPaddingValueThrowsInvalidArgumentException(): void
    {
        self::expectExceptionCode(1709664589);
        self::expectException(\InvalidArgumentException::class);

        $queryBuilder = (new ConnectionPool())->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME)->createQueryBuilder();
        $queryBuilder->expr()->rightPad('uid', 10, '');
    }

    #[Test]
    public function rightPadWithMultiCharacterPaddingValueThrowsInvalidArgumentException(): void
    {
        self::expectExceptionCode(1709664598);
        self::expectException(\InvalidArgumentException::class);

        $queryBuilder = (new ConnectionPool())->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME)->createQueryBuilder();
        $queryBuilder->expr()->rightPad('uid', 10, '..');
    }

    #[Test]
    public function ifExpressionReturnsExpectedDataSets(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/DataSet/TestExpressionBuilderIf.csv');
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();
        $result = $queryBuilder
            ->select('uid', 'title')
            ->addSelectLiteral(
                $queryBuilder->expr()->if(
                    $queryBuilder->expr()->and(
                        $queryBuilder->expr()->gt('uid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                        $queryBuilder->expr()->eq('hidden', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                    ),
                    $queryBuilder->expr()->literal('visible'),
                    $queryBuilder->expr()->literal('not-visible'),
                    'hidden_state_label'
                )
            )
            ->from('pages')
            ->orderBy('uid', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();
        $expected = [
            0 => [
                'uid' => 1,
                'title' => '123',
                'hidden_state_label' => 'visible',
            ],
            1 => [
                'uid' => 2,
                'title' => 'string-2',
                'hidden_state_label' => 'not-visible',
            ],
        ];
        self::assertSame($expected, $result);
    }

    #[Test]
    public function castTextExpressionReturnsExpectedResult(): void
    {
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->selectLiteral(...array_values([
            $queryBuilder->expr()->castText('(1 * 10)', 'virtual_field'),
        ]));
        $expected = [
            0 => [
                'virtual_field' => '10',
            ],
        ];
        $result = $queryBuilder->executeQuery()->fetchAllAssociative();
        self::assertSame($expected, $result);
    }
}
