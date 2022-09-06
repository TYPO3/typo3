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

namespace TYPO3\CMS\Extbase\Tests\Unit\Reflection;

use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Extbase\Reflection\ClassSchema;
use TYPO3\CMS\Extbase\Reflection\Exception\UnknownClassException;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyClassWithInvalidTypeHint;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 * @see test for reflection
 * @link second test for reflection
 * @link second test for reflection with second value
 */
class ReflectionServiceTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function getClassSchemaThrowsExceptionIfClassIsNotFound(): void
    {
        $this->expectException(UnknownClassException::class);
        $this->expectExceptionCode(1278450972);
        $this->expectExceptionMessageMatches('/^.*Reflection failed\.$/');
        $reflectionService = new ReflectionService(new NullFrontend('extbase'), 'ClassSchemata');
        $reflectionService->getClassSchema('Foo\Bar\Not\Existing');
    }

    /**
     * @test
     */
    public function getClassSchemaThrowsExceptionIfTypeHintedClassWasNotFound(): void
    {
        $this->expectException(UnknownClassException::class);
        $this->expectExceptionCode(1278450972);
        $this->expectExceptionMessageMatches('/^.*Reflection failed\.$/');
        $reflectionService = new ReflectionService(new NullFrontend('extbase'), 'ClassSchemata');
        $reflectionService->getClassSchema(DummyClassWithInvalidTypeHint::class);
    }

    /**
     * @test
     */
    public function reflectionServiceCanBeSerializedAndUnserialized(): void
    {
        $class = new class() {
        };

        $reflectionService = new ReflectionService(new NullFrontend('extbase'), 'ClassSchemata');
        $serialized = serialize($reflectionService);
        unset($reflectionService);

        $reflectionService = unserialize($serialized, ['allowed_classes' => [ReflectionService::class]]);

        self::assertInstanceOf(ReflectionService::class, $reflectionService);
        self::assertInstanceOf(ClassSchema::class, $reflectionService->getClassSchema($class));
    }

    /**
     * @test
     */
    public function reflectionServiceCanBeSerializedAndUnserializedWithCacheManager(): void
    {
        $class = new class() {
        };

        $reflectionService = new ReflectionService(new NullFrontend('extbase'), 'ClassSchemata');
        $serialized = serialize($reflectionService);
        unset($reflectionService);

        $reflectionService = unserialize($serialized, ['allowed_classes' => [ReflectionService::class]]);

        self::assertInstanceOf(ReflectionService::class, $reflectionService);
        self::assertInstanceOf(ClassSchema::class, $reflectionService->getClassSchema($class));
    }
}
