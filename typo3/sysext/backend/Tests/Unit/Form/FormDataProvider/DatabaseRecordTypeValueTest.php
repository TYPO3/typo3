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
     * @var DatabaseRecordTypeValue
     */
    protected $subject;

    /**
     * @var DatabaseConnection | ObjectProphecy
     */
    protected $dbProphecy;

    protected function setUp()
    {
        $this->dbProphecy = $this->prophesize(DatabaseConnection::class);
        $GLOBALS['TYPO3_DB'] = $this->dbProphecy->reveal();

        $this->subject = new DatabaseRecordTypeValue();
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

        $this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1438185331);

        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataSetsRecordTypeValueToHistoricalOneIfTypeZeroIsNotDefined()
    {
        $input = [
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

        $this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1438183881);

        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataSetsRecordTypeValueToValueOfDatabaseField()
    {
        $input = [
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

        $this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1438185437);

        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataSetsRecordTypeValueToValueOfDefaultLanguageRecordIfConfiguredAsExclude()
    {
        $input = [
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

        $this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1325862241);

        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionForForeignTypeIfPointerConfigurationHasNoTable()
    {
        $input = [
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

        $this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1438253614);

        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataSetsTypeValueFromForeignTableRecord()
    {
        $input = [
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
        // Required for BackendUtility::getRecord
        $GLOBALS['TCA']['foreignTable'] = ['foo'];

        $this->dbProphecy->exec_SELECTgetSingleRow('foreignField', 'foreignTable', 'uid=42')->shouldBeCalled()->willReturn($foreignRecordResult);

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
        // Required for BackendUtility::getRecord
        $GLOBALS['TCA']['sys_file'] = ['foo'];

        $this->dbProphecy->exec_SELECTgetSingleRow('type', 'sys_file', 'uid=222')->shouldBeCalled()->willReturn($foreignRecordResult);

        $expected = $input;
        $expected['recordTypeValue'] = '2';

        $this->assertSame($expected, $this->subject->addData($input));
    }
}
