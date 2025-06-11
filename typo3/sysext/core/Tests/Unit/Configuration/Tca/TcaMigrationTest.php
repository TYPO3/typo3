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

namespace TYPO3\CMS\Core\Tests\Unit\Configuration\Tca;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\Tca\TcaMigration;
use TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TcaMigrationTest extends UnitTestCase
{
    #[Test]
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

    #[Test]
    public function usageOfSubTypeAddsMessage(): void
    {
        $input = [
            'aTable' => [
                'types' => [
                    'aType' => [
                        'subtype_value_field' => 'subtype_value_field',
                    ],
                ],
            ],
            'bTable' => [
                'types' => [
                    'bType' => [
                        'subtypes_addlist' => 'subtype_add_field',
                    ],
                ],
            ],
            'cTable' => [
                'types' => [
                    'cType' => [
                        'subtypes_excludelist' => 'subtype_exclude_field',
                    ],
                ],
            ],
            'dTable' => [
                'types' => [
                    'aType' => [
                        'defaultValues' => ['foo'],
                    ],
                ],
            ],
        ];
        $subject = new TcaMigration();
        $subject->migrate($input);
        $messages = $subject->getMessages();
        self::assertCount(3, $messages);
        self::assertStringContainsString('The TCA record type \'aType\' of table \'aTable\' makes use of the removed "sub types" functionality. The options \'subtype_value_field\', \'subtypes_addlist\' and \'subtypes_excludelist\' are not evaluated anymore. Please adjust your TCA accordingly by migrating those sub types to dedicated record types.', $messages[0]);
        self::assertStringContainsString('The TCA record type \'bType\' of table \'bTable\' makes use of the removed "sub types" functionality. The options \'subtype_value_field\', \'subtypes_addlist\' and \'subtypes_excludelist\' are not evaluated anymore. Please adjust your TCA accordingly by migrating those sub types to dedicated record types.', $messages[1]);
        self::assertStringContainsString('The TCA record type \'cType\' of table \'cTable\' makes use of the removed "sub types" functionality. The options \'subtype_value_field\', \'subtypes_addlist\' and \'subtypes_excludelist\' are not evaluated anymore. Please adjust your TCA accordingly by migrating those sub types to dedicated record types.', $messages[2]);
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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
            ],
        ];
        $subject = new TcaMigration();
        self::assertEquals($expected, $subject->migrate($input));
    }

    #[Test]
    public function removeMaxDBListItemsIsRemoved(): void
    {
        $input = [
            'aTable' => [
                'interface' => [
                ],
            ],
            'bTable' => [
                'interface' => [
                    'maxDBListItems' => 30,
                    'maxSingleDBListItems' => 50,
                ],
            ],
        ];
        $expected = [
            'aTable' => [
            ],
            'bTable' => [
            ],
        ];
        $subject = new TcaMigration();
        self::assertEquals($expected, $subject->migrate($input));
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    public static function internalTypeFolderMigratedToTypeDataProvider(): iterable
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

    #[DataProvider('internalTypeFolderMigratedToTypeDataProvider')]
    #[Test]
    public function internalTypeFolderMigratedToType(array $input, array $expected): void
    {
        $subject = new TcaMigration();
        self::assertSame($expected, $subject->migrate($input));
    }

    public static function requiredFlagIsMigratedDataProvider(): iterable
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

    #[DataProvider('requiredFlagIsMigratedDataProvider')]
    #[Test]
    public function requiredFlagIsMigrated(array $tca, array $expected): void
    {
        $subject = new TcaMigration();
        self::assertEquals($expected, $subject->migrate($tca));
    }

    public static function evalNullMigratedToNullableOptionDataProvider(): iterable
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
     * @param array<string, mixed> $tca
     * @param array<string, mixed> $expected
     */
    #[DataProvider('evalNullMigratedToNullableOptionDataProvider')]
    #[Test]
    public function evalNullMigratedToNullableOption(array $tca, array $expected): void
    {
        $subject = new TcaMigration();
        self::assertSame($expected, $subject->migrate($tca));
    }

    public static function evalEmailMigratedToTypeDataProvider(): iterable
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
                        'columnWithOutdatedConfiguration' => [
                            'config' => [
                                'type' => 'input',
                                'eval' => 'email',
                                'max' => 255,
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
                        'columnWithOutdatedConfiguration' => [
                            'config' => [
                                'type' => 'email',
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

    #[DataProvider('evalEmailMigratedToTypeDataProvider')]
    #[Test]
    public function evalEmailMigratedToType(array $input, array $expected): void
    {
        $subject = new TcaMigration();
        self::assertSame($expected, $subject->migrate($input));
    }

    public static function typeNoneColsMigratedToSizeDataProvider(): iterable
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

    #[DataProvider('typeNoneColsMigratedToSizeDataProvider')]
    #[Test]
    public function typeNoneColsMigratedToSize(array $tca, array $expected): void
    {
        $subject = new TcaMigration();
        self::assertSame($expected, $subject->migrate($tca));
    }

    public static function renderTypeInputLinkMigratedToTypeLinkDataProvider(): iterable
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

    #[DataProvider('renderTypeInputLinkMigratedToTypeLinkDataProvider')]
    #[Test]
    public function renderTypeInputLinkMigratedToTypeLink(array $input, array $expected): void
    {
        $subject = new TcaMigration();
        self::assertSame($expected, $subject->migrate($input));
    }

    public static function evalPasswordSaltedPasswordMigratedToTypePasswordDataProvider(): iterable
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

    #[DataProvider('evalPasswordSaltedPasswordMigratedToTypePasswordDataProvider')]
    #[Test]
    public function evalPasswordSaltedPasswordMigratedToTypePassword(array $input, array $expected): void
    {
        $subject = new TcaMigration();
        self::assertSame($expected, $subject->migrate($input));
    }

    public static function renderTypeInputDateTimeMigratedToTypeDatetimeDataProvider(): iterable
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

    #[DataProvider('renderTypeInputDateTimeMigratedToTypeDatetimeDataProvider')]
    #[Test]
    public function renderTypeInputDateTimeMigratedToTypeDatetime(array $input, array $expected): void
    {
        $subject = new TcaMigration();
        self::assertSame($expected, $subject->migrate($input));
    }

    #[Test]
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

    #[Test]
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

    public static function selectIndividualAllowDenyMigratedToNewPositionDataProvider(): iterable
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

    #[DataProvider('selectIndividualAllowDenyMigratedToNewPositionDataProvider')]
    #[Test]
    public function selectIndividualAllowDenyMigratedToNewPosition(array $tca, array $expected): void
    {
        $subject = new TcaMigration();
        self::assertEquals($expected, $subject->migrate($tca));
    }

    public static function renderTypeColorpickerToTypeColorDataProvider(): iterable
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
                                        [ 'label' => 'typo3 orange', 'value' => '#FF8700'],
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
                                        ['label' => 'typo3 orange', 'value' => '#FF8700'],
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

    #[DataProvider('renderTypeColorpickerToTypeColorDataProvider')]
    #[Test]
    public function renderTypeColorpickerToTypeColor(array $input, array $expected): void
    {
        $subject = new TcaMigration();
        self::assertSame($expected, $subject->migrate($input));
    }

    public static function typeTextWithEvalIntOrDouble2MigratedToTypeNumberDataProvider(): iterable
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
     * @param array<string, mixed> $input
     * @param array<string, mixed> $expected
     */
    #[DataProvider('typeTextWithEvalIntOrDouble2MigratedToTypeNumberDataProvider')]
    #[Test]
    public function typeTextWithEvalIntOrDouble2MigratedToTypeNumber(array $input, array $expected): void
    {
        $subject = new TcaMigration();
        self::assertSame($expected, $subject->migrate($input));
    }

    public static function propertyAlwaysDescriptionIsRemovedDataProvider(): iterable
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
     * @param array<string, mixed> $input
     * @param array<string, mixed> $expected
     */
    #[DataProvider('propertyAlwaysDescriptionIsRemovedDataProvider')]
    #[Test]
    public function propertyAlwaysDescriptionIsRemoved(array $input, array $expected): void
    {
        $subject = new TcaMigration();
        self::assertSame($expected, $subject->migrate($input));
    }

    #[Test]
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

    public static function falHandlingInTypeInlineIsMigratedToTypeFileDataProvider(): iterable
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
                                'foreign_match_fields' => [
                                    'fieldname' => 'aColumn',
                                ],
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
     * @param array<string, mixed> $input
     * @param array<string, mixed> $expected
     * @param string $expectedMessagePart
     */
    #[DataProvider('falHandlingInTypeInlineIsMigratedToTypeFileDataProvider')]
    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
    public function valuePickerItemsAreMigratedToAssociatedArray(): void
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'aColumn' => [
                        'config' => [
                            'type' => 'select',
                            'valuePicker' => [
                                'items' => [
                                    [
                                        'foo',
                                        'bar',
                                    ],
                                    [
                                        'lorem',
                                        'ipsum',
                                    ],
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
                            'valuePicker' => [
                                'items' => [
                                    [
                                        'label' => 'foo',
                                        'value' => 'bar',
                                    ],
                                    [
                                        'label' => 'lorem',
                                        'value' => 'ipsum',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        self::assertSame($expected, (new TcaMigration())->migrate($input));
    }

    #[Test]
    public function migrationRemovesMmInsertFields(): void
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'aColumn' => [
                        'config' => [
                            'type' => 'group',
                            'MM_insert_fields' => [
                                'aField' => 'aValue',
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
                            'type' => 'group',
                        ],
                    ],
                ],
            ],
        ];
        self::assertSame($expected, (new TcaMigration())->migrate($input));
    }

    #[Test]
    public function migrationRemovesMmHasUidFieldIfFalse(): void
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'aColumn' => [
                        'config' => [
                            'type' => 'group',
                            'MM_hasUidField' => false,
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
                            'type' => 'group',
                        ],
                    ],
                ],
            ],
        ];
        self::assertSame($expected, (new TcaMigration())->migrate($input));
    }

    #[Test]
    public function migrationRemovesMmHasUidFieldIfTrue(): void
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'aColumn' => [
                        'config' => [
                            'type' => 'group',
                            'MM_hasUidField' => true,
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
                            'type' => 'group',
                        ],
                    ],
                ],
            ],
        ];
        self::assertSame($expected, (new TcaMigration())->migrate($input));
    }

    #[Test]
    public function migrationChangesRenderTypeFromT3Editor(): void
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'aColumn' => [
                        'config' => [
                            'type' => 'text',
                            'renderType' => 't3editor',
                        ],
                    ],
                    'bColumn' => [
                        'config' => [
                            'type' => 'text',
                        ],
                    ],
                ],
                'types' => [
                    'aType' => [
                        'columnsOverrides' => [
                            'bColumn' => [
                                'config' => [
                                    'renderType' => 't3editor',
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
                            'type' => 'text',
                            'renderType' => 'codeEditor',
                        ],
                    ],
                    'bColumn' => [
                        'config' => [
                            'type' => 'text',
                        ],
                    ],
                ],
                'types' => [
                    'aType' => [
                        'columnsOverrides' => [
                            'bColumn' => [
                                'config' => [
                                    'renderType' => 'codeEditor',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        self::assertSame($expected, (new TcaMigration())->migrate($input));
    }

    #[Test]
    public function removeAllowLanguageSynchronizationFromColumnsOverrides(): void
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'aColumn' => [
                        'config' => [
                            'type' => 'text',
                            'config' => [
                                'behaviour' => [
                                    'allowLanguageSynchronization' => true,
                                ],
                            ],
                        ],
                    ],
                    'bColumn' => [
                        'config' => [
                            'type' => 'text',
                        ],
                    ],
                ],
                'types' => [
                    'aType' => [
                        'showitem' => 'bColumn',
                        'columnsOverrides' => [
                            'bColumn' => [
                                'config' => [
                                    'behaviour' => [
                                        'allowLanguageSynchronization' => true,
                                    ],
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
                            'type' => 'text',
                            'config' => [
                                'behaviour' => [
                                    'allowLanguageSynchronization' => true,
                                ],
                            ],
                        ],
                    ],
                    'bColumn' => [
                        'config' => [
                            'type' => 'text',
                        ],
                    ],
                ],
                'types' => [
                    'aType' => [
                        'showitem' => 'bColumn',
                        'columnsOverrides' => [
                            'bColumn' => [
                                'config' => [
                                    'behaviour' => [
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        self::assertSame($expected, (new TcaMigration())->migrate($input));
    }

    #[Test]
    public function inlineChildrenAreMadeWorkspaceAware(): void
    {
        $input = [
            'parent1WorkspaceAware' => [
                'ctrl' => [
                    'versioningWS' => true,
                ],
                'columns' => [
                    'aColumn' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'child1NotWorkspaceAware',
                        ],
                    ],
                ],
                'types' => [
                    'aType' => [
                        'showitem' => 'aColumn',
                    ],
                ],
            ],
            'child1NotWorkspaceAware' => [
                // This table is made workspace aware since the parent is.
                'columns' => [
                    'aColumn' => [
                        'config' => [
                            'type' => 'text',
                        ],
                    ],
                ],
                'types' => [
                    'aType' => [
                        'showitem' => 'aColumn',
                    ],
                ],
            ],

            'parent2WorkspaceAware' => [
                'ctrl' => [
                    'versioningWS' => true,
                ],
                'columns' => [
                    'aColumn' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'child2WorkspaceAware',
                        ],
                    ],
                ],
                'types' => [
                    'aType' => [
                        'showitem' => 'aColumn',
                    ],
                ],
            ],
            'child2WorkspaceAware' => [
                'ctrl' => [
                    'versioningWS' => true,
                ],
                'columns' => [
                    'aColumn' => [
                        'config' => [
                            'type' => 'text',
                        ],
                    ],
                ],
                'types' => [
                    'aType' => [
                        'showitem' => 'aColumn',
                    ],
                ],
            ],

            'parent3NotWorkspaceAware' => [
                'columns' => [
                    'aColumn' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'child3NotWorkspaceAware',
                        ],
                    ],
                ],
                'types' => [
                    'aType' => [
                        'showitem' => 'aColumn',
                    ],
                ],
            ],
            'child3NotWorkspaceAware' => [
                'columns' => [
                    'aColumn' => [
                        'config' => [
                            'type' => 'text',
                        ],
                    ],
                ],
                'types' => [
                    'aType' => [
                        'showitem' => 'aColumn',
                    ],
                ],
            ],

            'parent4NotWorkspaceAware' => [
                'columns' => [
                    'aColumn' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'child4WorkspaceAware',
                        ],
                    ],
                ],
                'types' => [
                    'aType' => [
                        'showitem' => 'aColumn',
                    ],
                ],
            ],
            'child4WorkspaceAware' => [
                'ctrl' => [
                    'versioningWS' => true,
                ],
                'columns' => [
                    'aColumn' => [
                        'config' => [
                            'type' => 'text',
                        ],
                    ],
                ],
                'types' => [
                    'aType' => [
                        'showitem' => 'aColumn',
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['child1NotWorkspaceAware']['ctrl']['versioningWS'] = true;
        self::assertSame($expected, (new TcaMigration())->migrate($input));
    }

    #[DataProvider('requiredYearFlagIsRemovedDataProvider')]
    #[Test]
    public function requiredYearFlagIsRemoved(array $tca, array $expected): void
    {
        $subject = new TcaMigration();
        self::assertEquals($expected, $subject->migrate($tca));
    }

    public static function requiredYearFlagIsRemovedDataProvider(): iterable
    {
        yield 'field contains eval=year' => [
            'tca' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                                'eval' => 'year',
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

        yield 'field contains eval=fo,year' => [
            'tca' => [
                'aTable' => [
                    'columns' => [
                        'aColumn' => [
                            'config' => [
                                'type' => 'input',
                                'eval' => 'fo,year',
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
                                'eval' => 'fo',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    #[Test]
    public function removeIsStaticControlOption(): void
    {
        $input = [
            'aTable' => [
                'ctrl' => [
                    'title' => 'foobar',
                    'is_static' => true,
                ],
            ],
        ];
        $expected = [
            'aTable' => [
                'ctrl' => [
                    'title' => 'foobar',
                ],
            ],
        ];

        self::assertSame($expected, (new TcaMigration())->migrate($input));
    }
}
