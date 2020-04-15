<?php

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

use TYPO3\CMS\Belog\Domain\Model\LogEntry;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\DataHandling\TableColumnSubType;
use TYPO3\CMS\Core\DataHandling\TableColumnType;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Domain\Model\FrontendUser;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception\InvalidClassException;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class DataMapFactoryTest extends UnitTestCase
{
    /**
     * @return array
     */
    public function oneToOneRelation()
    {
        return [
            ['Tx_Myext_Domain_Model_Foo'],
            [FrontendUser::class]
        ];
    }

    /**
     * @test
     * @dataProvider oneToOneRelation
     */
    public function setRelationsDetectsOneToOneRelation($className)
    {
        $mockColumnMap = $this->createMock(ColumnMap::class);
        $columnConfiguration = [
            'type' => 'select',
            'foreign_table' => 'tx_myextension_bar',
            'foreign_field' => 'parentid'
        ];
        $type = $className;
        $elementType = null;
        $mockDataMapFactory = $this->getAccessibleMock(DataMapFactory::class, ['setOneToOneRelation', 'setOneToManyRelation', 'setManyToManyRelation'], [], '', false);
        $mockDataMapFactory->expects(self::once())->method('setOneToOneRelation')->willReturn($mockColumnMap);
        $mockDataMapFactory->expects(self::never())->method('setOneToManyRelation');
        $mockDataMapFactory->expects(self::never())->method('setManyToManyRelation');
        $mockDataMapFactory->_call('setRelations', $mockColumnMap, $columnConfiguration, $type, $elementType);
    }

    /**
     * @test
     */
    public function settingOneToOneRelationSetsRelationTableMatchFields()
    {
        $mockColumnMap = $this->createMock(ColumnMap::class);
        $matchFields = [
            'fieldname' => 'foo_model'
        ];
        $columnConfiguration = [
            'type' => 'select',
            'foreign_table' => 'tx_myextension_bar',
            'foreign_field' => 'parentid',
            'foreign_match_fields' => $matchFields
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
    public function settingOneToManyRelationSetsRelationTableMatchFields()
    {
        $mockColumnMap = $this->createMock(ColumnMap::class);
        $matchFields = [
            'fieldname' => 'foo_model'
        ];
        $columnConfiguration = [
            'type' => 'select',
            'foreign_table' => 'tx_myextension_bar',
            'foreign_field' => 'parentid',
            'foreign_match_fields' => $matchFields
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
    public function setRelationsDetectsOneToOneRelationWithIntermediateTable()
    {
        $mockColumnMap = $this->createMock(ColumnMap::class);
        $columnConfiguration = [
            'type' => 'select',
            'foreign_table' => 'tx_myextension_bar',
            'MM' => 'tx_myextension_mm'
        ];
        $type = 'Tx_Myext_Domain_Model_Foo';
        $elementType = null;
        $mockDataMapFactory = $this->getAccessibleMock(DataMapFactory::class, ['setOneToOneRelation', 'setOneToManyRelation', 'setManyToManyRelation'], [], '', false);
        $mockDataMapFactory->expects(self::never())->method('setOneToOneRelation');
        $mockDataMapFactory->expects(self::never())->method('setOneToManyRelation');
        $mockDataMapFactory->expects(self::once())->method('setManyToManyRelation')->willReturn($mockColumnMap);
        $mockDataMapFactory->_call('setRelations', $mockColumnMap, $columnConfiguration, $type, $elementType);
    }

    /**
     * @test
     */
    public function setRelationsDetectsOneToManyRelation()
    {
        $mockColumnMap = $this->createMock(ColumnMap::class);
        $columnConfiguration = [
            'type' => 'select',
            'foreign_table' => 'tx_myextension_bar',
            'foreign_field' => 'parentid',
            'foreign_table_field' => 'parenttable'
        ];
        $type = ObjectStorage::class;
        $elementType = 'Tx_Myext_Domain_Model_Foo';
        $mockDataMapFactory = $this->getAccessibleMock(DataMapFactory::class, ['setOneToOneRelation', 'setOneToManyRelation', 'setManyToManyRelation'], [], '', false);
        $mockDataMapFactory->expects(self::never())->method('setOneToOneRelation');
        $mockDataMapFactory->expects(self::once())->method('setOneToManyRelation')->willReturn($mockColumnMap);
        $mockDataMapFactory->expects(self::never())->method('setManyToManyRelation');
        $mockDataMapFactory->_call('setRelations', $mockColumnMap, $columnConfiguration, $type, $elementType);
    }

    /**
     * @test
     */
    public function setRelationsDetectsSelectRenderTypeSingleAsNonRelational()
    {
        $columnMap = new ColumnMap('foo', 'foo');
        $columnConfiguration = [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => [
                ['One', 1],
                ['Two', 2],
                ['Three', 3],
            ],
        ];
        $type = null;
        $elementType = null;
        $mockDataMapFactory = $this->getAccessibleMock(DataMapFactory::class, ['setOneToOneRelation', 'setOneToManyRelation', 'setManyToManyRelation'], [], '', false);
        $mockDataMapFactory->expects(self::never())->method('setOneToOneRelation');
        $mockDataMapFactory->expects(self::never())->method('setOneToManyRelation');
        $mockDataMapFactory->expects(self::never())->method('setManyToManyRelation');
        $actualColumnMap = $mockDataMapFactory->_call('setRelations', $columnMap, $columnConfiguration, $type, $elementType);
        self::assertSame($columnMap::RELATION_NONE, $actualColumnMap->getTypeOfRelation());
    }

    /**
     * @return array
     */
    public function columnConfigurationIsInitializedWithMaxItemsEvaluationForTypeGroupDataProvider()
    {
        return [
            'maxitems not set' => ['', 'RELATION_HAS_MANY'],
            'maxitems equals 1' => ['1', 'RELATION_NONE'],
            'maxitems higher than 1' => ['10', 'RELATION_HAS_MANY']
        ];
    }

    /**
     * @test
     *
     * @dataProvider columnConfigurationIsInitializedWithMaxItemsEvaluationForTypeGroupDataProvider
     */
    public function setRelationsDetectsTypeGroupAndRelationManyToMany($maxitems, $relation)
    {
        $columnMap = new ColumnMap('foo', 'foo');
        if (empty($maxitems)) {
            $columnConfiguration = [
                'type' => 'group',
            ];
        } else {
            $columnConfiguration = [
                'type' => 'group',
                'maxitems' => $maxitems
            ];
        }
        $type = null;
        $elementType = null;
        $mockDataMapFactory = $this->getAccessibleMock(DataMapFactory::class, ['setOneToOneRelation', 'setOneToManyRelation', 'setManyToManyRelation'], [], '', false);
        $mockDataMapFactory->expects(self::never())->method('setOneToOneRelation');
        $mockDataMapFactory->expects(self::never())->method('setOneToManyRelation');
        $mockDataMapFactory->expects(self::never())->method('setManyToManyRelation');
        $actualColumnMap = $mockDataMapFactory->_call('setRelations', $columnMap, $columnConfiguration, $type, $elementType);
        self::assertSame($relation, $actualColumnMap->getTypeOfRelation());
    }

    /**
     * @test
     */
    public function setRelationsDetectsManyToManyRelationOfTypeSelect()
    {
        $mockColumnMap = $this->createMock(ColumnMap::class);
        $columnConfiguration = [
            'type' => 'select',
            'foreign_table' => 'tx_myextension_bar',
            'MM' => 'tx_myextension_mm'
        ];
        $type = ObjectStorage::class;
        $elementType = 'Tx_Myext_Domain_Model_Foo';
        $mockDataMapFactory = $this->getAccessibleMock(DataMapFactory::class, ['setOneToOneRelation', 'setOneToManyRelation', 'setManyToManyRelation'], [], '', false);
        $mockDataMapFactory->expects(self::never())->method('setOneToOneRelation');
        $mockDataMapFactory->expects(self::never())->method('setOneToManyRelation');
        $mockDataMapFactory->expects(self::once())->method('setManyToManyRelation')->willReturn($mockColumnMap);
        $mockDataMapFactory->_call('setRelations', $mockColumnMap, $columnConfiguration, $type, $elementType);
    }

    /**
     * @test
     */
    public function setRelationsDetectsManyToManyRelationOfTypeInlineWithIntermediateTable()
    {
        $mockColumnMap = $this->createMock(ColumnMap::class);
        $columnConfiguration = [
            'type' => 'inline',
            'foreign_table' => 'tx_myextension_righttable',
            'MM' => 'tx_myextension_mm'
        ];
        $type = ObjectStorage::class;
        $elementType = 'Tx_Myext_Domain_Model_Foo';
        $mockDataMapFactory = $this->getAccessibleMock(DataMapFactory::class, ['setOneToOneRelation', 'setOneToManyRelation', 'setManyToManyRelation'], [], '', false);
        $mockDataMapFactory->expects(self::never())->method('setOneToOneRelation');
        $mockDataMapFactory->expects(self::never())->method('setOneToManyRelation');
        $mockDataMapFactory->expects(self::once())->method('setManyToManyRelation')->willReturn($mockColumnMap);
        $mockDataMapFactory->_call('setRelations', $mockColumnMap, $columnConfiguration, $type, $elementType);
    }

    /**
     * @test
     */
    public function columnMapIsInitializedWithManyToManyRelationOfTypeSelect()
    {
        $leftColumnsDefinition = [
            'rights' => [
                'type' => 'select',
                'foreign_table' => 'tx_myextension_righttable',
                'foreign_table_where' => 'WHERE 1=1',
                'MM' => 'tx_myextension_mm',
                'MM_table_where' => 'WHERE 2=2'
            ]
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
    public function columnMapIsInitializedWithOppositeManyToManyRelationOfTypeSelect()
    {
        $rightColumnsDefinition = [
            'lefts' => [
                'type' => 'select',
                'foreign_table' => 'tx_myextension_lefttable',
                'MM' => 'tx_myextension_mm',
                'MM_opposite_field' => 'rights'
            ]
        ];
        $leftColumnsDefinition = [];
        $leftColumnsDefinition['rights']['MM_opposite_field'] = 'opposite_field';
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
    public function columnMapIsInitializedWithManyToManyRelationOfTypeInlineAndIntermediateTable()
    {
        $leftColumnsDefinition = [
            'rights' => [
                'type' => 'inline',
                'foreign_table' => 'tx_myextension_righttable',
                'MM' => 'tx_myextension_mm',
                'foreign_sortby' => 'sorting'
            ]
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
    public function columnMapIsInitializedWithManyToManyRelationWithoutPidColumn()
    {
        $leftColumnsDefinition = [
            'rights' => [
                'type' => 'select',
                'foreign_table' => 'tx_myextension_righttable',
                'foreign_table_where' => 'WHERE 1=1',
                'MM' => 'tx_myextension_mm'
            ]
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
    public function columnMapIsInitializedWithManyToManyRelationWithPidColumn()
    {
        $leftColumnsDefinition = [
            'rights' => [
                'type' => 'select',
                'foreign_table' => 'tx_myextension_righttable',
                'foreign_table_where' => 'WHERE 1=1',
                'MM' => 'tx_myextension_mm'
            ]
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
    public function columnMapIsInitializedWithFieldEvaluationsForDateTimeFieldsDataProvider()
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
    public function columnMapIsInitializedWithFieldEvaluationsForDateTimeFields($type, $expectedValue)
    {
        $columnDefinition = [
            'type' => 'input',
            'dbType' => $type,
            'eval' => $type,
        ];

        $mockColumnMap = $this->getMockBuilder(ColumnMap::class)
            ->setMethods(['setDateTimeStorageFormat'])
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
    public function buildDataMapThrowsExceptionIfClassNameIsNotKnown()
    {
        $this->expectException(InvalidClassException::class);
        // @TODO expectExceptionCode is 0
        $mockDataMapFactory = $this->getAccessibleMock(DataMapFactory::class, ['getControlSection'], [], '', false);
        $cacheMock = $this->getMockBuilder(VariableFrontend::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();
        $cacheMock->expects(self::any())->method('get')->willReturn(false);
        $mockDataMapFactory->_set('dataMapCache', $cacheMock);
        $mockDataMapFactory->buildDataMap('UnknownObject');
    }

    /**
     * @test
     */
    public function buildDataMapFetchesSubclassesRecursively()
    {
        self::markTestSkipped('Incomplete mocking in a complex scenario. This should be a functional test');
        $configuration = [
            'persistence' => [
                'classes' => [
                    FrontendUser::class => [
                        'subclasses' => [
                            'Tx_SampleExt_Domain_Model_LevelOne1' => 'Tx_SampleExt_Domain_Model_LevelOne1',
                            'Tx_SampleExt_Domain_Model_LevelOne2' => 'Tx_SampleExt_Domain_Model_LevelOne2'
                        ]
                    ],
                    'Tx_SampleExt_Domain_Model_LevelOne1' => [
                        'subclasses' => [
                            'Tx_SampleExt_Domain_Model_LevelTwo1' => 'Tx_SampleExt_Domain_Model_LevelTwo1',
                            'Tx_SampleExt_Domain_Model_LevelTwo2' => 'Tx_SampleExt_Domain_Model_LevelTwo2'
                        ]
                    ],
                    'Tx_SampleExt_Domain_Model_LevelOne2' => [
                        'subclasses' => []
                    ]
                ]
            ]
        ];
        $expectedSubclasses = [
            'Tx_SampleExt_Domain_Model_LevelOne1',
            'Tx_SampleExt_Domain_Model_LevelTwo1',
            'Tx_SampleExt_Domain_Model_LevelTwo2',
            'Tx_SampleExt_Domain_Model_LevelOne2'
        ];

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface|\PHPUnit\Framework\MockObject\MockObject $objectManager */
        $objectManager = $this->getMockBuilder(ObjectManager::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();

        /** @var $configurationManager \TYPO3\CMS\Extbase\Configuration\ConfigurationManager|\PHPUnit\Framework\MockObject\MockObject */
        $configurationManager = $this->createMock(ConfigurationManager::class);
        $configurationManager->expects(self::once())->method('getConfiguration')->with('Framework')->willReturn($configuration);
        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory $dataMapFactory */
        $dataMapFactory = $this->getAccessibleMock(DataMapFactory::class, ['test']);
        $dataMapFactory->_set('reflectionService', new ReflectionService());
        $dataMapFactory->_set('objectManager', $objectManager);
        $dataMapFactory->_set('configurationManager', $configurationManager);
        $cacheMock = $this->createMock(VariableFrontend::class);
        $cacheMock->expects(self::any())->method('get')->willReturn(false);
        $dataMapFactory->_set('dataMapCache', $cacheMock);
        $dataMap = $dataMapFactory->buildDataMap(FrontendUser::class);
        self::assertSame($expectedSubclasses, $dataMap->getSubclasses());
    }

    /**
     * @return array
     */
    public function classNameTableNameMappings()
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
    public function resolveTableNameReturnsExpectedTablenames($className, $expected)
    {
        $dataMapFactory = $this->getAccessibleMock(DataMapFactory::class, ['dummy'], [], '', false);
        self::assertSame($expected, $dataMapFactory->_call('resolveTableName', $className));
    }

    /**
     * @test
     */
    public function createColumnMapReturnsAValidColumnMap()
    {
        /** @var $dataMapFactory \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory */
        $dataMapFactory = $this->getAccessibleMock(DataMapFactory::class, ['dummy'], [], '', false);

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface|\PHPUnit\Framework\MockObject\MockObject $objectManager */
        $objectManager = $this->createMock(ObjectManager::class);
        $columnMap = $this->getMockBuilder(ColumnMap::class)
            ->setConstructorArgs(['column', 'property'])
            ->getMock();
        $objectManager->expects(self::once())->method('get')->willReturn($columnMap);

        $dataMapFactory->_set('objectManager', $objectManager);

        self::assertEquals(
            $columnMap,
            $dataMapFactory->_call('createColumnMap', 'column', 'property')
        );
    }

    /**
     * @return array
     */
    public function tcaConfigurationsContainingTypeAndInternalType()
    {
        return [
            [['type' => 'input'], TableColumnType::INPUT, null],
            [['type' => 'text'], TableColumnType::TEXT, null],
            [['type' => 'check'], TableColumnType::CHECK, null],
            [['type' => 'radio'], TableColumnType::RADIO, null],
            [['type' => 'select'], TableColumnType::SELECT, null],
            [['type' => 'group', 'internal_type' => 'db'], TableColumnType::GROUP, TableColumnSubType::DB],
            [['type' => 'group', 'internal_type' => 'folder'], TableColumnType::GROUP, TableColumnSubType::FOLDER],
            [['type' => 'none'], TableColumnType::NONE, null],
            [['type' => 'passthrough'], TableColumnType::PASSTHROUGH, null],
            [['type' => 'user'], TableColumnType::USER, null],
            [['type' => 'flex'], TableColumnType::FLEX, null],
            [['type' => 'inline'], TableColumnType::INLINE, null],
            [['type' => 'slug'], TableColumnType::SLUG, null],
        ];
    }

    /**
     * @test
     * @dataProvider tcaConfigurationsContainingTypeAndInternalType
     *
     * @param array $columnConfiguration
     * @param string $type
     * @param string $internalType
     */
    public function setTypeDetectsTypeAndInternalTypeProperly(array $columnConfiguration, $type, $internalType)
    {
        /** @var $dataMapFactory \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory | AccessibleObjectInterface */
        $dataMapFactory = $this->getAccessibleMock(DataMapFactory::class, ['dummy'], [], '', false);

        /** @var ColumnMap $columnMap */
        $columnMap = $this->getAccessibleMock(ColumnMap::class, ['dummy'], [], '', false);

        $dataMapFactory->_call('setType', $columnMap, $columnConfiguration);

        self::assertEquals($type, (string)$columnMap->getType());
        self::assertEquals($internalType, (string)$columnMap->getInternalType());
    }
}
