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

namespace TYPO3\CMS\Extbase\Tests\Unit\Reflection;

use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Reflection\Exception\PropertyNotAccessibleException;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\ArrayAccessClass;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyClassWithGettersAndSetters;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ObjectAccessTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected DummyClassWithGettersAndSetters $dummyObject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dummyObject = new DummyClassWithGettersAndSetters();
        $this->dummyObject->setProperty('string1');
        $this->dummyObject->setAnotherProperty(42);
    }

    /**
     * @test
     */
    public function getPropertyReturnsExpectedValueForGetterProperty(): void
    {
        $property = ObjectAccess::getProperty($this->dummyObject, 'property');
        self::assertEquals('string1', $property);
    }

    /**
     * @test
     */
    public function getPropertyReturnsExpectedValueForPublicProperty(): void
    {
        $property = ObjectAccess::getProperty($this->dummyObject, 'publicProperty2');
        self::assertEquals(42, $property, 'A property of a given object was not returned correctly.');
    }

    /**
     * @test
     */
    public function getPropertyThrowsExceptionIfPropertyDoesNotExist(): void
    {
        $this->expectException(PropertyNotAccessibleException::class);
        $this->expectExceptionCode(1476109666);
        ObjectAccess::getProperty($this->dummyObject, 'notExistingProperty');
    }

    /**
     * @test
     */
    public function getPropertyReturnsNullIfArrayKeyDoesNotExist(): void
    {
        $result = ObjectAccess::getProperty([], 'notExistingProperty');
        self::assertNull($result);
    }

    /**
     * @test
     */
    public function getPropertyTriesToCallABooleanGetterMethodIfItExists(): void
    {
        $property = ObjectAccess::getProperty($this->dummyObject, 'booleanProperty');
        self::assertTrue($property);
    }

    /**
     * @test
     */
    public function setPropertyReturnsFalseIfPropertyIsNotAccessible(): void
    {
        self::assertFalse(ObjectAccess::setProperty($this->dummyObject, 'protectedProperty', 42));
    }

    /**
     * @test
     */
    public function setPropertyCallsASetterMethodToSetThePropertyValueIfOneIsAvailable(): void
    {
        ObjectAccess::setProperty($this->dummyObject, 'property', 4242);
        self::assertEquals(4242, $this->dummyObject->getProperty(), 'setProperty does not work with setter.');
    }

    /**
     * @test
     */
    public function setPropertyWorksWithPublicProperty(): void
    {
        ObjectAccess::setProperty($this->dummyObject, 'publicProperty', 4242);
        self::assertEquals(4242, $this->dummyObject->publicProperty, 'setProperty does not work with public property.');
    }

    /**
     * @test
     */
    public function setPropertyCanDirectlySetValuesInAnArrayObjectOrArray(): void
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
    public function getPropertyCanAccessPropertiesOfAnArrayObject(): void
    {
        $arrayObject = new \ArrayObject(['key' => 'value']);
        $actual = ObjectAccess::getProperty($arrayObject, 'key');
        self::assertEquals('value', $actual, 'getProperty does not work with ArrayObject property.');
    }

    /**
     * @test
     */
    public function getPropertyCanAccessPropertiesOfAnObjectStorageObject(): void
    {
        $objectStorage = new ObjectStorage();
        $object = new \stdClass();
        $objectStorage->attach($object);
        $actual = ObjectAccess::getProperty($objectStorage, '0');
        self::assertSame($object, $actual, 'getProperty does not work with ObjectStorage property.');
    }

    /**
     * @test
     */
    public function getPropertyCanAccessPropertiesOfAnObjectImplementingArrayAccess(): void
    {
        $arrayAccessInstance = new ArrayAccessClass(['key' => 'value']);
        $actual = ObjectAccess::getProperty($arrayAccessInstance, 'key');
        self::assertEquals('value', $actual, 'getProperty does not work with Array Access property.');
    }

    /**
     * @test
     */
    public function getPropertyCanAccessPropertiesOfArrayAccessWithGetterMethodWhenOffsetNotExists(): void
    {
        $arrayAccessInstance = new ArrayAccessClass([]);
        $actual = ObjectAccess::getProperty($arrayAccessInstance, 'virtual');
        self::assertEquals('default-value', $actual, 'getProperty does not work with Array Access property.');
    }

    /**
     * @test
     */
    public function getPropertyCanAccessPropertiesOfArrayAccessWithPriorityForOffsetIfOffsetExists(): void
    {
        $arrayAccessInstance = new ArrayAccessClass(['virtual' => 'overridden-value']);
        $actual = ObjectAccess::getProperty($arrayAccessInstance, 'virtual');
        self::assertEquals('overridden-value', $actual, 'getProperty does not work with Array Access property.');
    }

    /**
     * @test
     */
    public function getPropertyCanAccessPropertiesOfAnArray(): void
    {
        $array = ['key' => 'value'];
        $expected = ObjectAccess::getProperty($array, 'key');
        self::assertEquals('value', $expected, 'getProperty does not work with Array property.');
    }

    /**
     * @test
     */
    public function getPropertyPathCanAccessPropertiesOfAnArray(): void
    {
        $array = ['parent' => ['key' => 'value']];
        $actual = ObjectAccess::getPropertyPath($array, 'parent.key');
        self::assertEquals('value', $actual, 'getPropertyPath does not work with Array property.');
    }

    /**
     * @test
     */
    public function getPropertyPathCanAccessPropertiesOfAnObjectImplementingArrayAccess(): void
    {
        $array = ['parent' => new \ArrayObject(['key' => 'value'])];
        $actual = ObjectAccess::getPropertyPath($array, 'parent.key');
        self::assertEquals('value', $actual, 'getPropertyPath does not work with Array Access property.');
    }

    /**
     * @test
     */
    public function getPropertyPathCanAccessPropertiesOfAnExtbaseObjectStorageObject(): void
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
    public function getPropertyPathOnObjectStorageDoesNotAffectOngoingLoop(): void
    {
        $objectStorage = $this->setUpObjectStorageWithTwoItems();
        $i = 0;
        foreach ($objectStorage as $object) {
            ObjectAccess::getPropertyPath($objectStorage, '0.key');
            $i++;
        }
        self::assertSame(2, $i);
    }

    protected function setUpObjectStorageWithTwoItems(): ObjectStorage
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
    public function getPropertyPathCanAccessPropertiesOfAnSplObjectStorageObject(): void
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
    public function getPropertyPathOnSplObjectStorageDoesNotAffectOngoingLoop(): void
    {
        $objectStorage = $this->setUpSplObjectStorageWithTwoItems();
        $i = 0;
        foreach ($objectStorage as $object) {
            ObjectAccess::getPropertyPath($objectStorage, '0.key');
            $i++;
        }
        self::assertSame(2, $i);
    }

    protected function setUpSplObjectStorageWithTwoItems(): \SplObjectStorage
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
    public function getGettablePropertyNamesReturnsAllPropertiesWhichAreAvailable(): void
    {
        GeneralUtility::setSingletonInstance(ReflectionService::class, new ReflectionService(new NullFrontend('extbase'), 'ClassSchemata'));
        $gettablePropertyNames = ObjectAccess::getGettablePropertyNames($this->dummyObject);
        $expectedPropertyNames = ['anotherBooleanProperty', 'anotherProperty', 'booleanProperty', 'property', 'property2', 'publicProperty', 'publicProperty2', 'someValue'];
        self::assertEquals($gettablePropertyNames, $expectedPropertyNames, 'getGettablePropertyNames returns not all gettable properties.');
    }

    /**
     * @test
     */
    public function getGettablePropertyNamesRespectsMethodArguments(): void
    {
        $dateTimeZone = new \DateTimeZone('+2');
        GeneralUtility::setSingletonInstance(ReflectionService::class, new ReflectionService(new NullFrontend('extbase'), 'ClassSchemata'));
        $gettablePropertyNames = ObjectAccess::getGettablePropertyNames($dateTimeZone);
        $expectedPropertyNames = ['location', 'name'];
        foreach ($expectedPropertyNames as $expectedPropertyName) {
            self::assertContains($expectedPropertyName, $gettablePropertyNames);
        }
    }

    /**
     * @test
     */
    public function getSettablePropertyNamesReturnsAllPropertiesWhichAreAvailable(): void
    {
        GeneralUtility::setSingletonInstance(ReflectionService::class, new ReflectionService(new NullFrontend('extbase'), 'ClassSchemata'));
        $settablePropertyNames = ObjectAccess::getSettablePropertyNames($this->dummyObject);
        $expectedPropertyNames = ['anotherBooleanProperty', 'anotherProperty', 'property', 'property2', 'publicProperty', 'publicProperty2', 'writeOnlyMagicProperty'];
        self::assertEquals($settablePropertyNames, $expectedPropertyNames, 'getSettablePropertyNames returns not all settable properties.');
    }

    /**
     * @test
     */
    public function getSettablePropertyNamesReturnsPropertyNamesOfStdClass(): void
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
    public function getGettablePropertiesReturnsTheCorrectValuesForAllProperties(): void
    {
        GeneralUtility::setSingletonInstance(ReflectionService::class, new ReflectionService(new NullFrontend('extbase'), 'ClassSchemata'));
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
    public function getGettablePropertiesReturnsPropertiesOfStdClass(): void
    {
        $stdClassObject = new \stdClass();
        $stdClassObject->property = 'string1';
        $stdClassObject->property2 = null;
        $stdClassObject->publicProperty2 = 42;
        $allProperties = ObjectAccess::getGettableProperties($stdClassObject);
        $expectedProperties = [
            'property' => 'string1',
            'property2' => null,
            'publicProperty2' => 42,
        ];
        self::assertEquals($expectedProperties, $allProperties, 'expectedProperties did not return the right values for the properties.');
    }

    /**
     * @test
     */
    public function isPropertySettableTellsIfAPropertyCanBeSet(): void
    {
        self::assertTrue(ObjectAccess::isPropertySettable($this->dummyObject, 'writeOnlyMagicProperty'));
        self::assertTrue(ObjectAccess::isPropertySettable($this->dummyObject, 'publicProperty'));
        self::assertTrue(ObjectAccess::isPropertySettable($this->dummyObject, 'property'));
        self::assertFalse(ObjectAccess::isPropertySettable($this->dummyObject, 'privateProperty'));
    }

    /**
     * @test
     */
    public function isPropertySettableWorksOnStdClass(): void
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
    public function isPropertyGettableTellsIfAPropertyCanBeRetrieved($property, $expected): void
    {
        self::assertEquals($expected, ObjectAccess::isPropertyGettable($this->dummyObject, $property));
    }

    public static function propertyGettableTestValues(): array
    {
        return [
            ['publicProperty', true],
            ['property', true],
            ['booleanProperty', true],
            ['anotherBooleanProperty', true],
            ['privateProperty', false],
            ['writeOnlyMagicProperty', false],
        ];
    }

    /**
     * @test
     */
    public function isPropertyGettableWorksOnArrayAccessObjects(): void
    {
        $arrayObject = new \ArrayObject();
        $arrayObject['key'] = 'v';
        self::assertTrue(ObjectAccess::isPropertyGettable($arrayObject, 'key'));
        self::assertFalse(ObjectAccess::isPropertyGettable($arrayObject, 'undefinedKey'));
    }

    /**
     * @test
     */
    public function isPropertyGettableWorksOnObjectsMixingRegularPropertiesAndArrayAccess(): void
    {
        /** @var \ArrayAccess $object */
        $object = new class () extends \ArrayObject {
            private $regularProperty = 'foo';

            public function getRegularProperty(): string
            {
                return $this->regularProperty;
            }
        };

        $object['key'] = 'v';
        self::assertTrue(ObjectAccess::isPropertyGettable($object, 'regularProperty'));
        self::assertTrue(ObjectAccess::isPropertyGettable($object, 'key'));

        self::assertSame('foo', ObjectAccess::getProperty($object, 'regularProperty'));
        self::assertSame('v', ObjectAccess::getProperty($object, 'key'));
    }

    /**
     * @test
     */
    public function isPropertyGettableWorksOnStdClass(): void
    {
        $stdClassObject = new \stdClass();
        $stdClassObject->property = 'foo';
        self::assertTrue(ObjectAccess::isPropertyGettable($stdClassObject, 'property'));
        self::assertFalse(ObjectAccess::isPropertyGettable($stdClassObject, 'undefinedProperty'));
    }

    /**
     * @test
     */
    public function getPropertyPathCanRecursivelyGetPropertiesOfAnObject(): void
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
    public function getPropertyPathReturnsNullForNonExistingPropertyPath(): void
    {
        $alternativeObject = new DummyClassWithGettersAndSetters();
        $alternativeObject->setProperty(new \stdClass());
        $this->dummyObject->setProperty2($alternativeObject);
        self::assertNull(ObjectAccess::getPropertyPath($this->dummyObject, 'property2.property.not.existing'));
    }

    /**
     * @test
     */
    public function getPropertyPathReturnsNullIfSubjectIsNoObject(): void
    {
        $string = 'Hello world';
        self::assertNull(ObjectAccess::getPropertyPath($string, 'property2'));
    }

    /**
     * @test
     */
    public function getPropertyPathReturnsNullIfSubjectOnPathIsNoObject(): void
    {
        $object = new \stdClass();
        $object->foo = 'Hello World';
        self::assertNull(ObjectAccess::getPropertyPath($object, 'foo.bar'));
    }
}
