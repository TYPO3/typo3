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
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\GenericType;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\Type\UnionType;
use Symfony\Component\TypeInfo\TypeIdentifier;
use TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\Reflection\ClassSchema;
use TYPO3\CMS\Extbase\Reflection\ClassSchema\Property;
use TYPO3\CMS\Extbase\Reflection\ClassSchema\PropertyCharacteristics;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyClassWithAllTypesOfMethods;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyClassWithAllTypesOfProperties;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyControllerWithValidateAttributeWithoutParamTypeHint;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyModel;
use TYPO3\CMS\Extbase\Validation\Exception\InvalidTypeHintException;
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
            array_keys(new ClassSchema(DummyClassWithAllTypesOfProperties::class)->getProperties())
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
            ],
            array_keys(new ClassSchema(DummyClassWithAllTypesOfMethods::class)->getMethods())
        );
    }

    #[Test]
    public function classSchemaDetectsDynamicProperties(): void
    {
        self::assertTrue(new ClassSchema(DummyClassWithAllTypesOfProperties::class)->hasProperty('publicProperty'));
        self::assertTrue(new ClassSchema(DummyClassWithAllTypesOfProperties::class)->hasProperty('protectedProperty'));
        self::assertTrue(new ClassSchema(DummyClassWithAllTypesOfProperties::class)->hasProperty('privateProperty'));
    }

    #[Test]
    public function classSchemaGenerationThrowsExceptionWithValidateAttributesForParamWithoutTypeHint(): void
    {
        $this->expectException(InvalidTypeHintException::class);
        $this->expectExceptionMessageIs('Missing type information for parameter "$fooParam" in TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyControllerWithValidateAttributeWithoutParamTypeHint->methodWithValidateAttributesAction(): Use a type hint.');
        $this->expectExceptionCode(1515075192);

        new ClassSchema(DummyControllerWithValidateAttributeWithoutParamTypeHint::class);
    }

    #[Test]
    public function classSchemaDetectsMethodParameterTypeViaReflection(): void
    {
        $class = new class {
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
        $class = new class {
            /**
             * @param ClassSchema $foo
             */
            public function foo(mixed $foo): void {}
        };

        $classSchema = new ClassSchema(get_class($class));
        self::assertSame('mixed', $classSchema->getMethod('foo')->getParameter('foo')->getType());
    }

    #[Test]
    public function classSchemaCanHandleSelfMethodReturnTypes(): void
    {
        $class = new class {
            public function __construct(public ?self $copy = null) {}

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
    public function classSchemaSurvivesSerialization(): void
    {
        // Given a schema with cascade, validators, types and a method
        $classSchema = new ClassSchema(DummyClassWithAllTypesOfProperties::class);

        // When it is serialized and unserialized again
        $restored = $this->serializeAndRestore($classSchema);

        // Then it equals the original one
        self::assertEquals($classSchema, $restored);
        self::assertSame('remove', $restored->getProperty('propertyWithCascadeAttribute')->cascade);
    }

    #[Test]
    public function classSchemaWithFileUploadSurvivesSerialization(): void
    {
        // Given a schema with a file upload property
        $classSchema = new ClassSchema(DummyModel::class);

        // When it is serialized and unserialized again
        $restored = $this->serializeAndRestore($classSchema);

        // Then it equals the original one and the file upload configuration is restored
        self::assertEquals($classSchema, $restored);
        $fileUpload = $restored->getProperty('propertyWithFileUploadAttribute')->fileUpload;
        self::assertNotNull($fileUpload);
        self::assertEquals($classSchema->getProperty('propertyWithFileUploadAttribute')->fileUpload, $fileUpload);
    }

    #[Test]
    public function serializedClassSchemaContainsNoPropertyObjects(): void
    {
        $serialized = serialize(new ClassSchema(DummyClassWithAllTypesOfProperties::class));

        self::assertStringNotContainsString(Property::class, $serialized);
        self::assertStringNotContainsString(PropertyCharacteristics::class, $serialized);
    }

    private function serializeAndRestore(ClassSchema $classSchema): ClassSchema
    {
        return unserialize(serialize($classSchema), [
            'allowed_classes' => [
                ClassSchema::class,
                BuiltinType::class,
                ObjectType::class,
                CollectionType::class,
                GenericType::class,
                NullableType::class,
                UnionType::class,
                TypeIdentifier::class,
                DuplicationBehavior::class,
            ],
        ]);
    }
}
