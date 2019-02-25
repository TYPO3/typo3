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
use TYPO3\CMS\Extbase\Validation\Exception\InvalidTypeHintException;
use TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationConfigurationException;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ClassSchemaTest extends UnitTestCase
{
    /**
     * @test
     */
    public function classSchemaForModelIsSetAggregateRootIfRepositoryClassIsFoundForNamespacedClasses()
    {
        $this->resetSingletonInstances = true;
        $service = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Reflection\ReflectionService::class);
        $classSchema = $service->getClassSchema(Fixture\DummyModel::class);
        $this->assertTrue($classSchema->isAggregateRoot());
    }

    /**
     * @test
     */
    public function classSchemaHasConstructor()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithConstructorAndConstructorArguments::class);
        static::assertTrue($classSchema->hasConstructor());
    }

    /**
     * @test
     */
    public function classSchemaDetectsConstructorArguments()
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

    /**
     * @test
     */
    public function classSchemaDetectsConstructorArgumentsWithDependencies()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithConstructorAndConstructorArgumentsWithDependencies::class);
        static::assertTrue($classSchema->hasConstructor());

        $methodDefinition = $classSchema->getMethod('__construct');
        static::assertArrayHasKey('foo', $methodDefinition['params']);
        static::assertSame(Fixture\DummyClassWithGettersAndSetters::class, $methodDefinition['params']['foo']['dependency']);
    }

    /**
     * @test
     */
    public function classSchemaGetProperties()
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

    /**
     * @test
     */
    public function classSchemaHasMethod()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithAllTypesOfMethods::class);
        static::assertTrue($classSchema->hasMethod('publicMethod'));
        static::assertFalse($classSchema->hasMethod('nonExistentMethod'));
    }

    /**
     * @test
     */
    public function classSchemaGetMethods()
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

    /**
     * @test
     */
    public function classSchemaDetectsMethodVisibility()
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

    /**
     * @test
     */
    public function classSchemaDetectsInjectProperties()
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

    /**
     * @test
     */
    public function classSchemaDetectsInjectMethods()
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

    /**
     * @test
     */
    public function classSchemaDetectsPropertiesWithLazyAnnotation()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithLazyDoctrineAnnotation::class);
        static::assertTrue($classSchema->getProperty('propertyWithLazyAnnotation')['annotations']['lazy']);
    }

    /**
     * @test
     */
    public function classSchemaDetectsStaticMethods()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithAllTypesOfMethods::class);

        $methodDefinition = $classSchema->getMethod('staticMethod');
        static::assertTrue($methodDefinition['static']);
    }

    /**
     * @test
     */
    public function classSchemaDetectsMandatoryParams()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithAllTypesOfMethods::class);

        $methodDefinition = $classSchema->getMethod('methodWithMandatoryParam');
        static::assertFalse($methodDefinition['params']['param']['optional']);
    }

    /**
     * @test
     */
    public function classSchemaDetectsNullableParams()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithAllTypesOfMethods::class);

        $methodDefinition = $classSchema->getMethod('methodWithNullableParam');
        static::assertTrue($methodDefinition['params']['param']['nullable']);
    }

    /**
     * @test
     */
    public function classSchemaDetectsDefaultValueParams()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithAllTypesOfMethods::class);

        $methodDefinition = $classSchema->getMethod('methodWithDefaultValueParam');
        static::assertSame('foo', $methodDefinition['params']['param']['default']);
    }

    /**
     * @test
     */
    public function classSchemaDetectsParamTypeFromTypeHint()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithAllTypesOfMethods::class);

        $methodDefinition = $classSchema->getMethod('methodWithTypeHintedParam');
        static::assertSame('string', $methodDefinition['params']['param']['type']);
    }

    /**
     * @test
     */
    public function classSchemaDetectsPropertyVisibility()
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

    /**
     * @test
     */
    public function classSchemaDetectsInjectProperty()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithAllTypesOfProperties::class);

        $propertyDefinition = $classSchema->getProperty('propertyWithInjectAnnotation');
        static::assertTrue($propertyDefinition['annotations']['inject']);
    }

    /**
     * @test
     */
    public function classSchemaDetectsTransientProperty()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithAllTypesOfProperties::class);

        $propertyDefinition = $classSchema->getProperty('propertyWithTransientAnnotation');
        static::assertTrue($propertyDefinition['annotations']['transient']);
    }

    /**
     * @test
     */
    public function classSchemaDetectsCascadeProperty()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithAllTypesOfProperties::class);

        $propertyDefinition = $classSchema->getProperty('propertyWithCascadeAnnotation');
        static::assertSame('remove', $propertyDefinition['annotations']['cascade']);
    }

    /**
     * @test
     */
    public function classSchemaDetectsCascadePropertyOnlyWithVarAnnotation()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithAllTypesOfProperties::class);

        $propertyDefinition = $classSchema->getProperty('propertyWithCascadeAnnotationWithoutVarAnnotation');
        static::assertNull($propertyDefinition['annotations']['cascade']);
    }

    /**
     * @test
     */
    public function classSchemaDetectsIgnoreValidationAnnotation()
    {
        $classSchema = new ClassSchema(Fixture\DummyControllerWithIgnoreValidationDoctrineAnnotation::class);
        static::assertTrue(isset($classSchema->getMethod('someAction')['tags']['ignorevalidation']));
        static::assertTrue(in_array('foo', $classSchema->getMethod('someAction')['tags']['ignorevalidation'], true));
        static::assertTrue(in_array('bar', $classSchema->getMethod('someAction')['tags']['ignorevalidation'], true));
        static::assertFalse(in_array('baz', $classSchema->getMethod('someAction')['tags']['ignorevalidation'], true));
    }

    /**
     * @test
     */
    public function classSchemaDetectsTypeAndElementType()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithAllTypesOfProperties::class);

        $propertyDefinition = $classSchema->getProperty('propertyWithObjectStorageAnnotation');
        static::assertSame(ObjectStorage::class, $propertyDefinition['type']);
        static::assertSame(Fixture\DummyClassWithAllTypesOfProperties::class, $propertyDefinition['elementType']);
    }

    /**
     * @test
     */
    public function classSchemaDetectsSingletons()
    {
        static::assertTrue((new ClassSchema(Fixture\DummySingleton::class))->isSingleton());
    }

    /**
     * @test
     */
    public function classSchemaDetectsModels()
    {
        static::assertTrue((new ClassSchema(Fixture\DummyEntity::class))->isModel());
        static::assertTrue((new ClassSchema(Fixture\DummyValueObject::class))->isModel());
    }

    /**
     * @test
     */
    public function classSchemaDetectsEntities()
    {
        static::assertTrue((new ClassSchema(Fixture\DummyEntity::class))->isEntity());
    }

    /**
     * @test
     */
    public function classSchemaDetectsValueObjects()
    {
        static::assertTrue((new ClassSchema(Fixture\DummyValueObject::class))->isValueObject());
    }

    /**
     * @test
     */
    public function classSchemaDetectsClassName()
    {
        $this->resetSingletonInstances = true;
        static::assertSame(Fixture\DummyModel::class, (new ClassSchema(Fixture\DummyModel::class))->getClassName());
    }

    /**
     * @test
     */
    public function classSchemaDetectsNonStaticProperties()
    {
        static::assertTrue((new ClassSchema(Fixture\DummyClassWithAllTypesOfProperties::class))->hasProperty('publicProperty'));
        static::assertTrue((new ClassSchema(Fixture\DummyClassWithAllTypesOfProperties::class))->hasProperty('protectedProperty'));
        static::assertTrue((new ClassSchema(Fixture\DummyClassWithAllTypesOfProperties::class))->hasProperty('privateProperty'));
    }

    /**
     * @test
     */
    public function classSchemaDetectsStaticProperties()
    {
        static::assertTrue((new ClassSchema(Fixture\DummyClassWithAllTypesOfProperties::class))->hasProperty('publicStaticProperty'));
        static::assertTrue((new ClassSchema(Fixture\DummyClassWithAllTypesOfProperties::class))->hasProperty('protectedStaticProperty'));
        static::assertTrue((new ClassSchema(Fixture\DummyClassWithAllTypesOfProperties::class))->hasProperty('privateStaticProperty'));
    }

    /**
     * @test
     */
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
    public function classSchemaDetectsValidateAnnotationsModelProperties(): void
    {
        $this->resetSingletonInstances = true;
        $classSchema = new ClassSchema(Fixture\DummyModel::class);
        static::assertSame(
            [
                [
                    'name' => 'StringLength',
                    'options' => [
                        'minimum' => 1,
                        'maximum' => 10,
                    ],
                    'className' => StringLengthValidator::class
                ],
                [
                    'name' => 'NotEmpty',
                    'options' => [],
                    'className' => NotEmptyValidator::class
                ],
                [
                    'name' => 'TYPO3.CMS.Extbase:NotEmpty',
                    'options' => [],
                    'className' => NotEmptyValidator::class
                ],
                [
                    'name' => 'TYPO3.CMS.Extbase.Tests.Unit.Reflection.Fixture:DummyValidator',
                    'options' => [],
                    'className' => Fixture\Validation\Validator\DummyValidator::class
                ],
                [
                    'name' => '\TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator',
                    'options' => [],
                    'className' => NotEmptyValidator::class
                ],
                [
                    'name' => NotEmptyValidator::class,
                    'options' => [],
                    'className' => NotEmptyValidator::class
                ]
            ],
            $classSchema->getProperty('propertyWithValidateAnnotations')['validators']
        );
    }

    /**
     * @test
     */
    public function classSchemaDetectsValidateAnnotationsOfControllerActions(): void
    {
        $this->resetSingletonInstances = true;
        $classSchema = new ClassSchema(Fixture\DummyController::class);
        static::assertSame(
            [
                [
                    'name' => 'StringLength',
                    'options' => [
                        'minimum' => 1,
                        'maximum' => 10,
                    ],
                    'className' => StringLengthValidator::class
                ],
                [
                    'name' => 'NotEmpty',
                    'options' => [],
                    'className' => NotEmptyValidator::class
                ],
                [
                    'name' => 'TYPO3.CMS.Extbase:NotEmpty',
                    'options' => [],
                    'className' => NotEmptyValidator::class
                ],
                [
                    'name' => 'TYPO3.CMS.Extbase.Tests.Unit.Reflection.Fixture:DummyValidator',
                    'options' => [],
                    'className' => Fixture\Validation\Validator\DummyValidator::class
                ],
                [
                    'name' => '\TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator',
                    'options' => [],
                    'className' => NotEmptyValidator::class
                ],
                [
                    'name' => NotEmptyValidator::class,
                    'options' => [],
                    'className' => NotEmptyValidator::class
                ]
            ],
            $classSchema->getMethod('methodWithValidateAnnotationsAction')['params']['fooParam']['validators']
        );
    }

    /**
     * @test
     */
    public function classSchemaGenerationThrowsExceptionWithValidateDoctrineAnnotationsForParamWithoutTypeHint()
    {
        $this->resetSingletonInstances = true;
        $this->expectException(InvalidTypeHintException::class);
        $this->expectExceptionMessage('Missing type information for parameter "$fooParam" in TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyControllerWithValidateAnnotationWithoutParamTypeHint->methodWithValidateAnnotationsAction(): Either use an @param annotation or use a type hint.');
        $this->expectExceptionCode(1515075192);

        new ClassSchema(Fixture\DummyControllerWithValidateAnnotationWithoutParamTypeHint::class);
    }

    /**
     * @test
     */
    public function classSchemaGenerationThrowsExceptionWithValidateDoctrineAnnotationsForMissingParam()
    {
        $this->resetSingletonInstances = true;
        $this->expectException(InvalidValidationConfigurationException::class);
        $this->expectExceptionMessage('Invalid validate annotation in TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyControllerWithValidateAnnotationWithoutParam->methodWithValidateAnnotationsAction(): The following validators have been defined for missing param "$fooParam": NotEmpty, StringLength');
        $this->expectExceptionCode(1515073585);

        new ClassSchema(Fixture\DummyControllerWithValidateAnnotationWithoutParam::class);
    }
}
