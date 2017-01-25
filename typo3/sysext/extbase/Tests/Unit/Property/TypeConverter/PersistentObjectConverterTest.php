<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Property\TypeConverter;

/*                                                                        *
 * This script belongs to the Extbase framework.                          *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;
use TYPO3\CMS\Extbase\Tests\Unit\Property\TypeConverter\Fixtures\PersistentObjectEntityFixture;
use TYPO3\CMS\Extbase\Tests\Unit\Property\TypeConverter\Fixtures\PersistentObjectFixture;
use TYPO3\CMS\Extbase\Tests\Unit\Property\TypeConverter\Fixtures\PersistentObjectValueObjectFixture;

/**
 * Test case
 */
class PersistentObjectConverterTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Property\TypeConverterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $converter;

    /**
     * @var \TYPO3\CMS\Extbase\Reflection\ReflectionService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockReflectionService;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockPersistenceManager;

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockObjectManager;

    /**
     * @var \TYPO3\CMS\Extbase\Object\Container\Container|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockContainer;

    /**
     * @throws \InvalidArgumentException
     * @throws \PHPUnit_Framework_Exception
     * @throws \RuntimeException
     */
    protected function setUp()
    {
        $this->converter = new PersistentObjectConverter();
        $this->mockReflectionService = $this->getMock(\TYPO3\CMS\Extbase\Reflection\ReflectionService::class);
        $this->inject($this->converter, 'reflectionService', $this->mockReflectionService);

        $this->mockPersistenceManager = $this->getMock(\TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface::class);
        $this->inject($this->converter, 'persistenceManager', $this->mockPersistenceManager);

        $this->mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $this->mockObjectManager->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(function () {
                $args = func_get_args();
                $reflectionClass = new \ReflectionClass(array_shift($args));
                if (empty($args)) {
                    return $reflectionClass->newInstance();
                } else {
                    return $reflectionClass->newInstanceArgs($args);
                }
            }));
        $this->inject($this->converter, 'objectManager', $this->mockObjectManager);

        $this->mockContainer = $this->getMock(\TYPO3\CMS\Extbase\Object\Container\Container::class);
        $this->inject($this->converter, 'objectContainer', $this->mockContainer);
    }

    /**
     * @test
     */
    public function checkMetadata()
    {
        $this->assertEquals(['integer', 'string', 'array'], $this->converter->getSupportedSourceTypes(), 'Source types do not match');
        $this->assertEquals('object', $this->converter->getSupportedTargetType(), 'Target type does not match');
        $this->assertEquals(1, $this->converter->getPriority(), 'Priority does not match');
    }

    /**
     * @return array
     */
    public function dataProviderForCanConvert()
    {
        return [
            [true, false, true],
            // is entity => can convert
            [false, true, true],
            // is valueobject => can convert
            [false, false, false],
            // is no entity and no value object => can not convert
        ];
    }

    /**
     * @test
     * @dataProvider dataProviderForCanConvert
     */
    public function canConvertFromReturnsTrueIfClassIsTaggedWithEntityOrValueObject($isEntity, $isValueObject, $expected)
    {
        $className = PersistentObjectFixture::class;

        if ($isEntity) {
            $className = PersistentObjectEntityFixture::class;
        } elseif ($isValueObject) {
            $className = PersistentObjectValueObjectFixture::class;
        }
        $this->assertEquals($expected, $this->converter->canConvertFrom('myInputData', $className));
    }

    /**
     * @test
     */
    public function getSourceChildPropertiesToBeConvertedReturnsAllPropertiesExceptTheIdentityProperty()
    {
        $source = [
            'k1' => 'v1',
            '__identity' => 'someIdentity',
            'k2' => 'v2'
        ];
        $expected = [
            'k1' => 'v1',
            'k2' => 'v2'
        ];
        $this->assertEquals($expected, $this->converter->getSourceChildPropertiesToBeConverted($source));
    }

    /**
     * @test
     */
    public function getTypeOfChildPropertyShouldUseReflectionServiceToDetermineType()
    {
        $mockSchema = $this->getMockBuilder(\TYPO3\CMS\Extbase\Reflection\ClassSchema::class)->disableOriginalConstructor()->getMock();
        $this->mockReflectionService->expects($this->any())->method('getClassSchema')->with('TheTargetType')->will($this->returnValue($mockSchema));

        $this->mockContainer->expects($this->any())->method('getImplementationClassName')->will($this->returnValue('TheTargetType'));
        $mockSchema->expects($this->any())->method('hasProperty')->with('thePropertyName')->will($this->returnValue(true));
        $mockSchema->expects($this->any())->method('getProperty')->with('thePropertyName')->will($this->returnValue([
            'type' => 'TheTypeOfSubObject',
            'elementType' => null
        ]));
        $configuration = $this->buildConfiguration([]);
        $this->assertEquals('TheTypeOfSubObject', $this->converter->getTypeOfChildProperty('TheTargetType', 'thePropertyName', $configuration));
    }

    /**
     * @test
     */
    public function getTypeOfChildPropertyShouldUseConfiguredTypeIfItWasSet()
    {
        $this->mockReflectionService->expects($this->never())->method('getClassSchema');
        $this->mockContainer->expects($this->any())->method('getImplementationClassName')->will($this->returnValue('foo'));

        $configuration = $this->buildConfiguration([]);
        $configuration->forProperty('thePropertyName')->setTypeConverterOption(\TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_TARGET_TYPE, 'Foo\Bar');
        $this->assertEquals('Foo\Bar', $this->converter->getTypeOfChildProperty('foo', 'thePropertyName', $configuration));
    }

    /**
     * @test
     */
    public function convertFromShouldFetchObjectFromPersistenceIfUuidStringIsGiven()
    {
        $identifier = '17';
        $object = new \stdClass();

        $this->mockPersistenceManager->expects($this->any())->method('getObjectByIdentifier')->with($identifier)->will($this->returnValue($object));
        $this->assertSame($object, $this->converter->convertFrom($identifier, 'MySpecialType'));
    }

    /**
     * @test
     */
    public function convertFromShouldFetchObjectFromPersistenceIfuidStringIsGiven()
    {
        $identifier = '17';
        $object = new \stdClass();

        $this->mockPersistenceManager->expects($this->any())->method('getObjectByIdentifier')->with($identifier)->will($this->returnValue($object));
        $this->assertSame($object, $this->converter->convertFrom($identifier, 'MySpecialType'));
    }

    /**
     * @test
     */
    public function convertFromShouldFetchObjectFromPersistenceIfOnlyIdentityArrayGiven()
    {
        $identifier = '12345';
        $object = new \stdClass();

        $source = [
            '__identity' => $identifier
        ];
        $this->mockPersistenceManager->expects($this->any())->method('getObjectByIdentifier')->with($identifier)->will($this->returnValue($object));
        $this->assertSame($object, $this->converter->convertFrom($source, 'MySpecialType'));
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Extbase\Property\Exception\InvalidPropertyMappingConfigurationException
     */
    public function convertFromShouldThrowExceptionIfObjectNeedsToBeModifiedButConfigurationIsNotSet()
    {
        $identifier = '12345';
        $object = new \stdClass();
        $object->someProperty = 'asdf';

        $source = [
            '__identity' => $identifier,
            'foo' => 'bar'
        ];
        $this->mockPersistenceManager->expects($this->any())->method('getObjectByIdentifier')->with($identifier)->will($this->returnValue($object));
        $this->converter->convertFrom($source, 'MySpecialType');
    }

    /**
     * @param array $typeConverterOptions
     * @return \TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration
     */
    protected function buildConfiguration($typeConverterOptions)
    {
        $configuration = new \TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration();
        $configuration->setTypeConverterOptions(\TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::class, $typeConverterOptions);
        return $configuration;
    }

    /**
     * @param int $numberOfResults
     * @param Matcher $howOftenIsGetFirstCalled
     * @return \stdClass
     */
    public function setupMockQuery($numberOfResults, $howOftenIsGetFirstCalled)
    {
        $mockClassSchema = $this->getMock(\TYPO3\CMS\Extbase\Reflection\ClassSchema::class, [], ['Dummy']);
        $mockClassSchema->expects($this->any())->method('getIdentityProperties')->will($this->returnValue(['key1' => 'someType']));
        $this->mockReflectionService->expects($this->any())->method('getClassSchema')->with('SomeType')->will($this->returnValue($mockClassSchema));

        $mockConstraint = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\Comparison::class)->disableOriginalConstructor()->getMock();

        $mockObject = new \stdClass();
        $mockQuery = $this->getMock(\TYPO3\CMS\Extbase\Persistence\QueryInterface::class);
        $mockQueryResult = $this->getMock(\TYPO3\CMS\Extbase\Persistence\QueryResultInterface::class);
        $mockQueryResult->expects($this->any())->method('count')->will($this->returnValue($numberOfResults));
        $mockQueryResult->expects($howOftenIsGetFirstCalled)->method('getFirst')->will($this->returnValue($mockObject));
        $mockQuery->expects($this->any())->method('equals')->with('key1', 'value1')->will($this->returnValue($mockConstraint));
        $mockQuery->expects($this->any())->method('matching')->with($mockConstraint)->will($this->returnValue($mockQuery));
        $mockQuery->expects($this->any())->method('execute')->will($this->returnValue($mockQueryResult));

        $this->mockPersistenceManager->expects($this->any())->method('createQueryForType')->with('SomeType')->will($this->returnValue($mockQuery));

        return $mockObject;
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Extbase\Property\Exception\TargetNotFoundException
     */
    public function convertFromShouldReturnExceptionIfNoMatchingObjectWasFound()
    {
        $this->setupMockQuery(0, $this->never());
        $this->mockReflectionService->expects($this->never())->method('getClassSchema');

        $source = [
            '__identity' => 123
        ];
        $actual = $this->converter->convertFrom($source, 'SomeType');
        $this->assertNull($actual);
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Extbase\Property\Exception\DuplicateObjectException
     */
    public function convertFromShouldThrowExceptionIfMoreThanOneObjectWasFound()
    {
        $this->setupMockQuery(2, $this->never());

        $source = [
            '__identity' => 666
        ];
        $this->mockPersistenceManager->expects($this->any())->method('getObjectByIdentifier')->with(666)->will($this->throwException(new \TYPO3\CMS\Extbase\Property\Exception\DuplicateObjectException));
        $this->converter->convertFrom($source, 'SomeType');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Extbase\Property\Exception\InvalidPropertyMappingConfigurationException
     */
    public function convertFromShouldThrowExceptionIfObjectNeedsToBeCreatedButConfigurationIsNotSet()
    {
        $source = [
            'foo' => 'bar'
        ];
        $this->converter->convertFrom($source, 'MySpecialType');
    }

    /**
     * @test
     */
    public function convertFromShouldCreateObject()
    {
        $source = [
            'propertyX' => 'bar'
        ];
        $convertedChildProperties = [
            'property1' => 'bar'
        ];
        $expectedObject = new \TYPO3\CMS\Extbase\Tests\Fixture\ClassWithSetters();
        $expectedObject->property1 = 'bar';

        $this->mockReflectionService->expects($this->any())->method('getMethodParameters')->with(\TYPO3\CMS\Extbase\Tests\Fixture\ClassWithSetters::class, '__construct')->will($this->throwException(new \ReflectionException()));
        $this->mockObjectManager->expects($this->any())->method('getClassNameByObjectName')->with(\TYPO3\CMS\Extbase\Tests\Fixture\ClassWithSetters::class)->will($this->returnValue(\TYPO3\CMS\Extbase\Tests\Fixture\ClassWithSetters::class));
        $configuration = $this->buildConfiguration([PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => true]);
        $result = $this->converter->convertFrom($source, \TYPO3\CMS\Extbase\Tests\Fixture\ClassWithSetters::class, $convertedChildProperties, $configuration);
        $this->assertEquals($expectedObject, $result);
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Extbase\Property\Exception\InvalidTargetException
     */
    public function convertFromShouldThrowExceptionIfPropertyOnTargetObjectCouldNotBeSet()
    {
        $source = [
            'propertyX' => 'bar'
        ];
        $object = new \TYPO3\CMS\Extbase\Tests\Fixture\ClassWithSetters();
        $convertedChildProperties = [
            'propertyNotExisting' => 'bar'
        ];
        $this->mockObjectManager->expects($this->any())->method('get')->with(\TYPO3\CMS\Extbase\Tests\Fixture\ClassWithSetters::class)->will($this->returnValue($object));
        $this->mockReflectionService->expects($this->any())->method('getMethodParameters')->with(\TYPO3\CMS\Extbase\Tests\Fixture\ClassWithSetters::class, '__construct')->will($this->returnValue([]));
        $configuration = $this->buildConfiguration([PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => true]);
        $result = $this->converter->convertFrom($source, \TYPO3\CMS\Extbase\Tests\Fixture\ClassWithSetters::class, $convertedChildProperties, $configuration);
        $this->assertSame($object, $result);
    }

    /**
     * @test
     */
    public function convertFromShouldCreateObjectWhenThereAreConstructorParameters()
    {
        $source = [
            'propertyX' => 'bar'
        ];
        $convertedChildProperties = [
            'property1' => 'param1',
            'property2' => 'bar'
        ];
        $expectedObject = new \TYPO3\CMS\Extbase\Tests\Fixture\ClassWithSettersAndConstructor('param1');
        $expectedObject->setProperty2('bar');

        $this->mockReflectionService
                ->expects($this->any())
                ->method('getMethodParameters')
                ->with(\TYPO3\CMS\Extbase\Tests\Fixture\ClassWithSettersAndConstructor::class, '__construct')
                ->will($this->returnValue([
                    'property1' => ['optional' => false]
                ]));
        $this->mockReflectionService
                ->expects($this->any())
                ->method('hasMethod')
                ->with(\TYPO3\CMS\Extbase\Tests\Fixture\ClassWithSettersAndConstructor::class, '__construct')
                ->will($this->returnValue(true));
        $this->mockObjectManager->expects($this->any())->method('getClassNameByObjectName')->with(\TYPO3\CMS\Extbase\Tests\Fixture\ClassWithSettersAndConstructor::class)->will($this->returnValue(\TYPO3\CMS\Extbase\Tests\Fixture\ClassWithSettersAndConstructor::class));
        $this->mockContainer->expects($this->any())->method('getImplementationClassName')->will($this->returnValue(\TYPO3\CMS\Extbase\Tests\Fixture\ClassWithSettersAndConstructor::class));
        $configuration = $this->buildConfiguration([PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => true]);
        $result = $this->converter->convertFrom($source, \TYPO3\CMS\Extbase\Tests\Fixture\ClassWithSettersAndConstructor::class, $convertedChildProperties, $configuration);
        $this->assertEquals($expectedObject, $result);
        $this->assertEquals('bar', $expectedObject->getProperty2());
    }

    /**
     * @test
     */
    public function convertFromShouldCreateObjectWhenThereAreOptionalConstructorParameters()
    {
        $source = [
            'propertyX' => 'bar'
        ];
        $expectedObject = new \TYPO3\CMS\Extbase\Tests\Fixture\ClassWithSettersAndConstructor('thisIsTheDefaultValue');

        $this->mockReflectionService->expects($this->any())->method('getMethodParameters')->with(\TYPO3\CMS\Extbase\Tests\Fixture\ClassWithSettersAndConstructor::class, '__construct')->will($this->returnValue([
            'property1' => ['optional' => true, 'defaultValue' => 'thisIsTheDefaultValue']
        ]));
        $this->mockReflectionService
                ->expects($this->any())
                ->method('hasMethod')
                ->with(\TYPO3\CMS\Extbase\Tests\Fixture\ClassWithSettersAndConstructor::class, '__construct')
                ->will($this->returnValue(true));
        $this->mockObjectManager->expects($this->any())->method('getClassNameByObjectName')->with(\TYPO3\CMS\Extbase\Tests\Fixture\ClassWithSettersAndConstructor::class)->will($this->returnValue(\TYPO3\CMS\Extbase\Tests\Fixture\ClassWithSettersAndConstructor::class));
        $this->mockContainer->expects($this->any())->method('getImplementationClassName')->will($this->returnValue(\TYPO3\CMS\Extbase\Tests\Fixture\ClassWithSettersAndConstructor::class));
        $configuration = $this->buildConfiguration([PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => true]);
        $result = $this->converter->convertFrom($source, \TYPO3\CMS\Extbase\Tests\Fixture\ClassWithSettersAndConstructor::class, [], $configuration);
        $this->assertEquals($expectedObject, $result);
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Extbase\Property\Exception\InvalidTargetException
     */
    public function convertFromShouldThrowExceptionIfRequiredConstructorParameterWasNotFound()
    {
        $source = [
            'propertyX' => 'bar'
        ];
        $object = new \TYPO3\CMS\Extbase\Tests\Fixture\ClassWithSettersAndConstructor('param1');
        $convertedChildProperties = [
            'property2' => 'bar'
        ];

        $this->mockReflectionService->expects($this->any())->method('getMethodParameters')->with(\TYPO3\CMS\Extbase\Tests\Fixture\ClassWithSettersAndConstructor::class, '__construct')->will($this->returnValue([
            'property1' => ['optional' => false]
        ]));
        $this->mockReflectionService
                ->expects($this->any())
                ->method('hasMethod')
                ->with(\TYPO3\CMS\Extbase\Tests\Fixture\ClassWithSettersAndConstructor::class, '__construct')
                ->will($this->returnValue(true));
        $this->mockObjectManager->expects($this->any())->method('getClassNameByObjectName')->with(\TYPO3\CMS\Extbase\Tests\Fixture\ClassWithSettersAndConstructor::class)->will($this->returnValue(\TYPO3\CMS\Extbase\Tests\Fixture\ClassWithSettersAndConstructor::class));
        $this->mockContainer->expects($this->any())->method('getImplementationClassName')->will($this->returnValue(\TYPO3\CMS\Extbase\Tests\Fixture\ClassWithSettersAndConstructor::class));
        $configuration = $this->buildConfiguration([PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => true]);
        $result = $this->converter->convertFrom($source, \TYPO3\CMS\Extbase\Tests\Fixture\ClassWithSettersAndConstructor::class, $convertedChildProperties, $configuration);
        $this->assertSame($object, $result);
    }

    /**
     * @test
     */
    public function convertFromShouldReturnNullForEmptyString()
    {
        $source = '';
        $result = $this->converter->convertFrom($source, \TYPO3\CMS\Extbase\Tests\Fixture\ClassWithSettersAndConstructor::class);
        $this->assertNull($result);
    }
}
