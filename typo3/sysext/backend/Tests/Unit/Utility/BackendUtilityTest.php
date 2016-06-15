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
        return array(
            'Single year' => array(
                'seconds' => 60 * 60 * 24 * 365,
                'expectedLabel' => '1 year'
            ),
            'Plural years' => array(
                'seconds' => 60 * 60 * 24 * 365 * 2,
                'expectedLabel' => '2 yrs'
            ),
            'Single negative year' => array(
                'seconds' => 60 * 60 * 24 * 365 * -1,
                'expectedLabel' => '-1 year'
            ),
            'Plural negative years' => array(
                'seconds' => 60 * 60 * 24 * 365 * 2 * -1,
                'expectedLabel' => '-2 yrs'
            ),
            'Single day' => array(
                'seconds' => 60 * 60 * 24,
                'expectedLabel' => '1 day'
            ),
            'Plural days' => array(
                'seconds' => 60 * 60 * 24 * 2,
                'expectedLabel' => '2 days'
            ),
            'Single negative day' => array(
                'seconds' => 60 * 60 * 24 * -1,
                'expectedLabel' => '-1 day'
            ),
            'Plural negative days' => array(
                'seconds' => 60 * 60 * 24 * 2 * -1,
                'expectedLabel' => '-2 days'
            ),
            'Single hour' => array(
                'seconds' => 60 * 60,
                'expectedLabel' => '1 hour'
            ),
            'Plural hours' => array(
                'seconds' => 60 * 60 * 2,
                'expectedLabel' => '2 hrs'
            ),
            'Single negative hour' => array(
                'seconds' => 60 * 60 * -1,
                'expectedLabel' => '-1 hour'
            ),
            'Plural negative hours' => array(
                'seconds' => 60 * 60 * 2 * -1,
                'expectedLabel' => '-2 hrs'
            ),
            'Single minute' => array(
                'seconds' => 60,
                'expectedLabel' => '1 min'
            ),
            'Plural minutes' => array(
                'seconds' => 60 * 2,
                'expectedLabel' => '2 min'
            ),
            'Single negative minute' => array(
                'seconds' => 60 * -1,
                'expectedLabel' => '-1 min'
            ),
            'Plural negative minutes' => array(
                'seconds' => 60 * 2 * -1,
                'expectedLabel' => '-2 min'
            ),
            'Zero seconds' => array(
                'seconds' => 0,
                'expectedLabel' => '0 min'
            )
        );
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
        $GLOBALS['TCA'] = array(
            'tt_content' => array(
                'columns' => array(
                    'header' => array(
                        'config' => array(
                            'type' => 'input',
                        ),
                    ),
                ),
            ),
        );
        $this->assertEquals('0', BackendUtility::getProcessedValue('tt_content', 'header', '0'));
    }

    /**
     * @test
     */
    public function getProcessedValueForGroup()
    {
        $GLOBALS['TCA'] = array(
            'tt_content' => array(
                'columns' => array(
                    'multimedia' => array(
                        'config' => array(
                            'type' => 'group',
                        ),
                    ),
                ),
            ),
        );
        $this->assertSame('1, 2', BackendUtility::getProcessedValue('tt_content', 'multimedia', '1,2'));
    }

    /**
     * @test
     */
    public function getProcessedValueForGroupWithOneAllowedTable()
    {
        $GLOBALS['TCA'] = array(
            'tt_content' => array(
                'columns' => array(
                    'pages' => array(
                        'config' => array(
                            'type' => 'group',
                            'allowed' => 'pages',
                            'internal_type' => 'db',
                            'maxitems' => 22,
                            'minitems' => 0,
                            'show_thumbs' => 1,
                            'size' => 3,
                        ),
                    ),
                ),
            ),
        );

        $this->assertSame('Page 1, Page 2', ProcessedValueForGroupWithOneAllowedTableFixture::getProcessedValue('tt_content', 'pages', '1,2'));
    }

    /**
     * @test
     */
    public function getProcessedValueForGroupWithMultipleAllowedTables()
    {
        $GLOBALS['TCA'] = array(
            'index_config' => array(
                'columns' => array(
                    'indexcfgs' => array(
                        'config' => array(
                            'type' => 'group',
                            'internal_type' => 'db',
                            'allowed' => 'index_config,pages',
                            'size' => 5,
                        ),
                    ),
                ),
            ),
        );

        $this->assertSame('Page 1, Configuration 2', ProcessedValueForGroupWithMultipleAllowedTablesFixture::getProcessedValue('index_config', 'indexcfgs', 'pages_1,index_config_2'));
    }

    /**
     * @test
     */
    public function getProcessedValueForSelectWithMMRelation()
    {
        $GLOBALS['TYPO3_DB'] = $this->createMock(DatabaseConnection::class);
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
                            return array(
                                'uid_local' => 1,    // uid of a sys_category record
                                'uid_foreign' => 1,    // uid of a pages record
                            );
                        case 2:
                            return array(
                                'uid_local' => 2,    // uid of a sys_category record
                                'uid_foreign' => 1,    // uid of a pages record
                            );
                        case 3:
                            return null;
                        // SELECT * FROM sys_catgory
                        case 4:
                            return array(
                                'uid' => 1,
                                'title' => 'Category 1',
                            );
                        case 5:
                            return array(
                                'uid' => 2,
                                'title' => 'Category 2',
                            );
                        case 6:
                            return null;
                    }
                    return null;
                }
            )
            );

        $GLOBALS['TCA'] = array(
            'pages' => array(
                'columns' => array(
                    'categories' => array(
                        'config' => array(
                            'type' => 'select',
                            'foreign_table' => 'sys_category',
                            'MM' => 'sys_category_record_mm',
                            'MM_match_fields' => array(
                                'fieldname' => 'categories',
                                'tablesnames' => 'pages',
                            ),
                            'MM_opposite_field' => 'items',
                        ),
                    ),
                ),
            ),
            'sys_category' => array(
                'columns' => array(
                    'items' => array(
                        'config' => array(
                            'type' => 'group',
                            'internal_type' => 'db',
                            'allowed' => '*',
                            'MM' => 'sys_category_record_mm',
                            'MM_oppositeUsage' => array(),
                        )
                    )
                ),
            ),
        );

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
        return array(
            'only uid' => array(
                'table' => 'test_table',
                'prefix' => '',
                'presetFields' => array(),
                'tca' => array(),
                'expectedFields' => 'uid'
            ),
            'label set' => array(
                'table' => 'test_table',
                'prefix' => '',
                'presetFields' => array(),
                'tca' => array(
                    'ctrl' => array(
                        'label' => 'label'
                    )
                ),
                'expectedFields' => 'uid,label'
            ),
            'label_alt set' => array(
                'table' => 'test_table',
                'prefix' => '',
                'presetFields' => array(),
                'tca' => array(
                    'ctrl' => array(
                        'label_alt' => 'label,label2'
                    )
                ),
                'expectedFields' => 'uid,label,label2'
            ),
            'versioningWS set' => array(
                'table' => 'test_table',
                'prefix' => '',
                'presetFields' => array(),
                'tca' => array(
                    'ctrl' => array(
                        'versioningWS' => true
                    )
                ),
                'expectedFields' => 'uid,t3ver_id,t3ver_state,t3ver_wsid,t3ver_count'
            ),
            'selicon_field set' => array(
                'table' => 'test_table',
                'prefix' => '',
                'presetFields' => array(),
                'tca' => array(
                    'ctrl' => array(
                        'selicon_field' => 'field'
                    )
                ),
                'expectedFields' => 'uid,field'
            ),
            'typeicon_column set' => array(
                'table' => 'test_table',
                'prefix' => '',
                'presetFields' => array(),
                'tca' => array(
                    'ctrl' => array(
                        'typeicon_column' => 'field'
                    )
                ),
                'expectedFields' => 'uid,field'
            ),
            'enablecolumns set' => array(
                'table' => 'test_table',
                'prefix' => '',
                'presetFields' => array(),
                'tca' => array(
                    'ctrl' => array(
                        'enablecolumns' => array(
                            'disabled' => 'hidden',
                            'starttime' => 'start',
                            'endtime' => 'stop',
                            'fe_group' => 'groups'
                        )
                    )
                ),
                'expectedFields' => 'uid,hidden,start,stop,groups'
            ),
            'label set to uid' => array(
                'table' => 'test_table',
                'prefix' => '',
                'presetFields' => array(),
                'tca' => array(
                    'ctrl' => array(
                        'label' => 'uid'
                    )
                ),
                'expectedFields' => 'uid'
            )
        );
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
        return array(
            'item set' => array(
                'table' => 'tt_content',
                'col' => 'menu_type',
                'key' => '1',
                'tca' => array(
                    'columns' => array(
                        'menu_type' => array(
                            'config' => array(
                                'items' => array(
                                    array('Item 1', '0'),
                                    array('Item 2', '1'),
                                    array('Item 3', '3')
                                )
                            )
                        )
                    )
                ),
                'expectedLabel' => 'Item 2'
            ),
            'item set twice' => array(
                'table' => 'tt_content',
                'col' => 'menu_type',
                'key' => '1',
                'tca' => array(
                    'columns' => array(
                        'menu_type' => array(
                            'config' => array(
                                'items' => array(
                                    array('Item 1', '0'),
                                    array('Item 2a', '1'),
                                    array('Item 2b', '1'),
                                    array('Item 3', '3')
                                )
                            )
                        )
                    )
                ),
                'expectedLabel' => 'Item 2a'
            ),
            'item not found' => array(
                'table' => 'tt_content',
                'col' => 'menu_type',
                'key' => '5',
                'tca' => array(
                    'columns' => array(
                        'menu_type' => array(
                            'config' => array(
                                'items' => array(
                                    array('Item 1', '0'),
                                    array('Item 2', '1'),
                                    array('Item 3', '2')
                                )
                            )
                        )
                    )
                ),
                'expectedLabel' => null
            )
        );
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
        return array(
            'no field found' => array(
                'pageId' => '123',
                'table' => 'tt_content',
                'col' => 'menu_type',
                'key' => '10',
                'tca' => array(
                    'columns' => array(
                        'menu_type' => array(
                            'config' => array(
                                'items' => array(
                                    array('Item 1', '0'),
                                    array('Item 2', '1'),
                                    array('Item 3', '3')
                                )
                            )
                        )
                    )
                ),
                'expectedLabel' => ''
            ),
            'no tsconfig set' => array(
                'pageId' => '123',
                'table' => 'tt_content',
                'col' => 'menu_type',
                'key' => '1',
                'tca' => array(
                    'columns' => array(
                        'menu_type' => array(
                            'config' => array(
                                'items' => array(
                                    array('Item 1', '0'),
                                    array('Item 2', '1'),
                                    array('Item 3', '3')
                                )
                            )
                        )
                    )
                ),
                'expectedLabel' => 'Item 2'
            )
        );
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
        return array(
            'return value if found' => array(
                'foobar', // table
                'someColumn', // col
                'foo, bar', // keyList
                array( // TCA
                    'columns' => array(
                        'someColumn' => array(
                            'config' => array(
                                'items' => array(
                                    '0' => array('aFooLabel', 'foo'),
                                    '1' => array('aBarLabel', 'bar')
                                )
                            )
                        )
                    )
                ),
                array(), // page TSconfig
                'aFooLabel, aBarLabel' // expected
            ),
            'page TSconfig overrules TCA' => array(
                'foobar', // table
                'someColumn', // col
                'foo,bar, add', // keyList
                array( // TCA
                    'columns' => array(
                        'someColumn' => array(
                            'config' => array(
                                'items' => array(
                                    '0' => array('aFooLabel', 'foo'),
                                    '1' => array('aBarLabel', 'bar')
                                )
                            )
                        )
                    )
                ),
                array( // page TSconfig
                    'addItems.' => array('add' => 'aNewLabel'),
                    'altLabels.' => array('bar' => 'aBarDiffLabel'),
                ),
                'aFooLabel, aBarDiffLabel, aNewLabel' // expected
            )
        );
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
        $tca = array(
            'columns' => array(
                'someColumn' => array(
                    'config' => array(
                        'type' => 'select',
                        'items' => array(
                            '0' => array('aFooLabel', 'foo'),
                            '1' => array('aBarLabel', 'bar')
                        )
                    )
                )
            )
        );
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
        $tca = array(
            'columns' => array(
                'someColumn' => array(
                    'config' => array(
                        'type' => 'select',
                        'items' => array(
                            '0' => array('aFooLabel', 'foo')
                        )
                    )
                )
            )
        );
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
        $completeConfiguration = array(
            'value' => 'bar',
            'properties' => array(
                'permissions.' => array(
                    'file.' => array(
                        'default.' => array('readAction' => '1'),
                        '1.' => array('writeAction' => '1'),
                        '0.' => array('readAction' => '0'),
                    ),
                )
            )
        );

        $GLOBALS['BE_USER'] = $this->createMock(BackendUserAuthentication::class);
        $GLOBALS['BE_USER']->expects($this->at(0))->method('getTSConfig')->will($this->returnValue($completeConfiguration));
        $GLOBALS['BE_USER']->expects($this->at(1))->method('getTSConfig')->will($this->returnValue(array('value' => null, 'properties' => null)));

        $this->assertSame($completeConfiguration, BackendUtilityFixture::getModTSconfig(42, 'notrelevant'));
    }

    /**
     * Data provider for replaceL10nModeFieldsReplacesFields
     *
     * @return array
     */
    public function replaceL10nModeFieldsReplacesFieldsDataProvider()
    {
        return array(
            'same table: mergeIfNotBlank' => array(
                'foo',
                array(
                    'origUid' => 1,
                    'field2' => 'fdas',
                    'field3' => 'trans',
                ),
                array(
                    'foo' => array(
                        'ctrl' => array(
                            'transOrigPointerTable' => '',
                            'transOrigPointerField' => 'origUid'
                        ),
                        'columns' => array(
                            'field2' => array('l10n_mode' => 'mergeIfNotBlank'),
                            'field3' => array('l10n_mode' => 'mergeIfNotBlank')
                        )
                    )
                ),
                array(
                    'origUid' => 0,
                    'field2' => 'basic',
                    'field3' => '',
                ),
                array(
                    'origUid' => 1,
                    'field2' => 'fdas',
                    'field3' => 'trans',
                )
            ),
            'other table: mergeIfNotBlank' => array(
                'foo',
                array(
                    'origUid' => 1,
                    'field2' => '',
                    'field3' => 'trans',
                ),
                array(
                    'foo' => array(
                        'ctrl' => array(
                            'transOrigPointerTable' => 'bar',
                            'transOrigPointerField' => 'origUid'
                        )
                    ),
                    'bar' => array(
                        'columns' => array(
                            'field2' => array('l10n_mode' => 'mergeIfNotBlank'),
                            'field3' => array('l10n_mode' => 'mergeIfNotBlank')
                        )
                    )
                ),
                array(
                    'origUid' => 0,
                    'field2' => 'basic',
                    'field3' => '',
                ),
                array(
                    'origUid' => 1,
                    'field2' => 'basic',
                    'field3' => 'trans',
                )
            ),
            'same table: exclude' => array(
                'foo',
                array(
                    'origUid' => 1,
                    'field2' => 'fdas',
                    'field3' => 'trans',
                ),
                array(
                    'foo' => array(
                        'ctrl' => array(
                            'transOrigPointerTable' => '',
                            'transOrigPointerField' => 'origUid'
                        ),
                        'columns' => array(
                            'field2' => array('l10n_mode' => 'exclude'),
                            'field3' => array('l10n_mode' => 'exclude')
                        )
                    )
                ),
                array(
                    'origUid' => 0,
                    'field2' => 'basic',
                    'field3' => '',
                ),
                array(
                    'origUid' => 1,
                    'field2' => 'basic',
                    'field3' => '',
                )
            ),
            'other table: exclude' => array(
                'foo',
                array(
                    'origUid' => 1,
                    'field2' => 'fdas',
                    'field3' => 'trans',
                ),
                array(
                    'foo' => array(
                        'ctrl' => array(
                            'transOrigPointerTable' => 'bar',
                            'transOrigPointerField' => 'origUid'
                        )
                    ),
                    'bar' => array(
                        'columns' => array(
                            'field2' => array('l10n_mode' => 'exclude'),
                            'field3' => array('l10n_mode' => 'exclude')
                        )
                    )
                ),
                array(
                    'origUid' => 0,
                    'field2' => 'basic',
                    'field3' => '',
                ),
                array(
                    'origUid' => 1,
                    'field2' => 'basic',
                    'field3' => '',
                )
            ),
        );
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
        $GLOBALS['TYPO3_DB'] = $this->createMock(DatabaseConnection::class);
        $GLOBALS['TYPO3_DB']->expects($this->any())->method('exec_SELECTgetSingleRow')->will($this->returnValue($originalRow));

        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|BackendUtility $subject */
        $subject = $this->getAccessibleMock(BackendUtility::class, array('dummy'));
        $this->assertSame($expected, $subject->_call('replaceL10nModeFields', $table, $row));
    }

    /**
     * @test
     */
    public function getSpecConfPartsSplitsDefaultExtras()
    {
        $defaultExtras = 'nowrap:wizards[foo|bar]:anotherDefaultExtras:some[other|setting|with|parameters]';
        $expected = array(
            'nowrap' => 1,
            'wizards' => array(
                'parameters' => array(
                    0 => 'foo',
                    1 => 'bar',
                ),
            ),
            'anotherDefaultExtras' => 1,
            'some' => array(
                'parameters' => array(
                    0 => 'other',
                    1 => 'setting',
                    2 => 'with',
                    3 => 'parameters',
                ),
            ),
        );
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
}
