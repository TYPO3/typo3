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
use TYPO3\CMS\Backend\Tests\Unit\Utility\Fixtures\BackendUtilityFixture;
use TYPO3\CMS\Backend\Tests\Unit\Utility\Fixtures\LabelFromItemListMergedReturnsCorrectFieldsFixture;
use TYPO3\CMS\Backend\Tests\Unit\Utility\Fixtures\ProcessedValueForGroupWithMultipleAllowedTablesFixture;
use TYPO3\CMS\Backend\Tests\Unit\Utility\Fixtures\ProcessedValueForGroupWithOneAllowedTableFixture;
use TYPO3\CMS\Backend\Tests\Unit\Utility\Fixtures\ProcessedValueForSelectWithMMRelationFixture;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Test case
 */
class BackendUtilityTest extends UnitTestCase
{
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
                            'show_thumbs' => 1,
                            'size' => 3,
                        ],
                    ],
                ],
            ],
        ];

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

        $this->assertSame('Page 1, Configuration 2', ProcessedValueForGroupWithMultipleAllowedTablesFixture::getProcessedValue('index_config', 'indexcfgs', 'pages_1,index_config_2'));
    }

    /**
     * @test
     */
    public function getProcessedValueForSelectWithMMRelation()
    {
        $GLOBALS['TYPO3_DB'] = $this->getMock(DatabaseConnection::class, [], [], '', false);
        $GLOBALS['TYPO3_DB']->expects($this->any())->method('fullQuoteStr')
            ->will($this->returnCallback(
                function ($quoteStr) {
                    return "'" . $quoteStr . "'";
                }
            )
            );
        $GLOBALS['TYPO3_DB']->expects($this->any())->method('exec_SELECTquery')->will($this->returnValue(0));
        $GLOBALS['TYPO3_DB']->expects($this->any())->method('sql_free_result');
        $GLOBALS['TYPO3_DB']->expects($this->any())->method('sql_fetch_assoc')
            ->will($this->returnCallback(
                function () {
                    static $called = 0;
                    ++$called;
                    switch ($called) {
                        // SELECT * FROM sys_category_record_mm
                        case 1:
                            return [
                                'uid_local' => 1,    // uid of a sys_category record
                                'uid_foreign' => 1,    // uid of a pages record
                            ];
                        case 2:
                            return [
                                'uid_local' => 2,    // uid of a sys_category record
                                'uid_foreign' => 1,    // uid of a pages record
                            ];
                        case 3:
                            return null;
                        // SELECT * FROM sys_catgory
                        case 4:
                            return [
                                'uid' => 1,
                                'title' => 'Category 1',
                            ];
                        case 5:
                            return [
                                'uid' => 2,
                                'title' => 'Category 2',
                            ];
                        case 6:
                            return null;
                    }
                    return null;
                }
            )
            );

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

        $this->assertSame('Category 1; Category 2', ProcessedValueForSelectWithMMRelationFixture::getProcessedValue('pages', 'categories', '2', 0, false, false, 1));
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
        $GLOBALS['LANG'] = $this->getMock(LanguageService::class, [], [], '', false);
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
        $GLOBALS['LANG'] = $this->getMock(LanguageService::class, [], [], '', false);
        $GLOBALS['LANG']->charSet = 'utf-8';
        $GLOBALS['LANG']->csConvObj = $this->getMock(CharsetConverter::class);
        $GLOBALS['LANG']->expects($this->any())->method('sL')->will($this->returnArgument(0));

        $GLOBALS['LANG']->csConvObj->expects($this->any())->method('crop')->will($this->returnArgument(1));

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
        $GLOBALS['LANG'] = $this->getMock(LanguageService::class, [], [], '', false);
        $GLOBALS['LANG']->charSet = 'utf-8';
        $GLOBALS['LANG']->csConvObj = $this->getMock(CharsetConverter::class);
        $GLOBALS['LANG']->expects($this->any())->method('sL')->will($this->returnArgument(0));

        $GLOBALS['LANG']->csConvObj->expects($this->any())->method('crop')->will($this->returnArgument(1));

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
        $onclickCode = 'var previewWin = window.open(' . GeneralUtility::quoteJSvalue($alternativeUrl) . ',\'newTYPO3frontendWindow\');';
        $this->assertStringMatchesFormat(
            $onclickCode,
            BackendUtility::viewOnClick(null, null, null, null, $alternativeUrl, null, false)
        );
    }

    /**
     * @test
     */
    public function getModTSconfigIgnoresValuesFromUserTsConfigIfNoSet()
    {
        $completeConfiguration = [
            'value' => 'bar',
            'properties' => [
                'permissions.' => [
                    'file.' => [
                        'default.' => ['readAction' => '1'],
                        '1.' => ['writeAction' => '1'],
                        '0.' => ['readAction' => '0'],
                    ],
                ]
            ]
        ];

        $GLOBALS['BE_USER'] = $this->getMock(BackendUserAuthentication::class, [], [], '', false);
        $GLOBALS['BE_USER']->expects($this->at(0))->method('getTSConfig')->will($this->returnValue($completeConfiguration));
        $GLOBALS['BE_USER']->expects($this->at(1))->method('getTSConfig')->will($this->returnValue(['value' => null, 'properties' => null]));

        $this->assertSame($completeConfiguration, BackendUtilityFixture::getModTSconfig(42, 'notrelevant'));
    }

    /**
     * Data provider for replaceL10nModeFieldsReplacesFields
     *
     * @return array
     */
    public function replaceL10nModeFieldsReplacesFieldsDataProvider()
    {
        return [
            'same table: mergeIfNotBlank' => [
                'foo',
                [
                    'origUid' => 1,
                    'field2' => 'fdas',
                    'field3' => 'trans',
                ],
                [
                    'foo' => [
                        'ctrl' => [
                            'transOrigPointerTable' => '',
                            'transOrigPointerField' => 'origUid'
                        ],
                        'columns' => [
                            'field2' => ['l10n_mode' => 'mergeIfNotBlank'],
                            'field3' => ['l10n_mode' => 'mergeIfNotBlank']
                        ]
                    ]
                ],
                [
                    'origUid' => 0,
                    'field2' => 'basic',
                    'field3' => '',
                ],
                [
                    'origUid' => 1,
                    'field2' => 'fdas',
                    'field3' => 'trans',
                ]
            ],
            'other table: mergeIfNotBlank' => [
                'foo',
                [
                    'origUid' => 1,
                    'field2' => '',
                    'field3' => 'trans',
                ],
                [
                    'foo' => [
                        'ctrl' => [
                            'transOrigPointerTable' => 'bar',
                            'transOrigPointerField' => 'origUid'
                        ]
                    ],
                    'bar' => [
                        'columns' => [
                            'field2' => ['l10n_mode' => 'mergeIfNotBlank'],
                            'field3' => ['l10n_mode' => 'mergeIfNotBlank']
                        ]
                    ]
                ],
                [
                    'origUid' => 0,
                    'field2' => 'basic',
                    'field3' => '',
                ],
                [
                    'origUid' => 1,
                    'field2' => 'basic',
                    'field3' => 'trans',
                ]
            ],
            'same table: exclude' => [
                'foo',
                [
                    'origUid' => 1,
                    'field2' => 'fdas',
                    'field3' => 'trans',
                ],
                [
                    'foo' => [
                        'ctrl' => [
                            'transOrigPointerTable' => '',
                            'transOrigPointerField' => 'origUid'
                        ],
                        'columns' => [
                            'field2' => ['l10n_mode' => 'exclude'],
                            'field3' => ['l10n_mode' => 'exclude']
                        ]
                    ]
                ],
                [
                    'origUid' => 0,
                    'field2' => 'basic',
                    'field3' => '',
                ],
                [
                    'origUid' => 1,
                    'field2' => 'basic',
                    'field3' => '',
                ]
            ],
            'other table: exclude' => [
                'foo',
                [
                    'origUid' => 1,
                    'field2' => 'fdas',
                    'field3' => 'trans',
                ],
                [
                    'foo' => [
                        'ctrl' => [
                            'transOrigPointerTable' => 'bar',
                            'transOrigPointerField' => 'origUid'
                        ]
                    ],
                    'bar' => [
                        'columns' => [
                            'field2' => ['l10n_mode' => 'exclude'],
                            'field3' => ['l10n_mode' => 'exclude']
                        ]
                    ]
                ],
                [
                    'origUid' => 0,
                    'field2' => 'basic',
                    'field3' => '',
                ],
                [
                    'origUid' => 1,
                    'field2' => 'basic',
                    'field3' => '',
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider replaceL10nModeFieldsReplacesFieldsDataProvider
     *
     * @param string $table
     * @param array $row
     * @param array $tca
     * @param array $originalRow
     * @param array $expected
     *
     * @throws \InvalidArgumentException
     * @throws \PHPUnit_Framework_Exception
     */
    public function replaceL10nModeFieldsReplacesFields($table, array $row, array $tca, array $originalRow, $expected)
    {
        $GLOBALS['TCA'] = $tca;
        $GLOBALS['TYPO3_DB'] = $this->getMock(DatabaseConnection::class);
        $GLOBALS['TYPO3_DB']->expects($this->any())->method('exec_SELECTgetSingleRow')->will($this->returnValue($originalRow));

        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|BackendUtility $subject */
        $subject = $this->getAccessibleMock(BackendUtility::class, ['dummy']);
        $this->assertSame($expected, $subject->_call('replaceL10nModeFields', $table, $row));
    }

    /**
     * @test
     */
    public function getSpecConfPartsSplitsDefaultExtras()
    {
        $defaultExtras = 'nowrap:wizards[foo|bar]:anotherDefaultExtras:some[other|setting|with|parameters]';
        $expected = [
            'nowrap' => 1,
            'wizards' => [
                'parameters' => [
                    0 => 'foo',
                    1 => 'bar',
                ],
            ],
            'anotherDefaultExtras' => 1,
            'some' => [
                'parameters' => [
                    0 => 'other',
                    1 => 'setting',
                    2 => 'with',
                    3 => 'parameters',
                ],
            ],
        ];
        $this->assertEquals($expected, BackendUtility::getSpecConfParts($defaultExtras));
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

    ///////////////////////////////////////
    // Tests concerning getTCAtypes
    ///////////////////////////////////////

    /**
     * @test
     */
    public function getTCAtypesReturnsCorrectValuesDataProvider()
    {
        return [
            'no input' => [
                '', // table
                [], // rec
                '', // useFieldNameAsKey
                null // expected
            ],
            'non-existant table' => [
                'fooBar', // table
                [], // rec
                '', // useFieldNameAsKey
                null // expected
            ],
            'Doktype=1: one simple field' => [
                'pages',
                [
                    'uid' => '1',
                    'doktype' => '1'
                ],
                false,
                [
                    0 => [
                        'field' => 'title',
                        'title' => null,
                        'palette' => null,
                        'spec' => [],
                        'origString' => 'title'
                    ]
                ]
            ],
            'non-existant type given: Return for type 1' => [
                'pages', // table
                [
                    'uid' => '1',
                    'doktype' => '999'
                ], // rec
                '', // useFieldNameAsKey
                [
                    0 => [
                        'field' => 'title',
                        'title' => null,
                        'palette' => null,
                        'spec' => [],
                        'origString' => 'title'
                    ]
                ] // expected
            ],
            'Doktype=1: one simple field, useFieldNameAsKey=true' => [
                'pages',
                [
                    'uid' => '1',
                    'doktype' => '1'
                ],
                true,
                [
                    'title' => [
                        'field' => 'title',
                        'title' => null,
                        'palette' => null,
                        'spec' => [],
                        'origString' => 'title'
                    ]
                ]
            ],
            'Empty showitem Field' => [
                'test',
                [
                    'uid' => '1',
                    'fooBar' => '99'
                ],
                true,
                [
                    '' => [
                        'field' => '',
                        'title' => null,
                        'palette' => null,
                        'spec' => [],
                        'origString' => ''
                    ]
                ]
            ],
            'RTE field within a palette' => [
                'pages',
                [
                    'uid' => '1',
                    'doktype' => '10',
                ],
                false,
                [
                    0 => [
                        'field' => '--div--',
                        'title' => 'General',
                        'palette' => null,
                        'spec' => [],
                        'origString' => '--div--;General'
                    ],
                    1 => [
                        'field' => '--palette--',
                        'title' => 'Palette',
                        'palette' => '123',
                        'spec' => [],
                        'origString' => '--palette--;Palette;123'
                    ],
                    2 => [
                        'field' => 'title',
                        'title' => null,
                        'palette' => null,
                        'spec' => [],
                        'origString' => 'title'
                    ],
                    3 => [
                        'field' => 'text',
                        'title' => null,
                        'palette' => null,
                        'spec' => [
                            'richtext' => 1,
                            'rte_transform' => [
                                'parameters' => [
                                    0 => 'mode=ts_css'
                                ]
                            ]
                        ],
                        'origString' => 'text'
                    ],
                    4 => [
                        'field' => 'select',
                        'title' => 'Select field',
                        'palette' => null,
                        'spec' => [],
                        'origString' => 'select;Select field'
                    ]
                ]
            ],
            'RTE field with more settings within a palette' => [
                'pages',
                [
                    'uid' => 1,
                    'doktype' => 2
                ],
                false,
                [
                    0 => [
                        'field' => '--div--',
                        'title' => 'General',
                        'palette' => null,
                        'spec' => [],
                        'origString' => '--div--;General'
                    ],
                    1 => [
                        'field' => '--palette--',
                        'title' => 'RTE palette',
                        'palette' => '456',
                        'spec' => [],
                        'origString' => '--palette--;RTE palette;456'
                    ],
                    2 => [
                        'field' => 'text2',
                        'title' => null,
                        'palette' => null,
                        'spec' => [
                            'richtext' => 1,
                            'rte_transform' => [
                                'parameters' => [
                                    0 => 'mode=fooBar,type=RTE'
                                ]
                            ]
                        ],
                        'origString' => 'text2'
                    ]
                ]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider getTCAtypesReturnsCorrectValuesDataProvider
     *
     * @param string $table
     * @param array $rec
     * @param bool $useFieldNameAsKey
     * @param array $expected
     */
    public function getTCAtypesReturnsCorrectValues($table, $rec, $useFieldNameAsKey, $expected)
    {
        $GLOBALS['TCA'] = [
            'pages' => [
                'ctrl' => [
                    'type' => 'doktype'
                ],
                'columns' => [
                    'title' => [
                        'label' => 'Title test',
                        'config' => [
                            'type' => 'input'
                        ]
                    ],
                    'text' => [
                        'label' => 'RTE Text',
                        'config' => [
                            'type' => 'text',
                            'cols' => 40,
                            'rows' => 5
                        ],
                        'defaultExtras' => 'richtext:rte_transform[mode=ts_css]'
                    ],
                    'text2' => [
                        'label' => 'RTE Text 2',
                        'config' => [
                            'type' => 'text',
                            'cols' => 40,
                            'rows' => 5
                        ],
                        'defaultExtras' => 'richtext:rte_transform[mode=fooBar,type=RTE]'
                    ],
                    'select' => [
                        'label' => 'Select test',
                        'config' => [
                            'items' => [
                                ['Please select', 0],
                                ['Option 1', 1],
                                ['Option 2', 2]
                            ]
                        ],
                        'maxitems' => 1,
                        'renderType' => 'selectSingle'
                    ]
                ],
                'types' => [
                    '1' => [
                        'showitem' => 'title'
                    ],
                    '2' => [
                        'showitem' => '--div--;General,--palette--;RTE palette;456'
                    ],
                    '10' => [
                        'showitem' => '--div--;General,--palette--;Palette;123,title'
                    ],
                    '14' => [
                        'showitem' => '--div--;General,title'
                    ]
                ],
                'palettes' => [
                    '123' => [
                        'showitem' => 'text,select;Select field'
                    ],
                    '456' => [
                        'showitem' => 'text2'
                    ]
                ]
            ],
            'test' => [
                'ctrl' => [
                    'type' => 'fooBar'
                ],
                'types' => [
                    '99' => [ 'showitem' => '']
                ]
            ]
        ];

        $return = BackendUtility::getTCAtypes($table, $rec, $useFieldNameAsKey);
        $this->assertSame($expected, $return);
    }
}
