<?php

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

use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordTypeValue;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class DatabaseRecordTypeValueTest extends UnitTestCase
{
    /**
     * @var DatabaseRecordTypeValue|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $subject;

    protected function setUp(): void
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
            'tableName' => 'aTable',
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
        self::assertSame($expected, $this->subject->addData($input));
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
        self::assertSame($expected, $this->subject->addData($input));
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
        self::assertSame($expected, $this->subject->addData($input));
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

        self::assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfTypePointsToANotExistingField()
    {
        $input = [
            'tableName' => 'aTable',
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

        self::assertSame($expected, $this->subject->addData($input));
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

        self::assertSame($expected, $this->subject->addData($input));
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

        self::assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfValueTypesNotExistsAndNoFallbackExists()
    {
        $input = [
            'tableName' => 'aTable',
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
            'tableName' => 'aTable',
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

        $this->subject->expects(self::once())
            ->method('getDatabaseRow')
            ->with('foreignTable', 42, 'foreignField')
            ->willReturn($foreignRecordResult);

        $expected = $input;
        $expected['recordTypeValue'] = '3';

        self::assertSame($expected, $this->subject->addData($input));
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
                'uid_local' => [
                    [
                        'uid' => 222,
                    ],
                ],
            ],
        ];

        $foreignRecordResult = [
            'type' => 2,
        ];

        $this->subject->expects(self::once())
            ->method('getDatabaseRow')
            ->with('sys_file', 222, 'type')
            ->willReturn($foreignRecordResult);

        $expected = $input;
        $expected['recordTypeValue'] = '2';

        self::assertSame($expected, $this->subject->addData($input));
    }
}
