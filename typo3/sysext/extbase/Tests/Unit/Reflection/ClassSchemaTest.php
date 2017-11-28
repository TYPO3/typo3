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
        $classSchema = $service->getClassSchema(Fixture\DummyModel::class);
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

        $constructorArguments = $classSchema->getConstructorArguments();
        static::assertArrayHasKey('foo', $constructorArguments);
        static::assertArrayHasKey('bar', $constructorArguments);

        $classSchema = new ClassSchema(Fixture\DummyClassWithConstructorAndWithoutConstructorArguments::class);
        static::assertTrue($classSchema->hasConstructor());
        static::assertSame([], $classSchema->getConstructorArguments());

        $classSchema = new ClassSchema(Fixture\DummyClassWithoutConstructor::class);
        static::assertFalse($classSchema->hasConstructor());
        static::assertSame([], $classSchema->getConstructorArguments());
    }

    public function testClassSchemaDetectsConstructorArgumentsWithDependencies()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithConstructorAndConstructorArgumentsWithDependencies::class);
        static::assertTrue($classSchema->hasConstructor());

        $methodDefinition = $classSchema->getMethod('__construct');
        static::assertArrayHasKey('foo', $methodDefinition['params']);
        static::assertSame(Fixture\DummyClassWithGettersAndSetters::class, $methodDefinition['params']['foo']['dependency']);
    }

    public function testClassSchemaGetProperties()
    {
        static::assertSame(
            [
                'publicProperty',
                'protectedProperty',
                'privateProperty',
                'publicStaticProperty',
                'protectedStaticProperty',
                'privateStaticProperty',
                'propertyWithIgnoredTags',
                'propertyWithInjectAnnotation',
                'propertyWithTransientAnnotation',
                'propertyWithCascadeAnnotation',
                'propertyWithCascadeAnnotationWithoutVarAnnotation',
                'propertyWithObjectStorageAnnotation'
            ],
            array_keys((new ClassSchema(Fixture\DummyClassWithAllTypesOfProperties::class))->getProperties())
        );
    }

    public function testClassSchemaHasMethod()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithAllTypesOfMethods::class);
        static::assertTrue($classSchema->hasMethod('publicMethod'));
        static::assertFalse($classSchema->hasMethod('nonExistentMethod'));
    }

    public function testClassSchemaGetMethods()
    {
        static::assertSame(
            [
                'publicMethod',
                'protectedMethod',
                'privateMethod',
                'methodWithIgnoredTags',
                'injectSettings',
                'injectMethodWithoutParam',
                'injectMethodThatIsProtected',
                'injectFoo',
                'staticMethod',
                'methodWithMandatoryParam',
                'methodWithNullableParam',
                'methodWithDefaultValueParam',
                'methodWithTypeHintedParam'
            ],
            array_keys((new ClassSchema(Fixture\DummyClassWithAllTypesOfMethods::class))->getMethods())
        );
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

    public function testClassSchemaDetectsInjectProperties()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithInjectDoctrineAnnotation::class);
        static::assertTrue($classSchema->hasInjectProperties());

        $injectProperties = $classSchema->getInjectProperties();
        static::assertArrayHasKey('propertyWithFullQualifiedClassName', $injectProperties);
        static::assertSame(Fixture\DummyClassWithInjectDoctrineAnnotation::class, $injectProperties['propertyWithFullQualifiedClassName']);

        static::assertArrayHasKey('propertyWithRelativeClassName', $injectProperties);
        static::assertSame('DummyClassWithInjectDoctrineAnnotation', $injectProperties['propertyWithRelativeClassName']);

        static::assertArrayHasKey('propertyWithImportedClassName', $injectProperties);
        static::assertSame('ClassSchemaTest', $injectProperties['propertyWithImportedClassName']);

        static::assertArrayHasKey('propertyWithImportedAndAliasedClassName', $injectProperties);
        static::assertSame('AliasedClassSchemaTest', $injectProperties['propertyWithImportedAndAliasedClassName']);
    }

    public function testClassSchemaDetectsInjectMethods()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithAllTypesOfMethods::class);
        static::assertTrue($classSchema->hasInjectMethods());

        $methodDefinition = $classSchema->getMethod('injectSettings');
        static::assertFalse($methodDefinition['injectMethod']);

        $methodDefinition = $classSchema->getMethod('injectMethodWithoutParam');
        static::assertFalse($methodDefinition['injectMethod']);

        $methodDefinition = $classSchema->getMethod('injectMethodThatIsProtected');
        static::assertFalse($methodDefinition['injectMethod']);

        $methodDefinition = $classSchema->getMethod('injectFoo');
        static::assertTrue($methodDefinition['injectMethod']);

        $injectMethods = $classSchema->getInjectMethods();
        static::assertArrayHasKey('injectFoo', $injectMethods);
    }

    public function testClassSchemaDetectsPropertiesWithLazyAnnotation()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithLazyDoctrineAnnotation::class);
        static::assertTrue($classSchema->getProperty('propertyWithLazyAnnotation')['annotations']['lazy']);
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

    public function testClassSchemaDetectsIgnoreValidationAnnotation()
    {
        $classSchema = new ClassSchema(Fixture\DummyControllerWithIgnoreValidationDoctrineAnnotation::class);
        static::assertTrue(isset($classSchema->getMethod('someAction')['tags']['ignorevalidation']));
        static::assertTrue(in_array('foo', $classSchema->getMethod('someAction')['tags']['ignorevalidation'], true));
        static::assertTrue(in_array('bar', $classSchema->getMethod('someAction')['tags']['ignorevalidation'], true));
        static::assertFalse(in_array('baz', $classSchema->getMethod('someAction')['tags']['ignorevalidation'], true));
    }

    public function testClassSchemaDetectsTypeAndElementType()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithAllTypesOfProperties::class);

        $propertyDefinition = $classSchema->getProperty('propertyWithObjectStorageAnnotation');
        static::assertSame(ObjectStorage::class, $propertyDefinition['type']);
        static::assertSame(Fixture\DummyClassWithAllTypesOfProperties::class, $propertyDefinition['elementType']);
    }

    public function testClassSchemaDetectsSingletons()
    {
        static::assertTrue((new ClassSchema(Fixture\DummySingleton::class))->isSingleton());
    }

    public function testClassSchemaDetectsModels()
    {
        static::assertTrue((new ClassSchema(Fixture\DummyEntity::class))->isModel());
        static::assertTrue((new ClassSchema(Fixture\DummyValueObject::class))->isModel());
    }

    public function testClassSchemaDetectsEntities()
    {
        static::assertTrue((new ClassSchema(Fixture\DummyEntity::class))->isEntity());
    }

    public function testClassSchemaDetectsValueObjects()
    {
        static::assertTrue((new ClassSchema(Fixture\DummyValueObject::class))->isValueObject());
    }

    public function testClassSchemaDetectsClassName()
    {
        static::assertSame(Fixture\DummyModel::class, (new ClassSchema(Fixture\DummyModel::class))->getClassName());
    }

    public function testClassSchemaDetectsNonStaticProperties()
    {
        static::assertTrue((new ClassSchema(Fixture\DummyClassWithAllTypesOfProperties::class))->hasProperty('publicProperty'));
        static::assertTrue((new ClassSchema(Fixture\DummyClassWithAllTypesOfProperties::class))->hasProperty('protectedProperty'));
        static::assertTrue((new ClassSchema(Fixture\DummyClassWithAllTypesOfProperties::class))->hasProperty('privateProperty'));
    }

    public function testClassSchemaDetectsStaticProperties()
    {
        static::assertTrue((new ClassSchema(Fixture\DummyClassWithAllTypesOfProperties::class))->hasProperty('publicStaticProperty'));
        static::assertTrue((new ClassSchema(Fixture\DummyClassWithAllTypesOfProperties::class))->hasProperty('protectedStaticProperty'));
        static::assertTrue((new ClassSchema(Fixture\DummyClassWithAllTypesOfProperties::class))->hasProperty('privateStaticProperty'));
    }

    public function testClassSchemaGetTags()
    {
        $tags = (new ClassSchema(Fixture\DummyClassWithTags::class))->getTags();
        static::assertArrayHasKey('see', $tags);

        // test ignored tags
        static::assertArrayNotHasKey('package', $tags);
        static::assertArrayNotHasKey('subpackage', $tags);
        static::assertArrayNotHasKey('license', $tags);
        static::assertArrayNotHasKey('copyright', $tags);
        static::assertArrayNotHasKey('author', $tags);
        static::assertArrayNotHasKey('version', $tags);
    }

    /**
     * @test
     */
    public function classSchemaDetectsValidateAnnotation()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithValidateAnnotation::class);

        static::assertSame(
            [],
            $classSchema->getProperty('propertyWithoutValidateAnnotations')['annotations']['validators']
        );
        static::assertSame(
            [
                'NotEmpty',
                'Empty (Foo=Bar)'
            ],
            $classSchema->getProperty('propertyWithValidateAnnotations')['annotations']['validators']
        );

        static::assertSame(
            [],
            $classSchema->getMethod('methodWithoutValidateAnnotations')['annotations']['validators']
        );

        static::assertSame(
            [
                '$fooParam FooValidator (FooValidatorOptionKey=FooValidatorOptionValue)',
                '$fooParam BarValidator'
            ],
            $classSchema->getMethod('methodWithValidateAnnotations')['annotations']['validators']
        );
    }
}
