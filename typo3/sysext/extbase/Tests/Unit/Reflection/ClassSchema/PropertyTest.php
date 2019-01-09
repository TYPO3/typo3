<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Extbase\Tests\Unit\Reflection\ClassSchema;

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
        static::assertTrue($classSchema->getProperty('propertyWithLazyAnnotation')->getAnnotationValue('lazy'));
    }

    /**
     * @test
     */
    public function classSchemaDetectsPropertyVisibility(): void
    {
        $classSchema = new ClassSchema(DummyClassWithAllTypesOfProperties::class);

        $property = $classSchema->getProperty('publicProperty');
        static::assertTrue($property->isPublic());
        static::assertFalse($property->isProtected());
        static::assertFalse($property->isPrivate());

        $property = $classSchema->getProperty('protectedProperty');
        static::assertFalse($property->isPublic());
        static::assertTrue($property->isProtected());
        static::assertFalse($property->isPrivate());

        $property = $classSchema->getProperty('privateProperty');
        static::assertFalse($property->isPublic());
        static::assertFalse($property->isProtected());
        static::assertTrue($property->isPrivate());
    }

    /**
     * @test
     */
    public function classSchemaDetectsInjectProperty(): void
    {
        $property = (new ClassSchema(DummyClassWithAllTypesOfProperties::class))
            ->getProperty('propertyWithInjectAnnotation');

        static::assertTrue($property->hasAnnotation('inject'));
        static::assertTrue($property->getAnnotationValue('inject'));
    }

    /**
     * @test
     */
    public function classSchemaDetectsTransientProperty(): void
    {
        $property = (new ClassSchema(DummyClassWithAllTypesOfProperties::class))
            ->getProperty('propertyWithTransientAnnotation');

        static::assertTrue($property->hasAnnotation('transient'));
        static::assertTrue($property->getAnnotationValue('transient'));
    }

    /**
     * @test
     */
    public function classSchemaDetectsCascadeProperty(): void
    {
        $property = (new ClassSchema(DummyClassWithAllTypesOfProperties::class))
            ->getProperty('propertyWithCascadeAnnotation');

        static::assertTrue($property->hasAnnotation('cascade'));
        static::assertSame('remove', $property->getAnnotationValue('cascade'));
    }

    /**
     * @test
     */
    public function classSchemaDetectsCascadePropertyOnlyWithVarAnnotation(): void
    {
        $property = (new ClassSchema(DummyClassWithAllTypesOfProperties::class))
            ->getProperty('propertyWithCascadeAnnotationWithoutVarAnnotation');

        static::assertFalse($property->hasAnnotation('cascade'));
        static::assertNull($property->getAnnotationValue('cascade'));
    }

    /**
     * @test
     */
    public function classSchemaDetectsTypeAndElementType(): void
    {
        $property = (new ClassSchema(DummyClassWithAllTypesOfProperties::class))
            ->getProperty('propertyWithObjectStorageAnnotation');

        static::assertSame(ObjectStorage::class, $property->getType());
        static::assertSame(DummyClassWithAllTypesOfProperties::class, $property->getElementType());
    }

    /**
     * @test
     */
    public function classSchemaDetectsValidateAnnotationsModelProperties(): void
    {
        $this->resetSingletonInstances = true;
        $property = (new ClassSchema(DummyModel::class))
            ->getProperty('propertyWithValidateAnnotations');

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
