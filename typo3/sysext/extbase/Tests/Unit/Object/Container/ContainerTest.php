<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Object\Container;

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
class ContainerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\Container\Container
     */
    protected $container;

    /**
     * @var \TYPO3\CMS\Extbase\Object\Container\ClassInfo
     */
    protected $cachedClassInfo;

    protected function setUp()
    {
        // The mocked cache will always indicate that he has nothing in the cache to force that we get the real class info
        $mockedCache = $this->getMock(\TYPO3\CMS\Extbase\Object\Container\ClassInfoCache::class, ['get', 'set']);
        $mockedCache->expects($this->any())->method('get')->will($this->returnValue(false));
        $mockedCache->expects($this->never())->method('has');
        $this->container = $this->getMock(\TYPO3\CMS\Extbase\Object\Container\Container::class, ['log', 'getClassInfoCache']);
        $this->container->expects($this->any())->method('getClassInfoCache')->will($this->returnValue($mockedCache));
    }

    /**
     * @test
     */
    public function getInstanceReturnsInstanceOfSimpleClass()
    {
        $object = $this->container->getInstance('t3lib_object_tests_c');
        $this->assertInstanceOf('t3lib_object_tests_c', $object);
    }

    /**
     * @test
     */
    public function getInstanceReturnsInstanceOfSimpleNamespacedClass()
    {
        $object = $this->container->getInstance(\TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\NamespacedClass::class);
        $this->assertInstanceOf(\TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\NamespacedClass::class, $object);
    }

    /**
     * @test
     */
    public function getInstanceReturnsInstanceOfAClassWithConstructorInjection()
    {
        $object = $this->container->getInstance('t3lib_object_tests_b');
        $this->assertInstanceOf('t3lib_object_tests_b', $object);
        $this->assertInstanceOf('t3lib_object_tests_c', $object->c);
    }

    /**
     * @test
     */
    public function getInstanceReturnsInstanceOfAClassWithTwoLevelDependency()
    {
        $object = $this->container->getInstance('t3lib_object_tests_a');
        $this->assertInstanceOf('t3lib_object_tests_a', $object);
        $this->assertInstanceOf('t3lib_object_tests_c', $object->b->c);
    }

    /**
     * @test
     */
    public function getInstanceReturnsInstanceOfAClassWithMixedSimpleTypeAndConstructorInjection()
    {
        $object = $this->container->getInstance('t3lib_object_tests_amixed_array');
        $this->assertInstanceOf('t3lib_object_tests_amixed_array', $object);
        $this->assertEquals(['some' => 'default'], $object->myvalue);
    }

    /**
     * @test
     */
    public function getInstanceReturnsInstanceOfAClassWithMixedSimpleTypeAndConstructorInjectionWithNullDefaultValue()
    {
        $object = $this->container->getInstance('t3lib_object_tests_amixed_null');
        $this->assertInstanceOf('t3lib_object_tests_amixed_null', $object);
        $this->assertNull($object->myvalue);
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Extbase\Object\Exception
     */
    public function getInstanceThrowsExceptionWhenTryingToInstanciateASingletonWithConstructorParameters()
    {
        $this->container->getInstance('t3lib_object_tests_amixed_array_singleton', ['somevalue']);
    }

    /**
     * @test
     */
    public function getInstanceReturnsInstanceOfAClassWithConstructorInjectionAndDefaultConstructorParameters()
    {
        $object = $this->container->getInstance('t3lib_object_tests_amixed_array');
        $this->assertInstanceOf('t3lib_object_tests_b', $object->b);
        $this->assertInstanceOf('t3lib_object_tests_c', $object->c);
        $this->assertEquals(['some' => 'default'], $object->myvalue);
    }

    /**
     * @test
     */
    public function getInstancePassesGivenParameterToTheNewObject()
    {
        $mockObject = $this->getMock('t3lib_object_tests_c');
        $object = $this->container->getInstance('t3lib_object_tests_a', [$mockObject]);
        $this->assertInstanceOf('t3lib_object_tests_a', $object);
        $this->assertSame($mockObject, $object->c);
    }

    /**
     * @test
     */
    public function getInstanceReturnsAFreshInstanceIfObjectIsNoSingleton()
    {
        $object1 = $this->container->getInstance('t3lib_object_tests_a');
        $object2 = $this->container->getInstance('t3lib_object_tests_a');
        $this->assertNotSame($object1, $object2);
    }

    /**
     * @test
     */
    public function getInstanceReturnsSameInstanceInstanceIfObjectIsSingleton()
    {
        $object1 = $this->container->getInstance('t3lib_object_tests_singleton');
        $object2 = $this->container->getInstance('t3lib_object_tests_singleton');
        $this->assertSame($object1, $object2);
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Extbase\Object\Exception\CannotBuildObjectException
     */
    public function getInstanceThrowsExceptionIfPrototypeObjectsWiredViaConstructorInjectionContainCyclicDependencies()
    {
        $this->container->getInstance('t3lib_object_tests_cyclic1WithSetterDependency');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Extbase\Object\Exception\CannotBuildObjectException
     */
    public function getInstanceThrowsExceptionIfPrototypeObjectsWiredViaSetterInjectionContainCyclicDependencies()
    {
        $this->container->getInstance('t3lib_object_tests_cyclic1');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Extbase\Object\Exception
     */
    public function getInstanceThrowsExceptionIfClassWasNotFound()
    {
        $this->container->getInstance('nonextistingclass_bla');
    }

    /**
     * @test
     */
    public function getInstanceUsesClassNameMd5AsCacheKey()
    {
        $className = \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\NamespacedClass::class;
        $classNameHash = md5($className);
        $mockedCache = $this->getMock(\TYPO3\CMS\Extbase\Object\Container\ClassInfoCache::class, ['has', 'set', 'get']);
        $container = $this->getMock(\TYPO3\CMS\Extbase\Object\Container\Container::class, ['log', 'getClassInfoCache']);
        $container->expects($this->any())->method('getClassInfoCache')->will($this->returnValue($mockedCache));
        $mockedCache->expects($this->never())->method('has');
        $mockedCache->expects($this->once())->method('get')->with($classNameHash)->will($this->returnValue(false));
        $mockedCache->expects($this->once())->method('set')->with($classNameHash, $this->anything())->will($this->returnCallback([$this, 'setClassInfoCacheCallback']));
        $container->getInstance($className);
        $this->assertInstanceOf(\TYPO3\CMS\Extbase\Object\Container\ClassInfo::class, $this->cachedClassInfo);
        $this->assertEquals($className, $this->cachedClassInfo->getClassName());
    }

    /**
     * @test
     */
    public function getInstanceInitializesObjects()
    {
        $instance = $this->container->getInstance('t3lib_object_tests_initializable');
        $this->assertTrue($instance->isInitialized());
    }

    /**
     * Callback for getInstanceUsesClassNameSha1AsCacheKey
     *
     * @param string $id
     * @param \TYPO3\CMS\Extbase\Object\Container\ClassInfo $value
     * @return void
     */
    public function setClassInfoCacheCallback($id, \TYPO3\CMS\Extbase\Object\Container\ClassInfo $value)
    {
        $this->cachedClassInfo = $value;
    }

    /**
     * @test
     */
    public function getEmptyObjectReturnsInstanceOfSimpleClass()
    {
        $object = $this->container->getEmptyObject('t3lib_object_tests_c');
        $this->assertInstanceOf('t3lib_object_tests_c', $object);
    }

    /**
     * @test
     */
    public function getEmptyObjectReturnsInstanceOfClassImplementingSerializable()
    {
        $object = $this->container->getEmptyObject('t3lib_object_tests_serializable');
        $this->assertInstanceOf('t3lib_object_tests_serializable', $object);
    }

    /**
     * @test
     */
    public function getEmptyObjectInitializesObjects()
    {
        $object = $this->container->getEmptyObject('t3lib_object_tests_initializable');
        $this->assertTrue($object->isInitialized());
    }

    /**
     * @test
     */
    public function test_canGetChildClass()
    {
        $object = $this->container->getInstance('t3lib_object_tests_b_child');
        $this->assertInstanceOf('t3lib_object_tests_b_child', $object);
    }

    /**
     * @test
     */
    public function test_canInjectInterfaceInClass()
    {
        $this->container->registerImplementation('t3lib_object_tests_someinterface', 't3lib_object_tests_someimplementation');
        $object = $this->container->getInstance('t3lib_object_tests_needsinterface');
        $this->assertInstanceOf('t3lib_object_tests_needsinterface', $object);
        $this->assertInstanceOf('t3lib_object_tests_someinterface', $object->dependency);
        $this->assertInstanceOf('t3lib_object_tests_someimplementation', $object->dependency);
    }

    /**
     * @test
     */
    public function test_canBuildCyclicDependenciesOfSingletonsWithSetter()
    {
        $object = $this->container->getInstance('t3lib_object_tests_resolveablecyclic1');
        $this->assertInstanceOf('t3lib_object_tests_resolveablecyclic1', $object);
        $this->assertInstanceOf('t3lib_object_tests_resolveablecyclic1', $object->o2->o3->o1);
    }

    /**
     * @test
     */
    public function singletonWhichRequiresPrototypeViaSetterInjectionWorksAndAddsDebugMessage()
    {
        $this->container->expects($this->once())->method('log')->with('The singleton "t3lib_object_singletonNeedsPrototype" needs a prototype in "injectDependency". This is often a bad code smell; often you rather want to inject a singleton.', 1);
        $object = $this->container->getInstance('t3lib_object_singletonNeedsPrototype');
        $this->assertInstanceOf('t3lib_object_prototype', $object->dependency);
    }

    /**
     * @test
     */
    public function singletonWhichRequiresSingletonViaSetterInjectionWorks()
    {
        $this->container->expects($this->never())->method('log');
        $object = $this->container->getInstance('t3lib_object_singletonNeedsSingleton');
        $this->assertInstanceOf('t3lib_object_singleton', $object->dependency);
    }

    /**
     * @test
     */
    public function prototypeWhichRequiresPrototypeViaSetterInjectionWorks()
    {
        $this->container->expects($this->never())->method('log');
        $object = $this->container->getInstance('t3lib_object_prototypeNeedsPrototype');
        $this->assertInstanceOf('t3lib_object_prototype', $object->dependency);
    }

    /**
     * @test
     */
    public function prototypeWhichRequiresSingletonViaSetterInjectionWorks()
    {
        $this->container->expects($this->never())->method('log');
        $object = $this->container->getInstance('t3lib_object_prototypeNeedsSingleton');
        $this->assertInstanceOf('t3lib_object_singleton', $object->dependency);
    }

    /**
     * @test
     */
    public function singletonWhichRequiresPrototypeViaConstructorInjectionWorksAndAddsDebugMessage()
    {
        $this->container->expects($this->once())->method('log')->with('The singleton "t3lib_object_singletonNeedsPrototypeInConstructor" needs a prototype in the constructor. This is often a bad code smell; often you rather want to inject a singleton.', 1);
        $object = $this->container->getInstance('t3lib_object_singletonNeedsPrototypeInConstructor');
        $this->assertInstanceOf('t3lib_object_prototype', $object->dependency);
    }

    /**
     * @test
     */
    public function singletonWhichRequiresSingletonViaConstructorInjectionWorks()
    {
        $this->container->expects($this->never())->method('log');
        $object = $this->container->getInstance('t3lib_object_singletonNeedsSingletonInConstructor');
        $this->assertInstanceOf('t3lib_object_singleton', $object->dependency);
    }

    /**
     * @test
     */
    public function prototypeWhichRequiresPrototypeViaConstructorInjectionWorks()
    {
        $this->container->expects($this->never())->method('log');
        $object = $this->container->getInstance('t3lib_object_prototypeNeedsPrototypeInConstructor');
        $this->assertInstanceOf('t3lib_object_prototype', $object->dependency);
    }

    /**
     * @test
     */
    public function prototypeWhichRequiresSingletonViaConstructorInjectionWorks()
    {
        $this->container->expects($this->never())->method('log');
        $object = $this->container->getInstance('t3lib_object_prototypeNeedsSingletonInConstructor');
        $this->assertInstanceOf('t3lib_object_singleton', $object->dependency);
    }

    /**
     * @test
     */
    public function isSingletonReturnsTrueForSingletonInstancesAndFalseForPrototypes()
    {
        $this->assertTrue($this->container->isSingleton(\TYPO3\CMS\Extbase\Object\Container\Container::class));
        $this->assertFalse($this->container->isSingleton(\TYPO3\CMS\Extbase\Core\Bootstrap::class));
    }

    /**
     * @test
     */
    public function isPrototypeReturnsFalseForSingletonInstancesAndTrueForPrototypes()
    {
        $this->assertFalse($this->container->isPrototype(\TYPO3\CMS\Extbase\Object\Container\Container::class));
        $this->assertTrue($this->container->isPrototype(\TYPO3\CMS\Extbase\Core\Bootstrap::class));
    }

    /************************************************
     * Test regarding constructor argument injection
     ************************************************/

    /**
     * test class SimpleTypeConstructorArgument
     * @test
     */
    public function getInstanceGivesSimpleConstructorArgumentToClassInstance()
    {
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\SimpleTypeConstructorArgument::class,
            [true]
        );
        $this->assertTrue($object->foo);
    }

    /**
     * test class SimpleTypeConstructorArgument
     * @test
     */
    public function getInstanceDoesNotInfluenceSimpleTypeConstructorArgumentIfNotGiven()
    {
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\SimpleTypeConstructorArgument::class
        );
        $this->assertFalse($object->foo);
    }

    /**
     * test class MandatoryConstructorArgument
     * @test
     */
    public function getInstanceGivesExistingConstructorArgumentToClassInstance()
    {
        $argumentTestClass = new Fixtures\ArgumentTestClass();
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\MandatoryConstructorArgument::class,
            [$argumentTestClass]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\MandatoryConstructorArgument::class,
            $object
        );
        $this->assertSame($argumentTestClass, $object->argumentTestClass);
    }

    /**
     * test class MandatoryConstructorArgument
     * @test
     */
    public function getInstanceInjectsNewInstanceOfClassToClassIfArgumentIsMandatory()
    {
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\MandatoryConstructorArgument::class
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\MandatoryConstructorArgument::class,
            $object
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\ArgumentTestClass::class,
            $object->argumentTestClass
        );
    }

    /**
     * test class OptionalConstructorArgument
     * @test
     */
    public function getInstanceDoesNotInjectAnOptionalArgumentIfNotGiven()
    {
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\OptionalConstructorArgument::class
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\OptionalConstructorArgument::class,
            $object
        );
        $this->assertNull($object->argumentTestClass);
    }

    /**
     * test class OptionalConstructorArgument
     * @test
     */
    public function getInstanceDoesNotInjectAnOptionalArgumentIfGivenArgumentIsNull()
    {
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\OptionalConstructorArgument::class,
            [null]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\OptionalConstructorArgument::class,
            $object
        );
        $this->assertNull($object->argumentTestClass);
    }

    /**
     * test class OptionalConstructorArgument
     * @test
     */
    public function getInstanceGivesExistingConstructorArgumentToClassInstanceIfArgumentIsGiven()
    {
        $argumentTestClass = new Fixtures\ArgumentTestClass();
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\OptionalConstructorArgument::class,
            [$argumentTestClass]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\OptionalConstructorArgument::class,
            $object
        );
        $this->assertSame($argumentTestClass, $object->argumentTestClass);
    }

    /**
     * test class MandatoryConstructorArgumentTwo
     * @test
     */
    public function getInstanceGivesTwoArgumentsToClassConstructor()
    {
        $firstArgument = new Fixtures\ArgumentTestClass();
        $secondArgument = new Fixtures\ArgumentTestClass();
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\MandatoryConstructorArgumentTwo::class,
            [$firstArgument, $secondArgument]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\MandatoryConstructorArgumentTwo::class,
            $object
        );
        $this->assertSame(
            $firstArgument,
            $object->argumentTestClass
        );
        $this->assertSame(
            $secondArgument,
            $object->argumentTestClassTwo
        );
    }

    /**
     * test class MandatoryConstructorArgumentTwo
     * @test
     */
    public function getInstanceInjectsTwoMandatoryArguments()
    {
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\MandatoryConstructorArgumentTwo::class
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\MandatoryConstructorArgumentTwo::class,
            $object
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\ArgumentTestClass::class,
            $object->argumentTestClass
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\ArgumentTestClass::class,
            $object->argumentTestClassTwo
        );
        $this->assertNotSame(
            $object->argumentTestClass,
            $object->argumentTestClassTwo
        );
    }

    /**
     * test class MandatoryConstructorArgumentTwo
     * @test
     */
    public function getInstanceInjectsSecondMandatoryArgumentIfFirstIsGiven()
    {
        $firstArgument = new Fixtures\ArgumentTestClass();
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\MandatoryConstructorArgumentTwo::class,
            [$firstArgument]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\MandatoryConstructorArgumentTwo::class,
            $object
        );
        $this->assertSame(
            $firstArgument,
            $object->argumentTestClass
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\ArgumentTestClass::class,
            $object->argumentTestClassTwo
        );
        $this->assertNotSame(
            $object->argumentTestClass,
            $object->argumentTestClassTwo
        );
    }

    /**
     * test class MandatoryConstructorArgumentTwo
     * @test
     */
    public function getInstanceInjectsFirstMandatoryArgumentIfSecondIsGiven()
    {
        $secondArgument = new Fixtures\ArgumentTestClass();
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\MandatoryConstructorArgumentTwo::class,
            [null, $secondArgument]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\MandatoryConstructorArgumentTwo::class,
            $object
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\ArgumentTestClass::class,
            $object->argumentTestClass
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\ArgumentTestClass::class,
            $object->argumentTestClassTwo
        );
        $this->assertSame(
            $secondArgument,
            $object->argumentTestClassTwo
        );
        $this->assertNotSame(
            $object->argumentTestClass,
            $object->argumentTestClassTwo
        );
    }

    /**
     * test class TwoConstructorArgumentsSecondOptional
     * @test
     */
    public function getInstanceGivesTwoArgumentsToClassConstructorIfSecondIsOptional()
    {
        $firstArgument = new Fixtures\ArgumentTestClass();
        $secondArgument = new Fixtures\ArgumentTestClass();
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsSecondOptional::class,
            [$firstArgument, $secondArgument]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsSecondOptional::class,
            $object
        );
        $this->assertSame(
            $firstArgument,
            $object->argumentTestClass
        );
        $this->assertSame(
            $secondArgument,
            $object->argumentTestClassTwo
        );
    }

    /**
     * test class TwoConstructorArgumentsSecondOptional
     * @test
     */
    public function getInstanceInjectsFirstMandatoryArgumentIfSecondIsOptionalAndNoneAreGiven()
    {
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsSecondOptional::class
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsSecondOptional::class,
            $object
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\ArgumentTestClass::class,
            $object->argumentTestClass
        );
        $this->assertNull($object->argumentTestClassTwo);
    }

    /**
     * test class TwoConstructorArgumentsSecondOptional
     * @test
     */
    public function getInstanceInjectsFirstMandatoryArgumentIfSecondIsOptionalAndBothAreGivenAsNull()
    {
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsSecondOptional::class,
            [null, null]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsSecondOptional::class,
            $object
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\ArgumentTestClass::class,
            $object->argumentTestClass
        );
        $this->assertNull($object->argumentTestClassTwo);
    }

    /**
     * test class TwoConstructorArgumentsSecondOptional
     * @test
     */
    public function getInstanceGivesFirstArgumentToConstructorIfSecondIsOptionalAndFirstIsGiven()
    {
        $firstArgument = new Fixtures\ArgumentTestClass();
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsSecondOptional::class,
            [$firstArgument]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsSecondOptional::class,
            $object
        );
        $this->assertSame(
            $firstArgument,
            $object->argumentTestClass
        );
        $this->assertNull($object->argumentTestClassTwo);
    }

    /**
     * test class TwoConstructorArgumentsSecondOptional
     * @test
     */
    public function getInstanceGivesFirstArgumentToConstructorIfSecondIsOptionalFirstIsGivenAndSecondIsGivenNull()
    {
        $firstArgument = new Fixtures\ArgumentTestClass();
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsSecondOptional::class,
            [$firstArgument, null]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsSecondOptional::class,
            $object
        );
        $this->assertSame(
            $firstArgument,
            $object->argumentTestClass
        );
        $this->assertNull($object->argumentTestClassTwo);
    }

    /**
     * test class TwoConstructorArgumentsFirstOptional
     *
     * @test
     */
    public function getInstanceOnFirstOptionalAndSecondMandatoryInjectsSecondArgumentIfFirstIsGivenAsNull()
    {
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsFirstOptional::class,
            [null]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsFirstOptional::class,
            $object
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\ArgumentTestClass::class,
            $object->argumentTestClassTwo
        );
    }

    /**
     * test class TwoConstructorArgumentsFirstOptional
     * @test
     */
    public function getInstanceOnFirstOptionalAndSecondMandatoryGivesTwoGivenArgumentsToConstructor()
    {
        $first = new Fixtures\ArgumentTestClass();
        $second = new Fixtures\ArgumentTestClass();
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsFirstOptional::class,
            [$first, $second]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsFirstOptional::class,
            $object
        );
        $this->assertSame(
            $first,
            $object->argumentTestClass
        );
        $this->assertSame(
            $second,
            $object->argumentTestClassTwo
        );
    }

    /**
     * test class TwoConstructorArgumentsFirstOptional
     * @test
     */
    public function getInstanceOnFirstOptionalAndSecondMandatoryInjectsSecondArgumentIfFirstIsGiven()
    {
        $first = new Fixtures\ArgumentTestClass();
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsFirstOptional::class,
            [$first]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsFirstOptional::class,
            $object
        );
        $this->assertSame(
            $first,
            $object->argumentTestClass
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\ArgumentTestClass::class,
            $object->argumentTestClassTwo
        );
        $this->assertNotSame(
            $object->argumentTestClass,
            $object->argumentTestClassTwo
        );
    }

    /**
     * test class TwoConstructorArgumentsFirstOptional
     *
     * @test
     */
    public function getInstanceOnFirstOptionalAndSecondMandatoryGivesSecondArgumentAsIsIfFirstIsGivenAsNullAndSecondIsGiven()
    {
        $second = new Fixtures\ArgumentTestClass();
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsFirstOptional::class,
            [null, $second]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsFirstOptional::class,
            $object
        );
        $this->assertSame(
            $second,
            $object->argumentTestClassTwo
        );
    }

    /**
     * test class TwoConstructorArgumentsFirstOptional
     *
     * @test
     */
    public function getInstanceOnFirstOptionalAndSecondMandatoryInjectsSecondArgumentIfFirstIsGivenAsNullAndSecondIsNull()
    {
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsFirstOptional::class,
            [null, null]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsFirstOptional::class,
            $object
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\ArgumentTestClass::class,
            $object->argumentTestClassTwo
        );
    }

    /**
     * test class TwoConstructorArgumentsBothOptional
     * @test
     */
    public function getInstanceOnTwoOptionalGivesTwoGivenArgumentsToConstructor()
    {
        $first = new Fixtures\ArgumentTestClass();
        $second = new Fixtures\ArgumentTestClass();
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsBothOptional::class,
            [$first, $second]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsBothOptional::class,
            $object
        );
        $this->assertSame(
            $first,
            $object->argumentTestClass
        );
        $this->assertSame(
            $second,
            $object->argumentTestClassTwo
        );
    }

    /**
     * test class TwoConstructorArgumentsBothOptional
     * @test
     */
    public function getInstanceOnTwoOptionalGivesNoArgumentsToConstructorIfArgumentsAreNull()
    {
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsBothOptional::class,
            [null, null]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsBothOptional::class,
            $object
        );
        $this->assertNull($object->argumentTestClass);
        $this->assertNull($object->argumentTestClassTwo);
    }

    /**
     * test class TwoConstructorArgumentsBothOptional
     * @test
     */
    public function getInstanceOnTwoOptionalGivesNoArgumentsToConstructorIfNoneAreGiven()
    {
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsBothOptional::class);
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsBothOptional::class,
            $object
        );
        $this->assertNull($object->argumentTestClass);
        $this->assertNull($object->argumentTestClassTwo);
    }

    /**
     * test class TwoConstructorArgumentsBothOptional
     * @test
     */
    public function getInstanceOnTwoOptionalGivesOneArgumentToConstructorIfFirstIsObjectAndSecondIsNotGiven()
    {
        $first = new Fixtures\ArgumentTestClass();
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsBothOptional::class,
            [$first]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsBothOptional::class,
            $object
        );
        $this->assertSame(
            $first,
            $object->argumentTestClass
        );
        $this->assertNull($object->argumentTestClassTwo);
    }

    /**
     * test class TwoConstructorArgumentsBothOptional
     * @test
     */
    public function getInstanceOnTwoOptionalGivesOneArgumentToConstructorIfFirstIsObjectAndSecondIsNull()
    {
        $first = new Fixtures\ArgumentTestClass();
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsBothOptional::class,
            [$first, null]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsBothOptional::class,
            $object
        );
        $this->assertSame(
            $first,
            $object->argumentTestClass
        );
        $this->assertNull($object->argumentTestClassTwo);
    }

    /**
     * test class TwoConstructorArgumentsBothOptional
     * @test
     */
    public function getInstanceOnTwoOptionalGivesOneArgumentToConstructorIfFirstIsNullAndSecondIsObject()
    {
        $second = new Fixtures\ArgumentTestClass();
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsBothOptional::class,
            [null, $second]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsBothOptional::class,
            $object
        );
        $this->assertNull($object->argumentTestClass);
        $this->assertSame(
            $second,
            $object->argumentTestClassTwo
        );
    }
}
