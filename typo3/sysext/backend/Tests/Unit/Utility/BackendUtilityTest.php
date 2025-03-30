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

namespace TYPO3\CMS\Backend\Tests\Unit\Utility;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Tests\Unit\Utility\Fixtures\LabelFromItemListMergedReturnsCorrectFieldsFixture;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\DataHandling\ItemProcessingService;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Schema\Field\CheckboxFieldType;
use TYPO3\CMS\Core\Schema\Field\FieldCollection;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\PageTsConfig;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class BackendUtilityTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    public static function calcAgeDataProvider(): array
    {
        return [
            '366 days' => [
                'seconds' => 60 * 60 * 24 * (365 + 1),
                'expectedLabel' => '1 year',
            ],
            '365 days' => [
                'seconds' => 60 * 60 * 24 * 365,
                'expectedLabel' => '1 year',
            ],
            'Plural years' => [
                'seconds' => 60 * 60 * 24 * (365 * 2 + 1),
                'expectedLabel' => '2 yrs',
            ],
            'Negative 366 year' => [
                'seconds' => 60 * 60 * 24 * (365 + 1) * -1,
                'expectedLabel' => '-1 year',
            ],
            'Negative 365 days (not a year, because of leap year)' => [
                'seconds' => 60 * 60 * 24 * 365 * -1,
                'expectedLabel' => '-365 days',
            ],
            'Negative 2*365 days' => [
                'seconds' => 60 * 60 * 24 * (365 * 2) * -1,
                'expectedLabel' => '-1 year',
            ],
            'Negative 366 + 365 days' => [
                'seconds' => 60 * 60 * 24 * (366 + 365) * -1,
                'expectedLabel' => '-2 yrs',
            ],
            'Single day' => [
                'seconds' => 60 * 60 * 24,
                'expectedLabel' => '1 day',
            ],
            'Plural days' => [
                'seconds' => 60 * 60 * 24 * 2,
                'expectedLabel' => '2 days',
            ],
            'Single negative day' => [
                'seconds' => 60 * 60 * 24 * -1,
                'expectedLabel' => '-1 day',
            ],
            'Plural negative days' => [
                'seconds' => 60 * 60 * 24 * 2 * -1,
                'expectedLabel' => '-2 days',
            ],
            'Single hour' => [
                'seconds' => 60 * 60,
                'expectedLabel' => '1 hour',
            ],
            'Plural hours' => [
                'seconds' => 60 * 60 * 2,
                'expectedLabel' => '2 hrs',
            ],
            'Single negative hour' => [
                'seconds' => 60 * 60 * -1,
                'expectedLabel' => '-1 hour',
            ],
            'Plural negative hours' => [
                'seconds' => 60 * 60 * 2 * -1,
                'expectedLabel' => '-2 hrs',
            ],
            'Single minute' => [
                'seconds' => 60,
                'expectedLabel' => '1 min',
            ],
            'Plural minutes' => [
                'seconds' => 60 * 2,
                'expectedLabel' => '2 min',
            ],
            'Single negative minute' => [
                'seconds' => 60 * -1,
                'expectedLabel' => '-1 min',
            ],
            'Plural negative minutes' => [
                'seconds' => 60 * 2 * -1,
                'expectedLabel' => '-2 min',
            ],
            'Zero seconds' => [
                'seconds' => 0,
                'expectedLabel' => '0 min',
            ],
        ];
    }

    #[DataProvider('calcAgeDataProvider')]
    #[Test]
    public function calcAgeReturnsExpectedValues(int $seconds, string $expectedLabel): void
    {
        $GLOBALS['EXEC_TIME'] = mktime(0, 0, 0, 8, 30, 2015);
        self::assertSame($expectedLabel, BackendUtility::calcAge($seconds));
    }

    #[Test]
    public function getProcessedValueForZeroStringIsZero(): void
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
        $GLOBALS['LANG'] = $this->createMock(LanguageService::class);
        self::assertEquals('0', BackendUtility::getProcessedValue('tt_content', 'header', '0'));
    }

    #[Test]
    public function getProcessedValueForGroup(): void
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
        $languageServiceMock = $this->createMock(LanguageService::class);
        $languageServiceMock->expects(self::once())->method('sL')->willReturn('testLabel');
        $GLOBALS['LANG'] = $languageServiceMock;
        $tcaFactoryMock = $this->getMockBuilder(TcaSchemaFactory::class)->disableOriginalConstructor()->getMock();
        GeneralUtility::addInstance(TcaSchemaFactory::class, $tcaFactoryMock);

        self::assertSame('testLabel', BackendUtility::getProcessedValue('tt_content', 'multimedia', '1,2'));
    }

    #[Test]
    public function getProcessedValueForFlexNull(): void
    {
        $GLOBALS['TCA'] = [
            'tt_content' => [
                'columns' => [
                    'pi_flexform' => [
                        'config' => [
                            'type' => 'flex',
                        ],
                    ],
                ],
            ],
        ];
        $languageServiceMock = $this->createMock(LanguageService::class);
        $languageServiceMock->method('sL')->willReturn('testLabel');
        $GLOBALS['LANG'] = $languageServiceMock;
        self::assertSame('', BackendUtility::getProcessedValue('tt_content', 'pi_flexform', null));
    }

    #[Test]
    public function getProcessedValueForDatetimeDbTypeDateNull(): void
    {
        $GLOBALS['TCA'] = [
            'tt_content' => [
                'columns' => [
                    'header' => [
                        'config' => [
                            'type' => 'datetime',
                            'dbType' => 'date',
                            'format' => 'date',
                        ],
                    ],
                ],
            ],
        ];
        $languageServiceMock = $this->createMock(LanguageService::class);
        $languageServiceMock->method('sL')->willReturn('testLabel');
        $GLOBALS['LANG'] = $languageServiceMock;
        self::assertSame('', BackendUtility::getProcessedValue('tt_content', 'header', null));
    }

    #[Test]
    public function getProcessedValueForDatetimeDbTypeDatetime(): void
    {
        $GLOBALS['TCA'] = [
            'tt_content' => [
                'columns' => [
                    'header' => [
                        'config' => [
                            'type' => 'datetime',
                            'dbType' => 'datetime',
                        ],
                    ],
                ],
            ],
        ];
        $value = '2022-09-23 00:03:00';
        $expected = BackendUtility::datetime((int)strtotime($value));
        $languageServiceMock = $this->createMock(LanguageService::class);
        $languageServiceMock->method('sL')->willReturn('testLabel');
        $GLOBALS['LANG'] = $languageServiceMock;
        self::assertSame($expected, BackendUtility::getProcessedValue('tt_content', 'header', $value));
    }

    #[Test]
    public function getProcessedValueForDatetimeDbTypeDatetimeNull(): void
    {
        $GLOBALS['TCA'] = [
            'tt_content' => [
                'columns' => [
                    'header' => [
                        'config' => [
                            'type' => 'datetime',
                            'dbType' => 'datetime',
                        ],
                    ],
                ],
            ],
        ];
        $languageServiceMock = $this->createMock(LanguageService::class);
        $languageServiceMock->method('sL')->willReturn('testLabel');
        $GLOBALS['LANG'] = $languageServiceMock;
        self::assertSame('', BackendUtility::getProcessedValue('tt_content', 'header', null));
    }

    #[Test]
    public function getProcessedValueForDatetimeDbTypeDate(): void
    {
        $GLOBALS['TCA'] = [
            'tt_content' => [
                'columns' => [
                    'header' => [
                        'config' => [
                            'type' => 'datetime',
                            'format' => 'date',
                            'dbType' => 'date',
                            'disableAgeDisplay' => true,
                        ],
                    ],
                ],
            ],
        ];
        $value = '2022-09-23';
        $expected = BackendUtility::date((int)strtotime($value));
        $languageServiceMock = $this->createMock(LanguageService::class);
        $languageServiceMock->method('sL')->willReturn('testLabel');
        $GLOBALS['LANG'] = $languageServiceMock;
        self::assertSame($expected, BackendUtility::getProcessedValue('tt_content', 'header', $value));
    }

    #[Test]
    public function getProcessedValueForFlex(): void
    {
        $GLOBALS['TCA'] = [
            'tt_content' => [
                'columns' => [
                    'pi_flexform' => [
                        'config' => [
                            'type' => 'flex',
                        ],
                    ],
                ],
            ],
        ];
        $languageServiceMock = $this->createMock(LanguageService::class);
        $languageServiceMock->method('sL')->willReturn('testLabel');
        $GLOBALS['LANG'] = $languageServiceMock;
        $expectation = "\n"
            . "\n    "
            . "\n        "
            . "\n            "
            . "\n                "
            . "\n                    bar"
            . "\n                "
            . "\n            "
            . "\n        "
            . "\n    "
            . "\n";

        self::assertSame($expectation, BackendUtility::getProcessedValue('tt_content', 'pi_flexform', '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="foo">
                    <value index="vDEF">bar</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>'));
    }

    #[Test]
    public function getProcessedValueDisplaysAgeForDateInputFieldsIfSettingAbsent(): void
    {
        $languageServiceMock = $this->createMock(LanguageService::class);
        $languageServiceMock->method('sL')->willReturn(' min| hrs| days| yrs| min| hour| day| year');
        $GLOBALS['LANG'] = $languageServiceMock;

        $GLOBALS['EXEC_TIME'] = mktime(0, 0, 0, 8, 30, 2015);

        $GLOBALS['TCA'] = [
            'tt_content' => [
                'columns' => [
                    'date' => [
                        'config' => [
                            'type' => 'datetime',
                            'format' => 'date',
                        ],
                    ],
                ],
            ],
        ];
        self::assertSame('2015-08-28 (-2 days)', BackendUtility::getProcessedValue('tt_content', 'date', mktime(0, 0, 0, 8, 28, 2015)));
    }

    public static function inputTypeDateDisplayOptions(): array
    {
        return [
            'typeSafe Setting' => [
                true,
                '2015-08-28',
            ],
            'non typesafe setting' => [
                1,
                '2015-08-28',
            ],
            'setting disabled typesafe' => [
                false,
                '2015-08-28 (-2 days)',
            ],
            'setting disabled not typesafe' => [
                0,
                '2015-08-28 (-2 days)',
            ],
        ];
    }

    #[DataProvider('inputTypeDateDisplayOptions')]
    #[Test]
    public function getProcessedValueHandlesAgeDisplayCorrectly(bool|int $input, string $expected): void
    {
        $languageServiceMock = $this->createMock(LanguageService::class);
        $languageServiceMock->method('sL')->willReturn(' min| hrs| days| yrs| min| hour| day| year');

        $GLOBALS['LANG'] = $languageServiceMock;

        $GLOBALS['EXEC_TIME'] = mktime(0, 0, 0, 8, 30, 2015);

        $GLOBALS['TCA'] = [
            'tt_content' => [
                'columns' => [
                    'date' => [
                        'config' => [
                            'type' => 'datetime',
                            'format' => 'date',
                            'disableAgeDisplay' => $input,
                        ],
                    ],
                ],
            ],
        ];
        self::assertSame($expected, BackendUtility::getProcessedValue('tt_content', 'date', mktime(0, 0, 0, 8, 28, 2015)));
    }

    #[Test]
    public function getProcessedValueForCheckWithSingleItem(): void
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
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $languageServiceMock = $this->createMock(LanguageService::class);
        $languageServiceMock->method('sL')->willReturnMap(
            [
                ['LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:yes', 'Yes'],
                ['LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:no', 'No'],
            ]
        );
        $GLOBALS['LANG'] = $languageServiceMock;
        self::assertSame('Yes', BackendUtility::getProcessedValue('tt_content', 'hide', 1));
    }

    #[Test]
    public function getProcessedValueForCheckWithSingleItemInvertStateDisplay(): void
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
                                    'invertStateDisplay' => true,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $languageServiceMock = $this->createMock(LanguageService::class);
        $languageServiceMock->method('sL')->willReturnMap(
            [
                ['LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:yes', 'Yes'],
                ['LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:no', 'No'],
            ]
        );
        $GLOBALS['LANG'] = $languageServiceMock;
        self::assertSame('No', BackendUtility::getProcessedValue('tt_content', 'hide', 1));
    }

    public static function getCommonSelectFieldsReturnsCorrectFieldsDataProvider(): array
    {
        return [
            'minimum fields' => [
                'table' => 'test_table',
                'prefix' => '',
                'presetFields' => [],
                'tca' => [],
                'expectedFields' => 'uid,pid',
            ],
            'label set' => [
                'table' => 'test_table',
                'prefix' => '',
                'presetFields' => [],
                'tca' => [
                    'ctrl' => [
                        'label' => 'label',
                    ],
                ],
                'expectedFields' => 'uid,pid,label',
            ],
            'label_alt set' => [
                'table' => 'test_table',
                'prefix' => '',
                'presetFields' => [],
                'tca' => [
                    'ctrl' => [
                        'label' => 'label', // @todo This is a bug, see #107143
                        'label_alt' => 'label2,label3',
                    ],
                ],
                'expectedFields' => 'uid,pid,label,label2,label3',
            ],
            'versioningWS set' => [
                'table' => 'test_table',
                'prefix' => '',
                'presetFields' => [],
                'tca' => [
                    'ctrl' => [
                        'versioningWS' => true,
                    ],
                ],
                'expectedFields' => 'uid,pid,t3ver_state,t3ver_wsid',
            ],
            'selicon_field set' => [
                'table' => 'test_table',
                'prefix' => '',
                'presetFields' => [],
                'tca' => [
                    'ctrl' => [
                        'selicon_field' => 'field',
                    ],
                ],
                'expectedFields' => 'uid,pid,field',
            ],
            'typeicon_column set' => [
                'table' => 'test_table',
                'prefix' => '',
                'presetFields' => [],
                'tca' => [
                    'ctrl' => [
                        'typeicon_column' => 'field',
                    ],
                ],
                'expectedFields' => 'uid,pid,field',
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
                            'fe_group' => 'groups',
                        ],
                    ],
                    'columns' => [
                        'hidden' => ['config' => ['type' => 'check']],
                        'start' => ['config' => ['type' => 'check']],
                        'stop' => ['config' => ['type' => 'check']],
                        'groups' => ['config' => ['type' => 'check']],
                    ],
                ],
                'expectedFields' => 'uid,pid,hidden,start,stop,groups',
            ],
            'label set to uid' => [
                'table' => 'test_table',
                'prefix' => '',
                'presetFields' => [],
                'tca' => [
                    'ctrl' => [
                        'label' => 'uid',
                    ],
                ],
                'expectedFields' => 'uid,pid',
            ],
            'prefix used' => [
                'table' => 'test_table',
                'prefix' => 'prefix.',
                'presetFields' => [
                    'preset',
                ],
                'tca' => [
                    'ctrl' => [
                        'label' => 'label',
                        'label_alt' => 'label2,label3', ],
                ],
                'expectedFields' => 'prefix.preset,prefix.uid,prefix.pid,prefix.label,prefix.label2,prefix.label3',
            ],
        ];
    }

    #[DataProvider('getCommonSelectFieldsReturnsCorrectFieldsDataProvider')]
    #[IgnoreDeprecations]
    #[Test]
    public function getCommonSelectFieldsReturnsCorrectFields(
        string $table,
        string $prefix,
        array $presetFields,
        array $tca,
        string $expectedFields = ''
    ): void {
        $fields = [];
        foreach ($tca['columns'] ?? [] as $columnName => $columnConfig) {
            $fields[$columnName] = new CheckboxFieldType($columnName, $columnConfig);
        }
        $expectedTcaSchema = new TcaSchema(
            'your_table_name',
            new FieldCollection($fields),
            $tca['ctrl'] ?? []
        );
        $tcaSchemaFactoryMock = $this->createMock(TcaSchemaFactory::class);
        $tcaSchemaFactoryMock->method('get')->with($table)->willReturn($expectedTcaSchema);
        GeneralUtility::addInstance(TcaSchemaFactory::class, $tcaSchemaFactoryMock);
        $selectFields = BackendUtility::getCommonSelectFields($table, $prefix, $presetFields);
        self::assertEquals($expectedFields, $selectFields);
    }

    public static function getLabelFromItemlistReturnsCorrectFieldsDataProvider(): array
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
                                    ['label' => 'Item 1', 'value' => '0'],
                                    ['label' => 'Item 2', 'value' => '1'],
                                    ['label' => 'Item 3', 'value' => '3'],
                                ],
                            ],
                        ],
                    ],
                ],
                'expectedLabel' => 'Item 2',
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
                                    ['label' => 'Item 1', 'value' => '0'],
                                    ['label' => 'Item 2a', 'value' => '1'],
                                    ['label' => 'Item 2b', 'value' => '1'],
                                    ['label' => 'Item 3', 'value' => '3'],
                                ],
                            ],
                        ],
                    ],
                ],
                'expectedLabel' => 'Item 2a',
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
                                    ['label' => 'Item 1', 'value' => '0'],
                                    ['label' => 'Item 2', 'value' => '1'],
                                    ['label' => 'Item 3', 'value' => '2'],
                                ],
                            ],
                        ],
                    ],
                ],
                'expectedLabel' => null,
            ],
            'item from itemsProcFunc' => [
                'table' => 'tt_content',
                'col' => 'menu_type',
                'key' => '1',
                'tca' => [
                    'columns' => [
                        'menu_type' => [
                            'config' => [
                                'type' => 'radio',
                                'items' => [],
                                'itemsProcFunc' => static function (array $parameters, $pObj) {
                                    $parameters['items'] = [
                                        ['label' => 'Item 1', 'value' => '0'],
                                        ['label' => 'Item 2', 'value' => '1'],
                                        ['label' => 'Item 3', 'value' => '2'],
                                    ];
                                },
                            ],
                        ],
                    ],
                ],
                'expectedLabel' => 'Item 2',
            ],
        ];
    }

    #[DataProvider('getLabelFromItemlistReturnsCorrectFieldsDataProvider')]
    #[Test]
    public function getLabelFromItemlistReturnsCorrectFields(
        string $table,
        string $col,
        string $key,
        array $tca,
        ?string $expectedLabel = ''
    ): void {
        $GLOBALS['TCA'][$table] = $tca;

        $cacheManagerMock = $this->createMock(CacheManager::class);
        $cacheMock = $this->createMock(FrontendInterface::class);
        $cacheManagerMock->method('getCache')->with('runtime')->willReturn($cacheMock);
        $cacheMock->method('get')->willReturnMap([
            ['pageTsConfig-pid-to-hash-0', 'hash'],
            ['pageTsConfig-hash-to-object-hash', new PageTsConfig(new RootNode(), [])],
        ]);
        GeneralUtility::addInstance(ItemProcessingService::class, new ItemProcessingService(
            $this->createMock(SiteFinder::class),
            $this->createMock(TcaSchemaFactory::class),
            $this->createMock(FlashMessageService::class)
        ));
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerMock);

        $label = BackendUtility::getLabelFromItemlist($table, $col, $key);
        self::assertEquals($label, $expectedLabel);
        GeneralUtility::purgeInstances();
    }

    public static function getLabelFromItemListMergedReturnsCorrectFieldsDataProvider(): array
    {
        return [
            'no field found' => [
                'pageId' => 123,
                'table' => 'tt_content',
                'column' => 'menu_type',
                'key' => '10',
                'tca' => [
                    'columns' => [
                        'menu_type' => [
                            'config' => [
                                'items' => [
                                    ['label' => 'Item 1', 'value' => '0'],
                                    ['label' => 'Item 2', 'value' => '1'],
                                    ['label' => 'Item 3', 'value' => '3'],
                                ],
                            ],
                        ],
                    ],
                ],
                'expectedLabel' => '',
            ],
            'no tsconfig set' => [
                'pageId' => 123,
                'table' => 'tt_content',
                'column' => 'menu_type',
                'key' => '1',
                'tca' => [
                    'columns' => [
                        'menu_type' => [
                            'config' => [
                                'items' => [
                                    ['label' => 'Item 1', 'value' => '0'],
                                    ['label' => 'Item 2', 'value' => '1'],
                                    ['label' => 'Item 3', 'value' => '3'],
                                ],
                            ],
                        ],
                    ],
                ],
                'expectedLabel' => 'Item 2',
            ],
        ];
    }

    #[DataProvider('getLabelFromItemListMergedReturnsCorrectFieldsDataProvider')]
    #[Test]
    public function getLabelFromItemListMergedReturnsCorrectFields(
        int $pageId,
        string $table,
        string $column,
        string $key,
        array $tca,
        string $expectedLabel = ''
    ): void {
        $GLOBALS['TCA'][$table] = $tca;

        self::assertEquals($expectedLabel, LabelFromItemListMergedReturnsCorrectFieldsFixture::getLabelFromItemListMerged($pageId, $table, $column, $key));
    }

    public static function getLabelsFromItemsListDataProvider(): array
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
                                    ['label' => 'aFooLabel', 'value' => 'foo'],
                                    ['label' => 'aBarLabel', 'value' => 'bar'],
                                ],
                            ],
                        ],
                    ],
                ],
                [], // page TSconfig
                'aFooLabel, aBarLabel', // expected
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
                                    ['label' => 'aFooLabel', 'value' => 'foo'],
                                    ['label' => 'aBarLabel', 'value' => 'bar'],
                                ],
                            ],
                        ],
                    ],
                ],
                [ // page TSconfig
                    'addItems.' => ['add' => 'aNewLabel'],
                    'altLabels.' => ['bar' => 'aBarDiffLabel'],
                ],
                'aFooLabel, aBarDiffLabel, aNewLabel', // expected
            ],
            'itemsProcFunc is evaluated' => [
                'foobar', // table
                'someColumn', // col
                'foo,bar', // keyList
                [ // TCA
                    'columns' => [
                        'someColumn' => [
                            'config' => [
                                'type' => 'select',
                                'itemsProcFunc' => static function (array $parameters, $pObj) {
                                    $parameters['items'] = [
                                        ['label' => 'aFooLabel', 'value' => 'foo'],
                                        ['label' => 'aBarLabel', 'value' => 'bar'],
                                    ];
                                },
                            ],
                        ],
                    ],
                ],
                [],
                'aFooLabel, aBarLabel', // expected
            ],
        ];
    }

    #[DataProvider('getLabelsFromItemsListDataProvider')]
    #[Test]
    public function getLabelsFromItemsListReturnsCorrectValue(
        string $table,
        string $col,
        string $keyList,
        array $tca,
        array $pageTsConfig,
        string $expectedLabel
    ): void {
        // Stub LanguageService and let sL() return the same value that came in again
        $GLOBALS['LANG'] = $this->createMock(LanguageService::class);
        $GLOBALS['LANG']->method('sL')->willReturnArgument(0);

        $cacheManagerMock = $this->createMock(CacheManager::class);
        $cacheMock = $this->createMock(FrontendInterface::class);
        $cacheManagerMock->method('getCache')->with('runtime')->willReturn($cacheMock);
        $cacheMock->method('get')->willReturnMap([
            ['pageTsConfig-pid-to-hash-0', 'hash'],
            ['pageTsConfig-hash-to-object-hash', new PageTsConfig(new RootNode(), [])],
        ]);
        GeneralUtility::addInstance(ItemProcessingService::class, new ItemProcessingService(
            $this->createMock(SiteFinder::class),
            $this->createMock(TcaSchemaFactory::class),
            $this->createMock(FlashMessageService::class)
        ));
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerMock);

        $GLOBALS['TCA'][$table] = $tca;
        $label = BackendUtility::getLabelsFromItemsList($table, $col, $keyList, $pageTsConfig);
        self::assertEquals($expectedLabel, $label);
        GeneralUtility::purgeInstances();
    }

    #[Test]
    public function getProcessedValueReturnsLabelsForExistingValuesSolely(): void
    {
        $table = 'foobar';
        $col = 'someColumn';
        $tca = [
            'columns' => [
                'someColumn' => [
                    'config' => [
                        'type' => 'select',
                        'items' => [
                            ['label' => 'aFooLabel', 'value' => 'foo'],
                            ['label' => 'aBarLabel', 'value' => 'bar'],
                        ],
                    ],
                ],
            ],
        ];
        // Stub LanguageService and let sL() return the same value that came in again
        $GLOBALS['LANG'] = $this->createMock(LanguageService::class);
        $GLOBALS['LANG']->method('sL')->willReturnArgument(0);

        $GLOBALS['TCA'][$table] = $tca;
        $label = BackendUtility::getProcessedValue($table, $col, 'foo,invalidKey,bar');
        self::assertEquals('aFooLabel, aBarLabel', $label);
    }

    #[Test]
    public function getProcessedValueReturnsLabelsFormItemsProcFuncUsingRow(): void
    {
        $table = 'foobar';
        $col = 'someColumn';
        $uid = 123;
        $tca = [
            'columns' => [
                'someColumn' => [
                    'config' => [
                        'type' => 'select',
                        'itemsProcFunc' => static function (array $parameters, $pObj) {
                            $parameters['items'] = [
                                ['label' => $parameters['row']['title'], 'value' => 'foo'],
                                ['label' => $parameters['row']['title2'], 'value' => 'bar'],
                                ['label' => (string)$parameters['row']['uid'], 'value' => 'uidIsApplied'],
                            ];
                        },
                    ],
                ],
            ],
        ];
        // Stub LanguageService and let sL() return the same value that came in again
        $GLOBALS['LANG'] = $this->createMock(LanguageService::class);
        $GLOBALS['LANG']->method('sL')->willReturnArgument(0);

        $cacheManagerMock = $this->createMock(CacheManager::class);
        $cacheMock = $this->createMock(FrontendInterface::class);
        $cacheManagerMock->method('getCache')->with('runtime')->willReturn($cacheMock);
        $cacheMock->method('get')->willReturnMap([
            ['pageTsConfig-pid-to-hash-0', 'hash'],
            ['pageTsConfig-hash-to-object-hash', new PageTsConfig(new RootNode(), [])],
        ]);
        GeneralUtility::addInstance(ItemProcessingService::class, new ItemProcessingService(
            $this->createMock(SiteFinder::class),
            $this->createMock(TcaSchemaFactory::class),
            $this->createMock(FlashMessageService::class)
        ));
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerMock);

        $row = [
            'title' => 'itemTitle',
            'title2' => 'itemTitle2',
        ];

        $GLOBALS['TCA'][$table] = $tca;
        $label = BackendUtility::getProcessedValue(
            $table,
            $col,
            'foo,invalidKey,bar,uidIsApplied',
            0,
            false,
            false,
            $uid,
            true,
            0,
            $row
        );
        self::assertEquals($row['title'] . ', ' . $row['title2'] . ', ' . $uid, $label);
    }

    #[Test]
    public function getProcessedValueReturnsPlainValueIfItemIsNotFound(): void
    {
        $table = 'foobar';
        $col = 'someColumn';
        $tca = [
            'columns' => [
                'someColumn' => [
                    'config' => [
                        'type' => 'select',
                        'items' => [
                            '0' => ['label' => 'aFooLabel', 'value' => 'foo'],
                        ],
                    ],
                ],
            ],
        ];
        // Stub LanguageService and let sL() return the same value that came in again
        $GLOBALS['LANG'] = $this->createMock(LanguageService::class);
        $GLOBALS['LANG']->method('sL')->willReturnArgument(0);

        $GLOBALS['TCA'][$table] = $tca;
        $label = BackendUtility::getProcessedValue($table, $col, 'invalidKey');
        self::assertEquals('invalidKey', $label);
    }

    #[Test]
    public function dateTimeAgeReturnsCorrectValues(): void
    {
        $languageServiceMock = $this->createMock(LanguageService::class);
        $languageServiceMock->method('sL')->willReturn(' min| hrs| days| yrs| min| hour| day| year');
        $GLOBALS['LANG'] = $languageServiceMock;
        $GLOBALS['EXEC_TIME'] = mktime(0, 0, 0, 3, 23, 2016);

        self::assertSame('2016-03-24 00:00 (-1 day)', BackendUtility::dateTimeAge($GLOBALS['EXEC_TIME'] + 86400));
        self::assertSame('2016-03-24 (-1 day)', BackendUtility::dateTimeAge($GLOBALS['EXEC_TIME'] + 86400, 1, 'date'));
    }

    #[Test]
    public function purgeComputedPropertyNamesRemovesPropertiesStartingWithUnderscore(): void
    {
        $propertyNames = [
            'uid',
            'pid',
            '_ORIG_PID',
        ];
        $computedPropertyNames = BackendUtility::purgeComputedPropertyNames($propertyNames);
        self::assertSame(['uid', 'pid'], $computedPropertyNames);
    }

    #[Test]
    public function purgeComputedPropertiesFromRecordRemovesPropertiesStartingWithUnderscore(): void
    {
        $record = [
            'uid'       => 1,
            'pid'       => 2,
            '_ORIG_PID' => 1,
        ];
        $expected = [
            'uid' => 1,
            'pid' => 2,
        ];
        $computedProperties = BackendUtility::purgeComputedPropertiesFromRecord($record);
        self::assertSame($expected, $computedProperties);
    }

    public static function splitTableUidDataProvider(): array
    {
        return [
            'simple' => [
                'pages_23',
                ['pages', '23'],
            ],
            'complex' => [
                'tt_content_13',
                ['tt_content', '13'],
            ],
            'multiple underscores' => [
                'tx_runaway_domain_model_crime_scene_1234',
                ['tx_runaway_domain_model_crime_scene', '1234'],
            ],
            'no underscore' => [
                'foo',
                ['', 'foo'],
            ],
        ];
    }

    #[DataProvider('splitTableUidDataProvider')]
    #[Test]
    public function splitTableUid($input, $expected): void
    {
        $result = BackendUtility::splitTable_Uid($input);
        self::assertSame($expected, $result);
    }

    #[Test]
    public function wsMapIdReturnsLiveIdIfNoBeUserIsAvailable(): void
    {
        $GLOBALS['BE_USER'] = null;
        $tableName = 'table_a';
        $uid = 42;
        self::assertSame(42, BackendUtility::wsMapId($tableName, $uid));
    }

    #[Test]
    public function getAllowedFieldsForTableReturnsEmptyArrayOnBrokenTca(): void
    {
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        self::assertEmpty(BackendUtility::getAllowedFieldsForTable('myTable', false));
    }

    /**
     * Do NOT remove this test, even though it has IgnoreDeprecations attribute,
     * we're testing the core's deprecation strategy here.
     * @todo Remove in TYPO3 v15 along with deprecated {@see BackendUtility::resolveFileReferences()}.
     */
    #[Test]
    #[IgnoreDeprecations]
    public function returnNullForMissingTcaConfigInResolveFileReferences(): void
    {
        $tableName = 'table_a';
        $fieldName = 'field_a';
        $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'] = [];
        self::assertNull(BackendUtility::resolveFileReferences($tableName, $fieldName, []));
    }

    /**
     * Do NOT remove this test, even though it has IgnoreDeprecations attribute,
     * we're testing the core's deprecation strategy here.
     * @todo Remove in TYPO3 v15 along with deprecated {@see BackendUtility::resolveFileReferences()}.
     */
    #[DataProvider('unfitResolveFileReferencesTableConfig')]
    #[Test]
    #[IgnoreDeprecations]
    public function returnNullForUnfitTableConfigInResolveFileReferences(array $config): void
    {
        $tableName = 'table_a';
        $fieldName = 'field_a';
        $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'] = $config;
        self::assertNull(BackendUtility::resolveFileReferences($tableName, $fieldName, []));
    }

    public static function unfitResolveFileReferencesTableConfig(): array
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
     * Do NOT remove this test, even though it has IgnoreDeprecations attribute,
     * we're testing the core's deprecation strategy here.
     * @todo Remove in TYPO3 v15 along with deprecated {@see BackendUtility::resolveFileReferences()}.
     */
    #[Test]
    #[IgnoreDeprecations]
    public function resolveFileReferencesReturnsEmptyResultForNoReferencesAvailable(): void
    {
        $tableName = 'table_a';
        $fieldName = 'field_a';
        $elementData = [
            $fieldName => 'foo',
            'uid' => 42,
        ];
        $relationHandlerMock = $this->createMock(RelationHandler::class);
        $relationHandlerMock->expects(self::once())->method('initializeForField')->with(
            $tableName,
            ['type' => 'file', 'foreign_table' => 'sys_file_reference'],
            $elementData,
            'foo'
        );
        $relationHandlerMock->expects(self::once())->method('processDeletePlaceholder');
        $relationHandlerMock->tableArray = ['sys_file_reference' => []];
        GeneralUtility::addInstance(RelationHandler::class, $relationHandlerMock);
        $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'] = [
            'type' => 'file',
            'foreign_table' => 'sys_file_reference',
        ];

        self::assertEmpty(BackendUtility::resolveFileReferences($tableName, $fieldName, $elementData));
    }
}
