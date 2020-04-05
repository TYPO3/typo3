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

use TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsOverrides;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class TcaColumnsOverridesTest extends UnitTestCase
{
    /**
     * @var TcaColumnsOverrides
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new TcaColumnsOverrides();
    }

    /**
     * @test
     */
    public function addDataRemovesGivenColumnsOverrides()
    {
        $input = [
            'command' => 'new',
            'tableName' => 'aTable',
            'recordTypeValue' => 'foo',
            'processedTca' => [
                'columns' => [],
                'types' => [
                    'foo' => [
                        'showitem' => [],
                        'columnsOverrides' => [],
                    ],
                ],
            ],
        ];

        $expected = $input;
        unset($expected['processedTca']['types']['foo']['columnsOverrides']);

        self::assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataMergesColumnsOverridesIntoColumns()
    {
        $input = [
            'command' => 'new',
            'tableName' => 'aTable',
            'recordTypeValue' => 'foo',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'aConfig' => 'aValue',
                        'anotherConfig' => 'anotherValue',
                    ],
                ],
                'types' => [
                    'foo' => [
                        'showitem' => [],
                        'columnsOverrides' => [
                            'aField' => [
                                'aConfig' => 'aDifferentValue',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['processedTca']['columns']['aField']['aConfig'] = 'aDifferentValue';
        unset($expected['processedTca']['types']['foo']['columnsOverrides']);

        self::assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataMergesColumnsOverridesDefaultValueIntoDatabaseRow()
    {
        $input = [
            'command' => 'new',
            'tableName' => 'aTable',
            'vanillaUid' => 12,
            'databaseRow' => [
                'uid' => 42,
            ],
            'recordTypeValue' => 'foo',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'aConfig' => 'aValue'
                    ],
                ],
                'types' => [
                    'foo' => [
                        'showitem' => [],
                        'columnsOverrides' => [
                            'aField' => [
                                'config' => [
                                    'default' => 'aDefault'
                                ]
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['databaseRow']['aField'] = 'aDefault';
        $expected['processedTca']['columns']['aField']['config']['default'] = 'aDefault';
        unset($expected['processedTca']['types']['foo']['columnsOverrides']);

        self::assertEquals($expected, $this->subject->addData($input));
    }
}
