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
use TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

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
                            'wizards' => [],
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
                        ],
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
                ],
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
                    'selicon_field_path' => 'my/folder',
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
        $expected = [
            'aTable' => [
                'ctrl' => [
                    'selicon_field' => 'aField',
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
                ],
            ],
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
                ],
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
                    'transOrigPointerField' => 'l10n_parent',
                ],
                'columns' => [
                    'l10n_parent' => [
                        'exclude' => true,
                        'config' => [
                            'type' => 'select',
                        ],
                    ],
                ],
            ],
            'bTable' => [
                'ctrl' => [
                    'transOrigPointerField' => 'l10n_parent',
                ],
                'columns' => [
                    'l10n_parent' => [
                        'config' => [
                            'type' => 'select',
                        ],
                    ],
                ],
            ],
            'cTable' => [
                'columns' => [
                    'l10n_parent' => [
                        'exclude' => true,
                        'config' => [
                            'type' => 'select',
                        ],
                    ],
                ],
            ],
        ];
        $expected = [
            'aTable' => [
                'ctrl' => [
                    'transOrigPointerField' => 'l10n_parent',
                ],
                'columns' => [
                    'l10n_parent' => [
                        'config' => [
                            'type' => 'select',
                        ],
                    ],
                ],
            ],
            'bTable' => [
                'ctrl' => [
                    'transOrigPointerField' => 'l10n_parent',
                ],
                'columns' => [
                    'l10n_parent' => [
                        'config' => [
                            'type' => 'select',
                        ],
                    ],
                ],
            ],
            'cTable' => [
                'columns' => [
                    'l10n_parent' => [
                        'exclude' => true,
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
    public function removeShowRecordFieldListFieldIsRemoved(): void
    {
        $input = [
            'aTable' => [
                'interface' => [
                    'showRecordFieldList' => 'title,text,description',
                ],
            ],
            'bTable' => [
                'interface' => [
                    'showRecordFieldList' => 'title,text,description',
                    'maxDBListItems' => 30,
                    'maxSingleDBListItems' => 50,
                ],
            ],
        ];
        $expected = [
            'aTable' => [
            ],
            'bTable' => [
                'interface' => [
                    'maxDBListItems' => 30,
                    'maxSingleDBListItems' => 50,
                ],
            ],
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
                ],
            ],
            'bTable' => [
                'ctrl' => [
                    'label' => 'labelField',
                    'versioningWS' => true,
                    'shadowColumnsForMovePlaceholders' => 'aValue',
                ],
            ],
        ];
        $expected = [
            'aTable' => [
                'ctrl' => [
                ],
            ],
            'bTable' => [
                'ctrl' => [
                    'label' => 'labelField',
                    'versioningWS' => true,
                ],
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
                ],
            ],
            'bTable' => [
                'ctrl' => [
                    'label' => 'labelField',
                    'versioningWS' => true,
                    'shadowColumnsForNewPlaceholders' => 'aValue',
                ],
            ],
        ];
        $expected = [
            'aTable' => [
                'ctrl' => [
                ],
            ],
            'bTable' => [
                'ctrl' => [
                    'label' => 'labelField',
                    'versioningWS' => true,
                ],
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
                                    'flags-multiple',
                                ],
                            ],
                            'default' => 0,
                        ],
                    ],
                ],
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
                                ['LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.default_value', 0],
                            ],
                            'default' => 0,
                        ],
                    ],
                ],
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
                            ],
                        ],
                    ],
                ],
            ],
            'dTable' => [
                'ctrl' => [
                    'title' => 'dTable',
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
                                ['LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.default_value', 0],
                            ],
                            'default' => 0,
                        ],
                    ],
                ],
            ],
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
                        ],
                    ],
                ],
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
                        ],
                    ],
                ],
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
                        ],
                    ],
                ],
            ],
            'dTable' => [
                'ctrl' => [
                    'title' => 'dTable',
                ],
                'columns' => [
                    'dLanguageField' => [
                        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'foreign_table' => 'sys_language',
                            'items' => [
                                ['label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.allLanguages', 'value' => -1],
                                ['label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.default_value', 'value' => 0],
                            ],
                            'default' => 0,
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
    public function showRemovedLocalizationRecordsRemoved(): void
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'inlineField' => [
                        'config' => [
                            'type' => 'inline',
                            'appearance' => [
                                'showRemovedLocalizationRecords' => true,
                            ],
                        ],
                    ],
                ],
            ],
            'bTable' => [
                'columns' => [
                    'inlineField' => [
                        'config' => [
                            'type' => 'inline',
                            'appearance' => [
                                'showRemovedLocalizationRecords' => false,
                            ],
                        ],
                    ],
                ],
            ],
            'cTable' => [
                'columns' => [
                    'someField' => [
                        'config' => [
                            'type' => 'select',
                            'appearance' => [
                                'showRemovedLocalizationRecords' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = [
            'aTable' => [
                'columns' => [
                    'inlineField' => [
                        'config' => [
                            'type' => 'inline',
                            'appearance' => [],
                        ],
                    ],
                ],
            ],
            'bTable' => [
                'columns' => [
                    'inlineField' => [
                        'config' => [
                            'type' => 'inline',
                            'appearance' => [],
                        ],
                    ],
                ],
            ],
            'cTable' => [
                'columns' => [
                    'someField' => [
                        'config' => [
                            'type' => 'select',
                            'appearance' => [
                                'showRemovedLocalizationRecords' => true,
                            ],
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
                        ],
                    ],
                ],
            ],
            'bTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [['', 0]],
                            'fileFolder' => 'EXT:styleguide/Resources/Public/Icons',
                        ],
                    ],
                ],
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
                        ],
                    ],
                ],
            ],
            'dTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                            'fileFolder' => 'EXT:styleguide/Resources/Public/Icons',
                            'fileFolder_extList' => 'svg',
                            'fileFolder_recursions' => 1,
                        ],
                    ],
                ],
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
                        ],
                    ],
                ],
            ],
        ];

        $expected = [
            'aTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [['label' => '', 'value' => 0]],
                            'fileFolderConfig' => [
                                'folder' => 'EXT:styleguide/Resources/Public/Icons',
                                'allowedExtensions' => 'svg',
                                'depth' => 1,
                            ],
                        ],
                    ],
                ],
            ],
            'bTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [['label' => '', 'value' => 0]],
                            'fileFolderConfig' => [
                                'folder' => 'EXT:styleguide/Resources/Public/Icons',
                            ],
                        ],
                    ],
                ],
            ],
            'cTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [['label' => '', 'value' => 0]],
                            'fileFolderConfig' => [
                                'folder' => '',
                                'allowedExtensions' => 'svg',
                                'depth' => 1,
                            ],
                        ],
                    ],
                ],
            ],
            'dTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                            'fileFolder' => 'EXT:styleguide/Resources/Public/Icons',
                            'fileFolder_extList' => 'svg',
                            'fileFolder_recursions' => 1,
                        ],
                    ],
                ],
            ],
            'eTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [['label' => '', 'value' => 0]],
                            'fileFolder_extList' => 'svg',
                            'fileFolder_recursions' => 1,
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
    public function levelLinksPositionIsMigrated(): void
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'appearance' => [
                                'levelLinksPosition' => 'none',
                            ],
                        ],
                    ],
                ],
            ],
            'bTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'appearance' => [
                                'levelLinksPosition' => 'invalid',
                            ],
                        ],
                    ],
                ],
            ],
            'cTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'appearance' => [
                                'levelLinksPosition' => 'both',
                            ],
                        ],
                    ],
                ],
            ],
            'dTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'appearance' => [
                                'levelLinksPosition' => 'none',
                            ],
                        ],
                    ],
                ],
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
                                'showNewRecordLink' => false,
                            ],
                        ],
                    ],
                ],
            ],
            'bTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'appearance' => [
                                'levelLinksPosition' => 'invalid',
                            ],
                        ],
                    ],
                ],
            ],
            'cTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'appearance' => [
                                'levelLinksPosition' => 'both',
                            ],
                        ],
                    ],
                ],
            ],
            'dTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'appearance' => [
                                'levelLinksPosition' => 'none',
                            ],
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
                                'rootUid' => 42,
                            ],
                        ],
                    ],
                ],
            ],
            'bTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'category',
                            'treeConfig' => [
                                'rootUid' => 43,
                            ],
                        ],
                    ],
                ],
            ],
            'cTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectTree',
                        ],
                    ],
                ],
            ],
            'dTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            // This config makes no sense, however we will not touch it
                            'type' => 'input',
                            'treeConfig' => [
                                'rootUid' => 43,
                            ],
                        ],
                    ],
                ],
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
                                'startingPoints' => '42',
                            ],
                        ],
                    ],
                ],
            ],
            'bTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'category',
                            'treeConfig' => [
                                'startingPoints' => '43',
                            ],
                        ],
                    ],
                ],
            ],
            'cTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectTree',
                        ],
                    ],
                ],
            ],
            'dTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                            'treeConfig' => [
                                'rootUid' => 43,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $subject = new TcaMigration();
        self::assertEquals($expected, $subject->migrate($input));
    }

    private function internalTypeFolderMigratedToTypeDataProvider(): iterable
    {
        yield 'internal_type=folder migrated to type=folder' => [
            'input' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'group',
                                'internal_type' => 'folder',
                                'maxitems' => 2,
                            ],
                        ],
                        'bColumn' => [
                            'config' => [
                                'type' => 'group',
                                'internal_type' => 'db',
                                'minitems' => 2,
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'folder',
                                'maxitems' => 2,
                            ],
                        ],
                        'bColumn' => [
                            'config' => [
                                'type' => 'group',
                                'minitems' => 2,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider internalTypeFolderMigratedToTypeDataProvider
     * @test
     */
    public function internalTypeFolderMigratedToType(array $input, array $expected): void
    {
        $subject = new TcaMigration();
        self::assertSame($expected, $subject->migrate($input));
    }

    public function requiredFlagIsMigratedDataProvider(): iterable
    {
        yield 'field contains eval=required' => [
            'tca' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                                'eval' => 'required',
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                                'required' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        yield 'field contains eval=trim,required' => [
            'tca' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                                'eval' => 'trim,required',
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                                'required' => true,
                                'eval' => 'trim',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        yield 'field does not contain eval with required' => [
            'tca' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                                'eval' => 'trim',
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                                'eval' => 'trim',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        yield 'field does not contain eval' => [
            'tca' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider requiredFlagIsMigratedDataProvider
     * @test
     */
    public function requiredFlagIsMigrated(array $input, array $expected): void
    {
        $subject = new TcaMigration();
        self::assertEquals($expected, $subject->migrate($input));
    }

    private function evalNullMigratedToNullableOptionDataProvider(): iterable
    {
        yield 'field contains eval=null' => [
            'tca' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                                'eval' => 'null',
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                                'nullable' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        yield 'field contains eval=trim,null' => [
            'tca' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                                'eval' => 'trim,null',
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                                'eval' => 'trim',
                                'nullable' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        yield 'field does not contain eval with null' => [
            'tca' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                                'eval' => 'trim',
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                                'eval' => 'trim',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        yield 'field does not contain eval' => [
            'tca' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider evalNullMigratedToNullableOptionDataProvider
     * @test
     * @param array<string, mixed> $input
     * @param array<string, mixed> $expected
     */
    public function evalNullMigratedToNullableOption(array $input, array $expected): void
    {
        $subject = new TcaMigration();
        self::assertSame($expected, $subject->migrate($input));
    }

    private function evalEmailMigratedToTypeDataProvider(): iterable
    {
        yield 'eval=email migrated to type=email' => [
            'input' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                                'eval' => 'email,trim,unique,uniqueInPid',
                                'required' => true,
                                'nullable' => true,
                            ],
                        ],
                        'bColumn' => [
                            'config' => [
                                'type' => 'input',
                                'eval' => 'email,trim',
                                'required' => true,
                            ],
                        ],
                        'differentColumn' => [
                            'config' => [
                                'type' => 'input',
                                'eval' => 'trim,unique',
                            ],
                        ],
                        'wrongTypeColumn' => [
                            'config' => [
                                'type' => 'text',
                                'eval' => 'email,trim,unique',
                            ],
                        ],
                        'alreadyMigratedColumn' => [
                            'config' => [
                                'type' => 'email',
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'email',
                                'eval' => 'unique,uniqueInPid',
                                'required' => true,
                                'nullable' => true,
                            ],
                        ],
                        'bColumn' => [
                            'config' => [
                                'type' => 'email',
                                'required' => true,
                            ],
                        ],
                        'differentColumn' => [
                            'config' => [
                                'type' => 'input',
                                'eval' => 'trim,unique',
                            ],
                        ],
                        'wrongTypeColumn' => [
                            'config' => [
                                'type' => 'text',
                                'eval' => 'email,trim,unique',
                            ],
                        ],
                        'alreadyMigratedColumn' => [
                            'config' => [
                                'type' => 'email',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider evalEmailMigratedToTypeDataProvider
     * @test
     */
    public function evalEmailMigratedToType(array $input, array $expected): void
    {
        $subject = new TcaMigration();
        self::assertSame($expected, $subject->migrate($input));
    }

    private function typeNoneColsMigratedToSizeDataProvider(): iterable
    {
        yield 'type none cols migrated to size' => [
            'tca' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'none',
                                'format' => 'int',
                                'cols' => 20,
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'none',
                                'format' => 'int',
                                'size' => 20,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        yield 'cols has priority over size and overrides it, if both are set' => [
            'tca' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'none',
                                'cols' => 20,
                                'size' => 30,
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'none',
                                'size' => 20,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider typeNoneColsMigratedToSizeDataProvider
     * @test
     */
    public function typeNoneColsMigratedToSize(array $tca, array $expected): void
    {
        $subject = new TcaMigration();
        self::assertSame($expected, $subject->migrate($tca));
    }

    private function renderTypeInputLinkMigratedToTypeLinkDataProvider(): iterable
    {
        yield 'Full example of renderType=inputLink migrated to type=link' => [
            'input' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                                'renderType' => 'inputLink',
                                'required' => true,
                                'nullable' => true,
                                'size' => 21,
                                'max' => 1234,
                                'eval' => 'trim',
                                'fieldControl' => [
                                    'linkPopup' => [
                                        'disabled' => true,
                                        'options' => [
                                            'title' => 'Browser label',
                                            'allowedExtensions' => 'jpg,png',
                                            'blindLinkFields' => 'class,target,title',
                                            'blindLinkOptions' => 'mail,folder,file,telephone',
                                        ],
                                    ],
                                ],
                                'softref' => 'typolink',
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'link',
                                'required' => true,
                                'nullable' => true,
                                'size' => 21,
                                'allowedTypes' => ['page', 'url', 'record'], // Ensures mail=>email str_replace works
                                'appearance' => [
                                    'enableBrowser' => false,
                                    'browserTitle' => 'Browser label',
                                    'allowedOptions' => ['params', 'rel'],
                                    'allowedFileExtensions' => ['jpg', 'png'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield 'Migrate type and remove eval' => [
            'input' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                                'renderType' => 'inputLink',
                                'eval' => 'trim',
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'link',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield 'full blind options' => [
            'input' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                                'renderType' => 'inputLink',
                                'fieldControl' => [
                                    'linkPopup' => [
                                        'options' => [
                                            'blindLinkOptions' => 'page,file,folder,url,mail,record,telephone',
                                            'blindLinkFields' => 'class,target,title,params,rel',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'link',
                                'allowedTypes' => [], // This is migrated correctly but will lead to an exception in the element
                                'appearance' => [
                                    'allowedOptions' => [],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield 'empty blind options' => [
            'input' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                                'renderType' => 'inputLink',
                                'fieldControl' => [
                                    'linkPopup' => [
                                        'disabled' => false,
                                        'options' => [
                                            'title' => '',
                                            'blindLinkOptions' => '',
                                            'blindLinkFields' => '',
                                            'allowedExtensions' => '',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'link',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield 'Non empty FieldControl is kept' => [
            'input' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                                'renderType' => 'inputLink',
                                'eval' => 'trim',
                                'fieldControl' => [
                                    'linkPopup' => [
                                        'disabled' => true,
                                    ],
                                    'editPopup' => [
                                        'disabled' => false,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'link',
                                'fieldControl' => [
                                    'editPopup' => [
                                        'disabled' => false,
                                    ],
                                ],
                                'appearance' => [
                                    'enableBrowser' => false,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield 'Ensure "email" is used as term' => [
            'input' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                                'renderType' => 'inputLink',
                                'fieldControl' => [
                                    'linkPopup' => [
                                        'options' => [
                                            'blindLinkOptions' => 'page,file,folder,url,telephone',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'link',
                                'allowedTypes' => ['email', 'record'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider renderTypeInputLinkMigratedToTypeLinkDataProvider
     * @test
     */
    public function renderTypeInputLinkMigratedToTypeLink(array $input, array $expected): void
    {
        $subject = new TcaMigration();
        self::assertSame($expected, $subject->migrate($input));
    }

    private function evalPasswordSaltedPasswordMigratedToTypePasswordDataProvider(): iterable
    {
        yield 'eval=password and eval=saltedPassword migrated to type=password' => [
            'input' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                                'eval' => 'trim,password,saltedPassword',
                                'required' => true,
                            ],
                        ],
                        'aColumnWithPassword' => [
                            'config' => [
                                'type' => 'input',
                                'eval' => 'trim,password',
                                'required' => true,
                            ],
                        ],
                        'aColumnWithSaltedpassword' => [
                            'config' => [
                                'type' => 'input',
                                'eval' => 'trim,saltedPassword',
                                'required' => true,
                            ],
                        ],
                        'fullMigration' => [
                            'config' => [
                                'type' => 'input',
                                'eval' => 'trim,password,saltedPassword,int',
                                'required' => true,
                                'nullable' => true,
                                'max' => 1234,
                                'search' => [
                                    'andWhere' => '{#CType}=\'text\' OR {#CType}=\'textpic\' OR {#CType}=\'textmedia\'',
                                ],
                            ],
                        ],
                        'differentColumn' => [
                            'config' => [
                                'type' => 'input',
                                'eval' => 'trim,unique',
                            ],
                        ],
                        'wrongTypeColumn' => [
                            'config' => [
                                'type' => 'text',
                                'eval' => 'trim,password,saltedPassword',
                            ],
                        ],
                        'alreadyMigratedColumn' => [
                            'config' => [
                                'type' => 'password',
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'password',
                                'required' => true,
                            ],
                        ],
                        'aColumnWithPassword' => [
                            'config' => [
                                'type' => 'password',
                                'required' => true,
                                'hashed' => false,
                            ],
                        ],
                        'aColumnWithSaltedpassword' => [
                            'config' => [
                                'type' => 'password',
                                'required' => true,
                            ],
                        ],
                        'fullMigration' => [
                            'config' => [
                                'type' => 'password',
                                'required' => true,
                                'nullable' => true,
                            ],
                        ],
                        'differentColumn' => [
                            'config' => [
                                'type' => 'input',
                                'eval' => 'trim,unique',
                            ],
                        ],
                        'wrongTypeColumn' => [
                            'config' => [
                                'type' => 'text',
                                'eval' => 'trim,password,saltedPassword',
                            ],
                        ],
                        'alreadyMigratedColumn' => [
                            'config' => [
                                'type' => 'password',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider evalPasswordSaltedPasswordMigratedToTypePasswordDataProvider
     * @test
     */
    public function evalPasswordSaltedPasswordMigratedToTypePassword(array $input, array $expected): void
    {
        $subject = new TcaMigration();
        self::assertSame($expected, $subject->migrate($input));
    }

    private function renderTypeInputDateTimeMigratedToTypeDatetimeDataProvider(): iterable
    {
        yield 'Full example of renderType=inputDateTime migrated to type=datetime' => [
            'input' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                                'renderType' => 'inputDateTime',
                                'format' => 'date',
                                'required' => true,
                                'nullable' => true,
                                'readOnly' => true,
                                'size' => 20,
                                'max' => 1234,
                                'eval' => 'trim,time,int',
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'datetime',
                                'required' => true,
                                'nullable' => true,
                                'readOnly' => true,
                                'size' => 20,
                                'format' => 'time',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield 'format, renderType and eval are unset' => [
            'input' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                                'format' => 'date',
                                'renderType' => 'inputDateTime',
                                'eval' => 'trim,datetime,int',
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'datetime',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield 'eval=datetime is kept when type=input or renderType=inputDateTime is missing' => [
            'input' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                                'eval' => 'datetime',
                            ],
                        ],
                    ],
                ],
                'bTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'text',
                                'renderType' => 'inputDateTime',
                                'eval' => 'datetime',
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                                'eval' => 'datetime',
                            ],
                        ],
                    ],
                ],
                'bTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'text',
                                'renderType' => 'inputDateTime',
                                'eval' => 'datetime',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield 'Unset default for native type fields, if it\'s the types empty value' => [
            'input' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                                'renderType' => 'inputDateTime',
                                'dbType' => 'date',
                                'eval' => 'date',
                                'default' => '0000-00-00',
                            ],
                        ],
                        'bColumn' => [
                            'config' => [
                                'type' => 'input',
                                'renderType' => 'inputDateTime',
                                'dbType' => 'datetime',
                                'eval' => 'datetime',
                                'default' => '0000-00-00 00:00:00',
                            ],
                        ],
                        'cColumn' => [
                            'config' => [
                                'type' => 'input',
                                'renderType' => 'inputDateTime',
                                'dbType' => 'time',
                                'eval' => 'time',
                                'default' => '00:00:00',
                            ],
                        ],
                        'dColumn' => [
                            'config' => [
                                'type' => 'input',
                                'renderType' => 'inputDateTime',
                                'dbType' => 'time',
                                'eval' => 'time',
                                'default' => '20:20:20',
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'datetime',
                                'dbType' => 'date',
                                'format' => 'date',
                            ],
                        ],
                        'bColumn' => [
                            'config' => [
                                'type' => 'datetime',
                                'dbType' => 'datetime',
                            ],
                        ],
                        'cColumn' => [
                            'config' => [
                                'type' => 'datetime',
                                'dbType' => 'time',
                                'format' => 'time',
                            ],
                        ],
                        'dColumn' => [
                            'config' => [
                                'type' => 'datetime',
                                'dbType' => 'time',
                                'default' => '20:20:20',
                                'format' => 'time',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield 'Update default value for datetime if it\'s no int value and no native type is used' => [
            'input' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                                'renderType' => 'inputDateTime',
                                'eval' => 'datetime',
                                'default' => '0',
                            ],
                        ],
                        'bColumn' => [
                            'config' => [
                                'type' => 'input',
                                'renderType' => 'inputDateTime',
                                'eval' => 'datetime',
                                'default' => '',
                            ],
                        ],
                        'cColumn' => [
                            'config' => [
                                'type' => 'input',
                                'renderType' => 'inputDateTime',
                                'eval' => 'datetime',
                                'default' => '16362836',
                            ],
                        ],
                        'dColumn' => [
                            'config' => [
                                'type' => 'input',
                                'renderType' => 'inputDateTime',
                                'eval' => 'datetime',
                                'default' => 'invalid',
                            ],
                        ],
                        'eColumn' => [
                            'config' => [
                                'type' => 'input',
                                'renderType' => 'inputDateTime',
                                'default' => time(),
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'datetime',
                                'default' => 0,
                            ],
                        ],
                        'bColumn' => [
                            'config' => [
                                'type' => 'datetime',
                                'default' => 0,
                            ],
                        ],
                        'cColumn' => [
                            'config' => [
                                'type' => 'datetime',
                                'default' => 16362836,
                            ],
                        ],
                        'dColumn' => [
                            'config' => [
                                'type' => 'datetime',
                            ],
                        ],
                        'eColumn' => [
                            'config' => [
                                'type' => 'datetime',
                                'default' => time(),
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider renderTypeInputDateTimeMigratedToTypeDatetimeDataProvider
     * @test
     */
    public function renderTypeInputDateTimeMigratedToTypeDatetime(array $input, array $expected): void
    {
        $subject = new TcaMigration();
        self::assertSame($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function authModeEnforceIsRemoved(): void
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'aColumn' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'authMode' => 'explicitAllow',
                            'authMode_enforce' => 'strict',
                        ],
                    ],
                    'bColumn' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'authMode' => 'explicitAllow',
                            // This is an invalid value, but still removed
                            'authMode_enforce' => 'foo',
                        ],
                    ],
                ],
            ],
        ];
        $expected = [
            'aTable' => [
                'columns' => [
                    'aColumn' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'authMode' => 'explicitAllow',
                        ],
                    ],
                    'bColumn' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'authMode' => 'explicitAllow',
                        ],
                    ],
                ],
            ],
        ];
        self::assertSame($expected, (new TcaMigration())->migrate($input));
    }

    /**
     * @test
     */
    public function authModeValuesAreEnforced(): void
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'aColumn' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            // good
                            'authMode' => 'explicitAllow',
                        ],
                    ],
                    'bColumn' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            // forced to 'explicitAllow'
                            'authMode' => 'explicitDeny',
                        ],
                    ],
                    'cColumn' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            // forced to 'explicitAllow'
                            'authMode' => 'individual',
                        ],
                    ],
                    'dColumn' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            // forced to 'explicitAllow'
                            'authMode' => 'foo',
                        ],
                    ],
                ],
            ],
        ];
        $expected = [
            'aTable' => [
                'columns' => [
                    'aColumn' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'authMode' => 'explicitAllow',
                        ],
                    ],
                    'bColumn' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'authMode' => 'explicitAllow',
                        ],
                    ],
                    'cColumn' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'authMode' => 'explicitAllow',
                        ],
                    ],
                    'dColumn' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'authMode' => 'explicitAllow',
                        ],
                    ],
                ],
            ],
        ];
        self::assertSame($expected, (new TcaMigration())->migrate($input));
    }

    public function selectIndividualAllowDenyMigratedToNewPositionDataProvider(): iterable
    {
        yield 'keyword EXPL_ALLOW at position 5 in times array' => [
            'tca' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'select',
                                'authMode' => 'individual',
                                'items' => [
                                    [
                                        'Label 1',
                                        'Value 1',
                                        null,
                                        null,
                                        'EXPL_ALLOW',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'select',
                                'authMode' => 'explicitAllow',
                                'items' => [
                                    [
                                        'label' => 'Label 1',
                                        'value' => 'Value 1',
                                        'icon' => null,
                                        'group' => null,
                                        'description' => '',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield 'keyword EXPL_DENY at position 5 in times array' => [
            'tca' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'select',
                                'authMode' => 'individual',
                                'items' => [
                                    [
                                        'Label 1',
                                        'Value 1',
                                        null,
                                        null,
                                        'EXPL_DENY',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'select',
                                'authMode' => 'explicitAllow',
                                'items' => [
                                    [
                                        'label' => 'Label 1',
                                        'value' => 'Value 1',
                                        'icon' => null,
                                        'group' => null,
                                        'description' => '',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield 'keyword in items array NOT migrated to new position' => [
            'tca' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'select',
                                'authMode' => 'individual',
                                'items' => [
                                    [
                                        'Label 1',
                                        'Value 1',
                                        null,
                                        null,
                                        'EXPL_DENY',
                                    ],
                                    [
                                        'Label 2',
                                        'Value 2',
                                        null,
                                        null,
                                        'Description 2',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'select',
                                'authMode' => 'explicitAllow',
                                'items' => [
                                    [
                                        'label' => 'Label 1',
                                        'value' => 'Value 1',
                                        'icon' => null,
                                        'group' => null,
                                        'description' => '',
                                    ],
                                    [
                                        'label' => 'Label 2',
                                        'value' => 'Value 2',
                                        'icon' => null,
                                        'group' => null,
                                        'description' => 'Description 2',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield 'items array NOT migrated to new position without authMode=individual set' => [
            'tca' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'select',
                                'authMode' => 'explicitAllow',
                                'items' => [
                                    [
                                        'Label 1',
                                        'Value 1',
                                        null,
                                        null,
                                        'Description',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'select',
                                'authMode' => 'explicitAllow',
                                'items' => [
                                    [
                                        'label' => 'Label 1',
                                        'value' => 'Value 1',
                                        'icon' => null,
                                        'group' => null,
                                        'description' => 'Description',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield 'position 6 is unset' => [
            'tca' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'select',
                                'authMode' => 'individual',
                                'items' => [
                                    [
                                        'Label 1',
                                        'Value 1',
                                        null,
                                        null,
                                        'Description',
                                        'EXPL_ALLOW',
                                    ],
                                    [
                                        'Label 1',
                                        'Value 1',
                                        null,
                                        null,
                                        'Description',
                                        'EXPL_DENY',
                                    ],
                                    [
                                        'Label 1',
                                        'Value 1',
                                        null,
                                        null,
                                        'Description',
                                        'somethingElse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'select',
                                'authMode' => 'explicitAllow',
                                'items' => [
                                    [
                                        'label' => 'Label 1',
                                        'value' => 'Value 1',
                                        'icon' => null,
                                        'group' => null,
                                        'description' => 'Description',
                                    ],
                                    [
                                        'label' => 'Label 1',
                                        'value' => 'Value 1',
                                        'icon' => null,
                                        'group' => null,
                                        'description' => 'Description',
                                    ],
                                    [
                                        'label' => 'Label 1',
                                        'value' => 'Value 1',
                                        'icon' => null,
                                        'group' => null,
                                        'description' => 'Description',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider selectIndividualAllowDenyMigratedToNewPositionDataProvider
     * @test
     */
    public function selectIndividualAllowDenyMigratedToNewPosition(array $input, array $expected): void
    {
        $subject = new TcaMigration();
        self::assertEquals($expected, $subject->migrate($input));
    }

    private function renderTypeColorpickerToTypeColorDataProvider(): iterable
    {
        yield 'Full example of renderType=colorpicker migrated to type=color' => [
            'input' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                                'renderType' => 'colorpicker',
                                'required' => true,
                                'nullable' => true,
                                'size' => 20,
                                'max' => 1234,
                                'eval' => 'trim',
                                'valuePicker' => [
                                    'items' => [
                                        [ 'typo3 orange', '#FF8700'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'color',
                                'required' => true,
                                'nullable' => true,
                                'size' => 20,
                                'valuePicker' => [
                                    'items' => [
                                        ['typo3 orange', '#FF8700'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield 'eval gets unset' => [
            'input' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                                'renderType' => 'colorpicker',
                                'eval' => 'trim',
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'color',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider renderTypeColorpickerToTypeColorDataProvider
     * @test
     */
    public function renderTypeColorpickerToTypeColor(array $input, array $expected): void
    {
        $subject = new TcaMigration();
        self::assertSame($expected, $subject->migrate($input));
    }

    private function typeTextWithEvalIntOrDouble2MigratedToTypeNumberDataProvider(): iterable
    {
        yield 'Full example of eval=double2 migrated to type=number' => [
            'input' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                                'size' => 10,
                                'max' => 20,
                                'required' => true,
                                'nullable' => true,
                                'default' => 40,
                                'eval' => 'trim,double2',
                                'range' => [
                                    'lower' => 0,
                                ],
                                'slider' => [
                                    'step' => 10,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'number',
                                'size' => 10,
                                'required' => true,
                                'nullable' => true,
                                'default' => 40,
                                'range' => [
                                    'lower' => 0,
                                ],
                                'slider' => [
                                    'step' => 10,
                                ],
                                'format' => 'decimal',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        yield 'type input with eval int migrated to type number and eval removed' => [
            'input' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                                'eval' => 'trim,int',
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'number',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        yield 'type input with eval double2 migrated to type="number" and format="decimal"' => [
            'input' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                                'eval' => 'trim,double2,uniqueInPid',
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'number',
                                'format' => 'decimal',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        yield 'type input without eval int or double2 not migrated' => [
            'input' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                                'eval' => 'trim,uniqueInPid',
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                                'eval' => 'trim,uniqueInPid',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        yield 'type input with a renderType defined not migrated to type number' => [
            'input' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                                'renderType' => 'someRenderType',
                                'eval' => 'int,date',
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                                'renderType' => 'someRenderType',
                                'eval' => 'int,date',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider typeTextWithEvalIntOrDouble2MigratedToTypeNumberDataProvider
     * @test
     * @param array<string, mixed> $input
     * @param array<string, mixed> $expected
     */
    public function typeTextWithEvalIntOrDouble2MigratedToTypeNumber(array $input, array $expected): void
    {
        $subject = new TcaMigration();
        self::assertSame($expected, $subject->migrate($input));
    }

    private function propertyAlwaysDescriptionIsRemovedDataProvider(): iterable
    {
        yield 'always_description is removed' => [
            'input' => [
              'aTable' => [
                  'interface' => [
                      'always_description' => 0,
                      'anything_else' => true,
                  ],
                  'columns' => [],
              ],
            ],
            'expected' => [
                'aTable' => [
                    'interface' => [
                        'anything_else' => true,
                    ],
                    'columns' => [],
                ],
            ],
        ];

        yield 'interface is removed if empty' => [
            'input' => [
                'aTable' => [
                    'interface' => [
                        'always_description' => 0,
                    ],
                    'columns' => [],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [],
                ],
            ],
        ];
    }

    /**
     * @dataProvider propertyAlwaysDescriptionIsRemovedDataProvider
     * @test
     * @param array<string, mixed> $input
     * @param array<string, mixed> $expected
     */
    public function propertyAlwaysDescriptionIsRemoved(array $input, array $expected): void
    {
        $subject = new TcaMigration();
        self::assertSame($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function ctrlCruserIdIsRemoved(): void
    {
        $input = [
            'aTable' => [
                'ctrl' => [
                    'cruser_id' => 'cruser_id',
                ],
            ],
        ];
        $expected = [
            'aTable' => [
                'ctrl' => [
                ],
            ],
        ];
        $subject = new TcaMigration();
        self::assertSame($expected, $subject->migrate($input));
    }

    private function falHandlingInTypeInlineIsMigratedToTypeFileDataProvider(): iterable
    {
        yield 'Full example of type=inline with foreign_table=sys_file_reference migrated to type=file' => [
            'input' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'inline',
                                'minitems' => 1,
                                'maxitems' => 2,
                                'foreign_field' => 'uid_foreign',
                                'foreign_label' => 'uid_local',
                                'foreign_match_fields' => [
                                    'fieldname' => 'aColumn',
                                ],
                                'foreign_selector' => 'uid_local',
                                'foreign_unique' => 'uid_local',
                                'foreign_sortby' => 'sorting_foreign',
                                'foreign_table' => 'sys_file_reference',
                                'foreign_table_field' => 'tablenames',
                                'appearance' => [
                                    'createNewRelationLinkTitle' => 'Add file',
                                    'enabledControls' => [
                                        'delete' => true,
                                        'dragdrop' => true,
                                        'sort' => false,
                                        'hide' => true,
                                        'info' => true,
                                        'new' => false,
                                    ],
                                    'headerThumbnail' => [
                                        'field' => 'uid_local',
                                        'height' => '45m',
                                    ],
                                    'showPossibleLocalizationRecords' => true,
                                    'useSortable' => true,
                                    'showNewRecordLink' => true,
                                    'newRecordLinkAddTitle' => true,
                                    'newRecordLinkTitle' => true,
                                    'levelLinksPosition' => 'both',
                                    'useCombination' => true,
                                    'suppressCombinationWarning' => true,
                                ],
                                'filter' => [
                                    [
                                        'userFunc' => FileExtensionFilter::class . '->filterInlineChildren',
                                        'parameters' => [
                                            'allowedFileExtensions' => 'jpg,png',
                                            'disallowedFileExtensions' => '',
                                        ],
                                    ],
                                ],
                                'overrideChildTca' => [
                                    'columns' => [
                                        'uid_local' => [
                                            'config' => [
                                                'appearance' => [
                                                    'elementBrowserAllowed' => 'jpg,png',
                                                    'elementBrowserType' => 'file',
                                                ],
                                            ],
                                        ],
                                    ],
                                    'types' => [
                                        '0' => [
                                            'showitem' => '--palette--;;somePalette',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'file',
                                'minitems' => 1,
                                'maxitems' => 2,
                                'appearance' => [
                                    'createNewRelationLinkTitle' => 'Add file',
                                    'enabledControls' => [
                                        'delete' => true,
                                        'dragdrop' => true,
                                        'sort' => false,
                                        'hide' => true,
                                        'info' => true,
                                    ],
                                    'headerThumbnail' => [
                                        'height' => '45m',
                                    ],
                                    'showPossibleLocalizationRecords' => true,
                                    'useSortable' => true,
                                ],
                                'overrideChildTca' => [
                                    'types' => [
                                        '0' => [
                                            'showitem' => '--palette--;;somePalette',
                                        ],
                                    ],
                                ],
                                'allowed' => 'jpg,png',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield 'Allowed and disallowed list is migrated from unused filter' => [
            'input' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'inline',
                                'foreign_table' => 'sys_file_reference',
                                'filter' => [
                                    [
                                        'userFunc' => FileExtensionFilter::class . '->filterInlineChildren',
                                        'parameters' => [
                                            'allowedFileExtensions' => 'jpg,png',
                                            'disallowedFileExtensions' => 'pdf,pages',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'file',
                                'allowed' => 'jpg,png',
                                'disallowed' => 'pdf,pages',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield 'Allowed list from filter takes precedence over element browser related option' => [
            'input' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'inline',
                                'foreign_table' => 'sys_file_reference',
                                'overrideChildTca' => [
                                    'columns' => [
                                        'uid_local' => [
                                            'config' => [
                                                'appearance' => [
                                                    'elementBrowserAllowed' => 'jpg,png',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                                'filter' => [
                                    [
                                        'userFunc' => FileExtensionFilter::class . '->filterInlineChildren',
                                        'parameters' => [
                                            'allowedFileExtensions' => 'pdf,docx',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'file',
                                'allowed' => 'pdf,docx',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield 'customControls hook is removed' => [
            'input' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'inline',
                                'foreign_table' => 'sys_file_reference',
                                'customControls' => [
                                    'userFunc' => '->someFunc()',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'file',
                            ],
                        ],
                    ],
                ],
            ],
            'The \'customControls\' option is not evaluated anymore',
        ];
        yield 'renamed appearance options are migrated' => [
            'input' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'inline',
                                'foreign_table' => 'sys_file_reference',
                                'appearance' => [
                                    'showPossibleRecordsSelector' => false,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'file',
                                'appearance' => [
                                    'showFileSelectors' => false,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield 'Usage of sys_file_reference as foreign_table without type=inline is still possible' => [
            'input' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'select',
                                'foreign_table' => 'sys_file_reference',
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'select',
                                'foreign_table' => 'sys_file_reference',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider falHandlingInTypeInlineIsMigratedToTypeFileDataProvider
     * @test
     * @param array<string, mixed> $input
     * @param array<string, mixed> $expected
     * @param string $expectedMessagePart
     */
    public function falHandlingInTypeInlineIsMigratedToTypeFile(array $input, array $expected, $expectedMessagePart = ''): void
    {
        $subject = new TcaMigration();
        self::assertSame($expected, $subject->migrate($input));
        if ($expectedMessagePart !== '') {
            $messageFound = false;
            foreach ($subject->getMessages() as $message) {
                if (str_contains($message, $expectedMessagePart)) {
                    $messageFound = true;
                    break;
                }
            }
            self::assertTrue($messageFound);
        }
    }

    /**
     * @test
     */
    public function falRelatedElementBrowserOptionsAreRemovedFromTypeGroup(): void
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'aColumns' => [
                        'config' => [
                            'type' => 'group',
                            'appearance' => [
                                'elementBrowserAllowed' => 'jpg,png',
                                'elementBrowserType' => 'file',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $expected = [
            'aTable' => [
                'columns' => [
                    'aColumns' => [
                        'config' => [
                            'type' => 'group',
                        ],
                    ],
                ],
            ],
        ];
        $subject = new TcaMigration();
        self::assertSame($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function falRelatedOptionsAreRemovedFromTypeInline(): void
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'aColumns' => [
                        'config' => [
                            'type' => 'inline',
                            'appearance' => [
                                'headerThumbnail' => [
                                    'height' => '45c',
                                ],
                                'fileUploadAllowed' => true,
                                'fileByUrlAllowed' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $expected = [
            'aTable' => [
                'columns' => [
                    'aColumns' => [
                        'config' => [
                            'type' => 'inline',
                        ],
                    ],
                ],
            ],
        ];
        $subject = new TcaMigration();
        self::assertSame($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function passContentIsRemovedFromTypeNone(): void
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'aColumn' => [
                        'config' => [
                            'type' => 'none',
                            'pass_content' => false,
                        ],
                    ],
                    'bColumn' => [
                        'config' => [
                            'type' => 'input',
                            'pass_content' => false,
                        ],
                    ],
                ],
            ],
        ];
        $expected = [
            'aTable' => [
                'columns' => [
                    'aColumn' => [
                        'config' => [
                            'type' => 'none',
                        ],
                    ],
                    'bColumn' => [
                        'config' => [
                            'type' => 'input',
                            'pass_content' => false,
                        ],
                    ],
                ],
            ],
        ];
        self::assertSame($expected, (new TcaMigration())->migrate($input));
    }

    /**
     * @test
     */
    public function itemsAreMigratedToAssociatedArray(): void
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'aColumn' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [
                                [
                                    'foo',
                                    'bar',
                                    'baz',
                                    'bee',
                                    'boo',
                                ],
                            ],
                        ],
                    ],
                    'bColumn' => [
                        'config' => [
                            'type' => 'radio',
                            'items' => [
                                [
                                    'foo',
                                    'bar',
                                ],
                            ],
                        ],
                    ],
                    'cColumn' => [
                        'config' => [
                            'type' => 'check',
                            'items' => [
                                [
                                    0 => 'foo',
                                    'invertStateDisplay' => true,
                                ],
                            ],
                        ],
                    ],
                    'dColumn' => [
                        'config' => [
                            'type' => 'foo',
                            'items' => [
                                [
                                    'foo',
                                    'bar',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $expected = [
            'aTable' => [
                'columns' => [
                    'aColumn' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [
                                [
                                    'label' => 'foo',
                                    'value' => 'bar',
                                    'icon' => 'baz',
                                    'group' => 'bee',
                                    'description' => 'boo',
                                ],
                            ],
                        ],
                    ],
                    'bColumn' => [
                        'config' => [
                            'type' => 'radio',
                            'items' => [
                                [
                                    'label' => 'foo',
                                    'value' => 'bar',
                                ],
                            ],
                        ],
                    ],
                    'cColumn' => [
                        'config' => [
                            'type' => 'check',
                            'items' => [
                                [
                                    'invertStateDisplay' => true,
                                    'label' => 'foo',
                                ],
                            ],
                        ],
                    ],
                    'dColumn' => [
                        'config' => [
                            'type' => 'foo',
                            'items' => [
                                [
                                    'foo',
                                    'bar',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        self::assertSame($expected, (new TcaMigration())->migrate($input));
    }
}
