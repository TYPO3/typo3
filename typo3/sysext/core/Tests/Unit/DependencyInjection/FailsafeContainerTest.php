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

namespace TYPO3\CMS\Core\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use stdClass as Service;
use TYPO3\CMS\Core\DependencyInjection\FailsafeContainer as Container;
use TYPO3\CMS\Core\DependencyInjection\ServiceProviderInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FailsafeContainerTest extends UnitTestCase
{
    #[Test]
    #[DoesNotPerformAssertions]
    public function canBeInstantiated(): void
    {
        new Container();
    }

    #[Test]
    public function withString(): void
    {
        $providerStub = self::createStub(ServiceProviderInterface::class);
        $providerStub->method('getExtensions')->willReturn([]);
        $providerStub->method('getFactories')->willReturn([
            'param' => static function () {
                return 'value';
            },
        ]);
        $container = new Container([$providerStub]);

        self::assertTrue($container->has('param'));
        self::assertEquals('value', $container->get('param'));
    }

    #[DataProvider('objectFactories')]
    #[Test]
    public function get(mixed $factory): void
    {
        $providerStub = self::createStub(ServiceProviderInterface::class);
        $providerStub->method('getExtensions')->willReturn([]);
        $providerStub->method('getFactories')->willReturn([
            'service' => $factory,
        ]);
        $container = new Container([$providerStub]);

        self::assertTrue($container->has('service'));
        self::assertInstanceOf(Service::class, $container->get('service'));
    }

    #[DataProvider('objectFactories')]
    #[Test]
    public function multipleGetServicesShouldBeEqual(mixed $factory): void
    {
        $providerStub = self::createStub(ServiceProviderInterface::class);
        $providerStub->method('getFactories')->willReturn(['service' => $factory]);
        // A factory can also be used as extension, as it's based on the same signature
        $providerStub->method('getExtensions')->willReturn(['extension' => $factory]);

        $container = new Container([$providerStub]);

        $serviceOne = $container->get('service');
        $serviceTwo = $container->get('service');

        $extensionOne = $container->get('extension');
        $extensionTwo = $container->get('extension');

        self::assertSame($serviceOne, $serviceTwo);
        self::assertSame($extensionOne, $extensionTwo);
    }

    #[Test]
    public function passesContainerAsParameter(): void
    {
        $providerStub = self::createStub(ServiceProviderInterface::class);
        $providerStub->method('getExtensions')->willReturn([]);
        $providerStub->method('getFactories')->willReturn([
            'service' => static function () {
                return new Service();
            },
            'container' => static function (ContainerInterface $container) {
                return $container;
            },
        ]);
        $container = new Container([$providerStub]);

        self::assertNotSame($container, $container->get('service'));
        self::assertSame($container, $container->get('container'));
    }

    #[Test]
    public function nullValueEntry(): void
    {
        $providerStub = self::createStub(ServiceProviderInterface::class);
        $providerStub->method('getExtensions')->willReturn([]);
        $providerStub->method('getFactories')->willReturn([
            'null' => static function () {
                return null;
            },
        ]);
        $container = new Container([$providerStub]);

        self::assertTrue($container->has('null'));
        self::assertNull($container->get('null'));
    }

    #[Test]
    public function nullValueEntryCallsFactoryOnlyOnce(): void
    {
        $calledCount = 0;
        $factory = static function () use (&$calledCount) {
            $calledCount++;
            return null;
        };
        $providerStub = self::createStub(ServiceProviderInterface::class);
        $providerStub->method('getExtensions')->willReturn([]);
        $providerStub->method('getFactories')->willReturn([
            'null' => $factory,
        ]);
        $container = new Container([$providerStub]);

        self::assertTrue($container->has('null'));
        self::assertNull($container->get('null'));
        self::assertTrue($container->has('null'));
        self::assertNull($container->get('null'));
        self::assertEquals(1, $calledCount);
    }

    #[Test]
    public function has(): void
    {
        $providerStub = self::createStub(ServiceProviderInterface::class);
        $providerStub->method('getExtensions')->willReturn([]);
        $providerStub->method('getFactories')->willReturn([
            'service' => static function () {
                return new Service();
            },
            'param' => static function () {
                return 'value';
            },
            'int' => static function () {
                return 2;
            },
            'bool' => static function () {
                return false;
            },
            'null' => static function () {
                return null;
            },
            '0' => static function () {
                return 0;
            },
        ]);
        $container = new Container([$providerStub]);

        self::assertTrue($container->has('param'));
        self::assertTrue($container->has('service'));
        self::assertTrue($container->has('int'));
        self::assertTrue($container->has('bool'));
        self::assertTrue($container->has('null'));
        self::assertFalse($container->has('non_existent'));
    }

    #[Test]
    public function defaultEntry(): void
    {
        $default = ['param' => 'value'];
        $container = new Container([], $default);

        self::assertSame('value', $container->get('param'));
    }

    #[Test]
    public function getValidatesKeyIsPresent(): void
    {
        $container = new Container();

        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessage('Container entry "foo" is not available.');
        $container->get('foo');
    }

    #[DataProvider('objectFactories')]
    #[Test]
    public function extension(mixed $factory): void
    {
        $providerStubA = self::createStub(ServiceProviderInterface::class);
        $providerStubA->method('getFactories')->willReturn(['service' => $factory]);
        $providerStubA->method('getExtensions')->willReturn([]);

        $providerStubB = self::createStub(ServiceProviderInterface::class);
        $providerStubB->method('getFactories')->willReturn([]);
        $providerStubB->method('getExtensions')->willReturn([
            'service' => static function (ContainerInterface $c, Service $s) {
                $s->value = 'value';
                return $s;
            },
        ]);
        $iterator = (static function () use ($providerStubA, $providerStubB): iterable {
            yield $providerStubA;
            yield $providerStubB;
        })();
        $container = new Container($iterator);

        self::assertSame('value', $container->get('service')->value);
    }

    #[DataProvider('objectFactories')]
    #[Test]
    public function extendingLaterProvider(mixed $factory): void
    {
        $providerStubA = self::createStub(ServiceProviderInterface::class);
        $providerStubA->method('getFactories')->willReturn(['service' => $factory]);
        $providerStubA->method('getExtensions')->willReturn([]);

        $providerStubB = self::createStub(ServiceProviderInterface::class);
        $providerStubB->method('getFactories')->willReturn([]);
        $providerStubB->method('getExtensions')->willReturn([
            'service' => static function (ContainerInterface $c, Service $s) {
                $s->value = 'value';
                return $s;
            },
        ]);
        $container = new Container([$providerStubB, $providerStubA]);

        self::assertSame('value', $container->get('service')->value);
    }

    #[DataProvider('objectFactories')]
    #[Test]
    public function extendingOwnFactory(mixed $factory): void
    {
        $providerStub = self::createStub(ServiceProviderInterface::class);
        $providerStub->method('getFactories')->willReturn(['service' => $factory]);
        $providerStub->method('getExtensions')->willReturn(
            [
                'service' => static function (ContainerInterface $c, Service $s) {
                    $s->value = 'value';
                    return $s;
                },
            ]
        );
        $container = new Container([$providerStub]);

        self::assertSame('value', $container->get('service')->value);
    }

    #[Test]
    public function extendingNonExistingFactory(): void
    {
        $providerStub = self::createStub(ServiceProviderInterface::class);
        $providerStub->method('getFactories')->willReturn([]);
        $providerStub->method('getExtensions')->willReturn([
            'service' => static function (ContainerInterface $c, ?Service $s = null) {
                if ($s === null) {
                    $s = new Service();
                }
                $s->value = 'value';
                return $s;
            },
        ]);
        $container = new Container([$providerStub]);

        self::assertSame('value', $container->get('service')->value);
    }

    #[DataProvider('objectFactories')]
    #[Test]
    public function multipleExtensions(mixed $factory): void
    {
        $providerStubA = self::createStub(ServiceProviderInterface::class);
        $providerStubA->method('getFactories')->willReturn(['service' => $factory]);
        $providerStubA->method('getExtensions')->willReturn([]);

        $providerStubB = self::createStub(ServiceProviderInterface::class);
        $providerStubB->method('getFactories')->willReturn([]);
        $providerStubB->method('getExtensions')->willReturn([
            'service' => static function (ContainerInterface $c, Service $s) {
                $s->value = '1';
                return $s;
            },
        ]);

        $providerStubC = self::createStub(ServiceProviderInterface::class);
        $providerStubC->method('getFactories')->willReturn([]);
        $providerStubC->method('getExtensions')->willReturn([
            'service' => static function (ContainerInterface $c, Service $s) {
                $s->value .= '2';
                return $s;
            },
        ]);
        $container = new Container([$providerStubA, $providerStubB, $providerStubC]);

        self::assertSame('12', $container->get('service')->value);
    }

    #[DataProvider('objectFactories')]
    #[Test]
    public function entryOverriding(mixed $factory): void
    {
        $providerStubA = self::createStub(ServiceProviderInterface::class);
        $providerStubA->method('getFactories')->willReturn(['service' => $factory]);
        $providerStubA->method('getExtensions')->willReturn([]);

        $providerStubB = self::createStub(ServiceProviderInterface::class);
        $providerStubB->method('getExtensions')->willReturn([]);
        $providerStubB->method('getFactories')->willReturn(['service' => static function () {
            return 'value';
        }]);

        $container = new Container([$providerStubA, $providerStubB]);

        self::assertNotInstanceOf(Service::class, $container->get('service'));
        self::assertEquals('value', $container->get('service'));
    }

    #[Test]
    public function cyclicDependency(): void
    {
        $providerStub = self::createStub(ServiceProviderInterface::class);
        $providerStub->method('getExtensions')->willReturn([]);
        $providerStub->method('getFactories')->willReturn([
            'A' => static function (ContainerInterface $container) {
                return $container->get('B');
            },
            'B' => static function (ContainerInterface $container) {
                return $container->get('A');
            },
        ]);

        $container = new Container([$providerStub]);

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('Container entry "A" is part of a cyclic dependency chain.');
        $container->get('A');
    }

    #[Test]
    public function cyclicDependencyRetrievedTwice(): void
    {
        $providerStub = self::createStub(ServiceProviderInterface::class);
        $providerStub->method('getExtensions')->willReturn([]);
        $providerStub->method('getFactories')->willReturn([
            'A' => static function (ContainerInterface $container) {
                return $container->get('B');
            },
            'B' => static function (ContainerInterface $container) {
                return $container->get('A');
            },
        ]);

        $container = new Container([$providerStub]);

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('Container entry "A" is part of a cyclic dependency chain.');
        try {
            $container->get('A');
        } catch (ContainerExceptionInterface $e) {
        }
        self::assertTrue($container->has('A'));
        $container->get('A');
    }

    #[Test]
    public function nullContainer(): void
    {
        $container = new Container();
        self::assertFalse($container->has('foo'));
    }

    #[Test]
    public function nullContainerWithDefaultEntries(): void
    {
        $container = new Container([], ['foo' => 'bar']);
        self::assertTrue($container->has('foo'));
    }

    public static function factory(): Service
    {
        return new Service();
    }

    /**
     * Provider for ServerProvider callables.
     * Either a closure, a static callable or invokable.
     */
    public static function objectFactories(): array
    {
        return [
            [
                // Static callback
                [self::class, 'factory'],
            ],
            [
                // Closure
                static function () {
                    return new Service();
                },
            ],
            [
                // Invokable
                new class {
                    public function __invoke(): Service
                    {
                        return new Service();
                    }
                },
            ],
            [
                // Non-static factory
                [
                    new class {
                        public function factory(): Service
                        {
                            return new Service();
                        }
                    },
                    'factory',
                ],
            ],
        ];
    }
}
