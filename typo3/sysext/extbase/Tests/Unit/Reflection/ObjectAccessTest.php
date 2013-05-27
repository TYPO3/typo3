<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Reflection;

/***************************************************************
 *  Copyright notice
 *
 *  All rights reserved
 *
 *  This class is a backport of the corresponding class of TYPO3 Flow.
 *  All credits go to the TYPO3 Flow team.
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
require_once __DIR__ . '/Fixture/DummyClassWithGettersAndSetters.php';
require_once __DIR__ . '/Fixture/ArrayAccessClass.php';

/**
 * Test Unit Test Base Class
 */
class ObjectAccessTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	protected $dummyObject;

	public function setUp() {
		$this->dummyObject = new \TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyClassWithGettersAndSetters();
		$this->dummyObject->setProperty('string1');
		$this->dummyObject->setAnotherProperty(42);
		$this->dummyObject->shouldNotBePickedUp = TRUE;
	}

	/**
	 * @test
	 */
	public function getPropertyReturnsExpectedValueForGetterProperty() {
		$property = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($this->dummyObject, 'property');
		$this->assertEquals($property, 'string1');
	}

	/**
	 * @test
	 */
	public function getPropertyReturnsExpectedValueForPublicProperty() {
		$property = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($this->dummyObject, 'publicProperty2');
		$this->assertEquals($property, 42, 'A property of a given object was not returned correctly.');
	}

	/**
	 * @test
	 */
	public function getPropertyReturnsExpectedValueForUnexposedPropertyIfForceDirectAccessIsTrue() {
		$property = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($this->dummyObject, 'unexposedProperty', TRUE);
		$this->assertEquals($property, 'unexposed', 'A property of a given object was not returned correctly.');
	}

	/**
	 * @test
	 */
	public function getPropertyReturnsExpectedValueForUnknownPropertyIfForceDirectAccessIsTrue() {
		$this->dummyObject->unknownProperty = 'unknown';
		$property = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($this->dummyObject, 'unknownProperty', TRUE);
		$this->assertEquals($property, 'unknown', 'A property of a given object was not returned correctly.');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Reflection\Exception\PropertyNotAccessibleException
	 */
	public function getPropertyReturnsPropertyNotAccessibleExceptionForNotExistingPropertyIfForceDirectAccessIsTrue() {
		$property = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($this->dummyObject, 'notExistingProperty', TRUE);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Reflection\Exception\PropertyNotAccessibleException
	 */
	public function getPropertyReturnsThrowsExceptionIfPropertyDoesNotExist() {
		\TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($this->dummyObject, 'notExistingProperty');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Reflection\Exception\PropertyNotAccessibleException
	 */
	public function getPropertyReturnsThrowsExceptionIfArrayKeyDoesNotExist() {
		\TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty(array(), 'notExistingProperty');
	}

	/**
	 * @test
	 */
	public function getPropertyTriesToCallABooleanGetterMethodIfItExists() {
		$property = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($this->dummyObject, 'booleanProperty');
		$this->assertTrue($property);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function getPropertyThrowsExceptionIfThePropertyNameIsNotAString() {
		\TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($this->dummyObject, new \ArrayObject());
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function setPropertyThrowsExceptionIfThePropertyNameIsNotAString() {
		\TYPO3\CMS\Extbase\Reflection\ObjectAccess::setProperty($this->dummyObject, new \ArrayObject(), 42);
	}

	/**
	 * @test
	 */
	public function setPropertyReturnsFalseIfPropertyIsNotAccessible() {
		$this->assertFalse(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::setProperty($this->dummyObject, 'protectedProperty', 42));
	}

	/**
	 * @test
	 */
	public function setPropertySetsValueIfPropertyIsNotAccessibleWhenForceDirectAccessIsTrue() {
		$this->assertTrue(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::setProperty($this->dummyObject, 'unexposedProperty', 'was set anyway', TRUE));
		$this->assertAttributeEquals('was set anyway', 'unexposedProperty', $this->dummyObject);
	}

	/**
	 * @test
	 */
	public function setPropertySetsValueIfPropertyDoesNotExistWhenForceDirectAccessIsTrue() {
		$this->assertTrue(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::setProperty($this->dummyObject, 'unknownProperty', 'was set anyway', TRUE));
		$this->assertAttributeEquals('was set anyway', 'unknownProperty', $this->dummyObject);
	}

	/**
	 * @test
	 */
	public function setPropertyCallsASetterMethodToSetThePropertyValueIfOneIsAvailable() {
		\TYPO3\CMS\Extbase\Reflection\ObjectAccess::setProperty($this->dummyObject, 'property', 4242);
		$this->assertEquals($this->dummyObject->getProperty(), 4242, 'setProperty does not work with setter.');
	}

	/**
	 * @test
	 */
	public function setPropertyWorksWithPublicProperty() {
		\TYPO3\CMS\Extbase\Reflection\ObjectAccess::setProperty($this->dummyObject, 'publicProperty', 4242);
		$this->assertEquals($this->dummyObject->publicProperty, 4242, 'setProperty does not work with public property.');
	}

	/**
	 * @test
	 */
	public function setPropertyCanDirectlySetValuesInAnArrayObjectOrArray() {
		$arrayObject = new \ArrayObject();
		$array = array();
		\TYPO3\CMS\Extbase\Reflection\ObjectAccess::setProperty($arrayObject, 'publicProperty', 4242);
		\TYPO3\CMS\Extbase\Reflection\ObjectAccess::setProperty($array, 'key', 'value');
		$this->assertEquals(4242, $arrayObject['publicProperty']);
		$this->assertEquals('value', $array['key']);
	}

	/**
	 * @test
	 */
	public function getPropertyCanAccessPropertiesOfAnArrayObject() {
		$arrayObject = new \ArrayObject(array('key' => 'value'));
		$actual = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($arrayObject, 'key');
		$this->assertEquals('value', $actual, 'getProperty does not work with ArrayObject property.');
	}

	/**
	 * @test
	 */
	public function getPropertyCanAccessPropertiesOfAnObjectImplementingArrayAccess() {
		$arrayAccessInstance = new \TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\ArrayAccessClass(array('key' => 'value'));
		$actual = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($arrayAccessInstance, 'key');
		$this->assertEquals('value', $actual, 'getProperty does not work with Array Access property.');
	}

	/**
	 * @test
	 */
	public function getPropertyCanAccessPropertiesOfAnArray() {
		$array = array('key' => 'value');
		$expected = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($array, 'key');
		$this->assertEquals($expected, 'value', 'getProperty does not work with Array property.');
	}

	/**
	 * @test
	 */
	public function getPropertyPathCanAccessPropertiesOfAnArray() {
		$array = array('parent' => array('key' => 'value'));
		$actual = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getPropertyPath($array, 'parent.key');
		$this->assertEquals('value', $actual, 'getPropertyPath does not work with Array property.');
	}

	/**
	 * @test
	 */
	public function getPropertyPathCanAccessPropertiesOfAnObjectImplementingArrayAccess() {
		$array = array('parent' => new \ArrayObject(array('key' => 'value')));
		$actual = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getPropertyPath($array, 'parent.key');
		$this->assertEquals('value', $actual, 'getPropertyPath does not work with Array Access property.');
	}

	/**
	 * @test
	 */
	public function getPropertyPathCanNotAccessPropertiesOfAnSplObjectStorageObject() {
		$objectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$exampleObject = new \stdClass();
		$exampleObject->key = 'value';
		$objectStorage->attach($exampleObject);
		$array = array(
			'parent' => $objectStorage,
		);
		$this->assertNull(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::getPropertyPath($array, 'parent.0.key'));
	}

	/**
	 * @test
	 */
	public function getGettablePropertyNamesReturnsAllPropertiesWhichAreAvailable() {
		$gettablePropertyNames = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getGettablePropertyNames($this->dummyObject);
		$expectedPropertyNames = array('anotherProperty', 'booleanProperty', 'property', 'property2', 'publicProperty', 'publicProperty2');
		$this->assertEquals($gettablePropertyNames, $expectedPropertyNames, 'getGettablePropertyNames returns not all gettable properties.');
	}

	/**
	 * @test
	 */
	public function getSettablePropertyNamesReturnsAllPropertiesWhichAreAvailable() {
		$settablePropertyNames = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getSettablePropertyNames($this->dummyObject);
		$expectedPropertyNames = array('anotherProperty', 'property', 'property2', 'publicProperty', 'publicProperty2', 'writeOnlyMagicProperty');
		$this->assertEquals($settablePropertyNames, $expectedPropertyNames, 'getSettablePropertyNames returns not all settable properties.');
	}

	/**
	 * @test
	 */
	public function getSettablePropertyNamesReturnsPropertyNamesOfStdClass() {
		$stdClassObject = new \stdClass();
		$stdClassObject->property = 'string1';
		$stdClassObject->property2 = NULL;
		$settablePropertyNames = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getSettablePropertyNames($stdClassObject);
		$expectedPropertyNames = array('property', 'property2');
		$this->assertEquals($expectedPropertyNames, $settablePropertyNames, 'getSettablePropertyNames returns not all settable properties.');
	}

	/**
	 * @test
	 */
	public function getGettablePropertiesReturnsTheCorrectValuesForAllProperties() {
		$allProperties = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getGettableProperties($this->dummyObject);
		$expectedProperties = array(
			'anotherProperty' => 42,
			'booleanProperty' => TRUE,
			'property' => 'string1',
			'property2' => NULL,
			'publicProperty' => NULL,
			'publicProperty2' => 42
		);
		$this->assertEquals($allProperties, $expectedProperties, 'expectedProperties did not return the right values for the properties.');
	}

	/**
	 * @test
	 */
	public function getGettablePropertiesReturnsPropertiesOfStdClass() {
		$stdClassObject = new \stdClass();
		$stdClassObject->property = 'string1';
		$stdClassObject->property2 = NULL;
		$stdClassObject->publicProperty2 = 42;
		$allProperties = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getGettableProperties($stdClassObject);
		$expectedProperties = array(
			'property' => 'string1',
			'property2' => NULL,
			'publicProperty2' => 42
		);
		$this->assertEquals($expectedProperties, $allProperties, 'expectedProperties did not return the right values for the properties.');
	}

	/**
	 * @test
	 */
	public function isPropertySettableTellsIfAPropertyCanBeSet() {
		$this->assertTrue(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertySettable($this->dummyObject, 'writeOnlyMagicProperty'));
		$this->assertTrue(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertySettable($this->dummyObject, 'publicProperty'));
		$this->assertTrue(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertySettable($this->dummyObject, 'property'));
		$this->assertFalse(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertySettable($this->dummyObject, 'privateProperty'));
		$this->assertFalse(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertySettable($this->dummyObject, 'shouldNotBePickedUp'));
	}

	/**
	 * @test
	 */
	public function isPropertySettableWorksOnStdClass() {
		$stdClassObject = new \stdClass();
		$stdClassObject->property = 'foo';
		$this->assertTrue(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertySettable($stdClassObject, 'property'));
		$this->assertFalse(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertySettable($stdClassObject, 'undefinedProperty'));
	}

	/**
	 * @test
	 */
	public function isPropertyGettableTellsIfAPropertyCanBeRetrieved() {
		$this->assertTrue(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertyGettable($this->dummyObject, 'publicProperty'));
		$this->assertTrue(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertyGettable($this->dummyObject, 'property'));
		$this->assertTrue(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertyGettable($this->dummyObject, 'booleanProperty'));
		$this->assertFalse(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertyGettable($this->dummyObject, 'privateProperty'));
		$this->assertFalse(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertyGettable($this->dummyObject, 'writeOnlyMagicProperty'));
		$this->assertFalse(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertyGettable($this->dummyObject, 'shouldNotBePickedUp'));
	}

	/**
	 * @test
	 */
	public function isPropertyGettableWorksOnArrayAccessObjects() {
		$arrayObject = new \ArrayObject();
		$arrayObject['key'] = 'v';
		$this->assertTrue(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertyGettable($arrayObject, 'key'));
		$this->assertFalse(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertyGettable($arrayObject, 'undefinedKey'));
	}

	/**
	 * @test
	 */
	public function isPropertyGettableWorksOnStdClass() {
		$stdClassObject = new \stdClass();
		$stdClassObject->property = 'foo';
		$this->assertTrue(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertyGettable($stdClassObject, 'property'));
		$this->assertFalse(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertyGettable($stdClassObject, 'undefinedProperty'));
	}

	/**
	 * @test
	 */
	public function getPropertyPathCanRecursivelyGetPropertiesOfAnObject() {
		$alternativeObject = new \TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyClassWithGettersAndSetters();
		$alternativeObject->setProperty('test');
		$this->dummyObject->setProperty2($alternativeObject);
		$expected = 'test';
		$actual = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getPropertyPath($this->dummyObject, 'property2.property');
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function getPropertyPathReturnsNullForNonExistingPropertyPath() {
		$alternativeObject = new \TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyClassWithGettersAndSetters();
		$alternativeObject->setProperty(new \stdClass());
		$this->dummyObject->setProperty2($alternativeObject);
		$this->assertNull(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::getPropertyPath($this->dummyObject, 'property2.property.not.existing'));
	}

	/**
	 * @test
	 */
	public function getPropertyPathReturnsNullIfSubjectIsNoObject() {
		$string = 'Hello world';
		$this->assertNull(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::getPropertyPath($string, 'property2'));
	}

	/**
	 * @test
	 */
	public function getPropertyPathReturnsNullIfSubjectOnPathIsNoObject() {
		$object = new \stdClass();
		$object->foo = 'Hello World';
		$this->assertNull(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::getPropertyPath($object, 'foo.bar'));
	}
}

?>