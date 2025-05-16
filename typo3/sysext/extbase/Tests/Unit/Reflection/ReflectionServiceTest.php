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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Reflection\Exception\UnknownClassException;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyClassWithInvalidTypeHint;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ReflectionServiceTest extends UnitTestCase
{
    #[Test]
    public function getClassSchemaThrowsExceptionIfClassIsNotFound(): void
    {
        $this->expectException(UnknownClassException::class);
        $this->expectExceptionCode(1278450972);
        $this->expectExceptionMessageMatches('/^.*Reflection failed\.$/');
        $reflectionService = new ReflectionService(new NullFrontend('extbase'), 'ClassSchemata');
        $reflectionService->getClassSchema('Foo\Bar\Not\Existing');
    }

    #[Test]
    public function getClassSchemaThrowsExceptionIfTypeHintedClassWasNotFound(): void
    {
        $this->expectException(UnknownClassException::class);
        $this->expectExceptionCode(1278450972);
        $this->expectExceptionMessageMatches('/^.*Reflection failed\.$/');
        $reflectionService = new ReflectionService(new NullFrontend('extbase'), 'ClassSchemata');
        $reflectionService->getClassSchema(DummyClassWithInvalidTypeHint::class);
    }

    #[Test]
    public function reflectionServiceCanBeSerializedAndUnserialized(): void
    {
        $reflectionService = new ReflectionService(new NullFrontend('extbase'), 'ClassSchemata');
        $serialized = serialize($reflectionService);
        unset($reflectionService);
        $reflectionService = unserialize($serialized, ['allowed_classes' => [ReflectionService::class]]);
        self::assertInstanceOf(ReflectionService::class, $reflectionService);
    }

    #[Test]
    public function getRawEntityPropertyValueReadsNonPublicProperty(): void
    {
        $entity = new class extends AbstractEntity {
            private string $secret = 'value';

            public function getSecret(): string
            {
                return $this->secret;
            }
        };
        $reflectionService = new ReflectionService(new NullFrontend('extbase'), 'ClassSchemata');

        self::assertSame('value', $reflectionService->getRawEntityPropertyValue($entity, 'secret'));
    }

    #[Test]
    public function getRawEntityPropertyValueReturnsNullForUninitializedOrUnknownProperty(): void
    {
        $entity = new class extends AbstractEntity {
            protected string $uninitialized;
        };
        $reflectionService = new ReflectionService(new NullFrontend('extbase'), 'ClassSchemata');

        self::assertNull($reflectionService->getRawEntityPropertyValue($entity, 'uninitialized'));
        self::assertNull($reflectionService->getRawEntityPropertyValue($entity, 'unknown'));
    }

    #[Test]
    public function virtualPropertyIsIgnored(): void
    {
        $entity = new class extends AbstractEntity {
            public string $virtual {
                get => 'computed';
                set(string $value) {
                }
            }
        };
        $reflectionService = new ReflectionService(new NullFrontend('extbase'), 'ClassSchemata');

        $reflectionService->setRawEntityPropertyValue($entity, 'virtual', 'value');

        self::assertFalse($reflectionService->hasEntityProperty($entity, 'virtual'));
        self::assertNull($reflectionService->getRawEntityPropertyValue($entity, 'virtual'));
    }

    #[Test]
    public function getRawEntityPropertyValueBypassesGetHook(): void
    {
        $entity = new class extends AbstractEntity {
            public string $title = 'raw' {
                get => strtoupper($this->title);
            }
        };
        $reflectionService = new ReflectionService(new NullFrontend('extbase'), 'ClassSchemata');

        self::assertSame('raw', $reflectionService->getRawEntityPropertyValue($entity, 'title'));
    }

    #[Test]
    public function setRawEntityPropertyValueBypassesSetHook(): void
    {
        $entity = new class extends AbstractEntity {
            public string $title = '' {
                set(string $value) {
                    $this->title = strtoupper($value);
                }
            }
        };
        $reflectionService = new ReflectionService(new NullFrontend('extbase'), 'ClassSchemata');

        $reflectionService->setRawEntityPropertyValue($entity, 'title', 'raw');

        self::assertSame('raw', $entity->title);
    }

    #[Test]
    public function getRawEntityPropertyValueReturnsValueOfRealInstanceForUninitializedLazyProxy(): void
    {
        // Given: an uninitialized native lazy proxy of an entity with a protected typed property
        $realInstance = new class extends AbstractEntity {
            protected string $username = 'real';
        };
        $reflectionClass = new \ReflectionClass($realInstance);
        $proxy = $reflectionClass->newLazyProxy(static fn(): object => $realInstance);
        self::assertTrue($reflectionClass->isUninitializedLazyObject($proxy)); // precondition
        $reflectionService = new ReflectionService(new NullFrontend('extbase'), 'ClassSchemata');

        // When: the raw value is read through the reflection service
        $value = $reflectionService->getRawEntityPropertyValue($proxy, 'username');

        // Then: it is the value of the real instance
        self::assertSame('real', $value);
    }

    #[Test]
    public function hasEntityPropertyAndSetRawEntityPropertyValueWorkOnUninitializedLazyProxy(): void
    {
        // Given: an uninitialized native lazy proxy of an entity with a protected typed property
        $realInstance = new class extends AbstractEntity {
            protected string $username = 'real';

            public function getUsername(): string
            {
                return $this->username;
            }
        };
        $reflectionClass = new \ReflectionClass($realInstance);
        $proxy = $reflectionClass->newLazyProxy(static fn(): object => $realInstance);
        $reflectionService = new ReflectionService(new NullFrontend('extbase'), 'ClassSchemata');

        // When: the property is checked and written through the reflection service
        $hasProperty = $reflectionService->hasEntityProperty($proxy, 'username');
        $reflectionService->setRawEntityPropertyValue($proxy, 'username', 'changed');

        // Then: the property exists and the value is written to the real instance
        self::assertTrue($hasProperty);
        self::assertSame('changed', $realInstance->getUsername());
        self::assertSame('changed', $reflectionService->getRawEntityPropertyValue($proxy, 'username'));
    }

    #[Test]
    public function uidSetWithoutLazyInitializationIsReadWithoutInitializingTheProxy(): void
    {
        // Given: an uninitialized native lazy proxy whose uid was set without initializing it
        $realInstance = new class extends AbstractEntity {
            protected string $username = 'real';
        };
        $reflectionClass = new \ReflectionClass($realInstance);
        $proxy = $reflectionClass->newLazyProxy(static fn(): object => $realInstance);
        $reflectionClass->getProperty('uid')->setRawValueWithoutLazyInitialization($proxy, 42);

        $reflectionService = new ReflectionService(new NullFrontend('extbase'), 'ClassSchemata');

        // When: the uid is read through the reflection service
        $uid = $reflectionService->getRawEntityPropertyValue($proxy, 'uid');

        // Then: the uid is known and the proxy is still uninitialized
        self::assertSame(42, $uid);
        self::assertTrue($reflectionClass->isUninitializedLazyObject($proxy));
    }

    #[Test]
    public function getRawEntityPropertyValueReturnsValueOnRepeatedReadsOfLazyProxy(): void
    {
        // Given: a native lazy proxy that was read once, so it is initialized
        $realInstance = new class extends AbstractEntity {
            protected string $username = 'real';
        };
        $reflectionClass = new \ReflectionClass($realInstance);
        $proxy = $reflectionClass->newLazyProxy(static fn(): object => $realInstance);
        $reflectionService = new ReflectionService(new NullFrontend('extbase'), 'ClassSchemata');
        $reflectionService->getRawEntityPropertyValue($proxy, 'username');
        self::assertFalse($reflectionClass->isUninitializedLazyObject($proxy)); // precondition

        // When: the same property is read again through the (cached) reflection property
        $value = $reflectionService->getRawEntityPropertyValue($proxy, 'username');

        // Then: it still is the value of the real instance, not null
        self::assertSame('real', $value);
    }

    #[Test]
    public function setRawEntityPropertyValueWritesToRealInstanceOfInitializedLazyProxy(): void
    {
        // Given: a native lazy proxy that was read once, so it is initialized and the reflection property is cached
        $realInstance = new class extends AbstractEntity {
            protected string $username = 'real';

            public function getUsername(): string
            {
                return $this->username;
            }
        };
        $reflectionClass = new \ReflectionClass($realInstance);
        $proxy = $reflectionClass->newLazyProxy(static fn(): object => $realInstance);
        $reflectionService = new ReflectionService(new NullFrontend('extbase'), 'ClassSchemata');
        $reflectionService->getRawEntityPropertyValue($proxy, 'username');
        self::assertFalse($reflectionClass->isUninitializedLazyObject($proxy)); // precondition

        // When: the property is written through the (cached) reflection property
        $reflectionService->setRawEntityPropertyValue($proxy, 'username', 'changed');

        // Then: the real instance holds the new value and reading through the proxy returns it
        self::assertSame('changed', $realInstance->getUsername());
        self::assertSame('changed', $reflectionService->getRawEntityPropertyValue($proxy, 'username'));
    }
}
