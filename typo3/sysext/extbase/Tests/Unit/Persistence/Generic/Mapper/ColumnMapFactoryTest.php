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

namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence\Generic\Mapper;

use ExtbaseTeam\BlogExample\Domain\Model\Administrator;
use TYPO3\CMS\Core\DataHandling\TableColumnType;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMapFactory;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ColumnMapFactoryTest extends UnitTestCase
{
    public function setRelationsDataProvider(): iterable
    {
        yield 'setRelations detects one to one relation with legacy "Tx_Foo_Bar" class name schema' => [
            'type' => 'Tx_Myext_Domain_Model_Foo',
            'elementType' => null,
            'tca' => [
                'type' => 'select',
                'foreign_table' => 'tx_myextension_bar',
                'foreign_field' => 'parentid',
            ],
            'expectedRelation' => ColumnMap::RELATION_HAS_ONE,
        ];

        yield 'setRelations detects one to one relation with FQCN' => [
            'type' => Administrator::class,
            'elementType' => null,
            'tca' => [
                'type' => 'select',
                'foreign_table' => 'tx_myextension_bar',
                'foreign_field' => 'parentid',
            ],
            'expectedRelation' => ColumnMap::RELATION_HAS_ONE,
        ];

        yield 'setRelations detects one to one relation with intermediate table' => [
            'type' => 'Tx_Myext_Domain_Model_Foo',
            'elementType' => null,
            'tca' => [
                'type' => 'select',
                'foreign_table' => 'tx_myextension_bar',
                'MM' => 'tx_myextension_mm',
            ],
            'expectedRelation' => ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY,
        ];

        yield 'setRelations detects one to many relation' => [
            'type' => ObjectStorage::class,
            'elementType' => 'Tx_Myext_Domain_Model_Foo',
            'tca' => [
                'type' => 'select',
                'foreign_table' => 'tx_myextension_bar',
                'foreign_field' => 'parentid',
                'foreign_table_field' => 'parenttable',
            ],
            'expectedRelation' => ColumnMap::RELATION_HAS_MANY,
        ];

        yield 'setRelations detects select renderType selectSingle as non-relational' => [
            'type' => null,
            'elementType' => null,
            'tca' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['One', 1],
                    ['Two', 2],
                    ['Three', 3],
                ],
            ],
            'expectedRelation' => ColumnMap::RELATION_NONE,
        ];

        yield 'columns configuration is initialized for type group' => [
            'type' => null,
            'elementType' => null,
            'tca' => [
                'type' => 'group',
            ],
            'expectedRelation' => ColumnMap::RELATION_HAS_MANY,
        ];

        yield 'columns configuration is initialized for type folder' => [
            'type' => null,
            'elementType' => null,
            'tca' => [
                'type' => 'folder',
            ],
            'expectedRelation' => ColumnMap::RELATION_HAS_MANY,
        ];

        yield 'columns configuration is initialized with maxitems = 1 evaluation for type group' => [
            'type' => null,
            'elementType' => null,
            'tca' => [
                'type' => 'group',
                'maxitems' => '1',
            ],
            'expectedRelation' => ColumnMap::RELATION_NONE,
        ];

        yield 'columns configuration is initialized with maxitems > 1 evaluation for type group' => [
            'type' => null,
            'elementType' => null,
            'tca' => [
                'type' => 'group',
                'maxitems' => '10',
            ],
            'expectedRelation' => ColumnMap::RELATION_HAS_MANY,
        ];

        yield 'setRelations detects many to many relation of type select' => [
            'type' => ObjectStorage::class,
            'elementType' => 'Tx_Myext_Domain_Model_Foo',
            'tca' => [
                'type' => 'select',
                'foreign_table' => 'tx_myextension_bar',
                'MM' => 'tx_myextension_mm',
            ],
            'expectedRelation' => ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY,
        ];

        yield 'setRelations detects many to many relation of type inline with intermediate table' => [
            'type' => ObjectStorage::class,
            'elementType' => 'Tx_Myext_Domain_Model_Foo',
            'tca' => [
                'type' => 'inline',
                'foreign_table' => 'tx_myextension_righttable',
                'MM' => 'tx_myextension_mm',
            ],
            'expectedRelation' => ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY,
        ];
    }

    /**
     * @dataProvider setRelationsDataProvider
     * @test
     */
    public function setRelations(?string $type, ?string $elementType, array $columnConfiguration, string $expectedRelation): void
    {
        $columnMap = new ColumnMap('foo', 'foo');
        $mockColumnMapFactory = $this->getAccessibleMock(ColumnMapFactory::class, null, [], '', false);
        $actualColumnMap = $mockColumnMapFactory->_call('setRelations', $columnMap, $columnConfiguration, $type, $elementType);
        self::assertSame($expectedRelation, $actualColumnMap->getTypeOfRelation());
    }

    /**
     * @test
     */
    public function settingOneToOneRelationSetsRelationTableMatchFields(): void
    {
        $mockColumnMap = $this->createMock(ColumnMap::class);
        $matchFields = [
            'fieldname' => 'foo_model',
        ];
        $columnConfiguration = [
            'type' => 'select',
            'foreign_table' => 'tx_myextension_bar',
            'foreign_field' => 'parentid',
            'foreign_match_fields' => $matchFields,
        ];

        $mockColumnMap->expects(self::once())
            ->method('setRelationTableMatchFields')
            ->with($matchFields);
        $mockColumnMapFactory = $this->getAccessibleMock(ColumnMapFactory::class, null, [], '', false);
        $mockColumnMapFactory->_call('setOneToOneRelation', $mockColumnMap, $columnConfiguration);
    }

    /**
     * @test
     */
    public function settingOneToManyRelationSetsRelationTableMatchFields(): void
    {
        $mockColumnMap = $this->createMock(ColumnMap::class);
        $matchFields = [
            'fieldname' => 'foo_model',
        ];
        $columnConfiguration = [
            'type' => 'select',
            'foreign_table' => 'tx_myextension_bar',
            'foreign_field' => 'parentid',
            'foreign_match_fields' => $matchFields,
        ];

        $mockColumnMap->expects(self::once())
            ->method('setRelationTableMatchFields')
            ->with($matchFields);
        $mockColumnMapFactory = $this->getAccessibleMock(ColumnMapFactory::class, null, [], '', false);
        $mockColumnMapFactory->_call('setOneToManyRelation', $mockColumnMap, $columnConfiguration);
    }

    /**
     * @test
     */
    public function columnMapIsInitializedWithManyToManyRelationOfTypeSelect(): void
    {
        $leftColumnsDefinition = [
            'rights' => [
                'type' => 'select',
                'foreign_table' => 'tx_myextension_righttable',
                'foreign_table_where' => 'WHERE 1=1',
                'MM' => 'tx_myextension_mm',
                'MM_table_where' => 'WHERE 2=2',
            ],
        ];
        $mockColumnMap = $this->createMock(ColumnMap::class);
        $mockColumnMap->expects(self::once())->method('setTypeOfRelation')->with(self::equalTo(ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY));
        $mockColumnMap->expects(self::once())->method('setRelationTableName')->with(self::equalTo('tx_myextension_mm'));
        $mockColumnMap->expects(self::once())->method('setChildTableName')->with(self::equalTo('tx_myextension_righttable'));
        $mockColumnMap->expects(self::once())->method('setChildSortByFieldName')->with(self::equalTo('sorting'));
        $mockColumnMap->expects(self::once())->method('setParentKeyFieldName')->with(self::equalTo('uid_local'));
        $mockColumnMap->expects(self::never())->method('setParentTableFieldName');
        $mockColumnMap->expects(self::never())->method('setRelationTableMatchFields');
        $mockColumnMap->expects(self::never())->method('setRelationTableInsertFields');
        $mockColumnMapFactory = $this->getAccessibleMock(ColumnMapFactory::class, null, [], '', false);
        $mockColumnMapFactory->_call('setManyToManyRelation', $mockColumnMap, $leftColumnsDefinition['rights']);
    }

    /**
     * @test
     */
    public function columnMapIsInitializedWithOppositeManyToManyRelationOfTypeSelect(): void
    {
        $rightColumnsDefinition = [
            'lefts' => [
                'type' => 'select',
                'foreign_table' => 'tx_myextension_lefttable',
                'MM' => 'tx_myextension_mm',
                'MM_opposite_field' => 'rights',
            ],
        ];

        $mockColumnMap = $this->createMock(ColumnMap::class);
        $mockColumnMap->expects(self::once())->method('setTypeOfRelation')->with(self::equalTo(ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY));
        $mockColumnMap->expects(self::once())->method('setRelationTableName')->with(self::equalTo('tx_myextension_mm'));
        $mockColumnMap->expects(self::once())->method('setChildTableName')->with(self::equalTo('tx_myextension_lefttable'));
        $mockColumnMap->expects(self::once())->method('setChildSortByFieldName')->with(self::equalTo('sorting_foreign'));
        $mockColumnMap->expects(self::once())->method('setParentKeyFieldName')->with(self::equalTo('uid_foreign'));
        $mockColumnMap->expects(self::never())->method('setParentTableFieldName');
        $mockColumnMap->expects(self::never())->method('setRelationTableMatchFields');
        $mockColumnMap->expects(self::never())->method('setRelationTableInsertFields');
        $mockColumnMapFactory = $this->getAccessibleMock(ColumnMapFactory::class, null, [], '', false);
        $mockColumnMapFactory->_call('setManyToManyRelation', $mockColumnMap, $rightColumnsDefinition['lefts']);
    }

    /**
     * @test
     */
    public function columnMapIsInitializedWithManyToManyRelationOfTypeInlineAndIntermediateTable(): void
    {
        $leftColumnsDefinition = [
            'rights' => [
                'type' => 'inline',
                'foreign_table' => 'tx_myextension_righttable',
                'MM' => 'tx_myextension_mm',
                'foreign_sortby' => 'sorting',
            ],
        ];
        $mockColumnMap = $this->createMock(ColumnMap::class);
        $mockColumnMap->expects(self::once())->method('setTypeOfRelation')->with(self::equalTo(ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY));
        $mockColumnMap->expects(self::once())->method('setRelationTableName')->with(self::equalTo('tx_myextension_mm'));
        $mockColumnMap->expects(self::once())->method('setChildTableName')->with(self::equalTo('tx_myextension_righttable'));
        $mockColumnMap->expects(self::once())->method('setChildSortByFieldName')->with(self::equalTo('sorting'));
        $mockColumnMap->expects(self::once())->method('setParentKeyFieldName')->with(self::equalTo('uid_local'));
        $mockColumnMap->expects(self::never())->method('setParentTableFieldName');
        $mockColumnMap->expects(self::never())->method('setRelationTableMatchFields');
        $mockColumnMap->expects(self::never())->method('setRelationTableInsertFields');
        $mockColumnMapFactory = $this->getAccessibleMock(ColumnMapFactory::class, ['getColumnsDefinition'], [], '', false);
        $mockColumnMapFactory->expects(self::never())->method('getColumnsDefinition');
        $mockColumnMapFactory->_call('setManyToManyRelation', $mockColumnMap, $leftColumnsDefinition['rights']);
    }

    /**
     * @test
     */
    public function columnMapIsInitializedWithManyToManyRelationWithoutPidColumn(): void
    {
        $leftColumnsDefinition = [
            'rights' => [
                'type' => 'select',
                'foreign_table' => 'tx_myextension_righttable',
                'foreign_table_where' => 'WHERE 1=1',
                'MM' => 'tx_myextension_mm',
            ],
        ];
        $mockColumnMap = $this->createMock(ColumnMap::class);
        $mockColumnMap->expects(self::once())->method('setRelationTableName')->with(self::equalTo('tx_myextension_mm'));
        $mockColumnMap->expects(self::once())->method('getRelationTableName')->willReturn('tx_myextension_mm');
        $mockColumnMap->expects(self::never())->method('setrelationTablePageIdColumnName');
        $mockColumnMapFactory = $this->getAccessibleMock(ColumnMapFactory::class, ['getControlSection'], [], '', false);
        $mockColumnMapFactory->expects(self::once())->method('getControlSection')->with(self::equalTo('tx_myextension_mm'))->willReturn(null);
        $mockColumnMapFactory->_call('setManyToManyRelation', $mockColumnMap, $leftColumnsDefinition['rights']);
    }

    /**
     * @test
     */
    public function columnMapIsInitializedWithManyToManyRelationWithPidColumn(): void
    {
        $leftColumnsDefinition = [
            'rights' => [
                'type' => 'select',
                'foreign_table' => 'tx_myextension_righttable',
                'foreign_table_where' => 'WHERE 1=1',
                'MM' => 'tx_myextension_mm',
            ],
        ];
        $mockColumnMap = $this->createMock(ColumnMap::class);
        $mockColumnMap->expects(self::once())->method('setRelationTableName')->with(self::equalTo('tx_myextension_mm'));
        $mockColumnMap->expects(self::once())->method('getRelationTableName')->willReturn('tx_myextension_mm');
        $mockColumnMap->expects(self::once())->method('setrelationTablePageIdColumnName')->with(self::equalTo('pid'));
        $mockColumnMapFactory = $this->getAccessibleMock(ColumnMapFactory::class, ['getControlSection'], [], '', false);
        $mockColumnMapFactory->expects(self::once())->method('getControlSection')->with(self::equalTo('tx_myextension_mm'))->willReturn(['ctrl' => ['foo' => 'bar']]);
        $mockColumnMapFactory->_call('setManyToManyRelation', $mockColumnMap, $leftColumnsDefinition['rights']);
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
     * @param string $type
     * @param string|null $expectedValue
     * @test
     * @dataProvider columnMapIsInitializedWithFieldEvaluationsForDateTimeFieldsDataProvider
     */
    public function columnMapIsInitializedWithFieldEvaluationsForDateTimeFields($type, $expectedValue): void
    {
        $columnDefinition = [
            'type' => 'datetime',
            'dbType' => $type,
        ];

        $mockColumnMap = $this->getMockBuilder(ColumnMap::class)
            ->onlyMethods(['setDateTimeStorageFormat'])
            ->disableOriginalConstructor()
            ->getMock();

        $mockColumnMap->setType(TableColumnType::DATETIME);

        if ($expectedValue !== null) {
            $mockColumnMap->expects(self::once())->method('setDateTimeStorageFormat')->with(self::equalTo($type));
        } else {
            $mockColumnMap->expects(self::never())->method('setDateTimeStorageFormat');
        }

        $accessibleColumnMapFactory = $this->getAccessibleMock(
            ColumnMapFactory::class,
            ['dummy'],
            [],
            '',
            false
        );
        $accessibleColumnMapFactory->_call('setDateTimeStorageFormat', $mockColumnMap, $columnDefinition);
    }

    public function tcaConfigurationsContainingType(): array
    {
        return [
            [['type' => 'input'], TableColumnType::INPUT],
            [['type' => 'text'], TableColumnType::TEXT],
            [['type' => 'check'], TableColumnType::CHECK],
            [['type' => 'radio'], TableColumnType::RADIO],
            [['type' => 'select'], TableColumnType::SELECT],
            [['type' => 'category'], TableColumnType::CATEGORY],
            [['type' => 'group'], TableColumnType::GROUP],
            [['type' => 'folder'], TableColumnType::FOLDER],
            [['type' => 'none'], TableColumnType::NONE],
            [['type' => 'language'], TableColumnType::LANGUAGE],
            [['type' => 'passthrough'], TableColumnType::PASSTHROUGH],
            [['type' => 'user'], TableColumnType::USER],
            [['type' => 'flex'], TableColumnType::FLEX],
            [['type' => 'inline'], TableColumnType::INLINE],
            [['type' => 'slug'], TableColumnType::SLUG],
            [['type' => 'email'], TableColumnType::EMAIL],
            [['type' => 'link'], TableColumnType::LINK],
            [['type' => 'password'], TableColumnType::PASSWORD],
            [['type' => 'datetime'], TableColumnType::DATETIME],
            [['type' => 'color'], TableColumnType::COLOR],
            [['type' => 'number'], TableColumnType::NUMBER],
            [['type' => 'file'], TableColumnType::FILE],
            [['type' => 'json'], TableColumnType::JSON],
            [['type' => 'uuid'], TableColumnType::UUID],
        ];
    }

    /**
     * @test
     * @dataProvider tcaConfigurationsContainingType
     */
    public function setTypeDetectsTypeProperly(array $columnConfiguration, TableColumnType $type): void
    {
        $columnMapFactory = $this->getAccessibleMock(ColumnMapFactory::class, null, [], '', false);

        $columnMap = $this->getAccessibleMock(ColumnMap::class, null, [], '', false);

        $columnMapFactory->_call('setType', $columnMap, $columnConfiguration);

        self::assertEquals($type, $columnMap->getType());
    }
}
