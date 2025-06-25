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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Reflection\ClassSchema;
use TYPO3\CMS\Extbase\Reflection\ClassSchema\Exception\NoSuchMethodParameterException;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyClassWithAllTypesOfMethods;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyClassWithIgnoreValidationAttribute;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyClassWithIgnoreValidationDoctrineAnnotation;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyController;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyControllerWithIgnoreValidationAttribute;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyControllerWithIgnoreValidationDoctrineAnnotation;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class MethodParameterTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[Test]
    public function classSchemaDetectsMandatoryParams(): void
    {
        self::assertFalse(
            (new ClassSchema(DummyClassWithAllTypesOfMethods::class))
            ->getMethod('methodWithMandatoryParam')
            ->getParameter('param')
            ->isOptional()
        );
    }

    #[Test]
    public function classSchemaDetectsDefaultValueParams(): void
    {
        self::assertSame(
            'foo',
            (new ClassSchema(DummyClassWithAllTypesOfMethods::class))
                ->getMethod('methodWithDefaultValueParam')
                ->getParameter('param')
                ->getDefaultValue()
        );
    }

    #[Test]
    public function classSchemaDetectsParamTypeFromTypeHint(): void
    {
        self::assertSame(
            'string',
            (new ClassSchema(DummyClassWithAllTypesOfMethods::class))
                ->getMethod('methodWithTypeHintedParam')
                ->getParameter('param')
                ->getType()
        );
    }

    #[Test]
    public function classSchemaDetectsIgnoreValidationAnnotation(): void
    {
        $classSchemaMethod = (new ClassSchema(DummyControllerWithIgnoreValidationDoctrineAnnotation::class))
            ->getMethod('someAction');
        self::assertTrue($classSchemaMethod->getParameter('foo')->ignoreValidation());
        self::assertTrue($classSchemaMethod->getParameter('bar')->ignoreValidation());

        $this->expectException(NoSuchMethodParameterException::class);
        $classSchemaMethod->getParameter('baz')->ignoreValidation();
    }

    #[Test]
    public function classSchemaDetectsIgnoreValidationAttribute(): void
    {
        $classSchemaMethod = (new ClassSchema(DummyControllerWithIgnoreValidationAttribute::class))
            ->getMethod('someAction');
        self::assertTrue($classSchemaMethod->getParameter('foo')->ignoreValidation());
        self::assertTrue($classSchemaMethod->getParameter('bar')->ignoreValidation());

        $this->expectException(NoSuchMethodParameterException::class);
        $classSchemaMethod->getParameter('baz')->ignoreValidation();
    }

    #[Test]
    public function classSchemaIgnoresIgnoreValidationAnnotationOnNonControllerClasses(): void
    {
        $classSchemaMethod = (new ClassSchema(DummyClassWithIgnoreValidationDoctrineAnnotation::class))
            ->getMethod('someAction');
        self::assertFalse($classSchemaMethod->getParameter('foo')->ignoreValidation());
        self::assertFalse($classSchemaMethod->getParameter('bar')->ignoreValidation());

        $this->expectException(NoSuchMethodParameterException::class);
        $classSchemaMethod->getParameter('baz')->ignoreValidation();
    }

    #[Test]
    public function classSchemaIgnoresIgnoreValidationAttributeOnNonControllerClasses(): void
    {
        $classSchemaMethod = (new ClassSchema(DummyClassWithIgnoreValidationAttribute::class))
            ->getMethod('someAction');
        self::assertFalse($classSchemaMethod->getParameter('foo')->ignoreValidation());
        self::assertFalse($classSchemaMethod->getParameter('bar')->ignoreValidation());

        $this->expectException(NoSuchMethodParameterException::class);
        $classSchemaMethod->getParameter('baz')->ignoreValidation();
    }

    #[Test]
    public function classSchemaDetectsValidateAnnotationsOfControllerActions(): void
    {
        $classSchema = new ClassSchema(DummyController::class);
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
            $classSchema->getMethod('methodWithValidateAnnotationsAction')->getParameter('fooParam')->getValidators()
        );
    }

    #[Test]
    public function classSchemaDetectsValidateAttributesOfControllerActions(): void
    {
        $classSchema = new ClassSchema(DummyController::class);
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
            $classSchema->getMethod('methodWithValidateAttributesAction')->getParameter('fooParam')->getValidators()
        );
    }
}
