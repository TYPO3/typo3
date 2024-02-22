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
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyClassWithAllTypesOfMethods;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class TYPO3\CMS\Extbase\Tests\Unit\Reflection\MethodTest
 */
final class MethodTest extends UnitTestCase
{
    #[Test]
    public function classSchemaDetectsMethodVisibility(): void
    {
        $classSchema = new ClassSchema(DummyClassWithAllTypesOfMethods::class);

        $methodDefinition = $classSchema->getMethod('publicMethod');
        self::assertTrue($methodDefinition->isPublic());
        self::assertFalse($methodDefinition->isProtected());
        self::assertFalse($methodDefinition->isPrivate());

        $methodDefinition = $classSchema->getMethod('protectedMethod');
        self::assertFalse($methodDefinition->isPublic());
        self::assertTrue($methodDefinition->isProtected());
        self::assertFalse($methodDefinition->isPrivate());

        $methodDefinition = $classSchema->getMethod('privateMethod');
        self::assertFalse($methodDefinition->isPublic());
        self::assertFalse($methodDefinition->isProtected());
        self::assertTrue($methodDefinition->isPrivate());
    }
}
