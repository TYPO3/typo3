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

namespace TYPO3\CMS\Extbase\Tests\Functional\Persistence\Generic\Mapper;

use TYPO3\CMS\Core\DataHandling\TableColumnType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMapFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ColumnMapFactoryTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected ColumnMapFactory $columnMapFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->columnMapFactory = $this->get(ColumnMapFactory::class);
    }

    public static function createDataProvider(): \Generator
    {
        $columnName = 'has_one';
        $propertyName = GeneralUtility::underscoredToLowerCamelCase($columnName);
        $expectedColumnMap = new ColumnMap($columnName, $propertyName);
        $expectedColumnMap->setType(TableColumnType::SELECT);
        $expectedColumnMap->setTypeOfRelation(ColumnMap::RELATION_HAS_ONE);
        $expectedColumnMap->setChildTableName('tx_myextension_bar');
        $expectedColumnMap->setParentKeyFieldName('parentid');
        yield 'setRelations detects one to one relation with legacy "Tx_Foo_Bar" class name schema' => [
            'columnName' => $columnName,
            'columnConfiguration' => [
                'config' => [
                    'type' => 'select',
                    'foreign_table' => 'tx_myextension_bar',
                    'foreign_field' => 'parentid',
                ],
            ],
            'propertyName' => $propertyName,
            'expectedColumnMap' => $expectedColumnMap,
        ];

        $columnName = 'has_one_via_intermediate_table';
        $propertyName = GeneralUtility::underscoredToLowerCamelCase($columnName);
        $expectedColumnMap = new ColumnMap($columnName, $propertyName);
        $expectedColumnMap->setType(TableColumnType::SELECT);
        $expectedColumnMap->setTypeOfRelation(ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY);
        $expectedColumnMap->setRelationTableName('tx_myextension_mm');
        $expectedColumnMap->setChildTableName('tx_myextension_bar');
        $expectedColumnMap->setChildSortByFieldName('sorting');
        $expectedColumnMap->setParentKeyFieldName('uid_local');
        $expectedColumnMap->setChildKeyFieldName('uid_foreign');
        yield 'setRelations detects one to one relation with intermediate table' => [
            'columnName' => $columnName,
            'columnConfiguration' => [
                'config' => [
                    'type' => 'select',
                    'foreign_table' => 'tx_myextension_bar',
                    'MM' => 'tx_myextension_mm',
                ],
            ],
            'propertyName' => $propertyName,
            'expectedColumnMap' => $expectedColumnMap,
        ];

        $columnName = 'has_many';
        $propertyName = GeneralUtility::underscoredToLowerCamelCase($columnName);
        $expectedColumnMap = new ColumnMap($columnName, $propertyName);
        $expectedColumnMap->setType(TableColumnType::SELECT);
        $expectedColumnMap->setTypeOfRelation(ColumnMap::RELATION_HAS_MANY);
        $expectedColumnMap->setChildTableName('tx_myextension_bar');
        $expectedColumnMap->setParentTableFieldName('parenttable');
        $expectedColumnMap->setParentKeyFieldName('parentid');
        yield 'setRelations detects one to many relation' => [
            'columnName' => $columnName,
            'columnConfiguration' => [
                'config' => [
                    'type' => 'select',
                    'foreign_table' => 'tx_myextension_bar',
                    'foreign_field' => 'parentid',
                    'foreign_table_field' => 'parenttable',
                ],
            ],
            'propertyName' => $propertyName,
            'expectedColumnMap' => $expectedColumnMap,
        ];

        $columnName = 'virtual';
        $propertyName = GeneralUtility::underscoredToLowerCamelCase($columnName);
        $expectedColumnMap = new ColumnMap($columnName, $propertyName);
        $expectedColumnMap->setType(TableColumnType::SELECT);
        $expectedColumnMap->setTypeOfRelation(ColumnMap::RELATION_NONE);
        yield 'setRelations detects select renderType selectSingle as non-relational' => [
            'columnName' => $columnName,
            'columnConfiguration' => [
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => [
                        ['One', 1],
                        ['Two', 2],
                        ['Three', 3],
                    ],
                ],
            ],
            'propertyName' => $propertyName,
            'expectedColumnMap' => $expectedColumnMap,
        ];

        $columnName = 'group';
        $propertyName = GeneralUtility::underscoredToLowerCamelCase($columnName);
        $expectedColumnMap = new ColumnMap($columnName, $propertyName);
        $expectedColumnMap->setType(TableColumnType::GROUP);
        $expectedColumnMap->setTypeOfRelation(ColumnMap::RELATION_HAS_MANY);
        yield 'columns configuration is initialized for type group' => [
            'columnName' => $columnName,
            'columnConfiguration' => [
                'config' => [
                    'type' => 'group',
                ],
            ],
            'propertyName' => $propertyName,
            'expectedColumnMap' => $expectedColumnMap,
        ];

        $columnName = 'folder';
        $propertyName = GeneralUtility::underscoredToLowerCamelCase($columnName);
        $expectedColumnMap = new ColumnMap($columnName, $propertyName);
        $expectedColumnMap->setType(TableColumnType::FOLDER);
        $expectedColumnMap->setTypeOfRelation(ColumnMap::RELATION_HAS_MANY);
        yield 'columns configuration is initialized for type folder' => [
            'columnName' => $columnName,
            'columnConfiguration' => [
                'config' => [
                    'type' => 'folder',
                ],
            ],
            'propertyName' => $propertyName,
            'expectedColumnMap' => $expectedColumnMap,
        ];

        $columnName = 'group_with_maxitems_1';
        $propertyName = GeneralUtility::underscoredToLowerCamelCase($columnName);
        $expectedColumnMap = new ColumnMap($columnName, $propertyName);
        $expectedColumnMap->setType(TableColumnType::GROUP);
        $expectedColumnMap->setTypeOfRelation(ColumnMap::RELATION_NONE);
        yield 'columns configuration is initialized with maxitems = 1 evaluation for type group' => [
            'columnName' => $columnName,
            'columnConfiguration' => [
                'config' => [
                    'type' => 'group',
                    'maxitems' => '1',
                ],
            ],
            'propertyName' => $propertyName,
            'expectedColumnMap' => $expectedColumnMap,
        ];

        $columnName = 'group_with_maxitems_10';
        $propertyName = GeneralUtility::underscoredToLowerCamelCase($columnName);
        $expectedColumnMap = new ColumnMap($columnName, $propertyName);
        $expectedColumnMap->setType(TableColumnType::GROUP);
        $expectedColumnMap->setTypeOfRelation(ColumnMap::RELATION_HAS_MANY);
        yield 'columns configuration is initialized with maxitems > 1 evaluation for type group' => [
            'columnName' => $columnName,
            'columnConfiguration' => [
                'config' => [
                    'type' => 'group',
                    'maxitems' => '10',
                ],
            ],
            'propertyName' => $propertyName,
            'expectedColumnMap' => $expectedColumnMap,
        ];

        $columnName = 'has_and_belongs_to_many';
        $propertyName = GeneralUtility::underscoredToLowerCamelCase($columnName);
        $expectedColumnMap = new ColumnMap($columnName, $propertyName);
        $expectedColumnMap->setType(TableColumnType::SELECT);
        $expectedColumnMap->setTypeOfRelation(ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY);
        $expectedColumnMap->setRelationTableName('tx_myextension_mm');
        $expectedColumnMap->setChildTableName('tx_myextension_bar');
        $expectedColumnMap->setChildSortByFieldName('sorting');
        $expectedColumnMap->setParentKeyFieldName('uid_local');
        $expectedColumnMap->setChildKeyFieldName('uid_foreign');
        yield 'setRelations detects many to many relation of type select' => [
            'columnName' => $columnName,
            'columnConfiguration' => [
                'config' => [
                    'type' => 'select',
                    'foreign_table' => 'tx_myextension_bar',
                    'MM' => 'tx_myextension_mm',
                ],
            ],
            'propertyName' => $propertyName,
            'expectedColumnMap' => $expectedColumnMap,
        ];

        $columnName = 'has_and_belongs_to_many';
        $propertyName = GeneralUtility::underscoredToLowerCamelCase($columnName);
        $expectedColumnMap = new ColumnMap($columnName, $propertyName);
        $expectedColumnMap->setType(TableColumnType::INLINE);
        $expectedColumnMap->setTypeOfRelation(ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY);
        $expectedColumnMap->setRelationTableName('tx_myextension_mm');
        $expectedColumnMap->setChildTableName('tx_myextension_righttable');
        $expectedColumnMap->setChildSortByFieldName('sorting');
        $expectedColumnMap->setParentKeyFieldName('uid_local');
        $expectedColumnMap->setChildKeyFieldName('uid_foreign');
        yield 'setRelations detects many to many relation of type inline with intermediate table' => [
            'columnName' => $columnName,
            'columnConfiguration' => [
                'config' => [
                    'type' => 'inline',
                    'foreign_table' => 'tx_myextension_righttable',
                    'MM' => 'tx_myextension_mm',
                ],
            ],
            'propertyName' => $propertyName,
            'expectedColumnMap' => $expectedColumnMap,
        ];
    }

    /**
     * @dataProvider createDataProvider
     * @test
     */
    public function create(string $columnName, array $columnConfiguration, string $propertyName, ColumnMap $expectedColumnMap): void
    {
        self::assertEquals(
            $expectedColumnMap,
            $this->columnMapFactory->create($columnName, $columnConfiguration, $propertyName, Fixtures\ColumnMapFactoryEntityFixture::class)
        );
    }

    /**
     * @test
     */
    public function settingOneToOneRelationSetsRelationTableMatchFields(): void
    {
        $columnName = 'has_one';
        $propertyName = GeneralUtility::underscoredToLowerCamelCase($columnName);
        $columnConfiguration = [
            'config' => [
                'type' => 'select',
                'foreign_table' => 'tx_myextension_bar',
                'foreign_field' => 'parentid',
                'foreign_match_fields' =>  [
                    'fieldname' => 'foo_model',
                ],
            ],
        ];

        $columnMap = $this->columnMapFactory->create($columnName, $columnConfiguration, $propertyName, Fixtures\ColumnMapFactoryEntityFixture::class);

        self::assertSame(
            [
                'fieldname' => 'foo_model',
            ],
            $columnMap->getRelationTableMatchFields()
        );
    }

    /**
     * @test
     */
    public function settingOneToManyRelationSetsRelationTableMatchFields(): void
    {
        $columnName = 'has_many';
        $propertyName = GeneralUtility::underscoredToLowerCamelCase($columnName);
        $columnConfiguration = [
            'config' => [
                'type' => 'select',
                'foreign_table' => 'tx_myextension_bar',
                'foreign_field' => 'parentid',
                'foreign_match_fields' => [
                    'fieldname' => 'foo_model',
                ],
            ],
        ];

        $columnMap = $this->columnMapFactory->create($columnName, $columnConfiguration, $propertyName, Fixtures\ColumnMapFactoryEntityFixture::class);

        self::assertSame(
            [
                'fieldname' => 'foo_model',
            ],
            $columnMap->getRelationTableMatchFields()
        );
    }

    /**
     * @test
     */
    public function columnMapIsInitializedWithManyToManyRelationOfTypeSelect(): void
    {
        $columnName = 'has_and_belongs_to_many';
        $propertyName = GeneralUtility::underscoredToLowerCamelCase($columnName);
        $expectedColumnMap = new ColumnMap($columnName, $propertyName);
        $expectedColumnMap->setType(TableColumnType::SELECT);
        $expectedColumnMap->setTypeOfRelation(ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY);
        $expectedColumnMap->setRelationTableName('tx_myextension_mm');
        $expectedColumnMap->setChildTableName('tx_myextension_righttable');
        $expectedColumnMap->setChildSortByFieldName('sorting');
        $expectedColumnMap->setParentKeyFieldName('uid_local');
        $expectedColumnMap->setChildKeyFieldName('uid_foreign');

        $columnConfiguration = [
            'config' => [
                'type' => 'select',
                'foreign_table' => 'tx_myextension_righttable',
                'foreign_table_where' => 'WHERE 1=1',
                'MM' => 'tx_myextension_mm',
                'MM_table_where' => 'WHERE 2=2',
            ],
        ];

        self::assertEquals($expectedColumnMap, $this->columnMapFactory->create($columnName, $columnConfiguration, $propertyName, Fixtures\ColumnMapFactoryEntityFixture::class));
    }

    /**
     * @test
     */
    public function columnMapIsInitializedWithOppositeManyToManyRelationOfTypeSelect(): void
    {
        $columnName = 'has_and_belongs_to_many';
        $propertyName = GeneralUtility::underscoredToLowerCamelCase($columnName);
        $expectedColumnMap = new ColumnMap($columnName, $propertyName);
        $expectedColumnMap->setType(TableColumnType::SELECT);
        $expectedColumnMap->setTypeOfRelation(ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY);
        $expectedColumnMap->setRelationTableName('tx_myextension_mm');
        $expectedColumnMap->setChildTableName('tx_myextension_lefttable');
        $expectedColumnMap->setChildSortByFieldName('sorting_foreign');
        $expectedColumnMap->setParentKeyFieldName('uid_foreign');
        $expectedColumnMap->setChildKeyFieldName('uid_local');

        $columnConfiguration = [
            'config' => [
                'type' => 'select',
                'foreign_table' => 'tx_myextension_lefttable',
                'MM' => 'tx_myextension_mm',
                'MM_opposite_field' => 'rights',
            ],
        ];

        self::assertEquals($expectedColumnMap, $this->columnMapFactory->create($columnName, $columnConfiguration, $propertyName, Fixtures\ColumnMapFactoryEntityFixture::class));
    }

    /**
     * @test
     */
    public function columnMapIsInitializedWithManyToManyRelationOfTypeInlineAndIntermediateTable(): void
    {
        $columnName = 'has_and_belongs_to_many';
        $propertyName = GeneralUtility::underscoredToLowerCamelCase($columnName);
        $expectedColumnMap = new ColumnMap($columnName, $propertyName);
        $expectedColumnMap->setType(TableColumnType::INLINE);
        $expectedColumnMap->setTypeOfRelation(ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY);
        $expectedColumnMap->setRelationTableName('tx_myextension_mm');
        $expectedColumnMap->setChildTableName('tx_myextension_righttable');
        $expectedColumnMap->setChildSortByFieldName('sorting');
        $expectedColumnMap->setParentKeyFieldName('uid_local');
        $expectedColumnMap->setChildKeyFieldName('uid_foreign');

        $columnConfiguration = [
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_myextension_righttable',
                'MM' => 'tx_myextension_mm',
                'foreign_sortby' => 'sorting',
            ],
        ];

        self::assertEquals($expectedColumnMap, $this->columnMapFactory->create($columnName, $columnConfiguration, $propertyName, Fixtures\ColumnMapFactoryEntityFixture::class));
    }

    /**
     * @test
     */
    public function columnMapIsInitializedWithManyToManyRelationWithoutPidColumn(): void
    {
        $columnName = 'many_to_many';
        $propertyName = GeneralUtility::underscoredToLowerCamelCase($columnName);
        $columnConfiguration = [
            'config' => [
                'type' => 'select',
                'foreign_table' => 'tx_myextension_righttable',
                'foreign_table_where' => 'WHERE 1=1',
                'MM' => 'tx_myextension_mm',
            ],
        ];

        $columnMap = $this->columnMapFactory->create($columnName, $columnConfiguration, $propertyName, Fixtures\ColumnMapFactoryEntityFixture::class);

        self::assertNull($columnMap->getRelationTablePageIdColumnName());
    }

    /**
     * @test
     */
    public function columnMapIsInitializedWithManyToManyRelationWithPidColumn(): void
    {
        $columnName = 'many_to_many';
        $propertyName = GeneralUtility::underscoredToLowerCamelCase($columnName);
        $columnConfiguration = [
            'config' => [
                'type' => 'select',
                'foreign_table' => 'tx_myextension_righttable',
                'foreign_table_where' => 'WHERE 1=1',
                'MM' => 'tx_myextension_mm',
            ],
        ];
        // MM checks if "the other" side exist in TCA. We need to at least have a TCA ctrl section.
        // @see ColumnMapFactory->getControlSection() and usage in this case.
        $GLOBALS['TCA']['tx_myextension_mm']['ctrl'] = [];

        $columnMap = $this->columnMapFactory->create($columnName, $columnConfiguration, $propertyName, Fixtures\ColumnMapFactoryEntityFixture::class);

        self::assertSame('pid', $columnMap->getRelationTablePageIdColumnName());
    }

    public function columnMapIsInitializedWithFieldEvaluationsForDateTimeFieldsDataProvider(): array
    {
        return [
            'date field' => ['date', 'date'],
            'datetime field' => ['datetime', 'datetime'],
            'time field' => ['time', 'time'],
            'no date/datetime/time field' => ['', null],
        ];
    }

    /**
     * @test
     * @dataProvider columnMapIsInitializedWithFieldEvaluationsForDateTimeFieldsDataProvider
     */
    public function columnMapIsInitializedWithFieldEvaluationsForDateTimeFields(string $type, ?string $expectedValue): void
    {
        $columnName = 'virtual';
        $propertyName = GeneralUtility::underscoredToLowerCamelCase($columnName);
        $columnConfiguration = [
            'config' => [
                'type' => 'datetime',
                'dbType' => $type,
            ],
        ];

        $columnMap = $this->columnMapFactory->create($columnName, $columnConfiguration, $propertyName, Fixtures\ColumnMapFactoryEntityFixture::class);

        self::assertSame($expectedValue, $columnMap->getDateTimeStorageFormat());
    }

    public function tcaConfigurationsContainingTypeDataProvider(): array
    {
        return [
            ['input', TableColumnType::INPUT],
            ['text', TableColumnType::TEXT],
            ['check', TableColumnType::CHECK],
            ['radio', TableColumnType::RADIO],
            ['select', TableColumnType::SELECT],
            ['category', TableColumnType::CATEGORY],
            ['group', TableColumnType::GROUP],
            ['folder', TableColumnType::FOLDER],
            ['none', TableColumnType::NONE],
            ['language', TableColumnType::LANGUAGE],
            ['passthrough', TableColumnType::PASSTHROUGH],
            ['user', TableColumnType::USER],
            ['flex', TableColumnType::FLEX],
            ['inline', TableColumnType::INLINE],
            ['slug', TableColumnType::SLUG],
            ['email', TableColumnType::EMAIL],
            ['link', TableColumnType::LINK],
            ['password', TableColumnType::PASSWORD],
            ['datetime', TableColumnType::DATETIME],
            ['color', TableColumnType::COLOR],
            ['number', TableColumnType::NUMBER],
            ['file', TableColumnType::FILE],
            ['json', TableColumnType::JSON],
            ['uuid', TableColumnType::UUID],
        ];
    }

    /**
     * @test
     * @dataProvider tcaConfigurationsContainingTypeDataProvider
     */
    public function setTypeDetectsTypeProperly(string $type, TableColumnType $expectedType): void
    {
        $columnName = 'virtual';
        $propertyName = GeneralUtility::underscoredToLowerCamelCase($columnName);
        $columnConfiguration = [
            'config' => [
                'type' => $type,
            ],
        ];

        $columnMap = $this->columnMapFactory->create($columnName, $columnConfiguration, $propertyName, Fixtures\ColumnMapFactoryEntityFixture::class);

        self::assertSame($expectedType, $columnMap->getType());
    }
}
