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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaRecordTitle;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TcaRecordTitleTest extends UnitTestCase
{
    protected string $timeZone;

    public function setUp(): void
    {
        parent::setUp();
        $this->timeZone = date_default_timezone_get();
        date_default_timezone_set('UTC');
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->timeZone);
        parent::tearDown();
    }

    #[Test]
    public function addDataThrowsExceptionWithMissingLabel(): void
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRew' => [],
            'processedTca' => [
                'ctrl' => [],
            ],
        ];
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1443706103);
        (new TcaRecordTitle())->addData($input);
    }

    #[Test]
    public function addDataReturnsRecordTitleForLabelUserFunction(): void
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [],
            'isInlineChild' => false,
            'processedTca' => [
                'ctrl' => [
                    'label' => 'uid',
                    'label_userFunc' => static function (&$parameters) {
                        $parameters['title'] = 'Test';
                    },
                ],
                'columns' => [],
            ],
        ];

        $expected = $input;
        $expected['recordTitle'] = 'Test';

        self::assertSame($expected, (new TcaRecordTitle())->addData($input));
    }

    #[Test]
    public function addDataReturnsRecordTitleForFormattedLabelUserFunction(): void
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'uid' => 5,
            ],
            'isInlineChild' => true,
            'isOnSymmetricSide' => false,
            'inlineParentConfig' => [
                'foreign_label' => 'aField',
            ],
            'processedTca' => [
                'ctrl' => [
                    'label' => 'uid',
                    'formattedLabel_userFunc' => static function (&$parameters) {
                        $parameters['title'] = 'Test';
                    },
                ],
                'columns' => [],
            ],
        ];

        $expected = $input;
        $expected['recordTitle'] = 'Test';

        self::assertSame($expected, (new TcaRecordTitle())->addData($input));
    }

    #[Test]
    public function addDataReturnsRecordTitleForInlineChildWithForeignLabel(): void
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => 'aValue',
            ],
            'processedTca' => [
                'ctrl' => [
                    'label' => 'foo',
                    'label_userFunc' => static function (&$parameters) {
                        $parameters['title'] = 'Value that MUST NOT be used, otherwise the code is broken.';
                    },
                ],
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                ],
            ],
            'isInlineChild' => true,
            'isOnSymmetricSide' => false,
            'inlineParentConfig' => [
                'foreign_label' => 'aField',
            ],
        ];
        $expected = $input;
        $expected['recordTitle'] = 'aValue';
        self::assertSame($expected, (new TcaRecordTitle())->addData($input));
    }

    #[Test]
    public function addDataOverridesRecordTitleWithFormattedLabelUserFuncForInlineChildWithForeignLabel(): void
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'uid' => 5,
                'aField' => 'aValue',
            ],
            'processedTca' => [
                'ctrl' => [
                    'label' => 'foo',
                    'formattedLabel_userFunc' => static function (&$parameters) {
                        $parameters['title'] = 'aFormattedLabel';
                    },
                ],
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                ],
            ],
            'isInlineChild' => true,
            'isOnSymmetricSide' => false,
            'inlineParentConfig' => [
                'foreign_label' => 'aField',
            ],
        ];
        $expected = $input;
        $expected['recordTitle'] = 'aFormattedLabel';
        self::assertSame($expected, (new TcaRecordTitle())->addData($input));
    }

    #[Test]
    public function addDataReturnsRecordTitleForInlineChildWithSymmetricLabel(): void
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => 'aValue',
            ],
            'processedTca' => [
                'ctrl' => [
                    'label' => 'foo',
                ],
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                ],
            ],
            'isInlineChild' => true,
            'inlineParentConfig' => [
                'symmetric_label' => 'aField',
            ],
            'isOnSymmetricSide' => true,
        ];
        $expected = $input;
        $expected['recordTitle'] = 'aValue';
        self::assertSame($expected, (new TcaRecordTitle())->addData($input));
    }

    #[Test]
    public function addDataReturnsRecordTitleForUid(): void
    {
        $input = [
            'tableName' => 'aTable',
            'isInlineChild' => false,
            'databaseRow' => [
                'uid' => 'NEW56017ee37d10e587251374',
            ],
            'processedTca' => [
                'ctrl' => [
                    'label' => 'uid',
                ],
                'columns' => [],
            ],
        ];

        $languageService = $this->createMock(LanguageService::class);
        $GLOBALS['LANG'] = $languageService;
        $languageService->method('sL')->with(self::anything())->willReturnArgument(0);

        $expected = $input;
        $expected['recordTitle'] = 'NEW56017ee37d10e587251374';
        self::assertSame($expected, (new TcaRecordTitle())->addData($input));
    }

    /**
     * Data provider for addDataReturnsRecordTitleForInputType
     * Each data set is an array with the following elements:
     *  - TCA field ['config'] section
     *  - Database value for field
     *  - expected title to be generated
     */
    public static function addDataReturnsRecordTitleForDatetimeTypeDataProvider(): array
    {
        return [
            'new record' => [
                [
                    'type' => 'datetime',
                ],
                '',
                '',
                '',
            ],
            'plain text input' => [
                [
                    'type' => 'datetime',
                ],
                'aValue',
                'aValue',
                'aValue',
            ],
            'date' => [
                [
                    'type' => 'datetime',
                    'format' => 'date',
                ],
                '978307261',
                '2001-01-01 (-7 days)',
                '2001-01-01 (-7 days)',
            ],
            'date (dbType: date)' => [
                [
                    'type' => 'datetime',
                    'format' => 'date',
                    'dbType' => 'date',
                ],
                '2001-01-01',
                '2001-01-01 (-7 days)',
                '2001-01-01 (-7 days)',
            ],
            'date (disableAgeDisplay: TRUE)' => [
                [
                    'type' => 'datetime',
                    'format' => 'date',
                    'disableAgeDisplay' => true,
                ],
                '978307261',
                '2001-01-01',
                '2001-01-01',
            ],
            'time' => [
                [
                    'type' => 'datetime',
                    'format' => 'time',
                ],
                '44100',
                '12:15',
                '12:15',
            ],
            'time (dbType: time)' => [
                [
                    'type' => 'datetime',
                    'format' => 'time',
                    'dbType' => 'time',
                ],
                '23:59:00',
                '23:59',
                '23:59',
            ],
            'timesec' => [
                [
                    'type' => 'datetime',
                    'format' => 'timesec',
                ],
                '44130',
                '12:15:30',
                '12:15:30',
            ],
            'timesec (dbType: time)' => [
                [
                    'type' => 'datetime',
                    'format' => 'timesec',
                    'dbType' => 'time',
                ],
                '23:59:59',
                '23:59:59',
                '23:59:59',
            ],
            'datetime (dbType: date)' => [
                [
                    'type' => 'datetime',
                    'dbType' => 'date',
                ],
                '2001-01-01',
                '2001-01-01 (-7 days)',
                '2001-01-01 (-7 days)',
            ],
            'datetime (dbType: date, invalid timestamp value)' => [
                [
                    'type' => 'datetime',
                    'dbType' => 'date',
                ],
                '978307261',
                '2001-01-01 (-7 days)',
                '2001-01-01 (-7 days)',
            ],
            'datetime (dbType: datetime)' => [
                [
                    'type' => 'datetime',
                    'dbType' => 'datetime',
                ],
                '2014-12-31 23:59:59',
                '2014-12-31 23:59',
                '2014-12-31 23:59',
            ],
            'datetime (dbType: datetime, invalid timestamp value)' => [
                [
                    'type' => 'datetime',
                    'dbType' => 'datetime',
                ],
                '978307261',
                '2001-01-01 00:01',
                '2001-01-01 01:01',
            ],
        ];
    }

    #[DataProvider('addDataReturnsRecordTitleForDatetimeTypeDataProvider')]
    #[Test]
    public function addDataReturnsRecordTitleForDatetimeType(
        array $fieldConfig,
        string $fieldValue,
        string $expectedUTCTitle,
        string $expectedBerlinTitle,
    ): void {
        $input = [
            'tableName' => 'aTable',
            'isInlineChild' => false,
            'databaseRow' => [
                'uid' => '1',
                'aField' => $fieldValue,
            ],
            'processedTca' => [
                'ctrl' => [
                    'label' => 'aField',
                ],
                'columns' => [
                    'aField' => [
                        'config' => $fieldConfig,
                    ],
                ],
            ],
        ];

        $timezones = [
            'UTC' => $expectedUTCTitle,
            'Europe/Berlin' => $expectedBerlinTitle,
        ];
        foreach ($timezones as $timezone => $expectedTitle) {
            $bak = date_default_timezone_get();
            date_default_timezone_set($timezone);

            $languageService = $this->createMock(LanguageService::class);
            $GLOBALS['LANG'] = $languageService;
            $languageService->method('sL')->with('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.minutesHoursDaysYears')
                ->willReturn(' min| hrs| days| yrs| min| hour| day| year');
            $GLOBALS['EXEC_TIME'] = 978912061;

            $expected = $input;
            $expected['recordTitle'] = $expectedTitle;
            self::assertSame($expected, (new TcaRecordTitle())->addData($input));
            date_default_timezone_set($bak);
        }
    }

    #[Test]
    public function addDataReturnsRecordTitleWithAlternativeLabel(): void
    {
        $input = [
            'tableName' => 'aTable',
            'isInlineChild' => false,
            'databaseRow' => [
                'uid' => '1',
                'aField' => '',
                'anotherField' => 'anotherValue',
            ],
            'processedTca' => [
                'ctrl' => [
                    'label' => 'aField',
                    'label_alt' => 'anotherField',
                ],
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                    'anotherField' => [
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['recordTitle'] = 'anotherValue';
        self::assertSame($expected, (new TcaRecordTitle())->addData($input));
    }

    #[Test]
    public function addDataReturnsRecordTitleWithMultipleAlternativeLabels(): void
    {
        $input = [
            'tableName' => 'aTable',
            'isInlineChild' => false,
            'databaseRow' => [
                'uid' => '1',
                'aField' => '',
                'anotherField' => '',
                'additionalField' => 'additionalValue',
            ],
            'processedTca' => [
                'ctrl' => [
                    'label' => 'aField',
                    'label_alt' => 'anotherField,additionalField',
                ],
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                    'anotherField' => [
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                    'additionalField' => [
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['recordTitle'] = 'additionalValue';
        self::assertSame($expected, (new TcaRecordTitle())->addData($input));
    }

    #[Test]
    public function addDataReturnsRecordTitleWithForcedAlternativeLabel(): void
    {
        $input = [
            'tableName' => 'aTable',
            'isInlineChild' => false,
            'databaseRow' => [
                'uid' => '1',
                'aField' => 'aField',
                'anotherField' => 'anotherField',
            ],
            'processedTca' => [
                'ctrl' => [
                    'label' => 'aField',
                    'label_alt' => 'anotherField',
                    'label_alt_force' => true,
                ],
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                    'anotherField' => [
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['recordTitle'] = 'aField, anotherField';
        self::assertSame($expected, (new TcaRecordTitle())->addData($input));
    }

    #[Test]
    public function addDataReturnsRecordTitleWithMultipleForcedAlternativeLabels(): void
    {
        $input = [
            'tableName' => 'aTable',
            'isInlineChild' => false,
            'databaseRow' => [
                'uid' => '1',
                'aField' => 'aField',
                'anotherField' => 'anotherField',
                'additionalField' => 'additionalValue',
            ],
            'processedTca' => [
                'ctrl' => [
                    'label' => 'aField',
                    'label_alt' => 'anotherField,additionalField',
                    'label_alt_force' => true,
                ],
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                    'anotherField' => [
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                    'additionalField' => [
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['recordTitle'] = 'aField, anotherField, additionalValue';
        self::assertSame($expected, (new TcaRecordTitle())->addData($input));
    }

    #[Test]
    public function addDataReturnsRecordTitleIgnoresEmptyAlternativeLabels(): void
    {
        $input = [
            'tableName' => 'aTable',
            'isInlineChild' => false,
            'databaseRow' => [
                'uid' => '1',
                'aField' => 'aField',
                'anotherField' => '',
                'additionalField' => 'additionalValue',
            ],
            'processedTca' => [
                'ctrl' => [
                    'label' => 'aField',
                    'label_alt' => 'anotherField,additionalField',
                    'label_alt_force' => true,
                ],
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                    'anotherField' => [
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                    'additionalField' => [
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['recordTitle'] = 'aField, additionalValue';
        self::assertSame($expected, (new TcaRecordTitle())->addData($input));
    }

    #[Test]
    public function addDataReturnsRecordTitleForRadioType(): void
    {
        $input = [
            'tableName' => 'aTable',
            'isInlineChild' => false,
            'databaseRow' => [
                'uid' => '1',
                'aField' => '2',
            ],
            'processedTca' => [
                'ctrl' => [
                    'label' => 'aField',
                ],
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'radio',
                            'items' => [
                                ['label' => 'foo', 'value' => 1],
                                ['label' => 'bar', 'value' => 2],
                                ['label' => 'baz', 'value' => 3],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['recordTitle'] = 'bar';
        self::assertSame($expected, (new TcaRecordTitle())->addData($input));
    }

    #[Test]
    public function addDataReturnsRecordTitleForInlineType(): void
    {
        $input = [
            'tableName' => 'aTable',
            'isInlineChild' => false,
            'databaseRow' => [
                'uid' => '1',
                'aField' => '2',
            ],
            'processedTca' => [
                'ctrl' => [
                    'label' => 'aField',
                ],
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                        ],
                        'children' => [
                            [
                                'recordTitle' => 'foo',
                                'vanillaUid' => 2,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['recordTitle'] = 'foo';
        self::assertSame($expected, (new TcaRecordTitle())->addData($input));
    }

    /**
     * Data provider for addDataReturnsRecordTitleForGroupType
     * Each data set is an array with the following elements:
     *  - TCA field configuration (merged with base config)
     *  - Database value for field
     *  - expected title to be generated
     */
    public static function addDataReturnsRecordTitleForGroupTypeDataProvider(): array
    {
        return [
            'new record' => [
                [],
                [],
                '',
            ],
            'db, single table, single record' => [
                [
                    'allowed' => 'aTable',
                ],
                [
                    [
                        'title' => 'aValue',
                    ],
                ],
                'aValue',
            ],
            'db, single table, multiple records' => [
                [
                    'allowed' => 'aTable',
                ],
                [
                    [
                        'title' => 'aValue',
                    ],
                    [
                        'title' => 'anotherValue',
                    ],
                ],
                'aValue, anotherValue',
            ],
            'db, multiple tables, single record' => [
                [
                    'allowed' => 'aTable,anotherTable',
                ],
                [
                    [
                        'uid' => 1,
                        'table' => 'anotherTable',
                        'title' => 'anotherValue',
                    ],
                ],
                'anotherValue',
            ],
            'db, multiple tables, multiple records' => [
                [
                    'allowed' => 'aTable,anotherTable',
                ],
                [
                    [
                        'uid' => 1,
                        'table' => 'aTable',
                        'title' => 'aValue',
                    ],
                    [
                        'uid' => 2,
                        'table' => 'anotherTable',
                        'title' => 'anotherValue',
                    ],
                ],
                'aValue, anotherValue',
            ],
        ];
    }

    #[DataProvider('addDataReturnsRecordTitleForGroupTypeDataProvider')]
    #[Test]
    public function addDataReturnsRecordTitleForGroupType(array $fieldConfig, array $fieldValue, string $expectedTitle): void
    {
        $input = [
            'tableName' => 'aTable',
            'isInlineChild' => false,
            'databaseRow' => [
                'uid' => '1',
                'aField' => $fieldValue,
            ],
            'processedTca' => [
                'ctrl' => [
                    'label' => 'aField',
                ],
                'columns' => [
                    'aField' => [
                        'config' => array_merge(
                            [
                                'type' => 'group',
                            ],
                            $fieldConfig
                        ),
                    ],
                ],
            ],
        ];

        $languageService = $this->createMock(LanguageService::class);
        $GLOBALS['LANG'] = $languageService;
        $languageService->method('sL')->with(self::anything())->willReturnArgument(0);

        $expected = $input;
        $expected['recordTitle'] = $expectedTitle;
        self::assertSame($expected, (new TcaRecordTitle())->addData($input));
    }

    #[Test]
    public function addDataReturnsRecordTitleForGroupTypeDb(): void
    {
        $input = [
            'tableName' => 'aTable',
            'isInlineChild' => false,
            'databaseRow' => [
                'uid' => '1',
                'aField' => [
                    [
                        'uid' => 1,
                        'table' => 'aTable',
                        'title' => 'aValue',
                    ],
                    [
                        'uid' => 2,
                        'table' => 'anotherTable',
                        'title' => 'anotherValue',
                    ],
                ],
            ],
            'processedTca' => [
                'ctrl' => [
                    'label' => 'aField',
                ],
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'group',
                            'allowed' => 'aTable,anotherTable',
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['recordTitle'] = 'aValue, anotherValue';
        self::assertSame($expected, (new TcaRecordTitle())->addData($input));
    }

    #[Test]
    public function addDataReturnsRecordTitleForSingleCheckboxType(): void
    {
        $input = [
            'tableName' => 'aTable',
            'isInlineChild' => false,
            'databaseRow' => [
                'aField' => 1,
            ],
            'processedTca' => [
                'ctrl' => [
                    'label' => 'aField',
                ],
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'check',
                        ],
                    ],
                ],
            ],
        ];

        $languageService = $this->createMock(LanguageService::class);
        $GLOBALS['LANG'] = $languageService;
        $languageService->expects($this->atLeastOnce())->method('sL')->with(self::anything())->willReturnArgument(0);

        $expected = $input;
        $expected['recordTitle'] = 'LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:yes';
        self::assertSame($expected, (new TcaRecordTitle())->addData($input));
    }

    #[Test]
    public function addDataReturnsRecordTitleForArrayCheckboxType(): void
    {
        $input = [
            'tableName' => 'aTable',
            'isInlineChild' => false,
            'databaseRow' => [
                'aField' => '5',
            ],
            'processedTca' => [
                'ctrl' => [
                    'label' => 'aField',
                ],
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'check',
                            'items' => [
                                ['label' => 'foo'],
                                ['label' => 'bar'],
                                ['label' => 'baz'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $languageService = $this->createMock(LanguageService::class);
        $GLOBALS['LANG'] = $languageService;
        $languageService->method('sL')->with(self::anything())->willReturnArgument(0);

        $expected = $input;
        $expected['recordTitle'] = 'foo, baz';
        self::assertSame($expected, (new TcaRecordTitle())->addData($input));
    }

    #[Test]
    public function addDataReturnsEmptyRecordTitleForFlexType(): void
    {
        $input = [
            'tableName' => 'aTable',
            'isInlineChild' => false,
            'databaseRow' => [
                'aField' => [
                    'data' => [
                        'sDEF' => [
                            'lDEF' => [
                                'aFlexField' => [
                                    'vDEF' => 'aFlexValue',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'processedTca' => [
                'ctrl' => [
                    'label' => 'aField',
                ],
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'ds' => [
                                'sheets' => [
                                    'sDEF' => [
                                        'ROOT' => [
                                            'type' => 'array',
                                            'el' => [
                                                'aFlexField' => [
                                                    'label' => 'Some input field',
                                                    'config' => [
                                                        'type' => 'input',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],

                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['recordTitle'] = '';
        self::assertSame($expected, (new TcaRecordTitle())->addData($input));
    }

    #[Test]
    public function addDataReturnsRecordTitleForSelectType(): void
    {
        $input = [
            'tableName' => 'aTable',
            'isInlineChild' => false,
            'databaseRow' => [
                'aField' => [
                    '1',
                    '2',
                ],
            ],
            'processedTca' => [
                'ctrl' => [
                    'label' => 'aField',
                ],
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'items' => [
                                ['label' => 'foo', 'value' => 1, 'icon' => null, 'group' => null],
                                ['label' => 'bar', 'value' => 2, 'icon' => null, 'group' => null],
                                ['label' => 'baz', 'value' => 4, 'icon' => null, 'group' => null],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['recordTitle'] = 'foo, bar';
        self::assertSame($expected, (new TcaRecordTitle())->addData($input));
    }

    #[Test]
    public function addDataReturnsStrippedAndTrimmedValueForTextType(): void
    {
        $input = [
            'tableName' => 'aTable',
            'isInlineChild' => false,
            'databaseRow' => [
                'aField' => '<p> text </p>',
            ],
            'processedTca' => [
                'ctrl' => [
                    'label' => 'aField',
                ],
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'text',
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['recordTitle'] = 'text';
        self::assertSame($expected, (new TcaRecordTitle())->addData($input));
    }
}
