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

use TYPO3\CMS\Core\Cache\CacheManager;
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
    /**
     * @test
     */
    public function getClassSchemaThrowsExceptionIfClassIsNotFound(): void
    {
        $this->expectException(UnknownClassException::class);
        $this->expectExceptionCode(1278450972);
        $this->expectExceptionMessage('Class Foo\Bar\Not\Existing does not exist. Reflection failed.');
        $reflectionService = new ReflectionService();
        $reflectionService->getClassSchema('Foo\Bar\Not\Existing');
    }

    /**
     * @test
     */
    public function getClassSchemaThrowsExceptionIfTypeHintedClassWasNotFound(): void
    {
        $this->expectException(UnknownClassException::class);
        $this->expectExceptionCode(1278450972);
        $this->expectExceptionMessage('Class Foo\Bar\Not\Found does not exist. Reflection failed.');
        $reflectionService = new ReflectionService();
        $reflectionService->getClassSchema(DummyClassWithInvalidTypeHint::class);
    }

    /**
     * @test
     */
    public function reflectionServiceCanBeSerializedAndUnserialized(): void
    {
        $class = new class() {
        };

        $reflectionService = new ReflectionService();
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
        $cacheManager = $this->prophesize(CacheManager::class);
        $cacheManager->getCache('extbase')->willReturn(new NullFrontend('extbase'));

        $class = new class() {
        };

        $reflectionService = new ReflectionService($cacheManager->reveal());
        $serialized = serialize($reflectionService);
        unset($reflectionService);

        $reflectionService = unserialize($serialized, ['allowed_classes' => [ReflectionService::class]]);

        self::assertInstanceOf(ReflectionService::class, $reflectionService);
        self::assertInstanceOf(ClassSchema::class, $reflectionService->getClassSchema($class));
    }

    /**
     * @test
     */
    public function reflectionServiceIsResetDuringWakeUp(): void
    {
        $insecureString = file_get_contents(__DIR__ . '/Fixture/InsecureSerializedReflectionService.txt');
        $reflectionService = unserialize($insecureString);

        $reflectionClass = new \ReflectionClass($reflectionService);
        $classSchemaProperty = $reflectionClass->getProperty('classSchemata');
        $classSchemaProperty->setAccessible(true);

        self::assertSame([], $classSchemaProperty->getValue($reflectionService));
    }
}
