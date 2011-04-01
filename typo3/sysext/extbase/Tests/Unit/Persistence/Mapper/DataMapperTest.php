<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Tymoteusz Motylewski <t.motylewski@gmail.com>
*  All rights reserved
*
*  Part of this class is a backport of the corresponding class of FLOW3.
*  Credits go to the v5 team.
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Testcase for Tx_Extbase_Persistence_Mapper_DataMapper
 */
class Tx_Extbase_Tests_Unit_Persistence_Mapper_DataMapperTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @test
	 */
	public function mapMapsArrayToObjectByCallingmapToObject() {
		$rows = array(array('uid' => '1234'));
		$object = new stdClass();

		$dataMapper = $this->getMock('Tx_Extbase_Persistence_Mapper_DataMapper', array('mapSingleRow','getTargetType'));
		$dataMapper->expects($this->any())->method('getTargetType')->will($this->returnArgument(1));
		$dataMapFactory = $this->getMock('Tx_Extbase_Persistence_Mapper_DataMapFactory');
		$dataMapper->injectDataMapFactory($dataMapFactory);
		$dataMapper->expects($this->once())->method('mapSingleRow')->with($rows[0])->will($this->returnValue($object));

		$dataMapper->map(get_class($object), $rows);
	}

	/**
	 * @test
	 */
	public function mapSingleRowReturnsObjectFromIdentityMapIfAvailable() {
		$row = array('uid' => '1234');
		$object = new stdClass();

		$identityMap = $this->getMock('Tx_Extbase_Persistence_IdentityMap');
		$identityMap->expects($this->once())->method('hasIdentifier')->with('1234')->will($this->returnValue(TRUE));
		$identityMap->expects($this->once())->method('getObjectByIdentifier')->with('1234')->will($this->returnValue($object));

		$dataMapper = $this->getAccessibleMock('Tx_Extbase_Persistence_Mapper_DataMapper', array('dummy'));
		$dataMapper->injectIdentityMap($identityMap);
		$dataMapper->_call('mapSingleRow', get_class($object), $row);
	}

	/**
	 * @test
	 */
	public function thawPropertiesSetsPropertyValues() {
		$className = 'Class' . md5(uniqid(mt_rand(), TRUE));
		eval('class ' . $className . ' extends Tx_Extbase_DomainObject_AbstractEntity { public $firstProperty; public $secondProperty; public $thirdProperty; public $fourthProperty; }');
		$object = new $className();

		$row = array(
			'uid' => '1234',
			'firstProperty' => 'firstValue',
			'secondProperty' => 1234,
			'thirdProperty'  => 1.234,
			'fourthProperty'  => FALSE
		);

		$columnMaps = array(
			'uid' => new Tx_Extbase_Persistence_Mapper_ColumnMap('uid','uid'),
			'pid' => new Tx_Extbase_Persistence_Mapper_ColumnMap('pid','pid'),
			'firstProperty' => new Tx_Extbase_Persistence_Mapper_ColumnMap('firstProperty','firstProperty'),
			'secondProperty' =>	new Tx_Extbase_Persistence_Mapper_ColumnMap('secondProperty','secondProperty'),
			'thirdProperty' => new Tx_Extbase_Persistence_Mapper_ColumnMap('thirdProperty','thirdProperty')
		);
		$dataMap = $this->getAccessibleMock('Tx_Extbase_Persistence_Mapper_DataMap', array('dummy'), array($className, $className));
		$dataMap->_set('columnMaps', $columnMaps);

		$dataMaps = array(
			$className => $dataMap
		);

		$classSchema = new Tx_Extbase_Reflection_ClassSchema($className);
		$classSchema->injectTypeHandlingService(new Tx_Extbase_Service_TypeHandlingService());
		$classSchema->addProperty('pid', 'integer');
		$classSchema->addProperty('uid', 'integer');
		$classSchema->addProperty('firstProperty', 'string');
		$classSchema->addProperty('secondProperty', 'integer');
		$classSchema->addProperty('thirdProperty', 'float');
		$classSchema->addProperty('fourthProperty', 'boolean');

		$mockReflectionService = $this->getMock('Tx_Extbase_Reflection_Service', array('getClassSchema'));
		$mockReflectionService->expects($this->any())->method('getClassSchema')->will($this->returnValue($classSchema));

		$dataMapper = $this->getAccessibleMock('Tx_Extbase_Persistence_Mapper_DataMapper', array('dummy'));
		$dataMapper->_set('dataMaps', $dataMaps);
		$dataMapper->injectReflectionService($mockReflectionService);
		$dataMapper->_call('thawProperties', $object, $row);

		$this->assertAttributeEquals('firstValue', 'firstProperty', $object);
		$this->assertAttributeEquals(1234, 'secondProperty', $object);
		$this->assertAttributeEquals(1.234, 'thirdProperty', $object);
		$this->assertAttributeEquals(FALSE, 'fourthProperty', $object);
	}

	/**
	 * Test if fetchRelatedEager method returns NULL when $fieldValue = '' and relation type == RELATION_HAS_ONE
	 * @test
	 */
	public function fetchRelatedEagerReturnsNullForEmptyRelationHasOne() {

		$columnMap = new Tx_Extbase_Persistence_Mapper_ColumnMap('columnName', 'propertyName');
		$columnMap->setTypeOfRelation(Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_ONE);

		$dataMap = $this->getMock('Tx_Extbase_Persistence_Mapper_DataMap', array('getColumnMap'));
		$dataMap->expects($this->any())->method('getColumnMap')->will($this->returnValue($columnMap));

		$dataMapper = $this->getAccessibleMock('Tx_Extbase_Persistence_Mapper_DataMapper', array('getDataMap'));
		$dataMapper->expects($this->any())->method('getDataMap')->will($this->returnValue($dataMap));

		$result = $dataMapper->_call('fetchRelatedEager', $this->getMock('Tx_Extbase_DomainObject_AbstractEntity'), 'SomeName', '');
		$this->assertEquals(NULL, $result);
	}

	/**
	 * Test if fetchRelatedEager method returns empty array when $fieldValue = '' and relation type != RELATION_HAS_ONE
	 * @test
	 */
	public function fetchRelatedEagerReturnsEmptyArrayForEmptyRelationNotHasOne() {

		$columnMap = new Tx_Extbase_Persistence_Mapper_ColumnMap('columnName', 'propertyName');
		$columnMap->setTypeOfRelation(Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_BELONGS_TO_MANY);

		$dataMap = $this->getMock('Tx_Extbase_Persistence_Mapper_DataMap', array('getColumnMap'));
		$dataMap->expects($this->any())->method('getColumnMap')->will($this->returnValue($columnMap));

		$dataMapper = $this->getAccessibleMock('Tx_Extbase_Persistence_Mapper_DataMapper', array('getDataMap'));
		$dataMapper->expects($this->any())->method('getDataMap')->will($this->returnValue($dataMap));

		$result = $dataMapper->_call('fetchRelatedEager', $this->getMock('Tx_Extbase_DomainObject_AbstractEntity'), 'SomeName', '');
		$this->assertEquals(array(), $result);
	}
}
?>