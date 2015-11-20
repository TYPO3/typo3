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

use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordOverrideValues;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case
 */
class DatabaseRecordOverrideValuesTest extends UnitTestCase
{
    /**
     * @var DatabaseRecordOverrideValues
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = new DatabaseRecordOverrideValues();
    }

    /**
     * @test
     */
    public function addDataReturnSameDataIfNoOverrideValuesSet()
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                ],
            ],
            'databaseRow' => [
                'uid' => 42,
            ],
            'overrideValues' => [
                'anotherField' => 13,
            ]
        ];

        $this->assertSame($input, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsDatabaseRowAndTcaType()
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
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
            'databaseRow' => [
                'uid' => 42,
            ],
            'overrideValues' => [
                'aField' => 256,
                'anotherField' => 13,
            ]
        ];

        $expected = $input;
        $expected['databaseRow']['aField'] = 256;
        $expected['databaseRow']['anotherField'] = 13;
        $expected['processedTca']['columns']['aField']['config'] = [
            'type' => 'hidden',
            'renderType' => 'hidden',
        ];
        $expected['processedTca']['columns']['anotherField']['config'] = [
            'type' => 'hidden',
            'renderType' => 'hidden',
        ];

        $this->assertSame($expected, $this->subject->addData($input));
    }
}
