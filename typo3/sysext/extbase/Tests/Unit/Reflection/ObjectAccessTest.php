<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Reflection;

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

/**
 * Test case
 */
class ObjectAccessTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    protected $dummyObject;

    protected function setUp()
    {
        $this->dummyObject = new \TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyClassWithGettersAndSetters();
        $this->dummyObject->setProperty('string1');
        $this->dummyObject->setAnotherProperty(42);
        $this->dummyObject->shouldNotBePickedUp = true;
    }

    /**
     * @test
     */
    public function getPropertyReturnsExpectedValueForGetterProperty()
    {
        $property = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($this->dummyObject, 'property');
        $this->assertEquals($property, 'string1');
    }

    /**
     * @test
     */
    public function getPropertyReturnsExpectedValueForPublicProperty()
    {
        $property = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($this->dummyObject, 'publicProperty2');
        $this->assertEquals($property, 42, 'A property of a given object was not returned correctly.');
    }

    /**
     * @test
     */
    public function getPropertyReturnsExpectedValueForUnexposedPropertyIfForceDirectAccessIsTrue()
    {
        $property = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($this->dummyObject, 'unexposedProperty', true);
        $this->assertEquals($property, 'unexposed', 'A property of a given object was not returned correctly.');
    }

    /**
     * @test
     */
    public function getPropertyReturnsExpectedValueForUnknownPropertyIfForceDirectAccessIsTrue()
    {
        $this->dummyObject->unknownProperty = 'unknown';
        $property = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($this->dummyObject, 'unknownProperty', true);
        $this->assertEquals($property, 'unknown', 'A property of a given object was not returned correctly.');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Extbase\Reflection\Exception\PropertyNotAccessibleException
     */
    public function getPropertyReturnsPropertyNotAccessibleExceptionForNotExistingPropertyIfForceDirectAccessIsTrue()
    {
        $property = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($this->dummyObject, 'notExistingProperty', true);
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Extbase\Reflection\Exception\PropertyNotAccessibleException
     */
    public function getPropertyReturnsThrowsExceptionIfPropertyDoesNotExist()
    {
        \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($this->dummyObject, 'notExistingProperty');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Extbase\Reflection\Exception\PropertyNotAccessibleException
     */
    public function getPropertyReturnsThrowsExceptionIfArrayKeyDoesNotExist()
    {
        \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty([], 'notExistingProperty');
    }

    /**
     * @test
     */
    public function getPropertyTriesToCallABooleanGetterMethodIfItExists()
    {
        $property = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($this->dummyObject, 'booleanProperty');
        $this->assertTrue($property);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function getPropertyThrowsExceptionIfThePropertyNameIsNotAString()
    {
        \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($this->dummyObject, new \ArrayObject());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function setPropertyThrowsExceptionIfThePropertyNameIsNotAString()
    {
        \TYPO3\CMS\Extbase\Reflection\ObjectAccess::setProperty($this->dummyObject, new \ArrayObject(), 42);
    }

    /**
     * @test
     */
    public function setPropertyReturnsFalseIfPropertyIsNotAccessible()
    {
        $this->assertFalse(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::setProperty($this->dummyObject, 'protectedProperty', 42));
    }

    /**
     * @test
     */
    public function setPropertySetsValueIfPropertyIsNotAccessibleWhenForceDirectAccessIsTrue()
    {
        $this->assertTrue(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::setProperty($this->dummyObject, 'unexposedProperty', 'was set anyway', true));
        $this->assertAttributeEquals('was set anyway', 'unexposedProperty', $this->dummyObject);
    }

    /**
     * @test
     */
    public function setPropertySetsValueIfPropertyDoesNotExistWhenForceDirectAccessIsTrue()
    {
        $this->assertTrue(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::setProperty($this->dummyObject, 'unknownProperty', 'was set anyway', true));
        $this->assertAttributeEquals('was set anyway', 'unknownProperty', $this->dummyObject);
    }

    /**
     * @test
     */
    public function setPropertyCallsASetterMethodToSetThePropertyValueIfOneIsAvailable()
    {
        \TYPO3\CMS\Extbase\Reflection\ObjectAccess::setProperty($this->dummyObject, 'property', 4242);
        $this->assertEquals($this->dummyObject->getProperty(), 4242, 'setProperty does not work with setter.');
    }

    /**
     * @test
     */
    public function setPropertyWorksWithPublicProperty()
    {
        \TYPO3\CMS\Extbase\Reflection\ObjectAccess::setProperty($this->dummyObject, 'publicProperty', 4242);
        $this->assertEquals($this->dummyObject->publicProperty, 4242, 'setProperty does not work with public property.');
    }

    /**
     * @test
     */
    public function setPropertyCanDirectlySetValuesInAnArrayObjectOrArray()
    {
        $arrayObject = new \ArrayObject();
        $array = [];
        \TYPO3\CMS\Extbase\Reflection\ObjectAccess::setProperty($arrayObject, 'publicProperty', 4242);
        \TYPO3\CMS\Extbase\Reflection\ObjectAccess::setProperty($array, 'key', 'value');
        $this->assertEquals(4242, $arrayObject['publicProperty']);
        $this->assertEquals('value', $array['key']);
    }

    /**
     * @test
     */
    public function getPropertyCanAccessPropertiesOfAnArrayObject()
    {
        $arrayObject = new \ArrayObject(['key' => 'value']);
        $actual = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($arrayObject, 'key');
        $this->assertEquals('value', $actual, 'getProperty does not work with ArrayObject property.');
    }

    /**
     * @test
     */
    public function getPropertyCanAccessPropertiesOfAnObjectStorageObject()
    {
        $objectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $objectStorage->key = 'value';
        $actual = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($objectStorage, 'key');
        $this->assertEquals('value', $actual, 'getProperty does not work with ObjectStorage property.');
    }

    /**
     * @test
     */
    public function getPropertyCanAccessPropertiesOfAnObjectImplementingArrayAccess()
    {
        $arrayAccessInstance = new \TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\ArrayAccessClass(['key' => 'value']);
        $actual = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($arrayAccessInstance, 'key');
        $this->assertEquals('value', $actual, 'getProperty does not work with Array Access property.');
    }

    /**
     * @test
     */
    public function getPropertyCanAccessPropertiesOfAnArray()
    {
        $array = ['key' => 'value'];
        $expected = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($array, 'key');
        $this->assertEquals($expected, 'value', 'getProperty does not work with Array property.');
    }

    /**
     * @test
     */
    public function getPropertyPathCanAccessPropertiesOfAnArray()
    {
        $array = ['parent' => ['key' => 'value']];
        $actual = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getPropertyPath($array, 'parent.key');
        $this->assertEquals('value', $actual, 'getPropertyPath does not work with Array property.');
    }

    /**
     * @test
     */
    public function getPropertyPathCanAccessPropertiesOfAnObjectImplementingArrayAccess()
    {
        $array = ['parent' => new \ArrayObject(['key' => 'value'])];
        $actual = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getPropertyPath($array, 'parent.key');
        $this->assertEquals('value', $actual, 'getPropertyPath does not work with Array Access property.');
    }

    /**
     * @test
     */
    public function getPropertyPathCanAccessPropertiesOfAnExtbaseObjectStorageObject()
    {
        $objectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $exampleObject = new \stdClass();
        $exampleObject->key = 'value';
        $exampleObject2 = new \stdClass();
        $exampleObject2->key = 'value2';
        $objectStorage->attach($exampleObject);
        $objectStorage->attach($exampleObject2);
        $array = [
            'parent' => $objectStorage,
        ];
        $this->assertSame('value', \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getPropertyPath($array, 'parent.0.key'));
        $this->assertSame('value2', \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getPropertyPath($array, 'parent.1.key'));
    }

    /**
     * @test
     */
    public function getPropertyPathCanAccessPropertiesOfAnSplObjectStorageObject()
    {
        $objectStorage = new \SplObjectStorage();
        $exampleObject = new \stdClass();
        $exampleObject->key = 'value';
        $exampleObject2 = new \stdClass();
        $exampleObject2->key = 'value2';
        $objectStorage->attach($exampleObject);
        $objectStorage->attach($exampleObject2);
        $array = [
            'parent' => $objectStorage,
        ];
        $this->assertSame('value', \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getPropertyPath($array, 'parent.0.key'));
        $this->assertSame('value2', \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getPropertyPath($array, 'parent.1.key'));
    }

    /**
     * @test
     */
    public function getGettablePropertyNamesReturnsAllPropertiesWhichAreAvailable()
    {
        $gettablePropertyNames = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getGettablePropertyNames($this->dummyObject);
        $expectedPropertyNames = ['anotherBooleanProperty', 'anotherProperty', 'booleanProperty', 'property', 'property2', 'publicProperty', 'publicProperty2', 'someValue'];
        $this->assertEquals($gettablePropertyNames, $expectedPropertyNames, 'getGettablePropertyNames returns not all gettable properties.');
    }

    /**
     * @test
     */
    public function getGettablePropertyNamesRespectsMethodArguments()
    {
        $dateTimeZone = new \DateTimeZone('+2');
        $gettablePropertyNames = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getGettablePropertyNames($dateTimeZone);
        $expectedPropertyNames = ['location', 'name'];
        $this->assertEquals($gettablePropertyNames, $expectedPropertyNames, 'getGettablePropertyNames does not respect method arguments.');
    }

    /**
     * @test
     */
    public function getSettablePropertyNamesReturnsAllPropertiesWhichAreAvailable()
    {
        $settablePropertyNames = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getSettablePropertyNames($this->dummyObject);
        $expectedPropertyNames = ['anotherBooleanProperty', 'anotherProperty', 'property', 'property2', 'publicProperty', 'publicProperty2', 'writeOnlyMagicProperty'];
        $this->assertEquals($settablePropertyNames, $expectedPropertyNames, 'getSettablePropertyNames returns not all settable properties.');
    }

    /**
     * @test
     */
    public function getSettablePropertyNamesReturnsPropertyNamesOfStdClass()
    {
        $stdClassObject = new \stdClass();
        $stdClassObject->property = 'string1';
        $stdClassObject->property2 = null;
        $settablePropertyNames = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getSettablePropertyNames($stdClassObject);
        $expectedPropertyNames = ['property', 'property2'];
        $this->assertEquals($expectedPropertyNames, $settablePropertyNames, 'getSettablePropertyNames returns not all settable properties.');
    }

    /**
     * @test
     */
    public function getGettablePropertiesReturnsTheCorrectValuesForAllProperties()
    {
        $allProperties = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getGettableProperties($this->dummyObject);
        $expectedProperties = [
            'anotherBooleanProperty' => true,
            'anotherProperty' => 42,
            'booleanProperty' => true,
            'property' => 'string1',
            'property2' => null,
            'publicProperty' => null,
            'publicProperty2' => 42,
            'someValue' => true,
        ];
        $this->assertEquals($allProperties, $expectedProperties, 'expectedProperties did not return the right values for the properties.');
    }

    /**
     * @test
     */
    public function getGettablePropertiesReturnsPropertiesOfStdClass()
    {
        $stdClassObject = new \stdClass();
        $stdClassObject->property = 'string1';
        $stdClassObject->property2 = null;
        $stdClassObject->publicProperty2 = 42;
        $allProperties = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getGettableProperties($stdClassObject);
        $expectedProperties = [
            'property' => 'string1',
            'property2' => null,
            'publicProperty2' => 42
        ];
        $this->assertEquals($expectedProperties, $allProperties, 'expectedProperties did not return the right values for the properties.');
    }

    /**
     * @test
     */
    public function isPropertySettableTellsIfAPropertyCanBeSet()
    {
        $this->assertTrue(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertySettable($this->dummyObject, 'writeOnlyMagicProperty'));
        $this->assertTrue(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertySettable($this->dummyObject, 'publicProperty'));
        $this->assertTrue(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertySettable($this->dummyObject, 'property'));
        $this->assertFalse(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertySettable($this->dummyObject, 'privateProperty'));
        $this->assertFalse(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertySettable($this->dummyObject, 'shouldNotBePickedUp'));
    }

    /**
     * @test
     */
    public function isPropertySettableWorksOnStdClass()
    {
        $stdClassObject = new \stdClass();
        $stdClassObject->property = 'foo';
        $this->assertTrue(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertySettable($stdClassObject, 'property'));
        $this->assertFalse(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertySettable($stdClassObject, 'undefinedProperty'));
    }

    /**
     * @test
     */
    public function isPropertyGettableTellsIfAPropertyCanBeRetrieved()
    {
        $this->assertTrue(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertyGettable($this->dummyObject, 'publicProperty'));
        $this->assertTrue(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertyGettable($this->dummyObject, 'property'));
        $this->assertTrue(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertyGettable($this->dummyObject, 'booleanProperty'));
        $this->assertFalse(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertyGettable($this->dummyObject, 'privateProperty'));
        $this->assertFalse(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertyGettable($this->dummyObject, 'writeOnlyMagicProperty'));
        $this->assertFalse(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertyGettable($this->dummyObject, 'shouldNotBePickedUp'));
        $this->assertTrue(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertyGettable($this->dummyObject, 'anotherBooleanProperty'));
    }

    /**
     * @test
     */
    public function isPropertyGettableWorksOnArrayAccessObjects()
    {
        $arrayObject = new \ArrayObject();
        $arrayObject['key'] = 'v';
        $this->assertTrue(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertyGettable($arrayObject, 'key'));
        $this->assertFalse(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertyGettable($arrayObject, 'undefinedKey'));
    }

    /**
     * @test
     */
    public function isPropertyGettableWorksOnStdClass()
    {
        $stdClassObject = new \stdClass();
        $stdClassObject->property = 'foo';
        $this->assertTrue(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertyGettable($stdClassObject, 'property'));
        $this->assertFalse(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertyGettable($stdClassObject, 'undefinedProperty'));
    }

    /**
     * @test
     */
    public function getPropertyPathCanRecursivelyGetPropertiesOfAnObject()
    {
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
    public function getPropertyPathReturnsNullForNonExistingPropertyPath()
    {
        $alternativeObject = new \TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyClassWithGettersAndSetters();
        $alternativeObject->setProperty(new \stdClass());
        $this->dummyObject->setProperty2($alternativeObject);
        $this->assertNull(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::getPropertyPath($this->dummyObject, 'property2.property.not.existing'));
    }

    /**
     * @test
     */
    public function getPropertyPathReturnsNullIfSubjectIsNoObject()
    {
        $string = 'Hello world';
        $this->assertNull(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::getPropertyPath($string, 'property2'));
    }

    /**
     * @test
     */
    public function getPropertyPathReturnsNullIfSubjectOnPathIsNoObject()
    {
        $object = new \stdClass();
        $object->foo = 'Hello World';
        $this->assertNull(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::getPropertyPath($object, 'foo.bar'));
    }
}
