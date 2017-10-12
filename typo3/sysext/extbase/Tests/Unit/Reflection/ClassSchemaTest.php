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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Reflection\ClassSchema;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyModel;

/**
 * Test case
 */
class ClassSchemaTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @test
     */
    public function classSchemaForModelIsSetAggregateRootIfRepositoryClassIsFoundForNamespacedClasses()
    {
        /** @var \TYPO3\CMS\Extbase\Reflection\ReflectionService $service */
        $service = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Reflection\ReflectionService::class);
        $classSchema = $service->getClassSchema(DummyModel::class);
        $this->assertTrue($classSchema->isAggregateRoot());
    }

    public function testClassSchemaHasConstructor()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithConstructorAndConstructorArguments::class);
        static::assertTrue($classSchema->hasConstructor());
    }

    public function testClassSchemaDetectsConstructorArguments()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithConstructorAndConstructorArguments::class);
        static::assertTrue($classSchema->hasConstructor());

        $methodDefinition = $classSchema->getMethod('__construct');
        static::assertArrayHasKey('foo', $methodDefinition['params']);
        static::assertArrayHasKey('bar', $methodDefinition['params']);
    }

    public function testClassSchemaDetectsConstructorArgumentsWithDependencies()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithConstructorAndConstructorArgumentsWithDependencies::class);
        static::assertTrue($classSchema->hasConstructor());

        $methodDefinition = $classSchema->getMethod('__construct');
        static::assertArrayHasKey('foo', $methodDefinition['params']);
        static::assertSame(Fixture\DummyClassWithGettersAndSetters::class, $methodDefinition['params']['foo']['dependency']);
    }

    public function testClassSchemaDetectsMethodVisibility()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithAllTypesOfMethods::class);

        $methodDefinition = $classSchema->getMethod('publicMethod');
        static::assertTrue($methodDefinition['public']);
        static::assertFalse($methodDefinition['protected']);
        static::assertFalse($methodDefinition['private']);

        $methodDefinition = $classSchema->getMethod('protectedMethod');
        static::assertFalse($methodDefinition['public']);
        static::assertTrue($methodDefinition['protected']);
        static::assertFalse($methodDefinition['private']);

        $methodDefinition = $classSchema->getMethod('privateMethod');
        static::assertFalse($methodDefinition['public']);
        static::assertFalse($methodDefinition['protected']);
        static::assertTrue($methodDefinition['private']);
    }

    public function testClassSchemaDetectsInjectMethods()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithAllTypesOfMethods::class);

        $methodDefinition = $classSchema->getMethod('injectSettings');
        static::assertFalse($methodDefinition['injectMethod']);

        $methodDefinition = $classSchema->getMethod('injectMethodWithoutParam');
        static::assertFalse($methodDefinition['injectMethod']);

        $methodDefinition = $classSchema->getMethod('injectMethodThatIsProtected');
        static::assertFalse($methodDefinition['injectMethod']);

        $methodDefinition = $classSchema->getMethod('injectFoo');
        static::assertTrue($methodDefinition['injectMethod']);
    }

    public function testClassSchemaDetectsStaticMethods()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithAllTypesOfMethods::class);

        $methodDefinition = $classSchema->getMethod('staticMethod');
        static::assertTrue($methodDefinition['static']);
    }

    public function testClassSchemaDetectsMandatoryParams()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithAllTypesOfMethods::class);

        $methodDefinition = $classSchema->getMethod('methodWithMandatoryParam');
        static::assertFalse($methodDefinition['params']['param']['optional']);
    }

    public function testClassSchemaDetectsNullableParams()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithAllTypesOfMethods::class);

        $methodDefinition = $classSchema->getMethod('methodWithNullableParam');
        static::assertTrue($methodDefinition['params']['param']['nullable']);
    }

    public function testClassSchemaDetectsDefaultValueParams()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithAllTypesOfMethods::class);

        $methodDefinition = $classSchema->getMethod('methodWithDefaultValueParam');
        static::assertSame('foo', $methodDefinition['params']['param']['default']);
    }

    public function testClassSchemaDetectsParamTypeFromTypeHint()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithAllTypesOfMethods::class);

        $methodDefinition = $classSchema->getMethod('methodWithTypeHintedParam');
        static::assertSame('string', $methodDefinition['params']['param']['type']);
    }

    public function testClassSchemaDetectsPropertyVisibility()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithAllTypesOfProperties::class);

        $propertyDefinition = $classSchema->getProperty('publicProperty');
        static::assertTrue($propertyDefinition['public']);
        static::assertFalse($propertyDefinition['protected']);
        static::assertFalse($propertyDefinition['private']);

        $propertyDefinition = $classSchema->getProperty('protectedProperty');
        static::assertFalse($propertyDefinition['public']);
        static::assertTrue($propertyDefinition['protected']);
        static::assertFalse($propertyDefinition['private']);

        $propertyDefinition = $classSchema->getProperty('privateProperty');
        static::assertFalse($propertyDefinition['public']);
        static::assertFalse($propertyDefinition['protected']);
        static::assertTrue($propertyDefinition['private']);
    }

    public function testClassSchemaDetectsInjectProperty()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithAllTypesOfProperties::class);

        $propertyDefinition = $classSchema->getProperty('propertyWithInjectAnnotation');
        static::assertTrue($propertyDefinition['annotations']['inject']);
    }

    public function testClassSchemaDetectsTransientProperty()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithAllTypesOfProperties::class);

        $propertyDefinition = $classSchema->getProperty('propertyWithTransientAnnotation');
        static::assertTrue($propertyDefinition['annotations']['transient']);
    }

    public function testClassSchemaDetectsCascadeProperty()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithAllTypesOfProperties::class);

        $propertyDefinition = $classSchema->getProperty('propertyWithCascadeAnnotation');
        static::assertSame('remove', $propertyDefinition['annotations']['cascade']);
    }

    public function testClassSchemaDetectsCascadePropertyOnlyWithVarAnnotation()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithAllTypesOfProperties::class);

        $propertyDefinition = $classSchema->getProperty('propertyWithCascadeAnnotationWithoutVarAnnotation');
        static::assertNull($propertyDefinition['annotations']['cascade']);
    }

    public function testClassSchemaDetectsTypeAndElementType()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithAllTypesOfProperties::class);

        $propertyDefinition = $classSchema->getProperty('propertyWithObjectStorageAnnotation');
        static::assertSame(ObjectStorage::class, $propertyDefinition['type']);
        static::assertSame(Fixture\DummyClassWithAllTypesOfProperties::class, $propertyDefinition['elementType']);
    }
}
