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

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Object\Container\Container;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnexpectedTypeException;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\Exception\UnknownPropertyTypeException;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\QueryObjectModelFactory;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryFactoryInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Session;
use TYPO3\CMS\Extbase\Reflection\ClassSchema;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;
use TYPO3\CMS\Extbase\Tests\Unit\Persistence\Generic\Mapper\Fixture\DummyChildEntity;
use TYPO3\CMS\Extbase\Tests\Unit\Persistence\Generic\Mapper\Fixture\DummyEntity;
use TYPO3\CMS\Extbase\Tests\Unit\Persistence\Generic\Mapper\Fixture\DummyParentEntity;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class DataMapperTest extends UnitTestCase
{
    /**
     * This test does not actually test anything rather than map calls both mocked methods getTargetType and mapSingleRow
     * while completely ignoring the result of the method.
     * @todo: Cover this functionality by a functional test
     *
     * @test
     */
    public function mapMapsArrayToObjectByCallingmapToObject()
    {
        $rows = [['uid' => '1234']];
        $object = new \stdClass();

        /** @var DataMapper|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject $dataMapper */
        $dataMapper = $this->getMockBuilder(DataMapper::class)
            ->setConstructorArgs([
                $this->createMock(ReflectionService::class),
                $this->createMock(QueryObjectModelFactory::class),
                $this->createMock(Session::class),
                $this->createMock(DataMapFactory::class),
                $this->createMock(QueryFactoryInterface::class),
                $this->createMock(ObjectManagerInterface::class),
                $this->createMock(EventDispatcherInterface::class),
            ])
            ->setMethods(['mapSingleRow', 'getTargetType'])
            ->getMock();

        $dataMapper->expects(self::any())->method('getTargetType')->willReturnArgument(1);
        $dataMapper->expects(self::once())->method('mapSingleRow')->with($rows[0])->willReturn($object);

        $dataMapper->map(get_class($object), $rows);
    }

    /**
     * This test does not actually test anything rather than mapSingleRow delegates functionality to
     * the persistence session which is a mock itself.
     * @todo: Cover this functionality by a functional test
     *
     * @test
     */
    public function mapSingleRowReturnsObjectFromPersistenceSessionIfAvailable()
    {
        $row = ['uid' => '1234'];
        $object = new \stdClass();
        $persistenceSession = $this->createMock(Session::class);
        $persistenceSession->expects(self::once())->method('hasIdentifier')->with('1234')->willReturn(true);
        $persistenceSession->expects(self::once())->method('getObjectByIdentifier')->with('1234')->willReturn($object);

        $dataMapper = $this->getAccessibleMock(
            DataMapper::class,
            ['dummy'],
            [
                $this->createMock(ReflectionService::class),
                $this->createMock(QueryObjectModelFactory::class),
                $persistenceSession,
                $this->createMock(DataMapFactory::class),
                $this->createMock(QueryFactoryInterface::class),
                $this->createMock(ObjectManagerInterface::class),
                $this->createMock(EventDispatcherInterface::class),
            ]
        );

        $dataMapper->_call('mapSingleRow', get_class($object), $row);
    }

    /**
     * This test has a far too complex setup to test a single unit. This actually is a functional test, accomplished
     * by mocking the whole dependency chain. This test only tests code structure while it should test functionality.
     * @todo: Cover this functionality by a functional test
     *
     * @test
     */
    public function thawPropertiesSetsPropertyValues()
    {
        $className = DummyEntity::class;
        $object = new DummyEntity();
        $row = [
            'uid' => '1234',
            'firstProperty' => 'firstValue',
            'secondProperty' => 1234,
            'thirdProperty' => 1.234,
            'fourthProperty' => false
        ];
        $columnMaps = [
            'uid' => new ColumnMap('uid', 'uid'),
            'pid' => new ColumnMap('pid', 'pid'),
            'firstProperty' => new ColumnMap('firstProperty', 'firstProperty'),
            'secondProperty' => new ColumnMap('secondProperty', 'secondProperty'),
            'thirdProperty' => new ColumnMap('thirdProperty', 'thirdProperty'),
            'fourthProperty' => new ColumnMap('fourthProperty', 'fourthProperty'),
        ];
        $dataMap = $this->getAccessibleMock(DataMap::class, ['dummy'], [$className, $className]);
        $dataMap->_set('columnMaps', $columnMaps);
        $dataMaps = [
            $className => $dataMap
        ];
        /** @var AccessibleObjectInterface|\TYPO3\CMS\Extbase\Reflection\ClassSchema $classSchema */
        $classSchema = new ClassSchema($className);
        $mockReflectionService = $this->getMockBuilder(ReflectionService::class)
            ->onlyMethods(['getClassSchema'])
            ->getMock();
        $mockReflectionService->expects(self::any())->method('getClassSchema')->willReturn($classSchema);
        $dataMapFactory = $this->getAccessibleMock(DataMapFactory::class, ['dummy'], [], '', false);
        $dataMapFactory->_set('dataMaps', $dataMaps);
        $dataMapper = $this->getAccessibleMock(
            DataMapper::class,
            ['dummy'],
            [
                $mockReflectionService,
                $this->createMock(QueryObjectModelFactory::class),
                $this->createMock(Session::class),
                $dataMapFactory,
                $this->createMock(QueryFactoryInterface::class),
                $this->createMock(ObjectManagerInterface::class),
                $this->createMock(EventDispatcherInterface::class),
            ]
        );
        $dataMapper->_call('thawProperties', $object, $row);

        self::assertEquals('firstValue', $object->firstProperty);
        self::assertEquals(1234, $object->secondProperty);
        self::assertEquals(1.234, $object->thirdProperty);
        self::assertFalse($object->fourthProperty);
    }

    /**
     * @test
     */
    public function thawPropertiesThrowsExceptionOnUnknownPropertyType(): void
    {
        $className = DummyEntity::class;
        $object = new DummyEntity();
        $row = [
            'uid' => '1234',
            'unknownType' => 'What am I?'
        ];
        $columnMaps = [
            'unknownType' => new ColumnMap('unknownType', 'unknownType'),
        ];
        $dataMap = $this->getAccessibleMock(DataMap::class, ['dummy'], [$className, $className]);
        $dataMap->_set('columnMaps', $columnMaps);
        $dataMaps = [
            $className => $dataMap
        ];
        /** @var AccessibleObjectInterface|\TYPO3\CMS\Extbase\Reflection\ClassSchema $classSchema */
        $classSchema = new ClassSchema($className);
        $mockReflectionService = $this->getMockBuilder(ReflectionService::class)
            ->onlyMethods(['getClassSchema'])
            ->getMock();
        $mockReflectionService->method('getClassSchema')->willReturn($classSchema);
        $dataMapFactory = $this->getAccessibleMock(DataMapFactory::class, ['dummy'], [], '', false);
        $dataMapFactory->_set('dataMaps', $dataMaps);
        $dataMapper = $this->getAccessibleMock(
            DataMapper::class,
            ['dummy'],
            [
                $mockReflectionService,
                $this->createMock(QueryObjectModelFactory::class),
                $this->createMock(Session::class),
                $dataMapFactory,
                $this->createMock(QueryFactoryInterface::class),
                $this->createMock(ObjectManagerInterface::class),
                $this->createMock(EventDispatcherInterface::class),
            ]
        );
        $this->expectException(UnknownPropertyTypeException::class);
        $dataMapper->_call('thawProperties', $object, $row);
    }

    /**
     * Test if fetchRelatedEager method returns NULL when $fieldValue = '' and relation type == RELATION_HAS_ONE
     *
     * This is actually a functional test as it tests multiple units along with a very specific setup of dependencies.
     * @todo: Cover this functionality by a functional test
     *
     * @test
     */
    public function fetchRelatedEagerReturnsNullForEmptyRelationHasOne()
    {
        $columnMap = new ColumnMap('columnName', 'propertyName');
        $columnMap->setTypeOfRelation(ColumnMap::RELATION_HAS_ONE);
        $dataMap = $this->getMockBuilder(DataMap::class)
            ->setMethods(['getColumnMap'])
            ->disableOriginalConstructor()
            ->getMock();
        $dataMap->expects(self::any())->method('getColumnMap')->willReturn($columnMap);
        $dataMapper = $this->getAccessibleMock(DataMapper::class, ['getDataMap'], [], '', false);
        $dataMapper->expects(self::any())->method('getDataMap')->willReturn($dataMap);
        $result = $dataMapper->_call('fetchRelatedEager', $this->createMock(AbstractEntity::class), 'SomeName', '');
        self::assertNull($result);
    }

    /**
     * Test if fetchRelatedEager method returns empty array when $fieldValue = '' and relation type != RELATION_HAS_ONE
     *
     * This is actually a functional test as it tests multiple units along with a very specific setup of dependencies.
     * @todo: Cover this functionality by a functional test
     *
     * @test
     */
    public function fetchRelatedEagerReturnsEmptyArrayForEmptyRelationNotHasOne()
    {
        $columnMap = new ColumnMap('columnName', 'propertyName');
        $columnMap->setTypeOfRelation(ColumnMap::RELATION_BELONGS_TO_MANY);
        $dataMap = $this->getMockBuilder(DataMap::class)
            ->setMethods(['getColumnMap'])
            ->disableOriginalConstructor()
            ->getMock();
        $dataMap->expects(self::any())->method('getColumnMap')->willReturn($columnMap);
        $dataMapper = $this->getAccessibleMock(DataMapper::class, ['getDataMap'], [], '', false);
        $dataMapper->expects(self::any())->method('getDataMap')->willReturn($dataMap);
        $result = $dataMapper->_call('fetchRelatedEager', $this->createMock(AbstractEntity::class), 'SomeName', '');
        self::assertEquals([], $result);
    }

    /**
     * Test if fetchRelatedEager method returns NULL when $fieldValue = ''
     * and relation type == RELATION_HAS_ONE without calling fetchRelated
     *
     * This is actually a functional test as it tests multiple units along with a very specific setup of dependencies.
     * @todo: Cover this functionality by a functional test
     *
     * @test
     */
    public function MapObjectToClassPropertyReturnsNullForEmptyRelationHasOne()
    {
        $columnMap = new ColumnMap('columnName', 'propertyName');
        $columnMap->setTypeOfRelation(ColumnMap::RELATION_HAS_ONE);
        $dataMap = $this->getMockBuilder(DataMap::class)
            ->setMethods(['getColumnMap'])
            ->disableOriginalConstructor()
            ->getMock();
        $dataMap->expects(self::any())->method('getColumnMap')->willReturn($columnMap);
        $dataMapper = $this->getAccessibleMock(DataMapper::class, ['getDataMap', 'fetchRelated'], [], '', false);
        $dataMapper->expects(self::any())->method('getDataMap')->willReturn($dataMap);
        $dataMapper->expects(self::never())->method('fetchRelated');
        $result = $dataMapper->_call('mapObjectToClassProperty', $this->createMock(AbstractEntity::class), 'SomeName', '');
        self::assertNull($result);
    }

    /**
     * Test if mapObjectToClassProperty method returns objects
     * that are already registered in the persistence session
     * without query it from the persistence layer
     *
     * This is actually a functional test as it tests multiple units along with a very specific setup of dependencies.
     * @todo: Cover this functionality by a functional test
     *
     * @test
     */
    public function mapObjectToClassPropertyReturnsExistingObjectWithoutCallingFetchRelated()
    {
        $columnMap = new ColumnMap('columnName', 'propertyName');
        $columnMap->setTypeOfRelation(ColumnMap::RELATION_HAS_ONE);
        $dataMap = $this->getMockBuilder(DataMap::class)
            ->setMethods(['getColumnMap'])
            ->disableOriginalConstructor()
            ->getMock();

        $object = new DummyParentEntity();
        $child = new DummyChildEntity();

        /** @var \TYPO3\CMS\Extbase\Reflection\ClassSchema|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject $classSchema1 */
        $classSchema1 = new ClassSchema(DummyParentEntity::class);
        $identifier = 1;

        $psrContainer = $this->getMockBuilder(ContainerInterface::class)
            ->setMethods(['has', 'get'])
            ->disableOriginalConstructor()
            ->getMock();
        $psrContainer->expects(self::any())->method('has')->willReturn(false);
        $container = new Container($psrContainer);

        $session = new Session(new Container($psrContainer));
        $session->registerObject($child, $identifier);

        $mockReflectionService = $this->getMockBuilder(ReflectionService::class)
            ->setMethods(['getClassSchema'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockReflectionService->expects(self::any())->method('getClassSchema')->willReturn($classSchema1);

        $dataMap->expects(self::any())->method('getColumnMap')->willReturn($columnMap);

        $dataMapper = $this->getAccessibleMock(
            DataMapper::class,
            ['getDataMap', 'getNonEmptyRelationValue'],
            [
                $mockReflectionService,
                $this->createMock(QueryObjectModelFactory::class),
                $session,
                $this->createMock(DataMapFactory::class),
                $this->createMock(QueryFactoryInterface::class),
                $this->createMock(ObjectManagerInterface::class),
                $this->createMock(EventDispatcherInterface::class),
            ]
        );
        $dataMapper->expects(self::any())->method('getDataMap')->willReturn($dataMap);
        $dataMapper->expects(self::never())->method('getNonEmptyRelationValue');
        $result = $dataMapper->_call('mapObjectToClassProperty', $object, 'relationProperty', $identifier);
        self::assertEquals($child, $result);
    }

    /**
     * Data provider for date checks. Date will be stored based on UTC in
     * the database. That's why it's not possible to check for explicit date
     * strings but using the date('c') conversion instead, which considers the
     * current local timezone setting.
     *
     * @return array
     */
    public function mapDateTimeHandlesDifferentFieldEvaluationsDataProvider()
    {
        return [
            'nothing' => [null, null, null],
            'timestamp' => [1, null, date('c', 1)],
            'empty date' => ['0000-00-00', 'date', null],
            'valid date' => ['2013-01-01', 'date', date('c', strtotime('2013-01-01T00:00:00+00:00'))],
            'empty datetime' => ['0000-00-00 00:00:00', 'datetime', null],
            'valid datetime' => ['2013-01-01 01:02:03', 'datetime', date('c', strtotime('2013-01-01T01:02:03+00:00'))],
        ];
    }

    /**
     * @param string|int|null $value
     * @param string|null $storageFormat
     * @param string|null $expectedValue
     * @test
     * @dataProvider mapDateTimeHandlesDifferentFieldEvaluationsDataProvider
     */
    public function mapDateTimeHandlesDifferentFieldEvaluations($value, $storageFormat, $expectedValue)
    {
        /** @var DataMapper|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject $accessibleDataMapFactory */
        $accessibleDataMapFactory = $this->getAccessibleMock(DataMapper::class, ['dummy'], [], '', false);

        /** @var $dateTime NULL|\DateTime */
        $dateTime = $accessibleDataMapFactory->_call('mapDateTime', $value, $storageFormat);

        if ($expectedValue === null) {
            self::assertNull($dateTime);
        } else {
            self::assertEquals($expectedValue, $dateTime->format('c'));
        }
    }

    /**
     * @test
     */
    public function testMapDateTimeHandlesSubclassesOfDateTime()
    {
        /** @var DataMapper|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject $accessibleDataMapFactory */
        $accessibleDataMapFactory = $this->getAccessibleMock(DataMapper::class, ['dummy'], [], '', false);
        $targetType = 'TYPO3\CMS\Extbase\Tests\Unit\Persistence\Fixture\Model\CustomDateTime';
        $date = '2013-01-01 01:02:03';
        $storageFormat = 'datetime';

        /** @var $dateTime NULL|\DateTime */
        $dateTime = $accessibleDataMapFactory->_call('mapDateTime', $date, $storageFormat, $targetType);

        self::assertInstanceOf($targetType, $dateTime);
    }

    /**
     * @test
     */
    public function getPlainValueReturnsCorrectDateTimeFormat()
    {
        /** @var DataMapper $subject */
        $subject = $this->createPartialMock(DataMapper::class, []);

        $columnMap = new ColumnMap('column_name', 'propertyName');
        $columnMap->setDateTimeStorageFormat('datetime');
        $datetimeAsString = '2013-04-15 09:30:00';
        $input = new \DateTime($datetimeAsString, new \DateTimeZone('UTC'));
        self::assertEquals('2013-04-15 09:30:00', $subject->getPlainValue($input, $columnMap));
        $columnMap->setDateTimeStorageFormat('date');
        self::assertEquals('2013-04-15', $subject->getPlainValue($input, $columnMap));
    }

    /**
     * @test
     * @dataProvider getPlainValueReturnsExpectedValuesDataProvider
     */
    public function getPlainValueReturnsExpectedValues($expectedValue, $input)
    {
        /** @var DataMapper $dataMapper */
        $dataMapper = $this->createPartialMock(DataMapper::class, []);

        self::assertSame($expectedValue, $dataMapper->getPlainValue($input));
    }

    /**
     * @return array
     */
    public function getPlainValueReturnsExpectedValuesDataProvider()
    {
        $traversableDomainObject = $this->prophesize()
            ->willImplement(\Iterator::class)
            ->willImplement(DomainObjectInterface::class);
        $traversableDomainObject->getUid()->willReturn(1);

        return [
            'datetime to timestamp' => ['1365866253', new \DateTime('@1365866253')],
            'boolean true to 1' => [1, true],
            'boolean false to 0' => [0, false],
            'NULL is handled as string' => ['NULL', null],
            'string value is returned unchanged' => ['RANDOM string', 'RANDOM string'],
            'array is flattened' => ['a,b,c', ['a', 'b', 'c']],
            'deep array is flattened' => ['a,b,c', [['a', 'b'], 'c']],
            'traversable domain object to identifier' => [1, $traversableDomainObject->reveal()],
            'integer value is returned unchanged' => [1234, 1234],
            'float is converted to string' => ['1234.56', 1234.56],
        ];
    }

    /**
     * @test
     */
    public function getPlainValueCallsGetRealInstanceOnInputIfInputIsInstanceOfLazyLoadingProxy()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionCode(1274799934);

        /** @var DataMapper $dataMapper */
        $dataMapper = $this->createPartialMock(DataMapper::class, []);
        $input = $this->createMock(LazyLoadingProxy::class);
        $input->expects(self::once())->method('_loadRealInstance')->willReturn($dataMapper);
        $dataMapper->getPlainValue($input);
    }

    /**
     * @test
     */
    public function getPlainValueCallsGetUidOnDomainObjectInterfaceInput()
    {
        /** @var DataMapper $dataMapper */
        $dataMapper = $this->createPartialMock(DataMapper::class, []);
        $input = $this->createMock(DomainObjectInterface::class);

        $input->expects(self::once())->method('getUid')->willReturn(23);
        self::assertSame(23, $dataMapper->getPlainValue($input));
    }
}
