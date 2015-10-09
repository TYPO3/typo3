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

use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessShowitem;

/**
 * Test case
 */
class TcaColumnsProcessShowitemTest extends UnitTestCase
{
    /**
     * @var TcaColumnsProcessShowitem
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = new TcaColumnsProcessShowitem();
    }

    /**
     * @test
     */
    public function addDataRegistersColumnsFieldReferencedInShowitems()
    {
        $input = [
            'columnsToProcess' => [],
            'databaseRow' => [],
            'recordTypeValue' => 'aType',
            'processedTca' => [
                'types' => [
                    'aType' => [
                        'showitem' => 'keepMe'
                    ],
                ],
                'columns' => [
                    'keepMe' => [
                        'config' => [
                            'type' => 'input',
                        ]
                    ],
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                        ]
                    ]
                ]
            ]
        ];

        $expected = $input;
        $expected['columnsToProcess'] = ['keepMe'];

        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataKeepsColumnsFieldReferencedInPalette()
    {
        $input = [
            'columnsToProcess' => [],
            'databaseRow' => [],
            'recordTypeValue' => 'aType',
            'processedTca' => [
                'types' => [
                    'aType' => [
                        'showitem' => '--palette--;;aPalette'
                    ],
                ],
                'palettes' => [
                    'aPalette' => [
                        'showitem' => 'keepMe',
                    ],
                ],
                'columns' => [
                    'keepMe' => [
                        'config' => [
                            'type' => 'input',
                        ]
                    ],
                    'bField' => [
                        'config' => [
                            'type' => 'input',
                        ]
                    ]
                ]
            ]
        ];

        $expected = $input;
        $expected['columnsToProcess'] = ['keepMe'];

        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataSkipsColumnsNotReferencedInShowitemOrPalette()
    {
        $input = [
            'databaseRow' => [],
            'recordTypeValue' => 'aType',
            'processedTca' => [
                'types' => [
                    'aType' => [
                        'showitem' => '--palette--;;aPalette, anotherField'
                    ],
                ],
                'palettes' => [
                    'aPalette' => [
                        'showitem' => 'aField',
                    ],
                ],
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                        ]
                    ],
                    'removeMe' => [
                        'config' => [
                            'type' => 'input',
                        ]
                    ],
                    'anotherField' => [
                        'config' => [
                            'type' => 'input',
                        ]
                    ]
                ]
            ]
        ];

        $expected = $input;
        $expected['columnsToProcess'] = ['aField', 'anotherField'];

        $this->assertSame($expected, $this->subject->addData($input));
    }
}
