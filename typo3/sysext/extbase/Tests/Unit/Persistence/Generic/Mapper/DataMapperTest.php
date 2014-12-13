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

use TYPO3\CMS\Core\Tests\AccessibleObjectInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;

/**
 * Test case
 */
class DataMapperTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function mapMapsArrayToObjectByCallingmapToObject() {
		$rows = array(array('uid' => '1234'));
		$object = new \stdClass();
		/** @var DataMapper|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject $dataMapper */
		$dataMapper = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper::class, array('mapSingleRow', 'getTargetType'));
		$dataMapper->expects($this->any())->method('getTargetType')->will($this->returnArgument(1));
		$dataMapFactory = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory::class);
		$dataMapper->_set('dataMapFactory', $dataMapFactory);
		$dataMapper->expects($this->once())->method('mapSingleRow')->with($rows[0])->will($this->returnValue($object));
		$dataMapper->map(get_class($object), $rows);
	}

	/**
	 * @test
	 */
	public function mapSingleRowReturnsObjectFromIdentityMapIfAvailable() {
		$row = array('uid' => '1234');
		$object = new \stdClass();
		$identityMap = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\IdentityMap::class);
		$identityMap->expects($this->once())->method('hasIdentifier')->with('1234')->will($this->returnValue(TRUE));
		$identityMap->expects($this->once())->method('getObjectByIdentifier')->with('1234')->will($this->returnValue($object));
		$dataMapper = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper::class, array('dummy'));
		$dataMapper->_set('identityMap', $identityMap);
		$dataMapper->_call('mapSingleRow', get_class($object), $row);
	}

	/**
	 * @test
	 */
	public function thawPropertiesSetsPropertyValues() {
		$className = 'Class' . md5(uniqid(mt_rand(), TRUE));
		$classNameWithNS = __NAMESPACE__ . '\\' . $className;
		eval('namespace ' . __NAMESPACE__ . '; class ' . $className . ' extends \\' . \TYPO3\CMS\Extbase\DomainObject\AbstractEntity::class . ' {
		 public $firstProperty; public $secondProperty; public $thirdProperty; public $fourthProperty;
		 }'
		);
		$object = new $classNameWithNS();
		$row = array(
			'uid' => '1234',
			'firstProperty' => 'firstValue',
			'secondProperty' => 1234,
			'thirdProperty' => 1.234,
			'fourthProperty' => FALSE
		);
		$columnMaps = array(
			'uid' => new ColumnMap('uid', 'uid'),
			'pid' => new ColumnMap('pid', 'pid'),
			'firstProperty' => new ColumnMap('firstProperty', 'firstProperty'),
			'secondProperty' => new ColumnMap('secondProperty', 'secondProperty'),
			'thirdProperty' => new ColumnMap('thirdProperty', 'thirdProperty')
		);
		$dataMap = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap::class, array('dummy'), array($className, $className));
		$dataMap->_set('columnMaps', $columnMaps);
		$dataMaps = array(
			$classNameWithNS => $dataMap
		);
		/** @var AccessibleObjectInterface|\TYPO3\CMS\Extbase\Reflection\ClassSchema $classSchema */
		$classSchema = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Reflection\ClassSchema::class, array('dummy'), array($classNameWithNS));
		$classSchema->addProperty('pid', 'integer');
		$classSchema->addProperty('uid', 'integer');
		$classSchema->addProperty('firstProperty', 'string');
		$classSchema->addProperty('secondProperty', 'integer');
		$classSchema->addProperty('thirdProperty', 'float');
		$classSchema->addProperty('fourthProperty', 'boolean');
		$mockReflectionService = $this->getMock(\TYPO3\CMS\Extbase\Reflection\ReflectionService::class, array('getClassSchema'));
		$mockReflectionService->expects($this->any())->method('getClassSchema')->will($this->returnValue($classSchema));
		$dataMapper = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper::class, array('dummy'));
		$dataMapper->_set('dataMaps', $dataMaps);
		$dataMapper->_set('reflectionService', $mockReflectionService);
		$dataMapper->_call('thawProperties', $object, $row);
		$this->assertAttributeEquals('firstValue', 'firstProperty', $object);
		$this->assertAttributeEquals(1234, 'secondProperty', $object);
		$this->assertAttributeEquals(1.234, 'thirdProperty', $object);
		$this->assertAttributeEquals(FALSE, 'fourthProperty', $object);
	}

	/**
	 * Test if fetchRelatedEager method returns NULL when $fieldValue = '' and relation type == RELATION_HAS_ONE
	 *
	 * @test
	 */
	public function fetchRelatedEagerReturnsNullForEmptyRelationHasOne() {
		$columnMap = new ColumnMap('columnName', 'propertyName');
		$columnMap->setTypeOfRelation(ColumnMap::RELATION_HAS_ONE);
		$dataMap = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap::class, array('getColumnMap'), array(), '', FALSE);
		$dataMap->expects($this->any())->method('getColumnMap')->will($this->returnValue($columnMap));
		$dataMapper = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper::class, array('getDataMap'));
		$dataMapper->expects($this->any())->method('getDataMap')->will($this->returnValue($dataMap));
		$result = $dataMapper->_call('fetchRelatedEager', $this->getMock(\TYPO3\CMS\Extbase\DomainObject\AbstractEntity::class), 'SomeName', '');
		$this->assertEquals(NULL, $result);
	}

	/**
	 * Test if fetchRelatedEager method returns empty array when $fieldValue = '' and relation type != RELATION_HAS_ONE
	 *
	 * @test
	 */
	public function fetchRelatedEagerReturnsEmptyArrayForEmptyRelationNotHasOne() {
		$columnMap = new ColumnMap('columnName', 'propertyName');
		$columnMap->setTypeOfRelation(ColumnMap::RELATION_BELONGS_TO_MANY);
		$dataMap = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap::class, array('getColumnMap'), array(), '', FALSE);
		$dataMap->expects($this->any())->method('getColumnMap')->will($this->returnValue($columnMap));
		$dataMapper = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper::class, array('getDataMap'));
		$dataMapper->expects($this->any())->method('getDataMap')->will($this->returnValue($dataMap));
		$result = $dataMapper->_call('fetchRelatedEager', $this->getMock(\TYPO3\CMS\Extbase\DomainObject\AbstractEntity::class), 'SomeName', '');
		$this->assertEquals(array(), $result);
	}

	/**
	 * Test if fetchRelatedEager method returns NULL when $fieldValue = ''
	 * and relation type == RELATION_HAS_ONE without calling fetchRelated
	 *
	 * @test
	 */
	public function mapObjectToClassPropertyReturnsNullForEmptyRelationHasOne() {
		$columnMap = new ColumnMap('columnName', 'propertyName');
		$columnMap->setTypeOfRelation(ColumnMap::RELATION_HAS_ONE);
		$dataMap = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap::class, array('getColumnMap'), array(), '', FALSE);
		$dataMap->expects($this->any())->method('getColumnMap')->will($this->returnValue($columnMap));
		$dataMapper = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper::class, array('getDataMap', 'fetchRelated'));
		$dataMapper->expects($this->any())->method('getDataMap')->will($this->returnValue($dataMap));
		$dataMapper->expects($this->never())->method('fetchRelated');
		$result = $dataMapper->_call('mapObjectToClassProperty', $this->getMock(\TYPO3\CMS\Extbase\DomainObject\AbstractEntity::class), 'SomeName', '');
		$this->assertEquals(NULL, $result);
	}

	/**
	 * Test if mapObjectToClassProperty method returns objects
	 * that are already registered in the persistence session
	 * without query it from the persistence layer
	 *
	 * @test
	 */
	public function mapObjectToClassPropertyReturnsExistingObjectWithoutCallingFetchRelated() {
		$columnMap = new ColumnMap('columnName', 'propertyName');
		$columnMap->setTypeOfRelation(ColumnMap::RELATION_HAS_ONE);
		$dataMap = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap::class, array('getColumnMap'), array(), '', FALSE);

		$className = 'Class1' . md5(uniqid(mt_rand(), TRUE));
		$classNameWithNS = __NAMESPACE__ . '\\' . $className;
		eval('namespace ' . __NAMESPACE__ . '; class ' . $className . ' extends \\' . \TYPO3\CMS\Extbase\DomainObject\AbstractEntity::class . ' { public $relationProperty; }');
		$object = new $classNameWithNS();

		$className2 = 'Class2' . md5(uniqid(mt_rand(), TRUE));
		$className2WithNS = __NAMESPACE__ . '\\' . $className2;
		eval('namespace ' . __NAMESPACE__ . '; class ' . $className2 . ' extends \\' . \TYPO3\CMS\Extbase\DomainObject\AbstractEntity::class . ' { }');
		$child = new $className2WithNS();
		$classSchema2 = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Reflection\ClassSchema::class, array('dummy'), array($className2WithNS));

		/** @var \TYPO3\CMS\Extbase\Reflection\ClassSchema|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject $classSchema1 */
		$classSchema1 = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Reflection\ClassSchema::class, array('dummy'), array($classNameWithNS));
		$classSchema1->addProperty('relationProperty', $className2WithNS);
		$identifier = 1;

		$session = new \TYPO3\CMS\Extbase\Persistence\Generic\Session();
		$session->registerObject($child, $identifier);

		$mockReflectionService = $this->getMock(\TYPO3\CMS\Extbase\Reflection\ReflectionService::class, array('getClassSchema'), array(), '', FALSE);
		$mockReflectionService->expects($this->any())->method('getClassSchema')->will($this->returnValue($classSchema1));

		$dataMap->expects($this->any())->method('getColumnMap')->will($this->returnValue($columnMap));
		$dataMapper = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper::class, array('getDataMap', 'getNonEmptyRelationValue'));
		$dataMapper->_set('reflectionService', $mockReflectionService);
		$dataMapper->_set('persistenceSession', $session);
		$dataMapper->expects($this->any())->method('getDataMap')->will($this->returnValue($dataMap));
		$dataMapper->expects($this->never())->method('getNonEmptyRelationValue');
		$result = $dataMapper->_call('mapObjectToClassProperty', $object, 'relationProperty', $identifier);
		$this->assertEquals($child, $result);
	}

	/**
	 * Data provider for date checks. Date will be stored based on UTC in
	 * the database. That's why it's not possible to check for explicit date
	 * strings but using the date('c') conversion instead, which considers the
	 * current local timezone setting.
	 *
	 * @return array
	 */
	public function mapDateTimeHandlesDifferentFieldEvaluationsDataProvider() {
		return array(
			'nothing' => array(NULL, NULL, NULL),
			'timestamp' => array(1, NULL, date('c', 1)),
			'empty date' => array('0000-00-00', 'date', NULL),
			'valid date' => array('2013-01-01', 'date', date('c', strtotime('2013-01-01T00:00:00+00:00'))),
			'empty datetime' => array('0000-00-00 00:00:00', 'datetime', NULL),
			'valid datetime' => array('2013-01-01 01:02:03', 'datetime', date('c', strtotime('2013-01-01T01:02:03+00:00'))),
		);
	}

	/**
	 * @param NULL|string|int $value
	 * @param NULL|string $storageFormat
	 * @param NULL|string $expectedValue
	 * @test
	 * @dataProvider mapDateTimeHandlesDifferentFieldEvaluationsDataProvider
	 */
	public function mapDateTimeHandlesDifferentFieldEvaluations($value, $storageFormat, $expectedValue) {
		$accessibleClassName = $this->buildAccessibleProxy(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper::class);

		/** @var DataMapper|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject $accessibleDataMapFactory */
		$accessibleDataMapFactory = new $accessibleClassName();

		/** @var $dateTime NULL|\DateTime */
		$dateTime = $accessibleDataMapFactory->_callRef('mapDateTime', $value, $storageFormat);

		if ($expectedValue === NULL) {
			$this->assertNull($dateTime);
		} else {
			$this->assertEquals($expectedValue, $dateTime->format('c'));
		}
	}

	/**
	 * @test
	 */
	public function getPlainValueReturnsCorrectDateTimeFormat() {
		$subject = new DataMapper();
		$columnMap = new ColumnMap('column_name', 'propertyName');
		$columnMap->setDateTimeStorageFormat('datetime');
		$datetimeAsString = '2013-04-15 09:30:00';
		$input = new \DateTime($datetimeAsString);
		$this->assertEquals('2013-04-15 09:30:00', $subject->getPlainValue($input, $columnMap));
		$columnMap->setDateTimeStorageFormat('date');
		$this->assertEquals('2013-04-15', $subject->getPlainValue($input, $columnMap));
	}

	/**
	 * @test
	 * @dataProvider getPlainValueReturnsExpectedValuesDataProvider
	 */
	public function getPlainValueReturnsExpectedValues($expectedValue, $input) {
		$dataMapper = new DataMapper();
		$this->assertSame($expectedValue, $dataMapper->getPlainValue($input));
	}

	/**
	 * @return array
	 */
	public function getPlainValueReturnsExpectedValuesDataProvider() {
		return array(
			'datetime to timestamp' => array('1365866253', new \DateTime('@1365866253')),
			'boolean true to 1' => array(1, TRUE),
			'boolean false to 0' => array(0, FALSE),
			'NULL is handled' => array('NULL', NULL),
			'plain value is returned unchanged' => array('RANDOM string', 'RANDOM string'),
			'array is flattened' => array('a,b,c', array('a', 'b', 'c')),
			'deep array is flattened' => array('a,b,c', array(array('a', 'b'), 'c')),
		);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnexpectedTypeException
	 */
	public function getPlainValueCallsGetRealInstanceOnInputIfInputIsInstanceOfLazyLoadingProxy() {
		$dataMapper = new DataMapper();
		$input = $this->getMock(
			\TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy::class,
			array(),
			array(),
			'',
			FALSE
		);
		$input->expects($this->once())->method('_loadRealInstance')->will($this->returnValue($dataMapper));
		$dataMapper->getPlainValue($input);
	}

	/**
	 * @test
	 */
	public function getPlainValueCallsGetUidOnDomainObjectInterfaceInput() {
		$dataMapper = new DataMapper();
		$input = $this->getMock(
			\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface::class,
			array(),
			array(),
			'',
			FALSE
		);
		$input->expects($this->once())->method('getUid')->will($this->returnValue(23));
		$this->assertSame(23, $dataMapper->getPlainValue($input));
	}
}