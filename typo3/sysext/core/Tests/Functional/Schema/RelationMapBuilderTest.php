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

namespace TYPO3\CMS\Core\Tests\Functional\Schema;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Schema\PassiveRelation;
use TYPO3\CMS\Core\Schema\RelationMapBuilder;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RelationMapBuilderTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];

    #[Test]
    public function relationMapBuilderContainsRelationsForFeGroups(): void
    {
        $subject = $this->get(RelationMapBuilder::class);
        $relationMap = $subject->buildFromStructure($GLOBALS['TCA']);

        $result = $relationMap->getActiveRelations('fe_users', 'usergroup');
        self::assertCount(1, $result);
        self::assertEquals('fe_groups', $result[0]->toTable());
        self::assertNull($result[0]->toField());
    }

    #[Test]
    public function relationMapBuilderContainsActiveAndPassiveRelationsToInlineField(): void
    {
        $subject = $this->get(RelationMapBuilder::class);
        $relationMap = $subject->buildFromStructure($GLOBALS['TCA']);

        $inlineParentTable = 'sys_workspace';
        $inlineParentField = 'custom_stages';
        $inlineChildTable = 'sys_workspace_stage';
        $inlineChildField = 'parentid';
        $result = $relationMap->getActiveRelations($inlineParentTable, $inlineParentField);
        self::assertCount(1, $result);
        self::assertEquals($inlineChildTable, $result[0]->toTable());
        self::assertEquals($inlineChildField, $result[0]->toField());
        $result = $relationMap->getPassiveRelations($inlineChildTable, $inlineChildField);
        self::assertCount(1, $result);
        self::assertEquals($inlineParentTable, $result[0]->fromTable());
        self::assertEquals($inlineParentField, $result[0]->fromField());
    }

    #[Test]
    public function relationMapBuilderContainsFileReferenceForTtContent(): void
    {
        $subject = $this->get(RelationMapBuilder::class);
        $relationMap = $subject->buildFromStructure($GLOBALS['TCA']);

        $result = $relationMap->getActiveRelations('tt_content', 'assets');
        self::assertCount(1, $result);
        self::assertEquals('sys_file_reference', $result[0]->toTable());
        self::assertEquals('uid_foreign', $result[0]->toField());
        $result = $relationMap->getPassiveRelations('sys_file_reference', 'uid_foreign');
        // find the relation back to tt_content
        $relationToTtContent = array_filter($result, static fn(PassiveRelation $relation) => $relation->fromTable() === 'tt_content');
        $relationToTtContent = array_values($relationToTtContent);

        self::assertEquals(
            ['image', 'assets', 'media'],
            array_map(static fn(PassiveRelation $relation) => $relation->fromField(), $relationToTtContent)
        );
    }

    #[Test]
    public function relationMapBuilderHandlesFlexFormWithSingleDataStructure(): void
    {
        $tca = [
            'test_table' => [
                'columns' => [
                    'flex_field' => [
                        'config' => [
                            'type' => 'flex',
                            'ds' => '
                                <T3DataStructure>
                                    <ROOT>
                                        <sheetTitle>Default Sheet</sheetTitle>
                                        <type>array</type>
                                        <el>
                                            <related_pages>
                                                <config>
                                                    <type>select</type>
                                                    <foreign_table>pages</foreign_table>
                                                </config>
                                            </related_pages>
                                            <category_field>
                                                <config>
                                                    <type>category</type>
                                                    <foreign_table>sys_category</foreign_table>
                                                </config>
                                            </category_field>
                                        </el>
                                    </ROOT>
                                </T3DataStructure>
                            ',
                        ],
                    ],
                ],
            ],
        ];

        $subject = $this->get(RelationMapBuilder::class);
        $relationMap = $subject->buildFromStructure($tca);

        $result = $relationMap->getActiveRelations('test_table', 'flex_field');
        self::assertCount(2, $result);

        // Sort by table name for consistent testing
        usort($result, fn($a, $b) => strcmp($a->toTable(), $b->toTable()));

        self::assertEquals('pages', $result[0]->toTable());
        self::assertNull($result[0]->toField());

        self::assertEquals('sys_category', $result[1]->toTable());
        self::assertNull($result[1]->toField());
    }

    #[Test]
    public function relationMapBuilderHandlesFlexFormWithMultipleSheets(): void
    {
        $tca = [
            'test_table' => [
                'columns' => [
                    'flex_field' => [
                        'config' => [
                            'type' => 'flex',
                            'ds' => '
                                <T3DataStructure>
                                    <sheets>
                                        <sheet1>
                                            <ROOT>
                                                <sheetTitle>Sheet 1</sheetTitle>
                                                <type>array</type>
                                                <el>
                                                    <file>
                                                        <config>
                                                            <type>file</type>
                                                        </config>
                                                    </file>
                                                </el>
                                            </ROOT>
                                        </sheet1>
                                        <sheet2>
                                            <ROOT>
                                                <sheetTitle>Sheet 2</sheetTitle>
                                                <type>array</type>
                                                <el>
                                                    <group_field>
                                                        <config>
                                                            <type>group</type>
                                                            <allowed>pages,tt_content</allowed>
                                                        </config>
                                                    </group_field>
                                                </el>
                                            </ROOT>
                                        </sheet2>
                                    </sheets>
                                </T3DataStructure>
                            ',
                        ],
                    ],
                ],
            ],
        ];

        $subject = $this->get(RelationMapBuilder::class);
        $relationMap = $subject->buildFromStructure($tca);

        $result = $relationMap->getActiveRelations('test_table', 'flex_field');
        self::assertCount(3, $result); // 1 inline + 2 group (pages, tt_content)

        // Sort by table name for consistent testing
        usort($result, fn($a, $b) => strcmp($a->toTable(), $b->toTable()));

        self::assertEquals('pages', $result[0]->toTable());
        self::assertNull($result[0]->toField());

        self::assertEquals('sys_file_reference', $result[1]->toTable());
        self::assertEquals('uid_foreign', $result[1]->toField());

        self::assertEquals('tt_content', $result[2]->toTable());
        self::assertNull($result[2]->toField());
    }

    #[Test]
    public function relationMapBuilderHandlesFlexFormWithRecordTypes(): void
    {
        $tca = [
            'test_table' => [
                'ctrl' => [
                    'type' => 'content_type',
                ],
                'columns' => [
                    'content_type' => [
                        'config' => [
                            'type' => 'select',
                        ],
                    ],
                    'flex_field' => [
                        'config' => [
                            'type' => 'flex',
                            'ds' => '
                                <T3DataStructure>
                                    <ROOT>
                                        <sheetTitle>Default</sheetTitle>
                                        <type>array</type>
                                        <el>
                                            <default_field>
                                                <config>
                                                    <type>select</type>
                                                    <foreign_table>pages</foreign_table>
                                                </config>
                                            </default_field>
                                        </el>
                                    </ROOT>
                                </T3DataStructure>
                            ',
                        ],
                    ],
                ],
                'types' => [
                    'special' => [
                        'showitem' => 'content_type,flex_field',
                        'columnsOverrides' => [
                            'flex_field' => [
                                'config' => [
                                    'ds' => '
                                        <T3DataStructure>
                                            <ROOT>
                                                <sheetTitle>Special</sheetTitle>
                                                <type>array</type>
                                                <el>
                                                    <special_field>
                                                        <config>
                                                            <type>select</type>
                                                            <foreign_table>sys_category</foreign_table>
                                                        </config>
                                                    </special_field>
                                                </el>
                                            </ROOT>
                                        </T3DataStructure>
                                    ',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $subject = $this->get(RelationMapBuilder::class);
        $relationMap = $subject->buildFromStructure($tca);

        $result = $relationMap->getActiveRelations('test_table', 'flex_field');
        self::assertCount(2, $result); // 1 from default + 1 from special type

        // Sort by table name for consistent testing
        usort($result, fn($a, $b) => strcmp($a->toTable(), $b->toTable()));

        self::assertEquals('pages', $result[0]->toTable());
        self::assertEquals('sys_category', $result[1]->toTable());
    }

    #[Test]
    public function relationMapBuilderHandlesFlexFormWithMMRelations(): void
    {
        $tca = [
            'test_table' => [
                'columns' => [
                    'flex_field' => [
                        'config' => [
                            'type' => 'flex',
                            'ds' => '
                                <T3DataStructure>
                                    <ROOT>
                                        <sheetTitle>MM Relations</sheetTitle>
                                        <type>array</type>
                                        <el>
                                            <mm_categories>
                                                <config>
                                                    <type>select</type>
                                                    <foreign_table>sys_category</foreign_table>
                                                    <MM>test_table_category_mm</MM>
                                                </config>
                                            </mm_categories>
                                        </el>
                                    </ROOT>
                                </T3DataStructure>
                            ',
                        ],
                    ],
                ],
            ],
        ];

        $subject = $this->get(RelationMapBuilder::class);
        $relationMap = $subject->buildFromStructure($tca);

        $result = $relationMap->getActiveRelations('test_table', 'flex_field');
        self::assertCount(1, $result);

        // MM relations use the MM table as the target
        self::assertEquals('test_table_category_mm', $result[0]->toTable());
        self::assertNull($result[0]->toField());
    }

    #[Test]
    public function relationMapBuilderSkipsFlexFormWithInvalidDataStructure(): void
    {
        $tca = [
            'test_table' => [
                'columns' => [
                    'flex_field' => [
                        'config' => [
                            'type' => 'flex',
                            'ds' => 'FILE:EXT:does_not_exist/invalid.xml',
                        ],
                    ],
                ],
            ],
        ];

        $subject = $this->get(RelationMapBuilder::class);
        $relationMap = $subject->buildFromStructure($tca);

        $result = $relationMap->getActiveRelations('test_table', 'flex_field');
        self::assertCount(0, $result); // Should skip invalid data structures
    }

    #[Test]
    public function relationMapBuilderIgnoresFlexFormSectionRelations(): void
    {
        $tca = [
            'test_table' => [
                'columns' => [
                    'flex_field' => [
                        'config' => [
                            'type' => 'flex',
                            'ds' => '
                                <T3DataStructure>
                                    <ROOT>
                                        <sheetTitle>With Sections</sheetTitle>
                                        <type>array</type>
                                        <el>
                                            <regular_field>
                                                <config>
                                                    <type>select</type>
                                                    <foreign_table>pages</foreign_table>
                                                </config>
                                            </regular_field>
                                            <section_field>
                                                <type>array</type>
                                                <section>1</section>
                                                <el>
                                                    <container1>
                                                        <type>array</type>
                                                        <el>
                                                            <section_text>
                                                                <config>
                                                                    <type>text</type>
                                                                </config>
                                                            </section_text>
                                                        </el>
                                                    </container1>
                                                </el>
                                            </section_field>
                                        </el>
                                    </ROOT>
                                </T3DataStructure>
                            ',
                        ],
                    ],
                ],
            ],
        ];

        $subject = $this->get(RelationMapBuilder::class);
        $relationMap = $subject->buildFromStructure($tca);

        $result = $relationMap->getActiveRelations('test_table', 'flex_field');
        self::assertCount(1, $result); // Only the regular field, sections with non-relational fields are allowed

        self::assertEquals('pages', $result[0]->toTable());
    }

    #[Test]
    public function relationMapBuilderSkipsFlexFormWithRelationsInSections(): void
    {
        $tca = [
            'test_table' => [
                'columns' => [
                    'flex_field' => [
                        'config' => [
                            'type' => 'flex',
                            'ds' => '
                                <T3DataStructure>
                                    <ROOT>
                                        <sheetTitle>Invalid Sections</sheetTitle>
                                        <type>array</type>
                                        <el>
                                            <section_field>
                                                <type>array</type>
                                                <section>1</section>
                                                <el>
                                                    <container1>
                                                        <type>array</type>
                                                        <el>
                                                            <invalid_relation>
                                                                <config>
                                                                    <type>select</type>
                                                                    <foreign_table>sys_category</foreign_table>
                                                                </config>
                                                            </invalid_relation>
                                                        </el>
                                                    </container1>
                                                </el>
                                            </section_field>
                                        </el>
                                    </ROOT>
                                </T3DataStructure>
                            ',
                        ],
                    ],
                ],
            ],
        ];

        $subject = $this->get(RelationMapBuilder::class);
        $relationMap = $subject->buildFromStructure($tca);

        $result = $relationMap->getActiveRelations('test_table', 'flex_field');
        self::assertCount(0, $result); // Entire FlexForm is skipped due to invalid section structure
    }

    #[Test]
    public function relationMapBuilderHandlesFlexFormWithFileFields(): void
    {
        $tca = [
            'test_table' => [
                'columns' => [
                    'flex_field' => [
                        'config' => [
                            'type' => 'flex',
                            'ds' => '
                                <T3DataStructure>
                                    <ROOT>
                                        <sheetTitle>File Relations</sheetTitle>
                                        <type>array</type>
                                        <el>
                                            <image_field>
                                                <config>
                                                    <type>file</type>
                                                    <foreign_table>sys_file_reference</foreign_table>
                                                    <foreign_field>uid_foreign</foreign_field>
                                                </config>
                                            </image_field>
                                        </el>
                                    </ROOT>
                                </T3DataStructure>
                            ',
                        ],
                    ],
                ],
            ],
        ];

        $subject = $this->get(RelationMapBuilder::class);
        $relationMap = $subject->buildFromStructure($tca);

        $result = $relationMap->getActiveRelations('test_table', 'flex_field');
        self::assertCount(1, $result);

        self::assertEquals('sys_file_reference', $result[0]->toTable());
        self::assertEquals('uid_foreign', $result[0]->toField());
    }
}
