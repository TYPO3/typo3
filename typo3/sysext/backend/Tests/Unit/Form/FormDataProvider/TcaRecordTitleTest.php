<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

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
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaRecordTitle;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Test case
 */
class TcaRecordTitleTest extends UnitTestCase
{
    /**
     * @var TcaRecordTitle
     */
    protected $subject;

    /**
     * @var string
     */
    protected $timeZone;

    public function setUp()
    {
        $this->subject = new TcaRecordTitle();
        $this->timeZone = date_default_timezone_get();
        date_default_timezone_set('UTC');
    }

    protected function tearDown()
    {
        date_default_timezone_set($this->timeZone);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionWithMissingLabel()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRew' => [],
            'processedTca' => [
                'ctrl' => [],
            ],
        ];
        $this->setExpectedException(\UnexpectedValueException::class, $this->any(), 1443706103);
        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataReturnsRecordTitleForLabelUserFunction()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [],
            'processedTca' => [
                'ctrl' => [
                    'label' => 'uid',
                    'label_userFunc' => function (&$parameters) {
                        $parameters['title'] = 'Test';
                    }
                ],
                'columns' => [],
            ],
        ];

        $expected = $input;
        $expected['recordTitle'] = 'Test';

        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataReturnsRecordTitleForFormattedLabelUserFunction()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [],
            'isInlineChild' => true,
            'processedTca' => [
                'ctrl' => [
                    'label' => 'uid',
                    'formattedLabel_userFunc' => function (&$parameters) {
                        $parameters['title'] = 'Test';
                    }
                ],
                'columns' => [],
            ],
        ];

        $expected = $input;
        $expected['recordTitle'] = 'Test';

        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataReturnsRecordTitleForInlineChildWithForeignLabel()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => 'aValue',
            ],
            'processedTca' => [
                'ctrl' => [
                    'label' => 'foo',
                    'label_userFunc' => function (&$parameters) {
                        $parameters['title'] = 'Value that MUST NOT be used, otherwise the code is broken.';
                    }
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
                'foreign_label' => 'aField',
            ],
        ];
        $expected = $input;
        $expected['recordTitle'] = 'aValue';
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataOverridesRecordTitleWithFormattedLabelUserFuncForInlineChildWithForeignLabel()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => 'aValue',
            ],
            'processedTca' => [
                'ctrl' => [
                    'label' => 'foo',
                    'formattedLabel_userFunc' => function (&$parameters) {
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
            'inlineParentConfig' => [
                'foreign_label' => 'aField',
            ],
        ];
        $expected = $input;
        $expected['recordTitle'] = 'aFormattedLabel';
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataReturnsRecordTitleForInlineChildWithSymmetricLabel()
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
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataReturnsRecordTitleForUid()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'uid' => 'NEW56017ee37d10e587251374',
            ],
            'processedTca' => [
                'ctrl' => [
                    'label' => 'uid'
                ],
                'columns' => [],
            ]
        ];

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $expected = $input;
        $expected['recordTitle'] = 'NEW56017ee37d10e587251374';
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * Data provider for addDataReturnsRecordTitleForInputType
     * Each data set is an array with the following elements:
     *  - TCA field ['config'] section
     *  - Database value for field
     *  - expected title to be generated
     *
     * @returns array
     */
    public function addDataReturnsRecordTitleForInputTypeDataProvider()
    {
        return [
            'new record' => [
                [
                    'type' => 'input',
                ],
                '',
                '',
            ],
            'plain text input' => [
                [
                    'type' => 'input',
                ],
                'aValue',
                'aValue',
            ],
            'date input' => [
                [
                    'type' => 'input',
                    'eval' => 'date'
                ],
                '978307261',
                '01-01-01 (-7 days)',
            ],
            'date input (dbType: date)' => [
                [
                    'type' => 'input',
                    'eval' => 'date',
                    'dbType' => 'date'
                ],
                '2001-01-01',
                '01-01-01 (-7 days)',
            ],
            'date input (disableAgeDisplay: TRUE)' => [
                [
                    'type' => 'input',
                    'eval' => 'date',
                    'disableAgeDisplay' => true
                ],
                '978307261',
                '01-01-01',
            ],
            'time input' => [
                [
                    'type' => 'input',
                    'eval' => 'time',
                ],
                '44100',
                '12:15',
            ],
            'timesec input' => [
                [
                    'type' => 'input',
                    'eval' => 'timesec',
                ],
                '44130',
                '12:15:30',
            ],
            'datetime input' => [
                [
                    'type' => 'input',
                    'eval' => 'datetime',
                    'dbType' => 'date'
                ],
                '978307261',
                '01-01-01 00:01',
            ],
            'datetime input (dbType: datetime)' => [
                [
                    'type' => 'input',
                    'eval' => 'datetime',
                    'dbType' => 'datetime'
                ],
                '2014-12-31 23:59:59',
                '31-12-14 23:59',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider addDataReturnsRecordTitleForInputTypeDataProvider
     *
     * @param array $fieldConfig
     * @param string $fieldValue
     * @param string $expectedTitle
     */
    public function addDataReturnsRecordTitleForInputType($fieldConfig, $fieldValue, $expectedTitle)
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'uid' => '1',
                'aField' => $fieldValue,
            ],
            'processedTca' => [
                'ctrl' => [
                    'label' => 'aField'
                ],
                'columns' => [
                    'aField' => [
                        'config' => $fieldConfig,
                    ]
                ],
            ]
        ];

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.minutesHoursDaysYears')
            ->willReturn(' min| hrs| days| yrs| min| hour| day| year');
        $languageService->sL(Argument::cetera())->willReturnArgument(0);
        $GLOBALS['EXEC_TIME'] = 978912061;

        $expected = $input;
        $expected['recordTitle'] = $expectedTitle;
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataReturnsRecordTitleWithAlternativeLabel()
    {
        $input = [
            'tableName' => 'aTable',
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
                            'type' => 'input'
                        ]
                    ],
                    'anotherField' => [
                        'config' => [
                            'type' => 'input'
                        ]
                    ]
                ],
            ]
        ];

        $expected = $input;
        $expected['recordTitle'] = 'anotherValue';
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataReturnsRecordTitleWithMultipleAlternativeLabels()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'uid' => '1',
                'aField' => '',
                'anotherField' => '',
                'additionalField' => 'additionalValue'
            ],
            'processedTca' => [
                'ctrl' => [
                    'label' => 'aField',
                    'label_alt' => 'anotherField,additionalField',
                ],
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input'
                        ]
                    ],
                    'anotherField' => [
                        'config' => [
                            'type' => 'input'
                        ]
                    ],
                    'additionalField' => [
                        'config' => [
                            'type' => 'input'
                        ]
                    ],
                ],
            ]
        ];

        $expected = $input;
        $expected['recordTitle'] = 'additionalValue';
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataReturnsRecordTitleWithForcedAlternativeLabel()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'uid' => '1',
                'aField' => 'aField',
                'anotherField' => 'anotherField'
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
                            'type' => 'input'
                        ]
                    ],
                    'anotherField' => [
                        'config' => [
                            'type' => 'input'
                        ]
                    ],
                ],
            ]
        ];

        $expected = $input;
        $expected['recordTitle'] = 'aField, anotherField';
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataReturnsRecordTitleWithMultipleForcedAlternativeLabels()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'uid' => '1',
                'aField' => 'aField',
                'anotherField' => 'anotherField',
                'additionalField' => 'additionalValue'
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
                            'type' => 'input'
                        ]
                    ],
                    'anotherField' => [
                        'config' => [
                            'type' => 'input'
                        ]
                    ],
                    'additionalField' => [
                        'config' => [
                            'type' => 'input'
                        ]
                    ],
                ],
            ]
        ];

        $expected = $input;
        $expected['recordTitle'] = 'aField, anotherField, additionalValue';
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataReturnsRecordTitleIgnoresEmptyAlternativeLabels()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'uid' => '1',
                'aField' => 'aField',
                'anotherField' => '',
                'additionalField' => 'additionalValue'
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
                            'type' => 'input'
                        ]
                    ],
                    'anotherField' => [
                        'config' => [
                            'type' => 'input'
                        ]
                    ],
                    'additionalField' => [
                        'config' => [
                            'type' => 'input'
                        ]
                    ],
                ],
            ]
        ];

        $expected = $input;
        $expected['recordTitle'] = 'aField, additionalValue';
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataReturnsRecordTitleForRadioType()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'uid' => '1',
                'aField' => '2',
            ],
            'processedTca' => [
                'ctrl' => [
                    'label' => 'aField'
                ],
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'radio',
                            'items' => [
                                ['foo', 1],
                                ['bar', 2],
                                ['baz', 3],
                            ]
                        ]
                    ]
                ],
            ]
        ];

        $expected = $input;
        $expected['recordTitle'] = 'bar';
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataReturnsRecordTitleForInlineType()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'uid' => '1',
                'aField' => '2',
            ],
            'processedTca' => [
                'ctrl' => [
                    'label' => 'aField'
                ],
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline'
                        ],
                        'children' => [
                            [
                                'recordTitle' => 'foo',
                                'vanillaUid' => 2
                            ]
                        ]
                    ]
                ],
            ]
        ];

        $expected = $input;
        $expected['recordTitle'] = 'foo';
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * Data provider for addDataReturnsRecordTitleForGroupType
     * Each data set is an array with the following elements:
     *  - TCA field configuration (merged with base config)
     *  - Database value for field
     *  - expected title to be generated
     *
     * @returns array
     */
    public function addDataReturnsRecordTitleForGroupTypeDataProvider()
    {
        return [
            'new record' => [
                [
                    'internal_type' => 'db',
                ],
                '',
                ''
            ],
            'internal_type: file' => [
                [
                    'internal_type' => 'file',
                ],
                'somePath/aFile.jpg,someOtherPath/anotherFile.png',
                'somePath/aFile.jpg, someOtherPath/anotherFile.png',
            ],
            'internal_type: db, single table, single record' => [
                [
                    'internal_type' => 'db',
                    'allowed' => 'aTable'
                ],
                '1|aValue',
                'aValue',
            ],
            'internal_type: db, single table, multiple records' => [
                [
                    'internal_type' => 'db',
                    'allowed' => 'aTable'
                ],
                '1|aValue,3|anotherValue',
                'aValue, anotherValue',
            ],
            'internal_type: db, multiple tables, single record' => [
                [
                    'internal_type' => 'db',
                    'allowed' => 'aTable,anotherTable'
                ],
                'anotherTable_1|anotherValue',
                'anotherValue',
            ],
            'internal_type: db, multiple tables, multiple records' => [
                [
                    'internal_type' => 'db',
                    'allowed' => 'aTable,anotherTable'
                ],
                'anotherTable_1|anotherValue,aTable_1|aValue',
                'aValue, anotherValue',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider addDataReturnsRecordTitleForGroupTypeDataProvider
     *
     * @param array $fieldConfig
     * @param string $fieldValue
     * @param string $expectedTitle
     */
    public function addDataReturnsRecordTitleForGroupType($fieldConfig, $fieldValue, $expectedTitle)
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'uid' => '1',
                'aField' => $fieldValue,
            ],
            'processedTca' => [
                'ctrl' => [
                    'label' => 'aField'
                ],
                'columns' => [
                    'aField' => [
                        'config' => array_merge(
                            [
                                'type' => 'group',
                            ],
                            $fieldConfig
                        ),
                    ]
                ],
            ]
        ];

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $expected = $input;
        $expected['recordTitle'] = $expectedTitle;
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataReturnsRecordTitleForGroupTypeWithInternalTypeDb()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'uid' => '1',
                'aField' => 'aTable_1|aValue,anotherTable_2|anotherValue',
            ],
            'processedTca' => [
                'ctrl' => [
                    'label' => 'aField'
                ],
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'group',
                            'internal_type' => 'db',
                            'allowed' => 'aTable,anotherTable',
                        ]
                    ]
                ],
            ]
        ];

        $expected = $input;
        $expected['recordTitle'] = 'aValue, anotherValue';
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataReturnsRecordTitleForSingleCheckboxType()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => 1,
            ],
            'processedTca' => [
                'ctrl' => [
                    'label' => 'aField'
                ],
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'check',
                        ]
                    ]
                ],
            ]
        ];

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0)->shouldBeCalled();

        $expected = $input;
        $expected['recordTitle'] = 'LLL:EXT:lang/locallang_common.xlf:yes';
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataReturnsRecordTitleForArrayCheckboxType()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => '5'
            ],
            'processedTca' => [
                'ctrl' => [
                    'label' => 'aField'
                ],
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'check',
                            'items' => [
                                ['foo', ''],
                                ['bar', ''],
                                ['baz', ''],
                            ]
                        ]
                    ]
                ],
            ]
        ];

        $expected = $input;
        $expected['recordTitle'] = 'foo, baz';
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataReturnsEmptyRecordTitleForFlexType()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => [
                    'data' => [
                        'sDEF' => [
                            'lDEF' => [
                                'aFlexField' => [
                                    'vDEF' => 'aFlexValue',
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'processedTca' => [
                'ctrl' => [
                    'label' => 'aField'
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
                            ]

                        ]
                    ]
                ],
            ]
        ];

        $expected = $input;
        $expected['recordTitle'] = '';
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataReturnsRecordTitleForSelectType()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => [
                    '1',
                    '2'
                ]
            ],
            'processedTca' => [
                'ctrl' => [
                    'label' => 'aField'
                ],
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'items' => [
                                ['foo', 1, null, null],
                                ['bar', 2, null, null],
                                ['baz', 4, null, null],
                            ]
                        ]
                    ]
                ],
            ]
        ];

        $expected = $input;
        $expected['recordTitle'] = 'foo, bar';
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataReturnsStrippedAndTrimmedValueForTextType()
    {
        $input = [
            'tableName' => 'aTable',
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
        $this->assertSame($expected, $this->subject->addData($input));
    }
}
