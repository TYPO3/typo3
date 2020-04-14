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

use TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessShowitem;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class TcaColumnsProcessShowitemTest extends UnitTestCase
{
    /**
     * @test
     */
    public function addDataThrowsExceptionIfTypesHasNoShowitem()
    {
        $input = [
            'recordTypeValue' => 'aType',
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'type' => 'aType',
                    ],
                ],
                'types' => [
                    'aType' => [],
                ],
            ],
        ];
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1438614542);
        (new TcaColumnsProcessShowitem())->addData($input);
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
            'isInlineChild' => false,
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

        self::assertSame($expected, (new TcaColumnsProcessShowitem())->addData($input));
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
            'isInlineChild' => false,
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

        self::assertSame($expected, (new TcaColumnsProcessShowitem())->addData($input));
    }

    /**
     * @test
     */
    public function addDataSkipsColumnsNotReferencedInShowitemOrPalette()
    {
        $input = [
            'databaseRow' => [],
            'recordTypeValue' => 'aType',
            'isInlineChild' => false,
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

        self::assertSame($expected, (new TcaColumnsProcessShowitem())->addData($input));
    }

    /**
     * @test
     */
    public function addDataSkipsColumnsForCollapsedInlineChild()
    {
        $input = [
            'command' => 'edit',
            'databaseRow' => [
                'uid' => 42,
            ],
            'recordTypeValue' => 'aType',
            'processedTca' => [
                'types' => [
                    'aType' => [
                        'showitem' => 'aField',
                    ],
                ],
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                        ]
                    ],
                ],
            ],
            'inlineParentConfig' => [
                'foreign_table' => 'aTable',
            ],
            'isInlineChild' => true,
            'isInlineChildExpanded' => false,
            'isInlineAjaxOpeningContext' => false,
            'inlineExpandCollapseStateArray' => [],
        ];
        $expected = $input;
        self::assertSame($expected, (new TcaColumnsProcessShowitem())->addData($input));
    }

    /**
     * @test
     */
    public function addDataSkipsColumnsForCollapsedAllInlineChild()
    {
        $input = [
            'command' => 'edit',
            'databaseRow' => [
                'uid' => 42,
            ],
            'recordTypeValue' => 'aType',
            'processedTca' => [
                'types' => [
                    'aType' => [
                        'showitem' => 'aField',
                    ],
                ],
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                        ]
                    ],
                ],
            ],
            'inlineParentConfig' => [
                'foreign_table' => 'aTable',
                'appearance' => [
                    'collapseAll' => true,
                ],
            ],
            'isInlineChild' => true,
            'isInlineChildExpanded' => false,
            'isInlineAjaxOpeningContext' => false,
            'inlineExpandCollapseStateArray' => [
                'aTable' => [
                    42,
                ],
            ],
        ];
        $expected = $input;
        self::assertSame($expected, (new TcaColumnsProcessShowitem())->addData($input));
    }

    /**
     * @test
     */
    public function addDataAddsColumnsForExpandedInlineChild()
    {
        $input = [
            'command' => 'edit',
            'databaseRow' => [
                'uid' => 42,
            ],
            'recordTypeValue' => 'aType',
            'processedTca' => [
                'types' => [
                    'aType' => [
                        'showitem' => 'aField',
                    ],
                ],
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                        ]
                    ],
                ],
            ],
            'inlineParentConfig' => [
                'foreign_table' => 'aTable',
            ],
            'isInlineChild' => true,
            'isInlineChildExpanded' => true,
            'isInlineAjaxOpeningContext' => false,
            'inlineExpandCollapseStateArray' => [
                'aTable' => [
                    42,
                ],
            ],
        ];
        $expected = $input;
        $expected['columnsToProcess'] = ['aField'];
        self::assertSame($expected, (new TcaColumnsProcessShowitem())->addData($input));
    }
}
