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

namespace TYPO3\CMS\Extbase\Tests\Unit\Reflection\ClassSchema;

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Reflection\ClassSchema;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyClassWithAllTypesOfProperties;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyClassWithLazyDoctrineAnnotation;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyModel;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyModelDeprecated;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\Validation\Validator\DummyValidator;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PropertyTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[Test]
    public function classSchemaDetectsPropertiesWithLazyAnnotation(): void
    {
        $classSchema = new ClassSchema(DummyClassWithLazyDoctrineAnnotation::class);
        self::assertTrue($classSchema->getProperty('propertyWithLazyAnnotation')->isLazy());
    }

    #[Test]
    public function classSchemaDetectsPropertiesWithLazyAttribute(): void
    {
        $classSchema = new ClassSchema(DummyClassWithLazyDoctrineAnnotation::class);
        self::assertTrue($classSchema->getProperty('propertyWithLazyAttribute')->isLazy());
    }

    #[Test]
    public function classSchemaDetectsPropertyVisibility(): void
    {
        $classSchema = new ClassSchema(DummyClassWithAllTypesOfProperties::class);

        $property = $classSchema->getProperty('publicProperty');
        self::assertTrue($property->isPublic());
        self::assertFalse($property->isProtected());
        self::assertFalse($property->isPrivate());

        $property = $classSchema->getProperty('protectedProperty');
        self::assertFalse($property->isPublic());
        self::assertTrue($property->isProtected());
        self::assertFalse($property->isPrivate());

        $property = $classSchema->getProperty('privateProperty');
        self::assertFalse($property->isPublic());
        self::assertFalse($property->isProtected());
        self::assertTrue($property->isPrivate());
    }

    #[Test]
    public function classSchemaDetectsTransientProperty(): void
    {
        $property = (new ClassSchema(DummyClassWithAllTypesOfProperties::class))
            ->getProperty('propertyWithTransientAnnotation');

        self::assertTrue($property->isTransient());
    }

    #[Test]
    public function classSchemaDetectsTransientPropertyFromAttribute(): void
    {
        $property = (new ClassSchema(DummyClassWithAllTypesOfProperties::class))
            ->getProperty('propertyWithTransientAttribute');

        self::assertTrue($property->isTransient());
    }

    #[Test]
    public function classSchemaDetectsCascadeProperty(): void
    {
        $property = (new ClassSchema(DummyClassWithAllTypesOfProperties::class))
            ->getProperty('propertyWithCascadeAnnotation');

        self::assertSame('remove', $property->getCascadeValue());
    }

    #[Test]
    public function classSchemaDetectsCascadePropertyFromAttribute(): void
    {
        $property = (new ClassSchema(DummyClassWithAllTypesOfProperties::class))
            ->getProperty('propertyWithCascadeAttribute');

        self::assertSame('remove', $property->getCascadeValue());
    }

    #[Test]
    public function classSchemaDetectsCascadePropertyOnlyWithVarAnnotation(): void
    {
        $property = (new ClassSchema(DummyClassWithAllTypesOfProperties::class))
            ->getProperty('propertyWithCascadeAnnotationWithoutVarAnnotation');

        self::assertNull($property->getCascadeValue());
    }

    #[Test]
    public function classSchemaDetectsTypeAndElementType(): void
    {
        $property = (new ClassSchema(DummyClassWithAllTypesOfProperties::class))
            ->getProperty('propertyWithObjectStorageAnnotation');

        $propertyTypes = $property->getTypes();

        self::assertCount(1, $propertyTypes);

        $propertyType = reset($propertyTypes);

        self::assertSame(ObjectStorage::class, $propertyType->getClassName());

        self::assertCount(2, $propertyType->getCollectionKeyTypes());
        self::assertSame('string', $propertyType->getCollectionKeyTypes()[0]->getBuiltinType());
        self::assertSame('int', $propertyType->getCollectionKeyTypes()[1]->getBuiltinType());
        self::assertCount(1, $propertyType->getCollectionValueTypes());

        self::assertSame(DummyClassWithAllTypesOfProperties::class, $propertyType->getCollectionValueTypes()[0]->getClassName());
    }

    #[Test]
    public function classSchemaDetectsTypeAndElementTypeWithoutFQCN(): void
    {
        $property = (new ClassSchema(DummyClassWithAllTypesOfProperties::class))
            ->getProperty('propertyWithObjectStorageAnnotationWithoutFQCN');

        self::assertCount(1, $property->getTypes());

        self::assertSame(ObjectStorage::class, $property->getTypes()[0]->getClassName());
        self::assertSame(DummyClassWithAllTypesOfProperties::class, $property->getTypes()[0]->getCollectionValueTypes()[0]->getClassName());
    }

    #[Test]
    public function classSchemaDetectsValidateAnnotationsModelProperties(): void
    {
        $property = (new ClassSchema(DummyModel::class))
            ->getProperty('propertyWithValidateAnnotations');

        self::assertSame(
            [
                [
                    'name' => 'StringLength',
                    'options' => [
                        'minimum' => 1,
                        'maximum' => 10,
                    ],
                    'className' => StringLengthValidator::class,
                ],
                [
                    'name' => 'NotEmpty',
                    'options' => [],
                    'className' => NotEmptyValidator::class,
                ],
                [
                    'name' => '\TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator',
                    'options' => [],
                    'className' => NotEmptyValidator::class,
                ],
                [
                    'name' => NotEmptyValidator::class,
                    'options' => [],
                    'className' => NotEmptyValidator::class,
                ],
            ],
            $property->getValidators()
        );
    }
    #[Test]
    public function classSchemaDetectsValidateAttributeModelProperties(): void
    {
        $property = (new ClassSchema(DummyModel::class))
            ->getProperty('propertyWithValidateAttributes');

        self::assertSame(
            [
                [
                    'name' => 'StringLength',
                    'options' => [
                        'minimum' => 1,
                        'maximum' => 10,
                    ],
                    'className' => StringLengthValidator::class,
                ],
                [
                    'name' => 'NotEmpty',
                    'options' => [],
                    'className' => NotEmptyValidator::class,
                ],
                [
                    'name' => '\TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator',
                    'options' => [],
                    'className' => NotEmptyValidator::class,
                ],
                [
                    'name' => NotEmptyValidator::class,
                    'options' => [],
                    'className' => NotEmptyValidator::class,
                ],
            ],
            $property->getValidators()
        );
    }

    #[Test]
    public function classSchemaDetectsValidateAttributeOnPromotedModelProperties(): void
    {
        $property = (new ClassSchema(DummyModel::class))
            ->getProperty('dummyPromotedProperty');

        self::assertSame(
            [
                [
                    'name' => 'StringLength',
                    'options' => [
                        'minimum' => 1,
                        'maximum' => 10,
                    ],
                    'className' => StringLengthValidator::class,
                ],
                [
                    'name' => 'NotEmpty',
                    'options' => [],
                    'className' => NotEmptyValidator::class,
                ],
                [
                    'name' => '\TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator',
                    'options' => [],
                    'className' => NotEmptyValidator::class,
                ],
                [
                    'name' => NotEmptyValidator::class,
                    'options' => [],
                    'className' => NotEmptyValidator::class,
                ],
            ],
            $property->getValidators()
        );
    }

    #[Test]
    public function classSchemaDetectsTypeFromPropertyWithStringTypeHint(): void
    {
        $property = (new ClassSchema(DummyClassWithAllTypesOfProperties::class))
            ->getProperty('stringTypedProperty');

        self::assertCount(1, $property->getTypes());
        self::assertSame('string', $property->getTypes()[0]->getBuiltinType());
    }

    #[Test]
    public function classSchemaDetectsTypeFromPropertyWithNullableStringTypeHint(): void
    {
        $property = (new ClassSchema(DummyClassWithAllTypesOfProperties::class))
            ->getProperty('nullableStringTypedProperty');

        self::assertCount(1, $property->getTypes());
        self::assertSame('string', $property->getTypes()[0]->getBuiltinType());
    }

    #[Test]
    public function isObjectStorageTypeDetectsObjectStorage(): void
    {
        $property = (new ClassSchema(DummyClassWithAllTypesOfProperties::class))
            ->getProperty('propertyWithObjectStorageAnnotationWithoutFQCN');

        self::assertTrue($property->isObjectStorageType());
    }

    #[Test]
    public function isObjectStorageTypeDetectsLazyObjectStorage(): void
    {
        $property = (new ClassSchema(DummyClassWithAllTypesOfProperties::class))
            ->getProperty('propertyWithLazyObjectStorageAnnotationWithoutFQCN');

        self::assertTrue($property->isObjectStorageType());
    }

    #[Test]
    public function filterLazyLoadingProxyAndLazyObjectStorageFiltersLazyLoadingProxy(): void
    {
        $property = (new ClassSchema(DummyClassWithAllTypesOfProperties::class))
            ->getProperty('propertyWithLazyLoadingProxy');

        $types = $property->getFilteredTypes([$property, 'filterLazyLoadingProxyAndLazyObjectStorage']);

        self::assertCount(1, $types);
        self::assertSame(DummyClassWithAllTypesOfProperties::class, $types[0]->getClassName());
    }

    #[Test]
    public function filterLazyLoadingProxyAndLazyObjectStorageFiltersLazyObjectStorage(): void
    {
        $property = (new ClassSchema(DummyClassWithAllTypesOfProperties::class))
            ->getProperty('propertyWithLazyObjectStorageAnnotationWithoutFQCN');

        $types = $property->getFilteredTypes([$property, 'filterLazyLoadingProxyAndLazyObjectStorage']);

        self::assertCount(1, $types);
        self::assertSame(ObjectStorage::class, $types[0]->getClassName());
    }

    #[Test]
    #[IgnoreDeprecations]
    public function classSchemaDetectsValidateAnnotationsModelPropertiesDeprecated(): void
    {
        $property = (new ClassSchema(DummyModelDeprecated::class))
            ->getProperty('propertyWithValidateAnnotations');

        self::assertSame(
            [
                [
                    'name' => 'TYPO3.CMS.Extbase:NotEmpty',
                    'options' => [],
                    'className' => NotEmptyValidator::class,
                ],
                [
                    'name' => 'TYPO3.CMS.Extbase.Tests.Unit.Reflection.Fixture:DummyValidator',
                    'options' => [],
                    'className' => DummyValidator::class,
                ],
            ],
            $property->getValidators()
        );
    }

    #[Test]
    #[IgnoreDeprecations]
    public function classSchemaDetectsValidateAttributeModelPropertiesDeprecated(): void
    {
        $property = (new ClassSchema(DummyModelDeprecated::class))
            ->getProperty('propertyWithValidateAttributes');

        self::assertSame(
            [
                [
                    'name' => 'TYPO3.CMS.Extbase:NotEmpty',
                    'options' => [],
                    'className' => NotEmptyValidator::class,
                ],
                [
                    'name' => 'TYPO3.CMS.Extbase.Tests.Unit.Reflection.Fixture:DummyValidator',
                    'options' => [],
                    'className' => DummyValidator::class,
                ],
            ],
            $property->getValidators()
        );
    }

    #[Test]
    #[IgnoreDeprecations]
    public function classSchemaDetectsValidateAttributeOnPromotedModelPropertiesDeprecated(): void
    {
        $property = (new ClassSchema(DummyModelDeprecated::class))
            ->getProperty('dummyPromotedProperty');

        self::assertSame(
            [
                [
                    'name' => 'TYPO3.CMS.Extbase:NotEmpty',
                    'options' => [],
                    'className' => NotEmptyValidator::class,
                ],
                [
                    'name' => 'TYPO3.CMS.Extbase.Tests.Unit.Reflection.Fixture:DummyValidator',
                    'options' => [],
                    'className' => DummyValidator::class,
                ],
            ],
            $property->getValidators()
        );
    }
}
