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

namespace TYPO3\CMS\Core\Tests\Unit\Migrations;

use TYPO3\CMS\Core\Migrations\TcaMigration;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class TcaMigrationTest extends UnitTestCase
{
    /**
     * @test
     */
    public function missingTypeThrowsException(): void
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'field_a' => [
                        'label' => 'aLabel',
                        'config' => [
                            'type' => 'text',
                        ],
                    ],
                    'field_b' => [
                        'label' => 'bLabel',
                        'config' => [
                            'rows' => 42,
                            'wizards' => []
                        ],
                    ],
                ],
            ],
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1482394401);
        $subject = new TcaMigration();
        $subject->migrate($input);
    }

    /**
     * @test
     */
    public function migrateReturnsGivenArrayUnchangedIfNoMigrationNeeded(): void
    {
        $input = $expected = [
            'aTable' => [
                'ctrl' => [
                    'aKey' => 'aValue',
                ],
                'columns' => [
                    'aField' => [
                        'label' => 'foo',
                        'config' => [
                            'type' => 'aType',
                            'lolli' => 'did this',
                        ]
                    ],
                ],
                'types' => [
                    0 => [
                        'showitem' => 'this,should;stay,this,too',
                    ],
                ],
            ],
        ];
        $subject = new TcaMigration();
        self::assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function migrateAddsMissingColumnsConfig(): void
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'aField' => [
                        'exclude' => true,
                    ],
                    'bField' => [
                    ],
                    'cField' => [
                        'config' => 'i am a string but should be an array',
                    ],
                    'dField' => [
                        // This kept as is, 'config' is not added. This is relevant
                        // for "flex" data structure arrays with section containers
                        // that have 'type'=>'array' on this level and an 'el' sub array
                        // with details.
                        'type' => 'array',
                    ],
                ]
            ],
        ];
        $expected = [
            'aTable' => [
                'columns' => [
                    'aField' => [
                        'exclude' => true,
                        'config' => [
                            'type' => 'none',
                        ],
                    ],
                    'bField' => [
                        'config' => [
                            'type' => 'none',
                        ],
                    ],
                    'cField' => [
                        'config' => [
                            'type' => 'none',
                        ],
                    ],
                    'dField' => [
                        'type' => 'array',
                    ],
                ],
            ],
        ];
        $subject = new TcaMigration();
        self::assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function ctrlSelIconFieldPathIsRemoved()
    {
        $input = [
            'aTable' => [
                'ctrl' => [
                    'selicon_field' => 'aField',
                    'selicon_field_path' => 'my/folder'
                ],
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'none',
                        ],
                    ],
                ]
            ],
        ];
        $expected = [
            'aTable' => [
                'ctrl' => [
                    'selicon_field' => 'aField'
                ],
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'none',
                        ],
                    ],
                ],
            ],
        ];
        $subject = new TcaMigration();
        self::assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function ctrlSetToDefaultOnCopyIsRemoved()
    {
        $input = [
            'aTable' => [
                'ctrl' => [
                    'title' => 'aField',
                    'setToDefaultOnCopy' => 'aField,anotherField',
                ],
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'none',
                        ],
                    ],
                ]
            ]
        ];
        $expected = [
            'aTable' => [
                'ctrl' => [
                    'title' => 'aField',
                ],
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'none',
                        ],
                    ],
                ],
            ],
        ];
        $subject = new TcaMigration();
        self::assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @return array
     */
    public function ctrlIntegrityColumnsAreAvailableDataProvider(): array
    {
        return [
            'filled columns' => [
                // tca
                [
                    'aTable' => [
                        'ctrl' => [
                            'origUid' => 'aField',
                            'languageField' => 'bField',
                            'transOrigPointerField' => 'cField',
                            'translationSource' => 'dField',
                        ],
                        'columns' => [
                            'aField' => [
                                'label' => 'aField',
                                'config' => [
                                    'type' => 'none',
                                ],
                            ],
                            'bField' => [
                                'label' => 'bField',
                                'config' => [
                                    'type' => 'none',
                                ],
                            ],
                            'cField' => [
                                'label' => 'cField',
                                'config' => [
                                    'type' => 'none',
                                ],
                            ],
                            'dField' => [
                                'label' => 'dField',
                                'config' => [
                                    'type' => 'none',
                                ],
                            ],
                        ],
                    ],
                ],
                // expectation
                [
                    'aTable' => [
                        'ctrl' => [
                            'origUid' => 'aField',
                            'languageField' => 'bField',
                            'transOrigPointerField' => 'cField',
                            'translationSource' => 'dField',
                        ],
                        'columns' => [
                            'aField' => [
                                'label' => 'aField',
                                'config' => [
                                    'type' => 'none',
                                ],
                            ],
                            'bField' => [
                                'label' => 'bField',
                                'config' => [
                                    'type' => 'none',
                                ],
                            ],
                            'cField' => [
                                'label' => 'cField',
                                'config' => [
                                    'type' => 'none',
                                ],
                            ],
                            'dField' => [
                                'label' => 'dField',
                                'config' => [
                                    'type' => 'none',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'mixed columns' => [
                // tca
                [
                    'aTable' => [
                        'ctrl' => [
                            'origUid' => 'aField',
                            'languageField' => 'bField',
                            'transOrigPointerField' => 'cField',
                            'translationSource' => 'dField',
                        ],
                        'columns' => [
                            'aField' => [
                                'label' => 'aField',
                                'config' => [
                                    'type' => 'none',
                                ],
                            ],
                            'bField' => [
                                'label' => 'bField',
                                'config' => [
                                    'type' => 'none',
                                ],
                            ],
                        ],
                    ],
                ],
                // expectation
                [
                    'aTable' => [
                        'ctrl' => [
                            'origUid' => 'aField',
                            'languageField' => 'bField',
                            'transOrigPointerField' => 'cField',
                            'translationSource' => 'dField',
                        ],
                        'columns' => [
                            'aField' => [
                                'label' => 'aField',
                                'config' => [
                                    'type' => 'none',
                                ],
                            ],
                            'bField' => [
                                'label' => 'bField',
                                'config' => [
                                    'type' => 'none',
                                ],
                            ],
                            'cField' => [
                                'config' => [
                                    'type' => 'passthrough',
                                    'default' => 0,
                                ],
                            ],
                            'dField' => [
                                'config' => [
                                    'type' => 'passthrough',
                                    'default' => 0,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'empty columns' => [
                // tca
                [
                    'aTable' => [
                        'ctrl' => [
                            'origUid' => 'aField',
                            'languageField' => 'bField',
                            'transOrigPointerField' => 'cField',
                            'translationSource' => 'dField',
                        ],
                        'columns' => [],
                    ],
                ],
                // expectation
                [
                    'aTable' => [
                        'ctrl' => [
                            'origUid' => 'aField',
                            'languageField' => 'bField',
                            'transOrigPointerField' => 'cField',
                            'translationSource' => 'dField',
                        ],
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'passthrough',
                                    'default' => 0,
                                ],
                            ],
                            'bField' => [
                                'config' => [
                                    'type' => 'passthrough',
                                    'default' => 0,
                                ],
                            ],
                            'cField' => [
                                'config' => [
                                    'type' => 'passthrough',
                                    'default' => 0,
                                ],
                            ],
                            'dField' => [
                                'config' => [
                                    'type' => 'passthrough',
                                    'default' => 0,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array $tca
     * @param array $expectation
     *
     * @test
     * @dataProvider ctrlIntegrityColumnsAreAvailableDataProvider
     */
    public function ctrlIntegrityColumnsAreAvailable(array $tca, array $expectation)
    {
        $subject = new TcaMigration();
        self::assertSame($expectation, $subject->migrate($tca));
    }

    /**
     * @test
     */
    public function removeEnableMultiSelectFilterTextfieldConfigurationIsRemoved()
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                            'enableMultiSelectFilterTextfield' => false,
                        ],
                    ],
                    'bField' => [
                        'config' => [
                            'type' => 'select',
                        ],
                    ],
                    'cField' => [
                        'config' => [
                            'type' => 'select',
                            'enableMultiSelectFilterTextfield' => true,
                        ],
                    ],
                ]
            ],
        ];
        $expected = [
            'aTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                    'bField' => [
                        'config' => [
                            'type' => 'select',
                        ],
                    ],
                    'cField' => [
                        'config' => [
                            'type' => 'select',
                        ],
                    ],
                ],
            ],
        ];
        $subject = new TcaMigration();
        self::assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function removeExcludeFieldForTransOrigPointerFieldIsRemoved(): void
    {
        $input = [
            'aTable' => [
                'ctrl' => [
                    'transOrigPointerField' => 'l10n_parent'
                ],
                'columns' => [
                    'l10n_parent' => [
                        'exclude' => true,
                        'config' => [
                            'type' => 'select'
                        ]
                    ]
                ]
            ],
            'bTable' => [
                'ctrl' => [
                    'transOrigPointerField' => 'l10n_parent'
                ],
                'columns' => [
                    'l10n_parent' => [
                        'config' => [
                            'type' => 'select'
                        ]
                    ]
                ]
            ],
            'cTable' => [
                'columns' => [
                    'l10n_parent' => [
                        'exclude' => true,
                        'config' => [
                            'type' => 'select'
                        ]
                    ]
                ]
            ]
        ];
        $expected = [
            'aTable' => [
                'ctrl' => [
                    'transOrigPointerField' => 'l10n_parent'
                ],
                'columns' => [
                    'l10n_parent' => [
                        'config' => [
                            'type' => 'select'
                        ]
                    ]
                ]
            ],
            'bTable' => [
                'ctrl' => [
                    'transOrigPointerField' => 'l10n_parent'
                ],
                'columns' => [
                    'l10n_parent' => [
                        'config' => [
                            'type' => 'select'
                        ]
                    ]
                ]
            ],
            'cTable' => [
                'columns' => [
                    'l10n_parent' => [
                        'exclude' => true,
                        'config' => [
                            'type' => 'select'
                        ]
                    ]
                ]
            ]
        ];
        $subject = new TcaMigration();
        self::assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function removeShowRecordFieldListFieldIsRemoved(): void
    {
        $input = [
            'aTable' => [
                'interface' => [
                    'showRecordFieldList' => 'title,text,description',
                ]
            ],
            'bTable' => [
                'interface' => [
                    'showRecordFieldList' => 'title,text,description',
                    'maxDBListItems' => 30,
                    'maxSingleDBListItems' => 50
                ]
            ]
        ];
        $expected = [
            'aTable' => [
            ],
            'bTable' => [
                'interface' => [
                    'maxDBListItems' => 30,
                    'maxSingleDBListItems' => 50
                ]
            ]
        ];
        $subject = new TcaMigration();
        self::assertEquals($expected, $subject->migrate($input));
    }
}
