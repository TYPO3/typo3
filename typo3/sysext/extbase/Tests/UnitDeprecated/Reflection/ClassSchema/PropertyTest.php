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
use TYPO3\CMS\Extbase\Tests\UnitDeprecated\Reflection\Fixture\DummyModel;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PropertyTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[Test]
    public function classSchemaDetectsValidateAnnotationsModelProperties(): void
    {
        $property = (new ClassSchema(DummyModel::class))
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
    public function classSchemaDetectsValidateAttributeModelProperties(): void
    {
        $property = (new ClassSchema(DummyModel::class))
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
    public function classSchemaDetectsValidateAttributeOnPromotedModelProperties(): void
    {
        $property = (new ClassSchema(DummyModel::class))
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
