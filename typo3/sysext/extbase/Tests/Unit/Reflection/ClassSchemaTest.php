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
        self::assertTrue($classSchema->isAggregateRoot());
    }

    /**
     * @test
     */
    public function classSchemaHasConstructor()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithConstructorAndConstructorArguments::class);
        self::assertTrue($classSchema->hasConstructor());
    }

    /**
     * @test
     */
    public function classSchemaGetProperties()
    {
        self::assertSame(
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
                'uid',
                '_localizedUid',
                '_languageUid',
                '_versionedUid',
                'pid',
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
        self::assertTrue($classSchema->hasMethod('publicMethod'));
        self::assertFalse($classSchema->hasMethod('nonExistentMethod'));
    }

    /**
     * @test
     */
    public function classSchemaGetMethods()
    {
        self::assertSame(
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
        self::assertTrue($classSchema->hasInjectProperties());

        $injectProperties = $classSchema->getInjectProperties();
        self::assertArrayHasKey('propertyWithFullQualifiedClassName', $injectProperties);
        self::assertSame(Fixture\DummyClassWithInjectDoctrineAnnotation::class, $injectProperties['propertyWithFullQualifiedClassName']->getType());

        self::assertArrayHasKey('propertyWithRelativeClassName', $injectProperties);
        self::assertSame(Fixture\DummyClassWithInjectDoctrineAnnotation::class, $injectProperties['propertyWithRelativeClassName']->getType());

        self::assertArrayHasKey('propertyWithImportedClassName', $injectProperties);
        self::assertSame(self::class, $injectProperties['propertyWithImportedClassName']->getType());

        self::assertArrayHasKey('propertyWithImportedAndAliasedClassName', $injectProperties);
        self::assertSame(self::class, $injectProperties['propertyWithImportedAndAliasedClassName']->getType());
    }

    /**
     * @test
     */
    public function classSchemaDetectsPropertyDefaultValue()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithAllTypesOfProperties::class);

        $propertyDefinition = $classSchema->getProperty('publicPropertyWithDefaultValue');
        self::assertSame('foo', $propertyDefinition->getDefaultValue());
    }

    /**
     * @test
     */
    public function classSchemaDetectsSingletons()
    {
        self::assertTrue((new ClassSchema(Fixture\DummySingleton::class))->isSingleton());
    }

    /**
     * @test
     */
    public function classSchemaDetectsModels()
    {
        self::assertTrue((new ClassSchema(Fixture\DummyEntity::class))->isModel());
        self::assertTrue((new ClassSchema(Fixture\DummyValueObject::class))->isModel());
    }

    /**
     * @test
     */
    public function classSchemaDetectsEntities()
    {
        self::assertTrue((new ClassSchema(Fixture\DummyEntity::class))->isEntity());
    }

    /**
     * @test
     */
    public function classSchemaDetectsValueObjects()
    {
        self::assertTrue((new ClassSchema(Fixture\DummyValueObject::class))->isValueObject());
    }

    /**
     * @test
     */
    public function classSchemaDetectsClassName()
    {
        $this->resetSingletonInstances = true;
        self::assertSame(Fixture\DummyModel::class, (new ClassSchema(Fixture\DummyModel::class))->getClassName());
    }

    /**
     * @test
     */
    public function classSchemaDetectsNonStaticProperties()
    {
        self::assertTrue((new ClassSchema(Fixture\DummyClassWithAllTypesOfProperties::class))->hasProperty('publicProperty'));
        self::assertTrue((new ClassSchema(Fixture\DummyClassWithAllTypesOfProperties::class))->hasProperty('protectedProperty'));
        self::assertTrue((new ClassSchema(Fixture\DummyClassWithAllTypesOfProperties::class))->hasProperty('privateProperty'));
    }

    /**
     * @test
     */
    public function classSchemaDetectsStaticProperties()
    {
        self::assertTrue((new ClassSchema(Fixture\DummyClassWithAllTypesOfProperties::class))->hasProperty('publicStaticProperty'));
        self::assertTrue((new ClassSchema(Fixture\DummyClassWithAllTypesOfProperties::class))->hasProperty('protectedStaticProperty'));
        self::assertTrue((new ClassSchema(Fixture\DummyClassWithAllTypesOfProperties::class))->hasProperty('privateStaticProperty'));
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
        self::assertSame('string', $classSchema->getMethod('foo')->getParameter('foo')->getType());
        self::assertSame(ClassSchema::class, $classSchema->getMethod('bar')->getParameter('foo')->getType());
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
        self::assertSame('string', $classSchema->getMethod('foo')->getParameter('foo')->getType());
    }

    /**
     * @test
     */
    public function classSchemaDetectsMethodParameterTypeDetectionViaDocBlocksIfNoTypeHintIsGiven(): void
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithAllTypesOfMethods::class);
        self::assertSame(Fixture\DummyClassWithAllTypesOfMethods::class, $classSchema->getMethod('methodWithDocBlockTypeHintOnly')->getParameter('param')->getType());
    }
}
