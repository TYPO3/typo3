<?php

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

namespace TYPO3\CMS\Extbase\Tests\Unit\Reflection;

use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Reflection\Exception\PropertyNotAccessibleException;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\ArrayAccessClass;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyClassWithGettersAndSetters;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ObjectAccessTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @var DummyClassWithGettersAndSetters
     */
    protected $dummyObject;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->dummyObject = new DummyClassWithGettersAndSetters();
        $this->dummyObject->setProperty('string1');
        $this->dummyObject->setAnotherProperty(42);
        $this->dummyObject->shouldNotBePickedUp = true;
    }

    /**
     * @test
     */
    public function getPropertyReturnsExpectedValueForGetterProperty()
    {
        $property = ObjectAccess::getProperty($this->dummyObject, 'property');
        self::assertEquals($property, 'string1');
    }

    /**
     * @test
     */
    public function getPropertyReturnsExpectedValueForPublicProperty()
    {
        $property = ObjectAccess::getProperty($this->dummyObject, 'publicProperty2');
        self::assertEquals($property, 42, 'A property of a given object was not returned correctly.');
    }

    /**
     * @test
     */
    public function getPropertyThrowsExceptionIfPropertyDoesNotExist()
    {
        $this->expectException(PropertyNotAccessibleException::class);
        $this->expectExceptionCode(1476109666);
        ObjectAccess::getProperty($this->dummyObject, 'notExistingProperty');
    }

    /**
     * @test
     */
    public function getPropertyReturnsNullIfArrayKeyDoesNotExist()
    {
        $result = ObjectAccess::getProperty([], 'notExistingProperty');
        self::assertNull($result);
    }

    /**
     * @test
     */
    public function getPropertyTriesToCallABooleanGetterMethodIfItExists()
    {
        $property = ObjectAccess::getProperty($this->dummyObject, 'booleanProperty');
        self::assertTrue($property);
    }

    /**
     * @test
     */
    public function setPropertyReturnsFalseIfPropertyIsNotAccessible()
    {
        self::assertFalse(ObjectAccess::setProperty($this->dummyObject, 'protectedProperty', 42));
    }

    /**
     * @test
     */
    public function setPropertyCallsASetterMethodToSetThePropertyValueIfOneIsAvailable()
    {
        ObjectAccess::setProperty($this->dummyObject, 'property', 4242);
        self::assertEquals($this->dummyObject->getProperty(), 4242, 'setProperty does not work with setter.');
    }

    /**
     * @test
     */
    public function setPropertyWorksWithPublicProperty()
    {
        ObjectAccess::setProperty($this->dummyObject, 'publicProperty', 4242);
        self::assertEquals($this->dummyObject->publicProperty, 4242, 'setProperty does not work with public property.');
    }

    /**
     * @test
     */
    public function setPropertyCanDirectlySetValuesInAnArrayObjectOrArray()
    {
        $arrayObject = new \ArrayObject();
        $array = [];
        ObjectAccess::setProperty($arrayObject, 'publicProperty', 4242);
        ObjectAccess::setProperty($array, 'key', 'value');
        self::assertEquals(4242, $arrayObject['publicProperty']);
        self::assertEquals('value', $array['key']);
    }

    /**
     * @test
     */
    public function getPropertyCanAccessPropertiesOfAnArrayObject()
    {
        $arrayObject = new \ArrayObject(['key' => 'value']);
        $actual = ObjectAccess::getProperty($arrayObject, 'key');
        self::assertEquals('value', $actual, 'getProperty does not work with ArrayObject property.');
    }

    /**
     * @test
     */
    public function getPropertyCanAccessPropertiesOfAnObjectStorageObject()
    {
        $objectStorage = new ObjectStorage();
        $object = new \stdClass();
        $objectStorage->attach($object);
        $actual = ObjectAccess::getProperty($objectStorage, 0);
        self::assertSame($object, $actual, 'getProperty does not work with ObjectStorage property.');
    }

    /**
     * @test
     */
    public function getPropertyCanAccessPropertiesOfAnObjectImplementingArrayAccess()
    {
        $arrayAccessInstance = new ArrayAccessClass(['key' => 'value']);
        $actual = ObjectAccess::getProperty($arrayAccessInstance, 'key');
        self::assertEquals('value', $actual, 'getProperty does not work with Array Access property.');
    }

    /**
     * @test
     */
    public function getPropertyCanAccessPropertiesOfArrayAccessWithGetterMethodWhenOffsetNotExists()
    {
        $arrayAccessInstance = new ArrayAccessClass([]);
        $actual = ObjectAccess::getProperty($arrayAccessInstance, 'virtual');
        self::assertEquals('default-value', $actual, 'getProperty does not work with Array Access property.');
    }

    /**
     * @test
     */
    public function getPropertyCanAccessPropertiesOfArrayAccessWithPriorityForOffsetIfOffsetExists()
    {
        $arrayAccessInstance = new ArrayAccessClass(['virtual' => 'overridden-value']);
        $actual = ObjectAccess::getProperty($arrayAccessInstance, 'virtual');
        self::assertEquals('overridden-value', $actual, 'getProperty does not work with Array Access property.');
    }

    /**
     * @test
     */
    public function getPropertyCanAccessPropertiesOfAnArray()
    {
        $array = ['key' => 'value'];
        $expected = ObjectAccess::getProperty($array, 'key');
        self::assertEquals($expected, 'value', 'getProperty does not work with Array property.');
    }

    /**
     * @test
     */
    public function getPropertyPathCanAccessPropertiesOfAnArray()
    {
        $array = ['parent' => ['key' => 'value']];
        $actual = ObjectAccess::getPropertyPath($array, 'parent.key');
        self::assertEquals('value', $actual, 'getPropertyPath does not work with Array property.');
    }

    /**
     * @test
     */
    public function getPropertyPathCanAccessPropertiesOfAnObjectImplementingArrayAccess()
    {
        $array = ['parent' => new \ArrayObject(['key' => 'value'])];
        $actual = ObjectAccess::getPropertyPath($array, 'parent.key');
        self::assertEquals('value', $actual, 'getPropertyPath does not work with Array Access property.');
    }

    /**
     * @test
     */
    public function getPropertyPathCanAccessPropertiesOfAnExtbaseObjectStorageObject()
    {
        $objectStorage = $this->setUpObjectStorageWithTwoItems();
        $array = [
            'parent' => $objectStorage,
        ];
        self::assertSame('value', ObjectAccess::getPropertyPath($array, 'parent.0.key'));
        self::assertSame('value2', ObjectAccess::getPropertyPath($array, 'parent.1.key'));
    }

    /**
     * @test
     */
    public function getPropertyPathOnObjectStorageDoesNotAffectOngoingLoop()
    {
        $objectStorage = $this->setUpObjectStorageWithTwoItems();
        $i = 0;
        foreach ($objectStorage as $object) {
            ObjectAccess::getPropertyPath($objectStorage, '0.key');
            $i++;
        }
        self::assertSame(2, $i);
    }

    /**
     * @return ObjectStorage
     */
    protected function setUpObjectStorageWithTwoItems()
    {
        $objectStorage = new ObjectStorage();
        $exampleObject = new \stdClass();
        $exampleObject->key = 'value';
        $exampleObject2 = new \stdClass();
        $exampleObject2->key = 'value2';
        $objectStorage->attach($exampleObject);
        $objectStorage->attach($exampleObject2);
        return $objectStorage;
    }

    /**
     * @test
     */
    public function getPropertyPathCanAccessPropertiesOfAnSplObjectStorageObject()
    {
        $objectStorage = $this->setUpSplObjectStorageWithTwoItems();
        $array = [
            'parent' => $objectStorage,
        ];
        self::assertSame('value', ObjectAccess::getPropertyPath($array, 'parent.0.key'));
        self::assertSame('value2', ObjectAccess::getPropertyPath($array, 'parent.1.key'));
    }

    /**
     * @test
     */
    public function getPropertyPathOnSplObjectStorageDoesNotAffectOngoingLoop()
    {
        $objectStorage = $this->setUpSplObjectStorageWithTwoItems();
        $i = 0;
        foreach ($objectStorage as $object) {
            ObjectAccess::getPropertyPath($objectStorage, '0.key');
            $i++;
        }
        self::assertSame(2, $i);
    }

    /**
     * @return \SplObjectStorage
     */
    protected function setUpSplObjectStorageWithTwoItems()
    {
        $objectStorage = new \SplObjectStorage();
        $exampleObject = new \stdClass();
        $exampleObject->key = 'value';
        $exampleObject2 = new \stdClass();
        $exampleObject2->key = 'value2';
        $objectStorage->attach($exampleObject);
        $objectStorage->attach($exampleObject2);
        return $objectStorage;
    }

    /**
     * @test
     */
    public function getGettablePropertyNamesReturnsAllPropertiesWhichAreAvailable()
    {
        $gettablePropertyNames = ObjectAccess::getGettablePropertyNames($this->dummyObject);
        $expectedPropertyNames = ['anotherBooleanProperty', 'anotherProperty', 'booleanProperty', 'property', 'property2', 'publicProperty', 'publicProperty2', 'someValue'];
        self::assertEquals($gettablePropertyNames, $expectedPropertyNames, 'getGettablePropertyNames returns not all gettable properties.');
    }

    /**
     * @test
     */
    public function getGettablePropertyNamesRespectsMethodArguments()
    {
        $dateTimeZone = new \DateTimeZone('+2');
        $gettablePropertyNames = ObjectAccess::getGettablePropertyNames($dateTimeZone);
        $expectedPropertyNames = ['location', 'name'];
        foreach ($expectedPropertyNames as $expectedPropertyName) {
            self::assertContains($expectedPropertyName, $gettablePropertyNames);
        }
    }

    /**
     * @test
     */
    public function getSettablePropertyNamesReturnsAllPropertiesWhichAreAvailable()
    {
        $settablePropertyNames = ObjectAccess::getSettablePropertyNames($this->dummyObject);
        $expectedPropertyNames = ['anotherBooleanProperty', 'anotherProperty', 'property', 'property2', 'publicProperty', 'publicProperty2', 'writeOnlyMagicProperty'];
        self::assertEquals($settablePropertyNames, $expectedPropertyNames, 'getSettablePropertyNames returns not all settable properties.');
    }

    /**
     * @test
     */
    public function getSettablePropertyNamesReturnsPropertyNamesOfStdClass()
    {
        $stdClassObject = new \stdClass();
        $stdClassObject->property = 'string1';
        $stdClassObject->property2 = null;
        $settablePropertyNames = ObjectAccess::getSettablePropertyNames($stdClassObject);
        $expectedPropertyNames = ['property', 'property2'];
        self::assertEquals($expectedPropertyNames, $settablePropertyNames, 'getSettablePropertyNames returns not all settable properties.');
    }

    /**
     * @test
     */
    public function getGettablePropertiesReturnsTheCorrectValuesForAllProperties()
    {
        $allProperties = ObjectAccess::getGettableProperties($this->dummyObject);
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
        self::assertEquals($allProperties, $expectedProperties, 'expectedProperties did not return the right values for the properties.');
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
        $allProperties = ObjectAccess::getGettableProperties($stdClassObject);
        $expectedProperties = [
            'property' => 'string1',
            'property2' => null,
            'publicProperty2' => 42
        ];
        self::assertEquals($expectedProperties, $allProperties, 'expectedProperties did not return the right values for the properties.');
    }

    /**
     * @test
     */
    public function isPropertySettableTellsIfAPropertyCanBeSet()
    {
        self::assertTrue(ObjectAccess::isPropertySettable($this->dummyObject, 'writeOnlyMagicProperty'));
        self::assertTrue(ObjectAccess::isPropertySettable($this->dummyObject, 'publicProperty'));
        self::assertTrue(ObjectAccess::isPropertySettable($this->dummyObject, 'property'));
        self::assertFalse(ObjectAccess::isPropertySettable($this->dummyObject, 'privateProperty'));
        self::assertFalse(ObjectAccess::isPropertySettable($this->dummyObject, 'shouldNotBePickedUp'));
    }

    /**
     * @test
     */
    public function isPropertySettableWorksOnStdClass()
    {
        $stdClassObject = new \stdClass();
        $stdClassObject->property = 'foo';
        self::assertTrue(ObjectAccess::isPropertySettable($stdClassObject, 'property'));
        self::assertFalse(ObjectAccess::isPropertySettable($stdClassObject, 'undefinedProperty'));
    }

    /**
     * @dataProvider propertyGettableTestValues
     * @test
     *
     * @param string $property
     * @param bool $expected
     */
    public function isPropertyGettableTellsIfAPropertyCanBeRetrieved($property, $expected)
    {
        self::assertEquals($expected, ObjectAccess::isPropertyGettable($this->dummyObject, $property));
    }

    /**
     * @return array
     */
    public function propertyGettableTestValues()
    {
        return [
            ['publicProperty', true],
            ['property', true],
            ['booleanProperty', true],
            ['anotherBooleanProperty', true],
            ['privateProperty', false],
            ['writeOnlyMagicProperty', false]
        ];
    }

    /**
     * @test
     */
    public function isPropertyGettableWorksOnArrayAccessObjects()
    {
        $arrayObject = new \ArrayObject();
        $arrayObject['key'] = 'v';
        self::assertTrue(ObjectAccess::isPropertyGettable($arrayObject, 'key'));
        self::assertFalse(ObjectAccess::isPropertyGettable($arrayObject, 'undefinedKey'));
    }

    /**
     * @test
     */
    public function isPropertyGettableWorksOnStdClass()
    {
        $stdClassObject = new \stdClass();
        $stdClassObject->property = 'foo';
        self::assertTrue(ObjectAccess::isPropertyGettable($stdClassObject, 'property'));
        self::assertFalse(ObjectAccess::isPropertyGettable($stdClassObject, 'undefinedProperty'));
    }

    /**
     * @test
     */
    public function getPropertyPathCanRecursivelyGetPropertiesOfAnObject()
    {
        $alternativeObject = new DummyClassWithGettersAndSetters();
        $alternativeObject->setProperty('test');
        $this->dummyObject->setProperty2($alternativeObject);
        $expected = 'test';
        $actual = ObjectAccess::getPropertyPath($this->dummyObject, 'property2.property');
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function getPropertyPathReturnsNullForNonExistingPropertyPath()
    {
        $alternativeObject = new DummyClassWithGettersAndSetters();
        $alternativeObject->setProperty(new \stdClass());
        $this->dummyObject->setProperty2($alternativeObject);
        self::assertNull(ObjectAccess::getPropertyPath($this->dummyObject, 'property2.property.not.existing'));
    }

    /**
     * @test
     */
    public function getPropertyPathReturnsNullIfSubjectIsNoObject()
    {
        $string = 'Hello world';
        self::assertNull(ObjectAccess::getPropertyPath($string, 'property2'));
    }

    /**
     * @test
     */
    public function getPropertyPathReturnsNullIfSubjectOnPathIsNoObject()
    {
        $object = new \stdClass();
        $object->foo = 'Hello World';
        self::assertNull(ObjectAccess::getPropertyPath($object, 'foo.bar'));
    }
}
