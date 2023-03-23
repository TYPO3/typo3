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

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use stdClass as Service;
use TYPO3\CMS\Core\DependencyInjection\FailsafeContainer as Container;
use TYPO3\CMS\Core\DependencyInjection\ServiceProviderInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class FailsafeContainerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function implementsInterface(): void
    {
        self::assertInstanceOf(ContainerInterface::class, new Container());
    }

    /**
     * @test
     */
    public function withString(): void
    {
        $providerMock = $this->createMock(ServiceProviderInterface::class);
        $providerMock->method('getExtensions')->willReturn([]);
        $providerMock->method('getFactories')->willReturn([
            'param' => static function () {
                return 'value';
            },
        ]);
        $container = new Container([$providerMock]);

        self::assertTrue($container->has('param'));
        self::assertEquals('value', $container->get('param'));
    }

    /**
     * @test
     * @dataProvider objectFactories
     */
    public function get(mixed $factory): void
    {
        $providerMock = $this->createMock(ServiceProviderInterface::class);
        $providerMock->method('getExtensions')->willReturn([]);
        $providerMock->method('getFactories')->willReturn([
            'service' => $factory,
        ]);
        $container = new Container([$providerMock]);

        self::assertTrue($container->has('service'));
        self::assertInstanceOf(Service::class, $container->get('service'));
    }

    /**
     * @test
     * @dataProvider objectFactories
     */
    public function multipleGetServicesShouldBeEqual(mixed $factory): void
    {
        $providerMock = $this->createMock(ServiceProviderInterface::class);
        $providerMock->method('getFactories')->willReturn(['service' => $factory]);
        // A factory can also be used as extension, as it's based on the same signature
        $providerMock->method('getExtensions')->willReturn(['extension' => $factory]);

        $container = new Container([$providerMock]);

        $serviceOne = $container->get('service');
        $serviceTwo = $container->get('service');

        $extensionOne = $container->get('extension');
        $extensionTwo = $container->get('extension');

        self::assertSame($serviceOne, $serviceTwo);
        self::assertSame($extensionOne, $extensionTwo);
    }

    /**
     * @test
     */
    public function passesContainerAsParameter(): void
    {
        $providerMock = $this->createMock(ServiceProviderInterface::class);
        $providerMock->method('getExtensions')->willReturn([]);
        $providerMock->method('getFactories')->willReturn([
            'service' => static function () {
                return new Service();
            },
            'container' => static function (ContainerInterface $container) {
                return $container;
            },
        ]);
        $container = new Container([$providerMock]);

        self::assertNotSame($container, $container->get('service'));
        self::assertSame($container, $container->get('container'));
    }

    /**
     * @test
     */
    public function nullValueEntry(): void
    {
        $providerMock = $this->createMock(ServiceProviderInterface::class);
        $providerMock->method('getExtensions')->willReturn([]);
        $providerMock->method('getFactories')->willReturn([
            'null' => static function () {
                return null;
            },
        ]);
        $container = new Container([$providerMock]);

        self::assertTrue($container->has('null'));
        self::assertNull($container->get('null'));
    }

    /**
     * @test
     */
    public function nullValueEntryCallsFactoryOnlyOnce(): void
    {
        $calledCount = 0;
        $factory = static function () use (&$calledCount) {
            $calledCount++;
            return null;
        };
        $providerMock = $this->createMock(ServiceProviderInterface::class);
        $providerMock->method('getExtensions')->willReturn([]);
        $providerMock->method('getFactories')->willReturn([
            'null' => $factory,
        ]);
        $container = new Container([$providerMock]);

        self::assertTrue($container->has('null'));
        self::assertNull($container->get('null'));
        self::assertTrue($container->has('null'));
        self::assertNull($container->get('null'));
        self::assertEquals(1, $calledCount);
    }

    /**
     * @test
     */
    public function has(): void
    {
        $providerMock = $this->createMock(ServiceProviderInterface::class);
        $providerMock->method('getExtensions')->willReturn([]);
        $providerMock->method('getFactories')->willReturn([
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
        $container = new Container([$providerMock]);

        self::assertTrue($container->has('param'));
        self::assertTrue($container->has('service'));
        self::assertTrue($container->has('int'));
        self::assertTrue($container->has('bool'));
        self::assertTrue($container->has('null'));
        self::assertFalse($container->has('non_existent'));
    }

    /**
     * @test
     */
    public function defaultEntry(): void
    {
        $default = ['param' => 'value'];
        $container = new Container([], $default);

        self::assertSame('value', $container->get('param'));
    }

    /**
     * @test
     */
    public function getValidatesKeyIsPresent(): void
    {
        $container = new Container();

        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessage('Container entry "foo" is not available.');
        $container->get('foo');
    }

    /**
     * @test
     * @dataProvider objectFactories
     */
    public function extension(mixed $factory): void
    {
        $providerMockA = $this->createMock(ServiceProviderInterface::class);
        $providerMockA->method('getFactories')->willReturn(['service' => $factory]);
        $providerMockA->method('getExtensions')->willReturn([]);

        $providerMockB = $this->createMock(ServiceProviderInterface::class);
        $providerMockB->method('getFactories')->willReturn([]);
        $providerMockB->method('getExtensions')->willReturn([
            'service' => static function (ContainerInterface $c, Service $s) {
                $s->value = 'value';
                return $s;
            },
        ]);
        $iterator = (static function () use ($providerMockA, $providerMockB): iterable {
            yield $providerMockA;
            yield $providerMockB;
        })();
        $container = new Container($iterator);

        self::assertSame('value', $container->get('service')->value);
    }

    /**
     * @test
     * @dataProvider objectFactories
     */
    public function extendingLaterProvider(mixed $factory): void
    {
        $providerMockA = $this->createMock(ServiceProviderInterface::class);
        $providerMockA->method('getFactories')->willReturn(['service' => $factory]);
        $providerMockA->method('getExtensions')->willReturn([]);

        $providerMockB = $this->createMock(ServiceProviderInterface::class);
        $providerMockB->method('getFactories')->willReturn([]);
        $providerMockB->method('getExtensions')->willReturn([
            'service' => static function (ContainerInterface $c, Service $s) {
                $s->value = 'value';
                return $s;
            },
        ]);
        $container = new Container([$providerMockB, $providerMockA]);

        self::assertSame('value', $container->get('service')->value);
    }

    /**
     * @test
     * @dataProvider objectFactories
     */
    public function extendingOwnFactory(mixed $factory): void
    {
        $providerMock = $this->createMock(ServiceProviderInterface::class);
        $providerMock->method('getFactories')->willReturn(['service' => $factory]);
        $providerMock->method('getExtensions')->willReturn(
            [
                'service' => static function (ContainerInterface $c, Service $s) {
                    $s->value = 'value';
                    return $s;
                },
            ]
        );
        $container = new Container([$providerMock]);

        self::assertSame('value', $container->get('service')->value);
    }

    /**
     * @test
     */
    public function extendingNonExistingFactory(): void
    {
        $providerMock = $this->createMock(ServiceProviderInterface::class);
        $providerMock->method('getFactories')->willReturn([]);
        $providerMock->method('getExtensions')->willReturn([
            'service' => static function (ContainerInterface $c, Service $s = null) {
                if ($s === null) {
                    $s = new Service();
                }
                $s->value = 'value';
                return $s;
            },
        ]);
        $container = new Container([$providerMock]);

        self::assertSame('value', $container->get('service')->value);
    }

    /**
     * @test
     * @dataProvider objectFactories
     */
    public function multipleExtensions(mixed $factory): void
    {
        $providerMockA = $this->createMock(ServiceProviderInterface::class);
        $providerMockA->method('getFactories')->willReturn(['service' => $factory]);
        $providerMockA->method('getExtensions')->willReturn([]);

        $providerMockB = $this->createMock(ServiceProviderInterface::class);
        $providerMockB->method('getFactories')->willReturn([]);
        $providerMockB->method('getExtensions')->willReturn([
            'service' => static function (ContainerInterface $c, Service $s) {
                $s->value = '1';
                return $s;
            },
        ]);

        $providerMockC = $this->createMock(ServiceProviderInterface::class);
        $providerMockC->method('getFactories')->willReturn([]);
        $providerMockC->method('getExtensions')->willReturn([
            'service' => static function (ContainerInterface $c, Service $s) {
                $s->value .= '2';
                return $s;
            },
        ]);
        $container = new Container([$providerMockA, $providerMockB, $providerMockC]);

        self::assertSame('12', $container->get('service')->value);
    }

    /**
     * @test
     * @dataProvider objectFactories
     */
    public function entryOverriding(mixed $factory): void
    {
        $providerMockA = $this->createMock(ServiceProviderInterface::class);
        $providerMockA->method('getFactories')->willReturn(['service' => $factory]);
        $providerMockA->method('getExtensions')->willReturn([]);

        $providerMockB = $this->createMock(ServiceProviderInterface::class);
        $providerMockB->method('getExtensions')->willReturn([]);
        $providerMockB->method('getFactories')->willReturn(['service' => static function () {
            return 'value';
        }]);

        $container = new Container([$providerMockA, $providerMockB]);

        self::assertNotInstanceOf(Service::class, $container->get('service'));
        self::assertEquals('value', $container->get('service'));
    }

    /**
     * @test
     */
    public function cyclicDependency(): void
    {
        $providerMock = $this->createMock(ServiceProviderInterface::class);
        $providerMock->method('getExtensions')->willReturn([]);
        $providerMock->method('getFactories')->willReturn([
            'A' => static function (ContainerInterface $container) {
                return $container->get('B');
            },
            'B' => static function (ContainerInterface $container) {
                return $container->get('A');
            },
        ]);

        $container = new Container([$providerMock]);

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('Container entry "A" is part of a cyclic dependency chain.');
        $container->get('A');
    }

    /**
     * @test
     */
    public function cyclicDependencyRetrievedTwice(): void
    {
        $providerMock = $this->createMock(ServiceProviderInterface::class);
        $providerMock->method('getExtensions')->willReturn([]);
        $providerMock->method('getFactories')->willReturn([
            'A' => static function (ContainerInterface $container) {
                return $container->get('B');
            },
            'B' => static function (ContainerInterface $container) {
                return $container->get('A');
            },
        ]);

        $container = new Container([$providerMock]);

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('Container entry "A" is part of a cyclic dependency chain.');
        try {
            $container->get('A');
        } catch (ContainerExceptionInterface $e) {
        }
        self::assertTrue($container->has('A'));
        $container->get('A');
    }

    /**
     * @test
     */
    public function nullContainer(): void
    {
        $container = new Container();
        self::assertFalse($container->has('foo'));
    }

    /**
     * @test
     */
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
                new class () {
                    public function __invoke(): Service
                    {
                        return new Service();
                    }
                },
            ],
            [
                // Non-static factory
                [
                    new class () {
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
