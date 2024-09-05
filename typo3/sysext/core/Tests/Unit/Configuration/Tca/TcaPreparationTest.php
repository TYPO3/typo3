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
use TYPO3\CMS\Core\Configuration\Tca\TcaPreparation;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TcaPreparationTest extends UnitTestCase
{
    public static function configureCategoryRelationsDataProvider(): \Generator
    {
        yield 'No category field' => [
            [
                'aTable' => [
                    'columns' => [
                        'foo' => [
                            'config' => [
                                'type' => 'select',
                                'foreign_table' => 'sys_category',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'aTable' => [
                    'columns' => [
                        'foo' => [
                            'config' => [
                                'type' => 'select',
                                'foreign_table' => 'sys_category',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield 'category field without relationship given (falls back to manyToMany)' => [
            [
                'aTable' => [
                    'columns' => [
                        'aField' => [
                            'config' => [
                                'type' => 'category',
                                'minitems' => 1,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'aTable' => [
                    'columns' => [
                        'aField' => [
                            'config' => [
                                'type' => 'category',
                                'minitems' => 1,
                                'size' => 20,
                                'default' => 0,
                                'foreign_table' => 'sys_category',
                                'foreign_table_where' => ' AND {#sys_category}.{#sys_language_uid} IN (-1, 0)',
                                'relationship' => 'manyToMany',
                                'maxitems' => 99999,
                                'MM' => 'sys_category_record_mm',
                                'MM_opposite_field' => 'items',
                                'MM_match_fields' => [
                                    'tablenames' => 'aTable',
                                    'fieldname' => 'aField',
                                ],
                            ],
                            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_category.categories',
                            'exclude' => true,
                        ],
                    ],
                ],
                'sys_category' => [
                    'columns' => [
                        'items' => [
                            'config' => [
                                'MM_oppositeUsage' => [
                                    'aTable' => [
                                        'aField',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield 'category field with oneToOne relationship and custom foreign_table_* options' => [
            [
                'aTable' => [
                    'columns' => [
                        'aField' => [
                            'config' => [
                                'type' => 'category',
                                'foreign_table' => 'some_table',
                                'foreign_table_where' => ' AND sys_category.pid IN (###PAGE_TSCONFIG_IDLIST###)',
                                'relationship' => 'oneToOne',
                                'minitems' => 1,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'aTable' => [
                    'columns' => [
                        'aField' => [
                            'config' => [
                                'type' => 'category',
                                'relationship' => 'oneToOne',
                                'minitems' => 1,
                                'size' => 20,
                                'default' => 0,
                                'foreign_table' => 'sys_category',
                                'foreign_table_where' => ' AND sys_category.pid IN (###PAGE_TSCONFIG_IDLIST###)',
                                'maxitems' => 1,
                            ],
                            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_category.categories',
                        ],
                    ],
                ],
            ],
        ];
        yield 'categoryField with oneToMany relationship' => [
            [
                'aTable' => [
                    'columns' => [
                        'aField' => [
                            'config' => [
                                'type' => 'category',
                                'relationship' => 'oneToMany',
                                'size' => 123,
                                'maxitems' => 0,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'aTable' => [
                    'columns' => [
                        'aField' => [
                            'config' => [
                                'type' => 'category',
                                'relationship' => 'oneToMany',
                                'size' => 123,
                                'foreign_table' => 'sys_category',
                                'foreign_table_where' => ' AND {#sys_category}.{#sys_language_uid} IN (-1, 0)',
                                'maxitems' => 99999,
                            ],
                            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_category.categories',
                        ],
                    ],
                ],
            ],
        ];
        yield 'categoryField with manyToMany relationship' => [
            [
                'aTable' => [
                    'columns' => [
                        'aField' => [
                            'exclude' => false,
                            'config' => [
                                'type' => 'category',
                                'relationship' => 'manyToMany',
                                'default' => 123,
                                'maxitems' => 123,
                                'foreign_table' => 'will_be_overwritten',
                                'MM' => 'will_be_overwritten',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'aTable' => [
                    'columns' => [
                        'aField' => [
                            'config' => [
                                'type' => 'category',
                                'relationship' => 'manyToMany',
                                'size' => 20,
                                'default' => 123,
                                'foreign_table' => 'sys_category',
                                'foreign_table_where' => ' AND {#sys_category}.{#sys_language_uid} IN (-1, 0)',
                                'maxitems' => 123,
                                'MM' => 'sys_category_record_mm',
                                'MM_opposite_field' => 'items',
                                'MM_match_fields' => [
                                    'tablenames' => 'aTable',
                                    'fieldname' => 'aField',
                                ],
                            ],
                            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_category.categories',
                            'exclude' => false,
                        ],
                    ],
                ],
                'sys_category' => [
                    'columns' => [
                        'items' => [
                            'config' => [
                                'MM_oppositeUsage' => [
                                    'aTable' => [
                                        'aField',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('configureCategoryRelationsDataProvider')]
    #[Test]
    public function configureCategoryRelations(array $input, array $expected): void
    {
        self::assertEquals($expected, (new TcaPreparation())->prepare($input));
    }

    public static function configureCategoryRelationsThrowsExceptionOnInvalidMaxitemsDataProvider(): \Generator
    {
        yield 'oneToOne relationship with maxitems=2' => [
            [
                'aTable' => [
                    'columns' => [
                        'foo' => [
                            'config' => [
                                'type' => 'category',
                                'relationship' => 'oneToOne',
                                'maxitems' => 2,
                            ],
                        ],
                    ],
                ],
            ],
            1627335016,
        ];
        yield 'oneToMany relationship with maxitems=1' => [
            [
                'aTable' => [
                    'columns' => [
                        'foo' => [
                            'config' => [
                                'type' => 'category',
                                'relationship' => 'oneToMany',
                                'maxitems' => 1,
                            ],
                        ],
                    ],
                ],
            ],
            1627335017,
        ];
    }

    #[DataProvider('configureCategoryRelationsThrowsExceptionOnInvalidMaxitemsDataProvider')]
    #[Test]
    public function configureCategoryRelationsThrowsExceptionOnInvalidMaxitems(array $input, int $exceptionCode): void
    {
        $this->expectExceptionCode($exceptionCode);
        $this->expectException(\RuntimeException::class);
        (new TcaPreparation())->prepare($input);
    }

    #[Test]
    public function configureCategoryRelationsThrowsExceptionOnInvalidRelationship(): void
    {
        $this->expectExceptionCode(1627898896);
        $this->expectException(\RuntimeException::class);
        (new TcaPreparation())->prepare([
            'aTable' => [
                'columns' => [
                    'foo' => [
                        'config' => [
                            'type' => 'category',
                            'relationship' => 'invalid',
                        ],
                    ],
                ],
            ],
        ]);
    }

    public static function configureFileReferencesDataProvider(): \Generator
    {
        yield 'allowed and disallowed in config' => [
            [
                'aTable' => [
                    'columns' => [
                        'foo' => [
                            'config' => [
                                'type' => 'file',
                                'allowed' => ['foo', 'bar'],
                                'disallowed' => ['baz', 'bencer'],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'aTable' => [
                    'columns' => [
                        'foo' => [
                            'config' => [
                                'type' => 'file',
                                'foreign_table' => 'sys_file_reference',
                                'foreign_field' => 'uid_foreign',
                                'foreign_sortby' => 'sorting_foreign',
                                'foreign_table_field' => 'tablenames',
                                'foreign_match_fields' => [
                                    'fieldname' => 'foo',
                                    'tablenames' => 'aTable',
                                ],
                                'foreign_label' => 'uid_local',
                                'foreign_selector' => 'uid_local',
                                'allowed' => 'foo,bar',
                                'disallowed' => 'baz,bencer',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield 'allowed and disallowed in config/overrideChildTca' => [
            [
                'aTable' => [
                    'columns' => [
                        'foo' => [
                            'config' => [
                                'type' => 'file',
                                'overrideChildTca' => [
                                    'columns' => [
                                        'aField' => [
                                            'config' => [
                                                'allowed' => ['foo', 'bar'],
                                                'disallowed' => ['baz', 'bencer'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'aTable' => [
                    'columns' => [
                        'foo' => [
                            'config' => [
                                'type' => 'file',
                                'foreign_table' => 'sys_file_reference',
                                'foreign_field' => 'uid_foreign',
                                'foreign_sortby' => 'sorting_foreign',
                                'foreign_table_field' => 'tablenames',
                                'foreign_match_fields' => [
                                    'fieldname' => 'foo',
                                    'tablenames' => 'aTable',
                                ],
                                'foreign_label' => 'uid_local',
                                'foreign_selector' => 'uid_local',
                                'overrideChildTca' => [
                                    'columns' => [
                                        'aField' => [
                                            'config' => [
                                                'allowed' => 'foo,bar',
                                                'disallowed' => 'baz,bencer',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield 'allowed and disallowed in columnsOverride/config' => [
            [
                'aTable' => [
                    'columns' => [
                        'foo' => [
                            'config' => [
                                'type' => 'file',
                            ],
                        ],
                    ],
                    'types' => [
                        'aType' => [
                            'columnsOverrides' => [
                                'aField' => [
                                    'config' => [
                                        'allowed' => ['foo', 'bar'],
                                        'disallowed' => ['baz', 'bencer'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'aTable' => [
                    'columns' => [
                        'foo' => [
                            'config' => [
                                'type' => 'file',
                                'foreign_table' => 'sys_file_reference',
                                'foreign_field' => 'uid_foreign',
                                'foreign_sortby' => 'sorting_foreign',
                                'foreign_table_field' => 'tablenames',
                                'foreign_match_fields' => [
                                    'fieldname' => 'foo',
                                    'tablenames' => 'aTable',
                                ],
                                'foreign_label' => 'uid_local',
                                'foreign_selector' => 'uid_local',
                            ],
                        ],
                    ],
                    'types' => [
                        'aType' => [
                            'columnsOverrides' => [
                                'aField' => [
                                    'config' => [
                                        'allowed' => 'foo,bar',
                                        'disallowed' => 'baz,bencer',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield 'columnsOverride without config' => [
            [
                'aTable' => [
                    'columns' => [
                        'foo' => [
                            'config' => [
                                'type' => 'file',
                            ],
                        ],
                    ],
                    'types' => [
                        'aType' => [
                            'columnsOverrides' => [
                                'aField' => [],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'aTable' => [
                    'columns' => [
                        'foo' => [
                            'config' => [
                                'type' => 'file',
                                'foreign_table' => 'sys_file_reference',
                                'foreign_field' => 'uid_foreign',
                                'foreign_sortby' => 'sorting_foreign',
                                'foreign_table_field' => 'tablenames',
                                'foreign_match_fields' => [
                                    'fieldname' => 'foo',
                                    'tablenames' => 'aTable',
                                ],
                                'foreign_label' => 'uid_local',
                                'foreign_selector' => 'uid_local',
                            ],
                        ],
                    ],
                    'types' => [
                        'aType' => [
                            'columnsOverrides' => [
                                'aField' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield 'allowed and disallowed in columnsOverride/overrideChildTca/columns' => [
            [
                'aTable' => [
                    'columns' => [
                        'foo' => [
                            'config' => [
                                'type' => 'file',
                            ],
                        ],
                    ],
                    'types' => [
                        'aType' => [
                            'columnsOverrides' => [
                                'aField' => [
                                    'config' => [
                                        'overrideChildTca' => [
                                            'columns' => [
                                                'aField' => [
                                                    'config' => [
                                                        'allowed' => ['foo', 'bar'],
                                                        'disallowed' => ['baz', 'bencer'],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'aTable' => [
                    'columns' => [
                        'foo' => [
                            'config' => [
                                'type' => 'file',
                                'foreign_table' => 'sys_file_reference',
                                'foreign_field' => 'uid_foreign',
                                'foreign_sortby' => 'sorting_foreign',
                                'foreign_table_field' => 'tablenames',
                                'foreign_match_fields' => [
                                    'fieldname' => 'foo',
                                    'tablenames' => 'aTable',
                                ],
                                'foreign_label' => 'uid_local',
                                'foreign_selector' => 'uid_local',
                            ],
                        ],
                    ],
                    'types' => [
                        'aType' => [
                            'columnsOverrides' => [
                                'aField' => [
                                    'config' => [
                                        'overrideChildTca' => [
                                            'columns' => [
                                                'aField' => [
                                                    'config' => [
                                                        'allowed' => 'foo,bar',
                                                        'disallowed' => 'baz,bencer',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield 'allowed and disallowed in columnsOverride/overrideChildTca/types' => [
            [
                'aTable' => [
                    'columns' => [
                        'foo' => [
                            'config' => [
                                'type' => 'file',
                            ],
                        ],
                    ],
                    'types' => [
                        'aType' => [
                            'columnsOverrides' => [
                                'aField' => [
                                    'config' => [
                                        'overrideChildTca' => [
                                            'types' => [
                                                'aType' => [
                                                    'config' => [
                                                        'allowed' => ['foo', 'bar'],
                                                        'disallowed' => ['baz', 'bencer'],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'aTable' => [
                    'columns' => [
                        'foo' => [
                            'config' => [
                                'type' => 'file',
                                'foreign_table' => 'sys_file_reference',
                                'foreign_field' => 'uid_foreign',
                                'foreign_sortby' => 'sorting_foreign',
                                'foreign_table_field' => 'tablenames',
                                'foreign_match_fields' => [
                                    'fieldname' => 'foo',
                                    'tablenames' => 'aTable',
                                ],
                                'foreign_label' => 'uid_local',
                                'foreign_selector' => 'uid_local',
                            ],
                        ],
                    ],
                    'types' => [
                        'aType' => [
                            'columnsOverrides' => [
                                'aField' => [
                                    'config' => [
                                        'overrideChildTca' => [
                                            'types' => [
                                                'aType' => [
                                                    'config' => [
                                                        'allowed' => 'foo,bar',
                                                        'disallowed' => 'baz,bencer',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('configureFileReferencesDataProvider')]
    #[Test]
    public function configureFileReferences(array $input, array $expected): void
    {
        self::assertEquals($expected, (new TcaPreparation())->prepare($input));
    }

    #[Test]
    public function configureEmailSetsSoftref(): void
    {
        $tca = [
            'aTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'email',
                        ],
                    ],
                ],
            ],
        ];
        $expected = $tca;
        $expected['aTable']['columns']['aField']['config']['softref'] = 'email[subst]';
        self::assertEquals($expected, (new TcaPreparation())->prepare($tca));
    }

    #[Test]
    public function configureEmailSetsSoftrefOverridesExisting(): void
    {
        $tca = [
            'aTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'email',
                            'softref' => 'isOverridden',
                        ],
                    ],
                ],
            ],
        ];
        $expected = $tca;
        $expected['aTable']['columns']['aField']['config']['softref'] = 'email[subst]';
        self::assertEquals($expected, (new TcaPreparation())->prepare($tca));
    }

    #[Test]
    public function configureLinkSetsSoftref(): void
    {
        $tca = [
            'aTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'link',
                        ],
                    ],
                ],
            ],
        ];
        $expected = $tca;
        $expected['aTable']['columns']['aField']['config']['softref'] = 'typolink';
        self::assertEquals($expected, (new TcaPreparation())->prepare($tca));
    }

    #[Test]
    public function configureLinkSetsSoftrefOverridesExisting(): void
    {
        $tca = [
            'aTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'link',
                            'softref' => 'isOverridden',
                        ],
                    ],
                ],
            ],
        ];
        $expected = $tca;
        $expected['aTable']['columns']['aField']['config']['softref'] = 'typolink';
        self::assertEquals($expected, (new TcaPreparation())->prepare($tca));
    }

    #[Test]
    public function prepareFileExtensionsReplacesPlaceholders(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] = 'jpg,png';
        $subject = new TcaPreparation();
        $subjectMethodReflection = new \ReflectionMethod($subject, 'prepareFileExtensions');
        self::assertEquals('jpg,png,gif', $subjectMethodReflection->invoke($subject, ['common-image-types', 'gif']));
    }

    #[Test]
    public function prepareFileExtensionsRemovesDuplicates(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] = 'jpg,png';
        $subject = new TcaPreparation();
        $subjectMethodReflection = new \ReflectionMethod($subject, 'prepareFileExtensions');
        self::assertEquals('jpg,png,gif', $subjectMethodReflection->invoke($subject, ['common-image-types,jpg,gif']));
    }

    public static function prepareSelectSingleAddsRelationshipDataProvider(): iterable
    {
        yield [
            [
                'MM' => 'select_table_mm',
            ],
            'manyToMany',
        ];
        yield [
            [],
            'manyToOne',
        ];
    }

    #[DataProvider('prepareSelectSingleAddsRelationshipDataProvider')]
    #[Test]
    public function prepareSelectSingleAddsRelationship(array $configuration, $expectedRelation): void
    {
        $subject = (new TcaPreparation())->prepare(['foo' => ['columns' => ['select' => ['config' => array_merge(['type' => 'select', 'renderType' => 'selectSingle', 'foreign_table' => 'tx_myextension_bar'], $configuration)]]]]);
        self::assertEquals($expectedRelation, $subject['foo']['columns']['select']['config']['relationship']);
    }

    #[Test]
    public function prepareSelectSingleDoesNotOverwriteRelationship(): void
    {
        $subject = (new TcaPreparation())->prepare(['foo' => ['columns' => ['select' => ['config' => ['type' => 'select', 'renderType' => 'selectSingle', 'foreign_table' => 'tx_myextension_bar', 'relationship' => 'oneToOne']]]]]);
        self::assertEquals('oneToOne', $subject['foo']['columns']['select']['config']['relationship']);
    }

    #[Test]
    public function prepareSelectSingleDoesNotAddRelationshipOnMissingForeignTable(): void
    {
        $subject = (new TcaPreparation())->prepare(['foo' => ['columns' => ['select' => ['config' => ['type' => 'select', 'renderType' => 'selectSingle']]]]]);
        self::assertNull($subject['foo']['columns']['select']['config']['relationship'] ?? null);
    }

    public static function prepareRelationshipToOneAddsMaxItemsDataProvider(): iterable
    {
        yield ['select'];
        yield ['inline'];
        yield ['file'];
        yield ['folder'];
        yield ['group'];
        yield ['input', 0];
    }

    #[DataProvider('prepareRelationshipToOneAddsMaxItemsDataProvider')]
    #[Test]
    public function prepareRelationshipToOneAddsMaxItems(string $type, int $maxitems = 1): void
    {
        $subject = (new TcaPreparation())->prepare(['foo' => ['columns' => ['relation' => ['config' => ['type' => $type, 'relationship' => 'oneToOne']]]]]);
        self::assertEquals($maxitems, $subject['foo']['columns']['relation']['config']['maxitems'] ?? 0);
        $subject = (new TcaPreparation())->prepare(['foo' => ['columns' => ['relation' => ['config' => ['type' => $type, 'relationship' => 'manyToOne']]]]]);
        self::assertEquals($maxitems, $subject['foo']['columns']['relation']['config']['maxitems'] ?? 0);
        $subject = (new TcaPreparation())->prepare(['foo' => ['columns' => ['relation' => ['config' => ['type' => $type, 'relationship' => 'manyToMany']]]]]);
        self::assertEquals(0, $subject['foo']['columns']['relation']['config']['maxitems'] ?? 0);
    }

    #[Test]
    public function addSystemFieldsWorksForTtContentOnly(): void
    {
        $tca = ['foo' => $this->getTtContentTca()];
        $subject = (new TcaPreparation())->prepare($tca);
        self::assertEquals($tca, $subject);
    }

    #[Test]
    public function addSystemFieldsWorksForTtContentWithRecordTypeFieldOnly(): void
    {
        $tca = ['tt_content' => $this->getTtContentTca()];
        unset($tca['tt_content']['ctrl']['type']);
        $subject = (new TcaPreparation())->prepare($tca);
        self::assertEquals($tca, $subject);
    }

    public static function addSystemFieldsWorksForTtContentDataProvider(): iterable
    {
        yield 'Default behaviour' => [
            [],
            '
                    --palette--;;general,
                    --palette--;;headers,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended
            ',
        ];
        yield 'General palette with label already exists' => [
            [
                'types' => [
                    'header' => [
                        'showitem' => '
                            --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
                            --palette--;;headers,
                        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
                            --palette--;;frames,
                            --palette--;;appearanceLinks,
                        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                            categories,
                        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
                    ',
                    ],
                ],
            ],
            '
                    --palette--;;general,
                    --palette--;;headers,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended
            ',
        ];
        yield 'Missing general palette' => [
            [
                'palettes' => [
                    'general' => '__UNSET',
                ],
            ],
            '
                    CType;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType_formlabel,
                    colPos;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:colPos_formlabel,
                    --palette--;;headers,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended
            ',
        ];
        yield 'General palette with wrong field names' => [
            [
                'palettes' => [
                    'general' => [
                        'showitem' => '
                            CType123;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType_formlabel,
                            foocolPos;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:colPos_formlabel,
                        ',
                    ],
                ],
            ],
            '
                    CType;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType_formlabel,
                    colPos;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:colPos_formlabel,
                    --palette--;;headers,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended
            ',
        ];
        yield 'LanguageField not set' => [
            [
                'ctrl' => [
                    'languageField' => '__UNSET',
                ],
            ],
            '
                    --palette--;;general,
                    --palette--;;headers,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended
            ',
        ];
        yield 'language palette defines different fields' => [
            [
                'palettes' => [
                    'language' => [
                        'showitem' => 'some_unintended_field',
                    ],
                ],
            ],
            '
                    --palette--;;general,
                    --palette--;;headers,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    sys_language_uid,
                    l18n_parent,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended
            ',
        ];
        yield 'unusual language field used and transOrigPointerField not set' => [
            [
                'ctrl' => [
                    'languageField' => 'custom_language_field',
                    'transOrigPointerField' => '__UNSET',
                ],
                'palettes' => [
                    'language' => [
                        'showitem' => 'some_unintended_field',
                    ],
                ],
            ],
            '
                    --palette--;;general,
                    --palette--;;headers,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    custom_language_field,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended
            ',
        ];
        yield 'additional fields are kept in the extended tab at the end' => [
            [
                'types' => [
                    'header' => [
                        'showitem' => '
                                --palette--;;headers,
                            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
                                --palette--;;frames,
                                --palette--;;appearanceLinks,
                            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                                categories,
                            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
                                custom_field,
                                --custom_field;LLL:EXT:extension/Resources/Private/Language/locallang.xlf:custom_field_label,
                        ',
                    ],
                ],
            ],
            '
                    --palette--;;general,
                    --palette--;;headers,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
                    custom_field,
                    --custom_field;LLL:EXT:extension/Resources/Private/Language/locallang.xlf:custom_field_label
            ',
        ];
        yield 'category tab is ommited' => [
            [
                'types' => [
                    'header' => [
                        'showitem' => '
                                --palette--;;headers,
                            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
                                --palette--;;frames,
                                --palette--;;appearanceLinks,
                            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
                        ',
                    ],
                ],
            ],
            '
                    --palette--;;general,
                    --palette--;;headers,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended
            ',
        ];
        yield 'duplicate system fields and palettes are removed' => [
            [
                'types' => [
                    'header' => [
                        'showitem' => '
                                --palette--;;headers,
                            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
                                --palette--;;frames,
                                --palette--;;appearanceLinks,
                            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                                categories,
                            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
                                --palette--;;general,
                                colPos,
                                sys_language_uid,

                        ',
                    ],
                ],
            ],
            '
                    --palette--;;general,
                    --palette--;;headers,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended
            ',
        ];
        yield 'access tab and palette are added for editlock field only' => [
            [
                'ctrl' => [
                    'enablecolumns' => '__UNSET',
                ],
            ],
            '
                    --palette--;;general,
                    --palette--;;headers,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended
            ',
        ];
        yield 'starttime field is added only' => [
            [
                'ctrl' => [
                    'editlock' => '__UNSET',
                    'enablecolumns' => [
                        'disabled' => '__UNSET',
                        'endtime' => '__UNSET',
                        'fe_group' => '__UNSET',
                    ],
                ],
                'palettes' => [
                    'access' => '__UNSET',
                ],
            ],
            '
                    --palette--;;general,
                    --palette--;;headers,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    starttime,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended
            ',
        ];
        yield 'access tab adds hidden palette only' => [
            [
                'ctrl' => [
                    'editlock' => '__UNSET',
                    'enablecolumns' => [
                        'starttime' => '__UNSET',
                        'endtime' => '__UNSET',
                        'fe_group' => '__UNSET',
                    ],
                ],
            ],
            '
                    --palette--;;general,
                    --palette--;;headers,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended
            ',
        ];
        yield 'notes tab is omitted' => [
            [
                'ctrl' => [
                    'descriptionColumn' => '__UNSET',
                ],
            ],
            '
                    --palette--;;general,
                    --palette--;;headers,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended
            ',
        ];
        yield 'custom label is removed' => [
            [
                'types' => [
                    'header' => [
                        'showitem' => '
                                --palette--;;headers,
                            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
                                --palette--;;frames,
                                --palette--;;appearanceLinks,
                            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                                categories,
                            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                                rowDescription;LLL:EXT:extension/Resources/Private/Language/locallang.xlf:customLabel,
                            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,

                        ',
                    ],
                ],
            ],
            '
                    --palette--;;general,
                    --palette--;;headers,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended
            ',
        ];
        yield 'custom first tab is kept' => [
            [
                'types' => [
                    'header' => [
                        'showitem' => '
                            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:custom-label,
                                --palette--;;headers,
                                bodytext,
                                --palette--;;custom-palette
                            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
                                --palette--;;frames,
                                --palette--;;appearanceLinks,
                            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                                categories,
                            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
                                custom_field,
                                --custom_field;LLL:EXT:extension/Resources/Private/Language/locallang.xlf:custom_field_label,
                        ',
                    ],
                ],
            ],
            '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:custom-label,
                    --palette--;;general,
                    --palette--;;headers,
                    bodytext,
                    --palette--;;custom-palette
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
                    custom_field,
                    --custom_field;LLL:EXT:extension/Resources/Private/Language/locallang.xlf:custom_field_label
            ',
        ];
        yield 'custom first tab is kept and first fields are added manually' => [
            [
                'ctrl' => [
                    'type' => 'recordType',
                ],
                'types' => [
                    'header' => [
                        'showitem' => '
                            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:custom-label,
                                --palette--;;headers,
                                bodytext,
                                --palette--;;custom-palette,
                            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
                                --palette--;;frames,
                                --palette--;;appearanceLinks,
                            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                                categories,
                            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
                                custom_field,
                                --custom_field;LLL:EXT:extension/Resources/Private/Language/locallang.xlf:custom_field_label,
                        ',
                    ],
                ],
                'palettes' => [
                    'general' => '__UNSET',
                ],
            ],
            '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:custom-label,
                    recordType,
                   colPos;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:colPos_formlabel,
                    --palette--;;headers,
                    bodytext,
                    --palette--;;custom-palette,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
                    custom_field,
                    --custom_field;LLL:EXT:extension/Resources/Private/Language/locallang.xlf:custom_field_label
            ',
        ];
    }

    #[DataProvider('addSystemFieldsWorksForTtContentDataProvider')]
    #[Test]
    public function addSystemFieldsWorksForTtContent(array $overwriteConfiguration, string $expectedShowitem): void
    {
        $tca = $this->getTtContentTca();
        ArrayUtility::mergeRecursiveWithOverrule($tca, $overwriteConfiguration);

        $subject = (new TcaPreparation())->prepare(['tt_content' => $tca]);

        self::assertEquals(
            preg_replace('/\s/', '', $expectedShowitem),
            preg_replace('/\s/', '', $subject['tt_content']['types']['header']['showitem'])
        );
    }

    public static function systemFieldsAreRemovedFromCustomPalettesDataProvider(): iterable
    {
        yield 'duplicate system fields are removed from custom palettes' => [
            [
                'palettes' => [
                    'custom-palette' => [
                        'showitem' => '
                                colPos,
                                sys_language_uid,
                                custom-field
                        ',
                    ],
                ],
            ],
            'custom-field',
        ];
        yield 'duplicate system fields with field label are removed from custom palettes' => [
            [
                'palettes' => [
                    'custom-palette' => [
                        'showitem' => '
                                colPos;LLL:EXT:extension/Resources/Private/Language/locallang.xlf:colPos.label,
                                sys_language_uid,
                                custom-field
                        ',
                    ],
                ],
            ],
            'custom-field',
        ];
        yield 'duplicate system fields with unusual field name are removed from custom palettes' => [
            [
                'ctrl' => [
                    'type' => 'CType123',
                    'languageField' => 'lang123',
                ],
                'palettes' => [
                    'custom-palette' => [
                        'showitem' => '
                                CType123;LLL:EXT:extension/Resources/Private/Language/locallang.xlf:ctype.label,
                                lang123,
                                custom-field
                        ',
                    ],
                ],
            ],
            'custom-field',
        ];
    }

    #[DataProvider('systemFieldsAreRemovedFromCustomPalettesDataProvider')]
    #[Test]
    public function systemFieldsAreRemovedFromCustomPalettes(array $overwriteConfiguration, string $expectedPaletteShowitem): void
    {
        $tca = $this->getTtContentTca();
        ArrayUtility::mergeRecursiveWithOverrule($tca, $overwriteConfiguration);

        $subject = (new TcaPreparation())->prepare(['tt_content' => $tca]);

        self::assertEquals(
            preg_replace('/\s/', '', $expectedPaletteShowitem),
            preg_replace('/\s/', '', $subject['tt_content']['palettes']['custom-palette']['showitem'])
        );
    }

    private function getTtContentTca(): array
    {
        return [
            'ctrl' => [
                'descriptionColumn' => 'rowDescription',
                'editlock' => 'editlock',
                'type' => 'CType',
                'transOrigPointerField' => 'l18n_parent',
                'languageField' => 'sys_language_uid',
                'enablecolumns' => [
                    'disabled' => 'hidden',
                    'starttime' => 'starttime',
                    'endtime' => 'endtime',
                    'fe_group' => 'fe_group',
                ],
            ],
            'palettes' => [
                'general' => [
                    'showitem' => '
                        CType;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType_formlabel,
                        colPos;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:colPos_formlabel,
                    ',
                ],
                'hidden' => [
                    'showitem' => '
                        hidden;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:field.default.hidden
                    ',
                ],
                'language' => [
                    'showitem' => '
                        sys_language_uid,l18n_parent
                    ',
                ],
                'access' => [
                    'showitem' => '
                        starttime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:starttime_formlabel,
                        endtime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:endtime_formlabel,
                        --linebreak--,
                        fe_group;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:fe_group_formlabel,
                        --linebreak--,editlock
                    ',
                ],
            ],
            'types' => [
                'header' => [
                    'showitem' => '
                            --palette--;;headers,
                        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
                            --palette--;;frames,
                            --palette--;;appearanceLinks,
                        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                            categories,
                        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
                    ',
                ],
            ],
        ];
    }
}
