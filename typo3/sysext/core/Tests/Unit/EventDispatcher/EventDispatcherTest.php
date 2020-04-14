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

namespace TYPO3\CMS\Core\Tests\Unit\EventDispatcher;

use Prophecy\Prophecy\ObjectProphecy;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class EventDispatcherTest extends UnitTestCase
{
    /**
     * @var ListenerProviderInterface|ObjectProphecy
     */
    protected $containerProphecy;

    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listenerProviderProphecy = $this->prophesize();
        $this->listenerProviderProphecy->willImplement(ListenerProviderInterface::class);

        $this->eventDispatcher = new EventDispatcher(
            $this->listenerProviderProphecy->reveal()
        );
    }

    /**
     * @test
     */
    public function implementsPsrInterface()
    {
        self::assertInstanceOf(EventDispatcherInterface::class, $this->eventDispatcher);
    }

    /**
     * @test
     * @dataProvider callables
     */
    public function dispatchesEvent(callable $callable)
    {
        $event = new \stdClass();
        $event->invoked = 0;

        $this->listenerProviderProphecy->getListenersForEvent($event)->will(function () use ($callable): iterable {
            yield $callable;
        });

        $ret = $this->eventDispatcher->dispatch($event);
        self::assertSame($event, $ret);
        self::assertEquals(1, $event->invoked);
    }

    /**
     * @test
     * @dataProvider callables
     */
    public function doesNotDispatchStoppedEvent(callable $callable)
    {
        $event = new class() implements StoppableEventInterface {
            public $invoked = 0;

            public function isPropagationStopped(): bool
            {
                return true;
            }
        };

        $this->listenerProviderProphecy->getListenersForEvent($event)->will(function () use ($callable): iterable {
            yield $callable;
        });

        $ret = $this->eventDispatcher->dispatch($event);
        self::assertSame($event, $ret);
        self::assertEquals(0, $event->invoked);
    }

    /**
     * @test
     * @dataProvider callables
     */
    public function dispatchesMultipleListeners(callable $callable)
    {
        $event = new \stdClass();
        $event->invoked = 0;

        $this->listenerProviderProphecy->getListenersForEvent($event)->will(function () use ($callable): iterable {
            yield $callable;
            yield $callable;
        });

        $ret = $this->eventDispatcher->dispatch($event);
        self::assertSame($event, $ret);
        self::assertEquals(2, $event->invoked);
    }

    /**
     * @test
     * @dataProvider callables
     */
    public function stopsOnStoppedEvent(callable $callable)
    {
        $event = new class() implements StoppableEventInterface {
            public $invoked = 0;
            public $stopped = false;

            public function isPropagationStopped(): bool
            {
                return $this->stopped;
            }
        };

        $this->listenerProviderProphecy->getListenersForEvent($event)->will(function () use ($callable): iterable {
            yield $callable;
            yield function (object $event): void {
                $event->invoked += 1;
                $event->stopped = true;
            };
            yield $callable;
        });

        $ret = $this->eventDispatcher->dispatch($event);
        self::assertSame($event, $ret);
        self::assertEquals(2, $event->invoked);
    }

    /**
     * @test
     */
    public function listenerExceptionIsPropagated()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionCode(1563270337);

        $event = new \stdClass();

        $this->listenerProviderProphecy->getListenersForEvent($event)->will(function (): iterable {
            yield function (object $event): void {
                throw new \BadMethodCallException('some invalid state', 1563270337);
            };
        });

        $this->eventDispatcher->dispatch($event);
    }

    /**
     * Provider for callables.
     * Either an invokable, class/method combination or a closure.
     */
    public function callables(): array
    {
        return [
            [
                // Invokable
                new class() {
                    public function __invoke(object $event): void
                    {
                        $event->invoked += 1;
                    }
                },
            ],
            [
                // Class + method
                [
                    new class() {
                        public function onEvent(object $event): void
                        {
                            $event->invoked += 1;
                        }
                    },
                    'onEvent',
                ]
            ],
            [
                // Closure
                function (object $event): void {
                    $event->invoked += 1;
                },
            ]
        ];
    }
}
