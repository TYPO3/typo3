<?php
namespace TYPO3\CMS\Extbase\Tests\UnitDeprecated\Reflection;

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
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    public function testClassSchemaDetectsLazyProperties()
    {
        $classSchema = new ClassSchema(Fixture\DummyClassWithLazyProperty::class);
        static::assertTrue($classSchema->getProperty('propertyWithLazyAnnotation')['annotations']['lazy']);
    }

    public function testClassSchemaDetectsIgnoreValidationAnnotation()
    {
        $classSchema = new ClassSchema(Fixture\DummyControllerWithIgnorevalidationAnnotation::class);
        static::assertTrue(isset($classSchema->getMethod('someAction')['tags']['ignorevalidation']));
    }

    /**
     * @test
     */
    public function classSchemaDetectsValidateAnnotationsModelProperties(): void
    {
        $classSchema = new ClassSchema(Fixture\DummyModelWithValidateAnnotation::class);

        static::assertSame(
            [
                [
                    'name' => 'StringLength',
                    'options' => [
                        'minimum' => '1',
                        'maximum' => '10',
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
                    'name' => 'TYPO3.CMS.Extbase.Tests.UnitDeprecated.Reflection.Fixture:DummyValidator',
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
        $classSchema = new ClassSchema(Fixture\DummyControllerWithValidateAnnotations::class);

        static::assertSame(
            [
                [
                    'name' => 'StringLength',
                    'options' => [
                        'minimum' => '1',
                        'maximum' => '10',
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
                    'name' => 'TYPO3.CMS.Extbase.Tests.UnitDeprecated.Reflection.Fixture:DummyValidator',
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
    public function classSchemaGenerationThrowsExceptionWithValidateAnnotationsForParamWithoutTypeHint(): void
    {
        $this->expectException(InvalidTypeHintException::class);
        $this->expectExceptionMessage('Missing type information for parameter "$fooParam" in TYPO3\CMS\Extbase\Tests\UnitDeprecated\Reflection\Fixture\DummyControllerWithValidateAnnotationWithoutParamTypeHint->methodWithValidateAnnotationsAction(): Either use an @param annotation or use a type hint.');
        $this->expectExceptionCode(1515075192);

        new ClassSchema(Fixture\DummyControllerWithValidateAnnotationWithoutParamTypeHint::class);
    }

    /**
     * @test
     */
    public function classSchemaGenerationThrowsExceptionWithValidateAnnotationsForMissingParam(): void
    {
        $this->expectException(InvalidValidationConfigurationException::class);
        $this->expectExceptionMessage('Invalid validate annotation in TYPO3\CMS\Extbase\Tests\UnitDeprecated\Reflection\Fixture\DummyControllerWithValidateAnnotationWithoutParam->methodWithValidateAnnotationsAction(): The following validators have been defined for missing param "$fooParam": NotEmpty, StringLength');
        $this->expectExceptionCode(1515073585);

        new ClassSchema(Fixture\DummyControllerWithValidateAnnotationWithoutParam::class);
    }
}
