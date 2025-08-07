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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\Reflection\ClassSchema;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyClassWithAllTypesOfMethods;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyClassWithAllTypesOfProperties;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyControllerWithValidateAttributeWithoutParam;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyControllerWithValidateAttributeWithoutParamTypeHint;
use TYPO3\CMS\Extbase\Validation\Exception\InvalidTypeHintException;
use TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationConfigurationException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ClassSchemaTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[Test]
    public function classSchemaGetProperties(): void
    {
        self::assertSame(
            [
                'publicProperty',
                'protectedProperty',
                'privateProperty',
                'publicPropertyWithDefaultValue',
                'stringTypedProperty',
                'nullableStringTypedProperty',
                'propertyWithTransientAttribute',
                'propertyWithCascadeAttribute',
                'propertyWithObjectStorageAnnotation',
                'propertyWithObjectStorageAnnotationWithoutFQCN',
                'propertyWithLazyLoadingProxy',
                'propertyWithLazyObjectStorageAnnotationWithoutFQCN',
                AbstractDomainObject::PROPERTY_UID,
                AbstractDomainObject::PROPERTY_LOCALIZED_UID,
                AbstractDomainObject::PROPERTY_LANGUAGE_UID,
                AbstractDomainObject::PROPERTY_VERSIONED_UID,
                AbstractDomainObject::PROPERTY_PID,
            ],
            array_keys((new ClassSchema(DummyClassWithAllTypesOfProperties::class))->getProperties())
        );
    }

    #[Test]
    public function classSchemaHasMethod(): void
    {
        $classSchema = new ClassSchema(DummyClassWithAllTypesOfMethods::class);
        self::assertTrue($classSchema->hasMethod('publicMethod'));
        self::assertFalse($classSchema->hasMethod('nonExistentMethod'));
    }

    #[Test]
    public function classSchemaGetMethods(): void
    {
        self::assertSame(
            [
                'publicMethod',
                'protectedMethod',
                'privateMethod',
                'methodWithMandatoryParam',
                'methodWithDefaultValueParam',
                'methodWithTypeHintedParam',
                'methodWithDocBlockTypeHintOnly',
            ],
            array_keys((new ClassSchema(DummyClassWithAllTypesOfMethods::class))->getMethods())
        );
    }

    #[Test]
    public function classSchemaDetectsDynamicProperties(): void
    {
        self::assertTrue((new ClassSchema(DummyClassWithAllTypesOfProperties::class))->hasProperty('publicProperty'));
        self::assertTrue((new ClassSchema(DummyClassWithAllTypesOfProperties::class))->hasProperty('protectedProperty'));
        self::assertTrue((new ClassSchema(DummyClassWithAllTypesOfProperties::class))->hasProperty('privateProperty'));
    }

    #[Test]
    public function classSchemaGenerationThrowsExceptionWithValidateDoctrineAttributesForParamWithoutTypeHint(): void
    {
        $this->expectException(InvalidTypeHintException::class);
        $this->expectExceptionMessage('Missing type information for parameter "$fooParam" in TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyControllerWithValidateAttributeWithoutParamTypeHint->methodWithValidateAttributesAction(): Use a type hint.');
        $this->expectExceptionCode(1515075192);

        new ClassSchema(DummyControllerWithValidateAttributeWithoutParamTypeHint::class);
    }

    #[Test]
    public function classSchemaGenerationThrowsExceptionWithValidateDoctrineAttributesForMissingParam(): void
    {
        $this->expectException(InvalidValidationConfigurationException::class);
        $this->expectExceptionMessage('Invalid validate annotation in TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyControllerWithValidateAttributeWithoutParam->methodWithValidateAttributesAction(): The following validators have been defined for missing param "$fooParam": NotEmpty, StringLength');
        $this->expectExceptionCode(1515073585);

        new ClassSchema(DummyControllerWithValidateAttributeWithoutParam::class);
    }

    #[Test]
    public function classSchemaDetectsMethodParameterTypeViaReflection(): void
    {
        $class = new class () {
            public function foo(string $foo): void {}

            public function bar(ClassSchema $foo): void {}
        };

        $classSchema = new ClassSchema(get_class($class));
        self::assertSame('string', $classSchema->getMethod('foo')->getParameter('foo')->getType());
        self::assertSame(ClassSchema::class, $classSchema->getMethod('bar')->getParameter('foo')->getType());
    }

    #[Test]
    public function classSchemaPrefersMethodParameterTypeDetectionViaReflection(): void
    {
        $class = new class () {
            /**
             * @param ClassSchema $foo
             */
            public function foo(string $foo): void {}
        };

        $classSchema = new ClassSchema(get_class($class));
        self::assertSame('string', $classSchema->getMethod('foo')->getParameter('foo')->getType());
    }

    #[Test]
    public function classSchemaCanHandleSelfMethodReturnTypes(): void
    {
        $class = new class () {
            public function __construct(?self $copy = null) {}
            public function injectCopy(self $copy): void {}
            public function foo($copy): self
            {
                return $this;
            }
            public function bar(self $copy): void {}
        };

        $classSchema = new ClassSchema(get_class($class));
        self::assertSame(get_class($class), $classSchema->getMethod('injectCopy')->getParameter('copy')->getType());
        self::assertSame(get_class($class), $classSchema->getMethod('bar')->getParameter('copy')->getType());
    }

    #[Test]
    public function classSchemaDetectsMethodParameterTypeDetectionViaDocBlocksIfNoTypeHintIsGiven(): void
    {
        $classSchema = new ClassSchema(DummyClassWithAllTypesOfMethods::class);
        self::assertSame(DummyClassWithAllTypesOfMethods::class, $classSchema->getMethod('methodWithDocBlockTypeHintOnly')->getParameter('param')->getType());
    }
}
