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

use TYPO3\CMS\Extbase\Reflection\ClassSchema;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyClassWithAllTypesOfMethods;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class TYPO3\CMS\Extbase\Tests\Unit\Reflection\MethodTest
 */
class MethodTest extends UnitTestCase
{
    /**
     * @test
     */
    public function classSchemaDetectsMethodVisibility()
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

    /**
     * @test
     */
    public function classSchemaDetectsInjectMethods()
    {
        $classSchema = new ClassSchema(DummyClassWithAllTypesOfMethods::class);
        self::assertTrue($classSchema->hasInjectMethods());

        $methodDefinition = $classSchema->getMethod('injectSettings');
        self::assertFalse($methodDefinition->isInjectMethod());

        $methodDefinition = $classSchema->getMethod('injectMethodWithoutParam');
        self::assertFalse($methodDefinition->isInjectMethod());

        $methodDefinition = $classSchema->getMethod('injectMethodThatIsProtected');
        self::assertFalse($methodDefinition->isInjectMethod());

        $methodDefinition = $classSchema->getMethod('injectFoo');
        self::assertTrue($methodDefinition->isInjectMethod());

        $injectMethods = $classSchema->getInjectMethods();
        self::assertArrayHasKey('injectFoo', $injectMethods);
    }

    /**
     * @test
     */
    public function classSchemaDetectsStaticMethods()
    {
        self::assertTrue(
            (new ClassSchema(DummyClassWithAllTypesOfMethods::class))
            ->getMethod('staticMethod')
            ->isStatic()
        );
    }
}
