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

use TYPO3\CMS\Backend\Form\FormDataProvider\TcaTypesShowitem;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case
 */
class TcaTypesShowitemTest extends UnitTestCase
{
    /**
     * @var TcaTypesShowitem
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = new TcaTypesShowitem();
    }

    /**
     * @test
     */
    public function addDataRemovesTypeRelatedFields()
    {
        $input = [
            'databaseRow' => [],
            'recordTypeValue' => 'aType',
            'processedTca' => [
                'types' => [
                    'aType' => [
                        'showitem' => 'foo',
                        'subtype_value_field' => 'bar',
                        'subtypes_excludelist' => [],
                        'subtypes_addlist' => [],
                        'bitmask_value_field' => 'foobar',
                        'bitmask_excludelist_bits' => [],
                    ],
                ],
            ],
        ];
        $expected = $input;
        $expected['processedTca']['types']['aType'] = [
            'showitem' => 'foo',
        ];
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataInsertsMatchingSubtypeAddListAfterSubtypeValueField()
    {
        $input = [
            'recordTypeValue' => 'aType',
            'databaseRow' => [
                'theSubtypeValueField' => 'theSubtypeValue',
            ],
            'processedTca' => [
                'types' => [
                    'aType' => [
                        'showitem' => 'aField,theSubtypeValueField,anotherField',
                        'subtype_value_field' => 'theSubtypeValueField',
                        'subtypes_addlist' => [
                            'theSubtypeValue' => 'additionalField',
                        ],
                    ],
                ],
            ],
        ];
        $expected = [
            'recordTypeValue' => 'aType',
            'databaseRow' => [
                'theSubtypeValueField' => 'theSubtypeValue',
            ],
            'processedTca' => [
                'types' => [
                    'aType' => [
                        'showitem' => 'aField,theSubtypeValueField,additionalField,anotherField',
                    ],
                ],
            ],
        ];
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataInsertsMatchingSubtypeAddListAfterPaletteWithSubtypeValueField()
    {
        $input = [
            'recordTypeValue' => 'aType',
            'databaseRow' => [
                'theSubtypeValueField' => 'theSubtypeValue',
            ],
            'processedTca' => [
                'types' => [
                    'aType' => [
                        'showitem' => 'aField,--palette--;;aPalette,anotherField',
                        'subtype_value_field' => 'theSubtypeValueField',
                        'subtypes_addlist' => [
                            'theSubtypeValue' => 'additionalField',
                        ],
                    ],
                ],
                'palettes' => [
                    'aPalette' => [
                        'showitem' => 'foo,theSubtypeValueField,bar',
                    ],
                ],
            ],
        ];
        $expected = [
            'recordTypeValue' => 'aType',
            'databaseRow' => [
                'theSubtypeValueField' => 'theSubtypeValue',
            ],
            'processedTca' => [
                'types' => [
                    'aType' => [
                        'showitem' => 'aField,--palette--;;aPalette,additionalField,anotherField',
                    ],
                ],
                'palettes' => [
                    'aPalette' => [
                        'showitem' => 'foo,theSubtypeValueField,bar',
                    ],
                ],
            ],
        ];
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataRemovesMatchingSubtypeExcludeListItems()
    {
        $input = [
            'recordTypeValue' => 'aType',
            'databaseRow' => [
                'theSubtypeValueField' => 'subtypeMatch',
            ],
            'processedTca' => [
                'types' => [
                    'aType' => [
                        'showitem' => 'aField, removeMe, anotherField',
                        'subtype_value_field' => 'theSubtypeValueField',
                        'subtypes_excludelist' => [
                            'subtypeMatch' => 'removeMe',
                        ],
                    ],
                ],
            ],
        ];
        $expected = [
            'recordTypeValue' => 'aType',
            'databaseRow' => [
                'theSubtypeValueField' => 'subtypeMatch',
            ],
            'processedTca' => [
                'types' => [
                    'aType' => [
                        'showitem' => 'aField,anotherField',
                    ],
                ],
            ],
        ];
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataRemovesMatchingSubtypeExcludeListItemsFromPalettes()
    {
        $input = [
            'recordTypeValue' => 'aType',
            'databaseRow' => [
                'theSubtypeValueField' => 'subtypeMatch',
            ],
            'processedTca' => [
                'types' => [
                    'aType' => [
                        'showitem' => '',
                        'subtype_value_field' => 'theSubtypeValueField',
                        'subtypes_excludelist' => [
                            'subtypeMatch' => 'removeMe',
                        ],
                    ],
                ],
                'palettes' => [
                    'aPalette' => [
                        'showitem' => 'aField, removeMe, anotherField',
                    ],
                ],
            ],
        ];
        $expected = [
            'recordTypeValue' => 'aType',
            'databaseRow' => [
                'theSubtypeValueField' => 'subtypeMatch',
            ],
            'processedTca' => [
                'types' => [
                    'aType' => [
                        'showitem' => '',
                    ],
                ],
                'palettes' => [
                    'aPalette' => [
                        'showitem' => 'aField,anotherField',
                    ],
                ],
            ],
        ];
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataRemovesMatchingBitmaskExcludeListItems()
    {
        $input = [
            'recordTypeValue' => 'aType',
            'databaseRow' => [
                'theSubtypeValueField' => 10, // 1 0 1 0
            ],
            'processedTca' => [
                'types' => [
                    'aType' => [
                        'showitem' => 'aField, removedBy3, anotherField, removedBy2',
                        'bitmask_value_field' => 'theSubtypeValueField',
                        'bitmask_excludelist_bits' => [
                            '-2' => 'removedBy2', // Remove if bit 2 is NOT set
                            '+3' => 'removedBy3', // Remvoe if bit 3 is set
                        ],
                    ],
                ],
            ],
        ];
        $expected = [
            'recordTypeValue' => 'aType',
            'databaseRow' => [
                'theSubtypeValueField' => 10,
            ],
            'processedTca' => [
                'types' => [
                    'aType' => [
                        'showitem' => 'aField,anotherField',
                    ],
                ],
            ],
        ];
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataRemovesMatchingBitmaskExcludeListItemsFromPalettes()
    {
        $input = [
            'recordTypeValue' => 'aType',
            'databaseRow' => [
                'theSubtypeValueField' => 10, // 1 0 1 0
            ],
            'processedTca' => [
                'types' => [
                    'aType' => [
                        'showitem' => '',
                        'bitmask_value_field' => 'theSubtypeValueField',
                        'bitmask_excludelist_bits' => [
                            '-2' => 'removeMe', // Remove if bit 2 is NOT set
                            '+3' => 'removeMe2', // Remvoe if bit 3 is set
                        ],
                    ],
                ],
                'palettes' => [
                    'aPalette' => [
                        'showitem' => 'aField, removeMe, anotherField',
                    ],
                    'anotherPalette' => [
                        'showitem' => 'removeMe2',
                    ],
                ],
            ],
        ];
        $expected = [
            'recordTypeValue' => 'aType',
            'databaseRow' => [
                'theSubtypeValueField' => 10,
            ],
            'processedTca' => [
                'types' => [
                    'aType' => [
                        'showitem' => '',
                    ],
                ],
                'palettes' => [
                    'aPalette' => [
                        'showitem' => 'aField,anotherField',
                    ],
                    'anotherPalette' => [
                        'showitem' => '',
                    ],
                ],
            ],
        ];
        $this->assertSame($expected, $this->subject->addData($input));
    }
}
