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

use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDateTimeFields;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class DatabaseRowDateTimeFieldsTest extends UnitTestCase
{
    /**
     * @test
     */
    public function addDataSetsTimestampZeroForDefaultDateField(): void
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'datetime',
                            'dbType' => 'date',
                        ],
                    ],
                ],
            ],
        ];
        $expected = $input;
        $expected['databaseRow']['aField'] = 0;
        self::assertEquals($expected, (new DatabaseRowDateTimeFields())->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsTimestampZeroForDefaultDateTimeField(): void
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'datetime',
                            'dbType' => 'datetime',
                        ],
                    ],
                ],
            ],
        ];
        $expected = $input;
        $expected['databaseRow']['aField'] = 0;
        self::assertEquals($expected, (new DatabaseRowDateTimeFields())->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsTimestampZeroForDefaultTimeField(): void
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'datetime',
                            'dbType' => 'time',
                        ],
                    ],
                ],
            ],
        ];
        $expected = $input;
        $expected['databaseRow']['aField'] = 0;
        self::assertEquals($expected, (new DatabaseRowDateTimeFields())->addData($input));
    }

    /**
     * @test
     */
    public function addDataConvertsDateStringToTimestamp(): void
    {
        $oldTimezone = date_default_timezone_get();
        date_default_timezone_set('UTC');
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'datetime',
                            'dbType' => 'date',
                        ],
                    ],
                ],
            ],
            'databaseRow' => [
                'aField' => '2015-07-27',
            ],
        ];
        $expected = $input;
        $expected['databaseRow']['aField'] = '2015-07-27T00:00:00+00:00';
        self::assertEquals($expected, (new DatabaseRowDateTimeFields())->addData($input));
        date_default_timezone_set($oldTimezone);
    }

    /**
     * @test
     */
    public function addDataConvertsDateTimeStringToTimestamp(): void
    {
        $oldTimezone = date_default_timezone_get();
        date_default_timezone_set('UTC');
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'datetime',
                            'dbType' => 'datetime',
                        ],
                    ],
                ],
            ],
            'databaseRow' => [
                'aField' => '2015-07-27 15:25:32',
            ],
        ];
        $expected = $input;
        $expected['databaseRow']['aField'] = '2015-07-27T15:25:32+00:00';
        self::assertEquals($expected, (new DatabaseRowDateTimeFields())->addData($input));
        date_default_timezone_set($oldTimezone);
    }

    /**
     * @test
     */
    public function addDataConvertsTimeStringToTimestamp(): void
    {
        $oldTimezone = date_default_timezone_get();
        date_default_timezone_set('UTC');
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'datetime',
                            'dbType' => 'time',
                        ],
                    ],
                ],
            ],
            'databaseRow' => [
                'aField' => '15:25:32',
            ],
        ];
        $expected = $input;
        $expected['databaseRow']['aField'] = date('Y-m-d') . 'T15:25:32+00:00';
        self::assertEquals($expected, (new DatabaseRowDateTimeFields())->addData($input));
        date_default_timezone_set($oldTimezone);
    }

    /**
     * @test
     */
    public function addDataAppliesResetValueForEmptyValue(): void
    {
        foreach (QueryHelper::getDateTimeTypes() as $dbType) {
            $input = [
                'tableName' => 'aTable',
                'processedTca' => [
                    'columns' => [
                        'aField' => [
                            'config' => [
                                'type' => 'datetime',
                                'dbType' => $dbType,
                            ],
                        ],
                    ],
                ],
                'databaseRow' => [
                    'aField' => QueryHelper::getDateTimeFormats()[$dbType]['empty'],
                ],
            ];
            $expected = $input;
            $expected['databaseRow']['aField'] = QueryHelper::getDateTimeFormats()[$dbType]['reset'];
            self::assertSame($expected, (new DatabaseRowDateTimeFields())->addData($input));
        }
    }
}
