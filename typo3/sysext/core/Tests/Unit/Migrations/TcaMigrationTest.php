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
    public function ctrlSelIconFieldPathIsRemoved(): void
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
    public function ctrlSetToDefaultOnCopyIsRemoved(): void
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
                                    'type' => 'language',
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
                                    'type' => 'language',
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
                                    'type' => 'language',
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
    public function ctrlIntegrityColumnsAreAvailable(array $tca, array $expectation): void
    {
        $subject = new TcaMigration();
        self::assertSame($expectation, $subject->migrate($tca));
    }

    /**
     * @test
     */
    public function removeEnableMultiSelectFilterTextfieldConfigurationIsRemoved(): void
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

    /**
     * @test
     */
    public function ctrlShadowColumnsForMovePlaceholdersIsRemoved(): void
    {
        $input = [
            'aTable' => [
                'ctrl' => [
                    'shadowColumnsForMovePlaceholders' => 'aValue',
                ]
            ],
            'bTable' => [
                'ctrl' => [
                    'label' => 'labelField',
                    'versioningWS' => true,
                    'shadowColumnsForMovePlaceholders' => 'aValue'
                ]
            ]
        ];
        $expected = [
            'aTable' => [
                'ctrl' => [
                ]
            ],
            'bTable' => [
                'ctrl' => [
                    'label' => 'labelField',
                    'versioningWS' => true
                ]
            ],
        ];
        $subject = new TcaMigration();
        self::assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function ctrlShadowColumnsForMoveAndPlaceholdersIsRemoved(): void
    {
        $input = [
            'aTable' => [
                'ctrl' => [
                    'shadowColumnsForNewPlaceholders' => 'aValue',
                    'shadowColumnsForMovePlaceholders' => 'anotherValue',
                ]
            ],
            'bTable' => [
                'ctrl' => [
                    'label' => 'labelField',
                    'versioningWS' => true,
                    'shadowColumnsForNewPlaceholders' => 'aValue'
                ]
            ]
        ];
        $expected = [
            'aTable' => [
                'ctrl' => [
                ]
            ],
            'bTable' => [
                'ctrl' => [
                    'label' => 'labelField',
                    'versioningWS' => true
                ]
            ],
        ];
        $subject = new TcaMigration();
        self::assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function languageFieldsAreMigratedToTcaTypeLanguage(): void
    {
        $input = [
            'aTable' => [
                'ctrl' => [
                    'title' => 'aTable',
                    'languageField' => 'aLanguageField',
                ],
                'columns' => [
                    'aLanguageField' => [
                        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'special' => 'languages',
                            'items' => [
                                [
                                    'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.allLanguages',
                                    -1,
                                    'flags-multiple'
                                ],
                            ],
                            'default' => 0,
                        ]
                    ]
                ]
            ],
            'bTable' => [
                'ctrl' => [
                    'title' => 'bTable',
                    'languageField' => 'bLanguageField',
                ],
                'columns' => [
                    'bLanguageField' => [
                        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'foreign_table' => 'sys_language',
                            'items' => [
                                ['LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.allLanguages', -1],
                                ['LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.default_value', 0]
                            ],
                            'default' => 0
                        ]
                    ]
                ]
            ],
            'cTable' => [
                'ctrl' => [
                    'title' => 'cTable',
                ],
                'columns' => [
                    'cLanguageField' => [
                        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'special' => 'languages',
                            'fieldWizard' => [
                                'selectIcons' => [
                                    'disabled' => false,
                                ],
                            ]
                        ]
                    ]
                ]
            ],
            'dTable' => [
                'ctrl' => [
                    'title' => 'dTable'
                ],
                'columns' => [
                    'dLanguageField' => [
                        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'foreign_table' => 'sys_language',
                            'items' => [
                                ['LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.allLanguages', -1],
                                ['LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.default_value', 0]
                            ],
                            'default' => 0
                        ]
                    ]
                ]
            ]
        ];

        $expected = [
            'aTable' => [
                'ctrl' => [
                    'title' => 'aTable',
                    'languageField' => 'aLanguageField',
                ],
                'columns' => [
                    'aLanguageField' => [
                        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
                        'config' => [
                            'type' => 'language',
                        ]
                    ]
                ]
            ],
            'bTable' => [
                'ctrl' => [
                    'title' => 'bTable',
                    'languageField' => 'bLanguageField',
                ],
                'columns' => [
                    'bLanguageField' => [
                        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
                        'config' => [
                            'type' => 'language',
                        ]
                    ]
                ]
            ],
            'cTable' => [
                'ctrl' => [
                    'title' => 'cTable',
                ],
                'columns' => [
                    'cLanguageField' => [
                        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
                        'config' => [
                            'type' => 'language',
                        ]
                    ]
                ]
            ],
            'dTable' => $input['dTable']
        ];

        $subject = new TcaMigration();
        self::assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function showRemovedLocalizationRecordsRemoved(): void
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'inlineField' => [
                        'config' => [
                            'type' => 'inline',
                            'appearance' => [
                                'showRemovedLocalizationRecords' => true
                            ]
                        ]
                    ]
                ]
            ],
            'bTable' => [
                'columns' => [
                    'inlineField' => [
                        'config' => [
                            'type' => 'inline',
                            'appearance' => [
                                'showRemovedLocalizationRecords' => false
                            ]
                        ]
                    ]
                ]
            ],
            'cTable' => [
                'columns' => [
                    'someField' => [
                        'config' => [
                            'type' => 'select',
                            'appearance' => [
                                'showRemovedLocalizationRecords' => true
                            ]
                        ]
                    ]
                ]
            ],
        ];

        $expected = [
            'aTable' => [
                'columns' => [
                    'inlineField' => [
                        'config' => [
                            'type' => 'inline',
                            'appearance' => []
                        ]
                    ]
                ]
            ],
            'bTable' => [
                'columns' => [
                    'inlineField' => [
                        'config' => [
                            'type' => 'inline',
                            'appearance' => []
                        ]
                    ]
                ]
            ],
            'cTable' => [
                'columns' => [
                    'someField' => [
                        'config' => [
                            'type' => 'select',
                            'appearance' => [
                                'showRemovedLocalizationRecords' => true
                            ]
                        ]
                    ]
                ]
            ],
        ];

        $subject = new TcaMigration();
        self::assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function fileFolderConfigurationIsMigrated(): void
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [['', 0]],
                            'fileFolder' => 'EXT:styleguide/Resources/Public/Icons',
                            'fileFolder_extList' => 'svg',
                            'fileFolder_recursions' => 1,
                        ]
                    ]
                ]
            ],
            'bTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [['', 0]],
                            'fileFolder' => 'EXT:styleguide/Resources/Public/Icons',
                        ]
                    ]
                ]
            ],
            'cTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [['', 0]],
                            'fileFolder' => '',
                            'fileFolder_extList' => 'svg',
                            'fileFolder_recursions' => 1,
                        ]
                    ]
                ]
            ],
            'dTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                            'fileFolder' => 'EXT:styleguide/Resources/Public/Icons',
                            'fileFolder_extList' => 'svg',
                            'fileFolder_recursions' => 1,
                        ]
                    ]
                ]
            ],
            'eTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [['', 0]],
                            'fileFolder_extList' => 'svg',
                            'fileFolder_recursions' => 1,
                        ]
                    ]
                ]
            ],
        ];

        $expected = [
            'aTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [['', 0]],
                            'fileFolderConfig' => [
                                'folder' => 'EXT:styleguide/Resources/Public/Icons',
                                'allowedExtensions' => 'svg',
                                'depth' => 1
                            ]
                        ]
                    ]
                ]
            ],
            'bTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [['', 0]],
                            'fileFolderConfig' => [
                                'folder' => 'EXT:styleguide/Resources/Public/Icons',
                            ]
                        ]
                    ]
                ]
            ],
            'cTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [['', 0]],
                            'fileFolderConfig' => [
                                'folder' => '',
                                'allowedExtensions' => 'svg',
                                'depth' => 1
                            ]
                        ]
                    ]
                ]
            ],
            'dTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                            'fileFolder' => 'EXT:styleguide/Resources/Public/Icons',
                            'fileFolder_extList' => 'svg',
                            'fileFolder_recursions' => 1,
                        ]
                    ]
                ]
            ],
            'eTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [['', 0]],
                            'fileFolder_extList' => 'svg',
                            'fileFolder_recursions' => 1,
                        ]
                    ]
                ]
            ],
        ];

        $subject = new TcaMigration();
        self::assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function levelLinksPositionIsMigrated(): void
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'appearance' => [
                                'levelLinksPosition' => 'none'
                            ]
                        ]
                    ]
                ]
            ],
            'bTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'appearance' => [
                                'levelLinksPosition' => 'invalid'
                            ]
                        ]
                    ]
                ]
            ],
            'cTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'appearance' => [
                                'levelLinksPosition' => 'both'
                            ]
                        ]
                    ]
                ]
            ],
            'dTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'appearance' => [
                                'levelLinksPosition' => 'none'
                            ]
                        ]
                    ]
                ]
            ],
        ];

        $expected = [
            'aTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'appearance' => [
                                'showAllLocalizationLink' => false,
                                'showSynchronizationLink' => false,
                                'showNewRecordLink' => false
                            ]
                        ]
                    ]
                ]
            ],
            'bTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'appearance' => [
                                'levelLinksPosition' => 'invalid'
                            ]
                        ]
                    ]
                ]
            ],
            'cTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'appearance' => [
                                'levelLinksPosition' => 'both'
                            ]
                        ]
                    ]
                ]
            ],
            'dTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'appearance' => [
                                'levelLinksPosition' => 'none'
                            ]
                        ]
                    ]
                ]
            ],
        ];

        $subject = new TcaMigration();
        self::assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function rootUidIsMigratedToStartingPositions(): void
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectTree',
                            'treeConfig' => [
                                'rootUid' => 42
                            ]
                        ]
                    ]
                ]
            ],
            'bTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'category',
                            'treeConfig' => [
                                'rootUid' => 43
                            ]
                        ]
                    ]
                ]
            ],
            'cTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectTree',
                        ]
                    ]
                ]
            ],
            'dTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            // This config makes no sense, however we will not touch it
                            'type' => 'input',
                            'treeConfig' => [
                                'rootUid' => 43
                            ]
                        ]
                    ]
                ]
            ],
        ];

        $expected = [
            'aTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectTree',
                            'treeConfig' => [
                                'startingPoints' => '42'
                            ]
                        ]
                    ]
                ]
            ],
            'bTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'category',
                            'treeConfig' => [
                                'startingPoints' => '43'
                            ]
                        ]
                    ]
                ]
            ],
            'cTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectTree',
                        ]
                    ]
                ]
            ],
            'dTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                            'treeConfig' => [
                                'rootUid' => 43
                            ]
                        ]
                    ]
                ]
            ],
        ];

        $subject = new TcaMigration();
        self::assertEquals($expected, $subject->migrate($input));
    }
}
