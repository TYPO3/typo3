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
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\Reflection\ClassSchema;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyClassWithAllTypesOfMethods;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyClassWithAllTypesOfProperties;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyClassWithConstructorAndConstructorArguments;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyControllerWithValidateAnnotationWithoutParam;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyControllerWithValidateAnnotationWithoutParamTypeHint;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyControllerWithValidateAttributeWithoutParam;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyControllerWithValidateAttributeWithoutParamTypeHint;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyEntity;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyModel;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummySingleton;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyValueObject;
use TYPO3\CMS\Extbase\Validation\Exception\InvalidTypeHintException;
use TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationConfigurationException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ClassSchemaTest extends UnitTestCase
{
    /**
     * @test
     */
    public function classSchemaForModelIsSetAggregateRootIfRepositoryClassIsFoundForNamespacedClasses(): void
    {
        $this->resetSingletonInstances = true;
        $service = new ReflectionService(new NullFrontend('extbase'), 'ClassSchemata');
        $classSchema = $service->getClassSchema(DummyModel::class);
        self::assertTrue($classSchema->isAggregateRoot());
    }

    /**
     * @test
     */
    public function classSchemaHasConstructor(): void
    {
        $classSchema = new ClassSchema(DummyClassWithConstructorAndConstructorArguments::class);
        self::assertTrue($classSchema->hasConstructor());
    }

    /**
     * @test
     */
    public function classSchemaGetProperties(): void
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
                'publicStaticPropertyWithDefaultValue',
                'stringTypedProperty',
                'nullableStringTypedProperty',
                'propertyWithIgnoredTags',
                'propertyWithInjectAnnotation',
                'propertyWithTransientAnnotation',
                'propertyWithTransientAttribute',
                'propertyWithCascadeAnnotation',
                'propertyWithCascadeAnnotationWithoutVarAnnotation',
                'propertyWithCascadeAttribute',
                'propertyWithObjectStorageAnnotation',
                'propertyWithObjectStorageAnnotationWithoutFQCN',
                AbstractDomainObject::PROPERTY_UID,
                AbstractDomainObject::PROPERTY_LOCALIZED_UID,
                AbstractDomainObject::PROPERTY_LANGUAGE_UID,
                AbstractDomainObject::PROPERTY_VERSIONED_UID,
                AbstractDomainObject::PROPERTY_PID,
            ],
            array_keys((new ClassSchema(DummyClassWithAllTypesOfProperties::class))->getProperties())
        );
    }

    /**
     * @test
     */
    public function classSchemaHasMethod(): void
    {
        $classSchema = new ClassSchema(DummyClassWithAllTypesOfMethods::class);
        self::assertTrue($classSchema->hasMethod('publicMethod'));
        self::assertFalse($classSchema->hasMethod('nonExistentMethod'));
    }

    /**
     * @test
     */
    public function classSchemaGetMethods(): void
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
            array_keys((new ClassSchema(DummyClassWithAllTypesOfMethods::class))->getMethods())
        );
    }

    /**
     * @test
     */
    public function classSchemaDetectsPropertyDefaultValue(): void
    {
        $classSchema = new ClassSchema(DummyClassWithAllTypesOfProperties::class);

        $propertyDefinition = $classSchema->getProperty('publicPropertyWithDefaultValue');
        self::assertSame('foo', $propertyDefinition->getDefaultValue());
    }

    /**
     * @test
     */
    public function classSchemaSkipsDetectionOfDefaultValuesOfStaticClassProperties(): void
    {
        $classSchema = new ClassSchema(DummyClassWithAllTypesOfProperties::class);

        $propertyDefinition = $classSchema->getProperty('publicStaticPropertyWithDefaultValue');
        self::assertNull($propertyDefinition->getDefaultValue());
    }

    /**
     * @test
     */
    public function classSchemaDetectsSingletons(): void
    {
        self::assertTrue((new ClassSchema(DummySingleton::class))->isSingleton());
    }

    /**
     * @test
     */
    public function classSchemaDetectsModels(): void
    {
        self::assertTrue((new ClassSchema(DummyEntity::class))->isModel());
        self::assertTrue((new ClassSchema(DummyValueObject::class))->isModel());
    }

    /**
     * @test
     */
    public function classSchemaDetectsEntities(): void
    {
        self::assertTrue((new ClassSchema(DummyEntity::class))->isEntity());
    }

    /**
     * @test
     */
    public function classSchemaDetectsValueObjects(): void
    {
        self::assertTrue((new ClassSchema(DummyValueObject::class))->isValueObject());
    }

    /**
     * @test
     */
    public function classSchemaDetectsClassName(): void
    {
        $this->resetSingletonInstances = true;
        self::assertSame(DummyModel::class, (new ClassSchema(DummyModel::class))->getClassName());
    }

    /**
     * @test
     */
    public function classSchemaDetectsNonStaticProperties(): void
    {
        self::assertTrue((new ClassSchema(DummyClassWithAllTypesOfProperties::class))->hasProperty('publicProperty'));
        self::assertTrue((new ClassSchema(DummyClassWithAllTypesOfProperties::class))->hasProperty('protectedProperty'));
        self::assertTrue((new ClassSchema(DummyClassWithAllTypesOfProperties::class))->hasProperty('privateProperty'));
    }

    /**
     * @test
     */
    public function classSchemaDetectsStaticProperties(): void
    {
        self::assertTrue((new ClassSchema(DummyClassWithAllTypesOfProperties::class))->hasProperty('publicStaticProperty'));
        self::assertTrue((new ClassSchema(DummyClassWithAllTypesOfProperties::class))->hasProperty('protectedStaticProperty'));
        self::assertTrue((new ClassSchema(DummyClassWithAllTypesOfProperties::class))->hasProperty('privateStaticProperty'));
    }

    /**
     * @test
     */
    public function classSchemaGenerationThrowsExceptionWithValidateDoctrineAnnotationsForParamWithoutTypeHint(): void
    {
        $this->resetSingletonInstances = true;
        $this->expectException(InvalidTypeHintException::class);
        $this->expectExceptionMessage('Missing type information for parameter "$fooParam" in TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyControllerWithValidateAnnotationWithoutParamTypeHint->methodWithValidateAnnotationsAction(): Use a type hint.');
        $this->expectExceptionCode(1515075192);

        new ClassSchema(DummyControllerWithValidateAnnotationWithoutParamTypeHint::class);
    }

    /**
     * @test
     */
    public function classSchemaGenerationThrowsExceptionWithValidateDoctrineAnnotationsForMissingParam(): void
    {
        $this->resetSingletonInstances = true;
        $this->expectException(InvalidValidationConfigurationException::class);
        $this->expectExceptionMessage('Invalid validate annotation in TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyControllerWithValidateAnnotationWithoutParam->methodWithValidateAnnotationsAction(): The following validators have been defined for missing param "$fooParam": NotEmpty, StringLength');
        $this->expectExceptionCode(1515073585);

        new ClassSchema(DummyControllerWithValidateAnnotationWithoutParam::class);
    }
    /**
     * @test
     */
    public function classSchemaGenerationThrowsExceptionWithValidateDoctrineAttributesForParamWithoutTypeHint(): void
    {
        $this->resetSingletonInstances = true;
        $this->expectException(InvalidTypeHintException::class);
        $this->expectExceptionMessage('Missing type information for parameter "$fooParam" in TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyControllerWithValidateAttributeWithoutParamTypeHint->methodWithValidateAttributesAction(): Use a type hint.');
        $this->expectExceptionCode(1515075192);

        new ClassSchema(DummyControllerWithValidateAttributeWithoutParamTypeHint::class);
    }

    /**
     * @test
     */
    public function classSchemaGenerationThrowsExceptionWithValidateDoctrineAttributesForMissingParam(): void
    {
        $this->resetSingletonInstances = true;
        $this->expectException(InvalidValidationConfigurationException::class);
        $this->expectExceptionMessage('Invalid validate annotation in TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyControllerWithValidateAttributeWithoutParam->methodWithValidateAttributesAction(): The following validators have been defined for missing param "$fooParam": NotEmpty, StringLength');
        $this->expectExceptionCode(1515073585);

        new ClassSchema(DummyControllerWithValidateAttributeWithoutParam::class);
    }

    /**
     * @test
     */
    public function classSchemaDetectsMethodParameterTypeViaReflection(): void
    {
        $class = new class () {
            public function foo(string $foo): void
            {
            }

            public function bar(ClassSchema $foo): void
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
        $class = new class () {
            /**
             * @param ClassSchema $foo
             */
            public function foo(string $foo): void
            {
            }
        };

        $classSchema = new ClassSchema(get_class($class));
        self::assertSame('string', $classSchema->getMethod('foo')->getParameter('foo')->getType());
    }

    /**
     * @test
     */
    public function classSchemaCanHandleSelfMethodReturnTypes(): void
    {
        $class = new class () {
            public function __construct(self $copy = null)
            {
            }
            public function injectCopy(self $copy): void
            {
            }
            public function foo($copy): self
            {
                return $this;
            }
            public function bar(self $copy): void
            {
            }
        };

        $classSchema = new ClassSchema(get_class($class));
        self::assertSame(get_class($class), $classSchema->getMethod('injectCopy')->getParameter('copy')->getType());
        self::assertSame(get_class($class), $classSchema->getMethod('bar')->getParameter('copy')->getType());
    }

    /**
     * @test
     */
    public function classSchemaDetectsMethodParameterTypeDetectionViaDocBlocksIfNoTypeHintIsGiven(): void
    {
        $classSchema = new ClassSchema(DummyClassWithAllTypesOfMethods::class);
        self::assertSame(DummyClassWithAllTypesOfMethods::class, $classSchema->getMethod('methodWithDocBlockTypeHintOnly')->getParameter('param')->getType());
    }
}
