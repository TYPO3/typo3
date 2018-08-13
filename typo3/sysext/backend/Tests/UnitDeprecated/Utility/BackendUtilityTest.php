<?php
namespace TYPO3\CMS\Backend\Tests\UnitDeprecated\Utility;

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

use TYPO3\CMS\Backend\Tests\UnitDeprecated\Utility\Fixtures\BackendUtilityFixture;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class BackendUtilityTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getModTSconfigIgnoresValuesFromUserTsConfigIfNotSet()
    {
        $completeConfiguration = [
            'value' => 'bar',
            'properties' => [
                'permissions.' => [
                    'file.' => [
                        'default.' => ['readAction' => '1'],
                        '1.' => ['writeAction' => '1'],
                        '0.' => ['readAction' => '0'],
                    ],
                ]
            ]
        ];

        $GLOBALS['BE_USER'] = $this->createMock(BackendUserAuthentication::class);
        $GLOBALS['BE_USER']->expects($this->at(0))->method('getTSConfig')->will($this->returnValue($completeConfiguration));
        $GLOBALS['BE_USER']->expects($this->at(1))->method('getTSConfig')->will($this->returnValue(['value' => null, 'properties' => null]));

        $this->assertSame($completeConfiguration, BackendUtilityFixture::getModTSconfig(42, 'notrelevant'));
    }

    ///////////////////////////////////////
    // Tests concerning getTCAtypes
    ///////////////////////////////////////

    /**
     * @test
     */
    public function getTCAtypesReturnsCorrectValuesDataProvider()
    {
        return [
            'no input' => [
                '', // table
                [], // rec
                '', // useFieldNameAsKey
                null // expected
            ],
            'non-existant table' => [
                'fooBar', // table
                [], // rec
                '', // useFieldNameAsKey
                null // expected
            ],
            'Doktype=1: one simple field' => [
                'pages',
                [
                    'uid' => '1',
                    'doktype' => '1'
                ],
                false,
                [
                    0 => [
                        'field' => 'title',
                        'title' => null,
                        'palette' => null,
                        'spec' => [],
                        'origString' => 'title'
                    ]
                ]
            ],
            'non-existant type given: Return for type 1' => [
                'pages', // table
                [
                    'uid' => '1',
                    'doktype' => '999'
                ], // rec
                '', // useFieldNameAsKey
                [
                    0 => [
                        'field' => 'title',
                        'title' => null,
                        'palette' => null,
                        'spec' => [],
                        'origString' => 'title'
                    ]
                ] // expected
            ],
            'Doktype=1: one simple field, useFieldNameAsKey=true' => [
                'pages',
                [
                    'uid' => '1',
                    'doktype' => '1'
                ],
                true,
                [
                    'title' => [
                        'field' => 'title',
                        'title' => null,
                        'palette' => null,
                        'spec' => [],
                        'origString' => 'title'
                    ]
                ]
            ],
            'Empty showitem Field' => [
                'test',
                [
                    'uid' => '1',
                    'fooBar' => '99'
                ],
                true,
                [
                    '' => [
                        'field' => '',
                        'title' => null,
                        'palette' => null,
                        'spec' => [],
                        'origString' => ''
                    ]
                ]
            ],
            'RTE field within a palette' => [
                'pages',
                [
                    'uid' => '1',
                    'doktype' => '10',
                ],
                false,
                [
                    0 => [
                        'field' => '--div--',
                        'title' => 'General',
                        'palette' => null,
                        'spec' => [],
                        'origString' => '--div--;General'
                    ],
                    1 => [
                        'field' => '--palette--',
                        'title' => 'Palette',
                        'palette' => '123',
                        'spec' => [],
                        'origString' => '--palette--;Palette;123'
                    ],
                    2 => [
                        'field' => 'title',
                        'title' => null,
                        'palette' => null,
                        'spec' => [],
                        'origString' => 'title'
                    ],
                    3 => [
                        'field' => 'text',
                        'title' => null,
                        'palette' => null,
                        'spec' => [],
                        'origString' => 'text'
                    ],
                    4 => [
                        'field' => 'select',
                        'title' => 'Select field',
                        'palette' => null,
                        'spec' => [],
                        'origString' => 'select;Select field'
                    ]
                ]
            ],
            'RTE field with more settings within a palette' => [
                'pages',
                [
                    'uid' => 1,
                    'doktype' => 2
                ],
                false,
                [
                    0 => [
                        'field' => '--div--',
                        'title' => 'General',
                        'palette' => null,
                        'spec' => [],
                        'origString' => '--div--;General'
                    ],
                    1 => [
                        'field' => '--palette--',
                        'title' => 'RTE palette',
                        'palette' => '456',
                        'spec' => [],
                        'origString' => '--palette--;RTE palette;456'
                    ],
                    2 => [
                        'field' => 'text2',
                        'title' => null,
                        'palette' => null,
                        'spec' => [],
                        'origString' => 'text2'
                    ]
                ]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider getTCAtypesReturnsCorrectValuesDataProvider
     *
     * @param string $table
     * @param array $rec
     * @param bool $useFieldNameAsKey
     * @param array $expected
     */
    public function getTCAtypesReturnsCorrectValues($table, $rec, $useFieldNameAsKey, $expected)
    {
        $GLOBALS['TCA'] = [
            'pages' => [
                'ctrl' => [
                    'type' => 'doktype'
                ],
                'columns' => [
                    'title' => [
                        'label' => 'Title test',
                        'config' => [
                            'type' => 'input'
                        ]
                    ],
                    'text' => [
                        'label' => 'RTE Text',
                        'config' => [
                            'type' => 'text',
                            'cols' => 40,
                            'rows' => 5
                        ],
                    ],
                    'text2' => [
                        'label' => 'RTE Text 2',
                        'config' => [
                            'type' => 'text',
                            'cols' => 40,
                            'rows' => 5
                        ],
                    ],
                    'select' => [
                        'label' => 'Select test',
                        'config' => [
                            'items' => [
                                ['Please select', 0],
                                ['Option 1', 1],
                                ['Option 2', 2]
                            ]
                        ],
                        'maxitems' => 1,
                        'renderType' => 'selectSingle'
                    ]
                ],
                'types' => [
                    '1' => [
                        'showitem' => 'title'
                    ],
                    '2' => [
                        'showitem' => '--div--;General,--palette--;RTE palette;456'
                    ],
                    '10' => [
                        'showitem' => '--div--;General,--palette--;Palette;123,title'
                    ],
                    '14' => [
                        'showitem' => '--div--;General,title'
                    ]
                ],
                'palettes' => [
                    '123' => [
                        'showitem' => 'text,select;Select field'
                    ],
                    '456' => [
                        'showitem' => 'text2'
                    ]
                ]
            ],
            'test' => [
                'ctrl' => [
                    'type' => 'fooBar'
                ],
                'types' => [
                    '99' => [ 'showitem' => '']
                ]
            ]
        ];

        $return = BackendUtility::getTCAtypes($table, $rec, $useFieldNameAsKey);
        $this->assertSame($expected, $return);
    }
}
