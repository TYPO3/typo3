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
        static::assertTrue($methodDefinition->isPublic());
        static::assertFalse($methodDefinition->isProtected());
        static::assertFalse($methodDefinition->isPrivate());

        $methodDefinition = $classSchema->getMethod('protectedMethod');
        static::assertFalse($methodDefinition->isPublic());
        static::assertTrue($methodDefinition->isProtected());
        static::assertFalse($methodDefinition->isPrivate());

        $methodDefinition = $classSchema->getMethod('privateMethod');
        static::assertFalse($methodDefinition->isPublic());
        static::assertFalse($methodDefinition->isProtected());
        static::assertTrue($methodDefinition->isPrivate());
    }

    /**
     * @test
     */
    public function classSchemaDetectsInjectMethods()
    {
        $classSchema = new ClassSchema(DummyClassWithAllTypesOfMethods::class);
        static::assertTrue($classSchema->hasInjectMethods());

        $methodDefinition = $classSchema->getMethod('injectSettings');
        static::assertFalse($methodDefinition->isInjectMethod());

        $methodDefinition = $classSchema->getMethod('injectMethodWithoutParam');
        static::assertFalse($methodDefinition->isInjectMethod());

        $methodDefinition = $classSchema->getMethod('injectMethodThatIsProtected');
        static::assertFalse($methodDefinition->isInjectMethod());

        $methodDefinition = $classSchema->getMethod('injectFoo');
        static::assertTrue($methodDefinition->isInjectMethod());

        $injectMethods = $classSchema->getInjectMethods();
        static::assertArrayHasKey('injectFoo', $injectMethods);
    }

    /**
     * @test
     */
    public function classSchemaDetectsStaticMethods()
    {
        static::assertTrue(
            (new ClassSchema(DummyClassWithAllTypesOfMethods::class))
            ->getMethod('staticMethod')
            ->isStatic()
        );
    }
}
