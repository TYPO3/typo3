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

namespace TYPO3\CMS\Extbase\Tests\UnitDeprecated\Reflection\ClassSchema;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Reflection\ClassSchema;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\Validation\Validator\DummyValidator;
use TYPO3\CMS\Extbase\Tests\UnitDeprecated\Reflection\Fixture\DummyController;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class MethodParameterTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[Test]
    public function classSchemaDetectsValidateAnnotationsOfControllerActions(): void
    {
        $classSchema = new ClassSchema(DummyController::class);
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
            $classSchema->getMethod('methodWithValidateAttributesAction')->getParameter('fooParam')->getValidators()
        );
    }
}
