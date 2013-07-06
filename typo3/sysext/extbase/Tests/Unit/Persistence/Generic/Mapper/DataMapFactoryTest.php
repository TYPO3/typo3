<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence\Generic\Mapper;

/***************************************************************
 *  Copyright notice
 *
 *  This class is a backport of the corresponding class of TYPO3 Flow.
 *  All credits go to the TYPO3 Flow team.
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
class DataMapFactoryTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @return array
	 */
	public function oneToOneRelation() {
		return array(
			array('Tx_Myext_Domain_Model_Foo'),
			array('TYPO3\\CMS\\Extbase\\Domain\\Model\\FrontendUser')
		);
	}

	/**
	 * @test
	 * @dataProvider oneToOneRelation
	 */
	public function setRelationsDetectsOneToOneRelation($className) {
		$mockColumnMap = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\ColumnMap', array(), array(), '', FALSE);
		$columnConfiguration = array(
			'type' => 'select',
			'foreign_table' => 'tx_myextension_bar',
			'foreign_field' => 'parentid'
		);
		$propertyMetaData = array(
			'type' => $className,
			'elementType' => NULL
		);
		$mockDataMapFactory = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMapFactory', array('setOneToOneRelation', 'setOneToManyRelation', 'setManyToManyRelation'), array(), '', FALSE);
		$mockDataMapFactory->expects($this->once())->method('setOneToOneRelation');
		$mockDataMapFactory->expects($this->never())->method('setOneToManyRelation');
		$mockDataMapFactory->expects($this->never())->method('setManyToManyRelation');
		$mockDataMapFactory->_callRef('setRelations', $mockColumnMap, $columnConfiguration, $propertyMetaData);
	}

	/**
	 * @test
	 */
	public function settingOneToOneRelationSetsRelationTableMatchFields() {
		$mockColumnMap = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\ColumnMap', array(), array(), '', FALSE);
		$matchFields = array(
				'fieldname' => 'foo_model'
			);
		$columnConfiguration = array(
			'type' => 'select',
			'foreign_table' => 'tx_myextension_bar',
			'foreign_field' => 'parentid',
			'foreign_match_fields' => $matchFields
		);

		$mockColumnMap->expects($this->once())
			->method('setRelationTableMatchFields')
			->with($matchFields);
		$mockDataMapFactory = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMapFactory', array('dummy'), array(), '', FALSE);
		$mockDataMapFactory->_call('setOneToOneRelation', $mockColumnMap, $columnConfiguration);
	}

	/**
	 * @test
	 */
	public function settingOneToManyRelationSetsRelationTableMatchFields() {
		$mockColumnMap = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\ColumnMap', array(), array(), '', FALSE);
		$matchFields = array(
				'fieldname' => 'foo_model'
			);
		$columnConfiguration = array(
			'type' => 'select',
			'foreign_table' => 'tx_myextension_bar',
			'foreign_field' => 'parentid',
			'foreign_match_fields' => $matchFields
		);

		$mockColumnMap->expects($this->once())
			->method('setRelationTableMatchFields')
			->with($matchFields);
		$mockDataMapFactory = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMapFactory', array('dummy'), array(), '', FALSE);
		$mockDataMapFactory->_call('setOneToManyRelation', $mockColumnMap, $columnConfiguration);
	}

	/**
	 * @test
	 */
	public function setRelationsDetectsOneToOneRelationWithIntermediateTable() {
		$mockColumnMap = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\ColumnMap', array(), array(), '', FALSE);
		$columnConfiguration = array(
			'type' => 'select',
			'foreign_table' => 'tx_myextension_bar',
			'MM' => 'tx_myextension_mm'
		);
		$propertyMetaData = array(
			'type' => 'Tx_Myext_Domain_Model_Foo',
			'elementType' => NULL
		);
		$mockDataMapFactory = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMapFactory', array('setOneToOneRelation', 'setOneToManyRelation', 'setManyToManyRelation'), array(), '', FALSE);
		$mockDataMapFactory->expects($this->never())->method('setOneToOneRelation');
		$mockDataMapFactory->expects($this->never())->method('setOneToManyRelation');
		$mockDataMapFactory->expects($this->once())->method('setManyToManyRelation');
		$mockDataMapFactory->_callRef('setRelations', $mockColumnMap, $columnConfiguration, $propertyMetaData);
	}

	/**
	 * @test
	 */
	public function setRelationsDetectsOneToManyRelation() {
		$mockColumnMap = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\ColumnMap', array(), array(), '', FALSE);
		$columnConfiguration = array(
			'type' => 'select',
			'foreign_table' => 'tx_myextension_bar',
			'foreign_field' => 'parentid',
			'foreign_table_field' => 'parenttable'
		);
		$propertyMetaData = array(
			'type' => 'TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage',
			'elementType' => 'Tx_Myext_Domain_Model_Foo'
		);
		$mockDataMapFactory = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMapFactory', array('setOneToOneRelation', 'setOneToManyRelation', 'setManyToManyRelation'), array(), '', FALSE);
		$mockDataMapFactory->expects($this->never())->method('setOneToOneRelation');
		$mockDataMapFactory->expects($this->once())->method('setOneToManyRelation');
		$mockDataMapFactory->expects($this->never())->method('setManyToManyRelation');
		$mockDataMapFactory->_callRef('setRelations', $mockColumnMap, $columnConfiguration, $propertyMetaData);
	}

	/**
	 * @test
	 */
	public function setRelationsDetectsManyToManyRelationOfTypeSelect() {
		$mockColumnMap = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\ColumnMap', array(), array(), '', FALSE);
		$columnConfiguration = array(
			'type' => 'select',
			'foreign_table' => 'tx_myextension_bar',
			'MM' => 'tx_myextension_mm'
		);
		$propertyMetaData = array(
			'type' => 'TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage',
			'elementType' => 'Tx_Myext_Domain_Model_Foo'
		);
		$mockDataMapFactory = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMapFactory', array('setOneToOneRelation', 'setOneToManyRelation', 'setManyToManyRelation'), array(), '', FALSE);
		$mockDataMapFactory->expects($this->never())->method('setOneToOneRelation');
		$mockDataMapFactory->expects($this->never())->method('setOneToManyRelation');
		$mockDataMapFactory->expects($this->once())->method('setManyToManyRelation');
		$mockDataMapFactory->_callRef('setRelations', $mockColumnMap, $columnConfiguration, $propertyMetaData);
	}

	/**
	 * @test
	 */
	public function setRelationsDetectsManyToManyRelationOfTypeInlineWithIntermediateTable() {
		$mockColumnMap = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\ColumnMap', array(), array(), '', FALSE);
		$columnConfiguration = array(
			'type' => 'inline',
			'foreign_table' => 'tx_myextension_righttable',
			'MM' => 'tx_myextension_mm'
		);
		$propertyMetaData = array(
			'type' => 'TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage',
			'elementType' => 'Tx_Myext_Domain_Model_Foo'
		);
		$mockDataMapFactory = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMapFactory', array('setOneToOneRelation', 'setOneToManyRelation', 'setManyToManyRelation'), array(), '', FALSE);
		$mockDataMapFactory->expects($this->never())->method('setOneToOneRelation');
		$mockDataMapFactory->expects($this->never())->method('setOneToManyRelation');
		$mockDataMapFactory->expects($this->once())->method('setManyToManyRelation');
		$mockDataMapFactory->_callRef('setRelations', $mockColumnMap, $columnConfiguration, $propertyMetaData);
	}

	/**
	 * @test
	 */
	public function columnMapIsInitializedWithManyToManyRelationOfTypeSelect() {
		$leftColumnsDefinition = array(
			'rights' => array(
				'type' => 'select',
				'foreign_table' => 'tx_myextension_righttable',
				'foreign_table_where' => 'WHERE 1=1',
				'MM' => 'tx_myextension_mm',
				'MM_table_where' => 'WHERE 2=2'
			)
		);
		$mockColumnMap = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\ColumnMap', array(), array(), '', FALSE);
		$mockColumnMap->expects($this->once())->method('setTypeOfRelation')->with($this->equalTo(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY));
		$mockColumnMap->expects($this->once())->method('setRelationTableName')->with($this->equalTo('tx_myextension_mm'));
		$mockColumnMap->expects($this->once())->method('setChildTableName')->with($this->equalTo('tx_myextension_righttable'));
		$mockColumnMap->expects($this->once())->method('setChildTableWhereStatement')->with($this->equalTo('WHERE 1=1'));
		$mockColumnMap->expects($this->once())->method('setChildSortByFieldName')->with($this->equalTo('sorting'));
		$mockColumnMap->expects($this->once())->method('setParentKeyFieldName')->with($this->equalTo('uid_local'));
		$mockColumnMap->expects($this->never())->method('setParentTableFieldName');
		$mockColumnMap->expects($this->never())->method('setRelationTableMatchFields');
		$mockColumnMap->expects($this->never())->method('setRelationTableInsertFields');
		$mockDataMapFactory = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMapFactory', array('dummy'), array(), '', FALSE);
		$mockDataMapFactory->_callRef('setManyToManyRelation', $mockColumnMap, $leftColumnsDefinition['rights']);
	}

	/**
	 * @test
	 */
	public function columnMapIsInitializedWithOppositeManyToManyRelationOfTypeSelect() {
		$rightColumnsDefinition = array(
			'lefts' => array(
				'type' => 'select',
				'foreign_table' => 'tx_myextension_lefttable',
				'MM' => 'tx_myextension_mm',
				'MM_opposite_field' => 'rights'
			)
		);
		$leftColumnsDefinition['rights']['MM_opposite_field'] = 'opposite_field';
		$mockColumnMap = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\ColumnMap', array(), array(), '', FALSE);
		$mockColumnMap->expects($this->once())->method('setTypeOfRelation')->with($this->equalTo(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY));
		$mockColumnMap->expects($this->once())->method('setRelationTableName')->with($this->equalTo('tx_myextension_mm'));
		$mockColumnMap->expects($this->once())->method('setChildTableName')->with($this->equalTo('tx_myextension_lefttable'));
		$mockColumnMap->expects($this->once())->method('setChildTableWhereStatement')->with(NULL);
		$mockColumnMap->expects($this->once())->method('setChildSortByFieldName')->with($this->equalTo('sorting_foreign'));
		$mockColumnMap->expects($this->once())->method('setParentKeyFieldName')->with($this->equalTo('uid_foreign'));
		$mockColumnMap->expects($this->never())->method('setParentTableFieldName');
		$mockColumnMap->expects($this->never())->method('setRelationTableMatchFields');
		$mockColumnMap->expects($this->never())->method('setRelationTableInsertFields');
		$mockDataMapFactory = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMapFactory', array('dummy'), array(), '', FALSE);
		$mockDataMapFactory->_callRef('setManyToManyRelation', $mockColumnMap, $rightColumnsDefinition['lefts']);
	}

	/**
	 * @test
	 */
	public function columnMapIsInitializedWithManyToManyRelationOfTypeInlineAndIntermediateTable() {
		$leftColumnsDefinition = array(
			'rights' => array(
				'type' => 'inline',
				'foreign_table' => 'tx_myextension_righttable',
				'MM' => 'tx_myextension_mm',
				'foreign_sortby' => 'sorting'
			)
		);
		$mockColumnMap = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\ColumnMap', array(), array(), '', FALSE);
		$mockColumnMap->expects($this->once())->method('setTypeOfRelation')->with($this->equalTo(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY));
		$mockColumnMap->expects($this->once())->method('setRelationTableName')->with($this->equalTo('tx_myextension_mm'));
		$mockColumnMap->expects($this->once())->method('setChildTableName')->with($this->equalTo('tx_myextension_righttable'));
		$mockColumnMap->expects($this->once())->method('setChildTableWhereStatement');
		$mockColumnMap->expects($this->once())->method('setChildSortByFieldName')->with($this->equalTo('sorting'));
		$mockColumnMap->expects($this->once())->method('setParentKeyFieldName')->with($this->equalTo('uid_local'));
		$mockColumnMap->expects($this->never())->method('setParentTableFieldName');
		$mockColumnMap->expects($this->never())->method('setRelationTableMatchFields');
		$mockColumnMap->expects($this->never())->method('setRelationTableInsertFields');
		$mockDataMapFactory = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMapFactory', array('getColumnsDefinition'), array(), '', FALSE);
		$mockDataMapFactory->expects($this->never())->method('getColumnsDefinition');
		$mockDataMapFactory->_callRef('setManyToManyRelation', $mockColumnMap, $leftColumnsDefinition['rights']);
	}

	/**
	 * @test
	 */
	public function columnMapIsInitializedWithManyToManyRelationWithoutPidColumn() {
		$leftColumnsDefinition = array(
			'rights' => array(
				'type' => 'select',
				'foreign_table' => 'tx_myextension_righttable',
				'foreign_table_where' => 'WHERE 1=1',
				'MM' => 'tx_myextension_mm'
			)
		);
		$mockColumnMap = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\ColumnMap', array(), array(), '', FALSE);
		$mockColumnMap->expects($this->once())->method('setRelationTableName')->with($this->equalTo('tx_myextension_mm'));
		$mockColumnMap->expects($this->once())->method('getRelationTableName')->will($this->returnValue('tx_myextension_mm'));
		$mockColumnMap->expects($this->never())->method('setrelationTablePageIdColumnName');
		$mockDataMapFactory = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMapFactory', array('getControlSection'), array(), '', FALSE);
		$mockDataMapFactory->expects($this->once())->method('getControlSection')->with($this->equalTo('tx_myextension_mm'))->will($this->returnValue(NULL));
		$mockDataMapFactory->_callRef('setManyToManyRelation', $mockColumnMap, $leftColumnsDefinition['rights']);
	}

	/**
	 * @test
	 */
	public function columnMapIsInitializedWithManyToManyRelationWithPidColumn() {
		$leftColumnsDefinition = array(
			'rights' => array(
				'type' => 'select',
				'foreign_table' => 'tx_myextension_righttable',
				'foreign_table_where' => 'WHERE 1=1',
				'MM' => 'tx_myextension_mm'
			)
		);
		$mockColumnMap = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\ColumnMap', array(), array(), '', FALSE);
		$mockColumnMap->expects($this->once())->method('setRelationTableName')->with($this->equalTo('tx_myextension_mm'));
		$mockColumnMap->expects($this->once())->method('getRelationTableName')->will($this->returnValue('tx_myextension_mm'));
		$mockColumnMap->expects($this->once())->method('setrelationTablePageIdColumnName')->with($this->equalTo('pid'));
		$mockDataMapFactory = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMapFactory', array('getControlSection'), array(), '', FALSE);
		$mockDataMapFactory->expects($this->once())->method('getControlSection')->with($this->equalTo('tx_myextension_mm'))->will($this->returnValue(array('ctrl' => array('foo' => 'bar'))));
		$mockDataMapFactory->_callRef('setManyToManyRelation', $mockColumnMap, $leftColumnsDefinition['rights']);
	}

	/**
	 * @return array
	 */
	public function columnMapIsInitializedWithFieldEvaluationsForDateTimeFieldsDataProvider() {
		return array(
			'date field' => array('date', 'date'),
			'datetime field' => array('datetime', 'datetime'),
			'no date/datetime field' => array('', NULL),
		);
	}

	/**
	 * @param string $type
	 * @param NULL|string $expectedValue
	 * @test
	 * @dataProvider columnMapIsInitializedWithFieldEvaluationsForDateTimeFieldsDataProvider
	 */
	public function columnMapIsInitializedWithFieldEvaluationsForDateTimeFields($type, $expectedValue) {
		$columnDefinition = array(
			'type' => 'input',
			'dbType' => $type,
			'eval' => $type,
		);

		$mockColumnMap = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\ColumnMap', array('setDateTimeStorageFormat'), array(), '', FALSE);

		if ($expectedValue !== NULL) {
			$mockColumnMap->expects($this->once())->method('setDateTimeStorageFormat')->with($this->equalTo($type));
		} else {
			$mockColumnMap->expects($this->never())->method('setDateTimeStorageFormat');
		}

		$accessibleClassName = $this->buildAccessibleProxy('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMapFactory');
		$accessibleDataMapFactory = new $accessibleClassName();
		$accessibleDataMapFactory->_callRef('setFieldEvaluations', $mockColumnMap, $columnDefinition);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Persistence\Generic\Exception\InvalidClassException
	 */
	public function buildDataMapThrowsExceptionIfClassNameIsNotKnown() {
		$mockDataMapFactory = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMapFactory', array('getControlSection'), array(), '', FALSE);
		$cacheMock = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\VariableFrontend', array('get'), array(), '', FALSE);
		$cacheMock->expects($this->any())->method('get')->will($this->returnValue(FALSE));
		$mockDataMapFactory->_set('dataMapCache', $cacheMock);
		$mockDataMapFactory->buildDataMap('UnknownObject');
	}

	/**
	 * @test
	 */
	public function buildDataMapFetchesSubclassesRecursively() {
		$configuration = array(
			'persistence' => array(
				'classes' => array(
					'TYPO3\\CMS\\Extbase\\Domain\\Model\\FrontendUser' => array(
						'subclasses' => array(
							'Tx_SampleExt_Domain_Model_LevelOne1' => 'Tx_SampleExt_Domain_Model_LevelOne1',
							'Tx_SampleExt_Domain_Model_LevelOne2' => 'Tx_SampleExt_Domain_Model_LevelOne2'
						)
					),
					'Tx_SampleExt_Domain_Model_LevelOne1' => array(
						'subclasses' => array(
							'Tx_SampleExt_Domain_Model_LevelTwo1' => 'Tx_SampleExt_Domain_Model_LevelTwo1',
							'Tx_SampleExt_Domain_Model_LevelTwo2' => 'Tx_SampleExt_Domain_Model_LevelTwo2'
						)
					),
					'Tx_SampleExt_Domain_Model_LevelOne2' => array(
						'subclasses' => array()
					)
				)
			)
		);
		$expectedSubclasses = array(
			'Tx_SampleExt_Domain_Model_LevelOne1',
			'Tx_SampleExt_Domain_Model_LevelTwo1',
			'Tx_SampleExt_Domain_Model_LevelTwo2',
			'Tx_SampleExt_Domain_Model_LevelOne2'
		);
		/** @var $configurationManager \TYPO3\CMS\Extbase\Configuration\ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject */
		$configurationManager = $this->getMock('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager');
		$configurationManager->expects($this->once())->method('getConfiguration')->with('Framework')->will($this->returnValue($configuration));
		$dataMapFactory = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMapFactory', array('test'));
		$dataMapFactory->injectReflectionService(new \TYPO3\CMS\Extbase\Reflection\ReflectionService());
		$dataMapFactory->injectObjectManager(new \TYPO3\CMS\Extbase\Object\ObjectManager());
		$dataMapFactory->injectConfigurationManager($configurationManager);
		$cacheMock = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\VariableFrontend', array(), array(), '', FALSE);
		$cacheMock->expects($this->any())->method('get')->will($this->returnValue(FALSE));
		$dataMapFactory->_set('dataMapCache', $cacheMock);
		$dataMap = $dataMapFactory->buildDataMap('TYPO3\\CMS\\Extbase\\Domain\\Model\\FrontendUser');
		$this->assertSame($dataMap->getSubclasses(), $expectedSubclasses);
	}

	/**
	 * @return array
	 */
	public function classNameTableNameMappings() {
		return array(
			'Core classes' => array('TYPO3\\CMS\\Belog\\Domain\\Model\\LogEntry', 'tx_belog_domain_model_logentry'),
			'Extension classes' => array('ExtbaseTeam\\BlogExample\\Domain\\Model\\Blog', 'tx_blogexample_domain_model_blog'),
			'Extension classes without namespace' => array('Tx_News_Domain_Model_News', 'tx_news_domain_model_news'),
		);
	}

	/**
	 * @test
	 * @dataProvider classNameTableNameMappings
	 */
	public function resolveTableNameReturnsExpectedTablenames($className, $expected) {
		$dataMapFactory = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMapFactory', array('dummy'));
		$this->assertSame($expected, $dataMapFactory->_call('resolveTableName', $className));
	}

	/**
	 * @test
	 */
	public function createColumnMapReturnsAValidColumnMap() {
		/** @var $dataMapFactory \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory */
		$dataMapFactory = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMapFactory', array('dummy'));
		$dataMapFactory->injectObjectManager($this->objectManager);

		$this->assertEquals(
			new \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap('column', 'property'),
			$dataMapFactory->_call('createColumnMap', 'column', 'property')
		);
	}

}

?>