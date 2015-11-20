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

use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case
 */
class DatabaseRowDefaultValuesTest extends UnitTestCase
{
    /**
     * @var DatabaseRowDefaultValues
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = new DatabaseRowDefaultValues();
    }

    /**
     * @test
     */
    public function addDataKeepsExistingValue()
    {
        $input = [
            'databaseRow' => [
                'aDefinedField' => 'aValue',
            ],
            'processedTca' => [
                'columns' => [
                    'aDefinedField' => [],
                ],
            ],
        ];
        $expected = $input;
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataKeepsExistingNullValueWithEvalNull()
    {
        $input = [
            'databaseRow' => [
                'aField' => null,
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'eval' => 'null',
                        ],
                    ],
                ],
            ],
        ];
        $expected = $input;
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsNullValueWithDefaultNullForNewRecord()
    {
        $input = [
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'eval' => 'null',
                            'default' => null,
                        ],
                    ],
                ],
            ],
        ];
        $expected = $input;
        $expected['databaseRow']['aField'] = null;
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsDefaultValueIfEvalNullIsSet()
    {
        $input = [
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'eval' => 'null',
                            'default' => 'foo',
                        ],
                    ],
                ],
            ],
        ];
        $expected = $input;
        $expected['databaseRow']['aField'] = 'foo';
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsDefaultValueIsSet()
    {
        $input = [
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'default' => 'foo',
                        ],
                    ],
                ],
            ],
        ];
        $expected = $input;
        $expected['databaseRow']['aField'] = 'foo';
        $this->assertSame($expected, $this->subject->addData($input));
    }
}
