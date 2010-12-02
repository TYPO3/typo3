<?php
/***************************************************************
*  Copyright notice
*
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
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

require_once('Fixture/DummyClassWithGettersAndSetters.php');
require_once('Fixture/ArrayAccessClass.php');

class Tx_Extbase_Tests_Unit_Reflection_ObjectAccessTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	protected $dummyObject;

	public function setUp() {
		$this->dummyObject = new Tx_Extbase_Tests_Unit_Reflection_Fixture_DummyClassWithGettersAndSetters();
		$this->dummyObject->setProperty('string1');
		$this->dummyObject->setAnotherProperty(42);
		$this->dummyObject->shouldNotBePickedUp = TRUE;
	}

	/**
	 * @test
	 */
	public function getPropertyReturnsExpectedValueForGetterProperty() {
		$property = Tx_Extbase_Reflection_ObjectAccess::getProperty($this->dummyObject, 'property');
		$this->assertEquals($property, 'string1');
	}

	/**
	 * @test
	 */
	public function getPropertyReturnsExpectedValueForPublicProperty() {
		$property = Tx_Extbase_Reflection_ObjectAccess::getProperty($this->dummyObject, 'publicProperty2');
		$this->assertEquals($property, 42, 'A property of a given object was not returned correctly.');
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_Reflection_Exception_PropertyNotAccessibleException
	 */
	public function getPropertyReturnsThrowsExceptionIfPropertyDoesNotExist() {
		Tx_Extbase_Reflection_ObjectAccess::getProperty($this->dummyObject, 'notExistingProperty');
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_Reflection_Exception_PropertyNotAccessibleException
	 */
	public function getPropertyReturnsThrowsExceptionIfArrayKeyDoesNotExist() {
		Tx_Extbase_Reflection_ObjectAccess::getProperty(array(), 'notExistingProperty');
	}

	/**
	 * @test
	 */
	public function getPropertyTriesToCallABooleanGetterMethodIfItExists() {
		$property = Tx_Extbase_Reflection_ObjectAccess::getProperty($this->dummyObject, 'booleanProperty');
		$this->assertSame('method called 1', $property);
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function getPropertyThrowsExceptionIfThePropertyNameIsNotAString() {
		Tx_Extbase_Reflection_ObjectAccess::getProperty($this->dummyObject, new ArrayObject());
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function setPropertyThrowsExceptionIfThePropertyNameIsNotAString() {
		Tx_Extbase_Reflection_ObjectAccess::setProperty($this->dummyObject, new ArrayObject(), 42);
	}

	/**
	 * @test
	 */
	public function setPropertyReturnsFalseIfPropertyIsNotAccessible() {
		$this->assertFalse(Tx_Extbase_Reflection_ObjectAccess::setProperty($this->dummyObject, 'protectedProperty', 42));
	}

	/**
	 * @test
	 */
	public function setPropertyCallsASetterMethodToSetThePropertyValueIfOneIsAvailable() {
		Tx_Extbase_Reflection_ObjectAccess::setProperty($this->dummyObject, 'property', 4242);
		$this->assertEquals($this->dummyObject->getProperty(), 4242, 'setProperty does not work with setter.');
	}

	/**
	 * @test
	 */
	public function setPropertyWorksWithPublicProperty() {
		Tx_Extbase_Reflection_ObjectAccess::setProperty($this->dummyObject, 'publicProperty', 4242);
		$this->assertEquals($this->dummyObject->publicProperty, 4242, 'setProperty does not work with public property.');
	}

	/**
	 * @test
	 */
	public function setPropertyCanDirectlySetValuesInAnArrayObjectOrArray() {
		$arrayObject = new ArrayObject();
		$array = array();

		Tx_Extbase_Reflection_ObjectAccess::setProperty($arrayObject, 'publicProperty', 4242);
		Tx_Extbase_Reflection_ObjectAccess::setProperty($array, 'key', 'value');

		$this->assertEquals(4242, $arrayObject['publicProperty']);
		$this->assertEquals('value', $array['key']);
	}

	/**
	 * @test
	 */
	public function getPropertyCanAccessPropertiesOfAnArrayObject() {
		$arrayObject = new ArrayObject(array('key' => 'value'));
		$expected = Tx_Extbase_Reflection_ObjectAccess::getProperty($arrayObject, 'key');
		$this->assertEquals($expected, 'value', 'getProperty does not work with ArrayObject property.');
	}

	/**
	 * @test
	 */
	public function getPropertyCanAccessPropertiesOfAnObjectImplementingArrayAccess() {
		$arrayAccessInstance = new Tx_Extbase_Tests_Unit_Reflection_Fixture_ArrayAccessClass(array('key' => 'value'));
		$expected = Tx_Extbase_Reflection_ObjectAccess::getProperty($arrayAccessInstance, 'key');
		$this->assertEquals($expected, 'value', 'getPropertyPath does not work with Array Access property.');
	}

	/**
	 * @test
	 */
	public function getPropertyCanAccessPropertiesOfAnArray() {
		$array = array('key' => 'value');
		$expected = Tx_Extbase_Reflection_ObjectAccess::getProperty($array, 'key');
		$this->assertEquals($expected, 'value', 'getProperty does not work with Array property.');
	}

	/**
	 * @test
	 */
	public function getPropertyPathCanAccessPropertiesOfAnArray() {
		$array = array('parent' => array('key' => 'value'));
		$expected = Tx_Extbase_Reflection_ObjectAccess::getPropertyPath($array, 'parent.key');
		$this->assertEquals($expected, 'value', 'getPropertyPath does not work with Array property.');
	}

	/**
	 * @test
	 */
	public function getPropertyPathCanAccessPropertiesOfAnObjectImplementingArrayAccess() {
		$array = array('parent' => new ArrayObject(array('key' => 'value')));
		$expected = Tx_Extbase_Reflection_ObjectAccess::getPropertyPath($array, 'parent.key');
		$this->assertEquals($expected, 'value', 'getPropertyPath does not work with Array Access property.');
	}

	/**
	 * @test
	 */
	public function getGettablePropertyNamesReturnsAllPropertiesWhichAreAvailable() {
		$gettablePropertyNames = Tx_Extbase_Reflection_ObjectAccess::getGettablePropertyNames($this->dummyObject);
		$expectedPropertyNames = array('anotherProperty', 'booleanProperty', 'property', 'property2', 'publicProperty', 'publicProperty2');
		$this->assertEquals($gettablePropertyNames, $expectedPropertyNames, 'getGettablePropertyNames returns not all gettable properties.');
	}

	/**
	 * @test
	 */
	public function getSettablePropertyNamesReturnsAllPropertiesWhichAreAvailable() {
		$settablePropertyNames = Tx_Extbase_Reflection_ObjectAccess::getSettablePropertyNames($this->dummyObject);
		$expectedPropertyNames = array('anotherProperty', 'property', 'property2', 'publicProperty', 'publicProperty2', 'writeOnlyMagicProperty');
		$this->assertEquals($settablePropertyNames, $expectedPropertyNames, 'getSettablePropertyNames returns not all settable properties.');
	}

	/**
	 * @test
	 */
	public function getSettablePropertyNamesReturnsPropertyNamesOfStdClass() {
		$stdClassObject = new stdClass();
		$stdClassObject->property = 'string1';
		$stdClassObject->property2 = NULL;

		$settablePropertyNames = Tx_Extbase_Reflection_ObjectAccess::getSettablePropertyNames($stdClassObject);
		$expectedPropertyNames = array('property', 'property2');
		$this->assertEquals($expectedPropertyNames, $settablePropertyNames, 'getSettablePropertyNames returns not all settable properties.');
	}

	/**
	 * @test
	 */
	public function getGettablePropertiesReturnsTheCorrectValuesForAllProperties() {
		$allProperties = Tx_Extbase_Reflection_ObjectAccess::getGettableProperties($this->dummyObject);
		$expectedProperties = array(
			'anotherProperty' => 42,
			'booleanProperty' => TRUE,
			'property' => 'string1',
			'property2' => NULL,
			'publicProperty' => NULL,
			'publicProperty2' => 42);
		$this->assertEquals($allProperties, $expectedProperties, 'expectedProperties did not return the right values for the properties.');
	}

	/**
	 * @test
	 */
	public function getGettablePropertiesReturnsPropertiesOfStdClass() {
		$stdClassObject = new stdClass();
		$stdClassObject->property = 'string1';
		$stdClassObject->property2 = NULL;
		$stdClassObject->publicProperty2 = 42;
		$allProperties = Tx_Extbase_Reflection_ObjectAccess::getGettableProperties($stdClassObject);
		$expectedProperties = array(
			'property' => 'string1',
			'property2' => NULL,
			'publicProperty2' => 42);
		$this->assertEquals($expectedProperties, $allProperties, 'expectedProperties did not return the right values for the properties.');
	}

	/**
	 * @test
	 */
	public function isPropertySettableTellsIfAPropertyCanBeSet() {
		$this->assertTrue(Tx_Extbase_Reflection_ObjectAccess::isPropertySettable($this->dummyObject, 'writeOnlyMagicProperty'));
		$this->assertTrue(Tx_Extbase_Reflection_ObjectAccess::isPropertySettable($this->dummyObject, 'publicProperty'));
		$this->assertTrue(Tx_Extbase_Reflection_ObjectAccess::isPropertySettable($this->dummyObject, 'property'));

		$this->assertFalse(Tx_Extbase_Reflection_ObjectAccess::isPropertySettable($this->dummyObject, 'privateProperty'));
		$this->assertFalse(Tx_Extbase_Reflection_ObjectAccess::isPropertySettable($this->dummyObject, 'shouldNotBePickedUp'));
	}

	/**
	 * @test
	 */
	public function isPropertySettableWorksOnStdClass() {
		$stdClassObject = new stdClass();
		$stdClassObject->property = 'foo';

		$this->assertTrue(Tx_Extbase_Reflection_ObjectAccess::isPropertySettable($stdClassObject, 'property'));

		$this->assertFalse(Tx_Extbase_Reflection_ObjectAccess::isPropertySettable($stdClassObject, 'undefinedProperty'));
	}

	/**
	 * @test
	 */
	public function isPropertyGettableTellsIfAPropertyCanBeRetrieved() {
		$this->assertTrue(Tx_Extbase_Reflection_ObjectAccess::isPropertyGettable($this->dummyObject, 'publicProperty'));
		$this->assertTrue(Tx_Extbase_Reflection_ObjectAccess::isPropertyGettable($this->dummyObject, 'property'));
		$this->assertTrue(Tx_Extbase_Reflection_ObjectAccess::isPropertyGettable($this->dummyObject, 'booleanProperty'));

		$this->assertFalse(Tx_Extbase_Reflection_ObjectAccess::isPropertyGettable($this->dummyObject, 'privateProperty'));
		$this->assertFalse(Tx_Extbase_Reflection_ObjectAccess::isPropertyGettable($this->dummyObject, 'writeOnlyMagicProperty'));
		$this->assertFalse(Tx_Extbase_Reflection_ObjectAccess::isPropertyGettable($this->dummyObject, 'shouldNotBePickedUp'));
	}

	/**
	 * @test
	 */
	public function isPropertyGettableWorksOnStdClass() {
		$stdClassObject = new stdClass();
		$stdClassObject->property = 'foo';

		$this->assertTrue(Tx_Extbase_Reflection_ObjectAccess::isPropertyGettable($stdClassObject, 'property'));

		$this->assertFalse(Tx_Extbase_Reflection_ObjectAccess::isPropertyGettable($stdClassObject, 'undefinedProperty'));
	}

	/**
	 * @test
	 */
	public function getPropertyPathCanRecursivelyGetPropertiesOfAnObject() {
		$alternativeObject = new Tx_Extbase_Tests_Unit_Reflection_Fixture_DummyClassWithGettersAndSetters();
		$alternativeObject->setProperty('test');
		$this->dummyObject->setProperty2($alternativeObject);

		$expected = 'test';
		$actual = Tx_Extbase_Reflection_ObjectAccess::getPropertyPath($this->dummyObject, 'property2.property');
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function getPropertyPathReturnsNullForNonExistingPropertyPath() {
		$alternativeObject = new Tx_Extbase_Tests_Unit_Reflection_Fixture_DummyClassWithGettersAndSetters();
		$alternativeObject->setProperty(new stdClass());
		$this->dummyObject->setProperty2($alternativeObject);

		$this->assertNull(Tx_Extbase_Reflection_ObjectAccess::getPropertyPath($this->dummyObject, 'property2.property.not.existing'));
	}

}
?>