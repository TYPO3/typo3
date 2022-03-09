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
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Belog\Domain\Model\LogEntry;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\DataHandling\TableColumnType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception\InvalidClassException;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class DataMapFactoryTest extends UnitTestCase
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
        $mockDataMapFactory = $this->getAccessibleMock(DataMapFactory::class, null, [], '', false);
        $actualColumnMap = $mockDataMapFactory->_call('setRelations', $columnMap, $columnConfiguration, $type, $elementType);
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
        $mockDataMapFactory = $this->getAccessibleMock(DataMapFactory::class, ['dummy'], [], '', false);
        $mockDataMapFactory->_call('setOneToOneRelation', $mockColumnMap, $columnConfiguration);
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
        $mockDataMapFactory = $this->getAccessibleMock(DataMapFactory::class, ['dummy'], [], '', false);
        $mockDataMapFactory->_call('setOneToManyRelation', $mockColumnMap, $columnConfiguration);
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
        $mockDataMapFactory = $this->getAccessibleMock(DataMapFactory::class, ['dummy'], [], '', false);
        $mockDataMapFactory->_call('setManyToManyRelation', $mockColumnMap, $leftColumnsDefinition['rights']);
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
        $mockDataMapFactory = $this->getAccessibleMock(DataMapFactory::class, ['dummy'], [], '', false);
        $mockDataMapFactory->_call('setManyToManyRelation', $mockColumnMap, $rightColumnsDefinition['lefts']);
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
        $mockDataMapFactory = $this->getAccessibleMock(DataMapFactory::class, ['getColumnsDefinition'], [], '', false);
        $mockDataMapFactory->expects(self::never())->method('getColumnsDefinition');
        $mockDataMapFactory->_call('setManyToManyRelation', $mockColumnMap, $leftColumnsDefinition['rights']);
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
        $mockDataMapFactory = $this->getAccessibleMock(DataMapFactory::class, ['getControlSection'], [], '', false);
        $mockDataMapFactory->expects(self::once())->method('getControlSection')->with(self::equalTo('tx_myextension_mm'))->willReturn(null);
        $mockDataMapFactory->_call('setManyToManyRelation', $mockColumnMap, $leftColumnsDefinition['rights']);
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
        $mockDataMapFactory = $this->getAccessibleMock(DataMapFactory::class, ['getControlSection'], [], '', false);
        $mockDataMapFactory->expects(self::once())->method('getControlSection')->with(self::equalTo('tx_myextension_mm'))->willReturn(['ctrl' => ['foo' => 'bar']]);
        $mockDataMapFactory->_call('setManyToManyRelation', $mockColumnMap, $leftColumnsDefinition['rights']);
    }

    /**
     * @return array
     */
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
            'type' => 'input',
            'dbType' => $type,
            'eval' => $type,
        ];

        $mockColumnMap = $this->getMockBuilder(ColumnMap::class)
            ->onlyMethods(['setDateTimeStorageFormat'])
            ->disableOriginalConstructor()
            ->getMock();

        if ($expectedValue !== null) {
            $mockColumnMap->expects(self::once())->method('setDateTimeStorageFormat')->with(self::equalTo($type));
        } else {
            $mockColumnMap->expects(self::never())->method('setDateTimeStorageFormat');
        }

        $accessibleDataMapFactory = $this->getAccessibleMock(
            DataMapFactory::class,
            ['dummy'],
            [],
            '',
            false
        );
        $accessibleDataMapFactory->_call('setFieldEvaluations', $mockColumnMap, $columnDefinition);
    }

    /**
     * @test
     */
    public function buildDataMapThrowsExceptionIfClassNameIsNotKnown(): void
    {
        $this->expectException(InvalidClassException::class);
        // @TODO expectExceptionCode is 0
        $mockDataMapFactory = $this->getAccessibleMock(DataMapFactory::class, ['getControlSection'], [], '', false);
        $cacheMock = $this->getMockBuilder(VariableFrontend::class)
            ->onlyMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();
        $cacheMock->method('get')->willReturn(false);
        $mockDataMapFactory->_set('dataMapCache', $cacheMock);
        $mockDataMapFactory->_set('baseCacheIdentifier', 'PackageDependentCacheIdentifier');
        $mockDataMapFactory->buildDataMap('UnknownObject');
    }

    /**
     * @return array
     */
    public function classNameTableNameMappings(): array
    {
        return [
            'Core classes' => [LogEntry::class, 'tx_belog_domain_model_logentry'],
            'Core classes with namespaces and leading backslash' => [LogEntry::class, 'tx_belog_domain_model_logentry'],
            'Extension classes' => ['ExtbaseTeam\\BlogExample\\Domain\\Model\\Blog', 'tx_blogexample_domain_model_blog'],
            'Extension classes with namespaces and leading backslash' => ['\\ExtbaseTeam\\BlogExample\\Domain\\Model\\Blog', 'tx_blogexample_domain_model_blog'],
        ];
    }

    /**
     * @test
     * @dataProvider classNameTableNameMappings
     */
    public function resolveTableNameReturnsExpectedTablenames($className, $expected): void
    {
        $dataMapFactory = $this->getAccessibleMock(DataMapFactory::class, ['dummy'], [], '', false);
        self::assertSame($expected, $dataMapFactory->_call('resolveTableName', $className));
    }

    /**
     * @test
     */
    public function createColumnMapReturnsAValidColumnMap(): void
    {
        /** @var DataMapFactory|MockObject|AccessibleObjectInterface $dataMapFactory*/
        $dataMapFactory = $this->getAccessibleMock(DataMapFactory::class, ['dummy'], [], '', false);

        $columnMap = $this->getMockBuilder(ColumnMap::class)
            ->setConstructorArgs(['column', 'property'])
            ->getMock();
        GeneralUtility::addInstance(ColumnMap::class, $columnMap);

        self::assertEquals(
            $columnMap,
            $dataMapFactory->_call('createColumnMap', 'column', 'property')
        );
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
        ];
    }

    /**
     * @test
     * @dataProvider tcaConfigurationsContainingType
     */
    public function setTypeDetectsTypeProperly(array $columnConfiguration, string $type): void
    {
        /** @var DataMapFactory|AccessibleObjectInterface|MockObject $dataMapFactory */
        $dataMapFactory = $this->getAccessibleMock(DataMapFactory::class, ['dummy'], [], '', false);

        /** @var ColumnMap|MockObject|AccessibleObjectInterface $columnMap */
        $columnMap = $this->getAccessibleMock(ColumnMap::class, ['dummy'], [], '', false);

        $dataMapFactory->_call('setType', $columnMap, $columnConfiguration);

        self::assertEquals($type, (string)$columnMap->getType());
    }
}
