<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Tests\Unit\DependencyInjection;

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

use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use stdClass as Service;
use TYPO3\CMS\Core\DependencyInjection\FailsafeContainer as Container;
use TYPO3\CMS\Core\DependencyInjection\ServiceProviderInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for the FailsafeContainer class
 */
class FailsafeContainerTest extends UnitTestCase
{
    /**
     * @var ObjectProphecy
     */
    protected $providerProphecy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->providerProphecy = $this->createServiceProviderProphecy();
    }

    protected function createServiceProviderProphecy(array $extensions = [], array $factories = []): ObjectProphecy
    {
        $prophecy = $this->prophesize();
        $prophecy->willImplement(ServiceProviderInterface::class);
        $prophecy->getFactories()->willReturn($extensions);
        $prophecy->getExtensions()->willReturn($factories);
        return $prophecy;
    }

    public function testImplementsInterface(): void
    {
        self::assertInstanceOf(ContainerInterface::class, new Container);
    }

    public function testWithString(): void
    {
        $this->providerProphecy->getFactories()->willReturn([
            'param' => function () {
                return 'value';
            }
        ]);
        $container = new Container([$this->providerProphecy->reveal()]);

        self::assertTrue($container->has('param'));
        self::assertEquals('value', $container->get('param'));
    }

    /**
     * @dataProvider objectFactories
     * @param mixed $factory
     */
    public function testGet($factory): void
    {
        $this->providerProphecy->getFactories()->willReturn([
            'service' => $factory,
        ]);
        $container = new Container([$this->providerProphecy->reveal()]);

        self::assertTrue($container->has('service'));
        self::assertInstanceOf(Service::class, $container->get('service'));
    }

    /**
     * @dataProvider objectFactories
     * @param mixed $factory
     */
    public function testMultipleGetServicesShouldBeEqual($factory): void
    {
        $this->providerProphecy->getFactories()->willReturn([ 'service' => $factory ]);
        // A factory can also be used as extension, as it's based on the same signature
        $this->providerProphecy->getExtensions()->willReturn([ 'extension' => $factory ]);

        $container = new Container([$this->providerProphecy->reveal()]);

        $serviceOne = $container->get('service');
        $serviceTwo = $container->get('service');

        $extensionOne = $container->get('extension');
        $extensionTwo = $container->get('extension');

        self::assertSame($serviceOne, $serviceTwo);
        self::assertSame($extensionOne, $extensionTwo);
    }

    public function testPassesContainerAsParameter(): void
    {
        $this->providerProphecy->getFactories()->willReturn([
            'service' => function () {
                return new Service();
            },
            'container' => function (ContainerInterface $container) {
                return $container;
            }
        ]);
        $container = new Container([$this->providerProphecy->reveal()]);

        self::assertNotSame($container, $container->get('service'));
        self::assertSame($container, $container->get('container'));
    }

    public function testNullValueEntry(): void
    {
        $this->providerProphecy->getFactories()->willReturn([
            'null' => function () {
                return null;
            }
        ]);
        $container = new Container([$this->providerProphecy->reveal()]);

        self::assertTrue($container->has('null'));
        self::assertNull($container->get('null'));
    }

    public function testNullValueEntryCallsFactoryOnlyOnce(): void
    {
        $calledCount = 0;
        $factory = function () use (&$calledCount) {
            $calledCount++;
            return null;
        };
        $this->providerProphecy->getFactories()->willReturn([
            'null' => $factory,
        ]);
        $container = new Container([$this->providerProphecy->reveal()]);

        self::assertTrue($container->has('null'));
        self::assertNull($container->get('null'));
        self::assertTrue($container->has('null'));
        self::assertNull($container->get('null'));
        self::assertEquals($calledCount, 1);
    }

    public function testHas(): void
    {
        $this->providerProphecy->getFactories()->willReturn([
            'service' => function () {
                return new Service();
            },
            'param' => function () {
                return 'value';
            },
            'int' => function () {
                return 2;
            },
            'bool' => function () {
                return false;
            },
            'null' => function () {
                return null;
            },
            '0' => function () {
                return 0;
            }
        ]);
        $container = new Container([$this->providerProphecy->reveal()]);

        self::assertTrue($container->has('param'));
        self::assertTrue($container->has('service'));
        self::assertTrue($container->has('int'));
        self::assertTrue($container->has('bool'));
        self::assertTrue($container->has('null'));
        self::assertFalse($container->has('non_existent'));
    }

    public function testDefaultEntry(): void
    {
        $default = ['param' => 'value'];
        $container = new Container([], $default);

        self::assertSame('value', $container->get('param'));
    }

    public function testGetValidatesKeyIsPresent(): void
    {
        $container = new Container();

        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessage('Container entry "foo" is not available.');
        $container->get('foo');
    }

    /**
     * @dataProvider objectFactories
     * @param mixed $factory
     */
    public function testExtension($factory): void
    {
        $providerA = $this->providerProphecy;
        $providerA->getFactories()->willReturn(['service' => $factory]);

        $providerB = $this->createServiceProviderProphecy();
        $providerB->getExtensions()->willReturn([
            'service' => function (ContainerInterface $c, Service $s) {
                $s->value = 'value';
                return $s;
            },
        ]);
        $iterator = (function () use ($providerA, $providerB): iterable {
            yield $providerA->reveal();
            yield $providerB->reveal();
        })();
        $container = new Container($iterator);

        self::assertSame('value', $container->get('service')->value);
    }

    /**
     * @dataProvider objectFactories
     * @param mixed $factory
     */
    public function testExtendingLaterProvider($factory): void
    {
        $providerA = $this->providerProphecy;
        $providerA->getFactories()->willReturn(['service' => $factory]);

        $providerB = $this->createServiceProviderProphecy();
        $providerB->getExtensions()->willReturn([
            'service' => function (ContainerInterface $c, Service $s) {
                $s->value = 'value';
                return $s;
            },
        ]);
        $container = new Container([$providerB->reveal(), $providerA->reveal()]);

        self::assertSame('value', $container->get('service')->value);
    }

    /**
     * @dataProvider objectFactories
     * @param mixed $factory
     */
    public function testExtendingOwnFactory($factory): void
    {
        $this->providerProphecy->getFactories()->willReturn(['service' => $factory]);
        $this->providerProphecy->getExtensions()->willReturn(
            [
                'service' => function (ContainerInterface $c, Service $s) {
                    $s->value = 'value';
                    return $s;
                },
            ]
        );
        $container = new Container([$this->providerProphecy->reveal()]);

        self::assertSame('value', $container->get('service')->value);
    }

    public function testExtendingNonExistingFactory(): void
    {
        $this->providerProphecy->getExtensions()->willReturn([
            'service' => function (ContainerInterface $c, Service $s = null) {
                if ($s === null) {
                    $s = new Service();
                }
                $s->value = 'value';
                return $s;
            },
        ]);
        $container = new Container([$this->providerProphecy->reveal()]);

        self::assertSame('value', $container->get('service')->value);
    }

    /**
     * @dataProvider objectFactories
     * @param mixed $factory
     */
    public function testMultipleExtensions($factory): void
    {
        $providerA = $this->providerProphecy;
        $providerA->getFactories()->willReturn(['service' => $factory]);

        $providerB = $this->createServiceProviderProphecy();
        $providerB->getExtensions()->willReturn([
            'service' => function (ContainerInterface $c, Service $s) {
                $s->value = '1';
                return $s;
            },
        ]);

        $providerC = $this->createServiceProviderProphecy();
        $providerC->getExtensions()->willReturn([
            'service' => function (ContainerInterface $c, Service $s) {
                $s->value .= '2';
                return $s;
            },
        ]);
        $container = new Container([$providerA->reveal(), $providerB->reveal(), $providerC->reveal()]);

        self::assertSame('12', $container->get('service')->value);
    }

    /**
     * @dataProvider objectFactories
     * @param mixed $factory
     */
    public function testEntryOverriding($factory): void
    {
        $providerA = $this->providerProphecy;
        $providerA->getFactories()->willReturn(['service' => $factory]);

        $providerB = $this->createServiceProviderProphecy();
        $providerB->getFactories()->willReturn(['service' => function () {
            return 'value';
        }]);

        $container = new Container([$providerA->reveal(), $providerB->reveal()]);

        self::assertNotInstanceOf(Service::class, $container->get('service'));
        self::assertEquals('value', $container->get('service'));
    }

    public function testCyclicDependency(): void
    {
        $this->providerProphecy->getFactories()->willReturn([
            'A' => function (ContainerInterface $container) {
                return $container->get('B');
            },
            'B' => function (ContainerInterface $container) {
                return $container->get('A');
            },
        ]);

        $container = new Container([$this->providerProphecy->reveal()]);

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('Container entry "A" is part of a cyclic dependency chain.');
        $container->get('A');
    }

    public function testCyclicDependencyRetrievedTwice(): void
    {
        $this->providerProphecy->getFactories()->willReturn([
            'A' => function (ContainerInterface $container) {
                return $container->get('B');
            },
            'B' => function (ContainerInterface $container) {
                return $container->get('A');
            },
        ]);

        $container = new Container([$this->providerProphecy->reveal()]);

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('Container entry "A" is part of a cyclic dependency chain.');
        try {
            $container->get('A');
        } catch (ContainerExceptionInterface $e) {
        }
        self::assertTrue($container->has('A'));
        $container->get('A');
    }

    public function testNullContainer(): void
    {
        $container = new Container;
        self::assertFalse($container->has('foo'));
    }

    public function testNullContainerWithDefaultEntries(): void
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
    public function objectFactories(): array
    {
        return [
            [
                // Static callback
                [ self::class, 'factory']
            ],
            [
                // Closure
                function () {
                    return new Service();
                }
            ],
            [
                // Invokable
                new class {
                    public function __invoke(): Service
                    {
                        return new Service();
                    }
                }
            ],
            [
                // Non static factory
                [
                    new class {
                        public function factory(): Service
                        {
                            return new Service();
                        }
                    },
                    'factory'
                ]
            ],
        ];
    }
}
