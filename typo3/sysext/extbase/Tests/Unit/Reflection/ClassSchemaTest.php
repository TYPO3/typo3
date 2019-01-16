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
use TYPO3\CMS\Extbase\Reflection\ClassSchema;
use TYPO3\CMS\Extbase\Validation\Exception\InvalidTypeHintException;
use TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationConfigurationException;
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
                'publicPropertyWithDefaultValue',
                'propertyWithIgnoredTags',
                'propertyWithInjectAnnotation',
                'propertyWithTransientAnnotation',
                'propertyWithCascadeAnnotation',
                'propertyWithCascadeAnnotationWithoutVarAnnotation',
                'propertyWithObjectStorageAnnotation',
                'propertyWithObjectStorageAnnotationWithoutFQCN',
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
                'methodWithTypeHintedParam',
                'methodWithDocBlockTypeHintOnly',
            ],
            array_keys((new ClassSchema(Fixture\DummyClassWithAllTypesOfMethods::class))->getMethods())
        );
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
        static::assertSame(Fixture\DummyClassWithInjectDoctrineAnnotation::class, $injectProperties['propertyWithRelativeClassName']);

        static::assertArrayHasKey('propertyWithImportedClassName', $injectProperties);
        static::assertSame(self::class, $injectProperties['propertyWithImportedClassName']);

        static::assertArrayHasKey('propertyWithImportedAndAliasedClassName', $injectProperties);
        static::assertSame(self::class, $injectProperties['propertyWithImportedAndAliasedClassName']);
    }

    /**
     * @test
     */
    public function classSchemaDetectsPropertyDefaultValue()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithAllTypesOfProperties::class);

        $propertyDefinition = $classSchema->getProperty('publicPropertyWithDefaultValue');
        static::assertSame('foo', $propertyDefinition->getDefaultValue());
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

    /**
     * @test
     */
    public function classSchemaDetectsMethodParameterTypeViaReflection(): void
    {
        $class = new class {
            public function foo(string $foo)
            {
            }

            public function bar(ClassSchema $foo)
            {
            }
        };

        $classSchema = new ClassSchema(get_class($class));
        static::assertSame('string', $classSchema->getMethod('foo')->getParameter('foo')->getType());
        static::assertSame(ClassSchema::class, $classSchema->getMethod('bar')->getParameter('foo')->getType());
    }

    /**
     * @test
     */
    public function classSchemaPrefersMethodParameterTypeDetectionViaReflection(): void
    {
        $class = new class {
            /**
             * @param ClassSchema $foo
             */
            public function foo(string $foo)
            {
            }
        };

        $classSchema = new ClassSchema(get_class($class));
        static::assertSame('string', $classSchema->getMethod('foo')->getParameter('foo')->getType());
    }

    /**
     * @test
     */
    public function classSchemaDetectsMethodParameterTypeDetectionViaDocBlocksIfNoTypeHintIsGiven(): void
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithAllTypesOfMethods::class);
        static::assertSame(Fixture\DummyClassWithAllTypesOfMethods::class, $classSchema->getMethod('methodWithDocBlockTypeHintOnly')->getParameter('param')->getType());
    }
}
