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

namespace TYPO3\CMS\Extbase\Tests\UnitDeprecated\Reflection;

use TYPO3\CMS\Extbase\Reflection\ClassSchema;
use TYPO3\CMS\Extbase\Tests\UnitDeprecated\Reflection\Fixture\DummyClassWithInjectDoctrineAnnotation;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ClassSchemaTest extends UnitTestCase
{
    /**
     * @test
     */
    public function classSchemaDetectsInjectProperties(): void
    {
        $classSchema = new ClassSchema(DummyClassWithInjectDoctrineAnnotation::class);
        self::assertTrue($classSchema->hasInjectProperties());

        $injectProperties = $classSchema->getInjectProperties();
        self::assertArrayHasKey('propertyWithFullQualifiedClassName', $injectProperties);
        self::assertSame(DummyClassWithInjectDoctrineAnnotation::class, $injectProperties['propertyWithFullQualifiedClassName']->getType());

        self::assertArrayHasKey('propertyWithRelativeClassName', $injectProperties);
        self::assertSame(DummyClassWithInjectDoctrineAnnotation::class, $injectProperties['propertyWithRelativeClassName']->getType());

        self::assertArrayHasKey('propertyWithImportedClassName', $injectProperties);
        self::assertSame(self::class, $injectProperties['propertyWithImportedClassName']->getType());

        self::assertArrayHasKey('propertyWithImportedAndAliasedClassName', $injectProperties);
        self::assertSame(self::class, $injectProperties['propertyWithImportedAndAliasedClassName']->getType());
    }
}
