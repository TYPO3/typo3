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

use Doctrine\DBAL\Platforms\SqlitePlatform;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ExpressionBuilderTest extends FunctionalTestCase
{
    protected $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Database/Fixtures/Extensions/test_expressionbuilder',
    ];

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * notInSet tests, as they reverse the tests from above, only the count() logic is used to avoid too many
     * result arrays to be defined.
     */

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    public function likeReturnsExpectedDataSetsDataProvider(): \Generator
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
                SqlitePlatform::class,
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
                SqlitePlatform::class,
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
                SqlitePlatform::class,
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

    /**
     * @test
     * @dataProvider likeReturnsExpectedDataSetsDataProvider
     * Note: SQLite does not properly work with non-ascii letters as search word for case-insensitive
     *       matching, UPPER() and LOWER() have the same issue, it only works with ascii letters.
     *       See: https://www.sqlite.org/src/doc/trunk/ext/icu/README.txt
     */
    public function likeReturnsExpectedDataSets(string $searchWord, array $expectedRows, array $excludePlatforms): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/DataSet/TestExpressionBuilderLikeAndNotLike.csv');
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_expressionbuildertest');
        if ($excludePlatforms !== []) {
            $platform = $queryBuilder->getConnection()->getDatabasePlatform();
            foreach ($excludePlatforms as $excludePlatform) {
                if ($platform instanceof $excludePlatform) {
                    self::markTestSkipped('Exculded platform ' . $excludePlatform);
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
        if (is_array($rows)) {
            $rows = $this->ensureRowsUidAsInteger($rows);
        }
        self::assertSame($expectedRows, $rows);
    }

    public function notLikeReturnsExpectedDataSetsDataProvider(): \Generator
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
                SqlitePlatform::class,
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
                SqlitePlatform::class,
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

    /**
     * @test
     * @dataProvider notLikeReturnsExpectedDataSetsDataProvider
     * Note: SQLite does not properly work with non-ascii letters as search word for case-insensitive
     *       matching, UPPER() and LOWER() have the same issue, it only works with ascii letters.
     *       See: https://www.sqlite.org/src/doc/trunk/ext/icu/README.txt
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
                    self::markTestSkipped('Exculded platform ' . $excludePlatform);
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
        if (is_array($rows)) {
            $rows = $this->ensureRowsUidAsInteger($rows);
        }
        self::assertSame($expectedRows, $rows);
    }

    protected function ensureRowsUidAsInteger(array $rows): array
    {
        array_walk($rows, function (&$row) {
            if (array_key_exists('uid', $row)) {
                $row['uid'] = (int)$row['uid'];
            }
        });
        return $rows;
    }
}
