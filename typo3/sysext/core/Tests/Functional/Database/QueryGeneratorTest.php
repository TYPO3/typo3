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

namespace TYPO3\CMS\Core\Tests\Functional\Database;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\QueryGenerator;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class QueryGeneratorTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBackendUserFromFixture(1);
        $GLOBALS['LANG'] = LanguageService::create('default');
    }

    /**
     * @test
     */
    public function getTreeListReturnsIngoingIdIfDepthIsZero(): void
    {
        $id = 1;
        $depth = 0;

        $queryGenerator = new QueryGenerator();
        $treeList = $queryGenerator->getTreeList($id, $depth);

        self::assertSame($id, $treeList);
    }

    /**
     * @test
     */
    public function getTreeListReturnsIngoingIdIfIdIsZero(): void
    {
        $id = 0;
        $depth = 1;

        $queryGenerator = new QueryGenerator();
        $treeList = $queryGenerator->getTreeList($id, $depth);

        self::assertSame($id, $treeList);
    }

    /**
     * @test
     */
    public function getTreeListReturnsPositiveIngoingIdIfIdIsNegative(): void
    {
        $id = -1;
        $depth = 0;

        $queryGenerator = new QueryGenerator();
        $treeList = $queryGenerator->getTreeList($id, $depth);

        self::assertSame(1, $treeList);
    }

    /**
     * @test
     */
    public function getTreeListReturnsEmptyStringIfIdAndDepthAreZeroAndBeginDoesNotEqualZero(): void
    {
        $id = 0;
        $depth = 0;
        $begin = 1;

        $queryGenerator = new QueryGenerator();
        $treeList = $queryGenerator->getTreeList($id, $depth, $begin);

        self::assertSame('', $treeList);
    }

    /**
     * @test
     */
    public function getTreeListReturnsIncomingIdIfNoSubPageRecordsOfThatIdExist(): void
    {
        $id = 1;
        $depth = 1;

        $queryGenerator = new QueryGenerator();
        $treeList = $queryGenerator->getTreeList($id, $depth);

        self::assertSame($id, $treeList);
    }

    /**
     * @test
     */
    public function getTreeListRespectsPermClauses(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/TestGetPageTreeStraightTreeSet.csv');

        $id = 1;
        $depth = 99;

        $queryGenerator = new QueryGenerator();
        $treeList = $queryGenerator->getTreeList($id, $depth, 0, 'hidden = 0');

        self::assertSame('1,2,3,4,5', $treeList);
    }

    /**
     * @test
     * @dataProvider dataForGetTreeListReturnsListOfIdsWithBeginSetToZero
     * @param int $id
     * @param int $depth
     * @param mixed $expectation
     */
    public function getTreeListReturnsListOfIdsWithBeginSetToZero(int $id, int $depth, $expectation): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/TestGetPageTreeStraightTreeSet.csv');

        $queryGenerator = new QueryGenerator();
        $treeList = $queryGenerator->getTreeList($id, $depth);

        self::assertSame($expectation, $treeList);
    }

    /**
     * @return array
     */
    public function dataForGetTreeListReturnsListOfIdsWithBeginSetToZero(): array
    {
        return [
            // [$id, $depth, $expectation]
            [
                1,
                1,
                '1,2'
            ],
            [
                1,
                2,
                '1,2,3'
            ],
            [
                1,
                99,
                '1,2,3,4,5,6'
            ],
            [
                2,
                1,
                '2,3'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataForGetTreeListReturnsListOfIdsWithBeginSetToMinusOne
     * @param int $id
     * @param int $depth
     * @param mixed $expectation
     */
    public function getTreeListReturnsListOfIdsWithBeginSetToMinusOne(int $id, int $depth, $expectation): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/TestGetPageTreeStraightTreeSet.csv');

        $queryGenerator = new QueryGenerator();
        $treeList = $queryGenerator->getTreeList($id, $depth, -1);

        self::assertSame($expectation, $treeList);
    }

    /**
     * @return array
     */
    public function dataForGetTreeListReturnsListOfIdsWithBeginSetToMinusOne(): array
    {
        return [
            // [$id, $depth, $expectation]
            [
                1,
                1,
                ',2'
            ],
            [
                1,
                2,
                ',2,3'
            ],
            [
                1,
                99,
                ',2,3,4,5,6'
            ],
            [
                2,
                1,
                ',3'
            ],
        ];
    }

    /**
     * @test
     */
    public function getTreeListReturnsListOfPageIdsOfABranchedTreeWithBeginSetToZero(): void
    {
        $id = 1;
        $depth = 3;

        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/TestGetPageTreeBranchedTreeSet.csv');

        $queryGenerator = new QueryGenerator();
        $treeList = $queryGenerator->getTreeList($id, $depth);

        self::assertSame('1,2,3,4,5', $treeList);
    }

    /**
     * @test
     */
    public function getTreeListReturnsListOfPageIdsOfABranchedTreeWithBeginSetToOne(): void
    {
        $id = 1;
        $depth = 3;
        $begin = 1;

        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/TestGetPageTreeBranchedTreeSet.csv');

        $queryGenerator = new QueryGenerator();
        $treeList = $queryGenerator->getTreeList($id, $depth, $begin);

        self::assertSame('2,3,4,5', $treeList);
    }

    /**
     * @test
     */
    public function getTreeListReturnsListOfPageIdsOfABranchedTreeWithBeginSetToTwo(): void
    {
        $id = 1;
        $depth = 3;
        $begin = 2;

        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/TestGetPageTreeBranchedTreeSet.csv');

        $queryGenerator = new QueryGenerator();
        $treeList = $queryGenerator->getTreeList($id, $depth, $begin);

        self::assertSame('3,5', $treeList);
    }

    public function getQueryWithIdOrDateDataProvider(): array
    {
        return [
            'pid 5134' => [
                5134,
                null,
                "pid = '5134'",
            ],
            'unix timestamp' => [
                1522863047,
                null,
                "pid = '1522863047'",
            ],
            'pid 5134 as string' => [
                '5134',
                null,
                "pid = '5134'",
            ],
            'unix timestamp as string' => [
                '1522863047',
                null,
                "pid = '1522863047'",
            ],
            'ISO 8601 date string' => [
                '2018-04-04T17:30:47Z',
                null,
                "pid = '1522863047'",
            ],
            'pid 5134 and second input value 5135' => [
                5134,
                5135,
                'pid >= 5134 AND pid <= 5135',
                'comparison' => 100,
            ],
            'ISO 8601 date string as first and second input' => [
                '2018-04-04T17:30:47Z',
                '2018-04-04T17:30:48Z',
                'pid >= 1522863047 AND pid <= 1522863048',
                'comparison' => 100,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getQueryWithIdOrDateDataProvider
     *
     * @param mixed $inputValue
     * @param mixed $inputValue1
     * @param string $expected
     * @param int $comparison
     */
    public function getQueryWithIdOrDate($inputValue, $inputValue1, string $expected, int $comparison = 64)
    {
        $GLOBALS['TCA'] = [];
        $GLOBALS['TCA']['aTable'] = [];
        $queryGenerator = new QueryGenerator();

        $inputConf = [
            [
                'operator' => '',
                'type' => 'FIELD_pid',
                'comparison' => $comparison,
                'inputValue' => $inputValue,
                'inputValue1' => $inputValue1,
            ],
        ];

        $queryGenerator->init('queryConfig', 'aTable');
        self::assertSame($expected, trim($queryGenerator->getQuery($inputConf), "\n\r"));
    }

    public function arbitraryDataIsEscapedDataProvider(): array
    {
        $dataSet = [];
        $injectors = [
            // INJ'ECT
            'INJ%quoteCharacter%ECT',
            // INJ '--
            // ' ECT
            'INJ %quoteCharacter%%commentStart% %commentEnd%%quoteCharacter% ECT'
        ];
        $comparisons = array_keys((new QueryGenerator())->compSQL);
        foreach ($injectors as $injector) {
            foreach ($comparisons as $comparison) {
                $dataSet[] = [
                    $injector,
                    [
                        'queryTable' => 'tt_content',
                        'queryFields' => 'uid,' . $injector,
                        'queryGroup' => $injector,
                        'queryOrder' => $injector,
                        'queryLimit' => $injector,
                        'queryConfig' => serialize([
                            [
                                'operator' => $injector,
                                'type' => 'FIELD_category_field', // falls back to CType (first field)
                                'comparison' => $comparison,
                                'inputValue' => $injector,

                            ],
                            [
                                'operator' => $injector,
                                'type' => 'FIELD_category_field',
                                'comparison' => $comparison,
                                'inputValue' => $injector,

                            ],
                        ]),
                    ],
                ];
            }
        }
        return $dataSet;
    }

    /**
     * @param string $injector
     * @param array $settings
     *
     * @test
     * @dataProvider arbitraryDataIsEscapedDataProvider
     * @throws \Doctrine\DBAL\DBALException
     */
    public function arbitraryDataIsEscaped(string $injector, array $settings)
    {
        $databasePlatform = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tt_content')->getDatabasePlatform();
        $replacements = [
            '%quoteCharacter%' => $databasePlatform->getStringLiteralQuoteCharacter(),
            '%commentStart%' => $databasePlatform->getSqlCommentStartString(),
            '%commentEnd%' => $databasePlatform->getSqlCommentEndString()
        ];
        $injector = str_replace(array_keys($replacements), $replacements, $injector);
        $settings = $this->prepareSettings($settings, $replacements);

        $queryGenerator = new QueryGenerator();
        $queryGenerator->init('queryConfig', $settings['queryTable']);
        $queryGenerator->makeSelectorTable($settings);
        $queryGenerator->enablePrefix = true;

        $queryString = $queryGenerator->getQuery($queryGenerator->queryConfig);
        $query = $queryGenerator->getSelectQuery($queryString);

        self::assertStringNotContainsString($injector, $query);
    }

    protected function prepareSettings(array $settings, array $replacements): array
    {
        foreach ($settings as $settingKey => &$settingValue) {
            if (is_string($settingValue)) {
                $settingValue = str_replace(array_keys($replacements), $replacements, $settingValue);
            }
            if (is_array($settingValue)) {
                $settingValue = $this->prepareSettings($settingValue, $replacements);
            }
        }
        return $settings;
    }
}
