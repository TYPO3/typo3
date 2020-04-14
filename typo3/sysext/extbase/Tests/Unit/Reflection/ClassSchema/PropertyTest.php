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

use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Reflection\ClassSchema;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyClassWithAllTypesOfProperties;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyClassWithLazyDoctrineAnnotation;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyModel;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\Validation\Validator\DummyValidator;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class TYPO3\CMS\Extbase\Tests\Unit\Reflection\PropertyTest
 */
class PropertyTest extends UnitTestCase
{
    /**
     * @test
     */
    public function classSchemaDetectsPropertiesWithLazyAnnotation(): void
    {
        $classSchema = new ClassSchema(DummyClassWithLazyDoctrineAnnotation::class);
        self::assertTrue($classSchema->getProperty('propertyWithLazyAnnotation')->isLazy());
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function classSchemaDetectsInjectProperty(): void
    {
        $property = (new ClassSchema(DummyClassWithAllTypesOfProperties::class))
            ->getProperty('propertyWithInjectAnnotation');

        self::assertTrue($property->isInjectProperty());
    }

    /**
     * @test
     */
    public function classSchemaDetectsTransientProperty(): void
    {
        $property = (new ClassSchema(DummyClassWithAllTypesOfProperties::class))
            ->getProperty('propertyWithTransientAnnotation');

        self::assertTrue($property->isTransient());
    }

    /**
     * @test
     */
    public function classSchemaDetectsCascadeProperty(): void
    {
        $property = (new ClassSchema(DummyClassWithAllTypesOfProperties::class))
            ->getProperty('propertyWithCascadeAnnotation');

        self::assertSame('remove', $property->getCascadeValue());
    }

    /**
     * @test
     */
    public function classSchemaDetectsCascadePropertyOnlyWithVarAnnotation(): void
    {
        $property = (new ClassSchema(DummyClassWithAllTypesOfProperties::class))
            ->getProperty('propertyWithCascadeAnnotationWithoutVarAnnotation');

        self::assertNull($property->getCascadeValue());
    }

    /**
     * @test
     */
    public function classSchemaDetectsTypeAndElementType(): void
    {
        $property = (new ClassSchema(DummyClassWithAllTypesOfProperties::class))
            ->getProperty('propertyWithObjectStorageAnnotation');

        self::assertSame(ObjectStorage::class, $property->getType());
        self::assertSame(DummyClassWithAllTypesOfProperties::class, $property->getElementType());
    }

    /**
     * @test
     */
    public function classSchemaDetectsTypeAndElementTypeWithoutFQCN(): void
    {
        $property = (new ClassSchema(DummyClassWithAllTypesOfProperties::class))
            ->getProperty('propertyWithObjectStorageAnnotationWithoutFQCN');

        self::assertSame(ObjectStorage::class, $property->getType());
        self::assertSame(DummyClassWithAllTypesOfProperties::class, $property->getElementType());
    }

    /**
     * @test
     */
    public function classSchemaDetectsValidateAnnotationsModelProperties(): void
    {
        $this->resetSingletonInstances = true;
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
                    'className' => DummyValidator::class
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
            $property->getValidators()
        );
    }
}
