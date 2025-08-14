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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DatabaseRowInitializeNewTest extends UnitTestCase
{
    #[Test]
    public function addDataReturnSameDataIfCommandIsEdit(): void
    {
        $input = [
            'command' => 'edit',
            'tableName' => 'aTable',
            'vanillaUid' => 23,
            'databaseRow' => [
                'uid' => 42,
            ],
            'userTsConfig' => [
                'TCAdefaults.' => [
                    'aTable.' => [
                        'uid' => 23,
                    ],
                ],
            ],
        ];
        self::assertSame($input, (new DatabaseRowInitializeNew())->addData($input));
    }

    #[Test]
    public function addDataThrowsExceptionIfDatabaseRowIsNotArray(): void
    {
        $input = [
            'tableName' => 'aTable',
            'command' => 'new',
            'databaseRow' => 'not-an-array',
        ];
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1444431128);
        (new DatabaseRowInitializeNew())->addData($input);
    }

    #[Test]
    public function addDataKeepsGivenDefaultsIfCommandIsNew(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'aTable',
            'vanillaUid' => 23,
            'neighborRow' => null,
            'inlineChildChildUid' => null,
            'isInlineChild' => false,
            'databaseRow' => [
                'aField' => 42,
            ],
        ];
        $expected = $input;
        $expected['databaseRow']['pid'] = 23;
        self::assertSame($expected, (new DatabaseRowInitializeNew())->addData($input));
    }

    #[Test]
    public function addDataSetsDefaultDataFromUserTsIfColumnIsDefinedInTca(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'aTable',
            'vanillaUid' => 23,
            'neighborRow' => null,
            'inlineChildChildUid' => null,
            'isInlineChild' => false,
            'databaseRow' => [],
            'userTsConfig' => [
                'TCAdefaults.' => [
                    'aTable.' => [
                        'aField' => 'userTsValue',
                    ],
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [],
                ],
            ],
        ];
        $expected = [
            'aField' => 'userTsValue',
            'pid' => 23,
        ];
        $result = (new DatabaseRowInitializeNew())->addData($input);
        self::assertSame($expected, $result['databaseRow']);
    }

    #[Test]
    public function addDataDoesNotSetDefaultDataFromUserTsIfColumnIsMissingInTca(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'aTable',
            'vanillaUid' => 23,
            'neighborRow' => null,
            'inlineChildChildUid' => null,
            'isInlineChild' => false,
            'databaseRow' => [],
            'userTsConfig' => [
                'TCAdefaults.' => [
                    'aTable.' => [
                        'aField' => 'userTsValue',
                    ],
                ],
            ],
            'processedTca' => [
                'columns' => [],
            ],
        ];
        $expected = [
            'pid' => 23,
        ];
        $result = (new DatabaseRowInitializeNew())->addData($input);
        self::assertSame($expected, $result['databaseRow']);
    }

    #[Test]
    public function addDataSetsDefaultDataFromPageTsIfColumnIsDefinedInTca(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'aTable',
            'vanillaUid' => 23,
            'neighborRow' => null,
            'inlineChildChildUid' => null,
            'isInlineChild' => false,
            'databaseRow' => [],
            'pageTsConfig' => [
                'TCAdefaults.' => [
                    'aTable.' => [
                        'aField' => 'pageTsValue',
                    ],
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [],
                ],
            ],
        ];
        $expected = [
            'aField' => 'pageTsValue',
            'pid' => 23,
        ];
        $result = (new DatabaseRowInitializeNew())->addData($input);
        self::assertSame($expected, $result['databaseRow']);
    }

    #[Test]
    public function addDataDoesNotSetDefaultDataFromPageTsIfColumnIsMissingInTca(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'aTable',
            'vanillaUid' => 23,
            'neighborRow' => null,
            'inlineChildChildUid' => null,
            'isInlineChild' => false,
            'databaseRow' => [],
            'pageTsConfig' => [
                'TCAdefaults.' => [
                    'aTable.' => [
                        'aField' => 'pageTsValue',
                    ],
                ],
            ],
            'processedTca' => [
                'columns' => [],
            ],
        ];
        $expected = [
            'pid' => 23,
        ];
        $result = (new DatabaseRowInitializeNew())->addData($input);
        self::assertSame($expected, $result['databaseRow']);
    }

    #[Test]
    public function addDataSetsDefaultDataOverrulingFromPageTs(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'aTable',
            'vanillaUid' => 23,
            'neighborRow' => null,
            'inlineChildChildUid' => null,
            'isInlineChild' => false,
            'databaseRow' => [],
            'pageTsConfig' => [
                'TCAdefaults.' => [
                    'aTable.' => [
                        'aField' => 'pageTsValue',
                    ],
                ],
            ],
            'userTsConfig' => [
                'TCAdefaults.' => [
                    'aTable.' => [
                        'aField' => 'userTsValue',
                    ],
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [],
                ],
            ],
        ];
        $expected = [
            'aField' => 'pageTsValue',
            'pid' => 23,
        ];
        $result = (new DatabaseRowInitializeNew())->addData($input);
        self::assertSame($expected, $result['databaseRow']);
    }

    #[Test]
    public function addDataSetsDefaultFromNeighborRow(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'aTable',
            'vanillaUid' => 23,
            'inlineChildChildUid' => null,
            'isInlineChild' => false,
            'databaseRow' => [],
            'neighborRow' => [
                'aField' => 'valueFromNeighbor',
            ],
            'processedTca' => [
                'ctrl' => [
                    'useColumnsForDefaultValues' => 'aField',
                ],
                'columns' => [
                    'aField' => [],
                ],
            ],
        ];
        $expected = [
            'aField' => 'valueFromNeighbor',
            'pid' => 23,
        ];
        $result = (new DatabaseRowInitializeNew())->addData($input);
        self::assertSame($expected, $result['databaseRow']);
    }

    #[Test]
    public function addDataSetsDefaultDataOverrulingFromNeighborRow(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'aTable',
            'vanillaUid' => 23,
            'inlineChildChildUid' => null,
            'isInlineChild' => false,
            'databaseRow' => [],
            'neighborRow' => [
                'aField' => 'valueFromNeighbor',
            ],
            'pageTsConfig' => [
                'TCAdefaults.' => [
                    'aTable.' => [
                        'aField' => 'pageTsValue',
                    ],
                ],
            ],
            'userTsConfig' => [
                'TCAdefaults.' => [
                    'aTable.' => [
                        'aField' => 'userTsValue',
                    ],
                ],
            ],
            'processedTca' => [
                'ctrl' => [
                    'useColumnsForDefaultValues' => 'aField',
                ],
                'columns' => [
                    'aField' => [],
                ],
            ],
        ];
        $expected = [
            'aField' => 'valueFromNeighbor',
            'pid' => 23,
        ];
        $result = (new DatabaseRowInitializeNew())->addData($input);
        self::assertSame($expected, $result['databaseRow']);
    }

    #[Test]
    public function addDataSetsDefaultDataFromDefaultValuesIfColumnIsDefinedInTca(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'aTable',
            'vanillaUid' => 23,
            'neighborRow' => null,
            'inlineChildChildUid' => null,
            'isInlineChild' => false,
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [],
                ],
            ],
            'defaultValues' => [
                'aTable' => [
                    'aField' => 'getValue',
                ],
            ],
        ];
        $expected = [
            'aField' => 'getValue',
            'pid' => 23,
        ];
        $result = (new DatabaseRowInitializeNew())->addData($input);
        self::assertSame($expected, $result['databaseRow']);
    }

    #[Test]
    public function addDataDoesNotSetDefaultDataFromDefaultValuesIfColumnIsMissingInTca(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'aTable',
            'vanillaUid' => 23,
            'neighborRow' => null,
            'inlineChildChildUid' => null,
            'isInlineChild' => false,
            'databaseRow' => [],
            'userTsConfig' => [
                'TCAdefaults.' => [
                    'aTable.' => [
                        'aField' => 'pageTsValue',
                    ],
                ],
            ],
            'processedTca' => [
                'columns' => [],
            ],
            'defaultValues' => [
                'aTable' => [
                    'aField' => 'getValue',
                ],
            ],
        ];
        $expected = [
            'pid' => 23,
        ];
        $result = (new DatabaseRowInitializeNew())->addData($input);
        self::assertSame($expected, $result['databaseRow']);
    }

    #[Test]
    public function addDataSetsDefaultDataOverrulesOtherDefaults(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'aTable',
            'vanillaUid' => 23,
            'inlineChildChildUid' => null,
            'isInlineChild' => false,
            'databaseRow' => [],
            'neighborRow' => [
                'aField' => 'valueFromNeighbor',
            ],
            'pageTsConfig' => [
                'TCAdefaults.' => [
                    'aTable.' => [
                        'aField' => 'pageTsValue',
                    ],
                ],
            ],
            'userTsConfig' => [
                'TCAdefaults.' => [
                    'aTable.' => [
                        'aField' => 'userTsValue',
                    ],
                ],
            ],
            'processedTca' => [
                'ctrl' => [
                    'useColumnsForDefaultValues' => 'aField',
                ],
                'columns' => [
                    'aField' => [],
                ],
            ],
            'defaultValues' => [
                'aTable' => [
                    'aField' => 'postValue',
                ],
            ],
        ];
        $expected = [
            'aField' => 'postValue',
            'pid' => 23,
        ];
        $result = (new DatabaseRowInitializeNew())->addData($input);
        self::assertSame($expected, $result['databaseRow']);
    }

    #[Test]
    public function addDataThrowsExceptionWithGivenChildChildUidButMissingInlineConfig(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'aTable',
            'neighborRow' => null,
            'isInlineChild' => false,
            'databaseRow' => [],
            'inlineChildChildUid' => 42,
        ];
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1444434102);
        (new DatabaseRowInitializeNew())->addData($input);
    }

    #[Test]
    public function addDataThrowsExceptionWithGivenChildChildUidButIsNotInteger(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'aTable',
            'neighborRow' => null,
            'isInlineChild' => false,
            'databaseRow' => [],
            'inlineChildChildUid' => '42',
        ];
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1444434103);
        (new DatabaseRowInitializeNew())->addData($input);
    }

    #[Test]
    public function addDataSetsForeignSelectorFieldToValueOfChildChildUid(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'aTable',
            'neighborRow' => null,
            'isInlineChild' => false,
            'databaseRow' => [],
            'inlineChildChildUid' => 42,
            'inlineParentConfig' => [
                'foreign_selector' => 'theForeignSelectorField',
            ],
            'processedTca' => [
                'columns' => [
                    'theForeignSelectorField' => [
                        'config' => [
                            'type' => 'group',
                        ],
                    ],
                ],
            ],
            'vanillaUid' => 5,
        ];
        $expected = $input;
        $expected['databaseRow']['theForeignSelectorField'] = 42;
        $expected['databaseRow']['pid'] = 5;
        self::assertSame($expected, (new DatabaseRowInitializeNew())->addData($input));
    }

    #[Test]
    public function addDataThrowsExceptionIfForeignSelectorDoesNotPointToGroupOrSelectField(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'aTable',
            'neighborRow' => null,
            'isInlineChild' => false,
            'databaseRow' => [],
            'inlineChildChildUid' => 42,
            'inlineParentConfig' => [
                'foreign_selector' => 'theForeignSelectorField',
            ],
            'processedTca' => [
                'columns' => [
                    'theForeignSelectorField' => [
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                ],
            ],
        ];
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1444434104);
        (new DatabaseRowInitializeNew())->addData($input);
    }

    #[Test]
    public function addDataThrowsExceptionIfInlineParentLanguageIsNoInteger(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'aTable',
            'neighborRow' => null,
            'inlineChildChildUid' => null,
            'isInlineChild' => false,
            'databaseRow' => [],
            'inlineParentConfig' => [
                'inline' => [
                    'parentSysLanguageUid' => 'not-an-integer',
                ],
            ],
            'processedTca' => [
                'ctrl' => [
                    'languageField' => 'sys_language_uid',
                    'transOrigPointerField' => 'l10n_parent',
                ],
                'columns' => [],
            ],
        ];
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1490360772);
        (new DatabaseRowInitializeNew())->addData($input);
    }

    #[Test]
    public function addDataSetsSysLanguageUidFromParent(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'aTable',
            'neighborRow' => null,
            'inlineChildChildUid' => null,
            'isInlineChild' => false,
            'vanillaUid' => 1,
            'databaseRow' => [],
            'inlineParentConfig' => [
                'inline' => [
                    'parentSysLanguageUid' => '42',
                ],
            ],
            'processedTca' => [
                'ctrl' => [
                    'languageField' => 'sys_language_uid',
                    'transOrigPointerField' => 'l10n_parent',
                ],
                'columns' => [],
            ],
        ];
        $expected = $input;
        $expected['databaseRow'] = [
            'sys_language_uid' => 42,
            'pid' => 1,
        ];
        $result = (new DatabaseRowInitializeNew())->addData($input);
        self::assertSame($expected, $result);
    }

    #[Test]
    public function addDataSetsPidToVanillaUid(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'aTable',
            'vanillaUid' => 23,
            'databaseRow' => [],
            'neighborRow' => null,
            'inlineChildChildUid' => null,
            'isInlineChild' => false,
        ];
        $expected = [];
        $expected['pid'] = 23;
        $result = (new DatabaseRowInitializeNew())->addData($input);
        self::assertSame($expected, $result['databaseRow']);
    }

    #[Test]
    public function addDataDoesNotUsePageTsValueForPidIfRecordIsNotInlineChild(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'aTable',
            'vanillaUid' => 23,
            'neighborRow' => null,
            'inlineChildChildUid' => null,
            'databaseRow' => [],
            'pageTsConfig' => [
                'TCAdefaults.' => [
                    'aTable.' => [
                        'pid' => '42',
                    ],
                ],
            ],
            'isInlineChild' => false,
        ];
        $expected = $input;
        $expected['databaseRow']['pid'] = 23;
        self::assertSame($expected, (new DatabaseRowInitializeNew())->addData($input));
    }

    #[Test]
    public function addDataThrowsExceptionIfPageTsConfigPidValueCanNotBeInterpretedAsInteger(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'aTable',
            'vanillaUid' => 23,
            'neighborRow' => null,
            'inlineChildChildUid' => null,
            'databaseRow' => [],
            'pageTsConfig' => [
                'TCAdefaults.' => [
                    'aTable.' => [
                        'pid' => 'notAnInteger',
                    ],
                ],
            ],
            'isInlineChild' => true,
        ];
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1461598332);
        (new DatabaseRowInitializeNew())->addData($input);
    }

    #[Test]
    public function addDataDoesUsePageTsValueForPidIfRecordIsInlineChild(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'aTable',
            'vanillaUid' => 23,
            'neighborRow' => null,
            'inlineChildChildUid' => null,
            'databaseRow' => [],
            'pageTsConfig' => [
                'TCAdefaults.' => [
                    'aTable.' => [
                        'pid' => '42',
                    ],
                ],
            ],
            'isInlineChild' => true,
        ];
        $expected = $input;
        $expected['databaseRow']['pid'] = 42;
        self::assertSame($expected, (new DatabaseRowInitializeNew())->addData($input));
    }

    #[Test]
    public function addDataSetsUidOfParentFieldIfRecordIsInlineChild(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'aTable',
            'vanillaUid' => 23,
            'neighborRow' => null,
            'inlineChildChildUid' => null,
            'databaseRow' => [],
            'isInlineChild' => true,
            'inlineParentUid' => 42,
            'inlineParentConfig' => [
                'foreign_field' => 'theParentField',
            ],
        ];
        $expected = $input;
        $expected['databaseRow']['theParentField'] = 42;
        $expected['databaseRow']['pid'] = 23;
        self::assertSame($expected, (new DatabaseRowInitializeNew())->addData($input));
    }

    #[Test]
    public function addDataSetsTypeSpecificDefaultsFromUserTs(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'tt_content',
            'vanillaUid' => 23,
            'neighborRow' => null,
            'inlineChildChildUid' => null,
            'isInlineChild' => false,
            'databaseRow' => [],
            'defaultValues' => [
                'tt_content' => [
                    'CType' => 'textmedia',
                ],
            ],
            'userTsConfig' => [
                'TCAdefaults.' => [
                    'tt_content.' => [
                        'header_layout' => '1',
                        'header_layout.' => [
                            'types.' => [
                                'textmedia' => '3',
                            ],
                        ],
                    ],
                ],
            ],
            'processedTca' => [
                'ctrl' => [
                    'type' => 'CType',
                ],
                'columns' => [
                    'header_layout' => [],
                ],
                'types' => [
                    'textmedia' => ['showitem' => 'header_layout'],
                ],
            ],
        ];
        $expected = [
            'header_layout' => '3',  // Type-specific value overrides field-level
            'pid' => 23,
        ];
        $result = (new DatabaseRowInitializeNew())->addData($input);
        self::assertSame($expected, $result['databaseRow']);
    }

    #[Test]
    public function addDataUsesFieldLevelDefaultWhenNoTypeSpecificDefaultExists(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'tt_content',
            'vanillaUid' => 23,
            'neighborRow' => null,
            'inlineChildChildUid' => null,
            'isInlineChild' => false,
            'databaseRow' => [],
            'defaultValues' => [
                'tt_content' => [
                    'CType' => 'text',
                ],
            ],
            'userTsConfig' => [
                'TCAdefaults.' => [
                    'tt_content.' => [
                        'header_layout' => '1',
                        'header_layout.' => [
                            'types.' => [
                                'textmedia' => '3',
                            ],
                        ],
                    ],
                ],
            ],
            'processedTca' => [
                'ctrl' => [
                    'type' => 'CType',
                ],
                'columns' => [
                    'header_layout' => [],
                ],
                'types' => [
                    'text' => ['showitem' => 'header_layout'],
                    'textmedia' => ['showitem' => 'header_layout'],
                ],
            ],
        ];
        $expected = [
            'header_layout' => '1',  // Field-level default used when no type-specific exists
            'pid' => 23,
        ];
        $result = (new DatabaseRowInitializeNew())->addData($input);
        self::assertSame($expected, $result['databaseRow']);
    }

    #[Test]
    public function addDataSetsTypeSpecificDefaultsFromPageTs(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'tt_content',
            'vanillaUid' => 23,
            'neighborRow' => null,
            'inlineChildChildUid' => null,
            'isInlineChild' => false,
            'databaseRow' => [],
            'defaultValues' => [
                'tt_content' => [
                    'CType' => 'textmedia',
                ],
            ],
            'pageTsConfig' => [
                'TCAdefaults.' => [
                    'tt_content.' => [
                        'header_layout' => '1',
                        'header_layout.' => [
                            'types.' => [
                                'textmedia' => '3',
                            ],
                        ],
                    ],
                ],
            ],
            'processedTca' => [
                'ctrl' => [
                    'type' => 'CType',
                ],
                'columns' => [
                    'header_layout' => [],
                ],
                'types' => [
                    'textmedia' => ['showitem' => 'header_layout'],
                ],
            ],
        ];
        $expected = [
            'header_layout' => '3',  // Type-specific value overrides field-level
            'pid' => 23,
        ];
        $result = (new DatabaseRowInitializeNew())->addData($input);
        self::assertSame($expected, $result['databaseRow']);
    }

    #[Test]
    public function addDataTypeSpecificPageTsOverridesUserTs(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'tt_content',
            'vanillaUid' => 23,
            'recordTypeValue' => 'textmedia',
            'neighborRow' => null,
            'inlineChildChildUid' => null,
            'isInlineChild' => false,
            'databaseRow' => [],
            'userTsConfig' => [
                'TCAdefaults.' => [
                    'tt_content.' => [
                        'header_layout' => '1',
                        'header_layout.' => [
                            'types.' => [
                                'textmedia' => '2',
                            ],
                        ],
                    ],
                ],
            ],
            'pageTsConfig' => [
                'TCAdefaults.' => [
                    'tt_content.' => [
                        'header_layout' => '0',
                        'header_layout.' => [
                            'types.' => [
                                'textmedia' => '3',
                            ],
                        ],
                    ],
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'header_layout' => [],
                ],
            ],
        ];
        $expected = [
            'header_layout' => '3',  // Page TS type-specific value takes precedence
            'pid' => 23,
        ];
        $result = (new DatabaseRowInitializeNew())->addData($input);
        self::assertSame($expected, $result['databaseRow']);
    }

    #[Test]
    public function addDataHandlesMultipleFieldsWithTypeSpecificDefaults(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'tt_content',
            'vanillaUid' => 23,
            'recordTypeValue' => 'textmedia',
            'neighborRow' => null,
            'inlineChildChildUid' => null,
            'isInlineChild' => false,
            'databaseRow' => [],
            'pageTsConfig' => [
                'TCAdefaults.' => [
                    'tt_content.' => [
                        'header_layout' => '1',
                        'header_layout.' => [
                            'types.' => [
                                'textmedia' => '3',
                            ],
                        ],
                        'frame_class' => 'default',
                        'frame_class.' => [
                            'types.' => [
                                'textmedia' => 'ruler-before',
                            ],
                        ],
                        'space_before_class' => 'none',  // No type-specific override
                    ],
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'header_layout' => [],
                    'frame_class' => [],
                    'space_before_class' => [],
                ],
            ],
        ];
        $expected = [
            'header_layout' => '3',
            'frame_class' => 'ruler-before',
            'space_before_class' => 'none',
            'pid' => 23,
        ];
        $result = (new DatabaseRowInitializeNew())->addData($input);
        self::assertSame($expected, $result['databaseRow']);
    }

    #[Test]
    public function addDataIgnoresTypeSpecificDefaultsWhenColumnNotInTca(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'tt_content',
            'vanillaUid' => 23,
            'recordTypeValue' => 'textmedia',
            'neighborRow' => null,
            'inlineChildChildUid' => null,
            'isInlineChild' => false,
            'databaseRow' => [],
            'pageTsConfig' => [
                'TCAdefaults.' => [
                    'tt_content.' => [
                        'header_layout.' => [
                            'types.' => [
                                'textmedia' => '3',
                            ],
                        ],
                    ],
                ],
            ],
            'processedTca' => [
                'columns' => [],  // header_layout not defined in TCA
            ],
        ];
        $expected = [
            'pid' => 23,
        ];
        $result = (new DatabaseRowInitializeNew())->addData($input);
        self::assertSame($expected, $result['databaseRow']);
    }

    #[Test]
    public function addDataHandlesOnlyTypeSpecificDefaultsWithoutFieldLevel(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'tt_content',
            'vanillaUid' => 23,
            'recordTypeValue' => 'textmedia',
            'neighborRow' => null,
            'inlineChildChildUid' => null,
            'isInlineChild' => false,
            'databaseRow' => [],
            'pageTsConfig' => [
                'TCAdefaults.' => [
                    'tt_content.' => [
                        'header_layout.' => [
                            'types.' => [
                                'textmedia' => '3',
                            ],
                        ],
                    ],
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'header_layout' => [],
                ],
            ],
        ];
        $expected = [
            'header_layout' => '3',
            'pid' => 23,
        ];
        $result = (new DatabaseRowInitializeNew())->addData($input);
        self::assertSame($expected, $result['databaseRow']);
    }

    #[Test]
    public function addDataSetsTypeSpecificDefaultsWithComplexConfiguration(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'tt_content',
            'vanillaUid' => 23,
            'recordTypeValue' => 'textmedia',
            'neighborRow' => null,
            'inlineChildChildUid' => null,
            'isInlineChild' => false,
            'databaseRow' => [],
            'pageTsConfig' => [
                'TCAdefaults.' => [
                    'tt_content.' => [
                        'header_layout' => '1',
                        'header_layout.' => [
                            'types.' => [
                                'textmedia' => '3',
                                'image' => '2',
                                'text' => '0',
                            ],
                        ],
                        'frame_class' => 'default',
                        'frame_class.' => [
                            'types.' => [
                                'textmedia' => 'ruler-before',
                                'image' => 'ruler-after',
                            ],
                        ],
                        'space_before_class' => 'none',  // No type-specific override
                    ],
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'header_layout' => [],
                    'frame_class' => [],
                    'space_before_class' => [],
                ],
            ],
        ];
        $expected = [
            'header_layout' => '3',  // Type-specific for textmedia
            'frame_class' => 'ruler-before',  // Type-specific for textmedia
            'space_before_class' => 'none',  // Field-level default
            'pid' => 23,
        ];
        $result = (new DatabaseRowInitializeNew())->addData($input);
        self::assertSame($expected, $result['databaseRow']);
    }

    #[Test]
    public function addDataTypeSpecificDefaultsOverrideUserTsDefaults(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'tt_content',
            'vanillaUid' => 23,
            'recordTypeValue' => 'textmedia',
            'neighborRow' => null,
            'inlineChildChildUid' => null,
            'isInlineChild' => false,
            'databaseRow' => [],
            'userTsConfig' => [
                'TCAdefaults.' => [
                    'tt_content.' => [
                        'header_layout' => '0',
                        'header_layout.' => [
                            'types.' => [
                                'textmedia' => '1',
                            ],
                        ],
                    ],
                ],
            ],
            'pageTsConfig' => [
                'TCAdefaults.' => [
                    'tt_content.' => [
                        'header_layout' => '2',
                        'header_layout.' => [
                            'types.' => [
                                'textmedia' => '3',
                            ],
                        ],
                    ],
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'header_layout' => [],
                ],
            ],
        ];
        $expected = [
            'header_layout' => '3',  // Page TS type-specific should win
            'pid' => 23,
        ];
        $result = (new DatabaseRowInitializeNew())->addData($input);
        self::assertSame($expected, $result['databaseRow']);
    }

    #[Test]
    public function addDataIgnoresTypeSpecificDefaultsForNonExistentType(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'tt_content',
            'vanillaUid' => 23,
            'recordTypeValue' => 'nonexistent_type',
            'neighborRow' => null,
            'inlineChildChildUid' => null,
            'isInlineChild' => false,
            'databaseRow' => [],
            'pageTsConfig' => [
                'TCAdefaults.' => [
                    'tt_content.' => [
                        'header_layout' => '1',
                        'header_layout.' => [
                            'types.' => [
                                'textmedia' => '3',
                            ],
                        ],
                    ],
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'header_layout' => [],
                ],
            ],
        ];
        $expected = [
            'header_layout' => '1',  // Should fall back to field-level default
            'pid' => 23,
        ];
        $result = (new DatabaseRowInitializeNew())->addData($input);
        self::assertSame($expected, $result['databaseRow']);
    }

    #[Test]
    public function addDataHandlesOnlyTypeSpecificDefaultsWithoutFieldLevelDefaults(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'tt_content',
            'vanillaUid' => 23,
            'recordTypeValue' => 'textmedia',
            'neighborRow' => null,
            'inlineChildChildUid' => null,
            'isInlineChild' => false,
            'databaseRow' => [],
            'pageTsConfig' => [
                'TCAdefaults.' => [
                    'tt_content.' => [
                        'header_layout.' => [
                            'types.' => [
                                'textmedia' => '3',
                            ],
                        ],
                    ],
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'header_layout' => [],
                ],
            ],
        ];
        $expected = [
            'header_layout' => '3',
            'pid' => 23,
        ];
        $result = (new DatabaseRowInitializeNew())->addData($input);
        self::assertSame($expected, $result['databaseRow']);
    }

    #[Test]
    public function addDataHandlesEmptyRecordTypeValue(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'tt_content',
            'vanillaUid' => 23,
            'recordTypeValue' => '',
            'neighborRow' => null,
            'inlineChildChildUid' => null,
            'isInlineChild' => false,
            'databaseRow' => [],
            'pageTsConfig' => [
                'TCAdefaults.' => [
                    'tt_content.' => [
                        'header_layout' => '1',
                        'header_layout.' => [
                            'types.' => [
                                'textmedia' => '3',
                            ],
                        ],
                    ],
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'header_layout' => [],
                ],
            ],
        ];
        $expected = [
            'header_layout' => '1',  // Should use field-level default when no type
            'pid' => 23,
        ];
        $result = (new DatabaseRowInitializeNew())->addData($input);
        self::assertSame($expected, $result['databaseRow']);
    }

    #[Test]
    public function addDataHandlesMissingRecordTypeValue(): void
    {
        $input = [
            'command' => 'new',
            'tableName' => 'tt_content',
            'vanillaUid' => 23,
            // recordTypeValue is missing and no defaultValues - should fall back to type '0'
            'neighborRow' => null,
            'inlineChildChildUid' => null,
            'isInlineChild' => false,
            'databaseRow' => [],
            'pageTsConfig' => [
                'TCAdefaults.' => [
                    'tt_content.' => [
                        'header_layout' => '1',
                        'header_layout.' => [
                            'types.' => [
                                'textmedia' => '3',
                            ],
                        ],
                    ],
                ],
            ],
            'processedTca' => [
                'ctrl' => [
                    'type' => 'CType',
                ],
                'columns' => [
                    'header_layout' => [],
                ],
                'types' => [
                    '0' => ['showitem' => 'header_layout'],
                    'textmedia' => ['showitem' => 'header_layout'],
                ],
            ],
        ];
        $expected = [
            'header_layout' => '1',  // Should use field-level default when no type
            'pid' => 23,
        ];
        $result = (new DatabaseRowInitializeNew())->addData($input);
        self::assertSame($expected, $result['databaseRow']);
    }

    #[Test]
    public function mergeTypeSpecificTcaDefaultsHandlesComplexConfigurations(): void
    {
        $provider = new DatabaseRowInitializeNew();
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('mergeTypeSpecificTcaDefaults');

        $tcaDefaults = [
            'header_layout' => '1',  // Simple field-level default
            'header_layout.' => [
                'types.' => [
                    'textmedia' => '3',
                    'image' => '2',
                ],
            ],
            'frame_class.' => [  // Only type-specific, no field-level
                'types.' => [
                    'textmedia' => 'ruler-before',
                ],
            ],
            'space_before_class' => 'none',  // Only field-level
            'other_config.' => [  // Configuration without types
                'some_option' => 'value',
            ],
        ];

        $result = $method->invoke($provider, $tcaDefaults, 'textmedia');

        $expected = [
            'header_layout' => '3',  // Type-specific override
            'frame_class' => 'ruler-before',  // Type-specific only
            'space_before_class' => 'none',  // Field-level only
            'other_config' => 'value',  // Configuration without types should extract first value
        ];

        self::assertEquals($expected, $result);
    }
}
