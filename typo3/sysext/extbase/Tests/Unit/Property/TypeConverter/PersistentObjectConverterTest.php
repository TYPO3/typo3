<?php

/*                                                                        *
 * This script belongs to the Extbase framework.                            *
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

require_once (__DIR__ . '/../../Fixtures/ClassWithSetters.php');
require_once (__DIR__ . '/../../Fixtures/ClassWithSettersAndConstructor.php');

/**
 * Testcase for the String to String converter
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @covers Tx_Extbase_Property_TypeConverter_PersistentObjectConverter<extended>
 */
class Tx_Extbase_Tests_Unit_Property_TypeConverter_PersistentObjectConverterTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @var Tx_Extbase_Property_TypeConverterInterface
	 */
	protected $converter;

	protected $mockReflectionService;
	protected $mockPersistenceManager;
	protected $mockObjectManager;

	public function setUp() {
		$this->converter = new Tx_Extbase_Property_TypeConverter_PersistentObjectConverter();
		$this->mockReflectionService = $this->getMock('Tx_Extbase_Reflection_Service');
		$this->converter->injectReflectionService($this->mockReflectionService);

		$this->mockPersistenceManager = $this->getMock('Tx_Extbase_Persistence_ManagerInterface');
		$this->converter->injectPersistenceManager($this->mockPersistenceManager);

		$this->mockObjectManager = $this->getMock('Tx_Extbase_Object_ObjectManagerInterface');
		$this->converter->injectObjectManager($this->mockObjectManager);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function checkMetadata() {
		$this->assertEquals(array('string', 'array'), $this->converter->getSupportedSourceTypes(), 'Source types do not match');
		$this->assertEquals('object', $this->converter->getSupportedTargetType(), 'Target type does not match');
		$this->assertEquals(1, $this->converter->getPriority(), 'Priority does not match');
	}

	public function dataProviderForCanConvert() {
		return array(
			array(TRUE, FALSE, TRUE), // is entity => can convert
			array(FALSE, TRUE, TRUE), // is valueobject => can convert
			array(FALSE, FALSE, FALSE) // is no entity and no value object => can not convert
		);
	}

	/**
	 * @test
	 * @dataProvider dataProviderForCanConvert
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function canConvertFromReturnsTrueIfClassIsTaggedWithEntityOrValueObject($isEntity, $isValueObject, $expected) {
		$className = uniqid('Test_Class');
		if ($isEntity) {
			eval("class $className extends Tx_Extbase_DomainObject_AbstractEntity {}");
		} elseif ($isValueObject) {
			eval("class $className extends Tx_Extbase_DomainObject_AbstractValueObject {}");
		} else {
			eval("class $className {}");
		}
		$this->assertEquals($expected, $this->converter->canConvertFrom('myInputData', $className));
	}

	/**
	 * test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getPropertyNamesReturnsEmptyArray() {
		$this->assertEquals(array(), $this->converter->getPropertyNames('myString'));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getSourceChildPropertiesToBeConvertedReturnsAllPropertiesExceptTheIdentityProperty() {
		$source = array(
			'k1' => 'v1',
			'__identity' => 'someIdentity',
			'k2' => 'v2'
		);
		$expected = array(
			'k1' => 'v1',
			'k2' => 'v2'
		);
		$this->assertEquals($expected, $this->converter->getSourceChildPropertiesToBeConverted($source));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getTypeOfChildPropertyShouldUseReflectionServiceToDetermineType() {
		$mockSchema = $this->getMockBuilder('Tx_Extbase_Reflection_ClassSchema')->disableOriginalConstructor()->getMock();
		$this->mockReflectionService->expects($this->any())->method('getClassSchema')->with('TheTargetType')->will($this->returnValue($mockSchema));

		$mockSchema->expects($this->any())->method('hasProperty')->with('thePropertyName')->will($this->returnValue(TRUE));
		$mockSchema->expects($this->any())->method('getProperty')->with('thePropertyName')->will($this->returnValue(array(
			'type' => 'TheTypeOfSubObject',
			'elementType' => NULL
		)));
		$configuration = $this->buildConfiguration(array());
		$this->assertEquals('TheTypeOfSubObject', $this->converter->getTypeOfChildProperty('TheTargetType', 'thePropertyName', $configuration));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getTypeOfChildPropertyShouldUseConfiguredTypeIfItWasSet() {
		$this->mockReflectionService->expects($this->never())->method('getClassSchema');

		$configuration = $this->buildConfiguration(array());
		$configuration->forProperty('thePropertyName')->setTypeConverterOption('Tx_Extbase_Property_TypeConverter_PersistentObjectConverter', Tx_Extbase_Property_TypeConverter_PersistentObjectConverter::CONFIGURATION_TARGET_TYPE, 'Foo\Bar');
		$this->assertEquals('Foo\Bar', $this->converter->getTypeOfChildProperty('foo', 'thePropertyName', $configuration));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function convertFromShouldFetchObjectFromPersistenceIfuidStringIsGiven() {
		$identifier = '17';
		$object = new stdClass();


		$this->mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with($identifier)->will($this->returnValue($object));
		$this->assertSame($object, $this->converter->convertFrom($identifier, 'MySpecialType'));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function convertFromShouldFetchObjectFromPersistenceIfOnlyIdentityArrayGiven() {
		$identifier = '12345';
		$object = new stdClass();

		$source = array(
			'__identity' => $identifier
		);
		$this->mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with($identifier)->will($this->returnValue($object));
		$this->assertSame($object, $this->converter->convertFrom($source, 'MySpecialType'));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @expectedException Tx_Extbase_Property_Exception_InvalidPropertyMappingConfigurationException
	 */
	public function convertFromShouldThrowExceptionIfObjectNeedsToBeModifiedButConfigurationIsNotSet() {
		$identifier = '12345';
		$object = new stdClass();
		$object->someProperty = 'asdf';

		$source = array(
			'__identity' => $identifier,
			'foo' => 'bar'
		);
		$this->mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with($identifier)->will($this->returnValue($object));
		$this->converter->convertFrom($source, 'MySpecialType');
	}

	/**
	 * @param array $typeConverterOptions
	 * @return Tx_Extbase_Property_PropertyMappingConfiguration
	 */
	protected function buildConfiguration($typeConverterOptions) {
		$configuration = new Tx_Extbase_Property_PropertyMappingConfiguration();
		$configuration->setTypeConverterOptions('Tx_Extbase_Property_TypeConverter_PersistentObjectConverter', $typeConverterOptions);
		return $configuration;
	}
	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function convertFromShouldCloneTheFetchedObjectIfObjectNeedsToBeModified() {
		$identifier = '12345';
		$object = new Tx_Extbase_Fixtures_ClassWithSetters();
		$object->someProperty = 'asdf';

		$source = array(
			'__identity' => $identifier,
			'foo' => 'bar'
		);
		$convertedChildProperties = array(
			'property1' => 'someConvertedValue'
		);
		$this->mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with($identifier)->will($this->returnValue($object));

		$configuration = $this->buildConfiguration(array(Tx_Extbase_Property_TypeConverter_PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED => TRUE));
		$actual = $this->converter->convertFrom($source, 'MySpecialType', $convertedChildProperties, $configuration);

		$this->assertNotSame($object, $actual, 'The object has not been cloned.');
		$this->assertEquals('asdf', $actual->someProperty, 'The object somehow lost its current state.');
		$this->assertEquals('someConvertedValue', $actual->property1, 'The sub properties have not been set.');
	}

	/**
	 * @param integer $numberOfResults
	 * @param Matcher $howOftenIsGetFirstCalled
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setupMockQuery($numberOfResults, $howOftenIsGetFirstCalled) {
		$mockClassSchema = $this->getMock('Tx_Extbase_Reflection_ClassSchema', array(), array('Dummy'));
		$mockClassSchema->expects($this->once())->method('getIdentityProperties')->will($this->returnValue(array('key1' => 'someType')));
		$this->mockReflectionService->expects($this->once())->method('getClassSchema')->with('SomeType')->will($this->returnValue($mockClassSchema));

		$mockConstraint = $this->getMockBuilder('Tx_Extbase_Persistence_Generic_Qom_Comparison')->disableOriginalConstructor()->getMock();

		$mockObject = new stdClass();
		$mockQuery = $this->getMock('Tx_Extbase_Persistence_QueryInterface');
		$mockQueryResult = $this->getMock('Tx_Extbase_Persistence_QueryResultInterface');
		$mockQueryResult->expects($this->once())->method('count')->will($this->returnValue($numberOfResults));
		$mockQueryResult->expects($howOftenIsGetFirstCalled)->method('getFirst')->will($this->returnValue($mockObject));
		$mockQuery->expects($this->once())->method('equals')->with('key1', 'value1')->will($this->returnValue($mockConstraint));
		$mockQuery->expects($this->once())->method('matching')->with($mockConstraint)->will($this->returnValue($mockQuery));
		$mockQuery->expects($this->once())->method('execute')->will($this->returnValue($mockQueryResult));

		$this->mockPersistenceManager->expects($this->once())->method('createQueryForType')->with('SomeType')->will($this->returnValue($mockQuery));

		return $mockObject;
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @expectedException Tx_Extbase_Property_Exception_InvalidPropertyMappingConfigurationException
	 */
	public function convertFromShouldThrowExceptionIfObjectNeedsToBeCreatedButConfigurationIsNotSet() {
		$source = array(
			'foo' => 'bar'
		);
		$this->converter->convertFrom($source, 'MySpecialType');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function convertFromShouldCreateObject() {
		$source = array(
			'propertyX' => 'bar'
		);
		$object = new Tx_Extbase_Fixtures_ClassWithSetters();
		$convertedChildProperties = array(
			'property1' => 'bar'
		);

		$this->mockObjectManager->expects($this->once())->method('create')->with('Tx_Extbase_Fixtures_ClassWithSetters')->will($this->returnValue($object));
		$this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('Tx_Extbase_Fixtures_ClassWithSetters', '__construct')->will($this->returnValue(array()));
		$configuration = $this->buildConfiguration(array(Tx_Extbase_Property_TypeConverter_PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => TRUE));
		$result = $this->converter->convertFrom($source, 'Tx_Extbase_Fixtures_ClassWithSetters', $convertedChildProperties, $configuration);
		$this->assertSame($object, $result);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @expectedException Tx_Extbase_Property_Exception_InvalidTargetException
	 */
	public function convertFromShouldThrowExceptionIfPropertyOnTargetObjectCouldNotBeSet() {
		$source = array(
			'propertyX' => 'bar'
		);
		$object = new Tx_Extbase_Fixtures_ClassWithSetters();
		$convertedChildProperties = array(
			'propertyNotExisting' => 'bar'
		);

		$this->mockObjectManager->expects($this->once())->method('create')->with('Tx_Extbase_Fixtures_ClassWithSetters')->will($this->returnValue($object));
		$this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('Tx_Extbase_Fixtures_ClassWithSetters', '__construct')->will($this->returnValue(array()));
		$configuration = $this->buildConfiguration(array(Tx_Extbase_Property_TypeConverter_PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => TRUE));
		$result = $this->converter->convertFrom($source, 'Tx_Extbase_Fixtures_ClassWithSetters', $convertedChildProperties, $configuration);
		$this->assertSame($object, $result);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function convertFromShouldCreateObjectWhenThereAreConstructorParameters() {
		$source = array(
			'propertyX' => 'bar'
		);
		$object = new Tx_Extbase_Fixtures_ClassWithSettersAndConstructor('param1');
		$convertedChildProperties = array(
			'property1' => 'param1',
			'property2' => 'bar'
		);

		$this->mockObjectManager->expects($this->once())->method('create')->with('Tx_Extbase_Fixtures_ClassWithSettersAndConstructor', 'param1')->will($this->returnValue($object));
		$this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('Tx_Extbase_Fixtures_ClassWithSettersAndConstructor', '__construct')->will($this->returnValue(array(
			'property1' => array('optional' => FALSE)
		)));
		$configuration = $this->buildConfiguration(array(Tx_Extbase_Property_TypeConverter_PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => TRUE));
		$result = $this->converter->convertFrom($source, 'Tx_Extbase_Fixtures_ClassWithSettersAndConstructor', $convertedChildProperties, $configuration);
		$this->assertSame($object, $result);
		$this->assertEquals('bar', $object->getProperty2());
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function convertFromShouldCreateObjectWhenThereAreOptionalConstructorParameters() {
		$source = array(
			'propertyX' => 'bar'
		);
		$object = new Tx_Extbase_Fixtures_ClassWithSettersAndConstructor('param1');

		$this->mockObjectManager->expects($this->once())->method('create')->with('Tx_Extbase_Fixtures_ClassWithSettersAndConstructor', 'thisIsTheDefaultValue')->will($this->returnValue($object));
		$this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('Tx_Extbase_Fixtures_ClassWithSettersAndConstructor', '__construct')->will($this->returnValue(array(
			'property1' => array('optional' => TRUE, 'defaultValue' => 'thisIsTheDefaultValue')
		)));
		$configuration = $this->buildConfiguration(array(Tx_Extbase_Property_TypeConverter_PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => TRUE));
		$result = $this->converter->convertFrom($source, 'Tx_Extbase_Fixtures_ClassWithSettersAndConstructor', array(), $configuration);
		$this->assertSame($object, $result);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @expectedException Tx_Extbase_Property_Exception_InvalidTargetException
	 */
	public function convertFromShouldThrowExceptionIfRequiredConstructorParameterWasNotFound() {
		$source = array(
			'propertyX' => 'bar'
		);
		$object = new Tx_Extbase_Fixtures_ClassWithSettersAndConstructor('param1');
		$convertedChildProperties = array(
			'property2' => 'bar'
		);

		$this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('Tx_Extbase_Fixtures_ClassWithSettersAndConstructor', '__construct')->will($this->returnValue(array(
			'property1' => array('optional' => FALSE)
		)));
		$configuration = $this->buildConfiguration(array(Tx_Extbase_Property_TypeConverter_PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => TRUE));
		$result = $this->converter->convertFrom($source, 'Tx_Extbase_Fixtures_ClassWithSettersAndConstructor', $convertedChildProperties, $configuration);
		$this->assertSame($object, $result);
	}
}
?>