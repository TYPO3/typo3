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

use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordTypeValue;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case
 */
class DatabaseRecordTypeValueTest extends UnitTestCase
{
    /**
     * @var DatabaseRecordTypeValue|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subject;

    /**
     * @var DatabaseConnection | ObjectProphecy
     */
    protected $dbProphecy;

    protected function setUp()
    {
        $this->subject = $this->getMockBuilder(DatabaseRecordTypeValue::class)
            ->setMethods(['getDatabaseRow'])
            ->getMock();
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfTcaTypesAreEmpty()
    {
        $input = [
            'processedTca' => [
                'types' => [],
            ],
        ];

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1438185331);

        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataKeepsExistingTcaRecordTypeValue()
    {
        $input = [
            'recordTypeValue' => 'egon',
            'processedTca' => [
                'types' => [
                    '1' => 'foo',
                ],
            ],
        ];
        $expected = $input;
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataKeepsExistingTcaRecordTypeValueWithValueZero()
    {
        $input = [
            'recordTypeValue' => 0,
            'processedTca' => [
                'types' => [
                    '1' => 'foo',
                ],
            ],
        ];
        $expected = $input;
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsRecordTypeValueToHistoricalOneIfTypeZeroIsNotDefined()
    {
        $input = [
            'recordTypeValue' => '',
            'processedTca' => [
                'types' => [
                    '1' => 'foo',
                ],
            ],
        ];
        $expected = $input;
        $expected['recordTypeValue'] = '1';
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsRecordTypeValueToZero()
    {
        $input = [
            'recordTypeValue' => '',
            'processedTca' => [
                'types' => [
                    '0' => 'foo',
                ],
            ],
        ];

        $expected = $input;
        $expected['recordTypeValue'] = '0';

        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfTypePointsToANotExistingField()
    {
        $input = [
            'recordTypeValue' => '',
            'processedTca' => [
                'ctrl' => [
                    'type' => 'notExists',
                ],
                'types' => [
                    '0' => 'foo',
                ],
            ],
            'databaseRow' => [
                'uid' => 23,
            ],
        ];

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1438183881);

        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataSetsRecordTypeValueToValueOfDatabaseField()
    {
        $input = [
            'recordTypeValue' => '',
            'processedTca' => [
                'ctrl' => [
                    'type' => 'aField',
                ],
                'types' => [
                    '3' => 'foo',
                ],
            ],
            'databaseRow' => [
                'aField' => 3,
            ],
        ];

        $expected = $input;
        $expected['recordTypeValue'] = '3';

        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsRecordTypeValueToZeroIfValueOfDatabaseFieldIsNotDefinedInTca()
    {
        $input = [
            'recordTypeValue' => '',
            'processedTca' => [
                'ctrl' => [
                    'type' => 'aField',
                ],
                'types' => [
                    '0' => 'foo',
                ],
            ],
            'databaseRow' => [
                'aField' => 3,
            ],
        ];

        $expected = $input;
        $expected['recordTypeValue'] = '0';

        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsRecordTypeValueToZeroIfValueOfDatabaseFieldIsEmptyString()
    {
        $input = [
            'recordTypeValue' => '',
            'processedTca' => [
                'ctrl' => [
                    'type' => 'aField',
                ],
                'types' => [
                    '0' => 'foo',
                ],
            ],
            'databaseRow' => [
                'aField' => '',
            ],
        ];

        $expected = $input;
        $expected['recordTypeValue'] = '0';

        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfValueTypesNotExistsAndNoFallbackExists()
    {
        $input = [
            'recordTypeValue' => '',
            'processedTca' => [
                'ctrl' => [
                    'type' => 'aField',
                ],
                'types' => [
                    '42' => 'foo',
                ],
            ],
            'databaseRow' => [
                'aField' => 23,
            ],
        ];

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1438185437);

        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataSetsRecordTypeValueToValueOfDefaultLanguageRecordIfConfiguredAsExclude()
    {
        $input = [
            'recordTypeValue' => '',
            'processedTca' => [
                'ctrl' => [
                    'languageField' => 'sys_language_uid',
                    'type' => 'aField',
                ],
                'columns' => [
                    'aField' => [
                        'l10n_mode' => 'exclude',
                    ],
                ],
                'types' => [
                    '3' => 'foo',
                ],
            ],
            'databaseRow' => [
                'sys_language_uid' => 2,
                'aField' => 4,
            ],
            'defaultLanguageRow' => [
                'aField' => 3,
            ],
        ];

        $expected = $input;
        $expected['recordTypeValue'] = '3';

        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsRecordTypeValueToValueOfDefaultLanguageRecordIfConfiguredAsMergeIfNotBlank()
    {
        $input = [
            'recordTypeValue' => '',
            'processedTca' => [
                'ctrl' => [
                    'languageField' => 'sys_language_uid',
                    'type' => 'aField',
                ],
                'columns' => [
                    'aField' => [
                        'l10n_mode' => 'mergeIfNotBlank',
                    ],
                ],
                'types' => [
                    '3' => 'foo',
                ],
            ],
            'databaseRow' => [
                'sys_language_uid' => 2,
                'aField' => '',
            ],
            'defaultLanguageRow' => [
                'aField' => 3,
            ],
        ];

        $expected = $input;
        $expected['recordTypeValue'] = '3';

        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsRecordTypeValueToValueOfLocalizedRecordIfConfiguredAsMergeIfNotBlankButNotBlank()
    {
        $input = [
            'recordTypeValue' => '',
            'processedTca' => [
                'ctrl' => [
                    'languageField' => 'sys_language_uid',
                    'type' => 'aField',
                ],
                'columns' => [
                    'aField' => [
                        'l10n_mode' => 'mergeIfNotBlank',
                    ],
                ],
                'types' => [
                    '3' => 'foo',
                ],
            ],
            'databaseRow' => [
                'sys_language_uid' => 2,
                'aField' => 3,
            ],
            'defaultLanguageRow' => [
                'aField' => 4,
            ],
        ];

        $expected = $input;
        $expected['recordTypeValue'] = '3';

        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionForForeignTypeConfigurationNotAsSelectOrGroup()
    {
        $input = [
            'recordTypeValue' => '',
            'processedTca' => [
                'ctrl' => [
                    'type' => 'localField:foreignField',
                ],
                'columns' => [
                    'localField' => [
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                ],
                'types' => [
                    '3' => 'foo',
                ],
            ],
        ];

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1325862241);

        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionForForeignTypeIfPointerConfigurationHasNoTable()
    {
        $input = [
            'recordTypeValue' => '',
            'processedTca' => [
                'ctrl' => [
                    'type' => 'localField:foreignField',
                ],
                'columns' => [
                    'localField' => [
                        'config' => [
                            'type' => 'select',
                        ],
                    ],
                ],
                'types' => [
                    '3' => 'foo',
                ],
            ],
            'databaseRow' => [
                'localField' => 3,
            ],
        ];

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1438253614);

        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataSetsTypeValueFromForeignTableRecord()
    {
        $input = [
            'recordTypeValue' => '',
            'processedTca' => [
                'ctrl' => [
                    'type' => 'localField:foreignField',
                ],
                'columns' => [
                    'localField' => [
                        'config' => [
                            'type' => 'select',
                            'foreign_table' => 'foreignTable',
                        ],
                    ],
                ],
                'types' => [
                    '3' => 'foo',
                ],
            ],
            'databaseRow' => [
                // Point to record 42 in foreignTable
                'localField' => 42,
            ],
        ];

        $foreignRecordResult = [
            'foreignField' => 3,
        ];

        $this->subject->expects($this->once())
            ->method('getDatabaseRow')
            ->with('foreignTable', 42, 'foreignField')
            ->willReturn($foreignRecordResult);

        $expected = $input;
        $expected['recordTypeValue'] = '3';

        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsTypeValueFromNestedTcaGroupField()
    {
        $input = [
            'recordTypeValue' => '',
            'processedTca' => [
                'ctrl' => [
                    'type' => 'uid_local:type',
                ],
                'columns' => [
                    'uid_local' => [
                        'config' => [
                            'type' => 'group',
                            'internal_type' => 'db',
                            'size' => 1,
                            'maxitems' => 1,
                            'minitems' => 0,
                            'allowed' => 'sys_file'
                        ],
                    ],
                ],
                'types' => [
                    '2' => 'foo',
                ],
            ],
            'databaseRow' => [
                // Processed group field
                'uid_local' => 'sys_file_222|my_test.jpg',
            ],
        ];

        $foreignRecordResult = [
            'type' => 2,
        ];

        $this->subject->expects($this->once())
            ->method('getDatabaseRow')
            ->with('sys_file', 222, 'type')
            ->willReturn($foreignRecordResult);

        $expected = $input;
        $expected['recordTypeValue'] = '2';

        $this->assertSame($expected, $this->subject->addData($input));
    }
}
