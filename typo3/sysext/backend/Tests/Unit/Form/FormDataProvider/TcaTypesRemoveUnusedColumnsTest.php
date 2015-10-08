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

use TYPO3\CMS\Backend\Form\FormDataProvider\TcaTypesRemoveUnusedColumns;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case
 */
class TcaTypesRemoveUnusedColumnsTest extends UnitTestCase
{
    /**
     * @var TcaTypesRemoveUnusedColumns
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = new TcaTypesRemoveUnusedColumns();
    }

    /**
     * @test
     */
    public function addDataKeepsColumnsFieldReferencedInShowitems()
    {
        $input = [
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
        unset($expected['processedTca']['columns']['aField']);

        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataKeepsColumnsFieldReferencedInPalette()
    {
        $input = [
            'databaseRow' => [],
            'recordTypeValue' => 'aType',
            'processedTca' => [
                'types' => [
                    'aType' => [
                        'showitem' => '--palette--;;aPalette'
                    ],
                ],
                'palettes' => [
                    'aPalette' => array(
                        'showitem' => 'keepMe',
                    ),
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
        unset($expected['processedTca']['columns']['bField']);

        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataRemovesColumnsNotReferencedInShowitemOrPalette()
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
                    'aPalette' => array(
                        'showitem' => 'aField',
                    ),
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
        unset($expected['processedTca']['columns']['removeMe']);

        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataKeepsColumnsFieldReferencedInLabel()
    {
        $input = [
            'databaseRow' => [],
            'recordTypeValue' => 'aType',
            'processedTca' => [
                'ctrl' => [
                    'label' => 'keepMe'
                ],
                'types' => [
                    'aType' => [
                        'showitem' => 'keepMeToo'
                    ],
                ],
                'palettes' => [
                    'aPalette' => array(
                        'showitem' => 'keepMe',
                    ),
                ],
                'columns' => [
                    'keepMe' => [
                        'config' => [
                            'type' => 'input',
                        ]
                    ],
                    'keepMeToo' => [
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
        unset($expected['processedTca']['columns']['aField']);

        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataKeepsColumnsFieldReferencedInLabelAlt()
    {
        $input = [
            'databaseRow' => [],
            'recordTypeValue' => 'aType',
            'processedTca' => [
                'ctrl' => [
                    'label' => 'keepMe',
                    'label_alt' => 'keepMeToo'
                ],
                'types' => [
                    'aType' => [
                        'showitem' => 'keepMe'
                    ],
                ],
                'palettes' => [
                    'aPalette' => array(
                        'showitem' => 'keepMe',
                    ),
                ],
                'columns' => [
                    'keepMe' => [
                        'config' => [
                            'type' => 'input',
                        ]
                    ],
                    'keepMeToo' => [
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
        unset($expected['processedTca']['columns']['aField']);

        $this->assertSame($expected, $this->subject->addData($input));
    }
}
