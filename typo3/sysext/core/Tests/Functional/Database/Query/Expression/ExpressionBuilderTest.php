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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class ExpressionBuilderTest extends FunctionalTestCase
{
    /**
     * @var array Extension comes with table setup to test inSet() methods of ExpressionBuilder
     */
    protected $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Database/Fixtures/Extensions/test_expressionbuilder',
    ];

    /**
     * @test
     */
    public function inSetReturnsExpectedDataSetsWithColumn()
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
            ->execute()
            ->fetchAll();
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
    public function inSetReturnsExpectedDataSets()
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
            ->execute()
            ->fetchAll();
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
    public function inSetReturnsExpectedDataSetsWithInts()
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
            ->execute()
            ->fetchAll();
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
    public function inSetReturnsExpectedDataSetsIfValueContainsLikeWildcard()
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
            ->execute()
            ->fetchAll();
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
    public function inSetReturnsExpectedDataSetsIfValueContainsBracket()
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
            ->execute()
            ->fetchAll();
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
    public function inSetReturnsExpectedDataSetsIfValueContainsClosingBracket()
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
            ->execute()
            ->fetchAll();
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
    public function inSetReturnsExpectedDataSetsIfValueContainsOpeningAndClosingBracket()
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
            ->execute()
            ->fetchAll();
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
    public function inSetReturnsExpectedDataSetsIfValueContainsBracketsAroundWord()
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
            ->execute()
            ->fetchAll();
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
    public function inSetReturnsExpectedDataSetsIfValueContainsBracketsAroundLikeWildcard()
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
            ->execute()
            ->fetchAll();
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
}
