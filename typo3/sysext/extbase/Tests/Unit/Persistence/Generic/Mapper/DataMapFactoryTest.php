<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence\Generic\Mapper;

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

use TYPO3\CMS\Core\DataHandling\TableColumnSubType;
use TYPO3\CMS\Core\DataHandling\TableColumnType;
use TYPO3\CMS\Core\Tests\AccessibleObjectInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap;

/**
 * Test case
 */
class DataMapFactoryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @return array
     */
    public function oneToOneRelation()
    {
        return [
            ['Tx_Myext_Domain_Model_Foo'],
            [\TYPO3\CMS\Extbase\Domain\Model\FrontendUser::class]
        ];
    }

    /**
     * @test
     * @dataProvider oneToOneRelation
     */
    public function setRelationsDetectsOneToOneRelation($className)
    {
        $mockColumnMap = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::class, [], [], '', false);
        $columnConfiguration = [
            'type' => 'select',
            'foreign_table' => 'tx_myextension_bar',
            'foreign_field' => 'parentid'
        ];
        $propertyMetaData = [
            'type' => $className,
            'elementType' => null
        ];
        $mockDataMapFactory = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory::class, ['setOneToOneRelation', 'setOneToManyRelation', 'setManyToManyRelation'], [], '', false);
        $mockDataMapFactory->expects($this->once())->method('setOneToOneRelation')->will($this->returnValue($mockColumnMap));
        $mockDataMapFactory->expects($this->never())->method('setOneToManyRelation');
        $mockDataMapFactory->expects($this->never())->method('setManyToManyRelation');
        $mockDataMapFactory->_callRef('setRelations', $mockColumnMap, $columnConfiguration, $propertyMetaData);
    }

    /**
     * @test
     */
    public function settingOneToOneRelationSetsRelationTableMatchFields()
    {
        $mockColumnMap = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::class, [], [], '', false);
        $matchFields = [
                'fieldname' => 'foo_model'
            ];
        $columnConfiguration = [
            'type' => 'select',
            'foreign_table' => 'tx_myextension_bar',
            'foreign_field' => 'parentid',
            'foreign_match_fields' => $matchFields
        ];

        $mockColumnMap->expects($this->once())
            ->method('setRelationTableMatchFields')
            ->with($matchFields);
        $mockDataMapFactory = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory::class, ['dummy'], [], '', false);
        $mockDataMapFactory->_call('setOneToOneRelation', $mockColumnMap, $columnConfiguration);
    }

    /**
     * @test
     */
    public function settingOneToManyRelationSetsRelationTableMatchFields()
    {
        $mockColumnMap = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::class, [], [], '', false);
        $matchFields = [
                'fieldname' => 'foo_model'
            ];
        $columnConfiguration = [
            'type' => 'select',
            'foreign_table' => 'tx_myextension_bar',
            'foreign_field' => 'parentid',
            'foreign_match_fields' => $matchFields
        ];

        $mockColumnMap->expects($this->once())
            ->method('setRelationTableMatchFields')
            ->with($matchFields);
        $mockDataMapFactory = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory::class, ['dummy'], [], '', false);
        $mockDataMapFactory->_call('setOneToManyRelation', $mockColumnMap, $columnConfiguration);
    }

    /**
     * @test
     */
    public function setRelationsDetectsOneToOneRelationWithIntermediateTable()
    {
        $mockColumnMap = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::class, [], [], '', false);
        $columnConfiguration = [
            'type' => 'select',
            'foreign_table' => 'tx_myextension_bar',
            'MM' => 'tx_myextension_mm'
        ];
        $propertyMetaData = [
            'type' => 'Tx_Myext_Domain_Model_Foo',
            'elementType' => null
        ];
        $mockDataMapFactory = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory::class, ['setOneToOneRelation', 'setOneToManyRelation', 'setManyToManyRelation'], [], '', false);
        $mockDataMapFactory->expects($this->never())->method('setOneToOneRelation');
        $mockDataMapFactory->expects($this->never())->method('setOneToManyRelation');
        $mockDataMapFactory->expects($this->once())->method('setManyToManyRelation')->will($this->returnValue($mockColumnMap));
        $mockDataMapFactory->_callRef('setRelations', $mockColumnMap, $columnConfiguration, $propertyMetaData);
    }

    /**
     * @test
     */
    public function setRelationsDetectsOneToManyRelation()
    {
        $mockColumnMap = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::class, [], [], '', false);
        $columnConfiguration = [
            'type' => 'select',
            'foreign_table' => 'tx_myextension_bar',
            'foreign_field' => 'parentid',
            'foreign_table_field' => 'parenttable'
        ];
        $propertyMetaData = [
            'type' => \TYPO3\CMS\Extbase\Persistence\ObjectStorage::class,
            'elementType' => 'Tx_Myext_Domain_Model_Foo'
        ];
        $mockDataMapFactory = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory::class, ['setOneToOneRelation', 'setOneToManyRelation', 'setManyToManyRelation'], [], '', false);
        $mockDataMapFactory->expects($this->never())->method('setOneToOneRelation');
        $mockDataMapFactory->expects($this->once())->method('setOneToManyRelation')->will($this->returnValue($mockColumnMap));
        $mockDataMapFactory->expects($this->never())->method('setManyToManyRelation');
        $mockDataMapFactory->_callRef('setRelations', $mockColumnMap, $columnConfiguration, $propertyMetaData);
    }

    /**
     * @test
     */
    public function setRelationsDetectsManyToManyRelationOfTypeSelect()
    {
        $mockColumnMap = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::class, [], [], '', false);
        $columnConfiguration = [
            'type' => 'select',
            'foreign_table' => 'tx_myextension_bar',
            'MM' => 'tx_myextension_mm'
        ];
        $propertyMetaData = [
            'type' => \TYPO3\CMS\Extbase\Persistence\ObjectStorage::class,
            'elementType' => 'Tx_Myext_Domain_Model_Foo'
        ];
        $mockDataMapFactory = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory::class, ['setOneToOneRelation', 'setOneToManyRelation', 'setManyToManyRelation'], [], '', false);
        $mockDataMapFactory->expects($this->never())->method('setOneToOneRelation');
        $mockDataMapFactory->expects($this->never())->method('setOneToManyRelation');
        $mockDataMapFactory->expects($this->once())->method('setManyToManyRelation')->will($this->returnValue($mockColumnMap));
        $mockDataMapFactory->_callRef('setRelations', $mockColumnMap, $columnConfiguration, $propertyMetaData);
    }

    /**
     * @test
     */
    public function setRelationsDetectsManyToManyRelationOfTypeInlineWithIntermediateTable()
    {
        $mockColumnMap = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::class, [], [], '', false);
        $columnConfiguration = [
            'type' => 'inline',
            'foreign_table' => 'tx_myextension_righttable',
            'MM' => 'tx_myextension_mm'
        ];
        $propertyMetaData = [
            'type' => \TYPO3\CMS\Extbase\Persistence\ObjectStorage::class,
            'elementType' => 'Tx_Myext_Domain_Model_Foo'
        ];
        $mockDataMapFactory = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory::class, ['setOneToOneRelation', 'setOneToManyRelation', 'setManyToManyRelation'], [], '', false);
        $mockDataMapFactory->expects($this->never())->method('setOneToOneRelation');
        $mockDataMapFactory->expects($this->never())->method('setOneToManyRelation');
        $mockDataMapFactory->expects($this->once())->method('setManyToManyRelation')->will($this->returnValue($mockColumnMap));
        $mockDataMapFactory->_callRef('setRelations', $mockColumnMap, $columnConfiguration, $propertyMetaData);
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
        $mockColumnMap = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::class, [], [], '', false);
        $mockColumnMap->expects($this->once())->method('setTypeOfRelation')->with($this->equalTo(ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY));
        $mockColumnMap->expects($this->once())->method('setRelationTableName')->with($this->equalTo('tx_myextension_mm'));
        $mockColumnMap->expects($this->once())->method('setChildTableName')->with($this->equalTo('tx_myextension_righttable'));
        $mockColumnMap->expects($this->once())->method('setChildTableWhereStatement')->with($this->equalTo('WHERE 1=1'));
        $mockColumnMap->expects($this->once())->method('setChildSortByFieldName')->with($this->equalTo('sorting'));
        $mockColumnMap->expects($this->once())->method('setParentKeyFieldName')->with($this->equalTo('uid_local'));
        $mockColumnMap->expects($this->never())->method('setParentTableFieldName');
        $mockColumnMap->expects($this->never())->method('setRelationTableMatchFields');
        $mockColumnMap->expects($this->never())->method('setRelationTableInsertFields');
        $mockDataMapFactory = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory::class, ['dummy'], [], '', false);
        $mockDataMapFactory->_callRef('setManyToManyRelation', $mockColumnMap, $leftColumnsDefinition['rights']);
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
        $leftColumnsDefinition['rights']['MM_opposite_field'] = 'opposite_field';
        $mockColumnMap = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::class, [], [], '', false);
        $mockColumnMap->expects($this->once())->method('setTypeOfRelation')->with($this->equalTo(ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY));
        $mockColumnMap->expects($this->once())->method('setRelationTableName')->with($this->equalTo('tx_myextension_mm'));
        $mockColumnMap->expects($this->once())->method('setChildTableName')->with($this->equalTo('tx_myextension_lefttable'));
        $mockColumnMap->expects($this->once())->method('setChildTableWhereStatement')->with(null);
        $mockColumnMap->expects($this->once())->method('setChildSortByFieldName')->with($this->equalTo('sorting_foreign'));
        $mockColumnMap->expects($this->once())->method('setParentKeyFieldName')->with($this->equalTo('uid_foreign'));
        $mockColumnMap->expects($this->never())->method('setParentTableFieldName');
        $mockColumnMap->expects($this->never())->method('setRelationTableMatchFields');
        $mockColumnMap->expects($this->never())->method('setRelationTableInsertFields');
        $mockDataMapFactory = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory::class, ['dummy'], [], '', false);
        $mockDataMapFactory->_callRef('setManyToManyRelation', $mockColumnMap, $rightColumnsDefinition['lefts']);
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
        $mockColumnMap = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::class, [], [], '', false);
        $mockColumnMap->expects($this->once())->method('setTypeOfRelation')->with($this->equalTo(ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY));
        $mockColumnMap->expects($this->once())->method('setRelationTableName')->with($this->equalTo('tx_myextension_mm'));
        $mockColumnMap->expects($this->once())->method('setChildTableName')->with($this->equalTo('tx_myextension_righttable'));
        $mockColumnMap->expects($this->once())->method('setChildTableWhereStatement');
        $mockColumnMap->expects($this->once())->method('setChildSortByFieldName')->with($this->equalTo('sorting'));
        $mockColumnMap->expects($this->once())->method('setParentKeyFieldName')->with($this->equalTo('uid_local'));
        $mockColumnMap->expects($this->never())->method('setParentTableFieldName');
        $mockColumnMap->expects($this->never())->method('setRelationTableMatchFields');
        $mockColumnMap->expects($this->never())->method('setRelationTableInsertFields');
        $mockDataMapFactory = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory::class, ['getColumnsDefinition'], [], '', false);
        $mockDataMapFactory->expects($this->never())->method('getColumnsDefinition');
        $mockDataMapFactory->_callRef('setManyToManyRelation', $mockColumnMap, $leftColumnsDefinition['rights']);
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
        $mockColumnMap = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::class, [], [], '', false);
        $mockColumnMap->expects($this->once())->method('setRelationTableName')->with($this->equalTo('tx_myextension_mm'));
        $mockColumnMap->expects($this->once())->method('getRelationTableName')->will($this->returnValue('tx_myextension_mm'));
        $mockColumnMap->expects($this->never())->method('setrelationTablePageIdColumnName');
        $mockDataMapFactory = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory::class, ['getControlSection'], [], '', false);
        $mockDataMapFactory->expects($this->once())->method('getControlSection')->with($this->equalTo('tx_myextension_mm'))->will($this->returnValue(null));
        $mockDataMapFactory->_callRef('setManyToManyRelation', $mockColumnMap, $leftColumnsDefinition['rights']);
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
        $mockColumnMap = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::class, [], [], '', false);
        $mockColumnMap->expects($this->once())->method('setRelationTableName')->with($this->equalTo('tx_myextension_mm'));
        $mockColumnMap->expects($this->once())->method('getRelationTableName')->will($this->returnValue('tx_myextension_mm'));
        $mockColumnMap->expects($this->once())->method('setrelationTablePageIdColumnName')->with($this->equalTo('pid'));
        $mockDataMapFactory = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory::class, ['getControlSection'], [], '', false);
        $mockDataMapFactory->expects($this->once())->method('getControlSection')->with($this->equalTo('tx_myextension_mm'))->will($this->returnValue(['ctrl' => ['foo' => 'bar']]));
        $mockDataMapFactory->_callRef('setManyToManyRelation', $mockColumnMap, $leftColumnsDefinition['rights']);
    }

    /**
     * @return array
     */
    public function columnMapIsInitializedWithFieldEvaluationsForDateTimeFieldsDataProvider()
    {
        return [
            'date field' => ['date', 'date'],
            'datetime field' => ['datetime', 'datetime'],
            'no date/datetime field' => ['', null],
        ];
    }

    /**
     * @param string $type
     * @param NULL|string $expectedValue
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

        $mockColumnMap = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::class, ['setDateTimeStorageFormat'], [], '', false);

        if ($expectedValue !== null) {
            $mockColumnMap->expects($this->once())->method('setDateTimeStorageFormat')->with($this->equalTo($type));
        } else {
            $mockColumnMap->expects($this->never())->method('setDateTimeStorageFormat');
        }

        $accessibleClassName = $this->buildAccessibleProxy(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory::class);
        $accessibleDataMapFactory = new $accessibleClassName();
        $accessibleDataMapFactory->_callRef('setFieldEvaluations', $mockColumnMap, $columnDefinition);
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Extbase\Persistence\Generic\Exception\InvalidClassException
     */
    public function buildDataMapThrowsExceptionIfClassNameIsNotKnown()
    {
        $mockDataMapFactory = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory::class, ['getControlSection'], [], '', false);
        $cacheMock = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class, ['get'], [], '', false);
        $cacheMock->expects($this->any())->method('get')->will($this->returnValue(false));
        $mockDataMapFactory->_set('dataMapCache', $cacheMock);
        $mockDataMapFactory->buildDataMap('UnknownObject');
    }

    /**
     * @test
     */
    public function buildDataMapFetchesSubclassesRecursively()
    {
        $this->markTestSkipped('Incomplete mocking in a complex scenario. This should be a functional test');
        $configuration = [
            'persistence' => [
                'classes' => [
                    \TYPO3\CMS\Extbase\Domain\Model\FrontendUser::class => [
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

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject $objectManager */
        $objectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManager::class, ['dummy'], [], '', false);

        /** @var $configurationManager \TYPO3\CMS\Extbase\Configuration\ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject */
        $configurationManager = $this->getMock(\TYPO3\CMS\Extbase\Configuration\ConfigurationManager::class);
        $configurationManager->expects($this->once())->method('getConfiguration')->with('Framework')->will($this->returnValue($configuration));
        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory $dataMapFactory */
        $dataMapFactory = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory::class, ['test']);
        $dataMapFactory->_set('reflectionService', new \TYPO3\CMS\Extbase\Reflection\ReflectionService());
        $dataMapFactory->_set('objectManager', $objectManager);
        $dataMapFactory->_set('configurationManager', $configurationManager);
        $cacheMock = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class, [], [], '', false);
        $cacheMock->expects($this->any())->method('get')->will($this->returnValue(false));
        $dataMapFactory->_set('dataMapCache', $cacheMock);
        $dataMap = $dataMapFactory->buildDataMap(\TYPO3\CMS\Extbase\Domain\Model\FrontendUser::class);
        $this->assertSame($expectedSubclasses, $dataMap->getSubclasses());
    }

    /**
     * @return array
     */
    public function classNameTableNameMappings()
    {
        return [
            'Core classes' => [\TYPO3\CMS\Belog\Domain\Model\LogEntry::class, 'tx_belog_domain_model_logentry'],
            'Core classes with namespaces and leading backslash' => [\TYPO3\CMS\Belog\Domain\Model\LogEntry::class, 'tx_belog_domain_model_logentry'],
            'Extension classes' => ['ExtbaseTeam\\BlogExample\\Domain\\Model\\Blog', 'tx_blogexample_domain_model_blog'],
            'Extension classes with namespaces and leading backslash' => ['\\ExtbaseTeam\\BlogExample\\Domain\\Model\\Blog', 'tx_blogexample_domain_model_blog'],
            'Extension classes without namespace' => ['Tx_News_Domain_Model_News', 'tx_news_domain_model_news'],
            'Extension classes without namespace but leading slash' => ['\\Tx_News_Domain_Model_News', 'tx_news_domain_model_news'],
        ];
    }

    /**
     * @test
     * @dataProvider classNameTableNameMappings
     */
    public function resolveTableNameReturnsExpectedTablenames($className, $expected)
    {
        $dataMapFactory = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory::class, ['dummy']);
        $this->assertSame($expected, $dataMapFactory->_call('resolveTableName', $className));
    }

    /**
     * @test
     */
    public function createColumnMapReturnsAValidColumnMap()
    {
        /** @var $dataMapFactory \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory */
        $dataMapFactory = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory::class, ['dummy']);

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject $objectManager */
        $objectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $columnMap = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::class, [], ['column', 'property']);
        $objectManager->expects($this->once())->method('get')->will($this->returnValue($columnMap));

        $dataMapFactory->_set('objectManager', $objectManager);

        $this->assertEquals(
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
            [['type' => 'group', 'internal_type' => 'file'], TableColumnType::GROUP, TableColumnSubType::FILE],
            [['type' => 'group', 'internal_type' => 'file_reference'], TableColumnType::GROUP, TableColumnSubType::FILE_REFERENCE],
            [['type' => 'group', 'internal_type' => 'folder'], TableColumnType::GROUP, TableColumnSubType::FOLDER],
            [['type' => 'none'], TableColumnType::NONE, null],
            [['type' => 'passthrough'], TableColumnType::PASSTHROUGH, null],
            [['type' => 'user'], TableColumnType::USER, null],
            [['type' => 'flex'], TableColumnType::FLEX, null],
            [['type' => 'inline'], TableColumnType::INLINE, null],
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
        $dataMapFactory = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory::class, ['dummy']);

        /** @var ColumnMap $columnMap */
        $columnMap = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::class, ['dummy'], [], '', false);

        $dataMapFactory->_call('setType', $columnMap, $columnConfiguration);

        $this->assertEquals($type, (string)$columnMap->getType());
        $this->assertEquals($internalType, (string)$columnMap->getInternalType());
    }
}
