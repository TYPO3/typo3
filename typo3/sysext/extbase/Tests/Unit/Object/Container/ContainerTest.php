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

namespace TYPO3\CMS\Extbase\Tests\Unit\Object\Container;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Extbase\Object\Container\Container;
use TYPO3\CMS\Extbase\Object\Exception;
use TYPO3\CMS\Extbase\Object\Exception\CannotBuildObjectException;
use TYPO3\CMS\Extbase\Reflection\Exception\UnknownClassException;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;
use TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\ArgumentTestClass;
use TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\MandatoryConstructorArgument;
use TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\MandatoryConstructorArgumentTwo;
use TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\NamespacedClass;
use TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\OptionalConstructorArgument;
use TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\SimpleTypeConstructorArgument;
use TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsBothOptional;
use TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsFirstOptional;
use TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsSecondOptional;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ContainerTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @var Container
     */
    protected Container $subject;

    protected LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = new class () implements LoggerInterface {
            use LoggerTrait;
            public array $records = [];
            public function log($level, $message, array $context = []): void
            {
                $this->records[] = [
                    'level' => $level,
                    'message' => $message,
                    'context' => $context,
                ];
            }
        };

        $reflectionService = new ReflectionService(new NullFrontend('extbase'), 'ClassSchemata');

        $notFoundException = new class () extends \Exception implements NotFoundExceptionInterface {
        };

        $psrContainer = $this->getMockBuilder(ContainerInterface::class)
            ->onlyMethods(['has', 'get'])
            ->getMock();
        $psrContainer->method('has')->willReturn(false);
        $psrContainer->method('get')->will(self::throwException($notFoundException));

        $this->subject = $this->getMockBuilder(Container::class)
            ->setConstructorArgs([$psrContainer])
            ->onlyMethods(['getReflectionService'])
            ->addMethods(['getLogger'])
            ->getMock();
        $this->subject->setLogger($this->logger);
        $this->subject->method('getReflectionService')->willReturn($reflectionService);
    }

    /**
     * @test
     */
    public function getInstanceReturnsInstanceOfSimpleClass(): void
    {
        $object = $this->subject->getInstance('t3lib_object_tests_c');
        self::assertInstanceOf('t3lib_object_tests_c', $object);
    }

    /**
     * @test
     */
    public function getInstanceReturnsInstanceOfSimpleNamespacedClass(): void
    {
        $object = $this->subject->getInstance(NamespacedClass::class);
        self::assertInstanceOf(NamespacedClass::class, $object);
    }

    /**
     * @test
     */
    public function getInstanceReturnsInstanceOfAClassWithConstructorInjection(): void
    {
        $object = $this->subject->getInstance('t3lib_object_tests_b');
        self::assertInstanceOf('t3lib_object_tests_b', $object);
        self::assertInstanceOf('t3lib_object_tests_c', $object->c);
    }

    /**
     * @test
     */
    public function getInstanceReturnsInstanceOfAClassWithTwoLevelDependency(): void
    {
        $object = $this->subject->getInstance('t3lib_object_tests_a');
        self::assertInstanceOf('t3lib_object_tests_a', $object);
        self::assertInstanceOf('t3lib_object_tests_c', $object->b->c);
    }

    /**
     * @test
     */
    public function getInstanceReturnsInstanceOfAClassWithMixedSimpleTypeAndConstructorInjection(): void
    {
        $object = $this->subject->getInstance('t3lib_object_tests_amixed_array');
        self::assertInstanceOf('t3lib_object_tests_amixed_array', $object);
        self::assertEquals(['some' => 'default'], $object->myvalue);
    }

    /**
     * @test
     */
    public function getInstanceReturnsInstanceOfAClassWithMixedSimpleTypeAndConstructorInjectionWithNullDefaultValue(): void
    {
        $object = $this->subject->getInstance('t3lib_object_tests_amixed_null');
        self::assertInstanceOf('t3lib_object_tests_amixed_null', $object);
        self::assertNull($object->myvalue);
    }

    /**
     * @test
     */
    public function getInstanceThrowsExceptionWhenTryingToInstanciateASingletonWithConstructorParameters(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1292858051);
        $this->subject->getInstance('t3lib_object_tests_amixed_array_singleton', ['somevalue']);
    }

    /**
     * @test
     */
    public function getInstanceReturnsInstanceOfAClassWithConstructorInjectionAndDefaultConstructorParameters(): void
    {
        $object = $this->subject->getInstance('t3lib_object_tests_amixed_array');
        self::assertInstanceOf('t3lib_object_tests_b', $object->b);
        self::assertInstanceOf('t3lib_object_tests_c', $object->c);
        self::assertEquals(['some' => 'default'], $object->myvalue);
    }

    /**
     * @test
     */
    public function getInstancePassesGivenParameterToTheNewObject(): void
    {
        $mockObject = $this->createMock('t3lib_object_tests_c');
        $object = $this->subject->getInstance('t3lib_object_tests_a', [$mockObject]);
        self::assertInstanceOf('t3lib_object_tests_a', $object);
        self::assertSame($mockObject, $object->c);
    }

    /**
     * @test
     */
    public function getInstanceReturnsAFreshInstanceIfObjectIsNoSingleton(): void
    {
        $object1 = $this->subject->getInstance('t3lib_object_tests_a');
        $object2 = $this->subject->getInstance('t3lib_object_tests_a');
        self::assertNotSame($object1, $object2);
    }

    /**
     * @test
     */
    public function getInstanceReturnsSameInstanceInstanceIfObjectIsSingleton(): void
    {
        $object1 = $this->subject->getInstance('t3lib_object_tests_singleton');
        $object2 = $this->subject->getInstance('t3lib_object_tests_singleton');
        self::assertSame($object1, $object2);
    }

    /**
     * @test
     */
    public function getInstanceThrowsExceptionIfPrototypeObjectsWiredViaConstructorInjectionContainCyclicDependencies(): void
    {
        $this->expectException(CannotBuildObjectException::class);
        $this->expectExceptionCode(1295611406);
        $this->subject->getInstance('t3lib_object_tests_cyclic1WithSetterDependency');
    }

    /**
     * @test
     */
    public function getInstanceThrowsExceptionIfPrototypeObjectsWiredViaSetterInjectionContainCyclicDependencies(): void
    {
        $this->expectException(CannotBuildObjectException::class);
        $this->expectExceptionCode(1295611406);
        $this->subject->getInstance('t3lib_object_tests_cyclic1');
    }

    /**
     * @test
     */
    public function getInstanceThrowsExceptionIfClassWasNotFound(): void
    {
        $this->expectException(UnknownClassException::class);
        $this->expectExceptionCode(1278450972);
        $this->subject->getInstance('nonextistingclass_bla');
    }

    /**
     * @test
     */
    public function getInstanceInitializesObjects(): void
    {
        $instance = $this->subject->getInstance('t3lib_object_tests_initializable');
        self::assertTrue($instance->isInitialized());
    }

    /**
     * @test
     */
    public function getEmptyObjectReturnsInstanceOfSimpleClass(): void
    {
        $object = $this->subject->getEmptyObject('t3lib_object_tests_c');
        self::assertInstanceOf('t3lib_object_tests_c', $object);
    }

    /**
     * @test
     */
    public function getEmptyObjectReturnsInstanceOfClassImplementingSerializable(): void
    {
        $object = $this->subject->getEmptyObject('t3lib_object_tests_serializable');
        self::assertInstanceOf('t3lib_object_tests_serializable', $object);
    }

    /**
     * @test
     */
    public function getEmptyObjectInitializesObjects(): void
    {
        $object = $this->subject->getEmptyObject('t3lib_object_tests_initializable');
        self::assertTrue($object->isInitialized());
    }

    /**
     * @test
     */
    public function canGetChildClass(): void
    {
        $object = $this->subject->getInstance('t3lib_object_tests_b_child');
        self::assertInstanceOf('t3lib_object_tests_b_child', $object);
    }

    /**
     * @test
     */
    public function canInjectInterfaceInClass(): void
    {
        $this->subject->registerImplementation('t3lib_object_tests_someinterface', 't3lib_object_tests_someimplementation');
        $object = $this->subject->getInstance('t3lib_object_tests_needsinterface');
        self::assertInstanceOf('t3lib_object_tests_needsinterface', $object);
        self::assertInstanceOf('t3lib_object_tests_someinterface', $object->dependency);
        self::assertInstanceOf('t3lib_object_tests_someimplementation', $object->dependency);
    }

    /**
     * @test
     */
    public function canBuildCyclicDependenciesOfSingletonsWithSetter(): void
    {
        $object = $this->subject->getInstance('t3lib_object_tests_resolveablecyclic1');
        self::assertInstanceOf('t3lib_object_tests_resolveablecyclic1', $object);
        self::assertInstanceOf('t3lib_object_tests_resolveablecyclic1', $object->o2->o3->o1);
    }

    /**
     * @test
     */
    public function singletonWhichRequiresPrototypeViaSetterInjectionWorksAndAddsDebugMessage(): void
    {
        $object = $this->subject->getInstance('t3lib_object_singletonNeedsPrototype');
        self::assertInstanceOf('t3lib_object_prototype', $object->dependency);
        self::assertEquals('notice', $this->logger->records[0]['level']);
        self::assertEquals('t3lib_object_singletonNeedsPrototype', $this->logger->records[0]['context']['class']);
        self::assertEquals('injectDependency', $this->logger->records[0]['context']['method']);
    }

    /**
     * @test
     */
    public function singletonWhichRequiresSingletonViaSetterInjectionWorks(): void
    {
        $object = $this->subject->getInstance('t3lib_object_singletonNeedsSingleton');
        self::assertInstanceOf('t3lib_object_singleton', $object->dependency);
        self::assertEmpty($this->logger->records);
    }

    /**
     * @test
     */
    public function prototypeWhichRequiresPrototypeViaSetterInjectionWorks(): void
    {
        $object = $this->subject->getInstance('t3lib_object_prototypeNeedsPrototype');
        self::assertInstanceOf('t3lib_object_prototype', $object->dependency);
        self::assertEmpty($this->logger->records);
    }

    /**
     * @test
     */
    public function prototypeWhichRequiresSingletonViaSetterInjectionWorks(): void
    {
        $object = $this->subject->getInstance('t3lib_object_prototypeNeedsSingleton');
        self::assertInstanceOf('t3lib_object_singleton', $object->dependency);
        self::assertEmpty($this->logger->records);
    }

    /**
     * @test
     */
    public function singletonWhichRequiresPrototypeViaConstructorInjectionWorksAndAddsDebugMessage(): void
    {
        $object = $this->subject->getInstance('t3lib_object_singletonNeedsPrototypeInConstructor');
        self::assertInstanceOf('t3lib_object_prototype', $object->dependency);
        self::assertEquals('notice', $this->logger->records[0]['level']);
        self::assertEquals('t3lib_object_singletonNeedsPrototypeInConstructor', $this->logger->records[0]['context']['class_name']);
    }

    /**
     * @test
     */
    public function singletonWhichRequiresSingletonViaConstructorInjectionWorks(): void
    {
        $object = $this->subject->getInstance('t3lib_object_singletonNeedsSingletonInConstructor');
        self::assertInstanceOf('t3lib_object_singleton', $object->dependency);
        self::assertEmpty($this->logger->records);
    }

    /**
     * @test
     */
    public function prototypeWhichRequiresPrototypeViaConstructorInjectionWorks(): void
    {
        $object = $this->subject->getInstance('t3lib_object_prototypeNeedsPrototypeInConstructor');
        self::assertInstanceOf('t3lib_object_prototype', $object->dependency);
        self::assertEmpty($this->logger->records);
    }

    /**
     * @test
     */
    public function prototypeWhichRequiresSingletonViaConstructorInjectionWorks(): void
    {
        $object = $this->subject->getInstance('t3lib_object_prototypeNeedsSingletonInConstructor');
        self::assertInstanceOf('t3lib_object_singleton', $object->dependency);
        self::assertEmpty($this->logger->records);
    }

    /************************************************
     * Test regarding constructor argument injection
     ************************************************/

    /**
     * test class SimpleTypeConstructorArgument
     * @test
     */
    public function getInstanceGivesSimpleConstructorArgumentToClassInstance(): void
    {
        $object = $this->subject->getInstance(
            SimpleTypeConstructorArgument::class,
            [true]
        );
        self::assertTrue($object->foo);
    }

    /**
     * test class SimpleTypeConstructorArgument
     * @test
     */
    public function getInstanceDoesNotInfluenceSimpleTypeConstructorArgumentIfNotGiven(): void
    {
        $object = $this->subject->getInstance(
            SimpleTypeConstructorArgument::class
        );
        self::assertFalse($object->foo);
    }

    /**
     * test class MandatoryConstructorArgument
     * @test
     */
    public function getInstanceGivesExistingConstructorArgumentToClassInstance(): void
    {
        $argumentTestClass = new ArgumentTestClass();
        $object = $this->subject->getInstance(
            MandatoryConstructorArgument::class,
            [$argumentTestClass]
        );
        self::assertInstanceOf(
            MandatoryConstructorArgument::class,
            $object
        );
        self::assertSame($argumentTestClass, $object->argumentTestClass);
    }

    /**
     * test class MandatoryConstructorArgument
     * @test
     */
    public function getInstanceInjectsNewInstanceOfClassToClassIfArgumentIsMandatory(): void
    {
        $object = $this->subject->getInstance(
            MandatoryConstructorArgument::class
        );
        self::assertInstanceOf(
            MandatoryConstructorArgument::class,
            $object
        );
        self::assertInstanceOf(
            ArgumentTestClass::class,
            $object->argumentTestClass
        );
    }

    /**
     * test class OptionalConstructorArgument
     * @test
     */
    public function getInstanceDoesNotInjectAnOptionalArgumentIfNotGiven(): void
    {
        $object = $this->subject->getInstance(
            OptionalConstructorArgument::class
        );
        self::assertInstanceOf(
            OptionalConstructorArgument::class,
            $object
        );
        self::assertNull($object->argumentTestClass);
    }

    /**
     * test class OptionalConstructorArgument
     * @test
     */
    public function getInstanceDoesNotInjectAnOptionalArgumentIfGivenArgumentIsNull(): void
    {
        $object = $this->subject->getInstance(
            OptionalConstructorArgument::class,
            [null]
        );
        self::assertInstanceOf(
            OptionalConstructorArgument::class,
            $object
        );
        self::assertNull($object->argumentTestClass);
    }

    /**
     * test class OptionalConstructorArgument
     * @test
     */
    public function getInstanceGivesExistingConstructorArgumentToClassInstanceIfArgumentIsGiven(): void
    {
        $argumentTestClass = new ArgumentTestClass();
        $object = $this->subject->getInstance(
            OptionalConstructorArgument::class,
            [$argumentTestClass]
        );
        self::assertInstanceOf(
            OptionalConstructorArgument::class,
            $object
        );
        self::assertSame($argumentTestClass, $object->argumentTestClass);
    }

    /**
     * test class MandatoryConstructorArgumentTwo
     * @test
     */
    public function getInstanceGivesTwoArgumentsToClassConstructor(): void
    {
        $firstArgument = new ArgumentTestClass();
        $secondArgument = new ArgumentTestClass();
        $object = $this->subject->getInstance(
            MandatoryConstructorArgumentTwo::class,
            [$firstArgument, $secondArgument]
        );
        self::assertInstanceOf(
            MandatoryConstructorArgumentTwo::class,
            $object
        );
        self::assertSame(
            $firstArgument,
            $object->argumentTestClass
        );
        self::assertSame(
            $secondArgument,
            $object->argumentTestClassTwo
        );
    }

    /**
     * test class MandatoryConstructorArgumentTwo
     * @test
     */
    public function getInstanceInjectsTwoMandatoryArguments(): void
    {
        $object = $this->subject->getInstance(
            MandatoryConstructorArgumentTwo::class
        );
        self::assertInstanceOf(
            MandatoryConstructorArgumentTwo::class,
            $object
        );
        self::assertInstanceOf(
            ArgumentTestClass::class,
            $object->argumentTestClass
        );
        self::assertInstanceOf(
            ArgumentTestClass::class,
            $object->argumentTestClassTwo
        );
        self::assertNotSame(
            $object->argumentTestClass,
            $object->argumentTestClassTwo
        );
    }

    /**
     * test class MandatoryConstructorArgumentTwo
     * @test
     */
    public function getInstanceInjectsSecondMandatoryArgumentIfFirstIsGiven(): void
    {
        $firstArgument = new ArgumentTestClass();
        $object = $this->subject->getInstance(
            MandatoryConstructorArgumentTwo::class,
            [$firstArgument]
        );
        self::assertInstanceOf(
            MandatoryConstructorArgumentTwo::class,
            $object
        );
        self::assertSame(
            $firstArgument,
            $object->argumentTestClass
        );
        self::assertInstanceOf(
            ArgumentTestClass::class,
            $object->argumentTestClassTwo
        );
        self::assertNotSame(
            $object->argumentTestClass,
            $object->argumentTestClassTwo
        );
    }

    /**
     * test class MandatoryConstructorArgumentTwo
     * @test
     */
    public function getInstanceInjectsFirstMandatoryArgumentIfSecondIsGiven(): void
    {
        $secondArgument = new ArgumentTestClass();
        $object = $this->subject->getInstance(
            MandatoryConstructorArgumentTwo::class,
            [null, $secondArgument]
        );
        self::assertInstanceOf(
            MandatoryConstructorArgumentTwo::class,
            $object
        );
        self::assertInstanceOf(
            ArgumentTestClass::class,
            $object->argumentTestClass
        );
        self::assertInstanceOf(
            ArgumentTestClass::class,
            $object->argumentTestClassTwo
        );
        self::assertSame(
            $secondArgument,
            $object->argumentTestClassTwo
        );
        self::assertNotSame(
            $object->argumentTestClass,
            $object->argumentTestClassTwo
        );
    }

    /**
     * test class TwoConstructorArgumentsSecondOptional
     * @test
     */
    public function getInstanceGivesTwoArgumentsToClassConstructorIfSecondIsOptional(): void
    {
        $firstArgument = new ArgumentTestClass();
        $secondArgument = new ArgumentTestClass();
        $object = $this->subject->getInstance(
            TwoConstructorArgumentsSecondOptional::class,
            [$firstArgument, $secondArgument]
        );
        self::assertInstanceOf(
            TwoConstructorArgumentsSecondOptional::class,
            $object
        );
        self::assertSame(
            $firstArgument,
            $object->argumentTestClass
        );
        self::assertSame(
            $secondArgument,
            $object->argumentTestClassTwo
        );
    }

    /**
     * test class TwoConstructorArgumentsSecondOptional
     * @test
     */
    public function getInstanceInjectsFirstMandatoryArgumentIfSecondIsOptionalAndNoneAreGiven(): void
    {
        $object = $this->subject->getInstance(
            TwoConstructorArgumentsSecondOptional::class
        );
        self::assertInstanceOf(
            TwoConstructorArgumentsSecondOptional::class,
            $object
        );
        self::assertInstanceOf(
            ArgumentTestClass::class,
            $object->argumentTestClass
        );
        self::assertNull($object->argumentTestClassTwo);
    }

    /**
     * test class TwoConstructorArgumentsSecondOptional
     * @test
     */
    public function getInstanceInjectsFirstMandatoryArgumentIfSecondIsOptionalAndBothAreGivenAsNull(): void
    {
        $object = $this->subject->getInstance(
            TwoConstructorArgumentsSecondOptional::class,
            [null, null]
        );
        self::assertInstanceOf(
            TwoConstructorArgumentsSecondOptional::class,
            $object
        );
        self::assertInstanceOf(
            ArgumentTestClass::class,
            $object->argumentTestClass
        );
        self::assertNull($object->argumentTestClassTwo);
    }

    /**
     * test class TwoConstructorArgumentsSecondOptional
     * @test
     */
    public function getInstanceGivesFirstArgumentToConstructorIfSecondIsOptionalAndFirstIsGiven(): void
    {
        $firstArgument = new ArgumentTestClass();
        $object = $this->subject->getInstance(
            TwoConstructorArgumentsSecondOptional::class,
            [$firstArgument]
        );
        self::assertInstanceOf(
            TwoConstructorArgumentsSecondOptional::class,
            $object
        );
        self::assertSame(
            $firstArgument,
            $object->argumentTestClass
        );
        self::assertNull($object->argumentTestClassTwo);
    }

    /**
     * test class TwoConstructorArgumentsSecondOptional
     * @test
     */
    public function getInstanceGivesFirstArgumentToConstructorIfSecondIsOptionalFirstIsGivenAndSecondIsGivenNull(): void
    {
        $firstArgument = new ArgumentTestClass();
        $object = $this->subject->getInstance(
            TwoConstructorArgumentsSecondOptional::class,
            [$firstArgument, null]
        );
        self::assertInstanceOf(
            TwoConstructorArgumentsSecondOptional::class,
            $object
        );
        self::assertSame(
            $firstArgument,
            $object->argumentTestClass
        );
        self::assertNull($object->argumentTestClassTwo);
    }

    /**
     * test class TwoConstructorArgumentsFirstOptional
     *
     * @test
     */
    public function getInstanceOnFirstOptionalAndSecondMandatoryInjectsSecondArgumentIfFirstIsGivenAsNull(): void
    {
        $object = $this->subject->getInstance(
            TwoConstructorArgumentsFirstOptional::class,
            [null]
        );
        self::assertInstanceOf(
            TwoConstructorArgumentsFirstOptional::class,
            $object
        );
        self::assertInstanceOf(
            ArgumentTestClass::class,
            $object->argumentTestClassTwo
        );
    }

    /**
     * test class TwoConstructorArgumentsFirstOptional
     * @test
     */
    public function getInstanceOnFirstOptionalAndSecondMandatoryGivesTwoGivenArgumentsToConstructor(): void
    {
        $first = new ArgumentTestClass();
        $second = new ArgumentTestClass();
        $object = $this->subject->getInstance(
            TwoConstructorArgumentsFirstOptional::class,
            [$first, $second]
        );
        self::assertInstanceOf(
            TwoConstructorArgumentsFirstOptional::class,
            $object
        );
        self::assertSame(
            $first,
            $object->argumentTestClass
        );
        self::assertSame(
            $second,
            $object->argumentTestClassTwo
        );
    }

    /**
     * test class TwoConstructorArgumentsFirstOptional
     * @test
     */
    public function getInstanceOnFirstOptionalAndSecondMandatoryInjectsSecondArgumentIfFirstIsGiven(): void
    {
        $first = new ArgumentTestClass();
        $object = $this->subject->getInstance(
            TwoConstructorArgumentsFirstOptional::class,
            [$first]
        );
        self::assertInstanceOf(
            TwoConstructorArgumentsFirstOptional::class,
            $object
        );
        self::assertSame(
            $first,
            $object->argumentTestClass
        );
        self::assertInstanceOf(
            ArgumentTestClass::class,
            $object->argumentTestClassTwo
        );
        self::assertNotSame(
            $object->argumentTestClass,
            $object->argumentTestClassTwo
        );
    }

    /**
     * test class TwoConstructorArgumentsFirstOptional
     *
     * @test
     */
    public function getInstanceOnFirstOptionalAndSecondMandatoryGivesSecondArgumentAsIsIfFirstIsGivenAsNullAndSecondIsGiven(): void
    {
        $second = new ArgumentTestClass();
        $object = $this->subject->getInstance(
            TwoConstructorArgumentsFirstOptional::class,
            [null, $second]
        );
        self::assertInstanceOf(
            TwoConstructorArgumentsFirstOptional::class,
            $object
        );
        self::assertSame(
            $second,
            $object->argumentTestClassTwo
        );
    }

    /**
     * test class TwoConstructorArgumentsFirstOptional
     *
     * @test
     */
    public function getInstanceOnFirstOptionalAndSecondMandatoryInjectsSecondArgumentIfFirstIsGivenAsNullAndSecondIsNull(): void
    {
        $object = $this->subject->getInstance(
            TwoConstructorArgumentsFirstOptional::class,
            [null, null]
        );
        self::assertInstanceOf(
            TwoConstructorArgumentsFirstOptional::class,
            $object
        );
        self::assertInstanceOf(
            ArgumentTestClass::class,
            $object->argumentTestClassTwo
        );
    }

    /**
     * test class TwoConstructorArgumentsBothOptional
     * @test
     */
    public function getInstanceOnTwoOptionalGivesTwoGivenArgumentsToConstructor(): void
    {
        $first = new ArgumentTestClass();
        $second = new ArgumentTestClass();
        $object = $this->subject->getInstance(
            TwoConstructorArgumentsBothOptional::class,
            [$first, $second]
        );
        self::assertInstanceOf(
            TwoConstructorArgumentsBothOptional::class,
            $object
        );
        self::assertSame(
            $first,
            $object->argumentTestClass
        );
        self::assertSame(
            $second,
            $object->argumentTestClassTwo
        );
    }

    /**
     * test class TwoConstructorArgumentsBothOptional
     * @test
     */
    public function getInstanceOnTwoOptionalGivesNoArgumentsToConstructorIfArgumentsAreNull(): void
    {
        $object = $this->subject->getInstance(
            TwoConstructorArgumentsBothOptional::class,
            [null, null]
        );
        self::assertInstanceOf(
            TwoConstructorArgumentsBothOptional::class,
            $object
        );
        self::assertNull($object->argumentTestClass);
        self::assertNull($object->argumentTestClassTwo);
    }

    /**
     * test class TwoConstructorArgumentsBothOptional
     * @test
     */
    public function getInstanceOnTwoOptionalGivesNoArgumentsToConstructorIfNoneAreGiven(): void
    {
        $object = $this->subject->getInstance(
            TwoConstructorArgumentsBothOptional::class
        );
        self::assertInstanceOf(
            TwoConstructorArgumentsBothOptional::class,
            $object
        );
        self::assertNull($object->argumentTestClass);
        self::assertNull($object->argumentTestClassTwo);
    }

    /**
     * test class TwoConstructorArgumentsBothOptional
     * @test
     */
    public function getInstanceOnTwoOptionalGivesOneArgumentToConstructorIfFirstIsObjectAndSecondIsNotGiven(): void
    {
        $first = new ArgumentTestClass();
        $object = $this->subject->getInstance(
            TwoConstructorArgumentsBothOptional::class,
            [$first]
        );
        self::assertInstanceOf(
            TwoConstructorArgumentsBothOptional::class,
            $object
        );
        self::assertSame(
            $first,
            $object->argumentTestClass
        );
        self::assertNull($object->argumentTestClassTwo);
    }

    /**
     * test class TwoConstructorArgumentsBothOptional
     * @test
     */
    public function getInstanceOnTwoOptionalGivesOneArgumentToConstructorIfFirstIsObjectAndSecondIsNull(): void
    {
        $first = new ArgumentTestClass();
        $object = $this->subject->getInstance(
            TwoConstructorArgumentsBothOptional::class,
            [$first, null]
        );
        self::assertInstanceOf(
            TwoConstructorArgumentsBothOptional::class,
            $object
        );
        self::assertSame(
            $first,
            $object->argumentTestClass
        );
        self::assertNull($object->argumentTestClassTwo);
    }

    /**
     * test class TwoConstructorArgumentsBothOptional
     * @test
     */
    public function getInstanceOnTwoOptionalGivesOneArgumentToConstructorIfFirstIsNullAndSecondIsObject(): void
    {
        $second = new ArgumentTestClass();
        $object = $this->subject->getInstance(
            TwoConstructorArgumentsBothOptional::class,
            [null, $second]
        );
        self::assertInstanceOf(
            TwoConstructorArgumentsBothOptional::class,
            $object
        );
        self::assertNull($object->argumentTestClass);
        self::assertSame(
            $second,
            $object->argumentTestClassTwo
        );
    }
}
