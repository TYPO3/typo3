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

    #[Test]
    public function prepareSelectSingleAddsMaxItems(): void
    {
        $subject = (new TcaPreparation())->prepare(['foo' => ['columns' => ['select' => ['config' => ['type' => 'select', 'renderType' => 'selectSingle']]]]]);
        self::assertEquals(1, $subject['foo']['columns']['select']['config']['maxitems']);
    }
}
