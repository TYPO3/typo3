<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Utility;

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

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Backend\Configuration\TsConfigParser;
use TYPO3\CMS\Backend\Tests\Unit\Utility\Fixtures\LabelFromItemListMergedReturnsCorrectFieldsFixture;
use TYPO3\CMS\Backend\Tests\Unit\Utility\Fixtures\ProcessedValueForGroupWithMultipleAllowedTablesFixture;
use TYPO3\CMS\Backend\Tests\Unit\Utility\Fixtures\ProcessedValueForGroupWithOneAllowedTableFixture;
use TYPO3\CMS\Backend\Tests\Unit\Utility\Fixtures\ProcessedValueForSelectWithMMRelationFixture;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DefaultRestrictionContainer;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher as SignalSlotDispatcher;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class BackendUtilityTest extends UnitTestCase
{
    /**
     * @var bool
     */
    protected $resetSingletonInstances = true;

    ///////////////////////////////////////
    // Tests concerning calcAge
    ///////////////////////////////////////
    /**
     * Data provider for calcAge function
     *
     * @return array
     */
    public function calcAgeDataProvider()
    {
        return [
            'Single year' => [
                'seconds' => 60 * 60 * 24 * 365,
                'expectedLabel' => '1 year'
            ],
            'Plural years' => [
                'seconds' => 60 * 60 * 24 * 365 * 2,
                'expectedLabel' => '2 yrs'
            ],
            'Single negative year' => [
                'seconds' => 60 * 60 * 24 * 365 * -1,
                'expectedLabel' => '-1 year'
            ],
            'Plural negative years' => [
                'seconds' => 60 * 60 * 24 * 365 * 2 * -1,
                'expectedLabel' => '-2 yrs'
            ],
            'Single day' => [
                'seconds' => 60 * 60 * 24,
                'expectedLabel' => '1 day'
            ],
            'Plural days' => [
                'seconds' => 60 * 60 * 24 * 2,
                'expectedLabel' => '2 days'
            ],
            'Single negative day' => [
                'seconds' => 60 * 60 * 24 * -1,
                'expectedLabel' => '-1 day'
            ],
            'Plural negative days' => [
                'seconds' => 60 * 60 * 24 * 2 * -1,
                'expectedLabel' => '-2 days'
            ],
            'Single hour' => [
                'seconds' => 60 * 60,
                'expectedLabel' => '1 hour'
            ],
            'Plural hours' => [
                'seconds' => 60 * 60 * 2,
                'expectedLabel' => '2 hrs'
            ],
            'Single negative hour' => [
                'seconds' => 60 * 60 * -1,
                'expectedLabel' => '-1 hour'
            ],
            'Plural negative hours' => [
                'seconds' => 60 * 60 * 2 * -1,
                'expectedLabel' => '-2 hrs'
            ],
            'Single minute' => [
                'seconds' => 60,
                'expectedLabel' => '1 min'
            ],
            'Plural minutes' => [
                'seconds' => 60 * 2,
                'expectedLabel' => '2 min'
            ],
            'Single negative minute' => [
                'seconds' => 60 * -1,
                'expectedLabel' => '-1 min'
            ],
            'Plural negative minutes' => [
                'seconds' => 60 * 2 * -1,
                'expectedLabel' => '-2 min'
            ],
            'Zero seconds' => [
                'seconds' => 0,
                'expectedLabel' => '0 min'
            ]
        ];
    }

    /**
     * @test
     * @dataProvider calcAgeDataProvider
     *
     * @param int $seconds
     * @param string $expectedLabel
     */
    public function calcAgeReturnsExpectedValues($seconds, $expectedLabel)
    {
        $this->assertSame($expectedLabel, BackendUtility::calcAge($seconds));
    }

    ///////////////////////////////////////
    // Tests concerning getProcessedValue
    ///////////////////////////////////////
    /**
     * @test
     * @see http://forge.typo3.org/issues/20994
     */
    public function getProcessedValueForZeroStringIsZero()
    {
        $GLOBALS['TCA'] = [
            'tt_content' => [
                'columns' => [
                    'header' => [
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                ],
            ],
        ];
        $GLOBALS['LANG'] = [];
        $this->assertEquals('0', BackendUtility::getProcessedValue('tt_content', 'header', '0'));
    }

    /**
     * @test
     */
    public function getProcessedValueForGroup()
    {
        $GLOBALS['TCA'] = [
            'tt_content' => [
                'columns' => [
                    'multimedia' => [
                        'config' => [
                            'type' => 'group',
                        ],
                    ],
                ],
            ],
        ];
        $GLOBALS['LANG'] = [];
        $this->assertSame('1, 2', BackendUtility::getProcessedValue('tt_content', 'multimedia', '1,2'));
    }

    /**
     * @test
     */
    public function getProcessedValueForGroupWithOneAllowedTable()
    {
        $GLOBALS['TCA'] = [
            'tt_content' => [
                'columns' => [
                    'pages' => [
                        'config' => [
                            'type' => 'group',
                            'allowed' => 'pages',
                            'internal_type' => 'db',
                            'maxitems' => 22,
                            'minitems' => 0,
                            'size' => 3,
                        ],
                    ],
                ],
            ],
        ];
        $GLOBALS['LANG'] = [];
        $this->assertSame('Page 1, Page 2', ProcessedValueForGroupWithOneAllowedTableFixture::getProcessedValue('tt_content', 'pages', '1,2'));
    }

    /**
     * @test
     */
    public function getProcessedValueForGroupWithMultipleAllowedTables()
    {
        $GLOBALS['TCA'] = [
            'index_config' => [
                'columns' => [
                    'indexcfgs' => [
                        'config' => [
                            'type' => 'group',
                            'internal_type' => 'db',
                            'allowed' => 'index_config,pages',
                            'size' => 5,
                        ],
                    ],
                ],
            ],
        ];
        $GLOBALS['LANG'] = [];
        $this->assertSame('Page 1, Configuration 2', ProcessedValueForGroupWithMultipleAllowedTablesFixture::getProcessedValue('index_config', 'indexcfgs', 'pages_1,index_config_2'));
    }

    /**
     * Prepare a mock database setup for a Doctrine connection
     * and return an array of all prophets to set expectations upon.
     *
     * @param string $tableName
     * @return array
     */
    protected function mockDatabaseConnection($tableName = 'sys_category')
    {
        $connectionProphet = $this->prophesize(Connection::class);
        $connectionProphet->quote(Argument::cetera())->will(function ($arguments) {
            return "'" . $arguments[0] . "'";
        });
        $connectionProphet->quoteIdentifier(Argument::cetera())->will(function ($arguments) {
            return '`' . $arguments[0] . '`';
        });

        $restrictionProphet = $this->prophesize(DefaultRestrictionContainer::class);
        $restrictionProphet->removeAll()->willReturn($restrictionProphet->reveal());
        $restrictionProphet->add(Argument::cetera())->willReturn($restrictionProphet->reveal());

        $queryBuilderProphet = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphet->expr()->willReturn(
            GeneralUtility::makeInstance(ExpressionBuilder::class, $connectionProphet->reveal())
        );
        $queryBuilderProphet->getRestrictions()->willReturn($restrictionProphet->reveal());
        $queryBuilderProphet->quoteIdentifier(Argument::cetera())->will(function ($arguments) {
            return '`' . $arguments[0] . '`';
        });

        $connectionPoolProphet = $this->prophesize(ConnectionPool::class);
        $connectionPoolProphet->getConnectionForTable($tableName)
            ->willReturn($connectionProphet->reveal());
        $connectionPoolProphet->getQueryBuilderForTable($tableName)
            ->shouldBeCalled()
            ->willReturn($queryBuilderProphet->reveal());

        return [$queryBuilderProphet, $connectionPoolProphet, $connectionProphet, $restrictionProphet];
    }

    /**
     * @test
     */
    public function getProcessedValueForSelectWithMMRelation()
    {
        /** @var RelationHandler|ObjectProphecy $relationHandlerProphet */
        $relationHandlerProphet = $this->prophesize(RelationHandler::class);
        $relationHandlerProphet->start(Argument::cetera())->shouldBeCalled();

        $relationHandlerInstance = $relationHandlerProphet->reveal();
        $relationHandlerInstance->tableArray['sys_category'] = [1, 2];

        list($queryBuilderProphet, $connectionPoolProphet) = $this->mockDatabaseConnection('sys_category');
        $statementProphet = $this->prophesize(\Doctrine\DBAL\Driver\Statement::class);
        $statementProphet->fetch()->shouldBeCalled()->willReturn(
            [
                'uid' => 1,
                'title' => 'Category 1',
            ],
            [
                'uid' => 2,
                'title' => 'Category 2',
            ],
            false
        );

        /** @var QueryBuilder|ObjectProphecy $queryBuilderProphet */
        $queryBuilderProphet->select('uid', 'sys_category.title')->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->from('sys_category')->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->where('`uid` IN (:dcValue1)')->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->createNamedParameter([1, 2], Connection::PARAM_INT_ARRAY)->willReturn(':dcValue1');
        $queryBuilderProphet->execute()->willReturn($statementProphet->reveal());

        GeneralUtility::addInstance(RelationHandler::class, $relationHandlerInstance);
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphet->reveal());

        $GLOBALS['TCA'] = [
            'pages' => [
                'columns' => [
                    'categories' => [
                        'config' => [
                            'type' => 'select',
                            'foreign_table' => 'sys_category',
                            'MM' => 'sys_category_record_mm',
                            'MM_match_fields' => [
                                'fieldname' => 'categories',
                                'tablesnames' => 'pages',
                            ],
                            'MM_opposite_field' => 'items',
                        ],
                    ],
                ],
            ],
            'sys_category' => [
                'ctrl' => ['label' => 'title'],
                'columns' => [
                    'items' => [
                        'config' => [
                            'type' => 'group',
                            'internal_type' => 'db',
                            'allowed' => '*',
                            'MM' => 'sys_category_record_mm',
                            'MM_oppositeUsage' => [],
                        ]
                    ]
                ],
            ],
        ];
        $GLOBALS['LANG'] = [];

        $this->assertSame(
            'Category 1; Category 2',
            ProcessedValueForSelectWithMMRelationFixture::getProcessedValue(
                'pages',
                'categories',
                '2',
                0,
                false,
                false,
                1
            )
        );
    }

    /**
     * @test
     */
    public function getProcessedValueDisplaysAgeForDateInputFieldsIfSettingAbsent()
    {
        /** @var ObjectProphecy $languageServiceProphecy */
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $languageServiceProphecy->sL(Argument::cetera())->willReturn(' min| hrs| days| yrs| min| hour| day| year');
        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();

        $GLOBALS['EXEC_TIME'] = mktime(0, 0, 0, 8, 30, 2015);

        $GLOBALS['TCA'] = [
            'tt_content' => [
                'columns' => [
                    'date' => [
                        'config' => [
                            'type' => 'input',
                            'eval' => 'date',
                        ],
                    ],
                ],
            ],
        ];
        $this->assertSame('28-08-15 (-2 days)', BackendUtility::getProcessedValue('tt_content', 'date', mktime(0, 0, 0, 8, 28, 2015)));
    }

    /**
     * @return array
     */
    public function inputTypeDateDisplayOptions()
    {
        return [
            'typeSafe Setting' => [
                true,
                '28-08-15',
            ],
            'non typesafe setting' => [
                1,
                '28-08-15',
            ],
            'setting disabled typesafe' => [
                false,
                '28-08-15 (-2 days)',
            ],
            'setting disabled not typesafe' => [
                0,
                '28-08-15 (-2 days)',
            ],
        ];
    }

    /**
     * @test
     *
     * @dataProvider inputTypeDateDisplayOptions
     *
     * @param string $input
     * @param string $expected
     */
    public function getProcessedValueHandlesAgeDisplayCorrectly($input, $expected)
    {
        /** @var ObjectProphecy $languageServiceProphecy */
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $languageServiceProphecy->sL(Argument::cetera())->willReturn(' min| hrs| days| yrs| min| hour| day| year');
        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();

        $GLOBALS['EXEC_TIME'] = mktime(0, 0, 0, 8, 30, 2015);

        $GLOBALS['TCA'] = [
            'tt_content' => [
                'columns' => [
                    'date' => [
                        'config' => [
                            'type' => 'input',
                            'eval' => 'date',
                            'disableAgeDisplay' => $input,
                        ],
                    ],
                ],
            ],
        ];
        $this->assertSame($expected, BackendUtility::getProcessedValue('tt_content', 'date', mktime(0, 0, 0, 8, 28, 2015)));
    }

    /**
     * @test
     */
    public function getProcessedValueForCheckWithSingleItem()
    {
        $GLOBALS['TCA'] = [
            'tt_content' => [
                'columns' => [
                    'hide' => [
                        'config' => [
                            'type' => 'check',
                            'items' => [
                                [
                                    0 => '',
                                    1 => '',
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $languageServiceProphecy = $this->prophesize(\TYPO3\CMS\Core\Localization\LanguageService::class);
        $languageServiceProphecy->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:yes')->willReturn('Yes');
        $languageServiceProphecy->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:no')->willReturn('No');
        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();
        $this->assertSame('Yes', BackendUtility::getProcessedValue('tt_content', 'hide', 1));
    }

    /**
     * @test
     */
    public function getProcessedValueForCheckWithSingleItemInvertStateDisplay()
    {
        $GLOBALS['TCA'] = [
            'tt_content' => [
                'columns' => [
                    'hide' => [
                        'config' => [
                            'type' => 'check',
                            'items' => [
                                [
                                    0 => '',
                                    1 => '',
                                    'invertStateDisplay' => true,
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $languageServiceProphecy = $this->prophesize(\TYPO3\CMS\Core\Localization\LanguageService::class);
        $languageServiceProphecy->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:yes')->willReturn('Yes');
        $languageServiceProphecy->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:no')->willReturn('No');
        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();
        $this->assertSame('No', BackendUtility::getProcessedValue('tt_content', 'hide', 1));
    }

    /**
     * Tests concerning getCommonSelectFields
     */

    /**
     * Data provider for getCommonSelectFieldsReturnsCorrectFields
     *
     * @return array The test data with $table, $prefix, $presetFields, $tca, $expectedFields
     */
    public function getCommonSelectFieldsReturnsCorrectFieldsDataProvider()
    {
        return [
            'only uid' => [
                'table' => 'test_table',
                'prefix' => '',
                'presetFields' => [],
                'tca' => [],
                'expectedFields' => 'uid'
            ],
            'label set' => [
                'table' => 'test_table',
                'prefix' => '',
                'presetFields' => [],
                'tca' => [
                    'ctrl' => [
                        'label' => 'label'
                    ]
                ],
                'expectedFields' => 'uid,label'
            ],
            'label_alt set' => [
                'table' => 'test_table',
                'prefix' => '',
                'presetFields' => [],
                'tca' => [
                    'ctrl' => [
                        'label_alt' => 'label,label2'
                    ]
                ],
                'expectedFields' => 'uid,label,label2'
            ],
            'versioningWS set' => [
                'table' => 'test_table',
                'prefix' => '',
                'presetFields' => [],
                'tca' => [
                    'ctrl' => [
                        'versioningWS' => true
                    ]
                ],
                'expectedFields' => 'uid,t3ver_id,t3ver_state,t3ver_wsid,t3ver_count'
            ],
            'selicon_field set' => [
                'table' => 'test_table',
                'prefix' => '',
                'presetFields' => [],
                'tca' => [
                    'ctrl' => [
                        'selicon_field' => 'field'
                    ]
                ],
                'expectedFields' => 'uid,field'
            ],
            'typeicon_column set' => [
                'table' => 'test_table',
                'prefix' => '',
                'presetFields' => [],
                'tca' => [
                    'ctrl' => [
                        'typeicon_column' => 'field'
                    ]
                ],
                'expectedFields' => 'uid,field'
            ],
            'enablecolumns set' => [
                'table' => 'test_table',
                'prefix' => '',
                'presetFields' => [],
                'tca' => [
                    'ctrl' => [
                        'enablecolumns' => [
                            'disabled' => 'hidden',
                            'starttime' => 'start',
                            'endtime' => 'stop',
                            'fe_group' => 'groups'
                        ]
                    ]
                ],
                'expectedFields' => 'uid,hidden,start,stop,groups'
            ],
            'label set to uid' => [
                'table' => 'test_table',
                'prefix' => '',
                'presetFields' => [],
                'tca' => [
                    'ctrl' => [
                        'label' => 'uid'
                    ]
                ],
                'expectedFields' => 'uid'
            ]
        ];
    }

    /**
     * @test
     * @dataProvider getCommonSelectFieldsReturnsCorrectFieldsDataProvider
     *
     * @param string $table
     * @param string $prefix
     * @param array $presetFields
     * @param array $tca
     * @param string $expectedFields
     */
    public function getCommonSelectFieldsReturnsCorrectFields($table, $prefix = '', array $presetFields, array $tca, $expectedFields = '')
    {
        $GLOBALS['TCA'][$table] = $tca;
        $selectFields = BackendUtility::getCommonSelectFields($table, $prefix, $presetFields);
        $this->assertEquals($selectFields, $expectedFields);
    }

    /**
     * Tests concerning getLabelFromItemlist
     */

    /**
     * Data provider for getLabelFromItemlistReturnsCorrectFields
     *
     * @return array The test data with $table, $col, $key, $expectedLabel
     */
    public function getLabelFromItemlistReturnsCorrectFieldsDataProvider()
    {
        return [
            'item set' => [
                'table' => 'tt_content',
                'col' => 'menu_type',
                'key' => '1',
                'tca' => [
                    'columns' => [
                        'menu_type' => [
                            'config' => [
                                'items' => [
                                    ['Item 1', '0'],
                                    ['Item 2', '1'],
                                    ['Item 3', '3']
                                ]
                            ]
                        ]
                    ]
                ],
                'expectedLabel' => 'Item 2'
            ],
            'item set twice' => [
                'table' => 'tt_content',
                'col' => 'menu_type',
                'key' => '1',
                'tca' => [
                    'columns' => [
                        'menu_type' => [
                            'config' => [
                                'items' => [
                                    ['Item 1', '0'],
                                    ['Item 2a', '1'],
                                    ['Item 2b', '1'],
                                    ['Item 3', '3']
                                ]
                            ]
                        ]
                    ]
                ],
                'expectedLabel' => 'Item 2a'
            ],
            'item not found' => [
                'table' => 'tt_content',
                'col' => 'menu_type',
                'key' => '5',
                'tca' => [
                    'columns' => [
                        'menu_type' => [
                            'config' => [
                                'items' => [
                                    ['Item 1', '0'],
                                    ['Item 2', '1'],
                                    ['Item 3', '2']
                                ]
                            ]
                        ]
                    ]
                ],
                'expectedLabel' => null
            ]
        ];
    }

    /**
     * @test
     * @dataProvider getLabelFromItemlistReturnsCorrectFieldsDataProvider
     *
     * @param string $table
     * @param string $col
     * @param string $key
     * @param array $tca
     * @param string $expectedLabel
     */
    public function getLabelFromItemlistReturnsCorrectFields($table, $col = '', $key = '', array $tca, $expectedLabel = '')
    {
        $GLOBALS['TCA'][$table] = $tca;
        $label = BackendUtility::getLabelFromItemlist($table, $col, $key);
        $this->assertEquals($label, $expectedLabel);
    }

    /**
     * Tests concerning getLabelFromItemListMerged
     */

    /**
     * Data provider for getLabelFromItemListMerged
     *
     * @return array The test data with $pageId, $table, $column, $key, $expectedLabel
     */
    public function getLabelFromItemListMergedReturnsCorrectFieldsDataProvider()
    {
        return [
            'no field found' => [
                'pageId' => '123',
                'table' => 'tt_content',
                'col' => 'menu_type',
                'key' => '10',
                'tca' => [
                    'columns' => [
                        'menu_type' => [
                            'config' => [
                                'items' => [
                                    ['Item 1', '0'],
                                    ['Item 2', '1'],
                                    ['Item 3', '3']
                                ]
                            ]
                        ]
                    ]
                ],
                'expectedLabel' => ''
            ],
            'no tsconfig set' => [
                'pageId' => '123',
                'table' => 'tt_content',
                'col' => 'menu_type',
                'key' => '1',
                'tca' => [
                    'columns' => [
                        'menu_type' => [
                            'config' => [
                                'items' => [
                                    ['Item 1', '0'],
                                    ['Item 2', '1'],
                                    ['Item 3', '3']
                                ]
                            ]
                        ]
                    ]
                ],
                'expectedLabel' => 'Item 2'
            ]
        ];
    }

    /**
     * @test
     * @dataProvider getLabelFromItemListMergedReturnsCorrectFieldsDataProvider
     *
     * @param int $pageId
     * @param string $table
     * @param string $column
     * @param string $key
     * @param array $tca
     * @param string $expectedLabel
     */
    public function getLabelFromItemListMergedReturnsCorrectFields($pageId, $table, $column = '', $key = '', array $tca, $expectedLabel = '')
    {
        $GLOBALS['TCA'][$table] = $tca;

        $this->assertEquals($expectedLabel, LabelFromItemListMergedReturnsCorrectFieldsFixture::getLabelFromItemListMerged($pageId, $table, $column, $key));
    }

    /**
     * Tests concerning getFuncCheck
     */

    /**
     * @test
     */
    public function getFuncCheckReturnsInputTagWithValueAttribute()
    {
        $this->assertStringMatchesFormat('<input %Svalue="1"%S/>', BackendUtility::getFuncCheck('params', 'test', true));
    }

    /*
     * Tests concerning getLabelsFromItemsList
     */

    /**
     * @return array
     */
    public function getLabelsFromItemsListDataProvider()
    {
        return [
            'return value if found' => [
                'foobar', // table
                'someColumn', // col
                'foo, bar', // keyList
                [ // TCA
                    'columns' => [
                        'someColumn' => [
                            'config' => [
                                'items' => [
                                    '0' => ['aFooLabel', 'foo'],
                                    '1' => ['aBarLabel', 'bar']
                                ]
                            ]
                        ]
                    ]
                ],
                [], // page TSconfig
                'aFooLabel, aBarLabel' // expected
            ],
            'page TSconfig overrules TCA' => [
                'foobar', // table
                'someColumn', // col
                'foo,bar, add', // keyList
                [ // TCA
                    'columns' => [
                        'someColumn' => [
                            'config' => [
                                'items' => [
                                    '0' => ['aFooLabel', 'foo'],
                                    '1' => ['aBarLabel', 'bar']
                                ]
                            ]
                        ]
                    ]
                ],
                [ // page TSconfig
                    'addItems.' => ['add' => 'aNewLabel'],
                    'altLabels.' => ['bar' => 'aBarDiffLabel'],
                ],
                'aFooLabel, aBarDiffLabel, aNewLabel' // expected
            ]
        ];
    }

    /**
     * @test
     * @dataProvider getLabelsFromItemsListDataProvider
     *
     * @param string $table
     * @param string $col
     * @param string $keyList
     * @param array $tca
     * @param array $pageTsConfig
     * @param string $expectedLabel
     */
    public function getLabelsFromItemsListReturnsCorrectValue($table, $col, $keyList, $tca, array $pageTsConfig, $expectedLabel)
    {
        // Stub LanguageService and let sL() return the same value that came in again
        $GLOBALS['LANG'] = $this->createMock(LanguageService::class);
        $GLOBALS['LANG']->expects($this->any())->method('sL')->will($this->returnArgument(0));

        $GLOBALS['TCA'][$table] = $tca;
        $label = BackendUtility::getLabelsFromItemsList($table, $col, $keyList, $pageTsConfig);
        $this->assertEquals($expectedLabel, $label);
    }

    /**
     * @test
     */
    public function getProcessedValueReturnsLabelsForExistingValuesSolely()
    {
        $table = 'foobar';
        $col = 'someColumn';
        $tca = [
            'columns' => [
                'someColumn' => [
                    'config' => [
                        'type' => 'select',
                        'items' => [
                            '0' => ['aFooLabel', 'foo'],
                            '1' => ['aBarLabel', 'bar']
                        ]
                    ]
                ]
            ]
        ];
        // Stub LanguageService and let sL() return the same value that came in again
        $GLOBALS['LANG'] = $this->createMock(LanguageService::class);
        $GLOBALS['LANG']->expects($this->any())->method('sL')->will($this->returnArgument(0));

        $GLOBALS['TCA'][$table] = $tca;
        $label = BackendUtility::getProcessedValue($table, $col, 'foo,invalidKey,bar');
        $this->assertEquals('aFooLabel, aBarLabel', $label);
    }

    /**
     * @test
     */
    public function getProcessedValueReturnsPlainValueIfItemIsNotFound()
    {
        $table = 'foobar';
        $col = 'someColumn';
        $tca = [
            'columns' => [
                'someColumn' => [
                    'config' => [
                        'type' => 'select',
                        'items' => [
                            '0' => ['aFooLabel', 'foo']
                        ]
                    ]
                ]
            ]
        ];
        // Stub LanguageService and let sL() return the same value that came in again
        $GLOBALS['LANG'] = $this->createMock(LanguageService::class);
        $GLOBALS['LANG']->expects($this->any())->method('sL')->will($this->returnArgument(0));

        $GLOBALS['TCA'][$table] = $tca;
        $label = BackendUtility::getProcessedValue($table, $col, 'invalidKey');
        $this->assertEquals('invalidKey', $label);
    }

    /**
     * Tests concerning viewOnClick
     */

    /**
     * @test
     */
    public function viewOnClickReturnsOnClickCodeWithAlternativeUrl()
    {
        // Make sure the hook inside viewOnClick is not fired. This may be removed if unit tests
        // bootstrap does not initialize TYPO3_CONF_VARS anymore.
        unset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['viewOnClickClass']);

        $alternativeUrl = 'https://typo3.org/about/typo3-the-cms/the-history-of-typo3/#section';
        $onclickCode = 'var previewWin = window.open(' . GeneralUtility::quoteJSvalue($alternativeUrl) . ',\'newTYPO3frontendWindow\');' . LF
            . 'if (previewWin.location.href === ' . GeneralUtility::quoteJSvalue($alternativeUrl) . ') { previewWin.location.reload(); };';
        $this->assertStringMatchesFormat(
            $onclickCode,
            BackendUtility::viewOnClick(null, null, null, null, $alternativeUrl, null, false)
        );
    }

    /**
     * @test
     */
    public function dateTimeAgeReturnsCorrectValues()
    {
        /** @var ObjectProphecy|LanguageService $languageServiceProphecy */
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $languageServiceProphecy->sL(Argument::cetera())->willReturn(' min| hrs| days| yrs| min| hour| day| year');
        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();
        $GLOBALS['EXEC_TIME'] = mktime(0, 0, 0, 3, 23, 2016);

        $this->assertSame('24-03-16 00:00 (-1 day)', BackendUtility::dateTimeAge($GLOBALS['EXEC_TIME'] + 86400));
        $this->assertSame('24-03-16 (-1 day)', BackendUtility::dateTimeAge($GLOBALS['EXEC_TIME'] + 86400, 1, 'date'));
    }

    /**
     * @test
     */
    public function purgeComputedPropertyNamesRemovesPropertiesStartingWithUnderscore()
    {
        $propertyNames = [
            'uid',
            'pid',
            '_ORIG_PID'
        ];
        $computedPropertyNames = BackendUtility::purgeComputedPropertyNames($propertyNames);
        self::assertSame(['uid', 'pid'], $computedPropertyNames);
    }

    /**
     * @test
     */
    public function purgeComputedPropertiesFromRecordRemovesPropertiesStartingWithUnderscore()
    {
        $record = [
            'uid'       => 1,
            'pid'       => 2,
            '_ORIG_PID' => 1
        ];
        $expected = [
            'uid' => 1,
            'pid' => 2
        ];
        $computedProperties = BackendUtility::purgeComputedPropertiesFromRecord($record);
        self::assertSame($expected, $computedProperties);
    }

    public function splitTableUidDataProvider()
    {
        return [
            'simple' => [
                'pages_23',
                ['pages', '23']
            ],
            'complex' => [
                'tt_content_13',
                ['tt_content', '13']
            ],
            'multiple underscores' => [
                'tx_runaway_domain_model_crime_scene_1234',
                ['tx_runaway_domain_model_crime_scene', '1234']
            ]
        ];
    }

    /**
     * @test
     * @dataProvider splitTableUidDataProvider
     */
    public function splitTableUid($input, $expected)
    {
        $result = BackendUtility::splitTable_Uid($input);
        self::assertSame($expected, $result);
    }

    /**
     * Tests if the method getPagesTSconfig can be called without having a GLOBAL['BE_USER'] object.
     * However, this test also shows all the various other dependencies this method has.
     *
     * @test
     */
    public function getPagesTSconfigWorksWithoutInitializedBackendUser()
    {
        $expected = ['called.' => ['config']];
        $pageId = 13;
        $parserProphecy = $this->prophesize(TsConfigParser::class);
        $parserProphecy->parseTSconfig(Argument::cetera())->willReturn(['hash' => $pageId, 'TSconfig' => $expected]);
        GeneralUtility::addInstance(TsConfigParser::class, $parserProphecy->reveal());

        $cacheManagerProphecy = $this->prophesize(CacheManager::class);
        $cacheProphecy = $this->prophesize(FrontendInterface::class);
        $cacheManagerProphecy->getCache('cache_runtime')->willReturn($cacheProphecy->reveal());
        $cacheHashProphecy = $this->prophesize(FrontendInterface::class);
        $cacheManagerProphecy->hasCache('extbase')->willReturn(false);
        $cacheManagerProphecy->getCache('cache_hash')->willReturn($cacheHashProphecy->reveal());
        $cacheProphecy->has(Argument::cetera())->willReturn(false);
        $cacheProphecy->get(Argument::cetera())->willReturn(false);
        $cacheProphecy->set(Argument::cetera())->willReturn(false);
        $cacheProphecy->get('backendUtilityBeGetRootLine')->willReturn(['13--1' => []]);
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerProphecy->reveal());
        $signalSlotDispatcherProphecy = $this->prophesize(SignalSlotDispatcher::class);
        $signalSlotDispatcherProphecy->dispatch(Argument::any(), Argument::any(), Argument::type('array'))->willReturnArgument(2);
        GeneralUtility::setSingletonInstance(SignalSlotDispatcher::class, $signalSlotDispatcherProphecy->reveal());

        $result = BackendUtility::getPagesTSconfig($pageId);
        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function returnNullForMissingTcaConfigInResolveFileReferences()
    {
        $tableName = 'table_a';
        $fieldName = 'field_a';
        $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'] = [];
        $this->assertNull(BackendUtility::resolveFileReferences($tableName, $fieldName, []));
    }

    /**
     * @test
     */
    public function fixVersioningPidDoesNotChangeValuesForNoBeUserAvailable()
    {
        $GLOBALS['BE_USER'] = null;
        $tableName = 'table_a';
        $GLOBALS['TCA'][$tableName]['ctrl']['versioningWS'] = 'not_empty';
        $rr = [
            'pid' => -1,
            't3ver_oid' => 7,
            't3ver_wsid' => 42,
        ];
        $reference = $rr;
        BackendUtility::fixVersioningPid($tableName, $rr);
        $this->assertSame($reference, $rr);
    }

    /**
     * @test
     * @dataProvider unfitResolveFileReferencesTableConfig
     */
    public function returnNullForUnfitTableConfigInResolveFileReferences(array $config)
    {
        $tableName = 'table_a';
        $fieldName = 'field_a';
        $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'] = $config;
        $this->assertNull(BackendUtility::resolveFileReferences($tableName, $fieldName, []));
    }

    public function unfitResolveFileReferencesTableConfig(): array
    {
        return [
            'invalid table' => [
                [
                    'type' => 'inline',
                    'foreign_table' => 'table_b',
                ],
            ],
            'empty table' => [
                [
                    'type' => 'inline',
                    'foreign_table' => '',
                ],
            ],
            'invalid type' => [
                [
                    'type' => 'select',
                    'foreign_table' => 'sys_file_reference',
                ],
            ],
            'empty type' => [
                [
                    'type' => '',
                    'foreign_table' => 'sys_file_reference',
                ],
            ],
            'empty' => [
                [
                    'type' => '',
                    'foreign_table' => '',
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function workspaceOLDoesNotChangeValuesForNoBeUserAvailable()
    {
        $GLOBALS['BE_USER'] = null;
        $tableName = 'table_a';
        $row = [
            'uid' => 1,
            'pid' => 17,
        ];
        $reference = $row;
        BackendUtility::workspaceOL($tableName, $row);
        $this->assertSame($reference, $row);
    }

    /**
     * @test
     */
    public function versioningPlaceholderClauseReturnsEmptyIfNoBeUserIsAvailable()
    {
        $GLOBALS['BE_USER'] = null;
        $tableName = 'table_a';
        $GLOBALS['TCA'][$tableName]['ctrl']['versioningWS'] = 'not_empty';
        $this->assertSame('', BackendUtility::versioningPlaceholderClause($tableName));
    }

    /**
     * @test
     */
    public function resolveFileReferencesReturnsEmptyResultForNoReferencesAvailable()
    {
        $tableName = 'table_a';
        $fieldName = 'field_a';
        $relationHandler = $this->prophesize(RelationHandler::class);
        $relationHandler->start(
            'foo',
            'sys_file_reference',
            '',
            42,
            $tableName,
            ['type' => 'inline', 'foreign_table' => 'sys_file_reference']
        )->shouldBeCalled();
        $relationHandler->tableArray = ['sys_file_reference' => []];
        $relationHandler->processDeletePlaceholder()->shouldBeCalled();
        GeneralUtility::addInstance(RelationHandler::class, $relationHandler->reveal());
        $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'] = [
            'type' => 'inline',
            'foreign_table' => 'sys_file_reference',
        ];
        $elementData = [
            $fieldName => 'foo',
            'uid' => 42,
        ];

        $this->assertEmpty(BackendUtility::resolveFileReferences($tableName, $fieldName, $elementData));
    }

    /**
     * @test
     */
    public function getWorkspaceWhereClauseReturnsEmptyIfNoBeUserIsAvailable()
    {
        $GLOBALS['BE_USER'] = null;
        $tableName = 'table_a';
        $GLOBALS['TCA'][$tableName]['ctrl']['versioningWS'] = 'not_empty';
        $this->assertSame('', BackendUtility::getWorkspaceWhereClause($tableName));
    }

    /**
     * @test
     */
    public function wsMapIdReturnsLiveIdIfNoBeUserIsAvailable()
    {
        $GLOBALS['BE_USER'] = null;
        $tableName = 'table_a';
        $uid = 42;
        $this->assertSame(42, BackendUtility::wsMapId($tableName, $uid));
    }

    /**
     * @test
     */
    public function getMovePlaceholderReturnsFalseIfNoBeUserIsAvailable()
    {
        $GLOBALS['BE_USER'] = null;
        $tableName = 'table_a';
        $this->assertFalse(BackendUtility::getMovePlaceholder($tableName, 42));
    }
}
